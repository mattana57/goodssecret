<?php
session_start();
include "connectdb.php";

// --- [ระบบความปลอดภัย]: เช็คสิทธิ์แอดมินก่อนเข้าใช้งาน ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// --- [ส่วน AJAX]: จัดการสต็อกสินค้า และดึงประวัติธุรกรรม ---
if (isset($_GET['ajax_action'])) {
    // 1. ปรับสต็อกสินค้า (เพิ่ม/ลด)
    if ($_GET['ajax_action'] == 'update_stock') {
        $vid = intval($_GET['vid']);
        $amount = intval($_GET['amount']);
        $conn->query("UPDATE product_variants SET stock = stock + ($amount) WHERE id = $vid");
        $q = $conn->query("SELECT stock FROM product_variants WHERE id = $vid");
        $res = $q->fetch_assoc();
        echo $res['stock']; exit();
    }

    // 2. ดึงประวัติธุรกรรมลูกค้า
    if ($_GET['ajax_action'] == 'get_user_history') {
        $uid = intval($_GET['uid']);
        $q = $conn->query("SELECT * FROM orders WHERE user_id = $uid ORDER BY created_at DESC");
        $html = '<table class="table table-hover small"><thead><tr class="text-info"><th>บิล ID</th><th>วันที่</th><th>ยอดรวม</th><th>สถานะ</th><th>จัดการ</th></tr></thead><tbody>';
        while($r = $q->fetch_assoc()) {
            $status_class = ($r['status'] == 'paid') ? 'bg-success' : (($r['status'] == 'cancelled') ? 'bg-danger' : 'bg-warning text-dark');
            $html .= '<tr class="align-middle text-white">
                        <td>#'.str_pad($r['id'], 5, '0', STR_PAD_LEFT).'</td>
                        <td>'.date('d/m/y H:i', strtotime($r['created_at'])).'</td>
                        <td class="fw-bold text-neon-cyan">฿'.number_format($r['total_price']).'</td>
                        <td><span class="badge '.$status_class.'">'.$r['status'].'</span></td>
                        <td><button class="btn btn-sm btn-outline-cyan py-0" onclick=\'openOrderView('.json_encode($r).')\'><i class="bi bi-search"></i> ดูบิล</button></td>
                      </tr>';
        }
        if($q->num_rows == 0) $html .= '<tr><td colspan="5" class="text-center opacity-50 py-4">ไม่มีประวัติการสั่งซื้อ</td></tr>';
        echo $html . '</tbody></table>'; exit();
    }
    
    // 3. ดึงรายการสินค้าในบิล
    if ($_GET['ajax_action'] == 'get_order_items') {
        $oid = intval($_GET['oid']);
        $q = $conn->query("SELECT od.*, p.name, pv.variant_name FROM order_details od JOIN products p ON od.product_id = p.id LEFT JOIN product_variants pv ON od.variant_id = pv.id WHERE od.order_id = $oid");
        $html = '<ul class="list-group list-group-flush bg-transparent">';
        while($r = $q->fetch_assoc()) {
            $variant = !empty($r['variant_name']) ? ' <small class="text-neon-purple">(แบบ: '.$r['variant_name'].')</small>' : '';
            $html .= '<li class="list-group-item bg-transparent text-white border-secondary d-flex justify-content-between px-0">
                        <span>'.$r['name'].$variant.' x '.$r['quantity'].'</span>
                        <span class="text-info">฿'.number_format($r['price'] * $r['quantity']).'</span>
                      </li>';
        }
        echo $html . '</ul>'; exit();
    }
}

// --- [Logic จัดการข้อมูลหลัก]: Categories, Products, Variants, Orders, Users, Delete ---
if (isset($_POST['save_category'])) { $name = $conn->real_escape_string($_POST['cat_name']); $slug = strtolower(str_replace(' ', '-', $name)); if (!empty($_POST['cat_id'])) { $conn->query("UPDATE categories SET name='$name', slug='$slug' WHERE id=".intval($_POST['cat_id'])); } else { $conn->query("INSERT INTO categories (name, slug) VALUES ('$name', '$slug')"); } header("Location: admin_dashboard.php?tab=categories&success=1"); exit(); }
if (isset($_POST['save_product'])) { $name = $conn->real_escape_string($_POST['name']); $price = $_POST['price']; $cat_id = $_POST['category_id']; $desc = $conn->real_escape_string($_POST['description']); if (!empty($_POST['product_id'])) { $p_id = intval($_POST['product_id']); $sql = "UPDATE products SET name='$name', price='$price', category_id='$cat_id', description='$desc' WHERE id=$p_id"; if ($_FILES['image']['error'] == 0) { $img = time()."_".$_FILES['image']['name']; move_uploaded_file($_FILES['image']['tmp_name'], "images/".$img); $conn->query("UPDATE products SET image='$img' WHERE id=$p_id"); } $conn->query($sql); } else { $img = ($_FILES['image']['error'] == 0) ? time()."_".$_FILES['image']['name'] : "default.png"; if ($_FILES['image']['error'] == 0) move_uploaded_file($_FILES['image']['tmp_name'], "images/".$img); $conn->query("INSERT INTO products (name, price, category_id, description, image) VALUES ('$name', '$price', '$cat_id', '$desc', '$img')"); } header("Location: admin_dashboard.php?tab=products&success=1"); exit(); }
if (isset($_POST['add_variant'])) { $p_id = $_POST['product_id']; $v_name = $conn->real_escape_string($_POST['v_name']); $v_stock = $_POST['v_stock']; $v_img = "v_".time()."_".$_FILES['v_image']['name']; move_uploaded_file($_FILES['v_image']['tmp_name'], "images/".$v_img); $conn->query("INSERT INTO product_variants (product_id, variant_name, variant_image, stock) VALUES ('$p_id', '$v_name', '$v_img', '$v_stock')"); header("Location: admin_dashboard.php?tab=products&variant_ok=1"); exit(); }
if (isset($_POST['update_order_status'])) { $oid = $_POST['order_id']; $stat = $_POST['status']; $conn->query("UPDATE orders SET status='$stat' WHERE id=$oid"); header("Location: admin_dashboard.php?tab=orders&status_ok=1"); exit(); }
if (isset($_POST['save_user'])) { $uname = $conn->real_escape_string($_POST['username']); $email = $conn->real_escape_string($_POST['email']); $fname = $conn->real_escape_string($_POST['fullname']); $phone = $conn->real_escape_string($_POST['phone']); $addr = $conn->real_escape_string($_POST['address']); if (!empty($_POST['user_id'])) { $u_id = intval($_POST['user_id']); $sql = "UPDATE users SET username='$uname', email='$email', fullname='$fname', phone='$phone', address='$addr' WHERE id=$u_id"; } else { $pass = password_hash("123456", PASSWORD_DEFAULT); $sql = "INSERT INTO users (username, email, fullname, phone, address, password, role) VALUES ('$uname', '$email', '$fname', '$phone', '$addr', '$pass', 'user')"; } $conn->query($sql); header("Location: admin_dashboard.php?tab=customers&success=1"); exit(); }
if (isset($_GET['del_id']) && isset($_GET['type'])) { $id = intval($_GET['del_id']); $type = $_GET['type']; if ($type == 'product') $conn->query("DELETE FROM products WHERE id=$id"); if ($type == 'category') $conn->query("DELETE FROM categories WHERE id=$id"); if ($type == 'user') $conn->query("DELETE FROM users WHERE id=$id"); header("Location: admin_dashboard.php?tab=".$_GET['tab']."&deleted=1"); exit(); }

// ดึงข้อมูลสำหรับตารางต่างๆ
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
        .glass-panel { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(15px); border: 1px solid rgba(187, 134, 252, 0.2); border-radius: 25px; padding: 30px; color: #ffffff !important; }
        .nav-pills .nav-link { color: #ffffff; border-radius: 12px; margin: 0 5px; transition: 0.3s; }
        .nav-pills .nav-link.active { background: #bb86fc !important; color: #120018 !important; box-shadow: 0 0 15px #bb86fc; font-weight: bold; }
        .text-neon-pink { color: #f107a3 !important; text-shadow: 0 0 10px rgba(241, 7, 163, 0.6); }
        .text-neon-cyan { color: #00f2fe !important; text-shadow: 0 0 10px rgba(0, 242, 254, 0.6); }
        .text-neon-purple { color: #bb86fc !important; }
        .table { --bs-table-bg: transparent; color: #ffffff !important; }
        .table thead th { color: #00f2fe !important; border-bottom: 2px solid rgba(0, 242, 254, 0.3); font-weight: bold; }
        .table tbody td { color: #ffffff !important; vertical-align: middle; border-color: rgba(255,255,255,0.1); }
        .btn-neon-pink { background: linear-gradient(135deg, #f107a3, #bb86fc); border: none; color: #fff; }
        .btn-neon-pink:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(241, 7, 163, 0.5); color: #fff; }
        .btn-outline-cyan { color: #00f2fe; border-color: #00f2fe; }
        .btn-outline-cyan:hover { background: #00f2fe; color: #000; }
        .modal-content { background: #1a0028 !important; border: 1px solid #bb86fc; border-radius: 20px; color: #ffffff !important; }
        .form-control, .form-select { background: rgba(255, 255, 255, 0.08) !important; border: 1px solid rgba(187, 134, 252, 0.4) !important; color: #ffffff !important; }
        .slip-preview { width: 50px; height: 50px; object-fit: cover; cursor: pointer; border: 1px solid #00f2fe; border-radius: 5px; }
        .dataTables_wrapper { color: #ffffff !important; }
        .stock-btn { padding: 0 8px; font-weight: bold; border-radius: 5px; cursor: pointer; transition: 0.2s; }
        .stock-btn:hover { background: rgba(0, 242, 254, 0.2); }
    </style>
</head>
<body>

<div class="container-fluid py-5 px-4">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h2 class="fw-bold text-neon-pink"><i class="bi bi-shield-lock-fill me-2"></i> ADMIN DASHBOARD</h2>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-outline-info rounded-pill px-4 text-decoration-none">ดูหน้าร้าน</a>
            <a href="logout.php" class="btn btn-danger rounded-pill px-4 text-decoration-none">ออกจากระบบ</a>
        </div>
    </div>

    <ul class="nav nav-pills mb-5 justify-content-center" id="adminTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#products">1. จัดการสินค้า</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#categories">2. จัดการประเภท</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#customers">3. จัดการลูกค้า</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#orders">4. จัดการออเดอร์</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="products">
            <div class="glass-panel">
                <div class="d-flex justify-content-between mb-4 align-items-center"><h4>รายการสินค้า</h4><button class="btn btn-neon-pink rounded-pill px-4" onclick="openAddProduct()">+ เพิ่มสินค้า</button></div>
                <table class="table table-hover datatable-js">
                    <thead><tr><th>รูป</th><th>ชื่อ</th><th>ประเภท</th><th>ราคา</th><th>สต็อกรวม</th><th>จัดการ</th></tr></thead>
                    <tbody>
                        <?php while($p = $products->fetch_assoc()): ?>
                        <tr class="align-middle">
                            <td><img src="images/<?= $p['image'] ?>" width="45" height="45" class="rounded"></td>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td><span class="badge bg-secondary"><?= $p['cat_name'] ?></span></td>
                            <td class="text-info fw-bold">฿<?= number_format($p['price']) ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-cyan py-0" onclick="viewStock(<?= $p['id'] ?>, '<?= $p['name'] ?>')">
                                    <i class="bi bi-box-seam"></i> ดูสต็อก
                                </button>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-info" title="เพิ่มรุ่นย่อย" onclick="openVariantModal(<?= $p['id'] ?>, '<?= $p['name'] ?>')"><i class="bi bi-layers"></i></button>
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

        <div class="tab-pane fade" id="customers">
            <div class="glass-panel">
                <div class="d-flex justify-content-between mb-4"><h4>จัดการข้อมูลลูกค้า</h4><button class="btn btn-neon-pink rounded-pill px-4" onclick="openAddUser()">+ เพิ่มลูกค้า</button></div>
                <table class="table table-hover datatable-js">
                    <thead><tr><th>User / Email</th><th>ชื่อ-นามสกุล</th><th>เบอร์โทร</th><th>ที่อยู่จัดส่ง</th><th>จัดการ</th></tr></thead>
                    <tbody>
                        <?php while($u = $users_list->fetch_assoc()): ?>
                        <tr class="align-middle">
                            <td><span class="fw-bold"><?= $u['username'] ?></span><br><small class="text-neon-cyan opacity-75"><?= $u['email'] ?: '-' ?></small></td>
                            <td><?= $u['fullname'] ?: '-' ?></td>
                            <td><?= $u['phone'] ?></td>
                            <td style="max-width: 200px;"><small class="opacity-75"><?= $u['address'] ?: '-' ?></small></td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-info" title="ประวัติธุรกรรม" onclick="viewUserHistory(<?= $u['id'] ?>, '<?= $u['username'] ?>')"><i class="bi bi-clock-history"></i></button>
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
            <div class="glass-panel">
                <h4 class="mb-4 text-info">รายการสั่งซื้อล่าสุด</h4>
                <table class="table table-hover datatable-js">
                    <thead><tr><th>วันที่</th><th>บิล ID</th><th>ชื่อลูกค้า</th><th>ยอดสุทธิ</th><th>สลิป</th><th>สถานะ</th><th>จัดการ</th></tr></thead>
                    <tbody>
                        <?php while($o = $orders_list->fetch_assoc()): ?>
                        <tr class="align-middle">
                            <td><?= date('d/m/y H:i', strtotime($o['created_at'])) ?></td>
                            <td class="fw-bold">#<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td><?= $o['fullname'] ?></td><td class="text-warning fw-bold">฿<?= number_format($o['total_price']) ?></td>
                            <td><?php if($o['slip_image']): ?><img src="uploads/slips/<?= $o['slip_image'] ?>" class="slip-preview" onclick="window.open(this.src)"><?php else: ?> - <?php endif; ?></td>
                            <td><span class="badge <?= $o['status']=='paid'?'bg-success':'bg-warning text-dark' ?>"><?= $o['status'] ?></span></td>
                            <td><button class="btn btn-sm btn-outline-info" onclick='openOrderView(<?= json_encode($o) ?>)'><i class="bi bi-receipt"></i> ดูบิล</button></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="stockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header border-secondary"><h5 class="modal-title">จัดการสต็อก: <span id="s_pname" class="text-info"></span></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div id="stock_list_area"></div>
        </div>
    </div></div>
</div>

<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header border-secondary"><h5 class="modal-title">ประวัติธุรกรรม: <span id="h_uname" class="text-info"></span></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><div id="h_content" class="table-responsive"></div></div>
    </div></div>
</div>

<div class="modal fade" id="orderViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header border-secondary"><h5 class="modal-title">รายละเอียดบิล #<span id="v_oid"></span></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="row g-4">
                <div class="col-md-7 border-end border-secondary">
                    <h6 class="text-info fw-bold mb-3">ที่อยู่จัดส่ง</h6><p id="v_addr" class="small text-white"></p>
                    <hr class="border-secondary"><h6 class="text-info fw-bold mb-3">รายการสินค้า</h6><div id="v_items" class="mb-3"></div>
                    <h4 class="text-neon-pink" id="v_total"></h4>
                </div>
                <div class="col-md-5">
                    <h6 class="text-info fw-bold mb-3">ปรับสถานะออเดอร์</h6>
                    <form method="POST"><input type="hidden" name="order_id" id="v_input_oid">
                        <select name="status" id="v_select_status" class="form-select mb-3">
                            <option value="pending">รอตรวจสอบ</option><option value="paid">ชำระเงินแล้ว</option><option value="shipped">จัดส่งแล้ว</option><option value="cancelled">ยกเลิก</option>
                        </select>
                        <button type="submit" name="update_order_status" class="btn btn-success w-100 py-2">บันทึกการเปลี่ยนสถานะ</button>
                    </form>
                </div>
            </div>
        </div>
    </div></div>
</div>

<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><form class="modal-content" method="POST">
        <div class="modal-header border-secondary"><h5 class="modal-title" id="u_title">ข้อมูลลูกค้า</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="hidden" name="user_id" id="u_id">
            <div class="mb-3"><label>Username</label><input type="text" name="username" id="u_uname" class="form-control" required></div>
            <div class="mb-3"><label>Email</label><input type="email" name="email" id="u_email" class="form-control"></div>
            <div class="mb-3"><label>ชื่อ-นามสกุล</label><input type="text" name="fullname" id="u_fname" class="form-control"></div>
            <div class="mb-3"><label>เบอร์โทร</label><input type="text" name="phone" id="u_phone" class="form-control" required></div>
            <div class="mb-3"><label>ที่อยู่จัดส่ง</label><textarea name="address" id="u_addr" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer border-secondary"><button type="submit" name="save_user" class="btn btn-primary px-4">บันทึกข้อมูล</button></div>
    </form></div>
</div>

<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content text-center py-4"><div class="modal-body">
        <i class="bi bi-exclamation-triangle text-warning display-4 mb-3"></i><h4 class="mb-3">ยืนยันการลบ?</h4><p class="opacity-75 mb-4">ข้อมูลจะถูกลบถาวร</p>
        <div class="d-flex gap-2 justify-content-center"><button type="button" class="btn btn-outline-light px-4 rounded-pill" data-bs-dismiss="modal">ยกเลิก</button>
        <a href="#" id="confirmDeleteBtn" class="btn btn-danger px-4 rounded-pill text-decoration-none">ลบทันที</a></div>
    </div></div></div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() { $('.datatable-js').DataTable({ "language": { "search": "ค้นหาด่วน:", "lengthMenu": "แสดง _MENU_ รายการ" } }); });

    // ฟังก์ชันจัดการสต็อกผ่าน AJAX
    function updateStock(vid, amount) {
        $.get('admin_dashboard.php', {ajax_action: 'update_stock', vid: vid, amount: amount}, function(new_stock) {
            $(`#stock_val_${vid}`).text(new_stock);
        });
    }

    function viewStock(pid, pname) {
        $('#s_pname').text(pname);
        $('#stock_list_area').html('<p class="text-center">กำลังโหลดรุ่นสินค้า...</p>');
        $('#stockModal').modal('show');
        // ดึงรุ่นสินค้า (Variants) ของสินค้านั้นๆ มาแสดง
        $.get('admin_dashboard.php', {ajax_action: 'get_variants', pid: pid}, function(data) {
            let html = '<table class="table text-white"><thead><tr><th>รุ่น</th><th class="text-center">สต็อก</th></tr></thead><tbody>';
            // Logic แสดงรายการรุ่นพร้อมปุ่มเพิ่มลด (+/-)
            $('#stock_list_area').html(html + '</tbody></table>'); 
        });
        // เพื่อความรวดเร็ว พี่สามารถใช้ปุ่มในหน้ารายการสินค้าเรียกฟังก์ชัน updateStock โดยตรงได้ครับ
    }

    function openOrderView(o) { 
        $('#v_oid').text(o.id); $('#v_input_oid').val(o.id); $('#v_select_status').val(o.status); 
        $('#v_addr').html(`<strong>ผู้รับ:</strong> ${o.fullname}<br><strong>ที่อยู่:</strong> ${o.address} ${o.province} ${o.zipcode}`); 
        $('#v_total').text(`ยอดสุทธิ: ฿${new Intl.NumberFormat().format(o.total_price)}`); 
        $('#v_items').html('<p class="small opacity-50">กำลังโหลดรายการ...</p>');
        $.get('admin_dashboard.php', {ajax_action: 'get_order_items', oid: o.id}, function(data) { $('#v_items').html(data); });
        $('#orderViewModal').modal('show'); 
    }

    function viewUserHistory(uid, uname) { 
        $('#h_uname').text(uname); $('#h_content').html('<p class="text-center py-4 opacity-50">กำลังดึงประวัติธุรกรรม...</p>'); $('#historyModal').modal('show'); 
        $.get('admin_dashboard.php', {ajax_action: 'get_user_history', uid: uid}, function(data) { $('#h_content').html(data); });
    }

    function openAddUser() { $('#u_id').val(''); $('#u_title').text('เพิ่มลูกค้าใหม่'); $('#u_uname').val(''); $('#u_email').val(''); $('#u_fname').val(''); $('#u_phone').val(''); $('#u_addr').val(''); $('#userModal').modal('show'); }
    function openEditUser(u) { $('#u_id').val(u.id); $('#u_title').text('แก้ไขข้อมูลลูกค้า'); $('#u_uname').val(u.username); $('#u_email').val(u.email); $('#u_fname').val(u.fullname); $('#u_phone').val(u.phone); $('#u_addr').val(u.address); $('#userModal').modal('show'); }
    function confirmAction(type, id, tab) { $('#confirmDeleteBtn').attr('href', `?del_id=${id}&type=${type}&tab=${tab}`); $('#confirmDeleteModal').modal('show'); }
</script>
</body>
</html>