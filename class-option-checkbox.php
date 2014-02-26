<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class TitanFrameworkOptionCheckbox extends TitanFrameworkOption {

	/*
	 * Display for options and meta
	 */
	public function display() {
		$this->echoOptionHeader();

		?>
		<label for="<?php echo $this->getID() ?>">
		<input name="<?php echo $this->getID() ?>" type="checkbox" id="<?php echo $this->getID() ?>" value="1" <?php checked( $this->getValue(), 1 ) ?>>
		<?php echo $this->getDesc( true ) ?>
		</label>
		<?php

		$this->echoOptionFooter( false );
	}

	public function cleanValueForSaving( $value ) {
		return '1' != $value ? '0' : '1';
	}

	public function cleanValueForGetting( $value ) {
		return '1' == $value ? true : false;
	}

	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkCustomizeControl( $wp_customize, $this->getID(), array(
			'label' => $this->settings['name'],
			'section' => $section->settings['id'],
			'settings' => $this->getID(),
			'type' => 'checkbox',
			'description' => $this->settings['desc'],
			'priority' => $priority,
		) ) );
	}

}