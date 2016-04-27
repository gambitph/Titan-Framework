jQuery(document).ready(function($) {
	"use strict";

	var cache = {};
	$( ".autocomplete" ).autocomplete({
		minLength: 2,
		source: function( request, response ) {
			var term = request.term;
			if ( term in cache ) {
				response( cache[ term ] );
				return;
			}

			$.getJSON( ajaxurl,
				$.extend(request, autocomplete_params, {
					post_type: $(this.element.context).attr('data-post-type'),
				}),
				function( data, status, xhr ) {
					console.log(data);
					data = data.map(function(post, i) {
						return {
							value: post.ID,
							label: post.post_title
						};
					});
					cache[ term ] = data;
					response( data );
				}
			);
		},
		focus: function( event, ui ) {
			$(this).val(ui.item.label);
			return false;
		},
		select: function( event, ui ) {
			$(this).val(ui.item.label);
			return false;
		},
		change: function( event, ui ) {
			const $target = $('#' + $(this).attr('data-target-id'))
			if (ui.item === null) {
				$target.val('');
			} else {
				$target.val(ui.item.value);
			}
		},
	});

});