document.addEventListener("DOMContentLoaded", function () {
 // Get the last order ID stored during checkout
 const orderId = localStorage.getItem("lastOrderId");

 // Get all checkout messages
 const checkoutMessages = JSON.parse(
  localStorage.getItem("checkoutMessages") || "[]"
 );

 // Find the specific order details
 const orderDetails = checkoutMessages.find(
  (message) => message.orderId === orderId
 );

 if (orderDetails) {
  // Update order details in the confirmation page
  document.getElementById("order-id").textContent = orderDetails.orderId;
  document.getElementById("customer-name").textContent = orderDetails.name;

  // Display order items
  const orderItemsList = document.getElementById("order-items");
  orderItemsList.innerHTML = orderDetails.items
   .map(
    (item) => `
            <li>
                <img src="${item.img}" alt="${
     item.name
    }" style="width: 50px; height: 50px;" 
                     onerror="this.src='img/placeholder.png'; this.onerror=null;">
                ${item.name} - ${item.quantity}x RM${item.price.toFixed(2)}
            </li>
        `
   )
   .join("");

  // Calculate and display totals
  const subtotal = orderDetails.items.reduce(
   (sum, item) => sum + item.price * item.quantity,
   0
  );
  const shipping = 0; // Add shipping calculation if needed
  const total = subtotal + shipping;

  document.getElementById("subtotal").textContent = `RM ${subtotal.toFixed(2)}`;
  document.getElementById("shipping").textContent = `RM ${shipping.toFixed(2)}`;
  document.getElementById("order-total").textContent = `RM ${total.toFixed(2)}`;
 } else {
  // Handle case when order details are not found
  document.getElementById("order-confirmation").innerHTML = `
            <h2>Order Not Found</h2>
            <p>We couldn't find your order details. Please contact customer support.</p>
        `;
 }
});
