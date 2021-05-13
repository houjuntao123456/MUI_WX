window.onload = function () {
    //自动加载机构
    onload_select_innerHTML()
    hidden_title();
}

// 进来加载的方法

// 下拉框初始赋值
function onload_select_innerHTML() {
    // 给组织加载多余
    var option_select_organization = document.createElement("option");
    option_select_organization.value = "";
    option_select_organization.innerHTML = "不限机构";
    document.getElementById("all_organization").appendChild(option_select_organization);
    // 门店加载多余 
    var option_select_store = document.createElement("option");
    option_select_store.value = "";
    option_select_store.innerHTML = "不限门店";
    document.getElementById("all_stores").appendChild(option_select_store);
    // 选择员工加载多余
    var option_select_clerk = document.createElement("option");
    option_select_clerk.value = "";
    option_select_clerk.innerHTML = "不限店员";
    document.getElementById("all_clerk").appendChild(option_select_clerk);
    onload_select();
}


// 下拉发生改变
// 机构 all_organization
function onload_select() {
    mui.post('/index.php/UserlistOrg/', {
    }, function (data) {
        //服务器返回响应，根据响应结果，分析是否登录成功；
        // console.log(data);
        if (data.code == 200) {
            for (i = 0; i < data.data.length; i++) {
                var option = document.createElement('option');
                option.value = data.data[i].code;
                option.innerHTML = data.data[i].name
                document.getElementById("all_organization").appendChild(option);
            }
        }
    }, 'json');
}
// 门店 all_stores
function clerkChange() {
    mui.post('/index.php/UserlistStore/', {
        code: all_organization.options[all_organization.selectedIndex].value
    }, function (data) {
        //服务器返回响应，根据响应结果，分析是否登录成功；
        // console.log(data);
        // console.log(data.data.length);
        if (data.code == 200) {
            document.getElementById("all_stores").innerHTML = '<option value="">选择门店</option>'
            for (i = 0; i < data.data.length; i++) {
                var option = document.createElement('option');
                option.value = data.data[i].code;
                option.innerHTML = data.data[i].name;
                document.getElementById("all_stores").appendChild(option);
            }
        }
    }, 'json');
}
// 店员 all_clerk
function gradeChange() {
    mui.post('/index.php/UserlistStaff/', {
        code: all_stores.options[all_stores.selectedIndex].value
    }, function (data) {
        //服务器返回响应，根据响应结果，分析是否登录成功；
        // console.log(data);
        // console.log(data.data.length);
        if (data.code == 200) {
            document.getElementById("all_clerk").innerHTML = '<option value="">选择店员</option>'
            for (i = 0; i < data.data.length; i++) {
                var option = document.createElement('option');
                option.value = data.data[i].code;
                option.innerHTML = data.data[i].name;
                document.getElementById("all_clerk").appendChild(option);
            }
        }
    }, 'json');
}
//切换 传值  //今天生日  默认今天生日 30天未消费
var sli = document.getElementById('segmentedControli');
var sls = document.getElementById('segmentedControls');
var sld = document.getElementById('segmentedControld');
var time = 'today';
var consum = 'unconsumed';
//今天 -----------------------------------------------------------------
var j_today = document.getElementById('j_today');
j_today.addEventListener('tap', function () {
    //console.log(sli.style.display+','+sls.style.display+','+sld.style.display);
    sli.style.display = 'block';
    sls.style.display = 'none';
    sld.style.display = 'none';
    j_today.classList.add('border-b');
    z_lately.classList.remove('border-b');
    y_thisMonth.classList.remove('border-b');
    document.getElementById('jr_unconsumed').classList.add('mui-active');
    document.getElementById('jr_consumption').classList.remove('mui-active');
    document.getElementById('jr_return_visit').classList.remove('mui-active');
    document.getElementById('jr_noreturn_visit').classList.remove('mui-active');
    pulls('today', 'unconsumed');
});
//未消费
document.getElementById('jr_unconsumed').addEventListener('tap', function () {
    pulls(time, 'unconsumed');
});
//有消费 
document.getElementById('jr_consumption').addEventListener('tap', function () {
    pulls(time, 'consumption');
});
//已回访
document.getElementById('jr_return_visit').addEventListener('tap', function () {
    pulls(time, 'returnVisit');
});
//未回访
document.getElementById('jr_noreturn_visit').addEventListener('tap', function () {
    pulls(time, 'noReturnVisit');
});
//近日 -----------------------------------------------------------------
var z_lately = document.getElementById('z_lately');
z_lately.addEventListener('tap', function () {
    sli.style.display = 'none';
    sls.style.display = 'block';
    sld.style.display = 'none';
    j_today.classList.remove('border-b');
    z_lately.classList.add('border-b');
    y_thisMonth.classList.remove('border-b');
    document.getElementById('zj_uncon').classList.add('mui-active');
    document.getElementById('zj_consu').classList.remove('mui-active');
    document.getElementById('zj_retur').classList.remove('mui-active');
    document.getElementById('zj_nore').classList.remove('mui-active');
    pulls('lately', 'unconsumed');
});
//未消费
document.getElementById('zj_uncon').addEventListener('tap', function () {
    pulls(time, 'unconsumed');
});
//有消费 
document.getElementById('zj_consu').addEventListener('tap', function () {
    pulls(time, 'consumption');
});
//已回访
document.getElementById('zj_retur').addEventListener('tap', function () {
    pulls(time, 'returnVisit');
});
//未回访
document.getElementById('zj_nore').addEventListener('tap', function () {
    pulls(time, 'noReturnVisit');
});
//30天 -----------------------------------------------------------------
var y_thisMonth = document.getElementById('y_thisMonth');
y_thisMonth.addEventListener('tap', function () {
    sli.style.display = 'none';
    sls.style.display = 'none';
    sld.style.display = 'block';
    j_today.classList.remove('border-b');
    z_lately.classList.remove('border-b');
    y_thisMonth.classList.add('border-b');
    document.getElementById('three_unconsumed3').classList.add('mui-active');
    document.getElementById('three_consumption3').classList.remove('mui-active');
    document.getElementById('return_visit3').classList.remove('mui-active');
    document.getElementById('noreturn_visit3').classList.remove('mui-active');
    pulls('thisMonth', 'unconsumed');
});
//未消费
document.getElementById('three_unconsumed3').addEventListener('tap', function () {
    pulls(time, 'unconsumed');
});
//有消费 
document.getElementById('three_consumption3').addEventListener('tap', function () {
    pulls(time, 'consumption');
});
//已回访
document.getElementById('return_visit3').addEventListener('tap', function () {
    pulls(time, 'returnVisit');
});
//未回访
document.getElementById('noreturn_visit3').addEventListener('tap', function () {
    pulls(time, 'noReturnVisit');
});

//点击查询 -----------------------------------------------------------------
document.getElementById("query").addEventListener('tap', function () {
    //今日近日本月模块切换
    sli.style.display = 'block';
    sls.style.display = 'none';
    sld.style.display = 'none';
    //下划线切换
    j_today.classList.add('border-b');
    z_lately.classList.remove('border-b');
    y_thisMonth.classList.remove('border-b');
    //字体颜色切换
    j_today.classList.add('mui-active');
    z_lately.classList.remove('mui-active');
    y_thisMonth.classList.remove('mui-active');
    //字体颜色背景块切换
    document.getElementById('jr_unconsumed').classList.add('mui-active');
    document.getElementById('jr_consumption').classList.remove('mui-active');
    document.getElementById('jr_return_visit').classList.remove('mui-active');
    document.getElementById('jr_noreturn_visit').classList.remove('mui-active');

    orgs = all_organization.options[all_organization.selectedIndex].value;
    stores = all_stores.options[all_stores.selectedIndex].value;
    staffs = all_clerk.options[all_clerk.selectedIndex].value;
    all_tying = document.getElementById("all_tying").options[document.getElementById("all_tying").selectedIndex].value;
    mui('.mui-off-canvas-wrap').offCanvas().toggle();
    pulls('today', 'unconsumed');
});


//传值 下拉上拉刷新
function pulls(t, c) {
    document.getElementById('datalist').innerHTML = "";
    count = 0;
    time = t;
    consum = c;
    mui('#pullrefresh').pullRefresh().refresh(true);
    pullupRefresh();

    
}
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

/**
 * 下拉刷新具体业务实现
 */
function pulldownRefresh() {
}
/**
 * 上拉加载具体业务实现
 */
// 重新开启加载
// mui('#pullrefresh').pullRefresh().refresh(true);
var orgs = "";
var stores = "";
var staffs = "";
var all_tying ="true";
// 分页计数
var count = 0;
// 分的条数 
var limit_num = 5;
function pullupRefresh() {
    console.log(all_tying)
    load.start();
    // console.log(orgs + ',' + stores + ',' + staffs);
    mui.post('/index.php/webBirthdayDiscountIndex/', {
        org: orgs,
        store: stores,
        staff: staffs,
        type: time,//今日
        vtype: consum,//未消费
        page: ++count,
        limit: limit_num
    }, function (data) {
        // console.log(data);
        if (data.code == 200) {
            //console.log(data);
            //console.log(data.data.data[i].level_code);
            //console.log(data.data.data);
            //服务器返回响应，根据响应结果，分析是否登录成功；
            // console.log(data.count.list.count / limit_num);
            setTimeout(function () {
                mui('#pullrefresh').pullRefresh().endPullupToRefresh((count > (data.data.count / limit_num))); //参数为true代表没有更多数据了。
                if (data.data.count == 0) {
                    var li = document.createElement('li');
                    li.innerHTML = '<h3 style="font-size: 18px;color: #929292;font-weight: normal;">暂无数据</h3>';
                    li.style = 'text-align:center;padding-top:10px;padding-bottom:10px;'
                    document.getElementById("datalist").appendChild(li);
                    document.getElementById("menu").classList.add("dis");
                } else {
                    for (i = 0; i < data.data.data.length; i++) {
                        document.getElementById("menu").classList.remove("dis");
                        let wexin_title = "";
                        // 进行判断,如果为空执行默认路径的图片

                        // if (data.data.list.data[i].vvip == 1) {
                        // wexin_title = '<span class="mui-icon mui-icon-weixin" style="position: absolute;top:-10px;right:0px;;z-index: 999;color: #80D640;"></span>';
                        // }
                        // 进行判断,如果为空执行默认路径的图片
                        if (data.data.data[i].img == "") {
                            data.data.data[i].img = "../images/suoke.jpg";
                        }
                        if (data.data.data[i].username == "" || data.data.data[i].username == null || data.data.data[i].username == undefined) {
                            data.data.data[i].username = '&nbsp;';
                        }
                        if (data.data.data[i].birthday == "" || data.data.data[i].birthday == null || data.data.data[i].birthday == undefined) {
                            data.data.data[i].birthday = "";
                        }
                        if (data.data.data[i].total_consumption == "" || data.data.data[i].total_consumption == null || data.data.data[i].total_consumption == undefined) {
                            data.data.data[i].total_consumption = 0;
                        }
                        if (data.data.data[i].consumption_times == "" || data.data.data[i].consumption_times == null || data.data.data[i].consumption_times == undefined) {
                            data.data.data[i].consumption_times = 0;
                        }
                        if (data.data.data[i].consumption_number == "" || data.data.data[i].consumption_number == undefined || data.data.data[i].consumption_number == null) {
                            data.data.data[i].consumption_number = 0;
                        }

                        if (data.data.data[i].visit_g == null || data.data.data[i].visit_g == undefined) {
                            data.data.data[i].visit_g = "未回访";
                        } else if (data.data.data[i].visit_g != "未回访") {
                            data.data.data[i].visit_g = parseInt(data.data.data[i].visit_g.replace(/,/g, ""));
                            if (data.data.data[i].visit_g > 365) {
                                data.data.data[i].visit_g = "未回访365+天";
                            } else {
                                data.data.data[i].visit_g = "未回访" + data.data.data[i].visit_g + "天";
                            }
                        }

                        if (data.data.data[i].rfm_days != "未消费") {
                            data.data.data[i].rfm_days = "未消费" + data.data.data[i].rfm_days + "天";
                        } else if (data.data.data[i].rfm_days == null || data.data.data[i].rfm_days == undefined) {
                            data.data.data[i].rfm_days = "未消费";
                        }
                        //rfm_days 未消费天数   consumption_times 次数  total_consumption 金额
                            var li = document.createElement('li');
                            li.className = 'mui-table-view-cell';
                            li.innerHTML = '<div class="list_left_one xuanze all_quanxuan" code="' + data.data.data[i].code + '"phone="' + data.data.data[i].phone + '">'
                                + '<p style="position: relative;">' + wexin_title +'<img src="' + data.data.data[i].img + '" alt="暂无图片" style="width:100%;border-radius:50%;border: 1px solid #eee;" ></p></div>'
                                + '<div class="list_left_two">'
                                + '<h4>' + data.data.data[i].username + ' <span style="margin-left: 5px;color:orange;">' + data.data.data[i].birthday + '</span></h4>'
                                + '<p>¥ ' + data.data.data[i].total_consumption + ' / ' + data.data.data[i].consumption_times + '次 / ' + data.data.data[i].consumption_number + '件</p>'
                                + '<p>' + data.data.data[i].rfm_days + ' / ' + data.data.data[i].visit_g + '</p>'
                                + '</div>'
                                + '<div class="list_left_three">'
                                + '<button style="margin-top: 0" name="' + data.data.data[i].code + '"onclick="member(this)">会员档案</button>'
                                + '<button style="margin-top: 5px" code="' + data.data.data[i].code + '" level_code="' + data.data.data[i].level_code + '" username="' + data.data.data[i].username + '" onclick="moban(this)">跟进模板</button>'
                                // + '<button name="' + data.data.data[i].code + '" onclick="tag(this)">会员标签</button>'
                                + '</div>'
                            document.getElementById("datalist").appendChild(li);
                   
                    }
                }
                load.stop();
            }, 1000);
        }
    }, 'json');
}
//点击按钮,将参数传递到另一个页面
function member(e) {
    location.href = '../homepage/member_data.html?' + 'code=' + e.getAttribute("name");
}
function moban(e) {
    localStorage.setItem("hd_username", e.getAttribute("username"));
    location.href = '../homepage/b_chart_moban.html?' + 'code=' + e.getAttribute("code") + "level_code*" + e.getAttribute("level_code");
}


