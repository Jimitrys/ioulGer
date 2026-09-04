// Initializer for Leaflet + CartoDB Positron Silver (No Labels) Map with "The Lab" & "Ano Patisia ISAP" Markers
  window.initIgwLeafletMap = function() {
    // 1. "The Lab" Studio Marker — 2px Outline Circle Badge
    const labIcon = L.divIcon({
      className: "igw-marker-lab-wrap",
      html: '<div class="igw-marker-lab"><span class="igw-marker-lab__circle">The Lab</span></div>',
      iconSize: [90, 34],
      iconAnchor: [45, 17]
    });

    L.marker(labCoords, { icon: labIcon, title: "Ioulia Geraskli Ceramic Lab" }).addTo(map);

    // 2. ISAP Metro Station Marker (Ano Patisia)
    const isapIcon = L.divIcon({
      className: "igw-marker-metro-wrap",
      html: '<div class="igw-marker-metro"><span class="igw-marker-metro__circle">M</span><span class="igw-marker-metro__label">Ano Patisia ISAP</span></div>',
      iconSize: [150, 26],
      iconAnchor: [12, 13] // Exact center of the 24px 'M' circle
    });

    L.marker(isapCoords, { icon: isapIcon, title: "Ano Patisia Metro / ISAP Station" }).addTo(map);

    // Frame both markers gracefully inside the visible map area
    const bounds = L.latLngBounds([labCoords, isapCoords]);
    const isMobile = window.innerWidth <= 900;
    map.fitBounds(bounds, {
      paddingTopLeft: isMobile ? [40, 40] : [70, 440], // offset for left floating info panel on desktop
      paddingBottomRight: [60, 60],
      maxZoom: 16
    });

    // Position Zoom Controls at top right
    L.control.zoom({ position: 'topright' }).addTo(map);
  };

  document.addEventListener("DOMContentLoaded", () => {

    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

    // Dynamically Load Leaflet JS script if needed
    if (typeof L === "undefined") {
      const script = document.createElement("script");
      script.src = "https://unpkg.com/leaflet@1.9.4/dist/leaflet.js";
      script.onload = window.initIgwLeafletMap;
      document.head.appendChild(script);
    } else {
      window.initIgwLeafletMap();
    }

    // Detect Elementor Editor Mode
    const isElementor = document.body.classList.contains('elementor-editor-active') ||
                        document.body.classList.contains('elementor-editor-preview') ||
                        document.documentElement.classList.contains('elementor-html') ||
                        (window.elementorFrontend && window.elementorFrontend.isEditMode());

    // 1. Initial Hero Entrance Animation
    if (isElementor) {
      document.body.classList.add('is-loaded');
    } else {
      setTimeout(() => {
  /* Arms the shared entrance system in the global script. Set here, inline,
     so nothing is painted visible and then hidden. */
  document.documentElement.classList.add('ia-js');

        document.body.classList.add('is-loaded');
      }, 100);
    }

    // 2. Scroll Reveal Observer
    const revealElements = document.querySelectorAll('.igw-scroll-reveal');
    if (isElementor) {
      revealElements.forEach(el => el.classList.add('in-view'));
    } else if ('IntersectionObserver' in window && revealElements.length > 0) {
      const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('in-view');
            revealObserver.unobserve(entry.target);
          }
        });
      }, { rootMargin: '0px 0px -15% 0px' });

      revealElements.forEach(el => revealObserver.observe(el));
    } else {
      revealElements.forEach(el => el.classList.add('in-view'));
    }

    // 3. Expandable Techniques Accordion
    const techTriggers = document.querySelectorAll('.igw-tech__trigger');
    techTriggers.forEach(trigger => {
      trigger.addEventListener('click', () => {
        const expanded = trigger.getAttribute('aria-expanded') === 'true';
        techTriggers.forEach(t => t.setAttribute('aria-expanded', 'false'));
        trigger.setAttribute('aria-expanded', !expanded);
      });
    });

    // 4. FAQ Accordion Logic
    const faqTriggers = document.querySelectorAll('.igw-faq__trigger');
    faqTriggers.forEach(trigger => {
      trigger.addEventListener('click', () => {
        const expanded = trigger.getAttribute('aria-expanded') === 'true';
        faqTriggers.forEach(t => t.setAttribute('aria-expanded', 'false'));
        trigger.setAttribute('aria-expanded', !expanded);
      });
    });

    // 5. SCROLL-DRIVEN STATEMENT (word by word)
    const beginSection = document.querySelector("[data-igw-begin]");
    const wordsHost = document.querySelector("[data-igw-words]");
    const rail = document.querySelector("[data-igw-rail]");
    const facts = document.querySelectorAll(".igw-begin__facts li");

    if (beginSection && wordsHost) {
      /* Site Studio unslashes canvas JS on import, so a whitespace class
         written here arrives with its backslash stripped and the sentence
         splits on the letter s instead. The class is built from the
         characters themselves so there is nothing to strip. */
      const whitespace = new RegExp("[" + String.fromCharCode(32, 9, 10, 13) + "]+");
      const words = wordsHost.textContent.trim().split(whitespace);
      wordsHost.textContent = "";
      words.forEach((w, i) => {
        const span = document.createElement("span");
        span.className = "igw-begin__word";
        span.textContent = w;
        wordsHost.appendChild(span);
        if (i < words.length - 1) wordsHost.appendChild(document.createTextNode(" "));
      });

      const wordEls = wordsHost.querySelectorAll(".igw-begin__word");

      if (isElementor || reduceMotion.matches) {
        wordEls.forEach(el => el.classList.add("is-lit"));
        if (rail) rail.style.transform = "scaleX(1)";
        facts.forEach(li => li.classList.add("is-in"));
      } else {
        let scheduled = false;

        const update = () => {
          scheduled = false;
          const rect = beginSection.getBoundingClientRect();
          const distance = rect.height - window.innerHeight;
          if (distance <= 0) return;

          let p = (-rect.top) / distance;
          p = Math.min(1, Math.max(0, p));

          const start = 0.08, end = 0.72;
          const local = Math.min(1, Math.max(0, (p - start) / (end - start)));
          const lit = Math.round(local * wordEls.length);

          wordEls.forEach((el, i) => {
            el.classList.toggle("is-lit", i < lit);
          });

          if (rail) rail.style.transform = "scaleX(" + local.toFixed(3) + ")";

          facts.forEach(li => li.classList.toggle("is-in", p > 0.78));
        };

        const onScroll = () => {
          if (!scheduled) {
            scheduled = true;
            window.requestAnimationFrame(update);
          }
        };

        window.addEventListener("scroll", onScroll, { passive: true });
        window.addEventListener("resize", onScroll);
        update();
      }
    }

    // 7. Full-Height Scroll Parallax on Intro SVG Graphic (slides UP from bottom to top of section)
    const introSvg = document.querySelector("[data-igw-intro-svg]");
    const introSection = document.querySelector(".igw-intro");

    if (introSvg && introSection && !reduceMotion.matches && !isElementor) {
      let ticking = false;

      const updateIntroParallax = () => {
        ticking = false;
        const rect = introSection.getBoundingClientRect();
        const windowHeight = window.innerHeight;

        if (rect.top <= windowHeight && rect.bottom >= 0) {
          // Progress goes from 0 (entering from bottom) to 1 (exiting at top)
          const progress = (windowHeight - rect.top) / (windowHeight + rect.height);
          // High intensity parallax: travels across section height (~320px travel range)
          const travelDistance = Math.min(360, rect.height * 0.45);
          const translateY = (0.5 - progress) * (travelDistance * 2);
          introSvg.style.transform = `translate3d(0, ${translateY.toFixed(2)}px, 0)`;
        }
      };

      const onScrollIntro = () => {
        if (!ticking) {
          ticking = true;
          window.requestAnimationFrame(updateIntroParallax);
        }
      };

      window.addEventListener("scroll", onScrollIntro, { passive: true });
      window.addEventListener("resize", onScrollIntro);
      updateIntroParallax();
    }

  });