<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}
/*
 * People can extend this class to create their own options
 */


class TitanFrameworkOption {

	const TYPE_META = 'meta';
	const TYPE_ADMIN = 'option';
	const TYPE_CUSTOMIZER = 'customizer';

	public $settings;
	public $type; // One of the TYPE_* constants above
	public $owner;
	public $echo_wrapper = true;

	private static $rowIndex = 0;


	/**
	 * Default settings across all options
	 * @var array
	 */
	private static $defaultSettings = array(

		'type' => 'text',

		/**
		 * The name of the option, for display purposes only.
		 *
		 * @since 1.0
		 * @var string
		 */
		'name' => '',

		/**
		 * The description to display together with this option.
		 *
		 * @since 1.0
		 * @var string
		 */
		'desc' => '',

		/**
		 * A unique ID for this option. This ID will be used to get the value for this option.
		 *
		 * @since 1.0
		 * @var string
		 */
		'id' => '',

		/**
		 * (Optional) The default value for this option.
		 *
		 * @since 1.0
		 * @var mixed
		 */
		'default' => '',

		/**
		 * (Optional) jQuery code that updates something in your site in the live preview. Only used when the option is placed in a theme customizer section.
		 *
		 * @since 1.0
		 * @var string
		 * @see http://www.titanframework.net/livepreview-parameter
		 */
		'livepreview' => '', // jQuery script to update something in the site. For theme customizer only

		/**
		 * (Optional) CSS rules to be used with this option. Only used when the option is placed in an admin page / panel or a theme customizer section.
		 * @since 1.0
		 * @var string
		 * @see http://www.titanframework.net/generate-css-automatically-for-your-options/
		 */
		'css' => '',

		/**
		 * (Optional) If true, the option will not be displayed, but will still be accessible using `getOption`. This is helpful for deprecating old settings, while still making your project backward compatible.
		 * @since 1.8
		 * @var bool
		 */
		'hidden' => false,

		/**
		 * (Optional) The transport parameter in the Customizer is automatically set. Use this to override the transport value. Value can be blank, 'refresh' or 'postMessage'
		 * @since 1.9.3
		 * @var string
		 */
		'transport' => '',

		'example' => '', // An example value for this field, will be displayed in a <code>

		/**
		 * (Optional) Sanitization callback function
		 * @since 1.9.4
		 * @var string
		 */
		'sanitize_callback' => '',
	);

	/**
	 * Default settings specific for this option. This is overridden by each option class
	 * @var array
	 */
	public $defaultSecondarySettings = array();

	public static function factory( $settings, $owner ) {
		$settings = array_merge( self::$defaultSettings, $settings );

		$className = 'TitanFrameworkOption' . str_replace( ' ', '', ucwords( str_replace( '-', ' ', $settings['type'] ) ) );

		// assume all the classes are already required
		if ( ! class_exists( $className ) && ! class_exists( $settings['type'] ) ) {
			TitanFramework::displayFrameworkError(
				sprintf( __( 'Option type or extended class %s does not exist.', TF_I18NDOMAIN ), '<code>' . $settings['type'] . '</code>', $settings ),
			$settings );
			return null;
		}

		if ( class_exists( $className ) ) {
			$obj = new $className( $settings, $owner );
			return $obj;
		}

		$className = $settings['type'];
		$obj = new $className( $settings, $owner );
		return $obj;
	}

	function __construct( $settings, $owner ) {
		$this->owner = $owner;

		$this->settings = array_merge( self::$defaultSettings, $this->defaultSecondarySettings );
		$this->settings = array_merge( $this->settings, $settings );

		$this->type = is_a( $owner, 'TitanFrameworkMetaBox' ) ? self::TYPE_META : self::TYPE_ADMIN;
		$this->type = is_a( $owner, 'TitanFrameworkCustomizer' ) ? self::TYPE_CUSTOMIZER : $this->type;

		// Generate a unique ID depending on the settings for those without IDs
		if ( empty( $this->settings['id'] ) && $this->settings['type'] != 'save' ) {
			$this->settings['id'] = substr( md5( serialize( $this->settings ) . serialize( $this->owner->settings ) ), 0, 16 );
		}
	}


	public function getValue( $postID = null ) {

		$value = false;

		if ( empty( $this->settings['id'] ) ) {
			return $value;
		}

		if ( $this->type == self::TYPE_ADMIN ) {

			$value = $this->getFramework()->getInternalAdminPageOption( $this->settings['id'], $this->settings['default'] );

		} else if ( $this->type == self::TYPE_META ) {

			if ( empty( $postID ) ) {
				$postID = $this->owner->postID;
			}
			// If no $postID is given, try and get it if we are in a loop.
			if ( empty( $postID ) && ! is_admin() && get_post() != null ) {
				$postID = get_the_ID();
			}

			// for meta options, use the default value for new posts/pages
			if ( metadata_exists( 'post', $postID, $this->getID() ) ) {
				$value = get_post_meta( $postID, $this->getID(), true );
			} else {
				$value = $this->settings['default'];
			}
		} else if ( $this->type == self::TYPE_CUSTOMIZER ) {
			$value = get_theme_mod( $this->getID(), $this->settings['default'] );
		}

		/**
		 * Allow others to change the value of the option before it gets cleaned
		 *
		 * @since 1.9.2
		 */
		$value = apply_filters( 'tf_pre_get_value_' . $this->getOptionNamespace(), $value, $postID, $this );

		// Apply cleaning method for the value (for serialized data, slashes, etc).
		$value = $this->cleanValueForGetting( $value );

		/**
		 * Allow others to change the value of the option after it gets cleaned
		 *
		 * @since 1.9
		 */
		return apply_filters( 'tf_get_value_' . $this->settings['type'] . '_' . $this->getOptionNamespace(), $value, $postID, $this );
	}


	/**
	 *
	 */
	public function setValue( $value, $postID = null ) {

		// Apply cleaning method for the value (for serialized data, slashes, etc).
		$value = $this->cleanValueForSaving( $value );

		if ( $this->type == self::TYPE_ADMIN ) {

			$this->getFramework()->setInternalAdminPageOption( $this->settings['id'], $value );

		} else if ( $this->type == self::TYPE_META ) {

			if ( empty( $postID ) ) {
				$postID = $this->owner->postID;
			}
			// If no $postID is given, try and get it if we are in a loop.
			if ( empty( $postID ) && ! is_admin() && get_post() != null ) {
				$postID = get_the_ID();
			}

			update_post_meta( $postID, $this->getID(), $value );

		} else if ( $this->type == self::TYPE_CUSTOMIZER ) {

			set_theme_mod( $this->getID(), $value );

		}

		do_action( 'tf_set_value_' . $this->settings['type'] . '_' . $this->getOptionNamespace(), $value, $postID, $this );

		return true;
	}


	/**
	 * Gets the framework instance currently used
	 *
	 * @return	TitanFramework
	 * @since	1.3
	 */
	protected function getFramework() {
		if ( is_a( $this->owner, 'TitanFrameworkAdminTab' ) ) {
			// a tab's parent is an admin panel
			return $this->owner->owner->owner;
		} else {
			// an admin panel's parent is the framework
			// a meta panel's parent is the framework
			// a theme customizer's parent is the framework
			return $this->owner->owner;
		}
	}


	/**
	 * Gets the option namespace used in the framework instance currently used
	 *
	 * @return	string The option namespace
	 * @since	1.0
	 */
	public function getOptionNamespace() {
		return $this->getFramework()->optionNamespace;
	}

	public function getID() {
		return $this->getOptionNamespace() . '_' . $this->settings['id'];
	}

	public function __call( $name, $args ) {
		$default = is_array( $args ) && count( $args ) ? $args[0] : '';
		if ( stripos( $name, 'get' ) == 0 ) {
			$setting = strtolower( substr( $name, 3 ) );
			return empty( $this->settings[ $setting ] ) ? $default : $this->settings[ $setting ];
		}
		return $default;
	}

	protected function echoOptionHeader( $showDesc = false ) {

		if ( ! $this->echo_wrapper ) {
			if ( $this->getHidden() ) {
				echo '<div style="display: none;">';
			}
			return;
		}

		// Allow overriding for custom styling
		$useCustom = false;
		$useCustom = apply_filters( 'tf_use_custom_option_header', $useCustom );
		$useCustom = apply_filters( 'tf_use_custom_option_header_' . $this->getOptionNamespace(), $useCustom );
		if ( $useCustom ) {
			do_action( 'tf_custom_option_header', $this );
			do_action( 'tf_custom_option_header_' . $this->getOptionNamespace(), $this );
			return;
		}

		$id = $this->getID();
		$name = $this->getName();
		$evenOdd = self::$rowIndex++ % 2 == 0 ? 'odd' : 'even';

		$style = $this->getHidden() == true ? 'style="display: none"' : '';

		?>
		<tr valign="top" class="row-<?php echo self::$rowIndex ?> <?php echo $evenOdd ?>" <?php echo $style ?>>
		<th scope="row" class="first">
			<label for="<?php echo ! empty( $id ) ? $id : '' ?>"><?php echo ! empty( $name ) ? $name : '' ?></label>
		</th>
		<td class="second tf-<?php echo $this->settings['type'] ?>">
		<?php

		$desc = $this->getDesc();
		if ( ! empty( $desc ) && $showDesc ) :
			?>
			<p class='description'><?php echo $desc ?></p>
			<?php
		endif;
	}

	protected function echoOptionHeaderBare() {

		if ( ! $this->echo_wrapper ) {
			if ( $this->getHidden() ) {
				echo '<div style="display: none;">';
			}
			return;
		}

		// Allow overriding for custom styling
		$useCustom = false;
		$useCustom = apply_filters( 'tf_use_custom_option_header', $useCustom );
		$useCustom = apply_filters( 'tf_use_custom_option_header_' . $this->getOptionNamespace(), $useCustom );
		if ( $useCustom ) {
			do_action( 'tf_custom_option_header', $this );
			do_action( 'tf_custom_option_header_' . $this->getOptionNamespace(), $this );
			return;
		}

		$id = $this->getID();
		$name = $this->getName();
		$evenOdd = self::$rowIndex++ % 2 == 0 ? 'odd' : 'even';

		$style = $this->getHidden() == true ? 'style="display: none"' : '';

		?>
		<tr valign="top" class="row-<?php echo self::$rowIndex ?> <?php echo $evenOdd ?>" <?php echo $style ?>>
			<td class="second tf-<?php echo $this->settings['type'] ?>">
		<?php
	}

	protected function echoOptionFooter( $showDesc = true ) {

		if ( ! $this->echo_wrapper ) {
			if ( $this->getHidden() ) {
				echo '</div>';
			}
			return;
		}

		// Allow overriding for custom styling
		$useCustom = false;
		$useCustom = apply_filters( 'tf_use_custom_option_footer', $useCustom );
		$useCustom = apply_filters( 'tf_use_custom_option_footer_' . $this->getOptionNamespace(), $useCustom );
		if ( $useCustom ) {
			do_action( 'tf_custom_option_footer', $this );
			do_action( 'tf_custom_option_footer_' . $this->getOptionNamespace(), $this );
			return;
		}

		$desc = $this->getDesc();
		if ( ! empty( $desc ) && $showDesc ) :
			?>
			<p class='description'><?php echo $desc ?></p>
			<?php
		endif;

		$example = $this->getExample();
		if ( ! empty( $example ) ) :
			?>
			<p class="description"><code><?php echo htmlentities( $example ) ?></code></p>
			<?php
		endif;

		?>
		</td>
		</tr>
		<?php
	}

	protected function echoOptionFooterBare( $showDesc = true ) {

		if ( ! $this->echo_wrapper ) {
			if ( $this->getHidden() ) {
				echo '</div>';
			}
			return;
		}

		// Allow overriding for custom styling
		$useCustom = false;
		$useCustom = apply_filters( 'tf_use_custom_option_footer', $useCustom );
		$useCustom = apply_filters( 'tf_use_custom_option_footer_' . $this->getOptionNamespace(), $useCustom );
		if ( $useCustom ) {
			do_action( 'tf_custom_option_footer', $this );
			do_action( 'tf_custom_option_footer_' . $this->getOptionNamespace(), $this );
			return;
		}

		?>
		</td>
		</tr>
		<?php
	}

	/* overridden */
	public function display() {
	}

	/* overridden */
	public function cleanValueForSaving( $value ) {
		return $value;
	}

	/* overridden */
	public function cleanValueForGetting( $value ) {
		if ( is_array( $value ) ) {
			return $value;
		}
		return stripslashes( $value );
	}

	/* overridden */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {

	}
}
