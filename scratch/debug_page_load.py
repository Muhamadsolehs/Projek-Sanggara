import asyncio
import json
import urllib.request
import websockets
import os
import subprocess
import time

edge_path = r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
port = 9245
proc = subprocess.Popen([
    edge_path,
    f"--remote-debugging-port={port}",
    "--headless=new",
    "--disable-gpu",
    "--window-size=1280,950",
    "about:blank"
])
time.sleep(1.5)

async def check_page():
    new_url = f"http://127.0.0.1:{port}/json/new?http://127.0.0.1:8000/pages/analisa-pelanggan.html"
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
        await cmd("Console.enable")

        # Let it run for 3 seconds
        await asyncio.sleep(3.0)

        # Check console logs and evaluate SanggaraAPI
        eval1 = await cmd("Runtime.evaluate", {
            "expression": "typeof SanggaraAPI !== 'undefined' ? typeof SanggaraAPI.getAnalisaPelanggan : 'no API'"
        })
        print("API check:", eval1)

        eval2 = await cmd("Runtime.evaluate", {
            "expression": "document.getElementById('kecamatanBarsContainer').innerHTML"
        })
        print("Container innerHTML:", eval2.get('result', {}).get('result', {}).get('value'))

        # Let's manually invoke loadAnalisaPelanggan() and await result
        eval3 = await cmd("Runtime.evaluate", {
            "expression": "(async () => { try { await loadAnalisaPelanggan(); return 'done'; } catch(e) { return 'ERR: ' + e.message; } })()",
            "awaitPromise": True
        })
        print("Manual invoke:", eval3)

        eval4 = await cmd("Runtime.evaluate", {
            "expression": "document.getElementById('kecamatanBarsContainer').innerHTML"
        })
        print("After manual invoke container:", eval4.get('result', {}).get('result', {}).get('value')[:200] if eval4.get('result', {}).get('result', {}).get('value') else "empty")

        # Take screenshot
        shot = await cmd("Page.captureScreenshot", {"format": "png"})
        if "result" in shot and "data" in shot["result"]:
            import base64
            with open(r"C:\Users\AdM\.gemini\antigravity\brain\b529fbe8-c810-4c8b-9355-a6d575a6922a\screenshot_analisa_pelanggan.png", "wb") as f:
                f.write(base64.b64decode(shot["result"]["data"]))
            print("New screenshot captured!")

try:
    asyncio.run(check_page())
finally:
    proc.kill()
