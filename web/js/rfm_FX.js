var outPutData;
var dateTime;
var booksdata;

var Rule_url; //路由
var rfm_type; //控制类型
var type_string;
var json_j = [];
// var y_tltle = "";
// var x_title = "";
// var z_title = ""
// app.title = '环形图';

function rfm_echarts() {
    myChart.off('click');
    if (ttt.innerHTML == "R活跃分析") {
        booksdata = ['流失', '休眠', '睡眠', '沉默', '常规', '活跃'];

        Rule_url = '/index.php/RfmActiveSel/';
        rfm_type = 'r';
        type_string = 'r_type'
        localStorage.setItem("rfm_type", 'R');; //控制群发

    } else if (ttt.innerHTML == "F回头率分析") {

        booksdata = ['无', '超低回头率', '低回头率', '中回头率', '高回头率', '超高回头率'];

        Rule_url = '/index.php/RfmBackSel/';
        rfm_type = 'f';
        localStorage.setItem("rfm_type", 'F');; //控制群发

    } else if (ttt.innerHTML == "M贡献度分析") {
        booksdata = ['无', '超低价值', '低价值', '中价值', '高价值', '超高价值'];

        Rule_url = '/index.php/RfmContributionSel/';
        rfm_type = 'm';
        localStorage.setItem("rfm_type", 'M');; //控制群发

    } else if (ttt.innerHTML == "I忠诚度分析") {
        booksdata = ['无', '超低转介绍', '低转介绍', '中转介绍', '高转介绍', '超高转介绍'];

        Rule_url = '/index.php/RfmLoyaltySel/';
        rfm_type = 'i';
        localStorage.setItem("rfm_type", 'I');; //控制群发

    } else if (ttt.innerHTML == "N高件数分析") {
        booksdata = ['无', '超低件数', '低件数', '中件数', '高件数', '超高件数'];

        Rule_url = '/index.php/RfmNumberSel/';
        rfm_type = 'n';
        localStorage.setItem("rfm_type", 'N');; //控制群发

    } else if (ttt.innerHTML == "P高客单分析") {
        booksdata = ['无', '超低客单价', '低客单价', '中客单价', '高客单价', '超高客单价'];

        Rule_url = '/index.php/RfmPriceSel/';
        rfm_type = 'p';
        localStorage.setItem("rfm_type", 'P');; //控制群发

    } else if (ttt.innerHTML == "A高件单分析") {
        booksdata = ['无', '超低价位', '低价位', '中价位', '高价位', '超高价位'];

        Rule_url = '/index.php/RfmUnivalentSel/';
        rfm_type = 'a';
        localStorage.setItem("rfm_type", 'A');; //控制群发

    } else if (ttt.innerHTML == "J高连带分析") {
        booksdata = ['无', '超低连带率', '低连带率', '中连带率', '高连带率', '超高连带率'];

        Rule_url = '/index.php/RfmJointSel/';
        rfm_type = 'j';
        localStorage.setItem("rfm_type", 'J');; //控制群发

    } else if (ttt.innerHTML == "C高消费分析") {
        booksdata = ['无消费', '超低消费', '低消费', '中消费', '高消费', '超高消费'];

        Rule_url = '/index.php/RfmConsumptionSel/';
        rfm_type = 'c';
        localStorage.setItem("rfm_type", 'C');; //控制群发
    }



    let option = {
        tooltip: {
            trigger: '', //item
            formatter: "{a} <br/>{b}: {c} ({d}%)"
        },
        legend: {
            orient: 'horizontal',
            x: 'left',
            y: 'top',

            data: [booksdata[0], booksdata[1], booksdata[2], booksdata[3], booksdata[4], booksdata[5]]

        },

        series: [
            {
                name: '单位/人数',
                type: 'pie',
                radius: ['90%', '1%'],
                avoidLabelOverlap: false,
                label: {
                    normal: {
                        show: true,
                        position: 'inner',
                        formatter: '{b}\n\n{c}' //\n{d}%
                    },
                    emphasis: {
                        show: true,
                        textStyle: {
                            fontSize: '30',
                            fontWeight: 'bold'
                        }
                    }
                },
                labelLine: {
                    normal: {
                        show: false
                    }
                },
                data: [
                    { value: outPutData[0], name: booksdata[0] },
                    { value: outPutData[1], name: booksdata[1] },
                    { value: outPutData[2], name: booksdata[2] },
                    { value: outPutData[3], name: booksdata[3] },
                    { value: outPutData[4], name: booksdata[4] },
                    { value: outPutData[5], name: booksdata[5] }
                ]
            }
        ]
    };


    // 使用刚指定的配置项和数据显示图表。
    myChart.setOption(option, true);

    //增加监听事件
    function eConsole(param) {
        var mes = '【' + param.type + '】';
        if (typeof param.seriesIndex != 'undefined') {
            // mes += '  seriesIndex : ' + param.seriesIndex;
            // mes += '  dataIndex : ' + param.dataIndex;
            if (param.type == 'click') {

                let percent = param.percent; //百分比
                let value = param.value; //数量
                let type = param.name; //类型
                let centor = '<table style="width: 100%;text-align: center;">'
                    + '<thead><tr><th>类型</th><th>人数</th><th>百分比</th></tr></thead>'
                    + '<tbody><tr><td style="padding-top:5px;font-size:14px;">' + type + '</td><td style="padding-top:5px;font-size:14px;">' + value + '</td><td style="padding-top:5px;font-size:14px;">' + percent + '%</td></tr></tbody></table>'

                prompt2(centor, type);
                return true;
                // this.stopPropagation();
                // alert(peiLenght);// 获取总共给分隔的扇形数
                // for (var i = 0; i < peiLenght; i++) {
                //     everyClick(param, i, option.legend.data[i], data_url[i])
                // }
            }

        }
    }
    myChart.on("click", eConsole);

}



function prompt2(centor, type) {
    mui.confirm('', centor, ['会员', '专场', '群发', '取消'], function (e) {
        // 点击了确定按钮
        if (e.index == 0) {
            // 会员
            for (let i = 0; i < json_j.length; i++) {
                if (json_j[i].type_type == type) {
                    member(json_j[i].id_code, json_j[i].store_code);
                }
            }
        } else if (e.index == 1) {
            //专场
            for (let i = 0; i < json_j.length; i++) {
                if (json_j[i].type_type == type) {
                    follow(json_j[i].id_code, json_j[i].store_code);
                }
            }

        } else if (e.index == 2) {
            //群发
            for (let i = 0; i < json_j.length; i++) {
                if (json_j[i].type_type == type) {
                    information(json_j[i].id_code, json_j[i].store_code);
                }
            }
        }
    })
}


// 添加跟进模板标题
function follow(code, store_code) {
    mui.prompt('', '请添加跟进模板名称', '跟进模板', ['取消', '确定'], function (e) {
        if (e.index == 1) {
            if (e.value != "") {
                mui.post('/index.php/RfmActiveRfmField/', {
                    // text: '【' + e.value + '】' + '<br>' + content_txt
                    title: e.value,
                    id: code,
                    rfm_type: rfm_type,
                    store_code: store_code
                }, function (data) {
                    //服务器返回响应，根据响应结果，分析是否登录成功；
                    if (data.code == 200) {
                        mui.toast('模板 [ ' + e.value + ' ] 已保存', {
                            duration: 'long',
                            type: 'div'
                        })
                    } else if (data.code == 400) {
                        mui.toast(data.msg, {
                            duration: 'long',
                            type: 'div'
                        })
                    }
                }, 'json'
                );


            } else {
                mui.toast('模板不能为空', {
                    duration: 'long',
                    type: 'div'
                })
                return false;
            }

        } else {
            // 取消
        }
    })

}

function men_seach(code,staff) {
    mui.post(Rule_url, {
        search: code,
        staff_code:staff
    }, function (data) {
        json_j = [];
        //服务器返回响应，根据响应结果，分析是否登录成功；
        if (data.code == 200) {
            for (i = 0; i < data.data.count; i++) {
                if (rfm_type == 'r') {
                    let json_s = {
                        type_type: data.data.data[i].r_type,
                        id_code: data.data.data[i].id,
                        store_code: data.data.data[i].store_code
                    }
                    json_j.push(json_s);
                } else if (rfm_type == 'f') {
                    let json_s = {
                        type_type: data.data.data[i].f_type,
                        id_code: data.data.data[i].id,
                        store_code: data.data.data[i].store_code
                    }
                    json_j.push(json_s);
                } else if (rfm_type == 'm') {
                    let json_s = {
                        type_type: data.data.data[i].m_type,
                        id_code: data.data.data[i].id,
                        store_code: data.data.data[i].store_code
                    }
                    json_j.push(json_s);
                }
                else if (rfm_type == 'i') {
                    let json_s = {
                        type_type: data.data.data[i].i_type,
                        id_code: data.data.data[i].id,
                        store_code: data.data.data[i].store_code
                    }
                    json_j.push(json_s);
                }
                else if (rfm_type == 'n') {
                    let json_s = {
                        type_type: data.data.data[i].n_type,
                        id_code: data.data.data[i].id,
                        store_code: data.data.data[i].store_code
                    }
                    json_j.push(json_s);
                }
                else if (rfm_type == 'p') {
                    let json_s = {
                        type_type: data.data.data[i].p_type,
                        id_code: data.data.data[i].id,
                        store_code: data.data.data[i].store_code
                    }
                    json_j.push(json_s);
                }
                else if (rfm_type == 'a') {
                    let json_s = {
                        type_type: data.data.data[i].a_type,
                        id_code: data.data.data[i].id,
                        store_code: data.data.data[i].store_code
                    }
                    json_j.push(json_s);
                }
                else if (rfm_type == 'j') {
                    let json_s = {
                        type_type: data.data.data[i].j_type,
                        id_code: data.data.data[i].id,
                        store_code: data.data.data[i].store_code
                    }
                    json_j.push(json_s);
                }
                else if (rfm_type == 'c') {
                    let json_s = {
                        type_type: data.data.data[i].c_type,
                        id_code: data.data.data[i].id,
                        store_code: data.data.data[i].store_code
                    }
                    json_j.push(json_s);
                }

            }
        }
    }, 'json'
    );
}

// 群发
function information(code, store_code) {
    mui.confirm('', '精准营销工具', ['短信', '视图', '微信', '卡劵'], function (e) {
        // e.index == 1
        if (e.index == 1) {
            // 视图
            location.href = '../homepage/rich_letter_view_member.html?' + 'id=' + code + 'code*' + store_code;

        } else if (e.index == 2) {
            location.href = '../homepage/short_weixin_group_member.html?' + 'id=' + code + 'code*' + store_code;
        } else if (e.index == 3) {
            location.href = '../homepage/group_card_coupon_member.html?' + 'id=' + code + 'code*' + store_code;
        } else {
            // 短信
            location.href = '../homepage/short_message_member.html?' + 'id=' + code + 'code*' + store_code;
        }
    })

}

// 会员
function member(code, store_code) {
    if (rfm_type == 'r') {
        location.href = '../homepage/activity_level_member.html?' + 'id=' + code + 'code*' + store_code;
    } else if (rfm_type == 'f') {
        location.href = '../homepage/recurrence_rate_member.html?' + 'id=' + code + 'code*' + store_code;
    } else if (rfm_type == 'm') {
        location.href = '../homepage/contribution_degree_member.html?' + 'id=' + code + 'code*' + store_code;
    }
    else if (rfm_type == 'i') {
        location.href = '../homepage/loyalty_member.html?' + 'id=' + code + 'code*' + store_code;
    }
    else if (rfm_type == 'n') {
        location.href = '../homepage/high_number_member.html?' + 'id=' + code + 'code*' + store_code;
    }
    else if (rfm_type == 'p') {
        location.href = '../homepage/gao_ke_shan_member.html?' + 'id=' + code + 'code*' + store_code;
    }
    else if (rfm_type == 'a') {
        location.href = '../homepage/tall_sheet_member.html?' + 'id=' + code + 'code*' + store_code;
    }
    else if (rfm_type == 'j') {
        location.href = '../homepage/high_joint_member.html?' + 'id=' + code + 'code*' + store_code;
    }
    else if (rfm_type == 'c') {
        location.href = '../homepage/high_consumption_member.html?' + 'id=' + code + 'code*' + store_code;
    }
}
