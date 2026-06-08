<?php
session_start();
require_once 'connect.php';

// Только сотрудник и админ
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id'])) {
    header('Location: login.php'); exit;
}
$role = (int)$_SESSION['role_id'];
if ($role === 1) {
    // Клиент — отправить в профиль
    header('Location: profile.php'); exit;
}

$order_id = (int)($_GET['id'] ?? 0);
if (!$order_id) { header('Location: ' . ($role === 3 ? 'admin.php' : 'employee.php')); exit; }

// Загружаем заказ
$stmt = $pdo->prepare("
    SELECT z.*, sz.nazvanie as status_name,
           p.familiya, p.imya, p.otchestvo, p.telefon, p.email,
           p.mesto_prozhivaniya, p.data_rozhdeniya, p.avatar
    FROM zakazy z
    JOIN statusy_zakazov sz ON sz.id = z.status_id
    JOIN polzovateli p ON p.id = z.user_id
    WHERE z.id = ?
    LIMIT 1
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) { ?>
<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><title>Заказ не найден</title>
<style>body{background:#0d0d0d;color:#f0f0f0;font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;}</style></head>
<body><div><div style="font-size:48px;margin-bottom:16px;">🔍</div><h2 style="color:#c48a3a;">Заказ не найден</h2><p style="color:#888;margin:12px 0 24px;">Возможно, заказ был удалён.</p>
<a href="<?= $role===3?'admin.php':'employee.php' ?>" style="color:#c48a3a;">← Вернуться в кабинет</a></div></body></html>
<?php exit; }

// Состав заказа
$items = $pdo->prepare("
    SELECT zt.kolichestvo, zt.cena_na_moment, t.nazvanie, t.opisanie, t.foto, t.kategoriya
    FROM zakaz_tovary zt
    JOIN tovary t ON t.id = zt.tovar_id
    WHERE zt.zakaz_id = ?
");
$items->execute([$order_id]);
$orderItems = $items->fetchAll();

// Доставка
$delivery = $pdo->prepare("SELECT * FROM dostavka WHERE zakaz_id=? LIMIT 1");
$delivery->execute([$order_id]);
$delivery = $delivery->fetch();

// Статусы
$statusLabels = [
    'new'         => ['label'=>'Новый',        'icon'=>'📥', 'color'=>'#3a5f3a', 'text'=>'#8aff8a'],
    'confirmed'   => ['label'=>'Подтверждён',  'icon'=>'✅', 'color'=>'#3a4f7a', 'text'=>'#8ab4ff'],
    'in_progress' => ['label'=>'В сборке',     'icon'=>'📦', 'color'=>'#5a4a20', 'text'=>'#ffd080'],
    'delivered'   => ['label'=>'Доставлен',    'icon'=>'🏠', 'color'=>'#2a4a2a', 'text'=>'#80ff80'],
    'canceled'    => ['label'=>'Отменён',      'icon'=>'✕',  'color'=>'#4a2a2a', 'text'=>'#ff8080'],
];
$st = $statusLabels[$order['status_name']] ?? ['label'=>$order['status_name'],'icon'=>'•','color'=>'#2a2a2a','text'=>'#aaa'];

$deliveryStatuses = ['planned'=>'Запланирована','in_transit'=>'В пути','delivered'=>'Доставлена'];
$tipDostavki = $order['tip_dostavki'] === 'na_datu' ? 'Доставка на дату' : 'Самовывоз';

$backUrl = $role === 3 ? 'admin.php?tab=orders' : 'employee.php?tab=in-progress';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Заказ <?= htmlspecialchars($order['nomer_zakaza']) ?> — Крона</title>
<link rel="stylesheet" href="style.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{background:#0b0b0b;color:#f0f0f0;font-family:'Segoe UI',Arial,sans-serif;min-height:100vh;}

.top-bar{display:flex;align-items:center;justify-content:space-between;padding:14px 28px;background:rgba(13,13,13,.97);border-bottom:1px solid rgba(196,138,58,.15);position:sticky;top:0;z-index:10;backdrop-filter:blur(14px);}
.top-bar-left{display:flex;align-items:center;gap:16px;}
.logo{font-size:18px;font-weight:800;color:#fff;text-decoration:none;}.logo span{color:#c48a3a;}
.back-btn{color:#888;text-decoration:none;font-size:14px;display:flex;align-items:center;gap:6px;transition:.2s;padding:7px 14px;border-radius:8px;border:1px solid #2a2a2a;}
.back-btn:hover{color:#c48a3a;border-color:rgba(196,138,58,.3);}
.scan-badge{background:rgba(196,138,58,.12);border:1px solid rgba(196,138,58,.3);color:#c48a3a;font-size:12px;font-weight:700;padding:5px 12px;border-radius:20px;display:flex;align-items:center;gap:6px;}

.page-wrap{max-width:860px;margin:0 auto;padding:32px 20px 60px;}

/* ШАПКА ЗАКАЗА */
.order-header{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:24px;flex-wrap:wrap;}
.order-number{font-size:28px;font-weight:900;color:#fff;margin-bottom:6px;}
.order-number span{color:#c48a3a;}
.order-date{font-size:13px;color:#888;}
.status-pill{display:inline-flex;align-items:center;gap:8px;padding:8px 18px;border-radius:24px;font-size:15px;font-weight:700;}

/* СЕТКА КАРТОЧЕК */
.cards-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;}
@media(max-width:600px){.cards-grid{grid-template-columns:1fr;}}
.card{background:#141414;border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:22px;}
.card-title{font-size:12px;color:#666;font-weight:700;text-transform:uppercase;letter-spacing:.6px;margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.card-title::before{content:'';width:3px;height:14px;background:#c48a3a;border-radius:2px;display:inline-block;}
.info-row{display:flex;flex-direction:column;margin-bottom:10px;}
.info-row:last-child{margin-bottom:0;}
.info-row .lbl{font-size:11px;color:#555;font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;}
.info-row .val{font-size:14px;color:#ddd;}
.info-row .val a{color:#c48a3a;text-decoration:none;}
.info-row .val a:hover{text-decoration:underline;}

/* КЛИЕНТ */
.client-card{background:#141414;border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:22px;margin-bottom:20px;}
.client-inner{display:flex;align-items:center;gap:16px;}
.client-avatar{width:56px;height:56px;border-radius:50%;object-fit:cover;border:2px solid rgba(196,138,58,.3);flex-shrink:0;}
.client-avatar-placeholder{width:56px;height:56px;border-radius:50%;background:#2a2a2a;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;}
.client-name{font-size:18px;font-weight:800;color:#fff;margin-bottom:4px;}
.client-meta{font-size:13px;color:#888;}
.client-meta a{color:#c48a3a;text-decoration:none;}

/* СОСТАВ */
.items-card{background:#141414;border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:22px;margin-bottom:20px;}
.item-row{display:flex;align-items:center;gap:14px;padding:12px 0;border-bottom:1px solid rgba(255,255,255,.05);}
.item-row:last-child{border-bottom:none;padding-bottom:0;}
.item-img{width:44px;height:44px;border-radius:8px;object-fit:cover;background:#1e1e1e;flex-shrink:0;}
.item-name{flex:1;font-size:14px;color:#ddd;font-weight:600;}
.item-desc{font-size:12px;color:#666;margin-top:2px;}
.item-qty{font-size:14px;color:#c48a3a;font-weight:700;min-width:60px;text-align:center;}
.item-price{font-size:14px;color:#aaa;text-align:right;min-width:110px;}

/* ИТОГ */
.total-row{display:flex;justify-content:space-between;align-items:center;background:rgba(196,138,58,.07);border:1px solid rgba(196,138,58,.2);border-radius:14px;padding:18px 22px;margin-top:16px;}
.total-lbl{font-size:16px;font-weight:700;}
.total-sum{font-size:28px;font-weight:900;color:#c48a3a;}

/* ПРОГРЕСС */
.progress-card{background:#141414;border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:22px;margin-bottom:20px;}
.progress-steps{display:flex;gap:0;margin-top:16px;}
.pstep{flex:1;text-align:center;font-size:12px;color:#555;position:relative;}
.pstep-circle{width:32px;height:32px;border-radius:50%;background:#1e1e1e;border:2px solid #2a2a2a;margin:0 auto 8px;display:flex;align-items:center;justify-content:center;font-size:16px;}
.pstep.done .pstep-circle{background:#c48a3a;border-color:#c48a3a;}
.pstep.done{color:#c48a3a;}
.pstep.current .pstep-circle{background:#2a6aad;border-color:#4a8acd;animation:pulse 1.5s infinite;}
.pstep.current{color:#8ab4ff;}
.pstep-line{position:absolute;top:15px;left:calc(50% + 16px);width:calc(100% - 32px);height:2px;background:#2a2a2a;}
.pstep-line.done{background:#c48a3a;}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:.5;}}

/* ПЕЧАТЬ */
.print-btn{background:rgba(196,138,58,.1);border:1px solid rgba(196,138,58,.3);color:#c48a3a;padding:9px 20px;border-radius:10px;cursor:pointer;font-size:14px;font-weight:600;display:flex;align-items:center;gap:8px;transition:.2s;text-decoration:none;}
.print-btn:hover{background:rgba(196,138,58,.2);}
.actions-bar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:24px;}

/* ДОСТАВКА */
.delivery-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;}

@media print{
    .top-bar,.back-btn,.print-btn,.actions-bar,.scan-badge{display:none!important;}
    body{background:#fff;color:#000;}
    .card,.client-card,.items-card,.progress-card{background:#f9f9f9;border:1px solid #ddd;}
    .order-number,.client-name{color:#000!important;}
    .item-name,.total-sum{color:#000!important;}
    .card-title::before{background:#c48a3a;}
}

/* LIGHT THEME */
body.light-theme{background:linear-gradient(135deg,#f0e8d8,#e8dfc8)!important;color:#1a1208;}
body.light-theme .top-bar{background:rgba(240,232,216,.97);}
body.light-theme .card,body.light-theme .client-card,body.light-theme .items-card,body.light-theme .progress-card{background:#fff9f0;border-color:rgba(196,138,58,.15);}
body.light-theme .order-number,body.light-theme .client-name{color:#1a1208;}
body.light-theme .info-row .val{color:#2a1a08;}
body.light-theme .item-name{color:#2a1a08;}
body.light-theme .pstep-circle{background:#f0e8d8;border-color:rgba(196,138,58,.2);}
</style>
</head>
<body>

<div class="top-bar">
    <div class="top-bar-left">
        <a class="logo" href="index.php">Кро<span>на</span></a>
        <a class="back-btn" href="<?= $backUrl ?>">← Кабинет</a>
    </div>
    <div style="display:flex;align-items:center;gap:12px;">
        <div class="scan-badge">📷 QR-сканирование</div>
        <button class="print-btn" onclick="window.print()">🖨️ Печать</button>
    </div>
</div>

<div class="page-wrap">

    <!-- ШАПКА -->
    <div class="order-header">
        <div>
            <div class="order-number">Заказ <span>#<?= htmlspecialchars($order['nomer_zakaza']) ?></span></div>
            <div class="order-date">Оформлен: <?= date('d.m.Y в H:i', strtotime($order['created_at'])) ?></div>
        </div>
        <div class="status-pill" style="background:<?= $st['color'] ?>;color:<?= $st['text'] ?>;">
            <?= $st['icon'] ?> <?= $st['label'] ?>
        </div>
    </div>

    <!-- ПРОГРЕСС -->
    <?php if ($order['status_name'] !== 'canceled'): ?>
    <div class="progress-card">
        <div class="card-title">Этап выполнения</div>
        <?php
        $steps = [
            ['icon'=>'📥','label'=>'Принят',      'key'=>'new'],
            ['icon'=>'✅','label'=>'Подтверждён', 'key'=>'confirmed'],
            ['icon'=>'📦','label'=>'В сборке',    'key'=>'in_progress'],
            ['icon'=>'🏠','label'=>'Доставлен',   'key'=>'delivered'],
        ];
        $stOrder = ['new'=>0,'confirmed'=>1,'in_progress'=>2,'delivered'=>3];
        $curIdx = $stOrder[$order['status_name']] ?? 0;
        ?>
        <div class="progress-steps">
            <?php foreach ($steps as $i => $step):
                $cls = $i < $curIdx ? 'done' : ($i === $curIdx ? 'current' : '');
            ?>
                <div class="pstep <?= $cls ?>">
                    <?php if ($i < count($steps)-1): ?>
                        <div class="pstep-line <?= $i < $curIdx ? 'done' : '' ?>"></div>
                    <?php endif; ?>
                    <div class="pstep-circle"><?= $cls ? $step['icon'] : '' ?></div>
                    <?= $step['label'] ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- КЛИЕНТ -->
    <div class="client-card">
        <div class="card-title">Клиент</div>
        <div class="client-inner">
            <?php if (!empty($order['avatar']) && file_exists($order['avatar'])): ?>
                <img class="client-avatar" src="<?= htmlspecialchars($order['avatar']) ?>" alt="Аватар">
            <?php else: ?>
                <div class="client-avatar-placeholder">👤</div>
            <?php endif; ?>
            <div>
                <div class="client-name"><?= htmlspecialchars($order['familiya'].' '.$order['imya'].' '.($order['otchestvo']??'')) ?></div>
                <div class="client-meta">
                    📞 <a href="tel:<?= htmlspecialchars($order['telefon']) ?>"><?= htmlspecialchars($order['telefon']) ?></a>
                    &nbsp;·&nbsp;
                    ✉️ <a href="mailto:<?= htmlspecialchars($order['email']) ?>"><?= htmlspecialchars($order['email']) ?></a>
                    <?php if (!empty($order['mesto_prozhivaniya'])): ?>
                        &nbsp;·&nbsp; 📍 <?= htmlspecialchars($order['mesto_prozhivaniya']) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ДЕТАЛИ ЗАКАЗА -->
    <div class="cards-grid">
        <div class="card">
            <div class="card-title">Детали заказа</div>
            <div class="info-row">
                <span class="lbl">Тип получения</span>
                <span class="val"><?= $tipDostavki ?></span>
            </div>
            <?php if ($order['tip_dostavki'] === 'na_datu' && !empty($order['data_dostavki']) && $order['data_dostavki'] !== '0000-00-00'): ?>
            <div class="info-row">
                <span class="lbl">Желаемая дата доставки</span>
                <span class="val" style="color:#c48a3a;font-weight:700;"><?= date('d.m.Y', strtotime($order['data_dostavki'])) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($order['komentariy'])): ?>
            <div class="info-row">
                <span class="lbl">Комментарий клиента</span>
                <span class="val" style="font-style:italic;color:#bbb;">"<?= htmlspecialchars($order['komentariy']) ?>"</span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="lbl">Дата оформления</span>
                <span class="val"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></span>
            </div>
        </div>

        <?php if ($delivery): ?>
        <div class="card">
            <div class="card-title">Доставка</div>
            <?php $dsLabel = $deliveryStatuses[$delivery['status']] ?? $delivery['status']; ?>
            <div class="info-row">
                <span class="lbl">Статус доставки</span>
                <span class="val">
                    <span class="delivery-badge" style="background:<?= ['planned'=>'rgba(50,100,200,.2)','in_transit'=>'rgba(200,150,50,.2)','delivered'=>'rgba(50,150,50,.2)'][$delivery['status']]??'#2a2a2a' ?>;color:<?= ['planned'=>'#8ab4ff','in_transit'=>'#ffd080','delivered'=>'#80ff80'][$delivery['status']]??'#aaa' ?>;">
                        <?= htmlspecialchars($dsLabel) ?>
                    </span>
                </span>
            </div>
            <div class="info-row">
                <span class="lbl">Адрес</span>
                <span class="val"><?= htmlspecialchars($delivery['address']) ?></span>
            </div>
            <?php if (!empty($delivery['delivery_date']) && $delivery['delivery_date'] !== '0000-00-00'): ?>
            <div class="info-row">
                <span class="lbl">Дата доставки</span>
                <span class="val" style="color:#c48a3a;font-weight:700;"><?= date('d.m.Y', strtotime($delivery['delivery_date'])) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($delivery['note'])): ?>
            <div class="info-row">
                <span class="lbl">Примечание</span>
                <span class="val" style="font-style:italic;color:#bbb;"><?= htmlspecialchars($delivery['note']) ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="card" style="display:flex;align-items:center;justify-content:center;color:#555;font-size:14px;">
            Доставка не назначена
        </div>
        <?php endif; ?>
    </div>

    <!-- СОСТАВ ЗАКАЗА -->
    <div class="items-card">
        <div class="card-title">Состав заказа</div>
        <?php foreach ($orderItems as $it): ?>
        <div class="item-row">
            <?php if (!empty($it['foto'])): ?>
                <img class="item-img" src="<?= htmlspecialchars($it['foto']) ?>" alt="">
            <?php else: ?>
                <div class="item-img" style="display:flex;align-items:center;justify-content:center;font-size:20px;">🪵</div>
            <?php endif; ?>
            <div style="flex:1;">
                <div class="item-name"><?= htmlspecialchars($it['nazvanie']) ?></div>
                <?php if (!empty($it['opisanie'])): ?>
                    <div class="item-desc"><?= htmlspecialchars($it['opisanie']) ?></div>
                <?php endif; ?>
                <?php if (!empty($it['kategoriya'])): ?>
                    <div class="item-desc" style="color:#555;"><?= htmlspecialchars($it['kategoriya']) ?></div>
                <?php endif; ?>
            </div>
            <div class="item-qty"><?= (int)$it['kolichestvo'] ?> шт.</div>
            <div class="item-price">
                <?= number_format($it['cena_na_moment'], 2, '.', ' ') ?> ₽/шт.<br>
                <strong style="color:#ddd;"><?= number_format($it['cena_na_moment'] * $it['kolichestvo'], 2, '.', ' ') ?> ₽</strong>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="total-row">
            <span class="total-lbl">Итого по заказу</span>
            <span class="total-sum"><?= number_format($order['obshaya_summa'], 2, '.', ' ') ?> ₽</span>
        </div>
    </div>

    <!-- БЫСТРЫЕ ДЕЙСТВИЯ для сотрудника -->
    <?php if ($order['status_name'] !== 'delivered' && $order['status_name'] !== 'canceled'): ?>
    <div class="card" style="margin-bottom:20px;">
        <div class="card-title">Быстрые действия</div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:4px;">
            <?php if ($order['status_name'] === 'new'): ?>
                <form method="post" action="<?= $role===3?'admin.php':'employee.php' ?>">
                    <input type="hidden" name="<?= $role===3?'zid':'order_id' ?>" value="<?= $order['id'] ?>">
                    <?php if ($role===3): ?>
                        <input type="hidden" name="status_id" value="2">
                        <button type="submit" name="change_order_status" style="background:#2a6aad;color:#fff;border:none;padding:10px 20px;border-radius:10px;cursor:pointer;font-size:14px;font-weight:600;">✅ Подтвердить заказ</button>
                    <?php else: ?>
                        <button type="submit" name="take_order" style="background:#c48a3a;color:#fff;border:none;padding:10px 20px;border-radius:10px;cursor:pointer;font-size:14px;font-weight:600;">📥 Принять в работу</button>
                    <?php endif; ?>
                </form>
            <?php elseif ($order['status_name'] === 'confirmed'): ?>
                <form method="post" action="<?= $role===3?'admin.php':'employee.php' ?>">
                    <input type="hidden" name="<?= $role===3?'zid':'order_id' ?>" value="<?= $order['id'] ?>">
                    <?php if ($role===3): ?>
                        <input type="hidden" name="status_id" value="3">
                        <button type="submit" name="change_order_status" style="background:#5a4a20;color:#ffd080;border:none;padding:10px 20px;border-radius:10px;cursor:pointer;font-size:14px;font-weight:600;">📦 Начать сборку</button>
                    <?php else: ?>
                        <button type="submit" name="progress_order" style="background:#2a6aad;color:#fff;border:none;padding:10px 20px;border-radius:10px;cursor:pointer;font-size:14px;font-weight:600;">📦 Начать сборку</button>
                    <?php endif; ?>
                </form>
            <?php elseif ($order['status_name'] === 'in_progress'): ?>
                <form method="post" action="<?= $role===3?'admin.php':'employee.php' ?>">
                    <input type="hidden" name="<?= $role===3?'zid':'order_id' ?>" value="<?= $order['id'] ?>">
                    <?php if ($role===3): ?>
                        <input type="hidden" name="status_id" value="4">
                        <button type="submit" name="change_order_status" style="background:#2a4a2a;color:#80ff80;border:none;padding:10px 20px;border-radius:10px;cursor:pointer;font-size:14px;font-weight:600;">🏠 Отметить доставленным</button>
                    <?php else: ?>
                        <button type="submit" name="complete_order" style="background:#2a7a3a;color:#fff;border:none;padding:10px 20px;border-radius:10px;cursor:pointer;font-size:14px;font-weight:600;">🏠 Отметить доставленным</button>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
            <a href="<?= $backUrl ?>" style="background:#1e1e1e;color:#888;border:1px solid #2a2a2a;padding:10px 20px;border-radius:10px;font-size:14px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;">← В кабинет</a>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
(function(){
    var saved = localStorage.getItem('krona-theme') || 'dark';
    if (saved === 'light') document.body.classList.add('light-theme');
})();
</script>
</body>
</html>
