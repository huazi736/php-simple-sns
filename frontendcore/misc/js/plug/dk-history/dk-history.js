(function($) {
    var locationWrapper = {
        put: function(hash, win) {
            (win || window).location.hash = this.encoder(hash);
        },
        get: function(win) {
            var hash = ((win || window).location.hash).replace(/^#/, '');
            try {
                return $.browser.mozilla ? hash : decodeURIComponent(hash);
            }
            catch (error) {
                return hash;
            }
        },
        encoder: encodeURIComponent
    };

    var iframeWrapper = {
        id: "__jQuery_history",
        init: function() {
            var html = '<iframe id="'+ this.id +'" style="display:none" src="javascript:false;" />';
            $("body").prepend(html);
            return this;
        },
        _document: function() {
            return $("#"+ this.id)[0].contentWindow.document;
        },
        put: function(hash) {
            var doc = this._document();
            doc.open();
            doc.close();
            locationWrapper.put(hash, doc);
        },
        get: function() {
            return locationWrapper.get(this._document());
        }
    };

    function initObjects(options) {
        options = $.extend({
                unescape: false
            }, options || {});

        locationWrapper.encoder = encoder(options.unescape);

        function encoder(unescape_) {
            if(unescape_ === true) {
                return function(hash){ return hash; };
            }
            if(typeof unescape_ == "string" &&
               (unescape_ = partialDecoder(unescape_.split("")))
               || typeof unescape_ == "function") {
                return function(hash) { return unescape_(encodeURIComponent(hash)); };
            }
            return encodeURIComponent;
        }

        function partialDecoder(chars) {
            var re = new RegExp($.map(chars, encodeURIComponent).join("|"), "ig");
            return function(enc) { return enc.replace(re, decodeURIComponent); };
        }
    }

    var implementations = {};

    implementations.base = {
        callback: undefined,
        type: undefined,

        check: function() {},
        load:  function(hash) {},
        init:  function(callback, options) {
            initObjects(options);
            self.callback = callback;
            self._options = options;
            self._init();
        },

        _init: function() {},
        _options: {}
    };

    implementations.timer = {
        _appState: undefined,
        _init: function() {
            var current_hash = locationWrapper.get();
            self._appState = current_hash;
            self.callback(current_hash);
            setInterval(self.check, 100);
        },
        check: function() {
            var current_hash = locationWrapper.get();
            if(current_hash != self._appState) {
                self._appState = current_hash;
                self.callback(current_hash);
            }
        },
        load: function(hash) {
            if(hash != self._appState) {
                locationWrapper.put(hash);
                self._appState = hash;
                self.callback(hash);
            }
        }
    };

    implementations.iframeTimer = {
        _appState: undefined,
        _init: function() {
            var current_hash = locationWrapper.get();
            self._appState = current_hash;
            iframeWrapper.init().put(current_hash);
            self.callback(current_hash);
            setInterval(self.check, 100);
        },
        check: function() {
            var iframe_hash = iframeWrapper.get(),
                location_hash = locationWrapper.get();

            if (location_hash != iframe_hash) {
                if (location_hash == self._appState) {    // user used Back or Forward button
                    self._appState = iframe_hash;
                    locationWrapper.put(iframe_hash);
                    self.callback(iframe_hash); 
                } else {                              // user loaded new bookmark
                    self._appState = location_hash;  
                    iframeWrapper.put(location_hash);
                    self.callback(location_hash);
                }
            }
        },
        load: function(hash) {
            if(hash != self._appState) {
                locationWrapper.put(hash);
                iframeWrapper.put(hash);
                self._appState = hash;
                self.callback(hash);
            }
        }
    };

    implementations.hashchangeEvent = {
        _init: function() {
            self.callback(locationWrapper.get());
            $(window).bind('hashchange', self.check);
        },
        check: function() {
            self.callback(locationWrapper.get());
        },
        load: function(hash) {
            locationWrapper.put(hash);
        }
    };

    var self = $.extend({}, implementations.base);

    if($.browser.msie && ($.browser.version < 8 || document.documentMode < 8)) {
        self.type = 'iframeTimer';
    } else if("onhashchange" in window) {
        self.type = 'hashchangeEvent';
    } else {
        self.type = 'timer';
    }

    $.extend(self, implementations[self.type]);
    $.history = self;
})(jQuery);


function CLASS_HISTORY(){
	this.Url = "index";
	this.suffix = "html";
	this.path = "comp"
	this.urlArr = null;
	this.left = $("#left");
	this.right = $("#right");
	this.top = $("#top");
	this.init();
}

CLASS_HISTORY.prototype={
	init:function(){
		var self = this;
		$.history.init(function(url) {
			if(url!=""){
				self.Url = url;
			}
			self.urlArr = self.Url.split("-");
			self.setTopNav();
			self.getLeft([self.setLeftNav,self.getRight,self.setFocus]);
			
		});
	},
	setFocus:function(self){
		var self = self;
		var thisUrl;
		self.default_url = self.left.find("li[default]").attr("default");
		if(self.urlArr.length<=1){
			thisUrl = self.Url+"-"+self.default_url;
		}else{
			thisUrl = self.Url
		}
		var $a = self.left.find(".nav").find("a[href=#"+thisUrl+"]");
		
		self.left.find(".nav").find("li").attr("class","");
		$a.parent().attr("class","on");
		var $topa = self.top.find(".nav").find("a[href^=#"+self.urlArr[0]+"]");
			self.top.find(".nav").find("li").attr("class","");
			$topa.parent().attr("class","on");
	},
	getLeft:function(arg){
		var self = this;
		var navName = self.urlArr[0]+"Nav";
		if(self.left.find("#"+navName).size()==0){
			$.djax({
				url:self.path+"/"+self.urlArr[0]+"/"+navName+"."+self.suffix,
				type:"GET",
				dataType:"HTML",
				success:function(data){
					self.left.html(data);
					arg[2](self);
					arg[0](self);
					arg[1](self);
				}
			});
		}else{
			arg[2](self);
			arg[1](self);
			
		}
	},
	getRight:function(self){
		var self = self;
		var realUrl  = self.Url.replace(/-/g,"/");
		
		if(self.urlArr.length<=1){
			realUrl = self.Url+"/"+self.default_url;
		}
					
		$.djax({
			url:self.path+"/"+realUrl+"."+self.suffix,
			type:"GET",
			dataType:"HTML",
			success:function(data){
				self.right.html(data);
			}
		});
	},
	setTopNav:function(){
		var self = this;
		self.top.find("div.nav").find("a").click(function(e) {
			var url = $(this).attr('href').replace(/^.*#/, '');
			self.top.find("div.nav").find("li").attr("class","");
			$(this).parent().attr("class","on");
			$.history.load(url);
			return false;
		});
	},
	setLeftNav:function(self){
		
		var self = self;
		self.left.find("div.nav").find("a").click(function(e) {
			var url = $(this).attr('href').replace(/^.*#/, '');
			self.left.find("div.nav").find("li").attr("class","");
			$(this).parent().attr("class","on");
			$.history.load(url);
			return false;
		});
	}
}