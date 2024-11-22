<?php
session_start();
require_once 'config.php';

// Function to fetch cart items for a user
function fetchCartItems($pdo, $userId)
{
    try {
        $stmt = $pdo->prepare("
            SELECT cart.id, cart.user_id, cart.product_id, cart.quantity, cart.size, cart.color, cart.created_at,
                   products.name, products.description, products.price, products.image
            FROM cart
            JOIN products ON cart.product_id = products.id
            WHERE cart.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        echo "Error fetching cart items: " . $e->getMessage();
        exit;
    }
}

// Check if user ID is set in the session
if (!isset($_SESSION['user_id'])) {
    echo "User not logged in.";
    exit;
}

$userId = $_SESSION['user_id'];
$cartItems = fetchCartItems($pdo, $userId);

// Check if cart items are fetched
if (empty($cartItems)) {
    echo "No items in cart.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="cart.css" />
    <link rel="icon" type="image/png" href="img/logo 64px.png" />
</head>

<body>
    <header>
        <div class="header-container">
            <!-- Logo Section -->
            <div class="logo">
                <a href="index.php">
                    <img src="img/logo 64px.png" alt="Clothing Store Logo" width="100" />
                    <!-- Adjust width as needed -->
                </a>
            </div>

            <!-- Navigation Section -->
            <nav class="top-nav">
                <a href="index.php">Home</a>
                <a href="product.php">Products</a>
                <a href="cart.php">Cart</a>
                <a href="checkout.php">Checkout</a>
                <a href="inbox.php">Inbox</a>
                <a href="logout.php" class="logout-btn">Logout</a>
            </nav>

            <!-- Search Bar Section -->
            <div class="search-bar">
                <input type="text" placeholder="Search items..." id="search-input" />
                <button id="search-btn">Search</button>
            </div>
        </div>
    </header>

    <!-- Cart Section -->
    <main>
        <section class="cart">
            <h2>Shopping Cart (<span id="cart-item-count"><?php echo count($cartItems); ?></span>)</h2>

            <div id="cart-items-container">
                <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item" data-id="<?php echo $item['id']; ?>">
                        <div class="product-info">
                            <img src="<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-product-image">
                            <div class="product-details">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="description"><?php echo htmlspecialchars($item['description']); ?></p>
                                <p class="price">RM<?php echo number_format($item['price'], 2); ?></p>
                            </div>
                        </div>
                        <div class="cart-item-actions">
                            <label for="quantity-<?php echo $item['id']; ?>">Quantity:</label>
                            <input type="number"
                                id="quantity-<?php echo $item['id']; ?>"
                                name="quantity"
                                value="<?php echo $item['quantity']; ?>"
                                min="1"
                                max="100"
                                onchange="updateItemQuantity(<?php echo $item['id']; ?>, this.value)">

                            <label for="size-<?php echo $item['id']; ?>">Size:</label>
                            <select id="size-<?php echo $item['id']; ?>"
                                name="size"
                                onchange="updateItemSize(<?php echo $item['id']; ?>, this.value)">
                                <option value="S" <?php echo $item['size'] == 'S' ? 'selected' : ''; ?>>Small</option>
                                <option value="M" <?php echo $item['size'] == 'M' ? 'selected' : ''; ?>>Medium</option>
                                <option value="L" <?php echo $item['size'] == 'L' ? 'selected' : ''; ?>>Large</option>
                            </select>

                            <label for="color-<?php echo $item['id']; ?>">Color:</label>
                            <select id="color-<?php echo $item['id']; ?>"
                                name="color"
                                onchange="updateItemColor(<?php echo $item['id']; ?>, this.value)">
                                <option value="Blue" <?php echo $item['color'] == 'Blue' ? 'selected' : ''; ?>>Blue</option>
                                <option value="Black" <?php echo $item['color'] == 'Black' ? 'selected' : ''; ?>>Black</option>
                                <option value="Grey" <?php echo $item['color'] == 'Grey' ? 'selected' : ''; ?>>Grey</option>
                            </select>
                            <button class="remove-btn" onclick="removeItem(<?php echo $item['id']; ?>)">Remove</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="total-section">
                <p class="total-label">Total: <span class="total-price">RM<?php echo array_sum(array_map(function ($item) {
                                                                                return $item['price'] * $item['quantity'];
                                                                            }, $cartItems)); ?></span></p>
                <a href="javascript:void(0);" class="checkout-btn" id="checkout-btn" onclick="proceedToCheckout()">Checkout</a>
            </div>
        </section>
    </main>

    <script>
        // Function to remove item
        function removeItem(itemId) {
            // Implement AJAX call to server to remove item from cart
            fetch(`remove_item.php?item_id=${itemId}`, {
                    method: 'GET'
                })
                .then(response => response.text())
                .then(data => {
                    if (data === 'success') {
                        // Remove the item from the DOM
                        const itemElement = document.querySelector(`.cart-item[data-id='${itemId}']`);
                        if (itemElement) {
                            itemElement.remove();
                        }
                        // Update the total price
                        updateTotalPrice();
                        // Update the cart item count
                        document.getElementById('cart-item-count').textContent = document.querySelectorAll('.cart-item').length;
                    } else {
                        alert('Failed to remove item.');
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Add event listeners for search
        document
            .getElementById("search-input")
            .addEventListener("input", function(e) {
                searchItems(e.target.value);
            });

        document.getElementById("search-btn").addEventListener("click", function() {
            const searchTerm = document.getElementById("search-input").value;
            searchItems(searchTerm);
        });

        // Function to perform search
        function searchItems(searchTerm) {
            const items = document.querySelectorAll('.cart-item');
            searchTerm = searchTerm.toLowerCase();

            items.forEach(item => {
                const itemName = item.querySelector("h3").textContent.toLowerCase();
                const itemSize = item.querySelector(`select[name="size"]`).value.toLowerCase();
                const itemColor = item.querySelector(`select[name="color"]`).value.toLowerCase();

                if (itemName.includes(searchTerm) ||
                    itemSize.includes(searchTerm) ||
                    itemColor.includes(searchTerm)
                ) {
                    item.style.display = "flex"; // Show matching items
                } else {
                    item.style.display = "none"; // Hide non-matching items
                }
            });
        }

        // Update total price
        function updateTotalPrice() {
            const items = document.querySelectorAll('.cart-item');
            let totalPrice = 0;
            items.forEach(item => {
                const price = parseFloat(item.querySelector('.price').textContent.replace('RM', ''));
                const quantity = parseInt(item.querySelector('input[name="quantity"]').value);
                totalPrice += price * quantity;
            });
            document.querySelector(".total-price").textContent = `RM${totalPrice.toFixed(2)}`;
        }

        // Function to proceed to checkout
        function proceedToCheckout() {
            const items = document.querySelectorAll('.cart-item');
            if (items.length === 0) {
                alert('Your cart is empty. Please add items to proceed to checkout.');
                return;
            }

            // Validate all items have valid quantities
            let isValid = true;
            items.forEach(item => {
                const quantity = parseInt(item.querySelector('input[name="quantity"]').value);
                if (quantity < 1 || quantity > 100) {
                    isValid = false;
                    alert('Item ' + item.querySelector("h3").textContent + ' has an invalid quantity.');
                }
            });

            if (isValid) {
                window.location.href = 'checkout.php';
            }
        }

        // Add these new functions
        function updateItemQuantity(itemId, quantity) {
            updateCartItem(itemId, {
                quantity: quantity
            });
        }

        function updateItemSize(itemId, size) {
            updateCartItem(itemId, {
                size: size
            });
        }

        function updateItemColor(itemId, color) {
            updateCartItem(itemId, {
                color: color
            });
        }

        function updateCartItem(itemId, updates) {
            // Show loading state
            const item = document.querySelector(`.cart-item[data-id="${itemId}"]`);
            if (item) item.style.opacity = '0.5';

            fetch('update_cart_item.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        item_id: itemId,
                        ...updates
                    })
                })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        updateTotalPrice();
                    } else {
                        throw new Error(data.message || 'Update failed');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to update item: ' + error.message);
                })
                .finally(() => {
                    if (item) item.style.opacity = '1';
                });
        }
    </script>
</body>

</html>