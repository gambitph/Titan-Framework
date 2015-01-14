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
		add_action( 'tf_create_option_' . $frameworkInstance->optionNamespace, array( $this, 'getOptionsWithCSS' ) );

		// display our CSS
		add_action( 'wp_head', array( $this, 'printCSS' ), 99 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueueCSS' ) );

		// Trigger new compile when theme customizer settings were saved
		add_action( 'customize_save_after', array( $this, 'generateSaveCSS' ) );
		// Trigger new compile when admin option settings were saved
		add_action( 'tf_admin_options_saved_' . $frameworkInstance->optionNamespace, array( $this, 'generateSaveCSS' ) );
		// Trigger compile when there are no default options saved yet
		add_action( 'tf_init_no_options_' . $frameworkInstance->optionNamespace, array( $this, 'generateMissingCSS' ) );
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

		// If the setting is 'generate css' and we can't just echo it out
		if ( $this->frameworkInstance->settings['css'] == 'generate' ) {
			$css = get_option( $this->getCSSSlug() );
			if ( ! empty( $css ) ) {
				echo "<style>{$css}</style>";
			}

		// If the setting is 'print inline css', print it out if we have any
		} else if ( $this->frameworkInstance->settings['css'] == 'inline' ) {
			$css = $this->generateCSS();
			if ( ! empty( $css ) ) {
				echo "<style>{$css}</style>";
			}
		}
	}


	/**
	 * Enqueues the generated CSS. Used IF the CSS file was successfully generated
	 *
	 * @return  void
	 * @since   1.2
	 */
	public function enqueueCSS() {

		// Only enqueue the generated css if we have the settings for it
		if ( $this->frameworkInstance->settings['css'] == 'generate' ) {

			$css = get_option( $this->getCSSSlug() );
			$generatedCss = $this->getCSSFilePath();

			if ( file_exists( $generatedCss ) && empty( $css ) ) {
				wp_enqueue_style( 'tf-compiled-options-' . $this->frameworkInstance->optionNamespace, $this->getCSSFileURL(), __FILE__ );
			}

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
		if ( ! empty( $option->settings['id'] ) ) {
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
	private function formCSSVariables( $id, $type, $value, $key = false, $cssString = '' ) {
		if ( is_serialized( $value ) ) {
			$value = unserialize( stripslashes( $value ) );
		}
		if ( is_array( $value ) ) {
			foreach ( $value as $subKey => $subValue ) {
				if ( $key !== false ) {
					$subKey = $key . '-' . $subKey;
				}
				$cssString = $this->formCSSVariables( $id, $type, $subValue, $subKey, $cssString );
			}
		} else {
			$value = esc_attr( $value );

			// If the value is a file address, wrap it in quotes
			if ( $type === 'upload' ) {
				$value = "'" . $value . "'";
			}

			if ( false === $key  ) {
				$cssString .= "\$" . esc_attr( $id ) . ": " . $value . ";\n";
			} else {
				$cssString .= "\$" . esc_attr( $id ) . "-" . esc_attr( $key ) . ": " . $value . ";\n";
			}
		}
		return $cssString;
	}


	/**
	 * Generates a CSS string of all the options
	 *
	 * @return  string A CSS string of all the values
	 * @since   1.2
	 */
	public function generateCSS() {
		$cssString = '';
		
		// These are the option types which are not allowed:
		$noCSSOptionTypes = array(
			'text',
			'textarea',
			'editor',
		);
		
		// Compile as SCSS & minify
		require_once( trailingslashit( dirname( __FILE__ ) ) . "inc/scssphp/scss.inc.php" );
		$scss = new scssc();

		// Get all the CSS
		foreach ( $this->allOptionsWithIDs as $option ) {
			// Only do this for the allowed types
			if ( in_array( $option->settings['type'], $noCSSOptionTypes ) ) {
				continue;
			}
			
			// Decide whether or not we should continue to generate CSS for this option
			if ( ! apply_filters( 'tf_continue_generate_css_' . $option->settings['type'] . '_' . $option->getOptionNamespace(), true, $option ) ) {
				continue;
			}
			
			// Custom generated CSS
			$generatedCSS = apply_filters( 'tf_generate_css_' . $option->settings['type'] . '_' . $option->getOptionNamespace(), '', $option );
			if ( $generatedCSS ) {
				try {
					$testerForValidCSS = $scss->compile( $generatedCSS );
					$cssString .= $generatedCSS;
				} catch (Exception $e) {
				}
				continue;
			}

			// Don't render CSS for this option if it doesn't have a value
			$optionValue = $this->frameworkInstance->getOption( $option->settings['id'] );
			if ( empty( $optionValue ) ) {
				continue;
			}

			// Add the values as SaSS variables
			$generatedCSS = $this->formCSSVariables(
				$option->settings['id'],
				$option->settings['type'],
				$optionValue
			);

			
			try {
				$testerForValidCSS = $scss->compile( $generatedCSS );
				$cssString .= $generatedCSS;
			} catch (Exception $e) {
			}

			// Add the custom CSS
			if ( ! empty( $option->settings['css'] ) ) {

				// In the css parameter, we accept the term `value` as our current value,
				// translate it into the SaSS variable for the current option
				$generatedCSS = str_replace( 'value', '#{$' . $option->settings['id'] . '}', $option->settings['css'] );

				try {
					$testerForValidCSS = $scss->compile( $generatedCSS );
					$cssString .= $generatedCSS;
				} catch (Exception $e) {
				}
			}
		}
		
		// Add additional CSS added via TitanFramework::createCSS()
		foreach ( $this->additionalCSS as $css ) {
			$cssString .= $css . "\n";
		}

		// Compile as SCSS & minify
		if ( ! empty( $cssString ) ) {
			$scss->setFormatter( self::SCSS_COMPRESSION );
			try {
				$cssString = $scss->compile( $cssString );
			} catch ( Exception $e ) {
			}
		}

		return $cssString;
	}


	/**
	 * Generates a the CSS file containing all the rules assigned to options, or created using
	 * the TitanFramework->createCSS( '...' ) function.
	 *
	 * @return	void
	 * @since	1.3
	 */
	public function generateSaveCSS() {
		$cssString = $this->generateCSS();

		if ( empty( $cssString ) ) {
			return;
		}

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
	 * When the no options are saved yet (e.g. new install) create a CSS
	 *
	 * @return	void
	 * @since	1.4.1
	 */
	public function generateMissingCSS() {
		add_action( 'admin_init', array( $this, '_generateMissingCSS' ), 1000 );
	}


	/**
	 * When the no options are saved yet (e.g. new install) create a CSS, called internally
	 *
	 * @return	void
	 * @since	1.4.1
	 */
	public function _generateMissingCSS() {
		// WP_Filesystem is only available in the admin
		if ( ! is_admin() ) {
			return;
		}

		$cssFilename = $this->getCSSFilePath();

		WP_Filesystem();
		global $wp_filesystem;

		// Check if the file exists
		if ( $wp_filesystem->exists( $cssFilename ) ) {
			return;
		}

		// Verify directory
		if ( ! $wp_filesystem->is_dir( dirname( $cssFilename ) ) ) {
			return;
		}
		if ( ! $wp_filesystem->is_writable( dirname( $cssFilename ) ) ) {
			return;
		}

		$this->generateSaveCSS();
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
