<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class TitanFrameworkThemeCustomizerSection {

	private $defaultSettings = array(
		'name' => '', // Name of the menu item
		// 'parent' => null, // slug of parent, if blank, then this is a top level menu
		'id' => '', // Unique ID of the menu item
		'panel' => '', // The Name of the panel to create
		'panel_id' => '', // The panel ID to create / add to. If this is blank & `panel` is given, this will be generated
		'capability' => 'edit_theme_options', // User role
		// 'icon' => 'dashicons-admin-generic', // Menu icon for top level menus only
		'desc' => '', // Description
		'position' => 30 // Menu position for top level menus only
	);

	public $settings;
	public $options = array();
	public $owner;

	// Makes sure we only load live previewing CSS only once
	private static $generatedHeadCSSPreview = false;

	function __construct( $settings, $owner ) {
		$this->owner = $owner;

		$this->settings = array_merge( $this->defaultSettings, $settings );

		if ( empty( $this->settings['name'] ) ) {
			$this->settings['name'] = __( "More Options", TF_I18NDOMAIN );
		}

		if ( empty( $this->settings['id'] ) ) {
			$this->settings['id'] = str_replace( ' ', '-', trim( strtolower( $this->settings['name'] ) ) );
		}

		if ( empty( $this->settings['panel_id'] ) ) {
			$this->settings['panel_id'] = str_replace( ' ', '-', trim( strtolower( $this->settings['panel'] ) ) );
		}

		add_action( 'customize_register', array( $this, 'register' ) );
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'loadUploaderScript' ) );
	}

	public function loadUploaderScript() {
		wp_enqueue_media();
		wp_enqueue_script( 'tf-theme-customizer-serialize', TitanFramework::getURL( 'js/serialize.js', __FILE__ ) );
		wp_enqueue_style( 'tf-admin-theme-customizer-styles', TitanFramework::getURL( 'css/admin-theme-customizer-styles.css', __FILE__ ) );
	}

	public function getID() {
		return $this->settings['id'];
	}

	public function livePreview() {
		?>
		<script>
		jQuery(document).ready(function($) {
			<?php
			foreach ( $this->options as $option ):
				if ( empty( $option->settings['livepreview'] ) ):
					continue;
				endif;
				?>
				wp.customize( '<?php echo $option->getID() ?>', function( v ) {
					v.bind( function( value ) {
						<?php

						// Some options may want to insert custom jQuery code before manipulation of live preview
						if ( ! empty( $option->settings['id'] ) ) {
							do_action( 'tf_livepreview_pre_' . $this->owner->optionNamespace, $option->settings['id'], $option->settings['type'], $option );
						}

						echo $option->settings['livepreview'];
						?>
					} );
				} );
				<?php
			endforeach;
			?>
		});
		</script>
		<?php
	}


	/**
	 * Prints out CSS styles for refresh previewing
	 *
	 * @return	void
	 * @since	1.3
	 */
	public function printPreviewCSS() {
		if ( self::$generatedHeadCSSPreview ) {
			return;
		}
		self::$generatedHeadCSSPreview = true;
		echo "<style>" . $this->owner->cssInstance->generateCSS() . "</style>";
	}

	public function register( $wp_customize ) {
		add_action( 'wp_head', array( $this, 'printPreviewCSS' ), 1000 );

		// Create the panel
		if ( ! empty( $this->settings['panel_id'] ) ) {
			$existingPanels = $wp_customize->panels();

			if ( ! array_key_exists( $this->settings['panel_id'], $existingPanels ) ) {
				$wp_customize->add_panel( $this->settings['panel_id'], array(
					'title' => $this->settings['panel'],
					'priority' => $this->settings['position'],
					'capability' => $this->settings['capability'],
				) );
			}
		}

		// Create the section
		$existingSections = $wp_customize->sections();

		if ( ! array_key_exists( $this->settings['id'], $existingSections ) ) {
			$wp_customize->add_section( $this->settings['id'], array(
				'title' => $this->settings['name'],
				'priority' => $this->settings['position'],
				'description' => $this->settings['desc'],
				'capability' => $this->settings['capability'],
				'panel' => empty( $this->settings['panel_id'] ) ? '' : $this->settings['panel_id'],
			) );
		}

		// Unfortunately we have to call each option's register from here
		foreach ( $this->options as $index => $option ) {
			if ( ! empty( $option->settings['id'] ) ) {
				$wp_customize->add_setting( $option->getID() , array(
					'default' => $option->settings['default'],
					'transport' => empty( $option->settings['livepreview'] ) ? 'refresh' : 'postMessage',
				) );
			}

			// We add the index here, this will be used to order the controls because of this minor bug:
			// https://core.trac.wordpress.org/ticket/20733
			$option->registerCustomizerControl( $wp_customize, $this, $index + 100 );
		}

		add_action( 'wp_footer', array( $this, 'livePreview' ) );
	}

	public function createOption( $settings ) {
		if ( ! apply_filters( 'tf_create_option_continue_' . $this->owner->optionNamespace, true, $settings ) ) {
			return null;
		}

		$obj = TitanFrameworkOption::factory( $settings, $this );
		$this->options[] = $obj;

		do_action( 'tf_create_option_' . $this->owner->optionNamespace, $obj );

		return $obj;
	}
}