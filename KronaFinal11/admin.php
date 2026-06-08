<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id'])) {
    header('Location: login.php'); exit;
}
$role = (int)$_SESSION['role_id'];
if ($role === 2) { header('Location: employee.php'); exit; }
if ($role === 1) { header('Location: profile.php');  exit; }
if ($role !== 3) { header('Location: index.php');    exit; }

$stmt = $pdo->prepare("SELECT * FROM polzovateli WHERE id = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) { session_unset(); session_destroy(); header('Location: index.php'); exit; }

$okMsg = ''; $errMsg = '';

// === Аватар ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_avatar'])) {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            if (!is_dir('uploads/avatars')) mkdir('uploads/avatars', 0777, true);
            $newName = 'uploads/avatars/' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $newName)) {
                $pdo->prepare("UPDATE polzovateli SET avatar=? WHERE id=?")->execute([$newName, $_SESSION['user_id']]);
                header('Location: admin.php?ok=avatar&tab=settings'); exit;
            }
        }
    }
    $errMsg = 'Ошибка загрузки аватара.';
}

// === Удалить пользователя ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $uid = (int)$_POST['uid'];
    if ($uid !== (int)$_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM polzovateli WHERE id=?")->execute([$uid]);
        header('Location: admin.php?ok=user_deleted&tab=users'); exit;
    }
}

// === Изменить роль пользователя ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $uid = (int)$_POST['uid'];
    $rid = (int)$_POST['role_id'];
    if ($uid !== (int)$_SESSION['user_id']) {
        $pdo->prepare("UPDATE polzovateli SET role_id=? WHERE id=?")->execute([$rid, $uid]);
        header('Location: admin.php?ok=role_changed&tab=users'); exit;
    }
}

// === Изменить статус заказа ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_order_status'])) {
    $zid = (int)$_POST['zid'];
    $sid = (int)$_POST['status_id'];
    $pdo->prepare("UPDATE zakazy SET status_id=? WHERE id=?")->execute([$sid, $zid]);
    // Telegram-уведомление клиенту
    require_once 'telegram.php';
    $statusMap = [1=>'new',2=>'confirmed',3=>'in_progress',4=>'delivered',5=>'canceled'];
    if (isset($statusMap[$sid])) tgNotifyOrderStatus($pdo, $zid, $statusMap[$sid]);
    header('Location: admin.php?ok=status_changed&tab=orders'); exit;
}

// === Удалить заказ ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    $pdo->prepare("DELETE FROM zakazy WHERE id=?")->execute([(int)$_POST['zid']]);
    header('Location: admin.php?ok=order_deleted&tab=orders'); exit;
}

// === Обновить доставку ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_delivery'])) {
    $did = (int)$_POST['did'];
    $dstatus = $_POST['dstatus'];
    $dnote = $_POST['dnote'];
    $pdo->prepare("UPDATE dostavka SET status=?, note=? WHERE id=?")->execute([$dstatus, $dnote, $did]);
    header('Location: admin.php?ok=delivery_updated&tab=deliveries'); exit;
}

// === Изменить количество товара (admin) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $tid = (int)$_POST['tovar_id'];
    $qty = max(0, (int)$_POST['kolichestvo']);
    $pdo->prepare("UPDATE tovary SET kolichestvo=? WHERE id=?")->execute([$qty, $tid]);
    header('Location: admin.php?ok=stock_updated&tab=products'); exit;
}

// === Добавить товар ===
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
    header('Location: admin.php?ok=tovar_added&tab=products'); exit;
}

// === Редактировать товар ===
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
    header('Location: admin.php?ok=tovar_updated&tab=products'); exit;
}

// === Удалить товар ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_tovar'])) {
    $tid = (int)$_POST['tovar_id'];
    $pdo->prepare("DELETE FROM tovary WHERE id=?")->execute([$tid]);
    header('Location: admin.php?ok=tovar_deleted&tab=products'); exit;
}

// === Данные ===
$users = $pdo->query("SELECT p.*, r.nazvanie as role_name FROM polzovateli p JOIN roli r ON r.id=p.role_id ORDER BY p.created_at DESC")->fetchAll();
$roles = $pdo->query("SELECT * FROM roli")->fetchAll();
$orders = $pdo->query("SELECT z.*, p.familiya, p.imya, p.telefon, sz.nazvanie as status_name FROM zakazy z JOIN polzovateli p ON p.id=z.user_id JOIN statusy_zakazov sz ON sz.id=z.status_id ORDER BY z.created_at DESC")->fetchAll();
$statuses = $pdo->query("SELECT * FROM statusy_zakazov")->fetchAll();
$deliveries = $pdo->query("SELECT d.*, z.nomer_zakaza, z.obshaya_summa, p.familiya, p.imya, p.telefon FROM dostavka d JOIN zakazy z ON z.id=d.zakaz_id JOIN polzovateli p ON p.id=z.user_id ORDER BY d.delivery_date ASC")->fetchAll();
$zayavki = $pdo->query("SELECT * FROM zayavki ORDER BY created_at DESC")->fetchAll();
$tovary_all = $pdo->query("SELECT * FROM tovary ORDER BY id ASC")->fetchAll();

// Подсчёты для отчётов
$totalRevenue = array_sum(array_column($orders, 'obshaya_summa'));
$totalOrders = count($orders);
$totalUsers = count($users);
$totalDeliveries = count($deliveries);

$okMsgs = [
    'avatar'=>'Аватар обновлён','user_deleted'=>'Пользователь удалён','role_changed'=>'Роль изменена',
    'status_changed'=>'Статус заказа изменён','order_deleted'=>'Заказ удалён','delivery_updated'=>'Доставка обновлена',
    'stock_updated'=>'Количество товара обновлено',
    'tovar_added'=>'Товар успешно добавлен',
    'tovar_updated'=>'Товар успешно обновлён',
    'tovar_deleted'=>'Товар удалён'
];
$okMsg = $okMsgs[$_GET['ok'] ?? ''] ?? '';
$activeTab = $_GET['tab'] ?? 'users';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кабинет администратора</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .adm-tabs{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;}
        .adm-tab{padding:10px 20px;border-radius:10px;background:#222;color:#aaa;cursor:pointer;border:none;font-size:14px;transition:.2s;}
        .adm-tab.active{background:#c48a3a;color:#fff;}
        .tab-content{display:none;}
        .tab-content.active{display:block;}
        .profile-block{color:#f3f3f3;padding:18px;border-radius:18px;background:#1a1a1a;margin-bottom:18px;}
        .profile-block h2{color:#c48a3a!important;margin-bottom:14px;}
        table.adm-table{width:100%;border-collapse:collapse;font-size:13px;}
        table.adm-table th{background:#2a2a2a;color:#c48a3a;padding:8px 10px;text-align:left;white-space:nowrap;}
        table.adm-table td{padding:8px 10px;border-bottom:1px solid #2a2a2a;color:#ddd;vertical-align:middle;}
        table.adm-table tr:hover td{background:#1a1a1a;}
        .btn-del{background:#7a2a2a;color:#fff;border:none;padding:5px 12px;border-radius:7px;cursor:pointer;font-size:13px;}
        .btn-del:hover{background:#aa3a3a;}
        .btn-sm{background:#c48a3a;color:#fff;border:none;padding:5px 12px;border-radius:7px;cursor:pointer;font-size:13px;}
        .btn-sm:hover{background:#a97030;}
        select.inline-sel{background:#2a2a2a;color:#ddd;border:1px solid #3a3a3a;border-radius:7px;padding:4px 8px;font-size:13px;}
        .ok-msg{background:#1a3a1a;border:1px solid #2a7a2a;color:#8aff8a;padding:12px 18px;border-radius:10px;margin-bottom:16px;}
        .err-msg{background:#3a1a1a;border:1px solid #7a2a2a;color:#ff8a8a;padding:12px 18px;border-radius:10px;margin-bottom:16px;}
        .stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:20px;}
        .stat-card{background:#222;border-radius:14px;padding:18px;text-align:center;}
        .stat-card .num{font-size:32px;font-weight:700;color:#c48a3a;}
        .stat-card .lbl{font-size:13px;color:#888;margin-top:4px;}
        .report-block{background:#1e1e1e;border:1px solid #2a2a2a;border-radius:12px;padding:16px;margin-bottom:16px;}
        .report-block h3{color:#c48a3a;margin-bottom:12px;}
        .badge{display:inline-block;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600;}
        .badge-new{background:#3a5f3a;color:#8aff8a;}
        .badge-confirmed{background:#3a4f7a;color:#8ab4ff;}
        .badge-progress{background:#5a4a20;color:#ffd080;}
        .badge-delivered{background:#2a4a2a;color:#80ff80;}
        .badge-canceled{background:#4a2a2a;color:#ff8080;}
        .badge-admin{background:#5a3a7a;color:#d4a0ff;}
        .badge-employee{background:#2a4a6a;color:#80c0ff;}
        .badge-client{background:#2a3a2a;color:#80ff80;}
        textarea.inline-ta{background:#2a2a2a;color:#ddd;border:1px solid #3a3a3a;border-radius:7px;padding:6px;font-size:12px;width:100%;margin-top:4px;resize:vertical;}
        .del-row{background:#1a1a1a;border-radius:10px;padding:12px;margin-bottom:10px;font-size:13px;color:#ddd;}
        .del-row strong{color:#c48a3a;}
        @media print{.adm-tabs,.btn-del,.btn-sm,.no-print{display:none!important;} body{background:#fff;color:#000;} .profile-block{background:#f5f5f5;border:1px solid #ddd;} table.adm-table th{background:#eee;color:#333;} table.adm-table td{color:#333;}}
    
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
body.light-theme .profile-card h3,
body.light-theme .profile-card p,
body.light-theme .profile-card td,
body.light-theme .profile-card label { color: #1a1208 !important; }
body.light-theme .profile-block { background: #f5ebe0 !important; color: #1a1208 !important; }
body.light-theme .profile-block h2 { color: #c48a3a !important; }

/* Admin tabs */
body.light-theme .adm-tab {
    background: rgba(196,138,58,.08) !important;
    color: #5a4a2a !important;
    border: 1px solid rgba(196,138,58,.18) !important;
}
body.light-theme .adm-tab:hover { background: rgba(196,138,58,.16) !important; color: #c48a3a !important; }
body.light-theme .adm-tab.active { background: #c48a3a !important; color: #fff !important; border-color: transparent !important; }

/* Tables */
body.light-theme table.adm-table th { background: #f0e4d0 !important; color: #c48a3a !important; }
body.light-theme table.adm-table td { color: #2a1e0a !important; border-color: rgba(196,138,58,.12) !important; }
body.light-theme table.adm-table tr:hover td { background: rgba(196,138,58,.05) !important; }

/* Stat cards */
body.light-theme .stat-grid .stat-card { background: #fff9f0 !important; border: 1px solid rgba(196,138,58,.18) !important; }
body.light-theme .stat-card .num { color: #c48a3a !important; }
body.light-theme .stat-card .lbl { color: #7a6040 !important; }

/* Report blocks */
body.light-theme .report-block {
    background: #fff9f0 !important;
    border-color: rgba(196,138,58,.18) !important;
}
body.light-theme .report-block h3 { color: #c48a3a !important; }

/* Del rows */
body.light-theme .del-row { background: #f5ebe0 !important; color: #2a1e0a !important; }
body.light-theme .del-row strong { color: #c48a3a !important; }

/* Inline inputs */
body.light-theme select.inline-sel {
    background: #fff !important;
    color: #1a1208 !important;
    border-color: rgba(196,138,58,.3) !important;
}
body.light-theme textarea.inline-ta {
    background: #fff !important;
    color: #1a1208 !important;
    border-color: rgba(196,138,58,.3) !important;
}

/* Messages */
body.light-theme .ok-msg { background: #e8f5e9 !important; color: #1f5a2b !important; border-color: rgba(80,180,100,.2) !important; }
body.light-theme .err-msg { background: #fce8e8 !important; color: #8a1f1f !important; border-color: rgba(200,80,80,.2) !important; }

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
<div class="profile-card" style="max-width:1100px;">
    <div class="profile-head">
        <div class="profile-avatar">
            <?php if(!empty($user['avatar']) && file_exists($user['avatar'])):?>
                <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Аватар">
            <?php else:?>
                <img src="img/users/default-avatar.png" alt="Аватар">
            <?php endif;?>
        </div>
        <div>
            <h1>Кабинет администратора</h1>
            <p style="color:#aaa;">Здравствуйте, <?= htmlspecialchars($user['imya'].' '.$user['familiya']) ?></p>
        </div>
    </div>

    <?php if($okMsg):?><div class="ok-msg">✓ <?= htmlspecialchars($okMsg) ?></div><?php endif;?>
    <?php if($errMsg):?><div class="err-msg">✗ <?= htmlspecialchars($errMsg) ?></div><?php endif;?>

    <!-- Статистика -->
    <div class="stat-grid">
        <div class="stat-card"><div class="num"><?= $totalUsers ?></div><div class="lbl">Пользователей</div></div>
        <div class="stat-card"><div class="num"><?= $totalOrders ?></div><div class="lbl">Заказов</div></div>
        <div class="stat-card"><div class="num"><?= $totalDeliveries ?></div><div class="lbl">Доставок</div></div>
        <div class="stat-card"><div class="num"><?= number_format($totalRevenue,0,'.',' ') ?> ₽</div><div class="lbl">Общая выручка</div></div>
    </div>

    <!-- Вкладки -->
    <div class="adm-tabs no-print">
        <?php $tabs = ['users'=>'👥 Клиенты','orders'=>'📦 Заказы','deliveries'=>'🚚 Доставки','zayavki'=>'📋 Заявки','products'=>'🏷️ Товары','reports'=>'📊 Отчёты','settings'=>'⚙ Настройки'];
        $chatUnreadAdmin = (int)$pdo->query("SELECT COUNT(*) FROM chat_soobscheniya m JOIN polzovateli p ON p.id=m.ot_user_id WHERE p.role_id=1 AND m.procitano=0")->fetchColumn();
        ?>
        <?php foreach($tabs as $k=>$v):?>
            <button class="adm-tab <?= $activeTab===$k?'active':'' ?>" onclick="switchTab('<?= $k ?>',this)"><?= $v ?></button>
        <?php endforeach;?>
        <a href="chat.php" class="adm-tab" style="text-decoration:none;">
            💬 Чат<?php if($chatUnreadAdmin>0):?> <span style="background:#c48a3a;color:#fff;border-radius:10px;padding:1px 7px;font-size:11px;margin-left:4px;"><?= $chatUnreadAdmin ?></span><?php endif;?>
        </a>
    </div>

    <!-- ПОЛЬЗОВАТЕЛИ -->
    <div id="users" class="tab-content <?= $activeTab==='users'?'active':'' ?> profile-block">
        <h2>Управление пользователями</h2>
        <div style="overflow-x:auto;">
        <table class="adm-table">
            <thead><tr><th>ID</th><th>ФИО</th><th>Телефон</th><th>Email</th><th>Город</th><th>Роль</th><th>Зарегистрирован</th><th>Действия</th></tr></thead>
            <tbody>
            <?php foreach($users as $u):
                $roleBadge = ['admin'=>'badge-admin','employee'=>'badge-employee','client'=>'badge-client'][$u['role_name']] ?? '';
                $roleLabel = ['admin'=>'Админ','employee'=>'Сотрудник','client'=>'Клиент'][$u['role_name']] ?? $u['role_name'];
            ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['familiya'].' '.$u['imya'].' '.($u['otchestvo']??'')) ?></td>
                    <td><?= htmlspecialchars($u['telefon']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['mesto_prozhivaniya']??'—') ?></td>
                    <td><span class="badge <?= $roleBadge ?>"><?= $roleLabel ?></span></td>
                    <td><?= date('d.m.Y',strtotime($u['created_at'])) ?></td>
                    <td style="display:flex;gap:6px;flex-wrap:wrap;">
                        <?php if($u['id'] !== (int)$_SESSION['user_id']):?>
                        <form method="post" style="display:flex;gap:4px;align-items:center;">
                            <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                            <select name="role_id" class="inline-sel">
                                <?php foreach($roles as $r):?>
                                    <option value="<?= $r['id'] ?>" <?= $r['id']==$u['role_id']?'selected':'' ?>><?= ['admin'=>'Админ','employee'=>'Сотрудник','client'=>'Клиент'][$r['nazvanie']]??$r['nazvanie'] ?></option>
                                <?php endforeach;?>
                            </select>
                            <button type="submit" name="change_role" class="btn-sm">Сменить</button>
                        </form>
                        <form method="post" onsubmit="return confirm('Удалить пользователя?');">
                            <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                            <button type="submit" name="delete_user" class="btn-del">✕</button>
                        </form>
                        <?php else:?>
                            <span style="color:#666;font-size:12px;">Вы</span>
                        <?php endif;?>
                    </td>
                </tr>
            <?php endforeach;?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- ЗАКАЗЫ -->
    <div id="orders" class="tab-content <?= $activeTab==='orders'?'active':'' ?> profile-block">
        <h2>Управление заказами</h2>
        <div style="overflow-x:auto;">
        <table class="adm-table">
            <thead><tr><th>№ Заказа</th><th>Клиент</th><th>Телефон</th><th>Сумма</th><th>Дата</th><th>Статус</th><th>Действия</th></tr></thead>
            <tbody>
            <?php foreach($orders as $o):
                $stBadge = ['new'=>'badge-new','confirmed'=>'badge-confirmed','in_progress'=>'badge-progress','delivered'=>'badge-delivered','canceled'=>'badge-canceled'][$o['status_name']]??'';
                $stLabel = ['new'=>'Новый','confirmed'=>'Подтверждён','in_progress'=>'В сборке','delivered'=>'Доставлен','canceled'=>'Отменён'][$o['status_name']]??$o['status_name'];
            ?>
                <tr>
                    <td><?= htmlspecialchars($o['nomer_zakaza']) ?></td>
                    <td><?= htmlspecialchars($o['familiya'].' '.$o['imya']) ?></td>
                    <td><?= htmlspecialchars($o['telefon']) ?></td>
                    <td><?= number_format($o['obshaya_summa'],2,'.',' ') ?> ₽</td>
                    <td><?= date('d.m.Y',strtotime($o['created_at'])) ?></td>
                    <td><span class="badge <?= $stBadge ?>"><?= $stLabel ?></span></td>
                    <td style="display:flex;gap:6px;flex-wrap:wrap;">
                        <form method="post" style="display:flex;gap:4px;align-items:center;">
                            <input type="hidden" name="zid" value="<?= $o['id'] ?>">
                            <select name="status_id" class="inline-sel">
                                <?php foreach($statuses as $s):
                                    $slabel=['new'=>'Новый','confirmed'=>'Подтверждён','in_progress'=>'В сборке','delivered'=>'Доставлен','canceled'=>'Отменён'][$s['nazvanie']]??$s['nazvanie'];
                                ?>
                                    <option value="<?= $s['id'] ?>" <?= $s['id']==$o['status_id']?'selected':'' ?>><?= $slabel ?></option>
                                <?php endforeach;?>
                            </select>
                            <button type="submit" name="change_order_status" class="btn-sm">OK</button>
                        </form>
                        <form method="post" onsubmit="return confirm('Удалить заказ?');">
                            <input type="hidden" name="zid" value="<?= $o['id'] ?>">
                            <button type="submit" name="delete_order" class="btn-del">✕</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach;?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- ДОСТАВКИ -->
    <div id="deliveries" class="tab-content <?= $activeTab==='deliveries'?'active':'' ?> profile-block">
        <h2>Управление доставками</h2>
        <?php if(empty($deliveries)):?>
            <p style="color:#666;font-style:italic;">Доставок нет.</p>
        <?php else: foreach($deliveries as $d):
            $dsLabel = ['planned'=>'Запланирована','in_transit'=>'В пути','delivered'=>'Доставлена'][$d['status']]??$d['status'];
        ?>
            <div class="del-row">
                <strong>Заказ <?= htmlspecialchars($d['nomer_zakaza']) ?></strong> —
                <?= htmlspecialchars($d['familiya'].' '.$d['imya']) ?>, <?= htmlspecialchars($d['telefon']) ?><br>
                <span style="color:#aaa;">Адрес:</span> <?= htmlspecialchars($d['address']) ?><br>
                <span style="color:#aaa;">Дата доставки:</span> <?= ($d['delivery_date']&&$d['delivery_date']!=='0000-00-00')?date('d.m.Y',strtotime($d['delivery_date'])):'—' ?> |
                <span style="color:#aaa;">Сумма:</span> <?= number_format($d['obshaya_summa'],2,'.',' ') ?> ₽
                <form method="post" style="margin-top:10px;display:flex;gap:8px;align-items:flex-start;flex-wrap:wrap;">
                    <input type="hidden" name="did" value="<?= $d['id'] ?>">
                    <div>
                        <div style="color:#aaa;font-size:12px;margin-bottom:4px;">Статус:</div>
                        <select name="dstatus" class="inline-sel">
                            <?php foreach(['planned'=>'Запланирована','in_transit'=>'В пути','delivered'=>'Доставлена'] as $dv=>$dl):?>
                                <option value="<?= $dv ?>" <?= $dv===$d['status']?'selected':'' ?>><?= $dl ?></option>
                            <?php endforeach;?>
                        </select>
                    </div>
                    <div style="flex:1;min-width:180px;">
                        <div style="color:#aaa;font-size:12px;margin-bottom:4px;">Примечание:</div>
                        <textarea name="dnote" class="inline-ta" rows="2"><?= htmlspecialchars($d['note']??'') ?></textarea>
                    </div>
                    <button type="submit" name="update_delivery" class="btn-sm" style="margin-top:20px;">Сохранить</button>
                </form>
            </div>
        <?php endforeach; endif;?>
    </div>

    <!-- ЗАЯВКИ -->
    <div id="zayavki" class="tab-content <?= $activeTab==='zayavki'?'active':'' ?> profile-block">
        <h2>Заявки с сайта</h2>
        <div style="overflow-x:auto;">
        <table class="adm-table">
            <thead><tr><th>ID</th><th>Имя</th><th>Телефон</th><th>Сообщение</th><th>Статус</th><th>Дата</th></tr></thead>
            <tbody>
            <?php foreach($zayavki as $z):?>
                <tr>
                    <td><?= $z['id'] ?></td>
                    <td><?= htmlspecialchars($z['name']?:'—') ?></td>
                    <td><?= htmlspecialchars($z['phone']?:'—') ?></td>
                    <td><?= htmlspecialchars($z['message']?:'—') ?></td>
                    <td><span class="badge badge-new"><?= htmlspecialchars($z['status']) ?></span></td>
                    <td><?= date('d.m.Y H:i',strtotime($z['created_at'])) ?></td>
                </tr>
            <?php endforeach;?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- ТОВАРЫ -->
    <div id="products" class="tab-content <?= $activeTab==='products'?'active':'' ?> profile-block">
        <h2>Управление товарами</h2>

        <!-- Кнопка добавить -->
        <button class="btn-sm" onclick="toggleAddForm()" style="background:#2a7a3a;color:#fff;border:none;padding:9px 20px;border-radius:9px;cursor:pointer;font-size:14px;margin-bottom:18px;">➕ Добавить товар</button>

        <!-- Форма добавления -->
        <div id="add-tovar-form" style="display:none;background:#181818;border:1px solid #2e2e2e;border-radius:14px;padding:20px;margin-bottom:22px;">
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
                    <button type="submit" name="add_tovar" style="background:#2a7a3a;color:#fff;border:none;padding:9px 22px;border-radius:9px;cursor:pointer;font-size:14px;">✓ Добавить</button>
                    <button type="button" onclick="toggleAddForm()" style="background:#3a3a3a;color:#ccc;border:none;padding:9px 18px;border-radius:9px;cursor:pointer;font-size:14px;">Отмена</button>
                </div>
            </form>
        </div>

        <!-- Карточки товаров -->
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;">
        <?php foreach($tovary_all as $t):
            $qty = (int)$t['kolichestvo'];
            $clr = $qty === 0 ? '#e05252' : ($qty <= 10 ? '#e0a030' : '#52c052');
        ?>
        <div class="prod-card" style="background:#181818;border:1px solid #2e2e2e;border-radius:14px;overflow:hidden;">
            <!-- Фото -->
            <div style="position:relative;height:160px;background:#111;overflow:hidden;">
                <?php if (!empty($t['foto']) && file_exists($t['foto'])): ?>
                    <img src="<?= htmlspecialchars($t['foto']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                <?php else: ?>
                    <div style="display:flex;align-items:center;justify-content:center;height:100%;color:#444;font-size:40px;">📦</div>
                <?php endif; ?>
                <span style="position:absolute;top:8px;right:8px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:<?= $t['aktiven'] ? '#1a3a1a' : '#2a2a2a' ?>;color:<?= $t['aktiven'] ? '#52c052' : '#888' ?>;">
                    <?= $t['aktiven'] ? 'Активен' : 'Скрыт' ?>
                </span>
            </div>
            <!-- Инфо -->
            <div style="padding:14px;">
                <div style="font-size:15px;font-weight:700;color:#f0f0f0;margin-bottom:2px;"><?= htmlspecialchars($t['nazvanie']) ?></div>
                <div style="font-size:12px;color:#888;margin-bottom:6px;"><?= htmlspecialchars($t['opisanie'] ?? '') ?></div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                    <span style="color:#c48a3a;font-size:16px;font-weight:700;"><?= number_format($t['cena'],2,'.',' ') ?> ₽</span>
                    <span style="font-size:13px;font-weight:700;color:<?= $clr ?>"><?= $qty ?> шт.</span>
                </div>
                <div style="font-size:11px;color:#666;margin-bottom:12px;">ID: <?= $t['id'] ?> | Категория: <?= htmlspecialchars($t['kategoriya'] ?? '—') ?></div>
                <!-- Кнопки -->
                <div style="display:flex;gap:8px;">
                    <button onclick="openEditModal(<?= htmlspecialchars(json_encode($t)) ?>)" style="flex:1;background:#2a4a7a;color:#fff;border:none;padding:8px;border-radius:8px;cursor:pointer;font-size:13px;">✏️ Изменить</button>
                    <form method="post" onsubmit="return confirm('Удалить товар «<?= htmlspecialchars(addslashes($t['nazvanie'])) ?>»?');" style="flex:0;">
                        <input type="hidden" name="tovar_id" value="<?= $t['id'] ?>">
                        <button type="submit" name="delete_tovar" style="background:#5a1a1a;color:#ff9090;border:none;padding:8px 12px;border-radius:8px;cursor:pointer;font-size:13px;">🗑</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>

    <!-- МОДАЛЬНОЕ ОКНО РЕДАКТИРОВАНИЯ -->
    <div id="edit-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:9999;overflow-y:auto;padding:20px;">
        <div style="max-width:560px;margin:40px auto;background:#1a1a1a;border:1px solid #3a3a3a;border-radius:16px;padding:24px;position:relative;">
            <button onclick="closeEditModal()" style="position:absolute;top:12px;right:16px;background:none;border:none;color:#aaa;font-size:22px;cursor:pointer;">✕</button>
            <h3 style="color:#c48a3a;margin-bottom:18px;">Редактировать товар</h3>
            <form method="post" enctype="multipart/form-data" id="edit-form">
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
                        <label style="font-size:13px;color:#aaa;display:block;margin-bottom:4px;">Фото товара (оставьте пустым, чтобы не менять)</label>
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
                    <button type="submit" name="edit_tovar" style="background:#c48a3a;color:#fff;border:none;padding:10px 26px;border-radius:9px;cursor:pointer;font-size:14px;font-weight:700;">💾 Сохранить</button>
                    <button type="button" onclick="closeEditModal()" style="background:#3a3a3a;color:#ccc;border:none;padding:10px 18px;border-radius:9px;cursor:pointer;font-size:14px;">Отмена</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ОТЧЁТЫ -->
    <div id="reports" class="tab-content <?= $activeTab==='reports'?'active':'' ?> profile-block">
        <h2>Отчёты</h2>
        <div style="display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap;" class="no-print">
            <button class="btn-sm" onclick="printReport('delivery-report')">🖨 Печать отчёта по доставкам</button>
            <button class="btn-sm" onclick="printReport('orders-report')">🖨 Печать отчёта по заказам</button>
        </div>

        <!-- Отчёт по доставкам -->
        <div id="delivery-report" class="report-block">
            <h3>Отчёт по доставкам</h3>
            <table class="adm-table">
                <thead><tr><th>Заказ</th><th>Клиент</th><th>Телефон</th><th>Адрес</th><th>Дата доставки</th><th>Статус</th><th>Сумма</th></tr></thead>
                <tbody>
                <?php foreach($deliveries as $d):
                    $dsLabel = ['planned'=>'Запланирована','in_transit'=>'В пути','delivered'=>'Доставлена'][$d['status']]??$d['status'];
                ?>
                    <tr>
                        <td><?= htmlspecialchars($d['nomer_zakaza']) ?></td>
                        <td><?= htmlspecialchars($d['familiya'].' '.$d['imya']) ?></td>
                        <td><?= htmlspecialchars($d['telefon']) ?></td>
                        <td><?= htmlspecialchars($d['address']) ?></td>
                        <td><?= ($d['delivery_date']&&$d['delivery_date']!=='0000-00-00')?date('d.m.Y',strtotime($d['delivery_date'])):'—' ?></td>
                        <td><?= $dsLabel ?></td>
                        <td><?= number_format($d['obshaya_summa'],2,'.',' ') ?> ₽</td>
                    </tr>
                <?php endforeach;?>
                </tbody>
            </table>
        </div>

        <!-- Отчёт по заказам -->
        <div id="orders-report" class="report-block">
            <h3>Отчёт по заказам</h3>
            <table class="adm-table">
                <thead><tr><th>№ Заказа</th><th>Клиент</th><th>Телефон</th><th>Дата</th><th>Статус</th><th>Сумма</th></tr></thead>
                <tbody>
                <?php
                $reportTotal = 0;
                foreach($orders as $o):
                    $stLabel=['new'=>'Новый','confirmed'=>'Подтверждён','in_progress'=>'В сборке','delivered'=>'Доставлен','canceled'=>'Отменён'][$o['status_name']]??$o['status_name'];
                    $reportTotal += $o['obshaya_summa'];
                ?>
                    <tr>
                        <td><?= htmlspecialchars($o['nomer_zakaza']) ?></td>
                        <td><?= htmlspecialchars($o['familiya'].' '.$o['imya']) ?></td>
                        <td><?= htmlspecialchars($o['telefon']) ?></td>
                        <td><?= date('d.m.Y',strtotime($o['created_at'])) ?></td>
                        <td><?= $stLabel ?></td>
                        <td><?= number_format($o['obshaya_summa'],2,'.',' ') ?> ₽</td>
                    </tr>
                <?php endforeach;?>
                <tr style="font-weight:bold;">
                    <td colspan="5" style="color:#c48a3a;text-align:right;">Итого:</td>
                    <td style="color:#c48a3a;"><?= number_format($reportTotal,2,'.',' ') ?> ₽</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- НАСТРОЙКИ -->
    <div id="settings" class="tab-content <?= $activeTab==='settings'?'active':'' ?> profile-block">
        <h2>Настройки администратора</h2>
        <div style="margin-bottom:14px;">
            <strong>ФИО:</strong> <?= htmlspecialchars($user['familiya'].' '.$user['imya'].' '.($user['otchestvo']??'')) ?><br>
            <strong>Email:</strong> <?= htmlspecialchars($user['email']) ?><br>
            <strong>Телефон:</strong> <?= htmlspecialchars($user['telefon']) ?><br>
            <strong>Город:</strong> <?= htmlspecialchars($user['mesto_prozhivaniya']??'—') ?>
        </div>
        <h2 style="margin-top:16px;">Изменить аватар</h2>
        <form method="post" enctype="multipart/form-data" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <input type="file" name="avatar" accept="image/*" style="color:#ddd;">
            <button type="submit" name="upload_avatar" class="btn btn-primary">Сохранить аватар</button>
        </form>
    </div>

    <br class="no-print">
    <a href="index.php" class="btn btn-primary no-print">← На главную</a>
    <a href="logout.php" class="btn btn-primary no-print" style="margin-left:10px;background:#7a2a2a;">Выйти</a>
</div>
</div>
<script>
function switchTab(id, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.adm-tab').forEach(t => t.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    btn.classList.add('active');
}
function printReport(reportId) {
    const el = document.getElementById(reportId);
    const win = window.open('','_blank','width=900,height=600');
    win.document.write('<html><head><title>Отчёт</title><style>body{font-family:Arial,sans-serif;padding:20px;} table{width:100%;border-collapse:collapse;} th{background:#eee;padding:8px;text-align:left;border:1px solid #ccc;} td{padding:8px;border:1px solid #ccc;} h3{margin-bottom:12px;}</style></head><body>');
    win.document.write(el.innerHTML);
    win.document.write('<p style="margin-top:20px;color:#888;font-size:12px;">Дата формирования: ' + new Date().toLocaleString('ru-RU') + '</p>');
    win.document.write('</body></html>');
    win.document.close();
    win.print();
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
<script>
function toggleAddForm() {
    var f = document.getElementById('add-tovar-form');
    f.style.display = f.style.display === 'none' ? 'block' : 'none';
}
function previewNewImg(input) {
    var img = document.getElementById('new-preview');
    if (input.files && input.files[0]) {
        var r = new FileReader();
        r.onload = function(e){ img.src = e.target.result; img.style.display='block'; };
        r.readAsDataURL(input.files[0]);
    }
}
function previewEditImg(input) {
    var img = document.getElementById('edit-preview');
    if (input.files && input.files[0]) {
        var r = new FileReader();
        r.onload = function(e){ img.src = e.target.result; img.style.display='block'; };
        r.readAsDataURL(input.files[0]);
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
