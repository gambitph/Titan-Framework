<?php
/**
 * This script is not used within Titan Framework itself.
 *
 * This script is meant to be used with your Titan Framework-dependent theme or plugin,
 * so that your theme/plugin can verify whether the framework is installed.
 *
 * If Titan is not installed, then the script will display a notice with a link to
 * Titan. If Titan is installed but not activated, it will display the appropriate notice as well.
 *
 * To use this script, just copy it into your theme/plugin directory and do a
 * require_once( 'titan-framework-checker.php' );
 *
 * Changelog:
 * v1.7.4
 *		* Now integrates with TGM Plugin Activation - uses TGM instead of displaying
 *			our own admin notice
 */


if ( ! class_exists( 'TitanFrameworkChecker' ) ) {

	/**
	 * Titan Framework Checker
	 *
	 * @since 1.6
	 */
	class TitanFrameworkChecker {


		const SEARCH_REGEX = '/titan-framework.php/i';
		const TITAN_CLASS = 'TitanFramework';
		const PLUGIN_SLUG = 'titan-framework';
		
		
		/**
		 * Constructor, add hooks for checking for Titan Framework
		 *
		 * @since 1.6
		 */
		function __construct() {
			add_action( 'after_setup_theme', array( $this, 'performCheck' ), 2 );
			add_action( 'tgmpa_register', array( $this, 'tgmPluginActivationInclude' ) );
		}


		/**
		 * Checks the existence of Titan Framework and prompts the display of a notice
		 *
		 * @since 1.6
		 */
		public function performCheck() {
			
			// Only show notifications in the admin
			if ( ! is_admin() ) {
				return;
			}
			
			// If the plugin does not exist, throw admin notice to install.
			if ( ! $this->pluginExists() ) {
				add_filter( 'admin_notices', array( $this, 'displayAdminNotificationNotExist' ) );
				
			// If the class doesn't exist, the plugin is inactive. Throw admin notice to activate plugin.
			} else if ( ! class_exists( self::TITAN_CLASS ) ) {
				add_filter( 'admin_notices', array( $this, 'displayAdminNotificationInactive' ) );
			}
		}


		/**
		 * Displays a notification in the admin with a link to search
		 *
		 * @since 1.6
		 */
		public function displayAdminNotificationNotExist() {

			// Check for TGM use, if used, let TGM do the notice.
			// We do this here since performCheck() is too early
			if ( $this->tgmPluginActivationExists() ) {
				return;
			}
			
			echo "<div class='error'><p><strong>"
				. __( "Titan Framework needs to be installed.", "default" )
				. sprintf( " <a href='%s'>%s</a>",
					admin_url( "plugin-install.php?tab=search&type=term&s=titan+framework" ),
					__( "Click here to search for the plugin.", "default" ) )
				. "</strong></p></div>";
		}

		
		/**
		 * Displays a notification in the admin if the Titan Framework is found but not activated.
		 *
		 * @since 1.6
		 */
		public function displayAdminNotificationInactive() {

			// Check for TGM use, if used, let TGM do the notice.
			// We do this here since performCheck() is too early
			if ( $this->tgmPluginActivationExists() ) {
				return;
			}
			
			echo "<div class='error'><p><strong>"
				. __( "Titan Framework needs to be activated.", "default" )
				. sprintf( " <a href='%s'>%s</a>",
					admin_url( "plugins.php" ),
					__( "Click here to go to the plugins page and activate it.", "default" ) )
				. "</strong></p></div>";
		}
		
		
		/**
		 * Checks the existence of Titan Framework in the list of plugins, 
		 * uses the slug path of the plugin for checking.
		 *
		 * @return	boolean True if the TF exists
		 * @since	1.6
		 */
		public function pluginExists() {
			// Required function as it is only loaded in admin pages.
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			
			// Get all plugins, activated or not.
			$plugins = get_plugins();

			// Check plugin existence by checking if the name is registered as an array key. get_plugins collects all plugin path into arrays.
			foreach ( $plugins as $slug => $plugin ) {
				if ( preg_match( self::SEARCH_REGEX, $slug, $matches ) ) {
					return true;
				}
			}
			
			return false;
		}
		
		
		/**
		 * Checks whether TGM Plugin Activation is being used.
		 *
		 * @return	boolean True if the TF was used in TGM
		 * @since	1.7.4
		 * @see		http://tgmpluginactivation.com/
		 */
		public function tgmPluginActivationExists() {
			return class_exists( 'TGM_Plugin_Activation' ) 
				&& function_exists( 'tgmpa' );
		}
		
		
		/**
		 * Includes Titan Framework in TGM Plugin Activation if it's
		 * available.
		 *
		 * @return	void
		 * @since	1.7.4
		 * @see		http://tgmpluginactivation.com/
		 */
		public function tgmPluginActivationInclude() {
			if ( ! $this->tgmPluginActivationExists() ) {
				return;
			}
			
		    tgmpa( array(
		        array(
		            'name' => 'Titan Framework',
		            'slug' => self::PLUGIN_SLUG,
		            'required' => true,
		        ),
		    ) );
		}
		
	}

	new TitanFrameworkChecker();

}