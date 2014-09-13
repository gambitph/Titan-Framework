<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class TitanFrameworkOptionGallery extends TitanFrameworkOption {


    function __construct($settings, $owner){
        parent::__construct($settings,$owner);
        add_action("admin_enqueue_scripts",array($this,'enqueueScripts'));
    }
    /*
     * Display for options and meta
     */
    public function display() {
        if(!isset($this->settings['dependency'])) $this->settings['dependency']=array("id"=>"","value"=>"");
        $this->echoOptionHeader();
        echo "Galery Placeholder";
        echo "<input name='".$this->getID()."'  type='hidden' value='".$this->getValue()."'/>";
        echo "<input type='button' value='click' id='galgal'>";
        $this->echoOptionFooter();
    }

    public function enqueueScripts(){
        wp_enqueue_media();
        wp_enqueue_script("tf-gallery",get_template_directory_uri()."/libs/titan-framework/js/gallery.js","jquery","1.0",true);
    }



}