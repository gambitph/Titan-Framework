<?php
/**
 * Select Google Font Option
 *
 * @deprecated deprecated since 1.4, should be removed in 1.5
 * @since	1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class TitanFrameworkOptionSelectGooglefont extends TitanFrameworkOption {

	public $defaultSecondarySettings = array(
		'enqueue' => true,
	);

	public static $firstLoad = true;


	/**
	 * Constructor
	 *
	 * @since	1.4
	 */
	function __construct( $settings, $owner ) {
		if ( defined( 'WP_DEBUG' ) ) {
			if ( WP_DEBUG == true ) {
				// Warn about deprecation, refer to `font` option
				TitanFramework::displayFrameworkError( sprintf( __( '%s has been deprecated and will be removed in version %s! Please use %s instead to avoid errors in the future.', TF_I18NDOMAIN ), '<code>select-googlefont</code>', '<code>1.5</code>', '<code>font</code>' ) );
			}
		}

		parent::__construct( $settings, $owner );

		add_filter( 'tf_generate_css_select-googlefont_' . $this->getOptionNamespace(), array( $this, 'generateCSS' ), 10, 2 );
	}


	/**
	 * Generates CSS for the font, this is used in TitanFrameworkCSS
	 *
	 * @param	string $css The CSS generated
	 * @param	TitanFrameworkOption $option The current option being processed
	 * @return	string The CSS generated
	 * @since	1.4
	 */
	public function generateCSS( $css, $option ) {
		if ( $this->settings['id'] != $option->settings['id'] ) {
			return $css;
		}

		$value = $this->getFramework()->getOption( $option->settings['id'] );

		if ( ! empty( $value['fontFamily'] ) ) {
			$css .= "\$" . $option->settings['id'] . "-name: " . $value['fontFamily'] . ";";
			$css .= "\$" . $option->settings['id'] . ": " . $value['fontFamily'] . ";";
		}

		if ( ! empty( $option->settings['css'] ) ) {
			$css .= str_replace( 'value', '$' . $option->settings['id'], $option->settings['css'] );
		}

		return $css;
	}


	public static function formScript( $value ) {
		if ( empty( $value ) ) {
			return '';
		}
		if ( is_serialized( $value ) ) {
			$value = unserialize( $value );
		}
		return sprintf( "http://fonts.googleapis.com/css?family=%s:%s&subset=%s",
			str_replace( ' ', '+', $value['name'] ),
			implode( ',', $value['variants'] ),
			implode( ',', $value['subsets'] )
		);
	}

	public static function formCSS( $value ) {
		if ( is_serialized( $value ) ) {
			$value = unserialize( $value );
		}
		return sprintf( "font-family: '%s', sans-serif;",
			$value['name']
		);
	}


	public static function formFormFamily( $value ) {
		if ( is_serialized( $value ) ) {
			$value = unserialize( $value );
		}
		return sprintf( "'%s', sans-serif",
			$value['name']
		);
	}

	public static function getVariantName( $variant ) {
		$variantName = '';

		if (preg_match('#^\d+#', $variant, $matches)) {
			if (count($matches)) {
				$fontWeight = $matches[0];
				switch ($fontWeight) {
					case '100':
						$variantName = __("Ultra-light", TF_I18NDOMAIN);
						break;
					case '200':
						$variantName = __("Light", TF_I18NDOMAIN);
						break;
					case '300':
						$variantName = __("Book", TF_I18NDOMAIN);
						break;
					case '500':
						$variantName = __("Medium", TF_I18NDOMAIN);
						break;
					case '600':
						$variantName = __("Semi-Bold", TF_I18NDOMAIN);
						break;
					case '700':
						$variantName = __("Bold", TF_I18NDOMAIN);
						break;
					case '800':
						$variantName = __("Extra-Bold", TF_I18NDOMAIN);
						break;
					case '900':
						$variantName = __("Ultra-Bold", TF_I18NDOMAIN);
						break;
					default:
						$variantName = __("Regular", TF_I18NDOMAIN);
				}
			}
		}

		if ( stripos( $variant, 'italic' ) !== false ) {
			$variantName .= str_replace( 'italic', ' Italic', $variant );
		}

		$variantName = trim( $variantName );

		return $variantName;
	}

	public static function createScript() {
		?>
		<script>
		jQuery(document).ready(function($) {
			"use strict";

			// initialize, select the correct item in the drop down box
			$('select.tf-select-googlefont ~ input').each(function() {
				var val = $(this).val();
				if ( val != '' ) {
					try {
						val = unserialize(val);
					} catch (err) {
						return;
					}

					var $this = $(this);
					$.each(val.variants, function(i, variant) {
						$this.parent().find('input.variant[data-variant=' + variant + ']').attr('checked', 'checked');
					});

					$.each(val.subsets, function(i, subset) {
						$this.parent().find('input.subset[data-subset=' + subset + ']').attr('checked', 'checked');
					});

					var valToSelect = $(this).parent().find('option').filter(function() {
						return $(this).text() == val.name;
					}).attr('value');

					$(this).parent().find('select.tf-select-googlefont').val( valToSelect );

				}
			});

			// update the field when a subset or variant is changed
			$('select.tf-select-googlefont ~ fieldset input').change(function() {
				var select = $(this).parents('fieldset').siblings('select.tf-select-googlefont');
				// prevent recursion
				if ( select.is('[just-changed]') ) {
					return;
				}
				$(this).parents('fieldset').siblings('select.tf-select-googlefont').trigger('change');
			});

			$('select.tf-select-googlefont')
			.change(function(event) {
				event.preventDefault();

				// prevent recursion
				$(this).attr('just-changed', '1');

				var option = $(this).parent().find('option[value=' + $(this).val() + ']');

				// show / hide subsets
				var supportedSubsets = JSON.parse( option.attr('data-subset') );
				var subsets = $(this).parent().find('input[data-subset]').parent().hide().end().filter(function() {
					return supportedSubsets.indexOf($(this).attr('data-subset')) != -1;
				}).parent().show().end();

				// make sure that at least one is checked
				if ( subsets.filter(':checked').length == 0 ) {
					subsets.eq(0).attr('checked','checked')
				}

				// show / hide variants
				var supportedVariants = JSON.parse( option.attr('data-variants') );
				var variants = $(this).parent().find('input[data-variant]').parent().hide().end().filter(function() {
					return supportedVariants.indexOf($(this).attr('data-variant')) != -1;
				}).parent().show().end();

				// make sure that at least one is checked
				if ( variants.filter(':checked').length == 0 ) {
					variants.eq(0).attr('checked','checked')
				}

				// update the text field
				var selectedVariants = $.map(variants.filter(':checked'), function(o) {
					return $(o).attr('data-variant');
				});
				var selectedSubsets = $.map(subsets.filter(':checked'), function(o) {
					return $(o).attr('data-subset');
				});
				var val = {
					name: option.text(),
					variants: selectedVariants,
					subsets: selectedSubsets
				};

				// update the preview
				$(this).siblings('iframe').attr('src', '<?php echo TitanFramework::getURL( 'iframe-googlefont-preview.php?f=', __FILE__ ) ?>' + val.name);

				// update the hidden field
				$(this).siblings('input').val(serialize(val)).trigger('change');
				$(this).removeAttr('just-changed');
			})
			.trigger('change');
		});
		</script>
		<?php
	}

	/*
	 * Display for options and meta
	 */
	public function display() {
		$this->echoOptionHeader( true );

		// set a default value
		$value = $this->getValue();
		if ( is_serialized( $value ) ) {
			$value = unserialize( $value );
		}
		if ( $value == array() || empty( $value['name'] ) || empty( $value['variants'] ) || empty( $value['subsets'] ) ) {
			$value = array( 'name' => 'Open Sans', 'variants' => array( '400' ), 'subsets' => array( 'latin' ) );
		}

		if ( self::$firstLoad ) {
			self::$firstLoad = false;
			self::createScript();
		}

		$allFonts = titan_get_googlefonts();

		?><select class='tf-select-googlefont'><?php
		foreach ( $allFonts as $key => $fontStuff ) {
			printf( "<option value='%s' data-subset='%s' data-variants='%s'>%s</option>",
				esc_attr( $key ),
				esc_attr( json_encode( $fontStuff['subsets'] ) ),
				esc_attr( json_encode( $fontStuff['variants'] ) ),
				$fontStuff['name']
			);
		}
		?></select><?php


		// preview
		printf( "<iframe src='%s'></iframe>", TitanFramework::getURL( 'iframe-googlefont-preview.php?f=' . $value['name'], __FILE__ ) );


		// select variants
		echo "<p class='description tf-variants'>Choose the styles to include:</p>";
		echo "<fieldset>";
		$allVariants = array( '100', '100italic', '200', '200italic', '300', '300italic', '400', 'italic', '500', '500italic', '600', '600italic', '700', '700italic', '800', '800italic', '900', '900italic' );
		foreach ( $allVariants as $key => $variant ) {
			printf( "<label style='display: none'><input type='checkbox' class='variant' data-variant='%s'/> %s</label>",
				esc_attr( $variant ),
				self::getVariantName( $variant )
			);
		}
		echo "</fieldset>";


		// select charsets
		echo "<p class='description tf-subsets'>Choose the subsets to include:</p>";
		echo "<fieldset>";
		$allSubsets = array( "latin", "latin-ext", "greek", "vietnamese", "cyrillic", "cyrillic-ext", "khmer", "greek-ext" );
		foreach ( $allSubsets as $key => $subset ) {
			printf( "<label style='display: none'><input type='checkbox' class='subset' data-subset='%s'/> %s</label>",
				esc_attr( $subset ),
				$subset
			);
		}
		echo "</fieldset>";

		if ( ! is_serialized( $value ) ) {
			$value = serialize( $value );
		}

		printf( "<input type='hidden' value='%s' name='%s' class='large-text'/>",
			esc_attr( $value ),
			esc_attr( $this->getID() )
		);

		$this->echoOptionFooter( false );
	}

	public function cleanValueForSaving( $value ) {
		if ( is_serialized( $value ) ) {
			return $value;
		}
		if ( is_string( $value ) ) {
			// return serialize
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
		if ( is_serialized( stripslashes( $value ) ) ) {
			$value = unserialize( stripslashes( $value ) );
			$value['css'] = self::formCSS( $value );
			$value['fontFamily'] = self::formFormFamily( $value );
			return $value;
		}
		if ( is_string( $value ) && stripos( $value, ',' ) !== false ) {
			$value = explode( ',', $value );
			$value['css'] = self::formCSS( $value );
			$value['fontFamily'] = self::formFormFamily( $value );
			return $value;
		}
		return $this->settings['default'];
	}

	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkOptionSelectGooglefontControl( $wp_customize, $this->getID(), array(
			'label' => $this->settings['name'],
			'section' => $section->settings['id'],
			'settings' => $this->getID(),
			'description' => $this->settings['desc'],
			'priority' => $priority,
		) ) );
	}
}


/*
 * WP_Customize_Control with description
 */
add_action( 'customize_register', 'registerTitanFrameworkOptionSelectGooglefontControl', 1 );
function registerTitanFrameworkOptionSelectGooglefontControl() {
	class TitanFrameworkOptionSelectGooglefontControl extends WP_Customize_Control {
		public $description;
		private static $firstLoad = true;

		public function render_content() {

			if ( self::$firstLoad ) {
				self::$firstLoad = false;
				TitanFrameworkOptionSelectGooglefont::createScript();
			}

			// set a default value
			$value = $this->value();
			if ( $value == array() || empty( $value ) ) {
				$value = serialize( array( 'name' => 'Open Sans', 'variants' => array( '400' ), 'subsets' => array( 'latin' ) ) );
			}
			$allFonts = titan_get_googlefonts();

			?>
			<script>
			jQuery(document).ready(function($) {
				setTimeout( function() {
					jQuery('input[data-customize-setting-link=<?php echo $this->id ?>]').val('<?php echo serialize( $value ) ?>');
				}, 1 );
			});
			</script>
			<label>
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
				<?php
				if ( ! empty( $this->description ) ) {
					echo "<p class='description'>{$this->description}</p>";
				}
				?>
				<select class='tf-select-googlefont'>
				<?php
				foreach ( $allFonts as $key => $fontStuff ) {
					printf( "<option value='%s' data-subset='%s' data-variants='%s'>%s</option>",
						esc_attr( $key ),
						esc_attr( json_encode( $fontStuff['subsets'] ) ),
						esc_attr( json_encode( $fontStuff['variants'] ) ),
						$fontStuff['name']
					);
				}
				?>
				</select>
				<?php


				// select variants
				echo "<p class='description tf-variants'>Choose the styles to include:</p>";
				echo "<fieldset class='tf-googlefont-area'>";
				$allVariants = array( '100', '100italic', '200', '200italic', '300', '300italic', '400', 'italic', '500', '500italic', '600', '600italic', '700', '700italic', '800', '800italic', '900', '900italic' );
				foreach ( $allVariants as $key => $variant ) {
					printf( "<label style='display: none'><input type='checkbox' class='variant' data-variant='%s'/> %s</label>",
						esc_attr( $variant ),
						TitanFrameworkOptionSelectGooglefont::getVariantName( $variant )
					);
				}
				echo "</fieldset>";


				// select charsets
				echo "<p class='description tf-subsets'>Choose the subsets to include:</p>";
				echo "<fieldset class='tf-googlefont-area'>";
				$allSubsets = array( "latin", "latin-ext", "greek", "vietnamese", "cyrillic", "cyrillic-ext", "khmer", "greek-ext" );
				foreach ( $allSubsets as $key => $subset ) {
					printf( "<label style='display: none'><input type='checkbox' class='subset' data-subset='%s'/> %s</label>",
						esc_attr( $subset ),
						$subset
					);
				}
				echo "</fieldset>";

				if ( ! is_serialized( $value ) ) {
					$value = serialize( $value );
				}

				?>
				<input type='hidden' <?php $this->link() ?> value='<?php echo esc_attr( $value ) ?>'/>
			</label>
			<?php
		}
	}
}