<?php
// Database connection
$host = "localhost";
$dbname = "qelado";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

// Get the user data to edit
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("User not found.");
    }
}

// Handle form submission to update user data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $gender = $_POST['gender'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];
    $current_password = $_POST['current_password'];

    // Always update the user details (excluding password if not changed)
    $update_query = "UPDATE users SET first_name = ?, last_name = ?, gender = ?, email = ?, role = ? WHERE id = ?";
    $stmt = $pdo->prepare($update_query);
    $stmt->execute([$first_name, $last_name, $gender, $email, $role, $user_id]);

    // If new password fields are filled, verify current password and update the password
    if ($new_password && $confirm_new_password) {
        if (!password_verify($current_password, $user['password'])) {
            echo "<script>alert('Current password is incorrect!');</script>";
        } elseif ($new_password !== $confirm_new_password) {
            echo "<script>alert('New passwords do not match!');</script>";
        } else {
            // Hash the new password and update it
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_password_query = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $pdo->prepare($update_password_query);
            $stmt->execute([$hashed_new_password, $user_id]);
        }
    }

    header("Location: admin_users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <style>
        /* Styling */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fc;
            margin: 0;
            padding: 0;
        }

        .container {
            margin: 50px auto;
            width: 80%;
            max-width: 800px;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin: 10px 0 5px;
            font-size: 16px;
        }

        input, select {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #45a049;
        }

        .back-btn {
            width: auto;
            padding: 12px 24px;
            background-color: #ccc;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
        }

        .back-btn:hover {
            background-color: #888;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>Edit User</h2>
        <form method="POST" action="edit.php?id=<?= $user['id'] ?>">
            <label for="first_name">First Name</label>
            <input type="text" name="first_name" id="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>

            <label for="last_name">Last Name</label>
            <input type="text" name="last_name" id="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>

            <label for="gender">Gender</label>
            <select name="gender" id="gender" required>
                <option value="Male" <?= $user['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                <option value="Female" <?= $user['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
            </select>

            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" required>

            <label for="role">Role</label>
            <select name="role" id="role" required>
                <option value="Admin" <?= $user['role'] == 'Admin' ? 'selected' : '' ?>>Admin</option>
                <option value="User" <?= $user['role'] == 'User' ? 'selected' : '' ?>>User</option>
            </select>

            <!-- Current Password (only required if changing password) -->
            <label for="current_password">Current Password (Required to change password)</label>
            <input type="password" name="current_password" id="current_password">

            <label for="new_password">New Password</label>
            <input type="password" name="new_password" id="new_password">

            <label for="confirm_new_password">Confirm New Password</label>
            <input type="password" name="confirm_new_password" id="confirm_new_password">

            <button type="submit" class="btn">Update User</button>
        </form>

        <!-- Back Button -->
        <a href="admin_users.php"><button class="back-btn">Back to Users List</button></a>
    </div>

</body>
</html>
