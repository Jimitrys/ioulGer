/* Contact — the form's behaviour.

   Three rules, because breaking them is what made the previous version feel
   unreliable:

   1. A step change is one synchronous swap. There is never a frame with no
      step on screen, so nothing flashes blank and nothing has to be timed.
   2. Animation is decoration. Every state is correct with the animations
      stripped out, so a throttled tab or a tired phone cannot strand the UI
      mid-transition.
   3. This page owns its own scrolling. Nothing here writes to html or body
      overflow — that belongs to the navbar, and two owners fight.
*/
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("igc-multistep-form");
  const stage = document.getElementById("igc-stage");
  const heading = document.getElementById("igc-dynamic-title");
  const progress = document.querySelector(".igc-progress");
  const track = document.getElementById("igc-progress-track");
  if (!form || !stage || !heading || !track) return;

  /* Only marks the page. App mode itself is decided in the stylesheet. */
  document.body.classList.add("igc-contact-app");

  const steps = new Map();
  form.querySelectorAll(".igc-step[data-step]").forEach((step) => steps.set(step.dataset.step, step));

  const inquiry = document.getElementById("inquiry_type_val");
  const category = document.getElementById("piece_category_val");
  const sizeValue = document.getElementById("size_range_input");
  const sizeLabel = document.getElementById("piece_size_label_val");
  const formError = document.getElementById("igc-form-error");

  const choices = Array.from(form.querySelectorAll(".igc-choice-btn"));
  const cards = Array.from(form.querySelectorAll(".igc-cat-card"));
  const ticks = Array.from(form.querySelectorAll(".igc-size-tick"));

  const ROUTES = {
    custom_piece: ["1", "2a", "2b", "2c", "3"],
    general_question: ["1", "2general", "3"]
  };
  const SIZES = {
    "1": "Small (< 20 cm)",
    "2": "Medium (20 - 40 cm)",
    "3": "Large / Statement (40 cm+)"
  };

  let currentKey = "1";
  const route = () => ROUTES[inquiry && inquiry.value] || ROUTES.custom_piece;

  /* ---------------------------------------------------------------------
     Height. The stylesheet owns it in app mode, where the stage scrolls;
     on a roomy desktop the card grows to fit the step instead, and this is
     the single place that writes it. */

  let watched = null;
  const observer = "ResizeObserver" in window ? new ResizeObserver(() => applyHeight()) : null;

  const inAppMode = () => getComputedStyle(stage).getPropertyValue("--igc-app").trim() === "1";

  function applyHeight() {
    const step = steps.get(currentKey);
    if (!step) return;
    if (inAppMode()) {
      stage.style.removeProperty("height");
      return;
    }
    stage.style.height = step.offsetHeight + "px";
  }

  function watchStep(step) {
    if (!observer) return;
    if (watched) observer.unobserve(watched);
    watched = step;
    observer.observe(step);
  }

  /* A textarea that grows, a font that lands late, a window that changes
     shape — all of them arrive here, and nowhere else. */
  if (observer) observer.observe(document.documentElement);

  /* ---------------------------------------------------------------------
     Steps */

  const setHeading = (text) => {
    heading.textContent = text;
    heading.classList.remove("is-entering");
    void heading.offsetWidth;
    heading.classList.add("is-entering");
  };

  const renderProgress = (key) => {
    const path = route();
    let index = key === "success" ? path.length - 1 : path.indexOf(key);
    if (index < 0) index = 0;

    if (track.children.length !== path.length) {
      track.replaceChildren(...path.map(() => {
        const segment = document.createElement("span");
        segment.className = "igc-progress__segment";
        return segment;
      }));
    }
    Array.from(track.children).forEach((segment, i) => segment.classList.toggle("is-done", i <= index));
    track.setAttribute("aria-valuemax", String(path.length));
    track.setAttribute("aria-valuenow", String(index + 1));
  };

  /* The exit animation is the only thing left running after a swap, and it
     is purely cosmetic: this ends it, whether it played or not. */
  let leaving = null;
  let leavingTimer = 0;

  const endExit = () => {
    window.clearTimeout(leavingTimer);
    if (!leaving) return;
    leaving.classList.remove("is-leaving", "is-back");
    leaving = null;
  };

  function goTo(key) {
    const next = steps.get(key);
    if (!next || key === currentKey) return;

    const path = route();
    const from = path.indexOf(currentKey);
    const to = key === "success" ? path.length : path.indexOf(key);
    const back = to > -1 && from > -1 && to < from;
    const previous = steps.get(currentKey);

    endExit();

    if (previous) {
      previous.classList.remove("is-active");
      previous.classList.toggle("is-back", back);
      previous.classList.add("is-leaving");
      previous.setAttribute("inert", "");
      leaving = previous;
      leavingTimer = window.setTimeout(endExit, 600);
    }

    next.classList.toggle("is-back", back);
    next.classList.add("is-active");
    next.removeAttribute("inert");
    currentKey = key;

    setHeading(next.dataset.title || "");
    renderProgress(key);
    watchStep(next);
    applyHeight();
    stage.scrollTop = 0;

    const focusable = next.querySelector("input:not([type=hidden]), textarea, select, button");
    if (focusable && document.activeElement !== document.body) focusable.focus({ preventScroll: true });
  }

  /* ---------------------------------------------------------------------
     Choices */

  const pick = (group, chosen, write) => {
    group.forEach((item) => {
      const on = item === chosen;
      item.setAttribute("aria-checked", String(on));
      item.tabIndex = on ? 0 : -1;
    });
    if (write) write(chosen);
  };

  choices.forEach((button) => {
    button.addEventListener("click", () => {
      pick(choices, button, (b) => { if (inquiry) inquiry.value = b.dataset.choice; });
      goTo(button.dataset.choice === "general_question" ? "2general" : "2a");
    });
  });

  cards.forEach((card) => {
    card.addEventListener("click", () => {
      pick(cards, card, (c) => { if (category) category.value = c.dataset.category || ""; });
      goTo("2b");
    });
  });

  const setSize = (value) => {
    const tick = ticks.find((t) => t.dataset.sizeVal === value) || ticks[1];
    if (!tick) return;
    pick(ticks, tick);
    if (sizeValue) sizeValue.value = tick.dataset.sizeVal;
    if (sizeLabel) sizeLabel.value = SIZES[tick.dataset.sizeVal] || "";
  };
  ticks.forEach((tick) => tick.addEventListener("click", () => setSize(tick.dataset.sizeVal)));

  /* Arrow keys move within a group, the way a radio group is expected to. */
  const asRadioGroup = (group) => {
    group.forEach((item) => item.addEventListener("keydown", (event) => {
      const step = event.key === "ArrowRight" || event.key === "ArrowDown" ? 1
        : event.key === "ArrowLeft" || event.key === "ArrowUp" ? -1 : 0;
      if (!step) return;
      event.preventDefault();
      const at = group.indexOf(item);
      const target = group[(at + step + group.length) % group.length];
      target.focus();
      if (group === ticks) setSize(target.dataset.sizeVal);
    }));
  };
  [choices, cards, ticks].forEach(asRadioGroup);

  /* ---------------------------------------------------------------------
     Validation */

  const looksLikeEmail = (value) => {
    const at = value.indexOf("@");
    const dot = value.lastIndexOf(".");
    return at > 0 && dot > at + 1 && dot < value.length - 1 && !value.includes(" ");
  };

  const validate = (key) => {
    const step = steps.get(key);
    if (!step) return true;

    let first = null;
    step.querySelectorAll("[required]").forEach((input) => {
      const field = input.closest(".igc-field, .igc-consent");
      const value = input.type === "checkbox" ? (input.checked ? "on" : "") : input.value.trim();
      const ok = Boolean(value) && (input.type !== "email" || looksLikeEmail(value));
      input.setAttribute("aria-invalid", String(!ok));
      if (field) field.classList.toggle("has-error", !ok);
      if (!ok && !first) first = input;
    });

    if (first) {
      first.focus({ preventScroll: true });
      first.scrollIntoView({ block: "nearest", behavior: "smooth" });
    }
    return !first;
  };

  form.addEventListener("input", (event) => {
    const field = event.target.closest(".igc-field, .igc-consent");
    if (field) field.classList.remove("has-error");
    event.target.removeAttribute("aria-invalid");
  });

  form.addEventListener("click", (event) => {
    const button = event.target.closest("button[data-action]");
    if (!button) return;
    const path = route();
    const at = path.indexOf(currentKey);
    if (button.dataset.action === "next" && validate(currentKey) && at < path.length - 1) goTo(path[at + 1]);
    if (button.dataset.action === "prev" && at > 0) goTo(path[at - 1]);
  });

  /* ---------------------------------------------------------------------
     Sending */

  const endpoint = form.dataset.ajax || "";
  const submitButton = form.querySelector("button[type=submit]");

  const showError = (message) => {
    if (!formError) return;
    formError.textContent = message;
    formError.classList.add("is-shown");
    formError.scrollIntoView({ block: "nearest", behavior: "smooth" });
  };

  const token = async () => {
    /* Fetched at send time rather than printed into the page: the page is
       cached, and a cached nonce goes stale while a fresh one never does. */
    const response = await fetch(endpoint + "?action=ioulia_contact_token", { credentials: "same-origin" });
    const payload = await response.json();
    if (!payload || !payload.success || !payload.data || !payload.data.nonce) throw new Error("token");
    return payload.data.nonce;
  };

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    if (!validate(currentKey)) return;
    if (formError) formError.classList.remove("is-shown");

    if (!endpoint) {
      showError("The form is not connected yet. Please email us directly at info@iouliageraskliceramics.com.");
      return;
    }

    if (submitButton) submitButton.setAttribute("aria-busy", "true");

    try {
      const body = new FormData(form);
      body.set("action", "ioulia_contact");
      body.set("nonce", await token());

      const response = await fetch(endpoint, { method: "POST", body, credentials: "same-origin" });
      const payload = await response.json();
      if (!payload || !payload.success) throw new Error((payload && payload.data && payload.data.message) || "failed");

      if (progress) progress.hidden = true;
      goTo("success");
    } catch (error) {
      showError("We could not send your message. Please try again, or email us at info@iouliageraskliceramics.com.");
    } finally {
      if (submitButton) submitButton.removeAttribute("aria-busy");
    }
  });

  const reset = document.getElementById("igc-reset-form");
  if (reset) {
    reset.addEventListener("click", () => {
      form.reset();
      pick(choices, null);
      pick(cards, null);
      if (choices[0]) choices[0].tabIndex = 0;
      if (cards[0]) cards[0].tabIndex = 0;
      if (inquiry) inquiry.value = "custom_piece";
      if (category) category.value = "";
      setSize("2");
      if (progress) progress.hidden = false;
      if (formError) formError.classList.remove("is-shown");
      goTo("1");
    });
  }

  /* ---------------------------------------------------------------------
     Start */

  steps.forEach((step, key) => {
    const active = key === currentKey;
    step.classList.toggle("is-active", active);
    step.classList.remove("is-leaving", "is-back");
    step.toggleAttribute("inert", !active);
  });
  if (choices[0]) choices[0].tabIndex = 0;
  if (cards[0]) cards[0].tabIndex = 0;
  setSize("2");
  renderProgress(currentKey);
  watchStep(steps.get(currentKey));
  applyHeight();
});
