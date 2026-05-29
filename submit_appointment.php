<?php

require_once 'db.php'; 

header('Content-Type: application/json'); 

// Ответ по умолчанию
$response = ['success' => false, 'message' => 'Неверный запрос или ошибка сервера.', 'code' => 500];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // Если метод не POST, выводим ошибку и завершаем.
    $response['message'] = 'Доступ запрещен.';
} else {

    try {
        $raw_data = file_get_contents('php://input');

        if (!$raw_data) {
             throw new Exception("Не удалось считать данные из тела запроса.");
        }
        
        // Декодируем строку JSON в массив PHP
        $data = json_decode($raw_data, true);

        if (!is_array($data)) {
            throw new Exception("Данные не являются корректным JSON.");
        }

        // Получение и санитизация данных из массива $data
        $therapist_id = isset($data['therapist_id']) ? trim((string)$data['therapist_id']) : null;
        $client_name  = isset($data['name']) ? trim((string)$data['name']) : '';
        $client_surname = isset($data['surname']) ? trim((string)$data['surname']) : '';
        $client_phone = isset($data['phone']) ? trim((string)$data['phone']) : '';
        $client_email = isset($data['email']) ? trim((string)$data['email']) : '';
        $client_notes = isset($data['comment']) ? substr(trim((string)$data['comment']), 0, 250) : '';

        // Проверка обязательных полей
        if (!$therapist_id || empty($client_name) || empty($client_phone)) {
            $response = [
                'success' => false, 
                'message' => 'Необходимо заполнить все обязательные поля. Поля не могут быть пустыми.', 
                'code' => 400
            ];

        } else {
            // Если ошибок нет, начинаем запись в БД
            $guest_name = trim($client_name . ' ' . $client_surname);
            $created_at = date('Y-m-d H:i:s');

            $sql = "INSERT INTO appointments 
                            (therapist_id, guest_name, guest_email, guest_phone, guest_notes, created_at) 
                            VALUES (:therapist_id, :guest_name, :guest_email, :guest_phone, :guest_notes, :created_at)";

            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([
                ':therapist_id' => $therapist_id,
                ':guest_name'  => $guest_name,
                ':guest_email' => $client_email,
                ':guest_phone' => $client_phone,
                ':guest_notes' => $client_notes,
                ':created_at'  => $created_at
            ]);

            $response = [
                'success' => true, 
                'message' => 'Заявка успешно создана! Мы свяжемся с вами в ближайшее время.', 
                'appointment_id' => $pdo->lastInsertId()
            ];
        }

    } catch (\PDOException $e) {
 
    $response = [
        'success' => false, 
        'message' => 'Возникла техническая ошибка при записи. Попробуйте позже или свяжитесь с нами напрямую.', // Общее сообщение для пользователя
        'code' => 500
    ];

} catch (Exception $e) {
    // Обработка других ошибок PHP
     $response = [
         'success' => false, 
         'message' => 'Критическая ошибка: ' . $e->getMessage(), 
         'code' => 500
     ];
}

} 


echo json_encode($response); 

?>