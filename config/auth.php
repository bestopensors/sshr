<?php
/**
 * Authentication Configuration and Functions
 */

session_start();

// Login protection
define('MAX_LOGIN_ATTEMPTS', 3);
define('LOGIN_BAN_MINUTES', 30);

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Require authentication - redirect to login if not logged in
 */
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Attempt login
 */
function attemptLogin($username, $password) {
    if (!dbAvailable()) {
        return false;
    }

    $ip = getClientIp();
    if (isIpBanned($ip)) {
        return false;
    }

    $stmt = db()->prepare("SELECT * FROM admins WHERE username = ? AND active = 1");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['login_time'] = time();
        
        clearFailedLogins($ip);

        // Update last login
        $stmt = db()->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$admin['id']]);
        
        return true;
    }

    recordFailedLogin($ip);
    return false;
}

/**
 * Logout user
 */
function logout() {
    $_SESSION = [];
    session_destroy();
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Resolve client IP address
 */
function getClientIp() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwarded = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $candidate = trim($forwarded[0]);
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            $ip = $candidate;
        }
    }

    return $ip;
}

/**
 * Check if an IP is currently banned
 */
function isIpBanned($ip) {
    if (!dbAvailable()) {
        return true;
    }

    $attempt = getLoginAttemptRow($ip);
    if (!$attempt || empty($attempt['banned_until'])) {
        return false;
    }

    $banUntil = strtotime($attempt['banned_until']);
    if ($banUntil !== false && $banUntil > time()) {
        return true;
    }

    // Ban expired, reset counters
    clearFailedLogins($ip);
    return false;
}

/**
 * Remaining attempts before ban
 */
function getRemainingLoginAttempts($ip) {
    if (!dbAvailable()) {
        return 0;
    }

    $attempt = getLoginAttemptRow($ip);
    if (!$attempt) {
        return MAX_LOGIN_ATTEMPTS;
    }

    if (!empty($attempt['banned_until']) && strtotime($attempt['banned_until']) > time()) {
        return 0;
    }

    $used = (int)$attempt['fail_count'];
    return max(0, MAX_LOGIN_ATTEMPTS - $used);
}

/**
 * Record failed login for IP
 */
function recordFailedLogin($ip) {
    if (!dbAvailable()) {
        return;
    }

    $attempt = getLoginAttemptRow($ip);
    $failCount = $attempt ? (int)$attempt['fail_count'] : 0;
    $failCount++;

    $bannedUntil = null;
    if ($failCount >= MAX_LOGIN_ATTEMPTS) {
        $bannedUntil = date('Y-m-d H:i:s', time() + (LOGIN_BAN_MINUTES * 60));
        $failCount = MAX_LOGIN_ATTEMPTS;
    }

    $stmt = db()->prepare(
        "INSERT INTO admin_login_attempts (ip_address, fail_count, last_failed, banned_until)
         VALUES (?, ?, NOW(), ?)
         ON DUPLICATE KEY UPDATE fail_count = VALUES(fail_count), last_failed = NOW(), banned_until = VALUES(banned_until)"
    );
    $stmt->execute([$ip, $failCount, $bannedUntil]);
}

/**
 * Clear failed login counters for IP
 */
function clearFailedLogins($ip) {
    if (!dbAvailable()) {
        return;
    }

    $stmt = db()->prepare(
        "UPDATE admin_login_attempts SET fail_count = 0, banned_until = NULL WHERE ip_address = ?"
    );
    $stmt->execute([$ip]);
}

/**
 * Fetch the login attempt row
 */
function getLoginAttemptRow($ip) {
    if (!dbAvailable()) {
        return null;
    }

    $stmt = db()->prepare("SELECT * FROM admin_login_attempts WHERE ip_address = ?");
    $stmt->execute([$ip]);
    return $stmt->fetch();
}


