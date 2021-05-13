// QQ表情插件

(function ($) {
	$.fn.qqFace = function (options) {
		var defaults = {
			id: 'facebox',
			path: 'face/',
			assign: 'content',
			tip: 'em_'
		};
		var option = $.extend(defaults, options);
		var assign = $('#' + option.assign);
		var id = option.id;
		var path = option.path;
		var tip = option.tip;
		if (assign.length <= 0) {
			alert('缺少表情赋值对象。');
			return false;
		}
		$(this).click(function (e) {
			$('#content_chat')[0].style.bottom="285px";
			$('#content_chat')[0].style.paddingTop="285px";
			var strFace, labFace;
			if ($('#' + id).length <= 0) {
				strFace = 
					'<table border="0" id="' + id +'" cellspacing="0" cellpadding="0" style="width:100%;"><tr>';
				for (var i = 1; i <= 75; i++) {
					labFace = '[' + tip + i + ']';
					strFace += '<td style="width:10%;"><img src="' + path + i + '.gif" onclick="$(\'#' + option.assign + '\').setCaret();$(\'#' + option.assign + '\').insertAtCaret(\'' + labFace + '\');" /></td>';
					if (i % 10 == 0) strFace += '</tr><tr>';
				}
				strFace += '</tr></table>';
			}
			// $(this).parent().append(strFace);
			console.log(strFace);
			$("#section_bq").append(strFace);
			// var offset = $(this).position();
			// var top = offset.top + $(this).outerHeight();
			// $('#'+id).css('top',top);
			// $('#'+id).css('left',offset.left);
			$('#' + id).show();
			e.stopPropagation();
			text_display();
		});
		//点击任何地方关闭
		// $(document)[0].click(function(){
		// 	$('#'+id).hide();
		// 	$('#'+id).remove();
		// 	// //控制footer还原
		// 	// bnt2.style.bottom = "0px";
		// 	// //控制还原
		// 	// content_chat.style.height = document.documentElement.clientHeight + 'px';
		// 	// console.log("dianji")
		// });
	};

})(jQuery);

jQuery.extend({
	unselectContents: function () {
		if (window.getSelection)
			window.getSelection().removeAllRanges();
		else if (document.selection)
			document.selection.empty();
	}
});
jQuery.fn.extend({
	selectContents: function () {
		$(this).each(function (i) {
			console.log(i);
			var node = this;
			var selection, range, doc, win;
			if ((doc = node.ownerDocument) && (win = doc.defaultView) && typeof win.getSelection != 'undefined' && typeof doc.createRange != 'undefined' && (selection = window.getSelection()) && typeof selection.removeAllRanges != 'undefined') {
				range = doc.createRange();
				range.selectNode(node);
				if (i == 0) {
					selection.removeAllRanges();
				}
				selection.addRange(range);
			} else if (document.body && typeof document.body.createTextRange != 'undefined' && (range = document.body.createTextRange())) {
				range.moveToElementText(node);
				range.select();
			}
		});
	},

	setCaret: function () {

		if (!$.browser.msie) return;
		var initSetCaret = function () {
			var textObj = $(this).get(0);
			textObj.caretPos = document.selection.createRange().duplicate();
		};
		$(this).click(initSetCaret).select(initSetCaret).keyup(initSetCaret);
	},
	insertAtCaret: function (textFeildValue) {

		var textObj = $(this).get(0);
		if (document.all && textObj.createTextRange && textObj.caretPos) {
			var caretPos = textObj.caretPos;
			caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == '' ?
				textFeildValue + '' : textFeildValue;

		} else if (textObj.setSelectionRange) {
			var rangeStart = textObj.selectionStart;
			var rangeEnd = textObj.selectionEnd;
			var tempStr1 = textObj.value.substring(0, rangeStart);
			var tempStr2 = textObj.value.substring(rangeEnd);
			textObj.value = tempStr1 + textFeildValue + tempStr2;
			textObj.focus();
			var len = textFeildValue.length;
			textObj.setSelectionRange(rangeStart + len, rangeStart + len);
			textObj.blur();
		} else {
			textObj.value += textFeildValue;
		}
		close();
	}
});
function close() {
	$('#facebox').hide();
	$('#facebox').remove();
	$('#content_chat')[0].style.bottom = "50px";
	$('#content_chat')[0].style.paddingTop = "50px";
}
function text_display(){
	$("#msg-mic")[0].style.display ="";
	$("#msg-bars")[0].style.display ="none";

	$("#msg-text")[0].style.display ="";
	$("#msg-sound")[0].style.display ="none"
}


