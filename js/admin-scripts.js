	// Provides method to confirm actions during saving and resetting to defaults, especially in admin sections.
	function confirm_action (btype, string, onyes, onno) {
		btype = ( typeof btype !== 'undefined' ) ? btype : 'submit';
		onyes = ( typeof onyes !== 'undefined' ) ? onyes : true;
		onno = ( typeof onno !== 'undefined' ) ? onno : false;
		var reply = confirm( string );
		if ( reply == true ) {
			if ( btype == 'reset') {
				jQuery('#tf-reset-form').submit();
			}
			return onyes;
		}
		else if ( reply == false ) {
			return onno;
		}
	}

jQuery(document).ready(function($) {
	"use strict";
});