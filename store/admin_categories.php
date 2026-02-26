<?php
if (isset($_POST['save_cat'])) {
    $n = $conn->real_escape_string($_POST['cat_name']);
    $conn->query("INSERT INTO categories (name, slug) VALUES ('$n', '".strtolower($n)."')");
    echo "<script>window.location='admin_dashboard.php?tab=categories&success=1';</script>";
}
$cats = $conn->query("SELECT * FROM categories ORDER BY id DESC");
?>
<div class="glass-panel">
    <div class="d-flex justify-content-between mb-4">
        <h4><i class="bi bi-tags me-2"></i> จัดการประเภทสินค้า</h4>
        <form method="POST" class="d-flex gap-2">
            <input type="text" name="cat_name" class="form-control" placeholder="ชื่อประเภทใหม่" required>
            <button type="submit" name="save_cat" class="btn btn-primary rounded-pill px-3">เพิ่ม</button>
        </form>
    </div>
    <table class="table text-white w-100 datatable-js">
        <thead><tr class="text-info"><th>ID</th><th>ชื่อประเภท</th><th>จัดการ</th></tr></thead>
        <tbody>
            <?php while($c = $cats->fetch_assoc()): ?>
            <tr><td>#<?= $c['id'] ?></td><td><?= $c['name'] ?></td><td><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></td></tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>