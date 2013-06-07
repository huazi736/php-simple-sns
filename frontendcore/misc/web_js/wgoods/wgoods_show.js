/*
*author: zhuliqi
*
*就一个快速返回按钮的功能
*
*/
function Wgoods_show(){
	var _self = this;
	
}
Wgoods_show.prototype = {
	init: function(){
		var _self = this;
		_self.addQuickButton();
		
	},
	addQuickButton: function(){
		var _self = this;

		var $backToTopEle = $('	<div class="upPopUp"></div>').appendTo($('.wgoodsShowContent')).click(function(){
			$("html,body").animate({scrollTop:0},120);
		}) .hover(function(){
			$(this).removeClass("upPopUpS")
			$(this).addClass("upPopUpB");
		},function(){
			$(this).removeClass("upPopUpB");
			$(this).addClass("upPopUpS");
			if($(document).scrollTop() == 0){$(this).hide();};
		}) ,$backToTopFun = function(){
			var st = $(document).scrollTop(),winh = $(window).height();
			if(st > 0){
				$backToTopEle.show();
			}else{
				$backToTopEle.hide();
			}

			if(!window.XMLHttpRequest){
				$backToTopEle.css("top",st + winh - 166);
			}
		};
		$(window).bind("scroll",$backToTopFun);
		$backToTopFun();
		

	}
}

$(document).ready(function(){
	var wgoods_show = new Wgoods_show();
	wgoods_show.init();
})