<?php

/**
 * Date Option Class
 *
 * @author Ardalan Naghshineh (www.ardalan.me)
 * @package Titan Framework Core
 **/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Date Option Class
 *
 * @since	1.0
 **/
class TitanFrameworkOptionDate extends TitanFrameworkOption {

	// Default settings specific to this option
	public $defaultSecondarySettings = array(
		'default' => 0,
		'dateonly' => false,
		'timeonly' => false
	);

	private static $date_epoch;

	/**
	 * Constructor
	 *
	 * @since	1.4
	 */
	function __construct( $settings, $owner ) {
		parent::__construct( $settings, $owner );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueDatepicker' ) );
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'enqueueDatepicker' ) );
		add_action( 'admin_head', array( __CLASS__, 'createCalendarScript' ) );

		if ( empty( self::$date_epoch ) ) {
			self::$date_epoch = date( 'Y-m-d', 0 );
		}
	}


	/**
	 * Cleans up the serialized value before saving
	 *
	 * @param	string $value The serialized value
	 * @return	string The cleaned value
	 * @since	1.4
	 */
	public function cleanValueForSaving( $value ) {
		if ( $value == '' ) {
			return 0;
		}
		if ( ! $this->settings['dateonly'] AND $this->settings['timeonly'] ) {
			$value = self::$date_epoch . ' ' . $value;
		}
		return strtotime( $value );
	}


	/**
	 * Cleans the value for getOption
	 *
	 * @param	string $value The raw value of the option
	 * @return	mixes The cleaned value
	 * @since	1.4
	 */
	public function cleanValueForGetting( $value ) {
		if ( $value == 0 ) {
			return '';
		}
		return $value;
	}


	/**
	 * Enqueues the jQuery UI scripts
	 *
	 * @return	void
	 * @since	1.4
	 */
	public function enqueueDatepicker() {
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-slider' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'tf-jquery-ui-timepicker-addon', TitanFramework::getURL( 'js/jquery-ui-timepicker-addon.js', __FILE__ ), array( 'jquery-ui-datepicker', 'jquery-ui-slider' ) );
	}


	/**
	 * Prints out the script the initializes the jQuery Datepicker
	 *
	 * @return	void
	 * @since	1.4
	 */
	public static function createCalendarScript() {
		?>
		<script>
		jQuery(document).ready(function($) {
			"use strict";

			var datepickerSettings = {
					dateFormat: 'yy-mm-dd',
					beforeShow: function(input, inst) {
						$('#ui-datepicker-div').addClass('tf-date-datepicker');
					}
				};
			$('.tf-date input[type=text]').each(function() {
				var $this = $(this);
				if ( $this.hasClass('dateonly') ) {
					$this.datepicker( datepickerSettings );
				} else if ( $this.hasClass('timeonly') ) {
					$this.timepicker( datepickerSettings );
				} else {
					$this.datetimepicker( datepickerSettings );
				}
			});
		});
		</script>
		<?php
	}


	/**
	 * Displays the option for admin pages and meta boxes
	 *
	 * @return	void
	 * @since	1.0
	 */
	public function display() {
		$this->echoOptionHeader();
		$dateFormat = 'Y-m-d H:i';
		$placeholder = 'YYYY-MM-DD HH:MM';
		if ( $this->settings['dateonly'] ) {
			$dateFormat = 'Y-m-d';
			$placeholder = 'YYYY-MM-DD';
		} else if ( $this->settings['timeonly'] ) {
			$dateFormat = 'H:i';
			$placeholder = 'HH:MM';
		}
		printf("<input class=\"input-date%s%s\" name=\"%s\" placeholder=\"%s\" id=\"%s\" type=\"text\" value=\"%s\" /> <p class=\"description\">%s</p>",
			( $this->settings['dateonly'] ? ' dateonly' : '' ),
			( $this->settings['timeonly'] ? ' timeonly' : '' ),
			$this->getID(),
			$placeholder,
			$this->getID(),
			esc_attr( ($this->getValue() > 0) ? date( $dateFormat, $this->getValue() ) : '' ),
			$this->settings['desc']
		);
		$this->echoOptionFooter(false);
	}
}

