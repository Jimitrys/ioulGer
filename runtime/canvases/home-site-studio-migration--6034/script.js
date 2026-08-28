(function () {
  "use strict";

  var root = document.querySelector(".iznE-parallax");
  if (!root) return;

  var isElementorEditor = document.body.classList.contains('elementor-editor-active');

  // Ενεργοποιούμε το ορατό state άμεσα αν είμαστε στον Elementor Editor
  if (isElementorEditor) {
    root.classList.add("is-fully-in-view");
    return;
  }

  var mainImage = root.querySelector("[data-izn-main-image]");
  var reducedMotionQuery = window.matchMedia("(prefers-reduced-motion: reduce)");
  var currentMainY = 0;
  var animationFrame = 0, isVisible = true;

  function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max);
  }

  function getProgress() {
    var rect = root.getBoundingClientRect();
    var scrollableDistance = Math.max(root.offsetHeight - window.innerHeight, 1);
    return clamp(-rect.top / scrollableDistance, 0, 1);
  }

  function render() {
    animationFrame = 0;
    if (!isVisible || reducedMotionQuery.matches) return;

    var rect = root.getBoundingClientRect();
    var viewportHeight = window.innerHeight;
    
    var isFullyInView = (rect.top <= 0 && rect.bottom >= viewportHeight);
    if (isFullyInView) {
      root.classList.add("is-fully-in-view");
    } else {
      root.classList.remove("is-fully-in-view");
    }

    var progress = getProgress();
    var isMobile = window.matchMedia("(max-width: 767px)").matches;
    var strength = isMobile ? 9 : 18;

    var targetMainY = (progress - 0.5) * viewportHeight * (strength / 100);

    currentMainY += (targetMainY - currentMainY) * 0.075;

    if (mainImage) mainImage.style.transform = "translate3d(0," + currentMainY.toFixed(2) + "px,0) scale(1.07)";

    if (Math.abs(targetMainY - currentMainY) > 0.08) {
      requestRender();
    }
  }

  function requestRender() {
    if (!animationFrame && isVisible && !reducedMotionQuery.matches) {
      animationFrame = window.requestAnimationFrame(render);
    }
  }

  if ("IntersectionObserver" in window) {
    var observer = new IntersectionObserver(
      function (entries) {
        isVisible = entries[0].isIntersecting;
        if (isVisible) requestRender();
      },
      { rootMargin: "20% 0px 20% 0px" }
    );
    observer.observe(root);
  }

  window.addEventListener("scroll", requestRender, { passive: true });
  window.addEventListener("resize", requestRender, { passive: true });
  requestRender();
})();