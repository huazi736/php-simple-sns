/*
	@ data

	1、服务器拉取的数据存储在这里
	2、当服务器有新数据，拉取过来更新掉更新客户端数据
	3、客户端优先到这里找数据

	desc: $.data("upate",["askList",id]);
		  $.data("setdata",["askList",id]);
		  $.data("deldata",["askList",id]);

		  data.ask = {list:[{name:lucy,class:1,id:5},{name:lily}]};

		  

			$.data("update",[
				["ask","list",0],{name:lucy,class:1,id:5,children:[1,2,3],"view"}
			]);
			
	$.data.ask.list[0] = 
		  
*/
Dmimi.prototype.data = function(name,arg){
	this.data.data = {};

	this.data._class = {
		getdata:function(arg){
			return $.data[arg[0]].arg[1];
		},
		setdata:function(arg){
			$.data[arg[0]] = arg[1];
		},
		updata:function(arg){
			var path = arg[0];
			var json = arg[1];
			var i = 0;
			var _data;
			function returnObj(name){
				if(typeof(name)==Number){
					_data = $.data[name];
				}else{
					_data = $.data.name;
				}
				if(_data){
					if(path[i++]){
						return returnObj(path[i++]);
					}
				}
			}
			returnObj(path[i]);
			_data = json;

			if(arg[2]=="view"){
				// ask.list.0
				$.view(arg[0].join("."),arg[0]);
			}
		},
		deldata:function(arg){

			if(arg)

			$.data[arg[0]] = undefined;
			
		}
	}
	
	return this.config._class[name];
};