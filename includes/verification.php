<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/mailer.php';

// Creates a fresh token for a user and emails them the link.
function issue_verification($conn, $user_id, $email, $first_name){

    // The raw token goes in the email. Only its hash is stored, so a
    // leaked database can't be used to verify accounts.
    $token = bin2hex(random_bytes(32));
    $hash  = hash('sha256', $token);

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE users
         SET verify_token_hash = ?,
             verify_expires    = DATE_ADD(NOW(), INTERVAL 24 HOUR),
             verify_sent_at    = NOW()
         WHERE user_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "si", $hash, $user_id);
    mysqli_stmt_execute($stmt);

    $base = rtrim(env('APP_URL', 'http://localhost/campus-lost-found'), '/');
    $link = $base . '/verify-email.php?token=' . $token;
    $name = htmlspecialchars($first_name);

    $html = "<p>Hi $name,</p>"
          . "<p>Confirm your email to activate your Campus Lost and Found account:</p>"
          . "<p><a href=\"$link\">Verify my email</a></p>"
          . "<p>This link expires in 24 hours. If you didn't create an account, ignore this message.</p>";

    $text = "Hi $first_name,\n\n"
          . "Confirm your email to activate your account:\n$link\n\n"
          . "This link expires in 24 hours. If you didn't create an account, ignore this message.";

    return send_mail($email, "Verify your email", $html, $text);
}