<header class="header">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <div class="pic">
        <img src="assets/images/logo.png" alt="logo" class="logo"/>
        <span class="logo_text">Костянка</span>
    </div>
    <nav class="nav-buttons">
        <?php if (isset($_SESSION['user_id'])): ?>
            <button onclick="window.location.href='user_page.php'" class="button">Личный кабинет</button>
            <button onclick="window.location.href='logout.php'" class="button">Выйти</button>
        <?php else: ?>
            <button onclick="redirectToPage(1)" class="button">Войти</button>
            <button onclick="redirectToPage(2)" class="button">Регистрация</button>
        <?php endif; ?>
    </nav>
</header>
<body>
<div class="navigation">
        <button onclick="redirectToPage(3)" class="nav-button">Охота</button>
        <button onclick="redirectToPage(4)" class="nav-button">Книги</button>
        <button onclick="redirectToPage(5)" class="nav-button">Места</button>
        <button onclick="redirectToPage(6)" class="nav-button">О буккроссинге</button>
    </div>
</body>
<script>
    var num;
    function redirectToPage(num) {
        switch(num){
        case 1:window.location.href = "login.php";break;
        case 2:window.location.href = "register.php";break;
        case 3:window.location.href = "dashboard.php";break;
        case 4:window.location.href = "search.php";break;
        case 5:window.location.href = "places.php";break;
        case 6:window.location.href = "main.php";break;
        default:console.error("Неверный номер страницы: " + num);break;
    }
    }
    </script>
