var outPutData;
var dateTime;

var y_tltle = "";
var x_title = "";
var z_title = ""
var h_type = '';
var json_type = [];
var zhouqi_type = false;

// 禁用点击遮盖层弹出菜单
window.addEventListener('tap', function (e) {
    e.target.className == 'mui-backdrop mui-active' && e.stopPropagation();
}, true);
function rfm_echarts() {
    if (ttt.innerHTML == "活跃度") {
        localStorage.setItem("rfm_type", 'R');
        h_type = "R"
        zhouqi_type = false;
        z_title = "R活跃分析";
        x_title = '(得分)';
        y_tltle = '(人数)';
    } else if (ttt.innerHTML == "回头率") {
        localStorage.setItem("rfm_type", 'F');
        h_type = "F"
        zhouqi_type = true;
        z_title = "F回头率分析";
        x_title = '(得分)';
        y_tltle = '(人数)'

    } else if (ttt.innerHTML == "贡献度") {
        localStorage.setItem("rfm_type", 'M');
        h_type = "M"
        zhouqi_type = true;
        z_title = "M贡献度分析";
        x_title = '(得分)';
        y_tltle = '(人数)'

    } else if (ttt.innerHTML == "忠诚度") {
        localStorage.setItem("rfm_type", 'I');
        h_type = "I"
        zhouqi_type = true;
        z_title = "I忠诚度分析";
        x_title = '(得分)';
        y_tltle = '(天)'


    } else if (ttt.innerHTML == "高件数") {
        localStorage.setItem("rfm_type", 'N');
        h_type = "N"
        zhouqi_type = true;
        z_title = "N高件数分析";
        x_title = '(得分)';
        y_tltle = '(人数)'

    } else if (ttt.innerHTML == "高客单") {
        localStorage.setItem("rfm_type", 'P');
        h_type = "P"
        zhouqi_type = true;
        z_title = "P高客单分析";
        x_title = '(得分)';
        y_tltle = '(人数)'

    } else if (ttt.innerHTML == "高件单") {
        localStorage.setItem("rfm_type", 'A');
        h_type = "A"
        zhouqi_type = true;
        z_title = "A高件单分析";
        x_title = '(得分)';
        y_tltle = '(人数)'

    } else if (ttt.innerHTML == "高连带") {
        localStorage.setItem("rfm_type", 'J');
        h_type = "J"
        zhouqi_type = true;
        z_title = "J高连带分析";
        x_title = '(得分)';
        y_tltle = '(人数)'

    } else if (ttt.innerHTML == "高消费") {
        localStorage.setItem("rfm_type", 'C');
        h_type = "C"
        zhouqi_type = true;
        z_title = "C高消费分析";
        x_title = '(得分)';
        y_tltle = '(人数)'

    }
    // 指定图表的配置项和数据
    option = {
        title: {
            text: z_title,
        },
        backgroundColor: '#fff',
        // efeff0
        grid: {
            left: '3%',
            right: '12%',
            // top:'25%',
            bottom: '10%',
            containLabel: true
        },

        xAxis: {
            name: x_title,
            type: 'category',
            boundaryGap: true,
            splitLine: { show: true },
            nameTextStyle: {
                // color: "#222",
                // fontFamily: "#Arial",
                fontWeight: 'bold',
                fontSize: 14,
                padding: [0, 0, 0, -10]
                // margin: [20, 0,0, 0]
            },
            data: dateTime,
            axisLabel: {
                interval: 0,
                rotate: 0,
                margin: 8,
                textStyle: {
                    color: 'blue',
                    // fontFamily: 'sans-serif',
                    // fontSize: 15,
                    // fontStyle: 'italic',
                    // fontWeight: 'bold'
                },
                formatter: function (value) {
                    // debugger
                    // var str = value.indexOf("w");
                    // var str3 = value.indexOf("-");
                    // var str1 = decodeURI(value.substr(0, str3));
                    // var str4 = decodeURI(value.substr(str3 + 1));
                    // // alert(str1+'\n'+" |︲ "+"\n"+str4);
                    // var ret = str1 + "\n" + "<f<" + "\n" + str4+"\n";//拼接加\n返回的类目项
                    // return ret;
                    return value;
                }
            }
        },
        yAxis: {
            name: y_tltle,
            type: 'value',
            nameTextStyle: {
                // color:"#ffffffff",
                // fontFamily: "#Arial",
                fontWeight: 'bold',
                fontSize: 14,
                padding: [0, 0, 0, 0]
                // margin: [20, 0,0, 0]
            },
            axisLabel: {
                formatter: '{value}'
            }
        },
        series: [
            {
                type: 'bar',
                label: {
                    normal: {
                        show: true,
                        position: 'inside'
                    }
                },
                data: outPutData
            }
        ]
    };
    // 使用刚指定的配置项和数据显示图表。
    myChart.setOption(option);
    myChart.getZr().off('click');
    myChart.getZr().on('click', params => {
        const pointInPixel = [params.offsetX, params.offsetY];
        if (myChart.containPixel('grid', pointInPixel)) {
            //执行代码
            let pointInGrid = myChart.convertFromPixel({ seriesIndex: 0 }, pointInPixel);
            //X轴序号
            let xIndex = pointInGrid[0];
            //获取当前图表的option
            let op = myChart.getOption();
            //获得图表中我们想要的数据
            let month = op.xAxis[0].data[xIndex];
            centers_text(month);
        }
    });
}

// 触发弹出内容模板
function centers_text(num) {
    for (let i = 0; i < json_type.length; i++) {
        if (json_type[i].fen == num) {
            console.log(num)
            let content_txt = '';
            let type = json_type[i].type; //类型
            let fen = json_type[i].fen;//得分
            let id = json_type[i].id;   //id
            let name = json_type[i].name; //会员code
            let people_num = json_type[i].people_num; //会员人数
            if (zhouqi_type == false) {
                // 无周期
                content_txt = '<table> <tbody><tr class="tr-1">'
                    + ' <th class="width-40">类型</th>'
                    + ' <th class="width-15">得分</th>'
                    + '<th class="width-25">会员人数</th>'
                    + ' </tr>'
                    + '<tr class="tr-2">'
                    + '<td><span>' + type + '</span></td>'
                    + '<td><span>' + fen + '</span></td>'
                    + '<td><span>' + people_num + '</span></td>'
                    + ' </tr>'
                    + ' </tbody>'
                    + '</table>'
            } else if (zhouqi_type == true) {
                //   有周期
                let zhouqi = json_type[i].zhouqi; //周期
                content_txt = '<table> <tbody><tr class="tr-1">'
                    + ' <th class="width-40">类型</th>'
                    + ' <th class="width-15">周期</th>'
                    + ' <th class="width-15">得分</th>'
                    + '<th class="width-25">会员人数</th>'
                    + ' </tr>'
                    + '<tr class="tr-2">'
                    + '<td><span>' + type + '</span></td>'
                    + '<td><span>' + zhouqi + '</span></td>'
                    + '<td><span>' + fen + '</span></td>'
                    + '<td><span>' + people_num + '</span></td>'
                    + ' </tr>'
                    + ' </tbody>'
                    + '</table>'
            }

            mui.confirm('', content_txt, ['会员', '专场', '群发', '取消'], function (e) {
                // 点击了确定按钮
                if (e.index == 0) {
                    // 会员
                    member(id,name);
                } else if (e.index == 1) {
                    //专场
                    if (h_type == "R") {
                        follow(id, name, 'r');
                    } else if (h_type == "F") {
                        follow(id, name, 'f');
                    } else if (h_type == "M") {
                        follow(id, name, 'm');
                    } else if (h_type == "I") {
                        follow(id, name, 'i');
                    } else if (h_type == "N") {
                        follow(id, name, 'n');
                    } else if (h_type == "P") {
                        follow(id, name, 'p');
                    } else if (h_type == "A") {
                        follow(id, name, 'a');
                    } else if (h_type == "J") {
                        follow(id, name, 'j');
                    } else if (h_type == "C") {
                        follow(id, name, 'c');
                    }
                } else if (e.index == 2) {
                    //群发
                    information(id,name);
                } else if (e.index == 3){
                    return true;
                }
               
            })
            break;//结束循环
        }
    }




}

// 添加跟进模板标题
function follow(id,name,type) {
    mui.prompt('', '请添加跟进模板名称', '跟进模板', ['取消', '确定'], function (e) {
        if (e.index == 1) {
            if (e.value != "") {
                mui.post('/index.php/RfmActiveRfmField/', {
                    // text: '【' + e.value + '】' + '<br>' + content_txt
                    title: e.value,
                    id: id,
                    rfm_type: type,
                    store_code: name
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

// 点击按钮,将参数传递到另一个页面
function member(id,name) {
    if (h_type == "R") {
        location.href = '../homepage/activity_level_member.html?' + 'id=' +id + 'code*' + name;
    } else if (h_type == "F") {
        location.href = '../homepage/recurrence_rate_member.html?' + 'id=' +id + 'code*' + name;
    } else if (h_type == "M") {
        location.href = '../homepage/contribution_degree_member.html?' + 'id=' +id + 'code*' + name;
    } else if (h_type == "I") {
        location.href = '../homepage/loyalty_member.html?' + 'id=' +id + 'code*' + name;
    } else if (h_type == "N") {
        location.href = '../homepage/high_number_member.html?' + 'id=' +id + 'code*' + name;
    } else if (h_type == "P") {
        location.href = '../homepage/gao_ke_shan_member.html?' + 'id=' +id + 'code*' + name;
    } else if (h_type == "A") {
        location.href = '../homepage/tall_sheet_member.html?' + 'id=' +id + 'code*' + name;
    } else if (h_type == "J") {
        location.href = '../homepage/high_joint_member.html?' + 'id=' +id + 'code*' + name;
    } else if (h_type == "C") {
        location.href = '../homepage/high_consumption_member.html?' + 'id=' +id + 'code*' + name;
    }
}

// 群发
function information(id,name) {

    mui.confirm('', '精准营销工具', ['短信', '视图', '微信', '卡劵'], function (e) {
        // e.index == 1
        if (e.index == 1) {
            // 视图
            location.href = '../homepage/rich_letter_view_member.html?' + 'id=' + id + 'code*' + name;

        } else if (e.index == 2) {
            location.href = '../homepage/short_weixin_group_member.html?' + 'id=' + id + 'code*' + name;
        } else if (e.index == 3) {
            location.href = '../homepage/group_card_coupon_member.html?' + 'id=' + id + 'code*' + name;
        } else {
            // 短信
            location.href = '../homepage/short_message_member.html?' + 'id=' + id + 'code*' + name;
        }
    })
}