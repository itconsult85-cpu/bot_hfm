const fs = require('fs');

const css = fs.readFileSync('public/assets/css/dashboard.css', 'utf8');
let state = 'code';
let quote = '';
let escaped = false;
const stack = [];
const pairs = { ')': '(', ']': '[', '}': '{' };
for (let i = 0; i < css.length; i++) {
  const ch = css[i];
  const next = css[i + 1];
  if (state === 'comment') {
    if (ch === '*' && next === '/') { state = 'code'; i++; }
    continue;
  }
  if (state === 'string') {
    if (escaped) { escaped = false; continue; }
    if (ch === '\\') { escaped = true; continue; }
    if (ch === quote) state = 'code';
    continue;
  }
  if (ch === '/' && next === '*') { state = 'comment'; i++; continue; }
  if (ch === '"' || ch === "'") { state = 'string'; quote = ch; continue; }
  if ('([{'.includes(ch)) stack.push({ ch, i });
  else if (')]}'.includes(ch)) {
    const open = stack.pop();
    if (!open || open.ch !== pairs[ch]) throw new Error(`Unbalanced CSS token ${ch} at ${i}`);
  }
}
if (stack.length) throw new Error(`Unclosed CSS token ${stack[stack.length - 1].ch}`);
if (state !== 'code') throw new Error(`Unclosed CSS ${state}`);

for (const file of [
  'app/Views/layout/template.php',
  'app/Views/auth_login.php',
  'app/Views/admin/dashboard_vip.php',
]) {
  const html = fs.readFileSync(file, 'utf8');
  if (!html.includes('<html lang="id" data-bs-theme="dark">')) {
    throw new Error(`Dark color mode missing in ${file}`);
  }
}

const walletView = fs.readFileSync('app/Views/admin_campaign_wallets.php', 'utf8');
const start = walletView.indexOf('new Chart(ctx, {');
const endMarker = '        });';
const end = walletView.indexOf(endMarker, start);
if (start < 0 || end < 0) throw new Error('Could not locate Chart.js configuration');
let chartScript = walletView.slice(start, end + endMarker.length);
chartScript = chartScript
  .replace(/<\?=\s*json_encode\(\$dates\)\s*\?>/g, '[]')
  .replace(/<\?=\s*json_encode\(\$counts\)\s*\?>/g, '[]');
new Function(`const ctx = {}; function Chart() {}\n${chartScript}`);

console.log('PASS: CSS delimiters balanced; dark mode roots present; Chart.js config parses.');
