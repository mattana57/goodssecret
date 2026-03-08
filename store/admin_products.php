<?php
include 'db_connect.php';

// ดึงข้อมูลสินค้าพร้อมชื่อหมวดหมู่
$sql = "SELECT p.*, c.name AS cat_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        ORDER BY p.id DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Goods Secret Store</title>
    <style>
        body {
            background-color: #0f0417; /* พื้นหลังสีเข้มตามภาพ */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: white;
            margin: 0;
            padding: 20px;
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .btn-logout {
            background-color: #ff3366;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
        }
        /* เมนูแท็บ */
        .tabs {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }
        .tab {
            padding: 10px 20px;
            color: #ccc;
            cursor: pointer;
            transition: 0.3s;
        }
        .tab.active {
            background: #3c1361; /* สีม่วงตามตัวอย่าง */
            border-radius: 15px;
            color: white;
            box-shadow: 0 0 15px rgba(168, 85, 247, 0.4);
        }
        /* กล่องจัดการสินค้า */
        .content-card {
            background: rgba(60, 19, 97, 0.4);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .table-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .btn-add {
            background: #ff00aa; /* ชมพูนีออนตามภาพ */
            color: white;
            padding: 10px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
        }
        /* ตารางสินค้า */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            text-align: left;
            color: #00eaff; /* ฟ้าสว่างตามภาพ */
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        td {
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .product-img {
            width: 55px;
            height: 55px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #a855f7;
        }
        .stock-badge {
            background: #2d2d2d;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 14px;
        }
        .action-btns a {
            margin-right: 10px;
            text-decoration: none;
            color: #aaa;
        }
        .btn-edit:hover { color: #facc15; }
        .btn-del:hover { color: #ff4d4d; }
    </style>
</head>
<body>

<div class="dashboard-header">
    <h1 style="color: #ff00aa;">ADMIN DASHBOARD</h1>
    <a href="logout.php" class="btn-logout">ออกจากระบบ</a>
</div>

<div class="tabs">
    <div class="tab active">สินค้า & สต็อก</div>
    <div class="tab">จัดการประเภท</div>
    <div class="tab">จัดการลูกค้า</div>
    <div class="tab">จัดการออเดอร์</div>
</div>

<div class="content-card">
    <div class="table-top">
        <h2>📦 สินค้า & สต็อก</h2>
        <a href="add_product.php" class="btn-add">+ เพิ่มสินค้าใหม่</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>รูป</th>
                <th>ชื่อสินค้า</th>
                <th>ราคา</th>
                <th>สต็อก</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td>
                    <img src="uploads/<?php echo $row['image']; ?>" class="product-img" onerror="this.src='https://via.placeholder.com/55'">
                </td>
                <td>
                    <strong><?php echo $row['name']; ?></strong><br>
                    <small style="color: #888;">หมวดหมู่: <?php echo $row['cat_name'] ?? 'ไม่มีหมวดหมู่'; ?></small>
                </td>
                <td style="font-weight: bold;">฿<?php echo number_format($row['price'], 0); ?></td>
                <td><span class="stock-badge"><?php echo $row['stock']; ?> ชิ้น</span></td>
                <td class="action-btns">
                    <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="btn-edit">✎</a>
                    <a href="delete_product.php?id=<?php echo $row['id']; ?>" class="btn-del" onclick="return confirm('ยืนยันการลบ?')">🗑</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>