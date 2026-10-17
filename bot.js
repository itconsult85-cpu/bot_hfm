const { Telegraf } = require("telegraf");
const mysql = require("mysql2/promise");
const express = require("express");
const cors = require("cors");
const axios = require("axios");
const cron = require("node-cron");
const Tesseract = require("tesseract.js");
const { GoogleGenerativeAI } = require("@google/generative-ai");
const { execFile } = require("child_process");

// 1. EXPRESS & PORT MANAGEMENT
const app = express();
const API_PORT = 3000;
app.use(express.json());
app.use(cors());

app.post("/api/restart-bot", (req, res) => {
  const rawToken =
    botSettings.globals.BOT_CONTROL_TOKEN || "token_rahasia_bot_123";
  const configuredToken = String(rawToken).trim();

  if (!req.body || req.body.token !== configuredToken) {
    return res.status(403).json({ error: "Token restart tidak valid." });
  }

  res.json({ message: "bot_tele_hfm berhasil direstart (PM2 Auto-Restart)." });

  setTimeout(() => {
    console.log(
      "[SYSTEM] Menerima perintah restart dari Web. Mematikan proses untuk memicu PM2 auto-restart...",
    );
    process.exit(1);
  }, 1000);
});

app.post("/api/send-daily-report", async (req, res) => {
  const configuredToken = String(botSettings.globals.BOT_CONTROL_TOKEN || "");
  if (!configuredToken || req.body?.token !== configuredToken) {
    return res.status(403).json({ error: "Token laporan tidak valid." });
  }
  try {
    const result = await runDailyReport({
      skipSync: true,
      source: "dashboard",
    });
    if (!result?.sent) {
      return res.status(502).json({ error: "Laporan tidak berhasil dikirim." });
    }
    return res.json({
      message: "Laporan harian berhasil dikirim berdasarkan database.",
    });
  } catch (error) {
    console.error("[MANUAL REPORT ERROR]", error.message);
    return res
      .status(500)
      .json({ error: "Terjadi kesalahan saat mengirim laporan." });
  }
});

// ENDPOINT UNTUK KICK MEMBER DARI GRUP TELEGRAM
app.post("/kick-telegram", async (req, res) => {
  const { id_telegram } = req.body;
  const ID_GRUP_VIP = botSettings.globals.ID_GRUP_VIP;

  if (!id_telegram || !ID_GRUP_VIP) {
    return res.status(400).json({ error: "ID Telegram atau ID Grup kosong." });
  }

  try {
    // Proses Kick: Ban member lalu langsung di-Unban agar dia terkeluar
    // tapi masih bisa bergabung lagi di masa depan jika di-invite.
    await bot.telegram.banChatMember(ID_GRUP_VIP, parseInt(id_telegram));
    await bot.telegram.unbanChatMember(ID_GRUP_VIP, parseInt(id_telegram));
    await saveTelegramGroupMembership(
      ID_GRUP_VIP,
      "Grup Telegram VIP",
      { id: id_telegram },
      "left",
    );

    console.log(`[BOT API] Berhasil kick Telegram ID: ${id_telegram}`);
    res.json({ status: "success", message: "Berhasil dikick dari grup." });
  } catch (error) {
    console.error(
      `[BOT API ERROR] Gagal kick Telegram ID ${id_telegram}:`,
      error.message,
    );
    res.status(500).json({ error: "Gagal mengeksekusi kick di Telegram." });
  }
});

// ENDPOINT UNTUK RESET MEMORI USER (DARI WEB ADMIN)
app.post("/reset-user", async (req, res) => {
  if (!hasValidControlToken(req)) {
    return res.status(403).json({ error: "Token bot tidak valid." });
  }

  const userId = String(req.body?.user_id || "").trim();
  if (!/^\d{5,20}$/.test(userId)) {
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
});

// NYALAKAN SERVER EXPRESS AGAR BISA MENERIMA REQUEST DARI WEB PHP
app.listen(API_PORT, () => {
  console.log(
    `[EXPRESS] API Server berjalan dan siap menerima request di port ${API_PORT}`,
  );
});

// 2. KONEKSI DATABASE MYSQL
const db = mysql.createPool({
  host: "localhost",
  user: "bonichi",
  password: "Bonichi#2026",
  database: "db_hfm_bot",
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0,
});

// 3. MEMORI LOCAL & STATE GLOBAL
let bot = null;
let genAI = null;
let botStatus = { status: "Disconnected" };
let botSettings = { globals: {}, flows: [], faqs: [] };

const userSteps = {};
const userImagesCount = {};
const userTimeouts = {};
const userMessages = {};
const userTempData = {};
const vipNotified = new Set();
const validatedUsers = new Set();

const antrianBot = [];
let sedangAntriBot = false;
let bulkKickLepasIbRunning = false;
const delay = (ms) => new Promise((res) => setTimeout(res, ms));

function getBotControlToken() {
  return String(botSettings.globals.BOT_CONTROL_TOKEN || "").trim();
}

function hasValidControlToken(req) {
  const expected = Buffer.from(getBotControlToken(), "utf8");
  const supplied = Buffer.from(
    String(req.get("x-bot-control-token") || req.body?.token || "").trim(),
    "utf8",
  );
  return (
    expected.length > 0 &&
    supplied.length === expected.length &&
    require("crypto").timingSafeEqual(supplied, expected)
  );
}

async function hasDatabaseTable(tableName) {
  const [rows] = await db.query(
    "SELECT COUNT(*) AS total FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?",
    [tableName],
  );
  return Number(rows[0]?.total || 0) > 0;
}

async function hasDatabaseColumn(tableName, columnName) {
  const [rows] = await db.query(
    "SELECT COUNT(*) AS total FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?",
    [tableName, columnName],
  );
  return Number(rows[0]?.total || 0) > 0;
}

function getCiBaseUrl() {
  return String(
    process.env.CI_BASE_URL ||
      botSettings.globals.CI_BASE_URL ||
      "http://103.89.4.14/bot_wa",
  ).replace(/\/+$/, "");
}

async function getConfiguredTelegramGroup(allowLegacyFallback = true) {
  const fallbackGroup = {
    group_id: String(botSettings.globals.ID_GRUP_VIP || "").trim(),
    group_name: "Grup Telegram VIP",
    source: "bot_globals.ID_GRUP_VIP",
  };
  const token = getBotControlToken();
  if (token) {
    try {
      const response = await axios.get(
        `${getCiBaseUrl()}/client/apiGroupLinks`,
        { timeout: 10000, headers: { "X-Bot-Control-Token": token } },
      );
      const group = response.data?.links?.telegram;
      if (group?.group_id) return { ...group, source: "CI4 bot_group_links" };
      if (!allowLegacyFallback) return null;
    } catch (error) {
      console.error("[TELEGRAM GROUP CONFIG ERROR]", error.message);
      if (!allowLegacyFallback) return null;
    }
  } else if (!allowLegacyFallback) {
    return null;
  }
  return fallbackGroup.group_id ? fallbackGroup : null;
}

async function saveTelegramGroupMembership(groupId, groupName, member, status) {
  try {
    if (!(await hasDatabaseTable("telegram_group_members"))) return false;
    const telegramId = String(member.id || "").trim();
    if (!telegramId) return false;
    const displayName =
      [member.first_name, member.last_name]
        .filter(Boolean)
        .join(" ")
        .slice(0, 150) ||
      member.username ||
      "Pengguna Telegram";
    let phoneNumber = null;
    const [vipRows] = await db.query(
      "SELECT no_wa FROM tb_member_vip WHERE TRIM(id_telegram) = ? LIMIT 1",
      [telegramId],
    );
    phoneNumber = vipRows[0]?.no_wa || null;
    await db.query(
      `INSERT INTO telegram_group_members
        (telegram_id, phone_number, display_name, group_id, group_name, status, joined_at, left_at)
       VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
       ON DUPLICATE KEY UPDATE
        phone_number = VALUES(phone_number), display_name = VALUES(display_name),
        group_name = VALUES(group_name), status = VALUES(status),
        joined_at = IF(VALUES(status) = 'active', NOW(), joined_at),
        left_at = IF(VALUES(status) = 'left', NOW(), NULL), updated_at = NOW()`,
      [
        telegramId,
        phoneNumber,
        displayName,
        String(groupId),
        groupName || null,
        status,
        status === "left" ? new Date() : null,
      ],
    );
    return true;
  } catch (error) {
    console.error("[TELEGRAM GROUP MEMBER ERROR]", error.message);
    return false;
  }
}

async function hasPreviousTelegramGroupHistory(telegramId, groupId) {
  try {
    if (await hasDatabaseTable("telegram_group_members")) {
      const [membershipRows] = await db.query(
        "SELECT status FROM telegram_group_members WHERE telegram_id = ? AND group_id = ? LIMIT 1",
        [String(telegramId), String(groupId)],
      );
      if (membershipRows[0]?.status === "left") return true;
    }
    if (
      (await hasDatabaseTable("tb_member_logs")) &&
      (await hasDatabaseColumn("tb_member_logs", "id_telegram"))
    ) {
      const [logRows] = await db.query(
        `SELECT 1 FROM tb_member_logs
         WHERE TRIM(id_telegram) = ?
           AND tipe_aktivitas IN ('keluar_sendiri', 'keluar_di_remove', 'dihapus_admin_web', 'lepas_ib', 'keluar_grup')
         LIMIT 1`,
        [String(telegramId)],
      );
      if (logRows.length > 0) return true;
    }
  } catch (error) {
    console.error("[TELEGRAM HISTORY ERROR]", error.message);
  }
  return false;
}

app.get("/api/capabilities", (req, res) => {
  if (!hasValidControlToken(req)) {
    return res.status(401).json({ status: "failed", error: "Unauthorized." });
  }
  return res.json({ status: "success", capabilities: { bulk_kick_ids: true } });
});

app.post("/api/kick-lepas-ib-members", async (req, res) => {
  if (!(await fetchConfig())) {
    return res
      .status(503)
      .json({
        status: "failed",
        error: "Konfigurasi bot tidak dapat dimuat dari database.",
      });
  }
  if (!hasValidControlToken(req)) {
    return res.status(401).json({ status: "failed", error: "Unauthorized." });
  }
  if (!bot)
    return res
      .status(503)
      .json({ status: "failed", error: "Bot Telegram belum terhubung." });
  if (bulkKickLepasIbRunning) {
    return res
      .status(409)
      .json({ status: "failed", error: "Proses kick massal sedang berjalan." });
  }

  bulkKickLepasIbRunning = true;
  try {
    const group = await getConfiguredTelegramGroup(true);
    const groupId = String(group?.group_id || "").trim();
    const groupName =
      String(group?.group_name || "Grup Telegram VIP") +
      (group?.source === "bot_globals.ID_GRUP_VIP"
        ? " (fallback bot_globals)"
        : "");
    if (!groupId) {
      return res
        .status(409)
        .json({
          status: "failed",
          error:
            "ID grup tidak ditemukan. Atur group_id Telegram aktif di CI4 atau isi bot_globals.ID_GRUP_VIP pada database bot.",
        });
    }
    if (group?.source === "bot_globals.ID_GRUP_VIP") {
      console.warn(
        "[BULK KICK] API group-links CI4 tidak tersedia; memakai bot_globals.ID_GRUP_VIP.",
      );
    }

    const hasMembershipTable = await hasDatabaseTable("telegram_group_members");
    const hasLogs =
      (await hasDatabaseTable("tb_member_logs")) &&
      (await hasDatabaseColumn("tb_member_logs", "id_telegram"));
    const notLeftVip = hasMembershipTable
      ? `AND NOT EXISTS (SELECT 1 FROM telegram_group_members m WHERE m.telegram_id = TRIM(v.id_telegram) AND m.group_id = ? AND m.status = 'left')`
      : "";
    const notLeftLogs = hasMembershipTable
      ? `AND NOT EXISTS (SELECT 1 FROM telegram_group_members m WHERE m.telegram_id = TRIM(l.id_telegram) AND m.group_id = ? AND m.status = 'left')`
      : "";
    let candidateSql = `SELECT DISTINCT TRIM(v.id_telegram) AS telegram_id
      FROM tb_member_vip v
      WHERE v.status = 'lepas_ib' AND v.id_telegram IS NOT NULL
        AND TRIM(v.id_telegram) REGEXP '^[0-9]+$' ${notLeftVip}`;
    const queryParams = hasMembershipTable ? [groupId] : [];
    if (hasLogs) {
      candidateSql += ` UNION SELECT TRIM(l.id_telegram) AS telegram_id
        FROM tb_member_logs l
        WHERE l.tipe_aktivitas = 'lepas_ib' AND l.id_telegram IS NOT NULL
          AND TRIM(l.id_telegram) REGEXP '^[0-9]+$' ${notLeftLogs}
          AND NOT EXISTS (SELECT 1 FROM tb_member_logs newer WHERE TRIM(newer.id_telegram) = TRIM(l.id_telegram) AND newer.id > l.id)
          AND NOT EXISTS (SELECT 1 FROM tb_member_vip current_member WHERE TRIM(current_member.id_telegram) = TRIM(l.id_telegram) AND current_member.status = 'aktif')`;
      if (hasMembershipTable) queryParams.push(groupId);
    }
    const [candidates] = await db.query(candidateSql, queryParams);
    const result = {
      status: "success",
      group_name: groupName,
      candidates: candidates.length,
      kicked: 0,
      kicked_ids: [],
      skipped: 0,
      failed: 0,
      failed_ids: [],
    };
    const callTelegramWithRetry = async (action) => {
      let retries = 0;
      while (true) {
        try {
          return await action();
        } catch (error) {
          const retryAfter = Number(
            error.parameters?.retry_after ||
              error.response?.parameters?.retry_after ||
              0,
          );
          if (retryAfter <= 0 || retries >= 3) throw error;
          retries += 1;
          await delay((retryAfter + 1) * 1000);
        }
      }
    };

    for (const candidate of candidates) {
      const telegramId = String(candidate.telegram_id);
      try {
        const [currentRows] = await db.query(
          "SELECT status FROM tb_member_vip WHERE TRIM(id_telegram) = ?",
          [telegramId],
        );
        const hasActiveRecord = currentRows.some(
          (row) => row.status === "aktif",
        );
        let latestLogType = null;
        if (hasLogs) {
          const [latestLogs] = await db.query(
            "SELECT tipe_aktivitas FROM tb_member_logs WHERE TRIM(id_telegram) = ? ORDER BY id DESC LIMIT 1",
            [telegramId],
          );
          latestLogType = latestLogs[0]?.tipe_aktivitas || null;
        }
        let eligible =
          !hasActiveRecord &&
          (latestLogType !== null
            ? latestLogType === "lepas_ib"
            : currentRows.some((row) => row.status === "lepas_ib"));
        if (!eligible) {
          result.skipped += 1;
          continue;
        }

        if (hasMembershipTable) {
          const [leftRows] = await db.query(
            "SELECT 1 FROM telegram_group_members WHERE telegram_id = ? AND group_id = ? AND status = 'left' LIMIT 1",
            [telegramId, groupId],
          );
          if (leftRows.length > 0) {
            result.skipped += 1;
            continue;
          }
        }

        const member = await callTelegramWithRetry(() =>
          bot.telegram.getChatMember(groupId, telegramId),
        );
        if (
          ["left", "kicked", "creator", "administrator"].includes(
            member.status,
          ) ||
          (member.status === "restricted" && member.is_member === false)
        ) {
          if (["left", "kicked"].includes(member.status) && member.user) {
            await saveTelegramGroupMembership(
              groupId,
              groupName,
              member.user,
              "left",
            );
          }
          result.skipped += 1;
          continue;
        }
        if (!["member", "restricted"].includes(member.status)) {
          result.skipped += 1;
          continue;
        }

        let banApplied = false;
        try {
          await callTelegramWithRetry(() =>
            bot.telegram.banChatMember(groupId, telegramId),
          );
          banApplied = true;
          await callTelegramWithRetry(() =>
            bot.telegram.unbanChatMember(groupId, telegramId),
          );
          if (member.user)
            await saveTelegramGroupMembership(
              groupId,
              groupName,
              member.user,
              "left",
            );
          result.kicked += 1;
          result.kicked_ids.push(telegramId);
        } catch (error) {
          if (banApplied) {
            try {
              await callTelegramWithRetry(() =>
                bot.telegram.unbanChatMember(groupId, telegramId),
              );
              if (member.user)
                await saveTelegramGroupMembership(
                  groupId,
                  groupName,
                  member.user,
                  "left",
                );
              result.kicked += 1;
              result.kicked_ids.push(telegramId);
              continue;
            } catch (cleanupError) {
              error.message = `${error.message}; unban pemulihan gagal: ${cleanupError.message}`;
            }
          }
          throw error;
        }
      } catch (error) {
        result.failed += 1;
        if (result.failed_ids.length < 20) result.failed_ids.push(telegramId);
        console.error(
          `[BULK KICK LEPAS IB] Gagal memproses ${telegramId}:`,
          error.message,
        );
      }
      await delay(150);
    }
    return res.json(result);
  } catch (error) {
    console.error("[BULK KICK LEPAS IB ERROR]", error.message);
    return res
      .status(502)
      .json({ status: "failed", error: "Gagal menjalankan kick massal." });
  } finally {
    bulkKickLepasIbRunning = false;
  }
});

// 4. CONFIG LOADER & DATABASE HELPER
async function fetchConfig() {
  try {
    const [globalRows] = await db.query(
      "SELECT key_name, key_value FROM bot_globals ORDER BY id ASC",
    );
    botSettings.globals = {};
    globalRows.forEach((row) => {
      botSettings.globals[row.key_name] = row.key_value;
    });
    const [flowRows] = await db.query(
      "SELECT * FROM bot_flows ORDER BY step_level ASC",
    );
    botSettings.flows = flowRows;
    const [faqRows] = await db.query("SELECT * FROM bot_faqs");
    botSettings.faqs = faqRows;

    if (botSettings.globals.GEMINI_API_KEY) {
      genAI = new GoogleGenerativeAI(botSettings.globals.GEMINI_API_KEY);
    }
    console.log(
      `[SYSTEM] Sync DB Sukses. ${flowRows.length} Flows | ${faqRows.length} FAQs Loaded.`,
    );
    return true;
  } catch (error) {
    console.error("[SYSTEM ERROR] Gagal sync config DB:", error.message);
    return false;
  }
}

async function saveChatLog(userId, sender, message) {
  try {
    if (!message) return;

    // 1. Simpan ke Database
    await db.query(
      `INSERT INTO chat_logs_tele (phone_number, sender, message) VALUES (?, ?, ?)`,
      [userId.toString(), sender, message],
    );

    // // 2. CCTV ADMIN: Kirim tembusan riwayat chat ke Telegram Admin
    // const adminIdDb = botSettings.globals.ID_ADMIN || "";
    // const isAdmin = userId.toString() === adminIdDb.toString().trim();

    // // Cegah loop (Jangan kirim notif jika Admin yang sedang mengetik ke bot)
    // if (!isAdmin && adminIdDb && bot) {
    //   let mirrorMsg = "";

    //   // Rapikan format laporan ke Admin
    //   if (sender === "user") {
    //     mirrorMsg = `👤 *USER (${userId})*:\n${message}`;
    //   } else if (sender === "bot") {
    //     mirrorMsg = `🤖 *BOT* ➡️ _User_:\n${message}`;
    //   } else if (sender === "admin") {
    //     mirrorMsg = `👨‍💻 *ADMIN* ➡️ _User_:\n${message}`;
    //   }

    //   // Kirim laporan ke Admin (Gunakan .catch agar tidak crash jika diblokir)
    //   if (mirrorMsg) {
    //     await bot.telegram
    //       .sendMessage(adminIdDb, mirrorMsg, { parse_mode: "Markdown" })
    //       .catch(() => {});
    //   }
    // }
  } catch (e) {}
}

function adminHelpHint() {
  return "\n\nJika mengalami kendala pendaftaran dan ingin dibantu admin, ketik: tolong saya admin";
}

function adminHelpKeyboard() {
  return {
    keyboard: [[{ text: "🆘 Tolong saya admin" }]],
    resize_keyboard: true,
    one_time_keyboard: false,
  };
}

function adminMemberStatusLabel(status) {
  const labels = {
    aktif: "Aktif",
    lepas_ib: "Lepas IB",
    keluar: "Keluar",
    pasif: "Pasif",
  };
  return labels[String(status || "").trim()] || status || "Belum tercatat";
}

async function getAdminGroupStatus(adminInfo) {
  const telegramId = String(adminInfo?.id_telegram || "").trim();
  if (!telegramId) return "Belum ada ID Telegram / belum terdaftar ke bot";

  const groupId = String(botSettings.globals.ID_GRUP_VIP || "").trim();
  if (!groupId) return "ID grup belum dikonfigurasi";

  try {
    const member = await bot.telegram.getChatMember(groupId, telegramId);
    if (["creator", "administrator", "member"].includes(member.status)) {
      return `Sudah bergabung (${member.status === "member" ? "member" : member.status})`;
    }
    if (member.status === "restricted" && member.is_member === true) {
      return "Sudah bergabung (restricted)";
    }
    if (member.status === "kicked") return "Dikeluarkan/diblokir dari grup";
    if (member.status === "left") return "Belum bergabung atau sudah keluar";
    return `Status Telegram: ${member.status}`;
  } catch (error) {
    return "Tidak dapat dicek langsung (user belum pernah terdeteksi di grup / bot tidak dapat mengakses)";
  }
}

async function formatAdminMemberInfo(adminInfo) {
  const dbStatus = adminMemberStatusLabel(adminInfo?.member_status);
  const groupStatus = await getAdminGroupStatus(adminInfo);
  const telegramId = String(adminInfo?.id_telegram || "").trim() || "-";
  return (
    "\n\n📌 *Detail member:*" +
    `\n• Status database: *${dbStatus}*` +
    `\n• ID Telegram: \`${telegramId}\`` +
    `\n• Status grup: *${groupStatus}*`
  );
}

function isAdminHelpKeyword(message) {
  const words =
    String(message || "")
      .toLocaleLowerCase("id")
      .match(/[\p{L}\p{N}]+/gu) || [];
  return words.some(
    (word, index) =>
      words.slice(index, index + 3).join(" ") === "tolong saya admin",
  );
}

function isConfusionOrHumanHelpRequest(message) {
  let normalized = (
    String(message || "")
      .toLocaleLowerCase("id")
      .match(/[\p{L}\p{N}]+/gu) || []
  ).join(" ");
  normalized = normalized.replace(
    /\b(?:tidak|enggak|nggak|gak|ga) bingung\b/g,
    " ",
  );
  normalized = normalized.replace(
    /\b(?:tidak|enggak|nggak|gak|ga) (?:butuh|perlu|mau|ingin) admin\b/g,
    " ",
  );
  normalized = normalized.replace(
    /\b(?:tidak|enggak|nggak|gak|ga) (?:butuh|perlu|minta) bantuan\b/g,
    " ",
  );
  const phrases = [
    "bingung",
    "tidak paham",
    "enggak paham",
    "nggak paham",
    "gak paham",
    "ga paham",
    "belum paham",
    "kurang paham",
    "tidak mengerti",
    "enggak mengerti",
    "nggak mengerti",
    "gak mengerti",
    "tidak ngerti",
    "enggak ngerti",
    "nggak ngerti",
    "gak ngerti",
    "ga ngerti",
    "kurang ngerti",
    "tidak tahu",
    "enggak tahu",
    "nggak tahu",
    "gak tahu",
    "ga tahu",
    "kurang jelas",
    "butuh bantuan",
    "perlu bantuan",
    "minta bantuan",
    "mohon bantuan",
    "butuh bantuan manusia",
    "minta bantuan manusia",
    "butuh admin",
    "perlu admin",
    "mau admin",
    "ingin admin",
    "minta admin",
    "tolong admin",
    "minta tolong admin",
    "minta dibantu admin",
    "hubungi admin",
    "kontak admin",
    "bicara dengan admin",
    "bicara sama admin",
    "ingin bicara admin",
    "mau bicara admin",
    "ingin bicara dengan admin",
    "mau bicara dengan admin",
    "chat admin",
    "mau chat admin",
    "sambungkan ke admin",
    "admin manusia",
    "bantuan manusia",
    "operator",
    "customer service",
    "cs admin",
    "tolong jelaskan",
  ];
  return phrases.some((phrase) => normalized.includes(phrase));
}

async function sendAdminHelpReport(ctx, userId, userName, currentStep) {
  const adminId = String(botSettings.globals.ID_ADMIN || "").trim();
  if (!adminId) {
    const message = "Maaf kak, admin belum dikonfigurasi di bot. Silakan hubungi pengelola grup secara langsung.";
    await ctx.reply(message);
    await saveChatLog(userId, "bot", message);
    return false;
  }

  const sender = ctx.from || {};
  const fullName = [sender.first_name, sender.last_name].filter(Boolean).join(" ") || userName || "Kak";
  const username = sender.username ? "@" + sender.username : "-";
  const stageLabels = {
    1: "Status akun",
    2: "Pendaftaran akun",
    3: "Screenshot akun",
    4: "Pindah IB",
    5: "Arsip akun",
    6: "MT5 / deposit",
    7: "Formulir / undangan",
  };
  const stageLabel = stageLabels[Number(currentStep)] || "Tahap " + currentStep;
  const photoFileIds = new Set();
  let latestUserNote = "";
  const isAdminHelpButtonText = (value) => {
    const normalized = String(value || "").toLocaleLowerCase("id").replace(/[^\p{L}\p{N}]+/gu, " ").trim();
    const buttonPhrase = "tolong saya admin";
    return normalized === buttonPhrase ||
      normalized.replace(/^(?:[\p{L}\p{N}]{1,3}\s+)+(?=tolong saya admin$)/u, "") === buttonPhrase;
  };
  try {
    const [rows] = await db.query(
      "SELECT message FROM chat_logs_tele WHERE phone_number = ? AND sender = 'user' ORDER BY id DESC LIMIT 20",
      [String(userId)],
    );
    for (const row of rows) {
      const storedMessage = String(row.message || "");
      for (const match of storedMessage.matchAll(/\[TELEGRAM_FILE_ID:([^\]]+)\]/g)) {
        photoFileIds.add(match[1]);
      }
      if (!latestUserNote) {
        const note = storedMessage
          .replace(/\[TELEGRAM_FILE_ID:[^\]]+\]/g, "")
          .replace(/\s+/g, " ")
          .trim();
        if (note && note !== "[Photo]" && !isAdminHelpButtonText(note)) {
          latestUserNote = note;
        }
      }
    }
  } catch (error) {
    console.error("[ADMIN HELP HISTORY ERROR]", error.message);
  }

  const currentMessage = String(ctx.message?.text || ctx.message?.caption || "").trim();
  if (currentMessage && !isAdminHelpButtonText(currentMessage)) latestUserNote = currentMessage;
  const notePreview = [...(latestUserNote || "Tidak ada keterangan tambahan; silakan lihat foto terlampir bila tersedia.")].slice(0, 700).join("");
  const reportTime = new Date().toLocaleString("id-ID", { timeZone: "Asia/Jakarta" });
  const userProfileLink = "tg://user?id=" + encodeURIComponent(String(userId));
  const report = [
    "🆘 USER MEMINTA BANTUAN PENDAFTARAN",
    "Nama: " + fullName,
    "Username: " + username,
    "ID Telegram: " + userId,
    "Kendala pada bagian: " + stageLabel,
    "Keterangan user terakhir: " + notePreview,
    "Foto user terlampir: " + Math.min(photoFileIds.size, 10),
    "Waktu: " + reportTime + " WIB",
    "Silakan hubungi user secara langsung. Pesan tidak diteruskan melalui bot.",
  ].join("\n");

  try {
    await bot.telegram.sendMessage(adminId, report, {
      reply_markup: {
        inline_keyboard: [[{ text: "📨 Buka chat user", url: userProfileLink }]],
      },
    });
  } catch (error) {
    console.error("[ADMIN HELP REPORT ERROR]", error.message);
    const failure = "Maaf kak, informasi belum berhasil dikirim ke admin. Coba lagi beberapa saat.";
    await ctx.reply(failure);
    await saveChatLog(userId, "bot", failure);
    return false;
  }

  for (const [index, fileId] of [...photoFileIds].slice(0, 10).entries()) {
    try {
      await bot.telegram.sendPhoto(adminId, fileId, {
        caption: "Foto user " + (index + 1) + " · " + fullName + " · ID Telegram " + userId + " · " + stageLabel,
      });
    } catch (error) {
      console.error("[ADMIN HELP PHOTO ERROR]", error.message);
    }
  }

  const pendingTimeout = userTimeouts[String(userId)]?.timeout;
  if (pendingTimeout) clearTimeout(pendingTimeout);
  delete userTimeouts[String(userId)];
  const confirmation = "Info kendala kakak sudah dikirim satu kali ke admin. Admin akan menghubungi kakak langsung melalui Telegram. Pesan berikutnya tidak diteruskan melalui bot.";
  await ctx.reply(confirmation);
  await saveChatLog(userId, "bot", "Riwayat chat kakak sudah dikirim satu kali ke admin. Admin akan menghubungi kakak langsung melalui Telegram.");
  return true;
}

async function logUserProgress(userId, userName, step) {
  try {
    await db.query(
      `INSERT INTO user_progress (user_id, user_name, phone_number, current_step, last_active)
       VALUES (?, ?, ?, ?, NOW()) 
       ON DUPLICATE KEY UPDATE user_name = VALUES(user_name), current_step = VALUES(current_step), last_active = NOW()`,
      [userId.toString(), userName, userId.toString(), step],
    );
    console.log(
      `[PROGRESS] ✅ User ${userName} (ID: ${userId}) tersimpan di Step ${step}`,
    );
  } catch (e) {
    console.error("[PROGRESS ERROR] ❌ Gagal menyimpan data:", e.message);
  }
}

function replaceTags(msgText, userNameValue) {
  if (!msgText) return "";
  const callingName = userNameValue ? userNameValue : "kak";
  return msgText
    .replace(/{LINK_DAFTAR}/gi, botSettings.globals.LINK_DAFTAR || "")
    .replace(/{KODE_IB}/gi, botSettings.globals.KODE_IB || "")
    .replace(/{ADMIN_ID}/gi, botSettings.globals.ID_ADMIN || "")
    .replace(/{userName}|{NAMA_USER}/gi, callingName);
}

// 5. INTEGRASI GEMINI AI
async function askGemini(prompt, userName, currentStep) {
  if (!genAI) return null;
  try {
    const modelName =
      botSettings.globals.GEMINI_MODEL || "gemini-2.5-flash-lite";
    const model = genAI.getGenerativeModel({ model: modelName });
    const callingName = userName ? `kak ${userName}` : "kak";

    const systemPrompt = `Kamu adalah Cuanchi, admin pendaftaran VIP dari Akademi Full Margin.
Saat ini user ada di Tahap ${currentStep}. (1: Status, 2: Daftar, 3: SS Akun, 4: Pindah IB, 5: Arsip, 6: Buka MT5, 7: Isi Formulir).

ATURAN MUTLAK:
1. Jika user typo atau bertanya kendala, arahkan santai sesuai tahapnya.
2. Gunakan bahasa gaul, panggil '${callingName}'. Maksimal 2 kalimat.`;

    const result = await Promise.race([
      model.generateContent(`${systemPrompt}\n\nUser: ${prompt}`),
      new Promise((_, reject) =>
        setTimeout(() => reject(new Error("Gemini timeout")), 12000),
      ),
    ]);
    const response = await result.response;
    const text = response.text().trim();
    return text || null;
  } catch (err) {
    console.error("[GEMINI ERROR]", err.message);
    return null;
  }
}

// FUNGSI PENGIRIM PESAN + MEDIA (AUTO DETEKSI)
async function sendSmartMessage(ctx, replyText) {
  const mediaRegex = /\[(VIDEO|GAMBAR):\s*([a-zA-Z0-9_]+)\]/gi;
  let match;
  const mediaQueue = [];
  let cleanText = replyText;

  while ((match = mediaRegex.exec(replyText)) !== null) {
    const type = match[1].toUpperCase();
    const keyword = match[2].toLowerCase();
    cleanText = cleanText.replace(match[0], "").trim();
    mediaQueue.push({ type, keyword });
  }

  if (cleanText.length > 0) {
    await ctx.reply(cleanText, {
      parse_mode: "HTML",
      disable_web_page_preview: true,
    });
    await saveChatLog(ctx.from?.id, "bot", cleanText);
  }

  for (const media of mediaQueue) {
    try {
      const [rows] = await db.query(
        "SELECT * FROM tb_panduan_media WHERE keyword = ?",
        [media.keyword],
      );

      if (rows.length > 0) {
        if (media.type === "VIDEO" && rows[0].url_video) {
          await ctx.sendChatAction("upload_video");
          const response = await axios.get(rows[0].url_video, {
            responseType: "stream",
          });
          await ctx.replyWithVideo({ source: response.data });
        } else if (media.type === "GAMBAR" && rows[0].url_gambar) {
          await ctx.sendChatAction("upload_photo");
          const response = await axios.get(rows[0].url_gambar, {
            responseType: "stream",
          });
          await ctx.replyWithPhoto({ source: response.data });
        }
      }
    } catch (err) {
      console.error(`[MEDIA ERROR - ${media.keyword}]`, err.message);
      await ctx.reply(
        `⚠️ _(Sistem gagal memuat media: ${media.keyword}. Pastikan file ada di server)_`,
        { parse_mode: "Markdown" },
      );
    }
  }
}

function startTimeoutTimer(userId, userName) {
  if (userTimeouts[userId] && userTimeouts[userId].timeout) {
    clearTimeout(userTimeouts[userId].timeout);
  }

  // Admin dapat melakukan pengecekan ID Trading berulang kali. Jangan pernah
  // membuat follow-up otomatis untuk chat admin, tetapi tetap bersihkan timer
  // lama agar timer yang sudah terlanjur dibuat tidak mengirim pesan.
  const adminId = String(botSettings.globals.ID_ADMIN || "").trim();
  if (adminId !== "" && String(userId) === adminId) {
    delete userTimeouts[userId];
    return;
  }

  if (userSteps[userId] >= 7) {
    return;
  }

  const timeoutMin = parseInt(botSettings.globals.TIMEOUT_DURATION) || 5;
  userTimeouts[userId] = {
    timeout: setTimeout(
      async () => {
        const followUpTemplate = replaceTags(
          botSettings.globals.TIMEOUT_MESSAGE ||
            `Halo kak ${userName}, ada kendala saat pendaftaran? Boleh dibalas ya biar dibantu.`,
          userName,
        );
        const followUpMsg = /tolong\s+saya\s+admin/i.test(followUpTemplate)
          ? followUpTemplate
          : followUpTemplate + adminHelpHint();
        try {
          await bot.telegram.sendMessage(userId, followUpMsg, {
            reply_markup: adminHelpKeyboard(),
          });
          await saveChatLog(userId, "bot", followUpMsg);
        } catch (err) {
          if (err.message.includes("blocked")) {
            delete userSteps[userId];
            delete userImagesCount[userId];
          }
        }
      },
      timeoutMin * 60 * 1000,
    ),
    lastActive: Date.now(),
  };
}

async function jalankanAntrian() {
  if (sedangAntriBot || antrianBot.length === 0) return;
  sedangAntriBot = true;
  while (antrianBot.length > 0) {
    const task = antrianBot.shift();
    try {
      await task();
    } catch (e) {}
    await delay(1000);
  }
  sedangAntriBot = false;
}

// 8. INISIALISASI BOT & CORE ENGINE
async function initTelegramBot() {
  try {
    await fetchConfig();
    const TELEGRAM_TOKEN = botSettings.globals.TELEGRAM_TOKEN;
    if (!TELEGRAM_TOKEN) return;

    bot = new Telegraf(TELEGRAM_TOKEN);
    botStatus = { status: "Connected via Database" };
    const ID_GRUP_VIP = botSettings.globals.ID_GRUP_VIP;

    bot.on(["text", "photo"], async (ctx) => {
      antrianBot.push(async () => {
        try {
          const msg = ctx.message;
          const chatId = ctx.chat.id.toString();
          const chatType = ctx.chat.type;
          let textMessage = msg.text || msg.caption || "";
          const userName = ctx.from?.first_name || "Kak";
          const userId = ctx.from?.id;

          if (!userId || chatType === "group" || chatType === "supergroup")
            return;
          if (Math.floor(Date.now() / 1000) - msg.date > 120) return;

          const adminIdDb = String(botSettings.globals.ID_ADMIN || "").trim();
          const isAdmin = adminIdDb !== "" && userId.toString() === adminIdDb;
          const photoFileId = msg.photo?.[msg.photo.length - 1]?.file_id;
          if (!isAdmin) {
            const userMessageLog = msg.photo
              ? `${textMessage || "[Photo]"}\n[TELEGRAM_FILE_ID:${photoFileId}]`
              : textMessage || "[Photo]";
            await saveChatLog(userId, "user", userMessageLog);
          }
          await ctx.sendChatAction("typing");

          if (typeof userSteps[userId] === "undefined") userSteps[userId] = 1;
          let currentStep = userSteps[userId];

          // Periksa status validasi/invite sebelum menawarkan bantuan pendaftaran.
          const userKey = String(userId);
          if (!isAdmin) {
            try {
              const statusUrl = "http://103.89.4.144/bot_wa/client/check_status_vip/" + userId;
              const controlToken = getBotControlToken();
              if (!controlToken) throw new Error("BOT_CONTROL_TOKEN belum dikonfigurasi pada bot.");
              const checkStatus = await axios.get(statusUrl, {
                timeout: 5000,
                headers: { "X-Bot-Control-Token": controlToken },
              });
              if (checkStatus.data && checkStatus.data.is_vip === true) {
                const wasAlreadyValidated = validatedUsers.has(userKey);
                validatedUsers.add(userKey);
                let isMember = false;
                try {
                  const member = await bot.telegram.getChatMember(ID_GRUP_VIP, parseInt(userId, 10));
                  isMember = ["creator", "administrator", "member"].includes(member.status);
                } catch (memberError) {
                  console.warn("[VIP MEMBERSHIP CHECK]", memberError.message);
                }

                userSteps[userId] = 7;
                if (isMember) {
                  const registeredMessage = "Kak, akun kakak sudah tervalidasi dan sudah terdaftar di Grup VIP. Bot ini hanya melayani pendaftaran.";
                  await ctx.reply(registeredMessage, { reply_markup: { remove_keyboard: true } });
                  await saveChatLog(userId, "bot", "User tervalidasi dan sudah terdaftar di Grup VIP.");
                  return;
                }

                if (wasAlreadyValidated) {
                  const invitedMessage = "Kak, akun kakak sudah tervalidasi dan undangan Grup VIP sudah diberikan. Bot ini hanya melayani pendaftaran.";
                  await ctx.reply(invitedMessage, { reply_markup: { remove_keyboard: true } });
                  await saveChatLog(userId, "bot", invitedMessage);
                  return;
                }

                const inviteLink = await bot.telegram.createChatInviteLink(ID_GRUP_VIP, {
                  expire_date: Math.floor(Date.now() / 1000) + 60,
                  member_limit: 1,
                });
                const inviteMessage = "Status akun kakak sudah tervalidasi. Silakan bergabung ke Grup VIP melalui tautan ini:\n" + inviteLink.invite_link;
                const sentMsg = await ctx.reply(inviteMessage, { reply_markup: { remove_keyboard: true } });
                await saveChatLog(userId, "bot", "Status tervalidasi; tautan undangan Grup VIP sudah dikirim.");
                setTimeout(async () => {
                  try {
                    await bot.telegram.deleteMessage(userId, sentMsg.message_id);
                  } catch (error) {}
                }, 60000);
                return;
              }
              validatedUsers.delete(userKey);
            } catch (statusError) {
              if (statusError.response?.status === 401) {
                console.error("[VIP STATUS CHECK ERROR] CI4 menolak BOT_CONTROL_TOKEN; samakan konfigurasi bot dan CI4.");
              } else {
                console.error("[VIP STATUS CHECK ERROR]", statusError.message);
              }
              await ctx.reply(
                "Maaf kak, status pendaftaran belum dapat diperiksa oleh sistem. Silakan coba lagi beberapa saat lagi atau hubungi admin secara langsung.",
                { reply_markup: { remove_keyboard: true } },
              );
              await saveChatLog(userId, "bot", "Pemeriksaan status VIP gagal; pendaftaran ditunda.");
              return;
            }

            if (validatedUsers.has(userKey)) {
              const registeredMessage = "Kak, akun kakak sudah tervalidasi dan undangan Grup VIP sudah diberikan. Bot ini hanya melayani pendaftaran.";
              await ctx.reply(registeredMessage, { reply_markup: { remove_keyboard: true } });
              return;
            }
          }

          if (!isAdmin && isAdminHelpKeyword(textMessage)) {
            await sendAdminHelpReport(ctx, userKey, userName, currentStep);
            return;
          }

          if (!isAdmin && isConfusionOrHumanHelpRequest(textMessage)) {
            const helpChoice = "Saya coba bantu dulu ya, Kak. Jika masih terkendala dan ingin dibantu admin, tekan tombol di bawah atau ketik: tolong saya admin.";
            await ctx.reply(helpChoice, { reply_markup: adminHelpKeyboard() });
            await saveChatLog(userId, "bot", helpChoice);
          }

// 1.5 PENANGANAN FORMULIR & ID TRADING (AUTO DETEKSI DARI STEP MANA PUN)
          const namaMatch = textMessage.match(/Nama\s*:\s*(.+)/i);
          const waMatch = textMessage.match(
            /(?:No wa aktif|No wa|Nomor wa|WA)\s*:\s*(.+)/i,
          );
          const emailMatch = textMessage.match(/(?:Email|E-mail)\s*:\s*(.+)/i);
          const idTradingMatch = textMessage.match(/\b\d{5,15}\b/);

          const isMengisiForm = namaMatch && waMatch && emailMatch;

          // JIKA USER MENGIRIM FORM LENGKAP
          if (isMengisiForm) {
            // PERBAIKAN PERMANEN: Cek apakah ID sudah tervalidasi di memori.
            // Jika belum ada/kosong karena user curi start, tolak formnya!
            if (!userTempData[userId]) {
              await ctx.reply(
                "⚠️ Maaf Kak, sistem belum selesai memverifikasi ID Trading Kakak.\n\nSilakan ketik angka ID Trading-nya saja dan tunggu sampai ada balasan 'Berhasil / Valid' dari sistem, baru Kakak kirimkan formulir ini ya! 🙏",
              );
              startTimeoutTimer(userId, userName);
              return;
            }

            // Jika memori sudah terisi, gunakan HANYA id dari memori tersebut.
            const dataForm = {
              nama: namaMatch[1].trim(),
              no_wa: waMatch[1].trim(),
              email: emailMatch[1].trim(),
              id_telegram: userId.toString(),
              id_hfm: userTempData[userId],
            };

            const loadFormMsg = await ctx.reply(
              "⏳ Menyimpan data dan menyiapkan link VIP...",
            );

            try {
              const resSave = await axios.post(
                "http://103.89.4.144/bot_wa/client/simpanMemberForm",
                dataForm,
              );

              if (resSave.data.status === "sukses") {
                const inviteLink = await bot.telegram.createChatInviteLink(
                  botSettings.globals.ID_GRUP_VIP,
                  {
                    expire_date: Math.floor(Date.now() / 1000) + 60,
                    member_limit: 1,
                  },
                );

                const suksesMsg = `Selamat kak ${dataForm.nama}! Data berhasil diverifikasi.\n\nSilakan bergabung melalui link Grup VIP di bawah ini ya:\n👉 ${inviteLink.invite_link}\n\n_(Pesan otomatis ditarik dlm 1 menit)_`;

                await bot.telegram.deleteMessage(ctx.chat.id, loadFormMsg.message_id).catch(() => {});
                const sentMsg = await ctx.reply(suksesMsg, {
                  reply_markup: { remove_keyboard: true },
                });
                await saveChatLog(
                  userId,
                  "bot",
                  "Berhasil registrasi form dan kirim link.",
                );
                validatedUsers.add(String(userId));
                userSteps[userId] = 7;
                delete userTempData[userId];
                await logUserProgress(userId, userName, 7);

                setTimeout(async () => {
                  try {
                    await bot.telegram.deleteMessage(
                      userId,
                      sentMsg.message_id,
                    );
                  } catch (e) {}
                }, 60000);
              } else {
                await ctx.telegram.editMessageText(
                  ctx.chat.id,
                  loadFormMsg.message_id,
                  undefined,
                  "❌ Gagal menyimpan data: " + resSave.data.pesan,
                );
              }
            } catch (err) {
              const errorDetail =
                err.response && err.response.data
                  ? typeof err.response.data === "string"
                    ? err.response.data.substring(0, 150)
                    : JSON.stringify(err.response.data)
                  : err.message;
              await ctx.telegram.editMessageText(
                ctx.chat.id,
                loadFormMsg.message_id,
                undefined,
                `❌ Sistem Gagal Menyimpan.\n\n[DEBUG ADMIN]\nPenyebab: ${errorDetail}`,
              );
            }
            startTimeoutTimer(userId, userName);
            return;
          }

          // 2. CEK INPUT ANGKA ID TRADING MANUAL (HANYA ID, TANPA FORM)
          if (idTradingMatch && !isMengisiForm) {
            const adminIdDb = botSettings.globals.ID_ADMIN || "";
            const isAdmin = userId.toString() === adminIdDb.toString().trim();
            const idTrading = idTradingMatch[0];

            const loadText = isAdmin
              ? `👨‍💻 Siap Min! Mengecek status ID Trading (${idTrading}) ke server...`
              : `Mengecek ID Trading (${idTrading}) ke server, tunggu sebentar ya kak ${userName}... ⏳`;

            const sentLoadMsg = await ctx.reply(loadText);
            await saveChatLog(userId, "bot", loadText);

            const apiUserId = isAdmin ? "ADMIN_CHECK" : "CHECK_ONLY";

            try {
              const resCek = await axios.get(
                `http://103.89.4.144/bot_wa/client/cekHfm/${idTrading}/${apiUserId}`,
              );
              let finalMsg = "";

              if (resCek.data.status === "valid_sempurna") {
                if (isAdmin) {
                  const adminInfo = resCek.data.admin_info || {};
                  finalMsg =
                    `✅ *[ADMIN INFO]*\nID Trading \`${idTrading}\` statusnya *VALID* dan terdeteksi di IB BOSSCUAN!` +
                    await formatAdminMemberInfo(adminInfo);
                  await ctx.telegram.editMessageText(
                    ctx.chat.id,
                    sentLoadMsg.message_id,
                    undefined,
                    finalMsg,
                    { parse_mode: "Markdown" },
                  );
                } else {
                  userSteps[userId] = 7;
                  userTempData[userId] = idTrading;
                  finalMsg = `✅ **Selamat kak ${userName}!**\n\nID akun trading (${idTrading}) sudah terverifikasi dan berada di bawah BO$$CUAN 🎉\n\nSekarang silakan lengkapi data Kakak di bawah ini:\n\nNama :\nNo wa aktif :\nemail :\n\n_(Copy tulisan di atas, isi, lalu kirim ke sini agar kami bisa mengundang Kakak ke grup VIP Warga Avatar)_`;
                  await ctx.telegram.editMessageText(
                    ctx.chat.id,
                    sentLoadMsg.message_id,
                    undefined,
                    finalMsg,
                    { parse_mode: "Markdown" },
                  );
                  await logUserProgress(userId, userName, 7);
                }
                startTimeoutTimer(userId, userName);
                return;
              } else if (resCek.data.status === "id_diklaim_orang_lain") {
                if (isAdmin) {
                  const adminInfo = resCek.data.admin_info || {};
                  finalMsg =
                    `⚠️ *[ADMIN INFO]*\nID \`${idTrading}\` *SUDAH DIKLAIM* oleh nomor ${resCek.data.no_tersensor}.` +
                    await formatAdminMemberInfo(adminInfo);
                } else {
                  finalMsg = `❌ Maaf kak ${userName}, ID Trading ${idTrading} sudah diklaim oleh member lain dengan nomor ${resCek.data.no_tersensor}.`;
                }
              } else if (resCek.data.status === "kurang_deposit") {
                finalMsg = isAdmin
                  ? `⚠️ *[ADMIN INFO]*\nID \`${idTrading}\` terdaftar, tapi *DEPOSIT KURANG*.\n_Pesan:_ ${resCek.data.pesan}`
                  : `⚠️ ${resCek.data.pesan}\n\n💡 _Silakan lakukan deposit terlebih dahulu, lalu ketik ulang ID Kakak di sini jika sudah beres!_`;
              } else {
                finalMsg = isAdmin
                  ? `❌ *[ADMIN INFO]*\nID \`${idTrading}\` *TIDAK VALID* atau tidak terdaftar di IB kita.`
                  : resCek.data.pesan ||
                    "❌ ID Tidak Valid atau belum masuk di bawah IB BOSSCUAN.";
              }

              await ctx.telegram.editMessageText(
                ctx.chat.id,
                sentLoadMsg.message_id,
                undefined,
                finalMsg,
                { parse_mode: "Markdown" },
              );
              await saveChatLog(userId, "bot", finalMsg);
              startTimeoutTimer(userId, userName);
              return;
            } catch (err) {
              await ctx.telegram.editMessageText(
                ctx.chat.id,
                sentLoadMsg.message_id,
                undefined,
                "❌ Server API lagi gangguan kak. Coba beberapa saat lagi ya.",
              );
              startTimeoutTimer(userId, userName);
              return;
            }
          }

          // ==========================================
          // 3. OCR GAMBAR SCREENSHOT (PRIORITAS ID)
          // ==========================================
          if (msg.photo) {
            try {
              const loadMsg = await ctx.reply(
                `Sedang membaca gambar... Tunggu sebentar ya kak ${userName} ⏳`,
              );
              const fileId = msg.photo[msg.photo.length - 1].file_id;
              const fileLink = await bot.telegram.getFileLink(fileId);
              const responseImg = await axios.get(fileLink.href, {
                responseType: "arraybuffer",
              });
              const ocrRes = await Tesseract.recognize(
                Buffer.from(responseImg.data),
                "eng",
              );

              const ocrDetectedText = ocrRes.data.text || "";
              const ocrDetectedLower = ocrDetectedText.toLowerCase();

              // Tarik Angka ID sedini mungkin dari gambar!
              const ocrMatches = ocrDetectedText.match(/\b\d{5,15}\b/g);
              const uniqueIds = ocrMatches ? [...new Set(ocrMatches)] : [];
              const detectedId = uniqueIds.length === 1 ? uniqueIds[0] : "";
              const hasMultipleIds = uniqueIds.length > 1;

              // PRIORITAS 1: JIKA ADA 1 ANGKA ID TRADING TERDETEKSI -> LANGSUNG CEK API!
              if (detectedId) {
                try {
                  await ctx.telegram.deleteMessage(
                    ctx.chat.id,
                    loadMsg.message_id,
                  );
                } catch (e) {}
                const cekMsg = await ctx.reply(
                  `Angka ID (${detectedId}) terbaca dari gambar! Mengecek status pendaftaran & deposit kakak ke server... ⏳`,
                );

                try {
                  const resCek = await axios.get(
                    `http://103.89.4.144/bot_wa/client/cekHfm/${detectedId}/CHECK_ONLY`,
                  );

                  if (resCek.data.status === "valid_sempurna") {
                    userSteps[userId] = 7;
                    userTempData[userId] = detectedId;
                    const finalMsg = `✅ **Selamat kak ${userName}!**\n\nID akun trading (${detectedId}) sudah lolos verifikasi dan deposit masuk under BO$$CUAN 🎉\n\nSekarang silakan lengkapi data Kakak di bawah ini:\n\nNama :\nNo wa aktif :\nemail :\n\n_(Copy tulisan di atas, isi, lalu kirim ke sini untuk mendapatkan link VIP)_`;

                    await ctx.telegram.editMessageText(
                      ctx.chat.id,
                      cekMsg.message_id,
                      undefined,
                      finalMsg,
                      { parse_mode: "Markdown" },
                    );
                    await logUserProgress(userId, userName, 7);
                  } else if (resCek.data.status === "id_diklaim_orang_lain") {
                    await ctx.telegram.editMessageText(
                      ctx.chat.id,
                      cekMsg.message_id,
                      undefined,
                      `❌ Maaf kak, ID Trading ${detectedId} sudah diklaim oleh member lain dengan nomor ${resCek.data.no_tersensor}.`,
                    );
                  } else if (resCek.data.status === "kurang_deposit") {
                    await ctx.telegram.editMessageText(
                      ctx.chat.id,
                      cekMsg.message_id,
                      undefined,
                      `⚠️ ${resCek.data.pesan}\n\n💡 _Silakan deposit terlebih dahulu, lalu kirim ulang Screenshot-nya ke sini ya!_`,
                      { parse_mode: "Markdown" },
                    );
                  } else {
                    const invalidMsg = `❌ Maaf kak ${userName}, ID Trading ${detectedId} belum terdaftar di bawah IB kita.\n\nJika Kakak belum pindah IB, yuk ikuti panduan berikut:\n\nLogin via Web: http://hftidtrade.com\n\nCara Pindah IB:\n1. Klik Dompet Saya\n2. Klik Aksi Wallet\n3. Klik set ID Mitra & Kampanye\n4. Ketik g0olqxh5h3\n5. Klik Tambah & tetapkan Ulang.\n\n[VIDEO: pindah_ib]`;
                    await ctx.telegram
                      .deleteMessage(ctx.chat.id, cekMsg.message_id)
                      .catch(() => {});
                    await sendSmartMessage(ctx, invalidMsg);
                    userSteps[userId] = 4;
                  }
                  startTimeoutTimer(userId, userName);
                  return;
                } catch (err) {
                  await ctx.telegram.editMessageText(
                    ctx.chat.id,
                    cekMsg.message_id,
                    undefined,
                    "❌ Server API lagi gangguan kak. Coba lagi nanti ya.",
                  );
                  startTimeoutTimer(userId, userName);
                  return;
                }
              }

              // PRIORITAS 2: JIKA GAMBAR ADA BANYAK ID -> SURUH ARSIPKAN
              else if (hasMultipleIds) {
                try {
                  await ctx.telegram.deleteMessage(
                    ctx.chat.id,
                    loadMsg.message_id,
                  );
                } catch (e) {}
                const multiIdMsg = `Waduh kak ${userName}, aku mendeteksi ada **Lebih dari 1 Akun Trading** di gambar ini nih! 😅\n\nBiar sistem nggak bingung dan datanya valid, Kakak wajib **MENGARSIPKAN** akun-akun yang lama dulu ya (sisakan 1 akun saja).\n\nCara Arsip Akun:\n1. G boleh ada Posisi yg berjalan\n2. Saldo Wajib 0 (pindahkan ke wallet dulu)\n3. Klik Akun yg mau diarsipkan, lalu klik 'Arsipkan'\n\n[VIDEO: arsipkan_akun]\n\nKalo udah diarsipkan semua, kirim Screenshot buktinya ke sini ya kak!`;
                await sendSmartMessage(ctx, multiIdMsg);
                userSteps[userId] = 5;
              }

              // PRIORITAS 3: CEK SS PINDAH IB BERHASIL
              else if (
                ocrDetectedLower.includes("pembaruan berhasil") ||
                ocrDetectedLower.includes("update successful") ||
                ocrDetectedLower.includes(
                  "this campaign has already been assigned",
                ) ||
                ocrDetectedLower.includes("kampanye ini sudah ditetapkan")
              ) {
                try {
                  await ctx.telegram.deleteMessage(
                    ctx.chat.id,
                    loadMsg.message_id,
                  );
                } catch (e) {}
                const successMsg = `Mantap kak ${userName}, bukti pindah IB berhasil terdeteksi!\n\nSelanjutnya : \n\n1. arsipkan akun trading lamanya\n2. lalu buka akun trading baru ( bebas ) Pastikan Masukin kode IB g0olqxh5h3 di bagian introducing broker\n3. Lalu deposit minimal $10`;
                await sendSmartMessage(ctx, successMsg);
                userSteps[userId] = 5;
              }

              // PRIORITAS 4: CEK SS ARSIP BERHASIL
              else if (
                ocrDetectedLower.includes("arsip") &&
                (ocrDetectedLower.includes("berhasil") ||
                  ocrDetectedLower.includes("sukses"))
              ) {
                try {
                  await ctx.telegram.deleteMessage(
                    ctx.chat.id,
                    loadMsg.message_id,
                  );
                } catch (e) {}
                const arsipMsg = `Mantap kak ${userName}, udah berhasil arsip akun!\n\nSekarang lanjut ke langkah berikutnya:\n1. Buka akun trading Live MT5 (bebas pilih tipe)\n2. Pastikan memasukkan Kode IB g0olqxh5h3\n3. Deposit minimal Rp100.000\n\n⚠️ Kalo udah deposit, Ketik Angka Akun ID trading barunya ke sini ya kak!\n\n[GAMBAR: ss_akunsayaapk]\n\n[GAMBAR: ss_akunsaya]`;
                await sendSmartMessage(ctx, arsipMsg);
                userSteps[userId] = 6;
              }

              // PRIORITAS 5: KEYWORD TAMPILAN AKUN (BURAM/TIDAK ADA ID)
              else if (
                (ocrDetectedLower.includes("akun langsung") &&
                  ocrDetectedLower.includes("berdagang")) ||
                ocrDetectedLower.includes("buka akun perdagangan") ||
                (ocrDetectedLower.includes("akun trading") &&
                  ocrDetectedLower.includes("akun saya"))
              ) {
                try {
                  await ctx.telegram.deleteMessage(
                    ctx.chat.id,
                    loadMsg.message_id,
                  );
                } catch (e) {}
                const pindahIbMsg = `Mantap kak ${userName}, screenshot akunnya sudah terbaca!\n\nSekarang tinggal pindah IB aja ke under BOSSCUAN. Login via Web menggunakan Link Berikut : http://hftidtrade.com\n\nCara Pindah IB:\n1. Klik Dompet Saya\n2. Klik Aksi Wallet\n3. Klik set ID Mitra & Kampanye\n4. Ketik g0olqxh5h3 dibagian ID Mitra & Kampanye\n5. Klik Tambah\n6. Lalu klik tetapkan Ulang.\n\nIkutin Sesuai Video Tutorial ya. Jangan Lupa SS ketika sudah berhasil.\n\n[VIDEO: pindah_ib]`;
                await sendSmartMessage(ctx, pindahIbMsg);
                userSteps[userId] = 4;
              }

              // 6. GAGAL BACA SEMUA (GAMBAR BURAM/TIDAK RELEVAN)
              else {
                await ctx.telegram.editMessageText(
                  ctx.chat.id,
                  loadMsg.message_id,
                  undefined,
                  `Maaf kak ${userName}, aku gagal menemukan angka ID Trading atau tulisan valid di gambar itu. Bisa tolong ulangi atau ketik angkanya manual aja? 🙏`,
                );
              }
            } catch (ocrErr) {
              await ctx.reply(
                "Gagal memproses gambar kak. Ketik angkanya manual aja ya 🙏",
              );
            }
            startTimeoutTimer(userId, userName);
            return;
          }

          // 4. RESET / START COMMAND & FILTER MEMORI
          if (textMessage === "/start" || textMessage === "/reset") {
            userSteps[userId] = 1;
            const welcomeTemplate =
              botSettings.globals.KATA_SAPAAN ||
              `Halo Kak {NAMA_USER}, Selamat datang di layanan pendaftaran VIP!\nApakah Kakak berminat untuk bergabung ke grup kita?`;
            const welcomeMsg = replaceTags(welcomeTemplate, userName);
            await ctx.reply(welcomeMsg);
            await saveChatLog(userId, "bot", welcomeMsg);
            await logUserProgress(userId, userName, 1);
            startTimeoutTimer(userId, userName);
            return;
          }

          const textLower = textMessage.trim().toLowerCase();

          if (/^(sudah|udah|sdh|dah)$/i.test(textLower)) {
            if (currentStep === 1) {
              textMessage = "sudah punya akun";
            } else if (currentStep === 2) {
              textMessage = "selanjutnya";
            } else if (currentStep === 4) {
              await ctx.reply(
                `Bukti gambarnya belum masuk nih kak 😅\n\nKirim *foto screenshot bukti pindah IB-nya* ke sini dulu ya biar aku bisa cek otomatis! 📸`,
              );
              startTimeoutTimer(userId, userName);
              return;
            } else if (currentStep === 3 || currentStep === 5) {
              textMessage = "lanjut";
            }
          }

          const isGenericCara =
            /^(cara|caranya|gimana|gmn|tutorial|panduan|bagaimana)(\s+(gimana|gmn|caranya|banget))?(\s*\?)?$/i.test(
              textLower,
            );
          if (isGenericCara) {
            if (currentStep === 1) textMessage = "/start";
            else if (currentStep === 2) textMessage = "cara daftar";
            else if (currentStep === 3) textMessage = "selanjutnya";
            else if (currentStep === 4) textMessage = "cara pindah";
            else if (currentStep === 5) textMessage = "cara arsip";
            else if (currentStep === 6) textMessage = "deposit";
          }

          const isKataOk = /^(ok|oke|okey|siap|sip|baik|iya|ya|y)$/i.test(
            textLower,
          );
          if (isKataOk) {
            let okMsg = "";
            if (currentStep === 1)
              okMsg = `Siap kak ${userName}, jadi gimana nih, udah punya akun HFM belum?`;
            else if (currentStep === 2)
              okMsg = `Siap kak ${userName}, ditunggu ya. Kalau udah selesai daftar langsung kabarin aja (ketik 'udah').`;
            else if (currentStep === 3)
              okMsg = `Oke kak ${userName}, ditunggu ya screenshot akun tradingnya!`;
            else if (currentStep === 4)
              okMsg = `Oke kak ${userName}, ditunggu screenshot bukti pindah IB-nya ya!`;
            else if (currentStep === 5)
              okMsg = `Oke kak ${userName}, diarsipkan dulu ya akun lamanya, dan jangan lupa kirim screenshot buktinya ke sini!`;
            else if (currentStep === 6)
              okMsg = `Siap kak ${userName}, ditunggu ketikan ANGKA ID trading yang barunya ya kalau udah beres depo!`;

            if (okMsg) await ctx.reply(okMsg);
            await logUserProgress(userId, userName, currentStep);
            startTimeoutTimer(userId, userName);
            return;
          }

          // 5. MATCHING RULE, FAQ & FLOW
          let matched = false;
          const matchingText =
            userSteps[userId] === 1 &&
            /^(minat|berminat|tertarik|mau gabung|mau ikut|ikut|yaa|iya|iyah|yap|yes)$/i.test(textLower)
              ? `${textMessage} mau join`
              : textMessage;
          for (const rule of [...botSettings.faqs, ...botSettings.flows]) {
            if (!rule.keywords && !rule.trigger_keywords) continue;
            const keywordsArr = (rule.keywords || rule.trigger_keywords)
              .split(",")
              .map((k) => k.trim())
              .filter((k) => k);
            const regex = new RegExp(`\\b(${keywordsArr.join("|")})\\b`, "i");

            if (regex.test(matchingText)) {
              matched = true;
              if (rule.step_level) userSteps[userId] = rule.step_level;
              await sendSmartMessage(
                ctx,
                replaceTags(rule.reply_message, userName),
              );
              break;
            }
          }

          if (matched) {
            await logUserProgress(userId, userName, userSteps[userId]);
            startTimeoutTimer(userId, userName);
            return;
          }

          // 6. ASISTEN AI GEMINI & FALLBACK
          if (textMessage.length > 1 && textMessage.length < 150) {
            const aiReply = await askGemini(textMessage, userName, currentStep);
            if (aiReply) {
              await ctx.reply(aiReply);
              await saveChatLog(userId, "bot", aiReply);
            } else {
              const fallbackMsg = `Maaf kak ${userName}, aku belum memahami pesan itu.\n\nKetik /start untuk melihat panduan pendaftaran, atau ketik "tolong saya admin" jika ingin dibantu admin.`;
              await ctx.reply(fallbackMsg, { reply_markup: adminHelpKeyboard() });
              await saveChatLog(userId, "bot", fallbackMsg);
            }
          }
          let savedStep = userSteps[userId] || 1;
          await logUserProgress(userId, userName, savedStep);
          startTimeoutTimer(userId, userName);
        } catch (err) {
          console.error("[MESSAGE ERROR]", err.message);
        }
      });
      jalankanAntrian();
    });

    bot.on("new_chat_members", async (ctx) => {
      const configuredGroup = await getConfiguredTelegramGroup(true);
      const groupId = String(
        configuredGroup?.group_id || ID_GRUP_VIP || "",
      ).trim();
      if (ctx.chat.id.toString() !== groupId) return;
      const groupName =
        configuredGroup?.group_name || ctx.chat.title || "Grup Telegram VIP";

      for (const member of ctx.message.new_chat_members || []) {
        if (member.is_bot) continue;
        const telegramId = String(member.id);
        const memberName =
          [member.first_name, member.last_name].filter(Boolean).join(" ") ||
          member.username ||
          "Kak";
        const isReturning = await hasPreviousTelegramGroupHistory(
          telegramId,
          groupId,
        );
        await saveTelegramGroupMembership(groupId, groupName, member, "active");
        const welcomeTemplate = isReturning
          ? botSettings.globals.WELCOME_BACK_MESSAGE ||
            "Selamat Datang Kembali {NAMA_USER}, pastikan membaca rules yang ada di dalam group, pahami dan lakukan."
          : botSettings.globals.WELCOME_MESSAGE ||
            "Selamat Datang {NAMA_USER}, pastikan membaca rules yang ada di dalam group, pahami dan lakukan.";
        const welcomeMessage = replaceTags(welcomeTemplate, memberName);
        const messageOptions = {};
        if (ctx.message.message_thread_id)
          messageOptions.message_thread_id = ctx.message.message_thread_id;
        try {
          await bot.telegram.sendMessage(
            ctx.chat.id,
            welcomeMessage,
            messageOptions,
          );
          console.log(
            `[GROUP WELCOME] ${isReturning ? "Kembali" : "Baru"}: ${telegramId} di ${groupId}`,
          );
        } catch (error) {
          console.error("[GROUP WELCOME ERROR]", error.message);
        }
      }
    });

    bot.on("left_chat_member", async (ctx) => {
      const configuredGroup = await getConfiguredTelegramGroup(true);
      const groupId = String(
        configuredGroup?.group_id || ID_GRUP_VIP || "",
      ).trim();
      if (ctx.chat.id.toString() !== groupId) return;
      const member = ctx.message.left_chat_member;
      if (!member || member.is_bot) return;
      await saveTelegramGroupMembership(
        groupId,
        configuredGroup?.group_name || ctx.chat.title || "Grup Telegram VIP",
        member,
        "left",
      );

      // Hanya catat sebagai keluar sendiri bila user yang melakukan aksi leave.
      if (String(ctx.from?.id || "") === String(member.id)) {
        try {
          await axios.get(
            `${getCiBaseUrl()}/client/apiUpdateStatusMemberGrup/${encodeURIComponent(member.id)}/keluar_sendiri`,
            { timeout: 10000 },
          );
        } catch (error) {
          console.error("[GROUP LEAVE LOG ERROR]", error.message);
        }
      }
    });

    bot.launch();
    console.log(
      "[BOT] Telegram Bot Berhasil Berjalan dengan Konfigurasi MySQL!",
    );
  } catch (error) {
    console.error("[BOT INIT ERROR]", error.message);
  }
}

async function runDailyReport(options = {}) {
  const skipSync = options.skipSync === true;
  console.log("\n[CRON JOB] Memulai sync HFM sebelum laporan harian...");

  // Pastikan sistem bot Telegram sudah terinisialisasi
  if (!bot) {
    console.log("[REPORT FAIL] Bot Telegram belum siap/terkoneksi.");
    return;
  }

  let syncWarning = "";
  if (skipSync) {
    console.log(
      "[MANUAL REPORT] Sync dilewati; mengambil data laporan langsung dari database.",
    );
  } else {
    try {
      const syncResponse = await axios.get(
        "http://103.89.4.144/bot_wa/update-last-trade",
        { timeout: 180000 },
      );
      const syncResult = syncResponse.data || {};
      const syncFailed = Number(syncResult.failed || 0);
      if (syncResult.status !== "sukses" || syncFailed > 0) {
        syncWarning = `Sync HFM belum sempurna (failed=${syncFailed}).`;
        console.error("[SYNC WARNING] Tetap mengambil laporan:", syncResult);
      } else {
        console.log(
          "[SYNC HFM OK] Semua data berhasil disinkronkan. Menarik laporan...",
        );
      }
    } catch (syncError) {
      syncWarning = `Sync HFM gagal: ${syncError.message}`;
      console.error(
        "[SYNC WARNING] Sync gagal, laporan tetap dijalankan:",
        syncError.message,
      );
    }
  }

  try {
    const urlLaporan = "http://103.89.4.144/bot_wa/client/apiLaporanHarian";
    const response = await axios.get(urlLaporan, { timeout: 60000 });
    const dataReport = response.data || {};

    if (dataReport.status === "ok") {
      const masuk = dataReport.data.masuk || 0;
      const keluar_sendiri = dataReport.data.keluar_sendiri || 0;
      const lepas_ib = dataReport.data.lepas_ib || 0;
      const total_aktif = dataReport.data.total_aktif || 0;

      const pesanReport = `📊 *LAPORAN HARIAN GRUP VIP BOSSCUAN* 📊\n\nHalo Admin! Berikut rekap aktivitas member hari ini berdasarkan database:\n\n👥 *Total Client/User Aktif:* ${total_aktif} orang\n📈 *Member Masuk:* ${masuk} orang\n🚪 *Keluar Sendiri:* ${keluar_sendiri} orang\n🚫 *Lepas IB / Kick:* ${lepas_ib} orang\n\n_Data ditarik otomatis dari database sistem._`;

      const adminId = botSettings.globals.ID_ADMIN;

      // 1. KIRIM KE ADMIN
      if (adminId) {
        await bot.telegram
          .sendMessage(adminId, pesanReport, { parse_mode: "Markdown" })
          .catch((err) =>
            console.error("[REPORT ERROR] Gagal kirim ke Admin:", err.message),
          );
      }

      // 2. KIRIM KE GRUP (JIKA ADA)
      try {
        const ID_GRUP_LAPORAN = "-5564212131";
        if (ID_GRUP_LAPORAN) {
          await bot.telegram.sendMessage(ID_GRUP_LAPORAN, pesanReport, {
            parse_mode: "Markdown",
          });
          console.log("[REPORT SUKSES] Laporan harian berhasil dikirim.");
          return { sent: true };
        }
      } catch (groupErr) {
        console.error(
          "[REPORT ERROR] Gagal mengirim ke Grup:",
          groupErr.message,
        );
      }
    } else {
      console.error(
        "[REPORT FAIL] API mengembalikan status gagal/kosong. Data:",
        dataReport,
      );
      return { sent: false };
    }
  } catch (error) {
    console.error(
      "[REPORT ERROR] Gagal menarik data laporan dari CI4 setelah sync:",
      error.message,
    );
    return { sent: false };
  }
}

// Reminder aktivitas terpisah dari laporan harian dan waktunya diatur dari dashboard.
async function runActivityReminder() {
  console.log("[ACTIVITY REMINDER] Mulai proses evaluasi client...");
  try {
    const response = await axios.post(
      "http://103.89.4.144/bot_wa/client/run-activity-reminders",
      {},
      { timeout: 120000 },
    );
    console.log("[ACTIVITY REMINDER] Proses selesai:", response.data);
  } catch (error) {
    console.error(
      "[ACTIVITY REMINDER ERROR] Gagal menjalankan evaluasi:",
      error.response?.data || error.message,
    );
  }
}

const configuredCronTasks = new Map();
let scheduleCache = [];

async function refreshConfiguredSchedules() {
  try {
    const response = await axios.get(
      "http://103.89.4.144/bot_wa/client/bot-schedules",
      { timeout: 15000 },
    );
    const schedules = response.data?.schedules || [];
    for (const [key, task] of configuredCronTasks) {
      if (!schedules.some((item) => item.schedule_key === key)) {
        task.stop();
        configuredCronTasks.delete(key);
      }
    }
    for (const schedule of schedules) {
      const [hour, minute] = String(schedule.run_at || "")
        .split(":")
        .map(Number);
      if (!Number.isInteger(hour) || !Number.isInteger(minute)) continue;
      const expression = String(minute) + " " + String(hour) + " * * *";
      const existing = configuredCronTasks.get(schedule.schedule_key);
      if (existing?.expression === expression) continue;
      if (existing) existing.task.stop();
      const handler =
        schedule.schedule_type === "report"
          ? runDailyReport
          : runActivityReminder;
      const task = cron.schedule(expression, handler, {
        scheduled: true,
        timezone: "Asia/Jakarta",
      });
      configuredCronTasks.set(schedule.schedule_key, { expression, task });
    }
    scheduleCache = schedules;
    console.log(
      "[SCHEDULE] Jadwal aktif:",
      schedules.map((item) => item.schedule_key + "=" + item.run_at).join(", "),
    );
  } catch (error) {
    console.error(
      "[SCHEDULE ERROR] Gagal membaca jadwal dashboard:",
      error.message,
    );
  }
}
setInterval(fetchConfig, 5 * 60 * 1000);
setInterval(refreshConfiguredSchedules, 60 * 1000);
refreshConfiguredSchedules();
initTelegramBot();
process.once("SIGINT", () => bot && bot.stop("SIGINT"));
process.once("SIGTERM", () => bot && bot.stop("SIGTERM"));
