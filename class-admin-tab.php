<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class TitanFrameworkAdminTab {

    private $defaultSettings = array(
        'name' => '', // Name of the tab
        'id' => '', // Unique ID of the tab
        'title' => '', // Title to display in the admin panel when tab is active
    );

    public $options = array();
    public $settings;
    public $owner;

    function __construct( $settings, $owner ) {
        $this->owner = $owner;
        $this->settings = array_merge( $this->defaultSettings, $settings );

        if ( empty( $this->settings['title'] ) && ! empty( $this->settings['name'] ) ) {
            $this->settings['title'] = $this->settings['name'];
        }
        if ( ! empty( $this->settings['title'] ) && empty( $this->settings['name'] ) ) {
            $this->settings['name'] = $this->settings['title'];
        }

        if ( empty( $this->settings['id'] ) ) {
            $this->settings['id'] = str_replace( ' ', '-', trim( strtolower( $this->settings['name'] ) ) );
        }
    }

    public function isActiveTab() {
        return $this->settings['id'] == $this->owner->getActiveTab()->settings['id'];
    }

    public function createOption( $settings ) {
        $obj = TitanFrameworkOption::factory( $settings, $this );
        // $obj = new TitanFrameworkOption( $settings, $this );
        $this->options[] = $obj;

        if ( ! empty( $obj->settings['id'] ) ) {
            $this->owner->owner->optionsUsed[$obj->settings['id']] = $obj;
        }

        do_action( 'tf_create_option', $obj );

        return $obj;
    }

    public function displayTab() {
        ?>
        <a href="?page=<?php echo $this->owner->settings['id'] ?>&tab=<?php echo $this->settings['id'] ?>" class="nav-tab <?php echo $this->isActiveTab() ? "nav-tab-active" : '' ?>"><?php echo $this->settings['name'] ?></a>
        <?php
    }

    public function displayOptions() {
        foreach ( $this->options as $option ) {
            $option->display();
        }
    }
}
?>