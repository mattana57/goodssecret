<?php
session_start();
include "connectdb.php"; // ใช้ไฟล์เชื่อมต่อเดียวกับหน้าหลัก

if (!isset($_GET['id'])) { header("Location: admin_dashboard.php"); exit(); }

$id = intval($_GET['id']);
$res = $conn->query("SELECT * FROM products WHERE id = $id");
$product = $res->fetch_assoc();

if (isset($_POST['update'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $price = $_POST['price'];
    
    // อัปเดตข้อมูลพื้นฐาน
    $conn->query("UPDATE products SET name='$name', price='$price' WHERE id=$id");
    
    echo "<script>alert('แก้ไขสำเร็จ'); window.location='admin_dashboard.php?tab=products';</script>";
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #0c001c; color: white; padding: 50px; }
        .edit-card { background: rgba(255,255,255,0.05); padding: 30px; border-radius: 20px; border: 1px solid #bb86fc; }
    </style>
</head>
<body>
    <div class="container edit-card">
        <h3>แก้ไขสินค้า: <?= $product['name'] ?></h3>
        <form method="POST">
            <div class="mb-3">
                <label>ชื่อสินค้า</label>
                <input type="text" name="name" class="form-control" value="<?= $product['name'] ?>" required>
            </div>
            <div class="mb-3">
                <label>ราคา</label>
                <input type="number" name="price" class="form-control" value="<?= $product['price'] ?>" required>
            </div>
            <button type="submit" name="update" class="btn btn-primary">บันทึกการแก้ไข</button>
            <a href="admin_dashboard.php" class="btn btn-secondary">ยกเลิก</a>
        </form>
    </div>
</body>
</html>