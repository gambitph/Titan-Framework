<?php
/**
 * Tests for titan-framework.php
 *
 * @package Titan Framework Tests
 */

class Titan_Framework_Test extends PHPUnit_Framework_TestCase {
	
	public $tf_initializing = false;
	public $tf_create_options_called = false;
	public function tf_create_options() {
		$this->tf_initializing = TitanFramework::$initializing;
		$this->tf_create_options_called = true;
	}
	public $tf_done_called = false;
	public function tf_done() {
		$this->tf_done_called = true;
	}
	
	function test_trigger_initial_option_creation() {
		$o = new TitanFrameworkPlugin();
		
		$this->tf_initializing = false;
		$this->tf_create_options_called = false;
		$this->tf_done_called = false;
		
		add_action( 'tf_create_options', array( $this, 'tf_create_options' ) );
		add_action( 'tf_done', array( $this, 'tf_done' ) );
		
		$o->trigger_initial_option_creation();
		$this->assertTrue( $this->tf_create_options_called );
		$this->assertTrue( $this->tf_initializing );
		
		remove_action( 'tf_create_options', array( $this, 'tf_create_options' ) );
		remove_action( 'tf_done', array( $this, 'tf_done' ) );
	}
	
	function test_trigger_actual_option_creation() {
		$o = new TitanFrameworkPlugin();
		
		$this->tf_initializing = false;
		$this->tf_create_options_called = false;
		$this->tf_done_called = false;
		
		add_action( 'tf_create_options', array( $this, 'tf_create_options' ) );
		add_action( 'tf_done', array( $this, 'tf_done' ) );
		
		$o->trigger_actual_option_creation();
		$this->assertTrue( $this->tf_create_options_called );
		$this->assertTrue( $this->tf_done_called );
		$this->assertFalse( $this->tf_initializing );
		
		remove_action( 'tf_create_options', array( $this, 'tf_create_options' ) );
		remove_action( 'tf_done', array( $this, 'tf_done' ) );
	}
	
	function test_plugin_links() {
		$o = new TitanFrameworkPlugin();
		
		$meta = $o->plugin_links( array( 'existing' ), TF_PLUGIN_BASENAME );
		$this->assertContains( 'existing', $meta, 'Existing meta should not be touched.' );
		$this->assertGreaterThan( 1, count( $meta ), 'New meta links should be added' );
		
		$meta = $o->plugin_links( array( 'existing' ), 'another-plugin/plugin.php' );
		$this->assertContains( 'existing', $meta, 'Non TF meta should not be touched.' );
		$this->assertCount( 1, $meta, 'Non TF meta should not be touched.' );
	}
	
}

