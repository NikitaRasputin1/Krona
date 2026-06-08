<?php
session_start();
require_once 'connect.php';

$product_id = (int)($_POST['product_id'] ?? 0);

if ($product_id > 0) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    if (!isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] = 0;
    }
    $_SESSION['cart'][$product_id]++;
}

header('Location: produkciya.php?added=1');
exit;