<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class TitanFrameworkOptionRadioPalette extends TitanFrameworkOption {

	public $defaultSecondarySettings = array(
		'options' => array(),
	);

	function __construct( $settings, $owner ) {
		parent::__construct( $settings, $owner );

		add_action( 'tf_livepreview_pre_' . $this->getOptionNamespace(), array( $this, 'preLivePreview' ), 10, 3 );
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
		if ( $value == '' ) {
			$value = 0;
		}
		if ( ! is_array( $value ) ) {
			$value = $this->settings['options'][$value];
		}

		// print the palettes
		foreach ( $this->settings['options'] as $key => $colorSet ) {
			printf( '<label id="%s"><input id="%s" type="radio" name="%s" value="%s" %s/> <span>',
				$this->getID() . $key,
				$this->getID() . $key,
				$this->getID(),
				esc_attr( $key ),
				$value == $colorSet ? 'checked="checked"' : '' // can't use checked with arrays
			);
			if ( ! is_array( $colorSet ) ) {
				continue;
			}
			foreach ( $colorSet as $color ) {
				echo "<span style='background: {$color}'></span>";
			}
			echo "</span></label>";
		}

		$this->echoOptionFooter();
	}

	// Save the index of the selected palette
	public function cleanValueForSaving( $value ) {
		if ( ! is_array( $this->settings['options'] ) ) {
			return $value;
		}
		// if the key above is zero, we will get a blank value
		if ( $value === '' ) {
			$value = 0;
		}
		return $value;
	}

	// The value we should return is an array of the selected colors
	public function cleanValueForGetting( $value ) {
		if ( is_array( $value ) ) {
			return $value;
		}
		$value = stripslashes( $value );
		if ( is_serialized( $value ) ) {
			return unserialize( $value );
		}
		if ( empty( $value ) ) {
			$value = 0;
		}
		if ( array_key_exists( $value, $this->settings['options'] ) ) {
			return $this->settings['options'][$value];
		}
		return $value;
	}

	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkOptionRadioPaletteControl( $wp_customize, $this->getID(), array(
			'label' => $this->settings['name'],
			'section' => $section->settings['id'],
			'type' => 'select',
			'choices' => $this->settings['options'],
			'settings' => $this->getID(),
			'description' => $this->settings['desc'],
			'priority' => $priority,
		) ) );
	}

	// For live previews, we need to give our options to javascript and then get the proper value from it
	public function preLivePreview( $optionID, $optionType, $option ) {
		if ( $optionID != $this->settings['id'] || empty( $this->settings['options'] ) ) {
			return;
		}
		if ( $this->settings['options'] == array() ) {
			return;
		}
		?>
		var options = JSON.parse('<?php echo json_encode( $this->settings['options'] ) ?>'),
		key = value;

		value = options[value];
		<?php
	}
}


/*
 * We create a new control for the theme customizer
 */
add_action( 'customize_register', 'registerTitanFrameworkOptionRadioPaletteControl', 1 );
function registerTitanFrameworkOptionRadioPaletteControl() {
	class TitanFrameworkOptionRadioPaletteControl extends WP_Customize_Control {
		public $description;

		public function render_content() {
			// Get the correct value, we might get a blank if index / value is 0
			$value = $this->value();
			if ( $value == '' ) {
				$value = 0;
			}

			?><span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span><?php

			if ( ! empty( $this->description ) ) {
				echo "<p class='description'>" . $this->description . "</p>";
			}

			// print the palettes
			foreach ( $this->choices as $key => $colorSet ) {
				if ( ! is_array( $colorSet ) ) {
					continue;
				}
				?>
				<span class='tf-radio-palette'>
					<label>
						<input type="radio" name="<?php echo $this->id ?>" value="<?php echo esc_attr( $key ) ?>" <?php $this->link(); checked( $value, $key ); ?>/>
						<span>
							<?php
							foreach ( $colorSet as $color ) {
								echo "<span style='background: {$color}'></span>";
							}
							?>
						</span>
					</label>
				</span>
				<?php
			}
		}
	}
}