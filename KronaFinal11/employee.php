<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id'])) {
    header('Location: login.php'); exit;
}
$role = (int)$_SESSION['role_id'];
if ($role === 3) { header('Location: admin.php');   exit; }
if ($role === 1) { header('Location: profile.php'); exit; }
if ($role !== 2) { header('Location: index.php');   exit; }

$stmt = $pdo->prepare("SELECT * FROM polzovateli WHERE id = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) { session_unset(); session_destroy(); header('Location: index.php'); exit; }

$errorAvatar = '';

// Загрузка аватара
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_avatar'])) {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            if (!is_dir('uploads/avatars')) mkdir('uploads/avatars', 0777, true);
            $newName = 'uploads/avatars/' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $newName)) {
                $pdo->prepare("UPDATE polzovateli SET avatar = ? WHERE id = ?")->execute([$newName, $_SESSION['user_id']]);
                header('Location: employee.php?ok=avatar'); exit;
            } else { $errorAvatar = 'Не удалось сохранить файл.'; }
        } else { $errorAvatar = 'Разрешены только JPG, JPEG, PNG, GIF, WEBP.'; }
    } else { $errorAvatar = 'Выберите файл.'; }
}

// Принять заказ в работу (new->confirmed)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['take_order'])) {
    $oid = (int)$_POST['order_id'];
    $pdo->prepare("UPDATE zakazy SET status_id = 2 WHERE id = ? AND status_id = 1")->execute([$oid]);
    require_once 'telegram.php'; tgNotifyOrderStatus($pdo, $oid, 'confirmed');
    header('Location: employee.php?ok=taken&tab=in-progress'); exit;
}

// Начать сборку (confirmed->in_progress)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['progress_order'])) {
    $oid = (int)$_POST['order_id'];
    $pdo->prepare("UPDATE zakazy SET status_id = 3 WHERE id = ? AND status_id = 2")->execute([$oid]);
    require_once 'telegram.php'; tgNotifyOrderStatus($pdo, $oid, 'in_progress');
    header('Location: employee.php?ok=progress&tab=in-progress'); exit;
}

// Завершить (in_progress->delivered)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_order'])) {
    $oid = (int)$_POST['order_id'];
    $pdo->prepare("UPDATE zakazy SET status_id = 4 WHERE id = ? AND status_id = 3")->execute([$oid]);
    require_once 'telegram.php'; tgNotifyOrderStatus($pdo, $oid, 'delivered');
    header('Location: employee.php?ok=done&tab=in-progress'); exit;
}

// Изменить количество товара (сотрудник)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $tid = (int)$_POST['tovar_id'];
    $qty = max(0, (int)$_POST['kolichestvo']);
    $pdo->prepare("UPDATE tovary SET kolichestvo=? WHERE id=?")->execute([$qty, $tid]);
    header('Location: employee.php?ok=stock_updated&tab=products'); exit;
}

// Добавить товар (сотрудник)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tovar'])) {
    $name  = trim($_POST['nazvanie'] ?? '');
    $desc  = trim($_POST['opisanie'] ?? '');
    $price = (float)str_replace(',','.',($_POST['cena'] ?? '0'));
    $cat   = trim($_POST['kategoriya'] ?? '');
    $qty   = max(0,(int)($_POST['kolichestvo'] ?? 0));
    $aktiven = isset($_POST['aktiven']) ? 1 : 0;
    $foto = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            if (!is_dir('img/tovar')) mkdir('img/tovar', 0777, true);
            $fname = 'img/tovar/' . time() . '_' . preg_replace('/[^a-z0-9_.]/', '_', strtolower($_FILES['foto']['name']));
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $fname)) $foto = $fname;
        }
    }
    $pdo->prepare("INSERT INTO tovary (nazvanie,opisanie,cena,foto,kategoriya,aktiven,kolichestvo) VALUES (?,?,?,?,?,?,?)")
        ->execute([$name,$desc,$price,$foto,$cat,$aktiven,$qty]);
    header('Location: employee.php?ok=tovar_added&tab=products'); exit;
}

// Редактировать товар (сотрудник)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_tovar'])) {
    $tid   = (int)$_POST['tovar_id'];
    $name  = trim($_POST['nazvanie'] ?? '');
    $desc  = trim($_POST['opisanie'] ?? '');
    $price = (float)str_replace(',','.',($_POST['cena'] ?? '0'));
    $cat   = trim($_POST['kategoriya'] ?? '');
    $qty   = max(0,(int)($_POST['kolichestvo'] ?? 0));
    $aktiven = isset($_POST['aktiven']) ? 1 : 0;
    $existing = $pdo->prepare("SELECT foto FROM tovary WHERE id=?"); $existing->execute([$tid]); $row = $existing->fetch();
    $foto = $row['foto'] ?? '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            if (!is_dir('img/tovar')) mkdir('img/tovar', 0777, true);
            $fname = 'img/tovar/' . time() . '_' . preg_replace('/[^a-z0-9_.]/', '_', strtolower($_FILES['foto']['name']));
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $fname)) $foto = $fname;
        }
    }
    $pdo->prepare("UPDATE tovary SET nazvanie=?,opisanie=?,cena=?,foto=?,kategoriya=?,aktiven=?,kolichestvo=? WHERE id=?")
        ->execute([$name,$desc,$price,$foto,$cat,$aktiven,$qty,$tid]);
    header('Location: employee.php?ok=tovar_updated&tab=products'); exit;
}

// Данные
$newOrders = $pdo->query("SELECT z.*, p.familiya, p.imya, p.telefon, p.email FROM zakazy z JOIN polzovateli p ON p.id=z.user_id WHERE z.status_id=1 ORDER BY z.created_at DESC")->fetchAll();
$inProgress = $pdo->query("SELECT z.*, p.familiya, p.imya, p.telefon, p.email FROM zakazy z JOIN polzovateli p ON p.id=z.user_id WHERE z.status_id IN(2,3) ORDER BY z.created_at DESC")->fetchAll();
$deliveries = $pdo->query("SELECT d.*, z.nomer_zakaza, z.obshaya_summa, p.familiya, p.imya, p.telefon FROM dostavka d JOIN zakazy z ON z.id=d.zakaz_id JOIN polzovateli p ON p.id=z.user_id ORDER BY d.delivery_date ASC")->fetchAll();
$tovary_all = $pdo->query("SELECT * FROM tovary ORDER BY id ASC")->fetchAll();

$okMsgs = ['avatar'=>'Аватар обновлён','taken'=>'Заказ принят в работу','done'=>'Заказ отмечен как доставленный','progress'=>'Заказ переведён в сборку','stock_updated'=>'Количество товара обновлено','tovar_added'=>'Товар успешно добавлен','tovar_updated'=>'Товар успешно обновлён'];
$okMsg = $okMsgs[$_GET['ok'] ?? ''] ?? '';
$activeTab = $_GET['tab'] ?? 'new-orders';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кабинет сотрудника</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .emp-tabs{display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;}
        .emp-tab{padding:10px 22px;border-radius:10px;background:#222;color:#aaa;cursor:pointer;border:none;font-size:15px;transition:.2s;}
        .emp-tab.active{background:#c48a3a;color:#fff;}
        .tab-content{display:none;}
        .tab-content.active{display:block;}
        .order-card{background:#1e1e1e;border:1px solid #2e2e2e;border-radius:14px;padding:16px;margin-bottom:14px;}
        .order-card h3{color:#c48a3a;margin-bottom:8px;font-size:15px;}
        .order-meta{font-size:13px;color:#aaa;margin-bottom:4px;}
        .order-meta span{color:#ddd;}
        .order-actions{margin-top:12px;display:flex;gap:10px;flex-wrap:wrap;}
        .btn-take{background:#c48a3a;color:#fff;border:none;padding:8px 18px;border-radius:8px;cursor:pointer;font-size:14px;}
        .btn-take:hover{background:#a97030;}
        .btn-progress{background:#2a6aad;color:#fff;border:none;padding:8px 18px;border-radius:8px;cursor:pointer;font-size:14px;}
        .btn-done{background:#2a7a3a;color:#fff;border:none;padding:8px 18px;border-radius:8px;cursor:pointer;font-size:14px;}
        .badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;margin-left:8px;}
        .badge-new{background:#3a5f3a;color:#8aff8a;}
        .badge-confirmed{background:#3a4f7a;color:#8ab4ff;}
        .badge-progress{background:#5a4a20;color:#ffd080;}
        .empty-msg{color:#666;font-style:italic;padding:10px 0;}
        .ok-msg{background:#1a3a1a;border:1px solid #2a7a2a;color:#8aff8a;padding:12px 18px;border-radius:10px;margin-bottom:16px;}
        .items-list{margin-top:8px;font-size:13px;color:#bbb;padding-left:18px;}
        .items-list li{margin-bottom:3px;}
        .profile-block{color:#f3f3f3;padding:18px;border-radius:18px;background:#1a1a1a;margin-bottom:18px;}
        .profile-block h2{color:#c48a3a!important;margin-bottom:14px;}
        table.del-table{width:100%;border-collapse:collapse;font-size:13px;}
        table.del-table th{background:#2a2a2a;color:#c48a3a;padding:8px 10px;text-align:left;}
        table.del-table td{padding:8px 10px;border-bottom:1px solid #2a2a2a;color:#ddd;}
        table.del-table tr:hover td{background:#1a1a1a;}
        .cnt-badge{background:#c48a3a;color:#fff;border-radius:20px;padding:1px 8px;font-size:12px;margin-left:6px;}
        .cnt-badge-blue{background:#2a6aad;}
    
/* ===== LIGHT THEME ===== */
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
body.light-theme .profile-card h3 { color: #1a1208 !important; }
body.light-theme .profile-block { background: #f5ebe0 !important; color: #1a1208 !important; }
body.light-theme .profile-block h2 { color: #c48a3a !important; }

/* Tabs */
body.light-theme .emp-tab {
    background: rgba(196,138,58,.08) !important;
    color: #5a4a2a !important;
    border: 1px solid rgba(196,138,58,.18) !important;
}
body.light-theme .emp-tab:hover { background: rgba(196,138,58,.16) !important; color: #c48a3a !important; }
body.light-theme .emp-tab.active { background: #c48a3a !important; color: #fff !important; border-color: transparent !important; }

/* Order cards */
body.light-theme .order-card {
    background: #fff9f0 !important;
    border-color: rgba(196,138,58,.2) !important;
}
body.light-theme .order-card h3 { color: #c48a3a !important; }
body.light-theme .order-meta { color: #7a6040 !important; }
body.light-theme .order-meta span { color: #1a1208 !important; }
body.light-theme .items-list { color: #5a4a2a !important; }
body.light-theme .empty-msg { color: #9a8060 !important; }
body.light-theme .ok-msg { background: #e8f5e9 !important; color: #1f5a2b !important; border-color: rgba(80,180,100,.2) !important; }

/* Table */
body.light-theme table.del-table th { background: #f0e4d0 !important; color: #c48a3a !important; }
body.light-theme table.del-table td { color: #2a1e0a !important; border-color: rgba(196,138,58,.15) !important; }
body.light-theme table.del-table tr:hover td { background: rgba(196,138,58,.05) !important; }
body.light-theme .cnt-badge { background: #c48a3a !important; }
body.light-theme .cnt-badge-blue { background: #2a6aad !important; }

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
    <div class="profile-card" style="max-width:960px;">
        <div class="profile-head">
            <div class="profile-avatar">
                <?php if (!empty($user['avatar']) && file_exists($user['avatar'])): ?>
                    <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Аватар">
                <?php else: ?>
                    <img src="img/users/default-avatar.png" alt="Аватар">
                <?php endif; ?>
            </div>
            <div>
                <h1>Кабинет сотрудника</h1>
                <p style="color:#aaa;">Здравствуйте, <?= htmlspecialchars($user['imya'] . ' ' . $user['familiya']) ?></p>
            </div>
        </div>

        <?php if ($okMsg): ?>
            <div class="ok-msg">✓ <?= htmlspecialchars($okMsg) ?></div>
        <?php endif; ?>

        <!-- Аватар -->
        <div class="profile-block">
            <h2>Изменить аватар</h2>
            <?php if ($errorAvatar): ?><p style="color:#ffb4b4;margin-bottom:8px;"><?= htmlspecialchars($errorAvatar) ?></p><?php endif; ?>
            <form method="post" enctype="multipart/form-data" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <input type="file" name="avatar" accept="image/*" style="color:#ddd;">
                <button type="submit" name="upload_avatar" class="btn btn-primary">Сохранить</button>
            </form>
        </div>

        <!-- Вкладки -->
        <div class="emp-tabs">
            <button class="emp-tab <?= $activeTab==='new-orders'?'active':'' ?>" onclick="switchTab('new-orders',this)">
                Новые заказы<?php if(count($newOrders)):?><span class="cnt-badge"><?= count($newOrders) ?></span><?php endif;?>
            </button>
            <button class="emp-tab <?= $activeTab==='in-progress'?'active':'' ?>" onclick="switchTab('in-progress',this)">
                В работе<?php if(count($inProgress)):?><span class="cnt-badge cnt-badge-blue"><?= count($inProgress) ?></span><?php endif;?>
            </button>
            <button class="emp-tab <?= $activeTab==='deliveries'?'active':'' ?>" onclick="switchTab('deliveries',this)">Доставки</button>
            <button class="emp-tab <?= $activeTab==='products'?'active':'' ?>" onclick="switchTab('products',this)">🏷️ Товары</button>
            <?php
            $chatUnread = (int)$pdo->query("SELECT COUNT(*) FROM chat_soobscheniya m JOIN polzovateli p ON p.id=m.ot_user_id WHERE p.role_id=1 AND m.procitano=0")->fetchColumn();
            ?>
            <a href="chat.php" class="emp-tab" style="text-decoration:none;">
                💬 Чат<?php if($chatUnread>0):?> <span class="cnt-badge"><?= $chatUnread ?></span><?php endif;?>
            </a>
        </div>

        <!-- Новые заказы -->
        <div id="new-orders" class="tab-content <?= $activeTab==='new-orders'?'active':'' ?> profile-block">
            <h2>Новые заказы</h2>
            <?php if (empty($newOrders)): ?>
                <p class="empty-msg">Новых заказов нет.</p>
            <?php else: ?>
                <?php foreach ($newOrders as $o):
                    $items = $pdo->prepare("SELECT zt.kolichestvo, zt.cena_na_moment, t.nazvanie FROM zakaz_tovary zt JOIN tovary t ON t.id=zt.tovar_id WHERE zt.zakaz_id=?");
                    $items->execute([$o['id']]);
                    $orderItems = $items->fetchAll();
                ?>
                    <div class="order-card">
                        <h3>Заказ №<?= htmlspecialchars($o['nomer_zakaza']) ?> <span class="badge badge-new">Новый</span></h3>
                        <div class="order-meta">Клиент: <span><?= htmlspecialchars($o['familiya'].' '.$o['imya']) ?></span></div>
                        <div class="order-meta">Телефон: <span><?= htmlspecialchars($o['telefon']) ?></span></div>
                        <div class="order-meta">Email: <span><?= htmlspecialchars($o['email']) ?></span></div>
                        <div class="order-meta">Сумма: <span><?= number_format($o['obshaya_summa'],2,'.',' ') ?> ₽</span></div>
                        <div class="order-meta">Дата заказа: <span><?= date('d.m.Y H:i',strtotime($o['created_at'])) ?></span></div>
                        <?php if($o['tip_dostavki']==='na_datu' && !empty($o['data_dostavki']) && $o['data_dostavki']!=='0000-00-00'):?>
                            <div class="order-meta">Желаемая дата доставки: <span><?= date('d.m.Y',strtotime($o['data_dostavki'])) ?></span></div>
                        <?php endif;?>
                        <?php if($orderItems):?>
                            <div class="order-meta" style="margin-top:8px;">Состав:</div>
                            <ul class="items-list"><?php foreach($orderItems as $it):?><li><?= htmlspecialchars($it['nazvanie']) ?> — <?= $it['kolichestvo'] ?> шт. × <?= number_format($it['cena_na_moment'],2,'.',' ') ?> ₽</li><?php endforeach;?></ul>
                        <?php endif;?>
                        <div class="order-actions">
                            <form method="post">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <button type="submit" name="take_order" class="btn-take">✓ Принять в работу</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach;?>
            <?php endif;?>
        </div>

        <!-- В работе -->
        <div id="in-progress" class="tab-content <?= $activeTab==='in-progress'?'active':'' ?> profile-block">
            <h2>Заказы в работе</h2>
            <?php if (empty($inProgress)): ?>
                <p class="empty-msg">Нет заказов в работе.</p>
            <?php else: ?>
                <?php foreach ($inProgress as $o):
                    $items = $pdo->prepare("SELECT zt.kolichestvo, zt.cena_na_moment, t.nazvanie FROM zakaz_tovary zt JOIN tovary t ON t.id=zt.tovar_id WHERE zt.zakaz_id=?");
                    $items->execute([$o['id']]);
                    $orderItems = $items->fetchAll();
                    $badgeClass = $o['status_id']==2 ? 'badge-confirmed' : 'badge-progress';
                    $badgeText  = $o['status_id']==2 ? 'Подтверждён' : 'В сборке';
                ?>
                    <div class="order-card">
                        <h3>Заказ №<?= htmlspecialchars($o['nomer_zakaza']) ?> <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span></h3>
                        <div class="order-meta">Клиент: <span><?= htmlspecialchars($o['familiya'].' '.$o['imya']) ?></span></div>
                        <div class="order-meta">Телефон: <span><?= htmlspecialchars($o['telefon']) ?></span></div>
                        <div class="order-meta">Сумма: <span><?= number_format($o['obshaya_summa'],2,'.',' ') ?> ₽</span></div>
                        <div class="order-meta">Дата заказа: <span><?= date('d.m.Y H:i',strtotime($o['created_at'])) ?></span></div>
                        <?php if($o['tip_dostavki']==='na_datu' && !empty($o['data_dostavki']) && $o['data_dostavki']!=='0000-00-00'):?>
                            <div class="order-meta">Дата доставки: <span><?= date('d.m.Y',strtotime($o['data_dostavki'])) ?></span></div>
                        <?php endif;?>
                        <?php if($orderItems):?>
                            <ul class="items-list" style="margin-top:8px;"><?php foreach($orderItems as $it):?><li><?= htmlspecialchars($it['nazvanie']) ?> — <?= $it['kolichestvo'] ?> шт. × <?= number_format($it['cena_na_moment'],2,'.',' ') ?> ₽</li><?php endforeach;?></ul>
                        <?php endif;?>
                        <div class="order-actions">
                            <?php if($o['status_id']==2):?>
                                <form method="post"><input type="hidden" name="order_id" value="<?= $o['id'] ?>"><button type="submit" name="progress_order" class="btn-progress">🔧 Начать сборку</button></form>
                            <?php elseif($o['status_id']==3):?>
                                <form method="post"><input type="hidden" name="order_id" value="<?= $o['id'] ?>"><button type="submit" name="complete_order" class="btn-done">✓ Доставлен</button></form>
                            <?php endif;?>
                        </div>
                    </div>
                <?php endforeach;?>
            <?php endif;?>
        </div>

        <!-- Доставки -->
        <div id="deliveries" class="tab-content <?= $activeTab==='deliveries'?'active':'' ?> profile-block">
            <h2>Доставки</h2>
            <?php if (empty($deliveries)): ?>
                <p class="empty-msg">Доставок нет.</p>
            <?php else: ?>
                <div style="overflow-x:auto;">
                <table class="del-table">
                    <thead><tr><th>Заказ</th><th>Клиент</th><th>Телефон</th><th>Адрес</th><th>Дата доставки</th><th>Статус</th><th>Сумма</th></tr></thead>
                    <tbody>
                    <?php foreach($deliveries as $d):
                        $dsLabel = ['planned'=>'Запланирована','in_transit'=>'В пути','delivered'=>'Доставлена'][$d['status']] ?? $d['status'];
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($d['nomer_zakaza']) ?></td>
                            <td><?= htmlspecialchars($d['familiya'].' '.$d['imya']) ?></td>
                            <td><?= htmlspecialchars($d['telefon']) ?></td>
                            <td><?= htmlspecialchars($d['address']) ?></td>
                            <td><?= ($d['delivery_date'] && $d['delivery_date']!=='0000-00-00') ? date('d.m.Y',strtotime($d['delivery_date'])) : '—' ?></td>
                            <td><?= htmlspecialchars($dsLabel) ?></td>
                            <td><?= number_format($d['obshaya_summa'],2,'.',' ') ?> ₽</td>
                        </tr>
                    <?php endforeach;?>
                    </tbody>
                </table>
                </div>
            <?php endif;?>
        </div>

        <!-- ТОВАРЫ -->
        <div id="products" class="tab-content <?= $activeTab==='products'?'active':'' ?> profile-block">
            <h2>Управление товарами</h2>

            <!-- Кнопка добавить -->
            <button class="btn-take" onclick="toggleAddForm()" style="background:#2a7a3a;margin-bottom:18px;padding:9px 20px;font-size:14px;">➕ Добавить товар</button>

            <!-- Форма добавления -->
            <div id="add-tovar-form" style="display:none;background:#141414;border:1px solid #2e2e2e;border-radius:14px;padding:20px;margin-bottom:22px;">
                <h3 style="color:#c48a3a;margin-bottom:14px;">Новый товар</h3>
                <form method="post" enctype="multipart/form-data">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div>
                            <label style="font-size:13px;color:#aaa;display:block;margin-bottom:4px;">Название *</label>
                            <input type="text" name="nazvanie" required style="width:100%;padding:8px 10px;border-radius:8px;background:#2a2a2a;border:1px solid #3a3a3a;color:#fff;font-size:14px;box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="font-size:13px;color:#aaa;display:block;margin-bottom:4px;">Категория</label>
                            <input type="text" name="kategoriya" style="width:100%;padding:8px 10px;border-radius:8px;background:#2a2a2a;border:1px solid #3a3a3a;color:#fff;font-size:14px;box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="font-size:13px;color:#aaa;display:block;margin-bottom:4px;">Цена (₽) *</label>
                            <input type="number" name="cena" step="0.01" min="0" required style="width:100%;padding:8px 10px;border-radius:8px;background:#2a2a2a;border:1px solid #3a3a3a;color:#fff;font-size:14px;box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="font-size:13px;color:#aaa;display:block;margin-bottom:4px;">Количество на складе</label>
                            <input type="number" name="kolichestvo" min="0" value="0" style="width:100%;padding:8px 10px;border-radius:8px;background:#2a2a2a;border:1px solid #3a3a3a;color:#fff;font-size:14px;box-sizing:border-box;">
                        </div>
                        <div style="grid-column:1/-1;">
                            <label style="font-size:13px;color:#aaa;display:block;margin-bottom:4px;">Описание</label>
                            <textarea name="opisanie" rows="2" style="width:100%;padding:8px 10px;border-radius:8px;background:#2a2a2a;border:1px solid #3a3a3a;color:#fff;font-size:14px;box-sizing:border-box;resize:vertical;"></textarea>
                        </div>
                        <div>
                            <label style="font-size:13px;color:#aaa;display:block;margin-bottom:4px;">Фото товара</label>
                            <input type="file" name="foto" accept="image/*" onchange="previewNewImg(this)" style="color:#ccc;font-size:13px;">
                            <img id="new-preview" src="" style="display:none;margin-top:8px;width:100px;height:80px;object-fit:cover;border-radius:8px;border:1px solid #3a3a3a;">
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <label style="font-size:13px;color:#aaa;">Активен:</label>
                            <input type="checkbox" name="aktiven" checked style="width:18px;height:18px;cursor:pointer;">
                        </div>
                    </div>
                    <div style="margin-top:14px;display:flex;gap:10px;">
                        <button type="submit" name="add_tovar" class="btn-take" style="background:#2a7a3a;">✓ Добавить</button>
                        <button type="button" onclick="toggleAddForm()" style="background:#3a3a3a;color:#ccc;border:none;padding:9px 18px;border-radius:9px;cursor:pointer;font-size:14px;">Отмена</button>
                    </div>
                </form>
            </div>

            <!-- Карточки товаров -->
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:14px;">
            <?php foreach($tovary_all as $t):
                $qty = (int)$t['kolichestvo'];
                $clr = $qty === 0 ? '#e05252' : ($qty <= 10 ? '#e0a030' : '#52c052');
            ?>
            <div style="background:#141414;border:1px solid #2a2a2a;border-radius:14px;overflow:hidden;">
                <div style="position:relative;height:150px;background:#111;overflow:hidden;">
                    <?php if (!empty($t['foto']) && file_exists($t['foto'])): ?>
                        <img src="<?= htmlspecialchars($t['foto']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                        <div style="display:flex;align-items:center;justify-content:center;height:100%;color:#444;font-size:36px;">📦</div>
                    <?php endif; ?>
                    <span style="position:absolute;top:7px;right:7px;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;background:<?= $t['aktiven'] ? '#1a3a1a' : '#2a2a2a' ?>;color:<?= $t['aktiven'] ? '#52c052' : '#888' ?>;">
                        <?= $t['aktiven'] ? 'Активен' : 'Скрыт' ?>
                    </span>
                </div>
                <div style="padding:12px;">
                    <div style="font-size:14px;font-weight:700;color:#f0f0f0;margin-bottom:2px;"><?= htmlspecialchars($t['nazvanie']) ?></div>
                    <div style="font-size:12px;color:#777;margin-bottom:6px;"><?= htmlspecialchars($t['opisanie'] ?? '') ?></div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:10px;">
                        <span style="color:#c48a3a;font-size:15px;font-weight:700;"><?= number_format($t['cena'],2,'.',' ') ?> ₽</span>
                        <span style="font-size:13px;font-weight:700;color:<?= $clr ?>"><?= $qty ?> шт.</span>
                    </div>
                    <button onclick="openEditModal(<?= htmlspecialchars(json_encode($t)) ?>)" class="btn-take" style="width:100%;padding:8px;font-size:13px;text-align:center;">✏️ Редактировать</button>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>

        <br>
        <a href="index.php" class="btn btn-primary">← На главную</a>
    </div>
</div>
<script>
function switchTab(id, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.emp-tab').forEach(t => t.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    btn.classList.add('active');
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
<!-- Модальное окно редактирования товара (сотрудник) -->
<div id="edit-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.78);z-index:9999;overflow-y:auto;padding:20px;">
    <div style="max-width:540px;margin:40px auto;background:#1a1a1a;border:1px solid #3a3a3a;border-radius:16px;padding:24px;position:relative;">
        <button onclick="closeEditModal()" style="position:absolute;top:12px;right:16px;background:none;border:none;color:#aaa;font-size:22px;cursor:pointer;">✕</button>
        <h3 style="color:#c48a3a;margin-bottom:18px;">Редактировать товар</h3>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="tovar_id" id="edit-id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label style="font-size:13px;color:#aaa;display:block;margin-bottom:4px;">Название *</label>
                    <input type="text" name="nazvanie" id="edit-name" required style="width:100%;padding:8px 10px;border-radius:8px;background:#2a2a2a;border:1px solid #3a3a3a;color:#fff;font-size:14px;box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:13px;color:#aaa;display:block;margin-bottom:4px;">Категория</label>
                    <input type="text" name="kategoriya" id="edit-kat" style="width:100%;padding:8px 10px;border-radius:8px;background:#2a2a2a;border:1px solid #3a3a3a;color:#fff;font-size:14px;box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:13px;color:#aaa;display:block;margin-bottom:4px;">Цена (₽) *</label>
                    <input type="number" name="cena" id="edit-cena" step="0.01" min="0" required style="width:100%;padding:8px 10px;border-radius:8px;background:#2a2a2a;border:1px solid #3a3a3a;color:#fff;font-size:14px;box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:13px;color:#aaa;display:block;margin-bottom:4px;">Количество</label>
                    <input type="number" name="kolichestvo" id="edit-qty" min="0" style="width:100%;padding:8px 10px;border-radius:8px;background:#2a2a2a;border:1px solid #3a3a3a;color:#fff;font-size:14px;box-sizing:border-box;">
                </div>
                <div style="grid-column:1/-1;">
                    <label style="font-size:13px;color:#aaa;display:block;margin-bottom:4px;">Описание</label>
                    <textarea name="opisanie" id="edit-desc" rows="2" style="width:100%;padding:8px 10px;border-radius:8px;background:#2a2a2a;border:1px solid #3a3a3a;color:#fff;font-size:14px;box-sizing:border-box;resize:vertical;"></textarea>
                </div>
                <div style="grid-column:1/-1;">
                    <label style="font-size:13px;color:#aaa;display:block;margin-bottom:4px;">Фото (оставьте пустым, чтобы не менять)</label>
                    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                        <img id="edit-current-img" src="" alt="" style="width:90px;height:70px;object-fit:cover;border-radius:8px;border:1px solid #3a3a3a;display:none;">
                        <div>
                            <input type="file" name="foto" accept="image/*" onchange="previewEditImg(this)" style="color:#ccc;font-size:13px;">
                            <img id="edit-preview" src="" style="display:none;margin-top:8px;width:90px;height:70px;object-fit:cover;border-radius:8px;border:1px solid #3a3a3a;">
                        </div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <label style="font-size:13px;color:#aaa;">Активен:</label>
                    <input type="checkbox" name="aktiven" id="edit-aktiven" style="width:18px;height:18px;cursor:pointer;">
                </div>
            </div>
            <div style="margin-top:18px;display:flex;gap:10px;">
                <button type="submit" name="edit_tovar" class="btn-take" style="padding:10px 26px;font-size:14px;font-weight:700;">💾 Сохранить</button>
                <button type="button" onclick="closeEditModal()" style="background:#3a3a3a;color:#ccc;border:none;padding:10px 18px;border-radius:9px;cursor:pointer;font-size:14px;">Отмена</button>
            </div>
        </form>
    </div>
</div>
<script>
function toggleAddForm() {
    var f = document.getElementById('add-tovar-form');
    f.style.display = f.style.display === 'none' ? 'block' : 'none';
}
function previewNewImg(input) {
    var img = document.getElementById('new-preview');
    if (input.files && input.files[0]) {
        var r = new FileReader(); r.onload = function(e){ img.src=e.target.result; img.style.display='block'; }; r.readAsDataURL(input.files[0]);
    }
}
function previewEditImg(input) {
    var img = document.getElementById('edit-preview');
    if (input.files && input.files[0]) {
        var r = new FileReader(); r.onload = function(e){ img.src=e.target.result; img.style.display='block'; }; r.readAsDataURL(input.files[0]);
    }
}
function openEditModal(t) {
    document.getElementById('edit-id').value   = t.id;
    document.getElementById('edit-name').value = t.nazvanie;
    document.getElementById('edit-kat').value  = t.kategoriya || '';
    document.getElementById('edit-cena').value = t.cena;
    document.getElementById('edit-qty').value  = t.kolichestvo;
    document.getElementById('edit-desc').value = t.opisanie || '';
    document.getElementById('edit-aktiven').checked = t.aktiven == 1;
    var ci = document.getElementById('edit-current-img');
    if (t.foto) { ci.src = t.foto; ci.style.display = 'block'; } else { ci.style.display = 'none'; }
    document.getElementById('edit-preview').style.display = 'none';
    document.getElementById('edit-modal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}
function closeEditModal() {
    document.getElementById('edit-modal').style.display = 'none';
    document.body.style.overflow = '';
}
document.getElementById('edit-modal').addEventListener('click', function(e){ if(e.target===this) closeEditModal(); });
</script>
</body>
</html>
