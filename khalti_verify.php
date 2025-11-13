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

if (!is_array($data)) {
	echo json_encode(['success' => false, 'message' => 'Malformed request payload']);
	exit;
}

$token = isset($data['token']) ? trim((string) $data['token']) : '';
$amount = isset($data['amount']) ? (int) $data['amount'] : 0;
$name = trim($data['name'] ?? '');
$address = trim($data['address'] ?? '');
$phone = trim($data['phone'] ?? '');

if ($token === '' || $amount <= 0) {
	echo json_encode(['success' => false, 'message' => 'Missing token or amount']);
	exit;
}
if ($name === '' || $address === '' || $phone === '') {
	echo json_encode(['success' => false, 'message' => 'Name, address and phone are required']);
	exit;
}
if (!preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {
	echo json_encode(['success' => false, 'message' => 'Invalid phone number']);
	exit;
}

// Verify cart total matches amount
$cart = $_SESSION['cart'] ?? [];
if (!$cart) {
	echo json_encode(['success' => false, 'message' => 'Empty cart']);
	exit;
}
$ids = array_keys($cart);
if (!$ids) {
	echo json_encode(['success' => false, 'message' => 'Empty cart']);
	exit;
}
$idsSql = implode(',', array_map('intval', $ids));
$res = $conn->query("SELECT id, name, price, image FROM products WHERE id IN ($idsSql)");
if (!$res) {
	echo json_encode(['success' => false, 'message' => 'Could not load cart items']);
	exit;
}
$total = 0;
$dbItems = [];
while ($r = $res->fetch_assoc()) {
	$pid = (int) $r['id'];
	$qty = (int) ($cart[$pid] ?? 0);
	if ($qty <= 0) {
		continue;
	}
	$dbItems[$pid] = $r;
	$total += (float) $r['price'] * $qty;
}
$res->free();

if (!$dbItems) {
	echo json_encode(['success' => false, 'message' => 'Cart items unavailable']);
	exit;
}

$expectedItemCount = 0;
foreach ($cart as $qty) {
	if ((int) $qty > 0) {
		$expectedItemCount++;
	}
}

$validCart = [];
foreach ($dbItems as $pid => $product) {
	$validCart[$pid] = max(1, (int) $cart[$pid]);
}
if (count($validCart) !== $expectedItemCount) {
	$_SESSION['cart'] = $validCart;
	echo json_encode(['success' => false, 'message' => 'Some cart items are no longer available']);
	exit;
}
$_SESSION['cart'] = $validCart;

$expectedAmount = (int) round($total * 100);
if ($expectedAmount !== $amount) {
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
$orderItemsPayload = [];
foreach ($dbItems as $pid => $product) {
	$orderItemsPayload[] = [
		'id' => $pid,
		'name' => $product['name'],
		'qty' => $validCart[$pid],
		'price' => (float) $product['price'],
		'image' => $product['image'],
	];
}

$itemsJson = json_encode($orderItemsPayload, JSON_UNESCAPED_UNICODE);
if ($itemsJson === false) {
	echo json_encode(['success' => false, 'message' => 'Failed to serialize cart items']);
	exit;
}

ensureOrdersColumn($conn, 'mobile', 'VARCHAR(30) NULL');
ensureOrdersColumn($conn, 'payment_reference', 'VARCHAR(100) NULL');

$user_id = isset($_SESSION['user']) ? intval($_SESSION['user']['id']) : NULL;
$status = 'PAID_KHALTI';
$paymentReference = $resp['idx'];
$stmt = $conn->prepare(
	'INSERT INTO orders (user_id, items, total_amount, status, name, address, mobile, payment_reference, created_at)
	 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
);

if (!$stmt) {
	echo json_encode(['success' => false, 'message' => 'Failed to create order']);
	exit;
}

$stmt->bind_param(
	'isdsssss',
	$user_id,
	$itemsJson,
	$total,
	$status,
	$name,
	$address,
	$phone,
	$paymentReference
);

if (!$stmt->execute()) {
	$stmt->close();
	echo json_encode(['success' => false, 'message' => 'Failed to save order']);
	exit;
}

$orderId = $stmt->insert_id ?: $conn->insert_id;
$stmt->close();

// Clear cart
$_SESSION['cart'] = [];

echo json_encode(['success' => true, 'order_id' => $orderId]);
?>


