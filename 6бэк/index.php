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

// Функция для получения значения поля (приоритет: БД -> COOKIE -> пустая строка)
function getFieldValue($fieldName, $userData, $dbFieldName = null) {
    $dbField = $dbFieldName ?: $fieldName;
    
    // 1. Сначала проверяем COOKIE (новые данные из формы с ошибкой)
    if (isset($_COOKIE["form_$fieldName"])) {
        return htmlspecialchars($_COOKIE["form_$fieldName"]);
    }
    
    // 2. Затем данные из БД (уже сохраненные)
    if ($userData && isset($userData[$dbField]) && $userData[$dbField] !== null) {
        return htmlspecialchars($userData[$dbField]);
    }
    
    // 3. Иначе пустая строка
    return '';
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
    <title> Задание 5 </title>
    <meta charset="utf-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="basecss.css" type="text/css"/>

</head>
<body>
    <div class="highlighted container">
            <header>
                <h3> Анкета </h3>
                <a href="logout.php" class="btn btn-primary">
                    Выйти
                </a>
            </header>
        <?php if (isset($_COOKIE['error_db'])): ?>
            <div class="alert error">
                <?= htmlspecialchars($_COOKIE['error_db']) ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="fio" class="form-label">ФИО:</label> 
                <input class="form-control" id="fio" aria-describedby="format" name="fio" type="text"
                    value="<?= getFieldValue('fio', $userData, 'fio') ?>"
                    class="<?= isset($_COOKIE['error_fio']) ? 'error-field' : '' ?>">
                <?php if (isset($_COOKIE['error_fio'])): ?>
                    <div class="error"><?= htmlspecialchars($_COOKIE['error_fio']) ?></div>
                <?php endif; ?>
            </div>  

            <div class="mb-3">
                <label for="phone" class="form-label">Номер телефона:</label>
                <input class="form-control" id="phone" aria-describedby="format" name="phone" type="tel"
                    value="<?= getFieldValue('phone', $userData, 'phone') ?>"
                    class="<?= isset($_COOKIE['error_phone']) ? 'error-field' : '' ?>">
                <?php if (isset($_COOKIE['error_phone'])): ?>
                    <div class="error"><?= htmlspecialchars($_COOKIE['error_phone']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input class="form-control" id="email" name="email" type="email" 
                    value="<?= getFieldValue('email', $userData, 'email') ?>"
                    class="<?= isset($_COOKIE['error_email']) ? 'error-field' : '' ?>">
                <?php if (isset($_COOKIE['error_email'])): ?>
                    <div class="error"><?= htmlspecialchars($_COOKIE['error_email']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="birthdate">Дата рождения:</label>
                <input name="birthdate" type="date" class="form-control" id="birthdate"
                    value="<?= getFieldValue('birthdate', $userData, 'birthdate') ?>"
                    class="<?= isset($_COOKIE['error_birthdate']) ? 'error-field' : '' ?>">
                <?php if (isset($_COOKIE['error_birthdate'])): ?>
                    <div class="error"><?= htmlspecialchars($_COOKIE['error_birthdate']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">Пол:
                <div class="form-check">
                    <?php $genderValue = getFieldValue('gender', $userData, 'gender'); ?>
                    <input class="form-check-input" type="radio" name="gender" id="male" value="male" <?= ($genderValue == 'male') ? 'checked' : '' ?>>
                    <label class="form-check-label" for="male">Мужской</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="gender" id="female" value="female" <?= ($genderValue == 'female') ? 'checked' : '' ?>>
                    <label class="form-check-label" for="female">Женский</label>  
                </div>
                <?php if (isset($_COOKIE['error_gender'])): ?>
                    <div class="error"><?= htmlspecialchars($_COOKIE['error_gender']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="languages" class="form-label">Любимый язык программирования:</label>
                <select id="languages" name="languages[]" multiple size="5" class="form-select"
                    class="<?= isset($_COOKIE['error_languages']) ? 'error-field' : '' ?>">
                    <?php
                        $options = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskel', 'Clojure', 'Prolog', 'Scala', 'Go'];
                        
                        // Приоритет: COOKIE > БД
                        $selectedLangs = [];
                        if (isset($_COOKIE['form_languages']) && !empty($_COOKIE['form_languages'])) {
                            $selectedLangs = explode(',', $_COOKIE['form_languages']);
                        } elseif ($userLanguages && !empty($userLanguages)) {
                            $selectedLangs = $userLanguages;
                        }
                        
                        foreach ($options as $lang): ?>
                            <option value="<?= $lang ?>" <?= in_array($lang, $selectedLangs) ? 'selected' : '' ?>>
                                <?= $lang ?>
                            </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($_COOKIE['error_languages'])): ?>
                    <div class="error"><?= htmlspecialchars($_COOKIE['error_languages']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">Биография:
                <div class="form-floating">
                    <textarea class="form-control" id="bio" name="bio" class="<?= isset($_COOKIE['error_bio']) ? 'error-field' : '' ?>"><?= 
        getFieldValue('bio', $userData, 'bio') 
    ?></textarea>
                    <?php if (isset($_COOKIE['error_bio'])): ?>
                        <div class="error"><?= htmlspecialchars($_COOKIE['error_bio']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mb-3">
                <?php $contractValue = getFieldValue('contract', $userData, 'contract_agreed'); ?>
                <input type="checkbox" class="form-check-input" name="contract" id="contract"value="1"
                       <?= ($contractValue == '1' || $contractValue == 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="contract">С контрактом ознакомлен(-а)</label>
                <?php if (isset($_COOKIE['error_contract'])): ?>
                    <div class="error"><?= htmlspecialchars($_COOKIE['error_contract']) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">Сохранить</button>
            <?php if (isset($_GET['success'])): ?>
                <div class="success-mes">Спасибо, данные сохранены.</div>
            <?php endif; ?>
        </form> 
    </div>
</body>
</html>
