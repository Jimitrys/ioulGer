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

add_shortcode( 'ioulia_product_archive', 'igpa_render_product_archive' );
function igpa_render_product_archive( $atts = array() ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return '';
	}

	$atts = shortcode_atts(
		array(
			'title' => 'shop',
			'limit' => '60',
		),
		$atts,
		'ioulia_product_archive'
	);

	$limit = max( 1, min( 120, absint( $atts['limit'] ) ) );

	$query = new WP_Query(
		array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'posts_per_page'         => $limit,
			'orderby'                => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'meta_query'             => WC()->query->get_meta_query(),
			'tax_query'              => WC()->query->get_tax_query(),
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
		)
	);

	if ( ! $query->have_posts() ) {
		return '<p class="igpa-empty">no objects found.</p>';
	}

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

	$instance_id  = wp_unique_id( 'igpa-' );
	$product_count = (int) $query->post_count;

	ob_start();
	?>
	<section
		id="<?php echo esc_attr( $instance_id ); ?>"
		class="igpa"
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
				font-size: 10px;
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
				justify-content: end;
				gap: 24px;
				pointer-events: none;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__toolbar-count,
			#<?php echo esc_attr( $instance_id ); ?> .igpa__controls {
				pointer-events: auto;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__toolbar-count {
				color: var(--igpa-muted);
				font-size: 9px;
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

			#<?php echo esc_attr( $instance_id ); ?> .igpa__picker {
				position: relative;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__trigger {
				appearance: none;
				display: inline-flex;
				margin: 0;
				padding: 5px 0;
				border: 0;
				border-bottom: 1px solid transparent;
				border-radius: 0;
				background: transparent;
				color: var(--igpa-ink);
				box-shadow: none;
				cursor: pointer;
				align-items: center;
				gap: 8px;
				font-size: clamp(9px, .7vw, 11px);
				font-weight: 400;
				line-height: 1;
				letter-spacing: .025em;
				text-transform: lowercase;
				white-space: nowrap;
				transition: border-color 180ms ease, color 180ms ease;
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

			#<?php echo esc_attr( $instance_id ); ?> .igpa__picker.is-open .igpa__trigger,
			#<?php echo esc_attr( $instance_id ); ?> .igpa__trigger:hover {
				border-bottom-color: currentColor;
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
				font-size: 10px;
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
				font-size: 8px;
				font-weight: 400;
				line-height: 1;
				letter-spacing: .025em;
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
				font-size: clamp(10px, .78vw, 12px);
				font-weight: 400;
				line-height: 1.35;
				letter-spacing: -.008em;
				text-decoration: none;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__price {
				color: var(--igpa-ink);
				font-size: clamp(9px, .72vw, 11px);
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
				font-size: 12px;
				font-weight: 400;
				text-transform: lowercase;
			}

			#<?php echo esc_attr( $instance_id ); ?> .igpa__empty.is-visible {
				display: block;
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

				#<?php echo esc_attr( $instance_id ); ?> .igpa__title {
					font-size: 34px;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__toolbar {
					min-height: 42px;
					padding-top: 9px;
					padding-bottom: 9px;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__toolbar-count {
					display: none;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__controls {
					width: 100%;
					gap: 0;
					justify-content: space-between;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__trigger {
					font-size: 9px;
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
					font-size: 10px;
				}
			}

			@media (prefers-reduced-motion: reduce) {
				#<?php echo esc_attr( $instance_id ); ?> .igpa__card {
					opacity: 1;
					transform: none;
					transition: none;
				}

				#<?php echo esc_attr( $instance_id ); ?> .igpa__image,
				#<?php echo esc_attr( $instance_id ); ?> .igpa__menu {
					transition: none;
				}
			}
		</style>

		<header class="igpa__head">
			
			
		</header>

		<nav class="igpa__toolbar" aria-label="shop filters">
			

			<div class="igpa__controls">
				<div class="igpa__picker" data-picker data-kind="category">
					<button
						class="igpa__trigger"
						type="button"
						aria-expanded="false"
						aria-controls="<?php echo esc_attr( $instance_id ); ?>-categories"
						data-picker-trigger
					><span data-picker-label>category</span></button>

					<div
						class="igpa__menu"
						id="<?php echo esc_attr( $instance_id ); ?>-categories"
						role="listbox"
						aria-label="product category"
					>
						<button
							class="igpa__option"
							type="button"
							role="option"
							aria-selected="true"
							data-option-value="*"
							data-option-label="category"
						>all categories</button>

						<?php foreach ( $categories as $category ) : ?>
							<button
								class="igpa__option"
								type="button"
								role="option"
								aria-selected="false"
								data-option-value="<?php echo esc_attr( $category->slug ); ?>"
								data-option-label="<?php echo esc_attr( $category->name ); ?>"
							><?php echo esc_html( $category->name ); ?></button>
						<?php endforeach; ?>
					</div>
				</div>

				<?php if ( ! empty( $collections ) ) : ?>
					<div class="igpa__picker" data-picker data-kind="collection">
						<button
							class="igpa__trigger"
							type="button"
							aria-expanded="false"
							aria-controls="<?php echo esc_attr( $instance_id ); ?>-collections"
							data-picker-trigger
						><span data-picker-label>collection</span></button>

						<div
							class="igpa__menu"
							id="<?php echo esc_attr( $instance_id ); ?>-collections"
							role="listbox"
							aria-label="product collection"
						>
							<button
								class="igpa__option"
								type="button"
								role="option"
								aria-selected="true"
								data-option-value="*"
								data-option-label="collection"
							>all collections</button>

							<?php foreach ( $collections as $collection ) : ?>
								<button
									class="igpa__option"
									type="button"
									role="option"
									aria-selected="false"
									data-option-value="<?php echo esc_attr( $collection->slug ); ?>"
									data-option-label="<?php echo esc_attr( $collection->name ); ?>"
								><?php echo esc_html( $collection->name ); ?></button>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

				<div class="igpa__picker" data-picker data-kind="sort">
					<button
						class="igpa__trigger"
						type="button"
						aria-expanded="false"
						aria-controls="<?php echo esc_attr( $instance_id ); ?>-sort"
						data-picker-trigger
					><span data-picker-label>sort</span></button>

					<div
						class="igpa__menu"
						id="<?php echo esc_attr( $instance_id ); ?>-sort"
						role="listbox"
						aria-label="sort products"
					>
						<button
							class="igpa__option"
							type="button"
							role="option"
							aria-selected="true"
							data-option-value="featured"
							data-option-label="sort"
						>featured</button>
						<button
							class="igpa__option"
							type="button"
							role="option"
							aria-selected="false"
							data-option-value="newest"
							data-option-label="newest"
						>newest</button>
						<button
							class="igpa__option"
							type="button"
							role="option"
							aria-selected="false"
							data-option-value="price-asc"
							data-option-label="price: low"
						>price: low to high</button>
						<button
							class="igpa__option"
							type="button"
							role="option"
							aria-selected="false"
							data-option-value="price-desc"
							data-option-label="price: high"
						>price: high to low</button>
					</div>
				</div>
			</div>
		</nav>

		<div class="igpa__grid" data-grid>
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
				$second_id     = ! empty( $gallery_ids ) ? $gallery_ids[0] : $image_id;

				/*
				 * A quiet repeating rhythm: the first two cards of each group
				 * establish the paired-image reference, then single and paired
				 * cards alternate. Every media block keeps exactly the same height.
				 */
				$pair_positions = array( 0, 1, 4, 7 );
				$is_pair        = in_array( $order_index % 8, $pair_positions, true );

				$category_terms   = get_the_terms( $product_id, 'product_cat' );
				$collection_terms = get_the_terms( $product_id, 'product_collection' );

				$category_terms   = is_array( $category_terms ) ? $category_terms : array();
				$collection_terms = is_array( $collection_terms ) ? $collection_terms : array();

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
							<span class="igpa__sold">sold</span>
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

		<p class="igpa__empty" data-empty>no objects match this selection.</p>

		<script>
		(function () {
			"use strict";

			var root = document.getElementById(<?php echo wp_json_encode( $instance_id ); ?>);
			if (!root || root.dataset.ready === "true") return;
			root.dataset.ready = "true";

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
				return (value || "").split(/s+/).filter(Boolean);
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
			var stickyFrame = 0;

			function syncStickyTop() {
				stickyFrame = 0;
				if (!siteHeader) {
					root.style.setProperty("--igpa-sticky-top", "16px");
					return;
				}

				var bottom = Math.max(0, Math.ceil(siteHeader.getBoundingClientRect().bottom));
				root.style.setProperty("--igpa-sticky-top", (bottom + 8) + "px");
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
