const fs = require("node:fs");
const path = require("node:path");

const workspace = path.resolve(__dirname, "..", "..");
const baselinePath = "D:\\bot_stable_fixed.js";
const outputPath = path.join(workspace, "bot.js");
if (!fs.existsSync(baselinePath)) throw new Error("Stable baseline is missing: D:\\bot_stable_fixed.js");

const original = fs.readFileSync(baselinePath, "utf8");
const newline = original.includes("\r\n") ? "\r\n" : "\n";
const baselineSource = original.replace(/\r\n/g, "\n");
let source = baselineSource;
const stateMarker = "// 3. MEMORI LOCAL & STATE GLOBAL";
const resetStartMarker = 'app.post("/reset-user", async (req, res) => {';
const resetEndMarker = "// NYALAKAN SERVER EXPRESS AGAR BISA MENERIMA REQUEST DARI WEB PHP";
const baselineResetStart = baselineSource.indexOf(resetStartMarker);
const baselineResetEnd = baselineSource.indexOf(resetEndMarker, baselineResetStart);
const baselineStateMarker = baselineSource.indexOf(stateMarker);
if (baselineResetStart < 0 || baselineResetEnd <= baselineResetStart || baselineStateMarker <= baselineResetEnd) {
  throw new Error("Could not locate the reset endpoint and stable state boundary in the baseline.");
}
const reportFunction = fs.readFileSync(path.join(__dirname, "admin_help_report.snippet"), "utf8").trim();
const supportHandler = fs.readFileSync(path.join(__dirname, "admin_support_handler.snippet"), "utf8").trim();

function replaceSection(startMarker, endMarker, replacement, label) {
  const start = source.indexOf(startMarker);
  const end = source.indexOf(endMarker, start + startMarker.length);
  if (start < 0 || end <= start) throw new Error(`Could not find section: ${label}`);
  source = source.slice(0, start) + replacement + "\n\n" + source.slice(end);
}

const relayState = "const adminAssistedUsers = new Map();\nlet activeAdminTargetId = null;";
if (!source.includes(relayState)) throw new Error("Expected admin relay state was not found in baseline.");
source = source.replace(relayState, "const validatedUsers = new Set();");
const oldHelpButton = 'keyboard: [[{ text: "' + String.fromCodePoint(0x00f0, 0x0178, 0x2020, 0x02dc) + ' Tolong saya admin" }]],';
if (!source.includes(oldHelpButton)) throw new Error("Could not locate the corrupted help button label in the baseline.");
source = source.replace(oldHelpButton, 'keyboard: [[{ text: "🆘 Tolong saya admin" }]],');

replaceSection(
  resetStartMarker,
  resetEndMarker,
  `app.post("/reset-user", async (req, res) => {
  if (!hasValidControlToken(req)) {
    return res.status(403).json({ error: "Token bot tidak valid." });
  }

  const userId = String(req.body?.user_id || "").trim();
  if (!/^\\d{5,20}$/.test(userId)) {
    return res.status(400).json({ error: "ID Telegram tidak valid." });
  }

  try {
    delete userSteps[userId];
    delete userTempData[userId];
    delete userImagesCount[userId];
    delete userMessages[userId];
    vipNotified.delete(userId);
    validatedUsers.delete(userId);

    if (userTimeouts[userId]) {
      clearTimeout(userTimeouts[userId].timeout);
      delete userTimeouts[userId];
    }

    console.log("[BOT API] State progres user " + userId + " berhasil di-reset.");
    return res.json({ status: "success", message: "State progres bot berhasil di-reset." });
  } catch (error) {
    console.error("[BOT API ERROR] Gagal reset progres:", error.message);
    return res.status(500).json({ error: "Gagal mereset state bot." });
  }
});`,
  "protected reset-user API",
);

replaceSection(
  "function activateAdminAssistedSession(",
  "async function logUserProgress(",
  reportFunction,
  "admin relay helper and report function",
);

replaceSection(
  "// FITUR ADMIN: AMBIL ALIH CHAT (BALAS MANUAL)",
  "// 1.5 PENANGANAN FORMULIR & ID TRADING (AUTO DETEKSI DARI STEP MANA PUN)",
  supportHandler,
  "admin relay and VIP/help handling",
);

const formSuccessMarker = `await saveChatLog(
                  userId,
                  "bot",
                  "Berhasil registrasi form dan kirim link.",
                );

                userSteps[userId] = 7;`;
if (!source.includes(formSuccessMarker)) throw new Error("Could not locate the successful form/invite block.");
source = source.replace(
  formSuccessMarker,
  formSuccessMarker.replace("\n\n                userSteps[userId] = 7;", "\n                validatedUsers.add(String(userId));\n                userSteps[userId] = 7;"),
);

const oldFormSuccessMessage = `const sentMsg = await ctx.telegram.editMessageText(
                  ctx.chat.id,
                  loadFormMsg.message_id,
                  undefined,
                  suksesMsg,
                );`;
if (!source.includes(oldFormSuccessMessage)) throw new Error("Could not locate the form success message to remove the help keyboard.");
source = source.replace(oldFormSuccessMessage, `await bot.telegram.deleteMessage(ctx.chat.id, loadFormMsg.message_id).catch(() => {});
                const sentMsg = await ctx.reply(suksesMsg, {
                  reply_markup: { remove_keyboard: true },
                });`);

const cp1252Special = new Map([
  [0x20ac, 0x80], [0x201a, 0x82], [0x0192, 0x83], [0x201e, 0x84], [0x2026, 0x85],
  [0x2020, 0x86], [0x2021, 0x87], [0x02c6, 0x88], [0x2030, 0x89], [0x0160, 0x8a],
  [0x2039, 0x8b], [0x0152, 0x8c], [0x017d, 0x8e], [0x2018, 0x91], [0x2019, 0x92],
  [0x201c, 0x93], [0x201d, 0x94], [0x2022, 0x95], [0x2013, 0x96], [0x2014, 0x97],
  [0x02dc, 0x98], [0x2122, 0x99], [0x0161, 0x9a], [0x203a, 0x9b], [0x0153, 0x9c],
  [0x017e, 0x9e], [0x0178, 0x9f],
]);
const decoder = new TextDecoder("utf-8", { fatal: true });
let repairedMojibakeTokens = 0;
source = source.replace(/\u00e2\u0161\u00a0\u00ef\u00b8\u008f/g, () => {
  repairedMojibakeTokens += 1;
  return "⚠️";
});
source = source.replace(/\S+/g, token => {
  const bytes = [];
  for (const character of token) {
    const codePoint = character.codePointAt(0);
    if (codePoint <= 0x7f || (codePoint >= 0x80 && codePoint <= 0x9f) || (codePoint >= 0xa0 && codePoint <= 0xff)) {
      bytes.push(codePoint);
    } else if (cp1252Special.has(codePoint)) {
      bytes.push(cp1252Special.get(codePoint));
    } else {
      return token;
    }
  }
  try {
    const decoded = decoder.decode(Uint8Array.from(bytes));
    if (decoded !== token && /[\u0080-\uFFFF]/.test(decoded)) {
      repairedMojibakeTokens += 1;
      return decoded;
    }
  } catch (error) {}
  return token;
});

const currentResetStart = source.indexOf(resetStartMarker);
const currentResetEnd = source.indexOf(resetEndMarker, currentResetStart);
const currentStateMarker = source.indexOf(stateMarker);
if (source.slice(0, currentResetStart) !== baselineSource.slice(0, baselineResetStart) ||
    source.slice(currentResetEnd, currentStateMarker) !== baselineSource.slice(baselineResetEnd, baselineStateMarker)) {
  throw new Error("Unexpected startup, database, or API changes occurred outside the reset endpoint.");
}
for (const token of ["adminAssistedUsers", "activeAdminTargetId", "/selesai", "/balas", "forwardMessage("]) {
  if (source.includes(token)) throw new Error(`Legacy relay code remains: ${token}`);
}
if (source.includes("CI4_ENV_PATH") || source.includes("override: true")) throw new Error("Do not add a forced CI4 environment loader.");
if (!source.includes("validatedUsers.add(String(userId))")) throw new Error("Invite completion gate was not added.");

fs.writeFileSync(outputPath, source.replace(/\n/g, newline), "utf8");
console.log(`Built bot.js from bot_stable_fixed.js; repaired ${repairedMojibakeTokens} mojibake text tokens and applied help, VIP auth, and protected reset changes.`);
