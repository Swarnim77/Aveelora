<?php
require '../includes/db.php';
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin'){
	header('Location: ../login.php');
	exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
	$order_id = intval($_POST['order_id'] ?? 0);
	$new_status = strtoupper(trim($_POST['new_status'] ?? ''));
	$allowed = ['COMPLETED','CANCELLED'];
	if($order_id > 0 && in_array($new_status, $allowed, true)){
		@mysqli_query($conn, "ALTER TABLE orders ADD COLUMN status_updated_at DATETIME NULL");
		$stmt = $conn->prepare("UPDATE orders SET status=?, status_updated_at=NOW() WHERE id=?");
		$stmt->bind_param('si', $new_status, $order_id);
		$stmt->execute();
	}
	header('Location: dashboard.php');
	exit;
}

header('Location: dashboard.php');
exit;
?>

