<?php
add_action('rest_api_init', function() {
    // Include endpoint files
    require_once IWS_CUSTOM_CODE_PATH . 'endpoints/get-user-endpoint.php';
    require_once IWS_CUSTOM_CODE_PATH . 'endpoints/user-plan-endpoint.php';
    require_once IWS_CUSTOM_CODE_PATH . 'endpoints/add-user-endpoint.php';
    require_once IWS_CUSTOM_CODE_PATH . 'endpoints/get-wifi-plans-endpoint.php';
    require_once IWS_CUSTOM_CODE_PATH . 'endpoints/get-ott-plans-endpoint.php';
    // require_once IWS_CUSTOM_CODE_PATH . 'endpoints/delete-user-endpoint.php';
});