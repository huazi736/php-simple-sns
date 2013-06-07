var Textarea = Textarea || {};

Textarea.msgTip = function(elm, options) {
	this.$elm = $(elm);
	this.options = jQuery.extend({}, Textarea.msgTip.defaults, options);
	this.button = this.options.button;
	if (this.button.id) { //has button
		this.$btn = this.button.id;
	}
	this.initialize();
};

Textarea.msgTip.prototype = {
	initialize:function() {
		this.inject();
		this.notIE = !$.browser.msie;
		this.events = new Textarea.Events(this);
		this.notMedia = this.options.notMedia;
	},

	inject:function() {
		this.$textarea = this.$elm.find("textarea");
		if(this.$textarea == null) {
			this.textarea = $('<textarea></textarea>');
			this.$elm.append(this.$textarea)
		}
		this.$textarea.attr(this.options.textareaProps).css(this.options.textareaStyles);
        if(this.options.textareaProps.msg)
            this.$textarea.val(this.options.textareaProps.msg);
		this.$tip = $('<div class="tip"><span class="num">0</span>/' + this.options.maxlength + '</div>')
			.attr(this.options.tipProps)
			.css(this.options.tipStyles);
		this.$num = this.$tip.find(".num");
		this.$elm.append(this.$tip);
		if(this.$textarea.val() !== undefined){
			if(this.$textarea.val().length > this.options.maxlength){
				this.$tip.show();
				this.check();
			}
		}
	},

	check:function() {
		var val = this.options.iswordwrap?$.trim(this.$textarea.val()):$.trim(this.$textarea.val()).replace(/\n/g,''),
			len = val.length,
			regex = this.options.regexp,
			half,
			size,
			status = this.options.status || this.$btn.attr("data"),
			height;
		if (this.notIE) {
			this.$textarea.css("height", "auto");
		}
		if(this.options.pattern) {
			if (val.match(regex)) {
				half = val.match(regex).join("").length;
			}
			size = (len - half) + Math.ceil(half / 2);
		} else {
			size = len;
		}
		if (this.button.id && this.notMedia) {
            if (size == 0 || size > this.options.maxlength) {
				this.setBtnStatus(true);
			} else {
				this.setBtnStatus(false);
			}
		}
		if(this.button.id && !this.notMedia) {
            if(status != undefined) {
                if(status == "false" || size > this.options.maxlength) {
                    this.setBtnStatus(true)
                } else if(status == "true") {
                    this.setBtnStatus(false)
                }
            } else {
                if(size > this.options.maxlength) {
                    this.setBtnStatus(true);
                } else {
                    this.setBtnStatus(false);
                }
            }
		}
		if (size > this.options.maxlength) {
			this.$num.css("color", this.options.errorColor);
		} else {
			this.$num.css("color", "")
		}
		height = Math.max(this.$textarea[0].scrollHeight, this.options.textareaStyles.height);
		this.update(size, height);
	},

	setBtnStatus: function(isDisabled) {
        var callback;
        if(callback = this.options.textareaCallback) {
            this.textareaCallback = callback;
        }
		if(isDisabled && !this.$btn.hasClass(this.options.disableClass)) {
			this.$btn.removeClass(this.options.activeClass).addClass(this.options.disableClass);
			this.$btn.prop("disabled", true);
			if(this.textareaCallback)
				this.textareaCallback(true);
		} else if(!isDisabled && !this.$btn.hasClass(this.options.activeClass)) {
			this.$btn.removeClass(this.options.disableClass).addClass(this.options.activeClass);
			this.$btn.prop("disabled", false);
			if(this.textareaCallback)
                this.textareaCallback(false);
		}
	},

	reset: function() {
		if(this.$btn.hasClass(this.options.disableClass)) return false;
		this.$btn.removeClass(this.options.activeClass).addClass(this.options.disableClass);
		this.update(0, this.options.textareaStyles.height);
		this.$tip.hide();
	},

	update:function(size, height) {
		this.$textarea.css("height", height);
		this.$num.html(size);
	}
};

Textarea.msgTip.defaults = {
	maxlength:140,
	notMedia: false,
	iswordwrap:false,
	textareaProps:{
		"class":"textarea"
	},
	textareaStyles:{
		overflow:"hidden",
		height:47,
		"word-wrap": "break-word",
		"word-break": "break-all"
	},
	tipProps:{
		"class":"tip"
	},
	tipStyles: {
		"display": "none",
		"height": 18,
		"margin-top": 5,
		"text-align": "right"
	},
	regexp:/[a-zA-Z0-9\s~`!@#$%^&*()\-_+=\{\}\[\]\\|;\':"<>,\.\/?]*/g,
	activeClass:"active",
	disableClass:"disable",
	errorColor:"red",
	pattern: false//=字符占用字节（英文）
};

Textarea.Events = function(msgTip) {
	this.msgTip = msgTip;
	this.$textarea = this.msgTip.$textarea;
    this.msg = this.msgTip.options.textareaProps.msg;
	this.$btn = this.msgTip.$btn;
	this.attach();
};
Textarea.Events.prototype = {
	attach:function() {
		this.$textarea.on("input propertychange keyup", { self:this }, this.handleInput);
		this.$textarea.bind("focus", {self: this}, this.handleFocus);
		this.$textarea.bind("blur", {self: this}, this.handleBlur);
		this.$btn.on("click", {self: this}, this.btnClick);
	},
	handleInput:function(event) {
		var self = event.data.self;
		self.msgTip.check();
	},
	handleFocus: function(event) {
		var self = event.data.self;
        if(self.$textarea.val() == self.msg) {
            self.$textarea.val("");
        }
		self.msgTip.check();
		self.msgTip.$tip.show();
	},
	handleBlur: function(event) {
		var self = event.data.self;
        if(self.$textarea.val() == "") {
            self.$textarea.val(self.msg);
            self.msgTip.$tip.hide();
        }
	},
	btnClick: function(event) {
		var self = event.data.self;
        var callback = self.msgTip.button.callback;
        if(callback && typeof callback == "function") {
            callback(self.msgTip.reset);
        } else {
            self.msgTip.reset();
        }
	}
};