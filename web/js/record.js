/* 门店业绩------------------------------------------------------------------------------- */
//时间选择
var time_xz = document.getElementById("timexz");
var nav_time = document.getElementById("nav_time");

function title_time() {
	time_xz.addEventListener("click", function() {
		if (time_xz.classList.contains('mui-active')) { //有这个类
			nav_time.style.display = "none";
			time_xz.classList.remove("mui-active");
			public_collect();
			public_area();
			public_shop();
		} else {
			nav_time.style.display = "block";
			time_xz.classList.add("mui-active");
			public_collect();
			public_area();
			public_shop();
		}
	})
}
//点击完成
function wc() {
	nav_bg();
}

//店铺选择
var shop_xz = document.getElementById("shopxz");
var nav_shop = document.getElementById("nav_shop");

function title_shop() {
	shop_xz.addEventListener("click", function() {
		if (shop_xz.classList.contains('mui-active')) { //有这个类
			nav_shop.style.display = "none";
			shop_xz.classList.remove("mui-active");
			public_time();
			public_collect();
			public_area();
		} else {
			nav_shop.style.display = "block";
			shop_xz.classList.add("mui-active");
			public_time();
			public_collect();
			public_area();
		}
	})
	for (var dplist = 0; dplist < 10; dplist++) {
		var ul = document.createElement('ul');
		var li = document.createElement('li');
		li.innerHTML = '裤业1'+ dplist +'店<span class=""></span>'
		ul.appendChild(li);
		document.getElementById('dp_ul').appendChild(ul);
	}

	var all_li = document.getElementById('dp_ul').getElementsByTagName('li');
	for (var i = 0; i < all_li.length; i++) {
		dpacti(i);
	}

	function dpacti(i) {
		all_li[i].addEventListener("click", function() {
			var span = all_li[i].getElementsByTagName("span");
			for (var j = 0; j < span.length; j++) {
				if (span[j].className == "") { //有这个类
					span[j].className = "mui-icon mui-icon-checkmarkempty icon_css mui-active";
				} else {
					span[j].className = "";
				}
			}
		})
	}
	//点击确定
	document.getElementById("dpsure").addEventListener("click", function() {
		nav_bg();
	})

}

//导购选择
var collect_xz = document.getElementById("collectxz");
var nav_collect = document.getElementById("nav_collect");

function title_collect() {
	collect_xz.addEventListener("click", function() {
		if (collect_xz.classList.contains('mui-active')) { //有这个类
			nav_collect.style.display = "none";
			collect_xz.classList.remove("mui-active");
			public_time();
			public_area();
			public_shop();
		} else {
			nav_collect.style.display = "block";
			collect_xz.classList.add("mui-active");
			public_time();
			public_area();
			public_shop();
		}
	})

	for (var dglist = 0; dglist < 10; dglist++) {
		var ul = document.createElement('ul');
		var li = document.createElement('li');
		li.innerHTML = '导购人：'+ dglist +'<span class=""></span>'
		ul.appendChild(li);
		document.getElementById('dg_ul').appendChild(ul);
	}

	var dg_all = document.getElementById('dg_ul').getElementsByTagName('li');
	for (var i = 0; i < dg_all.length; i++) {
		dgacti(i);
	}

	function dgacti(i) {
		dg_all[i].addEventListener("click", function() {
			var span = dg_all[i].getElementsByTagName("span");
			for (var j = 0; j < span.length; j++) { 
				if (span[j].className == "") { //有这个类
					span[j].className = "mui-icon mui-icon-checkmarkempty icon_css mui-active";
				} else {
					span[j].className = "";
				}
			}
		})	
	}
	//点击确定
	document.getElementById("dgsure").addEventListener("click", function() {
		nav_bg();
	})
}

//状态
var area_xz = document.getElementById("areaxz");
var nav_area = document.getElementById("nav_area");

function title_area() {
	area_xz.addEventListener("click", function() {
		if (area_xz.classList.contains('mui-active')) { //有这个类
			nav_area.style.display = "none";
			area_xz.classList.remove("mui-active");
			public_time();
			public_collect();
			public_shop();
		} else {
			nav_area.style.display = "block";
			area_xz.classList.add("mui-active");
			public_time();
			public_collect();
			public_shop();
		}
	})
	//状态循环导出且多选设置
	var ul_all = document.getElementById('u_l').getElementsByTagName('li');

	// console.log(ul_all[0].innerText.replace(/(^\s*)|(\s*$)/g, ""));
	// console.log(ul_all[1].innerText.replace(/(^\s*)|(\s*$)/g, ""));
	
	for (var i = 0; i < ul_all.length; i++) {
		pqacti(i);
	}

	function pqacti(i) {
		ul_all[i].addEventListener("click", function() {
			var span = ul_all[i].getElementsByTagName("span");
			for (var j = 0; j < span.length; j++) { 
				if (span[j].className == "") { //有这个类
					span[j].className = "mui-icon mui-icon-checkmarkempty icon_css mui-active";
				} else {
					span[j].className = "";
				}
			}
		})	
	}
	//点击确定
	document.getElementById("ztsure").addEventListener("click", function() {
		nav_bg();
	})
}

//背景蒙版
function nav_bg() {
	if (nav_time.style.display == 'block' && time_xz.classList.contains('mui-active')) {
		public_time();
	} else if (nav_collect.style.display == 'block' && collect_xz.classList.contains('mui-active')) {
		public_collect();
	} else if (nav_area.style.display == 'block' && area_xz.classList.contains('mui-active')) {
		public_area();
	} else if (nav_shop.style.display == 'block' && shop_xz.classList.contains('mui-active')) {
		public_shop();
	}
}
//样式
function public_time() { //时间
	nav_time.style.display = "none";
	time_xz.classList.remove("mui-active");
}

function public_collect() { //汇总
	nav_collect.style.display = "none";
	collect_xz.classList.remove("mui-active");
}

function public_area() { //片区
	nav_area.style.display = "none";
	area_xz.classList.remove("mui-active");
}

function public_shop() { //店铺
	nav_shop.style.display = "none";
	shop_xz.classList.remove("mui-active");
}

var birth = document.getElementsByClassName("time_");
// 获取当前时间
var new_star = "";
var new_end = "";

function new_data() {
	var myDate = new Date();
	if ((myDate.getMonth() + 1) < 9) {
		new_star = myDate.getFullYear() + '-' + '0' + (myDate.getMonth() + 1) + '-' + (myDate.getDate() - 1);
		new_end = myDate.getFullYear() + '-' + '0' + (myDate.getMonth() + 1) + '-' + myDate.getDate();
	} else {
		new_star = myDate.getFullYear() + '-' + (myDate.getMonth() + 1) + '-' + (myDate.getDate() - 1);
		new_end = myDate.getFullYear() + '-' + (myDate.getMonth() + 1) + '-' + myDate.getDate();
	}
	birth[0].innerHTML = new_star;
	birth[1].innerHTML = new_end;
	console.log(new_star);
	console.log(new_end);
}
//开始时间
function start_time() {
	var dtpicker = new mui.DtPicker({
		type: "date", //设置日历初始视图模式 
		beginDate: new Date(1970, 01, 01), //设置开始日期 
		endDate: new Date(2070, 01, 01), //设置结束日期 
		labels: ['年', '月', '日'], //设置默认标签区域提示语 
	});
	dtpicker.show(function(items) {
		birth[0].innerHTML = items.y.value + '-' + items.m.value + '-' + items.d.value;
		console.log(birth[0].innerHTML);
	})
}
// 结束时间
function over_time() {
	var dtpicker = new mui.DtPicker({
		type: "date", //设置日历初始视图模式 
		beginDate: new Date(1970, 01, 01), //设置开始日期 
		endDate: new Date(2170, 01, 01), //设置结束日期 
		labels: ['年', '月', '日'], //设置默认标签区域提示语 
	});
	dtpicker.show(function(items) {
		birth[1].innerHTML = items.y.value + '-' + items.m.value + '-' + items.d.value
		console.log(birth[1].innerHTML);
	})
}


