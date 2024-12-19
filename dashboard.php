<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/header.php';
require_once 'config/db.php';

// Получение всех записей из таблицы books с данными о местах
try {
    $stmt = $pdo->query("
        SELECT b.title, b.author, b.description, b.action, b.created_at, p.name AS place_name, u.username
        FROM books b
        LEFT JOIN places p ON b.place_id = p.id
        LEFT JOIN users u ON b.owner_id = u.id
        ORDER BY b.created_at DESC
    ");
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка при загрузке данных: " . $e->getMessage());
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

    <!-- Таблица с данными -->
    <table class="dashboard-table">
        <thead>
            <!-- <tr>
                <th>Название</th>
                <th>Автор</th>
                <th>Описание</th>
                <th>Действие</th>
                <th>Место</th>
                <th>Пользователь</th>
                <th>Дата добавления</th>
            </tr> -->
        </thead>
        <tbody>
            <?php foreach ($books as $book): ?>
                <tr>
                    <td><?php echo htmlspecialchars($book['title']); ?></td>
                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                    <td><?php echo htmlspecialchars($book['description']); ?></td>
                    <td><?php echo htmlspecialchars($book['action'] === 'left' ? 'Оставил' : 'Забрал'); ?></td>
                    <td><?php echo htmlspecialchars($book['place_name']); ?></td>
                    <td><?php echo htmlspecialchars($book['username']); ?></td>
                    <td><?php echo htmlspecialchars($book['created_at']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
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
    .dashboard-table {
        width: 100%;
        border-collapse: collapse;
    }
    .dashboard-table th, .dashboard-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    .dashboard-table th {
        background-color: #f2f2f2;
    }
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
