<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}
class TitanFrameworkOptionSelectPostTypes extends TitanFrameworkOptionSelect {

	public $defaultSecondarySettings = array(
		'default' => '0', // show this when blank
		'public' => true,
		'value' => 'all',
		'slug' => true,
	);


	/**
	 * Creates the options for the select input. Puts the options in $this->settings['options']
	 *
	 * @since 1.11
	 *
	 * @return void
	 */
	public function create_select_options() {
		// Fetch post types.
		$post_types = tf_get_post_types( $this->settings['public'], $this->settings['value'] );

		$this->settings['options'] = array(
			'' => '— ' . __( 'Select', TF_I18NDOMAIN ) . ' —'
		);

		// Print all the other pages
		foreach ( $post_types as $post_type ) {

			if ( ! empty( $post_type->labels->singular_name ) ) {
				$slugname = true == $this->settings['slug'] ? ' (' . $post_type->name . ')' : '';
				$name = $post_type->labels->singular_name . $slugname;
			} else {
				$name = $post_type->name;
			}

			$this->settings['options'][ $post_type->name ] = $name;
		}
	}


	/*
	 * Display for options and meta
	 */
	public function display() {
		$this->create_select_options();
		parent::display();
	}


	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$this->create_select_options();
		parent::registerCustomizerControl( $wp_customize, $section, $priority );
	}

}
