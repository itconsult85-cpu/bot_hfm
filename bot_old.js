const { Telegraf } = require("telegraf");
const mysql = require("mysql2/promise");
const express = require("express");
const cors = require("cors");
const axios = require("axios");
const cron = require("node-cron");
const Tesseract = require("tesseract.js");
const { GoogleGenerativeAI } = require("@google/generative-ai");
const { execFile } = require("child_process");
const crypto = require("crypto");

// 1. EXPRESS & PORT MANAGEMENT
const app = express();
const API_PORT = 3000;
app.use(express.json());
app.use(cors());

app.post("/api/restart-bot", (req, res) => {
  if (!requireControlToken(req,res)) return;
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
  if (!requireControlToken(req,res)) return;
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
  if (!requireControlToken(req,res)) return;
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

app.post('/api/kick-lepas-ib-members',async(req,res)=>{
  await fetchConfig(); if(!requireControlToken(req,res))return; if(!bot)return res.status(503).json({status:"failed",error:"Bot Telegram belum terhubung."}); if(bulkKickLepasIbRunning)return res.status(409).json({status:"failed",error:"Proses kick massal sedang berjalan."}); bulkKickLepasIbRunning=true;
  try { const g=await groupConfig(); if(!g.id)return res.status(409).json({status:"failed",error:"ID grup Telegram belum dikonfigurasi."});
    const [rows]=await db.query("SELECT DISTINCT TRIM(id_telegram) telegram_id FROM tb_member_vip WHERE status='lepas_ib' AND id_telegram IS NOT NULL AND TRIM(id_telegram) REGEXP '^[0-9][0-9]*' UNION SELECT DISTINCT TRIM(id_telegram) telegram_id FROM tb_member_logs WHERE tipe_aktivitas='lepas_ib' AND id_telegram IS NOT NULL AND TRIM(id_telegram) REGEXP '^[0-9][0-9]*'");
    const result={status:"success",group_name:g.name,candidates:rows.length,kicked:0,skipped:0,failed:0,failed_ids:[]};
    for(const row of rows){const id=String(row.telegram_id);try{const m=await bot.telegram.getChatMember(g.id,id);if(["left","kicked","creator","administrator"].includes(m.status)||(m.status==="restricted"&&m.is_member===false)){result.skipped++;continue;}if(!["member","restricted"].includes(m.status)){result.skipped++;continue;}await bot.telegram.banChatMember(g.id,id);await bot.telegram.unbanChatMember(g.id,id);await recordGroupMember(g,m.user||{id},"left");result.kicked++;}catch(e){result.failed++;if(result.failed_ids.length<20)result.failed_ids.push(id);console.error("[BULK KICK]",id,e.message);}await delay(300);}
    return res.json(result);
  } catch(e){console.error("[BULK KICK ERROR]",e.message);return res.status(502).json({status:"failed",error:"Gagal menjalankan kick massal."});} finally {bulkKickLepasIbRunning=false;}
});

// ENDPOINT UNTUK RESET MEMORI USER (DARI WEB ADMIN)
app.post("/reset-user", async (req, res) => {
  const { user_id } = req.body;

  if (!user_id) {
    return res.status(400).json({ error: "User ID kosong." });
  }

  try {
    // Menghapus semua ingatan bot tentang user ini dari RAM
    delete userSteps[user_id];
    delete userTempData[user_id];
    delete userImagesCount[user_id];

    if (userTimeouts[user_id]) {
      clearTimeout(userTimeouts[user_id].timeout);
      delete userTimeouts[user_id];
    }

    console.log(`[BOT API] Memori user ${user_id} sukses di-reset dari Web!`);
    res.json({ status: "success", message: "Memori bot berhasil di-reset." });
  } catch (error) {
    console.error(
      `[BOT API ERROR] Gagal reset user ${user_id}:`,
      error.message,
    );
    res.status(500).json({ error: "Gagal mereset memori bot." });
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
let bulkKickLepasIbRunning = false;

const antrianBot = [];
let sedangAntriBot = false;
const delay = (ms) => new Promise((res) => setTimeout(res, ms));
function requireControlToken(req,res){
  const expected=String(botSettings.globals.BOT_CONTROL_TOKEN||'').trim();
  const supplied=String(req.get('x-bot-control-token')||req.body?.token||'').trim();
  if(!expected||supplied.length!==expected.length){res.status(401).json({status:"failed",error:"Unauthorized."});return false;}
  if(!crypto.timingSafeEqual(Buffer.from(supplied),Buffer.from(expected))){res.status(401).json({status:"failed",error:"Unauthorized."});return false;}
  return true;
}
async function groupConfig(){
  try { const [r]=await db.query("SELECT group_id,group_name,invite_link FROM bot_group_links WHERE platform = 'telegram' AND is_active=1 LIMIT 1"); if(r.length&&r[0].group_id) return {id:String(r[0].group_id),name:r[0].group_name||"Grup Telegram VIP",inviteLink:r[0].invite_link||""}; } catch(_) {}
  return {id:String(botSettings.globals.ID_GRUP_VIP||""),name:botSettings.globals.NAMA_GRUP_VIP||"Grup Telegram VIP",inviteLink:botSettings.globals.LINK_GRUP_VIP||""};
}
async function createVipInviteLink(){
  const group=await groupConfig();
  if(!group.id) throw new Error("ID grup Telegram belum dikonfigurasi.");
  const invite=await bot.telegram.createChatInviteLink(group.id,{expire_date:Math.floor(Date.now()/1000)+60,member_limit:1});
  return invite.invite_link;
}
async function recordGroupMember(group,member,status){
  try { const id=String(member.id); const name=[member.first_name,member.last_name].filter(Boolean).join(" ").slice(0,150)||member.username||"Pengguna Telegram"; const [v]=await db.query("SELECT no_wa FROM tb_member_vip WHERE id_telegram=? LIMIT 1",[id]);
  await db.query("INSERT INTO telegram_group_members (telegram_id,phone_number,display_name,group_id,group_name,status,joined_at,left_at) VALUES (?,?,?,?,?,?,NOW(),?) ON DUPLICATE KEY UPDATE phone_number=VALUES(phone_number),display_name=VALUES(display_name),group_name=VALUES(group_name),status=VALUES(status),joined_at=IF(VALUES(status)='active',NOW(),joined_at),left_at=IF(VALUES(status)='left',NOW(),NULL),updated_at=NOW()",[id,v[0]?.no_wa||null,name,String(group.id),group.name,status,status==='left'?new Date():null]); } catch(e){console.error("[GROUP MEMBER ERROR]",e.message);}
}
async function welcomeValidated(ctx,member,group){
  try { const id=String(member.id); const check=await axios.get("http://103.89.4.144/bot_wa/client/check_status_vip/"+encodeURIComponent(id),{timeout:5000}); if(check.data?.is_vip!==true)return; const [old]=await db.query("SELECT status FROM telegram_group_members WHERE telegram_id=? AND group_id=? LIMIT 1",[id,group.id]); const back=old[0]?.status==='left'; const key=back?"WELCOME_BACK_MESSAGE":"WELCOME_MESSAGE"; const fallback=back?"Selamat Datang Kembali {NAMA_USER}, pastikan membaca rules yang ada di dalam group, pahami dan lakukan.":"Selamat Datang {NAMA_USER}, pastikan membaca rules yang ada di dalam group, pahami dan lakukan."; const msg=replaceTags(botSettings.globals[key]||fallback,member.first_name||member.username||"Kak"); await ctx.reply(msg,{disable_web_page_preview:true}); await saveChatLog(id,"bot",msg); } catch(e){console.error("[WELCOME ERROR]",e.message);}
}

// 4. CONFIG LOADER & DATABASE HELPER
async function fetchConfig() {
  try {
    const [globalRows] = await db.query(
      "SELECT key_name, key_value FROM bot_globals",
    );
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

// 8. INISIALISASI BOT & CORE ENGINE
async function initTelegramBot() {
  try {
    await fetchConfig();
    const TELEGRAM_TOKEN = botSettings.globals.TELEGRAM_TOKEN;
    if (!TELEGRAM_TOKEN) return;

    bot = new Telegraf(TELEGRAM_TOKEN);
    botStatus = { status: "Connected via Database" };
    let ID_GRUP_VIP = (await groupConfig()).id;

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

          if (typeof userSteps[userId] === "undefined") userSteps[userId] = 1;
          let currentStep = userSteps[userId];

          // FITUR ADMIN: AMBIL ALIH CHAT (BALAS MANUAL)
          const adminIdDb = botSettings.globals.ID_ADMIN || "";
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
            const urlCek = `http://103.89.4.144/bot_wa/client/check_status_vip/${userId}`;
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
                const adminIdDb = botSettings.globals.ID_ADMIN || "";
                if (!isAdmin) {
                  const vipReply = `Halo kak ${userName}! Kakak kan sudah tergabung di grup VIP kita nih 🎉\n\nJika ada pertanyaan, kendala seputar trading, atau butuh panduan lebih lanjut, silakan langsung hubungi Admin Utama ya!\n\n👉 [Klik di sini untuk Chat Admin Utama](tg://user?id=${adminIdDb})`;
                  await ctx.reply(vipReply, { parse_mode: "Markdown" });
                }
                userSteps[userId] = 7;
                startTimeoutTimer(userId, userName);
                return;
              } else {
                const inviteLink = await createVipInviteLink();

                const sentMsg = await ctx.reply(
                  `Halo kak ${userName}! Status VIP kakak sudah aktif kok 🎉\n\nIni link grup VIP yang baru untuk kakak ya. Yuk buruan diklik sebelum hilang lagi!\n👉 ${inviteLink}\n\n_(Pesan otomatis ditarik dlm 1 menit)_`,
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
                const inviteLink = await createVipInviteLink();

                const suksesMsg = `Selamat kak ${dataForm.nama}! Data berhasil diverifikasi.\n\nSilakan bergabung melalui link Grup VIP di bawah ini ya:\n👉 ${inviteLink}\n\n_(Pesan otomatis ditarik dlm 1 menit)_`;

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
                  finalMsg = `✅ *[ADMIN INFO]*\nID Trading \`${idTrading}\` statusnya *VALID* dan terdeteksi di IB BOSSCUAN!`;
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
                finalMsg = isAdmin
                  ? `⚠️ *[ADMIN INFO]*\nID \`${idTrading}\` *SUDAH DIKLAIM* oleh nomor ${resCek.data.no_tersensor}.`
                  : `❌ Maaf kak ${userName}, ID Trading ${idTrading} sudah diklaim oleh member lain dengan nomor ${resCek.data.no_tersensor}.`;
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
            const welcomeMsg = replaceTags(
              botSettings.globals.KATA_SAPAAN ||
                `Halo Kak {NAMA_USER}, Selamat datang di layanan pendaftaran VIP!\nApakah Kakak berminat untuk bergabung ke grup kita?`,
              userName,
            );
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
          for (const rule of [...botSettings.faqs, ...botSettings.flows]) {
            if (!rule.keywords && !rule.trigger_keywords) continue;
            const keywordsArr = (rule.keywords || rule.trigger_keywords)
              .split(",")
              .map((k) => k.trim())
              .filter((k) => k);
            const regex = new RegExp(`\\b(${keywordsArr.join("|")})\\b`, "i");

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

    bot.on('new_chat_members',async(ctx)=>{const g=await groupConfig();if(!g.id||String(ctx.chat.id)!==g.id)return;for(const m of ctx.message.new_chat_members||[]){if(m.is_bot)continue;await welcomeValidated(ctx,m,g);await recordGroupMember(g,m,'active');}});

    bot.on("left_chat_member", async (ctx) => {
      if (ctx.chat.id.toString() === ID_GRUP_VIP) {
        const leftMember = ctx.message.left_chat_member;
        if (leftMember && !leftMember.is_bot) {
          await recordGroupMember(
            { id: ID_GRUP_VIP, name: botSettings.globals.NAMA_GRUP_VIP || ctx.chat.title },
            leftMember,
            "left",
          );
        }
        try {
          await axios.get(
            `http://103.89.4.144/bot_wa/client/apiUpdateStatusMemberGrup/${ctx.message.left_chat_member.id}/keluar_sendiri`,
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
