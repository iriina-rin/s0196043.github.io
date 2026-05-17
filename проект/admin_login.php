<?php
session_start();

// Подключение к БД
$db = new PDO("mysql:host=localhost;dbname=u68775", 'u68775', '7631071', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// Экстренный сброс пароля (доступен только по специальной ссылке)
if (isset($_GET['emergency_reset'])) {
    $new_hash = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt = $db->prepare("UPDATE admin_users SET password_hash = ? WHERE username = 'admin'");
    $stmt->execute([$new_hash]);
    die("Пароль сброшен. Новый пароль: admin123");
}

$error = '';
$attempts = $_SESSION['login_attempts'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $attempts < 5) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Очистка от невидимых символов
    $password = preg_replace('/[^\x20-\x7E]/', '', $password);

    // Проверка учетных данных
    $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin) {
        // Проверка пароля с подробным логированием
        if (password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            $_SESSION['login_attempts'] = 0;
            header('Location: admin.php');
            exit();
        } else {
            // Логирование неудачной попытки
            error_log("Failed admin login attempt. Username: $username, IP: {$_SERVER['REMOTE_ADDR']}");
            $error = 'Неверный пароль';
        }
    } else {
        $error = 'Пользователь не найден';
    }

    $_SESSION['login_attempts'] = ++$attempts;
} elseif ($attempts >= 5) {
    $error = 'Слишком много попыток. Попробуйте позже.';
    sleep(5); // Замедление brute-force атак
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в панель администратора</title>
    <style>
        :root {
            --primary: #4361ee;
            --error: #f72585;
            --background: #f8f9fa;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        h1 {
            text-align: center;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        button {
            width: 100%;
            padding: 0.75rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 1rem;
        }
        button:hover {
            background-color: #3a56d4;
        }
        .error {
            color: var(--error);
            margin: 1rem 0;
            padding: 0.75rem;
            background-color: rgba(247, 37, 133, 0.1);
            border-radius: 4px;
        }
        .attempts-warning {
            color: #ff6b35;
            text-align: center;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1><i class="fas fa-lock"></i> Вход администратора</h1>

        <?php if ($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                <?php if ($attempts > 0): ?>
                    <div style="margin-top: 0.5rem; font-size: 0.9rem;">
                        Попыток: <?= $attempts ?> из 5
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($attempts >= 5): ?>
            <div class="attempts-warning">
                <i class="fas fa-clock"></i> Превышено количество попыток. Подождите 5 минут.
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label for="username">Логин:</label>
                    <input type="text" id="username" name="username" required
                           value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="password">Пароль:</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit">
                    <i class="fas fa-sign-in-alt"></i> Войти
                </button>
            </form>
        <?php endif; ?>

        <!-- Ссылка для экстренного сброса (должна быть удалена в продакшене) -->
        <?php if (isset($_GET['debug'])): ?>
            <div style="margin-top: 2rem; text-align: center; font-size: 0.8rem;">
                <a href="admin_login.php?emergency_reset=1" style="color: var(--error);">
                    Экстренный сброс пароля (admin123)
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>