from pathlib import Path
import shutil

source = Path(r'D:\Aplikasi Web\bot_old.js')
workspace = Path(r'D:\xampp\htdocs\hfm_admin\bot_old.js')
backup = Path(r'D:\Aplikasi Web\bot_old.before-integration.js')
text = source.read_text(encoding='utf-8')
if not backup.exists():
    shutil.copy2(source, backup)

text = text.replace('const { execFile } = require("child_process");', 'const { execFile } = require("child_process");\nconst crypto = require("crypto");', 1)
text = text.replace('const vipNotified = new Set();', 'const vipNotified = new Set();\nlet bulkKickLepasIbRunning = false;', 1)
text = text.replace('async function askGemini(prompt, userName) {', 'async function askGemini(prompt, userName, currentStep = 1) {', 1)

marker = 'const delay = (ms) => new Promise((res) => setTimeout(res, ms));\n'
helpers = r'''

function hasValidControlToken(req) {
  const expected = String(botSettings.globals.BOT_CONTROL_TOKEN || "").trim();
  const supplied = String(req.get("x-bot-control-token") || req.body?.token || "").trim();
  if (!expected || supplied.length !== expected.length) return false;
  return crypto.timingSafeEqual(Buffer.from(supplied), Buffer.from(expected));
}

function requireControlToken(req, res) {
  if (!hasValidControlToken(req)) {
    res.status(401).json({ status: "failed", error: "Unauthorized." });
    return false;
  }
  return true;
}

async function getTelegramGroupConfig() {
  try {
    const [rows] = await db.query(
      "SELECT group_id, group_name, invite_link FROM bot_group_links WHERE platform = 'telegram' AND is_active = 1 LIMIT 1",
    );
    if (rows.length && String(rows[0].group_id || "").trim()) {
      return {
        groupId: String(rows[0].group_id).trim(),
        groupName: rows[0].group_name || "Grup Telegram VIP",
        inviteLink: rows[0].invite_link || "",
      };
    }
  } catch (error) {
    // Tabel bot_group_links bersifat tambahan; fallback ke konfigurasi lama.
  }
  return {
    groupId: String(botSettings.globals.ID_GRUP_VIP || "").trim(),
    groupName: botSettings.globals.NAMA_GRUP_VIP || "Grup Telegram VIP",
    inviteLink: botSettings.globals.LINK_GRUP_VIP || "",
  };
}

async function saveTelegramGroupMembership(groupId, groupName, member, status) {
  try {
    const telegramId = String(member.id);
    const displayName = [member.first_name, member.last_name]
      .filter(Boolean).join(" ").slice(0, 150) || member.username || "Pengguna Telegram";
    const [vipRows] = await db.query(
      "SELECT no_wa FROM tb_member_vip WHERE id_telegram = ? LIMIT 1",
      [telegramId],
    );
    const phoneNumber = vipRows[0]?.no_wa || null;
    await db.query(
      `INSERT INTO telegram_group_members
        (telegram_id, phone_number, display_name, group_id, group_name, status, joined_at, left_at)
       VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
       ON DUPLICATE KEY UPDATE
        phone_number = VALUES(phone_number), display_name = VALUES(display_name),
        group_name = VALUES(group_name), status = VALUES(status),
        joined_at = IF(VALUES(status) = 'active', NOW(), joined_at),
        left_at = IF(VALUES(status) = 'left', NOW(), NULL), updated_at = NOW()`,
      [telegramId, phoneNumber, displayName, String(groupId), groupName || null, status,
        status === "left" ? new Date() : null],
    );
  } catch (error) {
    console.error("[TELEGRAM GROUP MEMBER ERROR]", error.message);
  }
}

async function sendValidatedGroupWelcome(ctx, member, groupId) {
  try {
    const userId = String(member.id);
    const statusResponse = await axios.get(
      `http://103.89.4.144/bot_wa/client/check_status_vip/${encodeURIComponent(userId)}`,
      { timeout: 5000 },
    );
    if (statusResponse.data?.is_vip !== true) return;

    const [previousRows] = await db.query(
      `SELECT status FROM telegram_group_members
       WHERE telegram_id = ? AND group_id = ? LIMIT 1`,
      [userId, String(groupId)],
    );
    const returning = previousRows[0]?.status === "left";
    const template = returning
      ? botSettings.globals.WELCOME_BACK_MESSAGE
      : botSettings.globals.WELCOME_MESSAGE;
    const fallback = returning
      ? "Selamat Datang Kembali {NAMA_USER}, pastikan membaca rules yang ada di dalam group, pahami dan lakukan."
      : "Selamat Datang {NAMA_USER}, pastikan membaca rules yang ada di dalam group, pahami dan lakukan.";
    const message = replaceTags(template || fallback, member.first_name || member.username || "Kak");
    await ctx.reply(message, { disable_web_page_preview: true });
    await saveChatLog(userId, "bot", message);
  } catch (error) {
    console.error("[WELCOME GROUP ERROR]", error.message);
  }
}
'''
if marker not in text:
    raise SystemExit('delay marker not found')
text = text.replace(marker, marker + helpers, 1)

# Harden existing control endpoints without changing their public paths.
old = '''  const rawToken =
    botSettings.globals.BOT_CONTROL_TOKEN || "token_rahasia_bot_123";
  const configuredToken = String(rawToken).trim();

  if (!req.body || req.body.token !== configuredToken) {
    return res.status(403).json({ error: "Token restart tidak valid." });
  }
'''
new = '''  if (!requireControlToken(req, res)) return;
'''
if old not in text: raise SystemExit('restart auth block not found')
text = text.replace(old, new, 1)
old = '''  const configuredToken = String(botSettings.globals.BOT_CONTROL_TOKEN || "");
  if (!configuredToken || req.body?.token !== configuredToken) {
    return res.status(403).json({ error: "Token laporan tidak valid." });
  }
'''
if old not in text: raise SystemExit('report auth block not found')
text = text.replace(old, '  if (!requireControlToken(req, res)) return;\n', 1)
text = text.replace('''  const { id_telegram } = req.body;
  const ID_GRUP_VIP = botSettings.globals.ID_GRUP_VIP;
''', '''  if (!requireControlToken(req, res)) return;
  const { id_telegram } = req.body;
  const ID_GRUP_VIP = botSettings.globals.ID_GRUP_VIP;
''', 1)

bulk_marker = '// ENDPOINT UNTUK RESET MEMORI USER (DARI WEB ADMIN)'
bulk_route = r'''
// ENDPOINT KICK MASSAL MEMBER LEPAS IB, dipanggil dashboard CI4.
app.post("/api/kick-lepas-ib-members", async (req, res) => {
  await fetchConfig();
  if (!requireControlToken(req, res)) return;
  if (!bot) return res.status(503).json({ status: "failed", error: "Bot Telegram belum terhubung." });
  if (bulkKickLepasIbRunning) return res.status(409).json({ status: "failed", error: "Proses kick massal sedang berjalan." });

  bulkKickLepasIbRunning = true;
  try {
    const group = await getTelegramGroupConfig();
    if (!group.groupId) return res.status(409).json({ status: "failed", error: "ID grup Telegram belum dikonfigurasi." });
    const [candidates] = await db.query(
      `SELECT DISTINCT TRIM(id_telegram) AS telegram_id FROM tb_member_vip
       WHERE status = 'lepas_ib' AND id_telegram IS NOT NULL
       AND TRIM(id_telegram) REGEXP '^[0-9]+$'
       UNION SELECT DISTINCT TRIM(id_telegram) AS telegram_id FROM tb_member_logs
       WHERE tipe_aktivitas = 'lepas_ib' AND id_telegram IS NOT NULL
       AND TRIM(id_telegram) REGEXP '^[0-9]+$'`,
    );
    const result = { status: "success", group_name: group.groupName, candidates: candidates.length, kicked: 0, skipped: 0, failed: 0, failed_ids: [] };
    for (const candidate of candidates) {
      const telegramId = String(candidate.telegram_id);
      try {
        const member = await bot.telegram.getChatMember(group.groupId, telegramId);
        if (["left", "kicked", "creator", "administrator"].includes(member.status) ||
            (member.status === "restricted" && member.is_member === false)) {
          result.skipped += 1;
          if (member.user && ["left", "kicked"].includes(member.status)) await saveTelegramGroupMembership(group.groupId, group.groupName, member.user, "left");
          continue;
        }
        if (!["member", "restricted"].includes(member.status)) { result.skipped += 1; continue; }
        await bot.telegram.banChatMember(group.groupId, telegramId);
        await bot.telegram.unbanChatMember(group.groupId, telegramId);
        await saveTelegramGroupMembership(group.groupId, group.groupName, member.user || { id: telegramId }, "left");
        result.kicked += 1;
      } catch (error) {
        result.failed += 1;
        if (result.failed_ids.length < 20) result.failed_ids.push(telegramId);
        console.error(`[BULK KICK] Gagal memproses ${telegramId}:`, error.message);
      }
      await delay(300);
    }
    return res.json(result);
  } catch (error) {
    console.error("[BULK KICK ERROR]", error.message);
    return res.status(502).json({ status: "failed", error: "Gagal menjalankan kick massal." });
  } finally {
    bulkKickLepasIbRunning = false;
  }
});

'''
if bulk_marker not in text: raise SystemExit('bulk marker not found')
text = text.replace(bulk_marker, bulk_route + bulk_marker, 1)

# Add event listeners before the old left-member handler.
handler_marker = '    bot.on("left_chat_member", async (ctx) => {'
handlers = r'''    bot.on("new_chat_members", async (ctx) => {
      const group = await getTelegramGroupConfig();
      if (!group.groupId || String(ctx.chat.id) !== group.groupId) return;
      for (const member of ctx.message.new_chat_members || []) {
        if (member.is_bot) continue;
        await sendValidatedGroupWelcome(ctx, member, group.groupId);
        await saveTelegramGroupMembership(group.groupId, group.groupName || ctx.chat.title, member, "active");
      }
    });

'''
if handler_marker not in text: raise SystemExit('left handler marker not found')
text = text.replace(handler_marker, handlers + handler_marker, 1)

# Make old left event also record membership, while preserving its existing CI4 status update.
needle = '''      if (ctx.chat.id.toString() === ID_GRUP_VIP) {
        try {
          await axios.get(
'''
replacement = '''      if (ctx.chat.id.toString() === ID_GRUP_VIP) {
        const leftMember = ctx.message.left_chat_member;
        if (leftMember && !leftMember.is_bot) {
          await saveTelegramGroupMembership(ID_GRUP_VIP, botSettings.globals.NAMA_GRUP_VIP || ctx.chat.title, leftMember, "left");
        }
        try {
          await axios.get(
'''
if needle not in text: raise SystemExit('left body marker not found')
text = text.replace(needle, replacement, 1)

workspace.write_text(text, encoding='utf-8', newline='\n')
source.write_text(text, encoding='utf-8', newline='\n')

sql = Path(r'D:\xampp\htdocs\hfm_admin\sql\bot_old_features_safe.sql')
sql.write_text('''-- Fitur tambahan bot_old.js: pencatatan anggota Telegram dan welcome tervalidasi.\n-- Aman dijalankan berulang: tidak menghapus/mengubah data existing.\n\nCREATE TABLE IF NOT EXISTS `telegram_group_members` (\n  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,\n  `telegram_id` VARCHAR(50) NOT NULL,\n  `phone_number` VARCHAR(30) DEFAULT NULL,\n  `display_name` VARCHAR(150) DEFAULT NULL,\n  `group_id` VARCHAR(100) NOT NULL,\n  `group_name` VARCHAR(150) DEFAULT NULL,\n  `status` VARCHAR(20) NOT NULL DEFAULT 'active',\n  `joined_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,\n  `left_at` DATETIME DEFAULT NULL,\n  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,\n  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n  PRIMARY KEY (`id`),\n  UNIQUE KEY `uq_telegram_group_member_identity` (`telegram_id`, `group_id`),\n  KEY `idx_telegram_group_member_phone` (`phone_number`),\n  KEY `idx_telegram_group_member_status` (`status`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;\n\nINSERT INTO `bot_globals` (`key_name`, `key_value`)\nSELECT 'WELCOME_MESSAGE', 'Selamat Datang {NAMA_USER}, pastikan membaca rules yang ada di dalam group, pahami dan lakukan.'\nWHERE NOT EXISTS (SELECT 1 FROM `bot_globals` WHERE `key_name` = 'WELCOME_MESSAGE');\n\nINSERT INTO `bot_globals` (`key_name`, `key_value`)\nSELECT 'WELCOME_BACK_MESSAGE', 'Selamat Datang Kembali {NAMA_USER}, pastikan membaca rules yang ada di dalam group, pahami dan lakukan.'\nWHERE NOT EXISTS (SELECT 1 FROM `bot_globals` WHERE `key_name` = 'WELCOME_BACK_MESSAGE');\n''', encoding='utf-8', newline='\n')
print(f'Integrated {workspace} and updated {source}; backup={backup}; sql={sql}')
