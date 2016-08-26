<?php

/**
 * Number Option Class
 *
 * @author Benjamin Intal
 * @package Titan Framework Core
 **/

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}
/**
 * Number Option Class
 *
 * @since	1.0
 **/
class TitanFrameworkOptionNumber extends TitanFrameworkOption {

	// Default settings specific to this option
	public $defaultSecondarySettings = array(
		'size' => 'small', // or medium or large
		'placeholder' => '', // show this when blank
		'min' => 0,
		'max' => 1000,
		'step' => 1,
		'default' => 0,
		'unit' => '',
	);


	/**
	 * Constructor
	 *
	 * @since	1.4
	 */
	function __construct( $settings, $owner ) {
		parent::__construct( $settings, $owner );

		tf_add_action_once( 'admin_enqueue_scripts', array( $this, 'enqueueSlider' ) );
		tf_add_action_once( 'customize_controls_enqueue_scripts', array( $this, 'enqueueSlider' ) );
		add_action( 'admin_head', array( __CLASS__, 'createSliderScript' ) );
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
		return $value;
	}


	/**
	 * Cleans the value for getOption
	 *
	 * @param	string $value The raw value of the option
	 * @return	mixes The cleaned value
	 * @since	1.4
	 */
	public function cleanValueForGetting( $value ) {
		if ( $value == '' ) {
			return 0;
		}
		return $value;
	}


	/**
	 * Enqueues the jQuery UI scripts
	 *
	 * @return	void
	 * @since	1.4
	 */
	public function enqueueSlider() {
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-slider' );
	}


	/**
	 * Prints out the script the initializes the jQuery slider
	 *
	 * @return	void
	 * @since	1.4
	 */
	public static function createSliderScript() {
		?>
		<script>
		jQuery(document).ready(function($) {
			'use strict';

			$( '.tf-number input[type=number]' ).each(function() {
				if ( ! $( this ).prev().is( '.number-slider' ) ) {
					return;
				}
				$( this ).prev().slider( {
					max: Number( $( this ).attr('max') ),
					min: Number( $( this ).attr('min') ),
					step: Number( $( this ).attr('step') ),
					value: Number( $( this ).val() ),
					animate: 'fast',
					change: function( event, ui ) {
						var input = $( ui.handle ).parent().next();
						if ( ui.value !== input.val() ) {
							input.val( ui.value ).trigger( 'change' );
						}
					},
					slide: function( event, ui ) {
						var input = $( ui.handle ).parent().next();
						if ( ui.value !== input.val() ) {
							input.val( ui.value ).trigger( 'change' );
						}
					}
				} ).disableSelection();
			} );
			$( '.tf-number input[type=number]' ).on( 'keyup', _.debounce( function() {
				if ( $( this ).prev().slider( 'value' ).toString() !== $( this ).val().toString() ) {
					$( this ).prev().slider( 'value', $( this ).val() );
				}
			}, 500 ) );
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
		echo "<div class='number-slider'></div>";
		printf('<input class="%s-text" name="%s" placeholder="%s" id="%s" type="number" value="%s" min="%s" max="%s" step="%s" /> %s <p class="description">%s</p>',
			$this->settings['size'],
			$this->getID(),
			$this->settings['placeholder'],
			$this->getID(),
			esc_attr( $this->getValue() ),
			$this->settings['min'],
			$this->settings['max'],
			$this->settings['step'],
			$this->settings['unit'],
			$this->settings['desc']
		);
		$this->echoOptionFooter( false );
	}


	/**
	 * Registers the theme customizer control, for displaying the option
	 *
	 * @param	WP_Customize                    $wp_enqueue_script The customize object
	 * @param	TitanFrameworkCustomizerSection $section The section where this option will be placed
	 * @param	int                             $priority The order of this control in the section
	 * @return	void
	 * @since	1.0
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkOptionNumberControl( $wp_customize, $this->getID(), array(
			'label' => $this->settings['name'],
			'section' => $section->getID(),
			'settings' => $this->getID(),
			'description' => $this->settings['desc'],
			'priority' => $priority,
			'size' => $this->settings['size'],
			'min' => $this->settings['min'],
			'max' => $this->settings['max'],
			'step' => $this->settings['step'],
			'unit' => $this->settings['unit'],
		) ) );
	}
}



/*
 * We create a new control for the theme customizer
 */
add_action( 'customize_register', 'registerTitanFrameworkOptionNumberControl', 1 );


/**
 * Creates the option for the theme customizer
 *
 * @return	void
 * @since	1.0
 */
function registerTitanFrameworkOptionNumberControl() {
	class TitanFrameworkOptionNumberControl extends WP_Customize_Control {
		public $description;
		public $size;
		public $min;
		public $max;
		public $step;
		public $unit;

		private static $firstLoad = true;

		public function render_content() {
			// Print out the jQuery slider initializer
			if ( self::$firstLoad ) {
				TitanFrameworkOptionNumber::createSliderScript();
			}
			self::$firstLoad = false;

			?>
			<label class='tf-number'>
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
				<span class='number-slider'></span>
				<input class="<?php echo esc_attr( $this->size ) ?>-text" min="<?php echo esc_attr( $this->min ) ?>" max="<?php echo esc_attr( $this->max ) ?>" step="<?php echo esc_attr( $this->step ) ?>" type="number" value="<?php echo esc_attr( $this->value() ); ?>" <?php $this->link(); ?> />
				<?php echo esc_html( $this->unit ) ?>
			</label>
			<?php
			if ( ! empty( $this->description ) ) {
				echo "<p class='description'>{$this->description}</p>";
			}
		}
	}
}
