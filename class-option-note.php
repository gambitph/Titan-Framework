<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class TitanFrameworkOptionNote extends TitanFrameworkOption {

	public $defaultSecondarySettings = array(
		'color' => 'green', // The color of the note's border
		'notification' => false,
		'paragraph' => true,
	);

	/*
	 * Display for options and meta
	 */
	public function display() {
		$this->echoOptionHeader();

		$color = $this->settings['color'] == 'green' ? '' : 'error';

		if ( $this->settings['notification'] ) {
			?><div class='updated below-h2 <?php echo $color ?>'><?php
		}

		if ( $this->settings['paragraph'] ) {
			echo "<p class='description'>";
		}

		echo $this->settings['desc'];

		if ( $this->settings['paragraph'] ) {
			echo "</p>";
		}

		if ( $this->settings['notification'] ) {
			?></div><?php
		}

		$this->echoOptionFooter( false );
	}
}