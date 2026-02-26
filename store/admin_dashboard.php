<?php
session_start();
include "connectdb.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit();
}

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'products';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Goods Secret Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        /* ปรับพื้นหลังไม่ให้ขาว และใช้สีเดียวกับธีมหลัก */
        body { 
            background: #0c001c; 
            color: #ffffff !important; 
            font-family: 'Segoe UI', sans-serif; 
            min-height: 100vh;
        }
        .text-neon-pink { color: #f107a3 !important; text-shadow: 0 0 10px rgba(241, 7, 163, 0.6); }
        .text-neon-cyan { color: #00f2fe !important; text-shadow: 0 0 10px rgba(0, 242, 254, 0.6); }
        
        /* เมนูแท็บ */
        .nav-pills .nav-link { color: #ffffff; border-radius: 12px; margin: 0 5px; transition: 0.3s; border: 1px solid transparent; }
        .nav-pills .nav-link:hover { background: rgba(187, 134, 252, 0.1); border-color: #bb86fc; }
        .nav-pills .nav-link.active { background: #bb86fc !important; color: #120018 !important; box-shadow: 0 0 15px #bb86fc; font-weight: bold; }

        /* การ์ดแบบโปร่งแสง (Glassmorphism) */
        .glass-panel { 
            background: rgba(255, 255, 255, 0.03); 
            backdrop-filter: blur(15px); 
            border: 1px solid rgba(187, 134, 252, 0.2); 
            border-radius: 25px; 
            padding: 30px; 
        }

        /* ปรับแต่งตารางไม่ให้ขาว */
        .table { color: #ffffff !important; --bs-table-bg: transparent; }
        .table thead th { color: #00f2fe !important; border-bottom: 2px solid rgba(0, 242, 254, 0.3); }
        .table tbody td { border-color: rgba(255,255,255,0.05); vertical-align: middle; }
        
        /* ปุ่มสไตล์นีออน */
        .btn-neon-pink { background: linear-gradient(135deg, #f107a3, #bb86fc); border: none; color: #fff; }
        .btn-neon-pink:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(241, 7, 163, 0.5); color: #fff; }
        .btn-outline-cyan { border-color: #00f2fe; color: #00f2fe; }
        .btn-outline-cyan:hover { background: #00f2fe; color: #000; box-shadow: 0 0 10px #00f2fe; }
    </style>
</head>
<body>

<div class="container-fluid py-5 px-4">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h2 class="fw-bold text-neon-pink"><i class=\"bi bi-shield-lock-fill me-2\"></i> ADMIN DASHBOARD</h2>
        <a href="logout.php" class="btn btn-danger rounded-pill px-4 shadow">ออกจากระบบ</a>
    </div>

    <ul class="nav nav-pills mb-5 justify-content-center">
        <li class="nav-item"><a class="nav-link <?= $tab=='products'?'active':'' ?>" href="admin_dashboard.php?tab=products">สินค้า & สต็อก</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab=='categories'?'active':'' ?>" href="admin_dashboard.php?tab=categories">จัดการประเภท</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab=='customers'?'active':'' ?>" href="admin_dashboard.php?tab=customers">จัดการลูกค้า</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab=='orders'?'active':'' ?>" href="admin_dashboard.php?tab=orders">จัดการออเดอร์</a></li>
    </ul>

    <div class="tab-content">
        <?php 
            if($tab == 'products') include "admin_products.php";
            elseif($tab == 'categories') include "admin_categories.php";
            elseif($tab == 'customers') include "admin_customers.php";
            elseif($tab == 'orders') include "admin_orders_list.php";
        ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() { 
        $('.datatable-js').DataTable({ 
            "language": { "search": "ค้นหา:", "lengthMenu": "แสดง _MENU_ รายการ" } 
        }); 
    });
</script>
</body>
</html>