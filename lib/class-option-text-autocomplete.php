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

	public $defaultSecondarySettings = array(
		'post_type' => 'post',
		'target_id' => '',

		'placeholder' => '',
		'size' => 'regular',
		'sanitize_callbacks' => array(),
		'maxlength' => '',
		'unit' => '',
	);

	function __construct( $settings, $owner ) {
		parent::__construct( $settings, $owner );
		add_action('wp_ajax_option-text-autocomplete', array( $this, 'autocomplete_ajax_callback' ));
	}

	public function display() {

		$this->register_autocomplete_script();
		$this->echoOptionHeader();

		$val = $this->getValue();
		$title = '';
		$isTargetItself = empty($this->settings['target_id']);

		if ($isTargetItself) {
			if (!empty($val)) {
				$posts = get_posts(array(
					'p' => $val,
					'post_type' => $this->settings['post_type'])
				);
				if (!empty($posts)) {
					$title = $posts[0]->post_title;
				}
			}

			printf('<input name="%s" id="%s" type="hidden" value="%s" \>',
				$this->getID(),
				$this->getID(),
				esc_attr( $val )
			);
		}

		printf('<input class="%s-text autocomplete" name="%s" placeholder="%s" maxlength="%s" id="%s" type="text" value="%s" data-post-type="%s" data-target-id="%s" \>%s',
			empty($this->settings['size']) ? 'regular' : $this->settings['size'],
			$isTargetItself ? $this->getID().'-post_title' : $this->getID(),
			$this->settings['placeholder'],
			$this->settings['maxlength'],
			$isTargetItself ? $this->getID().'-post_title' : $this->getID(),
			esc_attr( $isTargetItself ? $title : $val ),
			$this->settings['post_type'],
			$this->settings['target_id'] ? $this->getOptionNamespace() . '_' . $this->settings['target_id'] : $this->getID(),
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

		$namespace = $this->getOptionNamespace();
		$query = array(
			's' => $_GET['term'],
			'post_type' => $_GET['post_type']
		);

		$posts = get_posts($query);

		if (empty($posts)) {
			$posts = apply_filters('tf_autocomplete_empty_result', $this, $query, $this->options );
		}

		wp_send_json($posts);
	}
}