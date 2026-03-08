<?php
session_start();
include "connectdb.php";

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$fullname = $conn->real_escape_string($_POST['fullname']);
$phone = $conn->real_escape_string($_POST['phone']);
$address = $conn->real_escape_string($_POST['address']);
$province = $conn->real_escape_string($_POST['province']);
$zipcode = $conn->real_escape_string($_POST['zipcode']);

// ปรับค่าให้ตรงกับ ENUM ในฐานข้อมูล
$payment_method = ($_POST['payment_method'] === 'bank_transfer') ? 'bank' : 'cod';
$order_status = "pending";

$sql_cart = "SELECT cart.*, products.price FROM cart 
             JOIN products ON cart.product_id = products.id 
             WHERE cart.user_id = $user_id";
$cart_items = $conn->query($sql_cart);

if ($cart_items->num_rows == 0) { die("ไม่มีสินค้าในตะกร้า"); }

$total_price = 0;
while ($item = $cart_items->fetch_assoc()) { $total_price += ($item['price'] * $item['quantity']); }

/* ================= [ส่วนจัดการรูปภาพสลิป]: คงเดิมตามความต้องการคุณ ================= */
$slip_name = "";
if ($payment_method === 'bank' && isset($_FILES['slip_image']) && $_FILES['slip_image']['error'] == 0) {
    $ext = pathinfo($_FILES['slip_image']['name'], PATHINFO_EXTENSION);
    $slip_name = "slip_" . time() . "_" . $user_id . "." . $ext;
    $target_dir = "slips/";
    if (!is_dir($target_dir)) { @mkdir($target_dir, 0777, true); }
    move_uploaded_file($_FILES['slip_image']['tmp_name'], $target_dir . $slip_name);
}

$conn->begin_transaction();

try {
    // อัปเดตที่อยู่ลงโปรไฟล์
    $sql_update_user = "UPDATE users SET 
                        fullname = '$fullname', phone = '$phone', address = '$address', 
                        province = '$province', zipcode = '$zipcode' 
                        WHERE id = $user_id";
    $conn->query($sql_update_user);

    // บันทึกออเดอร์ลงตาราง orders
    $sql_order = "INSERT INTO orders (user_id, total_price, fullname, phone, address, province, zipcode, payment_method, slip_image, status, created_at) 
                  VALUES ('$user_id', '$total_price', '$fullname', '$phone', '$address', '$province', '$zipcode', '$payment_method', '$slip_name', '$order_status', NOW())";
    
    if ($conn->query($sql_order)) {
        $order_id = $conn->insert_id;
        $cart_items->data_seek(0);
        
        while ($item = $cart_items->fetch_assoc()) {
            $v_id_val = !empty($item['variant_id']) ? $item['variant_id'] : 0;
            $v_id_sql = !empty($item['variant_id']) ? $item['variant_id'] : "NULL";

            // 1. บันทึกรายละเอียดออเดอร์ลงตาราง order_details
            $sql_details = "INSERT INTO order_details (order_id, product_id, variant_id, quantity, price) 
                            VALUES ('$order_id', '{$item['product_id']}', $v_id_sql, '{$item['quantity']}', '{$item['price']}')";
            $conn->query($sql_details);

            /* --- [ส่วนที่เพิ่มใหม่]: ตรรกะการหักสต็อกสินค้า --- */
            if ($v_id_val > 0) {
                // เคสที่ 1: สินค้ามีรุ่นย่อย ให้หักสต็อกในตาราง product_variants
                $conn->query("UPDATE product_variants SET stock = stock - {$item['quantity']} WHERE id = $v_id_val");
            } else {
                // เคสที่ 2: สินค้าปกติ ให้หักสต็อกในตาราง products หลัก
                $conn->query("UPDATE products SET stock = stock - {$item['quantity']} WHERE id = {$item['product_id']}");
            }
        }
        
        // ล้างตะกร้าและยืนยัน Transaction
        $conn->query("DELETE FROM cart WHERE user_id = $user_id");
        $conn->commit();
        
        header("Location: profile.php?order_complete=1");
        exit();
    } else {
        throw new Exception("SQL Error: " . $conn->error);
    }
} catch (Exception $e) {
    $conn->rollback();
    die("เกิดข้อผิดพลาด: " . $e->getMessage());
}
?>