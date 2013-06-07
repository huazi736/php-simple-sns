/*
 * Created on 2012-04-16
 * @author: Yewang
 * @desc: 端口网 (关注\互相关注\好友等关系操作)
 * @depand on: util.js, popUp.js
 * 按钮修改，默认选项  by卜海亮  2012-07-03
 *		增加 useInList， quickDelFriend 两个默认选项
*/

 (function($){
	function RELATION(options, _this) {
		this.wrap = $(_this);
		this.view(options);
	}
	RELATION.prototype = {
		options: {
			useInList: true,	//表示该js在列表上使用
			quickDelFriend: false,	//快速删除好友
			addUrl: 'main/api/addFollow',			//加关注
			cancalUrl: 'main/api/unFollow',			//取消关注
			friendUrl: 'main/api/addFriend',		//加好友
			delUrl: 'main/api/delFriend',			//删除好友
			addBtn: '<span class="btnBlue hide"><i></i><a class="addFollow" name="#" href="javascript:void(0);">加关注</a></span>'
			//callback:function(){}
		},
		view: function(options) {
			var _this = this,
				opts = _this.options = $.extend({}, _this.options, options),
				wrap = this.wrap,
				relation = wrap.attr('rel'),
				uid = wrap.attr('uid'),
				html = opts.addBtn.replace('#', uid),
				drop = '<div class="dropWrap dropMenu hide">\
						<div class="triggerBtn"><i class="friend"></i><span>关注</span><s></s></div>\
						<div class="dropList"><ul class="dropListul checkedUl">',
				li1 = '<li><a class="itemAnchor unFollow" name="'+ uid +'" href="javascript:void(0);"><span>取消关注</span></a></li>';
				//li2 = '<li><a class="itemAnchor hide addFriend" name="'+ uid +'" href="javascript:void(0);"><span>加为好友</span></a></li>';

			if('0' == relation){
				return false;
			}

			if(relation === '2' || relation === '3') {	//未关注的对象显示加关注按钮
				html = html.replace('hide','');
			} else {
				drop = drop.replace('dropMenu hide', ' dropMenu');
				var text = '互相关注';
				switch(relation) {
					case '10':
						text = '好友';
						li1 = li1.replace('unFollow', 'delFriend');
						li1 = li1.replace('取消关注', '删除好友');
						break;
					case '6':
					case '7'://被请求
						if(false == opts.useInList){
							//li2 = li2.replace('hide ', '');
						}
						break;
					case '4':
						text = '关注';
						break;
					case '8'://好友请求已发送
						if(true == opts.useInList){
							text = '好友请求已发送';
						} else {
							//li2 = li2.replace('加为好友', '好友请求已发送');
							//li2 = li2.replace('hide', 'sended');
						}
						break;
				}
				drop = drop.replace('关注', text);
			}
			//html += drop + li1 + li2 + '</ul></div></div>';
			html += drop + li1 + '</ul></div></div>';
			wrap.append(html);

			this.bindEvent(wrap);
		},
		bindEvent: function(wrap) {
			var self = this,
				op = this.options,
				addUrl = op.addUrl,
				cancalUrl = op.cancalUrl,
				friendUrl = op.friendUrl,
				delUrl = op.delUrl;
			wrap.find('a.addFollow').parent().click(function() {
				var $this = $(this).children('a'),
					uid = $this.attr('name');
				self.ajaxFun($this, addUrl, function(relation) {
					var text = '关注',
						classes = 'hide addFriend';
					if(relation === 6) {
						text = '互相关注';
						classes = 'addFriend';
						//进入别人首页关系操作
						if(op.index) {
							$this.parent().parent().next().removeClass('hide');
						}
					}
					$this.parent().addClass('hide').next().removeClass('dropDown').find('div.triggerBtn').children('span').text(text)
					.end().next().remove(); //兼容ie6 7 remove后重新插入
					if(true == op.useInList){
						$this.parent().next().append('<div class="dropList"><ul class="dropListul checkedUl">\
							<li><a class="itemAnchor unFollow" name="'+ uid +'" href="javascript:void(0);"><span>取消关注</span></a></li>\
							</ul></div>').removeClass('hide');
					} else {
						$this.parent().next().append('<div class="dropList"><ul class="dropListul checkedUl">\
							<li><a class="itemAnchor unFollow" name="'+ uid +'" href="javascript:void(0);"><span>取消关注</span></a></li>\
							<li><a class="itemAnchor '+classes+'" name="'+ uid +'" href="javascript:void(0);"><span>加为好友</span></a></li>\
							</ul></div>').removeClass('hide');
					}
					lisFun(wrap);
				});

				global.closeDropDown();

				return false;

			});

			//ie6 下拉宽度设置
			if(op.ie6) {
				wrap.find('div.triggerBtn').click(function() {
					var width = $(this).parent().width();
					$(this).next().width(width);
				});
			}

			lisFun(wrap);

			function lisFun(_wrap) {
				var dropWrap = _wrap.find('div.dropWrap');
				_wrap.find('a.itemAnchor').eq(0).click(function() {
					var $this = $(this),
						url = cancalUrl;
					if($this.hasClass('delFriend')) {
						url = delUrl;
					}

					if(false == op.quickDelFriend && $this.hasClass('delFriend')){
						//删除好友，并且不是快速删除，需要弹框提示
						var u_name = $("#name").text();
						if(!u_name){
							u_name = $this.parents('li.searchLi').find('p.searchCont').find('a').text();
						}
						if(!u_name){
							u_name = _wrap.attr('uname');
						}
						var $timeContent = $('<div class="delFriendDiv"><ul><li>您确定与<span class="strong">' + u_name + '</span>解除好友关系？</li><li><label><input type="checkbox" class="unFollowCheck" name="unFollowCheck" id="unFollowCheck" value="1" /><span>&nbsp;同时取消对TA的关注</span></label></li></ul></div>'),
							$unFollowCheck = $("#unFollowCheck", $timeContent);
						$this.subPopUp({
							width:300,
							title:'删除好友',
							content: $timeContent,
							buttons:'<span class="popBtns blueBtn callbackBtn">确定</span><span class="popBtns closeBtn">取消</span>',
							mask:true,
							maskMode:false,
							callback: function(){
								var checked = $unFollowCheck.attr('checked'),
									postData = {unFollow: 0};
								if('checked' == checked){
									postData.unFollow = 1;
								}
								self.ajaxFun($this, url, function(relation) {
									reRelation(dropWrap, relation);
									dropWrap.css("z-index","0");
									$.closeSubPop();
								}, postData);
							}
						});
						$('body').click();
					} else {

						self.ajaxFun($this, url, function(relation) {
							reRelation(dropWrap, relation);
							dropWrap.css("z-index","0");
						});
					}

					return false;
				});

				_wrap.find('a.itemAnchor').eq(1).click(function() {
					if($(this).hasClass('sended'))return false;
					var $this = $(this);

					self.ajaxFun($this, friendUrl, function(relation) {
						reRelation(dropWrap, relation);
						dropWrap.css("z-index","0");
					});

					return false;
				});
			}

			//重置关系
			function reRelation(_dropWrap, _relation) {
				//进入别人首页关系操作
				if(op.index) {
					var msg = _dropWrap.parent().next();
					msg.addClass('hide');
					if(_relation === 10 || _relation === 6 || _relation === 8) {
						msg.removeClass('hide');
					}
				}
				if(_relation === 2 || _relation === 3) {
					_dropWrap.addClass('hide').prev().removeClass('hide');
				} else {
					var text = '互相关注',
						deltext = '取消关注',
						classes = 'unFollow',
						a0 = _dropWrap.find('a.itemAnchor').eq(0);
						//a1 = _dropWrap.find('a.itemAnchor').eq(1);
					switch (_relation) {
						case 10:
							deltext = '删除好友';
							text = '好友';
							classes = 'delFriend';
							//a1.addClass('hide');
							break;
						case 6:
						case 7:
							if(false == op.useInList){
								//a1.removeClass('hide');
							}
							break;
						case 4:
							text = '关注';
							//a1.addClass('hide');
							break;
						default:
							text = '好友请求已发送';
							//a1.addClass('hide');
					}
					_dropWrap.find('div.triggerBtn').children('span').text(text);
					a0.removeAttr('class').addClass('itemAnchor ' + classes).html('<span>' + deltext + '</span>');
				}
				_dropWrap.removeClass('dropDown');
			}

		},
		ajaxFun: function($this, url, callback, data) {
			if($this.hasClass('getting'))return false;
			$this.addClass('getting');

			data = $.extend({}, {f_uid: $this.attr('name')}, data);
			url = mk_url(url, data);

			$.djax({
				url: url,
				type: 'GET',
				dataType: 'jsonp',
				jsonp: "callback",
				success: function(data) {
					$this.removeClass('getting');
					if(data.status !== 1) {
						$this.subPopUp({
							width:400,
							title:'温馨提示',
							content:'<div class="delFriendDiv">' + data.info + '</div>',
							buttons:'<span class="popBtns closeBtn callbackBtn">关闭</span>',
							mask:false,
							maskMode:true,
							callback:function(){
								//window.location.reload();
								return false;
							}
						});
						if(true == $this.hasClass('addFollow')){
							return false;
						}
					}
					data = data.data;
					var relation = data.relation;
						callback(relation);
				}
			});
		}
	};

	$.fn.relation = function(opts) {
		var op = {};
		if($.browser.msie &&($.browser.version =='6.0')) {
			op.ie6 = true;
		}
		var _opts = $.extend(op, opts);
		for (var i = 0, l = this.length; i < l; i++) {
			new RELATION(_opts, this[i]);
		}
	};
	
})(jQuery);