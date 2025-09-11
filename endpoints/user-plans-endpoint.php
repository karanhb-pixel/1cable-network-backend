<?php
// Register user-plans endpoint
register_rest_route('wp/v2/iws/v1', 'users', [
    'methods' => 'GET',
    'callback' => 'get_user_plans',
    'permission_callback' => 'check_user_permission'
]);

// Callback function
function get_user_plans($request) {
    // (Paste the get_user_plans function code here)
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    $user_id = $current_user->ID;
    $user_email = $current_user->user_email;

    if (!$user_id) {
        return new WP_REST_Response(['error' => 'Authentication failed'], 401);
    }

    global $wpdb;
    if (in_array('administrator', $user_roles)) {
        // $results = $wpdb->get_results("SELECT * FROM user_plans", ARRAY_A);
        $results = $wpdb->get_results("SELECT up.plan_id, up.username, up.start_date, up.end_date, up.email, wp.speed AS wifi_speed, ot.duration AS ott_duration FROM user_plans AS up LEFT JOIN wp_wifi_plans AS wp ON up.wifi_plan = wp.plan_id LEFT JOIN wp_ott_plans AS ot ON up.ott_plan = ot.plan_id", ARRAY_A);
    } else if (in_array('subscriber', $user_roles)) {
        $results = $wpdb->get_results($wpdb->prepare("SELECT up.plan_id, up.username, up.start_date, up.end_date, up.email, wp.speed AS wifi_speed, ot.duration AS ott_duration FROM user_plans AS up LEFT JOIN wp_wifi_plans AS wp ON up.wifi_plan = wp.plan_id LEFT JOIN wp_ott_plans AS ot ON up.ott_plan = ot.plan_id WHERE email = %s", $user_email), ARRAY_A);
    }

    if (empty($results)) {
        return new WP_REST_Response(['message' => 'No plans found for this user'], 404);
    }

    return new WP_REST_Response($results, 200);
}
   
   