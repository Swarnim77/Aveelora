<?php
require '../includes/db.php';
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin'){
	header('Location: ../login.php');
	exit;
}
$conn->query("ALTER TABLE orders ADD COLUMN mobile VARCHAR(30) NULL");
$res = $conn->query("SELECT * FROM orders WHERE status='COMPLETED' ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Completed Orders</title>
	<style>
		body { font-family:'Calibri','Roboto','Poppins',sans-serif; background:#f5f5dc; }
		main { max-width:1200px; margin:30px auto; padding:0 20px; }
		.content-card { background:white; padding:30px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1); }
		table { width:100%; border-collapse:collapse; margin-top:16px; }
		th,td { padding:12px; border-bottom:1px solid #ddd; text-align:left; }
		th { background:#f8f8f8; }
		.btn { background:#88A71C; color:#fff; padding:8px 14px; border:none; border-radius:6px; text-decoration:none; display:inline-block; }
	</style>
</head>
<body>
	<main>
		<div class="content-card">
			<h2>Completed Orders</h2>
			<div style="display:flex;gap:10px;flex-wrap:wrap;">
				<a class="btn" href="viewproduct.php">Back</a>
				<a class="btn" href="print_orders.php?status=COMPLETED" target="_blank">Print Completed Bills</a>
			</div>
			<table>
				<thead>
					<tr>
						<th>ID</th>
						<th>Name</th>
						<th>Address</th>
						<th>Mobile</th>
						<th>Products</th>
						<th>Total</th>
						<th>Created</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php while($o = $res->fetch_assoc()): ?>
					<tr>
						<td><?= htmlspecialchars($o['id']) ?></td>
						<td><?= htmlspecialchars($o['name']) ?></td>
						<td><?= htmlspecialchars($o['address']) ?></td>
						<td><?= htmlspecialchars($o['mobile']) ?></td>
						<td style="max-width:340px;">
							<?php
							$items = json_decode($o['items'], true) ?: [];
							if (!$items) { echo '<span style="color:#999;">-</span>'; }
							else {
								$list = array_map(function($it){
									return htmlspecialchars($it['name']).' x '.intval($it['qty']);
								}, $items);
								echo implode(', ', $list);
							}
							?>
						</td>
						<td>Rs. <?= htmlspecialchars($o['total_amount']) ?></td>
						<td><?= htmlspecialchars($o['created_at']) ?></td>
						<td>
							<a class="btn" href="print_orders.php?oid=<?= $o['id'] ?>" target="_blank">Print Bill</a>
						</td>
					</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
		</div>
	</main>
</body>
</html>


