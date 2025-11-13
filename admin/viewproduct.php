<?php
require '../includes/db.php';
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin'){ 
    header('Location: ../login.php'); 
    exit; 
}

// Handle delete product (POST)
$message = '';
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '') === 'delete_product'){
    $pid = intval($_POST['product_id'] ?? 0);
    if($pid > 0){
        $stmt = $conn->prepare('DELETE FROM products WHERE id=?');
        $stmt->bind_param('i',$pid);
        if($stmt->execute()){
            $message = '<div class="message" style="background:#e8f5e9;color:#256029;border:1px solid #c8e6c9;">Product deleted.</div>';
        } else {
            $message = '<div class="message" style="background:#ffebee;color:#b71c1c;border:1px solid #ffcdd2;">Failed to delete.</div>';
        }
    }
}

$res = $conn->query('SELECT * FROM products');
$conn->query("ALTER TABLE orders ADD COLUMN mobile VARCHAR(30) NULL");
$orders_res = $conn->query("SELECT * FROM orders WHERE status NOT IN ('COMPLETED','CANCELLED') ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Calibri', 'Roboto', 'Poppins', sans-serif;
            background-color: #1c23a3ff;
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
            margin-bottom: 20px;
        }
        
        .btn:hover {
            background-color: #7A951A;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        .btn-danger { background-color: #d32f2f; }
        .btn-danger:hover { background-color: #b71c1c; }
        
        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        table th,
        table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        table th {
            background-color: #f8f8f8;
            font-weight: bold;
            color: #333;
        }
        
        table tr:hover {
            background-color: #f9f9f9;
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
            
            table {
                font-size: 14px;
                display: block;
                overflow-x: auto;
            }
            
            table th,
            table td {
                padding: 8px;
            }
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
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
                    <p style="color: #666; font-size: 14px;">Manage Products & Orders</p>
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
        <!-- Products Section -->
        <div class="content-card">
            <h2>Manage Products</h2>
            <a href="addproduct.php" class="btn">Add New Product</a>
            <a href="dashboard.php" class="btn" style="background-color: #666;">Back to Dashboard</a>
            <?= $message ?>
            <?php if($res->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th style="width:160px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($p = $res->fetch_assoc()): ?>
                            <tr>
                                <td><?= $p['id'] ?></td>
                                <td><?= htmlspecialchars($p['name']) ?></td>
                                <td><?= htmlspecialchars($p['category']) ?></td>
                                <td>Rs. <?= htmlspecialchars($p['price']) ?></td>
                                <td>
                                    <form method="post" onsubmit="return confirm('Delete this product?');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_product">
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>No products found.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Orders Section -->
		<div class="content-card">
            <h2>Orders</h2>
			<div style="margin-bottom:12px; display:flex; gap:10px; flex-wrap:wrap;">
				<a class="btn" href="orders_completed.php">Completed Orders</a>
				<a class="btn" href="orders_cancelled.php" style="background:#666;">Cancelled Orders</a>
				<a class="btn" href="print_orders.php?status=PENDING" target="_blank">Print Pending Bills</a>
			</div>
            <?php if($orders_res->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
							<th>Name</th>
							<th>Address</th>
							<th>Mobile</th>
							<th>Products</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Created</th>
							<th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($o = $orders_res->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($o['id']) ?></td>
								<td><?= htmlspecialchars($o['name']) ?></td>
								<td><?= htmlspecialchars($o['address']) ?></td>
								<td><?= htmlspecialchars($o['mobile']) ?></td>
								<td style="max-width:400px;">
									<?php
									$items = json_decode($o['items'], true) ?: [];
									if (!$items) {
										echo '<span style="color:#999;">-</span>';
									} else {
										echo '<div style="display:flex;flex-direction:column;gap:8px;">';
										foreach ($items as $it) {
											$img = isset($it['image']) && $it['image'] ? $it['image'] : null;
											$price = isset($it['price']) ? floatval($it['price']) : 0;
											if (!$img) {
												// fallback to current product image
												$pid = intval($it['id']);
												$pr = $conn->query("SELECT image FROM products WHERE id=".$pid);
												if ($pr && $pr->num_rows) {
													$row = $pr->fetch_assoc();
													$img = $row['image'];
												}
											}
											$src = $img;
											if ($src && strpos($src, 'http') !== 0 && strpos($src, 'assets/images/') !== 0) {
												$src = '../assets/images/' . $src;
											}
											echo '<div style="display:flex;align-items:center;gap:10px;">';
											echo '<img src="'.htmlspecialchars($src ?: '../assets/images/placeholder_dark.png').'" alt="'.htmlspecialchars($it['name']).'" style="width:46px;height:46px;object-fit:cover;border-radius:4px;border:1px solid #eee;">';
											echo '<div style="color:#333;">'.htmlspecialchars($it['name']).' x '.intval($it['qty']).'<div style="color:#666;font-size:12px;">Rs. '.number_format($price,2).'</div></div>';
											echo '</div>';
										}
										echo '</div>';
									}
									?>
									<?php if ($items): ?>
									<button type="button" class="btn" style="margin-top:8px;padding:6px 10px;font-size:13px;" onclick="var d=document.getElementById('od<?= $o['id'] ?>'); d.style.display = d.style.display==='none' ? 'block' : 'none';">View Details</button>
									<div id="od<?= $o['id'] ?>" style="display:none;margin-top:8px;border:1px solid #eee;border-radius:6px;padding:8px;">
										<table style="width:100%;border-collapse:collapse;">
											<thead>
												<tr>
													<th style="text-align:left;padding:6px;border-bottom:1px solid #eee;">Product</th>
													<th style="text-align:right;padding:6px;border-bottom:1px solid #eee;">Price</th>
													<th style="text-align:right;padding:6px;border-bottom:1px solid #eee;">Qty</th>
													<th style="text-align:right;padding:6px;border-bottom:1px solid #eee;">Line Total</th>
												</tr>
											</thead>
											<tbody>
												<?php $dynTotal = 0.0; foreach($items as $it): $ip = floatval($it['price']); $iq = intval($it['qty']); $lt = $ip * $iq; $dynTotal += $lt; ?>
													<tr>
														<td style="padding:6px;border-bottom:1px solid #f3f3f3;"><?= htmlspecialchars($it['name']) ?></td>
														<td style="padding:6px;border-bottom:1px solid #f3f3f3;text-align:right;">Rs. <?= number_format($ip,2) ?></td>
														<td style="padding:6px;border-bottom:1px solid #f3f3f3;text-align:right;"><?= $iq ?></td>
														<td style="padding:6px;border-bottom:1px solid #f3f3f3;text-align:right;">Rs. <?= number_format($lt,2) ?></td>
													</tr>
												<?php endforeach; ?>
											</tbody>
											<tfoot>
												<tr>
													<td colspan="3" style="padding:6px;text-align:right;"><strong>Computed Total</strong></td>
													<td style="padding:6px;text-align:right;"><strong>Rs. <?= number_format($dynTotal,2) ?></strong></td>
												</tr>
											</tfoot>
										</table>
									</div>
									<?php endif; ?>
								</td>
                                <td><?= htmlspecialchars($o['status']) ?></td>
                                <td>Rs. <?= htmlspecialchars($o['total_amount']) ?></td>
                                <td><?= htmlspecialchars($o['created_at']) ?></td>
								<td>
									<form method="post" action="order_update.php" style="display:inline;">
										<input type="hidden" name="order_id" value="<?= $o['id'] ?>">
										<input type="hidden" name="status" value="COMPLETED">
										<button type="submit" class="btn">Mark Completed</button>
									</form>
									<form method="post" action="order_update.php" style="display:inline;">
										<input type="hidden" name="order_id" value="<?= $o['id'] ?>">
										<input type="hidden" name="status" value="CANCELLED">
										<button type="submit" class="btn btn-danger">Cancel</button>
									</form>
								</td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>No orders yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2025 Currency Exchange Admin Dashboard. All rights reserved.</p>
        <p>Administrative Panel</p>
    </footer>
</body>
</html>
