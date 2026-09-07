<?php
/**
 * Ioulia Geraskli — Product Archive
 *
 * Paste into Code Snippets and activate.
 * Add [ioulia_product_archive] to an Elementor Shortcode widget.
 */



/**
 * Categories describe what an object is.
 * Collections describe the creative series/story it belongs to.
 */
add_action( 'init', 'igpa_register_product_collection_taxonomy' );
function igpa_register_product_collection_taxonomy() {
	$labels = array(
		'name'                       => 'Collections',
		'singular_name'              => 'Collection',
		'menu_name'                  => 'Collections',
		'all_items'                  => 'All collections',
		'edit_item'                  => 'Edit collection',
		'view_item'                  => 'View collection',
		'update_item'                => 'Update collection',
		'add_new_item'               => 'Add new collection',
		'new_item_name'              => 'New collection name',
		'search_items'               => 'Search collections',
		'popular_items'              => 'Popular collections',
		'separate_items_with_commas' => 'Separate collections with commas',
		'add_or_remove_items'        => 'Add or remove collections',
		'choose_from_most_used'      => 'Choose from the most used collections',
		'not_found'                  => 'No collections found',
	);

	register_taxonomy(
		'product_collection',
		array( 'product' ),
		array(
			'labels'            => $labels,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'hierarchical'      => false,
			'query_var'         => true,
			'rewrite'           => array(
				'slug'       => 'collection',
				'with_front' => false,
			),
		)
	);
}

/**
 * Seed a small, editable editorial structure from the catalogue that already
 * exists. Nothing here changes inventory: One & Only is a merchandising tag,
 * while Collections remain a separate story-led taxonomy.
 */
add_action( 'init', 'igpa_seed_editorial_shop', 30 );
function igpa_seed_editorial_shop() {
	if ( '20260907a' === get_option( 'igpa_editorial_shop_version' ) ) {
		return;
	}

	$collection_specs = array(
		'beach-stories' => array(
			'name'    => 'Beach Stories',
			'needle'  => 'beach collection',
			'el_desc' => 'Ανάλαφρα χειροποίητα κεραμικά με χρώματα, σχήματα και μικρές αναμνήσεις από το ελληνικό καλοκαίρι.',
			'en_desc' => 'Light-hearted handmade ceramics shaped by the colours, forms and small memories of a Greek summer.',
		),
		'garden-table' => array(
			'name'    => 'Garden Table',
			'needle'  => 'garden',
			'el_desc' => 'Ζωγραφισμένα αντικείμενα για ένα ανεπιτήδευτο τραπέζι, ανάμεσα σε φύλλα, άνθη και καθημερινές μικρές τελετουργίες.',
			'en_desc' => 'Painted objects for an unhurried table, surrounded by leaves, flowers and small everyday rituals.',
		),
		'naked-forms' => array(
			'name'    => 'Naked Forms',
			'needle'  => 'naked collection',
			'el_desc' => 'Καθαρές φόρμες και η φυσική υφή του πηλού σε μια ήσυχη συλλογή όπου κάθε ατέλεια μένει ορατή.',
			'en_desc' => 'Quiet forms and the natural texture of clay, with every handmade trace intentionally left visible.',
		),
	);

	$collection_ids = array();
	foreach ( $collection_specs as $slug => $spec ) {
		$existing = term_exists( $slug, 'product_collection' );
		if ( ! $existing ) {
			$existing = wp_insert_term(
				$spec['name'],
				'product_collection',
				array(
					'slug'        => $slug,
					'description' => $spec['el_desc'],
				)
			);
		}

		if ( ! is_wp_error( $existing ) ) {
			$term_id = is_array( $existing ) ? absint( $existing['term_id'] ) : absint( $existing );
			$collection_ids[ $slug ] = $term_id;
			update_term_meta( $term_id, 'igpa_description_en', $spec['en_desc'] );
			update_term_meta( $term_id, 'igpa_order', count( $collection_ids ) );
		}
	}

	$one_term = term_exists( 'one-and-only', 'product_tag' );
	if ( ! $one_term ) {
		$one_term = wp_insert_term(
			'One & Only',
			'product_tag',
			array(
				'slug'        => 'one-and-only',
				'description' => 'Μοναδικά χειροποίητα κεραμικά σε ένα μόνο διαθέσιμο κομμάτι. Όταν φύγουν, δεν επαναλαμβάνονται.',
			)
		);
	}

	$one_term_id = 0;
	if ( ! is_wp_error( $one_term ) ) {
		$one_term_id = is_array( $one_term ) ? absint( $one_term['term_id'] ) : absint( $one_term );
		update_term_meta( $one_term_id, 'igpa_description_en', 'Singular handmade ceramics available as one piece only. Once they are gone, they are not reproduced.' );
	}

	$product_ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	$one_only_demo_ids = array( 4592, 4588, 4501, 4496 );
	foreach ( $product_ids as $product_id ) {
		$name = strtolower( html_entity_decode( get_the_title( $product_id ) ) );

		foreach ( $collection_specs as $slug => $spec ) {
			if ( isset( $collection_ids[ $slug ] ) && false !== strpos( $name, $spec['needle'] ) ) {
				wp_set_object_terms( $product_id, array( $collection_ids[ $slug ] ), 'product_collection', true );
			}
		}

		if ( $one_term_id && in_array( absint( $product_id ), $one_only_demo_ids, true ) ) {
			wp_set_object_terms( $product_id, array( $one_term_id ), 'product_tag', true );
		}
	}

	update_option( 'igpa_editorial_shop_version', '20260907a', false );
}

function igpa_archive_language() {
	return function_exists( 'ioulia_lang' ) && 'en' === ioulia_lang() ? 'en' : 'el';
}

function igpa_term_description( $term ) {
	if ( ! $term || is_wp_error( $term ) ) {
		return '';
	}

	if ( 'en' === igpa_archive_language() ) {
		$english = (string) get_term_meta( $term->term_id, 'igpa_description_en', true );
		if ( '' !== $english ) {
			return $english;
		}
	}

	return (string) $term->description;
}

function igpa_term_products( $taxonomy, $term_id, $limit = 2 ) {
	return get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'fields'         => 'ids',
			'tax_query'      => array(
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => absint( $term_id ),
				),
			),
		)
	);
}

function igpa_product_image_id( $product_id ) {
	$product = wc_get_product( $product_id );
	return $product ? absint( $product->get_image_id() ) : 0;
}

add_shortcode( 'ioulia_product_archive', 'igpa_render_product_archive' );
function igpa_render_product_archive( $atts = array() ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return '';
	}

	$atts = shortcode_atts(
		array(
			'title' => 'shop',
			'limit' => 'all',
		),
		$atts,
		'ioulia_product_archive'
	);

	/* A bounded page keeps the catalogue light even as the archive grows. */
	$requested = strtolower( trim( (string) $atts['limit'] ) );
	$limit     = ( '' === $requested || 'all' === $requested || '-1' === $requested || '0' === $requested )
		? 16
		: max( 4, min( 48, absint( $requested ) ) );
	$catalog_page       = isset( $_GET['catalog-page'] ) ? max( 1, absint( $_GET['catalog-page'] ) ) : 1;
	$selected_category  = isset( $_GET['catalog-category'] ) ? sanitize_title( wp_unslash( $_GET['catalog-category'] ) ) : '';
	$selected_collection = isset( $_GET['catalog-collection'] ) ? sanitize_title( wp_unslash( $_GET['catalog-collection'] ) ) : '';
	$selected_sort      = isset( $_GET['catalog-sort'] ) ? sanitize_key( wp_unslash( $_GET['catalog-sort'] ) ) : 'featured';
	$allowed_sorts      = array( 'featured', 'newest', 'price-asc', 'price-desc' );
	if ( ! in_array( $selected_sort, $allowed_sorts, true ) ) {
		$selected_sort = 'featured';
	}

	$tax_query = WC()->query->get_tax_query();
	if ( '' !== $selected_category ) {
		$tax_query[] = array(
			'taxonomy'         => 'product_cat',
			'field'            => 'slug',
			'terms'            => $selected_category,
			'include_children' => true,
		);
	}
	if ( '' !== $selected_collection ) {
		$tax_query[] = array(
			'taxonomy' => 'product_collection',
			'field'    => 'slug',
			'terms'    => $selected_collection,
		);
	}

	$query_args = array(
		'post_type'              => 'product',
		'post_status'            => 'publish',
		'posts_per_page'         => $limit,
		'paged'                  => $catalog_page,
		'orderby'                => array(
			'menu_order' => 'ASC',
			'date'       => 'DESC',
		),
		'meta_query'             => WC()->query->get_meta_query(),
		'tax_query'              => $tax_query,
		'no_found_rows'          => false,
		'update_post_meta_cache' => true,
		'update_post_term_cache' => true,
	);

	if ( 'newest' === $selected_sort ) {
		$query_args['orderby'] = array( 'date' => 'DESC' );
	} elseif ( in_array( $selected_sort, array( 'price-asc', 'price-desc' ), true ) ) {
		$query_args['meta_key'] = '_price';
		$query_args['orderby']  = array( 'meta_value_num' => 'price-desc' === $selected_sort ? 'DESC' : 'ASC' );
	}

	$query = new WP_Query( $query_args );
	$has_products = $query->have_posts();

	$categories = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'parent'     => 0,
			'exclude'    => array_filter( array( absint( get_option( 'default_product_cat' ) ) ) ),
			'orderby'    => 'menu_order',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $categories ) ) {
		$categories = array();
	}

	/*
	 * The whole tree, not just its top. The row of subcategories beside the
	 * filters shows one level at a time - the children of whatever category is
	 * selected - so it needs every term and the slug of each one's parent. Cards
	 * already carry their ancestors' slugs, so filtering by a term at any depth
	 * works with the matcher that is already there.
	 */
	$category_tree = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'exclude'    => array_filter( array( absint( get_option( 'default_product_cat' ) ) ) ),
			'orderby'    => 'menu_order',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $category_tree ) ) {
		$category_tree = array();
	}

	$category_slug_by_id = array();
	foreach ( $category_tree as $term ) {
		$category_slug_by_id[ $term->term_id ] = $term->slug;
	}

	$collections = get_terms(
		array(
			'taxonomy'   => 'product_collection',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $collections ) ) {
		$collections = array();
	}

	$lang            = igpa_archive_language();
	$english         = 'en' === $lang;
	$is_shop_landing = function_exists( 'is_shop' ) && is_shop() && 1 === $catalog_page && '' === $selected_category && '' === $selected_collection && 'featured' === $selected_sort;
	$archive_term    = is_tax( 'product_collection' ) || ( function_exists( 'is_product_tag' ) && is_product_tag( 'one-and-only' ) )
		? get_queried_object()
		: null;
	$editorial_terms = array();
	foreach ( array( 'beach-stories', 'garden-table', 'naked-forms' ) as $editorial_slug ) {
		$editorial_term = get_term_by( 'slug', $editorial_slug, 'product_collection' );
		if ( $editorial_term && ! is_wp_error( $editorial_term ) ) {
			$editorial_terms[] = $editorial_term;
		}
	}

	$one_only_term = get_term_by( 'slug', 'one-and-only', 'product_tag' );
	$all_label     = $english ? 'all' : 'όλα';
	$category_label = $english ? 'category' : 'κατηγορία';
	$collection_label = $english ? 'collection' : 'συλλογή';
	$sort_label      = $english ? 'sort' : 'ταξινόμηση';

	$instance_id  = wp_unique_id( 'igpa-' );
	$product_count = (int) $query->found_posts;

	ob_start();
	?>
	<section
		id="<?php echo esc_attr( $instance_id ); ?>"
		class="igpa<?php echo $is_shop_landing ? ' igpa--landing' : ''; ?><?php echo $archive_term ? ' igpa--term' : ''; ?>"
		data-igpa-root
		aria-label="<?php echo esc_attr( $atts['title'] ); ?>"
	>
		<style>
			#<?php echo esc_attr( $instance_id ); ?> {
				--igpa-paper: var(--ioulia-cream, #fffef7);
				--igpa-ink: var(--ioulia-dark, #2b2b2b);
				--igpa-accent: var(--ioulia-bg-dark, #7c3737);
				--igpa-muted: rgba(43, 43, 43, .58);
				--igpa-line: rgba(43, 43, 43, .2);
				--igpa-soft: #f1eee6;
				--igpa-x: var(--ioulia-page-x, clamp(18px, 2.8vw, 38px));
				--igpa-sticky-top: 142px;
				position: relative;
				width: 100%;
				padding:
					clamp(185px, 15vw, 220px)
					var(--igpa-x)
					clamp(90px, 10vw, 150px);
				background: var(--igpa-paper);
				color: var(--igpa-ink);
				font-family: var(--ioulia-font, "Montserrat", Arial, sans-serif);
			}

			#<?php echo esc_attr( $instance_id ); ?> *,
			#<?php echo esc_attr( $instance_id ); ?> *::before,
			#<?php echo esc_attr( $instance_id ); ?> *::after {
				box-sizing: border-box;
			}

			#<?php echo esc_attr( $instance_id ); ?> button {
				font: inherit;
			}

			#<?php echo esc_attr( $instance_id ); ?>.igpa--landing {
				padding-top: 0;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__editorial-hero {
				position: relative;
				display: grid;
				width: calc(100% + (var(--igpa-x) * 2));
				min-height: 100svh;
				margin: 0 calc(var(--igpa-x) * -1) clamp(76px, 9vw, 138px);
				grid-template-columns: repeat(2, minmax(0, 1fr));
				background: var(--igpa-soft);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__editorial-panel {
				position: relative;
				display: flex;
				min-width: 0;
				overflow: hidden;
				align-items: flex-end;
				color: #fffef7;
				text-decoration: none;
				isolation: isolate;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__editorial-panel::after {
				content: "";
				position: absolute;
				z-index: 1;
				inset: 0;
				background: linear-gradient(180deg, transparent 48%, rgba(20, 18, 17, .62) 100%);
				pointer-events: none;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__editorial-image,
			#<?php echo esc_attr( $instance_id ); ?> .igpa__collection-image,
			#<?php echo esc_attr( $instance_id ); ?> .igpa__term-image {
				position: absolute;
				inset: 0;
				width: 100%;
				height: 100%;
				object-fit: cover;
				transform: scale(1.001);
				transition: transform 1100ms cubic-bezier(.16, 1, .3, 1);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__editorial-panel:hover .igpa__editorial-image,
			#<?php echo esc_attr( $instance_id ); ?> .igpa__collection-card:hover .igpa__collection-image {
				transform: scale(1.025);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__editorial-copy {
				position: relative;
				z-index: 2;
				display: grid;
				width: 100%;
				padding: clamp(28px, 4vw, 64px);
				gap: 9px;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__eyebrow {
				font-size: var(--ioulia-micro, 12px);
				font-weight: 500;
				letter-spacing: .12em;
				text-transform: uppercase;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__editorial-title {
				margin: 0;
				font-size: clamp(36px, 5.2vw, 82px);
				font-weight: 400;
				line-height: .94;
				letter-spacing: -.055em;
				text-wrap: balance;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__editorial-action {
				display: inline-flex;
				margin-top: 8px;
				align-items: center;
				gap: 9px;
				font-size: var(--ioulia-small, 14px);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__editorial-action::after {
				content: "→";
				transition: transform 320ms cubic-bezier(.16, 1, .3, 1);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__editorial-panel:hover .igpa__editorial-action::after {
				transform: translateX(5px);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__collections {
				margin: 0 0 clamp(92px, 11vw, 170px);
				scroll-margin-top: 150px;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__section-heading {
				display: flex;
				margin-bottom: clamp(24px, 3vw, 44px);
				align-items: flex-end;
				justify-content: space-between;
				gap: 20px;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__section-heading h2 {
				max-width: 760px;
				margin: 0;
				font-size: clamp(34px, 4.8vw, 74px);
				font-weight: 400;
				line-height: .95;
				letter-spacing: -.05em;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__collection-grid {
				display: grid;
				grid-template-columns: repeat(3, minmax(0, 1fr));
				gap: clamp(10px, 1.2vw, 20px);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__collection-card {
				display: grid;
				min-width: 0;
				color: inherit;
				gap: 13px;
				text-decoration: none;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__collection-media {
				position: relative;
				aspect-ratio: 4 / 5;
				overflow: hidden;
				background: var(--igpa-soft);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__collection-name {
				margin: 0;
				font-size: clamp(19px, 1.5vw, 25px);
				font-weight: 400;
				line-height: 1.1;
				letter-spacing: -.025em;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__collection-description {
				max-width: 38em;
				margin: 0;
				color: var(--igpa-muted);
				font-size: var(--ioulia-small, 14px);
				line-height: 1.55;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__term-hero {
				display: grid;
				min-height: min(76svh, 820px);
				margin-bottom: clamp(64px, 8vw, 120px);
				grid-template-columns: minmax(0, 1fr) minmax(280px, .62fr) minmax(0, 1fr);
				align-items: stretch;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__term-visual {
				position: relative;
				min-height: 420px;
				overflow: hidden;
				background: var(--igpa-soft);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__term-copy {
				display: flex;
				padding: clamp(28px, 4vw, 64px);
				align-items: center;
				justify-content: center;
				text-align: center;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__term-copy-inner {
				display: grid;
				max-width: 360px;
				gap: 20px;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__term-copy h1 {
				margin: 0;
				font-size: clamp(40px, 4.8vw, 72px);
				font-weight: 400;
				line-height: .94;
				letter-spacing: -.05em;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__term-copy p {
				margin: 0;
				color: var(--igpa-muted);
				font-size: var(--ioulia-small, 14px);
				line-height: 1.65;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__head {
				display: flex;
				min-height: 58px;
				padding-bottom: clamp(22px, 2.4vw, 36px);
				align-items: flex-end;
				justify-content: space-between;
				gap: 24px;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__title {
				margin: 0;
				color: var(--igpa-ink);
				font-size: clamp(32px, 3.4vw, 52px);
				font-weight: 400;
				line-height: .95;
				letter-spacing: -.045em;
				text-transform: lowercase;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__head-count {
				margin: 0;
				color: var(--igpa-muted);
				font-size: var(--ioulia-micro);
				font-weight: 400;
				line-height: 1.2;
				letter-spacing: .035em;
				text-transform: lowercase;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__toolbar {
				position: sticky;
				z-index: 120;
				top: var(--igpa-sticky-top);
				display: flex;
				min-height: 46px;
				margin: 0 calc(var(--igpa-x) * -1);
				 padding: 0em clamp(28px, 3.05vw, 46px) 0 clamp(28px, 3.05vw, 46px);
				border: 0;
				background: transparent;
				align-items: center;
				justify-content: space-between;
				gap: 24px;
				pointer-events: none;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__toolbar-count,
			#<?php echo esc_attr( $instance_id ); ?> .igpa__controls {
				pointer-events: auto;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__toolbar-count {
				color: var(--igpa-muted);
				font-size: var(--ioulia-micro);
				font-weight: 400;
				line-height: 1;
				letter-spacing: .04em;
				text-transform: lowercase;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__controls {
				display: flex;
				align-items: center;
				gap: clamp(18px, 2.2vw, 34px);
			}

			/* The subcategories are words, not controls. They read as a line of
			   text beside the two filters, and the one in use is the one set in
			   ink - no borders, no fills, nothing that competes with the buttons
			   it sits next to. */
			#<?php echo esc_attr( $instance_id ); ?> .igpa__subs {
				display: flex;
				min-width: 0;
				align-items: baseline;
				gap: clamp(14px, 1.6vw, 26px);
				pointer-events: auto;
				flex-wrap: wrap;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__sub {
				appearance: none;
				display: inline-block;
				padding: 0;
				border: 0;
				border-radius: 0;
				background: none;
				color: var(--igpa-muted);
				font-size: var(--ioulia-small);
				font-weight: 500;
				line-height: 1.2;
				letter-spacing: .01em;
				text-transform: lowercase;
				white-space: nowrap;
				cursor: pointer;
				transition: color 220ms ease;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__sub:hover {
				color: var(--igpa-ink);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__sub.is-active {
				color: var(--igpa-ink);
				text-decoration: underline;
				text-underline-offset: 5px;
				text-decoration-thickness: 1px;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__sub:focus-visible {
				outline: 1px solid var(--igpa-accent);
				outline-offset: 4px;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__picker {
				position: relative;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__trigger {
				appearance: none;
				display: inline-flex;
				min-height: 44px;
				padding: 0;
				border: 0;
				border-radius: 0;
				background: transparent;
				box-shadow: none;
				color: var(--igpa-ink);
				align-items: center;
				font-size: var(--ioulia-small, 14px);
				font-weight: 400;
				text-transform: lowercase;
				gap: 8px;
				cursor: pointer;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__trigger::after {
				content: "";
				width: 5px;
				height: 5px;
				margin-top: -3px;
				border-right: 1px solid currentColor;
				border-bottom: 1px solid currentColor;
				transform: rotate(45deg);
				transition: transform 260ms cubic-bezier(.16, 1, .3, 1);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__picker.is-open .igpa__trigger {
				background: transparent;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__picker.is-open .igpa__trigger::after {
				margin-top: 3px;
				transform: rotate(225deg);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__trigger:focus-visible,
			#<?php echo esc_attr( $instance_id ); ?> .igpa__option:focus-visible {
				outline: 1px solid var(--igpa-accent);
				outline-offset: 3px;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__menu {
				position: absolute;
				z-index: 5;
				top: calc(100% + 9px);
				right: 0;
				display: grid;
				width: max-content;
				min-width: 190px;
				max-width: min(280px, calc(100vw - 36px));
				padding: 8px 0;
				border: 1px solid var(--igpa-line);
				background: var(--igpa-paper);
				box-shadow: 0 12px 28px rgba(43, 43, 43, .06);
				opacity: 0;
				visibility: hidden;
				transform: translateY(-5px);
				transition:
					opacity 160ms ease,
					visibility 160ms ease,
					transform 240ms cubic-bezier(.16, 1, .3, 1);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__picker.is-open .igpa__menu {
				opacity: 1;
				visibility: visible;
				transform: translateY(0);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__option {
				appearance: none;
				position: relative;
				width: 100%;
				margin: 0;
				padding: 9px 28px 9px 13px;
				border: 0;
				border-radius: 0;
				background: transparent;
				color: var(--igpa-muted);
				cursor: pointer;
				font-size: var(--ioulia-micro);
				font-weight: 400;
				line-height: 1.25;
				letter-spacing: .018em;
				text-align: left;
				text-transform: lowercase;
				transition: color 160ms ease;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__option:hover,
			#<?php echo esc_attr( $instance_id ); ?> .igpa__option[aria-selected="true"] {
				color: var(--igpa-ink);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__option[aria-selected="true"]::after {
				content: "";
				position: absolute;
				top: 50%;
				right: 13px;
				width: 5px;
				height: 5px;
				border-radius: 50%;
				background: var(--igpa-accent);
				transform: translateY(-50%);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__grid {
				display: grid;
				margin-top: clamp(16px, 2vw, 28px);
				grid-template-columns: repeat(2, minmax(0, 1fr));
				column-gap: clamp(10px, 1vw, 16px);
				row-gap: clamp(52px, 6.5vw, 90px);
				align-items: start;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__card {
				min-width: 0;
				opacity: 0;
				transform: translate3d(0, 22px, 0);
				transition:
					opacity 620ms ease,
					transform 820ms cubic-bezier(.16, 1, .3, 1);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__card.is-visible {
				opacity: 1;
				transform: translate3d(0, 0, 0);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__card[hidden] {
				display: none !important;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__media {
				position: relative;
				display: grid;
				width: 100%;
				aspect-ratio: 8 / 5;
				overflow: hidden;
				background: var(--igpa-soft);
				color: inherit;
				grid-template-columns: 1fr;
				gap: 4px;
				text-decoration: none;
				isolation: isolate;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__card--pair .igpa__media {
				grid-template-columns: repeat(2, minmax(0, 1fr));
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__frame {
				position: relative;
				min-width: 0;
				overflow: hidden;
				background: var(--igpa-soft);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__image {
				position: absolute;
				inset: 0;
				width: 100%;
				height: 100%;
				object-fit: cover;
				transform: scale(1.001);
				transition: transform 900ms cubic-bezier(.16, 1, .3, 1);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__media:hover .igpa__image {
				
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__card--pair .igpa__frame:nth-child(2) .igpa__image {
				transition-delay: 40ms;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__sold {
				position: absolute;
				z-index: 3;
				top: 11px;
				left: 11px;
				padding: 7px 9px;
				background: var(--igpa-paper);
				color: var(--igpa-ink);
				font-size: var(--ioulia-micro);
				font-weight: 400;
				line-height: 1;
				letter-spacing: .025em;
				text-transform: lowercase;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__one-only {
				position: absolute;
				z-index: 3;
				top: 11px;
				right: 11px;
				padding: 7px 10px;
				border-radius: 999px;
				background: var(--igpa-ink);
				color: var(--igpa-paper);
				font-size: var(--ioulia-micro, 11px);
				font-weight: 500;
				line-height: 1;
				letter-spacing: .02em;
				text-transform: lowercase;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__meta {
				display: grid;
				margin-top: 11px;
				justify-items: center;
				gap: 4px;
				text-align: center;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__name,
			#<?php echo esc_attr( $instance_id ); ?> .igpa__price {
				margin: 0;
				font-weight: 400;
				text-transform: lowercase;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__name,
			#<?php echo esc_attr( $instance_id ); ?> .igpa__name a {
				color: var(--igpa-ink);
				font-size: var(--ioulia-micro);
				font-weight: 400;
				line-height: 1.35;
				letter-spacing: -.008em;
				text-decoration: none;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__price {
				color: var(--igpa-ink);
				font-size: var(--ioulia-micro);
				line-height: 1.35;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__price del {
				color: var(--igpa-muted);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__price ins {
				color: var(--igpa-accent);
				text-decoration: none;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__empty {
				display: none;
				margin: 80px 0 0;
				color: var(--igpa-muted);
				font-size: var(--ioulia-small);
				font-weight: 400;
				text-transform: lowercase;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__empty.is-visible {
				display: block;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__toolbar {
				min-height: 62px;
				padding-top: 7px;
				padding-bottom: 7px;
				border: 0;
				background: transparent;
				pointer-events: auto;
				isolation: isolate;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__toolbar::before {
				content: "";
				position: absolute;
				z-index: -1;
				top: calc((var(--igpa-sticky-top) + 10px) * -1);
				right: 0;
				bottom: -36px;
				left: 0;
				background: linear-gradient(
					to bottom,
					var(--igpa-paper) 0%,
					rgba(255, 254, 247, .97) 42%,
					rgba(255, 254, 247, .68) 67%,
					rgba(255, 254, 247, 0) 100%
				);
				opacity: 0;
				pointer-events: none;
				transition: opacity 300ms ease;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__toolbar.is-stuck::before {
				opacity: 1;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-open {
				appearance: none;
				display: inline-flex;
				min-height: 44px;
				padding: 0;
				border: 0;
				border-radius: 0;
				background: transparent;
				box-shadow: none;
				color: var(--igpa-ink);
				align-items: center;
				gap: 10px;
				font-size: var(--ioulia-small, 14px);
				font-weight: 400;
				cursor: pointer;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-open svg {
				width: 18px;
				height: 18px;
				stroke: currentColor;
				stroke-width: 1.5;
				fill: none;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-count {
				display: inline-grid;
				width: 22px;
				height: 22px;
				border-radius: 50%;
				background: var(--igpa-ink);
				color: var(--igpa-paper);
				place-items: center;
				font-size: 10px;
				font-variant-numeric: tabular-nums;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-layer {
				position: fixed;
				z-index: 999999;
				inset: 0;
				display: flex;
				align-items: flex-end;
				justify-content: center;
				visibility: hidden;
				pointer-events: none;
				transition: visibility 0s linear 660ms;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-layer.is-open {
				visibility: visible;
				pointer-events: auto;
				transition-delay: 0s;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-backdrop {
				position: absolute;
				inset: 0;
				border: 0;
				background: rgba(25, 23, 22, .38);
				opacity: 0;
				backdrop-filter: blur(0);
				-webkit-backdrop-filter: blur(0);
				cursor: pointer;
				will-change: opacity, backdrop-filter;
				transition: opacity 420ms ease, backdrop-filter 560ms cubic-bezier(.16, 1, .3, 1), -webkit-backdrop-filter 560ms cubic-bezier(.16, 1, .3, 1);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-layer.is-open .igpa__filter-backdrop {
				opacity: 1;
				backdrop-filter: blur(7px);
				-webkit-backdrop-filter: blur(7px);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-panel {
				position: relative;
				z-index: 1;
				display: flex;
				width: min(760px, calc(100% - 36px));
				height: min(92svh, 860px);
				height: min(92dvh, 860px);
				max-height: min(92svh, 860px);
				max-height: min(92dvh, 860px);
				overflow: hidden;
				border-radius: 28px 28px 0 0;
				background: var(--igpa-paper);
				box-shadow: 0 -24px 70px rgba(26, 24, 23, .16);
				transform: translate3d(0, 105%, 0);
				transition: transform 620ms cubic-bezier(.16, 1, .3, 1);
				flex-direction: column;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-layer.is-open .igpa__filter-panel {
				transform: translate3d(0, 0, 0);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-form {
				display: flex;
				height: 100%;
				min-height: 0;
				flex: 1;
				flex-direction: column;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-head,
			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-foot {
				display: flex;
				padding: 20px clamp(20px, 4vw, 42px);
				align-items: center;
				justify-content: space-between;
				gap: 20px;
				background: var(--igpa-paper);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-head {
				border-bottom: 1px solid var(--igpa-line);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-grab {
				position: absolute;
				top: 10px;
				left: 50%;
				width: 40px;
				height: 4px;
				border-radius: 999px;
				background: var(--igpa-line);
				transform: translateX(-50%);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-title {
				margin: 0;
				font-size: clamp(24px, 3vw, 36px);
				font-weight: 500;
				line-height: 1;
				letter-spacing: -.035em;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-close {
				appearance: none;
				position: relative;
				width: 44px;
				height: 44px;
				padding: 0;
				border: 1px solid var(--igpa-line);
				border-radius: 50%;
				background: transparent;
				box-shadow: none;
				color: var(--igpa-ink);
				cursor: pointer;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-close::before,
			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-close::after {
				content: "";
				position: absolute;
				top: 21px;
				left: 13px;
				width: 18px;
				height: 1px;
				background: currentColor;
				transform: rotate(45deg);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-close::after {
				transform: rotate(-45deg);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-body {
				min-height: 0;
				padding: 4px clamp(20px, 4vw, 42px) 34px;
				max-height: 100%;
				overflow-y: scroll;
				overscroll-behavior: contain;
				-webkit-overflow-scrolling: touch;
				flex: 1 1 auto;
				touch-action: pan-y;
				scrollbar-gutter: stable;
				scrollbar-width: thin;
				scrollbar-color: rgba(43, 43, 43, .22) transparent;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-body::-webkit-scrollbar { width: 5px; }
			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-body::-webkit-scrollbar-track { background: transparent; }
			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-body::-webkit-scrollbar-thumb { border-radius: 999px; background: rgba(43, 43, 43, .22); }

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-group {
				margin: 0;
				padding: 28px 0;
				border: 0;
				border-bottom: 1px solid var(--igpa-line);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-body > .igpa__filter-group:first-child {
				margin-top: 30px;
				padding-top: 0;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-group:last-child {
				border-bottom: 0;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-legend {
				margin-bottom: 16px;
				font-size: var(--ioulia-micro, 12px);
				font-weight: 500;
				letter-spacing: .1em;
				text-transform: uppercase;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-options {
				display: grid;
				grid-template-columns: repeat(3, minmax(0, 1fr));
				gap: 10px;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-options--sort {
				grid-template-columns: 1fr;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-disclosure {
				border-bottom: 1px solid var(--igpa-line);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-summary {
				position: relative;
				display: grid;
				min-height: 70px;
				padding: 18px 48px 18px 0;
				align-items: center;
				grid-template-columns: minmax(0, 1fr) auto;
				gap: 20px;
				cursor: pointer;
				list-style: none;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-summary::-webkit-details-marker {
				display: none;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-summary::after {
				content: "";
				position: absolute;
				top: 50%;
				right: 5px;
				width: 10px;
				height: 10px;
				border-right: 1px solid currentColor;
				border-bottom: 1px solid currentColor;
				transform: translateY(-70%) rotate(45deg);
				transition: transform 360ms cubic-bezier(.16, 1, .3, 1);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-disclosure[open] .igpa__filter-summary::after {
				transform: translateY(-25%) rotate(225deg);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-summary-title {
				font-size: var(--ioulia-small, 14px);
				font-weight: 500;
				letter-spacing: .08em;
				text-transform: uppercase;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-summary-value {
				max-width: 280px;
				overflow: hidden;
				color: var(--igpa-muted);
				font-size: var(--ioulia-small, 14px);
				text-overflow: ellipsis;
				white-space: nowrap;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-disclosure-content {
				padding: 0 0 24px;
				animation: igpa-filter-reveal 380ms cubic-bezier(.16, 1, .3, 1) both;
			}

			@keyframes igpa-filter-reveal {
				from { opacity: 0; transform: translateY(-7px); }
				to { opacity: 1; transform: translateY(0); }
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-options--compact {
				grid-template-columns: repeat(2, minmax(0, 1fr));
				gap: 10px 12px;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-option.igpa__filter-option--compact span {
				min-height: 52px;
				padding: 11px 42px 11px 18px;
				border: 1px solid var(--igpa-line);
				border-radius: 18px;
				background: transparent;
				box-shadow: none;
				transition: border-color 200ms ease, box-shadow 240ms ease, background-color 200ms ease, transform 240ms cubic-bezier(.16, 1, .3, 1);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-option.igpa__filter-option--compact span::after {
				right: 16px;
				width: 18px;
				height: 18px;
				border-width: 1.5px;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-option.igpa__filter-option--compact input:checked + span {
				border-color: var(--igpa-ink);
				background: rgba(255, 255, 255, .5);
				color: var(--igpa-ink);
				font-weight: 500;
				box-shadow: inset 0 0 0 1px var(--igpa-ink);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-option.igpa__filter-option--compact input:checked + span::after {
				border: 4px solid var(--igpa-paper);
				background: var(--igpa-ink);
				box-shadow: 0 0 0 1px var(--igpa-ink);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-option.igpa__filter-option--compact:hover span {
				border-color: var(--igpa-ink);
				transform: translateY(-2px);
				background: rgba(255, 255, 255, .36);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-option {
				position: relative;
				min-width: 0;
				cursor: pointer;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-option input {
				position: absolute;
				opacity: 0;
				pointer-events: none;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-option span {
				display: flex;
				min-height: 56px;
				padding: 12px 42px 12px 16px;
				border: 1px solid var(--igpa-line);
				border-radius: 16px;
				align-items: center;
				font-size: var(--ioulia-small, 14px);
				line-height: 1.25;
				transition: border-color 220ms ease, background 220ms ease, transform 320ms cubic-bezier(.16, 1, .3, 1);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-option span::after {
				content: "";
				position: absolute;
				top: 50%;
				right: 16px;
				width: 17px;
				height: 17px;
				border: 1px solid var(--igpa-line);
				border-radius: 50%;
				background: var(--igpa-paper);
				transform: translateY(-50%);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-option input:checked + span {
				border-color: var(--igpa-ink);
				background: var(--igpa-ink);
				color: var(--igpa-paper);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-option input:checked + span::after {
				border: 5px solid var(--igpa-paper);
				background: var(--igpa-ink);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-option input:focus-visible + span {
				outline: 2px solid var(--igpa-accent);
				outline-offset: 3px;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-option:hover span {
				transform: translateY(-2px);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-foot {
				border-top: 1px solid var(--igpa-line);
				flex-direction: column;
				align-items: stretch;
				gap: 8px;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-reset {
				display: block;
				padding: 7px 12px 3px;
				color: var(--igpa-ink);
				font-size: var(--ioulia-small, 14px);
				text-align: center;
				text-underline-offset: 4px;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-apply {
				appearance: none;
				width: 100%;
				min-width: 0;
				min-height: 54px;
				padding: 14px 24px;
				border: 0;
				border-radius: 12px;
				background: var(--igpa-ink);
				color: var(--igpa-paper);
				box-shadow: none;
				font: inherit;
				font-size: var(--ioulia-small, 14px);
				font-weight: 500;
				cursor: pointer;
				transition: transform 240ms cubic-bezier(.16, 1, .3, 1), box-shadow 240ms ease;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-apply:hover,
			#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-apply:focus-visible {
				outline: none;
				transform: translateY(-2px);
				box-shadow: 0 8px 20px rgba(43, 43, 43, .16);
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__pagination {
				display: flex;
				margin-top: clamp(70px, 8vw, 120px);
				align-items: center;
				justify-content: center;
				gap: 4px;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__pagination > ul.page-numbers {
				display: flex;
				margin: 0;
				padding: 0;
				align-items: center;
				gap: 4px;
				list-style: none;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__pagination a.page-numbers,
			#<?php echo esc_attr( $instance_id ); ?> .igpa__pagination span.page-numbers {
				display: inline-grid;
				min-width: 44px;
				height: 44px;
				padding: 0 10px;
				border-radius: 50%;
				color: var(--igpa-muted);
				place-items: center;
				font-size: var(--ioulia-small, 14px);
				text-decoration: none;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__pagination span.page-numbers.current {
				background: var(--igpa-ink);
				color: var(--igpa-paper);
			}

			html.igpa-filter-open,
			body.igpa-filter-open {
				overflow: hidden;
			}

			@media (max-width: 767px) {
				#<?php echo esc_attr( $instance_id ); ?> {
					--igpa-x: var(--ioulia-page-x, 18px);
					padding:
						165px
						var(--igpa-x)
						90px;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__head {
					min-height: 42px;
					padding-bottom: 18px;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__editorial-hero {
					min-height: 100svh;
					grid-template-columns: 1fr;
					grid-template-rows: repeat(2, minmax(0, 1fr));
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__editorial-copy {
					padding: 24px 20px;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__editorial-title {
					font-size: clamp(34px, 11vw, 48px);
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__section-heading {
					display: block;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__collection-grid {
					grid-template-columns: 1fr;
					gap: 42px;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__collection-media {
					aspect-ratio: 4 / 4.7;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__term-hero {
					min-height: auto;
					grid-template-columns: 1fr;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__term-visual {
					min-height: 58svh;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__term-copy {
					min-height: 42svh;
					padding: 44px 22px;
					grid-row: 2;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__term-visual:last-child {
					display: none;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__title {
					font-size: 34px;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__toolbar {
					min-height: 42px;
					padding-top: 9px;
					padding-bottom: 9px;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__toolbar-count {
					display: inline;
				}

				/* The toolbar becomes two rows: the subcategories, then the two
				   filters under them. The row scrolls sideways rather than
				   wrapping, so the toolbar keeps one height however many terms
				   the level holds - and overflow-y is named, because a lone
				   overflow-x: auto makes the browser compute the other axis as
				   auto too and the row scrolls vertically as well. */
				#<?php echo esc_attr( $instance_id ); ?> .igpa__toolbar {
					min-height: 56px;
					flex-wrap: nowrap;
					row-gap: 0;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-panel {
					width: 100%;
					height: min(92svh, 860px);
					height: min(92dvh, 860px);
					max-height: min(92svh, 860px);
					max-height: min(92dvh, 860px);
					border-radius: 24px 24px 0 0;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-head,
				#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-foot {
					padding: 16px 18px;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-body {
					padding: 2px 18px 28px;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-options {
					grid-template-columns: repeat(2, minmax(0, 1fr));
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-options--compact {
					grid-template-columns: 1fr;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-summary {
					min-height: 64px;
					grid-template-columns: minmax(0, 1fr) minmax(0, auto);
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-summary-value {
					max-width: 42vw;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-option span {
					min-height: 58px;
					padding: 11px 36px 11px 13px;
					border-radius: 14px;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-option span::after {
					right: 12px;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__subs {
					width: 100%;
					margin: 0 calc(var(--igpa-x) * -1);
					padding: 2px var(--igpa-x);
					overflow-x: auto;
					overflow-y: hidden;
					gap: 18px;
					flex-wrap: nowrap;
					touch-action: pan-x;
					scrollbar-width: none;
					-webkit-overflow-scrolling: touch;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__subs::-webkit-scrollbar {
					display: none;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__controls {
					width: 100%;
					gap: 0;
					justify-content: space-between;
				}


				#<?php echo esc_attr( $instance_id ); ?> .igpa__picker:first-child .igpa__menu {
					right: auto;
					left: 0;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__grid {
					margin-top: 14px;
					grid-template-columns: 1fr;
					row-gap: 48px;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__media {
					aspect-ratio: 8 / 5;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__meta {
					margin-top: 9px;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__name,
				#<?php echo esc_attr( $instance_id ); ?> .igpa__name a,
				#<?php echo esc_attr( $instance_id ); ?> .igpa__price {
					font-size: var(--ioulia-micro);
				}
			}

			@media (prefers-reduced-motion: reduce) {
				#<?php echo esc_attr( $instance_id ); ?> .igpa__card {
					opacity: 1;
					transform: none;
					transition: none;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__image,
				#<?php echo esc_attr( $instance_id ); ?> .igpa__editorial-image,
				#<?php echo esc_attr( $instance_id ); ?> .igpa__collection-image,
				#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-layer,
				#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-backdrop,
				#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-panel,
				#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-option span,
				#<?php echo esc_attr( $instance_id ); ?> .igpa__menu {
					transition: none;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__filter-option:hover span {
					transform: none;
				}
			}
		</style>

		<?php if ( $is_shop_landing && ! empty( $editorial_terms ) && $one_only_term && ! is_wp_error( $one_only_term ) ) : ?>
			<?php
			$lead_collection = $editorial_terms[0];
			$lead_products   = igpa_term_products( 'product_collection', $lead_collection->term_id, 1 );
			$one_products    = igpa_term_products( 'product_tag', $one_only_term->term_id, 1 );
			$lead_image      = ! empty( $lead_products ) ? igpa_product_image_id( $lead_products[0] ) : 0;
			$one_image       = ! empty( $one_products ) ? igpa_product_image_id( $one_products[0] ) : 0;
			$one_url         = get_term_link( $one_only_term );
			?>
			<div class="igpa__editorial-hero" aria-label="<?php echo esc_attr( $english ? 'Shop stories' : 'Ιστορίες του καταστήματος' ); ?>">
				<a class="igpa__editorial-panel" href="#<?php echo esc_attr( $instance_id ); ?>-collections">
					<?php if ( $lead_image ) : ?>
						<?php echo wp_get_attachment_image( $lead_image, 'full', false, array( 'class' => 'igpa__editorial-image', 'loading' => 'eager', 'decoding' => 'async', 'alt' => $english ? 'Ioulia Geraskli ceramic collections' : 'Συλλογές κεραμικών Ioulia Geraskli' ) ); ?>
					<?php endif; ?>
					<span class="igpa__editorial-copy">
						<span class="igpa__eyebrow"><?php echo esc_html( $english ? 'discover' : 'ανακάλυψε' ); ?></span>
						<strong class="igpa__editorial-title"><?php echo esc_html( $english ? 'our collections' : 'οι συλλογές μας' ); ?></strong>
						<span class="igpa__editorial-action"><?php echo esc_html( $english ? 'explore the stories' : 'δες τις ιστορίες' ); ?></span>
					</span>
				</a>

				<a class="igpa__editorial-panel" href="<?php echo esc_url( is_wp_error( $one_url ) ? '#shop-objects' : $one_url ); ?>">
					<?php if ( $one_image ) : ?>
						<?php echo wp_get_attachment_image( $one_image, 'full', false, array( 'class' => 'igpa__editorial-image', 'loading' => 'eager', 'decoding' => 'async', 'alt' => $english ? 'One and Only handmade ceramic' : 'Μοναδικό χειροποίητο κεραμικό' ) ); ?>
					<?php endif; ?>
					<span class="igpa__editorial-copy">
						<span class="igpa__eyebrow"><?php echo esc_html( $english ? 'one piece only' : 'ένα μόνο κομμάτι' ); ?></span>
						<strong class="igpa__editorial-title">one &amp; only</strong>
						<span class="igpa__editorial-action"><?php echo esc_html( $english ? 'see what is available' : 'δες τι είναι διαθέσιμο' ); ?></span>
					</span>
				</a>
			</div>

			<section class="igpa__collections" id="<?php echo esc_attr( $instance_id ); ?>-collections" aria-labelledby="<?php echo esc_attr( $instance_id ); ?>-collections-title">
				<div class="igpa__section-heading">
					<h2 id="<?php echo esc_attr( $instance_id ); ?>-collections-title"><?php echo esc_html( $english ? 'objects gathered into stories' : 'αντικείμενα που γίνονται ιστορίες' ); ?></h2>
					<span class="igpa__eyebrow"><?php echo esc_html( $english ? 'current collections' : 'τρέχουσες συλλογές' ); ?></span>
				</div>
				<div class="igpa__collection-grid">
					<?php foreach ( $editorial_terms as $editorial_term ) : ?>
						<?php
						$term_products = igpa_term_products( 'product_collection', $editorial_term->term_id, 1 );
						$term_image    = ! empty( $term_products ) ? igpa_product_image_id( $term_products[0] ) : 0;
						$term_url      = get_term_link( $editorial_term );
						?>
						<a class="igpa__collection-card" href="<?php echo esc_url( is_wp_error( $term_url ) ? '#shop-objects' : $term_url ); ?>">
							<span class="igpa__collection-media">
								<?php if ( $term_image ) : ?>
									<?php echo wp_get_attachment_image( $term_image, 'large', false, array( 'class' => 'igpa__collection-image', 'loading' => 'lazy', 'decoding' => 'async', 'alt' => $editorial_term->name ) ); ?>
								<?php endif; ?>
							</span>
							<h3 class="igpa__collection-name"><?php echo esc_html( $editorial_term->name ); ?></h3>
							<p class="igpa__collection-description"><?php echo esc_html( igpa_term_description( $editorial_term ) ); ?></p>
						</a>
					<?php endforeach; ?>
				</div>
			</section>
		<?php elseif ( $archive_term ) : ?>
			<?php
			$archive_products = igpa_term_products( $archive_term->taxonomy, $archive_term->term_id, 2 );
			$archive_images   = array();
			foreach ( $archive_products as $archive_product_id ) {
				$archive_image_id = igpa_product_image_id( $archive_product_id );
				if ( $archive_image_id ) {
					$archive_images[] = $archive_image_id;
				}
			}
			?>
			<header class="igpa__term-hero">
				<div class="igpa__term-visual">
					<?php if ( isset( $archive_images[0] ) ) : ?>
						<?php echo wp_get_attachment_image( $archive_images[0], 'full', false, array( 'class' => 'igpa__term-image', 'loading' => 'eager', 'decoding' => 'async', 'alt' => $archive_term->name ) ); ?>
					<?php endif; ?>
				</div>
				<div class="igpa__term-copy">
					<div class="igpa__term-copy-inner">
						<span class="igpa__eyebrow"><?php echo esc_html( 'product_tag' === $archive_term->taxonomy ? ( $english ? 'one piece only' : 'ένα μόνο κομμάτι' ) : ( $english ? 'collection' : 'συλλογή' ) ); ?></span>
						<h1><?php echo esc_html( $archive_term->name ); ?></h1>
						<p><?php echo esc_html( igpa_term_description( $archive_term ) ); ?></p>
					</div>
				</div>
				<div class="igpa__term-visual">
					<?php if ( isset( $archive_images[1] ) ) : ?>
						<?php echo wp_get_attachment_image( $archive_images[1], 'full', false, array( 'class' => 'igpa__term-image', 'loading' => 'eager', 'decoding' => 'async', 'alt' => '' ) ); ?>
					<?php endif; ?>
				</div>
			</header>
		<?php endif; ?>

		<?php
		$active_filter_count = ( '' !== $selected_category ? 1 : 0 ) + ( '' !== $selected_collection ? 1 : 0 ) + ( 'featured' !== $selected_sort ? 1 : 0 );
		$clear_url = remove_query_arg( array( 'catalog-page', 'catalog-category', 'catalog-collection', 'catalog-sort' ) );
		$selected_category_term = '' !== $selected_category ? get_term_by( 'slug', $selected_category, 'product_cat' ) : null;
		$selected_collection_term = '' !== $selected_collection ? get_term_by( 'slug', $selected_collection, 'product_collection' ) : null;
		$current_category_text = $selected_category_term && ! is_wp_error( $selected_category_term ) ? $selected_category_term->name : ( $english ? 'all objects' : 'όλα τα αντικείμενα' );
		$current_collection_text = $selected_collection_term && ! is_wp_error( $selected_collection_term ) ? $selected_collection_term->name : ( $english ? 'all collections' : 'όλες οι συλλογές' );
		$sort_options = array(
			'featured'  => $english ? 'relevance' : 'σχετικότητα',
			'newest'    => $english ? 'newest' : 'νεότερα',
			'price-asc' => $english ? 'price: low to high' : 'τιμή: χαμηλή προς υψηλή',
			'price-desc'=> $english ? 'price: high to low' : 'τιμή: υψηλή προς χαμηλή',
		);
		?>
		<nav class="igpa__toolbar" aria-label="<?php echo esc_attr( $english ? 'Catalogue controls' : 'Επιλογές καταλόγου' ); ?>">
			<span class="igpa__toolbar-count"><span><?php echo esc_html( $product_count ); ?></span> <?php echo esc_html( $english ? 'objects' : 'αντικείμενα' ); ?></span>
			<button class="igpa__filter-open" type="button" data-filter-open aria-haspopup="dialog" aria-controls="<?php echo esc_attr( $instance_id ); ?>-filters">
				<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h10M18 7h2M4 17h2M10 17h10M14 4v6M6 14v6"></path></svg>
				<span><?php echo esc_html( $english ? 'filter & sort' : 'φίλτρα & ταξινόμηση' ); ?></span>
				<?php if ( $active_filter_count ) : ?><span class="igpa__filter-count"><?php echo esc_html( $active_filter_count ); ?></span><?php endif; ?>
			</button>
		</nav>

		<div class="igpa__filter-layer" id="<?php echo esc_attr( $instance_id ); ?>-filters" data-filter-layer aria-hidden="true">
			<button class="igpa__filter-backdrop" type="button" data-filter-close tabindex="-1" aria-label="<?php echo esc_attr( $english ? 'Close filters' : 'Κλείσιμο φίλτρων' ); ?>"></button>
			<div class="igpa__filter-panel" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $instance_id ); ?>-filter-title" data-lenis-prevent>
				<span class="igpa__filter-grab" aria-hidden="true"></span>
				<form class="igpa__filter-form" method="get" action="<?php echo esc_url( $clear_url ); ?>">
					<header class="igpa__filter-head">
						<h2 class="igpa__filter-title" id="<?php echo esc_attr( $instance_id ); ?>-filter-title"><?php echo esc_html( $english ? 'find your object' : 'βρες το αντικείμενό σου' ); ?></h2>
						<button class="igpa__filter-close" type="button" data-filter-close aria-label="<?php echo esc_attr( $english ? 'Close filters' : 'Κλείσιμο φίλτρων' ); ?>"></button>
					</header>

					<div class="igpa__filter-body" data-filter-scroll data-lenis-prevent data-lenis-prevent-wheel data-lenis-prevent-touch>
						<fieldset class="igpa__filter-group">
							<legend class="igpa__filter-legend"><?php echo esc_html( $english ? 'sort by' : 'ταξινόμηση' ); ?></legend>
							<div class="igpa__filter-options igpa__filter-options--sort igpa__filter-options--compact">
								<?php foreach ( $sort_options as $sort_value => $sort_text ) : ?>
									<label class="igpa__filter-option igpa__filter-option--compact"><input type="radio" name="catalog-sort" value="<?php echo esc_attr( $sort_value ); ?>" <?php checked( $sort_value, $selected_sort ); ?>><span><?php echo esc_html( $sort_text ); ?></span></label>
								<?php endforeach; ?>
							</div>
						</fieldset>

						<details class="igpa__filter-disclosure" <?php echo '' !== $selected_category ? 'open' : ''; ?>>
							<summary class="igpa__filter-summary">
								<span class="igpa__filter-summary-title"><?php echo esc_html( $english ? 'category' : 'κατηγορία' ); ?></span>
								<span class="igpa__filter-summary-value"><?php echo esc_html( $current_category_text ); ?></span>
							</summary>
							<div class="igpa__filter-disclosure-content">
								<div class="igpa__filter-options igpa__filter-options--compact">
									<label class="igpa__filter-option igpa__filter-option--compact"><input type="radio" name="catalog-category" value="" <?php checked( '', $selected_category ); ?>><span><?php echo esc_html( $english ? 'all objects' : 'όλα τα αντικείμενα' ); ?></span></label>
									<?php foreach ( $category_tree as $category ) : ?>
										<label class="igpa__filter-option igpa__filter-option--compact"><input type="radio" name="catalog-category" value="<?php echo esc_attr( $category->slug ); ?>" <?php checked( $category->slug, $selected_category ); ?>><span><?php echo esc_html( $category->name ); ?></span></label>
									<?php endforeach; ?>
								</div>
							</div>
						</details>

						<?php if ( ! empty( $collections ) ) : ?>
							<details class="igpa__filter-disclosure" <?php echo '' !== $selected_collection ? 'open' : ''; ?>>
								<summary class="igpa__filter-summary">
									<span class="igpa__filter-summary-title"><?php echo esc_html( $english ? 'collection' : 'συλλογή' ); ?></span>
									<span class="igpa__filter-summary-value"><?php echo esc_html( $current_collection_text ); ?></span>
								</summary>
								<div class="igpa__filter-disclosure-content">
									<div class="igpa__filter-options igpa__filter-options--compact">
										<label class="igpa__filter-option igpa__filter-option--compact"><input type="radio" name="catalog-collection" value="" <?php checked( '', $selected_collection ); ?>><span><?php echo esc_html( $english ? 'all collections' : 'όλες οι συλλογές' ); ?></span></label>
										<?php foreach ( $collections as $collection ) : ?>
											<label class="igpa__filter-option igpa__filter-option--compact"><input type="radio" name="catalog-collection" value="<?php echo esc_attr( $collection->slug ); ?>" <?php checked( $collection->slug, $selected_collection ); ?>><span><?php echo esc_html( $collection->name ); ?></span></label>
										<?php endforeach; ?>
									</div>
								</div>
							</details>
						<?php endif; ?>
					</div>

					<footer class="igpa__filter-foot">
						<button class="igpa__filter-apply" type="submit"><?php echo esc_html( $english ? 'show objects' : 'προβολή αντικειμένων' ); ?></button>
						<a class="igpa__filter-reset" href="<?php echo esc_url( $clear_url ); ?>"><?php echo esc_html( $english ? 'clear all' : 'καθαρισμός' ); ?></a>
					</footer>
				</form>
			</div>
		</div>

		<div class="igpa__grid" id="shop-objects" data-grid>
			<?php
			$order_index = 0;
			while ( $query->have_posts() ) :
				$query->the_post();

				$product = wc_get_product( get_the_ID() );
				if ( ! $product || ! $product->is_visible() ) {
					continue;
				}

				$product_id    = $product->get_id();
				$product_url   = get_permalink( $product_id );
				$product_name  = $product->get_name();
				$image_id      = absint( $product->get_image_id() );
				$gallery_ids   = array_values( array_filter( array_map( 'absint', $product->get_gallery_image_ids() ) ) );
				$second_id     = ! empty( $gallery_ids ) ? $gallery_ids[0] : 0;
				$is_pair       = 0 !== $second_id;

				$category_terms   = get_the_terms( $product_id, 'product_cat' );
				$collection_terms = get_the_terms( $product_id, 'product_collection' );

				$category_terms   = is_array( $category_terms ) ? $category_terms : array();
				$collection_terms = is_array( $collection_terms ) ? $collection_terms : array();
				$product_tags     = get_the_terms( $product_id, 'product_tag' );
				$product_tags     = is_array( $product_tags ) ? $product_tags : array();
				$is_one_only      = in_array( 'one-and-only', wp_list_pluck( $product_tags, 'slug' ), true );

				$category_slugs   = wp_list_pluck( $category_terms, 'slug' );
				$collection_slugs = wp_list_pluck( $collection_terms, 'slug' );

				/*
				 * Include parents so products assigned only to a child category
				 * still appear when their visible parent category is selected.
				 */
				foreach ( $category_terms as $category_term ) {
					$ancestor_ids = get_ancestors( $category_term->term_id, 'product_cat', 'taxonomy' );

					foreach ( $ancestor_ids as $ancestor_id ) {
						$ancestor = get_term( $ancestor_id, 'product_cat' );

						if ( $ancestor && ! is_wp_error( $ancestor ) ) {
							$category_slugs[] = $ancestor->slug;
						}
					}
				}

				$category_slugs = array_values( array_unique( $category_slugs ) );
				$price_value    = '' !== $product->get_price() ? (float) $product->get_price() : 0;
				$date_value     = get_post_timestamp( $product_id );
				?>
				<article
					class="igpa__card<?php echo $is_pair ? ' igpa__card--pair' : ''; ?>"
					data-card
					data-order="<?php echo esc_attr( $order_index ); ?>"
					data-date="<?php echo esc_attr( $date_value ); ?>"
					data-price="<?php echo esc_attr( $price_value ); ?>"
					data-categories="<?php echo esc_attr( implode( ' ', $category_slugs ) ); ?>"
					data-collections="<?php echo esc_attr( implode( ' ', $collection_slugs ) ); ?>"
				>
					<a
						class="igpa__media"
						href="<?php echo esc_url( $product_url ); ?>"
						aria-label="<?php echo esc_attr( $product_name ); ?>"
					>
						<span class="igpa__frame">
							<?php
							if ( $image_id ) {
								echo wp_get_attachment_image(
									$image_id,
									'large',
									false,
									array(
										'class'    => 'igpa__image',
										'loading'  => $order_index < 2 ? 'eager' : 'lazy',
										'decoding' => 'async',
										'alt'      => $product_name,
									)
								);
							} else {
								echo wc_placeholder_img(
									'large',
									array(
										'class' => 'igpa__image',
										'alt'   => $product_name,
									)
								);
							}
							?>
						</span>

						<?php if ( $is_pair ) : ?>
							<span class="igpa__frame" aria-hidden="true">
								<?php
								if ( $second_id ) {
									echo wp_get_attachment_image(
										$second_id,
										'large',
										false,
										array(
											'class'       => 'igpa__image',
											'loading'     => $order_index < 2 ? 'eager' : 'lazy',
											'decoding'    => 'async',
											'alt'         => '',
											'aria-hidden' => 'true',
										)
									);
								} else {
									echo wc_placeholder_img(
										'large',
										array(
											'class'       => 'igpa__image',
											'alt'         => '',
											'aria-hidden' => 'true',
										)
									);
								}
								?>
							</span>
						<?php endif; ?>

						<?php if ( ! $product->is_in_stock() ) : ?>
							<span class="igpa__sold"><?php echo esc_html( $english ? 'sold' : 'πωλήθηκε' ); ?></span>
						<?php endif; ?>
						<?php if ( $is_one_only ) : ?>
							<span class="igpa__one-only">one &amp; only</span>
						<?php endif; ?>
					</a>

					<div class="igpa__meta">
						<h2 class="igpa__name">
							<a href="<?php echo esc_url( $product_url ); ?>">
								<?php echo esc_html( $product_name ); ?>
							</a>
						</h2>
						<p class="igpa__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
					</div>
				</article>
				<?php
				$order_index++;
			endwhile;
			wp_reset_postdata();
			?>
		</div>

		<?php if ( $query->max_num_pages > 1 ) : ?>
			<?php
			$pagination_url  = remove_query_arg( 'catalog-page' );
			$pagination_base = str_replace( '999999999', '%#%', add_query_arg( 'catalog-page', 999999999, $pagination_url ) ) . '#shop-objects';
			$pagination      = paginate_links(
				array(
					'base'      => $pagination_base,
					'format'    => '',
					'current'   => $catalog_page,
					'total'     => (int) $query->max_num_pages,
					'mid_size'  => 1,
					'end_size'  => 1,
					'prev_text' => '<span aria-hidden="true">←</span><span class="screen-reader-text">' . esc_html( $english ? 'Previous page' : 'Προηγούμενη σελίδα' ) . '</span>',
					'next_text' => '<span aria-hidden="true">→</span><span class="screen-reader-text">' . esc_html( $english ? 'Next page' : 'Επόμενη σελίδα' ) . '</span>',
					'type'      => 'list',
				)
			);
			?>
			<?php if ( $pagination ) : ?>
				<nav class="igpa__pagination" aria-label="<?php echo esc_attr( $english ? 'Shop pages' : 'Σελίδες καταστήματος' ); ?>"><?php echo wp_kses_post( $pagination ); ?></nav>
			<?php endif; ?>
		<?php endif; ?>

		<p class="igpa__empty<?php echo $has_products ? '' : ' is-visible'; ?>" data-empty><?php echo esc_html( $english ? 'no objects match this selection.' : 'δεν βρέθηκαν αντικείμενα για αυτή την επιλογή.' ); ?></p>

		<script>
		(function () {
			"use strict";

			var root = document.getElementById(<?php echo wp_json_encode( $instance_id ); ?>);
			if (!root || root.dataset.ready === "true") return;
			root.dataset.ready = "true";

			var filterLayer = root.querySelector("[data-filter-layer]");
			var filterOpen = root.querySelector("[data-filter-open]");
			var filterClose = Array.prototype.slice.call(root.querySelectorAll("[data-filter-close]"));
			var filterScroll = root.querySelector("[data-filter-scroll]");
			var lastFilterFocus = null;
			var previousRootOverflow = "";
			var previousRootPadding = "";

			function lockFilterPage() {
				var page = document.documentElement;
				var bar = window.innerWidth - page.clientWidth;
				previousRootOverflow = page.style.overflow;
				previousRootPadding = page.style.paddingRight;
				page.style.overflow = "hidden";
				if (bar > 0) page.style.paddingRight = bar + "px";
			}

			function unlockFilterPage() {
				var page = document.documentElement;
				page.style.overflow = previousRootOverflow;
				page.style.paddingRight = previousRootPadding;
			}

			function openFilters() {
				if (!filterLayer) return;
				lastFilterFocus = document.activeElement;
				filterLayer.classList.add("is-open");
				filterLayer.setAttribute("aria-hidden", "false");
				document.documentElement.classList.add("igpa-filter-open");
				document.body.classList.add("igpa-filter-open");
				lockFilterPage();
				var closeButton = filterLayer.querySelector(".igpa__filter-close");
				if (closeButton) window.setTimeout(function () { closeButton.focus(); }, 80);
			}

			function closeFilters() {
				if (!filterLayer || !filterLayer.classList.contains("is-open")) return;
				filterLayer.classList.remove("is-open");
				filterLayer.setAttribute("aria-hidden", "true");
				document.documentElement.classList.remove("igpa-filter-open");
				document.body.classList.remove("igpa-filter-open");
				unlockFilterPage();
				if (lastFilterFocus && typeof lastFilterFocus.focus === "function") lastFilterFocus.focus();
			}

			if (filterOpen) filterOpen.addEventListener("click", openFilters);
			filterClose.forEach(function (button) {
				button.addEventListener("click", closeFilters);
			});

			if (filterScroll) {
				["wheel", "touchmove"].forEach(function (type) {
					filterScroll.addEventListener(type, function (event) {
						event.stopPropagation();
					}, {passive: true});
				});
			}

			Array.prototype.slice.call(root.querySelectorAll(".igpa__filter-disclosure")).forEach(function (disclosure) {
				var value = disclosure.querySelector(".igpa__filter-summary-value");
				Array.prototype.slice.call(disclosure.querySelectorAll("input[type='radio']")).forEach(function (input) {
					input.addEventListener("change", function () {
						var label = input.closest("label");
						var text = label ? label.querySelector("span") : null;
						if (value && text) value.textContent = text.textContent.trim();
					});
				});
			});

			document.addEventListener("keydown", function (event) {
				if (!filterLayer || !filterLayer.classList.contains("is-open")) return;
				if (event.key === "Escape") {
					event.preventDefault();
					closeFilters();
					return;
				}
				if (event.key !== "Tab") return;
				var focusable = Array.prototype.slice.call(filterLayer.querySelectorAll("button, a, input:not([disabled])")).filter(function (item) {
					return item.offsetParent !== null && item.getAttribute("tabindex") !== "-1";
				});
				if (!focusable.length) return;
				var first = focusable[0];
				var last = focusable[focusable.length - 1];
				if (event.shiftKey && document.activeElement === first) {
					event.preventDefault();
					last.focus();
				} else if (!event.shiftKey && document.activeElement === last) {
					event.preventDefault();
					first.focus();
				}
			});

			var grid = root.querySelector("[data-grid]");
			var cards = Array.prototype.slice.call(root.querySelectorAll("[data-card]"));
			var pickers = Array.prototype.slice.call(root.querySelectorAll("[data-picker]"));
			var countEls = root.querySelectorAll("[data-igpa-count]");
			var emptyEl = root.querySelector("[data-empty]");
			var activeCategory = "*";
			var activeCollection = "*";
			var activeSort = "featured";
			var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
			var observer = null;

			if (!reduceMotion && "IntersectionObserver" in window) {
				observer = new IntersectionObserver(function (entries) {
					entries.forEach(function (entry) {
						if (!entry.isIntersecting) return;
						entry.target.classList.add("is-visible");
						observer.unobserve(entry.target);
					});
				}, {
					rootMargin: "0px 0px -7% 0px",
					threshold: 0.06
				});
			}

			function words(value) {
				return (value || "").split(" ").filter(Boolean);
			}

			function matches(card) {
				var categoryMatch =
					activeCategory === "*" ||
					words(card.dataset.categories).indexOf(activeCategory) !== -1;

				var collectionMatch =
					activeCollection === "*" ||
					words(card.dataset.collections).indexOf(activeCollection) !== -1;

				return categoryMatch && collectionMatch;
			}

			function reveal(card) {
				if (reduceMotion || !observer) {
					card.classList.add("is-visible");
					return;
				}

				if (!card.classList.contains("is-visible")) {
					observer.observe(card);
				}
			}

			function updateView() {
				var visible = cards.filter(function (card) {
					var show = matches(card);
					card.hidden = !show;
					if (show) reveal(card);
					return show;
				});

				countEls.forEach(function (element) {
					element.textContent = String(visible.length);
				});

				emptyEl.classList.toggle("is-visible", visible.length === 0);
			}

			function sortCards() {
				var sorted = cards.slice().sort(function (a, b) {
					if (activeSort === "newest") {
						return Number(b.dataset.date) - Number(a.dataset.date);
					}
					if (activeSort === "price-asc") {
						return Number(a.dataset.price) - Number(b.dataset.price);
					}
					if (activeSort === "price-desc") {
						return Number(b.dataset.price) - Number(a.dataset.price);
					}
					return Number(a.dataset.order) - Number(b.dataset.order);
				});

				sorted.forEach(function (card) {
					grid.appendChild(card);
				});

				cards = sorted;
				updateView();
			}

			function closePickers(except) {
				pickers.forEach(function (picker) {
					if (picker === except) return;
					picker.classList.remove("is-open");
					var trigger = picker.querySelector("[data-picker-trigger]");
					if (trigger) trigger.setAttribute("aria-expanded", "false");
				});
			}

			/* ---------------------------------------------------------------
			   The row of subcategories.

			   One level is shown at a time: the children of whatever category is
			   selected. Choosing one filters by it and steps down into its own
			   children; a term with no children keeps its siblings on screen so
			   the row never empties and there is always a way sideways. "all"
			   returns to the top.
			   -------------------------------------------------------------- */

			var subButtons = Array.prototype.slice.call(
				root.querySelectorAll("[data-sub]")
			);
			var subAll = subButtons.filter(function (button) {
				return button.dataset.subValue === "*";
			})[0];
			var subTerms = subButtons.filter(function (button) {
				return button.dataset.subValue !== "*";
			});

			function subParentOf(slug) {
				for (var i = 0; i < subTerms.length; i++) {
					if (subTerms[i].dataset.subValue === slug) {
						return subTerms[i].dataset.subParent || "";
					}
				}
				return "";
			}

			function subChildrenOf(slug) {
				return subTerms.filter(function (button) {
					return (button.dataset.subParent || "") === slug;
				});
			}

			function renderSubs() {
				if (!subTerms.length) return;

				var shown;

				if (activeCategory === "*") {
					shown = subChildrenOf("");
				} else {
					shown = subChildrenOf(activeCategory);
					/* A leaf has nothing below it, so the row stays on its level. */
					if (!shown.length) shown = subChildrenOf(subParentOf(activeCategory));
					if (!shown.length) shown = subChildrenOf("");
				}

				subTerms.forEach(function (button) {
					var visible = shown.indexOf(button) !== -1;
					var current = button.dataset.subValue === activeCategory;
					button.hidden = !visible && !current;
					button.classList.toggle("is-active", current);
					button.setAttribute("aria-pressed", current ? "true" : "false");
				});

				if (subAll) {
					subAll.classList.toggle("is-active", activeCategory === "*");
					subAll.setAttribute("aria-pressed", activeCategory === "*" ? "true" : "false");
				}
			}

			/* The dropdown and this row set the same thing, so whichever is used
			   the other has to follow. */
			function setCategory(value) {
				activeCategory = value || "*";
				renderSubs();
				updateView();

				var picker = root.querySelector("[data-kind='category']");
				if (!picker) return;

				var label = picker.querySelector("[data-picker-label]");
				var chosen = picker.querySelector("[data-option-value='" + activeCategory + "']");

				Array.prototype.slice.call(
					picker.querySelectorAll("[data-option-value]")
				).forEach(function (option) {
					option.setAttribute("aria-selected", option === chosen ? "true" : "false");
				});

				if (label) {
					label.textContent = chosen
						? (chosen.dataset.optionLabel || chosen.textContent.trim())
						: "category";
				}
			}

			subButtons.forEach(function (button) {
				button.addEventListener("click", function () {
					setCategory(button.dataset.subValue);
				});
			});

			renderSubs();

			pickers.forEach(function (picker) {
				var trigger = picker.querySelector("[data-picker-trigger]");
				var label = picker.querySelector("[data-picker-label]");
				var options = Array.prototype.slice.call(
					picker.querySelectorAll("[data-option-value]")
				);
				var kind = picker.dataset.kind;

				if (!trigger) return;

				trigger.addEventListener("click", function (event) {
					event.stopPropagation();
					var willOpen = !picker.classList.contains("is-open");
					closePickers(picker);
					picker.classList.toggle("is-open", willOpen);
					trigger.setAttribute("aria-expanded", willOpen ? "true" : "false");
				});

				options.forEach(function (option) {
					option.addEventListener("click", function () {
						var value = option.dataset.optionValue || "*";
						var nextLabel = option.dataset.optionLabel || option.textContent.trim();

						options.forEach(function (item) {
							item.setAttribute(
								"aria-selected",
								item === option ? "true" : "false"
							);
						});

						if (label) label.textContent = nextLabel;

						if (kind === "category") {
							activeCategory = value;
							renderSubs();
							updateView();
						} else if (kind === "collection") {
							activeCollection = value;
							updateView();
						} else if (kind === "sort") {
							activeSort = value;
							sortCards();
						}

						closePickers();
					});
				});
			});

			document.addEventListener("click", function (event) {
				if (!root.contains(event.target)) {
					closePickers();
					return;
				}

				if (!event.target.closest("[data-picker]")) {
					closePickers();
				}
			});

			document.addEventListener("keydown", function (event) {
				if (event.key !== "Escape") return;
				closePickers();
			});

			/*
			 * The navbar changes height while scrolling. Reading its real bottom
			 * edge keeps this transparent toolbar immediately below it at all times.
			 */
			var siteHeader = document.getElementById("ioulia-header");
			var shopToolbar = root.querySelector(".igpa__toolbar");
			var stickyFrame = 0;

			function syncStickyTop() {
				stickyFrame = 0;
				var stickyTop = 16;
				if (!siteHeader) {
					root.style.setProperty("--igpa-sticky-top", stickyTop + "px");
				} else {
					var bottom = Math.max(0, Math.ceil(siteHeader.getBoundingClientRect().bottom));
					stickyTop = bottom + 8;
					root.style.setProperty("--igpa-sticky-top", stickyTop + "px");
				}

				if (shopToolbar) {
					shopToolbar.classList.toggle("is-stuck", shopToolbar.getBoundingClientRect().top <= stickyTop + 1);
				}
			}

			function requestStickySync() {
				if (stickyFrame) return;
				stickyFrame = window.requestAnimationFrame(syncStickyTop);
			}

			window.addEventListener("resize", requestStickySync);
			window.addEventListener("scroll", requestStickySync, { passive: true });

			if (siteHeader && "ResizeObserver" in window) {
				new ResizeObserver(requestStickySync).observe(siteHeader);
			}

			syncStickyTop();
			updateView();
		})();
		</script>
	</section>
	<?php

	return ob_get_clean();
}
