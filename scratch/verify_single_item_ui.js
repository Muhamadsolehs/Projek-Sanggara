const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

const ARTIFACT_DIR = 'C:/Users/AdM/.gemini/antigravity/brain/b529fbe8-c810-4c8b-9355-a6d575a6922a';
const edgePath = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const userDataDir = 'C:/Users/AdM/.gemini/antigravity/brain/b529fbe8-c810-4c8b-9355-a6d575a6922a/scratch/edge_prof_single_item';

async function run() {
  const browser = spawn(edgePath, [
    '--headless=new',
    '--remote-debugging-port=9249',
    `--user-data-dir=${userDataDir}`,
    '--window-size=1440,900',
    '--disable-gpu',
    'http://127.0.0.1:8000/pages/kelola-produk.html'
  ]);

  await new Promise(r => setTimeout(r, 2500));

  const list = await (await fetch('http://127.0.0.1:9249/json')).json();
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
  await new Promise(r => setTimeout(r, 2000));

  // 1. Screenshot Kelola Produk table (no stock column)
  console.log("Capturing Kelola Produk table...");
  const ssTable = await send('Page.captureScreenshot', { format: 'png' });
  fs.writeFileSync(path.join(ARTIFACT_DIR, 'screenshot_kelola_produk_no_stok.png'), Buffer.from(ssTable.data, 'base64'));

  // 2. Open Detail Modal
  console.log("Opening detail modal...");
  await send('Runtime.evaluate', { expression: `viewProduk(1)` });
  await new Promise(r => setTimeout(r, 1000));
  const ssDetail = await send('Page.captureScreenshot', { format: 'png' });
  fs.writeFileSync(path.join(ARTIFACT_DIR, 'screenshot_detail_produk_no_stok.png'), Buffer.from(ssDetail.data, 'base64'));

  // 3. Open Edit Modal
  console.log("Opening edit modal...");
  await send('Runtime.evaluate', { expression: `closeModal('detailProdukModal'); openEditProdukModal(1);` });
  await new Promise(r => setTimeout(r, 1000));
  const ssEdit = await send('Page.captureScreenshot', { format: 'png' });
  fs.writeFileSync(path.join(ARTIFACT_DIR, 'screenshot_edit_produk_no_stok.png'), Buffer.from(ssEdit.data, 'base64'));

  // 4. Navigate to Kasir
  console.log("Navigating to Kasir...");
  await send('Page.navigate', { url: 'http://127.0.0.1:8000/pages/kasir.html' });
  await new Promise(r => setTimeout(r, 2500));

  // Add 2 items to cart
  await send('Runtime.evaluate', { expression: `addToCart(1); addToCart(2);` });
  await new Promise(r => setTimeout(r, 1000));
  const ssKasir = await send('Page.captureScreenshot', { format: 'png' });
  fs.writeFileSync(path.join(ARTIFACT_DIR, 'screenshot_kasir_no_stok.png'), Buffer.from(ssKasir.data, 'base64'));

  // 5. Navigate to Tambah Produk
  console.log("Navigating to Tambah Produk...");
  await send('Page.navigate', { url: 'http://127.0.0.1:8000/pages/tambah-produk.html' });
  await new Promise(r => setTimeout(r, 2000));
  const ssTambah = await send('Page.captureScreenshot', { format: 'png' });
  fs.writeFileSync(path.join(ARTIFACT_DIR, 'screenshot_tambah_produk_no_stok.png'), Buffer.from(ssTambah.data, 'base64'));

  console.log("All screenshots captured successfully!");
  ws.close();
  browser.kill();
}

run().catch(err => {
  console.error("Error:", err);
  process.exit(1);
});
