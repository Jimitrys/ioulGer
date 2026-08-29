<?php
/**
 * Stop the workshop booking form scrolling the page to itself on load.
 *
 * The ioulia-workshop-bookings plugin ends goTo() with a guard that keeps the
 * active step on screen when the visitor moves between steps:
 *
 *   const shell = root.querySelector(".iwb__flow");
 *   if (shell && Math.abs(shell.getBoundingClientRect().top) > window.innerHeight * 0.6) {
 *       shell.scrollIntoView({ behavior: "smooth", block: "start" });
 *   }
 *
 * Its last line of setup is goTo(0), so the guard also runs on page load. On the
 * home page the form sits around 4800px down, the guard passes, and the visitor
 * lands at the booking form with the whole page above them unseen.
 *
 * The real fix is two lines in that plugin: give goTo a second parameter and
 * call it as goTo(0, false) at the end of setup, so the initial render never
 * scrolls. This snippet is the stopgap until that happens, and it is safe to
 * leave in place afterwards: it only suppresses a scroll nobody asked for.
 *
 * It suppresses scrollIntoView on .iwb__flow only, and only until the visitor
 * first touches the page. Every scroll the form makes in response to a real
 * interaction goes through untouched.
 */

if ( ! function_exists( 'ioulia_suppress_booking_autoscroll' ) ) {
	function ioulia_suppress_booking_autoscroll() {
		if ( is_admin() ) {
			return;
		}
		?>
<script id="ioulia-booking-autoscroll-fix">
(function () {
	var interacted = false;

	function markInteracted() { interacted = true; }

	['pointerdown', 'keydown', 'wheel', 'touchstart'].forEach(function (type) {
		window.addEventListener(type, markInteracted, { once: true, passive: true, capture: true });
	});

	var nativeScrollIntoView = Element.prototype.scrollIntoView;

	Element.prototype.scrollIntoView = function () {
		if (!interacted && this.classList && this.classList.contains('iwb__flow')) {
			return;
		}
		return nativeScrollIntoView.apply(this, arguments);
	};
}());
</script>
		<?php
	}
	add_action( 'wp_head', 'ioulia_suppress_booking_autoscroll', 1 );
}
