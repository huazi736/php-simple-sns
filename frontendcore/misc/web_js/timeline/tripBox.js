/**
 *@author: huweiliang
 *@created: 2012/08/04
 *@desc:景点频道
 *@version: v1.0
 **/
var tripBox = {};
tripBox.validator = {
	require : /[^(^\s*)|(\s*$)]/,
	isfloat : /^\d+(\.\d+)?$/,
	url : /^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/
}, tripBox.view = function(name, arg) {
	this.view._class = {
		//表单交互与验证
		clearTxt : function(arg) {
			var delValue = '';
			arg[0].each(function() {
				$(this).css({
					'color' : '#8e8e8e'
				});
				var delValue = $(this).val();
				$(this).focus(function() {
					if($(this).hasClass('url')){
						this.value = 'http://';
					}else{
						if(this.value==delValue){
							this.value = '';
						}
					}
					$(this).css({
						'color' : '#333'
					});
				})
				$(this).blur(function() {
					if(this.value == '') {
						this.value = delValue;
						$(this).css({
							'color' : '#f00',
							'background' : '#fbefef'
						});
						$(this).closest('.form_border').css({
							'border' : '1px #f05b5b solid'
						});
						$(this).addClass('tripwarm');
					} else {
						if(this.value != delValue) {
							if($(this).hasClass('isfloat')) {
								if(!tripBox.validator.isfloat.test(this.value)) {
									this.value = "价格为有效整数或浮点数";
									$(this).css({
										'color' : '#f00',
										'background' : '#fbefef'
									});
									$(this).closest('.form_border').css({
										'border' : '1px #f05b5b solid'
									});
									$(this).addClass('tripwarm');
								} else {
									$(this).css({
										'color' : '#333',
										'background' : '#fff'
									});
									$(this).closest('.form_border').css({
										'border' : '1px #bbbbbb solid'
									});
									$(this).removeClass('tripwarm');
								}
							} else if($(this).hasClass('url')) {
								if(!tripBox.validator.url.test(this.value)) {
									this.value = "请输入正确的链接格式地址";
									$(this).css({
										'color' : '#f00',
										'background' : '#fbefef'
									});
									$(this).closest('.form_border').css({
										'border' : '1px #f05b5b solid'
									});
									$(this).addClass('tripwarm');
								} else {
									$(this).css({
										'color' : '#333',
										'background' : '#fff'
									});
									$(this).closest('.form_border').css({
										'border' : '1px #bbbbbb solid'
									});
									$(this).removeClass('tripwarm');
								}
							} else {
								$(this).css({
									'color' : '#333',
									'background' : '#fff'
								});
								$(this).closest('.form_border').css({
									'border' : '1px #bbbbbb solid'
								});
								$(this).removeClass('tripwarm');
							}
						} else {
							$(this).css({
								'color' : '#f00',
								'background' : '#fbefef'
							});
							$(this).closest('.form_border').css({
								'border' : '1px #f05b5b solid'
							});
							$(this).addClass('tripwarm');
						}
					}
					//高亮发布按钮
					var len = $('.tripwarm').length;
					if(len < 1) {
						$('#distributeButton').parent().removeClass('disable');
					} else {
						$('#distributeButton').parent().addClass('disable');
					}
				})
			})
		},
		//加载发布成功后的模板
		publicBox : function(arg) {
			var $content = $('.time').find("ul.content");
			var msgname = '';
			var sideClass, clickDown, tipTxthighlight;
			var webId = $('.web_id').val();
			var faceSrc = $('#topUserAvatar').attr('src');
			if(goods_offset == 0) {
				sideClass = "sideLeft";
				goods_offset = 1
			} else {
				sideClass = "sideRight";
				goods_offset = 0
			}
			var location = mk_url('main/index.php?c=index&m=index&web_id=', {
				web_id : webId
			});
			var timeData = [arg[0].ymd.year, arg[0].ymd.month, arg[0].ymd.day, arg[0].ymd.hour, arg[0].ymd.minute, arg[0].ymd.second];
			//【旅游】之景点渲染开始
			var str = '<li name="timeBox" scale="true" id="' + arg[0].tid + '"  fid="' + arg[0].fid + '" uid="' + arg[0].uid + '" type="' + arg[0].type + '" highlight="' + arg[0].highlight + '" time="' + arg[0].ctime + '" timeData ="' + timeData + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><!--<span class="conWrap midLine tip_up_left_black ' + clickDown + '" tip="' + tipTxthighlight + '"><a><i class="conResize"></i></a></span>--><span class="conWrap tip_up_right_black" tip="编辑或删除"><a><i class="conEdit"></i></a><ul class="editMenu hide"><li class="sepLine"></li><li name="delTopic"><i class="delTopic"></i>删除帖子...</li></ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + faceSrc + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[0].uname + '</a></div><div class="postTime"><a href="javascript:void(0)">' + arg[0].friendly_time + '</a>';
			var ctime = arg[0].ctime;
			var dateline = arg[0].dateline;
			var msgname = arg[0].title || "";
			if(arg[0].ctime != arg[0].dateline && arg[0].friendly_line) {
				str += '<i class="insertTime tip_up_left_black" tip="' + arg[0].friendly_line + '"></i>';
			}
			var travel_html = '';
			if(arg[1] == 'trip') {
				msgname = arg[0].travel.name || "";
				travel_html = tripBox.view('tripInfoStream', arg[0]);
			} else if(arg[1] == 'airlineticket') {
				travel_html = tripBox.view('ticketInfoStream', arg[0]);
			}
			str += '</div></div></div><div class="infoContent">' + travel_html + '</div><div class="commentBox pd" msgname="' + msgname + '" commentObjId="' + arg[0].tid + '" pageType="web_' + arg[0].type + '" ctime="' + arg[0].ctime + '" action_uid="' + arg[0].uid + '"></div></div></li>';
			$content.prepend(str);
			tripBox.plug('likeforword', [$('.time')]);
			$('html,body').animate({
				scrollTop : $('#distributeButton').offset().top
			}, 400);
		},
		//自动调整图片大小
		autoImg : function(data) {
			var picWidth = data.travel.pics[0].b.width;
			return parseInt(picWidth) < 379 ? picWidth : 379;
		},
		//超值行程信息流
		tripInfoStream : function(data) {
			var str = '<div class="tripbox_main">';
			str += '<ul>';
			str += '<li class="trip_pics">';
			str += '<img src="' + data.travel.pics[0].b.url + '" width="' + tripBox.view('autoImg', data) + '" alt="" />';
			str += '</li>';
			str += '<li>';
			str += '<span class="trip_price">￥' + data.travel.price + '</span>';
			str += '<span class="trip_description">' + data.travel.description + '</span>';
			str += '</li>';
			str += '<li class="tripbox_btn">';
			str += '<a href="' + data.travel.link + '"target="_blank">现在去看看</a>';
			str += '</li>';
			str += '</ul>';
			str += '</div>';
			return str;
		},
		//特价机票信息流
		ticketInfoStream : function(data) {
			var travelsigns = function() {
				if(data.airticket.travelsigns == 0) {
					return '单程'
				} else {
					return '往返'
				}
			}
			var str = '<div class="airlineticket_main">'
			str += '<ul>';
			str += '<li>特价机票：<a href="' + data.airticket.link + '"><span>' + data.airticket.andfromtime + '</span><span>' + data.airticket.gocity + '-' + data.airticket.returntrip + '</span><span>' + travelsigns() + '</span><span class="price">￥' + data.airticket.price + '</span><span>' + data.airticket.rate + '折</span></a></li>';
			str += '</ul>';
			str += '</div>';
			return str;
		},
		tripListShow:function(){
			var tripLl=$('.trip_ul li');
			var len=tripLl.length;
			tripLl.each(function(index){
				var a=index+1;
				if(a%3==0){
					$(this).css({'margin-right':'0px'});
				}
			})
		}
	}
	return this.view._class[name](arg);
}, tripBox.control = function(name, arg) {
	this.control._class = {
		//发布框底下时间参数获取
		getTimeDate : function() {
			timeData = {};
			if($("[name='sel_christian']").val() == "0") {
				timeData.bc = 1;
				timeData.timestr = $("#date_a").val();
			} else {
				timeData.bc = -1;
				if($.trim($("[name='yearNum']").val()).length == 0) {
					$("[name='yearNum']").focus();
					return false;
				}
				timeData.timestr = parseInt($("[name='yearNum']").val()) * parseInt($("[name='sel_yearUnit']").val());
				if($.trim($("[name='sel_month']").val()).length != 0 && $("[name='sel_yearUnit']").val() == 1) {
					timeData.timestr += '-' + $("[name='sel_month']").val();
				}
				if($.trim($("[name='sel_Days']").val()).length != 0 && $("[name='sel_yearUnit']").val() == 1) {
					timeData.timestr += '-' + $("[name='sel_Days']").val();
				}
			}
			timeData.timedesc = $("[name='txt_explain']").val();
			return timeData;
		},
		//景点发布框与超值机票发框数据交互
		tripPublishEvent : function(arg) {
			tripBox.control('getTimeDate');
			arg[0].unbind('click');
			arg[0].bind('click', function() {
				if($('.tabList').find('.trip').css('display') == 'block') {
					//验证是否上传了图片
					var fileUpload = $('.file_upload').val();
					if(fileUpload == '') {
						var contant = '<span style="display:block; padding:15px; em-size:14px;">请点击添加图片来上传景点照片！</span>';
						tripBox.plug('tipsPopUp', [contant,
						function() {
							$.closePopUp();
						}]);

					}
					$(".trip").find('input').trigger('blur');
					var file = $("input[name='Filedata']").val();
					var disc = $("input[name='disc']").val();
					var price = $("input[name='price']").val();
					var link = $("input[name='link']").val();
					var webId = $('.web_id').val();
					var data = {
						'type' : 'travel',
						'file' : file,
						'disc' : disc,
						'price' : price,
						'link' : link,
						'timestr' : timeData.timestr,
						'timedesc' : timeData.timedesc,
						'bc' : timeData.bc,
						'web_id' : webId,
						'imgTag' : imgTag
					}
					var warlen = $('.tripwarm').length;
					if($('.tripwarm').length > 0) {
						return false;
					} else {
						var url = mk_url('channel/trip/addTravelDate');
						tripBox.model('tripPublishData', [url, data,
						function(json) {
							tripBox.control('clearTrip', [$('.trip')]);
							if(json.status != 1) {
								alert(json.info);
							} else {
								tripBox.view('publicBox', [json.data.data, 'trip']);
							}
						}])

					}
				} else if($('.tabList').find('.airlineticket').css('display') == 'block') {
					$(".airlineticket").find('input').trigger('blur');
					var setoutCity = $("input[name='setout_city']").val();
					var arriveCity = $("input[name='arrive_city']").val();
					var setoutTime = $("input[name='setout_time']").val();
					var ticketPrice = $("input[name='ticket_price']").val();
					var discount = $("input[name='discount']").val();
					var ticketSort = $("select option:selected").val();
					var ticketLink = $("input[name='ticket_link']").val();
					var webId = $('.web_id').val();
					var data = {
						'type' : 'airticket',
						'setout_city' : setoutCity,
						'arrive_city' : arriveCity,
						'setout_time' : setoutTime,
						'ticket_price' : ticketPrice,
						'discount' : discount,
						'ticket_sort' : ticketSort,
						'ticket_link' : ticketLink,
						'timestr' : timeData.timestr,
						'timedesc' : timeData.timedesc,
						'bc' : timeData.bc,
						'web_id' : webId,
					}
					var warlen = $('.tripwarm').length;
					if($('.tripwarm').length > 0) {
						return false;
					} else {
						var url = mk_url('channel/airticket/addAirticket');
						tripBox.model('tripPublishData', [url, data,
						function(json) {
							tripBox.control('clearTickets', [$('.airlineticket')]);
							if(json.status != 1) {
								alert(json.info);
							} else {
								tripBox.view('publicBox', [json.data.data, 'airlineticket']);
							}
						}])

					}
				}
			})
		},
		//清空超值行程返回默認狀態
		clearTrip : function(dom) {
			dom[0].find('.addtripic').show();
			dom[0].find('.addsuccess').hide();
			dom[0].find('.restort').hide();
			dom[0].find('.file_upload').val('');
			dom[0].find("[name='disc']").val('请输入行程描述').css({
				'color' : '#8e8e8e'
			});
			dom[0].find("[name='price']").val('请输入价格').css({
				'color' : '#8e8e8e'
			});
			dom[0].find("[name='link']").val('http://').css({
				'color' : '#8e8e8e'
			});
		},
		//清空特價機會返回到默認狀態
		clearTickets : function(dom) {
			dom[0].find("input[name='setout_city']").val('出发城市').css({
				'color' : '#8e8e8e'
			});
			dom[0].find("input[name='arrive_city']").val('到达城市').css({
				'color' : '#8e8e8e'
			});
			dom[0].find("input[name='ticket_price']").val('请输入机票价格').css({
				'color' : '#8e8e8e'
			});
			dom[0].find("input[name='discount']").val('请输入折扣').css({
				'color' : '#8e8e8e'
			});
			dom[0].find("input[name='ticket_link']").val('http://').css({
				'color' : '#8e8e8e'
			});
		}
	}
	return this.control._class[name](arg);
}, tripBox.plug = function(name, arg) {
	this.plug._class = {
		//转发、评论、赞初始化
		likeforword : function(arg) {
			arg[0].children("ul.content").find('.commentBox:not(.hasComment)').commentEasy({
				minNum : 3,
				UID : CONFIG['u_id'],
				userName : CONFIG['u_name'],
				avatar : CONFIG['u_head'],
				userPageUrl : $("#hd_userPageUrl").val(),
				isShow : false,
				isOnlyYou : false,
				relay : true,
				relayCallback : function(obj, _arg) {
					var pagetype = obj.parents('.commentBox').attr('pagetype');
					var url = 'main/share/share_info?' + _arg;
					$.ajax({
						url : mk_url(url),
						dataType : 'jsonp'
					}).then(function(q) {
						if(q.status) {
							self.event(["forward"], [obj, q.data]);
						} else {
							alert(q.info);
						}
					});
				}
			});
		},
		//转发、评论、赞初始化
		tripListLike : function(arg) {
			arg.commentEasy({
                minNum:3,
                UID:CONFIG.u_id,
                userName:CONFIG.u_name,
                avatar:CONFIG.u_head,
                relay:!0,
                userPageUrl:$("#hd_userPageUrl").val(),
                relayCallback:function (obj,_arg) {
                    var comment=new ui.Comment();
                    comment.share(obj,_arg,!0);
                }
            });
		},
		//上传插件
		uploadPic : function(arg) {
			arg[0].nextAll().hide();
			arg[0].closest('form').nextAll().eq(0).show();
			var url = mk_url('album/api/publicUploadCrossPhoto', {
				type : 4,
				flashUploadUid : CONFIG['u_id']
			});
			arg[0].attr('action', url);
			$('.form_upload').each(function() {
				$.uploader(this);
				window.uploadCallback = {
					success : function(response, successElm) {
						var data = response;
						var msg = data.msg;
						var url = msg.groupname + "/" + msg.filename + "_";
						imgTag = [{
							b : {
								url : url + "b." + msg.type,
								type : msg.type,
								width : msg.photosizes.b.w,
								height : msg.photosizes.b.h
							},
							s : {
								url : url + "s." + msg.type,
								type : msg.type,
								width : msg.photosizes.s.w,
								height : msg.photosizes.s.h
							}
						}];
						arg[0].closest('form').nextAll().eq(0).hide();
						arg[0].closest('form').nextAll().eq(1).show();
						arg[0].closest('form').nextAll().eq(2).show();
						arg[0].closest('form').find('.upload_wrap').css({
							'background' : 'none'
						});
					}
				}
			});
		},
		//提示弹出框
		tipsPopUp : function(arg) {
			$(this).popUp({
				width : 300,
				title : '验证提示',
				content : arg[0],
				buttons : '<span class="popBtns blueBtn callbackBtn">知道了</span>',
				mask : false,
				maskMode : true,
				callback : arg[1]
			})
		}
	}
	return this.plug._class[name](arg);
}, tripBox.model = function(name, arg) {
	this.model._class = {
		//景点发布请求
		tripPublishData : function(arg) {
			$.ajax({
				url : arg[0],
				data : arg[1],
				type : 'POST',
				dataType : 'jsonp',
				success : arg[2]
			})
		}
	}
	return this.model._class[name](arg);
}, tripBox.init = function() {
	var $inputTxt = $('.input_txt');
	var $addtripic = $('.addtripic');
	var $form = $('.form_upload');
	var $submit = $('#distributeButton');
	var imgTag = '';
	var goods_offset = 0;

	$('#head_img').attr('src', $('#topUserAvatar').attr('src'));
	$('#out_date').calendar({
		type : "yyyy-m-d",
		button : false,
		time : false
	});

	tripBox.view('clearTxt', [$inputTxt]);
	if($('#tripDK').length > 0) {
		tripBox.plug('uploadPic', [$form]);
	}
	if($('#tripDK')[0]) {
		tripBox.control('tripPublishEvent', [$submit, 'trip']);
	}
	if($('#airlineticketDK')[0]) {
		tripBox.control('tripPublishEvent', [$submit, 'airlineticket']);
	}
	tripBox.view('tripListShow');
	tripBox.plug('tripListLike', $('div.commentBox'));
}
$(function() {
	tripBox.init();
})