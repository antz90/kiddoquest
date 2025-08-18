<?php
/*
 * Template Name: Halaman Dashboard Anak
 */

// Security check: ensure a player is active.
if (!isset($_SESSION['active_player_id'])) {
    wp_redirect(home_url());
    exit;
}

// --- SETUP PLAYER & GAMIPRESS DATA ---
$player_id = $_SESSION['active_player_id'];
$player_data = get_userdata($player_id);

// Get point types data from our custom helper function in functions.php
$all_point_types = kiddoquest_get_all_point_types_data();

$points_coin_data = isset($all_point_types['coin']) ? $all_point_types['coin'] : null;
$points_star_data = isset($all_point_types['star']) ? $all_point_types['star'] : null;
$points_xp_data   = isset($all_point_types['xp']) ? $all_point_types['xp'] : null;

// Get user balances
$coin_balance = gamipress_get_user_points($player_id, 'coin');
$star_balance = gamipress_get_user_points($player_id, 'star');
$xp_balance   = gamipress_get_user_points($player_id, 'xp');

// Get rank progress from our custom helper function in functions.php
$player_level_data = kiddoquest_get_player_custom_level_progress($xp_balance);

get_header();
?>
<div id="game-world" class="game-world">

    <?php get_template_part('template-parts/layout/header', 'game'); ?>

    <main class="relative z-10 p-4">
        <h1 class="text-4xl text-white text-center font-game mb-6" style="text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">
            Petualangan Hari Ini</h1>
        <div class="task-path">
            <div class="path-line"></div>
            <?php
            // The fix you found: remove strtolower()
            $today = current_time('l');
            $now = current_time('H:i');

            // 2. Array of 10 colors for task icons.
            $task_colors = [
                'bg-blue-500',
                'bg-green-500',
                'bg-purple-500',
                'bg-yellow-500',
                'bg-red-500',
                'bg-indigo-500',
                'bg-pink-500',
                'bg-teal-500',
                'bg-orange-500',
                'bg-cyan-500'
            ];
            $task_index = 0; // Initialize a counter for the colors.

            if (have_rows('daftar_tugas_anak', 'user_' . $player_id)) :
                while (have_rows('daftar_tugas_anak', 'user_' . $player_id)) : the_row();
                    $scheduled_days = get_sub_field('jadwalkan_di_hari');

                    if (is_array($scheduled_days) && in_array($today, $scheduled_days)) :
                        $task_post = get_sub_field('pilih_tugas');
                        if ($task_post) :
                            $task_id = $task_post->ID;
                            $task_title = $task_post->post_title;

                            $is_completed = false;
                            $log_check_query = new WP_Query([
                                'post_type' => 'log-tugas',
                                'posts_per_page' => 1,
                                'date_query' => [['year' => current_time('Y'), 'month' => current_time('m'), 'day' => current_time('d')]],
                                'meta_query' => [
                                    'relation' => 'AND',
                                    ['key' => '_user_id', 'value' => $player_id], // Perbaikan
                                    ['key' => '_task_id', 'value' => $task_id], // Perbaikan
                                ],
                                'fields' => 'ids', // more efficient
                                'cache_results'          => false, // Jangan simpan hasil query ini di cache
                                'update_post_meta_cache' => false, // Jangan cache meta datanya juga
                            ]);
                            if ($log_check_query->have_posts()) {
                                $is_completed = true;
                            }
                            wp_reset_postdata();

                            // 1. Set default icon if ACF field is empty.
                            $icon_url = get_field('icon_tugas', $task_id);
                            if (empty($icon_url)) {
                                $icon_url = 'https://img.icons8.com/?size=100&id=54676&format=png&color=ffffff'; // Default icon (white version to match bg)
                            }

                            // Get points
                            $xp_points = (int) get_field('point_xp', $task_id);
                            $star_points = (int) get_field('poin_bintang_tugas', $task_id);

                            $coin_points = 0;
                            $is_timed_coin = false;
                            if (have_rows('point_koin_berwaktu', $task_id)) {
                                while (have_rows('point_koin_berwaktu', $task_id)) : the_row();
                                    $start_time = get_sub_field('waktu_mulai');
                                    $end_time = get_sub_field('waktu_selesai');

                                    $is_in_range = false;

                                    // Check if the time range spans across midnight (e.g., start is 21:00, end is 04:00)
                                    if ($start_time > $end_time) {
                                        // It's an overnight range. The condition is OR.
                                        if ($now >= $start_time || $now <= $end_time) {
                                            $is_in_range = true;
                                        }
                                    } else {
                                        // It's a same-day range. The condition is AND.
                                        if ($now >= $start_time && $now <= $end_time) {
                                            $is_in_range = true;
                                        }
                                    }

                                    if ($is_in_range) {
                                        $coin_points = (int) get_sub_field('jumlah_koin');
                                        $is_timed_coin = true;
                                        break;
                                    }
                                endwhile;
                            }
                            if (!$is_timed_coin) {
                                $coin_points = (int) get_field('point_koin_tugas', $task_id);
                            }

                            // Get the color for the current task using modulo.
                            $current_color = $task_colors[$task_index % count($task_colors)];

                            // 3. Logic for negative coin display.
                            $coin_color_class = 'text-green-600';
                            $coin_sign = '+';
                            if ($coin_points < 0) {
                                $coin_color_class = 'text-red-600 font-bold';
                                $coin_sign = ''; // Negative sign is already part of the number.
                            }

                            // Dynamic Layout
                            $alignment_class = ($task_index % 2 === 0) ? 'ml-14' : 'mr-14';
                            $text_align_class = ($task_index % 2 === 0) ? '' : 'text-right';
                            $position_class = ($task_index % 2 === 0) ? 'left-1/2' : 'right-1/2';
            ?>
                            <div class="task-node relative flex justify-center mb-24 <?php if ($is_completed) echo 'completed'; ?>"
                                onclick="completeTask(this)" data-task-id="<?php echo esc_attr($task_id); ?>"
                                data-player-id="<?php echo esc_attr($player_id); ?>">
                                <div
                                    class="task-icon-wrapper w-20 h-20 <?php echo $current_color; ?> rounded-full flex items-center justify-center border-4 border-white shadow-lg z-10 relative">
                                    <img src="<?php echo esc_url($icon_url); ?>" class="w-10 h-10 transition-opacity" alt="Task Icon">
                                </div>
                                <div
                                    class="absolute <?php echo $position_class; ?> <?php echo $alignment_class; ?> top-1/2 -translate-y-1/2 w-40">
                                    <div
                                        class="task-text-wrapper bg-white p-3 rounded-lg shadow-md transition-colors <?php echo $text_align_class; ?>">
                                        <p class="task-name font-bold text-gray-700"><?php echo esc_html($task_title); ?></p>
                                        <div
                                            class="task-reward text-xs flex items-center gap-2 mt-1 <?php echo ($task_index % 2 === 0) ? 'justify-start' : 'justify-end'; ?>">
                                            <?php if ($is_completed) : ?>
                                                <span class="flex items-center gap-1 text-gray-500">
                                                    SELESAI
                                                </span>
                                            <?php else : ?>
                                                <span class="flex items-center gap-1 <?php echo $coin_color_class; ?>">
                                                    <b><?php echo $coin_sign . $coin_points; ?></b>
                                                    <?php if ($points_coin_data) echo '<img src="' . esc_url($points_coin_data['icon_url']) . '" class="w-4 h-4 object-contain">'; ?>
                                                </span>
                                                <span class="flex items-center gap-1 text-gray-500">
                                                    <b>+<?php echo $star_points; ?></b>
                                                    <?php if ($points_star_data) echo '<img src="' . esc_url($points_star_data['icon_url']) . '" class="w-4 h-4 object-contain">'; ?>
                                                </span>
                                                <span class="flex items-center gap-1 text-gray-600">
                                                    <b>+<?php echo $xp_points; ?> XP</b>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    <?php
                            $task_index++; // Increment the index for the next task's color and alignment.
                        endif;
                    endif;
                endwhile;
                // Display a message if no tasks were found for today.
                if ($task_index === 0):
                    ?>
                    <div class="text-center bg-white/80 p-6 rounded-xl shadow-lg">
                        <p class="font-bold text-gray-700">Hore! Semua tugas hari ini sudah beres!</p>
                        <p class="text-sm text-gray-500">Saatnya bermain dan berpetualang!</p>
                    </div>
                <?php
                endif;
            else:
                // This 'else' is for when the repeater field itself is empty.
                ?>
                <div class="text-center bg-white/80 p-6 rounded-xl shadow-lg">
                    <p class="font-bold text-gray-700">Belum ada tugas yang diatur.</p>
                    <p class="text-sm text-gray-500">Minta orang tua untuk menambahkan tugas di profilmu!</p>
                </div>
            <?php
            endif;
            ?>
        </div>
    </main>

    <div id="page-task-complete" class="page-overlay">
        <div class="page-content text-center relative overflow-visible">
            <h2 class="font-game text-4xl text-yellow-500" style="text-shadow: 2px 2px #c2410c;">KEREN!</h2>
            <p class="text-lg mt-2 text-gray-700">Kamu menyelesaikan tugas:</p>
            <p id="completed-task-name" class="font-bold text-xl text-blue-600"></p>

            <img id="reward-sticker-img" src="" alt="Stiker Hadiah" class="w-32 h-32 mx-auto my-4 animate-bounce">
            <p class="text-sm text-gray-500">Kamu dapat stiker baru!</p>

            <div class="mt-4 bg-green-100 p-3 rounded-lg">
                <p class="font-bold text-green-700 text-lg">Hadiah: <span id="completed-task-reward"></span></p>
            </div>

            <button onclick="closeAllPages()" class="btn-game mt-6">Lanjut!</button>
        </div>
    </div>

    <?php get_template_part('template-parts/layout/footer', 'game'); ?>
</div>


<?php wp_enqueue_script('jquery'); ?>
<script>
    // --- JavaScript for Interactivity ---
    // Make sure you have these helper functions copied from ui-template.html
    const pages = ['rewards', 'leaderboard', 'awards', 'journal', 'profile', 'task-complete'];

    function openPage(pageId) {
        closeAllPages();
        const targetPage = document.getElementById(`page-${pageId}`);
        if (targetPage) {
            targetPage.classList.add('active');
        }
    }

    function closeAllPages() {
        pages.forEach(id => {
            const pageElement = document.getElementById(`page-${id}`);
            if (pageElement) {
                pageElement.classList.remove('active');
            }
        });
    }

    // Main function to handle task completion
    function completeTask(taskNodeElement) {
        if (taskNodeElement.classList.contains('completed') || taskNodeElement.classList.contains('processing')) {
            return;
        }
        taskNodeElement.classList.add('processing');

        const taskId = taskNodeElement.dataset.taskId;
        const playerId = taskNodeElement.dataset.playerId;

        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'handle_task_completion',
                task_id: taskId,
                player_id: playerId,
                nonce: '<?php echo wp_create_nonce('complete_task_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    taskNodeElement.classList.remove('processing');
                    taskNodeElement.classList.add('completed');

                    // --- FIX FOR REALTIME POINT UPDATE ---
                    // 1. Update Coin & Star balances
                    document.getElementById('coin-balance').textContent = response.data.new_coin_balance;
                    document.getElementById('star-balance').textContent = response.data.new_star_balance;

                    // 2. Update Level Name (in case of level up)
                    document.getElementById('level-name-display').textContent = response.data.new_level_info
                        .level_name;

                    // 3. Update XP Progress Bar
                    document.getElementById('xp-progress-bar').style.width = response.data.new_level_info
                        .percentage + '%';

                    // 4. Update XP Text (e.g., "1050 / 2000")
                    document.getElementById('xp-progress-text').textContent = response.data.new_xp_balance +
                        ' / ' + response.data.new_level_info.next_level;

                    // Populate the popup with data from the server
                    document.getElementById('completed-task-name').innerHTML = response.data.task_title;
                    document.getElementById('completed-task-reward').innerHTML = response.data.reward_text;
                    document.getElementById('reward-sticker-img').src = response.data.sticker_url;

                    // --- FIX FOR POPUP ---
                    // This function will now show the popup.
                    openPage('task-complete');

                } else {
                    alert('Oops! ' + response.data.message);
                    taskNodeElement.classList.remove('processing');
                }
            },
            error: function() {
                alert('Oops! Gagal menghubungi server.');
                taskNodeElement.classList.remove('processing');
            }
        });
    }


    function saveToPiggyBank(buttonElement) {
        if (!confirm('Yakin mau menabung 25% dari sisa koinmu menjadi Permata permanen? Koin harianmu akan berkurang.')) {
            return;
        }

        // Provide feedback
        const container = document.getElementById('piggy-bank-button-container');
        buttonElement.disabled = true;
        buttonElement.innerHTML = '...';

        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'save_to_piggy_bank',
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    // Update balances in real-time
                    document.getElementById('coin-balance').textContent = response.data.new_coin_balance;
                    document.getElementById('permata-balance').textContent = response.data.new_gem_balance;

                    // Hide the button after successful saving to prevent multiple uses
                    container.style.display = 'none';
                } else {
                    alert('Gagal! ' + response.data.message);
                    buttonElement.disabled = false;
                    buttonElement.innerHTML =
                        '<img src="https://img.icons8.com/?size=100&id=43840&format=png&color=000000" class="w-8 h-8">';
                }
            },
            error: function() {
                alert('Oops! Gagal menghubungi server.');
                buttonElement.disabled = false;
                buttonElement.innerHTML =
                    '<img src="https://img.icons8.com/?size=100&id=43840&format=png&color=000000" class="w-8 h-8">';
            }
        });
    }
</script>

<?php
get_footer();
?>