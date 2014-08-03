<?php
/**
 * This script is not used within Titan Framework itself.
 *
 * This script is meant to be used with your Titan Framework-dependent theme or plugin,
 * so that your theme/plugin can verify whether the framework is installed.
 *
 * If Titan is not installed, then the script will display a notice with a link to
 * Titan.
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
			if ( class_exists( 'TitanFramework' ) ) {
				return;
			}
			if ( is_admin() ) {
                add_filter( 'admin_notices', array( $this, 'displayAdminNotification' ) );
			}
		}


		/**
		 * Displays a notification in the admin with a link to search
		 *
		 * @since 1.6
		 */
		public function displayAdminNotification() {
            echo "<div class='error'><p><strong>"
                . __( "Titan Framework needs to be installed.", "default" )
                . sprintf( " <a href='%s'>%s</a>",
                    admin_url( "plugin-install.php?tab=search&type=term&s=titan+framework" ),
                    __( "Click here to search for the plugin.", "default" ) )
                . "</strong></p></div>";
        }

	}

	new TitanFrameworkChecker();

}