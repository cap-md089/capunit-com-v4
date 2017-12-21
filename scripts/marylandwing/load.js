function getXHR() {
    return window.ActiveXObject ? new ActiveXObject("MSXML2.XMLHTTP.3.0") : new XMLHttpRequest();
}

(function(window) {
    funcs = [];
    window.addFunction = function(func) {
        funcs.push(func);
    };
    window.executeFunctions = function() {
        funcs.forEach(Function.prototype.call, Function.prototype.call);
    };
    window.htmlDecode = function(str) {
        return $("<textarea />").html(str).text();
    };
})(window);

window.parseReturn = function (text) {
    var ret = {};
    var divider = '--COMPLEXITYPERPLEXITYSTYLIZEDWAYLAYING';
    text = text.split("\n");
    var ct = '';
    for (var i = 0; i < text.length; i++) {
        if (text[i] == divider) {
            ct = text[i+1].split("Name: ")[1];
            console.log(text[i+1]);
            i++;
            continue;
        } else if (ct !== '' && text[i] !== '') {
            if (!ret[ct]) ret[ct] = '';
            ret[ct] += text[i]+"\n";
        }
    }
    return ret;
}

var xhr1 = getXHR();
var xhr2 = getXHR();

var bload = false;
var hload = false;
var jload = false;

var loaded = [];

function checkJ() {
    jload = !!window.jQuery && loaded.length == 8;
    if (!jload) {
        setTimeout(checkJ, 50);
        return;
    }
    document.body.style.display = "block";
    for (var i in loaded) {
        loaded[i].apply(window);
    }
    if (JSON.parse(localStorage.getItem("LOGIN_DETAILS") || '{}').success == true) {
        $(".signedout").addClass("signedin").removeClass("signedout");
    }
    getHtml();
}

xhr1.addEventListener("load", function() {
    document.head.innerHTML += parseReturn(this.responseText).MainBody;
    var scripts = document.head.getElementsByTagName("script");
    var styles = document.head.getElementsByTagName("link");

    ns = [];

    for (var i = 0, slen = scripts.length; i < slen; i++) {
        n = document.createElement("script");
        n.src = scripts[i].src + "?v=" + parseInt(Math.random() * 10).toString();
        ns.push(n);
    }

    for (var i = 0, slen = styles.length; i < slen; i++) {
        n = document.createElement("link");
        if (styles[i].rel !== 'shortcut icon') {
            n.href = styles[i].href + "?v=" + parseInt(Math.random() * 10).toString();
        } else {
            n.href = styles[i].href + "&v=" + parseInt(Math.random() * 10).toString();    
        }
        n.rel = styles[i].rel;
        ns.push(n);
    }

    for (var i = scripts.length - 1; i >= 0; i--) {
        document.head.removeChild(scripts[i]);
    }
    for (var i = styles.length - 1; i >= 0; i--) {
        document.head.removeChild(styles[i]);
    }


    for (var i = 0; i < ns.length; i++) {
        document.head.appendChild(ns[i]);
    }

    hload = true;
    if (bload && hload) {
        checkJ();
    }
});
xhr2.addEventListener("load", function() {
    document.body.innerHTML = parseReturn(this.responseText).MainBody;
    bload = true;
    if (bload && hload) {
        checkJ();
    }
});

xhr1.open("GET", "/<?php echo HOST_SUB_DIR; ?>gettemplates/head?ajax=true");
xhr2.open("GET", "/<?php echo HOST_SUB_DIR; ?>gettemplates/body?ajax=true");

xhr1.send();
xhr2.send();

var HOST_SUB_DIR = '<?php echo HOST_SUB_DIR; ?>';
var CSS_THEME = '<?php echo Registry::Get("Styling.Preset"); ?>';