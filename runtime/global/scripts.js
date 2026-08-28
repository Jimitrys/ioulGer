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
});
