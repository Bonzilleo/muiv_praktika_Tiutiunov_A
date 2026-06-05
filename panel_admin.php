<?php

$success_message = '';
$issues = [];
$new_user_type = null;

try {
    // Запрашиваем все id и описания из таблицы issues
    $stmt_issues = $pdo->query("SELECT id, description FROM issues ORDER BY description ASC");
    $issues = $stmt_issues->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // В случае ошибки предотвращаем сбой работы формы
    die("<div style='color: red;'>Ошибка при загрузке данных о проблематиках: " . htmlspecialchars($e->getMessage()) . "</div>");
}

// Вводим переменную с путем, куда будем помещать фотографии психологов
$upload_dir = 'uploads/therapist_photos/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true); // Создаем директорию, если ее нет
}

// Обработка создания пользователей
if ($_SERVER["REQUEST_METHOD"] === "POST" && $user_role == 'Админ-пользователь') {

    // Проверяем, выбран ли режим создания пользователя
    $new_user_type = $_POST['new_user_type'] ?? '';

    $username = trim($_POST['new_username']) ?: null;
    $password = $_POST['new_password'];

    $last_user_id = null;
    $message_error = '';

    if (empty($username) || empty($password)) {
        $message_error = '<div style="background-color: #fee6e6; color: red; padding: 10px; border-radius: 5px;">Пожалуйста, заполните все обязательные поля (Логин и Пароль).</div>';
    } else {

        // Создание админа
        if ($new_user_type == 'admin') {
            try {
                $stmt_user = $pdo->prepare("INSERT INTO users (username, password, is_admin) VALUES (?, ?, ?)");
                $stmt_user->execute([$username, password_hash($password, PASSWORD_DEFAULT), 1]);
                $last_user_id = $pdo->lastInsertId();

                $success_message = '<div style="background-color: #e6ffe6; color: green; padding: 10px; border-radius: 5px;">Администратор успешно создан! Логин: ' . htmlspecialchars($username) . '</div>';
            } catch (\PDOException $e) {
                $message_error = '<div style="background-color: #ffe6e6; color: red; padding: 10px; border-radius: 5px;">Ошибка базы данных при создании администратора: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }


            // Создание психолога
        } elseif ($new_user_type == 'therapist') {
            $name = trim($_POST['new_full_name']);
            $role = $_POST['therapist_role'] ?? '';
            $gender = $_POST['therapist_gender'] ?? '';

            $uploaded_file = null;
            $image_path = 'images/default.jpg';

            if (!empty($_FILES['new_image_path']['name']) && $_FILES['new_image_path']['error'] === UPLOAD_ERR_OK) {
                $temp_file = $_FILES['new_image_path']['tmp_name'];
                $original_name = basename($_FILES['new_image_path']['name']);
                // Создаем уникальное имя файла для предотвращения перезаписи
                $unique_filename = uniqid('therapist_') . '_' . time() . '.' . pathinfo($original_name, PATHINFO_EXTENSION);
                $target_file = $upload_dir . $unique_filename;

                if (move_uploaded_file($temp_file, $target_file)) {
                    // Успешная загрузка: сохраняем относительный путь для БД
                    $image_path = 'uploads/therapist_photos/' . $unique_filename;
                } else {
                    $message_error = '<div style="background-color: #ffe6e6; color: red; padding: 10px; border-radius: 5px;">Ошибка загрузки файла. Проверьте права доступа к папке uploads/therapist_photos/.</div>';
                }
            }

            $age = filter_var($_POST['therapist_age'] ?? '', FILTER_VALIDATE_INT);
            $experience_years = filter_var($_POST['therapist_experience_years'] ?? '', FILTER_VALIDATE_INT);
            $base_price = filter_var($_POST['therapist_base_price'] ?? 0, FILTER_VALIDATE_FLOAT);

            try {
                // Создаем пользователя в таблице users
                $stmt_user = $pdo->prepare("INSERT INTO users (username, password, is_admin) VALUES (?, ?, ?)");
                $stmt_user->execute([$username, password_hash($password, PASSWORD_DEFAULT), 0]);
                $last_user_id = $pdo->lastInsertId();

                // Делаем новую запись в таблице therapists
                $stmt_thera = $pdo->prepare("INSERT INTO therapists (user_id, name, role, gender, age, base_price, experience_years, match_description, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_thera->execute([
                    $last_user_id,
                    htmlspecialchars($name),
                    htmlspecialchars($role),
                    htmlspecialchars($gender),
                    (int) $age,
                    (int) $base_price,
                    (int) $experience_years,
                    htmlspecialchars($_POST['therapist_match_description']),
                    $image_path
                ]);

                $new_therapist_id = $pdo->lastInsertId();

                // Связываем выбранные проблематики с новым психологом
                $selected_issues = $_POST['selected_issues'] ?? [];
                if (!empty($selected_issues)) {
                    $stmt_issue = $pdo->prepare("INSERT INTO therapist_issues (therapist_id, issue_id) VALUES (?, ?)");
                    foreach ($selected_issues as $issue_id) {
                        if (is_numeric($issue_id)) {
                            $stmt_issue->execute([$new_therapist_id, intval($issue_id)]);
                        }
                    }
                }

                // Сохраняем форматы сессий 
                $formats = [];
                if (isset($_POST['format_online']) && $_POST['format_online'] == 'online') {
                    $formats[] = $_POST['format_online'];
                }
                if (isset($_POST['format_offline']) && $_POST['format_offline'] == 'offline') {
                    $formats[] = $_POST['format_offline'];
                }

                // Вставляем каждую найденную пару (ID, Формат) в таблицу therapists_formats
                foreach ($formats as $format) {
                    $stmt_format = $pdo->prepare("INSERT INTO therapist_formats (therapist_id, format_tag) VALUES (?, ?)");
                    $stmt_format->execute([$new_therapist_id, $format]);
                }

                $success_message = '<div style="background-color: #e6ffe6; color: green; padding: 10px; border-radius: 5px;">✅ Специалист успешно создан! Логин: ' . htmlspecialchars($username) . '</div>';

            } catch (\PDOException $e) {
                $message_error = '<div style="background-color: #ffe6e6; color: red; padding: 10px; border-radius: 5px;">Ошибка базы данных при создании специалиста: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }

            // Проверка, если не выбран тип пользователя (на форме его надо выбрать в любом случае, проверка дополнительная)
        } else {
            $message_error = '<div style="background-color: #fee6e6; color: red; padding: 10px; border-radius: 5px;">Необходимо выбрать тип пользователя.</div>';
        }
    }

    // Окончательная установка переменной $message, которая будет видна в dashboard.php
    if ($success_message) {
        $message = $success_message;
    } elseif (!empty($message_error)) {
        $message = $message_error;
    } else {
        // Если ничего не произошло, message остается пустым или наследует ошибку от dashboard.php (если бы она была)
        $message = '';
    }
}

?>


<!-- Блок создания нового пользователя -->
<div style="margin-top: 40px;">
    <h3 class="section-header">Создание нового пользователя</h3>

    <!-- Сообщения об успешном/неуспешном создании -->
    <?php echo $message; ?>
    <form id="create_user_form" action="dashboard.php" method="POST" enctype="multipart/form-data">

        <!-- Выбираем тип пользователя -->
        <h4 style="margin-top: 20px;">Тип создаваемого аккаунта</h4>
        <div class="radio-list" id="user-type-selection">
            <label>
                <input type="radio" name="new_user_type" value="admin" required
                    onclick="toggleUserType('admin', 'Администратор')"> Администратор
            </label><br>
            <label>
                <input type="radio" name="new_user_type" value="therapist" required
                    onclick="toggleUserType('therapist', 'Психолог-консультант')"> Психолог-консультант
            </label>
        </div>

        <!-- Данные для создания пользователя в таблице users -->
        <h4 style="margin-top: 30px;">Основные учетные данные</h4>
        <div class="form-group">
            <label for="new_username">Логин</label>
            <input type="text" id="new_username" name="new_username" required>
        </div>
        <div class="form-group">
            <label for="new_password">Пароль</label>
            <input type="password" id="new_password" name="new_password" required minlength="6">
        </div>

        <!-- Данные для создания пользователя психолога в таблицу therapists, therapists_format, therapists_issues -->
        <div id="therapist-fields">
            <h4 style="margin-top: 30px;">Данные для Психолога-консультанта</h4>
            <div class="form-group">
                <label for="new_full_name">Полное имя</label>
                <input type="text" id="new_full_name" name="new_full_name"
                    placeholder="Имя, Фамилия, Отчество (при наличии)" required>
            </div>
            <div class="form-group">
                <label for="new_image_path">Фото психолога:</label>
                <input type="file" id="new_image_path" name="new_image_path">
            </div>

            <div class="grid-container">
                <div class="form-group">
                    <label for="therapist_gender">Пол</label>
                    <select id="therapist_gender" name="therapist_gender" required>
                        <option value="">Выберите пол...</option>
                        <option value="female">Женский</option>
                        <option value="male">Мужской</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="therapist_age">Возраст специалиста:</label>
                    <input type="number" id="therapist_age" name="therapist_age" min="18" max="70" required
                        placeholder="Введите возраст специалиста (целое число)">
                </div>
            </div>

            <div class="grid-container">
                <div class="form-group">
                    <label for="therapist_role">Специализация</label>
                    <input type="text" id="therapist_role" name="therapist_role" placeholder="Семейный психолог, КПТ..."
                        required>
                </div>
                <div class="form-group">
                    <label for="therapist_experience_years">Стаж работы</label>
                    <input type="number" id="therapist_experience_years" name="therapist_experience_years" min="0"
                        placeholder="Введите количество лет (целое число)" required>
                </div>
            </div>
            <div class="form-group">
                <label for="therapist_base_price">Цена за сессию</label>
                <input type="number" id="therapist_base_price" name="therapist_base_price" min="0"
                    placeholder="В рублях (целое число)" required>
            </div>


            <!-- Вывод динамического списка проблематик -->
            <fieldset class="form-group">
                <legend>Основные проблематики, с которыми работает психолог</legend>
                <?php if (!empty($issues)): ?>
                    <?php foreach ($issues as $issue): ?>
                        <div style="margin-bottom: 10px;">
                            <input type="checkbox" name="selected_issues[]" value="<?= htmlspecialchars($issue['id']) ?>"
                                id="issue_<?= htmlspecialchars($issue['id']) ?>">
                            <label for="issue_<?= htmlspecialchars($issue['id']) ?>" style="margin-left: 10px;">
                                <?= htmlspecialchars($issue['description']) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="system-notification">Проблематики пока не добавлены в систему.</p>
                <?php endif; ?>
            </fieldset>

            <!-- Выбор формата проведения сессии -->
            <div class="form-group">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">Выберите формат
                    проведения сессий:</label>
                <div style="margin-bottom: 10px;">
                    <input type="checkbox" name="format_online" value="online" id="format_online">
                    <label for="format_online" style="margin-right: 20px;">Онлайн</label>

                    <input type="checkbox" name="format_offline" value="offline" id="format_offline">
                    <label for="format_offline">Офлайн</label>
                </div>
            </div>

            <div class="form-group">
                <label for="therapist_match_description">Описание специалиста</label>
                <textarea id="therapist_match_description" name="therapist_match_description" rows="3"
                    required></textarea>
            </div>
        </div>

        <!-- Кнопка отправки -->
        <button type="submit" class="btn" style="margin-top: 40px;">Создать пользователя</button>
    </form>
</div>