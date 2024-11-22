<?php
session_start();

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Calculate the total number of items in the cart
$cartItemCount = array_reduce($_SESSION['cart'], function ($total, $item) {
    return $total + $item['quantity'];
}, 0);

// Return the cart count as a JSON response
echo json_encode(['count' => $cartItemCount]);
