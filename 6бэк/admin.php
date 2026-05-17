<?php
session_start();

// -------------------------------
// 1. Подключение к базе данных
// -------------------------------
try {
    $db = new PDO("mysql:host=localhost;dbname=u82815", 'u82815', '3583398', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// -------------------------------
// 2. Basic Auth (HTTP-аутентификация)
// -------------------------------
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    die("Требуется авторизация");
}

$username = $_SERVER['PHP_AUTH_USER'];
$password = $_SERVER['PHP_AUTH_PW'];

// Проверка учётных данных из базы
$stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ?");
$stmt->execute([$username]);
$admin = $stmt->fetch();

if (!$admin || !password_verify($password, $admin['password_hash'])) {
    die("Неверные учетные данные");
}

// -------------------------------
// 3. Проверка сессии администратора
// -------------------------------
if (!isset($_SESSION['admin_logged_in'])) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = $username;
}

// -------------------------------
// 4. Обработка удаления пользователей
// -------------------------------
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $db->beginTransaction();
        $db->prepare("DELETE FROM application_languages WHERE application_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM applications WHERE id = ?")->execute([$id]);
        $db->commit();
        header("Location: admin.php?deleted=1");
        exit();
    } catch (PDOException $e) {
        $db->rollBack();
        die("Ошибка при удалении: " . $e->getMessage());
    }
}

// -------------------------------
// 5. Получение данных пользователей и статистики
// -------------------------------
$users = $db->query("SELECT * FROM applications ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

$stats = $db->query("
    SELECT pl.name, COUNT(al.application_id) as user_count
    FROM programming_languages pl
    LEFT JOIN application_languages al ON pl.id = al.language_id
    GROUP BY pl.name
    ORDER BY user_count DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* -------------------------------
           Стилизация (копия твоих стилей)
        ------------------------------- */
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --danger: #f72585;
            --success: #4cc9f0;
            --light: #f8f9fa;
            --dark: #212529;
            --border-radius: 8px;
            --shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            color: var(--dark);
            line-height: 1.6;
            padding: 20px;
        }
        .container { max-width: 800px; margin: 0 auto; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 15px; border-bottom: 1px solid rgba(0,0,0,0.1); }
        h1 { color: var(--primary); font-size: 28px; }
        .logout-btn { display: inline-flex; align-items: center; gap: 8px; background-color: var(--danger); color: white; padding: 8px 16px; border-radius: var(--border-radius); text-decoration: none; transition: all 0.3s; }
        .logout-btn:hover { background-color: #d1146d; transform: translateY(-2px); }
        .alert { padding: 12px 16px; border-radius: var(--border-radius); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert.success { background-color: rgba(76,201,240,0.2); border-left: 4px solid var(--success); color: #0a6c83; }
        .admin-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; box-shadow: var(--shadow); border-radius: var(--border-radius); overflow: hidden; }
        .admin-table th, .admin-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        .admin-table th { background-color: var(--primary); color: white; }
        .admin-table tr:hover { background-color: #f5f5f5; }
        .action-btn { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 14px; }
        .edit-btn { background-color: var(--success); color: white; }
        .delete-btn { background-color: var(--danger); color: white; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px,1fr)); gap: 15px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 15px; border-radius: var(--border-radius); box-shadow: var(--shadow); }
        .stat-value { font-size: 24px; font-weight: bold; color: var(--primary); margin: 5px 0; }
    </style>
</head>
<body>
<div class="container">
    <header>
        <h1><i class="fas fa-user-shield"></i> Админ-панель</h1>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Выйти</a>
    </header>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert success"><i class="fas fa-check-circle"></i> Пользователь успешно удален!</div>
    <?php endif; ?>

    <h2><i class="fas fa-chart-pie"></i> Статистика по языкам</h2>
    <div class="stats-grid">
        <?php foreach ($stats as $stat): ?>
            <div class="stat-card">
                <h3><?= htmlspecialchars($stat['name']) ?></h3>
                <div class="stat-value"><?= $stat['user_count'] ?></div>
                <p><i class="fas fa-users"></i> пользователей</p>
            </div>
        <?php endforeach; ?>
    </div>

    <h2><i class="fas fa-users"></i> Список пользователей</h2>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th><th>ФИО</th><th>Телефон</th><th>Email</th><th>Дата рождения</th><th>Пол</th><th>Действия</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= $user['id'] ?></td>
                <td><?= htmlspecialchars($user['fio']) ?></td>
                <td><?= htmlspecialchars($user['phone']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= $user['birthdate'] ?></td>
                <td><?= $user['gender'] == 'male' ? 'Мужской' : 'Женский' ?></td>
                <td>
                    <a href="edit_user.php?id=<?= $user['id'] ?>" class="action-btn edit-btn"><i class="fas fa-edit"></i> Ред.</a>
                    <a href="admin.php?delete=<?= $user['id'] ?>" class="action-btn delete-btn" onclick="return confirm('Удалить этого пользователя?')"><i class="fas fa-trash-alt"></i> Удал.</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
