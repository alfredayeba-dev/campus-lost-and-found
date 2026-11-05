<?php

session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

include("config/database.php");

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

$validStatuses = ['lost', 'found', 'claimed', 'returned'];

$sql = "SELECT items.*, users.first_name, users.last_name, users.email
        FROM items
        LEFT JOIN users ON items.user_id = users.user_id
        WHERE 1=1";

$types  = "";
$params = [];

if($status !== "" && in_array($status, $validStatuses)){
    $sql .= " AND items.status = ?";
    $types .= "s";
    $params[] = $status;
}

if($search !== ""){
    $sql .= " AND (items.title LIKE ? OR items.description LIKE ? OR items.location LIKE ?)";
    $like = "%" . $search . "%";
    $types .= "sss";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY items.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);

if(!empty($params)){
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$count  = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html>
<head>
    <title>All Items</title>
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
        <a href="logout.php">Logout</a>
    </nav>

    <section class="section hatch">
        <div class="container">

            <a class="back-link" href="dashboard.php">&larr; Back to Dashboard</a>

            <h1 style="font-size:48px; font-weight:800; margin:0 0 20px;">All Items.</h1>

            <form method="GET" class="filter-bar">

                <input type="text"
                       name="search"
                       placeholder="Search title, description or location"
                       value="<?php echo htmlspecialchars($search); ?>">

                <select name="status">
                    <option value="">All statuses</option>
                    <?php foreach($validStatuses as $s){ ?>
                        <option value="<?php echo $s; ?>" <?php if($status === $s) echo "selected"; ?>>
                            <?php echo ucfirst($s); ?>
                        </option>
                    <?php } ?>
                </select>

                <button class="btn" type="submit">Filter</button>
                <a href="view-items.php">Reset</a>

            </form>

            <p class="result-count">
                <?php echo $count; ?> item<?php echo $count === 1 ? "" : "s"; ?> found
            </p>

            <?php if($count === 0){ ?>

                <div class="empty">No items found.</div>

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

                                <?php if(!empty($item['description'])){ ?>
                                    <p class="item-desc">
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
                                    <li><strong>Date:</strong> <?php echo date("M j, Y", strtotime($item['created_at'])); ?></li>
                                </ul>
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