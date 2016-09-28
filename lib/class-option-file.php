<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly.
}
class TitanFrameworkOptionFile extends TitanFrameworkOption {

    private static $firstLoad = true;

    public $defaultSecondarySettings = array(
        'placeholder' => '', // Show this when blank.
        'label' => '', // Add label.
    );


    /**
     * Constructor.
     *
     * @return	void
     * @since	1.5
     */
    function __construct( $settings, $owner ) {
        parent::__construct( $settings, $owner );

        add_filter( 'tf_generate_css_upload_' . $this->getOptionNamespace(), array( $this, 'generateCSS' ), 10, 2 );

        add_action( 'tf_livepreview_pre_' . $this->getOptionNamespace(), array( $this, 'preLivePreview' ), 10, 3 );
        add_action( 'tf_livepreview_post_' . $this->getOptionNamespace(), array( $this, 'postLivePreview' ), 10, 3 );
    }


    /**
     * Generates CSS for the font, this is used in TitanFrameworkCSS.
     *
     * @param	String               $css The CSS generated.
     * @param	TitanFrameworkOption $option The current option being processed.
     * @return	String The CSS generated.
     * @since	1.5
     */
    public function generateCSS( $css, $option ) {
        if ( $this->settings['id'] != $option->settings['id'] ) {
            return $css;
        }

        $value = $this->getValue();

        if ( empty( $value ) ) {
            return $css;
        }

        $css .= '$' . $option->settings['id'] . ': url(' . $value . ');';

        if ( ! empty( $option->settings['css'] ) ) {
            // In the css parameter, we accept the term `value` as our current value,
            // translate it into the SaSS variable for the current option.
            $css .= str_replace( 'value', '#{$' . $option->settings['id'] . '}', $option->settings['css'] );
        }

        return $css;
    }


    /**
     * The upload option gives out an attachment ID. Live previews will not work since we cannot get.
     * the upload URL from an ID easily. Use a specially created Ajax Handler for just getting the URL.
     *
     * @since 1.9
     *
     * @see tf_file_upload_option_customizer_get_value()
     */
    public function preLivePreview( $optionID, $optionType, $option ) {
        if ( $optionID != $this->settings['id'] ) {
            return;
        }

        $nonce = wp_create_nonce( 'tf_file_upload_option_nonce' );

        ?>
        wp.ajax.send( 'tf_file_upload_option_customizer_get_value', {
        data: {
        nonce: '<?php echo esc_attr( $nonce ); ?>',
        id: value
        },
        success: function( data ) {
        var $ = jQuery;
        var value = data;
        <?php
    }


    /**
     * Closes the Javascript code created in preLivePreview().
     *
     * @since 1.9
     *
     * @see preLivePreview()
     */
    public function postLivePreview( $optionID, $optionType, $option ) {
        if ( $optionID != $this->settings['id'] ) {
            return;
        }

        // Close the ajax call.
        ?>
        }
        });
        <?php
    }

    /*
     * Display for options and meta.
     */
    public function display() {
        self::createUploaderScript();

        $this->echoOptionHeader();

        // Display the preview file name.
        $value = $this->getValue();
        if ( ! is_array( $value ) ) {
            $value = $this->getValue();
        }

        $previewFile = '';
        if ( ! empty( $value ) ) {
            $previewFile = "<i class='dashicons dashicons-no-alt remove'></i><p>". basename( get_attached_file( $value ) ) . "</p>";
        } else {
          $previewFile = $this->settings['label'];
        }
        echo "<div class='tf-file-upload'>" . $previewFile . '</div>';

        printf('<input name="%s" placeholder="%s" id="%s" type="hidden" value="%s" />',
            $this->getID(),
            $this->settings['placeholder'],
            $this->getID(),
            esc_attr( $this->getValue() )
        );
        $this->echoOptionFooter();
    }

    /*
     * Display for theme customizer.
     */
    public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
        $wp_customize->add_control( new TitanFrameworkOptionFileUploadControl( $wp_customize, $this->getID(), array(
            'label' => $this->settings['name'],
            'section' => $section->getID(),
            'settings' => $this->getID(),
            'description' => $this->settings['desc'],
            'priority' => $priority,
            'caption' => $this->settings['label'],
        ) ) );
    }

    public static function createUploaderScript() {
        if ( ! self::$firstLoad ) {
            return;
        }
        self::$firstLoad = false;

        ?>
        <script>
            jQuery(document).ready(function($){
                "use strict";

                // In the theme customizer, the load event above doesn't work because of the accordion,
                // the image's height & width are detected as 0. We bind to the opening of an accordion
                // and adjust the image placement from there.
                var tfUploadAccordionSections = [];
                $('.tf-file-upload').each(function() {
                    var $accordion = $(this).parents('.control-section.accordion-section');
                    if ( $accordion.length > 0 ) {
                        if ( $.inArray( $accordion, tfUploadAccordionSections ) == -1 ) {
                            tfUploadAccordionSections.push($accordion);
                        }
                    }
                });
                $.each( tfUploadAccordionSections, function() {
                    var $title = $(this).find('.accordion-section-title:eq(0)'); // just opening the section
                    $title.click(function() {
                        var $accordion = $(this).parents('.control-section.accordion-section');
                        if ( ! $accordion.is('.open') ) {
                            $accordion.find('.tf-file-upload .thumbnail img').each(function() {
                                var $this = $(this);
                            });
                        }
                    });
                });


                // Remove the image when the remove link is clicked.
                $('body').on('click', '.tf-file-upload i.remove', function(event) {
                    event.preventDefault();
                    var _input = $(this).parents('.tf-file').find('input');
                    _input.siblings('.tf-file-upload').html('');
                    _input.val('').trigger('change');

                    return false;
                });


                // Open the upload media lightbox when the upload button is clicked.
                $('body').on('click', '.tf-file-upload', function(event) {
                    event.preventDefault();
                    // If we have a smaller image, users can click on the thumbnail.
                    var _this = $(this);
                    var _input = $(this).parents('.tf-file').find('input');
                    var _remove = $(this).siblings('.tf-file-upload-remove');

                    // Uploader frame properties.
                    var frame = wp.media({
                        title: '<?php esc_html_e( 'Select File', TF_I18NDOMAIN ) ?>',
                        multiple: false,
                        button : { text : '<?php esc_html_e( 'Use file', TF_I18NDOMAIN ) ?>' }
                    });

                    // Get the url when done.
                    frame.on('select', function() {
                        var selection = frame.state().get('selection');
                        selection.each(function(attachment) {
                            _input.val(attachment.id).trigger('change');
                            // document.getElementById(_input.id).value = attachment.id;
                            console.info(_input.val());
                            // Change filename.
                            _this.html("<i class='dashicons dashicons-no-alt remove'></i><p>"+attachment.attributes.filename+"</p>");

                            _remove.show();
                        });
                        frame.off('select');
                    });

                    // Open the uploader.
                    frame.open();

                    return false;
                });
            });
        </script>
        <?php
    }
}

/*
 * We create a new control for the theme customizer.
 */
add_action( 'customize_register', 'registerTitanFrameworkOptionFileUploadControl', 1 );
function registerTitanFrameworkOptionFileUploadControl() {
    class TitanFrameworkOptionFileUploadControl extends WP_Customize_Control {
        public $description;
        public $caption;
        
        public function render_content() {
            TitanFrameworkOptionFile::createUploaderScript();

            $previewFile = '';
            $value = $this->value();
            if ( ! is_array( $value ) ) {
                $value = $this->value();
            }

            if ( ! empty( $value ) ) {
                $previewFile = "<i class='dashicons dashicons-no-alt remove'></i><p>" . basename( get_attached_file( $value ) ) . "</p>";
            } else {
              $previewFile = $this->caption;
            }

            ?>
            <div class='tf-file'>
                <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
                <div class='tf-file-upload'><?php echo $previewFile ?></div>
                <input type='hidden' value="<?php echo esc_attr( $this->value() ); ?>" <?php $this->link(); ?>/>
            </div>
            <?php

            if ( ! empty( $this->description ) ) {
                echo "<p class='description'>{$this->description}</p>";
            }
        }
    }
}



if ( ! function_exists( 'tf_file_upload_option_customizer_get_value' ) ) {

    add_action( 'wp_ajax_tf_file_upload_option_customizer_get_value', 'tf_file_upload_option_customizer_get_value' );

    /**
     * Returns the image URL from an attachment ID & size.
     *
     * @see TitanFrameworkOptionUpload->preLivePreview().
     */
    function tf_file_upload_option_customizer_get_value() {

        if ( ! empty( $_POST['nonce'] ) && ! empty( $_POST['id'] ) ) {

            $nonce = sanitize_text_field( $_POST['nonce'] );
            $attachmentID = sanitize_text_field( $_POST['id'] );
            // $size = sanitize_text_field( $_POST['size'] );

            if ( wp_verify_nonce( $nonce, 'tf_file_upload_option_nonce' ) ) {
                $attachment = $attachmentID;
                if ( ! empty( $attachment ) ) {
                    wp_send_json_success( $attachment );
                }
            }
        }

        // Instead of doing a wp_send_json_error, send a blank value instead so.
        // Javascript adjustments still get executed.
        wp_send_json_success( '' );
    }
}
