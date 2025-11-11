<?php
require 'includes/db.php';
include 'includes/header.php';

$cat = trim($_GET['cat'] ?? '');
$catId = intval($_GET['cat_id'] ?? 0);
// If cat_id provided, resolve to category name
if ($cat === '' && $catId > 0) {
	$stmtc = $conn->prepare('SELECT name FROM categories WHERE id = ?');
	$stmtc->bind_param('i', $catId);
	$stmtc->execute();
	$r = $stmtc->get_result()->fetch_assoc();
	$cat = $r['name'] ?? '';
}
// Fallback if no category
if ($cat === '') {
    echo '<main><div class="content-card"><p>No category selected.</p><a href="index.php" class="btn">Back to Dashboard</a></div></main>';
    include 'includes/footer.php';
    exit;
}

// Load products by category (case-insensitive match)
$stmt = $conn->prepare('SELECT * FROM products WHERE LOWER(category) = LOWER(?)');
$stmt->bind_param('s', $cat);
$stmt->execute();
$res = $stmt->get_result();
$products = $res->fetch_all(MYSQLI_ASSOC);
?>
<style>
    .category-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }
    .category-title {
        color: #333;
        font-size: 28px;
        font-weight: 700;
    }
    .controls {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    .controls select {
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-family: inherit;
        font-size: 14px;
        min-width: 180px;
        background: white;
    }
    .products {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 30px;
        margin-top: 20px;
    }
    .product-card {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: transform 0.3s, box-shadow 0.3s;
        display: flex;
        flex-direction: column;
    }
    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    .product-card img { width: 100%; height: 220px; object-fit: cover; }
    .product-card-body { padding: 16px; display: flex; flex-direction: column; gap: 10px; }
    .product-card-body h3 { color: #333; font-size: 18px; }
    .product-card-body .price { color: #88A71C; font-size: 20px; font-weight: 700; }
    .qty-row { display: flex; align-items: center; gap: 8px; }
    .qty-row input[type="number"] {
        width: 80px; padding: 8px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;
    }
    @media (max-width: 768px) {
        .products { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        .category-title { font-size: 24px; }
    }
</style>

<main>
    <div class="content-card">
        <div class="category-header">
            <div class="category-title"><?=htmlspecialchars($cat)?></div>
            <div class="controls">
                <a href="index.php" class="btn">Back to Dashboard</a>
                <select id="sortSelect" aria-label="Sort products">
                    <option value="default">Sort: Default</option>
                    <option value="price-asc">Price: Low to High</option>
                    <option value="price-desc">Price: High to Low</option>
                    <option value="name-asc">Name: A to Z</option>
                    <option value="name-desc">Name: Z to A</option>
                </select>
            </div>
        </div>

        <?php if (!$products): ?>
            <p>No products found in this category.</p>
        <?php else: ?>
            <section id="categoryProducts" class="products">
                <?php foreach ($products as $p): ?>
                    <article class="product-card"
                        data-id="<?=$p['id']?>"
                        data-name="<?=htmlspecialchars($p['name'])?>"
                        data-price="<?=$p['price']?>">
                        <a href="product.php?id=<?=$p['id']?>">
                            <img src="assets/images/<?=htmlspecialchars($p['image'])?>" alt="<?=htmlspecialchars($p['name'])?>">
                        </a>
                        <div class="product-card-body">
                            <h3><?=htmlspecialchars($p['name'])?></h3>
                            <div class="price">Rs. <?=htmlspecialchars($p['price'])?></div>
                            <div class="qty-row">
                                <label for="qty-<?=$p['id']?>">Qty:</label>
                                <input id="qty-<?=$p['id']?>" type="number" min="1" value="1">
                            </div>
                            <div style="display:flex; gap:10px;">
                                <a href="product.php?id=<?=$p['id']?>" class="btn" style="flex:1; text-align:center;">View</a>
                                <button class="btn add-to-cart-btn" data-id="<?=$p['id']?>" data-image="assets/images/<?=htmlspecialchars($p['image'])?>" style="flex:1;">Add to Cart</button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </div>
</main>

<script>
// Sorting
const sortSelect = document.getElementById('sortSelect');
const container = document.getElementById('categoryProducts');
if (sortSelect && container) {
    sortSelect.addEventListener('change', () => {
        const cards = Array.from(container.querySelectorAll('.product-card'));
        const val = sortSelect.value;
        const byName = (a, b) => a.dataset.name.localeCompare(b.dataset.name);
        const byPrice = (a, b) => parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
        if (val === 'name-asc') cards.sort(byName);
        else if (val === 'name-desc') cards.sort((a,b) => byName(b,a));
        else if (val === 'price-asc') cards.sort(byPrice);
        else if (val === 'price-desc') cards.sort((a,b) => byPrice(b,a));
        else return;
        cards.forEach(c => container.appendChild(c));
    });
}

// Add to Cart without leaving page
function addToCart(productId, productName, productPrice, productImage, quantity) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    const existing = cart.find(item => item.id == productId);
    const qty = Math.max(1, parseInt(quantity || 1));
    if (existing) {
        existing.quantity = (existing.quantity || 1) + qty;
    } else {
        cart.push({
            id: productId,
            name: productName,
            price: 'Rs. ' + productPrice,
            image: productImage,
            quantity: qty
        });
    }
    localStorage.setItem('cart', JSON.stringify(cart));
    if (typeof updateCartCount === 'function') updateCartCount();
}

// Wire up buttons
document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        const card = btn.closest('.product-card');
        const id = card.dataset.id;
        const name = card.dataset.name;
        const price = card.dataset.price;
        const image = btn.dataset.image;
        const qtyInput = document.getElementById('qty-' + id);
        const qty = qtyInput ? qtyInput.value : 1;
        addToCart(id, name, price, image, qty);
        // Feedback
        btn.textContent = 'Added!';
        setTimeout(() => btn.textContent = 'Add to Cart', 900);
    });
});
</script>

<?php include 'includes/footer.php'; ?>

