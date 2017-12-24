window.mobile = ('ontouchstart' in window) || // Advanced test for touch events
    (window.DocumentTouch && document instanceof DocumentTouch);

window.loaded.push(function() {
    if (mobile) {
        $(".desktop").addClass("mobile").removeClass("desktop");
    }

    $.fn.outerHTML = function() {
        return $('<div />').append(this.eq(0).clone()).html();
    };

    this.customDialog = function(title, message, callback) {
        $("#mother").append('<div id="cover"></div>');
        var html = '<div id="alert_box">' + (title ? '<h2>' + title + '</h2>' : '') + '<div>' + message + '</div><button style="float:right;" id="ok">Close</button></div>';
        $("#mother").append(html);
        $("#alert_box button#ok").click(function() {
            if (callback) callback();
            $("#cover").remove();
            $("#alert_box").fadeOut(400, function() {
                $("#alert_box").remove();
            });
        });
        $("#alert_box div a").click(function() {
            if (callback) callback();
            $("#cover").remove();
            $("#alert_box").fadeOut(400, function() {
                $("#alert_box").remove();
            });
        });
        $("#cover").click(function() {
            if (callback) callback();
            $("#cover").remove();
            $("#alert_box").fadeOut(400, function() {
                $("#alert_box").remove();
            });
        });
        $('#alert_box').css({
            'z-index': 6,
            'position': 'fixed',
            'left': '50%',
            'top': '50%',
            'margin-left': function() { return -$(this).outerWidth() / 2 },
            'margin-top': function() { return -$(this).outerHeight() / 2 }
        });
        $("input[type=datetime-local]").each(function() {
            $(this).appendDtpicker({
                "dateFormat": "YYYY-MM-DDThh:mm",
                "current": $(this).val()
            });
        });
    };

    if (!(window.FormData && window.Promise && window.Promise.all)) {
        this.customDialog("Browser upgrade", "Please upgrade to a new browser,<br />as you may not be able to submit some forms<br />and will not be able to upload files.");
    }

    this.memberSelect = function(callback, multiple) {
        if (typeof callback == 'undefined') {
            return new Promise(memberSelect);
        } else {
            $.ajax('/' + HOST_SUB_DIR + 'memberlist', {
                method: 'POST',
                data: 'method=PUT&ajax=true',
                success: function(data, status, jqxhr) {
                    data = JSON.parse(data);
                    html = '<form id="memberSelect">';
                    html += "<input id=\"memberSelectFormSearch\" type=\"text\" />";
                    html += "<div class=\"labels\" style=\"height:" + Math.min(Math.min(42 * 12, $(window).height() * 0.4), 42 * Object.keys(data).length) + "px;overflow:auto\">";
                    for (var i in data) {
                        var c = data[i];
                        html += "<input name=\"memberSel\" type=\"" + (multiple ? "checkbox" : "radio") + "\" id=\"select" + i + "\" value=\"" + i + "\" class=\"selecterTestStuff\" />";
                        html += "<label class=\"shown selecterTestStuffLabel\" for=\"select" + i + "\">" + c + "</label>";
                    }
                    html += "</div>";
                    html += "</form>";
                    window.customDialog("Select a member", html, function() {
                        ret = [];
                        $("#memberSelect input:checked").each(function() {
                            ret.push({
                                'id': $(this).val(),
                                'name': $(this).find("+ label").text()
                            });
                        });
                        callback(ret);
                    });
                    $("#memberSelectFormSearch").on("keyup keydown", function() {
                        $search = new RegExp($(this).val(), 'i');
                        $els = [];
                        $("input[name=memberSel]").each(function(index) {
                            $label = $(this).next();
                            if ($(this).val().match($search)) {
                                $label.addClass("shown");
                                $els.push(this);
                            } else if ($label.html().match($search)) {
                                $label.addClass("shown");;
                                $els.push(this);
                            } else {
                                $label.removeClass("shown");
                            }
                        });
                        if ($els.length == 1) {
                            $($els[0]).attr("checked", "checked");
                        }
                    });
                    $("#memberSelect").on("submit", function () {
                        $(this).parent().parent().find("button").click();
                        return false;
                    });
                }
            });
        }
    };

    this.fileSelect = function(callback) {
        if (typeof callback == 'undefined') {
            return new Promise(fileSelect);
        } else {
            $.ajax('/' + HOST_SUB_DIR + 'filelist', {
                method: 'POST',
                data: 'method=PUT&ajax=true',
                success: function(data, status, jqxhr) {
                    data = JSON.parse(data);
                    html = '<form id="fileSelect">';
                    html += "<input id=\"fileSelectFormSearch\" style=\"position:relative\" type=\"text\" />";
                    html += "<div class=\"labels\" style=\"height:" + Math.min(Math.min(42 * 12, $(window).height() * 0.4), 42 * Object.keys(data).length) + "px;overflow:auto;padding-top:1px\">";
                    for (var i in data) {
                        var c = data[i];
                        html += "<input name=\"fileSel\" type=\"checkbox\" id=\"select" + i + "\" value=\"" + i + "\" class=\"selecterTestStuff\" />";
                        html += "<label class=\"shown selecterTestStuffLabel" + (c[1] ? " popupimagecontainer popupimagecontainer2" : "") + "\" for=\"select" + i + "\">" + c[0];
                        if (c[1]) {
                            html += "<div class=\"image-box popupimage popupimage2\"><img class=\"image\" src=\"/" + HOST_SUB_DIR + "filedownloader/" + i + "?ajax=true\" /></div>";
                        }
                        html += "</label>";
                    }
                    html += "</div>";
                    html += "</form>";
                    window.customDialog("Select a file", html, function() {
                        ret = [];
                        $("#fileSelect input:checked").each(function() {
                            ret.push($(this).val());
                        });
                        callback(ret);
                    });
                    $("#fileSelectFormSearch").on("keyup keydown", function() {
                        $search = new RegExp($(this).val(), 'i');
                        $("input[name=fileSel]").each(function(index) {
                            $label = $(this).next();
                            if ($(this).val().match($search)) {
                                $label.addClass("shown");
                                return;
                            }
                            if ($label.html().match($search)) {
                                $label.addClass("shown");;
                                return;
                            }
                            $label.removeClass("shown");
                        });
                    });
                    $(".popupimagecontainer2").on("mouseover", function() {
                        var $l = $(this),
                            $img = $l.find(".popupimage2");
                        var pos = $l.position();
                        $img.css({
                            top: (pos.top + 42) + "px",
                            left: "15px"
                        })
                    });
                }
            });
        }
    };

    this.getErrorMsg = function(code) {
        code = code.toString() ? code.toString() : "400";
        var codes = {
            "4": {
                "0": {
                    "1": "You aren't allowed to do that",
                    "2": "You don't have all permissions required"
                },
                "1": {
                    "1": "You aren't logged in"
                },
                "2": {
                    "1": "You didn't fill out the entire form"
                }
            },
            "3": { // Client errors
                "0": {
                    "0": "Some parse error happened, please let the webmaster know how this error happened so it can be fixed"
                },
                "1": {
                    "1": "Invalid input"
                }
            },
            "2": { // Network errors

            },
            "1": { // Server errors
                "0": {
                    "0": "The server is down for maintenance"
                },
                "1": { // Environment errors
                    "1": "The user database could not be reached",
                    "2": "The server database could not be connected to"
                }
            }
        };
        var code = code.toString().split("");
        if (codes[code[0]] && codes[code[0]][code[1]] && codes[code[0]][code[1]][code[2]]) {
            return [codes[code[0]][code[1]][code[2]], code.join("")];
        } else {
            return ["Some undertmined error, please contact the webmaster with details about how it is the error happened to get it fixed", "500"];
        }
    };

    this.displayErrorMsg = function(code) {
        var code = getErrorMsg(code);
        var html = "<h2>Oops! Error #" + code[1] + " occurred, for more details ask the webmaster</h2>";
        html += '<p style="font-family:monospace;">' + code[0] + '</p>';
        $("#body").html(html);
    };

    this.alertErrorMsg = function(code) {
        var code = getErrorMsg(code);
        var html = "<h2>Oops! Error #" + code[1] + " occurred, for more details ask the webmaster</h2>";
        html += '<p style="font-family:monospace;">' + code[0] + '</p>';
        customDialog("Oops! Error #" + code[1] + " occurred, for more details ask the webmaster", code[0]);
    }

    this.ulopen = false;
    this.slidemenu = function() { // Function that opens and closes the menu when the user is on a mobile device
        var ul = $("#navbar");
        ulopen = true;
        ul.animate({
            "left": 0
        }, 200, "linear");
        $("#modal").animate({
            "left": "50%",
            "right": 0
        }, 200, "linear");
    };
    this.closemenu = function() {
        if (ulopen) {
            ulopen = false
            $("#navbar").animate({
                "left": '-50%'
            }, 200, "linear");
            $("#modal").animate({
                "left": "-100%",
                "right": "100%"
            }, 200, "linear");
        }
    };

    this.viewImage = function(a) {
        $img = $(a).find('img');
        src = $($img).attr("src");
        comment = $(a).find("span").text();
        console.log(src.split("/")[2].split("?")[0]);
        this.customDialog(null, "<img src=\"" + src + "\" class=\"image-view\" /><br /><span class=\"caption\">" + comment + "</span>");
    };

    function resizeTextareas() {
        var sum = 1;
        var text = $(this).val().split("\n");
        for (var i = 0; i < text.length; i++) {
            sum += Math.ceil((1 + text[i].length) / 50);
        }
        $(this).attr("rows", sum);
    }

    $(function() {
        var id = mobile ? "#topbar" : "#navbar";
        window.offset = $(id).offset().top; // + $(id).height() / 2;
        $(window).scroll(function() {
            if (!mobile) {
                if ($(window).scrollTop() >= offset && $(window).outerWidth() > 800) {
                    $("#navbarhelper").css({ "height": $("#navbar").height() });
                    $("#navbar").addClass("stuck");
                } else if ($(window).outerWidth() > 800 && $(window).scrollTop() < offset) {
                    $("#navbarhelper").css({ "height": "0px" });
                    $("#navbar").removeClass("stuck");
                }
            } else {
                if ($(window).scrollTop() >= offset && $(window).outerWidth() > 800) {
                    $("#topbarhelper").css({ "height": $("#topbar").height() });
                    $("#topbar").addClass("stuck");
                } else if ($(window).outerWidth() > 800 && $(window).scrollTop() < offset) {
                    $("#topbarhelper").css({ "height": "0px" });
                    $("#topbar").removeClass("stuck");
                }
            }
        });
        $(window).resize(function() {
            window.offset = $(id).offset().top;
        });
        $("a.top").click(function(e) {
            $("html, body, #body").animate({ scrollTop: '0' }, 'slow');
            return false;
        });
        $("#modal").on("click touch", closemenu);
        $(".close-button").on("click touch", closemenu);
        $("#menu-button").on("click touch", slidemenu);
        $("#signIn_box").addClass("hidden");
        $("#signIn_link").on("click touch", function() {
            $("#signIn_box").css("display", "block");
            $(this).blur();
            $("#mother").append('<div id="cover"></div>');
            $("#signIn_box").css({
                "z-index": 6,
                "position": "fixed",
                "left": '50%',
                'top': '50%',
                'margin-left': function() { return -$(this).outerWidth() / 2 },
                'margin-top': function() { return -$(this).outerHeight() / 2 }
            });
            $("#cover").click(function() {
                $("#signIn_box").fadeOut(400, function() {
                    $("#signIn_box").css({
                        "display": "none"
                    });
                })
                $("#cover").remove();
            });
            $("#signIn").off();
            $("#signIn").submit(function() {
                $("#body").html("");
                $("#signIn_box").fadeOut(400, function() {
                    $("#signIn_box").css({
                        "display": "none"
                    });
                })
                $("#cover").remove();
                $("#loader").css("display", "block");
            });
        });
        $("#signOut_link").on("click touch", function() {
            $(".signedin").addClass("signedout").removeClass("signedin");
            localStorage.removeItem("LOGIN_DETAILS");
            getHtml();
        });
    });
    addFunction(function() {
        $(".capimage .image-box").each(function() {
            $(this).css({
                "height": $(this).width(),
                "width": $(this).width()
            });
        });
        $("textarea").each(resizeTextareas);
        $("textarea").change(resizeTextareas);
        $("textarea").keydown(resizeTextareas);
        $("textarea").keyup(resizeTextareas);
    });
});