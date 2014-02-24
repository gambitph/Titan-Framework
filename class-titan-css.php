<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Titan Framework CSS Class
 * In charge of creating and parsing CSS rules created from framework options.
 *
 * @author Benjamin Intal
 **/
class TitanFrameworkCSS {

    // Compression type to use
    const SCSS_COMPRESSION = 'scss_formatter_compressed';

    // Internal variables
    private $frameworkInstance;
    private $allOptionsWithIDs = array();

    // Keep all added CSS here
    private $additionalCSS = array();


    /**
     * Class constructor
     *
     * @param   TitanFramework $frameworkInstance an instance of the framework object
     * @return  void
     * @since   1.2
     */
    function __construct( $frameworkInstance ) {
        $this->frameworkInstance = $frameworkInstance;

        // Gather all the options
        add_action( 'tf_create_option', array( $this, 'getOptionsWithCSS' ) );

        // display our CSS
        add_action( 'wp_head', array( $this, 'printCSS' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueueCSS' ) );

        // Trigger new compile when theme customizer settings were saved
        add_action( 'customize_save_after', array( $this, 'generateCSS' ) );
        // Trigger new compile when admin option settings were saved
        add_action( 'tf_admin_options_saved', array( $this, 'generateCSS' ) );
    }


    /**
     * Adds a CSS string to the list for CSS generation
     *
     * @param   string $cssString string CSS, can contain SaSS variables of optionIDs
     * @return  void
     * @since   1.2
     */
    public function addCSS( $cssString ) {
        $this->additionalCSS[] = $cssString;
    }


    /**
     * Prints the styles in the head tag. Used IF the CSS file could not be generated
     *
     * @return  void
     * @since   1.2
     */
    public function printCSS() {
        $css = get_option( $this->getCSSSlug() );
        if ( ! empty( $css ) ) {
            print "<style>{$css}</style>";
        }
    }


    /**
     * Enqueues the generated CSS. Used IF the CSS file was successfully generated
     *
     * @return  void
     * @since   1.2
     */
    public function enqueueCSS() {
        $css = get_option( $this->getCSSSlug() );
        if ( empty( $css ) ) {
            wp_enqueue_style( 'tf-compiled-option-css', $this->getCSSFileURL(), __FILE__ );
        }
    }


    /**
     * Gathers all options with IDs for generation of CSS rules
     *
     * @param   TitanFrameworkOption $option The option which was just added
     * @return  void
     * @since   1.2
     */
    public function getOptionsWithCSS( $option ) {
        if ( !empty( $option->settings['id'] ) ) {
            $this->allOptionsWithIDs[] = $option;
        }
    }


    /**
     * Generates a unique slug for our CSS generation
     *
     * @return  string a unique slug that uses the option namespace
     * @since   1.2
     */
    private function getCSSSlug() {
        return TF . '-' . str_replace( ' ', '-', trim( strtolower( $this->frameworkInstance->optionNamespace ) ) . '-css' );
    }


    /**
     * Returns the path of the generated CSS file
     *
     * @return  string The full path to the CSS file
     * @since   1.2
     */
    private function getCSSFilePath() {
        $uploads = wp_upload_dir();
        $uploadsFolder = trailingslashit( $uploads['basedir'] );
        return $uploadsFolder . $this->getCSSSlug() . '.css';
    }


    /**
     * Returns the URL of the generated CSS file
     *
     * @return  string The URL to the CSS file
     * @since   1.2
     */
    private function getCSSFileURL() {
        $uploads = wp_upload_dir();
        return trailingslashit( $uploads['baseurl'] ) . $this->getCSSSlug() . '.css';
    }


    /**
     * Forms CSS rules containing SaSS variables
     *
     * @param   string $id The id of an option
     * @param   string $value The value or CSS rule
     * @param   mixes $key The key of the value, used for when the value is an array
     * @param   string $cssString The current CSS rules from a previous recursive call
     * @return  string CSS rules of SaSS variables
     * @since   1.2
     */
    private function formCSSVariables( $id, $value, $key = false, $cssString = '' ) {
        if ( is_serialized( $value ) ) {
            $value = unserialize( stripslashes( $value ) );
        }
        if ( is_array( $value ) ) {
            foreach ( $value as $subKey => $subValue ) {
                if ( $key !== false ) {
                    $subKey = $key . '-' . $subKey;
                }
                $cssString = $this->formCSSVariables( $id, $subValue, $subKey, $cssString );
            }
        } else {
            if ( $key === false ) {
                $cssString .= "\$" . esc_attr( $id ) . ": '" . esc_attr( $value ) . "';\n";
            } else {
                // If the value is a color, don't wrap it in quotes
                if ( preg_match( '/^#[a-f0-9]{6}$/i', $value ) ) {
                    $cssString .= "\$" . esc_attr( $id ) . "-" . esc_attr( $key ) . ": " . esc_attr( $value ) . ";\n";
                } else {
                    $cssString .= "\$" . esc_attr( $id ) . "-" . esc_attr( $key ) . ": '" . esc_attr( $value ) . "';\n";
                }
            }
        }
        return $cssString;
    }


    /**
     * Generates a the CSS file containing all the rules assigned to options, or created using
     * the TitanFramework->createCSS( '...' ) function.
     *
     * @return  void
     * @since   1.2
     */
    public function generateCSS() {
        $cssString = '';

        // Get all the CSS
        foreach ( $this->allOptionsWithIDs as $option ) {
            // Add the values as SaSS variables
            $cssString .= $this->formCSSVariables( $option->settings['id'], $this->frameworkInstance->getOption( $option->settings['id'] ) );

            // Add the custom CSS
            if ( ! empty( $option->settings['css'] ) ) {
                // In the css parameter, we accept the term `value` as our current value,
                // translate it into the SaSS variable for the current option
                $cssString .= str_replace( 'value', '$' . $option->settings['id'], $option->settings['css'] );
            }
        }

        // Add additional CSS added via TitanFramework::createCSS()
        foreach ( $this->additionalCSS as $css ) {
            $cssString .= $css . "\n";
        }

        // Compile as SCSS & minify
        require_once( trailingslashit( dirname( __FILE__ ) ) . "inc/scssphp/scss.inc.php" );
        $scss = new scssc();
        $scss->setFormatter( self::SCSS_COMPRESSION );
        $cssString = $scss->compile( $cssString );

        // Save our css
        if ( $this->writeCSS( $cssString, $this->getCSSFilePath() ) ) {
            // If we were able to save, remove our CSS option if it exists
            delete_option( $this->getCSSSlug() );
        } else {
            // If we were NOT able to save our generated CSS, save our CSS
            // as an option, we'll load that in wp_head in a hook
            update_option( $this->getCSSSlug(), $cssString );
        }
    }


    /**
     * Writes the CSS file
     *
     * @return  boolean True if the CSS file was written successfully
     * @since   1.2
     */
    private function writeCSS( $parsedCSS, $cssFilename ) {
        WP_Filesystem();
        global $wp_filesystem;

        // Verify that we can create the file
        if ( $wp_filesystem->exists( $cssFilename ) ) {
            if ( ! $wp_filesystem->is_writable( $cssFilename ) ) {
                return false;
            }
            if ( ! $wp_filesystem->is_readable( $cssFilename ) ) {
                return false;
            }
        }
        // Verify directory
        if ( ! $wp_filesystem->is_dir( dirname( $cssFilename ) ) ) {
            return false;
        }
        if ( ! $wp_filesystem->is_writable( dirname( $cssFilename ) ) ) {
            return false;
        }

        // Write our CSS
        return $wp_filesystem->put_contents( $cssFilename, $parsedCSS, 0644 );
    }

}
?>