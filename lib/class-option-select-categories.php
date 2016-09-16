<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}
class TitanFrameworkOptionSelectCategories extends TitanFrameworkOptionSelect {

	public $defaultSecondarySettings = array(
		'default' => '0', // show this when blank
		'orderby' => 'name',
		'order' => 'ASC',
		'taxonomy' => 'category',
		'hide_empty' => false,
		'show_count' => false,
	);


	/**
	 * Creates the options for the select input. Puts the options in $this->settings['options']
	 *
	 * @since 1.11
	 *
	 * @return void
	 */
	public function create_select_options() {
			$args = array(
				'orderby' => $this->settings['orderby'],
				'order' => $this->settings['order'],
				'taxonomy' => $this->settings['taxonomy'],
				'hide_empty' => $this->settings['hide_empty'] ? '1' : '0',
			);

			$categories = get_terms( $args );

			$this->settings['options'] = array(
				'' => '— ' . __( 'Select', TF_I18NDOMAIN ) . ' —'
			);

			foreach ( $categories as $category ) {
				$category_id = esc_attr( $category->term_id );
				$category->name .= $this->settings['show_count'] ? ' (' . $category->count . ')' : '';
				$this->settings['options'][ $category_id ] = esc_html( $category->name );
			}
	}


	/**
	 * Display for options and meta
	 */
	public function display() {
		$this->create_select_options();
		parent::display();
	}


	/**
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$this->create_select_options();
		parent::registerCustomizerControl( $wp_customize, $section, $priority );

	}
}
