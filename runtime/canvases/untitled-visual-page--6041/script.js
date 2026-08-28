(() => {
  const page = document.querySelector('.ioulia-about-page');
  if (!page || page.dataset.initialized === 'true') return;
  page.dataset.initialized = 'true';

  const hero = page.querySelector('.ig-about');
  const syncNavbarHeight = () => {
    const navbar = document.getElementById('ioulia-header');
    const bottom = navbar ? Math.max(0, Math.ceil(navbar.getBoundingClientRect().bottom)) : 82;
    page.style.setProperty('--about-nav', `${bottom}px`);
  };
  syncNavbarHeight();
  window.addEventListener('resize', syncNavbarHeight, { passive: true });
  const navbar = document.getElementById('ioulia-header');
  if (navbar && 'ResizeObserver' in window) new ResizeObserver(syncNavbarHeight).observe(navbar);

  const manifesto = page.querySelector('.ig-manifesto');
  if (!manifesto) return;
  manifesto.querySelectorAll('[data-reveal-text]').forEach((node) => {
    const words = node.textContent.trim().split(/s+/);
    node.textContent = '';
    words.forEach((word, index) => {
      const span = document.createElement('span');
      span.className = 'ig-manifesto__word';
      span.textContent = word;
      node.appendChild(span);
      if (index < words.length - 1) node.appendChild(document.createTextNode(' '));
    });
  });

  const words = [...manifesto.querySelectorAll('.ig-manifesto__word')];
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const clamp = (value, min, max) => Math.min(max, Math.max(min, value));
  let frame = 0;
  const render = () => {
    frame = 0;
    if (reduceMotion.matches) { words.forEach((word) => { word.style.opacity = '1'; }); return; }
    const rect = manifesto.getBoundingClientRect();
    const distance = Math.max(1, rect.height - window.innerHeight);
    const progress = clamp(-rect.top / distance, 0, 1);
    const wordWindow = 1 / Math.max(1, words.length);
    words.forEach((word, index) => {
      const start = index * wordWindow * .88;
      const local = clamp((progress - start) / (wordWindow * 2.25), 0, 1);
      word.style.opacity = String(.13 + (1 - Math.pow(1 - local, 3)) * .87);
    });
  };
  const requestRender = () => { if (!frame) frame = window.requestAnimationFrame(render); };
  window.addEventListener('scroll', requestRender, { passive: true });
  window.addEventListener('resize', requestRender, { passive: true });
  if (typeof reduceMotion.addEventListener === 'function') reduceMotion.addEventListener('change', requestRender);
  render();
})();