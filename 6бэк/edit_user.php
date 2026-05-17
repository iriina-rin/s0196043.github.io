<?php
// Проверка авторизации
require_once 'admin_auth.php';

// Подключение к БД
$db = new PDO("mysql:host=localhost;dbname=u82815", 'u82815', '3583398', [
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <title>Редактирование пользователя</title>
    <style>
        .container{
            width: 75%;
            padding: 20px;
            margin: 0 auto;
            margin-top: 50px;
            margin-bottom: 50px;
            border-radius: 8px;
        }
        .highlighted {
            background-color: #FFEBEE;
        } 
        .form-group {
            margin: 10px;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        label {
            display: block;
            margin-bottom: 5px;
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
        a {
            text-decoration: none;
        }
        .admin-btn{
            margin-right: 10px;
        }
         header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        } 
        .admin-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s;
        }
    </style>
</head>
<body>
    <div class="container highlighted">
        <header>
            <h2>Редактирование пользователя #<?= $userId ?></h2>
             <a href="admin.php" class="admin-btn">
                <button type="submit" class="btn btn-primary">Назад к списку</button>
            </a>
        </header>
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

            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
        </form>
    </div>
</body>
</html>
