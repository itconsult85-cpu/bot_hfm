-- Security hardening for the HFM Telegram bot.
-- This does not change table structure or business/member data.
-- Replace the placeholder only on the server with a long random secret.
-- INSERT IGNORE preserves an existing BOT_CONTROL_TOKEN.

INSERT IGNORE INTO `bot_globals` (`key_name`, `key_value`)
VALUES ('BOT_CONTROL_TOKEN', 'REPLACE_WITH_A_LONG_RANDOM_SERVER_SECRET');

-- The following values belong in the server environment, not in this SQL file:
-- HFM_API_KEY=<HFM partner API key>
-- DB_HOST=<database host>
-- DB_USER=<database user>
-- DB_PASSWORD=<database password>
-- DB_NAME=<database name>
-- CI_BASE_URL=https://<your-ci4-host>/bot_wa
