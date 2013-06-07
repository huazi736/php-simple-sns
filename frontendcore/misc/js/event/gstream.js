/**
 * @author:    wangwb
 * @created:   2012/7/13
 * @version:   v1.0
 * @desc:      群组_发表框和信息流
 */
 
$(document).ready(function() {

/****** 发表框 *******/
	var gid = $('#group_id').val();	
	//地址
	var getWebImg_url = mk_url("gevent/event/replyImg",{gid:gid}),
		distributeMsg_url = mk_url("gevent/event/replyMsg",{gid:gid}),
		distributeVideo_url   = mk_url("gevent/event/replyVideo",{gid:gid}),
		delTopic_url = mk_url("gevent/event/replydel",{gid:gid});
		
	var eventId = $('#eventDetail').attr('eventid');
	var nowUser = $('#hd_UID').val();	//当前用户ID
	var returnData;
			
	//切换信息发布类型,重置相应值
	$("#composerAttachments > li").click(function(){
	  $('#distributeInfoBody').find('.footer').hide();
	  var liArr = $("#composerAttachments > li");
	  var divArr = $("#distributeInfoBody").find('.distributeInfo');
	  for(var i=divArr.length-1;i>=0;i--){
	  	$(liArr[i]).removeClass('act');
		$(divArr[i]).addClass('hideEle');
	  	if($(this).attr('ref') == i){
			$(liArr[i]).addClass('act');
			$(divArr[i]).removeClass('hideEle');
			switch(i){
				case 0:
				$('#distributeInfoBody .pointUp').css('marginLeft','22px');
				$('#currentComposerAttachment').val(0);
				break;
				case 1:
				$('#distributeInfoBody .pointUp').css('marginLeft','80px');
				$('#photoFileOption').hide();
				$('#uploadPhotoPanel').hide();
				$('#photoSnapshotPanel').hide();
				$('#photoUploadWay').show();
				$('#currentComposerAttachment').val(1);
				break;
				case 2:
				$('#distributeInfoBody .pointUp').css('marginLeft','140px');
				$('#videoFileOption').hide();
				$('#uploadVideoPanel').hide();
				$('#recordVideoPanel').hide();
				$('#videoUploadWay').show();
				$('#currentComposerAttachment').val(2);
				break;
			}
		}
	  }
	});
	
//三个按扭点击
	
	//'发表'按扭外部div
	var $distributeInfoBodyFooter = $('#distributeInfoBody').find('div.footer');
	
	//点击留言输入框
	$('#distributeMsg').find('textarea.shareInfoCont').click(function(){
		if( $(this).val() == '写点什么吧' ) $(this).val('');
		$distributeInfoBodyFooter.show();
	}).focusout(function(){
		if( $(this).val() == '' ) $(this).val('写点什么吧');
	});
	
	//发布留言字数限制,自动增高
	/*$('#distributeMsg textarea.shareInfoCont').bind('keyup change',function(){
		limitStrNum(this);
	}).textareaHeight();

	$('#distributeMsg textarea.shareInfoCont').bind('keyup change',function(){
		limitStrNum(this);
	}); */
	
	
	//点击上传图片
	$('#upoadPhotoFromLocal').click(function(){
		$('#photoUploadWay').hide();
		$('#uploadPhotoPanel').show();
		$('#attachPhotoIntroduce').show();
		$('#photoFileOption').show();
		$distributeInfoBodyFooter.show();
	});
	
	//点击上传视频
	$('#upoadVideoFromLocal').click(function(){
		$('#videoUploadWay').hide();
		$('#uploadVideoPanel').show();
		$('#attachVideoIntroduce').show();
		$('#videoFileOption').show();
		$distributeInfoBodyFooter.show();
	});
	
	//取消视频上传
	$("#btn_cancelUpload").click(function(){
		$.ajax({
			type: "POST",
			url: cancelUploadURL,
			dataType:'jsonp',
			data:({progress_key:UNID}),
			success: function(data){
				if(data.status == 1){
					$("#theframe").show();
					$("#theframe")[0].src="softupload?id="+UNID;
					$(".uploadProgress").hide();
					$("#link").val("");
					$("#uploadTips").show();
					$('#videoDescriptions').hide();
					$('#uploadState').hide();
				}
			}
		});
	});
	
	//上传视频
	if(document.getElementById("uploadVideoFlashWrap"))
	{
		$("#uploadVideoFlash").uploadify({
			"uploader":CONFIG['misc_path']+"flash/uploadify.swf",
			"script":mk_url("video/video/index"),
			"scriptData":{
							'c' : 'videoapi',
							'm' : 'event_video',
			                'sessionid' : $('#hd_sessionid').val()
							},
			"method":"GET",
			'displayData':'percentage',
			'fileExt':'*.flv;*.3g2;*.3gp;*.3gpp;*.asf;*.avi;*.dat;*.divx;*.dv;*.f4v;*.flv;*.m2ts;*.m4v;*.mkv;*.mod;*.mov;*.mp4;*.mpe;*.mpeg;*.mpeg4;*.mpg;*.mts;*.nsv;*.ogm;*.ogv;*.qt;*.tod;*.ts;*.vob;*.wmv;',
			"fileDesc":"*.*",
			"width":62,
			"height":25,
			"queueID":"queueID",
			"auto":true,
			"buttonText":"SELECT FILE",
			"buttonImg":CONFIG['misc_path']+"img/system/brower.gif",
			"cancelImg":CONFIG['misc_path']+"img/system/icon_close_03.png",
			"fileDataName":"Filedata",
			'sizeLimit'   : 1024*1024*100,//100m
			"onProgress":function(e,id,obj,data){
			},
			"onSelectOnce":function(){
				$(".flashContent object").height(0).css("border","none");//隐藏选择图片按钮
				$(".flashContent").height(0).css({"border":"none","padding":"0px"});
				$(".flashContent div").hide();
			},
			"onComplete":function(e,queueID,fileObj,response,data){
				videoUploadComplete(response);
				$(".flashContent").hide();
				$("#uploadTips").hide();
				$('#videoDescriptions').show();
			},
			"onCancel":function(){ //取消方法
				$(".flashContent object").height(25);//隐藏选择图片按钮
				$(".flashContent").css({"border":"1px solid #CCCCCC","padding":"10px 10px"});
				$(".flashContent div").show();
			},
			"onError":function(e,qid,fo,eo){
				//console.log(eo);
			}
		});
	}

	// 点击'发表'按扭，发布相应内容
	$('#distributeButton').click(function(){
		var currentDistributeType = $('#currentComposerAttachment').val();
		switch(currentDistributeType){
			case '0'://发表留言
				//console.log('msg');
				distributeMessage(this);

			break
			case '1'://发表图片
				//console.log('pic');
				var tokenValue = $('div#shareDestinationObjects').attr('value');
				$('#tokenShareDestinations').val(tokenValue);
				if($('#uploadPhotoButton').val() == ''){
					return false;
				}else{
					var showWheLoadingObj = $('#distributeInfoBody').find('div.showWhenLoading');
					showAnimation(this,showWheLoadingObj);
					if($.trim($('#attachPhotoIntroduce').val()) == '给这张照片做些说明吧'){
						$('#attachPhotoIntroduce').val('加了一张新照片');
					}
					$('#uploadPhotoForm').submit();
				}
			break
			case '2'://发表视频
				//console.log('video');
				distributeVideo(this);
			break
		}
	});

	
	//发布留言
	function distributeMessage(_this){
		//alert(0);
		var _message = $('#distributeMsg').find('textarea.shareInfoCont').val();
		if(_message == '写点什么吧'){
			//default value;
			//alert(1);
		}else{
			//alert(2);
			var tokenValue = $('div#shareDestinationObjects').attr('value');
			$('#tokenShareDestinations').val(tokenValue);
			var _imgs = $('#distributeUiThumbPagerThumbs').find('img');
			var _whichPic = '';
			var _distributeSiteURL = '';
			var _distributeSiteTitle = '';
			var _distributeSiteDesc = '';
			var _style = 'content';
			var showWheLoadingObj = $('#distributeInfoBody').find('div.showWhenLoading');
			$.ajax({
				type:"POST",
				url: distributeMsg_url,
				dataType:'jsonp',
				data: { message:_message,eventid:eventId},
				beforeSend: function(XMLHttpRequest){
					showAnimation(_this,showWheLoadingObj);
				},
				success: function(result){
					if(result.status == 1){
						var data = result.data;
						inserData( data,true,'app' );
						//添加滚动条，显示更多
						if($('#infoArea').find('div.infoList').size()>0){
							$('#popUp').css('width','643px').find('div.contentBox').addClass('viewMore');
						}
						$('#tokenareaList').empty();
						$('#distributeMsg').find('textarea.shareInfoCont').val('写点什么吧');
						$('#tokenShareDestinations').val('');
						$('#uiThumbPagerControlButtons').find('span.uiThumbPagerControlTotalNumber').text(0);
						$('#linkedResponseMessage').find('strong.uiShareStageTitle').find('a.inlineEdit').text('');
						$('#linkedResponseMessage').find('div.uiShareStageSubtitle').text('');
						$('#linkedResponseMessage').find('p.uiShareStageContentText').find('a.inlineEdit').text('');
						$('span.uiThumbPagerControlTotalNumber').text(0);
						$('#distributeLinked').hide();
						$('#distributeUiThumbPagerThumbs').empty();
					}else{
						$(this).popUp({
							width:450,
							title:'提示!',
							content: '<div style="padding:10px;">发布状态发生错误请稍后重试!</div><div style="padding:0 5px 10px;">'+ result.data +'</div>',
							buttons:'',
							mask:true,
							maskMode:true,
							callback:function(){}
						});
						setTimeout(function(){
							$.closePopUp();
						},2000);
					}
					hideAnimation(_this,showWheLoadingObj);
				},
				error:function(result){
					$.alert('系统错误');
					hideAnimation(_this,showWheLoadingObj);
				}
			});
		}
	}

//图片上传处理
	//上传图片完毕callback func
	window.sendPhotoComplete = function (result){//data改str
		//var str = eval('('+ data +')');
		if(result.status){
			inserData(result.data,true,'app');
			if($('#infoArea').find('div.infoList').size()>0){
							$('#popUp').css('width','643px').find('div.contentBox').addClass('viewMore');
						}
		}else{
			alert(result.info);
		}
		$('#tokenareaList').empty();
		$('#attachPhotoIntroduce').val('给这张照片做些说明吧');
		$('#tokenShareDestinations').val('');
		var $tempParent  = $("#uploadPhotoButton").parent();
		$("#uploadPhotoButton").remove();
		$($tempParent).prepend('<input type=\"file\" name=\"uploadPhotoFile\" id=\"uploadPhotoButton\">');
		hideAnimation($('#distributeButton'),$('#distributeInfoBody').find('div.showWhenLoading'));
	};
	
//视频上传处理
	
	//发布视频
	function distributeVideo(_this){
		var _message = $('#attachVideoIntroduce').val();
		if(_message == '给这段视频做些说明吧'){
			//default value;
		}else{
			var tokenValue = $('div#shareDestinationObjects').attr('value');
			var _videoId = $('#uploadedVideoId').val();
			var showWheLoadingObj = $('#distributeInfoBody').find('div.showWhenLoading');
			var reData = {};
			$.each(returnData,function(index,value){
				reData[index] = value;
			});
			$.extend(reData,{message:_message,eventid:eventId,nowUser:nowUser});
			
			$.ajax({
				type:"POST",
				url:distributeVideo_url,
				dataType:'jsonp',
				data:(reData),
				beforeSend: function(XMLHttpRequest){
					showAnimation(_this,showWheLoadingObj);
				},
				success: function(data){
					if(data.status){
						var str = data.data;
						inserData(str,true,'app');
						//视频文件发表成功后
						$('#tokenareaList').empty();
						$('#attachVideoIntroduce').val('给这段视频做些说明吧');
						$('#tokenShareDestinations').val('');
						$('#uploadedVideoId').val('');
						$distributeInfoBodyFooter.hide();
						
						var liArr = $("#composerAttachments > li");
						var divArr = $("#distributeInfoBody").find('div.distributeInfo');
						for (var i = divArr.length - 1; i >= 0; i--) {
							$(liArr[i]).removeClass('act');
							$(divArr[i]).addClass('hideEle');
						}
						$(liArr[0]).addClass('act');
						$(divArr[0]).removeClass('hideEle');
						$('#distributeInfoBody div.pointUp').css('marginLeft','22px');
					}else{
						alert(data.data);
					}
					hideAnimation(_this,showWheLoadingObj);
				},
				error:function(data){
					alert('系统错误');
					hideAnimation(_this,showWheLoadingObj);
				}
			});
		}
	}
	
	
	/** 视频上传完毕后允许提交按钮提交form表单 **/
	window.videoUploadComplete = function(data){
		var str = eval('(' + data + ')');
		if(str.status == 1){
			$('#up_success').fadeIn();
			returnData = str.data;
			$('#up_success a').on('click',function(){
				$("#up_success").hide();
				$('.flashContent').show();
				$(".flashContent object").height(25);//隐藏选择图片按钮
				$(".flashContent").height(25).css({"border":"1px solid #CCCCCC","padding":"10px 10px"});
				$(".flashContent div").show();
			})
		}else if(str.status == 2){
			$(this).popUp({
				width:450,
				title:'提示!',
				content: '<div style="padding:10px">视频正在转码中，请耐心等待，请勿关闭本页面！</div>',
				buttons:'',
				mask:true,
				maskMode:false,
				callback:function(){}
			});
			$.ajax({
				type:"POST",
				url:mk_url("single/video/index.php?c=videoapi&m=event_video_transcod"),
				dataType:"jsonp",
				data:{type:str.type,vid:str.vid},
				success:function(result){
					returnData = result.data;
					if(!result.status==1){
						$.closePopUp();
						$(this).popUp({
							width:450,
							title:'提示!',
							content: '<div style="padding:10px">'+result.info+'</div>',
							buttons:'<label class="uiButton uiButtonConfirm"><input type="button" value="重新上传" class="callbackBtn" /></label>',
							mask:true,
							maskMode:true,
							callback:function(){location.href=location.href;}
						});
					}else{
						$.closePopUp();
						$('#up_success').fadeIn();
					}
				}
			});
		}else{
			$(this).popUp({
				width:450,
				title:'提示!',
				content: '<div style="padding:10px">' + result.info + '</div>',
				buttons:'<label class="uiButton uiButtonConfirm"><input type="button" value="确定" class="callbackBtn" /></label>',
				mask:true,
				maskMode:true,
				callback:function(){window.location.reload();$.closePopUp();}
			});
			setTimeout(function(){
				window.location.reload();
				$.closePopUp();
			},10000);
		}
	};
	
	
	/** 上传过程中隐藏掉上传提示信息 **/
	window.hideUploadTips = function(){
		$('#uploadTips').hide();
	}
	
	/** flash as 出错后调用此函数,并显示出错原因 **/
	window.showErrorFunc = function(error)
	{
		if(!error){
			error = '出现错误!';
		}
		var _str = '<div style="padding:10px;">'+error+'</div>';
		$(this).popUp({
			width:320,
			title:'上传视频文件出现错误!',
			content: _str,
			buttons:'<label class="uiButton uiButtonConfirm"><input class="closeBtn" type="button" value="确定" /></label>',
			mask:true,
			maskMode:true,
			callback:function(){}
		});
	};
	
	/******** 取消视频编辑 ********/
	/* $('#cancelEditVideo').click(function(){
		window.history.back(-1);
	}); */

	/******************** 视频播放块开始 ***************/
	function createVideo(_videoURL,videoClass){
			var _videoWidth = 403;
			var _videoHeight = 227;
			/********** ckplayer 初始化***********/
			var s1=new ckplayer();
			s1.ckplayer_url = CONFIG['misc_path']+'flash/ckplayer.swf';//播放器文件名
			s1.ckplayer_flv = _videoURL;//视频地址
			s1.ckplayer_loadimg = 'http://www.ckplayer.com/images/loadimg3.jpg';//初始图片地址
			s1.ckplayer_pauseflash = '';//暂停时播放的广告，只支持flash和图片
			s1.ckplayer_pauseurl = '';//暂停时播放图片时需要加一个链接
			s1.ckplayer_loadadv = '';//视频开始前播放的广告，可以是flash,也可是视频格式
			s1.ckplayer_loadurl = '';//视频开始前广告的链接地址，主要针对视频广告，如果是flash可以不填写
			s1.ckplayer_loadtime = 0;//视频开始前广告播放的秒数,只针对flash或图片有效
			s1.ckplayer_endstatus = 1;//视频结束后的动作，0停止播放并发送js,1是不发送js且重新循环播放,2停止播放
			s1.ckplayer_volume = 80;//视频默认音量0-100之间
			s1.ckplayer_play = 0;//视频默认播放还是暂停，0是暂停，1是播放
			s1.ckplayer_width = _videoWidth;//播放器宽度
			s1.ckplayer_height = _videoHeight;//播放器高度
			s1.ckplayer_bgcolor = '#000000';//播放器背景颜色
			s1.ckplayer_allowFullScreen = true;//是否支持全屏，true支持，false不支持，默认支持
			s1.swfwrite(videoClass);//div的id
	}
	window.initialize = function(){
		//此处为FLASH预留初始化接口
	};
	/******************** 视频播放块结束 ***************/
	
	/**
	 * Created on 2011-11-09
	 * @author: willian
	 * @desc: 显示ajax发送/加载动画
	 * @param comfirmBtn 确认或者发送按钮(jqeury风格id或者class,或者直接传入jquery对象)
	 * @param container 放置loadingAnimation的容器(jqeury风格id或者class,或者直接传入jquery对象)
	 */
	function showAnimation(comfirmBtn,container){
		var _str = '<div class="loadingAnimation"><img src="'+CONFIG['misc_path']+'img/system/more_loading.gif" /></div>';
		$(comfirmBtn).attr('disabled',true);
		$(container).append(_str);
	}
	/**
	 * Created on 2011-11-09
	 * @author: willian
	 * @desc: 隐藏ajax发送/加载动画
	 * @param comfirmBtn 确认或者发送按钮(jqeury风格id或者class,或者直接传入jquery对象)
	 * @param container 放置loadingAnimation的容器(jqeury风格id或者class,或者直接传入jquery对象)
	 */
	function hideAnimation(comfirmBtn,container){
		$(comfirmBtn).removeAttr('disabled');
		$(container).find('.loadingAnimation').remove();
	}


	/****** 信息流 *******/
	var $content = $('#content'), $infoArea = $('#infoArea'), $infoMain = $infoArea.find('>div.infoMain');
	
	//信息流滚动加载
	$('div.infoMain').scrollLoad({
		text:"查看更多↓",	
		url:mk_url("gevent/event/Info",{gid:gid}),
		data:{eid : eventId},
		dataType:"jsonp",
		success:function(result){
			if(result.status){
				var data = result.data;
				for(var i=0,len=data.length; i<len; i++){
					inserData(data[i],true,'load');
				}
				//添加滚动条，显示更多
				if($('#infoArea').find('div.infoList').size()>0){
					$('#popUp').css('width','643px').find('div.contentBox').addClass('viewMore');
				}

			}else{
				$.alert('滚动加载数据不成功！请检查网络，稍候再试！');
			}
		}
	});


	$("div.infoList").live('mouseenter mouseleave', function() {
		$(this).find('span.deleIcon').show().bind('mouseenter mouseleave', function() {
			//显示下拉菜单
			$(this).find('ul').show();
		});
	});
	
	//修复IE6下hover背景显示
	if($.browser.msie && parseInt($.browser.version) <= 6){
	
		$infoMain.on('mouseover','li.dropList_del',function(){
			$(this).addClass('ieHover');
		}).on('mouseout','li.dropList_del',function(){
			$(this).removeClass('ieHover');
		});
	}
	//删除信息列表 
	$infoMain.delegate('li.dropList_del', 'click', function(){
		var _thisInfo = this;
		$.confirm('删除','你确定要执行此操作吗？',function(){
			var $infoList = $(_thisInfo).parentsUntil('div.infoList').parent();
			var tid = $infoList.attr('fid');
			$.ajax({
				url : delTopic_url,
				type : 'post',
				dataType : 'jsonp',
				data : {eventid:eventId,replyid:tid},
				success : function(result) {
					if(result.status) {
						$infoList.remove();
					} else {
						alert(result.info);
					}
				}
			});
		});
	});

	//点击播放视频
	$('a.showFlash').live('click',function(){
		$(this).parent().addClass('hide');
		$(this).parent().next().removeClass('hide');
		var $videoDiv = $(this).parent().next().find('div');
		var videoClass = $videoDiv.attr('id'),
			videoUrl = $videoDiv.attr('videosrc');
		createVideo(videoUrl,videoClass);
	});
	
	//发表完成后插入到html
	inserData = function (infoData,list,strMode){
		var str  = '<div class="infoList clearfix" fid="'+ infoData.tid +'">';
			str += '	<div class="info_head"><a href="'+ infoData.code +'" ><img src="'+ infoData.avatar +'" alt="头像" /></a></div>';
			str += '	<div class="info_right">';
		//发表链接
		if(infoData.link !== false){
			var link = infoData.link;
			str += ' 		<div class="info_title"><a href="'+ infoData.code +'"><strong>'+ infoData.username +'</strong></a><span class="addtime">'+infoData.addtime+'</span>'+ infoData.message;
			str += '			<span class="' + ( (infoData.can_del) ? 'deleIcon' : '') + '"><ul class="dropList"><li class="dropList_del">删除帖子</li></ul></span>';
			str += '		</div>';
			str += '		<div class="outLink">';
			str += '			<div class="clearfix">';
			if(link.img_url){
				str += '			<span class="info_pic outPic"><img src="'+ link.img_url +'" alt="" /></span>';
			}
			str += '				<h3><a href="'+ link.link_url +'">'+ link.title +'<i>'+ link.link_url +'</i></a></h3>';
			str += '				<span class="outText">'+ link.des +'</span>';
			str += '			</div>';
			str += '		</div>';
		}else{
			str += '		<div class="info_title"><a href="'+ infoData.code +'"><strong>' + infoData.username + '</strong></a><span class="addtime">'+infoData.addtime+'</span>';
			str += '			<span class="' + ( (infoData.can_del) ? 'deleIcon' : '') + '"><ul class="dropList"><li class="dropList_del">删除帖子</li></ul></span>';
			str += '		</div>';
			str += '		<div class="info_text">'+ infoData.message +'</div>';
			//发表图片
			if(infoData.image !== false){
				str += '	<div class="info_pic"><img src="'+ infoData.image +'" alt="" /></div>';
			}
			//发表视频
			if(infoData.video !== false){
				str += '	<div class="info_media" videoImg="' + infoData.video.imgurl + '" videoUrl="' + infoData.video.videourl + '">';
				str += '		<div class="info_media_prev">';
				str += '			<img src="'+infoData.video.imgurl+'" alt="" />';
				str += '			<a href="javascript:void(0);" class="showFlash"><img src="' + CONFIG['misc_path']+ 'img/system/feedvideoplay.gif" alt="" /></a>';
				str += '		</div>';
				str += '		<div class="info_media_disp hide">';
				str += '			<div id="video_'+ infoData.tid +'" videosrc="' + infoData.video.videourl + '" videoheight="'+ infoData.video.height +'" videowidth="'+ infoData.video.width +'"></div>';
				str += '		</div>';
				str += '	</div>';
			}
		}
		str += '			<div class="info_btm"><div class="eventComment" commentObjId="'+ infoData.tid +'" pageType="event" action_uid="'+ infoData.action_uid + '" msgname="" msgurl=""><!-- commentEasy插件 --></div></div>';
		str += '		</div>';
		str += '	</div>';
		if(list){
			var temp = $(str);
			if(strMode == 'app'){
				$('#infoArea').find('>div.infoMain').prepend(temp);
			}
			if(strMode == 'load'){
				$('#infoArea').find('>div.infoMain').append(temp);
			}
			$("div.eventComment").commentEasy({isOnlyYou:true});
		}else{
			return str;
		}
	}
	
});
