/**
 * @author 	
 */
//v1.7
// Flash Player Version Detection
// Detect Client Browser type
// Copyright 2005-2007 Adobe Systems Incorporated. All rights reserved.
var js4swf = function() {
	this.isIE = (navigator.appVersion.indexOf("MSIE") != -1) ? true : false;
	this.isWin = (navigator.appVersion.toLowerCase().indexOf("win") != -1) ? true : false;
	this.isOpera = (navigator.userAgent.indexOf("Opera") != -1) ? true : false;
}

js4swf.prototype = {
	ControlVersion : function() {
		var version;
		var axo;
		var e;
		// NOTE : new ActiveXObject(strFoo) throws an exception if strFoo isn't in the registry
		try {
			// version will be set for 7.X or greater players
			axo = new ActiveXObject("ShockwaveFlash.ShockwaveFlash.7");
			version = axo.GetVariable("$version");
		} catch (e) {
		}
		if(!version) {
			try {
				// version will be set for 6.X players only
				axo = new ActiveXObject("ShockwaveFlash.ShockwaveFlash.6");
				// installed player is some revision of 6.0
				// GetVariable("$version") crashes for versions 6.0.22 through 6.0.29,
				// so we have to be careful.
				// default to the first public version
				version = "WIN 6,0,21,0";
				// throws if AllowScripAccess does not exist (introduced in 6.0r47)
				axo.AllowScriptAccess = "always";
				// safe to call for 6.0r47 or greater
				version = axo.GetVariable("$version");
			} catch (e) {
			}
		}
		if(!version) {
			try {
				// version will be set for 4.X or 5.X player
				axo = new ActiveXObject("ShockwaveFlash.ShockwaveFlash.3");
				version = axo.GetVariable("$version");
			} catch (e) {
			}
		}
		if(!version) {
			try {
				// version will be set for 3.X player
				axo = new ActiveXObject("ShockwaveFlash.ShockwaveFlash.3");
				version = "WIN 3,0,18,0";
			} catch (e) {
			}
		}
		if(!version) {
			try {
				// version will be set for 2.X player
				axo = new ActiveXObject("ShockwaveFlash.ShockwaveFlash");
				version = "WIN 2,0,0,11";
			} catch (e) {
				version = -1;
			}
		}
		return version;
	},
	// JavaScript helper required to detect Flash Player PlugIn version information
	GetSwfVer : function() {
		// NS/Opera version >= 3 check for Flash plugin in plugin array
		var flashVer = -1;
		if(navigator.plugins != null && navigator.plugins.length > 0) {
			if(navigator.plugins["Shockwave Flash 2.0"] || navigator.plugins["Shockwave Flash"]) {
				var swVer2 = navigator.plugins["Shockwave Flash 2.0"] ? " 2.0" : "";
				var flashDescription = navigator.plugins["Shockwave Flash" + swVer2].description;
				var descArray = flashDescription.split(" ");
				var tempArrayMajor = descArray[2].split(".");
				var versionMajor = tempArrayMajor[0];
				var versionMinor = tempArrayMajor[1];
				var versionRevision = descArray[3];
				if(versionRevision == "") {
					versionRevision = descArray[4];
				}
				if(versionRevision[0] == "d") {
					versionRevision = versionRevision.substring(1);
				} else if(versionRevision[0] == "r") {
					versionRevision = versionRevision.substring(1);
					if(versionRevision.indexOf("d") > 0) {
						versionRevision = versionRevision.substring(0, versionRevision.indexOf("d"));
					}
				}
				var flashVer = versionMajor + "." + versionMinor + "." + versionRevision;
			}
		}
		// MSN/WebTV 2.6 supports Flash 4
		else if(navigator.userAgent.toLowerCase().indexOf("webtv/2.6") != -1)
			flashVer = 4;
		// WebTV 2.5 supports Flash 3
		else if(navigator.userAgent.toLowerCase().indexOf("webtv/2.5") != -1)
			flashVer = 3;
		// older WebTV supports Flash 2
		else if(navigator.userAgent.toLowerCase().indexOf("webtv") != -1)
			flashVer = 2;
		else if(this.isIE && this.isWin && !this.isOpera) {
			flashVer = this.ControlVersion();
		}
		return flashVer;
	},
	// When called with reqMajorVer, reqMinorVer, reqRevision returns true if that version or greater is available
	DetectFlashVer : function(reqMajorVer, reqMinorVer, reqRevision) {
		versionStr = this.GetSwfVer();
		if(versionStr == -1) {
			return false;
		} else if(versionStr != 0) {
			if(this.isIE && this.isWin && !this.isOpera) {
				// Given "WIN 2,0,0,11"
				tempArray = versionStr.split(" ");
				// ["WIN", "2,0,0,11"]
				tempString = tempArray[1];
				// "2,0,0,11"
				versionArray = tempString.split(",");
				// ['2', '0', '0', '11']
			} else {
				versionArray = versionStr.split(".");
			}
			var versionMajor = versionArray[0];
			var versionMinor = versionArray[1];
			var versionRevision = versionArray[2];
			// is the major.revision >= requested major.revision AND the minor version >= requested minor
			if(versionMajor > parseFloat(reqMajorVer)) {
				return true;
			} else if(versionMajor == parseFloat(reqMajorVer)) {
				if(versionMinor > parseFloat(reqMinorVer))
					return true;
				else if(versionMinor == parseFloat(reqMinorVer)) {
					if(versionRevision >= parseFloat(reqRevision))
						return true;
				}
			}
			return false;
		}
	},
	AC_AddExtension : function(src, ext) {
		//if(src.indexOf('?') != -1)
		return src;
		//else
			//return src + ext;
	},
	AC_Generateobj : function(objAttrs, params, embedAttrs, container) {
		var str = '';
		if(this.isIE && this.isWin && !this.isOpera) {
			/*var object = document.createElement("object");
			var ckassid = document.createAttribute("ckassid");	
			ckassid.nodeValue = objAttrs["classid"];
			
			object.setAttributeNode(ckassid);
			for(var i in objAttrs) {
				if(i != "classid"){
					object[i] = objAttrs[i]
				}
			}
			document.getElementById(container).appendChild(object);
			for(var i in params) {
				var param = document.createElement("param");
				var reg = new RegExp("^(.)","g");
				var w = reg.test(i);
				var u = RegExp["$1"].toUpperCase();
				param.name = i.replace(reg,u);
				param.value = params[i];
				document.getElementById("upload").getElementsByTagName("object")[0].appendChild(param);
			}*/
			str += '<object ';
			for(var i in objAttrs) {
				str += i + '="' + objAttrs[i] + '" ';
			}
			str += '>';
			for(var i in params) {
				str += '<param name="' + i + '" value="' + params[i] + '" /> ';
			}
			str += '</object>';
		} else {
			str += '<embed ';
			for(var i in embedAttrs) {
				str += i + '="' + embedAttrs[i] + '" ';
			}
			str += '> </embed>';
		}
		if(container)
			container.innerHTML = str;
	},
	AC_FL_RunContent : function() {
		var ret = this.AC_GetArgs(arguments, ".swf", "movie", "clsid:d27cdb6e-ae6d-11cf-96b8-444553540000", "application/x-shockwave-flash");
		this.AC_Generateobj(ret.objAttrs, ret.params, ret.embedAttrs, ret.container);
	},
	AC_SW_RunContent : function() {
		var ret = this.AC_GetArgs(arguments, ".dcr", "src", "clsid:166B1BCA-3F9C-11CF-8075-444553540000", null);
		this.AC_Generateobj(ret.objAttrs, ret.params, ret.embedAttrs);
	},
	thisMovie: function(movieName){
		if(navigator.appName.indexOf("Microsoft")!=-1)
    	{
	    	return window[movieName];  	
    	}else{
    		return document[movieName];
    	}
	},
	AC_GetArgs : function(args, ext, srcParamName, classid, mimeType) {
		var ret = new Object();
		ret.objAttrs = new Object();
		ret.params = new Object();
		ret.embedAttrs = new Object();
		ret.embedAttrs["flashvars"] = ret.params["flashvars"] = "";
		ret.embedAttrs["wmode"] = ret.params["wmode"] = "window";
		ret.embedAttrs["bgcolor"] = ret.params["bgcolor"] = "#ffffff";
		ret.embedAttrs["menu"] = ret.params["menu"] = "false";
		ret.embedAttrs["allowScriptAccess"] = ret.params["allowScriptAccess"] = "always";
		ret.embedAttrs["width"] = ret.objAttrs["width"] = "100%";
		ret.embedAttrs["height"] = ret.objAttrs["height"] = "100%";
		ret.embedAttrs["name"] = ret.objAttrs["name"] = "flashvideoupload";
		ret.embedAttrs["id"] = ret.objAttrs["id"] = "flashvideoupload";
		ret.embedAttrs["style"] = ret.objAttrs["style"] = 'display:block;';
		
		
		args = args[0];
		for(var i in args) {
			var currArg = i.toLowerCase();
			switch (currArg) {
				case "appendto":
					ret.container = args[i];
					break;
				case "width":
				case "height":
					ret.embedAttrs[i] = ret.objAttrs[i] = args[i];
				case "types":
				case "size":
				case "url":
					var url = (typeof args[i] == "string")? args[i].replace(/\&/gi,","):args[i];
					ret.embedAttrs["flashvars"] = ret.params["flashvars"] += "&" + i + "=" + url;
					break;
				case "oninit":
					this.init = args[i];
					break;
				case "onselect":
					this.select = args[i];
					break;
				case "onsid":
					this.sid = args[i];
					break;
				case "onstart":
					this.start = args[i];
					break;
				case "oncancel":
					this.cancel = args[i];
					break;
				case "onprogress":
					this.progress = args[i];
					break;
				case "oncomplete":
					this.complete = args[i];
					break;
				case "onwarn":
					this.warn = args[i];
					break;
				case "onagain":
					this.again = args[i];
					break;
				case "classid":
					break;
				case "pluginspage":
					ret.embedAttrs[args[i]] = 'http://www.macromedia.com/go/getflashplayer';
					break;
				case "src":
				case "movie":
					args[i] = this.AC_AddExtension(args[i], ext);
					ret.embedAttrs["src"] = args[i];
					ret.params[srcParamName] = args[i];
					break;
				case "onafterupdate":
				case "onbeforeupdate":
				case "onblur":
				case "oncellchange":
				case "onclick":
				case "ondblclick":
				case "ondrag":
				case "ondragend":
				case "ondragenter":
				case "ondragleave":
				case "ondragover":
				case "ondrop":
				case "onfinish":
				case "onfocus":
				case "onhelp":
				case "onmousedown":
				case "onmouseup":
				case "onmouseover":
				case "onmousemove":
				case "onmouseout":
				case "onkeypress":
				case "onkeydown":
				case "onkeyup":
				case "onload":
				case "onlosecapture":
				case "onpropertychange":
				case "onreadystatechange":
				case "onrowsdelete":
				case "onrowenter":
				case "onrowexit":
				case "onrowsinserted":
				case "onstart":
				case "onscroll":
				case "onbeforeeditfocus":
				case "onactivate":
				case "onbeforedeactivate":
				case "ondeactivate":
				case "type":
				case "id":
					ret.objAttrs[i] = args[i];
					break;
				case "codebase":
					ret.objAttrs[i] = 'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0';
					break;
				case "align":
				case "vspace":
				case "hspace":
				case "class":
				case "title":
				case "accesskey":
				case "name":
				case "tabindex":
				case "style":
					ret.embedAttrs[i] = ret.objAttrs[i] = args[i];
					break;
				case "quality":
					ret.embedAttrs[i] = ret.params[i] = 'high';
					break;
				default:
					ret.embedAttrs[i] = ret.params[i] = args[i];
			}
		}
		ret.objAttrs["classid"] = classid;
		if(mimeType)
			ret.embedAttrs["type"] = mimeType;
		return ret;
	},
	onInit : function(list) {
		// 初始化时调用, 若list.length> 0 代表有可续传文件
		// [{file}, {file}]
		this.init(list);
	},
	onSelect : function(name) {
		this.select(name);
	},
	onSid : function(evt) {
		// 获得sid后返回, 更新sid用 (key, sid, name, type, size)
		this.sid();
	},
	onStart : function() {
		// 开始上传 (选择文件后自动开始)
		this.start();
	},
	onCancel : function() {
		// 上传取消事件
		this.cancel();
	},
	onProgress : function(evt) {
		// 上传进度事件 (evt.bytesLoaded, evt.bytesTotal, evt.speed) m=1 时无该事件
		this.progress();
	},
	onComplete : function(evt) {
		// 上传完成事件 (包含文件信息和完成后返回数据(evt.data))
		this.complete(evt);
	},
	onWarn : function(error) {
		// 报错事件 (key, msg)
		this.warn(error);
	},
	onAgain : function(){
		//重新上传触发的事件
		this.again();
	}
}

window.videoUpload = new js4swf();