<?php
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/header.php';
require_once 'config/db.php'; // Подключение к базе данных

// Получение ID авторизованного пользователя
$user_id = $_SESSION['user_id'];

// Получение информации о пользователе
$stmt = $pdo->prepare('SELECT username, role FROM users WHERE id = :id');
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo "Пользователь не найден.";
    exit();
}

// Получение роли пользователя
$role = isset($user['role']) ? $user['role'] : 'user';

// Обработка запроса на обновление роли
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $role === 'admin') {
    if (isset($_POST['user_id'])) {
        $target_user_id = $_POST['user_id'];

        // Обновляем роль другого пользователя до админа
        $stmt = $pdo->prepare('UPDATE users SET role = "admin" WHERE id = :id');
        $stmt->execute(['id' => $target_user_id]);

        echo "Пользователь с ID {$target_user_id} обновлён до админа.";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Персональная страница</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>


<main class="main_window">

    <div class="user-info">
        <h1>Добро пожаловать, <?php echo htmlspecialchars($user['username']); ?>!</h1>
        <p>Роль: <?php echo $role === 'admin' ? 'Администратор' : 'Пользователь'; ?></p>
    </div>

    <div class="form-container">
        <h2>Изменить пароль</h2>
        <form action="update_password.php" method="POST">
            <label for="new_password">Новый пароль:</label>
            <input type="password" id="new_password" name="new_password" required>
            <label for="confirm_password">Подтвердите пароль:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            <button type="submit">Обновить пароль</button>
        </form>

        <?php if ($role === 'admin'): ?>
        <h2>Сделать пользователя администратором</h2>
        <form method="POST">
            <input type="number" name="user_id" placeholder="ID пользователя" required>
            <button type="submit">Сделать админом</button>
        </form>
        <?php endif; ?>
    </div>

    <div class="user-actions">
    <h2>Ваши действия</h2>
    <ul>
        <li>Добавлено книг: 10</li>
        <li>Предложено мест: 5</li>
    </ul>

    <?php if ($role === 'admin'): ?>
    <button onclick="window.location.href='add_place.php'" class="button">Добавить место</button>
    <?php endif; ?>

    
</div>

</main>


</body>
</html>
