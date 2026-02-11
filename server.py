#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import os, json, csv, time, ssl, base64
import mimetypes
import io
import traceback
import hmac
import re
import uuid

from http.server import ThreadingHTTPServer, SimpleHTTPRequestHandler
from urllib.request import Request, urlopen
from urllib.parse import urlparse
from pathlib import Path
from datetime import datetime
from email.parser import BytesParser
from email.policy import default as email_default_policy

# Ensure correct mime-types for modern/edge image formats
mimetypes.add_type('image/avif', '.avif')
mimetypes.add_type('image/jpeg', '.jfif')

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
ROOT = Path(BASE_DIR)
ASSETS_DIR = os.path.join(BASE_DIR, "assets")
UPLOADS_DIR = os.path.join(ASSETS_DIR, "uploads")

# Public site URL (canonical/sitemap/schema). Override via env SITE_URL.
SITE_URL = os.getenv("SITE_URL", "https://mircranov.ru").strip().rstrip("/")

# Leads
LEADS_DIR = os.path.join(BASE_DIR, "leads")
LEADS_CSV = os.path.join(LEADS_DIR, "leads.csv")

# Products
PRODUCTS_JSON = ROOT / "data" / "products.json"
PRODUCTS_CSV  = ROOT / "data" / "products.csv"

# Site settings (logo, hero background, theme)
SETTINGS_JSON = ROOT / "data" / "settings.json"
DEFAULT_SETTINGS = {
    "theme_default": "blue",
    # optional:
    "logo_path": "",        # e.g. "/assets/uploads/branding/logo.png"
    "hero_bg_path": "",     # e.g. "/assets/uploads/branding/hero-bg.jpg"
}

# Optional notifications
TELEGRAM_BOT_TOKEN = os.getenv("TELEGRAM_BOT_TOKEN", "").strip()
TELEGRAM_CHAT_ID   = os.getenv("TELEGRAM_CHAT_ID", "").strip()

SMTP_HOST = os.getenv("SMTP_HOST", "").strip()
SMTP_PORT = int(os.getenv("SMTP_PORT", "587").strip() or "587")
SMTP_USER = os.getenv("SMTP_USER", "").strip()
SMTP_PASS = os.getenv("SMTP_PASS", "").strip()
SMTP_TO   = os.getenv("SMTP_TO", "").strip()

RATE_LIMIT_SECONDS = int(os.getenv("RATE_LIMIT_SECONDS", "10"))

# Lead/JSON body cap (does NOT apply to uploads)
MAX_BODY_BYTES = int(os.getenv("MAX_BODY_BYTES", "5000000"))  # 5 MB for json bodies/leads
# Upload cap
MAX_UPLOAD_BYTES = int(os.getenv("MAX_UPLOAD_BYTES", "25000000"))  # 25 MB

# Admin auth (Basic)
ADMIN_USER = os.getenv("ADMIN_USER", "cryptocommunity28")
ADMIN_PASS = os.getenv("ADMIN_PASS", "ip6zVP2F2WF0fji8")


def basic_auth_ok(headers) -> bool:
    """Validate HTTP Basic auth against ADMIN_USER/ADMIN_PASS."""
    try:
        auth = headers.get('Authorization', '') or ''
        if not auth.startswith('Basic '):
            return False
        b64 = auth.split(' ', 1)[1].strip()
        raw = base64.b64decode(b64).decode('utf-8', errors='replace')
        if ':' not in raw:
            return False
        user, pwd = raw.split(':', 1)
        # Use constant-time comparison to avoid timing leaks
        return hmac.compare_digest(user, ADMIN_USER) and hmac.compare_digest(pwd, ADMIN_PASS)
    except Exception:
        return False


_ip_last = {}

def ensure_leads_csv():
    os.makedirs(LEADS_DIR, exist_ok=True)
    if not os.path.exists(LEADS_CSV):
        with open(LEADS_CSV, "w", newline="", encoding="utf-8") as f:
            w = csv.writer(f)
            w.writerow(["ts", "ip", "lead_type", "page", "referer", "utm_json", "fields_json"])

def parse_leads():
    ensure_leads_csv()
    out = []
    try:
        with open(LEADS_CSV, "r", encoding="utf-8", newline="") as f:
            r = csv.DictReader(f)
            for row in r:
                out.append(row)
    except Exception:
        pass
    out.reverse()
    return out

def save_lead(payload: dict, ip: str, referer: str = ""):
    """
    payload example:
      {
        "lead_type": "price",
        "page": "/catalog/kmu/xxx/",
        "utm": {...},
        "fields": {"name":"...", "phone":"...", "message":"..."}
      }
    """
    ensure_leads_csv()
    if not isinstance(payload, dict):
        payload = {}

    lead_type = (payload.get("lead_type") or payload.get("type") or "lead").strip()[:32]
    page = (payload.get("page") or "").strip()[:512]
    utm = payload.get("utm") if isinstance(payload.get("utm"), dict) else {}
    fields = payload.get("fields") if isinstance(payload.get("fields"), dict) else {}

    phone = (fields.get("phone") or "").strip()
    if not phone:
        phone = (payload.get("phone") or "").strip()
        if phone:
            fields["phone"] = phone

    if not (fields.get("phone") or "").strip():
        return False, "Укажите телефон."

    ts = datetime.utcnow().isoformat(timespec="seconds") + "Z"
    try:
        with open(LEADS_CSV, "a", newline="", encoding="utf-8") as f:
            w = csv.writer(f)
            w.writerow([
                ts, ip, lead_type, page, referer,
                json.dumps(utm, ensure_ascii=False),
                json.dumps(fields, ensure_ascii=False)
            ])
    except Exception as e:
        return False, f"Ошибка записи: {e}"

    text = (
        f"Новая заявка: {lead_type}\n"
        f"Страница: {page}\n"
        f"Телефон: {fields.get('phone','')}\n"
        f"Имя: {fields.get('name','')}\n"
        f"Комментарий: {fields.get('message','')}"
    )
    try:
        send_telegram(text)
    except Exception:
        pass
    try:
        send_email("Новая заявка — Мир манипуляторов", text)
    except Exception:
        pass

    return True, "Заявка отправлена. Мы скоро свяжемся."


def slugify(s: str) -> str:
    s = (s or "").strip().lower()
    s = re.sub(r"[^a-z0-9]+", "-", s)
    s = re.sub(r"-{2,}", "-", s).strip("-")
    return s or "item"

def safe_filename(s: str) -> str:
    """Safe filename-like slug (latin/nums/_/-)."""
    s = (s or "").strip().lower()
    s = re.sub(r"[^a-z0-9_-]+", "-", s)
    s = re.sub(r"-{2,}", "-", s).strip("-")
    return s or "item"


def ensure_products_seed():
    """Ensure products.json exists. If missing/empty and products.csv exists, seed from CSV."""
    os.makedirs(os.path.dirname(PRODUCTS_JSON), exist_ok=True)

    products = read_json(PRODUCTS_JSON, None)
    if isinstance(products, list) and len(products) > 0:
        return

    if os.path.exists(PRODUCTS_CSV):
        seeded = []
        try:
            with open(PRODUCTS_CSV, "r", encoding="utf-8") as f:
                reader = csv.DictReader(f)
                for row in reader:
                    pid = (row.get("id") or "").strip() or f"kmu-{len(seeded)+1:03d}"
                    category = (row.get("category") or "kmu").strip() or "kmu"
                    brand = (row.get("brand") or "").strip()
                    model = (row.get("model") or "").strip()
                    title = (row.get("title") or "").strip()
                    if not title:
                        title = " ".join([x for x in [brand, model] if x]).strip() or pid

                    slug = (row.get("slug") or "").strip()
                    if not slug:
                        slug = f"{slugify(brand)}-{slugify(model)}-{pid}".strip("-")
                        slug = re.sub(r"-{2,}", "-", slug).strip("-")

                    year = (row.get("year") or "").strip()
                    status = (row.get("status") or "").strip()
                    price = (row.get("price") or "").strip()
                    city = (row.get("city") or "").strip()
                    image = (row.get("image") or "/assets/img/placeholder.svg").strip() or "/assets/img/placeholder.svg"
                    short = (row.get("short") or "").strip()
                    desc = (row.get("description") or "").strip()
                    specs = (row.get("specs") or "").strip()

                    seeded.append({
                        "id": pid,
                        "slug": slug,
                        "category": category,
                        "brand": brand,
                        "model": model,
                        "year": year,
                        "status": status,
                        "price": price,
                        "city": city,
                        "image": image,
                        "short": short,
                        "description": desc,
                        "specs": specs,
                        "title": title,
                        "popular": str(row.get("popular") or "").strip().lower() in ("1","true","yes","да","y"),
                    })
        except Exception as e:
            print("WARN: products seed from CSV failed:", repr(e))
            seeded = []

        if seeded:
            write_json_atomic(PRODUCTS_JSON, seeded)
            return

    fallback = [{
        "id": "kmu-001",
        "slug": "kmu-001",
        "category": "kmu",
        "brand": "Palfinger",
        "model": "PK 17502",
        "year": "2006",
        "status": "В наличии",
        "price": "Цена по запросу",
        "city": "Санкт-Петербург",
        "image": "/assets/img/placeholder.svg",
        "short": "Универсальная КМУ для стройки и погрузки.",
        "description": "Подбор аналогов. Документы. Логистика.",
        "cargo": "до 7 т",
        "outreach": "до 14 м",
        "sections": "5",
        "control": "пульт",
        "title": "Palfinger PK 17502",
        "popular": True,
    }]
    write_json_atomic(PRODUCTS_JSON, fallback)

def send_telegram(text: str):
    if not TELEGRAM_BOT_TOKEN or not TELEGRAM_CHAT_ID:
        return False, "Telegram not configured"
    url = f"https://api.telegram.org/bot{TELEGRAM_BOT_TOKEN}/sendMessage"
    payload = json.dumps({"chat_id": TELEGRAM_CHAT_ID, "text": text, "disable_web_page_preview": True}).encode("utf-8")
    req = Request(url, data=payload, headers={"Content-Type": "application/json"})
    try:
        with urlopen(req, timeout=10) as r:
            data = r.read().decode("utf-8", errors="ignore")
        return True, data
    except Exception as e:
        return False, str(e)

def send_email(subject: str, body: str):
    if not (SMTP_HOST and SMTP_USER and SMTP_PASS and SMTP_TO):
        return False, "SMTP not configured"
    try:
        import smtplib
        from email.mime.text import MIMEText
        msg = MIMEText(body, _charset="utf-8")
        msg["Subject"] = subject
        msg["From"] = SMTP_USER
        msg["To"] = SMTP_TO

        with smtplib.SMTP(SMTP_HOST, SMTP_PORT, timeout=15) as s:
            s.ehlo()
            try:
                s.starttls(context=ssl.create_default_context())
                s.ehlo()
            except Exception:
                pass
            s.login(SMTP_USER, SMTP_PASS)
            s.sendmail(SMTP_USER, [SMTP_TO], msg.as_string())
        return True, "sent"
    except Exception as e:
        return False, str(e)

def _now_date():
    return datetime.utcnow().date().isoformat()

def read_json(path: Path, default):
    try:
        return json.loads(path.read_text(encoding="utf-8"))
    except Exception:
        return default

def write_json_atomic(path: Path, obj):
    tmp = path.with_suffix(path.suffix + ".tmp")
    tmp.write_text(json.dumps(obj, ensure_ascii=False, indent=2), encoding="utf-8")
    tmp.replace(path)


def load_settings():
    s = read_json(SETTINGS_JSON, DEFAULT_SETTINGS)
    if not isinstance(s, dict):
        s = dict(DEFAULT_SETTINGS)
    out = dict(DEFAULT_SETTINGS)

    td = (s.get("theme_default") or out.get("theme_default") or "blue").strip() or "blue"
    if td not in ("blue", "white"):
        td = "blue"
    out["theme_default"] = td

    out["logo_path"] = (s.get("logo_path") or "").strip()
    out["hero_bg_path"] = (s.get("hero_bg_path") or "").strip()

    return out

def save_settings(new_settings: dict):
    cur = load_settings()
    if not isinstance(new_settings, dict):
        new_settings = {}

    if "theme_default" in new_settings:
        td = (new_settings.get("theme_default") or "").strip()
        if td in ("blue", "white"):
            cur["theme_default"] = td

    if "logo_path" in new_settings:
        cur["logo_path"] = (new_settings.get("logo_path") or "").strip()
    if "hero_bg_path" in new_settings:
        cur["hero_bg_path"] = (new_settings.get("hero_bg_path") or "").strip()

    os.makedirs(SETTINGS_JSON.parent, exist_ok=True)
    write_json_atomic(SETTINGS_JSON, cur)
    return cur

def specs_to_table(specs: str):
    """
    Превращает строку характеристик в таблицу.
    Поддерживает разделители: перенос строки, ;, |
    Формат: "Ключ: Значение"
    """
    if not specs:
        return []
    parts = re.split(r'[\n\r;|]\s*', specs)
    rows = []
    for p in parts:
        p = p.strip()
        if not p:
            continue
        if ":" in p:
            k, v = p.split(":", 1)
            rows.append({"k": k.strip(), "v": v.strip()})
        else:
            rows.append({"k": "Параметр", "v": p})
    return rows

def esc(s: str) -> str:
    return (s or "").replace("&","&amp;").replace("<","&lt;").replace(">","&gt;").replace('"',"&quot;").replace("'","&#39;")


# ============================
# Multi-image helpers (max 10)
# ============================
_IMG_PLACEHOLDER = "/assets/img/placeholder.svg"

def _parse_images_value(val):
    """Accept list or string; split by newline/|/;/, and return cleaned list."""
    if val is None:
        return []
    items = []
    if isinstance(val, list):
        items = val
    elif isinstance(val, tuple):
        items = list(val)
    else:
        s = str(val)
        s = s.strip()
        if s.startswith('[') and s.endswith(']'):
            s = s[1:-1]
        parts = re.split(r"[\n\r\t\|;,]+", s)
        items = parts
    out = []
    seen = set()
    for x in items:
        if x is None:
            continue
        s = str(x).strip().strip('"').strip("'")
        if not s:
            continue
        s = re.sub(r"\s+", " ", s).strip()
        if s in seen:
            continue
        seen.add(s)
        out.append(s)
        if len(out) >= 10:
            break
    return out

def get_product_images(p):
    """Return normalized list of images for product (max 10), fallback to placeholder."""
    if not isinstance(p, dict):
        return [_IMG_PLACEHOLDER]
    imgs = []
    if "images" in p:
        imgs.extend(_parse_images_value(p.get("images")))
    for i in range(2, 11):
        for key in (f"image{i}", f"img{i}", f"photo{i}"):
            if key in p:
                imgs.extend(_parse_images_value(p.get(key)))
    main = p.get("image")
    if isinstance(main, str) and main.strip():
        main = main.strip()
        if main not in imgs:
            imgs.insert(0, main)
    imgs = [x for x in imgs if isinstance(x, str) and x.strip()]
    if not imgs:
        imgs = [_IMG_PLACEHOLDER]
    return imgs[:10]

def _specs_from_separate_fields(p: dict):
    """Собирает specs_table/specs из отдельных полей cargo/outreach/sections/control."""
    if not isinstance(p, dict):
        return
    cargo = (p.get("cargo") or "").strip()
    outreach = (p.get("outreach") or "").strip()
    sections = (str(p.get("sections") or "")).strip()
    control = (p.get("control") or "").strip()

    rows = []
    if cargo: rows.append({"k": "Груз", "v": cargo})
    if outreach: rows.append({"k": "Вылет", "v": outreach})
    if sections: rows.append({"k": "Секций", "v": sections})
    if control: rows.append({"k": "Управление", "v": control})

    if rows:
        p["specs_table"] = rows
        if not (p.get("specs") or "").strip():
            p["specs"] = "\n".join([f'{r["k"]}: {r["v"]}' for r in rows])

def normalize_product(p: dict) -> dict:
    """Normalize product fields in-place. Ensures p.images list and p.image cover."""
    if not isinstance(p, dict):
        return p
    p.setdefault("category", "kmu")
    p.setdefault("brand", "")
    p.setdefault("model", "")
    p.setdefault("year", "")
    p.setdefault("status", "В наличии")
    p.setdefault("price", "Цена по запросу")
    p.setdefault("city", "")
    p.setdefault("short", "")
    p.setdefault("description", "")
    p.setdefault("specs", "")
    p.setdefault("cta", "Узнать цену")
    p.setdefault("featured", False)
    p.setdefault("featured_rank", "")

    _specs_from_separate_fields(p)

    # Title/slug
    if not (p.get("title") or "").strip():
        t = " ".join([x for x in [p.get("brand","").strip(), p.get("model","").strip()] if x]).strip()
        p["title"] = t or (p.get("id") or "Товар")
    if not (p.get("slug") or "").strip():
        base = p.get("title") or p.get("id") or "item"
        p["slug"] = slugify(str(base))

    # Specs table
    st = p.get("specs_table")
    if not isinstance(st, list):
        p["specs_table"] = specs_to_table(p.get("specs",""))
    elif len(st)==0 and (p.get("specs") or "").strip():
        p["specs_table"] = specs_to_table(p.get("specs",""))

    # Images
    imgs = get_product_images(p)
    p["images"] = imgs[:10]
    p["image"] = imgs[0] if imgs else _IMG_PLACEHOLDER
    return p

def normalize_products_list(prods):
    out = []
    if not isinstance(prods, list):
        return out
    for x in prods:
        if isinstance(x, dict):
            out.append(normalize_product(x))
    return out


# --------------------------
# Upload helpers (NO cgi)
# --------------------------
def _get_boundary(content_type: str):
    if not content_type:
        return None
    m = re.search(r'boundary=("?)([^";]+)\1', content_type, flags=re.I)
    if not m:
        return None
    return m.group(2).encode("utf-8", errors="ignore")

def _guess_boundary_from_body(body: bytes):
    """
    Если фронт неправильно выставил Content-Type без boundary,
    попробуем достать boundary из первой строки тела:
      ------WebKitFormBoundary....\r\n
    """
    if not body:
        return None
    eol = body.find(b"\r\n")
    if eol <= 2:
        return None
    first = body[:eol].strip()
    if not first.startswith(b"--"):
        return None
    return first[2:]  # without leading --

def _parse_multipart(body: bytes, content_type: str):
    """
    Возвращает:
      fields: dict[str, str]
      files: list[dict{name, filename, content_type, data(bytes)}]
    """
    boundary = _get_boundary(content_type) or _guess_boundary_from_body(body)
    if not boundary:
        raise ValueError("Boundary not found in Content-Type and could not be guessed from body")

    header = (
        f"Content-Type: multipart/form-data; boundary={boundary.decode('utf-8','ignore')}\r\n"
        "MIME-Version: 1.0\r\n\r\n"
    ).encode("utf-8")
    msg = BytesParser(policy=email_default_policy).parsebytes(header + body)

    fields = {}
    files = []

    for part in msg.iter_parts():
        disp = part.get_content_disposition()
        if disp != "form-data":
            continue

        name = part.get_param("name", header="content-disposition") or ""
        filename = part.get_filename()

        if filename:
            data = part.get_payload(decode=True) or b""
            files.append({
                "name": name,
                "filename": filename,
                "content_type": part.get_content_type() or "application/octet-stream",
                "data": data,
            })
        else:
            try:
                val = part.get_content()
            except Exception:
                payload = part.get_payload(decode=True) or b""
                val = payload.decode("utf-8", errors="replace")
            fields[name] = str(val)

    return fields, files

def save_uploaded_image_bytes(filename: str, data: bytes, base_name="image", subdir="", allowed_ext=None) -> str:
    """Сохраняет байты файла в /assets/uploads/<subdir>/ и возвращает web-path."""
    if not filename:
        raise ValueError("filename missing")

    ext = os.path.splitext(filename)[1].lower()
    if not ext:
        raise ValueError("file extension missing")

    if allowed_ext is not None and ext not in allowed_ext:
        raise ValueError(f"Недопустимое расширение {ext}. Разрешено: {', '.join(sorted(allowed_ext))}")

    allowed_default = {".jpg",".jpeg",".png",".webp",".avif",".jfif",".gif"}
    if ext not in allowed_default and allowed_ext is None:
        raise ValueError(f"Недопустимый тип файла {ext}")

    safe_base = safe_filename(base_name)[:60]
    safe_subdir = safe_filename(subdir)[:60] if subdir else ""
    os.makedirs(UPLOADS_DIR, exist_ok=True)

    out_dir = UPLOADS_DIR
    web_dir = "/assets/uploads"
    if safe_subdir:
        out_dir = os.path.join(UPLOADS_DIR, safe_subdir)
        web_dir = f"/assets/uploads/{safe_subdir}"
        os.makedirs(out_dir, exist_ok=True)

    token = uuid.uuid4().hex[:10]
    out_name = f"{safe_base}-{token}{ext}"
    out_path = os.path.join(out_dir, out_name)

    with open(out_path, "wb") as f:
        f.write(data)

    return f"{web_dir}/{out_name}"


# ============================
# HTML rendering (как у тебя)
# ============================
def carousel_html(images, title, large=False):
    imgs = (images or [])[:10]
    if not imgs:
        imgs = [_IMG_PLACEHOLDER]
    slides = []
    for i, src in enumerate(imgs):
        s = esc(src)
        a = esc(title) + (f" — фото {i+1}" if title else f"Фото {i+1}")
        slides.append(
            f'<img src="{s}" alt="{a}" loading="lazy" width="{"1200" if large else "640"}" height="{"800" if large else "420"}">'
        )
    slides_html = "".join(slides)
    if len(imgs) <= 1:
        return f'<div class="carousel" data-carousel><div class="carousel-track">{slides_html}</div></div>'
    dots = "".join([f'<button type="button" class="carousel-dot" data-dot="{i}" aria-label="Фото {i+1}"></button>' for i in range(len(imgs))])
    return (
        '<div class="carousel" data-carousel>'
        f'<div class="carousel-track">{slides_html}</div>'
        '<button type="button" class="carousel-btn" data-prev aria-label="Предыдущее фото">‹</button>'
        '<button type="button" class="carousel-btn" data-next aria-label="Следующее фото">›</button>'
        f'<div class="carousel-dots">{dots}</div>'
        '</div>'
    )

def abs_url(path: str) -> str:
    path = path or "/"
    if path.startswith("http://") or path.startswith("https://"):
        return path
    if not path.startswith("/"):
        path = "/" + path
    return SITE_URL + path

def org_ld() -> dict:
    return {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "Мир манипуляторов",
        "url": abs_url("/"),
        "email": "infocrane9@gmail.com",
        "telephone": "79817105640",
        "address": {"@type": "PostalAddress", "addressLocality": "Санкт-Петербург", "addressCountry": "RU"}
    }

def website_ld() -> dict:
    return {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "Мир манипуляторов",
        "url": abs_url("/"),
        "potentialAction": {
            "@type": "SearchAction",
            "target": abs_url("/catalog/?q={search_term_string}"),
            "query-input": "required name=search_term_string"
        }
    }

def breadcrumb_ld(items: list) -> dict:
    return {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {"@type": "ListItem", "position": i + 1, "name": name, "item": abs_url(url)}
            for i, (name, url) in enumerate(items)
        ]
    }

def site_header(active="/catalog/"):
    nav = [
        ("/catalog/", "Каталог", "catalog"),
        ("/brands/", "Производители", "brands"),
        ("/services/", "Услуги", "services"),
        ("/about/", "О компании", "about"),
        ("/contacts/", "Контакты", "contacts"),
        ("/blog/", "База знаний", "blog"),
    ]
    nav_links = []
    for href, label, key in nav:
        cls = "active" if href == active else ""
        nav_links.append(f'<a href="{href}" class="{cls}" data-nav="{key}">{label}</a>')
    nav_html = "\n      ".join(nav_links)

    mobile_links = [
        '<a class="btn sm" href="/catalog/">Каталог</a>',
        '<a class="btn sm" href="/brands/">Производители</a>',
        '<a class="btn sm" href="/services/">Услуги</a>',
        '<a class="btn sm" href="/about/">О компании</a>',
        '<a class="btn sm" href="/contacts/">Контакты</a>',
        '<a class="btn sm" href="/blog/">База знаний</a>',
    ]
    mobile_html = "".join(mobile_links)

    return f"""<header class="header">
  <div class="container header-inner">
    <a class="brand" href="/"><span class="logo"><img id="siteLogoImg" class="logo-img" src="" alt="Логотип" style="display:none" /><svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M5 19h14" stroke="white" stroke-opacity=".9" stroke-width="2" stroke-linecap="round"/>
<path d="M7 19V8l8-3v14" stroke="white" stroke-opacity=".9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M15 10l4 2v7" stroke="white" stroke-opacity=".9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg></span><span><strong>Мир манипуляторов</strong><span>КМУ из Европы • СПб → РФ</span></span></a>

    <nav class="nav" aria-label="Главное меню">
      {nav_html}
    </nav>

    <div class="header-cta">
      <a class="btn sm ghost mobile-toggle" id="mobileToggle" href="javascript:void(0)">Меню</a>

      <div class="theme-switch" title="Синяя / Белая">
        <span class="ts-label">Синяя</span>
        <label class="switch">
          <input type="checkbox" id="themeSwitch" aria-label="Переключить тему (синяя/белая)">
          <span class="slider"></span>
        </label>
        <span class="ts-label">Белая</span>
      </div>

      <a class="btn sm" href="tel:+79817105640" data-evt="lead_call">Позвонить</a>
      <a class="btn sm primary" href="/services/podbor/" data-evt="lead_pick">Подобрать КМУ</a>
    </div>
  </div>

  <div class="container" id="mobileNav" data-open="0" style="display:none; padding-bottom:14px;">
    <div class="card pad">
      <div style="display:flex;flex-wrap:wrap;gap:10px">
        {mobile_html}
      </div>
    </div>
  </div>
</header>""".strip()

def site_footer():
    year = datetime.utcnow().year
    return f"""
<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <div class="brand">Мир манипуляторов</div>
      <p class="muted">КМУ и полуприцепы из Европы: продажа, доставка, установка, сервис.</p>
    </div>
    <div class="footer-col">
      <b>Навигация</b>
      <a href="/catalog/">Каталог</a>
      <a href="/services/">Услуги</a>
      <a href="/contacts/">Контакты</a>
      <a href="/admin/">Админка</a>
    </div>
    <div class="footer-col">
      <b>Связь</b>
      <a href="tel:+79817105640">+7 (981) 710-56-40</a>
      <a href="mailto:infocrane9@gmail.com">infocrane9@gmail.com</a>
    </div>
  </div>
  <div class="container muted small" style="padding:12px 0">© {year} Мир манипуляторов</div>
</footer>
""".strip()

def render_product_card(p):
    p = normalize_product(p)
    href = f"/catalog/{esc(p.get('category','kmu'))}/{esc(p.get('slug',''))}/"
    title = esc(p.get("title") or p.get("name") or "")
    short = esc(p.get("short") or "")
    images = get_product_images(p)
    price = esc(p.get("price") or "Цена по запросу")
    status = esc(p.get("status") or "")
    city = esc(p.get("city") or "")

    tags = []
    if status: tags.append(f'<span class="tag">{status}</span>')
    if city: tags.append(f'<span class="tag">{city}</span>')
    tags.append(f'<span class="tag">{price}</span>')

    return f"""
    <article class="product">
      <div class="pimg">
        {carousel_html(images, title)}
        <a class="pimg-link" href="{href}" aria-label="Открыть карточку"></a>
      </div>
      <div class="pbody">
        <h3 class="ptitle"><a href="{href}">{title}</a></h3>
        <p class="muted">{short}</p>
        <div class="meta">{''.join(tags)}</div>
        <div class="actions">
          <a class="btn sm" href="{href}">Подробнее</a>
          <a class="btn primary sm" href="{href}#request">Узнать цену</a>
        </div>
      </div>
    </article>
    """.strip()

def render_catalog_page(cat, prods):
    cards = "\n".join([render_product_card(p) for p in prods if (p.get("category") or "kmu") == cat])

    filters = f"""
      <div class="card pad catalog-filters">
        <div class="filters-grid">
          <label class="field filters-search">
            <span>Поиск</span>
            <input class="input" id="f_q" placeholder="Например: Palfinger, 2018, 12 м..." />
          </label>

          <label class="field">
            <span>Бренд</span>
            <select class="input" id="f_brand"></select>
          </label>

          <label class="field">
            <span>Год</span>
            <select class="input" id="f_year"></select>
          </label>

          <label class="field">
            <span>Груз</span>
            <select class="input" id="f_cargo"></select>
          </label>

          <label class="field">
            <span>Вылет</span>
            <select class="input" id="f_outreach"></select>
          </label>

          <label class="field">
            <span>Секций</span>
            <select class="input" id="f_sections"></select>
          </label>

          <label class="field">
            <span>Сортировка</span>
            <select class="input" id="f_sort">
              <option value="relevance">По релевантности</option>
              <option value="name_asc">По названию (A→Z)</option>
              <option value="year_desc">По году (новые сверху)</option>
              <option value="updated_desc">По обновлению</option>
            </select>
          </label>
        </div>

        <div class="filters-row">
          <div class="muted small" id="f_count"></div>
          <button class="btn btn-ghost" type="button" id="f_clear">Сбросить</button>
        </div>

        <div class="muted small">Фильтры работают на клиенте и не мешают SEO: страницы товаров остаются статическими.</div>
      </div>
    """

    return f"""<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Каталог {cat.upper()} — Мир манипуляторов</title>
  <meta name="description" content="Каталог {cat.upper()}: товары в наличии и под заказ. Детальные страницы, характеристики и форма запроса цены." />
  <link rel="canonical" href="{abs_url(f"/catalog/{cat}/")}" />
  <meta name="robots" content="index, follow" />
  <meta property="og:title" content="Каталог {cat.upper()} — Мир манипуляторов" />
  <meta property="og:description" content="Каталог {cat.upper()}: товары в наличии и под заказ. Детальные страницы, характеристики и форма запроса цены." />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="{abs_url(f"/catalog/{cat}/")}" />
  <meta property="og:image" content="{abs_url("/assets/img/favicon.svg")}" />
  <meta name="twitter:card" content="summary" />
  <script type="application/ld+json">{json.dumps([org_ld(), website_ld(), breadcrumb_ld([("Главная","/"),("Каталог","/catalog/"),(cat.upper(), f"/catalog/{cat}/")])], ensure_ascii=False)}</script>
  <link rel="stylesheet" href="/assets/css/styles.css" />
</head>
<body>
  {site_header("/catalog/")}
  <main class="container">
    <section class="section">
      <nav class="breadcrumbs" aria-label="breadcrumb">
  <a href="/">Главная</a><span class="bc-sep">/</span>
  <a href="/catalog/">Каталог</a><span class="bc-sep">/</span>
  <span>{cat.upper()}</span>
</nav>
<h1>Каталог: {cat.upper()}</h1>
      <p class="lead">Нажми на карточку, чтобы открыть детальную страницу товара с характеристиками и формой запроса.</p>
      {filters}
      <div class="products" id="catalogGrid">
        {cards if cards.strip() else '<p class="muted">Пока нет товаров в этой категории.</p>'}
      </div>
    </section>
  </main>
  {site_footer()}
  <script src="/assets/js/main.js"></script>
  <script>window.__CATALOG_CATEGORY = "{cat}";</script>
  <script src="/assets/js/catalog-filters.js"></script>
</body>
</html>"""

def render_product_page(p, prods):
    p = normalize_product(p)
    title = esc(p.get("title") or p.get("name") or "")
    cat = p.get("category") or "kmu"
    slug = p.get("slug") or ""
    images = get_product_images(p)
    cover = images[0] if images else _IMG_PLACEHOLDER
    og_img_abs = abs_url(cover) if isinstance(cover, str) and cover.startswith("/") else cover

    short = esc(p.get("short") or "")
    desc_raw = (p.get("description") or "").strip()
    desc_html = "<p>" + "</p><p>".join([esc(x) for x in desc_raw.splitlines() if x.strip()]) + "</p>" if desc_raw else '<p class="muted">Описание скоро появится.</p>'

    brand = esc(p.get("brand") or "")
    model = esc(p.get("model") or "")
    year  = esc(str(p.get("year") or ""))
    status = esc(p.get("status") or "")
    price  = esc(p.get("price") or "Цена по запросу")
    city   = esc(p.get("city") or "")

    # Specs table
    specs_rows = p.get("specs_table") or []
    specs_html = ""
    if isinstance(specs_rows, list) and specs_rows:
        trs = []
        for r in specs_rows:
            if not isinstance(r, dict):
                continue
            k = esc(r.get("k") or "")
            v = esc(r.get("v") or "")
            trs.append(f"<tr><td>{k}</td><td>{v}</td></tr>")
        if trs:
            specs_html = f'<table class="specs-table">{"".join(trs)}</table>'
    if not specs_html:
        specs_html = '<p class="muted">Характеристики уточняйте у менеджера.</p>'

    # Similar products
    similar = [x for x in prods if (x.get("category") or "kmu")==cat and x.get("id")!=p.get("id")][:3]
    similar_html = ""
    if similar:
        similar_cards = "\n".join(render_product_card(x) for x in similar)
        similar_html = f"""<section class="section">
  <h2>Похожие товары</h2>
  <div class="products">{similar_cards}</div>
</section>"""

    # Schema.org
    product_ld = {
        "@context":"https://schema.org",
        "@type":"Product",
        "name": p.get("title") or p.get("name") or "",
        "url": abs_url(f"/catalog/{cat}/{slug}/"),
        "description": p.get("short") or "",
        "image": [],
    }
    for im in images[:10]:
        if isinstance(im, str) and im.startswith("/"):
            product_ld["image"].append(abs_url(im))
        else:
            product_ld["image"].append(im)
    if p.get("brand"):
        product_ld["brand"] = {"@type":"Brand","name": p.get("brand")}
    product_ld["offers"] = {
        "@type":"Offer",
        "priceCurrency":"RUB",
        "price": re.sub(r"[^0-9.]", "", str(p.get("price") or "")) or "0",
        "availability":"https://schema.org/InStock",
        "url": abs_url(f"/catalog/{cat}/{slug}/")
    }

    faq_ld = {
        "@context":"https://schema.org",
        "@type":"FAQPage",
        "mainEntity":[
            {"@type":"Question","name":"Как узнать цену и наличие?","acceptedAnswer":{"@type":"Answer","text":"Оставьте заявку — мы быстро уточним цену, наличие и комплектацию."}},
            {"@type":"Question","name":"Есть доставка и установка?","acceptedAnswer":{"@type":"Answer","text":"Да, организуем доставку и при необходимости установку/подключение."}},
        ]
    }

    hero_img = carousel_html(images, title, large=True)

    return f"""<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>{title} — Мир манипуляторов</title>
  <meta name="description" content="{short or title}" />
  <link rel="canonical" href="{abs_url(f"/catalog/{cat}/{slug}/")}" />
  <meta property="og:type" content="product" />
  <meta property="og:title" content="{title}" />
  <meta property="og:description" content="{short or title}" />
  <meta property="og:url" content="{abs_url(f"/catalog/{cat}/{slug}/")}" />
  <meta property="og:image" content="{og_img_abs}" />
  <meta name="twitter:card" content="summary_large_image" />
  <link rel="stylesheet" href="/assets/css/styles.css" />
  <script type="application/ld+json">{json.dumps([org_ld(), website_ld(), breadcrumb_ld([("Главная","/"),("Каталог","/catalog/"),(cat.upper(), f"/catalog/{cat}/"),(title, f"/catalog/{cat}/{slug}/")]), product_ld, faq_ld], ensure_ascii=False)}</script>
  <style>
    .specs-table{{width:100%;border-collapse:collapse}}
    .specs-table td{{border-bottom:1px solid rgba(255,255,255,.08);padding:10px 8px;vertical-align:top}}
    .specs-table td:first-child{{opacity:.85;width:45%}}
    .crumbs{{font-size:.9rem;opacity:.85;margin:14px 0}}
    .crumbs a{{color:inherit}}
    .product-gallery{{border-radius:14px;overflow:hidden;border:1px solid var(--line)}}
    .product-gallery .carousel{{height:100%}}
    .product-gallery .carousel-track{{aspect-ratio:16/10}}
  </style>
</head>
<body>
  {site_header("/catalog/")}
  <main class="container">
    <div class="crumbs"><a href="/">Главная</a> · <a href="/catalog/">Каталог</a> · <a href="/catalog/{esc(cat)}/">{esc(cat).upper()}</a> · {title}</div>

    <section class="section">
      <div class="grid2">
        <div>
          <h1 style="margin-top:0">{title}</h1>
          <div class="meta">
            {f'<span class="tag">{brand}</span>' if brand else ''}
            {f'<span class="tag">Модель: {model}</span>' if model else ''}
            {f'<span class="tag">Год: {year}</span>' if year else ''}
            {f'<span class="tag">{status}</span>' if status else ''}
            {f'<span class="tag">{city}</span>' if city else ''}
            <span class="tag">{price}</span>
          </div>

          <p class="muted">{short}</p>
          <div class="actions">
            <a class="btn primary" href="#request">{esc(p.get("cta") or "Узнать цену и наличие")}</a>
            <a class="btn ghost" href="/catalog/{esc(cat)}/">Назад в каталог</a>
          </div>
          <p class="notice">💡 Наличие и комплектацию уточняем быстро. Возможна доставка и установка.</p>
        </div>
        <div>
          <div class="card pad product-gallery">
            {hero_img}
          </div>
        </div>
      </div>
    </section>

    <section class="section">
      <div class="card pad">
        <h2 style="margin-top:0">Описание</h2>
        {desc_html}
      </div>
    </section>

    <section class="section">
      <div class="card pad">
        <h2 style="margin-top:0">Характеристики</h2>
        {specs_html}
      </div>
    </section>

    <section class="section" id="request">
      <div class="card pad">
        <h2 style="margin-top:0">Запросить цену</h2>
        <form class="lead-form" data-lead-type="price" data-page="/catalog/{esc(cat)}/{esc(slug)}/">
          <div class="grid2">
            <label class="field"><span>Имя</span><input class="input" name="name" required placeholder="Как к вам обращаться?"></label>
            <label class="field"><span>Телефон</span><input class="input" name="phone" required placeholder="+7..." inputmode="tel"></label>
          </div>
          <label class="field"><span>Комментарий</span><textarea class="input" name="message" rows="4" placeholder="Уточните комплектацию, доставку, монтаж..."></textarea></label>
          <button class="btn primary" type="submit">Отправить</button>
        </form>
      </div>
    </section>

    {similar_html}
  </main>
  {site_footer()}
  <script src="/assets/js/main.js"></script>
</body>
</html>
"""

def rebuild_static():
    prods = read_json(PRODUCTS_JSON, [])
    prods = normalize_products_list(prods)
    write_json_atomic(PRODUCTS_JSON, prods)

    cats = sorted(set((p.get("category") or "kmu") for p in prods))
    for cat in cats:
        cat_dir = ROOT / "catalog" / cat
        cat_dir.mkdir(parents=True, exist_ok=True)
        (cat_dir / "index.html").write_text(render_catalog_page(cat, prods), encoding="utf-8")
        for p in [x for x in prods if (x.get("category") or "kmu") == cat]:
            pdir = cat_dir / (p.get("slug") or "")
            pdir.mkdir(parents=True, exist_ok=True)
            (pdir / "index.html").write_text(render_product_page(p, prods), encoding="utf-8")

    urls = [
        "/", "/catalog/", "/services/", "/brands/", "/about/", "/contacts/", "/blog/",
        "/services/podbor/","/services/dostavka/","/services/ustanovka/","/services/remont/","/services/zapchasti/",
        "/admin/","/admin/leads/",
    ]
    for cat in cats:
        urls.append(f"/catalog/{cat}/")
    for p in prods:
        urls.append(f"/catalog/{p.get('category','kmu')}/{p.get('slug','')}/")

    try:
        for pth in (ROOT / "blog").glob("*/index.html"):
            rel = "/" + str(pth.relative_to(ROOT)).replace(os.sep, "/")
            rel = rel.replace("/index.html", "/")
            urls.append(rel)
    except Exception:
        pass

    lastmod = _now_date()
    xml = ['<?xml version="1.0" encoding="UTF-8"?>',
           '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">']
    for u in urls:
        xml.append("  <url>")
        xml.append(f"    <loc>{SITE_URL}{u}</loc>")
        xml.append(f"    <lastmod>{lastmod}</lastmod>")
        xml.append("    <changefreq>weekly</changefreq>")
        xml.append("  </url>")
    xml.append("</urlset>")
    (ROOT / "sitemap.xml").write_text("\n".join(xml), encoding="utf-8")
    (ROOT / "robots.txt").write_text("User-agent: *\nAllow: /\nSitemap: /sitemap.xml\n", encoding="utf-8")


class Handler(SimpleHTTPRequestHandler):
    def _json(self, code: int, obj):
        b = json.dumps(obj, ensure_ascii=False).encode("utf-8")
        self.send_response(code)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(b)))
        self.end_headers()
        self.wfile.write(b)

    def _text(self, code: int, text: str, ctype="text/plain; charset=utf-8"):
        b = text.encode("utf-8")
        self.send_response(code)
        self.send_header("Content-Type", ctype)
        self.send_header("Content-Length", str(len(b)))
        self.end_headers()
        self.wfile.write(b)

    def _rate_limited(self, ip: str) -> bool:
        now = time.time()
        last = _ip_last.get(ip, 0.0)
        if now - last < RATE_LIMIT_SECONDS:
            return True
        _ip_last[ip] = now
        return False

    def _require_admin(self):
        if basic_auth_ok(self.headers):
            return True
        self.send_response(401)
        self.send_header("WWW-Authenticate", 'Basic realm="Admin"')
        self.end_headers()
        return False

    def end_headers(self):
        try:
            p = urlparse(self.path).path
            if p.startswith('/admin') or p.endswith('.html'):
                self.send_header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                self.send_header('Pragma', 'no-cache')
        except Exception:
            pass
        super().end_headers()

    def do_GET(self):
        parsed = urlparse(self.path)
        path = parsed.path

        if (
            path.startswith("/admin") or
            path.startswith("/api/products") or
            path.startswith("/api/leads") or
            path.startswith("/api/import_csv") or
            path.startswith("/api/settings") or
            path.startswith("/api/rebuild") or
            path.startswith("/api/upload")
        ):
            if not self._require_admin():
                return

        # Public products (for catalog filters, no auth)
        if path == "/api/public/products":
            return self._json(200, normalize_products_list(read_json(PRODUCTS_JSON, [])))

        # Public settings (theme default + optional logo/bg)
        if path == "/api/public/settings":
            s = load_settings()
            return self._json(200, {
                "theme_default": s.get("theme_default","blue"),
                "logo_path": s.get("logo_path",""),
                "hero_bg_path": s.get("hero_bg_path",""),
            })

        if path == "/api/products":
            return self._json(200, normalize_products_list(read_json(PRODUCTS_JSON, [])))

        if path == "/api/settings":
            if not self._require_admin():
                return
            return self._json(200, load_settings())

        if path == "/api/leads":
            return self._json(200, parse_leads())

        if path == "/api/leads.csv":
            if not self._require_admin():
                return
            ensure_leads_csv()
            with open(LEADS_CSV, "r", encoding="utf-8") as f:
                content = f.read()
            return self._text(200, content, "text/csv; charset=utf-8")

        return super().do_GET()

    def do_POST(self):
        parsed = urlparse(self.path)
        path = parsed.path

        # Public lead endpoint
        if path == "/api/lead":
            ip = self.client_address[0]
            if self._rate_limited(ip):
                return self._json(429, {"ok": False, "msg": "Слишком часто. Попробуйте чуть позже."})

            length = int(self.headers.get("Content-Length", "0") or "0")
            if length <= 0 or length > MAX_BODY_BYTES:
                return self._json(400, {"ok": False, "msg": "Некорректный размер запроса"})

            try:
                raw = self.rfile.read(length)
                payload = json.loads(raw.decode("utf-8")) if raw else {}
            except Exception:
                payload = {}

            try:
                ok, msg = save_lead(payload, ip=ip, referer=self.headers.get("Referer",""))
                return self._json(200, {"ok": ok, "msg": msg})
            except Exception as e:
                return self._json(500, {"ok": False, "msg": "Ошибка сервера", "error": str(e)})

        # Admin auth for everything below
        if (
            path.startswith("/admin") or
            path.startswith("/api/products") or
            path.startswith("/api/leads") or
            path.startswith("/api/import_csv") or
            path.startswith("/api/settings") or
            path.startswith("/api/rebuild") or
            path.startswith("/api/upload")
        ):
            if not self._require_admin():
                return

        # Helper: read JSON body (for settings/products)
        payload = {}
        ctype = (self.headers.get("Content-Type", "") or "")
        if "application/json" in ctype:
            try:
                length = int(self.headers.get("Content-Length", "0") or "0")
            except Exception:
                length = 0
            if length > 0:
                raw = self.rfile.read(min(length, MAX_BODY_BYTES))
                try:
                    payload = json.loads(raw.decode("utf-8"))
                except Exception:
                    payload = {}

        # Save settings
        if path == "/api/settings":
            try:
                saved = save_settings(payload)
                return self._json(200, {"ok": True, "settings": saved})
            except Exception as e:
                return self._json(500, {"ok": False, "error": str(e)})

        # Rebuild pages
        if path == "/api/rebuild":
            try:
                rebuild_static()
                return self._json(200, {"ok": True})
            except Exception as e:
                return self._json(500, {"ok": False, "error": str(e)})

        # Upload image (supports 1..N files)
        if path == "/api/upload":
            try:
                ctype = self.headers.get("Content-Type", "") or ""
                if "multipart/form-data" not in ctype.lower():
                    return self._json(400, {"ok": False, "error": "multipart/form-data required", "details": ctype})

                clen = self.headers.get("Content-Length", "0")
                try:
                    clen_int = int(clen)
                except Exception:
                    clen_int = 0

                if clen_int <= 0:
                    return self._json(400, {"ok": False, "error": "empty body"})

                if clen_int > MAX_UPLOAD_BYTES:
                    return self._json(413, {"ok": False, "error": f"file too large (limit {MAX_UPLOAD_BYTES} bytes)"})

                raw_body = self.rfile.read(clen_int)

                # Parse multipart safely (even if boundary missing in header)
                fields, files = _parse_multipart(raw_body, ctype)

                if not files:
                    return self._json(400, {
                        "ok": False,
                        "error": "file required",
                        "details": "No file parts found. Проверь имя поля в FormData: file/files/images"
                    })

                purpose = (fields.get("purpose") or "").strip().lower()
                base_name = (fields.get("slug") or fields.get("title") or "image").strip()
                subdir = (fields.get("category") or "").strip()

                allowed = None
                if purpose == "logo":
                    base_name = "logo"
                    subdir = "branding"
                    allowed = {".png"}
                elif purpose in ("hero","background","bg"):
                    base_name = "hero-bg"
                    subdir = "branding"
                    allowed = {".jpg",".jpeg",".png",".webp",".avif",".jfif"}

                # Save all received files, but cap to 10
                saved_paths = []
                for i, f in enumerate(files[:10]):
                    fname = f.get("filename") or "image"
                    data = f.get("data") or b""
                    if not data:
                        continue
                    bn = base_name
                    if len(files) > 1:
                        bn = f"{base_name}-{i+1}"
                    saved_paths.append(save_uploaded_image_bytes(fname, data, base_name=bn, subdir=subdir, allowed_ext=allowed))

                if not saved_paths:
                    return self._json(400, {"ok": False, "error": "empty files", "details": "Файлы пришли, но без содержимого"})

                # Backward compatible: return "path" for first image
                return self._json(200, {"ok": True, "path": saved_paths[0], "paths": saved_paths})

            except Exception as e:
                return self._json(500, {"ok": False, "error": str(e), "trace": traceback.format_exc()})

        # Import from CSV -> products.json
        if path == "/api/import_csv":
            try:
                if not PRODUCTS_CSV.exists():
                    return self._json(404, {"ok": False, "error": "products.csv not found"})
                prods = []
                with open(PRODUCTS_CSV, "r", encoding="utf-8", newline="") as f:
                    r = csv.DictReader(f)
                    for row in r:
                        p = {
                            "id": (row.get("id") or "").strip() or "",
                            "category": (row.get("category") or "kmu").strip() or "kmu",
                            "brand": (row.get("brand") or "").strip(),
                            "model": (row.get("model") or "").strip(),
                            "year": (row.get("year") or "").strip(),
                            "status": (row.get("status") or "В наличии").strip() or "В наличии",
                            "price": (row.get("price") or "Цена по запросу").strip() or "Цена по запросу",
                            "city": (row.get("city") or "").strip(),
                            "image": (row.get("image") or "").strip() or _IMG_PLACEHOLDER,
                            "short": (row.get("short") or "").strip(),
                            "description": (row.get("description") or "").strip(),
                            "specs": (row.get("specs") or "").strip(),
                            "cta": (row.get("cta") or "").strip() or "Узнать цену",

                            # separate specs fields (optional columns)
                            "cargo": (row.get("cargo") or "").strip(),
                            "outreach": (row.get("outreach") or "").strip(),
                            "sections": (row.get("sections") or "").strip(),
                            "control": (row.get("control") or "").strip(),
                        }

                        images_raw = row.get("images")
                        if images_raw:
                            p["images"] = images_raw
                        for i in range(2, 11):
                            for key in (f"image{i}", f"img{i}", f"photo{i}"):
                                if row.get(key):
                                    p[key] = row.get(key)

                        p["title"] = (row.get("title") or "").strip() or (" ".join([x for x in [p["brand"], p["model"]] if x]).strip() if (p["brand"] or p["model"]) else p["id"])
                        p["slug"] = (row.get("slug") or "").strip() or slugify(p["title"])

                        normalize_product(p)
                        prods.append(p)

                write_json_atomic(PRODUCTS_JSON, prods)
                rebuild_static()
                return self._json(200, {"ok": True, "count": len(prods)})
            except Exception as e:
                return self._json(500, {"ok": False, "error": str(e)})

        # CRUD products (admin)
        if path == "/api/products":
            action = payload.get("action")
            prods = normalize_products_list(read_json(PRODUCTS_JSON, []))

            if action == "create":
                p = payload.get("product") or {}
                new_id = "p" + str(int(time.time()*1000))
                p["id"] = new_id
                normalize_product(p)
                p["featured"] = bool(p.get("featured", False))
                p["featured_rank"] = (p.get("featured_rank") or "").strip()

                prods.append(p)
                write_json_atomic(PRODUCTS_JSON, prods)
                rebuild_static()
                return self._json(200, {"ok": True, "id": new_id})

            if action == "update":
                p = payload.get("product") or {}
                pid = p.get("id")
                if not pid:
                    return self._json(400, {"ok": False, "error": "id required"})
                found = False
                for i, cur in enumerate(prods):
                    if cur.get("id")==pid:
                        p["id"] = pid
                        if "featured" not in p:
                            p["featured"] = cur.get("featured", False)
                        if "featured_rank" not in p:
                            p["featured_rank"] = cur.get("featured_rank", "")
                        if "cta" not in p:
                            p["cta"] = cur.get("cta", "Узнать цену")

                        for k in ("cargo","outreach","sections","control"):
                            if k not in p and k in cur:
                                p[k] = cur.get(k)

                        normalize_product(p)
                        p["featured"] = bool(p.get("featured", False))
                        p["featured_rank"] = (p.get("featured_rank") or "").strip()
                        prods[i] = p
                        found = True
                        break
                if not found:
                    return self._json(404, {"ok": False, "error": "not found"})
                write_json_atomic(PRODUCTS_JSON, prods)
                rebuild_static()
                return self._json(200, {"ok": True})

            if action == "delete":
                pid = payload.get("id")
                if not pid:
                    return self._json(400, {"ok": False, "error": "id required"})
                prods2 = [x for x in prods if x.get("id")!=pid]
                write_json_atomic(PRODUCTS_JSON, prods2)
                rebuild_static()
                return self._json(200, {"ok": True})

            return self._json(400, {"ok": False, "error": "unknown action"})

        return self._json(404, {"ok": False, "msg": "Not found"})


def run():
    os.chdir(BASE_DIR)
    ensure_products_seed()
    ensure_leads_csv()

    try:
        rebuild_static()
    except Exception:
        pass

    port = int(os.getenv("PORT", "8000"))
    httpd = ThreadingHTTPServer(("0.0.0.0", port), Handler)
    print(f"Serving on http://localhost:{port}")
    print("POST /api/lead -> leads/leads.csv (+ Telegram/Email optional)")
    print(f"Admin: http://localhost:{port}/admin/  (Basic auth: {ADMIN_USER}:{ADMIN_PASS})")
    httpd.serve_forever()

if __name__ == "__main__":
    run()
