<?php
add_filter('jwt_auth_token_before_dispatch', 'add_user_role_to_jwt_response', 10, 2);

function add_user_role_to_jwt_response($data, $user) {
    $user_roles = $user->roles;
    if (!empty($user_roles)) {
        $data['user_role'] = $user_roles[0];
    }
    return $data;
}