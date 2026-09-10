<?php
/**
 * Minimal template for the maintenance mode.
 * Shows only the page content, without the theme header, footer and menu.
 *
 * @package ACW_Maintenance_Mode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Container width.
 * When the "Full width" option is selected the content spans the whole viewport.
 * Otherwise it uses the default width inherited from the theme:
 * 1) theme.json -> settings.layout.contentSize (e.g. "720px", "40rem")
 * 2) global $content_width (in px)
 * 3) plugin fallback
 */
if ( 'full' === get_option( 'acw_mm_width' ) ) {
	$acw_mm_width = 'none';
} else {
	$acw_mm_width = '';
	if ( function_exists( 'wp_get_global_settings' ) ) {
		$acw_mm_layout = wp_get_global_settings( array( 'layout' ) );
		if ( ! empty( $acw_mm_layout['contentSize'] ) ) {
			$acw_mm_width = $acw_mm_layout['contentSize'];
		}
	}
	if ( '' === $acw_mm_width && ! empty( $GLOBALS['content_width'] ) ) {
		$acw_mm_width = (int) $GLOBALS['content_width'] . 'px';
	}
	if ( '' === $acw_mm_width ) {
		$acw_mm_width = '720px';
	}
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<?php wp_head(); ?>
	<style>
		.acw-mm-wrap {
			max-width: <?php echo esc_html( $acw_mm_width ); ?>;
			margin: 0 auto;
			padding: <?php echo 'none' === $acw_mm_width ? '0' : '8vh 24px'; ?>;
		}
	</style>
</head>
<body <?php body_class( 'acw-mm-body' ); ?>>
	<main class="acw-mm-wrap">
		<?php
		while ( have_posts() ) :
			the_post();
			the_content();
		endwhile;
		?>
	</main>
	<?php wp_footer(); ?>
</body>
</html>
