<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class TitanFrameworkAdminPanel {

    private $defaultSettings = array(
        'name' => '', // Name of the menu item
        'title' => '', // Title displayed on the top of the admin panel
        'parent' => null, // id of parent, if blank, then this is a top level menu
        'id' => '', // Unique ID of the menu item
        'capability' => 'manage_options', // User role
        'icon' => 'dashicons-admin-generic', // Menu icon for top level menus only http://melchoyce.github.io/dashicons/
        'position' => null, // Menu position. Can be used for both top and sub level menus
        'use_form' => true, // If false, options will not be wrapped in a form
    );

    public $settings;
    public $options = array();
    public $tabs = array();
    public $owner;

    public $panelID;

    private $activeTab = null;
    private static $idsUsed = array();

    function __construct( $settings, $owner ) {
        $this->owner = $owner;

        if ( ! is_admin() ) {
            return;
        }

        $this->settings = array_merge( $this->defaultSettings, $settings );
        // $this->options = $options;

        if ( empty( $this->settings['name'] ) ) {
            return;
        }

        if ( empty( $this->settings['title'] ) ) {
            $this->settings['title'] = $this->settings['name'];
        }

        if ( empty( $this->settings['id'] ) ) {
            $prefix = '';
            if ( ! empty( $this->settings['parent'] ) ) {
                $prefix = str_replace( ' ', '-', trim( strtolower( $this->settings['parent'] ) ) ) . '-';
            }
            $this->settings['id'] = $prefix . str_replace( ' ', '-', trim( strtolower( $this->settings['name'] ) ) );
        }

        // make sure all our IDs are unique
        $suffix = '';
        while ( in_array( $this->settings['id'] . $suffix, self::$idsUsed ) ) {
            if ( $suffix === '' ) {
                $suffix = 2;
            } else {
                $suffix++;
            }
        }
        $this->settings['id'] .= $suffix;

        // keep track of all IDs used
        self::$idsUsed[] = $this->settings['id'];

        $priority = -1;
        if ( $this->settings['parent'] ) {
            $priority = intval( $this->settings['position'] );
        }

        add_action( 'admin_menu', array( $this, 'register' ), $priority );
    }

    public function createAdminPanel( $settings ) {
        $settings['parent'] = $this->settings['id'];
        return $this->owner->createAdminPanel( $settings );
    }

    public function register() {
        // Parent menu
        if ( empty( $this->settings['parent'] ) ) {
            $this->panelID = add_menu_page( $this->settings['name'],
                           $this->settings['name'],
                           $this->settings['capability'],
                           $this->settings['id'],
                           array( $this, 'createAdminPage' ),
                           $this->settings['icon'],
                           $this->settings['position'] );
        // Sub menu
        } else {
            $this->panelID = add_submenu_page( $this->settings['parent'],
                              $this->settings['name'],
                              $this->settings['name'],
                              $this->settings['capability'],
                              $this->settings['id'],
                              array( $this, 'createAdminPage' ) );
        }

        add_action( 'load-' . $this->panelID, array( $this, 'saveOptions' ) );
    }

    protected function getOptionNamespace() {
        return $this->owner->optionNamespace;
    }

    public function saveOptions() {
        if ( ! $this->verifySecurity() ) {
            return;
        }

        $message = '';

        /*
         *  Save
         */

        if ( $_POST['action'] == 'save' ) {
            // we are in a tab
            $activeTab = $this->getActiveTab();
            if ( ! empty( $activeTab ) ) {
                foreach ( $activeTab->options as $option ) {
                    if ( empty( $option->settings['id'] ) ) {
                        continue;
                    }

                    if ( ! empty( $_POST[$this->getOptionNamespace() . '_' . $option->settings['id']] ) ) {
                        $value = $_POST[$this->getOptionNamespace() . '_' . $option->settings['id']];
                    } else {
                        $value = '';
                    }
                    // $value = $option->cleanValueForSaving( $value );
                    $this->owner->setOption( $option->settings['id'], $value );
                }
            }

            foreach ( $this->options as $option ) {
                if ( empty( $option->settings['id'] ) ) {
                    continue;
                }

                if ( ! empty( $_POST[$this->getOptionNamespace() . '_' . $option->settings['id']] ) ) {
                    $value = $_POST[$this->getOptionNamespace() . '_' . $option->settings['id']];
                } else {
                    $value = '';
                }

                $this->owner->setOption( $option->settings['id'], $value );
            }
            $this->owner->saveOptions();

            $message = 'saved';

        /*
         * Reset
         */

        } else if ( $_POST['action'] == 'reset' ) {
            // we are in a tab
            $activeTab = $this->getActiveTab();
            if ( ! empty( $activeTab ) ) {
                foreach ( $activeTab->options as $option ) {
                    if ( empty( $option->settings['id'] ) ) {
                        continue;
                    }

                    $this->owner->setOption( $option->settings['id'], $option->settings['default'] );
                }
            }

            foreach ( $this->options as $option ) {
                if ( empty( $option->settings['id'] ) ) {
                    continue;
                }

                $this->owner->setOption( $option->settings['id'], $option->settings['default'] );
            }
            $this->owner->saveOptions();

            $message = 'reset';
        }

        /*
         * Redirect to prevent refresh saving
         */

        // urlencode to allow special characters in the url
        $activeTab = $this->getActiveTab();
        $args = '?page=' . urlencode( $this->settings['id'] );
        $args .= empty( $activeTab ) ? '' : '&tab=' . urlencode( $activeTab->settings['id'] );
        $args .= empty( $message ) ? '' : '&message=' . $message;

        wp_redirect( admin_url( 'admin.php' . $args ) );
    }

    private function verifySecurity() {
        if ( empty( $_POST ) || empty( $_POST['action'] ) ) {
            return false;
        }

        $screen = get_current_screen();
        if ( $screen->id != $this->panelID ) {
            return false;
        }

        if ( ! current_user_can( $this->settings['capability'] ) ) {
            return false;
        }

        if ( ! check_admin_referer( $this->settings['id'], TF . '_nonce' ) ) {
            return false;
        }

        return true;
    }

    public function getActiveTab() {
        if ( ! count( $this->tabs ) ) {
            return '';
        }
        if ( ! empty( $this->activeTab ) ) {
            return $this->activeTab;
        }

        if ( empty( $_GET['tab'] ) ) {
            $this->activeTab = $this->tabs[0];
            return $this->activeTab;
        }

        foreach ( $this->tabs as $tab ) {
            if ( $tab->settings['id'] == $_GET['tab'] ) {
                $this->activeTab = $tab;
                return $this->activeTab;
            }
        }
    }

    public function createAdminPage() {
        ?>
        <div class='wrap titan-framework-panel-wrap'>
        <?php

        if ( ! count( $this->tabs ) ):
            ?>
            <h2><?php echo $this->settings['title'] ?></h2>
            <?php
        endif;

        if ( count( $this->tabs ) ):
            ?>
            <h2 class="nav-tab-wrapper">
            <?php
            foreach ( $this->tabs as $tab ) {
                $tab->displayTab();
            }
            ?>
            </h2>
            <h2><?php echo $this->getActiveTab()->settings['title'] ?></h2>
            <?php
        endif;

        // Display notification if we did something
        if ( ! empty( $_GET['message'] ) ) {
            if ( $_GET['message'] == 'saved' ) {
                echo TitanFrameworkAdminNotification::formNotification( __( 'Settings saved.', TF_I18NDOMAIN ), $_GET['message'] );
            } else if ( $_GET['message'] == 'reset' ) {
                echo TitanFrameworkAdminNotification::formNotification( __( 'Settings reset to default.', TF_I18NDOMAIN ), $_GET['message'] );
            }
        }

        if ( $this->settings['use_form'] ):
            ?>
            <form method='post'>
            <?php
        endif;

        if ( $this->settings['use_form'] ) {
            // security
            wp_nonce_field( $this->settings['id'], TF . '_nonce' );
        }

        ?>
        <table class='form-table'>
            <tbody>
        <?php

        $activeTab = $this->getActiveTab();
        if ( ! empty( $activeTab ) ) {
            $activeTab->displayOptions();
        }

        foreach ( $this->options as $option ) {
            $option->display();
        }

        ?>
            </tbody>
        </table>
        <?php

        if ( $this->settings['use_form'] ):
            ?>
            </form>
            <?php
        endif;

        // Reset form. We use JS to trigger a reset from other buttons within the main form
        // This is used by class-option-save.php
        if ( $this->settings['use_form'] ):
            ?>
            <form method='post' id='tf-reset-form'>
                <?php
                // security
                wp_nonce_field( $this->settings['id'], TF . '_nonce' );
                ?>
                <input type='hidden' name='action' value='reset'/>
            </form>
            <?php
        endif;
        ?>

        </div>
        <?php
    }

    public function createTab( $settings ) {
        $obj = new TitanFrameworkAdminTab( $settings, $this );
        $this->tabs[] = $obj;
        return $obj;
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