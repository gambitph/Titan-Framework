<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class TitanFrameworkMetaBox {

	private $defaultSettings = array(
		'name' => '', // Name of the menu item
		// 'parent' => null, // slug of parent, if blank, then this is a top level menu
		'id' => '', // Unique ID of the menu item
		// 'capability' => 'manage_options', // User role
		// 'icon' => 'dashicons-admin-generic', // Menu icon for top level menus only
		// 'position' => 100.01 // Menu position for top level menus only
		'post_type' => 'page', // Post type, can be an array of post types
		'context' => 'normal', // normal, advanced, or side
		'hide_custom_fields' => true, // If true, the custom fields box will not be shown
        'priority' => 'high', //  high, core, default, low
		'desc' => '', // Description displayed below the title
	);

	public $settings;
	public $options = array();
	public $owner;
	public $postID; // Temporary holder for the current post ID being edited in the admin

	function __construct( $settings, $owner ) {
		$this->owner = $owner;

		if ( ! is_admin() ) {
			return;
		}

		$this->settings = array_merge( $this->defaultSettings, $settings );
		// $this->options = $options;

		if ( empty( $this->settings['name'] ) ) {
			$this->settings['name'] = __( "More Options", TF_I18NDOMAIN );
		}

		if ( empty( $this->settings['id'] ) ) {
			$this->settings['id'] = str_replace( ' ', '-', trim( strtolower( $this->settings['name'] ) ) );
		}

		add_action( 'add_meta_boxes', array( $this, 'register' ) );
		add_action( 'save_post', array( $this, 'saveOptions' ), 10, 2 );
	}

	public function register() {
		$postTypes = array();

		// accomodate multiple post types
		if ( is_array( $this->settings['post_type'] ) ) {
			$postTypes = $this->settings['post_type'];
		} else {
			$postTypes[] = $this->settings['post_type'];
		}

		foreach ( $postTypes as $postType ) {
			// Hide the custom fields
			if ( $this->settings['hide_custom_fields']) {
				remove_meta_box( 'postcustom' , $postType , 'normal' );
			}

			add_meta_box(
				$this->settings['id'],
				$this->settings['name'],
				array( $this, 'display' ),
				$postType,
				$this->settings['context'],
				$this->settings['priority']
            );
		}
	}

	public function display( $post ) {
		$this->postID = $post->ID;

		wp_nonce_field( $this->settings['id'], TF . '_' . $this->settings['id'] . '_nonce' );

		if ( ! empty( $this->settings['desc'] ) ) {
			?><p class='description'><?php echo $this->settings['desc'] ?></p><?php
		}

		?>
		<table class="form-table tf-form-table">
		<tbody>
		<?php
		foreach ( $this->options as $option ) {
			$option->display();
		}
		?>
		</tbody>
		</table>
		<?php
	}

	public function saveOptions( $postID, $post ) {
		// Verify nonces and other stuff
		if ( ! $this->verifySecurity( $postID, $post ) ) {
			return;
		}
		
		// Hook 'tf_pre_save_options_{namespace}' - action pre-saving
		do_action( 'tf_pre_save_options_' . $this->owner->optionNamespace, $this );

		// Save the options one by one
		foreach ( $this->options as $option ) {
			if ( empty( $option->settings['id'] ) ) {
				continue;
			}

			if ( ! empty( $_POST[$this->owner->optionNamespace . '_' . $option->settings['id']] ) ) {
				$value = $_POST[$this->owner->optionNamespace . '_' . $option->settings['id']];
			} else {
				$value = '';
			}

			$this->owner->setOption( $option->settings['id'], $value, $postID );
		}
	}

	private function verifySecurity( $postID, $post ) {
		// Verify edit submission
		if ( empty( $_POST ) ) {
			return false;
		}
		if ( empty( $_POST['post_type'] ) ) {
			return false;
		}

		// Don't save on revisions
		if ( wp_is_post_revision( $postID ) ) {
			return false;
		}

		// Don't save on autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		// Verify that we are editing the correct post type
		if ( is_array( $this->settings['post_type'] ) ) {
			if ( ! in_array( $_POST['post_type'], $this->settings['post_type'] ) ) {
				return false;
			}
			if ( ! in_array( $post->post_type, $this->settings['post_type'] ) ) {
				return false;
			}
		} else {
			if ( $_POST['post_type'] != $this->settings['post_type'] ) {
				return false;
			}
			if ( $post->post_type != $this->settings['post_type'] ) {
				return false;
			}
		}

		// Verify our nonce
		if ( ! check_admin_referer( $this->settings['id'], TF . '_' . $this->settings['id'] . '_nonce' ) ) {
			return false;
		}

		// Check permissions
		if ( is_array( $this->settings['post_type'] ) ) {
			if ( in_array( 'page', $this->settings['post_type'] ) ) {
				if ( ! current_user_can( 'edit_page', $postID ) ) {
					return false;
				}
			} else if ( ! current_user_can( 'edit_post', $postID ) ) {
				return false;
			}
		} else {
			if ( $this->settings['post_type'] == 'page' ) {
				if ( ! current_user_can( 'edit_page', $postID ) ) {
					return false;
				}
			} else if ( ! current_user_can( 'edit_post', $postID ) ) {
				return false;
			}
		}

		return true;
	}

	public function createOption( $settings ) {
		if ( ! apply_filters( 'tf_create_option_continue_' . $this->owner->optionNamespace, true, $settings ) ) {
			return null;
		}

		$obj = TitanFrameworkOption::factory( $settings, $this );
		$this->options[] = $obj;

		do_action( 'tf_create_option_' . $this->owner->optionNamespace, $obj );

		return $obj;
	}
}
