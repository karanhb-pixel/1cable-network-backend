<?php
// Register edit-user endpoint
register_rest_route('wp/v2/iws/v1', 'users', [
    'methods' => 'PUT',
    'callback' => 'handle_edit_user',
    'permission_callback' => 'check_edit_user_permission',
    'args' => [
        'user_id' => [
            'description' => 'User ID to filter results.',
            'type' => 'integer',
            'required' => false,
            'validate_callback' => function ($value) {
                return is_numeric($value) && $value > 0;
            },
        ],
        'username' => [
            'description' => 'Login name for the user.',
            'type' => 'string',
            'required' => false,
            'sanitize_callback' => 'sanitize_user',
        ],
        'name' => [
            'description' => 'Display name for the user.',
            'type' => 'string',
            'required' => false,
            'sanitize_callback' => 'sanitize_text_field',
        ],
        'first_name' => [
            'description' => 'First name for the user.',
            'type' => 'string',
            'required' => false,
            'sanitize_callback' => 'sanitize_text_field',
        ],
        'last_name' => [
            'description' => 'Last name for the user.',
            'type' => 'string',
            'required' => false,
            'sanitize_callback' => 'sanitize_text_field',
        ],
        'email' => [
            'description' => 'The email address for the user.',
            'type' => 'string',
            'format' => 'email',
            'required' => false,
            'sanitize_callback' => 'sanitize_email',
            'validate_callback' => 'is_email',
        ],
        'nicename' => [
            'description' => 'The nicename for the user.',
            'type' => 'string',
            'required' => false,
            'sanitize_callback' => 'sanitize_text_field',
        ],
        'roles' => [
            'description' => 'Role assigned to the user.',
            'type' => 'string',
            'required' => false,
            'sanitize_callback' => 'sanitize_text_field',
        ],
        'password' => [
            'description' => 'Password for the user (never included).',
            'type' => 'string',
            'required' => false,
        ],
        'wifi_plan' => [
            'description' => 'Wifi plan for the user.',
            'type' => 'string',
            'required' => false,
            'sanitize_callback' => 'sanitize_text_field',
        ],
        'ott_plan' => [
            'description' => 'OTT plan for the user.',
            'type' => 'string',
            'required' => false,
            'sanitize_callback' => 'sanitize_text_field',
        ],
        'start_date' => [
            'description' => 'Start date for the user plan.',
            'type' => 'string',
            'required' => false,
            'sanitize_callback' => function($date) {
                // Accepts empty or valid date string (YYYY-MM-DD)
                $date = sanitize_text_field($date);
                if (empty($date)) return '';
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return $date;
                return '';
            },
        ],
        'end_date' => [
            'description' => 'End date for the user plan.',
            'type' => 'string',
            'required' => false,
            'sanitize_callback' => function($date) {
                $date = sanitize_text_field($date);
                if (empty($date)) return '';
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return $date;
                return '';
            },
        ],
    ],
]);

// Check if the user has permission to edit user like Administrator
function check_edit_user_permission() {
    return current_user_can('edit_users');
}
function handle_edit_user($request) {
    $user_id_from_url = intval($request->get_param('id'));
    $params = $request->get_json_params();

    $user_to_update = get_user_by('ID', $user_id_from_url);
    if (!$user_to_update) {
        return new WP_REST_Response(['error' => 'User not found.'], 404);
    }
    error_log('User to update: ' . print_r($user_to_update, true));
    // --- Step 1: Collect standard user data for comparison ---
    $user_update_data = ['ID' => $user_to_update->ID];
    
    if (isset($params['username']) && $params['username'] !== $user_to_update->user_login) {
        $user_update_data['user_login'] = sanitize_user($params['username']);
    }
    if (isset($params['name']) && $params['name'] !== $user_to_update->display_name) {
        $user_update_data['display_name'] = sanitize_text_field($params['name']);
    }
    if (isset($params['first_name']) && $params['first_name'] !== $user_to_update->first_name) {
        $user_update_data['first_name'] = sanitize_text_field($params['first_name']);
    }
    if (isset($params['last_name']) && $params['last_name'] !== $user_to_update->last_name) {
        $user_update_data['last_name'] = sanitize_text_field($params['last_name']);
    }
    if (isset($params['email']) && $params['email'] !== $user_to_update->user_email) {
        $user_update_data['user_email'] = sanitize_email($params['email']);
    }
    if (isset($params['nicename']) && $params['nicename'] !== $user_to_update->user_nicename) {
        $user_update_data['user_nicename'] = sanitize_text_field($params['nicename']);
    }
    if (isset($params['roles']) && !in_array($params['roles'], $user_to_update->roles)) {
        $user_update_data['role'] = sanitize_text_field($params['roles']);
    }
    if (isset($params['password']) && !empty($params['password'])) {
        $user_update_data['user_pass'] = $params['password'];
    }

    // --- Step 2: Collect meta_input for comparison ---
    $meta_input = [];
    $current_meta = get_all_custom_user_meta($user_to_update->ID);

    if (isset($params['wifi_plan']) && sanitize_text_field($params['wifi_plan']) !== $current_meta['wifi_plan']) {
        $meta_input['wifi_plan'] = (empty($params['wifi_plan']) || trim($params['wifi_plan']) === '0') ? 0 : sanitize_text_field($params['wifi_plan']);
    }
    if (isset($params['ott_plan']) && sanitize_text_field($params['ott_plan']) !== $current_meta['ott_plan']) {
        $meta_input['ott_plan'] = (empty($params['ott_plan']) || trim($params['ott_plan']) === '0') ? 0 : sanitize_text_field($params['ott_plan']);
    }
    if (isset($params['start_date']) && sanitize_text_field($params['start_date']) !== $current_meta['start_date']) {
        $meta_input['start_date'] = !empty($params['start_date']) ? sanitize_text_field($params['start_date']) : null;
    }
    if (isset($params['end_date']) && sanitize_text_field($params['end_date']) !== $current_meta['end_date']) {
        $meta_input['end_date'] = !empty($params['end_date']) ? sanitize_text_field($params['end_date']) : null;
    }
    
    // Add meta_input to the user data array
    if (!empty($meta_input)) {
        $user_update_data['meta_input'] = $meta_input;
    }

    // --- Step 3: Perform the update only if there's something to change ---
    // Remove the 'ID' key for the check, as it will always be set.
    $check_for_changes = $user_update_data;
    unset($check_for_changes['ID']);

    error_log('check_for_changes'. print_r($check_for_changes, true));

    if (empty($check_for_changes)) {
        return new WP_REST_Response([
            'success' => true,
            'user_id' => $user_id_from_url,
            'message' => 'No changes detected. Update not performed.'
        ], 200);
    }
    
    $update_result = wp_update_user($user_update_data);

    if (is_wp_error($update_result)) {
        return new WP_REST_Response([
            'error' => $update_result->get_error_message()
        ], 400);
    }

    // --- Step 4: Return success response ---
    return new WP_REST_Response([
        'success' => true,
        'user_id' => $user_id_from_url,
        'message' => 'User updated successfully.'
    ], 200);
}