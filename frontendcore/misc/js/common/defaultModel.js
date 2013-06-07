
/*
	Create 2012-4-22
	@ author linchangyuan
	@ name 《面向对象-模块开发模版》
	desc init初始化-> event 事件驱动-> model 调用远端数据 -> view 渲染呈现 -> plug 绑定插件
	


*/



$(function(){
	var momo = {}

	momo.view = function(name,arg){
		this.view._class={
			list:function(arg){
				var str="<div></div>"
				str+='';
				arg[0].html(str);
			}
		}
		return this.view._class[name](arg)
	}

	momo.event = function(name,arg){
		this.event._class={
			button:function(arg){
				arg[0].click(function(){
					momo.model("getData",[url,function(data){
						// {name:123,title:你好}
						momo.view("list",[$(table),data]);
					}])

				});
			}
		}
		return this.event._class[name](arg)
	}
	momo.plug = function(name,arg){
		this.plug._class={
			button:function(arg){
				
			}
		}
		return this.plug._class[name](arg)
	}

	//请求得到数据
	momo.model = function(name,arg){
		this.model._class={
			getData:function(arg){
				$.djax(function(){
					url:""
					data:""
					success:function(data){
						arg[1](data)
					}
				});
			}
		}
		return this.model._class[name](arg)
	}

	momo.init =function(){
		//事件驱动
		momo.event("button",[$("button")]);
	}
});