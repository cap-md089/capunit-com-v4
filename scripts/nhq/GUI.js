window.mobile = false;
(function(a){if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))) window.mobile = true;})(navigator.userAgent||navigator.vendor||window.opera);


var positionStickySupport = function() {var el=document.createElement('a'),mStyle=el.style;mStyle.cssText="position:sticky;position:-webkit-sticky;position:-ms-sticky;";return mStyle.position.indexOf('sticky')!==-1;}();

window.stuckMenu = false;

var initializeMobile = function () {
    window.mobile = true;
    $(".desktop").addClass("mobile").removeClass("desktop");
    $("#body").prepend("<div id=\"mobilemenublock\"><div id=\"mobileopener\"><span class=\"arrow\"></span><span id=\"menuMenu\">Menu</span></div><div id=\"mobilemenu\"></div></div><div id=\"mobilemenuhelper\"></div>");
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

	/**
 * jQuery Text Fit v1.0
 * https://github.com/nbrunt/TextFit
 *
 * Copyright 2013 Nick Brunt
 * http://nickbrunt.com
 *
 * Free to use and abuse under the MIT license.
 * http://www.opensource.org/licenses/mit-license.php
 */
!function(a){var b={width:function(b){var c=a("<span>"+b+"</span>").css({position:"absolute","white-space":"nowrap",visibility:"hidden"}).css("font",this.css("font"));a("body").append(c);var d=c.width();return c.remove(),d},bestfit:function(){for(var a=parseInt(this.css("font-size"),10),b=c(this);b.height()>this.height();)this.css("font-size",--a+"px");return d(b),this},truncate:function(a){for(var e,b=c(this);b.height()>this.height();)e=b.html(),b.html(e.substring(0,e.length-4)),b.append("...");return d(b),this}};a.fn.textfit=function(c){return this.length>1?void this.each(function(){a(this).textfit(c)}):b[c]?b[c].apply(this,Array.prototype.slice.call(arguments,1)):"object"!=typeof c&&c?void a.error("Method "+c+" does not exist on jQuery.textfit"):b.init.apply(this,arguments)};var c=function(b){return b.wrapInner(a("<div id='textfit-inner'></div>").css("width",b.css("width"))),a("#textfit-inner")},d=function(a){a.replaceWith(a.contents())}}(jQuery);

	$(".pagetitle").textfit('bestfit');

    $.fn.outerHTML = function() {
        return $('<div />').append(this.eq(0).clone()).html();
    };

    this.customDialog = function(title, message, callback) {
        $("#mother").append('<div class="cover"></div>');
        var html = '<div id="alert_box">' + (title ? '<h2>' + title + '</h2>' : '') + '<div class="content">' + message + '</div><div class="closeButton"><a style="float:right;" class="primaryButton" id="ok">Close</a></div></div>';
        $("#mother").append(html);
        $("#alert_box #ok, #alert_box a, .cover").click(function() {
			console.log('Running 5');
            if (callback) callback();
            $(".cover").remove();
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
						console.log('Running 3');
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
                },
                "3": {
                    "1": "This content is restricted to members of this account/unit"
                }
            },
            "3": { // Client errors
                "0": {
                    "0": "Some parse error happened, please let the webmaster know how this error happened so it can be fixed",
                    "1": "ScanAdd Session ID cookie missing",
                    "2": "ScanAdd Session ID not present in database"
                },
                "1": {
                    "1": "Invalid input",
                    "2": "The CAPID and email address provided could not be validated. Please contact support@capunit.com for assistance.",
                    "3": "There is already an account for this CAPID. Please contact support@capunit.com for assistance.",
                    "4": "The CAPID and email address provided could not be validated. Please contact support@capunit.com for assistance.",
                    "5": "The token provided is invalid. Please request another email link via the Create Account page.",
                    "6": "The CAPID and email address provided could not be validated. Please contact support@capunit.com for assistance.",
                    "7": "An undefined error occurred. Please contact support@capunit.com for assistance and provide the CAPID and email address used in the account creation process.",
                    "8": "Invalid CAPID"
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
            return ["Some undetermined error, please contact the webmaster with details about how it is the error happened to get it fixed", "300"];
        }
    };

    this.displayErrorMsg = function(code) {
        var code = getErrorMsg(code);
        var html = "<h2>Oops! Error #" + code[1] + " occurred, for more details ask the webmaster</h2>";
        html += '<p style="font-family:monospace;">' + code[0] + '</p>';
        if (code[1] == 411) {
            html += "<p><a href=\"#\" class=\"signin_link\">Sign in now</a></p>";
        }
        $("#pageblock").html(html);
        $("#sidenav").html("");
        $("#breadcrumbs").html("");
    };

    addFunction(function () {
        $(".signout_link").on("click touch", function () {
            localStorage.removeItem("LOGIN_DETAILS");
            getHtml();
        });
		$(".signin_link").on("click touch", function () {
			getHtml('/signin?returnurl=' + window.location.pathname);
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
        $("#modal").on("click touch", closemenu);
        $(".close-button").on("click touch", closemenu);
        $("#menu-button").on("click touch", slidemenu);
    });
    addFunction(function() {
        $("textarea").each(resizeTextareas);
        $("textarea").change(resizeTextareas);
        $("textarea").keydown(resizeTextareas);
        $("textarea").keyup(resizeTextareas);
    });
    addFunction(function () {
        if (window.location.pathname.split("/")[1] != 'flightassign') {
            return true;
        }
        if (!$("#flights").length) {
            return true;
        }
        els = document.getElementsByClassName('flight');
        for (i in els) {
            els[i].ondrop = function (ev) {
                ev.preventDefault();
                ev.target.prepend(document.getElementById(ev.dataTransfer.getData('text')));
                var el = document.getElementById(ev.dataTransfer.getData('text'));
                console.log(el.parentElement);
					var cs;
                console.log(el.parentElement.classList);
                if (Array.prototype.slice.apply(el.parentElement.classList).indexOf('flight') == -1) {
                    el.parentElement.parentElement.prepend(el);
                cs = Array.prototype.slice.apply(ev.target.parentElement.classList);
                cs.splice(cs.indexOf('flight'), 1);
				} else {
                cs = Array.prototype.slice.apply(ev.target.classList);
                cs.splice(cs.indexOf('flight'), 1);
                }
				var input = document.getElementById(ev.dataTransfer.getData('text')).children[0];
				input.value = input.value.split(':')[0] + ':' + cs[0];
            };
            els[i].ondragover = function (ev) {
                ev.preventDefault();
            }
        }
        var els = document.getElementsByClassName('cadet');
        for (var i in els) {
            els[i].draggable = true;
            els[i].ondragstart = function (ev) {
                ev.currentTarget.style.border = 'dashed';
                ev.dataTransfer.setData('text', ev.target.id);
                ev.effectAllowed = 'move';
            };
            els[i].ondragend = function (ev) {
                ev.target.style.border = 'solid';
                ev.dataTransfer.clearData();
            };
            els[i].ondrop = function(ev) {};
            els[i].ondragover = function(ev) {};
        }
    });

	addFunction(function () {
		$('input[type=file]').on('change', function (e) {
			$(this).parent().find('.fileNames').html(
				$.map($(this).prop('files'), function (f) { return f.name; })
				.join('<br />'));
		});
	});
});

    addFunction(function() {
        if (window.location.pathname.split("/")[1] == 'scanadd') {
		document.getElementById('capid').focus();

		setTimeout(function() {
			if (window.location.pathname.split("/")[1] == 'scanadd') {
				document.getElementById('signup-message').style.display = 'none';
			}
		}, 5000);
        }
    });
