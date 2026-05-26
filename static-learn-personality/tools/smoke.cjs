const fs = require('fs');
const path = require('path');
const root = path.join(__dirname, '..');
const QUESTION_BANK = new Function(
  fs.readFileSync(path.join(root, 'data', 'questions.js'), 'utf8') + '\nreturn QUESTION_BANK;'
)();
const { TYPE_PATTERNS, TYPE_LIBRARY } = new Function(
  fs.readFileSync(path.join(root, 'data', 'types.js'), 'utf8') +
    '\nreturn { TYPE_PATTERNS, TYPE_LIBRARY };'
)();

if (QUESTION_BANK.length !== 200) throw new Error('bank size');
for (const m of [1, 2, 3, 4]) {
  const n = QUESTION_BANK.filter((q) => q.module === m).length;
  if (n !== 50) throw new Error('module ' + m + ' count ' + n);
}

if (TYPE_PATTERNS.length !== 25) throw new Error('patterns');
for (const t of TYPE_PATTERNS) {
  if (t.pattern.length !== 8) throw new Error('pattern len ' + t.code);
  if (!TYPE_LIBRARY[t.code]) throw new Error('missing lib ' + t.code);
}

function shuffle(a) {
  const arr = [...a];
  for (let i = arr.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [arr[i], arr[j]] = [arr[j], arr[i]];
  }
  return arr;
}
function pick() {
  const ordered = [];
  for (let m = 1; m <= 4; m++) {
    const pool = QUESTION_BANK.filter((q) => q.module === m);
    const five = shuffle(pool).slice(0, 5);
    five.sort((a, b) => a.num - b.num);
    ordered.push(...five);
  }
  return ordered;
}
const session = pick();
if (session.length !== 20) throw new Error('session len');
for (let m = 1; m <= 4; m++) {
  const slice = session.slice((m - 1) * 5, m * 5);
  if (slice.some((q) => q.module !== m)) throw new Error('module order broken');
  for (let i = 1; i < slice.length; i++) {
    if (slice[i].num < slice[i - 1].num) throw new Error('sort within module');
  }
}

const counts = { S1: 0, S2: 0, S3: 0, E1: 0, E2: 0, E3: 0, A1: 0, A2: 0 };
session.forEach((q) => {
  const opt = q.options[0];
  counts[opt.dim]++;
});
const sum = Object.values(counts).reduce((a, b) => a + b, 0);
if (sum !== 20) throw new Error('count sum');

console.log('smoke ok: 200q, 25 types, session', session.length);
