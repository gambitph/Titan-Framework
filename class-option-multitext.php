<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class TitanFrameworkOptionMultitext extends TitanFrameworkOption {

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
        echo "<input name='".$this->getID()."' type='hidden' class='repeaterjson' id='{$this->getID()}' value='".join("|||",$this->getValue())."' >";
        echo "<div class='repeaterplaceholder'></div>";
        echo "<div class='repeater' style='margin-top:10px;'><input type='button' class='repeaterbtn button button-primary' value='Add More'/> </div>";
        $this->echoOptionFooter();
    }

    function enqueueScripts(){
        wp_enqueue_script("tf-repeatable",get_template_directory_uri()."/libs/titan-framework/js/repeatable.js","jquery","1.0",true);
    }

    function cleanValueForGetting($value){

        if( !is_array($value)){
            $parts = explode("|||",$value);
            return $parts;
        }else{
            return $value;
        }

    }

}