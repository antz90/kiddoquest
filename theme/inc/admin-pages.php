<?php

/**
 * This file handles all custom admin pages for the theme.
 */

/**
 * Registers the main admin menu and submenu pages for MyKiddo Quest.
 */
function kiddoquest_register_admin_pages()
{
    // Main Menu Page
    add_menu_page(
        'MyKiddo Quest',          // Page Title
        'MyKiddo Quest',          // Menu Title
        'manage_options',         // Capability
        'kiddoquest-main',        // Menu Slug
        'kiddoquest_render_schedule_summary_page', // Function to display the page
        'dashicons-games',        // Icon URL
        20                        // Position
    );

    // Submenu Page for Schedule
    add_submenu_page(
        'kiddoquest-main',        // Parent Slug
        'Ringkasan Jadwal',       // Page Title
        'Ringkasan Jadwal',       // Menu Title
        'manage_options',         // Capability
        'kiddoquest-schedule',    // Menu Slug (can be same as parent to make parent clickable)
        'kiddoquest_render_schedule_summary_page' // Function to display the page
    );
}
add_action('admin_menu', 'kiddoquest_register_admin_pages');


/**
 * Renders the HTML content for the Schedule Summary admin page.
 */
function kiddoquest_render_schedule_summary_page()
{
    // 1. Prepare the data (This whole section is unchanged)
    $players = get_users(['role' => 'subscriber']);
    $days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $schedule_data = [];
    foreach ($players as $player) {
        $player_id = $player->ID;
        $schedule_data[$player_id] = [
            'name' => $player->display_name,
            'schedule' => array_fill_keys($days_of_week, []),
        ];
        if (function_exists('have_rows') && have_rows('daftar_tugas_anak', 'user_' . $player_id)) {
            while (have_rows('daftar_tugas_anak', 'user_' . $player_id)) : the_row();
                $task_post = get_sub_field('pilih_tugas');
                $scheduled_days = get_sub_field('jadwalkan_di_hari');
                if ($task_post && is_array($scheduled_days) && function_exists('kiddoquest_get_max_points_for_task')) {
                    $max_points = kiddoquest_get_max_points_for_task($task_post->ID);
                    foreach ($scheduled_days as $day) {
                        $day_name = ucfirst($day);
                        if (in_array($day_name, $days_of_week)) {
                            $schedule_data[$player_id]['schedule'][$day_name][] = [
                                'title' => $task_post->post_title,
                                'points' => $max_points,
                            ];
                        }
                    }
                }
            endwhile;
        }
    }

    // 2. Render the page HTML
?>
    <div class="wrap">
        <h1>Ringkasan Jadwal Mingguan Anak</h1>
        <p>Tabel ini menunjukkan semua tugas yang dijadwalkan untuk setiap anak, beserta potensi poin koin maksimal per
            tugas dan total per hari.</p>

        <style>
            /* --- CSS YANG DIPERBAIKI --- */
            .schedule-table {
                width: 100%;
                border-collapse: collapse;
                table-layout: fixed;
            }

            .schedule-table th,
            .schedule-table td {
                border: 1px solid #ccc;
                padding: 10px;
                vertical-align: top;
            }

            .schedule-table th {
                background-color: #f5f5f5;
            }

            /* NEW: Wrapper inside the TD becomes the flex container */
            .cell-wrapper {
                display: flex;
                flex-direction: column;
                height: 100%;
                min-height: 420px;
                /* Give some minimum height for flexing */
            }

            .schedule-table ul {
                margin: 0 0 10px 0;
                padding-left: 15px;
                flex-grow: 1;
                /* This pushes the total down */
            }

            .schedule-table .task-item {
                display: flex;
                justify-content: space-between;
                margin-bottom: 4px;
            }

            .schedule-table .task-points {
                font-weight: bold;
                color: #2271b1;
                white-space: nowrap;
                margin-left: 8px;
            }

            .schedule-table .daily-total {
                margin-top: auto;
                border-top: 1px solid #ccc;
                padding-top: 5px;
                font-weight: bold;
            }
        </style>

        <table class="schedule-table widefat fixed">
            <thead>
                <tr>
                    <th style="width:5%;">Nama Anak</th>
                    <?php foreach ($days_of_week as $day) : ?>
                        <th><?php echo esc_html($day); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($players)) : ?>
                    <tr>
                        <td colspan="8">Tidak ada user dengan role 'subscriber' yang ditemukan.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($schedule_data as $player_id => $data) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($data['name']); ?></strong></td>
                            <?php
                            foreach ($days_of_week as $day) :
                                $tasks_today = $data['schedule'][$day];
                                $daily_total = 0;
                            ?>
                                <td>
                                    <div class="cell-wrapper">
                                        <?php if (!empty($tasks_today)) : ?>
                                            <ul>
                                                <?php foreach ($tasks_today as $task) :
                                                    $daily_total += $task['points'];
                                                ?>
                                                    <li>
                                                        <div class="task-item">
                                                            <span><?php echo esc_html($task['title']); ?></span>
                                                            <span class="task-points"><?php echo $task['points']; ?> 💰</span>
                                                        </div>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <div class="daily-total">
                                                Total: <?php echo $daily_total; ?> 💰
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php
}
