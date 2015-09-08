<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}
class TitanFrameworkAdminNotification {

	function __construct() {
		add_filter( 'admin_notices', array( $this, 'displayNotifications' ) );
	}

	public function createNotification( $message, $type = 'updated', $location = 'top' ) {

	}

	public function displayNotifications() {
	}

	public static function formNotification( $message, $type = 'updated', $location = 'top' ) {
		if ( $location != 'top' ) {
			$location = 'below-h2';
		}

		if ( $type == 'saved' || $type == 'reset' ) {
			$message = '<strong>' . $message . '</strong>';
			$type = 'updated';
		}

		return "<div class='$type $location'><p>{$message}</p></div>";
	}
}
