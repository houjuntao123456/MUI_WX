// mui.init({
//     pullRefresh: {
//         container: '#pullrefresh',
//         down: {
//             callback: pulldownRefresh
//         },
//         up: {
//             contentrefresh: '正在加载...',
//             auto: true,//可选,默认false.首次加载自动上拉刷新一次
//             callback: pullupRefresh
//         }
//     }
// });


/**
   * 下拉刷新具体业务实现
   */
// function pulldownRefresh() {
// }
/**
 * 上拉加载具体业务实现
 */
// 重新开启加载
// mui('#pullrefresh').pullRefresh().refresh(true);
// 分页计数
var count = 0;
// 分的条数
var limit_num = 10;
var url = "";
var idd = "";
var code = "";
var bar_code = "";
var rfm_type = "";

function opop(u, i, cc, bcode, cargo_type) {
    //console.log("调用")
    url = u;
    idd = i;
    code = cc;
    bar_code = bcode;
    rfm_type = cargo_type;
    // console.log(url)
    // pullupRefresh();
    mui.init({
        pullRefresh: {
            container: '#pullrefresh',
            // down: {
            //     callback: pulldownRefresh
            // },
            up: {
                contentrefresh: '正在加载...',
                auto: true,//可选,默认false.首次加载自动上拉刷新一次
                callback: pullupRefresh
            }
        }
    });
}
function pullupRefresh() {
    // console.log(url)
    // console.log(idd)
    mui.post(url, {
        id: idd,
        store_code: code,
        bar_code:bar_code,
        rfm_type:rfm_type,
        page: ++count,
        limit: limit_num
    }, function (data) {
        // console.log('img' + data.data.data[0].img);
        // console.log('农历' + data.data.data[0].calendar);
        //服务器返回响应，根据响应结果，分析是否登录成功；
        // console.log(data.data.count / limit_num);
        if (data.code == 200) {
            // if (data.data.data.length==0){
            //     mui.toast('暂无数据', { duration: 'long', type: 'div' }) ;
            // }else{
            setTimeout(function () {
                // console.log(data.data.count);
                // console.log(count > (data.data.count / limit_num));
                // console.log(count);
                mui('#pullrefresh').pullRefresh().endPullupToRefresh((count > (data.data.count / limit_num))); //参数为true代表没有更多数据了。
                var table = document.getElementById("member_data");
                for (i = 0; i < data.data.data.length; i++) {
                    // 进行判断,如果为空执行默认路径的图片
                    if (data.data.data[i].img == "") {
                        data.data.data[i].img = "../images/suoke.jpg";
                    }
                    //console.log(data.data.data[i].r_days);
                    if (data.data.data[i].username == "" || data.data.data[i].username == null || data.data.data[i].username == undefined) {
                        data.data.data[i].username = '&nbsp;';
                    }
                    if (data.data.data[i].birthday_g == "" || data.data.data[i].birthday_g == null || data.data.data[i].birthday_g == undefined) {
                        data.data.data[i].birthday_g = '&nbsp;';
                    }
                    if (data.data.data[i].total_consumption == "" || data.data.data[i].total_consumption == undefined || data.data.data[i].total_consumption == null) {
                        data.data.data[i].total_consumption = 0;
                    }
                    if (data.data.data[i].consumption_times == "" || data.data.data[i].consumption_times == undefined || data.data.data[i].consumption_times == null) {
                        data.data.data[i].consumption_times = 0;
                    }
                    if (data.data.data[i].rfm_days == "" || data.data.data[i].rfm_days == undefined || data.data.data[i].rfm_days == null) {
                        data.data.data[i].rfm_days = "未消费";
                    } else if (data.data.data[i].rfm_days != "未消费") {
                        data.data.data[i].rfm_days = "未消费" + data.data.data[i].rfm_days + "天";
                    }
                    if (data.data.data[i].consumption_number == "" || data.data.data[i].consumption_number == undefined || data.data.data[i].consumption_number == null) {
                        data.data.data[i].consumption_number = 0
                    }
                    if (data.data.data[i].phone_g == "" || data.data.data[i].phone_g == undefined || data.data.data[i].phone_g == null) {
                        data.data.data[i].phone_g = "&nbsp;";
                    }
                    // ----卡号, ----姓名, --手机号, --------, --门店, ------生日, ------消费次数, ----------金额, ---------图片, ------------, --未消费天数
                    //'code, username, phone, rfm_days, store_code, birthday, consumption_times, total_consumption, img, final_purchases, r_days'
                    var li = document.createElement('li');
                    li.className = 'mui-table-view-cell';
                    li.style = 'padding-left:5%;padding-right:5%;';
                    li.innerHTML = '<div class="mui-slider-cell">'
                        + ' <div class="oa-contact-cell mui-table">'

                        + '<input type="checkbox" id="sk' + data.data.data[i].code + '" code="' + data.data.data[i].code + '" phone="' + data.data.data[i].phone_g + '" onclick="checked_box(this)"></input>'
                        + ' <label for="sk' + data.data.data[i].code + '" class="mui-table-cell" style="width:80px;">'
                        + ' <img src="' + data.data.data[i].img + '" style="width:100%;border: 1px solid #eee;border-radius:50%;" />'
                        + '</label>'

                        + '<div class="oa-contact-content mui-table-cell" style="padding-top:10px;padding-left: 12px;">'
                        + ' <div class="mui-clearfix" style="text-align:left;line-height: 28px;width: 80%;">'
                        + '  <h4 class="oa-contact-name">' + data.data.data[i].username + '</h4>&nbsp;'
                        + ' <span class="oa-contact-position mui-h5">' + data.data.data[i].birthday_g + '</span>'
                        + '</div>'
                        + '<p>'
                        + '<span style="font-size:16px;">¥</span>' + data.data.data[i].total_consumption
                        + '<span style="font-size:16px;">&nbsp;/&nbsp;</span>' + data.data.data[i].consumption_times + '<span>次 / ' + data.data.data[i].consumption_number + '件</span>'
                        + '<button style="float: right;" onclick="member(this)" name="' + data.data.data[i].code + '">会员档案</button>'
                        + '</p>'
                        + '<p>' + data.data.data[i].rfm_days + '</p>'
                        + '<p><span>手机号:</span>' + data.data.data[i].phone_g + '</p>'
                        + '</div>'
                        + '</div>'
                        + '</div>'
                    table.appendChild(li);
                }
            }, 1000);
        }
        // }
    }, 'json'
    );
}
mui('.mui-scroll-wrapper').scroll({
    indicators: false, //是否显示滚动条
});

// 发送富信,短信,视图,卡劵

function checked_box(e) {
    if (e.checked == true) {
        // 添加数组
        add_array(e.getAttribute("phone"), e.getAttribute("code"));

    } else {
        // 删除数组
        remove_array(e.getAttribute("phone"), e.getAttribute("code"))

    }
}

var shuzu = [];
var shuzu_code = [];
// 向数组添加
function add_array(phone, code) {
    shuzu.push(phone);
    shuzu_code.push(code)
    // console.log(shuzu);
    // console.log(shuzu_code);
    jsNum()

}
// 向数组删除
function remove_array(phone, code) {
    for (i = 0; i < shuzu.length; i++) {
        if (shuzu[i] == phone) {
            shuzu.splice(i, 1);
        }
    }
    for (i = 0; i < shuzu_code.length; i++) {
        if (shuzu_code[i] == code) {
            shuzu_code.splice(i, 1);
        }
    }
    jsNum();
    // console.log(shuzu);
    // console.log(shuzu_code);
}
// 计算数量显示
function jsNum() {
    if (shuzu.length == 0) {
        document.getElementById("nums").classList.add("dis");
    } else {
        document.getElementById("nums").classList.remove("dis");
        document.getElementById("nums").innerHTML = shuzu.length;
    }
}

// 确定群发
function cluste_hai() {
    localStorage.setItem("route", '/index.php/webCustomerServiceShu/');
    mui.confirm('', '短信,视图,微信,卡劵', ['短信', '视图', '微信', '卡劵'], function (e) {
        // e.index == 1
        if (e.index == 1) {
            // 视图
            localStorage.setItem("array_shuzu_control", 'false');
            if (shuzu.length != 0) {
                localStorage.setItem("array_shuzu", shuzu);
                location.href = '../homepage/rich_letter_view.html';
            } else {
                mui.toast('请点击头像勾选需要发送视图的会员!!', {
                    duration: 'long',
                    type: 'div'
                })
            }

        } else if (e.index == 2) {
            // 微信
            // 控制短信页面选择全部会员 false(不是会员全选)
            localStorage.setItem("array_shuzu_control", 'false');
            if (shuzu.length != 0) {
                localStorage.setItem("array_shuzu", shuzu_code);
                location.href = '../homepage/short_weixin_group.html';
            } else {
                mui.toast('请点击头像勾选需要发送微信的会员!!', {
                    duration: 'long',
                    type: 'div'
                })
            }
        } else if (e.index == 3) {
            // 控制页面选择全部会员 false(不是会员全选)
            localStorage.setItem("array_shuzu_control", 'false');
            if (shuzu.length != 0) {
                localStorage.setItem("array_shuzu", shuzu_code);
                location.href = '../homepage/group_card_coupon.html';
            } else {
                mui.toast('请点击头像勾选需要发送卡劵的会员!!', {
                    duration: 'long',
                    type: 'div'
                })
            }
        } else {
            // 控制短信页面选择全部会员 false(不是会员全选)
            localStorage.setItem("array_shuzu_control", 'false');
            if (shuzu.length != 0) {
                localStorage.setItem("array_shuzu", shuzu);
                location.href = '../homepage/short_message.html?type=true';
            } else {
                mui.toast('请点击头像勾选需要发送短信的会员!!', {
                    duration: 'long',
                    type: 'div'
                })
            }
        }
    })
}