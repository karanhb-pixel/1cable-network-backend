<?php
// Prevent direct access
if (!defined(constant_name: 'ABSPATH')) {
    exit;
}
require_once(ABSPATH . 'wp-admin/includes/user.php');
/**
 * Register the delete user endpoint
 */
add_action('rest_api_init', function () {

    register_rest_route(route_namespace: 'wp/v2/iws/v1', route: 'users', args: [
        'methods' => 'DELETE',
        'callback' => 'iws_delete_user_callback',
        'permission_callback' => 'iws_delete_user_permissions_check'
    ]);
});



function iws_delete_user_permissions_check($request)
{
    return current_user_can(capability: 'delete_users');
}

/**
 * Callback function to handle user deletion
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function iws_delete_user_callback($request)
{
    $plan_id = $request->get_param('plan_id');
    $user_plans_table = 'user_plans';
    global $wpdb;

    error_log(message: "iws_delete_user_callback called with id: " . $plan_id);

    $params = $request->get_json_params();
    error_log(print_r("getting details using params :  ", true));
    error_log(print_r($params, true));

        if (isset($params['email'])) {
            $user_email = sanitize_user($params['email']);
        }


    // Check if user exists
    $user = get_user_by(field: 'email', value: $user_email);
    error_log(print_r("getting details using username(login) :  ", true));
    error_log(print_r($user, true));
    
    
    if (!$user) {
        return new WP_Error(code: 'user_not_found', message: 'User not found', data: array('status' => 404));
    }

    $user_id = $user->ID;
    // error_log(print_r("getting details using username(login) :  ", true));
    // error_log(print_r($user), true);

    $wpdb->query(query: "START TRANSACTION");

    $delete_plan_result = $wpdb->delete(table: $user_plans_table, where: ['plan_id' => $plan_id]);
    if ($delete_plan_result === false) {
        //Rollback transaction if plan deletion fails
        $wpdb->query(query: 'ROLLBACK');
        error_log(message: "Failed to delete plan from user_plans table. MySQL error: {$wpdb->last_error}");
        return new WP_REST_Response(data: [
            'message' => 'Failed to delete user plan.',
            'db_error' => $wpdb->last_error
        ], status: 500);
    }
    $delete_user_result = [];
    if (function_exists(function: 'wp_delete_user')) {
        // error_log(message: print_r(value: "wp_delete_user function exist."),message_type: true);
        $delete_user_result = wp_delete_user(id: $user_id);
    }


    if (is_wp_error(thing: $delete_user_result)) {
        // Rollback the transaction if user deletion fails.
        $wpdb->query(query: "ROLLBACK");
        return new WP_REST_Response(data: [
            'message' => 'Failed to delete WordPress user.',
            'error_message' => $delete_user_result->get_error_message()
        ], status: 500);
    }

    // If both deletions were successful, commit the transaction.
    $wpdb->query(query: "COMMIT");

    return new WP_REST_Response(data: ['message' => 'User and plan deleted successfully.'], status: 200);

}

