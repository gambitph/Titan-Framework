<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class TitanFrameworkOptionSelect extends TitanFrameworkOption {

	public $defaultSecondarySettings = array(
		'options' => array(),
	);

	/*
	 * Display for options and meta
	 */
	public function display() {
		$this->echoOptionHeader();

		?><select name="<?php echo $this->getID(); ?>"><?php
		foreach ( $this->settings['options'] as $value => $label ) {

			// this is if we have option groupings
			if ( is_array( $label ) ) {
				?><optgroup label="<?php echo $value ?>"><?php
				foreach ( $label as $subValue => $subLabel ) {
					printf("<option value=\"%s\" %s>%s</option>",
						$subValue,
						selected( $this->getValue(), $subValue, false ),
						$subLabel
						);
				}
				?></optgroup><?php

			// this is for normal list of options
			} else {
				printf("<option value=\"%s\" %s>%s</option>",
					$value,
					selected( $this->getValue(), $value, false ),
					$label
					);
			}
		}
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
		if ( ! $isAssociativeArray ) {
			$class = "TitanFrameworkCustomizeControl";

		// Associative array, custom make the control
		} else {
			$class = "TitanFrameworkOptionSelectControl";
		}

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
					<?php
					foreach ( $this->choices as $value => $label ):
						?><optgroup label="<?php echo $value ?>"><?php
						foreach ( $label as $subValue => $subLabel ) {
							printf("<option value=\"%s\" %s>%s</option>",
								esc_attr( $subValue ),
								selected( $this->value(), $subValue, false ),
								$subLabel
								);
						}
						?></optgroup><?php
					endforeach;
					?>
				</select>
			</label>
			<?php

			echo "<p class='description'>{$this->description}</p>";
		}
	}
}