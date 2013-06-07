// JavaScript Document

var tripSort = {};
tripSort.view = function(fn, arg) {
	this.view._class = {
		//移上去，单行改变背景
		checkProvince : function(arg) {
			arg[0].hover(function() {
				$(this).css({
					'background' : '#e7ebf2'
				});
			}, function() {
				$(this).css({
					'background' : '#fff'
				});
			})
		}
	}
	return this.view._class[fn](arg);
}, tripSort.control = function(fn, arg) {
	this.control._class = {
		tab : function(arg) {
			//tab 市级切换景点
			if(arg[0].find('em').length < 1) {
				arg[0].find('li:first').append('<em></em>');
			}
			arg[0].find('li').mouseover(function() {
				if($(this).find('em').length < 1) {
					$(this).append('<em></em>').siblings().find('em').remove();
				}
				var index = $(this).index();
				var contain = $(this).parents('.city').find('.tab_contain');
				contain.eq(index).show().siblings().hide();
			});
			//隐藏或显示景点切换
			var a = arg[0].parents('.city').find('.tab_contain');
			a.each(function() {
				if($(this).height() > 50) {
					$(this).height(50);
					$(this).css({
					});
					if($(this).find('span').length < 1) {
						$(this).prepend("<span class='showYoursister'>");
					}
				} else {
					$(this).css({
						'height' : 'auto'
					});
				}
			});
			//切换景点事件绑定
			$('.showYoursister').click(function() {
				if(parseInt($(this).parent().height()) != 50) {
					$(this).parent().height(50);
					$(this).removeClass('show_icon');
				} else {
					$(this).parent().css({
						'height' : 'auto'
					});
					$(this).addClass('show_icon');
				}
			})
		},
		//市超过一行处理
		autoCity : function(arg) {
			arg[0].each(function() {
				var temp = 0;
				var len = $(this).find('li').length;
				$(this).find('li').each(function(index) {
					temp += $(this).width();
				})
				temp += 20 * len;
				var ul_width = $(this).width(temp);
				var div_width = $(this).parent().width();
				if(temp-20 > div_width) {
					$(this).closest('h6').append("<em class='move_left'></em><em class='move_right'></em>");
				}
			})
			var left=0;
			$('.move_left').click(function() {
				var ul_tab = $(this).parent().find('.ul_tab');
				var li_width=0;
				var ulLeft=ul_tab.position().left;
				ul_tab.find('li').each(function(index){
					li_width=$(this).width();
				})
				li_width+=20;
				if(ulLeft<0){
					left+=li_width;
					ul_tab.css({
						'left' : left
					})
				}else{
					ul_tab.css({
						'left' : 0
					})
				}
			});
			$('.move_right').click(function() {
				var ul_tab = $(this).parent().find('.ul_tab');
				var li_width=0;
				var ulLeft=ul_tab.position().left;
				var difference = ul_tab.width() - $(this).parent().find('.fix').width();
				ul_tab.find('li').each(function(index){
					li_width=$(this).width();
				})
				li_width+=20;
				var intLeft=left*-1;
				if(difference-intLeft>20){
					left-=li_width;
					ul_tab.css({
						'left' : left
					})
				}
			})
		}
	}
	return this.control._class[fn](arg);
}, tripSort.model = function(fn, arg) {
	this.model._class = {
	}
	return this.model._class[fn](arg);
}, tripSort.init = function(fn, arg) {
	var $city = $('.city');
	var $tabUl = $('.ul_tab');
	$('.tab_box').each(function() {
		$(this).find('.tab_contain').not(':first').hide();
	})
	tripSort.view('checkProvince', [$city]);
	tripSort.control('tab', [$tabUl]);
	tripSort.control('autoCity', [$tabUl]);
}
$(function() {
	tripSort.init();
})