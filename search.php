<?php
session_start();
ini_set('display_errors', 0);
$user_id = $_SESSION['user_id'];

// Проверка роли пользователя (например, 'admin' или 'user')
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';

require_once 'config/header.php';
require_once 'config/db.php';

// Переменные для поиска
$title = isset($_POST['title']) ? $_POST['title'] : '';
$author = isset($_POST['author']) ? $_POST['author'] : '';

$query = "SELECT * FROM books WHERE 1"; // Базовый запрос
$params = [];

if ($title) {
    $query .= " AND title LIKE ?";
    $params[] = "%$title%";
}

if ($author) {
    $query .= " AND author LIKE ?";
    $params[] = "%$author%";
}

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Ошибка при получении данных: " . $e->getMessage();
    exit();
}

// Проверяем был запрос на удаление
if (isset($_POST['delete_id'])) {
    if ($role === 'admin') { // Удаление разрешено только для админа
        $delete_id = $_POST['delete_id'];

        try {
            $deleteQuery = "DELETE FROM books WHERE id = ?";
            $stmt = $pdo->prepare($deleteQuery);
            $stmt->execute([$delete_id]);
            header('Location: ' . $_SERVER['PHP_SELF']); // Перезагружаю страницу
            exit();
        } catch (PDOException $e) {
            echo "Ошибка при удалении книги: " . $e->getMessage();
            exit();
        }
    } else {
        echo "У вас нет прав для выполнения этого действия.";
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поиск книг</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<main class="main_window">

<form class="search-box" method="POST" action="">
    <label for="title">Название книги:</label>
    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>">
    <label for="author">Автор:</label>
    <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($author); ?>">
    <button type="submit">Найти</button>
</form>

<h2>Результаты поиска</h2>

<?php if (empty($books)): ?>
    <p>Книги не найдены по указанным критериям.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Название</th>
                <th>Автор</th>
                <th>Описание</th>
                <?php if ($role === 'admin'): ?>
                    <th>Действие</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($books as $book): ?>
                <tr>
                    <td><?php echo htmlspecialchars($book['title']); ?></td>
                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                    <td><?php echo htmlspecialchars($book['description']); ?></td>
                    <?php if ($role === 'admin'): ?>
                        <td>
                            <form method="POST" action="">
                                <input type="hidden" name="delete_id" value="<?php echo $book['id']; ?>">
                                <button type="submit" onclick="return confirm('Вы уверены, что хотите удалить эту книгу?');">Удалить</button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</main>

</body>
</html>
