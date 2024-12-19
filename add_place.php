<?php
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/db.php'; // Подключение к базе данных

// Проверяем роль пользователя
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = :id');
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    echo "Доступ запрещен. Только администраторы могут добавлять места.";
    exit();
}

// Обработка формы добавления места
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $place_name = trim($_POST['place_name']);
    $description = trim($_POST['description']);
    $address = trim($_POST['address']);

    // Проверка обязательных полей
    if (empty($place_name) || empty($address)) {
        echo "Пожалуйста, заполните все обязательные поля.";
    } else {
        // Добавление места в базу данных
        $stmt = $pdo->prepare('INSERT INTO places (name, description, address) VALUES (:name, :description, :address)');
        $stmt->execute([
            'name' => $place_name,
            'description' => $description,
            'address' => $address
        ]);

        echo "Место успешно добавлено.";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить место</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="header">
    <div class="pic">
        <img src="assets/images/logo.png" alt="logo" class="logo"/>
        <span class="logo_text">Костянка</span>
    </div>
    <nav class="nav_buttons">
        <button onclick="window.location.href='user_page.php'" class="button">На главную</button>
        <button onclick="window.location.href='logout.php'" class="button">Выйти</button>
    </nav>
</header>

<main class="main_window">
    <h1>Добавить новое место</h1>
    <form action="add_place.php" method="POST">
        <label for="place_name">Название места:</label>
        <input type="text" id="place_name" name="place_name" required>
        
        <label for="description">Описание:</label>
        <textarea id="description" name="description"></textarea>
        
        <label for="address">Адрес:</label>
        <input type="text" id="address" name="address" required>
        
        <button type="submit">Добавить место</button>
    </form>
</main>
</body>
</html>
