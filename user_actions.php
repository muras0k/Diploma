<?php
session_start();

require_once 'config/header.php';
require_once 'config/db.php';

// Проверка авторизации и роли
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "Доступ запрещён. Только для администраторов.";
    exit;
}

$actions = [];
$error = '';
$user_input = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_input = trim($_POST['user_identifier'] ?? '');

    if ($user_input === '') {
        $error = "Введите ID или имя пользователя.";
    } else {
        // Определяем, ID это или username
        if (ctype_digit($user_input)) {
            // Поиск по ID
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
            $stmt->execute([$user_input]);
        } else {
            // Поиск по username
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE username = ?");
            $stmt->execute([$user_input]);
        }

        $user = $stmt->fetch();

        if ($user) {
            $user_id = $user['id'];
            $username = htmlspecialchars($user['username']);

            $stmt = $pdo->prepare("
                SELECT ul.*, b.title AS book_title, p.name AS place_name
                FROM user_logs ul
                LEFT JOIN books b ON ul.book_id = b.id
                LEFT JOIN places p ON ul.place_id = p.id
                WHERE ul.user_id = ?
                ORDER BY ul.action_time DESC
            ");
            $stmt->execute([$user_id]);
            $actions = $stmt->fetchAll();
        } else {
            $error = "Пользователь не найден.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Действия пользователя</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <h2>Просмотр действий пользователя</h2>

    <form method="post">
        <label>Введите ID или имя пользователя:</label><br>
        <input type="text" name="user_identifier" value="<?= htmlspecialchars($user_input) ?>">
        <button type="submit">Показать действия</button>
    </form>

    <?php if ($error): ?>
        <p style="color:red;"><?= $error ?></p>
    <?php endif; ?>

    <?php if ($actions): ?>
        <h3>Действия пользователя <?= htmlspecialchars($username ?? '') ?></h3>
        <table>
            <tr>
                <th>Дата и время</th>
                <th>Тип действия</th>
                <th>Книга</th>
                <th>Полка</th>
            </tr>
            <?php foreach ($actions as $action): ?>
                <tr>
                    <td><?= htmlspecialchars($action['action_time']) ?></td>
                    <td><?= htmlspecialchars($action['action_type']) ?></td>
                    <td><?= htmlspecialchars($action['book_title'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($action['place_name'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>
