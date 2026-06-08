-- =========================================================
-- Патч: привязка таблицы zayavki к пользователям
-- Запустите в phpMyAdmin или через mysql-клиент
-- =========================================================

-- 1. Удалить пустые/мусорные заявки (id 1, 9, 10, 11 — пустые поля)
DELETE FROM `zayavki` WHERE `id` IN (1, 9, 10, 11);

-- 2. Добавить колонку user_id (nullable — заявка может быть от незарегистрированного)
ALTER TABLE `zayavki`
  ADD COLUMN `user_id` int(10) UNSIGNED DEFAULT NULL AFTER `id`;

-- 3. Добавить внешний ключ на polzovateli
ALTER TABLE `zayavki`
  ADD KEY `idx_zayavki_user` (`user_id`),
  ADD CONSTRAINT `fk_zayavki_user`
    FOREIGN KEY (`user_id`) REFERENCES `polzovateli` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE;

-- 4. Добавить колонку для привязки к заказу (опционально, полезно для связи)
ALTER TABLE `zayavki`
  ADD COLUMN `zakaz_id` int(10) UNSIGNED DEFAULT NULL AFTER `user_id`,
  ADD KEY `idx_zayavki_zakaz` (`zakaz_id`),
  ADD CONSTRAINT `fk_zayavki_zakaz`
    FOREIGN KEY (`zakaz_id`) REFERENCES `zakazy` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE;

-- 5. Добавить колонку обработчика (кто из сотрудников/админов взял заявку)
ALTER TABLE `zayavki`
  ADD COLUMN `handler_id` int(10) UNSIGNED DEFAULT NULL AFTER `zakaz_id`,
  ADD KEY `idx_zayavki_handler` (`handler_id`),
  ADD CONSTRAINT `fk_zayavki_handler`
    FOREIGN KEY (`handler_id`) REFERENCES `polzovateli` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE;

-- =========================================================
-- После этого патча таблица zayavki будет связана с:
--   polzovateli (user_id)   — кто оставил заявку
--   zakazy      (zakaz_id)  — к какому заказу относится
--   polzovateli (handler_id)— кто обрабатывает заявку
-- =========================================================
