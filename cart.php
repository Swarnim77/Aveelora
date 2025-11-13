<?php
require 'includes/db.php';
include 'includes/header.php';
?>
<style>
    .cart-header {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .cart-header h1 {
        color: #1c2ca7ff;
        margin-bottom: 10px;
        font-size: 28px;
    }
    
    #empty-cart {
        background: white;
        padding: 60px 40px;
        text-align: center;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: none;
    }
    
    #empty-cart h2 {
        color: #666;
        margin-bottom: 15px;
        font-size: 24px;
    }
    
    #empty-cart p {
        color: #999;
        margin-bottom: 20px;
        font-size: 16px;
    }
    
    #cart-items {
        display: flex;
        flex-direction: column;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .cart-item {
        background: white;
        padding: 20px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .cart-item-image {
        flex-shrink: 0;
    }
    
    .cart-item-image img {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 8px;
    }
    
    .cart-item-details {
        flex-grow: 1;
    }
    
    .cart-item-details h3 {
        color: #333;
        margin-bottom: 10px;
        font-size: 20px;
    }
    
    .cart-item-price {
        color: #666;
        margin-bottom: 10px;
        font-size: 16px;
    }
    
    .quantity-controls {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 15px 0;
    }
    
    .qty-btn {
        background-color: #1c2ca7ff;
        color: white;
        border: none;
        width: 35px;
        height: 35px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 18px;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.3s;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .qty-btn:hover {
        background-color: #7A951A;
    }
    
    .quantity {
        font-size: 18px;
        font-weight: bold;
        min-width: 40px;
        text-align: center;
        color: #333;
    }
    
    .cart-item-total {
        font-weight: bold;
        color: #1c2ca7ff;
        font-size: 18px;
        margin-top: 10px;
    }
    
    .remove-btn {
        background-color: #dc3545;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 500;
        transition: background-color 0.3s;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .remove-btn:hover {
        background-color: #c82333;
    }
    
    .cart-summary {
        background: white;
        padding: 25px;
        border-radius: 8px;
        margin-top: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .cart-summary h2 {
        color: #271ba6ff;
        font-size: 24px;
        margin-bottom: 10px;
    }
    
    .cart-total {
        font-size: 28px;
        font-weight: bold;
        color: #1c2ca7ff;
    }
    
    .clear-cart-btn {
        background-color: #dc3545;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 500;
        transition: background-color 0.3s;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .clear-cart-btn:hover {
        background-color: #c82333;
    }
    
    .checkout-btn {
        background-color: #1c2ca7ff;
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 500;
        text-decoration: none;
        display: inline-block;
        transition: background-color 0.3s, transform 0.2s;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .checkout-btn:hover {
        background-color: #7A951A;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    .cart-actions {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    @media (max-width: 768px) {
        .cart-item {
            flex-direction: column;
            text-align: center;
        }
        
        .cart-item-image img {
            width: 200px;
            height: 200px;
        }
        
        .cart-summary {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .cart-actions {
            width: 100%;
        }
        
        .checkout-btn,
        .clear-cart-btn {
            width: 100%;
        }
    }
</style>

<main>
    <div class="content-card" style="padding: 0;">
        <div class="cart-header">
            <h1>Your Cart</h1>
        </div>
        
        <div id="empty-cart">
            <h2>Paisa Satihalnus pachi bhau badhna sakcha</h2>
            <p></p>
            <a href="index.php" class="btn" style="margin-top: 20px;">Paisa Satda yaad garnuhola dhanyebad</a>
        </div>
        
        <div id="cart-items"></div>
        
        <div class="cart-summary" id="cart-summary" style="display: none;">
            <div>
                <h2>Total:</h2>
                <p class="cart-total" id="cart-total">Rs. 0</p>
            </div>
            <div class="cart-actions">
                <a href="payment.php" class="checkout-btn" id="checkout-btn">Proceed to Checkout</a>
                <button onclick="clearCart()" class="clear-cart-btn">Clear Cart</button>
            </div>
        </div>
    </div>
</main>

<script>
// Cart functionality using localStorage

// Add product to cart
function addToCart(productName, productPrice, productImage, productId) {
    // Get existing cart from localStorage
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    // Check if product already exists in cart
    const existingProduct = cart.find(item => item.id == productId);
    
    if (existingProduct) {
        // If product exists, increase quantity
        existingProduct.quantity += 1;
    } else {
        // If product doesn't exist, add it to cart
        const product = {
            id: productId,
            name: productName,
            price: productPrice,
            image: productImage,
            quantity: 1
        };
        cart.push(product);
    }
    
    // Save cart to localStorage
    localStorage.setItem('cart', JSON.stringify(cart));
    
    // Show success message
    alert('Product added to cart!');
    
    // Update cart count if function exists
    if (typeof updateCartCount === 'function') {
        updateCartCount();
    }
}

// Remove product from cart
function removeFromCart(productId) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    cart = cart.filter(item => item.id != productId);
    localStorage.setItem('cart', JSON.stringify(cart));
    displayCart(); // Refresh cart display
    if (typeof updateCartCount === 'function') {
        updateCartCount();
    }
}

// Update quantity in cart
function updateQuantity(productId, change) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    const product = cart.find(item => item.id == productId);
    
    if (product) {
        product.quantity += change;
        if (product.quantity <= 0) {
            removeFromCart(productId);
        } else {
            localStorage.setItem('cart', JSON.stringify(cart));
            displayCart(); // Refresh cart display
            if (typeof updateCartCount === 'function') {
                updateCartCount();
            }
        }
    }
}

// Get cart items
function getCartItems() {
    return JSON.parse(localStorage.getItem('cart')) || [];
}

// Clear cart
function clearCart() {
    if (confirm('Are you sure you want to clear your cart?')) {
        localStorage.removeItem('cart');
        displayCart(); // Refresh cart display
        if (typeof updateCartCount === 'function') {
            updateCartCount();
        }
    }
}

// Calculate total price
function calculateTotal() {
    const cart = getCartItems();
    return cart.reduce((total, item) => {
        // Handle both "Rs. 100" and "100" formats
        let price = item.price;
        if (typeof price === 'string') {
            price = price.replace(/Rs\.?\s*/i, '').replace(/Nrs\.?\s*/i, '').trim();
        }
        const numPrice = parseFloat(price) || 0;
        return total + (numPrice * item.quantity);
    }, 0);
}

// Update cart count in navigation (if needed)
function updateCartCount() {
    const cart = getCartItems();
    const count = cart.reduce((sum, item) => sum + item.quantity, 0);
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(el => {
        el.textContent = count;
    });
}

// Display cart on cart page
function displayCart() {
    const cartContainer = document.getElementById('cart-items');
    const totalContainer = document.getElementById('cart-total');
    const emptyCartMessage = document.getElementById('empty-cart');
    const cartSummary = document.getElementById('cart-summary');
    const checkoutBtn = document.getElementById('checkout-btn');
    
    if (!cartContainer) return;
    
    const cart = getCartItems();
    
    if (cart.length === 0) {
        cartContainer.innerHTML = '';
        if (emptyCartMessage) {
            emptyCartMessage.style.display = 'block';
        }
        if (totalContainer) {
            totalContainer.textContent = 'Rs. 0';
        }
        if (cartSummary) {
            cartSummary.style.display = 'none';
        }
        return;
    }
    
    if (emptyCartMessage) {
        emptyCartMessage.style.display = 'none';
    }
    
    if (cartSummary) {
        cartSummary.style.display = 'flex';
    }
    
    cartContainer.innerHTML = cart.map(item => {
        // Handle price format
        let price = item.price;
        if (typeof price === 'string') {
            price = price.replace(/Rs\.?\s*/i, '').replace(/Nrs\.?\s*/i, '').trim();
        }
        const numPrice = parseFloat(price) || 0;
        const itemTotal = numPrice * item.quantity;
        
        // Handle image path
        let imageSrc = item.image;
	if (imageSrc && 
		!imageSrc.startsWith('http') && 
		!imageSrc.startsWith('/') && 
		!imageSrc.startsWith('data:') &&
		!imageSrc.startsWith('assets/images/')) {
            imageSrc = 'assets/images/' + imageSrc;
        }
        
        return `
            <div class="cart-item">
                <div class="cart-item-image">
                    <img src="${imageSrc}" alt="${item.name}" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'150\' height=\'150\'%3E%3Crect fill=\'%23ddd\' width=\'150\' height=\'150\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'14\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3ENo Image%3C/text%3E%3C/svg%3E'">
                </div>
                <div class="cart-item-details">
                    <h3>${item.name}</h3>
                    <p class="cart-item-price">Rs. ${numPrice.toFixed(2)}</p>
                    <div class="quantity-controls">
                        <button onclick="updateQuantity('${item.id}', -1)" class="qty-btn">-</button>
                        <span class="quantity">${item.quantity}</span>
                        <button onclick="updateQuantity('${item.id}', 1)" class="qty-btn">+</button>
                    </div>
                    <p class="cart-item-total">Total: Rs. ${itemTotal.toFixed(2)}</p>
                </div>
                <button onclick="removeFromCart('${item.id}')" class="remove-btn">Remove</button>
            </div>
        `;
    }).join('');
    
    if (totalContainer) {
        const total = calculateTotal();
        totalContainer.textContent = `Rs. ${total.toFixed(2)}`;
    }
}

// Sync cart to PHP session before checkout
function syncCartToSession() {
    const cart = getCartItems();
    if (cart.length > 0) {
        // Send cart to PHP session via AJAX
        fetch('sync_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ cart: cart })
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  window.location.href = 'payment.php';
              } else {
                  alert('Error syncing cart. Please try again.');
              }
          })
          .catch(error => {
              console.error('Error:', error);
              // Fallback: try to proceed anyway
              window.location.href = 'payment.php';
          });
    } else {
        alert('AJa Peune plan Chaina?!');
    }
}

// Update checkout button to sync cart
document.addEventListener('DOMContentLoaded', function() {
    const checkoutBtn = document.getElementById('checkout-btn');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            syncCartToSession();
        });
    }
    
    displayCart();
    updateCartCount();
});

// Initialize cart display when page loads
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        displayCart();
        updateCartCount();
    });
} else {
    displayCart();
    updateCartCount();
}
</script>

<?php include 'includes/footer.php'; ?>
