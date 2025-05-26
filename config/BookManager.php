<?php
//namespace config;
require_once 'config/db.php';
class BookManager
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function addBook($title, $author, $description, $action, $user_id, $place_id, $genre, $photo)
    {
        $created_at = date("Y-m-d H:i:s");
    
        try {
            // Добавление книги в базу данных
            $stmt = $this->pdo->prepare("
                INSERT INTO books (title, author, description, action, owner_id, created_at, place_id, genre, photo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $author, $description, $action, $user_id, $created_at, $place_id, $genre, $photo]);
    
            // Получаем ID добавленной книги
            $book_id = $this->pdo->lastInsertId();
    
            // Увеличение популярности места
            $stmt = $this->pdo->prepare("UPDATE places SET usage_count = usage_count + 1 WHERE id = ?");
            $stmt->execute([$place_id]);
    
            // Логирование действия
            $action_type = ($action === 'left') ? 'added_book' : 'took_book';
            $stmt = $this->pdo->prepare("
                INSERT INTO user_logs (user_id, action_type, book_id, place_id, action_time)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $action_type, $book_id, $place_id, $created_at]);
    
            return true;
        } catch (PDOException $e) {
            throw new Exception("Ошибка при добавлении записи: " . $e->getMessage());
        }
    }
    


    public function validateFile($file)
    {
        $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
        $maxSize = 2 * 1024 * 1024; // 2 MB

        if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null; // Фото не выбрано
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Ошибка загрузки файла. Код ошибки: " . $file['error']);
        }

        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception("Неверный тип файла. Допустимые типы: PNG, JPG, JPEG.");
        }

        if ($file['size'] > $maxSize) {
            throw new Exception("Размер файла превышает 2 MB.");
        }

        // if (!@getimagesize($file['tmp_name'])) {
        //     throw new Exception("Файл поврежден или не является изображением.");
        // }

        return file_get_contents($file['tmp_name']); // Возвращаем содержимое файла
    }



    // public function deleteBook($book_id)
    // {
    //     try {
    //         // Просто удаляет книгу
    //         $stmt = $this->pdo->prepare("DELETE FROM books WHERE id = ?");
    //         $stmt->execute([$book_id]);
    
    //         return true;
    //     } catch (PDOException $e) {
    //         throw new Exception("Ошибка при удалении книги: " . $e->getMessage());
    //     }
    // }
    


    public function deleteBook($book_id)
    {
        try {
            // Проверяем, существует ли книга с таким ID
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM books WHERE id = ?");
            $stmt->execute([$book_id]);
            $count = $stmt->fetchColumn();
    
            // Если книга не существует, возвращаем false
            if ($count == 0) {
                return false;
            }
    
            // Удаляем книгу
            $stmt = $this->pdo->prepare("DELETE FROM books WHERE id = ?");
            $stmt->execute([$book_id]);
    
            return true;
        } catch (PDOException $e) {
            throw new Exception("Ошибка при удалении книги: " . $e->getMessage());
        }
    }
}
