<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Форма заявки</title>
    <style>
        @import url(https://fonts.googleapis.com/css?family=Inter:100,200,300,regular,500,600,700,800,900);

        body { 
            font-family: "Inter", sans-serif; 
            max-width: 1200px; 
            margin: 50px auto; 
            padding: 20px;
            background: #f5f5f5;
        }
        h1 { color: #333; }
        label { 
            display: block; 
            margin: 15px 0 5px; 
            font-weight: bold;
        }
        input[type="text"], input[type="password"],
        input[type="tel"],
        input[type="email"],
        input[type="date"],
        select,
        textarea { 
            width: 100%; 
            padding: 10px; 
            margin-bottom: 10px; 
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea { height: 100px; resize: vertical; }
        select[multiple] { height: 150px; }
        button { 
            background: #4CAF50; 
            color: white; 
            padding: 12px 24px; 
            border: none; 
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        button:hover { background: #45a049; }
        .error { 
            color: #d32f2f; 
            background: #ffebee; 
            padding: 10px; 
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid #d32f2f;
        }
        .success { 
            color: #388e3c; 
            background: #e8f5e9; 
            padding: 10px; 
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid #388e3c;
        }
        .error-field { 
            border: 2px solid #d32f2f !important;
            background: #ffebee;
        }
        .radio-group, .checkbox-group { 
            margin: 10px 0; 
            padding: 10px;
            background: white;
            border-radius: 4px;
        }
        .radio-group label, .checkbox-group label {
            display: inline;
            font-weight: normal;
            margin-right: 15px;
        }
        #messages { margin-bottom: 20px; }
        
        /* Стили для блока авторизации */
        .auth-block {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #bbdefb;
        }
        .auth-flex {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .auth-flex input {
            margin: 0;
            width: 200px;
        }
        .auth-flex button {
            margin: 0;
            background: #1976d2;
            padding: 10px 20px;
        }
        .auth-flex button:hover { background: #1565c0; }
        
        .user-block {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #c8e6c9;
        }
        .logout-btn {
            background: #d32f2f;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 4px;
        }
        .logout-btn:hover { background: #b71c1c; }
    </style>
</head>
<body>
    <h1>Форма заявки</h1>
    
    <!-- Блок авторизации -->
    <?php if (empty($_SESSION['uid'])): ?>
        <div class="auth-block">
            <h3 style="margin-top:0;">Вход для редактирования данных</h3>
            <form method="POST" action="index.php" style="margin:0;">
                <input type="hidden" name="auth_login" value="1">
                <div class="auth-flex">
                    <input type="text" name="login" placeholder="Логин" required>
                    <input type="password" name="pass" placeholder="Пароль" required>
                    <button type="submit">Войти</button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="user-block">
            <div>
                Вы авторизованы как <b><?= htmlspecialchars($_SESSION['login']) ?></b>. 
                Вы можете редактировать свои данные.
            </div>
            <a href="index.php?logout=1" class="logout-btn">Выйти</a>
        </div>
    <?php endif; ?>

    <?php if (!empty($messages)): ?>
    <div id="messages">
        <?php foreach ($messages as $message): ?>
            <?= $message ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <form method="POST" action="index.php">
        <!-- Скрытое поле для идентификации основной формы -->
        <input type="hidden" name="save_form" value="1">
        
        <!-- ФИО -->
        <label>ФИО:
            <input type="text" 
                   name="fio" 
                   value="<?= htmlspecialchars($values['fio'] ?? '') ?>"
                   class="<?= !empty($errors['fio']) ? 'error-field' : '' ?>">
        </label>
        
        <!-- Телефон -->
        <label>Телефон:
            <input type="tel" 
                   name="phone" 
                   value="<?= htmlspecialchars($values['phone'] ?? '') ?>"
                   class="<?= !empty($errors['phone']) ? 'error-field' : '' ?>">
        </label>
        
        <!-- Email -->
        <label>E-mail:
            <input type="email" 
                   name="email" 
                   value="<?= htmlspecialchars($values['email'] ?? '') ?>"
                   class="<?= !empty($errors['email']) ? 'error-field' : '' ?>">
        </label>
        
        <!-- Дата рождения -->
        <label>Дата рождения:
            <input type="date" 
                   name="birth_date" 
                   value="<?= htmlspecialchars($values['birth_date'] ?? '') ?>"
                   class="<?= !empty($errors['birth_date']) ? 'error-field' : '' ?>">
        </label>
        
        <!-- Пол -->
        <label>Пол:</label>
        <div class="radio-group">
            <input type="radio" 
                   name="gender" 
                   value="male"
                   id="male"
                   <?= ($values['gender'] ?? '') == 'male' ? 'checked' : '' ?>
                   class="<?= !empty($errors['gender']) ? 'error-field' : '' ?>">
            <label for="male">Мужской</label>
            
            <input type="radio" 
                   name="gender" 
                   value="female"
                   id="female"
                   <?= ($values['gender'] ?? '') == 'female' ? 'checked' : '' ?>
                   class="<?= !empty($errors['gender']) ? 'error-field' : '' ?>">
            <label for="female">Женский</label>
        </div>
        
        <!-- Языки программирования -->
        <label>Любимый язык программирования (выберите несколько):</label>
        <select name="languages[]" multiple 
                class="<?= !empty($errors['languages']) ? 'error-field' : '' ?>">
            <?php
            $langs = array('Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go');
            $selected_langs = $values['languages'] ?? [];
            foreach ($langs as $lang):
                $selected = in_array($lang, $selected_langs) ? 'selected' : '';
                print("<option value=\"$lang\" $selected>$lang</option>");
            endforeach;
            ?>
        </select>
        
        <!-- Биография -->
        <label>Биография:
            <textarea name="bio" 
                      class="<?= !empty($errors['bio']) ? 'error-field' : '' ?>"><?= htmlspecialchars($values['bio'] ?? '') ?></textarea>
        </label>
        
        <!-- Контракт -->
        <div class="checkbox-group">
            <input type="checkbox" 
                   name="contract" 
                   value="1"
                   id="contract"
                   <?= !empty($values['contract']) ? 'checked' : '' ?>
                   class="<?= !empty($errors['contract']) ? 'error-field' : '' ?>">
            <label for="contract">С контрактом ознакомлен(а)</label>
        </div>
        
        <button type="submit">Сохранить</button>
    </form>
</body>
</html>
