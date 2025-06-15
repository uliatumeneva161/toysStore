<?php
// Обработка формы комментариев
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['name'])) {
    try {
        // Создаем соединение
        $conn = new mysqli("MySQL-8.2", "root", "", "redenok_bd");
        // Проверяем соединение
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        // Получаем данные из формы
        $name = trim($_POST['name']);
        $comment = trim($_POST['comment']);
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        // Проверяем, что поля не пустые
        if (empty($name) || empty($comment) || $rating == 0) {
            throw new Exception("Все поля обязательны для заполнения, включая оценку");
        }
        // Подготовленный запрос для безопасности
        $stmt = $conn->prepare("INSERT INTO comments (name, comment, rating) VALUES (?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ssi", $name, $comment, $rating);
        if ($stmt->execute()) {
            $success_message = "Спасибо за ваш отзыв и оценку!";
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_uri = rtrim($request_uri, '/') ?: '/';

// Разрешенные страницы (без .php в URI)
$allowed_routes = [
    '/index' => true,        // Главная
    '/admin' => true,   // Админка
    // Другие маршруты
];
// Проверка маршрута
if ($request_uri === '/index.php') {
    // Продолжаем выполнение для отображения главной страницы
} elseif ($request_uri === '/admin') {
    if (file_exists(__DIR__ . '/admin.php')) {
        include __DIR__ . '/admin.php';
        exit;
    } else {
        header("HTTP/1.0 404 Not Found");
        include __DIR__ . '/404.php';
        exit;
    }
} elseif (!isset($allowed_routes[$request_uri])) {
    header("HTTP/1.0 404 Not Found");
    include __DIR__ . '/404.php';
    exit;
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Ребенок-Рад - магазин детских товаров</title>
</head>
<body>
   <div class="background-circles">
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
        <div class="circle circle-3"></div>
    </div>
    <header>
        <div class="header-container">
            <div class="logo">
                <h1>Ребенок-Рад</h1>
                <p class="tagline">Все для счастливого детства</p>
            </div>
            <nav class="navigation">
                <a href="#home" class="nav-link">Главная</a>      
                <a href="#catalog" class="nav-link">Каталог</a>
                <a href="#promotions" class="nav-link">Акции</a>
                <a href="#contacts" class="nav-link">Контакты</a>
            </nav>
            <a href="/admin">Admin</a>
        </div>
    </header>
    <main>
        <section id="home" class="main-slogan">
            <div class="hero-image">
                <img src="./img/main2.jpg" alt="">
            </div>
            <div class="hero-content">
                <h2>Подарите счастье вашему ребенку!</h2>
                <p>Наши лучшие товары для самых маленьких героев. Качественные и безопасные детские вещи для вашего ребенка.</p>
                <a href="#catalog" class="cta-button">Перейти в каталог</a>
            </div>
        </section>
        <section id="catalog" class="catalog">
            <h2>Каталог товаров</h2>
            <div class="products">
                <?php
                // Создаем соединение
                $conn = new mysqli("MySQL-8.2", "root", "", "redenok_bd");
                // Проверяем соединение
                if ($conn->connect_error) {
                    die("Connection failed: " . $conn->connect_error);
                }
                // Получаем данные из базы данных
                $sql = "SELECT * FROM products ORDER BY id DESC";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $discount_price = $row['price'] * (1 - $row['discount'] / 100);
                        echo '<div class="product-card">';
                        if ($row['discount'] > 0) {
                            echo '<div class="sale-badge">-' . $row['discount'] . '%</div>';
                        }
                        if ($row['is_hit']) {
                            echo '<div class="sale-badge">Хит</div>';
                        }
                        echo '<img src="' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['title']) . '" class="product-image">';
                        echo '<div class="product-info">';
                        echo '<h3 class="product-title">' . htmlspecialchars($row['title']) . '</h3>';
                        echo '<p class="product-description">' . nl2br(htmlspecialchars($row['description'])) . '</p>';
                        echo '<div class="product-price">' . number_format($discount_price, 0, '', ' ') . ' ₽';
                        if ($row['discount'] > 0) {
                            echo ' <span style="text-decoration: line-through; font-size: 0.9rem; color: #999;">' . number_format($row['price'], 0, '', ' ') . ' ₽</span>';
                        }
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<p class="no-products">Пока нет товаров в каталоге.</p>';
                }
                $conn->close();
                ?>
            </div>
        </section>
        <section id="promotions" class="promotions">
            <h2>Акции и скидки</h2>
            <div class="promotion-item">
                <div class="promotion-image">
                    <img src="./img/1.jpg" alt="Скидка на коляски">
                    <img src="./img/k2.jpg" alt="Скидка на коляски">
                    <img src="./img/k3.jpg" alt="Скидка на коляски">
                </div>
                <div class="promotion-content">
                    <h3>Скидка 20% на все коляски!</h3>
                    <p>При покупке любой модели коляски вы получаете скидку 20%. Не упустите шанс подарить своему ребенку комфорт и безопасность!</p>
                </div>
            </div>
        </section>
        <section id="contacts" class="contacts">
            <h2>Контакты</h2>
            <div class="contact-info">
                <p> +7 (123) 456-78-90</p>
                <p>rebenok-rad@mail.ru</p>
                <p> г. Рязань, ул. Ленина, д. 1</p>
            </div>
            <!-- Форма для добавления комментариев -->
            <div class="comments-section">
                <h3 class="section-title">Оставьте отзыв</h3>
                <?php if(isset($success_message)): ?>
                    <div class="alert success">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                <?php if(isset($error_message)): ?>
                    <div class="alert error">
                       <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                <form class="comment-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>#contacts" method="post">
                    <div class="form-group">
                        <label for="name">Ваше имя:</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="comment">Ваш отзыв:</label>
                        <textarea id="comment" name="comment" rows="4" required></textarea>
                    </div>
                   <div class="rating-container">
    <div class="rating-title">Оцените наш магазин:</div>
    <div class="rating-stars">
        <input type="radio" id="star5" name="rating" value="5" required>
        <label for="star5">
            <svg class="star-icon" viewBox="0 0 24 24">
                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
            </svg>
        </label>
        <input type="radio" id="star4" name="rating" value="4">
        <label for="star4">
            <svg class="star-icon" viewBox="0 0 24 24">
                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
            </svg>
        </label>
        <input type="radio" id="star3" name="rating" value="3">
        <label for="star3">
            <svg class="star-icon" viewBox="0 0 24 24">
                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
            </svg>
        </label>
        <input type="radio" id="star2" name="rating" value="2">
        <label for="star2">
            <svg class="star-icon" viewBox="0 0 24 24">
                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
            </svg>
        </label>
        <input type="radio" id="star1" name="rating" value="1">
        <label for="star1">
            <svg class="star-icon" viewBox="0 0 24 24">
                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
            </svg>
        </label>
    </div>
</div>

<button type="submit" class="submit-btn">
    Отправить отзыв
</button>

<!-- Отображение комментариев -->
<div class="comments-list">
    <h3 class="section-title">Отзывы покупателей</h3>
    <?php
    $conn = new mysqli("MySQL-8.2", "root", "", "redenok_bd");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $sql = "SELECT name, comment, rating, date_added FROM comments ORDER BY date_added DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $rating = (int)$row["rating"];
            echo '<div class="comment">';
            echo '<div class="comment-header">';
            echo '<span class="comment-author">' . htmlspecialchars($row["name"]) . '</span>';
            echo '<span class="comment-date">' . date("d.m.Y", strtotime($row["date_added"])) . '</span>';
            echo '</div>';
            if($rating > 0) {
                echo '<div class="comment-rating">';
                for($i = 1; $i <= 5; $i++) {
                    if ($i <= $rating) {
                        echo '<svg class="star-icon filled" viewBox="0 0 24 24">
                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                        </svg>';
                    } else {
                        echo '<svg class="star-icon empty" viewBox="0 0 24 24">
                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                        </svg>';
                    }
                }
                echo '</div>';
            }
            echo '<div class="comment-text">' . nl2br(htmlspecialchars($row["comment"])) . '</div>';
            echo '</div>';
        }
    } else {
        echo '<p class="no-comments">Пока нет комментариев. Будьте первым!</p>';
    }
    $conn->close();
    ?>
</div>

    </main>
    <footer>
        <p>© 2025 Ребенок-Рад. Все права защищены.</p>
    </footer>
    <script src="https://kit.fontawesome.com/a076d05399.js"   crossorigin="anonymous"></script>
    <script>
        // Плавный скролл к якорным ссылкам
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>
