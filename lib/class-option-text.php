<?php
/**
 * Text Option
 *
 * @package Titan Framework
 */

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly.
}

/**
 * Text Option
 *
 * Creates a text option
 *
 * <strong>Creating a text option:</strong>
 * <pre>$panel->createOption( array(
 *     'name' => 'My Text Option',
 *     'id' => 'my_text_option',
 *     'type' => 'text',
 *     'desc' => 'This is our option',
 * ) );</pre>
 *
 * @since 1.0
 * @type text
 * @availability Admin Pages|Meta Boxes|Customizer
 */
class TitanFrameworkOptionText extends TitanFrameworkOption {

	/**
	 * Default settings specific for this option
	 * @var array
	 */
	public $defaultSecondarySettings = array(

		/**
		 * (Optional) The placeholder label shown when the input field is blank
		 *
		 * @since 1.0
		 * @var string
		 */
		'placeholder' => '',

		/**
		 * (Optional) If true, the value of the input field will be hidden while typing.
		 *
		 * @since 1.0
		 * @var boolean
		 */
		'is_password' => false,

		/**
		 * (Optional) Callback function to call for additional input sanitization, this function will be called right before the option is saved.
		 *
		 * <pre>'my_sanitizing_function'</pre>
		 * or
		 * <pre>array( $this, 'my_sanitizing_function' )</pre>
		 *
		 * @since 1.5
		 * @var string|array
		 */
		'sanitize_callbacks' => array(),

		/**
		 * (Optional) The maximum character length allowed for the input field.
		 *
		 * @since 1.0
		 * @var int
		 */
		'maxlength' => '',

		/**
		 * (Optional) An additional label, located immediately after the form field. Accepts alphanumerics and symbols. Potential applications include indication of the unit, especially if the field is used with numbers.
		 *
		 * @since 1.5.2
		 * @var string
		 * @example 'px' or '%'
		 */
		'unit' => '',
	);

	/**
	 * Display for options and meta
	 */
	public function display() {
		$this->echoOptionHeader();
		printf('<input class="regular-text" name="%s" placeholder="%s" maxlength="%s" id="%s" type="%s" value="%s"\> %s',
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

	/**
	 * Cleans the value before saving the option
	 *
	 * @param string $value The value of the option.
	 */
	public function cleanValueForSaving( $value ) {
		$value = sanitize_text_field( $value );
		if ( ! empty( $this->settings['sanitize_callbacks'] ) ) {
			foreach ( $this->settings['sanitize_callbacks'] as $callback ) {
				$value = call_user_func_array( $callback, array( $value, $this ) );
			}
		}

		return $value;
	}

	/**
	 * Display for theme customizer
	 *
	 * @param WP_Customize             $wp_customize The customizer object.
	 * @param TitanFrameworkCustomizer $section      The customizer section.
	 * @param int                      $priority     The display priority of the control.
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
