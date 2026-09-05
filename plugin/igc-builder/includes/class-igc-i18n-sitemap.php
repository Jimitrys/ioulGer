<?php
defined( 'ABSPATH' ) || exit;

/**
 * Secondary-language URLs for the WordPress sitemap.
 *
 * Every URL WordPress knows about is a default-language permalink, so without
 * this the prefixed versions are never submitted and only one language is
 * discoverable. Each entry carries its alternates, which is what tells a search
 * engine the two URLs are the same page in different languages rather than
 * duplicates competing with each other.
 */
final class IGC_I18N_Sitemap extends WP_Sitemaps_Provider {

	public function __construct() {
		// Core sitemap rewrite rules only accept letters in the provider segment.
		$this->name        = 'ioulia';
		$this->object_type = 'ioulia';
	}

	public function get_url_list( $page_num, $object_subtype = '' ): array {
		$language = sanitize_key( (string) $object_subtype );
		$urls     = array();

		if ( ! isset( IGC_I18N::secondary_languages()[ $language ] ) ) {
			return $urls;
		}

		foreach ( $this->public_paths() as $path ) {
			$urls[] = array(
				'loc' => home_url( '/' . ltrim( IGC_I18N::prefix_path( $path, $language ), '/' ) ),
			);
		}

		return $urls;
	}

	public function get_max_num_pages( $object_subtype = '' ): int {
		return 1;
	}

	public function get_object_subtypes(): array {
		$subtypes = array();

		foreach ( IGC_I18N::secondary_languages() as $code => $language ) {
			$subtypes[ $code ] = (object) array(
				'name'  => $code,
				'label' => $language['name'],
			);
		}

		return $subtypes;
	}

	/**
	 * Published pages and products, as home-relative paths. Deliberately modest:
	 * a sitemap that lists the pages a visitor can reach is more useful than one
	 * that lists everything the database contains.
	 */
	private function public_paths(): array {
		$paths = array( '' );

		$posts = get_posts(
			array(
				'post_type'        => apply_filters( 'igc_i18n_sitemap_post_types', array( 'page', 'product' ) ),
				'post_status'      => 'publish',
				'posts_per_page'   => 2000,
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);

		foreach ( $posts as $id ) {
			$permalink = get_permalink( $id );

			if ( ! $permalink ) {
				continue;
			}

			$path = IGC_I18N::unprefix_path( IGC_I18N::relative_path( $permalink ) );

			if ( '' !== $path && IGC_I18N::is_translatable_path( $path ) ) {
				$paths[] = $path;
			}
		}

		return array_values( array_unique( $paths ) );
	}
}
