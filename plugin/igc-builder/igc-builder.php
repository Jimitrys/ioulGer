<?php
/**
 * Plugin Name: Site Studio
 * Description: A lightweight, code-first WordPress site framework with global styles, reusable blocks, theme templates and guarded PHP snippets.
 * Version: 1.1.5
 * Author: Dimitris Antoniou
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * Text Domain: igc-builder
 */

defined( 'ABSPATH' ) || exit;

define( 'IGC_BUILDER_VERSION', '1.1.5' );
define( 'IGC_BUILDER_FILE', __FILE__ );
define( 'IGC_BUILDER_DIR', plugin_dir_path( __FILE__ ) );
define( 'IGC_BUILDER_URL', plugin_dir_url( __FILE__ ) );

require_once IGC_BUILDER_DIR . 'includes/class-igc-content-types.php';
require_once IGC_BUILDER_DIR . 'includes/class-igc-admin.php';
require_once IGC_BUILDER_DIR . 'includes/class-igc-renderer.php';
require_once IGC_BUILDER_DIR . 'includes/class-igc-visual-builder.php';
require_once IGC_BUILDER_DIR . 'includes/class-igc-snippet-validator.php';
require_once IGC_BUILDER_DIR . 'includes/class-igc-snippets.php';
require_once IGC_BUILDER_DIR . 'includes/class-igc-snippet-importer.php';
require_once IGC_BUILDER_DIR . 'includes/class-igc-migration.php';
require_once IGC_BUILDER_DIR . 'includes/class-igc-woocommerce.php';
require_once IGC_BUILDER_DIR . 'includes/class-igc-git-workspace.php';

final class IGC_Builder {
	private static ?IGC_Builder $instance = null;

	public static function instance(): IGC_Builder {
		return self::$instance ??= new self();
	}

	private function __construct() {
		IGC_Content_Types::init();
		IGC_Admin::init();
		IGC_Renderer::init();
		IGC_Visual_Builder::init();
		IGC_Snippets::init();
		IGC_Snippet_Importer::init();
		IGC_Migration::init();
		IGC_WooCommerce::init();
		IGC_Git_Workspace::init();
	}
}

add_action( 'plugins_loaded', array( 'IGC_Builder', 'instance' ), 5 );

register_activation_hook(
	__FILE__,
	static function (): void {
		if ( false === get_option( 'igc_design_tokens', false ) ) {
			add_option( 'igc_design_tokens', IGC_Admin::default_tokens(), '', false );
		}
	}
);
