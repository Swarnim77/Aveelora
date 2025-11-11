<?php
require '../includes/db.php';
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin'){ 
    header('Location: ../login.php'); 
    exit; 
}

// Ensure categories table exists (with image column)
$conn->query("CREATE TABLE IF NOT EXISTS categories (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) UNIQUE, image VARCHAR(200) DEFAULT 'placeholder_dark.png')");
// Ensure image column exists for older deployments
$hasImageColRes = $conn->query("SHOW COLUMNS FROM categories LIKE 'image'");
if($hasImageColRes && $hasImageColRes->num_rows === 0){
	$conn->query("ALTER TABLE categories ADD COLUMN image VARCHAR(200) DEFAULT 'placeholder_dark.png'");
}

$msg='';
// Add category
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='add_category'){
	$name = trim($_POST['name'] ?? '');
	$imageName = 'placeholder_dark.png';
	// Handle image upload
	if(isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])){
		$allowed = ['image/jpeg','image/png','image/gif','image/webp'];
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime = finfo_file($finfo, $_FILES['image']['tmp_name']);
		finfo_close($finfo);
		if(in_array($mime, $allowed, true)){
			$ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
			$base = preg_replace('/[^a-zA-Z0-9_\-]/','_', pathinfo($_FILES['image']['name'], PATHINFO_FILENAME));
			$imageName = time().'_'.$base.'.'.$ext;
			@move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../assets/images/' . $imageName);
		}
	}
	if($name!==''){
		$stmt = $conn->prepare('INSERT IGNORE INTO categories(name, image) VALUES (?, ?)');
		$stmt->bind_param('ss',$name,$imageName);
        if($stmt->execute()){
            $msg = '<div class="message" style="background:#e8f5e9;color:#256029;border:1px solid #c8e6c9;">Category added.</div>';
        } else {
            $msg = '<div class="message" style="background:#ffebee;color:#b71c1c;border:1px solid #ffcdd2;">Failed to add category.</div>';
        }
    }
}
// Delete category
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='delete_category'){
    $cid = intval($_POST['category_id'] ?? 0);
    if($cid>0){
        // Get category name
		$catRes = $conn->prepare("SELECT name, image FROM categories WHERE id=?");
		$catRes->bind_param('i',$cid);
		$catRes->execute();
		$catRow = $catRes->get_result()->fetch_assoc();
        $catRow = $catRes ? $catRes->fetch_assoc() : null;
        $catName = $catRow['name'] ?? null;
		$catImage = $catRow['image'] ?? null;
        if($catName){
            // Optionally delete products under this category if requested
            if(isset($_POST['delete_products']) && $_POST['delete_products']=='1'){
                $stmtDelP = $conn->prepare('DELETE FROM products WHERE category = ?');
                $stmtDelP->bind_param('s',$catName);
                $stmtDelP->execute();
            } else {
                // Otherwise, keep products but clear their category
                $stmtClr = $conn->prepare('UPDATE products SET category=NULL WHERE category = ?');
                $stmtClr->bind_param('s',$catName);
                $stmtClr->execute();
            }
        }
        $stmt = $conn->prepare('DELETE FROM categories WHERE id=?');
        $stmt->bind_param('i',$cid);
        if($stmt->execute()){
			// Attempt to delete image file if custom
			if($catImage && $catImage !== 'placeholder_dark.png'){
				$imgPath = __DIR__ . '/../assets/images/' . $catImage;
				if(is_file($imgPath)){
					@unlink($imgPath);
				}
			}
            $msg = '<div class="message" style="background:#e8f5e9;color:#256029;border:1px solid #c8e6c9;">Category deleted.</div>';
        } else {
            $msg = '<div class="message" style="background:#ffebee;color:#b71c1c;border:1px solid #ffcdd2;">Failed to delete category.</div>';
        }
    }
}
// Delete product inside category page
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='delete_product'){
    $pid = intval($_POST['product_id'] ?? 0);
    if($pid>0){
        $stmt = $conn->prepare('DELETE FROM products WHERE id=?');
        $stmt->bind_param('i',$pid);
        if($stmt->execute()){
            $msg = '<div class="message" style="background:#e8f5e9;color:#256029;border:1px solid #c8e6c9;">Product deleted.</div>';
        } else {
            $msg = '<div class="message" style="background:#ffebee;color:#b71c1c;border:1px solid #ffcdd2;">Failed to delete product.</div>';
        }
    }
}

// Fetch data
$cats = $conn->query('SELECT c.id, c.name, c.image, COUNT(p.id) as product_count 
	FROM categories c 
	LEFT JOIN products p ON p.category = c.name
	GROUP BY c.id, c.name, c.image
	ORDER BY c.name ASC')->fetch_all(MYSQLI_ASSOC);

$selectedCat = trim($_GET['cat'] ?? '');
$products = [];
if($selectedCat !== ''){
    $stmt = $conn->prepare('SELECT * FROM products WHERE category = ? ORDER BY name ASC');
    $stmt->bind_param('s',$selectedCat);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Calibri','Roboto','Poppins',sans-serif; background:#f5f5dc; min-height:100vh; display:flex; flex-direction:column; }
        header { background:#d3d3d3; padding:20px 0; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
        .header-container { max-width:1200px; margin:0 auto; padding:0 20px; }
        .header-content { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; }
        .header-title { font-size:28px; font-weight:bold; color:#333; margin-bottom:10px; }
        nav { display:flex; gap:15px; flex-wrap:wrap; }
        nav a { color:#333; text-decoration:none; padding:8px 16px; border-radius:5px; transition:background-color .3s; font-weight:500; }
        nav a:hover { background:rgba(0,0,0,0.1); }
        main { flex:1; max-width:1200px; width:100%; margin:30px auto; padding:0 20px; }
        .content-card { background:white; padding:30px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1); margin-bottom:30px; }
        .content-card h2 { color:#333; margin-bottom:20px; font-size:24px; border-bottom:2px solid #88A71C; padding-bottom:10px; }
        .form-row { display:flex; gap:10px; flex-wrap:wrap; }
        .form-row input { padding:12px; border:1px solid #ddd; border-radius:6px; font-size:16px; }
        .btn { background:#88A71C; color:#fff; padding:10px 16px; border:none; border-radius:6px; cursor:pointer; font-size:14px; font-weight:600; text-decoration:none; display:inline-block; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
        .btn:hover { background:#7A951A; }
        .btn-danger { background:#d32f2f; }
        .btn-danger:hover { background:#b71c1c; }
        table { width:100%; border-collapse:collapse; margin-top:16px; }
        th, td { text-align:left; padding:12px; border-bottom:1px solid #ddd; }
        th { background:#f8f8f8; }
        .message { padding:12px; border-radius:6px; margin-bottom:12px; }
        .grid { display:grid; grid-template-columns: 2fr 3fr; gap:20px; }
        @media (max-width: 900px){ .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="header-content">
                <div>
                    <h1 class="header-title">Admin Dashboard</h1>
                    <p style="color:#666;font-size:14px;">Manage Categories & Products</p>
                </div>
                <nav>
                    <a href="dashboard.php">Home</a>
                    <a href="categories.php">Categories</a>
                    <a href="viewproduct.php">Products</a>
                    <a href="users.php">Users</a>
                    <a href="reports.php">Reports</a>
                    <a href="settings.php">Settings</a>
                    <a href="../logout.php">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <div class="grid">
            <div class="content-card">
                <h2>Categories</h2>
				<?= $msg ?>
				<form method="post" enctype="multipart/form-data" class="form-row" style="margin-bottom:12px; gap:12px; align-items:center;">
                    <input type="hidden" name="action" value="add_category">
                    <input type="text" name="name" placeholder="New category name" required>
					<input type="file" name="image" accept="image/*" style="padding:8px;">
                    <button type="submit" class="btn">Add Category</button>
                </form>
                <table>
                    <thead>
                        <tr>
							<th>Image</th>
                            <th>Name</th>
                            <th>Products</th>
                            <th style="width:220px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($cats as $c): ?>
                        <tr>
							<td>
								<img src="../assets/images/<?= htmlspecialchars($c['image'] ?: 'placeholder_dark.png') ?>" alt="<?= htmlspecialchars($c['name']) ?>" style="width:60px;height:40px;object-fit:cover;border-radius:4px;border:1px solid #eee;">
							</td>
                            <td><?= htmlspecialchars($c['name']) ?></td>
                            <td><?= intval($c['product_count']) ?></td>
                            <td>
                                <a class="btn" href="categories.php?cat=<?= urlencode($c['name']) ?>">View Products</a>
                                <form method="post" onsubmit="return confirm('Delete this category?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_category">
                                    <input type="hidden" name="category_id" value="<?= $c['id'] ?>">
                                    <label style="font-size:12px;color:#555;margin:0 6px;">
                                        <input type="checkbox" name="delete_products" value="1"> Delete products too
                                    </label>
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="content-card">
                <h2>Products in <?= $selectedCat ? htmlspecialchars($selectedCat) : '...' ?></h2>
                <?php if($selectedCat===''): ?>
                    <p>Select a category to view its products.</p>
                <?php else: ?>
                    <?php if(count($products)===0): ?>
                        <p>No products in this category.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th style="width:140px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($products as $p): ?>
                                <tr>
                                    <td><?= $p['id'] ?></td>
                                    <td><?= htmlspecialchars($p['name']) ?></td>
                                    <td>Rs. <?= htmlspecialchars($p['price']) ?></td>
                                    <td>
                                        <form method="post" onsubmit="return confirm('Delete this product?');" style="display:inline;">
                                            <input type="hidden" name="action" value="delete_product">
                                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer style="background:#000;color:#fff;text-align:center;padding:20px;margin-top:auto;">
        <p>&copy; 2025 Aveelora Admin Dashboard. All rights reserved.</p>
        <p>Administrative Panel</p>
    </footer>
</body>
</html>

