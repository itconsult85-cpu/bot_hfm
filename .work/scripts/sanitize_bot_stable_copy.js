const fs = require("node:fs");
const path = require("node:path");

const root = path.resolve(__dirname, "..", "..");
const file = path.join(root, "bot_stable.js");
let source = fs.readFileSync(file, "utf8");
const newline = source.includes("\r\n") ? "\r\n" : "\n";
source = source.replace(/\r\n/g, "\n");
function replaceOnce(search, replacement, label) {
  const index = source.indexOf(search);
  if (index < 0 || source.indexOf(search, index + search.length) >= 0) {
    throw new Error(`Expected one ${label} block.`);
  }
  source = source.slice(0, index) + replacement + source.slice(index + search.length);
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
replaceOnce('const cors = require("cors");\n', "", "cors import");
replaceOnce("app.use(cors());\n", "", "cors middleware");
replaceOnce("const API_PORT = 3000;", "const API_PORT = Number(process.env.API_PORT || 3000);", "API_PORT setting");
replaceOnce("app.listen(API_PORT, () => {", 'app.listen(API_PORT, "127.0.0.1", () => {', "API listener");

const dbPattern = /\/\/ 2\. KONEKSI DATABASE MYSQL\nconst db = mysql\.createPool\(\{[\s\S]*?\n\}\);/;
if (!dbPattern.test(source)) throw new Error("Database pool not found.");
source = source.replace(dbPattern, [
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

const restartPattern = /  const rawToken =\n    botSettings\.globals\.BOT_CONTROL_TOKEN \|\| "[^"]+";\n  const configuredToken = String\(rawToken\)\.trim\(\);\n\n  if \(!req\.body \|\| req\.body\.token !== configuredToken\) \{\n    return res\.status\(403\)\.json\(\{ error: "Token restart tidak valid\." \}\);\n  \}/;
if (!restartPattern.test(source)) throw new Error("Restart endpoint token fallback block not found.");
source = source.replace(restartPattern, '  if (!hasValidControlToken(req)) {\n    return res.status(401).json({ status: "failed", error: "Unauthorized." });\n  }');
const reportPattern = /  const configuredToken = String\(botSettings\.globals\.BOT_CONTROL_TOKEN \|\| ""\);\n  if \(!configuredToken \|\| req\.body\?\.token !== configuredToken\) \{\n    return res\.status\(403\)\.json\(\{ error: "Token laporan tidak valid\." \}\);\n  \}/;
if (!reportPattern.test(source)) throw new Error("Daily report token comparison block not found.");
source = source.replace(reportPattern, '  if (!hasValidControlToken(req)) {\n    return res.status(401).json({ status: "failed", error: "Unauthorized." });\n  }');

if (/password\s*:\s*["'][^"']+["']|token_rahasia_bot_123/.test(source)) {
  throw new Error("A hard-coded credential remains in the sanitized stable copy.");
}
fs.writeFileSync(file, source.replace(/\n/g, newline), "utf8");
console.log("bot_stable.js sanitized: database settings now come from environment variables.");
