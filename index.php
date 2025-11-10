<?php
require 'includes/db.php';
include 'includes/header.php';
$cats = $conn->query("SELECT DISTINCT category FROM products")->fetch_all(MYSQLI_ASSOC);
$res = $conn->query("SELECT * FROM products");
$products = $res->fetch_all(MYSQLI_ASSOC);
?>
<style>
    /* Hero Section */
    .hero {
        text-align: center;
        padding: 40px 20px;
        margin-bottom: 40px;
    }
    
    .hero h1 {
        font-size: 48px;
        color: #333;
        margin-bottom: 10px;
        font-weight: bold;
    }
    
    .hero .tag {
        font-size: 20px;
        color: #666;
        margin-bottom: 30px;
    }
    
    .search-row {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
        max-width: 800px;
        margin: 0 auto;
    }
    
    .search-row input,
    .search-row select {
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
        font-family: inherit;
        flex: 1;
        min-width: 200px;
    }
    
    .search-row input:focus,
    .search-row select:focus {
        outline: none;
        border-color: #88A71C;
        box-shadow: 0 0 0 2px rgba(136, 167, 28, 0.2);
    }
    
    /* Products Grid */
    .products {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 30px;
        margin-top: 30px;
    }
    
    .product-card {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    
    .product-card img {
        width: 100%;
        height: 250px;
        object-fit: cover;
    }
    
    .product-card-body {
        padding: 20px;
    }
    
    .product-card-body h3 {
        color: #333;
        margin-bottom: 10px;
        font-size: 20px;
    }
    
    .product-card-body .price {
        color: #88A71C;
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 15px;
    }
    
    @media (max-width: 768px) {
        .hero h1 {
            font-size: 36px;
        }
        
        .products {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
    }
</style>

<main>
    <div class="hero">
        <h1>Aveelora</h1>
        <p class="tag">Welcome Beautiful Souls</p>
    </div>

    <section id="productGrid" class="products">
        <?php foreach($products as $p): ?>
            <article class="product-card" data-name="<?=htmlspecialchars(strtolower($p['name']))?>" data-category="<?=htmlspecialchars($p['category'])?>" data-price="<?=$p['price']?>">
                <a href="product.php?id=<?=$p['id']?>">
                    <img src="assets/images/<?=htmlspecialchars($p['image'])?>" alt="<?=htmlspecialchars($p['name'])?>">
                </a>
                <div class="product-card-body">
                    <h3><?=htmlspecialchars($p['name'])?></h3>
                    <p class="price">Rs. <?=htmlspecialchars($p['price'])?></p>
                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                        <a href="product.php?id=<?=$p['id']?>" class="btn" style="flex: 1; text-align: center;">View</a>
                        <button onclick="addToCartFromIndex('<?=htmlspecialchars($p['name'])?>', 'Rs. <?=$p['price']?>', 'assets/images/<?=htmlspecialchars($p['image'])?>', <?=$p['id']?>)" class="btn" style="flex: 1;">Add to Cart</button>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
</main>

<script>
window.Aveelora = { initFilters: true };

// Add to cart function for index page
function addToCartFromIndex(productName, productPrice, productImage, productId) {
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
    
    // Update cart count
    updateCartCount();
}

// Update cart count in navigation
function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const count = cart.reduce((sum, item) => sum + item.quantity, 0);
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(el => {
        el.textContent = count;
        if (count > 0) {
            el.style.display = 'inline-block';
        } else {
            el.style.display = 'none';
        }
    });
}

// Initialize cart count on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', updateCartCount);
} else {
    updateCartCount();
}
</script>
<script src="assets/script.js"></script>

<?php include 'includes/footer.php'; ?>
