jQuery(document).ready(function($) {
	"use strict";

	// Removes the last blank table in admin pages. This is because a blank table is left if the last
	// option is a save option
	$('.titan-framework-panel-wrap table.form-table').filter(function() {
		return $(this).find('tbody tr').length === 0;
	}).remove();

	// Find all toggle buttons and trigger and event on click.
	// This will toggle all fields within the same section.
	$('.titan-framework-panel-wrap table.form-table .toggle-button.open').live('click', function() {
		$(this).closest('table').find('tr').not('.tf-heading').fadeOut();
		$(this).removeClass('open').addClass('closed').text('+');
		return false;
	});

	$('.titan-framework-panel-wrap table.form-table .toggle-button.closed').live('click', function() {
		$(this).closest('table').find('tr').not('.tf-heading').fadeIn();
		$(this).removeClass('closed').addClass('open').text('-');
		return false;
	});
});