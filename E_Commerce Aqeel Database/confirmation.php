<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit("Please login to view order details.");
}

// Check if order_id is set
$orderId = $_GET['order_id'] ?? $_SESSION['order_confirmation']['order_id'] ?? null;

if (!$orderId) {
    header("Location: checkout.php");
    exit("Order ID is missing.");
}

try {
    // Fetch order details with user verification
    $query = "
        SELECT o.*, u.email, u.first_name, u.last_name,
               sa.street, sa.city, sa.state, sa.postcode,
               p.payment_method, p.payment_status, p.transaction_id, p.amount,
               pm.name as payment_method_name
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        LEFT JOIN shipping_addresses sa ON o.id = sa.order_id
        LEFT JOIN payments p ON o.id = p.order_id
        LEFT JOIN payment_methods pm ON p.payment_method = pm.code
        WHERE o.id = :order_id AND o.user_id = :user_id
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'order_id' => $orderId,
        'user_id' => $_SESSION['user_id']
    ]);

    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception("Order not found or unauthorized access.");
    }

    // Format customer name
    $order['customer_name'] = trim($order['first_name'] . ' ' . $order['last_name']);

    // Fetch order items with product details
    $query = "
        SELECT oi.*, p.name, p.image, p.price as current_price
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = :order_id
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute(['order_id' => $orderId]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($orderItems)) {
        throw new Exception("No items found for this order.");
    }

    // Prepare order items for display
    $orderItems = array_map(function ($item) {
        return [
            'name' => $item['name'],
            'quantity' => $item['quantity'],
            'price' => $item['price'], // Using the price from order_items
            'image' => $item['image']
        ];
    }, $orderItems);
} catch (Exception $e) {
    error_log("Order confirmation error: " . $e->getMessage());
    header("Location: checkout.php");
    exit("An error occurred while confirming the order.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Qelado</title>
    <link rel="stylesheet" href="confirmation.css">
</head>

<body>
    <header>
        <h1>Thank You for Your Order!</h1>
    </header>

    <main>
        <section id="order-confirmation">
            <h2>Order #<?php echo htmlspecialchars($orderId); ?> Confirmed!</h2>
            <p>Status: <?php echo htmlspecialchars($order['status']); ?></p>

            <div class="confirmation-details">
                <div class="customer-info">
                    <h3>Customer Details</h3>
                    <p>Name: <?php echo htmlspecialchars($order['customer_name']); ?></p>
                    <p>Email: <?php echo htmlspecialchars($order['email']); ?></p>
                </div>

                <?php if ($order['street']): ?>
                    <div class="shipping-info">
                        <h3>Shipping Address</h3>
                        <p><?php echo htmlspecialchars($order['street']); ?></p>
                        <p><?php echo htmlspecialchars($order['city']); ?>,
                            <?php echo htmlspecialchars($order['state']); ?>
                            <?php echo htmlspecialchars($order['postcode']); ?></p>
                    </div>
                <?php endif; ?>

                <div class="payment-info">
                    <h3>Payment Information</h3>
                    <p>Method: <?php echo htmlspecialchars($order['payment_method_name']); ?></p>
                    <p>Status: <?php echo htmlspecialchars($order['payment_status']); ?></p>
                    <p>Total: RM<?php echo number_format($order['total'], 2); ?></p>
                </div>
            </div>

            <div class="order-summary">
                <h3>Order Summary</h3>
                <ul id="order-items">
                    <!-- Items will be populated via JavaScript -->
                </ul>
                <div class="order-totals">
                    <p>Total: <span id="order-total">RM <?php echo number_format($order['total'], 2); ?></span></p>
                </div>
            </div>

            <div class="action-buttons" style="margin-top: 20px; text-align: center;">
                <a href="inbox.php" class="view-inbox-btn" style="display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px;">View Order in Inbox</a>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 My E-commerce Website. All rights reserved.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const orderItems = <?php echo json_encode($orderItems); ?>;

            // Populate order items
            const orderItemsList = document.getElementById('order-items');
            orderItems.forEach(item => {
                const listItem = document.createElement('li');
                listItem.innerHTML = `
                    <img src="${item.image}" alt="${item.name}" 
                         style="width: 50px; height: 50px;" 
                         onerror="this.src='img/placeholder.png'">
                    <span class="item-details">
                        ${item.name}
                        <br>
                        ${item.quantity} x RM${parseFloat(item.price).toFixed(2)}
                    </span>
                `;
                orderItemsList.appendChild(listItem);
            });
        });
    </script>
</body>

</html>