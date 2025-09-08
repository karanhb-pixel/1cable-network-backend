<?php
// Register a single REST route for ott-plans that handles multiple methods
    register_rest_route('wp/v2', 'ott-plans', [
        [
            'methods' => 'GET',
            'callback' => 'get_ott_plans',
            'permission_callback' => '__return_true' // Publicly accessible to get plans
        ],
        [
            'methods' => 'POST',
            'callback' => 'handle_add_ott_plan',
            'permission_callback' => 'check_admin_permission', // Only for administrators
            'args' => [
                'duration' => [
                    'description' => 'Duration of the OTT plan.',
                    'type' => 'string',
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'color' => [
                    'description' => 'Color associated with the plan.',
                    'type' => 'string',
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'price' => [
                    'description' => 'Price of the OTT plan.',
                    'type' => 'string',
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ],
        [
            'methods' => 'PUT',
            'callback' => 'handle_edit_ott_plan',
            'permission_callback' => 'check_admin_permission', // Only for administrators
            'args' => [
                'plan_id' => [
                    'description' => 'ID of the plan to update.',
                    'type' => 'integer',
                    'required' => true,
                    'sanitize_callback' => 'absint',
                ],
                'duration' => [
                    'description' => 'Duration of the OTT plan.',
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'color' => [
                    'description' => 'Color associated with the plan.',
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'price' => [
                    'description' => 'Price of the OTT plan.',
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]
    ]);



// Callback function
function get_ott_plans($request) {
    global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM wp_ott_plans", ARRAY_A);
    if (empty($results)) {
        return new WP_REST_Response(['message' => 'No plans found for ott'], 404);
    }
    return new WP_REST_Response($results, 200);
}


// POST callback function: adds a new ott plan
function handle_add_ott_plan($request) {
    global $wpdb;
    $table_name = 'wp_ott_plans';

    $params = $request->get_json_params();

    // Check if the required parameters are set
    if (empty($params['duration']) || empty($params['color']) || empty($params['price'])) {
        return new WP_REST_Response(['message' => 'Missing required parameters.'], 400);
    }

    $insert_result = $wpdb->insert(
        $table_name,
        [
            'duration' => $params['duration'],
            'color' => $params['color'],
            'price' => $params['price']
        ],
        ['%s', '%s', '%s']
    );

    if ($insert_result === false) {
        return new WP_REST_Response(['message' => 'Failed to add new ott plan.', 'db_error' => $wpdb->last_error], 500);
    }

    return new WP_REST_Response(['message' => 'ott plan added successfully.', 'id' => $wpdb->insert_id], 201);
}

// PUT callback function: edits an existing ott plan
function handle_edit_ott_plan($request) {
    global $wpdb;
    $table_name = 'wp_ott_plans';

    $params = $request->get_json_params();
    $plan_id = $params['plan_id'];

    // Data to update, sanitized and filtered to remove empty values
    $data_to_update = [];
    if (isset($params['duration'])) {
        $data_to_update['duration'] = sanitize_text_field($params['duration']);
    }
    if (isset($params['color'])) {
        $data_to_update['color'] = sanitize_text_field($params['color']);
    }
    if (isset($params['price'])) {
        $data_to_update['price'] = sanitize_text_field($params['price']);
    }

    if (empty($data_to_update)) {
        return new WP_REST_Response(['message' => 'No data provided for update.'], 400);
    }

    $update_result = $wpdb->update(
        $table_name,
        $data_to_update,
        ['plan_id' => $plan_id],
        ['%s', '%s', '%s'], // Format of the updated data
        ['%d'] // Format of the WHERE clause
    );

    if ($update_result === false) {
        return new WP_REST_Response(['message' => 'Failed to update ott plan.', 'db_error' => $wpdb->last_error], 500);
    }

    if ($update_result === 0) {
        return new WP_REST_Response(['message' => 'No changes made or plan not found.'], 200);
    }

    return new WP_REST_Response(['message' => 'ott plan updated successfully.'], 200);
}