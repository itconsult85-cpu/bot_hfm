const fs = require("fs");
const source = fs.readFileSync("bot.js", "utf8");
const start = source.indexOf("function formatAdminLowDepositMessage(");
const end = source.indexOf("function isAdminHelpKeyword(", start);
const format = Function(source.slice(start, end) + "; return formatAdminLowDepositMessage")();
const sample = `Saldo/Deposit Kakak saat ini 1 USC.
Untuk bisa bergabung di Grup VIP silakan isi saldo minimal 548 USC (sekitar $5,48 USD), kurs acuan USD/IDR 18.258 per 1 USD, tanggal 2026-11-22 (setara Rp100.000).`;
console.log(format("205222092", sample, "Nicholas Keane"));
