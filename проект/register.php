<?php
session_start();

// Настройки базы данных
$db_host = 'localhost';
$db_name = 'u68775';
$db_user = 'u68775';
$db_pass = '7631071';

// Подключение к БД
try {
    $db = new PDO(
        "mysql:host=$db_host;dbname=$db_name",
        $db_user,
        $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Функция для очистки ввода
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Проверка авторизации
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Если пользователь уже авторизован - редирект
if (is_logged_in()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = sanitize_input($_POST['login']);
    $password = sanitize_input($_POST['password']);
    $password_confirm = sanitize_input($_POST['password_confirm']);

    // Валидация
    if (strlen($login) < 4) {
        $error = 'Логин должен быть не менее 4 символов';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } elseif ($password !== $password_confirm) {
        $error = 'Пароли не совпадают';
    } else {
        try {
            // Проверка уникальности логина
            $stmt = $db->prepare("SELECT COUNT(*) FROM applications WHERE login = ?");
            $stmt->execute([$login]);

            if ($stmt->fetchColumn() == 0) {
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);

                $stmt = $db->prepare("INSERT INTO applications (login, password_hash) VALUES (?, ?)");
                $stmt->execute([$login, $passwordHash]);

                $success = true;
            } else {
                $error = 'Этот логин уже занят';
            }
        } catch (PDOException $e) {
            $error = 'Ошибка регистрации: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .register-container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        h2 {
            text-align: center;
            color: #4361ee;
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
            background-color: #4361ee;
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
            color: #f72585;
            margin: 1rem 0;
            padding: 0.75rem;
            background-color: rgba(247, 37, 133, 0.1);
            border-radius: 4px;
        }
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        .login-link a {
            color: #4361ee;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2><i class="fas fa-user-plus"></i> Регистрация</h2>

        <?php if ($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div style="text-align: center; margin: 1rem 0; color: green;">
                <i class="fas fa-check-circle"></i> Регистрация прошла успешно!
            </div>
            <div class="login-link">
                <a href="login.php"><i class="fas fa-sign-in-alt"></i> Перейти к входу</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label for="login">Логин:</label>
                    <input type="text" id="login" name="login" required
                           value="<?= isset($login) ? htmlspecialchars($login) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="password">Пароль:</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Подтвердите пароль:</label>
                    <input type="password" id="password_confirm" name="password_confirm" required>
                </div>

                <button type="submit">
                    <i class="fas fa-user-plus"></i> Зарегистрироваться
                </button>
            </form>

            <div class="login-link">
                Уже есть аккаунт? <a href="login.php"><i class="fas fa-sign-in-alt"></i> Войти</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>