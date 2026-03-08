<?php
// ห้าม include admin_dashboard.php ในนี้
include "connectdb.php";

$id = intval($_GET['id']);

if (isset($_POST['update_product'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $is_hot = isset($_POST['is_hot']) ? 1 : 0;
    $is_trending = isset($_POST['is_trending']) ? 1 : 0;

    $sql = "UPDATE products SET 
            name='$name', 
            price='$price', 
            stock='$stock', 
            description='$description', 
            is_hot='$is_hot', 
            is_trending='$is_trending' 
            WHERE id=$id";
    
    if ($conn->query($sql)) {
        echo "<script>alert('บันทึกสำเร็จ'); window.location='admin_dashboard.php?tab=products';</script>";
    }
}

$p = $conn->query("SELECT * FROM products WHERE id=$id")->fetch_assoc();
?>

<div class="edit-card p-4">
    <h4 class="text-neon-cyan mb-4">แก้ไขสินค้า: <?= $p['name'] ?></h4>
    <form method="POST">
        <div class="row">
            <div class="col-md-4">
                <img src="images/<?= $p['image'] ?>" class="img-fluid rounded border border-info mb-3">
            </div>
            <div class="col-md-8">
                ชื่อสินค้า: <input type="text" name="name" class="form-control mb-2" value="<?= $p['name'] ?>">
                ราคา: <input type="number" name="price" class="form-control mb-2" value="<?= $p['price'] ?>">
                สต็อก: <input type="number" name="stock" class="form-control mb-2" value="<?= $p['stock'] ?>">
                รายละเอียด: <textarea name="description" class="form-control mb-2"><?= $p['description'] ?></textarea>
                
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="is_hot" <?= $p['is_hot'] ? 'checked' : '' ?>>
                    <label class="form-check-label">สินค้าแนะนำ (Hot)</label>
                </div>
                
                <button type="submit" name="update_product" class="btn btn-primary mt-3">บันทึกการแก้ไข</button>
            </div>
        </div>
    </form>
</div>