/*
	节点操作
*/

DMIMI.selector = {
	find:function(selector){
		DMIMI.cpu.validateDMIMI_ELE();
		var domTemp = [];
		if(selector){
			var object = DMIMI.cpu._test("attr",selector);
			DMIMI.cpu.eachElem(function(dom){
				var _dom = DMIMI._selector(selector,dom,"find");
				if(_dom&&_dom[0]){
					domTemp.push(_dom[0]);
				}
			});
			return DMIMI.classArray(domTemp);
		}else{
			return null;
		}
	},
	parent: function( selector ) {
		DMIMI.cpu.validateDMIMI_ELE();
		var domTemp = [];
		if(selector){
			var object = DMIMI.cpu._test("attr",selector);
			domTemp = DMIMI.cpu.dir(selector,"parentNode",object);
			return DMIMI.classArray(domTemp);
		}else{
			DMIMI.cpu.eachElem(function(dom){
				if(dom.parentNode){
					domTemp.push(dom.parentNode);
				}
			});
			return DMIMI.classArray(domTemp);
		}
		//var parent = selector.parentNode;
		//return parent && parent.nodeType !== 11 ? parent : null;
	},
	next: function( selector ) {
		DMIMI.cpu.validateDMIMI_ELE();
		var domTemp = [];
		if(selector){
			var object = DMIMI.cpu._test("attr",selector);

			domTemp = DMIMI.cpu.dir(selector,"nextSibling",object);
			
		}else{
			DMIMI.cpu.eachElem(function(dom){
				DMIMI.cpu.nsibling(dom,"nextSibling",domTemp);
			});
		}
		DMIMI_ELE[0] = domTemp;
		return DMIMI_ELE;

		//return DMIMI.cpu.nth( DMIMI_ELE[0][0], selector, "nextSibling" );
	},
	prev: function( selector ) {
		DMIMI.cpu.validateDMIMI_ELE();
		var domTemp = [];
		if(selector){
			var object = DMIMI.cpu._test("attr",selector);
			domTemp = DMIMI.cpu.dir(selector,"previousSibling",object);
			return DMIMI.classArray(domTemp);
		}else{
			DMIMI.cpu.eachElem(function(dom){
				DMIMI.cpu.nsibling(dom,"previousSibling",domTemp);
			});
			return DMIMI.classArray(domTemp);
		}

		//return DMIMI.cpu.nth( selector, 2, "previousSibling" );
	},
	nextAll: function( selector ) {
		DMIMI.cpu.validateDMIMI_ELE();
		var domTemp = [];
		if(selector){
			var object = DMIMI.cpu._test("attr",selector);
			
			DMIMI.cpu.eachElem(function(dom){
				var _dom = [];
				DMIMI.cpu.sibling("nextSibling",dom,dom,_dom);
				for(var j=0;j<_dom.length;j++){
					if(DMIMI.cpu.getDom(_dom[j],object)){
						domTemp.push(_dom[j]);
					}
				}
			});
			return DMIMI.classArray(domTemp);
		}else{
			DMIMI.cpu.eachElem(function(dom){
				DMIMI.cpu.sibling("nextSibling",dom,dom,domTemp);
			});
			return DMIMI.classArray(domTemp);
		}
		//return DMIMI.cpu.dir( selector, "nextSibling" );
	},
	prevAll: function( selector ) {
		DMIMI.cpu.validateDMIMI_ELE();
		var domTemp = [];
		if(selector){
			var object = DMIMI.cpu._test("attr",selector);
			
			DMIMI.cpu.eachElem(function(dom){
				var _dom = [];
				DMIMI.cpu.sibling(dom,dom,_dom);
				for(var j=0;j<_dom.length;j++){
					if(DMIMI.cpu.getDom(_dom[j],object)){
						domTemp.push(_dom[j]);
					}
				}
			});
			return DMIMI.classArray(domTemp);
		}else{
			DMIMI.cpu.eachElem(function(dom){
				DMIMI.cpu.sibling("previousSibling",dom,dom,domTemp);
			});
			return DMIMI.classArray(domTemp);
		}
		//return DMIMI.cpu.dir( selector, "previousSibling" );
	},
	siblings: function( selector ) {
		DMIMI.cpu.validateDMIMI_ELE();
		var domTemp = [];
		if(selector){
			var object = DMIMI.cpu._test("attr",selector);
			DMIMI.cpu.eachElem(function(dom){
				var _dom = [];
				DMIMI.cpu.sibling("nextSibling",dom.parentNode.firstChild,dom,_dom);
				for(var j=0;j<_dom.length;j++){
					if(DMIMI.cpu.getDom(_dom[j],object)){
						domTemp.push(_dom[j]);
					}
				}
			});
			return DMIMI.classArray(domTemp);
		}else{
			DMIMI.cpu.eachElem(function(dom){
				DMIMI.cpu.sibling("nextSibling",dom.parentNode.firstChild,dom,domTemp);
			});
			return DMIMI.classArray(domTemp);
		}


		//return DMIMI.cpu.sibling( selector.parentNode.firstChild, selector );
	},
	children: function( selector ) {
		/*
			判断当父节点为空的时候 children 肯定也是空
		*/
		DMIMI.cpu.validateDMIMI_ELE();

		/*
			这时候需要对selector进行解析
		*/
		var object = DMIMI.cpu._test("attr",selector);
		var tempDom = [];
		DMIMI.cpu.eachElem(function(dom){
			var b = DMIMI.cpu.sibling("nextSibling",dom.firstChild);
			for(var j=0;j<b.length;j++){
				if(DMIMI.cpu.getDom(b[j],object)){
					tempDom.push(b[j]);
				}
			}
		});
		return DMIMI.classArray(tempDom);
	},
	contents: function( selector ) {
		return DMIMI.cpu.nodeName( selector, "iframe" ) ?
			selector.contentDocument || selector.contentWindow.document :
			DMIMI.makeArray( selector.childNodes );
	}
};