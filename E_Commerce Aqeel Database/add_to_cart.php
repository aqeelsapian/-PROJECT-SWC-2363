<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id']; // Assuming user_id is stored in session
    $productId = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $size = $_POST['size'];
    $color = $_POST['color'];

    // Insert into cart
    $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, size, color, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    if ($stmt->execute([$userId, $productId, $quantity, $size, $color])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add to cart']);
    }
}
