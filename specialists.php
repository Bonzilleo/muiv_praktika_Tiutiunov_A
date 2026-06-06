<?php

session_start();
if (!defined('PER_PAGE')) {
    define('PER_PAGE', 10);
}

require_once 'db.php';

$params = [];
$where_clauses = [];
$is_filtering = false;

// Собираем все активные фильтры и возвращаем массив параметров 
// Важно, я решил убрать ввод фильтра по минимальной цене (оставил только максимальную), оставил обработку min_price в коде, если потребуется её вернуть

function collect_filters($get)
{
    $filters = [];

    // Поиск по имени/фамилии (если это не пустая строка)
    if (!empty($_GET['search_name']) && trim($_GET['search_name']) !== "") {
        $filters[':search_query'] = '%' . $_GET['search_name'] . '%';
    }

    // Фильтрация по цене (минимальная цена)
    if (isset($_GET['min_price']) && filter_var($_GET['min_price'], FILTER_VALIDATE_FLOAT)) {
        $filters[':min_price'] = floatval($_GET['min_price']);
    }

    // Фильтрация по цене (максимальная цена)
    if (isset($_GET['max_price']) && filter_var($_GET['max_price'], FILTER_VALIDATE_FLOAT)) {
        $filters[':max_price'] = floatval($_GET['max_price']);
    }

    return $filters;
}

// Передаем собранные параметры для использования в SQL
$collected_params = collect_filters($_GET);
$params = $collected_params;

if (!empty($collected_params)) {
    $is_filtering = true;

    // Проверка по имени/фамилии
    if (isset($collected_params[':search_query'])) {
        $where_clauses[] = "t.name LIKE :search_query";
    }

    // Добавление условий цены
    if (isset($collected_params[':min_price']) && $collected_params[':min_price'] >= 0) {
        $where_clauses[] = "t.base_price >= :min_price";
    }

    if (isset($collected_params[':max_price']) && $collected_params[':max_price'] >= 0) {
        $where_clauses[] = "t.base_price <= :max_price";
    }
}


// После того как собрали все параметры, помещаем их в переменную where_sql, чтобы сфомировать условие WHERE для запроса SQL
$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Подсчитываем количество найденных специалистов по параметрам выше
try {
    $count_sql = "SELECT COUNT(id) FROM therapists t" . $where_sql;
    $total_stmt = $pdo->prepare($count_sql);

    // Передаем все параметры, включая те, что относятся к фильтрам.
    $total_stmt->execute($params);
    $total_therapists = $total_stmt->fetchColumn();

} catch (\Exception $e) {
    $total_therapists = 0;
    error_log("Ошибка при подсчете специалистов: " . $e->getMessage());
}

// Рассчитываем количество страниц.
$total_pages = ceil($total_therapists / PER_PAGE);

// Получаем текущую страницу (по умолчанию 1)
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;

// Рассчитываем смещение и лимит для SQL
$offset = max(0, ($current_page - 1) * PER_PAGE);
$limit = PER_PAGE;

// Формирование финального запроса для вывода специалистов, где LIMIT - выводит N количество строк, а OFFSET - смещает таблицу на N строк
// Например, для списка элементов до 5 шт на одной страницы LIMIT будет 5, а OFFSET (Номер страницы - 1) * 5
$sql = "SELECT t.* FROM therapists t $where_sql LIMIT :limit OFFSET :offset";

try {
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $therapists_database = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($therapists_database as $index => $therapist) {
        // Форматы проведения сеансов
        $formats_stmt = $pdo->prepare("SELECT format_tag FROM therapist_formats WHERE therapist_id = ?");
        $formats_stmt->execute([$therapist['id']]);
        $therapists_database[$index]['format'] = $formats_stmt->fetchAll(PDO::FETCH_COLUMN);
    }

} catch (\Exception $e) {
    die("Ошибка выполнения запроса: " . htmlspecialchars($e->getMessage()));
}

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Наши ведущие специалисты — Психологический Центр "Гармония"</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="page-specialists">

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
        <h1 class="page-title-block">Наши специалисты</h1>
        <p class="subtitle">Демонстрация экспертности команды центра. Найдите своего терапевта.</p>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php
                    $class = ($i == $current_page) ? 'active' : '';
                    $href = "?page=" . $i;
                    ?>
                    <a href="<?php echo htmlspecialchars($href); ?>" class="pagination-btn <?php echo $class; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

        <div class="content-layout">
            <!-- Список специалистов -->
            <section class="main-content">
                <?php if (count($therapists_database) === 0): ?>
                    <p>К сожалению, по заданным критериям специалисты не найдены.</p>
                <?php else: ?>
                    <?php foreach ($therapists_database as $therapist): ?>
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
                                <p>Опыт: <?php echo htmlspecialchars($therapist['experience_years']); ?> лет • От
                                    <?php echo number_format((float) $therapist['base_price'], 0, '', ' ') . ' ₽'; ?> / сессия
                                </p>
                                <p><strong class="specialty">Форматы сессии:</strong>
                                    <?php
                                    if (empty($therapist['format'])) {
                                        echo 'Не указаны';
                                    } else {
                                        // Преобразуем теги типа 'online' в читаемый текст и объединяем их "и"
                                        $formats_text = implode(' и ', array_map(function ($format) {
                                            return ucwords(str_replace('-', ' ', $format));
                                        }, $therapist['format']));
                                        echo htmlspecialchars($formats_text);
                                    }
                                    ?>
                                </p>
                                <button type="button" class="btn specialist-booking-trigger"
                                    onclick="openBookingModal('<?php echo htmlspecialchars($therapist['id']); ?>')">
                                    Записаться на консультацию
                                </button>

                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>

            </section>


            <!-- Фильтр + ссылка на тест -->
            <aside style="flex: 1; min-width: 300px;">
                <div class="filter-widget" id="search-block">
                    <h3>Поиск специалиста</h3>

                    <form method="GET" action="specialists.php">
                        <?php
                        $page_param = 'page=' . $current_page;
                        ?>
                        <input type="hidden" name="page" value="<?php echo $current_page; ?>">

                        <label for="search_name" style="font-weight: bold; display: block; margin-top: 20px;">Имя или
                            Фамилия:</label>
                        <input type="text" id="search_name" name="search_name" placeholder="Например, Иванов или Анна"
                            value="<?php echo htmlspecialchars($_GET['search_name'] ?? ''); ?>">
                        <div class="filter-group">
                            <label for="min_price"
                                style="font-weight: bold; display: block; margin-top: 30px; margin-bottom: 12px;">Стоимость
                                сессии до:</label>
                            <input type="number" id="max_price" name="max_price" placeholder="Например, 7000"
                                value="<?php echo htmlspecialchars($_GET['max_price'] ?? ''); ?>">
                        </div>
                        <button type="submit" style="width: 100%; margin-top: 30px; display: block;">Найти</button>
                    </form>

                    <div style="margin-top: 25px; text-align: center;">
                        <label for="search_name"
                            style="font-weight: bold; display: block; margin-top: 20px; text-align: left;">Не знаете
                            кого выбрать?</label>
                        <p style="text-align: left;">Пройдите тест и мы подберем вам нужного психолога.</p>
                        <a href="quiz.php" class="btn" style="width: 100%; display: block; text-align: center;">
                            Пройти тест
                        </a>
                    </div>
                </div>
            </aside>

        </div>
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php
                    $class = ($i == $current_page) ? 'active' : '';
                    $href = "?page=" . $i;
                    ?>
                    <a href="<?php echo htmlspecialchars($href); ?>" class="pagination-btn <?php echo $class; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
        </div>
    </main>

    <footer class="page-footer">
        <div style="max-width: 1200px; margin: 0 auto; padding: 30px 0; text-align: center;">
            <p>© 2026 Психологический центр "Гармония". Все права защищены.</p>
        </div>
    </footer>

    <!-- После нажатия на кнопку записаться, открывается форма заполнения данных о клиенте -->
    <div id="booking-modal">
        <div class="modal-content">
            <button class="close-btn" onclick="closeBookingModal()">&times;</button>

            <h2 style="margin-top: 0;">Запись на консультацию</h2>
            <p>Пожалуйста, заполните форму. Ваши данные помогут специалисту подготовиться к первой сессии.</p>

            <!-- Форма с ID для работы JavaScript -->
            <form id="bookingForm" action="/submit_appointment.php" method="POST">
                <!-- Скрытое поле для передачи ID выбранного терапевта -->
                <input type="hidden" name="therapist_id" id="bookedTherapistId">

                <label for="name">Имя:</label>
                <input type="text" id="name" name="name" required placeholder="Введите ваше имя">

                <label for="surname">Фамилия:</label>
                <input type="text" id="surname" name="surname" required placeholder="Введите вашу фамилию">

                <label for="phone">Номер телефона:</label>
                <input type="tel" id="phone" name="phone" required placeholder="+7 (XXX) XXX-XX-XX">

                <label for="email">Почтовый ящик:</label>
                <input type="email" id="email" name="email" required placeholder="example@mail.ru">

                <label for="comment">Дополнительный комментарий (необязательно):</label>
                <textarea id="comment" name="comment" rows="4"
                    placeholder="Расскажите, какая проблема вас беспокоит или что вы ожидаете от терапии..."></textarea>

                <button type="submit" class="btn full-width-btn" style="margin-top: 20px;">Записаться на
                    консультацию</button>
            </form>
        </div>
    </div>

    <script>
        function openBookingModal(therapistId) {

            // После нажатия на кнопку Записаться на консультацию мы сохраняем идентификатор психолога
            document.getElementById('bookedTherapistId').value = therapistId;

            // Сброс формы и заполнение заголовка
            document.getElementById('name').value = '';
            document.getElementById('surname').value = '';
            document.getElementById('phone').value = '';
            document.getElementById('email').value = '';
            document.getElementById('comment').value = '';

            // Отображение формы
            const modal = document.getElementById('booking-modal');
            if (modal) {
                modal.style.display = 'flex'; // Показываем модальное окно
                document.body.style.overflow = 'hidden';
            }
        }

        function closeBookingModal() {
            const modal = document.getElementById('booking-modal');
            if (modal) {
                // Скрываем форму и сбрасываем его содержимое, чтобы пользователь увидел чистую форму при следующем открытии
                resetModalContent();
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
        }

        function resetModalContent() {
            const formContainer = document.getElementById('bookingForm');
            if (formContainer) {
                // Восстанавливаем исходную структуру формы, чтобы можно было нажать на кнопку повторно
                formContainer.innerHTML = `
            <!-- Форма с ID для работы JavaScript -->
            <form id="bookingForm" action="/submit_appointment.php" method="POST">
                <!-- Скрытое поле для передачи ID выбранного терапевта -->
                <input type="hidden" name="therapist_id" id="bookedTherapistId">

                <label for="name">Имя:</label>
                <input type="text" id="name" name="name" required placeholder="Введите ваше имя">

                <label for="surname">Фамилия:</label>
                <input type="text" id="surname" name="surname" required placeholder="Введите вашу фамилию">

                <label for="phone">Номер телефона:</label>
                <input type="tel" id="phone" name="phone" required placeholder="+7 (XXX) XXX-XX-XX">

                <label for="email">Почтовый ящик:</label>
                <input type="email" id="email" name="email" required placeholder="example@mail.ru">

                <label for="comment">Дополнительный комментарий (необязательно):</label>
                <textarea id="comment" name="comment" rows="4"
                    placeholder="Расскажите, какая проблема вас беспокоит или что вы ожидаете от терапии..."></textarea>

                <button type="submit" class="btn full-width-btn" style="margin-top: 20px;">Записаться на
                    консультацию</button>
            </form>
        `;
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const bookingForm = document.getElementById('bookingForm');
            if (bookingForm) {
                bookingForm.addEventListener('submit', function (event) {
                    // Предотвращаем стандартную отправку формы
                    event.preventDefault();

                    // Сбор данных из формы
                    const formData = new FormData(this);
                    const dataObject = Object.fromEntries(formData.entries());

                    // Вызываем функцию, которая отправляет данные на сервер
                    submitAppointmentData(dataObject);
                });
            }
        });


        function submitAppointmentData(data) {
            const bookingForm = document.getElementById('bookingForm');

            // Для отладки
            console.log("Данные, которые отправляются на сервер:", data);
            console.log("Перевод в JSON", JSON.stringify(data));

            // Отправляем данные на PHP обработчик
            fetch('submit_appointment.php', {
                method: 'POST',
                body: JSON.stringify(data), // Передаем данные в формате JSON
                headers: {
                    'Content-Type': 'application/json'
                }
            })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showSuccessMessage(result);
                    } else {
                        displayErrorMessage("Ошибка записи: " + result.message + "<br>Попробуйте позже или свяжитесь с нами по телефону.");
                    }
                })
                .catch(error => {
                    console.error('Сетевая ошибка:', error);
                    displayErrorMessage("Произошла критическая сетевая ошибка. Пожалуйста, обновите страницу.");
                });
        }

        function showSuccessMessage() {
            const modal = document.getElementById('booking-modal');
            if (!modal) return;

            const formContainer = document.querySelector('#booking-modal .modal-content');

            if (!formContainer) {
                console.error("Ошибка: Не найден контейнер (.modal-content)");
                return;
            }
            formContainer.innerHTML = `
        <div style="text-align: center; padding: 30px 10px;">
            <!-- Иконка успеха -->
            <h2 style="margin-top: 5px; color: var(--primary-color);">Запись подтверждена!</h3>
            <p>Спасибо за доверие. Мы получили вашу заявку, и специалист свяжется с вами в ближайшее время для подтверждения деталей консультации.</p>
            <p style="margin-top: 20px; font-weight: bold;">Пожалуйста, ожидайте звонка на ${document.getElementById('phone').value} или письма на ${document.getElementById('email').value}.</p>
        </div>
    `;

            // Через 4 секунд автоматически закрываем окно и переводим на главную страницу 
            setTimeout(() => {
                closeBookingModal();
                window.location.href = 'index.php'
            }, 4000);
        }

        function displayErrorMessage(messageHtml) {
            const modal = document.getElementById('booking-modal');
            if (!modal) return;

            // Ищем контейнер, чтобы заменить форму на сообщение об ошибке
            const formContainer = document.querySelector('#booking-modal .modal-content');
            formContainer.innerHTML = `
        <div style="text-align: center; padding: 30px 10px;">
            <h2 style="margin-top: 5px; color: var(--primary-color);">Не удалось отправить заявку.</h3>
            <p>${messageHtml}</p>
        </div>
    `;

            // Через 4 секунды автоматически закрываем окно и переводим на главную страницу 
            setTimeout(() => {
                closeBookingModal();
                window.location.href = 'index.php'
            }, 4000);
        }

    </script>
</body>

</html>