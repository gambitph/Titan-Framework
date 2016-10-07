<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly.
}
class TitanFrameworkOptionUpload extends TitanFrameworkOption {

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

		add_filter( 'tf_generate_css_upload_' . $this->getOptionNamespace(), array( $this, 'generateCSS' ), 10, 2 );
		add_filter( 'upload_mimes', array( $this, 'allow_svg_uploads' ) );

		add_action( 'tf_livepreview_pre_' . $this->getOptionNamespace(), array( $this, 'preLivePreview' ), 10, 3 );
		add_action( 'tf_livepreview_post_' . $this->getOptionNamespace(), array( $this, 'postLivePreview' ), 10, 3 );
		}


		/**
		* Allow SVG mime types to be uploaded to WP.
		*
		* @since 2.9
		*
		* @param array $mimes The allowed mime types.
		*
		* @return array $mimes The modified mime types.
		*/
		public function allow_svg_uploads( $mimes ) {
				$mimes['svg'] = 'image/svg+xml';
				return apply_filters( 'pbs_allow_svg_uploads', $mimes );
		}


	/**
	 * Generates CSS for the font, this is used in TitanFrameworkCSS
	 *
	 * @param	string               $css The CSS generated
	 * @param	TitanFrameworkOption $option The current option being processed.
	 * @return	string The CSS generated
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

		if ( is_numeric( $value ) ) {
			$size = ! empty( $option->settings['size'] ) ? $option->settings['size'] : 'thumbnail';
			$attachment = wp_get_attachment_image_src( $value, $size );
			$value = $attachment[0];
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
	 * The upload option gives out an attachment ID. Live previews will not work since we cannot get
	 * the upload URL from an ID easily. Use a specially created Ajax Handler for just getting the URL.
	 *
	 * @since 1.9
	 *
	 * @see tf_upload_option_customizer_get_value()
	 */
	public function preLivePreview( $optionID, $optionType, $option ) {
		if ( $optionID != $this->settings['id'] ) {
			return;
		}

		$nonce = wp_create_nonce( 'tf_upload_option_nonce' );
		$size = ! empty( $this->settings['size'] ) ? $this->settings['size'] : 'thumbnail';

		?>
		wp.ajax.send( 'tf_upload_option_customizer_get_value', {
		    data: {
				nonce: '<?php echo esc_attr( $nonce ) ?>',
				size: '<?php echo esc_attr( $size ) ?>',
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

		$previewImage = '';
		if ( ! empty( $value ) ) {
			$previewImage = "<i class='dashicons dashicons-no-alt remove'></i><img src='" . esc_url( $value ) . "' style='display: none'/>";
		}
		echo "<div class='thumbnail tf-image-preview'>" . $previewImage . '</div>';

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
		$wp_customize->add_control( new TitanFrameworkOptionUploadControl( $wp_customize, $this->getID(), array(
			'label' => $this->settings['name'],
			'section' => $section->getID(),
			'settings' => $this->getID(),
			'description' => $this->settings['desc'],
			'priority' => $priority,
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
			$('.tf-upload .thumbnail').find('img').parent().addClass('has-value').find(':before').css({'opacity':'0'});
			function tfUploadOptionCenterImage($this) {
				var _preview = $this.parents('.tf-upload').find('.thumbnail');
				$this.css({
					'marginTop': ( _preview.height() - $this.height() ) / 2,
					'marginLeft': ( _preview.width() - $this.width() ) / 2
				}).show();
			}


			// Calculate display offset of preview image on load.
			$('.tf-upload .thumbnail img').load(function() {
				tfUploadOptionCenterImage($(this));
			}).each(function(){
				// Sometimes the load event might not trigger due to cache.
				if(this.complete) {
					$(this).trigger('load');
				};
			});


			// In the theme customizer, the load event above doesn't work because of the accordion,
			// the image's height & width are detected as 0. We bind to the opening of an accordion
			// and adjust the image placement from there.
			var tfUploadAccordionSections = [];
			$('.tf-upload').each(function() {
				var $accordion = $(this).parents('.control-section.accordion-section');
				if ( $accordion.length > 0 ) {
					if ( $.inArray( $accordion, tfUploadAccordionSections ) == -1 ) {
						tfUploadAccordionSections.push($accordion);
					}
				}
			});
			$.each( tfUploadAccordionSections, function() {
				var $title = $(this).find('.accordion-section-title:eq(0)'); // Just opening the section.
				$title.click(function() {
					var $accordion = $(this).parents('.control-section.accordion-section');
					if ( ! $accordion.is('.open') ) {
						$accordion.find('.tf-upload .thumbnail img').each(function() {
							var $this = $(this);
							setTimeout(function() {
								tfUploadOptionCenterImage($this);
							}, 1);
						});
					}
				});
			});


			// Remove the image when the remove link is clicked.
			$('body').on('click', '.tf-upload i.remove', function(event) {
				event.preventDefault();
				var _input = $(this).parents('.tf-upload').find('input');
				var _preview = $(this).parents('.tf-upload').find('div.thumbnail');

				_preview.removeClass('has-value').find('img').remove().end().find('i').remove();
				_input.val('').trigger('change');

				return false;
			});


			// Open the upload media lightbox when the upload button is clicked.
			$('body').on('click', '.tf-upload .thumbnail, .tf-upload img', function(event) {
				event.preventDefault();
				// If we have a smaller image, users can click on the thumbnail.
				if ( $(this).is('.thumbnail') ) {
					if ( $(this).parents('.tf-upload').find('img').length != 0 ) {
						$(this).parents('.tf-upload').find('img').trigger('click');
						return true;
					}
				}

				var _input = $(this).parents('.tf-upload').find('input');
				var _preview = $(this).parents('.tf-upload').find('div.thumbnail');
				var _remove = $(this).siblings('.tf-upload-image-remove');

				// Uploader frame properties.
				var frame = wp.media({
					title: '<?php esc_html_e( 'Select Image', TF_I18NDOMAIN ) ?>',
					multiple: false,
					library: { type: 'image' },
					button : { text : '<?php esc_html_e( 'Use image', TF_I18NDOMAIN ) ?>' }
				});

				// Get the url when done.
				frame.on('select', function() {
					var selection = frame.state().get('selection');
					selection.each(function(attachment) {

						// if ( typeof attachment.attributes.sizes === 'undefined' ) {
						// 	return;
						// }

						if ( _input.length > 0 ) {
							_input.val(attachment.id);
						}

						if ( _preview.length > 0 ) {
							// remove current preview
							if ( _preview.find('img').length > 0 ) {
								_preview.find('img').remove();
							}
							if ( _preview.find('i.remove').length > 0 ) {
								_preview.find('i.remove').remove();
							}

							// Get the preview image.
						if ( typeof attachment.attributes.sizes != 'undefined' ) {
								var image = attachment.attributes.sizes.full;
 								if ( typeof attachment.attributes.sizes.thumbnail != 'undefined' ) {
 									image = attachment.attributes.sizes.thumbnail;
 								}
 								var url = image.url;
 								var marginTop = ( _preview.height() - image.height ) / 2;
 								var marginLeft = ( _preview.width() - image.width ) / 2;
 								var filename = '';
 							} else {
 								var url = attachment.attributes.url;
 								var marginTop = ( _preview.height() - 64 ) / 2;
 								var marginLeft = ( _preview.width() - 48 ) / 2;
 								var filename = attachment.attributes.filename;
							}

							$("<img src='" + url + "'/>").appendTo(_preview);
							$("<i class='dashicons dashicons-no-alt remove'></i>").prependTo(_preview);
						}
						// We need to trigger a change so that WP would detect that we changed the value.
						// Or else the save button won't be enabled.
						_input.trigger('change');

						_remove.show();
						$('.tf-upload .thumbnail').find('img').parent().addClass('has-value').find(':before').css({'opacity':'0'});
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
add_action( 'customize_register', 'registerTitanFrameworkOptionUploadControl', 1 );
function registerTitanFrameworkOptionUploadControl() {
	class TitanFrameworkOptionUploadControl extends WP_Customize_Control {
		public $description;

		public function render_content() {
			TitanFrameworkOptionUpload::createUploaderScript();

			$previewImage = '';
			$value = $this->value();
			if ( is_numeric( $value ) ) {
				// Gives us an array with the first element as the src or false on fail.
				$value = wp_get_attachment_image_src( $value, array( 150, 150 ) );
			}
			if ( ! is_array( $value ) ) {
				$value = $this->value();
			} else {
				$value = $value[0];
			}

			if ( ! empty( $value ) ) {
				$previewImage = "<i class='dashicons dashicons-no-alt remove'></i><img src='" . esc_url( $value ) . "' style='display: none'/>";
			}

			?>
			<div class='tf-upload'>
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
				<div class='thumbnail tf-image-preview'><?php echo $previewImage ?></div>
				<input type='hidden' value="<?php echo esc_attr( $this->value() ); ?>" <?php $this->link(); ?>/>
			</div>
			<?php

			if ( ! empty( $this->description ) ) {
				echo "<p class='description'>{$this->description}</p>";
			}
		}
	}
}



if ( ! function_exists( 'tf_upload_option_customizer_get_value' ) ) {

	add_action( 'wp_ajax_tf_upload_option_customizer_get_value', 'tf_upload_option_customizer_get_value' );

	/**
	 * Returns the image URL from an attachment ID & size.
	 *
	 * @see TitanFrameworkOptionUpload->preLivePreview().
	 */
	function tf_upload_option_customizer_get_value() {

		if ( ! empty( $_POST['nonce'] ) && ! empty( $_POST['id'] ) && ! empty( $_POST['size'] ) ) {

			$nonce = sanitize_text_field( $_POST['nonce'] );
			$attachmentID = sanitize_text_field( $_POST['id'] );
			$size = sanitize_text_field( $_POST['size'] );

			if ( wp_verify_nonce( $nonce, 'tf_upload_option_nonce' ) ) {
				$attachment = wp_get_attachment_image_src( $attachmentID, $size );
				if ( ! empty( $attachment ) ) {
					wp_send_json_success( $attachment[0] );
				}
			}
		}

		// Instead of doing a wp_send_json_error, send a blank value instead so.
		// Javascript adjustments still get executed.
		wp_send_json_success( '' );
	}
}
