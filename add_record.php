<?php
session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $description = trim($_POST['description']);
    $action = $_POST['action'];
    $place_id = $_POST['place'];
    $user_id = $_SESSION['user_id'];
    $created_at = date("Y-m-d H:i:s");

    try {
        $stmt = $pdo->prepare("
            INSERT INTO books (title, author, description, action, owner_id, created_at, place_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $author, $description, $action, $user_id, $created_at, $place_id]);

        header('Location: dashboard.php');
        exit();
    } catch (PDOException $e) {
        die("Ошибка при добавлении записи: " . $e->getMessage());
    }
}
?>
