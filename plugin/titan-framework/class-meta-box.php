<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class TitanFrameworkMetaBox {

    private $defaultSettings = array(
        'name' => '', // Name of the menu item
        // 'parent' => null, // slug of parent, if blank, then this is a top level menu
        'id' => '', // Unique ID of the menu item
        // 'capability' => 'manage_options', // User role
        // 'icon' => 'dashicons-admin-generic', // Menu icon for top level menus only
        // 'position' => 100.01 // Menu position for top level menus only
        'post_type' => 'page', // Post type
        'context' => 'normal', // normal, advanced, or side
        'hide_custom_fields' => true, // If true, the custom fields box will not be shown
    );

    public $settings;
    public $options = array();
    public $owner;
    public $postID; // Temporary holder for the current post ID being edited in the admin

    function __construct( $settings, $owner ) {
        $this->owner = $owner;

        if ( ! is_admin() ) {
            return;
        }

        $this->settings = array_merge( $this->defaultSettings, $settings );
        // $this->options = $options;

        if ( empty( $this->settings['name'] ) ) {
            $this->settings['name'] = __( "More Options", TF_I18NDOMAIN );
        }

        if ( empty( $this->settings['id'] ) ) {
            $this->settings['id'] = str_replace( ' ', '-', trim( strtolower( $this->settings['name'] ) ) );
        }

        add_action( 'add_meta_boxes', array( $this, 'register' ) );
        add_action( 'save_post', array( $this, 'saveOptions' ), 10, 2 );
    }

    public function register() {
        // Hide the custom fields
        if ( $this->settings['hide_custom_fields']) {
            remove_meta_box( 'postcustom' , $this->settings['post_type'] , 'normal' );
        }

        add_meta_box(
            $this->settings['id'],
            $this->settings['name'],
            array( $this, 'display' ),
            $this->settings['post_type'],
            $this->settings['context'],
            'high' );
    }

    public function display( $post ) {
        $this->postID = $post->ID;

        wp_nonce_field( $this->settings['id'], TF . '_' . $this->settings['id'] . '_nonce' );

        ?>
        <table class="form-table">
        <tbody>
        <?php
        foreach ( $this->options as $option ) {
            $option->display();
        }
        ?>
        </tbody>
        </table>
        <?php
    }

    public function saveOptions( $postID, $post ) {
        if ( ! $this->verifySecurity( $postID ) ) {
            return;
        }

        // Save the options one by one
        foreach ( $this->options as $option ) {
            if ( empty( $option->settings['id'] ) ) {
                continue;
            }

            if ( ! empty( $_POST[$this->owner->optionNamespace . '_' . $option->settings['id']] ) ) {
                $value = $_POST[$this->owner->optionNamespace . '_' . $option->settings['id']];
            } else {
                $value = '';
            }

            $this->owner->setOption( $option->settings['id'], $value, $postID );
        }
    }

    private function verifySecurity( $postID ) {
        if ( empty( $_POST ) ) {
            return false;
        }

        // Don't save on revisions
        if ( wp_is_post_revision( $postID ) ) {
            return false;
        }

        // Don't save on autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return false;
        }

        // Verify our nonce
        if ( ! check_admin_referer( $this->settings['id'], TF . '_' . $this->settings['id'] . '_nonce' ) ) {
            return false;
        }

        // Check permissions
        if ( $this->settings['post_type'] == 'page' ) {
            if ( ! current_user_can( 'edit_page', $postID ) ) {
                return false;
            }
        } else if ( ! current_user_can( 'edit_post', $postID ) ) {
            return false;
        }

        return true;
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