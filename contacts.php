<?php
session_start();
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Контакты и FAQ — Психологический Центр "Гармония"</title>
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
        <h1 class="page-title-block">Контакты и Часто задаваемые вопросы</h1>
        <p style="margin-bottom: 40px;">Здесь вы можете найти информацию о нашем местонахождении, связаться с нами
            напрямую или найти ответы на частые вопросы перед визитом.</p>

        <section class="contact-info-section">
            <h2 data-section="contacts">Контактная информация</h2>
            <div class="contact-info-row">
                <div class="contact-info-key">Наш адрес:</div>
                <div class="contact-info-value">г. Москва, ул. Лесная, д. 43, офис 204 (2 этаж)</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-key">Телефон:</div>
                <div class="contact-info-value"><a href="tel:+74950000000">+7 (495) 000-00-00</a></div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-key">Мессенджеры для быстрой связи:</div>
                <div class="contact-info-value">
                    <ul style="margin: 0;">
                        <li><a href="https://t.me" target="_blank">Telegram (@garmonia_center)</a></li>
                        <li><a href="https://wa.me" target="_blank">WhatsApp</a></li>
                    </ul>
                </div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-key">Электронная почта:</div>
                <div class="contact-info-value">
                    <a href="mailto:info@garmonia-center.ru">info@garmonia-center.ru</a>
                </div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-key">Режим работы:</div>
                <div class="contact-info-value">Ежедневно с 09:00 до 20:00 (по предварительной записи)</div>
            </div>

        </section>

        <hr style="border: none; border-top: 1px solid var(--border-color); margin: 40px 0;">

        <section class="faq-container">
            <h2 data-section="faq">Часто задаваемые вопросы (FAQ)</h2>
            <p>Мы собрали ответы на вопросы, которые чаще всего волнуют клиентов перед первой консультацией:</p>

            <details>
                <summary><strong style="color: var(--primary-color);">Как подготовиться к первой сессии?</strong>
                </summary>
                <div style="padding: 20px;">
                    <p>Никакой специальной подготовки не требуется. Вам не нужно писать списки или заучивать
                        формулировки. Достаточно вашего присутствия и готовности говорить о том, что вас волнует. Если
                        вам сложно сформулировать запрос, психолог поможет сделать это с помощью наводящих вопросов в
                        процессе беседы.</p>
                </div>
            </details>
            <details>
                <summary><strong style="color: var(--primary-color);">Что если мне не понравится психолог?</strong>
                </summary>
                <div style="padding: 20px;">
                    <p>Это абсолютно нормальная ситуация. Психотерапия строится на терапевтическом альянсе и личном
                        контакте. Если в процессе первой встречи вы поймете, что вам некомфортно со специалистом, вы
                        имеете полное право отказаться от дальнейших сессий. Вы можете пройти наш тест заново или
                        обратиться к администратору, чтобы мы бережно подобрали вам другого терапевта центра.</p>
                </div>
            </details>
            <details>
                <summary><strong style="color: var(--primary-color);">Конфиденциально ли это?</strong></summary>

                <div style="padding: 20px;">
                    <p>Да, на 100%. Конфиденциальность — это главный этический принцип работы нашего центра и каждого
                        терапевта. Всё, что вы рассказываете на сессии, включая сам факт вашего обращения, остается
                        строго между вами и вашим психологом. Исключением являются только ситуации, напрямую угрожающие
                        вашей жизни или жизни других людей, согласно законодательству РФ.</p>
                </div>
            </details>
            <details>
                <summary><strong style="color: var(--primary-color);">Сколько сессий мне понадобится?</strong></summary>
                <div style="padding: 20px;">
                    <p>Количество встреч зависит от вашего запроса. Для решения локальной проблемы или снятия острого
                        стресса может хватить 3–5 консультаций (формат психологического консультирования). Для глубоких
                        личностных изменений, работы с застарелыми травмами или изменения паттернов поведения требуется
                        долгосрочная терапия от нескольких месяцев до года.</p>
                </div>
            </details>
        </section>
    </main>

    <footer class="page-footer">
        <div style="max-width: 1200px; margin: 0 auto; padding: 30px 0; text-align: center;">
            <p>© 2026 Психологический центр "Гармония". Все права защищены.</p>
        </div>
    </footer>

</body>

</html>