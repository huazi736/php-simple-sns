/*
 *update on 2011-10-24
 *@author: 李海棠
 *@version:1.0.0
 *@desc:textArea 高度自适应jQuery插件
 *@示例: $(DOM).textareaHeight();
 */
 
(function($){
	$.fn.textareaHeight = function(settings){
		//@ Start 获取textArea样式
		$(this).each(function(i) {
            var styles = {
				width:$(this).width(),
				height:$(this).height(),
				padding:{
					top:$(this).css("padding-top"),
					right:$(this).css("padding-right"),
					bottom:$(this).css("padding-bottom"),
					left:$(this).css("padding-left")
				},
				margin:{
					top:$(this).css("margin-top"),
					right:$(this).css("margin-right"),
					bottom:$(this).css("margin-bottom"),
					left:$(this).css("margin-left")
				},
				border:{
					width:$(this).css("border-top-width"),
					style:$(this).css("border-top-style"),
					color:$(this).css("border-top-color")
				},
				fontSize:$(this).css("font-size"),
				fontFamily:$(this).css("font-family"),
				lineHeight:$(this).css("line-height")
			};
			//End 获取textArea样式
			$(this).css("overflow","hidden");//取消滚动条
			$(this).css("resize","none");//取消resize
			$(this).after("<div class='textareaHeight_hid' style='display:none;'><span></span></div>");//插入隐藏DIV
			//Start 设置隐藏DIV样式
			$(this).next(".textareaHeight_hid").css({
				"width":styles.width,
				"height":"auto",
				"min-height":styles.height,
				"_height":styles.height,
				"padding-top":styles.padding.top,
				"padding-right":styles.padding.right,
				"padding-bottom":styles.padding.bottom,
				"padding-left":styles.padding.left,
				"font-size":styles.fontSize,
				"font-family":styles.fontFamily,
				"line-height":styles.lineHeight,
				"border":styles.border.width+" "+styles.border.style+" "+styles.border.color,
				"word-wrap":"break-word",
				"word-break":"normal"
			});
			if($.browser.msie&&$.browser.version==6.0){
				$(this).next(".textareaHeight_hid").css({
					"height":styles.height
				});
			}
			//End 设置隐藏DIV样式
			//Start 事件绑定
			var _val = $(this).val();
			_val=_val.replace(/\n/g,'<br />');
			var _height;
			$(this).next(".textareaHeight_hid").find("span").text(_val);
			$(this).bind({
				"change keydown keyup paste cut focus blur":function(e){
					_val = $(this).val();
					_val=_val.replace(/\n/g,'<br />');
				   $(this).next(".textareaHeight_hid").find("span").html(_val);
				   _height = $(this).next(".textareaHeight_hid").height();
				   $(this).height(_height);
				}
			});
			//End 事件绑定
        });
	};
})(jQuery)