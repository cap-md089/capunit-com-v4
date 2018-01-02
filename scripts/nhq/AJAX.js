window.loaded.push(function() {
    this.getHtml = function(url, func, method, scback, fcback, push, remove, update, changeThing) {
        push = typeof push == 'undefined' ? true : push;
        url = url == "#" ? window.location.pathname + window.location.search : url || (window.location.pathname + window.location.search);
        method = method || 'GET';
        if (['GET', 'POST', 'PUT'].indexOf(method.toUpperCase()) == -1) method = 'GET';
        method = method.toUpperCase();
        var safeurl = url;
        if (url.split("?").length === 1) {
            url += "?ajax=true";
        } else {
            url += "&ajax=true";
        }
        if (func && func != '') {
            if (safeurl.split("?").length === 1) {
                safeurl += "?func=" + func
            } else {
                safeurl += "&func=" + func
            }
            url += "&func=" + func;
        }
        url += "&cookies=" + encodeURIComponent(JSON.stringify(getCookies()));
        url += "&mobile=" + (window.mobile ? "true" : "false");
        if (!remove) {
            $("#pageblock").html("");
            $("#sidenav").html("");
            $("#breadcrumbs").html("");
            $("#loader").css({
                "display": "block"
            });
        }
        fcback = fcback || function(xhr, status, error) {
            text = xhr.responseText;
            ret = parseReturn(text.replace('\r', ''));
            $("#pageblock").html(ret.MainBody);
            $("#sidenav").html(ret.SideNavigation);
            $("#breadcrumbs").html(ret.BreadCrumbs);
            $("#loader").css({
                "display": "none"
            });
        };
        scback = scback || function(data, stat, jxhr) {
            data = parseReturn(data);
            if (jxhr.getResponseHeader('X-User-Error')) {
                if (jxhr.getResponseHeader("X-User-Error") == 411) {
                    $(".signedin").addClass("signedout").removeClass("signedin");
                }
                displayErrorMsg(jxhr.getResponseHeader('X-User-Error').toString());
            } else {
                $("#pageblock").html(data.MainBody);
                $("#sidenav").html(data.SideNavigation);
                $("#breadcrumbs").html(data.BreadCrumbs);
            }
            if (jxhr.getResponseHeader('X-UserLogin')) {
                var thing = jxhr.getResponseHeader('X-UserLogin') == 'false' ? $(".signedin").addClass("signedout").removeClass("signedin") : $(".signedout").addClass("signedin").removeClass("signedout");
            }
            $(".descriptions").each(function () {
                $(this).attr('content', data.Description || "An Event Management site for Civil Air Patrol units");
            });
            $("#website_title").text(jxhr.getResponseHeader('X-User-Title'));
            $("#website_title_meta").attr("content", jxhr.getResponseHeader("X-User-Title"));
            $("#website_title_url").attr("content", url.split("?")[0]);
            $("#loader").css({
                "display": "none"
            });
            executeFunctions();
        };
        query = url.split("?")[1];
        $.ajax(url.split("?")[0], {
            method: 'POST',
            data: query + "&method=get",
            success: scback,
            error: fcback
        });

        if (!update) {
            window.history[push ? 'pushState' : 'replaceState']({ url: safeurl }, null, safeurl);
        }
        if (changeThing === undefined || changeThing) {
            $(".selected").removeClass("selected");
            u = document.createElement('a');
            u.href = url;
            url = u.pathname;
            url = (url == "/" ? "/main/" : url);
            if (['main', 'blog', 'calendar', 'photolibrary'].indexOf(url.split('/')[1].toLowerCase()) > -1) {
                $("."+url.split('/')[1].toLowerCase()).addClass("selected"); 
            } 
        }
    };
    this.handleFormSubmit = function(form, takeoutput) {
        try {
            var $form = $(form);
            (takeoutput ? $form.find('div.formbox').last().find('div') : $form.parent().find('#output')).html("");
            $form.find("input[type=submit]").prop("disabled", true);
            var url = form.action || location.href,
                scback;
            if (form.getAttribute('data-signin-form') === 'true') {
                $(this).css("display", "none");
            }
            scback = scback || function(data, status, xhr) {
                data = parseReturn(data);
                $form.find("input[type=submit]").prop("disabled", false);
                if (xhr.getResponseHeader("X-User-Error")) {
                    displayErrorMsg(xhr.getResponseHeader("X-User-Error"));
                } else if (form.getAttribute('data-signin-form') === 'true') {
                    data = JSON.parse(data.MainBody);
                    setCookies(data);
                    getHtml();
                } else if (form.getAttribute("data-form-reload") === 'true') {
                    getHtml();
                } else if (!takeoutput) {
                    $form.parent().find("#output").html(data.MainBody);
                } else {
                    $form.find('div:last').html(data.MainBody);
                }
                if (xhr.getResponseHeader('X-UserLogin')) {
                    var thing = xhr.getResponseHeader('X-UserLogin') == 'false' ? $(".signedin").addClass("signedout").removeClass("signedin") : $(".signedout").addClass("signedin").removeClass("signedout");
                }
                if ($(form).attr("id") == 'search' && mobile) {
                    closemenu();
                }
            };
            if (form.getAttribute("data-form-beforesend") != '') {
                var _func = window[form.getAttribute("data-form-beforesend")];
                if (!!_func) {
                    var push = _func($(form));
                } else {
                    var push = true;
                }
            } else {
                var push = true;
            }
            if (push) {
                if (window.FormData && window.Promise && window.Promise.all) {
                    if (form.getAttribute('data-signin-form') === 'true') {
                        $("#pageblock").html("");
                        $("#breadcrumbs").html("");
                        $("#sidenav").html("");
                        $("#loader").css({
                            "display": "none"
                        });
                        if (mobile) {
                            closemenu();
                        }
                    }
                    var formd = new FormData();
                    var filep = [];
                    $(form).find("input").each(function(i) {
                        switch ($(this).attr("type")) {
                            case "checkbox":
                                formd.append($(this).attr("name"), $(this).prop("checked"));
                                break;

                            case "radio":
                                if ($(this).prop("checked")) {
                                    formd.append($(this).attr("name"), $(this).val());
                                }
                                break;

                            case "datetime-local":
                                var date = createDate($(this).val().toString());
                                date = date ? date.getTime() / 1000 : 0;
                                formd.append($(this).attr("name"), date);
                                break;

                            case "range":
                                if ($(this).attr("multiple") && !this.classList.contains('ghost')) {
                                    formd.append($(this).attr("name") + "Low", this.valueLow);
                                    formd.append($(this).attr("name") + "High", this.valueHigh);
                                } else if ($(this).attr("name") && !this.classList.contains('ghost')) {
                                    formd.append($(this).attr("name"), $(this).val());
                                }
                                break;

                            case "file":
                                console.log("file");
                                $file = $(this);
                                var files = $file.prop("files");
                                console.log(files);
                                var formdata;
                                for (var i = 0; i < files.length; i++) {
                                    filep.push(new Promise(function(resolve, reject) {
                                        formdata = new FormData();
                                        formdata.append("cookies", JSON.stringify(getCookies()));
                                        formdata.append("ajax", "true");
                                        formdata.append("form", "true");
                                        formdata.append("filesList[]", $file.attr("name"));
                                        formdata.append($file.attr("name"), files[i], files[i].name);
                                        console.log("Sending file");
                                        $.ajax({
                                            url: '/' + HOST_SUB_DIR + 'fileuploader',
                                            data: formdata,
                                            processData: false,
                                            contentType: false,
                                            type: "POST",
                                            async: true,
                                            cache: false,
                                            beforeSend: function(jqXHR) {
                                                jqXHR.async = true;
                                            },
                                            success: function(data, stat, jxhr) {
                                                console.log("Success");
                                                if (jxhr.getResponseHeader("X-User-Error")) {
                                                    reject();
                                                }
                                                if (jxhr.getResponseHeader("X-Error")) {
                                                    reject(jxhr.getResponseHeader("X-Error"));
                                                }
                                                console.log(jxhr.getResponseHeader("X-Error"));
                                                resolve({
                                                    "name": $file.attr("name"),
                                                    "id": parseReturn(data).MainBody
                                                });
                                            },
                                            error: function() {
                                                reject();
                                            }
                                        })
                                    }));
                                }
                                break;

                            default:
                                if ($(this).attr("name")) {
                                    formd.append($(this).attr("name"), $(this).val());
                                }
                                break;
                        }
                    });
                    $(form).find("select").each(function(i) {
                        formd.append($(this).attr("name"), $(this).find('option:selected').val());
                    });
                    $(form).find("textarea").each(function() {
                        formd.append($(this).attr("name"), $(this).val());
                    });
                    formd.append("cookies", JSON.stringify(getCookies()));
                    formd.append("ajax", "true");
                    formd.append("form", "true");
                    formd.append("mobile", (window.mobile ? "true" : "false"));
                    Promise.all(filep).then(function(values) {
                        for (var i = 0; i < values.length; i++) {
                            formd.append(values[i].name, values[i].id);
                        }
                        $.ajax({
                            url: url,
                            data: formd,
                            type: "POST",
                            processData: false,
                            contentType: false,
                            async: true,
                            cache: false,
                            beforeSend: function(jqXHR) {
                                jqXHR.async = true;
                            },
                            success: function (a, b, c) {
                                $form.find("input[type=submit]").prop("disabled", false);
                                scback(a, b, c);
                            },
                            error: function(xhr) {
                                $("#sidenav").html("");
                                $("#pageblock").html(xhr.responseText);
                                $("#breadcrumbs").html("");
                                $("#loader").css({
                                    "display": "none"
                                });
                            }
                        });
                    }, function() {
                        customDialog("File upload error", "We're sorry, but we can't upload files that are bigger than 2MB");
                    });
                } else if ($(form).find("input[type=\"file\"]").length == 0) {
                    if (form.getAttribute('data-signin-form') === 'true') {
                        $("#sidenav").html("");
                        $("#pageblock").html("");
                        $("#breadcrumbs").html("");
                        $("#loader").css({
                            "display": "block"
                        });
                        if (mobile) {
                            closemenu();
                        }
                    }
                    query = "ajax=true&form=true&cookies=" + encodeURIComponent(JSON.stringify(getCookies()));
                    $(form).find("input").each(function(i) {
                        switch ($(this).attr("type")) {
                            case "checkbox":
                                query += "&" + encodeURIComponent($(this).attr("name")) + "=" + encodeURIComponent($(this).prop("checked"));
                                break;

                            case "radio":
                                if ($(this).prop("checked")) {
                                    query += "&" + encodeURIComponent($(this).attr("name")) + "=" + encodeURIComponent($(this).val());
                                }
                                break;

                            case "range":
                                if ($(this).attr("multiple") && !this.classList.contains('ghost')) {
                                    query += "&" + encodeURIComponent($(this).attr("name") + "Low") + "=" + encodeURIComponent(this.valueLow);
                                    query += "&" + encodeURIComponent($(this).attr("name") + "High") + "=" + encodeURIComponent(this.valueHigh);
                                } else if ($(this).attr("name") && !this.classList.contains('ghost')) {
                                    query += "&" + encodeURIComponent($(this).attr("name")) + "=" + encodeURIComponent($(this).val());
                                }
                                break;

                            case "datetime-local":
                                var date = createDate($(this).val().toString());
                                date = date ? date.getTime() / 1000 : 0;
                                query += "&" + encodeURIComponent($(this).attr("name")) + "=" + date;
                                break;

                            default:
                                if ($(this).attr("name")) {
                                    query += "&" + encodeURIComponent($(this).attr("name")) + "=" + encodeURIComponent($(this).val());
                                }
                                break;
                        }
                    });
                    $(form).find("select").each(function(i) {
                        query += "&" + encodeURIComponent($(this).attr("name")) + "=" + encodeURIComponent($(this).find('option:selected').val());
                    });
                    $(form).find('textarea').each(function(i) {
                        query += "&" + encodeURIComponent($(this).attr("name")) + "=" + encodeURIComponent($(this).val());
                    });
                    query += "$mobile="+(window.mobile ? "true" : "false");
                    $.ajax({
                        url: url,
                        data: query,
                        type: "POST",
                        success: function (a, b, c) {
                            $form.find("input[type=submit]").prop("disabled", false);
                            scback(a, b, c);
                        },
                        error: function(xhr) {
                            $("#sidenav").html("");
                            $("#pageblock").html(xhr.responseText);
                            $("#breadcrumbs").html("");
                            $("#loader").css({
                                "display": "none"
                            });
                        }
                    });
                } else {
                    customDialog('Browser upgrades', "Please upgrade to a newer browser, preferably FireFox or Google Chrome");
                    $form.find("input[type=submit]").prop("disabled", false);
                }
            } else {
                $form.find("input[type=submit]").prop("disabled", false);
            }
        } catch (e) {
            console.log(e);
        }
        return false;
    };

    this.getCookies = function() {
        var cookies = {};
        for (var i = 0; i < localStorage.length; i++) {
            cookies[localStorage.key(i)] = localStorage.getItem(localStorage.key(i));
        }
        return cookies;
    };

    this.setCookies = function(cookies) {
        console.log(cookies);
        if (cookies.valid) {
            for (var key in cookies.cookie) {
                localStorage.setItem(key, cookies.cookie[key]);
            }
        } else {
            if (cookies.reset) {
                customDialog("Password Reset", "Your password needs to be reset on <a href=\"https://www.capnhq.gov/\">CAP NHQ</a>");
            } else if (cookies.down) {
                customDialog("CAPNHQ Down", "CAP National Headquarters is not online at the moment, or we cannot connect to NHQ. We are sorry for the inconvenience.");
            } else {
                customDialog("Invalid login", "Login credentials could not be verified");
            }
        }
    };

    this.AJAXLinkClick = function(linkElement) {
        var a = linkElement;
        url = a.href;
        console.log('click');

        if (mobile) {
            closemenu();
        }

        getHtml(url);

        return false;
    };

    this.AsyncButton = function(linkElement, cbackn) {
        try {
            var url = linkElement.href;
            var a = $(linkElement);

            method = a.attr("data-http-method");
            if (['get', 'post'].indexOf(method.toLowerCase()) == -1) method = 'post'
            method = method.toUpperCase();

            var safeurl = url;

            url += (url.split("?").length - 1 ? "&" : "?") + "ajax=true";

            url += "&cookies=" + encodeURIComponent(JSON.stringify(getCookies()));

            if (a.attr("data-http-data")) {
                url += "&data=" + encodeURIComponent(a.attr("data-http-data"));
            }

            url += "&method=" + encodeURIComponent(a.attr("data-http-func"));
            url += "&mobile=" + (window.mobile ? "true" : "false");

            push = true;
            if (typeof window[cbackn + "_prepush"] !== 'undefined') {
                data = window[cbackn + "_prepush"](a, a.attr("data-http-data"));
                if (data instanceof Promise) {
                    data.then(function(d) {
                        url += "&predata=" + encodeURIComponent(d);

                        var query = url.split("?")[1];
                        $.ajax(url.split("?")[0], {
                            method: method,
                            data: query,
                            async: true,
                            cache: false,
                            success: function(data, status, jqxhr) {
                                if (window[cbackn]) window[cbackn](parseReturn(data).MainBody, status, jqxhr, a);
                            },
                            error: window[cbackn + "_error"]
                        });
                    });
                    push = false;
                }
                if (typeof data == "boolean" || typeof data == "string") {
                    push = !!data;
                }
                if (typeof data.push == "boolean") {
                    push = data.push;
                }
                if (typeof data == "string") {
                    url += "&predata=" + data;
                }
            }
            if (push) {
                var query = url.split("?")[1];
                $.ajax(url.split("?")[0], {
                    method: method,
                    data: query,
                    async: true,
                    cache: false,
                    success: function(data, status, jqxhr) {
                        console.log(cbackn);
                        if (window[cbackn]) window[cbackn](parseReturn(data).MainBody, status, jqxhr, a);
                        console.log(window[cbackn].toString());
                    },
                    error: window[cbackn + "_error"]
                });
            }

            return false;
        } catch (e) {
            console.log(e);
            return false;
        }
    };

    this.getPageId = function() {
        var four = this.location.href.split("/")[3];
        return four ? four : "";
    };

    this.onpopstate = function(data) {
        if (data.state) {
            getHtml(data.state.url, undefined, undefined, undefined, undefined, false);
        }
    };
});