<?php

require_once("includes/security.php");

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

include("config/database.php");
require_once("includes/images.php");

$user_id = $_SESSION['user_id'];
$message = "";

$categories = [
    "Electronics",
    "Books & Stationery",
    "Clothing",
    "Bags",
    "ID Cards & Documents",
    "Keys",
    "Wallets & Money",
    "Other"
];

$item_id = (int)($_GET['id'] ?? 0);

if($item_id <= 0){
    header("Location: my-items.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| GET ITEM
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM items
     WHERE item_id = ?
     AND user_id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $item_id,
    $user_id
);

mysqli_stmt_execute($stmt);

$item = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

if(!$item){
    die("Item not found.");
}

/*
|--------------------------------------------------------------------------
| UPDATE ITEM
|--------------------------------------------------------------------------
*/

if(isset($_POST['submit'])){

    csrf_require();

    $title       = trim($_POST['title']);
    $category    = $_POST['category'];
    $description = trim($_POST['description']);
    $location    = trim($_POST['location']);

    if(
        $title === "" ||
        $location === "" ||
        !in_array($category, $categories)
    ){
        $message = "Please complete all required fields.";
    }

    if($message === ""){

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE items
             SET
                title = ?,
                category = ?,
                description = ?,
                location = ?
             WHERE item_id = ?
             AND user_id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ssssii",
            $title,
            $category,
            $description,
            $location,
            $item_id,
            $user_id
        );

        if(mysqli_stmt_execute($stmt)){

            header("Location: my-items.php?updated=1");
            exit();

        }else{

            $message = "Something went wrong. Please try again.";

        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Item</title>
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

            <a class="back-link" href="my-items.php">
                &larr; Back to My Posts
            </a>

            <h1 style="font-size:48px; font-weight:800; margin:0 0 20px;">
                Edit Item.
            </h1>

            <div class="window">

                <div class="window-bar">
                    <span class="dot"></span>
                    <span class="dot"></span>
                    <span class="window-title">edit-item.html</span>
                </div>

                <div class="window-body">

                    <?php if($message !== ""){ ?>
                        <div class="alert alert-error">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php } ?>

                    <?php if(!empty($item['image'])){ ?>
                        <div class="form-group">

                            <label>Current Photo</label>

                            <img
                                src="<?php echo htmlspecialchars(image_url($item['image'])); ?>"
                                alt="Item image"
                                style="
                                    width:100%;
                                    max-width:350px;
                                    border-radius:12px;
                                    display:block;
                                "
                            >

                        </div>
                    <?php } ?>

                    <form method="POST">

                        <?php echo csrf_field(); ?>

                        <div class="form-group">
                            <label for="title">
                                Item Title *
                            </label>

                            <input
                                type="text"
                                id="title"
                                name="title"
                                maxlength="150"
                                value="<?php echo htmlspecialchars($item['title']); ?>"
                                required>
                        </div>

                        <div class="form-group">

                            <label for="category">
                                Category *
                            </label>

                            <select
                                id="category"
                                name="category"
                                required>

                                <?php foreach($categories as $cat){ ?>

                                    <option
                                        value="<?php echo htmlspecialchars($cat); ?>"
                                        <?php if($item['category'] === $cat) echo "selected"; ?>>

                                        <?php echo htmlspecialchars($cat); ?>

                                    </option>

                                <?php } ?>

                            </select>

                        </div>

                        <div class="form-group">

                            <label for="description">
                                Description
                            </label>

                            <textarea
                                id="description"
                                name="description"
                                rows="4"
                                placeholder="Colour, brand, distinguishing marks..."><?php echo htmlspecialchars($item['description']); ?></textarea>

                        </div>

                        <div class="form-group">

                            <label for="location">
                                Location *
                            </label>

                            <input
                                type="text"
                                id="location"
                                name="location"
                                maxlength="255"
                                value="<?php echo htmlspecialchars($item['location']); ?>"
                                required>

                        </div>

                        <div class="form-actions">

                            <button
                                class="btn"
                                type="submit"
                                name="submit">

                                Save Changes

                            </button>

                            <a href="my-items.php">
                                Cancel
                            </a>

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