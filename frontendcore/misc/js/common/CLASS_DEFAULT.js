
// 这是一个模版 lincy

function CLASS_POSTBOX(arg){
	this.init();
	
}
CLASS_POSTBOX.prototype= {
	init:function(){
		var self = this;
	},
	view:function(method,arg){
		var self = this;
		var _class = {
			status:function(arg){
				
			},
			photo:function(arg){
				
			},
			video:function(arg){
				
			},
			life:function(arg){
				
			}
		
		}
		$.each(method,function(index,value){
			if(value){
				return _class[value](arg);
			}
		});
	},
	cpu:function(method,arg){
		var self = this;
		var func = null;
		var _class={
			psTime:function(arg){
			 
				$.each(arg[0].find("li[name=time]"),function(){
					var id = $(this).attr("id");
					var scale = ($(this).offset().top+15)+($(this).height()-15);
					self.psTime[id] = ($(this).offset().top)+"-"+scale;
				});
			 
			}
			
		}
		$.each(method,function(index,value){
			if(value){
				func = _class[value](arg);
				return func;
			}
		});
		return func;
	},
	iefix:function(method,arg){
		var self = this;
		var _class={
			returnScale:function(arg){
			
			}
		
		}
		$.each(method,function(index,value){
			if(value){
				return fn = _class[value](arg);
			}
		});
		return fn;
	},
	event:function(method,arg){
		var self = this;
		var _class={
			
			distributeInfoBody:function(arg){
				
				
				
			}
		}
		$.each(method,function(index,value){
			if(value){
				return _class[value](arg);
			}
		});
	},
	plug:function(method,arg){
		var self = this;
		var _class = {
			
			tip_up:function(arg){
				arg[0].tip({
					direction:"up",
					width:"auto",
					showOn:"click",
					content:arg[1],
					key:arg[2],
					hold:true
				});
			},
			msg:function(arg){
				arg[0].find("[msg]").msg();
			}
		}
		$.each(method,function(index,value){
			if(value){
				return _class[value](arg);
			}
		});
	},
	model:function(method,arg){
		var self = this;
		var _class={
			
			changeData:function(arg){
				$.djax({
					url:"data3.txt",
					dataType:"html",
					async:true,
					success:function(data){
					
					},
					error:function(data){
						 
					}
				});
				
			},
			data:function(arg){
				
			}
		}
		return _class[method](arg);
	}
}