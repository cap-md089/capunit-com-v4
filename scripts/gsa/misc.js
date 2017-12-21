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