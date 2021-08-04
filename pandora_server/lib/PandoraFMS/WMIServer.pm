package PandoraFMS::WMIServer;
##########################################################################
# Pandora FMS WMI Server.
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
##########################################################################
# Copyright (c) 2005-2021 Artica Soluciones Tecnologicas S.L
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public License
# as published by the Free Software Foundation; version 2
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
##########################################################################

use strict;
use warnings;

use threads;
use threads::shared;
use Thread::Semaphore;

use POSIX qw(strftime);
use HTML::Entities;

# Default lib dir for RPM and DEB packages
use lib '/usr/lib/perl5';

use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;
use PandoraFMS::ProducerConsumerServer;

# Inherits from PandoraFMS::ProducerConsumerServer
our @ISA = qw(PandoraFMS::ProducerConsumerServer);

# Global variables
my @TaskQueue :shared;
my %PendingTasks :shared;
my $Sem :shared;
my $TaskSem :shared;

########################################################################################
# NetworkServer class constructor.
########################################################################################
sub new ($$;$) {
	my ($class, $config, $dbh) = @_;

	return undef unless $config->{'wmiserver'} == 1;

	# Check for a WMI client
	if (system ($config->{'wmi_client'} . " >$DEVNULL 2>&1") >> 8 != 1) {
		logger ($config, ' [E] ' . $config->{'wmi_client'} . " not found. " . $config->{'rb_product_name'} . " WMI Server needs a DCOM/WMI client.", 1);
		print_message ($config, ' [E] ' . $config->{'wmi_client'} . " not found. " . $config->{'rb_product_name'} . " WMI Server needs a DCOM/WMI client.", 1);
		return undef;
	}

	# Initialize semaphores and queues
	@TaskQueue = ();
	%PendingTasks = ();
	$Sem = Thread::Semaphore->new;
	$TaskSem = Thread::Semaphore->new (0);

	# Call the constructor of the parent class
	my $self = $class->SUPER::new($config, WMISERVER, \&PandoraFMS::WMIServer::data_producer, \&PandoraFMS::WMIServer::data_consumer, $dbh);

	bless $self, $class;
	return $self;
}

###############################################################################
# Run.
###############################################################################
sub run ($) {
	my $self = shift;
	my $pa_config = $self->getConfig ();

	print_message ($pa_config, " [*] Starting " . $pa_config->{'rb_product_name'} . " WMI Server.", 1);
	$self->setNumThreads ($pa_config->{'wmi_threads'});
	$self->SUPER::run (\@TaskQueue, \%PendingTasks, $Sem, $TaskSem);
}

###############################################################################
# Data producer.
###############################################################################
sub data_producer ($) {
	my $self = shift;
	my ($pa_config, $dbh) = ($self->getConfig (), $self->getDBH ());

	my @tasks;
	my @rows;

	if (pandora_is_master($pa_config) == 0) {
		@rows = get_db_rows ($dbh, 'SELECT tagente_modulo.id_agente_modulo, tagente_modulo.flag, tagente_estado.current_interval + tagente_estado.last_execution_try  AS time_left, last_execution_try
			FROM tagente, tagente_modulo, tagente_estado
			WHERE server_name = ?
			AND tagente_modulo.id_agente = tagente.id_agente
			AND	tagente.disabled = 0
			AND tagente_modulo.id_modulo = 6
			AND tagente_modulo.disabled = 0
			AND	tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
			AND ((tagente_estado.last_execution_try + tagente_estado.current_interval) < UNIX_TIMESTAMP() 
			OR tagente_modulo.flag = 1)				
			ORDER BY tagente_modulo.flag DESC, time_left ASC, last_execution_try ASC', safe_input($pa_config->{'servername'}));
	} else {
		@rows = get_db_rows ($dbh, 'SELECT DISTINCT(tagente_modulo.id_agente_modulo), tagente_modulo.flag, tagente_estado.current_interval + tagente_estado.last_execution_try AS time_left, last_execution_try
			FROM tagente, tagente_modulo, tagente_estado, tserver
			WHERE ((server_name = ?) OR (server_name = ANY(SELECT name FROM tserver WHERE status <> 1 AND server_type = ?)))
			AND tagente_modulo.id_agente = tagente.id_agente
			AND tagente.disabled = 0
			AND tagente_modulo.disabled = 0
			AND tagente_modulo.id_modulo = 6
			AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
			AND ((tagente_estado.last_execution_try + tagente_estado.current_interval) < UNIX_TIMESTAMP() OR tagente_modulo.flag = 1 )
			ORDER BY tagente_modulo.flag DESC, time_left ASC, last_execution_try ASC', safe_input($pa_config->{'servername'}), WMISERVER);
	}

	foreach my $row (@rows) {
		
		# Reset forced execution flag
		if ($row->{'flag'} == 1) {
			db_do ($dbh, 'UPDATE tagente_modulo SET flag = 0 WHERE id_agente_modulo = ?', $row->{'id_agente_modulo'});
		}

		push (@tasks, $row->{'id_agente_modulo'});
	}

	return @tasks;
}

###############################################################################
# Data consumer.
###############################################################################
sub data_consumer ($$) {
	my ($self, $module_id) = @_;
	my ($pa_config, $dbh) = ($self->getConfig (), $self->getDBH ());
	
	my $module = get_db_single_row ($dbh, 'SELECT * FROM tagente_modulo WHERE id_agente_modulo = ?', $module_id);
	return unless defined $module;
	
	# Initialize macros.
	my %macros = (
		'_agentcustomfield_\d+_' => undef,
	);

	# Build command to execute
	my $wmi_command = '';
	if (defined ($module->{'plugin_pass'}) && $module->{'plugin_pass'} ne "") {
		my $user = safe_output(subst_column_macros($module->{'plugin_user'}, \%macros, $pa_config, $dbh, undef, $module));
		my $pass = safe_output(pandora_output_password($pa_config, subst_column_macros($module->{'plugin_pass'}, \%macros, $pa_config, $dbh, undef, $module)));
		$wmi_command = $pa_config->{'wmi_client'} . ' -U "' . $user . '"%"' . $pass . '"';
	}
	elsif (defined ($module->{'plugin_user'}) && $module->{'plugin_user'} ne "") {
		my $user = safe_output(subst_column_macros($module->{'plugin_user'}, \%macros, $pa_config, $dbh, undef, $module));
		$wmi_command = $pa_config->{'wmi_client'} . ' -U "' . $user . '"';
	}
	else {
		$wmi_command = $pa_config->{'wmi_client'} . ' -N';
	}

	#Check ip_taget macro
	if ($module->{'ip_target'} eq '_address_') {
		$module->{'ip_target'} = get_db_value($dbh, "SELECT direccion FROM tagente WHERE id_agente=?", $module->{'id_agente'});
	}
	
	# Use a custom namespace
	my $namespace = $module->{'tcp_send'};
	if (defined($namespace) && $namespace ne '') {
		$namespace =~ s/\"/\'/g;
		$wmi_command .= ' --namespace="' . $namespace . '"';
	}

	# WMI query
	my $wmi_query = safe_output ($module->{'snmp_oid'});
	$wmi_query =~ s/\"/\'/g;

	$wmi_command .= ' //' . $module->{'ip_target'} . ' "' . $wmi_query . '"';
	logger ($pa_config, "Executing AM # $module_id WMI command '$wmi_command'", 9);

	# Execute command
	my $module_data = `$wmi_command 2>$DEVNULL`;
	if ($? ne 0 || ! defined ($module_data)) {
		pandora_update_module_on_error ($pa_config, $module, $dbh);
		return;
	}

	# Parse command output. Example:
	# CLASS: Win32_Processor
	# DeviceID|LoadPercentage
	# CPU0|2
	my @output = split("\n", $module_data);
	if ($#output < 2) {
		pandora_update_module_on_error ($pa_config, $module, $dbh);
		return;
	}

	# Check for errors
	if ($output[0] =~ m/ERROR/) {
		pandora_update_module_on_error ($pa_config, $module, $dbh);
		return;
	} 

	# Get the first row (line 3)
	my @row = split(/\|/, $output[2]);

	# Get the specified column
	if (defined ($module->{'tcp_port'})) {
		$wmi_query =~ m/SELECT\s(.+)\sFROM/ig;
		my @wmi_columns = split /\s*,\s*/, $1;
		my $selected_col = $wmi_columns[$module->{'tcp_port'}];

		if (!defined($selected_col)) {
			logger($pa_config, 'Warning, WMI module ' . safe_output($module->{'name'}) . ' column missconfigured, using first available.', 10);
			$selected_col = shift @wmi_columns;
		}

		# Get result col number
		my @output_col = split(/\|/, $output[1]);

		# Find column number
		my $col_number;

    for(my $i = 0; $i < @output_col; $i++ ) {
        if( $output_col[$i] =~ /$selected_col/i ) {
        		$col_number = $i;	
            last;
        }
    }		
		
		$module_data = $row[$col_number] if (defined ($col_number) &&  defined ($row[$col_number]));
		if ($module_data =~ m/^ERROR/) {
			pandora_update_module_on_error ($pa_config, $module, $dbh);
			return;
		} 
	}
		
	# Regexp
	if ($module->{'snmp_community'} ne ''){
		my $filter = $module->{'snmp_community'};
		eval {
			no warnings;
			$module_data = ($module_data =~ /$filter/) ? 1 : 0;
		};
	}

	my $utimestamp = time ();
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime($utimestamp));

	my %data = ("data" => $module_data);
	pandora_process_module ($pa_config, \%data, '', $module, '', $timestamp, $utimestamp, $self->getServerID (), $dbh);

	my $agent_os_version = get_db_value ($dbh, 'SELECT os_version FROM tagente WHERE id_agente = ?', $module->{'id_agente'});
	
	if ($agent_os_version eq ''){
		$agent_os_version = $pa_config->{'servername'}.'_WMI';
	}

	pandora_update_agent ($pa_config, $timestamp, $module->{'id_agente'}, undef, undef, -1, $dbh);
}

1;
__END__
