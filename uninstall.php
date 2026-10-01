<?php
/**
 * Uninstall handler: remove plugin options (on every site of a multisite network).
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$acwmmbp_options = array( 'acwmmbp_enabled', 'acwmmbp_page_id', 'acwmmbp_width', 'acwmmbp_roles', 'acwmmbp_allowed_pages' );

if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $acwmmbp_site_id ) {
		switch_to_blog( $acwmmbp_site_id );
		foreach ( $acwmmbp_options as $acwmmbp_option ) {
			delete_option( $acwmmbp_option );
		}
		restore_current_blog();
	}
} else {
	foreach ( $acwmmbp_options as $acwmmbp_option ) {
		delete_option( $acwmmbp_option );
	}
}
