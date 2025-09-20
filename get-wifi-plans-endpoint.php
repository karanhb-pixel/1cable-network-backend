<?php
// Register a single REST route for wifi-plans that handles multiple methods
    register_rest_route('wp/v2', 'wifi-plans', [
        [
            'methods' => 'GET',
            'callback' => 'get_wifi_plans',
            'permission_callback' => '__return_true' // Publicly accessible to get plans
        ],
        [
            'methods' => 'POST',
            'callback' => 'handle_add_wifi_plan',
            'permission_callback' => 'check_admin_permission', // Only for administrators
            'args' => [
                'speed' => [
                    'description' => 'Speed of the wifi plan.',
                    'type' => 'number',
                    'required' => true,
                    'sanitize_callback' => 'absint',
                ],
                'color' => [
                    'description' => 'Color associated with the plan.',
                    'type' => 'string',
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                '6_month' => [
                    'description' => 'Price for the 6-month plan.',
                    'type' => 'string',
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                '12_month' => [
                    'description' => 'Price for the 12-month plan.',
                    'type' => 'string',
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ],
        [
            'methods' => 'PUT',
            'callback' => 'handle_edit_wifi_plan',
            'permission_callback' => 'check_admin_permission', // Only for administrators
            'args' => [
                'plan_id' => [
                    'description' => 'ID of the plan to update.',
                    'type' => 'number',
                    'required' => true,
                    'sanitize_callback' => 'absint',
                ],
                'speed' => [
                    'description' => 'Speed of the wifi plan.',
                    'type' => 'number',
                    'required' => false,
                    'sanitize_callback' => 'absint',
                ],
                'color' => [
                    'description' => 'Color associated with the plan.',
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                '6_month' => [
                    'description' => 'Price for the 6-month plan.',
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                '12_month' => [
                    'description' => 'Price for the 12-month plan.',
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]
    ]);



// Callback function
function get_wifi_plans($request) {
    global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM wp_wifi_plans", ARRAY_A);
    if (empty($results)) {
        return new WP_REST_Response(['message' => 'No plans found for wifi'], 404);
    }
    return new WP_REST_Response($results, 200);
}



// POST callback function: adds a new wifi plan
function handle_add_wifi_plan($request) {
    global $wpdb;
    $table_name = 'wp_wifi_plans';

    $params = $request->get_params();

    // Check if the required parameters are set
    if (empty($params['speed']) || empty($params['color']) || empty($params['6_month']) || empty($params['12_month'])) {
        return new WP_REST_Response(['message' => 'Missing required parameters.'], 400);
    }

    $insert_result = $wpdb->insert(
        $table_name,
        [
            'speed' => $params['speed'],
            'color' => $params['color'],
            '6_month' => $params['6_month'],
            '12_month' => $params['12_month']
        ],
        ['%d', '%s', '%s', '%s']
    );

    if ($insert_result === false) {
        return new WP_REST_Response(['message' => 'Failed to add new wifi plan.', 'db_error' => $wpdb->last_error], 500);
    }

    return new WP_REST_Response(['message' => 'Wifi plan added successfully.', 'id' => $wpdb->insert_id], 201);
}

// PUT callback function: edits an existing wifi plan
function handle_edit_wifi_plan($request) {
    global $wpdb;
    $table_name = 'wp_wifi_plans';

    $params = $request->get_params();
    $plan_id = $params['plan_id'];

    // Data to update, sanitized and filtered to remove empty values
    $data_to_update = [];
    if (isset($params['speed'])) {
        $data_to_update['speed'] = absint($params['speed']);
    }
    if (isset($params['color'])) {
        $data_to_update['color'] = sanitize_text_field($params['color']);
    }
    if (isset($params['6_month'])) {
        $data_to_update['6_month'] = sanitize_text_field($params['6_month']);
    }
    if (isset($params['12_month'])) {
        $data_to_update['12_month'] = sanitize_text_field($params['12_month']);
    }

    if (empty($data_to_update)) {
        return new WP_REST_Response(['message' => 'No data provided for update.'], 400);
    }

    $update_result = $wpdb->update(
        $table_name,
        $data_to_update,
        ['plan_id' => $plan_id],
        ['%d', '%s', '%s', '%s'], // Format of the updated data
        ['%d'] // Format of the WHERE clause
    );

    if ($update_result === false) {
        return new WP_REST_Response(['message' => 'Failed to update wifi plan.', 'db_error' => $wpdb->last_error], 500);
    }

    if ($update_result === 0) {
        return new WP_REST_Response(['message' => 'No changes made or plan not found.'], 200);
    }

    return new WP_REST_Response(['message' => 'Wifi plan updated successfully.'], 200);
}