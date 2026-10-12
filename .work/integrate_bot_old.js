const fs = require('fs');
const path = require('path');
const source = 'D:/xampp/htdocs/hfm_admin/.work/bot_old.before-integration.js';
const workspace = 'D:/xampp/htdocs/hfm_admin/bot_old.js';
const backup = 'D:/xampp/htdocs/hfm_admin/.work/bot_old.before-integration.js';
let text = fs.readFileSync(source, 'utf8');
// Base file is the preserved original backup.
text = text.replace('const { execFile } = require("child_process");', 'const { execFile } = require("child_process");\nconst crypto = require("crypto");');
text = text.replace('const vipNotified = new Set();', 'const vipNotified = new Set();\nlet bulkKickLepasIbRunning = false;');
text = text.replace('async function askGemini(prompt, userName) {', 'async function askGemini(prompt, userName, currentStep = 1) {');
const marker = 'const delay = (ms) => new Promise((res) => setTimeout(res, ms));\n';
const helpers = `
function hasValidControlToken(req) {
  const expected = String(botSettings.globals.BOT_CONTROL_TOKEN || '').trim();
  const supplied = String(req.get('x-bot-control-token') || req.body?.token || '').trim();
  return !!expected && supplied.length === expected.length && crypto.timingSafeEqual(Buffer.from(supplied), Buffer.from(expected));
}
function requireControlToken(req, res) {
  if (!hasValidControlToken(req)) { res.status(401).json({ status: 'failed', error: 'Unauthorized.' }); return false; }
  return true;
}
async function getTelegramGroupConfig() {
  try {
    const [rows] = await db.query("SELECT group_id, group_name, invite_link FROM bot_group_links WHERE platform = 'telegram' AND is_active = 1 LIMIT 1");
    if (rows.length && String(rows[0].group_id || '').trim()) return { groupId: String(rows[0].group_id).trim(), groupName: rows[0].group_name || 'Grup Telegram VIP', inviteLink: rows[0].invite_link || '' };
  } catch (_) {}
  return { groupId: String(botSettings.globals.ID_GRUP_VIP || '').trim(), groupName: botSettings.globals.NAMA_GRUP_VIP || 'Grup Telegram VIP', inviteLink: botSettings.globals.LINK_GRUP_VIP || '' };
}
async function saveTelegramGroupMembership(groupId, groupName, member, status) {
  try {
    const telegramId = String(member.id);
    const displayName = [member.first_name, member.last_name].filter(Boolean).join(' ').slice(0, 150) || member.username || 'Pengguna Telegram';
    const [vipRows] = await db.query('SELECT no_wa FROM tb_member_vip WHERE id_telegram = ? LIMIT 1', [telegramId]);
    await db.query(
      \`INSERT INTO telegram_group_members (telegram_id, phone_number, display_name, group_id, group_name, status, joined_at, left_at)
       VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
       ON DUPLICATE KEY UPDATE phone_number = VALUES(phone_number), display_name = VALUES(display_name), group_name = VALUES(group_name), status = VALUES(status), joined_at = IF(VALUES(status) = 'active', NOW(), joined_at), left_at = IF(VALUES(status) = 'left', NOW(), NULL), updated_at = NOW()\`,
      [telegramId, vipRows[0]?.no_wa || null, displayName, String(groupId), groupName || null, status, status === 'left' ? new Date() : null]);
  } catch (error) { console.error('[TELEGRAM GROUP MEMBER ERROR]', error.message); }
}
async function sendValidatedGroupWelcome(ctx, member, groupId) {
  try {
    const userId = String(member.id);
    const check = await axios.get(\`http://103.89.4.144/bot_wa/client/check_status_vip/\${encodeURIComponent(userId)}\`, { timeout: 5000 });
    if (check.data?.is_vip !== true) return;
    const [previous] = await db.query('SELECT status FROM telegram_group_members WHERE telegram_id = ? AND group_id = ? LIMIT 1', [userId, String(groupId)]);
    const returning = previous[0]?.status === 'left';
    const fallback = returning ? 'Selamat Datang Kembali {NAMA_USER}, pastikan membaca rules yang ada di dalam group, pahami dan lakukan.' : 'Selamat Datang {NAMA_USER}, pastikan membaca rules yang ada di dalam group, pahami dan lakukan.';
    const message = replaceTags(botSettings.globals[returning ? 'WELCOME_BACK_MESSAGE' : 'WELCOME_MESSAGE'] || fallback, member.first_name || member.username || 'Kak');
    await ctx.reply(message, { disable_web_page_preview: true });
    await saveChatLog(userId, 'bot', message);
  } catch (error) { console.error('[WELCOME GROUP ERROR]', error.message); }
}
`;
if (!text.includes(marker)) throw new Error('delay marker not found');
text = text.replace(marker, marker + helpers);
const restart = `  const rawToken =
    botSettings.globals.BOT_CONTROL_TOKEN || "token_rahasia_bot_123";
  const configuredToken = String(rawToken).trim();

  if (!req.body || req.body.token !== configuredToken) {
    return res.status(403).json({ error: "Token restart tidak valid." });
  }
`;
text = text.replace(restart, '  if (!requireControlToken(req, res)) return;\n');
const report = `  const configuredToken = String(botSettings.globals.BOT_CONTROL_TOKEN || "");
  if (!configuredToken || req.body?.token !== configuredToken) {
    return res.status(403).json({ error: "Token laporan tidak valid." });
  }
`;
text = text.replace(report, '  if (!requireControlToken(req, res)) return;\n');
text = text.replace('  const { id_telegram } = req.body;\n  const ID_GRUP_VIP = botSettings.globals.ID_GRUP_VIP;', '  if (!requireControlToken(req, res)) return;\n  const { id_telegram } = req.body;\n  const ID_GRUP_VIP = botSettings.globals.ID_GRUP_VIP;');
const bulk = `
app.post('/api/kick-lepas-ib-members', async (req, res) => {
  await fetchConfig();
  if (!requireControlToken(req, res)) return;
  if (!bot) return res.status(503).json({ status: 'failed', error: 'Bot Telegram belum terhubung.' });
  if (bulkKickLepasIbRunning) return res.status(409).json({ status: 'failed', error: 'Proses kick massal sedang berjalan.' });
  bulkKickLepasIbRunning = true;
  try {
    const group = await getTelegramGroupConfig();
    if (!group.groupId) return res.status(409).json({ status: 'failed', error: 'ID grup Telegram belum dikonfigurasi.' });
    const [candidates] = await db.query('SELECT DISTINCT TRIM(id_telegram) AS telegram_id FROM tb_member_vip WHERE status = ' + String.fromCharCode(39) + 'lepas_ib' + String.fromCharCode(39) + ' AND id_telegram IS NOT NULL AND TRIM(id_telegram) REGEXP ' + String.fromCharCode(39) + '^[0-9]+ status: 'success', group_name: group.groupName, candidates: candidates.length, kicked: 0, skipped: 0, failed: 0, failed_ids: [] };
    for (const row of candidates) {
      const id = String(row.telegram_id);
      try {
        const member = await bot.telegram.getChatMember(group.groupId, id);
        if (['left','kicked','creator','administrator'].includes(member.status) || (member.status === 'restricted' && member.is_member === false)) { result.skipped++; continue; }
        if (!['member','restricted'].includes(member.status)) { result.skipped++; continue; }
        await bot.telegram.banChatMember(group.groupId, id); await bot.telegram.unbanChatMember(group.groupId, id);
        await saveTelegramGroupMembership(group.groupId, group.groupName, member.user || { id }, 'left'); result.kicked++;
      } catch (error) { result.failed++; if (result.failed_ids.length < 20) result.failed_ids.push(id); console.error('[BULK KICK]', id, error.message); }
      await delay(300);
    }
    return res.json(result);
  } catch (error) { console.error('[BULK KICK ERROR]', error.message); return res.status(502).json({ status: 'failed', error: 'Gagal menjalankan kick massal.' }); }
  finally { bulkKickLepasIbRunning = false; }
});
`;
text = text.replace('// ENDPOINT UNTUK RESET MEMORI USER (DARI WEB ADMIN)', bulk + '\n// ENDPOINT UNTUK RESET MEMORI USER (DARI WEB ADMIN)');
const handlers = `    bot.on('new_chat_members', async (ctx) => {
      const group = await getTelegramGroupConfig();
      if (!group.groupId || String(ctx.chat.id) !== group.groupId) return;
      for (const member of ctx.message.new_chat_members || []) {
        if (member.is_bot) continue;
        await sendValidatedGroupWelcome(ctx, member, group.groupId);
        await saveTelegramGroupMembership(group.groupId, group.groupName || ctx.chat.title, member, 'active');
      }
    });

`;
text = text.replace('    bot.on("left_chat_member", async (ctx) => {', handlers + '    bot.on("left_chat_member", async (ctx) => {');
const left = `      if (ctx.chat.id.toString() === ID_GRUP_VIP) {
        try {
          await axios.get(`;
const leftNew = `      if (ctx.chat.id.toString() === ID_GRUP_VIP) {
        const leftMember = ctx.message.left_chat_member;
        if (leftMember && !leftMember.is_bot) await saveTelegramGroupMembership(ID_GRUP_VIP, botSettings.globals.NAMA_GRUP_VIP || ctx.chat.title, leftMember, 'left');
        try {
          await axios.get(`;
text = text.replace(left, leftNew);
fs.writeFileSync(workspace, text);
fs.mkdirSync('D:/xampp/htdocs/hfm_admin/sql', { recursive: true });
fs.writeFileSync('D:/xampp/htdocs/hfm_admin/sql/bot_old_features_safe.sql', `-- Fitur tambahan bot_old.js; aman dijalankan berulang dan tidak menghapus data.\nCREATE TABLE IF NOT EXISTS telegram_group_members (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, telegram_id VARCHAR(50) NOT NULL, phone_number VARCHAR(30) DEFAULT NULL, display_name VARCHAR(150) DEFAULT NULL, group_id VARCHAR(100) NOT NULL, group_name VARCHAR(150) DEFAULT NULL, status VARCHAR(20) NOT NULL DEFAULT 'active', joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, left_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (id), UNIQUE KEY uq_telegram_group_member_identity (telegram_id, group_id), KEY idx_telegram_group_member_phone (phone_number), KEY idx_telegram_group_member_status (status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;\nINSERT INTO bot_globals (key_name,key_value) SELECT 'WELCOME_MESSAGE','Selamat Datang {NAMA_USER}, pastikan membaca rules yang ada di dalam group, pahami dan lakukan.' WHERE NOT EXISTS (SELECT 1 FROM bot_globals WHERE key_name='WELCOME_MESSAGE');\nINSERT INTO bot_globals (key_name,key_value) SELECT 'WELCOME_BACK_MESSAGE','Selamat Datang Kembali {NAMA_USER}, pastikan membaca rules yang ada di dalam group, pahami dan lakukan.' WHERE NOT EXISTS (SELECT 1 FROM bot_globals WHERE key_name='WELCOME_BACK_MESSAGE');\n`);
console.log('Integrated bot_old.js and wrote SQL.');



 + String.fromCharCode(39) + ' UNION SELECT DISTINCT TRIM(id_telegram) AS telegram_id FROM tb_member_logs WHERE tipe_aktivitas = ' + String.fromCharCode(39) + 'lepas_ib' + String.fromCharCode(39) + ' AND id_telegram IS NOT NULL AND TRIM(id_telegram) REGEXP ' + String.fromCharCode(39) + '^[0-9]+ status: 'success', group_name: group.groupName, candidates: candidates.length, kicked: 0, skipped: 0, failed: 0, failed_ids: [] };
    for (const row of candidates) {
      const id = String(row.telegram_id);
      try {
        const member = await bot.telegram.getChatMember(group.groupId, id);
        if (['left','kicked','creator','administrator'].includes(member.status) || (member.status === 'restricted' && member.is_member === false)) { result.skipped++; continue; }
        if (!['member','restricted'].includes(member.status)) { result.skipped++; continue; }
        await bot.telegram.banChatMember(group.groupId, id); await bot.telegram.unbanChatMember(group.groupId, id);
        await saveTelegramGroupMembership(group.groupId, group.groupName, member.user || { id }, 'left'); result.kicked++;
      } catch (error) { result.failed++; if (result.failed_ids.length < 20) result.failed_ids.push(id); console.error('[BULK KICK]', id, error.message); }
      await delay(300);
    }
    return res.json(result);
  } catch (error) { console.error('[BULK KICK ERROR]', error.message); return res.status(502).json({ status: 'failed', error: 'Gagal menjalankan kick massal.' }); }
  finally { bulkKickLepasIbRunning = false; }
});
`;
text = text.replace('// ENDPOINT UNTUK RESET MEMORI USER (DARI WEB ADMIN)', bulk + '\n// ENDPOINT UNTUK RESET MEMORI USER (DARI WEB ADMIN)');
const handlers = `    bot.on('new_chat_members', async (ctx) => {
      const group = await getTelegramGroupConfig();
      if (!group.groupId || String(ctx.chat.id) !== group.groupId) return;
      for (const member of ctx.message.new_chat_members || []) {
        if (member.is_bot) continue;
        await sendValidatedGroupWelcome(ctx, member, group.groupId);
        await saveTelegramGroupMembership(group.groupId, group.groupName || ctx.chat.title, member, 'active');
      }
    });

`;
text = text.replace('    bot.on("left_chat_member", async (ctx) => {', handlers + '    bot.on("left_chat_member", async (ctx) => {');
const left = `      if (ctx.chat.id.toString() === ID_GRUP_VIP) {
        try {
          await axios.get(`;
const leftNew = `      if (ctx.chat.id.toString() === ID_GRUP_VIP) {
        const leftMember = ctx.message.left_chat_member;
        if (leftMember && !leftMember.is_bot) await saveTelegramGroupMembership(ID_GRUP_VIP, botSettings.globals.NAMA_GRUP_VIP || ctx.chat.title, leftMember, 'left');
        try {
          await axios.get(`;
text = text.replace(left, leftNew);
fs.writeFileSync(workspace, text);
fs.mkdirSync('D:/xampp/htdocs/hfm_admin/sql', { recursive: true });
fs.writeFileSync('D:/xampp/htdocs/hfm_admin/sql/bot_old_features_safe.sql', `-- Fitur tambahan bot_old.js; aman dijalankan berulang dan tidak menghapus data.\nCREATE TABLE IF NOT EXISTS telegram_group_members (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, telegram_id VARCHAR(50) NOT NULL, phone_number VARCHAR(30) DEFAULT NULL, display_name VARCHAR(150) DEFAULT NULL, group_id VARCHAR(100) NOT NULL, group_name VARCHAR(150) DEFAULT NULL, status VARCHAR(20) NOT NULL DEFAULT 'active', joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, left_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (id), UNIQUE KEY uq_telegram_group_member_identity (telegram_id, group_id), KEY idx_telegram_group_member_phone (phone_number), KEY idx_telegram_group_member_status (status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;\nINSERT INTO bot_globals (key_name,key_value) SELECT 'WELCOME_MESSAGE','Selamat Datang {NAMA_USER}, pastikan membaca rules yang ada di dalam group, pahami dan lakukan.' WHERE NOT EXISTS (SELECT 1 FROM bot_globals WHERE key_name='WELCOME_MESSAGE');\nINSERT INTO bot_globals (key_name,key_value) SELECT 'WELCOME_BACK_MESSAGE','Selamat Datang Kembali {NAMA_USER}, pastikan membaca rules yang ada di dalam group, pahami dan lakukan.' WHERE NOT EXISTS (SELECT 1 FROM bot_globals WHERE key_name='WELCOME_BACK_MESSAGE');\n`);
console.log('Integrated bot_old.js and wrote SQL.');



 + String.fromCharCode(39) + '' );
    const result = { status: 'success', group_name: group.groupName, candidates: candidates.length, kicked: 0, skipped: 0, failed: 0, failed_ids: [] };
    for (const row of candidates) {
      const id = String(row.telegram_id);
      try {
        const member = await bot.telegram.getChatMember(group.groupId, id);
        if (['left','kicked','creator','administrator'].includes(member.status) || (member.status === 'restricted' && member.is_member === false)) { result.skipped++; continue; }
        if (!['member','restricted'].includes(member.status)) { result.skipped++; continue; }
        await bot.telegram.banChatMember(group.groupId, id); await bot.telegram.unbanChatMember(group.groupId, id);
        await saveTelegramGroupMembership(group.groupId, group.groupName, member.user || { id }, 'left'); result.kicked++;
      } catch (error) { result.failed++; if (result.failed_ids.length < 20) result.failed_ids.push(id); console.error('[BULK KICK]', id, error.message); }
      await delay(300);
    }
    return res.json(result);
  } catch (error) { console.error('[BULK KICK ERROR]', error.message); return res.status(502).json({ status: 'failed', error: 'Gagal menjalankan kick massal.' }); }
  finally { bulkKickLepasIbRunning = false; }
});
`;
text = text.replace('// ENDPOINT UNTUK RESET MEMORI USER (DARI WEB ADMIN)', bulk + '\n// ENDPOINT UNTUK RESET MEMORI USER (DARI WEB ADMIN)');
const handlers = `    bot.on('new_chat_members', async (ctx) => {
      const group = await getTelegramGroupConfig();
      if (!group.groupId || String(ctx.chat.id) !== group.groupId) return;
      for (const member of ctx.message.new_chat_members || []) {
        if (member.is_bot) continue;
        await sendValidatedGroupWelcome(ctx, member, group.groupId);
        await saveTelegramGroupMembership(group.groupId, group.groupName || ctx.chat.title, member, 'active');
      }
    });

`;
text = text.replace('    bot.on("left_chat_member", async (ctx) => {', handlers + '    bot.on("left_chat_member", async (ctx) => {');
const left = `      if (ctx.chat.id.toString() === ID_GRUP_VIP) {
        try {
          await axios.get(`;
const leftNew = `      if (ctx.chat.id.toString() === ID_GRUP_VIP) {
        const leftMember = ctx.message.left_chat_member;
        if (leftMember && !leftMember.is_bot) await saveTelegramGroupMembership(ID_GRUP_VIP, botSettings.globals.NAMA_GRUP_VIP || ctx.chat.title, leftMember, 'left');
        try {
          await axios.get(`;
text = text.replace(left, leftNew);
fs.writeFileSync(workspace, text);
fs.mkdirSync('D:/xampp/htdocs/hfm_admin/sql', { recursive: true });
fs.writeFileSync('D:/xampp/htdocs/hfm_admin/sql/bot_old_features_safe.sql', `-- Fitur tambahan bot_old.js; aman dijalankan berulang dan tidak menghapus data.\nCREATE TABLE IF NOT EXISTS telegram_group_members (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, telegram_id VARCHAR(50) NOT NULL, phone_number VARCHAR(30) DEFAULT NULL, display_name VARCHAR(150) DEFAULT NULL, group_id VARCHAR(100) NOT NULL, group_name VARCHAR(150) DEFAULT NULL, status VARCHAR(20) NOT NULL DEFAULT 'active', joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, left_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (id), UNIQUE KEY uq_telegram_group_member_identity (telegram_id, group_id), KEY idx_telegram_group_member_phone (phone_number), KEY idx_telegram_group_member_status (status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;\nINSERT INTO bot_globals (key_name,key_value) SELECT 'WELCOME_MESSAGE','Selamat Datang {NAMA_USER}, pastikan membaca rules yang ada di dalam group, pahami dan lakukan.' WHERE NOT EXISTS (SELECT 1 FROM bot_globals WHERE key_name='WELCOME_MESSAGE');\nINSERT INTO bot_globals (key_name,key_value) SELECT 'WELCOME_BACK_MESSAGE','Selamat Datang Kembali {NAMA_USER}, pastikan membaca rules yang ada di dalam group, pahami dan lakukan.' WHERE NOT EXISTS (SELECT 1 FROM bot_globals WHERE key_name='WELCOME_BACK_MESSAGE');\n`);
console.log('Integrated bot_old.js and wrote SQL.');



