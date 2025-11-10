<?php
session_start();
require 'includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (isset($data['cart']) && is_array($data['cart'])) {
        // Convert localStorage cart to PHP session format
        $_SESSION['cart'] = [];
        
        foreach ($data['cart'] as $item) {
            $productId = intval($item['id']);
            $quantity = intval($item['quantity']);
            
            if ($productId > 0 && $quantity > 0) {
                // Verify product exists in database
                $stmt = $conn->prepare('SELECT id, price FROM products WHERE id=?');
                $stmt->bind_param('i', $productId);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                
                if ($result) {
                    $_SESSION['cart'][$productId] = $quantity;
                }
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Cart synced successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid cart data']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>

