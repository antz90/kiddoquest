<?php

/**
 * Customize the admin columns for the 'log_tugas' CPT to create a better report.
 */

// 1. Add new columns (Anak, Tugas, Tanggal)
add_filter('manage_log-tugas_posts_columns', 'kiddoquest_set_custom_log_columns');
function kiddoquest_set_custom_log_columns($columns)
{
    unset($columns['author']); // Buang kolom author default
    unset($columns['date']);   // Buang kolom date default

    $columns['title'] = 'Log';
    $columns['player'] = 'Anak';
    $columns['task'] = 'Tugas yang Dikerjakan';
    $columns['completion_date'] = 'Waktu Selesai';

    return $columns;
}

// 2. Populate the new columns with data from post meta
add_action('manage_log-tugas_posts_custom_column', 'kiddoquest_custom_log_column_content', 10, 2);
function kiddoquest_custom_log_column_content($column, $post_id)
{
    switch ($column) {
        case 'player':
            $user_id = get_post_meta($post_id, '_user_id', true);
            if ($user_id) {
                $user_data = get_userdata($user_id);
                echo esc_html($user_data->display_name);
            }
            break;

        case 'task':
            $task_id = get_post_meta($post_id, '_task_id', true);
            if ($task_id) {
                echo esc_html(get_the_title($task_id));
            } else {
                // For journal entries, the task ID might be 0
                if (strpos(get_the_title($post_id), 'Menulis Jurnal') !== false) {
                    echo 'Menulis Jurnal Harian';
                }
            }
            break;

        case 'completion_date':
            echo get_the_date('d F Y, H:i:s', $post_id);
            break;
    }
}
