<?php

require_once("includes/security.php");

// Check login first, before running any queries
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

include("config/database.php");

// Count items by status in one query
$counts = ['lost' => 0, 'found' => 0, 'claimed' => 0, 'returned' => 0];

$countResult = mysqli_query(
    $conn,
    "SELECT status, COUNT(*) AS total FROM items GROUP BY status"
);

while($row = mysqli_fetch_assoc($countResult)){
    if(isset($counts[$row['status']])){
        $counts[$row['status']] = (int) $row['total'];
    }
}

$totalLost     = $counts['lost'];
$totalFound    = $counts['found'];
$totalReturned = $counts['returned'];
$totalClaimed  = $counts['claimed'];
$totalItems    = array_sum($counts);

// Latest 3 posts for the "Recent reports" section
$recent = mysqli_query(
    $conn,
    "SELECT item_id, title, category, status, location, image
     FROM items
     ORDER BY created_at DESC
     LIMIT 3"
);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- Top navigation -->
    <nav class="topbar">
        <a href="dashboard.php">Dashboard</a>
        <a href="report-lost-item.php">Report lost</a>
        <a href="report-found-item.php">Report found</a>
        <a href="view-items.php">All items</a>
        <a href="my-items.php">My posts</a>
        <?php if(($_SESSION['role'] ?? '') === 'admin'){ ?>
            <a href="admin.php">Admin</a>
        <?php } ?>
        <a href="logout.php" class="nav-logout">Logout</a>
    </nav>

   <!-- Hero -->
    <section class="section hero">
        <div class="container">

            <div class="hero-flex">

                <div class="hero-text">
                    <h1>Hello,<br><?php echo htmlspecialchars($_SESSION['first_name']); ?>.</h1>
                    <p>Welcome to the Campus Lost and Found System. Report something you lost, or help someone find what they've lost.</p>
                    <a class="btn" href="report-lost-item.php">Report an item</a>
                </div>

                <div class="hero-art">
                    <img src="images/map-icon.png" alt="Map pin illustration">
                </div>

            </div>

            <div class="quicklinks-title">Quick links</div>
            <div class="quicklinks">
                <a href="report-lost-item.php">Report Lost <span>&#8599;</span></a>
                <a href="report-found-item.php">Report Found <span>&#8599;</span></a>
                <a href="view-items.php">View Items <span>&#8599;</span></a>
                <a href="my-items.php">My Posts <span>&#8599;</span></a>
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section class="section hatch">
        <div class="container">
            <h2 class="heading">Overview</h2>

            <!-- CHANGED: added the stats-grid class -->
            <div class="grid stats-grid">

                <div class="window">
                    <div class="window-bar">
                        <span class="dot"></span><span class="dot"></span>
                        <span class="window-title">total-items.html</span>
                    </div>
                    <div class="window-body">
                        <p class="stat-number"><?php echo $totalItems; ?></p>
                        <p class="stat-label">Total items</p>
                    </div>
                </div>

                <div class="window">
                    <div class="window-bar">
                        <span class="dot"></span><span class="dot"></span>
                        <span class="window-title">lost-items.html</span>
                    </div>
                    <div class="window-body">
                        <p class="stat-number"><?php echo $totalLost; ?></p>
                        <p class="stat-label">Lost items</p>
                    </div>
                </div>

                <div class="window">
                    <div class="window-bar">
                        <span class="dot"></span><span class="dot"></span>
                        <span class="window-title">found-items.html</span>
                    </div>
                    <div class="window-body">
                        <p class="stat-number"><?php echo $totalFound; ?></p>
                        <p class="stat-label">Found items</p>
                    </div>
                </div>

                <div class="window">
                    <div class="window-bar">
                        <span class="dot"></span><span class="dot"></span>
                        <span class="window-title">returned-items.html</span>
                    </div>
                    <div class="window-body">
                        <p class="stat-number"><?php echo $totalReturned; ?></p>
                        <p class="stat-label">Returned items</p>
                    </div>
                </div>

                <div class="window">
                    <div class="window-bar">
                        <span class="dot"></span><span class="dot"></span>
                        <span class="window-title">claimed-items.html</span>
                    </div>
                    <div class="window-body">
                        <p class="stat-number"><?php echo $totalClaimed; ?></p>
                        <p class="stat-label">Claimed items</p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Recent reports -->
    <section class="section">
        <div class="container">
            <h2 class="heading">Recent reports</h2>
            <p><a href="view-items.php">View all items</a></p>

            <?php if(mysqli_num_rows($recent) === 0){ ?>

                <div class="empty">Nothing has been reported yet. Be the first!</div>

            <?php }else{ ?>

                <div class="grid">
                    <?php while($item = mysqli_fetch_assoc($recent)){ ?>
                        <div class="window item-card">
                            <div class="window-bar">
                                <span class="dot"></span><span class="dot"></span>
                                <span class="window-title">item-<?php echo (int) $item['item_id']; ?>.html</span>
                            </div>

                            <?php if(!empty($item['image'])){ ?>
                                <img class="item-image"
                                     src="uploads/<?php echo htmlspecialchars($item['image']); ?>"
                                     alt="Item photo">
                            <?php }else{ ?>
                                <div class="item-placeholder">No photo</div>
                            <?php } ?>

                            <div class="window-body">
                                <span class="badge badge-<?php echo htmlspecialchars($item['status']); ?>">
                                    <?php echo htmlspecialchars($item['status']); ?>
                                </span>
                                <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                                <p>
                                    <?php echo htmlspecialchars($item['category']); ?>
                                    &middot;
                                    <?php echo htmlspecialchars($item['location']); ?>
                                </p>
                                <a class="btn" href="view-item.php?id=<?php echo (int) $item['item_id']; ?>">View item</a>
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