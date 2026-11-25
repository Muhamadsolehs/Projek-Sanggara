const { spawn } = require('child_process');
const fs = require('fs');

async function test() {
  const browser = spawn('C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe', [
    '--headless=new',
    '--remote-debugging-port=9246',
    '--user-data-dir=C:/Users/AdM/.gemini/antigravity/brain/b529fbe8-c810-4c8b-9355-a6d575a6922a/scratch/edge_prof_test_save',
    'http://127.0.0.1:8000/pages/kasir.html'
  ]);
  await new Promise(r => setTimeout(r, 2000));
  const list = await (await fetch('http://127.0.0.1:9246/json')).json();
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

  ws.addEventListener('message', (evt) => {
    const d = JSON.parse(evt.data);
    if (d.method === 'Runtime.consoleAPICalled') {
      console.log('BROWSER CONSOLE:', d.params.type, d.params.args.map(a => a.value));
    }
  });

  await send('Page.enable');
  await send('Runtime.enable');
  await new Promise(r => setTimeout(r, 1000));

  // Open modal and fill
  const res = await send('Runtime.evaluate', {
    expression: `
      (async () => {
        openCustomerModal();
        await new Promise(r => setTimeout(r, 1000));
        document.getElementById('newCustomerName').value = 'Ibu Ratna Komalasari';
        document.getElementById('newCustomerPhone').value = '081234567890';
        document.getElementById('newCustomerAddress').value = 'Jl. Veteran No. 42 RT 05/RW 03';
        const fakeEvt = { preventDefault: () => {} };
        try {
          await saveCustomer(fakeEvt);
          return { status: 'success' };
        } catch(e) {
          return { status: 'error', message: e.message };
        }
      })()
    `,
    awaitPromise: true,
    returnByValue: true
  });

  console.log('Save result:', res.result ? res.result.value : res);
  await new Promise(r => setTimeout(r, 2000));
  ws.close();
  browser.kill();
}
test().catch(console.error);
