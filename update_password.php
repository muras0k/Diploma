<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/db.php'; // Подключение к базе данных

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Проверка совпадения паролей
    if ($new_password !== $confirm_password) {
        echo "Пароли не совпадают.";
        exit();
    }

    // Проверка длины пароля (минимум 6 символов)
    if (strlen($new_password) < 6) {
        echo "Пароль должен содержать не менее 6 символов.";
        exit();
    }

    // Хэширование нового пароля
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Обновление пароля в базе данных
    $stmt = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
    $stmt->execute([
        'password' => $hashed_password,
        'id' => $user_id
    ]);

    echo "Пароль успешно обновлен.";
    echo '<br><a href="user_page.php">Вернуться на главную страницу</a>';
} else {
    header('Location: user_page.php');
    exit();
}
?>
