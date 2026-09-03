<?php
// include/csrf.php — shared CSRF helpers (uses $_SESSION only, no DB).
if (!function_exists('csrf_token')) {
    function csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
if (!function_exists('csrf_verify')) {
    function csrf_verify() {
        return isset($_POST['csrf_token'])
            && hash_equals(csrf_token(), $_POST['csrf_token']);
    }
}
if (!function_exists('csrf_field')) {
    function csrf_field() {
        echo '<input type="hidden" name="csrf_token" value="' . htmlentities(csrf_token()) . '">';
    }
}
