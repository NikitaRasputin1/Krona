<?php
session_start();
require_once 'connect.php';

// Загружаем товары из БД для показа цен
$tovary = $pdo->query("SELECT id, nazvanie, opisanie, cena, kategoriya FROM tovary WHERE aktiven=1 ORDER BY kategoriya, id")->fetchAll();

// Группируем по категориям для удобства
$byKat = [];
foreach ($tovary as $t) {
    $byKat[$t['kategoriya'] ?? 'Прочее'][] = $t;
}

$cartCount = 0;
foreach (($_SESSION['cart'] ?? []) as $qty) $cartCount += (int)$qty;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Калькулятор материалов — Крона</title>
<link rel="stylesheet" href="style.css">
<style>
:root{--gold:#c48a3a;--gold2:#9f661f;--bg:#0b0b0b;--card:#131313;--card2:#1a1a1a;--border:rgba(255,255,255,.07);--text:#f0f0f0;--muted:#888;}
*{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--bg);color:var(--text);font-family:'Segoe UI',Arial,sans-serif;min-height:100vh;}

/* HEADER */
.page-header{display:flex;align-items:center;justify-content:space-between;padding:16px 40px;background:rgba(13,13,13,.96);border-bottom:1px solid rgba(196,138,58,.15);position:sticky;top:0;z-index:10;backdrop-filter:blur(14px);}
.logo{font-size:20px;font-weight:800;color:#fff;text-decoration:none;}.logo span{color:var(--gold);}
.header-links{display:flex;gap:18px;align-items:center;}
.header-links a{color:#888;font-size:14px;text-decoration:none;transition:.2s;}.header-links a:hover{color:var(--gold);}
.cart-badge{position:relative;}.cart-count{position:absolute;top:-6px;right:-8px;background:var(--gold);color:#fff;border-radius:50%;width:16px;height:16px;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;}

/* LAYOUT */
.page-wrap{max-width:960px;margin:0 auto;padding:44px 24px 80px;}
.page-title{font-size:32px;font-weight:900;margin-bottom:6px;}
.page-title span{color:var(--gold);}
.page-sub{color:var(--muted);font-size:15px;margin-bottom:36px;}

/* TYPE SELECTOR */
.type-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:36px;}
.type-card{background:var(--card2);border:2px solid var(--border);border-radius:16px;padding:22px 18px;cursor:pointer;transition:.2s;text-align:center;}
.type-card:hover{border-color:rgba(196,138,58,.4);background:#1e1e1e;}
.type-card.active{border-color:var(--gold);background:rgba(196,138,58,.08);}
.type-card .ico{font-size:36px;margin-bottom:10px;}
.type-card h3{font-size:16px;font-weight:700;color:var(--text);margin-bottom:4px;}
.type-card p{font-size:12px;color:var(--muted);}

/* FORM */
.calc-form{background:var(--card2);border:1px solid var(--border);border-radius:20px;padding:32px;margin-bottom:28px;display:none;}
.calc-form.active{display:block;}
.form-title{font-size:20px;font-weight:800;margin-bottom:24px;color:var(--text);}
.form-title span{color:var(--gold);}
.fields-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:18px;margin-bottom:28px;}
.field label{display:block;font-size:13px;color:var(--muted);margin-bottom:8px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;}
.field input,.field select{width:100%;padding:12px 16px;border-radius:12px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);color:var(--text);font-size:16px;outline:none;transition:.2s;font-family:inherit;}
.field input:focus,.field select:focus{border-color:var(--gold);background:rgba(196,138,58,.05);}
.field input::placeholder{color:#444;}
.field .hint{font-size:11px;color:#555;margin-top:5px;}
.btn-calc{background:linear-gradient(135deg,var(--gold),var(--gold2));color:#fff;border:none;padding:14px 36px;border-radius:14px;font-size:16px;font-weight:800;cursor:pointer;transition:.2s;box-shadow:0 4px 20px rgba(196,138,58,.3);}
.btn-calc:hover{transform:translateY(-1px);box-shadow:0 6px 28px rgba(196,138,58,.45);}

/* SELECT product row */
.product-row{display:grid;grid-template-columns:1fr auto;gap:12px;align-items:center;background:rgba(255,255,255,.02);border:1px solid var(--border);border-radius:12px;padding:14px 18px;margin-bottom:10px;}
.product-row-info{font-size:14px;color:var(--text);}
.product-row-info small{color:var(--muted);font-size:12px;display:block;margin-top:2px;}

/* RESULT */
.result-box{background:var(--card2);border:1px solid rgba(196,138,58,.25);border-radius:20px;padding:32px;display:none;}
.result-box.active{display:block;}
.result-title{font-size:22px;font-weight:800;margin-bottom:22px;}
.result-title span{color:var(--gold);}
.result-rows{margin-bottom:24px;}
.result-row{display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid var(--border);font-size:15px;}
.result-row:last-child{border-bottom:none;}
.result-row .name{color:#ddd;flex:1;}
.result-row .qty{color:var(--gold);font-weight:700;min-width:80px;text-align:center;}
.result-row .price{color:#aaa;min-width:120px;text-align:right;font-size:13px;}
.result-total{display:flex;justify-content:space-between;align-items:center;background:rgba(196,138,58,.08);border:1px solid rgba(196,138,58,.2);border-radius:14px;padding:18px 22px;margin-top:16px;}
.result-total .lbl{font-size:16px;font-weight:700;color:var(--text);}
.result-total .sum{font-size:26px;font-weight:900;color:var(--gold);}
.note-box{background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:12px;padding:14px 18px;margin-top:16px;font-size:13px;color:var(--muted);line-height:1.6;}
.note-box strong{color:#bbb;}
.btn-recalc{background:transparent;border:1px solid var(--border);color:var(--muted);padding:10px 22px;border-radius:10px;cursor:pointer;font-size:14px;margin-top:18px;transition:.2s;}
.btn-recalc:hover{border-color:var(--gold);color:var(--gold);}
.btn-to-products{background:rgba(196,138,58,.12);border:1px solid rgba(196,138,58,.3);color:var(--gold);padding:10px 22px;border-radius:10px;cursor:pointer;font-size:14px;margin-top:18px;margin-left:10px;text-decoration:none;display:inline-block;transition:.2s;}
.btn-to-products:hover{background:rgba(196,138,58,.2);}

/* WARN */
.warn{background:rgba(224,90,50,.08);border:1px solid rgba(224,90,50,.25);border-radius:10px;padding:10px 16px;font-size:13px;color:#ffaa80;margin-bottom:16px;display:none;}

/* LIGHT THEME */
body.light-theme{background:linear-gradient(135deg,#f0e8d8,#e8dfc8)!important;color:#1a1208;}
body.light-theme .page-header{background:rgba(240,232,216,.96);}
body.light-theme .type-card{background:#fff9f0;border-color:rgba(196,138,58,.15);color:#1a1208;}
body.light-theme .type-card h3{color:#1a1208;}
body.light-theme .type-card.active{border-color:var(--gold);background:rgba(196,138,58,.08);}
body.light-theme .calc-form,.body.light-theme .result-box{background:#fff9f0;border-color:rgba(196,138,58,.15);}
body.light-theme .field input,.body.light-theme .field select{background:#fff;border-color:rgba(196,138,58,.2);color:#1a1208;}
body.light-theme .result-row .name{color:#2a1a08;}
body.light-theme .product-row{background:rgba(196,138,58,.03);}
</style>
</head>
<body>

<header class="page-header">
    <a class="logo" href="index.php">Кро<span>на</span></a>
    <div class="header-links">
        <a href="produkciya.php">Продукция</a>
        <a href="kalkulator.php" style="color:var(--gold);">Калькулятор</a>
        <?php if(isset($_SESSION['user_id'])):?>
            <a href="cart.php" class="cart-badge">🛒 Корзина<?php if($cartCount>0):?><span class="cart-count"><?= $cartCount ?></span><?php endif;?></a>
            <a href="profile.php">Кабинет</a>
        <?php else:?>
            <a href="login.php">Войти</a>
        <?php endif;?>
    </div>
</header>

<div class="page-wrap">
    <h1 class="page-title">Калькулятор <span>материалов</span></h1>
    <p class="page-sub">Введите размеры — мы сами посчитаем, сколько материала вам нужно и сколько это стоит.</p>

    <!-- ШАГ 1: Выбор типа расчёта -->
    <div class="type-grid" id="typeGrid">
        <div class="type-card" onclick="selectType('brus')">
            <div class="ico">🪵</div>
            <h3>Брус</h3>
            <p>Расчёт по объёму конструкции (длина × ширина × высота)</p>
        </div>
        <div class="type-card" onclick="selectType('doska')">
            <div class="ico">🏗️</div>
            <h3>Доска обрезная</h3>
            <p>Расчёт по площади пола, стены или перекрытия</p>
        </div>
        <div class="type-card" onclick="selectType('opilki')">
            <div class="ico">🪣</div>
            <h3>Опилки / горбыль</h3>
            <p>Расчёт по объёму засыпки или количеству кубов</p>
        </div>
    </div>

    <!-- ФОРМА: Брус -->
    <div class="calc-form" id="form-brus">
        <div class="form-title">Расчёт <span>бруса</span></div>
        <div class="fields-grid">
            <div class="field">
                <label>Длина конструкции (м)</label>
                <input type="number" id="brus-l" placeholder="например, 6" min="0.1" step="0.1">
                <div class="hint">Длина помещения или стены</div>
            </div>
            <div class="field">
                <label>Ширина конструкции (м)</label>
                <input type="number" id="brus-w" placeholder="например, 4" min="0.1" step="0.1">
            </div>
            <div class="field">
                <label>Высота конструкции (м)</label>
                <input type="number" id="brus-h" placeholder="например, 3" min="0.1" step="0.1">
            </div>
        </div>
        <div class="field" style="margin-bottom:22px;">
            <label>Выберите сечение бруса</label>
            <select id="brus-product">
                <option value="">— выберите —</option>
                <?php foreach(($byKat['Брус'] ?? []) as $t):?>
                <option value="<?= $t['id'] ?>" data-cena="<?= $t['cena'] ?>" data-section="<?= htmlspecialchars($t['opisanie']) ?>">
                    <?= htmlspecialchars($t['nazvanie']) ?> — <?= htmlspecialchars($t['opisanie']) ?> (<?= number_format($t['cena'],0,'.',' ') ?> ₽/шт.)
                </option>
                <?php endforeach;?>
            </select>
        </div>
        <div class="warn" id="brus-warn">Заполните все поля и выберите сечение бруса.</div>
        <button class="btn-calc" onclick="calcBrus()">Рассчитать →</button>
        <button class="btn-recalc" onclick="resetType()" style="margin-left:10px;">← Назад</button>
    </div>

    <!-- ФОРМА: Доска -->
    <div class="calc-form" id="form-doska">
        <div class="form-title">Расчёт <span>доски обрезной</span></div>
        <div class="fields-grid">
            <div class="field">
                <label>Длина (м)</label>
                <input type="number" id="doska-l" placeholder="например, 5" min="0.1" step="0.1">
            </div>
            <div class="field">
                <label>Ширина (м)</label>
                <input type="number" id="doska-w" placeholder="например, 4" min="0.1" step="0.1">
            </div>
        </div>
        <div class="field" style="margin-bottom:22px;">
            <label>Выберите доску</label>
            <select id="doska-product">
                <option value="">— выберите —</option>
                <?php foreach(($byKat['Доска обрезная'] ?? []) as $t):?>
                <option value="<?= $t['id'] ?>" data-cena="<?= $t['cena'] ?>" data-desc="<?= htmlspecialchars($t['opisanie']) ?>">
                    <?= htmlspecialchars($t['nazvanie']) ?> — <?= htmlspecialchars($t['opisanie']) ?> (<?= number_format($t['cena'],0,'.',' ') ?> ₽/шт.)
                </option>
                <?php endforeach;?>
            </select>
            <div class="hint">Расчёт по площади: сколько досок нужно для покрытия указанной поверхности</div>
        </div>
        <div class="warn" id="doska-warn">Заполните все поля и выберите доску.</div>
        <button class="btn-calc" onclick="calcDoska()">Рассчитать →</button>
        <button class="btn-recalc" onclick="resetType()" style="margin-left:10px;">← Назад</button>
    </div>

    <!-- ФОРМА: Опилки/горбыль -->
    <div class="calc-form" id="form-opilki">
        <div class="form-title">Расчёт <span>опилок и горбыля</span></div>
        <div class="fields-grid">
            <div class="field">
                <label>Нужный объём (м³)</label>
                <input type="number" id="opil-vol" placeholder="например, 3" min="0.1" step="0.1">
                <div class="hint">Или введите длину × ширину × глубину засыпки</div>
            </div>
            <div class="field">
                <label>Длина засыпки (м) <small style="color:#555">необязательно</small></label>
                <input type="number" id="opil-l" placeholder="например, 6" min="0" step="0.1">
            </div>
            <div class="field">
                <label>Ширина засыпки (м) <small style="color:#555">необязательно</small></label>
                <input type="number" id="opil-w" placeholder="например, 4" min="0" step="0.1">
            </div>
            <div class="field">
                <label>Глубина засыпки (м) <small style="color:#555">необязательно</small></label>
                <input type="number" id="opil-d" placeholder="например, 0.3" min="0" step="0.01">
            </div>
        </div>
        <div class="field" style="margin-bottom:22px;">
            <label>Материал</label>
            <select id="opil-product">
                <option value="">— выберите —</option>
                <?php foreach(array_merge($byKat['Опилок'] ?? [], $byKat['Горбыль'] ?? []) as $t):?>
                <option value="<?= $t['id'] ?>" data-cena="<?= $t['cena'] ?>" data-cat="<?= htmlspecialchars($t['kategoriya']) ?>">
                    <?= htmlspecialchars($t['nazvanie']) ?> — <?= htmlspecialchars($t['opisanie']) ?> (<?= number_format($t['cena'],0,'.',' ') ?> ₽/ед.)
                </option>
                <?php endforeach;?>
            </select>
        </div>
        <div class="warn" id="opil-warn">Укажите объём или размеры засыпки и выберите материал.</div>
        <button class="btn-calc" onclick="calcOpilki()">Рассчитать →</button>
        <button class="btn-recalc" onclick="resetType()" style="margin-left:10px;">← Назад</button>
    </div>

    <!-- РЕЗУЛЬТАТ -->
    <div class="result-box" id="resultBox">
        <div class="result-title">Результат <span>расчёта</span></div>
        <div class="result-rows" id="resultRows"></div>
        <div class="result-total" id="resultTotal"></div>
        <div class="note-box" id="resultNote"></div>
        <div style="margin-top:18px;display:flex;gap:10px;flex-wrap:wrap;">
            <button class="btn-recalc" onclick="resetResult()">← Новый расчёт</button>
            <a href="produkciya.php" class="btn-to-products">Перейти к товарам →</a>
        </div>
    </div>
</div>

<script>
// ========== ТОВАРЫ ИЗ PHP ==========
const TOVARY = <?php
    $forJs = [];
    foreach ($tovary as $t) {
        $forJs[$t['id']] = ['id'=>$t['id'],'nazvanie'=>$t['nazvanie'],'opisanie'=>$t['opisanie'],'cena'=>(float)$t['cena'],'kategoriya'=>$t['kategoriya']];
    }
    echo json_encode($forJs, JSON_UNESCAPED_UNICODE);
?>;

// ========== UI ==========
function selectType(type) {
    document.querySelectorAll('.type-card').forEach(c => c.classList.remove('active'));
    document.querySelectorAll('.calc-form').forEach(f => f.classList.remove('active'));
    document.getElementById('resultBox').classList.remove('active');

    const card = document.querySelector(`.type-card[onclick="selectType('${type}')"]`);
    if (card) card.classList.add('active');
    document.getElementById('form-' + type).classList.add('active');
    document.getElementById('form-' + type).scrollIntoView({behavior:'smooth', block:'nearest'});
}

function resetType() {
    document.querySelectorAll('.type-card').forEach(c => c.classList.remove('active'));
    document.querySelectorAll('.calc-form').forEach(f => f.classList.remove('active'));
    document.getElementById('resultBox').classList.remove('active');
}

function resetResult() {
    document.getElementById('resultBox').classList.remove('active');
}

function showResult(rows, total, note) {
    // rows = [{name, qty, unit, pricePerUnit, totalPrice}]
    const rb = document.getElementById('resultRows');
    rb.innerHTML = rows.map(r => `
        <div class="result-row">
            <span class="name">${r.name}</span>
            <span class="qty">${r.qty} ${r.unit}</span>
            <span class="price">${r.pricePerUnit.toLocaleString('ru-RU')} ₽/ед. = <strong style="color:#ddd">${r.totalPrice.toLocaleString('ru-RU')} ₽</strong></span>
        </div>
    `).join('');

    document.getElementById('resultTotal').innerHTML = `
        <span class="lbl">Итого (с запасом 10%):</span>
        <span class="sum">${total.toLocaleString('ru-RU')} ₽</span>
    `;
    document.getElementById('resultNote').innerHTML = note;
    document.getElementById('resultBox').classList.add('active');
    document.getElementById('resultBox').scrollIntoView({behavior:'smooth', block:'nearest'});
}

function warn(id, show) {
    document.getElementById(id).style.display = show ? 'block' : 'none';
}

// ========== РАСЧЁТ БРУСА ==========
// Логика: объём конструкции = L×W×H
// Объём 1 бруса определяем из описания (или приблизительно из сечения)
// Парсим сечение из строки вида "150х200х6,0" → (0.15 × 0.20 × 6.0 м³)
function parseBrusSection(desc) {
    // форматы: "150х200х6,0" или "100х100х6,0" (мм×мм×м) или "200х200х6000мм"
    const m = desc.replace(/\s/g,'').match(/(\d+)[хx×Xx](\d+)[хx×Xx]([\d,\.]+)/i);
    if (!m) return null;
    let a = parseFloat(m[1]) / 1000; // мм → м
    let b = parseFloat(m[2]) / 1000; // мм → м
    let cStr = m[3].replace(',','.');
    let c = parseFloat(cStr);
    // если с > 100, считаем что это мм, иначе метры
    if (c > 100) c = c / 1000;
    return {a, b, c, vol: a * b * c};
}

function calcBrus() {
    const L = parseFloat(document.getElementById('brus-l').value);
    const W = parseFloat(document.getElementById('brus-w').value);
    const H = parseFloat(document.getElementById('brus-h').value);
    const sel = document.getElementById('brus-product');
    const tid = parseInt(sel.value);

    if (!L || !W || !H || !tid) { warn('brus-warn', true); return; }
    warn('brus-warn', false);

    const tovar = TOVARY[tid];
    const section = parseBrusSection(tovar.opisanie || '');

    let volKonstruktsii = L * W * H; // м³
    let qtyShts, note;

    if (section && section.vol > 0) {
        // Количество штук = объём конструкции / объём 1 бруса
        qtyShts = Math.ceil(volKonstruktsii / section.vol);
        note = `<strong>Как считали:</strong> Объём конструкции ${L}×${W}×${H} = ${volKonstruktsii.toFixed(2)} м³. 
                Объём одного бруса (${tovar.opisanie}) ≈ ${section.vol.toFixed(4)} м³. 
                Штук = ${volKonstruktsii.toFixed(2)} / ${section.vol.toFixed(4)} = ${qtyShts} шт. (с округлением вверх).<br>
                <strong>В расчёт включён запас 10%.</strong> Фактический расход зависит от конструкции.`;
    } else {
        // Fallback: считаем в кубах
        qtyShts = Math.ceil(volKonstruktsii * 1.1);
        note = `<strong>Как считали:</strong> Объём конструкции ${L}×${W}×${H} = ${volKonstruktsii.toFixed(2)} м³. Указано штук с запасом 10%.`;
    }

    const withReserve = Math.ceil(qtyShts * 1.1);
    const totalPrice = Math.round(withReserve * tovar.cena);

    showResult(
        [{name: tovar.nazvanie + ' ' + (tovar.opisanie||''), qty: withReserve, unit:'шт.', pricePerUnit: tovar.cena, totalPrice}],
        totalPrice,
        note
    );
}

// ========== РАСЧЁТ ДОСКИ ==========
// Логика: площадь = L×W, из описания парсим сечение доски
// Количество = площадь / (ширина_доски × длина_доски)
function parseDoskaSection(desc) {
    // формат "0,040х0,0150х6,0" → толщина × ширина × длина (метры)
    const m = desc.replace(/\s/g,'').match(/([\d,\.]+)[хx×Xx]([\d,\.]+)[хx×Xx]([\d,\.]+)/i);
    if (!m) return null;
    const a = parseFloat(m[1].replace(',','.'));
    const b = parseFloat(m[2].replace(',','.'));
    const c = parseFloat(m[3].replace(',','.'));
    // a — толщина, b — ширина, c — длина
    return {thickness: a, width: b, length: c, areaPerBoard: b * c};
}

function calcDoska() {
    const L = parseFloat(document.getElementById('doska-l').value);
    const W = parseFloat(document.getElementById('doska-w').value);
    const sel = document.getElementById('doska-product');
    const tid = parseInt(sel.value);

    if (!L || !W || !tid) { warn('doska-warn', true); return; }
    warn('doska-warn', false);

    const tovar = TOVARY[tid];
    const section = parseDoskaSection(tovar.opisanie || '');
    const area = L * W;
    let qty, note;

    if (section && section.areaPerBoard > 0) {
        qty = Math.ceil(area / section.areaPerBoard);
        note = `<strong>Как считали:</strong> Площадь ${L}×${W} = ${area.toFixed(2)} м². 
                Площадь одной доски (${tovar.opisanie}) = ${section.width} × ${section.length} = ${section.areaPerBoard.toFixed(3)} м². 
                Штук = ${area.toFixed(2)} / ${section.areaPerBoard.toFixed(3)} = ${qty} шт. (с округлением вверх).<br>
                <strong>В расчёт включён запас 10%.</strong>`;
    } else {
        qty = Math.ceil(area * 3);
        note = `<strong>Как считали:</strong> Площадь ${L}×${W} = ${area.toFixed(2)} м². Ориентировочный расчёт. <strong>Запас 10% включён.</strong>`;
    }

    const withReserve = Math.ceil(qty * 1.1);
    const totalPrice = Math.round(withReserve * tovar.cena);

    showResult(
        [{name: tovar.nazvanie + ' ' + (tovar.opisanie||''), qty: withReserve, unit:'шт.', pricePerUnit: tovar.cena, totalPrice}],
        totalPrice,
        note
    );
}

// ========== РАСЧЁТ ОПИЛОК/ГОРБЫЛЯ ==========
function calcOpilki() {
    const sel = document.getElementById('opil-product');
    const tid = parseInt(sel.value);

    // Объём: либо прямой ввод, либо из размеров
    let vol = parseFloat(document.getElementById('opil-vol').value) || 0;
    const l = parseFloat(document.getElementById('opil-l').value) || 0;
    const w = parseFloat(document.getElementById('opil-w').value) || 0;
    const d = parseFloat(document.getElementById('opil-d').value) || 0;

    if (l > 0 && w > 0 && d > 0) vol = l * w * d;

    if (!vol || !tid) { warn('opil-warn', true); return; }
    warn('opil-warn', false);

    const tovar = TOVARY[tid];
    const withReserve = Math.ceil(vol * 1.1 * 10) / 10;

    // Горбыль — в кубах (ед = 1 куб), Опилок — в мешках (1 мешок 60л = 0.06 м³)
    let qty, unit, note;
    const isOpilok = (tovar.kategoriya === 'Опилок');

    if (isOpilok) {
        const liters = withReserve * 1000;
        qty = Math.ceil(liters / 60); // 60 л / мешок
        unit = 'мешков';
        note = `<strong>Как считали:</strong> Объём засыпки ${vol.toFixed(2)} м³ + 10% запас = ${withReserve} м³ = ${(withReserve*1000).toFixed(0)} л. 
                Мешок опилок — 60 л. Мешков: ${qty} шт.<br>
                <strong>Запас 10% включён.</strong>`;
    } else {
        qty = Math.ceil(withReserve * 10) / 10;
        unit = 'м³';
        note = `<strong>Как считали:</strong> Объём засыпки ${vol.toFixed(2)} м³ + 10% запас = ${withReserve} м³ горбыля.<br>
                <strong>Запас 10% включён.</strong>`;
    }

    const totalPrice = Math.round(qty * tovar.cena);

    showResult(
        [{name: tovar.nazvanie + ' — ' + (tovar.opisanie||''), qty: qty, unit, pricePerUnit: tovar.cena, totalPrice}],
        totalPrice,
        note
    );
}

// THEME
(function(){
    var STORAGE_KEY='krona-theme';
    var body=document.body;
    var saved=localStorage.getItem(STORAGE_KEY)||'dark';
    if(saved==='light') body.classList.add('light-theme');
})();
</script>
</body>
</html>
