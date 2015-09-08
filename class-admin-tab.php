<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}
class TitanFrameworkAdminTab {

	private $defaultSettings = array(
		'name' => '', // Name of the tab
		'id' => '', // Unique ID of the tab
		'title' => '', // Title to display in the admin panel when tab is active
		'desc' => '', // Description shown just below the tab when open
	);

	public $options = array();
	public $settings;
	public $owner;

	function __construct( $settings, $owner ) {
		$this->owner = $owner;
		$this->settings = array_merge( $this->defaultSettings, $settings );

		if ( empty( $this->settings['title'] ) && ! empty( $this->settings['name'] ) ) {
			$this->settings['title'] = $this->settings['name'];
		}
		if ( ! empty( $this->settings['title'] ) && empty( $this->settings['name'] ) ) {
			$this->settings['name'] = $this->settings['title'];
		}

		if ( empty( $this->settings['id'] ) ) {
			$this->settings['id'] = str_replace( ' ', '-', trim( strtolower( $this->settings['name'] ) ) );
		}
	}

	public function isActiveTab() {
		return $this->settings['id'] == $this->owner->getActiveTab()->settings['id'];
	}

	public function createOption( $settings ) {
		if ( ! apply_filters( 'tf_create_option_continue_' . $this->owner->owner->optionNamespace, true, $settings ) ) {
			return null;
		}

		$obj = TitanFrameworkOption::factory( $settings, $this );
		$this->options[] = $obj;

		do_action( 'tf_create_option_' . $this->owner->owner->optionNamespace, $obj );

		return $obj;
	}

	public function displayTab() {
		$url = add_query_arg(
			array(
				'page' => $this->owner->settings['id'],
				'tab' => $this->settings['id'],
			),
			remove_query_arg( 'message' )
		);
		?>
		<a href="<?php echo esc_url( $url ) ?>" class="nav-tab <?php echo $this->isActiveTab() ? 'nav-tab-active' : '' ?>"><?php echo $this->settings['name'] ?></a>
		<?php
	}

	public function displayOptions() {
		foreach ( $this->options as $option ) {
			$option->display();
		}
	}
}
