<?php
session_start();
require_once 'connect.php';

$errors = [];
$successReset = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login_submit'])) {
        $email = trim($_POST['email'] ?? '');
        $parol = $_POST['parol'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM polzovateli WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($parol, $user['parol_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role_id'] = (int)$user['role_id'];
            $_SESSION['user_name'] = $user['imya'];

            switch ((int)$user['role_id']) {
                case 3:
                    header('Location: admin.php');
                    exit;
                case 2:
                    header('Location: employee.php');
                    exit;
                case 1:
                    header('Location: profile.php');
                    exit;
                default:
                    header('Location: index.php');
                    exit;
            }
        } else {
            $errors[] = 'Неверный email или пароль';
        }
    }

    if (isset($_POST['reset_submit'])) {
        $reset_email = trim($_POST['reset_email'] ?? '');

        if ($reset_email === '') {
            $errors[] = 'Введите email для восстановления';
        } else {
            $stmt = $pdo->prepare("SELECT id, email FROM polzovateli WHERE email = ? LIMIT 1");
            $stmt->execute([$reset_email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $stmt = $pdo->prepare("INSERT INTO password_reset_requests (user_id, email, token, status) VALUES (?, ?, ?, 'pending')");
                $stmt->execute([$user['id'], $user['email'], $token]);

                $successReset = 'Запрос отправлен, ожидайте ответного сообщения на указанную почту.';
            } else {
                $errors[] = 'Пользователь с таким email не найден';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script>
(function(){var t=localStorage.getItem('krona-theme');if(t==='light')document.documentElement.classList.add('light-theme-pre');})();
</script>
<style>
html.light-theme-pre body{background:#f5f0e8 !important;color:#1a1208 !important;}

/* ===== LIGHT THEME overrides ===== */
body.light-theme { background: linear-gradient(135deg,#f0e8d8,#e8dfc8) !important; color: #1a1208; }
body.light-theme .auth-page { background: linear-gradient(135deg,#f0e8d8,#e8dfc8) !important; }
body.light-theme .auth-form {
    background: #fff9f0 !important;
    border: 1px solid rgba(196,138,58,.2) !important;
    color: #1a1208 !important;
    box-shadow: 0 14px 40px rgba(0,0,0,.08) !important;
}
body.light-theme .auth-form h2 { color: #1a1208 !important; }
body.light-theme .auth-form input {
    background: #fff !important;
    border-color: rgba(196,138,58,.35) !important;
    color: #1a1208 !important;
    box-shadow: 0 2px 6px rgba(196,138,58,.06);
}
body.light-theme .auth-form input::placeholder { color: #a08060 !important; }
body.light-theme .auth-form input:focus {
    border-color: #c48a3a !important;
    box-shadow: 0 0 0 3px rgba(196,138,58,.15) !important;
}
body.light-theme .auth-form a { color: #c48a3a !important; }
body.light-theme .auth-error { background: #ffe8e8 !important; color: #8a1f1f !important; border-color: rgba(200,80,80,.2) !important; }
body.light-theme .auth-success { background: #e8f5e9 !important; color: #1f5a2b !important; border-color: rgba(80,180,100,.18) !important; }

/* Bootstrap modal */
body.light-theme .modal-content { background: #fff9f0 !important; color: #1a1208 !important; border-color: rgba(196,138,58,.2) !important; }
body.light-theme .modal-header, body.light-theme .modal-footer { border-color: rgba(196,138,58,.15) !important; }
body.light-theme .modal-title, body.light-theme .modal-body, body.light-theme .modal-body p { color: #1a1208 !important; }
body.light-theme .form-control {
    background: #fff !important;
    border-color: rgba(196,138,58,.3) !important;
    color: #1a1208 !important;
}
body.light-theme .btn-close { filter: none !important; }

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
<div class="auth-page">
    <form class="auth-form" method="post">
        <h2>Вход</h2>

        <?php if ($errors): ?>
            <div class="auth-error"><?php echo implode('<br>', $errors); ?></div>
        <?php endif; ?>

        <?php if ($successReset): ?>
            <div class="auth-success"><?php echo htmlspecialchars($successReset); ?></div>
        <?php endif; ?>

        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="parol" placeholder="Пароль" required>

        <button type="submit" name="login_submit" class="btn btn-primary">Войти</button>

        <button type="button" class="btn btn-link" data-bs-toggle="modal" data-bs-target="#resetModal">
            Восстановить пароль
        </button>

        <a href="register.php">Нет аккаунта? Регистрация</a>
    </form>
</div>

<div class="modal fade" id="resetModal" tabindex="-1" aria-labelledby="resetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="resetModalLabel">Восстановление пароля</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Введите вашу почту, и мы отправим запрос на восстановление пароля.</p>
                    <input type="email" name="reset_email" class="form-control" placeholder="Ваш email" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="reset_submit" class="btn btn-primary">Отправить запрос</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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