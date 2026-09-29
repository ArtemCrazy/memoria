"""
End-to-end check of the KIPORA test site over HTTP, the way a browser does it:
Smart-ID DEMO login -> account -> checkout -> archive upload -> file privacy
-> Montonio webhook -> paid order.

  python tools/e2e.py

Uses SK DEMO test account 40504040001 (auto-confirms) and example.com emails,
so nothing is sent to real people. Montonio keys are throwaway values set and
restored through tools/wp/e2e-keys.php / e2e-reset.php.
"""

import base64
import hashlib
import hmac
import io
import json
import os
import re
import subprocess
import sys
import time

import requests

BASE = "https://korovai.crazytest.ru/memoria"
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
failures = []


def check(name, ok, detail=""):
    print(("OK   " if ok else "FAIL ") + name + ("" if ok else "  " + detail))
    if not ok:
        failures.append(name)


def wp(script):
    out = subprocess.run([sys.executable, os.path.join(ROOT, "tools", "deploy.py"), "--wp", script], capture_output=True, text=True, encoding="utf-8")
    return out.stdout.strip()


def b64url(data):
    return base64.urlsafe_b64encode(data).rstrip(b"=").decode()


def jwt(payload, secret):
    head = b64url(json.dumps({"alg": "HS256", "typ": "JWT"}).encode())
    body = b64url(json.dumps(payload).encode())
    sig = b64url(hmac.new(secret.encode(), f"{head}.{body}".encode(), hashlib.sha256).digest())
    return f"{head}.{body}.{sig}"


def nonce(html, action):
    form = re.search(r'value="' + action + r'".*?name="_wpnonce" value="([a-f0-9]+)"', html, re.S)
    return form.group(1) if form else None


s = requests.Session()
s.cookies.set("beget", "begetok")
anon = requests.Session()
anon.cookies.set("beget", "begetok")

# 1. Login with Smart-ID DEMO.
r = s.post(f"{BASE}/wp-json/kipora/v1/auth/start", json={"method": "smartid", "idcode": "40504040001", "lang": "et"})
data = r.json()
check("auth start returns key and 4-digit code", bool(data.get("key")) and re.fullmatch(r"\d{4}", data.get("code", "")) is not None, r.text)
state = "pending"
for _ in range(30):
    st = s.post(f"{BASE}/wp-json/kipora/v1/auth/status", json={"key": data.get("key", ""), "lang": "et"}).json()
    state = st.get("state")
    if state != "pending":
        break
    time.sleep(1)
check("auth completes", state == "ok", json.dumps(st))
check("logged-in cookie set", any(c.name.startswith("wordpress_logged_in") for c in s.cookies))

bad = anon.post(f"{BASE}/wp-json/kipora/v1/auth/start", json={"method": "smartid", "idcode": "12345678901", "lang": "ru"}).json()
check("invalid personal code rejected in Russian", bad.get("reason") == "idcode" and "11" in bad.get("message", ""), json.dumps(bad, ensure_ascii=False))

stolen = anon.post(f"{BASE}/wp-json/kipora/v1/auth/status", json={"key": data.get("key", ""), "lang": "et"}).json()
check("session key is useless without the binding cookie", stolen.get("state") == "error", json.dumps(stolen))

page = s.get(f"{BASE}/minu-konto/").text
check("account greets the certificate name", "Tere, Ok Test" in page)

# 2. Checkout with a new memorial card.
sel = b64url(json.dumps({"direction": "grave", "package": "cleaning", "size": "double", "extras": ["fence"], "cemetery": "keila"}).encode())
page = s.get(f"{BASE}/tellimus/", params={"sel": sel}).text
check("checkout shows server total 179,90 €", "179,90" in page)
n = nonce(page, "kipora_checkout")
check("checkout form has nonce", bool(n))
form = {
    "action": "kipora_checkout", "_wpnonce": n, "sel": sel, "lang": "et", "card": "0",
    "name": "Mari Maasikas", "born": "1938", "died": "2019", "sector": "B", "plot": "12",
    "email": "e2e-client@example.com", "phone": "+372 5555 0000", "comment": "E2E",
    "method": "paymentInitiation", "terms": "1",
}
r = s.post(f"{BASE}/wp-admin/admin-post.php", data=form, allow_redirects=False)
loc = r.headers.get("Location", "")
check("checkout redirects back (no Montonio keys on test)", r.status_code in (302, 303) and "kp_order=" in loc and "no_payment" in loc, f"{r.status_code} {loc}")
order_id = int(re.search(r"kp_order=(\d+)", loc).group(1)) if "kp_order=" in loc else 0
page = s.get(loc).text
check("result page: waiting for payment", "Ootame makse kinnitust" in page)

tampered = dict(form, sel=b64url(json.dumps({"direction": "grave", "package": "nope"}).encode()))
r = s.post(f"{BASE}/wp-admin/admin-post.php", data=tampered, allow_redirects=False)
check("tampered selection sends back to calculator", "hinnakalkulaator" in r.headers.get("Location", ""), r.headers.get("Location", ""))

r = anon.get(loc)
check("another visitor cannot see the order", "Ootame makse" not in r.text)

# 3. Memorial card and archive upload.
page = s.get(f"{BASE}/minu-konto/").text
card = re.search(r"view=card&#038;id=(\d+)|view=card&amp;id=(\d+)|view=card&id=(\d+)", page)
card_id = int(next(g for g in card.groups() if g)) if card else 0
check("card created from checkout is listed", card_id > 0 and "Mari Maasikas" in page)
page = s.get(f"{BASE}/minu-konto/", params={"view": "card", "id": card_id}).text
check("card shows location from calculator", "Keila kalmistu" in page and "sektor B" in page and "plats 12" in page)
n = nonce(page, "kipora_file_upload")
png = base64.b64decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==")
r = s.post(f"{BASE}/wp-admin/admin-post.php", data={"action": "kipora_file_upload", "_wpnonce": n, "id": card_id, "lang": "et"},
           files=[("files[]", ("haud.png", io.BytesIO(png), "image/png"))], allow_redirects=True)
check("upload accepted", "Failid lisati arhiivi" in r.text, r.url)
m = re.search(r"kipora_file=(\d+)", r.text)
file_id = int(m.group(1)) if m else 0
check("uploaded file listed in archive", file_id > 0)
own = s.get(f"{BASE}/", params={"kipora_file": file_id})
check("owner downloads the file", own.status_code == 200 and own.content == png, str(own.status_code))
check("stranger gets 404 for the file", anon.get(f"{BASE}/", params={"kipora_file": file_id}).status_code == 404)
preview = s.get(f"{BASE}/", params={"kipora_file": file_id, "v": "p"})
check("preview is a JPEG copy", preview.status_code == 200 and preview.content[:3] == bytes([0xFF, 0xD8, 0xFF]), str(preview.status_code) + " " + preview.headers.get("Content-Type", ""))

fake = s.post(f"{BASE}/wp-admin/admin-post.php", data={"action": "kipora_file_upload", "_wpnonce": n, "id": card_id, "lang": "et"},
              files=[("files[]", ("evil.jpg", io.BytesIO(b"<?php echo 1;"), "image/jpeg"))], allow_redirects=True)
check("PHP disguised as JPG is refused", "JPG, PNG, WEBP" in fake.text)

# 4. Montonio webhook marks the order paid.
secret = wp("e2e-keys")
try:
    base = {"accessKey": "e2e-access", "merchantReference": f"KP-{order_id}", "uuid": "e2e-uuid", "grandTotal": 179.9, "currency": "EUR", "exp": int(time.time()) + 600}
    forged = jwt(dict(base, paymentStatus="PAID"), "wrong-secret")
    anon.post(f"{BASE}/wp-json/kipora/v1/montonio/notify", json={"orderToken": forged})
    page = s.get(f"{BASE}/minu-konto/", params={"view": "orders"}).text
    check("forged webhook ignored", "Ootab makset" in page)

    short = jwt(dict(base, paymentStatus="PAID", grandTotal=1.0), secret)
    anon.post(f"{BASE}/wp-json/kipora/v1/montonio/notify", json={"orderToken": short})
    page = s.get(f"{BASE}/minu-konto/", params={"view": "orders"}).text
    check("underpaid webhook ignored", "Ootab makset" in page)

    good = jwt(dict(base, paymentStatus="PAID", paymentProviderName="E2E bank"), secret)
    r = anon.post(f"{BASE}/wp-json/kipora/v1/montonio/notify", json={"orderToken": good})
    check("webhook answers 200", r.status_code == 200)
    page = s.get(f"{BASE}/minu-konto/", params={"view": "orders"}).text
    check("order is paid after valid webhook", "Makstud" in page and f"KP-{order_id}" in page)
    page = s.get(loc).text
    check("result page: thank you", "Aitäh, tellimus on makstud" in page)
finally:
    print(wp("e2e-reset"))

print(f"\norder KP-{order_id}, card {card_id}, file {file_id}")
print("all passed" if not failures else f"{len(failures)} failed")
sys.exit(1 if failures else 0)
