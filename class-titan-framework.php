<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class TitanFramework {

	public $optionNamespace; // Options will be prefixed with this

	private $adminPanels = array();
	private $metaBoxes = array();
	private $themeCustomizerSections = array();
	private $widgetAreas = array();
	private $googleFontsOptions = array();
	public $settings = array();

	// We store option ids which should not be created here (see removeOption)
	private $optionsToRemove = array();

	private static $instances = array();
	private $allOptionIDs = array();
	private $allOptions;

	public $cssInstance;
	public $trackerInstance;

	// We have an initialization phase where the options are just being gathered
	// for processing, during this phase we need to stop certain steps when creat
	public static $initializing = false;

	// We store the options (with IDs) here, used for ensuring our serialized option
	// value doesn't get cluttered with unused options
	public $optionsUsed = array();

	private $defaultSettings = array(
		'css' => 'generate', 	// If 'generate', Titan will try and generate a cacheable CSS file (or inline if it can't).
			 					// If 'inline', CSS will be printed out in the head tag,
								// If false, CSS will not be generated nor printed
		'tracking' => false, 	// TODO: Turn to true, when code is finalized for 1.6
	);

	public static function getInstance( $optionNamespace ) {
		// Clean namespace
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

	function __construct( $optionNamespace ) {
		// Clean namespace
		$optionNamespace = str_replace( ' ', '-', trim( strtolower( $optionNamespace ) ) );

		$this->optionNamespace = $optionNamespace;
		$this->settings = $this->defaultSettings;

		do_action( 'tf_init', $this );
		do_action( 'tf_init_' . $this->optionNamespace, $this );

		$this->cssInstance = new TitanFrameworkCSS( $this );
		$this->trackerInstance = new TitanFrameworkTracker( $this );

		add_action( 'after_setup_theme', array( $this, 'getAllOptions' ), 7 );
		add_action( 'init', array( $this, 'updateOptionDBListing' ), 12 );

		if ( is_admin() ) {
			add_action( 'init', array( $this, 'updateThemeModListing' ), 12 );
			add_action( 'init', array( $this, 'updateMetaDbListing' ), 12 );
			add_action( 'tf_create_option_' . $this->optionNamespace, array( $this, "verifyUniqueIDs" ) );
		}

		add_action( 'admin_enqueue_scripts', array( $this, "loadAdminScripts" ) );
		add_action( 'wp_enqueue_scripts', array( $this, "loadFrontEndScripts" ) );
		add_action( 'tf_create_option_' . $this->optionNamespace, array( $this, "rememberGoogleFonts" ) );
		add_action( 'tf_create_option_' . $this->optionNamespace, array( $this, "rememberAllOptions" ) );
		add_filter( 'tf_create_option_continue_' . $this->optionNamespace, array( $this, "removeChildThemeOptions" ), 10, 2 );
		
		// Create a save option filter for customizer options
		add_filter( 'pre_update_option', array( $this, 'addCustomizerSaveFilter' ), 10, 3 );
	}

	/**
	 * Checks all the ids and shows a warning when multiple occurances of an id is found.
	 * This is to ensure that there won't be any option conflicts
	 *
	 * @param   TitanFrameworkOption $option The object just created
	 * @return  void
	 * @since   1.1.1
	 */
	public function verifyUniqueIDs( $option ) {
		if ( empty( $option->settings['id'] ) ) {
			return;
		}

		// During initialization don't display ID errors
		if ( self::$initializing ) {
			return;
		}

		if ( in_array( $option->settings['id'], $this->allOptionIDs ) ) {
			self::displayFrameworkError(
				sprintf( __( 'All option IDs must be unique. The id %s has been used multiple times.', TF_I18NDOMAIN ),
					'<code>' . $option->settings['id'] . '</code>'
				)
			);
		} else {
			$this->allOptionIDs[] = $option->settings['id'];
		}
	}

	public function rememberGoogleFonts( $option ) {
		if ( is_a( $option, 'TitanFrameworkOptionSelectGooglefont' ) ) {
			if ( $option->settings['enqueue'] ) {
				$this->googleFontsOptions[] = $option;
			}
		}
	}


	/**
	 * Action hook on tf_create_option to remember all the options, used to
	 * ensure that our serialized option does not get cluttered with unused
	 * options
	 *
	 * @access  public
	 * @param	TitanFrameworkOption $option The option that was just created
	 * @return	void
	 * @since   1.2.1
	 */
	public function rememberAllOptions( $option ) {
		if ( ! empty( $option->settings['id'] ) ) {
			$this->optionsUsed[ $option->settings['id'] ] = $option;
		}
	}

	public function loadFrontEndScripts() {
		foreach ( $this->googleFontsOptions as $googleFontOption ) {
			$font = $this->getOption( $googleFontOption->settings['id'] );
			if ( empty( $font ) ) {
				continue;
			}
			wp_enqueue_style(
				'tf-google-webfont-' . strtolower( str_replace( ' ', '-', $font['name'] ) ),
				TitanFrameworkOptionSelectGooglefont::formScript( $font )
			);
		}
	}

	public function loadAdminScripts( $hook ) {
		// Get all options panel IDs
		$panel_ids = array();
		foreach ( $this->adminPanels as $admin_panel ) {
			$panel_ids[] = $admin_panel->panelID;
		}

		// Only enqueue scripts if we're on a Titan options page
		if ( in_array( $hook, $panel_ids ) || count($this->metaBoxes) ) {
			wp_enqueue_media();
			wp_enqueue_script( 'tf-serialize', TitanFramework::getURL( 'js/serialize.js', __FILE__ ) );
			wp_enqueue_script( 'tf-styling', TitanFramework::getURL( 'js/admin-styling.js', __FILE__ ) );
			wp_enqueue_style( 'tf-admin-styles', TitanFramework::getURL( 'css/admin-styles.css', __FILE__ ) );
		}
	}

	public function getAllOptions() {
		if ( empty( $this->allOptions ) ) {
			$this->allOptions = array();
		}

		if ( empty( $this->allOptions[$this->optionNamespace] ) ) {
			$this->allOptions[$this->optionNamespace] = array();
		} else {
			return $this->allOptions[$this->optionNamespace];
		}

		// Check if we have options saved already
		$currentOptions = get_option( $this->optionNamespace . '_options' );

		// First time run, this action hook can be used to trigger something
		if ( $currentOptions === false ) {
			do_action( 'tf_init_no_options_' . $this->optionNamespace );
		}

		// Put all the available options in our global variable for future checking
		if ( ! empty( $currentOptions ) && ! count( $this->allOptions[$this->optionNamespace] ) ) {
			$this->allOptions[$this->optionNamespace] = unserialize( $currentOptions );
		}

		return $this->allOptions[$this->optionNamespace];
	}

	public function saveOptions() {
		update_option( $this->optionNamespace . '_options', serialize( $this->allOptions[$this->optionNamespace] ) );
		do_action( 'tf_save_options_' . $this->optionNamespace );
		return $this->allOptions[$this->optionNamespace];
	}

	/*
	 * Cleans up the meta options in the database for our namespace.
	 * Remove unused stuff and add in the default values for new stuff
	 */
	public function updateMetaDbListing() {
		// TODO
	}

	/*
	 * Cleans up the theme mods in the database for our namespace.
	 * Remove unused stuff and add in the default values for new stuff
	 */
	public function updateThemeModListing() {
		$allThemeMods = get_theme_mods();

		// For fresh installs there won't be any theme mods yet
		if ( $allThemeMods === false ) {
			$allThemeMods = array();
		}

		$allThemeModKeys = array_fill_keys( array_keys( $allThemeMods ), null );

		// Check existing theme mods
		foreach ( $this->themeCustomizerSections as $section ) {
			foreach ( $section->options as $option ) {
				if ( ! isset( $allThemeMods[$option->getID()] ) ) {
					set_theme_mod( $option->getID(), $option->settings['default'] );
				}

				unset( $allThemeModKeys[$option->getID()] );
			}
		}

		// Remove all unused theme mods
		if ( count( $allThemeModKeys ) ) {
			foreach ( $allThemeModKeys as $optionName => $dummy ) {
				// Only remove theme mods that the framework created
				if ( stripos( $optionName, $this->optionNamespace . '_' ) === 0 ) {
					remove_theme_mod( $optionName );
				}
			}
		}
	}

	/*
	 * Cleans up the options present in the database for our namespace.
	 * Remove unused stuff and add in the default values for new stuff
	 */
	public function updateOptionDBListing() {
		// Get also a list of all option keys
		$allOptionKeys = array();
		if ( ! empty( $this->allOptions[$this->optionNamespace] ) ) {
			$allOptionKeys = array_fill_keys( array_keys( $this->allOptions[ $this->optionNamespace ] ), null );
		}

		// Check whether options have changed / added
		$changed = false;
		foreach ( $this->adminPanels as $panel ) {
			// Check existing options
			foreach ( $panel->options as $option ) {
				if ( empty( $option->settings['id'] ) ) {
					continue;
				}
				if ( ! isset( $this->allOptions[$this->optionNamespace][$option->settings['id']] ) ) {
					$this->allOptions[$this->optionNamespace][$option->settings['id']] = $option->settings['default'];
					$changed = true;
				}
				unset( $allOptionKeys[$option->settings['id']] );

				// Clean the value for retrieval
				$this->allOptions[$this->optionNamespace][$option->settings['id']] =
					$option->cleanValueForGetting( $this->allOptions[$this->optionNamespace][$option->settings['id']] );
			}
			// Check existing options
			foreach ( $panel->tabs as $tab ) {
				foreach ( $tab->options as $option ) {
					if ( empty( $option->settings['id'] ) ) {
						continue;
					}
					if ( ! isset( $this->allOptions[$this->optionNamespace][$option->settings['id']] ) ) {
						$this->allOptions[$this->optionNamespace][$option->settings['id']] = $option->settings['default'];
						$changed = true;
					}
					unset( $allOptionKeys[$option->settings['id']] );

					// Clean the value for retrieval
					$this->allOptions[$this->optionNamespace][$option->settings['id']] =
						$option->cleanValueForGetting( $this->allOptions[$this->optionNamespace][$option->settings['id']] );
				}
			}
		}

		// Remove all unused keys
		if ( count( $allOptionKeys ) ) {
			foreach ( $allOptionKeys as $optionName => $dummy ) {
				unset( $this->allOptions[$this->optionNamespace][$optionName] );
			}
			$changed = true;
		}

		// New options have been added, save the default values
		if ( $changed ) {
			update_option( $this->optionNamespace . '_options', serialize( $this->allOptions[$this->optionNamespace] ) );
		}
	}

	public function createAdminPanel( $settings ) {
		$obj = new TitanFrameworkAdminPanel( $settings, $this );
		$this->adminPanels[] = $obj;

		do_action( 'tf_admin_panel_created_' . $this->optionNamespace, $obj );

		return $obj;
	}

	public function createMetaBox( $settings ) {
		$obj = new TitanFrameworkMetaBox( $settings, $this );
		$this->metaBoxes[] = $obj;

		do_action( 'tf_meta_box_created_' . $this->optionNamespace, $obj );

		return $obj;
	}

	public function createThemeCustomizerSection( $settings ) {
		$obj = new TitanFrameworkThemeCustomizerSection( $settings, $this );
		$this->themeCustomizerSections[] = $obj;

		do_action( 'tf_theme_customizer_created_' . $this->optionNamespace, $obj );

		return $obj;
	}


	/**
	 * A function available ONLY to CHILD themes to stop the creation of options
	 * created by the PARENT theme.
	 *
	 * @access  public
	 * @param	string $optionName The id of the option to remove / stop from being created
	 * @return  void
	 * @since   1.2.1
	 */
	public function removeOption( $optionName ) {
		$this->optionsToRemove[] = $optionName;
	}


	/**
	 * Hook to the tf_create_option_continue filter, to check whether or not to continue
	 * adding an option (if the option id was used in $titan->removeOption).
	 *
	 * @access  public
	 * @param	boolean $continueCreating If true, the option will be created
	 * @param	array $optionSettings The settings for the option to be created
	 * @return  boolean If true, continue with creating the option. False to stop it.
	 * @since   1.2.1
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

	public function getOption( $optionName, $postID = null ) {
		$value = null;

		// Get the option value
		if ( array_key_exists( $optionName, $this->optionsUsed ) ) {
			$option = $this->optionsUsed[ $optionName ];

			// Admin page options
			if ( $option->type == TitanFrameworkOption::TYPE_ADMIN ) {

				// this is blank if called too early. getOption should be called inside a hook or template
				if ( ! is_array( $this->allOptions ) ) {
					self::displayFrameworkError( sprintf( __( 'Wrong usage of %s, this should be called inside a hook or from within a theme file.', TF_I18NDOMAIN ), '<code>getOption</code>' ) );
					return null;
				}

				$value = $this->allOptions[ $this->optionNamespace ][ $optionName ];


			// Meta box options
			} else if ( $option->type == TitanFrameworkOption::TYPE_META ) {

				// If no $postID is given, try and get it if we are in a loop
				if ( empty( $postID ) && ! is_admin() ) {
					if ( get_post() != null ) {
						$postID = get_the_ID();
					}
				}

				$value = get_post_meta( $postID, $this->optionNamespace . '_' . $optionName, true );


			// Theme customizer options
			} else if ( $option->type == TitanFrameworkOption::TYPE_CUSTOMIZER ) {
				$value = get_theme_mod( $this->optionNamespace . '_' . $optionName );

			}
		}

		// Apply cleaning method for the value (for serialized data, slashes, etc)
		if ( $value !== null ) {
			if ( ! empty( $this->optionsUsed[$optionName] ) ) {
				$value = $this->optionsUsed[$optionName]->cleanValueForGetting( $value );
			}
		}
		return $value;
	}

	public function setOption( $optionName, $value, $postID = null ) {
		// Apply cleaning method for the value (for serialized data, slashes, etc)
		if ( ! empty( $this->optionsUsed[$optionName] ) ) {
			$value = $this->optionsUsed[$optionName]->cleanValueForSaving( $value );
		}

		// Call to 'tf_save_option_{namespace}', $value contains the current value about to be saved
		// This is for admin panel & post meta options. Customizer settings are filtered by addCustomizerSaveFilter() below
		$value = apply_filters( 'tf_save_option_' . $this->optionNamespace, $value, $optionName );

		// Call to 'tf_save_option_{namespace}_{optionID}', $value contains the current value about to be saved
		// This is for admin panel & post meta options. Customizer settings are filtered by addCustomizerSaveFilter() below
		$value = apply_filters( 'tf_save_option_' . $this->optionNamespace . '_' . $optionName, $value );

		if ( empty( $postID ) ) {
			// option

			if ( ! is_array( $this->allOptions ) ) {
				// this is blank if called too early. getOption should be called inside a hook or template
				self::displayFrameworkError( sprintf( __( 'Wrong usage of %s, this should be called inside a hook or from within a theme file.', TF_I18NDOMAIN ), '<code>setOption</code>' ) );
				return '';
			}

			if ( array_key_exists( $optionName, $this->allOptions[$this->optionNamespace] ) ) {
				$this->allOptions[$this->optionNamespace][$optionName] = $value;
			} else {
				// customizer
				set_theme_mod( $this->optionNamespace . '_' . $optionName, $value );
			}
		} else {
			// meta
			return update_post_meta( $postID, $this->optionNamespace . '_' . $optionName, $value );
		}
		return $value;
	}

	public function createWidgetArea( $settings ) {
		$obj = new TitanFrameworkWidgetArea( $settings, $this );
		$this->widgetAreas[] = $obj;
		return $obj;
	}

	public function createCSS( $CSSString ) {
		$this->cssInstance->addCSS( $CSSString );
	}

	public function createShortcode( $settings ) {
		do_action( 'tf_create_shortcode', $settings );
		do_action( 'tf_create_shortcode_' . $this->optionNamespace, $settings );
	}

	public static function displayFrameworkError( $message, $errorObject = null ) {
		// Clean up the debug object for display. e.g. If this is a setting, we can have lots of blank values
		if ( is_array( $errorObject ) ) {
			foreach ( $errorObject as $key => $val ) {
				if ( $val === '' ) {
					unset( $errorObject[$key] );
				}
			}
		}

		// Display an error message
		?>
		<div style='margin: 20px'><strong><?php echo TF_NAME ?> Error:</strong>
			<?php echo $message ?>
			<?php
			if ( ! empty( $errorObject ) ):
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
	 * @param   string $script the script to get the url to, relative to $file
	 * @param   string $file the current file, should be __FILE__
	 * @return  string the url to $script
	 * @since   1.1.2
	 */
	public static function getURL( $script, $file ) {
		$parentTheme = trailingslashit( get_template_directory() );
		$childTheme = trailingslashit( get_stylesheet_directory() );
		$plugin = trailingslashit( dirname( $file ) );

		// Windows sometimes mixes up forward and back slashes, ensure forward slash for
		// correct URL output
		$parentTheme = str_replace( '\\', '/', $parentTheme );
		$childTheme = str_replace( '\\', '/', $childTheme );
		$file = str_replace( '\\', '/', $file );

		// framework is in a parent theme
		if ( stripos( $file, $parentTheme ) !== false ) {
			$dir = trailingslashit( dirname( str_replace( $parentTheme, '', $file ) ) );
			if ( $dir == './' ) {
				$dir = '';
			}
			return trailingslashit( get_template_directory_uri() ) . $dir . $script;
		// framework is in a child theme
		} else if ( stripos( $file, $childTheme ) !== false ) {
			$dir = trailingslashit( dirname( str_replace( $childTheme, '', $file ) ) );
			if ( $dir == './' ) {
				$dir = '';
			}
			return trailingslashit( get_stylesheet_directory_uri() ) . $dir . $script;
		}
		// framework is a or in a plugin
		return plugins_url( $script, $file );
	}


	/**
	 * Sets a value in the $setting class variable
	 *
	 * @param   string $setting The name of the setting
	 * @param   string $value The value to set
	 * @return  void
	 * @since   1.6
	 */
	public function set( $setting, $value ) {
		$oldValue = $this->settings[ $setting ];
		$this->settings[ $setting ] = $value;

		do_action( 'tf_setting_' . $setting . '_changed_' . $this->optionNamespace, $value, $oldValue );
	}


	/**
	 * Gets the CSS generated
	 *
	 * @return  string The generated CSS
	 * @since   1.6
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
	 * @param	$value mixed The value to be saved in the options
	 * @param	$optionName string The option name
	 * @param	$oldValue mixed The previously stored value
	 * @return	mixed The modified value to save
	 * @since   1.8
	 * @see		pre_update_option filter
	 */
	public function addCustomizerSaveFilter( $value, $optionName, $oldValue ) {
		
		$theme = get_option( 'stylesheet' );

		// Intercept theme mods only
		if ( ! preg_match( '/^theme_mods_' . $theme . '/', $optionName ) ) {
			return $value;
		}
		
		// We expect theme mods to be an array
		if ( ! is_array( $value ) ) {
			return $value;
		}
		
		// Checks whether a Titan customizer is in place
		$customizerUsed = false;
		
		// Go through all our customizer options and filter them for saving
		$optionIDs = array();
		foreach ( $this->themeCustomizerSections as $customizer ) {
			foreach ( $customizer->options as $option ) {
				if ( ! empty( $option->settings['id'] ) ) {
					$optionID = $option->settings['id'];
					$themeModName = $this->optionNamespace . '_' . $option->settings['id'];
					
					if ( ! array_key_exists( $themeModName, $value ) ) {
						continue;
					}
					
					$customizerUsed = true;
					
					// Try and unserialize if possible
					$tempValue = $value[ $themeModName ];
					if ( is_serialized( $tempValue ) ) {
						$tempValue = unserialize( $tempValue );
					}
					
					// Hook 'tf_save_option_{namespace}'
					$newValue = apply_filters( 'tf_save_option_' . $this->optionNamespace, $tempValue, $option->settings['id'] );
					
					// Hook 'tf_save_option_{namespace}_{optionID}'
					$newValue = apply_filters( 'tf_save_option_' . $themeModName, $tempValue );
					
					// We mainly check for equality here so that we won't have to serialize IF the value
					// wasn't touched anyway.
					if ( $newValue != $tempValue ) {
						if ( is_array( $newValue ) ) {
							$newValue = serialize( $newValue );
						}
					
						$value[ $themeModName ] = $newValue;
					}
				}
			}
		}
		
		// Hook 'tf_pre_save_options_{namespace}' - action pre-saving
		if ( $customizerUsed ) {
			do_action( 'tf_pre_save_options_' . $this->optionNamespace, $this->themeCustomizerSections );
		}
		
		return $value;
	}
}
