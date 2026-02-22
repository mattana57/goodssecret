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
    // 1. อัปเดตสต็อกจากการพิมพ์ตัวเลข (Manual Input)
    if ($_GET['ajax_action'] == 'update_stock_value') {
        $vid = intval($_GET['vid']);
        $new_val = intval($_GET['val']);
        $conn->query("UPDATE product_variants SET stock = $new_val WHERE id = $vid");
        echo $new_val; exit();
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

// --- [Logic จัดการสินค้า]: รองรับการเพิ่มรุ่นย่อยพร้อมราคาและรูปแยกกัน ---
if (isset($_POST['save_product'])) {
    $name = $conn->real_escape_string($_POST['name']); 
    $cat_id = $_POST['category_id']; 
    $desc = $conn->real_escape_string($_POST['description']);
    $is_variant = $_POST['is_variant']; 

    // บันทึกข้อมูลสินค้าหลักลง products
    $main_price = ($is_variant == 'no') ? $_POST['price'] : 0; // ถ้ารุ่นย่อย ราคาหลักจะเป็น 0
    $img = ($_FILES['image']['error'] == 0) ? time()."_".$_FILES['image']['name'] : "default.png";
    if ($_FILES['image']['error'] == 0) move_uploaded_file($_FILES['image']['tmp_name'], "images/".$img);

    $conn->query("INSERT INTO products (name, price, category_id, description, image) VALUES ('$name', '$main_price', '$cat_id', '$desc', '$img')");
    $new_p_id = $conn->insert_id;

    if($is_variant == 'yes') {
        // กรณีมีรุ่นย่อย: บันทึกวนลูปตามจำนวนรุ่น
        foreach($_POST['v_names'] as $i => $vname) {
            $vprice = $_POST['v_prices'][$i];
            $vstock = $_POST['v_stocks'][$i];
            $vimg_name = NULL;
            if ($_FILES['v_images']['error'][$i] == 0) {
                $vimg_name = "v_".time()."_".$_FILES['v_images']['name'][$i];
                move_uploaded_file($_FILES['v_images']['tmp_name'][$i], "images/".$vimg_name);
            }
            $conn->query("INSERT INTO product_variants (product_id, variant_name, price, variant_image, stock) VALUES ($new_p_id, '".$conn->real_escape_string($vname)."', '$vprice', '$vimg_name', $vstock)");
        }
    } else {
        // กรณีไม่มีรุ่นย่อย: สร้าง Variant เงา 1 อันเพื่อให้พิมพ์สต็อกในหน้าตารางได้
        $stock = $_POST['stock'];
        $conn->query("INSERT INTO product_variants (product_id, variant_name, price, stock) VALUES ($new_p_id, 'ทั่วไป', '$main_price', $stock)");
    }
    header("Location: admin_dashboard.php?tab=products&success=1"); exit();
}

// Logic จัดการหมวดหมู่, ลูกค้า, ลบ (ห้ามตัด)
if (isset($_POST['save_category'])) { $name = $conn->real_escape_string($_POST['cat_name']); $slug = strtolower(str_replace(' ', '-', $name)); if (!empty($_POST['cat_id'])) { $conn->query("UPDATE categories SET name='$name', slug='$slug' WHERE id=".intval($_POST['cat_id'])); } else { $conn->query("INSERT INTO categories (name, slug) VALUES ('$name', '$slug')"); } header("Location: admin_dashboard.php?tab=categories&success=1"); exit(); }
if (isset($_POST['save_user'])) { $uname = $conn->real_escape_string($_POST['username']); $email = $conn->real_escape_string($_POST['email']); $fname = $conn->real_escape_string($_POST['fullname']); $phone = $conn->real_escape_string($_POST['phone']); $addr = $conn->real_escape_string($_POST['address']); if (!empty($_POST['user_id'])) { $u_id = intval($_POST['user_id']); $sql = "UPDATE users SET username='$uname', email='$email', fullname='$fname', phone='$phone', address='$addr' WHERE id=$u_id"; } else { $pass = password_hash("123456", PASSWORD_DEFAULT); $sql = "INSERT INTO users (username, email, fullname, phone, address, password, role) VALUES ('$uname', '$email', '$fname', '$phone', '$addr', '$pass', 'user')"; } $conn->query($sql); header("Location: admin_dashboard.php?tab=customers&success=1"); exit(); }
if (isset($_GET['del_id']) && isset($_GET['type'])) { $id = intval($_GET['del_id']); $type = $_GET['type']; if ($type == 'product') $conn->query("DELETE FROM products WHERE id=$id"); if ($type == 'category') $conn->query("DELETE FROM categories WHERE id=$id"); if ($type == 'user') $conn->query("DELETE FROM users WHERE id=$id"); header("Location: admin_dashboard.php?tab=".$_GET['tab']."&deleted=1"); exit(); }

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
        .table { --bs-table-bg: transparent; color: #ffffff !important; }
        .table thead th { color: #00f2fe !important; border-bottom: 2px solid rgba(0, 242, 254, 0.3); font-weight: bold; }
        .table tbody td { color: #ffffff !important; vertical-align: middle; border-color: rgba(255,255,255,0.1); }
        .btn-neon-pink { background: linear-gradient(135deg, #f107a3, #bb86fc); border: none; color: #fff; }
        .modal-content { background: #1a0028 !important; border: 1px solid #bb86fc; border-radius: 20px; color: #ffffff !important; }
        .form-control, .form-select { background: rgba(255, 255, 255, 0.08) !important; border: 1px solid rgba(187, 134, 252, 0.4) !important; color: #ffffff !important; }
        .stock-input { width: 75px; text-align: center; border-radius: 8px; font-weight: bold; color: #00f2fe !important; }
        .variant-card { background: rgba(255,255,255,0.05); border: 1px solid #bb86fc; border-radius: 15px; padding: 15px; margin-bottom: 10px; }
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
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#products">1. จัดการสินค้า/สต็อก</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#categories">2. จัดการประเภท</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#customers">3. จัดการลูกค้า</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#orders">4. จัดการออเดอร์</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="products">
            <div class="glass-panel">
                <div class="d-flex justify-content-between mb-4 align-items-center"><h4>รายการสินค้า</h4><button class="btn btn-neon-pink rounded-pill px-4" onclick="openAddProduct()">+ เพิ่มสินค้า</button></div>
                <table class="table table-hover datatable-js">
                    <thead><tr><th>รูป</th><th>ชื่อสินค้า</th><th>ราคาหลัก</th><th>สต็อกแยกตามรุ่น (พิมพ์เลขเพื่อแก้)</th><th>จัดการ</th></tr></thead>
                    <tbody>
                        <?php while($p = $products->fetch_assoc()): ?>
                        <tr class="align-middle">
                            <td><img src="images/<?= $p['image'] ?>" width="45" height="45" class="rounded shadow-sm"></td>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td class="text-info fw-bold">฿<?= number_format($p['price']) ?></td>
                            <td>
                                <?php $v_q = $conn->query("SELECT * FROM product_variants WHERE product_id=".$p['id']);
                                while($v = $v_q->fetch_assoc()): ?>
                                <div class="mb-1 d-flex justify-content-between align-items-center border-bottom border-secondary pb-1">
                                    <small><?= $v['variant_name'] ?> (฿<?= number_format($v['price']) ?>):</small>
                                    <input type="number" class="form-control form-control-sm stock-input" value="<?= $v['stock'] ?>" onchange="manualUpdateStock(<?= $v['id'] ?>, this.value)">
                                </div>
                                <?php endwhile; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-warning" onclick='openEditProduct(<?= json_encode($p) ?>)'><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="confirmAction('product', <?= $p['id'] ?>, 'products')"><i class="bi bi-trash"></i></button>
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
                            <td><?= $u['fullname'] ?: '-' ?></td><td><?= $u['phone'] ?></td><td style="max-width: 250px;"><small class="opacity-75"><?= $u['address'] ?: '-' ?></small></td>
                            <td><div class="btn-group"><button class="btn btn-sm btn-outline-info" title="ประวัติธุรกรรม" onclick="viewUserHistory(<?= $u['id'] ?>, '<?= $u['username'] ?>')"><i class="bi bi-clock-history"></i></button> <button class="btn btn-sm btn-outline-warning" onclick='openEditUser(<?= json_encode($u) ?>)'><i class="bi bi-pencil"></i></button> <button class="btn btn-sm btn-outline-danger" onclick="confirmAction('user', <?= $u['id'] ?>, 'customers')"><i class="bi bi-trash"></i></button></div></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        </div>
</div>

<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><form class="modal-content" method="POST" enctype="multipart/form-data">
        <div class="modal-header border-secondary"><h5 class="modal-title" id="p_title">จัดการสินค้าใหม่</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="hidden" name="product_id" id="p_id">
            <div class="row g-3">
                <div class="col-md-6"><label>ชื่อสินค้า</label><input type="text" name="name" class="form-control" required></div>
                <div class="col-md-3"><label>ประเภท</label><select name="category_id" class="form-select"><?php $categories_list->data_seek(0); while($c=$categories_list->fetch_assoc()): ?><option value="<?=$c['id']?>"><?=$c['name']?></option><?php endwhile; ?></select></div>
                <div class="col-md-3"><label>มีรุ่นย่อยไหม?</label><select name="is_variant" id="is_variant_select" class="form-select border-info" onchange="toggleVariantFields(this.value)"><option value="no">ไม่มีรุ่นย่อย</option><option value="yes">มีรุ่นย่อย (ราคาแยกกัน)</option></select></div>
                
                <div id="no_variant_inputs" class="row g-3 px-0 mx-0 mt-1">
                    <div class="col-md-6"><label>ราคาขาย (บาท)</label><input type="number" name="price" class="form-control"></div>
                    <div class="col-md-6"><label>จำนวนสต็อกตั้งต้น</label><input type="number" name="stock" class="form-control" value="0"></div>
                </div>

                <div class="col-12"><label>รายละเอียดสินค้า</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                <div class="col-12"><label>รูปภาพสินค้าหลัก</label><input type="file" name="image" class="form-control"></div>
                
                <div id="variant_fields" class="col-12 mt-4" style="display: none;">
                    <h6 class="text-neon-cyan mb-3 border-bottom border-secondary pb-2">รายการรุ่นย่อย (กำหนดราคาและรูปแยกกันได้)</h6>
                    <div id="variant_container"></div>
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="addVariantRow()"><i class="bi bi-plus-lg"></i> เพิ่มแถวรุ่นย่อย</button>
                </div>
            </div>
        </div>
        <div class="modal-footer border-secondary"><button type="submit" name="save_product" class="btn btn-neon-pink w-100">บันทึกสินค้าเข้าคลัง</button></div>
    </form></div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() { $('.datatable-js').DataTable({ "language": { "search": "ค้นหา:", "lengthMenu": "แสดง _MENU_" } }); });
    
    function toggleVariantFields(val) { 
        $('#no_variant_inputs').toggle(val === 'no'); 
        $('#variant_fields').toggle(val === 'yes'); 
        if(val === 'yes' && $('#variant_container').is(':empty')) addVariantRow(); 
    }

    function addVariantRow() {
        $('#variant_container').append(`
            <div class="variant-card">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4"><label class="small">ชื่อรุ่น (เช่น สีแดง)</label><input type="text" name="v_names[]" class="form-control form-control-sm" required></div>
                    <div class="col-md-2"><label class="small">ราคา</label><input type="number" name="v_prices[]" class="form-control form-control-sm" required></div>
                    <div class="col-md-2"><label class="small">สต็อก</label><input type="number" name="v_stocks[]" class="form-control form-control-sm" value="0"></div>
                    <div class="col-md-3"><label class="small">รูป (ถ้ามี)</label><input type="file" name="v_images[]" class="form-control form-control-sm"></div>
                    <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.variant-card').remove()"><i class="bi bi-x"></i></button></div>
                </div>
            </div>
        `);
    }

    function manualUpdateStock(vid, new_val) { $.get('admin_dashboard.php', {ajax_action: 'update_stock_value', vid: vid, val: new_val}); }
    function openOrderView(o) { $('#orderViewModal').modal('show'); $.get('admin_dashboard.php', {ajax_action: 'get_order_items', oid: o.id}, function(data) { $('#v_items').html(data); }); }
    function viewUserHistory(uid, uname) { $('#h_uname').text(uname); $('#historyModal').modal('show'); $.get('admin_dashboard.php', {ajax_action: 'get_user_history', uid: uid}, function(data) { $('#h_content').html(data); }); }
    function openAddProduct() { $('#productModal').find('form')[0].reset(); $('#variant_container').empty(); toggleVariantFields('no'); $('#productModal').modal('show'); }
    function confirmAction(type, id, tab) { $('#confirmDeleteBtn').attr('href', `?del_id=${id}&type=${type}&tab=${tab}`); $('#confirmDeleteModal').modal('show'); }
</script>
</body>
</html>