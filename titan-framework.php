<?php
/**
 * Main plugin file
 *
 * @package Titan Framework
 *
 * @see lib/class-titan-framework.php
 */

/**
Plugin Name: Titan Framework
Plugin URI: http://www.titanframework.net/
Description: Titan Framework allows theme and plugin developers to create a admin pages, options, meta boxes, and theme customizer options with just a few simple lines of code.
Author: Benjamin Intal, Gambit
Version: 1.9.2
Author URI: http://gambit.ph
 */

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly.
}

// Used for tracking the version used.
defined( 'TF_VERSION' ) or define( 'TF_VERSION', '1.9.2' );
// Used for text domains.
defined( 'TF_I18NDOMAIN' ) or define( 'TF_I18NDOMAIN', 'titan-framework' );
// Used for general naming, e.g. nonces.
defined( 'TF' ) or define( 'TF', 'titan-framework' );
// Used for general naming.
defined( 'TF_NAME' ) or define( 'TF_NAME', 'Titan Framework' );
// Used for file includes.
defined( 'TF_PATH' ) or define( 'TF_PATH', trailingslashit( dirname( __FILE__ ) ) );
// Used for testing and checking plugin slug name.
defined( 'TF_PLUGIN_BASENAME' ) or define( 'TF_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once( TF_PATH . 'lib/class-admin-notification.php' );
require_once( TF_PATH . 'lib/class-admin-page.php' );
require_once( TF_PATH . 'lib/class-admin-tab.php' );
require_once( TF_PATH . 'lib/class-customizer.php' );
require_once( TF_PATH . 'lib/class-meta-box.php' );
require_once( TF_PATH . 'lib/class-option.php' );
require_once( TF_PATH . 'lib/class-option-ajax-button.php' );
require_once( TF_PATH . 'lib/class-option-checkbox.php' );
require_once( TF_PATH . 'lib/class-option-code.php' );
require_once( TF_PATH . 'lib/class-option-color.php' );
require_once( TF_PATH . 'lib/class-option-custom.php' );
require_once( TF_PATH . 'lib/class-option-edd-license.php' );
require_once( TF_PATH . 'lib/class-option-date.php' );
require_once( TF_PATH . 'lib/class-option-enable.php' );
require_once( TF_PATH . 'lib/class-option-editor.php' );
require_once( TF_PATH . 'lib/class-option-font.php' );
/* require_once( TF_PATH . 'lib/class-option-gallery.php' ); */
require_once( TF_PATH . 'lib/class-option-heading.php' );
require_once( TF_PATH . 'lib/class-option-iframe.php' );
require_once( TF_PATH . 'lib/class-option-multicheck.php' );
require_once( TF_PATH . 'lib/class-option-multicheck-categories.php' );
require_once( TF_PATH . 'lib/class-option-multicheck-pages.php' );
require_once( TF_PATH . 'lib/class-option-multicheck-posts.php' );
require_once( TF_PATH . 'lib/class-option-note.php' );
require_once( TF_PATH . 'lib/class-option-number.php' );
require_once( TF_PATH . 'lib/class-option-radio.php' );
require_once( TF_PATH . 'lib/class-option-radio-image.php' );
require_once( TF_PATH . 'lib/class-option-radio-palette.php' );
require_once( TF_PATH . 'lib/class-option-save.php' );
require_once( TF_PATH . 'lib/class-option-select-categories.php' );
require_once( TF_PATH . 'lib/class-option-select-pages.php' );
require_once( TF_PATH . 'lib/class-option-select-posts.php' );
require_once( TF_PATH . 'lib/class-option-select.php' );
require_once( TF_PATH . 'lib/class-option-separator.php' );
require_once( TF_PATH . 'lib/class-option-sortable.php' );
require_once( TF_PATH . 'lib/class-option-text.php' );
require_once( TF_PATH . 'lib/class-option-textarea.php' );
require_once( TF_PATH . 'lib/class-option-upload.php' );
require_once( TF_PATH . 'lib/class-titan-css.php' );
require_once( TF_PATH . 'lib/class-titan-framework.php' );
require_once( TF_PATH . 'lib/class-wp-customize-control.php' );
require_once( TF_PATH . 'lib/functions-googlefonts.php' );
require_once( TF_PATH . 'lib/functions-utils.php' );

/**
 * Titan Framework Plugin Class
 *
 * @since 1.0
 */
class TitanFrameworkPlugin {


	/**
	 * Constructor, add hooks
	 *
	 * @since 1.0
	 */
	function __construct() {
		add_action( 'plugins_loaded', array( $this, 'load_text_domain' ) );
		add_action( 'plugins_loaded', array( $this, 'force_load_first' ), 10, 1 );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_links' ), 10, 2 );

		// Create the options.
		add_action( 'init', array( $this, 'trigger_option_creation' ), 1 );
	}


	/**
	 * Trigger the creation of the options
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @return void
	 */
	public function trigger_option_creation() {

		/**
		 * Triggers the creation of options. Hook into this action and use the various create methods.
		 *
		 * @since 1.0
		 */
		do_action( 'tf_create_options' );

		/**
		 * Fires immediately after options are created.
		 *
		 * @since 1.8
		 */
		do_action( 'tf_done' );
	}


	/**
	 * Load plugin translations
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @return void
	 */
	public function load_text_domain() {
		load_plugin_textdomain( TF_I18NDOMAIN, false, basename( dirname( __FILE__ ) ) . '/languages/' );
	}


	/**
	 * Forces our plugin to be loaded first. This is to ensure that plugins that use the framework have access to
	 * this class from almost anywhere
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @return void
	 *
	 * @see	initially based on http://snippets.khromov.se/modify-wordpress-plugin-load-order/
	 */
	public function force_load_first( $plugins = null ) {
		$plugins = $plugins == null ? (array) get_option( 'active_plugins' ) : $plugins;

		if ( ! empty( $plugins ) ) {
			$index = array_search( TF_PLUGIN_BASENAME, $plugins );
			if ( false !== $index && 0 !== $index ) {
				array_splice( $plugins, $index, 1 );
				array_unshift( $plugins, TF_PLUGIN_BASENAME );
				update_option( 'active_plugins', $plugins );
			}
		}

		return $plugins;
	}


	/**
	 * Adds links to the docs and GitHub
	 *
	 * @since 1.1.1
	 * @access public
	 *
	 * @param	array  $plugin_meta The current array of links.
	 * @param	string $plugin_file The plugin file.
	 * @return	array  The current array of links together with our additions
	 **/
	public function plugin_links( $plugin_meta, $plugin_file ) {
		if ( TF_PLUGIN_BASENAME == $plugin_file ) {
			$plugin_meta[] = sprintf( "<a href='%s' target='_blank'>%s</a>",
				'http://www.titanframework.net/docs',
				__( 'Documentation', TF_I18NDOMAIN )
			);
			$plugin_meta[] = sprintf( "<a href='%s' target='_blank'>%s</a>",
				'https://github.com/gambitph/Titan-Framework',
				__( 'GitHub Repo', TF_I18NDOMAIN )
			);
			$plugin_meta[] = sprintf( "<a href='%s' target='_blank'>%s</a>",
				'https://github.com/gambitph/Titan-Framework/issues',
				__( 'Issue Tracker', TF_I18NDOMAIN )
			);
		}
		return $plugin_meta;
	}
}


new TitanFrameworkPlugin();
