<?php
/**
 * EDD License Activation.
 *
 * This class is meant to handle Easy Digital Downloads licenses.
 * When a license is entered, it's checked with the server through
 * the EDD API. If the license is valid it is activated and
 * the activation result is saved as a transient.
 *
 * As the licensed can be deactivated directly from the server,
 * a regular check needs to be done on the license in order to make sure
 * that the status is up to date.
 *
 * The required option parameters for the activator to work are:
 *
 * - (string) $server     URL of the shop where the license was generated
 * - (string) $item_name  The name of the item as set in the shop
 *
 * @author Julien Liabeuf <julien@liabeuf.fr>
 * @link   http://julienliabeuf.com
 */

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}
if ( class_exists( 'TitanFrameworkOption' ) ) {

	class TitanFrameworkOptionEddLicense extends TitanFrameworkOption {

		public $defaultSecondarySettings = array(
			'placeholder'                   => '',
			'is_password'                   => false,
			'server'                        => false,
			// Whether or not a license number is mandatory for checking for new version. Without license number it still won't be possible to update though. Users will just see that there is a new version available.
			'update_check_license_required' => false,
			'wp_override'                   => false,
		);

		/**
		 * Constructor
		 *
		 * @since    1.7.1
		 *
		 * @param array  $settings Option settings
		 * @param string $owner
		 */
		function __construct( $settings, $owner ) {
			parent::__construct( $settings, $owner );

			add_action( 'admin_init',                                      array( $this, 'checkUpdates' ), 10, 0 );
			add_action( 'tf_create_option_' . $this->getOptionNamespace(), array( $this, 'activateLicense' ) );
		}

		/**
		 * Activated the given EDD license.
		 *
		 * @return	void
		 * @since	1.7.1
		 */
		public function activateLicense( $option ) {
			if ( $this->settings['id'] != $option->settings['id'] ) {
				return;
			}

			/* Get the license */
			$license = esc_attr( $this->getValue() );

			/* License ID */
			$key = substr( md5( $license ), 0, 10 );

			/* If the license is set we can handle activation. */
			if ( strlen( $license ) > 0 ) {

				/* First of all we check if the user requested a manual activation */
				if ( isset( $_GET['eddactivate'] ) && '1' == $_GET['eddactivate'] ) {

					global $pagenow;

					if ( isset( $_GET ) ) {
						$get = (array) $_GET;
					}

					if ( isset( $get['eddactivate'] ) ) {
						unset( $get['eddactivate'] );
					}

					$this->check( $license, 'activate_license' );

					/* Redirect to the settings page without the eddactivate parameter (otherwise it's used in all tabs links) */
					wp_redirect( wp_sanitize_redirect( add_query_arg( $get, admin_url( $pagenow ) ) ) );
				}

				/* First activation of the license. */
				if ( false == get_transient( "tf_edd_license_try_$key" ) ) {
					$this->check( $license, 'activate_license' );
				}
			}

		}

		/**
		 * Display for options and meta
		 */
		public function display() {

			/* Get the license */
			$license = esc_attr( $this->getValue() );

			/* License ID */
			$key = substr( md5( $license ), 0, 10 );

			$this->echoOptionHeader();

			printf( '<input class="regular-text" name="%s" placeholder="%s" id="%s" type="%s" value="%s" />',
				$this->getID(),
				$this->settings['placeholder'],
				$this->getID(),
				$this->settings['is_password'] ? 'password' : 'text',
			$license );

			/* If the license is set, we display its status and check it if necessary. */
			if ( strlen( $license ) > 0 ) {

				/* Get the license activation status */
				$status = get_transient( "tf_edd_license_status_$key" );

				/* If no transient is found or it is expired to check the license again. */
				if ( false == $status ) {
					$status = $this->check( $license );
				}

				switch ( $status ) {

					case 'valid':
						?><p class="description"><?php esc_html_e( 'Your license is valid and active.', TF_I18NDOMAIN ); ?></p><?php
					break;

					case 'invalid':
						?><p class="description"><?php esc_html_e( 'Your license is invalid.', TF_I18NDOMAIN ); ?></p><?php
					break;

					case 'inactive':

						global $pagenow;

						if ( isset( $_GET ) ) {
							$get = (array) $_GET;
						}

						$get['eddactivate'] = true;
						$url                = esc_url( add_query_arg( $get, admin_url( $pagenow ) ) );
						?>
						<a href="<?php echo $url; ?>" class="button-secondary"><?php esc_html_e( 'Activate', TF_I18NDOMAIN ); ?></a>
						<p class="description"><?php esc_html_e( 'Your license is valid but inactive. Click the button above to activate it.', TF_I18NDOMAIN ); ?></p><?php

					break;

					case 'no_response':
						?><p class="description"><?php esc_html_e( 'The remote server did not return a valid response. You can retry by hitting the &laquo;Save&raquo; button again.', TF_I18NDOMAIN ); ?></p><?php
					break;

				}
			} else {
				?><p class="description"><?php esc_html_e( 'Entering your license key is mandatory to get the product updates.', TF_I18NDOMAIN ); ?></p><?php
			}

			$this->echoOptionFooter();

		}

		/*
		 * Display for theme customizer
		 */
		public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
			/**
			 * @var WP_Customize_Manager $wp_customize
			 */
			$wp_customize->add_control( new TitanFrameworkCustomizeControl( $wp_customize, $this->getID(), array(
				'label' => $this->settings['name'],
				'section' => $section->settings['id'],
				'settings' => $this->getID(),
				'description' => $this->settings['desc'],
				'priority' => $priority,
			) ) );
		}

		/**
		 * Check license status.
		 *
		 * The function makes an API call to the remote server and
		 * requests the license status.
		 *
		 * This function check (only) the license status or activate it
		 * depending on the $action parameter. The license status is then
		 * stored as a transient, and if an activation was made, an activation
		 * transient is also set in order to avoid activating when
		 * checking only is required.
		 *
		 * @param  string $license License key
		 * @param  string $action  Action to take (check_license or activate_license)
		 * @return string          Current license status
		 */
		public function check( $license = '', $action = 'check_license' ) {

			if ( empty( $license ) ) {
				return false;
			}

			/* Sanitize the key. */
			$license = trim( sanitize_key( $license ) );

			/* Set the transients lifetime. */
			$status_lifetime     = apply_filters( 'tf_edd_license_status_lifetime', 48 * 60 * 60 );         // Default is set to two days
			$activation_lifetime = apply_filters( 'tf_edd_license_activation_lifetime', 365 * 24 * 60 * 60 ); // Default is set to one year

			/* Prepare the data to send with the API request. */
			$api_params = array(
				'edd_action' => $action,
				'license'    => $license,
				'url'        => home_url(),
			);

			/**
			 * Set the item ID or name. ID has the highest priority
			 *
			 * @since 1.7.4
			 */
			if ( isset( $this->settings['item_id'] ) ) {
				$api_params['item_id'] = urlencode( $this->settings['item_id'] );
			} elseif ( isset( $this->settings['item_name'] ) ) {
				$api_params['item_name'] = urlencode( $this->settings['item_name'] );
			}

			if ( ! isset( $api_params['item_id'] ) && ! isset( $api_params['item_name'] ) ) {
				return false;
			}

			/* Call the API. */
			$response = wp_remote_get( add_query_arg( $api_params, $this->settings['server'] ), array( 'timeout' => 15, 'sslverify' => false ) );

			/* Check for request error. */
			if ( is_wp_error( $response ) ) {
				return false;
			}

			/* Decode license data. */
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			/* If the remote server didn't return a valid response we just return an error and don't set any transients so that activation will be tried again next time the option is saved */
			if ( ! is_object( $license_data ) || empty( $license_data ) || ! isset( $license_data->license ) ) {
				return 'no_response';
			}

			/* License ID */
			$key = substr( md5( $license ), 0, 10 );

			if ( 'activate_license' == $action ) {

				/**
				 * If the license is invalid we can set all transients right away.
				 * The user will need to modify its license anyways so there is no risk
				 * of preventing further activation attempts.
				 */
				if ( 'invalid' == $license_data->license ) {
					set_transient( "tf_edd_license_status_$key", 'invalid', $status_lifetime );
					set_transient( "tf_edd_license_try_$key", true, $activation_lifetime );
					return 'invalid';
				}

				/**
				 * Because sometimes EDD returns a "success" status even though the license hasn't been activated,
				 * we need to check the license status after activating it. Only then we can safely set the
				 * transients and avoid further activation attempts issues.
				 *
				 * @link https://github.com/gambitph/Titan-Framework/issues/203
				 */
				$status = $this->check( $license );

				if ( in_array( $status, array( 'valid', 'inactive' ) ) ) {

					/* We set the "try" transient only as the status will be set by the second instance of this method when we check the license status */
					set_transient( "tf_edd_license_try_$key", true, $activation_lifetime );

				}
			} else {

				/* Set the status transient. */
				set_transient( "tf_edd_license_status_$key", $license_data->license, $status_lifetime );

			}

			/* Return the license status. */
			return $license_data->license;

		}

		/**
		 * Check for plugin updates.
		 *
		 * This method is called throughout the entire WordPress admin
		 * and uses the EDD plugin updater class to check for new updates.
		 *
		 * @since  1.7.2
		 * @return boolean True if an update check was done, false otherwise
		 */
		public function checkUpdates() {

			/* Check if we have all the required parameters. */
			if ( ! isset( $this->settings['server'] ) || ! isset( $this->settings['name'] ) || ! isset( $this->settings['file'] ) ) {
				return false;
			}

			/* Make sure the file actually exists. */
			if ( ! file_exists( $this->settings['file'] ) ) {
				return false;
			}

			/* Retrieve license key */
			$license_key = trim( esc_attr( $this->getValue() ) );

			// Abort if license key is empty and one is required for update check
			if ( empty( $license_key ) && true === $this->settings['update_check_license_required'] ) {
				return false;
			}

			/* Check what type of item the file is */
			$item_is = $this->item_is( $this->settings['file'] );

			/* Item name */
			$item_name = isset( $this->settings['item_name'] ) ? sanitize_text_field( $this->settings['item_name'] ) : false;
			$item_id   = isset( $this->settings['item_id'] ) ? (int) $this->settings['item_id'] : false;

			/* Prepare updater arguments */
			$args = array(
				'license' => $license_key, // Item license key
			);

			/* Add license ID or name for identification */
			if ( false != $item_id ) {
				$args['item_id'] = $item_id;
			} elseif ( false != $item_name ) {
				$args['item_name'] = $item_name;
			}

			/* Load the plugin updater class and add required parameters. */
			if ( 'plugin' == $item_is ) {

				if ( ! class_exists( 'TITAN_EDD_SL_Plugin_Updater' ) ) {
					include( TF_PATH . 'inc/edd-licensing/EDD_SL_Plugin_Updater.php' );
				}

				$plugin              = get_plugin_data( $this->settings['file'] );
				$args['version']     = $plugin['Version'];
				$args['author']      = $plugin['Author'];
				$args['wp_override'] = $this->settings['wp_override'];

			} /* Load the theme updater class and add required parameters. */
			elseif ( in_array( $item_is, array( 'theme-parent', 'theme-child' ) ) ) {

				if ( ! class_exists( 'TITAN_EDD_Theme_Updater' ) ) {
					include( TF_PATH . 'inc/edd-licensing/theme-updater-class.php' );
				}

				add_filter( 'http_request_args', array( $this, 'disable_wporg_request' ), 5, 2 );

				$theme_dir = explode( '/', substr( str_replace( get_theme_root(), '', $this->settings['file'] ), 1 ) );
				$theme     = wp_get_theme( $theme_dir[0], get_theme_root() );

				/* Make sure the theme exists. */
				if ( ! $theme->exists() ) {
					return false;
				}

				$args['version']        = $theme->get( 'Version' );
				$args['author']         = $theme->get( 'Author' );
				$args['remote_api_url'] = esc_url( $this->settings['server'] );

				/* Set the update messages. */
				$strings = array(
					'theme-license'             => __( 'Theme License', 'edd-theme-updater' ),
					'enter-key'                 => __( 'Enter your theme license key.', 'edd-theme-updater' ),
					'license-key'               => __( 'License Key', 'edd-theme-updater' ),
					'license-action'            => __( 'License Action', 'edd-theme-updater' ),
					'deactivate-license'        => __( 'Deactivate License', 'edd-theme-updater' ),
					'activate-license'          => __( 'Activate License', 'edd-theme-updater' ),
					'status-unknown'            => __( 'License status is unknown.', 'edd-theme-updater' ),
					'renew'                     => __( 'Renew?', 'edd-theme-updater' ),
					'unlimited'                 => __( 'unlimited', 'edd-theme-updater' ),
					'license-key-is-active'     => __( 'License key is active.', 'edd-theme-updater' ),
					'expires%s'                 => __( 'Expires %s.', 'edd-theme-updater' ),
					'%1$s/%2$-sites'            => __( 'You have %1$s / %2$s sites activated.', 'edd-theme-updater' ),
					'license-key-expired-%s'    => __( 'License key expired %s.', 'edd-theme-updater' ),
					'license-key-expired'       => __( 'License key has expired.', 'edd-theme-updater' ),
					'license-keys-do-not-match' => __( 'License keys do not match.', 'edd-theme-updater' ),
					'license-is-inactive'       => __( 'License is inactive.', 'edd-theme-updater' ),
					'license-key-is-disabled'   => __( 'License key is disabled.', 'edd-theme-updater' ),
					'site-is-inactive'          => __( 'Site is inactive.', 'edd-theme-updater' ),
					'license-status-unknown'    => __( 'License status is unknown.', 'edd-theme-updater' ),
					'update-notice'             => __( "Updating this theme will lose any customizations you have made. 'Cancel' to stop, 'OK' to update.", 'edd-theme-updater' ),
					'update-available'          => __( '<strong>%1$s %2$s</strong> is available. <a href="%3$s" class="thickbox" title="%4s">Check out what\'s new</a> or <a href="%5$s"%6$s>update now</a>.', 'edd-theme-updater' ),
				);

			} /* What the hell is this?? */
			else {
				return false;
			}

			/* Update server URL */
			$endpoint = esc_url( $this->settings['server'] );

			/* Setup updater */
			if ( 'plugin' == $item_is ) {
				$edd_updater = new TITAN_EDD_SL_Plugin_Updater( $endpoint, $this->settings['file'], $args );
			} else {
				new TITAN_EDD_Theme_Updater( $args, $strings );
			}

			return true;

		}

		/**
		 * Check if $file is a theme or a plugin.
		 *
		 * @since  1.7.2
		 * @param  string $file Path to the file to check
		 * @return string       What type of file this is (parent theme, child theme or plugin)
		 */
		public function item_is( $file ) {

			$parentTheme = trailingslashit( get_template_directory() );
			$childTheme  = trailingslashit( get_stylesheet_directory() );

			/**
			 * Windows sometimes mixes up forward and back slashes, ensure forward slash for
			 * correct URL output.
			 *
			 * @see  TitanFramework::getURL()
			 */
			$parentTheme = str_replace( '\\', '/', $parentTheme );
			$childTheme  = str_replace( '\\', '/', $childTheme );
			$file        = str_replace( '\\', '/', $file );

			/* Make sure the file exists. */
			if ( ! file_exists( $file ) ) {
				return false;
			}

			/* The $file is in a parent theme */
			if ( stripos( $file, $parentTheme ) != false ) {
				return 'theme-parent';
			} /* The $file is in a child theme */
			else if ( stripos( $file, $childTheme ) != false ) {
				return 'theme-child';
			} /* The $file is in a plugin */
			else {
				return 'plugin';
			}

		}

		/**
		 * Disable requests to wp.org repository for this theme.
		 *
		 * @since 1.7.2
		 */
		function disable_wporg_request( $r, $url ) {

			// If it's not a theme update request, bail.
			if ( 0 != strpos( $url, 'https://api.wordpress.org/themes/update-check/1.1/' ) ) {
	 			return $r;
	 		}

	 		// Decode the JSON response
	 		$themes = json_decode( $r['body']['themes'] );

	 		// Remove the active parent and child themes from the check
	 		$parent = get_option( 'template' );
	 		$child = get_option( 'stylesheet' );
	 		unset( $themes->themes->$parent );
	 		unset( $themes->themes->$child );

	 		// Encode the updated JSON response
	 		$r['body']['themes'] = json_encode( $themes );

	 		return $r;
		}
	}
}

