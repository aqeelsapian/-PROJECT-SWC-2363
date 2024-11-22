<?php
session_start();
require_once 'config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_user'])) {
        // Add user logic
        $firstName = $_POST['first_name'];
        $lastName = $_POST['last_name'];
        $gender = $_POST['gender'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, gender, email, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$firstName, $lastName, $gender, $email, $password]);
    } elseif (isset($_POST['update_user'])) {
        // Update user logic
        $id = $_POST['id'];
        $firstName = $_POST['first_name'];
        $lastName = $_POST['last_name'];
        $gender = $_POST['gender'];
        $email = $_POST['email'];

        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, gender = ?, email = ? WHERE id = ?");
        $stmt->execute([$firstName, $lastName, $gender, $email, $id]);
    } elseif (isset($_POST['delete_user'])) {
        // Delete user logic
        $id = $_POST['id'];

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
    }
}

// Fetch users
$search = $_GET['search'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM users WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ?");
$stmt->execute(["%$search%", "%$search%", "%$search%"]);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
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
            <header class="mb-4">
                <h1 class="text-center">Admin Dashboard</h1>
            </header>
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title">User Management</h2>
                    <form method="GET" action="admin.php" class="form-inline mb-3">
                        <input type="text" name="search" class="form-control mr-2" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </form>
                    <table class="table table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Gender</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['id']) ?></td>
                                    <td><?= htmlspecialchars($user['first_name']) ?></td>
                                    <td><?= htmlspecialchars($user['last_name']) ?></td>
                                    <td><?= htmlspecialchars($user['gender']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                            <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                            <button type="submit" name="delete_user" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                        <form method="POST" action="edit_user.php" style="display:inline;">
                                            <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="btn btn-warning btn-sm">Edit</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <h3 class="mt-4">Add User</h3>
                    <form method="POST" action="admin.php" class="form-row">
                        <div class="form-group col-md-2">
                            <input type="text" name="first_name" class="form-control" placeholder="First Name" required>
                        </div>
                        <div class="form-group col-md-2">
                            <input type="text" name="last_name" class="form-control" placeholder="Last Name" required>
                        </div>
                        <div class="form-group col-md-2">
                            <input type="text" name="gender" class="form-control" placeholder="Gender" required>
                        </div>
                        <div class="form-group col-md-3">
                            <input type="email" name="email" class="form-control" placeholder="Email" required>
                        </div>
                        <div class="form-group col-md-2">
                            <input type="password" name="password" class="form-control" placeholder="Password" required>
                        </div>
                        <div class="form-group col-md-1">
                            <button type="submit" name="add_user" class="btn btn-success">Add User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>