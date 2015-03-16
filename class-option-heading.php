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
}