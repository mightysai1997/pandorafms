<?php
/**
 * Alert actions
 *
 * @category   Alerts
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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

// Load global vars.
global $config;

require_once $config['homedir'].'/include/functions_alerts.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_groups.php';
enterprise_include_once('meta/include/functions_alerts_meta.php');

check_login();

enterprise_hook('open_meta_frame');

if (! check_acl($config['id_user'], 0, 'LM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access Alert actions'
    );
    include 'general/noaccess.php';
    exit;
}

$update_action = (bool) get_parameter('update_action');
$create_action = (bool) get_parameter('create_action');
$delete_action = (bool) get_parameter('delete_action');
$copy_action = (bool) get_parameter('copy_action');
$pure = get_parameter('pure', 0);

if (defined('METACONSOLE')) {
    $sec = 'advanced';
} else {
    $sec = 'galertas';
}

// Header.
if (defined('METACONSOLE')) {
    alerts_meta_print_header();
} else {
    ui_print_page_header(
        __('Alerts').' &raquo; '.__('Alert actions'),
        'images/gm_alerts.png',
        false,
        '',
        true
    );
}

if ($copy_action) {
    $id = get_parameter('id');

    $al_action = alerts_get_alert_action($id);

    if (!check_acl_restricted_all($config['id_user'], $al_action['id_group'], 'LM')) {
        db_pandora_audit(
            'ACL Violation',
            'Trying to access Alert Management'
        );
        include 'general/noaccess.php';
        exit;
    }

    if ($al_action !== false) {
        // If user tries to copy an action with group=ALL.
        if ($al_action['id_group'] == 0) {
            // Then must have "PM" access privileges.
            if (! check_acl($config['id_user'], 0, 'PM')) {
                db_pandora_audit(
                    'ACL Violation',
                    'Trying to access Alert Management'
                );
                include 'general/noaccess.php';
                exit;
            }
        } else {
            $own_info = get_user_info($config['id_user']);
            if ($own_info['is_admin'] || check_acl($config['id_user'], 0, 'PM')) {
                $own_groups = array_keys(
                    users_get_groups($config['id_user'], 'LM')
                );
            } else {
                $own_groups = array_keys(
                    users_get_groups($config['id_user'], 'LM', false)
                );
            }

            $is_in_group = in_array($al_action['id_group'], $own_groups);
            // Then action group have to be in his own groups.
            if (!$is_in_group) {
                db_pandora_audit(
                    'ACL Violation',
                    'Trying to access Alert Management'
                );
                include 'general/noaccess.php';
                exit;
            }
        }
    }

    $result = alerts_clone_alert_action($id);

    if ($result) {
        db_pandora_audit(
            'Command management',
            'Duplicate alert action '.$id.' clone to '.$result
        );
    } else {
        db_pandora_audit(
            'Command management',
            'Fail try to duplicate alert action '.$id
        );
    }

    ui_print_result_message(
        $result,
        __('Successfully copied'),
        __('Could not be copied')
    );
}

if ($update_action || $create_action) {
    alerts_ui_update_or_create_actions($update_action);
}

if ($delete_action) {
    $id = get_parameter('id');

    $al_action = alerts_get_alert_action($id);

    if (!check_acl_restricted_all($config['id_user'], $al_action['id_group'], 'LM')) {
        db_pandora_audit(
            'ACL Violation',
            'Trying to access Alert Management'
        );
        include 'general/noaccess.php';
        exit;
    }

    if ($al_action !== false) {
        // If user tries to delete an action with group=ALL.
        if ($al_action['id_group'] == 0) {
            // Then must have "PM" access privileges.
            if (! check_acl($config['id_user'], 0, 'PM')) {
                db_pandora_audit(
                    'ACL Violation',
                    'Trying to access Alert Management'
                );
                include 'general/noaccess.php';
                exit;
            }

            // If user tries to delete an action of others groups.
        } else {
            $own_info = get_user_info($config['id_user']);
            if ($own_info['is_admin'] || check_acl($config['id_user'], 0, 'PM')) {
                $own_groups = array_keys(
                    users_get_groups($config['id_user'], 'LM')
                );
            } else {
                $own_groups = array_keys(
                    users_get_groups($config['id_user'], 'LM', false)
                );
            }

            $is_in_group = in_array($al_action['id_group'], $own_groups);
            // Then action group have to be in his own groups.
            if (!$is_in_group) {
                db_pandora_audit(
                    'ACL Violation',
                    'Trying to access Alert Management'
                );
                include 'general/noaccess.php';
                exit;
            }
        }
    }


    $result = alerts_delete_alert_action($id);

    if ($result) {
        db_pandora_audit(
            'Command management',
            'Delete alert action #'.$id
        );
    } else {
        db_pandora_audit(
            'Command management',
            'Fail try to delete alert action #'.$id
        );
    }

    ui_print_result_message(
        $result,
        __('Successfully deleted'),
        __('Could not be deleted')
    );
}

if (is_central_policies_on_node() === true) {
    ui_print_warning_message(
        __('This node is configured with centralized mode. All alerts templates information is read only. Go to metaconsole to manage it.')
    );
}

$search_string = (string) get_parameter('search_string', '');
$group = (int) get_parameter('group', 0);
$group_search = (int) get_parameter('group_search', 0);
$id_command_search = (int) get_parameter('id_command_search', 0);
$url = 'index.php?sec='.$sec.'&sec2=godmode/alerts/alert_actions';

// Filter table.
$table_filter = new stdClass();
$table_filter->width = '100%';
$table_filter->class = 'databox filters';
$table_filter->style = [];
$table_filter->style[0] = 'font-weight: bold';
$table_filter->style[2] = 'font-weight: bold';
$table_filter->style[4] = 'font-weight: bold';
$table_filter->data = [];

$table_filter->data[0][0] = __('Search');
$table_filter->data[0][1] = html_print_input_text(
    'search_string',
    $search_string,
    '',
    25,
    255,
    true
);

$return_all_group = false;

if (users_can_manage_group_all('LM') === true) {
    $return_all_group = true;
}

$table_filter->data[0][2] = __('Group');
$table_filter->data[0][3] = html_print_select_groups(
    $config['id_user'],
    'LM',
    $return_all_group,
    'group_search',
    $group_search,
    '',
    '',
    0,
    true
);
$table_filter->data[0][4] = __('Command');
$commands_sql = db_get_all_rows_filter(
    'talert_commands',
    ['id_group' => array_keys(users_get_groups(false, 'LW'))],
    [
        'id',
        'name',
    ],
    'AND',
    false,
    true
);

$commands = db_get_all_rows_sql($commands_sql);
$table_filter->data[0][5] = html_print_select(
    index_array($commands, 'id', 'name'),
    'id_command_search',
    $id_command_search,
    '',
    __('None'),
    0,
    true,
    false,
    true,
    '',
    false,
    'width:150px'
);
$table_filter->data[0][6] = '<div class="action-buttons">';
$table_filter->data[0][6] .= html_print_submit_button(
    __('Search'),
    '',
    false,
    'class="sub search"',
    true
);
$table_filter->data[0][5] .= '</div>';


$show_table_filter = '<form method="post" action="'.$url.'">';
$show_table_filter .= html_print_table($table_filter, true);
$show_table_filter .= '</form>';
if (is_metaconsole()) {
    ui_toggle($show_table_filter, __('Show Options'));
} else {
    echo $show_table_filter;
}


$table = new stdClass();
$table->width = '100%';
$table->class = 'info_table';
$table->data = [];
$table->head = [];
$table->head[0] = __('Name');
$table->head[1] = __('Command');
$table->head[2] = __('Group');
if (is_central_policies_on_node() === false) {
    $table->head[3] = __('Copy');
    $table->head[4] = __('Delete');
}

$table->style = [];
$table->style[0] = 'font-weight: bold';
$table->size = [];
$table->size[3] = '40px';
$table->size[4] = '40px';
$table->align = [];
$table->align[1] = 'left';
$table->align[2] = 'left';
$table->align[3] = 'left';
$table->align[4] = 'left';

$filter = [];
if (!is_user_admin($config['id_user']) && $group === 0) {
    $filter['talert_actions.id_group'] = array_keys(
        users_get_groups(false, 'LM')
    );
}

if ($group_search !== 0) {
    $filter['talert_actions.id_group'] = $group_search;
}

if ($search_string !== '') {
    $filter['talert_actions.name'] = '%'.$search_string.'%';
}

if ($id_command_search !== 0) {
    $filter['talert_commands.id'] = $id_command_search;
}

$actions = db_get_all_rows_filter(
    'talert_actions INNER JOIN talert_commands ON talert_actions.id_alert_command = talert_commands.id',
    $filter,
    'talert_actions.* , talert_commands.id_group AS command_group, talert_commands.name AS command_name'
);

if ($actions === false) {
    $actions = [];
}

// Pagination.
$total_actions = count($actions);
$offset = (int) get_parameter('offset');
$limit = (int) $config['block_size'];
$actions = array_slice($actions, $offset, $limit);

$rowPair = true;
$iterator = 0;
foreach ($actions as $action) {
    if ($rowPair) {
        $table->rowclass[$iterator] = 'rowPair';
    } else {
        $table->rowclass[$iterator] = 'rowOdd';
    }

    $rowPair = !$rowPair;
    $iterator++;

    $data = [];

    if (check_acl_restricted_all($config['id_user'], $action['id_group'], 'LM')) {
        $data[0] = '<a href="index.php?sec='.$sec.'&sec2=godmode/alerts/configure_alert_action&id='.$action['id'].'&pure='.$pure.'">'.$action['name'].'</a>';
    } else {
        $data[0] = $action['name'];
    }

    $data[1] = $action['command_name'];
    $data[2] = ui_print_group_icon($action['id_group'], true).'&nbsp;';
    if (!alerts_validate_command_to_action($action['id_group'], $action['command_group'])) {
        $data[2] .= html_print_image(
            'images/error.png',
            true,
            // FIXME: Translation.
            [
                'title' => __('The action and the command associated with it do not have the same group. Please contact an administrator to fix it.'),
            ]
        );
    }

    $data[3] = '';
    $data[4] = '';

    if (is_central_policies_on_node() === false
        && check_acl_restricted_all($config['id_user'], $action['id_group'], 'LM')
    ) {
        $table->cellclass[] = [
            3 => 'action_buttons',
            4 => 'action_buttons',
        ];

        $id_action = $action['id'];
        $text_confirm = __('Are you sure?');

        $data[3] = '<a href="index.php?sec='.$sec.'&sec2=godmode/alerts/alert_actions" 
        onClick="copy_action('.$id_action.',\''.$text_confirm.'\');">'.html_print_image('images/copy.png', true).'</a>';
        $data[4] = '<a href="index.php?sec='.$sec.'&sec2=godmode/alerts/alert_actions"
        onClick="delete_action('.$id_action.',\''.$text_confirm.'\');">'.html_print_image('images/cross.png', true).'</a>';
    }

    array_push($table->data, $data);
}

ui_pagination($total_actions, $url);
if (isset($data)) {
    html_print_table($table);
    ui_pagination($total_actions, $url, 0, 0, false, 'offset', true, 'pagination-bottom');
} else {
    ui_print_info_message(['no_close' => true, 'message' => __('No alert actions configured') ]);
}

if (is_central_policies_on_node() === false) {
    echo '<div class="action-buttons" style="width: '.$table->width.'">';
    echo '<form method="post" action="index.php?sec='.$sec.'&sec2=godmode/alerts/configure_alert_action&pure='.$pure.'">';
    html_print_submit_button(__('Create'), 'create', false, 'class="sub next"');
    html_print_input_hidden('create_alert', 1);
    echo '</form>';
    echo '</div>';
}

enterprise_hook('close_meta_frame');
?>

<script type="text/javascript">

function copy_action(id_action, text_confirm) {
    if (!confirm(text_confirm)) {
        return false;
    } else {
        jQuery.post ("ajax.php",
            {
            "page" : "godmode/alerts/alert_actions",
            "copy_action" : 1,
            "id" : id_action
            },
            function (data, status) {
                // No data.
            },
            "json"
        );
    }
}

function delete_action(id_action, text_confirm) {
    if (!confirm(text_confirm)) {
        return false;
    } else {
        jQuery.post ("ajax.php",
            {
            "page" : "godmode/alerts/alert_actions",
            "delete_action" : 1,
            "id" : id_action
            },
            function (data, status) {
                // No data.
            },
            "json"
        );
    }
}

</script>

