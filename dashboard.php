<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/header.php';
require_once 'config/db.php';

function showBooksWithoutOptimization($pdo) {
    try {
        $startTime = hrtime(true);
        $stmt = $pdo->query("SELECT SQL_NO_CACHE id, title, author, description, action, created_at, place_id, owner_id, genre FROM books ORDER BY created_at DESC");
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

        
        $memory = memory_get_peak_usage(true);

        echo "<table border='1' id='booksTable' style='display:none;'>";
        echo "<tr><th>Название</th><th>Автор</th><th>Описание</th><th>Действие</th><th>Полка</th><th>Пользователь</th><th>Жанр</th><th>Время</th></tr>";
        foreach ($books as $book) {
            // Дополнительный запрос для получения информации о месте
            $placeStmt = $pdo->prepare("SELECT name FROM places WHERE id = ?");
            $placeStmt->execute([$book['place_id']]);
            $place = $placeStmt->fetchColumn();

            // Дополнительный запрос для получения информации о пользователе
            $userStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $userStmt->execute([$book['owner_id']]);
            $username = $userStmt->fetchColumn();

            echo "<tr>";
            echo "<td>" . htmlspecialchars($book['title']) . "</td>";
            echo "<td>" . htmlspecialchars($book['author']) . "</td>";
            echo "<td>" . htmlspecialchars($book['description']) . "</td>";
            echo "<td>" . ($book['action'] === 'left' ? 'Оставил' : 'Забрал') . "</td>";
            echo "<td>" . htmlspecialchars($place) . "</td>";
            echo "<td>" . htmlspecialchars($username) . "</td>";
            echo "<td>" . htmlspecialchars($book['genre']) . "</td>";
            echo "<td>" . htmlspecialchars($book['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        $endTime = hrtime(true);
        // Выводим время и память
        echo "<p>Время выполнения (без оптимизации): " . (($endTime - $startTime) / 1000000) . " миллисекунд</p>";
        echo "<p>Максимальное использование памяти (без оптимизации): " . number_format($memory / 1024 / 1024, 2) . " MB</p>";

    } catch (PDOException $e) {
        echo "Ошибка: " . $e->getMessage();
    }
}


function showBooksWithOptimization($pdo) {
    try {
        $startTime = hrtime(true);
        $stmt = $pdo->query("
            SELECT b.title, b.author, b.description, b.action, b.created_at, p.name AS place_name, u.username, b.genre
            FROM books b
            LEFT JOIN places p ON b.place_id = p.id
            LEFT JOIN users u ON b.owner_id = u.id
            ORDER BY b.created_at DESC
        ");
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

        
        $memory = memory_get_peak_usage(true);

        echo "<table border='1' id='booksTable' style='display:none;'>";
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
        $endTime = hrtime(true);
        // Выводим время и память
        echo "<p>Время выполнения (с оптимизацией): " . (($endTime - $startTime) / 1000000) . " миллисекунд</p>";
        echo "<p>Максимальное использование памяти (с оптимизацией): " . number_format($memory / 1024 / 1024, 2) . " MB</p>";

    } catch (PDOException $e) {
        echo "Ошибка: " . $e->getMessage();
    }
}

// Подключаем к базе данных
$pdo = new PDO("mysql:host=localhost;dbname=book_exchange", "root", "");

// Обработка данных с кнопкой "Без оптимизации"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['without_optimization'])) {
    echo "<h2>Данные без оптимизации:</h2>";
    showBooksWithoutOptimization($pdo);
}

// Обработка данных с кнопкой "С оптимизацией"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['with_optimization'])) {
    echo "<h2>Данные с оптимизацией:</h2>";
    showBooksWithOptimization($pdo);
}

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

    <!-- Форма с кнопками для вывода данных с и без оптимизации -->
    <form method="POST">
        <button type="submit" name="without_optimization">Вывести данные без оптимизации</button>
        <button type="submit" name="with_optimization">Вывести данные с оптимизацией</button>
    </form>
    <button onclick="showBooks()">Показать книги</button>
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
<script>
    function showBooks() {
        // Делаем таблицу видимой
        document.getElementById('booksTable').style.display = 'block';
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
