<?php
/*
 * Plugin Name:       IWS Custom Code
 * Description:       Add custom code snippets to your WordPress site safely and easily.
 * Author:            karan
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      5.6
 */

// Define plugin path for easier includes
define('IWS_CUSTOM_CODE_PATH', plugin_dir_path(__FILE__));

// Handle CORS headers
require_once IWS_CUSTOM_CODE_PATH . 'includes/cors-headers.php';

// Remove auto-formatting filters
remove_filter("the_excerpt", "wpautop");
remove_filter("the_content", "wpautop");

// Include the API route registration file
require_once IWS_CUSTOM_CODE_PATH . 'includes/api-routes.php';

// Include JWT modification file
require_once IWS_CUSTOM_CODE_PATH . 'includes/jwt-modifications.php';

// Include common functions file
require_once IWS_CUSTOM_CODE_PATH . 'includes/common-functions.php';