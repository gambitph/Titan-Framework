<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}
class TitanFrameworkOptionEditor extends TitanFrameworkOption {

	public $defaultSecondarySettings = array(
		'wpautop' => true,
		'media_buttons' => true,
		'rows' => 10,
		'editor_settings' => array(),
	);

	/*
	 * Display for options and meta
	 */
	public function display() {

		$editorSettings = array(
			'wpautop' => $this->settings['wpautop'],
			'media_buttons' => $this->settings['media_buttons'],
			'textarea_rows' => $this->settings['rows'],
		);

		if ( is_array( $this->settings['editor_settings'] ) ) {
			$editorSettings = array_merge( $editorSettings, $this->settings['editor_settings'] );
		}

		$this->echoOptionHeader();

		wp_editor( $this->getValue(), $this->getID(), $editorSettings );

		$this->echoOptionFooter();
	}

	public function cleanValueForGetting( $value ) {
		if ( $this->settings['wpautop'] ) {
			return wpautop( stripslashes( $value ) );
		}
		return stripslashes( $value );
	}


	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkCustomizeControl( $wp_customize, $this->getID(), array(
			'label' => $this->settings['name'],
			'section' => $section->settings['id'],
			'settings' => $this->getID(),
			'description' => $this->settings['desc'],
			'priority' => $priority,
		) ) );
	}
}
