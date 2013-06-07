
/*
	Create 2012-4-22
	@ author 杨光远
	@ name 《面向对象-模块开发模版》
	@ 使用方法 在页面上直接使用<dktext class='' id='' maxlength=''></dktext> 请务必填写最长宽度 maxlength。
	


*/



$(function(){
	var dktext = {}
	
	dktext.view = function(name,arg){
		this.view._class={
			del_creat_new_div:function(arg)
			{
				var _self=arg[0];
				var _id=_self.attr("id");	
				var _maxlength=arg[1];		
				var _numid="num_"+_id;
				var _class=_self.attr("class");
				_self.after("<div id=\"con_"+_id+"\" class=\""+_self.attr("class")+"\" style=\"position:relative;padding-bottom:18px\">"+
					"<div contenteditable=true class=\""+_self.attr("class")+"\" id=\""+_id+"\" style=\"border:none;word-wrap:break-word;word-break:break-all;\"></div>"+
					"<div style=\"height:18px;position: absolute;bottom:0px;right:0px\" id=\""+_numid+"\">0/"+_maxlength+"</div></div>");

						
				_self.remove();
				if(_maxlength==-1)
				{
					$("#"+_numid).remove();
					$("#con_"+_id).css("padding-bottom","0px");
				}else{
					dktext.event("event_list",[$("#"+_id),$("#"+_numid),_maxlength]);
				}	
				
				
				
			}
			
		}
		return this.view._class[name](arg)
	}

	dktext.event = function(name,arg){
		this.event._class={
			sum_chat:function(arg){
				var sumi;

				var str;
				str=arg[0].text();



				_nownum=Math.floor(dktext.event("getChart",[str])/2);
				
				if(_nownum>arg[2])
				{
					_nownum="<a style='red'>"+_nownum+"</a>";
				}
				arg[1].html(_nownum+"/"+arg[2]);
			},
			event_list:function(arg){
				$(document).keydown(function(){
					setTimeout(function(){dktext.event("sum_chat",[arg[0],arg[1],arg[2]]);},100)
					
				})

			},
			getChart:function(arg){
				if(arg[0]==null)
				{
					return 0;
				}else{
					return (arg[0].length+arg[0].replace(/[\u0000-\u00ff]/g,"").length);
				}
			},
			dktext_each:function(arg)
			{
				var $dktext=$("dktext");
				$dktext.each(function(){
					var _maxlength;
					if($(this).attr("maxlength"))
					{
						_maxlength=$(this).attr("maxlength");
					}else
					{
						_maxlength=-1;
					}
					
						dktext.view("del_creat_new_div",[$(this),_maxlength]);
				})
			}

		}
		return this.event._class[name](arg)
	}

	dktext.init =function(){
		//事件驱动
		dktext.event("dktext_each",[]);

	}
	dktext.init();
});