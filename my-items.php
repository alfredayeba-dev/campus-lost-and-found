<?php

require_once("includes/security.php");

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

include("config/database.php");
require_once("includes/images.php");

$user_id = $_SESSION['user_id'];

// Handle status updates and deletes
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'])){

    csrf_require();

    $item_id = (int) $_POST['item_id'];

    if(isset($_POST['mark'])){

        $newStatus = $_POST['mark'];

        if(in_array($newStatus, ['claimed', 'returned'])){
            // user_id in the WHERE clause makes sure you can only edit your own posts
            $stmt = mysqli_prepare(
                $conn,
                "UPDATE items SET status = ? WHERE item_id = ? AND user_id = ?"
            );
            mysqli_stmt_bind_param($stmt, "sii", $newStatus, $item_id, $user_id);
            mysqli_stmt_execute($stmt);
        }

    }elseif(isset($_POST['delete'])){

        $stmt = mysqli_prepare(
            $conn,
            "SELECT image FROM items WHERE item_id = ? AND user_id = ?"
        );
        mysqli_stmt_bind_param($stmt, "ii", $item_id, $user_id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if($row){
            $stmt = mysqli_prepare(
                $conn,
                "DELETE FROM items WHERE item_id = ? AND user_id = ?"
            );
            mysqli_stmt_bind_param($stmt, "ii", $item_id, $user_id);
            mysqli_stmt_execute($stmt);

            image_delete($row['image']);
        }
    }

    header("Location: my-items.php");
    exit();
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM items WHERE user_id = ? ORDER BY created_at DESC"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$count  = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Posts</title>
    <?php include("includes/head.php"); ?>
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

            <a class="back-link" href="dashboard.php">&larr; Back to Dashboard</a>

            <h1 style="font-size:48px; font-weight:800; margin:0 0 20px;">My Posts.</h1>

            <?php if(isset($_GET['posted'])){ ?>
                <div class="alert alert-success">Your report was posted successfully.</div>
            <?php } ?>
            <?php if(isset($_GET['updated'])){ ?>
                <div class="alert alert-success">Item updated successfully.</div>
            <?php } ?>

            <?php if($count === 0){ ?>

                <div class="empty">
                    You haven't posted anything yet.
                    <br><br>
                    <a class="btn" href="report-lost-item.php">Report lost</a>
                    <a class="btn" href="report-found-item.php">Report found</a>
                </div>

            <?php }else{ ?>

                <div class="grid">
                    <?php while($item = mysqli_fetch_assoc($result)){ ?>

                        <div class="window item-card">
                            <div class="window-bar">
                                <span class="dot"></span><span class="dot"></span>
                                <span class="window-title">item-<?php echo (int) $item['item_id']; ?>.html</span>
                            </div>

                            <?php if(!empty($item['image'])){ ?>
                                <img class="item-image"
                                     src="<?php echo htmlspecialchars(image_url($item['image'])); ?>"
                                     alt="Item photo">
                            <?php }else{ ?>
                                <div class="item-placeholder">No photo</div>
                            <?php } ?>

                            <div class="window-body">

                                <span class="badge badge-<?php echo htmlspecialchars($item['status']); ?>">
                                    <?php echo htmlspecialchars($item['status']); ?>
                                </span>

                                <h3><?php echo htmlspecialchars($item['title']); ?></h3>

                                <ul class="meta">
                                    <li><strong>Category:</strong> <?php echo htmlspecialchars($item['category']); ?></li>
                                    <li><strong>Location:</strong> <?php echo htmlspecialchars($item['location']); ?></li>
                                    <li><strong>Date:</strong> <?php echo date("M j, Y", strtotime($item['created_at'])); ?></li>
                                </ul>

                                <div class="item-actions">
                                    <a class="btn" href="view-item.php?id=<?php echo (int) $item['item_id']; ?>">View</a>
                                    <a class="btn" href="edit-item.php?id=<?php echo (int)$item['item_id']; ?>">Edit</a>
                                    <?php if($item['status'] === 'lost'){ ?>
                                        <form method="POST">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="item_id" value="<?php echo (int) $item['item_id']; ?>">
                                            <button class="btn" type="submit" name="mark" value="returned">
                                                Mark as Returned
                                            </button>
                                        </form>
                                    <?php }elseif($item['status'] === 'found'){ ?>
                                        <form method="POST">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="item_id" value="<?php echo (int) $item['item_id']; ?>">
                                            <button class="btn" type="submit" name="mark" value="claimed">
                                                Mark as Claimed
                                            </button>
                                        </form>
                                    <?php } ?>

                                    <form method="POST" onsubmit="return confirm('Delete this post?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="item_id" value="<?php echo (int) $item['item_id']; ?>">
                                        <button class="btn btn-danger" type="submit" name="delete" value="1">
                                            Delete
                                        </button>
                                    </form>

                                </div>

                            </div>
                        </div>

                    <?php } ?>
                </div>

            <?php } ?>

        </div>
    </section>

    <footer class="footer">
        Campus Lost and Found System by Alfred Ayeba
    </footer>

</body>
</html>