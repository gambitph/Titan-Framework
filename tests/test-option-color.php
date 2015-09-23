<?php
/**
 * Tests for class-option-color.php
 *
 * @package Titan Framework Tests
 */

class Titan_Framework_Option_Color_Test extends WP_UnitTestCase {
	
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
		
		$titan = TitanFramework::getInstance( 'testing' );
		$titan->deleteAllOptions();
		
		parent::tearDown();
	}
	
	public function tf_create_options() {
		$titan = TitanFramework::getInstance( 'testing' );
		
		$container = $titan->createAdminPage( array(
			'name' => 'test container',
		) );
		$this->adminPageOption = $container->createOption( array(
			'id' => 'test1',
			'type' => 'color',
			'default' => '#123456',
		) );
		
		$container = $container->createTab( array(
			'name' => 'test container',
		) );
		$this->adminTabOption = $container->createOption( array(
			'id' => 'test2',
			'type' => 'color',
			'default' => '#123456',
		) );


		$container = $titan->createCustomizer( array(
			'name' => 'test container',
		) );
		$this->customizerOption = $container->createOption( array(
			'id' => 'test3',
			'type' => 'color',
			'default' => '#123456',
		) );


		$container = $titan->createMetaBox( array(
			'name' => 'test container',
		) );
		$this->metaOption = $container->createOption( array(
			'id' => 'test4',
			'type' => 'color',
			'default' => '#123456',
		) );
	}
	
	protected function checkValues( $option ) {
		$titan = TitanFramework::getInstance( 'testing' );
	
		$id = $option->settings['id'];
		$this->assertEquals( '#123456', $titan->getOption( $id ) );
	
		$option->setValue( 'rgba(0,0,0,.5)' );
		$titan->saveInternalAdminPageOptions(); // Does nothing for non-admin options
		$this->assertEquals( 'rgba(0,0,0,.5)', $titan->getOption( $id ) );
	
		$option->setValue( '' );
		$titan->saveInternalAdminPageOptions(); // Does nothing for non-admin options
		$this->assertEquals( '', $titan->getOption( $id ) );
	}
	
	function test_option_save_get_admin_page() {
		$this->checkValues( $this->adminPageOption );
	}
	
	function test_option_save_get_admin_tab() {
		$this->checkValues( $this->adminTabOption );
	}

	function test_option_save_get_customizer() {
		$this->checkValues( $this->customizerOption );
	}

	function test_option_save_get_meta_box() {
		$p = $this->factory->post->create( array( 'post_title' => 'Test Post' ) );
		
		global $post;
		$post = get_post( $p );
		setup_postdata( $post );
		
		$this->checkValues( $this->metaOption );
	}
	
}

