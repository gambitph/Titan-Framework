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
		'date' => true,
		'time' => false,
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
		if ( ! $this->settings['date'] && $this->settings['time'] ) {
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

						// Fix the button styles
						setTimeout( function() {
							jQuery('#ui-datepicker-div')
							.find('[type=button]').addClass('button').end()
							.find('.ui-datepicker-close[type=button]').addClass('button-primary');
						}, 0);
					},

					// Fix the button styles
					onChangeMonthYear: function() {
						setTimeout( function() {
							jQuery('#ui-datepicker-div')
							.find('[type=button]').addClass('button').end()
							.find('.ui-datepicker-close[type=button]').addClass('button-primary');
						}, 0);
					}
				};
			$('.tf-date input[type=text]').each(function() {
				var $this = $(this);
				if ( $this.hasClass('date') && ! $this.hasClass('time') ) {
					$this.datepicker( datepickerSettings );
				} else if ( ! $this.hasClass('date') && $this.hasClass('time') ) {
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
		if ( $this->settings['date'] && ! $this->settings['time'] ) {
			$dateFormat = 'Y-m-d';
			$placeholder = 'YYYY-MM-DD';
		} else if ( ! $this->settings['date'] && $this->settings['time'] ) {
			$dateFormat = 'H:i';
			$placeholder = 'HH:MM';
		}

		printf("<input class=\"input-date%s%s\" name=\"%s\" placeholder=\"%s\" id=\"%s\" type=\"text\" value=\"%s\" /> <p class=\"description\">%s</p>",
			( $this->settings['date'] ? ' date' : '' ),
			( $this->settings['time'] ? ' time' : '' ),
			$this->getID(),
			$placeholder,
			$this->getID(),
			esc_attr( ($this->getValue() > 0) ? date( $dateFormat, $this->getValue() ) : '' ),
			$this->settings['desc']
		);
		$this->echoOptionFooter(false);
	}


	/**
	 * Registers the theme customizer control, for displaying the option
	 *
	 * @param	WP_Customize $wp_enqueue_script The customize object
	 * @param	TitanFrameworkCustomizerSection $section The section where this option will be placed
	 * @param	int $priority The order of this control in the section
	 * @return	void
	 * @since	1.7
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkOptionDateControl( $wp_customize, $this->getID(), array(
			'label' => $this->settings['name'],
			'section' => $section->settings['id'],
			'settings' => $this->getID(),
			'description' => $this->settings['desc'],
			'priority' => $priority,
			'date' => $this->settings['date'],
			'time' => $this->settings['time'],
		) ) );
	}
}


/*
 * We create a new control for the theme customizer
 */
add_action( 'customize_register', 'registerTitanFrameworkOptionDateControl', 1 );


/**
 * Creates the option for the theme customizer
 *
 * @return	void
 * @since	1.3
 */
function registerTitanFrameworkOptionDateControl() {
	class TitanFrameworkOptionDateControl extends WP_Customize_Control {
		public $description;
		public $date;
		public $time;

		public function render_content() {

			TitanFrameworkOptionDate::createCalendarScript();

			$dateFormat = 'Y-m-d H:i';
			$placeholder = 'YYYY-MM-DD HH:MM';
			if ( $this->date && ! $this->time ) {
				$dateFormat = 'Y-m-d';
				$placeholder = 'YYYY-MM-DD';
			} else if ( ! $this->date && $this->time ) {
				$dateFormat = 'H:i';
				$placeholder = 'HH:MM';
			}

			$class = $this->date ? ' date' : '';
			$class .= $this->time ? ' time' : ''
			?>
			<label class='tf-date'>
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
				<input class="input-date<?php echo $class ?>" <?php $this->link(); ?> placeholder="<?php echo $placeholder ?>" type="text" value="<?php echo $this->value() ?>" />

				<?php
				if ( ! empty( $this->description ) ) {
					echo "<p class='description'>{$this->description}</p>";
				}
				?>
			</label>
			<?php
		}
	}
}