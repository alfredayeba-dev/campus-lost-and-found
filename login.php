<?php

session_start();

// Already logged in? Go straight to the dashboard
if(isset($_SESSION['user_id'])){
    header("Location: dashboard.php");
    exit();
}

include("config/database.php");

$message = "";

if(isset($_POST['login'])){

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Prepared statement to prevent SQL injection
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($result) == 1){

        $user = mysqli_fetch_assoc($result);

        if(password_verify($password, $user['password'])){

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['full_name'] = trim(
                $user['first_name'] . " " . $user['middle_name'] . " " . $user['last_name']
            );
            $_SESSION['role'] = $user['role'];

            header("Location: dashboard.php");
            exit();

        }else{
            $message = "Wrong password";
        }

    }else{
        $message = "Account not found";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
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

                <h1 class="heading" style="font-size:48px; font-weight:800; margin:0 0 20px;">Login.</h1>

                <div class="window">
                    <div class="window-bar">
                        <span class="dot"></span><span class="dot"></span>
                        <span class="window-title">login.html</span>
                    </div>

                    <div class="window-body">

                        <?php if($message !== ""){ ?>
                            <div class="alert alert-error">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php } ?>

                        <form method="POST">

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