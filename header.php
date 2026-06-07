<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$current_username = 'Гость';
$user_role = '';
$display_name = '';

if ($is_logged_in) {
    // Определяем имя пользователя, если есть имя (например из таблицы therapists) подставляем его, иначе - логин.
    // Выводим display_name Пользователь только в том случае, если при авторизации пользователь не определился как психолог или админ
    // но такой ситуации возникать на уровне пользователь не должно - 

    if (isset($_SESSION['profile_name'])) {
        $display_name = $_SESSION['profile_name'];
    } elseif (isset($_SESSION['username'])) {
        $display_name = htmlspecialchars($_SESSION['username']);
    } else {
        $display_name = 'Пользователь';
    }

    // Определяем роль: если пользователь есть в таблице therapists - ставим роль Психолог-консультант,
    // если пользователя нет, но есть пометка is_admin в таблице users - ставим роль Админ-пользователь
    // иначе - просто Пользователь, но такой ситуации возникать на уровне пользователь не должно - пользователь всегда психолог или админ
    if (isset($_SESSION['is_therapist']) && $_SESSION['is_therapist'] === true) {
        $user_role = 'Психолог-консультант';
        $current_therapist_id = $_SESSION['therapist_id'] ?? null;

    } elseif (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        $user_role = 'Админ-пользователь';
        $message = '';
    } else {
        $user_role = 'Пользователь';
        $current_therapist_id = null;
    }
}

?>

<header>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center;">

            <!-- Логотип / Название Центра -->
            <strong style="font-size: 1.8rem; color: var(--primary-color);">ГАРМОНИЯ</strong>

            <!-- Основная навигация (Первый уровень) -->
            <nav>
                <ul class="main-menu">
                    <li><a href="index.php">Главная</a></li>
                    <li><a href="specialists.php">Наши специалисты</a></li>
                    <li><a href="prices.php">Услуги и цены</a></li>
                    <li><a href="quiz.php">Подбор психолога</a></li>
                    <li><a href="blog.php">Блог</a></li>
                    <li><a href="contacts.php">Контакты и FAQ</a></li>
                    <li><a href="sitemap.php">Карта сайта</a></li>
                    <li><a href="dashboard.php">Личный кабинет</a></li>
                    <?php if ($is_logged_in): ?>
                        <!-- Кнопка выхода -->
                        <li class="logout-btn">
                            <a href="logout.php"
                                style="color: var(--accent-color); text-decoration: none; font-weight: 600;">Выйти</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>

        <script>

            const bodyElement = document.body;
            const themeContainer = document.getElementById('theme-selection-container');

            function resetAccessibilityMode() {
                // Удаляем главный класс ГОСТ
                bodyElement.classList.remove('is-visually-impaired');

                // Очищаем все возможные цветовые темы
                ['theme-wb', 'theme-bw', 'theme-bb'].forEach(cls => {
                    bodyElement.classList.remove(cls);
                });

                localStorage.removeItem('accessibilityMode');
            }

            function setAccessibilityMode(themeClass) {
                // Сначала сбрасываем все предыдущие настройки для чистоты
                resetAccessibilityMode();

                if (!themeClass || themeClass === 'normal') {
                    console.log("Сброс: Стандартный режим.");
                    return;
                }

                // Если выбран любой из специальных схем, применяем их к тегу body
                bodyElement.classList.add('is-visually-impaired');
                bodyElement.classList.add(themeClass);
                console.log(`Установлен режим слабовидящих с темой: ${themeClass}`);

                // Сохраняем выбор в Local Storage, чтобы при перезагрузке сохранять тему
                localStorage.setItem('accessibilityMode', themeClass);
            }

            document.addEventListener('DOMContentLoaded', () => {

                // Проверка состояния, запускается при загрузке каждой страницы
                const savedTheme = localStorage.getItem('accessibilityMode') || 'normal';
                setAccessibilityMode(savedTheme);


                // Обработчик кнопки "Доступность"
                const govStandardButton = document.querySelector('.accessibility-toggle-btn[data-theme="gov-standard-switcher"]');
                if (govStandardButton) {
                    govStandardButton.addEventListener('click', () => {
                        const themeSelectionContainer = document.getElementById('theme-selection-container');
                        if (themeSelectionContainer) {
                            // Проверяем текущее состояние элемента
                            let isCurrentlyHidden = window.getComputedStyle(themeSelectionContainer).display === 'none';

                            if (isCurrentlyHidden) {
                                // Если спрятан, показываем его
                                themeSelectionContainer.style.display = 'block';
                                govStandardButton.textContent = "Скрыть настройки доступности"; 
                            } else {
                                // Если виден, прячем его
                                themeSelectionContainer.style.display = 'none';
                                govStandardButton.textContent = "Настроить доступность";
                            }
                        }
                    });
                }

                // Обработчик для всех кнопок тем
                document.querySelectorAll('.theme-switcher').forEach(button => {
                    button.addEventListener('click', function () {
                        const selectedTheme = this.getAttribute('data-theme');
                        setAccessibilityMode(selectedTheme);
                    });
                });
            });

        </script>
</header>


<style>
    .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
    }
</style>
