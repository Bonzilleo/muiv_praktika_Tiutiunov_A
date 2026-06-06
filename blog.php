<?php
session_start();
require_once 'db.php';

$categories_list = [];
$news_posts = [];

// Получение всех категорий для фильтрации
try {
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Ошибка при получении категорий: " . $e->getMessage());
    // Возвращаем пустой массив в случае ошибки
    return [];
}

// Определяем, какой фильтр активен
$current_filter_id = $_GET['category'] ?? null;

try {
    $sql = "SELECT 
                n.*, 
                t.name AS author_name
            FROM news n
            LEFT JOIN therapists t ON n.author_id = t.id";

    $params = [];

    // Если фильтр активен, добавляем к переменной $sql JOIN через таблицу связей
    if ($current_filter_id) {
        $sql .= " 
            JOIN news_categories nc ON n.id = nc.news_id
            WHERE nc.category_id = :cat_id";
        $params[':cat_id'] = $current_filter_id;
    }

    $sql .= " ORDER BY n.created_at DESC";
    $stmt = $pdo->prepare($sql);

    // Применяем параметры (если они есть)
    if (!empty($params)) {
        $stmt->bindValue(':cat_id', $params[':cat_id']);
    }

    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Собираем данные после выполнения запроса
    $final_posts = [];

    foreach ($results as $row) {
        if (!isset($row['id']))
            continue;

        $news_data = [
            'id' => (int) $row['id'],
            'title' => htmlspecialchars($row['title']),
            'snippet' => htmlspecialchars($row['content_snippet']),
            'featured_image' => isset($row['featured_image']) ? htmlspecialchars($row['featured_image']) : '',
            'author_name' => htmlspecialchars($row['author_name'] ?? 'Неизвестный специалист'),
            'author_id' => (int) $row['author_id'],
            // Преобразуем значение из столбца created_at в формат даты (день.месяц.год)
            'date' => htmlspecialchars(empty($row['created_at']) ? date('d.m.Y') : str_replace(' ', ' ', date('d.m.Y', strtotime($row['created_at'])))),
        ];

        $final_posts[] = $news_data;
    }

    $news_posts = array_values($final_posts);

} catch (Exception $e) {
    $news_posts = [];
    error_log("Ошибка при загрузке новостей: " . $e->getMessage());
}

?>


<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Блог и База знаний — Психологический Центр "Гармония"</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="page-blog">

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
        <h1 style="margin-bottom: 5px;">Блог и База знаний</h1>
        <p>Полезные статьи, экспертные гайды и разборы реальных клинических случаев от практикующих специалистов нашего
            центра.</p>

        <?php if (isset($_SESSION['is_therapist']) && $_SESSION['is_therapist'] === true): ?>
            <div
                style="margin-top: 50px; padding: 20px; border: 1px dashed var(--primary-color); background-color: #f9f9ff;">
                <p>Хотите поделиться опытом? Перейди по ссылке ниже, чтобы опубликовать статью!
                </p>
                <a href="news_editor.php" class="primary-action-btn">Начать писать статью</a>
            </div>
        <?php endif; ?>

        <div class="filter-container"
            style="margin: 30px 0; padding: 20px; border-bottom: 1px solid var(--border-color); background-color: #f4f7fa;">
            <h2 style="margin-top: 0;">Фильтровать по категориям</h2>

            <!-- Форма для фильтрации -->
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="GET"
                style="display: flex; gap: 15px; align-items: center;">
                <label for="category">Категория:</label>
                <select name="category" id="category" onchange="this.form.submit()" class="filter-select"
                    style="padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                    <option value="" <?php echo $current_filter_id === null ? 'selected' : ''; ?>>Все категории</option>
                    <?php foreach ($categories_list as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $current_filter_id == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>


        <?php if (!empty($news_posts)): ?>
            <h2 style="margin-top: 40px;">Полезные статьи и гайды</h2>

            <!-- Цикл для отображения каждой статьи -->
            <?php foreach ($news_posts as $post): ?>
                <article class="blog-card"
                    style="padding: 25px; margin-bottom: 30px; border: 1px solid var(--border-color); background-color: white;">

                    <div class="article-image photo" style="margin-bottom: 20px;">
                        <?php
                        $image_path = $post['featured_image'] ?? '';
                        $src = '';

                        if (!empty(trim($image_path))) {
                            $src = $image_path;
                        }
                        ?>
                        <img src="<?php echo htmlspecialchars($src); ?>" alt=" ">
                    </div>

                    <h3 style="margin-top: 0;"><?php echo $post['title']; ?></h3>

                    <div style="font-size: 0.95rem; color: #777; margin-bottom: 15px;">
                        <?php echo "Автор:"; ?> <strong
                            style="color: var(--primary-color);"><?php echo $post['author_name']; ?></strong>
                        <span style="margin: 0 10px;">•</span>
                        <br>
                        <?php echo "Дата:"; ?> <strong class="date"> <?php echo $post['date']; ?></strong>
                    </div>

                    <p><?php echo $post['snippet']; ?></p>

                    <?php
                    echo '<p style="display: flex; gap: 10px;">';
                    echo '<a class="btn read-article-btn" href="article.php?id=' . htmlspecialchars($post['id']) . '"' . ' >Читать статью полностью</a>';

                    // Доступ к редактированию статьи
                    if ($user_role == 'Психолог-консультант') {
                        $therapist_id = $_SESSION['therapist_id'] ?? null;
                        if (isset($post['author_id']) && $post['author_id'] == $therapist_id) {
                            echo '<a class="btn edit-article-btn" href="news_editor.php?id=' . htmlspecialchars($post['id']) . '"' . ' >Редактировать статью</a></p>';
                        }
                    }
                    ?>
                </article>
            <?php endforeach; ?>

        <?php else: ?>
            <!-- Если новостей нет, выводим сообщение -->
            <article>
                <h3>Пока статей в блоге нет.</h3>
                <p>Мы постоянно работаем над контентом! Следите за обновлениями.</p>
            </article>
        <?php endif; ?>
    </main>

    <footer class="page-footer">
        <div style="max-width: 1200px; margin: 0 auto; padding: 30px 0; text-align: center;">
            <p>© 2026 Психологический центр "Гармония". Все права защищены.</p>
        </div>
    </footer>
</body>

</html>