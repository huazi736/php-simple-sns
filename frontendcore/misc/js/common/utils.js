
/*
 * Created on  2011-09-28 
 * @author: willian	
 * @desc: 公用js方法
 *
 * Update on 2012-03-07
 * @author: yewang
 * @desc: 整理全局方法，修改为面向对象写法
 */

var global = {
	init: function() {
		this.loadHead();
		this.bindEvents();
		this.globalDropDown();
	},
	//加载头部信息
	loadHead: function() {
		var headnav = $('#headNav');
		if(headnav[0]) {

			var topUrl = mk_url('main/topapi/showheader'),
				web_id = CONFIG['web_id'];
			if(web_id !== '0')topUrl = mk_url('main/topapi/showheader',{'web_id':web_id});
			$.djax({
				url: topUrl,
				type: 'GET',
				data: {url: window.location.href},
				cache: false,
				dataType: 'jsonp',
				jsonp:'callback',
				success: function(data) {
					if(data.status === 1) {
						var data = data.data;
						//判断添加信息条数
						var spans = $('#jewelContainer').find('span.jewelCount');
						for(var i=0, len=spans.length; i<len; i++) {
							if(data.num[i] > 0) {
								spans.eq(i).parent().addClass('hasNew').end().children().text(data.num[i]);
							}
						}

						var width = 800 - (headnav.next().width()),
							totalW = 0,
							current = 0,
							curIndex,
							appData = '',
							dataApp = data.app,
							len = dataApp.length;

						//插入所添加过的应用及网页
						for(var i = 0; i < len; i++) {
							if(dataApp[i].current) {
								current = i;
								curIndex = i;
							}
							var navTxt = dataApp[i].txt;
							// if(navTxt.length > 5) {
							// 	navTxt = navTxt.substring(0, 4) + '..';
							// }
							
							appData += '<li><a href="'+dataApp[i].url+'" class="pageLink" title="'+dataApp[i].txt+'">'+navTxt+'</a></li>';
						}

						
						headnav.width(width).children().append(appData);
						var lis = headnav.find('li'),
							l = lis.length;
						lis.eq(current).children('a').addClass('current');
						var curLi = lis.eq(current);
						for (var i = 0; i < l; i++) {
							var liWidth = $(lis[i]).width() + 3;
							totalW += liWidth;
							if(totalW > width) {
								if(i === (l-1) || (width + liWidth - totalW) < 20) {
									i--;
								}
								var d = c = i;
								//判断当前项是否已显示在导航条
								if(current >= i) {
									curLi.prependTo(curLi.parent());
									var navW = 0,
										newW = width - 30,
										newlis = headnav.find('li');
									for(var k = 0; k < len; k++) {
										navW += $(newlis[k]).width() + 3;
										if(navW > newW) {
											d = c = k;
											if(current = i)c = k-1;
											curLi.insertAfter(newlis.eq(k-1));
											break;
										}
									}
									
								}
								
								//插入下拉项
								var dropdownPage = '<li class="dropMenu morePage"><a href="javascript:void(0)" class="triggerBtn"></a><div class="dropList"><div class="pageList">';
								for(var j = c; j < len-1; j++) {
									var imgSrc = (dataApp[j].img === '') ? (miscpath + 'img/default/avatar_s.gif') : dataApp[j].img,
										itemClass = (dataApp[j].current) ? 'pageItem hovering' : 'pageItem';
									dropdownPage += '<div class="'+itemClass+'"><a href="'+dataApp[j].url+'" title="'+dataApp[j].txt+'"><img src="'+imgSrc+'" /><span>'+dataApp[j].txt+'</span></a></div>';
								}
								dropdownPage += '</div><div class="addPage"><a href="'+dataApp[len-1].url+'">+ 创建一个新的网页</a></div></li>';
								$(dropdownPage).insertAfter(headnav.find('li').eq(d-1)).nextAll().remove();
								//下拉项大于5个加滚动class
								if((len - i) > 6) {
									headnav.find('div.dropList').addClass('itemScroll');
								}
								break;
							}
						}

						headnav.removeClass('unvisible');
						
					} else {
						// alert(data.msg);
					}
				}
			});
		}
	},
	//各种事件绑定
	bindEvents: function() {
		/**
		 * Created on 2011-11-09
		 * @author: willian
		 * @desc: 请在fieldWithText的input中加入ref属性属性，值为提示信息如：
		 * @eg:<input type="text" class="fieldWithText" value="请输入密码" ref="请输入密码" />
		 */
		$(".fieldWithText").css('color','#999').blur(function(){
			if($.trim($(this).val()) == ''){
				$(this).val($(this).attr("ref"));
				$(this).css('color','#999');
			}else{
				$(this).css('color','#333');
			}
		}).focus(function(){
			if($.trim($(this).val()) == $(this).attr("ref")){
				$(this).val('');
				$(this).css('color','#333');
			}
		});
		
		/**
		 * Created on 2012-05-15
		 * @author: yewang
		 * @desc: 头部搜索隐藏显示默认关键词 "搜索"
		 */
		var searchVal = $('#initval'),
			searchInput = $('#globalSearch');
		searchVal.click(function() {
			searchInput.focus();
		});
		searchInput.blur(function(){
			if($.trim($(this).val()) === '') {
				searchVal.removeClass('hide');
			}
		}).focus(function(){
			searchVal.addClass('hide');
		});

		/**
		 * Created on 2012-03-05
		 * @author: zhangbo
		 * @desc: 头部数字新 请求 站内信 通知 定时器
		 */
		function soketGetHeaderInfo(){
			$.djax({
				type:"GET",
				url:mk_url('main/msg/show_unreadinfo'),
				cache: false,
				dataType: 'jsonp',
				jsonp:'callback',
				success: function(data){
					if(data && data.status === 1){
						var dataRequests = Number(data.data.requests);
						var dataMessages = Number(data.data.messages);
						var dataNotice = Number(data.data.notice);
					
						if (dataRequests > 0) {
							$('#requestsCountValue').parent().parent().addClass('hasNew');
							$('#requestsCountValue').html(dataRequests);
						}
						if(dataRequests == 0){
							$('#requestsCountValue').parent().parent().removeClass('hasNew');
						}
						if(dataMessages > 0){
							$('#messagesCountValue').parent().parent().addClass('hasNew');
							$('#messagesCountValue').html(dataMessages);
							
						}
						if(dataMessages == 0){
							$('#messagesCountValue').parent().parent().removeClass('hasNew');
						}
						if(dataNotice > 0){;
						$('#notificationsCountValue').parent().parent().addClass('hasNew');
						$('#notificationsCountValue').html(dataNotice);
						}
						if(dataNotice == 0){
							$('#notificationsCountValue').parent().parent().removeClass('hasNew');
						}
					}
				}
			});
		}
		//10秒调用一次服务器获取头部实时信息提醒 （暂时注释）
		if($('#jewelContainer')[0])setInterval(soketGetHeaderInfo,60000);
	},
	/**
	 * Created on 2012-03-07
	 * @author: yewang
	 * @desc: 全站下拉菜单点击消失 通过dropDown class控制
	 */
	globalDropDown: function() {
		$('body').click(function(e) {
			if($('.dropDown').length > 0) {
				var target = $(e.target);
				if(target.parents('.dropDown').length < 1 && !target.hasClass('dropDown')) {
					$('.dropDown').removeClass('dropDown').css("z-index","0");
				}
			}
		});
		
		$('body').delegate('.triggerBtn', 'click', function() {
			var parent = $(this).parent();
			$(parent).css("z-index","1");
			if($('.dropDown').length > 0) {
				if(parent.hasClass('dropDown')) {
					parent.removeClass('dropDown').css("z-index","0");
				} else {
					$('.dropDown').removeClass('dropDown').css("z-index","0");
					parent.addClass('dropDown');
				}
			} else {
				parent.addClass('dropDown');
			}
			
		});
	},
	//关闭下拉菜单方法调用  global.closeDropDown();
	closeDropDown: function() {
		$('.dropDown').removeClass('dropDown');
	}
	
};
/*用户反馈 弹框 start write by 杨光远*/
var feedback_function={
	init: function() {
		this.feedback_show();
		this.feedback();
	},
	subPopUps:function(arg)
            	{
            		arg[0].subPopUp({
            			width:arg[4],
            			title:arg[2],
            			content:arg[1],
            			buttons:'<span class="popBtns closeBtn">关闭</span>',
            			mask:true,
            			maskMode:false,
            			callback:arg[3]

            		})
            	},
            	feedback_ajax:function(arg)
            	{
            		var _self=this;
            		
            		$.djax({
            			type:"post",
                            	url:mk_url(arg[0]),
                            	data:{messageType:$("#feedback_type").val(),pagePath:arg[1],content:$("#feedback_con").val()},
			dataType:"json",
			
			success:function(rs){
				$.closePopUp();
				if(rs.status=="1"){
					_self.subPopUps([$("#feed_submit"),"您的问题已经提交成功。谢谢您对我们的支持！","提示",function(){},300]);

				}else{
					_self.subPopUps([$("#feed_submit"),"系统错误，请稍后再试！","提示",function(){},300]);
										
				}
					
			}
		});	
            	},
	feedback:function(){
		var _self=this;
		if($("input").is("#feed_submit")){
			

			$("#feed_submit").on("click",function(){
				var feedback_url=$("#feedback_url").val();
				var feedback_con=$("#feedback_con").val();
				if(feedback_url=="")
				{
				
				_self.subPopUps([$("#feed_submit"),"请输入您所需要提交内容的网址","提示",function(){},300]);
				return;
				}
				if(feedback_con=="")
				{
				_self.subPopUps([$("#feed_submit"),"请输入您所需要提交问题内容","提示",function(){},300]);
				return;
				}
			

				if($("#is_in").val()=="1")
				{
					_self.feedback_ajax(["feedback/main/add",$("#feedback_url").val()]);
				}else{
					_self.feedback_ajax(["feedback/fout/add",$("#feedback_url").val()]);
				}
				
			})
			

		}



    		
	 },
	  feedback_show:function(){
	 	// var _self=this;
	 	// $("body").append("<div id='user_feedback_div'><a class='user_feedback_click'>提出你宝贵的意见</a>"+
	 	// 				"<ul class='feedback_posts'>"+
   //                  					"<li><span>意见类型：</span>"+
   //                  					"<select id=\"feedback_type\">"+
   //                  					"<option vlaue=\"0\">个人账户</option>"+
   //                  					"<option vlaue=\"1\">主页功能</option>"+
   //                  					"<option vlaue=\"2\">网页功能</option>"+
   //                  					"<option vlaue=\"3\">搜索问题</option>"+
   //                  					"<option vlaue=\"4\">应用问题</option>"+
   //                  					"<option vlaue=\"5\">支付问题</option>"+
   //                  					"<option vlaue=\"6\">广告系统</option>"+
   //                  					"<option vlaue=\"7\">开放平台</option>"+
   //                  					"<option vlaue=\"8\">手机客户端</option>"+
   //                  					"</select></li>"+
                    					
   //                  					"<li><span>意见描述：</span><textarea type=\"text\" id=\"feedback_con\"></textarea></li>"+
   //            					"<li><label for=\"submit\" class=\"submit\"><input type=\"button\" value=\"提交\" id=\"feedback_submit\"></label></li>"+
   //            					"</ul></div>");
	 	
	 	// 	$("#user_feedback_div").find(".user_feedback_click").click(function(){
	 	// 		var pare=$("#user_feedback_div")
	 	// 		if(pare.css("left")=="0px")
   //          			{
   //          				pare.animate({
   //          					left:'-312'
   //          				})
            				
   //          			}else{
   //          				pare.animate({
   //          					left:'0'
   //          				})
            				
   //          			}	
                			
	 	// 	});
	 	// 	$("#feedback_submit").on("click",function(){

	 	// 		_self.feedback_ajax(["feedback/fout/add",window.location.href]);	
	 	// 	})
	 		
	        		
    		
    
    	
	 }
	
}
/*用户反馈 弹框 end*/
/*搜索表单为空不提交 start write by 杨光远*/
var search_isnull={
	init:function(){
		
		$("#navSearch").find("button[type=submit]").on("click",function(){
			var globalSearch_val=$("#globalSearch").val();
			if(globalSearch_val.trim()=="")
			{
				return false;
			}
		})
		
	}

}
/*搜索表单为空不提交 end*/

// /**/
// var c_event={
// 	init:function(){
// 		$(document).click(function(e){
// 			console.log(e.target);
// 		})
// 	}
// }
// /**/

$(document).ready(function() {
	global.init();
	feedback_function.init();

	search_isnull.init();
	//c_event.init();
	 
	/**
	 * Update on 2011-12-29
	 * @author： yewang
	 * @desc：判断添加全站头部搜索方法
	*/
	 
	if($("#globalSearch")[0]) {
		var searchKey = $("#globalSearch").val();
		$('#globalSearch').autocomplete({
			source: mk_url('main/search/main'),
			// source: "http://192.168.12.116/new_duankou/tmp/search-yew.php",
			sendType: 'field'
		});
	};
	
});

/**
 * Created on 2012-03-14
 * @author: yewang
 * @desc: 全局下拉菜单 权限设置
 * @example:
 **/

function DROPDOWN(opts, elem) {
	this.opts = $.extend({}, this.config, opts);
	this.opts.elem = $(elem);
	this.init();
}

DROPDOWN.prototype = {
	config: {
		btn: '',
		list: '',
		position: 'left',
		dataType: 'json',
		templete: false,
		ajax: true,
		ajaxUrl: ''
	},
	init: function() {
		this._interface();
	},
	_interface: function() {
		var op = this.opts,
			parent = op.elem,
			btn = $('<div class="triggerBtn"></div>'),
			list = $('<div class="dropList"><div>');
		if(op.permission) {
			var select = parent[0].getAttribute('s'),
				range = parent[0].getAttribute('r'),
				lists = '',
				liArr = {
					'8': '<li><a href="javascript:void(0);" rel="8" class="itemAnchor"><i></i><u class="s"></u><span>仅限自己</span></a></li>',
					'4': '<li><a href="javascript:void(0);" rel="4" class="itemAnchor"><i></i><u class="fr"></u><span>好友</span></a></li>',
					'1': '<li><a href="javascript:void(0);" rel="1" class="itemAnchor"><i></i><u class="o"></u></i><span>公开</span></a></li>',
					'-1': '<li><a href="javascript:void(0);" rel="-1" class="itemAnchor"><i></i><u class="c"></u><span>自定义</span></a></li>'
				};
			if(!range) {
				range = '8,4,1,-1';
			}
			range = range.split(',');
			for(var i = 0, l = range.length; i < l; i++) {
				lists += liArr[range[i]];
			}
			list.html('<ul class="dropListul checkedUl">'+lists+'</ul>').find('a[rel='+select+']').parent().addClass('current');
			
			var btnHtml = list.find('li.current').find('i').siblings().clone();
			btn.html(btnHtml).append('<s></s>');
			
		} else {
			btn.html(op.btn).append('<s></s>');
			list.html(op.list);
		}
		parent.addClass('dropMenu').append(btn).append(list);
		
		var dropList = parent.find('.dropList'),
			h = dropList.prev().height(),
			hor = op.position;
		if(op.top) {
			h = op.top;
		}
		dropList[0].style.top = h+'px';
		dropList[0].style[hor] = 0;
		
		if(range && range.length < 2) {
			list.remove();
			return;
		}

		this.bindEvent();
	},
	bindEvent: function() {
		var op = this.opts,
			elem = op.elem,
			self = this.callback,
			dataType = op.dataType,
			type = (dataType === 'jsonp')?'GET':'POST';
		
		//点击选中function
		function selected($this, par, _p) {
			var btn = par.find('.triggerBtn'),
				li = $this.parent();
			$this.on('click', function() {
				if(_p) {
					var p = op.permission,
						permission = $this[0].getAttribute('rel');
					var text = $(this).find("span").html();

					if(li.hasClass('current') && permission !== '-1') {
						btn.parent().removeClass('dropDown');
						return;
					}
					
					var _ids = par.attr('uid');
					if(p.im === true) {
						var data = {
							type: p.type,
							object_id: par[0].getAttribute('oid'),
							permission: permission
						};
						if(permission !== '-1') {
							self.im_callback(data, '-1', permission, li, btn, par, p.url);
						} else {
							// par.attr('s', permission);
							btn.parent().removeClass('dropDown');
							var friend_list_obj = {
								title: "好友列表",
								elm: $this,
								ids: (_ids.length > 3)? _ids : '',
								hidden:"false",
								type: type,
								dataType: dataType,
								callback: function(_data) {
									if(_data.ids.length > 0) {
										self.frient_list_callback(par, _data.ids , _data.names);
										data.permission = _data.ids;
										self.im_callback(data, data.permission, permission, li, btn ,par, p.url);
										self.closedrop(li, btn);
									}
								}
							};
							if(op.friend_url) {
								$.extend(friend_list_obj,op.friend_url);
							}
							new CLASS_FRIENDS_LIST(friend_list_obj);
							
						}
						
					} else {
						if(permission !== '-1') {
							par.attr({'s':permission, 'tip':text, 'uid': '-1'}).find('input').val(permission);
							self.closedrop(li, btn);
						} else {
							if(_ids.length > 3) {
								permission = _ids;
							}
							btn.parent().removeClass('dropDown');
							var friend_list_obj = {
								title: "好友列表",
								elm: $this,
								ids: (_ids.length > 3)? _ids : '',
								hidden:"false",
								type: type,
								dataType: dataType,
								callback: function(_data) {
									if(_data.ids.length > 0) {
										self.frient_list_callback(par, _data.ids , _data.names);
										self.closedrop(li, btn);
									}
								}
							};
							if(op.friend_url) {
								$.extend(friend_list_obj,op.friend_url);
							}
							new CLASS_FRIENDS_LIST(friend_list_obj);
						}
					}
				} else if(!li.hasClass('current')) {
					self.closedrop(li, btn);
				} else {
					btn.parent().removeClass('dropDown');
				}
			});
		}
		
		if(op.templete === true) {
			elem.find('a.itemAnchor').each(function() {
				var $this = $(this);
				selected($this, elem);
			});
		}
		
		if(op.permission) {
			dataType = op.permission.dataType,
			type = (dataType === 'jsonp')?'GET':'POST';
			elem.find('a.itemAnchor').each(function() {
				var $this = $(this);
				selected($this, elem, 'permission');
			});
		}
		
		if(op.callback) {
			elem.find('.itemAnchor').on('click', function() {
				var $this = $(this);
				op.callback($this);
				global.closeDropDown();
			});
		}
	},
	callback: {
		closedrop: function(li, btn) {
			li.addClass('current').siblings().removeClass('current');
			btn.find('span').html(li.find('span').html());
			if(btn.find('u')[0]) {
				btn.find('u')[0].className = li.find('u')[0].className;
			}
			btn.parent().removeClass('dropDown');
		},
		frient_list_callback: function(par, ids ,names) {
			var value = '-1';
			if(ids.length > 0) {
				value = ids;
			}
			par.attr('uid', value).attr("tip",names).find('input').val(value);
		},
		im_callback: function(_data, uid, permission, li, btn ,par, url) {
			var self = this;
			$.djax({
				url: url,
				type: 'POST',
				data: _data,
				success: function(data) {
					if(data.status === 1) {
						par.attr({'s': permission, 'uid':uid});
						self.closedrop(li, btn);
					} else {
						alert('系统错误');
					}
				}
				
			});
		}
	}
}

$.fn.dropdown = function(opts) {
	var _opts = $.extend({}, opts);
	for (var i = 0, l = this.length; i < l; i++) {
		new DROPDOWN(_opts, this[i]);
	}
}

/**
 * Created on 2011-10-22
 * @author: willian
 * @desc 输入框字符数限制
 * 1、textArea中需要加入maxlength属性来确定限制字数;
 * 2、_tip是你用来显示提示信息的容器，函数判断后会自动加上相应的样式 .nomalTipsState（正常）wrongTipsState（输入超过限制字数）;
 * 
 * @param _textArea 要限制的文字输入框,可传id,class,dom,jquery object
 * @param _tip (可选参数)限制字符提示框,可传id,class,dom,jquery object
 */
 /*
$.fn.limitStrNum = function(options){
	var opts = extend({},{
		colauto:false,
		num:500,
	},options);

	function CLASS_LIMIT(elm,opts){
		this.opts = opts;
		this.elm = $(elm);
		this.event();
		this.view();
	}
	CLASS_LIMIT.prototype.event = function(){

	}

	CLASS_LIMIT.prototype.view = function(){

	}

	return this.each(function(index) {
		new CLASS_LIMIT(this, opts,index);
	});

}
*/
function limitStrNum(_textArea,_tip){

	var $textArea  = $(_textArea); 
	if($textArea.size()==0){
		return false;
	}
	var $tip = $(_tip);
	var hasTip = $tip[0] ? true : false;
	var currentValue = $textArea.val();
	var limitNum = $textArea.attr('maxlength');
	currentLimitNum = limitNum - currentValue.length;

	$textArea.keydown(function(e){
		e = e || event;
		var keycode = e.keyCode || e.which;
		if(keycode!=34&&keycode!=8){
			if($(this).val().length>=limitNum){
				$(this).val($(this).val().substr(0,limitNum));
				$(this).focus();
				return false;
			}
		}
	})
	if(currentLimitNum >= 0){
		if(hasTip)
		{
			$tip.addClass('nomalTipsState').html('还可以输入'+ currentLimitNum +'个字');
		}
	}else{
		$textArea.val(currentValue.substr(0,limitNum));
		if(hasTip)
		{
			$tip.addClass('wrongTipsState').html('您的字数已经到在上限,无法再输入了');
		}
	}
}



/*
	* Created on 2011-12-15
	* @auther: linchangyuan
	* @name: jsonToString v1.0
	* @depends： jquery.js
	* @desc: $.jsonToString({name:"lucy"});  "{name:lucy}"
	* Update desc 
*/
$.jsonToString = function(o){
	var arr = [];
	var fmt = function(s){
		if(typeof s=="object" && s!=null){
			return $.jsonToString(s);
		}else{
			return s;
		}
	}
	for(var i in o)arr.push(""+i+":"+fmt(o[i]));
	return "{"+arr.join(",")+"}";
}


/*
	* Created on 2011-12-06
	* @auther: linchangyuan
	* @modify: wangweikun
	* @name: format v1.0
	* @depends： jquery.js
	* @desc: $.format("<div class='{0}'>{1}</dvi>",['a','b']);
	*		 $.format("<div class='{cls}'>{cnt}</dvi>",{cls:'a',cnt:'b'});
	
*/
$.format = function (source, params) {
    if (params.constructor != Array || params.constructor != Object) {
		$.each(params, function (i, n) {
			source = source.replace(new RegExp("\\{" + i + "\\}", "g"), n);
		});
		return source;
    }else{
		return source;
	}
};


$.fn.outerHtml = function(s) {
	$this = $(this);
	var h = $this.html();
	var s = $this.wrap("<div></div>").parent().html();
	$this.empty().html(h);
	return s;
}

/*
	* Created on 2011-12-06
	* @auther: linchangyuan
	* @name: djax v1.5
	* @desc: $.djax({
				el:el,	//触发事件target 对象
				obj:obj, //目标作用对象
				loading:true, //是否有loading效果
			 });
	* Update desc
			1.0 统一ajax 出口
			1.1 增加防止重复请求功能
			1.2 增加target对象和呈现对象loading效果
			1.3 loading 兼容两种定位情况
			1.4 解决同URL 不同参数无法重复请求的bug
			1.5 解决loading 效果在父节点relative,又有兄弟节点的时候范围不正确问题。
*/
var requestUrlArr=[];
$.djax = function(options) {

	var $loading,$img,$minLoading;
	var opts = $.extend({}, {
		type:"post",
		async:true,
		loadingSize:11,
		dataType:"json",
		cache:true,				// 默认缓存 不在重复加载
		data:null,
		global:false,
		aborted:true,
		ifModified:true,
		relative:false,
		beforeSend:function(){

			if(opts.loading) {
				if(opts.el){
					opts.el.addClass("min-loading");
				}
				if(opts.obj){
					var maxHeight,margin_top,margin_left;
					if(opts.obj.outerHeight()<opts.loadingSize){
						maxHeight = opts.obj.outerHeight();
						margin_top = 0;
						margin_left = (opts.obj.outerWidth())/2-maxHeight;
					}else{
						maxHeight = opts.loadingSize;
						margin_top = (opts.obj.outerHeight()-maxHeight)/2;
						margin_left = (opts.obj.outerWidth()-maxHeight)/2;
					}
					
					$img = $("<img style='max-height:"+opts.obj.outerHeight()+"px;margin-top:"+margin_top+"px;margin-left:"+margin_left+"px;' src='/frontendcore/misc/img/plug-img/djax/loading2.gif' />");
					$loading = $("<div class='djax_loading'><div class='loading_bg' style='height:"+opts.obj.outerHeight()+"px'></div></div>");

					if(!opts.relative){
						$loading.css({
							top:opts.obj.offset().top,
							left:opts.obj.offset().left,
							width:opts.obj.outerWidth(),
							height:opts.obj.outerHeight()
						}).prepend($img);
						opts.obj.after($loading);
					}else{
						var p = opts.obj.parent(),p_top,p_left;
						function searchParent(p){
							if(p){
								if(p.css("position")=="relative"||p.css("position")=="absolute"||p.css("position")=="fixed"){
									p_top = p.offset().top;
									p_left = p.offset().left;
								}else{
									return searchParent(p.parent())
								}
							}
						}
						
			
						searchParent(p);
						$loading.css({
							top:opts.obj.offset().top-p_top,
							left:opts.obj.offset().left-p_left,
							width:opts.obj.outerWidth(),
							height:opts.obj.outerHeight()
						}).prepend($img);
						opts.obj.after($loading);
						
					}
				}

				
			}
		},
		complete:function(data){
			if(opts.loading){
				if(opts.obj){
					$loading.remove();
				}
				
			}
			if(opts.aborted){
				requestUrlArr = $.grep(requestUrlArr,function(v){
					return v!=action;
				});
			}
			
		},
		timeout:3000,
		error:function(a,b,c){
			if(a.status=="0"){
				return false;
			}
			if(b=="abort"){
				return false;
			}
			var $div = $("<div class='errorLog'>服务器返回格式有误，action "+action+"  返回值:"+a.responseText+"</div>");
			$("body").append($div);
			
		}
		
	}, options);

	var action = opts.url+$.jsonToString(opts.data);
	if(opts.aborted){
		
		if($.inArray(action,requestUrlArr)!=-1){
			if(request.readyState=="1"){				
				request.abort();			//重复发送一个请求，执行abort 终止原请求。重新发送新请求。
			}else{
				return false;				//请求触发未完成，如果再次请求，将return false 不执行。 直到请求完成。
			}
			
		}
	}
	requestUrlArr.push(action);		// 保存当前执行URL 用于判断是否重复发送
	

	request = $.ajax({
		type:opts.type,
		url:opts.url,
		data:opts.data,
		dataType:opts.dataType,
		beforeSend: opts.beforeSend,
		success:function(data){
			if(data.status=="-1"){
				//登录超时
				if($('#relogin')[0])return;
				$('body').popUp({
					width:500,
					title:'请重新登录',
					content:'<div id="relogin"></div>',
					buttons:'<span class="popBtns closeBtn callbackBtn">取消登录</span>',
					mask:true,
					maskMode:false,
					callback:function(){
						window.location.href = mk_url('front/login/index');
					}
				});

				$('#relogin').load(mk_url('front/login/outlogin'));
				return false;
			}
			opts.success(data);
		},
		complete: opts.complete,
		cache:opts.cache,
		error:opts.error
	});
	return request;
}

/*
	* Created on 2011-12-09
	* @auther: linchangyuan
	* @name: msg v1.3
	* @depends： jquery.js
	* @desc: $.msg($("#wrap")); <input msg="请输入名字" />
	* Update desc 
			1.1 改写为插件模式 解决目标绝对定位问题。
			1.2 解决目标对象获取不到width 导致宽度异常 IE7
			1.3 增加msg继承对象的文本样式
	
*/
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
			if(this.$e.next("span.input_msg").size()==0){
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
            top  = (top == 0 ? 2 : top);
            this.$span.css({
				left: parseInt(this.$e.css("padding-left")) + this.opts.border*1 + p_left + e_left,
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
		}
	};
	$.fn.msg = (function(options){
		var opts = $.extend({}, $.fn.msg.defaults, options);
		return this.each(function(index) {
			new CLASS_MSG(this, opts,index);
		});
	});
	$.fn.msg.defaults = {
		absolute:false,
		border:1,
		textSize:12
	};
		
/*
	* Created on 2012-5-15
	* @auther: 田想兵
	* @name: canNotBeEmpty不能为空
	* @depends： jquery.js
	* @desc: $("#text").canNotBeEmpty($(".btn"));
	* Update desc 
			1.1 改写为插件模式 解决目标绝对定位问题。
			1.2 解决目标对象获取不到width 导致宽度异常 IE7
			1.3 增加msg继承对象的文本样式
*/
;(function($){						
	function checkTextLength(t,b){
		if ($.trim(t.val()).length>0){
			b.removeClass('disable');
		}else{
			b.addClass('disable');
		}
	}
	$.fn.extend({
		canNotBeEmpty:function(ops){
			var	button = typeof ops ==="string"?$(ops):ops;				
			var mytime ; 
			var _self=$(this);
			checkTextLength(_self,button);
			_self.blur().focus(function(){
				mytime=setInterval(function(){checkTextLength(_self,button)},100);
			}).blur(function(){
				clearInterval(mytime);
			});
			_self.change(function(){			
				checkTextLength(_self,button);
			});
		}
	});
})(jQuery);

// 处理 < 
function replaceBrackets(temp){
	return html = temp.replace(/</gi,"&lt;")
}

