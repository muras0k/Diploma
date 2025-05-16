<?php
session_start();
ini_set('display_errors', 0);

$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? 'user';

require_once 'config/header.php';
require_once 'config/db.php';

$weights = require 'config/weights.php';

// Функция для сохранения поисковых запросов
function saveSearchQuery($field, $value)
{
    global $user_id;

    if (!$value || !$user_id) return; // Не сохраняем пустые запросы или для незалогиненных пользователей

    $cookieName = "search_queries";
    $existing = isset($_COOKIE[$cookieName]) ? json_decode($_COOKIE[$cookieName], true) : [];
    if (!isset($existing[$user_id])) {
        $existing[$user_id] = [];
    }
    if (!isset($existing[$user_id][$field])) {
        $existing[$user_id][$field] = [];
    }
    $existing[$user_id][$field][] = $value;
    $existing[$user_id][$field] = array_unique($existing[$user_id][$field]); // Убираем дубли

    setcookie($cookieName, json_encode($existing), time() + 3600 * 24 * 30, '/'); // Срок хранения 30 дней
}

// Функция для получения подсказок
function getSuggestions($field)
{
    global $user_id;

    if (!$user_id) return []; // Подсказки только для залогиненных пользователей

    $cookieName = "search_queries";
    $existing = isset($_COOKIE[$cookieName]) ? json_decode($_COOKIE[$cookieName], true) : [];
    return $existing[$user_id][$field] ?? [];
}

// Переменные для поиска
$title = $_POST['title'] ?? '';
$author = $_POST['author'] ?? '';
$on_shelf = isset($_POST['on_shelf']) ? 1 : 0;

// Сохраняем поисковые запросы
saveSearchQuery('title', $title);
saveSearchQuery('author', $author);

// Базовый запрос для получения всех книг
$query = "
    SELECT 
        books.*,
        places.usage_count AS place_popularity
    FROM books
    LEFT JOIN places ON books.place_id = places.id
    WHERE 1
";
$params = [];

// Фильтры
if ($on_shelf) {
    $query .= " AND action = 'left'";
}

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

// Функция для расчета веса книги
function calculateWeight($book, $weights)
{
    $weight = 0;

    $weight += $book['action'] === 'left' ? $weights['action']['left'] : $weights['action']['taken'];
    $weight += $book['place_popularity'] * $weights['place_popularity'];
    $genre = $book['genre'];
    $weight += $weights['genre'][$genre] ?? $weights['genre']['default'];
    $recent = strtotime($book['added_time']) > strtotime('-1 week');
    $weight += $recent ? $weights['recent_time'] : 0;

    return $weight;
}

// Рассчитать вес для каждой книги
foreach ($books as &$book) {
    $book['weight'] = calculateWeight($book, $weights);
}
unset($book);

// Сортировка по весу
usort($books, function ($a, $b) {
    return $b['weight'] <=> $a['weight'];
});

// Проверка удаления
if (isset($_POST['delete_id'])) {
    if ($role === 'admin') {
        $delete_id = $_POST['delete_id'];

        try {
            $deleteQuery = "DELETE FROM books WHERE id = ?";
            $stmt = $pdo->prepare($deleteQuery);
            $stmt->execute([$delete_id]);
            header('Location: ' . $_SERVER['PHP_SELF']);
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

$suggestionsTitle = getSuggestions('title');
$suggestionsAuthor = getSuggestions('author');
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поиск книг</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const titleInput = document.getElementById('title');
            const authorInput = document.getElementById('author');
            
            function showSuggestions(input, suggestions) {
                const container = document.createElement('div');
                container.classList.add('suggestions');

                suggestions.forEach(suggestion => {
                    const item = document.createElement('div');
                    item.textContent = suggestion;
                    item.addEventListener('click', () => {
                        input.value = suggestion;
                        container.remove();
                    });
                    container.appendChild(item);
                });

                input.parentNode.appendChild(container);
            }

            titleInput.addEventListener('focus', () => {
                const suggestions = <?php echo json_encode($suggestionsTitle); ?>;
                showSuggestions(titleInput, suggestions);
            });

            authorInput.addEventListener('focus', () => {
                const suggestions = <?php echo json_encode($suggestionsAuthor); ?>;
                showSuggestions(authorInput, suggestions);
            });
        });
    </script>
    <style>
        .suggestions {
            position: absolute;
            background: white;
            border: 1px solid #ccc;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
        }
        .suggestions div {
            padding: 5px;
            cursor: pointer;
        }
        .suggestions div:hover {
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>

<main class="main_window">

<form class="search-box" method="POST" action="">
    <label for="title">Название книги:</label>
    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>">
    <label for="author">Автор:</label>
    <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($author); ?>">
    <button type="submit">Найти</button>
    <input type="checkbox" id="on_shelf" name="on_shelf" <?php echo $on_shelf ? 'checked' : ''; ?>>
    <label for="on_shelf">Только книги на полках</label>
</form>

<h2>Книги</h2>

<?php if (empty($books)): ?>
    <p>Книги не найдены по указанным критериям.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Фото</th>
                <th>Название</th>
                <th>Автор</th>
                <th>Жанр</th>
                <?php if ($role === 'admin'): ?>
                    <th>Вес</th>
                    <th>Действие</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($books as $book): ?>
                <tr>
                    <td><img src="<?= $book['photo'] ? 'data:image/jpeg;base64,' . base64_encode($book['photo']) : 'assets/images/default_book.png' ?>" 
                     alt="Фото книги" style="max-width: 150px; max-height: 150px;"></td>
                    <td><?php echo htmlspecialchars($book['title']); ?></td>
                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                    <td><?php echo htmlspecialchars($book['genre']); ?></td>
                    <?php if ($role === 'admin'): ?>
                        <td><?php echo htmlspecialchars($book['weight']); ?></td>
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
