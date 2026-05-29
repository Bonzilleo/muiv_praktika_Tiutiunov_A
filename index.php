<?php

require_once 'db.php';

$featured_specialists = [];
try {
    // Запрос - выбираем 5 специалистов, отсортированных по убыванию стажа для вывода на главную страницу.
    // SELECT включает все нужные поля: id (для ссылки), name, role, experience_years, base_price.
    $sql = "SELECT id, name, role, experience_years, base_price FROM therapists ORDER BY experience_years DESC LIMIT 5";
    $stmt = $pdo->query($sql);
    $featured_specialists = $stmt->fetchAll();

} catch (\PDOException $e) {
    // Если произошла ошибка БД, оставляем пустой массив, чтобы сайт не сломался.
    $featured_specialists = []; 
}

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Психологический Центр "Гармония" — Главная страница</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <header>
        <div>
            <strong>ГАРМОНИЯ</strong>
            <nav>
                <ul>
                    <li><a href="index.php" style="color: var(--accent-color); font-weight: 600;">Главная</a></li>
                    <li><a href="specialists.php">Наши специалисты</a></li>
                    <li><a href="prices.php">Услуги и цены</a></li>
                    <li><a href="quiz.php">Подбор психолога</a></li>
                    <li><a href="blog.html">Блог</a></li>
                    <li><a href="contacts.html">Контакты и FAQ</a></li>
                    <li><a href="dashboard.html">Личный кабинет</a></li>
                    <li><a href="sitemap.html">Карта сайта</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section>
            <h1>Бережная психологическая помощь для вашей гармонии</h1>
            <p>Поможем разобраться в себе, справиться с тревогой и наладить отношения. Пройдите короткий интерактивный
                тест, и система подберет идеального специалиста под ваш запрос.</p>

            <a href="quiz.php" class="btn">Пройти тест и подобрать психолога</a>
        </section>

        <section>
            <h2>Почему нам доверяют</h2>
            <ul class="feature-list">
                <li class="feature-card">
                    <h3 class="feature-title">100% Конфиденциальность</h3>
                    <p>Все сессии проходят строго анонимно. Ваши данные под надежной защитой.</p>
                </li>
                <li class="feature-card">
                    <h3 class="feature-title">Высший стандарт отбора</h3>
                    <p>Все специалисты имеют профильное образование и более 5 лет практики.</p>
                </li>
                <li class="feature-card">
                    <h3 class="feature-title">Быстрый подбор</h3>
                    <p>Интеллектуальный квиз подберет топ-3 терапевтов за 2 минуты.</p>
                </li>
            </ul>
        </section>

        <section class="dynamic-specialists">
            <h2>Наши ведущие специалисты</h2>

            <div class="featured-list-container">
                <!-- Перебироаем массив с специалистами и выводим по списку -->
                <?php if (count($featured_specialists) > 0): ?>
                    <?php foreach ($featured_specialists as $therapist): ?>
                        <article class="specialist-card featured-expert">
                            <div class="expert-photo">[Фото: <?php echo htmlspecialchars($therapist['name']); ?>]</div>
                            <h3><?php echo htmlspecialchars($therapist['name']); ?></h3>
                            <p><strong style="color: #666;">Специализация:</strong> <?php echo htmlspecialchars($therapist['role']); ?></p>
                            <p><strong style="color: #666;">Опыт:</strong> <?php echo htmlspecialchars($therapist['experience_years']); ?> лет</p>
                            <p><strong style="color: #666;">От стоимости сессии:</strong> От <?php echo htmlspecialchars($therapist['base_price']); ?> ₽</p>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Выводим сообщение, если нет данных -->
                    <p style="color: var(--primary-color);">В данный момент список ведущих специалистов обновляется. Попробуйте вернуться позже.</p>
                <?php endif; ?>
            </div>
        </section>

        <section>
            <h2>Отзывы тех, кому мы помогли</h2>

            <blockquote class="testimonial-card">
                <p>"Тест на сайте очень точно определил мою проблему. Порекомендовали Игоря Петрова. После 3 месяцев
                    терапии я наконец-то избавилась от панических атак. Спасибо вам!"</p>
                <cite>Мария К., 28 лет</cite>
            </blockquote>

            <blockquote class="testimonial-card">
                <p>"Обратились с женой на грани развода. Семейные сессии с Анной помогли услышать друг друга. Центр
                    очень уютный, и атмосфера располагает к открытости."</p>
                <cite>Александр и Ольга</cite>
            </blockquote>
        </section>
    </main>

    <footer>
        <div class="footer-content">
            <p>© 2026 Психологический центр "Гармония". Все права защищены.</p>
        </div>
    </footer>

</body >
</html>
