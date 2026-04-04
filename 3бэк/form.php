<?php

$dsn = 'mysql:host=localhost;dbname=u82815';
$user = 'u82815';
$pass = '3582298';

$db = new PDO($dsn,$user,$pass);

$name = $_POST['name'];
$phone = $_POST['phone'];
$email = $_POST['email'];
$birthdate = $_POST['birthdate'];
$gender = $_POST['gender'];
$bio = $_POST['bio'];

$contract = isset($_POST['contract']) ? 1 : 0;

$languages = $_POST['languages'];

$stmt = $db->prepare("INSERT INTO application
(name,phone,email,birthdate,gender,biography,contract)
VALUES (?,?,?,?,?,?,?)");

$stmt->execute([
$name,
$phone,
$email,
$birthdate,
$gender,
$bio,
$contract
]);

$app_id = $db->lastInsertId();

foreach($languages as $lang){

$stmt = $db->prepare("INSERT INTO application_languages
(application_id,language_id)
VALUES (?,?)");

$stmt->execute([$app_id,$lang]);

}

echo "Данные сохранены";

?>