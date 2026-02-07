#!/usr/bin/env python3
# -*- coding: utf-8 -*-
# Генератор JSON из data/products.csv.
# Запуск: python tools/build.py

import csv, json, re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
DATA_CSV = ROOT / "data" / "products.csv"
DATA_JSON = ROOT / "data" / "products.json"

def ru_slugify(s: str) -> str:
    s = s.lower().strip()
    tr = {"а":"a","б":"b","в":"v","г":"g","д":"d","е":"e","ё":"e","ж":"zh","з":"z","и":"i","й":"y",
          "к":"k","л":"l","м":"m","н":"n","о":"o","п":"p","р":"r","с":"s","т":"t","у":"u","ф":"f",
          "х":"h","ц":"ts","ч":"ch","ш":"sh","щ":"sch","ъ":"","ы":"y","ь":"","э":"e","ю":"yu","я":"ya"}
    out=[]
    for ch in s:
        if ch.isalnum(): out.append(tr.get(ch,ch))
        else: out.append("-")
    return re.sub(r"-{2,}","-","".join(out)).strip("-") or "item"

def main():
    items=[]
    with DATA_CSV.open("r", encoding="utf-8") as f:
        r=csv.DictReader(f)
        for row in r:
            row={k:(v or "").strip() for k,v in row.items()}
            row["title"]=(f"{row.get('brand','')} {row.get('model','')}".strip() or row.get("id","Техника"))
            row["slug"]=row.get("slug","").strip() or ru_slugify(f"{row.get('brand','')}-{row.get('model','')}-{row.get('id','')}")
            row["image"]=row.get("image") or "/assets/img/placeholder.svg"
            items.append(row)
    DATA_JSON.write_text(json.dumps(items, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"OK: {len(items)} items -> {DATA_JSON}")

if __name__ == "__main__":
    main()
