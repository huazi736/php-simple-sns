function CLASS_MSG(elm,options){

	this.$e = $(elm);
	this.opts = options;
	this.init();
}
CLASS_MSG.prototype = {
	init:function(){
		this.view();
		this.bindEventList();
		this.position();
	},
	view:function(){
		this.$span = $("<span class='input_msg'>"+this.$e.attr("msg")+"</span>");
		console.log("span")
		console.log(this.$span)
		if(this.$e.next("span.input_msg").size()==0){
			console.log(this.$span)
			this.$e.after(this.$span);
		}
		if(this.$e.val()!=""){
			this.$span.hide();
		}
		
	},
	position:function(){
		var p_left = parseInt(this.$e.parent().css("padding-left"));
		var e_left = parseInt(this.$e.css("margin-left"));
		var width = this.$e.attr("msg").length*12+20;
		var fontW = this.$e.css("font-weight");
		var fontS = this.$e.css("font-size");
		var lineH = parseInt(this.$span.css("line-height"));
        var top = parseInt(this.$e.css("padding-top"));
        if(this.$e.outerWidth>width){
			width = this.$e.outerWidth;
		}
		this.$e.parent().css({
			position:"relative"
		});
        top = this.opts.top||4;
        this.$span.css({
			left:parseInt(this.$e.css("padding-left"))+this.opts.border*1+p_left+e_left,
			top:top,
			width:width,
			overflow:"hidden",
			background:"#fff",
			"font-weight":fontW,
			"font-size":fontS
		});
	},
	bindEventList:function(){
		var self = this;
		/*
		this.$span.click(function(){
			self.$e.trigger("focus");
		});
		this.$e.focus(function(){
			self.$span.hide();
		});
		this.$e.blur(function(){
			if($(this).val()==""){
				self.$span.show();
			}else{
				self.$span.hide();
			}
		});
		this.$e.change(function(){
			if($(this).val()==""){
				self.$span.show();
			}else{
				self.$span.hide();
			}
		});
		*/
	}
};
DMIMI.plugin.msg = function(options){
	var opts = DMIMI.cpu.extend(DMIMI.plugin.msg.defaults, options);
	DMIMI.cpu.eachElem(function(dom,index){
		new CLASS_MSG(dom, opts,index);
	});
};
DMIMI.plugin.msg.defaults = {
	absolute:false,
	border:1,
	textSize:12
};
DMIMI.plugin.msg.public = {
	
}