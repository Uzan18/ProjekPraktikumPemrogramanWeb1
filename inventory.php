<?php
header('Content-Type: application/json');
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'save_item') {
    $item_name = $_POST['item_name'] ?? '';

    if (empty($item_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Item name required']);
        exit;
    }

    // Check if item exists for user
    $stmt = $pdo->prepare("SELECT id, quantity FROM inventory WHERE user_id = ? AND item_name = ?");
    $stmt->execute([$user_id, $item_name]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $stmt = $pdo->prepare("UPDATE inventory SET quantity = quantity + 1 WHERE id = ?");
        $stmt->execute([$existing['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO inventory (user_id, item_name, quantity) VALUES (?, ?, 1)");
        $stmt->execute([$user_id, $item_name]);
    }

    echo json_encode(['status' => 'success', 'message' => 'Item saved']);

} elseif ($action === 'get_inventory') {
    // Fetch user inventory
    $stmt = $pdo->prepare("SELECT item_name, quantity FROM inventory WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch user email
    $stmtUser = $pdo->prepare("SELECT email, gender FROM users WHERE id = ?");
    $stmtUser->execute([$user_id]);
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);
    $userEmail = $userData ? $userData['email'] : '';
    $userGender = $userData ? $userData['gender'] : '';

    echo json_encode([
        'status' => 'success',
        'inventory' => $items,
        'user_name' => $_SESSION['user_name'] ?? 'Player',
        'user_email' => $userEmail,
        'user_gender' => $userGender
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>