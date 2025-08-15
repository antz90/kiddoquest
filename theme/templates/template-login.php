<?php
/*
 * Template Name: Halaman Login
 */

// If user is already logged in, redirect them away to the homepage.
if (is_user_logged_in()) {
    wp_redirect(home_url());
    exit;
}

$login_error_message = '';

// Handle the form submission.
if ('POST' === $_SERVER['REQUEST_METHOD']) {
    $credentials = array(
        'user_login'    => isset($_POST['log']) ? sanitize_user($_POST['log']) : '',
        'user_password' => isset($_POST['pwd']) ? $_POST['pwd'] : '',
        'remember'      => isset($_POST['rememberme']),
    );

    // Use wp_signon() to attempt the login.
    $user = wp_signon($credentials, false);

    if (is_wp_error($user)) {
        // If login fails, store the error message.
        $login_error_message = '<strong>ERROR</strong>: Nama pengguna atau kata sandi salah.';
    } else {
        // If login is successful, redirect to the homepage (which is now 'Pilih Pemain').
        wp_redirect(home_url());
        exit;
    }
}

get_header();
?>
<div id="login-view" class="flex items-center justify-center min-h-screen">
    <div class="form-container text-center">
        <h1 class="font-game text-4xl text-blue-600" style="text-shadow: 2px 2px #fff;">MyKiddo Quest</h1>
        <p class="text-gray-600 mt-2 mb-6">Masuk untuk memulai petualangan!</p>

        <?php if (!empty($login_error_message)) : ?>
        <div class="mb-4 p-3 rounded-lg bg-red-100 text-red-700 border border-red-300">
            <?php echo $login_error_message; ?>
        </div>
        <?php endif; ?>

        <form id="login-form" class="space-y-4" method="POST" action="<?php echo esc_url(get_permalink()); ?>">
            <div>
                <input type="text" name="log" placeholder="Nama Pengguna" class="input-field" required>
            </div>
            <div>
                <input type="password" name="pwd" placeholder="Kata Sandi" class="input-field" required>
            </div>
            <div class="flex items-center justify-start text-sm">
                <input type="checkbox" name="rememberme" id="rememberme" class="mr-2">
                <label for="rememberme" class="text-gray-600">Ingat saya</label>
            </div>
            <button type="submit" class="btn-game w-full !mt-6">Masuk</button>
        </form>

        <p class="text-sm text-gray-500 mt-6">
            Lupa kata sandi? <a href="<?php echo wp_lostpassword_url(); ?>"
                class="font-bold text-blue-500 hover:underline">Reset di sini</a>
        </p>
    </div>
</div>
