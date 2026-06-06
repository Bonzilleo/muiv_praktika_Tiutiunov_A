<?php

session_start();
require_once 'db.php';

// Проверка авторизации
if (!isset($_SESSION['therapist_id']) || $_SESSION['is_therapist'] !== true) {
    die("Ошибка доступа: Попытка изменения данных неавторизованным пользователем.");
}

// Проверка метода запроса и наличия параметров
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Неверный метод запроса.']));
}

$appointment_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$new_status = strtolower($_POST['status'] ?? ''); 
$comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING) ?? '';
$raw_datetime = filter_input(INPUT_POST, 'date_time', FILTER_SANITIZE_STRING) ?? ''; 

$formatted_datetime = null;
if (!empty($raw_datetime)) {
    // Пытаемся создать объект DateTime из формата, который присылает клиент (d.m.Y H:i)
    $date_time_obj = DateTime::createFromFormat('d.m.Y H:i', $raw_datetime);

    if ($date_time_obj !== false) {
        // Если преобразование прошло успешно, форматируем его в стандартный SQL формат (YYYY-MM-DD HH:MM:SS)
        $formatted_datetime = $date_time_obj->format('Y-m-d H:i:s');
    } else {
        // Если парсинг не удался, оставляем null
        $formatted_datetime = ''; 
    }
}

// Проверка корректности данных после приведения типов
if (!$appointment_id || !in_array($new_status, ['confirmed', 'cancelled', 'completed'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Некорректные данные для обновления.']);
    exit();
}

try {

    // Если статус cancelled, обязательно нужен комментарий
    if ($new_status === 'cancelled') {
        if (empty(trim($comment))) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Для отмены заявки необходимо указать причину в поле "Комментарий".']);
            exit();
        }
    }

     // Если статус confirmed, обязательно нужна дата и время
    if ($new_status === 'confirmed') {
        // Теперь проверяем, что переменная $formatted_datetime содержит валидную дату.
        if (empty($formatted_datetime) || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $formatted_datetime)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Для подтверждения заявки необходимо указать корректную дату и время.']);
            exit();
        }
    }

    // Защита от случайных изменений: проверяем, что заявка принадлежит текущему специалисту.
    $stmt_check = $pdo->prepare("SELECT therapist_id FROM appointments WHERE id = :id AND therapist_id = :therapist_id LIMIT 1");
    $stmt_check->execute([':id' => $appointment_id, ':therapist_id' => $_SESSION['therapist_id']]);

    if (!$stmt_check->fetch()) {
        die("Ошибка безопасности: Вы не являетесь владельцем этой заявки.");
    }

    // Обновление статуса, даты/времени и добавление комментария
    $update_stmt = $pdo->prepare("
        UPDATE appointments 
        SET status = :new_status, updated_at = NOW(), therapists_notes = :comment, appointment_datetime = :datetime
        WHERE id = :id AND therapist_id = :therapist_id
    ");
    
    // Передаем все параметры: статус, ID, терапевт, комментарий и новая дата/время
    $success = $update_stmt->execute([
        ':new_status' => $new_status,
        ':id' => $appointment_id,
        ':therapist_id' => $_SESSION['therapist_id'], 
        // Комментарий: заполняем только при отмене
        ':comment' => ($new_status === 'cancelled') ? trim($comment) : null, 
        // Дата/время: заполняем только при подтверждении заявки
        ':datetime' => ($new_status === 'confirmed') ? $formatted_datetime : null 
    ]);

    if ($success) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => "Статус заявки успешно изменен"]);
    } else {
        http_response_code(500); 
        echo json_encode(['success' => false, 'message' => "Не удалось изменить статус заявки"]);
    }

} catch (\PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    error_log("--- DEBUG SQL ERROR ---");
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => "Внутренняя ошибка сервера. Ошибка DB: " . $e->getMessage()]);
}
?>
