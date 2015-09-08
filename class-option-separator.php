<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}
class TitanFrameworkOptionSeparator extends TitanFrameworkOption {

	/*
	 * Display for options and meta
	 */
	public function display() {
		$this->echoOptionHeader();
		?>
		<hr />
		<?php
		$this->echoOptionFooter( false );
	}
}
