function getXHR() {
    return window.ActiveXObject ? new ActiveXObject("MSXML2.XMLHTTP.3.0") : new XMLHttpRequest();
}

var xhr1 = getXHR();
var xhr2 = getXHR();

var bload = false;
var hload = false;
var jload = false;

var loaded = [];

function checkJ() {
    jload = !!window.jQuery && loaded.length == 8 && !!window.twttr && !window.FB;
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
    document.head.innerHTML += this.responseText;
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
        n.href = styles[i].href + "?v=" + parseInt(Math.random() * 10).toString();
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
    document.body.innerHTML = this.responseText;
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