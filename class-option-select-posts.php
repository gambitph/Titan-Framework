<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class TitanFrameworkOptionSelectPosts extends TitanFrameworkOption {

	public $defaultSecondarySettings = array(
		'default' => '0', // show this when blank
		'post_type' => 'post',
		'num' => -1,
		'post_status' => 'any',
		'orderby' => 'post_date',
		'order' => 'DESC',
	);

	/*
	 * Display for options and meta
	 */
	public function display() {
		$this->echoOptionHeader();

		$args = array(
			'post_type' => $this->settings['post_type'],
			'posts_per_page' => $this->settings['num'],
			'post_status' => $this->settings['post_status'],
			'orderby' => $this->settings['orderby'],
			'order' => $this->settings['order'],
		);

		$posts = get_posts( $args );

		echo "<select name='" . esc_attr( $this->getID() ) . "'>";

		// The default value (nothing is selected)
		printf( "<option value='%s' %s>%s</option>",
			'0',
			selected( $this->getValue(), '0', false ),
			"— " . __( "Select", TF_I18NDOMAIN ) . " —"
		);

		// Print all the other pages
		foreach ( $posts as $post ) {
			printf( "<option value='%s' %s>%s</option>",
				esc_attr( $post->ID ),
				selected( $this->getValue(), $post->ID, false ),
				$post->post_title
			);
		}
		echo "</select>";

		$this->echoOptionFooter();
	}

	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkOptionSelectPostsControl( $wp_customize, $this->getID(), array(
			'label' => $this->settings['name'],
			'section' => $section->settings['id'],
			'settings' => $this->getID(),
			'description' => $this->settings['desc'],
			'post_type' => $this->settings['post_type'],
			'posts_per_page' => $this->settings['num'],
			'post_status' => $this->settings['post_status'],
			'orderby' => $this->settings['orderby'],
			'order' => $this->settings['order'],
			'priority' => $priority,
		) ) );
	}
}

/*
 * WP_Customize_Control with description
 */
add_action( 'customize_register', 'registerTitanFrameworkOptionSelectPostsControl', 1 );
function registerTitanFrameworkOptionSelectPostsControl() {
	class TitanFrameworkOptionSelectPostsControl extends WP_Customize_Control {
		public $description;
		public $post_type;
		public $num;
		public $post_status;
		public $orderby;
		public $order;

		public function render_content() {
			$args = array(
				'post_type' => $this->post_type,
				'posts_per_page' => $this->num,
				'post_status' => $this->post_status,
				'orderby' => $this->orderby,
				'order' => $this->order,
			);

			$posts = get_posts( $args );

			?>
			<label>
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
				<select <?php $this->link(); ?>>
					<?php
					// The default value (nothing is selected)
					printf( "<option value='%s' %s>%s</option>",
						'0',
						selected( $this->value(), '0', false ),
						"— " . __( "Select", TF_I18NDOMAIN ) . " —"
					);

					// Print all the other pages
					foreach ( $posts as $post ) {
						printf( "<option value='%s' %s>%s</option>",
							esc_attr( $post->ID ),
							selected( $this->value(), $post->ID, false ),
							$post->post_title
						);
					}
					?>
				</select>
			</label>
			<?php
			echo "<p class='description'>{$this->description}</p>";
		}
	}
}