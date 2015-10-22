<?php

/**
 * Sortable Option Class
 *
 * @author	Benjamin Intal
 * @package	Titan Framework Core
 * @since	1.4
 **/

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}
/**
 * Code Option Class
 *
 * @since	1.4
 **/
class TitanFrameworkOptionSortable extends TitanFrameworkOption {

	// Default settings specific to this option
	public $defaultSecondarySettings = array(
		'options' => array(),
		'visible_button' => true,
	);

	private static $firstLoad = true;


	/**
	 * Constructor
	 *
	 * @since	1.4
	 */
	function __construct( $settings, $owner ) {
		parent::__construct( $settings, $owner );

		add_action( 'admin_head', array( __CLASS__, 'createSortableScript' ) );
		tf_add_action_once( 'admin_enqueue_scripts', array( $this, 'enqueueSortable' ) );
		tf_add_action_once( 'customize_controls_enqueue_scripts', array( $this, 'enqueueSortable' ) );
	}


	/**
	 * Enqueues the jQuery UI scripts
	 *
	 * @return	void
	 * @since	1.4
	 */
	public function enqueueSortable() {
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-sortable' );
	}


	/**
	 * Creates the javascript needed for sortable to run
	 *
	 * @return	void
	 * @since	1.4
	 */
	public static function createSortableScript() {
		if ( ! self::$firstLoad ) {
			return;
		}
		self::$firstLoad = false;

		?>
		<script>
		jQuery(document).ready(function($) {
			"use strict";

			// initialize
			$('.tf-sortable > ul ~ input').each(function() {
				var value = $(this).val();
				try {
					value = unserialize( value );
				} catch (err) {
					return;
				}

				var ul = $(this).siblings('ul:eq(0)');
				ul.find('li').addClass('tf-invisible').find('i.visibility').toggleClass('dashicons-visibility-faint');
				$.each(value, function(i, val) {
					ul.find('li[data-value=' + val + ']').removeClass('tf-invisible').find('i.visibility').toggleClass('dashicons-visibility-faint');
				});
			});

			$('.tf-sortable > ul').each(function() {
				$(this).sortable()
				.disableSelection()
				.on( "sortstop", function( event, ui ) {
					tfUpdateSortable(ui.item.parent());
				})
				.find('li').each(function() {
					$(this).find('i.visibility').click(function() {
						$(this).toggleClass('dashicons-visibility-faint').parents('li:eq(0)').toggleClass('tf-invisible');
					});
				})
				.click(function() {
					tfUpdateSortable( $(this).parents('ul:eq(0)') );
				})
			});
		});

		function tfUpdateSortable(ul) {
			"use strict";
			var $ = jQuery;

			var values = [];

			ul.find('li').each(function() {
				if ( ! $(this).is('.tf-invisible') ) {
					values.push( $(this).attr('data-value') );
				}
			});

			ul.siblings('input').eq(0).val( serialize( values ) ).trigger('change');
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
		if ( ! is_array( $this->settings['options'] ) ) {
			return;
		}
		if ( ! count( $this->settings['options'] ) ) {
			return;
		}

		$this->echoOptionHeader( true );

		$values = $this->getValue();
		if ( $values == '' ) {
			$values = array_keys( $this->settings['options'] );
		}
		if ( count( $values ) != count( $this->settings['options'] ) ) {
			$this->settings['visible_button'] = true;
		}

		$visibleButton = '';
		if ( $this->settings['visible_button'] == true ) {
			$visibleButton = "<i class='dashicons dashicons-visibility visibility'></i>";
		}
		?>
		<ul>
			<?php
			foreach ( $values as $dummy => $value ) {
				if ( isset( $this->settings['options'][ $value ] ) ) {
					printf( "<li data-value='%s'><i class='dashicons dashicons-menu'></i>%s%s</li>",
						esc_attr( $value ),
						$visibleButton,
						$this->settings['options'][ $value ]
					);
				}
			}

			$invisibleKeys = array_diff( array_keys( $this->settings['options'] ), $values );
			foreach ( $invisibleKeys as $dummy => $value ) {
				if ( isset( $this->settings['options'][ $value ] ) ) {
					printf( "<li data-value='%s'><i class='dashicons dashicons-menu'></i>%s%s</li>",
						esc_attr( $value ),
						$visibleButton,
						$this->settings['options'][ $value ]
					);
				}
			}
			?>
		</ul>
		<div class='clear: both'></div>
		<?php

		if ( ! is_serialized( $values ) ) {
			$values = serialize( $values );
		}

		printf( "<input type='hidden' name=\"%s\" id=\"%s\" value=\"%s\" />",
			$this->getID(),
			$this->getID(),
			esc_attr( $values )
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
		if ( is_array( $value ) ) {
			return $value;
		}
		if ( is_serialized( stripslashes( $value ) ) ) {
			return unserialize( $value );
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
		$wp_customize->add_control( new TitanFrameworkOptionSortableControl( $wp_customize, $this->getID(), array(
			'label' => $this->settings['name'],
			'section' => $section->settings['id'],
			'settings' => $this->getID(),
			'description' => $this->settings['desc'],
			'priority' => $priority,
			'options' => $this->settings['options'],
			'visible_button' => $this->settings['visible_button'],
		) ) );
	}
}



/*
 * We create a new control for the theme customizer
 */
add_action( 'customize_register', 'registerTitanFrameworkOptionSortableControl', 1 );


/**
 * Creates the option for the theme customizer
 *
 * @return	void
 * @since	1.4
 */
function registerTitanFrameworkOptionSortableControl() {
	class TitanFrameworkOptionSortableControl extends WP_Customize_Control {
		public $description;
		public $options;
		public $visible_button;

		public function render_content() {
			TitanFrameworkOptionSortable::createSortableScript();

			if ( ! is_array( $this->options ) ) {
				return;
			}
			if ( ! count( $this->options ) ) {
				return;
			}

			?>
			<label class='tf-sortable'>
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
			<?php

			$values = $this->value();
			if ( $values == '' ) {
				$values = array_keys( $this->options );
			}
			if ( is_serialized( $values ) ) {
				$values = unserialize( $values );
			}
			if ( count( $values ) != count( $this->options ) ) {
				$this->visible_button = true;
			}

			$visibleButton = '';
			if ( $this->visible_button == true ) {
				$visibleButton = "<i class='dashicons dashicons-visibility visibility'></i>";
			}
			?>
			<ul>
				<?php
				foreach ( $values as $dummy => $value ) {
					printf( "<li data-value='%s'><i class='dashicons dashicons-menu'></i>%s%s</li>",
						esc_attr( $value ),
						$visibleButton,
						$this->options[ $value ]
					);
				}

				$invisibleKeys = array_diff( array_keys( $this->options ), $values );
				foreach ( $invisibleKeys as $dummy => $value ) {
					printf( "<li data-value='%s'><i class='dashicons dashicons-menu'></i>%s%s</li>",
						esc_attr( $value ),
						$visibleButton,
						$this->options[ $value ]
					);
				}
				?>
			</ul>
			<div class='clear: both'></div>
			<?php

			if ( ! is_serialized( $values ) ) {
				$values = serialize( $values );
			}

			?>
				<input type='hidden' <?php $this->link(); ?> value='<?php echo esc_attr( $values )  ?>'/>
			</label>
			<?php
			echo "<p class='description'>{$this->description}</p>";
		}
	}
}
