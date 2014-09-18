;
(function ($) {
"use strict"
    $(document).ready(function () {
        $(".repeaterjson").each(function () {
            var container = $(this).next("div.repeaterplaceholder");
            var val = $(this).val();

            if (val) {
                var parts = val.split("|||");
            }else{
                parts = [""];
            }
            var i=0;
            for (i in parts) {
                $("<input>").attr("type", "text").addClass("repeatable").val(parts[i]).appendTo($(container));
                $("<input>").attr("type", "button").addClass("button removert").val("Remove").appendTo($(container));
                $(container).append("<br/>");
            }

            $(this).parent().on("blur",".repeatable",function(){
                var _parent = $(this).parent();
                serializert(_parent);
            });

            $(this).parent().on("click",".removert",function(){
                //serialize
                var _parent = $(this).parent();
                if($(this).parent().find(".repeatable").length>1){
                    $(this).prev().remove();
                    $(this).next().remove();
                    $(this).remove();
                }else{
                    $(this).prev().val("");
                }
                serializert(_parent);
            });


        });

        $(".repeater").each(function () {
            var that = this;
            var container = $(that).prev();
            $(this).on('click','.repeaterbtn',function(){
                $("<input>").attr("type", "text").addClass("repeatable").appendTo(container).focus();
                $("<input>").attr("type", "button").addClass("button removert").val("Remove").appendTo(container);
                $("<br/>").appendTo(container);

            });
        });

        function serializert(container){
            //alert($(container).html());
            var values = [];
            $(container).find("input[type='text']").each(function () {
                //alert($(this).val());
                values.push($(this).val());
            });
            $(container).prev(".repeaterjson").val(values.join("|||"));
        }
    });
})(jQuery);