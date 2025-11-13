<?php
require '../includes/db.php';
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin'){
	header('Location: ../login.php');
	exit;
}
$status = isset($_GET['status']) ? strtoupper(trim($_GET['status'])) : 'ALL';
$oid = isset($_GET['oid']) ? intval($_GET['oid']) : 0;
$where = '';
if ($oid > 0) {
	$where = "WHERE id=" . $oid;
} elseif (in_array($status, ['COMPLETED','CANCELLED','PAID_KHALTI','COD'])) {
	$where = "WHERE status='" . $conn->real_escape_string($status) . "'";
} elseif ($status === 'PENDING') {
	$where = "WHERE status NOT IN ('COMPLETED','CANCELLED')";
}
$res = $conn->query("SELECT * FROM orders $where ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Print Orders</title>
	<style>
		body { font-family:'Calibri','Roboto','Poppins',sans-serif; background:#fff; color:#000; }
		.toolbar { display:flex; gap:10px; align-items:center; padding:12px; border-bottom:1px solid #eee; }
		.btn { background:#333; color:#fff; padding:8px 12px; text-decoration:none; border-radius:4px; display:inline-block; }
		@media print { .toolbar { display:none; } .page-break { page-break-after: always; } }
		.order { border:1px solid #ddd; border-radius:6px; padding:12px; margin:16px auto; max-width:1000px; }
		.order h3 { margin:0 0 8px 0; }
		table { width:100%; border-collapse:collapse; margin-top:8px; }
		th,td { padding:8px; border-bottom:1px solid #eee; text-align:left; }
		th { background:#f8f8f8; }
		.total { text-align:right; font-weight:bold; }
	</style>
</head>
<body>
	<div class="toolbar">
		<a class="btn" href="viewproduct.php">Back</a>
		<a class="btn" href="#" onclick="window.print();return false;">Print</a>
		<span><?php if($oid>0){ echo 'Order #'.htmlspecialchars($oid); } else { echo 'Status: '.htmlspecialchars($status); } ?></span>
	</div>
	<?php while($o = $res->fetch_assoc()): ?>
	<div class="order">
		<h3 style="text-transform:uppercase;">CurrencyExchange.np</h3>
		<p style="margin:4px 0 10px 0;">Thankyou for Shopping</p>
		<p style="margin:0 0 10px 0;"><strong>Order Number:</strong> <?= htmlspecialchars($o['id']) ?></p>
		<div style="display:flex; gap:20px; flex-wrap:wrap; font-size:14px; color:#333;">
			<div><strong>Name:</strong> <?= htmlspecialchars($o['name']) ?></div>
			<div><strong>Mobile:</strong> <?= htmlspecialchars($o['mobile']) ?></div>
			<div><strong>Address:</strong> <?= htmlspecialchars($o['address']) ?></div>
			<div><strong>Created:</strong> <?= htmlspecialchars($o['created_at']) ?></div>
		</div>
		<?php
		$items = json_decode($o['items'], true) ?: [];
		$dynTotal = 0.0;
		?>
		<table>
			<thead>
				<tr>
					<th>Product</th>
					<th style="text-align:right;">Price</th>
					<th style="text-align:right;">Qty</th>
					<th style="text-align:right;">Line Total</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($items as $it): $ip = floatval($it['price']); $iq = intval($it['qty']); $lt = $ip * $iq; $dynTotal += $lt; ?>
				<tr>
					<td><?= htmlspecialchars($it['name']) ?></td>
					<td style="text-align:right;">Rs. <?= number_format($ip,2) ?></td>
					<td style="text-align:right;"><?= $iq ?></td>
					<td style="text-align:right;">Rs. <?= number_format($lt,2) ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="3" class="total">Total</td>
					<td style="text-align:right;"><strong>Rs. <?= number_format($dynTotal,2) ?></strong></td>
				</tr>
			</tfoot>
		</table>
	</div>
	<div class="page-break"></div>
	<?php endwhile; ?>
</body>
</html>


