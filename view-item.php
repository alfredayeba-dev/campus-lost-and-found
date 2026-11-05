<?php

session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

include("config/database.php");

$item_id = (int) ($_GET['id'] ?? 0);

$item = null;

if($item_id > 0){
    $stmt = mysqli_prepare(
        $conn,
        "SELECT items.*, users.first_name, users.last_name, users.email
         FROM items
         LEFT JOIN users ON items.user_id = users.user_id
         WHERE items.item_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $item_id);
    mysqli_stmt_execute($stmt);
    $item = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

$isOwner = $item && ((int) $item['user_id'] === (int) $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $item ? htmlspecialchars($item['title']) : "Item not found"; ?></title>
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
        <a href="logout.php" class="nav-logout">Logout</a>
    </nav>

    <section class="section hatch">
        <div class="container">

            <a class="back-link" href="view-items.php">&larr; Back to All Items</a>

            <?php if(!$item){ ?>

                <h1 style="font-size:48px; font-weight:800; margin:0 0 20px;">Not found.</h1>
                <div class="empty">This item doesn't exist or has been deleted.</div>

            <?php }else{ ?>

                <div class="window">
                    <div class="window-bar">
                        <span class="dot"></span><span class="dot"></span>
                        <span class="window-title">item-<?php echo (int) $item['item_id']; ?>.html</span>
                    </div>

                    <div class="detail-layout">

                        <div class="detail-media">
                            <?php if(!empty($item['image'])){ ?>
                                <img class="detail-image"
                                     src="uploads/<?php echo htmlspecialchars($item['image']); ?>"
                                     alt="Item photo">
                            <?php }else{ ?>
                                <div class="detail-placeholder">No photo</div>
                            <?php } ?>
                        </div>

                        <div class="detail-info">

                            <span class="badge badge-<?php echo htmlspecialchars($item['status']); ?>">
                                <?php echo htmlspecialchars($item['status']); ?>
                            </span>

                            <h1 class="detail-title"><?php echo htmlspecialchars($item['title']); ?></h1>

                            <?php if(!empty($item['description'])){ ?>
                                <p class="detail-desc">
                                    <?php echo nl2br(htmlspecialchars($item['description'])); ?>
                                </p>
                            <?php } ?>

                            <ul class="meta">
                                <li><strong>Category:</strong> <?php echo htmlspecialchars($item['category']); ?></li>
                                <li><strong>Location:</strong> <?php echo htmlspecialchars($item['location']); ?></li>
                                <li>
                                    <strong>Posted by:</strong>
                                    <?php
                                    echo htmlspecialchars(
                                        trim(($item['first_name'] ?? '') . " " . ($item['last_name'] ?? ''))
                                    );
                                    ?>
                                </li>
                                <?php if(!empty($item['email'])){ ?>
                                    <li>
                                        <strong>Contact:</strong>
                                        <a href="mailto:<?php echo htmlspecialchars($item['email']); ?>">
                                            <?php echo htmlspecialchars($item['email']); ?>
                                        </a>
                                    </li>
                                <?php } ?>
                                <li><strong>Posted:</strong> <?php echo date("M j, Y, g:i A", strtotime($item['created_at'])); ?></li>
                            </ul>

                            <div class="item-actions">
                                <?php if(!empty($item['email']) && !$isOwner){ ?>
                                    <a class="btn"
                                       href="mailto:<?php echo htmlspecialchars($item['email']); ?>?subject=<?php echo rawurlencode('Regarding: ' . $item['title']); ?>">
                                        Contact poster
                                    </a>
                                <?php } ?>

                                <?php if($isOwner){ ?>
                                    <a class="btn" href="my-items.php">Manage in My Posts</a>
                                <?php } ?>
                            </div>

                        </div>
                    </div>
                </div>

            <?php } ?>

        </div>
    </section>

    <footer class="footer">
        Campus Lost and Found System by Alfred Ayeba
    </footer>

</body>
</html>