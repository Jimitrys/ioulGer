/* Cart — quantity, removal and coupons without a page reload.

   Every change is sent through WooCommerce's own cart form, nonce and all, and
   the reply is WooCommerce's own rendering of the page. Nothing here decides
   what a cart costs; it only asks the same question the Update button asks and
   swaps in the answer.

   That is also why it degrades cleanly: with the script gone the steppers are
   never added, the number field and the Update button are exactly as
   WooCommerce printed them, and the page works the way it always did. */
(function () {
  const root = document.querySelector(".igc-shop--cart");
  if (!root) return;

  const bodyOf = () => root.querySelector(".igc-shop__body");
  const formOf = () => root.querySelector("form.woocommerce-cart-form");
  if (!formOf()) return;

  /* Says the steppers are live, which is what hides the Update button. */
  root.classList.add("is-live");

  let pending = 0;

  /* --------------------------------------------------------------------
     Asking WooCommerce */

  const swap = (html) => {
    const next = new DOMParser().parseFromString(html, "text/html");
    const fresh = next.querySelector(".igc-shop--cart .igc-shop__body");
    const here = bodyOf();
    if (!fresh || !here) {
      window.location.reload();
      return;
    }
    here.replaceChildren(...fresh.childNodes);
    decorate();

    /* The header's basket count lives outside this page. */
    if (window.jQuery) window.jQuery(document.body).trigger("wc_fragment_refresh");
  };

  const send = async (build) => {
    const form = formOf();
    if (!form) return;

    const token = ++pending;
    const body = new FormData(form);
    build(body);

    try {
      const response = await fetch(form.getAttribute("action") || window.location.href, {
        method: "POST",
        body: body,
        credentials: "same-origin",
        headers: { "X-Requested-With": "XMLHttpRequest" }
      });
      const html = await response.text();

      /* A later change has already been sent; this answer is stale. */
      if (token !== pending) return;
      swap(html);
    } catch (error) {
      window.location.reload();
    }
  };

  const updateCart = () => send((body) => body.set("update_cart", "1"));

  /* --------------------------------------------------------------------
     Steppers */

  const busy = (input) => {
    const row = input.closest(".cart_item");
    if (row) row.classList.add("is-busy");
  };

  const step = (input, by) => {
    const min = Number(input.getAttribute("min") || 0);
    const max = input.getAttribute("max") ? Number(input.getAttribute("max")) : Infinity;
    const now = Number(input.value || 0);
    const next = Math.min(max, Math.max(min, now + by));
    if (next === now) return;
    input.value = String(next);
    busy(input);
    updateCart();
  };

  const button = (label, title) => {
    const element = document.createElement("button");
    element.type = "button";
    element.className = "igc-qty-step";
    element.textContent = label;
    element.setAttribute("aria-label", title);
    return element;
  };

  function decorate() {
    root.querySelectorAll(".quantity").forEach((box) => {
      const input = box.querySelector("input.qty");
      if (!input || box.querySelector(".igc-qty-step")) return;

      const less = button("−", input.dataset.less || "Λιγότερα");
      const more = button("+", input.dataset.more || "Περισσότερα");
      less.addEventListener("click", () => step(input, -1));
      more.addEventListener("click", () => step(input, 1));
      box.insertBefore(less, input);
      box.appendChild(more);

      const min = Number(input.getAttribute("min") || 0);
      less.disabled = Number(input.value || 0) <= min;
    });
  }

  /* --------------------------------------------------------------------
     Everything else on the page, by delegation, so a swapped-in cart needs
     no rebinding. */

  root.addEventListener("click", (event) => {
    const remove = event.target.closest("a.remove");
    if (remove) {
      event.preventDefault();
      const row = remove.closest(".cart_item");
      if (row) row.classList.add("is-busy");
      fetch(remove.href, { credentials: "same-origin", headers: { "X-Requested-With": "XMLHttpRequest" } })
        .then((response) => response.text())
        .then(swap)
        .catch(() => window.location.reload());
      return;
    }

    const coupon = event.target.closest("button[name=apply_coupon]");
    if (coupon) {
      event.preventDefault();
      send((body) => body.set("apply_coupon", "1"));
    }
  });

  /* Typing a number straight into the field still counts as a change. */
  root.addEventListener("change", (event) => {
    const input = event.target.closest("input.qty");
    if (!input) return;
    busy(input);
    updateCart();
  });

  decorate();
})();
