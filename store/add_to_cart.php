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
$variant_id = isset($_GET['variant_id']) && $_GET['variant_id'] != "" ? intval($_GET['variant_id']) : 0; 
$action = $_GET['action'] ?? '';

if($product_id > 0 && $qty > 0){
    // [ปรับเพิ่ม]: ดึงราคาที่ถูกต้องตามประเภทสินค้า
    if ($variant_id > 0) {
        // กรณีมีรุ่นย่อย ดึงราคาจากตาราง product_variants
        $res = $conn->query("SELECT price FROM product_variants WHERE id = $variant_id");
        $v_data = $res->fetch_assoc();
        $price = $v_data['price'];
    } else {
        // กรณีสินค้าปกติ ดึงราคาจากตาราง products
        $res = $conn->query("SELECT price FROM products WHERE id = $product_id");
        $p_data = $res->fetch_assoc();
        $price = $p_data['price'];
    }

    $check = $conn->query("SELECT * FROM cart WHERE user_id = $user_id AND product_id = $product_id AND variant_id = $variant_id");
    
    if($check->num_rows > 0){
        $conn->query("UPDATE cart SET quantity = quantity + $qty WHERE user_id = $user_id AND product_id = $product_id AND variant_id = $variant_id");
    } else {
        // [ปรับเพิ่ม]: บันทึกราคา (price) ลงในตาราง cart ด้วย
        $conn->query("INSERT INTO cart (user_id, product_id, quantity, variant_id, price) VALUES ($user_id, $product_id, $qty, $variant_id, $price)");
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