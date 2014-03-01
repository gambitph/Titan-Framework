<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class TitanFrameworkOptionNote extends TitanFrameworkOption {

	public $defaultSecondarySettings = array(
		'color' => 'green', // The color of the note's border
	);

	/*
	 * Display for options and meta
	 */
	public function display() {
		$this->echoOptionHeader();

		$color = $this->settings['color'] == 'green' ? '' : 'error';
		?>
		<div class='updated below-h2 <?php echo $color ?>'><p><?php echo $this->settings['desc'] ?></p></div>
		<?php

		$this->echoOptionFooter( false );
	}
}