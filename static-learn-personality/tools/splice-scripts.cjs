const fs = require('fs');
const path = require('path');
const p = path.join(__dirname, '..', 'index.html');
let s = fs.readFileSync(p, 'utf8');
const start = s.indexOf('  <script>\n    const dimensionMeta');
const end = s.lastIndexOf('</script>');
if (start < 0 || end < 0) throw new Error('markers not found');
const rep =
  '  <script src="data/questions.js"></script>\n' +
  '  <script src="data/types.js"></script>\n' +
  '  <script src="app.js"></script>\n';
s = s.slice(0, start) + rep + s.slice(end + 9);
fs.writeFileSync(p, s);
console.log('Spliced index.html scripts');
