/*
 * Created on 2011-09-05
 * @author: Yewang
 * @desc: 弹出窗口插件popUp
 * @depends: popUp.css
 * @example:
	$("#id").click(function(){
		$(this).popUp({
			width:300,
			title:'popUp title',
			content:'popUp Content',
			buttons:'<span class="popBtns blueBtn callbackBtn">确认</span><span class="popBtns closeBtn">取消</span>',
			mask:false,
			maskMode:true,
			callback:function(){}
		});
	});
 * @param:
	{
		width: 弹出窗口宽度,
		title: 窗口标题(text格式),
		content: 窗口内容(html或text格式),
		buttons: 插入的按钮,
		mask: 是否遮罩,
		maskMode: 点击遮罩背景是否取消弹出层,
		callback: 点击callbackBtn, 所执行回调函数
	}
	
	* Update desc:
		content 可以传递objec对象  by linchangyuan
*/
 (function($){
	var ie6 = ($.browser.msie &&($.browser.version =='6.0')) ? true : null;

	function POPUP(options) {
		this.view(options);
	}
	POPUP.prototype = {
		options: {
			width:300,
			title:'PopUp',
			content:'Content',
			buttons:'<span class="popBtns blueBtn callbackBtn">确认</span><span class="popBtns closeBtn">取消</span>',
			mask:false,
			maskMode:true
			//callback:function(){}
		},

		view: function(options) {
			var _this = this,
				opts = $.extend({}, _this.options, options),
				pop_id = 'popUp',
				pop_mask = 'popMask';
			if(opts.subPop) {
				pop_id += 'Sub';
				pop_mask += 'Sub';
			}
			if($('#'+pop_id).length < 1){
				$('body').append($('<div id="'+pop_id+'" class="popUpWindow"><table class="p_table">'
					+'<tr><td class="tl corner"></td><td class="gary_bg"></td><td class="tr corner"></td></tr>'
					+'<tr><td class="gary_bg"></td><td><h3 class="popTitle"><span></span></h3><div class="popCont"></div><div class="popBtnsWrap"></div></td><td class="gary_bg"></td></tr>'
					+'<tr><td class="bl corner"></td><td class="gary_bg"></td><td class="br corner"></td></tr>'
				+'</table></div>'));
				if(opts.noTitle === true) {
					$('#'+pop_id).find('h3').remove();
				}
				if(opts.noButton === true) {
					$('#'+pop_id).find('div.popBtnsWrap').remove();
				}
				if(ie6) {
					$('#'+pop_id).append('<iframe class="frameBg"></iframe>');
				}
			}
			var pop = $('#'+pop_id),
				content = opts.content;
			if(typeof(content) == "object"){
				content.show();
			}
			
			
			pop.find("div.popCont").empty().html(content);

			pop.find("div.popBtnsWrap").html(opts.buttons);
            if(pop.find('h3.popTitle').has('span').length>0){
                pop.find('h3.popTitle').children().text(opts.title);
            }else{//当title容器被改变的时候 by wangyuefei
                var newtitle=$('<span></span>');
                newtitle.text(opts.title);
                pop.find('h3.popTitle').html(newtitle);
            }
			if(opts.height){
				pop.find("div.popCont").css({height:opts.height,overflow:"auto"});
			}
			opts.height = opts.height||pop.height();
			pop.css({'width':opts.width+'px','margin':'-'+opts.height/2+'px 0 0 '+'-'+opts.width/2+'px'}).show();
			if(ie6) {
				pop.find('iframe.frameBg').height(pop.height());
			}
			
			if(opts.titleWidth){
				pop.find('h3.popTitle').children().width(opts.titleWidth);
			}
			if(opts.mask == true) {
				if($('#'+pop_mask).length < 1)$('body').append('<div id="'+pop_mask+'" class="popUpMask"/>');
				var bodyH = $('body').height(),
					windowH = $(window).height(),
					maskH = (bodyH > windowH) ? bodyH : windowH;
				$('#'+pop_mask).width($('body').width()).height(maskH).show();
				if(opts.maskMode == true){
					$('#'+pop_mask).click(function(){
						_this.closePop(opts);
					});
				}
			}
			
			// if ie6
			if(ie6) {
				var h = $(window).height()/2;
				var mask = $('#'+pop_mask);
				pop.css('top',h+$(window).scrollTop());
				mask.css({'width':$(window).width(),'height':$(window).height(),'top':$(window).scrollTop()});
				$(window).scroll(function(){
					pop.css('top',h+$(window).scrollTop());
					mask.css('top',$(window).scrollTop());
				});
			}
			
			//有callback则执行callback
			pop.find('.callbackBtn').click(function(){
				if(opts.callback)opts.callback();
			});
			
			//close button
			pop.find('.closeBtn').click(function(){
				_this.closePop(opts);
			});
			
		},
		closePop: function(opts){
			if(opts.closeCallback)opts.closeCallback();
			if(opts.subPop) {
				$.closeSubPop();
			} else {
				$.closePopUp();
			}
		}
	};
	$.fn.popUp = function(settings) {
		var opts = $.extend({},settings);
		return new POPUP(opts);
	};
	
	$.fn.subPopUp = function(settings) {
		var opts = $.extend({},settings,{subPop:true});
		return new POPUP(opts);
	};
	
	//close popUp方法
	$.closePopUp = function() {
		$('#popUp').hide();
		$('#popMask').hide();
	};
	//close popUp方法
	$.closeSubPop = function() {
		$('#popUpSub').hide();
		$('#popMaskSub').hide();
	};
	//重置 popUp方法
	$.resetPopUp = function() {
		var pop = $('#popUp');
		var subPop = $('#popUpSub');
		if(pop[0])pop.css({'margin':'-'+pop.height()/2+'px 0 0 '+'-'+pop.width()/2+'px'});
		if(subPop[0])subPop.css({'margin':'-'+subPop.height()/2+'px 0 0 '+'-'+subPop.width()/2+'px'});
	};
	
	//$.alert方法
	$.alert = function(content,title){
		if($('#alertWindow').length < 1){
			$('body').append('<div id="alertWindow" class="popUpWindow"><table class="p_table">'
				+'<tr><td class="tl corner"></td><td class="gary_bg"></td><td class="tr corner"></td></tr>'
				+'<tr><td class="gary_bg"></td><td><h4 class="popTitle">提示信息</h4><div class="alertCont"></div><div class="popBtnsWrap"><span class="popBtns blueBtn closeBtn">确定</span></div></td><td class="gary_bg"></td></tr>'
				+'<tr><td class="bl corner"></td><td class="gary_bg"></td><td class="br corner"></td></tr>'
			+'</table></div><div id="alertMask" class="popUpMask"></div>');
			if(ie6) {
				$('#alertWindow').append('<iframe class="frameBg"></iframe>');
			}
		}
		var alertWindow = $('#alertWindow');
		var alertMask = $('#alertMask');
		if(title){
			alertWindow.find('h4').text(title);
		}
		alertWindow.find('.alertCont').html(content);
		alertWindow.css({'margin':'-'+alertWindow.height()/2+'px 0 0 '+'-'+alertWindow.width()/2+'px'}).show();
		var bodyH = $('body').height(),
			windowH = $(window).height(),
			maskH = (bodyH > windowH) ? bodyH : windowH;
		alertMask.width($('body').width()).height(maskH).show();
		// if ie6
		if(ie6){
			alertWindow.find('iframe.frameBg').height(alertWindow.height());
			var h = $(window).height()/2;
			alertWindow.css('top',h+$(window).scrollTop());
			alertMask.css({'width':$(window).width(),'height':$(window).height(),'top':$(window).scrollTop()});
			$(window).scroll(function(){
				alertWindow.css('top',h+$(window).scrollTop());
				alertMask.css('top',$(window).scrollTop());
			});
		}
		//close button
		alertWindow.find('.closeBtn').click(function(){
			alertWindow.hide();
			alertMask.hide();
		});
	};
	
	//$.confirm方法
	$.confirm = function(title,content,callback){
		if($('#confirmWindow').length < 1){
			$('body').append('<div id="confirmWindow" class="popUpWindow"><table class="p_table">'
				+'<tr><td class="tl corner"></td><td class="gary_bg"></td><td class="tr corner"></td></tr>'
				+'<tr><td class="gary_bg"></td><td><h4 class="popTitle">确认信息</h4><div class="alertCont"></div><div class="popBtnsWrap"><span class="popBtns blueBtn callbackBtn">确定</span><span class="popBtns closeBtn">取消</span></div></td><td class="gary_bg"></td></tr>'
				+'<tr><td class="bl corner"></td><td class="gary_bg"></td><td class="br corner"></td></tr>'
			+'</table></div><div id="confirmMask" class="popUpMask"></div>');
			if(ie6) {
				$('#confirmWindow').append('<iframe class="frameBg"></iframe>');
			}
		}
		var confirmWindow = $('#confirmWindow');
		var confirmMask = $('#confirmMask');
		if(title){
			confirmWindow.find('h4').text(title);
		}
		confirmWindow.find('.alertCont').html(content);
		confirmWindow.css({'margin':'-'+confirmWindow.height()/2+'px 0 0 '+'-'+confirmWindow.width()/2+'px'}).show();
		var bodyH = $('body').height(),
			windowH = $(window).height(),
			maskH = (bodyH > windowH) ? bodyH : windowH;
		confirmMask.width($('body').width()).height(maskH).show();
		// if ie6
		if(ie6){
			confirmWindow.find('iframe.frameBg').height(confirmWindow.height());
			var h = $(window).height()/2;
			confirmWindow.css('top',h+$(window).scrollTop());
			confirmMask.css({'width':$(window).width(),'height':$(window).height(),'top':$(window).scrollTop()});
			$(window).scroll(function(){
				confirmWindow.css('top',h+$(window).scrollTop());
				confirmMask.css('top',$(window).scrollTop());
			});
		}
		//close button
		confirmWindow.find('.closeBtn').unbind('click').bind('click', function(){
			confirmWindow.hide();
			confirmMask.hide();
		});
		
		//callback button
		confirmWindow.find('.callbackBtn').unbind('click').bind('click', function(){
			if(callback) {
				callback();
			}
			confirmWindow.hide();
			confirmMask.hide();
		});
	};
 })(jQuery);