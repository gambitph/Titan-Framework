<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class TitanFramework {

    public $optionNamespace; // Options will be prefixed with this

    private $adminPanels = array();
    private $metaBoxes = array();
    private $themeCustomizerSections = array();
    private $widgetAreas = array();
    private $googleFontsOptions = array();

    private static $instances = array();
    private static $allOptionIDs = array();
    private static $allOptions;

    // We store
    public $optionsUsed = array();

    public static function getInstance( $optionNamespace ) {
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

        add_action( 'after_setup_theme', array( $this, 'getAllOptions' ), 1 );
        add_action( 'after_setup_theme', array( $this, 'updateOptionDBListing' ) );

        if ( is_admin() ) {
            add_action( 'after_setup_theme', array( $this, 'updateThemeModListing' ) );
            add_action( 'after_setup_theme', array( $this, 'updateMetaDbListing' ) );
        }

        add_action( 'admin_enqueue_scripts', array( $this, "loadAdminScripts" ) );
        add_action( 'wp_enqueue_scripts', array( $this, "loadFrontEndScripts" ) );
        add_action( 'tf_create_option', array( $this, "rememberGoogleFonts" ) );
        add_action( 'tf_create_option', array( $this, "verifyUniqueIDs" ) );
    }

    /**
     * Checks all the ids and shows a warning when multiple occurances of an id is found.
     * This is to ensure that there won't be any option conflicts
     *
     * @param   TitanFrameworkOption $option The object just created
     * @return  null
     * @since   1.1.1
     */
    public function verifyUniqueIDs( $option ) {
        if ( empty( $option->settings['id'] ) ) {
            return;
        }

        if ( in_array( $option->settings['id'], self::$allOptionIDs ) ) {
            self::displayFrameworkError(
                sprintf( __( 'All option IDs must be unique. The id %s has been used multiple times.', TF_I18NDOMAIN ),
                    '<code>' . $option->settings['id'] . '</code>'
                )
            );
        } else {
            self::$allOptionIDs[] = $option->settings['id'];
        }
    }

    public function rememberGoogleFonts( $option ) {
        if ( is_a( $option, 'TitanFrameworkOptionSelectGooglefont' ) ) {
            if ( $option->settings['enqueue'] ) {
                $this->googleFontsOptions[] = $option;
            }
        }
    }

    public function loadFrontEndScripts() {
        foreach ( $this->googleFontsOptions as $googleFontOption ) {
            $font = $googleFontOption->getValue();
            if ( empty( $font ) ) {
                continue;
            }
            wp_enqueue_style(
                'tf-google-webfont-' . strtolower( str_replace( ' ', '-', $font['name'] ) ),
                TitanFrameworkOptionSelectGooglefont::formScript( $font )
            );
        }
    }

    public function loadAdminScripts() {
        wp_enqueue_media();
        wp_enqueue_script( 'tf-serialize', TitanFramework::getURL( 'serialize.js', __FILE__ ) );
        wp_enqueue_script( 'tf-styling', TitanFramework::getURL( 'admin-styling.js', __FILE__ ) );
        wp_enqueue_style( 'tf-admin-styles', TitanFramework::getURL( 'admin-styles.css', __FILE__ ) );
    }

    public function getAllOptions() {
        if ( empty( self::$allOptions ) ) {
            self::$allOptions = array();
        }

        if ( empty( self::$allOptions[$this->optionNamespace] ) ) {
            self::$allOptions[$this->optionNamespace] = array();
        } else {
            return self::$allOptions[$this->optionNamespace];
        }

        // Check if we have options saved already
        $currentOptions = get_option( $this->optionNamespace . '_options' );

        // Put all the available options in our global variable for future checking
        if ( ! empty( $currentOptions ) && ! count( self::$allOptions[$this->optionNamespace] ) ) {
            self::$allOptions[$this->optionNamespace] = unserialize( $currentOptions );
        }

        return self::$allOptions[$this->optionNamespace];
    }

    public function saveOptions() {
        update_option( $this->optionNamespace . '_options', serialize( self::$allOptions[$this->optionNamespace] ) );
        return self::$allOptions[$this->optionNamespace];
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
        $allOptionKeys = array_fill_keys( array_keys( self::$allOptions[$this->optionNamespace] ), null );

        // Check whether options have changed / added
        $changed = false;
        foreach ( $this->adminPanels as $panel ) {
            // Check existing options
            foreach ( $panel->options as $option ) {
                if ( empty( $option->settings['id'] ) ) {
                    continue;
                }
                if ( ! isset( self::$allOptions[$this->optionNamespace][$option->settings['id']] ) ) {
                    self::$allOptions[$this->optionNamespace][$option->settings['id']] = $option->settings['default'];
                    $changed = true;
                }
                unset( $allOptionKeys[$option->settings['id']] );

                // Clean the value for retrieval
                self::$allOptions[$this->optionNamespace][$option->settings['id']] =
                    $option->cleanValueForGetting( self::$allOptions[$this->optionNamespace][$option->settings['id']] );
            }
            // Check existing options
            foreach ( $panel->tabs as $tab ) {
                foreach ( $tab->options as $option ) {
                    if ( empty( $option->settings['id'] ) ) {
                        continue;
                    }
                    if ( ! isset( self::$allOptions[$this->optionNamespace][$option->settings['id']] ) ) {
                        self::$allOptions[$this->optionNamespace][$option->settings['id']] = $option->settings['default'];
                        $changed = true;
                    }
                    unset( $allOptionKeys[$option->settings['id']] );

                    // Clean the value for retrieval
                    self::$allOptions[$this->optionNamespace][$option->settings['id']] =
                        $option->cleanValueForGetting( self::$allOptions[$this->optionNamespace][$option->settings['id']] );
                }
            }
        }

        // Remove all unused keys
        if ( count( $allOptionKeys ) ) {
            foreach ( $allOptionKeys as $optionName => $dummy ) {
                unset( self::$allOptions[$this->optionNamespace][$optionName] );
            }
            $changed = true;
        }

        // New options have been added, save the default values
        if ( $changed ) {
            update_option( $this->optionNamespace . '_options', serialize( self::$allOptions[$this->optionNamespace] ) );
        }
    }

    public function createAdminPanel( $settings ) {
        $obj = new TitanFrameworkAdminPanel( $settings, $this );
        $this->adminPanels[] = $obj;
        return $obj;
    }

    public function createMetaBox( $settings ) {
        $obj = new TitanFrameworkMetaBox( $settings, $this );
        $this->metaBoxes[] = $obj;
        return $obj;
    }

    public function createThemeCustomizerSection( $settings ) {
        $obj = new TitanFrameworkThemeCustomizerSection( $settings, $this );
        $this->themeCustomizerSections[] = $obj;
        return $obj;
    }

    public function getOption( $optionName, $postID = null ) {
        $value = null;

        if ( empty( $postID ) ) {
            // option

            if ( ! is_array( self::$allOptions ) ) {
                // this is blank if called too early. getOption should be called inside a hook or template
                self::displayFrameworkError( sprintf( __( 'Wrong usage of %s, this should be called inside a hook or from within a theme file.', TF_I18NDOMAIN ), '<code>getOption</code>' ) );
                return '';
            }

            if ( array_key_exists( $optionName, self::$allOptions[$this->optionNamespace] ) ) {
                $value = self::$allOptions[$this->optionNamespace][$optionName];
            } else {
                // customizer
                $value = get_theme_mod( $this->optionNamespace . '_' . $optionName );
            }
        } else {
            // meta
            $value = get_post_meta( $postID, $this->optionNamespace . '_' . $optionName, true );
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

        if ( empty( $postID ) ) {
            // option

            if ( ! is_array( self::$allOptions ) ) {
                // this is blank if called too early. getOption should be called inside a hook or template
                self::displayFrameworkError( sprintf( __( 'Wrong usage of %s, this should be called inside a hook or from within a theme file.', TF_I18NDOMAIN ), '<code>setOption</code>' ) );
                return '';
            }

            if ( array_key_exists( $optionName, self::$allOptions[$this->optionNamespace] ) ) {
                self::$allOptions[$this->optionNamespace][$optionName] = $value;
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
        // framework is a or in a plugin
        return plugins_url( $script, $file );
    }
}
?>