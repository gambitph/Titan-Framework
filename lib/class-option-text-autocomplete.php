<?php
/**
 * Text Option
 *
 * @package Titan Framework
 */
if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly.
}
/**
 * Text Option
 *
 * Creates a text option
 *
 * <strong>Creating a text option:</strong>
 * <pre>$panel->createOption( array(
 *     'name' => 'My Text Option',
 *     'id' => 'my_text_option',
 *     'type' => 'text',
 *     'desc' => 'This is our option',
 * ) );</pre>
 *
 * @since 1.0
 * @type text
 * @availability Admin Pages|Meta Boxes|Customizer
 */
class TitanFrameworkOptionTextAutocomplete extends TitanFrameworkOptionText {

	function __construct( $settings, $owner ) {
		parent::__construct( $settings, $owner );
		add_action('wp_ajax_option-text-autocomplete', array( $this, 'autocomplete_ajax_callback' ));
	}

	public function display() {


		$this->register_autocomplete_script();
		$this->echoOptionHeader();

		$val = $this->getValue();
		$title = '';

		if (!empty($val)) {
			$posts = get_posts(array(
				'p' => $val,
				'post_type' => $this->settings['post_type'])
			);
			if (!empty($posts)) {
				$title = $posts[0]->post_title;
			}
		}
		// echo '<pre>'.print_r($posts,1).'</pre>';

		printf('<input name="%s" id="%s" type="hidden" value="%s" \>',
			$this->getID(),
			$this->getID(),
			esc_attr( $val )
		);

		printf('<input class="%s-text autocomplete" name="%s-post_title" placeholder="%s" maxlength="%s" id="%s-post_title" type="text" value="%s" data-post-type="%s" data-target-id="%s" \>%s',
			empty($this->settings['size']) ? 'regular' : $this->settings['size'],
			$this->getID(),
			$this->settings['placeholder'],
			$this->settings['maxlength'],
			$this->getID(),
			esc_attr( $title ),
			$this->settings['post_type'],
			$this->getID(),
			$this->settings['hidden'] ? '' : ' ' . $this->settings['unit']
		);
		$this->echoOptionFooter();
	}

	public function register_autocomplete_script() {
		wp_enqueue_script('option-text-autocomplete',  TitanFramework::getURL( '../js/option-text-autocomplete.js', __FILE__ ), array('jquery', 'jquery-ui-autocomplete'));

		wp_localize_script( 'option-text-autocomplete', 'autocomplete_params',
			array(
			   'action' => 'option-text-autocomplete',
			   '_ajax_nonce' => wp_create_nonce('autocomplete_params-nonce'),
			));
	}

	public function autocomplete_ajax_callback() {
		// echo "string";
		check_ajax_referer( 'autocomplete_params-nonce');

		$query = new WP_Query( array(
			's' => $_GET['term'],
			'post_type' => $_GET['post_type']
		));
		wp_send_json($query->posts);
	}
}