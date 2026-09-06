(function () {
  var root = document.querySelector('.iwl');
  if (!root) { return; }

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  function splitWords(node, className) {
    var whitespace = new RegExp('[' + String.fromCharCode(32, 9, 10, 13) + ']+');
    var words = node.textContent.trim().split(whitespace);
    node.textContent = '';
    words.forEach(function (word, index) {
      var span = document.createElement('span');
      span.className = className;
      span.textContent = word;
      span.style.setProperty('--iwl-word-i', index);
      node.appendChild(span);
      if (index < words.length - 1) { node.appendChild(document.createTextNode(' ')); }
    });
  }

  var revealNodes = root.querySelectorAll('.iwl__intro-art, .iwl__intro-copy, .iwl__gallery-item, .iwl__facts-main, .iwl__schedule p, .iwl__fact-grid > div, .iwl__faq-item, .iwl__location-card, .iwl__cta-actions');
  revealNodes.forEach(function (node, index) {
    node.classList.add('iwl__reveal');
    node.style.setProperty('--iwl-delay', Math.min(index % 5, 4));
  });

  var darkHeading = root.querySelector('.iwl__experience h2');
  if (darkHeading) { splitWords(darkHeading, 'iwl__scroll-word'); }
  var darkWords = root.querySelectorAll('.iwl__scroll-word');

  if (reduceMotion || !('IntersectionObserver' in window)) {
    revealNodes.forEach(function (node) { node.classList.add('is-in'); });
    darkWords.forEach(function (node) { node.classList.add('is-lit'); });
    return;
  }

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) { return; }
      entry.target.classList.add('is-in');
      observer.unobserve(entry.target);
    });
  }, { rootMargin: '0px 0px -12% 0px', threshold: .08 });

  revealNodes.forEach(function (node) { observer.observe(node); });

  var darkSection = root.querySelector('.iwl__experience');
  var gallery = root.querySelector('.iwl__gallery');
  var galleryImages = root.querySelectorAll('.iwl__gallery-item img');
  var introArt = root.querySelector('.iwl__intro-art img');
  var introSection = root.querySelector('.iwl__intro');
  var ticking = false;

  function clamp(value) { return Math.max(0, Math.min(1, value)); }

  function updateScrollMotion() {
    ticking = false;
    var viewport = window.innerHeight;

    if (darkSection && darkWords.length) {
      var darkRect = darkSection.getBoundingClientRect();
      var darkProgress = clamp((viewport * .78 - darkRect.top) / (darkRect.height * .72));
      var lit = Math.round(darkProgress * (darkWords.length + 2));
      darkWords.forEach(function (word, index) { word.classList.toggle('is-lit', index < lit); });
    }

    if (gallery) {
      var galleryRect = gallery.getBoundingClientRect();
      var galleryProgress = clamp((viewport - galleryRect.top) / (viewport + galleryRect.height));
      galleryImages.forEach(function (image, index) {
        var direction = index % 2 ? -1 : 1;
        image.style.setProperty('--iwl-image-y', ((galleryProgress - .5) * 34 * direction).toFixed(2) + 'px');
      });
    }

    if (introArt && introSection) {
      var introRect = introSection.getBoundingClientRect();
      var introProgress = clamp((viewport - introRect.top) / (viewport + introRect.height));
      introArt.style.setProperty('--iwl-art-y', ((.5 - introProgress) * 70).toFixed(2) + 'px');
    }
  }

  function requestUpdate() {
    if (ticking) { return; }
    ticking = true;
    window.requestAnimationFrame(updateScrollMotion);
  }

  window.addEventListener('scroll', requestUpdate, { passive: true });
  window.addEventListener('resize', requestUpdate);
  updateScrollMotion();
}());
