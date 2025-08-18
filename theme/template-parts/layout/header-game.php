<?php

// --- SETUP PLAYER & GAMIPRESS DATA ---
$player_id = $_SESSION['active_player_id'];
$player_data = get_userdata($player_id);

// Get point types data from our custom helper function in functions.php
$all_point_types = kiddoquest_get_all_point_types_data();

$points_coin_data = isset($all_point_types['coin']) ? $all_point_types['coin'] : null;
$points_star_data = isset($all_point_types['star']) ? $all_point_types['star'] : null;
$points_xp_data   = isset($all_point_types['xp']) ? $all_point_types['xp'] : null;
$points_permata_data = isset($all_point_types['permata']) ? $all_point_types['permata'] : null;

// Get user balances
$coin_balance = gamipress_get_user_points($player_id, 'coin');
$star_balance = gamipress_get_user_points($player_id, 'star');
$xp_balance   = gamipress_get_user_points($player_id, 'xp');
$permata_balance = gamipress_get_user_points($player_id, 'permata');

// Get rank progress from our custom helper function in functions.php
$player_level_data = kiddoquest_get_player_custom_level_progress($xp_balance);

?>
<div class="ganti-pemain fixed top-[95px] left-4 z-40 flex flex-col gap-2 items-center">
    <a href="<?php echo esc_url(add_query_arg('action', 'ganti', home_url())); ?>"
        class="btn-game bg-purple-500 !py-3 !px-3 !rounded-full shadow-lg" title="Ganti Pemain"
        style="box-shadow: 0 4px 0 #6b21a8;">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            class="lucide lucide-users-2">
            <path d="M14 19a6 6 0 0 0-12 0" />
            <circle cx="8" cy="10" r="4" />
            <path d="M22 19a6 6 0 0 0-6-5.5" />
            <path d="M16 10h6" />
        </svg>
    </a>
    <span class="text-gray-500 text-xs font-bold">GANTI PEMAIN</span>
</div>
<?php
// Check if it's after 6 PM (18:00)
$now = (int) current_time('G'); // 'G' gives 24-hour format without leading zeros
if ($now >= 18 && is_page('dashboard')) :
?>
    <div id="piggy-bank-button-container" class="fixed top-[185px] left-7 z-40" title="Tabung Sisa Koin Harianmu!">
        <button id="piggy-bank-button" class="btn-game bg-pink-500 !py-3 !px-3 !rounded-full shadow-lg animate-pulse"
            style="box-shadow: 0 4px 0 #c53093;" onclick="saveToPiggyBank(this)">
            <img src="https://img.icons8.com/?size=100&id=43840&format=png&color=000000" class="w-8 h-8">
        </button>
    </div>
<?php endif; ?>
<header class="p-4 flex justify-between items-center sticky top-0 z-30">
    <div class="flex items-center gap-3 bg-white/80 backdrop-blur-sm p-2 pr-4 rounded-full shadow-lg">
        <img src="https://placehold.co/50x50/60A5FA/FFFFFF?text=<?php echo esc_html(strtoupper(substr($player_data->display_name, 0, 1))); ?>"
            alt="Avatar Anak" class="w-12 h-12 rounded-full border-4 border-white">
        <div>
            <p class="font-game text-blue-600 text-lg leading-tight">
                <?php echo esc_html($player_data->display_name); ?></p>
            <p class="text-xs text-gray-500 -mt-1 font-bold">
                <span id="level-name-display"><?php echo esc_html($player_level_data['level_name']); ?></span>
            </p>
        </div>
        <div class="xp-progress flex flex-col gap-1">
            <div class="w-24 bg-gray-200 rounded-full h-3">
                <div id="xp-progress-bar" class="bg-gradient-to-r from-yellow-300 to-orange-400 h-3 rounded-full"
                    style="width: <?php echo esc_attr($player_level_data['percentage']); ?>%"></div>
            </div>
            <div class="w-full text-xs text-center">
                <span id="xp-progress-text"><?php echo $xp_balance; ?> /
                    <?php echo esc_html($player_level_data['next_level']); ?></span>

            </div>
        </div>
    </div>
    <div class="flex items-center gap-2 bg-white/80 backdrop-blur-sm p-2 rounded-full shadow-lg">
        <?php if ($points_coin_data && !empty($points_coin_data['icon_url'])): ?>
            <div class="flex items-center gap-1 bg-gray-100 px-3 py-1 rounded-full"
                title="<?php echo esc_attr($points_coin_data['singular_name']); ?>">
                <img src="<?php echo esc_url($points_coin_data['icon_url']); ?>" class="w-6 h-6 object-contain" />
                <span id="coin-balance" class="font-bold text-gray-700 text-lg"><?php echo $coin_balance; ?></span>
            </div>
        <?php endif; ?>
        <?php if ($points_star_data && !empty($points_star_data['icon_url'])): ?>
            <div class="flex items-center gap-1 bg-gray-100 px-3 py-1 rounded-full"
                title="<?php echo esc_attr($points_star_data['singular_name']); ?>">
                <img src="<?php echo esc_url($points_star_data['icon_url']); ?>" class="w-6 h-6 object-contain" />
                <span id="star-balance" class="font-bold text-gray-700 text-lg"><?php echo $star_balance; ?></span>
            </div>
        <?php endif; ?>
        <?php if ($points_permata_data): ?>
            <div class="flex items-center gap-1 bg-yellow-100 px-3 py-1 rounded-full"
                title="<?php echo esc_attr($points_permata_data['singular_name']); ?>">
                <img src="<?php echo esc_url($points_permata_data['icon_url']); ?>" class="w-6 h-6 object-contain" />
                <span id="permata-balance" class="font-bold text-yellow-700 text-lg"><?php echo $permata_balance; ?></span>
            </div>
        <?php endif; ?>
    </div>
</header>