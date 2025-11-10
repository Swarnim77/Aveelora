<?php
require 'includes/db.php';

// ✅ Start session safely (no warning if already active)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$cart = $_SESSION['cart'] ?? [];
if (!$cart) {
    // Redirect before sending any HTML output
    header("Location: index.php");
    exit;
}

// Get cart items
$ids = implode(',', array_map('intval', array_keys($cart)));
$res = $conn->query("SELECT * FROM products WHERE id IN ($ids)");
$items = [];
$total = 0;
while ($r = $res->fetch_assoc()) { 
    $items[] = $r; 
    $total += $r['price'] * $cart[$r['id']]; 
}

// ✅ Handle "Cash on Delivery" form before output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cod'])) {
    $user_id = isset($_SESSION['user']) ? intval($_SESSION['user']['id']) : NULL;
    $name = $conn->real_escape_string($_POST['name'] ?? 'Guest');
    $address = $conn->real_escape_string($_POST['address'] ?? 'Not provided');
    $items_json = $conn->real_escape_string(json_encode(array_map(function ($p) use ($cart) { 
        return [
            'id' => $p['id'],
            'name' => $p['name'],
            'qty' => $cart[$p['id']],
            'price' => $p['price']
        ]; 
    }, $items)));
    
    $query = "INSERT INTO orders (user_id, items, total_amount, status, name, address, created_at) 
              VALUES (" . ($user_id ? $user_id : 'NULL') . ", '" . $items_json . "', " . $total . ", 'COD', '" . $name . "', '" . $address . "', NOW())";
    
    @mysqli_query($conn, $query);
    $_SESSION['cart'] = [];

    // Redirect safely before HTML
    header("Location: final.php");
    exit;
}

// ✅ Safe to include header now (HTML starts here)
include 'includes/header.php';
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
.payment-form input:focus { outline:none; border-color:#88A71C; box-shadow:0 0 0 2px rgba(136,167,28,0.2); }
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

    <div class="payment-options">
        <!-- eSewa Payment Option -->
        <div class="payment-option">
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
        </div>

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
