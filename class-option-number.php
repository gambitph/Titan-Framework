<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class TitanFrameworkOptionNumber extends TitanFrameworkOption {

    public $defaultSecondarySettings = array(
        'size' => 'medium', // or medium or large
        'placeholder' => '', // show this when blank
        'min' => 0,
        'max' => 1000,
        'step' => 1,
    );

    /*
     * Display for options and meta
     */
    public function display() {
        $this->echoOptionHeader();
        printf("<input class=\"%s-text\" name=\"%s\" placeholder=\"%s\" id=\"%s\" type=\"number\" value=\"%s\" min=\"%s\" max=\"%s\" step=\"%s\"\> %s",
            $this->settings['size'],
            $this->getID(),
            $this->settings['placeholder'],
            $this->getID(),
            esc_attr( $this->getValue() ),
            $this->settings['min'],
            $this->settings['max'],
            $this->settings['step'],
            $this->settings['desc']
        );
        $this->echoOptionFooter( false );
    }

    /*
     * Display for theme customizer
     */
    public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
        $wp_customize->add_control( new TitanFrameworkOptionNumberControl( $wp_customize, $this->getID(), array(
            'label' => $this->settings['name'],
            'section' => $section->getID(),
            'settings' => $this->getID(),
            'description' => $this->settings['desc'],
            'priority' => $priority,
        ) ) );
    }
}

/*
 * WP_Customize_Control with description
 */
add_action( 'customize_register', 'registerTitanFrameworkOptionNumberControl', 1 );
function registerTitanFrameworkOptionNumberControl() {
    class TitanFrameworkOptionNumberControl extends WP_Customize_Control {
        public $description;

        public function render_content() {
            ?>
            <label>
                <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
                <input type="number" value="<?php echo esc_attr( $this->value() ); ?>" <?php $this->link(); ?> />
            </label>
            <?php
            echo "<p class='description'>{$this->description}</p>";
        }
    }
}