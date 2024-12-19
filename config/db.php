<?php
$host = 'localhost';
$dbname = 'book_exchange';
$usernamefordb = 'root'; // или ваш пользователь
$passwordfordb = ''; // если нет пароля для root

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $usernamefordb, $passwordfordb);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}
?>
