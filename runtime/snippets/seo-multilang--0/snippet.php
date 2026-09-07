<?php
/**
 * Search metadata for the Greek-first, bilingual public site.
 *
 * The IGC multilingual layer owns language routing and hreflang. This snippet
 * adds language-specific titles, descriptions, social cards, clean canonicals,
 * structured business data and a sitemap that excludes transactional pages.
 */

if ( ! function_exists( 'ioulia_seo_pages' ) ) {
	function ioulia_seo_pages() {
		return array(
			'home' => array(
				'el_title' => 'Χειροποίητα κεραμικά στην Αθήνα | Ioulia Geraskli Ceramics',
				'en_title' => 'Handmade Ceramics in Athens | Ioulia Geraskli Ceramics',
				'el_desc'  => 'Χειροποίητα κεραμικά αντικείμενα και βιωματικά εργαστήρια κεραμικής στο Ioulia Geraskli Ceramic Lab στην Αθήνα.',
				'en_desc'  => 'Handmade ceramic objects and hands-on pottery workshops at Ioulia Geraskli Ceramic Lab in Athens, Greece.',
			),
			'about' => array(
				'el_title' => 'Το Ceramic Lab και η φιλοσοφία μας | Ioulia Geraskli',
				'en_title' => 'The Ceramic Lab and Our Philosophy | Ioulia Geraskli',
				'el_desc'  => 'Γνώρισε το σύγχρονο ceramic lab της Ioulia Geraskli στην Αθήνα και τη χειροποίητη διαδικασία πίσω από κάθε μοναδικό κεραμικό.',
				'en_desc'  => 'Discover Ioulia Geraskli Ceramic Lab in Athens and the slow, hands-on process behind every one-of-a-kind ceramic piece.',
			),
			'workshops' => array(
				'el_title' => 'Workshops κεραμικής στην Αθήνα | Ioulia Geraskli',
				'en_title' => 'Pottery Workshops in Athens | Ioulia Geraskli',
				'el_desc'  => 'Μαθήματα πηλοπλαστικής και τροχού στα Άνω Πατήσια για αρχάριους, παιδιά και οικογένειες. Υλικά, εργαλεία και ψησίματα περιλαμβάνονται.',
				'en_desc'  => 'Handbuilding and wheel-throwing workshops in Ano Patisia, Athens, for beginners, children and families. Materials and firings included.',
			),
			'mathimata-piloplastikis-athina' => array(
				'el_title' => 'Μαθήματα Πηλοπλαστικής στην Αθήνα | Ioulia Geraskli',
				'en_title' => 'Handbuilding Pottery Classes in Athens | Ioulia Geraskli',
				'el_desc'  => 'Μάθε πηλοπλαστική με pinch pots, μακαρόνι και φύλλο στο ceramic lab μας στα Άνω Πατήσια. Για αρχάριους, με υλικά και ψησίματα.',
				'en_desc'  => 'Learn handbuilding with pinch pots, coiling and slab building at our Ano Patisia ceramic studio. Beginner-friendly, with materials and firings included.',
			),
			'mathimata-troxou-athina' => array(
				'el_title' => 'Μαθήματα Κεραμικού Τροχού στην Αθήνα | Ioulia Geraskli',
				'en_title' => 'Pottery Wheel Classes in Athens | Ioulia Geraskli',
				'el_desc'  => 'Μαθήματα κεραμικού τροχού στα Άνω Πατήσια: ζύμωμα, κεντράρισμα και βασικές φόρμες. Για αρχάριους, με online κράτηση.',
				'en_desc'  => 'Pottery wheel classes in Ano Patisia, Athens: wedging, centring and essential forms. Beginner-friendly sessions with easy online booking.',
			),
			'keramiki-gia-paidia-athina' => array(
				'el_title' => 'Μαθήματα Κεραμικής για Παιδιά στην Αθήνα | Ioulia Geraskli',
				'en_title' => 'Pottery Classes for Children in Athens | Ioulia Geraskli',
				'el_desc'  => 'Δημιουργικά μαθήματα κεραμικής για παιδιά 6–11 ετών στα Άνω Πατήσια. Πηλός, χρώμα, ελεύθερη έκφραση και όλα τα υλικά.',
				'en_desc'  => 'Creative pottery classes for children aged 6–11 in Ano Patisia, Athens, with clay, colour, open-ended making and all materials included.',
			),
			'keramiki-goneas-paidi-athina' => array(
				'el_title' => 'Κεραμική για Γονείς και Παιδιά στην Αθήνα | Ioulia Geraskli',
				'en_title' => 'Parent and Child Pottery in Athens | Ioulia Geraskli',
				'el_desc'  => 'Workshop κεραμικής για ένα παιδί 6–15 ετών και έναν γονέα στα Άνω Πατήσια. Δημιουργήστε μαζί ένα μοναδικό αντικείμενο.',
				'en_desc'  => 'A pottery workshop in Ano Patisia for one child aged 6–15 and one parent. Slow down, collaborate and make one ceramic object together.',
			),
			'zografiki-keramikou-athina' => array(
				'el_title' => 'Ζωγραφική Κεραμικού στην Αθήνα | Ioulia Geraskli',
				'en_title' => 'Ceramic Painting Workshop in Athens | Ioulia Geraskli',
				'el_desc'  => 'Κυριακάτικο workshop ζωγραφικής σε έτοιμο κεραμικό στα Άνω Πατήσια, με ποτό και κέρασμα. Υάλωμα και ψήσιμο περιλαμβάνονται.',
				'en_desc'  => 'A Sunday ceramic painting workshop in Ano Patisia, Athens, with a drink and themed treats. Glazing and firing are included.',
			),
			'book-workshop' => array(
				'el_title' => 'Κράτηση workshop κεραμικής | Ioulia Geraskli',
				'en_title' => 'Book a Pottery Workshop | Ioulia Geraskli',
				'el_desc'  => 'Διάλεξε πρόγραμμα, διαθέσιμη ημέρα και ώρα και κλείσε τη θέση σου σε workshop κεραμικής στο εργαστήριό μας στην Αθήνα.',
				'en_desc'  => 'Choose a workshop, available date and time, and book your pottery session at our ceramics studio in Athens.',
			),
			'shop' => array(
				'el_title' => 'Χειροποίητα κεραμικά αντικείμενα | Ioulia Geraskli',
				'en_title' => 'Handmade Ceramic Objects | Ioulia Geraskli',
				'el_desc'  => 'Ανακάλυψε χειροποίητα κεραμικά χρηστικά και διακοσμητικά αντικείμενα, σχεδιασμένα και ζωγραφισμένα στο χέρι στην Αθήνα.',
				'en_desc'  => 'Shop handmade functional and decorative ceramics, individually shaped and painted by hand in our Athens ceramic lab.',
			),
			'contact' => array(
				'el_title' => 'Επικοινωνία και custom κεραμικά | Ioulia Geraskli',
				'en_title' => 'Contact and Custom Ceramics | Ioulia Geraskli',
				'el_desc'  => 'Επικοινώνησε με το Ioulia Geraskli Ceramic Lab για custom κεραμικά, συνεργασίες ή πληροφορίες για το εργαστήριο στην Αθήνα.',
				'en_desc'  => 'Contact Ioulia Geraskli Ceramic Lab about custom ceramics, collaborations or visiting our ceramics studio in Athens.',
			),
			'privacy-policy' => array(
				'el_title' => 'Πολιτική Απορρήτου | Ioulia Geraskli Ceramics',
				'en_title' => 'Privacy Policy | Ioulia Geraskli Ceramics',
				'el_desc'  => 'Πώς το Ioulia Geraskli Ceramics συλλέγει, χρησιμοποιεί και προστατεύει τα προσωπικά δεδομένα των επισκεπτών και πελατών.',
				'en_desc'  => 'How Ioulia Geraskli Ceramics collects, uses and protects the personal data of website visitors and customers.',
			),
			'terms' => array(
				'el_title' => 'Όροι Χρήσης και Πώλησης | Ioulia Geraskli Ceramics',
				'en_title' => 'Terms of Use and Sale | Ioulia Geraskli Ceramics',
				'el_desc'  => 'Οι όροι χρήσης, αγορών, πληρωμών και παραγγελιών του ηλεκτρονικού καταστήματος Ioulia Geraskli Ceramics.',
				'en_desc'  => 'Terms governing use, purchases, payments and orders through the Ioulia Geraskli Ceramics online shop.',
			),
			'shipping-returns' => array(
				'el_title' => 'Αποστολές και Επιστροφές | Ioulia Geraskli Ceramics',
				'en_title' => 'Shipping and Returns | Ioulia Geraskli Ceramics',
				'el_desc'  => 'Πληροφορίες για αποστολές, παραδόσεις, αλλαγές και επιστροφές χειροποίητων κεραμικών από το ηλεκτρονικό μας κατάστημα.',
				'en_desc'  => 'Information about delivery, exchanges and returns for handmade ceramics purchased from our online shop.',
			),
			'cookies' => array(
				'el_title' => 'Πολιτική Cookies | Ioulia Geraskli Ceramics',
				'en_title' => 'Cookie Policy | Ioulia Geraskli Ceramics',
				'el_desc'  => 'Πληροφορίες για τα cookies που χρησιμοποιεί το iouliageraskliceramics.com και τις διαθέσιμες επιλογές συγκατάθεσης.',
				'en_desc'  => 'Information about cookies used by iouliageraskliceramics.com and the consent choices available to visitors.',
			),
		);
	}
}

if ( ! function_exists( 'ioulia_seo_page_key' ) ) {
	function ioulia_seo_page_key() {
		if ( is_front_page() ) {
			return 'home';
		}

		if ( function_exists( 'is_shop' ) && is_shop() ) {
			return 'shop';
		}

		return is_page() ? (string) get_post_field( 'post_name', get_queried_object_id() ) : '';
	}
}

if ( ! function_exists( 'ioulia_seo_meta' ) ) {
	function ioulia_seo_meta() {
		$pages = ioulia_seo_pages();
		$key   = ioulia_seo_page_key();

		if ( isset( $pages[ $key ] ) ) {
			return $pages[ $key ];
		}

		if ( is_tax( 'product_collection' ) || ( function_exists( 'is_product_tag' ) && is_product_tag( 'one-and-only' ) ) ) {
			$term = get_queried_object();
			if ( $term && ! is_wp_error( $term ) ) {
				$name    = wp_strip_all_tags( $term->name );
				$el_desc = (string) $term->description;
				$en_desc = (string) get_term_meta( $term->term_id, 'igpa_description_en', true );

				return array(
					'el_title' => $name . ' — Χειροποίητα Κεραμικά | Ioulia Geraskli',
					'en_title' => $name . ' — Handmade Ceramics | Ioulia Geraskli',
					'el_desc'  => '' !== $el_desc ? $el_desc : 'Ανακάλυψε τη συλλογή ' . $name . ' με χειροποίητα κεραμικά από το Ioulia Geraskli Ceramic Lab στην Αθήνα.',
					'en_desc'  => '' !== $en_desc ? $en_desc : 'Discover the ' . $name . ' collection of handmade ceramics from Ioulia Geraskli Ceramic Lab in Athens.',
				);
			}
		}

		if ( is_singular( 'product' ) ) {
			$name = wp_strip_all_tags( get_the_title( get_queried_object_id() ) );
			$name = trim( str_replace( '_', '', $name ) );

			return array(
				'el_title' => $name . ' — Χειροποίητο κεραμικό | Ioulia Geraskli',
				'en_title' => $name . ' — Handmade Ceramic | Ioulia Geraskli',
				'el_desc'  => 'Χειροποίητο κεραμικό ' . $name . ', σχεδιασμένο, πλασμένο και ζωγραφισμένο στο χέρι στο Ioulia Geraskli Ceramic Lab στην Αθήνα. Ανακάλυψε αυθεντικά κεραμικά αντικείμενα.',
				'en_desc'  => $name . ' is a one-of-a-kind ceramic piece, designed, shaped and painted by hand at Ioulia Geraskli Ceramic Lab in Athens. Discover original handmade ceramics.',
			);
		}

		return array();
	}
}

if ( ! function_exists( 'ioulia_seo_value' ) ) {
	function ioulia_seo_value( $field ) {
		$meta = ioulia_seo_meta();
		$lang = function_exists( 'ioulia_lang' ) ? ioulia_lang() : 'el';
		$key  = $lang . '_' . $field;

		return isset( $meta[ $key ] ) ? (string) $meta[ $key ] : '';
	}
}

if ( ! function_exists( 'ioulia_seo_title' ) ) {
	function ioulia_seo_title( $title ) {
		$custom = ioulia_seo_value( 'title' );

		return '' !== $custom ? $custom : $title;
	}
	add_filter( 'pre_get_document_title', 'ioulia_seo_title', 20 );
	add_filter( 'seopress_titles_title', 'ioulia_seo_title', 20 );
}

if ( ! function_exists( 'ioulia_seo_canonical' ) ) {
	function ioulia_seo_canonical() {
		if ( is_404() || is_search() || is_admin() ) {
			return '';
		}

		if ( function_exists( 'ioulia_alternate_url' ) && function_exists( 'ioulia_lang' ) ) {
			return ioulia_alternate_url( ioulia_lang() );
		}

		if ( is_singular() ) {
			return get_permalink( get_queried_object_id() );
		}

		$url = home_url( add_query_arg( array(), $GLOBALS['wp']->request ) );
		if ( isset( $_GET['catalog-page'] ) && absint( $_GET['catalog-page'] ) > 1 ) {
			$url = add_query_arg( 'catalog-page', absint( $_GET['catalog-page'] ), $url );
		}

		return $url;
	}
}

if ( ! function_exists( 'ioulia_seo_head' ) ) {
	function ioulia_seo_head() {
		if ( is_admin() || is_404() || is_search() ) {
			return;
		}

		$title       = ioulia_seo_title( wp_get_document_title() );
		$description = ioulia_seo_value( 'desc' );
		$canonical   = ioulia_seo_canonical();
		$lang        = function_exists( 'ioulia_lang' ) ? ioulia_lang() : 'el';
		$locale      = 'en' === $lang ? 'en_US' : 'el_GR';
		$alternate   = 'en' === $lang ? 'el_GR' : 'en_US';
		$type        = is_singular( 'product' ) ? 'product' : 'website';

		if ( '' !== $description ) {
			echo '<meta name="description" content="' . esc_attr( $description ) . '">' . chr( 10 );
		}

		if ( '' !== $canonical ) {
			echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . chr( 10 );
		}

		echo '<meta property="og:type" content="' . esc_attr( $type ) . '">' . chr( 10 );
		echo '<meta property="og:site_name" content="Ioulia Geraskli Ceramics">' . chr( 10 );
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . chr( 10 );
		echo '<meta property="og:url" content="' . esc_url( $canonical ) . '">' . chr( 10 );
		echo '<meta property="og:locale" content="' . esc_attr( $locale ) . '">' . chr( 10 );
		echo '<meta property="og:locale:alternate" content="' . esc_attr( $alternate ) . '">' . chr( 10 );

		if ( '' !== $description ) {
			echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . chr( 10 );
			echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . chr( 10 );
		}

		echo '<meta name="twitter:card" content="summary_large_image">' . chr( 10 );
		echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . chr( 10 );

		if ( is_singular() && has_post_thumbnail( get_queried_object_id() ) ) {
			$image = wp_get_attachment_image_url( get_post_thumbnail_id( get_queried_object_id() ), 'full' );

			if ( $image ) {
				echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . chr( 10 );
				echo '<meta name="twitter:image" content="' . esc_url( $image ) . '">' . chr( 10 );
			}
		}
	}
	remove_action( 'wp_head', 'rel_canonical' );
	add_action( 'wp_head', 'ioulia_seo_head', 2 );
}

if ( ! function_exists( 'ioulia_seo_exclude_consent_copy_from_snippets' ) ) {
	/**
	 * CookieYes is injected after the initial HTML, so mark its rendered UI as
	 * ineligible for Google snippets as soon as it appears. Consent remains fully
	 * visible and accessible to visitors; this affects search excerpts only.
	 */
	function ioulia_seo_exclude_consent_copy_from_snippets() {
		if ( is_admin() ) {
			return;
		}
		?>
		<script id="ioulia-cookie-nosnippet">
		(function () {
			var selector = '.cky-consent-container, .cky-modal, .cky-btn-revisit-wrapper';
			var protect = function (root) {
				if (root.nodeType === 1 && root.matches && root.matches(selector)) {
					root.setAttribute('data-nosnippet', '');
				}
				if (root.querySelectorAll) {
					root.querySelectorAll(selector).forEach(function (node) {
						node.setAttribute('data-nosnippet', '');
					});
				}

				return Boolean(document.querySelector('.cky-consent-container'));
			};

			if (protect(document)) {
				return;
			}

			var observer = new MutationObserver(function (records) {
				records.forEach(function (record) {
					record.addedNodes.forEach(function (node) {
						if (protect(node)) {
							observer.disconnect();
						}
					});
				});
			});
			observer.observe(document.documentElement, { childList: true, subtree: true });
		}());
		</script>
		<?php
	}
	add_action( 'wp_head', 'ioulia_seo_exclude_consent_copy_from_snippets', 1 );
}

if ( ! function_exists( 'ioulia_seo_robots' ) ) {
	function ioulia_seo_robots( $robots ) {
		$key = ioulia_seo_page_key();

		$thin_shop_archive = ( function_exists( 'is_product_category' ) && is_product_category() ) || ( function_exists( 'is_product_tag' ) && is_product_tag() && ! is_product_tag( 'one-and-only' ) );
		$filtered_catalog = isset( $_GET['catalog-category'] ) || isset( $_GET['catalog-collection'] ) || isset( $_GET['catalog-sort'] );

		if ( is_search() || is_404() || $thin_shop_archive || $filtered_catalog || in_array( $key, array( 'cart', 'checkout', 'my-account', 'kratiseis', 'cancel-booking', 'coming-soon' ), true ) ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
			unset( $robots['index'] );
		}

		return $robots;
	}
	add_filter( 'wp_robots', 'ioulia_seo_robots', 50 );
}

if ( ! function_exists( 'ioulia_seo_sitemap_pages' ) ) {
	function ioulia_seo_private_page_ids() {
		return get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'post_name__in'  => array( 'cart', 'checkout', 'my-account', 'kratiseis', 'cancel-booking', 'coming-soon' ),
			)
		);
	}

	function ioulia_seo_sitemap_pages( $args, $post_type ) {
		if ( 'page' !== $post_type ) {
			return $args;
		}

		$excluded = ioulia_seo_private_page_ids();

		$args['post__not_in'] = array_values( array_unique( array_merge( isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array(), $excluded ) ) );

		return $args;
	}
	add_filter( 'wp_sitemaps_posts_query_args', 'ioulia_seo_sitemap_pages', 20, 2 );
}

if ( ! function_exists( 'ioulia_seo_secondary_sitemap_query' ) ) {
	function ioulia_seo_secondary_sitemap_query( $query ) {
		$post_types = (array) $query->get( 'post_type' );

		if ( 2000 !== (int) $query->get( 'posts_per_page' ) || 'ids' !== $query->get( 'fields' ) || ! in_array( 'page', $post_types, true ) || ! in_array( 'product', $post_types, true ) ) {
			return;
		}

		$query->set( 'post__not_in', ioulia_seo_private_page_ids() );
	}
	add_action( 'pre_get_posts', 'ioulia_seo_secondary_sitemap_query', 20 );
}

if ( ! function_exists( 'ioulia_seo_sitemap_provider' ) ) {
	function ioulia_seo_sitemap_provider( $provider, $name ) {
		return 'users' === $name ? false : $provider;
	}
	add_filter( 'wp_sitemaps_add_provider', 'ioulia_seo_sitemap_provider', 20, 2 );

	function ioulia_seo_sitemap_taxonomies( $taxonomies ) {
		foreach ( array_keys( $taxonomies ) as $taxonomy ) {
			if ( ! in_array( $taxonomy, array( 'product_collection', 'product_tag' ), true ) ) {
				unset( $taxonomies[ $taxonomy ] );
			}
		}

		return $taxonomies;
	}
	add_filter( 'wp_sitemaps_taxonomies', 'ioulia_seo_sitemap_taxonomies', 20 );

	function ioulia_seo_sitemap_taxonomy_terms( $args, $taxonomy ) {
		$slugs = 'product_collection' === $taxonomy
			? array( 'beach-stories', 'garden-table', 'naked-forms' )
			: array( 'one-and-only' );
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
				'slug'       => $slugs,
				'fields'     => 'ids',
			)
		);

		$args['include'] = is_wp_error( $terms ) ? array( 0 ) : array_map( 'absint', $terms );
		return $args;
	}
	add_filter( 'wp_sitemaps_taxonomies_query_args', 'ioulia_seo_sitemap_taxonomy_terms', 20, 2 );
}

if ( ! function_exists( 'ioulia_seo_sitemap_status' ) ) {
	function ioulia_seo_sitemap_status( $preempt, $query ) {
		if ( get_query_var( 'sitemap' ) || get_query_var( 'sitemap-stylesheet' ) ) {
			return true;
		}

		return $preempt;
	}
	add_filter( 'pre_handle_404', 'ioulia_seo_sitemap_status', 20, 2 );
}

if ( ! function_exists( 'ioulia_seo_schema' ) ) {
	function ioulia_seo_schema_translate( $text ) {
		if ( ! function_exists( 'ioulia_lang' ) || 'en' !== ioulia_lang() || ! function_exists( 'ioulia_lookup_translation' ) ) {
			return $text;
		}

		$translated = ioulia_lookup_translation( 'en', $text );

		return '' !== $translated ? $translated : $text;
	}

	function ioulia_seo_workshop_faq() {
		$entries = array(
			array(
				'Χρειάζομαι προηγούμενη εμπειρία;',
				'Όχι, καθόλου. Όλα τα workshops μας είναι ανοιχτά τόσο σε απόλυτα αρχάριους όσο και σε άτομα που έχουν ήδη εξοικείωση με τον πηλό.',
			),
			array(
				'Πώς κάνω κράτηση και πόσο νωρίτερα;',
				'Επιλέγετε το πρόγραμμα και την ώρα που σας εξυπηρετεί στη σελίδα κρατήσεων. Για την καλύτερη οργάνωση του εργαστηρίου, είναι απαραίτητο να κλείνετε τη θέση σας τουλάχιστον 3 ημέρες πριν. Η επιβεβαίωση αποστέλλεται με email.',
			),
			array(
				'Τι περιλαμβάνεται στην τιμή;',
				'Στο κόστος περιλαμβάνονται όλα τα υλικά (πηλός, χρώματα, υαλώματα), η χρήση των εργαλείων και του τροχού, καθώς και τα ψησίματα στο καμίνι μας. (Στις αναγραφόμενες τιμές δεν συμπεριλαμβάνεται ΦΠΑ 24%).',
			),
			array(
				'Πότε παραλαμβάνω τα κεραμικά μου;',
				'Τα αντικείμενα που φτιάχνετε χρειάζονται χρόνο για να στεγνώσουν αργά, να ψηθούν (μπισκουί), να υαλωθούν και να ψηθούν ξανά σε υψηλή θερμοκρασία. Θα είναι έτοιμα και πλήρως λειτουργικά για παραλαβή σε περίπου 1–2 εβδομάδες (στο Κυριακάτικο σε 1 εβδομάδα).',
			),
			array(
				'Μπορώ να κλείσω θέση για περισσότερα άτομα;',
				'Ναι. Στη φόρμα κράτησης μπορείτε να επιλέξετε τον αριθμό των συμμετεχόντων. Η διαθεσιμότητα εξαρτάται από τον μέγιστο αριθμό θέσεων της εκάστοτε συνάντησης.',
			),
		);
		$questions = array();

		foreach ( $entries as $entry ) {
			$questions[] = array(
				'@type'          => 'Question',
				'name'           => ioulia_seo_schema_translate( $entry[0] ),
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => ioulia_seo_schema_translate( $entry[1] ),
				),
			);
		}

		return array(
			'@type'      => 'FAQPage',
			'@id'        => ioulia_seo_canonical() . '#faq',
			'inLanguage' => function_exists( 'ioulia_lang' ) && 'en' === ioulia_lang() ? 'en-US' : 'el-GR',
			'mainEntity' => $questions,
		);
	}

	function ioulia_seo_workshop_courses() {
		if ( ! function_exists( 'ioulia_workshop_active_programmes' ) ) {
			return array();
		}

		$day_names = array(
			1 => 'https://schema.org/Monday',
			2 => 'https://schema.org/Tuesday',
			3 => 'https://schema.org/Wednesday',
			4 => 'https://schema.org/Thursday',
			5 => 'https://schema.org/Friday',
			6 => 'https://schema.org/Saturday',
			7 => 'https://schema.org/Sunday',
		);
		$language   = function_exists( 'ioulia_lang' ) && 'en' === ioulia_lang() ? 'en-US' : 'el-GR';
		$workshops  = function_exists( 'ioulia_url' ) ? ioulia_url( 'workshops/' ) : home_url( '/workshops/' );
		$booking    = function_exists( 'ioulia_url' ) ? ioulia_url( 'book-workshop/' ) : home_url( '/book-workshop/' );
		$items      = array();
		$position   = 1;
		$landing_pages = function_exists( 'ioulia_workshop_landing_slugs' ) ? ioulia_workshop_landing_slugs() : array();

		foreach ( ioulia_workshop_active_programmes() as $slug => $programme ) {
			$instances = array();
			$course_url = isset( $landing_pages[ $slug ] )
				? ( function_exists( 'ioulia_url' ) ? ioulia_url( '/' . $landing_pages[ $slug ] . '/' ) : home_url( '/' . $landing_pages[ $slug ] . '/' ) )
				: $workshops . '#workshop-' . $slug;

			foreach ( (array) $programme['sessions'] as $session ) {
				if ( ! isset( $day_names[ $session['day'] ] ) ) {
					continue;
				}

				$instances[] = array(
					'@type'          => 'CourseInstance',
					'courseMode'     => 'onsite',
					'location'       => array( '@id' => untrailingslashit( (string) get_option( 'home' ) ) . '/#studio' ),
					'courseSchedule' => array(
						'@type'           => 'Schedule',
						'repeatFrequency' => 'P1W',
						'byDay'           => $day_names[ $session['day'] ],
						'startTime'       => $session['start'],
						'endTime'         => $session['end'],
						'scheduleTimezone'=> 'Europe/Athens',
					),
					'offers'          => array(
						'@type'         => 'Offer',
						'url'           => $booking,
						'price'         => (string) $programme['price'],
						'priceCurrency' => 'EUR',
						'availability'  => 'https://schema.org/InStock',
					),
				);
			}

			$course = array(
				'@type'               => 'Course',
				'@id'                 => $course_url . '#course',
				'name'                => ioulia_seo_schema_translate( $programme['title'] ),
				'description'         => ioulia_seo_schema_translate( $programme['summary'] ),
				'url'                 => $course_url,
				'inLanguage'          => $language,
				'provider'            => array( '@id' => untrailingslashit( (string) get_option( 'home' ) ) . '/#organization' ),
				'coursePrerequisites' => ioulia_seo_schema_translate( 'Δεν απαιτείται προηγούμενη εμπειρία.' ),
				'educationalLevel'    => 'Beginner',
				'isAccessibleForFree' => false,
				'offers'              => array(
					'@type'         => 'Offer',
					'url'           => $booking,
					'price'         => (string) $programme['price'],
					'priceCurrency' => 'EUR',
					'availability'  => 'https://schema.org/InStock',
				),
			);

			if ( ! empty( $instances ) ) {
				$course['hasCourseInstance'] = $instances;
			}

			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $position,
				'url'      => $course['url'],
				'item'     => $course,
			);
			$position++;
		}

		return array(
			'@type'           => 'ItemList',
			'@id'             => $workshops . '#workshop-programmes',
			'name'            => ioulia_seo_schema_translate( 'Μαθήματα κεραμικής στην Αθήνα' ),
			'numberOfItems'   => count( $items ),
			'itemListElement' => $items,
		);
	}

	function ioulia_seo_schema() {
		if ( is_admin() || is_404() || is_search() ) {
			return;
		}

		$home      = untrailingslashit( (string) get_option( 'home' ) ) . '/';
		$canonical = ioulia_seo_canonical();
		$language  = function_exists( 'ioulia_lang' ) && 'en' === ioulia_lang() ? 'en-US' : 'el-GR';
		$booking   = function_exists( 'ioulia_url' ) ? ioulia_url( 'book-workshop/' ) : home_url( '/book-workshop/' );
		$returns   = function_exists( 'ioulia_url' ) ? ioulia_url( 'shipping-returns/' ) : home_url( '/shipping-returns/' );
		$thin_shop_archive = ( function_exists( 'is_product_category' ) && is_product_category() ) || ( function_exists( 'is_product_tag' ) && is_product_tag() && ! is_product_tag( 'one-and-only' ) );
		$schema = array(
			'@context' => 'https://schema.org',
			'@graph'   => array(
				array(
					'@type'    => array( 'Organization', 'LocalBusiness', 'Store' ),
					'@id'      => $home . '#organization',
					'name'     => 'Ioulia Geraskli Ceramics',
					'url'      => $home,
					'email'    => 'info@iouliageraskliceramics.com',
					'telephone'=> '+302102514658',
					'address'  => array(
						'@type'           => 'PostalAddress',
						'@id'             => $home . '#address',
						'streetAddress'   => 'Προμπονά 42',
						'addressLocality' => 'Αθήνα',
						'postalCode'      => '111 43',
						'addressCountry'  => 'GR',
					),
					'geo'      => array(
						'@type'     => 'GeoCoordinates',
						'latitude'  => 38.0289084,
						'longitude' => 23.7389517,
					),
					'hasMap'   => 'https://www.google.com/maps/search/?api=1&query=38.0289084,23.7389517',
					'areaServed'=> array(
						'@type' => 'City',
						'name'  => 'Athens',
					),
					'sameAs'   => array(
						'https://www.instagram.com/iouliageraskli/',
						'https://www.facebook.com/p/Ioulia-Geraskli-Ceramic-Lab-100068617400520/',
					),
					'priceRange' => '€€',
					'currenciesAccepted' => 'EUR',
					'hasMerchantReturnPolicy' => array(
						'@type'                => 'MerchantReturnPolicy',
						'@id'                  => $home . '#return-policy',
						'merchantReturnLink'   => $returns,
						'returnPolicyCountry'  => 'GR',
						'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
						'merchantReturnDays'   => 14,
						'itemCondition'        => 'https://schema.org/NewCondition',
						'returnMethod'          => 'https://schema.org/ReturnByMail',
						'returnFees'            => 'https://schema.org/ReturnFeesCustomerResponsibility',
						'refundType'            => 'https://schema.org/FullRefund',
						'returnLabelSource'     => 'https://schema.org/ReturnLabelCustomerResponsibility',
						'itemDefectReturnFees' => 'https://schema.org/FreeReturn',
					),
					'knowsAbout' => array( 'Handmade ceramics', 'Pottery workshops', 'Handbuilding', 'Wheel throwing', 'Ceramic painting' ),
					'potentialAction' => array(
						'@type'  => 'ReserveAction',
						'target' => array(
							'@type'      => 'EntryPoint',
							'urlTemplate'=> $booking,
							'actionPlatform' => array(
								'https://schema.org/DesktopWebPlatform',
								'https://schema.org/MobileWebPlatform',
							),
						),
						'result' => array( '@type' => 'Reservation' ),
					),
				),
				array(
					'@type'   => 'Place',
					'@id'     => $home . '#studio',
					'name'    => 'Ioulia Geraskli Ceramic Lab',
					'address' => array( '@id' => $home . '#address' ),
					'geo'     => array(
						'@type'     => 'GeoCoordinates',
						'latitude'  => 38.0289084,
						'longitude' => 23.7389517,
					),
				),
				array(
					'@type'       => 'WebSite',
					'@id'         => $home . '#website',
					'url'         => $home,
					'name'        => 'Ioulia Geraskli Ceramics',
					'inLanguage'  => array( 'el-GR', 'en-US' ),
					'publisher'   => array( '@id' => $home . '#organization' ),
				),
				array(
					'@type'      => 'WebPage',
					'@id'        => $canonical . '#webpage',
					'url'        => $canonical,
					'name'       => ioulia_seo_title( wp_get_document_title() ),
					'description'=> ioulia_seo_value( 'desc' ),
					'inLanguage' => $language,
					'isPartOf'   => array( '@id' => $home . '#website' ),
					'about'      => array( '@id' => $home . '#organization' ),
				),
			),
		);

		$navigation = array(
			'home'      => $language === 'en-US' ? 'Home' : 'Αρχική',
			'shop'      => $language === 'en-US' ? 'Shop' : 'Κατάστημα',
			'about'     => $language === 'en-US' ? 'About' : 'Σχετικά',
			'workshops' => $language === 'en-US' ? 'Pottery Workshops' : 'Εργαστήρια Κεραμικής',
			'contact'   => $language === 'en-US' ? 'Contact' : 'Επικοινωνία',
		);

		foreach ( $navigation as $path => $name ) {
			$url = 'home' === $path
				? ( function_exists( 'ioulia_url' ) ? ioulia_url( '/', 'en-US' === $language ? 'en' : 'el' ) : home_url( '/' ) )
				: ( function_exists( 'ioulia_url' ) ? ioulia_url( '/' . $path . '/', 'en-US' === $language ? 'en' : 'el' ) : home_url( '/' . $path . '/' ) );

			$schema['@graph'][] = array(
				'@type' => 'SiteNavigationElement',
				'name'  => $name,
				'url'   => $url,
			);
		}

		if ( ! is_front_page() && ! $thin_shop_archive ) {
			$english     = 'en-US' === $language;
			$home_url    = function_exists( 'ioulia_url' ) ? ioulia_url( '/', $english ? 'en' : 'el' ) : home_url( '/' );
			$shop_url    = function_exists( 'ioulia_url' ) ? ioulia_url( '/shop/', $english ? 'en' : 'el' ) : home_url( '/shop/' );
			$breadcrumbs = array(
				array(
					'@type'    => 'ListItem',
					'position' => 1,
					'name'     => $english ? 'Home' : 'Αρχική',
					'item'     => $home_url,
				),
			);

			if ( is_singular( 'product' ) ) {
				$breadcrumbs[] = array(
					'@type'    => 'ListItem',
					'position' => 2,
					'name'     => $english ? 'Shop' : 'Κατάστημα',
					'item'     => $shop_url,
				);
				$breadcrumbs[] = array(
					'@type'    => 'ListItem',
					'position' => 3,
					'name'     => trim( str_replace( '_', '', wp_strip_all_tags( get_the_title( get_queried_object_id() ) ) ) ),
					'item'     => $canonical,
				);
			} else {
				$key                = ioulia_seo_page_key();
				$current_name       = isset( $navigation[ $key ] ) ? $navigation[ $key ] : wp_strip_all_tags( get_the_title( get_queried_object_id() ) );
				$breadcrumbs[] = array(
					'@type'    => 'ListItem',
					'position' => 2,
					'name'     => $current_name,
					'item'     => $canonical,
				);
			}

			$schema['@graph'][] = array(
				'@type'           => 'BreadcrumbList',
				'@id'             => $canonical . '#breadcrumbs',
				'itemListElement' => $breadcrumbs,
			);
		}

		if ( is_singular( 'product' ) && function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( get_queried_object_id() );

			if ( $product ) {
				$name   = trim( str_replace( '_', '', wp_strip_all_tags( $product->get_name() ) ) );
				$images = array();

				foreach ( array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) as $image_id ) {
					$image = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';

					if ( $image ) {
						$images[] = $image;
					}
				}

				$product_schema = array(
					'@type'       => 'Product',
					'@id'         => $canonical . '#product',
					'name'        => $name,
					'description' => ioulia_seo_value( 'desc' ),
					'url'         => $canonical,
					'image'       => array_values( array_unique( $images ) ),
					'brand'       => array(
						'@type' => 'Brand',
						'name'  => 'Ioulia Geraskli Ceramics',
					),
					'material'    => 'Ceramic',
					'offers'      => array(
						'@type'         => 'Offer',
						'url'           => $canonical,
						'price'         => (string) $product->get_price(),
						'priceCurrency' => get_woocommerce_currency(),
						'availability'  => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
						'itemCondition' => 'https://schema.org/NewCondition',
						'seller'        => array( '@id' => $home . '#organization' ),
					),
				);

				if ( '' !== $product->get_sku() ) {
					$product_schema['sku'] = $product->get_sku();
				}

				if ( $product->get_rating_count() > 0 ) {
					$product_schema['aggregateRating'] = array(
						'@type'       => 'AggregateRating',
						'ratingValue' => (string) $product->get_average_rating(),
						'reviewCount' => (int) $product->get_rating_count(),
					);
				}

				$schema['@graph'][] = $product_schema;
			}
		}

		$landing_page_slugs = function_exists( 'ioulia_workshop_landing_slugs' ) ? array_values( ioulia_workshop_landing_slugs() ) : array();
		if ( in_array( ioulia_seo_page_key(), array( 'workshops', 'book-workshop' ), true ) ) {
			$courses = ioulia_seo_workshop_courses();

			if ( ! empty( $courses['itemListElement'] ) ) {
				$schema['@graph'][] = $courses;
			}
		}

		if ( in_array( ioulia_seo_page_key(), $landing_page_slugs, true ) ) {
			$courses = ioulia_seo_workshop_courses();
			foreach ( (array) ( $courses['itemListElement'] ?? array() ) as $course_item ) {
				if ( isset( $course_item['item']['url'] ) && untrailingslashit( $course_item['item']['url'] ) === untrailingslashit( $canonical ) ) {
					$schema['@graph'][] = $course_item['item'];
					break;
				}
			}
		}

		if ( 'workshops' === ioulia_seo_page_key() ) {
			$schema['@graph'][] = ioulia_seo_workshop_faq();
		}

		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . chr( 10 );
	}
	add_action( 'wp_head', 'ioulia_seo_schema', 30 );
}
