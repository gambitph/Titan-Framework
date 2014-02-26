<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class TitanFrameworkAdminNotification {

	function __construct() {
		add_filter( 'admin_notices', array( $this, 'displayNotifications' ) );
	}

	public function createNotification( $message, $type = 'updated', $location = 'top' ) {

	}

	public function displayNotifications() {

	}

	public static function formNotification( $message, $type = 'updated', $location = 'top' ) {
		if ( 'top' != $location ) {
			$location = 'below-h2';
		}

		if ( 'saved' == $type || 'reset' == $type ) {
			$message = '<strong>' . $message . '</strong>';
			$type = 'updated';
		}

		return "<div class='$type $location'><p>{$message}</p></div>";
	}

}