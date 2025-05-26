<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/db.php';
require_once 'config/header.php';

$user_id = $_SESSION['user_id'];

// Обработка удаления книги
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['taken_book_id'])) {
    $book_id = (int)$_POST['taken_book_id'];

    try {
        // Получаем данные книги
        $stmt = $pdo->prepare("SELECT place_id FROM books WHERE id = ? AND place_id IN (SELECT id FROM places WHERE user_id = ?)");
        $stmt->execute([$book_id, $user_id]);
        $book = $stmt->fetch();

        if ($book) {
            $place_id = $book['place_id'];
            $created_at = date("Y-m-d H:i:s");

            // Логируем, что книгу забрали (пользователь неизвестен)
            $stmt = $pdo->prepare("
                INSERT INTO user_logs (user_id, action_type, book_id, place_id, action_time)
                VALUES (NULL, 'took_book', ?, ?, ?)
            ");
            $stmt->execute([$book_id, $place_id, $created_at]);

            // Обновляем статус книги (мягкое удаление)
            $stmt = $pdo->prepare("UPDATE books SET is_deleted = 1 WHERE id = ?");
            $stmt->execute([$book_id]);

            echo "<p>Книга помечена как забранная.</p>";
        } else {
            echo "<p>Ошибка: Книга не найдена или не принадлежит вам.</p>";
        }
    } catch (PDOException $e) {
        echo "<p>Ошибка: " . $e->getMessage() . "</p>";
    }
}

// Получение книг, связанных с местами владельца
$stmt = $pdo->prepare('
    SELECT b.id AS book_id, b.title, b.author, p.name AS place_name, u.username
    FROM books b
    JOIN places p ON b.place_id = p.id
    JOIN users u ON b.owner_id = u.id
    WHERE p.user_id = :user_id AND b.is_deleted = 0
');
$stmt->execute(['user_id' => $user_id]);
$books = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Книжки на вашей полке</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
<main class="main_window">
    <h1>Книжки на вашей полке</h1>
    <?php if (empty($books)): ?>
        <p>У вас пока нет книг на полках.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Название</th>
                    <th>Автор</th>
                    <th>Место</th>
                    <th>Владелец</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($books as $book): ?>
                    <tr>
                        <td><?= htmlspecialchars($book['title']) ?></td>
                        <td><?= htmlspecialchars($book['author']) ?></td>
                        <td><?= htmlspecialchars($book['place_name']) ?></td>
                        <td><?= htmlspecialchars($book['username']) ?></td>
                        <td>
                            <form method="POST" style="display:inline-block;">
                                <input type="hidden" name="taken_book_id" value="<?= $book['book_id'] ?>">
                            <button type="submit" class="button warning">Книгу забрали</button>
</form>

                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>
</body>
</html>
