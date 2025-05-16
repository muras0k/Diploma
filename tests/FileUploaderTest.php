<?php

use PHPUnit\Framework\TestCase;
use config\FileUploader;
require_once __DIR__ . '/../vendor/autoload.php';
class FileUploaderTest extends TestCase
{
    private $uploadDir = __DIR__ . '/uploads/'; // Тестовая папка для загрузки

    protected function setUp(): void
    {
        // Убедитесь, что тестовая папка существует
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }

        // Проверка на доступность директории
        //var_dump(is_writable($this->uploadDir)); // Проверка, доступна ли директория для записи
        //var_dump(is_dir($this->uploadDir)); // Проверка, существует ли директория
    }

    public function testIsFilePresent()
    {
        $file = ['error' => UPLOAD_ERR_NO_FILE];
        $uploader = new FileUploader($file, $this->uploadDir);

        $this->assertFalse($uploader->isFilePresent());
        $this->assertEquals("Ошибка: файл не был выбран.", $uploader->getErrorMessage());
    }

    public function testHasUploadError()
    {
        $file = ['error' => UPLOAD_ERR_CANT_WRITE];
        $uploader = new FileUploader($file, $this->uploadDir);

        $this->assertTrue($uploader->hasUploadError());
        $this->assertEquals("Ошибка загрузки файла: 7", $uploader->getErrorMessage());
    }



    public function testIsFileInvalidDueToMissingTmpFile()
    {
        $file = [
            'tmp_name' => '/nonexistent/file/path',
            'error' => UPLOAD_ERR_OK
        ];
        $uploader = new FileUploader($file, $this->uploadDir);

        $this->assertFalse($uploader->isFileValid());
        $this->assertEquals("Ошибка: сервер не может получить файл или файл поврежден.", $uploader->getErrorMessage());
    }

    public function testIsValidType()
    {
        $file = ['type' => 'image/jpeg'];
        $uploader = new FileUploader($file, $this->uploadDir);

        $this->assertTrue($uploader->isValidType());
    }

    public function testInvalidType()
    {
        $file = ['type' => 'text/plain'];
        $uploader = new FileUploader($file, $this->uploadDir);

        $this->assertFalse($uploader->isValidType());
        $this->assertEquals("Недопустимый тип файла. Допустимые форматы: PNG, JPG, JPEG.", $uploader->getErrorMessage());
    }

    public function testIsValidSize()
    {
        $file = ['size' => 1024 * 1024]; // 1 MB
        $uploader = new FileUploader($file, $this->uploadDir);

        $this->assertTrue($uploader->isValidSize());
    }

    public function testInvalidSize()
    {
        $file = ['size' => 3 * 1024 * 1024]; // 3 MB
        $uploader = new FileUploader($file, $this->uploadDir);

        $this->assertFalse($uploader->isValidSize());
        $this->assertEquals("Размер файла превышает 2 MB.", $uploader->getErrorMessage());
    }



    public function testMoveFileFailure()
    {
        $file = [
            'tmp_name' => '/nonexistent/file/path',
            'name' => 'test.jpg',
            'error' => UPLOAD_ERR_OK
        ];

        $uploader = new FileUploader($file, $this->uploadDir);
        $newFileName = $uploader->generateFileName();
        $result = $uploader->moveFile($newFileName);

        $this->assertFalse($result);
        $this->assertEquals("Ошибка при сохранении файла.", $uploader->getErrorMessage());
        $this->assertFileDoesNotExist($file['tmp_name']); // Убедитесь, что файл не существует

    }



}
