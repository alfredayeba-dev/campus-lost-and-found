<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/mailer.php';

function issue_password_reset($conn, $user_id, $email, $first_name){

    $token = bin2hex(random_bytes(32));
    $hash  = hash('sha256', $token);

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE users
         SET reset_token_hash = ?,
             reset_expires    = DATE_ADD(NOW(), INTERVAL 1 HOUR),
             reset_sent_at    = NOW()
         WHERE user_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "si", $hash, $user_id);
    mysqli_stmt_execute($stmt);

    $base = rtrim(env('APP_URL', 'http://localhost/campus-lost-found'), '/');
    $link = $base . '/reset-password.php?token=' . $token;
    $name = htmlspecialchars($first_name);

    $html = "<p>Hi $name,</p>"
          . "<p>We got a request to reset your password:</p>"
          . "<p><a href=\"$link\">Choose a new password</a></p>"
          . "<p>This link expires in 1 hour. If you didn't ask for this, ignore this email and your password stays the same.</p>";

    $text = "Hi $first_name,\n\n"
          . "We got a request to reset your password:\n$link\n\n"
          . "This link expires in 1 hour. If you didn't ask for this, ignore this email and your password stays the same.";

    return send_mail($email, "Reset your password", $html, $text);
}