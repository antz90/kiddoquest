<?php
// This file handles all scheduled tasks (WP-Cron).

// 1. Add a custom schedule for the monthly reset.
add_filter('cron_schedules', 'kiddoquest_add_monthly_cron_schedule');
function kiddoquest_add_monthly_cron_schedule($schedules)
{
    if (!isset($schedules['monthly'])) {
        $schedules['monthly'] = array(
            'interval' => 2635200, // Approx. 1 month in seconds
            'display'  => __('Once Monthly', 'kiddoquest'),
        );
    }
    return $schedules;
}

// 2. Schedule our events if they are not already scheduled.
if (!wp_next_scheduled('kiddoquest_daily_coin_reset_event')) {
    // Schedule the daily event to run at 18:30 server time.
    // WordPress will run it at the first opportunity after this time.
    wp_schedule_event(strtotime('today 18:30:00'), 'daily', 'kiddoquest_daily_coin_reset_event');
}
if (!wp_next_scheduled('kiddoquest_monthly_star_reset_event')) {
    // Schedule the monthly event.
    wp_schedule_event(strtotime('first day of next month 00:01:00'), 'monthly', 'kiddoquest_monthly_star_reset_event');
}


// 3. Hook the functions to our custom events.
add_action('kiddoquest_daily_coin_reset_event', 'kiddoquest_execute_daily_coin_reset');
add_action('kiddoquest_monthly_star_reset_event', 'kiddoquest_execute_monthly_star_reset');


/**
 * The function that performs the daily coin reset.
 */
function kiddoquest_execute_daily_coin_reset()
{
    // Get all players (users with 'subscriber' role)
    $players = get_users(['role' => 'subscriber']);

    foreach ($players as $player) {
        $player_id = $player->ID;

        // --- A. Calculate total coins earned today ---
        $today_logs = new WP_Query([
            'post_type'      => 'log-tugas',
            'posts_per_page' => -1,
            'date_query'     => [['year' => current_time('Y'), 'month' => current_time('m'), 'day' => current_time('d')]],
            'meta_query'     => [
                'relation' => 'AND',
                ['key' => '_user_id', 'value' => $player_id],
                ['key' => '_points_awarded_coin', 'compare' => 'EXISTS'], // Only get logs that have coin points
            ],
        ]);

        $total_coins_today = 0;
        if ($today_logs->have_posts()) {
            foreach ($today_logs->posts as $log_post) {
                $coins = (int) get_post_meta($log_post->ID, '_points_awarded_coin', true);
                $total_coins_today += $coins;
            }
        }

        // --- B. Create a summary log entry ---
        $log_post_id = wp_insert_post([
            'post_type'   => 'log-tugas',
            'post_title'  => 'Laporan Koin Harian - ' . $player->display_name,
            'post_status' => 'private',
            'post_author' => $player_id,
        ]);
        if ($log_post_id) {
            update_post_meta($log_post_id, '_user_id', $player_id);
            update_post_meta($log_post_id, '_log_type', 'coin_summary');
            update_post_meta($log_post_id, '_total_coins_earned', $total_coins_today);
        }

        // --- C. Reset the coin balance to 0 ---
        gamipress_update_user_points($player_id, 0, 'coin');
    }
}


/**
 * The function that performs the monthly star reset.
 */
function kiddoquest_execute_monthly_star_reset()
{
    $players = get_users(['role' => 'subscriber']);

    foreach ($players as $player) {
        $player_id = $player->ID;

        // --- A. Get current star balance before resetting ---
        $current_star_balance = (int) gamipress_get_user_points($player_id, 'star');

        // --- B. Create a summary log entry ---
        $log_post_id = wp_insert_post([
            'post_type'   => 'log-tugas',
            'post_title'  => 'Laporan Bintang Bulanan - ' . $player->display_name,
            'post_status' => 'private',
            'post_author' => $player_id,
        ]);
        if ($log_post_id) {
            update_post_meta($log_post_id, '_user_id', $player_id);
            update_post_meta($log_post_id, '_log_type', 'star_summary');
            update_post_meta($log_post_id, '_star_balance_before_reset', $current_star_balance);
        }

        // --- C. Reset the star balance to 0 ---
        gamipress_update_user_points($player_id, 0, 'star');
    }
}
