<?php
/**
 * Uninstall handler: remove plugin options.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'acw_mm_enabled' );
delete_option( 'acw_mm_page_id' );
delete_option( 'acw_mm_width' );
