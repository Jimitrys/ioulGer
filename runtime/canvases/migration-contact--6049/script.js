document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("igc-multistep-form");
    const dynamicTitle = document.getElementById("igc-dynamic-title");
    const progressBar = document.getElementById("igc-progress-bar");
    const progressCounter = document.getElementById("igc-progress-counter");
    
    if (!form || !dynamicTitle) return;

    let currentStepKey = "1";
    let stepSequence = ["1", "2a", "2b", "2c", "3"];
    let isTransitioning = false;

    const steps = form.querySelectorAll(".igc-step[data-step]");
    const hiddenInquiryVal = document.getElementById("inquiry_type_val");
    const hiddenCategoryVal = document.getElementById("piece_category_val");

    // Size Slider Widget Logic
    const sizeInput = document.getElementById("size_range_input");
    const sizeValueDisplay = document.getElementById("igc-size-value");
    const sizeTicks = document.querySelectorAll(".igc-size-tick");
    const hiddenSizeVal = document.getElementById("piece_size_label_val");

    const sizeMap = {
      "1": { label: "Small (< 20 cm)", text: "Small (< 20 cm)" },
      "2": { label: "Medium (20 – 40 cm)", text: "Medium (20 – 40 cm)" },
      "3": { label: "Large / Statement (40 cm+)", text: "Large / Statement (40 cm+)" }
    };

    const setSize = (valStr) => {
      if (sizeInput) sizeInput.value = valStr;
      if (sizeValueDisplay) sizeValueDisplay.textContent = sizeMap[valStr].text;
      if (hiddenSizeVal) hiddenSizeVal.value = sizeMap[valStr].label;

      sizeTicks.forEach(tick => {
        tick.classList.toggle("is-active", tick.getAttribute("data-size-val") === valStr);
      });
    };

    if (sizeInput) {
      sizeInput.addEventListener("input", (e) => setSize(e.target.value));
    }

    sizeTicks.forEach(tick => {
      tick.addEventListener("click", () => setSize(tick.getAttribute("data-size-val")));
    });

    // Dynamic sequence calculation
    const updateSequence = () => {
      const type = hiddenInquiryVal ? hiddenInquiryVal.value : "custom_piece";
      if (type === "general_question") {
        stepSequence = ["1", "2general", "3"];
      } else {
        stepSequence = ["1", "2a", "2b", "2c", "3"];
      }
    };

    // Update Progress Bar & Counter
    const updateProgress = (stepKey) => {
      updateSequence();
      let index = stepSequence.indexOf(stepKey);
      if (stepKey === "success") index = stepSequence.length - 1;
      if (index === -1) index = 0;

      const total = stepSequence.length;
      const percent = Math.min(100, Math.max(20, Math.round(((index + 1) / total) * 100)));

      if (progressBar) progressBar.style.width = percent + "%";
      if (progressCounter) {
        const stepNumText = String(index + 1).padStart(2, '0');
        const totalNumText = String(total).padStart(2, '0');
        progressCounter.textContent = `STEP ${stepNumText} / ${totalNumText}`;
      }
    };

    // ULTRA SMOOTH NON-JUMPY STEP TRANSITION (Exit -> Entrance)
    const updateStepUI = (targetStepKey) => {
      if (isTransitioning || targetStepKey === currentStepKey) return;
      isTransitioning = true;

      const currentStepEl = form.querySelector(`.igc-step[data-step='${currentStepKey}']`);
      const targetStepEl = form.querySelector(`.igc-step[data-step='${targetStepKey}']`);

      if (!targetStepEl) {
        isTransitioning = false;
        return;
      }

      // Title exit & update
      const newTitle = targetStepEl.getAttribute("data-title") || "Contact Us";
      dynamicTitle.style.opacity = "0";
      dynamicTitle.style.transform = "translateY(-6px)";

      // Step Exit animation
      if (currentStepEl) {
        currentStepEl.classList.remove("is-active");
        currentStepEl.classList.add("is-exiting");
      }

      setTimeout(() => {
        if (currentStepEl) {
          currentStepEl.classList.remove("is-exiting");
          currentStepEl.style.display = "none";
        }

        // Show target step
        targetStepEl.style.display = "flex";
        void targetStepEl.offsetWidth; // force layout recalc
        targetStepEl.classList.add("is-active");

        // Update Title text and entrance
        dynamicTitle.textContent = newTitle;
        dynamicTitle.style.opacity = "1";
        dynamicTitle.style.transform = "translateY(0)";

        currentStepKey = targetStepKey;
        updateProgress(targetStepKey);
        isTransitioning = false;
      }, 220);
    };

    // Step 1: Purpose Choice Buttons (Auto Advance)
    const choiceButtons = form.querySelectorAll(".igc-choice-btn");
    choiceButtons.forEach(btn => {
      btn.addEventListener("click", () => {
        const choice = btn.getAttribute("data-choice");
        if (hiddenInquiryVal) hiddenInquiryVal.value = choice;
        updateSequence();
        
        if (choice === "general_question") {
          updateStepUI("2general");
        } else {
          updateStepUI("2a");
        }
      });
    });

    // Step 2A: Custom Piece Category Cards (Auto Advance on Click)
    const catCards = form.querySelectorAll(".igc-cat-card");
    catCards.forEach(card => {
      card.addEventListener("click", () => {
        catCards.forEach(c => c.classList.remove("is-selected"));
        card.classList.add("is-selected");

        const cat = card.getAttribute("data-category");
        if (hiddenCategoryVal) hiddenCategoryVal.value = cat;

        setTimeout(() => {
          updateStepUI("2b");
        }, 150);
      });
    });

    // Validation
    const validateStep = (stepKey) => {
      let isValid = true;
      const currentStepEl = form.querySelector(`.igc-step[data-step='${stepKey}']`);
      if (!currentStepEl) return true;

      const visibleRequiredInputs = currentStepEl.querySelectorAll("input[required]:not([style*='display: none']), textarea[required]:not([style*='display: none'])");

      visibleRequiredInputs.forEach(input => {
        const fieldParent = input.closest(".igc-field");
        if (!input.value.trim() || (input.type === "email" && !/S+@S+.S+/.test(input.value))) {
          isValid = false;
          if (fieldParent) fieldParent.classList.add("has-error");
        } else {
          if (fieldParent) fieldParent.classList.remove("has-error");
        }
      });

      return isValid;
    };

    // Clear error state on field input
    form.addEventListener("input", (e) => {
      const field = e.target.closest(".igc-field");
      if (field && field.classList.contains("has-error")) {
        field.classList.remove("has-error");
      }
    });

    // Action Buttons (Next / Prev)
    form.addEventListener("click", (e) => {
      const btn = e.target.closest("button[data-action]");
      if (!btn) return;

      const action = btn.getAttribute("data-action");
      updateSequence();
      const currentIndex = stepSequence.indexOf(currentStepKey);

      if (action === "next") {
        if (validateStep(currentStepKey)) {
          if (currentIndex < stepSequence.length - 1) {
            updateStepUI(stepSequence[currentIndex + 1]);
          }
        }
      } else if (action === "prev") {
        if (currentIndex > 0) {
          updateStepUI(stepSequence[currentIndex - 1]);
        }
      }
    });

    // Form Submit Handler
    form.addEventListener("submit", (e) => {
      e.preventDefault();

      if (validateStep(currentStepKey)) {
        updateStepUI("success");
        const progressNav = document.querySelector(".igc-progress");
        if (progressNav) progressNav.style.display = "none";
      }
    });

    // Form Reset Handler
    const resetBtn = document.getElementById("igc-reset-form");
    if (resetBtn) {
      resetBtn.addEventListener("click", () => {
        form.reset();
        catCards.forEach(c => c.classList.remove("is-selected"));
        setSize("2");
        const progressNav = document.querySelector(".igc-progress");
        if (progressNav) progressNav.style.display = "flex";
        updateStepUI("1");
      });
    }

  });