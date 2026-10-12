const fs = require("node:fs");
const path = require("node:path");

const root = path.resolve(__dirname, "..", "..");
const stablePath = path.join(root, "bot_stable.js");
const outputPath = path.join(root, "bot.js");
let source = fs.readFileSync(stablePath, "utf8");
const newline = source.includes("\r\n") ? "\r\n" : "\n";
source = source.replace(/\r\n/g, "\n");

function replaceOnce(search, replacement, label) {
  const first = source.indexOf(search);
  if (first < 0 || source.indexOf(search, first + search.length) >= 0) {
    throw new Error(`Expected exactly one matching block for ${label}.`);
  }
  source = source.slice(0, first) + replacement + source.slice(first + search.length);
}

replaceOnce(
  'const { Telegraf } = require("telegraf");',
  [
    'const fs = require("fs");',
    'const path = require("path");',
    'const ci4EnvPath = process.env.CI4_ENV_PATH || (',
    '  fs.existsSync("/var/www/hfm_admin/.env")',
    '    ? "/var/www/hfm_admin/.env"',
    '    : path.join(__dirname, ".env")',
    ');',
    'const envLoad = require("dotenv").config({ path: ci4EnvPath, override: true });',
    'if (envLoad.error) console.error("[CONFIG] Gagal membaca .env CodeIgniter:", envLoad.error.message);',
    'const { Telegraf } = require("telegraf");',
  ].join("\n"),
  "environment loader",
);
replaceOnce('const cors = require("cors");\n', "", "undeclared cors dependency");
replaceOnce("app.use(cors());\n", "", "cors middleware");
replaceOnce("const API_PORT = 3000;", "const API_PORT = Number(process.env.API_PORT || 3000);", "API_PORT environment setting");
replaceOnce("app.listen(API_PORT, () => {", 'app.listen(API_PORT, "127.0.0.1", () => {', "local-only control API listener");

const databaseBlock = /\/\/ 2\. KONEKSI DATABASE MYSQL\nconst db = mysql\.createPool\(\{[\s\S]*?\n\}\);/;
if (!databaseBlock.test(source)) throw new Error("Stable database pool block was not found.");
source = source.replace(databaseBlock, [
  "// 2. KONEKSI DATABASE MYSQL",
  "const configValue = (keys, defaultValue = \"\") => {",
  "  for (const key of keys) {",
  "    const value = process.env[key];",
  "    if (value != null && String(value).trim() !== \"\") return value;",
  "  }",
  "  return defaultValue;",
  "};",
  "const db = mysql.createPool({",
  '  host: configValue(["BOT_DB_HOST", "DB_HOST", "database.default.hostname"], "127.0.0.1"),',
  '  port: Number(configValue(["BOT_DB_PORT", "DB_PORT", "database.default.port"], "3306")),',
  '  user: configValue(["BOT_DB_USER", "DB_USER", "database.default.username"]),',
  '  password: configValue(["BOT_DB_PASSWORD", "DB_PASSWORD", "database.default.password"]),',
  '  database: configValue(["BOT_DB_NAME", "DB_NAME", "database.default.database"], "db_hfm_bot"),',
  "  waitForConnections: true,",
  "  connectionLimit: 10,",
  "  queueLimit: 0,",
  "});",
].join("\n"));

replaceOnce(
  [
    '  const rawToken =',
    '    botSettings.globals.BOT_CONTROL_TOKEN || "token_rahasia_bot_123";',
    '  const configuredToken = String(rawToken).trim();',
    '',
    '  if (!req.body || req.body.token !== configuredToken) {',
    '    return res.status(403).json({ error: "Token restart tidak valid." });',
    '  }',
  ].join("\n"),
  '  if (!hasValidControlToken(req)) {\n    return res.status(401).json({ status: "failed", error: "Unauthorized." });\n  }',
  "restart API token fallback",
);
replaceOnce(
  [
    '  const configuredToken = String(botSettings.globals.BOT_CONTROL_TOKEN || "");',
    '  if (!configuredToken || req.body?.token !== configuredToken) {',
    '    return res.status(403).json({ error: "Token laporan tidak valid." });',
    '  }',
  ].join("\n"),
  '  if (!hasValidControlToken(req)) {\n    return res.status(401).json({ status: "failed", error: "Unauthorized." });\n  }',
  "daily report API token comparison",
);
replaceOnce(
  "let activeAdminTargetId = null;\n",
  "",
  "admin-to-user relay target state",
);

const adminApiAnchor = "async function hasDatabaseTable(tableName) {";
replaceOnce(
  adminApiAnchor,
  [
    "function requireControlToken(req, res) {",
    '  if (!hasValidControlToken(req)) {',
    '    res.status(401).json({ status: "failed", error: "Unauthorized." });',
    "    return false;",
    "  }",
    "  return true;",
    "}",
    "",
    'app.get("/api/admin-handoffs", (req, res) => {',
    "  if (!requireControlToken(req, res)) return;",
    "  const sessions = [...adminAssistedUsers.values()]",
    "    .sort((first, second) => second.startedAt - first.startedAt);",
    '  return res.json({ status: "success", count: sessions.length, sessions });',
    "});",
    "",
    adminApiAnchor,
  ].join("\n"),
  "authenticated admin-handoffs API",
);

const activateStart = source.indexOf("function activateAdminAssistedSession(userId) {");
const reportStart = source.indexOf("async function sendAdminHelpReport(ctx, userId, userName, currentStep) {");
if (activateStart < 0 || reportStart <= activateStart) throw new Error("Admin session helper block could not be located.");
source = source.slice(0, activateStart) + source.slice(reportStart);

source = source.replace(/^  const caption = `.*`;$/m, "  const caption = `User meminta bantuan pendaftaran\\nNama: ${fullName}\\nUsername: ${username}\\nID Telegram: ${userId}\\nTahap: ${currentStep}\\nHubungi user secara langsung melalui Telegram. Untuk menghentikan penerusan pesan lanjutan ke admin, kirim /selesai ${userId} ke bot.`;");
source = source.replace(/^  const confirmation =\n    .*;$/m, '  const confirmation =\n    "Riwayat chat kakak sudah diteruskan ke admin. Admin akan menghubungi kakak secara langsung melalui Telegram. Pesan berikutnya akan diteruskan ke admin sampai penerusan dihentikan.";');
if (!source.includes("Hubungi user secara langsung melalui Telegram.")) throw new Error("The admin report caption was not updated.");
if (!source.includes("Pesan berikutnya akan diteruskan ke admin sampai penerusan dihentikan.")) throw new Error("The user confirmation was not updated.");

const doneStart = source.indexOf("if (isAdmin && /^\\/selesai(?:\\s|$)/i.test(textMessage.trim())) {");
const balasStart = source.indexOf("if (isAdmin && /^\\/balas(?:\\s|$)/i.test(textMessage.trim())) {");
if (doneStart < 0 || balasStart <= doneStart) throw new Error("Admin /selesai and /balas handlers could not be located.");
const doneLineStart = source.lastIndexOf("\n", doneStart) + 1;
const balasLineStart = source.lastIndexOf("\n", balasStart) + 1;
source = source.slice(0, doneLineStart) + [
  "          if (isAdmin && /^\\/selesai(?:\\s|$)/i.test(textMessage.trim())) {",
  "            const targetId = textMessage.trim().split(/\\s+/)[1] || \"\";",
  "            if (!/^\\d+$/.test(targetId)) {",
  '              await ctx.reply("Format: /selesai ID_USER\\nContoh: /selesai 123456789");',
  "              return;",
  "            }",
  "            adminAssistedUsers.delete(targetId);",
  "            await ctx.reply(`Penerusan pesan untuk ${targetId} dihentikan. Pesan ini tidak dikirim ke user.`);",
  "            return;",
  "          }",
  "",
].join("\n") + source.slice(balasLineStart);

const balasNeedle = "if (isAdmin && /^\\/balas(?:\\s|$)/i.test(textMessage.trim())) {";
const balasStartAfterDone = source.indexOf(balasNeedle, doneLineStart);
const activeRelayNeedle = "if (\n            isAdmin &&\n            activeAdminTargetId &&";
const activeRelayStart = source.indexOf(activeRelayNeedle, balasStartAfterDone);
if (balasStartAfterDone < 0 || activeRelayStart <= balasStartAfterDone) throw new Error("Admin reply relay block could not be located.");
const balasLineAfterDone = source.lastIndexOf("\n", balasStartAfterDone) + 1;
const activeRelayLineStart = source.lastIndexOf("\n", activeRelayStart) + 1;
source = source.slice(0, balasLineAfterDone) + [
  "          if (isAdmin && /^\\/balas(?:\\s|$)/i.test(textMessage.trim())) {",
  '            await ctx.reply("Fitur balas user melalui bot sudah dinonaktifkan. Silakan hubungi user secara langsung di Telegram menggunakan ID atau username pada laporan.");',
  "            return;",
  "          }",
  "",
].join("\n") + source.slice(activeRelayLineStart);

const adminKeywordNeedle = "if (!isAdmin && isAdminHelpKeyword(textMessage)) {";
const relayStillStart = source.indexOf(activeRelayNeedle);
const keywordStart = source.indexOf(adminKeywordNeedle, relayStillStart);
if (relayStillStart < 0 || keywordStart <= relayStillStart) throw new Error("Admin manual relay block could not be located for removal.");
const relayLineStart = source.lastIndexOf("\n", relayStillStart) + 1;
const keywordLineStart = source.lastIndexOf("\n", keywordStart) + 1;
source = source.slice(0, relayLineStart) + source.slice(keywordLineStart);

replaceOnce(
  '"Permintaan bantuan kakak sudah diteruskan. Admin akan membalas melalui chat ini.";',
  '"Permintaan bantuan kakak sudah diteruskan. Admin akan menghubungi kakak secara langsung melalui Telegram.";',
  "repeat help request confirmation",
);

if (/token_rahasia_bot_123|password\s*:\s*["'][^"']+["']/.test(source)) {
  throw new Error("A hard-coded token or database password remains in the generated source.");
}
if (/activeAdminTargetId|sendMessage\(\s*targetId|forwardMessage\(\s*targetId/.test(source)) {
  throw new Error("An admin-to-user relay path remains in the generated source.");
}
if (!source.includes('app.get("/api/admin-handoffs"')) throw new Error("The authenticated admin-handoffs API is missing.");

fs.writeFileSync(outputPath, source.replace(/\n/g, newline), "utf8");
console.log("bot.js rebuilt from bot_stable.js: support-only relay removal and secure DB environment config applied.");
