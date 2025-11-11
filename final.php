<?php
require 'includes/db.php';
include 'includes/header.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$user = $_SESSION['user'] ?? null;
$oid = intval($_GET['oid'] ?? 0);
$order = null;
$message = '';
if ($oid > 0) {
	$stmt = $conn->prepare('SELECT * FROM orders WHERE id=?');
	$stmt->bind_param('i',$oid);
	$stmt->execute();
	$order = $stmt->get_result()->fetch_assoc();
}
// Handle cancel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order_id'])) {
	$cid = intval($_POST['cancel_order_id']);
	$stmt = $conn->prepare('SELECT * FROM orders WHERE id=?');
	$stmt->bind_param('i',$cid);
	$stmt->execute();
	$ord = $stmt->get_result()->fetch_assoc();
	if ($ord) {
		// Allow cancel if user owns it (or guest order) and not cancelled already
		$owned = $user ? (intval($ord['user_id']) === intval($user['id'])) : ($ord['user_id'] === null);
		if ($owned && $ord['status'] !== 'CANCELLED') {
			$up = $conn->prepare("UPDATE orders SET status='CANCELLED' WHERE id=?");
			$up->bind_param('i',$cid);
			if ($up->execute()) {
				$message = 'Order cancelled.';
				if ($oid === $cid) {
					$order['status'] = 'CANCELLED';
				}
			} else {
				$message = 'Failed to cancel order.';
			}
		} else {
			$message = 'Cannot cancel this order.';
		}
	}
}
?>
<style>
    .thank-you {
        text-align: center;
        padding: 60px 20px;
    }
    
    .thank-you h1 {
        color: #88A71C;
        font-size: 36px;
        margin-bottom: 20px;
    }
    
    .thank-you p {
        color: #666;
        font-size: 18px;
        margin-bottom: 15px;
    }
    
    .thank-you a {
        color: #88A71C;
        text-decoration: none;
        font-weight: 500;
    }
    
    .thank-you a:hover {
        text-decoration: underline;
    }
</style>

<main>
    <div class="content-card">
        <?php if($message): ?>
            <div class="message" style="background:#f8f8f8;border:1px solid #ddd;padding:12px;border-radius:6px;margin-bottom:12px;"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if($order): ?>
            <div class="thank-you">
                <h1>Thank You!</h1>
                <p>Your order has been placed successfully.</p>
                <div style="margin:20px auto;max-width:600px;text-align:left;">
                    <h3 style="color:#333;margin-bottom:10px;">Bill</h3>
                    <?php
                    $items = json_decode($order['items'], true) ?: [];
                    $total = floatval($order['total_amount']);
                    echo '<ul style="list-style:none;padding:0;margin:0 0 10px 0;">';
                    foreach($items as $it){
                        $line = floatval($it['price']) * intval($it['qty']);
                        echo '<li style="padding:8px 0;border-bottom:1px solid #eee;color:#666;">'.
                             htmlspecialchars($it['name']).' x '.intval($it['qty']).' — Rs. '.$line.
                             '</li>';
                    }
                    echo '</ul>';
                    echo '<p style="margin-top:10px;"><strong>Total: Rs. '.$total.'</strong></p>';
                    echo '<p style="margin-top:6px;color:#555;">Status: <strong>'.htmlspecialchars($order['status']).'</strong></p>';
                    ?>
                    <?php if($order['status']!=='CANCELLED'): ?>
                        <form method="post" onsubmit="return confirm('Cancel this order?');" style="margin-top:15px;">
                            <input type="hidden" name="cancel_order_id" value="<?= $order['id'] ?>">
                            <button type="submit" class="btn" style="background:#d32f2f;">Cancel Order</button>
                        </form>
                    <?php endif; ?>
                </div>
                <p style="margin-top: 30px;">
                    <a href="index.php" class="btn" style="color: white;">Back to Home</a>
                </p>
            </div>
        <?php else: ?>
            <div class="thank-you">
                <h1>Thank You!</h1>
                <p>Your order has been placed successfully.</p>
                <p>Check admin panel to view orders (demo).</p>
                <p style="margin-top: 30px;">
                    <a href="index.php" class="btn">Back to</a>
                </p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
<script>
(function(){
	try {
		// Clear localStorage cart after successful order
		localStorage.removeItem('cart');
		// Update header cart count if function exists
		if (typeof updateCartCount === 'function') {
			updateCartCount();
		} else {
			// Fallback: hide badge
			document.querySelectorAll('.cart-count').forEach(function(el){
				el.textContent = '0';
				el.style.display = 'none';
			});
		}
	} catch(e){}
})();
</script>
