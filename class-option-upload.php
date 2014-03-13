<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class TitanFrameworkOptionUpload extends TitanFrameworkOption {

	private static $firstLoad = true;

	public $defaultSecondarySettings = array(
		'placeholder' => '', // show this when blank
	);

	/*
	 * Display for options and meta
	 */
	public function display() {
		self::createUploaderScript();

		$this->echoOptionHeader();

		// display the preview image
		$value = $this->getValue();
		$previewImage = '';
		if ( ! empty( $value ) ) {
			$previewImage = "<img src='" . esc_attr( $value ) . "'/>";
		}
		echo "<div class='thumbnail tf-image-preview'>" . $previewImage . "</div>";

		printf("<input class=\"regular-text\" name=\"%s\" placeholder=\"%s\" id=\"%s\" type=\"text\" value=\"%s\" /> &nbsp; <button class='button-secondary upload tf-upload-image'>%s</button>",
			$this->getID(),
			$this->settings['placeholder'],
			$this->getID(),
			esc_attr( $this->getValue() ),
			__( "Upload", TF_I18NDOMAIN )
		);
		$this->echoOptionFooter();
	}

	/*
	 * Display for theme customizer
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

			// open the upload media when the image is clicked
			$('body').on('click', '.tf-theme-customizer.thumbnail img, .tf-image-preview.thumbnail img', function(event) {
				event.preventDefault();
				$(this).parent().siblings('p').find('button.tf-upload-image, button.tf-upload-image').trigger('click');
				$(this).parent().siblings('button.tf-upload-image').trigger('click');
			});

			// remove the image when the remove link is clicked
			$('.tf-upload-image-remove').click(function(event) {
				event.preventDefault();
				var _input = $(this).siblings('input[type="text"]');
				if ( _input.length == 0 ) {
					// be careful with this since different placements of the input may render this invalid
					_input = $(this).parent().siblings('input[type="text"]');
				}
				var _preview = $(this).siblings('div.thumbnail');
				if ( _preview.length == 0 ) {
					_preview = $(this).parent().siblings('div.thumbnail');
				}

				_input.val('');
				_preview.html('');
				// we need to trigger a change so that WP would detect that we changed the value
				// or else the save button won't be enabled
				_input.trigger('change');
				$(this).hide();

				return false;
			});

			// open the upload media lightbox when the upload button is clicked
			$('button.tf-upload-image').click(function(event) {
				event.preventDefault();
				var _input = $(this).siblings('input[type="text"]');
				if ( _input.length == 0 ) {
					// be careful with this since different placements of the input may render this invalid
					_input = $(this).parent().siblings('input[type="text"]');
				}
				var _preview = $(this).siblings('div.thumbnail');
				if ( _preview.length == 0 ) {
					_preview = $(this).parent().siblings('div.thumbnail');
				}
				var _remove = $(this).siblings('.tf-upload-image-remove');

				// uploader frame properties
				var frame = wp.media({
					title: "Select Image",
					multiple: false,
					library: { type: 'image' },
					button : { text : 'Use image' }
				});

				// get the url when done
				frame.on('select', function() {
					var selection = frame.state().get('selection');
					selection.each(function(attachment) {
						if ( _input.length > 0 ) {
							_input.val(attachment.attributes.url);
						}
						if ( _preview.length > 0 ) {
							_preview.html("<img src='" + attachment.attributes.url + "'/>")
							// _preview.html("<img src='" + attachment.attributes.sizes.thumbnail.url + "'/>")
						}
						// we need to trigger a change so that WP would detect that we changed the value
						// or else the save button won't be enabled
						_input.trigger('change');

						_remove.show();
					});
					frame.off('select');
				});

				// open the uploader
				frame.open();

				return false;
			});
		});
		</script>
		<?php
	}
}

/*
 * We create a new control for the theme customizer
 */
add_action( 'customize_register', 'registerTitanFrameworkOptionUploadControl', 1 );
function registerTitanFrameworkOptionUploadControl() {
	class TitanFrameworkOptionUploadControl extends WP_Customize_Control {
		public $description;

		public function render_content() {
			TitanFrameworkOptionUpload::createUploaderScript();

			$previewImage = '';
			$value = $this->value();
			if ( ! empty( $value ) ) {
				$previewImage = "<img src='" . esc_attr( $this->value() ) . "'/>";
			}

			?>
			<label>
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
				<div class='thumbnail tf-theme-customizer'><?php echo $previewImage ?></div>
				<input class='tf-theme-customizer-input' type='text' value="<?php echo esc_attr( $this->value() ); ?>" <?php $this->link(); ?>/>
				<p class='description'><button class='button-secondary upload tf-upload-image'>Upload</button> &nbsp; <a href="#" class="remove tf-upload-image-remove" style='<?php echo empty( $value ) ? "display: none" : '' ?>'>Remove</a></p>
			</label>
			<?php
			echo "<p class='description'>{$this->description}</p>";
		}
	}
}