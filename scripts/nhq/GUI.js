var SIGNIN_FORM = '<?php
    $libs = scandir (BASE_DIR . "lib");
    foreach ($libs as $lib) {
        if (is_file(BASE_DIR . "lib/$lib") && explode(".", $lib)[1] == 'php') {
            require_once (BASE_DIR . "lib/$lib");
        }
    }

    $form = new AsyncForm('/signin', 'Sign in', 'hidden', 'signin');

    $message = "Enter your eServices login information below to sign into the site.  Your password is not ";
    $message .= "permanently stored.  By providing your eServices information you agree to the terms and conditions ";
    $message .= "located at https://www.capunit.com/EULA.php";
    $form->addField('eula',$message,'textread');
    $form->addField('name', 'CAP ID')->addField('password', 'Password', 'password')->setSubmitInfo('Log in');

    echo str_replace("'", "\\'",  str_replace("\n", "", $form));
?>';

window.mobile = 
// true || 
    ('ontouchstart' in window) || // Advanced test for touch events
    (window.DocumentTouch && document instanceof DocumentTouch);

var positionStickySupport = function() {var el=document.createElement('a'),mStyle=el.style;mStyle.cssText="position:sticky;position:-webkit-sticky;position:-ms-sticky;";return mStyle.position.indexOf('sticky')!==-1;}();

window.stuckMenu = false;

var initializeMobile = function () {
    window.mobile = true;
    $(".desktop").addClass("mobile").removeClass("desktop");
    $("#body").prepend("<div id=\"mobilemenublock\"><div id=\"mobileopener\"><span class=\"arrow\"></span></div><div id=\"mobilemenu\"></div></div><div id=\"mobilemenuhelper\"></div>");
    $("#mobileopener").on('click touch', function () {
        (ulopen ? closemenu : slidemenu)();
    });
    $($("#sidenav")[0]).detach().prependTo($("#mobilemenu"));
    $($(".search")[0]).detach().prependTo($("#mobilemenu"));
    // $($("#breadcrumbs")[0]).detach().prependTo($("#mobilemenu"));

    window.offset = $("#mobilemenublock").offset().top;

    $(window).scroll(function () {
        if ($(window).scrollTop() >= offset) {
            $("#mobilemenublock").addClass("stuck");
            stuckMenu = true;
        } else {
            $("#mobilemenublock").removeClass("stuck");
            stuckMenu = false;
        }
        if (ulopen) {
            if (stuckMenu) {
                $("#mobilemenublock").css({
                    "height" : '100%'
                });
            } else {
                $("#mobilemenublock").css({
                    "height" : $(window).height() - (offset - $(window).scrollTop()) + 30
                });
            }
        }
    });
    $(window).resize(function () {
        window.offset = $('#mobilemenublock').offset().top;
    })
}

var undoMobile = function () {
    window.mobile = false;
    $(".mobile").addClass("desktop").removeClass("mobile");
}

window.loaded.push(function() {
    if (mobile) {
        initializeMobile();
    }

    $.fn.outerHTML = function() {
        return $('<div />').append(this.eq(0).clone()).html();
    };

    this.displaySignIn = function (e) {
        if (e) e.preventDefault();
        $("#signin_box").css("display", "block");
        $(this).blur();
        $("#mother").append('<div id="cover"></div>');
        $("#signin_box").css({
            "z-index": 5020,
            "position": "fixed"
        });
        if (!window.mobile) {
            $("#signin_box").css({
                'left': '50%',
                'top': '50%',
                'margin-left': function() { return -$(this).outerWidth() / 2 },
                'margin-top': function() { return -$(this).outerHeight() / 2 }
            });
        } else {
            $("#signin_box").css({
                'left':'0px',
                'right':'0px',
                'top':'0px',
                'bottom':'0px'
            });
        }
        $("#cover").click(function() {
            $("#signin_box").fadeOut(400, function() {
                $("#signin_box").css({
                    "display": "none"
                });
            })
            $("#cover").remove();
        });
        $("#signin").off();
        $("#signin").submit(function() {
            $("#pageblock").html("");
            $("#signin_box").fadeOut(400, function() {
                $("#signIn_box").css({
                    "display": "none"
                });
            })
            $("#cover").remove();
            $("#loader").css("display", "block");
        });
    }

    this.customDialog = function(title, message, callback) {
        $("#mother").append('<div id="cover"></div>');
        var html = '<div id="alert_box">' + (title ? '<h2>' + title + '</h2>' : '') + '<div class="content">' + message + '</div><div class="closeButton"><a style="float:right;" class="primaryButton" id="ok">Close</a></div></div>';
        $("#mother").append(html);
        $("#alert_box #ok").click(function() {
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
            'z-index': 5010,
            'position': 'fixed'
        });
        if (!window.mobile) {
            $("#alert_box").css({
                'left': '50%',
                'top': '50%',
                'margin-left': function() { return -$(this).outerWidth() / 2 },
                'margin-top': function() { return -$(this).outerHeight() / 2 }
            });
        } else {
            $("#alert_box").css({
                'left':'0px',
                'right':'0px',
                'top':'0px',
                'bottom':'0px'
            });
        }
        $("input[type=datetime-local]").each(function() {
            $(this).appendDtpicker({
                "dateFormat": "YYYY-MM-DDThh:mm",
                "current": $(this).val()
            });
        });
        if ($("#alert_box input[type=text]")[0]) $("#alert_box input[type=text]")[0].focus();
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
                    data = JSON.parse(parseReturn(data).MainBody);
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
                    data = JSON.parse(parseReturn(data).MainBody);
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
                            left: "45px"
                        })
                    });
                }
            });
        }
    };

    this.getErrorMsg = function(code) {
        code = code.toString() ? code.toString() : "300";
        var codes = {
            "5": {
                "0" : {
                    "1" : "We're sorry, this is a paid subscription feature.  Please contact <a href=\"mailto:sales@capunit.com\">sales@capunit.com</a> to purchase a subscription and access this feature."
                }
            },
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
            return ["Some undetermined error, please contact the webmaster with details about how it is the error happened to get it fixed", "500"];
        }
    };

    this.displayErrorMsg = function(code) {
        var code = getErrorMsg(code);
        var html = "<h2>Oops! Error #" + code[1] + " occurred, for more details ask the webmaster</h2>";
        html += '<p style="font-family:monospace;">' + code[0] + '</p>';
        if (code[1] == 411) {
            html += "<p><a href=\"#\" id=\"signin_link\">Sign in now</a></p>";
        }
        $("#pageblock").html(html);
        $("#sidenav").html("");
        $("#breadcrumbs").html("");

        $("#signin_link").on("click touch", displaySignIn);
    };

    addFunction(function () {
        $("#signin_link").on("click touch", displaySignIn);
        $("#signout_link").on("click touch", function () {
            localStorage.removeItem("LOGIN_DETAILS");
            getHtml();
        });
    });

    this.alertErrorMsg = function(code) {
        var code = getErrorMsg(code);
        var html = "<h2>Oops! Error #" + code[1] + " occurred, for more details ask the webmaster</h2>";
        html += '<p style="font-family:monospace;">' + code[0] + '</p>';
        customDialog("Oops! Error #" + code[1] + " occurred, for more details ask the webmaster", code[0]);
    }

    this.ulopen = false;
    this.slidemenu = function() { // Function that opens and closes the menu when the user is on a mobile device
        if (!mobile) return;
        if (stuckMenu) {
            $("#mobilemenublock").animate({
                "height" : '100%'
            }, 200, 'linear');
        } else {
            $("#mobilemenublock").animate({
                "height" : $(window).height() - (offset - $(window).scrollTop()) + 30
            }, 200, 'linear');
        }
        ulopen = true;
    };
    this.closemenu = function() {
        if (!mobile) return;
        $("#mobilemenublock").animate({
            "height" : "30px"
        }, 200, 'linear');
        ulopen = false;
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
        $("a.top").click(function(e) {
            $("html, body, #body").animate({ scrollTop: '0' }, 'slow');
            return false;
        });
        $signinbox = $(SIGNIN_FORM);
        $("#modal").on("click touch", closemenu);
        $(".close-button").on("click touch", closemenu);
        $("#menu-button").on("click touch", slidemenu);
        $("#mother").append($signinbox);
        $("#signin_box").css({
            'display':'none'
        });
        $("#signin_link").on("click touch", displaySignIn);
    });
    addFunction(function() {
        $("textarea").each(resizeTextareas);
        $("textarea").change(resizeTextareas);
        $("textarea").keydown(resizeTextareas);
        $("textarea").keyup(resizeTextareas);
    });
});
