<?php
session_start();
require_once 'connect.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telefon = trim($_POST['telefon'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $familiya = trim($_POST['familiya'] ?? '');
    $imya = trim($_POST['imya'] ?? '');
    $otchestvo = trim($_POST['otchestvo'] ?? '');
    $data_rozhdeniya = trim($_POST['data_rozhdeniya'] ?? '');
    $mesto_prozhivaniya = trim($_POST['mesto_prozhivaniya'] ?? '');
    $parol = $_POST['parol'] ?? '';
    $parol2 = $_POST['parol2'] ?? '';

    if ($telefon === '') $errors[] = 'Введите телефон';
    if ($email === '') $errors[] = 'Введите email';
    if ($familiya === '') $errors[] = 'Введите фамилию';
    if ($imya === '') $errors[] = 'Введите имя';
    if ($parol === '') $errors[] = 'Введите пароль';
    if ($parol !== $parol2) $errors[] = 'Пароли не совпадают';

    if (!$errors) {
        $stmt = $pdo->prepare("SELECT id FROM polzovateli WHERE email = ? OR telefon = ? LIMIT 1");
        $stmt->execute([$email, $telefon]);
        if ($stmt->fetch()) {
            $errors[] = 'Пользователь с таким email или телефоном уже существует';
        } else {
            $hash = password_hash($parol, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO polzovateli (role_id, telefon, email, familiya, imya, otchestvo, data_rozhdeniya, mesto_prozhivaniya, parol_hash) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $telefon,
                $email,
                $familiya,
                $imya,
                $otchestvo !== '' ? $otchestvo : null,
                $data_rozhdeniya !== '' ? $data_rozhdeniya : null,
                $mesto_prozhivaniya !== '' ? $mesto_prozhivaniya : null,
                $hash
            ]);
            $success = 'Регистрация прошла успешно';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" href="style.css">
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
        <h2>Регистрация</h2>
        <?php if ($errors): ?>
            <div class="auth-error"><?php echo implode('<br>', $errors); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="auth-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <input type="text" name="telefon" placeholder="Телефон" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="familiya" placeholder="Фамилия" required>
        <input type="text" name="imya" placeholder="Имя" required>
        <input type="text" name="otchestvo" placeholder="Отчество">
        <input type="date" name="data_rozhdeniya" placeholder="Дата рождения">
        <input type="text" name="mesto_prozhivaniya" placeholder="Место проживания">
        <input type="password" name="parol" placeholder="Пароль" required>
        <input type="password" name="parol2" placeholder="Подтверждение пароля" required>
        <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
        <a href="login.php">Уже есть аккаунт? Войти</a>
    </form>
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