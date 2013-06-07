/**
 * Created on  2011-09-28
 * @author: willian、qiuminggang
 * @desc: 视频模块
 */
var UNID = 111;
//标识符
$(document).ready(function() {
	UNID = $("#progress_key").val();
	var getVideoListPart = mk_url("video/video/ajax_lists");
	//隐私控制好友列表url
	var setVideoPrivate = "api/access/set";
	//设置隐私URL
	var getPurviewList = mk_url('video/video/get_purview_list');
	//视频播放页面url
	var playVideo = mk_url('video/video/player_video', {
		'vid' : ''
	});
	//编辑视频
	var editVideo = mk_url("video/video/edit_video", {
		vid : ''
	});
	//删除视频
	var delVideo = mk_url("video/video/del_video", {
		vid : ''
	});
	//上传提交FORM URL
	var postForm_URL = mk_url("video/video/add_video");
	//视频转换URL
	var convert_Url = mk_url("wvideo/video/getTranscodResult");

	/******************** 上传时视频隐私控制 ***************/
	//privacy setting
	$('#privacySetting').dropdown({
		permission : {
			type : 'video',
			access_content : '',
			dataType : 'jsonp',
			url : mk_url("video/video/set_permission"),
			im : true
		}
	});
	$('#privacySettingEdit').dropdown({
		permission : {
			type : 'video',
			dataType : 'jsonp',
			access_content : ''
		}
	});
	$('#privacySettingAdd').dropdown({
		permission : {
			type : 'video',
			dataType : 'jsonp',
			access_content : ''
		}
	});

	/** 视频上传成功后点击发布按钮 **/
	$('#saveVideoInfomation').click(function() {
		$.djax({
			type : "POST",
			url : postForm_URL,
			dataType : 'json',
			data : {
				vid : $("[name='vid']").val(),
				title : $("[name='title']").val(),
				txtdesc : $("[name='txtdesc']").val(),
				permission : $("[name='permission']").val()
			},
			success : function(data) {
				switch(parseInt(data.status)) {
					case 0:
						alert(data.info);
						location.href = location.href;
						break;
					case 1:
						$(window).unbind("beforeunload", leave);
						location.href = mk_url("video/video/player_video", {
							vid : data.data.vid
						});
						break;
					case 2:
						$(this).popUp({
							width : 500,
							title : '视频转码提示',
							content : '<div style="padding:10px;"><h3 style="width:260px;font-size:14px;text-align:center;margin:0 auto;">'+ data.info +'</h3></div>',
							buttons : '<label class="uiButton uiButtonConfirm"><input type="button" value="确定" class="callbackBtn" /></label>',
							mask : true,
							maskMode : false,
							callback : function() {
								location.href = data.data.url;
							}
						});

						var callFunc = function() {
							$.djax({
								type : "POST",
								url : convert_Url,
								dataType : "json",
								data : {
									vid : $("[name='vid']").val()
								},
								success : function(data) {
									$(window).unbind("beforeunload", leave);
									if(data.status == 1) {
										//alert(data.info);
										location.href = 'single/video/index.php?c=video&m=player_video&vid=' + data.vid;
									}
									if(data.status == 2) {
										$.closePopUp();
										$(this).popUp({
											width : 450,
											title : '提示!',
											content : '<div style="padding:10px">' + data.info + '</div>',
											buttons : '<label class="uiButton uiButtonConfirm"><input type="button" value="返回视频列表" class="callbackBtn" /></label>',
											mask : true,
											maskMode : false,
											callback : function() {
												location.href = "index.php";
											}
										});

										setTimeout(callFunc, 2000);

									}
									if(data.status == 0) {
										$(this).popUp({
											width : 450,
											title : '提示!',
											content : '<div style="padding:10px">' + data.info + '</div>',
											buttons : '<label class="uiButton uiButtonConfirm"><input type="button" value="重新上传" class="callbackBtn" /></label>',
											mask : true,
											maskMode : false,
											callback : function() {
												location.href = location.href;
											}
										});
									}

								}
							});
						}
						break;
					case 3:
						$(this).popUp({
							width : 450,
							title : '提示!',
							content : '<div style="padding:10px">' + data.info + '</div>',
							buttons : '<label class="uiButton uiButtonConfirm"><input type="button" value="确定" class="callbackBtn" /></label>',
							mask : true,
							maskMode : false,
							callback : function() {
								location.href = data.data.url;
							}
						});
						break;
				}
			}
		});
		return false;
	});

	/******************** 视频播放块开始 ***************/
	if(document.getElementById('videoShow')) {
		var href = location.href;
		//$(".media_prev .showFlash").click(function(){
		AC_FL_RunContent('codebase', 'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0', 'width', '100%', 'height', '100%', 'quality', 'high', 'pluginspage', 'http://www.macromedia.com/go/getflashplayer', 'wmode', 'opaque', 'id', 'player', 'bgcolor', '#000000', 'name', 'player', 'allowFullScreen', 'true', 'allowScriptAccess', 'always', 'movie', CONFIG['misc_path'] + 'flash/video/player.swf?' + document.getElementById('videoURL').value + '&uid=' + CONFIG['u_id'], 'style', 'display:block;', 'contentId', document.getElementById('videoShow'));
		$(".media_prev").hide();
		//});
	}
	/********************视频录制*********************/
	if($(".camObj").length > 0) {
		AC_FL_RunContent('codebase', 'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0', 'width', '380', 'height', '270', 'src', CONFIG['misc_path'] + "flash/Videocam1", 'quality', 'high', 'pluginspage', 'http://www.macromedia.com/go/getflashplayer', 'align', 'middle', 'play', 'true', 'loop', 'true', 'scale', 'showall', 'wmode', 'opaque', 'devicefont', 'false', 'id', 'Videocam1', 'bgcolor', '#ffffff', 'name', 'Videocam1', 'menu', 'true', 'allowFullScreen', 'false', 'allowScriptAccess', 'always', 'movie', CONFIG['misc_path'] + "flash/Videocam1", 'salign', '', 'contentId', $(".camObj").get(0), 'FlashVars', 'uid=' + document.getElementById("hd_sessionId").value + '&url=' + document.getElementById("recordurl").value);
		//end AC code
	}
	
	/******************** 编辑隐私控制 ***************/
	var $sharePower = $('#sharePower');
	if($sharePower[0]) {
		var permission = function(selected, $currentUICombox) {
			var $permission = $('input[name="permission"]', $currentUICombox);
			var friend_list_callback = function(friends) {
				$sharePower.attr({
					'ids' : friends.ids,
					'usernames' : friends.names
				});
				$permission.val(friends.ids);
				setPower($permission);
			}
			if(selected !== '-1') {
				$permission.val(selected);
			} else {
				var fri = $sharePower.attr('ids');
				return new CLASS_FRIENDS_LIST({
					detail : $('#friends_detail'), //列表放置位置
					ids : (!!fri) ? fri : '',
					elm : $currentUICombox.find('li').eq(3), //触发好友窗口点击对象
					callback : friend_list_callback
				});
			}
			setPower($permission);
		}

		$.djax({
			type : "POST",
			url : getPurviewList,
			dataType : 'json',
			data : {},
			success : function(data) {
				if(data) {
					var str = '';
					for(var i = 0, len = data.data.length; i < len; i++) {
						str += '<li ref="' + data.data[i].purview + '"><a href="javascript:void(0);"><i class="uiIcon icons7 ' + purArr[i] + '"></i>' + data.data[i].name + '</a></li>';
					}

					$sharePower.uiCombox({
						lis : str,
						defaultSelect : $sharePower.attr('value') * 1,
						callback : permission,
						activeSelected : true,
						width : 100,
						selectFieldName : 'permission', //隐藏域<input type="hidden" />
						selectFieldValue : '1'//隐藏域的值
					});
				}
			}
		});
	}

	//djax设置隐私
	function setPower(_$permission) {
		$.djax({
			type : "POST",
			url : setVideoPrivate,
			dataType : 'json',
			data : ( {
				type : 'video',
				object_id : $('#videoId').val(),
				permission : _$permission.val()
			}),
			success : function(data) {
				if(data.status == 1) {
					//alert(str.status);
				} else {
					alert(data.info);
				}
			}
		});
	}

	/******************** 视频播放块结束 ***************/

	/********************* 删除视频 *********************/
	$('#deleteVideo').click(function() {
		var _videoID = $(this).attr('rel');
		var _str = '<div style="padding:10px;"><div style="padding:4px 0"><img src="' + $('#video_pic').val() + '" width="168" height="90" /></div><div style="font-weight:bold;font-size:14px;margin-bottom:10px;">删除视频是永久性的操作</div><div style="margin-bottom:10px">如果你删除这个视频，你将不能取回它。</div><div style="margin-bottom:10px">您确定要删除此视频吗？</div></div>';
		$(this).popUp({
			width : 363,
			title : '提醒!',
			content : _str,
			buttons : '<label class="uiButton uiButtonConfirm"><input type="button" value="删除" class="callbackBtn" /></label><label class="uiButton uiButtonDepressed"><input type="button" value="取消" class="closeBtn"></label>',
			mask : true,
			maskMode : false,
			callback : function(args) {
				var delBtn = $('input.callbackBtn');
				delBtn.attr('disabled', 'disabled');
				$.djax({
					type : "POST",
					url : delVideo + _videoID,
					dataType : 'json',
					data : ( {
						videoID : _videoID
					}),
					success : function(data) {
						if(data.status == 1) {
							$(this).popUp({
								title : '视频已删除!',
								content : '<div style="padding:10px">视频已删除，转向到你的视频。</div>',
								buttons : '',
								mask : true,
								maskMode : false,
								callback : function() {
								}
							});
							setTimeout(function() {
								window.location.replace(mk_url("video/video/index"));
								$.closePopUp();
							}, 2000);
						} else {
							delBtn.removeAttr('disabled');
							alert(data.info);
						}
					}
				});
			}
		});
		return false;
	});
	
	/********************* 视频列表开始  *********************/
	function getVideoList(ii,tab){
		/**更多视频**/
		if($("#videoList").scrollLoad)
			$("#videoList").scrollLoad({
				text : '<div id="moreVideo"><a id="moreVideoButton" ref="1" href="javascript:void(0);">更多视频...</a></div>',
				url : getVideoListPart,
				data:{
					dkcode:$('#hd_dkcode').val(),
					dateline:$('#hd_dateline').val(),
					permissionType:ii
				},
				success : function(data) {
					var _str = '';
					var sum = data.rows;
					var videoinfo = data.content;
					$("#videoNum").text(sum);
					if(sum == 0) {
						$('.videoTips').text("该分类下暂无视频数据")
						$('#videoList').hide();
					}else{
						$('.videoTips').hide();
						$('#videoList').show();
					}
					for(var i = 0; i < videoinfo.length; i++) {
						_str += '<div class="videoGridItem">';
						_str += '<a class="videoLinkLarge" href="' + playVideo + videoinfo[i].id + '">';
						if(videoinfo[i].status == 4 || videoinfo[i].status == 5){
							_str += '<div class="shTipsBg"></div>';
							_str += '<div class="shTipsCon">正在审核中...</div>';
						}
						_str += '<i style="background:url(' + videoinfo[i].video_pic + ') no-repeat center"></i>';
						_str += '<span>' + videoinfo[i].time + '</span>';
						_str += '</a>';
						_str += '<div class="metadata">';
						_str += '<p class="videoName"><a title="' + videoinfo[i].title + '" href="' + playVideo + videoinfo[i].id + '" >' + videoinfo[i].title + '</a></p>';
						if(data.is_author) {
							_str += '<a href="' + editVideo + videoinfo[i].id + '">编辑视频</a>';
						}
						_str += '</div>';
						_str += '</div>';
					}
					if(tab) $('#videoList').html(_str)
					else $('#videoList').append(_str);
					tab = false;
				}
			});
	};
	
	//视频列表分类激活选项卡
	function activeMenu(o){
		var tab = true;
		$(".videoListTitle").nextAll().removeClass("on");
		o.addClass("on");
		$('#videoList').hide();
		$('.videoTips').show();
		$('.videoTips').text("加载中，请稍等...");
		getVideoList(o[0].id.replace("list_",""),tab);
	}
	
	//视频列表分类激活第一个选项卡
	if(location.href.indexOf("index") != -1){
		if($(".videoListTitle").next()[0]){
			$(".videoListTitle").nextAll().click(function(){
				activeMenu($(this));
			});
			activeMenu($(".videoListTitle").next());
		}else{
			getVideoList(0);
		}
	}
	/********************* 视频列表结束  *********************/
	
	/*****上传、编辑视频内，标题、说明字数输入限制*****/
	$(".metadataInput").each(function() {
		new Textarea.msgTip(this, {
			maxlength : 140,
			status : 'true',
			textareaStyles : {
				overflow : "hidden",
				height : 70
			},
			button : {
				id : $(".textareaTip")
			}
		});
	});
	
	/******************** 视频上传模块结束 ***************/
	try {
		videoUpload.AC_FL_RunContent({
			'appendTo' : document.getElementById("upload"), //flash添加到页面的容器
			'url' : $("#hd_video_upload_url").val() + '?appkey=' + $("#hd_url").val() + '&mid=1', //上传到的url
			'types' : '*.rm;*.rmvb;*.flv;*.3gp;*.mp4;*.dv', //可用的视频格式
			'size' : "102400", //限制上传大小，单位是kb
			'width' : 380,
			'height' : 60,
			'allowScriptAccess' : "always",
			'movie' : CONFIG['misc_path'] + "flash/upload.swf", //该swf的地址
			'wmode' : 'opaque', //默认window
			//初始化调用
			'onInit' : function(list) {
				$("#uploadTips").hide();
				$(".up_tips").hide();
				$('#videoDescriptions').show();
			},
			//上传成功调用
			'onComplete' : function(data) {
				var str = eval('(' + data + ')');
				if(str.status == 1) {
					$("saveVideoInfomation")
					$("#videoId").val(str.data);
					$("#uploadTips").hide();
					$(".up_tips").hide();
					$('#videoDescriptions').show();
					$('#saveVideoInfomation').removeAttr('disabled').removeClass('disabled');
					//显示发布视频隐藏按钮
					videoUpload.thisMovie('flashvideoupload').isJsComplete(true);
					//成功后与flash交互
				} else {
					videoUpload.thisMovie('flashvideoupload').isJsComplete(false);
					//失败后与flash交互
				}
			},
			'onSelect' : function(name) {
				$('#uploadVideoTitle').val(name.replace(".flv", ""));
			},
			//上传失败调用
			'onWarn' : function(error) {
				$(this).popUp({
					width : 450,
					title : '错误提示!',
					content : '<div style="padding:10px">' + error + '</div>',
					buttons : '<span class="popBtns blueBtn callbackBtn">重新上传</span>',
					mask : true,
					callback : function() {
						location.href = location.href;
					}
				});
				setTimeout(function() {
					window.location.reload();
					$.closePopUp();
				}, 10000);
			},
			'onAgain' : function() {//点击重新上传
				$("#uploadTips").show();
				$(".up_tips").show();
				$('#videoDescriptions').hide();
				$('#saveVideoInfomation')[0].disabled=true;
				$('#saveVideoInfomation').addClass('disabled');
				//显示发布视频隐藏按钮
				videoUpload.thisMovie('flashvideoupload').isJsOnAgain(true);
			}
		});
	} catch(e) {

	}
	
	//视频录制发布后处理
	function videoManger(result) {
		if (result) {
			var url;
			switch (parseInt(result.status)) {
				case 0:
					url = location.href;
					break;
				case 1:
					url = result.data.url;
					break;
			}
			$(this).popUp({
				width:450,
				title: '提示！',
				content: '<div style="padding:10px">' + result.info + '</div>',
				buttons:'<label class="uiButton uiButtonConfirm"><input type="button" value="确定" class="callbackBtn" /></label>',
				mask: true,
				maskMode: true,
				callback: function() {
					location.replace(url);
				}
			});
		}
	}
	
	//视频录制发布
	$('#saveCamInfomation').click(function(){
		$.ajax({
			url:mk_url("video/video/save_makevideo", {}),
			dataType:"jsonp",
			type:"POST",
			data:{
				hd_v_w:$("#hd_v_w").val(), 
				hd_v_h:$("#hd_v_h").val(), 
				hd_v_name:$("#hd_v_name").val(), 
				title:$("#uploadVideoTitle").val(), 
				txtdesc:$("#desc").val(), 
				permission:$("#videoPerm").val()
			},
			success:function(result) {
				videoManger(result);
			},
			error:function() {
				alert("网络错误!");
			}
		});
		return false;
	});
	
	//上传成功之后不保存就离开页面的弹窗提示
	function leave() {
		$(this).popUp({
			width : 450,
			title : '提示!',
			content : '<div style="padding:10px">确认离开当前页面吗？未保存的数据将会丢失!</div>',
			buttons : '<span class="popBtns blueBtn callbackBtn">确定</span><span class="popBtns blueBtn closeBtn">取消</span>',
			mask : true,
			maskMode : false,
			callback : function() {
				location.href = "index.php";
			}
		});
		return false;
	}

	$(".txt_url").val("");

	window.disable = function(boolen) {
		document.getElementById('saveCamInfomation').disabled = boolen;
		if(boolen) {
			$('#saveCamInfomation').addClass("disabled");
			$('#nextCamTips').show();
			$('#preCamTips').hide();
		} else {
			$('#saveCamInfomation').removeClass("disabled");
			$('#preCamTips').show();
			$('#nextCamTips').hide();
		}
	}
	
	window.thisMovie = function(movieName) {
		if(navigator.appName.indexOf("Microsoft") != -1) {
			return window[movieName];
		} else {
			return document[movieName];
		}
	}

	window.webcam_error = function(msg) {
		alert(msg);
	}

	window.getUid = function() {
		return document.getElementById("hd_sessionId").value;
	}
	window.camComplete = function(w, h, name) {
		document.getElementById("hd_v_w").value = w;
		document.getElementById("hd_v_h").value = h;
		document.getElementById("hd_v_name").value = name;
		$(".userActions a").bind("click", leave);
	}
	window.camNontCam = function() {
		$("#noCam").show();
		$(".camObj").hide();
		$("#videoDescriptions").hide();
	}

	window.change = function(h) {
		var height = h + "px";
		if($("#videoShow")) {
			$("#videoShow").css("height", height);
		}
	}
	$("#btn_Refresh").click(function() {
		location.href = location.href;
	});
});
