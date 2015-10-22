<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

define( 'TF_PLUGIN_BASENAME', 'Titan-Framework/titan-framework.php' );

require_once $_tests_dir . '/includes/functions.php';

$GLOBALS['wp_tests_options'] = array(
	'active_plugins' => array( 'Titan-Framework/titan-framework.php' ),
);

function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/titan-framework.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
require dirname( __FILE__ ) . '/helpers/shims.php';