import asyncio
import json
import urllib.request
import websockets
import subprocess
import time

edge_path = r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
port = 9252
proc = subprocess.Popen([
    edge_path,
    f"--remote-debugging-port={port}",
    "--headless=new",
    "--disable-gpu",
    "--window-size=1280,950",
    "about:blank"
])
time.sleep(1.5)

async def test_auto():
    new_url = f"http://127.0.0.1:{port}/json/new?about:blank"
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
                if "method" in resp and resp["method"] == "Runtime.consoleAPICalled":
                    print("BROWSER CONSOLE:", resp["params"]["args"])
                elif "method" in resp and resp["method"] == "Runtime.exceptionThrown":
                    print("BROWSER EXCEPTION:", resp["params"])

        await cmd("Page.enable")
        await cmd("Runtime.enable")
        await cmd("Console.enable")
        
        # Navigate to page
        nav_resp = await cmd("Page.navigate", {"url": "http://127.0.0.1:8000/pages/analisa-pelanggan.html"})
        print("Navigation response:", nav_resp)
        
        # Listen for messages for 4 seconds
        t_end = time.time() + 4.0
        while time.time() < t_end:
            try:
                raw = await asyncio.wait_for(ws.recv(), timeout=0.5)
                resp = json.loads(raw)
                if "method" in resp and resp["method"] == "Runtime.consoleAPICalled":
                    print("CONSOLE LOG:", resp["params"])
                elif "method" in resp and resp["method"] == "Runtime.exceptionThrown":
                    print("EXCEPTION:", resp["params"])
            except asyncio.TimeoutError:
                pass

        eval_res = await cmd("Runtime.evaluate", {
            "expression": "document.getElementById('kecamatanBarsContainer').innerHTML"
        })
        val = eval_res.get('result', {}).get('result', {}).get('value', '')
        print("Auto load result (first 150 chars):", val[:150])
        print("Contains 'kec-bar-item'?:", "kec-bar-item" in val)

try:
    asyncio.run(test_auto())
finally:
    proc.kill()
