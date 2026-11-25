const { spawn } = require('child_process');
const fs = require('fs');

async function test() {
  const browser = spawn('C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe', [
    '--headless=new',
    '--remote-debugging-port=9264',
    '--user-data-dir=C:/Users/AdM/.gemini/antigravity/brain/b529fbe8-c810-4c8b-9355-a6d575a6922a/scratch/edge_prof_nota_dp2',
    'http://127.0.0.1:8000/pages/nota.html?id=36'
  ]);
  await new Promise(r => setTimeout(r, 2000));
  const list = await (await fetch('http://127.0.0.1:9264/json')).json();
  const tab = list.find(t => t.url.includes('nota'));
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
    width: 1200,
    height: 1100,
    deviceScaleFactor: 1,
    mobile: false
  });

  // Wait until customerName is loaded from API
  let loaded = false;
  for (let i = 0; i < 20; i++) {
    const check = await send('Runtime.evaluate', {
      expression: `document.getElementById('customerName')?.textContent`,
      returnByValue: true
    });
    if (check && check.result && check.result.value === 'Asep Saefudin') {
      loaded = true;
      break;
    }
    await new Promise(r => setTimeout(r, 250));
  }

  console.log('Customer DP loaded:', loaded);
  await new Promise(r => setTimeout(r, 500));

  const ss = await send('Page.captureScreenshot', { format: 'png' });
  const outPath = 'C:/Users/AdM/.gemini/antigravity/brain/b529fbe8-c810-4c8b-9355-a6d575a6922a/screenshot_nota_dp_sesuai_kasir.png';
  fs.writeFileSync(outPath, Buffer.from(ss.data, 'base64'));
  console.log('Saved screenshot to:', outPath);

  ws.close();
  browser.kill();
}
test().catch(console.error);
