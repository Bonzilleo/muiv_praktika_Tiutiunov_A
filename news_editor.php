<?php

session_start();
require_once 'db.php';

// Авторизация пользователя

$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$user_role = 'Пользователь';
$current_therapist_id = null;

if ($is_logged_in) {
    // Логика определения роли и ID: ПСИХОЛОГ > АДМИН > Пользователь
    if (isset($_SESSION['is_therapist']) && $_SESSION['is_therapist'] === true) {
        $user_role = 'Психолог-консультант'; // Используем имя роли из сессии
        $current_therapist_id = $_SESSION['therapist_id'] ?? null; // *** ИСПОЛЬЗУЕМ ТЕРАПИСТОВ ID ***
    } elseif (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        $user_role = 'Админ-пользователь';
    } else {
        $user_role = 'Пользователь';
    }
}

if (!$is_logged_in) {
    die('<div class="alert alert-danger">Доступ запрещен. Пожалуйста, авторизуйтесь.</div>');
}

// Инициализация переменных сообщений и данных
$error_message = '';
$success_message = '';
$article_data = [];
$uploaded_image_path = null;
$categories_list = [];

try {
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // В случае ошибки продолжаем работу без категорий.
    error_log("Ошибка при получении списка категорий: " . $e->getMessage());
}


// Обработка создания или редактирования новости
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Проверяем, что пользователь имеет право писать/редактировать.
    $article_id = $_POST['article_id'] ?? null;

    if ($user_role == 'Психолог-консультант' && $article_id) {
        //Режим редактирования: Психолог может править, только если author_id совпадает с его id в таблице therapists.
        $stmt = $pdo->prepare("SELECT author_id FROM news WHERE id = :id");
        $stmt->execute([':id' => $article_id]);
        $existing_article = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing_article || (int) $existing_article['author_id'] !== $current_therapist_id) {
            die('<div class="alert alert-danger">У вас нет прав на редактирование данной статьи.</div>');
        }
    }


    // Обработка файла
    $upload_dir = 'uploads/article_covers/';

    if (!empty($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $temp_name = $_FILES['featured_image']['tmp_name'];
        $original_name = basename($_FILES['featured_image']['name']);
        // Создаем уникальное имя файла для предотвращения перезаписи
        $unique_filename = uniqid('article_') . '_' . time() . '.' . pathinfo($original_name, PATHINFO_EXTENSION);
        $target_file = $upload_dir . $unique_filename;

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true); // Создание папки с правами на запись
        }

        if (move_uploaded_file($temp_name, $target_file)) {
            $image_path = 'uploads/article_covers/' . $unique_filename;
        } else {
            // Если файл не перемещен:
            $error_message = '<div class="alert alert-danger">Не удалось загрузить изображение. Проверьте права на запись в папку uploads/article_covers/.</div>';
        }
    } else {
        $image_path = '';
    }

    // Подготавливаем данные и формируем запрос (INSERT или UPDATE)
    try {
        $data = [
            'title' => $_POST['title'],
            'content_snippet' => $_POST['content_snippet'],
            'full_content' => $_POST['full_content'] ?? '',
            'featured_image' => $image_path, // Путь к файлу обложки
            'category_id' => $_POST['category'] ?? null
        ];

        $current_datetime = date('Y-m-d H:i:s');
        $created_news_id = null;

        // Если article_id заполнен - это означает, что мы не создаем новую статью, а редактируем существующую
        if ($article_id) {
            // Режим редактирования (UPDATE)
            // Обновление основной статьи и получение id
            $stmt = $pdo->prepare("UPDATE news SET title=?, content_snippet=?, full_content=?, featured_image=? WHERE id=?");
            $stmt->execute([
                $data['title'],
                $data['content_snippet'],
                $data['full_content'],
                $data['featured_image'],
                $article_id
            ]);
            // ID не меняется при обновлении
            $created_news_id = $article_id;

            // Проверяем категории (DELETE + INSERT) - на форме создания/редактирования новости категория обязательна.
            if ($data['category_id']) {
                // Очищаем старую связь для этой новости
                $stmt = $pdo->prepare("DELETE FROM news_categories WHERE news_id = ?");
                $stmt->execute([$created_news_id]);

                // Вставляем новую связь
                $stmt = $pdo->prepare("INSERT INTO news_categories (news_id, category_id) VALUES (?, ?)");
                $stmt->execute([$created_news_id, $data['category_id']]);
            }

            $_SESSION['success_message'] = 'Статья успешно обновлена!';

        } else {
            // Если article_id не заполнен - значит мы создаем новую новость в БД
            // Режим создания (INSERT)
            // Создаем новость
            $stmt = $pdo->prepare("INSERT INTO news (title, content_snippet, full_content, featured_image, author_id, created_at) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['title'],
                $data['content_snippet'],
                $data['full_content'], 
                $data['featured_image'],
                $current_therapist_id ?? null,
                $current_datetime // Дата и время публикации
            ]);
            // Получаем ID только что созданной новости
            $created_news_id = $pdo->lastInsertId(); 

            // Связываем новость с категорией в таблице news_categories
             if ($data['category_id']) {
                $stmt = $pdo->prepare("INSERT INTO news_categories (news_id, category_id) VALUES (?, ?)");
                $stmt->execute([$created_news_id, $data['category_id']]);
            }

            $_SESSION['success_message'] = 'Статья успешно опубликована!';
        }

    } catch (\PDOException $e) {
        $error_message = '<div class="alert alert-danger">Ошибка сохранения: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }

    if (isset($_SESSION['success_message'])) {
         header('Location: blog.php');
         exit;
    }
}


// Загружаем данные для редактирования новости
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $article_id = $_GET['id'];

    // Проверка прав доступа для загрузки статьи на редактирование
    // Формируем запрос на вывод новости, где id = article_id и author_id = current_therapist_id
    if ($user_role == 'Психолог-консультант') {
        $sql = "SELECT * FROM news WHERE id = :id AND author_id = :author_id";
        $stmt = $pdo->prepare($sql);
        // Используем current_therapist_id для запроса!
        $stmt->execute([':id' => $article_id, ':author_id' => $current_therapist_id]);
    }

    // Получение категорий новостей
    if ($article_data) {
        $sql = "SELECT c.name FROM news_categories nc JOIN categories c ON nc.category_id = c.id WHERE nc.news_id = :id LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $article_id]);
        $category_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($category_data) {
            $article_data['selected_category'] = htmlspecialchars($category_data['name']);
        } else {
             // Если связь не найдена, используем пустой запасной вариант.
            $article_data['selected_category'] = ''; 
        }
    }

    $article_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$article_data) {
        die('<div class="alert alert-danger">Не удалось загрузить статью или у вас нет прав на просмотр ее деталей.</div>');
    }
}

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title><?php echo $article_data['title'] ?? 'Создать новую статью'; ?></title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <?php include 'header.php'; ?>
    <!-- Переключение режим доступности по ГОСТу-->
    <div id="accessibility-widget" class="bvi-panel">
        <div class="bvi-panel-container">
            <button class="accessibility-toggle-btn" data-theme="gov-standard-switcher"
                style="background: #eee; color: var(--text-color);">Настроить доступность</button>
            <div id="theme-selection-container" class="bvi-panel-container" style="display: none;">
                <span style="font-weight: 600; color: var(--text-color);">Выберите тему:</span>
                <button class="theme-switcher" data-theme="normal" style="background-color: #ccc;">Стандартный</button>
                <button class="theme-switcher" data-theme="theme-wb" title="Черно-белая">Черно-белый</button>
                <button class="theme-switcher" data-theme="theme-bw" title="Бело-черная">Бело-черный</button>
            </div>
        </div>
    </div>

    <main>

        <?php echo $success_message ?? ''; ?>
        <?php echo $error_message ?? ''; ?>


        <form action="news_editor.php" method="POST" enctype="multipart/form-data">
            <!-- Скрытое поле для режима редактирования -->
            <input type="hidden" name="article_id" value="<?php echo htmlspecialchars($article_data['id'] ?? ''); ?>">

            <div class="form-group">
                <label for="title">Заголовок статьи *</label>
                <input type="text" id="title" name="title" required
                    value="<?php echo htmlspecialchars($article_data['title'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="full_content">Полный текст статьи (HTML/Markdown)</label>
                <textarea id="full_content" name="full_content" rows="15"
                    required><?php echo htmlspecialchars($article_data['full_content'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="snippet">Превью/Текст в блог (Краткий отрывок) *</label>
                <textarea id="snippet" name="content_snippet" rows="3"
                    required><?php echo htmlspecialchars($article_data['content_snippet'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="featured_image">Обложка статьи (Image)</label>
                <input type="file" id="featured_image" name="featured_image" accept="image/*">
            </div>

            <div class="form-group">
                <label for="category">Категория *</label>
                <select id="category" name="category" required style="width: 100%; padding: 8px;">
                    <option value="">Выберите категорию</option>
                    <?php 
                    // Используем $categories_list, полученный в начале файла.
                    foreach ($categories_list as $category): 
                        $selected = (isset($article_data['selected_category']) && htmlspecialchars($article_data['selected_category']) === htmlspecialchars($category['name'])) ? 'selected' : '';
                    ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo $selected; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php
            // Проверяем, установлен ли ID в данных статьи.
            $button_text = !empty($article_data['id']) ? 'Обновить статью' : 'Опубликовать статью';
            ?>
            <button type="submit" class="primary-action-btn">
                <?= $button_text ?>
            </button>
        </form>
    </main>

</body>

</html>