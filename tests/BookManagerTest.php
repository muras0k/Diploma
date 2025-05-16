<?php
use PHPUnit\Framework\TestCase;
//use config\BookManager;
require_once 'config/db.php';
require_once 'config/BookManager.php';
require_once __DIR__ . '/../vendor/autoload.php';
class BookManagerTest extends TestCase
{
    private $pdoMock;
    private $bookManager;

    protected function setUp(): void
    {
        // Создаем мок PDO
        $this->pdoMock = $this->createMock(PDO::class);

        // Создаем экземпляр BookManager с моком базы данных
        $this->bookManager = new BookManager($this->pdoMock);
    }

    public function testAddBookSuccess()
    {
        $this->pdoMock->expects($this->exactly(2)) // Ожидаем два SQL-запроса (вставка книги и обновление места)
            ->method('prepare')
            ->willReturn($this->createMock(PDOStatement::class));

        $result = $this->bookManager->addBook(
            'Название книги',
            'Автор',
            'Описание',
            'left',
            1, // ID пользователя
            2, // ID места
            'Жанр',
            null // Без фото
        );

        $this->assertTrue($result);
    }

    public function testValidateFileSuccess()
    {
        $file = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '/tmp/php12345',
            'error' => UPLOAD_ERR_OK,
            'size' => 102400,
        ];

        

        // Мокаем существование файла
        $this->assertEquals(
            file_get_contents('/tmp/php12345'),
            $this->bookManager->validateFile($file)
        );
    }

    public function testValidateFileInvalidType()
    {
        $file = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => '/tmp/php12345',
            'error' => UPLOAD_ERR_OK,
            'size' => 102400,
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Неверный тип файла. Допустимые типы: PNG, JPG, JPEG.");
        $this->bookManager->validateFile($file);
    }

 
    public function testDeleteNonExistingBook()
    {
        // Пытаемся удалить книгу с id 999, которой нет в базе
    
        // Мокаем результат для выполнения запроса SELECT
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->method('fetchColumn')->willReturn(0); // Книга не найдена (вернется 0)
    
        // Мокаем результат для выполнения запроса DELETE
        $this->pdoMock->method('prepare')
                      ->willReturn($pdoStatementMock);
    
        $result = $this->bookManager->deleteBook(999);
        
        // Поскольку книга не существует, в тесте должно произойти исключение
        $this->assertFalse($result);
    }
    








    // public function testDeleteAllBooksFails()
    // {
    //     // Создаем мок PDOStatement для SELECT COUNT(*)
    //     $stmtMock = $this->createMock(PDOStatement::class);
    //     $stmtMock->expects($this->once())
    //         ->method('fetchColumn')
    //         ->willReturn(1); // Допустим, есть 1 книга
    
    //     // Настроим мок PDO, чтобы query() возвращал наш мок stmt
    //     $this->pdoMock->expects($this->once())
    //         ->method('query')
    //         ->with("SELECT COUNT(*) FROM books")
    //         ->willReturn($stmtMock);
    
    //     // Настроим prepare() для DELETE
    //     $stmtMockDelete = $this->createMock(PDOStatement::class);
    //     $stmtMockDelete->expects($this->once())->method('execute');
    
    //     $this->pdoMock->expects($this->once())
    //         ->method('prepare')
    //         ->with("DELETE FROM books")
    //         ->willReturn($stmtMockDelete);
    
    //     // Вызываем функцию
    //     $result = $this->bookManager->deleteAllBooks();
    
    //     // Ожидаем, что функция пройдет успешно
    //     $this->assertTrue($result, "Функция должна успешно удалить книги.");
    // }
    
    

}
