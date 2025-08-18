<?php
/*
 * Template Name: Halaman Toko
 */

// Security check: ensure a player is active.
if (!isset($_SESSION['active_player_id'])) {
    wp_redirect(home_url());
    exit;
}

// --- SETUP PLAYER & GAMIPRESS DATA ---
$player_id = $_SESSION['active_player_id'];
$player_data = get_userdata($player_id);

// Get current point balances and helper data
$coin_balance = (int) gamipress_get_user_points($player_id, 'coin');
$star_balance = (int) gamipress_get_user_points($player_id, 'star');
$all_point_types = kiddoquest_get_all_point_types_data();
$points_coin_data = isset($all_point_types['coin']) ? $all_point_types['coin'] : null;
$points_star_data = isset($all_point_types['star']) ? $all_point_types['star'] : null;
$now = current_time('H:i'); // Get current time once for efficiency

get_header();
?>

<div id="game-world" class="game-world">

    <?php get_template_part('template-parts/layout/header', 'game'); ?>

    <main class="relative z-10 pt-0 pb-48 px-4 max-w-5xl mx-auto">
        <h1 class="text-4xl text-white text-center font-game mb-6" style="text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">
            Toko Hadiah</h1>

        <section id="hadiah" class="mb-12">
            <h2 class="font-game text-3xl text-white mb-4" style="text-shadow: 1px 1px 2px #000;">Hadiah Spesial</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php
                $total_potential_coins = kiddoquest_get_potential_coins($player_id, 'pagi_siang');
                $reward_query_args = [
                    'post_type' => 'hadiah',
                    'posts_per_page' => -1,
                    'meta_query' => [
                        'relation' => 'OR', // Show if one of these is true
                        [
                            // Condition 1: The 'assigned users' field is NOT SET or EMPTY
                            // This means it's a public reward for everyone.
                            'key' => 'hadiah_untuk_user',
                            'compare' => 'NOT EXISTS',
                        ],
                        [
                            // Condition 2: The 'assigned users' field CONTAINS the current player's ID
                            'key' => 'hadiah_untuk_user',
                            'value' => '"' . $player_id . '"', // Must be quoted for serialized array search
                            'compare' => 'LIKE',
                        ],
                    ],
                ];
                $reward_query = new WP_Query($reward_query_args);
                $today_date = current_time('Y-m-d');

                if ($reward_query->have_posts()) : while ($reward_query->have_posts()) : $reward_query->the_post();
                        $reward_id = get_the_ID();

                        // --- HYBRID PRICING LOGIC ---
                        $price_type = get_field('tipe_harga');
                        $final_price = 0;
                        $point_type = '';

                        if ($price_type === 'dinamis') {
                            $price_percentage = (int) get_field('persentase_harga_koin');
                            $final_price = floor(($price_percentage / 100) * $total_potential_coins);
                            $point_type = 'coin';
                        } else { // 'statis'
                            $final_price = (int) get_field('harga_statis');
                            $point_type = get_field('jenis_point_statis');
                        }

                        $current_balance = ($point_type === 'coin') ? $coin_balance : $star_balance;
                        $point_icon = ($point_type === 'coin') ? $points_coin_data['icon_url'] : $points_star_data['icon_url'];
                        $can_afford = $current_balance >= $final_price;

                        // REVISED: Time restriction logic
                        $is_time_limited = get_field('terbatas_jam');
                        $is_in_time_window = true; // Assume available by default
                        $time_message = '';

                        if ($is_time_limited) {
                            $start_time_str = get_field('jam_mulai'); // e.g., "06:00"
                            $end_time_str = get_field('jam_selesai');   // e.g., "07:00"

                            // Convert all times to timestamps (integers) for reliable comparison
                            $now_ts = strtotime($now);
                            $start_ts = strtotime($start_time_str);
                            $end_ts = strtotime($end_time_str);

                            // Default to not being in the window if time is limited
                            $is_in_time_window = false;

                            // Check for overnight range (e.g., 21:00 to 04:00)
                            if ($start_ts > $end_ts) {
                                if ($now_ts >= $start_ts || $now_ts <= $end_ts) {
                                    $is_in_time_window = true;
                                }
                            } else {
                                // Same-day range
                                if ($now_ts >= $start_ts && $now_ts <= $end_ts) {
                                    $is_in_time_window = true;
                                }
                            }

                            // If not in window, prepare the message
                            if (!$is_in_time_window) {
                                $time_message = "(Jam " . date("H:i", $start_ts) . ' - ' .  date("H:i", $end_ts) . ')';
                            }
                        }

                        // --- VALIDASI BARU DI FRONTEND DENGAN 'log-tugas'---
                        $already_bought_today = false;
                        $purchase_log_check = new WP_Query([
                            'post_type' => 'log-tugas',
                            'posts_per_page' => 1,
                            'date_query' => [['year' => current_time('Y'), 'month' => current_time('m'), 'day' => current_time('d')]],
                            'meta_query' => [
                                'relation' => 'AND',
                                ['key' => '_user_id', 'value' => $player_id],
                                ['key' => '_purchased_item_id', 'value' => $reward_id],
                            ],
                            'fields' => 'ids',
                        ]);
                        if ($purchase_log_check->have_posts()) {
                            $already_bought_today = true;
                        }

                        $is_buyable = $can_afford && $is_in_time_window && !$already_bought_today;
                ?>
                        <div class="bg-white/80 backdrop-blur-sm p-4 rounded-xl shadow-lg text-center flex flex-col">
                            <?php the_post_thumbnail('medium', ['class' => 'w-full h-32 object-contain mb-2 rounded']); ?>
                            <h3 class="font-bold text-gray-800 flex-grow"><?php the_title(); ?></h3>
                            <div class="mt-2">
                                <button
                                    class="btn-game w-full text-sm <?php if (!$is_buyable) echo 'bg-gray-400 cursor-not-allowed'; ?>"
                                    <?php if (!$is_buyable) echo 'disabled'; ?> onclick="purchaseItem(this)"
                                    data-id="<?php echo $reward_id; ?>" data-type="hadiah">
                                    <span class="flex items-center justify-center gap-1 flex-col">
                                        <?php if ($already_bought_today) : ?>
                                            <span class="text-xs">Sudah Dibeli</span>
                                        <?php elseif (!$is_in_time_window) : ?>
                                            <span class="flex gap-1">
                                                <?php echo $final_price; ?>
                                                <img src="<?php echo esc_url($point_icon); ?>" class="w-5 h-5">
                                            </span>
                                            <span class="text-xs"><?php echo $time_message; ?></span>
                                        <?php else: ?>
                                            <span class="flex gap-1">
                                                <?php echo $final_price; ?>
                                                <img src="<?php echo esc_url($point_icon); ?>" class="w-5 h-5">
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                </button>
                            </div>
                        </div>
                    <?php endwhile;
                    wp_reset_postdata();
                else: ?>
                    <p class="text-white col-span-full">Hadiah spesial akan segera hadir!</p>
                <?php endif; ?>
            </div>
        </section>

        <section id="item-kamar">
            <h2 class="font-game text-3xl text-white mb-4" style="text-shadow: 1px 1px 2px #000;">Dekorasi Kamar</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php
                // 1. Get the active player's gender.
                $player_gender = get_field('gender_anak', 'user_' . $player_id); // 'cowok' or 'cewek'

                // 2. Build the query arguments.
                $item_query_args = [
                    'post_type' => 'item-kamar',
                    'posts_per_page' => -1,
                    'tax_query' => [
                        'relation' => 'OR', // Show item if it's for their gender OR if it's unisex.
                        [
                            'taxonomy' => 'gender-item',
                            'field'    => 'slug',
                            'terms'    => $player_gender, // e.g., 'cowok'
                        ],
                        [
                            'taxonomy' => 'gender-item',
                            'field'    => 'slug',
                            'terms'    => 'unisex',
                        ],
                    ],
                ];
                $item_query = new WP_Query($item_query_args);

                $total_potential_coins = kiddoquest_get_potential_coins($player_id, 'full_day');
                $owned_items = get_user_meta($player_id, 'owned_items', true);
                if (!is_array($owned_items)) $owned_items = [];

                if ($item_query->have_posts()) : while ($item_query->have_posts()) : $item_query->the_post();
                        $item_id = get_the_ID();
                        $price = (int) get_field('harga_item_kamar');
                        $is_owned = in_array($item_id, $owned_items);

                        // --- ADVANCED DYNAMIC PRICING LOGIC ---
                        $price_type = get_field('tipe_harga_item');
                        $final_price = 0;
                        $price_text = '';

                        if ($price_type === 'dinamis_adv') {
                            $percentage = (int) get_field('persentase_harga_item');
                            $deduction = (int) get_field('pengurangan_harga_item');
                            // Calculate the price with the new formula
                            $calculated_price = floor((($percentage / 100) * $total_potential_coins) - $deduction);
                            // Ensure price is never below a minimum (e.g., 1)
                            $final_price = max(1, $calculated_price);
                            $price_text = "($percentage% - $deduction)";
                        } else { // 'statis'
                            $final_price = (int) get_field('harga_item_statis');
                        }

                        $can_afford = $coin_balance >= $final_price;
                ?>
                        <div class="bg-white/80 backdrop-blur-sm p-4 rounded-xl shadow-lg text-center flex flex-col">
                            <?php the_post_thumbnail('medium', ['class' => 'w-full h-32 object-contain mb-2 rounded']); ?>
                            <h3 class="font-bold text-gray-800 flex-grow"><?php the_title(); ?></h3>
                            <div class="mt-2">
                                <?php if ($is_owned) : ?>
                                    <button class="btn-game w-full bg-gray-400 cursor-not-allowed text-sm"
                                        disabled>Dimiliki</button>
                                <?php else : ?>
                                    <button
                                        class="btn-game w-full text-sm <?php if (!$can_afford) echo 'bg-gray-400 cursor-not-allowed'; ?>"
                                        <?php if (!$can_afford) echo 'disabled'; ?> onclick="purchaseItem(this)"
                                        data-id="<?php echo $item_id; ?>" data-type="item-kamar">
                                        <span class="flex items-center justify-center gap-1">
                                            <?php echo $final_price; ?> <img
                                                src="<?php echo esc_url($points_coin_data['icon_url']); ?>" class="w-5 h-5">
                                        </span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile;
                    wp_reset_postdata();
                else: ?>
                    <p class="text-white col-span-full">Item dekorasi akan segera hadir!</p>
                <?php endif; ?>
            </div>
        </section>

    </main>
    <?php get_template_part('template-parts/layout/footer', 'game'); ?>
</div>

<?php wp_enqueue_script('jquery'); ?>
<script>
    function purchaseItem(buttonElement) {
        const itemId = buttonElement.dataset.id;
        const itemType = buttonElement.dataset.type;
        const originalButtonText = buttonElement.innerHTML;

        // Provide feedback to the user
        buttonElement.innerHTML = 'Memproses...';
        buttonElement.disabled = true;

        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'handle_purchase',
                item_id: itemId,
                item_type: itemType,
                // nonce: '...' // You can add a nonce here for extra security
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message); // Show success message

                    // Update point displays in the header in real-time
                    // Note: This requires the header to be on this page.
                    if (document.getElementById('coin-balance')) {
                        document.getElementById('coin-balance').textContent = response.data.new_coin_balance;
                    }
                    if (document.getElementById('star-balance')) {
                        document.getElementById('star-balance').textContent = response.data.new_star_balance;
                    }

                    // The simplest way to update the button status (e.g., to "Owned" or disabled)
                    // is to reload the page.
                    location.reload();

                } else {
                    alert('Gagal! ' + response.data.message);
                    buttonElement.innerHTML = originalButtonText; // Restore button text
                    buttonElement.disabled = false; // Re-enable button
                }
            },
            error: function() {
                alert('Oops! Gagal menghubungi server.');
                buttonElement.innerHTML = originalButtonText;
                buttonElement.disabled = false;
            }
        });
    }
</script>

<?php get_footer(); ?>