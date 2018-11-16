<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TitanFrameworkOptionSelectUsers extends TitanFrameworkOptionSelect {

	/**
	 * Creates the options for the select input. Puts the options in $this->settings['options']
	 *
	 * @since 1.11
	 *
	 * @return void
	 */
	public function create_select_options() {


		$users = get_users();

		$this->settings['options'] = array(
			'' => '— ' . __( 'Select', TF_I18NDOMAIN ) . ' —'
		);

		/** @var  $user WP_User */
		foreach ( $users as $user ) {
			$title                                  = esc_html( $user->display_name . ' (' . $user->user_nicename . ')' );
			$this->settings['options'][ $user->ID ] = $title;
		}

	}


	/*
	 * Display for options and meta
	 */
	public function display() {
		$this->create_select_options();
		parent::display();
	}


	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$this->create_select_options();
		parent::registerCustomizerControl( $wp_customize, $section, $priority );
	}

}
