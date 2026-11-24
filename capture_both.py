import subprocess
import time
import json
import urllib.request
import base64
import os
import asyncio
import websockets

edge_path = r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
if not os.path.exists(edge_path):
    edge_path = r"C:\Program Files\Microsoft\Edge\Application\msedge.exe"

port = 9232
proc = subprocess.Popen([
    edge_path,
    f"--remote-debugging-port={port}",
    "--headless=new",
    "--disable-gpu",
    "--window-size=1280,820",
    "about:blank"
])

time.sleep(1.5)

artifact_dir = r"C:\Users\AdM\.gemini\antigravity\brain\b529fbe8-c810-4c8b-9355-a6d575a6922a"

try:
    async def run_capture():
        # --- 1. Booking Tabel ---
        print("Capturing booking-tabel.html...")
        new_url1 = f"http://127.0.0.1:{port}/json/new?http://127.0.0.1:8000/pages/booking-tabel.html"
        req1 = urllib.request.urlopen(urllib.request.Request(new_url1, method='PUT'))
        tab1 = json.loads(req1.read().decode())
        ws_url1 = tab1.get("webSocketDebuggerUrl")

        async with websockets.connect(ws_url1) as ws1:
            msg_id = 1
            async def cmd1(method, params=None):
                nonlocal msg_id
                cid = msg_id
                msg_id += 1
                await ws1.send(json.dumps({"id": cid, "method": method, "params": params or {}}))
                while True:
                    resp = json.loads(await ws1.recv())
                    if resp.get("id") == cid:
                        return resp

            await cmd1("Page.enable")
            await cmd1("Runtime.enable")
            await asyncio.sleep(3.0) # wait for API fetch and DOM render

            shot1 = await cmd1("Page.captureScreenshot", {"format": "png"})
            if "result" in shot1 and "data" in shot1["result"]:
                path1 = os.path.join(artifact_dir, "screenshot_booking_tabel.png")
                with open(path1, "wb") as f:
                    f.write(base64.b64decode(shot1["result"]["data"]))
                print("Saved", path1)

        # --- 2. Pesanan Tabel ---
        print("Capturing pesanan-tabel.html...")
        new_url2 = f"http://127.0.0.1:{port}/json/new?http://127.0.0.1:8000/pages/pesanan-tabel.html"
        req2 = urllib.request.urlopen(urllib.request.Request(new_url2, method='PUT'))
        tab2 = json.loads(req2.read().decode())
        ws_url2 = tab2.get("webSocketDebuggerUrl")

        async with websockets.connect(ws_url2) as ws2:
            msg_id2 = 1
            async def cmd2(method, params=None):
                nonlocal msg_id2
                cid = msg_id2
                msg_id2 += 1
                await ws2.send(json.dumps({"id": cid, "method": method, "params": params or {}}))
                while True:
                    resp = json.loads(await ws2.recv())
                    if resp.get("id") == cid:
                        return resp

            await cmd2("Page.enable")
            await cmd2("Runtime.enable")
            await asyncio.sleep(3.0) # wait for API fetch and DOM render

            shot2 = await cmd2("Page.captureScreenshot", {"format": "png"})
            if "result" in shot2 and "data" in shot2["result"]:
                path2 = os.path.join(artifact_dir, "screenshot_pesanan_tabel.png")
                with open(path2, "wb") as f:
                    f.write(base64.b64decode(shot2["result"]["data"]))
                print("Saved", path2)

    asyncio.run(run_capture())

finally:
    proc.kill()
