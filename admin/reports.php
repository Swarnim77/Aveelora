<?php
require '../includes/db.php';
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin'){ 
    header('Location: ../login.php'); 
    exit; 
}

// Get report data
$total_orders = $conn->query('SELECT COUNT(*) as count FROM orders')->fetch_assoc()['count'];
// Revenue: sum of orders marked COMPLETED by admin
$revRow = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'COMPLETED'")->fetch_assoc();
$total_revenue = $revRow && $revRow['total'] !== null ? floatval($revRow['total']) : 0.0;
$cod_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'COD'")->fetch_assoc()['count'];
// Online paid (Khalti) before completion
$paid_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'PAID_KHALTI'")->fetch_assoc()['count'];
$total_products = $conn->query('SELECT COUNT(*) as count FROM products')->fetch_assoc()['count'];
$total_users = $conn->query('SELECT COUNT(*) as count FROM users')->fetch_assoc()['count'];

// Recent orders
$recent_orders = $conn->query('SELECT * FROM orders ORDER BY created_at DESC LIMIT 10')->fetch_all(MYSQLI_ASSOC);

// Top products (simplified - would need order_items table for accurate data)
$top_products = $conn->query('SELECT name, COUNT(*) as order_count FROM products GROUP BY name ORDER BY order_count DESC LIMIT 5')->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Dashboard</title>
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
        
        .btn-secondary {
            background-color: #666;
        }
        
        .btn-secondary:hover {
            background-color: #555;
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
            border-top: 1px solid #d3d3d3;
        }
        
        footer p {
            margin: 5px 0;
            font-size: 14px;
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
        
        .revenue {
            color: #88A71C;
            font-size: 32px;
            font-weight: bold;
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
                    <p style="color: #666; font-size: 14px;">Reports & Analytics</p>
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
        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Revenue</h3>
                <div class="revenue">Rs. <?= number_format($total_revenue, 2) ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Orders</h3>
                <div class="stat-value"><?= $total_orders ?></div>
            </div>
            <div class="stat-card">
                <h3>Paid Orders</h3>
                <div class="stat-value"><?= $paid_orders ?></div>
            </div>
            <div class="stat-card">
                <h3>COD Orders</h3>
                <div class="stat-value"><?= $cod_orders ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Products</h3>
                <div class="stat-value"><?= $total_products ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="stat-value"><?= $total_users ?></div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="content-card">
            <h2>Recent Orders</h2>
            <a href="dashboard.php" class="btn btn-secondary" style="margin-bottom: 20px;">Back to Dashboard</a>
            <?php if(count($recent_orders) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th>Total Amount</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_orders as $order): ?>
                            <tr>
                                <td><?= htmlspecialchars($order['id']) ?></td>
                                <td><?= htmlspecialchars($order['name']) ?></td>
                                <td><?= htmlspecialchars($order['address']) ?></td>
                                <td><?= htmlspecialchars($order['status']) ?></td>
                                <td>Rs. <?= htmlspecialchars($order['total_amount']) ?></td>
                                <td><?= htmlspecialchars($order['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>No orders found.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sales Summary -->
        <div class="content-card">
            <h2>Sales Summary</h2>
            <p style="color: #666; margin-bottom: 15px;">Total Revenue (Completed Orders): <strong style="color: #88A71C;">Rs. <?= number_format($total_revenue, 2) ?></strong></p>
            <p style="color: #666; margin-bottom: 15px;">Total Orders: <strong><?= $total_orders ?></strong></p>
            <p style="color: #666; margin-bottom: 15px;">Average Order Value (Revenue/Orders): <strong>Rs. <?= $total_orders > 0 ? number_format($total_revenue / $total_orders, 2) : '0.00' ?></strong></p>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2025 Aveelora Admin Dashboard. All rights reserved.</p>
        <p>Administrative Panel</p>
    </footer>
</body>
</html>

