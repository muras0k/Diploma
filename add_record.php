<?php
require_once 'config/db.php';
require_once 'config/BookManager.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $manager = new BookManager($pdo);

    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $description = trim($_POST['description']);
    $action = $_POST['action'];
    $place_id = $_POST['place'];
    $user_id = $_SESSION['user_id'];
    $genre = trim($_POST['genre']);
    $photo = null;

    try {
        // Проверка и загрузка файла
        if (isset($_FILES['book_photo'])) {
            $photo = $manager->validateFile($_FILES['book_photo']);
        }

        // Добавление книги
        $manager->addBook($title, $author, $description, $action, $user_id, $place_id, $genre, $photo);

        $_SESSION['message'] = "Книга успешно добавлена.";
    } catch (Exception $e) {
        $_SESSION['message'] = $e->getMessage();
    }

    header('Location: dashboard.php');
    exit();
}


