const { spawn } = require('child_process');
const fs = require('fs');

async function test() {
  const browser = spawn('C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe', [
    '--headless=new',
    '--remote-debugging-port=9254',
    '--user-data-dir=C:/Users/AdM/.gemini/antigravity/brain/b529fbe8-c810-4c8b-9355-a6d575a6922a/scratch/edge_prof_kasir_cart',
    'http://127.0.0.1:8000/pages/kasir.html'
  ]);
  await new Promise(r => setTimeout(r, 2000));
  const list = await (await fetch('http://127.0.0.1:9254/json')).json();
  const tab = list.find(t => t.url.includes('kasir'));
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
  await send('Emulation.setDeviceMetricsOverride', {
    width: 1440,
    height: 900,
    deviceScaleFactor: 1,
    mobile: false
  });

  await new Promise(r => setTimeout(r, 1500));

  // Click the first 2 "+ (add to cart)" buttons
  const res = await send('Runtime.evaluate', {
    expression: `
      (function() {
        const btns = document.querySelectorAll('.btn-add-cart');
        if (btns[0]) btns[0].click();
        if (btns[1]) btns[1].click();
        return { clickedCount: btns.length, cartCount: cart.length };
      })()
    `,
    returnByValue: true
  });
  console.log('Cart click result:', res.result ? res.result.value : res);

  await new Promise(r => setTimeout(r, 1000));

  const ss = await send('Page.captureScreenshot', { format: 'png' });
  fs.writeFileSync('C:/Users/AdM/.gemini/antigravity/brain/b529fbe8-c810-4c8b-9355-a6d575a6922a/screenshot_kasir_no_stok.png', Buffer.from(ss.data, 'base64'));
  console.log('Saved screenshot_kasir_no_stok.png');

  ws.close();
  browser.kill();
}
test().catch(console.error);
