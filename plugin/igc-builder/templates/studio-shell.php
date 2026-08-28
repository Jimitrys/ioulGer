<?php
/** Site Studio full-page template shell. */
defined( 'ABSPATH' ) || exit;

$igc_location = IGC_Renderer::current_location();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'site-studio' ); ?>>
<?php wp_body_open(); ?>
<?php
$igc_header = IGC_Renderer::render_location( 'header' );
if ( '' !== trim( $igc_header ) ) {
	echo $igc_header; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
?>
<main id="main" class="site-studio__main">
	<?php echo IGC_Renderer::render_location( $igc_location ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</main>
<?php
$igc_footer = IGC_Renderer::render_location( 'footer' );
if ( '' !== trim( $igc_footer ) ) {
	echo $igc_footer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
wp_footer();
?>
</body>
</html>
