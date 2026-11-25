const { spawn } = require('child_process');
const fs = require('fs');

async function test() {
  const browser = spawn('C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe', [
    '--headless=new',
    '--remote-debugging-port=9278',
    '--user-data-dir=C:/Users/AdM/.gemini/antigravity/brain/b529fbe8-c810-4c8b-9355-a6d575a6922a/scratch/edge_test_nota_clean4',
    'about:blank'
  ]);
  await new Promise(r => setTimeout(r, 1500));
  const list = await (await fetch('http://127.0.0.1:9278/json')).json();
  const ws = new WebSocket(list[0].webSocketDebuggerUrl);
  await new Promise(r => ws.onopen = r);
  let id = 1;
  function send(method, params = {}) {
    return new Promise(res => {
      const msgId = id++;
      const h = evt => {
        const p = JSON.parse(evt.data);
        if (p.id === msgId) { ws.removeEventListener('message', h); res(p.result); }
      };
      ws.addEventListener('message', h);
      ws.send(JSON.stringify({ id: msgId, method, params }));
    });
  }
  await send('Page.enable');
  await send('Runtime.enable');
  await send('Emulation.setDeviceMetricsOverride', {
    width: 1200,
    height: 1800,
    deviceScaleFactor: 1,
    mobile: false
  });

  await send('Page.navigate', { url: 'http://127.0.0.1:8000/pages/nota.html?id=36' });
  await new Promise(r => setTimeout(r, 2000));

  // Get receipt element bounding box
  const box = await send('Runtime.evaluate', {
    expression: `
      (() => {
        const el = document.querySelector('.receipt-paper-card') || document.querySelector('.receipt-page-wrapper');
        if (!el) return null;
        const r = el.getBoundingClientRect();
        return { x: window.scrollX + r.x, y: window.scrollY + r.y, width: r.width, height: r.height };
      })()
    `,
    returnByValue: true
  });
  console.log('Receipt box:', box?.result?.value);

  const clip = box?.result?.value ? {
    x: Math.max(0, box.result.value.x - 20),
    y: Math.max(0, box.result.value.y - 20),
    width: box.result.value.width + 40,
    height: box.result.value.height + 40,
    scale: 1
  } : undefined;

  const ss = await send('Page.captureScreenshot', { format: 'png', clip, captureBeyondViewport: true });
  fs.writeFileSync('C:/Users/AdM/.gemini/antigravity/brain/b529fbe8-c810-4c8b-9355-a6d575a6922a/screenshot_nota_dua_metode.png', Buffer.from(ss.data, 'base64'));
  console.log('Captured screenshot_nota_dua_metode.png with clip!');
  ws.close();
  browser.kill();
}
test().catch(console.error);
