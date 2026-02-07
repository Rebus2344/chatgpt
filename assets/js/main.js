(()=> {
  const $  = (sel, el=document) => el.querySelector(sel);
  const $$ = (sel, el=document) => Array.from(el.querySelectorAll(sel));

  // Mobile nav
  const toggle = $("#mobileToggle");
  const mobileNav = $("#mobileNav");
  if (toggle && mobileNav) {
    toggle.addEventListener("click", () => {
      const isOpen = mobileNav.getAttribute("data-open") === "1";
      mobileNav.setAttribute("data-open", isOpen ? "0" : "1");
      mobileNav.style.display = isOpen ? "none" : "block";
    });
  }

  // UTM params
  function getUtm() {
    const sp = new URLSearchParams(location.search);
    const out = {};
    for (const [k, v] of sp.entries()) {
      if (!v) continue;
      if (k.startsWith("utm_") || k === "gclid" || k === "yclid") out[k] = v;
    }
    return out;
  }

  function ensureStatusEl(form) {
    let el = form.querySelector(".form-status");
    if (!el) {
      el = document.createElement("div");
      el.className = "form-status muted small";
      el.style.marginTop = "10px";
      form.appendChild(el);
    }
    return el;
  }

  async function postJSON(url, data) {
    const res = await fetch(url, {
      method: "POST",
      headers: { "content-type": "application/json" },
      body: JSON.stringify(data),
    });
    const ct = res.headers.get("content-type") || "";
    const payload = ct.includes("application/json") ? await res.json().catch(()=>null) : await res.text().catch(()=>null);
    if (!res.ok) {
      const msg = (payload && payload.msg) ? payload.msg : (payload && payload.error) ? payload.error : (res.status + " " + res.statusText);
      throw new Error(msg);
    }
    return payload;
  }

  async function submitLead(form) {
    const fd = new FormData(form);

    // Collect fields (skip empty)
    const fields = {};
    for (const [k, v] of fd.entries()) {
      const val = (v ?? "").toString().trim();
      if (!val) continue;
      // ignore typical honeypots if someone adds them
      if (k === "company" || k === "website") continue;
      fields[k] = val;
    }

    const payload = {
      lead_type: form.getAttribute("data-lead-type") || "lead_form",
      page: location.pathname + location.search,
      referer: document.referrer || "",
      utm: getUtm(),
      fields
    };

    return postJSON("/api/lead", payload);
  }

  $$(".lead-form[data-lead]").forEach(form => {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const btn = form.querySelector('button[type="submit"]');
      const statusEl = ensureStatusEl(form);

      try {
        if (btn) btn.disabled = true;
        statusEl.textContent = "Отправляем…";
        const res = await submitLead(form);
        statusEl.textContent = (res && res.msg) ? res.msg : "Заявка отправлена ✅";
        form.reset();
      } catch (err) {
        statusEl.textContent = "Ошибка: " + (err && err.message ? err.message : "не удалось отправить");
      } finally {
        if (btn) btn.disabled = false;
      }
    });
  });
  // Home: featured / popular KMU
  function esc(s){
    return (s ?? "").toString()
      .replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")
      .replace(/"/g,"&quot;").replace(/'/g,"&#039;");
  }

  function getProductImages(p){
    const arr = Array.isArray(p && p.images) ? p.images.filter(Boolean) : [];
    const one = (p && p.image) ? [p.image] : [];
    const out = (arr.length ? arr : one).filter(Boolean);
    return (out.length ? out : ["/assets/img/placeholder.svg"]).slice(0, 10);
  }

  function renderCarousel(images, title){
    const imgs = (images || []).slice(0, 10);
    const slides = imgs.map((src, i) => {
      const s = esc(src || "");
      const a = `${title} — фото ${i+1}`;
      return `<img src="${s}" alt="${a}" loading="lazy" width="640" height="420">`;
    }).join("");

    if(imgs.length <= 1){
      return `<div class="carousel" data-carousel><div class="carousel-track">${slides}</div></div>`;
    }

    const dots = imgs.map((_, i) => `<button type="button" class="carousel-dot" data-dot="${i}" aria-label="Фото ${i+1}"></button>`).join("");

    return `
      <div class="carousel" data-carousel>
        <div class="carousel-track">${slides}</div>
        <button type="button" class="carousel-btn" data-prev aria-label="Предыдущее фото">‹</button>
        <button type="button" class="carousel-btn" data-next aria-label="Следующее фото">›</button>
        <div class="carousel-dots">${dots}</div>
      </div>
    `;
  }

  function homeCard(p){
    const href = `/catalog/${encodeURIComponent(p.category || "kmu")}/${encodeURIComponent(p.slug || "")}/`;
    const images = getProductImages(p);
    const brand = esc(p.brand || "");
    const model = esc(p.model || "");
    const year  = esc((p.year || "").toString());
    const status = esc(p.status || "");
    const titleRaw = p.title || "";
    const title = esc(titleRaw || (brand && model ? `${brand} ${model}` : (brand || model || "")));

    const tags = [];
    if (brand) tags.push(`<span class="tag">${brand}</span>`);
    if (model) tags.push(`<span class="tag">Модель: ${model}</span>`);
    if (year) tags.push(`<span class="tag">Год: ${year}</span>`);
    if (status) tags.push(`<span class="tag">${status}</span>`);
    const badges = tags.join("");

    return `
<article class="product">
  <div class="pimg">
    ${renderCarousel(images, title)}
    <a class="pimg-link" href="${href}" aria-label="Открыть карточку"></a>
  </div>
  <div class="pbody">
    <h3 class="ptitle"><a href="${href}">${title}</a></h3>
    <div class="meta">${badges}</div>
    <div class="actions">
      <a class="btn primary sm" href="${href}#request" data-evt="lead_price">Узнать цену</a>
      <a class="btn sm" href="${href}">Подробнее</a>
    </div>
  </div>
</article>`.trim();
  }

  async function renderHomeFeatured(){
    const box = document.getElementById("homeFeatured");
    if(!box) return;

    try{
      const res = await fetch("/api/public/products");
      const products = await res.json().catch(()=>[]);
      const kmu = (products || []).filter(p => (p.category || "kmu") === "kmu");

      let list = kmu.filter(p => p.featured === true || p.popular === true);
      if(list.length){
        list.sort((a,b)=>{
          const ra = parseInt(a.featured_rank || "9999", 10);
          const rb = parseInt(b.featured_rank || "9999", 10);
          if(ra !== rb) return ra - rb;
          return (a.title||"").localeCompare(b.title||"");
        });
      } else {
        // fallback: newest by year, then title
        list = kmu.slice().sort((a,b)=>{
          const ya = parseInt(a.year || "0", 10);
          const yb = parseInt(b.year || "0", 10);
          if(ya !== yb) return yb - ya;
          return (a.title||"").localeCompare(b.title||"");
        });
      }

      list = list.slice(0, 6);
      if(!list.length){
        box.innerHTML = `<div class="muted small">Пока нет позиций. Добавь товары в админке.</div>`;
        return;
      }
      box.innerHTML = list.map(homeCard).join("\n");
      initCarousels(box);
    } catch(e){
      box.innerHTML = `<div class="muted small">Не удалось загрузить позиции. Обнови страницу.</div>`;
    }
  }

  renderHomeFeatured();

  



  // ============================
  // Logo (apply /logo.png on all pages) + Carousels init
  // ============================
  function applyLogos(){
    const LOGO_SRC = "/logo.png?v=2";          // v=2 чтобы сбросить кэш
    const FALLBACK = "/assets/img/favicon.svg";

    // 1) Если есть конкретный img#siteLogoImg — обновим его
    document.querySelectorAll("#siteLogoImg").forEach(img => {
      img.src = LOGO_SRC;
      img.style.display = "block";
      img.onerror = () => { img.onerror = null; img.src = FALLBACK; };
    });

    // 2) Поддержка любых img.logo-img (если где-то есть)
    document.querySelectorAll("img.logo-img").forEach(img => {
      img.src = LOGO_SRC;
      img.style.display = "block";
      img.onerror = () => { img.onerror = null; img.src = FALLBACK; };
    });

    // 3) Самое важное: поддержка “старых” страниц, где в .logo только SVG
    document.querySelectorAll("header .logo, footer .logo").forEach(logoBox => {
      let img = logoBox.querySelector("img");
      if (!img){
        img = document.createElement("img");
        img.className = "logo-img";
        img.alt = "Мир манипуляторов";
        // вставляем в начало блока логотипа
        logoBox.insertBefore(img, logoBox.firstChild);
      }

      img.src = LOGO_SRC;
      img.style.display = "block";
      img.onerror = () => { img.onerror = null; img.src = FALLBACK; };

      // прячем svg-иконку, если она есть
      const svg = logoBox.querySelector("svg");
      if (svg) svg.style.display = "none";
    });
  }



  const _carouselRefs = [];

  function initCarousel(carousel){
    if (!carousel || carousel.dataset.inited === '1') return;
    const track = carousel.querySelector('.carousel-track');
    if (!track) return;
    const slides = Array.from(track.querySelectorAll('img'));
    const count = slides.length;

    const prevBtn = carousel.querySelector('[data-prev]');
    const nextBtn = carousel.querySelector('[data-next]');
    let dotsWrap = carousel.querySelector('.carousel-dots');

    if (count <= 1){
      if (prevBtn) prevBtn.style.display = 'none';
      if (nextBtn) nextBtn.style.display = 'none';
      if (dotsWrap) dotsWrap.style.display = 'none';
      carousel.dataset.inited = '1';
      return;
    }

    if (!dotsWrap){
      dotsWrap = document.createElement('div');
      dotsWrap.className = 'carousel-dots';
      carousel.appendChild(dotsWrap);
    }

    if (!dotsWrap.querySelector('.carousel-dot')){
      dotsWrap.innerHTML = slides.map((_, i) => `<button type="button" class="carousel-dot" data-dot="${i}" aria-label="Фото ${i+1}"></button>`).join('');
    }

    const dots = Array.from(dotsWrap.querySelectorAll('.carousel-dot'));

    const width = () => Math.max(1, track.getBoundingClientRect().width || track.clientWidth || 1);
    const clamp = (n) => Math.max(0, Math.min(count - 1, n));
    let index = 0;

    function setActive(i){
      index = clamp(i);
      dots.forEach((d, di) => d.setAttribute('aria-current', di === index ? 'true' : 'false'));
      if (prevBtn) prevBtn.style.display = (index <= 0) ? 'none' : 'flex';
      if (nextBtn) nextBtn.style.display = (index >= count - 1) ? 'none' : 'flex';
    }

    function scrollToIndex(i, smooth=true){
      const target = clamp(i);
      const left = target * width();
      track.scrollTo({ left, behavior: smooth ? 'smooth' : 'auto' });
      setActive(target);
    }

    // Sync on scroll (swipe)
    let raf = 0;
    track.addEventListener('scroll', () => {
      if (raf) cancelAnimationFrame(raf);
      raf = requestAnimationFrame(() => {
        const i = Math.round(track.scrollLeft / width());
        setActive(i);
      });
    }, { passive: true });

    // Buttons
    if (prevBtn) prevBtn.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); scrollToIndex(index - 1); });
    if (nextBtn) nextBtn.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); scrollToIndex(index + 1); });

    // Dots
    dots.forEach((d) => d.addEventListener('click', (e) => {
      e.preventDefault(); e.stopPropagation();
      const i = parseInt(d.getAttribute('data-dot') || '0', 10);
      scrollToIndex(i);
    }));

    // Initial state
    setActive(0);
    carousel.dataset.inited = '1';
    _carouselRefs.push({ carousel, track });
  }

  function initCarousels(root=document){
    Array.from((root || document).querySelectorAll('[data-carousel]')).forEach(initCarousel);
  }

  // Expose tiny public API for other scripts (catalog, product pages)
  try{
    window.MIRCRANE = window.MIRCRANE || {};
    window.MIRCRANE.initCarousels = initCarousels;
    window.MIRCRANE.applyLogos = applyLogos;
  }catch(e){}

  // Keep carousels aligned after resize
  window.addEventListener('resize', () => {
    _carouselRefs.forEach(({ track }) => {
      const w = Math.max(1, track.getBoundingClientRect().width || track.clientWidth || 1);
      const idx = Math.round(track.scrollLeft / w);
      track.scrollTo({ left: idx * w, behavior: 'auto' });
    });
  }, { passive: true });

  // ============================
  // Theme switch (Blue / White) + public settings (logo, hero background)
  // ============================
  function getThemeSwitches(){
    return Array.from(document.querySelectorAll("#themeSwitch, #themeSwitchHero"));
  }

  function applyTheme(theme){
    const t = (theme === "white") ? "white" : "blue";
    document.body.classList.remove("theme-white","theme-blue");
    document.body.classList.add(t === "white" ? "theme-white" : "theme-blue");
    try { localStorage.setItem("theme", t); } catch(e){}
    const switches = getThemeSwitches();
    switches.forEach(sw => { try{ sw.checked = (t === "white"); }catch(e){} });
}

  function getSavedTheme(){
    try { return localStorage.getItem("theme") || ""; } catch(e){ return ""; }
  }

  async function applyPublicSettings(){
    try{
      const res = await fetch("/api/public/settings", {cache:"no-store"});
      if(!res.ok) return;
      const s = await res.json();

      // Theme default (only if user hasn't chosen manually)
      const saved = getSavedTheme();
      if (!saved) applyTheme((s && s.theme_default) ? s.theme_default : "blue");
    }catch(e){
      // ignore
    }
  }

  (function initTheme(){
    const switches = getThemeSwitches();
    const saved = getSavedTheme();
    applyTheme(saved || "blue");
    switches.forEach(sw => {
      sw.addEventListener("change", () => {
        applyTheme(sw.checked ? "white" : "blue");
      });
    });
  })();
applyPublicSettings();

  applyLogos();
  initCarousels(document);

})();