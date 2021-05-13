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
    let newCompany = $.cookie("company");
    let strs = newCompany.substring(newCompany.length - 4);

    if (newCompany == "ic") {
        let openId = $.cookie('suoke_' + decodeURIComponent(getQueryVariable("company")) + '_wechart_openid');
        let nickname = $.cookie('suoke_nike_name_' + decodeURIComponent(getQueryVariable("company")));
        let headImgUrl = $.cookie('suoke_head_image_' + decodeURIComponent(getQueryVariable("company")));

        //openId = 'o9osQwUghKSMhf1ZGF3oclpiEvtc';
        if ((openId == null) || (openId == "") || (openId == undefined) || (openId == "undefined")) {
            location.href = "https://wechat.suokeduo.com/wechat/index?company=" + decodeURIComponent(getQueryVariable("company")) + "&redirect_url=" + locaton + '&company= ' +
                decodeURIComponent(getQueryVariable("company"));

            // Location.href ='https://wechat.suokeduo.com/wechat/index?company=suokeduo2020&redirect_url=https://wx.suokeduo.com/member/bonding_card.html?company=suokeduo2020'
        } else {
            $.cookie("openId", openId, { path: '/', expires: 1 });
            $.cookie("nickname", nickname, { path: '/', expires: 1 });
            $.cookie("headImgUrl", headImgUrl, { path: '/', expires: 1 });
        }
    } else {
        if (reg_suokeduos.test(strs)) {
            // 后四位是数字
            let openId = $.cookie('suoke_' + decodeURIComponent(getQueryVariable("company")) + '_wechart_openid');
            let nickname = $.cookie('suoke_nike_name_' + decodeURIComponent(getQueryVariable("company")));
            let headImgUrl = $.cookie('suoke_head_image_' + decodeURIComponent(getQueryVariable("company")));

            //openId = 'o9osQwUghKSMhf1ZGF3oclpiEvtc';
            if ((openId == null) || (openId == "") || (openId == undefined) || (openId == "undefined")) {
                location.href = "https://wechat.suokeduo.com/wechat/index?company=" + decodeURIComponent(getQueryVariable("company")) + "&redirect_url=" + locaton + '&company= ' +
                    decodeURIComponent(getQueryVariable("company"));

                // Location.href ='https://wechat.suokeduo.com/wechat/index?company=suokeduo2020&redirect_url=https://wx.suokeduo.com/member/bonding_card.html?company=suokeduo2020'
            } else {
                $.cookie("openId", openId, { path: '/', expires: 1 });
                $.cookie("nickname", nickname, { path: '/', expires: 1 });
                $.cookie("headImgUrl", headImgUrl, { path: '/', expires: 1 });
            }
        } else {
            // 后四位不是数字
            let openId = $.cookie('myself_openid_' + decodeURIComponent(getQueryVariable("company")));
            let nickname = $.cookie('myself_nick_name_' + decodeURIComponent(getQueryVariable("company")));
            let headImgUrl = $.cookie('myself_head_image_' + decodeURIComponent(getQueryVariable("company")));

            //openId = 'o9osQwUghKSMhf1ZGF3oclpiEvtc';
            if ((openId == null) || (openId == "") || (openId == undefined) || (openId == "undefined")) {
                location.href = "https://wxauth.suokeduo.com/card/snsapi_userinfo/forword?redirect_url=" + locaton + '&company= ' +
                    decodeURIComponent(getQueryVariable("company"));
            } else {
                $.cookie("openId", openId, { path: '/', expires: 1 });
                $.cookie("nickname", nickname, { path: '/', expires: 1 });
                $.cookie("headImgUrl", headImgUrl, { path: '/', expires: 1 });
            }
        }

    }





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