<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_qty'])) {
        foreach ($_POST['qty'] as $pid => $q) {
            $pid = (int)$pid; $q = (int)$q;
            if ($q <= 0) unset($_SESSION['cart'][$pid]);
            else $_SESSION['cart'][$pid] = $q;
        }
        header('Location: cart.php'); exit;
    }
    if (isset($_POST['remove'])) {
        unset($_SESSION['cart'][(int)$_POST['remove']]);
        header('Location: cart.php'); exit;
    }
    if (isset($_POST['choose_pickup']))   { header('Location: checkout.php?method=pickup'); exit; }
    if (isset($_POST['choose_delivery'])) { header('Location: dostavka.php'); exit; }
}

$productIds = array_keys($_SESSION['cart']);
$products   = [];
if ($productIds) {
    $ph   = implode(',', array_fill(0, count($productIds), '?'));
    $stmt = $pdo->prepare("SELECT id, nazvanie, cena, foto, kategoriya FROM tovary WHERE id IN ($ph)");
    $stmt->execute($productIds);
    foreach ($stmt->fetchAll() as $r) $products[$r['id']] = $r;
}
$subtotal = 0;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина — Крона</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* ─── Страница корзины ─── */
        .cart-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a, #111827);
            padding: 40px 20px 60px;
        }

        .cart-wrap {
            width: min(100%, 1020px);
            margin: 0 auto;
            color: #fff;
        }

        /* Шапка */
        .cart-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
            gap: 14px;
            flex-wrap: wrap;
        }

        .cart-topbar h1 {
            font-size: 34px;
            font-weight: 800;
            margin: 0;
        }

        .cart-topbar p {
            font-size: 14px;
            color: #64748b;
            margin: 4px 0 0;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,.1);
            background: transparent;
            color: #aaa;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: .2s;
            white-space: nowrap;
        }

        .btn-back:hover { border-color: #c48a3a; color: #c48a3a; }

        /* Двухколоночный layout */
        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 24px;
            align-items: start;
        }

        /* Список товаров */
        .cart-items { display: flex; flex-direction: column; gap: 12px; }

        .cart-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.07);
            border-radius: 18px;
            transition: border-color .2s;
        }

        .cart-item:hover { border-color: rgba(196,138,58,.2); }

        .cart-item-img {
            width: 88px;
            height: 70px;
            border-radius: 12px;
            object-fit: cover;
            flex-shrink: 0;
            border: 1px solid rgba(255,255,255,.06);
        }

        .cart-item-info { flex: 1; min-width: 0; }

        .cart-item-cat {
            font-size: 11px;
            color: #c48a3a;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 4px;
        }

        .cart-item-name {
            font-size: 16px;
            font-weight: 700;
            color: #f0f0f0;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cart-item-unit { font-size: 13px; color: #64748b; }

        /* Счётчик qty */
        .qty-control {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }

        .qty-btn {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,.08);
            background: rgba(255,255,255,.05);
            color: #fff;
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: .15s;
        }

        .qty-btn:hover { background: rgba(196,138,58,.2); border-color: #c48a3a; }

        .qty-val {
            width: 46px;
            height: 30px;
            text-align: center;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 8px;
            color: #fff;
            font-size: 15px;
            font-weight: 700;
        }

        /* Цена позиции */
        .cart-item-price {
            font-size: 17px;
            font-weight: 800;
            color: #fff;
            min-width: 90px;
            text-align: right;
            flex-shrink: 0;
        }

        /* Удалить */
        .btn-remove {
            background: transparent;
            border: none;
            color: #3a3a3a;
            cursor: pointer;
            font-size: 18px;
            padding: 4px 6px;
            border-radius: 8px;
            transition: .2s;
            flex-shrink: 0;
        }

        .btn-remove:hover { background: rgba(239,68,68,.1); color: #ef4444; }

        /* Действия под списком */
        .cart-footer-actions {
            display: flex;
            gap: 10px;
            margin-top: 12px;
        }

        .btn-update {
            padding: 10px 20px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,.08);
            background: transparent;
            color: #888;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: .2s;
        }

        .btn-update:hover { border-color: #c48a3a; color: #c48a3a; }

        /* ИТОГ */
        .cart-summary {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(196,138,58,.18);
            border-radius: 20px;
            padding: 26px;
            position: sticky;
            top: 24px;
        }

        .summary-title {
            font-size: 18px;
            font-weight: 800;
            margin: 0 0 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: #64748b;
            margin-bottom: 10px;
        }

        .summary-row.total {
            font-size: 20px;
            font-weight: 800;
            color: #fff;
            padding-top: 14px;
            border-top: 1px solid rgba(255,255,255,.07);
            margin-top: 6px;
        }

        .summary-row.total span:last-child { color: #c48a3a; }

        .summary-note {
            font-size: 12px;
            color: #374151;
            margin: 12px 0 20px;
            line-height: 1.5;
        }

        .btn-delivery-main {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 14px;
            border-radius: 14px;
            background: linear-gradient(135deg, #c48a3a, #a66d22);
            color: #fff;
            font-size: 15px;
            font-weight: 800;
            border: none;
            cursor: pointer;
            transition: .2s;
            margin-bottom: 10px;
            box-shadow: 0 4px 18px rgba(196,138,58,.3);
        }

        .btn-delivery-main:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 26px rgba(196,138,58,.45);
        }

        .btn-pickup-main {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 13px;
            border-radius: 14px;
            background: transparent;
            border: 1px solid rgba(255,255,255,.1);
            color: #aaa;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: .2s;
        }

        .btn-pickup-main:hover { border-color: #c48a3a; color: #c48a3a; }

        .secure-note {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 16px;
            padding: 10px 14px;
            border-radius: 10px;
            background: rgba(196,138,58,.06);
            font-size: 12px;
            color: #555;
        }

        /* Пустая корзина */
        .cart-empty {
            text-align: center;
            padding: 80px 20px;
        }

        .cart-empty .ico { font-size: 64px; margin-bottom: 18px; }

        .cart-empty h2 {
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .cart-empty p { color: #64748b; margin-bottom: 28px; font-size: 15px; }

        .btn-go-catalog {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 28px;
            border-radius: 14px;
            background: linear-gradient(135deg, #c48a3a, #a66d22);
            color: #fff;
            font-weight: 700;
            text-decoration: none;
            font-size: 15px;
            box-shadow: 0 4px 18px rgba(196,138,58,.3);
            transition: .2s;
        }

        .btn-go-catalog:hover { transform: translateY(-2px); }

        @media (max-width: 860px) {
            .cart-layout { grid-template-columns: 1fr; }
            .cart-summary { position: static; }
        }

        @media (max-width: 560px) {
            .cart-topbar h1 { font-size: 26px; }
            .cart-item-img { width: 68px; height: 54px; }
            .cart-item-price { min-width: 70px; font-size: 15px; }
        }
    
/* ===== LIGHT THEME ===== */
body.light-theme .cart-page {
    background: linear-gradient(135deg,#f0e8d8,#e8dfc8) !important;
}
body.light-theme .cart-wrap { color: #1a1208; }
body.light-theme .cart-topbar h1 { color: #1a1208; }
body.light-theme .cart-topbar p { color: #7a6040; }
body.light-theme .btn-back {
    border-color: rgba(196,138,58,.3) !important;
    color: #c48a3a !important;
    background: rgba(196,138,58,.07) !important;
}
body.light-theme .btn-back:hover { border-color: #c48a3a !important; background: rgba(196,138,58,.15) !important; }

/* Cart items */
body.light-theme .cart-item,
body.light-theme [class*="cart-item"],
body.light-theme .cart-card {
    background: #fff9f0 !important;
    border-color: rgba(196,138,58,.18) !important;
    color: #1a1208 !important;
    box-shadow: 0 4px 14px rgba(0,0,0,.06) !important;
}
body.light-theme .cart-item h3,
body.light-theme .cart-item p,
body.light-theme .cart-item span { color: #1a1208 !important; }
body.light-theme .item-price,
body.light-theme .cart-price,
body.light-theme [class*="price"] { color: #c48a3a !important; }

/* Quantity input */
body.light-theme input[type="number"],
body.light-theme .qty-input {
    background: #fff !important;
    border-color: rgba(196,138,58,.35) !important;
    color: #1a1208 !important;
}
body.light-theme .qty-btn,
body.light-theme [class*="qty-btn"] {
    background: rgba(196,138,58,.12) !important;
    border-color: rgba(196,138,58,.25) !important;
    color: #c48a3a !important;
}

/* Order summary */
body.light-theme .cart-summary,
body.light-theme .order-summary,
body.light-theme [class*="summary"] {
    background: #fff9f0 !important;
    border-color: rgba(196,138,58,.18) !important;
    color: #1a1208 !important;
}
body.light-theme .cart-summary h2,
body.light-theme .cart-summary p,
body.light-theme .cart-summary span { color: #1a1208 !important; }
body.light-theme .summary-total,
body.light-theme [class*="total"] { color: #c48a3a !important; }
body.light-theme .divider,
body.light-theme hr { border-color: rgba(196,138,58,.2) !important; }

/* Empty state */
body.light-theme .empty-cart,
body.light-theme [class*="empty"] { color: #7a6040 !important; }

/* Textarea / note */
body.light-theme textarea,
body.light-theme .note-input {
    background: #fff !important;
    border-color: rgba(196,138,58,.3) !important;
    color: #1a1208 !important;
}
body.light-theme textarea::placeholder { color: #a08060 !important; }

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
<div class="cart-page">
<div class="cart-wrap">

    <!-- Шапка -->
    <div class="cart-topbar">
        <div>
            <h1>Корзина</h1>
            <p><?= empty($_SESSION['cart']) ? 'Корзина пуста' : 'Проверьте товары и выберите способ получения' ?></p>
        </div>
        <a href="produkciya.php" class="btn-back">← В каталог</a>
    </div>

    <?php if (empty($_SESSION['cart'])): ?>

        <!-- ПУСТАЯ КОРЗИНА -->
        <div class="cart-empty">
            <div class="ico">🛒</div>
            <h2>В корзине пусто</h2>
            <p>Добавьте товары из нашего каталога</p>
            <a href="produkciya.php" class="btn-go-catalog">🪵 Перейти к товарам</a>
        </div>

    <?php else: ?>

    <form method="post" id="cartForm">
    <div class="cart-layout">

        <!-- ТОВАРЫ -->
        <div>
            <div class="cart-items">
                <?php foreach ($_SESSION['cart'] as $pid => $qty):
                    $pid = (int)$pid;
                    $p   = $products[$pid] ?? null;
                    if (!$p) continue;
                    $line = $p['cena'] * $qty;
                    $subtotal += $line;
                ?>
                <div class="cart-item">
                    <img class="cart-item-img"
                         src="<?= htmlspecialchars(!empty($p['foto']) ? $p['foto'] : 'img/users/default-avatar.png') ?>"
                         alt="<?= htmlspecialchars($p['nazvanie']) ?>">

                    <div class="cart-item-info">
                        <?php if (!empty($p['kategoriya'])): ?>
                            <div class="cart-item-cat"><?= htmlspecialchars($p['kategoriya']) ?></div>
                        <?php endif; ?>
                        <div class="cart-item-name"><?= htmlspecialchars($p['nazvanie']) ?></div>
                        <div class="cart-item-unit"><?= number_format((float)$p['cena'], 0, ',', ' ') ?> ₽ / шт.</div>
                    </div>

                    <div class="qty-control">
                        <button type="button" class="qty-btn" onclick="changeQty(<?= $pid ?>, -1)">−</button>
                        <input class="qty-val" type="number"
                               name="qty[<?= $pid ?>]"
                               id="qty_<?= $pid ?>"
                               value="<?= $qty ?>" min="0" max="9999">
                        <button type="button" class="qty-btn" onclick="changeQty(<?= $pid ?>, 1)">+</button>
                    </div>

                    <div class="cart-item-price"><?= number_format($line, 0, ',', ' ') ?> ₽</div>

                    <button type="submit" name="remove" value="<?= $pid ?>"
                            class="btn-remove"
                            title="Удалить"
                            onclick="return confirm('Удалить товар из корзины?')">✕</button>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-footer-actions">
                <button type="submit" name="update_qty" class="btn-update">🔄 Обновить корзину</button>
            </div>
        </div>

        <!-- ИТОГ -->
        <div class="cart-summary">
            <div class="summary-title">Ваш заказ</div>

            <div class="summary-row">
                <span>Позиций</span>
                <span><?= count($_SESSION['cart']) ?></span>
            </div>
            <div class="summary-row">
                <span>Количество</span>
                <span><?= array_sum($_SESSION['cart']) ?> шт.</span>
            </div>
            <div class="summary-row total">
                <span>Итого</span>
                <span><?= number_format($subtotal, 0, ',', ' ') ?> ₽</span>
            </div>

            <p class="summary-note">Стоимость доставки рассчитывается отдельно</p>

            <button type="submit" name="choose_delivery" class="btn-delivery-main">
                🚚 Оформить с доставкой
            </button>
            <button type="submit" name="choose_pickup" class="btn-pickup-main">
                🏭 Самовывоз
            </button>

            <div class="secure-note">🔒 Безопасное оформление заказа</div>
        </div>

    </div>
    </form>

    <?php endif; ?>

</div>
</div>

<script>
function changeQty(pid, delta) {
    const inp = document.getElementById('qty_' + pid);
    let val = parseInt(inp.value) + delta;
    if (val < 0) val = 0;
    inp.value = val;
}
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
