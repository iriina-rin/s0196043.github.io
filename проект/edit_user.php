<?php
// Проверка авторизации
require_once 'admin_auth.php';

// Подключение к БД
$db = new PDO("mysql:host=localhost;dbname=u68775", 'u68775', '7631071', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// Получение ID пользователя
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получение данных пользователя
$stmt = $db->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    die("Пользователь не найден");
}

// Получение языков пользователя
$langStmt = $db->prepare("SELECT pl.name FROM application_languages al
                         JOIN programming_languages pl ON al.language_id = pl.id
                         WHERE al.application_id = ?");
$langStmt->execute([$userId]);
$userLanguages = $langStmt->fetchAll(PDO::FETCH_COLUMN);

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $allowedLanguages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskel', 'Clojure', 'Prolog', 'Scala', 'Go'];
    $languages = isset($_POST['languages']) && is_array($_POST['languages']) ? $_POST['languages'] : [];

    try {
        $db->beginTransaction();

        // Обновление основной информации
        $stmt = $db->prepare("UPDATE applications SET
            fio = ?, phone = ?, email = ?, birthdate = ?,
            gender = ?, bio = ?, contract_agreed = ?
            WHERE id = ?");

        $stmt->execute([
            $_POST['fio'],
            $_POST['phone'],
            $_POST['email'],
            $_POST['birthdate'],
            $_POST['gender'],
            $_POST['bio'],
            isset($_POST['contract']) ? 1 : 0,
            $userId
        ]);

        // Обновление языков
        $db->prepare("DELETE FROM application_languages WHERE application_id = ?")
           ->execute([$userId]);

        $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id)
                            SELECT ?, id FROM programming_languages WHERE name = ?");
        foreach ($languages as $lang) {
            $stmt->execute([$userId, $lang]);
        }

        $db->commit();
        header("Location: admin.php?updated=1");
        exit();
    } catch (PDOException $e) {
        $db->rollBack();
        die("Ошибка при обновлении: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование пользователя</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="tel"],
        input[type="email"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        textarea {
            min-height: 100px;
        }
        select[multiple] {
            height: auto;
            min-height: 150px;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #4361ee;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #3a56d4;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Редактирование пользователя #<?= $userId ?></h1>
        <a href="admin.php">Назад к списку</a>

        <form method="POST">
            <div class="form-group">
                <label for="fio">ФИО:</label>
                <input type="text" id="fio" name="fio" value="<?= htmlspecialchars($user['fio']) ?>" required>
            </div>

            <div class="form-group">
                <label for="phone">Телефон:</label>
                <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>

            <div class="form-group">
                <label for="birthdate">Дата рождения:</label>
                <input type="date" id="birthdate" name="birthdate" value="<?= $user['birthdate'] ?>" required>
            </div>

            <div class="form-group">
                <label>Пол:</label>
                <div>
                    <input type="radio" id="male" name="gender" value="male" <?= $user['gender'] == 'male' ? 'checked' : '' ?>>
                    <label for="male" style="display: inline;">Мужской</label>
                </div>
                <div>
                    <input type="radio" id="female" name="gender" value="female" <?= $user['gender'] == 'female' ? 'checked' : '' ?>>
                    <label for="female" style="display: inline;">Женский</label>
                </div>
            </div>

            <div class="form-group">
                <label for="languages">Языки программирования:</label>
                <select id="languages" name="languages[]" multiple>
                    <?php
                    $allLanguages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskel', 'Clojure', 'Prolog', 'Scala', 'Go'];
                    foreach ($allLanguages as $lang): ?>
                        <option value="<?= $lang ?>" <?= in_array($lang, $userLanguages) ? 'selected' : '' ?>>
                            <?= $lang ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="bio">Биография:</label>
                <textarea id="bio" name="bio"><?= htmlspecialchars($user['bio']) ?></textarea>
            </div>

            <div class="form-group">
                <input type="checkbox" id="contract" name="contract" value="1" <?= $user['contract_agreed'] ? 'checked' : '' ?>>
                <label for="contract" style="display: inline;">Согласие на обработку данных</label>
            </div>

            <button type="submit" class="btn">Сохранить изменения</button>
        </form>
    </div>
</body>
</html>