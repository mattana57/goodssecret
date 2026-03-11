<?php
session_start();
include "connectdb.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit();
}

if (isset($_GET['del_id']) && isset($_GET['type'])) {
    $id = intval($_GET['del_id']);
    $type = $_GET['type'];
    
    if ($type == 'product') {
        $conn->query("DELETE FROM product_variants WHERE product_id=$id");
        $conn->query("DELETE FROM products WHERE id=$id");
    }
    elseif ($type == 'category') {
        $check = $conn->query("SELECT id FROM products WHERE category_id = $id LIMIT 1");
        if ($check->num_rows > 0) {
            echo "<script>alert('ไม่สามารถลบได้! เนื่องจากยังมีสินค้าที่ใช้งานประเภทนี้อยู่'); window.location='admin_dashboard.php?tab=categories';</script>";
            exit();
        }
        $conn->query("DELETE FROM categories WHERE id=$id");
    }
    elseif ($type == 'customer') {
        if ($id == $_SESSION['user_id']) {
            echo "<script>alert('ไม่สามารถลบตัวเองได้!'); window.location='admin_dashboard.php?tab=customers';</script>";
            exit();
        }
        $conn->query("DELETE FROM users WHERE id=$id");
    }
    
    header("Location: admin_dashboard.php?tab=".$_GET['tab']."&deleted=1"); 
    exit();
}

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'products';

if (isset($_GET['ajax_action'])) {
    if ($_GET['ajax_action'] == 'update_stock_direct') {
        $pid = intval($_GET['pid']); $val = intval($_GET['val']);
        $conn->query("UPDATE products SET stock = $val WHERE id = $pid");
        echo $val; exit();
    }
    if ($_GET['ajax_action'] == 'update_stock_value') {
        $vid = intval($_GET['vid']); $new_val = intval($_GET['val']);
        $conn->query("UPDATE product_variants SET stock = $new_val WHERE id = $vid");
        echo $new_val; exit();
    }
    if ($_GET['ajax_action'] == 'get_user_history') {
        $uid = intval($_GET['uid']);
        $q = $conn->query("SELECT * FROM orders WHERE user_id = $uid ORDER BY created_at DESC");
        
        $html = '<table class="table table-hover small text-white">
                    <thead>
                        <tr class="text-info">
                            <th>บิล ID</th>
                            <th>วันที่</th>
                            <th>ยอดรวม</th>
                            <th>สถานะ</th>
                            <th class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        while($r = $q->fetch_assoc()) {
            $status_color = "";
            if($r['status'] == 'delivered') $status_color = "text-success";
            elseif($r['status'] == 'cancelled') $status_color = "text-danger";
            elseif($r['status'] == 'shipped') $status_color = "text-info";
            else $status_color = "text-warning";

            $html .= "<tr class='align-middle'>
                        <td class='fw-bold'>#".str_pad($r['id'],5,'0',STR_PAD_LEFT)."</td>
                        <td>".date('d/m/y', strtotime($r['created_at']))."</td>
                        <td class='text-neon-cyan'>฿".number_format($r['total_price'])."</td>
                        <td><span class='fw-bold {$status_color}'>".strtoupper($r['status'])."</span></td>
                        <td class='text-center'>
                            <a href='admin_order_view.php?id={$r['id']}' class='btn btn-xs btn-outline-info rounded-pill px-3 py-1' style='font-size: 0.75rem;'>
                                <i class='bi bi-search me-1'></i> ดูรายละเอียด
                            </a>
                        </td>
                      </tr>";
        }
        if($q->num_rows == 0) $html .= '<tr><td colspan="5" class="text-center py-4 opacity-50">ไม่พบประวัติการสั่งซื้อ</td></tr>';
        echo $html . '</tbody></table>'; exit();
    }
}
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
        body { background: #0c001c; color: #ffffff !important; font-family: 'Segoe UI', sans-serif; min-height: 100vh; }
        .text-neon-pink { color: #ff00b3 !important; text-shadow: 0 0 10px rgba(255, 0, 179, 0.5); }
        .text-neon-cyan { color: #00f2fe !important; text-shadow: 0 0 10px rgba(0, 242, 254, 0.5); }
        .nav-pills .nav-link { color: #ffffff !important; border-radius: 12px; margin: 0 5px; opacity: 0.7; }
        .nav-pills .nav-link.active { background: #bb86fc !important; color: #120018 !important; box-shadow: 0 0 20px #bb86fc; font-weight: bold; opacity: 1; }
        .glass-panel { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(15px); border: 1px solid rgba(187, 134, 252, 0.3); border-radius: 25px; padding: 30px; }
        .table { color: #ffffff !important; --bs-table-bg: transparent; }
        .table thead th { color: #00f2fe !important; border-bottom: 2px solid #00f2fe; }
        .table tbody td { border-color: rgba(255,255,255,0.1); vertical-align: middle; color: #ffffff !important; }
        .btn-neon-pink { background: linear-gradient(135deg, #ff00b3, #bb86fc); border: none; color: #fff; font-weight: bold; }
        .modal-content { background: #1a0028 !important; border: 2px solid #bb86fc; color: #ffffff !important; border-radius: 20px; }
        .form-control, .form-select { background: rgba(255, 255, 255, 0.1) !important; color: #fff !important; border: 1px solid rgba(187, 134, 252, 0.5) !important; }
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

<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">ประวัติ: <span id="h_uname" class="text-neon-cyan"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="h_content"></div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    $(document).ready(function() { 
        $('.datatable-js').DataTable({ "language": { "search": "ค้นหา:", "lengthMenu": "แสดง _MENU_ รายการ" } }); 
    });

    function viewUserHistory(uid, uname) { 
        $('#h_uname').text(uname); $('#historyModal').modal('show'); 
        $('#h_content').html('<div class="text-center py-5"><div class="spinner-border text-info"></div></div>');
        $.get('admin_dashboard.php', {ajax_action: 'get_user_history', uid: uid}, function(data) { $('#h_content').html(data); }); 
    }

    function manualUpdateStockDirect(pid, val) { $.get('admin_dashboard.php', {ajax_action: 'update_stock_direct', pid: pid, val: val}); }
</script>
</body>
</html>