<?php
header('Content-Type: text/html; charset=UTF-8');
session_start(); // Инициализация механизма сессий

// 1. Подключение к БД
try {
    $pdo = new PDO('mysql:host=localhost;dbname=u82815', 'u82815', '3583398', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die('Ошибка подключения к БД: ' . $e->getMessage());
}

// Вспомогательная функция для проверки прав администратора
function isAdmin($pdo) {
    if (!isset($_SERVER['PHP_AUTH_USER'])) return false;
    $stmt = $pdo->prepare("SELECT password_hash FROM admin_users WHERE username = ?");
    $stmt->execute([$_SERVER['PHP_AUTH_USER']]);
    $admin = $stmt->fetch();
    return $admin && password_verify($_SERVER['PHP_AUTH_PW'], $admin['password_hash']);
}

// ========== ОБРАБОТКА GET-ЗАПРОСА (Отображение формы) ==========
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $messages = array();

    // Обработка выхода из системы
    if (isset($_GET['logout'])) {
        session_destroy();
        header('Location: index.php');
        exit();
    }

    // Проверка режима редактирования для администратора
    if (!empty($_GET['edit_id'])) {
        if (!isAdmin($pdo)) {
            header('HTTP/1.1 401 Unauthorized');
            header('WWW-Authenticate: Basic realm="Admin Page"');
            exit('У вас нет прав администратора.');
        }
        $_SESSION['admin_edit_id'] = $_GET['edit_id'];
    }

    // Вывод сообщения об успехе
    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', 100000);
        $messages[] = '<div class="success">Данные успешно сохранены.</div>';
        if (!empty($_SESSION['generated_pass'])) {
            $messages[] = '<div class="success">Ваш логин: <strong>' . htmlspecialchars($_SESSION['generated_login']) . '</strong><br>' .
                          'Ваш пароль: <strong>' . htmlspecialchars($_SESSION['generated_pass']) . '</strong> (сохраните его!)</div>';
            unset($_SESSION['generated_pass']);
            unset($_SESSION['generated_login']);
        }
    }

    // Сбор ошибок из кук
    $errors = array();
    $fields = array('fio', 'phone', 'email', 'birthdate', 'gender', 'languages', 'bio', 'contract_agreed');
    foreach ($fields as $field) {
        $errors[$field] = !empty($_COOKIE[$field . '_error']);
        if ($errors[$field]) {
            setcookie($field . '_error', '', 100000);
            $messages[] = '<div class="error">' . ($_COOKIE[$field . '_error_msg'] ?? 'Ошибка в поле') . '</div>';
            setcookie($field . '_error_msg', '', 100000);
        }
    }

    // Заполнение значений полей
    $values = array();
    $target_id = $_SESSION['admin_edit_id'] ?? ($_SESSION['uid'] ?? null);

    if ($target_id) {
        // Загрузка данных из БД для авторизованного пользователя или админ-правки
        $stmt = $pdo->prepare("SELECT * FROM application WHERE id = ?");
        $stmt->execute([$target_id]);
        $row = $stmt->fetch();
        if ($row) {
            $values['fio'] = $row['fio'];
            $values['phone'] = $row['phone_number'];
            $values['email'] = $row['email'];
            $values['birthdate'] = $row['birthdate'];
            $values['gender'] = $row['sex'];
            $values['bio'] = $row['biography'];
            $values['contract_agreed'] = 1;
            
            $stmt = $pdo->prepare("SELECT l.name FROM programming_languages l JOIN application_languages rl ON l.id = rl.id WHERE rl.id = ?");
            $stmt->execute([$target_id]);
            $values['languages'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    } else {
        // Значения из кук для неавторизованных пользователей
        foreach ($fields as $f) $values[$f] = $_COOKIE[$f.'_value'] ?? '';
        $values['languages'] = isset($_COOKIE['languages_value']) ? unserialize($_COOKIE['languages_value']) : array();
    }

    include('form.php');
} 
// ========== ОБРАБОТКА POST-ЗАПРОСА (Сохранение данных) ==========
else {
    // 1. Обработка авторизации (если нажата кнопка входа)
    if (isset($_POST['auth_submit'])) {
        $stmt = $pdo->prepare("SELECT id, password_hash FROM application WHERE login = ?");
        $stmt->execute([$_POST['auth_login']]);
        $user = $stmt->fetch();
        if ($user && password_verify($_POST['auth_pass'], $user['pass'])) {
            $_SESSION['uid'] = $user['id'];
            header('Location: index.php');
        } else {
            setcookie('auth_error', '1', time() + 3600);
            header('Location: index.php');
        }
        exit();
    }

    // 2. Валидация данных формы
    $errors = false;
    if (empty($_POST['fio']) || !preg_match('/^[a-zA-Zа-яА-ЯёЁ\s]{1,150}$/u', $_POST['fio'])) {
        setcookie('fio_error', '1', time() + 86400);
        setcookie('fio_error_msg', 'Некорректное ФИО', time() + 86400);
        $errors = true;
    }
    setcookie('fio_value', $_POST['fio'], time() + 31536000);
    // ... (аналогичная валидация для остальных полей: phone, email, birth_date, gender, languages, bio, contract)

    if ($errors) {
        $query = !empty($_SESSION['admin_edit_id']) ? '?edit_id='.$_SESSION['admin_edit_id'] : '';
        header('Location: index.php' . $query);
        exit();
    }

    // 3. Сохранение/Обновление в БД
    $edit_id = $_SESSION['admin_edit_id'] ?? ($_SESSION['uid'] ?? null);

    if ($edit_id) {
        // ОБНОВЛЕНИЕ существующей записи
        $stmt = $pdo->prepare("UPDATE applications SET fio=?, phone=?, email=?, birthdate=?, gender=?, biography=? WHERE id=?");
        $stmt->execute([$_POST['fio'], $_POST['phone'], $_POST['email'], $_POST['birthdate'], $_POST['gender'], $_POST['bio'], $edit_id]);
        
        $pdo->prepare("DELETE FROM application_languages WHERE id=?")->execute([$edit_id]);
    } else {
        // СОЗДАНИЕ новой записи
        $login = 'user' . rand(1000, 9999);
        $pass = rand(100000, 999999);
        $pass_hash = password_hash($pass, PASSWORD_DEFAULT); // Хеширование пароля
        
        $_SESSION['generated_login'] = $login;
        $_SESSION['generated_pass'] = $pass;

        $stmt = $pdo->prepare("INSERT INTO applications (fio, phone, email, birthdate, gender, biography, login, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['fio'], $_POST['phone'], $_POST['email'], $_POST['birthdate'], $_POST['gender'], $_POST['bio'], $login, $pass_hash]);
        $edit_id = $pdo->lastInsertId();
    }

    // Сохранение языков программирования
    foreach ($_POST['languages'] as $lang) {
        $l_stmt = $pdo->prepare("SELECT id FROM programming_languages WHERE name = ?");
        $l_stmt->execute([$lang]);
        $l_id = $l_stmt->fetchColumn();
        $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)")->execute([$edit_id, $l_id]);
    }

    // Очистка кук данных после успешного сохранения
    setcookie('save', '1');
    if (isset($_SESSION['admin_edit_id'])) {
        unset($_SESSION['admin_edit_id']);
        header('Location: admin.php');
    } else {
        header('Location: index.php');
    }
}
