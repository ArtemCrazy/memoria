"""
Full-page screenshots at a real viewport size, through Chrome DevTools.

Plain `chrome --screenshot` only captures the window, and a tall window
changes the page (the hero is one screen tall). Here the viewport stays e.g.
1440x900 and the capture extends below it.

  python tools/shot.py URL OUT.png [WIDTH] [HEIGHT] [--mobile]
"""

import base64
import json
import os
import subprocess
import sys
import tempfile
import time
import urllib.request

import websocket

CHROME = r"C:\Program Files\Google\Chrome\Application\chrome.exe"


def main():
    url, out = sys.argv[1], sys.argv[2]
    width = int(sys.argv[3]) if len(sys.argv) > 3 else 1440
    height = int(sys.argv[4]) if len(sys.argv) > 4 else 900
    mobile = "--mobile" in sys.argv
    port = 9333
    profile = tempfile.mkdtemp(prefix="kp-shot-")
    proc = subprocess.Popen([CHROME, "--headless=new", "--disable-gpu", "--hide-scrollbars", f"--remote-debugging-port={port}", f"--remote-allow-origins=http://127.0.0.1:{port}", f"--user-data-dir={profile}", "about:blank"], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    try:
        for _ in range(50):
            try:
                tabs = json.load(urllib.request.urlopen(f"http://127.0.0.1:{port}/json"))
                page = next(t for t in tabs if t["type"] == "page")
                break
            except Exception:
                time.sleep(0.2)
        ws = websocket.create_connection(page["webSocketDebuggerUrl"], timeout=60)
        seq = [0]

        def call(method, **params):
            seq[0] += 1
            ws.send(json.dumps({"id": seq[0], "method": method, "params": params}))
            while True:
                msg = json.loads(ws.recv())
                if msg.get("id") == seq[0]:
                    return msg.get("result", {})

        call("Emulation.setDeviceMetricsOverride", width=width, height=height, deviceScaleFactor=1, mobile=mobile)
        call("Page.enable")
        # The test host sometimes resets the connection: retry until a real page loads.
        for _ in range(4):
            call("Page.navigate", url=url)
            time.sleep(7)  # Beget's cookie check reloads the page once; fonts and the line animation settle.
            res = call("Runtime.evaluate", expression="location.href", returnByValue=True)
            if not str(res["result"].get("value", "")).startswith("chrome-error"):
                break
        # Optional page script before capture, e.g. to remove a cookie banner of a reference site.
        if os.environ.get("KP_SHOT_JS"):
            res = call("Runtime.evaluate", expression=os.environ["KP_SHOT_JS"], awaitPromise=True, returnByValue=True)
            if res.get("result", {}).get("value") is not None:
                print(res["result"]["value"])
            time.sleep(1)
        # Layout metrics report the viewport in mobile emulation; ask the page itself.
        res = call("Runtime.evaluate", expression="document.documentElement.scrollHeight", returnByValue=True)
        full = int(res["result"]["value"])
        shot = call("Page.captureScreenshot", format="png", captureBeyondViewport=True, clip={"x": 0, "y": 0, "width": width, "height": full, "scale": 1})
        with open(out, "wb") as f:
            f.write(base64.b64decode(shot["data"]))
        print(f"{out}: {width}x{full}")
    finally:
        proc.terminate()


if __name__ == "__main__":
    main()
