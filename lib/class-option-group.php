<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}
class TitanFrameworkOptionGroup extends TitanFrameworkOption {


	/**
	 * The defaults of the settings specific to this option.
	 *
	 * @var array
	 */
	public $defaultSecondarySettings = array(
		'options' => array(),
	);


	/**
	 * Holds the options of this group.
	 *
	 * @var array
	 */
	public $options = array();


	/**
	 * Override the constructor to include the creation of the options within
	 * the group.
	 *
	 * @param array                   $settings The settings of the option.
	 * @param TitanFrameworkAdminPage $owner The owner of the option.
	 */
	function __construct( $settings, $owner ) {
		parent::__construct( $settings, $owner );
		$this->init_group_options();
	}


	/**
	 * Creates the options contained in the group. Mimics how Admin pages
	 * create options.
	 *
	 * @return void
	 */
	public function init_group_options() {
		if ( ! empty( $this->settings['options'] ) ) {

			if ( is_array( $this->settings['options'] ) ) {

				foreach ( $this->settings['options'] as $settings ) {

					if ( ! apply_filters( 'tf_create_option_continue_' . $this->getOptionNamespace(), true, $settings ) ) {
						continue;
					}

					$obj = TitanFrameworkOption::factory( $settings, $this->owner );
					$this->options[] = $obj;

					do_action( 'tf_create_option_' . $this->getOptionNamespace(), $obj );
				}
			}
		}
	}


	/**
	 * Display for options and meta
	 */
	public function display() {

		$this->echoOptionHeader();

		if ( ! empty( $this->options ) ) {
			foreach ( $this->options as $option ) {

				// Display the name of the option.
				$name = $option->getName();
				if ( ! empty( $name ) && ! $option->getHidden() ) {
					echo '<span class="tf-group-name">' . esc_html( $name ) . '</span> ';
				}

				// Disable wrapper printing.
				$option->echo_wrapper = false;

				// Display the option field.
				echo '<span class="tf-group-option">';
				$option->display();
				echo '</span>';
			}
		}

		$this->echoOptionFooter();
	}
}
