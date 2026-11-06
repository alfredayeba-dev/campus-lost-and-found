<?php

require_once("includes/security.php");
include("config/database.php");
require_once("includes/ratelimit.php");

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$user  = null;

// A real token is exactly 64 hex characters
if(is_string($token) && preg_match('/^[a-f0-9]{64}$/', $token)){

    $hash = hash('sha256', $token);

    $stmt = mysqli_prepare(
        $conn,
        "SELECT user_id, email FROM users
         WHERE reset_token_hash = ? AND reset_expires > NOW()"
    );
    mysqli_stmt_bind_param($stmt, "s", $hash);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

$message = "";
$done    = false;

if($user && isset($_POST['reset'])){

    csrf_require();

    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if(strlen($new) < 8){

        $message = "Password must be at least 8 characters.";

    }elseif($new !== $confirm){

        $message = "Passwords do not match.";

    }else{

        $newHash = password_hash($new, PASSWORD_DEFAULT);

        // Re-checks the token inside the UPDATE, so it works exactly once.
        // Reaching the inbox also proves email ownership, so mark verified.
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE users
             SET password          = ?,
                 reset_token_hash  = NULL,
                 reset_expires     = NULL,
                 email_verified_at = COALESCE(email_verified_at, NOW())
             WHERE user_id = ?
               AND reset_token_hash = ?
               AND reset_expires > NOW()"
        );
        mysqli_stmt_bind_param($stmt, "sis", $newHash, $user['user_id'], $hash);
        mysqli_stmt_execute($stmt);

        if(mysqli_stmt_affected_rows($stmt) === 1){

            // A reset also clears any login lockout on that account
            login_clear_email($conn, $user['email']);
            $done = true;

        }else{

            $message = "This link was just used or has expired. Request a new one.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <?php include("includes/head.php"); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
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
                    <?php echo $done ? "Done." : "New password."; ?>
                </h1>

                <div class="window">
                    <div class="window-bar">
                        <span class="dot"></span><span class="dot"></span>
                        <span class="window-title">reset-password.html</span>
                    </div>

                    <div class="window-body">

                        <?php if($done){ ?>

                            <div class="alert alert-success">Your password has been changed. You can log in now.</div>
                            <a class="btn" href="login.php">Go to Login</a>

                        <?php }elseif(!$user){ ?>

                            <div class="alert alert-error">This reset link is invalid or has expired.</div>
                            <a class="btn" href="forgot-password.php">Request a new link</a>

                        <?php }else{ ?>

                            <?php if($message !== ""){ ?>
                                <div class="alert alert-error"><?php echo htmlspecialchars($message); ?></div>
                            <?php } ?>

                            <form method="POST">

                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                                <div class="form-group">
                                    <label for="new_password">New Password</label>
                                    <input type="password" id="new_password" name="new_password"
                                           minlength="8" autocomplete="new-password" required>
                                    <p class="form-hint">At least 8 characters.</p>
                                </div>

                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password"
                                           minlength="8" autocomplete="new-password" required>
                                </div>

                                <div class="form-actions">
                                    <button class="btn" type="submit" name="reset">Change Password</button>
                                </div>

                            </form>

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