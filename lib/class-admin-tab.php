<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}
class TitanFrameworkAdminTab {

	/**
	 * Default settings specific for this container
	 * @var array
	 */
	private $defaultSettings = array(

		/**
		 * The name of the tab, this is used as the label for the tab.
		 * @since 1.0
		 * @var string
		 */
		'name' => '',

		/**
		 * (Optional) A unique slug for this admin tab. Defaults to a generated slug based from the name parameter. This is appended to the url such as <code>admin.php?page=current_page&tab=id</code>
		 * @since 1.0
		 * @var string
		 */
		'id' => '',

		/**
		 * (Optional) A description displayed just below the tab when it’s active. You can use HTML tags here.
		 * @since 1.0
		 * @var string
		 */
		'desc' => '',
	);

	public $options = array();
	public $settings;
	public $owner;

	function __construct( $settings, $owner ) {
		$this->owner = $owner;
		$this->settings = array_merge( $this->defaultSettings, $settings );

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
