<?php
session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $place_id = (int)$_POST['place_id'];
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    $created_at = date('Y-m-d H:i:s');

    if ($rating < 1 || $rating > 5 || empty($comment)) {
        $_SESSION['message'] = "Неверная форма отзыва.";
        header("Location: places.php");
        exit();
    }
// Ошибка: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'details' in 'field list'
    try {
        $stmt = $pdo->prepare("INSERT INTO places_reviews (user_id, place_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $place_id, $rating, $comment, $created_at]);

        // // логирование
        // $log = $pdo->prepare("INSERT INTO user_logs (user_id, action_type, place_id) VALUES (?, 'left_review', ?)");
        // $log->execute([$user_id, "place_id=$place_id"]);

        $_SESSION['message'] = "Отзыв добавлен.";
    } catch (PDOException $e) {
        $_SESSION['message'] = "Ошибка: " . $e->getMessage();
    }
}

header("Location: places.php");
exit();
