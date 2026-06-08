<?php session_start(); ?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Доставка — Крона</title>

<link rel="stylesheet" href="style.css">

<style>
    *{
        margin:0;
        padding:0;
        box-sizing:border-box;
    }

    body{
        font-family: Inter, Arial, sans-serif;
        background:#0b0b0b;
        color:#f3f3f3;
        overflow-x:hidden;
    }

    a{
        text-decoration:none;
    }

    .delivery-page{
        position:relative;
        min-height:100vh;
        padding:70px 20px 90px;
        background:
            radial-gradient(circle at top left, rgba(196,138,58,.18), transparent 25%),
            radial-gradient(circle at bottom right, rgba(196,138,58,.12), transparent 30%),
            #0b0b0b;
    }

    .delivery-inner{
        width:min(100%, 1180px);
        margin:auto;
    }

    /* =========================
       HEADER
    ========================= */

    .del-header{
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:20px;
        flex-wrap:wrap;
        margin-bottom:55px;
    }

    .del-title-wrap{
        position:relative;
    }

    .del-tag{
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:8px 14px;
        border-radius:999px;
        background:rgba(196,138,58,.12);
        border:1px solid rgba(196,138,58,.25);
        color:#d89d4d;
        font-size:12px;
        font-weight:700;
        letter-spacing:1px;
        text-transform:uppercase;
        margin-bottom:16px;
    }

    .del-title-wrap h1{
        font-size:54px;
        line-height:1;
        font-weight:900;
        letter-spacing:-2px;
    }

    .del-title-wrap p{
        margin-top:16px;
        color:#8a8a8a;
        font-size:16px;
        max-width:580px;
        line-height:1.7;
    }

    .del-back{
        display:inline-flex;
        align-items:center;
        gap:10px;
        padding:14px 22px;
        border-radius:14px;
        background:rgba(255,255,255,.04);
        border:1px solid rgba(255,255,255,.08);
        color:#ddd;
        font-weight:700;
        transition:.25s ease;
        backdrop-filter:blur(10px);
    }

    .del-back:hover{
        transform:translateY(-3px);
        border-color:#c48a3a;
        color:#fff;
        box-shadow:0 12px 30px rgba(196,138,58,.15);
    }

    /* =========================
       CARDS
    ========================= */

    .glass-card{
        background:rgba(255,255,255,.03);
        border:1px solid rgba(255,255,255,.06);
        backdrop-filter:blur(16px);
        border-radius:24px;
        box-shadow:
            0 10px 40px rgba(0,0,0,.35),
            inset 0 1px 0 rgba(255,255,255,.04);
    }

    /* =========================
       STEPS
    ========================= */

    .section-head{
        margin-bottom:22px;
    }

    .section-tag{
        color:#c48a3a;
        text-transform:uppercase;
        letter-spacing:1.5px;
        font-size:12px;
        font-weight:800;
        margin-bottom:10px;
    }

    .section-title{
        font-size:32px;
        font-weight:900;
        letter-spacing:-1px;
    }

    .del-steps{
        display:grid;
        grid-template-columns:repeat(3,1fr);
        gap:22px;
        margin-bottom:55px;
    }

    .del-step{
        position:relative;
        overflow:hidden;
        padding:34px 28px;
        transition:.3s ease;
    }

    .del-step:hover{
        transform:translateY(-8px);
        border-color:rgba(196,138,58,.25);
    }

    .del-step::before{
        content:'';
        position:absolute;
        top:-60px;
        right:-60px;
        width:140px;
        height:140px;
        border-radius:50%;
        background:rgba(196,138,58,.07);
    }

    .del-step-num{
        width:60px;
        height:60px;
        border-radius:18px;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:22px;
        font-weight:900;
        background:linear-gradient(135deg,#d39a4c,#9f661f);
        color:#fff;
        margin-bottom:24px;
        box-shadow:0 8px 24px rgba(196,138,58,.35);
    }

    .del-step h3{
        font-size:20px;
        margin-bottom:12px;
        font-weight:800;
    }

    .del-step p{
        color:#9a9a9a;
        line-height:1.8;
        font-size:15px;
    }

    /* =========================
       INFO STRIP
    ========================= */

    .del-info-row{
        display:grid;
        grid-template-columns:repeat(3,1fr);
        gap:18px;
        margin-bottom:65px;
    }

    .del-info-item{
        display:flex;
        align-items:center;
        gap:18px;
        padding:22px;
        transition:.25s ease;
    }

    .del-info-item:hover{
        transform:translateY(-5px);
        border-color:rgba(196,138,58,.22);
    }

    .del-info-ico{
        width:58px;
        height:58px;
        border-radius:18px;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:28px;
        background:rgba(196,138,58,.1);
        flex-shrink:0;
    }

    .del-info-item strong{
        display:block;
        font-size:17px;
        margin-bottom:6px;
    }

    .del-info-item span{
        color:#8b8b8b;
        font-size:14px;
    }

    /* =========================
       TRANSPORT
    ========================= */

    .transport-grid{
        display:grid;
        grid-template-columns:repeat(3,1fr);
        gap:24px;
        margin-bottom:70px;
    }

    .transport-card{
        overflow:hidden;
        transition:.35s ease;
    }

    .transport-card:hover{
        transform:translateY(-10px);
        border-color:rgba(196,138,58,.25);
        box-shadow:0 20px 50px rgba(0,0,0,.45);
    }

    .transport-card img{
        width:100%;
        height:220px;
        object-fit:cover;
        display:block;
        transition:.5s ease;
    }

    .transport-card:hover img{
        transform:scale(1.06);
    }

    .transport-card-body{
        padding:24px;
    }

    .transport-top{
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        margin-bottom:18px;
    }

    .transport-card h3{
        font-size:24px;
        font-weight:800;
        margin-bottom:8px;
    }

    .capacity{
        color:#909090;
        font-size:14px;
    }

    .price{
        font-size:32px;
        font-weight:900;
        color:#d89d4d;
    }

    .price small{
        font-size:14px;
        font-weight:500;
        color:#8b8b8b;
    }

    .transport-features{
        display:flex;
        flex-direction:column;
        gap:10px;
        margin-top:18px;
    }

    .transport-features div{
        color:#b8b8b8;
        font-size:14px;
    }

    /* =========================
       CTA
    ========================= */

    .del-cta{
        position:relative;
        overflow:hidden;
        padding:60px 40px;
        text-align:center;
    }

    .del-cta::before{
        content:'';
        position:absolute;
        inset:0;
        background:
            radial-gradient(circle at top right, rgba(196,138,58,.15), transparent 30%);
        pointer-events:none;
    }

    .del-cta h2{
        font-size:40px;
        font-weight:900;
        margin-bottom:16px;
    }

    .del-cta p{
        color:#999;
        max-width:700px;
        margin:0 auto 28px;
        line-height:1.8;
        font-size:16px;
    }

    .btn-cta{
        display:inline-flex;
        align-items:center;
        gap:12px;
        padding:18px 34px;
        border-radius:18px;
        background:linear-gradient(135deg,#d39a4c,#9f661f);
        color:#fff;
        font-size:16px;
        font-weight:800;
        transition:.25s ease;
        box-shadow:0 10px 28px rgba(196,138,58,.35);
    }

    .btn-cta:hover{
        transform:translateY(-4px) scale(1.02);
        box-shadow:0 18px 40px rgba(196,138,58,.45);
    }

    /* =========================
       MOBILE
    ========================= */

    @media(max-width:960px){

        .transport-grid,
        .del-info-row,
        .del-steps{
            grid-template-columns:1fr;
        }

        .del-title-wrap h1{
            font-size:42px;
        }

        .section-title{
            font-size:28px;
        }

        .del-cta h2{
            font-size:30px;
        }
    }

    @media(max-width:560px){

        .delivery-page{
            padding-top:40px;
        }

        .del-title-wrap h1{
            font-size:34px;
        }

        .transport-card img{
            height:190px;
        }

        .del-cta{
            padding:40px 24px;
        }

        .btn-cta{
            width:100%;
            justify-content:center;
        }
    }
</style>
<script>
(function(){var t=localStorage.getItem('krona-theme');if(t==='light')document.documentElement.classList.add('light-theme-pre');})();
</script>
<style>
html.light-theme-pre body{background:#f5f0e8 !important;color:#1a1208 !important;}

/* ===== LIGHT THEME overrides ===== */
body.light-theme .delivery-page {
    background: radial-gradient(circle at top left, rgba(196,138,58,.08), transparent 25%),
                radial-gradient(circle at bottom right, rgba(196,138,58,.06), transparent 30%),
                #f5f0e8 !important;
    color: #1a1208;
}
body.light-theme .glass-card {
    background: #fff9f0 !important;
    border-color: rgba(196,138,58,.2) !important;
    box-shadow: 0 8px 28px rgba(0,0,0,.07), inset 0 1px 0 rgba(255,255,255,.9) !important;
}
body.light-theme .glass-card h3,
body.light-theme .glass-card p,
body.light-theme .glass-card span,
body.light-theme .del-step h3,
body.light-theme .del-step p { color: #1a1208 !important; }
body.light-theme .del-back {
    background: rgba(196,138,58,.08) !important;
    border-color: rgba(196,138,58,.25) !important;
    color: #c48a3a !important;
}
body.light-theme .del-back:hover {
    background: rgba(196,138,58,.16) !important;
    color: #a66d22 !important;
}
body.light-theme .section-title,
body.light-theme .section-tag,
body.light-theme .delivery-page h1,
body.light-theme .delivery-page h2,
body.light-theme .delivery-page h3 { color: #1a1208 !important; }
body.light-theme .delivery-page p { color: #3a2e1a !important; }
body.light-theme [class*="zone-card"],
body.light-theme [class*="del-zone"],
body.light-theme [class*="del-info"],
body.light-theme [class*="feature-card"],
body.light-theme [class*="del-feature"] {
    background: #fff9f0 !important;
    border-color: rgba(196,138,58,.18) !important;
    color: #1a1208 !important;
}

</style>
</head>

<body>
<!-- Theme Toggle (fixed) -->
<button class="theme-toggle theme-toggle--fixed" id="themeToggle" aria-label="Переключить тему" title="Светлая / тёмная тема">
    <span class="theme-toggle__track">
        <span class="theme-toggle__thumb">
            <span class="theme-icon theme-icon--dark">🌙</span>
            <span class="theme-icon theme-icon--light">☀️</span>
        </span>
    </span>
</button>

<div class="delivery-page">
<div class="delivery-inner">

    <!-- HEADER -->
    <div class="del-header">

        <div class="del-title-wrap">

            <div class="del-tag">
                🚚 Быстрая доставка
            </div>

            <h1>Доставка материалов</h1>

            <p>
                Надёжно доставляем пиломатериалы по всей России.
                Собственный транспорт и аккуратная погрузка.
            </p>

        </div>

        <a href="index.php" class="del-back">
            ← На главную
        </a>

    </div>

    <!-- STEPS -->

    <div class="section-head">
        <div class="section-tag">Как это работает</div>
        <div class="section-title"></div>
    </div>

    <div class="del-steps">

        <div class="del-step glass-card">

            <div class="del-step-num">1</div>

            <h3>Оформляете заказ</h3>

            <p>
                Выбираете продукцию в каталоге, добавляете товары в корзину
                и оставляете заявку.
            </p>

        </div>

        <div class="del-step glass-card">

            <div class="del-step-num">2</div>

            <h3>Подготавливаем груз</h3>

            <p>
                Проверяем качество, комплектуем заказ и надёжно фиксируем
                материалы перед отправкой.
            </p>

        </div>

        <div class="del-step glass-card">

            <div class="del-step-num">3</div>

            <h3>Доставляем к вам</h3>

            <p>
                Доставка занимает от 4 до 7 рабочих дней
                в зависимости от региона и объёма заказа.
            </p>

        </div>

    </div>

    <!-- INFO -->

    <div class="del-info-row">

        <div class="del-info-item glass-card">
            <div class="del-info-ico">🕒</div>
            <div>
                <strong>4–7 рабочих дней</strong>
                <span>Средний срок доставки</span>
            </div>
        </div>

        <div class="del-info-item glass-card">
            <div class="del-info-ico">📍</div>
            <div>
                <strong>По всей России</strong>
                <span>Работаем с регионами</span>
            </div>
        </div>

        <div class="del-info-item glass-card">
            <div class="del-info-ico">✅</div>
            <div>
                <strong>Бесплатная выгрузка</strong>
                <span>Входит в стоимость доставки</span>
            </div>
        </div>

    </div>

    <!-- TRANSPORT -->

    <div class="section-head">
        <div class="section-tag">Способы доставки</div>
        <div class="section-title"></div>
    </div>

    <div class="transport-grid">

        <!-- CARD -->

        <div class="transport-card glass-card">

            <img src="img/dostavka/gazel.jpg"
                 alt="Газель"
                 onerror="this.style.display='none'">

            <div class="transport-card-body">

                <div class="transport-top">

                    <div>
                        <h3>Газель</h3>
                        <div class="capacity">Вместимость 4–5 м³</div>
                    </div>

                    <div class="price">
                        100 ₽
                        <small>/ км</small>
                    </div>

                </div>

                <div class="transport-features">
                    <div>✔ Отлично подходит для частных заказов</div>
                    <div>✔ Быстрая доставка по городу</div>
                    <div>✔ Экономичный вариант</div>
                </div>

            </div>

        </div>

        <!-- CARD -->

        <div class="transport-card glass-card">

            <img src="img/dostavka/fishka.jpg"
                 alt="Манипулятор"
                 onerror="this.style.display='none'">

            <div class="transport-card-body">

                <div class="transport-top">

                    <div>
                        <h3>Манипулятор</h3>
                        <div class="capacity">Вместимость 8–9 м³</div>
                    </div>

                    <div class="price">
                        150 ₽
                        <small>/ км</small>
                    </div>

                </div>

                <div class="transport-features">
                    <div>✔ Самостоятельная разгрузка</div>
                    <div>✔ Для крупного объёма материалов</div>
                    <div>✔ Удобен для стройплощадок</div>
                </div>

            </div>

        </div>

        <!-- CARD -->

        <div class="transport-card glass-card">

            <img src="img/dostavka/fyra1.jpg"
                 alt="Большегруз"
                 onerror="this.style.display='none'">

            <div class="transport-card-body">

                <div class="transport-top">

                    <div>
                        <h3>Большегруз</h3>
                        <h3>Манипулятор</h3>
                        <div class="capacity">Вместимость 18–20 м³</div>
                    </div>

                    <div class="price">
                        200 ₽
                        <small>/ км</small>
                    </div>

                </div>

                <div class="transport-features">
                    <div>✔ Для больших поставок</div>
                    <div>✔ Выгодно при оптовых заказах</div>
                    <div>✔ Подходит для межгорода</div>
                </div>

            </div>

        </div>

    </div>

    <!-- CTA -->

    <div class="del-cta glass-card">

        <h2>Готовы оформить заказ?</h2>

        <p>
            Перейдите в каталог продукции, выберите нужные материалы
            и оформите доставку за пару минут.
        </p>

        <a href="produkciya.php" class="btn-cta">
            🪵 Перейти в каталог
        </a>

    </div>

</div>
</div>

<script>
(function(){
    var STORAGE_KEY='krona-theme';
    var btn=document.getElementById('themeToggle');
    var body=document.body;
    var saved=localStorage.getItem(STORAGE_KEY)||'dark';
    if(saved==='light') body.classList.add('light-theme');
    document.documentElement.classList.remove('light-theme-pre');
    if(btn){
        btn.addEventListener('click',function(){
            var isLight=body.classList.toggle('light-theme');
            localStorage.setItem(STORAGE_KEY,isLight?'light':'dark');
        });
    }
})();
</script>
</body>
</html>