<?php
/**
 * Titan Framework class
 *
 * @package Titan Framework
 */

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly.
}

/**
 * TitanFramework class
 *
 * @since 1.0
 */
class TitanFramework {

	/**
	 * All TitanFramework instances
	 * @var array
	 */
	private static $instances = array();

	/**
	 * The current option namespace.
	 * Options will be prefixed with this in the database
	 * @var string
	 */
	public $optionNamespace;

	/**
	 * All main containers (admin pages, meta boxes, customizer section)
	 * @var array of TitanFrameworkAdminPage, TitanFrameworkMetaBox, & TitanFrameworkCustomizer
	 */
	private $mainContainers = array();

	/**
	 * All Google Font options used. This is for enqueuing Google Fonts for the frontend
	 * TODO Move this to the TitanFrameworkOptionSelectGooglefont class and let it enqueue from there
	 * @var array TitanFrameworkOptionSelectGooglefont
	 */
	private $googleFontsOptions = array();

	/**
	 * We store option ids which should not be created here
	 * @var array
	 * @see removeOption()
	 */
	private $optionsToRemove = array();

	/**
	 * Holds the values of all admin (page & tab) options. We need this since
	 * @var array of TitanFrameworkOption
	 */
	private $adminOptions;

	/**
	 * The CSS class instance used
	 * @var TitanFrameworkCSS
	 */
	public $cssInstance;

	/**
	 * We store the options (with IDs) here, used for ensuring our serialized option
	 * value doesn't get cluttered with unused options
	 * @var array
	 */
	public $optionsUsed = array();

	/**
	 * The current list of settings
	 * @var array
	 */
	public $settings = array();

	/**
	 * Default settings
	 * @var array
	 */
	private $defaultSettings = array(
		'css' => 'generate', 	// If 'generate', Titan will try and generate a cacheable
		                        // CSS file (or inline if it can't).
			 					// If 'inline', CSS will be printed out in the head tag,
								// If false, CSS will not be generated nor printed.
	);


	/**
	 * Gets an instance of the framework for the namespace
	 *
	 * @since 1.0
	 *
	 * @param string $optionNamespace The namespace to get options from.
	 *
	 * @return TitanFramework
	 */
	public static function getInstance( $optionNamespace ) {

		// Clean namespace.
		$optionNamespace = str_replace( ' ', '-', trim( strtolower( $optionNamespace ) ) );

		foreach ( self::$instances as $instance ) {
			if ( $instance->optionNamespace == $optionNamespace ) {
				return $instance;
			}
		}

		$newInstance = new TitanFramework( $optionNamespace );
		self::$instances[] = $newInstance;
		return $newInstance;
	}


	/**
	 * Gets all active instances of Titan Framework
	 *
	 * @since 1.9.2
	 *
	 * @return array An array of TitanFramework objects
	 */
	public static function getAllInstances() {
		return self::$instances;
	}


	/**
	 * Creates a new TitanFramework object
	 *
	 * @since 1.0
	 *
	 * @param string $optionNamespace The namespace to get options from.
	 */
	function __construct( $optionNamespace ) {

		// Clean namespace.
		$optionNamespace = str_replace( ' ', '-', trim( strtolower( $optionNamespace ) ) );

		$this->optionNamespace = $optionNamespace;
		$this->settings = $this->defaultSettings;

		do_action( 'tf_init', $this );
		do_action( 'tf_init_' . $this->optionNamespace, $this );

		$this->cssInstance = new TitanFrameworkCSS( $this );

		add_action( 'admin_enqueue_scripts', array( $this, 'loadAdminScripts' ) );
		add_action( 'tf_create_option_' . $this->optionNamespace, array( $this, 'rememberAllOptions' ) );
		add_filter( 'tf_create_option_continue_' . $this->optionNamespace, array( $this, 'removeChildThemeOptions' ), 10, 2 );

		// Create a save option filter for customizer options.
		add_filter( 'pre_update_option', array( $this, 'addCustomizerSaveFilter' ), 10, 3 );
	}


	/**
	 * Action hook on tf_create_option to remember all the options, used to ensure that our
	 * serialized option does not get cluttered with unused options
	 *
	 * @since 1.2.1
	 *
	 * @param TitanFrameworkOption $option The option that was just created.
	 *
	 * @return void
	 */
	public function rememberAllOptions( $option ) {
		if ( ! empty( $option->settings['id'] ) ) {

			if ( is_admin() && isset( $this->optionsUsed[ $option->settings['id'] ] ) ) {
				self::displayFrameworkError(
					sprintf( __( 'All option IDs per namespace must be unique. The id %s has been used multiple times.', TF_I18NDOMAIN ),
						'<code>' . $option->settings['id'] . '</code>'
					)
				);
			}

			$this->optionsUsed[ $option->settings['id'] ] = $option;
		}
	}


	/**
	 * Loads all the admin scripts used by Titan Framework
	 *
	 * @since 1.0
	 *
	 * @param string $hook The slug of admin page that called the enqueue.
	 *
	 * @return void
	 */
	public function loadAdminScripts( $hook ) {

		// Get all options panel IDs.
		$panel_ids = array();
		if ( ! empty( $this->mainContainers['admin-page'] ) ) {
			foreach ( $this->mainContainers['admin-page'] as $admin_panel ) {
				$panel_ids[] = $admin_panel->panelID;
			}
		}

		// Only enqueue scripts if we're on a Titan options page.
		if ( in_array( $hook, $panel_ids ) || ! empty( $this->mainContainers['meta-box'] ) ) {
			wp_enqueue_media();
			wp_enqueue_script( 'tf-serialize', TitanFramework::getURL( '../js/min/serialize-min.js', __FILE__ ) );
			wp_enqueue_script( 'tf-styling', TitanFramework::getURL( '../js/min/admin-styling-min.js', __FILE__ ) );
			wp_enqueue_style( 'tf-admin-styles', TitanFramework::getURL( '../css/admin-styles.css', __FILE__ ) );
		}
	}


	/**
	 * Gets all the admin page options (not meta & customizer) and loads them from the database into
	 * a class variable. This is needed because all our admin page options are contained in a single entry.
	 *
	 * @since 1.9
	 *
	 * @return array All admin options currently in the instance
	 */
	protected function getInternalAdminOptions() {
		if ( empty( $this->adminOptions ) ) {
			$this->adminOptions = array();
		}

		if ( ! empty( $this->adminOptions ) ) {
			return $this->adminOptions;
		}

		// Check if we have options saved already.
		$currentOptions = get_option( $this->optionNamespace . '_options' );

		// First time run, this action hook can be used to trigger something.
		if ( false === $currentOptions ) {
			do_action( 'tf_init_no_options_' . $this->optionNamespace );
		}

		// Put all the available options in our global variable for future checking.
		if ( ! empty( $currentOptions ) && ! count( $this->adminOptions ) ) {
			$this->adminOptions = unserialize( $currentOptions );
		}

		if ( empty( $this->adminOptions ) ) {
			$this->adminOptions = array();
		}

		return $this->adminOptions;
	}


	/**
	 * Gets the admin page option that's loaded into the instance, used by the option class
	 *
	 * @since 1.9
	 *
	 * @param string $optionName The ID of the option (not namespaced).
	 * @param mixed  $defaultValue The default value to return if the option isn't available yet.
	 *
	 * @return mixed The option value
	 *
	 * @see TitanFrameworkOption->getValue()
	 */
	public function getInternalAdminPageOption( $optionName, $defaultValue = false ) {

		// Run this first to ensure that adminOptions carries all our admin page options.
		$this->getInternalAdminOptions();

		if ( array_key_exists( $optionName, $this->adminOptions ) ) {
			return $this->adminOptions[ $optionName ];
		} else {
			return $defaultValue;
		}
	}


	/**
	 * Sets the admin page option that's loaded into the instance, used by the option class.
	 * Doesn't perform a save, only sets the value in the class variable.
	 *
	 * @since 1.9
	 *
	 * @param string $optionName The ID of the option (not namespaced).
	 * @param mixed  $value The value to set.
	 *
	 * @return bool Always returns true
	 *
	 * @see TitanFrameworkOption->setValue()
	 */
	public function setInternalAdminPageOption( $optionName, $value ) {

		// Run this first to ensure that adminOptions carries all our admin page options.
		$this->getInternalAdminOptions();

		$this->adminOptions[ $optionName ] = $value;
		return true;
	}


	/**
	 * Saves all the admin (not meta & customizer) options which are currently loaded into this instance
	 *
	 * @since 1.0
	 *
	 * @return array All admin options currently in the instance
	 */
	public function saveInternalAdminPageOptions() {

		// Run this first to ensure that adminOptions carries all our admin page options.
		$this->getInternalAdminOptions();

		update_option( $this->optionNamespace . '_options', serialize( $this->adminOptions ) );
		do_action( 'tf_save_options_' . $this->optionNamespace );
		return $this->adminOptions;
	}


	/**
	 * Create a admin page
	 *
	 * @deprecated 1.9 Use createContainer() with 'type' => 'admin-page' or createAdminPanel() instead.
	 * @since 1.0
	 *
	 * @param array $settings The arguments for creating the admin page.
	 *
	 * @return TitanFrameworkAdminPage The created admin page
	 */
	public function createAdminPanel( $settings ) {
		// _deprecated_function( __FUNCTION__, '1.9', 'createAdminPage' );
		return $this->createAdminPage( $settings );
	}


	/**
	 * Create a admin page
	 *
	 * @since 1.0
	 *
	 * @param array $settings The arguments for creating the admin page.
	 *
	 * @return TitanFrameworkAdminPage The created admin page
	 */
	public function createAdminPage( $settings ) {
		$settings['type'] = 'admin-page';
		$container = $this->createContainer( $settings );
		do_action( 'tf_admin_panel_created_' . $this->optionNamespace, $container );
		return $container;
	}


	/**
	 * Create a meta box
	 *
	 * @since 1.0
	 *
	 * @param array $settings The arguments for creating the meta box.
	 *
	 * @return TitanFrameworkMetaBox The created meta box
	 */
	public function createMetaBox( $settings ) {
		$settings['type'] = 'meta-box';
		return $this->createContainer( $settings );
	}


	/**
	 * Create a customizer section
	 *
	 * @deprecated 1.9 Use createContainer() with 'type' => 'customizer' or createCustomizer instead.
	 * @since 1.0
	 *
	 * @param array $settings The arguments for creating a customizer section.
	 *
	 * @return TitanFrameworkCustomizer The created section
	 */
	public function createThemeCustomizerSection( $settings ) {
		// _deprecated_function( __FUNCTION__, '1.9', 'createContainer' );
		return $this->createCustomizer( $settings );
	}


	/**
	 * Create a customizer section
	 *
	 * @since 1.9
	 *
	 * @param array $settings The arguments for creating a customizer section.
	 *
	 * @return TitanFrameworkCustomizer The created section
	 */
	public function createCustomizer( $settings ) {
		$settings['type'] = 'customizer';
		$container = $this->createContainer( $settings );
		do_action( 'tf_theme_customizer_created_' . $this->optionNamespace, $container );
		return $container;
	}


	/**
	 * Creates a container (e.g. admin page, meta box, customizer section) depending
	 * on the `type` parameter given in $settings
	 *
	 * @since 1.9
	 *
	 * @param array $settings The arguments for creating the container.
	 *
	 * @return TitanFrameworkCustomizer|TitanFrameworkAdminPage|TitanFrameworkMetaBox The created container
	 */
	public function createContainer( $settings ) {
		if ( empty( $settings['type'] ) ) {
			self::displayFrameworkError( sprintf( __( '%s needs a %s parameter.', TF_I18NDOMAIN ), '<code>' . __FUNCTION__ . '</code>', '<code>type</code>' ) );
			return;
		}

		$type = strtolower( $settings['type'] );
		$class = 'TitanFramework' . str_replace( ' ', '', ucfirst( str_replace( '-', ' ', $settings['type'] ) ) );
		$action = str_replace( '-', '_', $type );
		$container = false;

		if ( ! class_exists( $class ) ) {
			self::displayFrameworkError( sprintf( __( 'Container of type %s, does not exist.', TF_I18NDOMAIN ), '<code>' . $settings['type'] . '</code>' ) );
			return;
		}

		// Create the container object.
		$container = new $class( $settings, $this );
		if ( empty( $this->mainContainers[ $type ] ) ) {
			$this->mainContainers[ $type ] = array();
		}

		$this->mainContainers[ $type ][] = $container;

		do_action( 'tf_' . $action . '_created_' . $this->optionNamespace, $container );

		return $container;
	}


	/**
	 * A function available ONLY to CHILD themes to stop the creation of options
	 * created by the PARENT theme.
	 *
	 * @since 1.2.1
	 * @access public
	 *
	 * @param string $optionName The id of the option to remove / stop from being created.
	 *
	 * @return void
	 */
	public function removeOption( $optionName ) {
		$this->optionsToRemove[] = $optionName;
	}


	/**
	 * Hook to the tf_create_option_continue filter, to check whether or not to continue
	 * adding an option (if the option id was used in $titan->removeOption).
	 *
	 * @since 1.2.1
	 * @access public
	 *
	 * @param boolean $continueCreating If true, the option will be created.
	 * @param array   $optionSettings The settings for the option to be created.
	 *
	 * @return boolean If true, continue with creating the option. False to stop it..
	 */
	public function removeChildThemeOptions( $continueCreating, $optionSettings ) {
		if ( ! count( $this->optionsToRemove ) ) {
			return $continueCreating;
		}
		if ( empty( $optionSettings['id'] ) ) {
			return $continueCreating;
		}
		if ( in_array( $optionSettings['id'], $this->optionsToRemove ) ) {
			return false;
		}
		return $continueCreating;
	}


	/**
	 * Get an option
	 *
	 * @since 1.0
	 *
	 * @param string $optionName The name of the option.
	 * @param int    $postID The post ID if this is a meta option.
	 *
	 * @return mixed The option value
	 */
	public function getOption( $optionName, $postID = null ) {
		$value = false;

		// Get the option value.
		if ( array_key_exists( $optionName, $this->optionsUsed ) ) {
			$option = $this->optionsUsed[ $optionName ];
			$value = $option->getValue( $postID );
		}

		return apply_filters( 'tf_get_option_' . $this->optionNamespace, $value, $optionName, $postID );
	}


	/**
	 * Gets a set of options. Pass an associative array containing the option names as keys and
	 * the values you want to be retained if the option names are not implemented.
	 *
	 * @since 1.9
	 *
	 * @param array $optionArray An associative array containing option names as keys.
	 * @param int   $postID The post ID if this is a meta option.
	 *
	 * @return array An array containing the values saved.
	 *
	 * @see $this->getOption()
	 */
	public function getOptions( $optionArray, $postID = null ) {
		foreach ( $optionArray as $optionName => $originalValue ) {
			if ( array_key_exists( $optionName, $this->optionsUsed ) ) {
				$optionArray[ $optionName ] = $this->getOption( $optionName, $postID );
			}
		}
		return apply_filters( 'tf_get_options_' . $this->optionNamespace, $optionArray, $postID );
	}


	/**
	 * Sets an option
	 *
	 * @since 1.0
	 *
	 * @param string $optionName The name of the option to save.
	 * @param mixed  $value The value of the option.
	 * @param int    $postID The ID of the parent post if this is a meta box option.
	 *
	 * @return boolean Always returns true
	 */
	public function setOption( $optionName, $value, $postID = null ) {

		// Get the option value.
		if ( array_key_exists( $optionName, $this->optionsUsed ) ) {
			$option = $this->optionsUsed[ $optionName ];
			$option->setValue( $value, $postID );
		}

		do_action( 'tf_set_option_' . $this->optionNamespace, $optionName, $value, $postID );

		return true;
	}


	/**
	 * Deletes ALL the options for the namespace. Even deletes all meta found in all posts.
	 * Mainly used for unit tests
	 *
	 * @since 1.9
	 *
	 * @return void
	 */
	public function deleteAllOptions() {

		// Delete all admin options.
		delete_option( $this->optionNamespace . '_options' );
		$this->adminOptions = array();

		// Delete all meta options.
		global $wpdb;
		$allPosts = $wpdb->get_results( 'SELECT ID FROM ' . $wpdb->posts, ARRAY_A );
		if ( ! empty( $allPosts ) ) {
			foreach ( $allPosts as $row ) {
				$allMeta = get_post_meta( $row['ID'] );

				// Only remove meta data that the framework created.
				foreach ( $allMeta as $metaName => $dummy ) {
					if ( stripos( $metaName, $this->optionNamespace . '_' ) === 0 ) {
						delete_post_meta( $row['ID'], $metaName );
					}
				}
			}
		}

		// Delete all theme mods.
		$allThemeMods = get_theme_mods();
		if ( ! empty( $allThemeMods ) && is_array( $allThemeMods ) ) {
			foreach ( $allThemeMods as $optionName => $dummy ) {

				// Only remove theme mods that the framework created.
				if ( stripos( $optionName, $this->optionNamespace . '_' ) === 0 ) {
					remove_theme_mod( $optionName );
				}
			}
		}
	}


	/**
	 * Generates style rules which can use options as their values
	 *
	 * @since 1.0
	 *
	 * @param string $CSSString The styles to render.
	 *
	 * @return void
	 */
	public function createCSS( $CSSString ) {
		$this->cssInstance->addCSS( $CSSString );
	}


	/**
	 * Displays an error notice
	 *
	 * @since 1.0
	 *
	 * @param string       $message The error message to display.
	 * @param array|object $errorObject The object to dump inside the error message.
	 *
	 * @return void
	 */
	public static function displayFrameworkError( $message, $errorObject = null ) {
		// Clean up the debug object for display. e.g. If this is a setting, we can have lots of blank values.
		if ( is_array( $errorObject ) ) {
			foreach ( $errorObject as $key => $val ) {
				if ( '' === $val ) {
					unset( $errorObject[ $key ] );
				}
			}
		}

		// Display an error message.
		?>
		<div style='margin: 20px; text-align: center;'><strong><?php echo TF_NAME ?> Error:</strong>
			<?php echo $message ?>
			<?php
			if ( ! empty( $errorObject ) ) :
				?>
				<pre><code style="display: inline-block; padding: 10px"><?php echo print_r( $errorObject, true ) ?></code></pre>
				<?php
			endif;
			?>
		</div>
		<?php
	}


	/**
	 * Acts the same way as plugins_url( 'script', __FILE__ ) but returns then correct url
	 * when called from inside a theme.
	 *
	 * @since 1.1.2
	 *
	 * @param string $script the script to get the url to, relative to $file.
	 * @param string $file the current file, should be __FILE__.
	 *
	 * @return string the url to $script
	 */
	public static function getURL( $script, $file ) {
		$parentTheme = trailingslashit( get_template_directory() );
		$childTheme = trailingslashit( get_stylesheet_directory() );
		$plugin = trailingslashit( dirname( $file ) );

		// Windows sometimes mixes up forward and back slashes, ensure forward slash for correct URL output.
		$parentTheme = str_replace( '\\', '/', $parentTheme );
		$childTheme = str_replace( '\\', '/', $childTheme );
		$file = str_replace( '\\', '/', $file );

		$url = '';

		// Framework is in a parent theme.
		if ( stripos( $file, $parentTheme ) !== false ) {
			$dir = trailingslashit( dirname( str_replace( $parentTheme, '', $file ) ) );
			if ( './' == $dir ) {
				$dir = '';
			}
			$url = trailingslashit( get_template_directory_uri() ) . $dir . $script;

		} else if ( stripos( $file, $childTheme ) !== false ) {
			// Framework is in a child theme.
			$dir = trailingslashit( dirname( str_replace( $childTheme, '', $file ) ) );
			if ( './' == $dir ) {
				$dir = '';
			}
			$url = trailingslashit( get_stylesheet_directory_uri() ) . $dir . $script;

		} else {
			// Framework is a or in a plugin.
			$url = plugins_url( $script, $file );
		}

		// Replace /foo/../ with '/'.
		$url = preg_replace( '/\/(?!\.\.)[^\/]+\/\.\.\//', '/', $url );

		return $url;
	}


	/**
	 * Sets a value in the $setting class variable
	 *
	 * @since 1.6
	 *
	 * @param string $setting The name of the setting.
	 * @param string $value The value to set.
	 *
	 * @return void
	 */
	public function set( $setting, $value ) {
		$oldValue = $this->settings[ $setting ];
		$this->settings[ $setting ] = $value;

		do_action( 'tf_setting_' . $setting . '_changed_' . $this->optionNamespace, $value, $oldValue );
	}


	/**
	 * Gets the CSS generated
	 *
	 * @since 1.6
	 *
	 * @return string The generated CSS
	 */
	public function generateCSS() {
		return $this->cssInstance->generateCSS();
	}



	/**
	 * Adds a 'tf_save_option_{namespace}_{optionID}' filter to all Customizer options
	 * which are just about to be saved
	 *
	 * This uses the `pre_update_option` filter to check all the options being saved if it's
	 * a theme_mod option. It further checks whether these are Titan customizer options,
	 * then attaches the new hook into those.
	 *
	 * @since 1.8
	 *
	 * @param mixed  $value The value to be saved in the options.
	 * @param string $optionName The option name.
	 * @param mixed  $oldValue The previously stored value.
	 *
	 * @return mixed The modified value to save
	 *
	 * @see pre_update_option filter
	 */
	public function addCustomizerSaveFilter( $value, $optionName, $oldValue ) {

		$theme = get_option( 'stylesheet' );

		// Intercept theme mods only.
		if ( strpos( $optionName, 'theme_mods_' . $theme ) !== 0 ) {
			return $value;
		}

		// We expect theme mods to be an array.
		if ( ! is_array( $value ) ) {
			return $value;
		}

		// Checks whether a Titan customizer is in place.
		$customizerUsed = false;

		// Go through all our customizer options and filter them for saving.
		$optionIDs = array();
		if ( ! empty( $this->mainContainers['customizer'] ) ) {
			foreach ( $this->mainContainers['customizer'] as $customizer ) {
				foreach ( $customizer->options as $option ) {
					if ( ! empty( $option->settings['id'] ) ) {
						$optionID = $option->settings['id'];
						$themeModName = $this->optionNamespace . '_' . $option->settings['id'];

						if ( ! array_key_exists( $themeModName, $value ) ) {
							continue;
						}

						$customizerUsed = true;

						// Try and unserialize if possible.
						$tempValue = $value[ $themeModName ];
						if ( is_serialized( $tempValue ) ) {
							$tempValue = unserialize( $tempValue );
						}

						// Hook 'tf_save_option_{namespace}'.
						$newValue = apply_filters( 'tf_save_option_' . $this->optionNamespace, $tempValue, $option->settings['id'] );

						// Hook 'tf_save_option_{namespace}_{optionID}'.
						$newValue = apply_filters( 'tf_save_option_' . $themeModName, $tempValue );

						// We mainly check for equality here so that we won't have to serialize IF the value wasn't touched anyway.
						if ( $newValue != $tempValue ) {
							if ( is_array( $newValue ) ) {
								$newValue = serialize( $newValue );
							}

							$value[ $themeModName ] = $newValue;
						}
					}
				}
			}
		}

		if ( $customizerUsed ) {
			/** This action is documented in class-admin-page.php */
			$namespace = $this->optionNamespace;
			do_action( "tf_pre_save_options_{$namespace}", $this->mainContainers['customizer'] );
		}

		return $value;
	}
}
