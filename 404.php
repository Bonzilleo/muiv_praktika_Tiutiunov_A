<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>404 — Мы здесь, чтобы помочь</title>
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

    <main class="container-404">
        <div class="content-box error-block">
            <h1 style="color: #9c3d2b;">Ой! Похоже, вы заблудились.</h1>
            <h2>Страница не найдена (404)</h2>

            <p>Не волнуйтесь. Мы здесь, чтобы помочь вам найти путь к гармонии.</p>
            <p>Возможно, вы неправильно ввели адрес или удалили страницу. Не переживайте — это всего лишь цифры и буквы!
            </p>

            <div class="system-notification">
                Мы уверены, что ваш поиск имеет цель: забота о себе. Пожалуйста, воспользуйтесь навигацией ниже или
                вернитесь к главной странице.
            </div>

            <div class="quick-links">
                <h3>Начните с самого начала</h3>
                <a href="index.php" class="btn primary-btn">Вернуться на главную страницу</a>

                <h3 style="margin: 25px 0 15px;">Или выберите один из этих важных шагов:</h3>

                <div class="link-grid">
                    <a href="quiz.php" class="btn quick-link-card primary-bg">
                        Пройти тест и подобрать психолога
                    </a>

                    <!-- Контакты/Помощь -->
                    <a href="contacts.php" class="btn quick-link-card secondary-bg">
                        Связаться с нами / FAQ
                    </a>

                    <!-- Список специалистов -->
                    <a href="specialists.php" class="btn quick-link-card tertiary-bg">
                        Посмотреть специалистов
                    </a>
                </div>
            </div>

        </div>
    </main>

    <footer class="page-footer">
        <div style="max-width: 1200px; margin: 0 auto; padding: 30px 0; text-align: center;">
            <p>© 2026 Психологический центр "Гармония". Все права защищены.</p>
        </div>
    </footer>

</body>

</html>