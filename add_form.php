<?php
require_once 'config/db.php';

// Загрузка всех мест
try {
    $stmt = $pdo->query("SELECT id, name, usage_count FROM places ORDER BY usage_count DESC, name ASC");
    $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка при загрузке мест: " . $e->getMessage());
}

// Получаем рекомендованные места по жанрам
$recommendedByGenre = [];
try {
    $stmt = $pdo->query("
        SELECT genre, place_id, COUNT(*) as count
        FROM books
        GROUP BY genre, place_id
        ORDER BY genre, count DESC
    ");
    $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($raw as $row) {
        $genre = trim(mb_strtolower($row['genre']));
        if (!isset($recommendedByGenre[$genre])) {
            $recommendedByGenre[$genre] = $row['place_id'];
        }
    }
} catch (PDOException $e) {
    $recommendedByGenre = [];
}

// Получаем id самого популярного места по общему использованию
$defaultPlaceId = count($places) > 0 ? $places[0]['id'] : null;
?>

<link rel="stylesheet" href="assets/css/main.css">

<form method="POST" id="form" enctype="multipart/form-data" action="add_record.php">
    <h2>Добавить запись</h2>

    <!-- Жанр -->
    <label for="genre">Жанр:</label>
    <select id="genre" name="genre" required>
        <option value="">Выберите жанр</option>
        <?php
        $genres = ["Фантастика", "Фэнтези", "Детектив", "Роман", "Научная литература", "История", "Биография", "Путешествия", "Ужасы", "Юмор", "Поэзия", "Драма"];
        foreach ($genres as $g) {
            echo "<option value=\"" . htmlspecialchars($g) . "\">$g</option>";
        }
        ?>
    </select>

    <button type="button" id="recommendBtn" disabled>Рекомендовать место</button>

    <!-- Место -->
    <label for="place">Место:</label>
    <select id="place" name="place" required>
        <option value="">Выберите место</option>
        <?php foreach ($places as $place): ?>
            <option value="<?= $place['id'] ?>">
                <?= htmlspecialchars($place['name']) ?> (<?= $place['usage_count'] ?> раз)
            </option>
        <?php endforeach; ?>
    </select>

    <!-- Остальная форма... -->
    <label for="title">Название книги:</label>
    <input type="text" id="title" name="title" required>

    <label for="author">Автор:</label>
    <input type="text" id="author" name="author" required>

    <label for="description">Описание:</label>
    <textarea id="description" name="description" required></textarea>

    <label>Действие:</label>
    <div>
        <label><input type="radio" name="action" value="left" checked> Оставил</label>
        <label><input type="radio" name="action" value="taken"> Забрал</label>
    </div>

    <label>Фото книги:
        <input type="file" name="book_photo" id="book_photo" accept=".png, .jpg, .jpeg">
    </label><br>

    <button type="submit">Добавить запись</button>
</form>

<script>
    const recommended = <?php echo json_encode($recommendedByGenre, JSON_UNESCAPED_UNICODE); ?>;
    const defaultPlaceId = <?php echo json_encode($defaultPlaceId); ?>;

    const genreSelect = document.getElementById('genre');
    const placeSelect = document.getElementById('place');
    const recommendBtn = document.getElementById('recommendBtn');

    // Активируем кнопку после выбора жанра
    genreSelect.addEventListener('change', () => {
        recommendBtn.disabled = !genreSelect.value;
    });

    // При нажатии "Рекомендовать"
    recommendBtn.addEventListener('click', () => {
        const genre = genreSelect.value.trim().toLowerCase();

        let placeId = recommended[genre] || defaultPlaceId;


        if (placeId) {
            placeSelect.value = placeId;
            placeSelect.classList.add('highlight');
            setTimeout(() => placeSelect.classList.remove('highlight'), 1500);
        } else {
            alert('Не удалось подобрать место.');
        }
    });



</script>

<style>
    select.highlight {
        background-color: #d7ffd7;
        transition: background-color 1s;
    }
</style>
