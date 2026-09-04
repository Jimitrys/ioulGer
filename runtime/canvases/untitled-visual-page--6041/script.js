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
     Text entrance.

     Every heading and paragraph is split into words so they can arrive one
     just behind the other. The split is done here rather than written into
     the markup so the copy stays readable and translatable as sentences.

     No backslash in the whitespace class: Site Studio unslashes canvas JS on
     import, and an escaped one would arrive splitting on the letter s. */

  const whitespace = new RegExp("[" + String.fromCharCode(32, 9, 10, 13) + "]+");

  const splitIntoWords = (node) => {
    if (node.querySelector('.ia-word')) return;
    const source = node.textContent.trim();
    if (!source) return;
    const words = source.split(whitespace);
    if (words.length > 60) return;  /* a paragraph this long reads as flicker */
    node.textContent = '';
    words.forEach((word, index) => {
      const span = document.createElement('span');
      span.className = 'ia-word';
      span.style.setProperty('--i', String(index));
      span.textContent = word;
      node.appendChild(span);
      if (index < words.length - 1) node.appendChild(document.createTextNode(' '));
    });
    /* The words start hidden, so whatever holds them has to be something the
       observer will come back and reveal. */
    node.setAttribute('data-ia-reveal', '');
  };

  page.querySelectorAll('.ia-gallery__title, .ia-room__title, .ia-room__copy, .ia-maker__title, .ia-maker__copy, .ia-life__title, .ia-life__copy, .ia-visit__title, .ia-visit__copy, .ioulia-designs .ioulia-about-copy, .ioulia-production .ioulia-about-copy, .ioulia-process .ioulia-about-copy, .ioulia-designs .ioulia-section-title, .ioulia-production .ioulia-section-title, .ioulia-process .ioulia-section-title')
      .forEach(splitIntoWords);

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
    let spoken = false;
    const seen = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        spoken = true;
        entry.target.classList.add('is-in');
        seen.unobserve(entry.target);
      });
    }, { rootMargin: '0px 0px -12% 0px', threshold: 0.08 });
    revealables.forEach((node) => seen.observe(node));

    /* The net is only for an observer that never speaks at all. An earlier
       version revealed the whole page after a fixed delay, which meant every
       section below the fold was already shown before it was reached - the
       entrance only ever played for whatever happened to be on screen at
       load. */
    window.setTimeout(() => {
      if (!spoken) showAll();
    }, 2600);
  }

  /* ---------------------------------------------------------------------
     The drag gallery.

     The strip is duplicated so that leaving one end arrives at the other, and
     position is kept as a single number the pointer, the wheel and the idle
     drift all write to. Each frame also carries its own rate, so the strip
     slides past itself instead of moving as one board.

     Everything here is an enhancement of a plain horizontal scroller: if this
     block never runs, the viewport still scrolls with a finger or a trackpad. */

  const viewport = page.querySelector('[data-ia-gallery]');
  const track = viewport && viewport.querySelector('.ia-gallery__track');

  if (track && !reduceMotion.matches) {
    const originals = [...track.children];
    originals.forEach((item) => {
      const copy = item.cloneNode(true);
      copy.setAttribute('aria-hidden', 'true');
      copy.querySelectorAll('img').forEach((image) => { image.alt = ''; });
      track.appendChild(copy);
    });

    /* The strip moves as one piece. Each frame then shifts a little on top of
       that, by how far it sits from the middle of the screen - so they slide
       past one another without ever drifting into each other. An earlier
       version multiplied each frame's own rate by the total distance
       travelled, which pulled them apart without limit until they collided. */
    /* Kept well under the gap between frames. Two neighbours shifting toward
       each other close twice this, and the narrowest gap the stylesheet
       allows is 32px, so the spread is scaled down on small screens too. */
    const SPREAD = [-11, 8, -14, 13, -7, 14, -12, 10];
    const spreadFor = (index) => SPREAD[index % 8];
    const frames = [...track.children].map((node, index) => ({
      node: node,
      spread: spreadFor(index),
      centre: 0
    }));

    let span = 0;
    const measure = () => {
      span = track.scrollWidth / 2;
      frames.forEach((frame) => {
        frame.centre = frame.node.offsetLeft + frame.node.offsetWidth / 2;
      });
    };

    let position = 0;
    let velocity = 0;
    let dragging = false;
    let lastPointer = 0;
    let ticking = 0;

    const paint = () => {
      ticking = 0;
      if (span > 0) position = ((position % span) + span) % span;
      track.style.transform = 'translate3d(' + (-position).toFixed(2) + 'px, 0, 0)';

      const width = viewport.clientWidth || 1;
      const scale = Math.min(1, width / 1400);
      frames.forEach((frame) => {
        const fromMiddle = (frame.centre - position - width / 2) / width;
        const shift = Math.max(-1, Math.min(1, fromMiddle)) * frame.spread * scale;
        frame.node.style.transform =
          'translate3d(' + shift.toFixed(2) + 'px, var(--lift, 0px), 0)';
      });
    };
    const requestPaint = () => { if (!ticking) ticking = window.requestAnimationFrame(paint); };

    const idle = () => {
      if (!dragging) {
        velocity *= 0.94;
        /* A slow drift keeps it alive and says it can be moved. */
        position += velocity + 0.18;
        requestPaint();
      }
      window.requestAnimationFrame(idle);
    };

    viewport.classList.add('is-live');
    measure();
    paint();
    window.requestAnimationFrame(idle);

    viewport.addEventListener('pointerdown', (event) => {
      dragging = true;
      velocity = 0;
      lastPointer = event.clientX;
      viewport.classList.add('is-dragging');
      if (viewport.setPointerCapture) viewport.setPointerCapture(event.pointerId);
    });
    viewport.addEventListener('pointermove', (event) => {
      if (!dragging) return;
      const delta = event.clientX - lastPointer;
      lastPointer = event.clientX;
      position -= delta;
      velocity = -delta * 0.6;
      requestPaint();
    });
    const release = () => {
      dragging = false;
      viewport.classList.remove('is-dragging');
    };
    viewport.addEventListener('pointerup', release);
    viewport.addEventListener('pointercancel', release);
    viewport.addEventListener('dragstart', (event) => event.preventDefault());
    window.addEventListener('resize', () => { measure(); requestPaint(); }, { passive: true });
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