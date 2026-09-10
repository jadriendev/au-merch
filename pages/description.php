<?php
include_once "../connection/config.php";

$id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM tbl_products WHERE product_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();
?>

<h1><?= $row['product_name']; ?></h1>