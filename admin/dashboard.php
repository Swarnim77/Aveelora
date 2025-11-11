<?php
require '../includes/db.php';
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin'){ 
    header('Location: ../login.php'); 
    exit; 
}

// Get statistics
$products_count = $conn->query('SELECT COUNT(*) as count FROM products')->fetch_assoc()['count'];
$orders_count = $conn->query('SELECT COUNT(*) as count FROM orders')->fetch_assoc()['count'];
$users_count = $conn->query('SELECT COUNT(*) as count FROM users')->fetch_assoc()['count'];

// Ensure status timestamp column exists (one-time, safe to run)
@mysqli_query($conn, "ALTER TABLE orders ADD COLUMN status_updated_at DATETIME NULL");

// Fetch grouped orders
$pending_orders = $conn->query("SELECT * FROM orders WHERE UPPER(status) NOT IN ('COMPLETED','CANCELLED') ORDER BY created_at DESC LIMIT 20")->fetch_all(MYSQLI_ASSOC);
$completed_orders = $conn->query("SELECT * FROM orders WHERE UPPER(status)='COMPLETED' ORDER BY status_updated_at DESC, created_at DESC LIMIT 20")->fetch_all(MYSQLI_ASSOC);
$cancelled_orders = $conn->query("SELECT * FROM orders WHERE UPPER(status)='CANCELLED' ORDER BY status_updated_at DESC, created_at DESC LIMIT 20")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Aveelora</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Calibri', 'Roboto', 'Poppins', sans-serif;
            background-color: #f5f5dc;
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
        
        /* Statistics Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-card .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #88A71C;
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
            border-bottom: 2px solid #88A71C;
            padding-bottom: 10px;
        }
        
        /* Tabs */
        .tabs { display:flex; gap:8px; margin-bottom:10px; flex-wrap:wrap; }
        .tab-btn { background:#f0f3e0; color:#333; border:1px solid #dfe7b3; padding:8px 14px; border-radius:20px; cursor:pointer; font-size:14px; }
        .tab-btn.active { background:#88A71C; color:#fff; border-color:#88A71C; }
        .tab-panel { display:none; }
        .tab-panel.active { display:block; }
        
        /* Buttons */
        .btn {
            background-color: #88A71C;
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
        }
        
        .btn:hover {
            background-color: #7A951A;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
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
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .content-card {
                padding: 20px;
            }
            
            table {
                font-size: 14px;
            }
            
            table th,
            table td {
                padding: 8px;
            }
        }
        
        .quick-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 20px;
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
                    <p style="color: #666; font-size: 14px;">Welcome, <?= htmlspecialchars($_SESSION['user']['name'] ?? 'Admin') ?></p>
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

    <!-- Main Content -->
    <main>
        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Products</h3>
                <div class="stat-value"><?= $products_count ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Orders</h3>
                <div class="stat-value"><?= $orders_count ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="stat-value"><?= $users_count ?></div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="content-card">
            <h2>Quick Actions</h2>
            <div class="quick-actions">
                <a href="addproduct.php" class="btn">Add New Product</a>
                <a href="categories.php" class="btn">Manage Categories</a>
                <a href="viewproduct.php" class="btn">Manage Products</a>
                <a href="users.php" class="btn">Manage Users</a>
                <a href="settings.php" class="btn">Settings</a>
            </div>
        </div>

		<!-- Recent Orders -->
        <div class="content-card">
            <h2>Recent Orders</h2>
            <div class="tabs">
                <button class="tab-btn active" data-tab="pending">Pending</button>
                <button class="tab-btn" data-tab="completed">Completed</button>
                <button class="tab-btn" data-tab="cancelled">Cancelled</button>
            </div>

            <!-- Pending Orders -->
            <div class="tab-panel active" id="tab-pending">
                <?php if(count($pending_orders) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Products</th>
                                <th>Grand Total</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pending_orders as $order): $items = json_decode($order['items'], true) ?: []; ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($order['id']) ?></td>
                                    <td>
                                        <div><?= htmlspecialchars($order['name']) ?></div>
                                        <div style="color:#666;font-size:12px;"><?= htmlspecialchars($order['address']) ?></div>
                                        <?php if(!empty($order['phone'])): ?>
                                            <div style="color:#666;font-size:12px;">Phone: <?= htmlspecialchars($order['phone']) ?></div>
                                        <?php endif; ?>
                                        <div style="margin-top:4px;"><span style="background:#f0f0f0;border:1px solid #ddd;border-radius:12px;padding:2px 8px;font-size:12px;"><?= htmlspecialchars($order['status']) ?></span></div>
                                    </td>
                                    <td>
                                        <?php if($items): ?>
                                            <ul style="list-style:none;margin:0;padding:0;">
                                                <?php foreach($items as $it): 
                                                    $qty = intval($it['qty'] ?? 0);
                                                    $price = floatval($it['price'] ?? 0);
                                                    $line = $qty * $price;
                                                ?>
                                                    <li style="padding:6px 0;border-bottom:1px solid #f0f0f0;">
                                                        <div style="color:#333;"><?= htmlspecialchars($it['name'] ?? 'Product') ?></div>
                                                        <div style="color:#666;font-size:12px;">Qty: <?= $qty ?> • Price: Rs. <?= number_format($price,2) ?> • Total: Rs. <?= number_format($line,2) ?></div>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <span style="color:#666;">No items</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>Rs. <?= number_format(floatval($order['total_amount']),2) ?></td>
                                    <td>
                                        <div><?= htmlspecialchars($order['created_at']) ?></div>
                                        <?php if(!empty($order['status_updated_at'])): ?>
                                            <div style="color:#666;font-size:12px;">Updated: <?= htmlspecialchars($order['status_updated_at']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="post" action="update_order_status.php" style="display:inline-block;margin-right:6px;">
                                            <input type="hidden" name="order_id" value="<?= intval($order['id']) ?>">
                                            <input type="hidden" name="new_status" value="COMPLETED">
                                            <button type="submit" class="btn" title="Mark Completed">Completed</button>
                                        </form>
                                        <form method="post" action="update_order_status.php" style="display:inline-block;margin-right:6px;">
                                            <input type="hidden" name="order_id" value="<?= intval($order['id']) ?>">
                                            <input type="hidden" name="new_status" value="CANCELLED">
                                            <button type="submit" class="btn" style="background:#d32f2f;" title="Cancel Order">Cancel</button>
                                        </form>
                                        <a class="btn" href="invoice.php?id=<?= intval($order['id']) ?>" target="_blank" title="Generate Bill">Generate Bill</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No pending orders.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Completed Orders -->
            <div class="tab-panel" id="tab-completed">
                <?php if(count($completed_orders) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Products</th>
                                <th>Grand Total</th>
                                <th>Completed At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($completed_orders as $order): $items = json_decode($order['items'], true) ?: []; ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($order['id']) ?></td>
                                    <td>
                                        <div><?= htmlspecialchars($order['name']) ?></div>
                                        <div style="color:#666;font-size:12px;"><?= htmlspecialchars($order['address']) ?></div>
                                        <?php if(!empty($order['phone'])): ?>
                                            <div style="color:#666;font-size:12px;">Phone: <?= htmlspecialchars($order['phone']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($items): ?>
                                            <ul style="list-style:none;margin:0;padding:0;">
                                                <?php foreach($items as $it): 
                                                    $qty = intval($it['qty'] ?? 0);
                                                    $price = floatval($it['price'] ?? 0);
                                                    $line = $qty * $price;
                                                ?>
                                                    <li style="padding:6px 0;border-bottom:1px solid #f0f0f0;">
                                                        <div style="color:#333;"><?= htmlspecialchars($it['name'] ?? 'Product') ?></div>
                                                        <div style="color:#666;font-size:12px;">Qty: <?= $qty ?> • Price: Rs. <?= number_format($price,2) ?> • Total: Rs. <?= number_format($line,2) ?></div>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <span style="color:#666;">No items</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>Rs. <?= number_format(floatval($order['total_amount']),2) ?></td>
                                    <td><?= htmlspecialchars($order['status_updated_at'] ?: $order['created_at']) ?></td>
                                    <td>
                                        <a class="btn" href="invoice.php?id=<?= intval($order['id']) ?>" target="_blank" title="Download Bill">Download Bill</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No completed orders.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Cancelled Orders -->
            <div class="tab-panel" id="tab-cancelled">
                <?php if(count($cancelled_orders) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Products</th>
                                <th>Grand Total</th>
                                <th>Cancelled At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($cancelled_orders as $order): $items = json_decode($order['items'], true) ?: []; ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($order['id']) ?></td>
                                    <td>
                                        <div><?= htmlspecialchars($order['name']) ?></div>
                                        <div style="color:#666;font-size:12px;"><?= htmlspecialchars($order['address']) ?></div>
                                        <?php if(!empty($order['phone'])): ?>
                                            <div style="color:#666;font-size:12px;">Phone: <?= htmlspecialchars($order['phone']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($items): ?>
                                            <ul style="list-style:none;margin:0;padding:0;">
                                                <?php foreach($items as $it): 
                                                    $qty = intval($it['qty'] ?? 0);
                                                    $price = floatval($it['price'] ?? 0);
                                                    $line = $qty * $price;
                                                ?>
                                                    <li style="padding:6px 0;border-bottom:1px solid #f0f0f0;">
                                                        <div style="color:#333;"><?= htmlspecialchars($it['name'] ?? 'Product') ?></div>
                                                        <div style="color:#666;font-size:12px;">Qty: <?= $qty ?> • Price: Rs. <?= number_format($price,2) ?> • Total: Rs. <?= number_format($line,2) ?></div>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <span style="color:#666;">No items</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>Rs. <?= number_format(floatval($order['total_amount']),2) ?></td>
                                    <td><?= htmlspecialchars($order['status_updated_at'] ?: $order['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No cancelled orders.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <script>
        (function(){
            var buttons = document.querySelectorAll('.tab-btn');
            var panels = {
                pending: document.getElementById('tab-pending'),
                completed: document.getElementById('tab-completed'),
                cancelled: document.getElementById('tab-cancelled')
            };
            buttons.forEach(function(btn){
                btn.addEventListener('click', function(){
                    buttons.forEach(function(b){ b.classList.remove('active'); });
                    Object.values(panels).forEach(function(p){ p.classList.remove('active'); });
                    var tab = btn.getAttribute('data-tab');
                    btn.classList.add('active');
                    if(panels[tab]) panels[tab].classList.add('active');
                });
            });
        })();
        </script>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2025 Aveelora Admin Dashboard. All rights reserved.</p>
        <p>Administrative Panel</p>
    </footer>
</body>
</html>

