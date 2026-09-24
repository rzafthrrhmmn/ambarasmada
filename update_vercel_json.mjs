const fs = require('fs');
const vj = JSON.parse(fs.readFileSync('vercel.json', 'utf8'));
if (!vj.routes.find(r => r.src === '/test-node')) {
  vj.routes.unshift({src: '/test-node', dest: '/api/test-node.js'});
}
fs.writeFileSync('vercel.json', JSON.stringify(vj, null, 4));
console.log('Updated vercel.json');
