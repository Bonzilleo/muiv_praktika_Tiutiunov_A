<?php
session_start();
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Карта сайта — Психологический Центр "Гармония"</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="page-layout">

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
        <h1 class="section-header">Карта сайта (Sitemap)</h1>
        <p style="margin-bottom: 40px;">Иерархический список всех разделов и веб-страниц официального сайта
            психологического центра "Гармония".</p>

        <section class="sitemap-content">
            <h2 data-section="contacts" style="color: var(--primary-color); border: none; font-size: 1.8em;">Основная
                структура сайта</h2>
            <ul class="sitemap-list">
                <li>
                    <a href="index.php">Главная страница</a>
                    <ul>
                        <li>Презентация центра</li>
                        <li>Блок ключевых преимуществ</li>
                        <li>Слайдер ведущих специалистов</li>
                        <li>Отзывы клиентов</li>
                    </ul>
                </li>
                <li>
                    <a href="specialists.php">Наши специалисты (Каталог терапевтов)</a>
                    <ul>
                        <li>Поиск специалистов</li>
                        <li>Индивидуальные формы прямой записи на прием</li>
                    </ul>
                </li>
                <li>
                    <a href="prices.php">Услуги и цены</a>
                    <ul>
                        <li>Динамическое перечисление услуг</li>
                        <li>Правила отмены и переноса консультаций</li>
                    </ul>
                </li>
                <li>
                    <a href="quiz.php">Психологический подбор (Тест)</a>
                    <ul>
                        <li>Интерактивный пошаговый квиз</li>
                        <li>Экран динамического вывода Топ-3 специалистов</li>
                    </ul>
                </li>
                <li>
                    <a href="blog.php">Блог / База знаний</a>
                    <ul>
                        <li>Полезные статьи от психологов центра психологии Гармония</li>
                    </ul>
                </li>
                <li>
                    <a href="contacts.php">Контакты и FAQ</a>
                    <ul>
                        <li>Адрес, мессенджеры, телефонный номер</li>
                        <li>Часто задаваемые вопросы (аккордеон ответов)</li>
                    </ul>
                </li>
                <li>
                    <a href="dashboard.php" class="admin-link">Личный кабинет</a>
                    <ul>
                        <li>Панель Админа (Создание новых пользователей)</li>
                        <li>Панель Психолога (Управление заявками, статусами)</li>
                    </ul>
                </li>
            </ul>
        </section>

    </main>

    <footer class="page-footer">
        <div style="max-width: 1200px; margin: 0 auto; padding: 30px 0; text-align: center;">
            <p>© 2026 Психологический центр "Гармония". Все права защищены.</p>
        </div>
    </footer>

</body>

</html>