<?php
session_start();

// Генерация CSRF токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Массив сообщений и ошибок
$messages = $errors = [];
$values = [];

// Если пользователь отправил форму
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['save_form'])) {
   
    // Проверка CSRF токена
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('<div class="error">Недействительный CSRF токен!</div>');
    }
   
    // Сбор данных и базовая валидация
    $values['fio'] = trim($_POST['fio'] ?? '');
    $values['phone'] = trim($_POST['phone'] ?? '');
    $values['email'] = trim($_POST['email'] ?? '');
    $values['birth_date'] = trim($_POST['birth_date'] ?? '');
    $values['gender'] = $_POST['gender'] ?? '';
    $values['languages'] = $_POST['languages'] ?? [];
    $values['bio'] = trim($_POST['bio'] ?? '');
    $values['contract'] = !empty($_POST['contract']) ? 1 : 0;

    if ($values['fio'] === '') $errors['fio'] = true;
    if ($values['email'] === '') $errors['email'] = true;
    if ($values['gender'] === '') $errors['gender'] = true;

    if (empty($errors)) {
        // Здесь вставка в БД через PDO (безопасно)
        // $stmt = $pdo->prepare("INSERT INTO applications (...) VALUES (...)");
        $messages[] = '<div class="success">Данные успешно сохранены!</div>';
    } else {
        $messages[] = '<div class="error">Пожалуйста, исправьте ошибки в форме.</div>';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Форма заявки</title>
    <style>
        /* Стили оставляем как в твоем оригинале */
    </style>
</head>
<body>
<h1>Форма заявки</h1>

<?php if (!empty($messages)): ?>
    <div id="messages">
        <?php foreach ($messages as $message): ?>
            <?= $message ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="POST" action="form.php">
    <input type="hidden" name="save_form" value="1">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <label>ФИО:
        <input type="text" name="fio"
               value="<?= htmlspecialchars($values['fio'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
               class="<?= !empty($errors['fio']) ? 'error-field' : '' ?>">
    </label>

    <label>Телефон:
        <input type="tel" name="phone"
               value="<?= htmlspecialchars($values['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
               class="<?= !empty($errors['phone']) ? 'error-field' : '' ?>">
    </label>

    <label>Email:
        <input type="email" name="email"
               value="<?= htmlspecialchars($values['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
               class="<?= !empty($errors['email']) ? 'error-field' : '' ?>">
    </label>

    <label>Дата рождения:
        <input type="date" name="birth_date"
               value="<?= htmlspecialchars($values['birth_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
               class="<?= !empty($errors['birth_date']) ? 'error-field' : '' ?>">
    </label>

    <label>Пол:</label>
    <div class="radio-group">
        <input type="radio" name="gender" value="male" id="male"
            <?= ($values['gender'] ?? '') == 'male' ? 'checked' : '' ?>>
        <label for="male">Мужской</label>

        <input type="radio" name="gender" value="female" id="female"
            <?= ($values['gender'] ?? '') == 'female' ? 'checked' : '' ?>>
        <label for="female">Женский</label>
    </div>

    <label>Языки программирования:</label>
    <select name="languages[]" multiple>
        <?php
        $langs = ['Pascal','C','C++','JavaScript','PHP','Python','Java','Haskell','Clojure','Prolog','Scala','Go'];
        $selected = $values['languages'] ?? [];
        foreach ($langs as $lang):
            $sel = in_array($lang, $selected) ? 'selected' : '';
            echo "<option value=\"$lang\" $sel>".htmlspecialchars($lang, ENT_QUOTES, 'UTF-8')."</option>";
        endforeach;
        ?>
    </select>

    <label>Биография:
        <textarea name="bio"><?= htmlspecialchars($values['bio'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
    </label>

    <div class="checkbox-group">
        <input type="checkbox" name="contract" value="1" <?= !empty($values['contract']) ? 'checked' : '' ?>>
        <label>С контрактом ознакомлен(а)</label>
    </div>

    <button type="submit">Сохранить</button>
</form>
</body>
</html>
