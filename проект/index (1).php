<?php
session_start();

// Подключение к БД
$db = new PDO("mysql:host=localhost;dbname=u68775", 'u68775', '7631071', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
// Обработка выхода
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}
// Получение данных пользователя
$stmt = $db->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userData = $stmt->fetch();

// Получение языков пользователя
$langStmt = $db->prepare("SELECT pl.name FROM application_languages al
                        JOIN programming_languages pl ON al.language_id = pl.id
                        WHERE al.application_id = ?");
$langStmt->execute([$_SESSION['user_id']]);
$userLanguages = $langStmt->fetchAll(PDO::FETCH_COLUMN);

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = [];
    $allowedLanguages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskel', 'Clojure', 'Prolog', 'Scala', 'Go'];
    $languages = isset($_POST['languages']) ? $_POST['languages'] : [];

    // Валидация данных
    if (empty($_POST['fio'])) $errors['fio'] = 'Заполните ФИО';
    if (empty($_POST['phone'])) $errors['phone'] = 'Заполните телефон';
    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Некорректный email';
    if (empty($_POST['birthdate'])) $errors['birthdate'] = 'Укажите дату рождения';
    if (empty($_POST['gender'])) $errors['gender'] = 'Укажите пол';
    if (empty($languages)) $errors['languages'] = 'Выберите хотя бы один язык';
    if (empty($_POST['bio'])) $errors['bio'] = 'Заполните биографию';
    if (!isset($_POST['contract'])) $errors['contract'] = 'Необходимо согласие';

    // Если ошибок нет - сохраняем в БД
    if (empty($errors)) {
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
                if (in_array($lang, $allowedLanguages)) {
                    $stmt->execute([$_SESSION['user_id'], $lang]);
                }
            }

            $db->commit();
            $success = "Данные успешно сохранены!";

            // Обновляем данные после сохранения
            $stmt = $db->prepare("SELECT * FROM applications WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $userData = $stmt->fetch();

            $langStmt = $db->prepare("SELECT pl.name FROM application_languages al
                                    JOIN programming_languages pl ON al.language_id = pl.id
                                    WHERE al.application_id = ?");
            $langStmt->execute([$_SESSION['user_id']]);
            $userLanguages = $langStmt->fetchAll(PDO::FETCH_COLUMN);

        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "Ошибка сохранения: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Главная страница</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Courier, monospace;
            background-color: #ffffff;
            text-decoration: none;
            margin: 0;
            padding: 0;
        }
        a {
            color: #000000;
        }
        .content {
            max-width: 960px;
            display: flex;
            flex-direction: column;
            margin: 0 auto;
            padding: 20px;
        }
        button {
            font-size: 24px;
            text-align: center;
            cursor: pointer;
            outline: none;
            color: #fff;
            background-color: green;
            border: none;
            border-radius: 15px;
            box-shadow: 0 9px #251d3f;
            padding: 10px 20px;
            margin: 10px auto;
            display: block;
        }
        button:hover {
            background-color: #ADFF2F;
        }
        button:focus {
            background-color: #4a4a8a;
            box-shadow: 0 5px #221a36;
            transform: translateY(4px);
        }
        #hiddenBlock {
            display: none;
            list-style-type: none;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .header {
            background-color: green;
            color: #ffffff;
            padding: 10px 0;
        }
        .header-content {
            max-width: 960px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 0 auto;
            padding: 0 20px;
        }
        .logo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: url('logo.jpg') no-repeat center;
            background-size: cover;
        }
        .site-link a {
            font-size: 20px;
            color: rgb(224, 171, 183);
            text-decoration: none;
        }
        .site-link a:hover {
            color: rgb(160, 98, 98);
        }
        .main-menu ul {
            list-style-type: none;
            display: flex;
            gap: 20px;
            margin: 0;
            padding: 0;
        }
        .main-menu a {
            font-size: 20px;
            color: rgb(255, 255, 255);
            text-decoration: none;
        }
        .main-menu a:hover {
            color: rgb(189, 160, 219);
        }
        .data-table {
            border-collapse: collapse;
            border: solid 3px #0c0808;
            width: 100%;
            margin: 20px 0;
        }
        .data-table th, .data-table td {
            text-align: left;
            border: solid 1px #040608;
            padding: 8px;
        }
        .data-table th {
            background-color: green;
            color: #fff;
            border: solid 1px #ffffff;
        }
        .data-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .footer {
            background-color: green;
            color: #fff;
            text-align: center;
            padding: 20px 0;
            margin-top: 20px;
        }
        .error {
            color: #ff4444;
            padding: 10px;
            margin: 10px 0;
            background-color: #ffebee;
            border-radius: 4px;
        }
        .success {
            color: #00C851;
            padding: 10px;
            margin: 10px 0;
            background-color: #e8f5e9;
            border-radius: 4px;
        }
        .calc {
            border: 1px solid #4e3030;
            border-radius: 5px;
            padding: 20px;
            margin: 20px 0;
        }
        .gallery-container {
            display: flex;
            align-items: center;
            position: relative;
            max-width: 800px;
            margin: 20px auto;
        }
        .gallery {
            display: flex;
            overflow: hidden;
            width: 100%;
        }
        .slides {
            display: flex;
            transition: transform 0.5s ease;
        }
        .slide {
            min-width: 33.33%;
            box-sizing: border-box;
            padding: 0 5px;
        }
        .slide img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .arrow {
            background-color: transparent;
            border: none;
            cursor: pointer;
            font-size: 2em;
            z-index: 1;
        }
        .pager {
            text-align: center;
            margin-top: 10px;
        }
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
            }
            .slide {
                min-width: 100%;
            }
        }
    </style>
</head>
<body class="m-auto">
    <header class="header p-2">
        <div class="container">
            <div class="header-content mx-auto">
                <div class="text-logo p-md-2">
                    <div class="logo m-0"></div>
                    <div class="site-link m-0 p-1">
                        <a href="https://moodle.kubsu.ru/">Сайт поддержки</a>
                    </div>
                </div>
                <nav class="main-menu mx-auto p-2">
                    <div class="col">
                        <ul>
                            <li class="ms-3 pt-3"><a href="https://ru.wikipedia.org/wiki/%D0%91%D1%80%D1%8E%D1%85%D0%BE%D0%B2%D0%B5%D1%86%D0%BA%D0%B0%D1%8F">Секрет</a></li>
                            <li class="ms-3 pt-3"><a href="www.kubsu.ru">Кубгу</a></li>
                            <li class="ms-3 pt-3"><a href="https://agroserver.ru/pshenitsa/p1-region-23.htm">Купить зерно</a></li>
                            <li class="ms-3 pt-3"><a href="https://github.com/">Github</a></li>
                            <li class="ms-3 pt-3">
                                <a href="?logout=1" style="color: #ff4444;">
                                    <i class="fas fa-sign-out-alt"></i> Выйти
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="content m-auto p-4">
                <?php if (!empty($success)): ?>
                    <div class="success"><?= $success ?></div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <?php foreach ($errors as $error): ?>
                        <div class="error"><?= $error ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <br><button class="m-auto p-2" id="toggleButton">See more information</button>
                <ul id="hiddenBlock"><br>
                    <li>Задание 1 и 7</li>
                    <a href="http://kubsu.ru/" id="Джунгарик" title="Официальный сайт Кубанского государственного университета">Абсолютная гиперссылка на главную страницу сайта kubsu.ru и ссылка с тремя параметрами в URL</a>
                    <li>Задание 2</li>
                    <a href="https://kubsu.ru/" title="Официальный сайт Кубанского государственного университета">Абсолютная на главную сайта kubsu.ru в протоколе https</a>
                    <li>Задание 3(ссылка-изображение)</li>
                    <a href="https://kubsu.ru/" title="Официальный сайт Кубанского государственного университета"> <img src="image.png" width="250"></a>
                    <li>Задание 4(задания 5,9,10,11)</li>
                    <a href="Web/Vnutr.html">Сокращенная ссылка на внутреннюю страницу</a>
                    <li>Задание 10(задания 11 и 12)</li>
                    <a href="Web/Catalog/about.html">Относительная на страницу в каталоге about</a>
                    <li> Задание 13(контекстная в тексте абзаца) </li>
                    <a href="https://kubsu.ru">КубГУ</a> лучший университет Юга России
                    <li> Задание 14</li>
                    <a href="https://www.kinopoisk.ru/lists/movies/top250/?utm_referrer=yandex.ru">Ссылка на фрагмент страницы стороннего сайта</a>
                    <li class="map">Задание 15(ссылки из прямоугольных и круглых областей картинки (HTML-тег map))
                        <map name="center">
                            <area shape="rect" coords="30,301,161,428" href="https://kubsu.ru/index.php" alt="Сайт университета">
                            <area shape="circle" coords="524,366,55" href="https://t.me/kubsunews" alt="Канал телеграм">
                        </map>
                        <div><img usemap="#center" src="kubik.png" alt="Фото главного корпуса"></div>
                    </li>
                    <li>Задание 16</li>
                    <a href="">Ссылка с пустым href</a>
                    <li>Задание 17</li>
                    <a>Ссылка без href</a>
                    <li>Задание 18</li>
                    <a href="https://kubsu.ru" rel="nofollow">Ссылка, по которой запрещен переход поисковиками</a>
                    <li>Задание 19</li>
                    <a href="https://kubsu.ru" rel="noindex">Ссылка, запрещенная для индексации поисковиками</a>
                    <li>Задание 6 и 8</li>
                    <a href= "#Джунгарик" >Вернуться в начало(ссылка на фрагмент текущей страницы и ссылка с параметром id в URL)</a>
                    <li>Задание 20(нумерованный список ссылок с подписями title)</li>
                    <ol>
                        <li><a href="https://kubsu.ru" title="Это сайт КубГУ">Перейти на сайт КубГУ</a></li>
                        <li><a href="https://t.me/kubsunews" title="Это телеграмм КубГУ">Перейти в телеграмм КубГУ</a></li>
                        <li><a href="" title="Что-то еще">...</a></li>
                    </ol>
                    <li>Задание 21</li>
                    <a href="ftp://login:password@example.com/file.txt">Ссылка на файл на сервере FTP с авторизацией</a>
                </ul><br>

                <form method="POST" class="p-3" style="background: #f8f9fa; border-radius: 8px;">
                    <h2>Анкета пользователя</h2>

                    <div class="mb-3">
                        <label class="form-label">ФИО:</label>
                        <input type="text" name="fio" class="form-control" value="<?= htmlspecialchars($userData['fio'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Телефон:</label>
                        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($userData['phone'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email:</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($userData['email'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Дата рождения:</label>
                        <input type="date" name="birthdate" class="form-control" value="<?= htmlspecialchars($userData['birthdate'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Пол:</label>
                        <div>
                            <input type="radio" id="male" name="gender" value="male" <?= ($userData['gender'] ?? '') == 'male' ? 'checked' : '' ?> required>
                            <label for="male">Мужской</label>
                        </div>
                        <div>
                            <input type="radio" id="female" name="gender" value="female" <?= ($userData['gender'] ?? '') == 'female' ? 'checked' : '' ?>>
                            <label for="female">Женский</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Языки программирования:</label>
                        <select name="languages[]" multiple class="form-control" required style="height: auto;">
                            <?php
                            $allLanguages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskel', 'Clojure', 'Prolog', 'Scala', 'Go'];
                            foreach ($allLanguages as $lang): ?>
                                <option value="<?= $lang ?>" <?= in_array($lang, $userLanguages) ? 'selected' : '' ?>>
                                    <?= $lang ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Биография:</label>
                        <textarea name="bio" class="form-control" rows="5" required><?= htmlspecialchars($userData['bio'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <input type="checkbox" id="contract" name="contract" <?= ($userData['contract_agreed'] ?? 0) ? 'checked' : '' ?> required>
                        <label for="contract">Согласен на обработку данных</label>
                    </div>

                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </form>

                <div class="tablet">
                    <table class="data-table pt-3">
                        <thead>
                            <tr>
                                <th id="1" colspan="2">Название и ID</th>
                                <th id="3">Лат.</th>
                                <th id="4">Описание</th>
                                <th id="5">Цена за тонну</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Пшеница</td>
                                <td>ID 1</td>
                                <td>Triticum</td>
                                <td>Род травянистых, в основном однолетних, растений семейства Злаки, или Мятликовые (Poaceae), ведущая зерновая культура во многих странах.</td>
                                <td>16000</td>
                            </tr>
                            <tr>
                                <td>Рожь</td>
                                <td>ID 2</td>
                                <td>Secále cereále</td>
                                <td>Однолетнее или двулетнее травянистое растение, вид рода рожь (Secale) семейства злаки, или мятликовые (Poaceae).</td>
                                <td>7500</td>
                            </tr>
                            <tr>
                                <td rowspan="2">Кукуруза</td>
                                <td>ID 3</td>
                                <td>Zea</td>
                                <td> Кукуруза сахарная - Однолетнее травянистое культурное растение, единственный культурный представитель рода Кукуруза (Zea) семейства Злаки (Poaceae).</td>
                                <td>14500</td>
                            </tr>
                            <tr>
                                <td>ID 4</td>
                                <td>Zea </td>
                                <td>Кукуруза кормовая —  однолетнее травянистое культурное растение, единственный культурный представитель рода Кукуруза (Zea) семейства Злаки (Poaceae).</td>
                                <td>14500</td>
                            </tr>
                            <tr>
                                <td>Ячмень</td>
                                <td>ID 5</td>
                                <td>Hordeum</td>
                                <td>Род растений семейства Злаки (Poaceae), один из древнейших злаков, возделываемых человеком.</td>
                                <td>11500</td>
                            </tr>
                            <tr>
                                <td>Овес</td>
                                <td>ID 6</td>
                                <td>Avéna</td>
                                <td>Род однолетних травянистых растений семейства Злаки, или Мятликовые (Poaceae)</td>
                                <td>12000</td>
                            </tr>
                        </tbody>
                    </table><br>
                </div>

                <div class="calc p-3">
                    <h1>Калькулятор стоимости заказа</h1>
                    <label for="quantity">Количество:</label>
                    <input type="number" class="p-1 mb-3" id="quantity" min="1" value="1">

                    <label>Автомобиль:</label>
                    <div style="display: flex;">
                        <input type="radio" id="service1" name="service" value="16000" checked>
                        <label for="service1">Пшеница</label>
                    </div>
                    <div style="display: flex;">
                        <input type="radio" id="service2" name="service" value="7500">
                        <label for="service2">Рожь</label>
                    </div>
                    <div style="display: flex;">
                        <input type="radio" id="service3" name="service" value="14500">
                        <label for="service3">Кукуруза сахарная</label>
                    </div>
                    <div style="display: flex;">
                        <input type="radio" id="service4" name="service" value="14000">
                        <label for="service4">Кукуруза кормовая</label>
                    </div>
                    <div style="display: flex;">
                        <input type="radio" id="service5" name="service" value="11500">
                        <label for="service5">Ячмень</label>
                    </div>
                    <div style="display: flex;">
                        <input type="radio" id="service6" name="service" value="12000">
                        <label for="service6">Овес</label>
                    </div><br>

                    <button id="calculate">Рассчитать стоимость</button><br>
                    <div id="result" class="mt-1"></div>
                </div><br>

                <div class="gallery-container">
                    <button class="arrow left" onclick="moveSlide(-1)">&#10094;</button>
                    <div class="gallery">
                        <div class="slides">
                            <div class="slide">
                                <img src="kolhoz.png" alt="Image 1">
                            </div>
                            <div class="slide">
                                <img src="hlopok.png" alt="Image 2">
                            </div>
                            <div class="slide">
                                <img src="pole.png" alt="Image 3">
                            </div>
                            <div class="slide">
                                <img src="pole2.png" alt="Image 4">
                            </div>
                            <div class="slide">
                                <img src="Psenitsa.png" alt="Image 5">
                            </div>
                            <div class="slide">
                                <img src="rekrot.png" alt="Image 6">
                            </div>
                            <div class="slide">
                                <img src="rozh.png" alt="Image 7">
                            </div>
                            <div class="slide">
                                <img src="xerno.png" alt="Image 8">
                            </div>
                        </div>
                    </div>
                    <button class="arrow right" onclick="moveSlide(1)">&#10095;</button>
                </div>

                <div class="pager">
                    <span id="current-page">1</span> / <span id="total-pages">5</span>
                </div><br>
            </div>
        </div>
    </main>

    <footer class="footer p-4">
        <div class="container">
            <div class="footer-content m-auto">
                <p>&copy; Артюхов А.</p>
            </div>
        </div>
    </footer>

    <script>
        const toggleButton = document.getElementById("toggleButton");
        const hiddenBlock = document.getElementById("hiddenBlock");

        toggleButton.addEventListener("click", () => {
            if (hiddenBlock.style.display === "block") {
                hiddenBlock.style.display = "none";
            } else {
                hiddenBlock.style.display = "block";
            }
        });

        // Калькулятор
        const quantityInput = document.getElementById('quantity');
        const serviceRadios = document.querySelectorAll('input[name="service"]');
        const calculateButton = document.getElementById('calculate');
        const resultDiv = document.getElementById('result');

        function calculateCost() {
            const quantity = parseInt(quantityInput.value);
            let price = 0;
            let selectedService = null;

            for (const radio of serviceRadios) {
                if (radio.checked) {
                    selectedService = radio;
                    price = parseInt(radio.value);
                    break;
                }
            }

            if (selectedService) {
                const totalCost = quantity * price;
                resultDiv.textContent = `Стоимость заказа: ${totalCost} руб.`;
            }
        }

        calculateButton.addEventListener('click', calculateCost);
        quantityInput.addEventListener('change', calculateCost);
        serviceRadios.forEach(radio => {
            radio.addEventListener('change', calculateCost);
        });

        // Галерея
        let currentSlide = 0;
        const totalSlides = 8;
        const slidesToShow = window.innerWidth <= 768 ? 1 : 3;
        const totalPages = window.innerWidth <= 768 ? 8 : 6;

        document.getElementById('total-pages').textContent = totalPages;

        function updatePager() {
            document.getElementById('current-page').textContent = currentSlide + 1;
        }

        function moveSlide(direction) {
            currentSlide += direction;

            if (currentSlide < 0) {
                currentSlide = 0;
            } else if (currentSlide >= totalPages) {
                currentSlide = totalPages - 1;
            }

            const gallery = document.querySelector('.slides');
            const offset = window.innerWidth <= 768 ? -currentSlide * (100 / 1) : -currentSlide * (100 / 3);
            gallery.style.transform = `translateX(${offset}%)`;
            updatePager();
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>