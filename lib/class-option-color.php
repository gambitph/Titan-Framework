<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}
class TitanFrameworkOptionColor extends TitanFrameworkOption {

	/**
	 * Default settings
	 * @var array
	 */
	public $defaultSecondarySettings = array(

		/**
		 * (Optional) If true, an additional control will become available in the color picker for adjusting the alpha/opacity value of the color. You can get rgba colors with the option.
		 *
		 * @since 1.9
		 * @var boolean
		 */
		'alpha' => false,
	);

	function __construct( $settings, $owner ) {
		parent::__construct( $settings, $owner );
		tf_add_action_once( 'admin_enqueue_scripts', array( $this, 'enqueueColorPickerScript' ) );
		tf_add_action_once( 'admin_footer', array( $this, 'startColorPicker' ) );
	}


	/**
	 * Display for options and meta
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function display() {

		$this->echoOptionHeader();

		printf( '<input class="tf-colorpicker" type="text" name="%s" id="%s" value="%s" data-default-color="%s" data-custom-width="0" %s/>',
			esc_attr( $this->getID() ),
			esc_attr( $this->getID() ),
			esc_attr( $this->getValue() ),
			esc_attr( $this->getValue() ),
			! empty( $this->settings['alpha'] ) ? "data-alpha='true'" : '' // Used by wp-color-picker-alpha
		);

		$this->echoOptionFooter();
	}


	/**
	 * Enqueue the colorpicker scripts
	 *
	 * @since 1.9
	 *
	 * @return void
	 */
	public function enqueueColorPickerScript() {
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker-alpha', TitanFramework::getURL( '../js/min/wp-color-picker-alpha-min.js', __FILE__ ), array( 'wp-color-picker' ), TF_VERSION );
	}


	/**
	 * Load the javascript to init the colorpicker
	 *
	 * @since 1.9
	 *
	 * @return void
	 */
	public function startColorPicker() {
		?>
		<script>
		jQuery(document).ready(function($) {
			"use strict";
			$('.tf-colorpicker').wpColorPicker();
		});
		</script>
		<?php
	}


	/**
	 * Display for theme customizer
	 *
	 * @since 1.0
	 *
	 * @param WP_Customize             $wp_customize
	 * @param TitanFrameworkCustomizer $section
	 * @param int                      $priority The location/priority of this option inside the section.
	 *
	 * @return void
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkOptionColorControl( $wp_customize, $this->getID(), array(
			'label' => $this->settings['name'],
			'section' => $section->getID(),
			'settings' => $this->getID(),
			'description' => $this->settings['desc'],
			'priority' => $priority,
			'alpha' => $this->settings['alpha'],
		) ) );
	}
}


add_action( 'customize_register', 'registerTitanFrameworkOptionColorControl', 1 );


function registerTitanFrameworkOptionColorControl() {
	class TitanFrameworkOptionColorControl extends WP_Customize_Color_Control {

		public $alpha;

		function __construct( $manager, $id, $args = array() ) {
			parent::__construct( $manager, $id, $args );
			tf_add_action_once( 'customize_controls_print_footer_scripts', array( $this, 'printOpacityOverrideTemplate' ) );
		}

		public function enqueue() {
			parent::enqueue();
			wp_enqueue_script( 'wp-color-picker-alpha', TitanFramework::getURL( '../js/min/wp-color-picker-alpha-min.js', __FILE__ ), array( 'wp-color-picker' ), TF_VERSION );
		}

		public function to_json() {
			parent::to_json();
			$this->json['alpha'] = $this->alpha;
		}

		public function printOpacityOverrideTemplate() {

			// Get the template for the color control, don't print it out.
			ob_start();
			$this->content_template();
			$contents = ob_get_contents();
			ob_end_clean();

			// Modify the template to include our alpha parameter.
			$contents = str_replace( '<input', '<input {{ alpha }} data-custom-width="0"', $contents );
			?>
			<script type="text/html" id="tmpl-customize-control-color-content-tf">
			<#
			var alpha = '';
			if ( data.alpha ) {
				alpha = ' data-alpha=true'; // Quotes added automatically.
			}
			#>
			<?php echo $contents ?>
			</script>
			<?php

			// Override the original color content template. Don't use jQuery here since it will be too late
			// We're sure that the templates have already been printed out
			// We can't modify the original output, so we just adjust the ID so that we won't use the original one
			?>
			<script>
			document.querySelector("#tmpl-customize-control-color-content").setAttribute("id", "tmpl-customize-control-color-content-old");
			document.querySelector("#tmpl-customize-control-color-content-tf").setAttribute("id", "tmpl-customize-control-color-content");
			</script>
			<?php
		}
	}
}
