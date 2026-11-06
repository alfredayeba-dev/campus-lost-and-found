<?php

require_once("includes/security.php");
include("includes/admin-guard.php");

// Item counts by status
$counts = ['lost' => 0, 'found' => 0, 'claimed' => 0, 'returned' => 0];

$countResult = mysqli_query($conn, "SELECT status, COUNT(*) AS total FROM items GROUP BY status");
while($row = mysqli_fetch_assoc($countResult)){
    if(isset($counts[$row['status']])){
        $counts[$row['status']] = (int) $row['total'];
    }
}

$totalItems = array_sum($counts);

$totalUsers = (int) mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM users")
)['total'];

$totalAdmins = (int) mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='admin'")
)['total'];

$stats = [
    ['users.html',    $totalUsers,         'Users'],
    ['admins.html',   $totalAdmins,        'Admins'],
    ['total.html',    $totalItems,         'Total items'],
    ['lost.html',     $counts['lost'],     'Lost'],
    ['found.html',    $counts['found'],    'Found'],
    ['claimed.html',  $counts['claimed'],  'Claimed'],
    ['returned.html', $counts['returned'], 'Returned'],
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin</title>
    <?php include("includes/head.php"); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include("includes/admin-nav.php"); ?>

    <section class="section hatch">
        <div class="container">

            <h1 style="font-size:48px; font-weight:800; margin:0 0 20px;">Admin.</h1>

            <div class="grid stats-grid">
                <?php foreach($stats as $s){ ?>
                    <div class="window">
                        <div class="window-bar">
                            <span class="dot"></span><span class="dot"></span>
                            <span class="window-title"><?php echo $s[0]; ?></span>
                        </div>
                        <div class="window-body">
                            <p class="stat-number"><?php echo $s[1]; ?></p>
                            <p class="stat-label"><?php echo $s[2]; ?></p>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <div class="quicklinks-title">Manage</div>
            <div class="quicklinks">
                <a href="admin-items.php">Manage items <span>&#8599;</span></a>
                <a href="admin-users.php">Manage users <span>&#8599;</span></a>
            </div>

        </div>
    </section>

    <footer class="footer">
        Campus Lost and Found System by Alfred Ayeba
    </footer>

</body>
</html>