
/*
	@auth linchangyuan
	@name Dmimi  duankou more intimate more intelligent
	@vrsion v1.0 
	@desc 更贴心，更智能

*/

/* @ 应用场景

	模块follow.js 
	
	var follow = Dmimi.application("follow");

	follow.view = {};
	follow.event = {};
	follow.model = {};
	follow.init = function(){
		follow.model("timeline",function(data){
			follow.view("timeline",data);
			
		});
		follow.model("monthData",function(data){
			$.each(data,function(i,d){
				follow.view(d.type,d);
			})
			
		});
		
	}
	

	模块friend.js

	var friend = Dmimi.application("friend") = {};
	friend.init = function(){
		follow.model("data",function(data){
			$.each(data,function(i,d){
				Dmimi.application("follow").view(d.type,d);
			});
		});
	}




*/





/*
	@ application

	1、所有模块都会加到application来
	2、当前模块用到未加载模块的功能时，通过include 把该模块加载进application来使用
	3、如果已经存在直接返回该模块。
*/
Dmimi.prototype.application = function(name){
	if(this.application._class==undefined){
		this.application._class = {};
	}
	if(this.application._class[name]){
		return this.application._class[name];
	}else{
		var newClass = this.include(name);
		if(newClass){
			return this.application._class[name];
		}else{
			return this.application._class[name] = {};
		}
	}
};