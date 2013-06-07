/*
 * Created on 2012-06-26
 * @author: Bohailiang
 * @desc: 网页 (关注\取消关注等操作)
 * @depand on: util.js, popUp.js, validator.js
*/

 (function($, undefined){
	function WEBRELATION(options, _this) {
		this.wrap = $(_this);
		this.view(options);
	}
	WEBRELATION.prototype = {
		options: {
			addUrl: 'webmain/api/addWebFollow',			//加关注
			cancalUrl: 'webmain/api/unWebFollow',			//取消关注
			setTimeUrl: 'webmain/api/updateWebFollowTime',			//设置时效
			defaultDays: 90,
			defaultDtype: 'd',
			dayType: {'d' : '天'},
			addBtn: '<span class="btnBlue hide"><i></i><a class="addFollow" name="#" href="javascript:void(0);">加关注</a></span>'
			//callback:function(){}
		},
		//显示按钮
		view: function(options) {
			var _this = this,
				opts = _this.options = $.extend({}, _this.options, options),
				wrap = this.wrap,
				relation = wrap.attr('rel'),
				uid = wrap.attr('uid'),
				webid = wrap.attr('webid');
				days = wrap.attr('days'),//关注时效
				type = wrap.attr('dtype'),//时效类型
				html = opts.addBtn.replace('#', uid +'_' + webid),
				drop = '<div class="dropWrap dropMenu hide">\
						<div class="triggerBtn"><i class="friend"></i><span>关注</span><s></s></div>\
						<div class="dropList"><ul class="dropListul checkedUl">',
				li1 = '<li><a class="itemAnchor setFollowTime" name="'+ uid +'_' + webid + '" href="javascript:void(0);"><span>设置关注时间</span></a></li>',
				li2 = '<li><a class="itemAnchor unFollow" name="'+ uid +'_' + webid + '" href="javascript:void(0);"><span>取消关注</span></a></li>';

			if(relation === '2') {	//未关注的对象显示加关注按钮
				html = html.replace('hide','');
			} else {
				drop = drop.replace('dropMenu hide', ' dropMenu');
				var text = '关注';
				switch(relation) {
					case '6'://永久关注
						text = '永久关注';
						break;
					case '4'://关注，且设置时效
						text = '关注(' + days + opts.dayType[type] + ')';
						break;
					case '8'://关注时效已过，需要激活
						text = '激活关注';
						break;
				}
				drop = drop.replace('关注', text);
			}
			html += drop + li1 + li2 + '</ul></div></div>';
			wrap.append(html);
			wrap.data('webRel', relation);

			this.bindEvent(wrap);
		},
		bindEvent: function(wrap) {
			var self = this,
				op = this.options,
				addUrl = op.addUrl,
				cancalUrl = op.cancalUrl,
				setTimeUrl = op.setTimeUrl;

			//加关注
			wrap.find('a.addFollow').parent().click(function() {
				var $this = $(this).children('a'),
					uidAndWebid = $this.attr('name');
				self.ajaxFun($this, addUrl, function(relation, days) {
					var text = '关注(' + days + ')',
						classes = 'hide addFriend',
						$triggerBtn = $this.parent().addClass('hide').next().removeClass('dropDown').find('div.triggerBtn');

					$triggerBtn.children('span').text(text)
					.end().next().remove(); //兼容ie6 7 remove后重新插入
					$this.parent().next().append('<div class="dropList"><ul class="dropListul checkedUl">\
						<li><a class="itemAnchor setFollowTime" name="'+ uidAndWebid +'" href="javascript:void(0);"><span>设置关注时间</span></a></li>\
						<li><a class="itemAnchor unFollow" name="'+ uidAndWebid +'" href="javascript:void(0);"><span>取消关注</span></a></li>\
						</ul></div>').removeClass('hide');
					lisFun(wrap);
					//设置关注时间
					showTimeSet($this);
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

				//设置关注时间
				_wrap.find('a.itemAnchor').eq(0).click(function() {
					$('body').click();
					showTimeSet($(this));

					return false;
				});

				//取消关注
				_wrap.find('a.itemAnchor').eq(1).click(function() {
					var $this = $(this),
						url = cancalUrl;

					self.ajaxFun($this, url, function(relation) {
						reRelation(dropWrap, relation);
						dropWrap.css("z-index","0");
					});

					return false;
				});
			}

			//显示 设置关注时间 的弹出层
			function showTimeSet(obj){
				var days = newDays = self.wrap.attr('days'),//关注时效
					type = newTyps = self.wrap.attr('dtype'),//时效类型
					relation = newRelation = self.wrap.attr('rel'),//关注状态
					confirmBtnTxt = '确定',
					dropWrap = wrap.find('div.dropWrap');

				var $timeContent = $('<div class="timeSetDiv"><ul><li><label><input checked="checked" class="setForever" type="radio" name="forever" value="0" />&nbsp;时间设置</label>&nbsp;<input type="text" size="3" name="days" id="days" class="input setTime" />天&nbsp;<span class="hide error">(请输入1-999的整数)</span></li><li><label><input class="setForever" type="radio" name="forever" id="forever" value="1" />&nbsp;永久关注</label></li></ul></div>'),
					$foreverRadio = $('#forever', $timeContent),
					$days = $('#days', $timeContent),
					$triggerBtnSpan = self.wrap.find('div.triggerBtn').find('span'),
					$errorSpan = $('.error', $timeContent);

				//设置默认时间
				$days.val(days);

				//初始化设置表单
				if(6 == relation){
					$foreverRadio.attr('checked', 'checked');
					$days.attr('disabled', 'disabled');
				} else if(8 == relation){
					confirmBtnTxt = '激活';
					newRelation = 4;
				}

				//绑定事件
				//1、两个radio的切换效果
				$("input:radio", $timeContent).click(function(){
					if('checked' == $foreverRadio.attr('checked')){
						$days.attr('disabled', 'disabled');
						//$triggerBtnSpan.text('永久关注');
						newRelation = 6;
					} else {
						$days.attr('disabled', false);
						setFocus($days);
						newRelation = 4;
					}
				});
				//2、改变时间时，即时改变
				$days.bind('change', function(){
					newDays = $days.val();
				});
				//3、改变时间，及时检测有效性
				// $days.bind('keydown', function(e){
				// 	newDays = $days.val();
				// 	console.log(newDays);
				// });
				// $days.bind('keyup', function(e){
				// 	var daysVal = $days.val();
				// 	if(!validator.number.test(daysVal) || 0 >= daysVal || 999 < daysVal){
				// 		$days.val(newDays);
				// 	} else {
				// 		newDays = daysVal;
				// 	}
				// });

				obj.popUp({
					width:300,
					title:'设置关注时间',
					content: $timeContent,
					buttons:'<span class="popBtns blueBtn callbackBtn">' + confirmBtnTxt + '</span><span class="popBtns closeBtn">取消</span>',
					mask:true,
					maskMode:false,
					callback: function(){
						var postData = {relation: null};
						$errorSpan.hide();
						if(4 == newRelation && (!validator.number.test(newDays) || 0 >= newDays || 999 < newDays) ){
							$errorSpan.show();
							setFocus($days);
							return false;
						}

						if(6 == newRelation){//永久关注
							postData = {relation: newRelation, days: -1, type: newTyps};
						} else if( 4 == newRelation && ( (days != newDays && 4 == relation) || 4 != relation ) ){
							postData = {relation: newRelation, days: newDays, type: newTyps};
						}

						if(null == postData.relation){
							$.closePopUp();
						} else {
							self.ajaxFun(obj, setTimeUrl, function(relation, days) {
								reRelation(dropWrap, relation, days);
								$.closePopUp();
							}, postData);
						}
					}
				});

				setFocus($days);
			}

			//重置关系
			function reRelation(_dropWrap, _relation, _days) {
				if(_relation === 2) {
					_dropWrap.addClass('hide').prev().removeClass('hide');
				} else {
					var text = '关注';

					switch (_relation) {
						case 6://永久关注
							text = '永久关注';
							break;
						case 4://关注，且设置时效
							if(undefined == _days){
								_days = defaultDays + '天';
							}
							text = '关注(' + _days + ')';
							break;
					}
					_dropWrap.find('div.triggerBtn').children('span').text(text);
				}
				_dropWrap.removeClass('dropDown');
			}

			function setFocus(obj){
				var elem = obj[0],
					caretPos = elem.value.length;

				if(elem.createTextRange){
					var range = elem.createTextRange();
					range.move('character', caretPos);
					range.select();
				} else {
					elem.setSelectionRange(caretPos, caretPos);
					elem.focus();
					// 模拟键盘添加空格
					var evt = document.createEvent('KeyboardEvent');
					evt.initKeyEvent('keypress', true, true, null, false, false, false, false, 0, 32);
					elem.dispatchEvent(evt);
					// 模拟键盘删除空格
					var evt = document.createEvent('KeyboardEvent');
					evt.initKeyEvent('keypress', true, true, null, false, false, false, false, 8, 0);
					elem.dispatchEvent(evt);
				}
			}
		},
		ajaxFun: function($this, url, callback, data) {
			var self_warp = this.wrap,
				self = this,
				uidAndWebid = $this.attr('name');
			uidAndWebid = uidAndWebid.split('_');
			data = $.extend({}, {f_uid: uidAndWebid[0], web_id: uidAndWebid[1]}, data);

			if($this.hasClass('getting'))return false;
			$this.addClass('getting');

			url = mk_url(url, data);
			$.djax({
				url: url,
				type: 'GET',
				dataType: 'jsonp',
				jsonp: "callback",
				success: function(data) {
					$this.removeClass('getting');
					if(data.status !== 1) {
						$this.popUp({
							width:400,
							title:'温馨提示',
							content:'<div class="timeSetDiv">' + data.info + '</div>',
							buttons:'<span class="popBtns closeBtn callbackBtn">关闭</span>',
							mask:false,
							maskMode:true,
							callback:function(){
								//window.location.reload();
								return false;
							}
						});
						return false;
					} else {
						data = data.data;
						var relation = data.relation,//关注状态，2 - 未关注，4 - 关注，设置时效， 6 - 永久关注
							days = data.days,
							type = data.type;

						//保存新的属性值到对象中，便于下次读取
						self_warp.attr('rel', relation);
						self_warp.attr('days', days);
						self_warp.attr('dtype', type);
						self_warp.data('webRel', relation);
						
						days = days + self.options.dayType[type];
						callback(relation, days);
					}
				}
			});
		}
	};

	$.fn.webRelation = function(opts) {
		var op = {}, webRel;
		if($.browser.msie &&($.browser.version =='6.0')) {
			op.ie6 = true;
		}
		var _opts = $.extend(op, opts);
		for (var i = 0, l = this.length; i < l; i++) {
			webRel = $(this[i]).data('webRel');
			if(undefined == webRel){
				new WEBRELATION(_opts, this[i]);
			}
		}
	};
	
})(jQuery);