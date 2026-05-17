<?php
session_start();

// =============================================
// НАСТРОЙКИ БЕЗОПАСНОСТИ
// =============================================

// Отключаем вывод ошибок на продакшене
error_reporting(0);
ini_set('display_errors', 0);

// Генерация CSRF-токена при необходимости
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// =============================================
// ПРОВЕРКА АВТОРИЗАЦИИ
// =============================================

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit();
}

// =============================================
// ПОДКЛЮЧЕНИЕ К БАЗЕ ДАННЫХ
// =============================================

try {
    $db = new PDO("mysql:host=localhost;dbname=u68775", 'u68775', '7631071', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Произошла ошибка подключения к базе данных. Пожалуйста, попробуйте позже.");
}

// =============================================
// ОБРАБОТКА УДАЛЕНИЯ ПОЛЬЗОВАТЕЛЯ
// =============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    // Проверка CSRF-токена
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Недействительный CSRF-токен");
    }

    $id = (int)$_POST['delete'];
    if ($id <= 0) {
        die("Неверный идентификатор пользователя");
    }

    try {
        $db->beginTransaction();

        // Удаляем связанные записи о языках программирования
        $stmt = $db->prepare("DELETE FROM application_languages WHERE application_id = ?");
        $stmt->execute([$id]);

        // Удаляем основную запись пользователя
        $stmt = $db->prepare("DELETE FROM applications WHERE id = ?");
        $stmt->execute([$id]);

        $db->commit();

        // Перенаправляем с сообщением об успехе
        header("Location: admin.php?deleted=1");
        exit();
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Delete error: ID $id - " . $e->getMessage());
        die("Произошла ошибка при удалении пользователя");
    }
}

// =============================================
// ПОЛУЧЕНИЕ ДАННЫХ
// =============================================

try {
    // Получаем список всех пользователей
    $stmt = $db->prepare("SELECT * FROM applications ORDER BY id");
    $stmt->execute();
    $users = $stmt->fetchAll();

    // Получаем статистику по языкам программирования
    $stmt = $db->prepare("
        SELECT pl.name, COUNT(al.application_id) as user_count
        FROM programming_languages pl
        LEFT JOIN application_languages al ON pl.id = al.language_id
        GROUP BY pl.name
        ORDER BY user_count DESC
    ");
    $stmt->execute();
    $stats = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Data fetch error: " . $e->getMessage());
    die("Произошла ошибка при получении данных");
}

// =============================================
// HTML-ШАБЛОН
// =============================================
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' cdnjs.cloudflare.com 'unsafe-inline'; style-src 'self' cdnjs.cloudflare.com 'unsafe-inline'; img-src 'self' data:">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --danger: #f72585;
            --success: #4cc9f0;
            --light: #f8f9fa;
            --dark: #212529;
            --border-radius: 8px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            color: var(--dark);
            line-height: 1.6;
            padding: 20px;
            margin: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: var(--primary);
            font-size: 28px;
            margin: 0;
        }

        .logout-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: var(--danger);
            color: white;
            padding: 8px 16px;
            border-radius: var(--border-radius);
            text-decoration: none;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        .logout-btn:hover {
            background-color: #d1146d;
            transform: translateY(-2px);
        }

        .alert {
            padding: 12px 16px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert.success {
            background-color: rgba(76, 201, 240, 0.2);
            border-left: 4px solid var(--success);
            color: #0a6c83;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 15px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary);
            margin: 5px 0;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: var(--shadow);
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .admin-table th,
        .admin-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .admin-table th {
            background-color: var(--primary);
            color: white;
        }

        .admin-table tr:hover {
            background-color: #f5f5f5;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }

        .edit-btn {
            background-color: var(--success);
            color: white;
        }

        .delete-btn {
            background-color: var(--danger);
            color: white;
        }

        .form-container {
            display: inline;
            margin: 0;
            padding: 0;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .admin-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-user-shield"></i> Админ-панель</h1>
            <form method="post" action="logout.php" class="form-container">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Выйти
                </button>
            </form>
        </header>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i> Пользователь успешно удален!
            </div>
        <?php endif; ?>

        <h2><i class="fas fa-chart-pie"></i> Статистика по языкам программирования</h2>
        <div class="stats-grid">
            <?php foreach ($stats as $stat): ?>
                <div class="stat-card">
                    <h3><?= htmlspecialchars($stat['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <div class="stat-value"><?= htmlspecialchars($stat['user_count'], ENT_QUOTES, 'UTF-8') ?></div>
                    <p><i class="fas fa-users"></i> пользователей</p>
                </div>
            <?php endforeach; ?>
        </div>

        <h2><i class="fas fa-users"></i> Список пользователей</h2>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ФИО</th>
                    <th>Телефон</th>
                    <th>Email</th>
                    <th>Дата рождения</th>
                    <th>Пол</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($user['fio'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($user['phone'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($user['birthdate'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $user['gender'] == 'male' ? 'Мужской' : 'Женский' ?></td>
                        <td>
                            <a href="edit_user.php?id=<?= htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8') ?>" class="action-btn edit-btn">
                                <i class="fas fa-edit"></i> Ред.
                            </a>
                            <form method="post" action="admin.php" class="form-container">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="delete" value="<?= htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="action-btn delete-btn" onclick="return confirm('Вы уверены, что хотите удалить этого пользователя?')">
                                    <i class="fas fa-trash-alt"></i> Удал.
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>