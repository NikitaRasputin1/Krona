<?php
// order_functions.php
// require_once 'connect.php' в файлах, которые подключают этот файл

function generate_order_number($pdo) {
    // Простая генерация уникального номера: Z + timestamp + случай
    return 'Z' . time() . mt_rand(100, 999);
}

function create_order($pdo, $user_id, $cart, $note = null, $status_id = 1, $tip_dostavki = 'standard', $data_dostavki = null) {
    // $cart = [product_id => qty, ...]
    if (empty($cart)) return false;

    // Получаем общую сумму и подготовим данные по товарам
    $ids = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, cena FROM tovary WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $prices = [];
    foreach ($rows as $r) $prices[$r['id']] = $r['cena'];

    $total = 0;
    foreach ($cart as $pid => $qty) {
        $pid = (int)$pid;
        $qty = (int)$qty;
        $price = isset($prices[$pid]) ? (float)$prices[$pid] : 0;
        $total += $price * $qty;
    }

    // Вставляем заказ в zakazy
    $nomer = generate_order_number($pdo);
    $stmt = $pdo->prepare("INSERT INTO zakazy (user_id, status_id, nomer_zakaza, obshaya_summa, tip_dostavki, data_dostavki, komentariy) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $status_id, $nomer, $total, $tip_dostavki, $data_dostavki ?: null, $note ?: null]);
    $order_id = (int)$pdo->lastInsertId();

    // Вставляем позиции в zakaz_tovary (cena_na_moment) и уменьшаем остаток на складе
    $stmtItem = $pdo->prepare("INSERT INTO zakaz_tovary (zakaz_id, tovar_id, kolichestvo, cena_na_moment) VALUES (?, ?, ?, ?)");
    $stmtStock = $pdo->prepare("UPDATE tovary SET kolichestvo = GREATEST(0, kolichestvo - ?) WHERE id = ?");
    foreach ($cart as $pid => $qty) {
        $pid = (int)$pid;
        $qty = (int)$qty;
        $price = isset($prices[$pid]) ? (float)$prices[$pid] : 0;
        $stmtItem->execute([$order_id, $pid, $qty, $price]);
        $stmtStock->execute([$qty, $pid]);
    }

    return ['order_id' => $order_id, 'nomer' => $nomer, 'total' => $total];
}