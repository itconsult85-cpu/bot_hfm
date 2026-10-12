const fs = require("node:fs");
const path = require("node:path");
const vm = require("node:vm");

function isAdminHelpKeyword(message) {
  const words = String(message || "").toLocaleLowerCase("id").match(/[\p{L}\p{N}]+/gu) || [];
  return words.some((word, index) => words.slice(index, index + 3).join(" ") === "tolong saya admin");
}

for (const [input, expected] of [
  ["tolong saya admin", true],
  ["TOLONG, saya ADMIN!", true],
  ["saya mau tolong saya admin ya", true],
  ["tolong admin", false],
]) {
  if (isAdminHelpKeyword(input) !== expected) throw new Error(`Unexpected keyword result for: ${input}`);
}

const root = path.join(__dirname, "..", "..");
const bot = fs.readFileSync(path.join(root, "bot.js"), "utf8");
const baseline = fs.readFileSync("D:\\bot_stable_fixed.js", "utf8");
const view = fs.readFileSync(path.join(root, "app", "Views", "admin_help_sessions.php"), "utf8");
const controller = fs.readFileSync(path.join(root, "app", "Controllers", "AdminDashboard.php"), "utf8");
const docs = fs.readFileSync(path.join(root, "docs", "telegram-bot-setup.md"), "utf8");
const progressController = fs.readFileSync(path.join(root, "app", "Controllers", "UserProgress.php"), "utf8");
const progressView = fs.readFileSync(path.join(root, "app", "Views", "user_progress", "index.php"), "utf8");
const routes = fs.readFileSync(path.join(root, "app", "Config", "Routes.php"), "utf8");

const resetStartMarker = 'app.post("/reset-user", async (req, res) => {';
const resetEndMarker = "// NYALAKAN SERVER EXPRESS AGAR BISA MENERIMA REQUEST DARI WEB PHP";
const stateMarker = "// 3. MEMORI LOCAL & STATE GLOBAL";
const baselineResetStart = baseline.indexOf(resetStartMarker);
const baselineResetEnd = baseline.indexOf(resetEndMarker, baselineResetStart);
const baselineStateMarker = baseline.indexOf(stateMarker);
const botResetStart = bot.indexOf(resetStartMarker);
const botResetEnd = bot.indexOf(resetEndMarker, botResetStart);
const botStateMarker = bot.indexOf(stateMarker);
if (baselineResetStart < 0 || botResetStart < 0 ||
    baseline.slice(0, baselineResetStart) !== bot.slice(0, botResetStart) ||
    baseline.slice(baselineResetEnd, baselineStateMarker) !== bot.slice(botResetEnd, botStateMarker)) {
  throw new Error("The stable startup and database configuration must remain unchanged outside the reset API.");
}
if (bot.includes("CI4_ENV_PATH") || bot.includes("override: true")) {
  throw new Error("A forced CI4 environment loader must not be added.");
}
const mojibakeMarkers = [
  String.fromCodePoint(0x00e2, 0x0153),
  String.fromCodePoint(0x00e2, 0x0161),
  String.fromCodePoint(0x00e2, 0x20ac),
  String.fromCodePoint(0x00e2, 0x009d),
  String.fromCodePoint(0x00e2, 0x008f),
  String.fromCodePoint(0x00f0, 0x0178),
  String.fromCodePoint(0x00ef, 0x00b8),
];
if (mojibakeMarkers.some(marker => bot.includes(marker))) {
  throw new Error("Mojibake emoji text must be restored before sending bot messages.");
}
if (!bot.includes('finalMsg = `✅ *[ADMIN INFO]*') ||
    !bot.includes('finalMsg = `✅ **Selamat kak ${userName}!**')) {
  throw new Error("The trading validation messages must use a valid checkmark emoji.");
}

const noRelay = ["adminAssistedUsers", "activeAdminTargetId", "/selesai", "/balas", "forwardMessage(", "sendMessage(targetId"];
if (noRelay.some(token => bot.includes(token))) {
  throw new Error("A relay command, session, or admin-to-user forwarding path remains.");
}
if (!bot.includes("Kendala pada bagian: ") ||
    !bot.includes("Keterangan user terakhir: ") ||
    !bot.includes("sendMessage(adminId, report") ||
    !bot.includes("sendPhoto(adminId, fileId") ||
    !bot.includes('text: "📨 Buka chat user"') ||
    !bot.includes('text: "🆘 Tolong saya admin"') ||
    bot.includes('text: "' + String.fromCodePoint(0x00f0, 0x0178, 0x2020, 0x02dc) + ' Tolong saya admin"') ||
    bot.includes("sendDocument(")) {
  throw new Error("The admin report must contain the issue stage, relevant user note, user photos, and no full chat dump.");
}
if (!bot.includes("Info kendala kakak sudah dikirim satu kali ke admin") ||
    !bot.includes("Admin akan menghubungi kakak langsung melalui Telegram")) {
  throw new Error("The user confirmation must say the admin will contact them directly.");
}

const vipCheck = bot.indexOf("const statusUrl = \"http://103.89.4.144/bot_wa/client/check_status_vip/\"");
const helpTrigger = bot.indexOf("if (!isAdmin && isAdminHelpKeyword(textMessage))");
if (vipCheck < 0 || helpTrigger < 0 || vipCheck > helpTrigger ||
    !bot.includes("validatedUsers.add(String(userId))") ||
    !bot.includes("validatedUsers.add(userKey)") ||
    !bot.includes("reply_markup: { remove_keyboard: true }")) {
  throw new Error("Validated/invited users must be restricted before help, with their reply keyboard removed.");
}
if (!bot.includes('headers: { "X-Bot-Control-Token": controlToken }') ||
    !bot.includes("if (!controlToken) throw new Error(\"BOT_CONTROL_TOKEN belum dikonfigurasi pada bot.\")")) {
  throw new Error("The VIP status request must authenticate to CI4 to avoid HTTP 401.");
}
const vipFlow = bot.slice(vipCheck, helpTrigger);
const vipCacheClear = vipFlow.indexOf("validatedUsers.delete(userKey)");
const vipStatusCatch = vipFlow.indexOf("} catch (statusError)");
if (vipCacheClear < 0 || vipStatusCatch < 0 || vipCacheClear > vipStatusCatch ||
    vipFlow.includes("VIP HISTORY CHECK ERROR") ||
    vipFlow.includes("SELECT 1 FROM chat_logs_tele WHERE phone_number = ?") ||
    !vipFlow.includes("Pemeriksaan status VIP gagal; pendaftaran ditunda.")) {
  throw new Error("A non-VIP status must clear stale memory; old invite logs must not block re-registration.");
}
const resetEndpointStart = bot.indexOf(resetStartMarker);
const resetEndpointEnd = bot.indexOf(resetEndMarker, resetEndpointStart);
const resetEndpoint = bot.slice(resetEndpointStart, resetEndpointEnd);
if (!resetEndpoint.includes("hasValidControlToken(req)") ||
    !resetEndpoint.includes("validatedUsers.delete(userId)") ||
    !resetEndpoint.includes("userTimeouts[userId]")) {
  throw new Error("The internal bot reset endpoint must authenticate and clear volatile user state.");
}
if (!bot.includes("ctx.reply(suksesMsg, {") ||
    !bot.includes("reply_markup: { remove_keyboard: true },")) {
  throw new Error("The successful validation/invite message must remove the help keyboard.");
}

if (!controller.includes("chat_logs_tele AS help_log") ||
    !controller.includes("Riwayat chat kakak sudah dikirim satu kali ke admin") ||
    !controller.includes("MAX(help_log.created_at) AS requestedAt") ||
    controller.includes("Bot belum dapat dihubungi")) {
  throw new Error("CI4 must load persisted help requests directly from the database.");
}
if (!view.includes("count($requests)") || !view.includes("tg://user?id=")) {
  throw new Error("The admin request page must offer direct contact to the Telegram user.");
}
if (!docs.includes("admin") || docs.includes("/selesai") || docs.includes("/balas")) {
  throw new Error("The bot setup documentation must not describe the old relay commands.");
}
if (!routes.includes("UserProgress::resetProgress") ||
    !progressController.includes("X-Bot-Control-Token") ||
    !progressController.includes("current_step" ) ||
    !progressView.includes("user-progress/reset") ||
    !progressView.includes("csrf_field()")) {
  throw new Error("The progress dashboard must provide an authenticated, CSRF-protected reset that also clears bot RAM.");
}

async function verifyOneWayReport() {
  const start = bot.indexOf("async function sendAdminHelpReport(");
  const end = bot.indexOf("\nasync function logUserProgress(", start);
  if (start < 0 || end <= start) throw new Error("Could not extract the report function for its mock test.");

  const outgoingMessages = [];
  const outgoingPhotos = [];
  const userReplies = [];
  const savedLogs = [];
  const legacyButtonText = String.fromCodePoint(0x00f0, 0x0178, 0x2020, 0x02dc) + " Tolong saya admin";
  const context = {
    botSettings: { globals: { ID_ADMIN: "9001" } },
    bot: { telegram: {
      sendMessage: async (...args) => outgoingMessages.push(args),
      sendPhoto: async (...args) => outgoingPhotos.push(args),
    } },
    db: { query: async () => [[
      { message: legacyButtonText },
      { message: "gagal unggah screenshot\\n[TELEGRAM_FILE_ID:test-photo-001]" },
    ]] },
    userTimeouts: {},
    saveChatLog: async (...args) => savedLogs.push(args),
    console: { error() {}, warn() {} },
  };
  vm.runInNewContext(bot.slice(start, end) + "\nglobalThis.reportFn = sendAdminHelpReport;", context);
  const ctx = {
    from: { first_name: "User Uji", username: "useruji" },
    message: { text: "🆘 Tolong saya admin" },
    reply: async message => userReplies.push(message),
  };
  const ok = await context.reportFn(ctx, "12345", "User Uji", 3);
  const report = outgoingMessages[0]?.[1] || "";
  if (!ok || outgoingMessages.length !== 1 || outgoingPhotos.length !== 1 ||
      outgoingPhotos[0][0] !== "9001" || outgoingPhotos[0][1] !== "test-photo-001" ||
      !report.includes("Kendala pada bagian: Screenshot akun") ||
      !report.includes("Keterangan user terakhir: gagal unggah screenshot") ||
      report.includes("tolong saya admin") ||
      outgoingMessages[0][2]?.reply_markup?.inline_keyboard?.[0]?.[0]?.url !== "tg://user?id=12345" ||
      !userReplies[0]?.includes("Admin akan menghubungi kakak langsung") ||
      !savedLogs.some(entry => String(entry[2]).includes("Riwayat chat kakak sudah dikirim satu kali ke admin"))) {
    throw new Error("Mock report did not send the expected direct-contact summary/photo-only payload.");
  }
}

verifyOneWayReport()
  .then(() => console.log("Stable baseline preserved; one-way help summary/photos, no relay commands, and post-invite restriction checks passed."))
  .catch(error => { console.error(error.message); process.exitCode = 1; });
