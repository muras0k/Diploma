<?php
require_once 'config/db.php';


try {
    $stmt = $pdo->query("SELECT id, name, usage_count FROM places ORDER BY usage_count DESC, name ASC");
    $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка при загрузке мест: " . $e->getMessage());
}
?>
<link rel="stylesheet" href="assets/css/main.css">
<form method="POST" id="form" enctype="multipart/form-data" action="add_record.php">
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

    <label>Фото книги (необязательно, до 2 Mb, форматы png, jpeg, jpg): 
        <input type="file" name="book_photo" id="book_photo" accept=".png, .jpg, .jpeg">
    </label><br>

    <label for="place">Место:</label>
    <select id="place" name="place" required>
        <option value="" selected>Выберите место</option>
        <?php foreach ($places as $place): ?>
            <option value="<?php echo $place['id']; ?>">
                <?php echo htmlspecialchars($place['name']) . " (" . $place['usage_count'] . " раз)"; ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="genre">Жанр:</label>
    <select id="genre" name="genre" required>
        <option value="" selected>Выберите жанр</option>
        <option value="Фантастика">Фантастика</option>
        <option value="Фэнтези">Фэнтези</option>
        <option value="Детектив">Детектив</option>
        <option value="Роман">Роман</option>
        <option value="Научная литература">Научная литература</option>
        <option value="История">История</option>
        <option value="Биография">Биография</option>
        <option value="Путешествия">Путешествия</option>
        <option value="Ужасы">Ужасы</option>
        <option value="Юмор">Юмор</option>
        <option value="Поэзия">Поэзия</option>
        <option value="Драма">Драма</option>
    </select>

    <button type="submit">Добавить запись</button>
</form>

<script>
document.getElementById('form').addEventListener('submit', function(e) {
    const bookPhotoInput = document.getElementById('book_photo');
    const bookPhotoFile = bookPhotoInput.files[0];  // Получаем файл

    // Проверяем, был ли файл выбран и существует ли он (не пустой)
    if (bookPhotoFile && bookPhotoFile.size === 0) {
        e.preventDefault();  // Отменяем отправку формы
        alert('Пожалуйста, выберите действительный файл для фото книги.');
    }
});
</script>
