<?php
require_once __DIR__.'/includes/config.php';
redirect_if_not_logged_in();

// Получение данных пользователя
$stmt = $db->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$userLanguages = get_user_languages($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Профиль</title>
    <link rel="stylesheet" href="/public/css/main.css">
</head>
<body>
    <?php include __DIR__.'/includes/header.php'; ?>

    <main class="container">
        <h1>Профиль пользователя</h1>

        <div class="profile-info">
            <p><strong>Логин:</strong> <?= sanitize_input($user['login']) ?></p>
            <p><strong>Email:</strong> <?= sanitize_input($user['email']) ?></p>
            <p><strong>Телефон:</strong> <?= sanitize_input($user['phone']) ?></p>

            <h3>Языки программирования:</h3>
            <ul>
                <?php foreach ($userLanguages as $lang): ?>
                    <li><?= sanitize_input($lang) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <a href="index.php" class="btn">Редактировать данные</a>
    </main>

    <?php include __DIR__.'/includes/footer.php'; ?>
</body>
</html>