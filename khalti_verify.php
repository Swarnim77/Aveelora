<?php
require 'includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['success' => false, 'message' => 'Invalid request method']);
	exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$token = $data['token'] ?? '';
$amount = intval($data['amount'] ?? 0);
$name = trim($data['name'] ?? 'Guest');
$address = trim($data['address'] ?? 'Not provided');
$phone = trim($data['phone'] ?? '');

if ($token === '' || $amount <= 0) {
	echo json_encode(['success' => false, 'message' => 'Missing token or amount']);
	exit;
}
if ($phone === '') {
	echo json_encode(['success' => false, 'message' => 'Phone is required']);
	exit;
}

// Verify cart total matches amount
$cart = $_SESSION['cart'] ?? [];
if (!$cart) {
	echo json_encode(['success' => false, 'message' => 'Empty cart']);
	exit;
}
$ids = implode(',', array_map('intval', array_keys($cart)));
$res = $conn->query("SELECT id, price FROM products WHERE id IN ($ids)");
$total = 0;
while ($r = $res->fetch_assoc()) {
	$total += $r['price'] * $cart[$r['id']];
}
if (intval($total * 100) !== $amount) {
	echo json_encode(['success' => false, 'message' => 'Amount mismatch']);
	exit;
}

// Call Khalti verify API
$verifyUrl = 'https://khalti.com/api/v2/payment/verify/';
$payload = json_encode(['token' => $token, 'amount' => $amount]);
$ch = curl_init($verifyUrl);
// Use secret key from environment or block if missing (demo-safe)
$secretKey = getenv('KHALTI_SECRET_KEY') ?: (defined('KHALTI_SECRET_KEY') ? KHALTI_SECRET_KEY : '');
if (!$secretKey) {
	echo json_encode(['success' => false, 'message' => 'Khalti secret key not configured']);
	exit;
}
curl_setopt($ch, CURLOPT_HTTPHEADER, [
	'Authorization: Key ' . $secretKey,
	'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($curlErr) {
	echo json_encode(['success' => false, 'message' => 'cURL error']);
	exit;
}

$resp = json_decode($response, true);
if ($httpCode !== 200 || !is_array($resp) || !isset($resp['idx'])) {
	$msg = isset($resp['detail']) ? $resp['detail'] : 'Verification failed';
	echo json_encode(['success' => false, 'message' => $msg]);
	exit;
}

// Create order
$user_id = isset($_SESSION['user']) ? intval($_SESSION['user']['id']) : NULL;
$items_json = $conn->real_escape_string(json_encode(array_map(function($pid) use ($conn, $cart){
	$pid = intval($pid);
	$sr = $conn->query("SELECT id, name, price FROM products WHERE id=$pid")->fetch_assoc();
	return [
		'id' => $sr['id'],
		'name' => $sr['name'],
		'qty' => $cart[$pid],
		'price' => $sr['price']
	];
}, array_keys($cart))));

$txn_id = $conn->real_escape_string($resp['idx']);
@mysqli_query($conn, "ALTER TABLE orders ADD COLUMN phone VARCHAR(30) NULL");
$stmt = $conn->prepare("INSERT INTO orders (user_id, items, total_amount, status, name, address, phone, created_at) VALUES (?,?,?,?,?,?,?, NOW())");
$status = 'PAID_KHALTI';
$stmt->bind_param('isdssss', $user_id, $items_json, $total, $status, $name, $address, $phone);
@$stmt->execute();
$orderId = $conn->insert_id;

// Clear cart
$_SESSION['cart'] = [];

echo json_encode(['success' => true, 'order_id' => $orderId]);
?>


