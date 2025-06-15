<?php
// Подключение к базе данных
$conn = new mysqli("MySQL-8.2", "root", "", "redenok_bd");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Настройка отчетов об ошибках MySQL
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
// Обработка удаления товара
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_product'])) {
    try {
        $product_id = (int)$_POST['delete_product'];
        // Получаем путь к изображению
        $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->bind_result($image_path);
        $stmt->fetch();
        $stmt->close();
        // Удаляем товар из БД
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->close();
        // Удаляем файл изображения
        if ($image_path && file_exists($image_path)) {
            unlink($image_path);
        }
        header("Location: admin.php");
        exit;
    } catch (Exception $e) {
        $error = "Ошибка при удалении товара: " . $e->getMessage();
    }
}
// Обработка добавления товара
$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    try {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $price = (float)$_POST['price'];
        $discount = isset($_POST['discount']) ? (float)$_POST['discount'] : 0;
        $is_hit = isset($_POST['is_hit']) ? 1 : 0;
        // Валидация данных
        if (empty($title) || empty($description)) {
            throw new Exception("Все текстовые поля обязательны для заполнения");
        }
        if ($price <= 0 || $price > 999999) {
            throw new Exception("Цена должна быть от 0.01 до 9 999 999");
        }
        if ($discount < 0 || $discount > 100) {
            throw new Exception("Скидка должна быть от 0 до 100%");
        }
        // Обработка изображения
        if (!isset($_FILES['image']) || $_FILES['image']['error'] != UPLOAD_ERR_OK) {
            throw new Exception("Необходимо загрузить изображение товара");
        }
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['image']['type'];
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception("Допустимы только изображения JPG, PNG или GIF");
        }
        // Создаем папку для загрузок, если ее нет
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        // Генерируем уникальное имя файла
        $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_ext;
        $file_path = $upload_dir . $file_name;
        // Перемещаем загруженный файл
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
            throw new Exception("Ошибка загрузки изображения");
        }
        // Добавляем товар в БД
        $stmt = $conn->prepare("INSERT INTO products (title, description, price, image, discount, is_hit) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdssi", $title, $description, $price, $file_path, $discount, $is_hit);
        $stmt->execute();
        $stmt->close();
        header("Location: admin.php");
        exit;
    } catch (Exception $e) {
        $error = "Ошибка при добавлении товара: " . $e->getMessage();
    }
}
// Обработка удаления комментария
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_comment'])) {
    try {
        $comment_id = (int)$_POST['delete_comment'];
        $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->bind_param("i", $comment_id);
        $stmt->execute();
        $stmt->close();
        header("Location: admin.php");
        exit;
    } catch (Exception $e) {
        $error = "Ошибка при удалении комментария: " . $e->getMessage();
    }
}
// Получаем данные
$products = $conn->query("SELECT * FROM products ORDER BY id DESC");
$comments = $conn->query("SELECT * FROM comments ORDER BY date_added DESC");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Админ-панель - Ребенок-Рад</title>
    <!-- <script src="https://kit.fontawesome.com/a076d05399.js"  crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">  -->
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">Админ-панель</h1>
            <a href="/index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> На сайт
            </a>
        </div>
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <!-- Секция товаров -->
        <div class="admin-section">
            <h2><i class="fas fa-box-open"></i> Управление товарами</h2>
            <form class="add-form" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Название товара</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="price">Цена (руб)</label>
                    <input type="number" id="price" name="price" step="100" min="100" max="9999999" required>
                </div>
                <div class="form-group">
                    <label for="discount">Скидка (%)</label>
                    <input type="number" id="discount" name="discount" step="5" min="0" max="100" value="0">
                </div>
                <div class="form-group">
                    <label for="is_hit">Хит продаж?</label>
                    <input type="checkbox" id="is_hit" name="is_hit">
                </div>
                <div class="form-group">
                    <label for="description">Описание товара</label>
                    <textarea id="description" name="description" required></textarea>
                </div>
                <div class="form-group">
                    <label for="image">Изображение товара</label>
                    <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif" required>
                </div>
                <div class="form-submit">
                    <button type="submit" name="add_product" value="1" class="submit-btn">
                        <i class="fas fa-plus"></i> Добавить товар
                    </button>
                </div>
            </form>
            <?php if ($products->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Изображение</th>
                            <th>Название</th>
                            <th>Описание</th>
                            <th>Цена</th>
                            <th>Скидка</th>
                            <th>Хит</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($product = $products->fetch_assoc()): ?>
                            <tr>
                                <td><?= $product['id'] ?></td>
                                <td>
                                    <?php if ($product['image']): ?>
                                        <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['title']) ?>" class="product-image">
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($product['title']) ?></td>
                                <td><?= nl2br(htmlspecialchars($product['description'])) ?></td>
                                <td><?= number_format($product['price'], 0, '', ' ') ?> ₽</td>
                                <td><?= $product['discount'],0 ?>%</td>
                                <td><?= $product['is_hit'] ? 'Да' : 'Нет' ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Удалить этот товар?');">
                                        <input type="hidden" name="delete_product" value="<?= $product['id'] ?>">
                                        <button type="submit" class="delete-btn">
                                            <i class="fas fa-trash"></i> Удалить
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Нет товаров для отображения</p>
            <?php endif; ?>
        </div>
        <!-- Секция комментариев -->
        <div class="admin-section">
            <h2><i class="fas fa-comments"></i> Управление комментариями</h2>
            <?php if ($comments->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Имя</th>
                            <th>Комментарий</th>
                            <th>Оценка</th>
                            <th>Дата</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($comment = $comments->fetch_assoc()): ?>
                            <tr>
                                <td><?= $comment['id'] ?></td>
                                <td><?= htmlspecialchars($comment['name']) ?></td>
                                <td><?= nl2br(htmlspecialchars($comment['comment'])) ?></td>
                                <td>
                                    <?php 
                                    $rating = (int)$comment['rating'];
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $rating ? '<i class="fas fa-star" style="color: gold;"></i>' : '<i class="far fa-star" style="color: gold;"></i>';
                                    }
                                    ?>
                                </td>
                                <td><?= date("d.m.Y H:i", strtotime($comment['date_added'])) ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Удалить этот комментарий?');">
                                        <input type="hidden" name="delete_comment" value="<?= $comment['id'] ?>">
                                        <button type="submit" class="delete-btn">
                                            <i class="fas fa-trash"></i> Удалить
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Нет комментариев для отображения</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

   