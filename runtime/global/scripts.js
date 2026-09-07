document.addEventListener('DOMContentLoaded', function () {
  if (!window.Lenis || window.iouliaLenis) {
    return;
  }

  window.iouliaLenis = new Lenis({
    autoRaf: true,
    anchors: true,
    autoToggle: true,
    smoothWheel: true,
    syncTouch: false,
    wheelMultiplier: 1,
    touchMultiplier: 1.5,
    infinite: false,
    respectReducedMotion: true,
    stopInertiaOnNavigate: true,

    prevent: function (node) {
      return Boolean(
        node.closest(
          '[data-lenis-prevent], ' +
          '#ioulia-menu-overlay, ' +
          '.ioulia-mini-cart-panel, ' +
          '.iwb__dialog, ' +
          '.dialog-widget-content, ' +
          '.elementor-popup-modal, ' +
          '.woocommerce-mini-cart, ' +
          '.wc-block-components-drawer, ' +
          '.select2-container'
        )
      );
    }
  });

  /* Cart and checkout own their vertical scroll in compact app mode. Keep
     wheel/touch input inside that surface even when another global listener
     is present; the data attributes above also tell Lenis not to consume it. */
  document.querySelectorAll('.igc-shop__body').forEach(function (surface) {
    ['wheel', 'touchmove'].forEach(function (type) {
      surface.addEventListener(type, function (event) {
        event.stopPropagation();
      }, { passive: true });
    });
  });
});

/* ==========================================================================
   GLOBAL CURSOR RING

   The native cursor stays visible. This is a separate, purely decorative
   outline with a small spring-like delay and quiet hover and press states.
   Touch, coarse pointers and reduced-motion visitors never receive it.
   ======================================================================= */

document.addEventListener('DOMContentLoaded', function () {
  if (document.querySelector('[data-ioulia-cursor-ring]')) return;

  const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  if (!finePointer.matches || reduceMotion.matches) return;

  const ring = document.createElement('div');
  const circle = document.createElement('span');
  ring.className = 'ioulia-cursor-ring';
  ring.setAttribute('data-ioulia-cursor-ring', '');
  ring.setAttribute('aria-hidden', 'true');
  circle.className = 'ioulia-cursor-ring__circle';
  ring.appendChild(circle);
  document.body.appendChild(ring);

  const interactiveSelector = [
    'a[href]',
    'button:not(:disabled)',
    'input:not(:disabled)',
    'select:not(:disabled)',
    'textarea:not(:disabled)',
    'summary',
    'label',
    '[role="button"]',
    '[contenteditable="true"]'
  ].join(', ');
  const localCursorSelector = '.icph, .mxl-iph';

  let targetX = 0;
  let targetY = 0;
  let currentX = 0;
  let currentY = 0;
  let previousTime = 0;
  let frame = 0;
  let started = false;

  const syncTargetState = function (target) {
    if (!(target instanceof Element)) {
      ring.classList.remove('is-interactive', 'is-local-cursor');
      return;
    }
    ring.classList.toggle('is-interactive', Boolean(target.closest(interactiveSelector)));
    ring.classList.toggle('is-local-cursor', Boolean(target.closest(localCursorSelector)));
  };

  const paint = function (time) {
    frame = 0;
    const elapsed = previousTime ? Math.min(40, time - previousTime) : 16;
    previousTime = time;
    const follow = 1 - Math.exp(-elapsed / 82);
    currentX += (targetX - currentX) * follow;
    currentY += (targetY - currentY) * follow;
    ring.style.transform = 'translate3d(' + (currentX - 18).toFixed(2) + 'px, ' + (currentY - 18).toFixed(2) + 'px, 0)';

    if (Math.abs(targetX - currentX) > .04 || Math.abs(targetY - currentY) > .04) {
      frame = window.requestAnimationFrame(paint);
    }
  };

  const requestPaint = function () {
    if (!frame) {
      previousTime = 0;
      frame = window.requestAnimationFrame(paint);
    }
  };

  window.addEventListener('pointermove', function (event) {
    if (event.pointerType && event.pointerType !== 'mouse') return;
    targetX = event.clientX;
    targetY = event.clientY;
    if (!started) {
      currentX = targetX;
      currentY = targetY;
      started = true;
    }
    syncTargetState(event.target);
    ring.classList.add('is-visible');
    requestPaint();
  }, { passive: true });

  document.addEventListener('pointerover', function (event) {
    syncTargetState(event.target);
  }, { passive: true });

  document.addEventListener('pointerdown', function (event) {
    if (event.pointerType && event.pointerType !== 'mouse') return;
    ring.classList.add('is-pressed');
  }, { passive: true });

  document.addEventListener('pointerup', function () {
    ring.classList.remove('is-pressed');
  }, { passive: true });

  document.documentElement.addEventListener('mouseleave', function () {
    ring.classList.remove('is-visible', 'is-pressed');
  });

  window.addEventListener('blur', function () {
    ring.classList.remove('is-visible', 'is-pressed');
  });
});


/* ===========================================================================
   ENTRANCE AND MOTION

   One system for every page that opts in, rather than a copy per canvas. A
   canvas takes part by marking elements: [data-ia-reveal] to arrive,
   [data-ia-drift] to move a little against the scroll, [data-ia-gallery] for
   a strip that is dragged sideways.

   The hidden starting states are written under html.ia-js. Each canvas adds
   that class inline as it parses, so the page is never painted visible and
   then hidden; and if a canvas never adds it, that page simply does not
   animate. Nothing here is required for a page to read.
   ======================================================================== */

/* Mark the editorial text before arming its hidden state. Doing both in one
   task avoids a visible first frame followed by the words disappearing. */
(function () {
  const armGlobalText = () => {
    const cataloguePage = document.body.matches('.woocommerce-shop, .single-product, .post-type-archive-product, .tax-product_cat, .tax-product_tag');
    if (!cataloguePage) {
      document.querySelectorAll('h1, h2, h3, p').forEach((node) => {
        if (node.closest('header, footer, nav, form, dialog, [role="dialog"], .iwf, .ioulia-mini-cart-panel, .igc-shop, .woocommerce')) return;
        if (node.matches('[data-igw-words], .iwl__experience h2')) return;

        const isHeading = node.matches('h1, h2, h3');
        const isEditorialParagraph = node.matches('[class*="lede"], [class*="intro"], [class*="copy"], [class*="statement"], [class*="description"]');
        if (!isHeading && !isEditorialParagraph) return;

        node.setAttribute('data-ia-words', '');
        node.setAttribute('data-ia-reveal', '');
      });
    }
    document.documentElement.classList.add('ia-js');
  };

  if (document.body) armGlobalText();
  else document.addEventListener('DOMContentLoaded', armGlobalText, { once: true });
}());

document.addEventListener('DOMContentLoaded', function () {
  if (document.documentElement.dataset.iaMotion === 'ready') return;
  document.documentElement.dataset.iaMotion = 'ready';

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

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
    if (!node.textContent.trim()) return;
    if (node.textContent.trim().split(whitespace).length > 60) return;  /* long enough to read as flicker */

    /* Walk the children rather than reading textContent. A heading that
       breaks its own line carries a <br>, which textContent drops - which is
       how "τα προγράμματα<br>μας" came out as one word. Line breaks are kept
       where the author put them and only the text between them is split. */
    let index = 0;
    const pieces = [];
    node.childNodes.forEach((child) => {
      if (child.nodeType === 1) { pieces.push(child.cloneNode(true)); return; }
      if (child.nodeType !== 3) return;
      const words = child.textContent.split(whitespace).filter(Boolean);
      words.forEach((word, position) => {
        const span = document.createElement('span');
        span.className = 'ia-word';
        span.style.setProperty('--i', String(index));
        span.textContent = word;
        pieces.push(span);
        if (position < words.length - 1) pieces.push(document.createTextNode(' '));
        index++;
      });
    });
    if (!index) return;
    node.replaceChildren(...pieces);

    /* The words start hidden, so whatever holds them has to be something the
       observer will come back and reveal. */
    node.setAttribute('data-ia-reveal', '');
  };

  document.querySelectorAll('[data-ia-words]').forEach(splitIntoWords);

  /* ---------------------------------------------------------------------
     Arrival.

     Anything marked [data-ia-reveal] waits until it is reached. The hidden
     starting state is written under html.ia-js, which each canvas sets inline
     as it parses - see the note at the top of this block. */

  const revealables = [...document.querySelectorAll('[data-ia-reveal]')];
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

  const viewport = document.querySelector('[data-ia-gallery]');
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
        /* Only the horizontal shift is written here, as a custom property.
           The vertical lift stays in the stylesheet, where a media query can
           switch it off - an inline transform carrying both would have
           overruled that and put the scatter back on a phone. */
        frame.node.style.setProperty('--shift', shift.toFixed(2) + 'px');
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

  const drifters = [...document.querySelectorAll('[data-ia-drift]')].map((node) => ({
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

  /* Not on a phone. A screen you scroll with a thumb, at speeds a wheel never
     reaches, turns a gentle parallax into pictures that will not sit still. */
  const roomToDrift = window.matchMedia('(min-width: 701px)');

  if (drifters.length && roomToDrift.matches) {
    window.addEventListener('scroll', requestDrift, { passive: true });
    window.addEventListener('resize', requestDrift, { passive: true });
    if (typeof reduceMotion.addEventListener === 'function') reduceMotion.addEventListener('change', requestDrift);
    drift();
  } else {
    drifters.forEach((item) => { item.node.style.transform = ''; });
  }
});
