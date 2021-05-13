//机构报表的树状图和ajax的js
//  报表图
var dateTime;
var outPutData;
function chart() {
    // 指定图表的配置项和数据
    option = {
        xAxis: {
            type: 'category',
            boundaryGap: false,
            data: dateTime,
        },
        yAxis: {
            type: 'value',
            show:true,
        },
        grid: {
            top:15,
            left: 80
        },
        series: [{
            data: outPutData,
            type: 'line',
            itemStyle: { normal: { label: { show: true, position:'inside' }}},
            smooth: true,
            areaStyle: {
                shadowColor: 'rgba(74，247，253，0.5)',
                shadowBlur: 10
            }
        }]
    };



    // 使用刚指定的配置项和数据显示图表。
    myChart.setOption(option);
}
function zon(check,end) {
    if (check=="null") {
        // 开始自调格式zon("null",)
        alert(check+" null "+end);
        mui.ajax('/index.php/org_achievement/', {
            data: {
                // username: 'username',
                // password: 'password'
            },
            dataType: 'json',//服务器返回json格式数据
            type: 'post',//HTTP请求类型
            timeout: 10000,//超时时间设置为10秒；
            headers: { 'Content-Type': 'application/json' },
            success: function (data) {
                //服务器返回响应，根据响应结果，分析是否登录成功；
                console.log(data);
                if (data.code == 200) {
                    // 进行数据显示
                    dateTime = [data.data.pic[0], data.data.pic[1], data.data.pic[2], data.data.pic[3], data.data.pic[4], data.data.pic[5], data.data.pic[6]];
                    outPutData = [data.data.time[0], data.data.time[1], data.data.time[2], data.data.time[3], data.data.time[4], data.data.time[5], data.data.time[6]];
                    chart();
                }

            },
            error: function (xhr, type, errorThrown) {
                //异常处理；
                console.log(type);
            }
        });
    } else if (check && end) {
        // 传的开始至结束格式是zon("2019-05-06","2019-09-03")
        alert(check+" && "+end);
        mui.ajax('/index.php/org_achievement/', {
            data: {
                check: check,
                end:end
            },
            dataType: 'json',//服务器返回json格式数据
            type: 'post',//HTTP请求类型
            timeout: 10000,//超时时间设置为10秒；
            headers: { 'Content-Type': 'application/json' },
            success: function (data) {
                //服务器返回响应，根据响应结果，分析是否登录成功；
                console.log(data);
                if (data.code == 200) {
                    // 进行数据显示
                    dateTime = [data.data.pic[0], data.data.pic[1], data.data.pic[2], data.data.pic[3], data.data.pic[4], data.data.pic[5], data.data.pic[6]];
                    outPutData = [data.data.time[0], data.data.time[1], data.data.time[2], data.data.time[3], data.data.time[4], data.data.time[5], data.data.time[6]];
                    chart();
                }

            },
            error: function (xhr, type, errorThrown) {
                //异常处理；
                console.log(type);
            }
        });
    }else if(check){
        //传的是日格式zon("2019-05-06",)
        alert(check+" || "+end);
    mui.ajax('/index.php/org_achievement/', {
        data: {
            check: check
        },
        dataType: 'json',//服务器返回json格式数据
        type: 'post',//HTTP请求类型
        timeout: 10000,//超时时间设置为10秒；
        headers: { 'Content-Type': 'application/json' },
        success: function (data) {
            //服务器返回响应，根据响应结果，分析是否登录成功；
            console.log(data);
            if (data.code == 200) {
                // 进行数据显示
                dateTime = [data.data.pic[0], data.data.pic[1], data.data.pic[2], data.data.pic[3], data.data.pic[4], data.data.pic[5], data.data.pic[6]];
                outPutData = [data.data.time[0], data.data.time[1], data.data.time[2], data.data.time[3], data.data.time[4], data.data.time[5], data.data.time[6]];
                chart();
            }

        },
        error: function (xhr, type, errorThrown) {
            //异常处理；
            console.log(type);
        }
    });
}else{
    alert(666);
}

  }