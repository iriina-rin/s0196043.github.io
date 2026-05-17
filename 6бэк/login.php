<?php
session_start();

// Если уже авторизован - перенаправляем на главную
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Подключение к БД
$db = new PDO("mysql:host=localhost;dbname=u82389", 'u82389', '3736104', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$error = '';
$debug_info = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login']);
    $password = trim($_POST['password']);

    // Отладочная информация
    $debug_info .= "Попытка входа: login='$login'\n";

    try {
        // Ищем пользователя в БД
        $stmt = $db->prepare("SELECT id, password_hash FROM applications WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if ($user) {
            $debug_info .= "Найден пользователь: ID={$user['id']}\n";
            $debug_info .= "Хэш из БД: {$user['password_hash']}\n";
            $debug_info .= "Длина хэша: " . strlen($user['password_hash']) . " символов\n";

            // Проверяем пароль
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $debug_info .= "Авторизация прошла успешно\n";

                // Перенаправляем после успешного входа
                header('Location: index.php');
                exit();
            } else {
                $debug_info .= "Ошибка: неверные учетные данные\n";
                $error = 'Неверный логин или пароль';
            }
        } 
    } catch (PDOException $e) {
        $error = 'Ошибка базы данных';
        $debug_info .= "Ошибка БД: " . $e->getMessage() . "\n";
    }

    // Логируем отладочную информацию
    error_log($debug_info);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <title>Вход в систему</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            background-color: #FFEBEE;
            padding: 30px;
            border-radius: 8px;
            width: 100%;
            max-width: 400px;
        }
        h2 {
            text-align: center;
            margin-bottom: 25px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
        }
        .error {
            border: 2px solid red;
            border-radius: 4px;
            color: red;
            margin: 15px 0;
            padding: 10px;
            text-align: center;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
        .register-link a {
            text-decoration: none;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Вход в систему</h2>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="login">Логин:</label>
                <input type="text" id="login" name="login" required>
            </div>

            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary">Войти</button>
        </form>

        <div class="register-link">
            Нет аккаунта? <a href="register.php">Зарегистрируйтесь</a>
        </div>
        <div class="register-link">
            <a href="admin_logout.php">Войти как администратор</a>
        </div>
    </div>
</body>
</html>
