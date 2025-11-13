<?php
require 'includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = $_SESSION['user'] ?? null;
if (!$user) {
    header('Location: login.php');
    exit;
}

// Ensure columns needed for order metadata exist.
ensureOrdersColumn($conn, 'mobile', 'VARCHAR(30) NULL');
ensureOrdersColumn($conn, 'payment_reference', 'VARCHAR(100) NULL');

$cart = $_SESSION['cart'] ?? [];
if (!$cart) {
    header('Location: index.php');
    exit;
}

$cartIds = array_keys($cart);
if (!$cartIds) {
    header('Location: index.php');
    exit;
}

$idsList = implode(',', array_map('intval', $cartIds));
$productQuery = $conn->query("SELECT id, name, price, image FROM products WHERE id IN ($idsList)");
if (!$productQuery) {
    $_SESSION['cart'] = [];
    header('Location: cart.php');
    exit;
}

$items = [];
$total = 0.0;
while ($row = $productQuery->fetch_assoc()) {
    $pid = (int) $row['id'];
    $quantity = (int) ($cart[$pid] ?? 0);
    if ($quantity <= 0) {
        continue;
    }
    $items[$pid] = $row;
    $rowPrice = (float) $row['price'];
    $total += $rowPrice * $quantity;
}
$productQuery->free();

if (!$items) {
    $_SESSION['cart'] = [];
    header('Location: cart.php');
    exit;
}

$expectedItemCount = 0;
foreach ($cart as $qty) {
    if ((int) $qty > 0) {
        $expectedItemCount++;
    }
}

$validCart = [];
foreach ($items as $pid => $product) {
    $validCart[$pid] = max(1, (int) $cart[$pid]);
}
if (count($validCart) !== $expectedItemCount) {
    $_SESSION['cart'] = $validCart;
    header('Location: cart.php');
    exit;
}
$_SESSION['cart'] = $validCart;
$cart = $validCart;

$orderItemsPayload = [];
foreach ($items as $pid => $product) {
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
    $_SESSION['cart'] = [];
    header('Location: cart.php');
    exit;
}

$codError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cod'])) {
    $userId = isset($_SESSION['user']) ? (int) $_SESSION['user']['id'] : null;
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($name === '' || $address === '' || $phone === '') {
        $codError = 'Please provide name, address, and phone number.';
    } elseif (!preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {
        $codError = 'Please enter a valid phone number.';
    } else {
        $status = 'COD_PENDING';
        $paymentReference = null;
        $stmt = $conn->prepare(
            'INSERT INTO orders (user_id, items, total_amount, status, name, address, mobile, payment_reference, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );

        if ($stmt) {
            $stmt->bind_param(
                'isdsssss',
                $userId,
                $itemsJson,
                $total,
                $status,
                $name,
                $address,
                $phone,
                $paymentReference
            );

            if ($stmt->execute()) {
                $oid = $stmt->insert_id ?: $conn->insert_id;
                $_SESSION['cart'] = [];
                $stmt->close();
                header('Location: final.php?oid=' . urlencode((string) $oid));
                exit;
            }

            $codError = 'Failed to place order. Please try again.';
            $stmt->close();
        } else {
            $codError = 'Failed to prepare order statement.';
        }
    }
}

include 'includes/header.php';
$publicKey = getenv('KHALTI_PUBLIC_KEY') ?: (defined('KHALTI_PUBLIC_KEY') ? KHALTI_PUBLIC_KEY : '');
?>

<style>
/* ✅ Your original CSS — unchanged */
.order-summary { margin-bottom:30px; }
.order-summary h3 { color:#333; margin-bottom:15px; font-size:22px; }
.order-summary ul { list-style:none; padding:0; }
.order-summary li { padding:10px 0; border-bottom:1px solid #eee; color:#666; }
.order-summary strong { color:#333; font-size:20px; }
.payment-options { display:grid; grid-template-columns:repeat(auto-fit, minmax(300px,1fr)); gap:20px; margin-top:30px; }
.payment-option { background:#f8f8f8; padding:25px; border-radius:8px; border:1px solid #ddd; }
.payment-option h3 { color:#333; margin-bottom:15px; font-size:20px; }
.payment-option .muted { color:#666; font-size:14px; margin-top:10px; }
.payment-form { margin-top:15px; }
.payment-form label { display:block; margin-bottom:8px; color:#333; font-weight:500; }
.payment-form input { width:100%; padding:10px; border:1px solid #ddd; border-radius:5px; font-size:16px; margin-bottom:15px; font-family:inherit; }
.payment-form input:focus { outline:none; border-color:#1c2ca7ff; box-shadow:0 0 0 2px rgba(136,167,28,0.2); }
@media (max-width:768px){ .payment-options { grid-template-columns:1fr; } }
</style>

<main>
<div class="content-card">
    <h1 style="color:#333; margin-bottom:30px; font-size:28px;">Checkout</h1>

    <div class="order-summary">
        <h3>Order Summary</h3>
        <ul>
            <?php foreach ($items as $it): $q = $cart[$it['id']]; ?>
                <li><?= htmlspecialchars($it['name']) ?> x <?= $q ?> — Rs. <?= $it['price'] * $q ?></li>
            <?php endforeach; ?>
        </ul>
        <p style="margin-top:15px;"><strong>Total: Rs. <?= $total ?></strong></p>
    </div>

    <?php if ($codError): ?>
        <div style="margin-bottom:20px;padding:12px;border-radius:6px;background:#fff0f0;border:1px solid #f5c2c7;color:#842029;">
            <?= htmlspecialchars($codError) ?>
        </div>
    <?php endif; ?>

    <div class="payment-options">
		<!-- Khalti Payment Option -->
		<div class="payment-option">
			<h3>Pay Online - Khalti</h3>
			<p class="muted">Secure payment via Khalti Checkout (test mode).</p>
			<form id="khalti-form" class="payment-form" onsubmit="return false;">
				<label>Name:
					<input type="text" id="kh-name" required>
				</label>
				<label>Address:
					<input type="text" id="kh-address" required>
				</label>
                <label>Phone:
                    <input type="tel" id="kh-phone" required pattern="[0-9+\-\s]{7,20}">
                </label>
				<button type="button" class="btn" id="khalti-button">Pay with Khalti</button>
			</form>
		</div>

        <!-- eSewa Payment Option -->
        <!-- <div class="payment-option">
            <h3>Pay Online - eSewa</h3>
            <form method="POST" action="esewa_pay.php">
                <input type="hidden" name="tAmt" value="<?= $total ?>">
                <input type="hidden" name="amt" value="<?= $total ?>">
                <input type="hidden" name="txAmt" value="0">
                <input type="hidden" name="psc" value="0">
                <input type="hidden" name="pdc" value="0">
                <input type="hidden" name="pid" value="<?= time() ?>_<?= rand(1000, 9999) ?>">
                <input type="hidden" name="scd" value="EPAYTEST">
                <input type="hidden" name="su" value="<?= window_location() ?>/esewa_success.php">
                <input type="hidden" name="fu" value="<?= window_location() ?>/esewa_fail.php">
                <button type="submit" class="btn">Pay with eSewa</button>
            </form>
            <p class="muted">eSewa test mode (demo).</p>
        </div> -->

        <!-- Cash on Delivery -->
        <div class="payment-option">
            <h3>Cash on Delivery</h3>
            <form method="post" class="payment-form">
                <input type="hidden" name="cod" value="1">
                <label>Name:
                    <input type="text" name="name" required>
                </label>
                <label>Address:
                    <input type="text" name="address" required>
                </label>
                <label>Phone:
                    <input type="tel" phone="phone" required pattern="[0-9+\-\s]{7,20}">
                </label>
                <button type="submit" class="btn">💵 Confirm COD</button>
            </form>
        </div>
    </div>
</div>
</main>

<?php include 'includes/footer.php'; ?>

<?php
// ✅ Helper function stays same
function window_location() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' 
                || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domain = $_SERVER['HTTP_HOST'];
    $path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    return $protocol . $domain . $path;
}
?>

<!-- Khalti Checkout -->
<script src="https://khalti.com/static/khalti-checkout.js"></script>
<script>
(function(){
	try {
		var khaltiBtn = document.getElementById('khalti-button');
		if(!khaltiBtn) return;
		var amountPaisa = <?= intval($total * 100) ?>; // Khalti expects amount in paisa
		var publicKey = <?= json_encode($publicKey) ?>;
		var config = {
			publicKey: publicKey,
			productIdentity: 'order_' + Date.now(),
			productName: 'Paisa Order',
			productUrl: <?= json_encode(window_location() . '/payment.php') ?>,
			amount: amountPaisa,
			eventHandler: {
				onSuccess: function(payload) {
					var name = document.getElementById('kh-name').value.trim();
					var address = document.getElementById('kh-address').value.trim();
                    var phone = document.getElementById('kh-phone').value.trim();
					if(!name || !address || !phone){
						alert('Please enter name, address and phone.');
						return;
					}
					fetch('khalti_verify.php', {
						method: 'POST',
						headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify({
							token: payload.token,
							amount: amountPaisa,
							name: name,
							address: address,
                            phone: phone
						})
					}).then(function(r){ return r.json(); })
					.then(function(res){
						if(res && res.success){
							var oid = res.order_id ? ('?oid=' + encodeURIComponent(res.order_id)) : '';
							window.location.href = 'final.php' + oid;
						} else {
							alert((res && res.message) ? res.message : 'Payment verification failed.');
						}
					}).catch(function(){
						alert('Network error during verification.');
					});
				},
				onError: function(error) {
					console.error(error);
					alert('Khalti payment failed.');
				},
				onClose: function() {
				}
			}
		};
		var checkout = new KhaltiCheckout(config);
		khaltiBtn.addEventListener('click', function(){
			var name = document.getElementById('kh-name').value.trim();
			var address = document.getElementById('kh-address').value.trim();
            var phone = document.getElementById('kh-phone').value.trim();
			if(!name || !address || !phone){
				alert('Please enter name, address and phone.');
				return;
			}
			if(!publicKey){
				alert('Demo: Add your Khalti TEST public key to enable the widget.');
				return;
			}
			checkout.show({ amount: amountPaisa });
		});
	} catch(e) { }
})();
</script>
