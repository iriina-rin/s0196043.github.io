<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Анкета</title>
<link rel="stylesheet" href="style.css">
</head>

<body>

<h1>Анкета</h1>

<form action="form.php" method="POST">

<p>ФИО</p>
<input type="text" name="name">

<p>Телефон</p>
<input type="tel" name="phone">

<p>Email</p>
<input type="email" name="email">

<p>Дата рождения</p>
<input type="date" name="birthdate">

<p>Пол</p>

<input type="radio" name="gender" value="male"> Мужской
<input type="radio" name="gender" value="female"> Женский

<p>Любимые языки программирования</p>

<select name="languages[]" multiple>

<option value="1">Pascal</option>
<option value="2">C</option>
<option value="3">C++</option>
<option value="4">JavaScript</option>
<option value="5">PHP</option>
<option value="6">Python</option>
<option value="7">Java</option>
<option value="8">Haskell</option>
<option value="9">Clojure</option>
<option value="10">Prolog</option>
<option value="11">Scala</option>
<option value="12">Go</option>

</select>

<p>Биография</p>

<textarea name="bio"></textarea>

<br><br>

<label>
<input type="checkbox" name="contract">
С контрактом ознакомлен
</label>

<br><br>

<button type="submit">Сохранить</button>

</form>

</body>
</html>
