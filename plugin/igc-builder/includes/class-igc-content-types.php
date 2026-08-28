<?php
defined( 'ABSPATH' ) || exit;

final class IGC_Content_Types {
	public static function init(): void {
		add_action( 'init', array( self::class, 'register' ), 0 );
	}

	public static function register(): void {
		self::register_type(
			'igc_code_block',
			__( 'Code Blocks', 'igc-builder' ),
			__( 'Code Block', 'igc-builder' ),
			'dashicons-editor-code'
		);

		self::register_type(
			'igc_theme_template',
			__( 'Theme Templates', 'igc-builder' ),
			__( 'Theme Template', 'igc-builder' ),
			'dashicons-layout'
		);

		self::register_type(
			'igc_php_snippet',
			__( 'PHP Snippets', 'igc-builder' ),
			__( 'PHP Snippet', 'igc-builder' ),
			'dashicons-editor-code'
		);

		self::register_type(
			'igc_canvas',
			__( 'Visual Pages', 'igc-builder' ),
			__( 'Visual Page', 'igc-builder' ),
			'dashicons-welcome-widgets-menus',
			false
		);
	}

	private static function register_type( string $post_type, string $plural, string $singular, string $icon, string|false $menu = 'igc-builder' ): void {
		register_post_type(
			$post_type,
			array(
				'labels' => array(
					'name'          => $plural,
					'singular_name' => $singular,
					'add_new_item'  => sprintf( __( 'Add %s', 'igc-builder' ), $singular ),
					'edit_item'     => sprintf( __( 'Edit %s', 'igc-builder' ), $singular ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => $menu,
				'show_in_rest'        => false,
				'supports'            => array( 'title', 'revisions' ),
				'menu_icon'           => $icon,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'exclude_from_search' => true,
			)
		);
	}
}
