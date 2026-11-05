<?php
require_once __DIR__ . '/env.php';
// Include this at the very top of every page, instead of session_start().

if(session_status() === PHP_SESSION_NONE){

    // Are we on HTTPS? (also checks the header hosting proxies send)
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    // Cookie settings must be applied BEFORE session_start()
    session_set_cookie_params([
        'lifetime' => 0,          // cookie dies when the browser closes
        'path'     => '/',
        'secure'   => $isHttps,   // only sent over HTTPS when on HTTPS
        'httponly' => true,       // JavaScript can't read it (limits XSS damage)
        'samesite' => 'Lax'       // not sent on cross-site POSTs (extra CSRF defence)
    ]);

    session_start();
}

// ---------- Idle timeout: log out after 30 minutes of inactivity ----------
$idleLimit = 30 * 60;

if(isset($_SESSION['user_id'])){
    if(isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $idleLimit){
        $_SESSION = [];
        session_destroy();
        header("Location: login.php?expired=1");
        exit();
    }
    $_SESSION['last_activity'] = time();
}

// ---------- CSRF helpers ----------
if(empty($_SESSION['csrf'])){
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// Prints the hidden input to put inside every POST form
function csrf_field(){
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars($_SESSION['csrf']) . '">';
}

// Returns true if the submitted token matches the session's token
function csrf_ok(){
    return isset($_POST['csrf'])
        && is_string($_POST['csrf'])
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

// Stops the script if the token is wrong
function csrf_require(){
    if(!csrf_ok()){
        http_response_code(419);
        die("Invalid or expired form. Go back, refresh the page and try again.");
    }
}