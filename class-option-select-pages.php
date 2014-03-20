<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class TitanFrameworkOptionSelectPages extends TitanFrameworkOption {

	public $defaultSecondarySettings = array(
		'default' => '0', // show this when blank
	);

	private static $allPages;

	/*
	 * Display for options and meta
	 */
	public function display() {
		$this->echoOptionHeader();

		// Remember the pages so as not to perform any more lookups
		if ( ! isset( self::$allPages ) ) {
			self::$allPages = get_pages();
		}

		echo "<select name='" . esc_attr( $this->getID() ) . "'>";

		// The default value (nothing is selected)
		printf( "<option value='%s' %s>%s</option>",
			'0',
			selected( $this->getValue(), '0', false ),
			"— " . __( "Select", TF_I18NDOMAIN ) . " —"
		);

		// Print all the other pages
		foreach ( self::$allPages as $page ) {
			printf( "<option value='%s' %s>%s</option>",
				esc_attr( $page->ID ),
				selected( $this->getValue(), $page->ID, false ),
				$page->post_title
			);
		}
		echo "</select>";

		$this->echoOptionFooter();
	}

	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkCustomizeControl( $wp_customize, $this->getID(), array(
			'label' => $this->settings['name'],
			'section' => $section->settings['id'],
			'settings' => $this->getID(),
			'type' => 'dropdown-pages',
			'description' => $this->settings['desc'],
			'priority' => $priority,
		) ) );
	}
}