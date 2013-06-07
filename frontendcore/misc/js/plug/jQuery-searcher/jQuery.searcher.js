/**
 * Created on 2011-12-15
 * @author: willian12345@126.com
 * @desc: 从本地或远程获得json数据解释排序后实现autoComplete实时查找信息
 * @version: 1.0
 * @eg:
 * 请参考站内信发新消息时输入姓名查找功能
 * $('#toPeopleInput').takeInfo({
 *		inputWrap:null, //放置input的container
		tokenArea:null, //已匹配信息container
		url:null, //实时匹配请求地址
		compactArea:null, //响应的数据
		compactAreaOffset:[0, 0], //匹配列表框离输入框偏移位置
		staticData:null,//是否是本地缓存数据需要json格式
		showNum:null //是否设置显示的最多条数
	});
 * */
 
(function($){
	function CLASS_Searcher(elem, options){
		var self = this;
		this.timeOutObject = null;
		this.enableGetData = true;
		this.staticDataCallback = null;
		this.$e = $(elem);
		this.opts = options;
		this.init();
		this.timeChange =null;
		this.tempValue ="";
		this.KEY = {
				UP: 38,
				DOWN: 40,
				BACKSPACE: 8,
				ENTER:13
			};
	}
	
	CLASS_Searcher.prototype = {
		init: function(){
			this.bindEvent();
		},
		view: function(func,args){
			var that = this;
			var view_Class = {
				compactAreaPosition: function(){//控制匹配列表的显示与位置
					var offsetCordding = that.$e.position();
					$(that.opts.compactArea).find('ul').html('');
					$(that.opts.compactArea).show().css('left', offsetCordding.left + that.opts.compactAreaOffset[0] + 'px').css('top', offsetCordding.top + that.opts.compactAreaOffset[1] + 'px');
				},
				showCompact: function(data){//匹配到的数据填充到匹配列表
					$(that.opts.compactArea).show();
					if (!that.opts.staticData) {
						var _str = '';
						if (data.compactedObjects.length>0) {
							for (var i = 0; i < data.compactedObjects.length; i++) {
								var _selected = (i == 0 ? ' class="selected"' : '');
								_str += '<li' + _selected + '><img class="uiProfilePhoto" src="' + data.compactedObjects[i]['avatar'] + '" /><span class="compactName" rel="' + data.compactedObjects[i]['userid'] + '">' + data.compactedObjects[i]['username'] + '</span><div><span>' + data.compactedObjects[i]['location'] + '</span></div></li>';
							}
						}
						else {
							$(that.opts.compactArea).hide();
						}
						$(that.opts.compactArea).find('ul').html(_str).find('li').hover(function(){
							$(this).addClass('selected').siblings().removeClass('selected');
						}, function(){
							$(this).removeClass('selected');
						}).click(function(){
							that.insertToken(this);
						});
						var h = data.compactedObjects.length *38;
						h>200 ? $(that.opts.compactArea).css({height:200,overflow:"auto"}): $(that.opts.compactArea).css({height:h,overflow:"hidden"});
					}else{
						$(that.opts.compactArea).show().find('ul').html('');
						var _str = '';
						if (data.length > 0) {
							for (var i = 0; i < data.length; i++) {
								var _selected = (i == 0 ? ' class="selected"' : '');
								_str += '<li' + _selected + '><img class="uiProfilePhoto" src="' + data[i].item['avatar'] + '" /><span class="compactName" rel="' + data[i].item['userid'] + '">' + data[i].item['username'] + '</span><div><span>' + data[i].item['location'] + '</span></div></li>';
							}
						}
						else{
							$(that.opts.compactArea).hide();
						}
						$(that.opts.compactArea).find('ul').append(_str).find('li').hover(function(){
							$(this).addClass('selected').siblings().removeClass('selected');
						}, function(){
							$(this).removeClass('selected');
						}).click(function(){
							that.insertToken(this);
						});
					}
				}
			}
			return view_Class[func](args);
		},
		bindEvent: function(){
			var that = this;
			
			/** 默认获得输入焦点 **/
			this.$e.focus();
			$(this.opts.inputWrap).click(function(){
				that.$e.focus();
			});
			var msgBrowser_Opera = navigator.userAgent.indexOf('Opera');
			/**当用户在<发送到信息框>中输入时实时匹配显示输入**/
			if (!that.opts.staticData){
				if (that.enableGetData){
					that.$e.focus(function(e){
						that.timeChange = window.setInterval(function(){
								var keyName = that.$e.val();
								if(keyName.length>0 && keyName != that.tempValue){
									that.getInfoRemote(e);
								}
								that.tempValue=keyName;
						}, 200);
					})
					this.$e.blur(function(e){
						setTimeout(function(){
						window.clearInterval(that.timeChange);},100);
					});
				}
			}else{
				that.getInfoLocal();
			}
			/**** 为输入框绑定键盘按下事件up,down,enter,backspace ****/
			that.$e.bind('keydown', function(e){
				var compactedHuman = $(that.opts.compactArea).find('li');	
				var currentIndex = 0;
				for (var i = 0; i < compactedHuman.length; i++) {
					if ($(compactedHuman[i]).hasClass('selected')) {
						currentIndex = i;
					}
				}
				var KEY = that.KEY;
				switch (e.keyCode){
					case KEY.UP:
						$(compactedHuman[currentIndex]).removeClass('selected');
						if (currentIndex < 1) {
							currentIndex = compactedHuman.length;
						}
						$(compactedHuman[currentIndex - 1]).addClass('selected');
						currentIndex--;
						break;
					case KEY.DOWN:
						$(compactedHuman[currentIndex]).removeClass('selected');
						if (currentIndex >= compactedHuman.length - 1) {
							currentIndex = -1;
						}
						$(compactedHuman[currentIndex + 1]).addClass('selected');
						currentIndex++;
						break;
					case KEY.ENTER:
						for (i = 0; i < compactedHuman.length; i++) {
							if ($(compactedHuman[i]).hasClass('selected')) {
								that.insertToken(compactedHuman[i]);
							}
						}
						return false;
						break;	
					case KEY.BACKSPACE:
						if(that.$e.val() == ''){
							$(that.opts.tokenArea).find('span').eq($(that.opts.tokenArea).find('span').length - 1).remove();
							$(that.opts.compactArea).hide();
						}
						break;	
				}
			}).bind('focusout',function(e){	
				var _this = this;
				setTimeout(function(){
					if($(that.opts.tokenArea).find('span').length <= 0){
						$(_this).val('请输入一个你朋友的名字').css({
							color: '#ccc',
							width: '200px'
						});
					}
					$(_this).val('');
					$(that.opts.compactArea).hide();
					ison=false;
				},500)
			}).bind('focusin',function(){
					$(this).val('').css({
						color: '#000',
						width: '30px'
					});
			});
			/** 删除已插入的信息 **/
			$('body').delegate('.deleteToken','click', function(){
				$(this).parent().remove();
				$(that.opts.inputWrap).find('input.toPeopleInput').show();
			});	

			//初史化时进行数量判断
			if(that.opts.showNum){
				var listNum = $(that.opts.tokenArea).find('span').index();
				if( listNum >= that.opts.showNum ){
					$(that.opts.inputWrap).find('input.toPeopleInput').hide();
				}else{
					$(that.opts.inputWrap).find('input.toPeopleInput').show();
				}
			}
		},
		getInfoRemote: function(e){
			var that = this;
			var KEY = that.KEY;
			if (that.enableGetData && $.trim(this.$e.val()) != '' && e.keyCode != KEY.UP && e.keyCode != KEY.DOWN && e.keyCode != KEY.ENTER){
				//that.enableGetData = false;
				that.view('compactAreaPosition');
				var _userids = '';//已添加的匹配信息id，用于过滤
				var _searchString = that.$e.val();
				var spans = $(that.opts.tokenArea).find('span');
				for(var i=0,spansLength = spans.length;i < spansLength; i++){
					_userids += $(spans[i]).attr('rel');
					if(i<spansLength - 1){
						_userids += ',';
					}
				}
				/*实时请求服务器得到匹配的好友列表*/
				$.ajax({
					type:that.opts.type||"POST", 
					url:that.opts.url,
					dataType:that.opts.dataType||'json',
					data:({userids:_userids,searchString:_searchString}),
					beforeSend: function(XMLHttpRequest){},
					success: function(data){
						data = data.data;
						if(data){
							that.view('showCompact',data);
							clearTimeout(that.timeOutObject);
							that.enableGetData = true;
						}
					}
				});
			}
			 
			if($.trim(that.$e.val()) == ''){/*输入框为空值时清空匹配列表 */
				$(that.opts.compactArea).find('ul').innerHTML = '';
				$(that.opts.compactArea).hide();
			}
		},
		getInfoLocal: function(){
			var that = this;
			
			that.staticDataCallback = function(_result){//排序数据回调函数
				if(_result){
					that.view('showCompact',_result);
				}
			}
			
			/** 调用ViolenceSearch排序类 **/
			ViolenceSearch.init({
				input: that.$e,
				resource: that.opts.staticData,
				filter: that.opts.tokenArea,
				filterWord: 'username',
				filterKey: 'userid',
				isFilterSelected:true,
				callback: that.staticDataCallback,
				descend: false
			});
		},
		insertToken: function(_currentGet){
			var that = this;
			var _rel = $(_currentGet).find('.compactName').attr('rel');/*用户id*/
			var _text = $(_currentGet).find('.compactName').text();/*用户姓名*/
			var _str = '<span rel="' + _rel + '">' + _text + '<a href="javascript:void(0)" class="deleteToken"></a></span>';
			$(that.opts.tokenArea).append(_str);
			/** 显示最多条数控制  add by: zhupinglei **/
			if(that.opts.showNum){
				var listNum = $(that.opts.tokenArea).find('span').index();
				if( listNum >= that.opts.showNum ){
					$(that.opts.inputWrap).find('input.toPeopleInput').hide();
				}else{
					$(that.opts.inputWrap).find('input.toPeopleInput').show();
				}
			}
			that.$e.val('');
			$(that.opts.compactArea).find('ul').empty();
			$(that.opts.compactArea).hide();
		}
	};
	
	
	$.fn.searcher = function(options){
		var options = $.extend({},$.fn.searcher.defaults, options);
		return new CLASS_Searcher(this,options);
	};
	$.fn.searcher.defaults = {
		inputWrap:null, //放置input的container
		tokenArea:null, //已匹配信息container
		url:null, //实时匹配请求地址
		compactArea:null, //响应的数据
		compactAreaOffset:[0, 22], //匹配列表框离输入框偏移位置
		staticData:null,//是否是本地缓存数据需要json格式
		showNum:null //是否设置显示的最多条数
	};
})(jQuery);
