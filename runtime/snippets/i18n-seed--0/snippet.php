<?php
/**
 * Ioulia i18n seed — the bulk translation of the site, kept in Git.
 *
 * The site is authored in Greek. This file maps each Greek string to its English
 * translation, so /en/ is complete the moment it is deployed and the visual
 * editor is only ever needed for corrections.
 *
 *   'Ελληνικό κείμενο' => 'English text',
 *
 * Matching rules, worth knowing before adding a line by hand:
 *
 *   - The key must be the Greek text exactly as it reads on the page, with the
 *     surrounding whitespace ignored. Runs of spaces and newlines inside it are
 *     collapsed to one space before matching, so indentation in the HTML is
 *     irrelevant.
 *   - Matching is per text run, not per element. A sentence split by an inline
 *     <em> or <br> is two strings, not one.
 *   - The same Greek string anywhere on the site gets the same translation.
 *   - Anything saved in the visual editor overrides the line here, so a fix made
 *     on the live site survives a deploy. Use the editor's "Εξαγωγή για Git"
 *     button to fold those corrections back into this file.
 *
 * Writing a value that contains an apostrophe:
 *
 *   Site Studio stores snippet code through update_post_meta(), which unslashes
 *   it, so a backslash written here never reaches PHP and an escaped apostrophe
 *   becomes a syntax error there. Wrap such a value in double quotes instead:
 *
 *     'Το κομμάτι' => "the piece's character",
 *
 *   The same rule applies to every snippet in this repository: no backslashes.
 *
 * Requires the "i18n translate" snippet.
 */

if ( ! function_exists( 'ioulia_i18n_seed_en' ) ) {
	function ioulia_i18n_seed_en( $seed, $lang ) {
		if ( 'en' !== $lang ) {
			return $seed;
		}

		return array_merge(
			(array) $seed,
			array(
				/* FOOTER & HEADER — chrome */
				'Ioulia Geraskli — αρχική' => 'Ioulia Geraskli home',
				'Νομικές πληροφορίες' => 'Legal',
				'Πολιτική Απορρήτου' => 'Privacy Policy',
				'Προστασία Δεδομένων' => 'Data Protection',
				'Αρχική σελίδα' => 'Home',
				'Άνοιγμα καλαθιού' => 'Open cart',
				'Άνοιγμα μενού' => 'Toggle menu',

				/* MENU OVERLAY */
				'Αρχική' => 'Home',
				'Κατάστημα' => 'Shop',
				'Σχετικά' => 'About',
				'Εργαστήρια' => 'Workshops',
				'Επικοινωνία' => 'Contact',

				/* MINI CART */
				'το καλάθι σου' => 'your cart',
				'Κλείσιμο καλαθιού' => 'Close cart',
				'κλείσιμο' => 'close',
				'αφαίρεση' => 'remove',
				'Τα μεταφορικά και οι φόροι υπολογίζονται στο ταμείο.' => 'Shipping and taxes are calculated at checkout.',
				'δες το καλάθι' => 'view cart',
				'το καλάθι σου είναι άδειο.' => 'your cart is empty.',
				'δες το κατάστημα' => 'explore the shop',

				/* HOME */
				'Φιλοσοφία' => 'Philosophy',
				'Υφή φόντου' => 'Background Texture',
				'Ένας σύγχρονος χώρος' => 'A contemporary space',
				'για δημιουργία με τα χέρια.' => 'for tactile creation.',
				'Σεβόμαστε τον ρυθμό του υλικού, κάνοντας κάθε μικρή ατέλεια αναπόσπαστο κομμάτι του μοναδικού χαρακτήρα κάθε αντικειμένου. Είναι μια ανοιχτή πρόσκληση να μπεις στον χώρο μας, να επιβραδύνεις και να δημιουργήσεις με τα ίδια σου τα χέρια.' => "We respect the rhythm of the material, making every slight imperfection an essential part of a piece's unique character. It is an open invitation to step into our own space, slow down, and create with your own hands.",
				'Η πρακτική του τροχού' => 'The Wheel Practice',

				/* ABOUT */
				'Κεραμικά αντικείμενα στο Ioulia Geraskli Ceramic Lab' => 'Ceramic objects at Ioulia Geraskli Ceramic Lab',
				'Για το εργαστήριο κεραμικής' => 'About the ceramic lab',
				'Το εργαστήριο κεραμικής μας στην Αθήνα είναι ένας ζωντανός δημιουργικός χώρος. Ένα σύγχρονο εργαστήριο γεμάτο υφές, πινέλα, χώμα και τις πολύ αγαπημένες μας γάτες, που συχνά επιβλέπουν τη δουλειά. Είναι ο τόπος όπου η τέχνη απομυθοποιείται και γίνεται κομμάτι της καθημερινότητάς μας.' => 'Our ceramic studio in Athens is a breathing creative space. A contemporary lab filled with textures, brushes, earth, and our very lovely cats who often oversee the workflow. It is the place where art is demystified and becomes part of our everyday life.',
				'Η καινοτόμος προσέγγιση στο ελληνικό design συναντά την παράδοση αυτής της αρχαίας μορφής τέχνης, δίνοντας σχήμα σε λειτουργικά και διακοσμητικά αντικείμενα αισθητικής αξίας, με πρώτη ύλη τον πηλό.' => 'The innovative approach to Greek design meets the tradition of this ancient form of art, giving shape to functional and decorative objects of esthetic quality, having clay as their raw material.',
				'Η φιλοσοφία μας' => 'Our philosophy',
				'Λειτουργούμε ως ένα σύγχρονο' => 'We operate as a contemporary',
				'εργαστήριο, μακριά' => 'craft lab, far',
				'από τη' => 'from the',
				'λογική της μαζικής' => 'logic of mass',
				'παραγωγής.' => 'production.',
				'Η διαδικασία' => 'The process',
				'Κεραμικά κατά τη διαδικασία κατασκευής' => 'Ceramic pieces during the making process',
				'Λεπτομέρεια από τη διαδικασία' => 'Ceramic vessels process detail',
				'Στον πυρήνα μας βρίσκεται η αξία της παύσης. Η επαφή με τον πηλό είναι μια υπενθύμιση να επιβραδύνουμε. Αφιερώνοντας χρόνο σε κάτι φτιαγμένο εξ ολοκλήρου στο χέρι, διεκδικούμε πίσω τον προσωπικό μας χρόνο. Κάθε χειροποίητο κεραμικό κρατά μέσα του την ησυχία και τη συγκέντρωση αυτής της διαδικασίας.' => 'At our core lies the value of the pause. Connecting with clay is a reminder to slow down. By dedicating time to something crafted entirely by hand, we reclaim our personal time. Each handmade ceramic holds within it the quietness and focus of this process.',
				'Η προσέγγισή μας στο παραδοσιακό ελληνικό design συναντά τη νεωτερικότητα των στιγμών που ζούμε. Συνδυάζουμε χρώμα, συναισθήματα και ανθρώπους, δημιουργούμε σύγχρονα, οικεία αντικείμενα εμπνευσμένα από την ποίηση της καθημερινότητας. Κάθε συλλογή είναι ένας ήσυχος διάλογος ανάμεσα στη λαϊκή παράδοση, το μοντέρνο design και την αλήθεια του υλικού.' => 'Our approach to traditional Greek design meets the modernity of the moments we live. We combine color, feelings and people, we create contemporary, relatable objects inspired by the poetry of everyday life. Each collection is a quiet dialogue between folk heritage, modern design, and the truth of the material.',
				'Εικονογράφηση κεραμικού' => 'Ceramic illustration',
				'Τα σχέδια' => 'The designs',
				'Εικονογράφηση κεραμικού σκεύους' => 'Ceramic vessel illustration',
				'Η παραγωγή' => 'The Production',
				'Ακολουθούμε μια συνειδητά αργή χειροτεχνική διαδικασία. Κάθε αντικείμενο πλάθεται, ζωγραφίζεται και υαλώνεται στο χέρι, μακριά από τη μαζική παραγωγή. Αυτό σημαίνει ότι κανένα κομμάτι δεν είναι ακριβώς ίδιο με το άλλο. Κάθε μικρή ατέλεια είναι το προσωπικό μας μήνυμα.' => 'We follow a consciously slow craft process. Every object is shaped, illustrated, and glazed by hand, far removed from mass production. This means no two pieces are exactly alike. Every slight imperfection is our personal message.',
			)
		);
	}
	add_filter( 'ioulia_i18n_seed', 'ioulia_i18n_seed_en', 10, 2 );
}
