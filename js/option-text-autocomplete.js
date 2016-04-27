console.log('option-text-autocomplete');

jQuery(function() {
    var cache = {};
    jQuery( ".autocomplete" ).autocomplete({
      minLength: 2,
      source: function( request, response ) {
        var term = request.term;
        if ( term in cache ) {
          response( cache[ term ] );
          return;
        }

        var pp = jQuery.extend(request, autocomplete_params, {
        	post_type: jQuery(this.element.context).attr('data-post-type'),
        });

        jQuery.getJSON( ajaxurl, pp, function( data, status, xhr ) {
        	data = data.map(function(post, i) {
        		return {
        			value: post.ID,
        			label: post.post_title
        		};
        	});
          cache[ term ] = data;
          response( data );
        });
      },
      focus: function( event, ui ) {
        jQuery(this).val(ui.item.label);
        return false;
      },
      select: function( event, ui ) {
      	jQuery(this).val(ui.item.label);
      	jQuery('#' + jQuery(this).attr('data-target-id')).val(ui.item.value);

      	return false;
      }
    });
  });