/**
 * Created on  2011-09-14 
 * @author: willian	
 * @desc: 消息模块
 * Update on 2012-2-5
 * @updateAuthor:tianxb
 */

$(document).ready(function(){
	//document.domain="duankou.com";
	var miscpath = CONFIG['misc_path'];
	var show_ajaxdata_url = mk_url("main/msg/show_ajaxdata");
	var edit_message_url  = mk_url("main/msg/edit_message");
	var save_message_url  = mk_url("main/msg/save_message");
	var add_msg_url       = mk_url("main/msg/add_msg");
	var hf_msg_url 		  = mk_url("main/msg/hf_msg");
	
	var list_msg_url	  = mk_url('main/msg/list_msg');

	
	var getAllFriends 	  = mk_url('main/msg/getfriends');
	//var getfriends_url    = mk_url("main/msg/getfriends");
	//var mypath = webpath;
	var del_message_url = mk_url("main/msg/del_pms");
	var del_messageSel_url= mk_url("main/msg/del_pms_item");
	var show_ajaxMsgInfo_url= "main/msg/msgdetail_more";
	var show_searchajaxMsgInfo_url= "main/msg/search_msg_more";
	//下面为前端测试路径，DK环境中要隐藏
 /*  mypath = webpath + "main/index.php/test/",
  		show_ajaxdata_url = "messagesSearch",
		edit_message_url = "messageReadStateChange", 
	    save_message_url = "messageArchive",
	    add_msg_url = "sendNewMessages",
	    getfriends_url = "getCompactedObject",
	    list_msg_url = "messagesSearch", 
		hf_msg_url = "replyNewMessages",
		getAllFriends = "get_all_friends";
    var uploadFile = 'main/application/views/uploadAttachFileHiddenIframe.html';*/
    /** 控制消息，站内信息搜索下拉框事件 **/
	//DKLayerHider.addHideItem('#searchFilterType');
	
	$('#searchFilterButton').click(function(){
		$(this).siblings().eq(0).show();
		$("body").one("click",function(){
			$("#searchFilterType").hide();
		})
		return false;
	});
	/**搜索(筛选)类型**/
	$('#searchFilterType').find('a').click(function(){	
		$("#sp_noread").hide();
		$("#allInfo").show();
		/** _messagesCateGory搜索类型 **/
		var _messagesCateGory = $(this).attr('rel');
		var searchQuery="";
		var color= "rgb(153, 153, 153)";
		if ($.browser.msie){
			color= "rgb(153,153,153)";
		}
		if(!$('#MessaginSearchQuery').attr("searchKey")&&$('#MessaginSearchQuery').val()!=$('#MessaginSearchQuery').attr("ref")&&$("#MessaginSearchQuery").css("color")!=color){
			$('#MessaginSearchQuery').attr("searchKey",$("#MessaginSearchQuery").val());
		}
		searchQuery=$('#MessaginSearchQuery').attr("searchKey")||"";
		$("#MessagingThreadlist").unscrollLoad();
		getMessageModuleMessages(_messagesCateGory,0,false,searchQuery);
		if(searchQuery.length>0){
			searchQuery+=" : ";
		}
		$('#MessaginSearchQuery').val(searchQuery+$(this).text());
		$('#searchFilterType').hide();
		$('#MessagingSearch').addClass('selected');
		$('#deleteFilter').show();
		$(this).parent().parent().find('li').removeClass('selected');
		$(this).parent().addClass('selected');
		
		switch(_messagesCateGory){
			case '0':
			$('#msgTitle h3').text('未读消息');
			break;
			case '2':
			$('#msgTitle h3').text('已存档消息');
			break;
			case '6':
			$('#msgTitle h3').text('已发送消息');
			break;
		}
		$('#MessagingThreadlist').children().remove();
		$('#moreMessagesButton').attr('rel',0);/** 重置加载更多,分页号为0 **/
		$('#MessaginSearchQuery').css("color","rgb(153, 153, 153)");
		
	});
	
	$('#deleteFilter').click(function(){
		$('#MessaginSearchQuery').attr("searchKey",'');
		$("#MessagingThreadlist").unscrollLoad();
		$('#MessaginSearchQuery').val('搜索消息');
		$('#MessagingSearch').removeClass('selected');
		$('#MessagingSearch').css("color","rgb(153, 153, 153)");
		$(this).hide();
		$('#MessagingThreadlist').children().remove();
		getMessageModuleMessages('',0,false);
		$('#moreMessagesButton').attr('rel',0);/** 重置加载更多,分页号为0 **/
		$('#searchFilterType').find('li').removeClass('selected');
		$("#sp_noread").show();
		$("#allInfo").hide();
		$("#msgTitle h3").html("消息");
	});
	$('#moreMessagesButton').click(function(){
		$("#MessagingThreadlist").unscrollLoad();
		var pageNum = ($(this).attr('rel'))*1;
		var _messagesCateGory = $('#searchFilterType').find('li.selected').find('a').attr('rel');
		if(!_messagesCateGory){_messagesCateGory = ''}
		getMessageModuleMessages(_messagesCateGory,pageNum,true);
	});
	var type=  querystring("type","#");
	if($.isNumeric(type))
		$('#searchFilterType').find('a[rel='+type+']').click();
	else
		getMessageModuleMessages('',1,true);
	$("#myform").bind("submit",function(){
		$('#MessagingThreadlist').children().remove();
		getMessageModuleMessages($('#searchFilterType li.selected').find('a').attr("rel"),1,false,$("#MessaginSearchQuery").val())
		return false;
	})

	//滚动加载更多
	function getMessageModuleMessages(_messagesCateGory,_pageNum,_isAppended,MessaginSearchQuery){
		if(!_messagesCateGory){_messagesCateGory = ''}
		if(!MessaginSearchQuery){MessaginSearchQuery = ''}
		if($("#MessagingThreadlist").scrollLoad)
		$("#MessagingThreadlist").scrollLoad({
				text:'<div id="moreMessagesButtonWrap"><div class="loadingAnimation"><img src="'+miscpath+'img/system/more_loading.gif" /></div><a id="moreMessagesButton" href="javascript:void(0);" rel="1" >查看更多</a> </div>',
				url:show_ajaxdata_url+"?messagesCateGory="+_messagesCateGory+"&MessaginSearchQuery="+encodeURIComponent(MessaginSearchQuery),
				success:function(data){
					var _str = '';
					if(data.isend==true){
						$("#MessagingThreadlist").unscrollLoad();
						$("#moreMessagesButtonWrap").hide();
					}
					if(data.status==1){
						for(var i=0,arrLenth=data.messages.length;i<arrLenth;i++){
							var currentMsgItem = data.messages[i];
							var _isread = currentMsgItem.state=='1' ? 'unread' : '';
							var _markTip = currentMsgItem.state=='1' ? '标记为已读' : '标记为未读';
							var _isArchive = _messagesCateGory == '2' ? '取消存档' : '存档';
							var _archiveClassName = _messagesCateGory == '2' ? 'archived' : '';
							var _isToUser = currentMsgItem.toUser ? '<img src="'+miscpath+'img/system/forward.gif"/>':"";
							_str += '<li class="threadRow '+ _isread +' '+ _archiveClassName +'" rel="'+ currentMsgItem.id +'"><table cellpadding="0" cellspacing="0" border="0"><tbody><tr><td class="threadMainCol"><a href="'+mk_url('main/msg/list_msg',{fromid:currentMsgItem.gid,lastId:currentMsgItem.id})+'" class="threadLink fl">';
							if(currentMsgItem['avatar'].length > 1){
								_str += '<span class="uiSplitPics clearfix"><span class="uiSplitPic leftThree"><img class="uiProfilePhoto uiProfilePhotoLarge img" src="'+ currentMsgItem['avatar'][0] +'" alt="" /></span><span class="uiSplitPic rTop"><img class="uiProfilePhoto uiProfilePhotoSmall img" src="'+ currentMsgItem['avatar'][1] +'" alt="" /></span><span class="uiSplitPic rBottom"><img class="uiProfilePhoto uiProfilePhotoSmall img" src="'+ currentMsgItem['avatar'][2] +'" alt="" /></span></span>';							
							}else{
								_str += '<img class="uiProfilePhoto fl" src="'+ currentMsgItem['avatar'][0] +'" alt="'+ currentMsgItem['name'] +'头像" />';
							}
							_str += '<span class="time">'+ currentMsgItem.dateline +'</span><span class="snippetWrapper"><strong class="author">'+ currentMsgItem.username +'</strong><span class="snippet">'+_isToUser+ currentMsgItem.m +'</span></span></a></td><td class="plm"><a href="javascript:void(0)" class="uiTooltip uiLinkSubtle"><i class="readState"></i><span class="uiTooltipWrap topTip"><span class="tipInner">'+ _markTip +'</span></span></a></td><td class="pls"><a href="javascript:void(0);" class="uiTooltip uiLinkSubtle"> <i class="delItem"></i> <span class="uiTooltipWrap topTip"> <span class="tipInner">'+_isArchive+'</span> </span> </a></td><td class="pls"><a href="javascript:void(0);" class="uiTooltip uiLinkSubtle"> <i class="deleteItem"></i> <span class="uiTooltipWrap topTip"> <span class="tipInner">删除</span> </span> </a></td></tr></tbody></table></li>';
						}
						$("#MessagingThreadlist").css({"min-height":525});
					}else
					{
						switch(_messagesCateGory){
							case '0':
								_str="<li class='noInfo'>您还没有未读消息</li>";
							break;
							case '2':
								_str="<li class='noInfo'>您还没有存档消息</li>";
							break;
							case '6':
								_str="<li class='noInfo'>您还没有已发送消息</li>";
							break;
							default:
								_str="<li class='noInfo'>您还没有消息</li>";
								break;
						}
						if (MessaginSearchQuery.length>0){
							_str="<li class='noInfo'>未搜索到相关消息，请重新输入关键字</li>";
						}
						$("#MessagingThreadlist").css({"min-height":225});
					}
					$('#MessagingThreadlist').append(_str);
				}
		});
	}
	/**更改消息状态**/
	$('i.readState').live('click',function(){
		var _li = $(this).parentsUntil('li.threadRow').parent().eq(0);
		var _readState = (_li.hasClass('unread'))? 0 : 1;
		var _messageID = _li.attr('rel');
		
		$.djax({
			url: edit_message_url,
			dataType:'json',
			data:({dataid:_messageID,readState:_readState}),
			success: function(data){
				data = data.data;
				if(data.state == 1){
					_readState == 0 ? ($(_li).removeClass('unread')):( $(_li).addClass('unread'));
					if (_readState == 0&&$("#searchFilterType li a[rel='0']").parent().hasClass("selected")){
						$(_li).remove();
						if ($("#MessagingThreadlist").children().length==0){
							$("#MessagingThreadlist").append("<li class='noInfo'>您还没有未读消息</li>");
						}
					}
				}else{
					alert(data.data);
				}
			},
			error:function(data){
				alert('系统错误');
			}
		});
	});

	$("#actionInfo li a").click(function(){
		var rel = parseInt( $(this).attr("rel") );
		var _messageID= $("[name='hd_lastId']").val();
		switch(rel){
			case 1://未读取
				$.djax({
					url: edit_message_url,
					dataType:'json',
					data:({dataid:_messageID,readState:0}),
					success: function(data){
						data = data.data;
						if(data.state == 1){
							location.href= mk_url('main/msg/show_message');
						}else{
							alert(data.data);
						}
					},
					error:function(data){
						alert('系统错误');
					}
				});
				break;
			case 2://存档				
				$.djax({
					url:save_message_url,
					dataType:'json',
					data:({dataid:_messageID}),
					success: function(data){
						data = data.data;
						if(data.state == 1){
							location.href= mk_url('main/msg/show_message');
						}else{
							alert(data.data);
						}
					}
				});
				break;
			case 3://删除消息
				$("#msgInfo_list>li,#searchmsgInfo_list>li").live('click',selectItem);
				$("#actionDelete").show();
				$("input[name='chk_msg']","#messagingMessages").show();
				$("input.closeBtn").one("click",function(){
					$("#actionDelete").hide();
					$("input[name='chk_msg']","#messagingMessages").hide();
					$("#msgInfo_list>li,#searchmsgInfo_list>li").unbind('click',selectItem);
				})
				break;
		}
	});
	/**消息存档**/
	$('i.delItem').live('click',function(){
		var _li = $(this).parentsUntil('li.threadRow').parent().eq(0);		
		var _messageID = _li.attr('rel');
		$.djax({
			url:save_message_url,
			dataType:'json',
			data:({dataid:_messageID}),
			success: function(data){				
				data = data.data;
				if(data.state == 1){
					$(_li).remove();
					if ($("#MessagingThreadlist").children().length==0){
						var _str="";
						var _messagesCateGory = $('#searchFilterType').find('li.selected').find('a').attr('rel');
						switch(_messagesCateGory){
							case '0':
								_str="<li class='noInfo'>您还没有未读消息</li>";
							break;
							case '2':
								_str="<li class='noInfo'>您还没有存档消息</li>";
							break;
							case '6':
								_str="<li class='noInfo'>您还没有已发送消息</li>";
							break;
							default:
								_str="<li class='noInfo'>您还没有消息</li>";
								break;
						}
						$("#MessagingThreadlist").append(_str);
					}
				}else{
					alert(data.data);
				}
			}
		});
	});
	//删除全部
	$("#btn_delAll").click(function(){
		var _messageID=$("#hd_dataid").first().val();
		$(this).popUp({
			width:450,
			title:'删除提示',
			content: '<div style="height: 100px;line-height: 100px;text-align: center;font-size:14px;">是否确定删除?</div>',
			buttons:'<span class="popBtns blueBtn callbackBtn">删除</span><span class="popBtns closeBtn">取消</span>',
			mask:true,
			maskMode:true,
			callback:function(){	
				$.djax({
					url: del_messageSel_url,
					dataType:'json',
					data:({dataid:"111111",id:_messageID}),
					success: function(data){
						data = data.data;
						if(data.state == 1){
							location.href= mk_url("main/msg/show_message" );
						}else{
							alert(data.data);
						}
					}
				});
			}
		});
	});
	//删除选择
	$("#btn_delSel").click(function(){
		var _messageID=[];
		var arr = $("[name='chk_msg']");
		for (var l=arr.length;l-- ; )
		{
			var t = $(arr[l]);
			if(t.attr("checked")){
				_messageID.push(t.val());
			}
		}
		if(_messageID.length<=0){
			alert("请选择要删除的消息!");
			return false;
		}
		$(this).popUp({
			width:450,
			title:'删除提示',
			content: '<div style="height: 100px;line-height: 100px;text-align: center;font-size:14px;">是否确定删除?</div>',
			buttons:'<span class="popBtns blueBtn callbackBtn">删除</span><span class="popBtns closeBtn">取消</span>',
			mask:true,
			maskMode:true,
			callback:function(){	
				$.djax({
					url: del_messageSel_url,
					dataType:'json',
					data:({dataid:_messageID.join(',')}),
					success: function(data){
						data = data.data;
						if(data.state == 1){
							location.href= mk_url("main/msg/show_message");
						}else{
							alert(data.data);
						}
					}
				});
			}
		});
	});
	/**删除消息**/
	$('i.deleteItem').live('click',function(){
		var _self= $(this);
		$(this).popUp({
			width:450,
			title:'删除提示',
			content: '<div style="height: 100px;line-height: 100px;text-align: center;font-size:14px;">此条信息将永久删除!</div>',
			buttons:'<span class="popBtns blueBtn callbackBtn">删除</span><span class="popBtns closeBtn">取消</span>',
			mask:true,
			maskMode:true,
			callback:function(){	
				var _li = _self.parentsUntil('li.threadRow').parent().eq(0);				
				var _messageID = _li.attr('rel');
				$.djax({
					url: del_message_url,
					dataType:'json',
					data:({dataid:_messageID}),
					success: function(data){
						data = data.data;
						if(data.state == 1){
							$(_li).remove();							
							if ($("#MessagingThreadlist").children().length==0){
								var _str="";
								var _messagesCateGory = $('#searchFilterType').find('li.selected').find('a').attr('rel');
								switch(_messagesCateGory){
									case '0':
										_str="<li class='noInfo'>您还没有未读消息</li>";
									break;
									case '2':
										_str="<li class='noInfo'>您还没有存档消息</li>";
									break;
									case '6':
										_str="<li class='noInfo'>您还没有已发送消息</li>";
									break;
									default:
										_str="<li class='noInfo'>您还没有消息</li>";
										break;
								}
								$("#MessagingThreadlist").append(_str);
							}
						}else{
							alert(data.data);
						}
					}
				});	
				$.closePopUp();
			}
		});
	})

	/**弹出发送新信息框**/
	$('#newMessage,#jewelSendNewMessage').click(function(){
		var _this = this;
		var _str = '<div id="newMessagesTable"><table cellpadding="0" cellspacing="0" border="0"><tbody><tr><th valign="top" style="padding:8px 0">收件人：</th><td style="padding:8px 0"><div class="receivePeopleWrap"><div class="tokenarea" id="tokenarea"><i id="msgTokenareaList" style="font-style:normal;"></i><input class="toPeopleInput" id="toPeopleInput" type="text" title="请输入一个你朋友的名字" /></div><div class="compactPeople" id="compactPeople"><ul></ul></div></div></td></tr><tr><th valign="top">消息：</th><td><div id="newMessageContentWrap"><textarea id="newMessageContent" maxlength="140"></textarea></div></td></tr><tr><th valign="top">&nbsp;</th><td><ul id="attachApps"><li><a class="attachOptions attachment" id="attachFileButton" href="javascript:void(0)"></a></li><li></li></ul></td></tr><tr><th valign="top">&nbsp;</th><td><div id="attachedFilesWrap"><ul id="attachedFiles"></ul></div></td></tr></tbody></table></div>';
		$(this).popUp({
			width:450,
			title:'新消息!',
			content: _str,
			buttons:'<span class="popBtns blueBtn callbackBtn" id="msgSend">发送</span><span class="popBtns closeBtn">取消</span>',
			mask:true,
			maskMode:true,
			callback:function(e){	
				//sendNewMessages(e);
			}
		});
		$("#popUp").find("#msgSend").click(sendNewMessages);
		$("#sencCancel").click(function(e){
			var _newMessageContent = $('#newMessageContent').val();
			if($.trim(_newMessageContent).length>0){				
				$("body").subPopUp({
						width:357,
						title:"取消发送",
						content:"<strong style='display:block; padding:15px; em-size:14px;'>你确定要取消当前编辑的消息么？取消后该内容不可恢复。</strong>",
						buttons:'<span class="popBtns blueBtn closeBtn" id="sp_delInfo">删除信息</span><span class="popBtns closeBtn">继续编辑</span>',
						mask:true,
						maskMode:false
				});
				$("#sp_delInfo").click(function(){
					$.closePopUp();
				});
				return false;
			}else
				$.closePopUp();
		});
		/*
		$.djax({
			url:mypath + getAllFriends,
			dataType:'json',
			data:({}),
			success: function(data){
				if(data.status == 1){
					// 实时匹配用户输入 
					$('#toPeopleInput').searcher({
						inputWrap:'div.receivePeopleWrap',
						tokenArea:'#msgTokenareaList',
						url:mypath + getAllFriends,
						compactArea:'#compactPeople',
						staticData: false,
						enableGetData:true
					});
				}else{
					alert(data.data);
				}
			}
		});
	*/
		// 实时匹配用户输入 
		$('#toPeopleInput').searcher({
			inputWrap:'div.receivePeopleWrap',
			tokenArea:'#msgTokenareaList',
			url: getAllFriends,
			compactArea:'#compactPeople',
			staticData: false,
			enableGetData:true,
			dataType:"jsonp",
			type:"GET"
		});
		$('#toPeopleInput').focus();
		$('#attachFileButton').uploader({
			inputFileId:'FileDataInput',
			formId:'jsUploaderForm',
			url:mk_url('main/msg/message_upload'),
			callback:'sendAttachedFileComplete'
		});
		$('#attachFileButton').attr("title","添加附件");
	});

	$(".callbackBtn").live("click",function(e){
		//sendNewMessages(e);
	});		
	function sendNewMessages(){
		var _this = this;
		if ($(_this).hasClass("disable")){
			return;
		}
		/*************自动匹配**/

		var _userids = '';
		var _fileNames = '';
		var _newMessageContent = $('#newMessageContent').val();
		var _attachedFileName = $('#attachedFiles').find('span.attachedFileName');
		var spans = $('#msgTokenareaList').find('span');
		for(var i=0,spansLength = spans.length;i < spansLength; i++){
			_userids += $(spans[i]).attr('rel');
			if(i<spansLength - 1){
				_userids += ',';
			}
		}
		
		for(var i=0,_attachedFileNameLength=_attachedFileName.length; i < _attachedFileNameLength; i++){
			_fileNames += $(_attachedFileName[i]).attr('rel');
			if(i<_attachedFileNameLength - 1){
				_fileNames += ',';
			}
		}
		if(_userids === ''){
			return false;
		}
		if(_userids.split(",").length>10){
			top.$("body").subPopUp({
					width:357,
					title:"提示",
					content:"<strong style='display:block; padding:15px; em-size:14px;'>添加收件人不得超过10位。</strong>",
					buttons:'<span class="popBtns blueBtn closeBtn">确定</span><span class="popBtns closeBtn">取消</span>',
					mask:true,
					maskMode:false
			});
			return false;
		}
		if($.trim(_newMessageContent) === '' && _fileNames === ''){
			return false;
		}
		if ($.trim(_newMessageContent) === ''){
			alert("消息内容不能为空!");
			return false;
		}
				var _newMessageContent = $('#newMessageContent').val();
		//var _userids=$("#toPeopleInput").val();
		$(_this).addClass("disable");
		$.djax({
			url: add_msg_url,
			dataType:'json',
			data:({userids:_userids,newMessageContent:_newMessageContent,fileNames:_fileNames}),
			success: function(data){
				data = data.data ;
				if(data.state == 1){
					if($(_this).attr('id') == 'jewelSendNewMessage'){
						$(this).popUp({
							width:450,
							title:'提示!',
							content: '<div style="padding:10px">消息发送成功!</div>',
							buttons:'',
							mask:true,
							maskMode:true,
							callback:function(){}
						});
						setTimeout(function(){
							$.closePopUp();
							window.location.href = list_msg_url+"?fromid="+data.locationID.gid+"&lastId=" + data.locationID.msgid;
						},1000);
					}else if( data.locationID){
						$.closePopUp();
						window.location.href = list_msg_url+"?fromid="+data.locationID.gid+"&lastId=" + data.locationID.msgid;
					}else{
						alert("发送失败!");
					}
				}else{
					alert(data.data);
				}
			}
		}).done(function(){
				$(_this).removeClass("disable");
			});
	}
	
	/**************** 附件上传 ***********/
	if($('#attachFileButtonForDetailPage').uploader)
	$('#attachFileButtonForDetailPage').uploader({
		inputFileId:'FileDataInputForDetailPage',
		formId:'jsUploaderFormForDetailPage',
		url:mk_url('main/msg/message_upload'),
		callback:'sendAttachedFileCompleteForDetailPage'
	});
	$('#attachFileButtonForDetailPage').attr("title","添加附件");
	/** 消息详细页上传一个附件后callback **/
	window.sendAttachedFileCompleteForDetailPage = function(data){
		sendAttachedFileCallback(data, '#attachedFilesForDetailPage');
	};
	
	/** 上传一个附件后callback **/
	window.sendAttachedFileComplete = function(data){
		sendAttachedFileCallback(data, '#attachedFiles');
	};
	/** 分别为弹出层内的附件添加与消息详细页内的附件添加做处理 **/
	function sendAttachedFileCallback(data, _attachedFileContainer){
		var data = eval('('+data+')');
		var _id = data.id,_fileName = data['filename'], _originalFileName = data.fileOriginalName, _fileSize = data.fileSize;
		var _fileType = _fileName.substr(_fileName.lastIndexOf('.'));
		var fileIconClassName = '';
		switch (_fileType) {
			case ".zip":
			case ".rar":
				fileIconClassName = 'achiveFile';
				break;
			case ".png":
			case ".jpg":
			case ".gif":
				fileIconClassName = 'picFile';
				break;
			default:
				fileIconClassName = 'footballFile';
				break;
		}
		var _str = '<li>';
		_str += '<i class="attachedFileIcon ' + fileIconClassName + '"></i><span class="attachedFileName" rel="' + _id + '">' + _originalFileName + '(' + _fileSize + 'KB)</span><span class="deleteAttachment"></span>';
		_str += '</li>';
		$(_attachedFileContainer).append(_str);
	}
	
	$('span.deleteAttachment').live('click', function(){
		$(this).parent().remove();
	});
	
	/***************************************消息详细内页*********************************/
	/** 站内信息操作下拉框事件 **/
	//DKLayerHider.addHideItem('#operatMessagesPanel');
	$('#operatMessages').click(function(){
		$('#operatMessagesPanel').show();
		return false;
	});
	
	/** 回复消息 **/
	$('#responseButton').click(function(){
		
		replyMessages();
		
	});
	
	/** 设置回车回复消息 **/
	$('#checkForEnter').click(function(){
		if ($(this).attr('checked')) {
			$('#responseButton').hide();
		}else{
			$('#responseButton').show();
		}
	});
		
	/** 回车回复消息 **/
	$('#messagingComposerBodyText').bind('keydown', function(e){
		var keyCode = e.keyCode;
		if (keyCode == 13 && $('#checkForEnter').attr('checked')) {//enter
			replyMessages();
		}
	});
	
	/** 回消息 **/
	function replyMessages(){
		if ($("#responseButton").hasClass("disable")){
			return ;
		}
		var _msg = $.trim($('#messagingComposerBodyText').val());
		if(_msg == '' || _msg == '回复') return false;
		var _targetUserID = $('#messagesHeadline').val();
		var _fileNames = '';
		var _newMessage = $('#messagingComposerBodyText').val();
		var _attachedFileName = $('#attachedFilesForDetailPage').find('span.attachedFileName');
		var _gid=$('#messagegid').val();
		
		for(var i=0,_attachedFileNameLength=_attachedFileName.length; i < _attachedFileNameLength; i++){
			_fileNames += $(_attachedFileName[i]).attr('rel');
			if(i<_attachedFileNameLength - 1){
				_fileNames += ',';
			}
		}
		$("#responseButton").addClass("disable");
		$.djax({
			type:"POST",
			url:hf_msg_url,
			dataType:'json',
			data:({targetUserID:_targetUserID,newMessage:_newMessage,attachedFileName:_fileNames,gid:_gid}),
			success: function(data){
				data = data.data;
				if(data.state == 1){
					$('#messagingComposerBodyText').val('');
					$('#attachedFilesForDetailPage').children().remove();
					
					var _str = '<li class="messagingMessage"><div class="massaginMain"><div class="uiImageBlock clearfix"><div class="hints"><abbr class="timestamp">'+ data.replytime +'</abbr><a class="messageCategory uiTooltip" href="javascript:void(0)"><i class="uiTooltip bp_messageFrom"></i><span class="uiTooltipWrap rightTip"><span class="tipInner">'+ data.replyfrom +'</span></span></a></div><a class="uiImageBlockImage" href="#" ref="'+ data.userid +'"><img alt="'+ data.username +'头像" src="'+ data.avatar +'" class="uiProfilePhoto" /></a><div class="uiImageBlockContent"><a href="#"><strong class="author">'+ data.username +'</strong></a><ul class="contentlist"><li><div class="content">'+ data.content +'</div>';
					
					if(data.files.length > 0){
						_str += '<div class="attachments">';
						_str += '<ul class="uiList files">';
						for(var j=0; j<data.files.length;j++){
							_str += '<li>';
							if (data.files[j]['is_image'] == '1')/**附件为图片类型**/ {
								_str += '<a href="'+data.files[j].downurl+'" title="' + data.files[j]['client_name'] + '" target="_blank" class="attachmentTit"><i class="uiIconP icons1 bp_photo"></i>' + data.files[j]['client_name'] + '</a><div class="previewWrapper"><a href="'+data.files[j].url+'" target="_blank"><img src="' +data.files[j].url+'" class="preview" height="78" alt="' + data.files[j]['client_name'] + '" /></a></div>';
							}else if (data.files[j]['is_image'] == '0') {/**附件为文件类型**/
								_str += '<a href="'+data.files[j].downurl+'" title="' + data.files[j]['client_name'] + '" target="_blank" class="attachmentTit"><i class="uiIconP attachedFileIcon bp_picFile"></i>' + data.files[j]['client_name'] + '</a>';
							}
							_str += '</li>';
						}
						
						_str += '</ul></div>';
					}
					_str += '</li></ul></div></div></div></li>';
					
					$('#messagingMessages > ul').append(_str);
				}else{
					alert(data.data);
				}
			}
		}).done(function(){
				$("#responseButton").removeClass("disable");
			});
	}
	var t=null;
	function pos_msg(){
	  clearTimeout(t);
	  t = setTimeout(function(){
	/** 回复框定位 **/
	if($('#messaginShelf').length){
		var bodyTop = document.documentElement.scrollTop + document.body.scrollTop;
		
		var content_PosOld = parseInt( $('#messaginShelf').offset().top);
		clearTimeout(t)
		var content_Pos=content_PosOld+$('#messaginShelf').height();
		if(bodyTop + $(window).height() < content_PosOld){
			$('#messaginShelf').addClass('fixedScrolling');
		}
		
		$(window).scroll(function(){
			bodyTop = document.documentElement.scrollTop + document.body.scrollTop;
			if(bodyTop + $(window).height() < content_Pos){
				$('#messaginShelf').addClass('fixedScrolling');
			} 
			if($('#messaginShelf').offset().top+30 >= content_PosOld){
				$('#messaginShelf').removeClass('fixedScrolling');
			}
			content_Pos = parseInt( $('#messaginShelf').offset().top)+$('#messaginShelf').height();
		});
	}},25);
	}
	pos_msg();
	/** 为body加上特殊样式 **/
	if($('#messageDetailPage')[0]){
		$(document.body).addClass('fixedBody');
	}
	
	/*****消息内容限制字数*****/
	$('#newMessageContent,#messagingComposerBodyText').live('keyup change',function(){
		limitStrNum(this);
	});
	/*******关闭设置*******/
	$("i.deleteItemSet").click(function(){
		$(this).closest("div.msg_Head").hide();	
	});
	$("#noRead").click(function(){		
		$("#msgInfo_list").unscrollLoad();
		$('#searchFilterType').find('a[rel=0]').click();
	});
	$("#yesSave").click(function(){
		$("#msgInfo_list").unscrollLoad();
		$('#searchFilterType').find('a[rel=2]').click();
	});
	$("#allInfo").click(function(){
		$("#msgInfo_list").unscrollLoad();
		$('#deleteFilter').triggerHandler("click");
	})
	function querystring(){
		var Url = location.href;
		var u='',g="",StrBack="";
		if (arguments[arguments.length-1]=="#")
		{
			u=Url.split("#");
		}else
			u=Url.split("?");
		if(u.length==1)
			g="";
		else
			g= u[1];
		if(g!=""){
			var gg =g.split("&");
			var m=gg.length;
			var str = arguments[0]+"=";
			for(var i = 0;i<m;i++){
				if(gg[i].indexOf(str) ==0 ){
					StrBack = gg[i].replace(str,"");
					break;
				}
			}
		}
		return StrBack;
	}
	var type= querystring("type","#");
	if ( $.isNumeric(type) ){
		type = parseInt(type);
		switch(type){
			case 0:{//未读消息
				$("#msgInfo .userActions #sp_btnBackUrl a").html("未读消息");
			}break;
			case 2:{
				$("#msgInfo .userActions #sp_btnBackUrl a").html("存档消息");
				$("#actionInfo a[rel='2']").html('取消存档');
			}break;
			case 6:{
				$("#msgInfo .userActions #sp_btnBackUrl a").html("已发送消息");
			}break;
		}
		$("#msgInfo .userActions #sp_btnBackUrl a").click(function(){
			var url = $(this).attr("href");
			location.href= url +"#type="+ type;
			return false;
		});
	}
	$("#MessagingMainContent").delegate(".threadMainCol a.threadLink","click",function(){
		var url = $(this).attr("href");
		var type = $("#searchFilterType li").filter(".selected").find("[rel]").attr("rel")||"";
		location.href=url+"#type="+type;
		return false;
	});
	//$("#msgInfo_list>li").bind('click',selectItem);
	function selectItem(){
		$("input.chk_msg",this).attr("checked",!$("input.chk_msg",this).attr("checked"));
		$(this).toggleClass("selected");		
		if($(this).hasClass("selected")){
			$("input.chk_msg",this).attr("checked",true);
		}else
			$("input.chk_msg",this).attr("checked",false);
		var len=$("#msgInfo_list > li input.chk_msg:checked,#searchmsgInfo_list > li input.chk_msg:checked").length;
		if(len==0){
			$("#btn_delSel").parent().addClass("disabled").addClass("disable");
			$("#btn_delSel").attr("disabled","true");
		}else{
			$("#btn_delSel").parent().removeClass("disabled").removeClass("disable");
			$("#btn_delSel").removeAttr("disabled");
		}		
	}
	function formatData(data,dom){
		if (!data){
			return;
		}
		var _str = '';
		if(data.isend==true){
			$("#msgInfo_list").unscrollLoad();
			$("#moreMessagesButtonWrap").hide();
		}
			if (data.messages.length==0||data.status==0){
				$("#dv_allInfo").hide();
			}else{
				$("#dv_allInfo").show();
			}
		if(data.status==1){
			var display = $(dom).find(":checkbox:visible").size()>0 ? "inline;":"none;";
			for(var i=0,arrLenth=data.messages.length;i<arrLenth;i++){
				var item =data.messages[i];
				_str+='<li class="messagingMessage">\
			  <div class="massaginMain">\
				<input type="checkbox" name="chk_msg" class="chk_msg hide" value="'+item.id+'" style="display:'+display+'"/>\
				<div class="uiImageBlock clearfix">\
				  <div class="timeAndFrom"> <abbr class="timestamp">'+item.dateline+'</abbr> <a class="messageCategory uiTooltip" href="javascript:void(0)"> <i class="bp_messageFrom png"></i> <span class="uiTooltipWrap leftTip"> <span class="tipInner">来自聊天室</span> </span> </a> </div>\
				  <a class="uiImageBlockImage" href="'+item.userpath+'"> <img alt="'+item.username+'头像" src="'+item.avatarurl+'" class="uiProfilePhoto" /> </a>\
				  <div class="uiImageBlockContent"> <a href="'+item.userpath+'"><strong class="author">'+item.username+'</strong></a>\
					<ul class="contentlist">\
				  ';
				   if (item.message.length>0)
				   {
					_str+='<li>\
							<div class="content">'+item.message+'</div>\
						</li>\
							';
				   }
					
					if (item.files[0])
					{
						_str+='\
							<li>\
							<div class="attachments">\
							<ul class="uiList files">\
							';
								for (var j =0;j<item.files.length ;j++ )
								{
									var f = item.files[j];
									if(f.is_image==0){
										_str+='\
											<li><a\
												href="'+f.downurl+'"\
												class="attachmentTit" title="'+f.client_name+'"><i\
												class="uiIconP attachedFileIcon bp_picFile"></i>'+f.client_name+'</a></li>\
											';
									}else{
										_str +='\
											<li><a\
												href="'+f.downurl+'"\
												class="attachmentTit" target="_blank" title="'+f.client_name+'"><i\
												class="uiIconP icons1 bp_photo"></i>'+f.client_name+'</a>\
											<div class="previewWrapper"><a\
												href="'+f.url+'"\
												target="_blank"> <img class="preview" height="78"\
												src="'+f.url+'">\
											</a></div>\
											</li>\
											';
									}
								}
							_str+='\
									</ul>\
									</div>\
									</li>\
									';
					}
						_str+='\
				</ul>\
				  </div>\
				</div>\
			  </div>\
			</li>';
			}
		}else
		{
			_str="<li class='noInfo'>未搜索到相关信息，请重新输入关键字</li>";
		}
		$(dom).append(_str);
		pos_msg();
	}
	//serach
	$("#msgTitle form").submit(function(){
		var val = $.trim( $("#searchkey",this).val() ) ;
		if (val == "在此对话框中搜索" || val=="" ){
			if ( $.trim($("#sp_btnBackUrl").text()) == "消息详情" ){
				location.href= $("#sp_btnBackUrl a").attr("href");
			}
			return false;
		}
	});

	if($("#searchmsgInfo_list").scrollLoad)
		$("#searchmsgInfo_list").scrollLoad({
				text:'<div id="moreMessagesButtonWrap"><div class="loadingAnimation"><img src="'+miscpath+'img/system/more_loading.gif" /></div><a id="moreMessagesButton" href="javascript:void(0);" rel="1" >查看更多</a> </div>',
				url: mk_url(show_searchajaxMsgInfo_url ,{gid:$("[name='gid']").val(),searchkey:encodeURIComponent($("[name='searchkey']").val())}),
				success:function (data){
					formatData(data,$("#searchmsgInfo_list"));
				}
		});

	if($("#msgInfo_list").scrollLoad)
		$("#msgInfo_list").scrollLoad({
				text:'<div id="moreMessagesButtonWrap"><div class="loadingAnimation"><img src="'+miscpath+'img/system/more_loading.gif" /></div><a id="moreMessagesButton" href="javascript:void(0);" rel="1" >查看更多</a> </div>',
				url: mk_url(show_ajaxMsgInfo_url ,{dataid:$("#hd_dataid").val()}),
				success:function (data){
					formatData(data,$("#msgInfo_list"));
				}
		});
});