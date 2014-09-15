;
(function ($) {
    $(document).ready(function () {
        $(".tf-text input, .tf-select select, .tf-color input.tf-colorpicker, .tf-checkbox input, .tf-radio fieldset").each(function () {
            var that = this;
            var did = ($(this).data("did")); //dependency id
            var dv = ($(this).data("dvalue")); //dependency value

            if (did != "") {

                var id = "#" + fdata.namespace + "_" + did;
//                alert(id + "\n"+$(id).prop("tagName"));

                if ($(id).prop("tagName") == "INPUT" && $(id).attr("type") == 'text') {
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
                } else if ($(id).prop("tagName") == "INPUT" && $(id).attr("type") == 'checkbox') {
                    $(id).on("change", function () {
                        if (($(this).is(":checked") && dv == "checked") || (!$(this).is(":checked") && dv != "checked")) {
                            $(that).parents(".odd, .even").show();
                        } else if (($(this).is(":checked") && dv == "unchecked") || (!$(this).is(":checked") && dv == "checked")) {
                            $(that).parents(".odd, .even").hide();
                        }
                    });
                } else if($(id+"1").prop("tagName") == "INPUT" && $(id+"1").attr("type") == 'radio'){
                    $(id+"1").parents("fieldset").find("input").on("change",function(){
                        if($(this).val()!=dv){
                            $(that).parents(".odd, .even").hide();
                        }else{
                            $(that).parents(".odd, .even").show();
                        }
                    });
                }


                if (($(id).attr("type") != 'checkbox' && $(id+"1").attr("type") != 'radio') && $(id).val() != dv) {
                    $(that).parents(".odd, .even").hide();
                } else if ($(id).attr("type") == 'checkbox') {
                    if (($(this).is(":checked") && dv == "checked") || (!$(this).is(":checked") && dv != "checked")) {
                        $(that).parents(".odd, .even").show();
                    } else if (($(this).is(":checked") && dv == "unchecked") || (!$(this).is(":checked") && dv == "checked")) {
                        $(that).parents(".odd, .even").hide();
                    }
                } else if($(id+"1").attr("type") == 'radio'){
                    if($(id+"1").parents("fieldset").find("input:checked").val()!=dv){
                        $(that).parents(".odd, .even").hide();
                    }else{
                        $(that).parents(".odd, .even").show();
                    }
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