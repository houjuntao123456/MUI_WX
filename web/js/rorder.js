document.getElementById("a_title").addEventListener("tap", function () {
	location.href = 'javascript: history.go(-1)';
});
var load = new Loading();
load.init();
/****************************************************************************************************/
// 分页计数
var count = 0;
var count2 = 0;
// 分的条数
var limit_num = 15;

//会员
mui("#pullrefresh").pullRefresh({
	// down: {
	//     callback: pulldownRefresh
	// },
	up: {
		contentrefresh: '正在加载...',
		contentnomore: '没有更多数据了',//可选，请求完毕若没有更多数据时显示的提醒内容；
		auto: true,//可选,默认false.首次加载自动上拉刷新一次
		callback: pullupRefresh
	},
})


//查询会员姓名
function pullupRefresh() {
	load.start();
	mui.post('/index.php/webReturnlikeVip/', {
		where: document.getElementById("searchs").value,
		page: ++count,
		limit: limit_num
	}, function (data) {
		// console.log(data);
		if (data.code == 200) {
			//服务器返回响应，根据响应结果，分析是否登录成功；
			setTimeout(function () {
				mui('#pullrefresh').pullRefresh().endPullupToRefresh((count > (data.data.count / limit_num))); //参数为true代表没有更多数据了。
				if (data.data.count == 0) {
					var ndiv = document.createElement('div');
					ndiv.innerHTML = '<div class="mui-card"><p style="text-align:center;color: #333;font-size: 16px;width: 100%;">暂无数据</p></div>';
					document.getElementById("data_n").appendChild(ndiv);
				} else {
					//服务器返回响应，根据响应结果，分析是否登录成功；
					for (var i = 0; i < data.data.data.length; i++) {
						var wexin_title = "";
						// 进行判断,如果为空执行默认路径的图片
						if (data.data.data[i].img == "" || data.data.data[i].img == null || data.data.data[i].img == " ") {
							data.data.data[i].img = "../images/suoke.jpg";
						}
						// 判断是否绑定微信 1是绑定 0是未绑定
						if (data.data.data[i].vvip == 1) {
							wexin_title = '<span class="mui-icon mui-icon-weixin" style="position: absolute;top: -10px;left: 40px;z-index: 999;color: #80D640;"></span>';
						}
						var ndiv = document.createElement('div');
						ndiv.className = 'vipcode';
						ndiv.id = data.data.data[i].code;
						ndiv.innerHTML = '<div><img src="' + data.data.data[i].img + '" alt="暂无图片">' + wexin_title + '</div>'
							+ '<p>' + data.data.data[i].username + '</p>';

						document.getElementById("data_n").appendChild(ndiv);
					}
					var obj_p = document.getElementById("data_n").getElementsByTagName("div");
					objp(obj_p);
				}
				load.stop();
			}, 1000);
		}
	}, 'json');
}
//选择会员
function objp(o) {
	for (j = 0; j < o.length; j++) {
		o[j].addEventListener("tap", function () {
			document.getElementById("dateName").value = this.innerText;
			document.getElementById('search_alll').classList.add('display_none');
			document.getElementById('da').classList.remove('display_none');
		});
	}
}
// vip_code 卡号
var vcode = "";
mui("#data_n").on('tap', '.vipcode', function (e) {
	// console.log(this.getAttribute("id"));
	// e.stopPropagation();
	vcode = this.getAttribute("id");
	// return vcode;
})

/****************************************************************************************************/
// 执行时间

// myDate.getHours(); //获取系统时，
// myDate.getMinutes(); //分
// myDate.getSeconds(); //秒

$(function () {
	$("#dateTime").click(function () {
		// var myDate = new Date(); //实例一个时间对象；
		var dtPicker = new mui.DtPicker({ type: 'datetime' });
		/*参数：'datetime'-完整日期视图(年月日时分)
				'date'--年视图(年月日)
				'time' --时间视图(时分)
				'month'--月视图(年月)
				'hour'--时视图(年月日时)
		*/
		dtPicker.show(function (selectItems) {
			var y = selectItems.y.text;  //获取选择的年
			var m = selectItems.m.text;  //获取选择的月
			var d = selectItems.d.text;  //获取选择的日
			var h = selectItems.h.text; // 时
			var i = selectItems.i.text; // 分
			var s = '00'; // 秒
			var date = y + "-" + m + "-" + d + " " + h + ":" + i + ":" + s;
			$("#dateTime").val(date);
		})
		document.activeElement.blur(); //js 禁止唤醒手机键盘
	});
})
//时间补0
function timeAdd0(str) {
	if (str.length <= 1) {
		// console.log('bu');
		str = '0' + str;
	}
	return str
}
/****************************************************************************************************/
var all_shopping = document.getElementById("all_shopping");
// 下拉框初始赋值
function onload_select_innerHTML() {
	// console.log('jiazaizhong');
	// 导购选择加载多余
	var option_all_shopping = document.createElement("option");
	option_all_shopping.value = "";
	option_all_shopping.innerHTML = "导购选择";
	all_shopping.appendChild(option_all_shopping);
}

//导购 
function onload_select() {
	mui.post('/index.php/Userlistwstaff/', {
	}, function (data) {
		//服务器返回响应，根据响应结果，分析是否登录成功；
		// console.log(data);
		if (data.code == 200) {
			// all_shopping.innerHTML = '<option value="">导购选择</option>'
			for (i = 0; i < data.data.length; i++) {
				var option = document.createElement('option');
				option.value = data.data[i].code;
				option.innerHTML = data.data[i].name;
				all_shopping.appendChild(option);
			}
		}
	}, 'json');
}
// 新增方案 ---------------------------------------------------------------------------------------------------------------
var programme_a = document.getElementById('segmentedControl');
var alist = programme_a.getElementsByTagName("a");

var parr = [1]; 
var num = 1;
var delarr = [];
var jishu = 1;
var contor = false;

var seearr = [1,2,3,4,5,6];//数组1   （所有人）
var judge = false;
var alternum = '';

function tempArr(){
	judge = true;

	//临时数组存放
	var tempSeearr = [];//临时数组1
	var tempParr = [];//临时数组2

	for(var i=0;i<parr.length;i++){
		tempSeearr[parr[i]]=true;//将数parr 中的元素值作为tempSeearr 中的键，值为true；
	}

	for(var i=0;i<seearr.length;i++){
		if(!tempSeearr[seearr[i]]){
			tempParr.push(seearr[i]);//过滤seearr 中与parr 相同的元素；
		}
	}
	// console.log(tempParr)
	alternum = tempParr[0];
	create_a(alternum);	
	// console.log(alternum);
	// console.log(parr);
	
}


function create_a(g) {
	var a = document.createElement('a');
	a.className = 'mui-control-item mui-active';
	a.innerHTML = '方案' + g;
	a.setAttribute('numbe', g);
	programme_a.appendChild(a);

	parr.push(parseInt(g));
	add_programme(parseInt(g));
	center_txt(parseInt(g))
	read_programme(parseInt(g))
}

//创建方案
function createProgramme() {
	for (var i = 0; i < alist.length; i++) {
		alist[i].classList.remove('mui-active');
	}

	if (num < 6) {
		// console.log(num);
		num++;
		if (contor == false) {
			if (judge == true){ //修改页面
				tempArr();				
			} else {
				create_a(num);
				jishu = (num + 1);
			}
		} else {
			if (parr.length == 6) {
				let add = delarr.pop();
				create_a(add);
			} else {
				if (delarr.length == 0) {
					create_a(jishu);
					jishu++;
				} else {
					let add = delarr.pop();
					create_a(add);
				}
			}
		}
	} else {
		mui.toast('最多6个方案')
	}
}
//删除方案
function delProgramme(){
	if (num > 1) {
		contor = true;
		for (var i = 0; i < alist.length; i++) {
			if (alist[i].classList.contains('mui-active')) {
				num--;
				let del = alist[i].getAttribute("numbe");
				let name_s = alist[i].innerHTML;
				// console.log(name_s);
				delarr.push(parseInt(del));

				for (let k = 0; k < parr.length; k++) {
					if (parr[k] == del) {
						parr.splice(k, 1)
					}
				}
				delete_programme(name_s,del);
				// 给当前节点的相邻兄弟加选中样式，若相邻上一级没有，则寻找下一级
				if (alist[i].previousElementSibling == null) {
					alist[i].nextElementSibling.classList.add("mui-active");
					center_txt(alist[i].nextElementSibling.getAttribute("numbe"));
					read_programme(alist[i].nextElementSibling.getAttribute("numbe"));
				} else {
					alist[i].previousElementSibling.classList.add("mui-active");
					center_txt(alist[i].previousElementSibling.getAttribute("numbe"));
					read_programme(alist[i].previousElementSibling.getAttribute("numbe"));
				}
				// 删除当前节点
				alist[i].parentNode.removeChild(alist[i]);
			}
		}
	} else {
		mui.toast('最少1个方案')
	}

}

var hxtext = document.getElementById("hxtext"); //核心卖点
var toptext = document.getElementById("toptext"); //top
var zttext = document.getElementById("zttext"); //整体搭配
var wttext = document.getElementById("wttext"); //顾客问题
var hstext = document.getElementById("hstext"); //回答问题

var jsonarr = [
	{
		id: 1,
		name: '方案1',
		img: [{
				delete_id: '',
				goods_code: '', 
				goods_img: '',
				price: '' 
			}
		],
		core: '',
		top: '',
		collocation: '',
		problem: '',
		talking_skill: ''
	}
];
//    添加方案
function add_programme(obj) {
	img_programme(obj);
	// console.log(obj)
	let json_2 = {
		id: obj,
		name: '方案' + obj,
		img: [
			{
				delete_id: '',
				goods_code: '', 
				goods_img: '',
				price: '' 
			}
		],
		core: '',
		top: '',
		collocation: '',
		problem: '',
		talking_skill: ''
	}

	jsonarr.push(json_2);	
}
// 删除方案 names
function delete_programme(names,del) {
	for (let l = 0; l < jsonarr.length; l++) {
		if (jsonarr[l].name == names || jsonarr[l].programme == del) {
			for (let j = 0; j < jsonarr[l].img.length; j++) {
				// console.log(jsonarr[l].img[j].delete_id);
				idcode = jsonarr[l].img[j].delete_id;
				delImg(idcode);				
			}
			jsonarr.splice(l, 1);
		}
	}
}

// 修改方案内容
function modify_programme(num, type, text) {
	for (let i = 0; i < jsonarr.length; i++) {
		if (jsonarr[i].id == num || jsonarr[i].programme == num) {
			// console.log(num);
			if (type == 'core') {
				jsonarr[i].core = text;
			} else if (type == 'top') {
				jsonarr[i].top = text;
			} else if (type == 'collocation') {
				jsonarr[i].collocation = text;
			} else if (type == 'problem') {
				jsonarr[i].problem = text;
			} else if (type == 'talking_skill') {
				jsonarr[i].talking_skill = text;
			}
			// console.log(jsonarr[i]);
		}
	}
}

var dpimg =document.getElementById('dpimg');
var idcode = '';
var gcode = '';
var paths = '';
var pricep = '';
var gname = '';
//读取方案
read_programme(1)
function read_programme(num) {
	img_programme(num);
	dpimg.innerHTML="";
	for (let i = 0; i < jsonarr.length; i++) {
		if (jsonarr[i].id == num || jsonarr[i].programme == num) {
			hxtext.value = jsonarr[i].core;
			toptext.value = jsonarr[i].top;		
			zttext.value = jsonarr[i].collocation;	
			wttext.value = jsonarr[i].problem;
			hstext.value = jsonarr[i].talking_skill;

			for (let j = 0; j < jsonarr[i].img.length; j++) {				
				if (jsonarr[i].img[j].goods_img != ""){
					idcode = jsonarr[i].img[j].delete_id;
					gcode = jsonarr[i].img[j].goods_code;
					paths = jsonarr[i].img[j].goods_img;
					pricep = jsonarr[i].img[j].price;
					dpimgf(dpimg, idcode, gcode, paths, pricep);
					
				}
				
			}
		}

	}
}

center_txt(1)
var num_bers = 1;
function center_txt(obj) {
	// console.log(obj);
	num_bers = obj;
}
$(function () {
	$("#hxtext").bind('input porpertychange', function () {
		modify_programme(num_bers, 'core', this.value);
	});
	$("#toptext").bind('input porpertychange', function () {
		modify_programme(num_bers, 'top', this.value);
	});
	$("#zttext").bind('input porpertychange', function () {
		modify_programme(num_bers, 'collocation', this.value);
	});
	$("#wttext").bind('input porpertychange', function () {
		modify_programme(num_bers, 'problem', this.value);
	});
	$("#hstext").bind('input porpertychange', function () {
		modify_programme(num_bers, 'talking_skill', this.value);
	});
});

// 商品搜索---------------------------------------------------------------------------------------------------------------

//侧滑 商品
mui("#offCanvasSideScroll").pullRefresh({
	up: {
		// height: 50,
		contentrefresh: '正在加载...',
		contentnomore: '没有更多数据了',//可选，请求完毕若没有更多数据时显示的提醒内容；
		auto: true,//可选,默认false.首次加载自动上拉刷新一次
		callback: pullupSearchs
	}
})

//侧滑 商品信息
function pullupSearchs() {
	load.start();
	mui.post('/index.php/webReturnOrderSearch/', {
		where: document.getElementById('searchInput').value,
		page: ++count2,
		limit: limit_num
	}, function (data) {
		//console.log(data);
		if (data.code == 200) {
			setTimeout(function () {
				mui('#offCanvasSideScroll').pullRefresh().endPullupToRefresh((count2 > (data.data.count / limit_num))); //参数为true代表没有更多数据了。
				if (data.data.count == 0) {
					var wares = document.createElement('div');
					wares.className = 'mui-row history-record';
					wares.innerHTML = '<div class="mui-col-xs-12 mui-col-sm-12"><p style="text-align: center;">暂无数据</p></div>'
					document.getElementById('xx').appendChild(wares);
				} else {
					//服务器返回响应，根据响应结果，分析是否登录成功；
					for (var i = 0; i < data.data.data.length; i++) {
						if (data.data.data[i].img == "") {
							data.data.data[i].img = '../images/sp1.png';
						}
						var wares = document.createElement('div');
						wares.className = 'mui-row history-record';
						wares.innerHTML = '<div class="mui-col-xs-6 mui-col-sm-6"><p>货号：' + data.data.data[i].code + '</p></div>' +
							'<div class="mui-col-xs-6 mui-col-sm-6"><p>名称：' + data.data.data[i].name + '</p></div>' +
							'<div class="mui-col-xs-6 mui-col-sm-6"><p>尺码：' + data.data.data[i].sizes + '</p></div>' +
							'<div class="mui-col-xs-6 mui-col-sm-6"><p>颜色：' + data.data.data[i].color + '</p></div>' +
							'<div class="mui-col-xs-6 mui-col-sm-6"><p>售价：' + data.data.data[i].price + '</p></div>' +
							'<div class="mui-col-xs-6 mui-col-sm-6"><p>条码：' + data.data.data[i].bar_code + '</p></div>' +
							'<div class="mui-col-xs-12 mui-col-sm-12"><p style="border-bottom: 1px solid #EEE;width: 98%;margin-bottom: 10px;"></p></div>' +
							'<div class="mui-col-xs-12 mui-col-sm-12">' +
							'<h4>点击选取你需要的产品图片</h4>' +
							'</div>' +
							'<div class="container">' +
							'<div class="z_photo">' +
							'<div class="z_addImg"><img name="' + data.data.data[i].name + '" code="' + data.data.data[i].bar_code + '" price="' + data.data.data[i].price + '" path="' + data.data.data[i].img + '" src="' + data.data.data[i].img + '"></div>' +
							// '<div class="z_addImg"><img name="' + data.data.data[i].name + '" code="' + data.data.data[i].bar_code + '" price="' + data.data.data[i].price + '" path="../images/1-3.jpg" src="../images/1-3.jpg"></div>' +
							'</div>' +
							'</div>'
						document.getElementById('xx').appendChild(wares);
					}
				}
				load.stop();
			}, 1000);
		}
	}, 'json');
}
//获取方案id
var imgpro = 1;
function img_programme(obj) {
	// console.log(obj);
	imgpro = obj;
}

mui('#segmentedControl').on('tap', '.mui-control-item', function () {	
	imgpro = this.getAttribute("numbe"); //获取id
	// console.log(imgpro);
})

//点击商品图片  将商品图片添加到缓存里
mui("#xx").on('tap', 'img', function () {
	gname = this.getAttribute("name");
	gcode = this.getAttribute("code");
	paths = this.getAttribute("path");
	pricep = this.getAttribute("price");
	// console.log(gname +','+ gcode +','+ paths +','+ pricep);
	imgpath(gname, gcode, paths, pricep);
})

function imgpath(gname, gcode, paths, pricep) {
	load.start();
	mui.post('/index.php/webReturnOrderAddCache/', {
		programme: imgpro, //方案1,2....
		goods_name: gname, //商品名称
		goods_code: gcode, //商品条码
		goods_img: paths, //商品图片地址
		price: pricep //售价
	}, function (data) {
		// console.log(data);
		if (data.code == 200) {
			let imgarr = {
				delete_id: data.data.delete_id,
				goods_code: gcode, 
				goods_img: paths,
				price: pricep
			};
			for (let i = 0; i < jsonarr.length; i++) {
				if (jsonarr[i].id == imgpro || jsonarr[i].programme == imgpro) {
					jsonarr[i].img.push(imgarr);
				}
			}
			dpimgf(dpimg, data.data.delete_id, gcode, paths, pricep);
			//服务器返回响应，根据响应结果，分析是否登录成功；
			mui('#offCanvasWrapper').offCanvas('close');
		} else {
			mui.toast('添加错误，请重试！！！')
		}
		load.stop();
	}, 'json');
}

/****************************************************************************************************/
//服装搭配 添加搭配图片
function dpimgf(dpimg, delete_code, gcode, paths, pricep) {
	var divs = document.createElement("div");
	divs.innerHTML = '<a id="' + delete_code + '" class="mui-icon mui-icon-closeempty"></a>' +
		'<img src="' + paths + '" alt="' + gcode + '" class="pimg" />' +
		'<div class="imgtxt"><p>' + gcode + '</p><p>￥' + pricep + '</p></div>'
		dpimg.appendChild(divs);
	//js 禁止唤醒手机键盘
	document.activeElement.blur();
}
var deid = "";
//图片删除
mui('#programmeControl').on('tap', '.mui-icon-closeempty', function () {
	deid = this.getAttribute("id");
	load.start();
	delImg(deid);
})
function delImg(deid){
	if (deid == "") {
		// console.log('图片为空，删除方案');
	} else {
		mui.post('/index.php/webReturnOrderDelCache/', {
			id: deid
		}, function (data) {
			//服务器返回响应，根据响应结果，分析是否登录成功；
			if (data.code == 200) {
				for (let i = 0; i < jsonarr.length; i++) {
					for (let j = 0; j < jsonarr[i].img.length; j++) {
						if (jsonarr[i].img[j].delete_id == deid){						
							deid = jsonarr[i].img[j].delete_id;
							jsonarr[i].img.splice(j, 1);
							document.getElementById(deid).parentNode.remove();
							mui.toast(data.msg)
						}						
					}
				}
			} 
			load.stop();
		}, 'json');
	}
}

//图片放大
mui("#programmeControl").on('tap', '.pimg', function () {
	var _this = this;
	var gcode_alt = this.getAttribute("alt");
	imgShow("#outerdiv", "#innerdiv", "#bigimg", _this, gcode_alt);
})

function imgShow(outerdiv, innerdiv, bigimg, _this, gcode_alt) {
	var src = _this.src;//获取当前点击的pimg元素中的src属性  
	$(bigimg).attr("src", src);//设置#bigimg元素的src属性  

	/*获取当前点击图片的真实大小，并显示弹出层及大图*/
	$("<img/>").attr("src", src).load(function () {
		var windowW = $(window).width();//获取当前窗口宽度  
		var windowH = $(window).height();//获取当前窗口高度  
		var realWidth = this.width;//获取图片真实宽度  
		var realHeight = this.height;//获取图片真实高度  
		var imgWidth, imgHeight;
		var scale = 0.8;//缩放尺寸，当图片真实宽度和高度大于窗口宽度和高度时进行缩放  

		if (realHeight > windowH * scale) {//判断图片高度  
			imgHeight = windowH * scale;//如大于窗口高度，图片高度进行缩放  
			imgWidth = imgHeight / realHeight * realWidth;//等比例缩放宽度  
			if (imgWidth > windowW * scale) {//如宽度扔大于窗口宽度  
				imgWidth = windowW * scale;//再对宽度进行缩放  
			}
		} else if (realWidth > windowW * scale) {//如图片高度合适，判断图片宽度  
			imgWidth = windowW * scale;//如大于窗口宽度，图片宽度进行缩放  
			imgHeight = imgWidth / realWidth * realHeight;//等比例缩放高度  
		} else {//如果图片真实高度和宽度都符合要求，高宽不变  
			imgWidth = realWidth;
			imgHeight = realHeight;
		}
		$(bigimg).css("width", imgWidth);//以最终的宽度对图片缩放  

		var w = (windowW - imgWidth) / 2;//计算图片与窗口左边距  
		var h = (windowH - imgHeight) / 2;//计算图片与窗口上边距  
		$(innerdiv).css({ "top": h, "left": w });//设置#innerdiv的top和left属性  
		$(outerdiv).fadeIn("fast");//淡入显示#outerdiv及.pimg  

		if ($(outerdiv).find("p").length == 0) { //判断 outerdiv 的节点下面是否有 p 标签：
			var ptext = document.createElement("p");
			ptext.style = 'color: #fff;font-size: 16px;text-align: center; margin-top: 28px;';
			ptext.innerHTML = gcode_alt;
			document.getElementById('outerdiv').appendChild(ptext);
		} else {
			$(outerdiv).find("p")[0].innerHTML = gcode_alt;
		}

	});

	mui('body').on('tap', outerdiv, function () { //再次点击淡出消失弹出层  
		$(this).fadeOut("fast");
	})
}

/****************************************************************************************************/
//保存 提交 创建
// 保存
//获取提交的信息
function baocun() {
	var datename = document.getElementById("dateName").value;//获取姓名
	var datetime = document.getElementById("dateTime").value;//获取时间
	var shoppingcode = all_shopping.options[all_shopping.selectedIndex].value;//获取导购卡号
	var shoppingname = all_shopping.options[all_shopping.selectedIndex].innerText;//获取导购姓名
	// console.log(jsonarr);
	//console.log(vcode+','+datename+','+datetime+','+shoppingcode+','+shoppingname+','+hxtxt+','+wttxt+','+hstxt);
	// vip_code 卡号， vip_name 姓名，execution_time 执行时间，shopping_code 导购卡号，shopping_name 导购姓名，core 核心卖点，problem 问题
	if (datename == "" || datename == undefined) {
		mui.alert('', '客户名不能为空！', function () { });
	} else if (datetime == "" || datetime == undefined) {
		mui.alert('', '时间不能为空！', function () { });
	} else if (shoppingname == "" || shoppingname == undefined || shoppingname == '导购选择') {
		mui.alert('', '请选择导购！', function () { });
	} else {
		load.start();
		mui.post('/index.php/webReturnOrderAddOrder/', {
			vip_code: vcode,
			vip_name: datename,
			execution_time: datetime,
			shopping_code: shoppingcode,
			shopping_name: shoppingname,
			programme_arr: jsonarr
		}, function (data) {
			if (data.code == 200) {
				load.stop();
				mui.alert('', '保存成功！', function () {
					location.href = '../homepage/return_order.html';
				});
			}
		}, 'json');
	}

}

// ----------------------------------------------------------------------------------
//赋值
function seeorder(codeid) {
	load.start();
	mui.post('/index.php/webReturnSeeOrder/', {
		code: codeid
	}, function (data) {
		//服务器返回响应，根据响应结果，分析是否登录成功；
		// console.log(data);
		if (data.code == 200) {
			setTimeout(function () {
				vcode = data.data.order.vip_code;
				document.getElementById("dateName").name = data.data.order.id;
				document.getElementById("dateName").value = data.data.order.vip_name; //返单客户姓名
				document.getElementById("dateName").placeholder = data.data.order.vip_name;

				document.getElementById("dateTime").value = data.data.order.execution_time; //执行时间
				document.getElementById("dateTime").placeholder = data.data.order.execution_time;

				var option = document.createElement('option');
				option.value = data.data.order.shopping_code;
				option.innerHTML = data.data.order.shopping_name; //执行导购
				document.getElementById("all_shopping").appendChild(option);
				onload_select();
				// mui-active
				parr.splice(0,parr.length);
				jsonarr.splice(0,jsonarr.length);
				// // console.log(data.data.retuen);
				// // console.log(data.data.goods);
				var programme_num = "";
				for (i = 0; i < data.data.retuen.length; i++) {
					var a = document.createElement('a');
					if (i == 0){
						a.className = 'mui-control-item mui-active';
						programme_num = data.data.retuen[i].programme;
					} else {
						a.className = 'mui-control-item';
					}
					a.innerHTML = '方案' + data.data.retuen[i].programme;
					a.setAttribute('numbe', data.data.retuen[i].programme);
					programme_a.appendChild(a);
					// programme_num = data.data.retuen[i].programme;
					add_programme(parseInt(data.data.retuen[i].programme));
					jsonarr[i].id = data.data.retuen[i].programme;
					jsonarr[i].core = data.data.retuen[i].core;
					jsonarr[i].top = data.data.retuen[i].top;
					jsonarr[i].collocation = data.data.retuen[i].collocation;
					jsonarr[i].problem = data.data.retuen[i].problem;
					jsonarr[i].talking_skill = data.data.retuen[i].talking_skill;
					jsonarr[i].name = '方案'+ data.data.retuen[i].programme;
					for (m = 0; m < data.data.goods.length; m++) {
						if ( data.data.retuen[i].programme == data.data.goods[m].programme){
							// imgpro = data.data.goods[m].programme;
							// gname = data.data.goods[m].goods_name;
							// gcode = data.data.goods[m].goods_code;
							// paths = data.data.goods[m].goods_img;
							// pricep = data.data.goods[m].price;
							// mui.post('/index.php/webReturnOrderAddCache/', {
							// 	programme: imgpro, //方案1,2....
							// 	goods_name: gname, //商品名称
							// 	goods_code: gcode, //商品条码
							// 	goods_img: paths, //商品图片地址
							// 	price: pricep //售价
							// }, function (data) {
							// 	// console.log(data);
								
							// }, 'json');

							// function imgpath(gname, gcode, paths, pricep) {}

							let imgarr = {
								delete_id: data.data.goods[m].delete_id,
								goods_code: data.data.goods[m].goods_code, 
								goods_img: data.data.goods[m].goods_img,
								price: data.data.goods[m].price
							};
							jsonarr[i].img.push(imgarr);
						}
					}
					parr.push(parseInt(data.data.retuen[i].programme));					
				}				
				center_txt(parseInt(programme_num));
				read_programme(parseInt(programme_num));

				if (data.data.retuen.length != 0){
					// tempArr();
					num = data.data.retuen.length;
					jishu = num+1;
					judge = true;
				} else {
					create_a(num);
				}
				// console.log(parr); 
				// console.log(jsonarr);
				load.stop();

			}, 1000);
		}
	}, 'json');
}

//获取修改的信息 
function editorder(codeid) {

	var idid = document.getElementById("dateName").name;
	var datename = document.getElementById("dateName").value;//获取姓名
	var datetime = document.getElementById("dateTime").value;//获取时间
	var shoppingcode = all_shopping.options[all_shopping.selectedIndex].value;//获取导购卡号
	var shoppingname = all_shopping.options[all_shopping.selectedIndex].innerText;//获取导购姓名
	//console.log(vcode+','+datename+','+datetime+','+shoppingcode+','+shoppingname+','+hxtxt+','+wttxt+','+hstxt);
	// vip_code 卡号， vip_name 姓名，execution_time 执行时间，shopping_code 导购卡号，shopping_name 导购姓名，core 核心卖点，problem 问题  talking_skill 话术
	// console.log(idid);
	// console.log(codeid);
	// console.log(vcode);
	// console.log(datename);
	// console.log(datetime);
	// console.log(shoppingcode);
	// console.log(shoppingname);
	// console.log(jsonarr);
	if (datename == "" || datename == undefined) {
		mui.alert('', '客户名不能为空！', function () { });
	} else if (datetime == "" || datetime == undefined) {
		mui.alert('', '时间不能为空！', function () { });
	} else if (shoppingname == "" || shoppingname == undefined || shoppingname == '导购选择') {
		mui.alert('', '请选择导购！', function () { });
	} else {
		load.start();
		mui.post('/index.php/webReturnEditOrder/', {
			id: idid,
			code: codeid,
			vip_code: vcode,
			vip_name: datename,
			execution_time: datetime,
			shopping_code: shoppingcode,
			shopping_name: shoppingname,
			programme_arr: jsonarr
		}, function (data) {
			if (data.code == 200) {
				load.stop();
				mui.alert('', '保存成功！', function () {
					location.href = '../homepage/return_plan.html';
				});
			}
		}, 'json');
	}
}
// 会员资料 新建返单 ---------------------------------------------------------------------------------------------------------------------------
var ppp = localStorage.getItem("add_fandan_ziliao");
var ppp2 = localStorage.getItem("add_fandan_ziliao2");
function kolko() {
	// 姓名
	let user_name = ppp.substring(0, ppp.indexOf(','));
	let user_name_code = ppp2.substring(0, ppp2.indexOf(','));
	vcode = user_name_code;
	// 形象顾问
	let consultant = ppp.substring(ppp.indexOf(',') + 1, ppp.length);
	let consultant_code = ppp2.substring(ppp2.indexOf(',') + 1, ppp2.length)
	if (ppp != "" && ppp2 != "") {
		mui("#dateName")[0].value = user_name;		
		if (consultant != "null" || consultant_code != ""){
			var option = document.createElement('option');
			option.value = consultant_code;
			option.innerHTML = consultant;
			all_shopping.appendChild(option);
			mui("#all_shopping")[0].insertBefore(option, mui("#all_shopping")[0].firstChild);
		}	
	}else{
		// console.log('kong')
	}
}