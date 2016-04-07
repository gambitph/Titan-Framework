<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}
class TitanFrameworkCustomizer {

	private $defaultSettings = array(
		'name' => '', // Name of the menu item
		// 'parent' => null, // slug of parent, if blank, then this is a top level menu
		'id' => '', // Unique ID of the menu item
		'panel' => '', // The Name of the panel to create
		'panel_desc' => '', // The description to display on the panel
		'panel_id' => '', // The panel ID to create / add to. If this is blank & `panel` is given, this will be generated
		'capability' => 'edit_theme_options', // User role
		// 'icon' => 'dashicons-admin-generic', // Menu icon for top level menus only
		'desc' => '', // Description
		'position' => 30,// Menu position for top level menus only
	);

	public $settings;
	public $options = array();
	public $owner;

	// Makes sure we only load live previewing CSS only once
	private static $namespacesWithPrintedPreviewCSS = array();

	function __construct( $settings, $owner ) {
		$this->owner = $owner;

		$this->settings = array_merge( $this->defaultSettings, $settings );

		if ( empty( $this->settings['name'] ) ) {
			$this->settings['name'] = __( 'More Options', TF_I18NDOMAIN );
		}

		if ( empty( $this->settings['id'] ) ) {
			$this->settings['id'] = $this->owner->optionNamespace . '_' . str_replace( ' ', '-', trim( strtolower( $this->settings['name'] ) ) );
		}

		if ( empty( $this->settings['panel_id'] ) && ! empty( $this->settings['panel'] ) ) {
			$this->settings['panel_id'] = $this->owner->optionNamespace . '_' . str_replace( ' ', '-', trim( strtolower( $this->settings['panel'] ) ) );
		}

		// Register the customizer control.
		add_action( 'customize_register', array( $this, 'register' ) );

		// Enqueue required customizer styles & scripts.
		tf_add_action_once( 'customize_controls_enqueue_scripts', array( $this, 'loadUploaderScript' ) );

		// Clear local storage, we use it for remembering modified customizer values.
		tf_add_action_once( 'customize_controls_print_footer_scripts', array( $this, 'initLocalStorage' ) );

		// Generate the custom CSS for live previews.
		tf_add_action_once( 'wp_ajax_tf_generate_customizer_css', array( $this, 'ajaxGenerateCustomizerCSS' ) );

		// Modify the values of the options for the generation of CSS with the values from the customizer $_POST.
		global $wp_customize;
		if ( isset( $wp_customize ) ) {
			tf_add_filter_once( 'tf_pre_get_value_' . $this->owner->optionNamespace, array( $this, 'useCustomizerModifiedValue' ), 10, 3 );
		}
	}

	public function loadUploaderScript() {
		wp_enqueue_media();
		wp_enqueue_script( 'tf-theme-customizer-serialize', TitanFramework::getURL( '../js/min/serialize-min.js', __FILE__ ) );
		wp_enqueue_style( 'tf-admin-theme-customizer-styles', TitanFramework::getURL( '../css/admin-theme-customizer-styles.css', __FILE__ ) );
	}

	public function getID() {
		return $this->settings['id'];
	}


	/**
	 * Ajax handler for generating CSS based on the existing options with values changed to
	 * match the customizer modified values.
	 *
	 * @since 1.9.2
	 *
	 * @return void
	 */
	public function ajaxGenerateCustomizerCSS() {

		// This value is passed back to the live preview ajax handler in $this->livePreviewMainScript()
		$generated = array(
			'css' => '',
		);

		foreach ( TitanFramework::getAllInstances() as $framework ) {

			// Modify the values of the options for the generation of CSS with the values from the customizer $_POST.
			$namespace = $framework->optionNamespace;
			add_filter( "tf_pre_get_value_{$namespace}", array( $this, 'useCustomizerModifiedValue' ), 10, 3 );

			// Generate our new CSS based on the customizer values
			$css = $framework->cssInstance->generateCSS();

			$generated['css'] .= $css;

			/**
			 * Allow options to add customizer live preview parameters. The tf_generate_customizer_preview_js hook allows for manipulating these values.
			 *
			 * @since 1.9.2
			 *
			 * @see tf_generate_customizer_preview_js
			 */
			$generated = apply_filters( "tf_generate_customizer_preview_css_{$namespace}", $generated );

		}

		wp_send_json_success( $generated );
	}


	/**
	 * Override the getOption value with the customizer value which comes from the $_POST array
	 *
	 * @since 1.9.2
	 *
	 * @param mixed                $value The value of the option.
	 * @param int                  $postID The post ID if there is one (always null in this case).
	 * @param TitanFrameworkOption $option The option being parsed.
	 *
	 * @return mixed The new value
	 *
	 * @see tf_pre_get_value_{namespace}
	 */
	public function useCustomizerModifiedValue( $value, $postID, $option ) {
		if ( empty( $_POST ) ) {
			return $value;
		}
		if ( ! is_array( $_POST ) ) {
			return $value;
		}
		if ( array_key_exists( $option->getID(), $_POST ) ) {
			return $_POST[ $option->getID() ];
		}

		if ( ! empty( $_POST['customized'] ) ) {
			$customizedSettings = (array) json_decode( stripslashes( $_POST['customized'] ) );
			if ( is_array( $customizedSettings ) && ! empty( $customizedSettings ) ) {
				if ( array_key_exists( $option->getID(), $customizedSettings ) ) {
					return $customizedSettings[ $option->getID() ];
				}
			}
		}
		return $value;
	}


	/**
	 * Prints the script that clears the JS local storage when customizer loads, this ensures we start fresh.
	 * Use localStorage so we can still use values when the customizer refreshes.
	 *
	 * @since 1.9.2
	 *
	 * @return void
	 */
	public function initLocalStorage() {
		?>
		<script>
		if ( typeof localStorage !== 'undefined' ) {
			localStorage.clear();
		}
		</script>
		<?php
	}


	/**
	 * Prints the script that uses ajax to adjust the live customizer CSS.
	 * Use localStorage so we can still use values when the customizer refreshes.
	 *
	 * @since 1.9.2
	 *
	 * @return void
	 */
	public function livePreviewMainScript() {
		?>
		<script>
		window.tf_refresh_css = function() {
			if ( typeof localStorage !== 'undefined' ) {

				// Using localStorage directly as an object-value dictionary doesn't work in FF, create a new object
				var localStorageData = {}, keys = Object.keys( localStorage );
				for ( var i in keys ) {
			        localStorageData[ keys[ i ] ] = localStorage.getItem( keys[ i ] );
			    }

				wp.ajax.send( 'tf_generate_customizer_css', {
				    success: function( data ) {

						// Add the modified CSS Titan has generated from the preview values.
						var style = document.querySelector('style#tf_live_preview');
						if ( ! style ) {
							var style = document.createElement('STYLE');
							style.setAttribute( 'id', 'tf_live_preview' );
							style.innerHTML = data.css;
							document.head.appendChild( style );
						} else {
							style.innerHTML = data.css;
						}

						<?php
						/**
						 * Render additional Javascript code for handling different data received for
						 * live previewing
						 *
						 * @since 1.9.2
						 *
						 * @see $this->ajaxGenerateCustomizerCSS()
						 */
						do_action( 'tf_generate_customizer_preview_js' );
						?>
				    },
					data: localStorageData
				  });

			}
		};
		</script>
		<?php
	}


	/**
	 * Prints the script PER option that handles customizer option changes for live previews
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function livePreview() {

		$printStart = false;
		foreach ( $this->options as $option ) {

			if ( empty( $option->settings['css'] ) && empty( $option->settings['livepreview'] ) ) {
				continue;
			}

			// Print the starting script tag.
			if ( ! $printStart ) {
				$printStart = true;
				?>
				<script>
				jQuery(document).ready(function($) {
				<?php
			}

			?>
			wp.customize( '<?php echo $option->getID() ?>', function( v ) {
				v.bind( function( value ) {
					<?php

					if ( empty( $option->settings['livepreview'] ) ) {

						/**
						 * If css parameter is given and there is no custom livepreview,
						 * we can simulate live previewing using just the css parameter.
						 */
						?>
						if ( typeof localStorage !== 'undefined' ) {
							localStorage.setItem( '<?php echo $option->getID() ?>', value );
						}
						window.tf_refresh_css();
						<?php

					} else {

						/**
						 * If the livepreview parameter is given, use that. This is the original behavior.
						 */
						// Some options may want to insert custom jQuery code before manipulation of live preview/
						if ( ! empty( $option->settings['id'] ) ) {
							do_action( 'tf_livepreview_pre_' . $this->owner->optionNamespace, $option->settings['id'], $option->settings['type'], $option );
						}

						echo $option->settings['livepreview'];

						// Some options may want to insert custom jQuery code after manipulation of live preview.
						if ( ! empty( $option->settings['id'] ) ) {
							do_action( 'tf_livepreview_post_' . $this->owner->optionNamespace, $option->settings['id'], $option->settings['type'], $option );
						}
					}

					?>
				} );
			} );
			<?php

		}

		// Print the ending script tag.
		if ( $printStart ) {
			?>
			});
			</script>
			<?php
		}
	}


	/**
	 * Prints out CSS styles for the current namespace refresh previewing
	 *
	 * @since	1.3
	 *
	 * @return	void
	 *
	 * @see self::$namespacesWithPrintedPreviewCSS
	 */
	public function printPreviewCSS() {

		// Only print the styles once per namespace
		if ( ! in_array( $this->owner->optionNamespace, self::$namespacesWithPrintedPreviewCSS ) ) {
			self::$namespacesWithPrintedPreviewCSS[] = $this->owner->optionNamespace;

			echo '<style id="titan-preview-' . esc_attr( $this->owner->optionNamespace ) . '">';
			echo $this->owner->cssInstance->generateCSS();
			echo '</style>';
		}
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
					'description' => ! empty( $this->settings['panel_desc'] ) ? $this->settings['panel_desc'] : '',
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

				$namespace = $this->owner->optionNamespace;
				$option_type = $option->settings['type'];
				$transport = empty( $option->settings['livepreview'] ) && empty( $option->settings['css'] ) ? 'refresh' : 'postMessage';

				// Allow options to override the transport parameter
				if ( ! empty( $option->settings['transport'] ) ) {
					$transport = $option->settings['transport'];
				}

				/**
				 * Allow options to override the transport mode of an option in the customizer
				 *
				 * @since 1.9.2
				 */
				$transport = apply_filters( "tf_customizer_transport_{$option_type}_{$namespace}", $transport, $option );

				$wp_customize->add_setting( $option->getID(), array(
					'default' => $option->settings['default'],
					'transport' => $transport,
				) );
			}

			// We add the index here, this will be used to order the controls because of this minor bug:
			// https://core.trac.wordpress.org/ticket/20733
			$option->registerCustomizerControl( $wp_customize, $this, $index + 100 );
		}

		add_action( 'wp_footer', array( $this, 'livePreview' ) );
		tf_add_action_once( 'wp_footer', array( $this, 'livePreviewMainScript' ) );
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
