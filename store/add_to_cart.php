<?php
session_start();
include 'connectdb.php'; 

if(!isset($_SESSION['user_id'])){
    if(isset($_GET['ajax'])){ echo json_encode(['status' => 'error']); exit(); }
    header("Location: login.php"); exit();
}

$user_id = $_SESSION['user_id'];
$product_id = intval($_GET['id']);
$qty = isset($_GET['qty']) ? intval($_GET['qty']) : 1; 
// ถ้าไม่มี variant_id ส่งมา ให้ถือว่าเป็น 0 (สินค้าปกติ)
$variant_id = isset($_GET['variant_id']) && $_GET['variant_id'] != "" ? intval($_GET['variant_id']) : 0; 
$action = $_GET['action'] ?? '';

if($product_id > 0 && $qty > 0){
    // เช็คทั้ง product_id และ variant_id
    $check = $conn->query("SELECT * FROM cart WHERE user_id = $user_id AND product_id = $product_id AND variant_id = $variant_id");
    
    if($check->num_rows > 0){
        // ถ้ามีอยู่แล้ว (ไม่ว่าจะสินค้าปกติหรือมีแยกย่อย) ให้บวกเพิ่ม
        $conn->query("UPDATE cart SET quantity = quantity + $qty WHERE user_id = $user_id AND product_id = $product_id AND variant_id = $variant_id");
    } else {
        // ถ้ายังไม่มี ให้เพิ่มแถวใหม่ลงไป
        $conn->query("INSERT INTO cart (user_id, product_id, quantity, variant_id) VALUES ($user_id, $product_id, $qty, $variant_id)");
    }
}

if(isset($_GET['ajax'])){
    $q = $conn->query("SELECT SUM(quantity) as total FROM cart WHERE user_id = $user_id");
    $row = $q->fetch_assoc();
    echo json_encode(['status' => 'success', 'total' => $row['total'] ?? 0]);
    exit();
}

header("Location: " . ($action == 'buy' ? 'cart.php' : $_SERVER['HTTP_REFERER']));
exit();
?>