<?php
header('Content-Type: application/json');
require_once 'connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (strlen($name) < 2 || strlen($phone) < 8) {
        echo json_encode(['success' => false, 'message' => 'Имя и телефон обязательны']);
        exit;
    }

    // Привязываем к пользователю, если он залогинен
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    $stmt = $pdo->prepare("INSERT INTO zayavki (user_id, name, phone, message) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([$user_id, $name, $phone, $message]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Заявка отправлена!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка сервера']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный метод']);
}
?>
