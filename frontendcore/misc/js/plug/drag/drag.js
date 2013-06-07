/*@author:    梁珊珊
 *@created:   2012/3/2
 *@version:   v1.0
 *@desc:      封面拖动效果
*/
(function($){
	
	function Drag(options){ 
		this.isMouseDown = false;
		this.currentElement = options.currentElement;
		this.lastMouseY;
		this.lastElemTop;
		this.imgArea = options.imgArea;
		this.unbind = options.unbind;
		this.init();
	}
	Drag.prototype = {
		init:function(){
			var self = this;
			self.currentElement.css('cursor','move');
			self.event(self.unbind);
		},
		getMousePosition:function(e){
			var posx = 0;
			var posy = 0;
			if(!e) var e = window.event;
			if(e.pageX || e.pageY){
				posx = e.pageX;
				posy = e.pageY;
			}else if(e.clientX || e.clientY){
				posx = e.clientX + document.body.scrollLeft + document.documentElement.scrollLeft;
				posy = e.clientY + document.body.scrollTop + document.documentElement.scrollTop;
			}
			return {'x':posx,'y':posy};
		},
		updataPosition:function(e){
			var self =  this;
			var pos =this.getMousePosition(e);
			var spanY = pos.y - lastMouseY;
			var top = lastElemTop+spanY;
			if(top>0){
				top = 0;
			}
			if(top<self.imgArea.height()-self.currentElement.height()){
				top = -(self.currentElement.height()-self.imgArea.height());
			}
			self.currentElement.css('top',top);
		},
		event:function(arg){
			var self = this;
			if(arg =='unbind'){
				self.imgArea.unbind("mousedown").unbind("mousemove").unbind("mouseover");
				return;
			}
			function mouseMove(e){
				self.updataPosition(e);
				return false;									
			}
			function MouseDown(e){
				var pos = self.getMousePosition(e);
				lastMouseY = pos.y+37;
				lastElemTop = self.currentElement.offset().top;
				$(this).bind("mousemove",mouseMove);
				return false;
			}
			self.imgArea.bind("mousedown",MouseDown).mouseout(function(){
				$(this).unbind("mousedown",MouseDown);
			}).mouseup(function(){
				$(this).unbind("mousemove",mouseMove);
			}).mouseover(function(){
				$(this).bind("mousedown",MouseDown);
			});
			
		}
	}

	$.fn.easydrag = function(options){
		  new Drag(options);
	};
	
})(jQuery);