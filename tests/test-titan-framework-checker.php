<?php
/**
 * Tests for titan-framework-checker.php
 *
 * @package Titan Framework Tests
 */

class Titan_Framework_Checker_Test extends PHPUnit_Framework_TestCase {
	
	public function setUp() {
		parent::setUp();
		require_once( 'titan-framework-checker.php' );
	}

	function titan_regex( $regex ) {
		return '/something-sure-not-installed/i'; // Sure installed
	}
	
	function test_display_install_or_active_notice() {
		$o = new TitanFrameworkChecker();
		
		if ( ! function_exists( 'tgmpa' ) ) {
			ob_start();
			$o->display_install_or_active_notice();
			$result = ob_get_contents(); 
			ob_end_clean(); 
			$this->assertNotEmpty( $result );
			
			add_filter( 'tf_framework_checker_regex', array( $this, 'titan_regex' ) );
			
			ob_start();
			$o->display_install_or_active_notice();
			$result = ob_get_contents(); 
			ob_end_clean(); 
			$this->assertNotEmpty( $result );
			
			remove_filter( 'tf_framework_checker_regex', array( $this, 'titan_regex' ) );
			
			function tgmpa() {}
		}
		
		if ( function_exists( 'tgmpa' ) ) {
			ob_start();
			$o->display_install_or_active_notice();
			$result = ob_get_contents(); 
			ob_end_clean(); 
			$this->assertEmpty( $result );
		}
	}

	function test_titan_admin_install_activate_notifications() {
		$o = new TitanFrameworkChecker();
		
		ob_start();
		$o->display_admin_notification_not_exist();
		$result = ob_get_contents(); 
		ob_end_clean(); 
		$this->assertNotEmpty( $result );
		
		ob_start();
		$o->display_admin_notification_inactive();
		$result = ob_get_contents(); 
		ob_end_clean(); 
		$this->assertNotEmpty( $result );
		
	}
	
	function test_titan_admin_plugin_exists() {
		$o = new TitanFrameworkChecker();
		
		$result = $o->plugin_exists();
		$this->assertTrue( $result );
		
		add_filter( 'tf_framework_checker_regex', array( $this, 'titan_regex' ) );
		
		$result = $o->plugin_exists();
		$this->assertFalse( $result );

		add_filter( 'tf_framework_checker_regex', array( $this, 'titan_regex' ) );
	}
	
}

