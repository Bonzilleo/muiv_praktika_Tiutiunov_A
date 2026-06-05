<h3 style="margin-top: 0;">Управление заявками на сессии</h3>

<div id="appointment-control-buttons">
    <button type="button" class="btn dashboard-toggle-btn active-tab" data-target="#active-appointments-container">Показать актуальные</button>
    <button type="button" class="btn dashboard-toggle-btn" data-target="#history-appointments-container">История заявок</button>
</div>

<div id="dashboard-content" class="appointment-dashboard-container">
    
    <!-- Таблица активных заявок (со статусом <> completed и cancelled) -->
    <div id="active-appointments-container">
        <?php try { ?>
            <!-- Запрос для активных заявок -->
            <?php $active_sql = "SELECT * FROM appointments WHERE therapist_id = :therapist_id AND status IN ('pending', 'confirmed') ORDER BY created_at ASC"; ?>
            <?php $stmt_active = $pdo->prepare($active_sql); ?>
            <?php $stmt_active->execute([':therapist_id' => $current_therapist_id]); ?>
            <?php $active_appointments = $stmt_active->fetchAll(); ?>

            <?php if (count($active_appointments) > 0): ?>
                <table class="appointment-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr><th>Номер заявки</th><th>Дата заявки</th><th>Имя клиента</th><th>Телефон / Email</th><th style="width: 15%;">Дата проведения</th><th style="width: 25%;">Комментарий клиента</th><th>Статус заявки</th><th>Действия</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($active_appointments as $appointment): ?>
                            <tr data-id="<?= htmlspecialchars($appointment['id']) ?>">
                                <td><?= htmlspecialchars($appointment['id']) ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($appointment['created_at'])); ?></td>
                                <td><?= htmlspecialchars($appointment['guest_name']) ?></td>
                                <td>
                                    Т: <?= htmlspecialchars($appointment['guest_phone']) ?><br>E: <a href="mailto=<?= htmlspecialchars($appointment['guest_email']) ?>"><?= htmlspecialchars($appointment['guest_email']) ?></a>
                                </td>
                                <td style="width: 15%;">
                                    <?php if (!empty($appointment['appointment_datetime'])) { echo date('d.m.Y H:i', strtotime($appointment['appointment_datetime'])); } else { echo "Дата не выбрана"; } ?>
                                </td>
                                <td style="max-width: 100%;">
                                    <?= nl2br(htmlspecialchars($appointment['guest_notes'])) ?: '-' ?>
                                </td>
                                <td style="font-weight: 600; color: <?= ($appointment['status'] == 'pending' ? '#f39c12' : '#2ecc71') ?>;"><?= htmlspecialchars($appointment['status']) ?></td>
                                <td>
                                    <?php if ($appointment['status'] == 'pending'): ?>
                                        <div class="action-buttons">
                                            <button class="btn status-action" data-id="<?= htmlspecialchars($appointment['id']) ?>" data-status="confirmed" style="background-color: #2ecc71; margin-right: 10px;">Подтвердить</button>
                                            <button class="btn status-action" data-id="<?= htmlspecialchars($appointment['id']) ?>" data-status="cancelled" style="background-color: #e74c3c;">Отменить</button>
                                        </div>
                                    <?php elseif ($appointment['status'] == 'confirmed'): ?>
                                        <div class="action-buttons">
                                            <button class="btn status-action" data-id="<?= htmlspecialchars($appointment['id']) ?>" data-status="completed" style="background-color: #3498db; margin-right: 10px;">Завершить</button>
                                            <button class="btn status-action" data-id="<?= htmlspecialchars($appointment['id']) ?>" data-status="cancelled" style="background-color: #e74c3c;">Отменить</button>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="system-notification">В данный момент активных заявок нет. Ждем новых клиентов!</p>
            <?php endif; ?>
        <?php } catch (\PDOException $e) { ?>
            <div style="color: red; background-color: #fee;">Ошибка загрузки активных заявок: <?= htmlspecialchars($e->getMessage()) ?></div>
        <?php } ?>
    </div>

    <!-- Таблица История заявок (со статусом = completed или cancelled) -->
    <div id="history-appointments-container" style="display: none;">
        <?php try { ?>
            <!-- Запрос для закрытых заявок -->
            <?php $history_sql = "SELECT * FROM appointments WHERE therapist_id = :therapist_id AND status IN ('completed', 'cancelled') ORDER BY created_at DESC"; ?>
            <?php $stmt_history = $pdo->prepare($history_sql); ?>
            <?php $stmt_history->execute([':therapist_id' => $current_therapist_id]); ?>
            <?php $history_appointments = $stmt_history->fetchAll(); ?>

            <?php if (count($history_appointments) > 0): ?>
                <table class="appointment-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr><th>Номер заявки</th><th>Дата заявки</th><th>Имя клиента</th><th>Телефон / Email</th><th style="max-width: 100%;">Комментарий клиента</th><th>Статус заявки</th><th>Мой комментарий</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history_appointments as $appointment): ?>
                            <tr>
                                <td><?= htmlspecialchars($appointment['id']) ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($appointment['created_at'])); ?></td>
                                <td><?= htmlspecialchars($appointment['guest_name']) ?></td>
                                <td>
                                    Т: <?= htmlspecialchars($appointment['guest_phone']) ?><br>E: <a href="mailto=<?= htmlspecialchars($appointment['guest_email']) ?>"><?= htmlspecialchars($appointment['guest_email']) ?></a>
                                </td>
                                <td style="max-width: 100%;"><?= nl2br(htmlspecialchars($appointment['guest_notes'])) ?: '-' ?></td>
                                <td style="font-weight: 600; color: <?= ($appointment['status'] == 'completed' ? '#2ecc71' : '#e74c3c') ?>;"><?= htmlspecialchars($appointment['status']) ?></td>
                                <td style="max-width: 100%;"><?= nl2br(htmlspecialchars($appointment['therapists_notes'])) ?: '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="system-notification">История заявок пока пуста.</p>
            <?php endif; ?>
        <?php } catch (\PDOException $e) { ?>
            <div style="color: red; background-color: #fee;">Ошибка загрузки истории заявок: <?= htmlspecialchars($e->getMessage()) ?></div>
        <?php } ?>
    </div>

</div>