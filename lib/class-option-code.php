<?php

/**
 * Code Option Class
 *
 * @author Benjamin Intal
 * @package Titan Framework Core
 **/

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}
/**
 * Code Option Class
 *
 * @since	1.3
 **/
class TitanFrameworkOptionCode extends TitanFrameworkOption {

	// Default settings specific to this option
	public $defaultSecondarySettings = array(
		'lang' => 'css',
		'theme' => 'chrome',
		'height' => 200,
	);


	/**
	 * Constructor
	 *
	 * @since	1.3
	 */
	function __construct( $settings, $owner ) {
		parent::__construct( $settings, $owner );

		add_action( 'admin_enqueue_scripts', array( $this, 'loadAdminScripts' ) );
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'loadAdminScripts' ) );

		// CSS generation for CSS code langs
		add_filter( 'tf_generate_css_code_' . $this->getOptionNamespace(), array( $this, 'generateCSSCode' ), 10, 2 );
		add_filter( 'wp_head', array( $this, 'printCSSForPagesAndPosts' ), 100 );

		// JS inclusion for Javascript code langs
		add_filter( 'wp_footer', array( $this, 'printJS' ), 100 );
		add_filter( 'wp_footer', array( $this, 'printJSForPagesAndPosts' ), 101 );
	}


	/**
	 * Prints javascript code in the header using wp_print_scripts
	 *
	 * @return	void
	 * @since	1.3
	 */
	public function printJS() {
		// For CSS langs only
		if ( $this->settings['lang'] != 'javascript' ) {
			return;
		}

		// For non-meta box options only
		if ( TitanFrameworkOption::TYPE_META == $this->type ) {
			return;
		}

		$js = $this->getValue();

		if ( ! empty( $js ) ) {
			printf( "<script type=\"text/javascript\">\n%s\n</script>\n", $js );
		}
	}


	/**
	 * Prints javascript code in the header for meta options using wp_print_scripts
	 *
	 * @return	void
	 * @since	1.3
	 */
	public function printJSForPagesAndPosts() {
		// This is for meta box options only, other types get generated normally
		if ( TitanFrameworkOption::TYPE_META != $this->type ) {
			return;
		}

		// For CSS langs only
		if ( $this->settings['lang'] != 'javascript' ) {
			return;
		}

		// Don't generate CSS for non-pages and non-posts
		$id = get_the_ID();
		if ( empty( $id ) || 1 == $id || ! is_singular() ) {
			return;
		}

		?>
		<script>
		<?php echo $this->getValue( $id ) ?>
		</script>
		<?php
	}


	/**
	 * Prints CSS styles in the header for meta options using wp_print_scripts
	 *
	 * @return	void
	 * @since	1.3
	 */
	public function printCSSForPagesAndPosts() {
		// This is for meta box options only, other types get generated normally
		if ( TitanFrameworkOption::TYPE_META != $this->type ) {
			return;
		}

		// For CSS langs only
		if ( $this->settings['lang'] != 'css' ) {
			return;
		}

		// Don't generate CSS for non-pages and non-posts
		$id = get_the_ID();
		if ( empty( $id ) || 1 == $id || ! is_singular() ) {
			return;
		}

		// Check if a CSS was entered
		$css = $this->getValue( $id );
		if ( empty( $css ) ) {
			return;
		}

		// Print out valid CSS only
		require_once( trailingslashit( dirname( dirname( __FILE__ ) ) ) . 'inc/scssphp/scss.inc.php' );
		$scss = new titanscssc();
		try {
			$css = $scss->compile( $css );
			echo "<style type='text/css' media='screen'>{$css}</style>";
		} catch (Exception $e) {
		}
	}


	/**
	 * Generates CSS to be included in our dynamically generated CSS file in
	 * TitanFrameworkCSS, using tf_generate_css_code
	 *
	 * @param	string               $css The CSS to output
	 * @param	TitanFrameworkOption $option The option object being generated
	 * @return	void
	 * @since	1.3
	 */
	public function generateCSSCode( $css, $option ) {
		if ( $this->settings['id'] != $option->settings['id'] ) {
			return $css;
		}
		if ( TitanFrameworkOption::TYPE_META != $option->type ) {
			$css = $this->getValue();
		}
		return $css;
	}


	/**
	 * Loads the ACE library for displaying our syntax highlighted code editor
	 *
	 * @return	void
	 * @since	1.3
	 */
	public function loadAdminScripts() {
		wp_enqueue_script( 'tf-ace', TitanFramework::getURL( '../js/ace-min-noconflict/ace.js', __FILE__ ) );
		wp_enqueue_script(
			'tf-ace-theme-' . $this->settings['theme'],
			TitanFramework::getURL( '../js/ace-min-noconflict/theme-' . $this->settings['theme'] . '.js',
			__FILE__ )
		);
		wp_enqueue_script(
			'tf-ace-mode-' . $this->settings['lang'],
			TitanFramework::getURL( '../js/ace-min-noconflict/mode-' . $this->settings['lang'] . '.js',
			__FILE__ )
		);
	}


	/**
	 * Displays the option for admin pages and meta boxes
	 *
	 * @return	void
	 * @since	1.3
	 */
	public function display() {
		$this->echoOptionHeader();

		?>
		<script>
		jQuery(document).ready(function ($) {
			var container = jQuery('#<?php echo $this->getID() ?>_ace_editor');
			container.width( container.parent().width() ).height( <?php echo $this->settings['height'] ?> );

			var editor = ace.edit( "<?php echo $this->getID() ?>_ace_editor" );
			container.css('width', 'auto');
			editor.setValue(container.siblings('textarea').val());
			editor.setTheme("ace/theme/<?php echo $this->settings['theme'] ?>");
			editor.getSession().setMode('ace/mode/<?php echo $this->settings['lang'] ?>');
			editor.setShowPrintMargin(false);
			editor.setHighlightActiveLine(false);
			editor.gotoLine(1);
			editor.session.setUseWorker(false);

			editor.getSession().on('change', function(e) {
				$(editor.container).siblings('textarea').val(editor.getValue());
			});
		});
		</script>
		<?php

		printf( "<div id='%s_ace_editor'></div>", $this->getID() );

		// The hidden textarea that will hold our contents
		printf( "<textarea name='%s' id='%s' style='display: none'>%s</textarea>",
			esc_attr( $this->getID() ),
			esc_attr( $this->getID() ),
			esc_textarea( $this->getValue() )
		);

		$this->echoOptionFooter();
	}


	/**
	 * Cleans the value for getOption
	 *
	 * @param	string $value The raw value of the option
	 * @return	mixes The cleaned value
	 * @since	1.3
	 */
	public function cleanValueForGetting( $value ) {
		return stripslashes( $value );
	}


	/**
	 * Registers the theme customizer control, for displaying the option
	 *
	 * @param	WP_Customize                    $wp_enqueue_script The customize object
	 * @param	TitanFrameworkCustomizerSection $section The section where this option will be placed
	 * @param	int                             $priority The order of this control in the section
	 * @return	void
	 * @since	1.3
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkOptionCodeControl( $wp_customize, $this->getID(), array(
			'label' => $this->settings['name'],
			'section' => $section->settings['id'],
			'settings' => $this->getID(),
			'description' => $this->settings['desc'],
			'priority' => $priority,
			'lang' => $this->settings['lang'],
			'theme' => $this->settings['theme'],
			'height' => $this->settings['height'],
		) ) );
	}
}



/*
 * We create a new control for the theme customizer
 */
add_action( 'customize_register', 'registerTitanFrameworkOptionCodeControl', 1 );


/**
 * Creates the option for the theme customizer
 *
 * @return	void
 * @since	1.3
 */
function registerTitanFrameworkOptionCodeControl() {
	class TitanFrameworkOptionCodeControl extends WP_Customize_Control {
		public $description;
		public $lang;
		public $theme;
		public $height;

		public function render_content() {
			?>
			<script>
			jQuery(document).ready(function ($) {
				var container = jQuery('#<?php echo $this->id ?>_ace_editor');
				container.width( container.parent().width() ).height( <?php echo $this->height ?> );

				var editor = ace.edit( "<?php echo $this->id ?>_ace_editor" );
				container.css('width', 'auto');
				editor.setValue(container.siblings('textarea').val());
				editor.setTheme("ace/theme/<?php echo $this->theme ?>");
				editor.getSession().setMode('ace/mode/<?php echo $this->lang ?>');
				editor.setShowPrintMargin(false);
				editor.setHighlightActiveLine(false);
				editor.gotoLine(1);
				editor.session.setUseWorker(false);

				editor.getSession().on('change', function(e) {
					$(editor.container).siblings('textarea').val(editor.getValue()).trigger('change');
				});
			});
			</script>

			<label>
			<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
			<?php

			printf( "<div id='%s_ace_editor' class='tf-code'></div>", $this->id );

			// The hidden textarea that will hold our contents
			?><textarea <?php $this->link(); ?> style='display: none'><?php echo $this->value() ?></textarea><?php

if ( ! empty( $this->description ) ) {
	echo "<p class='description'>{$this->description}</p>";
}

			?>
			</label>
			<?php
		}
	}
}
