<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly.
}
class TitanFrameworkOptionGallery extends TitanFrameworkOption {

	private static $firstLoad = true;

	public $defaultSecondarySettings = array(
		'size' => 'full', // The size of the image to use in the generated CSS.
		'placeholder' => '', // Show this when blank.
	);


	/**
	 * Constructor.
	 *
	 * @return	void
	 * @since	1.5
	 */
	function __construct( $settings, $owner ) {
		parent::__construct( $settings, $owner );
	}


	/*
	 * Display for options and meta.
	 */
	public function display() {
		self::createUploaderScript();

		$this->echoOptionHeader();

		// Display the preview image.
		$value = $this->getValue();

		if ( is_numeric( $value ) ) {
			// Gives us an array with the first element as the src or false on fail.
			$value = wp_get_attachment_image_src( $value, array( 150, 150 ) );
		}
		if ( ! is_array( $value ) ) {
			$value = $this->getValue();
		} else {
			$value = $value[0];
		}

		// $value = '101,96';.
		$value_arr = explode( ',', $value );

		foreach ( $value_arr as $k => $v ) {
			$previewImage = '';
			$value = $v;
			if ( ! empty( $v ) ) {
				$size = ! empty( $option->settings['size'] ) ? $option->settings['size'] : 'thumbnail';

				if ( is_numeric( $v ) ) {
					$attachment = wp_get_attachment_image_src( $v, $size );
					$v = $attachment[0];
				}

				$previewImage = "<i class='dashicons dashicons-no-alt remove'></i><img style='max-width: 150px; max-height: 150px; margin-top: 0px; margin-left: 0px;' src='" . esc_url( $v ) . "' style='display: none'/>";
				echo "<div data-attachment-id=" . $value . " class='thumbnail used-thumbnail tf-image-preview'>" . $previewImage . '</div>';
			}
		}
		echo "<div class='thumbnail tf-image-preview'></div>";

		printf('<input name="%s" placeholder="%s" id="%s" type="hidden" value="%s" />',
			$this->getID(),
			$this->settings['placeholder'],
			$this->getID(),
			esc_attr( $this->getValue() )
		);

		$this->echoOptionFooter();
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

    	function tfUploadOptionCenterImage($this) {

				var _preview = $this.parents('.tf-gallery').find('.thumbnail');
				$this.css({
					'marginTop': ( _preview.height() - $this.height() ) / 2,
					'marginLeft': ( _preview.width() - $this.width() ) / 2
				}).show();
			}


			// Calculate display offset of preview image on load.
			$('.tf-gallery .thumbnail img').load(function() {
				tfUploadOptionCenterImage($(this));
			}).each(function(){
				// Sometimes the load event might not trigger due to cache.
				if(this.complete) {
					$(this).trigger('load');
				};
			});


			// Creating attachments arr.
      var get_attachments_of_gallery = function(preview, input) {
        var $attachments_str = [];
        preview.find('.used-thumbnail').each(function(i, object){
            $attachments_str.push($(object).attr('data-attachment-id'));
        });
        input.val($attachments_str.join(','));
        input.trigger('change');
      }


			// Remove the image when the remove link is clicked.
			$('body').on('click', '.tf-gallery i.remove', function(event) {
				event.preventDefault();
				var _input = $(this).parents('.tf-gallery').find('input');
				var _preview = $(this).parents('.tf-gallery');

				$(this).parents('.thumbnail').remove();
				get_attachments_of_gallery(_preview, _input);

        return false;
			});


			// Open the upload media lightbox when the upload button is clicked.
			$('body').on('click', '.tf-gallery .thumbnail, .tf-gallery img', function(event) {
				event.preventDefault();

				var _input = $(this).parents('.tf-gallery').find('input');
				var _preview = $(this).parents('.tf-gallery');
				var _remove = $(this).siblings('.tf-gallery-image-remove');

				// Uploader frame properties.
				var frame = wp.media({
					title: '<?php esc_html_e( 'Select Image', TF_I18NDOMAIN ) ?>',
					multiple: true,
					library: { type: 'image' },
					button : { text : '<?php esc_html_e( 'Use image', TF_I18NDOMAIN ) ?>' }
				});

				// Get the url when done.
				frame.on('select', function() {
					var selection = frame.state().get('selection');

            if ( _preview.find('div.thumbnail').length > 0 ) {
                // Remove current preview.
                _preview.find('.used-thumbnail').remove();
            }

            var $attachments_str = [];
            selection.each(function(attachment) {

							if ( typeof attachment.attributes.sizes === 'undefined' ) {
								return;
							}

              $attachments_str.push(attachment.id);

              // Get the preview image.
              var image = attachment.attributes.sizes.full;
              if ( typeof attachment.attributes.sizes.thumbnail != 'undefined' ) {
                  image = attachment.attributes.sizes.thumbnail;
              }
              var url = image.url;
              // var marginTop = ( _preview.height() - image.height ) / 2;
              // var marginLeft = ( _preview.width() - image.width ) / 2;

              $("<div data-attachment-id='"+attachment.id+"' class='thumbnail used-thumbnail tf-image-preview'><i class='dashicons dashicons-no-alt remove'></i><img style='max-width: 150px; max-height: 150px; margin-top: 0px; margin-left: 0px;' src='" + url + "'/></div>").prependTo(_preview);

						_remove.show();
					});

            frame.off('select');


            // Updating the attachments input field.
            if ( _input.length > 0 ) {
                _input.val($attachments_str.join(','));
            }

            // We need to trigger a change so that WP would detect that we changed the value.
            // Or else the save button won't be enabled.
            _input.trigger('change');
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
