<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config/db.php';
require_once 'config/header.php';
$errorMessage = "";
$email = "";
$password = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                $_SESSION['role'] = $user['role']; // Сохраняем роль
                header('Location: main.php');
                exit();
            } else {
                $errorMessage = "Неверный email или пароль.";
            }
        } catch (PDOException $e) {
            $errorMessage = "Ошибка при авторизации: " . $e->getMessage();
        }
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
</head>
<body>

<main class="main_window">
    

    <div class="form-container">
        <form method="POST" action="">
            <h1>Войти в аккаунт</h1>

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
