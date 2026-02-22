<?php
session_start();
include "connectdb.php";

// --- [ระบบความปลอดภัย]: เช็คสิทธิ์แอดมินก่อนเข้าใช้งาน ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// --- [Logic ส่วนที่ 1]: จัดการประเภทสินค้า (Categories) ---
if (isset($_POST['save_category'])) {
    $name = $conn->real_escape_string($_POST['cat_name']);
    $slug = strtolower(str_replace(' ', '-', $name));
    if (!empty($_POST['cat_id'])) {
        $conn->query("UPDATE categories SET name='$name', slug='$slug' WHERE id=" . intval($_POST['cat_id']));
    } else {
        $conn->query("INSERT INTO categories (name, slug) VALUES ('$name', '$slug')\");
    }
    header("Location: admin_dashboard.php?tab=categories&success=1"); exit();
}

// --- [Logic ส่วนที่ 2]: จัดการสินค้าหลัก (Products) ---
if (isset($_POST['save_product'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $price = $_POST['price'];
    $cat_id = $_POST['category_id'];
    $desc = $conn->real_escape_string($_POST['description']);
    
    if (!empty($_POST['product_id'])) {
        $p_id = intval($_POST['product_id']);
        $sql = "UPDATE products SET name='$name', price='$price', category_id='$cat_id', description='$desc' WHERE id=$p_id";
    } else {
        $sql = "INSERT INTO products (name, price, category_id, description) VALUES ('$name', '$price', '$cat_id', '$desc')";
    }
    $conn->query($sql);
    header("Location: admin_dashboard.php?tab=products&success=1"); exit();
}

// 2.2 ลบสินค้า
if (isset($_GET['delete_product'])) {
    $id = intval($_GET['delete_product']);
    $conn->query("DELETE FROM products WHERE id=$id");
    header("Location: admin_dashboard.php?tab=products"); exit();
}

// 2.3 จัดการรูปภาพสินค้า
if (isset($_POST['upload_image'])) {
    $p_id = intval($_POST['product_id']);
    $file = $_FILES['p_image'];
    if ($file['error'] === 0) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_name = "prod_" . $p_id . "_" . time() . "." . $ext;
        if (move_uploaded_file($file['tmp_name'], "uploads/" . $new_name)) {
            $conn->query("INSERT INTO product_images (product_id, image_path) VALUES ($p_id, '$new_name')");
        }
    }
    header("Location: admin_dashboard.php?tab=products&img_ok=1"); exit();
}

// 2.4 จัดการตัวเลือกสินค้า (Variants)
if (isset($_POST['add_variant'])) {
    $p_id = intval($_POST['product_id']);
    $v_name = $conn->real_escape_string($_POST['variant_name']);
    $conn->query("INSERT INTO product_variants (product_id, variant_name) VALUES ($p_id, '$v_name')");
    header("Location: admin_dashboard.php?tab=products&var_ok=1"); exit();
}

// --- [Logic ส่วนที่ 3]: จัดการออเดอร์ (Orders) ---
if (isset($_POST['update_order_status'])) {
    $o_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    $conn->query("UPDATE orders SET status='$status' WHERE id=$o_id");
    header("Location: admin_dashboard.php?tab=orders&updated=1"); exit();
}

// --- ดึงข้อมูลเพื่อแสดงผล ---
$cats = $conn->query("SELECT * FROM categories ORDER BY id DESC");
$prods = $conn->query("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
$orders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");
$users = $conn->query("SELECT * FROM users ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Goods Secret</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        :root {
            --neon-pink: #ff007f;
            --neon-purple: #bc13fe;
            --dark-bg: #0f051d;
            --glass-bg: rgba(255, 255, 255, 0.05);
        }
        body { 
            background-color: var(--dark-bg); 
            color: #ffffff; /* ปรับตัวหนังสือหลักเป็นสีขาว */
            font-family: 'Sarabun', sans-serif;
        }
        .navbar-admin { 
            background: rgba(0,0,0,0.5); 
            backdrop-filter: blur(10px); 
            border-bottom: 1px solid var(--neon-pink);
        }
        .card-custom { 
            background: var(--glass-bg); 
            border: 1px solid rgba(255,255,255,0.1); 
            border-radius: 15px; 
            backdrop-filter: blur(5px);
            color: #ffffff;
        }
        .nav-tabs .nav-link { color: #ffffff; border: none; }
        .nav-tabs .nav-link.active { 
            background: var(--neon-pink); 
            color: white; 
            border-radius: 10px;
            box-shadow: 0 0 15px var(--neon-pink);
        }
        /* ปรับแต่งตารางให้สว่างขึ้น */
        .table { color: #ffffff !important; }
        .table thead th { 
            color: var(--neon-pink) !important; 
            border-bottom: 2px solid var(--neon-pink);
            text-transform: uppercase;
            font-size: 0.9rem;
        }
        .table tbody td { 
            vertical-align: middle; 
            border-color: rgba(255,255,255,0.1);
            color: #f8f9fa !important; /* เน้นสีตัวหนังสือในตาราง */
        }
        /* ส่วนของ DataTables */
        .dataTables_wrapper .dataTables_length, 
        .dataTables_wrapper .dataTables_filter, 
        .dataTables_wrapper .dataTables_info, 
        .dataTables_wrapper .dataTables_processing, 
        .dataTables_wrapper .dataTables_paginate {
            color: #ffffff !important;
        }
        .form-select, .form-control {
            background-color: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: #ffffff !important;
        }
        .form-control::placeholder { color: rgba(255,255,255,0.5); }
        .btn-neon-pink { 
            background: var(--neon-pink); 
            color: white; 
            border: none; 
            box-shadow: 0 0 10px var(--neon-pink);
        }
        .btn-neon-pink:hover { background: #e60072; color: white; }
        h2, h4, .h5 { color: var(--neon-pink); font-weight: bold; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-admin mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#"><i class="bi bi-shield-lock-fill me-2"></i>ADMIN DASHBOARD</a>
        <div class="ms-auto">
            <a href="index.php" class="btn btn-outline-light btn-sm me-2">ดูหน้าหน้าร้าน</a>
            <a href="logout.php" class="btn btn-danger btn-sm">ออกจากระบบ</a>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <ul class="nav nav-tabs mb-4 border-0 gap-2" id="adminTab" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#products">1. จัดการสินค้า</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#categories">2. จัดการประเภท</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#users">3. จัดการลูกค้า</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#orders">4. จัดการออเดอร์</button>
        </li>
    </ul>

    <div class="tab-content card-custom p-4">
        <div class="tab-pane fade show active" id="products">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4>รายการสินค้าทั้งหมด</h4>
                <button class="btn btn-neon-pink" onclick="openAddProduct()"><i class="bi bi-plus-circle me-1"></i>เพิ่มสินค้า</button>
            </div>
            <div class="table-responsive">
                <table class="table datatable-js">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ชื่อสินค้า</th>
                            <th>ประเภท</th>
                            <th>ราคา</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($p = $prods->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= $p['id'] ?></td>
                            <td><?= $p['name'] ?></td>
                            <td><span class="badge bg-purple"><?= $p['cat_name'] ?></span></td>
                            <td class="text-info fw-bold">฿<?= number_format($p['price']) ?></td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-warning" onclick='openEditProduct(<?= json_encode($p) ?>)'><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-sm btn-outline-info" onclick="openVariantModal(<?= $p['id'] ?>, '<?= $p['name'] ?>')"><i class="bi bi-layers"></i></button>
                                    <a href="?delete_product=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('ลบสินค้านี้?')"><i class="bi bi-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="categories">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4>จัดการประเภทสินค้า</h4>
                <button class="btn btn-neon-pink" onclick="openCatModal()"><i class="bi bi-plus-circle me-1"></i>เพิ่มประเภท</button>
            </div>
            <table class="table">
                <thead><tr><th>ID</th><th>ชื่อประเภท</th><th>จัดการ</th></tr></thead>
                <tbody>
                    <?php while($c = $cats->fetch_assoc()): ?>
                    <tr>
                        <td><?= $c['id'] ?></td>
                        <td><?= $c['name'] ?></td>
                        <td><button class="btn btn-sm btn-outline-warning" onclick='openEditCat(<?= json_encode($c) ?>)'>แก้ไข</button></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="tab-pane fade" id="users">
            <h4>ข้อมูลลูกค้าในระบบ</h4>
            <table class="table datatable-js">
                <thead><tr><th>ID</th><th>ชื่อผู้ใช้</th><th>ชื่อ-นามสกุล</th><th>เบอร์โทร</th><th>จัดการ</th></tr></thead>
                <tbody>
                    <?php while($u = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><?= $u['username'] ?></td>
                        <td><?= $u['fullname'] ?></td>
                        <td><?= $u['phone'] ?></td>
                        <td>-</td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="tab-pane fade" id="orders">
            <h4>จัดการรายการสั่งซื้อ</h4>
            <table class="table datatable-js">
                <thead><tr><th>ID</th><th>ลูกค้า</th><th>ยอดรวม</th><th>สถานะ</th><th>วันที่</th><th>จัดการ</th></tr></thead>
                <tbody>
                    <?php while($o = $orders->fetch_assoc()): ?>
                    <tr>
                        <td>#<?= $o['id'] ?></td>
                        <td><?= $o['fullname'] ?></td>
                        <td class="text-info">฿<?= number_format($o['total_price']) ?></td>
                        <td>
                            <?php 
                                $s_color = ['pending'=>'warning', 'paid'=>'success', 'shipped'=>'info', 'cancelled'=>'danger'];
                                $s_text = ['pending'=>'รอดำเนินการ', 'paid'=>'ชำระเงินแล้ว', 'shipped'=>'จัดส่งแล้ว', 'cancelled'=>'ยกเลิก'];
                            ?>
                            <span class="badge bg-<?= $s_color[$o['status']] ?>"><?= $s_text[$o['status']] ?></span>
                        </td>
                        <td class="small"><?= $o['created_at'] ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-light" onclick='viewOrderDetail(<?= json_encode($o) ?>)'><i class="bi bi-eye"></i></button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content card-custom" style="background: #1a0b2e;">
            <div class="modal-header border-secondary">
                <h5 class="modal-title" id="p_title">จัดการสินค้า</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="admin_dashboard.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="p_id">
                    <div class="mb-3"><label>ชื่อสินค้า</label><input type="text" name="name" id="p_name" class="form-control" required></div>
                    <div class="mb-3"><label>ราคา (บาท)</label><input type="number" name="price" id="p_price" class="form-control" required></div>
                    <div class="mb-3"><label>ประเภท</label>
                        <select name="category_id" id="p_cat" class="form-select">
                            <?php $cats->data_seek(0); while($c = $cats->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label>คำอธิบาย</label><textarea name="description" id="p_desc" class="form-control" rows="3"></textarea></div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="submit" name="save_product" class="btn btn-neon-pink">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() { 
        $('.datatable-js').DataTable({ "language": { "search": "ค้นหาด่วน:", "lengthMenu": "แสดง _MENU_ รายการ" } }); 
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('tab')) { new bootstrap.Tab(document.querySelector(`button[data-bs-target=\"#${urlParams.get('tab')}\"]`)).show(); }
    });

    function openAddProduct() { $('#p_id').val(''); $('#p_title').text('เพิ่มสินค้าใหม่'); $('#productModal').modal('show'); }
    function openEditProduct(p) { $('#p_id').val(p.id); $('#p_name').val(p.name); $('#p_price').val(p.price); $('#p_cat').val(p.category_id); $('#p_desc').val(p.description); $('#p_title').text('แก้ไขข้อมูลสินค้า'); $('#productModal').modal('show'); }
    function openVariantModal(id, name) { $('#v_pid').val(id); $('#v_pname').text(name); $('#variantModal').modal('show'); }
    function openCatModal() { $('#cat_id').val(''); $('#cat_title').text('เพิ่มประเภทสินค้า'); $('#catModal').modal('show'); }
    function openEditCat(c) { $('#cat_id').val(c.id); $('#cat_name').val(c.name); $('#cat_title').text('แก้ไขประเภทสินค้า'); $('#catModal').modal('show'); }
    
    function viewOrderDetail(o) { 
        // Logic สำหรับการดูรายละเอียดออเดอร์
        alert("ออเดอร์ #" + o.id + " โดย " + o.fullname);
    }
</script>

</body>
</html>