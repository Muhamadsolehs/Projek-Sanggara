const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

async function generatePDF() {
  const htmlPath = path.resolve(__dirname, '../DOKUMENTASI_SISTEM_SANGGARA.html');
  const fileUrl = 'file:///' + htmlPath.replace(/\\/g, '/');
  const outPdfPath = path.resolve(__dirname, '../DOKUMENTASI_SISTEM_SANGGARA.pdf');

  const browser = spawn('C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe', [
    '--headless=new',
    '--disable-extensions',
    '--remote-debugging-port=9283',
    '--user-data-dir=C:/Users/AdM/.gemini/antigravity/brain/b529fbe8-c810-4c8b-9355-a6d575a6922a/scratch/edge_pdf_profile_clean',
    'about:blank'
  ]);

  await new Promise(r => setTimeout(r, 1500));
  const list = await (await fetch('http://127.0.0.1:9283/json')).json();
  const pageTarget = list.find(t => t.type === 'page') || list[0];
  console.log('Connecting to target:', pageTarget.url, pageTarget.type);
  const ws = new WebSocket(pageTarget.webSocketDebuggerUrl);
  await new Promise(r => ws.onopen = r);

  let id = 1;
  function send(method, params = {}) {
    return new Promise((resolve, reject) => {
      const msgId = id++;
      const h = evt => {
        const p = JSON.parse(evt.data);
        if (p.id === msgId) {
          ws.removeEventListener('message', h);
          if (p.error) reject(p.error);
          else resolve(p.result);
        }
      };
      ws.addEventListener('message', h);
      ws.send(JSON.stringify({ id: msgId, method, params }));
    });
  }

  await send('Page.enable');
  await send('Page.navigate', { url: fileUrl });
  await new Promise(r => setTimeout(r, 2000));

  console.log('Calling Page.printToPDF...');
  const pdfResult = await send('Page.printToPDF', {
    printBackground: true,
    paperWidth: 8.27,
    paperHeight: 11.69,
    marginTop: 0.4,
    marginBottom: 0.5,
    marginLeft: 0.4,
    marginRight: 0.4,
    displayHeaderFooter: true,
    headerTemplate: '<div></div>',
    footerTemplate: '<div style="font-size: 8px; color: #94a3b8; width: 100%; text-align: right; padding-right: 15mm;">Halaman <span class="pageNumber"></span> dari <span class="totalPages"></span> &bull; Sanggara Management System</div>'
  });

  if (pdfResult && pdfResult.data) {
    fs.writeFileSync(outPdfPath, Buffer.from(pdfResult.data, 'base64'));
    console.log('SUCCESS: Generated PDF at', outPdfPath, 'Size:', fs.statSync(outPdfPath).size, 'bytes');
  } else {
    console.error('Failed to get PDF data');
  }

  ws.close();
  browser.kill();
}

generatePDF().catch(console.error);
