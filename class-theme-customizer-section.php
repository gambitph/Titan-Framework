<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class TitanFrameworkThemeCustomizerSection {

    private $defaultSettings = array(
        'name' => '', // Name of the menu item
        // 'parent' => null, // slug of parent, if blank, then this is a top level menu
        'id' => '', // Unique ID of the menu item
        'capability' => 'edit_theme_options', // User role
        // 'icon' => 'dashicons-admin-generic', // Menu icon for top level menus only
        'desc' => '', // Description
        'position' => 30 // Menu position for top level menus only
    );

    public $settings;
    public $options = array();
    public $owner;

    function __construct( $settings, $owner ) {
        $this->owner = $owner;

        $this->settings = array_merge( $this->defaultSettings, $settings );

        if ( empty( $this->settings['name'] ) ) {
            $this->settings['name'] = __( "More Options", TF_I18NDOMAIN );
        }

        if ( empty( $this->settings['id'] ) ) {
            $this->settings['id'] = str_replace( ' ', '-', trim( strtolower( $this->settings['name'] ) ) );
        }

        add_action( 'customize_register', array( $this, 'register' ) );
        add_action( 'customize_controls_enqueue_scripts', array( $this, 'loadUploaderScript' ) );
    }

    public function loadUploaderScript() {
        wp_enqueue_media();
        wp_enqueue_style( 'tf-admin-theme-customizer-styles', plugins_url( 'admin-theme-customizer-styles.css', __FILE__ ) );
        wp_enqueue_script( 'tf-theme-customizer-serialize', plugins_url( 'serialize.js', __FILE__ ) );
    }

    public function getID() {
        return $this->settings['id'];
    }

    public function livePreview() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            <?php
            foreach ( $this->options as $option ):
                if ( empty( $option->settings['livepreview'] ) ):
                    continue;
                endif;
                ?>
                wp.customize( '<?php echo $option->getID() ?>', function( v ) {
                    v.bind( function( value ) {
                        <?php

                        // Some options may want to insert custom jQuery code before manipulation of live preview
                        if ( ! empty( $option->settings['id'] ) ) {
                            do_action( 'tf_livepreview_pre', $option->settings['id'], $option->settings['type'], $option );
                        }

                        echo $option->settings['livepreview'];
                        ?>
                    } );
                } );
                <?php
            endforeach;
            ?>
        });
        </script>
        <?php
    }

    public function register( $wp_customize ) {
        $wp_customize->add_section( $this->settings['id'], array(
            'title' => $this->settings['name'],
            'priority' => $this->settings['position'],
            'description' => $this->settings['desc'],
            'capability' => $this->settings['capability'],
        ) );

        // Unfortunately we have to call each option's register from here
        foreach ( $this->options as $index => $option ) {
            if ( ! empty( $option->settings['id'] ) ) {
                $wp_customize->add_setting( $option->getID() , array(
                    'default' => $option->settings['default'],
                    'transport' => empty( $option->settings['livepreview'] ) ? 'refresh' : 'postMessage',
                ) );
            }

            // We add the index here, this will be used to order the controls because of this minor bug:
            // https://core.trac.wordpress.org/ticket/20733
            $option->registerCustomizerControl( $wp_customize, $this, $index + 1 );
        }

        add_action( 'wp_footer', array( $this, 'livePreview' ) );
    }

    public function createOption( $settings ) {
        $obj = TitanFrameworkOption::factory( $settings, $this );
        // $obj = new TitanFrameworkOption( $settings, $this );
        $this->options[] = $obj;

        if ( ! empty( $obj->settings['id'] ) ) {
            $this->owner->optionsUsed[$obj->settings['id']] = $obj;
        }

        do_action( 'tf_create_option', $obj );

        return $obj;
    }
}
?>