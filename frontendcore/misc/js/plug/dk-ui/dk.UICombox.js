/**
 * Created on 2011-12-16
 * @author: willian12345@126.com
 * @desc: dk.UICombox(下拉菜单组件)
 * $(dom).uiCombox({
		selectorName:'下拉菜单',
		lis: null, //combox菜单内容 默认结构需要传递相应数组 如：[{ref:0,text:'item0'},{ref:1,text:'item1'},{ref:2,text:'item2'},{ref:3,text:'item3'}]
		defaultSelect:-1,//初始化时默认选项
		callback: function(){}, //combox某个菜单后执行回调函数
		selectFieldName:'',//隐藏域<input type="hidden" />
		selectFieldValue:'',//隐藏域的值
		selectorWidth: 'auto', //combox按钮宽度
		width: 112, //combox菜单宽度
		activeSelected:false,//是否触发已选择菜单的回调函数
		menuPosition: [0, 24]//下拉菜单默认位置
	});
 * 
 * @version:1.3
 * Update desc
 * 添加禁止选择功能
 * 
 * @version: 1.2
 * Update desc
 *  1、添加菜单靠左靠右的选择
 *  2、将选择后执行text()换成html()获取整个html结构插入$selectedTarget
 *  
 * @eg:
 * 
 * HTML中放置div
 * <div id="myCombox1"></div>
 * 
 * javascript文件中
 * $('div#myCombox1').uiCombox({
		selectorName: '请选择',
		lis: [{ref:0,text:'item0'},{ref:1,text:'item1'},{ref:2,text:'item2'},{ref:3,text:'item3'}],
		defaultSelect:0,
		callback:_callback,
		selectorWidth: 400,
		width:429
	});
 * 
 * 
 * 
 * javascript默认生成普通下拉菜单结构：
 * <div class="uiCombox" value=""> 
  		<div class="uiComboxTit"><a class="uiButton uiSelectorButton uiComboxSelector png" href="javascript:void(0);"><i class="uiButtonIcon plus"></i><span class="uiButtonText uiComboxSelectedTarget">下拉菜单</span></a></div>
  		<div class="uiComboxMenu" target="#">
  			<ul>
  				<li ref="0"><a href="javascript:void(0);">item1</a></li>
  				<li ref="1" class="selected"><a href="javascript:void(0);">item2</a></li>
  				<li ref="2"><a href="javascript:void(0);">item3</a></li>
  				<li ref="3"><a href="javascript:void(0);">item4</a></li>
  			</ul>
  		</div>
	</div>
 *
 *
 * 
 * 
 **/


(function($){
	function CLASS_UIcombox(elem, options){
		this.$e = $(elem);
		this.opts = options;
		this.$selector = null;
		this.$menu = null;
		this.$selectedTarget = null;//选择后文本插入目标
		this.comboxWidth = 0;//选择菜单宽度
		this.selectorWidth = 0;//选择按钮的宽度
		this.menuPosition = null;
		this.selectField = null;
		this.init();
		
	}
	
	CLASS_UIcombox.prototype = {
		init: function(){
			this.view();
			this.bindEvent();
		},
		view: function(){
			var that = this;
			var str = '';
			var isArray = that.opts.lis instanceof Array;
			str += '<div class="uiComboxTit">';
			if(that.opts.selectFieldName){
				str += '<input type="hidden" name="'+ that.opts.selectFieldName +'" value="'+ that.opts.selectFieldValue +'" />';
			}
			str += that.opts.selectorStruc;
			str += '</div><div class="uiComboxMenu" target="#">'; 
			str += '<ul>';
			if(isArray){
				var tempArray = that.opts.lis;
				for(var i=0; i<that.opts.lis.length; i++){
					str += '<li ref="'+ tempArray[i].ref +'"><a href="javascript:void(0);">'+ tempArray[i].text +'</a></li>';
				}
			}
			str += '</ul>';
			str += '</div>';
			if(isArray){
				that.$e.addClass('uiCombox').append(str);
			}else{
				that.$e.addClass('uiCombox').append(str).find('.uiComboxMenu > ul').append(that.opts.lis);
			}
			
			
			that.callback = that.opts.callback;//回调函数
			that.comboxWidth = that.opts.width;//下拉菜单宽度
			that.menuPosition = that.opts.menuPosition//下拉菜单位置
			that.$selectedTarget = that.$e.find('.uiComboxSelectedTarget');
			that.$selector = that.$e.find('.uiComboxSelector');
			that.$selectedTarget.text(that.opts.selectorName);
			that.$menu = that.$e.find('.uiComboxMenu');
			
			if(that.opts.selectFieldName){
				that.selectField = that.$e.find('input[name='+ that.opts.selectFieldName +']');
			}
			
			if(that.opts.defaultSelect > -1){//如果有默认值则初始化各项值
				var $uiComboxMenu = that.$e.find('.uiComboxMenu');
				if (that.$e.attr('ids')) {//为自定义权限插件做特殊处理
					var $selfSet = $uiComboxMenu.find('li[ref="0"]');
					$selfSet.addClass('selected');
					that.$e.attr('value', $selfSet.attr('ref'));
					that.$selectedTarget.html($selfSet.find('a').html());
					if (that.selectField) 
						that.selectField.val($selfSet.attr('ref'));
				} else {
					$uiComboxMenu.find('li').each(function(i, v){
						if ($(v).attr('ref') * 1 === that.opts.defaultSelect) {
							$(v).addClass('selected');
							that.$e.attr('value', $(v).attr('ref'));
							that.$selectedTarget.html($(v).find('a').html());
							if (that.selectField) 
								that.selectField.val($(v).attr('ref'));
						}
					});
				}
			}else{
				that.$e.attr('value', '-1');
			}
			var mFull = (that.opts.selectorWidth == '100%') ? 'px' : '';
			if(that.opts.width){
				that.$menu.css('width', that.opts.width + mFull);
			}
			var sAuto = (that.opts.selectorWidth != 'auto') ? 'px' : '';
			that.$selector.css('width', that.opts.selectorWidth + sAuto);
			
			if(that.menuPosition){
				that.$menu.css({
					'left': that.menuPosition[0] + 'px',
					'top': that.menuPosition[1] + 'px'
				});
			}
			if(that.opts.menuRight){
				that.$menu.css({
					'left': 'auto',
					'right':0
				});
			}
			
			that.$menu.hide();
			if (!that.opts.disabled) {
				that.$selector.live('click', function(e){
					$('.uiCombox .uiComboxMenu').not(that.$menu).hide();//隐藏页面中其它已打开的uiComboxMenu
					$('.uiComboxSelector').removeClass('hightLight');
					if (that.$menu.css('display') == 'none') {
						that.$selector.addClass('hightLight');
						that.$menu.css({//兼容ie6-7关闭事件
							'display': 'block',
							'visibility': "visible"
						});
					}
					else {
						that.$selector.removeClass('hightLight');
						that.$menu.css({//兼容ie6-7关闭事件
							'display': 'none',
							'visibility': "hidden"
						});
					}
					
					$('.uiCombox').css('z-index', '5');
					that.$e.css('z-index', '6');
					e.stopPropagation();
				});
			}
		},
		bindEvent: function(){
			var that = this;
			this.$menu.find('li').die().live('click',function(){
				if(!$(this).hasClass('selected') || that.opts.activeSelected){
					that.setValue($(this));
				}
			}).hover(
				function(){
					$(this).addClass('hightLight')
				},
				function(){
					$(this).removeClass('hightLight')
				}
			); 
			
			//注册全局关闭事件
			DKLayerHider.addHideItem('div.uiCombox div.uiComboxMenu',function(){
				$('.uiComboxSelector').removeClass('hightLight');
			});
			$('#wrap').click(function(){//兼容ie6-7关闭事件
				$('.uiComboxMenu').css({
					'display': 'none',
					'visibility':"hidden"
				});
			})
			
			
		},
		setValue:function(li){
			var that = this;
			$(li).addClass('selected').siblings().removeClass('selected');
			that.$selectedTarget.html($(li).find('a').html());
			that.$e.attr('value',$(li).attr('ref'));
			
			if(that.selectField){
				that.selectField.val($(li).attr('ref'));
			}
			
			if(that.callback != null){
				that.callback($(li).attr('ref'),that.$e);
			}
		},
		externalCall:function(num){
			var that = this;
			that.$e.find('.uiComboxMenu').find('li').each(function(i,v){
				if($(v).attr('ref') * 1 === num){
					that.setValue($(v));
				}
			});
		}
	}
	
	$.fn.uiCombox = function(options){
		
		var opt = $.extend({},$.fn.uiCombox.defaults, options)
		if($(this).html() !== ''){
			return false;
		}
	
		return new CLASS_UIcombox(this,opt);
	};
	$.fn.uiCombox.defaults = {
		selectorStruc:'<a class="uiButton uiSelectorButton uiComboxSelector png" href="javascript:void(0);"><span class="uiButtonText uiComboxSelectedTarget">下拉菜单</span></a>',
		selectorName:'下拉菜单',
		lis: null, //选择菜单内容 <li><a href="javascript:void(0);"></a></li>[string],[dom],[jqurey]
		defaultSelect:-1,
		callback: null, //选择某个菜单后执行回调函数
		selectFieldName:null,//默认不需要表单input元素
		selectFieldValue:0,//默认input元素的值
		selectorWidth: 'auto', //选择按钮宽度
		width: '100%', //选择菜单宽度
		activeSelected:false,//是否触发已选择菜单的回调函数
		disabled:false,
		menuPosition:[],
		menuRight:false//菜单是否默认靠右
	};
})(jQuery);
