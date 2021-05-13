var shuzu = [];
var shuzu_code = [];
var control_Member = true; //true 全选, false 单选
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
        document.getElementById("show_qunfa").innerHTML = '群发';
        control_Member = true;
    } else {
        control_Member = false;
        document.getElementById("show_qunfa").innerHTML = '发送';
        document.getElementById("nums").classList.remove("dis");
        document.getElementById("nums").innerHTML = shuzu.length;
    }
}







//判断图片是否存在
function CheckImgExists(imgurl) {
    if ((imgurl == "") || (imgurl == null) || (imgurl == undefined) || (imgurl == "undefined")) {
        return '../images/suoke.jpg';
    } else {
        let ImgObj = new Image(); //判断图片是否存在  
        ImgObj.src = imgurl;
        //存在图片
        if (ImgObj.fileSize > 0 || (ImgObj.width > 0 && ImgObj.height > 0)) {
            return imgurl;
        } else {
            return '../images/suoke.jpg';
        }
    }

}
