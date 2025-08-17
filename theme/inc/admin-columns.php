<?php
// This file handles customizations for the WordPress admin columns.

/**
 * Adds and reorders the columns for the 'log-tugas' CPT list table.
 */
function kiddoquest_set_log_tugas_columns($columns)
{
    // We create a new, ordered array of columns.
    $new_columns = [
        'cb'         => $columns['cb'], // The checkbox
        'log_type'   => __('Tipe Log', 'kiddoquest'),
        'player'     => __('Anak', 'kiddoquest'),
        'details'    => __('Detail Aksi', 'kiddoquest'),
        'points'     => __('Perolehan Poin', 'kiddoquest'),
        'date'       => $columns['date'], // The original date column
    ];
    return $new_columns;
}
add_filter('manage_log-tugas_posts_columns', 'kiddoquest_set_log_tugas_columns');


/**
 * Populates the content for our custom columns.
 */
function kiddoquest_log_tugas_custom_column_content($column, $post_id)
{
    // We use a switch statement to handle each custom column.
    switch ($column) {

        case 'player':
            $user_id = get_post_meta($post_id, '_user_id', true);
            if ($user_id) {
                $user_data = get_userdata($user_id);
                echo esc_html($user_data->display_name);
            } else {
                echo 'N/A';
            }
            break;

        case 'log_type':
            $log_type = get_post_meta($post_id, '_log_type', true);
            if ($log_type === 'purchase') {
                echo '🛍️ Pembelian';
            } elseif ($log_type === 'coin_summary') {
                echo '💰 Laporan Koin';
            } elseif ($log_type === 'star_summary') {
                echo '⭐ Laporan Bintang';
            } else {
                echo '✅ Tugas Selesai';
            }
            break;

        case 'details':
            $task_id = get_post_meta($post_id, '_task_id', true);
            $item_id = get_post_meta($post_id, '_purchased_item_id', true);

            if ($task_id) {
                echo '<strong>Tugas:</strong> ' . esc_html(get_the_title($task_id));
            } elseif ($item_id) {
                echo '<strong>Item:</strong> ' . esc_html(get_the_title($item_id));
            } else {
                // For summary logs, the detail is in the points column.
                echo '<em>Laporan Harian/Bulanan</em>';
            }
            break;

        case 'points':
            $log_type = get_post_meta($post_id, '_log_type', true);

            if ($log_type === 'coin_summary') {
                $total = get_post_meta($post_id, '_total_coins_earned', true);
                echo "<strong>Total Harian:</strong> " . $total . ' 💰';
            } elseif ($log_type === 'star_summary') {
                $total = get_post_meta($post_id, '_star_balance_before_reset', true);
                echo "<strong>Total Bulanan:</strong> " . $total . ' ⭐';
            } elseif ($log_type === 'purchase') {
                // To show this, we need to save the purchase cost first. See Step 3.
                $cost = get_post_meta($post_id, '_purchase_cost', true);
                $point_type = get_post_meta($post_id, '_purchase_point_type', true);
                $icon = ($point_type === 'coin') ? '💰' : '⭐';
                echo "<strong>Biaya:</strong> -" . $cost . ' ' . $icon;
            } else { // Task completion
                $coins = (int) get_post_meta($post_id, '_points_awarded_coin', true);
                $stars = (int) get_post_meta($post_id, '_points_awarded_star', true);
                $xp = (int) get_post_meta($post_id, '_points_awarded_xp', true);

                $output = [];
                if ($coins != 0) $output[] = ($coins > 0 ? '+' : '') . $coins . ' 💰';
                if ($stars != 0) $output[] = '+' . $stars . ' ⭐';
                if ($xp != 0) $output[] = '+' . $xp . ' ✨';
                echo implode('<br>', $output);
            }
            break;
    }
}
add_action('manage_log-tugas_posts_custom_column', 'kiddoquest_log_tugas_custom_column_content', 10, 2);


/**
 * Adds a "Reset Stickers" button to the user profile edit page in the admin dashboard.
 * This will only show for users with the 'subscriber' role.
 *
 * @param WP_User $user The user object being edited.
 */
function kiddoquest_add_reset_stickers_button($user)
{
    // Only show this section for our 'players' (subscribers)
    if (!in_array('subscriber', $user->roles)) {
        return;
    }

    // Security field
    wp_nonce_field('kiddoquest_reset_stickers_nonce', 'reset_stickers_nonce_field');
?>
    <hr>
    <h2>Opsi Tambahan MyKiddo Quest</h2>
    <table class="form-table">
        <tr>
            <th><label for="reset_stickers">Reset Koleksi Stiker</label></th>
            <td>
                <label for="reset_stickers_confirm">
                    <input type="checkbox" name="reset_stickers_confirm" id="reset_stickers_confirm" value="yes">
                    Ya, saya mau menghapus semua data koleksi stiker untuk anak ini.
                </label>
                <p class="description">
                    <strong>Perhatian:</strong> Aksi ini tidak bisa diurungkan. Semua stiker yang telah dikumpulkan oleh
                    anak ini akan direset menjadi nol.
                </p>
            </td>
        </tr>
    </table>
<?php
}
add_action('edit_user_profile', 'kiddoquest_add_reset_stickers_button');


/**
 * Handles the logic when the "Reset Stickers" checkbox is checked and the user profile is updated.
 *
 * @param int $user_id The ID of the user being updated.
 */
function kiddoquest_handle_reset_stickers($user_id)
{
    // Security checks: user can edit this profile, nonce is valid, and the checkbox is checked.
    if (!current_user_can('edit_user', $user_id) || !isset($_POST['reset_stickers_nonce_field']) || !wp_verify_nonce($_POST['reset_stickers_nonce_field'], 'kiddoquest_reset_stickers_nonce') || !isset($_POST['reset_stickers_confirm']) || $_POST['reset_stickers_confirm'] !== 'yes') {
        return;
    }

    // Get all meta data for the user.
    $all_meta = get_user_meta($user_id);
    $sticker_meta_prefix = KIDDOQUEST_PREFIX . '_sticker_count_';

    // Loop through all meta keys.
    foreach ($all_meta as $meta_key => $meta_value) {
        // If a meta key starts with our sticker prefix...
        if (strpos($meta_key, $sticker_meta_prefix) === 0) {
            // ...delete it.
            delete_user_meta($user_id, $meta_key);
        }
    }
}
add_action('edit_user_profile_update', 'kiddoquest_handle_reset_stickers');
