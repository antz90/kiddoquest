<?php
/*
 * Template Name: Halaman Harta Karun
 */

// Security check and setup
if (!isset($_SESSION['active_player_id'])) {
    wp_redirect(home_url());
    exit;
}
$player_id = $_SESSION['active_player_id'];

get_header();
?>

<div class="game-world">

    <?php get_template_part('template-parts/layout/header', 'game'); ?>

    <main class="relative z-10 p-4">
        <h1 class="text-4xl text-white text-center font-game mb-6" style="text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">
            Harta Karun</h1>

        <div class="max-w-4xl mx-auto bg-white/80 backdrop-blur-sm p-4 sm:p-6 rounded-2xl shadow-lg">
            <h2 class="font-game text-3xl text-green-700 mb-4">Koleksi Stiker</h2>

            <div class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-8 gap-4">
                <?php
                // 1. Get a master list of ALL available stickers from the CPT.
                $all_stickers_query = new WP_Query([
                    'post_type'      => 'stiker',
                    'posts_per_page' => -1,
                    'orderby'        => 'title',
                    'order'          => 'ASC',
                ]);

                if ($all_stickers_query->have_posts()) : while ($all_stickers_query->have_posts()) : $all_stickers_query->the_post();
                        $sticker_id = get_the_ID();
                        $sticker_url = get_field('stiker_url');

                        // 2. For each sticker, check how many the active player owns.
                        $meta_key = KIDDOQUEST_PREFIX . '_sticker_count_' . $sticker_id;
                        $owned_count = (int) get_user_meta($player_id, $meta_key, true);
                ?>
                        <div class="relative bg-white/90 rounded-lg aspect-square p-2 flex flex-col items-center justify-center"
                            title="<?php the_title(); ?>">
                            <img src="<?php echo esc_url($sticker_url); ?>" class="max-w-full max-h-full transition-all duration-300 
                                    <?php
                                    // 3. Apply grayscale effect if the user doesn't own the sticker.
                                    if ($owned_count === 0) {
                                        echo 'filter grayscale opacity-50';
                                    }
                                    ?>" alt="<?php the_title(); ?>">

                            <?php
                            // 4. If owned, display the count badge.
                            if ($owned_count > 0) : ?>
                                <div class="text-base font-bold w-6 h-6 rounded-full flex items-center justify-center">
                                    <?php echo 'x' . $owned_count; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile;
                    wp_reset_postdata();
                else: ?>
                    <p class="col-span-full text-center text-gray-600">Belum ada stiker yang tersedia di game ini.</p>
                <?php endif; ?>
            </div>
        </div>

    </main>
    <?php get_template_part('template-parts/layout/footer', 'game'); ?>


</div>

<?php get_footer(''); ?>