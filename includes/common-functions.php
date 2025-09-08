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
