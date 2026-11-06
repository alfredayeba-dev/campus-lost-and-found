<?php

require_once("includes/security.php");
include("config/database.php");

$ok    = false;
$token = $_GET['token'] ?? '';

// A real token is exactly 64 hex characters
if(is_string($token) && preg_match('/^[a-f0-9]{64}$/', $token)){

    $hash = hash('sha256', $token);

    try{

        // Handles first-time verification AND a pending email change.
        // email = COALESCE(pending_email, email) must stay BEFORE
        // pending_email = NULL, because MySQL applies SET items left to right.
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE users
             SET email             = COALESCE(pending_email, email),
                 pending_email     = NULL,
                 email_verified_at = NOW(),
                 verify_token_hash = NULL,
                 verify_expires    = NULL
             WHERE verify_token_hash = ?
               AND verify_expires > NOW()
               AND (email_verified_at IS NULL OR pending_email IS NOT NULL)"
        );
        mysqli_stmt_bind_param($stmt, "s", $hash);
        mysqli_stmt_execute($stmt);

        $ok = (mysqli_stmt_affected_rows($stmt) === 1);

    }catch(mysqli_sql_exception $e){

        // For example, someone else claimed that address in the meantime
        $ok = false;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify Email</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <nav class="topbar">
        <a href="login.php">Login</a>
        <a href="register.php">Create account</a>
    </nav>

    <section class="section hatch">
        <div class="container">
            <div class="narrow">

                <h1 style="font-size:48px; font-weight:800; margin:0 0 20px;">
                    <?php echo $ok ? "Verified." : "Oops."; ?>
                </h1>

                <div class="window">
                    <div class="window-bar">
                        <span class="dot"></span><span class="dot"></span>
                        <span class="window-title">verify-email.html</span>
                    </div>

                    <div class="window-body">

                        <?php if($ok){ ?>

                            <div class="alert alert-success">Your email is verified. You can log in now.</div>
                            <a class="btn" href="login.php">Go to Login</a>

                        <?php }else{ ?>

                            <div class="alert alert-error">
                                This link is invalid, expired, or already used.
                            </div>
                            <p>If your account is already verified, just log in. Otherwise request a new link.</p>
                            <div class="form-actions">
                                <a class="btn" href="resend-verification.php">Resend link</a>
                                <a href="login.php">Login</a>
                            </div>

                        <?php } ?>

                    </div>
                </div>

            </div>
        </div>
    </section>

    <footer class="footer">
        Campus Lost and Found System by Alfred Ayeba
    </footer>

</body>
</html>