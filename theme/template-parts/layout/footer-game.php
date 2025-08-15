<?php

/**
 * The template for displaying the custom game footer.
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package KiddoQuest
 */

// Define the menu items in an array for easy management.
// The key is the page slug.
$game_menu_items = [
    'dashboard'  => ['label' => 'Dashboard', 'icon' => 'https://img.icons8.com/plasticine/100/home.png'],
    'toko'       => ['label' => 'Toko', 'icon' => 'https://img.icons8.com/plasticine/100/shop.png'],
    'kamar'      => ['label' => 'Kamar', 'icon' => 'https://img.icons8.com/plasticine/100/bed.png'], // Changed from Profile to Kamar
    'harta'      => ['label' => 'Harta', 'icon' => 'https://img.icons8.com/plasticine/100/treasure-chest.png'],
    'peringkat'  => ['label' => 'Peringkat', 'icon' => 'https://img.icons8.com/?size=100&id=103799&format=png&color=000000'],
    'jurnal'     => ['label' => 'Jurnal', 'icon' => 'https://img.icons8.com/plasticine/100/book.png'],
];

global $post;
$current_page_slug = $post->post_name;

?>
<div class="fixed bottom-4 left-1/2 -translate-x-1/2 w-full max-w-3xl px-4 z-50">
    <footer class="bg-black/20 backdrop-blur-sm rounded-full shadow-lg p-2">
        <nav class="flex justify-around items-center">
            <?php foreach ($game_menu_items as $slug => $item) :
                // Check if the current item is the active page
                $is_active = ($current_page_slug === $slug);
            ?>
                <a href="<?php echo esc_url(home_url('/' . $slug . '/')); ?>"
                    class="flex flex-col items-center text-center text-white w-16 transition-transform hover:scale-110 <?php if ($is_active) echo 'scale-110'; ?>"
                    title="<?php echo esc_attr($item['label']); ?>">

                    <div
                        class="w-12 h-12 rounded-full flex items-center justify-center mb-1 <?php echo $is_active ? 'bg-white/20' : 'bg-transparent'; ?>">
                        <img src="<?php echo esc_url($item['icon']); ?>" alt="<?php echo esc_attr($item['label']); ?>"
                            class="w-10 h-10 object-contain" />
                    </div>


                    <p class="font-game text-xs -mt-1" style="text-shadow: 1px 1px 2px #000;">
                        <?php echo esc_html($item['label']); ?>
                    </p>

                </a>
            <?php endforeach; ?>
        </nav>
    </footer>
</div>

<?php wp_footer(); ?>
</body>

</html>