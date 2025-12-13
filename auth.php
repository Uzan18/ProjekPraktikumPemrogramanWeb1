<?php
header('Content-Type: application/json');
session_start();
require 'config.php';

$action = $_POST['action'] ?? '';

if ($action === 'register') {
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $gender = $_POST['gender'] ?? '';

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($gender)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        exit;
    }

    if ($password !== $confirm_password) {
        echo json_encode(['status' => 'error', 'message' => 'Passwords do not match']);
        exit;
    }

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Email already registered']);
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, gender) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$first_name, $last_name, $email, $hashed_password, $gender])) {
        echo json_encode(['status' => 'success', 'message' => 'Registration successful']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Registration failed']);
    }

} elseif ($action === 'login') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Email and password required']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        echo json_encode(['status' => 'success', 'message' => 'Login successful', 'user' => $user]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
    }

} elseif ($action === 'logout') {
    session_destroy();
    echo json_encode(['status' => 'success', 'message' => 'Logged out']);

} elseif ($action === 'get_profile') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT first_name, last_name, email, gender FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode(['status' => 'success', 'user' => $user]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>