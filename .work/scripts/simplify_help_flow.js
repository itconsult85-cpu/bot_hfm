const fs = require("node:fs");
const path = require("node:path");
const file = path.resolve(__dirname, "..", "..", "bot.js");
let source = fs.readFileSync(file, "utf8");
const newline = source.includes("\r\n") ? "\r\n" : "\n";
source = source.replace(/\r\n/g, "\n");

const startNeedle = "// BANTUAN ADMIN: hanya teruskan riwayat dan pesan user kepada admin.";
const endNeedle = "// 1.5 PENANGANAN FORMULIR & ID TRADING (AUTO DETEKSI DARI STEP MANA PUN)";
const commentIndex = source.indexOf(startNeedle);
const stageIndex = source.indexOf(endNeedle, commentIndex);
if (commentIndex < 0 || stageIndex <= commentIndex) {
  throw new Error("Could not locate the existing help and VIP status handler block.");
}
const start = source.lastIndexOf("\n", commentIndex) + 1;
const end = source.lastIndexOf("\n", stageIndex) + 1;

const replacement = [
  "          // Bantuan admin dikirim sebagai snapshot riwayat hanya saat user meminta bantuan.",
  "          const userKey = String(userId);",
  "          if (!isAdmin && invitedUsers.has(userKey)) {",
  "            const completedMessage = directAdminContactMessage();",
  '            await ctx.reply(completedMessage, { parse_mode: "HTML", reply_markup: { remove_keyboard: true } });',
  '            await saveChatLog(userId, "bot", "Pendaftaran selesai; arahkan pertanyaan lanjutan langsung ke admin.");',
  "            return;",
  "          }",
  "",
  "          // Status VIP diperiksa sebelum tombol bantuan agar user yang sudah tervalidasi tidak membuka bantuan lagi.",
  "          if (!isAdmin) {",
  "            try {",
  "              const urlCek = `http://103.89.4.144/bot_wa/client/check_status_vip/${userId}`;",
  "              const checkStatus = await axios.get(urlCek, { timeout: 5000 });",
  "              if (checkStatus.data?.is_vip === true) {",
  "                let isMember = false;",
  "                try {",
  "                  const member = await bot.telegram.getChatMember(ID_GRUP_VIP, Number.parseInt(userId, 10));",
  '                  isMember = ["creator", "administrator", "member"].includes(member.status);',
  "                } catch (memberError) {",
  '                  console.warn("[VIP MEMBERSHIP CHECK]", memberError.message);',
  "                }",
  "                userSteps[userId] = 7;",
  "                if (isMember) {",
  "                  invitedUsers.add(userKey);",
  "                  const completedMessage = directAdminContactMessage();",
  '                  await ctx.reply(completedMessage, { parse_mode: "HTML", reply_markup: { remove_keyboard: true } });',
  '                  await saveChatLog(userId, "bot", "Status VIP aktif; menu bantuan bot ditutup.");',
  "                  return;",
  "                }",
  "",
  "                const inviteLink = await bot.telegram.createChatInviteLink(ID_GRUP_VIP, {",
  "                  expire_date: Math.floor(Date.now() / 1000) + 60,",
  "                  member_limit: 1,",
  "                });",
  "                const sentMsg = await ctx.reply(",
  "                  `Status VIP kakak sudah aktif. Silakan bergabung melalui link ini:\\n${inviteLink.invite_link}\\n\\nUntuk bantuan setelah bergabung, hubungi admin secara langsung.`,",
  "                  { reply_markup: { remove_keyboard: true } },",
  "                );",
  "                invitedUsers.add(userKey);",
  '                await saveChatLog(userId, "bot", "Status VIP aktif; link undangan dikirim dan menu bantuan bot ditutup.");',
  "                setTimeout(async () => {",
  "                  try {",
  "                    await bot.telegram.deleteMessage(userId, sentMsg.message_id);",
  "                  } catch (deleteError) {}",
  "                }, 60000);",
  "                return;",
  "              }",
  "            } catch (statusError) {",
  '              console.error("[VIP STATUS CHECK ERROR]", statusError.message);',
  "            }",
  "          }",
  "",
  "          if (isAdmin && /^\\/balas(?:\\s|$)/i.test(textMessage.trim())) {",
  '            await ctx.reply("Balasan melalui bot dinonaktifkan. Hubungi user langsung di Telegram menggunakan ID pada laporan bantuan.");',
  "            return;",
  "          }",
  "",
  "          if (!isAdmin && isAdminHelpKeyword(textMessage)) {",
  "            await sendAdminHelpReport(ctx, userKey, userName, currentStep);",
  "            return;",
  "          }",
  "",
  "          if (!isAdmin && isConfusionOrHumanHelpRequest(textMessage)) {",
  '            const helpChoice = "Saya coba bantu dulu ya, Kak. Jika masih terkendala dan ingin dibantu admin, tekan tombol di bawah atau ketik: tolong saya admin.";',
  "            await ctx.reply(helpChoice, { reply_markup: adminHelpKeyboard() });",
  '            await saveChatLog(userId, "bot", helpChoice);',
  "          }",
  "",
].join("\n");

source = source.slice(0, start) + replacement + source.slice(end);
if (/\/selesai|adminAssistedUsers|activeAdminTargetId|forwardMessage\(\s*adminIdDb/.test(source)) {
  throw new Error("A removed close command or continuous relay path remains in bot.js.");
}
if (!source.includes("invitedUsers.add(userKey)")) throw new Error("Invite-completion state was not added.");
fs.writeFileSync(file, source.replace(/\n/g, newline), "utf8");
console.log("bot.js handler simplified: one-shot history reports, no close/follow-up relay, and invite-completion chat gate.");
