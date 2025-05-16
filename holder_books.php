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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_book_id'])) {
    $book_id = (int)$_POST['delete_book_id'];

    try {
        $stmt = $pdo->prepare('DELETE FROM books WHERE id = :book_id AND place_id IN (SELECT id FROM places WHERE user_id = :user_id)');
        $stmt->execute([
            'book_id' => $book_id,
            'user_id' => $user_id
        ]);

        if ($stmt->rowCount() > 0) {
            echo "<p>Книга успешно удалена.</p>";
        } else {
            echo "<p>Ошибка: Книга не найдена или вы не имеете права её удалять.</p>";
        }
    } catch (PDOException $e) {
        echo "<p>Ошибка удаления книги: " . $e->getMessage() . "</p>";
    }
}

// Получение книг, связанных с местами владельца
$stmt = $pdo->prepare('
    SELECT b.id AS book_id, b.title, b.author, p.name AS place_name, u.username 
    FROM books b
    JOIN places p ON b.place_id = p.id
    JOIN users u ON b.owner_id = u.id
    WHERE p.user_id = :user_id
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
                                <input type="hidden" name="delete_book_id" value="<?= $book['book_id'] ?>">
                                <button type="submit" class="button danger">Удалить</button>
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
