-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Июн 10 2026 г., 10:34
-- Версия сервера: 10.4.32-MariaDB
-- Версия PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `krona1`
--

-- --------------------------------------------------------

--
-- Структура таблицы `chat_soobscheniya`
--

CREATE TABLE `chat_soobscheniya` (
  `id` int(10) UNSIGNED NOT NULL,
  `ot_user_id` int(10) UNSIGNED NOT NULL,
  `komu_user_id` int(10) UNSIGNED DEFAULT NULL,
  `tekst` text NOT NULL,
  `procitano` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `chat_soobscheniya`
--

INSERT INTO `chat_soobscheniya` (`id`, `ot_user_id`, `komu_user_id`, `tekst`, `procitano`, `created_at`) VALUES
(1, 5, 2, 'привет', 1, '2026-05-31 10:13:00'),
(2, 5, 2, 'как привязать аккаунт-тг?', 1, '2026-05-31 10:13:13'),
(3, 2, 5, 'привет', 1, '2026-05-31 10:13:46'),
(4, 2, 5, 'молча', 1, '2026-05-31 10:13:48'),
(5, 2, 5, 'ок понял', 1, '2026-05-31 19:01:05'),
(6, 2, 5, 'привет', 1, '2026-05-31 19:01:08'),
(7, 5, 2, 'куку', 1, '2026-05-31 19:47:29'),
(8, 2, 5, 'Привет Артем', 1, '2026-05-31 21:13:56'),
(9, 5, 2, 'Привет', 1, '2026-05-31 21:14:09'),
(10, 5, 2, 'привет', 1, '2026-05-31 21:24:43'),
(11, 6, 2, 'привет', 1, '2026-06-02 19:38:30'),
(12, 5, 2, 'хай', 1, '2026-06-02 19:38:40');

-- --------------------------------------------------------

--
-- Структура таблицы `dostavka`
--

CREATE TABLE `dostavka` (
  `id` int(10) UNSIGNED NOT NULL,
  `zakaz_id` int(10) UNSIGNED NOT NULL,
  `address` text NOT NULL,
  `delivery_date` date DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'planned',
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `dostavka`
--

INSERT INTO `dostavka` (`id`, `zakaz_id`, `address`, `delivery_date`, `status`, `note`, `created_at`) VALUES
(2, 4, 'улица Городской Вал, 15к1, подъезд 3', '0000-00-00', 'in_transit', 'Задержка', '2026-05-14 10:29:22'),
(3, 5, 'улица Городской Вал, 15к1, подъезд 3', '2222-02-02', 'planned', '', '2026-05-14 17:38:57'),
(4, 7, 'Архангельск, набережная 19', '2026-05-29', 'planned', 'Заезд во двор со стороны завода.', '2026-05-24 19:30:16'),
(5, 8, 'Архангельск', '2026-05-29', 'planned', '', '2026-05-24 19:31:21'),
(6, 9, 'Ярославль', '2026-05-29', 'planned', '', '2026-05-24 19:33:16'),
(7, 11, 'Архангельск', '2026-06-29', 'planned', 'Позвонить когда будете у ворот.', '2026-05-26 21:58:54'),
(8, 15, 'Архангельск', '2026-06-22', 'planned', '', '2026-05-31 10:34:16'),
(9, 16, 'Архангельск', '2026-08-01', 'planned', '', '2026-05-31 10:34:30'),
(10, 17, 'Архангельск', '2026-07-22', 'planned', '', '2026-05-31 21:25:37'),
(11, 18, 'Псков', '2026-06-22', 'planned', '', '2026-06-02 20:02:36'),
(12, 19, 'Архангельск', '2026-06-23', 'planned', '', '2026-06-02 20:03:01');

-- --------------------------------------------------------

--
-- Структура таблицы `password_reset_requests`
--

CREATE TABLE `password_reset_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `email` varchar(150) NOT NULL,
  `token` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `password_reset_requests`
--

INSERT INTO `password_reset_requests` (`id`, `user_id`, `email`, `token`, `status`, `created_at`) VALUES
(4, 5, 'klient1@yandex.ru', '7dd1a14ba1aa42bad5b5e9353b8744e8421ddde00771a7352bc24c944d59de52', 'pending', '2026-05-26 22:21:36'),
(5, 5, 'klient1@yandex.ru', '2007f91d9fbfd44f688752a8f9f6277267d99afa166585ffa6d73ef9b2527e61', 'pending', '2026-05-26 22:22:01'),
(6, 5, 'klient1@yandex.ru', '6aee1d97b27500cc435bfba92ca2b6dbf3ae8c7b9037a8bb3309ebcb247d50d5', 'pending', '2026-05-26 22:23:49'),
(7, 5, 'klient1@yandex.ru', '4d4d441ec25b9f90677432d1f390783a08125dc4f946320929c66be5c92df548', 'pending', '2026-05-31 19:03:36');

-- --------------------------------------------------------

--
-- Структура таблицы `polzovateli`
--

CREATE TABLE `polzovateli` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `telefon` varchar(20) NOT NULL,
  `email` varchar(150) NOT NULL,
  `familiya` varchar(100) NOT NULL,
  `imya` varchar(100) NOT NULL,
  `otchestvo` varchar(100) DEFAULT NULL,
  `data_rozhdeniya` date DEFAULT NULL,
  `mesto_prozhivaniya` varchar(150) DEFAULT NULL,
  `parol_hash` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `telegram_username` varchar(100) DEFAULT NULL,
  `telegram_chat_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `polzovateli`
--

INSERT INTO `polzovateli` (`id`, `role_id`, `telefon`, `email`, `familiya`, `imya`, `otchestvo`, `data_rozhdeniya`, `mesto_prozhivaniya`, `parol_hash`, `avatar`, `created_at`, `telegram_username`, `telegram_chat_id`) VALUES
(1, 1, '+79642960549', 'klient@yandex.ru', 'Распутин', 'Никита', 'Александрович', '2006-02-22', 'Ярославль', '$2y$10$WUEPv/WGs6qHM2P3cE2a6.5f2gExeLzLBe/WoJgDAYQyYtxaNuPOi', 'uploads/avatars/1_1779651182.jpg', '2026-05-07 20:22:05', NULL, NULL),
(2, 2, '+79082514449', 'sotrudnik@yandex.ru', 'Кашалот', 'Андрей', 'Александрович', '2006-02-22', 'Москва', '$2y$10$hD9EfCbjMUE7R5YBMwwDheahjZnx3wZpN31.z9BEFIriWsm83jZ2C', 'uploads/avatars/2_1779651035.jpg', '2026-05-07 20:53:34', NULL, NULL),
(3, 3, '+79082514445', 'admin@yandex.ru', 'Кашалот', 'Артем', 'Александрович', NULL, 'Питер', '$2y$10$61P3hvM6XDPcmxYOCHeqDu2v1cVydvPdB4YC5rWQn7.8q6iZG3fC6', 'uploads/avatars/3_1779299584.jpg', '2026-05-08 17:47:43', NULL, NULL),
(5, 1, '+79642960544', 'klient1@yandex.ru', 'Смирнов', 'Артем', 'Дмитриевич', '2004-05-25', 'Архангельск', '$2y$10$kXPo0mGCrFbmtHOb9LRIyeSEr/EL69siloYp2xhFIu5QqPocTWM7S', 'uploads/avatars/5_1779650926.jpg', '2026-05-24 19:25:58', 'artemqwerty123123', '1042549514'),
(6, 1, '+79642960444', 'klient2@yandex.ru', 'Антонов', 'Андрей', 'Александрович', '2003-04-25', 'Псков', '$2y$10$6.V9TL74cY1Rbks/yQyb6O3Z1dcY67J967HjFQ7wPa9/8vbeQ88j.', NULL, '2026-06-02 19:38:18', NULL, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `roli`
--

CREATE TABLE `roli` (
  `id` int(10) UNSIGNED NOT NULL,
  `nazvanie` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `roli`
--

INSERT INTO `roli` (`id`, `nazvanie`) VALUES
(3, 'admin'),
(1, 'client'),
(2, 'employee');

-- --------------------------------------------------------

--
-- Структура таблицы `statusy_zakazov`
--

CREATE TABLE `statusy_zakazov` (
  `id` int(10) UNSIGNED NOT NULL,
  `nazvanie` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `statusy_zakazov`
--

INSERT INTO `statusy_zakazov` (`id`, `nazvanie`) VALUES
(5, 'canceled'),
(2, 'confirmed'),
(4, 'delivered'),
(3, 'in_progress'),
(1, 'new');

-- --------------------------------------------------------

--
-- Структура таблицы `tovary`
--

CREATE TABLE `tovary` (
  `id` int(10) UNSIGNED NOT NULL,
  `nazvanie` varchar(150) NOT NULL,
  `opisanie` text DEFAULT NULL,
  `cena` decimal(10,2) NOT NULL DEFAULT 0.00,
  `foto` varchar(255) DEFAULT NULL,
  `kategoriya` varchar(100) DEFAULT NULL,
  `aktiven` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `kolichestvo` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `tovary`
--

INSERT INTO `tovary` (`id`, `nazvanie`, `opisanie`, `cena`, `foto`, `kategoriya`, `aktiven`, `created_at`, `kolichestvo`) VALUES
(1, 'Брус', '150х200х6,0 антисептированный', 950.00, 'img/tovar/brus.jpg', 'Брус', 1, '2026-05-13 19:46:44', 11),
(2, 'Доска', '0,040х0,0150х6,0 антисептированная', 300.00, 'img/tovar/doska-obrez.jpg', 'Доска обрезная', 1, '2026-05-13 19:51:21', 8),
(3, 'Доска', '0,040х0,0150х4,0 антисептированная', 250.00, 'img/tovar/doska-obrez.jpg', 'Доска обрезная', 1, '2026-05-18 10:30:39', 20),
(4, 'Брус', '200х200х6000мм антисептированный', 900.00, 'img/tovar/brus.jpg', 'Брус', 1, '2026-05-13 19:46:44', 19),
(5, 'Брус', '100х100х6,0 антисептированный', 650.00, 'img/tovar/brus.jpg', 'Брус', 1, '2026-05-13 19:46:44', 0),
(6, 'Горбыль (сосна)', '1 куб.м', 900.00, 'img/tovar/gorb.jpg', 'Горбыль', 1, '2026-05-13 19:46:44', 0),
(7, 'Горбыль (ель)', '1 куб.м ', 900.00, 'img/tovar/gorb.jpg', 'Горбыль', 1, '2026-05-13 19:46:44', 0),
(8, 'Опилок', 'Мешок 60 л.', 100.00, 'img/tovar/opilok.jpg', 'Опилок', 1, '2026-05-13 19:46:44', 0);

-- --------------------------------------------------------

--
-- Структура таблицы `zakazy`
--

CREATE TABLE `zakazy` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `nomer_zakaza` varchar(30) NOT NULL,
  `obshaya_summa` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tip_dostavki` enum('standard','na_datu') NOT NULL DEFAULT 'standard',
  `data_dostavki` date DEFAULT NULL,
  `komentariy` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `zakazy`
--

INSERT INTO `zakazy` (`id`, `user_id`, `status_id`, `nomer_zakaza`, `obshaya_summa`, `tip_dostavki`, `data_dostavki`, `komentariy`, `created_at`) VALUES
(3, 3, 3, 'Z1778754539234', 4000.00, '', NULL, NULL, '2026-05-14 10:28:59'),
(4, 3, 3, 'Z1778754562453', 2000.00, 'na_datu', '0000-00-00', NULL, '2026-05-14 10:29:22'),
(5, 2, 4, 'Z1778780337634', 5000.00, 'na_datu', '2222-02-02', NULL, '2026-05-14 17:38:57'),
(7, 5, 4, 'Z1779651016391', 4350.00, 'na_datu', '2026-05-29', 'Заезд во двор со стороны завода.', '2026-05-24 19:30:16'),
(8, 5, 4, 'Z1779651081626', 3000.00, 'na_datu', '2026-05-29', NULL, '2026-05-24 19:31:21'),
(9, 1, 4, 'Z1779651196420', 200.00, 'na_datu', '2026-05-29', NULL, '2026-05-24 19:33:16'),
(10, 1, 3, 'Z1779651202946', 2500.00, '', NULL, NULL, '2026-05-24 19:33:22'),
(11, 5, 4, 'Z1779832734972', 3200.00, 'na_datu', '2026-06-29', 'Позвонить когда будете у ворот.', '2026-05-26 21:58:54'),
(12, 5, 4, 'Z1779833147334', 1400.00, '', NULL, 'Заберу 29 числа', '2026-05-26 22:05:47'),
(13, 5, 5, 'Z1780097658844', 7000.00, '', NULL, NULL, '2026-05-29 23:34:18'),
(14, 5, 5, 'Z1780097713683', 7000.00, '', NULL, NULL, '2026-05-29 23:35:13'),
(15, 5, 4, 'Z1780223656239', 900.00, 'na_datu', '2026-06-22', NULL, '2026-05-31 10:34:16'),
(16, 5, 4, 'Z1780223670755', 950.00, 'na_datu', '2026-08-01', NULL, '2026-05-31 10:34:30'),
(17, 5, 4, 'Z1780262737996', 900.00, 'na_datu', '2026-07-22', NULL, '2026-05-31 21:25:37'),
(18, 6, 1, 'Z1780430556505', 900.00, 'na_datu', '2026-06-22', NULL, '2026-06-02 20:02:36'),
(19, 5, 1, 'Z1780430581727', 950.00, 'na_datu', '2026-06-23', NULL, '2026-06-02 20:03:01');

-- --------------------------------------------------------

--
-- Структура таблицы `zakaz_tovary`
--

CREATE TABLE `zakaz_tovary` (
  `id` int(10) UNSIGNED NOT NULL,
  `zakaz_id` int(10) UNSIGNED NOT NULL,
  `tovar_id` int(10) UNSIGNED NOT NULL,
  `kolichestvo` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `cena_na_moment` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `zakaz_tovary`
--

INSERT INTO `zakaz_tovary` (`id`, `zakaz_id`, `tovar_id`, `kolichestvo`, `cena_na_moment`) VALUES
(3, 3, 2, 1, 1000.00),
(4, 3, 1, 3, 1000.00),
(5, 4, 1, 2, 1000.00),
(6, 5, 2, 5, 1000.00),
(10, 7, 8, 1, 200.00),
(11, 7, 1, 1, 3500.00),
(12, 7, 2, 1, 650.00),
(13, 8, 7, 1, 3000.00),
(14, 9, 8, 1, 200.00),
(15, 10, 6, 1, 2500.00),
(16, 11, 7, 1, 3000.00),
(17, 11, 8, 1, 200.00),
(18, 12, 8, 7, 200.00),
(19, 14, 1, 2, 3500.00),
(20, 15, 4, 1, 900.00),
(21, 16, 1, 1, 950.00),
(22, 17, 4, 1, 900.00),
(23, 18, 4, 1, 900.00),
(24, 19, 1, 1, 950.00);

-- --------------------------------------------------------

--
-- Структура таблицы `zayavki`
--

CREATE TABLE `zayavki` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `zakaz_id` int(10) UNSIGNED DEFAULT NULL,
  `handler_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `message` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `zayavki`
--

INSERT INTO `zayavki` (`id`, `user_id`, `zakaz_id`, `handler_id`, `name`, `phone`, `message`, `status`, `created_at`) VALUES
(12, NULL, NULL, NULL, 'Артем', '+79082514449', '123', 'new', '2026-05-14 10:22:27'),
(13, NULL, NULL, NULL, 'Павел', '+79642960544', '11111', 'new', '2026-05-16 13:07:02'),
(14, NULL, NULL, NULL, 'Тест', 'тест', 'тест', 'new', '2026-05-16 13:10:24'),
(15, NULL, NULL, NULL, 'Артем', '+79082514449', 'hey', 'new', '2026-05-20 17:55:43'),
(16, NULL, NULL, NULL, 'Артем', '+79642960544', 'Привет!', 'new', '2026-05-26 21:58:02');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `chat_soobscheniya`
--
ALTER TABLE `chat_soobscheniya`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ot` (`ot_user_id`),
  ADD KEY `idx_komu` (`komu_user_id`);

--
-- Индексы таблицы `dostavka`
--
ALTER TABLE `dostavka`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dostavka_zakaz` (`zakaz_id`);

--
-- Индексы таблицы `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_password_reset_user` (`user_id`);

--
-- Индексы таблицы `polzovateli`
--
ALTER TABLE `polzovateli`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_polzovateli_telefon` (`telefon`),
  ADD UNIQUE KEY `uq_polzovateli_email` (`email`),
  ADD KEY `idx_polzovateli_role` (`role_id`);

--
-- Индексы таблицы `roli`
--
ALTER TABLE `roli`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_roli_nazvanie` (`nazvanie`);

--
-- Индексы таблицы `statusy_zakazov`
--
ALTER TABLE `statusy_zakazov`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_statusy_zakazov_nazvanie` (`nazvanie`);

--
-- Индексы таблицы `tovary`
--
ALTER TABLE `tovary`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tovary_aktiven` (`aktiven`);

--
-- Индексы таблицы `zakazy`
--
ALTER TABLE `zakazy`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_zakazy_nomer` (`nomer_zakaza`),
  ADD KEY `idx_zakazy_user` (`user_id`),
  ADD KEY `idx_zakazy_status` (`status_id`);

--
-- Индексы таблицы `zakaz_tovary`
--
ALTER TABLE `zakaz_tovary`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_zakaz_tovary_zakaz` (`zakaz_id`),
  ADD KEY `idx_zakaz_tovary_tovar` (`tovar_id`);

--
-- Индексы таблицы `zayavki`
--
ALTER TABLE `zayavki`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_zayavki_user` (`user_id`),
  ADD KEY `idx_zayavki_zakaz` (`zakaz_id`),
  ADD KEY `idx_zayavki_handler` (`handler_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `chat_soobscheniya`
--
ALTER TABLE `chat_soobscheniya`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT для таблицы `dostavka`
--
ALTER TABLE `dostavka`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT для таблицы `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `polzovateli`
--
ALTER TABLE `polzovateli`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `roli`
--
ALTER TABLE `roli`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `statusy_zakazov`
--
ALTER TABLE `statusy_zakazov`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `tovary`
--
ALTER TABLE `tovary`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT для таблицы `zakazy`
--
ALTER TABLE `zakazy`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT для таблицы `zakaz_tovary`
--
ALTER TABLE `zakaz_tovary`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT для таблицы `zayavki`
--
ALTER TABLE `zayavki`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `chat_soobscheniya`
--
ALTER TABLE `chat_soobscheniya`
  ADD CONSTRAINT `fk_chat_komu_user` FOREIGN KEY (`komu_user_id`) REFERENCES `polzovateli` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_chat_ot_user` FOREIGN KEY (`ot_user_id`) REFERENCES `polzovateli` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `dostavka`
--
ALTER TABLE `dostavka`
  ADD CONSTRAINT `fk_dostavka_zakaz` FOREIGN KEY (`zakaz_id`) REFERENCES `zakazy` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  ADD CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `polzovateli` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `polzovateli`
--
ALTER TABLE `polzovateli`
  ADD CONSTRAINT `fk_polzovateli_roli` FOREIGN KEY (`role_id`) REFERENCES `roli` (`id`) ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `zakazy`
--
ALTER TABLE `zakazy`
  ADD CONSTRAINT `fk_zakazy_status` FOREIGN KEY (`status_id`) REFERENCES `statusy_zakazov` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_zakazy_user` FOREIGN KEY (`user_id`) REFERENCES `polzovateli` (`id`) ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `zakaz_tovary`
--
ALTER TABLE `zakaz_tovary`
  ADD CONSTRAINT `fk_zakaz_tovary_tovar` FOREIGN KEY (`tovar_id`) REFERENCES `tovary` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_zakaz_tovary_zakaz` FOREIGN KEY (`zakaz_id`) REFERENCES `zakazy` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `zayavki`
--
ALTER TABLE `zayavki`
  ADD CONSTRAINT `fk_zayavki_handler` FOREIGN KEY (`handler_id`) REFERENCES `polzovateli` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_zayavki_user` FOREIGN KEY (`user_id`) REFERENCES `polzovateli` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_zayavki_zakaz` FOREIGN KEY (`zakaz_id`) REFERENCES `zakazy` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
