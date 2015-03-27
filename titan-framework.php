<?php
/*
Plugin Name: Titan Framework
Plugin URI: http://www.titanframework.net/
Description: Titan Framework allows theme and plugin developers to create a admin pages, options, meta boxes, and theme customizer options with just a few simple lines of code.
Author: Benjamin Intal, Gambit
Version: 1.7.5
Author URI: http://gambit.ph
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Used for tracking the version used
defined( 'TF_VERSION' ) or define( 'TF_VERSION', '1.7.5' );
// Used for text domains
defined( 'TF_I18NDOMAIN' ) or define( 'TF_I18NDOMAIN', 'titan-framework' );
// Used for general naming, e.g. nonces
defined( 'TF' ) or define( 'TF', 'titan-framework' );
// Used for general naming
defined( 'TF_NAME' ) or define( 'TF_NAME', 'Titan Framework' );
// Used for file includes
defined( 'TF_PATH' ) or define( 'TF_PATH', trailingslashit( dirname( __FILE__ ) ) );

require_once( TF_PATH . 'class-admin-notification.php' );
require_once( TF_PATH . 'class-admin-panel.php' );
require_once( TF_PATH . 'class-admin-tab.php' );
require_once( TF_PATH . 'class-meta-box.php' );
require_once( TF_PATH . 'class-option.php' );
require_once( TF_PATH . 'class-option-checkbox.php' );
require_once( TF_PATH . 'class-option-code.php' );
require_once( TF_PATH . 'class-option-color.php' );
require_once( TF_PATH . 'class-option-edd-license.php' );
require_once( TF_PATH . 'class-option-date.php' );
require_once( TF_PATH . 'class-option-enable.php' );
require_once( TF_PATH . 'class-option-editor.php' );
require_once( TF_PATH . 'class-option-font.php' );
require_once( TF_PATH . 'class-option-heading.php' );
require_once( TF_PATH . 'class-option-multicheck.php' );
require_once( TF_PATH . 'class-option-multicheck-categories.php' );
require_once( TF_PATH . 'class-option-multicheck-pages.php' );
require_once( TF_PATH . 'class-option-multicheck-posts.php' );
require_once( TF_PATH . 'class-option-note.php' );
require_once( TF_PATH . 'class-option-number.php' );
require_once( TF_PATH . 'class-option-radio.php' );
require_once( TF_PATH . 'class-option-radio-image.php' );
require_once( TF_PATH . 'class-option-radio-palette.php' );
require_once( TF_PATH . 'class-option-save.php' );
require_once( TF_PATH . 'class-option-select-categories.php' );
require_once( TF_PATH . 'class-option-select-pages.php' );
require_once( TF_PATH . 'class-option-select-posts.php' );
require_once( TF_PATH . 'class-option-select.php' );
require_once( TF_PATH . 'class-option-sortable.php' );
require_once( TF_PATH . 'class-option-text.php' );
require_once( TF_PATH . 'class-option-textarea.php' );
require_once( TF_PATH . 'class-option-upload.php' );
require_once( TF_PATH . 'class-theme-customizer-section.php' );
require_once( TF_PATH . 'class-titan-css.php' );
require_once( TF_PATH . 'class-titan-framework.php' );
require_once( TF_PATH . 'class-titan-tracking.php' );
require_once( TF_PATH . 'class-wp-customize-control.php' );
require_once( TF_PATH . 'functions-googlefonts.php' );
require_once( TF_PATH . 'functions-utils.php' );


/**
 * Titan Framework Plugin Class
 *
 * @since 1.0
 */
class TitanFrameworkPlugin {


	/**
	 * Constructor, add hooks
	 *
	 * @since	1.0
	 */
	function __construct() {
		add_action( 'plugins_loaded', array( $this, 'loadTextDomain' ) );
		add_action( 'plugins_loaded', array( $this, 'forceLoadFirst' ), 10, 1 );
		add_filter( 'plugin_row_meta', array( $this, 'pluginLinks' ), 10, 2 );

		// Initialize options, but do not really create them yet
		add_action( 'after_setup_theme', array( $this, 'triggerOptionCreation' ), 5 );

		// Create the options
		add_action( 'init', array( $this, 'triggerOptionCreation' ), 11 );
	}


	/**
	 * This will trigger the loading of all the options
	 *
	 * @access	public
	 * @return	void
	 * @since	1.6
	 */
	public function triggerOptionCreation() {
		// The after_setup_theme is the initialization stage
		if ( current_filter() == 'after_setup_theme' ) {
			TitanFramework::$initializing = true;
		}

		do_action( 'tf_create_options' );

		TitanFramework::$initializing = false;
	}


	/**
	 * Load plugin translations
	 *
	 * @access	public
	 * @return	void
	 * @since	1.0
	 */
	public function loadTextDomain() {
		load_plugin_textdomain( TF_I18NDOMAIN, false, basename( dirname( __FILE__ ) ) . '/languages/' );
	}


	/**
	 * Forces our plugin to be loaded first. This is to ensure that plugins that use the framework have access to
	 * this class.
	 *
	 * @access	public
	 * @return	void
	 * @since	1.0
	 * @see		loosly based on http://snippets.khromov.se/modify-wordpress-plugin-load-order/
	 */
	public function forceLoadFirst() {
		$tfFileName = basename( __FILE__ );
		if ( $plugins = get_option( 'active_plugins' ) ) {
			foreach ( $plugins as $key => $pluginPath ) {
				// If we are the first one to load already, don't do anything
				if ( strpos( $pluginPath, $tfFileName ) !== false && $key == 0 ) {
					break;
				// If we aren't the first one, force it!
				} else if ( strpos( $pluginPath, $tfFileName ) !== false ) {
					array_splice( $plugins, $key, 1 );
					array_unshift( $plugins, $pluginPath );
					update_option( 'active_plugins', $plugins );
					break;
				}
			}
		}
	}


	/**
	 * Adds links to the docs and GitHub
	 *
	 * @access	public
	 * @param	array $plugin_meta The current array of links
	 * @param	string $plugin_file The plugin file
	 * @return	array The current array of links together with our additions
	 * @since	1.1.1
	 **/
	public function pluginLinks( $plugin_meta, $plugin_file ) {
		if ( $plugin_file == plugin_basename( __FILE__ ) ) {
			$plugin_meta[] = sprintf( "<a href='%s' target='_blank'>%s</a>",
				"http://www.titanframework.net/docs",
				__( "Documentation", TF_I18NDOMAIN )
			);
			$plugin_meta[] = sprintf( "<a href='%s' target='_blank'>%s</a>",
				"https://github.com/gambitph/Titan-Framework",
				__( "GitHub Repo", TF_I18NDOMAIN )
			);
			$plugin_meta[] = sprintf( "<a href='%s' target='_blank'>%s</a>",
				"https://github.com/gambitph/Titan-Framework/issues",
				__( "Issue Tracker", TF_I18NDOMAIN )
			);
		}
		return $plugin_meta;
	}
}


new TitanFrameworkPlugin();