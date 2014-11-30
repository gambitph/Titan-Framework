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
 */


if ( ! class_exists( 'TitanFrameworkChecker' ) ) {


	/**
	 * Titan Framework Checker
	 *
	 * @since 1.6
	 */
	class TitanFrameworkChecker {


		/**
		 * Constructor, add hooks for checking for Titan Framework
		 *
		 * @since 1.6
		 */
		function __construct() {
			add_action( 'after_setup_theme', array( $this, 'performCheck' ), 2 );
		}


		/**
		 * Checks the existence of Titan Framework and prompts the display of a notice
		 *
		 * @since 1.6
		 */
		public function performCheck() {
			// NOTE: if you use a directory name other than titan-framework, change this path!
			// If the plugin does not exist, and the class doesn't exist either, then there's no plugin installed. Throw admin notice to install.
			if ( !$this->is_plugin_exist ( 'titan-framework/titan-framework.php' ) && !class_exists( 'TitanFramework' ) ) {
				if ( is_admin() ) {
					add_filter( 'admin_notices', array( $this, 'displayAdminNotificationNotExist' ) );
				}				
			}
			// If the plugin does exist but the class doesn't, the plugin is inactive. Throw admin notice to activate plugin.
			elseif ( $this->is_plugin_exist ( 'titan-framework/titan-framework.php' ) && !class_exists( 'TitanFramework' ) ) {
				if ( is_admin() ) {
					add_filter( 'admin_notices', array( $this, 'displayAdminNotificationInactive' ) );
				}			
			}
			// If the plugin exists and the class exists as well, or if the titan framework is embedded, as the class will exist from the start.
			else {
				return;
			}
		}


		/**
		 * Displays a notification in the admin with a link to search
		 *
		 * @since 1.6
		 */
		public function displayAdminNotificationNotExist() {
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
            echo "<div class='error'><p><strong>"
                . __( "Titan Framework needs to be activated.", "default" )
                . sprintf( " <a href='%s'>%s</a>",
                    admin_url( "plugins.php" ),
                    __( "Click here to go to the plugins page and activate it.", "default" ) )
                . "</strong></p></div>";
        }
		
		
		/**
		 * Checks if the files for Titan Framework does exist in the path.
		 *
		 * @since 1.6
		 */
		public function is_plugin_exist($needle) {
			// Required function as it is only loaded in admin pages.
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			// Get all plugins, activated or not.
			$all_plugins = get_plugins();
			// Check plugin existence by checking if the name is registered as an array key. get_plugins collects all plugin path into arrays.
			if ( isset($all_plugins[$needle]) ) {
				return true;
			}
			else {
				return false;
			}
        }		
		
	}

	new TitanFrameworkChecker();

}