<?php
session_start();

$isFirstVisit = !isset($_COOKIE['form_initialized']);

if ($isFirstVisit) {
    // Устанавливаем куку, что форма уже посещалась
    setcookie('form_initialized', '1', time() + 3600 * 24 * 30, '/'); // на 30 дней

    // Очищаем все возможные ошибки
    foreach ($_COOKIE as $name => $value) {
        if (strpos($name, 'error_') === 0 || strpos($name, 'form_') === 0) {
            setcookie($name, '', time() - 3600, '/');
        }
    }
}
// Редирект если не авторизован
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Функции для работы с cookies
function setFormCookie($name, $value, $expire = 0) {
    setcookie("form_$name", $value, $expire, '/');
}

function setErrorCookie($name, $message) {
    setcookie("error_$name", $message, 0, '/');
}

// Подключение к БД
$db = new PDO("mysql:host=localhost;dbname=u82815", 'u82815', '3583398', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);
// Очистка ошибок при первом заходе
if (!isset($_GET['form_submitted'])) {
    foreach ($_COOKIE as $name => $value) {
        if (strpos($name, 'error_') === 0) {
            setcookie($name, '', time() - 3600, '/');
        }
    }
}
// Загрузка данных пользователя
$stmt = $db->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userData = $stmt->fetch();

// Загрузка выбранных языков
$langStmt = $db->prepare("SELECT pl.name FROM application_languages al
                         JOIN programming_languages pl ON al.language_id = pl.id
                         WHERE al.application_id = ?");
$langStmt->execute([$_SESSION['user_id']]);
$userLanguages = $langStmt->fetchAll(PDO::FETCH_COLUMN);

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = [];
    $allowedLanguages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskel', 'Clojure', 'Prolog', 'Scala', 'Go'];

    // Валидация ФИО
    if (empty($_POST['fio'] ?? '')) {
        $errors['fio'] = 'Заполните ФИО';
        setErrorCookie('fio', $errors['fio']);
    } elseif (!preg_match('/^[а-яА-ЯёЁa-zA-Z\s]+$/u', $_POST['fio'])) {
        $errors['fio'] = 'Допустимы только буквы и пробелы';
        setErrorCookie('fio', $errors['fio']);
    }
    setFormCookie('fio', $_POST['fio'] ?? '');

    // Валидация телефона
    if (empty($_POST['phone'] ?? '')) {
        $errors['phone'] = 'Заполните телефон';
        setErrorCookie('phone', $errors['phone']);
    } elseif (!preg_match('/^\+?\d{10,15}$/', $_POST['phone'])) {
        $errors['phone'] = 'От 10 до 15 цифр, можно с +';
        setErrorCookie('phone', $errors['phone']);
    }
    setFormCookie('phone', $_POST['phone'] ?? '');

    // Валидация email
    if (empty($_POST['email'] ?? '')) {
        $errors['email'] = 'Заполните email';
        setErrorCookie('email', $errors['email']);
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Некорректный email';
        setErrorCookie('email', $errors['email']);
    }
    setFormCookie('email', $_POST['email'] ?? '');

    // Валидация даты рождения
    if (empty($_POST['birthdate'] ?? '')) {
        $errors['birthdate'] = 'Укажите дату рождения';
        setErrorCookie('birthdate', $errors['birthdate']);
    }
    setFormCookie('birthdate', $_POST['birthdate'] ?? '');

    // Валидация пола
    if (empty($_POST['gender'] ?? '')) {
        $errors['gender'] = 'Укажите пол';
        setErrorCookie('gender', $errors['gender']);
    }
    setFormCookie('gender', $_POST['gender'] ?? '');

    // Валидация языков программирования
    $languages = isset($_POST['languages']) && is_array($_POST['languages']) ? $_POST['languages'] : [];
    if (empty($languages)) {
        $errors['languages'] = 'Выберите хотя бы один язык';
        setErrorCookie('languages', $errors['languages']);
    } else {
        foreach ($languages as $lang) {
            if (!in_array($lang, $allowedLanguages)) {
                $errors['languages'] = 'Выбран недопустимый язык';
                setErrorCookie('languages', $errors['languages']);
                break;
            }
        }
    }
    setFormCookie('languages', !empty($languages) ? implode(',', $languages) : '');

    // Валидация биографии
    if (empty($_POST['bio'] ?? '')) {
        $errors['bio'] = 'Заполните биографию';
        setErrorCookie('bio', $errors['bio']);
    }
    setFormCookie('bio', $_POST['bio'] ?? '');

    // Валидация согласия
    if (empty($_POST['contract'] ?? '')) {
        $errors['contract'] = 'Необходимо согласие';
        setErrorCookie('contract', $errors['contract']);
    }

    // Если есть ошибки - редирект
    if (!empty($errors)) {
    header('Location: index.php?form_submitted=1');
    exit();
}

    // Если ошибок нет - сохраняем в БД
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
            $_SESSION['user_id']
        ]);

        // Обновление языков
        $db->prepare("DELETE FROM application_languages WHERE application_id = ?")
           ->execute([$_SESSION['user_id']]);

        $stmt = $db->prepare("INSERT INTO application_languages (application_id, language_id)
                            SELECT ?, id FROM programming_languages WHERE name = ?");
        foreach ($languages as $lang) {
            $stmt->execute([$_SESSION['user_id'], $lang]);
        }

        $db->commit();

        // Очистка куков после успешного сохранения
        foreach ($_COOKIE as $name => $value) {
            if (strpos($name, 'form_') === 0 || strpos($name, 'error_') === 0) {
                setcookie($name, '', time() - 3600, '/');
            }
        }

        header('Location: index.php?success=1');
        exit();

    } catch (PDOException $e) {
        $db->rollBack();
        setErrorCookie('db', 'Ошибка сохранения: '.$e->getMessage());
        header('Location: index.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Анкета</title>
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
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
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

        .alert.error {
            background-color: rgba(247, 37, 133, 0.2);
            border-left: 4px solid var(--danger);
            color: #a11a56;
        }

        form {
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input[type="text"],
        .form-group input[type="tel"],
        .form-group input[type="email"],
        .form-group input[type="date"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        .error-field {
            border-color: var(--danger) !important;
        }

        .error {
            color: var(--danger);
            font-size: 14px;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .radio-group {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .radio-group input[type="radio"] {
            margin-right: 10px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
        }

        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
        }

        select[multiple] {
            height: auto;
            min-height: 120px;
            padding: 8px !important;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .submit-btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            margin-top: 10px;
        }

        .submit-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            form {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-user-edit"></i> Анкета</h1>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Выйти
            </a>
        </header>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i> Данные успешно сохранены!
            </div>
        <?php endif; ?>

        <?php if (isset($_COOKIE['error_db'])): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_COOKIE['error_db']) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <!-- ФИО -->
<div class="form-group">
    <label for="fio">ФИО:</label>
    <input type="text" id="fio" name="fio"
           value="<?= isset($_COOKIE['form_fio']) ? htmlspecialchars($_COOKIE['form_fio']) : '' ?>"
           class="<?= isset($_COOKIE['error_fio']) ? 'error-field' : '' ?>">
    <?php if (isset($_COOKIE['error_fio'])): ?>
        <div class="error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_COOKIE['error_fio']) ?>
        </div>
    <?php endif; ?>
</div>

<!-- Телефон -->
<div class="form-group">
    <label for="phone">Телефон:</label>
    <input type="tel" id="phone" name="phone"
           value="<?= isset($_COOKIE['form_phone']) ? htmlspecialchars($_COOKIE['form_phone']) : '' ?>"
           class="<?= isset($_COOKIE['error_phone']) ? 'error-field' : '' ?>">
    <?php if (isset($_COOKIE['error_phone'])): ?>
        <div class="error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_COOKIE['error_phone']) ?>
        </div>
    <?php endif; ?>
</div>

<!-- Email -->
<div class="form-group">
    <label for="email">Email:</label>
    <input type="email" id="email" name="email"
           value="<?= isset($_COOKIE['form_email']) ? htmlspecialchars($_COOKIE['form_email']) : '' ?>"
           class="<?= isset($_COOKIE['error_email']) ? 'error-field' : '' ?>">
    <?php if (isset($_COOKIE['error_email'])): ?>
        <div class="error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_COOKIE['error_email']) ?>
        </div>
    <?php endif; ?>
</div>

<!-- Дата рождения -->
<div class="form-group">
    <label for="birthdate">Дата рождения:</label>
    <input type="date" id="birthdate" name="birthdate"
           value="<?= isset($_COOKIE['form_birthdate']) ? htmlspecialchars($_COOKIE['form_birthdate']) : '' ?>"
           class="<?= isset($_COOKIE['error_birthdate']) ? 'error-field' : '' ?>">
    <?php if (isset($_COOKIE['error_birthdate'])): ?>
        <div class="error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_COOKIE['error_birthdate']) ?>
        </div>
    <?php endif; ?>
</div>

<!-- Пол -->
<div class="form-group">
    <label>Пол:</label>
    <div class="radio-group">
        <input type="radio" id="male" name="gender" value="male"
               <?= (isset($_COOKIE['form_gender']) && $_COOKIE['form_gender'] == 'male') ? 'checked' : '' ?>>
        <label for="male">Мужской</label>
    </div>
    <div class="radio-group">
        <input type="radio" id="female" name="gender" value="female"
               <?= (isset($_COOKIE['form_gender']) && $_COOKIE['form_gender'] == 'female') ? 'checked' : '' ?>>
        <label for="female">Женский</label>
    </div>
    <?php if (isset($_COOKIE['error_gender'])): ?>
        <div class="error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_COOKIE['error_gender']) ?>
        </div>
    <?php endif; ?>
</div>

<!-- Языки программирования -->
<div class="form-group">
    <label for="languages">Любимые языки программирования:</label>
    <select id="languages" name="languages[]" multiple size="5"
            class="<?= isset($_COOKIE['error_languages']) ? 'error-field' : '' ?>">
        <?php
        $options = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskel', 'Clojure', 'Prolog', 'Scala', 'Go'];
        $selectedLangs = isset($_COOKIE['form_languages']) ? explode(',', $_COOKIE['form_languages']) : [];

        foreach ($options as $lang): ?>
            <option value="<?= $lang ?>" <?= in_array($lang, $selectedLangs) ? 'selected' : '' ?>>
                <?= $lang ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php if (isset($_COOKIE['error_languages'])): ?>
        <div class="error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_COOKIE['error_languages']) ?>
        </div>
    <?php endif; ?>
</div>

<!-- Биография -->
<div class="form-group">
    <label for="bio">Биография:</label>
    <textarea id="bio" name="bio"
              class="<?= isset($_COOKIE['error_bio']) ? 'error-field' : '' ?>"><?=
              isset($_COOKIE['form_bio']) ? htmlspecialchars($_COOKIE['form_bio']) : '' ?></textarea>
    <?php if (isset($_COOKIE['error_bio'])): ?>
        <div class="error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_COOKIE['error_bio']) ?>
        </div>
    <?php endif; ?>
</div>

<!-- Согласие -->
<div class="form-group">
    <div class="checkbox-group">
        <input type="checkbox" id="contract" name="contract" value="1"
               <?= (isset($_COOKIE['form_contract']) && $_COOKIE['form_contract'] == '1') ? 'checked' : '' ?>>
        <label for="contract">Согласен на обработку данных</label>
    </div>
    <?php if (isset($_COOKIE['error_contract'])): ?>
        <div class="error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_COOKIE['error_contract']) ?>
        </div>
    <?php endif; ?>
</div>
</div>

            <button type="submit" class="submit-btn">
                <i class="fas fa-save"></i> Сохранить данные
            </button>
        </form>
    </div>
</body>
</html>
