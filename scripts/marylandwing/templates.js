window.loaded.push(function() {
    addFunction(function() { // Paginator functions
        $(".pagenav a").click(function() {
            if ("." + $(".active").attr("class") !== $(this).attr("href")) {
                $(".active").addClass("inactive");
                $(".active").removeClass("active");

                var page = $($(this).attr("href"));
                page.addClass("active");
                page.removeClass("inactive");
                
                var height = $(".active").height();
                var time = 250;
                console.log(height);
                if (height > $(".active").height()) {
                    time = 100;
                }
                $(".pages").animate({
                    "padding-bottom": height + "px"
                });

            }
            return false;
        });
        $(".pages").css({ "padding-bottom": $(".active").height() + "px" });
    });

    addFunction(function() { // Detailed list functions
        $(".detailedlistplusname, .detailedlistplusarrow").on("click touch", function() {
            console.log("Touch");
            var arrow = $(this).parent().find("> .detailedlistplusarrow");
            if ($(arrow).hasClass('down')) {
                // $(this).parent().parent().animate({ "padding-bottom": "5px" }, 400);
                arrow.removeClass("down");
                $(this).parent().parent().find("> .detailedlistplusdesc").slideUp(400, function() {
                    var id = mobile ? "#topbar" : "#navbar";
                    window.offset = $(id).offset().top;
                });
            } else {
                // $(this).parent().parent().animate({ "padding-bottom": $(this).parent().parent().find("detailedlistplusdesc").height() }, 400);
                arrow.addClass("down");
                $(this).parent().parent().find("> .detailedlistplusdesc").slideDown(400, function() {
                    var id = mobile ? "#topbar" : "#navbar";
                    window.offset = $(id).offset().top;
                });
            }
        });
        $(".detailedlistplus").each(function() {
            var json = {};
            var ops = $(this).attr("data-options").split(";");
            for (var op in ops) {
                json[ops[op].split(":")[0]] = ops[op].split(":")[1];
            }
            if (json.defaultopen == "true") {
                $(this).children().each(function() {
                    $(this).find(".detailedlistplusdesc").slideToggle(1, function() {
                        var id = mobile ? "#topbar" : "#navbar";
                        window.offset = $(id).offset().top;
                    });
                    $(this).find(".detailedlistplusarrow").addClass("down");
                    // $(this).css({ "padding-bottom": $(this).find(".detailedlistplusdesc").height() }, 400);
                });
            }
        });
        $(".detailedlistplusrow.open").each(function() {
            $(this).next().slideDown(1, function() {
                var id = mobile ? "#topbar" : "#navbar";
                window.offset = $(id).offset().top;
            });
            $(this).find(".detailedlistplusarrow").addClass("down");
        });
        $(".detailedlistplusrow.closed").each(function() {
            $(this).next().slideUp(1, function() {
                var id = mobile ? "#topbar" : "#navbar";
                window.offset = $(id).offset().top;
            });
            $(this).find(".detailedlistplusarrow").removeClass("down");
        });

    });

    addFunction(function() { // Form functions
        $("input[type=\"range\"]").off();
        $("input[type=\"range\"]").on('input', function() {
            $(this).next('output').html(this.value);
        });
        $("input[type=\"range\"]").each(function() {
            $(this).next('output').html(this.value);
        });

        $("input.otherRadioInput").on('input', function() {
            $(this).parent().parent().find('input[type="radio"]').attr('value', $(this).val());
        });

        $("input").on("input", function() {
            $form = $(this).parent().parent().parent();
            if ($form.attr("id")) {

            }
        });

        $("form").on("submit", function() {
            if ($(this).attr("id")) {
                localStorage.removeItem("form." + $(this).attr("id"));
            }
        });

        $("div.autocomplete").each(function() {
            $el = $(this).find("input");
            data = $(this).find(".data").children();
            adata = [];
            for (var i = 0; i < data.length; i++) {
                adata.push($(data[i]).text());
            }
            $el.autocomplete({
                lookup: adata,
                zIndex: 5
            });
        });

        $("input[type=datetime-local]").each(function() {
            var mint = 5 * 60 * 1000;
            // if (!$(this).val()) {
            //     var t = new Date();
            //     var timestamp = t.getTime();
            //     t = new Date(Math.round(timestamp / mint) * mint);
            //     $(this).val(t.getFullYear() + "-" + (parseInt(t.getMonth(), 10)+1).toString() + "-" + t.getDate() + "T" + t.getHours() + ":" + t.getMinutes());
            // }
            $(this).appendDtpicker({
                "dateFormat": "YYYY-MM-DDThh:mm",
                "current": $(this).val(),
                "minuteInterval": mint / 60 / 1000
            });
        });

        multirange.init();
        console.log($(".multirange"));
        $(".multirange").each(dateRangeUpdate);
    });
});