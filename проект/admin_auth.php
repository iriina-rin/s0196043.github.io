<?php
// Подключение к БД для проверки учетных данных администратора
$db = new PDO("mysql:host=localhost;dbname=u68775", 'u68775', '7631071', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// Проверка авторизации
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Требуется авторизация';
    exit();
} else {
    // Проверка учетных данных в БД
    $stmt = $db->prepare("SELECT password_hash FROM admin_users WHERE username = ?");
    $stmt->execute([$_SERVER['PHP_AUTH_USER']]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($_SERVER['PHP_AUTH_PW'], $admin['password_hash'])) {
        header('HTTP/1.0 401 Unauthorized');
        echo 'Неверные учетные данные';
        exit();
    }
}