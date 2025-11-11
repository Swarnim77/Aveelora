<?php
require '../includes/db.php';
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin'){
	header('Location: ../login.php');
	exit;
}

$id = intval($_GET['id'] ?? 0);
$order = null;
if($id > 0){
	$res = $conn->query("SELECT * FROM orders WHERE id=".$id." LIMIT 1");
	if($res && $res->num_rows === 1){
		$order = $res->fetch_assoc();
	}
}
if(!$order){
	echo "<!DOCTYPE html><html><body><p>Order not found.</p></body></html>";
	exit;
}
$items = json_decode($order['items'], true) ?: [];
$subtotal = 0.0;
foreach($items as $it){
	$qty = intval($it['qty'] ?? 0);
	$price = floatval($it['price'] ?? 0);
	$subtotal += $qty * $price;
}
$total = floatval($order['total_amount'] ?? $subtotal);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Invoice #<?= htmlspecialchars($order['id']) ?> - Aveelora</title>
	<style>
		body{ font-family:'Calibri','Roboto','Poppins',sans-serif; background:#fff; color:#333; }
		.invoice-wrapper{ max-width:800px; margin:30px auto; padding:30px; border:1px solid #ddd; border-radius:8px; }
		.header{ text-align:center; margin-bottom:20px; }
		.header h1{ font-size:28px; font-weight:bold; margin:0; }
		.subheader{ text-align:center; color:#666; margin-bottom:20px; }
		.meta{ margin-bottom:20px; display:grid; grid-template-columns:1fr 1fr; gap:10px; }
		.meta div{ background:#f8f8f8; padding:10px; border:1px solid #eee; border-radius:6px; }
		table{ width:100%; border-collapse:collapse; margin-top:10px; }
		th, td{ padding:10px; border-bottom:1px solid #eee; text-align:left; }
		th{ background:#f8f8f8; }
		.total{ text-align:right; margin-top:15px; font-size:18px; }
		.footer{ text-align:center; margin-top:30px; color:#333; font-weight:600; }
		.actions{ text-align:right; margin:10px 0 20px; }
		.btn{ background:#88A71C; color:#fff; border:none; padding:10px 18px; border-radius:5px; cursor:pointer; font-size:14px; }
		.btn:hover{ background:#7A951A; }
		@media print{
			.actions{ display:none; }
			.invoice-wrapper{ border:none; }
		}
	</style>
</head>
<body>
	<div class="invoice-wrapper">
		<div class="actions">
			<button class="btn" onclick="window.print()">Download / Print</button>
			<a class="btn" href="dashboard.php" style="text-decoration:none; display:inline-block; margin-left:6px;">Back</a>
		</div>
		<div class="header">
			<h1>Aveelora Nepal</h1>
		</div>
        <div class="subheader">
            Number is: <?= htmlspecialchars($order['phone'] ?? '') ?>
        </div>
		<div class="subheader">
			Invoice for Order #<?= htmlspecialchars($order['id']) ?>
		</div>
		<div class="meta">
			<div>
				<div><strong>Order ID:</strong> #<?= htmlspecialchars($order['id']) ?></div>
				<div><strong>Order Date:</strong> <?= htmlspecialchars($order['created_at']) ?></div>
				<div><strong>Status:</strong> <?= htmlspecialchars($order['status']) ?></div>
			</div>
			<div>
				<div><strong>Customer:</strong> <?= htmlspecialchars($order['name']) ?></div>
				<div><strong>Address:</strong> <?= htmlspecialchars($order['address']) ?></div>
			</div>
		</div>

		<table>
			<thead>
				<tr>
					<th>Product</th>
					<th>Qty</th>
					<th>Price</th>
					<th>Total</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($items as $it): 
					$qty = intval($it['qty'] ?? 0);
					$price = floatval($it['price'] ?? 0);
					$line = $qty * $price;
				?>
				<tr>
					<td><?= htmlspecialchars($it['name'] ?? 'Product') ?></td>
					<td><?= $qty ?></td>
					<td>Rs. <?= number_format($price,2) ?></td>
					<td>Rs. <?= number_format($line,2) ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<div class="total">
			<div>Subtotal: <strong>Rs. <?= number_format($subtotal,2) ?></strong></div>
			<div>Total Amount: <strong>Rs. <?= number_format($total,2) ?></strong></div>
			<div>Payment Status: <strong><?= htmlspecialchars($order['status']) ?></strong></div>
		</div>

		<div class="footer">
			Thank you for shopping
		</div>
	</div>
</body>
</html>


