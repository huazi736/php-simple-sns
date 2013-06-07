function CLASS_JOB(){
	this.init();
}

CLASS_JOB.prototype = {
	init : function(){
		this.$item = $('.tagMenu').children('.item');
		this.$detail = $('.specContent').children('.detail');

		this.event('menuToggle');
		this.event('tagToggle');
	},
	view : function(method,arg){
		var self = this;
		var _class = {

		};

		return _class[method](arg);
	},
	event : function(method,arg){
		var self = this;
		var _class = {
			menuToggle : function(){ //一级分类切换
				self.$item.children('h2').bind('click',function(){
					$(this).parent().addClass('current').siblings().removeClass('current');
					$(this).next('ul').show().parent().siblings().children('ul').hide();
				});
			},
			tagToggle : function(){ //右侧tag切换
				self.$detail.children('ul').children('li').bind('click',function(){
					$(this).addClass('selected').siblings().removeClass('selected');
				});
			}
		};

		return _class[method](arg);
	}
}

$(function(){
	new CLASS_JOB();
})