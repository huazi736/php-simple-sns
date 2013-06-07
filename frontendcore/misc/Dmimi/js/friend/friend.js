

var friend = Dmimi.application("friend");

friend.init = function(){
	friend.model("data",function(data){
		$.each(data,function(i,d){
			friend._class = Dmimi.application("follow");
			friend._class.view(d.type,d);
		});
	});
}
friend.model = function(options){
	var _class = {
		data:function(arg){
			return [{name:"lily",type:"info"},{}]
		}
	}
	return _class[options.type](options.arg);
}