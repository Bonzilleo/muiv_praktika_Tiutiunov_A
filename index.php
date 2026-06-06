<?php

require_once 'db.php';

$featured_specialists = [];
try {
    // Запрос - выбираем 5 специалистов, отсортированных по убыванию стажа для вывода на главную страницу.
    // SELECT включает все нужные поля: id (для ссылки), name, role, experience_years, base_price.
    $sql = "SELECT id, name, role, experience_years, base_price, image_path, match_description FROM therapists ORDER BY experience_years DESC LIMIT 5";
    $stmt = $pdo->query($sql);
    $featured_specialists = $stmt->fetchAll();

} catch (\PDOException $e) {
    // Если произошла ошибка БД, оставляем пустой массив
    $featured_specialists = [];
}

$news_posts = [];
try {
    $sql = "SELECT n.*, t.name AS author_name FROM news n LEFT JOIN therapists t ON n.author_id = t.id ORDER BY n.created_at DESC LIMIT 3";
    $stmt = $pdo->query($sql);
    $news_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (\PDOException $e) {
    error_log("News loading error: " . $e->getMessage());
    $news_posts = [];
}

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Психологический Центр "Гармония" — Главная страница</title>
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
        <section>
            <h1 class="section-header">Психологическая помощь для вашей гармонии</h1>
            <p>Поможем разобраться в себе, справиться с тревогой и наладить отношения. Пройдите короткий интерактивный
                тест, и система подберет идеального специалиста под ваш запрос.</p>

            <a href="quiz.php" class="btn">Пройти тест и подобрать психолога</a>
        </section>

        <section id="trust-us-features">
            <h2 class="section-header">Почему нам доверяют</h2>
            <div class="feature-grid">
                <article class="content-card">
                    <h3>100% Конфиденциальность</h3>
                    <p>Все сессии проходят строго анонимно. Ваши данные под надежной защитой.</p>
                    <h3>Высший стандарт отбора</h3>
                    <p>Все специалисты имеют профильное образование и более 5 лет практики.</p>
                    <h3>Быстрый подбор</h3>
                    <p>Интеллектуальный квиз подберет топ-3 терапевтов за 2 минуты.</p>
                </article>
            </div>
        </section>

        <section class="dynamic-specialists">
            <h2 class="section-header">Наши ведущие специалисты</h2>

            <div class="featured-list-container">
                <!-- Перебироаем массив с специалистами и выводим по списку -->
                <?php if (count($featured_specialists) > 0): ?>
                    <?php foreach ($featured_specialists as $therapist): ?>
                        <article class="expert-card">
                            <div class="photo">
                                <?php
                                // Получаем путь к изображению из данных терапевта
                                $image_path = htmlspecialchars($therapist['image_path']);

                                // Определяем источник изображения: используем сохраненный путь, если он не пуст.
                                // Если путь пуст или некорректен, используем запасной 'images/default.jpg'.
                                $src = !empty($image_path) ? $image_path : 'images/default.jpg';
                                echo '<img src="' . $src . '" alt="Фото профиля ' . htmlspecialchars($therapist['name']) . '">';
                                ?>
                            </div>

                            <div class="details">
                                <h3 class="specialist-name"><?php echo htmlspecialchars($therapist['name']); ?></h3>

                                <p><strong class="specialty">Специализация:
                                    </strong><?php echo htmlspecialchars($therapist['role']); ?>
                                </p>
                                <p>Опыт: <?php echo htmlspecialchars($therapist['experience_years']); ?> лет •
                                    <?php echo number_format((float) $therapist['base_price'], 0, '', ' ') . ' ₽'; ?> / сессия
                                </p>
                                <p><strong class="specialty">О себе:
                                    </strong><?php echo htmlspecialchars($therapist['match_description']); ?>
                                </p>

                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Выводим сообщение, если нет данных -->
                    <p style="color: var(--primary-color);">В данный момент список ведущих специалистов обновляется.
                        Попробуйте вернуться позже.</p>
                <?php endif; ?>
            </div>
        </section>

        <section>
            <h2 class="section-header">Отзывы тех, кому мы помогли</h2>

            <blockquote class="testimonial-card">
                <p>"Тест на сайте очень точно определил мою проблему. Порекомендовали Игоря Петрова. После 3 месяцев
                    терапии я наконец-то избавилась от панических атак. Спасибо вам!"</p>
                <cite>Мария К., 28 лет</cite>
            </blockquote>

            <blockquote class="testimonial-card">
                <p>"Обратились с женой на грани развода. Семейные сессии с Анной помогли услышать друг друга. Центр
                    очень уютный, и атмосфера располагает к открытости."</p>
                <cite>Александр и Ольга</cite>
            </blockquote>
        </section>

        <section id="news-feed" style="margin-top: 50px;">
            <h2 class="section-header">Последние статьи блога</h2>

            <div class="news-list-container"
                style="display: flex; gap: 30px; justify-content: space-between; margin-top: 30px;">
                <?php if (count($news_posts) > 0): ?>
                    <?php foreach ($news_posts as $post): ?>
                        <article class="blog-excerpt" style="flex: 1; max-width: 30%; border: none; padding: 0;">
                            <div class="article-image photo" style="margin-bottom: 15px;">
                                <?php
                                $image_path = $post['featured_image'] ?? '';
                                $src = '';

                                if (!empty(trim($image_path))) {
                                    $src = $image_path;
                                }
                                ?>
                                <img src="<?php echo htmlspecialchars($src); ?>" alt=" ">
                            </div>

                            <h3 style="margin-top: 0; font-size: 1.2rem; color: var(--primary-color);">
                                <?php echo htmlspecialchars($post['title']); ?></h3>

                            <div style="font-size: 0.9rem; color: #777; margin-bottom: 15px;">
                                <span class="meta-item">Автор: <strong
                                        style="color: var(--primary-color);"><?php echo htmlspecialchars($post['author_name']); ?></strong></span><br>
                                <?php
                                $date_display = 'Дата не указана';
                                if (!empty($post['created_at'])) {
                                    $timestamp = strtotime($post['created_at']);
                                    $date_display = date('d.m.Y', $timestamp);
                                }
                                ?>
                                <span class="meta-item">Дата:
                                    <?php echo htmlspecialchars($date_display); ?></span>
                            </div>

                            <p><?php echo htmlspecialchars($post['content_snippet']); ?></p>

                            <!-- Кнопка перехода на полную статью -->
                            <a href="article.php?id=<?php echo htmlspecialchars($post['id']); ?>" class="btn btn-small"
                                style="display: inline-block; margin-top: 10px;">Читать полностью</a>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>В настоящее время новых статей нет. Следите за нашими обновлениями!</p>
                <?php endif; ?>
            </div>
        </section>

    </main>

    <footer class="page-footer">
        <div style="max-width: 1200px; margin: 0 auto; padding: 30px 0; text-align: center;">
            <p>© 2026 Психологический центр "Гармония". Все права защищены.</p>
        </div>
    </footer>

</body>


</html>