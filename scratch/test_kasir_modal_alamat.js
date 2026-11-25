const { spawn } = require('child_process');
const http = require('http');
const fs = require('fs');
const path = require('path');

const EDGE_PATH = "C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe";
const PORT = 9247;
const USER_DATA = "C:\\Users\\AdM\\.gemini\\antigravity\\brain\\b529fbe8-c810-4c8b-9355-a6d575a6922a\\scratch\\edge_profile_kasir_modal2";
const SCREENSHOT_PATH = "C:\\Users\\AdM\\.gemini\\antigravity\\brain\\b529fbe8-c810-4c8b-9355-a6d575a6922a\\screenshot_modal_kasir_alamat_lengkap.png";

async function run() {
  console.log("Launching Edge...");
  const edge = spawn(EDGE_PATH, [
    `--remote-debugging-port=${PORT}`,
    `--user-data-dir=${USER_DATA}`,
    '--no-first-run',
    '--no-default-browser-check',
    '--headless=new',
    '--window-size=1280,800',
    'http://127.0.0.1:8000/pages/kasir.html'
  ]);

  await new Promise(r => setTimeout(r, 2000));

  const tabs = await (await fetch(`http://127.0.0.1:${PORT}/json`)).json();
  const pageTab = tabs.find(t => t.type === 'page' && t.url.includes('kasir'));
  console.log("Connecting to tab:", pageTab.title, pageTab.url);

  const ws = new WebSocket(pageTab.webSocketDebuggerUrl);
  let id = 1;

  await new Promise(r => ws.onopen = r);
  console.log("WebSocket open");

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
      console.log('CONSOLE:', d.params.type, d.params.args.map(a => a.value));
    }
  });

  await send('Page.enable');
  await send('Runtime.enable');

  // Wait for page initial scripts
  await new Promise(r => setTimeout(r, 1500));

  // Open modal Tambah Pelanggan
  console.log("Opening Tambah Pelanggan modal...");
  await send('Runtime.evaluate', {
    expression: `openCustomerModal()`,
    returnByValue: true
  });

  // Wait for Ogan districts/villages to load
  await new Promise(r => setTimeout(r, 1500));

  // Fill in the form values
  const fillResult = await send('Runtime.evaluate', {
    expression: `
      (function() {
        const nameInput = document.getElementById('newCustomerName');
        const phoneInput = document.getElementById('newCustomerPhone');
        const addressInput = document.getElementById('newCustomerAddress');
        const kecSelect = document.getElementById('newCustomerKec');
        const kelSelect = document.getElementById('newCustomerKel');

        if (nameInput) nameInput.value = 'Bapak Dedi Mulyadi';
        if (phoneInput) phoneInput.value = '087788990011';
        if (addressInput) addressInput.value = 'Jl. Gandanegara No. 1, RT 01/RW 02';

        return {
          name: nameInput ? nameInput.value : null,
          phone: phoneInput ? phoneInput.value : null,
          address: addressInput ? addressInput.value : null,
          kec: kecSelect ? (kecSelect.options[kecSelect.selectedIndex] ? kecSelect.options[kecSelect.selectedIndex].text : kecSelect.value) : null,
          kel: kelSelect ? kelSelect.value : null
        };
      })()
    `,
    returnByValue: true
  });
  console.log("Form values filled:", fillResult.result.value);

  // Take screenshot of the open modal
  console.log("Capturing screenshot of modal...");
  const ss = await send('Page.captureScreenshot', { format: 'png' });
  fs.writeFileSync(SCREENSHOT_PATH, Buffer.from(ss.data, 'base64'));
  console.log("Screenshot saved to:", SCREENSHOT_PATH);

  // Call saveCustomer directly
  console.log("Submitting modal form via saveCustomer...");
  const saveRes = await send('Runtime.evaluate', {
    expression: `
      (async () => {
        const fakeEvt = { preventDefault: () => {} };
        try {
          await saveCustomer(fakeEvt);
          return { status: 'success' };
        } catch(e) {
          return { status: 'error', message: e.message, stack: e.stack };
        }
      })()
    `,
    awaitPromise: true,
    returnByValue: true
  });
  console.log("Direct saveCustomer call result:", saveRes.result ? saveRes.result.value : saveRes);

  // Wait for API save and dropdown selection
  await new Promise(r => setTimeout(r, 2500));

  // Check customerSelect value
  const checkSelect = await send('Runtime.evaluate', {
    expression: `
      (function() {
        const sel = document.getElementById('customerSelect');
        const opt = sel ? sel.options[sel.selectedIndex] : null;
        return {
          selectedVal: sel ? sel.value : null,
          selectedText: opt ? opt.textContent : null,
          modalActive: document.getElementById('customerModalBackdrop').classList.contains('active')
        };
      })()
    `,
    returnByValue: true
  });
  console.log("Result after submit:", checkSelect.result.value);

  ws.close();
  edge.kill();
  process.exit(0);
}

run().catch(err => {
  console.error("Error:", err);
  process.exit(1);
});
