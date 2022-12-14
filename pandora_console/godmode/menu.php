<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
require_once 'include/config.php';

check_login();

enterprise_include('godmode/menu.php');
require_once 'include/functions_menu.php';

$menu_godmode = [];
$menu_godmode['class'] = 'godmode';

if (check_acl($config['id_user'], 0, 'AR')
    || check_acl($config['id_user'], 0, 'AW')
    || check_acl($config['id_user'], 0, 'RR')
    || check_acl($config['id_user'], 0, 'RW')
    || check_acl($config['id_user'], 0, 'PM')
) {
    $sub = [];
    $sub['godmode/servers/discovery&wiz=main']['text'] = __('Start');
    $sub['godmode/servers/discovery&wiz=main']['id'] = 'Discovery';
    $sub['godmode/servers/discovery&wiz=tasklist']['text'] = __('Task list');
    $sub['godmode/servers/discovery&wiz=tasklist']['id'] = 'tasklist';

    if (check_acl($config['id_user'], 0, 'AW')
        || check_acl($config['id_user'], 0, 'PM')
    ) {
        if (check_acl($config['id_user'], 0, 'AW')) {
            $sub2 = [];
            $sub2['godmode/servers/discovery&wiz=hd&mode=netscan']['text'] = __('Network scan');
            enterprise_hook('hostdevices_submenu');
            $sub2['godmode/servers/discovery&wiz=hd&mode=customnetscan']['text'] = __('Custom network scan');
        }

        if (check_acl($config['id_user'], 0, 'PM')) {
            $sub2['godmode/servers/discovery&wiz=hd&mode=managenetscanscripts']['text'] = __('Manage scan scripts');
        }

        $sub['godmode/servers/discovery&wiz=hd']['text'] = __('Host & devices');
        $sub['godmode/servers/discovery&wiz=hd']['id'] = 'hd';
        $sub['godmode/servers/discovery&wiz=hd']['sub2'] = $sub2;
    }

    if (check_acl($config['id_user'], 0, 'AW')) {
        enterprise_hook('applications_menu');
        enterprise_hook('cloud_menu');
        enterprise_hook('console_task_menu');
    }

    // Add to menu.
    $menu_godmode['discovery']['text'] = __('Discovery');
    $menu_godmode['discovery']['sec2'] = '';
    $menu_godmode['discovery']['id'] = 'god-discovery';
    $menu_godmode['discovery']['sub'] = $sub;
}


$sub = [];
if (check_acl($config['id_user'], 0, 'AW') || check_acl($config['id_user'], 0, 'AD')) {
    $sub['godmode/agentes/modificar_agente']['text'] = __('Manage agents');
    $sub['godmode/agentes/modificar_agente']['id'] = 'Manage agents';
    $sub['godmode/agentes/modificar_agente']['subsecs'] = ['godmode/agentes/configurar_agente'];
}

if (check_acl($config['id_user'], 0, 'PM')) {
    $sub['godmode/agentes/fields_manager']['text'] = __('Custom fields');
    $sub['godmode/agentes/fields_manager']['id'] = 'Custom fields';

    $sub['godmode/modules/manage_nc_groups']['text'] = __('Component groups');
    $sub['godmode/modules/manage_nc_groups']['id'] = 'Component groups';
    // Category
    $sub['godmode/category/category']['text'] = __('Module categories');
    $sub['godmode/category/category']['id'] = 'Module categories';
    $sub['godmode/category/category']['subsecs'] = 'godmode/category/edit_category';

    $sub['godmode/modules/module_list']['text'] = __('Module types');
    $sub['godmode/modules/module_list']['id'] = 'Module types';

    $sub['godmode/groups/modu_group_list']['text'] = __('Module groups');
    $sub['godmode/groups/modu_group_list']['id'] = 'Module groups';
}

if (check_acl($config['id_user'], 0, 'AW')) {
    // Netflow
    if ($config['activate_netflow']) {
        $sub['godmode/netflow/nf_edit']['text'] = __('Netflow filters');
        $sub['godmode/netflow/nf_edit']['id'] = 'Netflow filters';
    }
}

if (!empty($sub)) {
    $menu_godmode['gagente']['text'] = __('Resources');
    $menu_godmode['gagente']['sec2'] = 'godmode/agentes/modificar_agente';
    $menu_godmode['gagente']['id'] = 'god-resources';
    $menu_godmode['gagente']['sub'] = $sub;
}

$sub = [];
if (check_acl($config['id_user'], 0, 'PM')) {
    $sub['godmode/groups/group_list']['text'] = __('Manage agents groups');
    $sub['godmode/groups/group_list']['id'] = 'Manage agents groups';
}

if (check_acl($config['id_user'], 0, 'PM')) {
    // Tag
    $sub['godmode/tag/tag']['text'] = __('Module tags');
    $sub['godmode/tag/tag']['id'] = 'Module tags';
    $sub['godmode/tag/tag']['subsecs'] = 'godmode/tag/edit_tag';

    enterprise_hook('enterprise_acl_submenu');
}

if (check_acl($config['id_user'], 0, 'UM')) {
    $sub['godmode/users/user_list']['text'] = __('Users management');
    $sub['godmode/users/user_list']['id'] = 'Users management';
}

if (check_acl($config['id_user'], 0, 'PM')) {
    $sub['godmode/users/profile_list']['text'] = __('Profile management');
    $sub['godmode/users/profile_list']['id'] = 'Profile management';
}

if (!empty($sub)) {
    $menu_godmode['gusuarios']['sub'] = $sub;
    $menu_godmode['gusuarios']['text'] = __('Profiles');
    $menu_godmode['gusuarios']['sec2'] = 'godmode/users/user_list';
    $menu_godmode['gusuarios']['id'] = 'god-users';
}

$sub = [];
if (check_acl($config['id_user'], 0, 'PM')) {
    // enterprise_hook('components_submenu');
    $sub['templates']['text'] = __('Templates');
    $sub['templates']['id'] = 'Templates';
    $sub['templates']['type'] = 'direct';
    $sub['templates']['subtype'] = 'nolink';
    $sub2 = [];
    $sub2['godmode/modules/manage_module_templates']['text'] = __('Module templates');
    $sub2['godmode/modules/manage_module_templates']['id'] = 'Module templates';
    $sub2['godmode/modules/private_enterprise_numbers']['text'] = __('Private Enterprise Numbers');
    $sub2['godmode/modules/private_enterprise_numbers']['id'] = 'Private Enterprise Numbers';
    $sub2['enterprise/godmode/modules/local_components']['text'] = __('Local components');
    $sub2['enterprise/godmode/modules/local_components']['id'] = 'Local components';
    $sub2['godmode/modules/manage_network_components']['text'] = __('Remote components');
    $sub2['godmode/modules/manage_network_components']['id'] = 'Network components';
    $sub['templates']['sub2'] = $sub2;
    /*
        $sub2['godmode/modules/manage_snmp_modules']['text'] = __('SNMP Modules');
        $sub2['godmode/modules/manage_snmp_modules']['id'] = 'SNMP Modules';
        $sub2['godmode/modules/manage_wmi_modules']['text'] = __('WMI Modules');
        $sub2['godmode/modules/manage_wmi_modules']['id'] = 'WMI Modules';
        $sub['godmode/modules/manage_block_templates']['text'] = __('Module blocks');
        $sub['godmode/modules/manage_block_templates']['id'] = 'Module blocks';
    */


    enterprise_hook('inventory_submenu');
    enterprise_hook('autoconfiguration_menu');
    enterprise_hook('agent_repository_menu');
}

if (check_acl($config['id_user'], 0, 'AW')) {
    enterprise_hook('policies_menu');
    enterprise_hook('agents_submenu');
}

if (check_acl($config['id_user'], 0, 'NW')) {
    enterprise_hook('agents_ncm_submenu');
}

if (check_acl($config['id_user'], 0, 'AW')) {
    $sub['gmassive']['text'] = __('Bulk operations');
    $sub['gmassive']['id'] = 'Bulk operations';
    $sub['gmassive']['type'] = 'direct';
    $sub['gmassive']['subtype'] = 'nolink';
    $sub2 = [];
    $sub2['godmode/massive/massive_operations&amp;tab=massive_agents']['text'] = __('Agents operations');
    $sub2['godmode/massive/massive_operations&amp;tab=massive_modules']['text'] = __('Modules operations');
    $sub2['godmode/massive/massive_operations&amp;tab=massive_plugins']['text'] = __('Plugins operations');
    if (check_acl($config['id_user'], 0, 'UM')) {
        $sub2['godmode/massive/massive_operations&amp;tab=massive_users']['text'] = __('Users operations');
    }

    $sub2['godmode/massive/massive_operations&amp;tab=massive_alerts']['text'] = __('Alerts operations');
    enterprise_hook('massivepolicies_submenu');
    enterprise_hook('massivesnmp_submenu');
    enterprise_hook('massivesatellite_submenu');
    enterprise_hook('massiveservices_submenu');

    $sub['gmassive']['sub2'] = $sub2;
}

if (check_acl($config['id_user'], 0, 'PM') || check_acl($config['id_user'], 0, 'UM')) {
    $sub['godmode/groups/group_list&tab=credbox']['text'] = __('Credential store');
    $sub['godmode/groups/group_list&tab=credbox']['id'] = 'credential store';
}

if (!empty($sub)) {
    $menu_godmode['gmodules']['text'] = __('Configuration');
    $menu_godmode['gmodules']['sec2'] = 'godmode/modules/manage_network_templates';
    $menu_godmode['gmodules']['id'] = 'god-configuration';
    $menu_godmode['gmodules']['sub'] = $sub;
}

if (check_acl($config['id_user'], 0, 'LW')
    || check_acl($config['id_user'], 0, 'LM')
    || check_acl($config['id_user'], 0, 'AD')
) {
    $menu_godmode['galertas']['text'] = __('Alerts');
    $menu_godmode['galertas']['sec2'] = 'godmode/alerts/alert_list';
    $menu_godmode['galertas']['id'] = 'god-alerts';

    $sub = [];
    $sub['godmode/alerts/alert_list']['text'] = __('List of Alerts');
    $sub['godmode/alerts/alert_list']['id'] = 'List of Alerts';
    $sub['godmode/alerts/alert_list']['pages'] = ['godmode/alerts/alert_view'];

    if (check_acl($config['id_user'], 0, 'LM')) {
        $sub['godmode/alerts/alert_templates']['text'] = __('Templates');
        $sub['godmode/alerts/alert_templates']['id'] = 'Templates';
        $sub['godmode/alerts/alert_templates']['pages'] = ['godmode/alerts/configure_alert_template'];

        $sub['godmode/alerts/alert_actions']['text'] = __('Actions');
        $sub['godmode/alerts/alert_actions']['id'] = 'Actions';
        $sub['godmode/alerts/alert_actions']['pages'] = ['godmode/alerts/configure_alert_action'];
        $sub['godmode/alerts/alert_commands']['text'] = __('Commands');
        $sub['godmode/alerts/alert_commands']['id'] = 'Commands';
        $sub['godmode/alerts/alert_commands']['pages'] = ['godmode/alerts/configure_alert_command'];
        $sub['godmode/alerts/alert_special_days']['text'] = __('Special days list');
        $sub['godmode/alerts/alert_special_days']['id'] = __('Special days list');
        $sub['godmode/alerts/alert_special_days']['pages'] = ['godmode/alerts/configure_alert_special_days'];

        enterprise_hook('eventalerts_submenu');
        $sub['godmode/snmpconsole/snmp_alert']['text'] = __('SNMP alerts');
        $sub['godmode/snmpconsole/snmp_alert']['id'] = 'SNMP alerts';
        enterprise_hook('alert_inventory_submenu');
    }

    $menu_godmode['galertas']['sub'] = $sub;
}

// Manage events
$sub = [];
if (check_acl($config['id_user'], 0, 'EW') || check_acl($config['id_user'], 0, 'EM')) {
    // Custom event fields
    $sub['godmode/events/events&section=filter']['text'] = __('Event filters');
    $sub['godmode/events/events&section=filter']['id'] = 'Event filters';
}

if (check_acl($config['id_user'], 0, 'PM')) {
    $sub['godmode/events/events&section=fields']['text'] = __('Custom events');
    $sub['godmode/events/events&section=fields']['id'] = 'Custom events';
    $sub['godmode/events/events&section=responses']['text'] = __('Event responses');
    $sub['godmode/events/events&section=responses']['id'] = 'Event responses';
}

if (!empty($sub)) {
    $menu_godmode['geventos']['text'] = __('Events');
    $menu_godmode['geventos']['sec2'] = 'godmode/events/events&section=filter';
    $menu_godmode['geventos']['id'] = 'god-events';
    $menu_godmode['geventos']['sub'] = $sub;
}


if (check_acl($config['id_user'], 0, 'AW') || check_acl($config['id_user'], 0, 'PM')) {
    // Servers
    $menu_godmode['gservers']['text'] = __('Servers');
    $menu_godmode['gservers']['sec2'] = 'godmode/servers/modificar_server';
    $menu_godmode['gservers']['id'] = 'god-servers';

    $sub = [];

    if (check_acl($config['id_user'], 0, 'AW')) {
        $sub['godmode/servers/modificar_server']['text'] = __('Manage servers');
        $sub['godmode/servers/modificar_server']['id'] = 'Manage servers';
    }

    // This subtabs are only for Pandora Admin.
    if (check_acl($config['id_user'], 0, 'PM')) {
        enterprise_hook('ha_cluster');

        $sub['godmode/servers/plugin']['text'] = __('Plugins');
        $sub['godmode/servers/plugin']['id'] = 'Plugins';

        $sub['godmode/servers/plugin_registration']['text'] = __('Register Plugin');
        $sub['godmode/servers/plugin_registration']['id'] = 'register_plugin';

        enterprise_hook('export_target_submenu');

        enterprise_hook('manage_satellite_submenu');
    }

    $menu_godmode['gservers']['sub'] = $sub;
}

if (check_acl($config['id_user'], 0, 'PM')) {
    // Setup
    $menu_godmode['gsetup']['text'] = __('Setup');
    $menu_godmode['gsetup']['sec2'] = 'general';
    $menu_godmode['gsetup']['id'] = 'god-setup';

    $sub = [];

    // Options Setup
    $sub['general']['text'] = __('Setup');
    $sub['general']['id'] = 'Setup';
    $sub['general']['type'] = 'direct';
    $sub['general']['subtype'] = 'nolink';
    $sub2 = [];

    $sub2['godmode/setup/setup&section=general']['text'] = __('General Setup');
    $sub2['godmode/setup/setup&section=general']['id'] = 'General Setup';
    $sub2['godmode/setup/setup&section=general']['refr'] = 0;

    enterprise_hook('password_submenu');
    enterprise_hook('enterprise_submenu');
    enterprise_hook('historydb_submenu');
    enterprise_hook('log_collector_submenu');

    $sub2['godmode/setup/setup&section=auth']['text'] = __('Authentication');
    $sub2['godmode/setup/setup&section=auth']['refr'] = 0;

    $sub2['godmode/setup/setup&section=perf']['text'] = __('Performance');
    $sub2['godmode/setup/setup&section=perf']['refr'] = 0;

    $sub2['godmode/setup/setup&section=vis']['text'] = __('Visual styles');
    $sub2['godmode/setup/setup&section=vis']['refr'] = 0;

    if (check_acl($config['id_user'], 0, 'AW')) {
        if ($config['activate_netflow']) {
            $sub2['godmode/setup/setup&section=net']['text'] = __('Netflow');
            $sub2['godmode/setup/setup&section=net']['refr'] = 0;
        }
    }

    $sub2['godmode/setup/setup&section=ehorus']['text'] = __('eHorus');
    $sub2['godmode/setup/setup&section=ehorus']['refr'] = 0;

    $sub2['godmode/setup/setup&section=integria']['text'] = __('Integria IMS');
    $sub2['godmode/setup/setup&section=integria']['refr'] = 0;

    enterprise_hook('module_library_submenu');

    $sub2['godmode/setup/setup&section=notifications']['text'] = __('Notifications');
    $sub2['godmode/setup/setup&section=notifications']['refr'] = 0;

    $sub2['godmode/setup/setup&section=websocket_engine']['text'] = __('Websocket Engine');
    $sub2['godmode/setup/setup&section=websocket_engine']['refr'] = 0;

    $sub2['godmode/setup/setup&section=external_tools']['text'] = __('External Tools');
    $sub2['godmode/setup/setup&section=external_tools']['refr'] = 0;

    if ($config['activate_gis']) {
        $sub2['godmode/setup/setup&section=gis']['text'] = __('Map conections GIS');
    }

    $sub['general']['sub2'] = $sub2;
    $sub['godmode/setup/os']['text'] = __('Edit OS');
    $sub['godmode/setup/os']['id'] = 'Edit OS';
    $sub['godmode/setup/license']['text'] = __('License');
    $sub['godmode/setup/license']['id'] = 'License';

    enterprise_hook('skins_submenu');

    $menu_godmode['gsetup']['sub'] = $sub;
}

if (check_acl($config['id_user'], 0, 'PM') || check_acl($config['id_user'], 0, 'DM')) {
    $menu_godmode['gextensions']['text'] = __('Admin tools');
    $menu_godmode['gextensions']['sec2'] = 'godmode/extensions';
    $menu_godmode['gextensions']['id'] = 'god-extensions';

    $sub = [];

    if (check_acl($config['id_user'], 0, 'PM')) {
        // Audit //meter en extensiones.
        $sub['godmode/audit_log']['text'] = __('System audit log');
        $sub['godmode/audit_log']['id'] = 'System audit log';
        $sub['godmode/setup/links']['text'] = __('Links');
        $sub['godmode/setup/links']['id'] = 'Links';
        $sub['tools/diagnostics']['text'] = __('Diagnostic info');
        $sub['tools/diagnostics']['id'] = 'Diagnostic info';
        enterprise_hook('omnishell');
        enterprise_hook('ipam_submenu');

        $sub['godmode/setup/news']['text'] = __('Site news');
        $sub['godmode/setup/news']['id'] = 'Site news';
        $sub['godmode/setup/file_manager']['text'] = __('File manager');
        $sub['godmode/setup/file_manager']['id'] = 'File manager';

        if (is_user_admin($config['id_user'])) {
            $sub['extensions/db_status']['text'] = __('DB Schema Check');
            $sub['extensions/db_status']['id'] = 'DB Schema Check';
            $sub['extensions/db_status']['sec'] = 'gbman';
            $sub['extensions/dbmanager']['text'] = __('DB Interface');
            $sub['extensions/dbmanager']['id'] = 'DB Interface';
            $sub['extensions/dbmanager']['sec'] = 'gbman';
            enterprise_hook('dbBackupManager');
            enterprise_hook('elasticsearch_interface_menu');
        }
    }

    $menu_godmode['gextensions']['sub'] = $sub;
}

if (is_array($config['extensions'])) {
    $sub = [];
    $sub2 = [];

    foreach ($config['extensions'] as $extension) {
        // If no godmode_menu is a operation extension
        if ($extension['godmode_menu'] == '') {
            continue;
        }

        if ($extension['godmode_menu']['name'] == 'System Info') {
            continue;
        }



        if ($extension['godmode_menu']['name'] != __('DB Schema check') && $extension['godmode_menu']['name'] != __('DB interface')) {
            $extmenu = $extension['godmode_menu'];
        }

        // Check the ACL for this user
        if (! check_acl($config['id_user'], 0, $extmenu['acl'])) {
            continue;
        }

        // Check if was displayed inside other menu
        if ($extension['godmode_menu']['fatherId'] == '') {
            $sub2[$extmenu['sec2']]['text'] = __($extmenu['name']);
            $sub2[$extmenu['sec2']]['id'] = $extmenu['name'];
            $sub2[$extmenu['sec2']]['refr'] = 0;
        } else {
            if (is_array($extmenu) && array_key_exists('fatherId', $extmenu)) {
                if (strlen($extmenu['fatherId']) > 0) {
                    if (array_key_exists('subfatherId', $extmenu)) {
                        if (strlen($extmenu['subfatherId']) > 0) {
                            $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['subfatherId']]['sub2'][$extmenu['sec2']]['text'] = __($extmenu['name']);
                            $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['subfatherId']]['sub2'][$extmenu['sec2']]['id'] = $extmenu['name'];
                            $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['subfatherId']]['sub2'][$extmenu['sec2']]['refr'] = 0;
                            $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['subfatherId']]['sub2'][$extmenu['sec2']]['icon'] = $extmenu['icon'];
                            $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['subfatherId']]['sub2'][$extmenu['sec2']]['sec'] = 'extensions';
                            $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['subfatherId']]['sub2'][$extmenu['sec2']]['extension'] = true;
                            $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['subfatherId']]['sub2'][$extmenu['sec2']]['enterprise'] = $extension['enterprise'];
                            $menu_godmode[$extmenu['fatherId']]['hasExtensions'] = true;
                        } else {
                            $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['text'] = __($extmenu['name']);
                            $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['id'] = $extmenu['name'];
                            $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['refr'] = 0;
                            $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['icon'] = $extmenu['icon'];
                            if ($extmenu['name'] == 'Cron jobs') {
                                $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['sec'] = $extmenu['fatherId'];
                            } else {
                                $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['sec'] = $extmenu['fatherId'];
                            }

                            $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['extension'] = true;
                            $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['enterprise'] = $extension['enterprise'];
                            $menu_godmode[$extmenu['fatherId']]['hasExtensions'] = true;
                        }
                    } else {
                        $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['text'] = __($extmenu['name']);
                        $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['id'] = $extmenu['name'];
                        $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['refr'] = 0;
                        $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['icon'] = $extmenu['icon'];
                        $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['sec'] = 'gextensions';
                        $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['extension'] = true;
                        $menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]['enterprise'] = $extension['enterprise'];
                        $menu_godmode[$extmenu['fatherId']]['hasExtensions'] = true;
                    }
                }
            }
        }
    }

    // Complete the submenu
    $extension_view = [];
    $extension_view['godmode/extensions']['id'] = 'Extension manager view';
    $extension_view['godmode/extensions']['text'] = __('Extension manager view');
    $extension_submenu = array_merge($extension_view, $sub2);

    $sub['godmode/extensions']['sub2'] = $extension_submenu;
    $sub['godmode/extensions']['text'] = __('Extension manager');
    $sub['godmode/extensions']['id'] = 'Extension manager';
    $sub['godmode/extensions']['type'] = 'direct';
    $sub['godmode/extensions']['subtype'] = 'nolink';

    if (is_array($menu_godmode['gextensions']['sub'])) {
        $submenu = array_merge($menu_godmode['gextensions']['sub'], $sub);
        if ($menu_godmode['gextensions']['sub'] != null) {
            $menu_godmode['gextensions']['sub'] = $submenu;
        }
    }
}

$menu_godmode['links']['text'] = __('Links');
$menu_godmode['links']['sec2'] = '';
$menu_godmode['links']['id'] = 'god-links';

$sub = [];
$rows = db_get_all_rows_in_table('tlink', 'name');
foreach ($rows as $row) {
    // Audit //meter en extensiones
    $sub[$row['link']]['text'] = $row['name'];
    $sub[$row['link']]['id'] = $row['name'];
    $sub[$row['link']]['type'] = 'direct';
    $sub[$row['link']]['subtype'] = 'new_blank';
}

$menu_godmode['links']['sub'] = $sub;

// Update Manager
if (check_acl($config['id_user'], 0, 'PM') && $config['enable_update_manager']) {
    $menu_godmode['messages']['text'] = __('Update manager');
    $menu_godmode['messages']['id'] = 'god-um_messages';
    $menu_godmode['messages']['sec2'] = '';

    $sub = [];
    $sub['godmode/update_manager/update_manager&tab=offline']['text'] = __('Update Manager offline');
    $sub['godmode/update_manager/update_manager&tab=offline']['id'] = 'Offline';

    $sub['godmode/update_manager/update_manager&tab=online']['text'] = __('Update Manager online');
    $sub['godmode/update_manager/update_manager&tab=online']['id'] = 'Online';
    $sub['godmode/update_manager/update_manager&tab=setup']['text'] = __('Update Manager options');
    $sub['godmode/update_manager/update_manager&tab=setup']['id'] = 'Options';

    $menu_godmode['messages']['sub'] = $sub;
}

// Module library.
if (check_acl($config['id_user'], 0, 'AR')) {
    $menu_godmode['gmodule_library']['text'] = __('Module library');
    $menu_godmode['gmodule_library']['id'] = 'god-module_library';

    $sub = [];
    $sub['godmode/module_library/module_library_view']['text'] = __('View');
    $sub['godmode/module_library/module_library_view']['id'] = 'View';

    $sub['godmode/module_library/module_library_view&tab=categories']['text'] = __('Categories');
    $sub['godmode/module_library/module_library_view&tab=categories']['id'] = 'categories';

    $menu_godmode['gmodule_library']['sub'] = $sub;
}

if (!$config['pure']) {
    menu_print_menu($menu_godmode);
}
