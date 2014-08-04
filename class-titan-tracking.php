<?php
/**
 * Titan Framework Tracker Class
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Titan Framework Tracker Class
 * In charge of the opt-in procedure and performing the actual data sending for tracking Titan
 * intallation details.
 *
 * @author Benjamin Intal
 **/
class TitanFrameworkTracker {

	const REMOTE_URL = 'http://www.titanframework.net/wp-admin/admin-ajax.php'; // TODO: This isn't live yet, although will not error out
	const TRACKER_INTERVAL = 10; // temporarily 10 secs for debugging FIXME: WEEK_IN_SECONDS;
	const REMOTE_ACTION = 'tracker';
	const OPT_AJAX_ACTION = 'tf_tracker_opted';
	const NONCE_NAME = 'tracker_nonce';

	// Internal variables
	private $frameworkInstance;
	private $optInOption;
	private $transientName;


	/**
	 * Class constructor
	 *
	 * @param	TitanFramework $frameworkInstance an instance of the framework object
	 * @return	void
	 * @since	1.6
	 */
	function __construct( $frameworkInstance ) {
		$this->frameworkInstance = $frameworkInstance;
		$this->optInOption = $this->frameworkInstance->optionNamespace . '_tf_tracker';
		$this->transientName = $this->optInOption . '_transient';

		add_action( 'admin_notices', array( $this, 'includeOptInScript' ) );
		add_action( 'wp_ajax_' . self::OPT_AJAX_ACTION, array( $this, 'ajaxTrackerOptedHandler' ) );
		add_action( 'admin_footer', array( $this, 'performTracking' ) );
		add_action( 'wp_footer', array( $this, 'performTracking' ) );
	}


	/**
	 * Adds a notice in the admin for tracking opt-in
	 *
	 * @return	void
	 * @since	1.6
	 */
	public function includeOptInScript() {
		// Check our settings
		if ( ! $this->frameworkInstance->settings['tracking'] ) {
			delete_option( $this->optInOption );
			delete_transient( $this->transientName, $this->transientName );
			return;
		}

		// Check if opted in/out before, quit
		if ( false !== get_option( $this->optInOption ) ) {
			return;
		}

		// Check if first time, ask to opt-in
		echo '<div class="updated" style="border-left-color: #3498db">
				<p>
					' . __( 'Help us make Titan Framework better by enabling your site to periodically send us tracking data. This is so we can know where and how Titan is being used.', TF_I18NDOMAIN ) . '
					<button name="opt" value="1" class="' . $this->optInOption . ' button button-primary">' . __( 'Help us and track', TF_I18NDOMAIN ) . '</button>
					<button name="opt" value="0" class="' . $this->optInOption . ' button button-default">' . __( "Don't track", TF_I18NDOMAIN ) . '</button>
					<script>
					jQuery(document).ready(function($) {
						$(".' . $this->optInOption . '").click(function() {
							var data = {
								"' . self::NONCE_NAME . '": "' . wp_create_nonce( __CLASS__ ) . '",
								"action": "' . self::OPT_AJAX_ACTION . '",
								"opt": $(this).val()
							};

							var $this = $(this);
							$.post(ajaxurl, data, function(response) {
								$this.parents(".updated:eq(0)").fadeOut();
							});

							$(".' . $this->optInOption . '").attr("disabled", "disabled");
						});
					});
					</script>
				</p>
			</div>';
	}


	/**
	 * Ajax handler for the opt-in question from the admin notice
	 *
	 * @return	void
	 * @since	1.6
	 */
	public function ajaxTrackerOptedHandler() {
		check_ajax_referer( __CLASS__, self::NONCE_NAME );

		if ( $_POST['opt'] == '1' ) {
			update_option( $this->optInOption, '1' );

			// Send out tracking stuff immediately during ajax
			$this->performTracking();
		} else {
			update_option( $this->optInOption, '0' );
		}

		die();
	}


	/**
	 * Performs the sending out of data for the tracking, this uses the transient API
	 * to perform data sending only every week.
	 *
	 * @return	void
	 * @since	1.6
	 */
	public function performTracking() {
		// Only do this when settings permit and the user opted-in
		if ( ! $this->frameworkInstance->settings['tracking'] ) {
			return;
		}
		if ( get_option( $this->optInOption ) !== '1' ) {
			return;
		}

		// Send out our tracking data if it's time already
		if ( false === get_transient( $this->transientName ) ) {

			$response = wp_remote_post(
				self::REMOTE_URL,
				array(
					'method' => 'POST',
					'timeout' => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking' => true,
					'headers' => array(),
					'body' => $this->formDataToSend(),
					'cookies' => array(),
			    )
			);

			// Periodically repeat
			set_transient( $this->transientName, $this->transientName, self::TRACKER_INTERVAL );
		}
	}


	/**
	 * Gathers all the data that we can get (not sensitive) to send out as tracker
	 * information.
	 *
	 * @return	array WP installation data
	 * @since	1.6
	 */
	private function formDataToSend() {
		$data = array();

		// Action of the receiving WP host
		$data['action'] = self::REMOTE_ACTION;

		// WordPress installation details
		$data['wp'] = array(
			'name' => get_bloginfo( 'name' ),
			'home_url' => home_url(),
			'description' => get_bloginfo( 'description' ),
			'version' => get_bloginfo( 'version' ),
			'text_direction' => get_bloginfo( 'text_direction' ),
			'language' => get_bloginfo( 'language' ),
		);

		// Titan Framework details
		$data['titan'] = array();
		if ( defined( 'TF_VERSION' ) ) {
			$data['titan']['version'] = TF_VERSION;
		}
		// Get option & container stats
		if ( ! empty( $this->frameworkInstance->optionsUsed ) ) {
			$data['titan']['num_options'] = count( $this->frameworkInstance->optionsUsed );
			$data['titan']['option_count'] = array();
			$data['titan']['container_count'] = array();
			$data['titan']['container_option_count'] = array();
			foreach ( $this->frameworkInstance->optionsUsed as $option ) {
				if ( ! empty( $option->settings['type'] ) ) {
					if ( empty( $data['titan']['option_count'][ $option->settings['type'] ] ) ) {
						$data['titan']['option_count'][ $option->settings['type'] ] = 0;
					}
					$data['titan']['option_count'][ $option->settings['type'] ]++;
				}

				if ( empty( $data['titan']['container_count'][ get_class( $option->owner ) ] ) ) {
					$data['titan']['container_count'][ get_class( $option->owner ) ] = 0;
				}
				$data['titan']['container_count'][ get_class( $option->owner ) ]++;

				if ( empty( $data['titan']['container_option_count'][ get_class( $option->owner ) ] ) ) {
					$data['titan']['container_option_count'][ get_class( $option->owner ) ] = array();
				}
				$data['titan']['container_option_count'][ get_class( $option->owner ) ][] = count( $option->owner->options );
			}
		}
		// Average the number of options per container
		if ( ! empty( $data['titan']['container_option_count'] ) ) {
			foreach ( $data['titan']['container_option_count'] as $key => $countArr ) {
				$runningTotal = 0;
				foreach ( $countArr as $count ) {
					$runningTotal += $count;
				}
				$data['titan']['container_option_count'][ $key ] = $runningTotal / count( $countArr );
			}
		}

		// Current theme details
		$theme = wp_get_theme();
		$data['theme'] = array(
			'name' => $theme->get( 'Name' ),
			'version' => $theme->get( 'Version' ),
			'themeuri' => $theme->get( 'ThemeURI' ),
			'author' => $theme->get( 'Author' ),
			'authoruri' => $theme->get( 'AuthorURI' ),
		);

		// Plugin details
		$data['plugins'] = array();
		if ( $plugins = get_plugins() ) {
			foreach ( $plugins as $key => $pluginData ) {
				if ( is_plugin_active( $key ) ) {
					$data['plugins'][ $key ] = $pluginData;
				}
			}
		}

		// PHP details
		$data['php'] = array(
			'phpversion' => phpversion(),
		);

		// Super basic security remote check
		$data['hash'] = md5( serialize( $data ) );

		return $data;
	}

}