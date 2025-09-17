<?php
/**
 * Common functions for IWS Custom Code plugin.
 */

// Check if the user is logged in
function check_user_permission() {
    return is_user_logged_in();
}

// Check if the user is an administrator
function check_admin_permission() {
    return current_user_can('administrator');
}

function get_all_custom_user_meta($user_id) {
    return [
        'wifi_plan'  => get_user_meta($user_id, 'wifi_plan', true),
        'ott_plan'   => get_user_meta($user_id, 'ott_plan', true),
        'start_date' => get_user_meta($user_id, 'start_date', true),
        'end_date'   => get_user_meta($user_id, 'end_date', true),
        'first_name'   => get_user_meta($user_id, 'first_name', true),
        'last_name'   => get_user_meta($user_id, 'last_name', true),
    ];
}