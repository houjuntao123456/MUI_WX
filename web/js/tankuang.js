document.write('<link rel="stylesheet" href="../css/Animate.css">');

// 创建隐藏样式
var style = document.createElement('style');
style.innerHTML = ".dis_none_skills{ display: none;}";
document.getElementsByTagName('head').item(0).appendChild(style); 

//开启动画
var start_animate ='bounceInRight';

document.write('<div id="skills_details" class="animated ' + start_animate + ' dis_none_skills" style="width:100%;position:absolute;top:0;font-size: 22px;background-color:#F9F9F9;height:100%;z-index:20"><header class="mui-bar mui-bar-nav mui-bar-nav-bg" style="background-color:#007aff;padding-left:0;padding-right:0"><a id="skills_title" class="mui-icon mui-icon-back" style="color:#fff;padding-left:15px"></a><h1 class="mui-title" style="color:#fff;width:50%;margin:0 auto">话术详情</h1></header><div class="mui-scroll-wrapper" style="top:44px"><div class="mui-scroll"><div  id="details_center" style="padding-top:10px;padding-bottom:10px;"></div> </div></div></div>');

// 动画显示
var skills_details = document.getElementById("skills_details");

// 内容显示
var details_center = document.getElementById("details_center");
// 关闭标签
var skills_title = document.getElementById("skills_title");
var tan={
    texts: function (obj){
        details_center.innerHTML=obj;
        this.add();
    },
    add:function(){
        // 添加动画
        skills_details.classList.add(start_animate);
        // 移除隐藏
        skills_details.classList.remove('dis_none_skills');
    },
    remove:function(){
        // 移除动画
        skills_details.classList.remove(start_animate);
        // 添加隐藏
        skills_details.classList.add('dis_none_skills');
    }
}

skills_title.onclick=function(){
    tan.remove();
}

