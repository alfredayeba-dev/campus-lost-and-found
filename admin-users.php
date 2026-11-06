<?php

require_once("includes/security.php");
include("includes/admin-guard.php");

$myId = (int) $_SESSION['user_id'];

// ---------- Handle actions ----------
if($_SERVER['REQUEST_METHOD'] === 'POST'){

    if(!csrf_ok()){
        die("Invalid request.");
    }

    $target = (int) ($_POST['user_id'] ?? 0);

    if($target <= 0){
        $_SESSION['flash_error'] = "Invalid user.";

    }elseif($target === $myId){
        // Stops you locking yourself out
        $_SESSION['flash_error'] = "You can't change or delete your own account here.";

    }elseif(isset($_POST['set_role'])){

        $newRole = $_POST['role'] ?? '';

        if(in_array($newRole, ['student', 'admin'])){
            $stmt = mysqli_prepare($conn, "UPDATE users SET role = ? WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt, "si", $newRole, $target);
            mysqli_stmt_execute($stmt);
            $_SESSION['flash'] = "Role updated.";
        }

    }elseif(isset($_POST['delete'])){

        // Collect the user's photos so the files can be removed too
        $stmt = mysqli_prepare($conn, "SELECT image FROM items WHERE user_id = ? AND image IS NOT NULL");
        mysqli_stmt_bind_param($stmt, "i", $target);
        mysqli_stmt_execute($stmt);
        $imgResult = mysqli_stmt_get_result($stmt);

        $images = [];
        while($r = mysqli_fetch_assoc($imgResult)){
            $images[] = $r['image'];
        }

        // Delete their items first, then the user
        $stmt = mysqli_prepare($conn, "DELETE FROM items WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $target);
        mysqli_stmt_execute($stmt);

        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $target);
        mysqli_stmt_execute($stmt);

        foreach($images as $img){
            image_delete($img);
        }

        $_SESSION['flash'] = "User and their posts deleted.";
    }

    $search = trim($_GET['search'] ?? '');
    header("Location: admin-users.php" . ($search !== "" ? "?search=" . urlencode($search) : ""));
    exit();
}

$flash      = $_SESSION['flash'] ?? "";
$flashError = $_SESSION['flash_error'] ?? "";
unset($_SESSION['flash'], $_SESSION['flash_error']);

// ---------- Search ----------
$search = trim($_GET['search'] ?? '');

$sql = "SELECT users.*, COUNT(items.item_id) AS item_count
        FROM users
        LEFT JOIN items ON items.user_id = users.user_id";

if($search !== ""){
    $sql .= " WHERE users.first_name LIKE ? OR users.last_name LIKE ?
              OR users.email LIKE ? OR users.student_id LIKE ?";
}

$sql .= " GROUP BY users.user_id ORDER BY users.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if($search !== ""){
    $like = "%" . $search . "%";
    mysqli_stmt_bind_param($stmt, "ssss", $like, $like, $like, $like);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$count  = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include("includes/admin-nav.php"); ?>

    <section class="section hatch">
        <div class="container">

            <h1 style="font-size:48px; font-weight:800; margin:0 0 20px;">Manage Users.</h1>

            <?php if($flash !== ""){ ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($flash); ?></div>
            <?php } ?>

            <?php if($flashError !== ""){ ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($flashError); ?></div>
            <?php } ?>

            <form method="GET" class="filter-bar">
                <input type="text" name="search"
                       placeholder="Search name, email or student ID"
                       value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn" type="submit">Search</button>
                <a href="admin-users.php">Reset</a>
            </form>

            <p class="result-count">
                <?php echo $count; ?> user<?php echo $count === 1 ? "" : "s"; ?>
            </p>

            <div class="window">
                <div class="window-bar">
                    <span class="dot"></span><span class="dot"></span>
                    <span class="window-title">users.html</span>
                </div>

                <div class="table-wrap">
                    <table class="admin-table">
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Posts</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>

                        <?php while($u = mysqli_fetch_assoc($result)){ ?>
                            <?php $isMe = ((int) $u['user_id'] === $myId); ?>
                            <tr>
                                <td><?php echo htmlspecialchars($u['student_id'] ?? ''); ?></td>
                                <td>
                                    <?php
                                    echo htmlspecialchars(trim(
                                        $u['first_name'] . " " . ($u['middle_name'] ?? '') . " " . $u['last_name']
                                    ));
                                    ?>
                                    <?php if($isMe){ ?><small>(you)</small><?php } ?>
                                </td>
                                <td><?php echo htmlspecialchars($u['email'] ?? ''); ?></td>
                                <td>
                                    <span class="badge <?php echo $u['role'] === 'admin' ? 'badge-lost' : 'badge-found'; ?>">
                                        <?php echo htmlspecialchars($u['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo (int) $u['item_count']; ?></td>
                                <td><?php echo date("M j, Y", strtotime($u['created_at'])); ?></td>
                                <td>
                                    <?php if($isMe){ ?>
                                        &mdash;
                                    <?php }else{ ?>
                                        <div class="item-actions">

                                            <form method="POST" class="inline-form">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="user_id" value="<?php echo (int) $u['user_id']; ?>">
                                                <input type="hidden" name="role"
                                                       value="<?php echo $u['role'] === 'admin' ? 'student' : 'admin'; ?>">
                                                <button class="btn btn-small" type="submit" name="set_role" value="1">
                                                    <?php echo $u['role'] === 'admin' ? 'Make student' : 'Make admin'; ?>
                                                </button>
                                            </form>

                                            <form method="POST" class="inline-form"
                                                  onsubmit="return confirm('Delete this user AND all their posts?');">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="user_id" value="<?php echo (int) $u['user_id']; ?>">
                                                <button class="btn btn-small btn-danger" type="submit" name="delete" value="1">Delete</button>
                                            </form>

                                        </div>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>

                    </table>
                </div>
            </div>

        </div>
    </section>

    <footer class="footer">
        Campus Lost and Found System by Alfred Ayeba
    </footer>

</body>
</html>