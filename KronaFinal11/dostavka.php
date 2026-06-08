<?php
session_start();
require_once 'connect.php';
require_once 'order_functions.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) { header('Location: cart.php'); exit; }

$user_id = (int)$_SESSION['user_id'];
$cart = $_SESSION['cart'];
$errors = [];
$success = '';

// Получаем данные пользователя для автозаполнения
$usr = $pdo->prepare("SELECT * FROM polzovateli WHERE id=? LIMIT 1");
$usr->execute([$user_id]);
$usr = $usr->fetch();

// Товары корзины
$ids = array_map('intval', array_keys($cart));
$products = [];
if ($ids) {
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare("SELECT id, nazvanie, cena, foto FROM tovary WHERE id IN ($ph)");
    $st->execute($ids);
    foreach ($st->fetchAll() as $r) $products[$r['id']] = $r;
}
$subtotal = 0;
foreach ($cart as $pid => $qty) {
    $p = $products[(int)$pid] ?? null;
    if ($p) $subtotal += $p['cena'] * $qty;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delivery'])) {
    $address = trim($_POST['address'] ?? '');
    $delivery_date = trim($_POST['delivery_date'] ?? '');
    $note = trim($_POST['note'] ?? '');
    if ($address === '') $errors[] = 'Введите адрес доставки';
    if ($delivery_date === '') $errors[] = 'Выберите дату доставки';
    if (!$errors) {
        $res = create_order($pdo, $user_id, $cart, $note, 1, 'na_datu', $delivery_date);
        if ($res) {
            $pdo->prepare("INSERT INTO dostavka (zakaz_id, address, delivery_date, status, note) VALUES (?,?,?,'planned',?)")
                ->execute([$res['order_id'], $address, $delivery_date, $note]);
            unset($_SESSION['cart']);
            $success = ['nomer' => $res['nomer'], 'total' => $res['total'], 'date' => $delivery_date];
        } else {
            $errors[] = 'Не удалось создать заказ. Попробуйте позже.';
        }
    }
}

$minDate = date('Y-m-d', strtotime('+1 day'));
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформление доставки — Крона</title>
    <link rel="stylesheet" href="style.css">
    <style>
        *{box-sizing:border-box;}
        body{margin:0;background:#0d0d0d;color:#f0f0f0;font-family:'Segoe UI',Arial,sans-serif;min-height:100vh;}

        .page-header{
            display:flex;align-items:center;justify-content:space-between;
            padding:18px 40px;background:rgba(13,13,13,.95);
            border-bottom:1px solid rgba(196,138,58,.15);
            position:sticky;top:0;z-index:10;backdrop-filter:blur(14px);
        }
        .logo{font-size:20px;font-weight:800;color:#fff;text-decoration:none;}
        .logo span{color:#c48a3a;}
        .back-link{color:#888;text-decoration:none;font-size:14px;transition:.2s;}
        .back-link:hover{color:#c48a3a;}

        .page-wrap{max-width:1060px;margin:0 auto;padding:44px 24px 60px;}

        /* STEPS */
        .steps{display:flex;align-items:center;gap:0;margin-bottom:40px;}
        .step-item{display:flex;align-items:center;gap:10px;font-size:13px;color:#555;font-weight:600;}
        .step-item.active{color:#c48a3a;}
        .step-item.done{color:#555;}
        .step-num{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;background:#222;border:2px solid #333;}
        .step-item.active .step-num{background:#c48a3a;border-color:#c48a3a;color:#fff;}
        .step-item.done .step-num{background:#2a3a2a;border-color:#3a6a3a;color:#6aaa6a;}
        .step-line{flex:1;height:2px;background:#222;margin:0 12px;max-width:60px;}
        .step-line.done{background:#c48a3a;}

        /* GRID */
        .checkout-grid{display:grid;grid-template-columns:1fr 360px;gap:28px;align-items:start;}

        /* FORM CARD */
        .form-card{background:#141414;border:1px solid rgba(255,255,255,.07);border-radius:20px;padding:32px;}
        .form-card h2{font-size:22px;font-weight:800;margin:0 0 24px;color:#fff;}
        .form-section{margin-bottom:28px;}
        .form-section h3{font-size:14px;font-weight:700;color:#c48a3a;text-transform:uppercase;letter-spacing:.7px;margin:0 0 16px;padding-bottom:10px;border-bottom:1px solid rgba(196,138,58,.15);}
        .field{margin-bottom:16px;}
        .field label{display:block;font-size:13px;color:#888;margin-bottom:7px;font-weight:600;}
        .field input,.field textarea,.field select{
            width:100%;padding:13px 16px;border-radius:12px;
            background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);
            color:#fff;font-size:15px;outline:none;transition:.2s;
            font-family:inherit;
        }
        .field input:focus,.field textarea:focus{border-color:#c48a3a;background:rgba(196,138,58,.05);}
        .field input::placeholder,.field textarea::placeholder{color:#444;}
        .field textarea{resize:vertical;min-height:90px;}
        .field-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;}

        /* DELIVERY OPTIONS */
        .delivery-options{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;}
        .del-opt{
            padding:16px;border-radius:14px;border:2px solid rgba(255,255,255,.07);
            background:rgba(255,255,255,.02);cursor:pointer;transition:.2s;text-align:center;
        }
        .del-opt:hover{border-color:rgba(196,138,58,.3);}
        .del-opt.selected{border-color:#c48a3a;background:rgba(196,138,58,.07);}
        .del-opt .ico{font-size:28px;margin-bottom:8px;}
        .del-opt .tit{font-size:14px;font-weight:700;color:#f0f0f0;}
        .del-opt .sub{font-size:12px;color:#666;margin-top:4px;}

        /* ERRORS */
        .error-box{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);color:#ffb4b4;padding:14px 18px;border-radius:12px;margin-bottom:20px;font-size:14px;}

        /* SUBMIT */
        .btn-submit{
            width:100%;padding:15px;border-radius:14px;
            background:linear-gradient(135deg,#c48a3a,#a97030);
            color:#fff;font-size:16px;font-weight:800;
            border:none;cursor:pointer;transition:.2s;
            box-shadow:0 4px 20px rgba(196,138,58,.35);
            display:flex;align-items:center;justify-content:center;gap:10px;
        }
        .btn-submit:hover{transform:translateY(-2px);box-shadow:0 8px 28px rgba(196,138,58,.5);}

        /* ORDER SUMMARY */
        .order-summary{background:#141414;border:1px solid rgba(196,138,58,.15);border-radius:20px;padding:28px;position:sticky;top:90px;}
        .os-title{font-size:18px;font-weight:800;margin:0 0 20px;}
        .os-item{display:flex;align-items:center;gap:12px;margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid rgba(255,255,255,.05);}
        .os-item:last-of-type{border-bottom:none;}
        .os-img{width:56px;height:44px;border-radius:8px;object-fit:cover;flex-shrink:0;}
        .os-name{font-size:13px;font-weight:600;color:#f0f0f0;flex:1;}
        .os-qty{font-size:12px;color:#666;}
        .os-line{font-size:14px;font-weight:700;color:#fff;}
        .os-total{display:flex;justify-content:space-between;align-items:center;padding-top:16px;border-top:1px solid rgba(255,255,255,.07);margin-top:8px;}
        .os-total .lbl{font-size:14px;color:#888;}
        .os-total .val{font-size:22px;font-weight:800;color:#c48a3a;}
        .secure-badge{display:flex;align-items:center;gap:8px;margin-top:18px;padding:12px;background:rgba(196,138,58,.06);border-radius:10px;font-size:12px;color:#888;}

        /* SUCCESS */
        .success-card{
            text-align:center;background:#141414;border:1px solid rgba(80,200,80,.2);
            border-radius:24px;padding:60px 40px;max-width:560px;margin:40px auto;
        }
        .success-icon{font-size:64px;margin-bottom:20px;}
        .success-card h2{font-size:28px;font-weight:800;margin:0 0 12px;}
        .success-card p{color:#888;font-size:15px;margin-bottom:8px;}
        .success-num{color:#c48a3a;font-size:20px;font-weight:800;margin:16px 0;}
        .btn-back{
            display:inline-flex;align-items:center;gap:8px;margin-top:24px;
            padding:13px 28px;border-radius:14px;
            background:linear-gradient(135deg,#c48a3a,#a97030);
            color:#fff;font-weight:700;text-decoration:none;font-size:15px;
        }

        @media(max-width:900px){.checkout-grid{grid-template-columns:1fr;}.order-summary{position:static;}}
        @media(max-width:600px){.page-header{padding:14px 20px;}.page-wrap{padding:24px 16px;}.field-row{grid-template-columns:1fr;}.delivery-options{grid-template-columns:1fr;}}
    </style>
</head>
<body>

<header class="page-header">
    <a href="index.php" class="logo">«ООО» <span>Крона</span></a>
    <a href="cart.php" class="back-link">← Назад в корзину</a>
</header>

<div class="page-wrap">

    <?php if ($success): ?>
    <!-- УСПЕХ -->
    <div class="success-card">
        <div class="success-icon">✅</div>
        <h2>Заказ оформлен!</h2>
        <div class="success-num">№<?= htmlspecialchars($success['nomer']) ?></div>
        <p>Сумма: <strong><?= number_format($success['total'], 0, ',', ' ') ?> ₽</strong></p>
        <p>Желаемая дата доставки: <strong><?= date('d.m.Y', strtotime($success['date'])) ?></strong></p>
        <p style="margin-top:16px;font-size:13px;">Мы свяжемся с вами для подтверждения. Следите за статусом в <a href="profile.php" style="color:#c48a3a;">личном кабинете</a>.</p>
        <a href="produkciya.php" class="btn-back">🪵 Продолжить покупки</a>
    </div>

    <?php else: ?>

    <!-- ШАГИ -->
    <div class="steps">
        <div class="step-item done"><div class="step-num">✓</div> Корзина</div>
        <div class="step-line done"></div>
        <div class="step-item active"><div class="step-num">2</div> Доставка</div>
        <div class="step-line"></div>
        <div class="step-item"><div class="step-num">3</div> Готово</div>
    </div>

    <?php if ($errors): ?>
        <div class="error-box">✕ <?= implode('<br>✕ ', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>

    <div class="checkout-grid">

        <!-- ФОРМА -->
        <form method="post">
        <div class="form-card">
            <h2>🚚 Оформление доставки</h2>

            <!-- Адрес -->
            <div class="form-section">
                <h3>Адрес доставки</h3>
                <div class="field">
                    <label>Улица, дом</label>
                    <input type="text" name="address" required placeholder="ул. Лесная, д. 5, кв. 10"
                           value="<?= htmlspecialchars($_POST['address'] ?? ($usr['mesto_prozhivaniya'] ?? '')) ?>">
                </div>
                <div class="field">
                    <label>Желаемая дата доставки</label>
                    <input type="date" name="delivery_date" required min="<?= $minDate ?>"
                           value="<?= htmlspecialchars($_POST['delivery_date'] ?? '') ?>">
                </div>
            </div>

            <!-- Получатель -->
            <div class="form-section">
                <h3>Получатель</h3>
                <div class="field-row">
                    <div class="field">
                        <label>Имя</label>
                        <input type="text" disabled value="<?= htmlspecialchars($usr['imya'] ?? '') ?>" style="opacity:.6;">
                    </div>
                    <div class="field">
                        <label>Телефон</label>
                        <input type="text" disabled value="<?= htmlspecialchars($usr['telefon'] ?? '') ?>" style="opacity:.6;">
                    </div>
                </div>
            </div>

            <!-- Комментарий -->
            <div class="form-section">
                <h3>Дополнительно</h3>
                <div class="field">
                    <label>Комментарий к заказу</label>
                    <textarea name="note" placeholder="Код домофона, удобное время, особые пожелания..."><?= htmlspecialchars($_POST['note'] ?? '') ?></textarea>
                </div>
            </div>

            <button type="submit" name="confirm_delivery" class="btn-submit">
                🚚 Подтвердить и оформить
            </button>
        </div>
        </form>

        <!-- ИТОГ -->
        <div class="order-summary">
            <div class="os-title">Ваш заказ</div>
            <?php foreach ($cart as $pid => $qty):
                $pid = (int)$pid; $p = $products[$pid] ?? null;
                if (!$p) continue;
                $line = $p['cena'] * $qty;
            ?>
            <div class="os-item">
                <img class="os-img" src="<?= htmlspecialchars($p['foto'] ?: 'img/card.jpg') ?>" alt="">
                <div style="flex:1;">
                    <div class="os-name"><?= htmlspecialchars($p['nazvanie']) ?></div>
                    <div class="os-qty"><?= $qty ?> шт.</div>
                </div>
                <div class="os-line"><?= number_format($line, 0, ',', ' ') ?> ₽</div>
            </div>
            <?php endforeach; ?>
            <div class="os-total">
                <span class="lbl">Итого к оплате</span>
                <span class="val"><?= number_format($subtotal, 0, ',', ' ') ?> ₽</span>
            </div>
            <div class="secure-badge">🔒 Безопасное оформление заказа</div>
        </div>

    </div>
    <?php endif; ?>
</div>
</body>
</html>
