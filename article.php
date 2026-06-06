<?php
session_start();
require_once 'db.php';

$article_post = [];
$error_message = '';

// Получаем id из параметров URL (например, article.php?id=5)
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $article_id = (int) $_GET['id'];

    try {
        // Ищем статью
        $sql = "SELECT 
                    n.*, 
                    GROUP_CONCAT(DISTINCT c.name) AS category_names
                FROM news n
                LEFT JOIN news_categories nc ON n.id = nc.news_id
                LEFT JOIN categories c ON nc.category_id = c.id
                WHERE n.id = :article_id
                GROUP BY n.id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':article_id' => $article_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Собираем данные из запроса
            $news_data = [
                'id' => $row['id'],
                'title' => htmlspecialchars($row['title']),
                'snippet' => htmlspecialchars($row['content_snippet'] ?? 'Текст статьи пока не добавлен.'),
                'featured_image' => isset($row['featured_image']) ? htmlspecialchars($row['featured_image']) : '',
                'full_content' => $row['full_content'],
                'author_id' => $row['author_id'],
                // Преобразуем значение из столбца created_at в формат даты (день.месяц.год)
                'date' => htmlspecialchars(str_replace(' ', ' ', date('d.m.Y', strtotime($row['created_at'])))),
                'categories' => !empty($row['category_names']) ? array_map('htmlspecialchars', explode(',', $row['category_names'])) : [],
            ];

            // Получаем имя автора из таблицы therapists
            $author_id = $news_data['author_id'];
            $stmt_author = $pdo->prepare("SELECT name, role FROM therapists WHERE id = ?");
            $stmt_author->execute([$author_id]);
            $author_profile = $stmt_author->fetch(PDO::FETCH_ASSOC);

            if ($author_profile) {
                $news_data['author_name'] = htmlspecialchars($author_profile['name']);
            } else {
                // Заглушка, если психолог не найден
                $news_data['author_name'] = 'Неизвестный специалист';
            }

            $article_post = $news_data;

        } else {
            // Эта ошибка теперь срабатывает только если статья действительно не существует в таблице news.
            $error_message = "Статья с таким ID не найдена. Пожалуйста, вернитесь на <a href='blog.php'>страницу блога</a>.";
        }
    } catch (PDOException $e) {
        $error_message = "Ошибка базы данных: Не удалось загрузить статью. Попробуйте позже.";
        error_log("Article load error: " . $e->getMessage());
    }

} else {
    // Если ID не передан в URL
    $error_message = "Пожалуйста, укажите ID статьи для просмотра.";
}

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title><?php echo $article_post['title'] ?? 'Блог | Психологический Центр "Гармония"'; ?></title>
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


    <main class="content-layout">
        <section style="padding: 40px; margin-bottom: 20px;">

            <?php if (!empty($error_message)): ?>
                <h1 style="color: var(--text-color);"><?php echo $article_post['title'] ?? ''; ?></h1>
                <div class="system-notification" style="border-color: #D4A373; background-color: #FEF6E9;">
                    <?php echo $error_message; ?>
                </div>

            <?php elseif (!empty($article_post)): ?>
                <!-- Обложка статьи -->
                <div class="article-image photo" style="margin-bottom: 30px;">
                    <?php
                    $image_path = $article_post['featured_image'] ?? '';
                    $alt_text = htmlspecialchars($article_post['title']);

                    if (!empty($image_path)) {
                        $src = htmlspecialchars($image_path);
                    }
                    ?>
                    <img src="<?php echo $src; ?>" alt=" ">
                </div>

                <h1 style="font-size: 2.6rem; color: var(--primary-color); margin-bottom: 15px;">
                    <?php echo $article_post['title']; ?>
                </h1>

                <div class="meta-info"
                    style="font-size: 0.9rem; color: #777; margin-bottom: 40px; border-bottom: 1px solid var(--border-color); padding-bottom: 15px;">
                    <span class="meta-item">Автор: <strong
                            style="color: var(--primary-color);"><?php echo $article_post['author_name']; ?></strong>
                        <span class="meta-item" style="margin-left: 20px;">Дата публикации: <strong
                                class="date"><?php echo $article_post['date']; ?></strong></span>
                        <div class="tag-container" style="margin-top: 10px; display: inline-block;">
                            <?php foreach ($article_post['categories'] as $category): ?>
                                <span class="tag-item"
                                    style="display: inline-block;">#<?php echo $category; ?></span>
                            <?php endforeach; ?>
                        </div>
                </div>

                <article class="article-content"
                    style="line-height: 1.8; font-size: 17px; color: var(--text-color); max-width: 900px;">
                    <?php echo nl2br($article_post['full_content']); ?>
                </article>

                <div style="margin-top: 50px; text-align: center;">
                    <a href="blog.php" class="btn" style="padding: 12px 40px;">Вернуться к другим
                        статьям</a>
                </div>

            <?php endif; ?>

        </section>
    </main>

    <footer class="page-footer">
        <div style="max-width: 1200px; margin: 0 auto; padding: 30px 0; text-align: center;">
            <p>© 2026 Психологический центр "Гармония". Все права защищены.</p>
        </div>
    </footer>

</body>

</html>