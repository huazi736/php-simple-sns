/**
author : zhupinglei
data : 2012/08/07
desc : dkEditor.js
version : v1.0
depend : jquery.js
**/
(function($){

function _dkEditor(here,opts,index){
	this.$e = $(here);
	this.opts = opts;
	this.index = index;
	this.init();
}

_dkEditor.prototype = {
	init : function(){
		var _this = this;
		//代码结构渲染
		var editorStr = '<div id="dkEditor">'+
							'<div class="editorBtns"><ul class="clearfix"></ul></div>'+
							'<div class="editorCon">'+
								'<iframe src="about:blank" frameborder="0" id="editorIframe"></iframe>'+
							'</div>'+
						'</div>';
		_this.$e.append(editorStr);
		var btnStr = '';
		for(var i = 0; i < _this.opts.btnId.length; i++){
			btnStr += '<li id="'+_this.opts.btnId[i]+'"><img src="'+CONFIG['misc_path']+'img/plug-img/dkEditor/'+_this.opts.btnId[i].toLowerCase()+'.png" /></li>';
		}
		_this.$e.find('.editorBtns ul').html(btnStr);
		//开启iframe编辑功能
		var _frame = document.getElementById('editorIframe');
		_window = _frame.contentWindow;
		_window.document.designMode = 'on';
		_window.document.canHaveHTML = true;
		_window.document.open();
		_window.document.writeln('<html><head><meta charset="UTF-8" /><style>body,p{padding:0; margin:0;}body{width:100%;word-wrap:break-word;}</style></head><body></body></html>');
		_window.document.close();
		//初始内容
		if(_this.opts.firstCon){
			$(_window.document.body).html(_this.opts.firstCon);
		}
		$('#editor').hide();
		_this.event();
	},
	event : function(){
		var _this = this;
		$('#dkEditor').find('.editorBtns li img').on({
			'mouseenter' : function(){
				$(this).addClass('hover');
			},
			'mouseleave' : function(){
				$(this).removeClass('hover');
			},
			'click' : function(){
				var range = _this.getRange(),
					rangeTxt = _this.rangeText(range);
				var action = $(this).parent().attr('id');
				$(_window.document.body).focus();
				_window.document.execCommand(action,false,null);
			}
		});
		$(_window.document).on({
			'click keydown keyup blur focus' : function(){
				_this.htmlFilter();
			}
		});
		$(document).click(function(){
			_this.htmlFilter();
		});
	},
	getRange : function(){
		var rng = null;
		if($.browser.msie){
			rng = _window.document.selection.createRange();
		}else{
			rng = _window.getSelection().getRangeAt(0);
		}
		return rng;
	},
	rangeText : function(range){
		var str = '';
		if($.browser.msie){
			str = range.text;
		}else{
			str = range.toString();
		}
		return str;
	},
	htmlFilter : function(){
		var tag = '*',
			tableTag = 'table,thead,tbody,tfoot,tr,th,td';
		$(_window.document.body).find('*:not(p)').attr('width','').attr('class','').attr('id','').css({'width':'','line-height':'160%'});
		var allcon = _window.document.body.innerHTML;
		$('#editor').val(allcon);
	}
}

$.fn.dkEditor = function(option){
	var opts = $.extend({},$.fn.dkEditor.defaults,option);
	return this.each(function(index){
		this.dkEditor = new _dkEditor(this,opts,index);
	});
}

$.fn.dkEditor.defaults = {
	btnId : ['Bold','Italic','Underline','Insertorderedlist','Insertunorderedlist'],
	firstCon : null
}

})(jQuery);