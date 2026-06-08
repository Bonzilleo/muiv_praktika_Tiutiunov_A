<?php
/*
 Личный кабинет состоит из трех блоков
 1) Dashboard.php - форма авторизации пользователя
 2) panel_admin.php - форма создания нового пользователя для администраторов
 3) panel_therapist.php - форма управления заявками от клиентов
*/

session_start();
require_once 'db.php';

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Личный кабинет — Психологический Центр "Гармония"</title>
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
        <?php if (!$is_logged_in): ?>
            <!-- Выводим панель авторизации пользователя -->
            <section id="login-form-container">
                <h2 class="text-center">Вход в Личный Кабинет</h2>
                <?php if (isset($_GET['error']) && $_GET['message']): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($_GET['message']) ?>
                    </div>
                <?php endif; ?>

                <p>Пожалуйста, авторизуйтесь для доступа к вашему профилю.</p>

                <form action="user_login.php" method="POST">
                    <div class="form-group">
                        <label for="username">Логин (Username)</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="primary-action-btn">Войти в кабинет</button>
                </form>
            </section>

        <?php else: ?>
            <!-- Панели для авторизированных пользователей -->
            <section id="user-dashboard-content">
                <h2 class="text-center">Добро пожаловать, <?php echo htmlspecialchars($display_name); ?>!</h2>
                <!-- Я выделил панели админа и психолога в отдельные файлы php, чтобы уменьшить размер кода dashboard.php -->
                <?php if ($user_role == 'Админ-пользователь'): ?>
                    <!-- Включаем панель для создания нового пользователя -->
                    <?php include 'panel_admin.php'; ?>
                <?php elseif ($user_role == 'Психолог-консультант'): ?>
                    <!-- Включаем панель для управления заявками от клиентов -->
                    <?php include 'panel_therapist.php'; ?>
                <?php endif; ?>
            </section>
        <?php endif; ?>

    </main>

    <footer class="page-footer">
        <div style="max-width: 1200px; margin: 0 auto; padding: 30px 0; text-align: center;">
            <p>© 2026 Психологический центр "Гармония". Все права защищены.</p>
        </div>
    </footer>


    <script>

        // Функция переключает отображение таблиц заявок (для формы пользователя психолога)
        function toggleDashboardView(targetId) {
            const buttons = document.querySelectorAll('.dashboard-toggle-btn');

            // Сначала сбрасываем состояние всех кнопок и контейнеров
            buttons.forEach(btn => btn.classList.remove('active-tab'));

            // Ищем все потенциальные контейнеры заявок, независимо от их расположения в DOM
            const containers = document.querySelectorAll('#active-appointments-container, #history-appointments-container');

            containers.forEach(container => {
                container.style.display = 'none';
            });

            // Делаем целевой контейнер видимым и активным
            const targetContainer = document.querySelector(targetId);
            if (targetContainer) {
                // Устанавливаем display: block, так как это общая секция контента
                targetContainer.style.display = 'block';
                document.querySelector(`.dashboard-toggle-btn[data-target="${targetId}"]`).classList.add('active-tab');
            }
        }

        // Функция для переключения полей при создании нового пользователя (для формы пользователя администратора)
        // Скрываем поля данных нового психолога, если нам надо создать администратора
        function toggleUserType(type) {
            const fields = document.getElementById('therapist-fields');
            const typeSelection = document.querySelector('#user-type-selection input[name="new_user_type"]:checked');

            document.querySelector('#create_user_form input[name="new_user_type"]').value = type;

            if (type === 'admin') {
                fields.style.display = 'none';
                // Блокируем все поля, связанные с терапевтом
                const inputs = fields.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    input.disabled = true;
                    input.value = '';
                    // Убираем аттрибут required, чтобы при создании администратора не обращали внимание на скрытые поля
                    input.removeAttribute('required');
                });

            } else if (type === 'therapist') {
                fields.style.display = 'block';
                // Активируем все поля данных психолога
                const inputs = fields.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    input.disabled = false;
                });

                // Устанавливаем обязательность для ключевых полей психолога
                document.querySelectorAll('#therapist-fields input[required], #therapist-fields textarea').forEach(el => el.setAttribute('required', 'required'));

            } else {
                fields.style.display = 'none';
                const inputs = fields.querySelectorAll('input, select, textarea');
                inputs.forEach(input => input.disabled = true);
            }
        }

        // Обработчик изменения типа пользователя
        document.addEventListener('DOMContentLoaded', () => {
            const dashboardButtons = document.querySelectorAll('.dashboard-toggle-btn');
            dashboardButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const targetId = this.getAttribute('data-target');
                    if (targetId) {
                        // Вызываем функцию, которая скрывает поля данных нового психолога
                        toggleDashboardView(targetId);
                    }
                });
            });

            // При загрузке страницы гарантируем, что активен и виден только первый блок
            const initialTarget = document.querySelector('.dashboard-toggle-btn.active-tab');
            if (initialTarget) {
                const targetId = initialTarget.getAttribute('data-target');
                setTimeout(() => toggleDashboardView(targetId), 50);
            }

            const form = document.getElementById('create_user_form');
            if (!form) return;

            // Вызываем функцию по умолчанию
            const initialTypeRadio = document.querySelector('input[name="new_user_type"]:checked');
            if (initialTypeRadio) {
                const selectedType = initialTypeRadio.value;
                toggleUserType(selectedType, selectedType === 'admin' ? 'Админ' : 'Психолог-консультант');
            }

            // Добавляем обработчик событий для переключения типа пользователя
            document.getElementById('user-type-selection').addEventListener('change', (e) => {
                if (e.target.name === 'new_user_type') {
                    const selectedType = e.target.value;
                    toggleUserType(selectedType, selectedType);
                }
            });

            const buttons = document.querySelectorAll('.status-action');
            buttons.forEach(button => {
                button.addEventListener('click', function () {
                    changeAppointmentStatus(this);
                });
            });

        });

        // Функция для обработки изменения статуса заявки
        function changeAppointmentStatus(buttonElement) {
            const id = buttonElement.getAttribute('data-id');
            let newStatus = buttonElement.getAttribute('data-status').toLowerCase();
            let comment = '';
            let datetimeValue = '';

            // Если заявку отменяют - надо ввести причину отмены
            if (newStatus === 'cancelled') {
                comment = prompt('Пожалуйста, укажите причину отмены заявки. Это обязательно для обновления статуса.');
                if (!comment || comment.trim() === '') {
                    alert("Отмена невозможна: Вы обязаны указать причину.");
                    return;
                }
            }

            // Если заявку принимают - указывают дату проведения
            if (newStatus === 'confirmed') {
                const datePrompt = prompt('Пожалуйста, укажите дату и время сессии в формате ДД.ММ.ГГГГ ЧЧ:ММ.');
                // Регулярное выражение для формата ДД.ММ.ГГГГ ЧЧ:ММ
                if (!datePrompt || !/^\d{2}\.\d{2}\.\d{4} \d{2}:\d{2}$/.test(datePrompt)) {
                    alert("Некорректный формат даты и времени. Попробуйте ввести дату в формате ДД.ММ.ГГГГ, а время — ЧЧ:ММ.");
                    return;
                }
                datetimeValue = datePrompt;
            }

            buttonElement.disabled = true;
            const originalButtonHtml = buttonElement.innerHTML;
            buttonElement.innerHTML = 'Обновление...';

            // Используем FormData для отправки данных, включая комментарий И дату/время
            const formData = new FormData();
            formData.append('id', id);
            formData.append('status', newStatus);
            if (newStatus === 'cancelled') {
                formData.append('comment', comment);
            }
            // Отправляем дату/время, если оно было получено ===
            if (datetimeValue) {
                formData.append('date_time', datetimeValue);
            }

            fetch('update_appointment.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    // Восстанавливаем оригинальный текст кнопки, независимо от успеха
                    buttonElement.disabled = false;
                    buttonElement.innerHTML = originalButtonHtml;

                    if (data.success) {
                        alert(data.message);
                        window.location.reload();

                    } else {
                        alert('Ошибка: ' + (data.message || 'Проверьте ваш доступ к записи или попробуйте позже.'));
                        // Восстанавливаем исходный текст, если что-то пошло не так
                        buttonElement.innerHTML = originalButtonHtml;
                    }
                })
                .catch(error => {
                    console.error('Network Error:', error);
                    alert('Произошла сетевая ошибка. Проверьте подключение к интернету.');
                    // Восстанавливаем исходный текст, если что-то пошло не так
                    buttonElement.innerHTML = originalButtonHtml;
                });
        }

        // Добавляем обработчик событий для обновления статуса заявок в таблице
        document.addEventListener('DOMContentLoaded', () => {
            const buttons = document.querySelectorAll('.status-action');
            buttons.forEach(button => {
                button.addEventListener('click', function () {
                    changeAppointmentStatus(this);
                });
            });
        });
        
    </script>


</body>

</html>