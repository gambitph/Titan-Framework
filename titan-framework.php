<?php
/*
Plugin Name: Titan Framework
Plugin URI: http://titanframework.net/
Description: Titan Framework allows theme and plugin developers to create a admin pages, options, meta boxes, and theme customizer options with just a few simple lines of code.
Author: Benjamin Intal, Gambit
Version: 1.0.2
Author URI: http://gambit.ph
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Used for text domains
define( 'TF_I18NDOMAIN', 'titan-framework' );
// Used for general naming, e.g. nonces
define( 'TF', 'titan-framework' );
// Used for general naming
define( 'TF_NAME', 'Titan Framework' );


require_once( plugin_dir_path( __FILE__ ) . 'class-admin-notification.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-admin-panel.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-admin-tab.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-meta-box.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-option.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-option-checkbox.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-option-color.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-option-heading.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-option-multicheck.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-option-multicheck-categories.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-option-multicheck-pages.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-option-multicheck-posts.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-option-note.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-option-number.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-option-radio.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-option-save.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-option-select-categories.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-option-select-googlefont.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-option-select-pages.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-option-select-posts.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-option-select.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-option-text.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-option-textarea.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-option-upload.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-theme-customizer-section.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-titan-framework.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-wp-customize-control.php' );
require_once( plugin_dir_path( __FILE__ ) . 'functions-googlefonts.php' );


class TitanFrameworkPlugin {
    function __construct() {
        add_action( 'plugins_loaded', array( $this, 'loadTextDomain' ) );
        add_action( 'activated_plugin', array( $this, 'forceLoadFirst' ) );
        add_filter( 'plugin_action_links', array( $this, 'pluginLink' ), 10, 2);
    }

    public function loadTextDomain() {
        load_plugin_textdomain( TF_I18NDOMAIN, false, basename( dirname( __FILE__ ) ) . '/languages/' );
    }

    /*
     * Forces our plugin to be loaded first. This is to ensure that plugins that use the framework have access to
     * this class.
     *
     * Thanks to http://snippets.khromov.se/modify-wordpress-plugin-load-order/ for this
     */
    public function forceLoadFirst() {
        $path = str_replace( WP_PLUGIN_DIR . '/', '', __FILE__ );
        if ( $plugins = get_option( 'active_plugins' ) ) {
            if ( $key = array_search( $path, $plugins ) ) {
                array_splice( $plugins, $key, 1 );
                array_unshift( $plugins, $path );
                update_option( 'active_plugins', $plugins );
            }
        }
    }

    public function pluginLink( $action_links, $plugin_file ) {
        return $action_links;
        if ( $plugin_file == plugin_basename( __FILE__ ) ) {
            $customLink = '<a href="http://titanframework.net" target="blank">' . __( "What's this?", TF_I18NDOMAIN ) . '</a>';
            array_unshift( $action_links, $customLink );
        }
        return $action_links;
    }
}
new TitanFrameworkPlugin();