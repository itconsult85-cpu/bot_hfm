// File ini berada di scripts/, sedangkan .env berada di root folder bot.
require("dotenv").config({ path: require("path").resolve(__dirname, "..", ".env") });

const readline = require("readline");
const { TelegramClient } = require("telegram");
const { StringSession } = require("telegram/sessions");

function ask(question) {
  const rl = readline.createInterface({ input: process.stdin, output: process.stdout });
  return new Promise((resolve) => rl.question(question, (answer) => {
    rl.close();
    resolve(answer.trim());
  }));
}

async function main() {
  const apiId = Number(process.env.MTPROTO_API_ID || await ask("MTPROTO_API_ID: "));
  const apiHash = String(process.env.MTPROTO_API_HASH || await ask("MTPROTO_API_HASH: ")).trim();
  if (!apiId || !apiHash) throw new Error("API ID dan API Hash wajib diisi.");

  const client = new TelegramClient(new StringSession(""), apiId, apiHash, {
    connectionRetries: 5,
  });

  await client.start({
    phoneNumber: async () => ask("Nomor Telegram akun khusus (+kode negara): "),
    phoneCode: async () => ask("Kode OTP Telegram: "),
    password: async () => ask("Password 2FA Telegram (kosong jika tidak ada): "),
    onError: (error) => console.error("Login gagal:", error.message),
  });

  console.log("\nLogin berhasil. Simpan nilai berikut ke .env dan jangan dibagikan:");
  console.log(`MTPROTO_SESSION=${client.session.save()}`);
  await client.disconnect();
}

main().catch((error) => {
  console.error(error.message);
  process.exitCode = 1;
});
