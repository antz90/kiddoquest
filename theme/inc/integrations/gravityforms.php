<?php
// Adjust $FORM_ID to your "Jurnal Harian" form ID.
const KIDDOQUEST_JOURNAL_FORM_ID = 1;

add_action('gform_after_submission', function ($entry, $form) {
    if ((int)$form['id'] !== KIDDOQUEST_JOURNAL_FORM_ID) return;

    $player_id = kiddoquest_get_active_player();
    if (! $player_id) return;

    // Duplicate check by day (use a simple user_meta flag)
    $key = 'journal_done_' . kiddoquest_today_key();
    if (get_user_meta($player_id, $key, true)) return;

    // Create jurnal post
    $content = ''; // map fields as needed
    $pid = wp_insert_post([
        'post_type'   => 'jurnal',
        'post_status' => 'private',
        'post_title'  => sprintf('Jurnal %s - %s', get_userdata($player_id)->display_name, current_time('mysql')),
        'post_content' => $content,
    ]);

    // Award points for journal (tune the amounts or source from a settings page)
    if (function_exists('gamipress_award_points_to_user')) {
        gamipress_award_points_to_user($player_id, 1, 'star');
        gamipress_award_points_to_user($player_id, 10, 'xp');
    }

    kiddoquest_create_task_log($player_id, 0, ['_type' => 'journal', '_jurnal_id' => $pid]);
    update_user_meta($player_id, $key, 1);
}, 10, 2);
