<?php


require_once 'db.php';

$services = [];
try {
    // Запрос: Извлекаем все поля, включая format_type
    $sql = "SELECT * FROM services ORDER BY service_id ASC";
    $stmt = $pdo->query($sql);
    $services = $stmt->fetchAll();

} catch (\PDOException $e) {
    // В случае ошибки покажем пустой массив.
    $services = [];
}

function formatPrice($min, $max)
{
    // Проверка на нулевые значения или отсутствие данных
    if (!is_numeric($min) || !is_numeric($max)) {
        return 'Цена уточняется';
    }

    $min = (float) $min;
    $max = (float) $max;

    // Специальная обработка для квиза/бесплатных услуг
    if ($min == 0 && $max == 0) {
        return 'Бесплатно';
    }

    $formattedMin = number_format($min, 0, '', ' ');
    $formattedMax = number_format($max, 0, '', ' ');

    if ($min == $max) {
        return $formattedMin;
    } elseif ($min < $max) {
        return "от {$formattedMin} до {$formattedMax}";
    } else {
        // Если min > max (теоретически), выводим как есть
        return "$formattedMin - $formattedMax";
    }
}

function getFormatDisplay($format_type)
{
    // Если формат проведения = тест, выводим соответствующий текст
    if (strtolower(trim($format_type)) === 'test') {
        return '<span style="color: var(--primary-color); font-weight: bold;">Интерактивный тест</span>';
    } elseif (strtolower(trim($format_type)) === 'online' || strtolower(trim($format_type)) === 'offline' || strtolower(trim($format_type)) === 'both') {
        return '<span style="color: var(--primary-color); font-weight: bold;">Онлайн / Офлайн</span>';
    } else {
        return ucfirst(strtolower(str_replace('|', '/', $format_type))) . ' (Проверка данных)';
    }
}

// =====================================================
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Услуги и цены — Психологический Центр "Гармония"</title>
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
        <h1>Услуги и цены</h1>
        <p>Информирование о предоставленных услугах, длительности встреч и правилах совместной работы.</p>

        <section>
            <h2 style="margin-top: 0;">Тарифная сетка</h2>

            <table class="service-table">
                <thead style="background-color: var(--bg-color); color: var(--primary-color);">
                    <tr>
                        <th>Вид услуги</th>
                        <th>Формат работы</th>
                        <th>Длительность</th>
                        <th>Действие</th>
                    </tr>
                </thead>
                <tbody class="service-body">
                    <!-- Перебираем все услуги центра из БД и помещаем в таблицу -->
                    <?php if (count($services) > 0): ?>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td class="service-name">
                                    <strong
                                        style="color: var(--primary-color);"><?php echo htmlspecialchars($service['name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($service['description'] ?? 'Подробное описание'); ?></small>
                                </td>
                                <td class="service-format"><?php echo getFormatDisplay($service['format_type']); ?></td>
                                <td class="service-duration">
                                    <?php echo htmlspecialchars($service['duration_minutes']); ?> минут
                                </td>
                                <td class="service-action">
                                    <?php
                                    if (strtolower(trim($service['format_type'])) === 'test') {
                                        $target_url = 'quiz.php';
                                    } else {
                                        $target_url = 'specialists.php';
                                    }
                                    echo '<a href="' . htmlspecialchars($target_url) . '" class="btn">' . htmlspecialchars($service['action_text']) . '</a>';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 30px;">В настоящий момент информация о
                                расписании и ценах находится в процессе обновления.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <section>
            <h2>Важная информация о длительности встреч</h2>
            <ul>
                <li>Стандартное время индивидуальной консультации составляет строго 50 минут. Это общепринятый
                    терапевтический час, включающий подведение итогов сессии.</li>
                <li>Парные и семейные встречидлятся дольше — 80 минут, чтобы дать возможность высказаться каждому
                    участнику процесса.</li>
                <li>Опоздание со стороны клиента сокращает время сессии на время опоздания. Время окончания консультации
                    при этом не сдвигается.</li>
            </ul>
        </section>

        <section>
            <h2>Правила отмены и переноса сессий</h2>
            <div>
                <p>Для обеспечения эффективности терапевтического процесса в нашем центре действуют единые правила
                    бронирования и отмены времени:</p>

                <ol>
                    <li>
                        <strong>Предупреждение за 24 часа:</strong> Отмена или перенос назначенной консультации
                        осуществляются без финансовых потерь не позднее, чем за 24 часа до согласованного времени.
                    </li>
                    <li>
                        <strong>Поздняя отмена (менее 24 часов):</strong> Если вы отменяете или переносите встречу менее
                        чем за сутки до её начала, сессия оплачивается в полном объёме (100% от её стоимости). Это
                        правило компенсирует время специалиста, которое было зарезервировано под вас и не могло быть
                        отдано другому клиенту.
                    </li>
                    <li>
                        <strong>Опоздание или отмена со стороны психолога:</strong> Если специалист отменяет
                        консультацию менее чем за 24 часа или опаздывает, центр обязуется предоставить вам следующую
                        сессию со скидкой 50% либо полностью перенести её на удобное для вас время.
                    </li>
                </ol>
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