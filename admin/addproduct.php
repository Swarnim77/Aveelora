<?php
require '../includes/db.php';
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin'){ 
    header('Location: ../login.php'); 
    exit; 
}
$msg='';
// Ensure categories table exists (with image column)
$conn->query("CREATE TABLE IF NOT EXISTS categories (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) UNIQUE, image VARCHAR(200) DEFAULT 'placeholder_dark.png')");
// Ensure image column exists for older deployments
$hasImageColRes = $conn->query("SHOW COLUMNS FROM categories LIKE 'image'");
if($hasImageColRes && $hasImageColRes->num_rows === 0){
	$conn->query("ALTER TABLE categories ADD COLUMN image VARCHAR(200) DEFAULT 'placeholder_dark.png'");
}
// Load categories
$catOptions = $conn->query("SELECT name FROM categories ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
if($_SERVER['REQUEST_METHOD']==='POST'){
  $name=$conn->real_escape_string($_POST['name']); 
  $selectedCat=trim($_POST['category'] ?? '');
  $newCat=trim($_POST['new_category'] ?? '');
  $catName = $newCat !== '' ? $newCat : $selectedCat;
  $cat=$conn->real_escape_string($catName);
  $price = floatval($_POST['price']); 
  $desc = $conn->real_escape_string($_POST['description']);
  $imgname = basename($_FILES['image']['name'] ?? '');
  if($imgname){
    move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../assets/images/'. $imgname);
  } else { 
    $imgname='placeholder_dark.png'; 
  }
  // Persist category if new
  if($catName !== ''){
    $stmtc = $conn->prepare('INSERT IGNORE INTO categories(name) VALUES (?)');
    $stmtc->bind_param('s',$catName);
    $stmtc->execute();
  }
  $stmt = $conn->prepare('INSERT INTO products (name,category,price,description,image) VALUES (?,?,?,?,?)');
  $stmt->bind_param('ssdss',$name,$cat,$price,$desc,$imgname);
  if($stmt->execute()) {
    $msg='<span style="color: #1c2ca7ff; font-weight: bold;">Product added successfully!</span>';
  } else {
    $msg='<span style="color: #d32f2f; font-weight: bold;">Error adding product.</span>';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Admin Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Calibri', 'Roboto', 'Poppins', sans-serif;
            background-color: #979712ff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header Styles */
        header {
            background-color: #d3d3d3;
            padding: 20px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .header-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        nav {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        nav a {
            color: #333;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background-color 0.3s;
            font-weight: 500;
        }
        
        nav a:hover {
            background-color: rgba(0,0,0,0.1);
        }
        
        /* Main Content */
        main {
            flex: 1;
            max-width: 1200px;
            width: 100%;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        /* Content Card */
        .content-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .content-card h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
            border-bottom: 2px solid #1c2ca7ff;
            padding-bottom: 10px;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            font-family: inherit;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #1c2ca7ff;
            box-shadow: 0 0 0 2px rgba(136, 167, 28, 0.2);
        }
        
        /* Buttons */
        .btn {
            background-color: #1c2ca7ff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s, transform 0.2s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-right: 10px;
            margin-top: 10px;
        }
        
        .btn:hover {
            background-color: #7A951A;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            background-color: #666;
        }
        
        .btn-secondary:hover {
            background-color: #555;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            background-color: #f0f0f0;
        }
        
        /* Footer */
        footer {
            background-color: #000000;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: auto;
        }
        
        footer p {
            margin: 5px 0;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            nav {
                width: 100%;
                margin-top: 15px;
            }
            
            .content-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <div class="header-content">
                <div>
                    <h1 class="header-title">Admin Dashboard</h1>
                    <p style="color: #666; font-size: 14px;">Add New Product</p>
                </div>
                <nav>
                    <a href="dashboard.php">Home</a>
                    <a href="viewproduct.php">Products</a>
                    <a href="users.php">Users</a>
                    <a href="reports.php">Reports</a>
                    <a href="settings.php">Settings</a>
                    <a href="../logout.php">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <div class="content-card">
            <h2>Add Product</h2>
            <?php if($msg): ?>
                <div class="message"><?= $msg ?></div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Product Name *</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <option value="" disabled selected>Select a category</option>
                        <?php foreach($catOptions as $co): ?>
                            <option value="<?= htmlspecialchars($co['name']) ?>"><?= htmlspecialchars($co['name']) ?></option>
                        <?php endforeach; ?>
                        <option value="__new__">+ Add new category...</option>
                    </select>
                    <input type="text" id="new_category" name="new_category" placeholder="New category name" style="display:none; margin-top:10px;">
                </div>
                
                <div class="form-group">
                    <label for="price">Price (Rs.) *</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="image">Product Image</label>
                    <input type="file" id="image" name="image" accept="image/*">
                </div>
                
                <button type="submit" class="btn">Add Product</button>
                <a href="viewproduct.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2025 Paisa Satne Thau Admin Dashboard. All rights reserved.</p>
        <p>Administrative Panel</p>
    </footer>
</body>
</html>
<script>
(function(){
    const sel = document.getElementById('category');
    const newInput = document.getElementById('new_category');
    if(sel && newInput){
        sel.addEventListener('change', function(){
            if(this.value === '__new__'){
                newInput.style.display = 'block';
                newInput.required = true;
                newInput.focus();
            } else {
                newInput.style.display = 'none';
                newInput.required = false;
                newInput.value = '';
            }
        });
    }
})();
</script>
