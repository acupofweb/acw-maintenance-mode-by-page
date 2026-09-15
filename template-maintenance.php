<?php
/**
 * Minimal template for the maintenance mode.
 * Shows only the page content, without the theme header, footer and menu.
 * The container styles are enqueued from the main plugin file and printed by
 * wp_head() below.
 *
 * @package ACW_Maintenance_Mode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<?php wp_head(); ?>
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
