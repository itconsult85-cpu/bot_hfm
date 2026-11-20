const fs = require('fs');
const path = require('path');

const required = [
  'public/assets/vendor/admin-lte/adminlte.min.css',
  'public/assets/vendor/admin-lte/adminlte.min.js',
  'public/assets/vendor/bootstrap/bootstrap.min.js',
  'public/assets/vendor/popper/popper.min.js',
  'public/assets/vendor/overlayscrollbars/overlayscrollbars.min.css',
  'public/assets/vendor/overlayscrollbars/overlayscrollbars.browser.es6.min.js',
  'public/assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css',
  'public/assets/vendor/bootstrap-icons/font/fonts/bootstrap-icons.woff2',
  'public/assets/vendor/bootstrap-icons/font/fonts/bootstrap-icons.woff',
  'public/assets/vendor/jquery/jquery-3.7.1.min.js',
  'public/assets/vendor/datatables/jquery.dataTables.min.js',
  'public/assets/vendor/datatables/dataTables.bootstrap5.min.js',
  'public/assets/vendor/datatables/i18n/id.json',
  'public/assets/vendor/chartjs/chart.js',
  'public/assets/vendor/qrcode/qrcode.min.js',
];
for (const file of required) {
  if (!fs.existsSync(file) || fs.statSync(file).size === 0) {
    throw new Error(`Missing/empty asset: ${file}`);
  }
}

const language = JSON.parse(fs.readFileSync('public/assets/vendor/datatables/i18n/id.json', 'utf8'));
if (!language || typeof language !== 'object') throw new Error('DataTables id.json is not an object');

const iconCss = 'public/assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css';
const iconText = fs.readFileSync(iconCss, 'utf8');
for (const match of iconText.matchAll(/url\(([^)]+)\)/g)) {
  const url = match[1].trim().replace(/^['"]|['"]$/g, '');
  if (url.startsWith('data:')) continue;
  if (/^https?:\/\//i.test(url)) throw new Error(`Remote font URL remains: ${url}`);
  const local = path.resolve(path.dirname(iconCss), url.split('?')[0]);
  if (!fs.existsSync(local)) throw new Error(`Font referenced by CSS is missing: ${local}`);
}

const scanRoots = ['app/Views', 'public/assets/css'];
const remoteAsset = /<script[^>]+\bsrc\s*=\s*["']https?:\/\/|<link[^>]+\bhref\s*=\s*["']https?:\/\/|url\(\s*["']?https?:\/\/|@import\s+["']?https?:\/\//i;
function scan(dir) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) scan(full);
    else if (/\.(php|css|html)$/i.test(entry.name)) {
      const content = fs.readFileSync(full, 'utf8');
      if (remoteAsset.test(content)) throw new Error(`External UI asset reference remains in ${full}`);
    }
  }
}
for (const root of scanRoots) scan(root);

const total = required.reduce((sum, file) => sum + fs.statSync(file).size, 0);
console.log(`PASS: ${required.length} vendor assets exist; JSON and icon font paths are valid; no remote CSS/script/image imports in scanned views/custom CSS. Asset bytes=${total}.`);
