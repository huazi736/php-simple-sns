/**
 * @author:    wangwb(w.hoby@qq.com)
 * @created:   2011/9/21
 * @version:   v1.0
 * @desc:      用户中心(好友请求/通知)

 * Update on 2012-03-07
 * @author: 张波
 * @desc: 修改了请求列表 通知列表的加载方式
 */
$(document).ready(function(){
	
	var showRequest_url = mk_url('main/invite/get_friend_apply');	//好友请求，点更多，跳转
	var showMessage_url = mk_url("main/msg/show_message");	        //站内信，点更多，跳转
	var listNotice_url = mk_url("main/notice/list_notice");	        //通知，点更多，跳转
	
	var request_top_url =  mk_url("main/invite/show_friendinvite");  //顶部请求（等待程序真实路径）
	var message_top_url =  mk_url("main/msg/msg_top");               //顶部站内信
	var notice_top_url =  mk_url("main/notice/top_notices");         //顶部通知
	
	var del_notice_url =  mk_url("main/notice/del_notice");    //通知列表删除通知
	var mwebpath =  mk_url("main/notice/get_notice");          //通知列表异步获取更多

	var addFollow_url = mk_url("main/api/addFollow");			         //加关注，请求关注 &系统推荐共用（等待程序真实路径）
	var actionFriend_url = mk_url("main/api/addFriend");	     //成为朋友
	var ignoreFriend_url = mk_url("main/invite/ignore_friend");	 //忽略好友请求

	var shareList_url = mk_url('main/invite/getCommonFriends');	//获取与某用户共同好友列表
	var del_shareList_url = mk_url('main/api/delFriend');	//删除好友
	var request_more = mk_url('main/invite/show_invite');		//异步获取好友请求更多
	var mayfriend_url = mk_url('main/invite/load_list');				//可能认识的人列表异步获取更多
	
	var num_requests = $('#requestsCountValue') ;
	/******************start:用户中心(左上角)*********************/
	//左上角，好友请求：点出下拉
	$('#requestsJewel').find('.jewelButton').click(function(){
		global.closeDropDown();
		$("#requestsJewel").addClass('dropDown');
		$("#requestsFlyout").find('.invite_list').html("<li class='firstChild ui-autocomplete-loading not-request-list'></li>");
				$.ajax({
				url: request_top_url,
				type: 'get',
				dataType: 'jsonp',
				success: function(result){
					result =result.data;
					if(result.state==1){
						$('#requestsFlyout').find('ul.invite_list').html(result.data);
						doRebind();
					}else{
						//接受返回的信息提示
						$('#requestsFlyout').find('ul.invite_list').html(result.data);
					 }
				 }
			 });
			 return false;
	});
	//左上角，好友请求：点出更多，跳转
	$('#requestsFlyout').find('>div.jewelFooter > a.seeMore').click(function(){
		window.location.href = showRequest_url;
		return false;
	});
	
	//左上角，站内信：点出下拉
	$('#messagesJewel').find('.jewelButton').bind('click', function(){
		global.closeDropDown();
		$("#messagesJewel").addClass('dropDown');
		$('.jewelItemList').html("<li class='firstChild ui-autocomplete-loading'></li>");
			$.ajax({
				url: message_top_url,
				type: 'get',
				dataType: 'jsonp',
				success: function(result){
					result =result.data;
					if (result.state == 1) {
						$('#messagesJewel').find('>a').removeClass('hasNew');
						$('#messagesCountValue').text('');
						$('#messagesFlyout').find('>ul.jewelItemList').html(result.data);
						doRebind();
					}
					else {
						$('.jewelItemList').html('<li class="firstChild not-message-list"><span class="not-message-list">暂时没有站内信</span></li>');
					}
				}
			});
			return false;
	});
	//左上角，站内信：点出更多，跳转
	$('#messagesFlyout').find('>div.jewelFooter > a.seeMore').click(function(){
		window.location.href = showMessage_url;
		return false;
	});
	
	//左上角，通知：点出下拉
	$('#notificationsJewel').find('.jewelButton').bind('click', function(){
		global.closeDropDown();
		$("#notificationsJewel").addClass('dropDown');
			$('.noticeItemList').html("<li style='padding:15px 0' class='firstChild ui-autocomplete-loading'></li>");
			$.ajax({
				url: notice_top_url,
				type: 'get',
				dataType: 'jsonp',
				success: function(result){
					result =result.data;
					if (result.state == 1) {
						$('#notificationsJewel').find('>a').removeClass('hasNew');
						$('#notificationsCountValue').text('');
						$('#notificationsFlyout').find('>ul.noticeItemList').html(result.data);
						$('#notificationsJewel').find('.jewelFooter').remove();
						$('.noticeItemList').after('<div class="jewelFooter"><a class="seeMore" href="'+listNotice_url+'"><span>查看所有通知</span></a></div>');
						doRebind();
					//此处为海棠的相册代码
					//$(".picView").picViewer();
					}
					else {
						$('#notificationsJewel').find('.jewelFooter').remove();
						$('.noticeItemList').html('<li class="firstChild"><span class="not-notice-list">暂时没有可显示的通知</span></li>');
						$('.noticeItemList').after('<div class="jewelFooter" id="no_seeMore"><a class="seeMore"href="javascript:void(0);"><span>查看所有通知</span></a></div>');
						
					}
				}
			});
			return false;
	});
	/******************end:用户中心(左上角)*********************/
		
	/*************start:请求处理事件(左上角&详细页共用)**************/
	function doRebind(){
		//加关注
		$('span[name="reqAdd"]').bind('click', function(){
			var rid = $(this).parentsUntil('li').parent().attr('rid');
			var _this=this;
			
			$.djax({
				url:  addFollow_url,
				type: 'post',
				dataType: 'json',
				data: {
					f_uid: rid
				},
				success: function(data){
					if(data.status==1){
						$(_this).removeClass().addClass('btnGray  cursor-d');
						$(_this).html('<i class="friend"></i><span>关注</span>').next().remove();
						$inviteContent.find('div.mayBottom > a').attr('pid','1');
						$(_this).unbind('click');
					
					}
				}
			});

		});
		
		//加好友
		$('span[name="reqFriend"]').bind('click', function(){
			var rid = $(this).parentsUntil('li').parent().attr('rid'); //获取当前请求的ID
			var _this=this;
			var u_name = $(_this).parent().parent().find("strong").text();
			$.ajax({
				url:  actionFriend_url,
				type: 'post',
				dataType: 'jsonp',
				data: {
					f_uid: rid
				},
				success: function(data){
					data =data.data;
					if (data.relation == 10) {
						var num_Friend = Number(num_requests.html());
						new_num_Friend = Number(num_requests.html()) - 1;
							if (new_num_Friend < 1) {
								$('#requestsCountWrapper').parent().removeClass('hasNew');
							}
							else {
								num_requests.html(new_num_Friend);
							}
							$(_this).removeClass().addClass('btnGray cursor-d').html('<i class="friend"></i><span>好友</span>').next().remove();
							$(_this).unbind('click');
			
						}
						if (data.relation == 4 || data.relation == 2) {
						$(_this).popUp({
						width:400,
						title:'温馨提示',
						content:'<p class="unable-friend">您和<span>'+u_name+'</span>已取消互相关注，不能加为好友!</p>',
						buttons:'<span class="popBtns closeBtn callbackBtn">关闭</span>',
						mask:false,
						maskMode:true,
						callback:function(){
								window.location.reload();
								return false;
							}
						});
	
					}
				}
			});

		});
		
		//top忽略请求，交互处理函数
		$('span[name="reqIgnore"]').bind('click', function(){
			var rid = $(this).parentsUntil('li').parent().attr('rid');
			var _this=this;
			$.djax({
	            url:  ignoreFriend_url,
	            type: 'post',
	            dataType: 'json',
	            data: {
	            	fr_uid: rid
				},
	            success: function(data){
					data =data.data;
					if(data.state==1){
						var num_Friend = Number(num_requests.html());
							new_num_Friend = Number(num_requests.html())-1;
							if(new_num_Friend < 1){
								$('#requestsCountWrapper').parent().removeClass('hasNew');
							}else{
								num_requests.html(new_num_Friend);
							}
						$('#requestsFlyout').find('ul.invite_list').html(data.data);
						doRebind()
					}else{
						//alert(data.msg);
					}
	            }
	   		});
		});

		
	
//删除好友
$('#share_List_box').find('a.delFriend').bind('click', function(){
			var rid = $(this).parent().attr('rid');
			var _this=$(this);
			
			$.ajax({
				url:  del_shareList_url,
				type: 'post',
				dataType: 'json',
				data: {
					f_uid: rid
				},
				success: function(data){
					if(data.status==1){
						_this.parents('li.clearfix').remove();
					}
				}
			});
		});
		
	}
	/***********start:请求处理事件(左上角&详细页共用)************/
	
	/****************start:查看所以请求列表********************/
	//判断是否为：查看所以请求列表
	if($('#inviteContent').size()){
		
		doRebind(); //初始化绑定按钮事件
		
		var $inviteContent = $('#inviteContent');
		var $inviteContent_list = $('#inviteList');
				//所有请求列表：忽略请求，交互处理函数

			var list_reqIgnore = function(){
				var rid = $(this).parentsUntil('li').parent().attr('rid');
				var _this=$(this);
				$.ajax({
		            url:  ignoreFriend_url,
		            type: 'post',
		            dataType: 'json',
		            data: {
		            	fr_uid: rid
					},
		            success: function(data){
							data= data.data;
						if(data.state==1){
							var num_Friend = Number(num_requests.html());
							new_num_Friend = Number(num_requests.html())-1;
							if(new_num_Friend < 1){
								$('#requestsCountWrapper').parent().removeClass('hasNew');
							}else{
								num_requests.html(new_num_Friend);
							}
							$('#inviteList').html(data.data);
							_this.parentsUntil('ul').remove();
							doRebind();
							if (data.isend){
								$inviteContent_list.unscrollLoad();
								$(window).off("scroll");
							}
						}else{
							//alert(data.msg);
						}
						_this.bind("click",list_reqIgnore);
		            }
		   		});
				_this.unbind("click",list_reqIgnore);
			}

		//查看所以请求列表：滚动自动加载
			var pid = $(this).attr('pid');
			var _this=this;
			$inviteContent_list.scrollLoad({
				text: "<div class='inviteBottom'><a href='javascript:void(0);'>点击查看更多↓</a></div>",
	            url: request_more,
	            success: function(data){
					if(data.status==1){
						var str = ''; 
						for (var i = 0, len = data.data.length; i < len; i++) {
							str += '<li class="clearfix" rid="'+ data.data[i].uid +'">';
							str += '	<span class="picHead"><a href="'+data.data[i].userpath+'"><img src="'+ data.data[i].avatarurl +'" alt="头像" /></a></span>';
							str += '	<span class="friendInfo"><a href="'+data.data[i].userpath+'"><strong>'+ data.data[i].username +'</strong></a><br>'+ data.data[i].dateline +'</span>';
							str += '<span class="addView"><span class="btnBlue" name="reqFriend"> <i></i><a href="javascript:void(0);">加好友</a></span> <span class="btnGray" name="reqIgnore_all"><a href="#">忽略请求</a></span></span>';
							str += '</li>';
						}
						$inviteContent_list.append(str);
						$inviteContent_list.find('span[name="reqIgnore_all"]').bind('click',list_reqIgnore);
						if(data.isend){
							
						}
						doRebind();	//重新绑定事件
					}else{
							$inviteContent_list.append('<li>没有可显示的列表</li>');
					    }
	            }
	   		});

		
		//系统推荐，点击重新换一组，异步
		$inviteContent.find('div.mayBottom > a').bind('click', function(){
			//获取页面中页数值
			var pid = $(this).attr('pid');
			var _this=this;
			$.ajax({
	            url: mayfriend_url,
	            type: 'post',
	            dataType: 'json',
	            data: {
					page: pid
				},
	            success: function(result){
					result= result.data;
					var data = result.data;
					if(result.state==1){
						var str = '';
						if( data != null){
							for (var i = 0, len = data.length; i < len; i++) {
							str += '<li class="clearfix" rid="'+ data[i].uid +'">';
							str += '	<span class="picHead"><a href="'+data[i].userpath+'"><img src="'+ data[i].avatarurl +'" alt="头像" /></a></span>';
							str += '	<span class="friendInfo"><a href="'+data[i].userpath+'"><strong>'+ data[i].name +'</strong></a><br>'+ data[i].sum +'</span>';
							str += '	<span class="addView"><span class="btnBlue" name="reqAdd"><i class="a"></i><a href="javascript:void(0);">加关注</a></span></span>';
							str += '</li>';
							}
						}else{
							str += '<li>没有可能认识的人</li>';
							$('.mayBottom').remove();
						}
						if( result.pageCount <= 4 ){
							$('.mayBottom').remove();
						}else{
							pid++;//改变页面中页数值
						}
						if(result.next_page == 0){
							$(_this).attr('pid','1');
						}else{
							$(_this).attr('pid',pid);
						}
						//循环一组后替换掉以前的内容
						$('ul.mayKnow_list').html(str);
						doRebind();	//重新绑定事件
					}
	            }
	   		});
		});
	
		//显示共同好友列表
		$inviteContent.find('ul.mayKnow_list').delegate('a.shareNum','click', function() {
			var rid = $(this).parentsUntil('li').parent().attr('rid');
			var _this = this;
			$.ajax({
				url: shareList_url,
				type: "post",
				dataType: "json",
				data: {
					f_uid: rid
				},
				success: function(result){
						result =result.data;
					if(result.state==1){
						$(_this).popUp({
							width : 467,
							height : 300,
							title : "共同好友列表",
							content : '<div class="shareList" id="share_List_box">'+ result.data +'</div>',
							mask : true,
							maskMode : false,
							buttons : '<span class="popBtns closeBtn">关闭</span>',
							callback : function() {
								$(_this).closePopUp();
							}
						});
						doRebind();	//重新绑定事件
						return false;
					
					}else{
						alert(result.msg);
					}
				}
			});
		});
	}
	/**************end:查看所以请求列表*******************/
	
	/**************start:查看所以通知列表********************/
	/**
	 * Created on 2012-3-7
	 * @author: zhangbo
	 * @desc: 通知列表相关处理
	 */
	
	//判断是否为：查看所以通知列表
	if($('#noticeContent').size()){
		var $noticeContent = $('#noticeContent');
		
		//通知列表：鼠标放上去显示效果
		$noticeContent.delegate('ul.noticeList > li', 'mouseenter mouseleave', function(){
			$(this).find('>span.btn_del').toggle();
		});
		//通知列表：删除通知
		$noticeContent.delegate('span.btn_del', 'click', function(){
			if(confirm('你确定要删除此条通知吗？')){
				var rid = $(this).parent().attr('rid');
				var _this = this;
				$.ajax({
		            url: del_notice_url,
		            type: 'post',
		            dataType: 'json',
		            data: {
						rid: rid
					},
		            success: function(data){
						data = data.data;
						if(data.state==1){
							$(_this).parent().remove();
						}else{
							alert(data.msg);
						}
		            }
		   		});
			}
		});
		
	//通知列表：滚动自动加载
			var cur_typeid = $(".current").find("a.select_event").attr('rel');
		   	$('#noticeContent-box').scrollLoad({
		   		text: "点击查看更多↓",
		   		url: mwebpath,
		   		data: {
		   			typeid: cur_typeid
		   		},
		   		success: function(data){
				   			if (data.status == 1) {
								var lastTime = $('.noticeTimeTitle').last().html();//最下面的的时间
				   				var _str = '';
				   				for (var i = 0, arrLenth = data.data.length; i < arrLenth; i++) {
				   					var noticeTime = data.data[i];
									var notice_time = noticeTime[0];//滚动第一个的时间
				   					_str += '<div class="noticeTimeList">';
									if(lastTime != notice_time){
										_str += '<h3 class="noticeTimeTitle">' + noticeTime[0] + '</h3>';
									}
				   					_str += ' <ul class="noticeList">';
				   					for (j = 0, notices_length = noticeTime[1].length; j < notices_length; j++) {
				   						var noticeNoticeItem = noticeTime[1][j];
				   						_str += ' <li rid="' + noticeNoticeItem["_id"] + '"> <span class="btn_del"></span>';
				   						_str += ' <i class="' + formatIconType(noticeNoticeItem["type"]) + '"></i>';
				   						_str += noticeNoticeItem["content"] + '<abbr class="timestamp">' + noticeNoticeItem["dateline"] + '</abbr>';
				   					}
				   					_str += ' </ul>';
				   					_str += ' </div>';
				   					}
									$('#noticeContent-box').append(_str);//插入组合好的html代码
				   				}else{
									$('#noticeContent-box').append('<ul><li>暂时没有新通知</li></ul>');
								}
							}
						});
		
	//切换其他通知
	$("#noticeDrop").find(".select_event").click(function(){
				var typeId = $(this).attr('rel');
					cur_val = $(this).find("span").text();
					$("#noticeDrop").removeClass('dropDown');
					$("#noticeDrop li").removeClass('current');
					$('#noticeDrop .triggerBtn').find('span').text(cur_val);
					$('#noticeDrop .triggerBtn').find('span').attr('rel',typeId);
					$(this).parent().addClass("current");
					$('#noticeContent-box').children().remove();
					$('.nextPage').children().remove();
					var self = this;
			      	$('#noticeContent-box').scrollLoad({
						text: "点击查看更多↓",
						url:mwebpath,
						type: 'post',
						dataType: 'json',
						data:{typeid:typeId},
						success:function(data){
						if (data.status == 1) {
							var lastTime = $('.noticeTimeTitle').last().html();//最下面的的时间
							var _str = '';
						for (var i = 0, arrLenth = data.data.length; i < arrLenth; i++) {
							var noticeTime =data.data[i];
							var notice_time = noticeTime[0];//滚动第一个的时间
				   				_str += '<div class="noticeTimeList">';
								if(lastTime != notice_time){
									_str += '<h3 class="noticeTimeTitle">' + noticeTime[0] + '</h3>';
									}
								_str += ' <ul class="noticeList">';
								for (j = 0, notices_length = noticeTime[1].length; j < notices_length; j++) {
									var noticeNoticeItem =noticeTime[1][j];
									_str += ' <li rid="' + noticeNoticeItem["_id"] + '"> <span class="btn_del"></span>';
									_str += ' <i class="' + formatIconType(noticeNoticeItem["type"]) + '"></i>';
									_str += noticeNoticeItem["content"] +'<abbr class="timestamp">'+ noticeNoticeItem["dateline"] +'</abbr>';
								}
								_str += ' </ul>';
								_str += ' </div>';
							}
								$('#noticeContent-box').append(_str);//插入组合好的html代码
									
						}else{
							 $('#noticeContent-box').append('<ul><li>暂时没有新通知</li></ul>');
						}
		            }
			 	 });
					
		});
	
		 
	}
	//通知小图标by txb 2012.7.23
	function formatIconType(type){
		var str = "icon ";
		switch(type){
			case "dk":{
				str += "dk";
			}break;
			case "photo":{
				str += "photo";
			}break;
			case "video":{
				str += "video";
			}break;
			case "blog":{
				str += "blog";
			}break;
			case "ask":{
				str += "ask";
			}break;
			case "info":{
				str += "info";
			}break;
			case "event":{
				str += "event";
			}break;
			case "web":{
				str += "web";
			}break;
			case "group":{
				str += "group";
			}break;
			default:{
				str += "dk";
			}break;
		}
		return str;
	}
	/***************end:通知详细页********************/	
});