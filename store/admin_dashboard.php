<?php
session_start();
include "connectdb.php";

// --- [ระบบความปลอดภัย]: เช็คสิทธิ์แอดมินก่อนเข้าใช้งาน ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// --- [ส่วน AJAX]: จัดการสต็อกและการดึงข้อมูลแบบ Real-time ---
if (isset($_GET['ajax_action'])) {
    if ($_GET['ajax_action'] == 'update_stock_direct') {
        $pid = intval($_GET['pid']);
        $val = intval($_GET['val']);
        $conn->query("UPDATE products SET stock = $val WHERE id = $pid");
        echo $val; exit();
    }
    if ($_GET['ajax_action'] == 'update_stock_value') {
        $vid = intval($_GET['vid']);
        $new_val = intval($_GET['val']);
        $conn->query("UPDATE product_variants SET stock = $new_val WHERE id = $vid");
        echo $new_val; exit();
    }
    // --- [คืนชีพ]: ฟังก์ชันดึงประวัติการสั่งซื้อลูกค้า ---
    if ($_GET['ajax_action'] == 'get_user_history') {
        $uid = intval($_GET['uid']);
        $q = $conn->query("SELECT * FROM orders WHERE user_id = $uid ORDER BY created_at DESC");
        $html = '<table class="table table-hover small text-white"><thead><tr class="text-info"><th>บิล ID</th><th>วันที่</th><th>ยอดรวม</th><th>สถานะ</th><th>จัดการ</th></tr></thead><tbody>';
        while($r = $q->fetch_assoc()) {
            $status_class = ($r['status'] == 'delivered') ? 'bg-success' : (($r['status'] == 'cancelled') ? 'bg-danger' : 'bg-warning text-dark');
            $html .= '<tr class="align-middle border-bottom border-secondary">
                        <td>#'.str_pad($r['id'], 5, '0', STR_PAD_LEFT).'</td>
                        <td>'.date('d/m/y H:i', strtotime($r['created_at'])).'</td>
                        <td class="fw-bold text-info">฿'.number_format($r['total_price']).'</td>
                        <td><span class="badge '.$status_class.'">'.$r['status'].'</span></td>
                        <td><a href="admin_order_view.php?id='.$r['id'].'" class="btn btn-sm btn-outline-cyan py-0">ดูบิล</a></td>
                      </tr>';
        }
        if($q->num_rows == 0) $html .= '<tr><td colspan="5" class="text-center opacity-50 py-4">ไม่มีประวัติการสั่งซื้อ</td></tr>';
        echo $html . '</tbody></table>'; exit();
    }
}

// --- [Logic จัดการสินค้า]: อัปเกรดเพื่อรองรับรูปภาพรายรุ่นย่อย ---
if (isset($_POST['save_product'])) {
    $name = $conn->real_escape_string($_POST['name']); 
    $cat_id = $_POST['category_id']; 
    $desc = $conn->real_escape_string($_POST['description']);
    $is_variant = $_POST['is_variant']; 
    $p_id = !empty($_POST['product_id']) ? intval($_POST['product_id']) : null;

    $img_sql = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $img_name = time() . "_" . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], "images/" . $img_name)) {
            $img_sql = ", image='$img_name'";
        }
    }

    if ($p_id) {
        $main_price = ($is_variant == 'no') ? $_POST['price'] : 0;
        $main_stock = ($is_variant == 'no') ? intval($_POST['stock']) : 0;
        $conn->query("UPDATE products SET name='$name', price='$main_price', stock='$main_stock', category_id='$cat_id', description='$desc' $img_sql WHERE id=$p_id");
    } else {
        $main_price = ($is_variant == 'no') ? $_POST['price'] : 0;
        $main_stock = ($is_variant == 'no') ? intval($_POST['stock']) : 0;
        $final_img = (isset($img_name)) ? $img_name : "default.png";
        $conn->query("INSERT INTO products (name, price, stock, category_id, description, image) VALUES ('$name', '$main_price', '$main_stock', '$cat_id', '$desc', '$final_img')");
        $new_p_id = $conn->insert_id;

        if($is_variant == 'yes' && isset($_POST['v_names'])) {
            foreach($_POST['v_names'] as $i => $vname) {
                $vprice = $_POST['v_prices'][$i]; 
                $vstock = $_POST['v_stocks'][$i];
                $vimg_name = "";
                // --- จัดการรูปภาพของรุ่นย่อย ---
                if (isset($_FILES['v_images']['name'][$i]) && $_FILES['v_images']['error'][$i] == 0) {
                    $vimg_name = "v_" . time() . "_" . basename($_FILES['v_images']['name'][$i]);
                    move_uploaded_file($_FILES['v_images']['tmp_name'][$i], "images/" . $vimg_name);
                }
                $conn->query("INSERT INTO product_variants (product_id, variant_name, price, stock, variant_image) VALUES ($new_p_id, '".$conn->real_escape_string($vname)."', '$vprice', $vstock, '$vimg_name')");
            }
        }
    }
    header("Location: admin_dashboard.php?tab=products&success=1"); exit();
}

// --- Logic จัดการหมวดหมู่ ---
if (isset($_POST['save_category'])) {
    $name = $conn->real_escape_string($_POST['cat_name']);
    $slug = strtolower(str_replace(' ', '-', $name));
    if (!empty($_POST['cat_id'])) {
        $conn->query("UPDATE categories SET name='$name', slug='$slug' WHERE id=".intval($_POST['cat_id']));
    } else {
        $conn->query("INSERT INTO categories (name, slug) VALUES ('$name', '$slug')");
    }
    header("Location: admin_dashboard.php?tab=categories&success=1"); exit();
}

// --- Logic จัดการลูกค้า ---
if (isset($_POST['save_user'])) {
    $uname = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $fname = $conn->real_escape_string($_POST['fullname']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $addr = $conn->real_escape_string($_POST['address']);
    if (!empty($_POST['user_id'])) {
        $u_id = intval($_POST['user_id']);
        $conn->query("UPDATE users SET username='$uname', email='$email', fullname='$fname', phone='$phone', address='$addr' WHERE id=$u_id");
    } else {
        $pass = password_hash("123456", PASSWORD_DEFAULT);
        $conn->query("INSERT INTO users (username, email, fullname, phone, address, password, role) VALUES ('$uname', '$email', '$fname', '$phone', '$addr', '$pass', 'user')");
    }
    header("Location: admin_dashboard.php?tab=customers&success=1"); exit();
}

// --- Logic การลบข้อมูล ---
if (isset($_GET['del_id']) && isset($_GET['type'])) {
    $id = intval($_GET['del_id']);
    $type = $_GET['type'];
    if ($type == 'product') $conn->query("DELETE FROM products WHERE id=$id");
    if ($type == 'category') $conn->query("DELETE FROM categories WHERE id=$id");
    if ($type == 'user') $conn->query("DELETE FROM users WHERE id=$id");
    header("Location: admin_dashboard.php?tab=".$_GET['tab']."&deleted=1"); exit();
}

$products = $conn->query("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
$categories_list = $conn->query("SELECT * FROM categories ORDER BY id DESC");
$users_list = $conn->query("SELECT * FROM users WHERE role='user' ORDER BY id DESC"); 
$orders_list = $conn->query("SELECT * FROM orders ORDER BY id DESC"); 
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
        body { background: #0c001c; color: #ffffff !important; font-family: 'Segoe UI', sans-serif; }
        .glass-panel { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(15px); border: 1px solid rgba(187, 134, 252, 0.2); border-radius: 25px; padding: 30px; }
        .nav-pills .nav-link { color: #ffffff; border-radius: 12px; margin: 0 5px; transition: 0.3s; }
        .nav-pills .nav-link.active { background: #bb86fc !important; color: #120018 !important; box-shadow: 0 0 15px #bb86fc; font-weight: bold; }
        .text-neon-pink { color: #f107a3 !important; text-shadow: 0 0 10px rgba(241, 7, 163, 0.6); }
        /* ปรับสีตัวหนังสือให้ชัดเจน */
        .table { --bs-table-bg: transparent; color: #ffffff !important; }
        .table thead th { color: #00f2fe !important; border-bottom: 2px solid rgba(0, 242, 254, 0.3); font-weight: bold; text-transform: uppercase; }
        .table tbody td { color: #ffffff !important; vertical-align: middle; border-color: rgba(255,255,255,0.1); }
        .btn-neon-pink { background: linear-gradient(135deg, #f107a3, #bb86fc); border: none; color: #fff; }
        .btn-neon-pink:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(241, 7, 163, 0.5); color: #fff; }
        .modal-content { background: #1a0028 !important; border: 1px solid #bb86fc; border-radius: 20px; color: #ffffff !important; }
        .form-control, .form-select { background: rgba(255, 255, 255, 0.08) !important; border: 1px solid rgba(187, 134, 252, 0.4) !important; color: #ffffff !important; }
        .product-img { width: 45px; height: 45px; object-fit: cover; border-radius: 8px; border: 1px solid rgba(0, 242, 254, 0.3); }
        .stock-input { width: 85px; text-align: center; border-radius: 8px; font-weight: bold; color: #00f2fe !important; background: rgba(0,0,0,0.3) !important; }
    </style>
</head>
<body>

<div class="container-fluid py-5 px-4">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h2 class="fw-bold text-neon-pink"><i class="bi bi-shield-lock-fill me-2"></i> ADMIN DASHBOARD</h2>
        <a href="logout.php" class="btn btn-danger rounded-pill px-4 text-decoration-none shadow">ออกจากระบบ</a>
    </div>

    <ul class="nav nav-pills mb-5 justify-content-center" id="adminTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#products">สินค้า & สต็อก</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#categories">จัดการประเภท</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#customers">จัดการลูกค้า</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#orders">จัดการออเดอร์</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="products">
            <div class="glass-panel shadow-lg">
                <div class="d-flex justify-content-between mb-4 align-items-center"><h4>รายการสินค้า</h4><button class="btn btn-neon-pink rounded-pill px-4 shadow" onclick="openAddProduct()">+ เพิ่มสินค้า</button></div>
                <table class="table table-hover datatable-js w-100">
                    <thead><tr><th>รูป</th><th>ชื่อสินค้า</th><th>ราคา</th><th>สต็อก</th><th>จัดการ</th></tr></thead>
                    <tbody>
                        <?php while($p = $products->fetch_assoc()): 
                            $v_q = $conn->query("SELECT * FROM product_variants WHERE product_id=".$p['id']);
                            $has_variants = ($v_q->num_rows > 0);
                        ?>
                        <tr class="align-middle">
                            <td><img src="images/<?= $p['image'] ?>" class="product-img shadow-sm" onerror="this.src='images/default.png'"></td>
                            <td class="fw-bold"><?= htmlspecialchars($p['name']) ?></td>
                            <td class="text-info fw-bold">฿<?= number_format($p['price']) ?></td>
                            <td>
                                <?php if (!$has_variants): ?>
                                <input type="number" class="form-control form-control-sm stock-input mx-auto shadow-sm" value="<?= $p['stock'] ?>" onchange="manualUpdateStockDirect(<?= $p['id'] ?>, this.value)">
                                <?php else: ?>
                                <?php while($v = $v_q->fetch_assoc()): ?>
                                <div class="mb-1 small d-flex justify-content-between align-items-center border-bottom border-secondary pb-1">
                                    <span class="text-white opacity-75"><?= $v['variant_name'] ?>:</span>
                                    <input type="number" class="form-control form-control-sm stock-input shadow-sm" value="<?= $v['stock'] ?>" onchange="manualUpdateStockVariant(<?= $v['id'] ?>, this.value)">
                                </div>
                                <?php endwhile; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group shadow-sm">
                                    <button class="btn btn-sm btn-outline-warning" onclick='openEditProduct(<?= json_encode($p) ?>)'><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmAction('product', <?= $p['id'] ?>, 'products')"><i class="bi bi-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="categories">
            <div class="glass-panel shadow-lg">
                <div class="d-flex justify-content-between mb-4 align-items-center"><h4>จัดการประเภท</h4><button class="btn btn-neon-pink btn-sm rounded-pill shadow" onclick="openCatModal()">+ เพิ่มประเภท</button></div>
                <table class="table table-hover datatable-js w-100">
                    <thead><tr><th>ID</th><th>ชื่อประเภท</th><th>จัดการ</th></tr></thead>
                    <tbody><?php while($c = $categories_list->fetch_assoc()): ?><tr><td>#<?= $c['id'] ?></td><td class="fw-bold"><?= $c['name'] ?></td><td><button class="btn btn-sm btn-outline-warning" onclick='openEditCat(<?= json_encode($c) ?>)'><i class="bi bi-pencil"></i></button> <button class="btn btn-sm btn-outline-danger" onclick="confirmAction('category', <?= $c['id'] ?>, 'categories')"><i class="bi bi-trash"></i></button></td></tr><?php endwhile; ?></tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="customers">
            <div class="glass-panel shadow-lg">
                <div class="d-flex justify-content-between mb-4"><h4>จัดการลูกค้า</h4><button class="btn btn-neon-pink rounded-pill px-4 shadow" onclick="openAddUser()">+ เพิ่มลูกค้า</button></div>
                <table class="table table-hover datatable-js w-100">
                    <thead><tr><th>Username</th><th>ชื่อ-นามสกุล</th><th>เบอร์โทร</th><th>จัดการ</th></tr></thead>
                    <tbody>
                        <?php while($u = $users_list->fetch_assoc()): ?>
                        <tr>
                            <td class="fw-bold"><?= $u['username'] ?><br><small class="text-info"><?= $u['email'] ?></small></td>
                            <td><?= $u['fullname'] ?: '-' ?></td>
                            <td><?= $u['phone'] ?></td>
                            <td>
                                <div class="btn-group shadow-sm">
                                    <button class="btn btn-sm btn-outline-info" onclick="viewUserHistory(<?= $u['id'] ?>, '<?= $u['username'] ?>')" title="ดูประวัติการสั่งซื้อ"><i class="bi bi-clock-history"></i></button>
                                    <button class="btn btn-sm btn-outline-warning" onclick='openEditUser(<?= json_encode($u) ?>)'><i class="bi bi-pencil"></i></button> 
                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmAction('user', <?= $u['id'] ?>, 'customers')"><i class="bi bi-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="orders">
            <div class="glass-panel shadow-lg">
                <h4 class="mb-4">รายการออเดอร์ล่าสุด</h4>
                <table class="table table-hover datatable-js w-100">
                    <thead><tr><th>วันที่</th><th>บิล ID</th><th>ลูกค้า</th><th>ยอดรวม</th><th>สถานะ</th><th>จัดการ</th></tr></thead>
                    <tbody><?php while($o = $orders_list->fetch_assoc()): ?><tr><td><?= date('d/m/y H:i', strtotime($o['created_at'])) ?></td><td class="fw-bold text-info">#<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?></td><td><?= $o['fullname'] ?></td><td class="text-warning fw-bold">฿<?= number_format($o['total_price']) ?></td><td><span class="badge bg-secondary"><?= strtoupper($o['status']) ?></span></td><td><a href="admin_order_view.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-info rounded-pill px-3 shadow-sm"><i class="bi bi-receipt"></i> จัดการบิล</a></td></tr><?php endwhile; ?></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><form class="modal-content shadow" method="POST" enctype="multipart/form-data">
        <div class="modal-header border-secondary"><h5>จัดการสินค้า</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="hidden" name="product_id" id="p_id">
            <div class="row g-3">
                <div class="col-md-6"><label>ชื่อสินค้า</label><input type="text" name="name" id="p_name" class="form-control" required></div>
                <div class="col-md-3"><label>ประเภท</label><select name="category_id" id="p_cat" class="form-select"><?php $categories_list->data_seek(0); while($c=$categories_list->fetch_assoc()): ?><option value="<?=$c['id']?>"><?=$c['name']?></option><?php endwhile; ?></select></div>
                <div class="col-md-3"><label>มีรุ่นย่อย?</label><select name="is_variant" id="is_variant_select" class="form-select" onchange="toggleVariantFields(this.value)"><option value="no">ไม่มี</option><option value="yes">มี</option></select></div>
                <div id="no_variant_inputs" class="row g-3 px-0 mx-0"><div class="col-md-6"><label>ราคา</label><input type="number" name="price" id="p_price" class="form-control"></div><div class="col-md-6"><label>สต็อก</label><input type="number" name="stock" id="p_stock" class="form-control"></div></div>
                <div class="col-12"><label>รายละเอียด</label><textarea name="description" id="p_desc" class="form-control" rows="2"></textarea></div>
                <div class="col-12"><label>รูปภาพหลัก</label><input type="file" name="image" class="form-control"></div>
                <div id="variant_fields" style="display:none;" class="col-12 mt-3">
                    <h6 class="text-info border-bottom border-secondary pb-2">รายการรุ่นย่อยและรูปภาพ</h6>
                    <div id="variant_container"></div>
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="addVariantRow()">+ เพิ่มรุ่นย่อย</button>
                </div>
            </div>
        </div>
        <div class="modal-footer border-secondary"><button type="submit" name="save_product" class="btn btn-neon-pink w-100 shadow">บันทึกสินค้า</button></div>
    </form></div>
</div>

<div class="modal fade" id="catModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><form class="modal-content shadow" method="POST">
        <div class="modal-header border-secondary"><h5 id="cat_title">จัดการประเภท</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><input type="hidden" name="cat_id" id="cat_id"><div class="mb-3"><label>ชื่อประเภทสินค้า</label><input type="text" name="cat_name" id="cat_name" class="form-control" required></div></div>
        <div class="modal-footer border-secondary"><button type="submit" name="save_category" class="btn btn-neon-pink w-100 shadow">บันทึกข้อมูล</button></div>
    </form></div>
</div>

<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><form class="modal-content shadow" method="POST">
        <div class="modal-header border-secondary"><h5>ข้อมูลลูกค้า</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="hidden" name="user_id" id="u_id">
            <div class="mb-3"><label>Username</label><input type="text" name="username" id="u_uname" class="form-control" required></div>
            <div class="mb-2"><label>Email</label><input type="email" name="email" id="u_email" class="form-control"></div>
            <div class="mb-2"><label>ชื่อ-นามสกุล</label><input type="text" name="fullname" id="u_fname" class="form-control"></div>
            <div class="mb-2"><label>เบอร์โทร</label><input type="text" name="phone" id="u_phone" class="form-control" required></div>
            <div class="mb-2"><label>ที่อยู่</label><textarea name="address" id="u_addr" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer border-secondary"><button type="submit" name="save_user" class="btn btn-neon-pink w-100 shadow">บันทึก</button></div>
    </form></div>
</div>

<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header border-secondary"><h5>ประวัติ: <span id="h_uname" class="text-info"></span></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="h_content"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content text-center py-4 shadow"><div class="modal-body"><i class="bi bi-exclamation-triangle text-warning display-4 mb-3"></i><h4 class="text-white">ยืนยันลบ?</h4><div class="d-flex gap-2 justify-content-center mt-4"><button type="button" class="btn btn-outline-light px-4 rounded-pill" data-bs-dismiss="modal">ยกเลิก</button><a href="#" id="confirmDeleteBtn" class="btn btn-danger px-4 rounded-pill text-decoration-none shadow">ลบทันที</a></div></div></div></div></div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() { $('.datatable-js').DataTable({ "language": { "search": "ค้นหา:", "lengthMenu": "แสดง _MENU_ รายการ" } }); });
    function toggleVariantFields(val) { $('#no_variant_inputs').toggle(val === 'no'); $('#variant_fields').toggle(val === 'yes'); }
    
    // เพิ่มช่องอัปโหลดรูปภาพในแถวรุ่นย่อย
    function addVariantRow() { $('#variant_container').append(`<div class="variant-card mb-2 p-2 border border-secondary rounded shadow-sm"><div class="row g-2 align-items-end"><div class="col-md-3"><label class="small text-white">รุ่น</label><input type="text" name="v_names[]" class="form-control form-control-sm" required></div><div class="col-md-2"><label class="small text-white">ราคา</label><input type="number" name="v_prices[]" class="form-control form-control-sm" required></div><div class="col-md-2"><label class="small text-white">สต็อก</label><input type="number" name="v_stocks[]" class="form-control form-control-sm" value="0"></div><div class="col-md-4"><label class="small text-white">รูปภาพ</label><input type="file" name="v_images[]" class="form-control form-control-sm" accept="image/*"></div><div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger shadow-sm" onclick="this.closest('.variant-card').remove()"><i class="bi bi-trash"></i></button></div></div></div>`); }
    
    function manualUpdateStockDirect(pid, new_val) { $.get('admin_dashboard.php', {ajax_action: 'update_stock_direct', pid: pid, val: new_val}); }
    function manualUpdateStockVariant(vid, new_val) { $.get('admin_dashboard.php', {ajax_action: 'update_stock_value', vid: vid, val: new_val}); }
    
    // ฟังก์ชันดูประวัติลูกค้า
    function viewUserHistory(uid, uname) { 
        $('#h_uname').text(uname); 
        $('#historyModal').modal('show'); 
        $('#h_content').html('<div class="text-center py-5"><div class="spinner-border text-info"></div></div>');
        $.get('admin_dashboard.php', {ajax_action: 'get_user_history', uid: uid}, function(data) { $('#h_content').html(data); }); 
    }

    function openAddProduct() { $('#productModal').find('form')[0].reset(); $('#p_id').val(''); $('#variant_container').empty(); toggleVariantFields('no'); $('#productModal').modal('show'); }
    function openEditProduct(p) { $('#p_id').val(p.id); $('#p_name').val(p.name); $('#p_price').val(p.price); $('#p_stock').val(p.stock); $('#p_cat').val(p.category_id); $('#p_desc').val(p.description); toggleVariantFields('no'); $('#productModal').modal('show'); }
    function openCatModal() { $('#cat_id').val(''); $('#cat_name').val(''); $('#cat_title').text('เพิ่มประเภทสินค้า'); $('#catModal').modal('show'); }
    function openEditCat(c) { $('#cat_id').val(c.id); $('#cat_name').val(c.name); $('#cat_title').text('แก้ไขประเภทสินค้า'); $('#catModal').modal('show'); }
    function openAddUser() { $('#u_id').val(''); $('#userModal').modal('show'); }
    function openEditUser(u) { $('#u_id').val(u.id); $('#u_uname').val(u.username); $('#u_email').val(u.email); $('#u_fname').val(u.fullname); $('#u_phone').val(u.phone); $('#u_addr').val(u.address); $('#userModal').modal('show'); }
    function confirmAction(type, id, tab) { $('#confirmDeleteBtn').attr('href', `?del_id=${id}&type=${type}&tab=${tab}`); $('#confirmDeleteModal').modal('show'); }
</script>
</body>
</html>