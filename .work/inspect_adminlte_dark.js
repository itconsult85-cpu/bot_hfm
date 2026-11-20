const fs = require('fs');
const css = fs.readFileSync('public/assets/vendor/admin-lte/adminlte.min.css', 'utf8');
for (const term of ['data-bs-theme', 'dark-mode', '--bs-body-bg', '--bs-tertiary-bg', 'color-scheme']) {
  console.log(`${term}: ${css.split(term).length - 1} occurrence(s)`);
}
const terms = ['data-bs-theme=dark', '[data-bs-theme="dark"]', 'dark-mode'];
for (const term of terms) {
  const i = css.indexOf(term);
  console.log(`\n${term}: ${i >= 0 ? css.slice(Math.max(0, i - 180), i + 360) : 'not found'}`);
}
