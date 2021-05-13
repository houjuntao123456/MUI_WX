
{/* <div id="switch_store" class="mui-row" style="height: 40px;line-height: 40px;">
    &nbsp;<span style="padding-left: 2px; background-color: #007aff;"></span>&nbsp; <span
        id="member_txt">形象顾问</span> <span class="mui-icon mui-icon-arrowdown"
            style="float: right;line-height: 38px;margin-right: 5px;"></span>
</div> */}



(function () {
    let mui_conteont = document.getElementsByClassName("mui-content");

    var divsss = document.createElement("div");
    divsss.className = 'mui-row';
    divsss.setAttribute("id", "rfm_switch_store")
    divsss.style = 'height: 40px;line-height: 40px;';
    divsss.innerHTML = '&nbsp;<span style="padding-left: 2px; background-color: #007aff;"></span>&nbsp;'
        + ' <span id="rfm_member_txt">形象顾问</span> '
        + '<span class="mui-icon mui-icon-arrowdown"  style="float: right;line-height: 38px;margin-right: 5px;"></span>'
    mui_conteont[0].insertBefore(divsss, mui_conteont[0].firstChild);

})();


var json = [];

var rfm_switch_store = document.getElementById("rfm_switch_store");
console.log(rfm_switch_store);

rfm_switch_store.addEventListener("tap", function () {
    // console.log(json)
    let picker = new mui.PopPicker();
    picker.setData(json);
    picker.show(function (selectItems) {
        // staff_code2 = selectItems[0].value;
        // localStorage_code[1] = staff_code2;
        // localStorage_code[3] = selectItems[0].text;
        mui("#rfm_member_txt")[0].innerHTML = selectItems[0].text;
        men(selectItems[0].mem_code, selectItems[0].value);
        // add_huncun();
    })
});

function rfm_staff_code(code) {
    // console.log(code);
    mui("#rfm_member_txt")[0].innerHTML = '形象顾问';
    json = [];
    // localStorage.setItem("vip_staff_code", '');
    mui.post('/index.php/UserlistStaff/', {
        code: code,
    }, function (data) {
        console.log(data);
        // 服务器返回响应，根据响应结果，分析是否登录成功；
        if (data.code == 200) {
            if (data.data.length == 0) {
                let son = {
                    mem_code: code,
                    value: "",
                    text: '暂无形象顾问'
                }
                json.push(son);
            } else {
                for (let i = 0; i < data.data.length; i++) {
                    let son = {
                        mem_code: code,
                        value: data.data[i].code,
                        text: data.data[i].name
                    }
                    json.push(son);
                }
            }

        }
    }, 'json'
    );

}