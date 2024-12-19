<?php
require_once 'config/db.php';

// Получаем список мест из базы данных
try {
    $stmt = $pdo->query("SELECT id, name FROM places");
    $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка при загрузке мест: " . $e->getMessage());
}
?>

<form method="POST" action="add_record.php">
    <h2>Добавить запись</h2>

    <label for="title">Название книги:</label>
    <input type="text" id="title" name="title" required>

    <label for="author">Автор:</label>
    <input type="text" id="author" name="author" required>

    <label for="description">Описание:</label>
    <textarea id="description" name="description" required></textarea>

    <label>Действие:</label>
    <div>
        <label>
            <input type="radio" name="action" value="left" checked>
            Оставил
        </label>
        <label>
            <input type="radio" name="action" value="taken">
            Забрал
        </label>
    </div>

    <label for="place">Место:</label>
    <select id="place" name="place" required>
        <option value="" selected>Выберите место</option>
        <?php foreach ($places as $place): ?>
            <option value="<?php echo $place['id']; ?>"><?php echo htmlspecialchars($place['name']); ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Добавить запись</button>
</form>
