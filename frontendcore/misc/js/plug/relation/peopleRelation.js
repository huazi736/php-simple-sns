/*
 * Created on 2012-07-04
 * @author: 卜海亮
 * @desc: 端口网个人首页 (关注\互相关注\好友等关系操作)
 * @depand on: util.js, popUp.js
*/
(function($, undefined){

	function PEOPLERELATION(options, _this){
		this.elem = $(_this);
		this.init(options);
	}

	PEOPLERELATION.prototype = {
		//一些默认选项
		options: {
			quickDelFriend: false,	//快速删除好友
			addUrl: mk_url('main/api/addFollow'),			//加关注
			cancalUrl: mk_url('main/api/unFollow'),			//取消关注
			friendUrl: mk_url('main/api/addFriend'),		//加好友
			delUrl: mk_url('main/api/delFriend'),			//删除好友
			sendMessageUrl: mk_url('main/msg/index')		//发消息
		},

		//初始化函数
		init: function(options){
			var _this = this,
				opts = _this.options = $.extend({}, _this.options, options),
				elem = this.elem,
				relation = elem.attr('rel'),
				uid = elem.attr('uid'),
				html = '';
			//组合html代码
			html = _this.buildHtml(uid, relation);
			//嵌入新的html
			elem.append(html);
			//绑定事件
			_this.eventBind(elem);
		},

		//组合html语句
		buildHtml: function(uid, relation){
			var html = '',
				state_div = '',
				action_div = '',
				more_div = '',
				htmlTemp = {
					showAddBtn: false,
					addBtn: { isBeFollowed: false },									//加关注按钮
					relationState: { relationClass: 'friend', text: '关注' },			//关系状态
					hasActionBtu: false,
					actionBtn: { actionClass: 'cancelFollow', text: '取消关注', iconClass: false },
					moreAction: [														//下三角展开的操作
						{ actionClass: 'sendMessage', text: '发消息' },	//发送消息
						{ actionClass: 'unFollow', text: '取消关注' }	//取消关注
					]
				};

			relation = relation.toString();
			if('0' == relation){
				html = '';
				return html;
			}
			
			switch(relation){
				case '2':  //加关注按钮
					htmlTemp.showAddBtn = true;
					break;
				case '3': //被关注了，显示另一种加关注按钮
					htmlTemp.showAddBtn = true;
					htmlTemp.addBtn.isBeFollowed = true;
					break;
				case '4': //关注
					htmlTemp.moreAction = [{ actionClass: 'unFollow', text: '取消关注' }];
					break;
				case '6': //互相关注
					htmlTemp.relationState.relationClass = 'doubleFollow';
					htmlTemp.relationState.text = '互相关注';
					htmlTemp.actionBtn.actionClass = 'addFriend';
					htmlTemp.actionBtn.iconClass = 'plus';
					htmlTemp.actionBtn.text = '加为好友';
					htmlTemp.hasActionBtu = true;
					break;
				case '7': //被请求加为好友
					htmlTemp.relationState.relationClass = 'doubleFollow';
					htmlTemp.relationState.text = '互相关注';
					htmlTemp.actionBtn.actionClass = 'addFriend';
					htmlTemp.actionBtn.iconClass = 'plus';
					htmlTemp.actionBtn.text = '同意加为好友';
					htmlTemp.hasActionBtu = true;
					break;
				case '8': //好友请求已发送
					htmlTemp.relationState.text = '好友请求已发送';
					break;
				case '10': //好友
					htmlTemp.relationState.text = '好友';
					htmlTemp.moreAction = [{ actionClass: 'sendMessage', text: '发消息' }, { actionClass: 'delFriend', text: '删除好友' }];
					break;
			}

			if(true == htmlTemp.showAddBtn){
				//只显示加关注按钮
				html += '<div class="userName">';
				if(true == htmlTemp.addBtn.isBeFollowed){
					html += '<span class="followed"><i class="friend"></i></span>';
				}
				html += '<span class="btnBlue"><i class="plus"></i><a class="addFollow" name="' + uid + '" href="#">加关注</a></span>';
				html += '</div>';
			} else {
				//关系状态框
				state_div = '<div class="relation_state"><i class="' + htmlTemp.relationState.relationClass + '"></i><span>' + htmlTemp.relationState.text + '</span></div>';

				//更多操作按钮
				more_div = '<div class="triggerBtn"><s></s></div>\
						 <div class="dropList"><ul class="dropListul checkedUl">';
				for(var i in htmlTemp.moreAction){
					more_div += '<li><a class="itemAnchor '+ htmlTemp.moreAction[i].actionClass + '" name="'+ uid +'" href="#"><span>'+ htmlTemp.moreAction[i].text + '</span></a></li>';
				}
				more_div += '</ul></div>';

				if(true == htmlTemp.hasActionBtu){
					action_div = '<div class="actionBtn"><span class="btnBlue">';
					if(false !== htmlTemp.actionBtn.iconClass){
						action_div += '<i class="' + htmlTemp.actionBtn.iconClass + '"></i>';
					}
					action_div += '<a name="'+ uid +'" class="' + htmlTemp.actionBtn.actionClass + '" href="#">' + htmlTemp.actionBtn.text + '</a></span></div>';

					html = state_div + '<div class="userName theAction"><div class="dropWrap dropMenu">' + action_div + more_div + '</div></div>';
				} else {
					html = '<div class="userName"><div class="dropWrap dropMenu">' + state_div + more_div + '</div></div>';
				}
			}

			return html;
		},

		//绑定事件
		eventBind: function (elem){
			var self = this,
				op = this.options,
				uid, ajaxUrl;

			elem.find('.relation_state').click(function(){
				$('body').click();
			});

			//加关注
			elem.find('.addFollow').parents('.userName').click(function(){
				var $this = $(this).find('a');
				uid = $this.attr('name');
				ajaxUrl = op.addUrl;

				self.ajaxFun($this, ajaxUrl, function(relation) {
					if(1 != relation){
						self.resetRelation(relation, uid);
					}
				});
			});

			//加好友
			elem.find('.addFriend').parent().click(function(){
				$('body').click();
				var $this = $(this).children('a');
				uid = $this.attr('name');
				ajaxUrl = op.friendUrl;

				self.ajaxFun($this, ajaxUrl, function(relation) {
					self.resetRelation(relation, uid);
				});
			});

			//取消关注
			elem.find('.unFollow').click(function(){
				var $this = $(this);
				uid = $this.attr('name');
				ajaxUrl = op.cancalUrl;

				self.ajaxFun($this, ajaxUrl, function(relation) {
					self.resetRelation(relation, uid);
				});
			});

			//删除好友
			elem.find('.delFriend').click(function(){
				var $this = $(this);
				uid = $this.attr('name');
				ajaxUrl = op.delUrl;

				if(true == op.quickDelFriend){
					self.ajaxFun($this, ajaxUrl, function(relation) {
						self.resetRelation(relation, uid);
					});
				} else {
					var u_name = $("#name").text();
					var $timeContent = $('<div class="delFriendDiv"><ul><li>您确定与<span class="strong">' + u_name + '</span>解除好友关系？</li><li><label for="unFollowCheck"><input type="checkbox" class="unFollowCheck" name="unFollowCheck" id="unFollowCheck" value="1" /><span>&nbsp;同时取消对TA的关注</span></label></li></ul></div>'),
						$unFollowCheck = $("#unFollowCheck", $timeContent);
					$this.popUp({
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
							self.ajaxFun($this, ajaxUrl, function(relation) {
								self.resetRelation(relation, uid);
								$.closePopUp();
							}, postData);
						}
					});
					$('body').click();
				}
			});

			//发送消息
			elem.find('.sendMessage').click(function(){
				window.location.href = op.sendMessageUrl;
			});

			//ie6 下拉宽度设置
			if(op.ie6) {
				elem.find('div.triggerBtn').click(function() {
					var width = $(this).parent().width();
					$(this).next().width(width);
				});
			}
		},

		//重新绘制页面上显示的关系，以及操作
		resetRelation: function(relation, uid){
			var elem = this.elem
				html = '';

			html = this.buildHtml(uid, relation);
			//嵌入新的html
			elem.empty();
			elem.append(html);
			//绑定事件
			this.eventBind(elem);
		},

		//ajax请求函数
		ajaxFun: function($this, url, callback, data) {
			if($this.hasClass('getting'))return false;
			$this.addClass('getting');

			data = $.extend({}, {f_uid: $this.attr('name')}, data);

			$.djax({
				url: url,
				type: 'POST',
				dataType: 'json',
				data: data,
				success: function(data) {
					if(data.status !== 1) {
						//显示错误信息，弹出层
						$this.popUp({
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
					}
					data = data.data;
					var relation = data.relation;
						callback(relation);
					$this.removeClass('getting');
				}
			});
		}
	};

	//作为jQuery的函数
	$.fn.peopleRelation = function(opts) {
		var op = {};
		if($.browser.msie &&($.browser.version =='6.0')) {
			op.ie6 = true;
		}
		var _opts = $.extend(op, opts);
		for (var i = 0, l = this.length; i < l; i++) {
			new PEOPLERELATION(_opts, this[i]);
		}
	};

})(jQuery);