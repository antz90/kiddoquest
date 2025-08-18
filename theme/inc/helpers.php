<?php
// Common helpers used across templates and AJAX.

/**
 * Calculates player's level progress based on a custom, hardcoded level map.
 *
 * @param int $user_xp The user's current total XP.
 * @return array Contains 'level_name' and 'percentage'.
 */
function kiddoquest_get_player_custom_level_progress($user_xp)
{
    $levels_map = kiddoquest_get_level_map();

    $current_level_num = 0;
    $current_level_name = 'Start';
    $current_level_req = 0;
    $next_level_req = 0;

    // Loop through the map to find the user's current level.
    foreach ($levels_map as $level_num => $level_data) {
        if ($user_xp >= $level_data['xp_required']) {
            $current_level_num = $level_num;
            $current_level_name = $level_data['name'];
            $current_level_req = $level_data['xp_required'];
        } else {
            // The first level the user has NOT reached is the next level.
            $next_level_req = $level_data['xp_required'];
            break;
        }
    }

    // Determine the requirement for the actual next level, if it exists.
    if (isset($levels_map[$current_level_num + 1])) {
        $next_level_req = $levels_map[$current_level_num + 1]['xp_required'];
    } else {
        // User is at the max level.
        $next_level_req = $current_level_req;
    }

    // Calculate percentage
    $percentage = 0;
    if ($next_level_req > $current_level_req) {
        $points_to_next_level = $next_level_req - $current_level_req;
        $points_earned_in_level = $user_xp - $current_level_req;
        $percentage = ($points_earned_in_level / $points_to_next_level) * 100;
    } else {
        // This means user is at max level.
        $percentage = 100;
    }

    return [
        'level_name' => $current_level_name,
        'next_level' => $next_level_req,
        'percentage' => min($percentage, 100), // Ensure it never goes above 100.
    ];
}


/**
 * Custom helper function to get all GamiPress point types with their essential data.
 * This is more reliable than depending on specific GamiPress template tags that might change.
 *
 * @return array An associative array of point types, keyed by their slug.
 */
function kiddoquest_get_all_point_types_data()
{
    $point_types_data = [];

    // Get all 'point-type' posts.
    $args = array(
        'post_type'      => 'points-type',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    );

    $point_types = get_posts($args);

    if (empty($point_types)) {
        return $point_types_data; // Return empty if none found.
    }

    // Loop through each point type post to extract our needed data.
    foreach ($point_types as $pt) {
        $slug = $pt->post_name; // e.g., 'coin', 'star', 'xp'

        $point_types_data[$slug] = [
            'singular_name' => get_post_meta($pt->ID, '_gamipress_singular_name', true),
            'plural_name'   => get_post_meta($pt->ID, '_gamipress_plural_name', true),
            'icon_url'      => get_the_post_thumbnail_url($pt->ID, 'full'), // Get the URL of the featured image.
        ];
    }

    return $point_types_data;
}

/**
 * Calculates potential coins a player can earn, can be filtered by shift.
 *
 * @param int $player_id The ID of the player.
 * @param string $shift 'full_day' or 'pagi_siang'.
 * @return int The total potential coins.
 */
function kiddoquest_get_potential_coins($player_id, $shift = 'full_day')
{
    $total_potential = 0;
    $today = current_time('l');

    if (have_rows('daftar_tugas_anak', 'user_' . $player_id)) {
        while (have_rows('daftar_tugas_anak', 'user_' . $player_id)) : the_row();
            $scheduled_days = get_sub_field('jadwalkan_di_hari');
            if (is_array($scheduled_days) && in_array($today, $scheduled_days)) {
                $task_post = get_sub_field('pilih_tugas');
                if ($task_post) {
                    // If we only want the morning shift, check the task's time setting.
                    if ($shift === 'pagi_siang') {
                        $task_time = get_field('waktu_tugas', $task_post->ID);
                        if ($task_time !== 'pagi_siang') {
                            continue; // Skip this task if it's not a morning/afternoon task.
                        }
                    }

                    // We use our other helper function to get the max points for the task.
                    $total_potential += kiddoquest_get_max_points_for_task($task_post->ID);
                }
            }
        endwhile;
    }
    return $total_potential;
}


/**
 * Gets the maximum possible coin points for a single task post ID.
 *
 * @param int $task_id The ID of the task post.
 * @return int The highest possible coin value.
 */
function kiddoquest_get_max_points_for_task($task_id)
{
    if (!$task_id) return 0;

    $max_points = (int) get_field('point_koin_tugas', $task_id);

    if (have_rows('point_koin_berwaktu', $task_id)) {
        while (have_rows('point_koin_berwaktu', $task_id)) : the_row();
            $timed_coins = (int) get_sub_field('jumlah_koin');
            if ($timed_coins > $max_points) {
                $max_points = $timed_coins;
            }
        endwhile;
    }
    return $max_points;
}
