/* Catalog filters (client-side)
   - Loads /api/public/products
   - Filters by category (window.__CATALOG_CATEGORY), search, brand, year
   - Updates URL query params
*/
(function () {
  const grid = document.getElementById("catalogGrid");
  const ui = {
    q: document.getElementById("f_q"),
    brand: document.getElementById("f_brand"),
    year: document.getElementById("f_year"),
    cargo: document.getElementById("f_cargo"),
    outreach: document.getElementById("f_outreach"),
    sections: document.getElementById("f_sections"),
    sort: document.getElementById("f_sort"),
    count: document.getElementById("f_count"),
    clear: document.getElementById("f_clear"),
  };
  if (!grid || !ui.q || !ui.brand || !ui.year || !ui.cargo || !ui.outreach || !ui.sections || !ui.sort) return;

  const category = (window.__CATALOG_CATEGORY || "").trim();
  let all = [];
  let filtered = [];

  const esc = (s) => (s || "").replace(/[&<>'"]/g, (c) => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", "'": "&#39;", '"': "&quot;"
  }[c]));

  function getProductImages(p){
    const arr = Array.isArray(p && p.images) ? p.images.filter(Boolean) : [];
    const one = (p && p.image) ? [p.image] : [];
    const res = (arr.length ? arr : one).filter(Boolean);
    return (res.length ? res : ['/assets/img/placeholder.svg']).slice(0, 10);
  }

  function renderCarousel(images, title){
    const imgs = (images || []).slice(0, 10);
    const slides = imgs.map((src, i) => {
      const s = esc(src || '');
      const a = `${title} — фото ${i+1}`;
      return `<img src="${s}" alt="${a}" loading="lazy" width="640" height="420">`;
    }).join('');

    if (imgs.length <= 1){
      return `<div class="carousel" data-carousel><div class="carousel-track">${slides}</div></div>`;
    }

    const dots = imgs.map((_, i) => `<button type="button" class="carousel-dot" data-dot="${i}" aria-label="Фото ${i+1}"></button>`).join('');

    return `
      <div class="carousel" data-carousel>
        <div class="carousel-track">${slides}</div>
        <button type="button" class="carousel-btn" data-prev aria-label="Предыдущее фото">‹</button>
        <button type="button" class="carousel-btn" data-next aria-label="Следующее фото">›</button>
        <div class="carousel-dots">${dots}</div>
      </div>
    `;
  }

function cardHtml(p) {
  const href = `/catalog/${encodeURIComponent(p.category || "kmu")}/${encodeURIComponent(p.slug || "")}/`;
  const images = getProductImages(p);

  const brand = esc(p.brand || "");
  const model = esc(p.model || "");
  const year  = esc((p.year || "").toString());
  const status = esc(p.status || "");

  const titleRaw = p.title || p.name || "";
  const title = esc(titleRaw || (brand && model ? `${brand} ${model}` : (brand || model || "")));
  const short = esc(p.short || "");

  const tags = [];
  if (brand) tags.push(`<span class="tag">${brand}</span>`);
  if (model) tags.push(`<span class="tag">Модель: ${model}</span>`);
  if (year) tags.push(`<span class="tag">Год: ${year}</span>`);
  if (status) tags.push(`<span class="tag">${status}</span>`);

  const specBadges = (p.specs_table || []).slice(0, 2).map(r => {
    const k = esc((r || {}).k || "");
    const v = esc((r || {}).v || "");
    return (k && v) ? `<span class="tag">${k}: ${v}</span>` : "";
  }).filter(Boolean);

  const badges = [...tags, ...specBadges].join("");

  return `
    <article class="product"
      data-name="${esc((titleRaw || title || "").toLowerCase())}"
      data-brand="${esc((p.brand||"").toLowerCase())}"
      data-year="${esc((p.year||"").toString())}">
      <div class="pimg">
        ${renderCarousel(images, title)}
        <a class="pimg-link" href="${href}" aria-label="Открыть карточку"></a>
      </div>
      <div class="pbody">
        <h3 class="ptitle"><a href="${href}">${title}</a></h3>
        ${short ? `<p class="muted">${short}</p>` : ``}
        <div class="meta">${badges}</div>
        <div class="actions">
          <a class="btn btn-ghost" href="${href}">Подробнее</a>
          <a class="btn btn-primary" href="${href}#request">Узнать цену</a>
        </div>
      </div>
    </article>`;
}

  function getQuery() {
    const sp = new URLSearchParams(location.search);
    return {
  q: (sp.get("q") || "").trim(),
  brand: (sp.get("brand") || "").trim(),
  year: (sp.get("year") || "").trim(),
  cargo: (sp.get("cargo") || "").trim(),
  outreach: (sp.get("outreach") || "").trim(),
  sections: (sp.get("sections") || "").trim(),
  sort: (sp.get("sort") || "relevance").trim(),
};
  }

  function setQuery(next) {
    const sp = new URLSearchParams(location.search);
    ["q","brand","year","cargo","outreach","sections","sort"].forEach(k => {
      const v = (next[k] || "").toString().trim();
      if (v && !(k === "sort" && v === "relevance")) sp.set(k, v);
      else sp.delete(k);
    });
    const url = location.pathname + (sp.toString() ? ("?" + sp.toString()) : "");
    history.replaceState(null, "", url);
  }

  function fillSelect(select, values, placeholder) {
    const current = select.value;
    select.innerHTML = "";
    const opt0 = document.createElement("option");
    opt0.value = "";
    opt0.textContent = placeholder;
    select.appendChild(opt0);
    values.forEach(v => {
      const o = document.createElement("option");
      o.value = v;
      o.textContent = v;
      select.appendChild(o);
    });
    // keep if still present
    if (current && values.includes(current)) select.value = current;
  }

  function firstNumber(val) {
  // take first number from string: "7.0 т", "12,5 м"
  const s = String(val || "").replace(",", ".");
  const m = s.match(/-?\d+(\.\d+)?/);
  return m ? parseFloat(m[0]) : null;
}

function pickSpec(p, keywords) {
  const rows = (p && p.specs_table) ? p.specs_table : [];
  for (const r of rows) {
    const k = String((r && r.k) || "").toLowerCase();
    if (!k) continue;
    for (const kw of keywords) {
      if (k.includes(kw)) return r.v;
    }
  }
  return null;
}

function computeFacets(p) {
  // Flexible mapping: "груз" can match "груз", "грузопод", "грузовой момент"
  const cargoRaw = pickSpec(p, ["грузопод", "груз", "грузовой"]);
  const outreachRaw = pickSpec(p, ["вылет", "радиус"]);
  const sectionsRaw = pickSpec(p, ["секц"]);
  const cargoNum = firstNumber(cargoRaw);
  const outreachNum = firstNumber(outreachRaw);
  const sectionsNum = firstNumber(sectionsRaw);

  p._cargo = cargoNum;
  p._outreach = outreachNum;
  p._sections = sectionsNum != null ? Math.round(sectionsNum) : null;

  return p;
}

function sortProducts(list, sort, q) {
    const s = sort || "relevance";
    if (s === "name_asc") return list.slice().sort((a,b)=> (a.name||"").localeCompare(b.name||"", "ru"));
    if (s === "year_desc") return list.slice().sort((a,b)=> (parseInt(b.year||0,10)||0) - (parseInt(a.year||0,10)||0));
    if (s === "updated_desc") return list.slice().sort((a,b)=> (b.updated_at||"").localeCompare(a.updated_at||""));
    // relevance: naive scoring by name contains query
    const qq = (q||"").toLowerCase();
    if (!qq) return list;
    const score = (p) => {
      const n = (p.name||"").toLowerCase();
      const sh = (p.short||"").toLowerCase();
      let s = 0;
      if (n.includes(qq)) s += 3;
      if (sh.includes(qq)) s += 1;
      return s;
    };
    return list.slice().sort((a,b)=> score(b)-score(a));
  }

  function apply() {
  const q = ui.q.value.trim().toLowerCase();
  const brand = ui.brand.value.trim().toLowerCase();
  const year = ui.year.value.trim();
  const cargo = ui.cargo.value.trim();
  const outreach = ui.outreach.value.trim();
  const sections = ui.sections.value.trim();

  let list = all;
  if (category) list = list.filter(p => (p.category || "kmu") === category);

  if (q) {
    list = list.filter(p => {
      const n = (p.name || "").toLowerCase();
      const sh = (p.short || "").toLowerCase();
      return n.includes(q) || sh.includes(q);
    });
  }
  if (brand) list = list.filter(p => ((p.brand || "").toLowerCase() === brand));
  if (year) list = list.filter(p => ((p.year || "").toString() === year));

  if (cargo) list = list.filter(p => (p._cargo != null && String(p._cargo) === cargo));
  if (outreach) list = list.filter(p => (p._outreach != null && String(p._outreach) === outreach));
  if (sections) list = list.filter(p => (p._sections != null && String(p._sections) === sections));

  list = sortProducts(list, ui.sort.value, q);

  filtered = list;
  grid.innerHTML = filtered.length ? filtered.map(cardHtml).join("\n") : `<p class="muted">Ничего не найдено. Попробуй снять фильтры.</p>`;
  if (window.MIRCRANE && window.MIRCRANE.initCarousels) window.MIRCRANE.initCarousels(grid);
  if (ui.count) ui.count.textContent = `Найдено: ${filtered.length}`;
  setQuery({
    q: ui.q.value.trim(),
    brand: ui.brand.value.trim(),
    year: ui.year.value.trim(),
    cargo: ui.cargo.value.trim(),
    outreach: ui.outreach.value.trim(),
    sections: ui.sections.value.trim(),
    sort: ui.sort.value
  });
}

  function debounce(fn, ms) {
    let t = null;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), ms);
    };
  }

  async function init() {
    // Load products
    try {
      const res = await fetch("/api/public/products", { cache: "no-store" });
      all = (await res.json()).map(computeFacets);
    } catch (e) {
      // fallback: keep existing HTML cards and enable simple DOM-filter by title
      console.warn("catalog filters: cannot load products", e);
      all = [];
    }

    const q0 = getQuery();
    ui.q.value = q0.q || "";
    ui.sort.value = q0.sort || "relevance";

    // Build facets from data (category-scoped)
    let scope = all;
    if (category) scope = scope.filter(p => (p.category || "kmu") === category);
    const brands = Array.from(new Set(scope.map(p => (p.brand || "").trim()).filter(Boolean))).sort((a,b)=>a.localeCompare(b,"ru"));
const years = Array.from(new Set(scope.map(p => (p.year || "").toString().trim()).filter(Boolean))).sort((a,b)=> (parseInt(b,10)||0) - (parseInt(a,10)||0));

const cargos = Array.from(new Set(scope.map(p => (p._cargo != null ? String(p._cargo) : "")).filter(Boolean)))
  .map(x => parseFloat(x)).filter(x => !Number.isNaN(x))
  .sort((a,b)=>a-b).map(x => String(x));

const outreaches = Array.from(new Set(scope.map(p => (p._outreach != null ? String(p._outreach) : "")).filter(Boolean)))
  .map(x => parseFloat(x)).filter(x => !Number.isNaN(x))
  .sort((a,b)=>a-b).map(x => String(x));

const sections = Array.from(new Set(scope.map(p => (p._sections != null ? String(p._sections) : "")).filter(Boolean)))
  .map(x => parseInt(x,10)).filter(x => !Number.isNaN(x))
  .sort((a,b)=>a-b).map(x => String(x));
fillSelect(ui.brand, brands, "Бренд: все");
    fillSelect(ui.year, years, "Год: любой");
    fillSelect(ui.cargo, cargos, "Груз: любой");
    fillSelect(ui.outreach, outreaches, "Вылет: любой");
    fillSelect(ui.sections, sections, "Секций: любые");

    if (q0.brand && brands.includes(q0.brand)) ui.brand.value = q0.brand;
    if (q0.year && years.includes(q0.year)) ui.year.value = q0.year;
    if (q0.cargo && cargos.includes(q0.cargo)) ui.cargo.value = q0.cargo;
    if (q0.outreach && outreaches.includes(q0.outreach)) ui.outreach.value = q0.outreach;
    if (q0.sections && sections.includes(q0.sections)) ui.sections.value = q0.sections;

    // Listeners
    ui.q.addEventListener("input", debounce(apply, 120));
    ui.brand.addEventListener("change", apply);
    ui.year.addEventListener("change", apply);
    ui.cargo.addEventListener("change", apply);
    ui.outreach.addEventListener("change", apply);
    ui.sections.addEventListener("change", apply);
    ui.sort.addEventListener("change", apply);

    if (ui.clear) ui.clear.addEventListener("click", () => {
      ui.q.value = "";
      ui.brand.value = "";
      ui.year.value = "";
      ui.cargo.value = "";
      ui.outreach.value = "";
      ui.sections.value = "";
      ui.sort.value = "relevance";
      apply();
    });

    // Render
    if (all.length) apply();
  }

  init();
})();
