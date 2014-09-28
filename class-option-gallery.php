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
        echo "<ul class='gallery-ph'></ul>";
        echo "<input class='galleryinfo' data-did='{$this->settings['dependency']['id']}' data-dvalue='{$this->settings['dependency']['value']}'  name='".$this->getID()."'  type='hidden' value='".$this->getValue()."'/>";
        echo "<input type='button' data-multiple='true' value='Add Images To Gallery' class='galgal button button-primary button-large'>";
        echo "<input type='button' value='Clear' style='margin-left:10px;' class='galgalremove button button-large' >";
        $this->echoOptionFooter();
    }

    public function enqueueScripts(){
        wp_enqueue_media();
        wp_enqueue_script("tf-gallery",get_template_directory_uri()."/libs/titan-framework/js/gallery.js","jquery","1.0",true);
        wp_enqueue_style("tf-gallery-css",get_template_directory_uri()."/libs/titan-framework/css/class-option-gallery.css");
    }



}
