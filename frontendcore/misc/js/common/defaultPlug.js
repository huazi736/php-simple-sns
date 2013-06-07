/*
	* Created on 2011-12-09
	* @auther: linchangyuan
	* @name: msg v1.3
	* @depends： jquery.js
	* @desc: $.msg($("#wrap")); <input msg="请输入名字" />
	* Update desc 
			1.1 改写为插件模式 解决目标绝对定位问题。
			1.2 解决目标对象获取不到width 导致宽度异常 IE7
			1.3 增加msg继承对象的文本样式
	
*/
	function CLASS_MSG(elm,options){
		this.$e = $(elm);
		this.opts = options;
		this.init();
	}
	CLASS_MSG.prototype = {
		init:function(){
			this.view();
			this.bindEventList();
			this.position();
		},
		view:function(){
			this.$span = $("<span class='input_msg'>"+this.$e.attr("msg")+"</span>");
			if(this.$e.next("span.input_msg").size()==0){
				this.$e.after(this.$span);
			}
			if(this.$e.val()!=""){
				this.$span.hide();
			}
			
		},
		position:function(){
			var p_left = parseInt(this.$e.parent().css("padding-left"));
			var e_left = parseInt(this.$e.css("margin-left"));
			var width = this.$e.attr("msg").length*12+20;
			var fontW = this.$e.css("font-weight");
			var fontS = this.$e.css("font-size");
			var lineH = parseInt(this.$span.css("line-height"));
            var top = parseInt(this.$e.css("padding-top"));
            if(this.$e.outerWidth>width){
				width = this.$e.outerWidth;
			}
			this.$e.parent().css({
				position:"relative"
			});
            // top  = (top == NaN ? 4 : top);(原来代码，修改原因:任何值永远都不等于NaN，即使是NaN本身与NaN也不相等，判断一个值是否为NaN应使用函数isNaN())
			top  = (top == 0 ? 4 : top);	// 修改后的代码（陈海云修改：2012-06-08）
            this.$span.css({
				left:parseInt(this.$e.css("padding-left"))+this.opts.border*1+p_left+e_left,
				top:top,
				width:width,
				overflow:"hidden",
				background:"#fff",
				"font-weight":fontW,
				"font-size":fontS
			});
		},
		bindEventList:function(){
			var self = this;
			this.$span.click(function(){
				self.$e.trigger("focus");
			});
			this.$e.focus(function(){
				self.$span.hide();
			});
			this.$e.blur(function(){
				if($(this).val()==""){
					self.$span.show();
				}else{
					self.$span.hide();
				}
			});
			this.$e.change(function(){
				if($(this).val()==""){
					self.$span.show();
				}else{
					self.$span.hide();
				}
			});
		}
	};
	$.fn.msg = (function(options){
		var opts = $.extend({}, $.fn.msg.defaults, options);
		return this.each(function(index) {
			new CLASS_MSG(this, opts,index);
		});
	});
	$.fn.msg.defaults = {
		absolute:false,
		border:1,
		textSize:12
	};