<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class TitanFrameworkOptionText extends TitanFrameworkOption {

	public $defaultSecondarySettings = array(
		'placeholder' => '', // show this when blank
		'is_password' => false,
	);

	/*
	 * Display for options and meta
	 */
	public function display() {
		$this->echoOptionHeader();
		printf("<input class=\"regular-text\" name=\"%s\" placeholder=\"%s\" id=\"%s\" type=\"%s\" value=\"%s\" />",
			$this->getID(),
			$this->settings['placeholder'],
			$this->getID(),
			$this->settings['is_password'] ? 'password' : 'text',
			esc_attr( $this->getValue() ) );
		$this->echoOptionFooter();
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