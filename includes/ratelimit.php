<?php
require_once __DIR__ . '/env.php';

const LOGIN_MAX_PER_ACCOUNT = 5;    // failed tries for one email, from one IP
const LOGIN_MAX_PER_IP      = 20;   // failed tries from one IP, any email
const LOGIN_WINDOW_MINUTES  = 15;

function client_ip(){

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // Behind a host's proxy, REMOTE_ADDR is the proxy, not the visitor.
    // Only trust the forwarded header when you turn it on in .env.
    if(env('TRUST_PROXY', '0') === '1' && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
        $parts = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
        $last  = end($parts);   // the entry our own proxy added

        if(filter_var($last, FILTER_VALIDATE_IP)){
            $ip = $last;
        }
    }

    return $ip;
}

function login_key($email){
    return mb_substr(strtolower(trim($email)), 0, 100);
}

function login_is_locked($conn, $email, $ip){

    $email  = login_key($email);
    $window = (int) LOGIN_WINDOW_MINUTES;

    $stmt = mysqli_prepare(
        $conn,
        "SELECT COALESCE(SUM(email = ?), 0) AS account_fails,
                COUNT(*)                    AS ip_fails
         FROM login_attempts
         WHERE ip = ?
           AND attempted_at > DATE_SUB(NOW(), INTERVAL $window MINUTE)"
    );
    mysqli_stmt_bind_param($stmt, "ss", $email, $ip);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    return ((int) $row['account_fails'] >= LOGIN_MAX_PER_ACCOUNT)
        || ((int) $row['ip_fails']      >= LOGIN_MAX_PER_IP);
}

function login_record_failure($conn, $email, $ip){

    $email = login_key($email);

    $stmt = mysqli_prepare($conn, "INSERT INTO login_attempts (email, ip) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, "ss", $email, $ip);
    mysqli_stmt_execute($stmt);

    // Occasionally tidy up old rows
    if(random_int(1, 50) === 1){
        mysqli_query($conn, "DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    }
}

// After a successful login: forget this email's failures from this IP
function login_clear($conn, $email, $ip){

    $email = login_key($email);

    $stmt = mysqli_prepare($conn, "DELETE FROM login_attempts WHERE email = ? AND ip = ?");
    mysqli_stmt_bind_param($stmt, "ss", $email, $ip);
    mysqli_stmt_execute($stmt);
}

// After a password reset: forget this email's failures from everywhere
function login_clear_email($conn, $email){

    $email = login_key($email);

    $stmt = mysqli_prepare($conn, "DELETE FROM login_attempts WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
}