/***********************************************************************************
	@ vrsion v1.0 

	@ auth linchangyuan 

	@ create 2012-6-5

	@ name Dmimi  PS:duankou more intimate more intelligent  更贴心（程序懂你，友好的出错提示！性能消耗提示！），更智能（能为你多做点的就多做点）

	updata
		2012-7-23 			继续attribute selector开发，以及children方法中selector的实现
		2012-7-26 			1.优化CPU参数传递模式.
							2.工具包中增加各种dom操作
		2012-7-27			解决html的bug
		2012-7-30			append after 等文档操作插入string 和 dom类型的区别
		2012-7-31			Dmimi plugin 开发

***************************************************************************************/






var DMIMI = function(elem){
	return DMIMI._selector(elem);
};

/*
	利用js单线程 设置一个全局变量用于存储当前对象
*/
var DMIMI_ELE = []; 


DMIMI.Dmimi = "beta 1.0";
/*
	选择器主函数，selector: .class #id div []
*/

DMIMI._selector = function (selector,dom,type){
	var doc = dom||document;
	var domTemp;
	DMIMI.selector = selector;
	if(selector.nodeType === 1){
		domTemp = [selector];
		return DMIMI.classArray(domTemp);
	}else if(selector.indexOf("<")==0){
		domTemp = [DMIMI.cpu.createElement(selector)];
		return DMIMI.classArray(domTemp)
	}else if(selector.indexOf("#")!="-1"){
		domTemp = [doc.getElementById(selector.replace("#",""))];
	}else if(selector.indexOf(".")!="-1"){
		domTemp = [];
		if(selector.indexOf(".")!=0){
			/*
				如果“.”不在第一个位置那么存在元素
			*/
			var arr = selector.split(".");
			var d = doc.getElementsByTagName(arr[0]);
			for(var i=0;i<d.length;i++){
				if(d[i].className==arr[1]){
					domTemp.push(d[i]);
				}
			}
		}else{
			domTemp = doc.getElementsByClassName(selector.replace(".",""));

		}
	}else if(selector.indexOf("[")!=-1){
		domTemp = [];

		/*
			通过cpu 我们得到一个匹配selector 的数组，0： domTemp, 1: attributes
		*/
		var object = DMIMI.cpu._test("attr",selector);


		/*
			一个匿名函数返回指定节点， 通过遍历每一个节点并且判断tagName attribute 返回匹配的节点
		*/
		var d;
		object.tagName ? d = doc.getElementsByTagName(object.tagName) : d =  doc.getElementsByTagName("*");

		domTemp = function(d,arr){
			var _dom = [];
			for(var i=0;i<d.length;i++){
				var thisDom = d[i];
				var thisDomBool = false;
				for(var j=0;j<arr.length;j++){
					thisDomBool = DMIMI.cpu.validateSelector(thisDom,{
						attrName:arr[j].attrName,
						attrValue:arr[j].attrValue
					});
					if(!thisDomBool){
						break;
					}
				}

				if(thisDomBool){
					_dom.push(thisDom);
				}

			}
			return _dom;
		}(d,object.arr);

	}else {
		domTemp = doc.getElementsByTagName(selector);
	}

	/*
		当存在find 需要改变当操作dom对象的时候
	*/
	if(type&&type=="find"){
		return domTemp;
	}else{
		return DMIMI.classArray(domTemp)
	}
}

/*
	使得dom对象继承所有Dmimi方法及属性
*/
DMIMI.classArray = function(dom){
	DMIMI_ELE = [dom];
	for(var i in DMIMI){
		DMIMI_ELE[i] = DMIMI[i];
	}

	return DMIMI_ELE;
}

$ = DMIMI;

