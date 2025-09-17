<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Ensure the necessary user.php file is included
require_once(ABSPATH . 'wp-admin/includes/user.php');

/**
 * Register the delete user endpoint with a user ID in the URL.
 */
add_action('rest_api_init', function () {
    register_rest_route('wp/v2/iws/v1', 'users/(?P<id>\d+)', [
        'methods' => 'DELETE',
        'callback' => 'iws_delete_user_callback',
        'permission_callback' => 'iws_delete_user_permissions_check',
        'args' => [
            'id' => [
                'validate_callback' => function ($param) {
                    return is_numeric($param) && $param > 0;
                }
            ]
        ]
    ]);
});

/**
 * Check permissions for deleting a user.
 */
function iws_delete_user_permissions_check($request) {
    return current_user_can('delete_users');
}

/**
 * Callback function to handle user deletion using the user ID.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function iws_delete_user_callback($request) {
    $user_id = (int) $request->get_param('id');
    
    // Check if the function exists (required for robustness)
    if (!function_exists('wp_delete_user')) {
        return new WP_REST_Response([
            'message' => 'Internal Server Error: User deletion function not available.'
        ], 500);
    }

    // Attempt to delete the user.
    $delete_user_result = wp_delete_user($user_id);

    if (is_wp_error($delete_user_result)) {
        return new WP_REST_Response([
            'message' => 'Failed to delete WordPress user.',
            'error_message' => $delete_user_result->get_error_message()
        ], 500);
    }

    if ($delete_user_result === false) {
        return new WP_REST_Response([
            'message' => 'Failed to delete user. User may not exist or an internal error occurred.'
        ], 500);
    }

    return new WP_REST_Response(['message' => 'User deleted successfully.'], 200);
}
