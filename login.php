<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config/db.php';
require_once 'config/header.php';

$errorMessage = "";
$email = "";
$password = "";

// Генерация CSRF-токена (если не установлен)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем CSRF-токен из формы
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

    // Проверка CSRF-токена
    if (hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $email = isset($_POST['email']) ? trim($_POST['email'], " \t.") : '';
        $password = isset($_POST['password']) ? trim($_POST['password'], " \t.") : '';

        if (empty($email) || empty($password)) {
            $errorMessage = "Все поля должны быть заполнены.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, username, role, password FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    $log = $pdo->prepare("INSERT INTO user_logs (user_id, action_type) VALUES (?, ?)");
                    $log->execute([$user['id'], 'logged_in']);

                    header('Location: main.php');
                    exit();
                } else {
                    $errorMessage = "Неверный email или пароль.";
                }
            } catch (PDOException $e) {
                $errorMessage = "Ошибка при авторизации: " . $e->getMessage();
            }
        }
    } else {
        $errorMessage = "Неверный CSRF-токен.";
    }
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
<main class="main_window">
    <div class="form-container">
        <form method="POST" action="">
            <h1>Войти в аккаунт</h1>

            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Email" required>

            <label for="password">Пароль:</label>
            <input type="password" id="password" name="password" placeholder="Password" required>

            <button type="submit">Войти</button>
        </form>
        
        <?php if (!empty($errorMessage)): ?>
            <p class="error-message"><?php echo htmlspecialchars($errorMessage); ?></p>
        <?php endif; ?>

    </div>
</main>
</body>
</html>
