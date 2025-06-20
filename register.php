<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once 'config/db.php';
require_once 'config/header.php';
// Переменные для отображения ошибок и сохранения значений полей
$errorMessage = "";
$email = "";
$username = "";
$password = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email'], " \t.") : '';
    $username = isset($_POST['username']) ? trim($_POST['username'], " \t.") : '';
    $password = isset($_POST['password']) ? trim($_POST['password'], " \t.") : '';

    // Проверка на пустые поля
    if (empty($email) || empty($username) || empty($password)) {
        $errorMessage = "Все поля должны быть заполнены.";
    } else {
        try {
            // Проверка существования email
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn()) {
                $errorMessage = "Пользователь с такой почтой уже существует.";
            }

            // Проверка существования username
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn()) {
                $errorMessage = "Пользователь с таким именем уже существует.";
            }

            // Если нет ошибок, создаем нового пользователя
            if (empty($errorMessage)) {
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
                if ($stmt->execute([$username, $email, $passwordHash])) {

                    $newUserId = $pdo->lastInsertId();

                    $log = $pdo->prepare("INSERT INTO user_logs (user_id, action_type) VALUES (?, ?)");
                    $log->execute([$newUserId, 'registered']);
                    

                    header("Location: main.php"); // Перенаправление на страницу успеха
                    exit;
                } else {
                    $errorMessage = "Ошибка при регистрации. Попробуйте позже.";
                }
            }
        } catch (PDOException $e) {
            $errorMessage = "Ошибка: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/main.css">
</head>

<body>


    <main class="main_window">
        
    <div class="form-container">
        <form method="POST" action="">
            <h1>Присоединяйтесь к Буккроссингу!</h1>
            <!-- <div>После регистрации вы сможете отпускать и регистрировать книги, оставлять заявки на книги и участвовать в обороте</div> -->
            <label for="username">Имя пользователя:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" placeholder="Username" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Email" required>

            <label for="password">Пароль:</label>
            <input type="password" id="password" name="password" placeholder="Password" required>

            <button type="submit">Зарегистрироваться</button>
        </form>
        
        <?php if (!empty($errorMessage)): ?>
            <p class="error-message"><?php echo htmlspecialchars($errorMessage); ?></p>
        <?php endif; ?>
        
    </div>
    </main>


    <script>
    var num;
    function redirectToPage(num) {
        switch(num){
         case 1:window.location.href = "login.php";break;
         case 2:window.location.href = "register.php";break;
         case 3:window.location.href = "dashboard.php";break;
         case 4:window.location.href = "search.php";break;
         case 5:window.location.href = "places.php";break;
         case 6:window.location.href = "main.php";break;
         default:console.error("Неверный номер страницы: " + num);break;
         }
    }
    </script>
</body>
</html>
