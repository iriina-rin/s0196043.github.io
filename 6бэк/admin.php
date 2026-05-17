<?php
// 1. Подключение к БД
$pdo = new PDO('mysql:host=localhost;dbname=u82815', 'u82815', '3583398');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 2. HTTP-авторизация
function authenticate($pdo) {
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        return false;
    }

    $stmt = $pdo->prepare("SELECT password_hash FROM admin_users WHERE username = ?");
    $stmt->execute([$_SERVER['PHP_AUTH_USER']]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($_SERVER['PHP_AUTH_PW'], $admin['password_hash'])) {
        return true;
    }
    return false;
}

if (!authenticate($pdo)) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    print('<h1>401 Требуется авторизация</h1>');
    exit();
}

// 3. Обработка действий (Удаление/Редактирование)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_id'])) {
        // Удаление пользователя и его связей
        $id = intval($_POST['delete_id']);
        $pdo->prepare("DELETE FROM application_languages WHERE id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM applications WHERE id = ?")->execute([$id]);
        header('Location: admin.php?msg=deleted');
        exit();
    }
}

// 4. Получение статистики
$stats_stmt = $pdo->query("
    SELECT l.name, COUNT(rl.id) as count 
    FROM programming_languages l 
    LEFT JOIN application_programming rl ON l.id = rl.lang_id 
    GROUP BY l.name
");
$stats = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Получение всех данных пользователей
$users_stmt = $pdo->query("SELECT * FROM applications");
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель администратора</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 30px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background: #eee; }
        .stats-container { background: #f9f9f9; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Панель администратора</h1>
    <p>Вы вошли как: <?= htmlspecialchars($_SERVER['PHP_AUTH_USER']) ?></p>

    <div class="stats-container">
        <h2>Статистика по языкам</h2>
        <ul>
            <?php foreach ($stats as $row): ?>
                <li><?= htmlspecialchars($row['lang_name']) ?>: <strong><?= $row['count'] ?></strong></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <h2>Все заявки</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>ФИО</th>
            <th>Email</th>
            <th>Действия</th>
        </tr>
        <?php foreach ($users as $user): ?>
        <tr>
            <td><?= $user['id'] ?></td>
            <td><?= htmlspecialchars($user['fio']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td>
                <a href="index.php?edit_id=<?= $user['id'] ?>">Редактировать</a>
                
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="delete_id" value="<?= $user['id'] ?>">
                    <button type="submit" onclick="return confirm('Удалить?')">Удалить</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
