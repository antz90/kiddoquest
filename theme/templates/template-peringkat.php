<?php
/*
 * Template Name: Halaman Peringkat
 */

// Security check and setup
if (!isset($_SESSION['active_player_id'])) {
    wp_redirect(home_url());
    exit;
}

get_header();
?>

<div class="game-world" style="height: auto; min-height: 100vh;">

    <?php get_template_part('template-parts/layout/header', 'game'); ?>

    <main class="relative z-10 pt-0 pb-48 px-4 max-w-5xl mx-auto">
        <h1 class="text-4xl text-white text-center font-game mb-6" style="text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">
            Papan Peringkat</h1>

        <div class="max-w-2xl mx-auto space-y-3">
            <?php
            // The key is this WP_User_Query arguments array.
            // We get all users with the 'subscriber' role...
            // ... and sort them by the '_gamipress_xp_points' meta key in descending order.
            $args = [
                'role'       => 'subscriber',
                'meta_key'   => '_gamipress_xp_points',
                'orderby'    => 'meta_value_num',
                'order'      => 'DESC',
            ];
            $user_query = new WP_User_Query($args);
            $users = $user_query->get_results();

            if (!empty($users)) :
                $rank = 1; // Start the rank counter
                foreach ($users as $user) :
                    // Get the user's XP
                    $xp_points = gamipress_get_user_points($user->ID, 'xp');

                    // Determine styling based on rank
                    $bg_color = 'bg-white';
                    $border_color = 'border-gray-200';
                    $text_color = 'text-gray-700';
                    $rank_color = 'text-gray-500';

                    if ($rank === 1) { // Gold for 1st place
                        $bg_color = 'bg-yellow-100';
                        $border_color = 'border-yellow-300';
                        $text_color = 'text-yellow-800';
                        $rank_color = 'text-yellow-600';
                    } elseif ($rank === 2) { // Silver for 2nd place
                        $bg_color = 'bg-gray-200';
                        $border_color = 'border-gray-400';
                        $text_color = 'text-gray-800';
                        $rank_color = 'text-gray-600';
                    } elseif ($rank === 3) { // Bronze for 3rd place
                        $bg_color = 'bg-orange-100';
                        $border_color = 'border-orange-300';
                        $text_color = 'text-orange-800';
                        $rank_color = 'text-orange-600';
                    }

            ?>
                    <div
                        class="flex items-center <?php echo $bg_color; ?> p-3 rounded-full border-4 <?php echo $border_color; ?> shadow">
                        <span class="font-game text-2xl <?php echo $rank_color; ?> w-10 text-center"><?php echo $rank; ?></span>
                        <img src="https://placehold.co/40x40/<?php echo dechex(rand(0, 16777215)); ?>/FFFFFF?text=<?php echo esc_html(strtoupper(substr($user->display_name, 0, 1))); ?>"
                            alt="Avatar" class="w-10 h-10 rounded-full">
                        <p class="ml-4 font-bold <?php echo $text_color; ?> flex-grow">
                            <?php echo esc_html($user->display_name); ?></p>
                        <div class="flex items-center gap-1 font-game text-lg <?php echo $text_color; ?>">
                            <?php echo $xp_points; ?> ✨
                        </div>
                    </div>
                <?php
                    $rank++; // Increment rank for the next user
                endforeach;
            else :
                ?>
                <p class="text-center text-white bg-black/20 rounded-lg p-4">Belum ada pemain di papan peringkat.</p>
            <?php endif; ?>
        </div>

    </main>

    <?php get_template_part('template-parts/layout/footer', 'game'); ?>


</div>

<?php get_footer(); ?>