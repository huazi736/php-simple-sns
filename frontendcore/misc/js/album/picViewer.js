 /*
 * @ name: picViewer.js
 * @ author: lishijun
 * @ desc:  图片查看器JS
 *
 */


function CLASS_PICVIEWER(){
 	this.init();
}

CLASS_PICVIEWER.prototype = {
	init : function(){
		this.$openKey = $('.photoLink'); //查看器开关
		
		this.event('openPicViewer');
		this.event('windowResize');
		this.event('closePicViewer');
	},
	view : function(method,arg){
		var _class = {
			creatIframe : function(arg){
				var picView_width = parseInt($(window).width())+15; //@ 获取浏览器宽度
				var picView_height = $(window).height(); //@ 获取浏览器高度
				var picView_heightBG = $(document).height(); //@ 获取文档高度
				var offsetH = parseInt($(window).scrollTop()); //@ 滚动条距离顶部高度
				var waitTipLeft = (picView_width-15-89)/2; //@ 等待图标left位置
				var waitTipTop = (picView_height-90)/2 + offsetH; //@ 等待图标top位置

				$('body').attr('style','position:relative; overflow:hidden;').prepend('<div id="picView_bg"></div>').prepend('<div class="closePicViewerBtn" title="按ESC键关闭"></div>').children('.closePicViewerBtn').css({
				  	'position':'fixed',
				  	'top':0,
				  	'right':0,
				  	'height':'47px',
				  	'width':'47px',
				  	'background':'url('+CONFIG['misc_path']+'img/system/photo_scan_layer.png) no-repeat left top',
				  	'cursor':'pointer',
				  	'z-index':'1002'
				});

				if($.browser.msie && ($.browser.version==6.0 || $.browser.version==7.0)){
					$('body').attr('style','*overflow-y:auto;');
					picView_width += 2;
				}
				
				$('html').attr('style','*overflow:hidden'); //隐藏滚动条(IE6、IE7)

				$('#picView_bg').css({ //设置遮罩层
					'position':'absolute',
					'top':'0px',
					'left':'0px',
					'z-index':'1000',
					'background':'#000 url('+miscpath+'img/system/waitTip.gif) no-repeat '+waitTipLeft+'px'+' '+waitTipTop+'px' 
				}).width(picView_width).height(picView_heightBG).fadeTo(0,0.8);

				$('body').prepend('<div id="picView_box"></div>'); //创建iframe容器
				$('#picView_box').css({
					'position':'fixed',
					'top':'0px',
					'left':'0px',
					'width':picView_width+'px',
					'height':picView_height+'px',
					'z-index':'1001'
				}).append('<iframe width="100%" height="100%" allowtransparency="true" id="picView_iframe"></iframe>');

				if($.browser.msie && $.browser.version==6.0){
					var scrollTop = $(window).scrollTop();
					$('#picView_box').css({'position':'absolute','top':scrollTop+'px'});
					$('.closePicViewerBtn').css({'position':'absolute','top':scrollTop+'px'});
				}

				$('#picView_iframe').attr('src',arg[0]).load(function(){
					$('#picView_bg').css({'background-image':'none'});
				});
			},
			closeIframe : function(arg){
				var url = window.location.href.replace(/%2526/g,'&').replace(/%253A/g,':').replace(/%252F/g,'/').replace(/%253F/g,'?').replace(/%253D/g,'=');
				var pattern = /^(\S+)&iscomment=(\d+)&jumpurl=(\S+)$/i;
				if(pattern.test(url)){
					var arr = pattern.exec(url);
					if(arr[2] === '1'){
						window.location.href = arr[1];
					}
				}
 				$('body').removeAttr('style').children('#picView_bg').remove().end().children('#picView_box').remove().end().children('.closePicViewerBtn').remove();
 				$('html').removeAttr('style'); //兼容ie6、ie7
			}
		};
		return _class[method](arg);
	},
	event : function(method,arg){
		var self = this;
		var _class = {
			windowResize : function(){
				$(window).resize(function(){
					$('#picView_box').css({width:$(window).width(),height:$(window).height()}); //窗口自适应大小
					$('#picView_bg').width($(document).width()).height($(document).height()); //遮罩层大小自适应文档大小
				});
			},
			openPicViewer : function(){
				self.$openKey.live('click',function(){
					self.view('creatIframe',[$(this).attr('url')]);
				});
			},
			closePicViewer : function(){
				$('body').delegate('.closePicViewerBtn','click',function(){
					self.view('closeIframe');
				}).delegate('.closePicViewerBtn','mouseover',function(){
					$(this).css({'background-position':'-48px top'});
				}).delegate('.closePicViewerBtn','mouseout',function(){
					$(this).css({'background-position':'left top'});
				});

				$(document).bind('keydown',function(e){
					if(e.keyCode === 27){
						self.view('closeIframe');
					}
				});
			}
		};
		return _class[method](arg);
	}
}

$(function(){
	new CLASS_PICVIEWER();
})

