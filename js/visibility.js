;
(function ($) {
    $(document).ready(function () {
        $(".tf-text input, .tf-select select, .tf-color input.tf-colorpicker, .tf-checkbox input, .tf-radio fieldset, .tf-image .galleryinfo, .tf-radio-palette .dep").each(function () {
            var that = this;
            var did = ($(this).data("did")); //dependency id
            var dv = ($(this).data("dvalue")); //dependency value

            if (did != "") {

                var id = "#" + fdata.namespace + "_" + did;
                var deps = $(id).data("dependents");
                if(deps!="" && deps!=undefined){
                    deps = deps.split(".");
                }else{
                    deps = [];
                }
                deps.push($(this).attr("id"));
                $(id).data("dependents",deps.join("."));

                if ($(id).prop("tagName") == "INPUT" && $(id).attr("type") == 'text') {
                    $(id).on("blur", function () {
                        if ($(this).val() != dv) {
                            $(that).parents(".odd, .even").hide();
                            hideDependents($(id));
                        } else {
                            $(that).parents(".odd, .even").show();
                            showDependents($(id));
                        }
                    });
                    $(id).on("keyup", function () {
                        if ($(this).val() != dv) {
                            $(that).parents(".odd, .even").hide();
                            hideDependents($(id));
                        } else {
                            $(that).parents(".odd, .even").show();
                            showDependents($(id));
                        }
                    });
                } else if ($(id).prop("tagName") == "SELECT") {
                    $(id).on("change", function () {
//                        console.log($(this).val());
                        if ($(this).val() != dv) {
                            $(that).parents(".odd, .even").hide();
                            hideDependents($(id));
                        } else {
                            $(that).parents(".odd, .even").show();
                            showDependents($(id));
                        }
                    });
                } else if ($(id).prop("tagName") == "INPUT" && $(id).attr("type") == 'checkbox') {
                    $(id).on("change", function () {
                        if (($(this).is(":checked") && dv == "checked") || (!$(this).is(":checked") && dv != "checked")) {
                            $(that).parents(".odd, .even").show();
                            showDependents($(id));
                        } else if (($(this).is(":checked") && dv == "unchecked") || (!$(this).is(":checked") && dv == "checked")) {
                            $(that).parents(".odd, .even").hide();
                            hideDependents($(id));
                        }
                    });
                } else if($(id+"1").prop("tagName") == "INPUT" && $(id+"1").attr("type") == 'radio'){
                    $(id+"1").parents("fieldset").find("input").on("change",function(){
                        if($(this).val()!=dv){
                            $(that).parents(".odd, .even").hide();
                            hideDependents($(id));
                        }else{
                            $(that).parents(".odd, .even").show();
                            showDependents($(id));
                        }
                    });
                }

                if(!$(id).is(":visible")){
                    $(that).parents(".odd, .even").hide();
                };


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

        function hideDependents(obj){
            $(".tf-text input, .tf-select select, .tf-color input.tf-colorpicker, .tf-checkbox input, .tf-radio fieldset, .tf-image .galleryinfo").each(function () {
                var did = ($(this).data("did")); //dependency id
                if (did != "") {
                    var id = "#" + fdata.namespace + "_" + did;
                    if(!$(id).is(":visible")){
                        $(this).parents(".odd, .even").hide();
                    }
                }
            });
        }

        function showDependents(obj){
            var deps = $(obj).data("dependents");
            if(deps!="" && deps!=undefined){
                deps = deps.split(".");
                for(var i in deps){
                    var id = "#"+deps[i];
                    $(id).trigger("change");
                    $(id).trigger("keyup");
                }
            }
        }

        //metabox display depenedency on post format
        function resetmetaBox() {
            $(".postbox table.tf-form-table").each(function () {

                //post format
                var dependency_post_format = $(this).data("post-format");
                var selected_post_type = $("#post-formats-select input:checked").attr("id");
                if (selected_post_type) {
                    if (selected_post_type == "post-format-0") selected_post_type = "post-format-standard";
                    if (dependency_post_format.indexOf(selected_post_type.replace("post-format-", "")) == -1) {
                        $(this).parent().parent().hide();
                    } else {
                        $(this).parent().parent().show();
                    }
                }

                //page template
                if($("#page_template").length>0) {
                    var dependency_page_template = $(this).data("page-template");
                    var selected_page_template = $("#page_template").val();
                    if(dependency_page_template) {
                        if (dependency_page_template.indexOf(selected_page_template)==-1) {
                            $(this).parent().parent().hide();
                        } else {
                            $(this).parent().parent().show();
                        }
                    }
                }

            });
        }



        $("#post-formats-select input").on("change", resetmetaBox);
        $("#page_template").on("change", resetmetaBox);
        resetmetaBox();
    });
})(jQuery);
