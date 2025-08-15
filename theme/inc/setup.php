<?php

/**
 * Starts the PHP session on the 'init' hook.
 * This ensures the session is available for both normal page loads and AJAX requests.
 */
function kiddoquest_start_session()
{
    if (!session_id()) {
        session_start();
    }
}
add_action('init', 'kiddoquest_start_session', 1);

// Global login redirect rules
function kiddoquest_template_redirect()
{
    $login_page_url = home_url('/login');
    $dashboard_url = home_url('/dashboard');

    // --- RULE 1: For users who are NOT logged in ---
    if (!is_user_logged_in()) {
        if (!is_page('login')) {
            wp_redirect($login_page_url);
            exit;
        }
        return;
    }

    // --- RULE 2: For users who ARE logged in ---
    if (is_page('login')) {
        wp_redirect(home_url());
        exit;
    }

    if (isset($_SESSION['active_player_id'])) {
        // --- THE FIX IS HERE ---
        // Redirect to dashboard from homepage ONLY IF there's no 'action' query string.
        if (is_front_page() && !isset($_GET['action'])) {
            wp_redirect($dashboard_url);
            exit;
        }
    }
}
add_action('template_redirect', 'kiddoquest_template_redirect');
