<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}
class TitanFrameworkOptionSelectPostTypes extends TitanFrameworkOption {

	public $defaultSecondarySettings = array(
		'default' => '0', // show this when blank
		'public' => true,
		'value' => 'all',
		'slug' => true,
	);

	/*
	 * Display for options and meta
	 */
	public function display() {
		$this->echoOptionHeader();

		// Fetch post types.
		$post_types = tf_get_post_types( $this->settings['public'], $this->settings['value'] );

		echo "<select name='" . esc_attr( $this->getID() ) . "'>";

		// The default value (nothing is selected)
		printf( "<option value='%s' %s>%s</option>",
			'0',
			selected( $this->getValue(), '0', false ),
			'— ' . __( 'Select', TF_I18NDOMAIN ) . ' —'
		);

		// Print all the other pages
		foreach ( $post_types as $post_type ) {

			$slug = $post_type->name;

			$slugname = true == $this->settings['slug'] ? ' (' . $slug . ')' : '';

			$name = $post_type->name;
			if ( ! empty( $post_type->labels->singular_name ) ) {
				$name = $post_type->labels->singular_name . $slugname;
			}

			printf( "<option value='%s' %s>%s</option>",
				$slug,
				selected( $this->getValue(), $slug, false ),
				$name
			);

		}
		echo '</select>';

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
			'value' => $this->settings['value'],
			'public' => $this->settings['public'],
			'slug' => $this->settings['slug'],
			'priority' => $priority,
		) ) );
	}
}

/*
 * WP_Customize_Control with description
 */
add_action( 'customize_register', 'registerTitanFrameworkOptionSelectPosttypeControl', 1 );
function registerTitanFrameworkOptionSelectPosttypeControl() {
	class TitanFrameworkOptionSelectPosttypeControl extends WP_Customize_Control {
		public $description;
		public $value;
		public $public;
		public $slug;

		public function render_content() {

			// Fetch post types.
			$post_types = tf_get_post_types( $this->public, $this->value );

			?>
			<label>
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
				<select <?php $this->link(); ?>>
					<?php
					// The default value (nothing is selected)
					printf( "<option value='%s' %s>%s</option>",
						'0',
						selected( $this->value(), '0', false ),
						'— ' . __( 'Select', TF_I18NDOMAIN ) . ' —'
					);

					// Print all the other pages
					foreach ( $post_types as $post_type ) {

						$slug = $post_type->name;

						$slugname = true == $this->slug ? ' (' . $slug . ')' : '';

						$name = $post_type->name;
						if ( ! empty( $post_type->labels->singular_name ) ) {
							$name = $post_type->labels->singular_name . $slugname;
						}

						printf( "<option value='%s' %s>%s</option>",
							$slug,
							selected( $this->getValue(), $slug, false ),
							$name
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
