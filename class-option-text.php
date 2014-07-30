<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class TitanFrameworkOptionText extends TitanFrameworkOption {

	public $defaultSecondarySettings = array(
		'placeholder' => '', // show this when blank
		'is_password' => false,
		'sanitize_callbacks' => array(),
		'maxlength' => '',
		'unit' => ''
	);

	/*
	 * Display for options and meta
	 */
	public function display() {
		$this->echoOptionHeader();
		printf("<input class=\"regular-text\" name=\"%s\" placeholder=\"%s\" maxlength=\"%s\" id=\"%s\" type=\"%s\" value=\"%s\"\> %s",
			$this->getID(),
			$this->settings['placeholder'],
			$this->settings['maxlength'],
			$this->getID(),
			$this->settings['is_password'] ? 'password' : 'text',
			esc_attr( $this->getValue() ), 
			$this->settings['unit'] 
		);
		$this->echoOptionFooter();
	}

	public function cleanValueForSaving( $value ){
		$value = sanitize_text_field( $value );
		if( !empty( $this->settings['sanitize_callbacks'] ) ){
			foreach( $this->settings['sanitize_callbacks'] as $callback ){
				$value = call_user_func_array( $callback, array( $value, $this ) );
			}
		}

		return $value;
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