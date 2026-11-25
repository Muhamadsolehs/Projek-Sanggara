const { spawn } = require('child_process');
const fs = require('fs');

async function test() {
  const browser = spawn('C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe', [
    '--headless=new',
    '--remote-debugging-port=9252',
    '--user-data-dir=C:/Users/AdM/.gemini/antigravity/brain/b529fbe8-c810-4c8b-9355-a6d575a6922a/scratch/edge_prof_kp_load2',
    'http://127.0.0.1:8000/pages/kelola-produk.html'
  ]);
  await new Promise(r => setTimeout(r, 2000));
  const list = await (await fetch('http://127.0.0.1:9252/json')).json();
  const tab = list.find(t => t.url.includes('kelola-produk'));
  const ws = new WebSocket(tab.webSocketDebuggerUrl);
  await new Promise(r => ws.onopen = r);

  let id = 1;
  function send(method, params = {}) {
    return new Promise((resolve) => {
      const msgId = id++;
      const handler = (evt) => {
        const parsed = JSON.parse(evt.data);
        if (parsed.id === msgId) {
          ws.removeEventListener('message', handler);
          resolve(parsed.result);
        }
      };
      ws.addEventListener('message', handler);
      ws.send(JSON.stringify({ id: msgId, method, params }));
    });
  }

  await send('Page.enable');
  await send('Runtime.enable');

  await new Promise(r => setTimeout(r, 1000));

  // 1. Table
  await send('Runtime.evaluate', {
    expression: `loadProdukData()`,
    awaitPromise: true
  });
  await new Promise(r => setTimeout(r, 1200));

  const ssTable = await send('Page.captureScreenshot', { format: 'png' });
  fs.writeFileSync('C:/Users/AdM/.gemini/antigravity/brain/b529fbe8-c810-4c8b-9355-a6d575a6922a/screenshot_kelola_produk_no_stok.png', Buffer.from(ssTable.data, 'base64'));

  // 2. Detail Modal
  await send('Runtime.evaluate', { expression: `viewProduk(1)` });
  await new Promise(r => setTimeout(r, 1000));
  const ssDetail = await send('Page.captureScreenshot', { format: 'png' });
  fs.writeFileSync('C:/Users/AdM/.gemini/antigravity/brain/b529fbe8-c810-4c8b-9355-a6d575a6922a/screenshot_detail_produk_no_stok.png', Buffer.from(ssDetail.data, 'base64'));

  // 3. Edit Modal
  await send('Runtime.evaluate', { expression: `closeModal('detailProdukModal'); openEditProdukModal(1);` });
  await new Promise(r => setTimeout(r, 1000));
  const ssEdit = await send('Page.captureScreenshot', { format: 'png' });
  fs.writeFileSync('C:/Users/AdM/.gemini/antigravity/brain/b529fbe8-c810-4c8b-9355-a6d575a6922a/screenshot_edit_produk_no_stok.png', Buffer.from(ssEdit.data, 'base64'));

  console.log('All KP screenshots captured successfully!');
  ws.close();
  browser.kill();
}
test().catch(console.error);
