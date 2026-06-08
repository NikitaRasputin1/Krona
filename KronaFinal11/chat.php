<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$myId   = (int)$_SESSION['user_id'];
$myRole = (int)($_SESSION['role_id'] ?? 1);

// ── AJAX: ОТПРАВИТЬ СООБЩЕНИЕ ─────────────────────────────
if (isset($_POST['ajax_send'])) {
    header('Content-Type: application/json');
    $tekst = trim($_POST['tekst'] ?? '');
    $toId  = (int)($_POST['to_id'] ?? 0);
    if ($tekst === '' || $toId <= 0) {
        echo json_encode(['ok' => false, 'err' => 'empty']);
        exit;
    }
    $pdo->prepare("INSERT INTO chat_soobscheniya (ot_user_id, komu_user_id, tekst) VALUES (?,?,?)")
        ->execute([$myId, $toId, $tekst]);
    echo json_encode(['ok' => true]);
    exit;
}

// ── AJAX: ПОЛУЧИТЬ СООБЩЕНИЯ ──────────────────────────────
if (isset($_GET['ajax_msgs'])) {
    header('Content-Type: application/json');
    $withId = (int)($_GET['with'] ?? 0);
    if ($withId <= 0) { echo json_encode([]); exit; }

    // Отмечаем прочитанными входящие
    $pdo->prepare("UPDATE chat_soobscheniya SET procitano=1 WHERE komu_user_id=? AND ot_user_id=?")
        ->execute([$myId, $withId]);

    $st = $pdo->prepare("
        SELECT m.id, m.ot_user_id, m.tekst,
               DATE_FORMAT(m.created_at,'%d.%m %H:%i') as vremya,
               p.familiya, p.imya, p.avatar, p.role_id
        FROM chat_soobscheniya m
        JOIN polzovateli p ON p.id = m.ot_user_id
        WHERE (m.ot_user_id=? AND m.komu_user_id=?)
           OR (m.ot_user_id=? AND m.komu_user_id=?)
        ORDER BY m.created_at ASC
    ");
    $st->execute([$myId, $withId, $withId, $myId]);
    echo json_encode($st->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// ── AJAX: СПИСОК КЛИЕНТОВ ДЛЯ СОТРУДНИКА ─────────────────
if (isset($_GET['ajax_clients']) && $myRole >= 2) {
    header('Content-Type: application/json');
    $st = $pdo->query("
        SELECT p.id, p.familiya, p.imya, p.avatar,
               (SELECT COUNT(*) FROM chat_soobscheniya
                WHERE ot_user_id=p.id AND procitano=0) as unread,
               (SELECT MAX(created_at) FROM chat_soobscheniya
                WHERE ot_user_id=p.id OR komu_user_id=p.id) as last_msg
        FROM polzovateli p
        WHERE p.role_id = 1
          AND EXISTS (
              SELECT 1 FROM chat_soobscheniya
              WHERE ot_user_id=p.id OR komu_user_id=p.id
          )
        ORDER BY last_msg DESC
    ");
    echo json_encode($st->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// ── ОБЫЧНАЯ ЗАГРУЗКА СТРАНИЦЫ ─────────────────────────────
if ($myRole === 1) {
    // Для клиента: список сотрудников/админов
    $staff = $pdo->query("
        SELECT id, familiya, imya, avatar, role_id
        FROM polzovateli WHERE role_id IN (2,3)
        ORDER BY role_id DESC, familiya ASC
    ")->fetchAll();
} else {
    // Для сотрудника: начальный список клиентов
    $clients = $pdo->query("
        SELECT p.id, p.familiya, p.imya, p.avatar,
               (SELECT COUNT(*) FROM chat_soobscheniya
                WHERE ot_user_id=p.id AND procitano=0) as unread,
               (SELECT MAX(created_at) FROM chat_soobscheniya
                WHERE ot_user_id=p.id OR komu_user_id=p.id) as last_msg
        FROM polzovateli p
        WHERE p.role_id = 1
          AND EXISTS (
              SELECT 1 FROM chat_soobscheniya
              WHERE ot_user_id=p.id OR komu_user_id=p.id
          )
        ORDER BY last_msg DESC
    ")->fetchAll();

    $totalUnread = (int)$pdo->query("
        SELECT COUNT(*) FROM chat_soobscheniya WHERE procitano=0
          AND ot_user_id IN (SELECT id FROM polzovateli WHERE role_id=1)
    ")->fetchColumn();
}

$cartCount = 0;
foreach (($_SESSION['cart'] ?? []) as $q) $cartCount += (int)$q;

// Данные текущего пользователя для JS
$me = $pdo->prepare("SELECT familiya, imya, avatar FROM polzovateli WHERE id=? LIMIT 1");
$me->execute([$myId]); $me = $me->fetch();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Чат — Крона</title>
<link rel="stylesheet" href="style.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{background:#0b0b0b;color:#f0f0f0;font-family:'Segoe UI',Arial,sans-serif;height:100vh;display:flex;flex-direction:column;overflow:hidden;}
.top-bar{display:flex;align-items:center;justify-content:space-between;padding:13px 28px;background:rgba(13,13,13,.97);border-bottom:1px solid rgba(196,138,58,.15);flex-shrink:0;}
.logo{font-size:18px;font-weight:800;color:#fff;text-decoration:none;}.logo span{color:#c48a3a;}
.top-links{display:flex;gap:16px;align-items:center;}
.top-links a{color:#888;font-size:14px;text-decoration:none;transition:.2s;}.top-links a:hover{color:#c48a3a;}
.chat-layout{display:flex;flex:1;overflow:hidden;}
.chat-sidebar{width:270px;border-right:1px solid rgba(255,255,255,.07);background:#0f0f0f;display:flex;flex-direction:column;flex-shrink:0;}
.sidebar-header{padding:14px 16px;border-bottom:1px solid rgba(255,255,255,.06);font-size:12px;font-weight:700;color:#666;text-transform:uppercase;letter-spacing:.6px;}
.sidebar-list{overflow-y:auto;flex:1;}
.sidebar-item{display:flex;align-items:center;gap:11px;padding:12px 16px;cursor:pointer;border-bottom:1px solid rgba(255,255,255,.03);transition:.15s;border-left:3px solid transparent;}
.sidebar-item:hover{background:rgba(255,255,255,.04);}
.sidebar-item.active{background:rgba(196,138,58,.1);border-left-color:#c48a3a;}
.sidebar-item.active .si-name{color:#fff;}
.si-av{width:38px;height:38px;border-radius:50%;background:#2a2a2a;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0;overflow:hidden;}
.si-av img{width:38px;height:38px;border-radius:50%;object-fit:cover;}
.si-info{flex:1;min-width:0;}
.si-name{font-size:14px;font-weight:600;color:#ccc;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.si-sub{font-size:11px;color:#555;margin-top:2px;}
.u-badge{background:#c48a3a;color:#fff;border-radius:50%;width:20px;height:20px;font-size:11px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.chat-area{flex:1;display:flex;flex-direction:column;overflow:hidden;}
.chat-hdr{padding:13px 20px;border-bottom:1px solid rgba(255,255,255,.07);display:flex;align-items:center;gap:12px;background:#111;flex-shrink:0;min-height:62px;}
.chat-hdr-av{width:36px;height:36px;border-radius:50%;background:#2a2a2a;display:flex;align-items:center;justify-content:center;font-size:17px;overflow:hidden;flex-shrink:0;}
.chat-hdr-av img{width:36px;height:36px;border-radius:50%;object-fit:cover;}
.chat-hdr-name{font-size:15px;font-weight:700;color:#fff;}
.chat-hdr-sub{font-size:12px;color:#888;}
.msgs{flex:1;overflow-y:auto;padding:18px 20px;display:flex;flex-direction:column;gap:10px;}
.msg{display:flex;gap:9px;max-width:74%;}
.msg.mine{align-self:flex-end;flex-direction:row-reverse;}
.msg-av{width:30px;height:30px;border-radius:50%;background:#2a2a2a;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;overflow:hidden;}
.msg-av img{width:30px;height:30px;border-radius:50%;object-fit:cover;}
.msg-body{display:flex;flex-direction:column;gap:3px;}
.msg.mine .msg-body{align-items:flex-end;}
.msg-name{font-size:11px;color:#555;font-weight:600;}
.bubble{padding:10px 14px;border-radius:16px;font-size:14px;line-height:1.5;word-break:break-word;}
.msg.theirs .bubble{background:#1e1e1e;color:#eee;border-bottom-left-radius:4px;}
.msg.mine .bubble{background:linear-gradient(135deg,#c48a3a,#9f661f);color:#fff;border-bottom-right-radius:4px;}
.msg-time{font-size:11px;color:#555;}
.msg.mine .msg-time{color:#b07830;}
.empty-state{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#333;text-align:center;gap:10px;padding:20px;}
.empty-state .ico{font-size:48px;opacity:.35;}
.empty-state h3{font-size:17px;font-weight:700;color:#444;}
.empty-state p{font-size:13px;color:#333;max-width:260px;line-height:1.6;}
.chat-inp{padding:12px 16px;border-top:1px solid rgba(255,255,255,.07);background:#0f0f0f;display:flex;gap:10px;align-items:flex-end;flex-shrink:0;}
.chat-inp textarea{flex:1;background:#1a1a1a;border:1px solid #2a2a2a;border-radius:12px;padding:10px 14px;color:#fff;font-size:14px;font-family:inherit;resize:none;outline:none;max-height:100px;line-height:1.5;transition:.2s;}
.chat-inp textarea:focus{border-color:rgba(196,138,58,.5);}
.chat-inp textarea::placeholder{color:#444;}
.send-btn{background:linear-gradient(135deg,#c48a3a,#9f661f);border:none;color:#fff;width:42px;height:42px;border-radius:11px;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.send-btn:disabled{opacity:.4;cursor:default;}
body.light-theme{background:#f0e8d8!important;color:#1a1208;}
body.light-theme .top-bar{background:rgba(240,232,216,.97);}
body.light-theme .chat-sidebar{background:#f5ede0;border-color:rgba(196,138,58,.15);}
body.light-theme .chat-hdr{background:#f9f2e8;}
body.light-theme .chat-inp{background:#f5ede0;}
body.light-theme .chat-inp textarea{background:#fff;border-color:rgba(196,138,58,.2);color:#1a1208;}
body.light-theme .msg.theirs .bubble{background:#fff;color:#1a1208;}
body.light-theme .si-name{color:#2a1a08;}
body.light-theme .sidebar-item.active .si-name{color:#1a1208;}
</style>
</head>
<body>

<div class="top-bar">
    <a class="logo" href="index.php">Кро<span>на</span></a>
    <div class="top-links">
        <?php if ($myRole === 1): ?>
            <a href="profile.php">← Профиль</a>
            <a href="produkciya.php">Продукция</a>
            <?php if ($cartCount > 0): ?><a href="cart.php">🛒 <?= $cartCount ?></a><?php endif; ?>
        <?php else: ?>
            <a href="<?= $myRole===3?'admin.php':'employee.php' ?>">← Кабинет</a>
            <span id="totalUnreadBadge" style="display:<?= !empty($totalUnread)?'inline':'none' ?>;background:#c48a3a;color:#fff;border-radius:20px;padding:3px 10px;font-size:12px;font-weight:700;"><?= $totalUnread ?? 0 ?> новых</span>
        <?php endif; ?>
    </div>
</div>

<div class="chat-layout">

    <!-- SIDEBAR -->
    <div class="chat-sidebar">
        <div class="sidebar-header"><?= $myRole === 1 ? 'Написать' : 'Клиенты' ?></div>
        <div class="sidebar-list" id="sidebarList">

            <?php if ($myRole === 1): ?>
                <?php if (empty($staff)): ?>
                    <div style="padding:20px;color:#444;font-size:13px;text-align:center;">Нет доступных менеджеров</div>
                <?php else: foreach ($staff as $s):
                    $rl = (int)$s['role_id'] === 3 ? 'Администратор' : 'Сотрудник'; ?>
                    <div class="sidebar-item" data-id="<?= $s['id'] ?>" data-name="<?= htmlspecialchars($s['familiya'].' '.$s['imya']) ?>" data-sub="<?= $rl ?>" data-avatar="<?= htmlspecialchars($s['avatar'] ?? '') ?>" onclick="selectChat(this)">
                        <div class="si-av">
                            <?php if (!empty($s['avatar']) && file_exists($s['avatar'])): ?><img src="<?= htmlspecialchars($s['avatar']) ?>" alt=""><?php else: ?>👷<?php endif; ?>
                        </div>
                        <div class="si-info">
                            <div class="si-name"><?= htmlspecialchars($s['familiya'].' '.$s['imya']) ?></div>
                            <div class="si-sub"><?= $rl ?></div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>

            <?php else: ?>
                <?php if (empty($clients)): ?>
                    <div id="noClients" style="padding:20px;color:#444;font-size:13px;text-align:center;">Пока нет переписок</div>
                <?php else: foreach ($clients as $cl): ?>
                    <div class="sidebar-item" data-id="<?= $cl['id'] ?>" data-name="<?= htmlspecialchars($cl['familiya'].' '.$cl['imya']) ?>" data-sub="" data-avatar="<?= htmlspecialchars($cl['avatar'] ?? '') ?>" onclick="selectChat(this)">
                        <div class="si-av">
                            <?php if (!empty($cl['avatar']) && file_exists($cl['avatar'])): ?><img src="<?= htmlspecialchars($cl['avatar']) ?>" alt=""><?php else: ?>👤<?php endif; ?>
                        </div>
                        <div class="si-info">
                            <div class="si-name"><?= htmlspecialchars($cl['familiya'].' '.$cl['imya']) ?></div>
                            <div class="si-sub"><?= $cl['last_msg'] ? date('d.m H:i', strtotime($cl['last_msg'])) : '' ?></div>
                        </div>
                        <?php if ((int)$cl['unread'] > 0): ?>
                            <div class="u-badge"><?= $cl['unread'] ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; endif; ?>
            <?php endif; ?>

        </div>
    </div>

    <!-- ЧАТА ОБЛАСТЬ -->
    <div class="chat-area">
        <!-- Шапка -->
        <div class="chat-hdr" id="chatHdr">
            <div style="color:#444;font-size:14px;padding:4px;">
                <?= $myRole === 1 ? 'Выберите менеджера из списка слева' : 'Выберите клиента из списка слева' ?>
            </div>
        </div>

        <!-- Сообщения -->
        <div class="msgs" id="msgs">
            <div class="empty-state" id="emptyState">
                <div class="ico">💬</div>
                <h3><?= $myRole === 1 ? 'Выберите менеджера' : 'Выберите клиента' ?></h3>
                <p>Выберите собеседника из списка слева чтобы открыть переписку.</p>
            </div>
        </div>

        <!-- Поле ввода -->
        <div class="chat-inp" id="chatInp" style="display:none;">
            <textarea id="msgInput" placeholder="Напишите сообщение..." rows="1"
                onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendMsg();}"></textarea>
            <button class="send-btn" id="sendBtn" onclick="sendMsg()">➤</button>
        </div>
    </div>
</div>

<script>
var MY_ID    = <?= $myId ?>;
var MY_ROLE  = <?= $myRole ?>;
var MY_NAME  = <?= json_encode($me['imya'] ?? 'Вы') ?>;
var MY_AV    = <?= json_encode($me['avatar'] ?? '') ?>;

var activeId   = 0;  // ID собеседника
var activeName = '';
var lastMsgId  = 0;  // последний показанный id сообщения
var pollTimer  = null;

// ── Выбор собеседника ─────────────────────────────────────
function selectChat(el) {
    document.querySelectorAll('.sidebar-item').forEach(function(x){ x.classList.remove('active'); });
    el.classList.add('active');

    activeId   = parseInt(el.dataset.id);
    activeName = el.dataset.name;
    var sub    = el.dataset.sub || '';
    var av     = el.dataset.avatar || '';

    // Убираем бейдж непрочитанных
    var badge = el.querySelector('.u-badge');
    if (badge) badge.remove();

    // Шапка
    var avHtml = av ? '<img src="'+av+'" alt="">' : (MY_ROLE===1 ? '👷' : '👤');
    document.getElementById('chatHdr').innerHTML =
        '<div class="chat-hdr-av">'+avHtml+'</div>' +
        '<div><div class="chat-hdr-name">'+activeName+'</div>' +
        (sub ? '<div class="chat-hdr-sub">'+sub+'</div>' : '') + '</div>';

    // Показываем поле ввода
    document.getElementById('chatInp').style.display = 'flex';

    // Сбрасываем и грузим сообщения
    lastMsgId = 0;
    document.getElementById('msgs').innerHTML = '';
    loadMsgs();

    // Фокус на поле
    setTimeout(function(){ document.getElementById('msgInput').focus(); }, 100);
}

// ── Загрузка сообщений (polling) ─────────────────────────
function loadMsgs() {
    if (!activeId) return;
    fetch('chat.php?ajax_msgs=1&with=' + activeId)
        .then(function(r){ return r.json(); })
        .then(function(msgs) {
            var wrap = document.getElementById('msgs');
            // Только новые
            var newMsgs = msgs.filter(function(m){ return parseInt(m.id) > lastMsgId; });
            if (newMsgs.length === 0) return;

            // Убираем заглушку если была
            var es = document.getElementById('emptyState');
            if (es) es.remove();

            newMsgs.forEach(function(m) {
                var isMine = parseInt(m.ot_user_id) === MY_ID;
                var cls    = isMine ? 'mine' : 'theirs';
                var avHtml = m.avatar ? '<img src="'+m.avatar+'" alt="">' : (parseInt(m.role_id)===1?'👤':'👷');
                var nameHtml = isMine ? 'Вы' : m.familiya;

                var div = document.createElement('div');
                div.className = 'msg ' + cls;
                div.innerHTML =
                    '<div class="msg-av">'+avHtml+'</div>' +
                    '<div class="msg-body">' +
                      '<div class="msg-name">'+nameHtml+'</div>' +
                      '<div class="bubble">'+escHtml(m.tekst).replace(/\n/g,'<br>')+'</div>' +
                      '<div class="msg-time">'+m.vremya+'</div>' +
                    '</div>';
                wrap.appendChild(div);
                lastMsgId = Math.max(lastMsgId, parseInt(m.id));
            });

            // Скролл вниз
            wrap.scrollTop = wrap.scrollHeight;
        })
        .catch(function(e){ console.error('loadMsgs error', e); });
}

// ── Отправка ─────────────────────────────────────────────
function sendMsg() {
    var ta   = document.getElementById('msgInput');
    var btn  = document.getElementById('sendBtn');
    var text = ta.value.trim();
    if (!text || !activeId) return;

    btn.disabled = true;
    ta.value = '';
    ta.style.height = 'auto';

    var fd = new FormData();
    fd.append('ajax_send', '1');
    fd.append('to_id', activeId);
    fd.append('tekst', text);

    fetch('chat.php', { method: 'POST', body: fd })
        .then(function(r){ return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            if (data.ok) {
                loadMsgs(); // сразу обновляем
                // Для сотрудника — обновляем список клиентов
                if (MY_ROLE >= 2) refreshClients();
            }
        })
        .catch(function(){ btn.disabled = false; });
}

// ── Обновление списка клиентов (для сотрудника) ───────────
function refreshClients() {
    if (MY_ROLE < 2) return;
    fetch('chat.php?ajax_clients=1')
        .then(function(r){ return r.json(); })
        .then(function(clients) {
            var list = document.getElementById('sidebarList');
            var noC  = document.getElementById('noClients');
            if (noC) noC.remove();

            // Добавляем новых клиентов которых нет в списке
            clients.forEach(function(cl) {
                var existing = list.querySelector('[data-id="'+cl.id+'"]');
                if (!existing) {
                    var av = cl.avatar ? '<img src="'+cl.avatar+'" alt="">' : '👤';
                    var div = document.createElement('div');
                    div.className = 'sidebar-item' + (parseInt(cl.id)===activeId?' active':'');
                    div.dataset.id     = cl.id;
                    div.dataset.name   = cl.familiya + ' ' + cl.imya;
                    div.dataset.sub    = '';
                    div.dataset.avatar = cl.avatar || '';
                    div.setAttribute('onclick','selectChat(this)');
                    div.innerHTML =
                        '<div class="si-av">'+av+'</div>' +
                        '<div class="si-info">' +
                          '<div class="si-name">'+cl.familiya+' '+cl.imya+'</div>' +
                          '<div class="si-sub"></div>' +
                        '</div>';
                    list.appendChild(div);
                }
                // Обновляем бейдж
                if (parseInt(cl.id) !== activeId && parseInt(cl.unread) > 0) {
                    var ex = list.querySelector('[data-id="'+cl.id+'"]');
                    if (ex && !ex.querySelector('.u-badge')) {
                        var b = document.createElement('div');
                        b.className = 'u-badge';
                        b.textContent = cl.unread;
                        ex.appendChild(b);
                    }
                }
            });
        });
}

// ── Polling каждые 3 секунды ──────────────────────────────
setInterval(function() {
    if (activeId) loadMsgs();
    if (MY_ROLE >= 2) refreshClients();
}, 3000);

// ── Авторасширение textarea ───────────────────────────────
document.getElementById('msgInput').addEventListener('input', function(){
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 100) + 'px';
});

// ── Экранирование HTML ────────────────────────────────────
function escHtml(s) {
    return String(s)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Для клиента: автовыбор первого сотрудника ─────────────
<?php if ($myRole === 1 && !empty($staff)): ?>
window.addEventListener('DOMContentLoaded', function(){
    var first = document.querySelector('.sidebar-item');
    if (first) selectChat(first);
});
<?php endif; ?>

// Тема
if (localStorage.getItem('krona-theme') === 'light') document.body.classList.add('light-theme');
</script>
</body>
</html>
