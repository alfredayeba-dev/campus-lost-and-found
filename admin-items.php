<?php

session_start();
include("includes/admin-guard.php");

$validStatuses = ['lost', 'found', 'claimed', 'returned'];

// ---------- Handle actions ----------
if($_SERVER['REQUEST_METHOD'] === 'POST'){

    if(!csrf_ok()){
        die("Invalid request.");
    }

    $item_id = (int) ($_POST['item_id'] ?? 0);

    if($item_id > 0 && isset($_POST['set_status'])){

        $newStatus = $_POST['status'] ?? '';

        if(in_array($newStatus, $validStatuses)){
            $stmt = mysqli_prepare($conn, "UPDATE items SET status = ? WHERE item_id = ?");
            mysqli_stmt_bind_param($stmt, "si", $newStatus, $item_id);
            mysqli_stmt_execute($stmt);
            $_SESSION['flash'] = "Status updated.";
        }

    }elseif($item_id > 0 && isset($_POST['delete'])){

        $stmt = mysqli_prepare($conn, "SELECT image FROM items WHERE item_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $item_id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if($row){
            $stmt = mysqli_prepare($conn, "DELETE FROM items WHERE item_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $item_id);
            mysqli_stmt_execute($stmt);

            if(!empty($row['image']) && file_exists("uploads/" . $row['image'])){
                unlink("uploads/" . $row['image']);
            }
            $_SESSION['flash'] = "Item deleted.";
        }
    }

    // Go back to the same filtered view
    $back = array_filter([
        'search' => $_GET['search'] ?? '',
        'status' => $_GET['status'] ?? ''
    ], fn($v) => $v !== '');

    header("Location: admin-items.php" . ($back ? "?" . http_build_query($back) : ""));
    exit();
}

$flash = $_SESSION['flash'] ?? "";
unset($_SESSION['flash']);

// ---------- Search and filter ----------
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

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
    <title>Manage Items</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include("includes/admin-nav.php"); ?>

    <section class="section hatch">
        <div class="container">

            <h1 style="font-size:48px; font-weight:800; margin:0 0 20px;">Manage Items.</h1>

            <?php if($flash !== ""){ ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($flash); ?></div>
            <?php } ?>

            <form method="GET" class="filter-bar">
                <input type="text" name="search"
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
                <a href="admin-items.php">Reset</a>
            </form>

            <p class="result-count">
                <?php echo $count; ?> item<?php echo $count === 1 ? "" : "s"; ?>
            </p>

            <?php if($count === 0){ ?>

                <div class="empty">No items found.</div>

            <?php }else{ ?>

                <div class="window">
                    <div class="window-bar">
                        <span class="dot"></span><span class="dot"></span>
                        <span class="window-title">items.html</span>
                    </div>

                    <div class="table-wrap">
                        <table class="admin-table">
                            <tr>
                                <th>Photo</th>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Posted by</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>

                            <?php while($item = mysqli_fetch_assoc($result)){ ?>
                                <tr>
                                    <td>
                                        <?php if(!empty($item['image'])){ ?>
                                            <img class="thumb"
                                                 src="uploads/<?php echo htmlspecialchars($item['image']); ?>"
                                                 alt="">
                                        <?php }else{ ?>
                                            &mdash;
                                        <?php } ?>
                                    </td>

                                    <td>
                                        <strong><?php echo htmlspecialchars($item['title']); ?></strong><br>
                                        <small>
                                            <?php echo htmlspecialchars($item['category']); ?>
                                            &middot;
                                            <?php echo htmlspecialchars($item['location']); ?>
                                        </small>
                                    </td>

                                    <td>
                                        <form method="POST" class="inline-form">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="item_id" value="<?php echo (int) $item['item_id']; ?>">
                                            <select name="status">
                                                <?php foreach($validStatuses as $s){ ?>
                                                    <option value="<?php echo $s; ?>"
                                                        <?php if($item['status'] === $s) echo "selected"; ?>>
                                                        <?php echo ucfirst($s); ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                            <button class="btn btn-small" type="submit" name="set_status" value="1">Save</button>
                                        </form>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars(trim(($item['first_name'] ?? '') . " " . ($item['last_name'] ?? ''))); ?>
                                        <br><small><?php echo htmlspecialchars($item['email'] ?? ''); ?></small>
                                    </td>

                                    <td><?php echo date("M j, Y", strtotime($item['created_at'])); ?></td>

                                    <td>
                                        <div class="item-actions">
                                            <a class="btn btn-small"
                                               href="view-item.php?id=<?php echo (int) $item['item_id']; ?>">View</a>

                                            <form method="POST" class="inline-form"
                                                  onsubmit="return confirm('Delete this item permanently?');">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="item_id" value="<?php echo (int) $item['item_id']; ?>">
                                                <button class="btn btn-small btn-danger" type="submit" name="delete" value="1">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>

                        </table>
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