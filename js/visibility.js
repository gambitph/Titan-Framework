;
(function ($) {
    $(document).ready(function () {
        $(".tf-text input, .tf-select select, .tf-color input.tf-colorpicker").each(function () {
            var that = this;
            var did = ($(this).data("did")); //dependency id
            var dv = ($(this).data("dvalue")); //dependency value

            if (did != "") {

                var id = "#" + fdata.namespace + "_" + did;
//                alert(id + "\n"+$(id).prop("tagName"));

                if ($(id).prop("tagName") == "INPUT") {
                    $(id).on("blur", function () {
                        if ($(this).val() != dv) {
                            $(that).parents(".odd, .even").hide();
                        } else {
                            $(that).parents(".odd, .even").show();
                        }
                    });
                    $(id).on("keyup", function () {
                        if ($(this).val() != dv) {
                            $(that).parents(".odd, .even").hide();
                        } else {
                            $(that).parents(".odd, .even").show();
                        }
                    });
                } else if ($(id).prop("tagName") == "SELECT") {
                    $(id).on("change", function () {
//                        console.log($(this).val());
                        if ($(this).val() != dv) {
                            $(that).parents(".odd, .even").hide();
                        } else {
                            $(that).parents(".odd, .even").show();
                        }
                    });
                }

                if ($(id).val() != dv) {
                    $(that).parents(".odd, .even").hide();
                }
            }
        });

        //metabox display depenedency on post format
        function resetmetaBox() {
            $(".postbox table.tf-form-table").each(function () {
                var dependency = $(this).data("post-format");
                var selected_post_type = $("#post-formats-select input:checked").attr("id");
                if (selected_post_type) {
                    if (selected_post_type == "post-format-0") selected_post_type = "post-format-standard";
                    if (dependency.indexOf(selected_post_type.replace("post-format-", "")) == -1) {
                        $(this).parent().parent().hide();
                    } else {
                        $(this).parent().parent().show();
                    }
                }

            });
        }

        $("#post-formats-select input").on("change", resetmetaBox);
        resetmetaBox();
    });
})(jQuery);