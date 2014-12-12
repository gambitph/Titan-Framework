<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class TitanFrameworkOptionColor extends TitanFrameworkOption {

	private static $firstLoad = true;

	public $defaultSecondarySettings = array(
		'placeholder' => '', // show this when blank
	);

	/*
	 * Display for options and meta
	 */
	public function display() {
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );

		$this->echoOptionHeader();

		?>
		<input class="tf-colorpicker" type="text" name="<?php echo $this->getID() ?>" id="<?php echo $this->getID() ?>" value="<?php echo $this->getValue() ?>"  data-default-color="<?php echo $this->getValue() ?>"/>
		<?php

		// load the javascript to init the colorpicker
		if ( self::$firstLoad ):
			?>
			<script>
			jQuery(document).ready(function($) {
				"use strict";
				$('.tf-colorpicker').wpColorPicker();
			});
			</script>
			<?php
		endif;

		$this->echoOptionFooter();

		self::$firstLoad = false;
	}

	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $this->getID(), array(
			'label' => $this->settings['name'],
			'section' => $section->getID(),
			'settings' => $this->getID(),
			'description' => $this->settings['desc'],
			'priority' => $priority,
		) ) );
	}
}