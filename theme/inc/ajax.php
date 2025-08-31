<?php

/**
 * Handles the task completion logic when a task is clicked on the dashboard.
 * Hooked to: wp_ajax_handle_task_completion
 */
function kiddoquest_handle_task_completion()
{
    // 1. SECURITY: Check the nonce sent from the frontend.
    if (!check_ajax_referer('complete_task_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Sesi tidak valid.']);
        return;
    }

    // 2. GET DATA: Sanitize the incoming player and task IDs.
    $player_id = isset($_POST['player_id']) ? intval($_POST['player_id']) : 0;
    $task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;

    if (!$player_id || !$task_id) {
        wp_send_json_error(['message' => 'Data tidak lengkap.']);
        return;
    }

    // 3. PREVENT DUPLICATES: Check if this task has already been completed today by this player.
    $today_log_query = new WP_Query([
        'post_type' => 'log-tugas',
        'posts_per_page' => 1,
        'date_query' => [
            ['year' => current_time('Y'), 'month' => current_time('m'), 'day' => current_time('d')],
        ],
        'meta_query' => [
            'relation' => 'AND',
            ['key' => '_user_id', 'value' => $player_id],
            ['key' => '_task_id', 'value' => $task_id],
        ],
    ]);

    if ($today_log_query->have_posts()) {
        wp_send_json_error(['message' => 'Tugas ini sudah selesai hari ini!']);
        return;
    }

    // 4. CALCULATE POINTS (Server-side to prevent cheating)
    // This logic is copied from the template to ensure security.
    $xp_points = (int) get_field('point_xp', $task_id);
    $star_points = (int) get_field('poin_bintang_tugas', $task_id); // Star points are fixed.

    $now = current_time('H:i');
    $coin_points = 0;
    $is_timed_coin = false;
    if (have_rows('point_koin_berwaktu', $task_id)) {
        while (have_rows('point_koin_berwaktu', $task_id)) : the_row();
            $start_time = get_sub_field('waktu_mulai');
            $end_time = get_sub_field('waktu_selesai');
            if (($start_time > $end_time && ($now >= $start_time || $now <= $end_time)) || ($start_time <= $end_time && ($now >= $start_time && $now <= $end_time))) {
                $coin_points = (int) get_sub_field('jumlah_koin');
                $is_timed_coin = true;
                break;
            }
        endwhile;
    }
    if (!$is_timed_coin) {
        $coin_points = (int) get_field('point_koin_tugas', $task_id);
    }


    // 5. AWARD/DEDUCT GAMIPRESS POINTS
    $log_entry_title = get_the_title($task_id);

    // Logic for Coins (can be negative)
    if ($coin_points > 0) {
        gamipress_award_points_to_user($player_id, $coin_points, 'coin', ['log_title' => $log_entry_title]);
    } elseif ($coin_points < 0) {
        // Use deduct function for negative points, and use abs() to make the number positive.
        gamipress_deduct_points_to_user($player_id, abs($coin_points), 'coin', ['log_title' => $log_entry_title . ' (Deduct)']);
    }

    // Logic for Star and XP (always positive)
    if ($star_points > 0) gamipress_award_points_to_user($player_id, $star_points, 'star', ['log_title' => $log_entry_title]);
    if ($xp_points > 0) gamipress_award_points_to_user($player_id, $xp_points, 'xp', ['log_title' => $log_entry_title]);


    // 6. CREATE LOG POST (CPT 'log-tugas')
    $player_data = get_userdata($player_id);
    $log_post_id = wp_insert_post([
        'post_type' => 'log-tugas',
        'post_title' => sprintf('"%s" oleh %s', $log_entry_title, $player_data->display_name),
        'post_status' => 'private', // Keep logs private.
    ]);
    if ($log_post_id) {
        update_post_meta($log_post_id, '_user_id', $player_id);
        update_post_meta($log_post_id, '_task_id', $task_id);

        // We save the points calculated in step #4.
        update_post_meta($log_post_id, '_points_awarded_coin', $coin_points);
        update_post_meta($log_post_id, '_points_awarded_star', $star_points);
        update_post_meta($log_post_id, '_points_awarded_xp', $xp_points);
    }

    // 7. GET RANDOM STICKER
    $sticker_url = '';
    $all_stickers = get_posts([
        'post_type' => 'stiker',
        'posts_per_page' => -1,
        'fields' => 'ids' // More efficient, just get IDs.
    ]);
    if (!empty($all_stickers)) {
        $random_sticker_id = $all_stickers[array_rand($all_stickers)];
        $sticker_url = get_field('stiker_url', $random_sticker_id);

        // The meta key will be something like 'kiddoquest_sticker_count_123' where 123 is the sticker post ID.
        $meta_key = KIDDOQUEST_PREFIX . '_sticker_count_' . $random_sticker_id;

        // Get the current count of this specific sticker for the user.
        // get_user_meta returns 0 if the key doesn't exist, which is perfect for us.
        $current_count = (int) get_user_meta($player_id, $meta_key, true);

        // Increment the count by 1.
        $new_count = $current_count + 1;

        // Save the new count back to the user's meta data.
        update_user_meta($player_id, $meta_key, $new_count);
    }

    // 8. PREPARE AND SEND SUCCESS RESPONSE
    $all_point_types = kiddoquest_get_all_point_types_data();
    $points_coin_data = isset($all_point_types['coin']) ? $all_point_types['coin'] : null;
    $points_star_data = isset($all_point_types['star']) ? $all_point_types['star'] : null;
    $points_xp_data = isset($all_point_types['xp']) ? $all_point_types['xp'] : null;

    $reward_text = '';
    if ($coin_points !== 0) $reward_text .= sprintf('%s%d <img src="%s" class="w-4 h-4 inline-block"> ', ($coin_points > 0 ? '+' : ''), $coin_points, $points_coin_data['icon_url']);
    if ($star_points !== 0) $reward_text .= sprintf('%s%d <img src="%s" class="w-4 h-4 inline-block"> ', ($star_points > 0 ? '+' : ''), $star_points, $points_star_data['icon_url']);
    if ($xp_points !== 0) $reward_text .= sprintf('+%d <img src="%s" class="w-4 h-4 inline-block">', $xp_points, $points_xp_data['icon_url']);

    $new_coin_balance = gamipress_get_user_points($player_id, 'coin');
    $new_star_balance = gamipress_get_user_points($player_id, 'star');
    $new_xp_balance   = gamipress_get_user_points($player_id, 'xp');

    $new_level_data = kiddoquest_get_player_custom_level_progress($new_xp_balance);

    $response_data = [
        'message' => 'Tugas berhasil diselesaikan!',
        'task_title' => $log_entry_title,
        'reward_text' => $reward_text,
        'sticker_url' => $sticker_url,
        'new_coin_balance'  => $new_coin_balance,
        'new_star_balance'  => $new_star_balance,
        'new_xp_balance'    => $new_xp_balance,
        'new_level_info'    => $new_level_data,
    ];

    wp_send_json_success($response_data);
}

// Hook the function to WordPress's AJAX system.
add_action('wp_ajax_handle_task_completion', 'kiddoquest_handle_task_completion');
// add_action('wp_ajax_nopriv_handle_task_completion', 'kiddoquest_handle_task_completion'); // Not needed if only for logged-in users, but good practice.


/**
 * Handles the item/reward purchase logic from the shop.
 * Hooked to: wp_ajax_handle_purchase
 */
function kiddoquest_handle_purchase()
{
    // 1. SECURITY & SETUP
    if (!isset($_SESSION['active_player_id'])) {
        wp_send_json_error(['message' => 'Player tidak aktif.']);
        return;
    }
    // Nonce check can be added for extra security if you want.

    $player_id = $_SESSION['active_player_id'];
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    $item_type = isset($_POST['item_type']) ? sanitize_text_field($_POST['item_type']) : '';

    if (!$item_id || !$item_type) {
        wp_send_json_error(['message' => 'Data pembelian tidak lengkap.']);
        return;
    }

    // 2. GET ITEM DETAILS & VALIDATE
    $price = 0;
    $point_type_slug = '';

    if ($item_type === 'item-kamar') {
        $price_type = get_field('tipe_harga_item', $item_id);

        if ($price_type === 'dinamis_adv') {
            $total_potential_coins = kiddoquest_get_potential_coins($player_id, 'full_day');
            $percentage = (int) get_field('persentase_harga_item', $item_id);
            $deduction = (int) get_field('pengurangan_harga_item', $item_id);
            $calculated_price = floor((($percentage / 100) * $total_potential_coins) - $deduction);
            $price = max(1, $calculated_price);
            $point_type_slug = 'coin';
        } elseif ($price_type === 'statis_permata') {
            $price = (int) get_field('harga_item_permata', $item_id);
            $point_type_slug = 'permata';
        } else { // 'statis' (coin)
            $price = (int) get_field('harga_item_statis', $item_id);
            $point_type_slug = 'coin';
        }

        // Validation: Check if item is already owned
        $owned_items = get_user_meta($player_id, 'owned_items', true);
        if (is_array($owned_items) && in_array($item_id, $owned_items)) {
            wp_send_json_error(['message' => 'Anda sudah memiliki item ini.']);
            return;
        }
    } elseif ($item_type === 'hadiah') {
        $price_type = get_field('tipe_harga', $item_id);
        $assigned_users = get_field('hadiah_untuk_user', $item_id);

        if (!empty($assigned_users) && !in_array($player_id, wp_list_pluck($assigned_users, 'ID'))) {
            wp_send_json_error(['message' => 'Anda tidak berhak membeli hadiah ini.']);
            return;
        }

        if ($price_type === 'dinamis') {
            $total_potential_coins = kiddoquest_get_potential_coins($player_id, 'pagi_siang');
            $price_percentage = (int) get_field('persentase_harga_koin', $item_id);
            $price = floor(($price_percentage / 100) * $total_potential_coins);
            $point_type_slug = 'coin';
        } else { // 'statis'
            $price = (int) get_field('harga_statis', $item_id);
            $point_type_slug = get_field('jenis_poin_statis', $item_id);
        }

        // --- VALIDASI BARU: Cek ke CPT 'log-tugas' ---
        $purchase_log_query = new WP_Query([
            'post_type' => 'log-tugas',
            'posts_per_page' => 1,
            'date_query' => [['year' => current_time('Y'), 'month' => current_time('m'), 'day' => current_time('d')]],
            'meta_query' => [
                'relation' => 'AND',
                ['key' => '_user_id', 'value' => $player_id],
                ['key' => '_purchased_item_id', 'value' => $item_id], // Custom meta key for purchases
            ],
        ]);

        if ($purchase_log_query->have_posts()) {
            wp_send_json_error(['message' => 'Hadiah ini sudah Anda beli hari ini.']);
            return;
        }

        // Validation: Re-check time restriction on the server
        if (get_field('terbatas_jam', $item_id)) {
            $now = strtotime(current_time('H:i'));
            $start = strtotime(get_field('jam_mulai', $item_id));
            $end = strtotime(get_field('jam_selesai', $item_id));
            if (($start > $end && ($now < $start && $now > $end)) || ($start <= $end && ($now < $start || $now > $end))) {
                wp_send_json_error(['message' => 'Sudah lewat waktu untuk membeli hadiah ini.']);
                return;
            }
        }
    } else {
        wp_send_json_error(['message' => 'Tipe item tidak valid.']);
        return;
    }

    // 3. CHECK PLAYER'S BALANCE
    $player_balance = (int) gamipress_get_user_points($player_id, $point_type_slug);

    error_log('point type ' . $point_type_slug);

    if ($player_balance < $price) {
        // Create a more detailed error message for debugging.
        $error_message = sprintf(
            'Poin tidak cukup! Poin %s Anda saat ini: %d, sedangkan harga item: %d.',
            strtoupper($point_type_slug),
            $player_balance,
            $price
        );
        wp_send_json_error(['message' => $error_message]);
        return;
    }

    // 4. PROCESS TRANSACTION
    // Deduct points from the player
    gamipress_deduct_points_to_user($player_id, $price, $point_type_slug, ['log_title' => 'Beli: ' . get_the_title($item_id)]);

    // --- LOGGING LOGIC BARU ---
    // Log all purchases to 'log-tugas' CPT
    $player_data = get_userdata($player_id);
    $log_post_id = wp_insert_post([
        'post_type' => 'log-tugas',
        'post_title' => sprintf('Pembelian "%s" oleh %s', get_the_title($item_id), $player_data->display_name),
        'post_status' => 'private',
    ]);
    if ($log_post_id) {
        update_post_meta($log_post_id, '_user_id', $player_id);
        update_post_meta($log_post_id, '_purchased_item_id', $item_id); // Simpan ID item yang dibeli
        update_post_meta($log_post_id, '_log_type', 'purchase'); // Tandai sebagai log pembelian

        update_post_meta($log_post_id, '_purchase_cost', $price);
        update_post_meta($log_post_id, '_purchase_point_type', $point_type_slug);
    }

    // If it's a room item, we still need to save it to the owned_items meta for the "Kamar" feature
    $debug_info = []; // We will store our debug data here.

    if ($item_type === 'item-kamar') {
        $owned_items_before = get_user_meta($player_id, 'owned_items', true);
        if (!is_array($owned_items_before)) {
            $owned_items_before = [];
        }

        $debug_info['owned_before'] = $owned_items_before; // Log state before adding

        // Add the new item
        $owned_items_after = $owned_items_before;
        $owned_items_after[] = $item_id;
        $owned_items_after = array_unique($owned_items_after); // Ensure no duplicates

        $debug_info['owned_after'] = $owned_items_after; // Log state after adding

        // Attempt to update the user meta
        $update_status = update_user_meta($player_id, 'owned_items', $owned_items_after);

        $debug_info['update_status'] = $update_status; // Log the result of the update
    }

    // 5. SEND SUCCESS RESPONSE
    $response_data = [
        'message'          => 'Pembelian berhasil!',
        'new_coin_balance' => gamipress_get_user_points($player_id, 'coin'),
        'new_star_balance' => gamipress_get_user_points($player_id, 'star'),
        // 'debug_info'        => $debug_info // Include our debug log in the response
    ];
    wp_send_json_success($response_data);
}

// Hook the function to WordPress's AJAX system.
add_action('wp_ajax_handle_purchase', 'kiddoquest_handle_purchase');



/**
 * Saves the user's custom room layout.
 */
function kiddoquest_save_room_layout()
{
    if (!isset($_POST['player_id']) || !isset($_POST['layout_data'])) {
        wp_send_json_error(['message' => 'Data tidak lengkap.']);
        return;
    }

    $player_id = intval($_POST['player_id']);
    // Sanitize the layout data array
    $layout_data = array_map(function ($item) {
        return [
            'item_id' => intval($item['item_id']),
            'x' => floatval($item['x']),
            'y' => floatval($item['y']),
            'z' => intval($item['z']),
        ];
    }, $_POST['layout_data']);

    // Save the sanitized data to user meta
    update_user_meta($player_id, 'room_layout', $layout_data);

    wp_send_json_success(['message' => 'Layout disimpan.']);
}
add_action('wp_ajax_save_room_layout', 'kiddoquest_save_room_layout');

/**
 * Create a 'jurnal' post and award points after Gravity Form submission.
 * Updated to handle multiple fields from the new journal form.
 */
function kiddoquest_log_journal_after_submission($entry, $form)
{
    // Only run for our specific Journal Form (ID 1, based on your file)
    if ($form['id'] != 1) {
        return;
    }

    if (!isset($_SESSION['active_player_id'])) {
        return;
    }
    $player_id = $_SESSION['active_player_id'];

    // Prevent duplicate entries on the same day (server-side check)
    $journal_query = new WP_Query([
        'post_type' => 'jurnal',
        'author'    => $player_id,
        'date_query' => [['year' => current_time('Y'), 'month' => current_time('m'), 'day' => current_time('d')]],
    ]);
    if ($journal_query->have_posts()) {
        return;
    }

    // --- NEW: Get values and labels from all fields ---
    // The keys ('1', '3', etc.) are the Field IDs from your Gravity Form JSON.
    $fields_data = [
        '1' => rgar($entry, '1'),
        '3' => rgar($entry, '3'),
        '4' => rgar($entry, '4'),
        '5' => rgar($entry, '5'),
        '6' => rgar($entry, '6'),
    ];

    $journal_content = ''; // Start with an empty string

    // Build the formatted content for the post
    foreach ($fields_data as $field_id => $field_value) {
        if (!empty($field_value)) {
            $field_object = GFFormsModel::get_field($form, $field_id);
            $field_label = $field_object ? $field_object->label : 'Jawaban';

            // Append each question and answer to the content string
            $journal_content .= "<h3>" . esc_html($field_label) . "</h3>";
            $journal_content .= "<p>" . nl2br(esc_html($field_value)) . "</p>";
            $journal_content .= "<hr>";
        }
    }

    // Stop if the content is empty (e.g., only the required field was empty)
    if (empty($journal_content)) {
        return;
    }

    // Create the new post in the 'jurnal' CPT
    $new_post_id = wp_insert_post([
        'post_type'    => 'jurnal',
        'post_title'   => 'Jurnal oleh ' . get_the_author_meta('display_name', $player_id) . ' - ' . current_time('Y-m-d'),
        'post_content' => $journal_content, // Use the new formatted content
        'post_status'  => 'publish',
        'post_author'  => $player_id,
    ]);

    // Award points for writing in the journal
    // if ($new_post_id) {
    //     gamipress_award_points_to_user($player_id, 20, 'xp', ['log_title' => 'Menulis Jurnal']);
    //     gamipress_award_points_to_user($player_id, 20, 'coin', ['log_title' => 'Menulis Jurnal']);
    // }
}
add_action('gform_after_submission_1', 'kiddoquest_log_journal_after_submission', 10, 2); // Change _1 to your form ID


/**
 * Handles the logic for converting daily coins to permanent Gems (Permata).
 */
function kiddoquest_handle_save_to_piggy_bank()
{
    if (!isset($_SESSION['active_player_id'])) {
        wp_send_json_error(['message' => 'Player tidak aktif.']);
        return;
    }
    $player_id = $_SESSION['active_player_id'];

    $deposit_log_query = new WP_Query([
        'post_type'      => 'log-tugas',
        'posts_per_page' => 1,
        'date_query'     => [['year' => current_time('Y'), 'month' => current_time('m'), 'day' => current_time('d')]],
        'meta_query'     => [
            'relation' => 'AND',
            ['key' => '_user_id', 'value' => $player_id],
            ['key' => '_log_type', 'value' => 'piggy_bank_deposit'],
        ],
        'fields' => 'ids',
    ]);
    if ($deposit_log_query->have_posts()) {
        wp_send_json_error(['message' => 'Kamu sudah menabung hari ini!']);
        return;
    }

    $conversion_rate = 0.25; // 25%
    $current_coins = (int) gamipress_get_user_points($player_id, 'coin');

    if ($current_coins <= 0) {
        wp_send_json_error(['message' => 'Tidak ada koin untuk ditabung.']);
        return;
    }

    $coins_to_convert = floor($current_coins * $conversion_rate);

    if ($coins_to_convert <= 0) {
        wp_send_json_error(['message' => 'Jumlah koin terlalu kecil untuk ditabung.']);
        return;
    }

    // --- Start Transaction ---
    // 1. Deduct the daily coins
    gamipress_deduct_points_to_user($player_id, $coins_to_convert, 'coin', ['log_title' => 'Menabung ke Celengan']);
    // 2. Award the permanent Gems (Permata)
    gamipress_award_points_to_user($player_id, $coins_to_convert, 'permata', ['log_title' => 'Menerima dari Tabungan']);

    // 3. Create a log entry for this transaction in our custom CPT
    $player_data = get_userdata($player_id);
    $log_post_id = wp_insert_post([
        'post_type'   => 'log-tugas',
        'post_title'  => sprintf('Menabung %d Koin menjadi Permata - %s', $coins_to_convert, $player_data->display_name),
        'post_status' => 'private',
        'post_author' => $player_id,
    ]);
    if ($log_post_id) {
        update_post_meta($log_post_id, '_user_id', $player_id);
        update_post_meta($log_post_id, '_log_type', 'piggy_bank_deposit'); // New log type
        update_post_meta($log_post_id, '_coins_converted', $coins_to_convert);
        update_post_meta($log_post_id, '_gems_gained', $coins_to_convert);
    }

    // Send success response with new balances
    wp_send_json_success([
        'message'          => sprintf('%d Koin berhasil ditabung menjadi %d Permata!', $coins_to_convert, $coins_to_convert),
        'new_coin_balance' => gamipress_get_user_points($player_id, 'coin'),
        'new_gem_balance'  => gamipress_get_user_points($player_id, 'permata'),
    ]);
}
add_action('wp_ajax_save_to_piggy_bank', 'kiddoquest_handle_save_to_piggy_bank');
