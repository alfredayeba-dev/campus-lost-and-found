<?php

require_once("includes/security.php");

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

include("config/database.php");

$user_id = (int) $_SESSION['user_id'];

$message         = "";
$passwordMessage = "";

/*
|--------------------------------------------------------------------------
| GET USER
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Account was deleted while logged in
if(!$user){
    $_SESSION = [];
    session_destroy();
    header("Location: login.php");
    exit();
}

// Values shown in the profile form (replaced by what the user typed if saving fails)
$form = [
    'first_name'  => $user['first_name']  ?? '',
    'middle_name' => $user['middle_name'] ?? '',
    'last_name'   => $user['last_name']   ?? '',
    'email'       => $user['email']       ?? ''
];

/*
|--------------------------------------------------------------------------
| UPDATE PROFILE
|--------------------------------------------------------------------------
*/

if(isset($_POST['submit'])){

    csrf_require();

    $form['first_name']  = trim($_POST['first_name'] ?? '');
    $form['middle_name'] = trim($_POST['middle_name'] ?? '');
    $form['last_name']   = trim($_POST['last_name'] ?? '');
    $form['email']       = trim($_POST['email'] ?? '');

    if($form['first_name'] === "" || $form['last_name'] === "" || $form['email'] === ""){

        $message = "Please complete all required fields.";

    }elseif(!filter_var($form['email'], FILTER_VALIDATE_EMAIL)){

        $message = "Please enter a valid email address.";

    }else{

        // Is the email used by someone else?
        $check = mysqli_prepare(
            $conn,
            "SELECT user_id FROM users WHERE email = ? AND user_id != ?"
        );
        mysqli_stmt_bind_param($check, "si", $form['email'], $user_id);
        mysqli_stmt_execute($check);
        $exists = mysqli_num_rows(mysqli_stmt_get_result($check));

        if($exists > 0){

            $message = "That email address is already in use.";

        }else{

            // Store NULL for an empty middle name, same as register.php
            $middleToSave = ($form['middle_name'] === "") ? null : $form['middle_name'];

            try{

                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE users
                     SET first_name = ?, middle_name = ?, last_name = ?, email = ?
                     WHERE user_id = ?"
                );
                mysqli_stmt_bind_param(
                    $stmt,
                    "ssssi",
                    $form['first_name'],
                    $middleToSave,
                    $form['last_name'],
                    $form['email'],
                    $user_id
                );
                mysqli_stmt_execute($stmt);

                // Keep the session in step with the database
                $_SESSION['first_name'] = $form['first_name'];
                $_SESSION['last_name']  = $form['last_name'];
                $_SESSION['full_name']  = trim(
                    $form['first_name'] . " " . $form['middle_name'] . " " . $form['last_name']
                );

                header("Location: profile.php?updated=1");
                exit();

            }catch(mysqli_sql_exception $e){

                $message = "Unable to update profile. Please try again.";

            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| CHANGE PASSWORD
|--------------------------------------------------------------------------
*/

if(isset($_POST['change_password'])){

    csrf_require();

    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if($current_password === "" || $new_password === "" || $confirm_password === ""){

        $passwordMessage = "All password fields are required.";

    }elseif(!password_verify($current_password, $user['password'])){

        $passwordMessage = "Current password is incorrect.";

    }elseif($new_password !== $confirm_password){

        $passwordMessage = "New passwords do not match.";

    }elseif(strlen($new_password) < 8){

        $passwordMessage = "Password must be at least 8 characters.";

    }elseif($new_password === $current_password){

        $passwordMessage = "New password must be different from the current one.";

    }else{

        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

        try{

            $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt, "si", $new_hash, $user_id);
            mysqli_stmt_execute($stmt);

            // New session ID after a credential change
            session_regenerate_id(true);

            header("Location: profile.php?password_updated=1");
            exit();

        }catch(mysqli_sql_exception $e){

            $passwordMessage = "Unable to update password. Please try again.";

        }
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>My Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <nav class="topbar">
        <a href="dashboard.php">Dashboard</a>
        <a href="report-lost-item.php">Report lost</a>
        <a href="report-found-item.php">Report found</a>
        <a href="view-items.php">All items</a>
        <a href="my-items.php">My posts</a>

        <?php if(($_SESSION['role'] ?? '') === 'admin'){ ?>
            <a href="admin.php">Admin</a>
        <?php } ?>

        <a href="profile.php">Profile</a>
        <a href="logout.php" class="nav-logout">Logout</a>
    </nav>

    <section class="section hatch">
        <div class="container">
            <div class="narrow-wide">

                <a class="back-link" href="dashboard.php">&larr; Back to Dashboard</a>

                <h1 style="font-size:48px; font-weight:800; margin:0 0 20px;">My Profile.</h1>

                <!-- ============ Card 1: Profile details ============ -->
                <div class="window">

                    <div class="window-bar">
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="window-title">profile.html</span>
                    </div>

                    <div class="window-body">

                        <?php if(isset($_GET['updated'])){ ?>
                            <div class="alert alert-success">Profile updated successfully.</div>
                        <?php } ?>

                        <?php if($message !== ""){ ?>
                            <div class="alert alert-error"><?php echo htmlspecialchars($message); ?></div>
                        <?php } ?>

                        <form method="POST">

                            <?php echo csrf_field(); ?>

                            <div class="form-group">
                                <label for="student_id">Student ID</label>
                                <input type="text"
                                       id="student_id"
                                       value="<?php echo htmlspecialchars($user['student_id'] ?? ''); ?>"
                                       disabled>
                            </div>

                            <div class="form-group">
                                <label for="first_name">First Name *</label>
                                <input type="text"
                                       id="first_name"
                                       name="first_name"
                                       value="<?php echo htmlspecialchars($form['first_name']); ?>"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="middle_name">Middle Name</label>
                                <input type="text"
                                       id="middle_name"
                                       name="middle_name"
                                       value="<?php echo htmlspecialchars($form['middle_name']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="last_name">Last Name *</label>
                                <input type="text"
                                       id="last_name"
                                       name="last_name"
                                       value="<?php echo htmlspecialchars($form['last_name']); ?>"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email"
                                       id="email"
                                       name="email"
                                       value="<?php echo htmlspecialchars($form['email']); ?>"
                                       required>
                            </div>

                            <div class="form-actions">
                                <button class="btn" type="submit" name="submit">Save Changes</button>
                                <a href="dashboard.php">Cancel</a>
                            </div>

                        </form>

                    </div>
                </div>

                <!-- ============ Card 2: Security (spaced out) ============ -->
                <div class="window" style="margin-top:40px;">

                    <div class="window-bar">
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="window-title">security-settings.html</span>
                    </div>

                    <div class="window-body">

                        <?php if(isset($_GET['password_updated'])){ ?>
                            <div class="alert alert-success">Password updated successfully.</div>
                        <?php } ?>

                        <?php if($passwordMessage !== ""){ ?>
                            <div class="alert alert-error"><?php echo htmlspecialchars($passwordMessage); ?></div>
                        <?php } ?>

                        <form method="POST">

                            <?php echo csrf_field(); ?>

                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password"
                                       id="current_password"
                                       name="current_password"
                                       autocomplete="current-password"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password"
                                       id="new_password"
                                       name="new_password"
                                       minlength="8"
                                       autocomplete="new-password"
                                       required>
                                <p class="form-hint">At least 8 characters.</p>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password"
                                       id="confirm_password"
                                       name="confirm_password"
                                       minlength="8"
                                       autocomplete="new-password"
                                       required>
                            </div>

                            <div class="form-actions">
                                <button class="btn" type="submit" name="change_password">Change Password</button>
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