<?php
// Include right after session_start(). Blocks anyone who isn't an admin.

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

include_once("config/database.php");

// Check the role in the database every time, not just the session,
// so a demoted admin loses access immediately.
$guardStmt = mysqli_prepare($conn, "SELECT role FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($guardStmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($guardStmt);
$guardRow = mysqli_fetch_assoc(mysqli_stmt_get_result($guardStmt));

if(!$guardRow || $guardRow['role'] !== 'admin'){
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Access denied</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body>
        <section class="section hatch">
            <div class="container narrow">
                <div class="window">
                    <div class="window-bar">
                        <span class="dot"></span><span class="dot"></span>
                        <span class="window-title">403.html</span>
                    </div>
                    <div class="window-body">
                        <h2 class="heading">Access denied.</h2>
                        <p>This area is for administrators only.</p>
                        <a class="btn" href="dashboard.php">Back to Dashboard</a>
                    </div>
                </div>
            </div>
        </section>
    </body>
    </html>
    <?php
    exit();
}

$_SESSION['role'] = $guardRow['role'];
