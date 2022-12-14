<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
enterprise_include_once('include/functions_policies.php');
enterprise_include_once('godmode/agentes/module_manager_editor_prediction.php');
require_once 'include/functions_agents.php';
ui_require_jquery_file('validate');

$disabledBecauseInPolicy = false;
$disabledTextBecauseInPolicy = '';
$page = get_parameter('page', '');
$id_agente = get_parameter('id_agente', '');
$agent_name = get_parameter('agent_name', agents_get_alias($id_agente));
$id_agente_modulo = get_parameter('id_agent_module', 0);
$custom_integer_2 = get_parameter('custom_integer_2', 0);
$sql = 'SELECT *
	FROM tagente_modulo
	WHERE id_agente_modulo = '.$id_agente_modulo;
$row = db_get_row_sql($sql);
$is_service = false;
$is_synthetic = false;
$is_synthetic_avg = false;
$ops = false;
if ($row !== false && is_array($row)) {
    $prediction_module = $row['prediction_module'];
    $custom_integer_2 = $row['custom_integer_2'];
    // Services are an Enterprise feature.
    $custom_integer_1 = $row['custom_integer_1'];

    switch ((int) $prediction_module) {
        case MODULE_PREDICTION_SERVICE:
            $selected = 'service_selected';
            $custom_integer_2 = 0;
        break;

        case MODULE_PREDICTION_SYNTHETIC:
            $ops_json = enterprise_hook(
                'modules_get_synthetic_operations',
                [$id_agente_modulo]
            );


            $ops = json_decode($ops_json, true);



            // Erase the key of array serialize as <num>**
            $chunks = explode('**', reset(array_keys($ops)));

            $first_op = explode('_', $chunks[1]);



            if (isset($first_op[1]) && $first_op[1] == 'avg') {
                $selected = 'synthetic_avg_selected';
            } else {
                $selected = 'synthetic_selected';
            }

            $custom_integer_1 = 0;
            $custom_integer_2 = 0;
        break;

        case MODULE_PREDICTION_TRENDING:
            $selected = 'trending_selected';
            $prediction_module = $custom_integer_1;
        break;

        case MODULE_PREDICTION_PLANNING:
            $selected = 'capacity_planning';
            $prediction_module = $custom_integer_1;
            $estimation_interval = $custom_string_1;
            $estimation_type = $custom_string_2;
        break;

        default:
            $prediction_module = $custom_integer_1;
        break;
    }
} else {
    $selected = 'capacity_planning';
    $custom_integer_1 = 0;
}

if (strstr($page, 'policy_modules') === false) {
    if ($config['enterprise_installed']) {
        $disabledBecauseInPolicy = policies_is_module_in_policy($id_agent_module) && policies_is_module_linked($id_agent_module);
    } else {
        $disabledBecauseInPolicy = false;
    }

    if ($disabledBecauseInPolicy) {
        $disabledTextBecauseInPolicy = 'disabled = "disabled"';
    }
}

$extra_title = __('Prediction server module');

$data = [];
$data[0] = __('Source module');
$data[0] .= ui_print_help_icon('prediction_source_module', true);
$data[1] = '';
// Services and Synthetic are an Enterprise feature.
$module_service_synthetic_selector = enterprise_hook('get_module_service_synthetic_selector', [$selected]);
if ($module_service_synthetic_selector !== ENTERPRISE_NOT_HOOK) {
    $data[1] = $module_service_synthetic_selector;

    $table_simple->colspan['module_service_synthetic_selector'][1] = 3;
    push_table_simple($data, 'module_service_synthetic_selector');

    $data = [];
    $data[0] = '';
}

$data[1] = '<div id="module_data" class="w50p float-left top-1em">';
$data[1] .= html_print_label(__('Agent'), 'agent_name', true).'<br/>';

// Get module and agent of the target prediction module
if (empty($prediction_module) === false) {
    $id_agente_clean = modules_get_agentmodule_agent($prediction_module);
    $prediction_module_agent = modules_get_agentmodule_agent_name($prediction_module);
    $agent_name_clean = $prediction_module_agent;
    $agent_alias = agents_get_alias($id_agente_clean);
} else {
    $id_agente_clean = 0;
    $agent_name_clean = '';
    $agent_alias = '';
}


$params = [];
$params['return'] = true;
$params['show_helptip'] = true;
$params['input_name'] = 'agent_name';
$params['value'] = $agent_alias;
$params['javascript_is_function_select'] = true;
$params['selectbox_id'] = 'prediction_module';
$params['none_module_text'] = __('Select Module');
$params['use_hidden_input_idagent'] = true;
$params['hidden_input_idagent_id'] = 'hidden-id_agente_module_prediction';
$data[1] .= ui_print_agent_autocomplete_input($params);

$data[1] .= '<br />';
$data[1] .= html_print_label(__('Module'), 'prediction_module', true).'<br />';
if ($id_agente) {
    $sql = 'SELECT id_agente_modulo, nombre
		FROM tagente_modulo
		WHERE delete_pending = 0
			AND history_data = 1
			AND id_agente =  '.$id_agente_clean.'
			AND id_agente_modulo  <> '.$id_agente_modulo;

    $data[1] .= html_print_input(
        [
            'type'          => 'select_from_sql',
            'sql'           => $sql,
            'name'          => 'prediction_module',
            'selected'      => $prediction_module,
            'nothing'       => __('Select Module'),
            'nothing_value' => 0,
            'return'        => true,
        ]
    );
} else {
    $data[1] .= '<select id="prediction_module" name="custom_integer_1" disabled="disabled"><option value="0">Select an Agent first</option></select>';
}

$data[1] .= '<br />';
$data[1] .= html_print_label(__('Period'), 'custom_integer_2', true).'<br/>';

$periods[0] = __('Weekly');
$periods[1] = __('Monthly');
$periods[2] = __('Daily');
$data[1] .= html_print_select($periods, 'custom_integer_2', $custom_integer_2, '', '', 0, true);

$data[1] .= html_print_input_hidden('id_agente_module_prediction', $id_agente, true);
$data[1] .= '</div>';

$table_simple->colspan['prediction_module'][1] = 3;
push_table_simple($data, 'prediction_module');

$data = [];
$data[0] = '';

$data[1] .= html_print_label(__('Calculation type'), 'estimation_type', true).'<br/>';
$data[1] .= html_print_input(
    [
        'type'     => 'select',
        'return'   => 'true',
        'name'     => 'estimation_type',
        'class'    => 'w250px',
        'fields'   => [
            'estimation_absolute'    => __('Estimated absolute value'),
            'estimation_calculation' => __('Calculation of days to reach limit'),
        ],
        'selected' => $estimation_type,
    ],
    'div',
    false
);

$data[1] .= '<div id="estimation_interval_row">';
$data[1] .= html_print_label(__('Future estimation'), 'estimation_interval', true).'<br/>';
$data[1] .= html_print_input(
    [
        'type'   => 'interval',
        'return' => 'true',
        'name'   => 'estimation_interval',
        'value'  => $estimation_interval,
    ],
    'div',
    false
);
$data[1] .= '</div>';


$data[1] .= '<div id="estimation_days_row">';
$data[1] .= html_print_label(__('Limit value'), 'estimation_days', true).'<br/>';
$data[1] .= html_print_input(
    [
        'type'   => 'number',
        'return' => 'true',
        'id'     => 'estimation_days',
        'name'   => 'estimation_days',
        'value'  => $estimation_interval,
    ]
);
$data[1] .= '</div>';

push_table_simple($data, 'capacity_planning');

// Services are an Enterprise feature.
$selector_form = enterprise_hook('get_selector_form', [$custom_integer_1]);
if ($selector_form !== ENTERPRISE_NOT_HOOK) {
    $data = [];
    $data[0] = '';
    $data[1] = $selector_form;

    $table_simple->colspan['service_module'][1] = 3;
    push_table_simple($data, 'service_module');
}

// Synthetic modules are an Enterprise feature.
$synthetic_module_form = enterprise_hook('get_synthetic_module_form');
if ($synthetic_module_form !== ENTERPRISE_NOT_HOOK) {
    $data = [];
    $data[0] = '';
    $data[1] = $synthetic_module_form;

    push_table_simple($data, 'synthetic_module');
}

$trending_module_form = enterprise_hook('get_trending_module_form', [$custom_string_1]);
if ($trending_module_form !== ENTERPRISE_NOT_HOOK) {
    $data = [];
    $data[0] = '';
    $data[1] .= $trending_module_form;

    push_table_simple($data, 'trending_module');
}



// Netflow modules are an Enterprise feature.
$netflow_module_form = enterprise_hook('get_netflow_module_form', [$custom_integer_1]);
if ($netflow_module_form !== ENTERPRISE_NOT_HOOK) {
    $data = [];
    $data[0] = '';
    $data[1] = $netflow_module_form;

    $table_simple->colspan['netflow_module_form'][1] = 3;
    push_table_simple($data, 'netflow_module');
}




// Removed common useless parameter
unset($table_advanced->data[3]);
?>
<script type="text/javascript">
    $(document).ready(function() {
        <?php
        enterprise_hook(
            'setup_services_synth',
            [
                $selected,
                $is_netflow,
                $ops,
            ]
        );
        ?>
    });
</script>