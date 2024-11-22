// Function to fetch cart data from the server
function fetchCart() {
  fetch("cart_backend.php", {
    method: "GET",
  })
    .then((response) => response.json())
    .then((cart) => {
      updateCartUI(cart);
    })
    .catch((error) => console.error("Error fetching cart:", error));
}

// Function to update the cart UI
function updateCartUI(cart) {
  const cartItemsContainer = document.getElementById("cart-items-container");
  const totalPriceElement = document.querySelector(".total-price");
  const cartItemCount = document.getElementById("cart-item-count");

  cartItemsContainer.innerHTML = ""; // Clear previous items

  let total = 0;
  cart.forEach((item) => {
    const itemElement = document.createElement("div");
    itemElement.classList.add("cart-item");
    itemElement.innerHTML = `
      <div class="product-info">
        <img src="${item.img_url}" alt="${item.product_name}" class="cart-product-image">
        <div class="product-details">
          <h3>${item.product_name}</h3>
          <p>Size: ${item.size}</p>
          <p>Color: ${item.color}</p>
          <p>Price: RM${item.price}</p>
          <p>Quantity: ${item.quantity}</p>
        </div>
      </div>
      <button class="remove" data-cart-id="${item.cart_id}">Remove</button>
    `;
    cartItemsContainer.appendChild(itemElement);

    // Event listener for remove button
    itemElement.querySelector(".remove").addEventListener("click", () => {
      removeFromCart(item.cart_id);
    });

    total += item.price * item.quantity;
  });

  totalPriceElement.textContent = `RM${total.toFixed(2)}`;
  cartItemCount.textContent = cart.length;
}

// Function to remove an item from the cart
function removeFromCart(cart_id) {
  fetch("cart_backend.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ action: "remove", cart_id }),
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.success) {
        fetchCart(); // Refresh the cart
      } else {
        console.error("Error removing item:", result.error);
      }
    });
}

// Initialize the cart on page load
window.onload = fetchCart;
