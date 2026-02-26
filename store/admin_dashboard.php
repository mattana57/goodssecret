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
        /* บังคับพื้นหลังมืดและตัวหนังสือขาวสว่าง */
        body { 
            background: #0c001c; 
            color: #ffffff !important; 
            font-family: 'Segoe UI', sans-serif; 
            min-height: 100vh;
        }
        
        /* หัวข้อสีชมพูนีออนสว่าง */
        .text-neon-pink { color: #ff00b3 !important; text-shadow: 0 0 15px rgba(255, 0, 179, 0.8); }
        .text-neon-cyan { color: #00f2fe !important; text-shadow: 0 0 15px rgba(0, 242, 254, 0.8); }
        
        /* การ์ดแบบโปร่งแสงแต่เน้นตัวหนังสือด้านในให้สว่าง */
        .glass-panel { 
            background: rgba(255, 255, 255, 0.05); 
            backdrop-filter: blur(15px); 
            border: 1px solid rgba(187, 134, 252, 0.3); 
            border-radius: 25px; 
            padding: 30px; 
            color: #ffffff !important;
        }

        /* ตาราง: เน้นสีขาวสว่างและขอบสว่าง */
        .table { color: #ffffff !important; --bs-table-bg: transparent; }
        .table thead th { 
            color: #00f2fe !important; 
            border-bottom: 2px solid #00f2fe; 
            font-size: 1.1rem;
            text-shadow: 0 0 5px rgba(0, 242, 254, 0.5);
        }
        .table tbody td { 
            border-color: rgba(255,255,255,0.15); 
            vertical-align: middle; 
            color: #ffffff !important;
            font-size: 1rem;
        }
        
        /* เมนูแท็บ */
        .nav-pills .nav-link { color: #ffffff !important; border-radius: 12px; margin: 0 5px; opacity: 0.7; }
        .nav-pills .nav-link.active { background: #bb86fc !important; color: #120018 !important; box-shadow: 0 0 20px #bb86fc; font-weight: bold; opacity: 1; }

        /* ปุ่มสีสันสดใส */
        .btn-neon-pink { background: linear-gradient(135deg, #ff00b3, #bb86fc); border: none; color: #fff; font-weight: bold; }
        .btn-neon-pink:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(255, 0, 179, 0.6); color: #fff; }
        
        /* ปรับสีช่องค้นหาและตัวเลขของ DataTable */
        .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate {
            color: #ffffff !important;
        }
        input[type="search"], select {
            background: rgba(255,255,255,0.1) !important;
            color: #ffffff !important;
            border: 1px solid #bb86fc !important;
        }
    </style>
</head>
<body>

<div class="container-fluid py-5 px-4">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h2 class="fw-bold text-neon-pink">ADMIN DASHBOARD</h2>
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