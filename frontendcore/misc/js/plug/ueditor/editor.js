
UEDITOR_CONFIG = window.UEDITOR_CONFIG || {};

var baidu = window.baidu || {};

window.baidu = baidu;

window.UE = baidu.editor =  {};

UE.plugins = {};

UE.commands = {};


UE.version = "1.2.2.0";

var dom = UE.dom = {};


var browser = UE.browser = function(){
    var agent = navigator.userAgent.toLowerCase(),
        opera = window.opera,
        browser = {
        
        ie		: !!window.ActiveXObject,

        
        opera	: ( !!opera && opera.version ),

        
        webkit	: ( agent.indexOf( ' applewebkit/' ) > -1 ),

        
        mac	: ( agent.indexOf( 'macintosh' ) > -1 ),

        
        quirks : ( document.compatMode == 'BackCompat' )
    };

    
    browser.gecko = ( navigator.product == 'Gecko' && !browser.webkit && !browser.opera );

    var version = 0;
    if ( browser.ie )
    {
        version = parseFloat( agent.match( /msie (\d+)/ )[1] );

        
        browser.ie8 = !!document.documentMode;

        
        browser.ie8Compat = document.documentMode == 8;

        
        browser.ie7Compat = ( ( version == 7 && !document.documentMode )
                || document.documentMode == 7 );

        
        browser.ie6Compat = ( version < 7 || browser.quirks );

    }
    if ( browser.gecko )
    {
        var geckoRelease = agent.match( /rv:([\d\.]+)/ );
        if ( geckoRelease )
        {
            geckoRelease = geckoRelease[1].split( '.' );
            version = geckoRelease[0] * 10000 + ( geckoRelease[1] || 0 ) * 100 + ( geckoRelease[2] || 0 ) * 1;
        }
    }
    
    if (/chrome\/(\d+\.\d)/i.test(agent)) {
        browser.chrome = + RegExp['\x241'];
    }
    
    if(/(\d+\.\d)?(?:\.\d)?\s+safari\/?(\d+\.\d+)?/i.test(agent) && !/chrome/i.test(agent)){
    	browser.safari = + (RegExp['\x241'] || RegExp['\x242']);
    }
    if ( browser.opera )
        version = parseFloat( opera.version() );
    if ( browser.webkit )
        version = parseFloat( agent.match( / applewebkit\/(\d+)/ )[1] );

    
    browser.version = version;

    
    browser.isCompatible =
        !browser.mobile && (
        ( browser.ie && version >= 6 ) ||
        ( browser.gecko && version >= 10801 ) ||
        ( browser.opera && version >= 9.5 ) ||
        ( browser.air && version >= 1 ) ||
        ( browser.webkit && version >= 522 ) ||
        false );
    return browser;
}();
var ie = browser.ie,
    webkit = browser.webkit,
    gecko = browser.gecko;


    var utils = UE.utils =
	
    {
		
		makeInstance: function(obj) {
            var noop = new Function();
			noop.prototype = obj;
			obj = new noop;
			noop.prototype = null;
			return obj;
		},
        
		extend: function(t, s, b) {
			if (s) {
				for (var k in s) {
					if (!b || !t.hasOwnProperty(k)) {
						t[k] = s[k];
					}
				}
			}
			return t;
		},
		
		isArray: function(array) {
			return Object.prototype.toString.apply(array) === '[object Array]'
		},
		
		isString: function(str) {
			return typeof str == 'string' || str.constructor == String;
		},
		
		inherits: function(subClass, superClass) {
			var oldP = subClass.prototype,
			    newP = utils.makeInstance(superClass.prototype);
			utils.extend(newP, oldP, true);
			subClass.prototype = newP;
			return (newP.constructor = subClass);
		},

		
		bind: function(fn, this_) {
			return function() {
				return fn.apply(this_, arguments);
			};
		},

		
		defer: function(fn, delay, exclusion) {
			var timerID;
			return function() {
				if (exclusion) {
					clearTimeout(timerID);
				}
				timerID = setTimeout(fn, delay);
			};
		},



		
		indexOf: function(array, item, at) {
            for(var i=at||0,l = array.length;i<l;i++){
               if(array[i] === item){
                   return i;
               }
            }
            return -1;
		},

        findNode : function(nodes,tagNames,fn){
            for(var i=0,ci;ci=nodes[i++];){
                if(fn? fn(ci) : this.indexOf(tagNames,ci.tagName.toLowerCase())!=-1){
                    return ci;
                }
            }
        },
		
		removeItem: function(array, item) {
            for(var i=0,l = array.length;i<l;i++){
                if(array[i] === item){
                    array.splice(i,1);
                    i--;
                }
            }
		},

		
		trim: function(str) {
            return str.replace(/(^[ \t\n\r]+)|([ \t\n\r]+$)/g, '');
		},

		
		listToMap: function(list) {
            if(!list)return {};
            list = utils.isArray(list) ? list : list.split(',');
            for(var i=0,ci,obj={};ci=list[i++];){
                obj[ci.toUpperCase()] = obj[ci] = 1;
            }
            return obj;
		},

		
		unhtml: function(str) {
           return str ? str.replace(/[&<">]/g, function(m){
               return {
                   '<': '&lt;',
                   '&': '&amp;',
                   '"': '&quot;',
                   '>': '&gt;'
               }[m]
           }) : '';
		},
        html:  function(str) {
            return str ? str.replace(/&((g|l|quo)t|amp);/g, function(m){
                return {
                    '&lt;':'<',
                    '&amp;':'&',
                    '&quot;':'"',
                    '&gt;': '>'
                }[m]
            }) : '';
        },
		
		cssStyleToDomStyle: function() {
            var test = document.createElement('div').style,
               cache = {
                   'float': test.cssFloat != undefined ? 'cssFloat' : test.styleFloat != undefined ? 'styleFloat': 'float'
               };

            return function(cssName) {
               return cache[cssName] || (cache[cssName] = cssName.toLowerCase().replace(/-./g, function(match){return match.charAt(1).toUpperCase();}));
            };
		}(),
		
        loadFile : function(doc,obj,fun){
            if (obj.id && doc.getElementById(obj.id)) {
				return;
			}
            var element = doc.createElement(obj.tag);
            delete obj.tag;
            for(var p in obj){
                element.setAttribute(p,obj[p]);
            }
			element.onload = element.onreadystatechange = function() {
                if (!this.readyState || /loaded|complete/.test(this.readyState)) {
                    fun && fun();
                    element.onload = element.onreadystatechange = null;
                }
			};

			doc.getElementsByTagName("head")[0].appendChild(element);

        },
        
        isEmptyObject : function(obj){
            for ( var p in obj ) {
                return false;
            }
            return true;
        },
        isFunction : function (source) {
            return '[object Function]' == Object.prototype.toString.call(source);
        },

        fixColor : function (name, value) {
            if (/color/i.test(name) && /rgba?/.test(value)) {
                var array = value.split(",");
                if (array.length > 3)
                    return "";
                value = "#";
                for (var i = 0, color; color = array[i++];) {
                    color = parseInt(color.replace(/[^\d]/gi, ''), 10).toString(16);
                    value += color.length == 1 ? "0" + color : color;
                }

                value = value.toUpperCase();
            }
            return  value;
        },
        
        optCss : function(val){
            var padding,margin,border;
            val = val.replace(/(padding|margin|border)\-([^:]+):([^;]+);?/gi,function(str,key,name,val){
                if(val.split(' ').length == 1){
                    switch (key){
                        case 'padding':
                            !padding && (padding = {});
                            padding[name] = val;
                            return '';
                        case 'margin':
                            !margin && (margin = {});
                            margin[name] = val;
                            return '';
                        case 'border':
                            return val == 'initial' ? '' : str;

                    }
                }
                return str
            });

            function opt(obj,name){
                if(!obj)
                    return ''
                var t = obj.top ,b = obj.bottom,l = obj.left,r = obj.right,val = '';
                if(!t || !l || !b || !r){
                    for(var p in obj){
                        val +=';'+name+'-' + p + ':' + obj[p]+';';
                    }
                }else{
                    val += ';'+name+':' +
                        (t == b && b == l && l == r ? t :
                            t == b && l == r ? (t + ' ' + l) :
                                l == r ?  (t + ' ' + l + ' ' + b) : (t + ' ' + r + ' ' + b + ' ' + l))+';'
                }
                return val;
            }
            val += opt(padding,'padding') + opt(margin,'margin');

            return val.replace(/^[ \n\r\t;]*|[ \n\r\t]*$/,'').replace(/;([ \n\r\t]+)|\1;/g,';')
                .replace(/(&((l|g)t|quot|#39))?;{2,}/g,function(a,b){

                    return b ? b + ";;" : ';'
                })

        },
        
        domReady : function (){
            var isReady = false,
                fnArr = [];
            function doReady(){
                isReady = true;
                for(var ci;ci=fnArr.pop();){
                   ci();
                }
            }
            return function(onready){
                if ( document.readyState === "complete" ) {
                    return setTimeout( onready, 1 );
                }
                onready && fnArr.push(onready);

                isReady && doReady();


                if( browser.ie ){
                    (function(){
                        if ( isReady ) return;
                        try {
                            document.documentElement.doScroll("left");
                        } catch( error ) {
                            setTimeout( arguments.callee, 0 );
                            return;
                        }
                        doReady();
                    })();
                    window.attachEvent('onload',doReady);
                }else{
                    document.addEventListener( "DOMContentLoaded", function(){
                        document.removeEventListener( "DOMContentLoaded", arguments.callee, false );
                        doReady();
                    }, false );
                    window.addEventListener('load',doReady,false);
                }
            }


        }()

	};


    utils.domReady();

    
    var EventBase = UE.EventBase = function(){};

    EventBase.prototype = {
        
        addListener : function ( type, listener ) {
            getListener( this, type, true ).push( listener );
        },
        
        removeListener : function ( type, listener ) {
            var listeners = getListener( this, type );
            listeners && utils.removeItem( listeners, listener );
        },
        
        fireEvent : function ( type ) {
            var listeners = getListener( this, type ),
                r, t, k;
            if ( listeners ) {

                k = listeners.length;
                while ( k -- ) {
                    t = listeners[k].apply( this, arguments );
                    if ( t !== undefined ) {
                        r = t;
                    }

                }
                
            }
            if ( t = this['on' + type.toLowerCase()] ) {
                r = t.apply( this, arguments );
            }
            return r;
        }
    };
    
    function getListener( obj, type, force ) {
        var allListeners;
        type = type.toLowerCase();
        return ( ( allListeners = ( obj.__allListeners || force && ( obj.__allListeners = {} ) ) )
            && ( allListeners[type] || force && ( allListeners[type] = [] ) ) );
    }



var dtd = dom.dtd = (function() {
    function _( s ) {
        for (var k in s) {
            s[k.toUpperCase()] = s[k];
        }
        return s;
    }
    function X( t ) {
        var a = arguments;
        for ( var i=1; i<a.length; i++ ) {
            var x = a[i];
            for ( var k in x ) {
                if (!t.hasOwnProperty(k)) {
                    t[k] = x[k];
                }
            }
        }
        return t;
    }
    var A = _({isindex:1,fieldset:1}),
        B = _({input:1,button:1,select:1,textarea:1,label:1}),
        C = X( _({a:1}), B ),
        D = X( {iframe:1}, C ),
        E = _({hr:1,ul:1,menu:1,div:1,blockquote:1,noscript:1,table:1,center:1,address:1,dir:1,pre:1,h5:1,dl:1,h4:1,noframes:1,h6:1,ol:1,h1:1,h3:1,h2:1}),
        F = _({ins:1,del:1,script:1,style:1}),
        G = X( _({b:1,acronym:1,bdo:1,'var':1,'#':1,abbr:1,code:1,br:1,i:1,cite:1,kbd:1,u:1,strike:1,s:1,tt:1,strong:1,q:1,samp:1,em:1,dfn:1,span:1}), F ),
        H = X( _({sub:1,img:1,embed:1,object:1,sup:1,basefont:1,map:1,applet:1,font:1,big:1,small:1}), G ),
        I = X( _({p:1}), H ),
        J = X( _({iframe:1}), H, B ),
        K = _({img:1,embed:1,noscript:1,br:1,kbd:1,center:1,button:1,basefont:1,h5:1,h4:1,samp:1,h6:1,ol:1,h1:1,h3:1,h2:1,form:1,font:1,'#':1,select:1,menu:1,ins:1,abbr:1,label:1,code:1,table:1,script:1,cite:1,input:1,iframe:1,strong:1,textarea:1,noframes:1,big:1,small:1,span:1,hr:1,sub:1,bdo:1,'var':1,div:1,object:1,sup:1,strike:1,dir:1,map:1,dl:1,applet:1,del:1,isindex:1,fieldset:1,ul:1,b:1,acronym:1,a:1,blockquote:1,i:1,u:1,s:1,tt:1,address:1,q:1,pre:1,p:1,em:1,dfn:1}),

        L = X( _({a:0}), J ),//a不能被切开，所以把他
        M = _({tr:1}),
        N = _({'#':1}),
        O = X( _({param:1}), K ),
        P = X( _({form:1}), A, D, E, I ),
        Q = _({li:1}),
        R = _({style:1,script:1}),
        S = _({base:1,link:1,meta:1,title:1}),
        T = X( S, R ),
        U = _({head:1,body:1}),
        V = _({html:1});

    var block = _({address:1,blockquote:1,center:1,dir:1,div:1,dl:1,fieldset:1,form:1,h1:1,h2:1,h3:1,h4:1,h5:1,h6:1,hr:1,isindex:1,menu:1,noframes:1,ol:1,p:1,pre:1,table:1,ul:1}),
        empty =  _({area:1,base:1,br:1,col:1,hr:1,img:1,input:1,link:1,meta:1,param:1,embed:1});

    return  _({
        $nonBodyContent: X( V, U, S ),
        $block : block,
        $inline : L,

        $body : X( _({script:1,style:1}), block ),

        $cdata : _({script:1,style:1}),
        $empty : empty,
        $nonChild : _({iframe:1}),
        $listItem : _({dd:1,dt:1,li:1}),
        $list: _({ul:1,ol:1,dl:1}),
        $isNotEmpty : _({table:1,ul:1,ol:1,dl:1,iframe:1,area:1,base:1,col:1,hr:1,img:1,embed:1,input:1,link:1,meta:1,param:1}),
        $removeEmpty : _({a:1,abbr:1,acronym:1,address:1,b:1,bdo:1,big:1,cite:1,code:1,del:1,dfn:1,em:1,font:1,i:1,ins:1,label:1,kbd:1,q:1,s:1,samp:1,small:1,span:1,strike:1,strong:1,sub:1,sup:1,tt:1,u:1,'var':1}),

        $removeEmptyBlock : _({'p':1,'div':1}),
        $tableContent : _({caption:1,col:1,colgroup:1,tbody:1,td:1,tfoot:1,th:1,thead:1,tr:1,table:1}),
        $notTransContent : _({pre:1,script:1,style:1,textarea:1}),
        html: U,
        head: T,
        style: N,
        script: N,
        body: P,
        base: {},
        link: {},
        meta: {},
        title: N,
        col : {},
        tr : _({td:1,th:1}),
        img : {},
        embed: {},
        colgroup : _({thead:1,col:1,tbody:1,tr:1,tfoot:1}),
        noscript : P,
        td : P,
        br : {},
        th : P,
        center : P,
        kbd : L,
        button : X( I, E ),
        basefont : {},
        h5 : L,
        h4 : L,
        samp : L,
        h6 : L,
        ol : Q,
        h1 : L,
        h3 : L,
        option : N,
        h2 : L,
        form : X( A, D, E, I ),
        select : _({optgroup:1,option:1}),
        font : L,
        ins : L,
        menu : Q,
        abbr : L,
        label : L,
        table : _({thead:1,col:1,tbody:1,tr:1,colgroup:1,caption:1,tfoot:1}),
        code : L,
        tfoot : M,
        cite : L,
        li : P,
        input : {},
        iframe : P,
        strong : L,
        textarea : N,
        noframes : P,
        big : L,
        small : L,
        span :_({'#':1,br:1}),
        hr : L,
        dt : L,
        sub : L,
        optgroup : _({option:1}),
        param : {},
        bdo : L,
        'var' : L,
        div : P,
        object : O,
        sup : L,
        dd : P,
        strike : L,
        area : {},
        dir : Q,
        map : X( _({area:1,form:1,p:1}), A, F, E ),
        applet : O,
        dl : _({dt:1,dd:1}),
        del : L,
        isindex : {},
        fieldset : X( _({legend:1}), K ),
        thead : M,
        ul : Q,
        acronym : L,
        b : L,
        a : X( _({a:1}), J ),
        blockquote :X(_({td:1,tr:1,tbody:1,li:1}),P),
        caption : L,
        i : L,
        u : L,
        tbody : M,
        s : L,
        address : X( D, I ),
        tt : L,
        legend : L,
        q : L,
        pre : X( G, C ),
        p : X(_({'a':1}),L),
        em :L,
        dfn : L
    });
})();


    function getDomNode(node, start, ltr, startFromChild, fn, guard) {
        var tmpNode = startFromChild && node[start],
            parent;

        !tmpNode && (tmpNode = node[ltr]);

        while (!tmpNode && (parent = (parent || node).parentNode)) {
            if (parent.tagName == 'BODY' || guard && !guard(parent))
                return null;
            tmpNode = parent[ltr];
        }

        if (tmpNode && fn && !fn(tmpNode)) {
            return  getDomNode(tmpNode, start, ltr, false, fn)
        }
        return tmpNode;
    }

    var attrFix = ie && browser.version < 9 ? {
        tabindex: "tabIndex",
        readonly: "readOnly",
        "for": "htmlFor",
        "class": "className",
        maxlength: "maxLength",
        cellspacing: "cellSpacing",
        cellpadding: "cellPadding",
        rowspan: "rowSpan",
        colspan: "colSpan",
        usemap: "useMap",
        frameborder: "frameBorder"
    } : {
        tabindex: "tabIndex",
        readonly: "readOnly"
    },

    styleBlock = utils.listToMap([
        '-webkit-box','-moz-box','block' ,
        'list-item' ,'table' ,'table-row-group' ,
        'table-header-group','table-footer-group' ,
        'table-row' ,'table-column-group' ,'table-column' ,
        'table-cell' ,'table-caption'
    ]);



    var domUtils = dom.domUtils = {
        NODE_ELEMENT : 1,
        NODE_DOCUMENT : 9,
        NODE_TEXT : 3,
        NODE_COMMENT : 8,
        NODE_DOCUMENT_FRAGMENT : 11,
        POSITION_IDENTICAL : 0,
        POSITION_DISCONNECTED : 1,
        POSITION_FOLLOWING : 2,
        POSITION_PRECEDING : 4,
        POSITION_IS_CONTAINED : 8,
        POSITION_CONTAINS : 16,
        fillChar : ie && browser.version == '6' ? '\ufeff' : '\u200B',

        keys : {
             8:1,  46:1,
             16:1,  17:1,  18:1,
            37:1, 38:1, 39:1, 40:1,
            13:1 
        },
        
        getPosition : function (nodeA, nodeB) {
            if (nodeA === nodeB) {
                return 0;
            }

            var node,
                parentsA = [nodeA],
                parentsB = [nodeB];


            node = nodeA;
            while (node = node.parentNode) {
                if (node === nodeB) {
                    return 10;
                }
                parentsA.push(node);

            }


            node = nodeB;
            while (node = node.parentNode) {
                if (node === nodeA) {
                    return 20;
                }
                parentsB.push(node);

            }

            parentsA.reverse();
            parentsB.reverse();

            if (parentsA[0] !== parentsB[0])
                return 1;

            var i = -1;
            while (i++,parentsA[i] === parentsB[i]) ;
            nodeA = parentsA[i];
            nodeB = parentsB[i];

            while (nodeA = nodeA.nextSibling) {
                if (nodeA === nodeB) {
                    return 4
                }
            }
            return  2;
        },

        
        getNodeIndex : function (node) {
            var child = node.parentNode.firstChild,i = 0;
            while(node!==child){
                i++;
                child = child.nextSibling;
            }
            return i;
        },

        
        inDoc: function (node, doc){
            while (node = node.parentNode) {
                if (node === doc) {
                    return true;
                }
            }
            return false;
        },

        
        findParent : function (node, tester, includeSelf) {
            if (!domUtils.isBody(node)) {
                node = includeSelf ? node : node.parentNode;
                while (node) {

                    if (!tester || tester(node) || this.isBody(node)) {

                        return tester && !tester(node) && this.isBody(node) ? null : node;
                    }
                    node = node.parentNode;

                }
            }

            return null;
        },
        
        findParentByTagName : function(node, tagName, includeSelf,excludeFn) {
            if (node && node.nodeType && !this.isBody(node) && (node.nodeType == 1 || node.nodeType)) {
                tagName = utils.listToMap(utils.isArray(tagName) ? tagName : [tagName]);
                node = node.nodeType == 3 || !includeSelf ? node.parentNode : node;
                while (node && node.tagName && node.nodeType != 9) {
                    if(excludeFn && excludeFn(node))
                        break;
                    if (tagName[node.tagName])
                        return node;
                    node = node.parentNode;
                }
            }

            return null;
        },
        
        findParents: function (node, includeSelf, tester, closerFirst) {
            var parents = includeSelf && ( tester && tester(node) || !tester ) ? [node] : [];
            while (node = domUtils.findParent(node, tester)) {
                parents.push(node);
            }
            return closerFirst ? parents : parents.reverse();
        },

        
        insertAfter : function (node, nodeToInsert) {
            return node.parentNode.insertBefore(nodeToInsert, node.nextSibling);
        },

        
        remove :  function (node, keepChildren) {
            var parent = node.parentNode,
                child;
            if (parent) {
                if (keepChildren && node.hasChildNodes()) {
                    while (child = node.firstChild) {
                        parent.insertBefore(child, node);
                    }
                }
                parent.removeChild(node);
            }
            return node;
        },

        
        getNextDomNode : function(node, startFromChild, filter, guard) {
            return getDomNode(node, 'firstChild', 'nextSibling', startFromChild, filter, guard);

        },
        
        isBookmarkNode : function(node) {
            return node.nodeType == 1 && node.id && /^_baidu_bookmark_/i.test(node.id);
        },
        
        getWindow : function (node) {
            var doc = node.ownerDocument || node;
            return doc.defaultView || doc.parentWindow;
        },
        
        getCommonAncestor : function(nodeA, nodeB) {
            if (nodeA === nodeB)
                return nodeA;
            var parentsA = [nodeA] ,parentsB = [nodeB], parent = nodeA,
                i = -1;


            while (parent = parent.parentNode) {

                if (parent === nodeB)
                    return parent;
                parentsA.push(parent)
            }
            parent = nodeB;
            while (parent = parent.parentNode) {
                if (parent === nodeA)
                    return parent;
                parentsB.push(parent)
            }

            parentsA.reverse();
            parentsB.reverse();
            while (i++,parentsA[i] === parentsB[i]);
            return i == 0 ? null : parentsA[i - 1];

        },
        
        clearEmptySibling : function(node, ingoreNext, ingorePre) {
            function clear(next, dir) {
                var tmpNode;
                while(next && !domUtils.isBookmarkNode(next) && (domUtils.isEmptyInlineElement(next)
                    || !new RegExp('[^\t\n\r' + domUtils.fillChar + ']').test(next.nodeValue) )){
                    tmpNode = next[dir];
                    domUtils.remove(next);
                    next = tmpNode;
                }
            }

            !ingoreNext && clear(node.nextSibling, 'nextSibling');
            !ingorePre && clear(node.previousSibling, 'previousSibling');
        },

        
        split: function (node, offset) {
            var doc = node.ownerDocument;
            if (browser.ie && offset == node.nodeValue.length) {
                var next = doc.createTextNode('');
                return domUtils.insertAfter(node, next);
            }

            var retval = node.splitText(offset);

            if (browser.ie8) {
                var tmpNode = doc.createTextNode('');
                domUtils.insertAfter(retval, tmpNode);
                domUtils.remove(tmpNode);

            }

            return retval;
        },

        
        isWhitespace : function(node) {
            return !new RegExp('[^ \t\n\r' + domUtils.fillChar + ']').test(node.nodeValue);
        },
        
        getXY : function (element) {
            var x = 0,y = 0;
            while (element.offsetParent) {
                y += element.offsetTop;
                x += element.offsetLeft;
                element = element.offsetParent;
            }

            return {
                'x': x,
                'y': y
            };
        },
        
        on : function (obj, type, handler) {
            var types = type instanceof Array ? type : [type],
                k = types.length;
            if (k) while (k --) {
                type = types[k];
                if (obj.addEventListener) {
                    obj.addEventListener(type, handler, false);
                } else {
                    if(!handler._d)
                        handler._d ={};
                    var key = type+handler.toString();
                    if(!handler._d[key]){
                         handler._d[key] =  function(evt) {
                            return handler.call(evt.srcElement, evt || window.event);
                        };

                        obj.attachEvent('on' + type,handler._d[key]);
                    }
                }
            }

            obj = null;
        },

        
        un : function (obj, type, handler) {
            var types = type instanceof Array ? type : [type],
                k = types.length;
            if (k) while (k --) {
                type = types[k];
                if (obj.removeEventListener) {
                    obj.removeEventListener(type, handler, false);
                } else {
                    var key = type+handler.toString();
                    obj.detachEvent('on' + type, handler._d ? handler._d[key] : handler);
                    if(handler._d &&  handler._d[key]){
                        delete handler._d[key];
                    }
                }
            }
        },

        
         isSameElement : function(nodeA, nodeB) {
            
            if (nodeA.tagName != nodeB.tagName)
                return 0;

            var thisAttribs = nodeA.attributes,
                otherAttribs = nodeB.attributes;


            if (!ie && thisAttribs.length != otherAttribs.length)
                return 0;

            var attrA,attrB,al = 0,bl=0;
            for(var i= 0;attrA=thisAttribs[i++];){
                if(attrA.nodeName == 'style' ){
                    if(attrA.specified)al++;
                    if(domUtils.isSameStyle(nodeA,nodeB)){
                        continue
                    }else{
                        return 0;
                    }
                }
                if(ie){
                    if(attrA.specified){
                        al++;
                        attrB = otherAttribs.getNamedItem(attrA.nodeName);
                    }else{
                        continue;
                    }
                }else{
                    attrB = nodeB.attributes[attrA.nodeName];
                }
                if(!attrB.specified)return 0;
                if(attrA.nodeValue != attrB.nodeValue)
                    return 0;

            }
            if(ie){
                for(i=0;attrB = otherAttribs[i++];){
                    if(attrB.specified){
                        bl++;
                    }
                }
                if(al!=bl)
                    return 0;
            }
            return 1;
        },

        
        isSameStyle : function (elementA, elementB) {
            var styleA = elementA.style.cssText.replace(/( ?; ?)/g,';').replace(/( ?: ?)/g,':'),
                styleB = elementB.style.cssText.replace(/( ?; ?)/g,';').replace(/( ?: ?)/g,':');

            if(!styleA || !styleB){
                return styleA == styleB ? 1: 0;
            }
            styleA = styleA.split(';');
            styleB = styleB.split(';');

            if(styleA.length != styleB.length)
                return 0;
            for(var i = 0,ci;ci=styleA[i++];){
                if(utils.indexOf(styleB,ci) == -1)
                    return 0
            }
            return 1;
        },

        
        isBlockElm : function (node) {
            return node.nodeType == 1 && (dtd.$block[node.tagName]||styleBlock[domUtils.getComputedStyle(node,'display')])&& !dtd.$nonChild[node.tagName];
        },

        
        isBody : function(node) {
            return  node && node.nodeType == 1 && node.tagName.toLowerCase() == 'body';
        },
        
        breakParent : function(node, parent) {
            var tmpNode, parentClone = node, clone = node, leftNodes, rightNodes;
            do {
                parentClone = parentClone.parentNode;

                if (leftNodes) {
                    tmpNode = parentClone.cloneNode(false);
                    tmpNode.appendChild(leftNodes);
                    leftNodes = tmpNode;

                    tmpNode = parentClone.cloneNode(false);
                    tmpNode.appendChild(rightNodes);
                    rightNodes = tmpNode;

                } else {
                    leftNodes = parentClone.cloneNode(false);
                    rightNodes = leftNodes.cloneNode(false);
                }


                while (tmpNode = clone.previousSibling) {
                    leftNodes.insertBefore(tmpNode, leftNodes.firstChild);
                }

                while (tmpNode = clone.nextSibling) {
                    rightNodes.appendChild(tmpNode);
                }

                clone = parentClone;
            } while (parent !== parentClone);

            tmpNode = parent.parentNode;
            tmpNode.insertBefore(leftNodes, parent);
            tmpNode.insertBefore(rightNodes, parent);
            tmpNode.insertBefore(node, rightNodes);
            domUtils.remove(parent);
            return node;
        },

        
        isEmptyInlineElement : function(node) {

            if (node.nodeType != 1 || !dtd.$removeEmpty[ node.tagName ])
                return 0;

            node = node.firstChild;
            while (node) {
                if (domUtils.isBookmarkNode(node))
                    return 0;
                if (node.nodeType == 1 && !domUtils.isEmptyInlineElement(node) ||
                    node.nodeType == 3 && !domUtils.isWhitespace(node)
                    ) {
                    return 0;
                }
                node = node.nextSibling;
            }
            return 1;

        },

        
        trimWhiteTextNode : function(node) {

            function remove(dir) {
                var child;
                while ((child = node[dir]) && child.nodeType == 3 && domUtils.isWhitespace(child))
                    node.removeChild(child)

            }

            remove('firstChild');
            remove('lastChild');

        },

        
        mergChild : function(node, tagName, attrs) {

            var list = domUtils.getElementsByTagName(node, node.tagName.toLowerCase());
            for (var i = 0,ci; ci = list[i++];) {

                if (!ci.parentNode || domUtils.isBookmarkNode(ci)) continue;
                if (ci.tagName.toLowerCase() == 'span') {
                    if (node === ci.parentNode) {
                        domUtils.trimWhiteTextNode(node);
                        if (node.childNodes.length == 1) {
                            node.style.cssText = ci.style.cssText + ";" + node.style.cssText;
                            domUtils.remove(ci, true);
                            continue;
                        }
                    }
                    ci.style.cssText = node.style.cssText + ';' + ci.style.cssText;
                    if (attrs) {
                        var style = attrs.style;
                        if (style) {
                            style = style.split(';');
                            for (var j = 0,s; s = style[j++];) {
                                ci.style[utils.cssStyleToDomStyle(s.split(':')[0])] = s.split(':')[1];
                            }
                        }
                    }
                    if (domUtils.isSameStyle(ci, node)) {

                        domUtils.remove(ci, true)
                    }
                    continue;
                }
                if (domUtils.isSameElement(node, ci)) {
                    domUtils.remove(ci, true);
                }
            }

            if (tagName == 'span') {
                var as = domUtils.getElementsByTagName(node, 'a');
                for (var i = 0,ai; ai = as[i++];) {

                    ai.style.cssText = ';' + node.style.cssText;

                    ai.style.textDecoration = 'underline';

                }
            }
        },

        
        getElementsByTagName : function(node, name) {
            var list = node.getElementsByTagName(name),arr = [];
            for (var i = 0,ci; ci = list[i++];) {
                arr.push(ci)
            }
            return arr;
        },
        
        mergToParent : function(node) {
            var parent = node.parentNode;

            while (parent && dtd.$removeEmpty[parent.tagName]) {
                if (parent.tagName == node.tagName || parent.tagName == 'A') {//针对a标签单独处理
                    domUtils.trimWhiteTextNode(parent);
                    if (parent.tagName == 'SPAN' && !domUtils.isSameStyle(parent, node)
                        || (parent.tagName == 'A' && node.tagName == 'SPAN')) {
                        if (parent.childNodes.length > 1 || parent !== node.parentNode) {
                            node.style.cssText = parent.style.cssText + ";" + node.style.cssText;
                            parent = parent.parentNode;
                            continue;
                        } else {

                            parent.style.cssText += ";" + node.style.cssText;
                            if (parent.tagName == 'A') {
                                parent.style.textDecoration = 'underline';
                            }

                        }
                    }
                    if(parent.tagName != 'A' ){
                       
                         parent === node.parentNode &&  domUtils.remove(node, true);
                        break;
                    }
                }
                parent = parent.parentNode;
            }

        },
        
        mergSibling : function(node, ingorePre, ingoreNext) {
            function merg(rtl, start, node) {
                var next;
                if ((next = node[rtl]) && !domUtils.isBookmarkNode(next) && next.nodeType == 1 && domUtils.isSameElement(node, next)) {
                    while (next.firstChild) {
                        if (start == 'firstChild') {
                            node.insertBefore(next.lastChild, node.firstChild);
                        } else {
                            node.appendChild(next.firstChild)
                        }

                    }
                    domUtils.remove(next);
                }
            }

            !ingorePre && merg('previousSibling', 'firstChild', node);
            !ingoreNext && merg('nextSibling', 'lastChild', node);
        },

        
        unselectable :
            gecko ?
                function(node) {
                    node.style.MozUserSelect = 'none';
                }
                : webkit ?
                function(node) {
                    node.style.KhtmlUserSelect = 'none';
                }
                :
                function(node) {
                    node.onselectstart = function () { return false; };
                    node.onclick = node.onkeyup = node.onkeydown = function(){return false};
                    node.unselectable = 'on';
                    node.setAttribute("unselectable","on");
                    for (var i = 0,ci; ci = node.all[i++];) {
                        switch (ci.tagName.toLowerCase()) {
                            case 'iframe' :
                            case 'textarea' :
                            case 'input' :
                            case 'select' :

                                break;
                            default :
                                ci.unselectable = 'on';
                                node.setAttribute("unselectable","on");
                        }
                    }
                },
        
        removeAttributes : function (elm, attrNames) {
            for(var i = 0,ci;ci=attrNames[i++];){
                ci = attrFix[ci] || ci;
                switch (ci){
                    case 'className':
                        elm[ci] = '';
                        break;
                    case 'style':
                        elm.style.cssText = '';
                        !browser.ie && elm.removeAttributeNode(elm.getAttributeNode('style'))
                }
                elm.removeAttribute(ci);
            }
        },
        creElm : function(doc,tag,attrs){
            return this.setAttributes(doc.createElement(tag),attrs)
        },
        
        setAttributes : function(node, attrs) {
            for (var name in attrs) {
                var value = attrs[name];
                switch (name) {
                    case 'class':
                        node.className = value;
                        break;
                    case 'style' :
                        node.style.cssText = node.style.cssText + ";" + value;
                        break;
                    case 'innerHTML':
                        node[name] = value;
                        break;
                    case 'value':
                        node.value = value;
                        break;
                    default:
                        node.setAttribute(attrFix[name]||name, value);
                }
            }

            return node;
        },

        
        getComputedStyle : function (element, styleName) {
            function fixUnit(key, val) {
                if (key == 'font-size' && /pt$/.test(val)) {
                    val = Math.round(parseFloat(val) / 0.75) + 'px';
                }
                return val;
            }
            if(element.nodeType == 3){
                element = element.parentNode;
            }
            if (browser.ie && browser.version < 9 && styleName == 'font-size' && !element.style.fontSize &&
                !dtd.$empty[element.tagName] && !dtd.$nonChild[element.tagName]) {
                var span = element.ownerDocument.createElement('span');
                span.style.cssText = 'padding:0;border:0;font-family:simsun;';
                span.innerHTML = '.';
                element.appendChild(span);
                var result = span.offsetHeight;
                element.removeChild(span);
                span = null;
                return result + 'px';
            }

            try {
                var value = domUtils.getStyle(element, styleName) ||
                    (window.getComputedStyle ? domUtils.getWindow(element).getComputedStyle(element, '').getPropertyValue(styleName) :
                        ( element.currentStyle || element.style )[utils.cssStyleToDomStyle(styleName)]);

            } catch(e) {
                return null;
            }

            return fixUnit(styleName, utils.fixColor(styleName, value));
        },

        
        removeClasses : function (element, classNames) {
            element.className = (' ' + element.className + ' ').replace(
                new RegExp('(?:\\s+(?:' + classNames.join('|') + '))+\\s+', 'g'), ' ');
        },
        
        removeStyle : function(node, name) {
            node.style[utils.cssStyleToDomStyle(name)] = '';
            if(!node.style.cssText)
                domUtils.removeAttributes(node,['style'])
        },
        
        hasClass : function (element, className) {
            return ( ' ' + element.className + ' ' ).indexOf(' ' + className + ' ') > -1;
        },

        
        preventDefault : function (evt) {
            evt.preventDefault  ? evt.preventDefault() : (evt.returnValue = false);
        },
        
        getStyle : function(element, name) {
            var value = element.style[ utils.cssStyleToDomStyle(name) ];
            return utils.fixColor(name, value);
        },
        setStyle: function (element, name, value) {
            element.style[utils.cssStyleToDomStyle(name)] = value;
        },
        setStyles: function (element, styles) {
            for (var name in styles) {
                if (styles.hasOwnProperty(name)) {
                    domUtils.setStyle(element, name, styles[name]);
                }
            }
        },
        
        removeDirtyAttr : function(node) {
            for (var i = 0,ci,nodes = node.getElementsByTagName('*'); ci = nodes[i++];) {
                ci.removeAttribute('_moz_dirty')
            }
            node.removeAttribute('_moz_dirty')
        },
        
        getChildCount : function (node, fn) {
            var count = 0,first = node.firstChild;
            fn = fn || function() {
                return 1
            };
            while (first) {
                if (fn(first))
                    count++;
                first = first.nextSibling;
            }
            return count;
        },

        
        isEmptyNode : function(node) {
            return !node.firstChild || domUtils.getChildCount(node, function(node) {
                return  !domUtils.isBr(node) && !domUtils.isBookmarkNode(node) && !domUtils.isWhitespace(node)
            }) == 0
        },
        
        clearSelectedArr : function(nodes) {
            var node;
            while(node = nodes.pop()){
                domUtils.removeAttributes(node,['class']);
            }
        },


        
        scrollToView : function(node, win, offsetTop) {
            var
                getViewPaneSize = function() {
                    var doc = win.document,
                        mode = doc.compatMode == 'CSS1Compat';

                    return {
                        width : ( mode ? doc.documentElement.clientWidth : doc.body.clientWidth ) || 0,
                        height : ( mode ? doc.documentElement.clientHeight : doc.body.clientHeight ) || 0
                    };

                },
                getScrollPosition = function(win) {

                    if ('pageXOffset' in win) {
                        return {
                            x : win.pageXOffset || 0,
                            y : win.pageYOffset || 0
                        };
                    }
                    else {
                        var doc = win.document;
                        return {
                            x : doc.documentElement.scrollLeft || doc.body.scrollLeft || 0,
                            y : doc.documentElement.scrollTop || doc.body.scrollTop || 0
                        };
                    }
                };


            var winHeight = getViewPaneSize().height,offset = winHeight * -1 + offsetTop;


            offset += (node.offsetHeight || 0);

            var elementPosition = domUtils.getXY(node);
            offset += elementPosition.y;

            var currentScroll = getScrollPosition(win).y;
            if (offset > currentScroll || offset < currentScroll - winHeight)
                win.scrollTo(0, offset + (offset < 0 ? -20 : 20));
        },
        
        isBr : function(node) {
            return node.nodeType == 1 && node.tagName == 'BR';
        },
      
        isFillChar : function(node){
            return node.nodeType == 3 && !node.nodeValue.replace(new RegExp( domUtils.fillChar ),'').length
        },
        isStartInblock : function(range){
            
            var tmpRange = range.cloneRange(),
                flag = 0,
                start = tmpRange.startContainer,
                tmp;

            while(start && domUtils.isFillChar(start)){
                tmp = start;
                start = start.previousSibling
            }
            if(tmp){
                tmpRange.setStartBefore(tmp);
                start = tmpRange.startContainer;

            }
            if(start.nodeType == 1 && domUtils.isEmptyNode(start) && tmpRange.startOffset == 1){
                tmpRange.setStart(start,0).collapse(true);
            }
            while(!tmpRange.startOffset){
                start = tmpRange.startContainer;


                if(domUtils.isBlockElm(start)||domUtils.isBody(start)){
                    flag = 1;
                    break;
                }
                var pre = tmpRange.startContainer.previousSibling,
                    tmpNode;
                if(!pre){
                    tmpRange.setStartBefore(tmpRange.startContainer);
                }else{
                    while(pre && domUtils.isFillChar(pre)){
                        tmpNode = pre;
                        pre = pre.previousSibling;

                    }
                    if(tmpNode){
                        tmpRange.setStartBefore(tmpNode);
                    }else
                        tmpRange.setStartBefore(tmpRange.startContainer);
                }



            }
           
            return flag && !domUtils.isBody(tmpRange.startContainer) ? 1 : 0;
        },
        isEmptyBlock : function(node){
            var reg = new RegExp( '[ \t\r\n' + domUtils.fillChar+']', 'g' );
            if(node[browser.ie?'innerText':'textContent'].replace(reg,'').length >0)
                return 0;

            for(var n in dtd.$isNotEmpty){
                if(node.getElementsByTagName(n).length)
                    return 0;
            }
           
            return 1;
        },
       
        setViewportOffset: function (element, offset){
            var left = parseInt(element.style.left) | 0;
            var top = parseInt(element.style.top) | 0;
            var rect = element.getBoundingClientRect();
            var offsetLeft = offset.left - rect.left;
            var offsetTop = offset.top - rect.top;
            if (offsetLeft) {
                element.style.left = left + offsetLeft + 'px';
            }
            if (offsetTop) {
                element.style.top = top + offsetTop + 'px';
            }
        },
        fillNode : function(doc,node){
            var tmpNode = browser.ie ? doc.createTextNode(domUtils.fillChar) : doc.createElement('br');
            node.innerHTML = '';
            node.appendChild(tmpNode);

        },
        moveChild : function(src,tag,dir){
            while(src.firstChild){
                if(dir && tag.firstChild){
                    tag.insertBefore(src.lastChild,tag.firstChild);
                }else{
                    tag.appendChild(src.firstChild)
                }
            }
           
        },
        hasNoAttributes : function(node){
            return browser.ie ? /^<\w+\s*?>/.test(node.outerHTML) :node.attributes.length == 0;
        },
        isCustomeNode : function(node){
            return node.nodeType == 1 && node.getAttribute('_ue_div_script');
        }

    }; 

    var fillCharReg = new RegExp( domUtils.fillChar, 'g' );


(function() {
    var guid = 0,
        fillChar = domUtils.fillChar,
        fillData;

    
    function updateCollapse( range ) {
        range.collapsed =
            range.startContainer && range.endContainer &&
                range.startContainer === range.endContainer &&
                range.startOffset == range.endOffset;
    }
    
    function setEndPoint( toStart, node, offset, range ) {
        if ( node.nodeType == 1 && (dtd.$empty[node.tagName] || dtd.$nonChild[node.tagName])) {
            offset = domUtils.getNodeIndex( node ) + (toStart ? 0 : 1);
            node = node.parentNode;
        }
        if ( toStart ) {
            range.startContainer = node;
            range.startOffset = offset;
            if ( !range.endContainer ) {
                range.collapse( true );
            }
        } else {
            range.endContainer = node;
            range.endOffset = offset;
            if ( !range.startContainer ) {
                range.collapse( false );
            }
        }
        updateCollapse( range );
        return range;
    }

    function execContentsAction ( range, action ) {

        var start = range.startContainer,
            end = range.endContainer,
            startOffset = range.startOffset,
            endOffset = range.endOffset,
            doc = range.document,
            frag = doc.createDocumentFragment(),
            tmpStart,tmpEnd;

        if ( start.nodeType == 1 ) {
            start = start.childNodes[startOffset] || (tmpStart = start.appendChild( doc.createTextNode( '' ) ));
        }
        if ( end.nodeType == 1 ) {
            end = end.childNodes[endOffset] || (tmpEnd = end.appendChild( doc.createTextNode( '' ) ));
        }

        if ( start === end && start.nodeType == 3 ) {

            frag.appendChild( doc.createTextNode( start.substringData( startOffset, endOffset - startOffset ) ) );
            if ( action ) {
                start.deleteData( startOffset, endOffset - startOffset );
                range.collapse( true );
            }

            return frag;
        }


        var current,currentLevel,clone = frag,
            startParents = domUtils.findParents( start, true ),endParents = domUtils.findParents( end, true );
        for ( var i = 0; startParents[i] == endParents[i]; i++ );


        for ( var j = i,si; si = startParents[j]; j++ ) {
            current = si.nextSibling;
            if ( si == start ) {
                if ( !tmpStart ) {
                    if ( range.startContainer.nodeType == 3 ) {
                        clone.appendChild( doc.createTextNode( start.nodeValue.slice( startOffset ) ) );
                        if ( action ) {
                            start.deleteData( startOffset, start.nodeValue.length - startOffset );

                        }
                    } else {
                        clone.appendChild( !action ? start.cloneNode( true ) : start );
                    }
                }

            } else {
                currentLevel = si.cloneNode( false );
                clone.appendChild( currentLevel );
            }


            while ( current ) {
                if ( current === end || current === endParents[j] )break;
                si = current.nextSibling;
                clone.appendChild( !action ? current.cloneNode( true ) : current );


                current = si;
            }
            clone = currentLevel;

        }


        clone = frag;

        if ( !startParents[i] ) {
            clone.appendChild( startParents[i - 1].cloneNode( false ) );
            clone = clone.firstChild;
        }
        for ( var j = i,ei; ei = endParents[j]; j++ ) {
            current = ei.previousSibling;
            if ( ei == end ) {
                if ( !tmpEnd && range.endContainer.nodeType == 3 ) {
                    clone.appendChild( doc.createTextNode( end.substringData( 0, endOffset ) ) );
                    if ( action ) {
                        end.deleteData( 0, endOffset );

                    }
                }


            } else {
                currentLevel = ei.cloneNode( false );
                clone.appendChild( currentLevel );
            }
            if ( j != i || !startParents[i] ) {
                while ( current ) {
                    if ( current === start )break;
                    ei = current.previousSibling;
                    clone.insertBefore( !action ? current.cloneNode( true ) : current, clone.firstChild );


                    current = ei;
                }

            }
            clone = currentLevel;
        }


        if ( action ) {
            range.setStartBefore( !endParents[i] ? endParents[i - 1] : !startParents[i] ? startParents[i - 1] : endParents[i] ).collapse( true )
        }
        tmpStart && domUtils.remove( tmpStart );
        tmpEnd && domUtils.remove( tmpEnd );
        return frag;
    }


    
    var Range = dom.Range = function( document ) {
        var me = this;
        me.startContainer =
        me.startOffset =
        me.endContainer =
        me.endOffset = null;
        me.document = document;
        me.collapsed = true;
    };

    function removeFillData(doc,excludeNode){
        try{
            if ( fillData && domUtils.inDoc(fillData,doc)  ) {

                  if(!fillData.nodeValue.replace( fillCharReg, '' ).length){
                      var tmpNode = fillData.parentNode;
                      domUtils.remove(fillData);
                      while(tmpNode && domUtils.isEmptyInlineElement(tmpNode) && !tmpNode.contains(excludeNode)){
                          fillData = tmpNode.parentNode;
                          domUtils.remove(tmpNode);
                          tmpNode = fillData
                      }

                  }else
                      fillData.nodeValue = fillData.nodeValue.replace( fillCharReg, '' )
            }
        }catch(e){}

    }
    function mergSibling(node,dir){
       var tmpNode;
       node = node[dir];
       while(node && domUtils.isFillChar(node)){
           tmpNode = node[dir];
           domUtils.remove(node);
           node = tmpNode;
       }
   }
    Range.prototype = {
        
        cloneContents : function() {
            return this.collapsed ? null : execContentsAction( this, 0 );
        },
        
        deleteContents : function() {
            if ( !this.collapsed )
                execContentsAction( this, 1 );
            if(browser.webkit){
                var txt = this.startContainer;
                if(txt.nodeType == 3 && !txt.nodeValue.length){

                    this.setStartBefore(txt).collapse(true);
                    domUtils.remove(txt)
                }
            }
            return this;
        },
        
        extractContents : function() {
            return this.collapsed ? null : execContentsAction( this, 2 );
        },
        
        setStart : function( node, offset ) {
            return setEndPoint( true, node, offset, this );
        },
        
        setEnd : function( node, offset ) {
            return setEndPoint( false, node, offset, this );
        },
        
        setStartAfter : function( node ) {
            return this.setStart( node.parentNode, domUtils.getNodeIndex( node ) + 1 );
        },
        
        setStartBefore : function( node ) {
            return this.setStart( node.parentNode, domUtils.getNodeIndex( node ) );
        },
        
        setEndAfter : function( node ) {
            return this.setEnd( node.parentNode, domUtils.getNodeIndex( node ) + 1 );
        },
        
        setStartAtFirst : function(node){
            return this.setStart(node,0)
        },
        
        setStartAtLast : function(node){
            return this.setStart(node,node.nodeType == 3 ? node.nodeValue.length : node.childNodes.length)
        },
        
        setEndAtFirst : function(node){
            return this.setEnd(node,0)
        },
        
        setEndAtLast : function(node){
            return this.setEnd(node,node.nodeType == 3 ? node.nodeValue.length : node.childNodes.length)
        },
        
        setEndBefore : function( node ) {
            return this.setEnd( node.parentNode, domUtils.getNodeIndex( node ) );
        },
        
        selectNode : function( node ) {
            return this.setStartBefore( node ).setEndAfter( node );
        },
        
        selectNodeContents : function( node ) {
            return this.setStart( node, 0 ).setEnd( node, node.nodeType == 3 ? node.nodeValue.length : node.childNodes.length );
        },

        
        cloneRange : function() {
            var me = this,range = new Range( me.document );
            return range.setStart( me.startContainer, me.startOffset ).setEnd( me.endContainer, me.endOffset );

        },

        
        collapse : function( toStart ) {
            var me = this;
            if ( toStart ) {
                me.endContainer = me.startContainer;
                me.endOffset = me.startOffset;
            }
            else {
                me.startContainer = me.endContainer;
                me.startOffset = me.endOffset;
            }

            me.collapsed = true;
            return me;
        },
        
        shrinkBoundary : function( ignoreEnd ) {
            var me = this,child,
                collapsed = me.collapsed;
            while ( me.startContainer.nodeType == 1
                && (child = me.startContainer.childNodes[me.startOffset])
                && child.nodeType == 1  && !domUtils.isBookmarkNode(child)
                && !dtd.$empty[child.tagName] && !dtd.$nonChild[child.tagName] ) {
                me.setStart( child, 0 );
            }
            if ( collapsed )
                return me.collapse( true );
            if ( !ignoreEnd ) {
                while ( me.endContainer.nodeType == 1//是element
                    && me.endOffset > 0
                    && (child = me.endContainer.childNodes[me.endOffset - 1])
                    && child.nodeType == 1 && !domUtils.isBookmarkNode(child)
                    && !dtd.$empty[child.tagName] && !dtd.$nonChild[child.tagName]) {
                    me.setEnd( child, child.childNodes.length );
                }
            }

            return me;
        },
        
        getCommonAncestor : function( includeSelf, ignoreTextNode ) {
            var start = this.startContainer,
                end = this.endContainer;
            if ( start === end ) {
                if ( includeSelf && start.nodeType == 1 && this.startOffset == this.endOffset - 1 ) {
                    return start.childNodes[this.startOffset];
                }
                return ignoreTextNode && start.nodeType == 3 ? start.parentNode : start;
            }
            return domUtils.getCommonAncestor( start, end );

        },
        
        trimBoundary : function( ignoreEnd ) {
            this.txtToElmBoundary();
            var start = this.startContainer,
                offset = this.startOffset,
                collapsed = this.collapsed,
                end = this.endContainer;
            if ( start.nodeType == 3 ) {
                if ( offset == 0 ) {
                    this.setStartBefore( start )
                } else {
                    if ( offset >= start.nodeValue.length ) {
                        this.setStartAfter( start );
                    } else {
                        var textNode = domUtils.split( start, offset );
                        if ( start === end )
                            this.setEnd( textNode, this.endOffset - offset );
                        else if ( start.parentNode === end )
                            this.endOffset += 1;
                        this.setStartBefore( textNode );
                    }
                }
                if ( collapsed ) {
                    return this.collapse( true );
                }
            }
            if ( !ignoreEnd ) {
                offset = this.endOffset;
                end = this.endContainer;
                if ( end.nodeType == 3 ) {
                    if ( offset == 0 ) {
                        this.setEndBefore( end );
                    } else {
                        if ( offset >= end.nodeValue.length ) {
                            this.setEndAfter( end );
                        } else {
                            domUtils.split( end, offset );
                            this.setEndAfter( end );
                        }
                    }

                }
            }
            return this;
        },
        
        txtToElmBoundary : function() {
            function adjust( r, c ) {
                var container = r[c + 'Container'],
                    offset = r[c + 'Offset'];
                if ( container.nodeType == 3 ) {
                    if ( !offset ) {
                        r['set' + c.replace( /(\w)/, function( a ) {
                            return a.toUpperCase()
                        } ) + 'Before']( container )
                    } else if ( offset >= container.nodeValue.length ) {
                        r['set' + c.replace( /(\w)/, function( a ) {
                            return a.toUpperCase()
                        } ) + 'After' ]( container )
                    }
                }
            }

            if ( !this.collapsed ) {
                adjust( this, 'start' );
                adjust( this, 'end' );
            }

            return this;
        },

        
        insertNode : function( node ) {
            var first = node,length = 1;
            if ( node.nodeType == 11 ) {
                first = node.firstChild;
                length = node.childNodes.length;
            }


            this.trimBoundary( true );

            var start = this.startContainer,
                offset = this.startOffset;

            var nextNode = start.childNodes[ offset ];

            if ( nextNode ) {
                start.insertBefore( node, nextNode );

            }
            else {
                start.appendChild( node );
            }


            if ( first.parentNode === this.endContainer ) {
                this.endOffset = this.endOffset + length;
            }


            return this.setStartBefore( first );
        },
        
        setCursor : function( toEnd ,notFillData) {
            return this.collapse( toEnd ? false : true ).select(notFillData);
        },
        
        createBookmark : function( serialize, same ) {
            var endNode,
                startNode = this.document.createElement( 'span' );
            startNode.style.cssText = 'display:none;line-height:0px;';
            startNode.appendChild( this.document.createTextNode( '\uFEFF' ) );
            startNode.id = '_baidu_bookmark_start_' + (same ? '' : guid++);

            if ( !this.collapsed ) {
                endNode = startNode.cloneNode( true );
                endNode.id = '_baidu_bookmark_end_' + (same ? '' : guid++);
            }
            this.insertNode( startNode );

            if ( endNode ) {
                this.collapse( false ).insertNode( endNode );
                this.setEndBefore( endNode )
            }
            this.setStartAfter( startNode );

            return {
                start : serialize ? startNode.id : startNode,
                end : endNode ? serialize ? endNode.id : endNode : null,
                id : serialize
            }
        },
        
        moveToBookmark : function( bookmark ) {
            var start = bookmark.id ? this.document.getElementById( bookmark.start ) : bookmark.start,
                end = bookmark.end && bookmark.id ? this.document.getElementById( bookmark.end ) : bookmark.end;
            this.setStartBefore( start );
            domUtils.remove( start );
            if ( end ) {
                this.setEndBefore( end );
                domUtils.remove( end )
            } else {
                this.collapse( true );
            }

            return this;
        },
        
        enlarge : function( toBlock, stopFn ) {
            var isBody = domUtils.isBody,
                pre,node,tmp = this.document.createTextNode( '' );
            if ( toBlock ) {
                node = this.startContainer;
                if ( node.nodeType == 1 ) {
                    if ( node.childNodes[this.startOffset] ) {
                        pre = node = node.childNodes[this.startOffset]
                    } else {
                        node.appendChild( tmp );
                        pre = node = tmp;
                    }
                } else {
                    pre = node;
                }

                while ( 1 ) {
                    if ( domUtils.isBlockElm( node ) ) {
                        node = pre;
                        while ( (pre = node.previousSibling) && !domUtils.isBlockElm( pre ) ) {
                            node = pre;
                        }
                        this.setStartBefore( node );

                        break;
                    }
                    pre = node;
                    node = node.parentNode;
                }
                node = this.endContainer;
                if ( node.nodeType == 1 ) {
                    if(pre = node.childNodes[this.endOffset]) {
                        node.insertBefore( tmp, pre );
                    }else{
                        node.appendChild(tmp)
                    }

                    pre = node = tmp;
                } else {
                    pre = node;
                }

                while ( 1 ) {
                    if ( domUtils.isBlockElm( node ) ) {
                        node = pre;
                        while ( (pre = node.nextSibling) && !domUtils.isBlockElm( pre ) ) {
                            node = pre;
                        }
                        this.setEndAfter( node );

                        break;
                    }
                    pre = node;
                    node = node.parentNode;
                }
                if ( tmp.parentNode === this.endContainer ) {
                    this.endOffset--;
                }
                domUtils.remove( tmp )
            }
            if ( !this.collapsed ) {
                while ( this.startOffset == 0 ) {
                    if ( stopFn && stopFn( this.startContainer ) )
                        break;
                    if ( isBody( this.startContainer ) )break;
                    this.setStartBefore( this.startContainer );
                }
                while ( this.endOffset == (this.endContainer.nodeType == 1 ? this.endContainer.childNodes.length : this.endContainer.nodeValue.length) ) {
                    if ( stopFn && stopFn( this.endContainer ) )
                        break;
                    if ( isBody( this.endContainer ) )break;

                    this.setEndAfter( this.endContainer )
                }
            }

            return this;
        },
        
        adjustmentBoundary : function() {
            if(!this.collapsed){
                while ( !domUtils.isBody( this.startContainer ) &&
                    this.startOffset == this.startContainer[this.startContainer.nodeType == 3 ? 'nodeValue' : 'childNodes'].length
                ) {
                    this.setStartAfter( this.startContainer );
                }
                while ( !domUtils.isBody( this.endContainer ) && !this.endOffset ) {
                    this.setEndBefore( this.endContainer );
                }
            }
            return this;
        },
        
        applyInlineStyle : function( tagName, attrs ,list) {
            
            if(this.collapsed)return this;
            this.trimBoundary().enlarge( false,
                function( node ) {
                    return node.nodeType == 1 && domUtils.isBlockElm( node )
                } ).adjustmentBoundary();


            var bookmark = this.createBookmark(),
                end = bookmark.end,
                filterFn = function( node ) {
                    return node.nodeType == 1 ? node.tagName.toLowerCase() != 'br' : !domUtils.isWhitespace( node )
                },
                current = domUtils.getNextDomNode( bookmark.start, false, filterFn ),
                node,
                pre,
                range = this.cloneRange();

            while ( current && (domUtils.getPosition( current, end ) & domUtils.POSITION_PRECEDING) ) {


                if ( current.nodeType == 3 || dtd[tagName][current.tagName] ) {
                    range.setStartBefore( current );
                    node = current;
                    while ( node && (node.nodeType == 3 || dtd[tagName][node.tagName]) && node !== end ) {

                        pre = node;
                        node = domUtils.getNextDomNode( node, node.nodeType == 1, null, function( parent ) {
                            return dtd[tagName][parent.tagName]
                        } )
                    }

                    var frag = range.setEndAfter( pre ).extractContents(),elm;
                    if(list && list.length > 0){
                        var level,top;
                        top = level = list[0].cloneNode(false);
                        for(var i=1,ci;ci=list[i++];){

                            level.appendChild(ci.cloneNode(false));
                            level = level.firstChild;

                        }
                        elm = level;

                    }else{
                        elm = range.document.createElement( tagName )
                    }
                    
                    if ( attrs ) {
                        domUtils.setAttributes( elm, attrs )
                    }
                    elm.appendChild( frag );


                    range.insertNode( list ?  top : elm );
                    var aNode;
                    if(tagName == 'span' && attrs.style && /text\-decoration/.test(attrs.style) && (aNode = domUtils.findParentByTagName(elm,'a',true)) ){

                            domUtils.setAttributes(aNode,attrs);
                            domUtils.remove(elm,true);
                            elm = aNode;



                    }else{
                        domUtils.mergSibling( elm );
                        domUtils.clearEmptySibling( elm );
                    }
                    domUtils.mergChild( elm, tagName,attrs );
                    current = domUtils.getNextDomNode( elm, false, filterFn );
                    domUtils.mergToParent( elm );
                    if ( node === end )break;
                } else {
                    current = domUtils.getNextDomNode( current, true, filterFn )
                }
            }

            return this.moveToBookmark( bookmark );
        },
        
        removeInlineStyle : function( tagName ) {
            if(this.collapsed)return this;
            tagName = utils.isArray( tagName ) ? tagName : [tagName];

            this.shrinkBoundary().adjustmentBoundary();

            var start = this.startContainer,end = this.endContainer;

            while ( 1 ) {

                if ( start.nodeType == 1 ) {
                    if ( utils.indexOf( tagName, start.tagName.toLowerCase() ) > -1 ) {
                        break;
                    }
                    if ( start.tagName.toLowerCase() == 'body' ) {
                        start = null;
                        break;
                    }


                }
                start = start.parentNode;

            }

            while ( 1 ) {
                if ( end.nodeType == 1 ) {
                    if ( utils.indexOf( tagName, end.tagName.toLowerCase() ) > -1 ) {
                        break;
                    }
                    if ( end.tagName.toLowerCase() == 'body' ) {
                        end = null;
                        break;
                    }

                }
                end = end.parentNode;
            }


            var bookmark = this.createBookmark(),
                frag,
                tmpRange;
            if ( start ) {
                tmpRange = this.cloneRange().setEndBefore( bookmark.start ).setStartBefore( start );
                frag = tmpRange.extractContents();
                tmpRange.insertNode( frag );
                domUtils.clearEmptySibling( start, true );
                start.parentNode.insertBefore( bookmark.start, start );

            }

            if ( end ) {
                tmpRange = this.cloneRange().setStartAfter( bookmark.end ).setEndAfter( end );
                frag = tmpRange.extractContents();
                tmpRange.insertNode( frag );
                domUtils.clearEmptySibling( end, false, true );
                end.parentNode.insertBefore( bookmark.end, end.nextSibling );


            }

            var current = domUtils.getNextDomNode( bookmark.start, false, function( node ) {
                return node.nodeType == 1
            } ),next;

            while ( current && current !== bookmark.end ) {

                next = domUtils.getNextDomNode( current, true, function( node ) {
                    return node.nodeType == 1
                } );
                if ( utils.indexOf( tagName, current.tagName.toLowerCase() ) > -1 ) {

                    domUtils.remove( current, true );


                }
                current = next;
            }



            return this.moveToBookmark( bookmark );
        },
        
        getClosedNode : function() {

            var node;
            if ( !this.collapsed ) {
                var range = this.cloneRange().adjustmentBoundary().shrinkBoundary();
                if ( range.startContainer.nodeType == 1 && range.startContainer === range.endContainer && range.endOffset - range.startOffset == 1 ) {
                    var child = range.startContainer.childNodes[range.startOffset];
                    if ( child && child.nodeType == 1 && (dtd.$empty[child.tagName] || dtd.$nonChild[child.tagName])) {
                        node = child;
                    }
                }
            }
            return node;
        },
        
        select : browser.ie ? function( notInsertFillData ,textRange) {

            var nativeRange;

            if ( !this.collapsed )
                this.shrinkBoundary();
            var node = this.getClosedNode();
            if ( node && !textRange) {
                try {
                    nativeRange = this.document.body.createControlRange();
                    nativeRange.addElement( node );
                    nativeRange.select();
                } catch( e ) {
                }
                return this;
            }

            var bookmark = this.createBookmark(),
                start = bookmark.start,
                end;

            nativeRange = this.document.body.createTextRange();
            nativeRange.moveToElementText( start );
            nativeRange.moveStart( 'character', 1 );
            if ( !this.collapsed ) {
                var nativeRangeEnd = this.document.body.createTextRange();
                end = bookmark.end;
                nativeRangeEnd.moveToElementText( end );
                nativeRange.setEndPoint( 'EndToEnd', nativeRangeEnd );

            } else {
                if ( !notInsertFillData && this.startContainer.nodeType != 3 ) {
                    var tmpText = this.document.createTextNode( fillChar ),
                        tmp = this.document.createElement( 'span' );



                    tmp.appendChild( this.document.createTextNode( fillChar) );
                    start.parentNode.insertBefore( tmp, start );
                    start.parentNode.insertBefore( tmpText, start );
                    removeFillData(this.document,tmpText);
                    fillData = tmpText;

                    mergSibling(tmp,'previousSibling');
                    mergSibling(start,'nextSibling');
                    nativeRange.moveStart( 'character', -1 );
                    nativeRange.collapse( true );

                }
            }

            this.moveToBookmark( bookmark );
            tmp && domUtils.remove( tmp );
            nativeRange.select();
            return this;

        } : function( notInsertFillData ) {

            var win = domUtils.getWindow( this.document ),
                sel = win.getSelection(),
                txtNode;
           
            browser.gecko ?  this.document.body.focus() : win.focus();

            if ( sel ) {
                sel.removeAllRanges();
                if ( this.collapsed && !notInsertFillData  ){



                    txtNode =  this.document.createTextNode( fillChar );
                    this.insertNode( txtNode );
                    removeFillData(this.document,txtNode);
                    mergSibling(txtNode,'previousSibling');
                    mergSibling(txtNode,'nextSibling');
                    fillData = txtNode;
                    this.setStart( txtNode, browser.webkit ? 1 : 0 ).collapse( true );

                }
                var nativeRange = this.document.createRange();
                nativeRange.setStart( this.startContainer, this.startOffset );
                nativeRange.setEnd( this.endContainer, this.endOffset );

                sel.addRange( nativeRange );

            }
            return this;
        },
        
        scrollToView : function(win,offset){

            win = win ? window : domUtils.getWindow(this.document);

            var span = this.document.createElement('span');
            span.innerHTML = '&nbsp;';
            var tmpRange = this.cloneRange();
            tmpRange.insertNode(span);
            domUtils.scrollToView(span,win,offset);

            domUtils.remove(span);
            return this;
        }

    };
})();

(function () {

    function getBoundaryInformation( range, start ) {

        var getIndex = domUtils.getNodeIndex;

        range = range.duplicate();
        range.collapse( start );


        var parent = range.parentElement();
        if ( !parent.hasChildNodes() ) {
            return  {container:parent,offset:0};
        }

        var siblings = parent.children,
            child,
            testRange = range.duplicate(),
            startIndex = 0,endIndex = siblings.length - 1,index = -1,
            distance;

        while ( startIndex <= endIndex ) {
            index = Math.floor( (startIndex + endIndex) / 2 );
            child = siblings[index];
            testRange.moveToElementText( child );
            var position = testRange.compareEndPoints( 'StartToStart', range );


            if ( position > 0 ) {

                endIndex = index - 1;
            } else if ( position < 0 ) {

                startIndex = index + 1;
            } else {
                return  {container:parent,offset:getIndex( child )};
            }
        }

        if ( index == -1 ) {
            testRange.moveToElementText( parent );
            testRange.setEndPoint( 'StartToStart', range );
            distance = testRange.text.replace( /(\r\n|\r)/g, '\n' ).length;
            siblings = parent.childNodes;
            if ( !distance ) {
                child = siblings[siblings.length - 1];
                return  {container:child,offset:child.nodeValue.length};
            }

            var i = siblings.length;
            while ( distance > 0 )
                distance -= siblings[ --i ].nodeValue.length;

            return {container:siblings[i],offset:-distance}
        }

        testRange.collapse( position > 0 );
        testRange.setEndPoint( position > 0 ? 'StartToStart' : 'EndToStart', range );
        distance = testRange.text.replace( /(\r\n|\r)/g, '\n' ).length;
        if ( !distance ) {
            return  dtd.$empty[child.tagName] || dtd.$nonChild[child.tagName]?

            {container : parent,offset:getIndex( child ) + (position > 0 ? 0 : 1)} :
            {container : child,offset: position > 0 ? 0 : child.childNodes.length}
        }

        while ( distance > 0 ) {
            try{
                var pre = child;
                child = child[position > 0 ? 'previousSibling' : 'nextSibling'];
                distance -= child.nodeValue.length;
            }catch(e){
                return {container:parent,offset:getIndex(pre)};
            }

        }
        return  {container:child,offset:position > 0 ? -distance : child.nodeValue.length + distance}
    }

    
    function transformIERangeToRange( ieRange, range ) {
        if ( ieRange.item ) {
            range.selectNode( ieRange.item( 0 ) );
        } else {
            var bi = getBoundaryInformation( ieRange, true );
            range.setStart( bi.container, bi.offset );
            if ( ieRange.compareEndPoints( 'StartToEnd',ieRange ) != 0 ) {
                bi = getBoundaryInformation( ieRange, false );
                range.setEnd( bi.container, bi.offset );
            }
        }
        return range;
    }

    
    function _getIERange(sel){
        var ieRange;
        try{
            ieRange = sel.getNative().createRange();
        }catch(e){
            return null;
        }
        var el = ieRange.item ? ieRange.item( 0 ) : ieRange.parentElement();
        if ( ( el.ownerDocument || el ) === sel.document ) {
            return ieRange;
        }
        return null;
    }
    var Selection = dom.Selection = function ( doc ) {
        var me = this, iframe;
        me.document = doc;

        if ( ie ) {
            iframe = domUtils.getWindow(doc).frameElement;
            domUtils.on( iframe, 'beforedeactivate', function () {
                me._bakIERange = me.getIERange();
            } );
            domUtils.on( iframe, 'activate', function () {
                try {
                    if ( !_getIERange(me) && me._bakIERange ) {
                        me._bakIERange.select();
                    }
                } catch ( ex ) {
                }
                me._bakIERange = null;
            } );
        }
        iframe = doc = null;
    };

    Selection.prototype = {
        
        getNative : function () {
            var doc = this.document;
            try{
                return !doc ? null : ie ? doc.selection : domUtils.getWindow( doc ).getSelection();
            }catch(e){
                return null;
            }
        },



        
        getIERange : function () {

            var ieRange = _getIERange(this);
            if ( !ieRange ) {
                if ( this._bakIERange ) {
                    return this._bakIERange;
                }
            }
            return ieRange;
        },

        
        cache : function () {
            this.clear();
            this._cachedRange = this.getRange();
            this._cachedStartElement = this.getStart();

            this._cachedStartElementPath = this.getStartElementPath();
        },

        getStartElementPath : function(){
            if(this._cachedStartElementPath){
                return this._cachedStartElementPath;
            }
            var start = this.getStart();
            if(start){
                return domUtils.findParents(start,true,null,true)
            }
            return [];
        },
        
        clear : function () {
            this._cachedStartElementPath = this._cachedRange = this._cachedStartElement = null;
        },
        
        isFocus : function(){
            try{
                return browser.ie  && _getIERange(this) || !browser.ie &&  this.getNative().rangeCount ? true : false;
            }catch(e){
                return false;
            }

        },
        
        getRange : function () {
            var me = this;
            
            function optimze(range){
                var child = me.document.body.firstChild,
                    collapsed = range.collapsed;
                while(child && child.firstChild){
                    range.setStart(child,0);
                    child = child.firstChild;
                }
                if(!range.startContainer){
                    range.setStart(me.document.body,0)
                }
                if(collapsed){
                    range.collapse(true);
                }
            }
            if ( me._cachedRange != null ) {
                return this._cachedRange;
            }
            var range = new baidu.editor.dom.Range( me.document );

            if ( ie ) {
                var nativeRange = me.getIERange();
                if(nativeRange){
                    transformIERangeToRange( nativeRange, range );
                }else{
                    optimze(range)
                }

            } else {
                var sel = me.getNative();
                if ( sel && sel.rangeCount ) {
                    var firstRange = sel.getRangeAt( 0 ) ;
                    var lastRange = sel.getRangeAt( sel.rangeCount - 1 );
                    range.setStart( firstRange.startContainer, firstRange.startOffset ).setEnd( lastRange.endContainer, lastRange.endOffset );
                    if(range.collapsed && domUtils.isBody(range.startContainer) && !range.startOffset){
                        optimze(range)
                    }
                } else {
                    if(this._bakRange && domUtils.inDoc(this._bakRange.startContainer,this.document))
                        return this._bakRange;
                    optimze(range)
                        
                }
                
            }

            return this._bakRange = range;
        },

        
        getStart : function () {
            if ( this._cachedStartElement ) {
                return this._cachedStartElement;
            }
            var range = ie ? this.getIERange()  : this.getRange(),
                tmpRange,
                start,tmp,parent;

            if (ie) {
                if(!range){
                   return this.document.body.firstChild;
                }
                if (range.item)
                    return range.item(0);

                tmpRange = range.duplicate();
                tmpRange.text.length > 0 && tmpRange.moveStart('character',1);
                tmpRange.collapse(1);
                start = tmpRange.parentElement();


                parent = tmp = range.parentElement();
                while (tmp = tmp.parentNode) {
                    if (tmp == start) {
                        start = parent;
                        break;
                    }
                }

            } else {
                range.shrinkBoundary();
                start = range.startContainer;

                if (start.nodeType == 1 && start.hasChildNodes())
                    start = start.childNodes[Math.min(start.childNodes.length - 1, range.startOffset)];

                if (start.nodeType == 3)
                    return start.parentNode;


            }
            return start;

        },
        
        getText : function(){
            var nativeSel,nativeRange;
            if(this.isFocus() && (nativeSel = this.getNative())){
                nativeRange = browser.ie ? nativeSel.createRange() : nativeSel.getRangeAt(0);
                return browser.ie ? nativeRange.text : nativeRange.toString();
            }
            return '';
        }
    };


})();
(function () {
    var uid = 0,
        _selectionChangeTimer;

    function replaceSrc(div){
         var imgs = div.getElementsByTagName("img"),
             orgSrc;
         for(var i=0,img;img = imgs[i++];){
             if(orgSrc = img.getAttribute("orgSrc")){
                 img.src = orgSrc;
                 img.removeAttribute("orgSrc");
             }
         }
         var as = div.getElementsByTagName("a");
         for(var i=0,ai;ai=as[i++];i++){
            if(ai.getAttribute('data_ue_src')){
                ai.setAttribute('href',ai.getAttribute('data_ue_src'))
               
            }
         }

    }
    function setValue(form,editor){
        var textarea;
        if(editor.textarea){
            if(utils.isString(editor.textarea)){
                for(var i= 0,ti,tis=domUtils.getElementsByTagName(form,'textarea');ti=tis[i++];){
                    if(ti.id == 'ueditor_textarea_' + editor.options.textarea){
                        textarea = ti;
                        break;
                    }

                }
            }else{
                textarea = editor.textarea;
            }
        }
        if(!textarea){
            form.appendChild(textarea = domUtils.creElm(document,'textarea',{
                'name' : editor.options.textarea,
                'id' : 'ueditor_textarea_' + editor.options.textarea,
                'style' : "display:none"
            }));
        }
        textarea.value = editor.getContent()
    }
    
    var Editor = UE.Editor = function( options ) {
        var me = this;
        me.uid = uid ++;
        EventBase.call( me );
        me.commands = {};
        me.options = utils.extend( options || {},
            UEDITOR_CONFIG, true );
        me.setOpt({
            isShow : true,
            initialContent:'欢迎使用ueditor!',
            autoClearinitialContent:false,
            iframeCssUrl: me.options.UEDITOR_HOME_URL + '/themes/default/iframe.css',
            textarea:'editorValue',
            focus:false,
            minFrameHeight:320,
            autoClearEmptyNode : true,
            fullscreen : false,
            readonly : false,
            zIndex : 999,
            imagePopup:true,
            enterTag:'p',
            pageBreakTag : '_baidu_page_break_tag_'
        });
        for ( var pi in UE.plugins ) {
            UE.plugins[pi].call( me )
        }
    };
    Editor.prototype = {

        setOpt : function(key,val){
            var obj = {};
            if(utils.isString(key)){
                obj[key] = val
            }else{
                obj = key;
            }
            utils.extend(this.options,obj,true);
        },
        destroy : function(){
            var me = this;
            me.fireEvent('destroy');
            me.container.innerHTML = '';
            domUtils.remove(me.container);
            for(var p in me){
                delete this[p]
            }

        },
        
        render : function ( container ) {
            var me = this;
            if (container.constructor === String) {
                container = document.getElementById(container);
            }
            if(container){
                if(browser.ie){
                    var ifr = document.createElement('iframe');
                    ifr.width = '100%';
                    ifr.height = '100%';
                    ifr.id = 'baidu_editor_' + this.uid;
                    ifr.setAttribute('frameBorder','0',0);
                    container.appendChild(ifr);
                    container.style.overflow = 'hidden';
                    setTimeout(function(){
                         me._setup( ifr.contentWindow.document );
                    },0)

                    ifr.src = "javascript:void((function(){document.open();document.domain='"+ location.href.split("/")[2].substr(location.href.split("/")[2].indexOf('.') + 1) +"';document.close();})())";
                }else{
                    container.innerHTML = '<iframe id="' + 'baidu_editor_' + this.uid + '"' + 'width="100%" height="100%"  frameborder="0"></iframe>';
                    container.style.overflow = 'hidden';
                    this._setup( container.firstChild.contentWindow.document );
                }
    
            }

        },

        _setup: function ( doc ) {
            var me = this, options = me.options;
            !browser.webkit && doc.open();
            doc.domain = location.href.split("/")[2].substr(location.href.split("/")[2].indexOf('.') + 1);
            var useBodyAsViewport = ie && browser.version < 9;
            doc.write( ( ie && browser.version < 9 ? '' : '<!DOCTYPE html>') +
                '<html xmlns="http://www.w3.org/1999/xhtml"' + (!useBodyAsViewport ? ' class="view"' : '')  + '><head>' +
                ( options.iframeCssUrl ? '<link rel="stylesheet" type="text/css" href="' + utils.unhtml( options.iframeCssUrl ) + '"/>' : '' ) +
                '<style id="editorinitialstyle" type="text/css">' +
                '.selectTdClass{background-color:#3399FF !important;}' +
                'table.noBorderTable td{border:1px dashed #ddd !important}'+
                'table{clear:both;margin-bottom:10px;border-collapse:collapse;word-break:break-all;}' +
                '.pagebreak{display:block;clear:both !important;cursor:default !important;width: 100% !important;margin:0;}' +
                '.anchorclass{background: url("' + me.options.UEDITOR_HOME_URL + 'themes/default/images/anchor.gif") no-repeat scroll left center transparent;border: 1px dotted #0000FF;cursor: auto;display: inline-block;height: 16px;width: 15px;}' +
                '.view{padding:0;word-wrap:break-word;cursor:text;height:100%;}\n' +
                'body{margin:8px;color:#666;font:12px/18px Tahoma,Verdana,Arial,sans-serif;}' +
                'li{clear:both}' +
                'p{margin:5px 0;}'
                + ( options.initialStyle ||' ' ) +
                '</style></head><body' + (useBodyAsViewport ? ' class="view"' : '')  + '></body></html>' );
            !browser.webkit && doc.close();

            if ( ie ) {
                doc.body.disabled = true;
                doc.body.contentEditable = true;
                doc.body.disabled = false;
            } else {
                doc.body.contentEditable = true;
                doc.body.spellcheck = false;
            }


            me.document = doc;
            me.window = doc.defaultView || doc.parentWindow;

            me.iframe = me.window.frameElement;
            me.body = doc.body;
            me.setHeight(options.minFrameHeight);

            me.selection = new dom.Selection( doc );
            if(browser.gecko){
                this.selection.getNative().removeAllRanges();
            }
            this._initEvents();
            if(options.initialContent){
                if(options.autoClearinitialContent){
                    var oldExecCommand = me.execCommand;
                    me.execCommand = function(){
                        me.fireEvent('firstBeforeExecCommand');
                        oldExecCommand.apply(me,arguments)
                    };
                    this.setDefaultContent(options.initialContent);
                }else
                    this.setContent(options.initialContent,true);
            }
            for(var form = this.iframe.parentNode;!domUtils.isBody(form);form = form.parentNode){

                if(form.tagName == 'FORM'){
                    domUtils.on(form,'submit',function(){
                        setValue(this,me)
                    });
                    break;
                }
            }
            if(domUtils.isEmptyNode(me.body)){
                me.body.innerHTML = '<p>'+(browser.ie?'':'<br/>')+'</p>';
            }
            if(options.focus){
                setTimeout(function(){
                    me.focus();
                    !me.options.autoClearinitialContent &&  me._selectionChange()
                });


            }

            if(!me.container){
                me.container = this.iframe.parentNode;
            }

            if(options.fullscreen && me.ui){
                me.ui.setFullScreen(true)
            }
            me.fireEvent( 'ready' );
            if(!browser.ie){
                domUtils.on(me.window,'blur',function(){
                    me._bakRange = me.selection.getRange();
                    me.selection.getNative().removeAllRanges();
                });
            }
            if(browser.gecko && browser.version <= 10902){
               me.body.contentEditable = false;
               setTimeout(function(){
                   me.body.contentEditable = true;
               },100);
                setInterval(function(){
                    me.body.style.height = me.iframe.offsetHeight - 20 + 'px'
                },100)
            }

            !options.isShow && me.setHide();

            options.readonly && me.setDisabled();

        },
        

        sync : function(formId){
            var me = this,
                form = formId ? document.getElementById(formId) :
                    domUtils.findParent(me.iframe.parentNode,function(node){return node.tagName == 'FORM'},true);
            form && setValue(form,me);
        },
        
        setHeight: function (height){
            if (height !== parseInt(this.iframe.parentNode.style.height)){
                this.iframe.parentNode.style.height = height  +  'px';

            }
            this.document.body.style.height = height - 20 + 'px'
        },

        
        getContent : function (cmd,fn) {
            if( cmd && utils.isFunction(cmd)){
                fn = cmd;
                cmd = '';
            }
            if(fn ? !fn():!this.hasContents())
                return '';

            this.fireEvent( 'beforegetcontent',cmd );
            var reg = new RegExp( domUtils.fillChar, 'g' ),
                html = this.body.innerHTML.replace(reg,'').replace(/>[\t\r\n]*?</g,'><');
            this.fireEvent( 'aftergetcontent',cmd );
            if (this.serialize) {
                var node = this.serialize.parseHTML(html);
                node = this.serialize.transformOutput(node);
                html = this.serialize.toHTML(node);
            }
            return html.replace(/(&nbsp;)+/g,function(s){
                for(var i= 0,str = [],l= s.split(';').length-1;i<l;i++){
                    str.push(i%2 == 0?' ':'&nbsp;')
                }
                return str.join('');
            })
        },

        
        getPlainTxt : function(){
            var reg = new RegExp( domUtils.fillChar,'g' ),
                html = this.body.innerHTML.replace(/[\n\r]/g,'');//ie要先去了\n在处理
            html = html.replace(/<(p|div)[^>]*>(<br\/?>|&nbsp;)<\/\1>/gi,'\n')
                       .replace(/<br\/?>/gi,'\n')
                       .replace(/<[^>/]+>/g,'')
                       .replace(/(\n)?<\/([^>]+)>/g,function(a,b,c){
                            return dtd.$block[c] ? '\n' : b ? b : '';
                        });
            return html.replace(reg,'').replace(/\u00a0/g,' ').replace(/&nbsp;/g,' ')
        },

        
        getContentTxt : function(){
            var reg = new RegExp( domUtils.fillChar,'g' );
            return this.body[browser.ie ? 'innerText':'textContent'].replace(reg,'').replace(/\u00a0/g,' ')
        },

        
        setContent : function ( html,notFireSelectionchange) {
            var me = this,
                inline = utils.extend({a:1,A:1},dtd.$inline,true),
                lastTagName;

            html = html
                .replace(/^[ \t\r\n]*?</,'<')
                .replace(/>[ \t\r\n]*?$/,'>')
                .replace(/>[\t\r\n]*?</g,'><')//代码高量的\n不能去除
                .replace(/[\s\/]?(\w+)?>[ \t\r\n]*?<\/?(\w+)/gi,function(a,b,c){
                    if(b){
                        lastTagName = c;
                    }else{
                        b = lastTagName
                    }
                    return !inline[b] && !inline[c] ? a.replace(/>[ \t\r\n]*?</,'><') : a;
                });
            me.fireEvent( 'beforesetcontent' );
            var serialize = this.serialize;
            if (serialize) {
                var node = serialize.parseHTML(html);
                node = serialize.transformInput(node);
                node = serialize.filter(node);
                html = serialize.toHTML(node);
            }
            this.body.innerHTML = html.replace(new RegExp('[\r' + domUtils.fillChar + ']*','g'),'');
            if(browser.ie && browser.version < 7 ){
                replaceSrc(this.document.body);
            }
            if(me.options.enterTag == 'p'){
                var child = this.body.firstChild,tmpNode;
                if(!child || child.nodeType == 1 &&
                    (dtd.$cdata[child.tagName] ||
                          domUtils.isCustomeNode(child)
                    )
                    && child === this.body.lastChild){
                    this.body.innerHTML = '<p>'+(browser.ie ? '' :'<br/>')+'</p>' + this.body.innerHTML;
                }else{
                    var p = me.document.createElement('p');
                     while(child){
                        while(child && (child.nodeType ==3 || child.nodeType == 1 && dtd.p[child.tagName] && !dtd.$cdata[child.tagName])){
                            tmpNode = child.nextSibling;
                            p.appendChild(child);
                            child = tmpNode;
                        }
                        if(p.firstChild){
                            if(!child){
                                me.body.appendChild(p);
                                break;
                            }else{
                                me.body.insertBefore(p,child);
                                p = me.document.createElement('p');
                            }
                        }
                        child = child.nextSibling;

                    }

                }


            }

            me.adjustTable && me.adjustTable(me.body);
            me.fireEvent( 'aftersetcontent' );
            me.fireEvent( 'contentchange' );
            !notFireSelectionchange && me._selectionChange();
            me._bakRange = me._bakIERange = null;
            if(browser.gecko){
                me.selection.getNative().removeAllRanges();
            }
        },

        
        focus : function () {
            try{
                this.selection.getRange().select(true);
            }catch(e){}

        },

         
        _initEvents : function () {
            var me = this,
                doc = me.document,
                win = me.window;
            me._proxyDomEvent = utils.bind( me._proxyDomEvent, me );
            domUtils.on( doc, ['click',  'contextmenu','mousedown','keydown', 'keyup','keypress', 'mouseup', 'mouseover', 'mouseout', 'selectstart'], me._proxyDomEvent );

            domUtils.on( win, ['focus', 'blur'], me._proxyDomEvent );

            domUtils.on( doc, ['mouseup','keydown'], function(evt){
                if(evt.type == 'keydown' && (evt.ctrlKey || evt.metaKey || evt.shiftKey || evt.altKey)){
                    return;
                }
                if(evt.button == 2)return;
                me._selectionChange(250, evt );
            });
            var innerDrag = 0,source = browser.ie ? me.body : me.document,dragoverHandler;

            domUtils.on(source,'dragstart',function(){
                innerDrag = 1;
            });

            domUtils.on(source,browser.webkit ? 'dragover' : 'drop',function(){
                return browser.webkit ?
                    function(){
                        clearTimeout( dragoverHandler );
                        dragoverHandler = setTimeout( function(){
                            if(!innerDrag){
                                var sel = me.selection,
                                    range = sel.getRange();
                                if(range){
                                    var common = range.getCommonAncestor();
                                    if(common && me.serialize){
                                        var f = me.serialize,
                                            node =
                                                f.filter(
                                                    f.transformInput(
                                                        f.parseHTML(
                                                            f.word(common.innerHTML)
                                                        )
                                                    )
                                                );
                                        common.innerHTML = f.toHTML(node)
                                    }

                                }
                            }
                            innerDrag = 0;
                        }, 200 );
                    } :
                    function(e){

                        if(!innerDrag){
                            e.preventDefault ? e.preventDefault() :(e.returnValue = false) ;

                        }
                        innerDrag = 0;
                    }

            }());

        },
        _proxyDomEvent: function ( evt ) {

            return this.fireEvent( evt.type.replace( /^on/, '' ), evt );
        },

        _selectionChange : function ( delay, evt ) {

            var me = this;
            if(!me.selection.isFocus())
                return;

            var hackForMouseUp = false;
            var mouseX, mouseY;
            if (browser.ie && browser.version < 9 && evt && evt.type == 'mouseup') {
                var range = this.selection.getRange();
                if (!range.collapsed) {
                    hackForMouseUp = true;
                    mouseX = evt.clientX;
                    mouseY = evt.clientY;
                }
            }
            clearTimeout(_selectionChangeTimer);
            _selectionChangeTimer = setTimeout(function(){
                if(!me.selection.getNative()){
                    return;
                }
                var ieRange;
                if (hackForMouseUp && me.selection.getNative().type == 'None' ) {
                    ieRange = me.document.body.createTextRange();
                    try {
                        ieRange.moveToPoint( mouseX, mouseY );
                    } catch(ex){
                        ieRange = null;
                    }
                }
                var bakGetIERange;
                if (ieRange) {
                    bakGetIERange = me.selection.getIERange;
                    me.selection.getIERange = function (){
                        return ieRange;
                    };
                }
                me.selection.cache();
                if (bakGetIERange) {
                    me.selection.getIERange = bakGetIERange;
                }
                if ( me.selection._cachedRange && me.selection._cachedStartElement ) {
                    me.fireEvent( 'beforeselectionchange' );
                    me.fireEvent( 'selectionchange', !!evt );
                    me.fireEvent('afterselectionchange');
                    me.selection.clear();
                }
            }, delay || 50);

        },

        _callCmdFn: function ( fnName, args ) {
            var cmdName = args[0].toLowerCase(),
                cmd, cmdFn;
            cmd =  this.commands[cmdName] ||  UE.commands[cmdName];
            cmdFn = cmd && cmd[fnName];
            if ( (!cmd || !cmdFn) && fnName == 'queryCommandState' ) {
                return 0;
            } else if ( cmdFn ) {
                return cmdFn.apply( this, args );
            }
        },

    
        execCommand : function ( cmdName ) {
            cmdName = cmdName.toLowerCase();
            var me = this,
                result,
                cmd = me.commands[cmdName] || UE.commands[cmdName];
            if ( !cmd || !cmd.execCommand ) {
                return;
            }

            if ( !cmd.notNeedUndo && !me.__hasEnterExecCommand ) {
                me.__hasEnterExecCommand = true;
                if(me.queryCommandState(cmdName) !=-1){
                    me.fireEvent( 'beforeexeccommand', cmdName );
                    result = this._callCmdFn( 'execCommand', arguments );
                    me.fireEvent( 'afterexeccommand', cmdName );
                }

                me.__hasEnterExecCommand = false;
            } else {
                result = this._callCmdFn( 'execCommand', arguments );
            }
            me._selectionChange();
            return result;
        },

        
        queryCommandState : function ( cmdName ) {
            return this._callCmdFn( 'queryCommandState', arguments );
        },

        
        queryCommandValue : function ( cmdName ) {
            return this._callCmdFn( 'queryCommandValue', arguments );
        },
        
        hasContents : function(tags){
            if(tags){
               for(var i=0,ci;ci=tags[i++];){
                    if(this.document.getElementsByTagName(ci).length > 0)
                        return true;
               }
            }
            if(!domUtils.isEmptyBlock(this.body)){
                return true
            }
            tags = ['div'];
            for(i= 0;ci=tags[i++];){
                var nodes = domUtils.getElementsByTagName(this.document,ci);
                for(var n= 0,cn;cn=nodes[n++];){
                    if(domUtils.isCustomeNode(cn)){
                        return true;
                    }
                }
            }
            return false;
        },
        
        reset : function(){
            this.fireEvent('reset');
        },
        
        setEnabled : function(){
            var me = this,range;
            if(me.body.contentEditable == 'false'){
                me.body.contentEditable = true;
                range = me.selection.getRange();
                try{
                    range.moveToBookmark(me.lastBk);
                    delete me.lastBk
                }catch(e){
                    range.setStartAtFirst(me.body).collapse(true)
                }
                range.select(true);
                if(me.bkqueryCommandState){
                    me.queryCommandState = me.bkqueryCommandState;
                    delete me.bkqueryCommandState;
                }

                me.fireEvent( 'selectionchange');
            }


        },
        
        setDisabled : function(exclude){
            var me = this;
            exclude = exclude ? utils.isArray(exclude) ? exclude : [exclude] : [];
            if(me.body.contentEditable == 'true'){
                if(!me.lastBk){
                    me.lastBk = me.selection.getRange().createBookmark(true);
                }
                me.body.contentEditable = false;
                me.bkqueryCommandState = me.queryCommandState;
                me.queryCommandState =function(type){
                    if(utils.indexOf(exclude,type)!=-1){
                        return me.bkqueryCommandState.apply(me,arguments)
                    }

                    return -1;
                };
                me.fireEvent( 'selectionchange');
            }



        },
        
        setDefaultContent : function(){
             function clear(){
                var me = this;
                if(me.document.getElementById('initContent')){
                    me.document.body.innerHTML = '<p>'+(ie ? '' : '<br/>')+'</p>';
                    var range = me.selection.getRange();

                    me.removeListener('firstBeforeExecCommand',clear);
                    me.removeListener('focus',clear);
                
                    setTimeout(function(){
                        range.setStart(me.document.body.firstChild,0).collapse(true).select(true);
                        me._selectionChange();
                    })


                }
            }
            return function (cont){
                var me = this;
                me.document.body.innerHTML = '<p id="initContent">'+cont+'</p>';
                if(browser.ie && browser.version < 7){
                    replaceSrc(me.document.body);
                }
                me.addListener('firstBeforeExecCommand',clear);
                me.addListener('focus',clear);
            }


        }(),
        
        setShow : function(){
            var me = this,
                range = me.selection.getRange();
            if(me.container.style.display == 'none'){
                try{
                    range.moveToBookmark(me.lastBk);
                    delete me.lastBk
                }catch(e){
                    range.setStartAtFirst(me.body).collapse(true)
                }
                range.select(true);
                me.container.style.display  = '';
            }

        },
        
        setHide : function(){
            var me = this;
            if(!me.lastBk){
                me.lastBk = me.selection.getRange().createBookmark(true);
            }
            me.container.style.display = 'none'
        }

    };
    utils.inherits( Editor, EventBase );
})();


UE.ajax = function() {
	return {
		
		request:function(url, ajaxOptions) {
            var ajaxRequest = creatAjaxRequest(),
                timeIsOut = false,
                defaultAjaxOptions = {
                    method:"POST",
                    timeout:5000,
                    async:true,
                    data:{},//需要传递对象的话只能覆盖
                    onsuccess:function() {
                    },
                    onerror:function() {
                    }
                };

			if (typeof url === "object") {
				ajaxOptions = url;
				url = ajaxOptions.url;
			}
			if (!ajaxRequest || !url) return;
			var ajaxOpts = ajaxOptions ? utils.extend(defaultAjaxOptions,ajaxOptions) : defaultAjaxOptions;

			var submitStr = json2str(ajaxOpts);
			if (!utils.isEmptyObject(ajaxOpts.data)){
                submitStr += (submitStr? "&":"") + json2str(ajaxOpts.data);
			}
            var timerID = setTimeout(function() {
                if (ajaxRequest.readyState != 4) {
                    timeIsOut = true;
                    ajaxRequest.abort();
                    clearTimeout(timerID);
                }
            }, ajaxOpts.timeout);

			var method = ajaxOpts.method.toUpperCase();
            var str = url + (url.indexOf("?")==-1?"?":"&") + (method=="POST"?"":submitStr+ "&noCache=" + +new Date);
			ajaxRequest.open(method, str, ajaxOpts.async);
			ajaxRequest.onreadystatechange = function() {
				if (ajaxRequest.readyState == 4) {
					if (!timeIsOut && ajaxRequest.status == 200) {
						ajaxOpts.onsuccess(ajaxRequest);
					} else {
						ajaxOpts.onerror(ajaxRequest);
					}
				}
			};
			if (method == "POST") {
				ajaxRequest.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
				ajaxRequest.send(submitStr);
			} else {
				ajaxRequest.send(null);
			}
		}
	};

	
	function json2str(json) {
		var strArr = [];
		for (var i in json) {
			if(i=="method" || i=="timeout" || i=="async") continue;
			if (!((typeof json[i]).toLowerCase() == "function" || (typeof json[i]).toLowerCase() == "object")) {
				strArr.push( encodeURIComponent(i) + "="+encodeURIComponent(json[i]) );
			}
		}
		return strArr.join("&");

	}

	
	function creatAjaxRequest() {
		var xmlHttp = null;
		if (window.XMLHttpRequest) {
			xmlHttp = new XMLHttpRequest();
		} else {
			try {
				xmlHttp = new ActiveXObject("Msxml2.XMLHTTP");
			} catch (e) {
				try {
					xmlHttp = new ActiveXObject("Microsoft.XMLHTTP");
				} catch (e) {
				}
			}
		}
		return xmlHttp;
	}
}();
var baidu = baidu || {};
baidu.editor = baidu.editor || {};
baidu.editor.ui = {};(function (){
    var browser = baidu.editor.browser,
        domUtils = baidu.editor.dom.domUtils;

    var magic = '$EDITORUI';
    var root = window[magic] = {};
    var uidMagic = 'ID' + magic;
    var uidCount = 0;

    var uiUtils = baidu.editor.ui.uiUtils = {
        uid: function (obj){
            return (obj ? obj[uidMagic] || (obj[uidMagic] = ++ uidCount) : ++ uidCount);
        },
        hook: function ( fn, callback ) {
            var dg;
            if (fn && fn._callbacks) {
                dg = fn;
            } else {
                dg = function (){
                    var q;
                    if (fn) {
                        q = fn.apply(this, arguments);
                    }
                    var callbacks = dg._callbacks;
                    var k = callbacks.length;
                    while (k --) {
                        var r = callbacks[k].apply(this, arguments);
                        if (q === undefined) {
                            q = r;
                        }
                    }
                    return q;
                };
                dg._callbacks = [];
            }
            dg._callbacks.push(callback);
            return dg;
        },
        createElementByHtml: function (html){
            var el = document.createElement('div');
            el.innerHTML = html;
            el = el.firstChild;
            el.parentNode.removeChild(el);
            return el;
        },
        getViewportElement: function (){
            return (browser.ie && browser.quirks) ?
                document.body : document.documentElement;
        },
        getClientRect: function (element){
            var bcr;
            try{
                bcr = element.getBoundingClientRect();
            }catch(e){
                bcr={left:0,top:0,height:0,width:0}
            }
            var rect = {
                left: Math.round(bcr.left),
                top: Math.round(bcr.top),
                height: Math.round(bcr.bottom - bcr.top),
                width: Math.round(bcr.right - bcr.left)
            };
            var doc;
            while ((doc = element.ownerDocument) !== document &&
                (element = domUtils.getWindow(doc).frameElement)) {
                bcr = element.getBoundingClientRect();
                rect.left += bcr.left;
                rect.top += bcr.top;
            }
            rect.bottom = rect.top + rect.height;
            rect.right = rect.left + rect.width;
            return rect;
        },
        getViewportRect: function (){
            var viewportEl = uiUtils.getViewportElement();
            var width = (window.innerWidth || viewportEl.clientWidth) | 0;
            var height = (window.innerHeight ||viewportEl.clientHeight) | 0;
            return {
                left: 0,
                top: 0,
                height: height,
                width: width,
                bottom: height,
                right: width
            };
        },
        setViewportOffset: function (element, offset){
            var rect;
            var fixedLayer = uiUtils.getFixedLayer();
            if (element.parentNode === fixedLayer) {
                element.style.left = offset.left + 'px';
                element.style.top = offset.top + 'px';
            } else {
                domUtils.setViewportOffset(element, offset);
            }
        },
        getEventOffset: function (evt){
            var el = evt.target || evt.srcElement;
            var rect = uiUtils.getClientRect(el);
            var offset = uiUtils.getViewportOffsetByEvent(evt);
            return {
                left: offset.left - rect.left,
                top: offset.top - rect.top
            };
        },
        getViewportOffsetByEvent: function (evt){
            var el = evt.target || evt.srcElement;
            var frameEl = domUtils.getWindow(el).frameElement;
            var offset = {
                left: evt.clientX,
                top: evt.clientY
            };
            if (frameEl && el.ownerDocument !== document) {
                var rect = uiUtils.getClientRect(frameEl);
                offset.left += rect.left;
                offset.top += rect.top;
            }
            return offset;
        },
        setGlobal: function (id, obj){
            root[id] = obj;
            return magic + '["' + id  + '"]';
        },
        unsetGlobal: function (id){
            delete root[id];
        },
        copyAttributes: function (tgt, src){
            var attributes = src.attributes;
            var k = attributes.length;
            while (k --) {
                var attrNode = attributes[k];
                if ( attrNode.nodeName != 'style' && attrNode.nodeName != 'class' && (!browser.ie || attrNode.specified) ) {
                    tgt.setAttribute(attrNode.nodeName, attrNode.nodeValue);
                }
            }
            if (src.className) {
                tgt.className += ' ' + src.className;
            }
            if (src.style.cssText) {
                tgt.style.cssText += ';' + src.style.cssText;
            }
        },
        removeStyle: function (el, styleName){
            if (el.style.removeProperty) {
                el.style.removeProperty(styleName);
            } else if (el.style.removeAttribute) {
                el.style.removeAttribute(styleName);
            } else throw '';
        },
        contains: function (elA, elB){
            return elA && elB && (elA === elB ? false : (
                elA.contains ? elA.contains(elB) :
                    elA.compareDocumentPosition(elB) & 16
                ));
        },
        startDrag: function (evt, callbacks,doc){
            var doc = doc || document;
            var startX = evt.clientX;
            var startY = evt.clientY;
            function handleMouseMove(evt){
                var x = evt.clientX - startX;
                var y = evt.clientY - startY;
                callbacks.ondragmove(x, y);
                if (evt.stopPropagation) {
                    evt.stopPropagation();
                } else {
                    evt.cancelBubble = true;
                }
            }
            if (doc.addEventListener) {
                function handleMouseUp(evt){
                    doc.removeEventListener('mousemove', handleMouseMove, true);
                    doc.removeEventListener('mouseup', handleMouseMove, true);
                    window.removeEventListener('mouseup', handleMouseUp, true);
                    callbacks.ondragstop();
                }
                doc.addEventListener('mousemove', handleMouseMove, true);
                doc.addEventListener('mouseup', handleMouseUp, true);
                window.addEventListener('mouseup', handleMouseUp, true);
                evt.preventDefault();
            } else {
                var elm = evt.srcElement;
                elm.setCapture();
                function releaseCaptrue(){
                    elm.releaseCapture();
                    elm.detachEvent('onmousemove', handleMouseMove);
                    elm.detachEvent('onmouseup', releaseCaptrue);
                    elm.detachEvent('onlosecaptrue', releaseCaptrue);
                    callbacks.ondragstop();
                }
                elm.attachEvent('onmousemove', handleMouseMove);
                elm.attachEvent('onmouseup', releaseCaptrue);
                elm.attachEvent('onlosecaptrue', releaseCaptrue);
                evt.returnValue = false;
            }
            callbacks.ondragstart();
        },
        getFixedLayer: function (){
            var layer = document.getElementById('edui_fixedlayer');
            if (layer == null) {
                layer = document.createElement('div');
                layer.id = 'edui_fixedlayer';
                document.body.appendChild(layer);
                if (browser.ie && browser.version <= 8) {
                    layer.style.position = 'absolute';
                    bindFixedLayer();
                    setTimeout(updateFixedOffset);
                } else {
                    layer.style.position = 'fixed';
                }
                layer.style.left = '0';
                layer.style.top = '0';
                layer.style.width = '0';
                layer.style.height = '0';
            }
            return layer;
        },
        makeUnselectable: function (element){
            if (browser.opera || (browser.ie && browser.version < 9)) {
                element.unselectable = 'on';
                if (element.hasChildNodes()) {
                    for (var i=0; i<element.childNodes.length; i++) {
                        if (element.childNodes[i].nodeType == 1) {
                            uiUtils.makeUnselectable(element.childNodes[i]);
                        }
                    }
                }
            } else {
                if (element.style.MozUserSelect !== undefined) {
                    element.style.MozUserSelect = 'none';
                } else if (element.style.WebkitUserSelect !== undefined) {
                    element.style.WebkitUserSelect = 'none';
                } else if (element.style.KhtmlUserSelect !== undefined) {
                    element.style.KhtmlUserSelect = 'none';
                }
            }
        }
    };
    function updateFixedOffset(){
        var layer = document.getElementById('edui_fixedlayer');
        uiUtils.setViewportOffset(layer, {
            left: 0,
            top: 0
        });
    }
    function bindFixedLayer(adjOffset){
        domUtils.on(window, 'scroll', updateFixedOffset);
        domUtils.on(window, 'resize', baidu.editor.utils.defer(updateFixedOffset, 0, true));
    }
})();
(function (){
    var utils = baidu.editor.utils,
        uiUtils = baidu.editor.ui.uiUtils,
        EventBase = baidu.editor.EventBase,
        UIBase = baidu.editor.ui.UIBase = function (){};

    UIBase.prototype = {
        className: '',
        uiName: '',
        initOptions: function (options){
            var me = this;
            for (var k in options) {
                me[k] = options[k];
            }
            this.id = this.id || 'edui' + uiUtils.uid();
        },
        initUIBase: function (){
            this._globalKey = utils.unhtml( uiUtils.setGlobal(this.id, this) );
        },
        render: function (holder){
            var html = this.renderHtml();
            var el = uiUtils.createElementByHtml(html);
            var seatEl = this.getDom();
            if (seatEl != null) {
                seatEl.parentNode.replaceChild(el, seatEl);
                uiUtils.copyAttributes(el, seatEl);
            } else {
                if (typeof holder == 'string') {
                    holder = document.getElementById(holder);
                }
                holder = holder || uiUtils.getFixedLayer();
                holder.appendChild(el);
            }
            this.postRender();
        },
        getDom: function (name){
            if (!name) {
                return document.getElementById( this.id );
            } else {
                return document.getElementById( this.id + '_' + name );
            }
        },
        postRender: function (){
            this.fireEvent('postrender');
        },
        getHtmlTpl: function (){
            return '';
        },
        formatHtml: function (tpl){
            var prefix = 'edui-' + this.uiName;
            return (tpl
                .replace(/##/g, this.id)
                .replace(/%%-/g, this.uiName ? prefix + '-' : '')
                .replace(/%%/g, (this.uiName ? prefix : '') + ' ' + this.className)
                .replace(/\$\$/g, this._globalKey));
        },
        renderHtml: function (){
            return this.formatHtml(this.getHtmlTpl());
        },
        dispose: function (){
            var box = this.getDom();
            if (box) baidu.editor.dom.domUtils.remove( box );
            uiUtils.unsetGlobal(this.id);
        }
    };
    utils.inherits(UIBase, EventBase);
})();
(function (){
    var utils = baidu.editor.utils,
        UIBase = baidu.editor.ui.UIBase,
        Separator = baidu.editor.ui.Separator = function (options){
            this.initOptions(options);
            this.initSeparator();
        };
    Separator.prototype = {
        uiName: 'separator',
        initSeparator: function (){
            this.initUIBase();
        },
        getHtmlTpl: function (){
            return '<div id="##" class="edui-box %%"></div>';
        }
    };
    utils.inherits(Separator, UIBase);

})();

(function (){
    var utils = baidu.editor.utils,
        domUtils = baidu.editor.dom.domUtils,
        UIBase = baidu.editor.ui.UIBase,
        uiUtils = baidu.editor.ui.uiUtils;
    
    var Mask = baidu.editor.ui.Mask = function (options){
        this.initOptions(options);
        this.initUIBase();
    };
    Mask.prototype = {
        getHtmlTpl: function (){
            return '<div id="##" class="edui-mask %%" onmousedown="return $$._onMouseDown(event, this);"></div>';
        },
        postRender: function (){
            var me = this;
            domUtils.on(window, 'resize', function (){
                setTimeout(function (){
                    if (!me.isHidden()) {
                        me._fill();
                    }
                });
            });
        },
        show: function (zIndex){
            this._fill();
            this.getDom().style.display = '';
            this.getDom().style.zIndex = zIndex;
        },
        hide: function (){
            this.getDom().style.display = 'none';
            this.getDom().style.zIndex = '';
        },
        isHidden: function (){
            return this.getDom().style.display == 'none';
        },
        _onMouseDown: function (){
            return false;
        },
        _fill: function (){
            var el = this.getDom();
            var vpRect = uiUtils.getViewportRect();
            el.style.width = vpRect.width + 'px';
            el.style.height = vpRect.height + 'px';
        }
    };
    utils.inherits(Mask, UIBase);
})();

(function () {
    var utils = baidu.editor.utils,
        uiUtils = baidu.editor.ui.uiUtils,
        domUtils = baidu.editor.dom.domUtils,
        UIBase = baidu.editor.ui.UIBase,
        Popup = baidu.editor.ui.Popup = function (options){
            this.initOptions(options);
            this.initPopup();
        };

    var allPopups = [];
    function closeAllPopup( el ){
        var newAll = [];
        for ( var i = 0; i < allPopups.length; i++ ) {
            var pop = allPopups[i];
            if (!pop.isHidden()) {
                if (pop.queryAutoHide(el) !== false) {
                    pop.hide();
                }
            }
        }
    }

    Popup.postHide = closeAllPopup;

    var ANCHOR_CLASSES = ['edui-anchor-topleft','edui-anchor-topright',
        'edui-anchor-bottomleft','edui-anchor-bottomright'];
    Popup.prototype = {
        SHADOW_RADIUS: 5,
        content: null,
        _hidden: false,
        autoRender: true,
        canSideLeft: true,
        canSideUp: true,
        initPopup: function (){
            this.initUIBase();
            allPopups.push( this );
        },
        getHtmlTpl: function (){
            return '<div id="##" class="edui-popup %%">' +
                ' <div id="##_body" class="edui-popup-body">' +
                ' <iframe style="position:absolute;z-index:-1;left:0;top:0;background-color: white;" frameborder="0" width="100%" height="100%" src="javascript:"></iframe>' +
                ' <div class="edui-shadow"></div>' +
                ' <div id="##_content" class="edui-popup-content">' +
                this.getContentHtmlTpl() +
                '  </div>' +
                ' </div>' +
                '</div>';
        },
        getContentHtmlTpl: function (){
            if(this.content){
                if (typeof this.content == 'string') {
                    return this.content;
                }
                return this.content.renderHtml();
            }else{
                return ''
            }

        },
        _UIBase_postRender: UIBase.prototype.postRender,
        postRender: function (){
            if (this.content instanceof UIBase) {
                this.content.postRender();
            }
            this.fireEvent('postRenderAfter');
            this.hide(true);
            this._UIBase_postRender();
        },
        _doAutoRender: function (){
            if (!this.getDom() && this.autoRender) {
                this.render();
            }
        },
        mesureSize: function (){
            var box = this.getDom('content');
            return uiUtils.getClientRect(box);
        },
        fitSize: function (){
            var popBodyEl = this.getDom('body');
            popBodyEl.style.width = '';
            popBodyEl.style.height = '';
            var size = this.mesureSize();
            popBodyEl.style.width = size.width + 'px';
            popBodyEl.style.height = size.height + 'px';
            return size;
        },
        showAnchor: function ( element, hoz ){
            this.showAnchorRect( uiUtils.getClientRect( element ), hoz );
        },
        showAnchorRect: function ( rect, hoz, adj ){
            this._doAutoRender();
            var vpRect = uiUtils.getViewportRect();
            this._show();
            var popSize = this.fitSize();

            var sideLeft, sideUp, left, top;
            if (hoz) {
                sideLeft = this.canSideLeft && (rect.right + popSize.width > vpRect.right && rect.left > popSize.width);
                sideUp = this.canSideUp && (rect.top + popSize.height > vpRect.bottom && rect.bottom > popSize.height);
                left = (sideLeft ? rect.left - popSize.width : rect.right);
                top = (sideUp ? rect.bottom - popSize.height : rect.top);
            } else {
                sideLeft = this.canSideLeft && (rect.right + popSize.width > vpRect.right && rect.left > popSize.width);
                sideUp = this.canSideUp && (rect.top + popSize.height > vpRect.bottom && rect.bottom > popSize.height);
                left = (sideLeft ? rect.right - popSize.width : rect.left);
                top = (sideUp ? rect.top - popSize.height : rect.bottom);
            }

            var popEl = this.getDom();
            uiUtils.setViewportOffset(popEl, {
                left: left,
                top: top
            });
            domUtils.removeClasses(popEl, ANCHOR_CLASSES);
            popEl.className += ' ' + ANCHOR_CLASSES[(sideUp ? 1 : 0) * 2 + (sideLeft ? 1 : 0)];
            if(this.editor){
                popEl.style.zIndex = this.editor.container.style.zIndex * 1 + 10;
                baidu.editor.ui.uiUtils.getFixedLayer().style.zIndex = popEl.style.zIndex - 1;
            }

        },
        showAt: function (offset) {
            var left = offset.left;
            var top = offset.top;
            var rect = {
                left: left,
                top: top,
                right: left,
                bottom: top,
                height: 0,
                width: 0
            };
            this.showAnchorRect(rect, false, true);
        },
        _show: function (){
            if (this._hidden) {
                var box = this.getDom();
                box.style.display = '';
                this._hidden = false;
                this.fireEvent('show');
            }
        },
        isHidden: function (){
            return this._hidden;
        },
        show: function (){
            this._doAutoRender();
            this._show();
        },
        hide: function (notNofity){
            if (!this._hidden && this.getDom()) {
                this.getDom().style.display = 'none';
                this._hidden = true;
                if (!notNofity) {
                    this.fireEvent('hide');
                }
            }
        },
        queryAutoHide: function (el){
            return !el || !uiUtils.contains(this.getDom(), el);
        }
    };
    utils.inherits(Popup, UIBase);
    
    domUtils.on( document, 'mousedown', function ( evt ) {
        var el = evt.target || evt.srcElement;
        closeAllPopup( el );
    } );
    domUtils.on( window, 'scroll', function () {
        closeAllPopup();
    } );

})();

(function (){
    var utils = baidu.editor.utils,
        UIBase = baidu.editor.ui.UIBase,
        ColorPicker = baidu.editor.ui.ColorPicker = function (options){
            this.initOptions(options);
            this.noColorText = this.noColorText || '不设置颜色';
            this.initUIBase();
        };

    ColorPicker.prototype = {
        getHtmlTpl: function (){
            return genColorPicker(
                this.noColorText
                );
        },
        _onTableClick: function (evt){
            var tgt = evt.target || evt.srcElement;
            var color = tgt.getAttribute('data-color');
            if (color) {
                this.fireEvent('pickcolor', color);
            }
        },
        _onTableOver: function (evt){
            var tgt = evt.target || evt.srcElement;
            var color = tgt.getAttribute('data-color');
            if (color) {
                this.getDom('preview').style.backgroundColor = color;
            }
        },
        _onTableOut: function (){
            this.getDom('preview').style.backgroundColor = '';
        },
        _onPickNoColor: function (){
            this.fireEvent('picknocolor');
        }
    };
    utils.inherits(ColorPicker, UIBase);

    var COLORS = (
            'ffffff,000000,eeece1,1f497d,4f81bd,c0504d,9bbb59,8064a2,4bacc6,f79646,' +
            'f2f2f2,7f7f7f,ddd9c3,c6d9f0,dbe5f1,f2dcdb,ebf1dd,e5e0ec,dbeef3,fdeada,' +
            'd8d8d8,595959,c4bd97,8db3e2,b8cce4,e5b9b7,d7e3bc,ccc1d9,b7dde8,fbd5b5,' +
            'bfbfbf,3f3f3f,938953,548dd4,95b3d7,d99694,c3d69b,b2a2c7,92cddc,fac08f,' +
            'a5a5a5,262626,494429,17365d,366092,953734,76923c,5f497a,31859b,e36c09,' +
            '7f7f7f,0c0c0c,1d1b10,0f243e,244061,632423,4f6128,3f3151,205867,974806,' +
            'c00000,ff0000,ffc000,ffff00,92d050,00b050,00b0f0,0070c0,002060,7030a0,').split(',');

    function genColorPicker(noColorText){
        var html = '<div id="##" class="edui-colorpicker %%">' +
            '<div class="edui-colorpicker-topbar edui-clearfix">' +
             '<div unselectable="on" id="##_preview" class="edui-colorpicker-preview"></div>' +
             '<div unselectable="on" class="edui-colorpicker-nocolor" onclick="$$._onPickNoColor(event, this);">'+ noColorText +'</div>' +
            '</div>' +
            '<table  class="edui-box" style="border-collapse: collapse;" onmouseover="$$._onTableOver(event, this);" onmouseout="$$._onTableOut(event, this);" onclick="return $$._onTableClick(event, this);" cellspacing="0" cellpadding="0">' +
            '<tr style="border-bottom: 1px solid #ddd;font-size: 13px;line-height: 25px;color:#366092;padding-top: 2px"><td colspan="10">主题颜色</td> </tr>'+
            '<tr class="edui-colorpicker-tablefirstrow" >';
        for (var i=0; i<COLORS.length; i++) {
            if (i && i%10 === 0) {
                html += '</tr>'+(i==60?'<tr style="border-bottom: 1px solid #ddd;font-size: 13px;line-height: 25px;color:#366092;"><td colspan="10">标准颜色</td></tr>':'')+'<tr'+(i==60?' class="edui-colorpicker-tablefirstrow"':'')+'>';
            }
            html += i<70 ? '<td style="padding: 0 2px;"><a hidefocus title="'+COLORS[i]+'" onclick="return false;" href="javascript:" unselectable="on" class="edui-box edui-colorpicker-colorcell"' +
                        ' data-color="#'+ COLORS[i] +'"'+
                        ' style="background-color:#'+ COLORS[i] +';border:solid #ccc;'+
                        (i<10 || i>=60?'border-width:1px;':
                         i>=10&&i<20?'border-width:1px 1px 0 1px;':

                        'border-width:0 1px 0 1px;')+
                        '"' +
                    '></a></td>':'';
        }
        html += '</tr></table></div>';
        return html;
    }
})();

(function (){
    var utils = baidu.editor.utils,
        uiUtils = baidu.editor.ui.uiUtils,
        UIBase = baidu.editor.ui.UIBase;
    
    var TablePicker = baidu.editor.ui.TablePicker = function (options){
        this.initOptions(options);
        this.initTablePicker();
    };
    TablePicker.prototype = {
        defaultNumRows: 10,
        defaultNumCols: 10,
        maxNumRows: 20,
        maxNumCols: 20,
        numRows: 10,
        numCols: 10,
        lengthOfCellSide: 22,
        initTablePicker: function (){
            this.initUIBase();
        },
        getHtmlTpl: function (){
            return '<div id="##" class="edui-tablepicker %%">' +
                 '<div class="edui-tablepicker-body">' +
                  '<div class="edui-infoarea">' +
                   '<span id="##_label" class="edui-label"></span>' +
                   '<span class="edui-clickable" onclick="$$._onMore();">更多</span>' +
                  '</div>' +
                  '<div class="edui-pickarea"' +
                   ' onmousemove="$$._onMouseMove(event, this);"' +
                   ' onmouseover="$$._onMouseOver(event, this);"' +
                   ' onmouseout="$$._onMouseOut(event, this);"' +
                   ' onclick="$$._onClick(event, this);"' +
                  '>' +
                    '<div id="##_overlay" class="edui-overlay"></div>' +
                  '</div>' +
                 '</div>' +
                '</div>';
        },
        _UIBase_render: UIBase.prototype.render,
        render: function (holder){
            this._UIBase_render(holder);
            this.getDom('label').innerHTML = '0列 x 0行';
        },
        _track: function (numCols, numRows){
            var style = this.getDom('overlay').style;
            var sideLen = this.lengthOfCellSide;
            style.width = numCols * sideLen + 'px';
            style.height = numRows * sideLen + 'px';
            var label = this.getDom('label');
            label.innerHTML = numCols + '列 x ' + numRows + '行';
            this.numCols = numCols;
            this.numRows = numRows;
        },
        _onMouseOver: function (evt, el){
            var rel = evt.relatedTarget || evt.fromElement;
            if (!uiUtils.contains(el, rel) && el !== rel) {
                this.getDom('label').innerHTML = '0列 x 0行';
                this.getDom('overlay').style.visibility = '';
            }
        },
        _onMouseOut: function (evt, el){
            var rel = evt.relatedTarget || evt.toElement;
            if (!uiUtils.contains(el, rel) && el !== rel) {
                this.getDom('label').innerHTML = '0列 x 0行';
                this.getDom('overlay').style.visibility = 'hidden';
            }
        },
        _onMouseMove: function (evt, el){
            var style = this.getDom('overlay').style;
            var offset = uiUtils.getEventOffset(evt);
            var sideLen = this.lengthOfCellSide;
            var numCols = Math.ceil(offset.left / sideLen);
            var numRows = Math.ceil(offset.top / sideLen);
            this._track(numCols, numRows);
        },
        _onClick: function (){
            this.fireEvent('picktable', this.numCols, this.numRows);
        },
        _onMore: function (){
            this.fireEvent('more');
        }
    };
    utils.inherits(TablePicker, UIBase);
})();
(function (){
    var browser = baidu.editor.browser,
        domUtils = baidu.editor.dom.domUtils,
        uiUtils = baidu.editor.ui.uiUtils;
    
    var TPL_STATEFUL = 'onmousedown="$$.Stateful_onMouseDown(event, this);"' +
        ' onmouseup="$$.Stateful_onMouseUp(event, this);"' +
        ( browser.ie ? (
        ' onmouseenter="$$.Stateful_onMouseEnter(event, this);"' +
        ' onmouseleave="$$.Stateful_onMouseLeave(event, this);"' )
        : (
        ' onmouseover="$$.Stateful_onMouseOver(event, this);"' +
        ' onmouseout="$$.Stateful_onMouseOut(event, this);"' ));
    
    baidu.editor.ui.Stateful = {
        alwalysHoverable: false,
        Stateful_init: function (){
            this._Stateful_dGetHtmlTpl = this.getHtmlTpl;
            this.getHtmlTpl = this.Stateful_getHtmlTpl;
        },
        Stateful_getHtmlTpl: function (){
            var tpl = this._Stateful_dGetHtmlTpl();
            return tpl.replace(/stateful/g, function (){ return TPL_STATEFUL; });
        },
        Stateful_onMouseEnter: function (evt, el){
            if (!this.isDisabled() || this.alwalysHoverable) {
                this.addState('hover');
                this.fireEvent('over');
            }
        },
        Stateful_onMouseLeave: function (evt, el){
            if (!this.isDisabled() || this.alwalysHoverable) {
                this.removeState('hover');
                this.removeState('active');
                this.fireEvent('out');
            }
        },
        Stateful_onMouseOver: function (evt, el){
            var rel = evt.relatedTarget;
            if (!uiUtils.contains(el, rel) && el !== rel) {
                this.Stateful_onMouseEnter(evt, el);
            }
        },
        Stateful_onMouseOut: function (evt, el){
            var rel = evt.relatedTarget;
            if (!uiUtils.contains(el, rel) && el !== rel) {
                this.Stateful_onMouseLeave(evt, el);
            }
        },
        Stateful_onMouseDown: function (evt, el){
            if (!this.isDisabled()) {
                this.addState('active');
            }
        },
        Stateful_onMouseUp: function (evt, el){
            if (!this.isDisabled()) {
                this.removeState('active');
            }
        },
        Stateful_postRender: function (){
            if (this.disabled && !this.hasState('disabled')) {
                this.addState('disabled');
            }
        },
        hasState: function (state){
            return domUtils.hasClass(this.getStateDom(), 'edui-state-' + state);
        },
        addState: function (state){
            if (!this.hasState(state)) {
                this.getStateDom().className += ' edui-state-' + state;
            }
        },
        removeState: function (state){
            if (this.hasState(state)) {
                domUtils.removeClasses(this.getStateDom(), ['edui-state-' + state]);
            }
        },
        getStateDom: function (){
            return this.getDom('state');
        },
        isChecked: function (){
            return this.hasState('checked');
        },
        setChecked: function (checked){
            if (!this.isDisabled() && checked) {
                this.addState('checked');
            } else {
                this.removeState('checked');
            }
        },
        isDisabled: function (){
            return this.hasState('disabled');
        },
        setDisabled: function (disabled){
            if (disabled) {
                this.removeState('hover');
                this.removeState('checked');
                this.removeState('active');
                this.addState('disabled');
            } else {
                this.removeState('disabled');
            }
        }
    };
})();

(function (){
    var utils = baidu.editor.utils,
        UIBase = baidu.editor.ui.UIBase,
        Stateful = baidu.editor.ui.Stateful,
        Button = baidu.editor.ui.Button = function (options){
            this.initOptions(options);
            this.initButton();
        };
    Button.prototype = {
        uiName: 'button',
        label: '',
        title: '',
        showIcon: true,
        showText: true,
        initButton: function (){
            this.initUIBase();
            this.Stateful_init();
        },
        getHtmlTpl: function (){
            return '<div id="##" class="edui-box %%">' +
                '<div id="##_state" stateful>' +
                 '<div class="%%-wrap"><div id="##_body" unselectable="on" ' + (this.title ? 'title="' + this.title + '"' : '') +
                 ' class="%%-body" onmousedown="return false;" onclick="return $$._onClick();">' +
                  (this.showIcon ? '<div class="edui-box edui-icon"></div>' : '') +
                  (this.showText ? '<div class="edui-box edui-label">' + this.label + '</div>' : '') +
                 '</div>' +
                '</div>' +
                '</div></div>';
        },
        postRender: function (){
            this.Stateful_postRender();
        },
        _onClick: function (){
            if (!this.isDisabled()) {
                this.fireEvent('click');
            }
        }
    };
    utils.inherits(Button, UIBase);
    utils.extend(Button.prototype, Stateful);

})();

(function (){
    var utils = baidu.editor.utils,
        uiUtils = baidu.editor.ui.uiUtils,
        domUtils = baidu.editor.dom.domUtils,
        UIBase = baidu.editor.ui.UIBase,
        Stateful = baidu.editor.ui.Stateful,
        SplitButton = baidu.editor.ui.SplitButton = function (options){
            this.initOptions(options);
            this.initSplitButton();
        };
    SplitButton.prototype = {
        popup: null,
        uiName: 'splitbutton',
        title: '',
        initSplitButton: function (){
            this.initUIBase();
            this.Stateful_init();
            var me = this;
            if (this.popup != null) {
                var popup = this.popup;
                this.popup = null;
                this.setPopup(popup);
            }
        },
        _UIBase_postRender: UIBase.prototype.postRender,
        postRender: function (){
            this.Stateful_postRender();
            this._UIBase_postRender();
        },
        setPopup: function (popup){
            if (this.popup === popup) return;
            if (this.popup != null) {
                this.popup.dispose();
            }
            popup.addListener('show', utils.bind(this._onPopupShow, this));
            popup.addListener('hide', utils.bind(this._onPopupHide, this));
            popup.addListener('postrender', utils.bind(function (){
                popup.getDom('body').appendChild(
                    uiUtils.createElementByHtml('<div id="' +
                        this.popup.id + '_bordereraser" class="edui-bordereraser edui-background" style="width:' +
                        (uiUtils.getClientRect(this.getDom()).width - 2) + 'px"></div>')
                    );
                popup.getDom().className += ' ' + this.className;
            }, this));
            this.popup = popup;
        },
        _onPopupShow: function (){
            this.addState('opened');
        },
        _onPopupHide: function (){
            this.removeState('opened');
        },
        getHtmlTpl: function (){
            return '<div id="##" class="edui-box %%">' +
                '<div '+ (this.title ? 'title="' + this.title + '"' : '') +' id="##_state" stateful><div class="%%-body">' +
                '<div id="##_button_body" class="edui-box edui-button-body" onclick="$$._onButtonClick(event, this);">' +
                '<div class="edui-box edui-icon"></div>' +
                '</div>' +
                '<div class="edui-box edui-splitborder"></div>' +
                '<div class="edui-box edui-arrow" onclick="$$._onArrowClick();"></div>' +
                '</div></div></div>';
        },
        showPopup: function (){
            var rect = uiUtils.getClientRect(this.getDom());
            rect.top -= this.popup.SHADOW_RADIUS;
            rect.height += this.popup.SHADOW_RADIUS;
            this.popup.showAnchorRect(rect);
        },
        _onArrowClick: function (event, el){
            if (!this.isDisabled()) {
                this.showPopup();
            }
        },
        _onButtonClick: function (){
            if (!this.isDisabled()) {
                this.fireEvent('buttonclick');
            }
        }
    };
    utils.inherits(SplitButton, UIBase);
    utils.extend(SplitButton.prototype, Stateful, true);

})();

(function (){
    var utils = baidu.editor.utils,
        uiUtils = baidu.editor.ui.uiUtils,
        ColorPicker = baidu.editor.ui.ColorPicker,
        Popup = baidu.editor.ui.Popup,
        SplitButton = baidu.editor.ui.SplitButton,
        ColorButton = baidu.editor.ui.ColorButton = function (options){
            this.initOptions(options);
            this.initColorButton();
        };
    ColorButton.prototype = {
        initColorButton: function (){
            var me = this;
            this.popup = new Popup({
                content: new ColorPicker({
                    noColorText: '清除颜色',
                    onpickcolor: function (t, color){
                        me._onPickColor(color);
                    },
                    onpicknocolor: function (t, color){
                        me._onPickNoColor(color);
                    }
                }),
                editor:me.editor
            });
            this.initSplitButton();
        },
        _SplitButton_postRender: SplitButton.prototype.postRender,
        postRender: function (){
            this._SplitButton_postRender();
            this.getDom('button_body').appendChild(
                uiUtils.createElementByHtml('<div id="' + this.id + '_colorlump" class="edui-colorlump"></div>')
                );
            this.getDom().className += ' edui-colorbutton';
        },
        setColor: function (color){
            this.getDom('colorlump').style.backgroundColor = color;
            this.color = color;
        },
        _onPickColor: function (color){
            if (this.fireEvent('pickcolor', color) !== false) {
                this.setColor(color);
                this.popup.hide();
            }
        },
        _onPickNoColor: function (color){
            if (this.fireEvent('picknocolor') !== false) {
                this.popup.hide();
            }
        }
    };
    utils.inherits(ColorButton, SplitButton);

})();

(function (){
    var utils = baidu.editor.utils,
        Popup = baidu.editor.ui.Popup,
        TablePicker = baidu.editor.ui.TablePicker,
        SplitButton = baidu.editor.ui.SplitButton,
        TableButton = baidu.editor.ui.TableButton = function (options){
            this.initOptions(options);
            this.initTableButton();
        };
    TableButton.prototype = {
        initTableButton: function (){
            var me = this;
            this.popup = new Popup({
                content: new TablePicker({
                    onpicktable: function (t, numCols, numRows){
                        me._onPickTable(numCols, numRows);
                    },
                    onmore: function (){
                        me.popup.hide();
                        me.fireEvent('more');
                    }
                }),
                'editor':me.editor
            });
            this.initSplitButton();
        },
        _onPickTable: function (numCols, numRows){
            if (this.fireEvent('picktable', numCols, numRows) !== false) {
                this.popup.hide();
            }
        }
    };
    utils.inherits(TableButton, SplitButton);

})();

(function (){
    var utils = baidu.editor.utils,
        UIBase = baidu.editor.ui.UIBase;

    var AutoTypeSetPicker = baidu.editor.ui.AutoTypeSetPicker = function (options){
        this.initOptions(options);
        this.initAutoTypeSetPicker();
    };
    AutoTypeSetPicker.prototype = {
        initAutoTypeSetPicker: function (){
            this.initUIBase();
        },
        getHtmlTpl: function (){
            var opt = this.editor.options.autotypeset;

            return '<div id="##" class="edui-autotypesetpicker %%">' +
                 '<div class="edui-autotypesetpicker-body">' +
                    '<table >' +
                        '<tr><td colspan="2"><input type="checkbox" name="mergeEmptyline" '+ (opt["mergeEmptyline"] ? "checked" : "" )+'>合并空行</td><td colspan="2"><input type="checkbox" name="removeEmptyline" '+ (opt["removeEmptyline"] ? "checked" : "" )+'>删除空行</td></tr>'+
                        '<tr><td colspan="2"><input type="checkbox" name="removeClass" '+ (opt["removeClass"] ? "checked" : "" )+'>清除样式</td><td colspan="2"><input type="checkbox" name="indent" '+ (opt["indent"] ? "checked" : "" )+'>首行缩进2字</td></tr>'+
                        '<tr><td colspan="2"><input type="checkbox" name="textAlign" '+ (opt["textAlign"] ? "checked" : "" )+'>对齐方式：</td><td colspan="2" id="textAlignValue"><input type="radio" name="textAlignValue" value="left" '+((opt["textAlign"]&&opt["textAlign"]=="left") ? "checked" : "")+'>左对齐<input type="radio" name="textAlignValue" value="center" '+((opt["textAlign"]&&opt["textAlign"]=="center") ? "checked" : "")+'>居中对齐<input type="radio" name="textAlignValue" value="right" '+((opt["textAlign"]&&opt["textAlign"]=="right") ? "checked" : "")+'>右对齐 </tr>'+
                        '<tr><td colspan="2"><input type="checkbox" name="imageBlockLine" '+ (opt["imageBlockLine"] ? "checked" : "" )+'>图片浮动</td>' +
                            '<td colspan="2" id="imageBlockLineValue">' +
                                '<input type="radio" name="imageBlockLineValue" value="none" '+((opt["imageBlockLine"]&&opt["imageBlockLine"]=="none") ? "checked" : "")+'>默认' +
                                '<input type="radio" name="imageBlockLineValue" value="left" '+((opt["imageBlockLine"]&&opt["imageBlockLine"]=="left") ? "checked" : "")+'>左浮动' +
                                '<input type="radio" name="imageBlockLineValue" value="center" '+((opt["imageBlockLine"]&&opt["imageBlockLine"]=="center") ? "checked" : "")+'>独占行居中' +
                                '<input type="radio" name="imageBlockLineValue" value="right" '+((opt["imageBlockLine"]&&opt["imageBlockLine"]=="right") ? "checked" : "")+'>右浮动</tr>'+

                        '<tr><td colspan="2"><input type="checkbox" name="clearFontSize" '+ (opt["clearFontSize"] ? "checked" : "" )+'>清除字号</td><td colspan="2"><input type="checkbox" name="clearFontFamily" '+ (opt["clearFontFamily"] ? "checked" : "" )+'>清除字体</td></tr>'+
                        '<tr><td colspan="4"><input type="checkbox" name="removeEmptyNode" '+ (opt["removeEmptyNode"] ? "checked" : "" )+'>去掉冗余的html代码</td></tr>'+
                        '<tr><td colspan="4"><input type="checkbox" name="pasteFilter" '+ (opt["pasteFilter"] ? "checked" : "" )+'>粘贴过滤 (对每次粘贴的内容应用以上过滤规则)</td></tr>'+
                        '<tr><td colspan="4" align="right"><button >执行</button></td></tr>'+
                    '</table>'+
                 '</div>' +
                '</div>';


        },
        _UIBase_render: UIBase.prototype.render
    };
    utils.inherits(AutoTypeSetPicker, UIBase);
})();

(function (){
    var utils = baidu.editor.utils,
        Popup = baidu.editor.ui.Popup,
        AutoTypeSetPicker = baidu.editor.ui.AutoTypeSetPicker,
        SplitButton = baidu.editor.ui.SplitButton,
        AutoTypeSetButton = baidu.editor.ui.AutoTypeSetButton = function (options){
            this.initOptions(options);
            this.initAutoTypeSetButton();
        };
    function getPara(me){
        var opt = me.editor.options.autotypeset,
            cont = me.getDom(),
            ipts = domUtils.getElementsByTagName(cont,"input");
        for(var i=ipts.length-1,ipt;ipt=ipts[i--];){
            if(ipt.getAttribute("type")=="checkbox"){
                var attrName = ipt.getAttribute("name");
                opt[attrName] && delete opt[attrName];
                if(ipt.checked){
                    var attrValue = document.getElementById(attrName+"Value");
                    if(attrValue){
                        if(/input/ig.test(attrValue.tagName)){
                            opt[attrName] = attrValue.value;
                        }else{
                            var iptChilds = attrValue.getElementsByTagName("input");
                            for(var j=iptChilds.length-1,iptchild;iptchild=iptChilds[j--];){
                                if(iptchild.checked){
                                    opt[attrName] = iptchild.value;
                                    break;
                                }
                            }
                        }
                    }else{
                        opt[attrName] = true;
                    }
                }
            }
        }
        var selects = domUtils.getElementsByTagName(cont,"select");
        for(var i=0,si;si=selects[i++];){
            var attr = si.getAttribute('name');
            opt[attr] = opt[attr] ? si.value : '';
        }
        me.editor.options.autotypeset = opt;
    }
    AutoTypeSetButton.prototype = {
        initAutoTypeSetButton: function (){
            var me = this;
            this.popup = new Popup({
                content: new AutoTypeSetPicker({editor:me.editor}),
                'editor':me.editor,
                hide : function(){

                    if (!this._hidden && this.getDom()) {
                        getPara(this);
                        this.getDom().style.display = 'none';
                        this._hidden = true;
                        this.fireEvent('hide');
                    }
                }
            });
            var flag = 0;
            this.popup.addListener('postRenderAfter',function(){
                var popupUI = this;
                if(flag)return;
                var cont = this.getDom(),
                    btn = cont.getElementsByTagName('button')[0];
                btn.onclick = function(){
                    getPara(popupUI);
                    me.editor.execCommand('autotypeset')
                };
                flag = 1;
            });
            this.initSplitButton();
        }
    };
    utils.inherits(AutoTypeSetButton, SplitButton);

})();
(function (){
    var utils = baidu.editor.utils,
        uiUtils = baidu.editor.ui.uiUtils,
        UIBase = baidu.editor.ui.UIBase,
        Toolbar = baidu.editor.ui.Toolbar = function (options){
            this.initOptions(options);
            this.initToolbar();
        };
    Toolbar.prototype = {
        items: null,
        initToolbar: function (){
            this.items = this.items || [];
            this.initUIBase();
        },
        add: function (item){
            this.items.push(item);
        },
        getHtmlTpl: function (){
            var buff = [];
            for (var i=0; i<this.items.length; i++) {
                buff[i] = this.items[i].renderHtml();
            }
            return '<div id="##" class="edui-toolbar %%" onselectstart="return false;" onmousedown="return $$._onMouseDown(event, this);">' +
                buff.join('') +
                '</div>'
        },
        postRender: function (){
            var box = this.getDom();
            for (var i=0; i<this.items.length; i++) {
                this.items[i].postRender();
            }
            uiUtils.makeUnselectable(box);
        },
        _onMouseDown: function (){
            return false;
        }
    };
    utils.inherits(Toolbar, UIBase);

})();

(function (){
    var utils = baidu.editor.utils,
        domUtils = baidu.editor.dom.domUtils,
        uiUtils = baidu.editor.ui.uiUtils,
        UIBase = baidu.editor.ui.UIBase,
        Popup = baidu.editor.ui.Popup,
        Stateful = baidu.editor.ui.Stateful,
        Menu = baidu.editor.ui.Menu = function (options){
            this.initOptions(options);
            this.initMenu();
        };

    var menuSeparator = {
        renderHtml: function (){
            return '<div class="edui-menuitem edui-menuseparator"><div class="edui-menuseparator-inner"></div></div>';
        },
        postRender: function (){},
        queryAutoHide: function (){ return true; }
    };
    Menu.prototype = {
        items: null,
        uiName: 'menu',
        initMenu: function (){
            this.items = this.items || [];
            this.initPopup();
            this.initItems();
        },
        initItems: function (){
            for (var i=0; i<this.items.length; i++) {
                var item = this.items[i];
                if (item == '-') {
                    this.items[i] = this.getSeparator();
                } else if (!(item instanceof MenuItem)) {
                    this.items[i] = this.createItem(item);
                }
            }
        },
        getSeparator: function (){
            return menuSeparator;
        },
        createItem: function (item){
            return new MenuItem(item);
        },
        _Popup_getContentHtmlTpl: Popup.prototype.getContentHtmlTpl,
        getContentHtmlTpl: function (){
            if (this.items.length == 0) {
                return this._Popup_getContentHtmlTpl();
            }
            var buff = [];
            for (var i=0; i<this.items.length; i++) {
                var item = this.items[i];
                buff[i] = item.renderHtml();
            }
            return ('<div class="%%-body">' + buff.join('') + '</div>');
        },
        _Popup_postRender: Popup.prototype.postRender,
        postRender: function (){
            var me = this;
            for (var i=0; i<this.items.length; i++) {
                var item = this.items[i];
                item.ownerMenu = this;
                item.postRender();
            }
            domUtils.on(this.getDom(), 'mouseover', function (evt){
                evt = evt || event;
                var rel = evt.relatedTarget || evt.fromElement;
                var el = me.getDom();
                if (!uiUtils.contains(el, rel) && el !== rel) {
                    me.fireEvent('over');
                }
            });
            this._Popup_postRender();
        },
        queryAutoHide: function (el){
            if (el) {
                if (uiUtils.contains(this.getDom(), el)) {
                    return false;
                }
                for (var i=0; i<this.items.length; i++) {
                    var item = this.items[i];
                    if (item.queryAutoHide(el) === false) {
                        return false;
                    }
                }
            }
        },
        clearItems: function (){
            for (var i=0; i<this.items.length; i++) {
                var item = this.items[i];
                clearTimeout(item._showingTimer);
                clearTimeout(item._closingTimer);
                if (item.subMenu) {
                    item.subMenu.destroy();
                }
            }
            this.items = [];
        },
        destroy: function (){
            if (this.getDom()) {
                domUtils.remove(this.getDom());
            }
            this.clearItems();
        },
        dispose: function (){
            this.destroy();
        }
    };
    utils.inherits(Menu, Popup);
    
    var MenuItem = baidu.editor.ui.MenuItem = function (options){
        this.initOptions(options);
        this.initUIBase();
        this.Stateful_init();
        if (this.subMenu && !(this.subMenu instanceof Menu)) {
            this.subMenu = new Menu(this.subMenu);
        }
    };
    MenuItem.prototype = {
        label: '',
        subMenu: null,
        ownerMenu: null,
        uiName: 'menuitem',
        alwalysHoverable: true,
        getHtmlTpl: function (){
            return '<div id="##" class="%%" stateful onclick="$$._onClick(event, this);">' +
                '<div class="%%-body">' +
                this.renderLabelHtml() +
                '</div>' +
                '</div>';
        },
        postRender: function (){
            var me = this;
            this.addListener('over', function (){
                me.ownerMenu.fireEvent('submenuover', me);
                if (me.subMenu) {
                    me.delayShowSubMenu();
                }
            });
            if (this.subMenu) {
                this.getDom().className += ' edui-hassubmenu';
                this.subMenu.render();
                this.addListener('out', function (){
                    me.delayHideSubMenu();
                });
                this.subMenu.addListener('over', function (){
                    clearTimeout(me._closingTimer);
                    me._closingTimer = null;
                    me.addState('opened');
                });
                this.ownerMenu.addListener('hide', function (){
                    me.hideSubMenu();
                });
                this.ownerMenu.addListener('submenuover', function (t, subMenu){
                    if (subMenu !== me) {
                        me.delayHideSubMenu();
                    }
                });
                this.subMenu._bakQueryAutoHide = this.subMenu.queryAutoHide;
                this.subMenu.queryAutoHide = function (el){
                    if (el && uiUtils.contains(me.getDom(), el)) {
                        return false;
                    }
                    return this._bakQueryAutoHide(el);
                };
            }
            this.getDom().style.tabIndex = '-1';
            uiUtils.makeUnselectable(this.getDom());
            this.Stateful_postRender();
        },
        delayShowSubMenu: function (){
            var me = this;
            if (!me.isDisabled()) {
                me.addState('opened');
                clearTimeout(me._showingTimer);
                clearTimeout(me._closingTimer);
                me._closingTimer = null;
                me._showingTimer = setTimeout(function (){
                    me.showSubMenu();
                }, 250);
            }
        },
        delayHideSubMenu: function (){
            var me = this;
            if (!me.isDisabled()) {
                me.removeState('opened');
                clearTimeout(me._showingTimer);
                if (!me._closingTimer) {
                    me._closingTimer = setTimeout(function (){
                        if (!me.hasState('opened')) {
                            me.hideSubMenu();
                        }
                        me._closingTimer = null;
                    }, 400);
                }
            }
        },
        renderLabelHtml: function (){
            return '<div class="edui-arrow"></div>' +
                '<div class="edui-box edui-icon"></div>' +
                '<div class="edui-box edui-label %%-label">' + (this.label || '') + '</div>';
        },
        getStateDom: function (){
            return this.getDom();
        },
        queryAutoHide: function (el){
            if (this.subMenu && this.hasState('opened')) {
                return this.subMenu.queryAutoHide(el);
            }
        },
        _onClick: function (event, this_){
            if (this.hasState('disabled')) return;
            if (this.fireEvent('click', event, this_) !== false) {
                if (this.subMenu) {
                    this.showSubMenu();
                } else {
                    Popup.postHide();
                }
            }
        },
        showSubMenu: function (){
            var rect = uiUtils.getClientRect(this.getDom());
            rect.right -= 5;
            rect.left += 2;
            rect.width -= 7;
            rect.top -= 4;
            rect.bottom += 4;
            rect.height += 8;
            this.subMenu.showAnchorRect(rect, true, true);
        },
        hideSubMenu: function (){
            this.subMenu.hide();
        }
    };
    utils.inherits(MenuItem, UIBase);
    utils.extend(MenuItem.prototype, Stateful, true);
})();

(function (){
    var utils = baidu.editor.utils,
        uiUtils = baidu.editor.ui.uiUtils,
        Menu = baidu.editor.ui.Menu,
        SplitButton = baidu.editor.ui.SplitButton,
        Combox = baidu.editor.ui.Combox = function (options){
            this.initOptions(options);
            this.initCombox();
        };
    Combox.prototype = {
        uiName: 'combox',
        initCombox: function (){
            var me = this;
            this.items = this.items || [];
            for (var i=0; i<this.items.length; i++) {
                var item = this.items[i];
                item.uiName = 'listitem';
                item.index = i;
                item.onclick = function (){
                    me.selectByIndex(this.index);
                };
            }
            this.popup = new Menu({
                items: this.items,
                uiName: 'list',
                editor:this.editor
            });
            this.initSplitButton();
        },
        _SplitButton_postRender: SplitButton.prototype.postRender,
        postRender: function (){
            this._SplitButton_postRender();
            this.setLabel(this.label || '');
            this.setValue(this.initValue || '');
        },
        showPopup: function (){
            var rect = uiUtils.getClientRect(this.getDom());
            rect.top += 1;
            rect.bottom -= 1;
            rect.height -= 2;
            this.popup.showAnchorRect(rect);
        },
        getValue: function (){
            return this.value;
        },
        setValue: function (value){
            var index = this.indexByValue(value);
            if (index != -1) {
                this.selectedIndex = index;
                this.setLabel(this.items[index].label);
                this.value = this.items[index].value;
            } else {
                this.selectedIndex = -1;
                this.setLabel(this.getLabelForUnknowValue(value));
                this.value = value;
            }
        },
        setLabel: function (label){
            this.getDom('button_body').innerHTML = label;
            this.label = label;
        },
        getLabelForUnknowValue: function (value){
            return value;
        },
        indexByValue: function (value){
            for (var i=0; i<this.items.length; i++) {
                if (value == this.items[i].value) {
                    return i;
                }
            }
            return -1;
        },
        getItem: function (index){
            return this.items[index];
        },
        selectByIndex: function (index){
            if (index < this.items.length && this.fireEvent('select', index) !== false) {
                this.selectedIndex = index;
                this.value = this.items[index].value;
                this.setLabel(this.items[index].label);
            }
        }
    };
    utils.inherits(Combox, SplitButton);
})();

(function (){
    var utils = baidu.editor.utils,
        domUtils = baidu.editor.dom.domUtils,
        uiUtils = baidu.editor.ui.uiUtils,
        Mask = baidu.editor.ui.Mask,
        UIBase = baidu.editor.ui.UIBase,
        Button = baidu.editor.ui.Button,
        Dialog = baidu.editor.ui.Dialog = function (options){
            this.initOptions(utils.extend({
                autoReset: true,
                draggable: true,
                onok: function (){},
                oncancel: function (){},
                onclose: function (t, ok){
                    return ok ? this.onok() : this.oncancel();
                }
            },options));
            this.initDialog();
        };
    var modalMask;
    var dragMask;
    Dialog.prototype = {
        draggable: false,
        uiName: 'dialog',
        initDialog: function (){
            var me = this;
            this.initUIBase();
            this.modalMask = (modalMask || (modalMask = new Mask({
                className: 'edui-dialog-modalmask'
            })));
            this.dragMask = (dragMask || (dragMask = new Mask({
                className: 'edui-dialog-dragmask'
            })));
            this.closeButton = new Button({
                className: 'edui-dialog-closebutton',
                title: '关闭对话框',
                onclick: function (){
                    me.close(false);
                }
            });
            if (this.buttons) {
                for (var i=0; i<this.buttons.length; i++) {
                    if (!(this.buttons[i] instanceof Button)) {
                        this.buttons[i] = new Button(this.buttons[i]);
                    }
                }
            }
        },
        fitSize: function (){
            var popBodyEl = this.getDom('body');
            var size = this.mesureSize();
            popBodyEl.style.width = size.width + 'px';
            popBodyEl.style.height = size.height + 'px';
            return size;
        },
        safeSetOffset: function (offset){
            var me = this;
            var el = me.getDom();
            var vpRect = uiUtils.getViewportRect();
            var rect = uiUtils.getClientRect(el);
            var left = offset.left;
            if (left + rect.width > vpRect.right) {
                left = vpRect.right - rect.width;
            }
            var top = offset.top;
            if (top + rect.height > vpRect.bottom) {
                top = vpRect.bottom - rect.height;
            }
            el.style.left = Math.max(left, 0) + 'px';
            el.style.top = Math.max(top, 0) + 'px';
        },
        showAtCenter: function (){
            this.getDom().style.display = '';
            var vpRect = uiUtils.getViewportRect();
            var popSize = this.fitSize();
            var titleHeight = this.getDom('titlebar').offsetHeight | 0;
            var left = vpRect.width / 2 - popSize.width / 2;
            var top = vpRect.height / 2 - (popSize.height - titleHeight) / 2 - titleHeight;
            var popEl = this.getDom();
            this.safeSetOffset({
                left: Math.max(left | 0, 0),
                top: Math.max(top | 0, 0)
            });
            if (!domUtils.hasClass(popEl, 'edui-state-centered')) {
                popEl.className += ' edui-state-centered';
            }
            this._show();
        },
        getContentHtml: function (){
            var contentHtml = '';
            if (typeof this.content == 'string') {
                contentHtml = this.content;
            } else if (this.iframeUrl) {
                contentHtml = '<span id="'+ this.id +'_contmask" class="dialogcontmask"></span><iframe id="'+ this.id +
                    '_iframe" class="%%-iframe" height="100%" width="100%" frameborder="0" src="'+ this.iframeUrl +'"></iframe>';
            }
            return contentHtml;
        },
        getHtmlTpl: function (){
            var footHtml = '';
            if (this.buttons) {
                var buff = [];
                for (var i=0; i<this.buttons.length; i++) {
                    buff[i] = this.buttons[i].renderHtml();
                }
                footHtml = '<div class="%%-foot">' +
                     '<div id="##_buttons" class="%%-buttons">' + buff.join('') + '</div>' +
                    '</div>';
            }
            return '<div id="##" class="%%"><div class="%%-wrap"><div id="##_body" class="%%-body">' +
                '<div class="%%-shadow"></div>' +
                '<div id="##_titlebar" class="%%-titlebar">' +
                '<div class="%%-draghandle" onmousedown="$$._onTitlebarMouseDown(event, this);">' +
                 '<span class="%%-caption">' + (this.title || '') + '</span>' +
                '</div>' +
                this.closeButton.renderHtml() +
                '</div>' +
                '<div id="##_content" class="%%-content">'+ ( this.autoReset ? '' : this.getContentHtml()) +'</div>' +
                footHtml +
                '</div></div></div>';
        },
        postRender: function (){
            if (!this.modalMask.getDom()) {
                this.modalMask.render();
                this.modalMask.hide();
            }
            if (!this.dragMask.getDom()) {
                this.dragMask.render();
                this.dragMask.hide();
            }
            var me = this;
            this.addListener('show', function (){
                me.modalMask.show(this.getDom().style.zIndex - 2);
            });
            this.addListener('hide', function (){
                me.modalMask.hide();
            });
            if (this.buttons) {
                for (var i=0; i<this.buttons.length; i++) {
                    this.buttons[i].postRender();
                }
            }
            domUtils.on(window, 'resize', function (){
                setTimeout(function (){
                    if (!me.isHidden()) {
                        me.safeSetOffset(uiUtils.getClientRect(me.getDom()));
                    }
                });
            });
            this._hide();
        },
        mesureSize: function (){
            var body = this.getDom('body');
            var width = uiUtils.getClientRect(this.getDom('content')).width;
            var dialogBodyStyle = body.style;
            dialogBodyStyle.width = width;
            return uiUtils.getClientRect(body);
        },
        _onTitlebarMouseDown: function (evt, el){
            if (this.draggable) {
                var rect;
                var vpRect = uiUtils.getViewportRect();
                var me = this;
                uiUtils.startDrag(evt, {
                    ondragstart: function (){
                        rect = uiUtils.getClientRect(me.getDom());
                        me.getDom('contmask').style.visibility = 'visible';
                        me.dragMask.show(me.getDom().style.zIndex - 1);
                    },
                    ondragmove: function (x, y){
                        var left = rect.left + x;
                        var top = rect.top + y;
                        me.safeSetOffset({
                            left: left,
                            top: top
                        });
                    },
                    ondragstop: function (){
                        me.getDom('contmask').style.visibility = 'hidden';
                        domUtils.removeClasses(me.getDom(), ['edui-state-centered']);
                        me.dragMask.hide();
                    }
                });
            }
        },
        reset: function (){
            this.getDom('content').innerHTML = this.getContentHtml();
        },
        _show: function (){
            if (this._hidden) {
                this.getDom().style.display = '';
                this.editor.container.style.zIndex && (this.getDom().style.zIndex = this.editor.container.style.zIndex * 1 + 10);
                this._hidden = false;
                this.fireEvent('show');
                baidu.editor.ui.uiUtils.getFixedLayer().style.zIndex = this.getDom().style.zIndex - 4;
            }
        },
        isHidden: function (){
            return this._hidden;
        },
        _hide: function (){
            if (!this._hidden) {
                this.getDom().style.display = 'none';
                this.getDom().style.zIndex = '';
                this._hidden = true;
                this.fireEvent('hide');
            }
        },
        open: function (){
            if (this.autoReset) {
                try{
                    this.reset();
                }catch(e){
                    this.render();
                    this.open()
                }
            }
            this.showAtCenter();
            if (this.iframeUrl) {
                try {
                    this.getDom('iframe').focus();
                } catch(ex){}
            }
        },
        _onCloseButtonClick: function (evt, el){
            this.close(false);
        },
        close: function (ok){
            if (this.fireEvent('close', ok) !== false) {
                this._hide();
            }
        }
    };
    utils.inherits(Dialog, UIBase);
})();

(function (){
    var utils = baidu.editor.utils,
        Menu = baidu.editor.ui.Menu,
        SplitButton = baidu.editor.ui.SplitButton,
        MenuButton = baidu.editor.ui.MenuButton = function (options){
            this.initOptions(options);
            this.initMenuButton();
        };
    MenuButton.prototype = {
        initMenuButton: function (){
            var me = this;
            this.uiName = "menubutton";
            this.popup = new Menu({
                items: me.items,
                className: me.className,
                editor:me.editor
            });
            this.popup.addListener('show', function (){
                var list = this;
                for (var i=0; i<list.items.length; i++) {
                    list.items[i].removeState('checked');
                    if (list.items[i].value == me._value) {
                        list.items[i].addState('checked');
                        this.value = me._value;
                    }
                }
            });
            this.initSplitButton();
        },
        setValue : function(value){
            this._value = value;
        }
        
    };
    utils.inherits(MenuButton, SplitButton);
})();
(function (){
    var utils = baidu.editor.utils;
    var editorui = baidu.editor.ui;
    var _Dialog = editorui.Dialog;
    editorui.Dialog = function (options){
        var dialog = new _Dialog(options);
        dialog.addListener('hide', function (){
            if (dialog.editor) {
                var editor = dialog.editor;
                try {
                    editor.focus()
                } catch(ex){}
            }
        });
        return dialog;
    };

    var  iframeUrlMap ={
        'anchor':'~/dialogs/anchor/anchor.html',
        'insertimage':'~/dialogs/image/image.html',
        'inserttable':'~/dialogs/table/table.html',
        'link':'~/dialogs/link/link.html',
        'spechars':'~/dialogs/spechars/spechars.html',
        'searchreplace':'~/dialogs/searchreplace/searchreplace.html',
        'map':'~/dialogs/map/map.html',
        'gmap':'~/dialogs/gmap/gmap.html',
        'insertvideo':'~/dialogs/video/video.html',
        'help':'~/dialogs/help/help.html',
        'highlightcode':'~/dialogs/code/code.html',
        'emotion':'~/dialogs/emotion/emotion.html',
        'wordimage':'~/dialogs/wordimage/wordimage.html',
        'attachment':'~/dialogs/attachment/attachment.html',
        'insertframe':'~/dialogs/insertframe/insertframe.html',
        'edittd':'~/dialogs/table/edittd.html',
        'webapp':'~/dialogs/webapp/webapp.html',
        'snapscreen': '~/dialogs/snapscreen/snapscreen.html'
    };
    var btnCmds = ['undo', 'redo','formatmatch',
        'bold', 'italic', 'underline',
        'strikethrough', 'subscript', 'superscript','source','indent','outdent',
        'blockquote','pasteplain','pagebreak',
        'selectall', 'print', 'preview', 'horizontal', 'removeformat','time','date','unlink',
        'insertparagraphbeforetable','insertrow','insertcol','mergeright','mergedown','deleterow',
        'deletecol','splittorows','splittocols','splittocells','mergecells','deletetable'];

    for(var i=0,ci;ci=btnCmds[i++];){
        ci = ci.toLowerCase();
        editorui[ci] = function (cmd){
            return function (editor, title){
                var ui = new editorui.Button({
                    className: 'edui-for-' + cmd,
                    title: title || editor.options.labelMap[cmd] || '',
                    onclick: function (){
                        editor.execCommand(cmd);
                    },
                    showText: false
                });
                editor.addListener('selectionchange', function (type, causeByUi, uiReady){
                    var state = editor.queryCommandState(cmd);
                    if (state == -1) {
                        ui.setDisabled(true);
                        ui.setChecked(false);
                    } else {
                        if(!uiReady){
                            ui.setDisabled(false);
                            ui.setChecked(state);
                        }
                    }
                });
                return ui;
            };
        }(ci);
    }
    editorui.cleardoc = function(editor, title){
        var ui = new editorui.Button({
            className: 'edui-for-cleardoc',
            title: title || editor.options.labelMap.cleardoc || '',
            onclick: function (){
                if(confirm('确定清空文档吗？')){
                    editor.execCommand('cleardoc');
                }
            }
        });
        editor.addListener('selectionchange',function(){
            ui.setDisabled(editor.queryCommandState('cleardoc') == -1);
        });
        return ui;
    };
    var typeset = {
        'justify' : ['left','right','center','justify'],
        'imagefloat' :  ['none','left','center','right'],
        'directionality' : ['ltr','rtl']
    };

    for(var p in typeset){

        (function(cmd,val){
            for(var i=0,ci;ci=val[i++];){
                (function(cmd2){
                    editorui[cmd.replace('float','')+cmd2] = function (editor, title){
                        var ui = new editorui.Button({
                            className: 'edui-for-'+ cmd.replace('float','') + cmd2,
                            title: title || editor.options.labelMap[cmd.replace('float','') + cmd2] || '',
                            onclick: function (){
                                editor.execCommand(cmd, cmd2);
                            }
                        });
                        editor.addListener('selectionchange', function (type, causeByUi, uiReady){
                            ui.setDisabled(editor.queryCommandState(cmd) == -1);
                            ui.setChecked(editor.queryCommandValue(cmd) == cmd2 && !uiReady);
                        });
                        return ui;
                    };
                })(ci)
            }
        })(p,typeset[p])
    }
    for(var i=0,ci;ci = ['backcolor', 'forecolor'][i++];){
        editorui[ci] = function (cmd){
            return function (editor, title){
                var ui = new editorui.ColorButton({
                    className: 'edui-for-' + cmd,
                    color: 'default',
                    title: title || editor.options.labelMap[cmd] || '',
                    editor:editor,
                    onpickcolor: function (t, color){
                        editor.execCommand(cmd, color);
                    },
                    onpicknocolor: function (){
                        editor.execCommand(cmd, 'default');
                        this.setColor('transparent');
                        this.color = 'default';
                    },
                    onbuttonclick: function (){
                        editor.execCommand(cmd, this.color);
                    }
                });
                editor.addListener('selectionchange', function (){
                    ui.setDisabled(editor.queryCommandState(cmd) == -1);
                });
                return ui;
            };
        }(ci);
    }


    var dialogBtns = {
        noOk : ['searchreplace','help','spechars','webapp'],
        ok : ['attachment','anchor','link', 'insertimage', 'map', 'gmap','insertframe','wordimage',
            'insertvideo','highlightcode','insertframe','edittd']

    };

    for(var p in dialogBtns){
        (function(type,vals){
            for(var i = 0,ci;ci=vals[i++];){
                (function(cmd){
                    editorui[cmd] =function (editor, iframeUrl, title){
                        iframeUrl = iframeUrl || (editor.options.iframeUrlMap||{})[cmd] || iframeUrlMap[cmd];
                        title = title ||editor.options.labelMap[cmd.toLowerCase()] || '';
                        var dialog
                        if(iframeUrl){
                            dialog = new editorui.Dialog( utils.extend({
                                    iframeUrl: editor.ui.mapUrl(iframeUrl),
                                    editor: editor,
                                    className: 'edui-for-' + cmd,
                                    title: title
                                },type == 'ok'?{
                                    buttons: [{
                                        className: 'edui-okbutton',
                                        label: '确认',
                                        onclick: function (){
                                            dialog.close(true);
                                        }
                                    }, {
                                        className: 'edui-cancelbutton',
                                        label: '取消',
                                        onclick: function (){
                                            dialog.close(false);
                                        }
                                    }]
                                }:{}));

                            editor.ui._dialogs[cmd+"Dialog"] = dialog;
                        }

                        var ui = new editorui.Button({
                            className: 'edui-for-' + cmd,
                            title: title,
                            onclick: function (){
                                if(dialog){
                                    if(cmd=="wordimage"){//wordimage需要先判断是否存在word_img属性再确定是否打开
                                        editor.execCommand("wordimage","word_img");
                                        if(editor.word_img){
                                            dialog.render();
                                            dialog.open();
                                        }
                                    }else{
                                        dialog.render();
                                        dialog.open();
                                    }
                                }
                            }
                        });
                        editor.addListener('selectionchange', function (){
                            var unNeedCheckState = {'edittd':1,'edittable':1};
                            if(cmd in unNeedCheckState)return;

                            var state = editor.queryCommandState(cmd);
                            ui.setDisabled(state == -1);
                            ui.setChecked(state);
                        });
                        return ui;
                    };
                })(ci.toLowerCase())
            }
        })(p,dialogBtns[p])
    }

    editorui.snapscreen = function(editor, iframeUrl, title){
            title = title || editor.options.labelMap['snapscreen'] || '';
            var ui = new editorui.Button({
                className: 'edui-for-snapscreen',
                title: title,
                onclick: function (){
                    editor.execCommand("snapscreen");
                }
            });

            if(browser.ie){
                iframeUrl = iframeUrl || (editor.options.iframeUrlMap||{})["snapscreen"] || iframeUrlMap["snapscreen"];
                if(iframeUrl){
                    var dialog = new editorui.Dialog({
                        iframeUrl: editor.ui.mapUrl(iframeUrl),
                        editor: editor,
                        className: 'edui-for-snapscreen',
                        title: title,
                        buttons: [{
                            className: 'edui-okbutton',
                            label: '确认',
                            onclick: function (){
                                dialog.close(true);
                            }
                        }, {
                            className: 'edui-cancelbutton',
                            label: '取消',
                            onclick: function (){
                                dialog.close(false);
                            }
                        }]

                    });
                    dialog.render();
                    editor.ui._dialogs["snapscreenDialog"] = dialog;
                }

            }
            editor.addListener('selectionchange',function(){
                ui.setDisabled( editor.queryCommandState('snapscreen') == -1);
            });
            return ui;
        };



    editorui.fontfamily = function (editor, list, title){
        list = list || editor.options['fontfamily'] || [];
        title = title || editor.options.labelMap['fontfamily'] || '';

        for(var i=0,ci,items=[];ci=list[i++];){

            (function(key,val){
                items.push({
                    label: key,
                    value: val,
                    renderLabelHtml: function (){
                        return '<div class="edui-label %%-label" style="font-family:' +
                            utils.unhtml(this.value.join(',')) + '">' + (this.label || '') + '</div>';
                    }
                });
            })(ci[0],ci[1])
        }
        var ui = new editorui.Combox({
            editor:editor,
            items: items,
            onselect: function (t,index){
                editor.execCommand('FontFamily', this.items[index].value);
            },
            onbuttonclick: function (){
                this.showPopup();
            },
            title: title,
            initValue: title,
            className: 'edui-for-fontfamily',
            indexByValue: function (value){
                if(value){
                    for(var i=0,ci;ci=this.items[i];i++){
                        if(ci.value.join(',').indexOf(value) != -1)
                            return i;
                    }
                }

                return -1;
            }
        });
        editor.addListener('selectionchange', function (type, causeByUi, uiReady){
            if(!uiReady){
                var state = editor.queryCommandState('FontFamily');
                if (state == -1) {
                    ui.setDisabled(true);
                } else {
                    ui.setDisabled(false);
                    var value = editor.queryCommandValue('FontFamily');
                    value && (value = value.replace(/['"]/g,'').split(',')[0]);
                    ui.setValue( value);

                }
            }

        });
        return ui;
    };

    editorui.fontsize = function (editor, list, title){
        title = title || editor.options.labelMap['fontsize'] || '';
        list = list || editor.options['fontsize'] || [];
        var items = [];
        for (var i=0; i<list.length; i++) {
            var size = list[i] + 'px';
            items.push({
                label: size,
                value: size,
                renderLabelHtml: function (){
                    return '<div class="edui-label %%-label" style="line-height:1;font-size:' +
                        this.value + '">' + (this.label || '') + '</div>';
                }
            });
        }
        var ui = new editorui.Combox({
            editor:editor,
            items: items,
            title: title,
            initValue: title,
            onselect: function (t,index){
                editor.execCommand('FontSize', this.items[index].value);
            },
            onbuttonclick: function (){
                this.showPopup();
            },
            className: 'edui-for-fontsize'
        });
        editor.addListener('selectionchange', function (type, causeByUi, uiReady){
            if(!uiReady){
                var state = editor.queryCommandState('FontSize');
                if (state == -1) {
                    ui.setDisabled(true);
                } else {
                    ui.setDisabled(false);
                    ui.setValue(editor.queryCommandValue('FontSize'));
                }
            }

        });
        return ui;
    };

    editorui.paragraph = function (editor, list, title){
        title = title || editor.options.labelMap['paragraph'] || '';
        list = list || editor.options['paragraph'] || [];
        for (var i=0,items = []; i<list.length; i++) {
            var item = list[i].split(':');
            var tag = item[0];
            var label = item[1];
            items.push({
                label: label,
                value: tag,
                renderLabelHtml: function (){
                    return '<div class="edui-label %%-label"><span class="edui-for-' + this.value + '">' + (this.label || '') + '</span></div>';
                }
            });
        }
        var ui = new editorui.Combox({
            editor:editor,
            items: items,
            title: title,
            initValue: title,
            className: 'edui-for-paragraph',
            onselect: function (t,index){
                editor.execCommand('Paragraph', this.items[index].value);
            },
            onbuttonclick: function (){
                this.showPopup();
            }
        });
        editor.addListener('selectionchange', function (type, causeByUi, uiReady){
            if(!uiReady){
                var state = editor.queryCommandState('Paragraph');
                if (state == -1) {
                    ui.setDisabled(true);
                } else {
                    ui.setDisabled(false);
                    var value = editor.queryCommandValue('Paragraph');
                    var index = ui.indexByValue(value);
                    if (index != -1) {
                        ui.setValue(value);
                    } else {
                        ui.setValue(ui.initValue);
                    }
                }
            }

        });
        return ui;
    };
    editorui.customstyle = function(editor,list,title){
        list = list || editor.options['customstyle'];
        title = title || editor.options.labelMap['customstyle'] || '';
        if(!list)
            return;
        for(var i=0,items = [],t;t=list[i++];){
            (function(ti){
                items.push({
                    label: ti.label,
                    value: ti,
                    renderLabelHtml: function (){
                        return '<div class="edui-label %%-label">' +'<'+ ti.tag +' ' + (ti.className?' class="'+ti.className+'"':"")
                            + (ti.style ? ' style="' + ti.style+'"':"") + '>' + ti.label+"<\/"+ti.tag+">"
                            + '</div>';
                    }
                });
            })(t)

        }

        var ui = new editorui.Combox({
            editor:editor,
            items: items,
            title: title,
            initValue:title,
            className: 'edui-for-customstyle',
            onselect: function (t,index){
                editor.execCommand('customstyle', this.items[index].value);
            },
            onbuttonclick: function (){
                this.showPopup();
            },
            indexByValue: function (value){
                for(var i=0,ti;ti=this.items[i++];){
                    if(ti.label == value){
                        return i-1
                    }
                }
                return -1;
            }
        });
        editor.addListener('selectionchange', function (type, causeByUi, uiReady){
            if(!uiReady){
                var state = editor.queryCommandState('customstyle');
                if (state == -1) {
                    ui.setDisabled(true);
                } else {
                    ui.setDisabled(false);
                    var value = editor.queryCommandValue('customstyle');
                    var index = ui.indexByValue(value);
                    if (index != -1) {
                        ui.setValue(value);
                    } else {
                        ui.setValue(ui.initValue);
                    }
                }
            }

        });
        return ui;
    };
    editorui.inserttable = function (editor, iframeUrl, title){
        iframeUrl = iframeUrl || (editor.options.iframeUrlMap||{})['inserttable'] || iframeUrlMap['inserttable'];
        title = title || editor.options.labelMap['inserttable'] || '';
        if(iframeUrl){
            var dialog = new editorui.Dialog({
                iframeUrl: editor.ui.mapUrl(iframeUrl),
                editor: editor,
                className: 'edui-for-inserttable',
                title: title,
                buttons: [{
                    className: 'edui-okbutton',
                    label: '确认',
                    onclick: function (){
                        dialog.close(true);
                    }
                }, {
                    className: 'edui-cancelbutton',
                    label: '取消',
                    onclick: function (){
                        dialog.close(false);
                    }
                }]

            });
            dialog.render();
            editor.ui._dialogs['inserttableDialog'] = dialog;
        }

        var ui = new editorui.TableButton({
            editor:editor,
            title: title,
            className: 'edui-for-inserttable',
            onpicktable: function (t,numCols, numRows){
                editor.execCommand('InsertTable', {numRows:numRows, numCols:numCols,border:1});
            },
            onmore: function (){
                dialog && dialog.open();
            },
            onbuttonclick: function (){
                dialog && dialog.open();
            }
        });
        editor.addListener('selectionchange', function (){
            ui.setDisabled(editor.queryCommandState('inserttable') == -1);
        });
        return ui;
    };

    editorui.lineheight = function (editor, title){
        var val = editor.options.lineheight;
        for(var i=0,ci,items=[];ci = val[i++];){
            items.push({
                label : ci == '1' ? '默认' : ci,
                value: ci,
                onclick:function(){
                    editor.execCommand("lineheight", this.value);
                }
            })
        }
        var ui = new editorui.MenuButton({
            editor:editor,
            className : 'edui-for-lineheight',
            title : title || editor.options.labelMap['lineheight'] || '',
            items :items,
            onbuttonclick: function (){
                var value = editor.queryCommandValue('LineHeight') || this.value;
                editor.execCommand("LineHeight", value);
            }
        });
        editor.addListener('selectionchange', function (){
            var state = editor.queryCommandState('LineHeight');
            if (state == -1) {
                ui.setDisabled(true);
            } else {
                ui.setDisabled(false);
                var value = editor.queryCommandValue('LineHeight');
                value && ui.setValue((value + '').replace(/cm/,''));
                ui.setChecked(state)
            }
        });
        return ui;
    };

    var rowspacings = ['top','bottom'];
    for(var r=0,ri;ri=rowspacings[r++];){
        (function(cmd){
            editorui['rowspacing' + cmd] = function(editor){
                var val = editor.options['rowspacing'+cmd] ;

                for(var i=0,ci,items=[];ci = val[i++];){
                    items.push({
                        label : ci,
                        value: ci,
                        onclick:function(){
                            editor.execCommand("rowspacing", this.value,cmd);
                        }
                    })
                }
                var ui = new editorui.MenuButton({
                    editor:editor,
                    className : 'edui-for-rowspacing'+cmd,
                    title : editor.options.labelMap['rowspacing'+cmd],
                    items :items,
                    onbuttonclick: function (){
                        var value = editor.queryCommandValue('rowspacing',cmd) || this.value;
                        editor.execCommand("rowspacing", value,cmd);
                    }
                });
                editor.addListener('selectionchange', function (){
                    var state = editor.queryCommandState('rowspacing',cmd);
                    if (state == -1) {
                        ui.setDisabled(true);
                    } else {
                        ui.setDisabled(false);
                        var value = editor.queryCommandValue('rowspacing',cmd);
                        value && ui.setValue((value + '').replace(/%/,''));
                        ui.setChecked(state)
                    }
                });
                return ui;
            }
        })(ri)
    }
    var lists = ['insertorderedlist','insertunorderedlist'];
    for(var l = 0,cl;cl = lists[l++]; ){
        (function(cmd){
            editorui[cmd] =function (editor){
                var vals = editor.options[cmd],
                    _onMenuClick = function(){
                        editor.execCommand(cmd, this.value);
                    };
                for(var i=0,items=[],ci;ci=vals[i++];){
                    items.push({
                        label : ci[0],
                        value : ci[1],
                        onclick : _onMenuClick
                    })
                }
                var ui = new editorui.MenuButton({
                    editor:editor,
                    className : 'edui-for-'+cmd,
                    title : editor.options.labelMap[cmd] || '',
                    'items' :items,
                    onbuttonclick: function (){
                        var value = editor.queryCommandValue(cmd) || this.value;
                        editor.execCommand(cmd, value);
                    }
                });
                editor.addListener('selectionchange', function (){
                    var state = editor.queryCommandState(cmd);
                    if (state == -1) {
                        ui.setDisabled(true);
                    } else {
                        ui.setDisabled(false);
                        var value = editor.queryCommandValue(cmd);
                        ui.setValue(value);
                        ui.setChecked(state)
                    }
                });
                return ui;
            };
        })(cl)
    }

    editorui.fullscreen = function (editor, title){
        title = title || editor.options.labelMap['fullscreen'] || '';
        var ui = new editorui.Button({
            className: 'edui-for-fullscreen',
            title: title,
            onclick: function (){
                if (editor.ui) {
                    editor.ui.setFullScreen(!editor.ui.isFullScreen());
                }
                this.setChecked(editor.ui.isFullScreen());
            }
        });
        editor.addListener('selectionchange', function (){
            var state = editor.queryCommandState('fullscreen');
            ui.setDisabled(state == -1);
            ui.setChecked(editor.ui.isFullScreen());
        });
        return ui;
    };
    editorui.emotion = function(editor, iframeUrl, title){
        var ui = new editorui.MultiMenuPop({
            title: title || editor.options.labelMap.emotion || '',
            editor: editor,
            className: 'edui-for-emotion',
            iframeUrl: editor.ui.mapUrl(iframeUrl || (editor.options.iframeUrlMap||{})['emotion'] || iframeUrlMap['emotion'])
        });
        editor.addListener('selectionchange', function (){
            ui.setDisabled(editor.queryCommandState('emotion') == -1)
        });
        return ui;
    };

    editorui.autotypeset = function (editor){
        var ui = new editorui.AutoTypeSetButton({
            editor:editor,
            title: editor.options.labelMap['autotypeset'] || '',
            className: 'edui-for-autotypeset',
            onbuttonclick: function (){
                editor.execCommand('autotypeset')
            }
        });
        editor.addListener('selectionchange', function (){
            ui.setDisabled(editor.queryCommandState('autotypeset') == -1);
        });
        return ui;
    };

})();

(function () {
    var utils = baidu.editor.utils,
        uiUtils = baidu.editor.ui.uiUtils,
        UIBase = baidu.editor.ui.UIBase,
        domUtils = baidu.editor.dom.domUtils;

    function EditorUI( options ) {
        this.initOptions( options );
        this.initEditorUI();
    }

    EditorUI.prototype = {
        uiName: 'editor',
        initEditorUI: function () {
            this.editor.ui = this;
            this._dialogs = {};
            this.initUIBase();
            this._initToolbars();
            var editor = this.editor,
                me = this;

            editor.addListener( 'ready', function () {
                domUtils.on( editor.window, 'scroll', function () {
                    baidu.editor.ui.Popup.postHide();
                } );
                if ( editor.options.elementPathEnabled ) {
                    editor.ui.getDom( 'elementpath' ).innerHTML = '<div class="edui-editor-breadcrumb">path:</div>';
                }
                if ( editor.options.wordCount ) {
                    editor.ui.getDom( 'wordcount' ).innerHTML = '字数统计';
                    editor.addListener('keyup',function(type,evt){
                        var keyCode = evt.keyCode || evt.which;
                        if(keyCode == 32){
                            me._wordCount();
                        }
                    });
                }
                if(!editor.options.elementPathEnabled && !editor.options.wordCount){
                    editor.ui.getDom( 'elementpath' ).style.display="none";
                    editor.ui.getDom( 'wordcount' ).style.display="none";
                }

                if(!editor.selection.isFocus())return;
                editor.fireEvent( 'selectionchange', false, true );


            } );

            editor.addListener( 'mousedown', function ( t, evt ) {
                var el = evt.target || evt.srcElement;
                baidu.editor.ui.Popup.postHide( el );
            } );
            editor.addListener( 'contextmenu', function ( t, evt ) {
                baidu.editor.ui.Popup.postHide();
            } );
            editor.addListener( 'selectionchange', function () {
                if ( editor.options.elementPathEnabled ) {
                    me[(editor.queryCommandState('elementpath') == -1 ? 'dis':'en') + 'ableElementPath']()
                }
                if ( editor.options.wordCount ) {
                    me[(editor.queryCommandState('wordcount') == -1 ? 'dis':'en') + 'ableWordCount']()
                }

            } );
            var popup = new baidu.editor.ui.Popup( {
                editor:editor,
                content: '',
                className: 'edui-bubble',
                _onEditButtonClick: function () {
                    this.hide();
                    editor.ui._dialogs.linkDialog.open();
                },
                _onImgEditButtonClick: function (name) {
                    this.hide();
                    editor.ui._dialogs[name]  && editor.ui._dialogs[name].open();

                },
                _onImgSetFloat: function( value ) {
                    this.hide();
                    editor.execCommand( "imagefloat", value );

                },
                _setIframeAlign: function( value ) {
                    var frame = popup.anchorEl;
                    var newFrame = frame.cloneNode( true );
                    switch ( value ) {
                        case -2:
                            newFrame.setAttribute( "align", "" );
                            break;
                        case -1:
                            newFrame.setAttribute( "align", "left" );
                            break;
                        case 1:
                            newFrame.setAttribute( "align", "right" );
                            break;
                        case 2:
                            newFrame.setAttribute( "align", "middle" );
                            break;
                    }
                    frame.parentNode.insertBefore( newFrame, frame );
                    domUtils.remove( frame );
                    popup.anchorEl = newFrame;
                    popup.showAnchor( popup.anchorEl );
                },
                _updateIframe: function() {
                    editor._iframe = popup.anchorEl;
                    editor.ui._dialogs.insertframeDialog.open();
                    popup.hide();
                },
                _onRemoveButtonClick: function (cmdName) {
                    editor.execCommand( cmdName );
                    this.hide();
                },
                queryAutoHide: function ( el ) {
                    if ( el && el.ownerDocument == editor.document ) {
                        if ( el.tagName.toLowerCase() == 'img' || domUtils.findParentByTagName( el, 'a', true ) ) {
                            return el !== popup.anchorEl;
                        }
                    }
                    return baidu.editor.ui.Popup.prototype.queryAutoHide.call( this, el );
                }
            } );
            popup.render();
            if(editor.options.imagePopup){
                editor.addListener( 'mouseover', function( t, evt ) {
                    evt = evt || window.event;
                    var el = evt.target || evt.srcElement;
                    if (  editor.ui._dialogs.insertframeDialog && /iframe/ig.test( el.tagName )  ) {
                        var html = popup.formatHtml(
                            '<nobr>属性: <span onclick=$$._setIframeAlign(-2) class="edui-clickable">默认</span>&nbsp;&nbsp;<span onclick=$$._setIframeAlign(-1) class="edui-clickable">左对齐</span>&nbsp;&nbsp;<span onclick=$$._setIframeAlign(1) class="edui-clickable">右对齐</span>&nbsp;&nbsp;' +
                                '<span onclick=$$._setIframeAlign(2) class="edui-clickable">居中</span>' +
                                ' <span onclick="$$._updateIframe( this);" class="edui-clickable">修改</span></nobr>' );
                        if ( html ) {
                            popup.getDom( 'content' ).innerHTML = html;
                            popup.anchorEl = el;
                            popup.showAnchor( popup.anchorEl );
                        } else {
                            popup.hide();
                        }
                    }
                } );
                editor.addListener( 'selectionchange', function ( t, causeByUi ) {
                    if ( !causeByUi ) return;
                    var html =  '',
                        img = editor.selection.getRange().getClosedNode(),
                        dialogs = editor.ui._dialogs;
                    if ( img && img.tagName == 'IMG' ) {
                        var dialogName = 'insertimageDialog';
                        if ( img.className.indexOf( "edui-faked-video" ) != -1 ) {
                            dialogName = "insertvideoDialog"
                        }
                        if(img.className.indexOf( "edui-faked-webapp" ) != -1){
                            dialogName = "webappDialog"
                        }
                        if ( img.src.indexOf( "http://api.map.baidu.com" ) != -1 ) {
                            dialogName = "mapDialog"
                        }
                        if ( img.src.indexOf( "http://maps.google.com/maps/api/staticmap" ) != -1 ) {
                            dialogName = "gmapDialog"
                        }
                        if ( img.getAttribute( "anchorname" ) ) {
                            dialogName = "anchorDialog";
                            html = popup.formatHtml(
                                '<nobr>属性: <span onclick=$$._onImgEditButtonClick("anchorDialog") class="edui-clickable">修改</span>&nbsp;&nbsp;' +
                                '<span onclick=$$._onRemoveButtonClick(\'anchor\') class="edui-clickable">删除</span></nobr>' );
                        }
                        if( img.getAttribute("word_img")){
                            editor.word_img = [img.getAttribute("word_img")];
                            dialogName = "wordimageDialog"
                        }
                        if(!dialogs[dialogName]){
                            return;
                        }
                        !html && (html = popup.formatHtml(
                            '<nobr>属性: <span onclick=$$._onImgSetFloat("none") class="edui-clickable">默认</span>&nbsp;&nbsp;' +
                                '<span onclick=$$._onImgSetFloat("left") class="edui-clickable">居左</span>&nbsp;&nbsp;' +
                                '<span onclick=$$._onImgSetFloat("right") class="edui-clickable">居右</span>&nbsp;&nbsp;' +
                                '<span onclick=$$._onImgSetFloat("center") class="edui-clickable">居中</span>&nbsp;&nbsp;' +
                                '<span onclick="$$._onImgEditButtonClick(\''+dialogName+'\');" class="edui-clickable">修改</span></nobr>' ))

                    }
                    if(editor.ui._dialogs.linkDialog){
                        var link = domUtils.findParentByTagName( editor.selection.getStart(), "a", true );
                        var url;
                        if ( link  && (url = (link.getAttribute( 'data_ue_src' ) || link.getAttribute( 'href', 2 )))  ) {
                            var txt = url;
                            if ( url.length > 30 ) {
                                txt = url.substring( 0, 20 ) + "...";
                            }
                            if ( html ) {
                                html += '<div style="height:5px;"></div>'
                            }
                            html += popup.formatHtml(
                                '<nobr>链接: <a target="_blank" href="' + url + '" title="' + url + '" >' + txt + '</a>' +
                                    ' <span class="edui-clickable" onclick="$$._onEditButtonClick();">修改</span>' +
                                    ' <span class="edui-clickable" onclick="$$._onRemoveButtonClick(\'unlink\');"> 清除</span></nobr>' );
                            popup.showAnchor( link );
                        }
                    }

                    if ( html ) {
                        popup.getDom( 'content' ).innerHTML = html;
                        popup.anchorEl = img || link;
                        popup.showAnchor( popup.anchorEl );
                    } else {
                        popup.hide();
                    }
                } );
            }

        },
        _initToolbars: function () {
            var editor = this.editor;
            var toolbars = this.toolbars || [];
            var toolbarUis = [];
            for ( var i = 0; i < toolbars.length; i++ ) {
                var toolbar = toolbars[i];
                var toolbarUi = new baidu.editor.ui.Toolbar();
                for ( var j = 0; j < toolbar.length; j++ ) {
                    var toolbarItem = toolbar[j].toLowerCase();
                    var toolbarItemUi = null;
                    if ( typeof toolbarItem == 'string' ) {
                        if ( toolbarItem == '|' ) {
                            toolbarItem = 'Separator';
                        }

                        if ( baidu.editor.ui[toolbarItem] ) {
                            toolbarItemUi = new baidu.editor.ui[toolbarItem]( editor );
                        }
                        if ( toolbarItem == 'FullScreen' ) {
                            if ( toolbarUis && toolbarUis[0] ) {
                                toolbarUis[0].items.splice( 0, 0, toolbarItemUi );
                            } else {
                                toolbarItemUi && toolbarUi.items.splice( 0, 0, toolbarItemUi );
                            }

                            continue;


                        }
                    } else {
                        toolbarItemUi = toolbarItem;
                    }
                    if ( toolbarItemUi ) {
                        toolbarUi.add( toolbarItemUi );
                    }
                }
                toolbarUis[i] = toolbarUi;
            }
            this.toolbars = toolbarUis;
        },
        getHtmlTpl: function () {
            return '<div id="##" class="%%">' +
                '<div id="##_toolbarbox" class="%%-toolbarbox">' +
                (this.toolbars.length  ?
                '<div id="##_toolbarboxouter" class="%%-toolbarboxouter"><div class="%%-toolbarboxinner">' +
                this.renderToolbarBoxHtml() +
                '</div></div>':'') +
                '<div id="##_toolbarmsg" class="%%-toolbarmsg" style="display:none;">' +
                '<div id = "##_upload_dialog" class="%%-toolbarmsg-upload" onclick="$$.showWordImageDialog();">点此上传</div>' +
                '<div class="%%-toolbarmsg-close" onclick="$$.hideToolbarMsg();">x</div>' +
                '<div id="##_toolbarmsg_label" class="%%-toolbarmsg-label"></div>' +
                '<div style="height:0;overflow:hidden;clear:both;"></div>' +
                '</div>' +
                '</div>' +
                '<div id="##_iframeholder" class="%%-iframeholder"></div>' +
                '<div id="##_bottombar" class="%%-bottomContainer"><table><tr>' +
                '<td id="##_elementpath" class="%%-bottombar"></td>' +
                '<td id="##_wordcount" class="%%-wordcount"></td>' +
                '</tr></table></div>' +
                '</div>';
        },
        showWordImageDialog:function() {
            this.editor.execCommand( "wordimage", "word_img" );
            this._dialogs['wordimageDialog'].open();
        },
        renderToolbarBoxHtml: function () {
            var buff = [];
            for ( var i = 0; i < this.toolbars.length; i++ ) {
                buff.push( this.toolbars[i].renderHtml() );
            }
            return buff.join( '' );
        },
        setFullScreen: function ( fullscreen ) {

            if ( this._fullscreen != fullscreen ) {
                this._fullscreen = fullscreen;
                this.editor.fireEvent( 'beforefullscreenchange', fullscreen );
                var editor = this.editor;

                if ( baidu.editor.browser.gecko ) {
                    var bk = editor.selection.getRange().createBookmark();
                }



                if ( fullscreen ) {

                    this._bakHtmlOverflow = document.documentElement.style.overflow;
                    this._bakBodyOverflow = document.body.style.overflow;
                    this._bakAutoHeight = this.editor.autoHeightEnabled;
                    this._bakScrollTop = Math.max( document.documentElement.scrollTop, document.body.scrollTop );
                    if ( this._bakAutoHeight ) {
                        editor.autoHeightEnabled = false;
                        this.editor.disableAutoHeight();
                    }

                    document.documentElement.style.overflow = 'hidden';
                    document.body.style.overflow = 'hidden';

                    this._bakCssText = this.getDom().style.cssText;
                    this._bakCssText1 = this.getDom( 'iframeholder' ).style.cssText;
                    this._updateFullScreen();

                } else {

                    this.getDom().style.cssText = this._bakCssText;
                    this.getDom( 'iframeholder' ).style.cssText = this._bakCssText1;
                    if ( this._bakAutoHeight ) {
                        editor.autoHeightEnabled = true;
                        this.editor.enableAutoHeight();
                    }
                    document.documentElement.style.overflow = this._bakHtmlOverflow;
                    document.body.style.overflow = this._bakBodyOverflow;
                    window.scrollTo( 0, this._bakScrollTop );
                }
                if ( baidu.editor.browser.gecko ) {

                    var input = document.createElement( 'input' );

                    document.body.appendChild( input );

                    editor.body.contentEditable = false;
                    setTimeout( function() {

                        input.focus();
                        setTimeout( function() {
                            editor.body.contentEditable = true;
                            editor.selection.getRange().moveToBookmark( bk ).select( true );
                            baidu.editor.dom.domUtils.remove( input );

                            fullscreen && window.scroll( 0, 0 );

                        } )

                    } )
                }

                this.editor.fireEvent( 'fullscreenchanged', fullscreen );
                this.triggerLayout();
            }
        },
        _wordCount:function() {
            var wdcount = this.getDom( 'wordcount' );
            if ( !this.editor.options.wordCount ) {
                wdcount.style.display = "none";
                return;
            }
            wdcount.innerHTML = this.editor.queryCommandValue( "wordcount" );
        },
        disableWordCount: function () {
            var w = this.getDom( 'wordcount' );
            w.innerHTML = '';
            w.style.display = 'none';
            this.wordcount = false;

        },
        enableWordCount: function () {
            var w = this.getDom( 'wordcount' );
            w.style.display = '';
            this.wordcount = true;
            this._wordCount();
        },
        _updateFullScreen: function () {
            if ( this._fullscreen ) {
                var vpRect = uiUtils.getViewportRect();
                this.getDom().style.cssText = 'border:0;position:absolute;left:0;top:0;width:' + vpRect.width + 'px;height:' + vpRect.height + 'px;z-index:' + (this.getDom().style.zIndex * 1 + 100);
                uiUtils.setViewportOffset( this.getDom(), { left: 0, top: 0 } );
                this.editor.setHeight( vpRect.height - this.getDom( 'toolbarbox' ).offsetHeight - this.getDom( 'bottombar' ).offsetHeight );

            }
        },
        _updateElementPath: function () {
            var bottom = this.getDom( 'elementpath' ),list;
            if ( this.elementPathEnabled && (list = this.editor.queryCommandValue( 'elementpath' ))) {

                var buff = [];
                for ( var i = 0,ci; ci = list[i]; i++ ) {
                    buff[i] = this.formatHtml( '<span unselectable="on" onclick="$$.editor.execCommand(&quot;elementpath&quot;, &quot;' + i + '&quot;);">' + ci + '</span>' );
                }
                bottom.innerHTML = '<div class="edui-editor-breadcrumb" onmousedown="return false;">path: ' + buff.join( ' &gt; ' ) + '</div>';

            } else {
                bottom.style.display = 'none'
            }
        },
        disableElementPath: function () {
            var bottom = this.getDom( 'elementpath' );
            bottom.innerHTML = '';
            bottom.style.display = 'none';
            this.elementPathEnabled = false;

        },
        enableElementPath: function () {
            var bottom = this.getDom( 'elementpath' );
            bottom.style.display = '';
            this.elementPathEnabled = true;
            this._updateElementPath();
        },
        isFullScreen: function () {
            return this._fullscreen;
        },
        postRender: function () {
            UIBase.prototype.postRender.call( this );
            for ( var i = 0; i < this.toolbars.length; i++ ) {
                this.toolbars[i].postRender();
            }
            var me = this;
            var timerId,
                domUtils = baidu.editor.dom.domUtils,
                updateFullScreenTime = function() {
                    clearTimeout( timerId );
                    timerId = setTimeout( function () {
                        me._updateFullScreen();
                    } );
                };
            domUtils.on( window, 'resize', updateFullScreenTime );

            me.addListener( 'destroy', function() {
                domUtils.un( window, 'resize', updateFullScreenTime );
                clearTimeout( timerId );
            } )
        },
        showToolbarMsg: function ( msg, flag ) {
            this.getDom( 'toolbarmsg_label' ).innerHTML = msg;
            this.getDom( 'toolbarmsg' ).style.display = '';
            if ( !flag ) {
                var w = this.getDom( 'upload_dialog' );
                w.style.display = 'none';
            }
        },
        hideToolbarMsg: function () {
            this.getDom( 'toolbarmsg' ).style.display = 'none';
        },
        mapUrl: function ( url ) {
            return url ? url.replace( '~/', this.editor.options.UEDITOR_HOME_URL || '' ) : ''
        },
        triggerLayout: function () {
            var dom = this.getDom();
            if ( dom.style.zoom == '1' ) {
                dom.style.zoom = '100%';
            } else {
                dom.style.zoom = '1';
            }
        }
    };
    utils.inherits( EditorUI, baidu.editor.ui.UIBase );

    baidu.editor.ui.Editor = function ( options ) {

        var editor = new baidu.editor.Editor( options );
        editor.options.editor = editor;



        var oldRender = editor.render;
        editor.render = function ( holder ) {
            utils.domReady(function(){
                new EditorUI( editor.options );
                if ( holder ) {
                    if ( holder.constructor === String ) {
                        holder = document.getElementById( holder );
                    }
                    holder && holder.getAttribute( 'name' ) && ( editor.options.textarea = holder.getAttribute( 'name' ));
                    if ( holder && /script|textarea/ig.test( holder.tagName ) ) {
                        var newDiv = document.createElement( 'div' );
                        holder.parentNode.insertBefore( newDiv, holder );
                        var cont = holder.value || holder.innerHTML;
                        editor.options.initialContent = /^[\t\r\n ]*$/.test(cont) ? editor.options.initialContent :
                            cont.replace(/>[\n\r\t]+([ ]{4})+/g,'>')
                                .replace(/[\n\r\t]+([ ]{4})+</g,'<')
                                .replace(/>[\n\r\t]+</g,'><');

                        holder.id && (newDiv.id = holder.id);

                        holder.className && (newDiv.className = holder.className);
                        holder.style.cssText && (newDiv.style.cssText = holder.style.cssText);
                        if(/textarea/i.test(holder.tagName)){
                            editor.textarea = holder;
                            editor.textarea.style.display = 'none'
                        }else{
                            holder.parentNode.removeChild( holder )
                        }
                        holder = newDiv;
                        holder.innerHTML = '';
                    }

                }

                editor.ui.render( holder );
                var iframeholder = editor.ui.getDom( 'iframeholder' );
                editor.container = editor.ui.getDom();
                editor.container.style.zIndex = editor.options.zIndex;
                oldRender.call( editor, iframeholder );

            })
        };
        return editor;
    };
})();
(function(){
    var utils = baidu.editor.utils,
        Popup = baidu.editor.ui.Popup,
        SplitButton = baidu.editor.ui.SplitButton,
        MultiMenuPop = baidu.editor.ui.MultiMenuPop = function(options){
            this.initOptions(options);
            this.initMultiMenu();
        };

    MultiMenuPop.prototype = {
        initMultiMenu: function (){
            var me = this;
            this.popup = new Popup({
                content: '',
                editor : me.editor,
                iframe_rendered: false,
                onshow: function (){
                    if (!this.iframe_rendered) {
                        this.iframe_rendered = true;
                        this.getDom('content').innerHTML = '<iframe id="'+me.id+'_iframe" src="'+ me.iframeUrl +'" frameborder="0"></iframe>';
                        me.editor.container.style.zIndex && (this.getDom().style.zIndex = me.editor.container.style.zIndex * 1 + 1);
                    }
                }
            });
            this.onbuttonclick = function(){
                this.showPopup();
            };
            this.initSplitButton();
        }

    };

    utils.inherits(MultiMenuPop, SplitButton);
})();


UE.commands['preview'] = {
    execCommand : function(){
        var me = this,
            w = window.open(CONFIG['misc_path'] + 'js/plug/ueditor/preview.html', "_blank", ""),
            css = me.document.getElementById("syntaxhighlighter_css"),
            js = document.getElementById("syntaxhighlighter_js"),
            style = "<style type='text/css'>"+(me.document.getElementById("editorinitialstyle")&&me.document.getElementById("editorinitialstyle").innerHTML)+"</style>",
            cont = me.getContent();
            cont = (cont != UEDITOR_CONFIG.initialContent) ? cont : '';
        if(browser.ie){
            cont = cont.replace(/<\s*br\s*\/?\s*>/gi,'<br/><br/>')
        }

        setTimeout(function(){
            var d = w.document;
            d.open();
            d.write('<html><head>'+style+'<link rel="stylesheet" type="text/css" href="'+utils.unhtml( me.options.iframeCssUrl ) + '"/>'+
                    (css ? '<link rel="stylesheet" type="text/css" href="' + css.href + '"/>' : '')

                + (css&&js ? ' <script type="text/javascript" charset="utf-8" src="'+js.src+'"></script>':'')
                +'<title></title></head><body >' +
                cont +
                (css && js ? '<script type="text/javascript">'+(baidu.editor.browser.ie ? 'window.onload = function(){SyntaxHighlighter.all()};' : 'SyntaxHighlighter.all();')+
                    'setTimeout(function(){' +
                    'for(var i=0,di;di=SyntaxHighlighter.highlightContainers[i++];){' +
                        'var tds = di.getElementsByTagName("td");' +
                        'for(var j=0,li,ri;li=tds[0].childNodes[j];j++){' +
                            'ri = tds[1].firstChild.childNodes[j];' +
                            'ri.style.height = li.style.height = ri.offsetHeight + "px";' +
                        '}' +
                    '}},100)</script>':'') +
                         '</body></html>');
            d.close();
         },300);
    },
    notNeedUndo : 1
};


UE.plugins['basestyle'] = function(){
    var basestyles = {
            'bold':['strong','b'],
            'italic':['em','i'],
            'subscript':['sub'],
            'superscript':['sup']
        },
        getObj = function(editor,tagNames){
            var path = editor.selection.getStartElementPath();
            return utils.findNode(path,tagNames);
        },
        me = this;
    for ( var style in basestyles ) {
        (function( cmd, tagNames ) {
            me.commands[cmd] = {
                execCommand : function( cmdName ) {

                    var range = new dom.Range(me.document),obj = '';
                    if(me.currentSelectedArr && me.currentSelectedArr.length > 0){
                        for(var i=0,ci;ci=me.currentSelectedArr[i++];){
                            if(ci.style.display != 'none'){
                                range.selectNodeContents(ci).select();
                                !obj && (obj = getObj(this,tagNames));
                                if(cmdName == 'superscript' || cmdName == 'subscript'){
                                    
                                    if(!obj || obj.tagName.toLowerCase() != cmdName)
                                        range.removeInlineStyle(['sub','sup'])

                                }
                                obj ? range.removeInlineStyle( tagNames ) : range.applyInlineStyle( tagNames[0] )
                            }

                        }
                        range.selectNodeContents(me.currentSelectedArr[0]).select();
                    }else{
                        range = me.selection.getRange();
                        obj = getObj(this,tagNames);

                        if ( range.collapsed ) {
                            if ( obj ) {
                                var tmpText =  me.document.createTextNode('');
                                range.insertNode( tmpText ).removeInlineStyle( tagNames );

                                range.setStartBefore(tmpText);
                                domUtils.remove(tmpText);
                            } else {
                                
                                var tmpNode = range.document.createElement( tagNames[0] );
                                if(cmdName == 'superscript' || cmdName == 'subscript'){
                                    tmpText = me.document.createTextNode('');
                                    range.insertNode(tmpText)
                                        .removeInlineStyle(['sub','sup'])
                                        .setStartBefore(tmpText)
                                        .collapse(true);

                                }
                                range.insertNode( tmpNode ).setStart( tmpNode, 0 );
                                


                            }
                            range.collapse( true )

                        } else {
                            if(cmdName == 'superscript' || cmdName == 'subscript'){
                                if(!obj || obj.tagName.toLowerCase() != cmdName)
                                    range.removeInlineStyle(['sub','sup'])

                            }
                            obj ? range.removeInlineStyle( tagNames ) : range.applyInlineStyle( tagNames[0] )
                        }

                        range.select();
                        
                    }

                    return true;
                },
                queryCommandState : function() {
                   if(this.highlight){
                       return -1;
                   }
                   return getObj(this,tagNames) ? 1 : 0;
                }
            }
        })( style, basestyles[style] );

    }
};



UE.plugins['removeformat'] = function(){
    var me = this;
    me.setOpt({
       'removeFormatTags': 'b,big,code,del,dfn,em,font,i,ins,kbd,q,samp,small,span,strike,strong,sub,sup,tt,u,var',
       'removeFormatAttributes':'class,style,lang,width,height,align,hspace,valign'
    });
    me.commands['removeformat'] = {
        execCommand : function( cmdName, tags, style, attrs,notIncludeA ) {

            var tagReg = new RegExp( '^(?:' + (tags || this.options.removeFormatTags).replace( /,/g, '|' ) + ')$', 'i' ) ,
                removeFormatAttributes = style ? [] : (attrs || this.options.removeFormatAttributes).split( ',' ),
                range = new dom.Range( this.document ),
                bookmark,node,parent,
                filter = function( node ) {
                    return node.nodeType == 1;
                };

            function isRedundantSpan (node) {
                if (node.nodeType == 3 || node.tagName.toLowerCase() != 'span')
                    return 0;
                if (browser.ie) {
                    var attrs = node.attributes;
                    if ( attrs.length ) {
                        for ( var i = 0,l = attrs.length; i<l; i++ ) {
                            if ( attrs[i].specified ) {
                                return 0;
                            }
                        }
                        return 1;
                    }
                }
                return !node.attributes.length
            }
            function doRemove( range ) {

                var bookmark1 = range.createBookmark();
                if ( range.collapsed ) {
                    range.enlarge( true );
                }
                if(!notIncludeA){
                    var aNode = domUtils.findParentByTagName(range.startContainer,'a',true);
                    if(aNode){
                        range.setStartBefore(aNode)
                    }

                    aNode = domUtils.findParentByTagName(range.endContainer,'a',true);
                    if(aNode){
                        range.setEndAfter(aNode)
                    }

                }


                bookmark = range.createBookmark();

                node = bookmark.start;
                while ( (parent = node.parentNode) && !domUtils.isBlockElm( parent ) ) {
                    domUtils.breakParent( node, parent );

                    domUtils.clearEmptySibling( node );
                }
                if ( bookmark.end ) {
                    node = bookmark.end;
                    while ( (parent = node.parentNode) && !domUtils.isBlockElm( parent ) ) {
                        domUtils.breakParent( node, parent );
                        domUtils.clearEmptySibling( node );
                    }
                    var current = domUtils.getNextDomNode( bookmark.start, false, filter ),
                        next;
                    while ( current ) {
                        if ( current == bookmark.end ) {
                            break;
                        }

                        next = domUtils.getNextDomNode( current, true, filter );

                        if ( !dtd.$empty[current.tagName.toLowerCase()] && !domUtils.isBookmarkNode( current ) ) {
                            if ( tagReg.test( current.tagName ) ) {
                                if ( style ) {
                                    domUtils.removeStyle( current, style );
                                    if ( isRedundantSpan( current ) && style != 'text-decoration')
                                        domUtils.remove( current, true );
                                } else {
                                    domUtils.remove( current, true )
                                }
                            } else {
                                if(!dtd.$tableContent[current.tagName] && !dtd.$list[current.tagName]){
                                    domUtils.removeAttributes( current, removeFormatAttributes );
                                    if ( isRedundantSpan( current ) )
                                        domUtils.remove( current, true );
                                }

                            }
                        }
                        current = next;
                    }
                }
                var pN = bookmark.start.parentNode;
                if(domUtils.isBlockElm(pN) && !dtd.$tableContent[pN.tagName] && !dtd.$list[pN.tagName]){
                    domUtils.removeAttributes(  pN,removeFormatAttributes );
                }
                pN = bookmark.end.parentNode;
                if(bookmark.end && domUtils.isBlockElm(pN) && !dtd.$tableContent[pN.tagName]&& !dtd.$list[pN.tagName]){
                    domUtils.removeAttributes(  pN,removeFormatAttributes );
                }
                range.moveToBookmark( bookmark ).moveToBookmark(bookmark1);
                var node = range.startContainer,
                    tmp,
                    collapsed = range.collapsed;
                while(node.nodeType == 1 && domUtils.isEmptyNode(node) && dtd.$removeEmpty[node.tagName]){
                    tmp = node.parentNode;
                    range.setStartBefore(node);
                    if(range.startContainer === range.endContainer){
                        range.endOffset--;
                    }
                    domUtils.remove(node);
                    node = tmp;
                }

                if(!collapsed){
                    node = range.endContainer;
                    while(node.nodeType == 1 && domUtils.isEmptyNode(node) && dtd.$removeEmpty[node.tagName]){
                        tmp = node.parentNode;
                        range.setEndBefore(node);
                        domUtils.remove(node);

                        node = tmp;
                    }


                }
            }

            if ( this.currentSelectedArr && this.currentSelectedArr.length ) {
                for ( var i = 0,ci; ci = this.currentSelectedArr[i++]; ) {
                    range.selectNodeContents( ci );
                    doRemove( range );
                }
                range.selectNodeContents( this.currentSelectedArr[0] ).select();
            } else {

                range = this.selection.getRange();
                doRemove( range );
                range.select();
            }
        },
        queryCommandState : function(){
            return this.highlight ? -1 :0;
        }

    };

};


UE.plugins['font'] = function() {
    var me = this,
        fonts = {
            'forecolor':'color',
            'backcolor':'background-color',
            'fontsize':'font-size',
            'fontfamily':'font-family',
            'underline':'text-decoration',
            'strikethrough':'text-decoration'
        };
    me.setOpt({
        'fontfamily':[
            ['宋体',['宋体', 'SimSun']],
            ['楷体',['楷体', '楷体_GB2312', 'SimKai']],
            ['黑体',['黑体', 'SimHei']],
            ['隶书',['隶书', 'SimLi']],
            ['andale mono',['andale mono']],
            ['arial',['arial', 'helvetica', 'sans-serif']],
            ['arial black',['arial black', 'avant garde']],
            ['comic sans ms',['comic sans ms']],
            ['impact',['impact', 'chicago']],
            ['times new roman',['times new roman']]
        ],
        'fontsize':[10, 11, 12, 14, 16, 18, 20, 24, 36]
    });

    for ( var p in fonts ) {
        (function( cmd, style ) {
            UE.commands[cmd] = {
                execCommand : function( cmdName, value ) {
                    value = value || (this.queryCommandState(cmdName) ? 'none' : cmdName == 'underline' ? 'underline' : 'line-through');
                    var me = this,
                        range = this.selection.getRange(),
                        text;

                    if ( value == 'default' ) {

                        if(range.collapsed){
                            text = me.document.createTextNode('font');
                            range.insertNode(text).select()

                        }
                        me.execCommand( 'removeFormat', 'span,a', style);
                        if(text){
                            range.setStartBefore(text).setCursor();
                            domUtils.remove(text)
                        }


                    } else {
                        if(me.currentSelectedArr && me.currentSelectedArr.length > 0){
                            for(var i=0,ci;ci=me.currentSelectedArr[i++];){
                                range.selectNodeContents(ci);
                                range.applyInlineStyle( 'span', {'style':style + ':' + value} );

                            }
                            range.selectNodeContents(this.currentSelectedArr[0]).select();
                        }else{
                            if ( !range.collapsed ) {
                                if((cmd == 'underline'||cmd=='strikethrough') && me.queryCommandValue(cmd)){
                                     me.execCommand( 'removeFormat', 'span,a', style );
                                }
                                range = me.selection.getRange();

                                range.applyInlineStyle( 'span', {'style':style + ':' + value} ).select();
                            } else {

                                var span = domUtils.findParentByTagName(range.startContainer,'span',true);
                                text = me.document.createTextNode('font');
                                if(span && !span.children.length && !span[browser.ie ? 'innerText':'textContent'].replace(fillCharReg,'').length){
                                    range.insertNode(text);
                                     if(cmd == 'underline'||cmd=='strikethrough'){
                                         range.selectNode(text).select();
                                         me.execCommand( 'removeFormat','span,a', style, null );

                                         span = domUtils.findParentByTagName(text,'span',true);
                                         range.setStartBefore(text)

                                    }
                                    span.style.cssText = span.style.cssText +  ';' + style + ':' + value;
                                    range.collapse(true).select();


                                }else{


                                    range.insertNode(text);
                                    range.selectNode(text).select();
                                    span = range.document.createElement( 'span' );

                                    if(cmd == 'underline'||cmd=='strikethrough'){
                                        if(domUtils.findParentByTagName(text,'a',true)){
                                            range.setStartBefore(text).setCursor();
                                             domUtils.remove(text);
                                             return;
                                         }
                                         me.execCommand( 'removeFormat','span,a', style );
                                    }

                                    span.style.cssText = style + ':' + value;


                                    text.parentNode.insertBefore(span,text);
                                    if(!browser.ie || browser.ie && browser.version == 9){
                                        var spanParent = span.parentNode;
                                        while(!domUtils.isBlockElm(spanParent)){
                                            if(spanParent.tagName == 'SPAN'){
                                                span.style.cssText = spanParent.style.cssText + span.style.cssText;
                                            }
                                            spanParent = spanParent.parentNode;
                                        }
                                    }



                                    range.setStart(span,0).setCursor();


                                }
                                domUtils.remove(text)
                            }
                        }

                    }
                    return true;
                },
                queryCommandValue : function (cmdName) {
                    var startNode = this.selection.getStart();
                    if(cmdName == 'underline'||cmdName=='strikethrough' ){
                        var tmpNode = startNode,value;
                        while(tmpNode && !domUtils.isBlockElm(tmpNode) && !domUtils.isBody(tmpNode)){
                            if(tmpNode.nodeType == 1){
                                value = domUtils.getComputedStyle( tmpNode, style );

                                if(value != 'none'){
                                    return value;
                                }
                            }

                            tmpNode = tmpNode.parentNode;
                        }
                        return 'none'
                    }
                    return  domUtils.getComputedStyle( startNode, style );
                },
                queryCommandState : function(cmdName){
                    if(this.highlight){
                       return -1;
                   }
                    if(!(cmdName == 'underline'||cmdName=='strikethrough'))
                        return 0;
                    return this.queryCommandValue(cmdName) == (cmdName == 'underline' ? 'underline' : 'line-through')
                }
            }
        })( p, fonts[p] );
    }


};

UE.plugins['paragraph'] = function() {
    var me = this,
        block = domUtils.isBlockElm,
        notExchange = ['TD','LI','PRE'],

        doParagraph = function(range,style,attrs,sourceCmdName){
            var bookmark = range.createBookmark(),
                filterFn = function( node ) {
                    return   node.nodeType == 1 ? node.tagName.toLowerCase() != 'br' &&  !domUtils.isBookmarkNode(node) : !domUtils.isWhitespace( node )
                },
                para;

            range.enlarge( true );
            var bookmark2 = range.createBookmark(),
                current = domUtils.getNextDomNode( bookmark2.start, false, filterFn ),
                tmpRange = range.cloneRange(),
                tmpNode;
            while ( current && !(domUtils.getPosition( current, bookmark2.end ) & domUtils.POSITION_FOLLOWING) ) {
                if ( current.nodeType == 3 || !block( current ) ) {
                    tmpRange.setStartBefore( current );
                    while ( current && current !== bookmark2.end && !block( current ) ) {
                        tmpNode = current;
                        current = domUtils.getNextDomNode( current, false, null, function( node ) {
                            return !block( node )
                        } );
                    }
                    tmpRange.setEndAfter( tmpNode );
                    
                    para = range.document.createElement( style );
                    if(attrs){
                        domUtils.setAttributes(para,attrs);
                        if(sourceCmdName && sourceCmdName == 'customstyle' && attrs.style)
                            para.style.cssText = attrs.style;
                    }
                    para.appendChild( tmpRange.extractContents() );
                    if(domUtils.isEmptyNode(para)){
                        domUtils.fillChar(range.document,para);
                        
                    }

                    tmpRange.insertNode( para );

                    var parent = para.parentNode;
                    if ( block( parent ) && !domUtils.isBody( para.parentNode ) && utils.indexOf(notExchange,parent.tagName)==-1) {
                        if(!(sourceCmdName && sourceCmdName == 'customstyle')){
                            parent.getAttribute('dir') && para.setAttribute('dir',parent.getAttribute('dir'));
                            parent.style.cssText && (para.style.cssText = parent.style.cssText + ';' + para.style.cssText);
                            parent.style.textAlign && !para.style.textAlign && (para.style.textAlign = parent.style.textAlign);
                            parent.style.textIndent && !para.style.textIndent && (para.style.textIndent = parent.style.textIndent);
                            parent.style.padding && !para.style.padding && (para.style.padding = parent.style.padding);
                        }
                        if(attrs && /h\d/i.test(parent.tagName) && !/h\d/i.test(para.tagName) ){
                            domUtils.setAttributes(parent,attrs);
                            if(sourceCmdName && sourceCmdName == 'customstyle' && attrs.style)
                                parent.style.cssText = attrs.style;
                            domUtils.remove(para,true);
                            para = parent;
                        }else
                            domUtils.remove( para.parentNode, true );

                    }
                    if(  utils.indexOf(notExchange,parent.tagName)!=-1){
                        current = parent;
                    }else{
                       current = para;
                    }


                    current = domUtils.getNextDomNode( current, false, filterFn );
                } else {
                    current = domUtils.getNextDomNode( current, true, filterFn );
                }
            }
            return range.moveToBookmark( bookmark2 ).moveToBookmark( bookmark );
        };
    me.setOpt('paragraph',['p:段落', 'h1:标题 1', 'h2:标题 2', 'h3:标题 3', 'h4:标题 4', 'h5:标题 5', 'h6:标题 6']);
    me.commands['paragraph'] = {
        execCommand : function( cmdName, style,attrs,sourceCmdName ) {
            var range = new dom.Range(this.document);
            if(this.currentSelectedArr && this.currentSelectedArr.length > 0){
                for(var i=0,ti;ti=this.currentSelectedArr[i++];){
                    if(ti.style.display == 'none') continue;
                    if(domUtils.isEmptyNode(ti)){
                      
                        var tmpTxt = this.document.createTextNode('paragraph');
                        ti.innerHTML = '';
                        ti.appendChild(tmpTxt);
                    }
                    doParagraph(range.selectNodeContents(ti),style,attrs,sourceCmdName);
                    if(tmpTxt){
                        var pN = tmpTxt.parentNode;
                        domUtils.remove(tmpTxt);
                        if(domUtils.isEmptyNode(pN)){
                            domUtils.fillNode(this.document,pN)
                        }
                         
                    }


                }
                var td = this.currentSelectedArr[0];

                if(domUtils.isEmptyBlock(td)){
                    range.setStart(td,0).setCursor(false,true);
                }else{
                    range.selectNode(td).select()
                }

            }else{
                range = this.selection.getRange();
                if(range.collapsed){
                    var txt = this.document.createTextNode('p');
                    range.insertNode(txt);
                    if(browser.ie){
                        var node = txt.previousSibling;
                        if(node && domUtils.isWhitespace(node)){
                            domUtils.remove(node)
                        }
                        node = txt.nextSibling;
                        if(node && domUtils.isWhitespace(node)){
                            domUtils.remove(node)
                        } 
                    }

                }
                range = doParagraph(range,style,attrs,sourceCmdName);
                if(txt){
                    range.setStartBefore(txt).collapse(true);
                    pN = txt.parentNode;

                    domUtils.remove(txt);
                    
                    if(domUtils.isBlockElm(pN)&&domUtils.isEmptyNode(pN)){
                        domUtils.fillNode(this.document,pN)
                    }

                }

                if(browser.gecko && range.collapsed && range.startContainer.nodeType == 1){
                    var child = range.startContainer.childNodes[range.startOffset];
                    if(child && child.nodeType == 1 && child.tagName.toLowerCase() == style){
                        range.setStart(child,0).collapse(true)
                    }
                }
                range.select()

            }
            return true;
        },
        queryCommandValue : function() {
            var node = utils.findNode(this.selection.getStartElementPath(),['p','h1','h2','h3','h4','h5','h6']);
            return node ? node.tagName.toLowerCase() : '';
        },
        queryCommandState : function(){
            return this.highlight ? -1 :0;
        }
    }
};


(function(){
    var block = domUtils.isBlockElm,
        defaultValue = {
            left : 1,
            right : 1,
            center : 1,
            justify : 1
        },
        doJustify = function(range,style){
            var bookmark = range.createBookmark(),
                filterFn = function( node ) {
                    return node.nodeType == 1 ? node.tagName.toLowerCase() != 'br' &&  !domUtils.isBookmarkNode(node) : !domUtils.isWhitespace( node )
                };

            range.enlarge(true);
            var bookmark2 = range.createBookmark(),
                current = domUtils.getNextDomNode(bookmark2.start,false,filterFn),
                tmpRange = range.cloneRange(),
                tmpNode;
            while(current &&  !(domUtils.getPosition(current,bookmark2.end)&domUtils.POSITION_FOLLOWING)){
                if(current.nodeType == 3 || !block(current)){
                    tmpRange.setStartBefore(current);
                    while(current && current!==bookmark2.end &&  !block(current)){
                        tmpNode = current;
                        current = domUtils.getNextDomNode(current,false,null,function(node){
                            return !block(node)
                        });
                    }
                    tmpRange.setEndAfter(tmpNode);
                    var common = tmpRange.getCommonAncestor();
                    if( !domUtils.isBody(common) && block(common)){
                        domUtils.setStyles(common,utils.isString(style) ? {'text-align':style} : style);
                        current = common;
                    }else{
                        var p = range.document.createElement('p');
                         domUtils.setStyles(p,utils.isString(style) ? {'text-align':style} : style);
                        var frag = tmpRange.extractContents();
                        p.appendChild(frag);
                        tmpRange.insertNode(p);
                        current = p;
                    }
                    current = domUtils.getNextDomNode(current,false,filterFn);
                }else{
                    current = domUtils.getNextDomNode(current,true,filterFn);
                }
            }
            return range.moveToBookmark(bookmark2).moveToBookmark(bookmark)
        };
    UE.commands['justify'] =  {
        execCommand : function( cmdName,align ) {

            var  range = this.selection.getRange(),
                txt;
           
            if(this.currentSelectedArr && this.currentSelectedArr.length > 0){
                for(var i=0,ti;ti=this.currentSelectedArr[i++];){
                    if(domUtils.isEmptyNode(ti)){
                        txt = this.document.createTextNode('p');
                        range.setStart(ti,0).collapse(true).insertNode(txt).selectNode(txt);
                        
                    }else{
                        range.selectNodeContents(ti)
                    }

                    doJustify(range,align);
                    txt && domUtils.remove(txt);
                }
                range.selectNode(this.currentSelectedArr[0]).select()
            }else{
                if(range.collapsed){
                    txt = this.document.createTextNode('p');
                    range.insertNode(txt);
                }
                doJustify(range,align);
                if(txt){
                    range.setStartBefore(txt).collapse(true);
                    domUtils.remove(txt);
                }
                
                range.select();

            }
            return true;
        },
        queryCommandValue : function() {
            var startNode = this.selection.getStart(),
                value = domUtils.getComputedStyle(startNode,'text-align');
            return defaultValue[value] ? value : 'left';
        },
        queryCommandState : function(){
            return this.highlight ? -1 : 0;
                
        }

    }


})();



(function() {
    function optimize( range ) {
        var start = range.startContainer,end = range.endContainer;

        if ( start = domUtils.findParentByTagName( start, 'a', true ) ) {
            range.setStartBefore( start )
        }
        if ( end = domUtils.findParentByTagName( end, 'a', true ) ) {
            range.setEndAfter( end )
        }
    }

    UE.commands['unlink'] = {
        execCommand : function() {
            var as,
                range = new dom.Range(this.document),
                tds = this.currentSelectedArr,
                bookmark;
            if(tds && tds.length >0){
                for(var i=0,ti;ti=tds[i++];){
                    as = domUtils.getElementsByTagName(ti,'a');
                    for(var j=0,aj;aj=as[j++];){
                        domUtils.remove(aj,true);
                    }
                }
                if(domUtils.isEmptyNode(tds[0])){
                    range.setStart(tds[0],0).setCursor();
                }else{
                    range.selectNodeContents(tds[0]).select()
                }
            }else{
                range = this.selection.getRange();
                if(range.collapsed && !domUtils.findParentByTagName( range.startContainer, 'a', true )){
                    return;
                }
                bookmark = range.createBookmark();
                optimize( range );
                range.removeInlineStyle( 'a' ).moveToBookmark( bookmark ).select();
            }
        },
        queryCommandState : function(){
            return !this.highlight && this.queryCommandValue('link') ?  0 : -1;
        }

    };
    function doLink(range,opt){
        optimize( range = range.adjustmentBoundary() );
        var start = range.startContainer;
        if(start.nodeType == 1){
            start = start.childNodes[range.startOffset];
            if(start && start.nodeType == 1 && start.tagName == 'A' && /^(?:https?|ftp|file)\s*:\s*\/\//.test(start[browser.ie?'innerText':'textContent'])){
                start[browser.ie ? 'innerText' : 'textContent'] =  utils.html(opt.textValue||opt.href);

            }
        }
        range.removeInlineStyle( 'a' );
        if ( range.collapsed ) {
            var a = range.document.createElement( 'a'),
                text = '';
            if(opt.textValue){

                text =   utils.html(opt.textValue);
                delete opt.textValue;
            }else{
                text =   utils.html(opt.href);

            }
            domUtils.setAttributes( a, opt );
            range.insertNode( a );
            a[browser.ie ? 'innerText' : 'textContent'] = text;
            range.selectNode( a );
        } else {
            range.applyInlineStyle( 'a', opt )

        }
    }
    UE.commands['link'] = {
        queryCommandState : function(){
            return this.highlight ? -1 :0;
        },
        execCommand : function( cmdName, opt ) {
            var range = new dom.Range(this.document),
                tds = this.currentSelectedArr;

            opt.data_ue_src && (opt.data_ue_src = utils.unhtml(opt.data_ue_src));
            opt.href && (opt.href = utils.unhtml(opt.href));
            opt.textValue && (opt.textValue = utils.unhtml(opt.textValue));
            if(tds && tds.length){
                for(var i=0,ti;ti=tds[i++];){
                    if(domUtils.isEmptyNode(ti)){
                        ti[browser.ie ? 'innerText' : 'textContent'] =   utils.html(opt.textValue || opt.href)
                    }
                    doLink(range.selectNodeContents(ti),opt)
                }
                range.selectNodeContents(tds[0]).select()

               
            }else{
                doLink(range=this.selection.getRange(),opt);
                range.collapse().select(true);

            }
        },
        queryCommandValue : function() {

            var range = new dom.Range(this.document),
                tds = this.currentSelectedArr,
                as,
                node;
            if(tds && tds.length){
                for(var i=0,ti;ti=tds[i++];){
                    as = ti.getElementsByTagName('a');
                    if(as[0])
                        return as[0]
                }
            }else{
                range = this.selection.getRange();



                if ( range.collapsed ) {
                    node = this.selection.getStart();
                    if ( node && (node = domUtils.findParentByTagName( node, 'a', true )) ) {
                        return node;
                    }
                } else {
                    range.shrinkBoundary();
                    var start = range.startContainer.nodeType  == 3 || !range.startContainer.childNodes[range.startOffset] ? range.startContainer : range.startContainer.childNodes[range.startOffset],
                        end =  range.endContainer.nodeType == 3 || range.endOffset == 0 ? range.endContainer : range.endContainer.childNodes[range.endOffset-1],

                        common = range.getCommonAncestor();


                    node = domUtils.findParentByTagName( common, 'a', true );
                    if ( !node && common.nodeType == 1){

                        var as = common.getElementsByTagName( 'a' ),
                            ps,pe;

                        for ( var i = 0,ci; ci = as[i++]; ) {
                            ps = domUtils.getPosition( ci, start ),pe = domUtils.getPosition( ci,end);
                            if ( (ps & domUtils.POSITION_FOLLOWING || ps & domUtils.POSITION_CONTAINS)
                                &&
                                (pe & domUtils.POSITION_PRECEDING || pe & domUtils.POSITION_CONTAINS)
                                ) {
                                node = ci;
                                break;
                            }
                        }
                    }

                    return node;
                }
            }


        }
    };


})();


UE.plugins['catchremoteimage'] = function () {
    if (this.options.catchRemoteImageEnable===false)return;
    var me = this;
    this.setOpt({
            localDomain:["127.0.0.1","localhost"],
            separater:'ue_separate_ue',
            catchFieldName:"upfile",
            catchRemoteImageEnable:true
        });
    var ajax = UE.ajax,
        localDomain = me.options.localDomain ,
        catcherUrl = me.options.catcherUrl,
        separater = me.options.separater;
    function catchremoteimage(imgs, callbacks) {
        var submitStr = imgs.join(separater);
        var tmpOption = {
            timeout:60000,
            onsuccess:callbacks["success"],
            onerror:callbacks["error"]
        };
        tmpOption[me.options.catchFieldName] = submitStr;
        ajax.request(catcherUrl, tmpOption);
    }

    me.addListener("afterpaste", function () {
        me.fireEvent("catchRemoteImage");
    });

    me.addListener("catchRemoteImage", function () {
        var remoteImages = [];
        var imgs = domUtils.getElementsByTagName(me.document, "img");
        var test = function (src,urls) {
            for (var j = 0, url; url = urls[j++];) {
                if (src.indexOf(url) !== -1) {
                    return true;
                }
            }
            return false;
        };
        for (var i = 0, ci; ci = imgs[i++];) {
            if (ci.getAttribute("word_img"))continue;
            var src = ci.getAttribute("data_ue_src") || ci.src || "";
            if (/^(https?|ftp):/i.test(src) && !test(src,localDomain)) {
                remoteImages.push(src);
            }
        }
        if (remoteImages.length) {
            catchremoteimage(remoteImages, {
                success:function (xhr) {
                    try {
                        var info = eval("(" + xhr.responseText + ")");
                    } catch (e) {
                        return;
                    }
                    var srcUrls = info.srcUrl.split(separater),
                        urls = info.url.split(separater);
                    for (var i = 0, ci; ci = imgs[i++];) {
                        var src = ci.getAttribute("data_ue_src") || ci.src || "";
                        for (var j = 0, cj; cj = srcUrls[j++];) {
                            var url = urls[j - 1];
                            if (src == cj && url != "error") {
                                var newSrc = me.options.catcherPath + url;
                                domUtils.setAttributes(ci, {
                                    "src":newSrc,
                                    "data_ue_src":newSrc
                                });
                                break;
                            }
                        }
                    }
                },
                error:function () {
                    me.fireEvent("catchremoteerror");
                }
            })
        }

    })
};


UE.commands['imagefloat'] = {
    execCommand : function (cmd, align){
        var me = this,
            range = me.selection.getRange();
        if(!range.collapsed ){
            var img = range.getClosedNode();
            if(img && img.tagName == 'IMG'){
                switch (align){
                    case 'left':
                    case 'right':
                    case 'none':
                        var pN = img.parentNode,tmpNode,pre,next;
                        while(dtd.$inline[pN.tagName] || pN.tagName == 'A'){
                            pN = pN.parentNode;
                        }
                        tmpNode = pN;
                        if(tmpNode.tagName == 'P' && domUtils.getStyle(tmpNode,'text-align') == 'center'){
                            if(!domUtils.isBody(tmpNode) && domUtils.getChildCount(tmpNode,function(node){return !domUtils.isBr(node) && !domUtils.isWhitespace(node)}) == 1){
                                pre = tmpNode.previousSibling;
                                next = tmpNode.nextSibling;
                                if(pre && next && pre.nodeType == 1 &&  next.nodeType == 1 && pre.tagName == next.tagName && domUtils.isBlockElm(pre)){
                                    pre.appendChild(tmpNode.firstChild);
                                    while(next.firstChild){
                                        pre.appendChild(next.firstChild)
                                    }
                                    domUtils.remove(tmpNode);
                                    domUtils.remove(next);
                                }else{
                                    domUtils.setStyle(tmpNode,'text-align','')
                                }


                            }

                            range.selectNode(img).select()
                        }
                        domUtils.setStyle(img,'float',align);
                        break;
                    case 'center':
                        if(me.queryCommandValue('imagefloat') != 'center'){
                            pN = img.parentNode;
                            domUtils.setStyle(img,'float','none');
                            tmpNode = img;
                            while(pN && domUtils.getChildCount(pN,function(node){return !domUtils.isBr(node) && !domUtils.isWhitespace(node)}) == 1
                                && (dtd.$inline[pN.tagName] || pN.tagName == 'A')){
                                tmpNode = pN;
                                pN = pN.parentNode;
                            }
                            range.setStartBefore(tmpNode).setCursor(false);
                            pN = me.document.createElement('div');
                            pN.appendChild(tmpNode);
                            domUtils.setStyle(tmpNode,'float','');

                            me.execCommand('insertHtml','<p id="_img_parent_tmp" style="text-align:center">'+pN.innerHTML+'</p>');

                            tmpNode = me.document.getElementById('_img_parent_tmp');
                            tmpNode.removeAttribute('id');
                            tmpNode = tmpNode.firstChild;
                            range.selectNode(tmpNode).select();
                            next = tmpNode.parentNode.nextSibling;
                            if(next && domUtils.isEmptyNode(next)){
                                domUtils.remove(next)
                            }

                        }

                        break;
                }

            }
        }
    },
    queryCommandValue : function() {
        var range = this.selection.getRange(),
            startNode,floatStyle;
        if(range.collapsed){
            return 'none';
        }
        startNode = range.getClosedNode();
        if(startNode && startNode.nodeType == 1 && startNode.tagName == 'IMG'){
            floatStyle = domUtils.getComputedStyle(startNode,'float');
            if(floatStyle == 'none'){
                floatStyle = domUtils.getComputedStyle(startNode.parentNode,'text-align') == 'center' ? 'center' : floatStyle
            }
            return {
                    left : 1,
                    right : 1,
                    center : 1
                }[floatStyle] ? floatStyle : 'none'
        }
        return 'none'


    },
    queryCommandState : function(){
        if(this.highlight){
                   return -1;
               }
        var range = this.selection.getRange(),
            startNode;
        if(range.collapsed){
            return -1;
        }
        startNode = range.getClosedNode();
        if(startNode && startNode.nodeType == 1 && startNode.tagName == 'IMG'){
            return 0;
        }
        return -1;
    }
};
UE.commands['insertimage'] = {
    execCommand : function (cmd, opt){
        opt = utils.isArray(opt) ? opt : [opt];
        if(!opt.length) return;
        var me = this,
            range = me.selection.getRange(),
            img = range.getClosedNode();
        if(img && /img/i.test( img.tagName ) && img.className != "edui-faked-video" &&!img.getAttribute("word_img") ){
            var first = opt.shift();
            var floatStyle = first['floatStyle'];
            delete first['floatStyle'];
            domUtils.setAttributes(img,first);
            me.execCommand('imagefloat',floatStyle);
            if(opt.length > 0){
                range.setStartAfter(img).setCursor(false,true);
                me.execCommand('insertimage',opt);
            }

        }else{
            var html = [],str = '',ci;
            ci = opt[0];
            if(opt.length == 1){
                str = '<img src="'+ci.src+'" '+ (ci.data_ue_src ? ' data_ue_src="' + ci.data_ue_src +'" ':'') +
                        (ci.width ? 'width="'+ci.width+'" ':'') +
                        (ci.height ? ' height="'+ci.height+'" ':'') +
                        (ci['floatStyle']&&ci['floatStyle']!='center' ? ' style="float:'+ci['floatStyle']+';"':'') +
                        (ci.title?' title="'+ci.title+'"':'') + ' border="'+ (ci.border||0) + '" hspace = "'+(ci.hspace||0)+'" vspace = "'+(ci.vspace||0)+'" />';
                if(ci['floatStyle'] == 'center'){
                        str = '<p style="text-align: center">'+str+'</p>'
                 }
                html.push(str)

            }else{
                for(var i=0;ci=opt[i++];){
                    str =  '<p ' + (ci['floatStyle'] == 'center' ? 'style="text-align: center" ' : '') + '><img src="'+ci.src+'" '+
                        (ci.width ? 'width="'+ci.width+'" ':'') +   (ci.data_ue_src ? ' data_ue_src="' + ci.data_ue_src +'" ':'') +
                        (ci.height ? ' height="'+ci.height+'" ':'') +
                        ' style="' + (ci['floatStyle']&&ci['floatStyle']!='center' ? 'float:'+ci['floatStyle']+';':'') +
                        (ci.border||'') + '" ' +
                        (ci.title?' title="'+ci.title+'"':'') + ' /></p>';
                    html.push(str)
                }
            }

            me.execCommand('insertHtml',html.join(''));
        }
    },
    queryCommandState : function(){
        return this.highlight ? -1 :0;
    }
};


UE.plugins['table'] = function () {
    var me = this,
            keys = domUtils.keys,
            clearSelectedTd = domUtils.clearSelectedArr;
    var anchorTd,
            tableOpt,
            _isEmpty = domUtils.isEmptyNode;

    function getIndex( cell ) {
        var cells = cell.parentNode.cells;
        for ( var i = 0, ci; ci = cells[i]; i++ ) {
            if ( ci === cell ) {
                return i;
            }
        }
    }

    function deleteTable( table, range ) {
        var p = table.ownerDocument.createElement( 'p' );
        domUtils.fillNode( me.document, p );
        var pN = table.parentNode;
        if ( pN && pN.getAttribute( 'dropdrag' ) ) {
            table = pN;
        }
        table.parentNode.insertBefore( p, table );
        domUtils.remove( table );
        range.setStart( p, 0 ).setCursor();
    }

    
    function _isHide( cell ) {
        return cell.style.display == "none";
    }

    function getCount( arr ) {
        var count = 0;
        for ( var i = 0, ti; ti = arr[i++]; ) {
            if ( !_isHide( ti ) ) {
                count++
            }

        }
        return count;
    }

    me.currentSelectedArr = [];
    me.addListener( 'mousedown', _mouseDownEvent );
    me.addListener( 'keydown', function ( type, evt ) {
        var keyCode = evt.keyCode || evt.which;
        if ( !keys[keyCode] && !evt.ctrlKey && !evt.metaKey && !evt.shiftKey && !evt.altKey ) {
            clearSelectedTd( me.currentSelectedArr )
        }
    } );

    me.addListener( 'mouseup', function () {

        anchorTd = null;
        me.removeListener( 'mouseover', _mouseDownEvent );
        var td = me.currentSelectedArr[0];
        if ( td ) {
            me.document.body.style.webkitUserSelect = '';
            var range = new dom.Range( me.document );
            if ( _isEmpty( td ) ) {
                range.setStart( me.currentSelectedArr[0], 0 ).setCursor();
            } else {
                range.selectNodeContents( me.currentSelectedArr[0] ).select();
            }

        } else {

            var range = me.selection.getRange().shrinkBoundary();

            if ( !range.collapsed ) {
                var start = domUtils.findParentByTagName( range.startContainer, 'td', true ),
                        end = domUtils.findParentByTagName( range.endContainer, 'td', true );
                if ( start && !end || !start && end || start && end && start !== end ) {
                    range.collapse( true ).select( true )
                }
            }


        }

    } );

    function reset() {
        me.currentSelectedArr = [];
        anchorTd = null;

    }

    
    me.commands['inserttable'] = {
        queryCommandState:function () {
            if ( this.highlight ) {
                return -1;
            }
            var range = this.selection.getRange();
            return domUtils.findParentByTagName( range.startContainer, 'table', true )
                    || domUtils.findParentByTagName( range.endContainer, 'table', true )
                    || me.currentSelectedArr.length > 0 ? -1 : 0;
        },
        execCommand:function ( cmdName, opt ) {
            opt = opt || {numRows:5, numCols:5,border:1};
            var html = ['<table ' + (opt.border == "0"  ? ' class="noBorderTable"' : '') + ' _innerCreateTable = "true" '];
            if ( opt.cellSpacing && opt.cellSpacing != '0' || opt.cellPadding && opt.cellPadding != '0' ) {
                html.push( ' style="border-collapse:separate;" ' )
            }
            opt.cellSpacing && opt.cellSpacing != '0' && html.push( ' cellSpacing="' + opt.cellSpacing + '" ' );
            opt.cellPadding && opt.cellPadding != '0' && html.push( ' cellPadding="' + opt.cellPadding + '" ' );
            html.push( ' width="' + (opt.width || 100) + (typeof opt.widthUnit == "undefined" ? '%' : opt.widthUnit) + '" ' );
            opt.height && html.push( ' height="' + opt.height + (typeof opt.heightUnit == "undefined" ? '%' : opt.heightUnit) + '" ' );
            opt.align && (html.push( ' align="' + opt.align + '" ' ));
            html.push( ' border="' + (opt.border || 0) + '" borderColor="' + (opt.borderColor || '#000000') + '"' );
            opt.borderType == "1" && html.push( ' borderType="1" ' );
            opt.bgColor && html.push( ' bgColor="' + opt.bgColor + '"' );
            html.push( ' ><tbody>' );
            opt.width = Math.floor( (opt.width || '100') / opt.numCols );
            for ( var i = 0; i < opt.numRows; i++ ) {
                html.push( '<tr>' )
                for ( var j = 0; j < opt.numCols; j++ ) {
                    html.push( '<td style="width:' + opt.width + (typeof opt.widthUnit == "undefined" ? '%' : opt.widthUnit) + ';'
                            + (opt.borderType == '1' ? 'border:' + opt.border + 'px solid ' + (opt.borderColor || '#000000') : '')
                            + '">'
                            + (browser.ie ? domUtils.fillChar : '<br/>') + '</td>' )
                }
                html.push( '</tr>' )
            }
            me.execCommand( 'insertHtml', html.join( '' ) + '</tbody></table>' );
            reset();
            if ( opt.align ) {
                var range = me.selection.getRange(),
                        bk = range.createBookmark(),
                        start = range.startContainer;
                while ( start && !domUtils.isBody( start ) ) {
                    if ( domUtils.isBlockElm( start ) ) {
                        start.style.clear = 'both';
                        range.moveToBookmark( bk ).select();
                        break;
                    }
                    start = start.parentNode;
                }
            }

        }
    };
    me.commands['edittable'] = {
        queryCommandState:function () {
            var range = this.selection.getRange();
            if ( this.highlight ) {
                return -1;
            }
            return domUtils.findParentByTagName( range.startContainer, 'table', true )
                    || me.currentSelectedArr.length > 0 ? 0 : -1;
        },
        execCommand:function ( cmdName, opt ) {
            var start = me.selection.getStart(),
                    table = domUtils.findParentByTagName( start, 'table', true );
            if ( table ) {
                table.style.cssText = table.style.cssText.replace( /border[^;]+/gi, '' );
                table.style.borderCollapse = opt.cellSpacing && opt.cellSpacing != '0' || opt.cellPadding && opt.cellPadding != '0' ? 'separate' : 'collapse';
                opt.cellSpacing && opt.cellSpacing != '0' ? table.setAttribute( 'cellSpacing', opt.cellSpacing ) : table.removeAttribute( 'cellSpacing' );
                opt.cellPadding && opt.cellPadding != '0' ? table.setAttribute( 'cellPadding', opt.cellPadding ) : table.removeAttribute( 'cellPadding' );
                opt.height && table.setAttribute( 'height', opt.height + opt.heightUnit );
                opt.align && table.setAttribute( 'align', opt.align );
                opt.width && table.setAttribute( 'width', opt.width + opt.widthUnit );
                opt.bgColor && table.setAttribute( 'bgColor', opt.bgColor );
                opt.borderColor && table.setAttribute( 'borderColor', opt.borderColor );
                table.setAttribute( 'border', opt.border );
                table.className = opt.border == "0" ? "noBorderTable" : "";

                if ( opt.borderType == "1" ) {
                    for ( var i = 0, ti, tds = table.getElementsByTagName( 'td' ); ti = tds[i++]; ) {
                        ti.style.border = opt.border + 'px solid ' + (opt.borderColor || '#000000')

                    }
                    table.setAttribute( 'borderType', '1' )
                } else {
                    for ( var i = 0, ti, tds = table.getElementsByTagName( 'td' ); ti = tds[i++]; ) {
                        if ( browser.ie ) {
                            ti.style.cssText = ti.style.cssText.replace( /border[^;]+/gi, '' );

                        } else {
                            domUtils.removeStyle( ti, 'border' );
                            domUtils.removeStyle( ti, 'border-image' )
                        }

                    }
                    table.removeAttribute( 'borderType' )
                }

            }
        }
    };

    me.commands['edittd'] = {
        queryCommandState:function () {
            if ( this.highlight ) {
                return -1;
            }
            var range = this.selection.getRange();
            return (domUtils.findParentByTagName( range.startContainer, 'table', true )
                    && domUtils.findParentByTagName( range.endContainer, 'table', true )) || me.currentSelectedArr.length > 0 ? 0 : -1;
        },
        
        execCommand:function ( cmdName, tdItems ) {
            var range = this.selection.getRange(),
                    tds = !me.currentSelectedArr.length ? [domUtils.findParentByTagName( range.startContainer, ['td', 'th'], true )] : me.currentSelectedArr;
            for ( var i = 0, td; td = tds[i++]; ) {
                domUtils.setAttributes( td, {
                    "bgColor":tdItems.bgColor,
                    "align":tdItems.align,
                    "vAlign":tdItems.vAlign
                } );
            }
        }
    };


    
    me.commands['deletetable'] = {
        queryCommandState:function () {
            if ( this.highlight ) {
                return -1;
            }
            var range = this.selection.getRange();
            return (domUtils.findParentByTagName( range.startContainer, 'table', true )
                    && domUtils.findParentByTagName( range.endContainer, 'table', true )) || me.currentSelectedArr.length > 0 ? 0 : -1;
        },
        execCommand:function () {
            var range = this.selection.getRange(),
                    table = domUtils.findParentByTagName( me.currentSelectedArr.length > 0 ? me.currentSelectedArr[0] : range.startContainer, 'table', true );
            deleteTable( table, range );
            reset();
        }
    };

    
    me.commands['addcaption'] = {
        queryCommandState:function () {
            if ( this.highlight ) {
                return -1;
            }
            var range = this.selection.getRange();
            return (domUtils.findParentByTagName( range.startContainer, 'table', true )
                    && domUtils.findParentByTagName( range.endContainer, 'table', true )) || me.currentSelectedArr.length > 0 ? 0 : -1;
        },
        execCommand:function ( cmdName, opt ) {

            var range = this.selection.getRange(),
                    table = domUtils.findParentByTagName( me.currentSelectedArr.length > 0 ? me.currentSelectedArr[0] : range.startContainer, 'table', true );

            if ( opt == "on" ) {
                var c = table.createCaption();
                c.innerHTML = "请在此输入表格标题";
            } else {
                table.removeChild( table.caption );
            }
        }
    };


    
    me.commands['mergeright'] = {
        queryCommandState:function () {
            if ( this.highlight ) {
                return -1;
            }
            var range = this.selection.getRange(),
                    start = range.startContainer,
                    td = domUtils.findParentByTagName( start, ['td', 'th'], true );


            if ( !td || this.currentSelectedArr.length > 1 )return -1;

            var tr = td.parentNode;
            var rightCellIndex = getIndex( td ) + td.colSpan;
            if ( rightCellIndex >= tr.cells.length ) {
                return -1;
            }
            var rightCell = tr.cells[rightCellIndex];
            if ( _isHide( rightCell ) ) {
                return -1;
            }
            return td.rowSpan == rightCell.rowSpan ? 0 : -1;
        },
        execCommand:function () {

            var range = this.selection.getRange(),
                    start = range.startContainer,
                    td = domUtils.findParentByTagName( start, ['td', 'th'], true ) || me.currentSelectedArr[0],
                    tr = td.parentNode,
                    rows = tr.parentNode.parentNode.rows;
            var rightCellRowIndex = tr.rowIndex,
                    rightCellCellIndex = getIndex( td ) + td.colSpan,
                    rightCell = rows[rightCellRowIndex].cells[rightCellCellIndex];
            for ( var i = rightCellRowIndex; i < rightCellRowIndex + rightCell.rowSpan; i++ ) {
                for ( var j = rightCellCellIndex; j < rightCellCellIndex + rightCell.colSpan; j++ ) {
                    var tmpCell = rows[i].cells[j];
                    tmpCell.setAttribute( 'rootRowIndex', tr.rowIndex );
                    tmpCell.setAttribute( 'rootCellIndex', getIndex( td ) );

                }
            }
            td.colSpan += rightCell.colSpan || 1;
            _moveContent( td, rightCell );
            rightCell.style.display = "none";
            if ( domUtils.isEmptyBlock( td ) ) {
                range.setStart( td, 0 ).setCursor();
            } else {
                range.selectNodeContents( td ).setCursor( true, true );
            }
            browser.ie && domUtils.removeAttributes( td, ['width', 'height'] );
        }
    };

    
    me.commands['mergedown'] = {
        queryCommandState:function () {
            if ( this.highlight ) {
                return -1;
            }
            var range = this.selection.getRange(),
                    start = range.startContainer,
                    td = domUtils.findParentByTagName( start, 'td', true );

            if ( !td || getCount( me.currentSelectedArr ) > 1 )return -1;


            var tr = td.parentNode,
                    table = tr.parentNode.parentNode,
                    rows = table.rows;
            var downCellRowIndex = tr.rowIndex + td.rowSpan;
            if ( downCellRowIndex >= rows.length ) {
                return -1;
            }
            var downCell = rows[downCellRowIndex].cells[getIndex( td )];
            if ( _isHide( downCell ) ) {
                return -1;
            }
            return td.colSpan == downCell.colSpan ? 0 : -1;
        },
        execCommand:function () {

            var range = this.selection.getRange(),
                    start = range.startContainer,
                    td = domUtils.findParentByTagName( start, ['td', 'th'], true ) || me.currentSelectedArr[0];


            var tr = td.parentNode,
                    rows = tr.parentNode.parentNode.rows;

            var downCellRowIndex = tr.rowIndex + td.rowSpan,
                    downCellCellIndex = getIndex( td ),
                    downCell = rows[downCellRowIndex].cells[downCellCellIndex];
            for ( var i = downCellRowIndex; i < downCellRowIndex + downCell.rowSpan; i++ ) {
                for ( var j = downCellCellIndex; j < downCellCellIndex + downCell.colSpan; j++ ) {
                    var tmpCell = rows[i].cells[j];


                    tmpCell.setAttribute( 'rootRowIndex', tr.rowIndex );
                    tmpCell.setAttribute( 'rootCellIndex', getIndex( td ) );
                }
            }
            td.rowSpan += downCell.rowSpan || 1;
            _moveContent( td, downCell );
            downCell.style.display = "none";
            if ( domUtils.isEmptyBlock( td ) ) {
                range.setStart( td, 0 ).setCursor();
            } else {
                range.selectNodeContents( td ).setCursor( true, true );
            }
            browser.ie && domUtils.removeAttributes( td, ['width', 'height'] );
        }
    };

    
    me.commands['deleterow'] = {
        queryCommandState:function () {
            if ( this.highlight ) {
                return -1;
            }
            var range = this.selection.getRange(),
                    start = range.startContainer,
                    td = domUtils.findParentByTagName( start, ['td', 'th'], true );
            if ( !td && me.currentSelectedArr.length == 0 )return -1;

        },
        execCommand:function () {
            var range = this.selection.getRange(),
                    start = range.startContainer,
                    td = domUtils.findParentByTagName( start, ['td', 'th'], true ),
                    tr,
                    table,
                    cells,
                    rows ,
                    rowIndex ,
                    cellIndex;

            if ( td && me.currentSelectedArr.length == 0 ) {
                var count = (td.rowSpan || 1) - 1;

                me.currentSelectedArr.push( td );
                tr = td.parentNode,
                        table = tr.parentNode.parentNode;

                rows = table.rows,
                        rowIndex = tr.rowIndex + 1,
                        cellIndex = getIndex( td );

                while ( count ) {

                    me.currentSelectedArr.push( rows[rowIndex].cells[cellIndex] );
                    count--;
                    rowIndex++
                }
            }

            while ( td = me.currentSelectedArr.pop() ) {

                if ( !domUtils.findParentByTagName( td, 'table' ) ) {//|| _isHide(td)

                    continue;
                }
                tr = td.parentNode,
                        table = tr.parentNode.parentNode;
                cells = tr.cells,
                        rows = table.rows,
                        rowIndex = tr.rowIndex,
                        cellIndex = getIndex( td );
                
                for ( var currentCellIndex = 0; currentCellIndex < cells.length; ) {
                    var currentNode = cells[currentCellIndex];
                    if ( _isHide( currentNode ) ) {
                        var topNode = rows[currentNode.getAttribute( 'rootRowIndex' )].cells[currentNode.getAttribute( 'rootCellIndex' )];
                        topNode.rowSpan--;
                        currentCellIndex += topNode.colSpan;
                    } else {
                        if ( currentNode.rowSpan == 1 ) {
                            currentCellIndex += currentNode.colSpan;
                        } else {
                            var downNode = rows[rowIndex + 1].cells[currentCellIndex];
                            downNode.style.display = "";
                            downNode.rowSpan = currentNode.rowSpan - 1;
                            downNode.colSpan = currentNode.colSpan;
                            currentCellIndex += currentNode.colSpan;
                        }
                    }
                }
                domUtils.remove( tr );
                var topRowTd, focusTd, downRowTd;

                if ( rowIndex == rows.length ) {
                    if ( rowIndex == 0 ) {
                        deleteTable( table, range );

                        return;
                    }
                    var preRowIndex = rowIndex - 1;
                    topRowTd = rows[preRowIndex].cells[ cellIndex];
                    focusTd = _isHide( topRowTd ) ? rows[topRowTd.getAttribute( 'rootRowIndex' )].cells[topRowTd.getAttribute( 'rootCellIndex' )] : topRowTd;

                } else {

                    downRowTd = rows[rowIndex].cells[cellIndex];
                    focusTd = _isHide( downRowTd ) ? rows[downRowTd.getAttribute( 'rootRowIndex' )].cells[downRowTd.getAttribute( 'rootCellIndex' )] : downRowTd;
                }
            }


            range.setStart( focusTd, 0 ).setCursor();
            update( table )
        }
    };

    
    me.commands['deletecol'] = {
        queryCommandState:function () {
            if ( this.highlight ) {
                return -1;
            }
            var range = this.selection.getRange(),
                    start = range.startContainer,
                    td = domUtils.findParentByTagName( start, ['td', 'th'], true );
            if ( !td && me.currentSelectedArr.length == 0 )return -1;
        },
        execCommand:function () {

            var range = this.selection.getRange(),
                    start = range.startContainer,
                    td = domUtils.findParentByTagName( start, ['td', 'th'], true );


            if ( td && me.currentSelectedArr.length == 0 ) {

                var count = (td.colSpan || 1) - 1;

                me.currentSelectedArr.push( td );
                while ( count ) {
                    do {
                        td = td.nextSibling
                    } while ( td.nodeType == 3 );
                    me.currentSelectedArr.push( td );
                    count--;
                }
            }

            while ( td = me.currentSelectedArr.pop() ) {
                if ( !domUtils.findParentByTagName( td, 'table' ) ) {
                    continue;
                }

                var tr = td.parentNode,
                        table = tr.parentNode.parentNode,
                        cellIndex = getIndex( td ),
                        rows = table.rows,
                        cells = tr.cells,
                        rowIndex = tr.rowIndex;
                
                var rowSpan;
                for ( var currentRowIndex = 0; currentRowIndex < rows.length; ) {
                    var currentNode = rows[currentRowIndex].cells[cellIndex];
                    if ( _isHide( currentNode ) ) {
                        var leftNode = rows[currentNode.getAttribute( 'rootRowIndex' )].cells[currentNode.getAttribute( 'rootCellIndex' )];
                        rowSpan = leftNode.rowSpan;
                        for ( var i = 0; i < leftNode.rowSpan; i++ ) {
                            var delNode = rows[currentRowIndex + i].cells[cellIndex];
                            domUtils.remove( delNode );
                        }
                        leftNode.colSpan--;
                        currentRowIndex += rowSpan;
                    } else {
                        if ( currentNode.colSpan == 1 ) {
                            rowSpan = currentNode.rowSpan;
                            for ( var i = currentRowIndex, l = currentRowIndex + currentNode.rowSpan; i < l; i++ ) {
                                domUtils.remove( rows[i].cells[cellIndex] );
                            }
                            currentRowIndex += rowSpan;

                        } else {
                            var rightNode = rows[currentRowIndex].cells[cellIndex + 1];
                            rightNode.style.display = "";
                            rightNode.rowSpan = currentNode.rowSpan;
                            rightNode.colSpan = currentNode.colSpan - 1;
                            currentRowIndex += currentNode.rowSpan;
                            domUtils.remove( currentNode );
                        }
                    }
                }
                var preColTd, focusTd, nextColTd;
                if ( cellIndex == cells.length ) {
                    if ( cellIndex == 0 ) {
                        deleteTable( table, range );
                        return;
                    }
                    var preCellIndex = cellIndex - 1;
                    preColTd = rows[rowIndex].cells[preCellIndex];
                    focusTd = _isHide( preColTd ) ? rows[preColTd.getAttribute( 'rootRowIndex' )].cells[preColTd.getAttribute( 'rootCellIndex' )] : preColTd;

                } else {

                    nextColTd = rows[rowIndex].cells[cellIndex];
                    focusTd = _isHide( nextColTd ) ? rows[nextColTd.getAttribute( 'rootRowIndex' )].cells[nextColTd.getAttribute( 'rootCellIndex' )] : nextColTd;
                }
            }

            range.setStart( focusTd, 0 ).setCursor();
            update( table )
        }
    };

    
    me.commands['splittocells'] = {
        queryCommandState:function () {
            if ( this.highlight ) {
                return -1;
            }
            var range = this.selection.getRange(),
                    start = range.startContainer,
                    td = domUtils.findParentByTagName( start, ['td', 'th'], true );
            return td && ( td.rowSpan > 1 || td.colSpan > 1 ) && (!me.currentSelectedArr.length || getCount( me.currentSelectedArr ) == 1) ? 0 : -1;
        },
        execCommand:function () {

            var range = this.selection.getRange(),
                    start = range.startContainer,
                    td = domUtils.findParentByTagName( start, ['td', 'th'], true ),
                    tr = td.parentNode,
                    table = tr.parentNode.parentNode;
            var rowIndex = tr.rowIndex,
                    cellIndex = getIndex( td ),
                    rowSpan = td.rowSpan,
                    colSpan = td.colSpan;


            for ( var i = 0; i < rowSpan; i++ ) {
                for ( var j = 0; j < colSpan; j++ ) {
                    var cell = table.rows[rowIndex + i].cells[cellIndex + j];
                    cell.rowSpan = 1;
                    cell.colSpan = 1;

                    if ( _isHide( cell ) ) {
                        cell.style.display = "";
                        cell.innerHTML = browser.ie ? '' : "<br/>";
                    }
                }
            }
        }
    };


    
    me.commands['splittorows'] = {
        queryCommandState:function () {
            if ( this.highlight ) {
                return -1;
            }
            var range = this.selection.getRange(),
                    start = range.startContainer,
                    td = domUtils.findParentByTagName( start, 'td', true ) || me.currentSelectedArr[0];
            return td && ( td.rowSpan > 1) && (!me.currentSelectedArr.length || getCount( me.currentSelectedArr ) == 1) ? 0 : -1;
        },
        execCommand:function () {

            var range = this.selection.getRange(),
                    start = range.startContainer,
                    td = domUtils.findParentByTagName( start, 'td', true ) || me.currentSelectedArr[0],
                    tr = td.parentNode,
                    rows = tr.parentNode.parentNode.rows;

            var rowIndex = tr.rowIndex,
                    cellIndex = getIndex( td ),
                    rowSpan = td.rowSpan,
                    colSpan = td.colSpan;

            for ( var i = 0; i < rowSpan; i++ ) {
                var cells = rows[rowIndex + i],
                        cell = cells.cells[cellIndex];
                cell.rowSpan = 1;
                cell.colSpan = colSpan;
                if ( _isHide( cell ) ) {
                    cell.style.display = "";
                    cell.innerHTML = browser.ie ? '' : '<br/>'
                }
                for ( var j = cellIndex + 1; j < cellIndex + colSpan; j++ ) {
                    cell = cells.cells[j];

                    cell.setAttribute( 'rootRowIndex', rowIndex + i )
                }

            }
            clearSelectedTd( me.currentSelectedArr );
            this.selection.getRange().setStart( td, 0 ).setCursor();
        }
    };


    
    me.commands['insertparagraphbeforetable'] = {
        queryCommandState:function () {
            if ( this.highlight ) {
                return -1;
            }
            var range = this.selection.getRange(),
                    start = range.startContainer,
                    td = domUtils.findParentByTagName( start, 'td', true ) || me.currentSelectedArr[0];
            return  td && domUtils.findParentByTagName( td, 'table' ) ? 0 : -1;
        },
        execCommand:function () {

            var range = this.selection.getRange(),
                    start = range.startContainer,
                    table = domUtils.findParentByTagName( start, 'table', true );

            start = me.document.createElement( me.options.enterTag );
            table.parentNode.insertBefore( start, table );
            clearSelectedTd( me.currentSelectedArr );
            if ( start.tagName == 'P' ) {
                start.innerHTML = browser.ie ? '' : '<br/>';
                range.setStart( start, 0 )
            } else {
                range.setStartBefore( start )
            }
            range.setCursor();

        }
    };

    
    me.commands['splittocols'] = {
        queryCommandState:function () {
            if ( this.highlight ) {
                return -1;
            }
            var range = this.selection.getRange(),
                    start = range.startContainer,
                    td = domUtils.findParentByTagName( start, ['td', 'th'], true ) || me.currentSelectedArr[0];
            return td && ( td.colSpan > 1) && (!me.currentSelectedArr.length || getCount( me.currentSelectedArr ) == 1) ? 0 : -1;
        },
        execCommand:function () {

            var range = this.selection.getRange(),
                    start = range.startContainer,
                    td = domUtils.findParentByTagName( start, ['td', 'th'], true ) || me.currentSelectedArr[0],
                    tr = td.parentNode,
                    rows = tr.parentNode.parentNode.rows;

            var rowIndex = tr.rowIndex,
                    cellIndex = getIndex( td ),
                    rowSpan = td.rowSpan,
                    colSpan = td.colSpan;

            for ( var i = 0; i < colSpan; i++ ) {
                var cell = rows[rowIndex].cells[cellIndex + i];
                cell.rowSpan = rowSpan;
                cell.colSpan = 1;
                if ( _isHide( cell ) ) {
                    cell.style.display = "";
                    cell.innerHTML = browser.ie ? '' : '<br/>'
                }

                for ( var j = rowIndex + 1; j < rowIndex + rowSpan; j++ ) {
                    var tmpCell = rows[j].cells[cellIndex + i];
                    tmpCell.setAttribute( 'rootCellIndex', cellIndex + i );
                }
            }
            clearSelectedTd( me.currentSelectedArr );
            this.selection.getRange().setStart( td, 0 ).setCursor();
        }
    };


    
    me.commands['insertrow'] = {
        queryCommandState:function () {
            if ( this.highlight ) {
                return -1;
            }
            var range = this.selection.getRange();
            return domUtils.findParentByTagName( range.startContainer, 'table', true )
                    || domUtils.findParentByTagName( range.endContainer, 'table', true ) || me.currentSelectedArr.length != 0 ? 0 : -1;
        },
        execCommand:function () {
            var range = this.selection.getRange(),
                    start = range.startContainer,
                    tr = domUtils.findParentByTagName( start, 'tr', true ) || me.currentSelectedArr[0].parentNode,
                    table = tr.parentNode.parentNode,
                    rows = table.rows;
            var rowIndex = tr.rowIndex,
                    cells = rows[rowIndex].cells;
            var newRow = table.insertRow( rowIndex );

            var newCell;
            for ( var cellIndex = 0; cellIndex < cells.length; ) {

                var tmpCell = cells[cellIndex];

                if ( _isHide( tmpCell ) ) {
                    var topCell = rows[tmpCell.getAttribute( 'rootRowIndex' )].cells[tmpCell.getAttribute( 'rootCellIndex' )];
                    topCell.rowSpan++;
                    for ( var i = 0; i < topCell.colSpan; i++ ) {
                        newCell = tmpCell.cloneNode( false );
                        domUtils.removeAttributes( newCell, ["bgColor", "valign", "align"] );
                        newCell.rowSpan = newCell.colSpan = 1;
                        newCell.innerHTML = browser.ie ? '' : "<br/>";
                        newCell.className = '';

                        if ( newRow.children[cellIndex + i] ) {
                            newRow.insertBefore( newCell, newRow.children[cellIndex + i] );
                        } else {
                            newRow.appendChild( newCell )
                        }

                        newCell.style.display = "none";
                    }
                    cellIndex += topCell.colSpan;

                } else {//若当前单元格未隐藏，则在其上行插入colspan个单元格

                    for ( var j = 0; j < tmpCell.colSpan; j++ ) {
                        newCell = tmpCell.cloneNode( false );
                        domUtils.removeAttributes( newCell, ["bgColor", "valign", "align"] );
                        newCell.rowSpan = newCell.colSpan = 1;
                        newCell.innerHTML = browser.ie ? '' : "<br/>";
                        newCell.className = '';
                        if ( newRow.children[cellIndex + j] ) {
                            newRow.insertBefore( newCell, newRow.children[cellIndex + j] );
                        } else {
                            newRow.appendChild( newCell )
                        }
                    }
                    cellIndex += tmpCell.colSpan;
                }
            }
            update( table );
            range.setStart( newRow.cells[0], 0 ).setCursor();

            clearSelectedTd( me.currentSelectedArr );
        }
    };

    
    me.commands['insertcol'] = {
        queryCommandState:function () {
            if ( this.highlight ) {
                return -1;
            }
            var range = this.selection.getRange();
            return domUtils.findParentByTagName( range.startContainer, 'table', true )
                    || domUtils.findParentByTagName( range.endContainer, 'table', true ) || me.currentSelectedArr.length != 0 ? 0 : -1;
        },
        execCommand:function () {

            var range = this.selection.getRange(),
                    start = range.startContainer,
                    td = domUtils.findParentByTagName( start, ['td', 'th'], true ) || me.currentSelectedArr[0],
                    table = domUtils.findParentByTagName( td, 'table' ),
                    rows = table.rows;

            var cellIndex = getIndex( td ),
                    newCell;
            for ( var rowIndex = 0; rowIndex < rows.length; ) {

                var tmpCell = rows[rowIndex].cells[cellIndex], tr;

                if ( _isHide( tmpCell ) ) {//如果当前单元格是隐藏的，表明当前单元格由其左边span过来，找到其左边单元格

                    var leftCell = rows[tmpCell.getAttribute( 'rootRowIndex' )].cells[tmpCell.getAttribute( 'rootCellIndex' )];
                    leftCell.colSpan++;
                    for ( var i = 0; i < leftCell.rowSpan; i++ ) {
                        newCell = td.cloneNode( false );
                        domUtils.removeAttributes( newCell, ["bgColor", "valign", "align"] );
                        newCell.rowSpan = newCell.colSpan = 1;
                        newCell.innerHTML = browser.ie ? '' : "<br/>";
                        newCell.className = '';
                        tr = rows[rowIndex + i];
                        if ( tr.children[cellIndex] ) {
                            tr.insertBefore( newCell, tr.children[cellIndex] );
                        } else {
                            tr.appendChild( newCell )
                        }

                        newCell.style.display = "none";
                    }
                    rowIndex += leftCell.rowSpan;

                } else {

                    for ( var j = 0; j < tmpCell.rowSpan; j++ ) {
                        newCell = td.cloneNode( false );
                        domUtils.removeAttributes( newCell, ["bgColor", "valign", "align"] );
                        newCell.rowSpan = newCell.colSpan = 1;
                        newCell.innerHTML = browser.ie ? '' : "<br/>";
                        newCell.className = '';
                        tr = rows[rowIndex + j];
                        if ( tr.children[cellIndex] ) {
                            tr.insertBefore( newCell, tr.children[cellIndex] );
                        } else {
                            tr.appendChild( newCell )
                        }

                        newCell.innerHTML = browser.ie ? '' : "<br/>";

                    }
                    rowIndex += tmpCell.rowSpan;
                }
            }
            update( table );
            range.setStart( rows[0].cells[cellIndex], 0 ).setCursor();
            clearSelectedTd( me.currentSelectedArr );

        }
    };

    
    me.commands['mergecells'] = {
        queryCommandState:function () {
            if ( this.highlight ) {
                return -1;
            }
            var count = 0;
            for ( var i = 0, ti; ti = this.currentSelectedArr[i++]; ) {
                if ( !_isHide( ti ) )
                    count++;
            }
            return count > 1 ? 0 : -1;
        },
        execCommand:function () {

            var start = me.currentSelectedArr[0],
                    end = me.currentSelectedArr[me.currentSelectedArr.length - 1],
                    table = domUtils.findParentByTagName( start, 'table' ),
                    rows = table.rows,
                    cellsRange = {
                        beginRowIndex:start.parentNode.rowIndex,
                        beginCellIndex:getIndex( start ),
                        endRowIndex:end.parentNode.rowIndex,
                        endCellIndex:getIndex( end )
                    },

                    beginRowIndex = cellsRange.beginRowIndex,
                    beginCellIndex = cellsRange.beginCellIndex,
                    rowsLength = cellsRange.endRowIndex - cellsRange.beginRowIndex + 1,
                    cellLength = cellsRange.endCellIndex - cellsRange.beginCellIndex + 1,

                    tmp = rows[beginRowIndex].cells[beginCellIndex];

            for ( var i = 0, ri; (ri = rows[beginRowIndex + i++]) && i <= rowsLength; ) {
                for ( var j = 0, ci; (ci = ri.cells[beginCellIndex + j++]) && j <= cellLength; ) {
                    if ( i == 1 && j == 1 ) {
                        ci.style.display = "";
                        ci.rowSpan = rowsLength;
                        ci.colSpan = cellLength;
                    } else {
                        ci.style.display = "none";
                        ci.rowSpan = 1;
                        ci.colSpan = 1;
                        ci.setAttribute( 'rootRowIndex', beginRowIndex );
                        ci.setAttribute( 'rootCellIndex', beginCellIndex );
                        _moveContent( tmp, ci );
                    }
                }
            }
            this.selection.getRange().setStart( tmp, 0 ).setCursor();
            browser.ie && domUtils.removeAttributes( tmp, ['width', 'height'] );
            clearSelectedTd( me.currentSelectedArr );
        }
    };

    
    function _moveContent( cellTo, cellFrom ) {
        if ( _isEmpty( cellFrom ) ) return;

        if ( _isEmpty( cellTo ) ) {
            cellTo.innerHTML = cellFrom.innerHTML;
            return;
        }
        var child = cellTo.lastChild;
        if ( child.nodeType != 1 || child.tagName != 'BR' ) {
            cellTo.appendChild( cellTo.ownerDocument.createElement( 'br' ) )
        }
        while ( child = cellFrom.firstChild ) {
            cellTo.appendChild( child );
        }
    }


    
    function _getCellsRange( cellA, cellB ) {

        var trA = cellA.parentNode,
                trB = cellB.parentNode,
                aRowIndex = trA.rowIndex,
                bRowIndex = trB.rowIndex,
                rows = trA.parentNode.parentNode.rows,
                rowsNum = rows.length,
                cellsNum = rows[0].cells.length,
                cellAIndex = getIndex( cellA ),
                cellBIndex = getIndex( cellB );

        if ( cellA == cellB ) {
            return {
                beginRowIndex:aRowIndex,
                beginCellIndex:cellAIndex,
                endRowIndex:aRowIndex + cellA.rowSpan - 1,
                endCellIndex:cellBIndex + cellA.colSpan - 1
            }
        }

        var
                beginRowIndex = Math.min( aRowIndex, bRowIndex ),
                beginCellIndex = Math.min( cellAIndex, cellBIndex ),
                endRowIndex = Math.max( aRowIndex + cellA.rowSpan - 1, bRowIndex + cellB.rowSpan - 1 ),
                endCellIndex = Math.max( cellAIndex + cellA.colSpan - 1, cellBIndex + cellB.colSpan - 1 );

        while ( 1 ) {

            var tmpBeginRowIndex = beginRowIndex,
                    tmpBeginCellIndex = beginCellIndex,
                    tmpEndRowIndex = endRowIndex,
                    tmpEndCellIndex = endCellIndex;
            if ( beginRowIndex > 0 ) {
                for ( cellIndex = beginCellIndex; cellIndex <= endCellIndex; ) {
                    var currentTopTd = rows[beginRowIndex].cells[cellIndex];
                    if ( _isHide( currentTopTd ) ) {
                        beginRowIndex = currentTopTd.getAttribute( 'rootRowIndex' );
                        currentTopTd = rows[currentTopTd.getAttribute( 'rootRowIndex' )].cells[currentTopTd.getAttribute( 'rootCellIndex' )];
                    }

                    cellIndex = getIndex( currentTopTd ) + (currentTopTd.colSpan || 1);
                }
            }
            if ( beginCellIndex > 0 ) {
                for ( var rowIndex = beginRowIndex; rowIndex <= endRowIndex; ) {
                    var currentLeftTd = rows[rowIndex].cells[beginCellIndex];
                    if ( _isHide( currentLeftTd ) ) {
                        beginCellIndex = currentLeftTd.getAttribute( 'rootCellIndex' );
                        currentLeftTd = rows[currentLeftTd.getAttribute( 'rootRowIndex' )].cells[currentLeftTd.getAttribute( 'rootCellIndex' )];
                    }
                    rowIndex = currentLeftTd.parentNode.rowIndex + (currentLeftTd.rowSpan || 1);
                }
            }
            if ( endRowIndex < rowsNum ) {
                for ( var cellIndex = beginCellIndex; cellIndex <= endCellIndex; ) {
                    var currentDownTd = rows[endRowIndex].cells[cellIndex];
                    if ( _isHide( currentDownTd ) ) {
                        currentDownTd = rows[currentDownTd.getAttribute( 'rootRowIndex' )].cells[currentDownTd.getAttribute( 'rootCellIndex' )];
                    }
                    endRowIndex = currentDownTd.parentNode.rowIndex + currentDownTd.rowSpan - 1;
                    cellIndex = getIndex( currentDownTd ) + (currentDownTd.colSpan || 1);
                }
            }
            if ( endCellIndex < cellsNum ) {
                for ( rowIndex = beginRowIndex; rowIndex <= endRowIndex; ) {
                    var currentRightTd = rows[rowIndex].cells[endCellIndex];
                    if ( _isHide( currentRightTd ) ) {
                        currentRightTd = rows[currentRightTd.getAttribute( 'rootRowIndex' )].cells[currentRightTd.getAttribute( 'rootCellIndex' )];
                    }
                    endCellIndex = getIndex( currentRightTd ) + currentRightTd.colSpan - 1;
                    rowIndex = currentRightTd.parentNode.rowIndex + (currentRightTd.rowSpan || 1);
                }
            }

            if ( tmpBeginCellIndex == beginCellIndex && tmpEndCellIndex == endCellIndex && tmpEndRowIndex == endRowIndex && tmpBeginRowIndex == beginRowIndex ) {
                break;
            }
        }
        return {
            beginRowIndex:beginRowIndex,
            beginCellIndex:beginCellIndex,
            endRowIndex:endRowIndex,
            endCellIndex:endCellIndex
        }
    }


    
    function _mouseDownEvent( type, evt ) {
        anchorTd = evt.target || evt.srcElement;

        if ( me.queryCommandState( 'highlightcode' ) || domUtils.findParent( anchorTd, function ( node ) {
            return node.tagName == "DIV" && /highlighter/.test( node.id );
        } ) ) {

            return;
        }

        if ( evt.button == 2 )return;
        me.document.body.style.webkitUserSelect = '';


        clearSelectedTd( me.currentSelectedArr );
        domUtils.clearSelectedArr( me.currentSelectedArr );
        if ( anchorTd.tagName !== 'TD' ) {
            anchorTd = domUtils.findParentByTagName( anchorTd, 'td' ) || anchorTd;
        }

        if ( anchorTd.tagName == 'TD' ) {


            me.addListener( 'mouseover', function ( type, evt ) {
                var tmpTd = evt.target || evt.srcElement;
                _mouseOverEvent.call( me, tmpTd );
                evt.preventDefault ? evt.preventDefault() : (evt.returnValue = false);
            } );

        } else {


            reset();
        }

    }

    
    function _mouseOverEvent( tmpTd ) {

        if ( anchorTd && tmpTd.tagName == "TD" ) {

            me.document.body.style.webkitUserSelect = 'none';
            var table = tmpTd.parentNode.parentNode.parentNode;
            me.selection.getNative()[browser.ie ? 'empty' : 'removeAllRanges']();
            var range = _getCellsRange( anchorTd, tmpTd );
            _toggleSelect( table, range );


        }
    }

    
    function _toggleSelect( table, cellsRange ) {
        var rows = table.rows;
        clearSelectedTd( me.currentSelectedArr );
        for ( var i = cellsRange.beginRowIndex; i <= cellsRange.endRowIndex; i++ ) {
            for ( var j = cellsRange.beginCellIndex; j <= cellsRange.endCellIndex; j++ ) {
                var td = rows[i].cells[j];
                td.className = "selectTdClass";
                me.currentSelectedArr.push( td );
            }
        }
    }
    function update( table ) {
        var tds = table.getElementsByTagName( 'td' ),
                rowIndex, cellIndex, rows = table.rows;
        for ( var j = 0, tj; tj = tds[j++]; ) {
            if ( !_isHide( tj ) ) {
                rowIndex = tj.parentNode.rowIndex;
                cellIndex = getIndex( tj );
                for ( var r = 0; r < tj.rowSpan; r++ ) {
                    var c = r == 0 ? 1 : 0;
                    for ( ; c < tj.colSpan; c++ ) {
                        var tmp = rows[rowIndex + r].children[cellIndex + c];


                        tmp.setAttribute( 'rootRowIndex', rowIndex );
                        tmp.setAttribute( 'rootCellIndex', cellIndex );

                    }
                }
            }
            if ( !_isHide( tj ) ) {
                domUtils.removeAttributes( tj, ['rootRowIndex', 'rootCellIndex'] );
            }
            if ( tj.colSpan && tj.colSpan == 1 ) {
                tj.removeAttribute( 'colSpan' )
            }
            if ( tj.rowSpan && tj.rowSpan == 1 ) {
                tj.removeAttribute( 'rowSpan' )
            }
            var width;
            if ( !_isHide( tj ) && (width = tj.style.width) && /%/.test( width ) ) {
                tj.style.width = Math.floor( 100 / tj.parentNode.cells.length ) + '%'
            }
        }
    }

    me.adjustTable = function ( cont ) {
        var table = cont.getElementsByTagName( 'table' );
        for ( var i = 0, ti; ti = table[i++]; ) {
            if ( ti.getAttribute( 'align' ) ) {
                var next = ti.nextSibling;
                while ( next ) {
                    if ( domUtils.isBlockElm( next ) ) {
                        break;
                    }
                    next = next.nextSibling;
                }
                if ( next ) {
                    next.style.clear = 'both';
                }
            }

            ti.removeAttribute( '_innerCreateTable' );
            var tds = domUtils.getElementsByTagName( ti, 'td' ),
                    td, tmpTd;

            for ( var j = 0, tj; tj = tds[j++]; ) {
                if ( domUtils.isEmptyNode( tj ) ) {
                    tj.innerHTML = browser.ie ? domUtils.fillChar : '<br/>';
                }
                var index = getIndex( tj ),
                        rowIndex = tj.parentNode.rowIndex,
                        rows = domUtils.findParentByTagName( tj, 'table' ).rows;

                for ( var r = 0; r < tj.rowSpan; r++ ) {
                    var c = r == 0 ? 1 : 0;
                    for ( ; c < tj.colSpan; c++ ) {

                        if ( !td ) {
                            td = tj.cloneNode( false );

                            td.rowSpan = td.colSpan = 1;
                            td.style.display = 'none';
                            td.innerHTML = browser.ie ? '' : '<br/>';


                        } else {
                            td = td.cloneNode( true )
                        }

                        td.setAttribute( 'rootRowIndex', tj.parentNode.rowIndex );
                        td.setAttribute( 'rootCellIndex', index );
                        if ( r == 0 ) {
                            if ( tj.nextSibling ) {
                                tj.parentNode.insertBefore( td, tj.nextSibling );
                            } else {
                                tj.parentNode.appendChild( td )
                            }
                        } else {
                            tmpTd = rows[rowIndex + r].children[index];
                            if ( tmpTd ) {
                                tmpTd.parentNode.insertBefore( td, tmpTd )
                            } else {
                                rows[rowIndex + r].appendChild( td )
                            }
                        }


                    }
                }


            }
            var bw = domUtils.getComputedStyle( ti, "border-width" );
            if ( bw == '0px' || (bw == "" && ti.getAttribute( "border" ) === "0") ) {
                ti.className = "noBorderTable";
            }

        }
        me.fireEvent( "afteradjusttable", cont );
    };
};

UE.commands['spechars'] = {
    queryCommandState : function(){
        return this.highlight ? -1 :0;
    }
};




    UE.plugins['list'] = function(){
        var me = this,
            notExchange = {
                'TD':1,
                'PRE':1,
                'BLOCKQUOTE':1
            };
        me.setOpt({
            'insertorderedlist':[
                ['1,2,3...','decimal'],
                ['a,b,c...','lower-alpha'],
                ['i,ii,iii...','lower-roman'],
                ['A,B,C','upper-alpha'],
                ['I,II,III...','upper-roman']
            ],
            'insertunorderedlist':[
                ['○ 小圆圈','circle'],
                ['● 小圆点','disc'],
                ['■ 小方块','square']
            ]
        });
        function adjustList(list,tag,style){
            var nextList = list.nextSibling;
            if(nextList && nextList.nodeType == 1 && nextList.tagName.toLowerCase() == tag && (domUtils.getStyle(nextList,'list-style-type')||(tag == 'ol'?'decimal' : 'disc')) == style){
                domUtils.moveChild(nextList,list);
                if(nextList.childNodes.length == 0){
                    domUtils.remove(nextList);
                }
            }
            var preList = list.previousSibling;
            if(preList && preList.nodeType == 1 && preList.tagName.toLowerCase() == tag && (domUtils.getStyle(preList,'list-style-type')||(tag == 'ol'?'decimal' : 'disc')) == style){
                domUtils.moveChild(list,preList)
            }


            if(list.childNodes.length == 0){
                domUtils.remove(list);
            }
        }
        me.addListener('keydown', function(type, evt) {
            function preventAndSave(){
                evt.preventDefault ? evt.preventDefault() : (evt.returnValue = false)
                me.undoManger && me.undoManger.save()
            }
            
            var keyCode = evt.keyCode || evt.which;
            if (keyCode == 13) {//回车
                
                var range = me.selection.getRange(),
                    start = domUtils.findParentByTagName(range.startContainer, ['ol','ul'], true,function(node){return node.tagName == 'TABLE'}),
                    end = domUtils.findParentByTagName(range.endContainer, ['ol','ul'], true,function(node){return node.tagName == 'TABLE'});
                if (start && end && start === end) {
                    
                    if(!range.collapsed){
                        start = domUtils.findParentByTagName(range.startContainer, 'li', true);
                        end = domUtils.findParentByTagName(range.endContainer, 'li', true);
                        if(start && end && start === end){
                            range.deleteContents();
                            li =  domUtils.findParentByTagName(range.startContainer, 'li', true);
                            if(li && domUtils.isEmptyBlock(li)){
                               
                                pre = li.previousSibling;
                                next = li.nextSibling;
                                p = me.document.createElement('p');
                              
                                domUtils.fillNode(me.document,p);
                                parentList = li.parentNode;
                                if(pre && next){
                                    range.setStart(next,0).collapse(true).select(true);
                                    domUtils.remove(li);

                                }else{
                                    if(!pre && !next || !pre){

                                        parentList.parentNode.insertBefore(p,parentList);



                                    } else{
                                        li.parentNode.parentNode.insertBefore(p,parentList.nextSibling);
                                    }
                                    domUtils.remove(li);
                                    if(!parentList.firstChild){
                                        domUtils.remove(parentList)
                                    }
                                    range.setStart(p,0).setCursor();


                                }
                                preventAndSave();
                                return;

                            }
                        }else{
                            var tmpRange = range.cloneRange(),
                                bk = tmpRange.collapse(false).createBookmark();

                            range.deleteContents();
                            tmpRange.moveToBookmark(bk);
                            var li = domUtils.findParentByTagName(tmpRange.startContainer, 'li', true),
                                pre = li.previousSibling,
                                next = li.nextSibling;

                            if (pre ) {
                                li = pre;
                                if(pre.firstChild && domUtils.isBlockElm(pre.firstChild)){
                                    pre = pre.firstChild;

                                }
                                if(domUtils.isEmptyNode(pre))
                                    domUtils.remove(li);
                            }
                            if (next ) {
                                li = next;
                                if(next.firstChild && domUtils.isBlockElm(next.firstChild)){
                                    next = next.firstChild;
                                }
                                if(domUtils.isEmptyNode(next))
                                    domUtils.remove(li);
                            }
                            tmpRange.select();
                            preventAndSave();
                            return;
                        }
                    }


                    li = domUtils.findParentByTagName(range.startContainer, 'li', true);

                    if (li) {
                        if(domUtils.isEmptyBlock(li)){
                            bk = range.createBookmark();
                            var parentList = li.parentNode;
                            if(li!==parentList.lastChild){
                                domUtils.breakParent(li,parentList);
                            }else{

                                parentList.parentNode.insertBefore(li,parentList.nextSibling);
                                if(domUtils.isEmptyNode(parentList)){
                                    domUtils.remove(parentList);
                                }
                            }
                            if(!dtd.$list[li.parentNode.tagName]){
                                if(!domUtils.isBlockElm(li.firstChild)){
                                    p = me.document.createElement('p');
                                    li.parentNode.insertBefore(p,li);
                                    while(li.firstChild){
                                        p.appendChild(li.firstChild);
                                    }
                                    domUtils.remove(li);
                                }else{
                                    domUtils.remove(li,true);
                                }
                            }
                            range.moveToBookmark(bk).select();


                        }else{
                            var first = li.firstChild;
                            if(!first || !domUtils.isBlockElm(first)){
                                 var p = me.document.createElement('p');
                                
                                !li.firstChild && domUtils.fillNode(me.document,p);
                                while(li.firstChild){

                                    p.appendChild(li.firstChild);
                                }
                                li.appendChild(p);
                                first = p;
                            }

                                var span = me.document.createElement('span');

                                range.insertNode(span);
                                domUtils.breakParent(span, li);

                                var nextLi = span.nextSibling;
                                first = nextLi.firstChild;

                                if (!first) {
                                    p = me.document.createElement('p');
                                    
                                    domUtils.fillNode(me.document,p);
                                    nextLi.appendChild(p);
                                    first = p;
                                }
                                if (domUtils.isEmptyNode(first)) {
                                    first.innerHTML = '';
                                    domUtils.fillNode(me.document,first);
                                }

                                range.setStart(first, 0).collapse(true).shrinkBoundary().select();
                                domUtils.remove(span);
                                pre = nextLi.previousSibling;
                                if(pre && domUtils.isEmptyBlock(pre)){
                                    pre.innerHTML = '<p></p>';
                                    domUtils.fillNode(me.document,pre.firstChild);
                                }

                            }

                        preventAndSave();
                    }


                }
            }
            if(keyCode == 8){
                range = me.selection.getRange();
                if (range.collapsed && domUtils.isStartInblock(range)) {
                   tmpRange = range.cloneRange().trimBoundary();
                   li = domUtils.findParentByTagName(range.startContainer, 'li', true);
                    if (li && domUtils.isStartInblock(tmpRange)) {

                        if (li && (pre = li.previousSibling)) {
                            if (keyCode == 46 && li.childNodes.length)
                                return;
                            if(dtd.$list[pre.tagName]){
                                pre = pre.lastChild;
                            }
                            me.undoManger && me.undoManger.save();
                            first = li.firstChild;
                            if (domUtils.isBlockElm(first)) {
                                if (domUtils.isEmptyNode(first)) {
                                    pre.appendChild(first);
                                    range.setStart(first,0).setCursor(false,true);
                                    while(li.firstChild){
                                        pre.appendChild(li.firstChild)
                                    }
                                } else {
                                    start = domUtils.findParentByTagName(range.startContainer, 'p', true);
                                    if(start && start !== first){
                                        return;
                                    }
                                    span = me.document.createElement('span');
                                    range.insertNode(span);
                                    domUtils.moveChild(li,pre);
                                    range.setStartBefore(span).collapse(true).select(true);

                                    domUtils.remove(span)

                                }
                            } else {
                                if (domUtils.isEmptyNode(li)) {
                                    var p = me.document.createElement('p');
                                    pre.appendChild(p);
                                     range.setStart(p,0).setCursor();
                                } else {
                                    range.setEnd(pre, pre.childNodes.length).collapse().select(true);
                                    while (li.firstChild) {
                                        pre.appendChild(li.firstChild)
                                    }


                                }
                            }

                            domUtils.remove(li);

                            me.undoManger && me.undoManger.save();
                            domUtils.preventDefault(evt);
                            return;

                        }

                        if (li && !li.previousSibling) {
                            first = li.firstChild;
                            if (!first ||  li.lastChild === first && domUtils.isEmptyNode(domUtils.isBlockElm(first) ? first : li)) {
                                var p = me.document.createElement('p');

                                li.parentNode.parentNode.insertBefore(p, li.parentNode);
                                domUtils.fillNode(me.document,p);
                                range.setStart(p, 0).setCursor();
                                domUtils.remove(!li.nextSibling ? li.parentNode : li);
                                me.undoManger && me.undoManger.save();
                                domUtils.preventDefault(evt);
                                return;
                            }


                        }


                    }


                }

            }
        });
        me.commands['insertorderedlist'] =
        me.commands['insertunorderedlist'] = {
            execCommand : function( command, style ) {
                if(!style){
                    style = command.toLowerCase() == 'insertorderedlist' ? 'decimal' : 'disc'
                }
                var me = this,
                    range = this.selection.getRange(),
                    filterFn = function( node ) {
                        return   node.nodeType == 1 ? node.tagName.toLowerCase() != 'br' : !domUtils.isWhitespace( node )
                    },
                    tag =  command.toLowerCase() == 'insertorderedlist' ? 'ol' : 'ul',
                    frag = me.document.createDocumentFragment();
                range.adjustmentBoundary().shrinkBoundary();
                var bko = range.createBookmark(true),
                    start = domUtils.findParentByTagName(me.document.getElementById(bko.start),'li'),
                    modifyStart = 0,
                    end = domUtils.findParentByTagName(me.document.getElementById(bko.end),'li'),
                    modifyEnd = 0,
                    startParent,endParent,
                    list,tmp;

                if(start || end){
                    start && (startParent = start.parentNode);
                    if(!bko.end){
                        end = start;
                    }
                    end && (endParent = end.parentNode);

                    if(startParent === endParent){
                        while(start !== end){
                            tmp = start;
                            start = start.nextSibling;
                            if(!domUtils.isBlockElm(tmp.firstChild)){
                                var p = me.document.createElement('p');
                                while(tmp.firstChild){
                                    p.appendChild(tmp.firstChild)
                                }
                                tmp.appendChild(p);
                            }
                            frag.appendChild(tmp);
                        }
                        tmp = me.document.createElement('span');
                        startParent.insertBefore(tmp,end);
                        if(!domUtils.isBlockElm(end.firstChild)){
                            p = me.document.createElement('p');
                            while(end.firstChild){
                                p.appendChild(end.firstChild)
                            }
                            end.appendChild(p);
                        }
                        frag.appendChild(end);
                        domUtils.breakParent(tmp,startParent);
                        if(domUtils.isEmptyNode(tmp.previousSibling)){
                            domUtils.remove(tmp.previousSibling)
                        }
                        if(domUtils.isEmptyNode(tmp.nextSibling)){
                            domUtils.remove(tmp.nextSibling)
                        }
                        var nodeStyle = domUtils.getComputedStyle( startParent, 'list-style-type' ) || (command.toLowerCase() == 'insertorderedlist' ? 'decimal' : 'disc');
                        if(startParent.tagName.toLowerCase() == tag && nodeStyle == style){
                            for(var i=0,ci,tmpFrag = me.document.createDocumentFragment();ci=frag.childNodes[i++];){
                                while(ci.firstChild){
                                    tmpFrag.appendChild(ci.firstChild);
                                }
                               
                            }
                            tmp.parentNode.insertBefore(tmpFrag,tmp);
                        }else{
                            list = me.document.createElement(tag);
                            domUtils.setStyle(list,'list-style-type',style);
                            list.appendChild(frag);
                            tmp.parentNode.insertBefore(list,tmp);
                        }

                        domUtils.remove(tmp);
                         list && adjustList(list,tag,style);
                        range.moveToBookmark(bko).select();
                        return;
                    }
                    if(start){
                        while(start){
                            tmp = start.nextSibling;
                            var tmpfrag = me.document.createDocumentFragment(),
                                hasBlock = 0;
                            while(start.firstChild){
                                if(domUtils.isBlockElm(start.firstChild))
                                    hasBlock = 1;
                                tmpfrag.appendChild(start.firstChild);
                            }
                            if(!hasBlock){
                                var tmpP = me.document.createElement('p');
                                tmpP.appendChild(tmpfrag);
                                frag.appendChild(tmpP)
                            }else{
                                frag.appendChild(tmpfrag);
                            }
                            domUtils.remove(start);
                            start = tmp;
                        }
                        startParent.parentNode.insertBefore(frag,startParent.nextSibling);
                        if(domUtils.isEmptyNode(startParent)){
                            range.setStartBefore(startParent);
                            domUtils.remove(startParent)
                        }else{
                           range.setStartAfter(startParent);
                        }


                         modifyStart = 1;
                    }

                    if(end){
                        start = endParent.firstChild;
                        while(start !== end){
                           tmp = start.nextSibling;

                           tmpfrag = me.document.createDocumentFragment();
                           hasBlock = 0;
                            while(start.firstChild){
                                if(domUtils.isBlockElm(start.firstChild))
                                    hasBlock = 1;
                                tmpfrag.appendChild(start.firstChild);
                            }
                            if(!hasBlock){
                                tmpP = me.document.createElement('p');
                                tmpP.appendChild(tmpfrag);
                                frag.appendChild(tmpP)
                            }else{
                                frag.appendChild(tmpfrag);
                            }
                            domUtils.remove(start);
                            start = tmp;
                        }
                        frag.appendChild(end.firstChild);
                        domUtils.remove(end);
                        endParent.parentNode.insertBefore(frag,endParent);
                        range.setEndBefore(endParent);
                        if(domUtils.isEmptyNode(endParent)){
                            domUtils.remove(endParent)
                        }

                         modifyEnd = 1;
                    }



                }

                if(!modifyStart){
                    range.setStartBefore(me.document.getElementById(bko.start))
                }
                if(bko.end && !modifyEnd){
                    range.setEndAfter(me.document.getElementById(bko.end))
                }
                range.enlarge(true,function(node){return notExchange[node.tagName] });

                frag = me.document.createDocumentFragment();

                var bk = range.createBookmark(),
                    current = domUtils.getNextDomNode( bk.start, false, filterFn ),
                    tmpRange = range.cloneRange(),
                    tmpNode,
                    block = domUtils.isBlockElm;

                while ( current && current !== bk.end && (domUtils.getPosition( current, bk.end ) & domUtils.POSITION_PRECEDING)  ) {

                    if ( current.nodeType == 3 || dtd.li[current.tagName] ) {
                        if(current.nodeType == 1 && dtd.$list[current.tagName]){
                            while(current.firstChild){
                                frag.appendChild(current.firstChild)
                            }
                            tmpNode = domUtils.getNextDomNode( current, false, filterFn );
                            domUtils.remove(current);
                            current = tmpNode;
                            continue;

                        }
                        tmpNode = current;
                        tmpRange.setStartBefore( current );

                        while ( current && current !== bk.end && (!block(current) || domUtils.isBookmarkNode(current) )) {
                            tmpNode = current;
                            current = domUtils.getNextDomNode( current, false, null, function( node ) {
                                return !notExchange[node.tagName]
                            } );
                        }

                        if(current && block(current)){
                            tmp = domUtils.getNextDomNode( tmpNode, false, filterFn );
                            if(tmp && domUtils.isBookmarkNode(tmp)){
                                current = domUtils.getNextDomNode( tmp, false, filterFn );
                                tmpNode = tmp;
                            }
                        }
                        tmpRange.setEndAfter( tmpNode );

                        current = domUtils.getNextDomNode( tmpNode, false, filterFn );

                        var li = range.document.createElement( 'li' );

                        li.appendChild(tmpRange.extractContents());
                        frag.appendChild(li);



                    } else {

                        current = domUtils.getNextDomNode( current, true, filterFn );
                    }
                }
                range.moveToBookmark(bk).collapse(true);
                list = me.document.createElement(tag);
                domUtils.setStyle(list,'list-style-type',style);
                list.appendChild(frag);
                range.insertNode(list);
                adjustList(list,tag,style);
                range.moveToBookmark(bko).select();

            },
            queryCommandState : function( command ) {
                return this.highlight ? -1 :
                    utils.findNode(this.selection.getStartElementPath(),[command.toLowerCase() == 'insertorderedlist' ? 'ol' : 'ul']) ? 1: 0;
            },
            queryCommandValue : function( command ) {
                var   node = utils.findNode(this.selection.getStartElementPath(),[command.toLowerCase() == 'insertorderedlist' ? 'ol' : 'ul']);
                return node ? domUtils.getComputedStyle( node, 'list-style-type' ) : null;
            }
        }
    };



UE.plugins['searchreplace'] = function(){

    var currentRange,
        first,
        me = this;
    me.addListener('reset',function(){
        currentRange = null;
        first = null;
    });
    me.commands['searchreplace'] = {

            execCommand : function(cmdName,opt){
               	var me = this,
                    sel = me.selection,
                    range,
                    nativeRange,
                    num = 0,
                opt = utils.extend(opt,{
                    all : false,
                    casesensitive : false,
                    dir : 1
                },true);


                if(browser.ie){
                    while(1){
                        var tmpRange;
                        nativeRange = me.document.selection.createRange();
                        tmpRange = nativeRange.duplicate();
                        tmpRange.moveToElementText(me.document.body);
                        if(opt.all){
                            first = 0;
                            opt.dir = 1;
                            
                            if(currentRange){
                                tmpRange.setEndPoint(opt.dir == -1 ? 'EndToStart' : 'StartToEnd',currentRange)
                            }
                        }else{
                            tmpRange.setEndPoint(opt.dir == -1 ? 'EndToStart' : 'StartToEnd',nativeRange);
                            if(opt.hasOwnProperty("replaceStr")){
                                tmpRange.setEndPoint(opt.dir == -1 ? 'StartToEnd' : 'EndToStart',nativeRange);
                            }
                        }
                        nativeRange = tmpRange.duplicate();



                        if(!tmpRange.findText(opt.searchStr,opt.dir,opt.casesensitive ? 4 : 0)){
                            currentRange = null;
                            tmpRange = me.document.selection.createRange();
                            tmpRange.scrollIntoView();
                            return num;
                        }
                        tmpRange.select();
                        if(opt.hasOwnProperty("replaceStr")){
                            range = sel.getRange();
                            range.deleteContents().insertNode(range.document.createTextNode(opt.replaceStr)).select();
                            currentRange = sel.getNative().createRange();

                        }
                        num++;
                        if(!opt.all)break;
                    }
                }else{
                    var w = me.window,nativeSel = sel.getNative(),tmpRange;
                    while(1){
                        if(opt.all){
                            if(currentRange){
                                currentRange.collapse(false);
                                nativeRange = currentRange;

                            }else{
                                nativeRange  = me.document.createRange();
                                nativeRange.setStart(me.document.body,0);

                            }
                            nativeSel.removeAllRanges();
                            nativeSel.addRange( nativeRange );
                            first = 0;
                            opt.dir = 1;
                        }else{
                            nativeRange = w.getSelection().getRangeAt(0);
                           
                            if(opt.hasOwnProperty("replaceStr")){
                                nativeRange.collapse(opt.dir == 1 ? true : false);
                            }
                        }
                       
                        if(!first){
                            nativeRange.collapse( opt.dir <0 ? true : false);
                            nativeSel.removeAllRanges();
                            nativeSel.addRange( nativeRange );
                        }else{
                            nativeSel.removeAllRanges();
                        }

                        if(!w.find(opt.searchStr,opt.casesensitive,opt.dir < 0 ? true : false) ) {
                            currentRange = null;
                            nativeSel.removeAllRanges();

                            return num;
                        }
                        first = 0;
                        range = w.getSelection().getRangeAt(0);
                        if(!range.collapsed){

                            if(opt.hasOwnProperty("replaceStr")){
                                range.deleteContents();
                                var text = w.document.createTextNode(opt.replaceStr);
                                range.insertNode(text);
                                range.selectNode(text);
                                nativeSel.addRange(range);
                                currentRange = range.cloneRange();
                            }
                        }
                        num++;
                        if(!opt.all)break;
                    }

                }
                return true;
            }
    }

};
UE.plugins['highlight'] = function() {
    var me = this;
    if(!/highlightcode/i.test(me.options.toolbars.join('')))return;
    me.commands['highlightcode'] = {
        execCommand: function (cmdName, code, syntax) {
            if(code && syntax){
                var pre = document.createElement("pre");
                pre.className = "brush: "+syntax+";toolbar:false;";
                pre.style.display = "";
                pre.appendChild(document.createTextNode(code));
                document.body.appendChild(pre);
                if(me.queryCommandState("highlightcode")){
                    me.execCommand("highlightcode");
                }
                me.execCommand('inserthtml', SyntaxHighlighter.highlight(pre,null,true),true);
                var div = me.document.getElementById(SyntaxHighlighter.getHighlighterDivId());
                div.setAttribute('highlighter',pre.className);
                domUtils.remove(pre);
                adjustHeight()
            }else{
                var range = this.selection.getRange(),
                   start = domUtils.findParentByTagName(range.startContainer, 'table', true),
                   end = domUtils.findParentByTagName(range.endContainer, 'table', true),
                   codediv;
                if(start && end && start === end && start.parentNode.className.indexOf("syntaxhighlighter")>-1){
                    codediv = start.parentNode;
                    if(domUtils.isBody(codediv.parentNode)){
                        var p = me.document.createElement('p');
                        p.innerHTML = browser.ie ? '' : '<br/>';
                        me.body.insertBefore(p,codediv);
                        range.setStart(p,0)
                    }else{
                        range.setStartBefore(codediv)
                    }
                    range.setCursor();
                    domUtils.remove(codediv);
                }
            }
        },
        queryCommandState: function(){
            var range = this.selection.getRange(),start,end;
            range.adjustmentBoundary();
                start = domUtils.findParent(range.startContainer,function(node){
                    return node.nodeType == 1 && node.tagName == 'DIV' && domUtils.hasClass(node,'syntaxhighlighter')
                },true);
                end = domUtils.findParent(range.endContainer,function(node){
                    return node.nodeType == 1 && node.tagName == 'DIV' && domUtils.hasClass(node,'syntaxhighlighter')
                },true);
            return start && end && start == end  ? 1 : 0;
        }
    };

    me.addListener('beforeselectionchange',function(){
        me.highlight = me.queryCommandState('highlightcode');
    });

    me.addListener('afterselectionchange',function(){
        me.highlight = 0;
    });
    me.addListener("ready",function(){
        if(typeof XRegExp == "undefined"){
            var obj = {
                id : "syntaxhighlighter_js",
                src : me.options.highlightJsUrl || me.options.UEDITOR_HOME_URL + "third-party/SyntaxHighlighter/shCore.js",
                tag : "script",
                type : "text/javascript",
                defer : "defer"
            };
            utils.loadFile(document,obj,function(){
                changePre();
            });
        }
        if(!me.document.getElementById("syntaxhighlighter_css")){
            var obj = {
                id : "syntaxhighlighter_css",
                tag : "link",
                rel : "stylesheet",
                type : "text/css",
                href : me.options.highlightCssUrl ||me.options.UEDITOR_HOME_URL + "third-party/SyntaxHighlighter/shCoreDefault.css"
            };
            utils.loadFile(me.document,obj);
        }

    });
    me.addListener("beforegetcontent",function(type,cmd){
        for(var i=0,di,divs=domUtils.getElementsByTagName(me.body,'div');di=divs[i++];){
            if(di.className == 'container'){
                var pN = di.parentNode;
                while(pN){
                    if(pN.tagName == 'DIV' && /highlighter/.test(pN.id)){
                        break;
                    }
                    pN = pN.parentNode;
                }
                if(!pN)return;
                var pre = me.document.createElement('pre');
                for(var str=[],c=0,ci;ci=di.childNodes[c++];){
                    str.push(ci[browser.ie?'innerText':'textContent']);
                }
                pre.appendChild(me.document.createTextNode(str.join('\n')));
                pre.className = pN.getAttribute('highlighter');
                pN.parentNode.insertBefore(pre,pN);
                domUtils.remove(pN);
            }
        }
    });
    me.addListener("aftergetcontent",function(type,cmd){
        changePre();
    });
    function adjustHeight(){
        setTimeout(function(){
            var div = me.document.getElementById(SyntaxHighlighter.getHighlighterDivId());

            if(div){
                var tds = div.getElementsByTagName('td');
                for(var i=0,li,ri;li=tds[0].childNodes[i];i++){
                    ri = tds[1].firstChild.childNodes[i];
                    if(ri){
                        ri.style.height = li.style.height = ri.offsetHeight + 'px';
                    }
                }

            }
        });

    }
    function changePre(){
        for(var i=0,pr,pres = domUtils.getElementsByTagName(me.document,"pre");pr=pres[i++];){
            if(pr.className.indexOf("brush")>-1){
                
                var pre = document.createElement("pre"),txt,div;
                pre.className = pr.className;
                pre.style.display = "none";
                pre.appendChild(document.createTextNode(pr[browser.ie?'innerText':'textContent']));
                document.body.appendChild(pre);
                try{
                    txt = SyntaxHighlighter.highlight(pre,null,true);
                }catch(e){
                    domUtils.remove(pre);
                    return ;
                }

                div = me.document.createElement("div");
                div.innerHTML = txt;

                div.firstChild.setAttribute('highlighter',pre.className);
                pr.parentNode.insertBefore(div.firstChild,pr);

                domUtils.remove(pre);
                domUtils.remove(pr);
                
                adjustHeight()
            }
        }
    }
    me.addListener("aftersetcontent",function(){
        
        changePre();
    });
    me.addListener('fullscreenchanged',function(){
        var div = domUtils.getElementsByTagName(me.document,'div');
        for(var j=0,di;di=div[j++];){
            if(/^highlighter/.test(di.id)){
                var tds = di.getElementsByTagName('td');
                for(var i=0,li,ri;li=tds[0].childNodes[i];i++){
                    ri = tds[1].firstChild.childNodes[i];

                    ri.style.height = li.style.height = ri.offsetHeight + 'px';
                }
            }
        }

    })
};

(function (){
    function SourceFormater(config){
        config = config || {};
        this.indentChar = config.indentChar || '    ';
        this.breakChar = config.breakChar || '\n';
        this.selfClosingEnd = config.selfClosingEnd || ' />';
    }
    var unhtml1 = function (){
        var map = { '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
        function rep( m ){ return map[m]; }
        return function ( str ) {
            str = str + '';
            return str ? str.replace( /[<>"']/g, rep ) : '';
        };
    }();
    var inline = utils.extend({a:1,A:1},dtd.$inline,true);


    function printAttrs(attrs){
        var buff = [];
        for (var k in attrs) {
            buff.push(k + '="' + unhtml1(attrs[k]) + '"');
        }
        return buff.join(' ');
    }
    SourceFormater.prototype = {
        format: function (html){
            var node = UE.serialize.parseHTML(html);
            this.buff = [];
            this.indents = '';
            this.indenting = 1;
            this.visitNode(node);
            return this.buff.join('');
        },
        visitNode: function (node){
            if (node.type == 'fragment') {
                this.visitChildren(node.children);
            } else if (node.type == 'element') {
                var selfClosing = dtd.$empty[node.tag];
                this.visitTag(node.tag, node.attributes, selfClosing);

                this.visitChildren(node.children);

                if (!selfClosing) {
                    this.visitEndTag(node.tag);
                }
            } else if (node.type == 'comment') {
                this.visitComment(node.data);
            } else {
                this.visitText(node.data,dtd.$notTransContent[node.parent.tag]);
            }
        },
        visitChildren: function (children){
            for (var i=0; i<children.length; i++) {
                this.visitNode(children[i]);
            }
        },
        visitTag: function (tag, attrs, selfClosing){
            if (this.indenting) {
                this.indent();
            } else if (!inline[tag]) {
                this.newline();
                this.indent();
            }
            this.buff.push('<', tag);
            var attrPart = printAttrs(attrs);
            if (attrPart) {
                this.buff.push(' ', attrPart);
            }
            if (selfClosing) {
                this.buff.push(this.selfClosingEnd);
                if (tag == 'br') {
                    this.newline();
                }
            } else {
                this.buff.push('>');
                this.indents += this.indentChar;
            }
            if (!inline[tag]) {
                this.newline();
            }
        },
        indent: function (){
            this.buff.push(this.indents);
            this.indenting = 0;
        },
        newline: function (){
            this.buff.push(this.breakChar);
            this.indenting = 1;
        },
        visitEndTag: function (tag){
            
            this.indents = this.indents.slice(0, -this.indentChar.length);
            if (this.indenting) {
                this.indent();
            } else if (!inline[tag]) {
                this.newline();
                this.indent();
            }
            this.buff.push('</', tag, '>');
        },
        visitText: function (text,notTrans){
            if (this.indenting) {
                this.indent();
            }
            text = text.replace(/&nbsp;/g, ' ')
            this.buff.push(text);

        },
        visitComment: function (text){
            if (this.indenting) {
                this.indent();
            }
            this.buff.push('<!--', text, '-->');
        }
    };

    var sourceEditors = {
        textarea: function (editor, holder){
            var textarea = holder.ownerDocument.createElement('textarea');
            textarea.style.cssText = 'position:absolute;resize:none;width:100%;height:100%;border:0;padding:0;margin:0;overflow-y:auto;';
            if (browser.ie && browser.version < 8) {
                textarea.style.width = holder.offsetWidth + 'px';
                textarea.style.height = holder.offsetHeight + 'px';
                holder.onresize = function (){
                    textarea.style.width = holder.offsetWidth + 'px';
                    textarea.style.height = holder.offsetHeight + 'px';
                };
            }
            holder.appendChild(textarea);
            return {
                setContent: function (content){
                    textarea.value = content;
                },
                getContent: function (){
                    return textarea.value;
                },
                select: function (){
                    var range;
                    if (browser.ie) {
                        range = textarea.createTextRange();
                        range.collapse(true);
                        range.select();
                    } else {
                        textarea.setSelectionRange(0, 0);
                        textarea.focus();
                    }
                },
                dispose: function (){
                    holder.removeChild(textarea);
                    holder.onresize = null;
                    textarea = null;
                    holder = null;
                }
            };
        },
        codemirror: function (editor, holder){
            var options = {
                mode: "text/html",
                tabMode: "indent",
                lineNumbers: true,
                lineWrapping:true
            };
            var codeEditor = window.CodeMirror(holder, options);
            var dom = codeEditor.getWrapperElement();
            dom.style.cssText = 'position:absolute;left:0;top:0;width:100%;height:100%;font-family:consolas,"Courier new",monospace;font-size:13px;';
            codeEditor.getScrollerElement().style.cssText = 'position:absolute;left:0;top:0;width:100%;height:100%;';
            codeEditor.refresh();
            return {
                setContent: function (content){
                    codeEditor.setValue(content);
                },
                getContent: function (){
                    return codeEditor.getValue();
                },
                select: function (){
                    codeEditor.focus();
                },
                dispose: function (){
                    holder.removeChild(dom);
                    dom = null;
                    codeEditor = null;
                }
            };
        }
    };

    UE.plugins['source'] = function (){
        var me = this;
        var opt = this.options;
        var formatter = new SourceFormater(opt.source);
        var sourceMode = false;
        var sourceEditor;
        opt.sourceEditor = opt.sourceEditor || 'codemirror';

        function createSourceEditor(holder){
            return sourceEditors[opt.sourceEditor == 'codemirror' && window.CodeMirror ? 'codemirror' : 'textarea'](me, holder);
        }

        var bakCssText;
        me.commands['source'] = {
            execCommand: function (){

                sourceMode = !sourceMode;
                if (sourceMode) {
                    me.undoManger && me.undoManger.save();
                    this.currentSelectedArr && domUtils.clearSelectedArr(this.currentSelectedArr);
                    if(browser.gecko)
                        me.body.contentEditable = false;
                    
                    bakCssText = me.iframe.style.cssText;
                    me.iframe.style.cssText += 'position:absolute;left:-32768px;top:-32768px;';

                    var content = formatter.format(me.hasContents() ? me.getContent() : '');

                    sourceEditor = createSourceEditor(me.iframe.parentNode);

                    sourceEditor.setContent(content);
                    setTimeout(function (){
                        sourceEditor.select();
                    });
                } else {
                    
                    me.iframe.style.cssText = bakCssText;
                    var cont = sourceEditor.getContent() || '<p>' + (browser.ie ? '' : '<br/>')+'</p>';
                    cont = cont.replace(/>[\n\r\t]+([ ]{4})+/g,'>').replace(/[\n\r\t]+([ ]{4})+</g,'<').replace(/>[\n\r\t]+</g,'><');


                    me.setContent(cont);
                    sourceEditor.dispose();
                    sourceEditor = null;
                    setTimeout(function(){
                        
                        var first = me.body.firstChild;
                        if(!first){
                            me.body.innerHTML = '<p>'+(browser.ie?'':'<br/>')+'</p>';
                            first = me.body.firstChild;
                        }
                        me.undoManger && me.undoManger.save();

                        while(first && first.firstChild){

                            first = first.firstChild;
                        }
                        var range = me.selection.getRange();
                        if(first.nodeType == 3 || dtd.$empty[first.tagName]){
                            range.setStartBefore(first)
                        }else{
                            range.setStart(first,0);
                        }

                        if(browser.gecko){

                            var input = document.createElement('input');
                            input.style.cssText = 'position:absolute;left:0;top:-32768px';

                            document.body.appendChild(input);

                            me.body.contentEditable = false;
                            setTimeout(function(){
                                domUtils.setViewportOffset(input, { left: -32768, top: 0 });
                                input.focus();
                                setTimeout(function(){
                                    me.body.contentEditable = true;
                                    range.setCursor(false,true);
                                    domUtils.remove(input)
                                })

                            })
                        }else{
                            range.setCursor(false,true);
                        }

                    })
                }
                this.fireEvent('sourcemodechanged', sourceMode);
            },
            queryCommandState: function (){
                return sourceMode|0;
            }
        };
        var oldQueryCommandState = me.queryCommandState;
        me.queryCommandState = function (cmdName){
            cmdName = cmdName.toLowerCase();
            if (sourceMode) {
                return cmdName == 'source' ? 1 : -1;
            }
            return oldQueryCommandState.apply(this, arguments);
        };
        var oldGetContent = me.getContent;
        me.getContent = function (){

            if(sourceMode && sourceEditor ){
                var html = sourceEditor.getContent();
                if (this.serialize) {
                    var node = this.serialize.parseHTML(html);
                    node = this.serialize.filter(node);
                    html = this.serialize.toHTML(node);
                }
                return html;
            }else{
                return oldGetContent.apply(this, arguments)
            }
        };
        if(opt.sourceEditor == "codemirror"){
            me.addListener("ready",function(){
                utils.loadFile(document,{
                    src : opt.codeMirrorJsUrl || opt.UEDITOR_HOME_URL + "third-party/codemirror2.15/codemirror.js",
                    tag : "script",
                    type : "text/javascript",
                    defer : "defer"
                });
                utils.loadFile(document,{
                    tag : "link",
                    rel : "stylesheet",
                    type : "text/css",
                    href : opt.codeMirrorCssUrl || opt.UEDITOR_HOME_URL + "third-party/codemirror2.15/codemirror.css"
                });

            });
        }

    };

})();

    UE.plugins['autolink'] = function() {
        var cont = 0;
        if (browser.ie) {
            return;
        }
        var me = this;
        me.addListener('reset',function(){
           cont = 0;
        });
        me.addListener('keydown', function(type, evt) {
            var keyCode = evt.keyCode || evt.which;

            if (keyCode == 32 || keyCode == 13) {

                var sel = me.selection.getNative(),
                    range = sel.getRangeAt(0).cloneRange(),
                    offset,
                    charCode;

                var start = range.startContainer;
                while (start.nodeType == 1 && range.startOffset > 0) {
                    start = range.startContainer.childNodes[range.startOffset - 1];
                    if (!start)
                        break;
                    range.setStart(start, start.nodeType == 1 ? start.childNodes.length : start.nodeValue.length);
                    range.collapse(true);
                    start = range.startContainer;
                }

                do{
                    if (range.startOffset == 0) {
                        start = range.startContainer.previousSibling;

                        while (start && start.nodeType == 1) {
                            start = start.lastChild;
                        }
                        if (!start || domUtils.isFillChar(start))
                            break;
                        offset = start.nodeValue.length;
                    } else {
                        start = range.startContainer;
                        offset = range.startOffset;
                    }
                    range.setStart(start, offset - 1);
                    charCode = range.toString().charCodeAt(0);
                } while (charCode != 160 && charCode != 32);

                if (range.toString().replace(new RegExp(domUtils.fillChar, 'g'), '').match(/(?:https?:\/\/|ssh:\/\/|ftp:\/\/|file:\/|www\.)/i)) {
                    while(range.toString().length){
                        if(/^(?:https?:\/\/|ssh:\/\/|ftp:\/\/|file:\/|www\.)/i.test(range.toString())){
                            break;
                        }
                        try{
                            range.setStart(range.startContainer,range.startOffset+1)
                        }catch(e){
                            var start = range.startContainer;
                            while(!(next = start.nextSibling)){
                                if(domUtils.isBody(start)){
                                    return;
                                }
                                start = start.parentNode;

                            }
                            range.setStart(next,0)

                        }

                    }
                    var a = me.document.createElement('a'),text = me.document.createTextNode(' '),href;

                    me.undoManger && me.undoManger.save();
                    a.appendChild(range.extractContents());
                    a.href = a.innerHTML = a.innerHTML.replace(/<[^>]+>/g,'');
                    href = a.getAttribute("href").replace(new RegExp(domUtils.fillChar,'g'),'');
                    href = /^(?:https?:\/\/)/ig.test(href) ? href : "http://"+ href;
                    a.setAttribute('data_ue_src',href);
                    a.href = href;

                    range.insertNode(a);
                    a.parentNode.insertBefore(text, a.nextSibling);
                    range.setStart(text, 0);
                    range.collapse(true);
                    sel.removeAllRanges();
                    sel.addRange(range);
                    me.undoManger && me.undoManger.save();
                }
            }
        })
    };
UE.commands['autosubmit'] = {
    execCommand:function () {
        var me=this,
            form = domUtils.findParentByTagName(me.iframe,"form", false);

        if (form)    {
            if(me.fireEvent("beforesubmit")===false) return;
            me.sync();
            form.submit();
        }

    }
};

UE.plugins['contextmenu'] = function () {
    var me = this,
        menu,
        items = me.options.contextMenu||[
            {label:'删除',cmdName:'delete'},
            {label:'全选',cmdName:'selectall'},
            {
                label:'删除代码',
                cmdName:'highlightcode',
                icon:'deletehighlightcode'

            },
            {
                label:'清空文档',
                cmdName:'cleardoc',
                exec:function () {

                    if ( confirm( '确定清空文档吗？' ) ) {

                        this.execCommand( 'cleardoc' );
                    }
                }
            },
            '-',
            {
                label:'取消链接',
                cmdName:'unlink'
            },
            '-',
            {
                group:'段落格式',
                icon:'justifyjustify',

                subMenu:[
                    {
                        label:'居左对齐',
                        cmdName:'justify',
                        value:'left'
                    },
                    {
                        label:'居右对齐',
                        cmdName:'justify',
                        value:'right'
                    },
                    {
                        label:'居中对齐',
                        cmdName:'justify',
                        value:'center'
                    },
                    {
                        label:'两端对齐',
                        cmdName:'justify',
                        value:'justify'
                    }
                ]
            },
            '-',
            {
                label:'表格属性',
                cmdName:'edittable',
                exec:function () {
                    this.ui._dialogs['inserttableDialog'].open();
                }
            },
            {
                label:'单元格属性',
                cmdName:'edittd',
                exec:function () {
                    if(UE.ui['edittd']){
                        new UE.ui['edittd'](this);
                    }
                    this.ui._dialogs['edittdDialog'].open();
                }
            },
            {
                group:'表格',
                icon:'table',

                subMenu:[
                    {
                        label:'删除表格',
                        cmdName:'deletetable'
                    },
                    {
                        label:'表格前插行',
                        cmdName:'insertparagraphbeforetable'
                    },
                    '-',
                    {
                        label:'删除行',
                        cmdName:'deleterow'
                    },
                    {
                        label:'删除列',
                        cmdName:'deletecol'
                    },
                    '-',
                    {
                        label:'前插入行',
                        cmdName:'insertrow'
                    },
                    {
                        label:'前插入列',
                        cmdName:'insertcol'
                    },
                    '-',
                    {
                        label:'右合并单元格',
                        cmdName:'mergeright'
                    },
                    {
                        label:'下合并单元格',
                        cmdName:'mergedown'
                    },
                    '-',
                    {
                        label:'拆分成行',
                        cmdName:'splittorows'
                    },
                    {
                        label:'拆分成列',
                        cmdName:'splittocols'
                    },
                    {
                        label:'合并多个单元格',
                        cmdName:'mergecells'
                    },
                    {
                        label:'完全拆分单元格',
                        cmdName:'splittocells'
                    }
                ]
            },
            {
                label:'复制(ctrl+c)',
                cmdName:'copy',
                exec:function () {
                    alert( "请使用ctrl+c进行复制" );
                },
                query : function(){return 0;}
            },
            {
                label:'粘贴(ctrl+v)',
                cmdName:'paste',
                exec:function () {
                    alert( "请使用ctrl+v进行粘贴" );
                },
                query : function(){return 0;}
            }
        ];
    if(!items.length)return;
    var uiUtils = UE.ui.uiUtils;
    me.addListener('contextmenu',function(type,evt){
        var offset = uiUtils.getViewportOffsetByEvent(evt);
        me.fireEvent('beforeselectionchange');
        if (menu)
            menu.destroy();
        for (var i = 0,ti,contextItems = []; ti = items[i]; i++) {
            var last;
            (function(item) {
                if (item == '-') {
                    if ((last = contextItems[contextItems.length - 1 ] ) && last !== '-')
                        contextItems.push('-');
                } else if (item.group) {

                        for (var j = 0,cj,subMenu = []; cj = item.subMenu[j]; j++) {
                            (function(subItem) {
                                if (subItem == '-') {
                                    if ((last = subMenu[subMenu.length - 1 ] ) && last !== '-')
                                        subMenu.push('-');

                                } else {
                                    if ((me.commands[subItem.cmdName] ||  UE.commands[subItem.cmdName]||subItem.query) &&
                                        (subItem.query ? subItem.query() : me.queryCommandState(subItem.cmdName)) > -1) {
                                        subMenu.push({
                                            'label':subItem.label,
                                            className: 'edui-for-' + subItem.cmdName + (subItem.value || ''),
                                            onclick : subItem.exec ? function() {
                                                subItem.exec.call(me)
                                            } : function() {
                                                me.execCommand(subItem.cmdName, subItem.value)
                                            }
                                        })
                                    }

                                }

                            })(cj)

                        }
                        if (subMenu.length) {
                            contextItems.push({
                                'label' : item.group,
                                className: 'edui-for-' + item.icon,
                                'subMenu' : {
                                    items: subMenu,
                                    editor:me
                                }
                            })
                        }

                } else {
                    if ((me.commands[item.cmdName] ||  UE.commands[item.cmdName]||item.query) &&
                        (item.query ? item.query() : me.queryCommandState(item.cmdName)) > -1) {
                        if(item.cmdName == 'highlightcode' && me.queryCommandState(item.cmdName) == 0)
                            return;
                        contextItems.push({
                            'label':item.label,
                            className: 'edui-for-' + (item.icon ? item.icon : item.cmdName + (item.value || '')),
                            onclick : item.exec ? function() {
                                item.exec.call(me)
                            } : function() {
                                me.execCommand(item.cmdName, item.value)
                            }
                        })
                    }

                }

            })(ti)
        }
        if (contextItems[contextItems.length - 1] == '-')
            contextItems.pop();
        menu = new UE.ui.Menu({
            items: contextItems,
            editor:me
        });
        menu.render();
        menu.showAt(offset);
        domUtils.preventDefault(evt);
        if(browser.ie){
            var ieRange;
            try{
                ieRange = me.selection.getNative().createRange();
            }catch(e){
               return;
            }
            if(ieRange.item){
                var range = new dom.Range(me.document);
                range.selectNode(ieRange.item(0)).select(true,true);

            }
        }
    })
};




UE.plugins['autoheight'] = function () {
    var me = this;
    me.autoHeightEnabled = me.options.autoHeightEnabled !== false ;
    if (!me.autoHeightEnabled)return;

    var bakOverflow,
        span, tmpNode,
        lastHeight = 0,
        currentHeight,
        timer;
    function adjustHeight() {
        clearTimeout(timer);
        timer = setTimeout(function () {
            if (me.queryCommandState('source') != 1) {
                if (!span) {
                    span = me.document.createElement('span');
                    span.style.cssText = 'display:block;width:0;margin:0;padding:0;border:0;clear:both;';
                    span.innerHTML = '.';
                }
                tmpNode = span.cloneNode(true);
                me.body.appendChild(tmpNode);

                currentHeight = Math.max(domUtils.getXY(tmpNode).y + tmpNode.offsetHeight, me.options.minFrameHeight);

                if (currentHeight != lastHeight) {

                    me.setHeight(currentHeight);

                    lastHeight = currentHeight;
                }

                domUtils.remove(tmpNode);

            }
        }, 100)
    }
    me.addListener('destroy', function () {
        me.removeListener('contentchange', adjustHeight);
        me.removeListener('keyup', adjustHeight);
        me.removeListener('mouseup', adjustHeight);
    });
    me.enableAutoHeight = function () {
        if(!me.autoHeightEnabled)return;
        var doc = me.document;
        me.autoHeightEnabled = true;
        bakOverflow = doc.body.style.overflowY;
        doc.body.style.overflowY = 'hidden';
        me.addListener('contentchange', adjustHeight);
        me.addListener('keyup', adjustHeight);
        me.addListener('mouseup', adjustHeight);
        setTimeout(function () {
            adjustHeight();
        }, browser.gecko ? 100 : 0);
        me.fireEvent('autoheightchanged', me.autoHeightEnabled);
    };
    me.disableAutoHeight = function () {

        me.body.style.overflowY = bakOverflow || '';

        me.removeListener('contentchange', adjustHeight);
        me.removeListener('keyup', adjustHeight);
        me.removeListener('mouseup', adjustHeight);
        me.autoHeightEnabled = false;
        me.fireEvent('autoheightchanged', me.autoHeightEnabled);
    };
    me.addListener('ready', function () {
        me.enableAutoHeight();
        var timer;
        domUtils.on(browser.ie ? me.body : me.document,browser.webkit ? 'dragover' : 'drop',function(){
            clearTimeout(timer);
            timer = setTimeout(function(){
                adjustHeight()
            },100)

        });
    });






};



    UE.plugins['autofloat'] = function() {
        var me = this,
            optsAutoFloatEnabled = me.options.autoFloatEnabled !== false;
        if(!optsAutoFloatEnabled){
            return;
        }
        var uiUtils = UE.ui.uiUtils,
       		LteIE6 = browser.ie && browser.version <= 6,
            quirks = browser.quirks;

        function checkHasUI(editor){
           if(!editor.ui){
              alert('autofloat插件功能依赖于UEditor UI\nautofloat定义位置: _src/plugins/autofloat.js');

              throw({
                  name: '未包含UI文件',
                  message: 'autofloat功能依赖于UEditor UI。autofloat定义位置: _src/plugins/autofloat.js'
              });
           }


           return 1;
       }
        function fixIE6FixedPos(){
            var docStyle = document.body.style;
           docStyle.backgroundImage = 'url("about:blank")';
           docStyle.backgroundAttachment = 'fixed';
        }



		var	bakCssText,
			placeHolder = document.createElement('div'),
            toolbarBox,orgTop,
            getPosition,
            flag =true;
		function setFloating(){
			var toobarBoxPos = domUtils.getXY(toolbarBox),
				origalFloat = domUtils.getComputedStyle(toolbarBox,'position'),
                origalLeft = domUtils.getComputedStyle(toolbarBox,'left');
			toolbarBox.style.width = toolbarBox.offsetWidth + 'px';
            toolbarBox.style.zIndex = me.options.zIndex * 1 + 1;
			toolbarBox.parentNode.insertBefore(placeHolder, toolbarBox);
			if (LteIE6 || quirks) {
                if(toolbarBox.style.position != 'absolute'){
                    toolbarBox.style.position = 'absolute';
                }

                toolbarBox.style.top = (document.body.scrollTop||document.documentElement.scrollTop) - orgTop + 'px';
			} else {
                if (browser.ie7Compat && flag) {
                    flag = false;
                    toolbarBox.style.left =  domUtils.getXY(toolbarBox).x - document.documentElement.getBoundingClientRect().left+2  + 'px';

                }
                if(toolbarBox.style.position != 'fixed'){
                    toolbarBox.style.position = 'fixed';
                    toolbarBox.style.top = '37px';

                    ((origalFloat == 'absolute' || origalFloat == 'relative') && parseFloat(origalLeft)) && (toolbarBox.style.left = toobarBoxPos.x + 'px');
                }

			}


		}
		function unsetFloating(){
            flag = true;
            if(placeHolder.parentNode)
			    placeHolder.parentNode.removeChild(placeHolder);

			toolbarBox.style.cssText = bakCssText;
		}

        function updateFloating(){
            var rect3 = getPosition(me.container);

            if (rect3.top < 0 && rect3.bottom - toolbarBox.offsetHeight > 0) {
                setFloating();
            }else{
                unsetFloating();
            }


        }
        var defer_updateFloating = utils.defer(function(){
            updateFloating();
        },browser.ie ? 200 : 100,true);

        me.addListener('destroy',function(){
            domUtils.un(window, ['scroll','resize'], updateFloating);
            me.removeListener('keydown', defer_updateFloating);
        });
        me.addListener('ready', function(){
            if(checkHasUI(me)){

                getPosition = uiUtils.getClientRect;
                toolbarBox = me.ui.getDom('toolbarbox');
                orgTop = getPosition(toolbarBox).top;
                bakCssText = toolbarBox.style.cssText;


                placeHolder.style.height = toolbarBox.offsetHeight + 'px';
                if(LteIE6){
                    fixIE6FixedPos();
                }
                me.addListener('autoheightchanged', function (t, enabled){
                    if (enabled) {
                        domUtils.on(window, ['scroll','resize'], updateFloating);
                        me.addListener('keydown', defer_updateFloating);
                    } else {
                        domUtils.un(window, ['scroll','resize'], updateFloating);
                        me.removeListener('keydown', defer_updateFloating);
                    }
                });

                me.addListener('beforefullscreenchange', function (t, enabled){
                    if (enabled) {
                        unsetFloating();
                    }
                });
                me.addListener('fullscreenchanged', function (t, enabled){
                    if (!enabled) {
                        updateFloating();
                    }
                });
                me.addListener('sourcemodechanged', function (t, enabled){
                    setTimeout(function (){
                        updateFloating();
                    });
                });
            }
        })
	};



UE.plugins['wordcount'] = function(){
    var me = this;
    me.setOpt({
        wordCount:true,
        maximumWords:10000,
        wordCountMsg:'当前已输入 {#count} 个字符，您还可以输入{#leave} 个字符 ',
        wordOverFlowMsg:'<span style="color:red;">你输入的字符个数已经超出最大允许值，服务器可能会拒绝保存！</span>'
    });
    var opt = me.options,
        max = opt.maximumWords,
        msg = opt.wordCountMsg ,
        errMsg = opt.wordOverFlowMsg;
    if(!opt.wordCount)return;
    me.commands["wordcount"]={
        queryCommandValue:function(cmd,onlyCount){
            var length,contentText,reg;
            if(onlyCount){
                reg = new RegExp("[\r\t\n]","g");
                contentText = this.getContentTxt().replace(reg,"");
                return contentText.length;
            }
            reg = new RegExp("[\r\t\n]","g");
            contentText = this.getContentTxt().replace(reg,"");
            length = contentText.length;
            if(max-length<0){
                me.fireEvent('wordcountoverflow');
                return errMsg
            }

            return msg.replace("{#leave}",max-length >= 0 ? max-length:0).replace("{#count}",length);;
        }
    };
};


UE.plugins['elementpath'] = function(){

    var currentLevel,
        tagNames,
        me = this;
    me.setOpt('elementPathEnabled',true);
    if(!me.options.elementPathEnabled)return;
    me.commands['elementpath'] = {
        execCommand : function( cmdName, level ) {
            var start = tagNames[level],
                range = me.selection.getRange();
            me.currentSelectedArr && domUtils.clearSelectedArr(me.currentSelectedArr);
            currentLevel = level*1;
            if(dtd.$tableContent[start.tagName]){
                switch (start.tagName){
                    case 'TD':me.currentSelectedArr = [start];
                            start.className = me.options.selectedTdClass;
                            break;
                    case 'TR':
                        var cells = start.cells;
                        for(var i=0,ti;ti=cells[i++];){
                            me.currentSelectedArr.push(ti);
                            ti.className = me.options.selectedTdClass;
                        }
                        break;
                    case 'TABLE':
                    case 'TBODY':

                        var rows = start.rows;
                        for(var i=0,ri;ri=rows[i++];){
                            cells = ri.cells;
                            for(var j=0,tj;tj=cells[j++];){
                                 me.currentSelectedArr.push(tj);
                                tj.className = me.options.selectedTdClass;
                            }
                        }

                }
                start = me.currentSelectedArr[0];
                if(domUtils.isEmptyNode(start)){
                    range.setStart(start,0).setCursor()
                }else{
                   range.selectNodeContents(start).select()
                }
            }else{
                range.selectNode(start).select()

            }
        },
        queryCommandValue : function() {
            var parents = [].concat(this.selection.getStartElementPath()).reverse(),
                names = [];
            tagNames = parents;
            for(var i=0,ci;ci=parents[i];i++){
                if(ci.nodeType == 3) continue;
                var name = ci.tagName.toLowerCase();
                if(name == 'img' && ci.getAttribute('anchorname')){
                    name = 'anchor'
                }
                names[i] = name;
                if(currentLevel == i){
                   currentLevel = -1;
                    break;
                }
            }
            return names;
        }
    }


};




UE.plugins['undo'] = function() {
    var me = this,
        maxUndoCount = me.options.maxUndoCount || 20,
        maxInputCount = me.options.maxInputCount || 20,
        fillchar = new RegExp(domUtils.fillChar + '|<\/hr>','gi'),// ie会产生多余的</hr>
        specialAttr = /\b(?:href|src|name)="[^"]*?"/gi;

    function UndoManager() {

        this.list = [];
        this.index = 0;
        this.hasUndo = false;
        this.hasRedo = false;
        this.undo = function() {

            if ( this.hasUndo ) {
                var currentScene = this.getScene(),
                    lastScene = this.list[this.index];
                if ( lastScene.content.replace(specialAttr,'') != currentScene.content.replace(specialAttr,'') ) {
                    this.save();
                }
                                    if(!this.list[this.index - 1] && this.list.length == 1){
                    this.reset();
                    return;
                }
                while ( this.list[this.index].content == this.list[this.index - 1].content ) {
                    this.index--;
                    if ( this.index == 0 ) {
                        return this.restore( 0 )
                    }
                }
                this.restore( --this.index );
            }
        };
        this.redo = function() {
            if ( this.hasRedo ) {
                while ( this.list[this.index].content == this.list[this.index + 1].content ) {
                    this.index++;
                    if ( this.index == this.list.length - 1 ) {
                        return this.restore( this.index )
                    }
                }
                this.restore( ++this.index );
            }
        };

        this.restore = function() {

            var scene = this.list[this.index];
            me.document.body.innerHTML = scene.bookcontent.replace(fillchar,'');
            if(browser.ie){
                for(var i=0,pi,ps = me.document.getElementsByTagName('p');pi = ps[i++];){
                    if(pi.innerHTML == ''){
                        domUtils.fillNode(me.document,pi);
                    }
                }
            }

            var range = new dom.Range( me.document );
            range.moveToBookmark( {
                start : '_baidu_bookmark_start_',
                end : '_baidu_bookmark_end_',
                id : true
            } );
            if(browser.ie && browser.version == 9 && range.collapsed && domUtils.isBlockElm(range.startContainer) && domUtils.isEmptyNode(range.startContainer)){
                domUtils.fillNode(range.document,range.startContainer);

            }
            range.select(!browser.gecko);
             setTimeout(function(){
                range.scrollToView(me.autoHeightEnabled,me.autoHeightEnabled ? domUtils.getXY(me.iframe).y:0);
            },200);

            this.update();
            if(me.currentSelectedArr){
                me.currentSelectedArr = [];
                var tds = me.document.getElementsByTagName('td');
                for(var i=0,td;td=tds[i++];){
                    if(td.className == me.options.selectedTdClass){
                         me.currentSelectedArr.push(td);
                    }
                }
            }
             this.clearKey();
            me.fireEvent('reset',true);
            me.fireEvent('contentchange')
        };

        this.getScene = function() {
            var range = me.selection.getRange(),
                cont = me.body.innerHTML.replace(fillchar,'');
            range.shrinkBoundary();
            browser.ie && (cont = cont.replace(/>&nbsp;</g,'><').replace(/\s*</g,'').replace(/>\s*/g,'>'));
            var bookmark = range.createBookmark( true, true ),
                bookCont = me.body.innerHTML.replace(fillchar,'');

            range.moveToBookmark( bookmark ).select( true );
            return {
                bookcontent : bookCont,
                content : cont
            }
        };
        this.save = function() {

            var currentScene = this.getScene(),
                lastScene = this.list[this.index];
            if ( lastScene && lastScene.content == currentScene.content &&
                    lastScene.bookcontent == currentScene.bookcontent
            ) {
                return;
            }

            this.list = this.list.slice( 0, this.index + 1 );
            this.list.push( currentScene );
            if ( this.list.length > maxUndoCount ) {
                this.list.shift();
            }
            this.index = this.list.length - 1;
            this.clearKey();
            this.update();
            me.fireEvent('contentchange')
        };
        this.update = function() {
            this.hasRedo = this.list[this.index + 1] ? true : false;
            this.hasUndo = this.list[this.index - 1] || this.list.length == 1 ? true : false;

        };
        this.reset = function() {
            this.list = [];
            this.index = 0;
            this.hasUndo = false;
            this.hasRedo = false;
            this.clearKey();


        };
        this.clearKey = function(){
             keycont = 0;
            lastKeyCode = null;
        }
    }

    me.undoManger = new UndoManager();
    function saveScene() {

        this.undoManger.save()
    }

    me.addListener( 'beforeexeccommand', saveScene );
    me.addListener( 'afterexeccommand', saveScene );

    me.addListener('reset',function(type,exclude){
        if(!exclude)
            me.undoManger.reset();
    });
    me.commands['redo'] = me.commands['undo'] = {
        execCommand : function( cmdName ) {
            me.undoManger[cmdName]();
        },
        queryCommandState : function( cmdName ) {

            return me.undoManger['has' + (cmdName.toLowerCase() == 'undo' ? 'Undo' : 'Redo')] ? 0 : -1;
        },
        notNeedUndo : 1
    };

    var keys = {
             16:1,  17:1,  18:1,
            37:1, 38:1, 39:1, 40:1,
            13:1 
        },
        keycont = 0,
        lastKeyCode;

    me.addListener( 'keydown', function( type, evt ) {
        var keyCode = evt.keyCode || evt.which;

        if ( !keys[keyCode] && !evt.ctrlKey && !evt.metaKey && !evt.shiftKey && !evt.altKey ) {

            if ( me.undoManger.list.length == 0 || ((keyCode == 8 ||keyCode == 46) && lastKeyCode != keyCode) ) {

                me.undoManger.save();
                lastKeyCode = keyCode;
                return

            }
            if(me.undoManger.list.length == 2 && me.undoManger.index == 0 && keycont == 0){
                me.undoManger.list.splice(1,1);
                me.undoManger.update();
            }
            lastKeyCode = keyCode;
            keycont++;
            if ( keycont > maxInputCount ) {

                setTimeout( function() {
                    me.undoManger.save();
                }, 0 );

            }
        }
    } )
};


UE.plugins['selectall'] = function(){
    var me = this;
    me.commands['selectall'] = {
        execCommand : function(){
            var range = this.selection.getRange();
            range.selectNodeContents(this.body);
            if(domUtils.isEmptyBlock(this.body))
                range.collapse(true);
            range.select(true);
            this.selectAll = true;
        },
        notNeedUndo : 1
    };

    me.addListener('ready',function(){

        domUtils.on(me.document,'click',function(evt){

            me.selectAll = false;
        })
    })

};

UE.plugins['keystrokes'] = function() {
    var me = this,
        flag = 0,
        keys = domUtils.keys,
        trans = {
            'B' : 'strong',
            'I' : 'em',
            'FONT' : 'span'
        },
        sizeMap = [0, 10, 12, 16, 18, 24, 32, 48],
        listStyle = {
            'OL':['decimal','lower-alpha','lower-roman','upper-alpha','upper-roman'],

            'UL':[ 'circle','disc','square']
        };
    me.addListener('keydown', function(type, evt) {
        var keyCode = evt.keyCode || evt.which;

        if(this.selectAll){
            this.selectAll = false;
            if((keyCode == 8 || keyCode == 46)){
                me.undoManger && me.undoManger.save();
                me.body.innerHTML = '<p>'+(browser.ie ? '' : '<br/>')+'</p>';

                new dom.Range(me.document).setStart(me.body.firstChild,0).setCursor(false,true);
                me.undoManger && me.undoManger.save();
                browser.ie && me._selectionChange();
                domUtils.preventDefault(evt);
                return;
            }


        }
        if (keyCode == 8 ) {//|| keyCode == 46


            var range = me.selection.getRange(),
                tmpRange,
                start,end;
            if(range.collapsed){
                start = range.startContainer;
                if(domUtils.isWhitespace(start)){
                    start = start.parentNode;
                }
                if(domUtils.isEmptyNode(start) && start === me.body.firstChild){

                    if(start.tagName != 'P'){
                        p = me.document.createElement('p');
                        me.body.insertBefore(p,start);
                        domUtils.fillNode(me.document,p);
                        range.setStart(p,0).setCursor(false,true);
                        domUtils.remove(start);
                    }
                    domUtils.preventDefault(evt);
                    return;

                }
            }

            if (range.collapsed && range.startContainer.nodeType == 3 && range.startContainer.nodeValue.replace(new RegExp(domUtils.fillChar, 'g'), '').length == 0) {
                range.setStartBefore(range.startContainer).collapse(true)
            }
            if (start = range.getClosedNode()) {
                me.undoManger && me.undoManger.save();
                range.setStartBefore(start);
                domUtils.remove(start);
                range.setCursor();
                me.undoManger && me.undoManger.save();
                domUtils.preventDefault(evt);
                return;
            }
            if (!browser.ie) {

                start = domUtils.findParentByTagName(range.startContainer, 'table', true);
                end = domUtils.findParentByTagName(range.endContainer, 'table', true);
                if (start && !end || !start && end || start !== end) {
                    evt.preventDefault();
                    return;
                }
            }


            if (me.undoManger) {

                if (!range.collapsed) {
                    me.undoManger.save();
                    flag = 1;
                }
            }

        }
        if (keyCode == 9) {
            range = me.selection.getRange();
            me.undoManger && me.undoManger.save();

            for (var i = 0,txt = '',tabSize = me.options.tabSize|| 4,tabNode =  me.options.tabNode || '&nbsp;'; i < tabSize; i++) {
                txt += tabNode;
            }
            var span = me.document.createElement('span');
            span.innerHTML = txt;
            if (range.collapsed) {

                var li = domUtils.findParentByTagName(range.startContainer, 'li', true);

                if (li && domUtils.isStartInblock(range)) {
                    bk = range.createBookmark();
                    var parentLi = li.parentNode,
                        list = me.document.createElement(parentLi.tagName);
                    var index = utils.indexOf(listStyle[list.tagName], domUtils.getComputedStyle(parentLi, 'list-style-type'));
                    index = index + 1 == listStyle[list.tagName].length ? 0 : index + 1;
                    domUtils.setStyle(list, 'list-style-type', listStyle[list.tagName][index]);

                    parentLi.insertBefore(list, li);
                    list.appendChild(li);
                    range.moveToBookmark(bk).select()

                } else
                    range.insertNode(span.cloneNode(true).firstChild).setCursor(true);

            } else {
                start = domUtils.findParentByTagName(range.startContainer, 'table', true);
                end = domUtils.findParentByTagName(range.endContainer, 'table', true);
                if (start || end) {
                    evt.preventDefault ? evt.preventDefault() : (evt.returnValue = false);
                    return
                }
                start = domUtils.findParentByTagName(range.startContainer, ['ol','ul'], true);
                end = domUtils.findParentByTagName(range.endContainer, ['ol','ul'], true);
                if (start && end && start === end) {
                    var bk = range.createBookmark();
                    start = domUtils.findParentByTagName(range.startContainer, 'li', true);
                    end = domUtils.findParentByTagName(range.endContainer, 'li', true);
                    if (start === start.parentNode.firstChild) {
                        var parentList = me.document.createElement(start.parentNode.tagName);

                        start.parentNode.parentNode.insertBefore(parentList, start.parentNode);
                        parentList.appendChild(start.parentNode);
                    } else {
                        parentLi = start.parentNode,
                            list = me.document.createElement(parentLi.tagName);

                        index = utils.indexOf(listStyle[list.tagName], domUtils.getComputedStyle(parentLi, 'list-style-type'));
                        index = index + 1 == listStyle[list.tagName].length ? 0 : index + 1;
                        domUtils.setStyle(list, 'list-style-type', listStyle[list.tagName][index]);
                        start.parentNode.insertBefore(list, start);
                        var nextLi;
                        while (start !== end) {
                            nextLi = start.nextSibling;
                            list.appendChild(start);
                            start = nextLi;
                        }
                        list.appendChild(end);

                    }
                    range.moveToBookmark(bk).select();

                } else {
                    if (start || end) {
                        evt.preventDefault ? evt.preventDefault() : (evt.returnValue = false);
                        return
                    }
                    start = domUtils.findParent(range.startContainer, filterFn);
                    end = domUtils.findParent(range.endContainer, filterFn);
                    if (start && end && start === end) {
                        range.deleteContents();
                        range.insertNode(span.cloneNode(true).firstChild).setCursor(true);
                    } else {
                        var bookmark = range.createBookmark(),
                            filterFn = function(node) {
                                return domUtils.isBlockElm(node);

                            };

                        range.enlarge(true);
                        var bookmark2 = range.createBookmark(),
                            current = domUtils.getNextDomNode(bookmark2.start, false, filterFn);


                        while (current && !(domUtils.getPosition(current, bookmark2.end) & domUtils.POSITION_FOLLOWING)) {


                            current.insertBefore(span.cloneNode(true).firstChild, current.firstChild);

                            current = domUtils.getNextDomNode(current, false, filterFn);

                        }

                        range.moveToBookmark(bookmark2).moveToBookmark(bookmark).select();
                    }

                }


            }
            me.undoManger && me.undoManger.save();
            evt.preventDefault ? evt.preventDefault() : (evt.returnValue = false);
        }
        if(browser.gecko && keyCode == 46){
            range = me.selection.getRange();
            if(range.collapsed){
                start = range.startContainer;
                if(domUtils.isEmptyBlock(start)){
                    var parent = start.parentNode;
                    while(domUtils.getChildCount(parent) == 1 && !domUtils.isBody(parent)){
                        start = parent;
                        parent = parent.parentNode;
                    }
                    if(start === parent.lastChild)
                        evt.preventDefault();
                    return;
                }
            }
        }
    });
    me.addListener('keyup', function(type, evt) {
        var keyCode = evt.keyCode || evt.which;
        if (!browser.gecko && !keys[keyCode] && !evt.ctrlKey && !evt.metaKey && !evt.shiftKey && !evt.altKey) {
            range = me.selection.getRange();
            if (range.collapsed) {
                var start = range.startContainer,
                    isFixed = 0;

                while (!domUtils.isBlockElm(start)) {
                    if (start.nodeType == 1 && utils.indexOf(['FONT','B','I'], start.tagName) != -1) {

                        var tmpNode = me.document.createElement(trans[start.tagName]);
                        if (start.tagName == 'FONT') {
                            tmpNode.style.cssText = (start.getAttribute('size') ? 'font-size:' + (sizeMap[start.getAttribute('size')] || 12) + 'px' : '')
                                + ';' + (start.getAttribute('color') ? 'color:' + start.getAttribute('color') : '')
                                + ';' + (start.getAttribute('face') ? 'font-family:' + start.getAttribute('face') : '')
                                + ';' + start.style.cssText;
                        }
                        while (start.firstChild) {
                            tmpNode.appendChild(start.firstChild)
                        }
                        start.parentNode.insertBefore(tmpNode, start);
                        domUtils.remove(start);
                        if (!isFixed) {
                            range.setEnd(tmpNode, tmpNode.childNodes.length).collapse(true)

                        }
                        start = tmpNode;
                        isFixed = 1;
                    }
                    start = start.parentNode;

                }

                isFixed && range.select()

            }
        }

        if (keyCode == 8 ) {//|| keyCode == 46
            if(browser.gecko){
                for(var i=0,li,lis = domUtils.getElementsByTagName(this.body,'li');li=lis[i++];){
                    if(domUtils.isEmptyNode(li) && !li.previousSibling){
                        var liOfPn = li.parentNode;
                        domUtils.remove(li);
                        if(domUtils.isEmptyNode(liOfPn)){
                            domUtils.remove(liOfPn)
                        }

                    }
                }
            }

            var range,start,parent,
                tds = this.currentSelectedArr;
            if (tds && tds.length > 0) {
                for (var i = 0,ti; ti = tds[i++];) {
                    ti.innerHTML = browser.ie ? ( browser.version < 9 ? '&#65279' : '' ) : '<br/>';

                }
                range = new dom.Range(this.document);
                range.setStart(tds[0], 0).setCursor();
                if (flag) {
                    me.undoManger.save();
                    flag = 0;
                }
                if (browser.webkit) {
                    evt.preventDefault();
                }
                return;
            }

            range = me.selection.getRange();

            start = range.startContainer;
            if(domUtils.isWhitespace(start)){
                start = start.parentNode
            }
            var removeFlag = 0;
            while (start.nodeType == 1 && domUtils.isEmptyNode(start) && dtd.$removeEmpty[start.tagName]) {
                removeFlag = 1;
                parent = start.parentNode;
                domUtils.remove(start);
                start = parent;
            }

            if ( removeFlag && start.nodeType == 1 && domUtils.isEmptyNode(start)) {
                if (browser.ie) {
                    var span = range.document.createElement('span');
                    start.appendChild(span);
                    range.setStart(start,0).setCursor();
                    li = domUtils.findParentByTagName(start,'li',true);
                    if(li){
                        var next = li.nextSibling;
                        while(next){
                            if(domUtils.isEmptyBlock(next)){
                                li = next;
                                next = next.nextSibling;
                                domUtils.remove(li);
                                continue;

                            }
                            break;
                        }
                    }
                } else {
                    start.innerHTML = '<br/>';
                    range.setStart(start, 0).setCursor(false,true);
                }

                setTimeout(function() {
                    if (browser.ie) {
                        domUtils.remove(span);
                    }

                    if (flag) {
                        me.undoManger.save();
                        flag = 0;
                    }
                }, 0)
            } else {

                if (flag) {
                    me.undoManger.save();
                    flag = 0;
                }

            }
        }
    })
};
UE.plugins['serialize'] = function () {
    var ie = browser.ie,
        version = browser.version;

    function ptToPx(value){
        return /pt/.test(value) ? value.replace( /([\d.]+)pt/g, function( str ) {
            return  Math.round(parseFloat(str) * 96 / 72) + "px";
        } ) : value;
    }
    var me = this, autoClearEmptyNode = me.options.autoClearEmptyNode,
            EMPTY_TAG = dtd.$empty,
            parseHTML = function () {
                var RE_PART = /<(?:(?:\/([^>]+)>)|(?:!--([\S|\s]*?)-->)|(?:([^\s\/>]+)\s*((?:(?:"[^"]*")|(?:'[^']*')|[^"'<>])*)\/?>))/g,
                        RE_ATTR = /([\w\-:.]+)(?:(?:\s*=\s*(?:(?:"([^"]*)")|(?:'([^']*)')|([^\s>]+)))|(?=\s|$))/g,
                                        EMPTY_ATTR = {checked:1,compact:1,declare:1,defer:1,disabled:1,ismap:1,multiple:1,nohref:1,noresize:1,noshade:1,nowrap:1,readonly:1,selected:1},
                                        CDATA_TAG = {script:1,style: 1},
                                        NEED_PARENT_TAG = {
                                            "li": { "$": 'ul', "ul": 1, "ol": 1 },
                                            "dd": { "$": "dl", "dl": 1 },
                                            "dt": { "$": "dl", "dl": 1 },
                                            "option": { "$": "select", "select": 1 },
                                            "td": { "$": "tr", "tr": 1 },
                                            "th": { "$": "tr", "tr": 1 },
                                            "tr": { "$": "tbody", "tbody": 1, "thead": 1, "tfoot": 1, "table": 1 },
                                            "tbody": { "$": "table", 'table':1,"colgroup": 1 },
                                            "thead": { "$": "table", "table": 1 },
                                            "tfoot": { "$": "table", "table": 1 },
                                            "col": { "$": "colgroup","colgroup":1 }
                                        };
                                var NEED_CHILD_TAG = {
                    "table": "td", "tbody": "td", "thead": "td", "tfoot": "td", "tr": "td",
                    "colgroup": "col",
                    "ul": "li", "ol": "li",
                    "dl": "dd",
                    "select": "option"
                };

                function parse( html, callbacks ) {

                    var match,
                            nextIndex = 0,
                            tagName,
                            cdata;
                    RE_PART.exec( "" );
                    while ( (match = RE_PART.exec( html )) ) {

                        var tagIndex = match.index;
                        if ( tagIndex > nextIndex ) {
                            var text = html.slice( nextIndex, tagIndex );
                            if ( cdata ) {
                                cdata.push( text );
                            } else {
                                callbacks.onText( text );
                            }
                        }
                        nextIndex = RE_PART.lastIndex;
                        if ( (tagName = match[1]) ) {
                            tagName = tagName.toLowerCase();
                            if ( cdata && tagName == cdata._tag_name ) {
                                callbacks.onCDATA( cdata.join( '' ) );
                                cdata = null;
                            }
                            if ( !cdata ) {
                                callbacks.onTagClose( tagName );
                                continue;
                            }
                        }
                        if ( cdata ) {
                            cdata.push( match[0] );
                            continue;
                        }
                        if ( (tagName = match[3]) ) {
                            if ( /="/.test( tagName ) ) {
                                continue;
                            }
                            tagName = tagName.toLowerCase();
                            var attrPart = match[4],
                                    attrMatch,
                                    attrMap = {},
                                    selfClosing = attrPart && attrPart.slice( -1 ) == '/';
                            if ( attrPart ) {
                                RE_ATTR.exec( "" );
                                while ( (attrMatch = RE_ATTR.exec( attrPart )) ) {
                                    var attrName = attrMatch[1].toLowerCase(),
                                            attrValue = attrMatch[2] || attrMatch[3] || attrMatch[4] || '';
                                    if ( !attrValue && EMPTY_ATTR[attrName] ) {
                                        attrValue = attrName;
                                    }
                                    if ( attrName == 'style' ) {
                                        if ( ie && version <= 6 ) {
                                            attrValue = attrValue.replace( /(?!;)\s*([\w-]+):/g, function ( m, p1 ) {
                                                return p1.toLowerCase() + ':';
                                            } );
                                        }
                                    }
                                    if ( attrValue ) {
                                        attrMap[attrName] = attrValue.replace( /:\s*/g, ':' )
                                    }

                                }
                            }
                            callbacks.onTagOpen( tagName, attrMap, selfClosing );
                            if ( !cdata && CDATA_TAG[tagName] ) {
                                cdata = [];
                                cdata._tag_name = tagName;
                            }
                            continue;
                        }
                        if ( (tagName = match[2]) ) {
                            callbacks.onComment( tagName );
                        }
                    }
                    if ( html.length > nextIndex ) {
                        callbacks.onText( html.slice( nextIndex, html.length ) );
                    }
                }

                return function ( html, forceDtd ) {

                    var fragment = {
                        type: 'fragment',
                        parent: null,
                        children: []
                    };
                    var currentNode = fragment;

                    function addChild( node ) {
                        node.parent = currentNode;
                        currentNode.children.push( node );
                    }

                    function addElement( element, open ) {
                        var node = element;
                        if ( NEED_PARENT_TAG[node.tag] ) {
                            while ( NEED_PARENT_TAG[currentNode.tag] && NEED_PARENT_TAG[currentNode.tag][node.tag] ) {
                                currentNode = currentNode.parent;
                            }
                            if ( currentNode.tag == node.tag ) {
                                currentNode = currentNode.parent;
                            }
                            while ( NEED_PARENT_TAG[node.tag] ) {
                                if ( NEED_PARENT_TAG[node.tag][currentNode.tag] ) break;
                                node = node.parent = {
                                    type: 'element',
                                    tag: NEED_PARENT_TAG[node.tag]['$'],
                                    attributes: {},
                                    children: [node]
                                };
                            }
                        }
                        if ( forceDtd ) {
                            while ( dtd[node.tag] && !(currentNode.tag == 'span' ? utils.extend( dtd['strong'], {'a':1,'A':1} ) : (dtd[currentNode.tag] || dtd['div']))[node.tag] ) {
                                if ( tagEnd( currentNode ) ) continue;
                                if ( !currentNode.parent ) break;
                                currentNode = currentNode.parent;
                            }
                        }
                        node.parent = currentNode;
                        currentNode.children.push( node );
                        if ( open ) {
                            currentNode = element;
                        }
                        if ( element.attributes.style ) {
                            element.attributes.style = element.attributes.style.toLowerCase();
                        }
                        return element;
                    }
                    function tagEnd( node ) {
                        var needTag;
                        if ( !node.children.length && (needTag = NEED_CHILD_TAG[node.tag]) ) {
                            addElement( {
                                type: 'element',
                                tag: needTag,
                                attributes: {},
                                children: []
                            }, true );
                            return true;
                        }
                        return false;
                    }

                    parse( html, {
                        onText: function ( text ) {

                            while ( !(dtd[currentNode.tag] || dtd['div'])['#'] ) {
                                if ( tagEnd( currentNode ) ) continue;
                                currentNode = currentNode.parent;
                            }
                                addChild( {
                                    type: 'text',
                                    data: text
                                } );

                        },
                        onComment: function ( text ) {
                            addChild( {
                                type: 'comment',
                                data: text
                            } );
                        },
                        onCDATA: function ( text ) {
                            while ( !(dtd[currentNode.tag] || dtd['div'])['#'] ) {
                                if ( tagEnd( currentNode ) ) continue;
                                currentNode = currentNode.parent;
                            }
                            addChild( {
                                type: 'cdata',
                                data: text
                            } );
                        },
                        onTagOpen: function ( tag, attrs, closed ) {
                            closed = closed || EMPTY_TAG[tag] ;
                            addElement( {
                                type: 'element',
                                tag: tag,
                                attributes: attrs,
                                closed: closed,
                                children: []
                            }, !closed );
                        },
                        onTagClose: function ( tag ) {
                            var node = currentNode;
                            while ( node && tag != node.tag ) {
                                node = node.parent;
                            }
                            if ( node ) {
                                for ( var tnode = currentNode; tnode !== node.parent; tnode = tnode.parent ) {
                                    tagEnd( tnode );
                                }
                                currentNode = node.parent;
                            } else {
                                if ( !(dtd.$removeEmpty[tag] || dtd.$removeEmptyBlock[tag] || tag == 'embed') ) {
                                    node = {
                                        type: 'element',
                                        tag: tag,
                                        attributes: {},
                                        children: []
                                    };
                                    addElement( node, true );
                                    tagEnd( node );
                                    currentNode = node.parent;
                                }


                            }
                        }
                    } );
                    while ( currentNode !== fragment ) {
                        tagEnd( currentNode );
                        currentNode = currentNode.parent;
                    }
                    return fragment;
                };
            }();
    var unhtml1 = function () {
        var map = { '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };

        function rep( m ) {
            return map[m];
        }

        return function ( str ) {
            str = str + '';
            return str ? str.replace( /[<>"']/g, rep ) : '';
        };
    }();
    var toHTML = function () {
        function printChildren( node, pasteplain ) {
            var children = node.children;

            var buff = [];
            for ( var i = 0,ci; ci = children[i]; i++ ) {

                buff.push( toHTML( ci, pasteplain ) );
            }
            return buff.join( '' );
        }

        function printAttrs( attrs ) {
            var buff = [];
            for ( var k in attrs ) {
                var value = attrs[k];
                
                if(k == 'style'){
                    value = ptToPx(value);
                    if(/rgba?\s*\([^)]*\)/.test(value)){
                        value = value.replace( /rgba?\s*\(([^)]*)\)/g, function( str ) {
                            return utils.fixColor('color',str);
                        } )
                    }
                    attrs[k] = utils.optCss(value.replace(/windowtext/g,'#000'))
                                .replace(/white-space[^;]+;/g,'');

                }

                buff.push( k + '="' + unhtml1( attrs[k] ) + '"' );
            }
            return buff.join( ' ' )
        }

        function printData( node, notTrans ) {
            return notTrans ? node.data.replace(/&nbsp;/g,' ') : unhtml1( node.data ).replace(/ /g,'&nbsp;');
        }
        var transHtml = {
            'div':'p',
            'li':'p',
            'tr':'p',
            'br':'br',
            'p':'p'//trace:1398 碰到p标签自己要加上p,否则transHtml[tag]是undefined

        };

        function printElement( node, pasteplain ) {
            if ( node.type == 'element' && !node.children.length && (dtd.$removeEmpty[node.tag]) && node.tag != 'a' && utils.isEmptyObject(node.attributes) && autoClearEmptyNode) {// 锚点保留
                return html;
            }
            var tag = node.tag;
            if ( pasteplain && tag == 'td' ) {
                if ( !html ) html = '';
                html += printChildren( node, pasteplain ) + '&nbsp;&nbsp;&nbsp;';
            } else {
                var attrs = printAttrs( node.attributes );
                var html = '<' + (pasteplain && transHtml[tag] ? transHtml[tag] : tag) + (attrs ? ' ' + attrs : '') + (EMPTY_TAG[tag] ? ' />' : '>');
                if ( !EMPTY_TAG[tag] ) {
                    if( tag == 'p' && !node.children.length){
                        html += browser.ie ? '&nbsp;' : '<br/>';
                    }
                    html += printChildren( node, pasteplain );
                    html += '</' + (pasteplain && transHtml[tag] ? transHtml[tag] : tag) + '>';
                }
            }

            return html;
        }

        return function ( node, pasteplain ) {
            if ( node.type == 'fragment' ) {
                return printChildren( node, pasteplain );
            } else if ( node.type == 'element' ) {
                return printElement( node, pasteplain );
            } else if ( node.type == 'text' || node.type == 'cdata' ) {
                return printData( node, dtd.$notTransContent[node.parent.tag] );
            } else if ( node.type == 'comment' ) {
                return '<!--' + node.data + '-->';
            }
            return '';
        };
    }();
    var transformWordHtml = function () {

        function isWordDocument( strValue ) {
            var re = new RegExp( /(class="?Mso|style="[^"]*\bmso\-|w:WordDocument|<v:)/ig );
            return re.test( strValue );
        }

        function ensureUnits( v ) {
            v = v.replace( /([\d.]+)([\w]+)?/g, function ( m, p1, p2 ) {
                return (Math.round( parseFloat( p1 ) ) || 1) + (p2 || 'px');
            } );
            return v;
        }

        function filterPasteWord( str ) {
            str = str.replace( /<!--\s*EndFragment\s*-->[\s\S]*$/, '' )
                .replace( /^(\r\n|\n|\r)|(\r\n|\n|\r)$/ig, "" )
                .replace( /^\s*(&nbsp;)+/ig, "" )
                .replace( /(&nbsp;|<br[^>]*>)+\s*$/ig, "" )
                .replace( /<!--[\s\S]*?-->/ig, "" )
                .replace(/<v:shape [^>]*>[\s\S]*?.<\/v:shape>/gi,function(str){
                    try{
                        var width = str.match(/width:([ \d.]*p[tx])/i)[1],
                            height = str.match(/height:([ \d.]*p[tx])/i)[1],
                            src =  str.match(/src=\s*"([^"]*)"/i)[1];
                        return '<img width="'+ptToPx(width)+'" height="'+ptToPx(height)+'" src="' + src + '" />'
                    } catch(e){
                        return '';
                    }

                })
                .replace( /v:\w+=["']?[^'"]+["']?/g, '' )
                .replace( /<(!|script[^>]*>.*?<\/script(?=[>\s])|\/?(\?xml(:\w+)?|xml|meta|link|style|\w+:\w+)(?=[\s\/>]))[^>]*>/gi, "" )
                .replace( /<p [^>]*class="?MsoHeading"?[^>]*>(.*?)<\/p>/gi, "<p><strong>$1</strong></p>" )
                .replace( /(lang)\s*=\s*([\'\"]?)[\w-]+\2/ig, "" )
                .replace( /<font[^>]*>\s*<\/font>/gi, '' )
                .replace( /class\s*=\s*["']?(?:(?:MsoTableGrid)|(?:MsoListParagraph)|(?:MsoNormal(Table)?))\s*["']?/gi, '' );
            str = str.replace( /(<[a-z][^>]*)\sstyle=(["'])([^\2]*?)\2/gi, function( str, tag, tmp, style ) {

                var n = [],
                        i = 0,
                        s = style.replace( /^\s+|\s+$/, '' ).replace( /&quot;/gi, "'" ).split( /;\s*/g );
                for ( var i = 0; i < s.length; i++ ) {
                    var v = s[i];
                    var name, value,
                            parts = v.split( ":" );

                    if ( parts.length == 2 ) {
                        name = parts[0].toLowerCase();
                        value = parts[1].toLowerCase();
                        switch ( name ) {
                            case "mso-padding-alt":
                            case "mso-padding-top-alt":
                            case "mso-padding-right-alt":
                            case "mso-padding-bottom-alt":
                            case "mso-padding-left-alt":
                            case "mso-margin-alt":
                            case "mso-margin-top-alt":
                            case "mso-margin-right-alt":
                            case "mso-margin-bottom-alt":
                            case "mso-margin-left-alt":
                            case "mso-height":
                            case "mso-width":
                            case "mso-vertical-align-alt":
                                if(!/<table/.test(tag))
                                    n[i] = name.replace( /^mso-|-alt$/g, "" ) + ":" + ensureUnits( value );
                                continue;
                            case "horiz-align":
                                n[i] = "text-align:" + value;
                                continue;

                            case "vert-align":
                                n[i] = "vertical-align:" + value;
                                continue;

                            case "font-color":
                            case "mso-foreground":
                                n[i] = "color:" + value;
                                continue;

                            case "mso-background":
                            case "mso-highlight":
                                n[i] = "background:" + value;
                                continue;

                            case "mso-default-height":
                                n[i] = "min-height:" + ensureUnits( value );
                                continue;

                            case "mso-default-width":
                                n[i] = "min-width:" + ensureUnits( value );
                                continue;

                            case "mso-padding-between-alt":
                                n[i] = "border-collapse:separate;border-spacing:" + ensureUnits( value );
                                continue;

                            case "text-line-through":
                                if ( (value == "single") || (value == "double") ) {
                                    n[i] = "text-decoration:line-through";
                                }
                                continue;
                            case "mso-zero-height":
                                if ( value == "yes" ) {
                                    n[i] = "display:none";
                                }
                                continue;
                            case 'margin':
                                if ( !/[1-9]/.test( parts[1] ) ) {
                                    continue;
                                }
                        }

                        if ( /^(mso|column|font-emph|lang|layout|line-break|list-image|nav|panose|punct|row|ruby|sep|size|src|tab-|table-border|text-(?:decor|trans)|top-bar|version|vnd|word-break)/.test( name ) ) {
                                continue;
                        }
                        if(/text\-indent|padding|margin/.test(name) && /\-[\d.]+/.test(value)){
                            continue;
                        }
                        n[i] = name + ":" + parts[1];
                    }
                }
                if ( i > 0 ) {
                    return tag + ' style="' + n.join( ';' ) + '"';
                } else {
                    return tag;
                }
            } );
            str = str.replace( /([ ]+)<\/span>/ig, function ( m, p ) {
                return new Array( p.length + 1 ).join( '&nbsp;' ) + '</span>';
            } );
            return str;
        }

        return function ( html ) {
            first = null;
            parentTag = '',liStyle = '',firstTag = '';
            if ( isWordDocument( html ) ) {
                html = filterPasteWord( html );
            }
            return html.replace( />[ \t\r\n]*</g, '><' );
        };
    }();
    var NODE_NAME_MAP = {
        'text': '#text',
        'comment': '#comment',
        'cdata': '#cdata-section',
        'fragment': '#document-fragment'
    };
    var first,
            parentTag = '',liStyle = '',firstTag;
    function transNode( node, word_img_flag ) {

        var sizeMap = [0, 10, 12, 16, 18, 24, 32, 48],
                attr,
                indexOf = utils.indexOf;
        switch ( node.tag ) {
            case 'script':
                node.tag = 'div';
                node.attributes._ue_div_script = 1;
                node.attributes._ue_script_data = node.children[0] ? encodeURIComponent(node.children[0].data)  : '';
                node.children = [];
                break;
            case 'img':
                if(node.attributes.src && /^data:/.test(node.attributes.src)){
                    return {
                        type : 'fragment',
                        children:[]
                    }
                }
                if ( node.attributes.src && /^(?:file)/.test( node.attributes.src ) ) {
                    if ( !/(gif|bmp|png|jpg|jpeg)$/.test( node.attributes.src ) ) {
                        return {
                            type : 'fragment',
                            children:[]
                        }
                    }
                    node.attributes.word_img = node.attributes.src;
                    node.attributes.src = me.options.UEDITOR_HOME_URL + 'themes/default/images/spacer.gif';
                    var flag = parseInt(node.attributes.width)<128||parseInt(node.attributes.height)<43;
                    node.attributes.style="background:url(" + me.options.UEDITOR_HOME_URL +"themes/default/images/"+(flag?"word.gif":"localimage.png")+") no-repeat center center;border:1px solid #ddd";
                    word_img_flag && (word_img_flag.flag = 1);
                }
                if(browser.ie && browser.version < 7 )
                    node.attributes.orgSrc = node.attributes.src;
                node.attributes.data_ue_src = node.attributes.data_ue_src || node.attributes.src;
                break;
            case 'li':
                var child = node.children[0];

                if ( !child || child.type != 'element' || child.tag != 'p' && dtd.p[child.tag] ) {
                    var tmpPNode = {
                        type: 'element',
                        tag: 'p',
                        attributes: {},

                        parent : node
                    };
                    tmpPNode.children = child ? node.children :[
                            browser.ie ? {
                                type:'text',
                                data:domUtils.fillChar,
                                parent : tmpPNode

                            }:
                            {
                                type : 'element',
                                tag : 'br',
                                attributes:{},
                                closed: true,
                                children: [],
                                parent : tmpPNode
                            }
                    ];
                    node.children =   [tmpPNode];
                }
                break;
            case 'table':
            case 'td':
                optStyle( node );
                break;
            case 'a'://锚点，a==>img
                if ( node.attributes['anchorname'] ) {
                    node.tag = 'img';
                    node.attributes = {
                        'class' : 'anchorclass',
                        'anchorname':node.attributes['name']
                    };
                    node.closed = 1;
                }
                node.attributes.href && (node.attributes.data_ue_src = node.attributes.href);
                break;
            case 'b':
                node.tag = node.name = 'strong';
                break;
            case 'i':
                node.tag = node.name = 'em';
                break;
            case 'u':
                node.tag = node.name = 'span';
                node.attributes.style = (node.attributes.style || '') + ';text-decoration:underline;';
                break;
            case 's':
            case 'del':
                node.tag = node.name = 'span';
                node.attributes.style = (node.attributes.style || '') + ';text-decoration:line-through;';
                if ( node.children.length == 1 ) {
                    child = node.children[0];
                    if ( child.tag == node.tag ) {
                        node.attributes.style += ";" + child.attributes.style;
                        node.children = child.children;

                    }
                }
                break;
            case 'span':
                var style = node.attributes.style;
                if ( style ) {
                    if ( !node.attributes.style  || browser.webkit && style == "white-space:nowrap;") {
                        delete node.attributes.style;
                    }
                }
                
                if(browser.gecko && browser.version <= 10902 && node.parent){
                    var parent = node.parent;
                    if(parent.tag == 'span' && parent.attributes && parent.attributes.style){
                        node.attributes.style = parent.attributes.style + ';' + node.attributes.style;
                    }
                }
                if ( utils.isEmptyObject( node.attributes ) && autoClearEmptyNode) {
                    node.type = 'fragment'
                }
                break;
            case 'font':
                node.tag = node.name = 'span';
                attr = node.attributes;
                node.attributes = {
                    'style': (attr.size ? 'font-size:' + (sizeMap[attr.size] || 12) + 'px' : '')
                    + ';' + (attr.color ? 'color:'+ attr.color : '')
                    + ';' + (attr.face ? 'font-family:'+ attr.face : '')
                    + ';' + (attr.style||'')
                };

                while(node.parent.tag == node.tag && node.parent.children.length == 1){
                    node.attributes.style && (node.parent.attributes.style ? (node.parent.attributes.style += ";" + node.attributes.style) : (node.parent.attributes.style = node.attributes.style));
                    node.parent.children = node.children;
                    node = node.parent;

                }
                break;
            case 'p':
                if ( node.attributes.align ) {
                    node.attributes.style = (node.attributes.style || '') + ';text-align:' +
                            node.attributes.align + ';';
                    delete node.attributes.align;
                }
        }
        return node;
    }

    function optStyle( node ) {
        if ( ie && node.attributes.style ) {
            var style = node.attributes.style;
            node.attributes.style = style.replace(/;\s*/g,';');
            node.attributes.style = node.attributes.style.replace( /^\s*|\s*$/, '' )
        }
    }
    function transOutNode( node ) {

        switch ( node.tag ) {
            case 'div' :
                if(node.attributes._ue_div_script){
                    node.tag = 'script';
                    node.children = [{type:'cdata',data:decodeURIComponent(node.attributes._ue_script_data)||'',parent:node}];
                    delete node.attributes._ue_div_script;
                    delete node.attributes._ue_script_data;
                    break;
                }
            case 'table':
                !node.attributes.style && delete node.attributes.style;
                if ( ie && node.attributes.style ) {

                    optStyle( node );
                }
                if(node.attributes['class'] == 'noBorderTable'){
                    delete node.attributes['class'];
                }
                break;
            case 'td':
            case 'th':
                if ( /display\s*:\s*none/i.test( node.attributes.style ) ) {
                    return {
                        type: 'fragment',
                        children: []
                    };
                }
                if ( ie && !node.children.length ) {
                    var txtNode = {
                        type: 'text',
                        data:domUtils.fillChar,
                        parent : node
                    };
                    node.children[0] = txtNode;
                }
                if ( ie && node.attributes.style ) {
                    optStyle( node );

                }
                if(node.attributes['class'] == 'selectTdClass'){
                    delete node.attributes['class']
                }
                break;
            case 'img'://锚点，img==>a
                if ( node.attributes.anchorname ) {
                    node.tag = 'a';
                    node.attributes = {
                        name : node.attributes.anchorname,
                        anchorname : 1
                    };
                    node.closed = null;
                }else{
                    if(node.attributes.data_ue_src){
                        node.attributes.src = node.attributes.data_ue_src;
                        delete node.attributes.data_ue_src;
                    }
                }
                break;

            case 'a':
                if(node.attributes.data_ue_src){
                    node.attributes.href = node.attributes.data_ue_src;
                    delete node.attributes.data_ue_src;
                }
        }

        return node;
    }

    function childrenAccept( node, visit, ctx ) {

        if ( !node.children || !node.children.length ) {
            return node;
        }
        var children = node.children;
        for ( var i = 0; i < children.length; i++ ) {
            var newNode = visit( children[i], ctx );
            if ( newNode.type == 'fragment' ) {
                var args = [i, 1];
                args.push.apply( args, newNode.children );
                children.splice.apply( children, args );
                if ( !children.length ) {
                    node = {
                        type: 'fragment',
                        children: []
                    }
                }
                i --;
            } else {
                children[i] = newNode;
            }
        }
        return node;
    }

    function Serialize( rules ) {
        this.rules = rules;
    }

    Serialize.prototype = {
        rules: null,
        filter: function ( node, rules, modify ) {
            rules = rules || this.rules;
            var whiteList = rules && rules.whiteList;
            var blackList = rules && rules.blackList;

            function visitNode( node, parent ) {
                node.name = node.type == 'element' ?
                        node.tag : NODE_NAME_MAP[node.type];
                if ( parent == null ) {
                    return childrenAccept( node, visitNode, node );
                }

                if ( blackList && blackList[node.name] ) {
                    modify && (modify.flag = 1);
                    return {
                        type: 'fragment',
                        children: []
                    };
                }
                if ( whiteList ) {
                    if ( node.type == 'element' ) {
                        if ( parent.type == 'fragment' ? whiteList[node.name] : whiteList[node.name] && whiteList[parent.name][node.name] ) {

                            var props;
                            if ( (props = whiteList[node.name].$) ) {
                                var oldAttrs = node.attributes;
                                var newAttrs = {};
                                for ( var k in props ) {
                                    if ( oldAttrs[k] ) {
                                        newAttrs[k] = oldAttrs[k];
                                    }
                                }
                                node.attributes = newAttrs;
                            }


                        } else {
                            modify && (modify.flag = 1);
                            node.type = 'fragment';
                            node.name = parent.name;
                        }
                    } else {
                    }
                }
                if ( blackList || whiteList ) {
                    childrenAccept( node, visitNode, node );
                }
                return node;
            }

            return visitNode( node, null );
        },
        transformInput: function ( node, word_img_flag ) {

            function visitNode( node ) {
                node = transNode( node, word_img_flag );
                node = childrenAccept( node, visitNode, node );
                if ( me.options.pageBreakTag && node.type == 'text' && node.data.replace( /\s/g, '' ) == me.options.pageBreakTag ) {

                    node.type = 'element';
                    node.name = node.tag = 'hr';

                    delete node.data;
                    node.attributes = {
                        'class' : 'pagebreak',
                        noshade:"noshade",
                        size:"5",
                        'unselectable' : 'on',
                        'style' : 'moz-user-select:none;-khtml-user-select: none;'
                    };

                    node.children = [];

                }
                if(node.type == 'text' && !dtd.$notTransContent[node.parent.tag]){
                    node.data = node.data.replace(/[\r\t\n]*/g,'')//.replace(/[ ]*$/g,'')
                }
                return node;
            }

            return visitNode( node );
        },
        transformOutput: function ( node ) {
            function visitNode( node ) {

                if ( node.tag == 'hr' && node.attributes['class'] == 'pagebreak' ) {
                    delete node.tag;
                    node.type = 'text';
                    node.data = me.options.pageBreakTag;
                    delete node.children;

                }

                node = transOutNode( node );
                if ( node.tag == 'ol' || node.tag == 'ul' ) {
                    first = 1;
                }
                node = childrenAccept( node, visitNode, node );
                if ( node.tag == 'ol' || node.tag == 'ul' ) {
                    first = 0;
                }
                return node;
            }

            return visitNode( node );
        },
        toHTML: toHTML,
        parseHTML: parseHTML,
        word: transformWordHtml
    };
    me.serialize = new Serialize( me.options.serialize || {});
    UE.serialize = new Serialize( {} );
};


(function() {
    function getClipboardData( callback ) {

        var doc = this.document;

        if ( doc.getElementById( 'baidu_pastebin' ) ) {
            return;
        }

        var range = this.selection.getRange(),
            bk = range.createBookmark(),
            pastebin = doc.createElement( 'div' );

        pastebin.id = 'baidu_pastebin';
        browser.webkit && pastebin.appendChild( doc.createTextNode( domUtils.fillChar + domUtils.fillChar ) );
        doc.body.appendChild( pastebin );
        bk.start.style.display = '';
        pastebin.style.cssText = "position:absolute;width:1px;height:1px;overflow:hidden;left:-1000px;white-space:nowrap;top:" +
            domUtils.getXY( bk.start ).y + 'px';

        range.selectNodeContents( pastebin ).select( true );

        setTimeout( function() {
            
            if (browser.webkit) {
                
                for(var i=0,pastebins = doc.querySelectorAll('#baidu_pastebin'),pi;pi=pastebins[i++];){
                    if(domUtils.isEmptyNode(pi)){
                        domUtils.remove(pi)
                    }else{
                        pastebin = pi;
                        break;
                    }
                }


            }

			try{
                pastebin.parentNode.removeChild(pastebin);
            }catch(e){}

            range.moveToBookmark( bk ).select(true);
            callback( pastebin );
        }, 0 );


    }

    UE.plugins['paste'] = function() {
        var me = this;
        var word_img_flag = {flag:""};

        var pasteplain = me.options.pasteplain === true;
        var modify_num = {flag:""};
        me.commands['pasteplain'] = {
            queryCommandState: function (){
                return pasteplain;
            },
            execCommand: function (){
                pasteplain = !pasteplain|0;
            },
            notNeedUndo : 1
        };

        function filter(div){
            
            var html;
            if ( div.firstChild ) {
                    var nodes = domUtils.getElementsByTagName(div,'span');
                    for(var i=0,ni;ni=nodes[i++];){
                        if(ni.id == '_baidu_cut_start' || ni.id == '_baidu_cut_end'){
                            domUtils.remove(ni)
                        }
                    }

                    if(browser.webkit){

                        var brs = div.querySelectorAll('div br');
                        for(var i=0,bi;bi=brs[i++];){
                            var pN = bi.parentNode;
                            if(pN.tagName == 'DIV' && pN.childNodes.length ==1){
                                pN.innerHTML = '<p><br/></p>';
                                
                                domUtils.remove(pN)
                            }
                        }
                        var divs = div.querySelectorAll('#baidu_pastebin');
                        for(var i=0,di;di=divs[i++];){
                            var tmpP = me.document.createElement('p');
                            di.parentNode.insertBefore(tmpP,di);
                            while(di.firstChild){
                                tmpP.appendChild(di.firstChild)
                            }
                            domUtils.remove(di)
                        }



                        var metas = div.querySelectorAll('meta');
                        for(var i=0,ci;ci=metas[i++];){
                            domUtils.remove(ci);
                        }

                        var brs = div.querySelectorAll('br');
                        for(i=0;ci=brs[i++];){
                            if(/^apple-/.test(ci)){
                                domUtils.remove(ci)
                            }
                        }

                    }
                    if(browser.gecko){
                        var dirtyNodes = div.querySelectorAll('[_moz_dirty]')
                        for(i=0;ci=dirtyNodes[i++];){
                            ci.removeAttribute( '_moz_dirty' )
                        }
                    }
                    if(!browser.ie ){
                        var spans = div.querySelectorAll('span.apple-style-span');
                        for(var i=0,ci;ci=spans[i++];){
                            domUtils.remove(ci,true);
                        }
                    }


                    html = div.innerHTML;

                    var f = me.serialize;
                    if(f){
                        try{
                            var node =  f.transformInput(
                                        f.parseHTML(
                                            f.word(html)//, true
                                        ),word_img_flag
                                    );
                            node = f.filter(node,pasteplain ? {
                                whiteList: {
                                    'p': {'br':1,'BR':1},
                                    'br':{'$':{}},
                                    'div':{'br':1,'BR':1,'$':{}},
                                    'li':{'$':{}},
                                    'tr':{'td':1,'$':{}},
                                    'td':{'$':{}}

                                },
                                blackList: {
                                    'style':1,
                                    'script':1,
                                    'object':1
                                }
                            } : null, !pasteplain ? modify_num : null);

                            if(browser.webkit){
                                var length = node.children.length,
                                    child;
                                while((child = node.children[length-1]) && child.tag == 'br'){
                                    node.children.splice(length-1,1);
                                    length = node.children.length;
                                }
                            }
                            html = f.toHTML(node,pasteplain)

                        }catch(e){}

                    }
                   html = {'html':html};

                   me.fireEvent('beforepaste',html);
                   me.execCommand( 'insertHtml',html.html,true);

	               me.fireEvent("afterpaste");

                }
        }

        me.addListener('ready',function(){
            domUtils.on(me.body,'cut',function(){

                var range = me.selection.getRange();
                if(!range.collapsed && me.undoManger){
                    me.undoManger.save()
                }
       
            });
                domUtils.on(me.body, browser.ie ? 'keydown' : 'paste',function(e){
                    if(browser.ie && (!e.ctrlKey || e.keyCode != '86'))
                        return;
                    getClipboardData.call( me, function( div ) {
                        filter(div);
                    } );


                })

        });

    }

})();


UE.plugins['fiximgclick'] = function() {
    var me = this;
    if ( browser.webkit ) {
        me.addListener( 'click', function( type, e ) {
            if ( e.target.tagName == 'IMG' ) {
                var range = new dom.Range( me.document );
                range.selectNode( e.target ).select();

            }
        } )
    }
};

UE.plugins['enterkey'] = function() {
    var hTag,
        me = this,
        tag = me.options.enterTag;
    me.addListener('keyup', function(type, evt) {

        var keyCode = evt.keyCode || evt.which;
        if (keyCode == 13) {
            var range = me.selection.getRange(),
                start = range.startContainer,
                doSave;
            if (!browser.ie) {

                if (/h\d/i.test(hTag)) {
                    if (browser.gecko) {
                        var h = domUtils.findParentByTagName(start, [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6','blockquote'], true);
                        if (!h) {
                            me.document.execCommand('formatBlock', false, '<p>');
                            doSave = 1;
                        }
                    } else {
                        if (start.nodeType == 1) {
                            var tmp = me.document.createTextNode(''),div;
                            range.insertNode(tmp);
                            div = domUtils.findParentByTagName(tmp, 'div', true);
                            if (div) {
                                var p = me.document.createElement('p');
                                while (div.firstChild) {
                                    p.appendChild(div.firstChild);
                                }
                                div.parentNode.insertBefore(p, div);
                                domUtils.remove(div);
                                range.setStartBefore(tmp).setCursor();
                                doSave = 1;
                            }
                            domUtils.remove(tmp);

                        }
                    }

                    if (me.undoManger && doSave) {
                        me.undoManger.save()
                    }
                }

            }



            setTimeout(function() {
                me.selection.getRange().scrollToView(me.autoHeightEnabled, me.autoHeightEnabled ? domUtils.getXY(me.iframe).y : 0);
            }, 50)

        }
    });

    me.addListener('keydown', function(type, evt) {

        var keyCode = evt.keyCode || evt.which;
        if (keyCode == 13) {//回车
            if (me.undoManger) {
                me.undoManger.save()
            }
            hTag = '';


            var range = me.selection.getRange();

            if (!range.collapsed) {
                var start = range.startContainer,
                    end = range.endContainer,
                    startTd = domUtils.findParentByTagName(start, 'td', true),
                    endTd = domUtils.findParentByTagName(end, 'td', true);
                if (startTd && endTd && startTd !== endTd || !startTd && endTd || startTd && !endTd) {
                    evt.preventDefault ? evt.preventDefault() : ( evt.returnValue = false);
                    return;
                }
            }
            me.currentSelectedArr && domUtils.clearSelectedArr(me.currentSelectedArr);

            if (tag == 'p') {


                if (!browser.ie) {

                    start = domUtils.findParentByTagName(range.startContainer, ['ol','ul','p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6','blockquote'], true);


                    if (!start) {

                        me.document.execCommand('formatBlock', false, '<p>');
                        if (browser.gecko) {
                            range = me.selection.getRange();
                            start = domUtils.findParentByTagName(range.startContainer, 'p', true);
                            start && domUtils.removeDirtyAttr(start);
                        }


                    } else {
                        hTag = start.tagName;
                        start.tagName.toLowerCase() == 'p' && browser.gecko && domUtils.removeDirtyAttr(start);
                    }

                }

            } else {
                evt.preventDefault ? evt.preventDefault() : ( evt.returnValue = false);

                if (!range.collapsed) {
                    range.deleteContents();
                    start = range.startContainer;
                    if (start.nodeType == 1 && (start = start.childNodes[range.startOffset])) {
                        while (start.nodeType == 1) {
                            if (dtd.$empty[start.tagName]) {
                                range.setStartBefore(start).setCursor();
                                if (me.undoManger) {
                                    me.undoManger.save()
                                }
                                return false;
                            }
                            if (!start.firstChild) {
                                var br = range.document.createElement('br');
                                start.appendChild(br);
                                range.setStart(start, 0).setCursor();
                                if (me.undoManger) {
                                    me.undoManger.save()
                                }
                                return false;
                            }
                            start = start.firstChild
                        }
                        if (start === range.startContainer.childNodes[range.startOffset]) {
                            br = range.document.createElement('br');
                            range.insertNode(br).setCursor();

                        } else {
                            range.setStart(start, 0).setCursor();
                        }


                    } else {
                        br = range.document.createElement('br');
                        range.insertNode(br).setStartAfter(br).setCursor();
                    }


                } else {
                    br = range.document.createElement('br');
                    range.insertNode(br);
                    var parent = br.parentNode;
                    if (parent.lastChild === br) {
                        br.parentNode.insertBefore(br.cloneNode(true), br);
                        range.setStartBefore(br)
                    } else {
                        range.setStartAfter(br)
                    }
                    range.setCursor();

                }

            }

        }
    });
};


UE.commands['delete'] = {
    execCommand : function (){

        var range = this.selection.getRange(),
            mStart = 0,
            mEnd = 0,
            me = this;
        if(this.selectAll ){
            me.body.innerHTML = '<p>'+(browser.ie ? '&nbsp;' : '<br/>')+'</p>';

            range.setStart(me.body.firstChild,0).setCursor(false,true);

            me.selectAll = false;
            return;
        }
        if(me.currentSelectedArr && me.currentSelectedArr.length > 0){
            for(var i=0,ci;ci=me.currentSelectedArr[i++];){
                if(ci.style.display != 'none'){
                    ci.innerHTML = browser.ie ? domUtils.fillChar : '<br/>'
                }

            }
            range.setStart(me.currentSelectedArr[0],0).setCursor();
            return;
        }
        if(range.collapsed)
            return;
        range.txtToElmBoundary();
        while(!range.startOffset &&  !domUtils.isBody(range.startContainer) &&  !dtd.$tableContent[range.startContainer.tagName] ){
            mStart = 1;
            range.setStartBefore(range.startContainer);
        }
        while(!domUtils.isBody(range.endContainer)&&  !dtd.$tableContent[range.endContainer.tagName]  ){
            var child,endContainer = range.endContainer,endOffset = range.endOffset;
            child = endContainer.childNodes[endOffset];
            if(!child || domUtils.isBr(child) && endContainer.lastChild === child){
                range.setEndAfter(endContainer);
                continue;
            }
            break;

        }
        if(mStart){
            var start = me.document.createElement('span');
            start.innerHTML = 'start';
            start.id = '_baidu_cut_start';
            range.insertNode(start).setStartBefore(start)
        }
        if(mEnd){
            var end = me.document.createElement('span');
            end.innerHTML = 'end';
            end.id = '_baidu_cut_end';
            range.cloneRange().collapse(false).insertNode(end);
            range.setEndAfter(end)

        }



        range.deleteContents();


        if(domUtils.isBody(range.startContainer) && domUtils.isEmptyBlock(me.body)){
            me.body.innerHTML = '<p>'+(browser.ie?'':'<br/>')+'</p>';
            range.setStart(me.body.firstChild,0).collapse(true);
        }else if ( !browser.ie && domUtils.isEmptyBlock(range.startContainer)){
            range.startContainer.innerHTML = '<br/>'
        }

        range.select(true)
    },
    queryCommandState : function(){

        if(this.currentSelectedArr && this.currentSelectedArr.length > 0){
            return 0;
        }
        return this.highlight || this.selection.getRange().collapsed ? -1 : 0;
    }
};


    UE.commands['inserthtml'] = {
        execCommand: function (command,html,notSerialize){
            var me = this,
                range,
                div,
                tds = me.currentSelectedArr;

            range = me.selection.getRange();

            div = range.document.createElement( 'div' );
            div.style.display = 'inline';
            var serialize = me.serialize;
            if (!notSerialize && serialize) {
                var node = serialize.parseHTML(html);
                node = serialize.transformInput(node);
                node = serialize.filter(node);
                html = serialize.toHTML(node);
            }
            div.innerHTML = utils.trim( html );
            try{
                me.adjustTable && me.adjustTable(div);
            }catch(e){}


            if(tds && tds.length){
                for(var i=0,ti;ti=tds[i++];){
                    ti.className = ''
                }
                tds[0].innerHTML = '';
                range.setStart(tds[0],0).collapse(true);
                me.currentSelectedArr = [];
            }

            if ( !range.collapsed ) {

                range.deleteContents();
                if(range.startContainer.nodeType == 1){
                    var child = range.startContainer.childNodes[range.startOffset],pre;
                    if(child && domUtils.isBlockElm(child) && (pre = child.previousSibling) && domUtils.isBlockElm(pre)){
                        range.setEnd(pre,pre.childNodes.length).collapse();
                        while(child.firstChild){
                            pre.appendChild(child.firstChild);

                        }
                        domUtils.remove(child);
                    }
                }

            }


            var child,parent,pre,tmp,hadBreak = 0;
            while ( child = div.firstChild ) {
                range.insertNode( child );
                if ( !hadBreak && child.nodeType == domUtils.NODE_ELEMENT && domUtils.isBlockElm( child ) ){

                    parent = domUtils.findParent( child,function ( node ){ return domUtils.isBlockElm( node ); } );
                    if ( parent && parent.tagName.toLowerCase() != 'body' && !(dtd[parent.tagName][child.nodeName] && child.parentNode === parent)){
                        if(!dtd[parent.tagName][child.nodeName]){
                            pre = parent;
                        }else{
                            tmp = child.parentNode;
                            while (tmp !== parent){
                                pre = tmp;
                                tmp = tmp.parentNode;
    
                            }    
                        }
                        
                       
                        domUtils.breakParent( child, pre || tmp );
                        var pre = child.previousSibling;
                        domUtils.trimWhiteTextNode(pre);
                        if(!pre.childNodes.length){
                            domUtils.remove(pre);
                        }

                        if(!browser.ie &&
                            (next = child.nextSibling) &&
                            domUtils.isBlockElm(next) &&
                            next.lastChild &&
                            !domUtils.isBr(next.lastChild)){
                            next.appendChild(me.document.createElement('br'))
                        }
                        hadBreak = 1;
                    }
                }
                var next = child.nextSibling;
                if(!div.firstChild && next && domUtils.isBlockElm(next)){

                    range.setStart(next,0).collapse(true);
                    break;
                }
                range.setEndAfter( child ).collapse();

            }


            child = range.startContainer;
            if(domUtils.isBlockElm(child) && domUtils.isEmptyNode(child)){
                child.innerHTML = browser.ie ? '' : '<br/>'
            }
            range.select(true);


            setTimeout(function(){
                range = me.selection.getRange();
                range.scrollToView(me.autoHeightEnabled,me.autoHeightEnabled ? domUtils.getXY(me.iframe).y:0);
            },200)



            
        }
    };

UE.plugins['shortcutkeys'] = function(){
    var me = this,
        shortcutkeys = {
    		"ctrl+66" : "Bold" ,//^B
        	"ctrl+90" : "Undo" ,//undo
        	"ctrl+89" : "Redo",
       		"ctrl+73" : "Italic",
       		"ctrl+85" : "Underline" ,//^U
        	"ctrl+shift+67" : "removeformat",
        	"ctrl+shift+76" : "justify:left",
        	"ctrl+shift+82" : "justify:right",
        	"ctrl+65" : "selectAll",
            "ctrl+13" : "autosubmit"//手动提交
    	};
    me.addListener('keydown',function(type,e){

        var keyCode = e.keyCode || e.which,value;
		for ( var i in shortcutkeys ) {
		    if ( /^(ctrl)(\+shift)?\+(\d+)$/.test( i.toLowerCase() ) || /^(\d+)$/.test( i ) ) {
		        if ( ( (RegExp.$1 == 'ctrl' ? (e.ctrlKey||e.metaKey) : 0)
                        && (RegExp.$2 != "" ? e[RegExp.$2.slice(1) + "Key"] : 1)
                        && keyCode == RegExp.$3
                    ) ||
                     keyCode == RegExp.$1
                ){

                    value = shortcutkeys[i].split(':');
                    me.execCommand( value[0],value[1]);
                    domUtils.preventDefault(e)
		        }
		    }
		}
    });
};