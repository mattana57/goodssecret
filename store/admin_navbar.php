<?php
// เช็ค session แอดมินตรงนี้ (ถ้ามีระบบแยกสิทธิ์)
?>
<style>
    .admin-nav { background: rgba(26, 0, 40, 0.95); border-bottom: 1px solid #bb86fc; padding: 15px 0; }
    .nav-link-admin { color: #fff !important; margin: 0 10px; font-weight: 500; transition: 0.3s; border-radius: 10px; padding: 8px 15px !important; }
    .nav-link-admin:hover, .nav-link-admin.active { background: rgba(187, 134, 252, 0.2); color: #00f2fe !important; box-shadow: 0 0 10px rgba(0, 242, 254, 0.3); }
    .text-neon-pink { color: #f107a3; text-shadow: 0 0 5px #f107a3; }
</style>
<nav class="navbar navbar-expand-lg admin-nav sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold text-white" href="admin_products.php">
            <i class="bi bi-shield-lock-fill text-neon-pink me-2"></i>ADMIN SECRET
        </a>
        <button class="navbar-toggler border-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#adminMenu">
            <i class="bi bi-list text-white"></i>
        </button>
        <div class="collapse navbar-collapse" id="adminMenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link nav-link-admin" href="admin_products.php"><i class="bi bi-box-seam me-1"></i> 1. จัดการสินค้า</a></li>
                <li class="nav-item"><a class="nav-link nav-link-admin" href="admin_categories.php"><i class="bi bi-grid me-1"></i> 2. จัดการประเภท</a></li>
                <li class="nav-item"><a class="nav-link nav-link-admin" href="admin_customers.php"><i class="bi bi-people me-1"></i> 3. จัดการลูกค้า</a></li>
                <li class="nav-item"><a class="nav-link nav-link-admin" href="admin_orders.php"><i class="bi bi-receipt me-1"></i> 4. จัดการออเดอร์</a></li>
                <li class="nav-item"><a class="nav-link nav-link-admin text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a></li>
            </ul>
        </div>
    </div>
</nav>