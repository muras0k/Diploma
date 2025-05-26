<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/header.php';
require_once 'config/db.php';

function showBooksOptimized($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT b.title, b.author, b.description, b.action, b.created_at,
                p.name AS place_name, u.username, b.genre 
            FROM books b
            LEFT JOIN places p ON b.place_id = p.id
            LEFT JOIN users u ON b.owner_id = u.id
            WHERE is_deleted = 0
            ORDER BY b.created_at DESC
            LIMIT 20
        ");
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<table border='1' id='booksTable'>";
        echo "<tr><th>Название</th><th>Автор</th><th>Описание</th><th>Действие</th><th>Полка</th><th>Пользователь</th><th>Жанр</th><th>Время</th></tr>";
        foreach ($books as $book) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($book['title']) . "</td>";
            echo "<td>" . htmlspecialchars($book['author']) . "</td>";
            echo "<td>" . htmlspecialchars($book['description']) . "</td>";
            echo "<td>" . ($book['action'] === 'left' ? 'Оставил' : 'Забрал') . "</td>";
            echo "<td>" . htmlspecialchars($book['place_name']) . "</td>";
            echo "<td>" . htmlspecialchars($book['username']) . "</td>";
            echo "<td>" . htmlspecialchars($book['genre']) . "</td>";
            echo "<td>" . htmlspecialchars($book['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (PDOException $e) {
        echo "Ошибка: " . $e->getMessage();
    }
}

$pdo = new PDO("mysql:host=localhost;dbname=book_exchange", "root", "");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<main class="main_window">
    <h1>Последние книги из книговорота</h1>

    <!-- Кнопка для вызова формы -->
    <button onclick="showAddForm()">Добавить запись</button>

    <?php showBooksOptimized($pdo); ?>
</main>

<!-- Модальное окно для добавления записи -->
<div id="addFormModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="hideAddForm()">&times;</span>
        <?php include 'add_form.php'; ?>
    </div>
</div> 

<script>
    function showAddForm() {
        document.getElementById('addFormModal').style.display = 'block';
    }

    function hideAddForm() {
        document.getElementById('addFormModal').style.display = 'none';
    }
</script>

<style>
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .modal-content {
        background-color: white;
        padding: 20px;
        border-radius: 5px;
        width: 400px;
        position: relative;
    }
    .close {
        position: absolute;
        top: 10px;
        right: 10px;
        cursor: pointer;
    }
</style>

</body>
</html>