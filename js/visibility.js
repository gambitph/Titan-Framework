;
(function ($) {
    $(document).ready(function () {
        $(".tf-text input, .tf-select select").each(function () {
            var that = this;
            var did = ($(this).data("did")); //dependency id
            var dv = ($(this).data("dvalue")); //dependency value

            if (did != "") {

                var id = "#" + fdata.namespace + "_" + did;
//                alert(id + "\n"+$(id).prop("tagName"));

                if ($(id).prop("tagName") == "INPUT") {
                    $(id).on("blur", function () {
                        if ($(this).val() != dv) {
                            $(that).parent().parent().hide();
                        } else {
                            $(that).parent().parent().show();
                        }
                    });
                    $(id).on("keyup", function () {
                        if ($(this).val() != dv) {
                            $(that).parent().parent().hide();
                        } else {
                            $(that).parent().parent().show();
                        }
                    });
                } else if ($(id).prop("tagName") == "SELECT") {
                    $(id).on("change", function () {
//                        console.log($(this).val());
                        if ($(this).val() != dv) {
                            $(that).parent().parent().hide();
                        } else {
                            $(that).parent().parent().show();
                        }
                    });
                }

                if ($(id).val() != dv) {
                    $(that).parent().parent().hide();
                }
            }
        });
    });
})(jQuery);