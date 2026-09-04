(() => {
  const page = document.querySelector('.ioulia-about-page');
  if (!page || page.dataset.initialized === 'true') return;
  page.dataset.initialized = 'true';

  /* The room kept for the fixed header comes from --ioulia-header-h, which the
     navbar publishes on the root element. Measuring it here as well followed
     the header as it shrank on scroll and moved the hero while reading. */

  const manifesto = page.querySelector('.ig-manifesto');
  if (!manifesto) return;
  manifesto.querySelectorAll('[data-reveal-text]').forEach((node) => {
    const words = node.textContent.trim().split(" ").filter(Boolean);
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

  /* ---------------------------------------------------------------------
     Reveal and drift, for every section below the manifesto.

     The hidden starting state lives under html.ia-js, so adding this class is
     what arms the animation. Nothing else on the page depends on it: if this
     script never runs, or throws before this line, the page is simply a page
     that does not animate. */

  const root = document.documentElement;
  root.classList.add('ia-js');

  const revealables = [...page.querySelectorAll('[data-ia-reveal]')];
  const showAll = () => revealables.forEach((node) => node.classList.add('is-in'));

  if (reduceMotion.matches || !('IntersectionObserver' in window)) {
    showAll();
  } else {
    const seen = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-in');
        seen.unobserve(entry.target);
      });
    }, { rootMargin: '0px 0px -12% 0px', threshold: 0.08 });
    revealables.forEach((node) => seen.observe(node));

    /* If the observer has not spoken by now something is wrong with it, and a
       page of invisible sections is worse than a page with no animation. */
    window.setTimeout(showAll, 2600);
  }

  /* Drift: each frame moves a few pixels against the scroll as it crosses the
     screen. The number on the element is how far, in pixels, and which way. */

  const drifters = [...page.querySelectorAll('[data-ia-drift]')].map((node) => ({
    node: node,
    range: Number(node.dataset.iaDrift) || 0
  }));

  let driftFrame = 0;
  const drift = () => {
    driftFrame = 0;
    if (reduceMotion.matches) {
      drifters.forEach((item) => { item.node.style.transform = ''; });
      return;
    }
    const viewport = window.innerHeight;
    drifters.forEach((item) => {
      const box = item.node.getBoundingClientRect();
      if (box.bottom < -200 || box.top > viewport + 200) return;
      /* -1 as the frame enters from below, +1 as it leaves at the top. */
      const centre = box.top + box.height / 2;
      const travel = clamp((viewport / 2 - centre) / (viewport / 2 + box.height / 2), -1, 1);
      item.node.style.transform = 'translate3d(0, ' + (travel * item.range).toFixed(2) + 'px, 0)';
    });
  };
  const requestDrift = () => { if (!driftFrame) driftFrame = window.requestAnimationFrame(drift); };

  if (drifters.length) {
    window.addEventListener('scroll', requestDrift, { passive: true });
    window.addEventListener('resize', requestDrift, { passive: true });
    if (typeof reduceMotion.addEventListener === 'function') reduceMotion.addEventListener('change', requestDrift);
    drift();
  }
})();