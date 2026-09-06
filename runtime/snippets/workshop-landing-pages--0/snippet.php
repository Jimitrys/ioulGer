<?php
/**
 * Focused, bilingual workshop landing pages.
 *
 * The programme facts stay in workshops-data. This file supplies the editorial
 * layer and creates the five public WordPress pages once, after Site Sync.
 *
 * No backslashes anywhere: Site Studio strips one level on import.
 */

if ( ! function_exists( 'ioulia_workshop_landing_copy' ) ) {
	function ioulia_workshop_landing_copy( $slug, $lang ) {
		$copy = array(
			'handbuilding' => array(
				'el' => array(
					'eyebrow' => 'Πηλοπλαστική · Άνω Πατήσια',
					'title' => 'Μαθήματα πηλοπλαστικής στην Αθήνα',
					'lede' => 'Γνώρισε τον πηλό με τα χέρια σου και δημιούργησε ένα δικό σου χρηστικό ή γλυπτικό αντικείμενο, χωρίς να χρειάζεται προηγούμενη εμπειρία.',
					'intro_title' => 'Ο πιο άμεσος δρόμος προς τον πηλό',
					'intro' => 'Με pinch pots, μακαρόνι και φύλλο εξερευνούμε φόρμες, υφές και μικρές ατέλειες που κάνουν κάθε κομμάτι μοναδικό. Δουλεύεις στον δικό σου ρυθμό, με καθοδήγηση όταν τη χρειάζεσαι.',
					'steps' => array( 'Προετοιμάζουμε και γνωρίζουμε το υλικό.', 'Χτίζουμε τη φόρμα με τα χέρια και εργαλεία χειρός.', 'Ολοκληρώνουμε την επιφάνεια και αναλαμβάνουμε τα ψησίματα.' ),
				),
				'en' => array(
					'eyebrow' => 'Handbuilding · Ano Patisia',
					'title' => 'Handbuilding pottery classes in Athens',
					'lede' => 'Meet clay through your hands and create a functional or sculptural piece of your own. No previous pottery experience is needed.',
					'intro_title' => 'The most direct way into clay',
					'intro' => 'Using pinch pots, coiling and slab building, we explore form, texture and the small imperfections that make each piece unique. You work at your own pace, with guidance whenever you need it.',
					'steps' => array( 'Prepare the clay and learn how it behaves.', 'Build your form by hand with simple studio tools.', 'Finish the surface while we take care of the firings.' ),
				),
			),
			'wheel' => array(
				'el' => array(
					'eyebrow' => 'Τροχός · Άνω Πατήσια',
					'title' => 'Μαθήματα κεραμικού τροχού στην Αθήνα',
					'lede' => 'Μια πρακτική εισαγωγή στον τροχό: από το ζύμωμα και το κεντράρισμα μέχρι το πρώτο σου μπολ, κύπελλο ή κύλινδρο.',
					'intro_title' => 'Ρυθμός, συγκέντρωση, επανάληψη',
					'intro' => 'Ο τροχός θέλει χρόνο και ήρεμη προσοχή. Μαθαίνεις τη σωστή στάση, πώς να κεντράρεις τον πηλό και πώς να σηκώνεις σταθερά τοιχώματα, βήμα βήμα.',
					'steps' => array( 'Ζυμώνουμε και κεντράρουμε τον πηλό.', 'Ανοίγουμε τη φόρμα και ανεβάζουμε τα τοιχώματα.', 'Δοκιμάζουμε βασικά σχήματα και κρατάμε το αποτέλεσμα.' ),
				),
				'en' => array(
					'eyebrow' => 'Wheel throwing · Ano Patisia',
					'title' => 'Pottery wheel classes in Athens',
					'lede' => 'A practical introduction to the pottery wheel, from wedging and centring to your first bowl, cup or cylinder.',
					'intro_title' => 'Rhythm, focus and repetition',
					'intro' => 'Wheel throwing rewards time and calm attention. Learn posture, centring and how to pull even walls, one clear step at a time.',
					'steps' => array( 'Wedge and centre the clay.', 'Open the form and pull up the walls.', 'Try essential shapes and keep what you make.' ),
				),
			),
			'kids' => array(
				'el' => array(
					'eyebrow' => 'Παιδιά 6–11 · Άνω Πατήσια',
					'title' => 'Μαθήματα κεραμικής για παιδιά στην Αθήνα',
					'lede' => 'Ένα δημιουργικό εργαστήριο όπου τα παιδιά πλάθουν, πειραματίζονται και μαθαίνουν να εμπιστεύονται τα χέρια και τη φαντασία τους.',
					'intro_title' => 'Χώρος για ελεύθερη δημιουργία',
					'intro' => 'Ο πηλός γίνεται παιχνίδι, αφήγηση και αισθητηριακή εμπειρία. Τα παιδιά γνωρίζουν το υλικό μέσα από κατάλληλες τεχνικές και φεύγουν με κάτι που έφτιαξαν τα ίδια.',
					'steps' => array( 'Γνωρίζουμε τον πηλό μέσα από αφή και παιχνίδι.', 'Δίνουμε μορφή σε μια προσωπική ιδέα.', 'Χρωματίζουμε και προετοιμάζουμε το έργο για ψήσιμο.' ),
				),
				'en' => array(
					'eyebrow' => 'Ages 6–11 · Ano Patisia',
					'title' => 'Pottery classes for children in Athens',
					'lede' => 'A creative workshop where children shape, experiment and learn to trust their hands and imagination.',
					'intro_title' => 'Room for open-ended making',
					'intro' => 'Clay becomes play, storytelling and a sensory experience. Children meet the material through age-appropriate techniques and leave with something they made themselves.',
					'steps' => array( 'Explore clay through touch and play.', 'Turn a personal idea into a three-dimensional form.', 'Add colour and prepare the piece for firing.' ),
				),
			),
			'parent-child' => array(
				'el' => array(
					'eyebrow' => 'Γονέας & παιδί · Άνω Πατήσια',
					'title' => 'Κεραμική για γονείς και παιδιά στην Αθήνα',
					'lede' => 'Μια κοινή εμπειρία δημιουργίας για ένα παιδί 6–15 ετών και έναν γονέα, με χρόνο για συνεργασία, παιχνίδι και ουσιαστική επαφή.',
					'intro_title' => 'Δύο ζευγάρια χέρια, μία ιδέα',
					'intro' => 'Σχεδιάζετε και κατασκευάζετε μαζί ένα κεραμικό αντικείμενο. Η διαδικασία είναι ήρεμη, ανοιχτή και σχεδιασμένη ώστε και οι δύο να συμμετέχετε πραγματικά.',
					'steps' => array( 'Διαλέγετε μαζί την ιδέα και τη φόρμα.', 'Μοιράζεστε τα στάδια της κατασκευής.', 'Ολοκληρώνετε ένα κοινό αντικείμενο για το σπίτι.' ),
				),
				'en' => array(
					'eyebrow' => 'Parent & child · Ano Patisia',
					'title' => 'Parent and child pottery in Athens',
					'lede' => 'A shared making experience for one child aged 6–15 and one parent, with time for collaboration, play and real connection.',
					'intro_title' => 'Two pairs of hands, one idea',
					'intro' => 'Design and build one ceramic object together. The process is calm, open and designed so that both of you take a meaningful part.',
					'steps' => array( 'Choose the idea and form together.', 'Share the making process from start to finish.', 'Complete one shared object to take home.' ),
				),
			),
			'paint-and-sip' => array(
				'el' => array(
					'eyebrow' => 'Κυριακή · Άνω Πατήσια',
					'title' => 'Ζωγραφική κεραμικού στην Αθήνα',
					'lede' => 'Διάλεξε ένα έτοιμο κεραμικό του εργαστηρίου, ζωγράφισέ το με την παρέα σου και απόλαυσε ένα ποτό και θεματικά κεράσματα.',
					'intro_title' => 'Μια Κυριακή με χρώμα',
					'intro' => 'Κάθε συνάντηση έχει διαφορετικό θέμα. Εσύ επιλέγεις το αντικείμενο και τη δική σου εικαστική κατεύθυνση· εμείς αναλαμβάνουμε υάλωμα και ψήσιμο ώστε να είναι έτοιμο για καθημερινή χρήση.',
					'steps' => array( 'Διαλέγεις έτοιμο κεραμικό και παλέτα.', 'Ζωγραφίζεις με καθοδήγηση, ποτό και κέρασμα.', 'Παραλαμβάνεις το υαλωμένο έργο σε περίπου μία εβδομάδα.' ),
				),
				'en' => array(
					'eyebrow' => 'Sunday · Ano Patisia',
					'title' => 'Ceramic painting workshop in Athens',
					'lede' => 'Choose a ceramic piece made in the studio, paint it with friends and enjoy a drink with small themed treats.',
					'intro_title' => 'A Sunday filled with colour',
					'intro' => 'Each session follows a different theme. You choose the object and your own visual direction; we glaze and fire it so it is ready for everyday use.',
					'steps' => array( 'Choose a ceramic piece and colour palette.', 'Paint with guidance, a drink and something to share.', 'Collect the glazed piece in about one week.' ),
				),
			),
		);

		return isset( $copy[ $slug ][ $lang ] ) ? $copy[ $slug ][ $lang ] : array();
	}
}

if ( ! function_exists( 'ioulia_workshop_landing_programme_slug' ) ) {
	function ioulia_workshop_landing_programme_slug() {
		$page_slug = is_page() ? (string) get_post_field( 'post_name', get_queried_object_id() ) : '';
		$map       = function_exists( 'ioulia_workshop_landing_slugs' ) ? ioulia_workshop_landing_slugs() : array();
		$found     = array_search( $page_slug, $map, true );

		return false === $found ? '' : $found;
	}
}

if ( ! function_exists( 'ioulia_workshop_landing_day_times' ) ) {
	function ioulia_workshop_landing_day_times( $sessions, $lang ) {
		$weekdays = 'en' === $lang
			? array( 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday' )
			: array( 1 => 'Δευτέρα', 2 => 'Τρίτη', 3 => 'Τετάρτη', 4 => 'Πέμπτη', 5 => 'Παρασκευή', 6 => 'Σάββατο', 7 => 'Κυριακή' );
		$by_day = array();

		foreach ( (array) $sessions as $session ) {
			$by_day[ $session['day'] ][] = $session['start'] . ' – ' . $session['end'];
		}

		ksort( $by_day );
		$lines = array();
		foreach ( $by_day as $day => $times ) {
			$lines[] = $weekdays[ $day ] . ': ' . implode( ' & ', $times );
		}

		return $lines;
	}
}

if ( ! function_exists( 'ioulia_workshop_landing_render' ) ) {
	function ioulia_workshop_landing_render() {
		$slug      = ioulia_workshop_landing_programme_slug();
		$programme = function_exists( 'ioulia_workshop_programme' ) ? ioulia_workshop_programme( $slug ) : null;
		$lang      = function_exists( 'ioulia_lang' ) && 'en' === ioulia_lang() ? 'en' : 'el';
		$copy      = ioulia_workshop_landing_copy( $slug, $lang );

		if ( ! $programme || empty( $copy ) ) {
			return '';
		}

		$en       = 'en' === $lang;
		$days     = ioulia_workshop_landing_day_times( $programme['sessions'], $lang );
		$booking  = function_exists( 'ioulia_url' ) ? ioulia_url( '/book-workshop/', $lang ) : home_url( '/book-workshop/' );
		$all      = function_exists( 'ioulia_url' ) ? ioulia_url( '/workshops/', $lang ) : home_url( '/workshops/' );
		$images   = array(
			'handbuilding'  => 'https://iouliageraskliceramics.com/wp-content/uploads/2026/09/IMG_9310-1536x1024.webp',
			'wheel'         => 'https://iouliageraskliceramics.com/wp-content/uploads/2026/09/IMG_9251-1536x1024.webp',
			'kids'          => 'https://iouliageraskliceramics.com/wp-content/uploads/2026/07/image_50419713-scaled-1-1-1152x1536.jpg',
			'parent-child'  => 'https://iouliageraskliceramics.com/wp-content/uploads/2026/09/IMG_9439-1536x1024.webp',
			'paint-and-sip' => 'https://iouliageraskliceramics.com/wp-content/uploads/2026/09/IMG_9502-1536x1024.webp',
		);
		$price = number_format_i18n( (float) $programme['price'], 0 ) . '€';

		ob_start();
		?>
		<main class="iwl">
			<section class="iwl__hero" aria-labelledby="iwl-title">
				<div class="iwl__hero-copy">
					<p class="iwl__eyebrow"><?php echo esc_html( $copy['eyebrow'] ); ?></p>
					<h1 id="iwl-title"><?php echo esc_html( $copy['title'] ); ?></h1>
					<p class="iwl__lede"><?php echo esc_html( $copy['lede'] ); ?></p>
					<a class="ioulia-btn ioulia-btn--filled iwl__button" href="<?php echo esc_url( $booking ); ?>"><?php echo esc_html( $en ? 'Book your place' : 'Κλείσε τη θέση σου' ); ?></a>
				</div>
				<figure class="iwl__hero-image"><img src="<?php echo esc_url( $images[ $slug ] ); ?>" alt="<?php echo esc_attr( $copy['title'] ); ?>" loading="eager" fetchpriority="high"></figure>
			</section>

			<section class="iwl__intro">
				<p class="iwl__section-number"><?php echo esc_html( $programme['number'] ); ?></p>
				<h2><?php echo esc_html( $copy['intro_title'] ); ?></h2>
				<p class="iwl__body"><?php echo esc_html( $copy['intro'] ); ?></p>
			</section>

			<section class="iwl__experience" aria-labelledby="iwl-experience-title">
				<header><p class="iwl__eyebrow"><?php echo esc_html( $en ? 'In the studio' : 'Μέσα στο εργαστήριο' ); ?></p><h2 id="iwl-experience-title"><?php echo esc_html( $en ? 'What you will do' : 'Τι θα κάνεις' ); ?></h2></header>
				<ol>
					<?php foreach ( $copy['steps'] as $index => $step ) : ?>
						<li><span><?php echo esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span><p><?php echo esc_html( $step ); ?></p></li>
					<?php endforeach; ?>
				</ol>
			</section>

			<section class="iwl__facts" aria-labelledby="iwl-facts-title">
				<div class="iwl__facts-main">
					<p class="iwl__eyebrow"><?php echo esc_html( $en ? 'Plan your visit' : 'Οργάνωσε την επίσκεψή σου' ); ?></p>
					<h2 id="iwl-facts-title"><?php echo esc_html( $en ? 'Days, times and price' : 'Ημέρες, ώρες και κόστος' ); ?></h2>
					<div class="iwl__schedule">
						<?php foreach ( $days as $day ) : ?><p><?php echo esc_html( $day ); ?></p><?php endforeach; ?>
					</div>
				</div>
				<dl class="iwl__fact-grid">
					<div><dt><?php echo esc_html( $en ? 'Price' : 'Κόστος' ); ?></dt><dd><?php echo esc_html( $price ); ?><small><?php echo esc_html( $en ? 'per meeting, VAT excluded' : 'ανά συνάντηση, χωρίς ΦΠΑ' ); ?></small></dd></div>
					<div><dt><?php echo esc_html( $en ? 'Group' : 'Ομάδα' ); ?></dt><dd><?php echo esc_html( $programme['capacity'] ); ?><small><?php echo esc_html( $en ? 'places per session' : 'θέσεις ανά συνάντηση' ); ?></small></dd></div>
					<div><dt><?php echo esc_html( $en ? 'Level' : 'Επίπεδο' ); ?></dt><dd><?php echo esc_html( $en ? 'All' : 'Όλα' ); ?><small><?php echo esc_html( $en ? 'beginners are welcome' : 'οι αρχάριοι είναι ευπρόσδεκτοι' ); ?></small></dd></div>
					<div><dt><?php echo esc_html( $en ? 'Included' : 'Περιλαμβάνονται' ); ?></dt><dd><?php echo esc_html( $en ? 'Materials' : 'Υλικά' ); ?><small><?php echo esc_html( $en ? 'tools and firings' : 'εργαλεία και ψησίματα' ); ?></small></dd></div>
				</dl>
			</section>

			<section class="iwl__cta">
				<div><p class="iwl__eyebrow"><?php echo esc_html( $en ? 'Ready when you are' : 'Όποτε είσαι έτοιμος' ); ?></p><h2><?php echo esc_html( $en ? 'Come and get your hands in clay.' : 'Έλα να βάλουμε τα χέρια στον πηλό.' ); ?></h2></div>
				<div class="iwl__cta-actions"><a class="ioulia-btn ioulia-btn--filled iwl__button" href="<?php echo esc_url( $booking ); ?>"><?php echo esc_html( $en ? 'View available dates' : 'Δες διαθέσιμες ημερομηνίες' ); ?></a><a class="iwl__text-link" href="<?php echo esc_url( $all ); ?>"><?php echo esc_html( $en ? 'All workshops' : 'Όλα τα workshops' ); ?></a></div>
			</section>
		</main>
		<?php
		return ob_get_clean();
	}

	add_shortcode( 'ioulia_workshop_landing', 'ioulia_workshop_landing_render' );
}

if ( ! function_exists( 'ioulia_workshop_landing_ensure_pages' ) ) {
	function ioulia_workshop_landing_ensure_pages() {
		if ( ! current_user_can( 'manage_options' ) || get_option( 'ioulia_workshop_landing_pages_created' ) ) {
			return;
		}

		$titles = array(
			'mathimata-piloplastikis-athina' => 'Μαθήματα Πηλοπλαστικής στην Αθήνα',
			'mathimata-troxou-athina'        => 'Μαθήματα Κεραμικού Τροχού στην Αθήνα',
			'keramiki-gia-paidia-athina'     => 'Κεραμική για Παιδιά στην Αθήνα',
			'keramiki-goneas-paidi-athina'   => 'Κεραμική για Γονείς και Παιδιά στην Αθήνα',
			'zografiki-keramikou-athina'     => 'Ζωγραφική Κεραμικού στην Αθήνα',
		);

		foreach ( $titles as $page_slug => $title ) {
			if ( get_page_by_path( $page_slug, OBJECT, 'page' ) ) {
				continue;
			}

			wp_insert_post(
				array(
					'post_type'      => 'page',
					'post_name'      => $page_slug,
					'post_title'     => $title,
					'post_status'    => 'publish',
					'post_content'   => '',
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				)
			);
		}

		update_option( 'ioulia_workshop_landing_pages_created', 1, false );
	}

	add_action( 'admin_init', 'ioulia_workshop_landing_ensure_pages' );
}
