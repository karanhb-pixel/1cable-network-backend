<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Migration function to move user plan data to user meta.
 */
function my_custom_migrate_plan_data()
{
    // Check if the migration has already run.
    if (get_option('my_custom_migration_complete') === 'yes') {
        return;
    }

    global $wpdb;

    // Get all user plan data from your custom table
    $plans_to_migrate = $wpdb->get_results("SELECT *
        FROM wp_users
        INNER JOIN user_plans
        ON wp_users.user_email = user_plans.email;
        ", ARRAY_A);

    error_log("plan_to_migrate". print_r($plans_to_migrate, true));
    if (!empty($plans_to_migrate)) {
        foreach ($plans_to_migrate as $plan) {
            $user_id = $plan['ID'];

            // Add or update the custom data in user meta
            update_user_meta($user_id, 'wifi_plan', $plan['wifi_plan']);
            update_user_meta($user_id, 'ott_plan', $plan['ott_plan']);
            update_user_meta($user_id, 'start_date', $plan['start_date']);
            update_user_meta($user_id, 'end_date', $plan['end_date']);

            $result = get_user_meta($user_id, '', true);
            if (!empty($result)) {
                error_log('get_user_meta : '. print_r($result, true));
            }
            error_log("Migrated plan data for user ID: {$user_id}");
        }
    }

    // Set an option to ensure the migration only runs once.
    update_option('my_custom_migration_complete', 'yes');

    // Optionally, you can drop the old table here after verification.
    // $wpdb->query("DROP TABLE user_plans");
}

// Attach the migration function to the 'init' hook.
add_action('init', 'my_custom_migrate_plan_data');

