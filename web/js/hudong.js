var shuzu = [];
var shuzu_code = [];
var all_contor = true; //全选状态
mui(".mui-table-view").on('tap', '.xuanze', function () {
    //获取id
    // var id = this.getAttribute("id");
    //传值给详情页面，通知加载新数据
        // 非全选状态
        if (this.classList.contains("xuanzhong")) {
            // 不包含 (未选中状态)
            this.classList.remove("xuanzhong");
            console.log('未选中状态')
            // 添加数组
            remove_array(this.getAttribute("phone"), this.getAttribute("code"))
        } else {
            // 包含(选中状态)
            this.classList.add("xuanzhong");
            console.log('选中状态')
            add_array(this.getAttribute("phone"), this.getAttribute("code"));
        }

})
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
        document.getElementById("menu").innerHTML = '群发';
        document.getElementById("nums").classList.add("dis");
        all_contor=true;
    } else {
        document.getElementById("menu").innerHTML = '发送';
        document.getElementById("nums").classList.remove("dis");
        document.getElementById("nums").innerHTML = shuzu.length;
        all_contor = false;
    }
}






// 确定群发
function cluste_hai() {
    localStorage.setItem("route", '/index.php/webCustomerServiceShu/');
    mui.confirm('', '精准营销工具', ['短信', '视图', '微信', '卡劵'], function (e) {
        // e.index == 1
        if (e.index == 1) {
            // 视图
            if (all_contor) {
                // 已选择全部会员
                // 控制短信页面选择全部会员 true(会员全选)
                localStorage.setItem("array_shuzu_control", 'true');
                location.href = '../homepage/rich_letter_view.html';
            } else {
                // 控制短信页面选择全部会员 false(不是会员全选)
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
            }
        } else if (e.index == 2) {
            if (all_contor) {
                // 已选择全部会员
                // 控制短信页面选择全部会员 true(会员全选)
                localStorage.setItem("array_shuzu_control", 'true');
                location.href = '../homepage/short_weixin_group.html';
            } else {
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
            }
        } else if (e.index == 3) {
            if (all_contor) {
                // 已选择全部会员
                // 控制短信页面选择全部会员 true(会员全选)
                localStorage.setItem("array_shuzu_control", 'true');
                location.href = '../homepage/group_card_coupon.html';
            } else {
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
            }
        } else {
            if (all_contor) {
                // 已选择全部会员
                // 控制短信页面选择全部会员 true(会员全选)
                localStorage.setItem("array_shuzu_control", 'true');
                location.href = '../homepage/short_message.html?type=true';
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
        }
    })
}



// // 全选会员
// function all_election(e) {
//     let all_quanxuan = document.getElementsByClassName("all_quanxuan");
//     all_contor = !all_contor;
//     // if(e==1){
//     //     mui('.mui-off-canvas-wrap').offCanvas().toggle();
//     // }
//     if (all_contor == true) {
//         // 全选
//         for (let index = 0; index < all_quanxuan.length; index++) {
//             all_quanxuan[index].classList.add("xuanzhong");
//         }

//     } else {
//         // 取消全选
//         for (let index = 0; index < all_quanxuan.length; index++) {
//             all_quanxuan[index].classList.remove("xuanzhong");
//         }

//     }
// }




