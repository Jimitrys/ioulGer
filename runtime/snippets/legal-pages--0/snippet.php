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
 * THESE ARE DRAFTS. They are honest about how the site actually works, but the
 * identification details below are blank because nobody has given them to me,
 * and a Greek trader has to publish them. Fill in ioulia_legal_details() and
 * have someone check the result before treating any of this as final.
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
				'address'      => '',
				'area'         => 'Άνω Πατήσια, Αθήνα',
				'email'        => 'info@iouliageraskliceramics.com',
				'phone'        => '',
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

		if ( '' !== $d['legal_name'] ) {
			$lines[] = ( $en ? 'Trading as: ' : 'Επωνυμία: ' ) . $d['legal_name'];
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
			$lines[] = ( $en ? 'Address: ' : 'Έδρα: ' ) . $d['address'];
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

			/* ---------------------------------------------------------------
			 * Πολιτική Απορρήτου
			 * ------------------------------------------------------------ */
			'privacy-policy' => array(
				'title' => 'Πολιτική Απορρήτου',
				'lede'  => 'Κρατάμε όσα λιγότερα δεδομένα μπορούμε για να λειτουργήσει το εργαστήριο και το κατάστημα, και τίποτε περισσότερο. Εδώ γράφουμε ποια είναι αυτά, γιατί τα χρειαζόμαστε και τι μπορείς να ζητήσεις.',
				'sections' => array(
					array(
						'h' => 'Ποιοι είμαστε',
						'p' => array( 'Υπεύθυνος επεξεργασίας των δεδομένων σου είναι το ' . $d['trading_name'] . ', που λειτουργεί τον ιστότοπο iouliageraskliceramics.com.' ),
						'ul' => ioulia_legal_identity_lines( 'el' ),
					),
					array(
						'h' => 'Τι συλλέγουμε',
						'p' => array( 'Μόνο ό,τι μας δίνεις εσύ, και όσα καταγράφει τεχνικά ο ιστότοπος για να δουλέψει.' ),
						'ul' => array(
							'Παραγγελία: ονοματεπώνυμο, διεύθυνση αποστολής και χρέωσης, email, τηλέφωνο, τα κομμάτια που αγόρασες και το ποσό.',
							'Φόρμα επικοινωνίας: ονοματεπώνυμο, email, τηλέφωνο αν το δώσεις, και το μήνυμά σου.',
							'Κράτηση σε εργαστήριο: ονοματεπώνυμο, email, τηλέφωνο, το μάθημα και η ημερομηνία που διάλεξες.',
							'Πληρωμή: τα στοιχεία της κάρτας σου δεν περνούν ποτέ από τους δικούς μας διακομιστές και δεν τα βλέπουμε. Τα χειρίζεται ο πάροχος πληρωμών.',
							'Τεχνικά: διεύθυνση IP, τύπος προγράμματος περιήγησης και ώρα επίσκεψης, στα αρχεία καταγραφής του διακομιστή.',
							'Cookies: όσα περιγράφονται στην Πολιτική Cookies.',
						),
					),
					array(
						'h' => 'Γιατί τα επεξεργαζόμαστε',
						'ul' => array(
							'Για να εκτελέσουμε την παραγγελία ή την κράτησή σου — εκτέλεση σύμβασης.',
							'Για να απαντήσουμε στο μήνυμά σου — έννομο συμφέρον να επικοινωνούμε με όποιον μας γράφει.',
							'Για να εκδώσουμε και να φυλάξουμε παραστατικά — νομική υποχρέωση.',
							'Για την ασφάλεια του ιστότοπου και την αποτροπή απάτης — έννομο συμφέρον.',
							'Για στατιστικά ή marketing cookies, αν και μόνο αν τα δεχτείς — συγκατάθεση, που μπορείς να ανακαλέσεις όποτε θέλεις.',
						),
					),
					array(
						'h' => 'Πόσο τα κρατάμε',
						'ul' => array(
							'Παραστατικά και στοιχεία παραγγελιών: όσο ορίζει η φορολογική νομοθεσία.',
							'Μηνύματα από τη φόρμα επικοινωνίας: δεν αποθηκεύονται στον ιστότοπο. Προωθούνται στο email του εργαστηρίου και μένουν εκεί όσο χρειάζεται για να απαντηθούν.',
							'Κρατήσεις εργαστηρίων: μέχρι το τέλος του μαθήματος και για εύλογο διάστημα μετά, για λογιστικούς λόγους.',
							'Αρχεία καταγραφής διακομιστή: για σύντομο διάστημα, όπως τα κρατά ο πάροχος φιλοξενίας.',
						),
					),
					array(
						'h' => 'Ποιος άλλος τα βλέπει',
						'p' => array( 'Δεν πουλάμε και δεν ανταλλάσσουμε προσωπικά δεδομένα. Τα μοιραζόμαστε μόνο με όσους χρειάζεται για να φτάσει η παραγγελία σε σένα:' ),
						'ul' => array(
							'Ο πάροχος φιλοξενίας του ιστότοπου.',
							'Ο πάροχος πληρωμών, για τη χρέωση και για τον έλεγχο απάτης.',
							'Η εταιρεία ταχυμεταφορών, για τη διεύθυνση και το τηλέφωνο αποστολής.',
							'Ο λογιστής μας και οι αρχές, όπου το επιβάλλει ο νόμος.',
						),
					),
					array(
						'h' => 'Διαβίβαση εκτός Ευρωπαϊκής Ένωσης',
						'p' => array( 'Ορισμένοι από τους παραπάνω παρόχους μπορεί να επεξεργάζονται δεδομένα εκτός ΕΟΧ. Όπου συμβαίνει αυτό, γίνεται με τις εγγυήσεις που προβλέπει ο GDPR, συνήθως τις τυποποιημένες συμβατικές ρήτρες της Ευρωπαϊκής Επιτροπής.' ),
					),
					array(
						'h' => 'Τα δικαιώματά σου',
						'p' => array( 'Έχεις δικαίωμα πρόσβασης, διόρθωσης, διαγραφής, περιορισμού, φορητότητας και εναντίωσης, καθώς και δικαίωμα να ανακαλέσεις τη συγκατάθεσή σου χωρίς αυτό να θίγει όσα έγιναν πριν. Γράψε μας στο ' . $d['email'] . ' και απαντάμε το αργότερο μέσα σε έναν μήνα.' ),
						'p2' => array( 'Αν θεωρείς ότι δεν χειριστήκαμε σωστά τα δεδομένα σου, μπορείς να προσφύγεις στην Αρχή Προστασίας Δεδομένων Προσωπικού Χαρακτήρα, Λεωφόρος Κηφισίας 1-3, 115 23 Αθήνα, dpa.gr.' ),
					),
					array(
						'h' => 'Ασφάλεια',
						'p' => array( 'Ο ιστότοπος λειτουργεί αποκλειστικά με κρυπτογραφημένη σύνδεση. Η πρόσβαση στα δεδομένα παραγγελιών περιορίζεται σε όσους τη χρειάζονται για να δουλέψουν. Κανένα σύστημα δεν είναι απόλυτα ασφαλές, αλλά αν συμβεί περιστατικό που σε αφορά θα ενημερωθείς όπως ορίζει ο νόμος.' ),
					),
					array(
						'h' => 'Αλλαγές',
						'p' => array( 'Αν αλλάξει κάτι ουσιαστικό, θα ενημερωθεί αυτή η σελίδα και η ημερομηνία στην κορυφή της.' ),
					),
				),
			),

			/* ---------------------------------------------------------------
			 * Όροι Χρήσης και Πώλησης
			 * ------------------------------------------------------------ */
			'terms' => array(
				'title' => 'Όροι Χρήσης και Πώλησης',
				'lede'  => 'Οι όροι με τους οποίους λειτουργεί αυτός ο ιστότοπος και με τους οποίους αγοράζεις από εμάς. Ισχύουν όπως είναι τη στιγμή που δίνεις την παραγγελία.',
				'sections' => array(
					array(
						'h' => 'Ποιοι είμαστε',
						'ul' => ioulia_legal_identity_lines( 'el' ),
					),
					array(
						'h' => 'Χειροποίητα κομμάτια',
						'p' => array( 'Κάθε κεραμικό πλάθεται, ζωγραφίζεται και ψήνεται στο χέρι. Οι διαστάσεις, τα χρώματα και η υφή διαφέρουν λίγο από κομμάτι σε κομμάτι, και οι φωτογραφίες δείχνουν το είδος, όχι το ακριβές αντικείμενο που θα λάβεις. Αυτές οι μικρές διαφορές δεν είναι ελαττώματα.' ),
					),
					array(
						'h' => 'Η παραγγελία',
						'p' => array( 'Η παραγγελία σου είναι πρόταση αγοράς. Η σύμβαση συνάπτεται όταν σου στείλουμε την επιβεβαίωση. Αν κάτι δεν είναι διαθέσιμο ή αν μια τιμή έχει καταχωρηθεί προφανώς λάθος, μπορούμε να μην αποδεχτούμε την παραγγελία και σου επιστρέφουμε ολόκληρο το ποσό.' ),
					),
					array(
						'h' => 'Τιμές και πληρωμή',
						'ul' => array(
							'Οι τιμές είναι σε ευρώ και περιλαμβάνουν ΦΠΑ. Τα μεταφορικά υπολογίζονται στο ταμείο.',
							'Η πληρωμή γίνεται με τους τρόπους που εμφανίζονται στο ταμείο τη στιγμή της παραγγελίας.',
							'Τα κομμάτια παραμένουν στην κυριότητά μας μέχρι να εξοφληθούν.',
						),
					),
					array(
						'h' => 'Υπαναχώρηση',
						'p' => array( 'Έχεις δικαίωμα να υπαναχωρήσεις μέσα σε δεκατέσσερις ημερολογιακές ημέρες από την παραλαβή, χωρίς να χρειάζεται να δικαιολογήσεις γιατί. Οι λεπτομέρειες και ο τρόπος είναι στη σελίδα Αποστολές και Επιστροφές.' ),
						'p2' => array( 'Το δικαίωμα δεν ισχύει για κομμάτια που φτιάχτηκαν κατά παραγγελία ή εξατομικεύτηκαν για σένα, ούτε για τη συμμετοχή σε εργαστήριο που έχει ήδη γίνει με τη συγκατάθεσή σου.' ),
					),
					array(
						'h' => 'Νόμιμη εγγύηση',
						'p' => array( 'Αν ένα κομμάτι είναι ελαττωματικό ή δεν ανταποκρίνεται σε αυτό που παραγγείλθηκε, ισχύουν τα δικαιώματά σου κατά τον ελληνικό και τον ευρωπαϊκό νόμο: επισκευή, αντικατάσταση, μείωση τιμής ή υπαναχώρηση. Η εγγύηση αυτή είναι ανεξάρτητη από την υπαναχώρηση των δεκατεσσάρων ημερών.' ),
					),
					array(
						'h' => 'Εργαστήρια και μαθήματα',
						'ul' => array(
							'Η θέση σου κρατείται όταν ολοκληρωθεί η κράτηση.',
							'Αν χρειαστεί να ακυρώσεις, ενημέρωσέ μας το συντομότερο. Για ακύρωση τουλάχιστον σαράντα οκτώ ώρες πριν, η θέση μεταφέρεται σε άλλη ημερομηνία ή επιστρέφεται το ποσό.',
							'Αν αναγκαστούμε εμείς να ακυρώσουμε ένα μάθημα, σου προτείνουμε νέα ημερομηνία ή σου επιστρέφουμε ολόκληρο το ποσό.',
							'Στον χώρο δουλεύουμε με πηλό, εργαλεία και κλίβανο. Ακολούθησε τις οδηγίες μας και πες μας από πριν για τυχόν αλλεργίες ή θέματα υγείας.',
						),
					),
					array(
						'h' => 'Πνευματική ιδιοκτησία',
						'p' => array( 'Τα σχέδια, οι φωτογραφίες, τα κείμενα και η εικαστική ταυτότητα αυτού του ιστότοπου ανήκουν στην Ιουλία Γεράσκλη. Μπορείς να τα μοιραστείς με αναφορά στην πηγή. Δεν μπορείς να τα αντιγράψεις για εμπορική χρήση ή να αναπαραγάγεις τα σχέδια σε δικά σου αντικείμενα χωρίς γραπτή άδεια.' ),
					),
					array(
						'h' => 'Ευθύνη',
						'p' => array( 'Κάνουμε ό,τι μπορούμε ώστε ο ιστότοπος να είναι διαθέσιμος και οι πληροφορίες σωστές, χωρίς να εγγυόμαστε αδιάλειπτη λειτουργία. Δεν περιορίζουμε την ευθύνη μας σε καμία περίπτωση όπου ο νόμος δεν το επιτρέπει, όπως σε θάνατο, σωματική βλάβη ή δόλο.' ),
					),
					array(
						'h' => 'Δίκαιο και διαφορές',
						'p' => array( 'Εφαρμόζεται το ελληνικό δίκαιο και αρμόδια είναι τα δικαστήρια των Αθηνών, χωρίς να θίγονται τα δικαιώματα που έχεις ως καταναλωτής στη χώρα διαμονής σου. Για εξωδικαστική επίλυση μπορείς να απευθυνθείς στον Συνήγορο του Καταναλωτή ή στην ευρωπαϊκή πλατφόρμα ΗΕΔ στο ec.europa.eu/consumers/odr.' ),
					),
				),
			),

			/* ---------------------------------------------------------------
			 * Αποστολές και Επιστροφές
			 * ------------------------------------------------------------ */
			'shipping-returns' => array(
				'title' => 'Αποστολές και Επιστροφές',
				'lede'  => 'Πώς ταξιδεύει ένα κεραμικό μέχρι εσένα, και τι γίνεται αν δεν είναι αυτό που περίμενες ή αν φτάσει σπασμένο.',
				'sections' => array(
					array(
						'h' => 'Χρόνος προετοιμασίας',
						'p' => array( 'Τα κομμάτια που είναι σε απόθεμα φεύγουν συνήθως μέσα σε δύο με τρεις εργάσιμες. Ό,τι φτιάχνεται κατά παραγγελία χρειάζεται περισσότερο, γιατί περνά από πλάσιμο, ζωγραφική και δύο ψησίματα. Στην επιβεβαίωση της παραγγελίας θα βρεις τον χρόνο που ισχύει για τη δική σου.' ),
					),
					array(
						'h' => 'Πού στέλνουμε',
						'ul' => array(
							'Ελλάδα, με ταχυμεταφορά.',
							'Ευρωπαϊκή Ένωση.',
							'Υπόλοιπος κόσμος, κατόπιν συνεννόησης. Γράψε μας πριν παραγγείλεις και υπολογίζουμε τα μεταφορικά.',
						),
						'p2' => array( 'Το ακριβές κόστος εμφανίζεται στο ταμείο πριν πληρώσεις. Εκτός ΕΕ, τυχόν δασμοί ή φόροι εισαγωγής βαρύνουν εσένα και δεν τους ελέγχουμε.' ),
					),
					array(
						'h' => 'Συσκευασία',
						'p' => array( 'Τα κεραμικά είναι εύθραυστα και συσκευάζονται ανάλογα, με υλικά που προστατεύουν χωρίς να παραείναι πολλά. Αν κάτι μπορεί να ξαναχρησιμοποιηθεί, το ξαναχρησιμοποιούμε.' ),
					),
					array(
						'h' => 'Αν φτάσει σπασμένο',
						'p' => array( 'Λυπόμαστε, και το φτιάχνουμε. Στείλε μας φωτογραφία του κομματιού και της συσκευασίας μέσα σε σαράντα οκτώ ώρες από την παραλαβή, στο ' . $d['email'] . '. Θα σου στείλουμε αντικατάσταση, ή θα σου επιστρέψουμε το ποσό αν το κομμάτι ήταν μοναδικό.' ),
					),
					array(
						'h' => 'Αλλαγή γνώμης',
						'p' => array( 'Έχεις δεκατέσσερις ημέρες από την παραλαβή για να υπαναχωρήσεις, χωρίς να εξηγήσεις γιατί.' ),
						'ul' => array(
							'Γράψε μας στο ' . $d['email'] . ' μέσα στις δεκατέσσερις ημέρες.',
							'Στείλε πίσω το κομμάτι μέσα σε άλλες δεκατέσσερις, στην ίδια κατάσταση που το έλαβες και συσκευασμένο ώστε να ταξιδέψει με ασφάλεια.',
							'Τα έξοδα της επιστροφής τα αναλαμβάνεις εσύ, εκτός αν το κομμάτι ήταν ελαττωματικό ή λάθος.',
							'Επιστρέφουμε το ποσό μέσα σε δεκατέσσερις ημέρες από τη στιγμή που θα το παραλάβουμε, με τον ίδιο τρόπο πληρωμής.',
						),
					),
					array(
						'h' => 'Τι δεν επιστρέφεται',
						'ul' => array(
							'Κομμάτια που φτιάχτηκαν κατά παραγγελία ή εξατομικεύτηκαν για σένα.',
							'Κομμάτια που έχουν χρησιμοποιηθεί ή φθαρεί μετά την παραλαβή.',
							'Δωροκάρτες.',
						),
					),
					array(
						'h' => 'Φροντίδα',
						'p' => array( 'Τα περισσότερα κομμάτια αντέχουν πλυντήριο πιάτων και φούρνο μικροκυμάτων, αλλά διαρκούν περισσότερο αν πλυθούν στο χέρι. Τα κομμάτια με χρυσό ή μεταλλική λεπτομέρεια δεν μπαίνουν ποτέ στα μικροκύματα. Απότομες αλλαγές θερμοκρασίας ραγίζουν το υαλωμα.' ),
					),
				),
			),

			/* ---------------------------------------------------------------
			 * Πολιτική Cookies
			 * ------------------------------------------------------------ */
			'cookies' => array(
				'title' => 'Πολιτική Cookies',
				'lede'  => 'Τι αποθηκεύει αυτός ο ιστότοπος στη συσκευή σου, γιατί, και πώς το αλλάζεις.',
				'sections' => array(
					array(
						'h' => 'Τι είναι τα cookies',
						'p' => array( 'Μικρά αρχεία που ο ιστότοπος αφήνει στο πρόγραμμα περιήγησής σου. Άλλα είναι απαραίτητα για να δουλέψει η σελίδα και άλλα προαιρετικά. Τα προαιρετικά μπαίνουν μόνο αν τα δεχτείς.' ),
					),
					array(
						'h' => 'Απαραίτητα',
						'p' => array( 'Χωρίς αυτά ο ιστότοπος δεν λειτουργεί, και γι αυτό δεν ζητούν συγκατάθεση.' ),
						'ul' => array(
							'Καλάθι και σύνοδος αγορών: κρατούν τι έχεις προσθέσει και τα στοιχεία του ταμείου όσο ολοκληρώνεις την παραγγελία.',
							'Συγκατάθεση: κρατά την επιλογή σου για τα cookies, ώστε να μη ρωτηθείς ξανά σε κάθε σελίδα.',
							'Ασφάλεια πληρωμών: ο πάροχος πληρωμών χρησιμοποιεί δικά του cookies για να αναγνωρίσει ύποπτες συναλλαγές.',
						),
					),
					array(
						'h' => 'Προαιρετικά',
						'p' => array( 'Λειτουργικά, στατιστικά και cookies marketing μπαίνουν μόνο αν τα επιτρέψεις στο σχετικό παράθυρο. Αν δεν δώσεις συγκατάθεση, ο ιστότοπος λειτουργεί κανονικά.' ),
					),
					array(
						'h' => 'Πώς αλλάζεις γνώμη',
						'p' => array( 'Άνοιξε τις ρυθμίσεις συγκατάθεσης από το εικονίδιο στην κάτω γωνία της σελίδας και άλλαξε ό,τι θέλεις. Μπορείς επίσης να διαγράψεις όλα τα cookies από τις ρυθμίσεις του προγράμματος περιήγησής σου, οπότε θα ερωτηθείς ξανά στην επόμενη επίσκεψη.' ),
					),
					array(
						'h' => 'Ενσωματωμένο περιεχόμενο',
						'p' => array( 'Σελίδες που δείχνουν περιεχόμενο από άλλη υπηρεσία, όπως έναν χάρτη ή μια ανάρτηση, μπορεί να φορτώνουν cookies αυτής της υπηρεσίας. Ισχύει η δική της πολιτική.' ),
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
				'lede'  => 'We keep as little personal data as the studio and the shop can run on, and nothing beyond that. This page says what we hold, why we need it, and what you can ask us to do with it.',
				'sections' => array(
					array(
						'h' => 'Who we are',
						'p' => array( 'The data controller is ' . $d['trading_name'] . ', which runs iouliageraskliceramics.com.' ),
						'ul' => ioulia_legal_identity_lines( 'en' ),
					),
					array(
						'h' => 'What we collect',
						'p' => array( 'Only what you give us, and what the site records technically in order to work.' ),
						'ul' => array(
							'An order: your name, delivery and billing address, email, telephone, the pieces you bought and the amount.',
							'The contact form: your name, email, telephone if you give one, and your message.',
							'A workshop booking: your name, email, telephone, and the class and date you chose.',
							'Payment: your card details never pass through our servers and we never see them. They are handled by the payment provider.',
							'Technical: IP address, browser type and time of the visit, in the server logs.',
							'Cookies: as described in the Cookie Policy.',
						),
					),
					array(
						'h' => 'Why we process it',
						'ul' => array(
							'To fulfil your order or booking — performance of a contract.',
							'To answer your message — our legitimate interest in replying to people who write to us.',
							'To issue and keep invoices — a legal obligation.',
							'To keep the site secure and prevent fraud — legitimate interest.',
							'For statistics or marketing cookies, if and only if you accept them — consent, which you can withdraw at any time.',
						),
					),
					array(
						'h' => 'How long we keep it',
						'ul' => array(
							'Invoices and order records: for as long as Greek tax law requires.',
							'Contact form messages: not stored on the site at all. They are relayed to the studio inbox and stay there as long as answering them takes.',
							'Workshop bookings: until the class has run, and a reasonable period after it for accounting.',
							'Server logs: a short period, as kept by the hosting provider.',
						),
					),
					array(
						'h' => 'Who else sees it',
						'p' => array( 'We do not sell or trade personal data. We share it only with the people needed to get an order to you:' ),
						'ul' => array(
							'The company hosting the site.',
							'The payment provider, to take the payment and check it for fraud.',
							'The courier, for the delivery address and telephone number.',
							'Our accountant, and the authorities where the law requires it.',
						),
					),
					array(
						'h' => 'Transfers outside the European Union',
						'p' => array( 'Some of the providers above may process data outside the EEA. Where that happens it is covered by the safeguards the GDPR requires, usually the European Commission standard contractual clauses.' ),
					),
					array(
						'h' => 'Your rights',
						'p' => array( 'You have the right of access, rectification, erasure, restriction, portability and objection, and the right to withdraw consent without affecting what was done before. Write to ' . $d['email'] . ' and we will answer within a month at the latest.' ),
						'p2' => array( 'If you believe we have handled your data badly, you can complain to the Hellenic Data Protection Authority, 1-3 Kifissias Avenue, 115 23 Athens, dpa.gr.' ),
					),
					array(
						'h' => 'Security',
						'p' => array( 'The site runs only over an encrypted connection. Access to order data is limited to the people who need it to do their work. No system is perfectly secure, but if an incident affects you, you will be told as the law requires.' ),
					),
					array(
						'h' => 'Changes',
						'p' => array( 'If something material changes, this page and the date at the top of it will be updated.' ),
					),
				),
			),

			'terms' => array(
				'title' => 'Terms of Use and Sale',
				'lede'  => 'The terms this site runs on and the terms you buy from us under. They apply as they stand at the moment you place an order.',
				'sections' => array(
					array(
						'h' => 'Who we are',
						'ul' => ioulia_legal_identity_lines( 'en' ),
					),
					array(
						'h' => 'Handmade pieces',
						'p' => array( 'Every ceramic is thrown, painted and fired by hand. Dimensions, colours and texture vary a little from piece to piece, and the photographs show the kind of object rather than the exact one you will receive. Those small differences are not faults.' ),
					),
					array(
						'h' => 'Your order',
						'p' => array( 'An order is an offer to buy. The contract is formed when we send you a confirmation. If something is unavailable, or a price has obviously been entered wrongly, we may decline the order and refund you in full.' ),
					),
					array(
						'h' => 'Prices and payment',
						'ul' => array(
							'Prices are in euro and include VAT. Delivery is calculated at checkout.',
							'Payment is taken by the methods shown at checkout at the time of your order.',
							'Pieces remain ours until they are paid for in full.',
						),
					),
					array(
						'h' => 'Right of withdrawal',
						'p' => array( 'You may withdraw from the purchase within fourteen calendar days of receiving it, without giving a reason. How to do it is on the Shipping and Returns page.' ),
						'p2' => array( 'The right does not apply to pieces made to order or personalised for you, nor to a workshop that has already taken place with your agreement.' ),
					),
					array(
						'h' => 'Legal guarantee',
						'p' => array( 'If a piece is faulty or does not match what was ordered, your rights under Greek and European law apply: repair, replacement, a reduction in price, or withdrawal. This guarantee is separate from the fourteen-day right above.' ),
					),
					array(
						'h' => 'Workshops and classes',
						'ul' => array(
							'Your place is held once the booking is complete.',
							'If you need to cancel, tell us as early as you can. Cancel at least forty-eight hours before and we will move your place to another date or refund it.',
							'If we have to cancel a class, we will offer you another date or refund you in full.',
							'The studio works with clay, tools and a kiln. Follow our instructions, and tell us in advance about any allergies or health conditions.',
						),
					),
					array(
						'h' => 'Intellectual property',
						'p' => array( 'The designs, photographs, text and visual identity on this site belong to Ioulia Geraskli. You are welcome to share them with credit. You may not copy them commercially or reproduce the designs on objects of your own without written permission.' ),
					),
					array(
						'h' => 'Liability',
						'p' => array( 'We do what we can to keep the site available and its information correct, without guaranteeing uninterrupted service. We do not limit our liability in any case where the law does not allow it, such as death, personal injury or fraud.' ),
					),
					array(
						'h' => 'Law and disputes',
						'p' => array( 'Greek law applies and the courts of Athens have jurisdiction, without affecting the consumer rights you hold in your country of residence. For out-of-court resolution you can approach the Greek Consumer Ombudsman or the European ODR platform at ec.europa.eu/consumers/odr.' ),
					),
				),
			),

			'shipping-returns' => array(
				'title' => 'Shipping and Returns',
				'lede'  => 'How a ceramic travels to you, and what happens if it is not what you expected or arrives broken.',
				'sections' => array(
					array(
						'h' => 'Preparation time',
						'p' => array( 'Pieces in stock usually leave within two to three working days. Anything made to order takes longer, because it has to be thrown, painted and fired twice. Your order confirmation carries the time that applies to yours.' ),
					),
					array(
						'h' => 'Where we ship',
						'ul' => array(
							'Greece, by courier.',
							'The European Union.',
							'The rest of the world, by arrangement. Write to us before ordering and we will work out the shipping.',
						),
						'p2' => array( 'The exact cost is shown at checkout before you pay. Outside the EU, any import duties or taxes are yours and are not within our control.' ),
					),
					array(
						'h' => 'Packing',
						'p' => array( 'Ceramics are fragile and are packed accordingly, with materials that protect without being excessive. Where something can be reused, we reuse it.' ),
					),
					array(
						'h' => 'If it arrives broken',
						'p' => array( 'We are sorry, and we will put it right. Send us a photograph of the piece and of the packaging within forty-eight hours of delivery, to ' . $d['email'] . '. We will send a replacement, or refund you if the piece was one of a kind.' ),
					),
					array(
						'h' => 'If you change your mind',
						'p' => array( 'You have fourteen days from delivery to withdraw, without explaining why.' ),
						'ul' => array(
							'Write to ' . $d['email'] . ' within those fourteen days.',
							'Send the piece back within a further fourteen, in the condition you received it and packed so that it travels safely.',
							'Return postage is yours, unless the piece was faulty or wrong.',
							'We refund within fourteen days of receiving it, by the same payment method.',
						),
					),
					array(
						'h' => 'What cannot be returned',
						'ul' => array(
							'Pieces made to order or personalised for you.',
							'Pieces that have been used or damaged after delivery.',
							'Gift cards.',
						),
					),
					array(
						'h' => 'Care',
						'p' => array( 'Most pieces take a dishwasher and a microwave, but they last longer washed by hand. Pieces with gold or any metallic detail must never go in the microwave. Sudden changes of temperature crack the glaze.' ),
					),
				),
			),

			'cookies' => array(
				'title' => 'Cookie Policy',
				'lede'  => 'What this site stores on your device, why, and how to change it.',
				'sections' => array(
					array(
						'h' => 'What cookies are',
						'p' => array( 'Small files a site leaves in your browser. Some are needed for the page to work and some are optional. The optional ones are only set if you accept them.' ),
					),
					array(
						'h' => 'Necessary',
						'p' => array( 'Without these the site does not work, which is why they do not ask for consent.' ),
						'ul' => array(
							'Cart and shopping session: they hold what you have added and your checkout details while you complete an order.',
							'Consent: holds your cookie choice, so you are not asked again on every page.',
							'Payment security: the payment provider uses its own cookies to recognise suspicious transactions.',
						),
					),
					array(
						'h' => 'Optional',
						'p' => array( 'Functional, statistics and marketing cookies are only set if you allow them in the consent window. If you give no consent, the site works normally.' ),
					),
					array(
						'h' => 'Changing your mind',
						'p' => array( 'Open the consent settings from the icon in the corner of the page and change whatever you like. You can also delete all cookies from your browser settings, in which case you will be asked again on your next visit.' ),
					),
					array(
						'h' => 'Embedded content',
						'p' => array( 'Pages that show content from another service, such as a map or a post, may load that service cookies. Its own policy applies to them.' ),
					),
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
				$out .= '<p>' . esc_html( $paragraph ) . '</p>';
			}

			if ( ! empty( $section['ul'] ) ) {
				$out .= '<ul class="ilegal__list">';
				foreach ( $section['ul'] as $item ) {
					$out .= '<li>' . esc_html( $item ) . '</li>';
				}
				$out .= '</ul>';
			}

			foreach ( (array) ( $section['p2'] ?? array() ) as $paragraph ) {
				$out .= '<p>' . esc_html( $paragraph ) . '</p>';
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
