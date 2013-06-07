/*
	* Created on 2012-7-30

	* @auther: linchangyuan

	* @name: 插件模版 v1.0
	
	* @depends： jquery.js

	* Update desc 
	
*/
DMIMI.plugin = {};

/*
	下面这个msg作为演示方便放这边， 实际开发的时候放在plugin里面
*/

DMIMI.plugin.msg = function(options){
	var opts = DMIMI.cpu.extend(DMIMI.plugin.msg.defaults, options);


	var momo = {};

	momo.init = function(elem,index,opts){
		var $ele = $(elem);
		var index = index;
		$ele.val($ele.attr("msg"));



		/*
			设置public函数 内部保留原始变量 提供外部访问.
		*/
		var public = {
			index:index,
			info:{
				setWidth:"设置宽度"
			},
			setWidth:function(width){
				$ele.width(width);
			}
		}
		return public;
	}


	/*
		输出的时候采用array方式，当对象是多个的时候会比较有用
	*/
	var ele = DMIMI.cpu.validateDMIMI_ELE();
	if(ele){
		var arr = [];
		DMIMI.each(ele,function(dom,index){
			arr.push(new momo.init(dom,index,opts));
		});
		return arr;
	}
};
DMIMI.plugin.msg.defaults = {
	absolute:false,
	border:1,
	textSize:12
};