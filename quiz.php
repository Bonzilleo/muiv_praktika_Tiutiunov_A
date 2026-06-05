<?php
require_once 'db.php';

$issues = [];
try {
    // Запрашиваем все ID и описания из таблицы issues
    $stmt_issues = $pdo->query("SELECT id, description FROM issues ORDER BY description ASC");
    $issues = $stmt_issues->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // В случае ошибки предотвращаем сбой работы формы
    die("<div style='color: red;'>Ошибка при загрузке данных о проблематиках: " . htmlspecialchars($e->getMessage()) . "</div>");
}

$show_results = false;
$top_three = [];

try {
    $sql = "SELECT id, name, role, gender, age, match_description, base_price, experience_years, image_path FROM therapists";
    $stmt = $pdo->query($sql);
    $therapists_database = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Вывести по каждому терапевту список проблем и возможных форматов проведения сеансов
    foreach ($therapists_database as $index => $therapist) {
        // Список проблем
        $issues_stmt = $pdo->prepare("SELECT issue_id FROM therapist_issues WHERE therapist_id = ?");
        $issues_stmt->execute([$therapist['id']]);
        $therapists_database[$index]['issues'] = $issues_stmt->fetchAll(PDO::FETCH_COLUMN);

        // Форматы проведения сеансов
        $formats_stmt = $pdo->prepare("SELECT format_tag FROM therapist_formats WHERE therapist_id = ?");
        $formats_stmt->execute([$therapist['id']]);
        $therapists_database[$index]['format'] = $formats_stmt->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (\PDOException $e) {
    die("Ошибка загрузки данных из БД: " . $e->getMessage());
}

// Алгоритм расчета процента совпадений для вывода подходящего специалиста
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $selected_issues = isset($_POST['quiz_issue']) && is_array($_POST['quiz_issue']) ? $_POST['quiz_issue'] : [];
    $selected_gender = isset($_POST['quiz_gender']) ? htmlspecialchars($_POST['quiz_gender']) : 'no_matter';
    $selected_age_group = isset($_POST['quiz_age']) ? htmlspecialchars($_POST['quiz_age']) : 'any';
    $selected_format = isset($_POST['quiz_format']) ? htmlspecialchars($_POST['quiz_format']) : 'flexible';

    $scored_therapists = [];

    foreach ($therapists_database as $therapist) {
        $score = 0;
        $max_score = 0;

        // Подсчитываем баллы за наличие проблематик у специалистов + считаем максимально возможный счет
        if (!empty($selected_issues)) {
            $max_score += (count($selected_issues) * 10);
            foreach ($selected_issues as $issue) {
                if (in_array($issue, $therapist['issues'])) {
                    $score += 10;
                }
            }
        }

        // Подсчитываем баллы за выбранный пол специалиста
        if ($selected_gender !== "no_matter") {
            $max_score += 10;
            if ($therapist['gender'] === $selected_gender) {
                $score += 10;
            }
        }

        // Подсчитываем баллы за возраст специалиста
        if ($selected_age_group !== "any") {
            $max_score += 20;
            if ($selected_age_group === 'young') {
                $max_score += 20;
                if ($therapist['age'] !== null && $therapist['age'] <= 35) {
                    $score += 20;
                }
            } elseif ($selected_age_group === 'experienced') {
                $max_score += 20;
                if ($therapist['age'] !== null && $therapist['age'] > 35) {
                    $score += 20;
                }
            }

        }

        // Подсчитываем баллы за выбранный формат проведения сессии
        if ($selected_format !== "flexible") {
            $max_score += 10;
            if (in_array($selected_format, $therapist['format'])) {
                $score += 10;
            }
        }

        if ($max_score === 0) {
            $max_score = 1;
        }

        // Рассчитываем процент совпадений по результату опроса
        $final_percentage = round(($score / $max_score) * 100);

        $therapist['matchPercentage'] = $final_percentage;
        $scored_therapists[] = $therapist;
    }

    // Сортируем полученный массив с процентом совпадений по убыванию
    usort($scored_therapists, function ($a, $b) {
        return $b['matchPercentage'] <=> $a['matchPercentage'];
    });

    // Выводим первых три специалиста
    $top_three = array_slice($scored_therapists, 0, 3);
    $show_results = true;
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Психологический подбор — Психологический Центр "Гармония"</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="page-quiz">

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
        <h1 class="page-title-block">Интерактивный подбор психолога</h1>

        <?php if (!$show_results): ?>
            <p>Ответьте на вопросы анкеты. Наша система проанализирует ваши ответы и предложит 3 наиболее подходящих
                специалистов центра.</p>

            <section id="quiz-form-container">
                <h2 class="section-header">Тест-анкета</h2>
                <form action="quiz.php" method="POST">

                    <fieldset>
                        <legend>№1 Что вас беспокоит в первую очередь?</legend>
                        <p>Выберите одну или несколько тем, которые вызывают наибольшее беспокойство:</p>
                        <?php if (!empty($issues)): ?>
                            <?php foreach ($issues as $issue): ?>
                                <div style="margin-bottom: 10px;">
                                    <label>
                                        <input type="checkbox" name="quiz_issue[]" value="<?= htmlspecialchars($issue['id']) ?>"
                                            id="issue_<?= htmlspecialchars($issue['id']) ?>">
                                        <span>
                                            <?php echo htmlspecialchars($issue['description']); ?>
                                        </span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="system-notification">В настоящее время в системе не зафиксировано проблемных областей.</p>
                        <?php endif; ?>
                    </fieldset>

                    <fieldset>
                        <legend>№2 Какого пола специалиста вы предпочитаете?</legend>
                        <label><input type="radio" name="quiz_gender" value="no_matter" checked> Мне не важен пол
                            специалиста</label><br>
                        <label><input type="radio" name="quiz_gender" value="female"> Я хочу работать с женщиной</label><br>
                        <label><input type="radio" name="quiz_gender" value="male"> Я хочу работать с мужчиной</label>
                    </fieldset>

                    <fieldset>
                        <legend>№3 Какого возраста специалиста вы ищете?</legend>
                        <label><input type="radio" name="quiz_age" value="any" checked> Любой возраст</label><br>
                        <label><input type="radio" name="quiz_age" value="young"> До 35 лет (молодой специалист)</label><br>
                        <label><input type="radio" name="quiz_age" value="experienced"> Старше 35 лет (более опытный
                            специалист)</label>
                    </fieldset>

                    <fieldset>
                        <legend>№4 Какой формат встреч вам подходит?</legend>
                        <label><input type="radio" name="quiz_format" value="online"> Только онлайн (видеосвязь)</label><br>
                        <label><input type="radio" name="quiz_format" value="offline"> Офлайн (личный визит в кабинет
                            центра)</label><br>
                        <label><input type="radio" name="quiz_format" value="flexible" checked> Рассматриваю оба
                            варианта</label>
                    </fieldset>

                    <br>
                    <button type="submit">Показать подходящих психологов</button>
                </form>
            </section>
        <?php else: ?>

            <!-- Выводим результат прохождения опроса на этой же странице после отправки формы -->
            <section class="results-section">
                <h2 class="section-header">Результаты вашего подбора (Топ-3 специалиста)</h2>
                <p>На основе ваших ответов система подобрала следующих терапевтов:</p>

                <!-- Вместо <ul>...</ul> -->
                <div class="specialist-grid">
                    <?php foreach ($top_three as $index => $therapist): ?>
                        <article class="expert-card">
                            <div class="photo-block">
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
                                <p><strong class="specialty">Специализация:</strong>
                                    <?php echo htmlspecialchars($therapist['role']); ?></p>
                                <p class="meta-info">Опыт: <?php echo htmlspecialchars($therapist['experience_years']); ?> лет •
                                    От
                                    <?php echo number_format((float) $therapist['base_price'], 0, ',', ' ') . ' ₽'; ?> / сессия
                                </p>
                                <p class="specialty-info"><strong>Форматы сессии:</strong>
                                    <?php
                                    if (empty($therapist['format'])) {
                                        echo 'Не указаны';
                                    } else {
                                        $formats_text = implode(' и ', array_map(function ($format) {
                                            return ucwords(str_replace('-', ' ', $format));
                                        }, $therapist['format']));
                                        echo htmlspecialchars($formats_text);
                                    }
                                    ?>
                                </p>
                                <p class="match-description"><strong>Почему подходит:</strong>
                                    <?php echo htmlspecialchars($therapist['match_description']); ?></p>

                                <button type="button" class="btn specialist-booking-trigger"
                                    onclick="openBookingModal('<?php echo htmlspecialchars($therapist['id']); ?>')">
                                    Записаться на консультацию
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div style="margin-top: 30px; display: flex; gap: 15px;">
                    <a href="quiz.php" class="btn secondary-action-btn">Пройти тест заново</a>
                    <a href="specialists.php" class="btn primary-action-btn">Показать всех психологов</a>
                </div>
            </section>
        <?php endif; ?>
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

            // После нажатия на кнопку "Записаться на консультацию" мы сохраняем идентификатор психолога
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

            // Через 4 секунды автоматически закрываем окно и переводим на главную страницу 
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