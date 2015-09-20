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

	private static $rowIndex = 0;

	private static $defaultSettings = array(
		'name' => '', // Name of the option
		'desc' => '', // Description of the option
		'id' => '', // Unique ID of the option
		'type' => 'text', //
		'default' => '', // Menu icon for top level menus only
		'example' => '', // An example value for this field, will be displayed in a <code>
		'livepreview' => '', // jQuery script to update something in the site. For theme customizer only
		'hidden' => false,
	);

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

		// remove blank settings to make it ready for merging with the defaults
		foreach ( $settings as $key => $value ) {
			if ( $value == '' ) {
				unset( $settings[ $key ] );
			}
		}

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

		// Apply cleaning method for the value (for serialized data, slashes, etc).
		$value = $this->cleanValueForGetting( $value );

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
