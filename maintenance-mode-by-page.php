<?php
/**
 * Plugin Name: Maintenance Mode by Page
 * Description: Put your site in maintenance mode showing logged-out visitors a single page of your choice, picked from your existing pages. Logged-in users keep browsing the whole site.
 * Author: A Cup of Web
 * Author URI: https://www.acupofweb.it/
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: maintenance-mode-by-page
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ACW_Maintenance_Mode {

	const OPTION_ENABLED = 'acw_mm_enabled';
	const OPTION_PAGE_ID = 'acw_mm_page_id';
	const OPTION_WIDTH   = 'acw_mm_width';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'template_redirect', array( $this, 'maybe_redirect' ) );
		add_filter( 'template_include', array( $this, 'maybe_use_blank_template' ) );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_notice' ), 100 );
	}

	/**
	 * True when maintenance is active and a valid page has been selected.
	 */
	private function is_active() {
		return get_option( self::OPTION_ENABLED ) && (int) get_option( self::OPTION_PAGE_ID ) > 0;
	}

	/**
	 * Redirect logged-out visitors to the maintenance page.
	 */
	public function maybe_redirect() {
		if ( ! $this->is_active() ) {
			return;
		}

		// Logged-in users can see the whole site.
		if ( is_user_logged_in() ) {
			return;
		}

		$page_id = (int) get_option( self::OPTION_PAGE_ID );

		// If we are already on the maintenance page, serve it with a 503 status
		// (temporarily unavailable) so search engines do not index it as
		// permanent content.
		if ( is_page( $page_id ) ) {
			$this->send_503_headers();
			return;
		}

		// Do not touch requests that are not the public front end of the site.
		if ( is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		// Allow the login/registration page through.
		if ( isset( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'] ) {
			return;
		}

		wp_safe_redirect( get_permalink( $page_id ), 302 );
		exit;
	}

	/**
	 * On the maintenance page (logged-out visitor) load a minimal template that
	 * shows only the content, without the theme header/footer/menu.
	 */
	public function maybe_use_blank_template( $template ) {
		if ( ! $this->is_active() || is_user_logged_in() ) {
			return $template;
		}
		if ( is_page( (int) get_option( self::OPTION_PAGE_ID ) ) ) {
			return plugin_dir_path( __FILE__ ) . 'template-maintenance.php';
		}
		return $template;
	}

	/**
	 * Set the HTTP 503 headers for the maintenance page.
	 */
	private function send_503_headers() {
		if ( headers_sent() ) {
			return;
		}
		$protocol = isset( $_SERVER['SERVER_PROTOCOL'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_PROTOCOL'] ) ) : 'HTTP/1.1';
		if ( ! in_array( $protocol, array( 'HTTP/1.1', 'HTTP/2', 'HTTP/2.0', 'HTTP/3' ), true ) ) {
			$protocol = 'HTTP/1.1';
		}
		header( $protocol . ' 503 Service Unavailable', true, 503 );
		header( 'Retry-After: 3600' );
		status_header( 503 );
		nocache_headers();
	}

	public function add_settings_page() {
		add_options_page(
			__( 'Maintenance Mode', 'maintenance-mode-by-page' ),
			__( 'Maintenance', 'maintenance-mode-by-page' ),
			'manage_options',
			'maintenance-mode-by-page',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting( 'acw_mm_group', self::OPTION_ENABLED, array(
			'type'              => 'boolean',
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
			'default'           => false,
		) );
		register_setting( 'acw_mm_group', self::OPTION_PAGE_ID, array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 0,
		) );
		register_setting( 'acw_mm_group', self::OPTION_WIDTH, array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_width' ),
			'default'           => 'default',
		) );
	}

	public function sanitize_checkbox( $value ) {
		return ! empty( $value ) ? 1 : 0;
	}

	public function sanitize_width( $value ) {
		return 'full' === $value ? 'full' : 'default';
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$enabled = get_option( self::OPTION_ENABLED );
		$page_id = (int) get_option( self::OPTION_PAGE_ID );
		$width   = get_option( self::OPTION_WIDTH );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Maintenance Mode', 'maintenance-mode-by-page' ); ?></h1>

			<?php if ( $this->is_active() ) : ?>
				<div class="notice notice-warning inline">
					<p><strong><?php esc_html_e( 'Maintenance mode is ACTIVE.', 'maintenance-mode-by-page' ); ?></strong>
					<?php esc_html_e( 'Logged-out visitors only see the selected page.', 'maintenance-mode-by-page' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'acw_mm_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable maintenance', 'maintenance-mode-by-page' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( self::OPTION_ENABLED ); ?>" value="1" <?php checked( $enabled, 1 ); ?> />
								<?php esc_html_e( 'Show visitors only the selected page', 'maintenance-mode-by-page' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( self::OPTION_PAGE_ID ); ?>"><?php esc_html_e( 'Page to display', 'maintenance-mode-by-page' ); ?></label>
						</th>
						<td>
							<?php
							wp_dropdown_pages( array(
								'name'              => esc_attr( self::OPTION_PAGE_ID ),
								'id'                => esc_attr( self::OPTION_PAGE_ID ),
								'selected'          => absint( $page_id ),
								'show_option_none'  => esc_html__( '— Select a page —', 'maintenance-mode-by-page' ),
								'option_none_value' => 0,
							) );
							?>
							<p class="description"><?php esc_html_e( 'Choose one of the pages already listed under “Pages”.', 'maintenance-mode-by-page' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Content width', 'maintenance-mode-by-page' ); ?></th>
						<td>
							<fieldset>
								<label>
									<input type="radio" name="<?php echo esc_attr( self::OPTION_WIDTH ); ?>" value="default" <?php checked( $width, 'default' ); ?> />
									<?php esc_html_e( 'Default width (inherited from the theme)', 'maintenance-mode-by-page' ); ?>
								</label>
								<br />
								<label>
									<input type="radio" name="<?php echo esc_attr( self::OPTION_WIDTH ); ?>" value="full" <?php checked( $width, 'full' ); ?> />
									<?php esc_html_e( 'Full width', 'maintenance-mode-by-page' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Notice in the admin bar when maintenance is active.
	 */
	public function admin_bar_notice( $wp_admin_bar ) {
		if ( ! $this->is_active() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$wp_admin_bar->add_node( array(
			'id'    => 'acw-mm-notice',
			'title' => __( '⚠ Maintenance active', 'maintenance-mode-by-page' ),
			'href'  => admin_url( 'options-general.php?page=maintenance-mode-by-page' ),
		) );
	}
}

new ACW_Maintenance_Mode();
