<?php
require 'includes/db.php';
include 'includes/header.php';
$id = intval($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT * FROM products WHERE id=?');
$stmt->bind_param('i',$id);
$stmt->execute();
$res = $stmt->get_result();
$p = $res->fetch_assoc();
if(!$p){ 
    echo '<main><div class="content-card"><p>Product not found.</p><a href="index.php" class="btn">Back to Home</a></div></main>'; 
    include 'includes/footer.php'; 
    exit; 
}
?>
<style>
    .product-detail {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        margin-top: 30px;
    }
    
    .product-detail img {
        width: 100%;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .product-info h2 {
        color: #333;
        font-size: 32px;
        margin-bottom: 15px;
    }
    
    .product-info .price {
        color: #88A71C;
        font-size: 28px;
        font-weight: bold;
        margin-bottom: 20px;
    }
    
    .product-info p {
        color: #666;
        line-height: 1.6;
        margin-bottom: 30px;
        font-size: 16px;
    }
    
    .product-form {
        margin-top: 20px;
    }
    
    .product-form label {
        display: block;
        margin-bottom: 10px;
        color: #333;
        font-weight: 500;
    }
    
    .product-form input[type="number"] {
        width: 100px;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
        margin-bottom: 20px;
    }
    
    .product-form input[type="number"]:focus {
        outline: none;
        border-color: #88A71C;
        box-shadow: 0 0 0 2px rgba(136, 167, 28, 0.2);
    }
    
    @media (max-width: 768px) {
        .product-detail {
            grid-template-columns: 1fr;
            gap: 20px;
        }
    }
</style>

<main>
    <div class="content-card">
        <div class="product-detail">
            <img src="assets/images/<?=htmlspecialchars($p['image'])?>" alt="<?=htmlspecialchars($p['name'])?>" id="product-image">
            <div class="product-info">
                <h2 id="product-name"><?=htmlspecialchars($p['name'])?></h2>
                <p class="price" id="product-price">Rs. <?=htmlspecialchars($p['price'])?></p>
                <p><?=htmlspecialchars($p['description'])?></p>
                <div class="product-form">
                    <label>Quantity: 
                        <input type="number" id="product-quantity" value="1" min="1">
                    </label>
                    <button type="button" class="btn" onclick="addProductToCart()">Add to Cart</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function addProductToCart() {
    const productId = <?=$p['id']?>;
    const productName = document.getElementById('product-name').textContent;
    const productPrice = document.getElementById('product-price').textContent.trim();
    const productImage = document.getElementById('product-image').src;
    const quantity = parseInt(document.getElementById('product-quantity').value) || 1;
    
    // Get existing cart from localStorage
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    // Check if product already exists in cart
    const existingProduct = cart.find(item => item.id == productId);
    
    if (existingProduct) {
        // If product exists, increase quantity
        existingProduct.quantity += quantity;
    } else {
        // If product doesn't exist, add it to cart
        const product = {
            id: productId,
            name: productName,
            price: productPrice,
            image: 'assets/images/<?=htmlspecialchars($p['image'])?>',
            quantity: quantity
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

// Update cart count in navigation
function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const count = cart.reduce((sum, item) => sum + item.quantity, 0);
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(el => {
        el.textContent = count;
    });
    
    // Update cart link in header if it exists
    const cartLink = document.querySelector('a[href="cart.php"]');
    if (cartLink && count > 0) {
        if (!cartLink.querySelector('.cart-count')) {
            const countSpan = document.createElement('span');
            countSpan.className = 'cart-count';
            countSpan.style.marginLeft = '5px';
            countSpan.style.backgroundColor = '#88A71C';
            countSpan.style.color = 'white';
            countSpan.style.borderRadius = '50%';
            countSpan.style.padding = '2px 8px';
            countSpan.style.fontSize = '12px';
            cartLink.appendChild(countSpan);
        }
        cartLink.querySelector('.cart-count').textContent = count;
    }
}

// Initialize cart count on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', updateCartCount);
} else {
    updateCartCount();
}
</script>

<?php include 'includes/footer.php'; ?>
