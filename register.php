<?php

require_once("includes/security.php");

// Already logged in? Go straight to the dashboard
if(isset($_SESSION['user_id'])){
    header("Location: dashboard.php");
    exit();
}

include("config/database.php");
require_once("includes/captcha.php");
require_once("includes/verification.php");

$message = "";

if(isset($_POST['register'])){

    csrf_require();

    $student_id  = trim($_POST['student_id'] ?? '');
    $first_name  = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name   = trim($_POST['last_name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $rawPassword = $_POST['password'] ?? '';

    if($middle_name === ""){
        $middle_name = null;
    }

    if(!turnstile_verify()){

        $message = "Please complete the security check.";

    }elseif($student_id === "" || $first_name === "" || $last_name === "" || $email === ""){

        $message = "Please fill in all required fields.";

    }elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){

        $message = "Please enter a valid email address.";

    }elseif(strlen($rawPassword) < 8){

        $message = "Password must be at least 8 characters.";

    }else{

        // Email already used?
        $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $emailTaken = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;

        // Student ID already used?
        $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE student_id = ?");
        mysqli_stmt_bind_param($stmt, "s", $student_id);
        mysqli_stmt_execute($stmt);
        $idTaken = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;

        if($emailTaken){

            $message = "Email already exists.";

        }elseif($idTaken){

            $message = "Student ID already exists.";

        }else{

            $password = password_hash($rawPassword, PASSWORD_DEFAULT);
            $role     = "student";

            try{

                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO users
                     (student_id, first_name, middle_name, last_name, email, password, role)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                mysqli_stmt_bind_param(
                    $stmt,
                    "sssssss",
                    $student_id, $first_name, $middle_name, $last_name, $email, $password, $role
                );
                mysqli_stmt_execute($stmt);

                $newId = mysqli_insert_id($conn);

                // If the email fails to send, the account still exists and the
                // user can request a new link from the resend page.
                issue_verification($conn, $newId, $email, $first_name);

                header("Location: login.php?registered=1");
                exit();

            }catch(mysqli_sql_exception $e){

                // 1062 = duplicate key (unique index caught a simultaneous signup)
                $message = ($e->getCode() === 1062)
                    ? "Email or Student ID already exists."
                    : "Something went wrong. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Registration</title>
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

                <h1 style="font-size:48px; font-weight:800; margin:0 0 20px;">Register.</h1>

                <div class="window">
                    <div class="window-bar">
                        <span class="dot"></span><span class="dot"></span>
                        <span class="window-title">register.html</span>
                    </div>

                    <div class="window-body">

                        <?php if($message !== ""){ ?>
                            <div class="alert alert-error">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php } ?>

                        <form method="POST">

                            <?php echo csrf_field(); ?>

                            <div class="form-group">
                                <label for="student_id">Student ID</label>
                                <input type="text" id="student_id" name="student_id"
                                       value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name"
                                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="middle_name">Middle Name (optional)</label>
                                <input type="text" id="middle_name" name="middle_name"
                                       value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name"
                                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email"
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" id="password" name="password"
                                       minlength="8" autocomplete="new-password" required>
                                <p class="form-hint">At least 8 characters.</p>
                            </div>

                            <div class="form-group">
                                <?php echo turnstile_widget(); ?>
                            </div>

                            <div class="form-actions">
                                <button class="btn" type="submit" name="register">Register</button>
                            </div>

                        </form>

                        <p class="form-footer">
                            Already have an account? <a href="login.php">Login</a>
                        </p>

                    </div>
                </div>

            </div>
        </div>
    </section>

    <footer class="footer">
        Campus Lost and Found System
    </footer>

</body>
</html>