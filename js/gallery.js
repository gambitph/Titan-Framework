(function ($) {
    //var file_frame;

    $(document).ready(function () {
        var query = wp.media.query();

        query.filterWithIds = function (ids) {
            return _(this.models.filter(function (c) {
                return _.contains(ids, c.id);
            }));
        };

        $(".galgalremove").each(function(){
            $(this).on("click",function(){
                var container = $(this).siblings("ul");
                var valcontainer = $(this).siblings(".galleryinfo");
                container.html("");
                valcontainer.val("");
            });
        });

        $(".galgal").each(function () {
            var container = $(this).siblings("ul");
            var selected_ids = $(this).prev("input").val();
            if (selected_ids && selected_ids.length > 0) {
                if($(this).data("multiple"))
                    $(this).val("Customize This Gallery");
                else
                    $(this).val("Change Image");


                $(this).css("marginTop", "10px");
                $(this).next().css("marginTop", "10px");

            }else{
                $(this).next().hide();
            }
            container.html("");
            selected_ids = selected_ids.split(",");
            for (i = 0; i < selected_ids.length; i++) {
                if (selected_ids[i] > 0) {
                    var attachment = new wp.media.model.Attachment.get(selected_ids[i]);
                    attachment.fetch({success: function (att) {
                        container.append("<li><img src='" + att.attributes.sizes.thumbnail.url + "'/></li>");
                    }});
                }
            }

        });
    })

    $(".galgal").each(function () {
        $(this).on("click", function () {

            var that = this;

            var multiple = $(this).data("multiple");
            if(multiple == undefined) multiple = true;


            if (file_frame) {
                file_frame.open();
                return;
            }

            var file_frame = wp.media.frames.file_frame = wp.media({
                frame: 'post',
                state: 'insert',
                multiple: multiple
            });

            file_frame.on('insert', function () {

                var data = file_frame.state().get('selection');
                var jdata = data.toJSON();
                var selected_ids = _.pluck(jdata, "id");
                var container = $(that).siblings("ul");

                if (selected_ids.length > 0) {
                    $(that).css("marginTop", "10px");
                    $(that).next().css("marginTop", "10px");
                    $(that).next().show();
                    if(multiple)
                        $(that).val("Customize This Gallery");
                    else
                        $(that).val("Change Image");
                }
                $(that).prev('input').val(selected_ids.join(","));
                container.html("");

                data.map(function (attachment) {
                    if (_.contains(['png','jpg','gif','jpeg'],attachment.get('subtype'))) {
                        try {
                            container.append("<li><img src='" + attachment.attributes.sizes.thumbnail.url + "'/></li>");
                        } catch (e) {
                            console.log(e);
                        }
                    }
                });
            });

            file_frame.on('open', function () {
                var selection = file_frame.state().get('selection');
                var ats = $(that).prev(".galleryinfo").val().split(",");
                for (i = 0; i < ats.length; i++) {
                    if (ats[i] > 0)
                        selection.add(wp.media.attachment(ats[i]));
                }
            });

            file_frame.open();

        })
    })
})(jQuery);
