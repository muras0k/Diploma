<?php

require_once 'config/db.php';
require_once 'config/header.php';

session_start();

$place_id = isset($_GET['place_id']) ? (int)$_GET['place_id'] : 0;

if ($place_id <= 0) {
    echo "Некорректный ID места.";
    exit();
}

// Получаем отзывы
$stmt = $pdo->prepare("
    SELECT r.rating, r.comment, r.created_at, u.username
    FROM places_reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.place_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$place_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем данные о месте
$placeStmt = $pdo->prepare("SELECT name FROM places WHERE id = ?");
$placeStmt->execute([$place_id]);
$place = $placeStmt->fetch(PDO::FETCH_ASSOC);
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Места для обмена книг</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/main.css">
</head>

<h1>Отзывы о полке "<?php echo htmlspecialchars($place['name']); ?>"</h1>

<?php if (empty($reviews)): ?>
    <p>Пока нет отзывов.</p>
<?php else: ?>
    <?php foreach ($reviews as $review): ?>
        <div class="review-item">
            <p><strong><?php echo htmlspecialchars($review['username']); ?></strong> (<?php echo $review['rating']; ?>/5)</p>
            <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
            <p><?php echo $review['created_at']; ?></p>
            <hr>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (isset($_SESSION['user_id'])): ?>
    <h2>Оставить отзыв:</h2>
    <form action="add_review.php" method="post">
        <input type="hidden" name="place_id" value="<?php echo $place_id; ?>">
        <label for="rating">Оценка:</label>
        <select name="rating" id="rating" required>
            <option value="5">5 — Отлично</option>
            <option value="4">4 — Хорошо</option>
            <option value="3">3 — Нормально</option>
            <option value="2">2 — Плохо</option>
            <option value="1">1 — Ужасно</option>
        </select><br><br>
        <textarea name="comment" placeholder="Ваш отзыв..." required></textarea><br>
        <button type="submit">Отправить</button>
    </form>
<?php else: ?>
    <p>Войдите, чтобы оставить отзыв.</p>
<?php endif; ?>
