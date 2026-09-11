<?php
// include/password_migration.php — backward-compatible password hashing.
// New passwords use password_hash(); legacy MD5 hashes are verified once
// and transparently upgraded on successful login.
if (!function_exists('password_is_legacy_md5')) {
    function password_is_legacy_md5($stored) {
        return is_string($stored) && preg_match('/^[a-f0-9]{32}$/i', $stored) === 1;
    }
}
if (!function_exists('password_verify_compat')) {
    function password_verify_compat($plain, $stored) {
        if (!is_string($stored) || $stored === '') {
            return false;
        }
        if (password_is_legacy_md5($stored)) {
            return hash_equals($stored, md5($plain));
        }
        return password_verify($plain, $stored);
    }
}
if (!function_exists('password_hash_new')) {
    function password_hash_new($plain) {
        return password_hash($plain, PASSWORD_DEFAULT);
    }
}
if (!function_exists('password_needs_upgrade')) {
    function password_needs_upgrade($stored) {
        if (!is_string($stored) || $stored === '') {
            return true;
        }
        if (password_is_legacy_md5($stored)) {
            return true;
        }
        return password_needs_rehash($stored, PASSWORD_DEFAULT);
    }
}
