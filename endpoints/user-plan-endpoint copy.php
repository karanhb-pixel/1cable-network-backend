<?php
// Register user-plan endpoint
register_rest_route('wp/v2', 'user-plan/(?P<id>\d+)', [
    'methods' => ['GET', 'PUT'],
    'callback' => 'handle_user_plan',
    'permission_callback' => 'check_user_permission',
    'args' => [
        'id' => [
            'validate_callback' => function($param, $request, $key) {
                return is_numeric($param);
            }
        ]
    ]
]);



// Callback function
function handle_user_plan($request) {
    $id = $request->get_param('id');
    // echo('ID: ' . $id . ', type: ' . gettype($id));
    $method = $request->get_method();
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    $user_id = $current_user->ID;
    $user_email = $current_user->user_email;

    if (!$user_id) {
        return new WP_REST_Response(['error' => 'Authentication failed'], 401);
    }

     // A helper function to split a full name into first and last name
    function split_name($full_name) {
        $parts = explode(' ', trim($full_name));
        $first_name = array_shift($parts);
        $last_name = implode(' ', $parts);
        return [
            'first_name' => $first_name,
            'last_name' => $last_name
        ];
    }

    global $wpdb;

    if ($method === 'GET') {
        // Fetch single plan
        // $query = "SELECT up.plan_id, up.username, up.start_date, up.end_date, up.email, wp.speed AS wifi_speed, ot.duration AS ott_duration FROM user_plans AS up LEFT JOIN wp_wifi_plans AS wp ON up.wifi_plan = wp.plan_id LEFT JOIN wp_ott_plans AS ot ON up.ott_plan = ot.plan_id WHERE up.plan_id = %d";
        $query = "SELECT up.plan_id, up.username, up.start_date, up.end_date, up.email, up.wifi_plan , up.ott_plan FROM user_plans AS up WHERE up.plan_id = %d";
        // $results = $wpdb->get_results($wpdb->prepare("SELECT up.plan_id, up.username, up.start_date, up.end_date, up.email, wp.speed AS wifi_speed, ot.duration AS ott_duration FROM user_plans AS up LEFT JOIN wp_wifi_plans AS wp ON up.wifi_plan = wp.plan_id LEFT JOIN wp_ott_plans AS ot ON up.ott_plan = ot.plan_id WHERE email = %s", $user_email), ARRAY_A);
        
        if (!in_array('administrator', $user_roles)) {
            $query .= " AND up.email = %s";
            $results = $wpdb->get_results($wpdb->prepare($query, $id, $user_email), ARRAY_A);
        } else {
            $results = $wpdb->get_results($wpdb->prepare($query, $id), ARRAY_A);
        }

        if (empty($results)) {
            return new WP_REST_Response(['message' => 'Plan not found with id ' . $id], 404);
        }
        function check_function(){
            
        }
        if(function_exists('get_user_by')){
            $user = get_user_by('email',$results[0]['email']);
            $names = split_name($user->display_name);
            
            $filter_user = [
                'user_id' => $user->ID,
                'nicename' => $user->user_nicename,
                'display_name' => $user->display_name,
                'roles' => $user->roles[0],
                'first_name' => $names['first_name'],
                'last_name' => $names['last_name']
            ];

            $responce_data = [...$results[0],...$filter_user];
            
            // error_log(print_r($responce_data, true));
            // error_log(print_r($results[0], true));
            return new WP_REST_Response($responce_data, 200);
        }else{

            return new WP_REST_Response($results[0], 200);
        }

        // print "hello".$result;
        // return new WP_REST_Response($results[0], 200);
        
    } elseif ($method === 'PUT') {
        // Update plan
        if (!in_array('administrator', $user_roles)) {
            return new WP_REST_Response(['error' => 'Insufficient permissions'], 403);
        }

        $data = $request->get_json_params();

        // Sanitize inputs
        $update_data = [];
        if (isset($data['username'])) {
            $update_data['username'] = sanitize_text_field($data['username']);
        }
        if (isset($data['start_date'])) {
            $update_data['start_date'] = sanitize_text_field($data['start_date']);
        }
        if (isset($data['end_date'])) {
            $update_data['end_date'] = sanitize_text_field($data['end_date']);
        }
        if (isset($data['email'])) {
            $update_data['email'] = sanitize_email($data['email']);
        }
        if (isset($data['wifi_plan'])) {
            $update_data['wifi_plan'] = intval($data['wifi_plan']);
        }
        if (isset($data['ott_plan'])) {
            $update_data['ott_plan'] = intval($data['ott_plan']);
        }

        if (empty($update_data)) {
            return new WP_REST_Response(['error' => 'No valid fields to update'], 400);
        }

        $result = $wpdb->update('user_plans', $update_data, ['plan_id' => $id]);

        if ($result === false) {
            return new WP_REST_Response(['error' => 'Update failed'], 500);
        }

        return new WP_REST_Response(['message' => 'Plan updated successfully'], 200);
    }

    return new WP_REST_Response(['error' => 'Method not allowed'], 405);
}