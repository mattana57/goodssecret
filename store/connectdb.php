<?php
$host = "localhost";
$user = "root";
$pass = "6!gSoLZDZxO!";
$db   = "goods_secret_store";

$conn = new mysqli($host,$user,$pass,$db);

if($conn->connect_error){
    die("Connection failed: " . $conn->connect_error);
}
?>
