/*
 * Created on 2012-03-28
 * @author: Yewang
 * @desc: 学校、院系、公司、职位选择插件
 * @depends: popUp.js
*/

 (function($){
	function empty(){}
	function SELECT_SCHOOL_COMPANY(options, elem) {
		this.elem = elem;
		this.init(options);
	}
	SELECT_SCHOOL_COMPANY.prototype = {
		options: {
			parm: '',
			popWith: 730,
			frameHeight: 300,
			needClear : false,
			inputOnclick: empty,
			clearOnclick: empty
			//callback:function(){}
		},
		init: function(options) {
			var _this = this;
			_this.options = $.extend({}, _this.options, options);
			if(this.options.needClear){
				$('<div class="richSelect clearfix"><a class="richSelect-clear" style="display:none;"></a></div>').insertBefore(this.elem).append(this.elem);
			}
			this.events();
		},
		events: function() {
			var opt = this.options,
				self = this,
				$this = this.elem;
			$this.click(function() {
				if ($this.parent().next().find('.dkUserwikiNice').length>0) {//确保当前进行一个添加操作。
					return;
				};
				$('input').removeClass('operating_info');
				$this.addClass('operating_info').popUp({
					width: opt.popWith,
					title: opt.popTitle,
					buttons: '<span class="popBtns blueBtn closeBtn">关闭</span>',
					content: '<iframe id="selectSC" name="selectSC" width="100%" height="' +opt.frameHeight+ 'px" scrolling="no" frameborder="0" src=' +opt.url+ '&parm=' +opt.parm+ '></iframe>',
					callback: function() {
						
					}
				});
				$this.blur();
			});
			if(opt.needClear){
				$this.prev().click(function(){
					$this.addClass('operating_info').val("");
					$(this).hide();
					opt.clearOnclick.call($this, this);
				});
			}
		}
	};
	$.fn.selectSC = function(settings) {
		var opts = $.extend({},settings);
		for(var i = 0, len = $(this).length; i < len; i++) {
			var self = $(this)[i];
			return new SELECT_SCHOOL_COMPANY(opts, $(self));
		}
		
	};
 })(jQuery);