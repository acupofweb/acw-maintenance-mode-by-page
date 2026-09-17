<?php
/**
 * Uninstall handler: remove plugin options.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'acwmmbp_enabled' );
delete_option( 'acwmmbp_page_id' );
delete_option( 'acwmmbp_width' );
