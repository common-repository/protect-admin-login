<?php
if ( defined( 'ABSPATH' ) && ! class_exists( 'protect_admin_login_option' ) ) {
	/**
	 * Base class to run in environment
	 *
	 * While plugin active, It loads to functions
	 *
	 * @package Protect Admin Login
	 * @since 3.0.0
	 */
	class protect_admin_login_option {
		private $wp_login_php;

		private function basename() {
			return plugin_basename( __FILE__ );
		}

		private function path() {
			return trailingslashit( __DIR__ );
		}
		/* Conditionally adds a trailing slash if the permalink structure has a trailing slash */
		private function use_trailing_slashes() {
			return '/' === substr( get_option( 'permalink_structure' ), -1, 1 );
		}

		private function user_trailingslashit( $string ) {
			return $this->use_trailing_slashes() ? trailingslashit( $string ) : untrailingslashit( $string );
		}
		/* Default environment of wp site */
		private function wp_template_loader() {
			global $myindex;

			$myindex = 'index.php';

			if ( ! defined( 'WP_USE_THEMES' ) ) {
				define( 'WP_USE_THEMES', true );
			}

			wp();

			if ( $_SERVER['REQUEST_URI'] === $this->user_trailingslashit( str_repeat( '-/', 10 ) ) ) {
				$_SERVER['REQUEST_URI'] = $this->user_trailingslashit( '/wp-login-php/' );
			}

			require_once ABSPATH . WPINC . '/template-loader.php';

			die;
		}

		private function protected_login_slug() {
			if (
				( $slug = get_option( 'protect_admin_login' ) ) || (
					is_multisite() &&
					is_plugin_active_for_network( $this->basename() ) &&
					( $slug = get_site_option( 'protect_admin_login', 'login' ) )
				) ||
				( $slug = 'login' )
			) {
				return $slug;
			}
		}

		public function protected_login_url( $scheme = null ) {
			if ( get_option( 'permalink_structure' ) ) {
				return $this->user_trailingslashit( home_url( '/', $scheme ) . $this->protected_login_slug() );
			} else {
				return home_url( '/', $scheme ) . '?' . $this->protected_login_slug();
			}
		}
			/**
			 * Hooks to load in environment
			 *
			 * This option allows to overwrite basic functions
			 *
			 * @package Protect Admin Login
			 * @since 3.0.0
			 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			add_filter( 'plugin_action_links_' . $this->basename(), array( $this, 'plugin_action_links' ) );
			add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 1 );
			add_action( 'wp_loaded', array( $this, 'wp_loaded' ) );
			add_filter( 'site_url', array( $this, 'site_url' ), 10, 4 );
			add_filter( 'wp_redirect', array( $this, 'wp_redirect' ), 10, 2 );
			/* it disable auto redirect to default */
			remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );
		}

		public function activate() {
			add_option( 'cus_redirect', '1' );
		}


		public function admin_init() {
			global $pagenow;
			/**
			 * Add option input box in permalink page
			 *
			 * This option allows to replace wp-admin url
			 *
			 * @package Protect Admin Login
			 * @since 3.0.0
			 */
			add_settings_section(
				'protect-admin-login-section',
				_x( 'Add New Login Url (It will replace with wp-admin)', 'Text string for settings page', 'protect-admin-login' ),
				array( $this, 'cus_section_desc' ),
				'permalink'
			);

			add_settings_field(
				'cus-page',
				'<label for="cus-page">' . __( 'New Login Url', 'protect-admin-login' ) . '</label>',
				array( $this, 'protect_admin_login_input' ),
				'permalink',
				'protect-admin-login-section'
			);

			if ( isset( $_POST['protect_admin_login'] ) && $pagenow === 'options-permalink.php' ) {
				if (
					( $protect_admin_login = sanitize_title_with_dashes( $_POST['protect_admin_login'] ) ) &&
					strpos( $protect_admin_login, 'wp-login' ) === false &&
					! in_array( $protect_admin_login, $this->forbidden_slugs() )
				) {
					update_option( 'protect_admin_login', $protect_admin_login );
				}
			}
		}

		/**
		 * Multi site and admin role
		 *
		 * @package Protect Admin Login
		 * @since 3.0.0
		 */
		public function cus_section_desc() {
			if ( is_multisite() && is_super_admin() && is_plugin_active_for_network( $this->basename() ) ) {
				echo '<p>' .
						sprintf(
							__( 'To set a networkwide default, go to %s.', 'protect-admin-login' ),
							'<a href="' . esc_url( network_admin_url( 'settings.php#cus-page-input' ) ) . '">' .
								__( 'Network Settings', 'protect-admin-login' ) .
							'</a>'
						) .
					'</p>';
			}
		}
		/**
		 * Protect and admin login input
		 *
		 * @package Protect Admin Login
		 * @since 3.0.0
		 */
		public function protect_admin_login_input() {
			if ( get_option( 'permalink_structure' ) ) {
				echo '<code>' . trailingslashit( home_url() ) . '</code> <input id="cus-page-input" type="text" name="protect_admin_login" value="' . $this->protected_login_slug() . '">' . ( $this->use_trailing_slashes() ? ' <code>/</code>' : '' );
			} else {
				echo '<code>' . trailingslashit( home_url() ) . '?</code> <input id="cus-page-input" type="text" name="protect_admin_login" value="' . $this->protected_login_slug() . '">';
			}
		}
			/**
			 * Show notification of new url
			 *
			 * It shows new url of wp-admin
			 *
			 * @package Protect Admin Login
			 * @since 3.0.0
			 */
		public function admin_notices() {
			global $pagenow;

			if ( ! is_network_admin() && $pagenow === 'options-permalink.php' && isset( $_GET['settings-updated'] ) ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( __( 'Your new login url is: %s. Save it! (If you forget new login url, deactivate plugin through file manager or cpanel.)', 'protect-admin-login' ), '<strong><a href="' . $this->protected_login_url() . '">' . $this->protected_login_url() . '</a></strong>' ) . '</p></div>';
			}
		}

		public function plugin_action_links( $links ) {

				array_unshift(
					$links,
					'<a href="' . esc_url( admin_url( 'options-permalink.php#cus-page-input' ) ) . '">' .
						__( 'Settings', 'protect-admin-login' ) .
					'</a>'
				);

			return $links;
		}
			/**
			 * It checks plugin enabled or not
			 *
			 * It throw error on plugin disable
			 *
			 * @package Protect Admin Login
			 * @since 3.0.0
			 */
		public function plugins_loaded() {

			global $pagenow;

			load_plugin_textdomain( 'protect-admin-login' );

			if (
				! is_multisite() && (
					strpos( $_SERVER['REQUEST_URI'], 'wp-signup' ) !== false ||
					strpos( $_SERVER['REQUEST_URI'], 'wp-activate' ) !== false
				)
			) {
				wp_die( __( 'Protect admin login is not enabled.', 'protect-admin-login' ), '', array( 'response' => 403 ) );
			}

			$request = parse_url( $_SERVER['REQUEST_URI'] );

			if ( (
					strpos( $_SERVER['REQUEST_URI'], 'wp-login.php' ) !== false ||
					untrailingslashit( $request['path'] ) === site_url( 'wp-login', 'relative' )
				) && ! is_admin()
			) {
				$this->wp_login_php     = true;
				$_SERVER['REQUEST_URI'] = $this->user_trailingslashit( '/' . str_repeat( '-/', 10 ) );
				$pagenow                = 'index.php';
			} elseif (
				untrailingslashit( $request['path'] ) === home_url( $this->protected_login_slug(), 'relative' ) || (
					! get_option( 'permalink_structure' ) &&
					isset( $_GET[ $this->protected_login_slug() ] ) &&
					empty( $_GET[ $this->protected_login_slug() ] )
			) ) {
				$pagenow = 'wp-login.php';
			}
		}
			/**
			 * It throws error on access wp-admin url
			 *
			 * It blocks wp-admin
			 *
			 * @package Protect Admin Login
			 * @since 3.0.0
			 */
		public function wp_loaded() {
			global $pagenow;

			if ( is_admin() && ! is_user_logged_in() && ! defined( 'DOING_AJAX' ) ) {
				wp_die( __( 'You must log in to access dashboard. This website using secure login url.', 'protect-admin-login' ), '', array( 'response' => 403 ) );
			}

			$request = parse_url( $_SERVER['REQUEST_URI'] );

			if (
				$pagenow === 'wp-login.php' &&
				$request['path'] !== $this->user_trailingslashit( $request['path'] ) &&
				get_option( 'permalink_structure' )
			) {
				wp_safe_redirect( $this->user_trailingslashit( $this->protected_login_url() ) . ( ! empty( $_SERVER['QUERY_STRING'] ) ? '?' . $_SERVER['QUERY_STRING'] : '' ) );
				die;
			} elseif ( $this->wp_login_php ) {
				if (
					( $referer = wp_get_referer() ) &&
					strpos( $referer, 'wp-activate.php' ) !== false &&
					( $referer = parse_url( $referer ) ) &&
					! empty( $referer['query'] )
				) {
					parse_str( $referer['query'], $referer );

					if (
						! empty( $referer['key'] ) &&
						( $result = wpmu_activate_signup( $referer['key'] ) ) &&
						is_wp_error( $result ) && (
							$result->get_error_code() === 'already_active' ||
							$result->get_error_code() === 'blog_taken'
					) ) {
						wp_safe_redirect( $this->protected_login_url() . ( ! empty( $_SERVER['QUERY_STRING'] ) ? '?' . $_SERVER['QUERY_STRING'] : '' ) );
						die;
					}
				}

				$this->wp_template_loader();
			} elseif ( $pagenow === 'wp-login.php' ) {
				global $error, $interim_login, $action, $user_login;

				@require_once ABSPATH . 'wp-login.php';

				die;
			}
		}

		public function site_url( $url, $path, $scheme, $blog_id ) {
			return $this->filter_wp_login_php( $url, $scheme );
		}


		public function wp_redirect( $location, $status ) {
			return $this->filter_wp_login_php( $location );
		}

		public function filter_wp_login_php( $url, $scheme = null ) {
			if ( strpos( $url, 'wp-login.php' ) !== false ) {
				if ( is_ssl() ) {
					$scheme = 'https';
				}

				$args = explode( '?', $url );

				if ( isset( $args[1] ) ) {
					parse_str( $args[1], $args );
					$url = add_query_arg( $args, $this->protected_login_url( $scheme ) );
				} else {
					$url = $this->protected_login_url( $scheme );
				}
			}

			return $url;
		}

		public function forbidden_slugs() {
			$wp = new WP();
			return array_merge( $wp->public_query_vars, $wp->private_query_vars );
		}
	}

	new protect_admin_login_option();
}
