<?php
require 'includes/db.php';
include 'includes/header.php';
// Ensure categories table exists
$conn->query("CREATE TABLE IF NOT EXISTS categories (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) UNIQUE, image VARCHAR(200) DEFAULT 'placeholder_dark.png')");
// Ensure image column exists for older deployments
$hasImageColRes = $conn->query("SHOW COLUMNS FROM categories LIKE 'image'");
if($hasImageColRes && $hasImageColRes->num_rows === 0){
	$conn->query("ALTER TABLE categories ADD COLUMN image VARCHAR(200) DEFAULT 'placeholder_dark.png'");
}
$catsRes = $conn->query("SELECT id, name, image FROM categories ORDER BY name ASC");
$categories = $catsRes ? $catsRes->fetch_all(MYSQLI_ASSOC) : [];
?>
<style>
    /* Categories Grid */
    .categories {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }
    .category-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        cursor: pointer;
        transition: transform 0.3s, box-shadow 0.3s;
        display: flex;
        flex-direction: column;
        text-decoration: none;
        color: inherit;
    }
    .category-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    .category-image {
        width: 100%;
        height: 130px;
        object-fit: cover;
        background: #fafafa;
    }
    .category-name {
        text-align: center;
        padding: 12px 10px;
        font-weight: 600;
        color: #333;
    }

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
        color: #bd2424ff;
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

    <section class="content-card" style="margin-bottom: 30px;">
        <h2 style="margin-bottom:16px; color:#333;">Shop by Category</h2>
        <div class="categories">
            <?php
            if(count($categories)===0){
                echo '<p>No categories yet.</p>';
            } else {
                foreach($categories as $c){
					$img = $c['image'] ?: 'placeholder_dark.png';
					echo '<a class="category-card" href="category.php?cat='.urlencode($c['name']).'" aria-label="'.htmlspecialchars($c['name']).'">';
					echo '<img class="category-image" src="assets/images/'.htmlspecialchars($img).'" alt="'.htmlspecialchars($c['name']).'">';
                    echo '<div class="category-name">'.htmlspecialchars($c['name']).'</div>';
                    echo '</a>';
                }
            }
            ?>
        </div>
        
    </section>

    <!-- Products grid removed to keep dashboard category-focused -->
</main>

<!-- no product list JS needed on index; cart count handled globally -->
<script src="assets/script.js"></script>

<?php include 'includes/footer.php'; ?>
