<?php

require_once("includes/security.php");

// Already logged in? Go straight to the dashboard
if(isset($_SESSION['user_id'])){
    header("Location: dashboard.php");
    exit();
}

include("config/database.php");
require_once("includes/captcha.php");
require_once("includes/ratelimit.php");

$message     = "";
$messageType = "error";
$showResend  = false;
$ip          = client_ip();

if(isset($_GET['expired'])){
    $message = "You were logged out for being inactive. Please log in again.";
}

if(isset($_GET['registered'])){
    $message     = "Account created! Check your email for a verification link before logging in.";
    $messageType = "success";
}

if(isset($_POST['login'])){

    csrf_require();

    $message     = "";
    $messageType = "error";

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if(login_is_locked($conn, $email, $ip)){

        $message = "Too many failed attempts. Please wait about " . LOGIN_WINDOW_MINUTES . " minutes and try again.";

    }elseif(!turnstile_verify()){

        $message = "Please complete the security check.";

    }else{

        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        // One message for "no such account" and "wrong password"
        if(!$user || !password_verify($password, $user['password'])){

            login_record_failure($conn, $email, $ip);
            $message = "Invalid email or password.";

        }elseif(empty($user['email_verified_at'])){

            // Only shown after the correct password, so it leaks nothing
            $message    = "Please verify your email before logging in. Check your inbox for the link.";
            $showResend = true;

        }else{

            login_clear($conn, $email, $ip);

            session_regenerate_id(true);

            $_SESSION['user_id']    = $user['user_id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name']  = $user['last_name'];
            $_SESSION['full_name']  = trim(
                $user['first_name'] . " " . ($user['middle_name'] ?? '') . " " . $user['last_name']
            );
            $_SESSION['role']       = $user['role'];

            header("Location: dashboard.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
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

                <h1 style="font-size:48px; font-weight:800; margin:0 0 20px;">Login.</h1>

                <div class="window">
                    <div class="window-bar">
                        <span class="dot"></span><span class="dot"></span>
                        <span class="window-title">login.html</span>
                    </div>

                    <div class="window-body">

                        <?php if($message !== ""){ ?>
                            <div class="alert alert-<?php echo $messageType; ?>">
                                <?php echo htmlspecialchars($message); ?>
                                <?php if($showResend){ ?>
                                    <br><a href="resend-verification.php">Resend verification email</a>
                                <?php } ?>
                            </div>
                        <?php } ?>

                        <form method="POST">

                            <?php echo csrf_field(); ?>

                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email"
                                       id="email"
                                       name="email"
                                       placeholder="you@example.com"
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password"
                                       id="password"
                                       name="password"
                                       placeholder="Your password"
                                       required>
                                <p class="form-hint"><a href="forgot-password.php">Forgot password?</a></p>
                            </div>

                            <div class="form-group">
                                <?php echo turnstile_widget(); ?>
                            </div>

                            <div class="form-actions">
                                <button class="btn" type="submit" name="login">Login</button>
                            </div>

                        </form>

                        <p class="form-footer">
                            No account yet? <a href="register.php">Create one</a>
                        </p>

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