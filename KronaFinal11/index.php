
<?php
session_start();
$loggedIn = isset($_SESSION['user_id']);

// Определяем куда вести кнопку "Личный кабинет" по роли
function cabinetUrl(): string {
    $role = (int)($_SESSION['role_id'] ?? 1);
    return match($role) {
        3 => 'admin.php',
        2 => 'employee.php',
        default => 'profile.php',
    };
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Деревообрабатывающее предприятие</title>
    <link rel="stylesheet" href="style.css">
<script>
(function(){var t=localStorage.getItem('krona-theme');if(t==='light')document.documentElement.classList.add('light-theme-pre');})();
</script>
<style>
html.light-theme-pre body{background:#f5f0e8 !important;color:#1a1208 !important;}
</style>
</head>



<body id="top">
    <a href="#" class="chat-toggle" id="chatToggle" aria-label="Открыть чат">💬</a>

    <div class="chat-widget" id="chatWidget" aria-hidden="true">
        <div class="chat-header">
            <div>
                <strong>Ассистент</strong>
                <p>Онлайн</p>
            </div>
            <button class="chat-close" id="chatClose" aria-label="Закрыть чат">×</button>
        </div>

        <div class="chat-messages" id="chatMessages">
        </div>

        <div class="chat-actions" id="chatActions">
            <button class="chat-btn" data-action="helper">Вызвать помощника</button>
        </div>
    </div>

    <header class="hero">
        <div class="slide active" style="background-image: url('img/fon1.jpg');"></div>
        <div class="slide" style="background-image: url('img/fon2.jpg');"></div>
        <div class="slide" style="background-image: url('img/fon3.jpg');"></div>

        <div class="overlay"></div>

        <div class="content">
          <nav class="navbar navbar-glass">
    <a href="index.php" class="site-brand" style="text-decoration:none;">
        <div class="site-title">"ООО" <span>Крона</span>/<span>Крона+</span></div>
        <div class="site-subtitle"></div>
    </a>

    <ul class="nav-links nav-links-modern">
        <li>
            <button class="theme-toggle" id="themeToggle" aria-label="Переключить тему" title="Светлая / тёмная тема">
                <span class="theme-toggle__track">
                    <span class="theme-toggle__thumb">
                        <span class="theme-icon theme-icon--dark">🌙</span>
                        <span class="theme-icon theme-icon--light">☀️</span>
                    </span>
                </span>
            </button>
        </li>
        <div class="account-area">
        <button class="account-btn" id="accountBtn" type="button" aria-haspopup="true" aria-expanded="false">
            <span class="account-icon">👤</span>
        </button>

        <div class="account-menu" id="accountMenu">
            <?php if ($loggedIn): ?>
                <a href="<?= cabinetUrl() ?>">Личный кабинет</a>
                <a href="logout.php">Выход</a>
            <?php else: ?>
                <a href="register.php">Регистрация</a>
                <a href="login.php">Вход</a>
            <?php endif; ?>
        </div>
    </div>
        <li><a href="index.php">Главная</a></li>
        <li><a href="produkciya.php">Продукция</a></li>
        <li><a href="kalkulator.php">🧮 Калькулятор</a></li>
        <li><a href="#about">О компании</a></li>
        <li><a href="#contacts">Контакты</a></li>
        <li><a href="delivery.php">Доставка</a></li>
    </ul>

    
</nav>
            <section class="hero-text">
                <p class="subtitle">Надежное деревообрабатывающее предприятие</p>
                <h1>Качественные пиломатериалы из древесины</h1>
                <p class="description">
                    
                </p>
                <a href="#contacts" class="btn btn-primary">Оставить заявку</a>
            </section>
        </div>
    </header>


    <section class="about" id="about">
        <div class="container">
            <div class="about-grid">

                <div class="about-image">
                    <img src="img/card.jpg" alt="Деревообрабатывающее предприятие">
                    <div class="about-years">
                        <span class="about-years-num">25+</span>
                        <span class="about-years-lbl">лет на рынке</span>
                    </div>
                </div>

                <div class="about-text">
                    <p class="about-tag">О компании</p>
                    <h2>Надёжное деревообрабатывающее предприятие</h2>
                    <p>
                        Производим и поставляем пиломатериалы.
                        Собственное производство — полный контроль качества на каждом этапе.
                    </p>
                    <p>
                        Работаем с 2000 года. Современное оборудование, отборное сырьё и
                        внимательное отношение к каждому заказу — наш стандарт.
                    </p>
                </div>

            </div>
        </div>
    </section>

   
<section class="advantages">
    <div class="container">
        <p class="section-subtitle"></p>
        <h2 class="section-title advantages-title">Почему выбирают нас</h2>

        <div class="advantages-box">
            <div class="advantage-card">
                <div class="advantage-icon">🏭</div>
                <h3>Собственное производство</h3>
                <p>Контроль всех этапов изготовления позволяет поддерживать стабильное качество продукции.</p>
            </div>

            <div class="advantage-card">
                <div class="advantage-icon">🌲</div>
                <h3>Качественные материалы</h3>
                <p>Используем надежное сырье и соблюдаем технологию обработки древесины.</p>
            </div>

            <div class="advantage-card">
                <div class="advantage-icon">🤝</div>
                <h3>Индивидуальный подход</h3>
                <p>Учитываем требования заказчика и подбираем оптимальные решения под каждый заказ.</p>
            </div>

            <div class="advantage-card">
                <div class="advantage-icon">🚚</div>
                <h3>Доставка по заказу</h3>
                <p>Организуем доставку продукции до клиента в удобное время и в нужном объеме.</p>
            </div>
        </div>
    </div>
</section>
<section class="contacts reveal" id="contacts">
    <div class="container">
        <div class="contacts-grid">
            <div class="contacts-info">
                <p class="section-subtitle"></p>
                <h2 class="section-title contacts-title">Свяжитесь с нами</h2>
                <p class="contacts-text">
                    Мы всегда готовы ответить на вопросы, рассчитать заказ и помочь подобрать подходящую продукцию.
                </p>

                <div class="contact-cards">
                    <div class="contact-card">
                        <span>📞</span>
                        <div>
                            <h3>Телефон</h3>
                            <p>+7 (900) 123-45-67</p>
                        </div>
                    </div>

                    <div class="contact-card">
                        <span>✉️</span>
                        <div>
                            <h3>Почта</h3>
                            <p>info@krona-plus.ru</p>
                        </div>
                    </div>

                    <div class="contact-card">
                        <span>📍</span>
                        <div>
                            <h3>Адрес</h3>
                            <p>Архангельская область, п.Брин-Наволок,20Б</p>
                        </div>
                    </div>

                    <div class="contact-card">
                        <span>🕒</span>
                        <div>
                            <h3>График</h3>
                            <p>Пн–Пт: 9:00–19:00</p>
                        </div>
                    </div>
                </div>
            </div>

        <form class="contact-form" id="contactForm" action="send_zayavka.php" method="post">
            <h3>Оставить заявку</h3>
            <input type="text" name="name" placeholder="Ваше имя">
            <input type="tel" name="phone" placeholder="Номер телефона">
            <textarea name="message" placeholder="Ваше сообщение"></textarea>
            <button type="submit">Отправить</button>
        </form>

<div class="success-modal" id="successModal" aria-hidden="true">
    <div class="success-modal__box">
        <button class="success-modal__close" id="successClose" aria-label="Закрыть окно">×</button>
        <div class="success-modal__icon">✓</div>
        <h3>Заявка отправлена</h3>
        <p>Ваша заявка успешно отправлена, мы свяжемся по номеру телефона, оставленному в заявке, пожалуйста, ожидайте!</p>
        <button class="success-modal__btn" id="successOk">Понятно</button>
    </div>
</div>
        </div>

        <div class="contact-map">
            <iframe
                src="https://yandex.ru/map-widget/v1/?um=constructor%3Aplaceholder&source=constructor"
                width="100%"
                height="100%"
                frameborder="0">
            </iframe>
        </div>
    </div>
</section>

<footer class="footer">
    <div class="container footer-inner">
        <div>
            <div class="logo footer-logo">"ООО" <span>Крона<span></span>/<span></span>Крона+</span></div>
            <p>Деревообрабатывающее предприятие с акцентом на качество и надежность.</p>
        </div>

        <div class="footer-links">
            <a href="#">Главная</a>
            <a href="#">Продукция</a>
            <a href="#">О компании</a>
            <a href="#">Контакты</a>
            <a href="#">Доставка</a>
        </div>

        <div class="footer-copy">
            © 2026 "ООО" Крона/Крона+. Все права защищены.
        </div>
    </div>


</footer>
    <script src="script.js"></script>
    <a href="#top" class="back-to-top" aria-label="Наверх">↑</a>
</body>
</html>