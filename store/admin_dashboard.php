<?php
session_start();
include "connectdb.php";

// --- [ระบบความปลอดภัย]: เช็คสิทธิ์แอดมิน ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit();
}

// --- [ส่วน AJAX]: จัดการสต็อกและการดึงข้อมูล ---
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
        $html = '<table class="table table-hover small text-white"><thead><tr class="text-info"><th>บิล ID</th><th>วันที่</th><th>ยอดรวม</th><th>สถานะ</th></tr></thead><tbody>';
        while($r = $q->fetch_assoc()) {
            $html .= "<tr><td>#".str_pad($r['id'],5,'0',STR_PAD_LEFT)."</td><td>".date('d/m/y', strtotime($r['created_at']))."</td><td class='text-white'>฿".number_format($r['total_price'])."</td><td>{$r['status']}</td></tr>";
        }
        if($q->num_rows == 0) $html .= '<tr><td colspan="4" class="text-center opacity-50">ไม่มีประวัติ</td></tr>';
        echo $html . '</tbody></table>'; exit();
    }
}

// --- [Logic จัดการสินค้า]: บันทึกข้อมูล (Products & Variants) ---
if (isset($_POST['save_product'])) {
    $name = $conn->real_escape_string($_POST['name']); 
    $cat_id = $_POST['category_id']; 
    $desc = $conn->real_escape_string($_POST['description']);
    $is_variant = $_POST['is_variant']; 
    $p_id = !empty($_POST['product_id']) ? intval($_POST['product_id']) : null;

    $img_name = "default.png";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $img_name = time() . "_" . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], "images/" . $img_name);
    }

    if ($p_id) {
        $conn->query("UPDATE products SET name='$name', category_id='$cat_id', description='$desc' WHERE id=$p_id");
        $target_p_id = $p_id;
    } else {
        $pr = ($is_variant == 'no') ? $_POST['price'] : 0;
        $st = ($is_variant == 'no') ? intval($_POST['stock']) : 0;
        $conn->query("INSERT INTO products (name, price, stock, category_id, description, image) VALUES ('$name', '$pr', '$st', '$cat_id', '$desc', '$img_name')");
        $target_p_id = $conn->insert_id; 
    }

    if($is_variant == 'yes' && isset($_POST['v_names'])) {
        foreach($_POST['v_names'] as $i => $vname) {
            $vp = $_POST['v_prices'][$i]; $vs = $_POST['v_stocks'][$i]; $vimg = "";
            if (isset($_FILES['v_images']['name'][$i]) && $_FILES['v_images']['error'][$i] == 0) {
                $vimg = "v_" . time() . "_" . $i . "_" . basename($_FILES['v_images']['name'][$i]);
                move_uploaded_file($_FILES['v_images']['tmp_name'][$i], "images/" . $vimg);
            }
            $conn->query("INSERT INTO product_variants (product_id, variant_name, price, stock, variant_image) VALUES ($target_p_id, '$vname', '$vp', $vs, '$vimg')");
        }
    }
    header("Location: admin_dashboard.php?tab=products&success=1"); exit();
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
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background: #0c001c; color: #ffffff !important; }
        .glass-panel { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(15px); border: 1px solid rgba(187, 134, 252, 0.2); border-radius: 25px; padding: 30px; }
        .nav-pills .nav-link { color: #ffffff; }
        .nav-pills .nav-link.active { background: #bb86fc !important; color: #120018 !important; font-weight: bold; }
        .table { color: #ffffff !important; }
        .form-control, .form-select { background: rgba(255, 255, 255, 0.1) !important; color: #fff !important; }
        .btn-neon { background: #f107a3; border: none; color: #fff; }
    </style>
</head>
<body>

<div class="container-fluid py-5 px-4">
    <div class="d-flex justify-content-between mb-5">
        <h2 style="color:#f107a3;">ADMIN DASHBOARD</h2>
        <a href="logout.php" class="btn btn-danger">ออกจากระบบ</a>
    </div>

    <ul class="nav nav-pills mb-5 justify-content-center" id="adminTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#products">สินค้า</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#customers">ลูกค้า</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="products">
            <div class="glass-panel">
                <div class="d-flex justify-content-between mb-4"><h4>รายการสินค้า</h4><button class="btn btn-neon" onclick="openAddProduct()">+ เพิ่มสินค้า</button></div>
                <table class="table">
                    <thead><tr><th>รูป</th><th>ชื่อ</th><th>สต็อก</th><th>จัดการ</th></tr></thead>
                    <tbody>
                        <?php while($p = $products->fetch_assoc()): 
                            $v_q = $conn->query("SELECT * FROM product_variants WHERE product_id=".$p['id']);
                        ?>
                        <tr>
                            <td><img src="images/<?= $p['image'] ?>" width="40"></td>
                            <td><?= $p['name'] ?></td>
                            <td>
                                <?php if($v_q->num_rows == 0): ?>
                                    <?= $p['stock'] ?>
                                <?php else: while($v = $v_q->fetch_assoc()): ?>
                                    <div class="small"><?= $v['variant_name'] ?>: <?= $v['stock'] ?></div>
                                <?php endwhile; endif; ?>
                            </td>
                            <td><button class="btn btn-sm btn-warning" onclick='openEditProduct(<?= json_encode($p) ?>)'><i class="bi bi-pencil"></i></button></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="customers">
            <div class="glass-panel">
                <h4>จัดการลูกค้า</h4>
                <table class="table">
                    <thead><tr><th>Username</th><th>จัดการ</th></tr></thead>
                    <tbody>
                        <?php while($u = $users_list->fetch_assoc()): ?>
                        <tr>
                            <td><?= $u['username'] ?></td>
                            <td><button class="btn btn-sm btn-info" onclick="viewUserHistory(<?= $u['id'] ?>, '<?= $u['username'] ?>')"><i class="bi bi-clock-history"></i> ประวัติ</button></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><form class="modal-content" style="background:#1a0028;" method="POST" enctype="multipart/form-data">
        <div class="modal-header border-secondary"><h5 id="p_title">จัดการสินค้า</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="hidden" name="product_id" id="p_id">
            <div class="row g-3">
                <div class="col-md-6"><label>ชื่อสินค้า</label><input type="text" name="name" id="p_name" class="form-control" required></div>
                <div class="col-md-3"><label>ประเภท</label><select name="category_id" id="p_cat" class="form-select"><?php $categories_list->data_seek(0); while($c=$categories_list->fetch_assoc()): ?><option value="<?=$c['id']?>"><?=$c['name']?></option><?php endwhile; ?></select></div>
                <div class="col-md-3"><label>มีรุ่นย่อย?</label><select name="is_variant" id="is_variant_select" class="form-select" onchange="toggleVariantFields(this.value)"><option value="no">ไม่มี</option><option value="yes">มี</option></select></div>
                <div id="no_variant_inputs" class="row g-3 px-0 mx-0"><div class="col-md-6"><label>ราคา</label><input type="number" name="price" id="p_price" class="form-control"></div><div class="col-md-6"><label>สต็อก</label><input type="number" name="stock" id="p_stock" class="form-control"></div></div>
                <div class="col-12"><label>รูปภาพหลัก</label><input type="file" name="image" class="form-control"></div>
                <div id="variant_fields" style="display:none;" class="col-12 mt-3"><div id="variant_container"></div><button type="button" class="btn btn-sm btn-outline-info" onclick="addVariantRow()">+ เพิ่มรุ่นย่อย</button></div>
            </div>
        </div>
        <div class="modal-footer border-secondary"><button type="submit" name="save_product" class="btn btn-primary w-100">บันทึก</button></div>
    </form></div>
</div>

<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content" style="background:#1a0028;"><div class="modal-header border-secondary"><h5>ประวัติ: <span id="h_uname"></span></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body" id="h_content"></div></div></div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // --- ฟังก์ชันที่ทำให้ปุ่มกดได้ ---
    function toggleVariantFields(val) { $('#no_variant_inputs').toggle(val === 'no'); $('#variant_fields').toggle(val === 'yes'); }
    
    function addVariantRow() {
        $('#variant_container').append(`<div class="variant-card mb-2 p-2 border border-secondary rounded"><div class="row g-2 align-items-end"><div class="col-md-3"><label class="small">รุ่น</label><input type="text" name="v_names[]" class="form-control form-control-sm" required></div><div class="col-md-2"><label class="small">ราคา</label><input type="number" name="v_prices[]" class="form-control form-control-sm" required></div><div class="col-md-2"><label class="small">สต็อก</label><input type="number" name="v_stocks[]" class="form-control form-control-sm" value="0"></div><div class="col-md-4"><label class="small">รูปภาพ</label><input type="file" name="v_images[]" class="form-control form-control-sm"></div><div class="col-md-1"><button type="button" class="btn btn-sm btn-danger" onclick="$(this).parent().parent().parent().remove()">X</button></div></div></div>`);
    }

    function viewUserHistory(uid, uname) { 
        $('#h_uname').text(uname); $('#historyModal').modal('show'); 
        $('#h_content').html('<div class="text-center p-5"><div class="spinner-border"></div></div>');
        $.get('admin_dashboard.php', {ajax_action: 'get_user_history', uid: uid}, function(data) { $('#h_content').html(data); }); 
    }

    function openAddProduct() { $('#productModal').find('form')[0].reset(); $('#p_id').val(''); $('#variant_container').empty(); toggleVariantFields('no'); $('#p_title').text('เพิ่มสินค้าใหม่'); $('#productModal').modal('show'); }
    
    function openEditProduct(p) { 
        $('#p_id').val(p.id); $('#p_name').val(p.name); $('#p_price').val(p.price); $('#p_stock').val(p.stock); $('#p_cat').val(p.category_id); $('#p_title').text('แก้ไขสินค้า'); toggleVariantFields('no'); $('#productModal').modal('show'); 
    }
</script>
</body>
</html>