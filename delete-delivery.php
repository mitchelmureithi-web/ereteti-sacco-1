<?php
// delete-delivery.php — DELETE: removes one delivery
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: deliveries.php');
    exit;
}

$id = filter_input(INPUT_POST, 'delivery_id', FILTER_VALIDATE_INT);
$date = $_POST['date'] ?? '';

if ($id) {
    $stmt = mysqli_prepare($conn, 'DELETE FROM milk_delivery WHERE delivery_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
}

header('Location: deliveries.php?date=' . urlencode($date) . '&deleted=1');
exit;
