<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$stmt = $pdo->query("
    SELECT id, nazvanie, opisanie, cena, foto, kategoriya, kolichestvo
    FROM tovary
    WHERE aktiven = 1
    ORDER BY id DESC
");

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cartCount = 0;
foreach ($_SESSION['cart'] as $qty) {
    $cartCount += (int)$qty;
}

$categories = array_unique(array_filter(array_column($products, 'kategoriya')));
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Продукция — Крона</title>

<link rel="stylesheet" href="style.css">

<style>

/* =========================================
   GLOBAL
========================================= */

:root{
    --bg:#0b0b0b;
    --card:#131313;
    --text:#f5f5f5;
    --muted:#8a8a8a;
    --line:rgba(255,255,255,.07);
    --gold:#c48a3a;
    --gold2:#9f661f;
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    background:
        radial-gradient(circle at top left, rgba(196,138,58,.12), transparent 24%),
        radial-gradient(circle at bottom right, rgba(196,138,58,.08), transparent 30%),
        var(--bg);

    color:var(--text);
    font-family:Inter, Arial, sans-serif;
    overflow-x:hidden;
}

a{
    text-decoration:none;
    color:inherit;
}

/* =========================================
   BACKGROUND BLUR
========================================= */

.bg-blur{
    position:fixed;
    border-radius:50%;
    filter:blur(120px);
    opacity:.12;
    pointer-events:none;
    z-index:-1;
}

.blur1{
    width:320px;
    height:320px;
    background:#c48a3a;
    top:-120px;
    left:-120px;
}

.blur2{
    width:260px;
    height:260px;
    background:#c48a3a;
    right:-100px;
    bottom:-100px;
}

/* =========================================
   PAGE
========================================= */

.products-page{
    min-height:100vh;
    padding:60px 20px 90px;
}

.products-container{
    width:min(100%, 1280px);
    margin:auto;
}

/* =========================================
   HEADER
========================================= */

.products-head{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:20px;
    flex-wrap:wrap;
    margin-bottom:34px;
}

.products-head h1{
    font-size:56px;
    line-height:1;
    font-weight:900;
    letter-spacing:-2px;
    margin-bottom:10px;
}

.products-head p{
    color:var(--muted);
    font-size:16px;
    line-height:1.7;
}

.home-btn,
.cart-link{
    display:inline-flex;
    align-items:center;
    gap:10px;
    padding:14px 22px;
    border-radius:16px;
    background:rgba(255,255,255,.04);
    border:1px solid var(--line);
    color:#fff;
    font-weight:700;
    transition:.25s;
    backdrop-filter:blur(10px);
}

.home-btn:hover,
.cart-link:hover{
    transform:translateY(-3px);
    border-color:rgba(196,138,58,.3);
    box-shadow:0 14px 30px rgba(0,0,0,.35);
}

.cart-badge{
    min-width:24px;
    height:24px;
    border-radius:999px;
    background:linear-gradient(135deg,var(--gold),var(--gold2));
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:12px;
    font-weight:800;
}

/* =========================================
   NOTICE
========================================= */

.notice{
    margin-bottom:24px;
    padding:16px 20px;
    border-radius:18px;
    background:rgba(22,90,22,.22);
    border:1px solid rgba(100,255,100,.15);
    color:#9cff9c;
    font-weight:700;
    transition:.3s;
}

/* =========================================
   TOOLBAR
========================================= */

.products-toolbar{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    flex-wrap:wrap;

    padding:20px;
    margin-bottom:22px;

    background:rgba(255,255,255,.03);
    border:1px solid var(--line);
    border-radius:24px;

    backdrop-filter:blur(14px);
}

/* FILTERS */

.filter-btns{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
}

.filter-btn{
    border:none;
    cursor:pointer;

    padding:11px 18px;
    border-radius:999px;

    background:rgba(255,255,255,.03);
    border:1px solid rgba(255,255,255,.08);

    color:#aaa;
    font-size:13px;
    font-weight:700;

    transition:.22s ease;
}

.filter-btn:hover{
    transform:translateY(-2px);
    color:#fff;
    border-color:rgba(196,138,58,.3);
}

.filter-btn.active{
    background:linear-gradient(135deg,var(--gold),var(--gold2));
    color:#fff;
    border-color:transparent;
    box-shadow:0 10px 24px rgba(196,138,58,.25);
}

/* SEARCH */

.search-wrap{
    position:relative;
}

.search-wrap::before{
    content:'🔍';
    position:absolute;
    left:14px;
    top:50%;
    transform:translateY(-50%);
    font-size:14px;
    opacity:.6;
}

.search-input{
    width:260px;
    padding:13px 16px 13px 42px;

    border-radius:16px;
    border:1px solid rgba(255,255,255,.08);

    background:rgba(255,255,255,.03);
    color:#fff;
    outline:none;

    font-size:14px;
    transition:.25s;
}

.search-input:focus{
    border-color:rgba(196,138,58,.4);
    background:rgba(196,138,58,.05);
    box-shadow:0 0 0 4px rgba(196,138,58,.08);
}

.search-input::placeholder{
    color:#666;
}

/* =========================================
   RESULTS
========================================= */

.results-count{
    margin-bottom:26px;
    color:#777;
    font-size:14px;
}

.results-count span{
    color:var(--gold);
    font-weight:800;
}

/* =========================================
   PRODUCTS GRID
========================================= */

.products-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(290px,1fr));
    gap:26px;
}

/* =========================================
   PRODUCT CARD
========================================= */

.product-card{
    position:relative;
    overflow:hidden;

    display:flex;
    flex-direction:column;

    background:linear-gradient(180deg,#171717,#101010);

    border:1px solid rgba(255,255,255,.06);
    border-radius:26px;

    transition:.35s ease;

    box-shadow:
        0 10px 40px rgba(0,0,0,.3),
        inset 0 1px 0 rgba(255,255,255,.03);
}

.product-card:hover{
    transform:translateY(-8px);

    border-color:rgba(196,138,58,.22);

    box-shadow:
        0 20px 50px rgba(0,0,0,.45),
        0 0 0 1px rgba(196,138,58,.05);
}

.product-card img{
    width:100%;
    height:220px;
    object-fit:cover;
    display:block;
    transition:.45s ease;
}

.product-card:hover img{
    transform:scale(1.04);
}

.product-content{
    display:flex;
    flex-direction:column;
    flex:1;

    padding:20px;
}

.product-cat-badge{
    display:inline-flex;
    align-items:center;

    width:max-content;

    padding:5px 11px;
    margin-bottom:14px;

    border-radius:999px;

    background:rgba(196,138,58,.12);
    border:1px solid rgba(196,138,58,.22);

    color:#d89d4d;

    font-size:10px;
    font-weight:800;
    letter-spacing:.6px;
    text-transform:uppercase;
}

.product-content h3{
    font-size:22px;
    font-weight:900;
    line-height:1.3;

    margin-bottom:10px;

    display:-webkit-box;
    -webkit-line-clamp:2;
    -webkit-box-orient:vertical;
    overflow:hidden;
}

.product-content p{
    color:#8b8b8b;

    font-size:14px;
    line-height:1.7;

    margin-bottom:18px;

    display:-webkit-box;
    -webkit-line-clamp:3;
    -webkit-box-orient:vertical;
    overflow:hidden;

    min-height:auto;
}

.product-bottom{
    margin-top:auto;

    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
}

.product-price{
    font-size:28px;
    font-weight:900;
    color:var(--gold);

    line-height:1;
}

.product-btn{
    border:none;
    cursor:pointer;

    padding:12px 18px;
    border-radius:14px;

    background:linear-gradient(135deg,var(--gold),var(--gold2));

    color:#fff;
    font-weight:800;
    font-size:13px;

    transition:.25s ease;

    white-space:nowrap;

    box-shadow:0 10px 24px rgba(196,138,58,.18);
}

.product-btn:hover{
    transform:translateY(-2px);
    box-shadow:0 14px 30px rgba(196,138,58,.28);
}

.product-btn.in-cart{
    background:linear-gradient(135deg,#1f6128,#2f8f3d);
    color:#c9ffd0;
}

/* =========================================
   GRID
========================================= */

.products-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(320px,1fr));
    gap:24px;
}

/* =========================================
   MOBILE
========================================= */

@media(max-width:768px){

    .products-grid{
        grid-template-columns:1fr;
    }

    .product-card img{
        height:200px;
    }

    .product-bottom{
        flex-direction:column;
        align-items:flex-start;
    }

    .product-btn{
        width:100%;
    }
}

/* BUTTON */

.product-btn{
    border:none;
    cursor:pointer;

    padding:13px 18px;
    border-radius:14px;

    background:linear-gradient(135deg,var(--gold),var(--gold2));

    color:#fff;
    font-weight:800;
    font-size:14px;

    transition:.25s ease;

    box-shadow:0 10px 24px rgba(196,138,58,.18);
}

.product-btn:hover{
    transform:translateY(-3px);
    box-shadow:0 16px 34px rgba(196,138,58,.3);
}

.product-btn.in-cart{
    background:linear-gradient(135deg,#1f6128,#2f8f3d);
    color:#c9ffd0;
}

/* =========================================
   EMPTY
========================================= */

.empty-state{
    display:none;
    grid-column:1/-1;

    padding:90px 20px;

    text-align:center;

    border-radius:28px;
    border:1px dashed rgba(255,255,255,.08);

    background:rgba(255,255,255,.02);
}

.empty-state.show{
    display:block;
}

.empty-state .ico{
    font-size:60px;
    margin-bottom:18px;
}

.empty-state p{
    color:#777;
    font-size:16px;
}

/* =========================================
   SCROLLBAR
========================================= */

::-webkit-scrollbar{
    width:10px;
}

::-webkit-scrollbar-track{
    background:#0e0e0e;
}

::-webkit-scrollbar-thumb{
    background:#2a2a2a;
    border-radius:999px;
}

::-webkit-scrollbar-thumb:hover{
    background:#c48a3a;
}

/* =========================================
   ANIMATION
========================================= */

.product-card{
    animation:fadeUp .45s ease;
}

@keyframes fadeUp{
    from{
        opacity:0;
        transform:translateY(20px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}

/* =========================================
   MOBILE
========================================= */

@media(max-width:860px){

    .products-head{
        align-items:flex-start;
    }

    .products-head h1{
        font-size:42px;
    }

    .products-toolbar{
        flex-direction:column;
        align-items:stretch;
    }

    .search-input{
        width:100%;
    }

    .product-bottom{
        flex-direction:column;
        align-items:flex-start;
    }

    .product-btn{
        width:100%;
    }
}

@media(max-width:560px){

    .products-page{
        padding-top:36px;
    }

    .products-head h1{
        font-size:34px;
    }

    .products-grid{
        grid-template-columns:1fr;
    }

    .product-card img{
        height:220px;
    }

    .home-btn,
    .cart-link{
        width:100%;
        justify-content:center;
    }
}


/* ===== LIGHT THEME overrides ===== */
body.light-theme { background: #f5f0e8 !important; }
body.light-theme .products-page { background: #f5f0e8 !important; color: #1a1208; }
body.light-theme .products-container { background: #f5f0e8; }
body.light-theme .products-head h1,
body.light-theme .products-page h1,
body.light-theme .products-page h2,
body.light-theme .products-page h3,
body.light-theme .products-page p { color: #1a1208 !important; }

/* Filter buttons */
body.light-theme .filter-btn {
    background: rgba(196,138,58,.07) !important;
    border-color: rgba(196,138,58,.2) !important;
    color: #5a4a2a !important;
}
body.light-theme .filter-btn:hover { color: #c48a3a !important; border-color: #c48a3a !important; }
body.light-theme .filter-btn.active { background: linear-gradient(135deg,#c48a3a,#a66d22) !important; color: #fff !important; border-color: transparent !important; }

/* Search input */
body.light-theme .search-input {
    background: #fff9f0 !important;
    border-color: rgba(196,138,58,.3) !important;
    color: #1a1208 !important;
    box-shadow: 0 2px 8px rgba(196,138,58,.08);
}
body.light-theme .search-input::placeholder { color: #a08060 !important; }
body.light-theme .search-input:focus { border-color: #c48a3a !important; box-shadow: 0 0 0 3px rgba(196,138,58,.12) !important; }

/* Product cards */
body.light-theme .product-card {
    background: #fff9f0 !important;
    border-color: rgba(196,138,58,.18) !important;
    color: #1a1208 !important;
    box-shadow: 0 6px 20px rgba(0,0,0,.07) !important;
}
body.light-theme .product-card h2,
body.light-theme .product-card h3,
body.light-theme .product-card p,
body.light-theme .product-card span { color: #1a1208 !important; }
body.light-theme .product-price,
body.light-theme .price { color: #c48a3a !important; }
body.light-theme .product-unit,
body.light-theme .unit { color: #7a6040 !important; }

/* Select, quantity input */
body.light-theme select,
body.light-theme input[type="number"],
body.light-theme input[type="text"] {
    background: #fff !important;
    border-color: rgba(196,138,58,.3) !important;
    color: #1a1208 !important;
}

</style>
<script>
(function(){var t=localStorage.getItem('krona-theme');if(t==='light')document.documentElement.classList.add('light-theme-pre');})();
</script>
<style>
html.light-theme-pre body{background:#f5f0e8 !important;color:#1a1208 !important;}
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

<div class="bg-blur blur1"></div>
<div class="bg-blur blur2"></div>

<div class="products-page">
<div class="products-container">

    <!-- HEADER -->

    <div class="products-head">

        <a href="index.php"
           class="home-btn"
           title="На главную">
            ← Главная
        </a>

        <div>

            <div style="
                display:inline-flex;
                align-items:center;
                gap:8px;
                padding:8px 14px;
                border-radius:999px;
                background:rgba(196,138,58,.12);
                border:1px solid rgba(196,138,58,.2);
                color:#d89d4d;
                font-size:12px;
                font-weight:800;
                letter-spacing:1px;
                text-transform:uppercase;
                margin-bottom:16px;
            ">
                🪵 Каталог продукции
            </div>

            <h1>Продукция</h1>

            <p>
                Пиломатериалы, доска, брус и другие товары
                с доставкой по всей России.
            </p>

        </div>

        <a class="cart-link" href="cart.php">
            🛒 Корзина
            <span class="cart-badge"><?= $cartCount ?></span>
        </a>

    </div>

    <?php if (!empty($_GET['added'])): ?>
        <div class="notice">
            ✓ Товар добавлен в корзину
        </div>
    <?php endif; ?>

    <!-- FILTERS -->

    <div class="products-toolbar">

        <div class="filter-btns">

            <button class="filter-btn active" data-cat="all">
                Все товары
            </button>

            <?php foreach ($categories as $cat): ?>

                <button class="filter-btn"
                        data-cat="<?= htmlspecialchars($cat) ?>">

                    <?= htmlspecialchars($cat) ?>

                </button>

            <?php endforeach; ?>

        </div>

        <div class="search-wrap">
            <input
                class="search-input"
                type="text"
                id="searchInput"
                placeholder="Поиск товара..."
            >
        </div>

    </div>

    <!-- RESULTS -->

    <div class="results-count">
        Показано:
        <span id="countNum"><?= count($products) ?></span>
        товаров
    </div>

    <!-- PRODUCTS -->

    <div class="products-grid" id="productsGrid">

        <?php if ($products): ?>

            <?php foreach ($products as $p):

                $inCart = isset($_SESSION['cart'][$p['id']])
                    && $_SESSION['cart'][$p['id']] > 0;
            ?>

            <div class="product-card"
                 data-cat="<?= htmlspecialchars($p['kategoriya'] ?? '') ?>"
                 data-name="<?= htmlspecialchars(mb_strtolower($p['nazvanie'])) ?>">

                <img src="<?= htmlspecialchars(!empty($p['foto']) ? $p['foto'] : 'img/users/default-avatar.png') ?>"
                     alt="<?= htmlspecialchars($p['nazvanie']) ?>">

                <div class="product-content">

                    <?php if (!empty($p['kategoriya'])): ?>
                        <div class="product-cat-badge">
                            <?= htmlspecialchars($p['kategoriya']) ?>
                        </div>
                    <?php endif; ?>

                    <h3><?= htmlspecialchars($p['nazvanie']) ?></h3>

                    <p>
                        <?= htmlspecialchars(
                            mb_strimwidth(
                                $p['opisanie'] ?? '',
                                0,
                                120,
                                '...'
                            )
                        ) ?>
                    </p>

                    <div class="product-bottom">

                        <div>
                            <div style="
                                color:#666;
                                font-size:12px;
                                margin-bottom:4px;
                            ">
                                Цена
                            </div>

                            <span class="product-price">
                                <?= number_format((float)$p['cena'], 0, ',', ' ') ?> ₽
                            </span>
                        </div>

                        <?php
                            $stock = (int)($p['kolichestvo'] ?? 0);
                            $stockColor = $stock === 0 ? '#e05252' : ($stock <= 10 ? '#e0a030' : '#52c052');
                            $stockLabel = $stock === 0 ? 'Нет в наличии' : "В наличии: {$stock} шт.";
                        ?>
                        <div style="font-size:12px;font-weight:600;color:<?= $stockColor ?>;margin-bottom:10px;"><?= $stockLabel ?></div>

                        <form method="post" action="add_to_cart.php">

                            <input type="hidden"
                                   name="product_id"
                                   value="<?= (int)$p['id'] ?>">

                            <input type="hidden"
                                   name="redirect"
                                   value="produkciya.php">

                            <button type="submit"
                                    class="product-btn <?= $inCart ? 'in-cart' : '' ?>"
                                    <?= $stock === 0 ? 'disabled style="opacity:.45;cursor:not-allowed;"' : '' ?>>

                                <?= $inCart ? '✓ В корзине' : '+ В корзину' ?>

                            </button>

                        </form>

                    </div>

                </div>

            </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty-state show">
                <div class="ico">🪵</div>
                <p>Товары пока не добавлены</p>
            </div>

        <?php endif; ?>

        <!-- EMPTY FILTER -->

        <div class="empty-state" id="emptyState">
            <div class="ico">🔍</div>
            <p>Ничего не найдено. Попробуйте изменить запрос.</p>
        </div>

    </div>

</div>
</div>

<script>

const filterBtns  = document.querySelectorAll('.filter-btn');
const cards       = document.querySelectorAll('.product-card');
const searchInput = document.getElementById('searchInput');
const emptyState  = document.getElementById('emptyState');
const countNum    = document.getElementById('countNum');

let activeCat = 'all';

function applyFilters(){

    const q = searchInput.value.toLowerCase().trim();

    let visible = 0;

    cards.forEach(card => {

        const cat  = card.dataset.cat || '';
        const name = card.dataset.name || '';

        const catOk  = activeCat === 'all' || cat === activeCat;
        const nameOk = !q || name.includes(q);

        if(catOk && nameOk){

            card.style.display = '';
            visible++;

        }else{

            card.style.display = 'none';

        }

    });

    countNum.textContent = visible;

    emptyState.classList.toggle('show', visible === 0);
}

filterBtns.forEach(btn => {

    btn.addEventListener('click', () => {

        filterBtns.forEach(b => b.classList.remove('active'));

        btn.classList.add('active');

        activeCat = btn.dataset.cat;

        applyFilters();

    });

});

searchInput.addEventListener('input', applyFilters);

/* AUTO HIDE NOTICE */

<?php if (!empty($_GET['added'])): ?>

setTimeout(() => {

    const notice = document.querySelector('.notice');

    if(notice){
        notice.style.opacity = '0';
        notice.style.transform = 'translateY(-10px)';
    }

}, 2500);

<?php endif; ?>

</script>

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