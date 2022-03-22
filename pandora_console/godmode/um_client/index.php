<?php
/**
 * Update Manager Client.
 *
 * This is an atomic package, this file must be referenced from general product
 * menu entries in order to give Update Manager Client work.
 *
 * DO NOT EDIT THIS FILE. ONLY SETTINGS SECTION.
 *
 * @category   Class
 * @package    Update Manager
 * @subpackage Client
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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
global $config;

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/resources/helpers.php';

use PandoraFMS\Enterprise\Metaconsole\Synchronizer;
use UpdateManager\UI\Manager;

if (function_exists('check_login') === true) {
    check_login();
}

if (function_exists('check_acl') === true
    && function_exists('is_user_admin') === true
) {
    if ((bool) check_acl($config['id_user'], 0, 'PM') !== true
        && (bool) is_user_admin($config['id_user']) !== true
    ) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access Setup Management'
        );
        include 'general/noaccess.php';
        return;
    }
}

if (function_exists('db_get_value') === true) {
    $license = db_get_value(
        db_escape_key_identifier('value'),
        'tupdate_settings',
        db_escape_key_identifier('key'),
        'customer_key'
    );
}

if (empty($license) === true) {
    $license = 'PANDORA-FREE';
}

$mode_str = '';
if (isset($mode) === false) {
    $mode = null;
}

if ($mode === Manager::MODE_ONLINE) {
    $mode_str = 'online';
} else if ($mode === Manager::MODE_OFFLINE) {
    $mode_str = 'offline';
}

if (function_exists('enterprise_hook') === true) {
    enterprise_include_once('/include/functions_license.php');
    $license_data = enterprise_hook('license_get_info');
    if ($license_data !== ENTERPRISE_NOT_HOOK) {
        $days_to_expiry = ((strtotime($license_data['expiry_date']) - time()) / (60 * 60 * 24));

        if ((int) $license_data['limit_mode'] === 0) {
            $agent_table = (is_metaconsole() === true) ? 'tmetaconsole_agent' : 'tagente';
            $limit = db_get_value('count(*)', $agent_table, 'disabled', 0);
        } else {
            $limit = db_get_value('count(*)', 'tagente_modulo', 'disabled', 0);
        }

        if ($limit > $license_data['limit']) {
            ui_print_warning_message(
                __(
                    'You cannot use update manager %s. You are exceding monitoring limits by %s elements. Please update your license or disable enterprise section by moving enterprise directory to another location and try again.',
                    $mode_str,
                    ($limit - $license_data['limit'])
                )
            );
            return;
        }

        if ($days_to_expiry < 0) {
            ui_print_warning_message(
                __(
                    'You cannot use update manager %s. This license has expired %d days ago. Please update your license or disable enterprise section by moving enterprise directory to another location and try again.',
                    $mode_str,
                    abs($days_to_expiry)
                )
            );
            return;
        }

        if (rtrim($license_data['licensed_to']) === Manager::PANDORA_TRIAL_ISSUER) {
            if (function_exists('get_product_name') === true) {
                $product_name = get_product_name();
            } else {
                $product_name = 'Pandora FMS';
            }

            ui_print_info_message(
                __(
                    'You cannot use update manager %s. This license is a trial license to test all %s features. Please update your license to unlock all %s features.',
                    $mode_str,
                    $product_name,
                    $product_name
                )
            );
            return;
        }
    } else {
        $license_data = [];
        $license_data['count_enabled'] = db_get_value(
            'count(*)',
            'tagente',
            'disabled',
            0
        );
    }
}


// Set dbh.
if (is_array($config) === true && $config['dbconnection'] !== null) {
    $dbh = (object) $config['dbconnection'];
} else {
    $dbh = null;
}

// Retrieve current definition.
if ($dbh !== null) {
    $stm = $dbh->query(
        'SELECT `value` FROM `tconfig`
         WHERE `token`="current_package"'
    );
    if ($stm !== false) {
        $current_package = $stm->fetch_array();
        if ($current_package !== null) {
            $current_package = $current_package[0];
        }
    }

    if (empty($current_package) === true) {
        // If no current_package is present, use current_package_enterprise.
        $stm = $dbh->query(
            'SELECT MAX(`value`) FROM `tconfig`
            WHERE `token`="current_package_enterprise"'
        );
        if ($stm !== false) {
            $current_package = $stm->fetch_array();

            if ($current_package !== null) {
                $current_package = $current_package[0];
            }
        }
    }

    $mr = 0;
    $stm = $dbh->query(
        'SELECT MAX(`value`) FROM `tconfig`
         WHERE `token`="MR" OR `token`="minor_release"'
    );
    if ($stm !== false) {
        $mr = $stm->fetch_array();
        if ($mr !== null) {
            $mr = $mr[0];
        }
    }

    $puid = null;
    $stm = $dbh->query(
        'SELECT `value` FROM `tconfig`
         WHERE `token`="pandora_uid"'
    );
    if ($stm !== false) {
        $puid = $stm->fetch_array();
        if ($puid !== null) {
            $puid = $puid[0];
        }
    }
} else {
    $current_package = 0;
    $mr = 0;
    $puid = null;
}

if (is_ajax() !== true) {
    ?>
    <script type="text/javascript">
        var clientMode = '<?php echo $mode; ?>';
    </script>
    <?php
    if (function_exists('db_get_value_sql') === true) {
        $server_version = (string) db_get_value_sql(
            'SELECT `version` FROM `tserver` ORDER BY `master` DESC'
        );
        if ($server_version !== false
            && preg_match('/NG\.(\d\.*\d*?) /', $server_version, $matches) > 0
        ) {
            if ((float) $matches[1]  !== (float) $current_package) {
                ui_print_warning_message(
                    __(
                        'Master server version %s does not match console version %s.',
                        (float) $matches[1],
                        (float) $current_package
                    )
                );
            }
        }
    }

    $PHPmemory_limit_min = config_return_in_bytes('800M');
    $PHPmemory_limit = config_return_in_bytes(ini_get('memory_limit'));
    if ($PHPmemory_limit < $PHPmemory_limit_min && $PHPmemory_limit !== -1) {
        $msg = __(
            '\'%s\' recommended value is %s or greater. Please, change it on your PHP configuration file (php.ini) or contact with administrator',
            'memory_limit',
            '800M'
        );
        if (function_exists('ui_print_warning_message') === true) {
            ui_print_warning_message($msg);
        } else {
            echo $msg;
        }
    }
}

// Load styles.
if (function_exists('ui_require_css_file') === true) {
    ui_require_css_file('pandora', 'godmode/um_client/resources/styles/');
}

if (isset($mode) === false) {
    $mode = Manager::MODE_ONLINE;
    if (function_exists('get_parameter') === true) {
        $mode = (int) get_parameter('mode', null);
    } else {
        $mode = ($_REQUEST['mode'] ?? Manager::MODE_ONLINE);
    }
}

if (is_int($mode) === false) {
    switch ($mode) {
        case 'offline':
            $mode = Manager::MODE_OFFLINE;
        break;

        case 'register':
            $mode = Manager::MODE_REGISTER;
        break;

        case 'online':
        default:
            $mode = Manager::MODE_ONLINE;
        break;
    }
}

$dbhHistory = null;

if (is_array($config) === true
    && (bool) $config['history_db_enabled'] === true
) {
    ob_start();
    $password = $config['history_db_pass'];
    if (function_exists('io_output_password') === true) {
        $password = io_output_password($config['history_db_pass']);
    }

    $dbhHistory = db_connect(
        $config['history_db_host'],
        $config['history_db_name'],
        $config['history_db_user'],
        $password,
        $config['history_db_port']
    );
    ob_get_clean();

    if ($dbhHistory === false) {
        $dbhHistory = null;
    }
}

$url_update_manager = null;
$homedir = sys_get_temp_dir();
$dbconnection = null;
$remote_config = null;
$is_metaconsole = false;
$insecure = false;
$pandora_url = ui_get_full_url('godmode/um_client', false, false, false);

if (is_array($config) === true) {
    $allowOfflinePatches = false;
    if (isset($config['allow_offline_patches']) === true) {
        $allowOfflinePatches = (bool) $config['allow_offline_patches'];
    }

    if (isset($config['secure_update_manager']) === false) {
        $config['secure_update_manager'] = null;
    }

    if ($config['secure_update_manager'] === ''
        || $config['secure_update_manager'] === null
    ) {
        $insecure = false;
    } else {
        // Directive defined.
        $insecure = !$config['secure_update_manager'];
    }

    if ((bool) is_ajax() === false) {
        if ($mode === Manager::MODE_ONLINE
            && ($puid === null || $puid === 'OFFLINE')
        ) {
            ui_print_error_message(__('Update manager online requires registration.'));
        }

        if ($mode === Manager::MODE_OFFLINE) {
            ui_print_warning_message(
                __('Applying offline patches may make your console unusable, we recommend to completely backup your files before applying any patch.')
            );
        }
    }

    $url_update_manager = $config['url_update_manager'];
    $homedir = $config['homedir'];
    $dbconnection = $config['dbconnection'];
    $remote_config = $config['remote_config'];
    if (function_exists('is_metaconsole') === true) {
        $is_metaconsole = (bool) is_metaconsole();
    }

    if ($is_metaconsole === false) {
        if ((bool) $config['node_metaconsole'] === true) {
            $url_update_manager = $config['metaconsole_base_url'];
            $url_update_manager .= 'godmode/um_client/api.php';
        }
    } else if ($is_metaconsole === true) {
        $sc = new Synchronizer();
        $url_meta_base = ui_get_full_url('/', false, false, false);
        $sc->apply(
            function ($node, $sync) use ($url_meta_base) {
                try {
                    global $config;

                    $sync->connect($node);

                    $config['metaconsole_base_url'] = db_get_value(
                        'value',
                        'tconfig',
                        'token',
                        'metaconsole_base_url'
                    );

                    if ($config['metaconsole_base_url'] === false) {
                        // Unset to create new value if does not exist previously.
                        $config['metaconsole_base_url'] = null;
                    }

                    config_update_value(
                        'metaconsole_base_url',
                        $url_meta_base
                    );

                    $sync->disconnect();
                } catch (Exception $e) {
                    ui_print_error_message(
                        'Cannot update node settings: ',
                        $e->getMessage()
                    );
                }
            }
        );
    }
}

$proxy = null;
if (empty($config['update_manager_proxy_server']) === false
    || empty($config['update_manager_proxy_port']) === false
    || empty($config['update_manager_proxy_user']) === false
    || empty($config['update_manager_proxy_password']) === false
) {
    $proxy = [
        'host'     => $config['update_manager_proxy_server'],
        'port'     => $config['update_manager_proxy_port'],
        'user'     => $config['update_manager_proxy_user'],
        'password' => $config['update_manager_proxy_password'],
    ];
}

$ui = new Manager(
    ((is_array($config) === true) ? $pandora_url : 'http://'.$_SERVER['SERVER_ADDR'].'/'),
    ((is_array($config) === true) ? ui_get_full_url('ajax.php') : ''),
    ((is_array($config) === true) ? 'godmode/um_client/index' : ''),
    [
        'url'                    => $url_update_manager,
        'insecure'               => $insecure,
        'license'                => $license,
        'limit_count'            => ((is_array($license_data) === true) ? $license_data['count_enabled'] : null),
        'language'               => ((is_array($config) === true) ? $config['language'] : null),
        'timezone'               => ((is_array($config) === true) ? $config['timezone'] : null),
        'homedir'                => $homedir,
        'dbconnection'           => $dbconnection,
        'historydb'              => $dbhHistory,
        'current_package'        => $current_package,
        'MR'                     => $mr,
        'registration_code'      => $puid,
        'remote_config'          => $remote_config,
        'propagate_updates'      => $is_metaconsole,
        'proxy'                  => $proxy,
        'allowOfflinePatches'    => $allowOfflinePatches,
        'set_maintenance_mode'   => function () {
            if (function_exists('config_update_value') === true) {
                config_update_value('maintenance_mode', 1);
            }
        },
        'clear_maintenance_mode' => function () {
            if (function_exists('config_update_value') === true) {
                config_update_value('maintenance_mode', 0);
            }
        },
    ],
    $mode
);

$ui->run();
