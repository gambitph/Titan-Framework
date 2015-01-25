<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class TitanFrameworkOptionHeading extends TitanFrameworkOption {

	/*
	 * Display for options and meta
	 */
	public function display() {
		?>
		<tr valign="top" class="even first tf-heading">
		<th scope="row" class="first last" colspan="2">
		<h3><?php echo $this->settings['name'] ?></h3>
		</th>
		</tr>
		<?php
	}
    
/*
* Display for theme customizer
*/
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkOptionHeadingControl( $wp_customize, $this->getID(), array(
			'label' => $this->settings['name'],
			'section' => $section->settings['id'],
			'type' => 'select',
			'settings' => $this->getID(),
			'description' => $this->settings['desc'],
			'priority' => $priority,
		) ) );
	}
}


/*
 * We create a new control for the theme customizer
 */
add_action( 'customize_register', 'registerTitanFrameworkOptionHeadingControl', 1 );
function registerTitanFrameworkOptionHeadingControl() {
	class TitanFrameworkOptionHeadingControl extends WP_Customize_Control {
		public $description;

		public function render_content() {

			?><span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span><?php

			if ( ! empty( $this->description ) ) {
				echo "<p class='description'>" . $this->description . "</p>";
			}

				?>

				<?php
			}
		}
}
