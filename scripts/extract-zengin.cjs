const zenginCode = require('../node_modules/zengin-code/lib/zengin-code');
const fs = require('fs');
const path = require('path');

// Banks only (no branches) → Vite bundleに含める
const banks = {};
for (const [code, bank] of Object.entries(zenginCode)) {
    banks[code] = { code, name: bank.name, kana: bank.kana, hira: bank.hira };
}
const jsDataDir = path.join(__dirname, '../resources/js/data');
if (!fs.existsSync(jsDataDir)) fs.mkdirSync(jsDataDir, { recursive: true });
fs.writeFileSync(path.join(jsDataDir, 'banks.json'), JSON.stringify(banks));
console.log(`banks.json: ${Object.keys(banks).length}件`);

// 支店ファイル → publicに静的配置
const branchDir = path.join(__dirname, '../public/data/zengin/branches');
if (!fs.existsSync(branchDir)) fs.mkdirSync(branchDir, { recursive: true });
for (const [bankCode, bank] of Object.entries(zenginCode)) {
    if (bank.branches) {
        const list = Object.values(bank.branches).map(b => ({ code: b.code, name: b.name, kana: b.kana, hira: b.hira }));
        fs.writeFileSync(path.join(branchDir, `${bankCode}.json`), JSON.stringify(list));
    }
}
console.log(`支店ファイル: ${Object.keys(zenginCode).length}銀行分`);
