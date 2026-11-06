<?php

require_once("includes/security.php");
include("config/database.php");
require_once("includes/captcha.php");
require_once("includes/password-reset.php");

$message = "";
$type    = "error";

if(isset($_POST['forgot'])){

    csrf_require();

    if(!turnstile_verify()){

        $message = "Please complete the security check.";

    }else{

        $email = trim($_POST['email'] ?? '');

        // At most one reset email per account per minute
        $stmt = mysqli_prepare(
            $conn,
            "SELECT user_id, first_name, email
             FROM users
             WHERE email = ?
               AND (reset_sent_at IS NULL OR reset_sent_at < DATE_SUB(NOW(), INTERVAL 60 SECOND))"
        );
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if($row){
            issue_password_reset($conn, $row['user_id'], $row['email'], $row['first_name']);
        }

        // Same answer whether or not the account exists
        $message = "If that email belongs to an account, we've sent a reset link. Check your inbox and spam folder.";
        $type    = "success";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/style.css">
    <?php echo turnstile_script(); ?>
</head>
<body>

    <nav class="topbar">
        <a href="login.php">Login</a>
        <a href="register.php">Create account</a>
    </nav>

    <section class="section hatch">
        <div class="container">
            <div class="narrow">

                <h1 style="font-size:48px; font-weight:800; margin:0 0 20px;">Forgot it?</h1>

                <div class="window">
                    <div class="window-bar">
                        <span class="dot"></span><span class="dot"></span>
                        <span class="window-title">forgot-password.html</span>
                    </div>

                    <div class="window-body">

                        <?php if($message !== ""){ ?>
                            <div class="alert alert-<?php echo $type; ?>">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php } ?>

                        <form method="POST">

                            <?php echo csrf_field(); ?>

                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" required>
                            </div>

                            <div class="form-group">
                                <?php echo turnstile_widget(); ?>
                            </div>

                            <div class="form-actions">
                                <button class="btn" type="submit" name="forgot">Send reset link</button>
                                <a href="login.php">Back to login</a>
                            </div>

                        </form>

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