<?php
session_start();
include "connectdb.php";
include "navbar.php";

$slug = $_GET['slug'] ?? "";
$search = $_GET['search'] ?? "";

/* GET CATEGORY INFO */
$catQuery = $conn->prepare("SELECT * FROM categories WHERE slug=?");
$catQuery->bind_param("s",$slug);
$catQuery->execute();
$category = $catQuery->get_result()->fetch_assoc();

/* GET PRODUCTS */
$sql = "
SELECT products.* FROM products
LEFT JOIN categories ON products.category_id = categories.id
WHERE 1
";

if($slug){
    $sql .= " AND categories.slug='".$conn->real_escape_string($slug)."'";
}

if($search){
    $sql .= " AND products.name LIKE '%".$conn->real_escape_string($search)."%'";
}

$products = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title><?= $category['name'] ?? 'ค้นหาสินค้า'; ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background:#2a0845;color:white">

<div class="container py-5">

<h3 class="mb-4">
<?= $category['name'] ?? 'ผลการค้นหา'; ?>
</h3>

<form class="d-flex mb-4">
<input type="hidden" name="slug" value="<?= $slug ?>">
<input class="form-control me-2" name="search" placeholder="ค้นหาในหมวดนี้">
<button class="btn btn-light">ค้นหา</button>
</form>

<div class="row">
<?php while($p = $products->fetch_assoc()){ ?>
<div class="col-md-3 mb-4">
<div class="card p-3 text-center">
<img src="images/<?= $p['image']; ?>" class="img-fluid mb-2">
<h6><?= $p['name']; ?></h6>
<p><?= number_format($p['price']); ?> บาท</p>
</div>
</div>
<?php } ?>
</div>

<a href="index.php" class="btn btn-light mt-3">กลับหน้าแรก</a>

</div>
</body>
</html>
