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

port = 9228
proc = subprocess.Popen([
    edge_path,
    f"--remote-debugging-port={port}",
    "--headless=new",
    "--disable-gpu",
    "about:blank"
])

time.sleep(1.5)

try:
    async def run_check():
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

            await cmd("Console.enable")
            await cmd("Runtime.enable")
            
            # Listen to console messages or test fetch directly
            test_fetch = """
            (async function() {
                try {
                    const res = await SanggaraAPI.getBooking();
                    return JSON.stringify({ ok: true, dataCount: res.data ? res.data.length : 0 });
                } catch(e) {
                    return JSON.stringify({ ok: false, error: e.toString(), stack: e.stack });
                }
            })()
            """
            eval_res = await cmd("Runtime.evaluate", {"expression": test_fetch, "awaitPromise": True, "returnByValue": True})
            print("EVAL RESULT:", eval_res)

    asyncio.run(run_check())

finally:
    proc.kill()
