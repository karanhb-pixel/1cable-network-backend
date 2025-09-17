<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registers custom meta fields for users.
 *
 * This function is hooked to 'rest_api_init' to ensure meta fields
 * are available to the REST API.
 */
function iws_register_user_meta() {
    register_meta('user', 'wifi_plan', [
        'single'       => true,
        'type'         => 'integer',
        'auth_callback' => 'check_create_user_permission',
        'show_in_rest' => true,
    ]);
    register_meta('user', 'ott_plan', [
        'single'       => true,
        'type'         => 'integer',
        'auth_callback' => 'check_create_user_permission',
        'show_in_rest' => true,
    ]);
    register_meta('user', 'start_date', [
        'single'       => true,
        'type'         => 'string',
        'auth_callback' => 'check_create_user_permission',
        'show_in_rest' => true,
    ]);
    register_meta('user', 'end_date', [
        'single'       => true,
        'type'         => 'string',
        'auth_callback' => 'check_create_user_permission',
        'show_in_rest' => true,
    ]);
}
add_action('rest_api_init', 'iws_register_user_meta');

// Include the permissions check function if it's not in the main plugin file
// require_once plugin_dir_path(__FILE__) . 'class-user-endpoints.php';
