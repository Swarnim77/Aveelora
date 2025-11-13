<?php
require '../includes/db.php';
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin'){
	header('Location: ../login.php');
	exit;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
	$oid = intval($_POST['order_id'] ?? 0);
	$status = trim($_POST['status'] ?? '');
	if($oid > 0 && in_array($status, ['COMPLETED','CANCELLED'], true)){
		$stmt = $conn->prepare('UPDATE orders SET status=? WHERE id=?');
		$stmt->bind_param('si', $status, $oid);
		$stmt->execute();
	}
}
header('Location: viewproduct.php');
exit;
?>


