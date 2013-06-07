/*
	@ cpu
	
	desc 数据逻辑处理

*/

DMIMI.cpu = {
	/*
		递归算法 参数:
			condition(条件)  callback 
	*/
	resursion:function(condition,object,callback){
		var temp = [];
		var fun = function(object){
			if(object.nodeType===1){
				if(object.childNodes&&object.childNodes[0].nodeType!==1){
					var obj = object.childNodes;
					for(var i=0;i<obj.length;i++){
						if(obj[i].childNodes&&obj[i].childNodes[0].nodeType!==1){
							fun(obj[i].childNodes);
						}else{
							temp.push(obj[i]);
						}
					}
				}else{
					temp.push(object);
				}
			}
		}
		fun(object);
		callback(temp)
	},
	_test:function(name,text){
		var results;
		var _class = {
			"attr":function(text){
				var object = {};
				if(text.indexOf(".")!=-1){
					var domTemp = [];
					if(text.indexOf(".")!=0){
						/*
							如果“.”不在第一个位置那么存在元素
						*/
						var arr = text.split(".");
						object.tagName = arr[0];
						object.arr = [{className:"class",classValue:arr[1]}];
						
					}else{
						object.arr =[{className:"class",classValue:text.replace(".","")}]

					}
					return object;
				}
				var reg1 = /^\w*/;
				var reg2 = /(\[\w+=\w+]*\])/gi;
				
				/*
					通过正则reg1 得到匹配的tagName
				*/
				results = text.match(reg1);
				if(results&&results.length&&results[0]!=""){
					object.tagName = results[0];
				}

				/*
					通过正则reg2 得到匹配的attribute
				*/
				object.arr = [];
				results = text.match(reg2);
				if(results&&results.length){
					//这里用来把[name=abc]多个这样的放到一个对象中准备作为过滤的条件
					for(var i=0;i<results.length;i++){
						var a = results[i];
						a = a.replace(/[\[\]]/g,"");
						var aArray = a.split("=");
						object.arr[i] = {attrName:aArray[0],attrValue:aArray[1]};
					}
				}

				return object;
			}
		}
		return _class[name](text);
	},

	/*
		用于验证dom是否符合selector条件
		object: tagName attrName attrValue
	*/
	validateSelector:function(dom,object){

		if(object.tagName){
			if(dom.tagName!=object.tagName.toUpperCase()){
				return false;
			}
		}
		if(object.attrName){
			if(!dom.getAttribute(object.attrName)){
				return false;
			}
		}
		if(object.attrValue){
			if(dom.getAttribute(object.attrName)!=object.attrValue){
				return false;
			}
		}

		if(object.className){
			if(dom.className!=object.classValue){
				return false;
			}
		}
		return true;
	},
	/*	
		用于判断dom是否符合条件
	*/
	getDom:function(dom,object){
		var a = [];
		var arr = object.arr;
		var thisDomBool = false;
		if(arr&&arr.length){
			for(var k=0;k<arr.length;k++){
				thisDomBool = DMIMI.cpu.validateSelector(dom,{
					tagName:object.tagName,
					attrName:arr[k].attrName,
					attrValue:arr[k].attrValue,
					className:arr[k].className,
					classValue:arr[k].classValue
				});
				if(!thisDomBool){
					break;
				}
			}
		}else{
			thisDomBool = DMIMI.cpu.validateSelector(dom,{tagName:object.tagName});
		}
		return thisDomBool;
	},
	validateDMIMI_ELE:function(obj){
		if(obj&&obj[0]&&obj[0][0]){
			return obj;
		}else if(DMIMI_ELE[0]&&DMIMI_ELE[0].length&&DMIMI_ELE[0][0]){ 
			return DMIMI_ELE[0];
		}else{
			console.warn(DMIMI.error.undefined("DMIMI_ELE ")+"？"+DMIMI.selector);
			return null;
		}
	},
	extend:function(a,b){
		var _class = {};
		if(b){
			for(var name in b){
				_class[name] = b[name];
				
			}
		}
		if(a){
			for(var name in a){
				if(b&&b[name]==a[name]){
					return;
				}
				_class[name] = a[name];
				
			}
		}
		return _class;
	},

	// 用于将模块属性方法合并到主干，方便用户使用
	merge:function(a,b){
		var thisExtend = function(obj){
			for(var name in obj){
				a[name] = obj[name];
			}
		}
		for(var i=0;i<b.length;i++){
			thisExtend(b[i].obj);
			delete a[b[i].name];
		}
	},
	/*
	makeArray:function( array, results ) {
		var i = 0,
			ret = results || [];

		if ( toString.call(array) === "[object Array]" ) {
			Array.prototype.push.apply( ret, array );

		} else {
			if ( typeof array.length === "number" ) {
				for ( var l = array.length; i < l; i++ ) {
					ret.push( array[i] );
				}

			} else {
				for ( ; array[i]; i++ ) {
					ret.push( array[i] );
				}
			}
		}

		return ret;
	},
	filter: function( expr, elems, not ) {
		if ( not ) {
			expr = ":not(" + expr + ")";
		}

		return elems.length === 1 ?
			CLASS.find.matchesSelector(elems[0], expr) ? [ elems[0] ] : [] :
			CLASS.find.matches(expr, elems);
	},
	*/

	createElement:function(data){
		var element;
		/*
			这个正则获取元素name 如div
		*/
		var reg1 = /^<\w*/;
		var results1 = data.match(reg1);
		results1 = results1[0].replace("<","");


		/*
			这个正则获取元素属性包括class
		*/
		var reg2 = /\w*=['"]\w*['"]/g;
		var results2 = data.match(reg2);


		
		/*
			这个html 得到 元素html
		*/
		var index = data.indexOf(">");
		var html = data.substring(index+1,data.length-(3+results1.length));
		
		/*
			创建一个该节点
		*/
		element = document.createElement(results1);


		/*
			收集元素上含有的属性
		*/
		var arr = [];
		for(var i=0;i<results2.length;i++){
			var a = results2[i];
			a = a.replace(/['"]/g,"");
			var aArray = a.split("=");
			arr[i] = {attrName:aArray[0],attrValue:aArray[1]};
		}
		
		/*
			遍历收集的属性 一一对新创建的 _dom 赋值
		*/
		for(var i=0;i<arr.length;i++){
			if(arr[i].attrName=="class"){
				element.className = arr[i].attrValue;
			}else{
				element.setAttribute(arr[i].attrName,arr[i].attrValue);
			}
		}
		element.innerHTML = html;

		return element;
	},
	/*
		用于选择器，next parent 等
	*/
	dir: function( selector,dir,object ) {
		var dom = [];
		var fun = function(elem,dir,arr){
			if(elem[dir]){
				if(DMIMI.cpu.getDom(elem[dir],object)){
					dom.push(elem[dir]);
				}else{
					return fun(elem[dir],dir,arr);
				}
			}else{
				return false;
			}
		}
		DMIMI.cpu.eachElem(function(dom){
			fun(dom,dir,object);
		});

		return dom;
	},

	/*
		用于append prepend
	*/
	pend:function(dom,pend,data){

		var fun;
		var temp;
		
	
		if(typeof data=="string"||data=="number"){
			
			fun = function(dom,type,child,type2){
				temp = dom.innerHTML;
				if(type2=="append"){
					dom.innerHTML = temp+data;
				}
				if(type2=="prepend"){
					dom.innerHTML = data+temp;
				}
				if(type2=="after"||type2=="before"){
					var bool = new RegExp(/^</).test(DMIMI.trim(data));
					if(bool){
						temp = DMIMI.cpu.createElement(data);
					}else{
						temp = document.createTextNode(data);
	
					}
					dom[type](temp,child);
				}
			}
		}else{

			fun = function(dom,type,child){ 
				for(var j=0;data[0][j];j++){
					if(data[0][j]){
						dom[type](data[0][j],child);
					}
				}
			}
		}
		var obj1,obj2,type; 

		for(var i=0;dom[i];i++){
			switch(pend){
				case "append":
					obj1 = dom[i];
					obj2 = dom[i].firstChild;
					type = "appendChild";
				break;
				case "prepend":
					obj1 = dom[i];
					obj2 = dom[i].firstChild;
					type = "insertBefore";
				break;
				case "before":
					obj1 = dom[i].parentNode;
					obj2 = dom[i];
					type = "insertBefore";
				break;
				case "after":
					obj1 = dom[i].parentNode;
					obj2 = dom[i].nextSibling;
					type = "insertBefore";
				break;
			}
			fun(obj1,type,obj2,pend);
		}
	},

	nth: function( elem, selector, dir ) {
		var num = 0;
		for ( ; elem; elem = elem[dir] ) {
			if ( elem.nodeType === 1 ) {
				break;
			}
		}

		return elem;
	},
	/*
		这个或者兄弟节点采用递归方式，直到返回正确节点或者没有了。
	*/
	nsibling:function(dom,dir,domTemp){
		var fun = function(node){
			if(node.nodeType===1){
				return node;
			}
			if(node[dir]){
				return fun(node[dir]);
			}
			return null;
		}
		if(dom[dir]){
			var _dom = fun(dom[dir]);
			if(_dom){
				domTemp.push(_dom);
			}
		}
		return domTemp;
	},

	/*
		这里遍历兄弟节点判断nodeType返回元素，而不是空格换行等
	*/
	sibling: function(dir, n, elem ,domTemp) {
		var r = [];

		for ( ; n; n = n[dir] ) {
			if ( n.nodeType === 1 && n !== elem ) {

				if(domTemp){
					domTemp.push(n);
				}
				r.push( n );
			}
		}
		return r;
	},
	/*
		用于遍历DMIMI_ELE callback回传
	*/
	eachElem:function(callback){

		if(DMIMI_ELE[0]&&DMIMI_ELE[0].length){
			for(var i=0;i<DMIMI_ELE[0].length;i++){
				callback(DMIMI_ELE[0][i],i);
			}
		}else{
			callback(DMIMI_ELE[0],0);
		}
	}
}