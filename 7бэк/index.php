
<?php
header('Content-Type: text/html; charset=UTF-8');
session_start(); // Сессии

// 1. Подключение к БД
try {
    $pdo = new PDO('mysql:host=localhost;dbname=u82815', 'u82815', '3583398', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die('Ошибка подключения к БД.');
}

// CSP для защиты XSS
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self';");

// Функция проверки прав администратора
function isAdmin($pdo) {
    if (!isset($_SERVER['PHP_AUTH_USER'])) return false;
    $stmt = $pdo->prepare("SELECT password_hash FROM admin_users WHERE username = ?");
    $stmt->execute([$_SERVER['PHP_AUTH_USER']]);
    $admin = $stmt->fetch();
    return $admin && password_verify($_SERVER['PHP_AUTH_PW'], $admin['password_hash']);
}

// CSRF токен
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ================= GET-запрос =================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $messages = [];

    // Выход из сессии
    if (!empty($_GET['logout'])) {
        session_destroy();
        header('Location: index.php');
        exit();
    }

    // Редактирование администратора
    if (!empty($_GET['edit_id'])) {
        if (!isAdmin($pdo)) {
            header('HTTP/1.1 401 Unauthorized');
            header('WWW-Authenticate: Basic realm="Admin Page"');
            exit('У вас нет прав администратора.');
        }
        $_SESSION['admin_edit_id'] = $_GET['edit_id'];
    }

    // Сообщения об успешном сохранении
    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', time() - 3600);
        $messages[] = '<div class="success">Данные успешно сохранены.</div>';
        if (!empty($_SESSION['generated_pass'])) {
            $messages[] = '<div class="success">Ваш логин: <strong>'
                . htmlspecialchars($_SESSION['generated_login'], ENT_QUOTES, 'UTF-8')
                . '</strong><br>Пароль: <strong>'
                . htmlspecialchars($_SESSION['generated_pass'], ENT_QUOTES, 'UTF-8')
                . '</strong> (сохраните его!)</div>';
            unset($_SESSION['generated_login'], $_SESSION['generated_pass']);
        }
    }

    // Подгрузка значений полей
    $values = [];
    $target_id = $_SESSION['admin_edit_id'] ?? ($_SESSION['uid'] ?? null);

    if ($target_id) {
        $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
        $stmt->execute([$target_id]);
        $row = $stmt->fetch();
        if ($row) {
            foreach(['fio','phone','email','birthdate','gender','bio'] as $f){
                $values[$f] = $row[$f];
            }
            $values['contract'] = 1;
            // Языки
            $stmt2 = $pdo->prepare("
                SELECT l.name FROM programming_languages l
                JOIN application_languages al ON l.id = al.language_id
                WHERE al.application_id = ?
            ");
            $stmt2->execute([$target_id]);
            $values['languages'] = $stmt2->fetchAll(PDO::FETCH_COLUMN);
        }
    } else {
        // Значения из кук
        foreach(['fio','phone','email','birthdate','gender','bio','contract'] as $f){
            $values[$f] = $_COOKIE[$f.'_value'] ?? '';
        }
        $values['languages'] = isset($_COOKIE['languages_value']) ? unserialize($_COOKIE['languages_value']) : [];
    }

    include('form.php');
    exit();
}

// ================= POST-запрос =================
else {
    // Проверка CSRF токена
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Недействительный CSRF-токен!");
    }

    $errors = false;

    // Валидация ФИО
    $fio = trim($_POST['fio'] ?? '');
    if ($fio === '' || !preg_match('/^[a-zA-Zа-яА-ЯёЁ\s]{1,150}$/u', $fio)) {
        setcookie('fio_error', '1', time()+86400);
        setcookie('fio_error_msg', 'Некорректное ФИО', time()+86400);
        $errors = true;
    }
    setcookie('fio_value', $fio, time()+31536000);

    // Можно добавить аналогично phone, email, birthdate, gender, bio, contract

    if ($errors) {
        $query = !empty($_SESSION['admin_edit_id']) ? '?edit_id='.$_SESSION['admin_edit_id'] : '';
        header('Location: index.php'.$query);
        exit();
    }

    // Определяем редактирование или новая запись
    $edit_id = $_SESSION['admin_edit_id'] ?? ($_SESSION['uid'] ?? null);

    if ($edit_id) {
        $stmt = $pdo->prepare("
            UPDATE applications
            SET fio=?, phone=?, email=?, birthdate=?, gender=?, biography=?
            WHERE id=?
        ");
        $stmt->execute([
            $_POST['fio'], $_POST['phone'], $_POST['email'],
            $_POST['birthdate'], $_POST['gender'], $_POST['bio'], $edit_id
        ]);
        $pdo->prepare("DELETE FROM application_languages WHERE application_id=?")->execute([$edit_id]);
    } else {
        // Генерация логина и пароля для нового пользователя
        $login = 'user'.rand(1000,9999);
        $pass = rand(100000,999999);
        $pass_hash = password_hash($pass, PASSWORD_DEFAULT);

        $_SESSION['generated_login'] = $login;
        $_SESSION['generated_pass'] = $pass;

        $stmt = $pdo->prepare("
            INSERT INTO applications (fio, phone, email, birthdate, gender, biography, login, password_hash)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['fio'], $_POST['phone'], $_POST['email'],
            $_POST['birthdate'], $_POST['gender'], $_POST['bio'], $login, $pass_hash
        ]);
        $edit_id = $pdo->lastInsertId();
    }

    // Сохранение языков
    foreach ($_POST['languages'] ?? [] as $lang) {
        $stmt2 = $pdo->prepare("SELECT id FROM programming_languages WHERE name=?");
        $stmt2->execute([$lang]);
        $lang_id = $stmt2->fetchColumn();
        if ($lang_id) {
            $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)")->execute([$edit_id, $lang_id]);
        }
    }

    setcookie('save', '1');
    unset($_SESSION['admin_edit_id']);

    header('Location: index.php');
    exit();
}
