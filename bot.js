require("dotenv").config();
const { Telegraf } = require("telegraf");
const mysql = require("mysql2/promise");
const express = require("express");
const axios = require("axios");
const cron = require("node-cron");
const Tesseract = require("tesseract.js");
const { GoogleGenerativeAI } = require("@google/generative-ai");

// 1. EXPRESS & PORT MANAGEMENT
const app = express();
const API_PORT = Number(process.env.API_PORT || 3000);
app.use(express.json({ limit: "64kb" }));
// The control API is bound to localhost by deployment; do not expose it with a wildcard CORS policy.


// 2. KONEKSI DATABASE MYSQL
const db = mysql.createPool({
  host: process.env.DB_HOST || "127.0.0.1",
  port: Number(process.env.DB_PORT || 3306),
  user: process.env.DB_USER || "",
  password: process.env.DB_PASSWORD || "",
  database: process.env.DB_NAME || "db_hfm_bot",
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0,
});

// 3. MEMORI LOCAL & STATE GLOBAL
let bot = null;
let genAI = null;
let botStatus = { status: "Disconnected" };
let dynamicAdminId = "";
let botSettings = { globals: {}, flows: [], faqs: [] };
const CI_BASE_URL = (process.env.CI_BASE_URL || "").replace(/\/$/, "");
if (!CI_BASE_URL) console.warn("[CONFIG] CI_BASE_URL belum dikonfigurasi.");
const ciUrl = (path) => `${CI_BASE_URL}${path}`;

async function getDynamicGroupLinks() {
  try {
    const response = await axios.get(ciUrl("/client/apiGroupLinks"), {
      timeout: 10000,
    });
    const links = response.data?.links || {};
    links.__admin_id = String(response.data?.admin_id || "").trim();
    return links;
  } catch (error) {
    console.error("[GROUP LINKS ERROR]", error.message);
    return {};
  }
}

const userSteps = {};
const userImagesCount = {};
const userTimeouts = {};
const userMessages = {};
const userTempData = {};
const vipNotified = new Set();

const antrianBot = [];
let sedangAntriBot = false;
const delay = (ms) => new Promise((res) => setTimeout(res, ms));

// 4. CONFIG LOADER & DATABASE HELPER
async function fetchConfig() {
  try {
    const [globalRows] = await db.query(
      "SELECT key_name, key_value FROM bot_globals",
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
  } catch (error) {
    console.error("[SYSTEM ERROR] Gagal sync config DB:", error.message);
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
    // const adminIdDb = dynamicAdminId || "";
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
    .replace(/{ADMIN_ID}/gi, dynamicAdminId || "")
    .replace(/{userName}|{NAMA_USER}/gi, callingName);
}

// 5. INTEGRASI GEMINI AI
async function askGemini(prompt, userName, currentStep = 1) {
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

    const result = await model.generateContent(
      `${systemPrompt}\n\nUser: ${prompt}`,
    );
    const response = await result.response;
    return response.text().trim();
  } catch (err) {
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

  if (userSteps[userId] >= 7) {
    return;
  }

  const timeoutMin = parseInt(botSettings.globals.TIMEOUT_DURATION) || 5;
  userTimeouts[userId] = {
    timeout: setTimeout(
      async () => {
        const followUpMsg = replaceTags(
          botSettings.globals.TIMEOUT_MESSAGE ||
            `Halo kak ${userName}, ada kendala saat pendaftaran? Boleh dibalas ya biar dibantu.`,
          userName,
        );
        try {
          await bot.telegram.sendMessage(userId, followUpMsg);
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

function hasValidControlToken(req) {
  const expected = String(botSettings.globals.BOT_CONTROL_TOKEN || "");
  const supplied = String(req.get("x-bot-control-token") || req.body?.token || "");
  return expected.length > 0 && supplied.length === expected.length &&
    require("crypto").timingSafeEqual(Buffer.from(supplied), Buffer.from(expected));
}

function requireControlToken(req, res) {
  if (!hasValidControlToken(req)) {
    res.status(401).json({ status: "failed", error: "Unauthorized." });
    return false;
  }
  return true;
}

app.post("/api/restart-bot", (req, res) => {
  if (!requireControlToken(req, res)) return;
  res.json({ status: "ok", message: "Bot akan direstart oleh process manager." });
  setImmediate(() => process.exit(0));
});

app.post("/api/send-daily-report", async (req, res) => {
  if (!requireControlToken(req, res)) return;
  if (!bot || !dynamicAdminId) return res.status(503).json({ status: "failed", error: "Bot atau admin belum siap." });
  try {
    const report = await axios.get(ciUrl("/client/apiLaporanHarian"), { timeout: 15000 });
    const data = report.data?.data || {};
    const message = `\ud83d\udcca Laporan harian\n\nMasuk: ${Number(data.masuk || 0)}\nKeluar sendiri: ${Number(data.keluar_sendiri || 0)}\nLepas IB/kick: ${Number(data.lepas_ib || 0)}\nTotal aktif: ${Number(data.total_aktif || 0)}`;
    await bot.telegram.sendMessage(dynamicAdminId, message);
    return res.json({ status: "success", data });
  } catch (error) {
    console.error("[REPORT ERROR]", error.message);
    return res.status(502).json({ status: "failed", error: "Gagal mengambil atau mengirim laporan." });
  }
});

// API dashboard: kirim tombol perantara ke grup Telegram.
app.post("/send-whatsapp-button", async (req, res) => {
  if (!requireControlToken(req, res)) return;
  try {
    const sourceLinkId = Number(req.body?.source_link_id);
    const telegramGroupId = String(req.body?.telegram_group_id || "").trim();
    if (!sourceLinkId || !telegramGroupId)
      return res
        .status(400)
        .json({
          status: "failed",
          error: "source_link_id dan telegram_group_id wajib diisi.",
        });
    if (!bot)
      return res
        .status(503)
        .json({ status: "failed", error: "Bot Telegram belum terhubung." });
    const [rows] = await db.query(
      "SELECT id, group_name, invite_link FROM bot_group_links WHERE id = ? AND platform = 'whatsapp' AND is_active = 1 LIMIT 1",
      [sourceLinkId],
    );
    if (!rows.length)
      return res
        .status(404)
        .json({
          status: "failed",
          error: "Link WhatsApp aktif tidak ditemukan.",
        });
    const me = await bot.telegram.getMe();
    const deepLink = `https://t.me/${me.username}?start=wa_${sourceLinkId}`;
    const message = `📢 *Akses ${rows[0].group_name}*\n\nKlik tombol di bawah. Bot akan mengecek data pendaftaran Anda terlebih dahulu sebelum memberikan akses grup WhatsApp.`;
    await bot.telegram.sendMessage(telegramGroupId, message, {
      parse_mode: "Markdown",
      reply_markup: {
        inline_keyboard: [[{ text: "Gabung Grup WhatsApp", url: deepLink }]],
      },
      disable_web_page_preview: true,
    });
    res.json({ status: "success", deep_link: deepLink });
  } catch (error) {
    console.error("[SEND WA BUTTON ERROR]", error.message);
    res.status(502).json({ status: "failed", error: error.message });
  }
});

// 8. INISIALISASI BOT & CORE ENGINE
app.listen(API_PORT, "127.0.0.1", () => {
  console.log(`[API] Internal bot API listening on 127.0.0.1:${API_PORT}`);
});

async function initTelegramBot() {
  try {
    await fetchConfig();
    const TELEGRAM_TOKEN = botSettings.globals.TELEGRAM_TOKEN;
    if (!TELEGRAM_TOKEN) return;

    bot = new Telegraf(TELEGRAM_TOKEN);
    botStatus = { status: "Connected via Database" };
    // Sumber tunggal konfigurasi grup: bot_group_links melalui endpoint CI4.
    const initialGroupLinks = await getDynamicGroupLinks();
    let ID_GRUP_VIP = initialGroupLinks.telegram?.group_id || "";
    dynamicAdminId = initialGroupLinks.__admin_id || "";
    if (!ID_GRUP_VIP)
      console.warn(
        "[GROUP CONFIG] ID grup Telegram belum diatur di menu Link Grup.",
      );

    bot.start(async (ctx) => {
      const payload = String(ctx.startPayload || "").trim();
      const match = payload.match(/^wa_(\d+)$/i);
      if (!match)
        return ctx.reply(
          "Silakan kirim pesan atau gunakan tombol pendaftaran yang tersedia.",
        );
      const sourceLinkId = Number(match[1]);
      const userId = ctx.from.id.toString();
      const links = await getDynamicGroupLinks();
      const whatsapp = links.whatsapp;
      if (!whatsapp || Number(whatsapp.id) !== sourceLinkId)
        return ctx.reply(
          "❌ Link WhatsApp ini sudah tidak aktif. Silakan minta link terbaru dari admin.",
        );
      try {
        const status = await axios.get(
          ciUrl(`/client/check_status_vip/${encodeURIComponent(userId)}`),
          { timeout: 5000 },
        );
        if (status.data?.is_vip === true) {
          return ctx.reply(
            "✅ Data Anda sudah terdaftar di database. Silakan masuk ke grup WhatsApp:",
            {
              reply_markup: {
                inline_keyboard: [
                  [
                    {
                      text: `Masuk ${whatsapp.group_name || "Grup WhatsApp"}`,
                      url: whatsapp.invite_link,
                    },
                  ],
                ],
              },
            },
          );
        }
      } catch (error) {
        console.error("[WA DEEPLINK STATUS ERROR]", error.message);
        return ctx.reply(
          "❌ Sistem sedang tidak dapat mengecek data Anda. Silakan coba lagi beberapa saat.",
        );
      }
      userSteps[userId] = 5;
      userTempData[userId] = {
        id_hfm: null,
        registration_source: "telegram_whatsapp_button",
        registration_source_id: sourceLinkId,
      };
      const prompt = `Data Anda belum terdaftar.\n\nSilakan kirim ID Trading terlebih dahulu. Bot akan memvalidasinya, lalu meminta Nama, Nomor WhatsApp, dan Email jika ID valid.`;
      await ctx.reply(prompt);
      await saveChatLog(userId, "bot", prompt);
    });

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

          await saveChatLog(userId, "user", textMessage || "[Photo]");
          await ctx.sendChatAction("typing");

          const latestGroupLinks = await getDynamicGroupLinks();
          if (latestGroupLinks.telegram?.group_id)
            ID_GRUP_VIP = latestGroupLinks.telegram.group_id;
          if (latestGroupLinks.__admin_id)
            dynamicAdminId = latestGroupLinks.__admin_id;

          if (typeof userSteps[userId] === "undefined") userSteps[userId] = 1;
          let currentStep = userSteps[userId];

          // FITUR ADMIN: AMBIL ALIH CHAT (BALAS MANUAL)
          const adminIdDb = dynamicAdminId || "";
          const isAdmin = userId.toString() === adminIdDb.toString().trim();

          if (isAdmin && textMessage.startsWith("/balas ")) {
            const parts = textMessage.split(" ");
            if (parts.length >= 3) {
              const targetId = parts[1];
              const replyMsg = parts.slice(2).join(" ");

              try {
                await bot.telegram.sendMessage(
                  targetId,
                  `👨‍💻 *Pesan dari Admin:*\n\n${replyMsg}`,
                  { parse_mode: "Markdown" },
                );

                await ctx.reply(`✅ Pesan berhasil dikirim ke ID ${targetId}`);
                await saveChatLog(targetId, "admin", replyMsg);
              } catch (e) {
                await ctx.reply(
                  `❌ Gagal mengirim. Pastikan ID benar dan user belum memblokir bot.`,
                );
              }
            } else {
              await ctx.reply(
                "❌ Format salah.\nGunakan: `/balas [ID_USER] [PESAN KAKAK]`\nContoh: `/balas 123456789 Halo kak, ada yang bisa dibantu?`",
                { parse_mode: "Markdown" },
              );
            }
            return;
          }

          // 1. CEK STATUS VIP & MEMBER LAMA
          try {
            const urlCek = ciUrl(`/client/check_status_vip/${encodeURIComponent(userId)}`);
            const checkStatus = await axios.get(urlCek, { timeout: 5000 });

            if (checkStatus.data && checkStatus.data.is_vip === true) {
              let isMember = false;

              try {
                const member = await bot.telegram.getChatMember(
                  ID_GRUP_VIP,
                  parseInt(userId),
                );
                if (
                  ["creator", "administrator", "member"].includes(member.status)
                ) {
                  isMember = true;
                }
              } catch (errMember) {
                isMember = false;
              }

              if (isMember) {
                const adminIdDb = dynamicAdminId || "";
                if (!isAdmin) {
                  const vipReply = `Halo kak ${userName}! Kakak kan sudah tergabung di grup VIP kita nih 🎉\n\nJika ada pertanyaan, kendala seputar trading, atau butuh panduan lebih lanjut, silakan langsung hubungi Admin Utama ya!\n\n👉 [Klik di sini untuk Chat Admin Utama](tg://user?id=${adminIdDb})`;
                  await ctx.reply(vipReply, { parse_mode: "Markdown" });
                }
                userSteps[userId] = 7;
                startTimeoutTimer(userId, userName);
                return;
              } else {
                const links = await getDynamicGroupLinks();
                let telegramLink = links.telegram?.invite_link || "";
                if (!telegramLink) {
                  const inviteLink = await bot.telegram.createChatInviteLink(
                    ID_GRUP_VIP,
                    {
                      expire_date: Math.floor(Date.now() / 1000) + 60,
                      member_limit: 1,
                    },
                  );
                  telegramLink = inviteLink.invite_link;
                }
                const whatsappLink =
                  links.whatsapp?.invite_link ||
                  "(link WhatsApp belum dikonfigurasi admin)";
                const sentMsg = await ctx.reply(
                  `Halo kak ${userName}! Status VIP kakak sudah aktif kok 🎉\n\nSilakan bergabung ke kedua grup VIP:\n\nTelegram:\n👉 ${telegramLink}\n\nWhatsApp:\n👉 ${whatsappLink}\n\n_(Pesan otomatis ditarik dlm 1 menit)_`,
                );

                setTimeout(async () => {
                  try {
                    await bot.telegram.deleteMessage(
                      userId,
                      sentMsg.message_id,
                    );
                  } catch (e) {}
                }, 60000);
                userSteps[userId] = 7;
                startTimeoutTimer(userId, userName);
                return;
              }
            }
          } catch (e) {}

          // 1.5 PENANGANAN FORMULIR (STEP 7)
          if (currentStep === 7) {
            const text = textMessage;

            const namaMatch = text.match(/Nama\s*:\s*(.+)/i);
            const waMatch = text.match(/No wa aktif\s*:\s*(.+)/i);
            const emailMatch = text.match(/email\s*:\s*(.+)/i);

            if (namaMatch && waMatch && emailMatch) {
              const dataForm = {
                nama: namaMatch[1].trim(),
                no_wa: waMatch[1].trim(),
                email: emailMatch[1].trim(),
                id_telegram: userId.toString(),
                id_hfm:
                  typeof userTempData[userId] === "object"
                    ? userTempData[userId].id_hfm
                    : userTempData[userId] || "",
                registration_source:
                  typeof userTempData[userId] === "object"
                    ? userTempData[userId].registration_source
                    : null,
                registration_source_id:
                  typeof userTempData[userId] === "object"
                    ? userTempData[userId].registration_source_id
                    : null,
              };

              const loadFormMsg = await ctx.reply(
                "⏳ Menyimpan data dan menyiapkan link VIP...",
              );

              try {
                const resSave = await axios.post(
                  ciUrl("/client/simpanMemberForm"),
                  dataForm,
                );

                if (resSave.data.status === "sukses") {
                  const links = await getDynamicGroupLinks();
                  let telegramLink = links.telegram?.invite_link || "";
                  if (!telegramLink) {
                    const inviteLink = await bot.telegram.createChatInviteLink(
                      ID_GRUP_VIP,
                      {
                        expire_date: Math.floor(Date.now() / 1000) + 60,
                        member_limit: 1,
                      },
                    );
                    telegramLink = inviteLink.invite_link;
                  }
                  const whatsappLink =
                    links.whatsapp?.invite_link ||
                    "(link WhatsApp belum dikonfigurasi admin)";
                  const suksesMsg = `Selamat kak ${dataForm.nama}! Data berhasil diverifikasi.\n\nSilakan bergabung ke kedua grup VIP:\n\nTelegram:\n👉 ${telegramLink}\n\nWhatsApp:\n👉 ${whatsappLink}\n\n_(Pesan otomatis ditarik dlm 1 menit)_`;
                  const sentMsg = await ctx.telegram.editMessageText(
                    ctx.chat.id,
                    loadFormMsg.message_id,
                    undefined,
                    suksesMsg,
                  );
                  await saveChatLog(
                    userId,
                    "bot",
                    "Berhasil registrasi form dan kirim link.",
                  );

                  userSteps[userId] = 7;
                  delete userTempData[userId];

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

                const failMsg = `❌ Sistem Gagal Menyimpan.\n\n[DEBUG ADMIN]\nPenyebab: ${errorDetail}`;

                try {
                  await ctx.telegram.editMessageText(
                    ctx.chat.id,
                    loadFormMsg.message_id,
                    undefined,
                    failMsg,
                  );
                } catch (e) {
                  await ctx.reply(failMsg);
                }
              }
            } else {
              const errorFormat =
                "❌ Format tidak sesuai atau ada baris yang hilang kak.\n\nSilakan *Copy (Salin)* format sebelumnya, tempel di sini, lalu isi dengan lengkap ya!";
              await ctx.reply(errorFormat);
              await saveChatLog(userId, "bot", errorFormat);
            }
            startTimeoutTimer(userId, userName);
            return;
          }

          // 2. CEK INPUT ANGKA ID TRADING MANUAL
          const idTradingMatch = textMessage.match(/\b\d{5,}\b/);

          if (idTradingMatch) {
            const adminIdDb = dynamicAdminId || "";
            const isAdmin = userId.toString() === adminIdDb.toString().trim();

            if (!isAdmin && currentStep < 5) {
              const warningMsg = `Eits, tunggu dulu kak ${userName}! Kakak belum sampai di tahap penyerahan ID Trading nih 😅\n\nYuk ikuti panduan step-by-step sebelumnya ya!`;
              await ctx.reply(warningMsg);
              await saveChatLog(userId, "bot", warningMsg);
              startTimeoutTimer(userId, userName);
              return;
            }

            const idTrading = idTradingMatch[0];
            const loadText = isAdmin
              ? `👨‍💻 Siap Min! Mengecek status ID Trading (${idTrading}) ke server...`
              : `Oke kak ${userName}, aku cek dulu ya ID Trading (${idTrading})...`;

            // PERBAIKAN: Simpan object pesan balasan ke sentLoadMsg
            const sentLoadMsg = await ctx.reply(loadText);
            await saveChatLog(userId, "bot", loadText);

            const apiUserId = isAdmin ? "ADMIN_CHECK" : "CHECK_ONLY";

            try {
              const resCek = await axios.get(
                ciUrl(`/client/cekHfm/${encodeURIComponent(idTrading)}/${encodeURIComponent(apiUserId)}`),
              );
              let finalMsg = "";

              if (resCek.data.status === "valid_sempurna") {
                if (isAdmin) {
                  finalMsg = `✅ *[ADMIN INFO]*\nID Trading \`${idTrading}\` statusnya *VALID* dan terdeteksi di bawah IB BOSSCUAN!`;
                  await ctx.telegram.editMessageText(
                    ctx.chat.id,
                    sentLoadMsg.message_id,
                    undefined,
                    finalMsg,
                    { parse_mode: "Markdown" },
                  );
                  await saveChatLog(userId, "bot", "Admin berhasil cek ID.");
                } else {
                  userSteps[userId] = 7;
                  const previousSource =
                    typeof userTempData[userId] === "object"
                      ? userTempData[userId]
                      : {};
                  userTempData[userId] = {
                    ...previousSource,
                    id_hfm: idTrading,
                  };
                  finalMsg = `Baik. ID akun trading sudah under BO$$CUAN...\nSekarang silahkan lengkapi data anda dibawah ini.\n\nNama :\nNo wa aktif :\nemail :\n\nSetelah anda melengkapi data diatas, kami akan mengundang anda untuk join di grup VIP WARGA AVATAR by Papip Celebes`;
                  await ctx.telegram.editMessageText(
                    ctx.chat.id,
                    sentLoadMsg.message_id,
                    undefined,
                    finalMsg,
                  );
                  await saveChatLog(userId, "bot", "Mengirim form registrasi.");
                }
              } else if (resCek.data.status === "id_diklaim_orang_lain") {
                finalMsg = isAdmin
                  ? `⚠️ *[ADMIN INFO]*\nID \`${idTrading}\` *SUDAH DIKLAIM* oleh nomor ${resCek.data.no_tersensor}.`
                  : `❌ Maaf kak ${userName}, ID Trading ${idTrading} sudah diklaim oleh member lain dengan nomor ${resCek.data.no_tersensor}.`;
                await ctx.telegram.editMessageText(
                  ctx.chat.id,
                  sentLoadMsg.message_id,
                  undefined,
                  finalMsg,
                  { parse_mode: isAdmin ? "Markdown" : undefined },
                );
                await saveChatLog(userId, "bot", finalMsg);
              } else if (resCek.data.status === "kurang_deposit") {
                const depoPesan = isAdmin
                  ? `⚠️ *[ADMIN INFO]*\nID \`${idTrading}\` terdaftar, tapi *DEPOSIT KURANG*.\n_Pesan:_ ${resCek.data.pesan}`
                  : `⚠️ ${resCek.data.pesan}\n\n💡 _Kalo Kakak belum tau caranya, cukup ketik_ *"cara deposit"* _biar aku kirimin video panduannya ya!_\n\nKalo udah berhasil deposit, silakan kirim ulang **Screenshot Akunnya** atau ketik lagi angka ID-nya ke sini biar aku cek ulang! 📸`;

                await ctx.telegram.editMessageText(
                  ctx.chat.id,
                  sentLoadMsg.message_id,
                  undefined,
                  depoPesan,
                  { parse_mode: isAdmin ? "Markdown" : undefined },
                );
                await saveChatLog(userId, "bot", depoPesan);
              } else {
                finalMsg = isAdmin
                  ? `❌ *[ADMIN INFO]*\nID \`${idTrading}\` *TIDAK VALID* atau tidak terdaftar di IB kita.`
                  : resCek.data.pesan ||
                    "❌ ID Tidak Valid atau tidak ditemukan.";
                await ctx.telegram.editMessageText(
                  ctx.chat.id,
                  sentLoadMsg.message_id,
                  undefined,
                  finalMsg,
                  { parse_mode: isAdmin ? "Markdown" : undefined },
                );
                await saveChatLog(userId, "bot", finalMsg);
              }
            } catch (err) {
              const errorDetail = err.response
                ? JSON.stringify(err.response.data)
                : err.message;
              let errMsg =
                "❌ Server API lagi gangguan kak. Coba lagi nanti ya.";
              if (isAdmin)
                errMsg += `\n\n👨‍💻 *[DEBUG ADMIN]*\n_Gagal mengecek ID ${idTrading}_\n*Penyebab:* ${errorDetail}`;

              await ctx.telegram.editMessageText(
                ctx.chat.id,
                sentLoadMsg.message_id,
                undefined,
                errMsg,
                { parse_mode: "Markdown" },
              );
            }
            startTimeoutTimer(userId, userName);
            return;
          }

          // ==========================================
          // 3. OCR GAMBAR SCREENSHOT
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

              // URUTAN PRIORITAS DETEKSI GAMBAR OCR

              // Tarik Angka ID sedini mungkin dari gambar!
              const ocrMatches = ocrDetectedText.match(/\b\d{5,}\b/g);
              const uniqueIds = ocrMatches ? [...new Set(ocrMatches)] : [];
              const detectedId = uniqueIds.length === 1 ? uniqueIds[0] : "";
              const hasMultipleIds = uniqueIds.length > 1;

              // 1. CEK SS PINDAH IB BERHASIL (Lanjut ke Step 5: Arsip)
              if (
                ocrDetectedLower.includes("pembaruan berhasil") ||
                ocrDetectedLower.includes("update successful") ||
                ocrDetectedLower.includes(
                  "this campaign has already been assigned",
                ) ||
                ocrDetectedLower.includes("kampanye ini sudah ditetapkan")
              ) {
                const successMsg = `Mantap kak ${userName}, bukti pindah IB berhasil terdeteksi!\n\nSelanjutnya : \n\n1. arsipkan akun trading lamanya\n2. lalu buka akun trading baru ( bebas ) Pastikan Masukin kode IB g0olqxh5h3 di bagian introducing broker\n3. Lalu deposit minimal $10`;

                try {
                  await ctx.telegram.deleteMessage(
                    ctx.chat.id,
                    loadMsg.message_id,
                  );
                } catch (e) {}
                await sendSmartMessage(ctx, successMsg);
                userSteps[userId] = 5;
              }

              // 2. CEK SS ARSIP BERHASIL (Lanjut ke Step 6: Buka MT5 & Deposit)
              else if (
                ocrDetectedLower.includes("arsip") &&
                (ocrDetectedLower.includes("berhasil") ||
                  ocrDetectedLower.includes("sukses"))
              ) {
                const arsipMsg = `Mantap kak ${userName}, udah berhasil arsip akun!\n\nSekarang lanjut ke langkah berikutnya:\n1. Buka akun trading Live MT5 (bebas pilih tipe)\n2. Pastikan memasukkan Kode IB g0olqxh5h3\n3. Deposit minimal Rp100.000\n\n⚠️ Kalo udah deposit, Ketik Angka Akun ID trading barunya ke sini ya kak!\n\n[GAMBAR: ss_akunsayaapk]\n\n[GAMBAR: ss_akunsaya]`;

                try {
                  await ctx.telegram.deleteMessage(
                    ctx.chat.id,
                    loadMsg.message_id,
                  );
                } catch (e) {}
                await sendSmartMessage(ctx, arsipMsg);
                userSteps[userId] = 6;
              }

              // 3. JIKA ADA LEBIH DARI 1 ID TRADING (MULTIPLE IDs) -> SURUH ARSIPKAN
              else if (hasMultipleIds) {
                try {
                  await ctx.telegram.deleteMessage(
                    ctx.chat.id,
                    loadMsg.message_id,
                  );
                } catch (e) {}

                const multiIdMsg = `Waduh kak ${userName}, aku mendeteksi ada **Lebih dari 1 Akun Trading** di gambar ini nih! 😅\n\nBiar sistem nggak bingung dan datanya valid, Kakak wajib **MENGARSIPKAN** akun-akun yang lama dulu ya (sisakan 1 akun saja).\n\nCara Arsip Akun:\n1. G boleh ada Posisi yg berjalan\n2. Saldo Wajib 0 (pindahkan ke wallet dulu)\n3. Klik Akun yg mau diarsipkan, lalu klik 'Arsipkan'\n\n[VIDEO: arsipkan_akun]\n\nKalo udah diarsipkan semua, kirim Screenshot buktinya ke sini ya kak!`;

                await sendSmartMessage(ctx, multiIdMsg);
                userSteps[userId] = 5; // Arahkan ke Tahap Arsip
              }

              // 4. JIKA HANYA ADA TEPAT 1 ANGKA ID TRADING -> WAJIB CEK API DULU!
              else if (detectedId) {
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
                    ciUrl(`/client/cekHfm/${encodeURIComponent(detectedId)}/CHECK_ONLY`),
                  );

                  if (resCek.data.status === "valid_sempurna") {
                    userSteps[userId] = 7; // Lolos API!
                    userTempData[userId] = detectedId;
                    const finalMsg = `Selamat kak ${userName}! Akun dan deposit sudah masuk under BO$$CUAN 🎉\n\nSekarang silahkan lengkapi data anda dibawah ini.\n\nNama :\nNo wa aktif :\nemail :\n\nSetelah melengkapi data, kami akan mengundang anda ke grup VIP WARGA AVATAR.`;

                    await ctx.telegram.editMessageText(
                      ctx.chat.id,
                      cekMsg.message_id,
                      undefined,
                      finalMsg,
                    );
                  } else if (resCek.data.status === "id_diklaim_orang_lain") {
                    await ctx.telegram.editMessageText(
                      ctx.chat.id,
                      cekMsg.message_id,
                      undefined,
                      `❌ Maaf kak, ID Trading ${detectedId} sudah diklaim oleh member lain dengan nomor ${resCek.data.no_tersensor}.`,
                    );
                  } else if (resCek.data.status === "kurang_deposit") {
                    const depoPesan = `⚠️ ${resCek.data.pesan}\n\n💡 _Kalo Kakak belum tau caranya, cukup ketik_ *"cara deposit"* _biar aku kirimin video panduannya ya!_\n\nKalo udah berhasil deposit, silakan **kirim ulang Screenshot Akunnya** atau ketik lagi angka ID-nya ke sini biar aku cek ulang! 📸`;

                    await ctx.telegram.editMessageText(
                      ctx.chat.id,
                      cekMsg.message_id,
                      undefined,
                      depoPesan,
                      { parse_mode: "Markdown" },
                    );
                  } else {
                    // ID TIDAK VALID / BELUM DI BAWAH IB -> KASIH PANDUAN PINDAH IB
                    const invalidMsg = `❌ Maaf kak ${userName}, ID Trading ${detectedId} belum terdaftar di bawah IB kita.\n\nJika Kakak belum pindah IB, yuk ikuti panduan berikut:\n\nLogin via Web: http://hftidtrade.com\n\nCara Pindah IB:\n1. Klik Dompet Saya\n2. Klik Aksi Wallet\n3. Klik set ID Mitra & Kampanye\n4. Ketik g0olqxh5h3\n5. Klik Tambah & tetapkan Ulang.\n\n[VIDEO: pindah_ib]`;

                    await ctx.telegram
                      .deleteMessage(ctx.chat.id, cekMsg.message_id)
                      .catch(() => {});
                    await sendSmartMessage(ctx, invalidMsg);
                    userSteps[userId] = 4; // Kembalikan ke Tahap Pindah IB
                  }
                } catch (err) {
                  const errorDetail = err.response
                    ? JSON.stringify(err.response.data)
                    : err.message;
                  let errMsg =
                    "❌ Server API lagi gangguan kak. Coba lagi nanti ya.";
                  const adminIdDb = dynamicAdminId || "";
                  if (userId.toString() === adminIdDb.toString().trim()) {
                    errMsg += `\n\n👨‍💻 *[DEBUG ADMIN]*\n_Gagal mengecek ID ${detectedId}_\n*Penyebab:* ${errorDetail}`;
                  }
                  await ctx.telegram.editMessageText(
                    ctx.chat.id,
                    cekMsg.message_id,
                    undefined,
                    errMsg,
                    { parse_mode: "Markdown" },
                  );
                }
              }

              // 5. JIKA GAMBAR TIDAK ADA ANGKA ID (BURAM), TAPI ADA KEYWORD TAMPILAN AKUN
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

              // 6. GAGAL BACA SEMUA (Tidak ada ID dan Tidak ada Keyword)
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
            const welcomeMsg = replaceTags(
              botSettings.globals.KATA_SAPAAN ||
                `Halo Kak {NAMA_USER}, Selamat datang di layanan pendaftaran VIP!\nApakah Kakak berminat untuk bergabung ke grup kita?`,
              userName,
            );
            await ctx.reply(welcomeMsg);
            await saveChatLog(userId, "bot", welcomeMsg);
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
            startTimeoutTimer(userId, userName);
            return;
          }

          // 5. MATCHING RULE, FAQ & FLOW
          let matched = false;
          for (const rule of [...botSettings.faqs, ...botSettings.flows]) {
            if (!rule.keywords && !rule.trigger_keywords) continue;
            const keywordsArr = (rule.keywords || rule.trigger_keywords)
              .split(",")
              .map((k) => k.trim())
              .filter((k) => k);
            const escapedKeywords = keywordsArr.map((keyword) => keyword.replace(/[.*+?^${}()|[\]\\]/g, "\\$&"));
            const regex = new RegExp(`\\b(${escapedKeywords.join("|")})\\b`, "i");

            if (regex.test(textMessage)) {
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
              const loadingMsg = `Maaf kak ${userName}, sistemku lagi loading nih 🙏\n\nSilakan ketik /start untuk mengulang dari awal ya!`;
              await ctx.reply(loadingMsg);
              await saveChatLog(userId, "bot", loadingMsg);
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

    bot.on("left_chat_member", async (ctx) => {
      if (ctx.chat.id.toString() === ID_GRUP_VIP) {
        try {
          await axios.get(
            ciUrl(`/client/apiUpdateStatusMemberGrup/${encodeURIComponent(ctx.message.left_chat_member.id)}/keluar_sendiri`),
          );
        } catch (e) {}
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

setInterval(fetchConfig, 5 * 60 * 1000);
initTelegramBot();
process.once("SIGINT", () => bot && bot.stop("SIGINT"));
process.once("SIGTERM", () => bot && bot.stop("SIGTERM"));
