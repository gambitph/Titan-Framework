<?php
/**
 * This script is not used within Titan Framework itself.
 *
 * This script is meant to be used when embedding Titan Framework into your
 * theme or plugin.
 *
 * To embed Titan Framework into your project, copy the whole Titan Framework folder
 * into your project, then in your functions.php or main plugin script, do a
 * require_once( 'Titan-Framework/titan-framework-embedder.php' );
 *
 * When done, your project will use the embedded copy of Titan Framework. When the plugin
 * version is activated, that one will be used instead.
 *
 * For more details on embedding, read our docs:
 * http://www.titanframework.net/embedding-titan-framework-in-your-project/
 */


if ( ! class_exists( 'TitanFrameworkEmbedder' ) ) {


	/**
	 * Titan Framework Embedder
	 *
	 * @since 1.6
	 */
	class TitanFrameworkEmbedder {


		/**
		 * Constructor, add hooks for embedding for Titan Framework
		 *
		 * @since 1.6
		 */
		function __construct() {
			// Don't do anything when we're activating a plugin to prevent errors
			// on redeclaring Titan classes
			if ( is_admin() ) {
				if ( ! empty( $_GET['action'] ) && ! empty( $_GET['plugin'] ) ) {
				    if ( $_GET['action'] == 'activate' ) {
				        return;
				    }
				}
			}
			add_action( 'after_setup_theme', array( $this, 'perform_check' ), 1 );
		}


		/**
		 * Uses Titan Framework
		 *
		 * @since 1.6
		 */
		public function perform_check() {
			if ( class_exists( 'TitanFramework' ) ) {
				return;
			}
			require_once( 'titan-framework.php' );
		}
	}

	new TitanFrameworkEmbedder();
}
