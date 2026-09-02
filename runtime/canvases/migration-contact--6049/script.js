document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("igc-multistep-form");
  const stage = document.getElementById("igc-stage");
  const dynamicTitle = document.getElementById("igc-dynamic-title");
  const progress = document.querySelector(".igc-progress");
  const progressTrack = document.getElementById("igc-progress-track");

  if (!form || !stage || !dynamicTitle || !progressTrack) return;

  const steps = Array.from(form.querySelectorAll(".igc-step[data-step]"));
  const hiddenInquiryVal = document.getElementById("inquiry_type_val");
  const hiddenCategoryVal = document.getElementById("piece_category_val");
  const sizeInput = document.getElementById("size_range_input");
  const sizeValueDisplay = document.getElementById("igc-size-value");
  const sizeTicks = Array.from(form.querySelectorAll(".igc-size-tick"));
  const hiddenSizeVal = document.getElementById("piece_size_label_val");
  const choiceButtons = Array.from(form.querySelectorAll(".igc-choice-btn"));
  const catCards = Array.from(form.querySelectorAll(".igc-cat-card"));
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const compactExperience = window.matchMedia("(max-width: 1024px), (max-height: 900px)");

  let currentStepKey = "1";
  let stepSequence = ["1", "2a", "2b", "2c", "3"];
  let isTransitioning = false;
  let transitionTimer = 0;
  let transitionUnlockTimer = 0;

  const sizeMap = {
    "1": "Small (< 20 cm)",
    "2": "Medium (20 – 40 cm)",
    "3": "Large / Statement (40 cm+)"
  };

  const activeStep = () => form.querySelector(`.igc-step[data-step='${currentStepKey}']`);

  const updateSequence = () => {
    stepSequence = hiddenInquiryVal && hiddenInquiryVal.value === "general_question"
      ? ["1", "2general", "3"]
      : ["1", "2a", "2b", "2c", "3"];
  };

  const syncStageHeight = (step = activeStep()) => {
    if (!step) return;
    if (compactExperience.matches) {
      stage.style.removeProperty("height");
      return;
    }
    window.requestAnimationFrame(() => {
      stage.style.height = `${step.scrollHeight}px`;
    });
  };

  const renderProgress = (stepKey) => {
    updateSequence();
    let index = stepSequence.indexOf(stepKey);
    if (stepKey === "success") index = stepSequence.length - 1;
    if (index < 0) index = 0;

    if (progressTrack.children.length !== stepSequence.length) {
      progressTrack.replaceChildren(...stepSequence.map(() => {
        const segment = document.createElement("span");
        segment.className = "igc-progress__segment";
        segment.setAttribute("aria-hidden", "true");
        return segment;
      }));
    }

    Array.from(progressTrack.children).forEach((segment, segmentIndex) => {
      segment.classList.toggle("is-done", segmentIndex <= index);
    });
    progressTrack.setAttribute("aria-valuemax", String(stepSequence.length));
    progressTrack.setAttribute("aria-valuenow", String(index + 1));
  };

  const setSize = (value) => {
    const size = sizeMap[value] || sizeMap["2"];
    if (sizeInput) sizeInput.value = value;
    if (sizeValueDisplay) sizeValueDisplay.textContent = size;
    if (hiddenSizeVal) hiddenSizeVal.value = size;
    sizeTicks.forEach((tick) => {
      const selected = tick.dataset.sizeVal === value;
      tick.classList.toggle("is-active", selected);
      tick.setAttribute("aria-pressed", String(selected));
    });
  };

  const validateStep = (stepKey) => {
    const step = form.querySelector(`.igc-step[data-step='${stepKey}']`);
    if (!step) return true;

    let firstInvalid = null;
    step.querySelectorAll("input[required], textarea[required], select[required]").forEach((input) => {
      const field = input.closest(".igc-field");
      const value = input.value.trim();
      const valid = Boolean(value) && (input.type !== "email" || /\S+@\S+\.\S+/.test(value));
      input.setAttribute("aria-invalid", String(!valid));
      if (field) field.classList.toggle("has-error", !valid);
      if (!valid && !firstInvalid) firstInvalid = input;
    });

    if (firstInvalid) firstInvalid.focus({ preventScroll: true });
    return !firstInvalid;
  };

  const updateStepUI = (targetStepKey) => {
    if (isTransitioning || targetStepKey === currentStepKey) return;

    const current = activeStep();
    const target = form.querySelector(`.igc-step[data-step='${targetStepKey}']`);
    if (!target) return;

    updateSequence();
    const currentIndex = stepSequence.indexOf(currentStepKey);
    const targetIndex = stepSequence.indexOf(targetStepKey);
    const goingBack = targetStepKey !== "success" && targetIndex < currentIndex;
    const nextTitle = target.dataset.title || "Contact Us";
    const delay = reduceMotion ? 0 : 120;

    isTransitioning = true;
    window.clearTimeout(transitionTimer);
    window.clearTimeout(transitionUnlockTimer);
    dynamicTitle.style.opacity = "0";
    dynamicTitle.style.transform = goingBack ? "translateX(8px)" : "translateX(-8px)";

    if (current) {
      current.setAttribute("aria-hidden", "true");
      current.setAttribute("inert", "");
      current.classList.toggle("is-back", goingBack);
      current.classList.remove("is-active");
      current.classList.add("is-exiting");
    }

    target.classList.toggle("is-back", goingBack);
    target.setAttribute("aria-hidden", "false");
    target.removeAttribute("inert");
    target.style.display = "flex";
    syncStageHeight(target);

    transitionTimer = window.setTimeout(() => {
      if (current) {
        current.classList.remove("is-exiting", "is-back");
        current.style.display = "";
      }

      target.classList.add("is-active");
      dynamicTitle.textContent = nextTitle;
      dynamicTitle.style.opacity = "1";
      dynamicTitle.style.transform = "none";
      currentStepKey = targetStepKey;
      renderProgress(targetStepKey);
      syncStageHeight(target);
      stage.scrollTo({ top: 0, behavior: "auto" });

      transitionUnlockTimer = window.setTimeout(() => {
        target.classList.remove("is-back");
        isTransitioning = false;
      }, reduceMotion ? 0 : 500);
    }, delay);
  };

  choiceButtons.forEach((button) => {
    button.setAttribute("aria-pressed", "false");
    button.addEventListener("click", () => {
      const choice = button.dataset.choice;
      choiceButtons.forEach((item) => {
        const selected = item === button;
        item.classList.toggle("is-selected", selected);
        item.setAttribute("aria-pressed", String(selected));
      });
      if (hiddenInquiryVal) hiddenInquiryVal.value = choice;
      updateSequence();
      window.setTimeout(() => updateStepUI(choice === "general_question" ? "2general" : "2a"), reduceMotion ? 0 : 120);
    });
  });

  catCards.forEach((card) => {
    card.setAttribute("aria-pressed", "false");
    card.addEventListener("click", () => {
      catCards.forEach((item) => {
        const selected = item === card;
        item.classList.toggle("is-selected", selected);
        item.setAttribute("aria-pressed", String(selected));
      });
      if (hiddenCategoryVal) hiddenCategoryVal.value = card.dataset.category || "";
      window.setTimeout(() => updateStepUI("2b"), reduceMotion ? 0 : 120);
    });
  });

  if (sizeInput) sizeInput.addEventListener("input", (event) => setSize(event.target.value));
  sizeTicks.forEach((tick) => tick.addEventListener("click", () => setSize(tick.dataset.sizeVal)));

  form.addEventListener("input", (event) => {
    const field = event.target.closest(".igc-field");
    if (field) field.classList.remove("has-error");
    event.target.removeAttribute("aria-invalid");
  });

  form.addEventListener("click", (event) => {
    const button = event.target.closest("button[data-action]");
    if (!button) return;

    updateSequence();
    const currentIndex = stepSequence.indexOf(currentStepKey);
    if (button.dataset.action === "next" && validateStep(currentStepKey) && currentIndex < stepSequence.length - 1) {
      updateStepUI(stepSequence[currentIndex + 1]);
    }
    if (button.dataset.action === "prev" && currentIndex > 0) {
      updateStepUI(stepSequence[currentIndex - 1]);
    }
  });

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    if (!validateStep(currentStepKey)) return;
    if (progress) progress.hidden = true;
    updateStepUI("success");
  });

  const resetButton = document.getElementById("igc-reset-form");
  if (resetButton) {
    resetButton.addEventListener("click", () => {
      form.reset();
      choiceButtons.concat(catCards).forEach((item) => {
        item.classList.remove("is-selected");
        item.setAttribute("aria-pressed", "false");
      });
      if (hiddenInquiryVal) hiddenInquiryVal.value = "custom_piece";
      if (hiddenCategoryVal) hiddenCategoryVal.value = "";
      setSize("2");
      if (progress) progress.hidden = false;
      updateStepUI("1");
    });
  }

  steps.forEach((step) => {
    step.style.display = "";
    step.classList.remove("is-exiting", "is-back");
    const active = step.dataset.step === currentStepKey;
    step.setAttribute("aria-hidden", String(!active));
    step.toggleAttribute("inert", !active);
  });
  setSize("2");
  renderProgress(currentStepKey);
  syncStageHeight();

  if ("ResizeObserver" in window) {
    new ResizeObserver(() => syncStageHeight()).observe(form);
  }
  compactExperience.addEventListener("change", () => syncStageHeight());
  window.addEventListener("resize", () => syncStageHeight(), { passive: true });
});
