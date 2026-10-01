<?php
/**
 * Plugin Name: ACW Maintenance Mode by Page
 * Description: Put your site in maintenance mode showing visitors a single page of your choice, picked from your existing pages. Choose which user roles keep browsing the whole site.
 * Author: A Cup of Web
 * Author URI: https://www.acupofweb.it/
 * Version: 1.1.0
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: acw-maintenance-mode-by-page
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ACWMMBP_Maintenance_Mode {

	const VERSION        = '1.1.0';
	const OPTION_ENABLED = 'acwmmbp_enabled';
	const OPTION_PAGE_ID = 'acwmmbp_page_id';
	const OPTION_WIDTH   = 'acwmmbp_width';
	const OPTION_ROLES   = 'acwmmbp_roles';
	const OPTION_ALLOWED = 'acwmmbp_allowed_pages';
	const SETTINGS_SLUG  = 'acw-maintenance-mode-by-page';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'maybe_block_admin' ) );
		add_action( 'admin_notices', array( $this, 'invalid_page_notice' ) );
		add_action( 'template_redirect', array( $this, 'maybe_redirect' ) );
		add_filter( 'template_include', array( $this, 'maybe_use_blank_template' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_notice' ), 100 );

		// Purge page caches whenever the settings are saved. pre_update_option_*
		// runs even when a value does not change, unlike update_option_*.
		foreach ( array( self::OPTION_ENABLED, self::OPTION_PAGE_ID, self::OPTION_WIDTH, self::OPTION_ROLES, self::OPTION_ALLOWED ) as $option ) {
			add_filter( 'pre_update_option_' . $option, array( $this, 'schedule_cache_purge' ) );
		}
	}

	/**
	 * True when the maintenance checkbox is on.
	 */
	private function is_enabled() {
		return (bool) get_option( self::OPTION_ENABLED );
	}

	/**
	 * ID of the maintenance page, or 0 when no valid page is selected.
	 */
	private function get_page_id() {
		$page_id = (int) get_option( self::OPTION_PAGE_ID );
		return $this->is_valid_page( $page_id ) ? $page_id : 0;
	}

	/**
	 * A valid maintenance page is a published page without password, other than
	 * the posts page (on the posts page is_page() is false, which would cause a
	 * redirect loop; a password would show the password form to visitors).
	 */
	private function is_valid_page( $page_id ) {
		if ( $page_id <= 0 || (int) get_option( 'page_for_posts' ) === $page_id ) {
			return false;
		}
		$page = get_post( $page_id );
		return $page && 'page' === $page->post_type && 'publish' === $page->post_status && '' === $page->post_password;
	}

	/**
	 * Roles that keep browsing the site while maintenance is active.
	 * Until the setting is saved, default to the roles that can write content,
	 * so subscribers and customers see the maintenance page.
	 */
	private function get_bypass_roles() {
		$roles = get_option( self::OPTION_ROLES, false );
		if ( false === $roles ) {
			$roles = array();
			foreach ( wp_roles()->role_objects as $role => $role_object ) {
				if ( $role_object->has_cap( 'edit_posts' ) ) {
					$roles[] = $role;
				}
			}
		}
		return (array) $roles;
	}

	/**
	 * True when the current user can browse the site and the dashboard.
	 * Administrators always can, so nobody can lock themselves out.
	 */
	private function user_can_bypass() {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$user       = wp_get_current_user();
		$can_bypass = current_user_can( 'manage_options' ) || (bool) array_intersect( (array) $user->roles, $this->get_bypass_roles() );

		/**
		 * Filters whether the current user can browse the site during maintenance.
		 *
		 * @param bool    $can_bypass Whether the user can bypass maintenance.
		 * @param WP_User $user       The current user.
		 */
		return (bool) apply_filters( 'acwmmbp_user_can_bypass', $can_bypass, $user );
	}

	/**
	 * Pages that stay reachable during maintenance: the ones selected in the
	 * settings plus the login, registration and lost password pages when a
	 * plugin moves them to the front end (e.g. Paid Memberships Pro).
	 */
	private function get_allowed_page_ids() {
		$ids = (array) get_option( self::OPTION_ALLOWED, array() );
		foreach ( $this->get_login_page_ids() as $id ) {
			$ids[] = $id;
		}

		/**
		 * Filters the IDs of the pages that stay reachable during maintenance.
		 *
		 * @param int[] $ids Page IDs.
		 */
		$ids = apply_filters( 'acwmmbp_allowed_page_ids', $ids );

		return array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
	}

	/**
	 * Front-end pages used as login, registration or lost password page.
	 */
	private function get_login_page_ids() {
		$ids = array();
		foreach ( array( wp_login_url(), wp_registration_url(), wp_lostpassword_url() ) as $url ) {
			if ( false !== strpos( $url, 'wp-login.php' ) ) {
				continue;
			}
			$id = url_to_postid( $url );
			if ( $id ) {
				$ids[] = $id;
			}
		}
		return array_unique( $ids );
	}

	/**
	 * True when the current request is the maintenance page shown to a user
	 * who cannot bypass maintenance.
	 */
	private function is_maintenance_request() {
		if ( ! $this->is_enabled() || $this->user_can_bypass() ) {
			return false;
		}
		$page_id = $this->get_page_id();
		return $page_id && is_page( $page_id );
	}

	/**
	 * Redirect visitors (and users whose role cannot bypass maintenance) to the
	 * maintenance page.
	 */
	public function maybe_redirect() {
		if ( ! $this->is_enabled() || $this->user_can_bypass() ) {
			return;
		}

		$page_id = $this->get_page_id();

		// If we are already on the maintenance page, serve it with a 503 status
		// (temporarily unavailable) so search engines do not index it as
		// permanent content.
		if ( $page_id && is_page( $page_id ) ) {
			$this->send_503_headers();
			return;
		}

		if ( is_page() && in_array( get_queried_object_id(), $this->get_allowed_page_ids(), true ) ) {
			return;
		}

		// The selected page is missing or no longer published: keep the site
		// closed with a generic message instead of redirecting to a dead URL.
		if ( ! $page_id ) {
			$this->send_503_headers();
			wp_die(
				esc_html__( 'The site is undergoing maintenance. Please come back soon.', 'acw-maintenance-mode-by-page' ),
				esc_html__( 'Maintenance', 'acw-maintenance-mode-by-page' ),
				array( 'response' => 503 )
			);
		}

		nocache_headers();
		wp_safe_redirect( get_permalink( $page_id ), 302 );
		exit;
	}

	/**
	 * Keep logged-in users whose role cannot bypass maintenance out of the
	 * dashboard. AJAX and admin-post.php stay reachable for front-end features.
	 */
	public function maybe_block_admin() {
		if ( ! $this->is_enabled() || ! is_user_logged_in() || $this->user_can_bypass() || wp_doing_ajax() ) {
			return;
		}
		if ( isset( $GLOBALS['pagenow'] ) && 'admin-post.php' === $GLOBALS['pagenow'] ) {
			return;
		}

		$page_id = $this->get_page_id();
		nocache_headers();
		wp_safe_redirect( $page_id ? get_permalink( $page_id ) : home_url( '/' ), 302 );
		exit;
	}

	/**
	 * On the maintenance page load a minimal template that shows only the
	 * content, without the theme header/footer/menu.
	 */
	public function maybe_use_blank_template( $template ) {
		if ( $this->is_maintenance_request() ) {
			return plugin_dir_path( __FILE__ ) . 'template-maintenance.php';
		}
		return $template;
	}

	/**
	 * Enqueue the maintenance page styles (only on the maintenance page). The
	 * rules are added inline so the container width can follow the selected
	 * option and the theme.
	 */
	public function enqueue_styles() {
		if ( ! $this->is_maintenance_request() ) {
			return;
		}

		wp_register_style( 'acwmmbp', false, array(), self::VERSION );
		wp_enqueue_style( 'acwmmbp' );

		$width   = $this->get_container_width();
		$padding = 'none' === $width ? '0' : '8vh 24px';
		$css     = '.acw-mm-wrap{max-width:' . $width . ';margin:0 auto;padding:' . $padding . ';}';
		wp_add_inline_style( 'acwmmbp', $css );
	}

	/**
	 * Container width for the maintenance page.
	 * "full" spans the whole viewport; otherwise inherit from the theme
	 * (theme.json contentSize, then $content_width), with a fallback.
	 */
	private function get_container_width() {
		if ( 'full' === get_option( self::OPTION_WIDTH ) ) {
			return 'none';
		}

		$theme_size = '';
		if ( function_exists( 'wp_get_global_settings' ) ) {
			$layout = wp_get_global_settings( array( 'layout' ) );
			if ( ! empty( $layout['contentSize'] ) && is_string( $layout['contentSize'] ) ) {
				$theme_size = $layout['contentSize'];
			}
		}

		if ( $this->is_simple_length( $theme_size ) ) {
			$fallback = $theme_size;
		} elseif ( ! empty( $GLOBALS['content_width'] ) ) {
			$fallback = (int) $GLOBALS['content_width'] . 'px';
		} else {
			$fallback = '720px';
		}

		// Themes with theme.json print the content size as a CSS custom
		// property, which also resolves values like clamp() or var().
		if ( '' !== $theme_size ) {
			return 'var(--wp--style--global--content-size, ' . $fallback . ')';
		}
		return $fallback;
	}

	/**
	 * True for a plain CSS length such as "720px", "40rem" or "100%".
	 */
	private function is_simple_length( $value ) {
		return (bool) preg_match( '/^\d+(\.\d+)?(px|rem|em|%|vw|ch)$/', $value );
	}

	/**
	 * Set the HTTP 503 headers for the maintenance page.
	 */
	private function send_503_headers() {
		if ( headers_sent() ) {
			return;
		}
		status_header( 503 );
		header( 'Retry-After: 3600' );
		nocache_headers();
	}

	/**
	 * Purge the caches once, at the end of the request that saves the settings,
	 * after all the options have been updated.
	 */
	public function schedule_cache_purge( $value ) {
		if ( ! has_action( 'shutdown', array( $this, 'purge_cache' ) ) ) {
			add_action( 'shutdown', array( $this, 'purge_cache' ) );
		}
		return $value;
	}

	/**
	 * Purge the most common page caches, so visitors do not keep getting pages
	 * cached before maintenance was turned on (or the maintenance page after it
	 * was turned off).
	 */
	public function purge_cache() {
		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			sg_cachepress_purge_cache();
		}
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}
		do_action( 'litespeed_purge_all' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- LiteSpeed Cache hook.
		do_action( 'wpfc_clear_all_cache' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WP Fastest Cache hook.

		/**
		 * Fires after the maintenance settings change, to purge other caches.
		 */
		do_action( 'acwmmbp_purge_cache' );
	}

	public function add_settings_page() {
		add_options_page(
			__( 'Maintenance Mode', 'acw-maintenance-mode-by-page' ),
			__( 'Maintenance', 'acw-maintenance-mode-by-page' ),
			'manage_options',
			self::SETTINGS_SLUG,
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting( 'acwmmbp_group', self::OPTION_ENABLED, array(
			'type'              => 'boolean',
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
			'default'           => false,
		) );
		register_setting( 'acwmmbp_group', self::OPTION_PAGE_ID, array(
			'type'              => 'integer',
			'sanitize_callback' => array( $this, 'sanitize_page_id' ),
			'default'           => 0,
		) );
		register_setting( 'acwmmbp_group', self::OPTION_WIDTH, array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_width' ),
			'default'           => 'default',
		) );
		// No default here: get_bypass_roles() needs to tell "never saved" apart from "no roles".
		register_setting( 'acwmmbp_group', self::OPTION_ROLES, array(
			'type'              => 'array',
			'sanitize_callback' => array( $this, 'sanitize_roles' ),
		) );
		register_setting( 'acwmmbp_group', self::OPTION_ALLOWED, array(
			'type'              => 'array',
			'sanitize_callback' => array( $this, 'sanitize_allowed_pages' ),
			'default'           => array(),
		) );
	}

	public function sanitize_checkbox( $value ) {
		return ! empty( $value ) ? 1 : 0;
	}

	public function sanitize_page_id( $value ) {
		$page_id = absint( $value );
		if ( $page_id && ! $this->is_valid_page( $page_id ) ) {
			add_settings_error(
				self::OPTION_PAGE_ID,
				'acwmmbp_invalid_page',
				__( 'The selected page must be a published page without password and cannot be the posts page.', 'acw-maintenance-mode-by-page' )
			);
			return 0;
		}
		return $page_id;
	}

	public function sanitize_width( $value ) {
		return 'full' === $value ? 'full' : 'default';
	}

	public function sanitize_roles( $value ) {
		$roles = array_map( 'sanitize_key', (array) $value );
		return array_values( array_intersect( $roles, array_keys( wp_roles()->get_names() ) ) );
	}

	public function sanitize_allowed_pages( $value ) {
		return array_values( array_unique( array_filter( array_map( 'absint', (array) $value ) ) ) );
	}

	/**
	 * Warn administrators when maintenance is on but the selected page is not
	 * usable (deleted, unpublished...). The settings page shows its own notice.
	 */
	public function invalid_page_notice() {
		if ( ! $this->is_enabled() || $this->get_page_id() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'settings_page_' . self::SETTINGS_SLUG === $screen->id ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p>
				<?php esc_html_e( 'Maintenance mode is active but the selected page is not available: visitors see a generic maintenance message.', 'acw-maintenance-mode-by-page' ); ?>
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=' . self::SETTINGS_SLUG ) ); ?>"><?php esc_html_e( 'Choose a page', 'acw-maintenance-mode-by-page' ); ?></a>
			</p>
		</div>
		<?php
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$enabled        = $this->is_enabled();
		$page_id        = (int) get_option( self::OPTION_PAGE_ID );
		$width          = get_option( self::OPTION_WIDTH );
		$bypass_roles   = $this->get_bypass_roles();
		$allowed_pages  = array_map( 'absint', (array) get_option( self::OPTION_ALLOWED, array() ) );
		$posts_page_id  = (int) get_option( 'page_for_posts' );
		$login_page_ids = $this->get_login_page_ids();
		// The posts page cannot be used: on it is_page() is false.
		$pages = array_filter(
			get_pages(),
			function ( $page ) use ( $posts_page_id ) {
				return (int) $page->ID !== $posts_page_id;
			}
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Maintenance Mode', 'acw-maintenance-mode-by-page' ); ?></h1>

			<?php if ( $enabled && $this->get_page_id() ) : ?>
				<div class="notice notice-warning inline">
					<p><strong><?php esc_html_e( 'Maintenance mode is ACTIVE.', 'acw-maintenance-mode-by-page' ); ?></strong>
					<?php esc_html_e( 'Visitors and users without access only see the selected page.', 'acw-maintenance-mode-by-page' ); ?></p>
				</div>
			<?php elseif ( $enabled ) : ?>
				<div class="notice notice-error inline">
					<p><strong><?php esc_html_e( 'Maintenance mode is ACTIVE, but no valid page is selected.', 'acw-maintenance-mode-by-page' ); ?></strong>
					<?php esc_html_e( 'Visitors see a generic maintenance message. Choose a published page.', 'acw-maintenance-mode-by-page' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'acwmmbp_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable maintenance', 'acw-maintenance-mode-by-page' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( self::OPTION_ENABLED ); ?>" value="1" <?php checked( $enabled ); ?> />
								<?php esc_html_e( 'Show visitors only the selected page', 'acw-maintenance-mode-by-page' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( self::OPTION_PAGE_ID ); ?>"><?php esc_html_e( 'Page to display', 'acw-maintenance-mode-by-page' ); ?></label>
						</th>
						<td>
							<select name="<?php echo esc_attr( self::OPTION_PAGE_ID ); ?>" id="<?php echo esc_attr( self::OPTION_PAGE_ID ); ?>">
								<option value="0"><?php esc_html_e( '— Select a page —', 'acw-maintenance-mode-by-page' ); ?></option>
								<?php foreach ( $pages as $page ) : ?>
									<?php if ( '' === $page->post_password ) : ?>
										<option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( (int) $page->ID, $page_id ); ?>><?php echo esc_html( str_repeat( '— ', count( get_post_ancestors( $page ) ) ) . get_the_title( $page ) ); ?></option>
									<?php endif; ?>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Choose one of the published pages listed under “Pages” (the posts page and password-protected pages cannot be used).', 'acw-maintenance-mode-by-page' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Who can browse the site', 'acw-maintenance-mode-by-page' ); ?></th>
						<td>
							<fieldset>
								<?php foreach ( wp_roles()->get_names() as $role => $role_name ) : ?>
									<?php
									$role_object = get_role( $role );
									$is_admin    = $role_object && $role_object->has_cap( 'manage_options' );
									?>
									<label>
										<input type="checkbox" name="<?php echo esc_attr( self::OPTION_ROLES ); ?>[]" value="<?php echo esc_attr( $role ); ?>" <?php checked( $is_admin || in_array( $role, $bypass_roles, true ) ); ?> <?php disabled( $is_admin ); ?> />
										<?php echo esc_html( translate_user_role( $role_name ) ); ?>
									</label>
									<br />
								<?php endforeach; ?>
							</fieldset>
							<p class="description"><?php esc_html_e( 'Checked roles keep browsing the site and using the dashboard. Everyone else, including logged-out visitors, only sees the maintenance page. Administrators always have access.', 'acw-maintenance-mode-by-page' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( self::OPTION_ALLOWED ); ?>"><?php esc_html_e( 'Pages always reachable', 'acw-maintenance-mode-by-page' ); ?></label>
						</th>
						<td>
							<select multiple size="8" name="<?php echo esc_attr( self::OPTION_ALLOWED ); ?>[]" id="<?php echo esc_attr( self::OPTION_ALLOWED ); ?>">
								<?php foreach ( $pages as $page ) : ?>
									<option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( in_array( (int) $page->ID, $allowed_pages, true ) ); ?>><?php echo esc_html( get_the_title( $page ) ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Pages that stay reachable during maintenance, such as a front-end login, registration or account page. Hold Ctrl (Cmd on Mac) to select more than one.', 'acw-maintenance-mode-by-page' ); ?></p>
							<?php if ( $login_page_ids ) : ?>
								<p class="description">
									<?php
									printf(
										/* translators: %s: comma-separated list of page titles. */
										esc_html__( 'Detected automatically and always reachable: %s', 'acw-maintenance-mode-by-page' ),
										esc_html( implode( ', ', array_map( 'get_the_title', $login_page_ids ) ) )
									);
									?>
								</p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Content width', 'acw-maintenance-mode-by-page' ); ?></th>
						<td>
							<fieldset>
								<label>
									<input type="radio" name="<?php echo esc_attr( self::OPTION_WIDTH ); ?>" value="default" <?php checked( $width, 'default' ); ?> />
									<?php esc_html_e( 'Default width (inherited from the theme)', 'acw-maintenance-mode-by-page' ); ?>
								</label>
								<br />
								<label>
									<input type="radio" name="<?php echo esc_attr( self::OPTION_WIDTH ); ?>" value="full" <?php checked( $width, 'full' ); ?> />
									<?php esc_html_e( 'Full width', 'acw-maintenance-mode-by-page' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>
				</table>
				<p class="description">
					<?php esc_html_e( 'Saving purges the cache of SiteGround Speed Optimizer, WP Rocket, LiteSpeed Cache, W3 Total Cache, WP Super Cache and WP Fastest Cache. With other caching plugins, a CDN or a server cache, purge it manually after turning maintenance on or off.', 'acw-maintenance-mode-by-page' ); ?>
				</p>
				<p class="description">
					<?php esc_html_e( 'This is a maintenance mode, not a security barrier: the REST API and direct media file URLs stay reachable.', 'acw-maintenance-mode-by-page' ); ?>
				</p>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Notice in the admin bar when maintenance is active.
	 */
	public function admin_bar_notice( $wp_admin_bar ) {
		if ( ! $this->is_enabled() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$wp_admin_bar->add_node( array(
			'id'    => 'acw-mm-notice',
			'title' => __( '⚠ Maintenance active', 'acw-maintenance-mode-by-page' ),
			'href'  => admin_url( 'options-general.php?page=' . self::SETTINGS_SLUG ),
		) );
	}
}

new ACWMMBP_Maintenance_Mode();
