

var follow = Dmimi.application("follow");

follow.view = function(options){
	var _class = {
		info:function(arg){
			return '<span>'+arg[1].name+'</span>';
		}
	}
	return _class[options.type](options.arg);
};
follow.event = {};
follow.model = {};
follow.init = function(){
	
}