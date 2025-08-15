<?php
/*
 * Template Name: Halaman Pilih Pemain
 * Description: Homepage for logged-in users to select their active character.
 */

// Check if an active player is already in the session.
$active_player_id = isset($_SESSION['active_player_id']) ? $_SESSION['active_player_id'] : null;

// Logic to handle form submission when the "Mulai" button is clicked.
if (isset($_POST['selected_player_id']) && is_numeric($_POST['selected_player_id'])) {
    // Set the new player ID into the session.
    $_SESSION['active_player_id'] = intval($_POST['selected_player_id']);

    // Redirect to the dashboard to start the game.
    wp_redirect(home_url('/dashboard'));
    exit;
}

// Logic to handle "Ganti Player". This simply unsets the session variable.
if (isset($_GET['action']) && $_GET['action'] === 'ganti') {
    unset($_SESSION['active_player_id']);
    wp_redirect(home_url()); // Redirect back to this page to refresh the state.
    exit;
}

get_header();
?>

<?php if ($active_player_id) : ?>
    <div class="absolute top-4 right-4 z-10">
        <a href="<?php echo esc_url(add_query_arg('action', 'ganti', home_url())); ?>"
            class="btn-game bg-red-500 !py-2 !px-4 text-sm" style="box-shadow: 0 4px 0 #c82333;">
            Ganti Jagoan
        </a>
    </div>
<?php endif; ?>

<div id="character-view" class="flex items-center justify-center min-h-screen">
    <div class="character-selection-container text-center">
        <h1 class="font-game text-5xl text-blue-600" style="text-shadow: 2px 2px #fff;">MyKiddo Quest</h1>
        <h2 class="font-game text-3xl text-orange-500 mt-4 mb-6">
            <?php echo $active_player_id ? 'Jagoanmu Sudah Siap!' : 'Pilih Jagoanmu!'; ?>
        </h2>

        <form method="POST">
            <div class="grid grid-cols-2 gap-4">
                <?php
                // Get all users with the 'subscriber' role (the children).
                $kids = get_users(array('role' => 'subscriber'));

                foreach ($kids as $kid) :
                    // --- LOGIC TO ADD SELECTION INDICATOR ---
                    // Check if the current kid in the loop is the active one from the session.
                    $is_selected = ($kid->ID == $active_player_id);
                    $selected_class = $is_selected ? 'selected' : '';
                    $kids_image = get_field('gambar_karakter', 'user_' . $kid->ID)
                ?>
                    <label class="character-card <?php echo $selected_class; ?>" onclick="selectCharacter(this)">
                        <input type="radio" name="selected_player_id" value="<?php echo esc_attr($kid->ID); ?>"
                            class="hidden" <?php checked($is_selected); ?>>

                        <img src="<?php echo $kids_image; ?>" al t="Karakter <?php echo esc_attr($kid->display_name); ?>"
                            class="mx-auto h-48 object-contain">
                        <p class="font-game text-xl mt-2 text-gray-800"><?php echo esc_html($kid->display_name); ?></p>
                    </label>
                <?php endforeach; ?>
            </div>

            <button id="start-button" type="submit" class="btn-game w-full !mt-8 bg-gray-400 cursor-not-allowed"
                disabled>
                Mulai Petualangan
            </button>
        </form>
    </div>
</div>

<script>
    const startButton = document.getElementById('start-button');
    const characterCards = document.querySelectorAll('.character-card');

    // Function to visually select a character card.
    function selectCharacter(selectedCardElement) {
        // Remove 'selected' class from all other cards.
        characterCards.forEach(card => {
            card.classList.remove('selected');
        });

        // Add 'selected' class to the clicked card.
        selectedCardElement.classList.add('selected');

        // Ensure the hidden radio button inside it is checked.
        selectedCardElement.querySelector('input[type="radio"]').checked = true;

        // Enable the start button.
        enableStartButton();
    }

    // Function to enable the button.
    function enableStartButton() {
        startButton.disabled = false;
        startButton.classList.remove('bg-gray-400', 'cursor-not-allowed');
        startButton.classList.add('bg-green-500');
        startButton.style.boxShadow = '0 5px 0 #15803d';
    }

    // On page load, check if a character is already pre-selected.
    document.addEventListener('DOMContentLoaded', function() {
        const alreadySelected = document.querySelector('.character-card.selected');
        if (alreadySelected) {
            // If yes, enable the button right away.
            enableStartButton();
        }
    });
</script>