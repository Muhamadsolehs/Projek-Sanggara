const { spawn } = require('child_process');

async function test() {
  const browser = spawn('C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe', [
    '--headless=new',
    '--remote-debugging-port=9276',
    '--user-data-dir=C:/Users/AdM/.gemini/antigravity/brain/b529fbe8-c810-4c8b-9355-a6d575a6922a/scratch/edge_debug_dn',
    'http://127.0.0.1:8000/pages/data-nota.html'
  ]);
  await new Promise(r => setTimeout(r, 2000));
  const list = await (await fetch('http://127.0.0.1:9276/json')).json();
  const tab = list.find(t => t.url.includes('data-nota'));
  const ws = new WebSocket(tab.webSocketDebuggerUrl);
  await new Promise(r => ws.onopen = r);

  ws.addEventListener('message', (evt) => {
    const data = JSON.parse(evt.data);
    if (data.method === 'Runtime.consoleAPICalled') {
      console.log('CONSOLE:', data.params.type, data.params.args.map(a => a.value || a.description));
    }
    if (data.method === 'Runtime.exceptionThrown') {
      console.log('EXCEPTION:', data.params.exceptionDetails);
    }
  });

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
  await send('Log.enable');

  await new Promise(r => setTimeout(r, 2000));

  const evalRes = await send('Runtime.evaluate', {
    expression: `
      (async function() {
        try {
          const res = await SanggaraAPI.getNotaList();
          return { ok: true, data: res };
        } catch (e) {
          return { ok: false, err: e.message, stack: e.stack };
        }
      })()
    `,
    awaitPromise: true,
    returnByValue: true
  });

  console.log('API call in browser:', JSON.stringify(evalRes, null, 2));

  ws.close();
  browser.kill();
}
test().catch(console.error);
