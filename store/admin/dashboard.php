<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role']!="admin"){
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
<h2>🔐 Admin Dashboard</h2>
<p>ยินดีต้อนรับผู้ดูแลระบบ</p>

<a href="index.php" class="btn btn-secondary">กลับหน้าแรก</a>
</div>

</body>
</html>
