<?php
// ============================================================
//  telegram.php — отправка уведомлений через Telegram Bot API
// ============================================================
//
//  КАК НАСТРОИТЬ (пошагово):
//
//  1. Откройте Telegram, найдите @BotFather
//  2. Напишите ему: /newbot
//  3. Введите название бота (например: Крона Уведомления)
//  4. Введите username бота (например: kronahelper_bot)
//  5. BotFather пришлёт токен вида: 1234567890:AAF...xyz
//  6. Вставьте токен в TELEGRAM_BOT_TOKEN ниже
//  7. Вставьте username бота (без @) в TELEGRAM_BOT_USERNAME
//
//  КАК КЛИЕНТ ПОДКЛЮЧАЕТСЯ:
//  1. Клиент находит вашего бота в Telegram по @username
//  2. Нажимает /start  ← БЕЗ ЭТОГО БОТ НЕ СМОЖЕТ ПИСАТЬ КЛИЕНТУ
//  3. Идёт в профиль на сайте → вводит свой @username → жмёт «Привязать»
//  4. Теперь при каждой смене статуса заказа бот пишет ему сообщение
//
//  ПОЧЕМУ НЕ РАБОТАЕТ (частые ошибки):
//  — Клиент НЕ написал /start боту → бот не может инициировать диалог
//  — Неверный токен (скопировали не полностью)
//  — Клиент заблокировал бота
// ============================================================

define('TELEGRAM_BOT_TOKEN',    '8834705108:AAFdEanD8Pd8abkkCDPzjfbKYrF6R59Kt-w');
define('TELEGRAM_BOT_USERNAME', 'kronahelper_bot');

/**
 * Отправить сообщение клиенту по его chat_id.
 * chat_id — это числовой ID пользователя в Telegram,
 * он сохраняется в polzovateli.telegram_chat_id при привязке.
 */
function tgSend(string $chatId, string $text): bool {
    if (empty($chatId)) return false;

    // Правильный URL: api.telegram.org/bot{TOKEN}/sendMessage
    $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage';

    $payload = json_encode([
        'chat_id'    => $chatId,
        'text'       => $text,
        'parse_mode' => 'HTML',
    ]);

    $ctx = stream_context_create(['http' => [
        'method'        => 'POST',
        'header'        => "Content-Type: application/json\r\n",
        'content'       => $payload,
        'timeout'       => 5,
        'ignore_errors' => true,
    ]]);

    $result = @file_get_contents($url, false, $ctx);
    if ($result === false) return false;

    $data = json_decode($result, true);
    return !empty($data['ok']);
}

/**
 * Найти chat_id пользователя по его @username.
 * Работает через getUpdates — ищет среди всех кто писал боту /start.
 * ВАЖНО: клиент должен сначала написать боту /start,
 * иначе его не будет в списке обновлений.
 */
function tgResolveChatId(string $username): ?string {
    $username = ltrim(strtolower(trim($username)), '@');
    if (empty($username)) return null;

    // Правильный URL: api.telegram.org/bot{TOKEN}/getUpdates
    $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/getUpdates?limit=100';

    $ctx = stream_context_create(['http' => [
        'timeout'       => 5,
        'ignore_errors' => true,
    ]]);

    $result = @file_get_contents($url, false, $ctx);
    if (!$result) return null;

    $data = json_decode($result, true);
    if (empty($data['ok']) || empty($data['result'])) return null;

    // Перебираем все входящие сообщения боту (от новых к старым)
    foreach (array_reverse($data['result']) as $upd) {
        $from = $upd['message']['from'] ?? null;
        if (!$from) continue;
        if (strtolower($from['username'] ?? '') === $username) {
            return (string)$from['id']; // Возвращаем числовой chat_id
        }
    }

    return null; // Пользователь не писал боту /start
}

/**
 * Отправить уведомление клиенту при смене статуса заказа.
 */
function tgNotifyOrderStatus(PDO $pdo, int $orderId, string $newStatusName): void {
    $stmt = $pdo->prepare("
        SELECT z.nomer_zakaza, z.obshaya_summa,
               p.imya, p.familiya, p.telegram_chat_id
        FROM zakazy z
        JOIN polzovateli p ON p.id = z.user_id
        WHERE z.id = ? LIMIT 1
    ");
    $stmt->execute([$orderId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || empty($row['telegram_chat_id'])) return;

    $statusMessages = [
        'new' => [
            'icon'  => '📥',
            'title' => 'Заказ принят',
            'desc'  => 'Ваш заказ получен и ожидает обработки. Мы скоро свяжемся с вами.',
        ],
        'confirmed' => [
            'icon'  => '✅',
            'title' => 'Заказ подтверждён',
            'desc'  => 'Заказ подтверждён и передан в работу. Скоро начнём сборку!',
        ],
        'in_progress' => [
            'icon'  => '📦',
            'title' => 'Заказ собирается',
            'desc'  => 'Ваш заказ сейчас собирают на складе. Скоро будет готов к отгрузке.',
        ],
        'delivered' => [
            'icon'  => '🏠',
            'title' => 'Заказ доставлен',
            'desc'  => 'Ваш заказ доставлен. Спасибо, что выбрали Крону!',
        ],
        'canceled' => [
            'icon'  => '✕',
            'title' => 'Заказ отменён',
            'desc'  => 'Ваш заказ был отменён. Если есть вопросы — свяжитесь с нами.',
        ],
    ];

    $s = $statusMessages[$newStatusName] ?? null;
    if (!$s) return;

    $name  = $row['imya'];
    $nomer = $row['nomer_zakaza'];
    $summa = number_format((float)$row['obshaya_summa'], 2, '.', ' ');

    $text = "{$s['icon']} <b>{$s['title']}</b>\n\n"
          . "Здравствуйте, {$name}!\n"
          . "{$s['desc']}\n\n"
          . "📋 <b>Заказ:</b> #{$nomer}\n"
          . "💰 <b>Сумма:</b> {$summa} ₽\n\n"
          . "🌲 <i>Лесопилка Крона</i>";

    tgSend($row['telegram_chat_id'], $text);
}
