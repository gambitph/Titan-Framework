<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}
class TitanFrameworkOptionRadioImage extends TitanFrameworkOption {

	public $defaultSecondarySettings = array(
		'options' => array(),
	);

	function __construct( $settings, $owner ) {
		parent::__construct( $settings, $owner );
	}

	/*
	 * Display for options and meta
	 */
	public function display() {
		if ( empty( $this->settings['options'] ) ) {
			return;
		}
		if ( $this->settings['options'] == array() ) {
			return;
		}

		$this->echoOptionHeader();

		// Get the correct value, since we are accepting indices in the default setting
		$value = $this->getValue();

		// print the images
		foreach ( $this->settings['options'] as $key => $imageURL ) {
			if ( $value == '' ) {
				$value = $key;
			}
			printf( '<label id="%s"><input id="%s" type="radio" name="%s" value="%s" %s/> <img src="%s" /></label>',
				$this->getID() . $key,
				$this->getID() . $key,
				$this->getID(),
				esc_attr( $key ),
				checked( $value, $key, false ),
				esc_attr( $imageURL )
			);
		}

		$this->echoOptionFooter();
	}

	// Save the index of the selected option
	public function cleanValueForSaving( $value ) {
		if ( ! is_array( $this->settings['options'] ) ) {
			return $value;
		}
		// if the key above is zero, we will get a blank value
		if ( $value == '' ) {
			$keys = array_keys( $this->settings['options'] );
			return $keys[0];
		}
		return $value;
	}

	// The value we should return is a key of one of the options
	public function cleanValueForGetting( $value ) {
		if ( ! empty( $this->settings['options'] ) && $value == '' ) {
			$keys = array_keys( $this->settings['options'] );
			return $keys[0];
		}
		return $value;
	}

	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkOptionRadioImageControl( $wp_customize, $this->getID(), array(
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
 * We create a new control for the theme customizer
 */
add_action( 'customize_register', 'registerTitanFrameworkOptionRadioImageControl', 1 );
function registerTitanFrameworkOptionRadioImageControl() {
	class TitanFrameworkOptionRadioImageControl extends WP_Customize_Control {
		public $description;

		public function render_content() {

			?><span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span><?php

if ( ! empty( $this->description ) ) {
	echo "<p class='description'>" . $this->description . '</p>';
}

			// print the images
			$value = $this->value();
foreach ( $this->choices as $key => $imageURL ) {
	// Get the correct value, we might get a blank if index / value is 0
	if ( $value == '' ) {
		$value = $key;
	}
	?>
	<span class='tf-radio-image'>
		<label>
			<input type="radio" name="<?php echo esc_attr( $this->id ) ?>" value="<?php echo esc_attr( $key ) ?>" <?php $this->link(); checked( $value, $key ); ?>/>
				<img src="<?php echo esc_attr( $imageURL ) ?>"/>
			</input>
		</label>
				</span>
				<?php
}
		}
	}
}
