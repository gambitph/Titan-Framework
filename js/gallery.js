(function($){
    var file_frame,data,ats;
    ats = [];
    $(document).ready(function(){
        alert("hi");
    });



    $("#galgal").on("click",function(){

        if ( file_frame ) {
            file_frame.open();
            return;
        }

        file_frame = wp.media.frames.file_frame = wp.media({
            frame:    'post',
            state:    'insert',
            multiple: true
        });

        /**
         * Setup an event handler for what to do when an image has been
         * selected.
         *
         * Since we're using the 'view' state when initializing
         * the file_frame, we need to make sure that the handler is attached
         * to the insert event.
         */
        file_frame.on( 'insert', function() {
            /**
             * We'll cover this in the next version.
             */
            var data = file_frame.state().get( 'selection' );
            alert("ss");
            data.map( function( attachment ) {

                //console.log(attachment);
                ats.push(attachment.id);
                console.log(attachment.attributes);
                attachment = attachment.toJSON();

                // Do something with attachment.id and/or attachment.url here
            });
            var jdata = data.toJSON();

            console.log(jdata);

        });

        file_frame.on('open', function(){
            var selection = file_frame.state().get('selection');

            if (ats.length>0) {
                for(i in ats)
                selection.add(wp.media.attachment(ats[i]));
            }
        });

        // Now display the actual file_frame
        file_frame.open();

    })
})(jQuery);