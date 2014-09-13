(function($){
    $(document).ready(function(){
        alert("hi");
    });

    $("#galgal").on("click",function(){
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
            var data = file_frame.state().get( 'selection' ).toJSON();
            console.log(data);

        });

        // Now display the actual file_frame
        file_frame.open();

    })
})(jQuery);