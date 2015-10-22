<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}
class TitanFrameworkOptionSelect extends TitanFrameworkOption {

	public $defaultSecondarySettings = array(
		'options' => array(),
	);

	/*
	 * Display for options and meta
	 */
	public function display() {

		$this->echoOptionHeader();

		$multiple = isset( $this->settings['multiple'] ) && true == $this->settings['multiple'] ? 'multiple' : '';
		$name = $this->getID();
		$val = (array) $this->getValue();

		if ( ! empty( $multiple ) ) {
			$name = "{$name}[]";
		}

		?><select name="<?php echo $name; ?>" <?php echo $multiple; ?>><?php
			tf_parse_select_options( $this->settings['options'], $val );
		?></select><?php
		$this->echoOptionFooter();
	}

	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$isAssociativeArray = false;

		if ( count( $this->settings['options'] ) ) {
			foreach ( $this->settings['options'] as $value => $label ) {
				$isAssociativeArray = is_array( $label );
				break;
			}
		}

		// Not associative array, do normal control
		// if ( ! $isAssociativeArray ) {
		// $class = "TitanFrameworkCustomizeControl";
		//
		// // Associative array, custom make the control
		// } else {
			$class = 'TitanFrameworkOptionSelectControl';
		// }
		$wp_customize->add_control( new $class( $wp_customize, $this->getID(), array(
			'label' => $this->settings['name'],
			'section' => $section->settings['id'],
			'type' => 'select',
			'choices' => $this->settings['options'],
			'settings' => $this->getID(),
			'description' => $this->settings['desc'],
			'priority' => $priority,
		) ) );
	}
}


/*
 * We create a new control for the theme customizer (for the grouped options only)
 */
add_action( 'customize_register', 'registerTitanFrameworkOptionSelectControl', 1 );
function registerTitanFrameworkOptionSelectControl() {
	class TitanFrameworkOptionSelectControl extends WP_Customize_Control {
		public $description;

		public function render_content() {
			?>
			<label>
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
				<select <?php $this->link(); ?>>
					<?php tf_parse_select_options( $this->choices, (array) $this->value() ); ?>
				</select>
			</label>
			<?php

			echo "<p class='description'>{$this->description}</p>";
		}
	}
}

/**
 * Helper function for parsing select options
 *
 * This function is used to reduce duplicated code between the TF option
 * and the customizer control.
 *
 * @since 1.9
 *
 * @param array $options List of options
 * @param array $val     Current value
 *
 * @return void
 */
function tf_parse_select_options( $options, $val = array() ) {

	/* No options? Duh... */
	if ( empty( $options ) ) {
		return;
	}

	/* Make sure the current value is an array (for multiple select) */
	if ( ! is_array( $val ) ) {
		$val = (array) $val;
	}

	foreach ( $options as $value => $label ) {

		// this is if we have option groupings
		if ( is_array( $label ) ) {

			?>
			<optgroup label="<?php echo $value ?>"><?php
			foreach ( $label as $subValue => $subLabel ) {

				printf( '<option value="%s" %s %s>%s</option>',
					$subValue,
					in_array( $subValue, $val ) ? 'selected="selected"' : '',
					disabled( stripos( $subValue, '!' ), 0, false ),
					$subLabel
				);
			}
			?></optgroup><?php
		} // this is for normal list of options
		else {
			printf( '<option value="%s" %s %s>%s</option>',
				$value,
				in_array( $value, $val ) ? 'selected="selected"' : '',
				disabled( stripos( $value, '!' ), 0, false ),
				$label
			);
		}
	}

}
