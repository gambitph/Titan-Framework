<?php
/**
 * Tests for class-option-text.php
 *
 * @package Titan Framework Tests
 */

class Titan_Framework_Option_Text_Test extends WP_UnitTestCase {
	
	public $adminPageOption;
	public $adminTabOption;
	public $customizerOption;
	public $metaOption;
	
	public function setUp() {
		
		add_action( 'tf_create_options', array( $this, 'tf_create_options' ) );
		do_action( 'init' );
		
		parent::setUp();
	}
	
	public function tearDown() {		
		remove_action( 'tf_create_options', array( $this, 'tf_create_options' ) );
		
		parent::tearDown();
	}
	
	public function tf_create_options() {
		$titan = TitanFramework::getInstance( 'testing' );
		
		$container = $titan->createAdminPage( array(
			'name' => 'test container',
		) );
		$this->adminPageOption = $container->createOption( array(
			'id' => 'test1',
			'type' => 'text',
			'default' => 'default',
		) );
		
		$container = $container->createTab( array(
			'name' => 'test container',
		) );
		$this->adminTabOption = $container->createOption( array(
			'id' => 'test2',
			'type' => 'text',
			'default' => 'default',
		) );
		

		$container = $titan->createCustomizer( array(
			'name' => 'test container',
		) );
		$this->customizerOption = $container->createOption( array(
			'id' => 'test3',
			'type' => 'text',
			'default' => 'default',
		) );
		

		$container = $titan->createMetaBox( array(
			'name' => 'test container',
		) );
		$this->metaOption = $container->createOption( array(
			'id' => 'test4',
			'type' => 'text',
			'default' => 'default',
		) );
	}
	
	function test_option_save_get_admin_page() {
		$option = $this->adminPageOption;
		
		$titan = TitanFramework::getInstance( 'testing' );
		
		$id = $option->settings['id'];
		$this->assertEquals( 'default', $titan->getOption( $id ) );
		
		$option->setValue( 'modified' );
		$titan->saveInternalAdminPageOptions();
		$this->assertEquals( 'modified', $titan->getOption( $id ) );
		
		$option->setValue( 'modified again' );
		$titan->saveInternalAdminPageOptions();
		$this->assertEquals( 'modified again', $titan->getOption( $id ) );
		
		$this->go_to( admin_url('options-general.php?page=test-container') );
	}
	
	function test_option_save_get_admin_tab() {
		$option = $this->adminTabOption;
		
		$titan = TitanFramework::getInstance( 'testing' );
		
		$id = $option->settings['id'];
		$this->assertEquals( 'default', $titan->getOption( $id ) );
		
		$option->setValue( 'modified' );
		$titan->saveInternalAdminPageOptions();
		$this->assertEquals( 'modified', $titan->getOption( $id ) );
		
		$option->setValue( 'modified again' );
		$titan->saveInternalAdminPageOptions();
		$this->assertEquals( 'modified again', $titan->getOption( $id ) );
	}
	
	function test_option_save_get_customizer() {
		$option = $this->customizerOption;
		
		$titan = TitanFramework::getInstance( 'testing' );
		
		$id = $option->settings['id'];
		$this->assertEquals( 'default', $titan->getOption( $id ) );
		
		$option->setValue( 'modified' );
		$this->assertEquals( 'modified', $titan->getOption( $id ) );
	}
	
	function test_option_save_get_meta_box() {
		$option = $this->metaOption;
		
		$titan = TitanFramework::getInstance( 'testing' );

		$p = $this->factory->post->create( array( 'post_title' => 'Test Post' ) );
		
		global $post;
		$post = get_post( $p );
		setup_postdata( $post );
		
		$id = $option->settings['id'];
		$this->assertEquals( 'default', $titan->getOption( $id ) );
		
		$option->setValue( 'modified' );
		$this->assertEquals( 'modified', $titan->getOption( $id ) );		
	}
	
}

