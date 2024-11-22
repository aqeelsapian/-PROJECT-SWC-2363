<?php
session_start();
require_once 'config.php';

// Core functions
function fetchCartItems($pdo, $userId)
{
  $stmt = $pdo->prepare("
        SELECT c.*, p.name, p.price, p.image 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
  $stmt->execute([$userId]);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function processOrder($pdo, $userId, $orderData)
{
  try {
    $pdo->beginTransaction();

    // 1. Create order
    $stmt = $pdo->prepare("
            INSERT INTO orders (user_id, total, status, created_at) 
            VALUES (?, ?, 'Pending', NOW())
        ");
    $stmt->execute([$userId, $orderData['total']]);
    $orderId = $pdo->lastInsertId();

    // 2. Create order items
    $stmt = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price) 
            VALUES (?, ?, ?, ?)
        ");
    foreach ($orderData['items'] as $item) {
      $stmt->execute([
        $orderId,
        $item['product_id'],
        $item['quantity'],
        $item['price']
      ]);
    }

    // 3. Create shipping address
    $stmt = $pdo->prepare("
            INSERT INTO shipping_addresses 
            (order_id, street, city, state, postcode, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
    $stmt->execute([
      $orderId,
      $orderData['address']['street'],
      $orderData['address']['city'],
      $orderData['address']['state'],
      $orderData['address']['postcode']
    ]);

    // 4. Create payment record
    $transactionId = 'TXN_' . uniqid();
    $stmt = $pdo->prepare("
            INSERT INTO payments 
            (order_id, payment_method, payment_status, amount, transaction_id, created_at) 
            VALUES (?, ?, 'Pending', ?, ?, NOW())
        ");
    $stmt->execute([
      $orderId,
      $orderData['payment_method'],
      $orderData['total'],
      $transactionId
    ]);

    // 5. Clear cart
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);

    $pdo->commit();
    return $orderId;
  } catch (Exception $e) {
    $pdo->rollBack();
    throw $e;
  }
}

// Main logic
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

$userId = $_SESSION['user_id'];
$cartItems = fetchCartItems($pdo, $userId);

if (empty($cartItems)) {
  header('Location: cart.php');
  exit;
}

$totalPrice = array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $cartItems));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    // Validate input
    $required = ['name', 'email', 'phone', 'payment'];
    foreach ($required as $field) {
      if (empty($_POST[$field])) {
        throw new Exception("Please fill in all required fields");
      }
    }

    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
      throw new Exception("Invalid email format");
    }

    // Process order
    $orderData = [
      'items' => $cartItems,
      'total' => $totalPrice,
      'payment_method' => $_POST['payment'],
      'address' => $_POST['address']
    ];

    $orderId = processOrder($pdo, $userId, $orderData);

    // Store confirmation details
    $_SESSION['order_confirmation'] = [
      'order_id' => $orderId,
      'total' => $totalPrice,
      'items' => $cartItems
    ];

    header("Location: confirmation.php?order_id=$orderId");
    exit;
  } catch (Exception $e) {
    $error = $e->getMessage();
  }
}
?>

<!-- Simplified HTML structure -->
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout - Qelado</title>
  <link rel="stylesheet" href="checkout.css">
</head>

<body>

  <main>
    <!-- Error display -->
    <?php if (isset($error)): ?>
      <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" id="checkout-form">
      <!-- Order Summary -->
      <section class="order-summary">
        <h2>Order Summary</h2>
        <?php foreach ($cartItems as $item): ?>
          <div class="item">
            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="">
            <div>
              <h3><?php echo htmlspecialchars($item['name']); ?></h3>
              <p>Size: <?php echo htmlspecialchars($item['size']); ?></p>
              <p>Color: <?php echo htmlspecialchars($item['color']); ?></p>
              <p>Quantity: <?php echo $item['quantity']; ?></p>
              <p>Price: RM<?php echo number_format($item['price'], 2); ?></p>
            </div>
          </div>
        <?php endforeach; ?>
        <div class="total">
          <h3>Total: RM<?php echo number_format($totalPrice, 2); ?></h3>
        </div>
      </section>

      <!-- Customer Information -->
      <section class="customer-info">
        <h2>Customer Information</h2>
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="tel" name="phone" placeholder="Phone" required>
      </section>

      <!-- Shipping Address -->
      <section class="shipping">
        <h2>Shipping Address</h2>
        <input type="text" name="address[street]" placeholder="Street Address" required>
        <input type="text" name="address[city]" placeholder="City" required>
        <select name="address[state]" required>
          <option value="">Select State</option>
          <option value="Selangor">Selangor</option>
          <option value="Selangor">Johor</option>
          <option value="Selangor">Kedah</option>
          <option value="Selangor">Kelantan</option>
          <option value="Selangor">Melaka</option>
          <option value="Selangor">Negeri Sembilan</option>
          <option value="Selangor">Wilayah Persekutuan</option>
          <option value="Selangor">Pahang</option>
          <option value="Selangor">Pulau Pinang</option>
          <option value="Selangor">Perak</option>
          <option value="Selangor">Perlis</option>
          <option value="Selangor">Sabah</option>
          <option value="Selangor">Sarawak</option>
          <option value="Selangor">Terengganu</option>
          <!-- Add other states -->
        </select>
        <input type="text" name="address[postcode]" placeholder="Postcode" required>
      </section>

      <!-- Payment Method -->
      <section class="payment">
        <h2>Payment Method</h2>
        <div class="payment-options">
          <label>
            <input type="radio" name="payment" value="touch-n-go" required>
            Touch 'n Go eWallet
          </label>
          <label>
            <input type="radio" name="payment" value="credit-card">
            Credit Card
          </label>
          <label>
            <input type="radio" name="payment" value="online-banking">
            Online Banking
          </label>
        </div>
      </section>

      <button type="submit">Place Order (RM<?php echo number_format($totalPrice, 2); ?>)</button>
    </form>
  </main>

  <script>
    document.getElementById('checkout-form').addEventListener('submit', function(e) {
      const btn = this.querySelector('button[type="submit"]');
      btn.disabled = true;
      btn.textContent = 'Processing...';
    });
  </script>
</body>

</html>