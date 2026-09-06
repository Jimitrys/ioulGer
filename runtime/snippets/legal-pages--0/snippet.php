<?php
/**
 * Legal pages — privacy, terms, shipping and returns, cookies.
 *
 * The footer has always linked to /privacy-policy/ and /data-protection/ and
 * neither page existed: both were 404s. This snippet owns the four documents
 * the shop actually needs, in Greek and in English.
 *
 * Three things live here rather than in a canvas:
 *
 *   1. The WordPress pages themselves. Site Studio's importer creates canvases,
 *      templates and snippets - never pages - so a canvas can never bring its
 *      own URL with it. These are created once, on an admin request, and are
 *      never recreated or overwritten afterwards; delete one in WordPress and
 *      it stays deleted.
 *
 *   2. The copy, in both languages. A legal document is too long to translate
 *      string by string through the i18n seed, and it is the one kind of page
 *      where a half-translated sentence is a real problem. Each document is
 *      written twice and the right one is chosen by ioulia_lang().
 *
 *   3. The shortcode the template calls. One template covers all four pages and
 *      asks this for the document belonging to the page being viewed.
 *
 * The documents follow the site's real checkout, booking and contact flows.
 * The legal business name, VAT number, tax office and GEMI remain deliberately
 * blank until the owner supplies them; inventing trader details would be worse
 * than making the missing compliance item explicit in the repository.
 *
 * No backslashes anywhere in this file: Site Studio unslashes snippet code on
 * import. See CONVENTIONS.md.
 */

if ( ! function_exists( 'ioulia_legal_details' ) ) {
	/**
	 * Who is trading. Everything empty here is simply left off the page rather
	 * than printed as a gap, so an unfinished field is invisible to a visitor
	 * and obvious to whoever edits this file.
	 */
	function ioulia_legal_details() {
		return apply_filters(
			'ioulia_legal_details',
			array(
				'trading_name' => 'Ioulia Geraskli Ceramic Lab',
				'legal_name'   => '',
				'vat'          => '',
				'tax_office'   => '',
				'registry'     => '',
				'address'      => 'Προμπονά 42, 111 43 Αθήνα, Ελλάδα',
				'address_en'   => '42 Prompona Street, 111 43 Athens, Greece',
				'area'         => 'Άνω Πατήσια, Αθήνα',
				'email'        => 'info@iouliageraskliceramics.com',
				'phone'        => '+30 210 2514658',
			)
		);
	}
}

if ( ! function_exists( 'ioulia_legal_slugs' ) ) {
	function ioulia_legal_slugs() {
		return array( 'privacy-policy', 'terms', 'shipping-returns', 'cookies' );
	}
}

if ( ! function_exists( 'ioulia_legal_updated' ) ) {
	function ioulia_legal_updated( $lang ) {
		return 'en' === $lang ? 'Last updated: September 2026' : 'Τελευταία ενημέρωση: Σεπτέμβριος 2026';
	}
}

if ( ! function_exists( 'ioulia_legal_identity_lines' ) ) {
	/**
	 * The trader's identification block, in the order a Greek e-shop is expected
	 * to state it. Missing pieces are dropped, not padded.
	 */
	function ioulia_legal_identity_lines( $lang ) {
		$d     = ioulia_legal_details();
		$en    = 'en' === $lang;
		$lines = array();

		$lines[] = ( $en ? 'Trading name: ' : 'Εμπορική ονομασία: ' ) . $d['trading_name'];

		if ( '' !== $d['legal_name'] ) {
			$lines[] = ( $en ? 'Legal or business name: ' : 'Νόμιμη επωνυμία: ' ) . $d['legal_name'];
		}
		if ( '' !== $d['vat'] ) {
			$lines[] = ( $en ? 'VAT number: ' : 'ΑΦΜ: ' ) . $d['vat'];
		}
		if ( '' !== $d['tax_office'] ) {
			$lines[] = ( $en ? 'Tax office: ' : 'ΔΟΥ: ' ) . $d['tax_office'];
		}
		if ( '' !== $d['registry'] ) {
			$lines[] = ( $en ? 'Companies register (GEMI): ' : 'ΓΕΜΗ: ' ) . $d['registry'];
		}
		if ( '' !== $d['address'] ) {
			$lines[] = ( $en ? 'Address: ' . $d['address_en'] : 'Έδρα: ' . $d['address'] );
		} elseif ( '' !== $d['area'] ) {
			$lines[] = ( $en ? 'Studio: ' : 'Εργαστήριο: ' ) . $d['area'];
		}
		if ( '' !== $d['phone'] ) {
			$lines[] = ( $en ? 'Telephone: ' : 'Τηλέφωνο: ' ) . $d['phone'];
		}

		$lines[] = ( $en ? 'Email: ' : 'Email: ' ) . $d['email'];

		return $lines;
	}
}

if ( ! function_exists( 'ioulia_legal_documents_el' ) ) {
	function ioulia_legal_documents_el() {
		$d = ioulia_legal_details();

		return array(
			'privacy-policy' => array(
				'title' => 'Πολιτική Απορρήτου',
				'lede'  => 'Χρησιμοποιούμε μόνο τα προσωπικά δεδομένα που χρειάζονται για τις παραγγελίες, τις κρατήσεις και την επικοινωνία με το εργαστήριο. Εδώ εξηγούμε καθαρά τι συλλέγουμε, γιατί και για πόσο.',
				'sections' => array(
					array(
						'h'  => 'Υπεύθυνος επεξεργασίας',
						'p'  => array( 'Υπεύθυνος επεξεργασίας για το iouliageraskliceramics.com είναι το ' . $d['trading_name'] . '.' ),
						'ul' => ioulia_legal_identity_lines( 'el' ),
					),
					array(
						'h' => 'Δεδομένα που συλλέγουμε',
						'p' => array( 'Τα δεδομένα προέρχονται κυρίως από εσένα. Ορισμένα τεχνικά δεδομένα δημιουργούνται αυτόματα όταν χρησιμοποιείς τον ιστότοπο.' ),
						'ul' => array(
							'Παραγγελίες και προαιρετικός λογαριασμός: όνομα, στοιχεία επικοινωνίας, διευθύνσεις χρέωσης και αποστολής, περιεχόμενο και αξία παραγγελίας, ιστορικό, σημειώσεις και στοιχεία λογαριασμού.',
							'Πληρωμές: αναγνωριστικό, τρόπος, κατάσταση συναλλαγής και, όπου εμφανίζεται, τα τελευταία ψηφία της κάρτας. Δεν αποθηκεύουμε τον πλήρη αριθμό κάρτας ή τον κωδικό ασφαλείας.',
							'Κρατήσεις workshop: ονοματεπώνυμο, email, τηλέφωνο, πρόγραμμα, ημερομηνία, ώρα, αριθμός συμμετεχόντων, προαιρετική σημείωση και καταγραφή της συγκατάθεσης.',
							'Επικοινωνία ή αίτημα custom έργου: όνομα, email, προαιρετικό τηλέφωνο, επιλογές έργου και μήνυμα.',
							'Τεχνικά και ασφάλεια: IP, χρόνος αιτήματος, browser, συσκευή, σελίδα αναφοράς και συμβάντα ασφαλείας στα αναγκαία αρχεία καταγραφής.',
							'Cookies και παρόμοιες τεχνολογίες, όπως περιγράφονται στην Πολιτική Cookies.',
						),
					),
					array(
						'h' => 'Σκοποί και νομικές βάσεις',
						'ul' => array(
							'Εκτέλεση σύμβασης ή ενέργειες πριν από αυτή: καλάθι, παραγγελία, πληρωμή, παράδοση, λογαριασμός, κράτηση workshop και εξυπηρέτηση.',
							'Νομική υποχρέωση: έκδοση και διατήρηση φορολογικών στοιχείων, υποχρεώσεις καταναλωτή και ανταπόκριση σε νόμιμα αιτήματα αρχών.',
							'Έννομο συμφέρον: απάντηση σε μηνύματα, πρόληψη απάτης, ασφάλεια και τεχνική λειτουργία, καθώς και υπεράσπιση νομικών αξιώσεων, αφού σταθμίσουμε τα δικαιώματά σου.',
							'Συγκατάθεση: μόνο για μη αναγκαία cookies ή προαιρετική εμπορική επικοινωνία, αν ενεργοποιηθεί. Ανακαλείται οποτεδήποτε.',
						),
					),
					array(
						'h' => 'Τι είναι υποχρεωτικό',
						'p' => array( 'Τα πεδία που σημειώνονται ως υποχρεωτικά χρειάζονται για να ολοκληρωθεί το αντίστοιχο αίτημα. Χωρίς στοιχεία παράδοσης δεν μπορούμε να στείλουμε παραγγελία και χωρίς στοιχεία επικοινωνίας και επιλογή συνεδρίας δεν μπορούμε να κρατήσουμε θέση. Ο λογαριασμός πελάτη είναι προαιρετικός.' ),
					),
					array(
						'h' => 'Χρόνος διατήρησης',
						'ul' => array(
							'Παραγγελίες, παραστατικά και συναφή στοιχεία: τουλάχιστον πέντε έτη από τη λήξη της αντίστοιχης περιόδου ή περισσότερο αν το απαιτεί άλλη νομοθεσία ή εκκρεμής αξίωση.',
							'Κρατήσεις workshop: διαγράφονται αυτόματα δώδεκα μήνες μετά τη συνεδρία, εκτός από στοιχεία που πρέπει να μείνουν σε λογιστικό παραστατικό.',
							'Μηνύματα επικοινωνίας: δεν αποθηκεύονται στη βάση του site. Αποστέλλονται στο email του εργαστηρίου και διατηρούνται όσο χρειάζεται για την απάντηση, τη συνεργασία ή μια σχετική αξίωση.',
							'Λογαριασμός πελάτη: όσο παραμένει ενεργός ή μέχρι να ζητήσεις διαγραφή, με εξαίρεση στοιχεία παραγγελιών που πρέπει νόμιμα να διατηρηθούν.',
							'Τεχνικά αρχεία και cookies: για περιορισμένο χρόνο ανάλογα με τον σκοπό και τη διάρκεια που εμφανίζεται στις ρυθμίσεις cookies.',
						),
					),
					array(
						'h' => 'Αποδέκτες και συνεργάτες',
						'p' => array( 'Δεν πουλάμε προσωπικά δεδομένα. Πρόσβαση παίρνουν μόνο, στον βαθμό που χρειάζεται, η φιλοξενία και τεχνική υποστήριξη, το WooCommerce και το WooPayments για κατάστημα και πληρωμές, οι πάροχοι email, η εταιρεία ταχυμεταφορών, ο λογιστής και οι δημόσιες αρχές όταν υπάρχει νόμιμη υποχρέωση. Οι συνεργάτες δεσμεύονται από σύμβαση ή τη δική τους νόμιμη ιδιότητα.' ),
					),
					array(
						'h' => 'Διαβιβάσεις εκτός ΕΟΧ',
						'p' => array( 'Ορισμένοι τεχνολογικοί πάροχοι μπορεί να επεξεργάζονται δεδομένα εκτός Ευρωπαϊκού Οικονομικού Χώρου. Όταν συμβαίνει, χρησιμοποιείται ισχύουσα απόφαση επάρκειας ή κατάλληλη εγγύηση, όπως οι Τυποποιημένες Συμβατικές Ρήτρες της Ευρωπαϊκής Επιτροπής. Μπορείς να ζητήσεις πληροφορίες ή αντίγραφο της σχετικής εγγύησης στο ' . $d['email'] . '.' ),
					),
					array(
						'h' => 'Τα δικαιώματά σου',
						'p' => array( 'Ανάλογα με την επεξεργασία, έχεις δικαίωμα ενημέρωσης, πρόσβασης, διόρθωσης, διαγραφής, περιορισμού, φορητότητας και εναντίωσης. Μπορείς επίσης να ανακαλέσεις συγκατάθεση χωρίς να επηρεάζεται η προηγούμενη νόμιμη επεξεργασία. Δεν χρησιμοποιούμε αποκλειστικά αυτοματοποιημένες αποφάσεις ή profiling με έννομα αποτελέσματα.' ),
						'p2' => array( 'Στείλε αίτημα στο ' . $d['email'] . '. Απαντάμε χωρίς χρέωση, συνήθως μέσα σε έναν μήνα, και μπορεί να ζητήσουμε εύλογη επιβεβαίωση ταυτότητας. Μπορείς επίσης να απευθυνθείς στην Αρχή Προστασίας Δεδομένων Προσωπικού Χαρακτήρα: https://www.dpa.gr/.' ),
					),
					array(
						'h' => 'Παιδιά και ασφάλεια',
						'p' => array( 'Οι online παραγγελίες και οι κρατήσεις παιδικών μαθημάτων πρέπει να γίνονται από γονέα ή νόμιμο κηδεμόνα. Δεν ζητάμε από παιδιά να μας στείλουν προσωπικά δεδομένα. Χρησιμοποιούμε κρυπτογράφηση, περιορισμένη πρόσβαση, ενημερώσεις και αντίγραφα ασφαλείας, χωρίς κανένα σύστημα να μπορεί να θεωρηθεί απολύτως ασφαλές.' ),
					),
					array(
						'h' => 'Αλλαγές στην πολιτική',
						'p' => array( 'Αν αλλάξουν ουσιαστικά οι υπηρεσίες ή ο τρόπος επεξεργασίας, θα ενημερώσουμε αυτή τη σελίδα και την ημερομηνία της. Όπου απαιτείται νέα συγκατάθεση, θα τη ζητήσουμε ξανά.' ),
					),
				),
			),

			'terms' => array(
				'title' => 'Όροι Χρήσης και Πώλησης',
				'lede'  => 'Οι όροι για τη χρήση του site, την αγορά χειροποίητων κεραμικών και την κράτηση workshop. Ισχύει η έκδοση που είναι δημοσιευμένη όταν ολοκληρώνεις την παραγγελία ή την κράτηση.',
				'sections' => array(
					array( 'h' => 'Στοιχεία επιχείρησης', 'ul' => ioulia_legal_identity_lines( 'el' ) ),
					array(
						'h' => 'Προϊόντα και χειροποίητος χαρακτήρας',
						'p' => array( 'Περιγράφουμε τα βασικά χαρακτηριστικά, τις διαστάσεις και τη χρήση κάθε προϊόντος όσο πιο πιστά γίνεται. Επειδή κάθε κεραμικό κατασκευάζεται και υαλώνεται στο χέρι, μικρές διαφορές σε σχήμα, μέγεθος, απόχρωση, πινελιά και υφή είναι μέρος του χαρακτήρα του και όχι ελάττωμα, εφόσον δεν αναιρούν όσα συμφωνήθηκαν. Οι οθόνες μπορεί επίσης να αποδίδουν διαφορετικά τα χρώματα.' ),
					),
					array(
						'h' => 'Παραγγελία και σύμβαση',
						'p' => array( 'Πριν από την πληρωμή βλέπεις τα προϊόντα, τις ποσότητες, την τελική τιμή και τα μεταφορικά και μπορείς να διορθώσεις λάθη. Η υποβολή είναι πρόταση αγοράς. Η σύμβαση ολοκληρώνεται με την επιβεβαίωση αποδοχής ή αποστολής. Αν ένα προϊόν δεν είναι διαθέσιμο ή υπάρχει προφανές λάθος τιμής, θα επικοινωνήσουμε μαζί σου και τυχόν ποσό που χρεώθηκε θα επιστραφεί χωρίς αδικαιολόγητη καθυστέρηση.' ),
					),
					array(
						'h' => 'Τιμές και πληρωμή',
						'ul' => array(
							'Οι τιμές προϊόντων είναι σε ευρώ και περιλαμβάνουν τον ισχύοντα ΦΠΑ. Τα μεταφορικά εμφανίζονται χωριστά πριν από την παραγγελία.',
							'Διαθέσιμοι είναι μόνο οι τρόποι πληρωμής που εμφανίζονται στο checkout, σήμερα online πληρωμή μέσω WooPayments ή αντικαταβολή όπου προσφέρεται.',
							'Η online πληρωμή μπορεί να υπόκειται σε έλεγχο ταυτότητας και απάτης από τον πάροχο πληρωμών. Δεν αποθηκεύουμε πλήρη στοιχεία κάρτας.',
						),
					),
					array(
						'h' => 'Αποστολή και παράδοση',
						'p' => array( 'Για τώρα δεχόμαστε αποστολές μόνο σε διευθύνσεις εντός Ελλάδας. Οι διαθέσιμες επιλογές, το κόστος και η εκτίμηση παράδοσης εμφανίζονται στο checkout. Η ευθύνη για το προϊόν παραμένει σε εμάς μέχρι να το παραλάβεις εσύ ή τρίτος που όρισες, εκτός από μεταφορέα που ανέθεσες ανεξάρτητα εσύ.' ),
					),
					array(
						'h' => 'Υπαναχώρηση από αγορά προϊόντος',
						'p' => array( 'Για αγορά από απόσταση μπορείς να υπαναχωρήσεις χωρίς αιτιολογία μέσα σε δεκατέσσερις ημερολογιακές ημέρες από την παραλαβή. Οι οδηγίες, οι επιστροφές χρημάτων και το υπόδειγμα δήλωσης βρίσκονται στη σελίδα Αποστολές και Επιστροφές.' ),
						'p2' => array( 'Η εξαίρεση αφορά μόνο αγαθά που κατασκευάστηκαν σύμφωνα με δικές σου προδιαγραφές ή εξατομικεύτηκαν σαφώς. Το γεγονός ότι ένα έτοιμο προϊόν είναι χειροποίητο ή μοναδικό δεν αρκεί από μόνο του για να χαθεί το δικαίωμα.' ),
					),
					array(
						'h' => 'Νόμιμη εγγύηση συμμόρφωσης',
						'p' => array( 'Για καινούργια προϊόντα ισχύει η ελάχιστη διετής νόμιμη εγγύηση συμμόρφωσης. Καλύπτει ελάττωμα ή ουσιώδη απόκλιση από την περιγραφή που υπήρχε κατά την παράδοση, όχι πτώση, κακή χρήση, θερμικό σοκ, φυσιολογική φθορά ή τις γνωστοποιημένες μικρές χειροποίητες διαφοροποιήσεις. Τα νόμιμα μέσα αποκατάστασης μπορεί να είναι δωρεάν επισκευή ή αντικατάσταση και, όταν συντρέχουν οι προϋποθέσεις, μείωση τιμής ή λύση της σύμβασης. Αυτή η προστασία είναι διαφορετική από τη δεκατετραήμερη υπαναχώρηση.' ),
					),
					array(
						'h' => 'Workshop και ακυρώσεις',
						'ul' => array(
							'Η θέση δεσμεύεται μόλις ολοκληρωθεί η online κράτηση και σταλεί επιβεβαίωση. Η φόρμα κράτησης δεν χρεώνει κάρτα. Η τιμή και ο ΦΠΑ εμφανίζονται πριν από την ολοκλήρωση.',
							'Επειδή το workshop παρέχεται σε συγκεκριμένη ημερομηνία και ώρα, δεν ισχύει το γενικό δεκατετραήμερο δικαίωμα υπαναχώρησης για υπηρεσίες αναψυχής συγκεκριμένης ημερομηνίας.',
							'Παρέχουμε συμβατικά τη δυνατότητα ακύρωσης τουλάχιστον σαράντα οκτώ ώρες πριν, με μεταφορά της θέσης ή επιστροφή τυχόν ποσού που έχει ήδη πληρωθεί. Για αργότερη ακύρωση επικοινώνησε άμεσα και θα προσπαθήσουμε να βοηθήσουμε, χωρίς εγγύηση επιστροφής.',
							'Αν ακυρώσουμε εμείς τη συνεδρία, προσφέρουμε άλλη ημερομηνία ή πλήρη επιστροφή τυχόν πληρωμής.',
						),
					),
					array(
						'h' => 'Ανήλικοι και ασφάλεια workshop',
						'p' => array( 'Κράτηση για παιδί γίνεται από γονέα ή κηδεμόνα, ο οποίος επιβεβαιώνει την κατάλληλη ηλικία και μας ενημερώνει εγκαίρως για αλλεργία, ευαισθησία ή άλλη ανάγκη που επηρεάζει την ασφαλή συμμετοχή. Όλοι οι συμμετέχοντες οφείλουν να ακολουθούν τις οδηγίες για πηλό, εργαλεία, υαλώματα και εξοπλισμό. Η είσοδος σε μη επιτρεπόμενους χώρους και η χρήση κλιβάνου χωρίς επίβλεψη απαγορεύονται.' ),
					),
					array(
						'h' => 'Πνευματική ιδιοκτησία και χρήση site',
						'p' => array( 'Τα πρωτότυπα σχέδια, φωτογραφίες, κείμενα και η εικαστική ταυτότητα ανήκουν στην Ιουλία Γεράσκλη ή χρησιμοποιούνται με άδεια. Επιτρέπεται προσωπική προβολή και κοινοποίηση με σαφή αναφορά. Δεν επιτρέπεται εμπορική αντιγραφή, αναπαραγωγή σχεδίων, αυτοματοποιημένη συλλογή ή παρεμβολή στη λειτουργία του site χωρίς γραπτή άδεια.' ),
					),
					array(
						'h' => 'Ευθύνη, παράπονα και δίκαιο',
						'p' => array( 'Δεν αποκλείουμε δικαιώματα ή ευθύνη που δεν επιτρέπεται να αποκλειστεί από τον νόμο. Για ερώτηση ή παράπονο γράψε πρώτα στο ' . $d['email'] . '. Εφαρμόζεται το ελληνικό δίκαιο, χωρίς να στερείται ο καταναλωτής αναγκαστική προστασία που του παρέχει ο νόμος. Αν δεν λυθεί η διαφορά, μπορείς να απευθυνθείς στον Συνήγορο του Καταναλωτή: https://www.synigoroskatanaloti.gr/. Η παλιά ευρωπαϊκή πλατφόρμα ODR έχει καταργηθεί από τις 20 Ιουλίου 2025.' ),
					),
				),
			),

			'shipping-returns' => array(
				'title' => 'Αποστολές και Επιστροφές',
				'lede'  => 'Στέλνουμε προσεκτικά συσκευασμένα κεραμικά μόνο εντός Ελλάδας. Παρακάτω θα βρεις τον χρόνο, το κόστος, το δικαίωμα υπαναχώρησης και τι κάνουμε αν κάτι φτάσει λάθος ή σπασμένο.',
				'sections' => array(
					array(
						'h' => 'Περιοχή και χρόνος αποστολής',
						'p' => array( 'Το ηλεκτρονικό κατάστημα δέχεται προς το παρόν αποστολές μόνο σε διευθύνσεις εντός Ελλάδας. Οι ενεργές μέθοδοι checkout δίνουν εκτιμώμενο χρόνο επτά έως δέκα εργάσιμες ημέρες. Για έτοιμα προϊόντα η προετοιμασία γίνεται συνήθως μέσα σε δύο έως τρεις εργάσιμες, ενώ για custom παραγωγή ισχύει ο ειδικός χρόνος που συμφωνείται πριν από την παραγγελία. Οι χρόνοι είναι εκτιμήσεις και μπορεί να επηρεαστούν από αργίες ή έκτακτα γεγονότα.' ),
					),
					array(
						'h' => 'Κόστος, παράδοση και ευθύνη',
						'p' => array( 'Το ακριβές κόστος ή η τυχόν δωρεάν αποστολή εμφανίζεται πριν από την τελική υποβολή. Έλεγξε προσεκτικά τη διεύθυνση και ένα τηλέφωνο επικοινωνίας. Αν η παράδοση καθυστερεί ουσιωδώς, επικοινώνησε μαζί μας. Φέρουμε τον κίνδυνο απώλειας ή ζημιάς μέχρι να παραλάβεις εσύ ή το πρόσωπο που όρισες.' ),
					),
					array(
						'h' => 'Συσκευασία και ζημιά στη μεταφορά',
						'p' => array( 'Συσκευάζουμε τα εύθραυστα κεραμικά ώστε να προστατεύονται κατά τη μεταφορά. Αν ένα προϊόν φτάσει σπασμένο, ελαττωματικό ή διαφορετικό από την παραγγελία, κράτησε το προϊόν και τη συσκευασία και στείλε φωτογραφίες στο ' . $d['email'] . '. Η ενημέρωση μέσα σε σαράντα οκτώ ώρες βοηθά να γίνει γρήγορα η αναφορά στον μεταφορέα, αλλά δεν περιορίζει τη νόμιμη εγγύηση ή τα δικαιώματά σου.' ),
					),
					array(
						'h' => 'Δικαίωμα υπαναχώρησης',
						'p' => array( 'Για ένα έτοιμο προϊόν που αγοράστηκε online μπορείς να μας δηλώσεις ότι υπαναχωρείς, χωρίς αιτιολογία, μέσα σε δεκατέσσερις ημερολογιακές ημέρες από την ημέρα που το παρέλαβες εσύ ή τρίτος που όρισες. Αν μία παραγγελία παραδίδεται τμηματικά, η προθεσμία αρχίζει με το τελευταίο προϊόν.' ),
						'ul' => array(
							'Στείλε σαφή δήλωση στο ' . $d['email'] . ' πριν λήξει η προθεσμία. Δεν χρειάζεται ειδική διατύπωση ή έγκριση.',
							'Στείλε το προϊόν πίσω χωρίς αδικαιολόγητη καθυστέρηση και το αργότερο μέσα σε δεκατέσσερις ημέρες από τη δήλωση, στη διεύθυνση Προμπονά 42, 111 43 Αθήνα, Ελλάδα.',
							'Σε υπαναχώρηση λόγω αλλαγής γνώμης αναλαμβάνεις το άμεσο κόστος επιστροφής. Συσκεύασέ το όπως απαιτεί ένα εύθραυστο αντικείμενο.',
							'Ευθύνεσαι μόνο για μείωση αξίας από χειρισμό πέρα από αυτόν που χρειάζεται για να διαπιστώσεις τη φύση, τα χαρακτηριστικά και τη λειτουργία του προϊόντος.',
						),
					),
					array(
						'h' => 'Επιστροφή χρημάτων',
						'p' => array( 'Επιστρέφουμε χωρίς αδικαιολόγητη καθυστέρηση και το αργότερο μέσα σε δεκατέσσερις ημέρες από τότε που ενημερωθήκαμε για την υπαναχώρηση όλες τις πληρωμές που λάβαμε, μαζί με το κόστος της βασικής διαθέσιμης αποστολής. Η επιστροφή γίνεται με το ίδιο μέσο πληρωμής, χωρίς χρέωση, εκτός αν συμφωνήσεις ρητά αλλιώς. Μπορούμε να περιμένουμε μέχρι να παραλάβουμε το προϊόν ή μέχρι να μας στείλεις απόδειξη αποστολής, όποιο συμβεί πρώτο. Πρόσθετο κόστος ακριβότερου τρόπου παράδοσης που επέλεξες δεν επιστρέφεται.' ),
					),
					array(
						'h' => 'Εξαιρέσεις',
						'p' => array( 'Δεν υπάρχει δικαίωμα υπαναχώρησης για προϊόν που κατασκευάστηκε σύμφωνα με δικές σου προδιαγραφές ή εξατομικεύτηκε σαφώς. Η εξαίρεση δεν εφαρμόζεται αυτόματα σε κάθε χειροποίητο ή μοναδικό έτοιμο κεραμικό. Δεν αφορά επίσης προϊόν που απλώς δοκιμάστηκε προσεκτικά, αλλά μπορεί να υπάρξει μείωση επιστροφής αν χρησιμοποιήθηκε ή υπέστη ζημιά μετά την παράδοση.' ),
					),
					array(
						'h' => 'Ελαττωματικό ή μη σύμφωνο προϊόν',
						'p' => array( 'Η υπαναχώρηση λόγω αλλαγής γνώμης είναι ανεξάρτητη από τη διετή νόμιμη εγγύηση συμμόρφωσης. Αν υπάρχει ελάττωμα ή το προϊόν δεν συμφωνεί ουσιωδώς με την περιγραφή, επικοινώνησε μαζί μας. Η επισκευή ή αντικατάσταση γίνεται χωρίς κόστος και, όταν αυτές δεν είναι δυνατές ή κατάλληλες σύμφωνα με τον νόμο, μπορεί να δικαιούσαι μείωση τιμής ή λύση της σύμβασης. Τα εύλογα έξοδα επιστροφής μη σύμφωνου προϊόντος βαρύνουν εμάς.' ),
					),
					array(
						'h' => 'Υπόδειγμα δήλωσης υπαναχώρησης',
						'p' => array( 'Προς ' . $d['trading_name'] . ', Προμπονά 42, 111 43 Αθήνα, ' . $d['email'] . ': Σας γνωστοποιώ ότι υπαναχωρώ από τη σύμβαση πώλησης των ακόλουθων αγαθών: [περιγραφή]. Αριθμός παραγγελίας: [αριθμός]. Ημερομηνία παραγγελίας και παραλαβής: [ημερομηνίες]. Ονοματεπώνυμο και διεύθυνση καταναλωτή: [στοιχεία]. Ημερομηνία: [ημερομηνία]. Υπογραφή απαιτείται μόνο αν σταλεί σε χαρτί.' ),
					),
					array(
						'h' => 'Φροντίδα',
						'p' => array( 'Ακολούθησε τις ειδικές οδηγίες του προϊόντος. Απόφυγε απότομες αλλαγές θερμοκρασίας και μην τοποθετείς κεραμικά με χρυσή ή μεταλλική λεπτομέρεια σε φούρνο μικροκυμάτων. Αν δεν αναφέρεται ρητά ότι ένα κομμάτι είναι κατάλληλο για πλυντήριο πιάτων ή τρόφιμα, ρώτησέ μας πριν από τη χρήση.' ),
					),
				),
			),

			'cookies' => array(
				'title' => 'Πολιτική Cookies',
				'lede'  => 'Ο ιστότοπος χρησιμοποιεί τα ελάχιστα αναγκαία cookies για να λειτουργούν το καλάθι, το checkout, η ασφάλεια και οι επιλογές συγκατάθεσης. Τα προαιρετικά cookies δεν ενεργοποιούνται χωρίς επιλογή σου.',
				'sections' => array(
					array(
						'h' => 'Τι είναι και ποιος είναι υπεύθυνος',
						'p' => array( 'Cookies είναι μικρά αρχεία ή αναγνωριστικά που αποθηκεύονται στη συσκευή σου. Υπεύθυνος για τη χρήση τους στο iouliageraskliceramics.com είναι το ' . $d['trading_name'] . '. Η χρήση τους διέπεται από τον GDPR και τον ν. 3471/2006.' ),
					),
					array(
						'h' => 'Απολύτως αναγκαία cookies',
						'p' => array( 'Δεν απαιτούν συγκατάθεση μόνο όταν είναι αναγκαία για υπηρεσία που ζήτησες ή για τη μετάδοση της επικοινωνίας.' ),
						'ul' => array(
							'WooCommerce: προσωρινό καλάθι, ποσότητες, σύνοδος πελάτη και λειτουργία checkout.',
							'WooPayments και ασφάλεια: ολοκλήρωση πληρωμής, αυθεντικοποίηση και πρόληψη απάτης μόνο όταν χρησιμοποιείται η πληρωμή.',
							'Επιλογές cookies: αποθήκευση της συγκατάθεσης ή απόρριψης, ώστε να θυμόμαστε την επιλογή σου.',
							'WordPress και διαχείριση: είσοδος και προστασία συνεδρίας μόνο για συνδεδεμένους χρήστες ή προσωπικό.',
						),
					),
					array(
						'h' => 'Προαιρετικά cookies',
						'p' => array( 'Δεν χρησιμοποιούμε σκόπιμα cookies διαφήμισης ή profiling αυτή τη στιγμή. Αν ενεργοποιηθούν λειτουργικά, στατιστικά ή marketing εργαλεία στο μέλλον, θα εμφανίζονται ονομαστικά στις ρυθμίσεις και θα φορτώνονται μόνο μετά από ελεύθερη, συγκεκριμένη επιλογή. Η απόρριψη δεν εμποδίζει την αγορά ή την κράτηση.' ),
					),
					array(
						'h' => 'Διάρκεια και τρίτοι πάροχοι',
						'p' => array( 'Κάποια cookies λήγουν με το κλείσιμο του browser και άλλα διατηρούνται για τη διάρκεια που αναγράφεται στον αναλυτικό πίνακα του εργαλείου συγκατάθεσης. Εκεί εμφανίζονται επίσης ο πάροχος και ο σκοπός κάθε ενεργού cookie. Εξωτερικό περιεχόμενο δεν πρέπει να φορτώνει μη αναγκαία cookies πριν από τη συγκατάθεση.' ),
					),
					array(
						'h' => 'Διαχείριση συγκατάθεσης',
						'p' => array( 'Μπορείς να αποδεχτείς ή να απορρίψεις τις προαιρετικές κατηγορίες με ισότιμο τρόπο και να αλλάξεις γνώμη οποτεδήποτε από το εικονίδιο ρυθμίσεων στην κάτω γωνία. Η ανάκληση ισχύει για το μέλλον. Μπορείς επίσης να διαγράψεις cookies από τον browser, αλλά το καλάθι ή άλλες βασικές λειτουργίες μπορεί να χαθούν.' ),
					),
					array(
						'h' => 'Ερωτήσεις',
						'p' => array( 'Για ερώτηση σχετικά με cookies ή προσωπικά δεδομένα γράψε στο ' . $d['email'] . '. Για τα υπόλοιπα δικαιώματα και τη δυνατότητα καταγγελίας δες την Πολιτική Απορρήτου.' ),
					),
				),
			),
		);
	}
}

if ( ! function_exists( 'ioulia_legal_documents_en' ) ) {
	function ioulia_legal_documents_en() {
		$d = ioulia_legal_details();

		return array(
			'privacy-policy' => array(
				'title' => 'Privacy Policy',
				'lede'  => 'We use only the personal data needed to handle orders, workshop bookings and conversations with the studio. This page explains clearly what we collect, why we use it and how long we keep it.',
				'sections' => array(
					array( 'h' => 'Data controller', 'p' => array( 'The controller for iouliageraskliceramics.com is ' . $d['trading_name'] . '.' ), 'ul' => ioulia_legal_identity_lines( 'en' ) ),
					array(
						'h' => 'Data we collect',
						'p' => array( 'Most data comes directly from you. Some technical data is created automatically when you use the site.' ),
						'ul' => array(
							'Orders and optional accounts: name, contact details, billing and delivery addresses, order contents and value, history, notes and account details.',
							'Payments: transaction identifier, method and status and, where shown, the last digits of the card. We do not store the full card number or security code.',
							'Workshop bookings: name, email, telephone, programme, date, time, participant count, optional note and a record of consent.',
							'Contact or custom commission request: name, email, optional telephone, project choices and message.',
							'Technical and security data: IP address, request time, browser, device, referring page and security events in necessary logs.',
							'Cookies and similar technologies, as described in the Cookie Policy.',
						),
					),
					array(
						'h' => 'Purposes and legal bases',
						'ul' => array(
							'Contract or steps you request before a contract: cart, order, payment, delivery, account, workshop booking and customer care.',
							'Legal obligation: invoices and tax records, consumer-law duties and lawful requests from authorities.',
							'Legitimate interests: answering messages, fraud prevention, security and technical operation, and establishing or defending legal claims, after balancing your rights.',
							'Consent: only for non-essential cookies or optional marketing communications, if introduced. You may withdraw it at any time.',
						),
					),
					array( 'h' => 'What is required', 'p' => array( 'Fields marked as required are needed to complete the relevant request. We cannot ship without delivery details or reserve a class without contact and session details. Creating a customer account is optional.' ) ),
					array(
						'h' => 'Retention',
						'ul' => array(
							'Orders, invoices and related records: at least five years from the end of the relevant accounting period, or longer where another law or a pending claim requires it.',
							'Workshop bookings: automatically deleted twelve months after the session, except for information that must remain in an accounting record.',
							'Contact messages: not stored in the site database. They are delivered to the studio mailbox and kept for as long as needed to answer, work together or handle a related claim.',
							'Customer accounts: while active or until deletion is requested, except for order data we must retain by law.',
							'Technical logs and cookies: for a limited period appropriate to their purpose and the lifetime shown in cookie settings.',
						),
					),
					array( 'h' => 'Recipients and service providers', 'p' => array( 'We do not sell personal data. Access is limited, as needed, to hosting and technical support, WooCommerce and WooPayments for the store and payments, email providers, the courier, our accountant and public authorities where required by law. Providers act under contract or their own lawful role.' ) ),
					array( 'h' => 'Transfers outside the EEA', 'p' => array( 'Some technology providers may process data outside the European Economic Area. Where this occurs, we use a valid adequacy decision or appropriate safeguards such as the European Commission Standard Contractual Clauses. You may ask for information or a copy of the relevant safeguard at ' . $d['email'] . '.' ) ),
					array(
						'h' => 'Your rights',
						'p' => array( 'Depending on the processing, you have rights to information, access, rectification, erasure, restriction, portability and objection. You may withdraw consent without affecting earlier lawful processing. We do not make solely automated decisions or use profiling that produces legal or similarly significant effects.' ),
						'p2' => array( 'Email ' . $d['email'] . '. We respond free of charge, normally within one month, and may reasonably verify your identity. You may also complain to the Hellenic Data Protection Authority: https://www.dpa.gr/.' ),
					),
					array( 'h' => 'Children and security', 'p' => array( 'Online orders and bookings for children must be made by a parent or legal guardian. We do not ask children to send us personal data. We use encryption, restricted access, updates and backups, although no system can be completely secure.' ) ),
					array( 'h' => 'Changes to this policy', 'p' => array( 'If our services or processing change materially, we will update this page and its date. Where new consent is required, we will ask again.' ) ),
				),
			),

			'terms' => array(
				'title' => 'Terms of Use and Sale',
				'lede'  => 'The terms for using the site, buying handmade ceramics and booking a workshop. The version published when you complete an order or booking applies.',
				'sections' => array(
					array( 'h' => 'Business details', 'ul' => ioulia_legal_identity_lines( 'en' ) ),
					array( 'h' => 'Products and handmade character', 'p' => array( 'We describe the main features, dimensions and use of each product as accurately as possible. Because every ceramic is made and glazed by hand, small differences in shape, size, shade, brushwork and texture are part of its character rather than a defect, provided they do not contradict what was agreed. Screens can also display colour differently.' ) ),
					array( 'h' => 'Order and contract', 'p' => array( 'Before paying, you can review and correct products, quantities, final price and delivery. Submitting an order is an offer to buy. The contract is formed when we confirm acceptance or dispatch. If an item is unavailable or a price is obviously wrong, we will contact you and refund any amount charged without undue delay.' ) ),
					array(
						'h' => 'Prices and payment',
						'ul' => array(
							'Product prices are in euro and include applicable VAT. Delivery is shown separately before the order is placed.',
							'Only the payment methods displayed at checkout are available, currently online payment through WooPayments or cash on delivery where offered.',
							'Online payments may be subject to authentication and fraud checks by the payment provider. We do not store full card details.',
						),
					),
					array( 'h' => 'Shipping and delivery', 'p' => array( 'For now we ship only to addresses within Greece. Available methods, cost and estimated delivery are shown at checkout. Risk remains with us until you or a person you nominate takes physical possession, unless you independently appoint a carrier not offered by us.' ) ),
					array(
						'h' => 'Withdrawal from a product purchase',
						'p' => array( 'For a distance purchase, you may withdraw without giving a reason within fourteen calendar days of delivery. Instructions, refunds and a model statement are on the Shipping and Returns page.' ),
						'p2' => array( 'The exception applies only to goods made to your specifications or clearly personalised. Being handmade or a unique ready-made piece does not by itself remove the right.' ),
					),
					array( 'h' => 'Legal guarantee of conformity', 'p' => array( 'New goods carry the minimum two-year legal guarantee of conformity. It covers a defect or material departure from the description that existed on delivery, not a drop, misuse, thermal shock, normal wear or disclosed minor handmade variations. Legal remedies may include free repair or replacement and, where the law permits, a price reduction or termination. This protection is separate from the fourteen-day withdrawal right.' ) ),
					array(
						'h' => 'Workshops and cancellations',
						'ul' => array(
							'Your place is reserved when the online booking is completed and confirmation is sent. The booking form does not charge a card. Price and VAT are shown before completion.',
							'Because a workshop is a leisure service supplied on a specific date and time, the general fourteen-day withdrawal right does not apply.',
							'We contractually allow cancellation at least forty-eight hours before the session, with a move to another date or refund of any amount already paid. For a later cancellation, contact us immediately and we will try to help, without guaranteeing a refund.',
							'If we cancel the session, we will offer another date or a full refund of any payment.',
						),
					),
					array( 'h' => 'Minors and workshop safety', 'p' => array( 'A booking for a child must be made by a parent or guardian, who confirms the suitable age and tells us in good time about an allergy, sensitivity or other need affecting safe participation. Everyone must follow instructions for clay, tools, glazes and equipment. Entering restricted areas or using a kiln without supervision is prohibited.' ) ),
					array( 'h' => 'Intellectual property and site use', 'p' => array( 'Original designs, photography, copy and visual identity belong to Ioulia Geraskli or are used with permission. Personal viewing and sharing with clear credit are welcome. Commercial copying, reproducing designs, automated extraction or interfering with the site is not allowed without written permission.' ) ),
					array( 'h' => 'Liability, complaints and law', 'p' => array( 'We do not exclude rights or liability that cannot lawfully be excluded. For a question or complaint, first email ' . $d['email'] . '. Greek law applies without depriving a consumer of mandatory protection. If we cannot resolve a dispute, you may contact the Greek Consumer Ombudsman: https://www.synigoroskatanaloti.gr/. The former EU ODR platform was discontinued on 20 July 2025.' ) ),
				),
			),

			'shipping-returns' => array(
				'title' => 'Shipping and Returns',
				'lede'  => 'We currently ship carefully packed ceramics within Greece only. Here you can find delivery estimates, costs, withdrawal rights and what happens if something arrives wrong or broken.',
				'sections' => array(
					array( 'h' => 'Where and when we ship', 'p' => array( 'The online store currently ships only to addresses in Greece. Active checkout methods show an estimated seven to ten working days. Ready-made pieces are usually prepared within two to three working days; a custom piece follows the lead time agreed before ordering. Times are estimates and may be affected by public holidays or events outside reasonable control.' ) ),
					array( 'h' => 'Cost, delivery and risk', 'p' => array( 'The exact cost or any free delivery is displayed before the order is submitted. Please check the address and contact telephone carefully. Contact us if delivery is materially delayed. We bear the risk of loss or damage until you or your nominee receives the goods.' ) ),
					array( 'h' => 'Packing and transit damage', 'p' => array( 'We pack fragile ceramics for safe travel. If a piece arrives broken, faulty or different from your order, keep the item and packaging and email photographs to ' . $d['email'] . '. Notice within forty-eight hours helps us make a prompt carrier claim, but it does not reduce your legal guarantee or consumer rights.' ) ),
					array(
						'h' => 'Right of withdrawal',
						'p' => array( 'For a ready-made product bought online, you may tell us that you are withdrawing without giving a reason within fourteen calendar days after you or your nominee receives it. For goods delivered separately, the period begins with the last item.' ),
						'ul' => array(
							'Email an unambiguous statement to ' . $d['email'] . ' before the deadline. No special wording or approval is needed.',
							'Return the item without undue delay and no later than fourteen days after your notice to 42 Prompona Street, 111 43 Athens, Greece.',
							'For a change-of-mind return, you pay the direct return cost. Pack the item as a fragile object requires.',
							'You are responsible only for diminished value caused by handling beyond what is necessary to establish the nature, characteristics and functioning of the item.',
						),
					),
					array( 'h' => 'Refunds', 'p' => array( 'We refund without undue delay and no later than fourteen days after learning of the withdrawal all payments received, including the cost of our least expensive standard delivery. We use the same payment method, without a fee, unless you expressly agree otherwise. We may wait until we receive the goods or evidence that you sent them, whichever is earlier. Any extra cost of a more expensive delivery option you chose is not refunded.' ) ),
					array( 'h' => 'Exceptions', 'p' => array( 'There is no withdrawal right for goods made to your specifications or clearly personalised. The exception does not automatically apply to every handmade or unique ready-made ceramic. Carefully examining an item does not remove the right, although a refund may be reduced if the item has been used or damaged after delivery.' ) ),
					array( 'h' => 'Faulty or non-conforming goods', 'p' => array( 'A change-of-mind withdrawal is separate from the two-year legal guarantee of conformity. If an item is faulty or materially differs from its description, contact us. Repair or replacement is free and, where those remedies are not possible or appropriate under the law, you may be entitled to a price reduction or termination. We bear reasonable return costs for non-conforming goods.' ) ),
					array( 'h' => 'Model withdrawal statement', 'p' => array( 'To ' . $d['trading_name'] . ', 42 Prompona Street, 111 43 Athens, ' . $d['email'] . ': I hereby give notice that I withdraw from my contract for the sale of the following goods: [description]. Order number: [number]. Ordered and received on: [dates]. Consumer name and address: [details]. Date: [date]. A signature is required only if this is sent on paper.' ) ),
					array( 'h' => 'Care', 'p' => array( 'Follow any product-specific care instructions. Avoid sudden temperature changes and never microwave ceramics with gold or metallic detail. If an item is not expressly described as dishwasher-safe or food-safe, ask us before use.' ) ),
				),
			),

			'cookies' => array(
				'title' => 'Cookie Policy',
				'lede'  => 'The site uses the minimum essential cookies needed for the cart, checkout, security and consent choices. Optional cookies are not enabled without your choice.',
				'sections' => array(
					array( 'h' => 'What cookies are and who is responsible', 'p' => array( 'Cookies are small files or identifiers stored on your device. The controller for their use on iouliageraskliceramics.com is ' . $d['trading_name'] . '. Their use is governed by the GDPR and Greek Law 3471/2006.' ) ),
					array(
						'h' => 'Strictly necessary cookies',
						'p' => array( 'These do not require consent only where they are necessary for a service you requested or for transmitting communications.' ),
						'ul' => array(
							'WooCommerce: temporary cart, quantities, customer session and checkout operation.',
							'WooPayments and security: payment completion, authentication and fraud prevention when payment is used.',
							'Cookie choices: keeping your acceptance or rejection so the site remembers it.',
							'WordPress and administration: login and session protection only for signed-in users or staff.',
						),
					),
					array( 'h' => 'Optional cookies', 'p' => array( 'We do not intentionally use advertising or profiling cookies at present. If functional, analytics or marketing tools are introduced, they will be named in settings and loaded only after a free, specific choice. Rejecting them will not prevent shopping or booking.' ) ),
					array( 'h' => 'Duration and third parties', 'p' => array( 'Some cookies expire when the browser closes and others remain for the lifetime shown in the consent tool detailed list. That list also shows each active cookie provider and purpose. Embedded third-party content should not load non-essential cookies before consent.' ) ),
					array( 'h' => 'Managing consent', 'p' => array( 'You can accept or reject optional categories with equally easy controls and change your mind at any time from the settings icon in the lower corner. Withdrawal applies going forward. You can also delete cookies in your browser, but this may remove a cart or other essential state.' ) ),
					array( 'h' => 'Questions', 'p' => array( 'For questions about cookies or personal data, email ' . $d['email'] . '. See the Privacy Policy for your other rights and how to complain.' ) ),
				),
			),
		);
	}
}

if ( ! function_exists( 'ioulia_legal_document' ) ) {
	function ioulia_legal_document( $slug, $lang = null ) {
		$lang      = $lang ? $lang : ( function_exists( 'ioulia_lang' ) ? ioulia_lang() : 'el' );
		$documents = 'en' === $lang ? ioulia_legal_documents_en() : ioulia_legal_documents_el();

		return isset( $documents[ $slug ] ) ? $documents[ $slug ] : null;
	}
}

if ( ! function_exists( 'ioulia_legal_text' ) ) {
	/**
	 * Keep document copy escaped while turning the few written email and web
	 * addresses into useful links. The source strings never contain HTML.
	 */
	function ioulia_legal_text( $text ) {
		return wp_kses_post( make_clickable( esc_html( $text ) ) );
	}
}

if ( ! function_exists( 'ioulia_legal_render' ) ) {
	/**
	 * The shortcode the legal template calls. It takes the document belonging to
	 * the page being viewed, so one template covers all four.
	 */
	function ioulia_legal_render( $atts = array() ) {
		$atts = shortcode_atts( array( 'page' => '' ), (array) $atts, 'ioulia_legal' );

		$slug = sanitize_title( $atts['page'] );
		if ( '' === $slug ) {
			$queried = get_queried_object();
			$slug    = isset( $queried->post_name ) ? (string) $queried->post_name : '';
		}

		$lang     = function_exists( 'ioulia_lang' ) ? ioulia_lang() : 'el';
		$document = ioulia_legal_document( $slug, $lang );
		if ( ! $document ) {
			return '';
		}

		$out  = '<article class="ilegal__doc">';
		$out .= '<p class="ilegal__updated">' . esc_html( ioulia_legal_updated( $lang ) ) . '</p>';
		$out .= '<h1 class="ilegal__title">' . esc_html( $document['title'] ) . '</h1>';

		if ( ! empty( $document['lede'] ) ) {
			$out .= '<p class="ilegal__lede">' . esc_html( $document['lede'] ) . '</p>';
		}

		foreach ( $document['sections'] as $section ) {
			$out .= '<section class="ilegal__section">';
			$out .= '<h2 class="ilegal__heading">' . esc_html( $section['h'] ) . '</h2>';

			foreach ( (array) ( $section['p'] ?? array() ) as $paragraph ) {
				$out .= '<p>' . ioulia_legal_text( $paragraph ) . '</p>';
			}

			if ( ! empty( $section['ul'] ) ) {
				$out .= '<ul class="ilegal__list">';
				foreach ( $section['ul'] as $item ) {
					$out .= '<li>' . ioulia_legal_text( $item ) . '</li>';
				}
				$out .= '</ul>';
			}

			foreach ( (array) ( $section['p2'] ?? array() ) as $paragraph ) {
				$out .= '<p>' . ioulia_legal_text( $paragraph ) . '</p>';
			}

			$out .= '</section>';
		}

		$out .= '</article>';

		return $out;
	}

	add_shortcode( 'ioulia_legal', 'ioulia_legal_render' );
}

if ( ! function_exists( 'ioulia_legal_ensure_pages' ) ) {
	/**
	 * The four pages have to exist in WordPress for their URLs to resolve; the
	 * Site Studio importer only ever creates canvases, templates and snippets.
	 *
	 * Created once, in the admin, and never touched again: the option records
	 * that this has run, so deleting a page in WordPress does not bring it back
	 * on the next request. The body is left empty on purpose - the template
	 * renders the document, and page content is never printed.
	 */
	function ioulia_legal_ensure_pages() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( get_option( 'ioulia_legal_pages_created' ) ) {
			return;
		}

		$titles = array(
			'privacy-policy'   => 'Πολιτική Απορρήτου',
			'terms'            => 'Όροι Χρήσης και Πώλησης',
			'shipping-returns' => 'Αποστολές και Επιστροφές',
			'cookies'          => 'Πολιτική Cookies',
		);

		foreach ( $titles as $slug => $title ) {
			if ( get_page_by_path( $slug, OBJECT, 'page' ) ) {
				continue;
			}

			wp_insert_post(
				array(
					'post_type'      => 'page',
					'post_name'      => $slug,
					'post_title'     => $title,
					'post_status'    => 'publish',
					'post_content'   => '',
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				)
			);
		}

		update_option( 'ioulia_legal_pages_created', 1, false );
	}

	add_action( 'admin_init', 'ioulia_legal_ensure_pages' );
}

if ( ! function_exists( 'ioulia_legal_connect_woocommerce' ) ) {
	/**
	 * WooCommerce was still pointing its checkout notices at legacy pages. Link
	 * the checkout to the canonical documents once, after the pages exist.
	 */
	function ioulia_legal_connect_woocommerce() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( '20260906' === (string) get_option( 'ioulia_legal_connections_version' ) ) {
			return;
		}

		$terms   = get_page_by_path( 'terms', OBJECT, 'page' );
		$privacy = get_page_by_path( 'privacy-policy', OBJECT, 'page' );

		if ( $terms ) {
			update_option( 'woocommerce_terms_page_id', (int) $terms->ID );
		}
		if ( $privacy ) {
			update_option( 'wp_page_for_privacy_policy', (int) $privacy->ID );
		}

		update_option( 'ioulia_legal_connections_version', '20260906', false );
	}

	add_action( 'admin_init', 'ioulia_legal_connect_woocommerce', 20 );
}
