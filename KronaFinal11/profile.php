<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Редирект в нужный кабинет по роли
$role = (int)($_SESSION['role_id'] ?? 1);
if ($role === 3) { header('Location: admin.php');    exit; }
if ($role === 2) { header('Location: employee.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM polzovateli WHERE id = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) { session_unset(); session_destroy(); header('Location: login.php'); exit; }

$errorAvatar = '';

// Сохранение Telegram username
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_telegram'])) {
    require_once 'telegram.php';
    $tgUsername = trim($_POST['telegram_username'] ?? '');
    $tgUsername = ltrim($tgUsername, '@');
    $chatId = null;
    if ($tgUsername !== '') {
        $chatId = tgResolveChatId($tgUsername);
    }
    $pdo->prepare("UPDATE polzovateli SET telegram_username=?, telegram_chat_id=? WHERE id=?")
        ->execute([$tgUsername ?: null, $chatId, $_SESSION['user_id']]);
    // Обновляем $user сразу
    $user['telegram_username'] = $tgUsername;
    $user['telegram_chat_id']  = $chatId;
    $tgMsg = $chatId
        ? 'Telegram привязан! Уведомления будут приходить автоматически.'
        : ($tgUsername !== ''
            ? 'Username сохранён, но бот вас пока не нашёл. Напишите боту /start и обновите снова.'
            : 'Telegram отвязан.');
    header('Location: profile.php?tg=' . urlencode($tgMsg)); exit;
}

// Загрузка аватара
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_avatar'])) {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            if (!is_dir('uploads/avatars')) mkdir('uploads/avatars', 0777, true);
            $newName = 'uploads/avatars/' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $newName)) {
                $pdo->prepare("UPDATE polzovateli SET avatar=? WHERE id=?")->execute([$newName, $_SESSION['user_id']]);
                header('Location: profile.php?ok=avatar'); exit;
            } else { $errorAvatar = 'Не удалось сохранить файл.'; }
        } else { $errorAvatar = 'Разрешены только JPG, JPEG, PNG, GIF, WEBP.'; }
    } else { $errorAvatar = 'Выберите файл для загрузки.'; }
}

// Отмена заказа клиентом
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $oid = (int)$_POST['order_id'];
    // Проверяем что заказ принадлежит этому клиенту и не доставлен
    $chk = $pdo->prepare("SELECT id, status_id FROM zakazy WHERE id=? AND user_id=? LIMIT 1");
    $chk->execute([$oid, $_SESSION['user_id']]);
    $chkRow = $chk->fetch();
    if ($chkRow && (int)$chkRow['status_id'] !== 4) { // 4 = delivered
        // Возвращаем товары на склад
        $items = $pdo->prepare("SELECT tovar_id, kolichestvo FROM zakaz_tovary WHERE zakaz_id=?");
        $items->execute([$oid]);
        $stmtStock = $pdo->prepare("UPDATE tovary SET kolichestvo = kolichestvo + ? WHERE id=?");
        foreach ($items->fetchAll() as $it) {
            $stmtStock->execute([$it['kolichestvo'], $it['tovar_id']]);
        }
        // Ставим статус canceled (id=5)
        $pdo->prepare("UPDATE zakazy SET status_id=5 WHERE id=?")->execute([$oid]);
        require_once 'telegram.php'; tgNotifyOrderStatus($pdo, $oid, 'canceled');
        header('Location: profile.php?ok=canceled'); exit;
    }
    header('Location: profile.php?err=cancel'); exit;
}

// Заказы клиента
$orders = $pdo->prepare("
    SELECT z.*, sz.nazvanie as status_name
    FROM zakazy z
    JOIN statusy_zakazov sz ON sz.id=z.status_id
    WHERE z.user_id=?
    ORDER BY z.created_at DESC
");
$orders->execute([$_SESSION['user_id']]);
$orders = $orders->fetchAll();

// Доставки клиента
$deliveries = $pdo->prepare("
    SELECT d.*, z.nomer_zakaza, z.obshaya_summa, z.status_id, sz.nazvanie as order_status
    FROM dostavka d
    JOIN zakazy z ON z.id=d.zakaz_id
    JOIN statusy_zakazov sz ON sz.id=z.status_id
    WHERE z.user_id=?
    ORDER BY d.delivery_date ASC
");
$deliveries->execute([$_SESSION['user_id']]);
$deliveries = $deliveries->fetchAll();

$okMsg = '';
if (isset($_GET['ok'])) {
    $okMsg = ['avatar'=>'Аватар обновлён', 'canceled'=>'Заказ успешно отменён. Товары возвращены на склад.'][$_GET['ok']] ?? '';
}
if (isset($_GET['tg'])) $okMsg = $_GET['tg'];
$errMsg = (isset($_GET['err']) && $_GET['err']==='cancel') ? 'Невозможно отменить этот заказ.' : '';

// Хелпер: статус заказа для клиента
function clientStatusLabel($statusName) {
    return [
        'new'         => ['label'=>'Принят','icon'=>'📥','color'=>'#3a5f3a','text'=>'#8aff8a','desc'=>'Ваш заказ получен и ожидает обработки'],
        'confirmed'   => ['label'=>'Подтверждён','icon'=>'✅','color'=>'#3a4f7a','text'=>'#8ab4ff','desc'=>'Заказ подтверждён, скоро начнём сборку'],
        'in_progress' => ['label'=>'Собирается','icon'=>'📦','color'=>'#5a4a20','text'=>'#ffd080','desc'=>'Ваш заказ собирается и будет доставлен в срок'],
        'delivered'   => ['label'=>'Доставлен','icon'=>'🏠','color'=>'#2a4a2a','text'=>'#80ff80','desc'=>'Заказ доставлен'],
        'canceled'    => ['label'=>'Отменён','icon'=>'✕','color'=>'#4a2a2a','text'=>'#ff8080','desc'=>'Заказ отменён'],
    ][$statusName] ?? ['label'=>$statusName,'icon'=>'•','color'=>'#2a2a2a','text'=>'#aaa','desc'=>''];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-block{color:#f3f3f3;padding:18px;border-radius:18px;background:#1a1a1a;margin-bottom:18px;}
        .profile-block h2{color:#c48a3a!important;margin-bottom:14px;}
        .order-card{background:#1e1e1e;border:1px solid #2e2e2e;border-radius:14px;padding:16px;margin-bottom:14px;}
        .order-card h3{color:#c48a3a;margin-bottom:8px;font-size:15px;}
        .order-meta{font-size:13px;color:#aaa;margin-bottom:4px;}
        .order-meta span{color:#ddd;}
        .status-pill{display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:20px;font-size:13px;font-weight:600;margin-bottom:8px;}
        .status-desc{font-size:12px;color:#888;margin-bottom:10px;}
        .items-list{font-size:13px;color:#bbb;padding-left:18px;margin-top:6px;}
        .items-list li{margin-bottom:3px;}
        .empty-msg{color:#666;font-style:italic;padding:10px 0;}
        .ok-msg{background:#1a3a1a;border:1px solid #2a7a2a;color:#8aff8a;padding:12px 18px;border-radius:10px;margin-bottom:16px;}
        .delivery-card{background:#1e1e1e;border:1px solid #2e2e2e;border-radius:14px;padding:16px;margin-bottom:14px;}
        .delivery-card h3{color:#c48a3a;margin-bottom:8px;}
        .progress-track{display:flex;gap:0;margin:12px 0;}
        .progress-step{flex:1;text-align:center;font-size:11px;color:#555;position:relative;}
        .progress-step::before{content:'';display:block;width:26px;height:26px;border-radius:50%;background:#2a2a2a;border:2px solid #3a3a3a;margin:0 auto 6px;line-height:22px;font-size:14px;}
        .progress-step.done::before{background:#c48a3a;border-color:#c48a3a;content:attr(data-icon);}
        .progress-step.done{color:#c48a3a;}
        .progress-step.current::before{background:#2a6aad;border-color:#4a8acd;content:attr(data-icon);animation:pulse 1.5s infinite;}
        .progress-step.current{color:#8ab4ff;}
        .progress-line{position:absolute;top:13px;left:calc(50% + 13px);width:calc(100% - 26px);height:2px;background:#2a2a2a;z-index:0;}
        .progress-line.done{background:#c48a3a;}
        @keyframes pulse{0%,100%{opacity:1;}50%{opacity:.5;}}
        .profile-info p{padding:6px 0;border-bottom:1px solid #2a2a2a;font-size:14px;color:#ccc;}
        .profile-info p strong{color:#aaa;display:inline-block;min-width:150px;}
    
/* ===== LIGHT THEME overrides ===== */
body.light-theme { background: linear-gradient(135deg,#f0e8d8,#e8dfc8) !important; color: #1a1208; }
body.light-theme .profile-page { background: linear-gradient(135deg,#f0e8d8,#e8dfc8) !important; }
body.light-theme .profile-card {
    background: #fff9f0 !important;
    border: 1px solid rgba(196,138,58,.18) !important;
    color: #1a1208 !important;
    box-shadow: 0 14px 40px rgba(0,0,0,.08) !important;
}
body.light-theme .profile-card h1,
body.light-theme .profile-card h2,
body.light-theme .profile-card h3,
body.light-theme .profile-card p,
body.light-theme .profile-card span,
body.light-theme .profile-card label,
body.light-theme .profile-card td,
body.light-theme .profile-card th { color: #1a1208 !important; }
body.light-theme .profile-block { background: #f8f0e0 !important; border-color: rgba(196,138,58,.12) !important; }
body.light-theme .profile-block h2 { color: #1a1208 !important; }
body.light-theme .avatar-placeholder { background: #e9dfc9 !important; color: #c48a3a !important; }

/* Order cards inside profile */
body.light-theme [class*="order"] {
    background: #fff9f0 !important;
    border-color: rgba(196,138,58,.18) !important;
    color: #1a1208 !important;
}
body.light-theme [class*="order"] span,
body.light-theme [class*="order"] p { color: #3a2e1a !important; }

/* Inputs in profile edit forms */
body.light-theme .profile-card input,
body.light-theme .profile-card textarea,
body.light-theme .profile-card select {
    background: #fff !important;
    border-color: rgba(196,138,58,.3) !important;
    color: #1a1208 !important;
}
body.light-theme .profile-card input::placeholder,
body.light-theme .profile-card textarea::placeholder { color: #a08060 !important; }
body.light-theme .profile-card input:focus,
body.light-theme .profile-card textarea:focus { border-color: #c48a3a !important; box-shadow: 0 0 0 3px rgba(196,138,58,.12) !important; }

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
<div class="profile-page">
    <div class="profile-card" style="max-width:900px;">
        <div class="profile-head">
            <div class="profile-avatar">
                <?php if(!empty($user['avatar']) && file_exists($user['avatar'])):?>
                    <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Аватар">
                <?php else:?>
                    <img src="img/users/default-avatar.png" alt="Аватар">
                <?php endif;?>
            </div>
            <div>
                <h1>Личный кабинет</h1>
                <p style="color:#aaa;">Здравствуйте, <?= htmlspecialchars($user['imya'].' '.$user['familiya']) ?></p>
            </div>
        </div>

        <?php if($okMsg):?><div class="ok-msg">✓ <?= htmlspecialchars($okMsg) ?></div><?php endif;?>
        <?php if($errMsg):?><div style="background:#3a1a1a;border:1px solid #7a2a2a;color:#ff9090;padding:12px 18px;border-radius:10px;margin-bottom:16px;">✕ <?= htmlspecialchars($errMsg) ?></div><?php endif;?>

        <!-- Быстрые кнопки -->
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;">
            <a href="chat.php" style="background:rgba(196,138,58,.1);border:1px solid rgba(196,138,58,.3);color:#c48a3a;padding:9px 18px;border-radius:10px;text-decoration:none;font-size:14px;font-weight:600;display:inline-flex;align-items:center;gap:6px;">
                💬 Написать менеджеру
            </a>
        </div>

        <!-- Данные профиля -->
        <div class="profile-block">
            <h2>Ваши данные</h2>
            <div class="profile-info">
                <p><strong>Фамилия:</strong> <?= htmlspecialchars($user['familiya']) ?></p>
                <p><strong>Имя:</strong> <?= htmlspecialchars($user['imya']) ?></p>
                <p><strong>Отчество:</strong> <?= htmlspecialchars($user['otchestvo']??'—') ?></p>
                <p><strong>Телефон:</strong> <?= htmlspecialchars($user['telefon']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                <p><strong>Место проживания:</strong> <?= htmlspecialchars($user['mesto_prozhivaniya']??'—') ?></p>
                <?php if($user['data_rozhdeniya']):?>
                <p><strong>Дата рождения:</strong> <?= date('d.m.Y',strtotime($user['data_rozhdeniya'])) ?></p>
                <?php endif;?>
            </div>
        </div>

        <!-- Аватар -->
        <div class="profile-block">
            <h2>Изменить аватар</h2>
            <?php if($errorAvatar):?><p style="color:#ffb4b4;margin-bottom:8px;"><?= htmlspecialchars($errorAvatar) ?></p><?php endif;?>
            <form method="post" enctype="multipart/form-data" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <input type="file" name="avatar" accept="image/*" style="color:#ddd;">
                <button type="submit" name="upload_avatar" class="btn btn-primary">Сохранить</button>
            </form>
        </div>

        <!-- Telegram -->
        <div class="profile-block">
            <h2>🔔 Telegram-уведомления</h2>
            <?php
                require_once 'telegram.php';
                $tgLinked = !empty($user['telegram_chat_id']);
                $tgUser   = htmlspecialchars($user['telegram_username'] ?? '');
            ?>
            <?php if ($tgLinked): ?>
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;background:rgba(50,150,50,.1);border:1px solid rgba(50,200,50,.2);border-radius:10px;padding:10px 14px;">
                    <span style="font-size:22px;">✅</span>
                    <div>
                        <div style="font-size:14px;color:#80ff80;font-weight:700;">Telegram привязан</div>
                        <div style="font-size:13px;color:#888;">@<?= $tgUser ?> — уведомления включены</div>
                    </div>
                </div>
            <?php else: ?>
                <div style="font-size:13px;color:#888;margin-bottom:14px;line-height:1.6;">
                    Привяжите Telegram и получайте уведомления о статусе заказов прямо в мессенджер.<br>
                    <strong style="color:#bbb;">Шаг 1:</strong> Напишите боту
                    <a href="https://t.me/<?= TELEGRAM_BOT_USERNAME ?>" target="_blank" style="color:#c48a3a;">@<?= TELEGRAM_BOT_USERNAME ?></a>
                    команду <code style="background:#2a2a2a;padding:2px 6px;border-radius:4px;">/start</code><br>
                    <strong style="color:#bbb;">Шаг 2:</strong> Введите ваш @username ниже и нажмите «Привязать».
                </div>
            <?php endif; ?>
            <form method="post" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <input type="text" name="telegram_username"
                       value="<?= $tgUser ?>"
                       placeholder="@username"
                       style="padding:9px 14px;border-radius:10px;background:#2a2a2a;border:1px solid #3a3a3a;color:#fff;font-size:14px;width:200px;">
                <button type="submit" name="save_telegram" class="btn btn-primary">
                    <?= $tgLinked ? 'Обновить' : 'Привязать' ?>
                </button>
                <?php if ($tgLinked): ?>
                    <button type="submit" name="save_telegram" value="1"
                            onclick="document.querySelector('[name=telegram_username]').value=''"
                            style="background:transparent;border:1px solid #4a2a2a;color:#ff8080;padding:9px 14px;border-radius:10px;cursor:pointer;font-size:13px;">
                        Отвязать
                    </button>
                <?php endif; ?>
            </form>
        </div>

        <!-- Заказы -->
        <div class="profile-block">
            <h2>Ваши заказы</h2>
            <?php if(empty($orders)):?>
                <p class="empty-msg">У вас пока нет заказов.</p>
            <?php else: foreach($orders as $o):
                $st = clientStatusLabel($o['status_name']);
                $items = $pdo->prepare("SELECT zt.kolichestvo, zt.cena_na_moment, t.nazvanie FROM zakaz_tovary zt JOIN tovary t ON t.id=zt.tovar_id WHERE zt.zakaz_id=?");
                $items->execute([$o['id']]);
                $orderItems = $items->fetchAll();
            ?>
                <div class="order-card">
                    <h3>Заказ №<?= htmlspecialchars($o['nomer_zakaza']) ?></h3>
                    <div style="display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:20px;font-size:13px;font-weight:600;margin-bottom:6px;background:<?= $st['color'] ?>;color:<?= $st['text'] ?>;">
                        <?= $st['icon'] ?> <?= $st['label'] ?>
                    </div>
                    <div class="status-desc"><?= $st['desc'] ?></div>

                    <!-- Прогресс-трекер -->
                    <?php
                    $steps = [
                        ['icon'=>'📥','label'=>'Принят','status'=>'new'],
                        ['icon'=>'✅','label'=>'Подтверждён','status'=>'confirmed'],
                        ['icon'=>'📦','label'=>'Собирается','status'=>'in_progress'],
                        ['icon'=>'🏠','label'=>'Доставлен','status'=>'delivered'],
                    ];
                    $stOrder = ['new'=>0,'confirmed'=>1,'in_progress'=>2,'delivered'=>3,'canceled'=>-1];
                    $curIdx = $stOrder[$o['status_name']] ?? 0;
                    ?>
                    <?php if($o['status_name'] !== 'canceled'):?>
                    <div class="progress-track">
                        <?php foreach($steps as $i=>$step):
                            $cls = '';
                            if($i < $curIdx) $cls='done';
                            elseif($i === $curIdx) $cls='current';
                        ?>
                            <div class="progress-step <?= $cls ?>" data-icon="<?= $step['icon'] ?>">
                                <?php if($i < count($steps)-1):?><div class="progress-line <?= ($i<$curIdx)?'done':'' ?>"></div><?php endif;?>
                                <?= $step['label'] ?>
                            </div>
                        <?php endforeach;?>
                    </div>
                    <?php endif;?>

                    <div class="order-meta">Сумма: <span><?= number_format($o['obshaya_summa'],2,'.',' ') ?> ₽</span></div>
                    <div class="order-meta">Дата заказа: <span><?= date('d.m.Y H:i',strtotime($o['created_at'])) ?></span></div>
                    <?php if($o['tip_dostavki']==='na_datu' && !empty($o['data_dostavki']) && $o['data_dostavki']!=='0000-00-00'):?>
                        <div class="order-meta">Желаемая дата доставки: <span><?= date('d.m.Y',strtotime($o['data_dostavki'])) ?></span></div>
                    <?php endif;?>
                    <?php if($orderItems):?>
                        <div class="order-meta" style="margin-top:8px;">Состав:</div>
                        <ul class="items-list"><?php foreach($orderItems as $it):?><li><?= htmlspecialchars($it['nazvanie']) ?> — <?= $it['kolichestvo'] ?> шт. × <?= number_format($it['cena_na_moment'],2,'.',' ') ?> ₽</li><?php endforeach;?></ul>
                    <?php endif;?>

                    <?php if($o['status_name'] !== 'canceled' && $o['status_name'] !== 'delivered'):?>
                    <div style="margin-top:14px;padding-top:12px;border-top:1px solid #2a2a2a;">
                        <button onclick="document.getElementById('cancel-confirm-<?= $o['id'] ?>').style.display='block';this.style.display='none';"
                                style="background:transparent;border:1px solid #7a2a2a;color:#ff9090;padding:7px 16px;border-radius:8px;cursor:pointer;font-size:13px;">
                            ✕ Отменить заказ
                        </button>
                        <div id="cancel-confirm-<?= $o['id'] ?>" style="display:none;margin-top:10px;background:#2a1a1a;border:1px solid #5a2a2a;border-radius:10px;padding:12px 14px;">
                            <p style="font-size:13px;color:#ffb4b4;margin-bottom:10px;">⚠ Вы уверены? Отменить заказ №<?= htmlspecialchars($o['nomer_zakaza']) ?>?</p>
                            <div style="display:flex;gap:8px;">
                                <form method="post">
                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                    <button type="submit" name="cancel_order"
                                            style="background:#7a2a2a;color:#fff;border:none;padding:7px 16px;border-radius:8px;cursor:pointer;font-size:13px;">
                                        Да, отменить
                                    </button>
                                </form>
                                <button onclick="this.closest('div[id]').style.display='none';this.closest('.order-card').querySelector('button[onclick]').style.display='';"
                                        style="background:#2a2a2a;color:#aaa;border:1px solid #3a3a3a;padding:7px 16px;border-radius:8px;cursor:pointer;font-size:13px;">
                                    Нет, оставить
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif;?>
                </div>
            <?php endforeach; endif;?>
        </div>

        <!-- Доставки -->
        <div class="profile-block">
            <h2>Запланированные доставки</h2>
            <?php if(empty($deliveries)):?>
                <p class="empty-msg">Доставок нет.</p>
            <?php else: foreach($deliveries as $d):
                $st = clientStatusLabel($d['order_status']);
                $dsLabel = ['planned'=>'Запланирована','in_transit'=>'В пути','delivered'=>'Доставлена'][$d['status']]??$d['status'];
            ?>
                <div class="delivery-card">
                    <h3>Доставка по заказу №<?= htmlspecialchars($d['nomer_zakaza']) ?></h3>
                    <div style="display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;margin-bottom:8px;background:<?= $st['color'] ?>;color:<?= $st['text'] ?>;">
                        <?= $st['icon'] ?> <?= $st['label'] ?>
                    </div>
                    <div class="order-meta">Адрес доставки: <span><?= htmlspecialchars($d['address']) ?></span></div>
                    <div class="order-meta">Дата доставки: <span><?= ($d['delivery_date']&&$d['delivery_date']!=='0000-00-00')?date('d.m.Y',strtotime($d['delivery_date'])):'Уточняется' ?></span></div>
                    <div class="order-meta">Статус доставки: <span><?= htmlspecialchars($dsLabel) ?></span></div>
                    <div class="order-meta">Сумма заказа: <span><?= number_format($d['obshaya_summa'],2,'.',' ') ?> ₽</span></div>
                    <?php if(!empty($d['note'])):?>
                        <div class="order-meta">Примечание: <span><?= htmlspecialchars($d['note']) ?></span></div>
                    <?php endif;?>

                    <?php if($d['order_status']==='in_progress'):?>
                        <div style="margin-top:10px;padding:10px 14px;background:#1a2a3a;border-radius:10px;font-size:13px;color:#8ab4ff;">
                            📦 Ваш заказ сейчас собирается и будет доставлен в указанные сроки
                        </div>
                    <?php elseif($d['order_status']==='delivered'):?>
                        <div style="margin-top:10px;padding:10px 14px;background:#1a3a1a;border-radius:10px;font-size:13px;color:#80ff80;">
                            ✓ Заказ доставлен. Спасибо за покупку!
                        </div>
                    <?php elseif($d['order_status']==='confirmed'):?>
                        <div style="margin-top:10px;padding:10px 14px;background:#1a2a1a;border-radius:10px;font-size:13px;color:#c8e6c9;">
                            ✅ Заказ подтверждён, скоро начнём сборку и доставим в срок
                        </div>
                    <?php endif;?>
                </div>
            <?php endforeach; endif;?>
        </div>

        <a href="index.php" class="btn btn-primary">← На главную</a>
        <a href="logout.php" class="btn btn-primary" style="margin-left:10px;background:#3a3a3a;">Выйти</a>
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
