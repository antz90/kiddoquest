<?php
/*
 * Template Name: Halaman Jurnal
 */

// Security check and setup
if (!isset($_SESSION['active_player_id'])) {
    wp_redirect(home_url());
    exit;
}
$player_id = $_SESSION['active_player_id'];

// Check if the user has already submitted a journal entry today.
$has_submitted_today = false;
$todays_entry_content = '';

$journal_query = new WP_Query([
    'post_type'      => 'jurnal',
    'author'         => $player_id,
    'posts_per_page' => 1,
    'date_query'     => [
        ['year' => current_time('Y'), 'month' => current_time('m'), 'day' => current_time('d')],
    ],
]);

if ($journal_query->have_posts()) {
    $has_submitted_today = true;
    while ($journal_query->have_posts()) {
        $journal_query->the_post();
        $todays_entry_content = get_the_content();
    }
    wp_reset_postdata();
}

get_header();
?>

<div class="game-world">

    <?php get_template_part('template-parts/layout/header', 'game'); ?>

    <main class="relative z-10 pt-0 pb-48 px-4 max-w-5xl mx-auto">
        <h1 class="text-4xl text-white text-center font-game mb-6" style="text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">
            Jurnal Rahasia</h1>

        <div class="max-w-2xl mx-auto bg-white/80 backdrop-blur-sm p-6 rounded-2xl shadow-lg">

            <?php if ($has_submitted_today) : ?>
                <div class="prose max-w-none">
                    <h2 class="font-game text-2xl text-purple-600 mb-2">Jurnalmu Hari Ini:</h2>
                    <blockquote><?php echo wpautop($todays_entry_content); ?></blockquote>
                    <p class="text-sm text-center text-gray-500 mt-4">Kamu hebat sudah menulis hari ini! Kembali lagi besok
                        ya.</p>
                </div>
            <?php else : ?>
                <div>
                    <h2 class="font-game text-2xl text-purple-600 mb-4 text-center">Tulis ceritamu di sini...</h2>
                    <?php
                    // Display the Gravity Form. CHANGE id=1 to your actual form ID.
                    echo do_shortcode('[gravityform id=1 title=false description=false ajax=true]');
                    ?>
                </div>
            <?php endif; ?>

        </div>

    </main>

    <?php get_template_part('template-parts/layout/footer', 'game'); ?>


</div>

<?php get_footer('game'); ?>