<?php
/**
 * View for delete agents in Massive Operations
 *
 * @category   Configuration
 * @package    Pandora FMS
 * @subpackage Massive Operations
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2022 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

// Begin.
check_login();

if ((bool) check_acl($config['id_user'], 0, 'AW') === false) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access massive agent deletion section'
    );
    include 'general/noaccess.php';
    return;
}

require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_alerts.php';
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_massive_operations.php';


function process_manage_delete($id_agents)
{
    if (empty($id_agents)) {
        ui_print_error_message(__('No agents selected'));
        return false;
    }

    $id_agents = (array) $id_agents;

    $copy_modules = (bool) get_parameter('copy_modules');
    $copy_alerts = (bool) get_parameter('copy_alerts');

    $error = false;
    $count_deleted = 0;
    $agent_id_restore = 0;
    foreach ($id_agents as $id_agent) {
        $success = agents_delete_agent($id_agent);
        if (! $success) {
            $agent_id_restore = $id_agent;
            break;
        }

        $count_deleted++;
    }

    if (! $success) {
        ui_print_error_message(
            sprintf(
                __('There was an error deleting the agent, the operation has been cancelled Could not delete agent %s'),
                agents_get_name($agent_id_restore)
            )
        );

        return false;
    } else {
        ui_print_success_message(
            sprintf(
                __(
                    'Successfully deleted (%s)',
                    $count_deleted
                )
            )
        );

        return true;
    }
}


$id_group = (int) get_parameter('id_group');
$id_agents = get_parameter('id_agents');
$recursion = get_parameter('recursion');

$delete = (bool) get_parameter_post('delete');

if ($delete === true) {
    $result = process_manage_delete($id_agents);

    $info = '{"Agent":"'.implode(',', $id_agents).'"}';
    if ($result === true) {
        db_pandora_audit(
            'Massive management',
            'Delete agent ',
            false,
            false,
            $info
        );
    } else {
        db_pandora_audit(
            'Massive management',
            'Fail try to delete agent',
            false,
            false,
            $info
        );
    }
}


if (is_metaconsole() === false && is_management_allowed() === false) {
    if (\is_metaconsole() === false) {
        $url_link = '<a target="_blank" href="'.ui_get_meta_url($url).'">';
        $url_link .= __('metaconsole');
        $url_link .= '</a>';
    } else {
        $url_link = __('any node');
    }

    \ui_print_warning_message(
        __(
            'This node is configured with centralized mode. All alert calendar information is read only. Go to %s to manage it.',
            $url_link
        )
    );
}


// $groups = users_get_groups();
$table = new stdClass;
$table->id = 'delete_table';
$table->class = 'databox filters';
$table->width = '100%';
$table->data = [];
$table->style = [];
$table->style[0] = 'font-weight: bold;';
$table->style[2] = 'font-weight: bold';
$table->size = [];
$table->size[0] = '15%';
$table->size[1] = '35%';
$table->size[2] = '15%';
$table->size[3] = '35%';

$table->data = [];
$table->data[0][0] = __('Group');
$table->data[0][1] = html_print_select_groups(
    false,
    'AW',
    true,
    'id_group',
    $id_group,
    false,
    '',
    '',
    true
);
$table->data[0][2] = __('Group recursion');
$table->data[0][3] = html_print_checkbox(
    'recursion',
    1,
    $recursion,
    true,
    false
);

$status_list = [];
$status_list[AGENT_STATUS_NORMAL] = __('Normal');
$status_list[AGENT_STATUS_WARNING] = __('Warning');
$status_list[AGENT_STATUS_CRITICAL] = __('Critical');
$status_list[AGENT_STATUS_UNKNOWN] = __('Unknown');
$status_list[AGENT_STATUS_NOT_NORMAL] = __('Not normal');
$status_list[AGENT_STATUS_NOT_INIT] = __('Not init');
$table->data[1][0] = __('Status');
$table->data[1][1] = html_print_select(
    $status_list,
    'status_agents',
    'selected',
    '',
    __('All'),
    AGENT_STATUS_ALL,
    true
);

$table->data[1][2] = __('Show agents');
$table->data[1][3] = html_print_select(
    [
        0 => 'Only enabled',
        1 => 'Only disabled',
    ],
    'disabled',
    2,
    '',
    __('All'),
    2,
    true,
    false,
    true,
    '',
    false,
    'width:30%;'
);

if (is_metaconsole() === true) {
    $servers = metaconsole_get_servers();
    $server_fields = [];
    foreach ($servers as $key => $server) {
        $server_fields[$key] = $server['server_name'];
    }

    $table->data[2][2] = __('Node');
    $table->data[2][3] = html_print_select(
        $server_fields,
        'node',
        0,
        '',
        __('All'),
        0,
        true
    );
}



$table->data[3][0] = __('Agents');
$table->data[3][0] .= '<span id="agent_loading" class="invisible">';
$table->data[3][0] .= html_print_image('images/spinner.png', true);
$table->data[3][0] .= '</span>';
$table->data[3][1] = html_print_select(
    agents_get_agents_selected(
        array_keys(users_get_groups($config['id_user'], 'AW', false))
    ),
    'id_agents[]',
    0,
    false,
    '',
    '',
    true,
    true,
    true,
    '',
    false,
    'min-width: 500px; max-width: 500px; max-height: 100px',
    false,
    false,
    false,
    '',
    false,
    false,
    false,
    false,
    true,
    true,
    true
);

$url = 'index.php?sec=gmassive&sec2=godmode/massive/massive_operations&option=delete_agents';
if (is_metaconsole() === true) {
    $ulr = 'index.php?sec=advanced&sec2=advanced/massive_operations&tab=massive_agents&pure=0&option=delete_agents';
}

echo '<form method="post" id="form_agents" action="'.$url.'">';
html_print_table($table);

if (is_metaconsole() === true || is_management_allowed() === true) {
    attachActionButton('delete', 'delete', $table->width);
}

echo '</form>';

echo '<h3 class="error invisible" id="message"> </h3>';

ui_require_jquery_file('form');
ui_require_jquery_file('pandora.controls');
?>

<script type="text/javascript">
    $(document).ready (function () {
        var recursion;
        $("#checkbox-recursion").click(function () {
            recursion = this.checked ? 1 : 0;
            $("#id_group").trigger("change");
        });

        var disabled;
        $("#disabled").click(function () {
            disabled = this.value;
            $("#id_group").trigger("change");
        });

        $("#id_group").pandoraSelectGroupAgent ({
            status_agents: function () {
                return $("#status_agents").val();
            },
            agentSelect: "select#id_agents",
            privilege: "AW",
            recursion: function() {
                return recursion;
            },
            disabled: function() {
                return disabled;
            }
        });

        $("#status_agents").change(function() {
            $("#id_group").trigger("change");
        });

        disabled = 2;

        //$("#id_group").trigger("change");
    });
</script>
