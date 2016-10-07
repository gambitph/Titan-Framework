<?php
/**
 * Font Option Class
 *
 * @since	1.4
 */

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}
/**
 * Font Option Class
 *
 * @since	1.4
 */
class TitanFrameworkOptionFont extends TitanFrameworkOption {

	// Default settings specific to this option
	public $defaultSecondarySettings = array(
		'show_font_family' => true,
		'show_color' => true,
		'show_font_size' => true,
		'show_font_weight' => true,
		'show_font_style' => true,
		'show_line_height' => true,
		'show_letter_spacing' => true,
		'show_text_transform' => true,
		'show_font_variant' => true,
		'show_text_shadow' => true,
		'show_preview' => true,
		'enqueue' => true,
		'preview_text' => '',
		'include_fonts' => '', // A regex string or array of regex strings to match font names to include.
		'show_websafe_fonts' => true,
		'show_google_fonts' => true,
		'fonts' => array(),
	);

	// Default style options
	public static $defaultStyling = array(
		'font-family' => 'inherit',
		'color' => '#333333',
		'font-size' => 'inherit',
		'font-weight' => 'inherit',
		'font-style' => 'normal',
		'line-height' => '1.5em',
		'letter-spacing' => 'normal',
		'text-transform' => 'none',
		'font-variant' => 'normal',
		'text-shadow-location' => 'none',
		'text-shadow-distance' => '0px',
		'text-shadow-blur' => '0px',
		'text-shadow-color' => '#333333',
		'text-shadow-opacity' => '1',
		'font-type' => 'google', // Only used internally to determine if the font is a
		'dark' => '', // only used to toggle the preview background
	);

	// The list of web safe fonts
	public static $webSafeFonts = array(
		'Arial, Helvetica, sans-serif' => 'Arial',
		'"Arial Black", Gadget, sans-serif' => 'Arial Black',
		'"Comic Sans MS", cursive, sans-serif' => 'Comic Sans',
		'"Courier New", Courier, monospace' => 'Courier New',
		'Georgia, serif' => 'Geogia',
		'Impact, Charcoal, sans-serif' => 'Impact',
		'"Lucida Console", Monaco, monospace' => 'Lucida Console',
		'"Lucida Sans Unicode", "Lucida Grande", sans-serif' => 'Lucida Sans',
		'"Palatino Linotype", "Book Antiqua", Palatino, serif' => 'Palatino',
		'Tahoma, Geneva, sans-serif' => 'Tahoma',
		'"Times New Roman", Times, serif' => 'Times New Roman',
		'"Trebuchet MS", Helvetica, sans-serif' => 'Trebuchet',
		'Verdana, Geneva, sans-serif' => 'Verdana',
	);

	// Holds all the options with Google Fonts for enqueuing.
	// We need to do this since we want to gather all the fonts first then enqueue only the unique fonts
	private static $optionsToEnqueue = array();


	/**
	 * Constructor
	 *
	 * @return	void
	 * @since	1.4
	 */
	function __construct( $settings, $owner ) {
		parent::__construct( $settings, $owner );

		tf_add_action_once( 'admin_enqueue_scripts', array( $this, 'loadAdminScripts' ) );
		tf_add_action_once( 'customize_controls_enqueue_scripts', array( $this, 'loadAdminScripts' ) );
		tf_add_action_once( 'admin_head', array( __CLASS__, 'createFontScript' ) );
		tf_add_action_once( 'wp_enqueue_scripts', array( $this, 'enqueueGooglefonts' ) );
		add_filter( 'tf_generate_css_font_' . $this->getOptionNamespace(), array( $this, 'generateCSS' ), 10, 2 );

		// Customizer preview handling
		tf_add_action_once( 'tf_generate_customizer_preview_js', array( $this, 'generateCustomizerPreviewJS' ) );
		tf_add_filter_once( 'tf_generate_customizer_preview_css_' . $this->getOptionNamespace(), array( $this, 'generateCustomizerPreviewCSS' ) );

		if ( $this->settings['enqueue'] ) {
			self::$optionsToEnqueue[] = $this;
		}
	}


	/**
	 * Adds the Javascript code that adds Google fonts straight into the customizer preview.
	 *
	 * @since 1.9.2
	 *
	 * @return void
	 *
	 * @see TitanFrameworkCustomizer->livePreviewMainScript()
	 */
	public function generateCustomizerPreviewJS() {
		?>
		for ( var fontName in data.google_fonts ) {
			if ( document.querySelector( '#tf-preview-' + fontName ) ) {
				continue;
			}
			var link = document.createElement('LINK');
			link.setAttribute( 'rel', 'stylesheet' );
			link.setAttribute( 'type', 'text/css' );
			link.setAttribute( 'media', 'all' );
			link.setAttribute( 'id', 'tf-preview' + fontName );
			link.setAttribute( 'href', data.google_fonts[ fontName ] );
			document.head.appendChild( link );
		}
		<?php
	}


	/**
	 * Adds the list of all Google fonts into the customizer live preview
	 *
	 * @since 1.9.2
	 *
	 * @param array $generated The parameters to pass to the ajax handler during customizer live previews.
	 *
	 * @return array An array containing modified ajax values to pass
	 */
	public function generateCustomizerPreviewCSS( $generated ) {
		if ( empty( $generated['google_fonts'] ) ) {
			$generated['google_fonts'] = array();
		}
		$generated['google_fonts'] = array_merge( $generated['google_fonts'], $this->getGoogleFontURLs() );
		return $generated;
	}


	/**
	 * Gets all the Google font URLs for enqueuing. This was previously inside $this->enqueueGooglefonts()
	 * but was split off so it can be used by other functions.
	 *
	 * @since 1.9.2
	 *
	 * @return array An array containing the font names as keys and the font URLs as values.
	 */
	public function getGoogleFontURLs() {

		$urls = array();

		// Gather all the fonts that we need to load, some may be repeated so we need to
		// load them once after gathering them
		$fontsToLoad = array();
		foreach ( self::$optionsToEnqueue as $option ) {
			$fontValue = $option->getValue();

			if ( empty( $fontValue['font-family'] ) ) {
				continue;
			}
			if ( $fontValue['font-family'] == 'inherit' ) {
				continue;
			}

			if ( $fontValue['font-type'] != 'google' ) {
				continue;
			}
			// Stop load Custom Fonts
			if ( in_array($fontValue['font-family'],$this->settings['fonts']) ) {
				continue;
			}

			// Get all the fonts that we need to load
			if ( empty( $fontsToLoad[ $fontValue['font-family'] ] ) ) {
				$fontsToLoad[ $fontValue['font-family'] ] = array();
			}

			// Get the weight
			$variant = $fontValue['font-weight'];
			if ( $variant == 'normal' ) {
				$variant = '400';
			} else if ( $variant == 'bold' ) {
				$variant = '500';
			} else if ( $variant == 'bolder' ) {
				$variant = '800';
			} else if ( $variant == 'lighter' ) {
				$variant = '100';
			}

			if ( $fontValue['font-style'] == 'italic' ) {
				$variant .= 'italic';
			}

			$fontsToLoad[ $fontValue['font-family'] ][] = $variant;
		}

		// Font subsets, allow others to change this
		$subsets = apply_filters( 'tf_google_font_subsets_' . $this->getOptionNamespace(), array( 'latin', 'latin-ext' ) );

		// Enqueue the Google Font
		foreach ( $fontsToLoad as $fontName => $variants ) {

			// Always include the normal weight so that we don't error out
			$variants[] = '400';
			$variants = array_unique( $variants );

			$fontUrl = sprintf( '//fonts.googleapis.com/css?family=%s:%s&subset=%s',
				str_replace( ' ', '+', $fontName ),
				implode( ',', $variants ),
				implode( ',', $subsets )
			);

			$fontUrl = apply_filters( 'tf_enqueue_google_webfont_' . $this->getOptionNamespace(), $fontUrl, $fontName );

			if ( $fontUrl != false ) {
				$urls[ $fontName ] = $fontUrl;
			}
		}

		return $urls;
	}


	/**
	 * Enqueues all the Google fonts, used in wp_enqueue_scripts
	 *
	 * @since	1.4
	 *
	 * @return	void
	 */
	public function enqueueGooglefonts() {
		$urls = $this->getGoogleFontURLs();

		foreach ( $urls as $fontName => $url ) {
			wp_enqueue_style( 'tf-google-webfont-' . strtolower( str_replace( ' ', '-', $fontName ) ), $url );
		}
	}


	/**
	 * Generates CSS for the font, this is used in TitanFrameworkCSS
	 *
	 * @param	string               $css The CSS generated
	 * @param	TitanFrameworkOption $option The current option being processed
	 * @return	string The CSS generated
	 * @since	1.4
	 */
	public function generateCSS( $css, $option ) {
		if ( $this->settings['id'] != $option->settings['id'] ) {
			return $css;
		}

		$skip = array( 'dark', 'font-type', 'text-shadow-distance', 'text-shadow-blur', 'text-shadow-color', 'text-shadow-opacity' );

		// If the value is blank, use the defaults
		$value = $this->getValue();
		$value = array_merge( self::$defaultStyling, $value );

		foreach ( $value as $key => $val ) {

			// Force skip other keys, those are processed under another key, e.g. text-shadow-distance is
			// used by text-shadow-location
			if ( in_array( $key, $skip ) ) {
				continue;
			}

			// Don't include keys which are not in the default styles
			if ( ! in_array( $key, array_keys( self::$defaultStyling ) ) ) {
				continue;
			}

			if ( $key == 'font-family' ) {
				if ( $value[ $key ] == 'inherit' ) {
					$css .= '$' . $option->settings['id'] . '-' . $key . ': ' . $value[ $key ] . ';';
					continue;
				}
				if ( ! empty( $value['font-type'] ) ) {
					if ( $value['font-type'] == 'google' ) {
						$css .= '$' . $option->settings['id'] . '-' . $key . ': "' . $value[ $key ] . '";';
						continue;
					}
				}
				$css .= '$' . $option->settings['id'] . '-' . $key . ': ' . $value[ $key ] . ';';
				continue;
			}

			if ( $key == 'text-shadow-location' ) {
				$textShadow = '';
				if ( $value[ $key ] != 'none' ) {
					if ( stripos( $value[ $key ], 'left' ) !== false ) {
						$textShadow .= '-' . $value['text-shadow-distance'];
					} else if ( stripos( $value[ $key ], 'right' ) !== false ) {
						$textShadow .= $value['text-shadow-distance'];
					} else {
						$textShadow .= '0';
					}
					$textShadow .= ' ';
					if ( stripos( $value[ $key ], 'top' ) !== false ) {
						$textShadow .= '-' . $value['text-shadow-distance'];
					} else if ( stripos( $value[ $key ], 'bottom' ) !== false ) {
						$textShadow .= $value['text-shadow-distance'];
					} else {
						$textShadow .= '0';
					}
					$textShadow .= ' ';
					$textShadow .= $value['text-shadow-blur'];
					$textShadow .= ' ';

					$rgb = tf_hex2rgb( $value['text-shadow-color'] );
					$rgb[] = $value['text-shadow-opacity'];

					$textShadow .= 'rgba(' . implode( ',', $rgb ) . ')';
				} else {
					$textShadow .= $value[ $key ];
				}

				$css .= '$' . $option->settings['id'] . '-text-shadow: ' . $textShadow . ';';
				continue;
			}

			$css .= '$' . $option->settings['id'] . '-' . $key . ': ' . $value[ $key ] . ';';
		}

		/*
		 * There are 2 ways to include the values for the CSS. The normal `value-arraykey`, or just `value`
		 * Using `value` will print out the entire font CSS.
		 */

		// Create the entire CSS for the font, this should just be used to replace the `value` variable.
		$cssVariables = '';
		$cssChecking = array( 'font_family', 'color', 'font_size', 'font_weight', 'font_style', 'line_height', 'letter_spacing', 'text_transform', 'font_variant', 'text_shadow' );

		// Enter values that are not marked as false.
		foreach ( $cssChecking as $subject ) {
			if ( $option->settings[ 'show_'.$subject ] ) {
				$cssVariableArray[] = str_replace( '_', '-', $subject );
			}
		}

		// Now, integrate these values with their corresponding keys.
		foreach ( $cssVariableArray as $param ) {
			$cssVariables .= $param . ': $' . $option->settings['id'] . '-' . $param . ";\n";
		}

		// Replace the `value` parameters in the given css.
		$modifiedCss = '';
		if ( ! empty( $option->settings['css'] ) ) {
			$modifiedCss = $option->settings['css'];

			// If `value` is given, replace it with the entire css we created above in $cssVariables.
			$modifiedCss = preg_replace( '/value[^-]/', $cssVariables, $modifiedCss );

			// Normal `value-arraykey` values.
			$modifiedCss = str_replace( 'value-', '$' . $option->settings['id'] . '-', $modifiedCss );
		}

		$css .= $modifiedCss;

		return $css;
	}


	/**
	 * Enqueues the needed scripts for the admin
	 *
	 * @return	void
	 * @since	1.4
	 */
	public function loadAdminScripts() {
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );
	}


	/**
	 * Creates the Javascript for running the font option
	 *
	 * @return	void
	 * @since	1.4
	 */
	public static function createFontScript() {

		?>
		<script>
		jQuery(document).ready(function($) {
			"use strict";

			var _tf_select_font_throttle = null;

			// Initialize color pickers
			$('.tf-font .tf-font-sel-color, .tf-font .tf-font-sel-shadow-color').wpColorPicker({
				change: function ( event, ui ) {
					// update the preview, but throttle it to prevent fast loading
					if ( _tf_select_font_throttle != null ) {
						clearTimeout( _tf_select_font_throttle );
						_tf_select_font_throttle = null;
					}
					var $this = $(this);
					_tf_select_font_throttle = setTimeout( function() {
						tf_select_font_update_preview( $this.parents('.tf-font:eq(0)'), true );
					}, 300 );
				}
			});


			// Initialize the option
			$('.tf-font').each(function() {

				// Update save field on change
				$(this).find('select,.tf-font-sel-dark').change(function() {
					tf_select_font_update_preview( $(this).parents('.tf-font:eq(0)'), true );
				});

				// Trigger for toggling light/dark preview backgrounds
				$(this).find('.btn-dark').click(function() {
					var darkInput = $(this).parent().find('.tf-font-sel-dark');
					if ( darkInput.val() == '' ) {
						darkInput.val('dark').trigger('change');
					} else {
						darkInput.val('').trigger('change');
					}
				})

				// initialize preview
				tf_select_font_update_preview( $(this), true );

				// We have to do this after 1ms for the theme customizer, or else the field's value
				// gets changed to a weird value
				var $this = $(this);
				setTimeout( function() {
					tf_select_font_update_preview( $this, false )
				}, 1 );
			});


			/**
			 * Theme Customizer scripts
			 */

			// Check for font selector clicks, we need to adjust styles to make it look nice
			$('body.wp-customizer .tf-font').on('mouseup', function(e) {
				if ( $(e.target).is('.wp-color-result') ) {
					if ( ! $(e.target).is('.wp-picker-open') ) {
						$(e.target).parents('label:eq(0)').addClass('tf-picker-open');
					} else {
						$(e.target).parents('label:eq(0)').removeClass('tf-picker-open');
					}
				}
			});

			// Check for close clicks (clicking outside while the picker is open)
			$('body.wp-customizer').on('mouseup', '*', function(e) {
				var $target = $(e.target);
				if ( $target.is('.wp-color-result, .wp-color-picker, .wp-picker-default') ) {
					return;
				}
				if ( $target.parents('.wp-picker-holder').length > 0 ) {
					return;
				}
				if ( $('.tf-picker-open').length > 0 ) {
					$('.tf-picker-open').removeClass('tf-picker-open');
				}
			});
		});


		// Updates the option elements
		function tf_select_font_update_preview( $container, doTrigger ) {
			"use strict";
			var $ = jQuery;

			// Show / hide shadow fields
			if ( $container.find(".tf-font-sel-location").val() == 'none'
				 || $container.find('.tf-font-sel-location').parents('label:eq(0)').attr('data-visible') == 'false' ) {
				$container.find(".tf-font-sel-distance").parents('label:eq(0)').fadeOut();
				$container.find(".tf-font-sel-blur").parents('label:eq(0)').fadeOut();
				$container.find(".tf-font-sel-shadow-color").parents('label:eq(0)').fadeOut();
				$container.find(".tf-font-sel-opacity").parents('label:eq(0)').fadeOut();
			} else {
				$container.find(".tf-font-sel-distance").parents('label:eq(0)').fadeIn();
				$container.find(".tf-font-sel-blur").parents('label:eq(0)').fadeIn();
				$container.find(".tf-font-sel-shadow-color").parents('label:eq(0)').fadeIn();
				$container.find(".tf-font-sel-opacity").parents('label:eq(0)').fadeIn();
			}

			var family = $container.find('.tf-font-sel-family').val();

			// These are all our parameters
			var params = {
				'font-family': family,
				'font-type': $container.find(".tf-font-sel-family option[value='" + family + "']").parent().attr('class'),
				'color': $container.find(".tf-font-sel-color").val(),
				'font-size': $container.find(".tf-font-sel-size").val(),
				'font-weight': $container.find(".tf-font-sel-weight").val(),
				'font-style': $container.find(".tf-font-sel-style").val(),
				'line-height': $container.find(".tf-font-sel-height").val(),
				'letter-spacing': $container.find(".tf-font-sel-spacing").val(),
				'text-transform': $container.find(".tf-font-sel-transform").val(),
				'font-variant': $container.find(".tf-font-sel-variant").val(),
				'text-shadow-location': $container.find(".tf-font-sel-location").val(),
				'text-shadow-distance': $container.find(".tf-font-sel-distance").val(),
				'text-shadow-blur': $container.find(".tf-font-sel-blur").val(),
				'text-shadow-color': $container.find(".tf-font-sel-shadow-color").val(),
				'text-shadow-opacity': $container.find(".tf-font-sel-opacity").val(),
				'dark': $container.find(".tf-font-sel-dark").val(),
				'text': $container.find("iframe").attr('data-preview-text')
			}

			// Update preview
			if ( $container.find('iframe').is(':not([data-visible=false])') ) {
				$container.find('iframe').attr('src', '<?php echo TitanFramework::getURL( 'iframe-font-preview.php?', __FILE__ ) ?>' + $.param(params) );
			}

			// Update hidden save field
			$container.find('.tf-for-saving').val(serialize(params));
			if ( doTrigger ) {
				$container.find('.tf-for-saving').trigger('change');
			}
		}
		</script>
		<?php
	}


	/**
	 * Displays the option in admin panels and meta boxes
	 *
	 * @return	void
	 * @since	1.4
	 */
	public function display() {
		$this->echoOptionHeader( true );

		// Get the current value and merge with defaults
		$value = $this->getValue();
		if ( ! empty(  $value ) ) {
			$value = array_merge( self::$defaultStyling, $value );
		} else {
			$value = self::$defaultStyling;
		}

		/*
		 * Create all the fields
		 */
		$visibilityAttrs = '';
		if ( ! $this->settings['show_font_family'] ) {
			$visibilityAttrs = "data-visible='false' style='display: none'";
		}
		?>
		<div>
		<label <?php echo $visibilityAttrs ?>>
			Font Family
			<select class='tf-font-sel-family'>
				<option value='inherit'>inherit</option>
				<?php

				if( $this->settings['fonts'] ) {
					?>
						<optgroup label="Custom Fonts" class='customf-fonts'>
							<?php

							foreach ( $this->settings['fonts'] as $family => $label ) {
								printf( "<option value='%s'%s>%s</option>",
									$family,
									selected( $value['font-family'], $family, false ),
									$label
								);
							}

							?>
						</optgroup>
					<?php
				}

				if ( $this->settings['show_websafe_fonts'] ) {
					?>
					<optgroup label="Web Safe Fonts" class='safe'>
						<?php
						foreach ( self::$webSafeFonts as $family => $label ) {
							printf( "<option value='%s'%s>%s</option>",
								$family,
								selected( $value['font-family'], $family, false ),
								$label
							);
						}
						?>
					</optgroup>
					<?php
				}

				if ( $this->settings['show_google_fonts'] ) {
					?>
					<optgroup label="Google WebFonts" class='google'>
						<?php
						$allFonts = titan_get_googlefonts();
						foreach ( $allFonts as $key => $fontStuff ) {

							// Show only the include_fonts (font names) if provided, uses regex.
							if ( ! empty( $this->settings['include_fonts'] ) ) {
								if ( is_array( $this->settings['include_fonts'] ) ) {
									$fontNameMatch = false;
									foreach ( $this->settings['include_fonts'] as $fontNamePattern ) {
										if ( ! is_string( $fontNamePattern ) ) {
											continue;
										}
										$fontNamePattern = '/' . trim( $fontNamePattern, '/' ) . '/';
										if ( preg_match( $fontNamePattern . 'i', $fontStuff['name'] ) ) {
											$fontNameMatch = true;
											break;
										}
									}
									if ( ! $fontNameMatch ) {
										continue;
									}
								} else if ( is_string( $this->settings['include_fonts'] ) ) {
									$fontNamePattern = '/' . trim( $this->settings['include_fonts'], '/' ) . '/';
									if ( ! preg_match( $fontNamePattern . 'i', $fontStuff['name'] ) ) {
										continue;
									}
								}
							}

							printf( "<option value='%s'%s>%s</option>",
								esc_attr( $fontStuff['name'] ),
								selected( $value['font-family'], $fontStuff['name'], false ),
								$fontStuff['name']
							);
						}
						?>
					</optgroup>
					<?php
				}


				?>
			</select>
		</label>
		<?php

		$visibilityAttrs = '';
		if ( ! $this->settings['show_color'] ) {
			$visibilityAttrs = "data-visible='false' style='display: none'";
		}
		?>
		<label <?php echo $visibilityAttrs ?>>
			Color
			<input class='tf-font-sel-color' type="text" value="<?php echo esc_attr( $value['color'] ) ?>"  data-default-color="<?php echo esc_attr( $value['color'] ) ?>"/>
		</label>
		<?php

		$visibilityAttrs = '';
		if ( ! $this->settings['show_font_size'] ) {
			$visibilityAttrs = "data-visible='false' style='display: none'";
		}
		?>
		<label <?php echo $visibilityAttrs ?>>
			Font Size
			<select class='tf-font-sel-size'>
				<option value='inherit'>inherit</option>
				<?php
				for ( $i = 1; $i <= 150; $i++ ) {
					printf( "<option value='%s'%s>%s</option>",
						esc_attr( $i . 'px' ),
						selected( $value['font-size'], $i . 'px', false ),
						$i . 'px'
					);
				}
        for ( $i = 0.1; $i <= 3.1; $i += 0.1 ) {
					printf( "<option value='%s'%s>%s</option>",
						esc_attr( $i . 'em' ),
						selected( $value['font-size'], $i . 'em', false ),
						$i . 'em'
					);
				}
				?>
			</select>
		</label>
		<?php

		$visibilityAttrs = '';
		if ( ! $this->settings['show_font_weight'] ) {
			$visibilityAttrs = "data-visible='false' style='display: none'";
		}
		?>
		<label <?php echo $visibilityAttrs ?>>
			Font Weight
			<select class='tf-font-sel-weight'>
				<option value='inherit'>inherit</option>
				<?php
				$options = array( 'normal', 'bold', 'bolder', 'lighter', '100', '200', '300', '400', '500', '600', '700', '800', '900' );
				foreach ( $options as $option ) {
					printf( "<option value='%s'%s>%s</option>",
						esc_attr( $option ),
						selected( $value['font-weight'], $option, false ),
						$option
					);
				}
				?>
			</select>
		</label>
		<?php

		$visibilityAttrs = '';
		if ( ! $this->settings['show_font_style'] ) {
			$visibilityAttrs = "data-visible='false' style='display: none'";
		}
		?>
		<label <?php echo $visibilityAttrs ?>>
			Font Style
			<select class='tf-font-sel-style'>
				<?php
				$options = array( 'normal', 'italic' );
				foreach ( $options as $option ) {
					printf( "<option value='%s'%s>%s</option>",
						esc_attr( $option ),
						selected( $value['font-style'], $option, false ),
						$option
					);
				}
				?>
			</select>
		</label>
		<?php

		$visibilityAttrs = '';
		if ( ! $this->settings['show_line_height'] ) {
			$visibilityAttrs = "data-visible='false' style='display: none'";
		}
		?>
		<label <?php echo $visibilityAttrs ?>>
			Line Height
			<select class='tf-font-sel-height'>
				<?php
				for ( $i = .5; $i <= 3; $i += 0.1 ) {
					printf( "<option value='%s'%s>%s</option>",
						esc_attr( $i . 'em' ),
						selected( $value['line-height'], $i . 'em', false ),
						$i . 'em'
					);
				}
				?>
			</select>
		</label>
		<?php

		$visibilityAttrs = '';
		if ( ! $this->settings['show_letter_spacing'] ) {
			$visibilityAttrs = "data-visible='false' style='display: none'";
		}
		?>
		<label <?php echo $visibilityAttrs ?>>
			Letter Spacing
			<select class='tf-font-sel-spacing'>
				<option value='normal'>normal</option>
				<?php
				for ( $i = -20; $i <= 20; $i++ ) {
					printf( "<option value='%s'%s>%s</option>",
						esc_attr( $i . 'px' ),
						selected( $value['letter-spacing'], $i . 'px', false ),
						$i . 'px'
					);
				}
				?>
			</select>
		</label>
		<?php

		$visibilityAttrs = '';
		if ( ! $this->settings['show_text_transform'] ) {
			$visibilityAttrs = "data-visible='false' style='display: none'";
		}
		?>
		<label <?php echo $visibilityAttrs ?>>
			Text Transform
			<select class='tf-font-sel-transform'>
				<?php
				$options = array( 'none', 'capitalize', 'uppercase', 'lowercase' );
				foreach ( $options as $option ) {
					printf( "<option value='%s'%s>%s</option>",
						esc_attr( $option ),
						selected( $value['text-transform'], $option, false ),
						$option
					);
				}
				?>
			</select>
		</label>
		<?php

		$visibilityAttrs = '';
		if ( ! $this->settings['show_font_variant'] ) {
			$visibilityAttrs = "data-visible='false' style='display: none'";
		}
		?>
		<label <?php echo $visibilityAttrs ?>>
			Font Variant
			<select class='tf-font-sel-variant'>
				<?php
				$options = array( 'normal', 'small-caps' );
				foreach ( $options as $option ) {
					printf( "<option value='%s'%s>%s</option>",
						esc_attr( $option ),
						selected( $value['font-variant'], $option, false ),
						$option
					);
				}
				?>
			</select>
		</label>
		<?php

		$visibilityAttrs = '';
		if ( ! $this->settings['show_text_shadow'] ) {
			$visibilityAttrs = "data-visible='false' style='display: none'";
		}
		?>
		<label <?php echo $visibilityAttrs ?>>
			Shadow Location
			<select class='tf-font-sel-location'>
				<?php
				$options = array( 'none', 'top', 'bottom', 'left', 'right', 'top-left', 'top-right', 'bottom-left', 'bottom-right' );
				foreach ( $options as $option ) {
					printf( "<option value='%s'%s>%s</option>",
						esc_attr( $option ),
						selected( $value['text-shadow-location'], $option, false ),
						$option
					);
				}
				?>
			</select>
		</label>
		<label style='display: none'>
			Shadow Distance
			<select class='tf-font-sel-distance'>
				<?php
				for ( $i = 0; $i <= 10; $i++ ) {
					printf( "<option value='%s'%s>%s</option>",
						esc_attr( $i . 'px' ),
						selected( $value['text-shadow-distance'], $i . 'px', false ),
						$i . 'px'
					);
				}
				?>
			</select>
		</label>
		<label style='display: none'>
			Shadow Blur
			<select class='tf-font-sel-blur'>
				<?php
				$options = array( '0px', '1px', '2px', '3px', '4px', '5px', '10px', '20px' );
				foreach ( $options as $option ) {
					printf( "<option value='%s'%s>%s</option>",
						esc_attr( $option ),
						selected( $value['text-shadow-blur'], $option, false ),
						$option
					);
				}
				?>
			</select>
		</label>
		<label style='display: none'>
			Shadow Color
			<input class="tf-font-sel-shadow-color" type="text" value="<?php echo esc_attr( $value['text-shadow-color'] ) ?>"  data-default-color="<?php echo esc_attr( $value['text-shadow-color'] ) ?>"/>
		</label>
		<label style='display: none'>
			Shadow Opacity
			<select class='tf-font-sel-opacity'>
				<?php
				$options = array( '1', '0.9', '0.8', '0.7', '0.6', '0.5', '0.4', '0.3', '0.2', '0.1', '0' );
				foreach ( $options as $option ) {
					printf( "<option value='%s'%s>%s</option>",
						esc_attr( $option ),
						selected( $value['text-shadow-opacity'], $option, false ),
						$option
					);
				}
				?>
			</select>
		</label>
		</div>
		<?php

		$visibilityAttrs = '';
		if ( ! $this->settings['show_preview'] ) {
			$visibilityAttrs = "data-visible='false' style='display: none'";
		}
		?>
		<div <?php echo $visibilityAttrs ?>>
			<iframe data-preview-text='<?php echo esc_attr( $this->settings['preview_text'] ) ?>'></iframe>
			<i class='dashicons dashicons-admin-appearance btn-dark'></i>
			<input type='hidden' class='tf-font-sel-dark' value='<?php echo esc_attr( $value['dark'] ? 'dark' : '' ) ?>'/>
		</div>
		<?php

		if ( ! is_serialized( $value ) ) {
			$value = serialize( $value );
		}

		printf("<input type='hidden' class='tf-for-saving' name='%s' id='%s' value='%s' />",
			$this->getID(),
			$this->getID(),
			esc_attr( $value )
		);

		$this->echoOptionFooter( false );
	}


	/**
	 * Cleans up the serialized value before saving
	 *
	 * @param	string $value The serialized value
	 * @return	string The cleaned value
	 * @since	1.4
	 */
	public function cleanValueForSaving( $value ) {
		if ( is_array( $value ) ) {
			$value = serialize( $value );
		}
		return stripslashes( $value );
	}


	/**
	 * Cleans the raw value for getting
	 *
	 * @param	string $value The raw value
	 * @return	string The cleaned value
	 * @since	1.4
	 */
	public function cleanValueForGetting( $value ) {
		if ( is_string( $value ) ) {
			$value = maybe_unserialize( stripslashes( $value ) );
		}
		if ( is_array( $value ) ) {
			$value = array_merge( self::$defaultStyling, $value );
		}
		if ( ! empty( $value['font-family'] ) ) {
			$value['font-type'] = in_array( $value['font-family'], array_keys( self::$webSafeFonts ) ) ? 'websafe' : 'google';
		}
		return $value;
	}


	/**
	 * Registers the theme customizer control, for displaying the option
	 *
	 * @param	WP_Customize                    $wp_enqueue_script The customize object
	 * @param	TitanFrameworkCustomizerSection $section The section where this option will be placed
	 * @param	int                             $priority The order of this control in the section
	 * @return	void
	 * @since	1.4
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkOptionFontControl( $wp_customize, $this->getID(), array(
			'label' => $this->settings['name'],
			'section' => $section->settings['id'],
			'settings' => $this->getID(),
			'description' => $this->settings['desc'],
			'priority' => $priority,
			'params' => $this->settings,
		) ) );
	}
}



/*
 * We create a new control for the theme customizer
 */
add_action( 'customize_register', 'registerTitanFrameworkOptionFontControl', 1 );


/**
 * Creates the option for the theme customizer
 *
 * @return	void
 * @since	1.4
 */
function registerTitanFrameworkOptionFontControl() {
	class TitanFrameworkOptionFontControl extends WP_Customize_Control {
		public $description;
		public $params;

		public function render_content() {
			$this->params['show_preview'] = false;
			TitanFrameworkOptionFont::createFontScript();

			?>
			<div class='tf-font'>
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
			<?php

			// Get the current value and merge with defaults
			$value = $this->value();
			if ( is_serialized( $value ) ) {
				$value = unserialize( $value );
			}
			if ( ! is_array( $value ) ) {
				$value = array();
			}
			$value = array_merge( TitanFrameworkOptionFont::$defaultStyling, $value );

			/*
			 * Create all the fields
			 */
			$visibilityAttrs = '';
			if ( ! $this->params['show_font_family'] ) {
				$visibilityAttrs = "data-visible='false' style='display: none'";
			}
			?>
			<div>
			<label <?php echo $visibilityAttrs ?>>
				Font Family
				<select class='tf-font-sel-family'>
					<option value='inherit'>inherit</option>
					<?php

					if( $this->params['fonts'] ) {
						?>
							<optgroup label="Custom Fonts" class='customf-fonts'>
								<?php

								foreach ( $this->params['fonts'] as $family => $label ) {
									printf( "<option value='%s'%s>%s</option>",
										$family,
										selected( $value['font-family'], $family, false ),
										$label
									);
								}

								?>
							</optgroup>
						<?php
					}

					if ( $this->params['show_websafe_fonts'] ) {
					?>
				    <optgroup label="Web Safe Fonts" class='safe'>
						<?php
						foreach ( TitanFrameworkOptionFont::$webSafeFonts as $family => $label ) {
							printf( "<option value='%s'%s>%s</option>",
								$family,
								selected( $value['font-family'], $family, false ),
								$label
							);
						}
						?>
					</optgroup>
					<?php
					}
					?>

					<?php
					if ( $this->params['show_google_fonts'] ) {
					?>
				    <optgroup label="Google WebFonts" class='google'>
				    <?php
						$allFonts = titan_get_googlefonts();
						foreach ( $allFonts as $key => $fontStuff ) {

							// Show only the include_fonts (font names) if provided, uses regex.
							if ( ! empty( $this->params['include_fonts'] ) ) {
								if ( is_array( $this->params['include_fonts'] ) ) {
									$fontNameMatch = false;
									foreach ( $this->params['include_fonts'] as $fontNamePattern ) {
										if ( ! is_string( $fontNamePattern ) ) {
											continue;
										}
										$fontNamePattern = '/' . trim( $fontNamePattern, '/' ) . '/';
										if ( preg_match( $fontNamePattern . 'i', $fontStuff['name'] ) ) {
											$fontNameMatch = true;
											break;
										}
									}
									if ( ! $fontNameMatch ) {
										continue;
									}
								} else if ( is_string( $this->params['include_fonts'] ) ) {
									$fontNamePattern = '/' . trim( $this->params['include_fonts'], '/' ) . '/';
									if ( ! preg_match( $fontNamePattern . 'i', $fontStuff['name'] ) ) {
										continue;
									}
								}
							}

							printf( "<option value='%s'%s>%s</option>",
								esc_attr( $fontStuff['name'] ),
								selected( $value['font-family'], $fontStuff['name'], false ),
								$fontStuff['name']
							);
						}
						?>
					</optgroup>
					<?php
					// End the show_google_fonts conditional
					}
					?>
				</select>
			</label>
			<?php

			$visibilityAttrs = '';
			if ( ! $this->params['show_color'] ) {
				$visibilityAttrs = "data-visible='false' style='display: none'";
			}
			?>
			<label <?php echo $visibilityAttrs ?>>
				Color
				<input class='tf-font-sel-color' type="text" value="<?php echo esc_attr( $value['color'] ) ?>"  data-default-color="<?php echo esc_attr( $value['color'] ) ?>"/>
			</label>
			<?php

			$visibilityAttrs = '';
			if ( ! $this->params['show_font_size'] ) {
				$visibilityAttrs = "data-visible='false' style='display: none'";
			}
			?>
			<label <?php echo $visibilityAttrs ?>>
				Font Size
				<select class='tf-font-sel-size'>
					<option value='inherit'>inherit</option>
					<?php
					for ( $i = 1; $i <= 150; $i++ ) {
						printf( "<option value='%s'%s>%s</option>",
							esc_attr( $i . 'px' ),
							selected( $value['font-size'], $i . 'px', false ),
							$i . 'px'
						);
					}
					?>
				</select>
			</label>
			<?php

			$visibilityAttrs = '';
			if ( ! $this->params['show_font_weight'] ) {
				$visibilityAttrs = "data-visible='false' style='display: none'";
			}
			?>
			<label <?php echo $visibilityAttrs ?>>
				Font Weight
				<select class='tf-font-sel-weight'>
					<option value='inherit'>inherit</option>
					<?php
					$options = array( 'normal', 'bold', 'bolder', 'lighter', '100', '200', '300', '400', '500', '600', '700', '800', '900' );
					foreach ( $options as $option ) {
						printf( "<option value='%s'%s>%s</option>",
							esc_attr( $option ),
							selected( $value['font-weight'], $option, false ),
							$option
						);
					}
					?>
				</select>
			</label>
			<?php

			$visibilityAttrs = '';
			if ( ! $this->params['show_font_style'] ) {
				$visibilityAttrs = "data-visible='false' style='display: none'";
			}
			?>
			<label <?php echo $visibilityAttrs ?>>
				Font Style
				<select class='tf-font-sel-style'>
					<?php
					$options = array( 'normal', 'italic' );
					foreach ( $options as $option ) {
						printf( "<option value='%s'%s>%s</option>",
							esc_attr( $option ),
							selected( $value['font-style'], $option, false ),
							$option
						);
					}
					?>
				</select>
			</label>
			<?php

			$visibilityAttrs = '';
			if ( ! $this->params['show_line_height'] ) {
				$visibilityAttrs = "data-visible='false' style='display: none'";
			}
			?>
			<label <?php echo $visibilityAttrs ?>>
				Line Height
				<select class='tf-font-sel-height'>
					<?php
					for ( $i = .5; $i <= 3; $i += 0.1 ) {
						printf( "<option value='%s'%s>%s</option>",
							esc_attr( $i . 'em' ),
							selected( $value['line-height'], $i . 'em', false ),
							$i . 'em'
						);
					}
					?>
				</select>
			</label>
			<?php

			$visibilityAttrs = '';
			if ( ! $this->params['show_letter_spacing'] ) {
				$visibilityAttrs = "data-visible='false' style='display: none'";
			}
			?>
			<label <?php echo $visibilityAttrs ?>>
				Letter Spacing
				<select class='tf-font-sel-spacing'>
					<option value='normal'>normal</option>
					<?php
					for ( $i = -20; $i <= 20; $i++ ) {
						printf( "<option value='%s'%s>%s</option>",
							esc_attr( $i . 'px' ),
							selected( $value['letter-spacing'], $i . 'px', false ),
							$i . 'px'
						);
					}
					?>
				</select>
			</label>
			<?php

			$visibilityAttrs = '';
			if ( ! $this->params['show_text_transform'] ) {
				$visibilityAttrs = "data-visible='false' style='display: none'";
			}
			?>
			<label <?php echo $visibilityAttrs ?>>
				Text Transform
				<select class='tf-font-sel-transform'>
					<?php
					$options = array( 'none', 'capitalize', 'uppercase', 'lowercase' );
					foreach ( $options as $option ) {
						printf( "<option value='%s'%s>%s</option>",
							esc_attr( $option ),
							selected( $value['text-transform'], $option, false ),
							$option
						);
					}
					?>
				</select>
			</label>
			<?php

			$visibilityAttrs = '';
			if ( ! $this->params['show_font_variant'] ) {
				$visibilityAttrs = "data-visible='false' style='display: none'";
			}
			?>
			<label <?php echo $visibilityAttrs ?>>
				Font Variant
				<select class='tf-font-sel-variant'>
					<?php
					$options = array( 'normal', 'small-caps' );
					foreach ( $options as $option ) {
						printf( "<option value='%s'%s>%s</option>",
							esc_attr( $option ),
							selected( $value['font-variant'], $option, false ),
							$option
						);
					}
					?>
				</select>
			</label>
			<?php

			$visibilityAttrs = '';
			if ( ! $this->params['show_text_shadow'] ) {
				$visibilityAttrs = "data-visible='false' style='display: none'";
			}
			?>
			<label <?php echo $visibilityAttrs ?>>
				Shadow Location
				<select class='tf-font-sel-location'>
					<?php
					$options = array( 'none', 'top', 'bottom', 'left', 'right', 'top-left', 'top-right', 'bottom-left', 'bottom-right' );
					foreach ( $options as $option ) {
						printf( "<option value='%s'%s>%s</option>",
							esc_attr( $option ),
							selected( $value['text-shadow-location'], $option, false ),
							$option
						);
					}
					?>
				</select>
			</label>
			<label style='display: none'>
				Shadow Distance
				<select class='tf-font-sel-distance'>
					<?php
					for ( $i = 0; $i <= 10; $i++ ) {
						printf( "<option value='%s'%s>%s</option>",
							esc_attr( $i . 'px' ),
							selected( $value['text-shadow-distance'], $i . 'px', false ),
							$i . 'px'
						);
					}
					?>
				</select>
			</label>
			<label style='display: none'>
				Shadow Blur
				<select class='tf-font-sel-blur'>
					<?php
					$options = array( '0px', '1px', '2px', '3px', '4px', '5px', '10px', '20px' );
					foreach ( $options as $option ) {
						printf( "<option value='%s'%s>%s</option>",
							esc_attr( $option ),
							selected( $value['text-shadow-blur'], $option, false ),
							$option
						);
					}
					?>
				</select>
			</label>
			<label style='display: none'>
				Shadow Color
				<input class="tf-font-sel-shadow-color" type="text" value="<?php echo esc_attr( $value['text-shadow-color'] ) ?>"  data-default-color="<?php echo esc_attr( $value['text-shadow-color'] ) ?>"/>
			</label>
			<label style='display: none'>
				Shadow Opacity
				<select class='tf-font-sel-opacity'>
					<?php
					$options = array( '1', '0.9', '0.8', '0.7', '0.6', '0.5', '0.4', '0.3', '0.2', '0.1', '0' );
					foreach ( $options as $option ) {
						printf( "<option value='%s'%s>%s</option>",
							esc_attr( $option ),
							selected( $value['text-shadow-opacity'], $option, false ),
							$option
						);
					}
					?>
				</select>
			</label>
			</div>
			<?php

			$visibilityAttrs = '';
			if ( ! $this->params['show_preview'] ) {
				$visibilityAttrs = "data-visible='false' style='display: none'";
			}
			?>
			<div <?php echo $visibilityAttrs ?>>
				<iframe></iframe>
				<i class='dashicons dashicons-admin-appearance btn-dark'></i>
				<input type='hidden' class='tf-font-sel-dark' value='<?php echo esc_attr( $value['dark'] ? 'dark' : '' ) ?>'/>
			</div>
			<?php

			if ( ! is_serialized( $value ) ) {
				$value = serialize( $value );
			}

			?>
			<input type='hidden' class='tf-for-saving' <?php $this->link() ?> value='<?php echo esc_attr( $value ) ?>'/>
			</div>
			<?php
			echo "<p class='description'>{$this->description}</p>";
		}
	}
}
