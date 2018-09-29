function deletePageFuncs(data) {
    getHtml('/' + HOST_SUB_DIR + 'page/list');
    location.reload();
}

function devIssues(data) {
    customDialog("Result", data);
    getHtml();
}

function deletePhotos(data, details, jqxhr, a) {
    a.remove();
    customDialog("Photo removal", data);
}

function deletePhotos_prepush() {
    return "photo";
}

window.loaded.push(function() {
    addFunction(function() {
        $form = $("#eventForm");
        if (!!$form) {
            child = $form.children().last();
            child = child.children().last().children().last();
            child.prop('disabled', true);
        }
    });
});

function createDate(string) { // Date.parse is inconsistent
    string = string.toString();
    if (!string.match(/((?:19|20)??\d{2})[\/\-\.](\d|0\d|1[0-2])[\/\-\.](\d|0\d|1\d|2\d|3[01])[\sT](0\d|1\d|2[0-4]|\d)\:?([0-5]\d)(\:([0-5]\d)(\.\d{1,3})?)?(?!(\sAM|\sPM))/i) &&
        !string.match(/((?:19|20)??\d{2})[\/\-\.](\d|0\d|1[0-2])[\/\-\.](\d|0\d|1\d|2\d|3[01])[\sT](0\d|1[0-2]|\d)\:?([0-5]\d)(?:\:([0-5]\d)(\.\d{1,3})?)?\s([aA][mM]|[pP][mM])/i)) {
        return false;
    }
    var data;
    if (string.match(/((?:19|20)??\d{2})[\/\-\.](\d|0\d|1[0-2])[\/\-\.](\d|0\d|1\d|2\d|3[01])[\sT](0\d|1[0-2]|\d)\:?([0-5]\d)(?:\:([0-5]\d)(\.\d{1,3})?)?\s([aA][mM]|[pP][mM])/i)) {
        data = string.match(/((?:19|20)??\d{2})[\/\-\.](\d|0\d|1[0-2])[\/\-\.](\d|0\d|1\d|2\d|3[01])[\sT](0\d|1[0-2]|\d)\:?([0-5]\d)(?:\:([0-5]\d)(\.\d{1,3})?)?\s([aA][mM]|[pP][mM])/i);
        data[4] += data[8].toLowerCase() == 'pm' ? 12 : 0;
    } else {
        data = string.match(/((?:19|20)??\d{2})[\/\-\.](\d|0\d|1[0-2])[\/\-\.](\d|0\d|1\d|2\d|3[01])[\sT](0\d|1\d|2[0-4]|\d)\:?([0-5]\d)(\:([0-5]\d)(\.\d{1,3})?)?(?!(\sAM|\sPM))/i);
    }
    if (data[1].length == 2) data[1] += '20';
    data.forEach(function(v, i, a) {
        a[i] = parseInt(v, 10);
        if (isNaN(a[i])) a[i] = null;
    });
    data[2] -= 1;
    var d = new Date(data[1], data[2], data[3], data[4], data[5], data[6], data[7]);
    return d;
}

function checkInputs($form) {
    ins = [];

    $('#eventForm input[type="datetime-local"]:lt(4)').each(function() {
        ins.push($(this).val());
    });

    if (!createDate(ins[0]) || !createDate(ins[1]) || !createDate(ins[2]) || !createDate(ins[3])) {
        customDialog("Form Input Error", "One of date and time fields is not correctly filled out");
        return false;
    }

    ins.forEach(function(element, index, array) {
        date = createDate(element);
        array[index] = date ? date.getTime() : 0;
    });

    if (((Date.now() / 1000) <= ins[0]) && (ins[0] <= ins[1]) && (ins[1] <= ins[2]) && (ins[2] <= ins[3])) {
        return true;
    } else {
        customDialog("Form Input Error", "The supplied dates and times are not in sequential order");
        return false;
    }
}

function deletePost(data) {
    customDialog("Post status", data);
    getHtml('/' + HOST_SUB_DIR + 'blog/');
}

function deletePost_prepush() {
    return "post";
}

function calendarEventView(data, details, jqxhr, a) {
    $a = $(a);
    ev = $a.attr('data-http-data');
    title = ev + ': ' + $a.text();
    customDialog(title, data);
}

function delEvent(data, status, jqxhr, a) {
    customDialog("Event deletion", data);
}

function copyEvent_prepush(a) {
    form = "<form class=\"asyncForm\">";
    form += "<div class=\"formbar\">";
    form += "<div class=\"formbox\"><label for=\"newDateInput\">Start time of new event</label></div>";
    form += "<div class=\"formbox\" style=\"min-height: 50px;height:auto;\"><input type=\"datetime-local\" id=\"newDateInput\" value=\"" + $("#dateTimeOfCurrentEvent").html() + "\" /></div>";
    form += "</div></form>";
    return new Promise(function(resolve, reject) {
        customDialog("New Start time", form, function() {
            var d = createDate($("#newDateInput").val());
            resolve(d ? d.getTime() / 1000 : 0);
        });
    });
}

function copyEvent(data, status, jqxhr, a) {
    setTimeout(function() {
        customDialog("Event copy", data)
    }, 500);
}

function fileDownloader(data, status, jqxhr, a) {
    if (data.length == 3) {
        alertErrorMsg(data);
        return;
    }
    data = data.split('/');
    data[data.length-1] = '1'+data[data.length-1];
    data = data.join('/');
    window.open(data);
}

addFunction(function() {
    if (window.location.pathname.split("/")[1] != 'admin') {
        return true;
    }
    if ($("#emailList").html().length > 0) {
        names = $("#emailList").html().split("; ");
    } else {
        names = [];
    }
    console.log(names);
    window.contactViewerEmailList = [];
    $.each(names, function(i, el) {
        if ($.inArray(el, window.contactViewerEmailList) === -1) window.contactViewerEmailList.push(el);
    });
    console.log(window.contactViewerEmailList);
    htm = '';
    for (var i = 0; i < window.contactViewerEmailList.length; i++) {
        htm += window.contactViewerEmailList[i] + '; ';
    }
    htm = htm.substr(0, htm.length - 2);

    $("#emailList").html(htm);
});

function contactViewerAddToEmailList_prepush(a, data) {
    if (window.contactViewerEmailList.indexOf(data) == -1) {
        window.contactViewerEmailList.push(data);
    } else {
        window.contactViewerEmailList.splice(window.contactViewerEmailList.indexOf(data), 1);
    }

    htm = '';

    for (var i = 0; i < window.contactViewerEmailList.length; i++) {
        htm += window.contactViewerEmailList[i] + '; ';
    }
    htm = htm.substr(0, htm.length - 2);

    $("#emailList").html(htm);

    $(a).html($(a).html() == 'Add' ? 'Remove' : 'Add');

    return false;
}

function addUserToAttendance_prepush(a, data) {
    var promise = memberSelect();

    promise.then(function(data) {
        if (data != undefined && data.length != 0) {
            for (var i = 0; i < data.length; i++) {
                $clone = $(a).parent().parent().prev().clone();
                $clone.attr("id", "");
                $clone.insertBefore($(a).parent().parent());
                $(a).parent().parent().prev().find("input").val(data[i].id);
            }
        }
    });

    return false;
}

function removeAttendanceUserMultiAdd_prepush(a, data) {
    $(a).parent().parent().parent().remove();

    return false;
}

function deleteAttendanceRecord(data, status, jqxhr, a) {
    getHtml();
}

function asyncFormSelectFilesInsteadOfUpload_prepush(a, name) {
    var promise = fileSelect();

    promise.then(function(data) {
        if (data != undefined && data.length != 0) {
            for (var i = 0; i < data.length; i++) {
                $("<input name=\"" + name + "[]\" value=\"" + data[i] + "\" type=\"hidden\" />").appendTo($(a).parent().parent().parent());
            }
        }
    });

    return false;
}

function memberPermissionsAddAUser_prepush(a, data) {

    memberSelect(function(data) {
        if (data[0] != undefined) {
            data = data[0];
            $clone = $("#templateAdder").clone();
            $clone.attr("id", "");
            $clone.insertBefore($(a).parent().parent());
            $clone.find("input").attr("name", data.id);
            $($clone.find('.formbox')[0]).find("label").text(data.name);
            $($clone.find('.formbox')[1]).find("input").each(function(index) {
                $(this).attr("id", data.id + '' + index);
            });
            $($clone.find('.formbox')[1]).find("label").each(function(index) {
                $(this).attr("for", data.id + '' + index);
            });
            $("<input name=\"capids[]]\" value=\"" + data.id + "\" type=\"hidden\" />").appendTo($(a).parent().parent().parent());
        }
    }, false);

    return false;
}

function memberPermissionsRemoveAUser(data, status, jqxhr, a) {
    $(a).parent().parent().prev().remove()
    $(a).parent().parent().remove();
    customDialog("Permissions", "User removed from access list");
}

function selectCAPIDForEventForm_prepush(a, retclass) {
    console.log(retclass);
    memberSelect(function(data) {
        if (data[0] != undefined) {
            id = data[0].id;
            $(".capPOC" + retclass).val(id);
            $.ajax('/' + HOST_SUB_DIR + 'contactfor', {
                'method': 'POST',
                data: 'method=PUT&ajax=true&data=' + id,
                success: function(data, status, jqxhr) {
                    data = JSON.parse(data);
                    $('.capPOCPHONE' + retclass).val(data.phone);
                    $('.capPOCEMAIL' + retclass).val(data.email);
                }
            });
        }
    }, false);

    return false;
}

function reload() {
    getHtml();
}

function fileDeleted (data) {
    getHtml();
    customDialog("File Status", data);
}

function dateRangeUpdate() {
    var options = {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit"
    };
    html = "";
    html += (new Date(+this.valueLow * 1000)).toLocaleTimeString("en-US", options);
    html += " - "
    html += (new Date(+this.valueHigh * 1000)).toLocaleTimeString("en-US", options);
    $("output[for=" + $(this).attr("id") + "]").html(html);
}

function setCookiesJSONString(data) {
    setCookies(JSON.parse(data));
}

function su(data) {
    localStorage.setItem("LOGIN_DETAILS", JSON.parse(data));
    setTimeout(function() {
        getHtml("/admin");
    }, 500);
}

function su_prepush() {
    return new Promise(function(resolve, reject) {
        memberSelect(function(data) {
            if (typeof data[0] != 'undefined') {
                resolve(data[0].id);
            }
        }, false);
    });
}

function teamCreateAddUser_prepush(a, retclass) {
    memberSelect(function(data) {
        if (data[0] != undefined) {
            id = data[0].id;
            $("." + retclass).val(id);
        }
    }, false);

    return false;
}

function addUserToTeam_prepush(a, data) {
    var promise = memberSelect();

    promise.then(function(data) {
        if (data != undefined && data.length != 0) {
            for (var i = 0; i < data.length; i++) {
                $clone = $(a).parent().parent().prev().prev().clone();
                $clone.attr("id", "");
                $clone2 = $(a).parent().parent().prev().clone();
                $clone2.attr("id", "");
                $clone.insertBefore($(a).parent().parent());
                $clone2.insertBefore($(a).parent().parent());
                $(a).parent().parent().prev().prev().find("input").val(data[i].id);
                $(a).parent().parent().prev().find("input").val("");
            }
        }
    });

    return false;
}

function removeTeamUserMultiAdd_prepush(a, data) {
    $(a).parent().parent().parent().next().remove();
    $(a).parent().parent().parent().remove();

    return false;
}

function alertReload (data) {
    customDialog(null, data);
    getHtml();
}

function attendanceListingPopup (data) {
    customDialog("Short Attendance Listing", data);
}

function signupPopup (data) {
    customDialog("Signup Listing", data);
}

