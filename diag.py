import subprocess
import time
import json
import urllib.request
import asyncio
import websockets
import os

edge_path = r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
if not os.path.exists(edge_path):
    edge_path = r"C:\Program Files\Microsoft\Edge\Application\msedge.exe"

port = 9230
proc = subprocess.Popen([
    edge_path,
    f"--remote-debugging-port={port}",
    "--headless=new",
    "--disable-gpu",
    "about:blank"
])

time.sleep(1.5)

try:
    async def inspect():
        new_url = f"http://127.0.0.1:{port}/json/new?http://127.0.0.1:8000/pages/booking-tabel.html"
        req = urllib.request.urlopen(urllib.request.Request(new_url, method='PUT'))
        tab = json.loads(req.read().decode())
        ws_url = tab.get("webSocketDebuggerUrl")

        async with websockets.connect(ws_url) as ws:
            msg_id = 1
            async def cmd(method, params=None):
                nonlocal msg_id
                cid = msg_id
                msg_id += 1
                await ws.send(json.dumps({"id": cid, "method": method, "params": params or {}}))
                while True:
                    resp = json.loads(await ws.recv())
                    if resp.get("id") == cid:
                        return resp

            await cmd("Page.enable")
            await cmd("Runtime.enable")
            await asyncio.sleep(3)

            # Check document readyState, scripts, and errors
            diag = """
            ({
                readyState: document.readyState,
                hasAPI: typeof window.SanggaraAPI,
                scripts: Array.from(document.querySelectorAll('script')).map(s => s.src || 'inline'),
                tableHtml: document.getElementById('bookingTableBody') ? document.getElementById('bookingTableBody').innerHTML : 'no-tbody',
                emptyDisplay: document.getElementById('bookingEmptyState') ? document.getElementById('bookingEmptyState').style.display : 'no-empty'
            })
            """
            r = await cmd("Runtime.evaluate", {"expression": diag, "returnByValue": True})
            print("DIAG RESULT:", json.dumps(r, indent=2))

    asyncio.run(inspect())
finally:
    proc.kill()
