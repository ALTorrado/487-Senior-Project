<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    
    session_set_cookie_params([
        'lifetime' => 3600,
        'path' => '/',
        'domain' => '',    
        'secure' => true,   
        'httponly' => true  
    ]);
    
    session_start();
}

define('SESSION_TIMEOUT', 3600);

function checkSessionTimeout() {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        
        header("Location: login.php?session_expired=1");
        exit();
    }
    
    $_SESSION['last_activity'] = time();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
    checkSessionTimeout();
}

if (isLoggedIn()) {
    checkSessionTimeout();
}
?>
