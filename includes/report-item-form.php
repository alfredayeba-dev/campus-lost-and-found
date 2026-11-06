<?php
// Shared by report-lost-item.php and report-found-item.php
// Expects $type to be either 'lost' or 'found'

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

include("config/database.php");
require_once("includes/images.php");

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

if(isset($_POST['submit'])){

    csrf_require();

    $title       = trim($_POST['title'] ?? '');
    $category    = $_POST['category'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $user_id     = (int) $_SESSION['user_id'];
    $imageRef    = null;

    if($title === "" || $location === "" || !in_array($category, $categories)){
        $message = "Please fill in the title, category and location.";
    }

    // Optional photo
    if($message === ""){
        $uploaded = image_upload($_FILES['image'] ?? null, $imgError);

        if($uploaded === null){
            $message = $imgError;
        }elseif($uploaded !== "none"){
            $imageRef = $uploaded;
        }
    }

    if($message === ""){

        try{

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO items (title, category, description, status, location, image, user_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param(
                $stmt,
                "ssssssi",
                $title, $category, $description, $type, $location, $imageRef, $user_id
            );
            mysqli_stmt_execute($stmt);

            header("Location: my-items.php?posted=1");
            exit();

        }catch(mysqli_sql_exception $e){

            // Don't leave an orphaned photo behind
            image_delete($imageRef);
            $message = "Something went wrong. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Report <?php echo ucfirst($type); ?> Item</title>
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

                <h1 style="font-size:48px; font-weight:800; margin:0 0 20px;">
                    Report <?php echo ucfirst($type); ?> Item.
                </h1>

                <div class="window">
                    <div class="window-bar">
                        <span class="dot"></span><span class="dot"></span>
                        <span class="window-title">report-<?php echo $type; ?>.html</span>
                    </div>

                    <div class="window-body">

                        <?php if($message !== ""){ ?>
                            <div class="alert alert-error">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php } ?>

                        <form method="POST" enctype="multipart/form-data">

                            <?php echo csrf_field(); ?>

                            <div class="form-group">
                                <label for="title">Item Title *</label>
                                <input type="text" id="title" name="title" maxlength="150"
                                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="category">Category *</label>
                                <select id="category" name="category" required>
                                    <option value="">-- Select category --</option>
                                    <?php foreach($categories as $cat){ ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>"
                                            <?php if(($_POST['category'] ?? '') === $cat) echo "selected"; ?>>
                                            <?php echo htmlspecialchars($cat); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" rows="4"
                                          placeholder="Colour, brand, distinguishing marks..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="location">Where was it <?php echo $type; ?>? *</label>
                                <input type="text" id="location" name="location" maxlength="255"
                                       value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="image">Photo (optional, max 2MB)</label>
                                <input type="file" id="image" name="image"
                                       accept="image/jpeg,image/png,image/gif">
                            </div>

                            <div class="form-actions">
                                <button class="btn" type="submit" name="submit">
                                    Submit <?php echo ucfirst($type); ?> Report
                                </button>
                                <a href="dashboard.php">Cancel</a>
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