<?php
require 'includes/db.php';
session_start();
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if(!$data || !isset($data['token'])){ echo json_encode(['success'=>false,'message'=>'Invalid payload']); exit; }

$token = $data['token'];
$amount = intval($data['amount'] ?? 0);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://khalti.com/api/v2/payment/verify/");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Key test_secret_key_22d3e4623df84d8a8179f84e8c746d0b"
]);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['token'=>$token,'amount'=>$amount]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$resp = json_decode($response, true);

if($status_code == 200 && isset($resp['idx'])){
    $cart = $_SESSION['cart'] ?? [];
    if(!$cart){ echo json_encode(['success'=>false,'message'=>'Cart empty']); exit; }
    $ids = implode(',', array_map('intval', array_keys($cart)));
    $res = $conn->query("SELECT * FROM products WHERE id IN ($ids)");
    $items = []; $total = 0;
    while($r = $res->fetch_assoc()){ $items[] = $r; $total += $r['price'] * $cart[$r['id']]; }
    $user_id = isset($_SESSION['user']) ? intval($_SESSION['user']['id']) : NULL;
    $items_json = $conn->real_escape_string(json_encode(array_map(function($p) use ($cart){ return ['id'=>$p['id'],'name'=>$p['name'],'qty'=>$cart[$p['id']],'price'=>$p['price']]; }, $items)));
    $query = "INSERT INTO orders (user_id,items,total_amount,status,name,address,created_at) VALUES (".($user_id?intval($user_id):'NULL').",'".$items_json."',".$total.", 'Paid', 'Khalti', 'Khalti', NOW())";
    @mysqli_query($conn, $query);
    $_SESSION['cart'] = [];
    echo json_encode(['success'=>true,'resp'=>$resp]); exit;
} else {
    echo json_encode(['success'=>false,'message'=>'Khalti verification failed','resp'=>$resp]); 
    exit;
}
?>