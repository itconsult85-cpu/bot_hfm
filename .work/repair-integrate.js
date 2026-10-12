const fs=require('fs');
const p='.work/integrate_bot_old.js';
let s=fs.readFileSync(p,'utf8');
const q="    const [candidates] = await db.query('SELECT DISTINCT TRIM(id_telegram) AS telegram_id FROM tb_member_vip WHERE status = ' + String.fromCharCode(39) + 'lepas_ib' + String.fromCharCode(39) + ' AND id_telegram IS NOT NULL AND TRIM(id_telegram) REGEXP ' + String.fromCharCode(39) + '^[0-9]+$' + String.fromCharCode(39) + ' UNION SELECT DISTINCT TRIM(id_telegram) AS telegram_id FROM tb_member_logs WHERE tipe_aktivitas = ' + String.fromCharCode(39) + 'lepas_ib' + String.fromCharCode(39) + ' AND id_telegram IS NOT NULL AND TRIM(id_telegram) REGEXP ' + String.fromCharCode(39) + '^[0-9]+$' + String.fromCharCode(39) + '' );";
s=s.replace(/    const \[candidates\][\s\S]*?    const result = \{/,(q+'\n    const result = {'));
fs.writeFileSync(p,s);
