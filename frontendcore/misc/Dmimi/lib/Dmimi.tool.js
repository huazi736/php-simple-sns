/*
	工具

*/

DMIMI.tool = {
	size:function(){
		return DMIMI_ELE[0].length;
	},
	html:function(data){
		DMIMI.cpu.validateDMIMI_ELE();
		if(data){
			DMIMI.cpu.eachElem(function(dom){
				dom.innerHTML = data;
			});
		}else{
			var arr=[];
			DMIMI.cpu.eachElem(function(dom){
				arr.push(dom.innerHTML);
			});
			if(arr.length==1){
				return arr.join("");
			}else{
				return arr;
			}
		}
	},
	text:function(data){
		if(data){
			DMIMI_ELE[0].textContent = data;
		}else{
			return DMIMI_ELE[0].textContent
		}
	},
	val:function(data){
		DMIMI.cpu.validateDMIMI_ELE();
		DMIMI.cpu.eachElem(function(dom){
			dom.value = data;
		});
	},
	attr:function(name,value){
		var _this;
		if(DMIMI.cpu.validateDMIMI_ELE()){
			_this = DMIMI_ELE;
		}else{
			_this = this;
		}
		if(value){
			DMIMI.each(this[0],function(dom){
				dom.setAttribute(name,value);
			});
		}else{
			var arr=[];
			DMIMI.each(this[0],function(dom){
				arr.push(dom.getAttribute(name));
			});
			return arr;
		}
	},
	width:function(data){
		var ele = DMIMI.cpu.validateDMIMI_ELE(this);
		if(ele){
			DMIMI.each(ele[0],function(dom){
				dom.style.width = data+"px";
			});
		}else{
			return null;
		}
	},
	height:function(data){
		DMIMI.cpu.validateDMIMI_ELE();
		DMIMI.cpu.eachElem(function(dom){
			dom.style.height = data+"px";
		});
	},
	hide:function(){

		DMIMI.each(this[0],function(dom,index){
			dom.style.display = "none";
		})
	},
	show:function(){
		DMIMI.cpu.validateDMIMI_ELE();
		DMIMI.cpu.eachElem(function(dom){
			dom.style.display = "static";
		});
	},
	/*
		文档操作
	*/
	append:function(data){
		DMIMI.cpu.validateDMIMI_ELE();
		DMIMI.cpu.pend(DMIMI_ELE[0],"append",data);
	},
	prepend:function(data){
		DMIMI.cpu.validateDMIMI_ELE();
		DMIMI.cpu.pend(DMIMI_ELE[0],"prepend",data);
	},
	before:function(data){
		DMIMI.cpu.validateDMIMI_ELE();
		DMIMI.cpu.pend(DMIMI_ELE[0],"before",data);
	},
	after:function(data){
		console.log(data)
		DMIMI.cpu.pend(this[0],"after",data);
	},
	addClass:function(data){
		DMIMI.cpu.validateDMIMI_ELE();
		var _class;
		DMIMI.cpu.eachElem(function(dom){
			_class = dom.className;
			_class+=" "+data;
			dom.className = _class;
		});
	},
	removeClass:function(data){
		DMIMI.cpu.validateDMIMI_ELE();
		var _class;
		DMIMI.cpu.eachElem(function(dom){

			_class = dom.className;
			_class = _class.replace(data,"");
			dom.className = DMIMI.trim(_class);
		});
	},
	eq: function( i ) {
		return i === -1 ?
			DMIMI.classArray(DMIMI_ELE[0].slice( i )) :
			DMIMI.classArray(DMIMI_ELE[0].slice( i, +i + 1 ));
	},

	first: function() {
		return DMIMI.classArray(DMIMI_ELE[0]).eq( 0 );
	},

	last: function() {
		return DMIMI.classArray(DMIMI_ELE[0]).eq( -1 );
	},

	/*
		匹配前后空格，去除
	*/
	trim:function(data){
		data = data.replace(/^\s*|\s*$/g,"");
		return data;
	},
	trimAll:function(data){
		data = data.replace(/^\s*/g,"");
		return data;
	},
	/*
		each 遍历
	*/
	
	each:function(obj,callback){
		var len = obj.length;
		for(var i=0;i<len;i++){
			callback(obj[i],i);
		}
	},

	/*
		事件
	*/
	click:function(){

	},



	/*
		css
	*/
	css:function(obj){
		DMIMI.each(this[0],function(dom){
			for( var s in obj){
				dom.style[s] = obj;
			}
		});
	}
}


