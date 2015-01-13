<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class TitanFrameworkOptionEnable extends TitanFrameworkOption {

	private static $firstLoad = true;

	public $defaultSecondarySettings = array(
		'enabled' => '',
		'disabled' => '',
	);

	/*
	 * Display for options and meta
	 */
	public function display() {
		$this->echoOptionHeader();

		if ( empty( $this->settings['enabled'] ) ) {
			$this->settings['enabled'] = __( 'Enabled', TF_I18NDOMAIN );
		}
		if ( empty( $this->settings['disabled'] ) ) {
			$this->settings['disabled'] = __( 'Disabled', TF_I18NDOMAIN );
		}

		?>
		<input name="<?php echo $this->getID() ?>" type="checkbox" id="<?php echo $this->getID() ?>" value="1" <?php checked( $this->getValue(), 1 ) ?>>
		<span class="button button-<?php echo checked( $this->getValue(), 1, false ) ? 'primary' : 'secondary' ?>"><?php echo $this->settings['enabled'] ?></span><span class="button button-<?php echo checked( $this->getValue(), 1, false ) ? 'secondary' : 'primary' ?>"><?php echo $this->settings['disabled'] ?></span>
		<?php

		// load the javascript to init the colorpicker
		if ( self::$firstLoad ):
			?>
			<script>
			jQuery(document).ready(function($) {
				"use strict";
				$('body').on('click', '.tf-enable .button-secondary', function() {
					$(this).parent().find('.button').toggleClass('button-primary button-secondary');
					var checkBox = $(this).parents('.tf-enable').find('input');
					if ( checkBox.is(':checked') ) {
						checkBox.removeAttr('checked');
					} else {
						checkBox.attr('checked', 'checked');
					}
					checkBox.trigger('change');
				});
			});
			</script>
			<?php
		endif;

		$this->echoOptionFooter();

		self::$firstLoad = false;
	}

	public function cleanValueForSaving( $value ) {
		return $value != '1' ? '0' : '1';
	}

	public function cleanValueForGetting( $value ) {
		return $value == '1' ? true : false;
	}

	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkOptionEnableControl( $wp_customize, $this->getID(), array(
			'label' => $this->settings['name'],
			'section' => $section->settings['id'],
			'settings' => $this->getID(),
			'description' => $this->settings['desc'],
			'priority' => $priority,
			'options' => $this->settings,
		) ) );
	}
}


/*
 * We create a new control for the theme customizer
 */
add_action( 'customize_register', 'registerTitanFrameworkOptionEnableControl', 1 );
function registerTitanFrameworkOptionEnableControl() {
	class TitanFrameworkOptionEnableControl extends WP_Customize_Control {
		public $description;
		public $options;

		private static $firstLoad = true;

		public function render_content() {

			if ( empty( $this->options['enabled'] ) ) {
				$this->options['enabled'] = __( 'Enabled', TF_I18NDOMAIN );
			}
			if ( empty( $this->options['disabled'] ) ) {
				$this->options['disabled'] = __( 'Disabled', TF_I18NDOMAIN );
			}
			?>
			<div class='tf-enable'>
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
				<input type="checkbox" value="1" <?php $this->link(); ?>>
				<span class="button button-<?php echo checked( $this->value(), 1, false ) ? 'primary' : 'secondary' ?>"><?php echo $this->options['enabled'] ?></span><span class="button button-<?php echo checked( $this->value(), 1, false ) ? 'secondary' : 'primary' ?>"><?php echo $this->options['disabled'] ?></span>
			</div>
			<?php

			echo "<p class='description'>{$this->description}</p>";

			// load the javascript to init the colorpicker
			if ( self::$firstLoad ):
				?>
				<script>
				jQuery(document).ready(function($) {
					"use strict";
					$('body').on('click', '.tf-enable .button-secondary', function() {
						$(this).parent().find('.button').toggleClass('button-primary button-secondary');
						var checkBox = $(this).parents('.tf-enable').find('input');
						if ( checkBox.is(':checked') ) {
							checkBox.removeAttr('checked');
						} else {
							checkBox.attr('checked', 'checked');
						}
						checkBox.trigger('change');
					});
				});
				</script>
				<?php
			endif;

			self::$firstLoad = false;
		}
	}
}