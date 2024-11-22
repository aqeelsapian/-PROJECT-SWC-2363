<?php
session_start();
require_once 'config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get user data
if (isset($_POST['id'])) {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: admin.php");
        exit();
    }
} else {
    header("Location: admin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admin_user.css">
</head>

<body>
    <div class="d-flex">
        <nav class="sidebar bg-light">
            <ul class="list-unstyled">
                <li><a href="admin.php">Dashboard</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="orders.php">Orders</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        <div class="container mt-5">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title">Edit User</h2>
                    <form method="POST" action="admin.php" class="form-row">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">

                        <div class="form-group col-md-6">
                            <label>First Name</label>
                            <input type="text" name="first_name" class="form-control"
                                value="<?= htmlspecialchars($user['first_name']) ?>" required>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Last Name</label>
                            <input type="text" name="last_name" class="form-control"
                                value="<?= htmlspecialchars($user['last_name']) ?>" required>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Gender</label>
                            <input type="text" name="gender" class="form-control"
                                value="<?= htmlspecialchars($user['gender']) ?>" required>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control"
                                value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>

                        <div class="form-group col-12">
                            <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                            <a href="admin.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>