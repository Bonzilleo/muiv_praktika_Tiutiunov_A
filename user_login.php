<?php
session_start();
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: dashboard.php?error=access_denied");
    exit;
}

$username = trim($_POST['username']);
$password = $_POST['password'];

// Запрос пользователя по логину
$stmt = $pdo->prepare("SELECT id, username, password, is_admin FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Пользователь не найден
    header("Location: dashboard.php?error=invalid_credentials&message=Пользователь с таким логином не найден.");
    exit;
}

// Верификация пароля функцией php password_verify()
if (password_verify($password, $user['password'])) {

    $is_therapist = false;

    // Запрашиваем из таблицы therapists, есть ли запись для этого user_id
    $stmt_check = $pdo->prepare("SELECT id, name FROM therapists WHERE user_id = ? LIMIT 1");
    $stmt_check->execute([$user['id']]);
    $therapist_data = $stmt_check->fetch();

    if ($therapist_data) {
        $is_therapist = true; // Специалист найден, значит - заходит психолог
        $therapist_name = $therapist_data['name'];
    }

    // Успешная авторизация и установка сессии
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['is_admin'] = (bool) $user['is_admin'];
    $_SESSION['is_therapist'] = $is_therapist;

    if ($is_therapist) {

        $_SESSION['therapist_id'] = $therapist_data['id'];

    }

    if ($therapist_name) {
        $_SESSION['profile_name'] = htmlspecialchars($therapist_name);
    } else {
        $_SESSION['profile_name'] = htmlspecialchars($user['username']);
    }

    header("Location: dashboard.php");
    exit;
} else {
    // Пароль не совпал
    header("Location: dashboard.php?error=invalid_credentials&message=Неверный логин или пароль.");
    exit;
}
?>