const fs = require('fs');

const cssFiles = [
  '.work/vendor-staging/admin-lte/adminlte.min.css',
  '.work/vendor-staging/overlayscrollbars/overlayscrollbars.min.css',
  '.work/vendor-staging/bootstrap-icons/css/bootstrap-icons.min.css',
];
for (const file of cssFiles) {
  const css = fs.readFileSync(file, 'utf8');
  const urls = [...css.matchAll(/url\(([^)]+)\)/g)]
    .map((match) => match[1].trim().replace(/^['"]|['"]$/g, ''));
  const usedUrls = [...new Set(urls.filter((url) => !url.startsWith('data:')))];
  const imports = [...css.matchAll(/@import[^;]+/g)].map((match) => match[0]);
  console.log(`\n${file}\n  urls=${JSON.stringify(usedUrls)}\n  imports=${JSON.stringify(imports)}`);
}

const scriptFiles = [
  '.work/vendor-staging/admin-lte/adminlte.min.js',
  '.work/vendor-staging/bootstrap/bootstrap.min.js',
  '.work/vendor-staging/overlayscrollbars/overlayscrollbars.browser.es6.min.js',
  '.work/vendor-staging/bootstrap-icons/css/bootstrap-icons.min.css',
  '.work/vendor-staging/chartjs/chart.js',
  '.work/vendor-staging/qrcode/qrcode.min.js',
  '.work/vendor-staging/jquery/jquery-3.7.1.min.js',
  '.work/vendor-staging/datatables/jquery.dataTables.min.js',
];
for (const file of scriptFiles) {
  const content = fs.readFileSync(file, 'utf8');
  console.log(`\nHEADER ${file}: ${content.slice(0, 230).replace(/\s+/g, ' ')}`);
}
