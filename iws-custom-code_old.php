<?php
/*
 * Plugin Name:       IWS Custom Code - old
 * Description:       Add custom code snippets to your WordPress site safely and easily.
 * Author:            karan
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      5.6
 */

// CORS headers for all requests (including errors and preflight)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    status_header(200);
    exit();
}

remove_filter("the_excerpt","wpautop");
remove_filter("the_content","wpautop");



add_action('rest_api_init', function() {
    // CORS headers
    remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
    add_filter( 'rest_pre_serve_request', function( $value ) {
        header( 'Access-Control-Allow-Origin: *' );
        header( 'Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS' );
        header( 'Access-Control-Allow-Credentials: true' );
        header( 'Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce' );
        return $value;
    });

    

    register_rest_route('wp/v2','user-plans',[
        'methods' => 'GET',
        'callback' => 'get_user_plans',
        'permission_callback' => 'check_user_permission'
    ]);

    register_rest_route('wp/v2','add-user',[
        'methods' => 'post',
        'callback' => 'handle_add_user',
        'permission_callback' => 'check_create_user_permission',
        'args' => [
            'username' => [
                'description' => 'Login name for the user.',
                'type' => 'string',
                'required' => true,
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
                'required' => true,
                'sanitize_callback' => 'sanitize_email',
                'validate_callback' => 'is_email',
            ],
            'nickname' => [
                'description' => 'The nickname for the user.',
                'type' => 'string',
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'roles' => [
                'description' => 'Role assigned to the user.',
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'password' => [
                'description' => 'Password for the user (never included).',
                'type' => 'string',
                'required' => true,
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
});

// Check if the user is logged in
function check_user_permission() {
    return is_user_logged_in();
}
//check if the user has permission to create user like Administator
function check_create_user_permission(){
    return current_user_can('create_users');
}

function handle_add_user($request){
    $params = $request->get_json_params();

    $username    = isset($params['username'])    ? $params['username']    : '';
    $name        = isset($params['name'])        ? $params['name']        : '';
    $first_name  = isset($params['first_name'])  ? $params['first_name']  : '';
    $last_name   = isset($params['last_name'])   ? $params['last_name']   : '';
    $email       = isset($params['email'])       ? $params['email']       : '';
    $nickname    = isset($params['nickname'])    ? $params['nickname']    : '';
    $roles       = isset($params['roles'])       ? $params['roles']       : '';
    $password    = isset($params['password'])    ? $params['password']    : '';
    $wifi_plan   = isset($params['wifi_plan'])   ? $params['wifi_plan']   : '';
    $ott_plan    = isset($params['ott_plan'])    ? $params['ott_plan']    : '';
    $start_date  = isset($params['start_date'])  ? $params['start_date']  : '';
    $end_date    = isset($params['end_date'])    ? $params['end_date']    : '';

    // Create the user
    $user_id = wp_insert_user( array(
        'user_login'    => $username,
        'user_pass'     => $password,
        'user_email'    => $email,
        'role'          => $roles,
        'display_name'  => $name,
        'first_name'    => $first_name,
        'last_name'     => $last_name,
        'nickname'      => $nickname,
    ) );

    if ( is_wp_error( $user_id ) ) {
        return new WP_REST_Response( array( 'error' => $user_id->get_error_message() ), 400 );
    }

    global $wpdb;
    $table_name = 'user_plans';

    $insert_result = $wpdb->insert( 
        $table_name, 
        array(
            'username'   => $username,
            'email'      => $email,
            'wifi_plan'  => $wifi_plan,
            'ott_plan'   => $ott_plan,
            'start_date' => $start_date,
            'end_date'   => $end_date,
        ), 
        array( 
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
        ) 
    );

    // Check if the plan data insertion was successful.
    if ( ! $insert_result ) {
        // Log MySQL error for debugging
        error_log('MySQL Insert Error (user_plans): ' . $wpdb->last_error);
        if ( function_exists('wp_delete_user') ) {
            wp_delete_user( $user_id );
        }
        return new WP_REST_Response( array(
            'success' => false,
            'message' => 'Failed to add plan data.',
            'db_error' => $wpdb->last_error
        ), 500 );
    }

    return new WP_REST_Response( array( 'success' => true, 'user_id' => $user_id,'username'=>$username,'message'=>'User and Plan Sucessfully Created.' ), 201 );
}


// Get user plans
function get_user_plans($request){
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    $user_id = $current_user->ID;
    $user_email = $current_user->user_email;

    // Check if a user ID was successfully retrieved.
    if ( ! $user_id ) {
        return new WP_REST_Response( array( 'error' => 'Authentication failed' ), 401 );
    }

    if(in_array('administrator',$user_roles)){
        global $wpdb;
        // Retrieve all user plans for administrators
        $results = $wpdb->get_results( "SELECT * FROM user_plans", ARRAY_A );
        if ( empty($results) ) {
            // No plans found in the table
            return new WP_REST_Response( array( 'message' => 'No User found in database' ), 404 );
        } else {
            return new WP_REST_Response( $results, 200 );
        }

    }else if(in_array('subscriber',$user_roles)){
        global $wpdb;
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM user_plans WHERE email = %s",
            $user_email
        ), ARRAY_A );
        // $results now contains all user_plans rows for this email
        if ( empty($results) ) {
            // No plans found for this user
            return new WP_REST_Response( array( 'message' => 'No plans found for this user' ), 404 );
        } else {
            // Plans found for this user
            return new WP_REST_Response( $results, 200 );
        }
    }
}
// Add User Role to JWT Response
//
// This filter modifies the data array before it is sent in the JWT response,
// adding the user's role for easy access on the frontend.
add_filter( 'jwt_auth_token_before_dispatch', 'add_user_role_to_jwt_response', 10, 2 );

/**
 * Adds the user's role to the JWT response data.
 *
 * @param array $data The original response data array.
 * @param WP_User $user The WP_User object for the authenticated user.
 * @return array The modified response data array.
 */
function add_user_role_to_jwt_response( $data, $user ) {
    // Get the user's roles.
    // get_roles() returns an array of role slugs.
    $user_roles = $user->roles;

    // Add the roles to the response data array.
    // We'll add the first role, as many users only have one.
    // You could also add the full array if your application requires multiple roles.
    if ( ! empty( $user_roles ) ) {
        $data['user_role'] = $user_roles[0];
    }

    return $data;
}
?>