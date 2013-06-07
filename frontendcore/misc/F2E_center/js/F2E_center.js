/*
 *@author: 酱油
 *@created: 2012/07/06
 *@desc: 前端架构库
 *@version: v1.0
 *@绕到天上去了
 */

function CLASS_F2E(){
	this.init();
}

CLASS_F2E.prototype = {
	/*Start 初始化*/
	init:function(){
		var $obj = {
			frameThumb:$("iframe",$("#frameThumb")),
			solidControl:$("#frameSolid")
		};
		var $opt = {
			frameW:$("#frameThumb").width(),
			frameS:Math.ceil($obj.frameThumb.size()/5)
		};
		this.view("thumb",$obj.frameThumb);
		this.view("solid",[$obj.solidControl,$opt.frameS,$opt.frameW,$("#frameThumb")]);
	},
	/*End 初始化*/
	/*Start 事件驱动*/
	event:function(e,arg){
		var self = this;
		var _class={
			/*Start 缩略图事件*/
			thumb:function(arg){
				arg.load(function(){
					arg.contents().find("html").css({
						'-webkit-transform':'translate(-39%,-39%) scale(0.203)',
						'-moz-transform':'translate(-39%,-39%) scale(0.4)'
					});
					arg.show();
				})
			},
			/*End 缩略图事件*/
			/*Start 滑动事件*/
			solid:function(arg){
				arg[0].find("li").hover(function(){
					$(this).addClass("selected").prevAll("li").removeClass("selected").end().nextAll("li").removeClass("selected");
					arg[3].stop();
					arg[3].animate({
						marginLeft:0-Number(arg[2]*$(this).index())
					},'slow');
				})
			}
			/*End 滑动事件*/
		}
		return _class[e](arg);
	},
	/*End 事件驱动*/
	/*Start 渲染层*/
	view:function(e,arg){
		var self=this;
		var _class={
			/*Start 缩略图渲染*/
			thumb:function(arg){
				arg.hide();
				self.event(e,arg);//调用缩略图事件
			},
			/*End 缩略图渲染*/
			/*Start 滑动控制渲染*/
			solid:function(arg){
				if(arg[1]<=1){return false;}
				var li = '';
				for(i=0;i<arg[1];i++){
					li = li + ("<li>●</li>")+' ';
				}
				arg[0].html(li);
				arg[0].find("li").first().addClass("selected");
				self.event('solid',arg);
			}
			/*End 滑动控制渲染*/
		}
		return _class[e](arg);
	}
	/*End 渲染层*/
}

$(function(){
	new CLASS_F2E();
})
 
 