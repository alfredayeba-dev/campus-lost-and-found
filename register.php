<?php

session_start();

// Already logged in? Go straight to the dashboard
if(isset($_SESSION['user_id'])){
    header("Location: dashboard.php");
    exit();
}

include("config/database.php");

$message = "";

if(isset($_POST['register'])){

    $student_id  = trim($_POST['student_id']);
    $first_name  = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name   = trim($_POST['last_name']);
    $email       = trim($_POST['email']);
    $rawPassword = $_POST['password'];

    // Middle name is optional, store NULL if left empty
    if($middle_name === ""){
        $middle_name = null;
    }

    if(strlen($rawPassword) < 6){

        $message = "Password must be at least 6 characters.";

    }else{

        // Check email (prepared statement)
        $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $checkEmail = mysqli_stmt_get_result($stmt);

        if(mysqli_num_rows($checkEmail) > 0){

            $message = "Email already exists.";

        }else{

            // Check student ID (prepared statement)
            $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE student_id = ?");
            mysqli_stmt_bind_param($stmt, "s", $student_id);
            mysqli_stmt_execute($stmt);
            $checkStudentId = mysqli_stmt_get_result($stmt);

            if(mysqli_num_rows($checkStudentId) > 0){

                $message = "Student ID already exists.";

            }else{

                $password = password_hash($rawPassword, PASSWORD_DEFAULT);
                $role = "student";

                // Insert new user (prepared statement)
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

                if(mysqli_stmt_execute($stmt)){
                    header("Location: login.php");
                    exit();
                }else{
                    $message = "Something went wrong. Please try again.";
                }
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
</head>
<body>

    <nav class="topbar">
        <a href="login.php">Login</a>
        <a href="register.php">Create account</a>
    </nav>

    <section class="section hatch">
        <div class="container">
            <div class="narrow">

                <h1 class="heading" style="font-size:48px; font-weight:800; margin:0 0 20px;">Register.</h1>

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

                        <form action="" method="POST">

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
                                <input type="password" id="password" name="password" minlength="6" required>
                                <p class="form-hint">At least 6 characters.</p>
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
        Campus Lost and Found System by Alfred Ayeba
    </footer>

</body>
</html>