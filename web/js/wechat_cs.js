// 微信认证


var locaton = window.location.href;
// console.log(locaton);

$.cookie("company", decodeURIComponent(getQueryVariable("company")), {
    path: "/"
});
$.cookie('locaton', locaton, {
    path: '/'
});
var reg_suokeduos = new RegExp("^[0-9]*$");
(function () {
    $.cookie("openId","o9osQwUghKSMhf1ZGF3oclpiEvtc")
})();

// 获取公司名称
function getQueryVariable(variable) {
    var query = window.location.search.substring(1);
    var vars = query.split("&");
    for (var i = 0; i < vars.length; i++) {
        var pair = vars[i].split("=");
        if (pair[0] == variable) {
            return pair[1];
        }
    }
    return (false);
}