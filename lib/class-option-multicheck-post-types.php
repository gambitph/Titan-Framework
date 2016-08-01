<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}
class TitanFrameworkOptionMulticheckPostTypes extends TitanFrameworkOptionMulticheck {

	public $defaultSecondarySettings = array(
		'options' => array(),
		'public' => true,
		'value' => 'all',
		'slug' => true,
	);

	/*
	 * Display for options and meta
	 */
	public function display() {

		// Fetch post types.
		$post_types = tf_get_post_types( $this->settings['public'], $this->settings['value'] );

		$this->settings['options'] = array();
		foreach ( $post_types as $post_type ) {

			$slug = $post_type->name;

			$slugname = true == $this->settings['slug'] ? ' (' . $slug . ')' : '';

			$name = $post_type->name;
			if ( ! empty( $post_type->labels->singular_name ) ) {
				$name = $post_type->labels->singular_name . $slugname;
			}

			$this->settings['options'][ $slug ] = $name;
		}

		parent::display();
	}

	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {

		// Fetch post types.
		$post_types = tf_get_post_types( $this->settings['public'], $this->settings['value'] );

		$this->settings['options'] = array();
		foreach ( $post_types as $post_type ) {

			$slug = $post_type->name;

			$slugname = true == $this->settings['slug'] ? ' (' . $slug . ')' : '';

			$name = $post_type->name;
			if ( ! empty( $post_type->labels->singular_name ) ) {
				$name = $post_type->labels->singular_name . ' (' . $slugname . ')';
			}

			$this->settings['options'][ $slug ] = $name;
		}

		$wp_customize->add_control( new TitanFrameworkOptionMulticheckControl( $wp_customize, $this->getID(), array(
			'label' => $this->settings['name'],
			'section' => $section->settings['id'],
			'settings' => $this->getID(),
			'description' => $this->settings['desc'],
			'public' => $this->settings['public'],
			'value' => $this->settings['value'],
			'slug' => $this->settings['slug'],
			'options' => $this->settings['options'],
			'priority' => $priority,
		) ) );
	}
}
