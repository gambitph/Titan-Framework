<?php
/**
 * Tests for titan-framework-checker.php
 *
 * @package Titan Framework Tests
 */

class Titan_Framework_Checker_Test extends PHPUnit_Framework_TestCase {
	
	public $hasTitanChecker = false;
	
	public function setUp() {
		parent::setUp();
		
		// We need to require here since it's not included in normal behavior
		if ( file_exists( 'titan-framework-checker.php' ) ) {
			require_once( 'titan-framework-checker.php' );
			$this->hasTitanChecker = true;
		}
	}
	
	function titan_regex_pass( $regex ) {
		return '/hello\.php/i'; // Sure installed
	}

	function titan_regex_fail( $regex ) {
		return '/something-sure-not-installed/i'; // Sure not installed
	}
	
	function titan_class_fail( $class ) {
		return 'Titan_That_Does_Not_Exist'; // Sure class that does not exist
	}
	
	function titan_class_pass( $class ) {
		return 'TitanFrameworkChecker'; // Sure class that exists
	}
	
	function test_display_install_or_active_notice() {
		if ( ! class_exists( 'TitanFrameworkChecker' ) ) {
			return;
		}
		
		$o = new TitanFrameworkChecker();
		
		if ( ! function_exists( 'tgmpa' ) ) {
			
			// Titan is not installed, show message.
			add_filter( 'tf_framework_checker_regex', array( $this, 'titan_regex_fail' ) );
			
			ob_start();
			$o->display_install_or_active_notice();
			$result = ob_get_contents(); 
			ob_end_clean(); 
			$this->assertNotEmpty( $result );
			
			remove_filter( 'tf_framework_checker_regex', array( $this, 'titan_regex_fail' ) );
			
			// Titan is installed, but not activated, show message.
			add_filter( 'tf_framework_checker_regex', array( $this, 'titan_regex_pass' ) );
			add_filter( 'tf_framework_checker_titan_class', array( $this, 'titan_class_fail' ) );
			
			ob_start();
			$o->display_install_or_active_notice();
			$result = ob_get_contents(); 
			ob_end_clean();
			$this->assertNotEmpty( $result );
			
			remove_filter( 'tf_framework_checker_titan_class', array( $this, 'titan_class_fail' ) );
			remove_filter( 'tf_framework_checker_regex', array( $this, 'titan_regex_pass' ) );
			
			// Titan is installed and activated, don't show message.
			add_filter( 'tf_framework_checker_regex', array( $this, 'titan_regex_pass' ) );
			add_filter( 'tf_framework_checker_titan_class', array( $this, 'titan_class_pass' ) );
			
			ob_start();
			$o->display_install_or_active_notice();
			$result = ob_get_contents(); 
			ob_end_clean();
			$this->assertEmpty( $result );
			
			remove_filter( 'tf_framework_checker_regex', array( $this, 'titan_regex_pass' ) );
			remove_filter( 'tf_framework_checker_titan_class', array( $this, 'titan_class_pass' ) );
			
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
	
	function test_titan_admin_plugin_exists() {
		if ( ! class_exists( 'TitanFrameworkChecker' ) ) {
			return;
		}
		
		$o = new TitanFrameworkChecker();

		add_filter( 'tf_framework_checker_regex', array( $this, 'titan_regex_fail' ) );
		
		$result = $o->plugin_exists();
		$this->assertFalse( $result );
		
		remove_filter( 'tf_framework_checker_regex', array( $this, 'titan_regex_fail' ) );
		
		add_filter( 'tf_framework_checker_regex', array( $this, 'titan_regex_pass' ) );
		
		$result = $o->plugin_exists();
		$this->assertTrue( $result );

		add_filter( 'tf_framework_checker_regex', array( $this, 'titan_regex_pass' ) );
	}
	
}

