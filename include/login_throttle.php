<?php
// include/login_throttle.php — brute-force protection helpers.
// Uses the login_attempts table. Requires $dbh (PDO) to be available.
if (!function_exists('throttle_blocked')) {
    // Returns true if the given identifier (email) is currently locked out.
    function throttle_blocked($dbh, $identifier) {
        $max = 5;                 // max failed attempts
        $window = 15 * 60;        // lockout window in seconds
        $sql = "SELECT COUNT(*) AS c, MAX(attempt_time) AS last
                FROM login_attempts
                WHERE identifier=:id AND attempt_time > (NOW() - INTERVAL $window SECOND)";
        $q = $dbh->prepare($sql);
        $q->bindParam(':id', $identifier, PDO::PARAM_STR);
        $q->execute();
        $row = $q->fetch(PDO::FETCH_OBJ);
        if ($row && $row->c >= $max) {
            return true;
        }
        return false;
    }
}
if (!function_exists('throttle_fail')) {
    // Record a failed login attempt.
    function throttle_fail($dbh, $identifier) {
        $sql = "INSERT INTO login_attempts (identifier, attempt_time) VALUES (:id, NOW())";
        $q = $dbh->prepare($sql);
        $q->bindParam(':id', $identifier, PDO::PARAM_STR);
        $q->execute();
    }
}
if (!function_exists('throttle_clear')) {
    // Clear failed attempts for an identifier on successful login.
    function throttle_clear($dbh, $identifier) {
        $sql = "DELETE FROM login_attempts WHERE identifier=:id";
        $q = $dbh->prepare($sql);
        $q->bindParam(':id', $identifier, PDO::PARAM_STR);
        $q->execute();
    }
}
