/**
 * @author:    wangwb(w.hoby@qq.com)
 * @created:   2011/12/15
 * @version:   v1.0
 * @desc:      首页_活动
 */
 
$(document).ready(function() {
	//头部
	if( $.browser.msie && ($.browser.version =='6.0') ){
		$('#header').children().append('<iframe style="position:absolute; z-index:-1; width:100%; height:100%; top:0; left:0; border:0; filter:alpha(opacity=0);"></iframe>');
	}
	
	//研发在此处设置路径
	var doMoreList_url  = $('#doMoreList_url').val(),
		doAnswer_url     = mk_url("event/event/doAnswer"),
		guestList_url   = mk_url("event/event/getUserListByEventStatus"),
		delGuest_url    = mk_url("event/event/delGuest"),
		sendNotice_url  = mk_url("event/event/sendNotice"),
		cancelEvent_url = mk_url("event/event/cancelEvent"),
		exportEvent_url = mk_url("event/event/exportEvent"),
 getEventFollowUserList = mk_url("event/event/getEventFollowUserList"),
      inviteEventFriend = mk_url("event/event/inviteEventFriend"),
		photo_url       = mk_url("event/event/addEventPic");
	
	/***************start:活动列表页********************/
	var $eventBox = $('#event').find('div.eventBox');
			//正在进行/已结束的活动
		var isEnd 	  = window.location.href.match(/endlist/),
			activeStr = isEnd ? '已结束的活动&nbsp;&nbsp;':'进行中的活动';
		$('#eventActive').dropdown({
			templete: true,
			btn: '<span>'+ activeStr +'</span>', 
			list: '<ul class="dropListul checkedUl"><li ><a class="itemAnchor" href="'+mk_url("event/event/mylist",{dkcode:CONFIG['action_dkcode']})+'"><i></i><span>进行中的活动</span></a></li><li><a class="itemAnchor" href="'+mk_url("event/event/endlist",{dkcode:CONFIG['action_dkcode']})+'"><i></i><span>已结束的活动&nbsp;&nbsp;</span></a></li></ul>',
			callback: function(ele) {}
		}); 
		
	
	if($eventBox.size()>0){
		var eventType = $('input.eventType').attr('eventType');
		var listName = '';
		//更多加载
		$('div.eventList').scrollLoad({
			text:"更多活动↓",	
			url:doMoreList_url, //请求地址
			data:{eventType:eventType}, //请求参数
			success:function(result){	//请求成功
				if(result.status){
					$eventBox.find("div.eventLoading").removeClass("hide");
					var data = result.data;
					for(var i = 0; i < data.length; i++){
						var listTit = '<h3>' + data[i].name + '</h3>';
						if(data[i].name == listName){
							$('div.eventList').find('ul:last').append(data[i].list);
						}else{
							$('div.eventList').append(listTit + '<ul>' + data[i].list + '</ul>');
							listName = data[i].name;
						}
					}
					$eventBox.find("div.eventLoading").addClass("hide");
				}else{
					$.alert(result.msg);
				}
			}
		});



		//确定参加
		$eventBox.on('click','a.eventyes',function(){
			var eid = $(this).closest('li').attr('eid');
			var _this = this;
			$.ajax({
				url : doAnswer_url,
				type : 'post',
				dataType : 'json',
				data : {eid:eid,status:2},
				success : function(result){
					if(result.status){
						$(_this).parent().text("确定参加");
					}else{
						$.alert(result.msg);
					}
				}	
			});
		});
		
		//不参加
		$eventBox.on('click','a.eventno',function(){
			var eid = $(this).closest('li').attr('eid');
			var _this = this;
			$.ajax({
				url : doAnswer_url,
				type : 'post',
				dataType : 'json',
				data : {eid:eid,status:0},
				success : function(result){
					if(result.status){
						$(_this).parent().text("不参加");
					}else{
						$.alert(result.msg);
					}
				}
			});
		});
		
	}
	/****************end:活动列表页*********************/

	/***************start:活动编辑页********************/
	
	//textarea高度自适应
	//if($('#haveTextarea').size() > 0){$('#attachPhotoIntroduce,#attachVideoIntroduce,#eventInfo').textareaHeight();}
	
	//输入不能大于140个字符
	function limitLength(inputId){
		if(inputId.val().length > 140){
			var str = inputId.val();
			str = str.substring(0,140);
			inputId.val(str);
		}
	}
	var eventInfoLimit = $('#eventInfo'),picInfoLimit = $('#attachPhotoIntroduce');

	eventInfoLimit.keydown(function(){limitLength(eventInfoLimit);}).keyup(function(){limitLength(eventInfoLimit);}).bind("contextmenu",function(e){return false;});
	picInfoLimit.keydown(function(){limitLength(picInfoLimit);}).keyup(function(){limitLength(picInfoLimit);}).bind("contextmenu",function(e){return false;});
	
	if($('#eventEditForm').size()>0){

		var eventId = $('#eventid').val();

		//调用日期插件
		$(".html_date").calendar({type:"yyyy-mm-dd",button:false,time:false});
		
		//初始化时间下拉框
		(function(){
			var selStart = $('#startTime').attr('sel'),selEnd = $('#endTime').attr('sel');
			var str ='<ul>';
			for(var i=0;i<24;i++){
				str +='<li value="'+ i*60 +'">'+ i +':00</li>';
				str +='<li value="'+ (i+0.5)*60 +'">'+ i +':30</li>';
			}
			str += '</ul>'
			$('.timeList').html(str);
			setTimeout(function(){ $('#startTime').val(selStart); $('#endTime').val(selEnd);},1); //解决IE6下bug
		})();
		
		//时间选择操作
		$('.timeValue').click(function(e){
			e.stopPropagation();
			$(this).find('.timeList').toggle();
			$(document).click(function(){
				$('.timeList').hide();
			});
		});
		$('.timeList ul li').hover(function(){
			$(this).addClass('hov');
		},function(){
			$(this).removeClass('hov');
		}).click(function(){
			var nowValue = $(this).attr('value'),
				nowClock = $(this).text();
			$(this).parents('.timeSelect').find('input').val(nowValue);
			$(this).parents('.timeSelect').find('.timeValue span').text(nowClock);
		});
		
		
		//生成地区
		var seletedArea = $('#event_area').attr('ref'); 
		var eventArea = new initAreaComponent('event_area','0-nation,1-province,1-city,',seletedArea,'');
			eventArea.initalize();   //初始化
		
		//活动创建页面刷新清除输入内容
		if($('#createPage').size() > 0){
			$('#startDate').val('');
			//$('#startTime').val('');
			$('#endDate').val('');
			//$('#endTime').val('');
			$('#eventName').val('');
			$('.inputText').val('');
			$('#eventInfo').val('');
		}

		//验证表单日期与名称
		$('#btnEventSave').click(function(){
			var regex = /[^(^\s*)|(\s*$)]/,
				dateSet = /^\d{4}-\d{2}-\d{2}$/,
				errMsg = "";
			
			//显示错误信息
			function errInfo(errMsg){
				$('#eventEditTip').text(errMsg).fadeIn('slow').css('display','block');
			}
			

			//验证活动标题
			if( !$.trim($('#eventName').val()) ){
				errMsg = "请输入活动名称。";
				errInfo(errMsg);
				return false;
			}

			//获取系统当前日期时间
			var today = $('#startDate').attr('now'),
				date  = new Date(),
				nowTime = date.getHours() * 60 + date.getMinutes();

			//获取用户选择的时间
			var startDate = $('#startDate').val(),
				endDate = $('#endDate').val(),
				startTime = parseInt( $('#startTime').val() ),
				endTime = parseInt( $('#endTime').val() );
			
			//验证开始时间
			if( !regex.test(startDate) ){
				errMsg = "请选择活动开始时间。";
				errInfo(errMsg);
				return false;
			}else if( (startDate < today || (startDate == today && startTime <= nowTime) ) && $('#createPage').size()){ //与当前系统时间比较，只在创建时做判断
				errMsg = "活动开始时间不能早于当前时间，请选择正确的时间。";
				errInfo(errMsg);
				return false;
			}

			//验证结束时间
			if( !regex.test(endDate) ){
				errMsg = "您还没有设置活动结束时间。";
				errInfo(errMsg);
				return false;
			}else if( endDate < startDate || ( endDate == startDate && endTime <= startTime ) ){ //与开始时间比较
				errMsg = "活动结束时间不能早于开始时间，请选择正确的时间。";
				errInfo(errMsg);
				return false;
			}
			
			
			var str = '';
			$('#adminList').find('span').each(function(index){
				str += $(this).attr('rel')+',';
			});
			$('#addAdmin2').val(str);
			
		});	
		
		//添加管理员
		if( $('#tokenarea').size()>0 ){
			$.ajax({
				url: mk_url("event/event/getEventFollowUserList",{eventid:eventId,field:1}),
				type: "post",
				dataType: "json",
				data: {eventid:eventId},
				success: function(result){
					if(result.status==1){
						$('#addAdmin').searcher({
							inputWrap:'#addAdminWrap',
							tokenArea:'#adminList',
							url:null,
							compactArea:'#compactAdmin',
							staticData: result.data,
							showNum:5
						});
						$('#addAdmin').val('请输入一个你朋友的名字').css({
							color: '#ccc',
							width: '200px'
						}).blur();
						//第一个管理员不允许删除
						var adStr = '<div class="admin">' + $('#adminList span').eq(0).html() + '</div>';
						$('#adminList span').eq(0).remove();
						$('#adminList').prepend(adStr);
						$('#adminList div a').remove();
						
					}else{
						$.alert(result.info);
					}
				}
			});	
		}
		
		//显示好友列表 编辑
		$('#inviteGuest').click(function(){
			var eventId = $('#eventid').val();
			var uid = $('#contentArea').attr('uid');
			var detail =$('#friends_detail');
			new CLASS_FRIENDS_LIST({
				detail:detail,	//列表放置位置
				id:uid,			//当前用户的id
				elm:$(this),		//触发好友窗口点击对象
				getUrl:mk_url("event/event/getEventFollowUserList",{eventid:eventId}), //获取好友列表url
				postUrl:mk_url("event/event/inviteEventFriend",{eventid:eventId}),		//发送选中的
				title:'邀请好友',
				noData:'您还没有任何粉丝'
			});
		});
		
		var add_img_i=0;
		//上传图片
		$('#leftCol > div.addEventImg').click(function(){
			$(this).popUp({
					width : 500,
					title : '添加活动照片',
					content : '<iframe src="" width="0" height="0" class="hideEle" name="uploadPhotoHiddenIframe"></iframe>'+
								'<form id="uploadPhotoForm" name="uploadPhotoForm" action="'+photo_url+'?eventid='+ eventId +'&' + (add_img_i++) +'" method="post" target="uploadPhotoHiddenIframe" enctype="multipart/form-data">'+
									'<div id="uploadPhotoPanel">'+
										'<div class="uploadButtonCont createPhoto">'+
											'<label for="uploadPhotoButton">请本地选择一张图片：</label><input type="hidden" name="MAX_FILE_SIZE" value="4194304" /><input type="file" id="uploadPhotoButton" name="uploadPhotoFile" size="30" style="height:24px;" />'+
											'<p>（上传图片最大不能超过4M！允许上传的图片格式：jpg、png、jpeg、gif） </p>'+
										'</div>'+
									'</div>'+
								'</form>',
					buttons : '<span class="btn_forward popBtns blueBtn callbackBtn">关闭</span>',
					mask : false,
					maskMode : true,
					callback : function() {
						$.closePopUp();
					}
			});
			
			//浏览选择图片
			$('#uploadPhotoButton').change(function(){
				$('#uploadPhotoForm').submit();

				$('#uploadPhotoButton').replaceWith('<img src="'+CONFIG['misc_path']+'img/system/more_loading.gif" width="16" height="11" border="0" alt="sending..." />');
				//PHP回调函数，返回状态
				window.sendPhotoComplete = function (result){
					if(result.status){
						$.closePopUp();
						$('#eventImg').find('>img').attr('src',result.eventPhoto).end().next().html('<img src="'+CONFIG['misc_path']+'img/system/edit_icon.gif" height="11" width="11" alt="+" />更改活动照片');
					}else{
						$.closePopUp();
						$.alert('图片上传失败，请确认图片格式是否正确或检查网络是否正常！');
					}
				};
			});
				
		});

		//返回列表
		$('#btnReturn').click(function(){
			var _this = this;
			$.confirm('提示框','数据尚未保存，确定要离开此页面吗',function(){
				window.location.href = $(_this).attr('ref');
				$.closePopUp();
			});
			return true;
		});

	}
	/*****************end:活动编辑页********************/
	
	/****************start:活动显示页*******************/
	if($('#eventDetail').size()>0){
		var eventId = $('#eventDetail').attr('eventid');
		//受邀人列表
		$('#eventGuest').find('h3>a').click(function(){
			var joinType = parseInt($(this).attr('ref')),
				guestThis = this;
			$().popUp({
				width : 467,
				title : "与活动有关的人",
				content : '<div class="eventPopTop"><div id="guestCombox" class="dropWrap dropMenu"></div></div><div class="guestList"></div>',
				mask : true,
				maskMode : false,
				buttons : '<span class="popBtns blueBtn closeBtn guestBtn">关闭</span>',
				callback : function(){$.closePopUp();}
			});
			$('span.guestBtn').on('click',function(){
				window.location.reload();
			});
			//getPopList(joinType);

			var tmp = location.search.match(/dkcode=\d+/);

			tmp = tmp ? '?' + tmp[0] : '';

			$.ajax({
				url: guestList_url + tmp,
				type: "post",
				dataType: "json",
				data: {eventid:eventId, jointype:joinType},
				success: function(result){
					var data =result.data.data;
					var btnCon = '',
						selectIndex = 0;
					if($(guestThis).parent().parent().hasClass('eventAdmin')){
						btnCon = '管理员（<em>'+ result.data.managenum +'</em>）';
						selectIndex = 1;
					}else if($(guestThis).parent().parent().attr('id')=='guest_going'){
						btnCon = '确定参加（<em>'+ result.data.gonum +'</em>）';
						selectIndex = 2;
					}else if($(guestThis).parent().parent().attr('id')=='guest_invited'){
						btnCon = '尚未答复（<em>'+ result.data.unkownnum +'</em>）';
						selectIndex = 3;
					}
					$('#guestCombox').dropdown({
						templete: true,
						btn: '<span>'+ btnCon + '</span>', 
						list: '<ul class="dropListul checkedUl"><li ><a class="itemAnchor" rel="0" href="javascript:void(0);"><i></i><span>全部（<em>'+ result.data.allnum +'</em>）</span></a></li><li><a class="itemAnchor" rel="4" href="javascript:void(0);"><i></i><span>管理员（<em>'+ result.data.managenum +'</em>）</span></a></li><li><a class="itemAnchor" rel="4" href="javascript:void(0);"><i></i><span>确定参加（<em>'+ result.data.gonum +'</em>）</span></a></li><li><a class="itemAnchor" rel="8" href="javascript:void(0);"><i></i><span>尚未答复（<em>'+ result.data.unkownnum +'</em>）</span></a></li></ul>',
						callback: function(ele) {
							var rel = ele.attr('rel');
							getPopList(rel);
						}
					}); 
					$('#guestCombox ul li').eq(selectIndex).addClass('current');
					if(result.status==1){
						var str  = '<ul>';
						for(var i=0,len=data.length;i<len;i++){
							var can_del = (result.data.canAdmin && data[i].type != '2') ? '1' : '0';
							str += ' <li class="clearfix" uid="'+ data[i].uid +'" answer="'+ data[i].answer +'" del="'+can_del+'"><span class="iconDel"></span><a href="'+ data[i].link +'" target="_parent"><img width="50" height="50" alt="头像" src="'+ data[i].userhead +'">'+ data[i].username +'</a></li>';
						}
						str += '</ul>';
						$('#popUp').find('div.guestList').html(str);
					}else{
						alert(result.msg);
					}
				}
			});
			
			//初始化及选择后请求
			function getPopList(joinType){
				$.ajax({
					url: guestList_url + tmp,
					type: "post",
					dataType: "json",
					data: {eventid:eventId, jointype:joinType},
					success: function(result){
						var data =result.data.data;
						if(result.status==1){
							var str  = '<ul>';
							for(var i=0,len=data.length;i<len;i++){
								var can_del = (result.data.canAdmin && data[i].type != '2') ? '1' : '0';
								str += ' <li class="clearfix" uid="'+ data[i].uid +'" answer="'+ data[i].answer +'" del="'+can_del+'"><span class="iconDel"></span><a href="'+ data[i].link +'" target="_parent"><img width="50" height="50" alt="头像" src="'+ data[i].userhead +'">'+ data[i].username +'</a></li>';
							}
							str += '</ul>';
							$('#popUp').find('div.guestList').html(str);
						}else{
							alert(result.msg);
						}
					}
				});
			}
			
			//删除好友列表
			$('#popUp').find('div.guestList').on('mouseenter mouseleave','li',function(){
				if($(this).attr('del') == '1'){
					$(this).find('>span').toggle();
				}
			}).on('click','span.iconDel',function(){ 
				var _thisDel = this;
				$.confirm('人员删除','你确定要删除此人吗？',function(){
					var uid = $(_thisDel).parent().attr('uid');
					$.ajax({
						url: delGuest_url,
						type: "post",
						dataType: "json",
						data: {eventid:eventId, jointype:joinType, uid:uid},
						success: function(result){
							if(result.status==1){
								//改变按钮数字
								var guestBtnNum_obj = $('#guestCombox').find('>div.triggerBtn em'),
									guestBtnNum_new = parseInt( $(guestBtnNum_obj).text() ) -1;
								$(guestBtnNum_obj).text( guestBtnNum_new >0 ? guestBtnNum_new : 0 );

								//改变下拉表中全部
								var guestAllNum_obj = $('#guestCombox').find('>div.dropList em').eq(0),
									guestAllNum_new = parseInt( $(guestAllNum_obj).text() ) -1;
								$(guestAllNum_obj).text( guestAllNum_new >0 ? guestAllNum_new : 0 );

								//改变下拉表所属分类
								var answer = $(_thisDel).parent().attr('answer'),
									guestTypeNum_obj = $('#guestCombox').find('>div.dropList em').eq(4-answer),
									guestTypeNum_new = parseInt( $(guestTypeNum_obj).text() ) -1;
								guestTypeNum_obj.text( guestTypeNum_new >0 ? guestTypeNum_new : 0 );

								$(_thisDel).parent().remove();

							}else{
								alert(result.msg);
							}
						}
					});
				}); 
			});
		});
		
		//显示好友列表
		$('#inviteGuest').click(function(){
			//alert('b');
			var uid = $('#contentArea').attr('uid');
			var detail =$('#friends_detail');
			new CLASS_FRIENDS_LIST({
				detail:detail,	//列表放置位置
				id:uid,			//当前用户的id
				elm:$(this),		//触发好友窗口点击对象
				getUrl:mk_url("event/event/getEventFollowUserList",{eventid:eventId}), //获取好友列表url getEventFollowUserList
				postUrl:mk_url("event/event/inviteEventFriend",{eventid:eventId}),		//发送选中的
				title:'邀请好友',
				noData:'您还没有任何粉丝',
				callback:function(){
					setTimeout(function(){window.location.reload();},1500);
				}
			});
		});
		
		
		//发送通知
		$('#sendNotice').click(function(){
			$(this).popUp({
				width : 467,
				title : "发送通知",
				content : '<div class="popConBox"><div id="errMsgTip"></div><table class="sendNoticeTable">'+
							'<tbody><tr class="dataRow">'+
								'<th class="label">参加人：</th>'+
							  	'<td><select id="status" name="status"><option value="0">全部</option><option value="4">确定参加</option><option value="8">尚未答复</option></select></td>'+
						  	'</tr><tr class="dataRow">'+
							  	'<th class="label">发送内容：</th>'+
							  	'<td><textarea id="noticeTextarea"></textarea></td>'+
							'</tr></tbody></table></div>',
				mask : true,
				maskMode : false,
				buttons : '<span id="btnSendNotice" class="popBtns blueBtn callbackBtn">发送</span><span class="popBtns closeBtn">取消</span>',
				callback : function(){}
			});
			//验证+发送
			$('#btnSendNotice').on('click',function(){
				var status = $('#status').val();
				var noticeText = $('#noticeTextarea').val();
				if(!noticeText){
					$('#errMsgTip').text('发送的内容不能为空！').fadeIn('slow').css('display','block');
					return false;
				}else{
					$.post( sendNotice_url,{status:status,notice:noticeText,eventid:eventId},'json');
					$.closePopUp();
				}
			})
			
		});


       //答复菜单 (我要参加 / 确定参加 / 不参加 / 退出活动)
		$('a.doAnswer').click(function(){
			var statusValue = $(this).attr('ref');
			$.ajax({
				url : doAnswer_url,
				dataType : 'json',
				data : {eid:eventId,status:statusValue},
				type : 'post',
				success : function(result){
					setTimeout(function(){window.location.reload();},500);
				}
			});

		});
		

		//取消活动
		$('#cancelEvent').click(function(){
			$(this).popUp({
				width : 467,
				title : "取消活动？",
				content : '<div class="popConBox">你确定要取消此活动吗？此操作将不可撤销！</div>',
				mask : true,
				maskMode : false,
				buttons : '<span id="btnSendNotice" class="popBtns blueBtn callbackBtn">是</span><span class="popBtns closeBtn">否</span>',
				callback : function(){
					$.ajax({
						url : cancelEvent_url,
						dataType : 'json',
						data : {eventid:eventId},
						type : 'post',
						success : function(result){
							window.location.href = result.data.jump;
							//alert(result.data.jump);
						}
					});
					$.closePopUp();
					
				}
			});
		});
		
		//导出活动
		$('#eventGuest').find('div.guestFooter>a').eq(0).click(function(){
			$(this).popUp({
				width : 467,
				title : "导出活动",
				content : '<div class="popConBox"><div id="errMsgTip"></div>发送邮件到<input type="text" id="exportEvent" name="exportEvent" class="inputText" /></div>',
				mask : true,
				maskMode : false,
				buttons : '<span id="btnSendNotice" class="popBtns blueBtn callbackBtn">导出</span><span class="popBtns closeBtn">取消</span>',
				callback : function(){
					var exportText = $('#exportEvent').val();
					if(!exportText){
						$('#errMsgTip').text('邮件地址不能为空！').fadeIn('slow').css('display','block');
						return false;
					}else{
						$.post( exportEvent_url,{exportmail:exportText,eventid:eventId},'json');
						$.closePopUp();
					}
				}
			});
		});	
		
		
	}
	/*****************end:活动显示页********************/
});
