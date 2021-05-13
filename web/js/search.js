// 跟进提醒搜索
var search_json = [];
var search_json_result = [];
var li2s = document.getElementsByClassName("li2");
var search = {
    // 添加
    add: function (code, name) {
        let json = {
            id: code,
            name: name
        };
        search_json.push(json);
    },
    // 搜索
    searchs: function (value) {
        mui('.mui-scroll-wrapper').scroll().scrollTo(0, 0, 100);//100毫秒滚动到顶
        if (value == "") {
            for (let i = 0; i < li2s.length; i++) {
                li2s[i].classList.remove('dis');
            }
        } else {
            search_json_result = [];
            for (let i = 0; i < search_json.length; i++) {
                if (search_json[i].name.indexOf(value) > -1) {
                    //则包含该元素
                    let json = {
                        id: search_json[i].id,
                        name: search_json[i].name
                    };
                    search_json_result.push(json);
                }
            }
            // 保存显示li的下角标
            let remind=[];
            for (let h = 0; h < search_json_result.length; h++) {
                for (let i = 0; i < li2s.length; i++) {
                    if (li2s[i].firstChild.getAttribute("code") == search_json_result[h].id) {
                        remind.push(i);
                    }
                }
            }
            // 给全体隐藏
            for (let i = 0; i < li2s.length; i++) {
                li2s[i].classList.add('dis');
            }
            // 给需要显示打开显示
            for(let i=0;i<remind.length;i++){
                li2s[remind[i]].classList.remove("dis");
            }

        }
    }
}