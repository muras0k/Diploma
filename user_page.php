<?php
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/db.php'; // Подключение к базе данных
require_once 'config/header.php'; // Подключение общего заголовка
require_once 'config/FileUploader.php';
use config\FileUploader;

$user_id = $_SESSION['user_id'];
$weightsFile = 'config/weights.php';
$weights = require $weightsFile;

$books = [];

try {
    $stmt = $pdo->prepare("
        SELECT b.title, b.author, p.name AS place_name
        FROM books b
        LEFT JOIN places p ON b.place_id = p.id
        WHERE b.owner_id = :user_id AND is_deleted = 0
    ");
    $stmt->execute(['user_id' => $user_id]);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Ошибка при получении книг: " . htmlspecialchars($e->getMessage());
}

// Получение текущего пользователя
$stmt = $pdo->prepare('SELECT id, username, role, avatar_path FROM users WHERE id = :user_id');
$stmt->execute(['user_id' => $user_id]);
$currentUser = $stmt->fetch();

if (!$currentUser) {
    echo "Ошибка: пользователь не найден.";
    exit();
}

// Проверка, является ли пользователь администратором
$is_admin = $currentUser['role'] === 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $fileUploader = new FileUploader($_FILES['avatar'],'uploads/avatars/' ); //'uploads/avatars/'  'tests/uploads/'

    // Проверки на загрузку файла
    if (!$fileUploader->isFilePresent()) {
        $_SESSION['message'] = $fileUploader->getErrorMessage();
        header('Location: user_page.php');
        exit();
    }

    if ($fileUploader->hasUploadError()) {
        $_SESSION['message'] = $fileUploader->getErrorMessage();
        header('Location: user_page.php');
        exit();
    }

    if (!$fileUploader->isFileValid()) {
        $_SESSION['message'] = $fileUploader->getErrorMessage();
        header('Location: user_page.php');
        exit();
    }

    if (!$fileUploader->isValidType()) {
        $_SESSION['message'] = $fileUploader->getErrorMessage();
        header('Location: user_page.php');
        exit();
    }

    if (!$fileUploader->isValidSize()) {
        $_SESSION['message'] = $fileUploader->getErrorMessage();
        header('Location: user_page.php');
        exit();
    }

    // Генерация уникального имени для файла и перемещение его
    $newFileName = $fileUploader->generateFileName();
    $filePath = $fileUploader->moveFile($newFileName);

    if (!$filePath) {
        $_SESSION['message'] = $fileUploader->getErrorMessage();
        header('Location: user_page.php');
        exit();
    }

    // Обновление пути аватарки в базе данных
    $stmt = $pdo->prepare('UPDATE users SET avatar_path = :avatar_path WHERE id = :id');
    $stmt->execute(['avatar_path' => $filePath, 'id' => $_SESSION['user_id']]);

    $_SESSION['message'] = "Аватарка успешно загружена.";
    header('Location: user_page.php');
    exit();
}

// Вывод сообщения
if (!empty($_SESSION['message'])) {
    echo "<p style='color: red;'>" . htmlspecialchars($_SESSION['message']) . "</p>";
    unset($_SESSION['message']);
}

// Обработка обновления весов (только для администраторов)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_left']) && $is_admin) {
    $newWeights = [
        'action' => [
            'left' => (int)$_POST['action_left'],
            'taken' => (int)$_POST['action_taken'],
        ],
        'place_popularity' => (int)$_POST['place_popularity'],
        'genre' => [],
        'recent_time' => (int)$_POST['recent_time'],
    ];

    foreach ($_POST as $key => $value) {
        if (strpos($key, 'genre_') === 0) {
            $genre = substr($key, 6);
            $newWeights['genre'][$genre] = (int)$value;
        }
    }

    file_put_contents($weightsFile, "<?php\nreturn " . var_export($newWeights, true) . ";\n");
    $_SESSION['message'] = "Весы успешно обновлены.";
    header('Location: user_page.php');
    exit();
}

// Обработка повышения ролей пользователей
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['promote_user_id']) && $is_admin) {
    $targetUserId = (int)$_POST['promote_user_id'];
    $stmt = $pdo->prepare('UPDATE users SET role = "holder" WHERE id = :id AND role = "user"');
    $stmt->execute(['id' => $targetUserId]);

    $_SESSION['message'] = $stmt->rowCount() > 0 
        ? "Пользователь успешно повышен до holder." 
        : "Ошибка: пользователь уже не является user.";
    header('Location: user_page.php');
    exit();
}

// Получение пользователей для повышения роли
$usersToPromote = [];
if ($is_admin) {
    $stmt = $pdo->query('SELECT id, username FROM users WHERE role = "user"');
    $usersToPromote = $stmt->fetchAll();
}

// Получение списка жанров
$stmt = $pdo->query('SELECT DISTINCT genre FROM books');
$genres = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<main class="main_window">
    <h1>Личный кабинет</h1>
    <p>Добро пожаловать, <strong><?= htmlspecialchars($currentUser['username']) ?></strong>!</p>
    <p>Ваша роль: <strong><?= htmlspecialchars($currentUser['role']) ?></strong></p>

    <h2>Ваш аватар</h2>
<?php if (!empty($currentUser['avatar_path']) && file_exists($currentUser['avatar_path'])): ?>
    <img src="<?= htmlspecialchars($currentUser['avatar_path']) ?>" alt="Аватар пользователя" style="max-width: 150px; max-height: 150px;">
<?php else: ?>
    <p>У вас пока нет аватарки. Загрузите её с помощью формы выше.</p>
<?php endif; ?>



<form method="POST" enctype="multipart/form-data" id="avatarForm">
    <label for="avatar">Выберите изображение:</label>
    <input type="file" name="avatar" id="avatar" accept="image/png, image/jpeg">
    <button type="submit">Загрузить</button>
</form>

<script>
document.getElementById('avatarForm').addEventListener('submit', function(e) {
    const fileInput = document.getElementById('avatar');
    const file = fileInput.files[0];  // Получаем первый выбранный файл

    // Проверяем, был ли файл выбран и существует ли он (не пустой)
    if (!file || file.size === 0) {
        e.preventDefault();  // Отменяем отправку формы
        alert('Пожалуйста, выберите действительный файл для загрузки.');
    }
});
</script>


    <?php if ($is_admin): ?>
        <h2>Настройка весов</h2>
        <form method="POST">
            <p>Вес за действие</p>
            <label>Оставили книгу: <input type="number" name="action_left" value="<?= $weights['action']['left'] ?>"></label><br>
            <label>Забрали книгу: <input type="number" name="action_taken" value="<?= $weights['action']['taken'] ?>"></label><br>

            <p>Популярность места</p>
            <label>Коэффициент: <input type="number" name="place_popularity" value="<?= $weights['place_popularity'] ?>"></label><br>

            <p>Вес по жанру</p>
            <?php foreach ($genres as $genre): ?>
                <label><?= htmlspecialchars($genre['genre']) ?>:</label>
                <input type="number" name="genre_<?= htmlspecialchars($genre['genre']) ?>" 
                    value="<?= $weights['genre'][$genre['genre']] ?? 0 ?>">
                <br>
            <?php endforeach; ?>

            <p>Вес за недавнее добавление</p>
            <label>Коэффициент: <input type="number" name="recent_time" value="<?= $weights['recent_time'] ?>"></label><br>

            <button type="submit">Сохранить</button>
        </form>

        <h2>Пользователи с ролью user</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя пользователя</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usersToPromote)): ?>
                    <tr>
                        <td colspan="3">Нет пользователей с ролью user.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($usersToPromote as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td>
                                <form method="POST" style="display:inline-block;">
                                    <input type="hidden" name="promote_user_id" value="<?= $user['id'] ?>">
                                    <button type="submit"onclick="return confirm('Вы уверены, что хотите изменить роль этого пользователя?');">Повысить до holder</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
    <form action="user_actions.php" method="get" style="margin-top: 15px;">
        <button type="submit">Просмотр действий пользователей</button>
    </form>

    <?php endif; ?>



    <h2>Ваши книги на полках</h2>
    <?php if (empty($books)): ?>
        <p>У вас пока нет добавленных книг.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Название</th>
                    <th>Автор</th>
                    <th>Место</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($books as $book): ?>
                    <tr>
                        <td><?= htmlspecialchars($book['title']) ?></td>
                        <td><?= htmlspecialchars($book['author']) ?></td>
                        <td><?= htmlspecialchars($book['place_name']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if ($currentUser['role'] === 'holder' || $is_admin): ?>
        <h2>Действия для владельцев полок</h2>
        <p><a href="add_place.php" class="button">Добавить место</a></p>
        <p><a href="holder_books.php" class="button">Книжки на вашей полке</a></p>
    <?php endif; ?>
</main>
</body>
</html>