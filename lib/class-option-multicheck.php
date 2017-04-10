<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}
class TitanFrameworkOptionMulticheck extends TitanFrameworkOption {

	public $defaultSecondarySettings = array(
		'options' => array(),
		'select_all' => false,
	);

	/**
	 * Constructor
	 *
	 * @param array  $settings Option settings
	 * @param string $owner    Namespace
	 *
	 * @since 1.11
	 */
	function __construct( $settings, $owner ) {
		parent::__construct( $settings, $owner );

		tf_add_action_once( 'admin_enqueue_scripts', array( $this, 'load_select_scripts' ) );
		tf_add_action_once( 'customize_controls_enqueue_scripts', array( $this, 'load_select_scripts' ) );

	}


	/*
	 * Display for options and meta
	 */
	public function display() {
		$this->echoOptionHeader( true );

		echo '<fieldset>';

		$savedValue = $this->getValue();

		if ( ! empty( $this->settings['select_all'] ) ) {
			$select_all_label = __( 'Select All' );
			if ( is_string(  $this->settings['select_all'] ) ) {
				$select_all_label = $this->settings['select_all'];
			}
			printf('<label style="margin-bottom: 1em !important;"><input class="tf_checkbox_selectall" type="checkbox" /> %s </label><br>',
				esc_html( $select_all_label )
			);
		}

		foreach ( $this->settings['options'] as $value => $label ) {

			printf('<label for="%s"><input id="%s" type="checkbox" name="%s[]" value="%s" %s/> %s</label><br>',
				$this->getID() . $value,
				$this->getID() . $value,
				$this->getID(),
				esc_attr( $value ),
				checked( in_array( $value, $savedValue ), true, false ),
				$label
			);
		}

		echo '</fieldset>';

		$this->echoOptionFooter( false );

	}

	/**
	 * Load the multicheck-selectall script
	 *
	 * @since 1.11
	 * @return void
	 */
	public function load_select_scripts() {

		wp_enqueue_script( 'tf-multicheck-select-all', TitanFramework::getURL( '../js/multicheck-select-all.js', __FILE__ ), array( 'jquery' ), TF_VERSION, true );

	}

	public function cleanValueForSaving( $value ) {
		if ( empty( $value ) ) {
			return array();
		}
		if ( is_serialized( $value ) ) {
			return $value;
		}
		// CSV
		if ( is_string( $value ) ) {
			$value = explode( ',', $value );
		}
		return serialize( $value );
	}

	public function cleanValueForGetting( $value ) {
		if ( empty( $value ) ) {
			return array();
		}
		if ( is_array( $value ) ) {
			return $value;
		}
		if ( is_serialized( $value ) ) {
			return unserialize( $value );
		}
		if ( is_string( $value ) ) {
			return explode( ',', $value );
		}
	}

	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkOptionMulticheckControl( $wp_customize, $this->getID(), array(
			'label' => $this->settings['name'],
			'section' => $section->settings['id'],
			'settings' => $this->getID(),
			'description' => $this->settings['desc'],
			'options' => $this->settings['options'],
			'select_all' => $this->settings['select_all'],
			'priority' => $priority,
		) ) );
	}
}


/*
 * WP_Customize_Control with description
 */
add_action( 'customize_register', 'registerTitanFrameworkOptionMulticheckControl', 1 );
function registerTitanFrameworkOptionMulticheckControl() {
	class TitanFrameworkOptionMulticheckControl extends WP_Customize_Control {
		public $description;
		public $options;
		public $select_all;

		private static $firstLoad = true;

		// Since theme_mod cannot handle multichecks, we will do it with some JS
		public function render_content() {
			// the saved value is an array. convert it to csv
			if ( is_array( $this->value() ) ) {
				$savedValueCSV = implode( ',', $this->value() );
				$values = $this->value();
			} else {
				$savedValueCSV = $this->value();
				$values = explode( ',', $this->value() );
			}

			if ( self::$firstLoad ) {
				self::$firstLoad = false;

				?>
				<script>
				jQuery(document).ready(function($) {
					"use strict";

					$('input.tf-multicheck').change(function(event) {
						event.preventDefault();
						var csv = '';

						$(this).parents('li:eq(0)').find('input[type=checkbox]').each(function() {
							if ($(this).is(':checked')) {
								csv += $(this).attr('value') + ',';
							}
						});

						csv = csv.replace(/,+$/, "");

						$(this).parents('li:eq(0)').find('input[type=hidden]').val(csv)
						// we need to trigger the field afterwards to enable the save button
						.trigger('change');
						return true;
					});
				});
				</script>
				<?php
			}

			$description = '';
			if ( ! empty( $this->description ) ) {
				$description = "<p class='description'>" . $this->description . '</p>';
			}
			?>
			<label class='tf-multicheck-container'>
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
				<?php
				echo $description;

				if ( ! empty( $this->select_all ) ) {
					$select_all_label = __( 'Select All' );
					if ( is_string( $this->select_all ) ) {
						$select_all_label = $this->select_all;
					}
					printf('<label style="margin-bottom: 1em !important; display: inline-block;"><input class="tf_checkbox_selectall" type="checkbox" /> %s </label><br>',
						esc_html( $select_all_label )
					);
				}

				foreach ( $this->options as $value => $label ) {
					printf('<label for="%s"><input class="tf-multicheck" id="%s" type="checkbox" value="%s" %s/> %s</label><br>',
						$this->id . $value,
						$this->id . $value,
						esc_attr( $value ),
						checked( in_array( $value, $values ), true, false ),
						$label
					);
				}
				?>
				<input type="hidden" value="<?php echo esc_attr( $savedValueCSV ); ?>" <?php $this->link(); ?> />
			</label>
			<?php
		}
	}
}
