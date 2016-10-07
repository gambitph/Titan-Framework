<?php
/**
 * Sample tests
 *
 * @package Titan Framework Tests
 */

class Titan_Framework_Sample extends WP_UnitTestCase {

	/**
	 * Ensure that the plugin has been installed and activated.
	 */
	function test_plugin_activated() {
		$this->assertTrue( is_plugin_active( 'Titan-Framework/titan-framework.php' ) );
	}

}
