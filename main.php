<?php

require_once 'config/header.php';
require_once 'config/db.php';

$currentDate = date('Y-m-d H:i:s');
$monthAgo = date('Y-m-d H:i:s', strtotime('-1 month'));

// Подготовка запроса для новых пользователей
$stmtNewUsers = $pdo->prepare("
    SELECT COUNT(*) FROM user_logs
    WHERE action_type = 'registered' AND action_time >= :monthAgo
");
$stmtNewUsers->execute(['monthAgo' => $monthAgo]);
$newUsers = $stmtNewUsers->fetchColumn();

// Книги, добавленные за последний месяц
$stmtAddedBooks = $pdo->prepare("
    SELECT COUNT(*) FROM user_logs
    WHERE action_type = 'added_book' AND action_time >= :monthAgo
");
$stmtAddedBooks->execute(['monthAgo' => $monthAgo]);
$addedBooks = $stmtAddedBooks->fetchColumn() + 10;

// Книги, взятые за последний месяц
$stmtTookBooks = $pdo->prepare("
    SELECT COUNT(*) FROM user_logs
    WHERE action_type = 'took_book' AND action_time >= :monthAgo
");
$stmtTookBooks->execute(['monthAgo' => $monthAgo]);
$tookBooks = $stmtTookBooks->fetchColumn();

?>



  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Костянка - лучший сайт обмена книг</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/main.css">
  </head>
  <body>
    
</div>
    <main class="main_window">

        <div class="content">
        <h1 class="page_title">Добро пожаловать на Костянку!</h1>
        <div class="about_content">
            <label style=" margin-left: 20px;">Что такое Буккроссинг?</label>
            <p>Буккроссинг (bookcrossing) возник в 2001 году по инициативе специалиста по интернет-технологиям, американца Рона Хорнбэкера.
                Движение из США переместилось в Европу и было тепло встречено в Италии, затем во Франции и по всей Европе, вплоть до
                Финляндии. В общем же, сейчас по миру в системе распространения книг зарегистрировано более 2 миллионов участников и 10 миллионов книг.
            </p>
            <p>Процесс буккроссинга выглядит так: зарегистрировав себя и присвоив книге специальный номер вы оставляете ее в заранее обдуманном месте (кафе, парке, вокзале, автобусе…), где любой человек может взять и прочитать ее. Таким образом мы «освобождаем» книги, спасаем от стояния на полке. Бывший же обладатель книги, будет всегда знать о перемещении своего «питомца», получая e-mail о том, в чьи руки она попала, и как она там очутилась. Второй, и побочной, целью является превращение всего мира в "огромную библиотеку".</p>
            <p>Только подумайте об этом, что лет через пять после регистрации книги к вам приходит на e-mail подтверждение того, что ваша книга была найдена, и человек, нашедший ее, может находиться где угодно, даже на другом конце света (мы сотрудничаем с подобными движениями по всему миру)! Представьте, как вы обрадуетесь, вспомните о том времени, когда оставили книгу и подумаете, сколько всего произошло за целых 5 лет. Каждая «отпущенная» вами книга, как сообщение в бутылке, весть о том, что оно было найдено, может сделать вас счастливее, познакомить вас с интересными людьми или вообще перевернуть вашу жизнь с ног на голову.</p>        
        </div>
        </div>>
        <p>За последний месяц зарегистрировалось <strong><?= $newUsers ?></strong> новых пользователей.</p>
        <p>Добавлено <strong><?= $addedBooks ?></strong> книг и взято <strong><?= $tookBooks ?></strong>.</p>>
    </main>
    

  </body>
</html>
