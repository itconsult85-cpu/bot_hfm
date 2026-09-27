-- Security hardening for the HFM Telegram bot.
-- This does not change table structure or business/member data.
--
-- IMPORTANT:
-- 1. In phpMyAdmin, click/select the target CI4 database in the left sidebar first.
-- 2. Then run this entire file. Do not run only the INSERT while no database is selected.
--
-- CLI example (replace db_hfm_bot with the database in your CI4 .env):
-- mysql -u <user> -p db_hfm_bot < sql/security_hardening.sql
--
-- INSERT IGNORE preserves an existing BOT_CONTROL_TOKEN. If it does not exist,
-- MySQL creates a random token. The SELECT at the end displays the active token
-- so it can be used by the Node process/dashboard configuration if required.

INSERT IGNORE INTO `bot_globals` (`key_name`, `key_value`)
VALUES ('BOT_CONTROL_TOKEN', CONCAT('hfm_', HEX(RANDOM_BYTES(32))));

SELECT `key_name`, `key_value`
FROM `bot_globals`
WHERE `key_name` = 'BOT_CONTROL_TOKEN';

-- The following values belong in the server environment, not in this SQL file:
-- HFM_API_KEY=<HFM partner API key>
-- DB_HOST=<database host>
-- DB_USER=<database user>
-- DB_PASSWORD=<database password>
-- DB_NAME=<database name>
-- CI_BASE_URL=https://<your-ci4-host>/bot_wa
