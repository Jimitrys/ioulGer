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
				/* HOME HERO */
				'Αντικείμενα που πλάθονται αργά.' => 'Objects shaped slowly.',
				'Φτιαγμένα για να τα κρατάς.' => 'Made to be held.',
				'Μια πρακτική κεραμικής' => 'A ceramic practice',
				'φτιαγμένη στο χέρι.' => 'made by hand.',
				'Δες τα κομμάτια' => 'Explore pieces',
				'Εργαστήρια Κεραμικής' => 'Ceramic Workshops',
				'Δες' => 'Explore',
				'Αθήνα, Ελλάδα' => 'Athens, Greece',

				/* HOME PRODUCT REEL */
				'Πρόσφατα προϊόντα' => 'Recent products',
				'ΣΥΡΕ' => 'DRAG',
				'ΔΕΣ' => 'VIEW',
				'Νέες' => 'Latest',
				'δημιουργίες' => 'creations',

				/* WORKSHOPS — page */
				'Workshops Κεραμικής Αθήνα' => 'Pottery Workshops in Athens',
				'Κεραμικής' => 'Pottery',
				'Αθήνα' => 'Athens',
				'Κλείσε Θέση' => 'Book your place',
				'Εργαστήριο κεραμικής στο Ioulia Geraskli Ceramic Lab στα Άνω Πατήσια' => 'Pottery workshop at Ioulia Geraskli Ceramic Lab in Ano Patisia',
				'Μαθήματα πηλοπλαστικής και τροχού για όσους θέλουν να επιβραδύνουν, να έρθουν σε επαφή με τον πηλό και να δημιουργήσουν κάτι δικό τους.' => 'Handbuilding and wheel-throwing classes for anyone who wants to slow down, connect with clay and create something of their own.',
				'Το εργαστήριο βρίσκεται στα Άνω Πατήσια. Όλα τα επίπεδα είναι ευπρόσδεκτα και δεν απαιτείται προηγούμενη εμπειρία. Συμμετοχή κατόπιν κράτησης (τουλάχιστον 3 ημέρες πριν).' => 'Our studio is in Ano Patisia, Athens. All levels are welcome and no previous experience is required. Advance booking is essential, at least three days before your session.',
				'ένα αληθινό' => 'a real working',
				'εργαστήριο' => 'studio',
				'Η εμπειρία των workshops στο εργαστήριό μας είναι αληθινή! Μην περιμένετε να βρείτε μια άδεια αίθουσα πεντακάθαρη και τακτοποιημένη φτιαγμένη μόνο για σεμινάρια. Δουλεύουμε σε ένα ζωντανό εργαστήριο σε πλήρη λειτουργία — με τη σκόνη και τις λάσπες από τον πηλό, με τις γάτες μας, τα γεμάτα ράφια μας και το 90s ραδιοφωνάκι μας.' => 'Our workshops take place in a real, working ceramics studio. Do not expect a spotless, perfectly ordered room created just for classes. This is a lively studio in full swing — complete with clay dust and splashes, our cats, shelves filled with work and a little radio playing 90s music.',
				'Ένα σημείο συνάντησης για δημιουργία, επικοινωνία και ουσιαστική επαφή με την τέχνη της κεραμικής. Μας ενδιαφέρει η ουσία: να μαθαίνετε, να αφήνεστε ελεύθεροι και να κάνετε την κεραμική κομμάτι σας. Σας περιμένουμε γεμάτοι όρεξη για να λερωθούμε και να δημιουργήσουμε!' => 'It is a meeting place for making, connecting and engaging meaningfully with the art of ceramics. What matters to us is that you learn, feel free to experiment and make ceramics your own. Come prepared to get your hands dirty and create with us.',
				'φωτογραφίες εργαστηρίου κεραμικής' => 'pottery studio photographs',
				'Δημιουργία με πηλό στο εργαστήριο' => 'Working with clay in the studio',
				'Μάθημα τροχού κεραμικής' => 'Wheel-throwing class',
				'Στιγμιότυπο από το ceramic lab' => 'Inside the ceramic lab',
				'Σύρε' => 'Drag',
				'Κάθεσαι. Τα χέρια σου βυθίζονται στον πηλό. Καταρρέει μία, ίσως δύο φορές, και μετά κάτι στέκεται — μια κούπα, ένα μπολ, ένα σχήμα που δεν υπήρχε σήμερα το πρωί.' => 'You sit down and press your hands into the clay. It collapses once, perhaps twice, and then something takes shape — a cup, a bowl, a form that did not exist this morning.',
				'στον δικό σου ρυθμό' => 'work at your own pace',
				'τα προγράμματα' => 'our workshop',
				'μας' => 'programme',
				'Μπορείς να συμμετέχεις σε όσα workshops θέλεις μέσα στην εβδομάδα ή στον μήνα και σε όποιο γκρουπ σε βολεύει, αρκεί να κλείνεις τη θέση σου 3 ημέρες πριν. Όλα τα υλικά, τα εργαλεία και τα ψησίματα συμπεριλαμβάνονται.' => 'Join as many workshops as you like during the week or month, choosing whichever group suits you best. Simply reserve your place at least three days in advance. All materials, tools and firings are included.',
				'Κατασκευές με τη μέθοδο της πηλοπλαστικής (με τα χέρια μας και με εργαλεία χειρός: pinch pots, μακαρόνι/coiling, φύλλο/slab building). Ένας άμεσος, οργανικός τρόπος να πλάσετε τον πηλό και να εξερευνήσετε φόρμες και υφές.' => 'Create pieces using handbuilding techniques and simple tools: pinch pots, coiling and slab building. It is a direct, intuitive way to shape clay and explore form and texture.',
				'Εισαγωγή στη λειτουργία και στα εργαλεία του τροχού. Ζύμωμα πηλού, κεντράρισμα, άνοιγμα και ανέβασμα τοιχωμάτων για τη δημιουργία βασικών σχημάτων (κούπες, μπολ, κυλίνδρους). Εστίαση στον ρυθμό, τη στάση του σώματος και τη συγκέντρωση.' => 'An introduction to the pottery wheel and its tools. Learn to wedge and centre clay, open it up and pull the walls to create simple forms such as cups, bowls and cylinders. The class focuses on rhythm, posture and concentration.',
				'Πηλοπλαστική για Παιδιά (6–11 ετών)' => 'Handbuilding for Children (ages 6–11)',
				'Επαφή και κατασκευές με πηλό! Μέσα από την κεραμική το παιδί καλλιεργεί τη φαντασία του, λερώνεται και αφήνεται ελεύθερο. Μαθαίνει να δημιουργεί με τα χέρια του και εξασκείται αισθητηριακά.' => 'A playful, hands-on introduction to clay. Through ceramics, children develop their imagination, enjoy getting messy and learn to express themselves freely. They make with their hands while building sensory awareness.',
				'Γονέας & Παιδί (6–15 ετών)' => 'Parent & Child (ages 6–15)',
				'Δημιουργήστε παρέα ένα κεραμικό αντικείμενο από πηλό! Η αλληλεπίδραση, η συνεργασία και η ηρεμία όταν δημιουργούμε μαζί συνθέτουν μια μοναδική εμπειρία που μας φέρνει κοντά.' => 'Create a ceramic piece together. The interaction, collaboration and sense of calm that come from making side by side create a unique shared experience that brings you closer.',
				'Κυριακάτικο & Κονσεπτικό (Paint & Sip)' => 'Sunday Concept: Paint & Sip',
				'Workshop ζωγραφικής σε έτοιμο κεραμικό αντικείμενο του εργαστηρίου μας. Μία ή δύο Κυριακές τον μήνα ελάτε με την παρέα σας να ζωγραφίσετε το δικό σας κεραμικό, συνοδεία ποτού και θεματικών εδεσμάτων (τσιμπι-τσιμπι)! Σε μία εβδομάδα παραλαμβάνετε το κεραμικό σας υαλωμένο και έτοιμο για καθημερινή χρήση. Τα θέματα ανακοινώνονται αρχές κάθε μήνα.' => 'Paint a ready-made ceramic piece from our studio. Once or twice a month on a Sunday, bring your friends, choose your piece and paint it while enjoying a drink and themed bites. One week later, your ceramic will be glazed, fired and ready for everyday use. Each month’s themes are announced at the beginning of the month.',
				'Ημέρες & Ώρες:' => 'Days & times:',
				'Κόστος:' => 'Price:',
				'Δευτέρα' => 'Monday',
				'Τρίτη' => 'Tuesday',
				'Τετάρτη' => 'Wednesday',
				'Πέμπτη' => 'Thursday',
				'Παρασκευή' => 'Friday',
				'Σάββατο' => 'Saturday',
				'Κυριακή' => 'Sunday',
				'συνάντηση' => 'session',
				'• Δευτέρα: 11:00 – 13:00' => '• Monday: 11:00–13:00',
				'• Δευτέρα: 18:00 – 20:30' => '• Monday: 18:00–20:30',
				'• Τρίτη: 17:00 – 18:30' => '• Tuesday: 17:00–18:30',
				'• Τρίτη: 18:30 – 20:30' => '• Tuesday: 18:30–20:30',
				'• Τετάρτη: 11:00 – 13:30 & 18:00 – 20:30' => '• Wednesday: 11:00–13:30 & 18:00–20:30',
				'• Πέμπτη: 17:00 – 18:30' => '• Thursday: 17:00–18:30',
				'• Πέμπτη: 18:30 – 20:30' => '• Thursday: 18:30–20:30',
				'• Παρασκευή: 11:00 – 13:00' => '• Friday: 11:00–13:00',
				'• Παρασκευή: 11:00 – 13:30 & 18:00 – 20:30' => '• Friday: 11:00–13:30 & 18:00–20:30',
				'• Σάββατο: 09:30 – 11:00' => '• Saturday: 09:30–11:00',
				'• Σάββατο: 11:00 – 13:30, 13:30 – 16:00 & 16:30 – 19:00' => '• Saturday: 11:00–13:30, 13:30–16:00 & 16:30–19:00',
				'• Κυριακή: 12:00 – 14:00 ή 18:00 – 20:00' => '• Sunday: 12:00–14:00 or 18:00–20:00',
				'25,00€ / συνάντηση' => '€25.00 / session',
				'30,00€ / συνάντηση' => '€30.00 / session',
				'20,00€ / συνάντηση' => '€20.00 / session',
				'40,00€ / συνάντηση' => '€40.00 / session',
				'(περιλαμβάνονται υλικά & εργαλεία · χωρίς ΦΠΑ)' => '(materials and tools included · excluding VAT)',
				'(χωρίς ΦΠΑ)' => '(excluding VAT)',
				'(γονέας + παιδί μαζί · χωρίς ΦΠΑ)' => '(parent and child together · excluding VAT)',
				'(1-2 Κυριακές τον μήνα)' => '(1–2 Sundays per month)',
				'(περιλαμβάνεται το κεραμικό, χρώματα, υάλωμα, ψήσιμο & κέρασμα · χωρίς ΦΠΑ)' => '(ceramic piece, paints, glazing, firing and refreshments included · excluding VAT)',
				'Τοποθεσία Εργαστηρίου' => 'Studio location',
				'άνοιγμα στο google maps' => 'open in Google Maps',
				'Ανοιχτά για Workshops' => 'Open for workshops',
				'Αθήνα, Άνω Πατήσια' => 'Athens, Ano Patisia',
				'Διεύθυνση' => 'Address',
				'Προμπονά 42, Άνω Πατήσια, 111 43 Αθήνα' => '42 Prompona Street, Ano Patisia, 111 43 Athens',
				'Πρόσβαση & Μετακίνηση' => 'Getting here',
				'5 λεπτά με τα πόδια από τον σταθμό ΗΣΑΠ / Μετρό' => 'A 5-minute walk from the ISAP / Metro station',
				'Άνω Πατήσια' => 'Ano Patisia',
				'Οδηγίες στο Google Maps' => 'Directions on Google Maps',
				'πριν το workshop' => 'before your workshop',
				'Χρειάζομαι προηγούμενη εμπειρία;' => 'Do I need any previous experience?',
				'Όχι, καθόλου. Όλα τα workshops μας είναι ανοιχτά τόσο σε απόλυτα αρχάριους όσο και σε άτομα που έχουν ήδη εξοικείωση με τον πηλό.' => 'Not at all. Every workshop is open both to complete beginners and to people who already have some experience with clay.',
				'Πώς κάνω κράτηση και πόσο νωρίτερα;' => 'How do I book, and how far in advance?',
				'Επιλέγετε το πρόγραμμα και την ώρα που σας εξυπηρετεί στη σελίδα κρατήσεων. Για την καλύτερη οργάνωση του εργαστηρίου, είναι απαραίτητο να κλείνετε τη θέση σας τουλάχιστον 3 ημέρες πριν. Η επιβεβαίωση αποστέλλεται με email.' => 'Choose your workshop and preferred time on the booking page. To help us prepare the studio, please reserve your place at least three days in advance. We will send your confirmation by email.',
				'Τι περιλαμβάνεται στην τιμή;' => 'What is included in the price?',
				'Στο κόστος περιλαμβάνονται όλα τα υλικά (πηλός, χρώματα, υαλώματα), η χρήση των εργαλείων και του τροχού, καθώς και τα ψησίματα στο καμίνι μας. (Στις αναγραφόμενες τιμές δεν συμπεριλαμβάνεται ΦΠΑ 24%).' => 'The price includes all materials — clay, colours and glazes — use of the tools and pottery wheel, and firing in our kiln. The listed prices exclude 24% VAT.',
				'Πότε παραλαμβάνω τα κεραμικά μου;' => 'When can I collect my ceramics?',
				'Τα αντικείμενα που φτιάχνετε χρειάζονται χρόνο για να στεγνώσουν αργά, να ψηθούν (μπισκουί), να υαλωθούν και να ψηθούν ξανά σε υψηλή θερμοκρασία. Θα είναι έτοιμα και πλήρως λειτουργικά για παραλαβή σε περίπου 1–2 εβδομάδες (στο Κυριακάτικο σε 1 εβδομάδα).' => 'Your pieces need time to dry slowly, undergo a bisque firing, be glazed and then fired again at a high temperature. They will be fully finished and ready to collect in approximately one to two weeks; Paint & Sip pieces are ready in one week.',
				'Μπορώ να κλείσω θέση για περισσότερα άτομα;' => 'Can I book for more than one person?',
				'Ναι. Στη φόρμα κράτησης μπορείτε να επιλέξετε τον αριθμό των συμμετεχόντων. Η διαθεσιμότητα εξαρτάται από τον μέγιστο αριθμό θέσεων της εκάστοτε συνάντησης.' => 'Yes. You can select the number of participants in the booking form. Availability depends on the number of places remaining in each session.',
				/* WORKSHOPS — booking form */
				'Χέρια που δουλεύουν τον πηλό στο εργαστήριο' => 'Hands working with clay in the studio',
				'Ζωγραφική πάνω σε κεραμικό στο εργαστήριο' => 'Painting a ceramic piece in the studio',
				'κρατήσεις' => 'bookings',
				'κλείσε τη θέση σου' => 'book your place',
				'Διάλεξε πρόγραμμα, ημέρα και ώρα. Θα λάβεις email μόλις ολοκληρωθεί η κράτησή σου.' => 'Pick a programme, a day and a time. You will get an email as soon as your booking is complete.',
				'πρόγραμμα' => 'programme',
				'ημέρα' => 'day',
				'ώρα' => 'time',
				'στοιχεία' => 'details',
				'Πρόγραμμα' => 'Workshop',
				'Ώρα' => 'Time',
				'Η επιλογή σου' => 'Your selection',
				'Άτομα' => 'People',
				'Ονοματεπώνυμο' => 'Full name',
				'Τηλέφωνο' => 'Phone',
				'Κάτι που πρέπει να ξέρουμε;' => 'Anything we should know?',
				'προαιρετικό' => 'optional',
				'Πίσω' => 'Back',
				'Ολοκλήρωση κράτησης' => 'Complete booking',
				'Συμφωνώ να κρατήσετε τα στοιχεία μου για να διαχειριστείτε αυτή την κράτηση.' => 'I agree that you may keep my details in order to manage this booking.',
				'Η θέση σου κρατήθηκε.' => 'Your place is booked.',
				'Σου στείλαμε email με τα στοιχεία. Θα σε περιμένουμε στο εργαστήριο.' => 'We have emailed you the details. We will be waiting for you at the studio.',
				'Αυτή τη στιγμή δεν υπάρχουν διαθέσιμες ημερομηνίες. Γράψε μας και θα βρούμε μαζί μια μέρα.' => 'There are no dates available right now. Write to us and we will find a day together.',
				'Λιγότερα άτομα' => 'Fewer people',
				'Περισσότερα άτομα' => 'More people',

				/* WORKSHOPS — programme names, shown inside the form */
				'Workshop Πηλοπλαστικής' => 'Handbuilding Workshop',
				'Κατασκευές με τα χέρια και με εργαλεία χειρός: pinch pots, μακαρόνι, φύλλο.' => 'Building by hand and with hand tools: pinch pots, coiling, slab building.',
				'περιλαμβάνονται υλικά και εργαλεία' => 'materials and tools included',
				'Workshop Τροχού' => 'Wheel Workshop',
				'Ζύμωμα, κεντράρισμα, άνοιγμα και ανέβασμα τοιχωμάτων στον τροχό.' => 'Wedging, centring, opening and raising walls on the wheel.',
				'Πηλοπλαστική για Παιδιά' => 'Handbuilding for Children',
				'Για παιδιά 6 έως 11 ετών.' => 'For children aged 6 to 11.',
				'Γονέας & Παιδί' => 'Parent & Child',
				'Για παιδιά 6 έως 15 ετών, μαζί με έναν γονέα.' => 'For children aged 6 to 15, together with a parent.',
				'η τιμή είναι για γονέα και παιδί μαζί' => 'the price covers parent and child together',
				'Κυριακάτικο & Κονσεπτικό' => 'Sunday & Concept',
				'Ζωγραφική σε έτοιμο κεραμικό, με ποτό και κέρασμα. Μία ή δύο Κυριακές τον μήνα.' => 'Painting a finished ceramic, with a drink and something to nibble. One or two Sundays a month.',
				'περιλαμβάνεται το κεραμικό, χρώματα, υάλωμα, ψήσιμο και κέρασμα' => 'the ceramic, paints, glazing, firing and refreshments are included',
				/* WORKSHOPS — booking section redesign */
				'Μαθήματα πηλοπλαστικής και τροχού για όσους θέλουν να επιβραδύνουν και να δημιουργήσουν κάτι δικό τους. Όλα τα υλικά, τα εργαλεία και τα ψησίματα περιλαμβάνονται.' => 'Handbuilding and wheel classes for anyone who wants to slow down and make something of their own. All materials, tools and firings are included.',
				'Οι κρατήσεις γίνονται τουλάχιστον 3 ημέρες πριν τη συνάντηση. Όλα τα υλικά και τα ψησίματα περιλαμβάνονται.' => 'Bookings are made at least 3 days before the session. All materials and firings are included.',
				'Κλείσε τη θέση σου' => 'Book your place',
				'Κλείσιμο' => 'Close',
				'Τι θέλεις να κάνεις;' => 'What would you like to make?',
				'Πέντε εργαστήρια, όλα ανοιχτά και σε αρχάριους.' => 'Five workshops, all suitable for beginners.',
				'Διάλεξε ημέρα και ώρα.' => 'Choose a date and time.',
				'Διάλεξε ημέρα.' => 'Choose a date.',
				'Διάλεξε ώρα' => 'Choose a time',
				'Διαθέσιμες ώρες' => 'Available times',
				'Τα στοιχεία σου' => 'Your details',
				'Θα λάβεις email με την επιβεβαίωση.' => 'We will email your confirmation.',
				'Προηγούμενος μήνας' => 'Previous month',
				'Επόμενος μήνας' => 'Next month',
				'1 θέση' => '1 place',
				'%d θέσεις' => '%d places',
				'Αυτές είναι οι διαθέσιμες θέσεις.' => 'That is the maximum available for this session.',
				'Στέλνουμε...' => 'Sending...',
				'Κάτι πήγε στραβά.' => 'Something went wrong.',
				'Δεν υπάρχει σύνδεση. Δοκίμασε ξανά.' => 'No connection. Please try again.',
				'Ευχαριστούμε.' => 'Thank you.',
				'Δοκίμασε ξανά σε λίγο.' => 'Please try again in a moment.',
				'Δεν βρήκαμε αυτό το πρόγραμμα.' => 'We could not find that workshop.',
				'Αυτή η ώρα δεν είναι διαθέσιμη για το συγκεκριμένο πρόγραμμα.' => 'That time is not available for this workshop.',
				'Οι κρατήσεις γίνονται τουλάχιστον %d ημέρες πριν τη συνάντηση.' => 'Bookings must be made at least %d days before the session.',
				'Αυτή η συνάντηση είναι πλήρης. Διάλεξε άλλη ημέρα ή ώρα.' => 'This session is full. Please choose another date or time.',
				'Έμειναν %d θέσεις σε αυτή τη συνάντηση.' => 'Only %d places remain for this session.',
				'Γράψε το ονοματεπώνυμό σου.' => 'Please enter your full name.',
				'Γράψε ένα έγκυρο email.' => 'Please enter a valid email address.',
				'Χρειαζόμαστε τη συγκατάθεσή σου για να κρατήσουμε τα στοιχεία σου.' => 'We need your consent to keep your details for this booking.',
				'Στις τιμές δεν συμπεριλαμβάνεται ΦΠΑ 24%.' => 'Prices exclude 24% VAT.',
				/* WORKSHOPS — the drag gallery, and what its photographs show */
				'μέσα στο εργαστήριο' => 'inside the studio',
				'Χέρια στον τροχό με υγρό πηλό' => 'Hands at the wheel, working wet clay',
				'Βάζα με χρώματα και υαλώματα στο εργαστήριο' => 'Jars of colour and glaze in the studio',
				'Σχεδιασμός πάνω σε κεραμικό' => 'Drawing onto a piece',
				'Το εργαστήριο από ψηλά, με τους πάγκους και τα ράφια' => 'The studio from above, benches and shelves',
				'Ζωγραφική με πινέλο πάνω σε κεραμικό' => 'Painting a piece with a brush',
				'Κρατώντας ένα μεγάλο πιάτο' => 'Holding a large plate',
				'Ράφια με άψητα κεραμικά' => 'Shelves of unfired pieces',
				'Δουλειά στον πάγκο του εργαστηρίου' => 'Working at the studio bench',
				'Η βιτρίνα του εργαστηρίου στα Άνω Πατήσια' => 'The studio window in Ano Patisia',
				/* WORKSHOPS — the card on that photograph */
				'Άνω Πατήσια, Αθήνα' => 'Ano Patisia, Athens',
				'Προμπονά 42' => 'Prompona 42',
				'111 43 Αθήνα' => '111 43 Athens',
				'5 λεπτά με τα πόδια από τον σταθμό Άνω Πατήσια' => 'Five minutes on foot from Ano Patisia station',
				'Οδηγίες' => 'Directions',
				/* WORKSHOPS — the three notes under the statement */
				'χωρίς εμπειρία' => 'no experience needed',
				'το παίρνεις μαζί σου' => 'yours to take home',
				'Η πρώτη φορά στον πηλό' => 'Your first time with clay',

				/* ABOUT — the drag gallery under the hero */
				'Λίγες στιγμές από το εργαστήριο' => 'A few moments from the studio',

				/* ABOUT — the sections added below the manifesto */
				'Ένας χώρος που δουλεύει' => 'A room that works',
				'Ράφια που γεμίζουν και αδειάζουν, πινέλα μέσα σε βάζα, σκόνη στο πάτωμα και γάτες που επιβλέπουν από ψηλά. Εδώ γίνονται όλα, από το πρώτο σκίτσο μέχρι το τελευταίο ψήσιμο.' => 'Shelves that fill and empty, brushes standing in jars, dust on the floor and cats supervising from above. Everything happens here, from the first sketch to the last firing.',
				'Πίσω από τον τροχό' => 'Behind the wheel',
				'Η Ιουλία δουλεύει καθημερινά στο εργαστήριο: πλάθει, ζωγραφίζει, ψήνει και ξαναρχίζει. Ό,τι φεύγει από εδώ έχει περάσει από τα χέρια της.' => "Ioulia works in the studio every day: shaping, painting, firing and starting again. Everything that leaves here has passed through her hands.",
				'Στα χέρια σου' => 'In your hands',
				'Ένα κεραμικό τελειώνει όταν αρχίζει να χρησιμοποιείται. Στο τραπέζι, στην αυλή, σε μια βόλτα έξω από την πόλη. Εκεί παίρνει το νόημά του.' => 'A piece is only finished once it is being used. On the table, in the yard, on a day out of the city. That is where it finds its meaning.',
				'Έλα από το εργαστήριο' => 'Come by the studio',
				'Άνω Πατήσια, Αθήνα. Χτύπα την πόρτα, δες τη δουλειά από κοντά ή γράψου σε ένα μάθημα.' => 'Ano Patisia, Athens. Knock on the door, see the work up close, or sign up for a class.',
				/* The button says 'Επικοινωνία', which the navigation above already carries. */

				/* ABOUT — what the photographs show, for anyone who cannot see them */
				'Το εργαστήριο με τα ράφια και το τραπέζι δουλειάς' => 'The studio, with its shelves and work table',
				'Ο πάγκος με τα πινέλα και μια γάτα στο τραπέζι' => 'The bench, with brushes and a cat on the table',
				'Χέρια ζωγραφίζουν ένα κεραμικό με πινέλο' => 'Hands painting a piece with a brush',
				'Αντικείμενο πάνω στον τροχό' => 'A piece on the wheel',
				'Η Ιουλία στο εργαστήριο' => 'Ioulia in the studio',
				'Η Ιουλία στον τροχό' => 'Ioulia at the wheel',
				'Η Ιουλία ανάμεσα στα κομμάτια της' => 'Ioulia among her pieces',
				'Κούπα ζωγραφισμένη στο χέρι πάνω στον τροχό' => 'A hand-painted cup on the wheel',
				'Γλάστρες σε σχήμα κεφαλιού με λουλούδια' => 'Head-shaped planters holding flowers',
				'Κούπα σε χρήση έξω, στη φύση' => 'A cup in use outdoors',
				'Δύο άνθρωποι πίνουν από χειροποίητες κούπες' => 'Two people drinking from handmade cups',
				'Η βιτρίνα του εργαστηρίου' => 'The studio window from the street',

				/* CART & CHECKOUT — the page headings. Everything else on these two
				   screens is WooCommerce's own wording and comes from its language pack. */
				'Το καλάθι σου' => 'Your cart',
				'Ολοκλήρωση παραγγελίας' => 'Checkout',
				'Καλάθι' => 'Cart',
				'Ταμείο' => 'Checkout',

				/* CONTACT — headings, one per step */
				'Ας φτιάξουμε κάτι μαζί' => "Let's shape something together",
				'Τι κεραμικό φαντάζεσαι;' => 'What kind of piece do you have in mind?',
				'Μέγεθος και υάλωμα' => 'Scale and glaze',
				'Περίγραψέ το μας' => 'Describe it for us',
				'Πες μας περισσότερα' => 'Tell us more',
				'Πού να σου απαντήσουμε;' => 'Where should we reply?',
				'Ευχαριστούμε!' => 'Thank you!',

				/* CONTACT — the two routes */
				'Κεραμικό κατά παραγγελία' => 'A commissioned piece',
				'Γενική ερώτηση ή επίσκεψη στο εργαστήριο' => 'A question or a studio visit',

				/* CONTACT — what the piece is */
				'Βάζο' => 'Vase',
				'Μπολ ή πιατέλα' => 'Bowl or platter',
				'Σερβίτσιο' => 'Dinnerware set',
				'Γλυπτό αντικείμενο' => 'Sculptural object',
				'Αρχιτεκτονική επιφάνεια' => 'Architectural surface',
				'Κάτι άλλο' => 'Something else',

				/* CONTACT — scale */
				'Μέγεθος κατά προσέγγιση' => 'Approximate scale',
				'Μικρό' => 'Small',
				'Μεσαίο' => 'Medium',
				'Μεγάλο' => 'Large',
				'έως 20 εκ.' => 'up to 20cm',
				'20–40 εκ.' => '20-40cm',
				'πάνω από 40 εκ.' => 'over 40cm',

				/* CONTACT — fields */
				'Υάλωμα ή χρώμα' => 'Glaze or colour',
				'π.χ. ματ κρεμ, σκούρος πηλός με υφή' => 'e.g. matte cream, dark textured clay',
				'Περιγραφή και ιδέες' => 'Description and ideas',
				'Πες μας για τον χώρο, την ιδέα ή κάτι συγκεκριμένο' => 'Tell us about the space, the idea, or anything specific',
				'Γράψε μας λίγα λόγια για το κομμάτι.' => 'Please tell us a little about the piece.',
				'Θέμα' => 'Subject',
				'Διάλεξε θέμα' => 'Choose a subject',
				'Μαθήματα κεραμικής' => 'Pottery classes',
				'Επίσκεψη στο εργαστήριο, Άνω Πατήσια' => 'Studio visit in Ano Patisia',
				'Συνεργασία ή Τύπος' => 'Collaboration or press',
				'Γενική ερώτηση' => 'General question',
				'Το μήνυμά σου' => 'Your message',
				'Πώς μπορούμε να βοηθήσουμε;' => 'How can we help?',
				'Γράψε μας το μήνυμά σου.' => 'Please write your message.',
				'Το όνομά σου' => 'Your name',
				'(προαιρετικό)' => '(optional)',

				/* CONTACT — consent, sending, and what comes after */
				'Συμφωνώ να χρησιμοποιηθούν τα στοιχεία μου για να απαντηθεί αυτό το μήνυμα. Στέλνονται με email στο εργαστήριο και δεν αποθηκεύονται σε αυτό το site.' => 'I agree to my details being used to answer this message. They are emailed to the studio and are not stored on this site.',
				'Χρειαζόμαστε τη συγκατάθεσή σου για να στείλουμε το μήνυμα.' => 'We need your consent before sending.',
				'Άφησε αυτό το πεδίο κενό' => 'Leave this field empty',
				'Αποστολή' => 'Send',
				'Δεν μπορέσαμε να στείλουμε το μήνυμά σου. Δοκίμασε ξανά ή γράψε μας στο info@iouliageraskliceramics.com.' => 'We could not send your message. Please try again, or write to us at info@iouliageraskliceramics.com.',
				'Η φόρμα δεν είναι συνδεδεμένη ακόμη. Γράψε μας στο info@iouliageraskliceramics.com.' => 'The form is not connected yet. Please write to us at info@iouliageraskliceramics.com.',
				'Το μήνυμά σου έφτασε. Διαβάζουμε κάθε αίτημα προσωπικά και θα σου απαντήσουμε σύντομα.' => 'Your message has arrived. We read every request personally and will be in touch shortly.',
				'Ακολούθησέ μας στο Instagram' => 'Follow us on Instagram',
				'Στείλε νέο μήνυμα' => 'Send another message',
				'Προτιμάς απευθείας επικοινωνία;' => 'Prefer to get in touch directly?',

				/* CONTACT — chrome the screen reader reads */
				'Επικοινωνία και παραγγελία κεραμικού' => 'Contact and commission enquiries',
				'Πρόοδος φόρμας' => 'Form progress',
				'Για τι πρόκειται;' => 'What is this about?',
				'Είδος κεραμικού' => 'Type of piece',

				/* 'Πίσω', 'Ονοματεπώνυμο' and 'Τηλέφωνο' are already carried by
				   the booking dialog above and translate the same way here. */
				'Συνέχεια' => 'Continue',

				/* WORKSHOPS — dialog chrome */
				'Δημοφιλές' => 'Popular',
				'Επόμενο' => 'Next',
			)
		);
	}
	add_filter( 'ioulia_i18n_seed', 'ioulia_i18n_seed_en', 10, 2 );
}
