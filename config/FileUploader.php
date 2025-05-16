<?php
namespace config;
class FileUploader {
    private $file;
    private $uploadDir;
    private $allowedTypes;
    private $maxSize;
    private $errorMessage;

    public function __construct($file, $uploadDir, $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'], $maxSize = 2 * 1024 * 1024) {
        $this->file = $file;
        $this->uploadDir = $uploadDir;
        $this->allowedTypes = $allowedTypes;
        $this->maxSize = $maxSize;
    }

    // Проверка наличия файла
    public function isFilePresent() {
        if ($this->file['error'] === UPLOAD_ERR_NO_FILE) {
            $this->errorMessage = "Ошибка: файл не был выбран.";
            return false;
        }
        return true;
    }

    // Проверка на другие ошибки загрузки
    public function hasUploadError() {
        if ($this->file['error'] !== UPLOAD_ERR_OK) {
            $this->errorMessage = "Ошибка загрузки файла: " . $this->file['error'];
            return true;
        }
        return false;
    }

    // Проверка файла на повреждения и его получение
    public function isFileValid() {
        if (empty($this->file['tmp_name']) ) { //|| !is_uploaded_file($this->file['tmp_name']) ДЛЯ ТЕСТА УБИРАЮ ПРОВЕРКУ НА ПОЛУЧЕНИЕ ФАЙЛА ИЗ СЕТИ
            $this->errorMessage = "Ошибка: файл не был получен сервером или был удален.";
            return false;
        }

        // Проверка на наличие файла на сервере (ситуация, когда сервер не может получить файл клиента)
        if (!file_exists($this->file['tmp_name']) || !is_readable($this->file['tmp_name'])) {
            $this->errorMessage = "Ошибка: сервер не может получить файл или файл поврежден.";
            return false;
        }

        // Дополнительная проверка: попытка открыть файл для подтверждения целостности
        if (!$this->isImageFile($this->file['tmp_name'])) {
            $this->errorMessage = "Ошибка: файл поврежден или не является изображением.";
            return false;
        }

        return true;
    }

    // Проверка, является ли файл изображением (чтобы убедиться, что файл не поврежден)
    private function isImageFile($filePath) {
        $image = @getimagesize($filePath);
        return $image !== false;
    }

    // Проверка на допустимый тип файла
    public function isValidType() {
        if (!in_array($this->file['type'], $this->allowedTypes)) {
            $this->errorMessage = "Недопустимый тип файла. Допустимые форматы: PNG, JPG, JPEG.";
            return false;
        }
        return true;
    }

    // Проверка размера файла
    public function isValidSize() {
        if ($this->file['size'] > $this->maxSize) {
            $this->errorMessage = "Размер файла превышает " . ($this->maxSize / (1024 * 1024)) . " MB.";
            return false;
        }
        return true;
    }

    // Генерация уникального имени для файла
    public function generateFileName() {
        return uniqid('avatar_', true) . '.' . pathinfo($this->file['name'], PATHINFO_EXTENSION);
    }

    // Перемещение файла в нужную папку
    public function moveFile($newFileName) {
        $filePath = $this->uploadDir . $newFileName;

        if (!move_uploaded_file($this->file['tmp_name'], $filePath)) {
            $this->errorMessage = "Ошибка при сохранении файла.";
            return false;
        }

        // Дополнительная проверка, если файл перемещен, но поврежден
        if (!file_exists($filePath) || !is_readable($filePath)) {
            $this->errorMessage = "Ошибка: файл поврежден или не существует по указанному пути.";
            return false;
        }

        return $filePath;
    }

    // Получение ошибки, если она есть
    public function getErrorMessage() {
        return $this->errorMessage;
    }
}
?>
