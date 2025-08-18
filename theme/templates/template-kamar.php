<?php
/*
 * Template Name: Halaman Kamar
 */

if (!isset($_SESSION['active_player_id'])) {
    wp_redirect(home_url());
    exit;
}
$player_id = $_SESSION['active_player_id'];

// Get all data needed for the room
$bg_url = get_field('background_kamar', 'user_' . $player_id);
$char_url = get_field('gambar_karakter', 'user_' . $player_id);

// --- FIX PART 1: Clean up the owned items list first ---
$owned_item_ids_raw = get_user_meta($player_id, 'owned_items', true);
if (!is_array($owned_item_ids_raw)) $owned_item_ids_raw = [];

// Filter out any items that may have been deleted from the CPT
$valid_owned_item_ids = [];
foreach ($owned_item_ids_raw as $item_id) {
    if (get_post_status($item_id) === 'publish') {
        $valid_owned_item_ids[] = $item_id;
    }
}

// Get the list of items currently placed in the room
$placed_items_layout = get_user_meta($player_id, 'room_layout', true);
if (!is_array($placed_items_layout)) $placed_items_layout = [];

// Calculate which items are "in the closet" using the cleaned list
$placed_item_ids = wp_list_pluck($placed_items_layout, 'item_id');
$stowed_item_ids = array_diff($valid_owned_item_ids, $placed_item_ids);

get_header();
?>
<script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
<style>
    /* Basic styling for the room and items */
    #kamar-area {
        width: 100%;
        height: 75vh;
        border: 4px solid #8d5b4c;
        border-radius: 1rem;
        background-size: cover;
        background-position: center;
        position: relative;
        overflow: hidden;
    }

    .placed-item,
    #karakter-di-kamar {
        position: absolute;
        touch-action: none;
        user-select: none;
        /* Prevent items from being too big */
    }

    .placed-item {
        max-width: 64px;
    }

    .placed-item:hover {
        border: 2px dashed #fca311;
        cursor: move;
    }

    #inventaris {
        background: rgba(0, 0, 0, 0.2);
        backdrop-filter: blur(5px);
    }

    .inventory-item {
        cursor: grab;
    }
</style>

<div class="game-world" style="height: auto; min-height: 100vh;">

    <?php get_template_part('template-parts/layout/header', 'game'); ?>

    <main class="relative z-10 pt-4 pb-52 px-4">
        <div class="flex justify-between items-center mb-4">
            <span class="kosong"></span>
            <h1 class="text-4xl text-white text-center font-game" style="text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">
                Dekorasi Kamar
            </h1>
            <div class="flex gap-2">
                <button id="reset-layout-button" class="btn-game bg-red-500">Bereskan Kamar</button>
                <button id="save-layout-button" class="btn-game bg-green-500">Simpan Tampilan</button>
            </div>
        </div>

        <div class="flex flex-col md:flex-row gap-4">
            <div id="kamar-area" class="flex-grow" style="background-image: url('<?php echo esc_url($bg_url); ?>');">
                <?php if ($char_url): ?>
                    <img src="<?php echo esc_url($char_url); ?>" id="karakter-di-kamar"
                        class="h-full w-auto max-h-[450px] bottom-0 left-4" alt="Karakter">
                <?php endif; ?>

                <?php foreach ($placed_items_layout as $item) :
                    if (get_post_status($item['item_id']) === 'publish') :
                        $item_img_url = get_the_post_thumbnail_url($item['item_id'], 'medium');
                        $x_pos = esc_attr($item['x']);
                        $y_pos = esc_attr($item['y']);
                        $z_pos = esc_attr($item['z'] ?? 1);
                ?>
                        <img src="<?php echo esc_url(get_the_post_thumbnail_url($item['item_id'], 'medium')); ?>"
                            class="placed-item" draggable="false" data-item-id="<?php echo esc_attr($item['item_id']); ?>"
                            style="transform: translate(<?php echo $x_pos; ?>px, <?php echo $y_pos; ?>px); z-index: <?php echo $z_pos; ?>;"
                            data-x="<?php echo $x_pos; ?>" data-y="<?php echo $y_pos; ?>" data-z="<?php echo $z_pos; ?>">
                    <?php endif; // End check for get_post_status 
                    ?>
                <?php endforeach; ?>
            </div>

            <div id="inventaris"
                class="w-full md:w-64 flex-shrink-0 p-4 rounded-xl h-48 md:h-auto md:max-h-[75vh] overflow-y-auto">
                <h3 class="font-game text-white text-xl mb-3">Lemari</h3>
                <div id="inventory-list" class="grid grid-cols-3 md:grid-cols-2 gap-2">
                    <?php foreach ($stowed_item_ids as $item_id) : ?>
                        <div class="bg-white/20 p-2 rounded-lg aspect-square flex items-center justify-center inventory-item"
                            data-item-id="<?php echo esc_attr($item_id); ?>">
                            <img draggable="false"
                                src="<?php echo esc_url(get_the_post_thumbnail_url($item_id, 'thumbnail')); ?>"
                                class="max-w-full max-h-full">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
    <?php get_template_part('template-parts/layout/footer', 'game'); ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const room = document.getElementById('kamar-area');
        const inventoryList = document.getElementById('inventory-list');
        let highestZ = 10; // Initial z-index

        // Reset layout button
        document.getElementById('reset-layout-button').addEventListener('click', function() {
            if (!confirm('Yakin mau membereskan semua item kembali ke lemari?')) {
                return;
            }

            // Select all items currently in the room
            const itemsInRoom = room.querySelectorAll('.placed-item');

            itemsInRoom.forEach(function(itemElement) {
                const itemId = itemElement.dataset.itemId;
                const itemImgSrc = itemElement.src;

                // Create a new inventory item element
                const newInventoryItem = document.createElement('div');
                newInventoryItem.className =
                    'bg-white/20 p-2 rounded-lg aspect-square flex items-center justify-center inventory-item';
                newInventoryItem.dataset.itemId = itemId;
                newInventoryItem.innerHTML =
                    `<img src="${itemImgSrc}" class="max-w-full max-h-full">`;
                inventoryList.appendChild(newInventoryItem);

                // Make the new inventory item draggable
                makeInventoryItemDraggable(newInventoryItem);

                // Remove the item from the room display
                itemElement.remove();
            });
        });

        // Fungsi untuk item DI DALAM KAMAR
        function makeRoomItemDraggable(element) {
            let x = parseFloat(element.dataset.x) || 0;
            let y = parseFloat(element.dataset.y) || 0;
            element.style.transform = `translate(${x}px, ${y}px)`;

            interact(element).draggable({
                listeners: {
                    start(event) {
                        highestZ++;
                        event.target.style.zIndex = highestZ;
                    },
                    move(event) {
                        x += event.dx;
                        y += event.dy;
                        event.target.style.transform = `translate(${x}px, ${y}px)`;
                    },
                    end(event) {
                        event.target.dataset.x = x;
                        event.target.dataset.y = y;
                        event.target.dataset.z = highestZ;
                    }
                },
                modifiers: [
                    interact.modifiers.restrictEdges({
                        outer: 'parent'
                    })
                ],
                inertia: true
            });

            interact(element).on('doubletap', function(event) {
                event.preventDefault();
                const itemElement = event.currentTarget;
                const itemId = itemElement.dataset.itemId;
                const itemImgSrc = itemElement.src;

                const newInventoryItem = document.createElement('div');
                newInventoryItem.className =
                    'bg-white/20 p-2 rounded-lg aspect-square flex items-center justify-center inventory-item';
                newInventoryItem.dataset.itemId = itemId;
                newInventoryItem.innerHTML =
                    `<img src="${itemImgSrc}" class="max-w-full max-h-full" draggable="false">`;
                inventoryList.appendChild(newInventoryItem);
                makeInventoryItemDraggable(newInventoryItem);
                itemElement.remove();
            });
        }

        // --- FUNGSI UNTUK ITEM DI DALAM LEMARI (DISEMPURNAKAN) ---
        function makeInventoryItemDraggable(element) {
            interact(element).draggable({
                inertia: true,
                // We don't need a custom 'move' listener here.
                // Interact.js handles the visual feedback automatically.
                // The main logic is in the dropzone.
            });
        }

        // Dropzone untuk area kamar
        interact(room).dropzone({
            accept: '.inventory-item',
            ondrop: function(event) {
                const droppedItem = event.relatedTarget;
                const itemId = droppedItem.dataset.itemId;
                const itemImgSrc = droppedItem.querySelector('img').src;

                const newItem = document.createElement('img');
                newItem.src = itemImgSrc;
                newItem.className = 'placed-item';
                newItem.dataset.itemId = itemId;
                newItem.draggable = false; // Important for the new item too!

                const roomRect = room.getBoundingClientRect();
                const dropCenter = {
                    x: event.dragEvent.clientX,
                    y: event.dragEvent.clientY
                };

                // Calculate position relative to the room, centered on cursor
                const newItemWidth = 75; // Approximate width for centering
                const newItemHeight = 75; // Approximate height for centering
                const xPos = dropCenter.x - roomRect.left - (newItemWidth / 2);
                const yPos = dropCenter.y - roomRect.top - (newItemHeight / 2);

                newItem.style.left = `0px`; // Base position
                newItem.style.top = `0px`; // Base position
                newItem.dataset.x = xPos;
                newItem.dataset.y = yPos;

                room.appendChild(newItem);

                // MUST be called after appending to the DOM
                makeRoomItemDraggable(newItem);

                droppedItem.remove();
            }
        });

        // Initialize listeners for all items on page load
        document.querySelectorAll('.placed-item').forEach(makeRoomItemDraggable);
        document.querySelectorAll('.inventory-item').forEach(makeInventoryItemDraggable);

        // Save layout button
        document.getElementById('save-layout-button').addEventListener('click', function() {
            const button = this;
            button.textContent = 'Menyimpan...';
            button.disabled = true;

            const layoutData = [];
            room.querySelectorAll('.placed-item').forEach(function(item) {
                layoutData.push({
                    item_id: item.dataset.itemId,
                    x: parseFloat(item.dataset.x) || 0,
                    y: parseFloat(item.dataset.y) || 0,
                    z: parseInt(item.dataset.z) || 1
                });
            });

            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'save_room_layout',
                    layout_data: layoutData,
                    player_id: <?php echo $player_id; ?>
                },
                success: function(response) {
                    if (response.success) {
                        alert('Tampilan kamar berhasil disimpan!');
                    } else {
                        alert('Gagal menyimpan: ' + response.data.message);
                    }
                    button.textContent = 'Simpan Tampilan';
                    button.disabled = false;
                },
                error: function() {
                    alert('Error: Gagal menghubungi server.');
                    button.textContent = 'Simpan Tampilan';
                    button.disabled = false;
                }
            });
        });
    });
</script>

<?php get_footer(); ?>