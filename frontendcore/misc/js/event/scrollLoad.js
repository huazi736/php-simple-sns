/*
 * Created on 2012-3-12.
 * @name: scrollLoad v1.0
 * @author: linchangyuan
 * @desc:  //属性见 [default setting]
		 
 */
(function($) {
	function CLASS_SCROLLLOAD(elm, options ,index){
		var self = this;
		this.ajax=[];
		this.$e = $(elm);
		this.opts = options;
		this.page = 1;//this.page = 0;修改by梁珊珊
		this.init();
	}
	CLASS_SCROLLLOAD.prototype= {
		init:function(){
			var self = this;
			this.more = $('<div name="nextPage"  class="nextPage" style="display:none"><a class="nextPage">'+self.opts.text+'</a></div>');
			
			this.moreNum = 2;
			self.methodData = {};
			self.methodData.page = self.page;
			
			for(var i in self.opts.data){//添加2012－3－26　梁珊
				self.methodData[i] = self.opts.data[i];
			}
			

			//self.methodData.data = self.opts.data;注释2012-3-26　梁珊
			this.model("data",[self.methodData,function(data){

				self.opts.success(data,self.opts.proxy);
				self.page++;

				if(data.status!=0&&!data.isend){
					self.$e.after(self.more);
					self.event(["more"],[self.more]);
					self.event(["scroll"]);
				}

			}]);
		},
		event:function(method,arg){
			var self = this;
			var _class={
				scroll:function(arg){
					var $psTime;
					var setTimeId;
					var i = 0;
			
					self.scrollChange = function(){
						var win = $(this);
						setTimeId = setTimeout(function(setTimeId){
							var thisTop = win.scrollTop();
							var thisHeight = win.innerHeight();
	
							if(thisTop>=($("body").height()-$(window).height())-self.opts.offset){
							
								if(self.moreNum<1){
									return false;
								}
								self.$e.after('<div class="h100 loading"></div>');
								$(window).off("scroll",self.scrollChange);
								self.methodData.page = self.page;
								self.model("data",[self.methodData,function(data){
									self.opts.success(data,self.opts.proxy);
									self.page++;
									//$(window).on("scroll",self.scrollChange);
									if(data.status==0||data.isend){
										$(window).off("scroll",self.scrollChange);
									}
									self.$e.nextAll("div.loading").remove();
									self.moreNum--;
									if(self.moreNum==0&&!data.isend){	// 第二次滚动并且有数据
										self.$e.next(".nextPage").show();
									}else{
										self.$e.next(".nextPage").hide();
									}
								}]);
							}
							$(window).on("scroll",self.scrollChange)
						},500);
						$(window).off("scroll",self.scrollChange)
					}
					$(window).off("scroll",self.scrollChange).on("scroll",self.scrollChange);
				},
				more:function(arg){
					arg[0].click(function(){
						$(window).off("scroll",self.scrollChange);
						self.$e.next(".nextPage").hide();
						self.$e.after('<div class="h100 loading"></div>');
						self.methodData.page = self.page;
						
						self.model("data",[self.methodData,function(data){
							
							self.opts.success(data,self.opts.proxy);
							self.page++;
							self.$e.next("div.loading").remove();
							if (!data.isend) {
								self.event(["scroll"]);
							}
							
						}]);
						self.moreNum = 1;
					
					});
				}
				
			}
			$.each(method,function(index,value){
				if(value){
					return _class[value](arg);
				}
			});
		},


		// 数据源本地某个txt json 格式的字符串
		model:function(method,arg){
			var self = this;
			var _class={
				data:function(arg){
					var request=$.djax({
						url:self.opts.url,
                        type:self.opts.type||'POST',//王月飞2012.7.12
						dataType:self.opts.dataType||"json",//王月飞2012.7.12
						data:arg[0],//添加by梁珊珊2012/3/16
						success:function(data){
							if(data){	
								arg[1](data);
							}
						}
					});
					self.ajax.push(request);
				}
			}
			return _class[method](arg);
		}
	}

	$.fn.scrollLoad = function(options){
		var opts = $.extend({}, $.fn.scrollLoad.defaults, options);
		return this.each(function(index) {
			this.scrollLoad = new CLASS_SCROLLLOAD(this, opts,index);
			$(window).off("scroll",this.scrollChange);
		});
	};
	$.fn.unscrollLoad=function(){
		this.each(function(i){
			if(this.scrollLoad){
				if(this.scrollLoad.ajax){
					var temp =this.scrollLoad.ajax;
					for (var i =temp.length-1;i>=0 ;i-- )
					{
						temp[i].abort();
					}
				}
			}
			this.scrollLoad=null;
		});
	}
	$.fn.scrollLoad.defaults = { 
		text:"显示更多动态",	
		url:"",					//请求地址
		data:{},				//请求参数
		success:function(){},	//完成函数
		proxy:{},				//类对象指针
		offset:30				//偏移量,滚动到距离底部距离触发
	};
})(jQuery);
