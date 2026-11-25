const { spawn } = require('child_process');
const fs = require('fs');

async function capture() {
  const browser = spawn('C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe', [
    '--headless=new',
    '--remote-debugging-port=9272',
    '--user-data-dir=C:/Users/AdM/.gemini/antigravity/brain/b529fbe8-c810-4c8b-9355-a6d575a6922a/scratch/edge_prof_twomethods_real',
    'http://127.0.0.1:8000/pages/data-nota.html'
  ]);
  await new Promise(r => setTimeout(r, 2000));
  const list = await (await fetch('http://127.0.0.1:9272/json')).json();
  const tab = list.find(t => t.url.includes('data-nota'));
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

  // Explicitly wait until dynamic rows (e.g. rowBooking36) are loaded from API
  let tableLoaded = false;
  for (let i = 0; i < 25; i++) {
    const check = await send('Runtime.evaluate', {
      expression: `!!document.getElementById('rowBooking36')`,
      returnByValue: true
    });
    if (check && check.result && check.result.value === true) {
      tableLoaded = true;
      break;
    }
    await new Promise(r => setTimeout(r, 250));
  }
  console.log('Dynamic table row loaded:', tableLoaded);

  await new Promise(r => setTimeout(r, 500));

  const ssTable = await send('Page.captureScreenshot', { format: 'png' });
  const outPathTable = 'C:/Users/AdM/.gemini/antigravity/brain/b529fbe8-c810-4c8b-9355-a6d575a6922a/screenshot_data_nota_dua_metode.png';
  fs.writeFileSync(outPathTable, Buffer.from(ssTable.data, 'base64'));
  console.log('Saved screenshot_data_nota_dua_metode.png');

  // Navigate to nota.html?id=36
  await send('Page.navigate', { url: 'http://127.0.0.1:8000/pages/nota.html?id=36' });

  let notaLoaded = false;
  for (let i = 0; i < 25; i++) {
    const check = await send('Runtime.evaluate', {
      expression: `document.getElementById('customerName')?.textContent`,
      returnByValue: true
    });
    if (check && check.result && check.result.value === 'Asep Saefudin') {
      notaLoaded = true;
      break;
    }
    await new Promise(r => setTimeout(r, 250));
  }
  console.log('Nota loaded for customer:', notaLoaded);

  await new Promise(r => setTimeout(r, 500));

  const ssNota = await send('Page.captureScreenshot', { format: 'png' });
  const outPathNota = 'C:/Users/AdM/.gemini/antigravity/brain/b529fbe8-c810-4c8b-9355-a6d575a6922a/screenshot_nota_dua_metode.png';
  fs.writeFileSync(outPathNota, Buffer.from(ssNota.data, 'base64'));
  console.log('Saved screenshot_nota_dua_metode.png');

  ws.close();
  browser.kill();
}
capture().catch(console.error);
