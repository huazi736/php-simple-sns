document.domain = CONFIG['local_run'] ? "duankou.com" : CONFIG['domain'].substring(1);
var textAreaInitialize = false;
var WEB_ID = $(".web_id").val();

/*UPDATE:zhuliqi  7/31 */
function CLASS_WEBPOSTBOX(options) {
	this.webposts = null;
	this.argData = {};
	this.index = 0;
	this.photoData = null;
	this._class = options._class;
    this.classTimeLine = options.classTimeLine;
	this.videoData = null;
	this.yearNum = $(".yearNum");
	this.isCurrent = false;
}
CLASS_WEBPOSTBOX.prototype = {
	init:function(webposts) {
		this.webposts = webposts;
		this.webposts.find("ul.postHead li:last").css("border-right", "0 none");
		this.bindEvent();
		$("[name='sel_christian']", webposts).change();
		$("[msg]", webposts).msg();
		$(".split").hide();
//        this.createBtnAble(false);
		//$("[name='txt_explain']").next('span').css("top",0);
		
		if(!textAreaInitialize) {
			$(".Js_textArea").each(function() {
				var myStatusTextArea = new Textarea.msgTip(this, {
					maxlength:500,
					notMedia:true,
					textareaProps:{
						"class":"shareInfoCont msg"
					},
					textareaStyles:{
						overflow:"hidden",
						height:60
					},
					button:{
						id:$("#distributeButton").parent()
					}
				});
			});
			$(".Js_mediaArea").each(function() {
				var mediaArea = new Textarea.msgTip(this, {
					maxlength:140,
					notMedia: false,
					textareaProps:{
						"class":"shareInfoCont msg"
					},
					textareaStyles:{
						overflow:"hidden",
						height:60
					},
					button:{
						id:$("#distributeButton").parent()
					}
				});
			});
			$(".Js_groupArea").each(function() {
				var mediaArea = new Textarea.msgTip(this, {
					maxlength:140,
					notMedia: false,
					textareaProps:{
						"class":"shareInfoCont msg"
					},
					textareaStyles:{
						overflow:"hidden",
						height:30
					},
					button:{
						id:$("#distributeButton").parent()
					}
				});
			});
		}
		textAreaInitialize = true;
	},
	sendEditshopping: function() {

	},
	createBtnAble:function(bool) {
		if(!$('#distributeButton')[0]) return;
		if (bool) {
			$('#distributeButton').parent().removeClass('disable');
			return false;
		} else {
			$('#distributeButton').parent().addClass('disable');
		}
	},

	bindEvent:function() {
		var _self = this;
		var webposts = _self.webposts;
		var tabContent = $(".tabContent", _self.webposts);

		var postText = webposts.find(".postText");
		var postTextArea = postText.find("textarea");
		var postImage = webposts.find(".postImage");
		var postVideo = webposts.find(".postVideo");
		var postGoods = webposts.find(".goods");
		var postGroup = webposts.find(".group");
		var postHouse = webposts.find(".house");


		_self.webposts.find("ul.postHead li").live('click',function() {
			var page = $("#data-page");
			if(page.hasClass("data-page-goods")) {
				page.removeClass("data-page-goods");
			} else if(page.hasClass("data-page-group")) {
				page.removeClass("data-page-group");
			}
			var index = $(this).index();
			var rel = $(this).attr('rel')
			$(".split").show();
			$("#distributeButton").parent().attr("data", false);
			tabContent.show();

			var _postcont = $('.tabList').find('.postcont');
			_postcont.eq(index).show().siblings().hide();
			
			if(rel == 'image'){
				postImage.find(".PostselectTab").show();
				postImage.find(".imgContent").hide();
			}else if(rel == 'video'){
				postVideo.find(".PostselectTab").show();
				postVideo.find(".videoContent").hide();
			}

			if(rel == 'status'){
				if ($("#myStatusTextArea").height() < 50) {
					$("#myStatusTextArea").css("height", 50)
				}
				var value = $("[name='txt_Text']").val();
				var l = value.length;
				if(value != "" && !(l > postTextArea.attr("tmaxlength"))) {
					_self.createBtnAble(true);
				} else {
					_self.createBtnAble(false);
				}
			}

			var _postbtn = $("div.tabFooter", _self.webposts);
			(rel == 'image' || rel == 'video') ? _postbtn.hide() : _postbtn.show();
			if(index == 0) _self.text();
		});

		$("[name='sel_christian']", _self.webposts).change(function() {
			var i = $(this).val();
			if($(this).find("option:selected").text() == "公元前") {
				self.isCurrent = true;
			} else {
				self.isCurrent = false;
			}
			$(this).parent().nextAll("span[rel]").hide().end().nextAll("[rel='" + i + "']").show();
		});
		$("[name='sel_month']", _self.webposts).change(function() {
			if ($(this).val() != '') {
				$("[name='sel_Days']").show();
				var rel = $(this).find("option:selected").attr("rel");
				_self.createDays(rel);
			} else {
				$("[name='sel_Days']").val('').hide();
			}
		});
//			$("[name='yearNum']",_self.webposts).unbind().bind('blur',validateDateSel);
//			$("[name='sel_yearUnit']",_self.webposts).unbind().bind('change',function(e){validateDateSel.call($("[name='yearNum']",_self.webposts),e)});
		$("ul.PostselectTab>li>a").live('click',function() {
			var ref = $(this).attr("ref");
			_self.index = ref;
			$(".tabFooter", _self.webposts).show();
			var postselectTab = $("ul.PostselectTab");
			postselectTab.hide();
			var imgContent = $(".imgContent");
			var videoContent = $(".videoContent");
			//image
			if ($(this).closest(".twoTab").hasClass("postImage")) {
				imgContent.show();
				_self.image();
			}
			//video
			if ($(this).closest(".twoTab").hasClass("postVideo")) {
				videoContent.show();
				_self.video();
			}
		});
		$("[name='sel_yearUnit']", _self.webposts).change(function() {
			if ($(this).val() == 1) {
				$(this).next().show();
			} else
				$(this).next().hide().next().hide();
		}).change();
		$("#TopPostArea,.dk_calendar,#calendar_block").bind('mousedown', function(e) {
			e.stopPropagation();
		});
		$(document).bind('mousedown', function(e) {
			if($(".dk_calendar").find(e.target).size() !== 0 ) {
				return;
			}
			$(".pointUp").hide();
			$(".TopPostBox").hide();
		});
	},
	createDays:function(m) {
		var str = "<option value=''>日</option>";
		for (var i = 1; i <= m; i++) {
			str += '<option value="' + i + '">' + i + '日</option>';
		}
		$("[name='sel_Days']", this.webposts).html(str);
	},
	/*****获取时间*****/
	getDate:function() {
		var _self = this;
		if ($("[name='sel_christian']", this.webposts).val() == "0") {
			_self.argData.bc = 1;
			_self.argData.timestr = $("[name='txt_selectDate']", _self.webposts).val();
		} else {
			_self.argData.bc = -1;
			if ($.trim($("[name='yearNum']", this.webposts).val()).length == 0) {
				$("[name='yearNum']", this.webposts).focus();
				return false;
			}
			_self.argData.timestr = parseInt($("[name='yearNum']", this.webposts).val()) * parseInt($("[name='sel_yearUnit']").val());
			if ($.trim($("[name='sel_month']", this.webposts).val()).length != 0 && $("[name='sel_yearUnit']").val() == 1) {
				_self.argData.timestr += '-' + $("[name='sel_month']", this.webposts).val();
			}
			if ($.trim($("[name='sel_Days']", this.webposts).val()).length != 0 && $("[name='sel_yearUnit']").val() == 1) {
				_self.argData.timestr += '-' + $("[name='sel_Days']", this.webposts).val();
			}
		}
		_self.argData.timedesc = $("[name='txt_explain']", _self.webposts).val();
	},
	checkTextLength:function() {
		if ($.trim($("[name='txt_Text']", this.webposts).val()).length > 0) {
			$('[name="btn_sendInfo"]', this.webposts).parent().removeClass('disable');
		} else {
			$('[name="btn_sendInfo"]', this.webposts).parent().addClass('disable');
		}
	},
	/*********文字************/
	text:function() {
		var _self = this;
		//$("[name='btn_sendInfo']", this.webposts).unbind("click");
		$("[name='btn_sendInfo']", this.webposts).bind("click", function() {
			if (!$('[name="btn_sendInfo"]', _self.webposts).parent().hasClass('disable')) {
				_self.argData.content = $.trim($("[name='txt_Text']", _self.webposts).val());
				_self.argData.type = "info";
				_self.getDate();
				_self.argData.web_id = $(".web_id").val();
				if (_self.argData.content == "") {
					return false;
				}
				if(self.isCurrent && $(".yearNum").val() == "") {
					$.alert("请填写公元前年份", "提交失败！")
					return false;
				}
				_self.post()
			}
		});
	},
	image:function() {
		var _self = this;
		var postImg = $(".postImg");
		var postImgCam = $(".postImgCam");
		var postImgText = $(".postImg").find("textarea");
		if ($("#up_photo_success:visible", _self.webposts).length == 0) {
			$('[name="btn_sendInfo"]', _self.webposts).parent().addClass('disable');
		}
		$(".tabFooter").show();
		if (_self.index == 0) {
			//上传照片
			var type = 3;
			postImg.show();
			postImgCam.hide();
			$("#distributeButton").parent().attr("data", "true");
			if (!_self.photoData) {
				_self.createBtnAble(false);
				$("#distributeButton").parent().attr("data", "false");
			} else if(postImgText.val().length > postImgText.attr("tmaxlength")) {
				_self.createBtnAble(false);
			} else {
				_self.createBtnAble(true);
				$("#distributeButton").parent().attr("data", "true");
			}
			//var webpath = "http://localhost/frontendcore/"
			var updataPic_URL = mk_url("walbum/api/upload");
			var miscpath = CONFIG['misc_path'];
			var flashUrl = miscpath + "flash/plug-flash/jQuery-uploadify/uploadify.swf";
			if ($("#uploadPhotoButtonUploader", _self.webposts).length == 0)
				$("#uploadPhotoButton", _self.webposts).uploadify({
					"uploader": flashUrl,
					"script":updataPic_URL,
					"method":'GET',
					"scriptData":{
						"flashUploadUid":$("#flashuploaduid").attr("flashuploaduid"),
						'type':type,
						'web_id':WEB_ID
					},
					"cancelImg":miscpath + "img/system/icon_close_03.png",
					"buttonImg":miscpath + "img/system/icon_selectImg.png",
					"folder":miscpath + "temp",
					"fileExt":"*.jpg;*.jpeg;*.gif;*.png",
					"fileDesc":"*.jpg;*.jpeg;*.gif;*.png图片格式",
					"width":67,
					"height":24,
					"queueID":"photo_queueID",
					"multi":false,
					"auto":true,
					"queueSizeLimit":100,
					"fileDataName":'uploadPhotoFile',
					'sizeLimit':1024 * 1024 * 10,
					'expressInstall':miscpath + "flash/expressInstall.swf",
                    'scriptAccess':'always',
					"onOpen":function() {
						$("#flashuploaduid object").height(0).css("border", "none");//隐藏选择图片按钮
						$("#flashuploaduid").height(0).css({"border":"none", "padding":"0px"});
						$("#flashuploaduid div").hide();
					},
					"onComplete":function(e, queueID, fileObj, response, data) {
						var data = eval('(' + response + ')');
						if (data.status == 1) {
							$('[name="btn_sendInfo"]').parent().removeClass('disable');
							_self.photoData = $.extend(_self.photoData, data.data);
							$("body").data('photoData', data.data);
							if(postImgText.val() !== "" && (postImgText.val().length > postImgText.attr("tmaxlength"))) {
								_self.createBtnAble(false);
							} else {
								_self.createBtnAble(true);
							}
							$('#distributeButton').parent().attr("data", "true")
							$("#up_photo_success").show();
						} else {
							$(this).subPopUp({
								width:557,
								title:"上传图片时发生错误",
								content:'<div style="padding:15px; line-height:200%;"><strong>上传图片时发生错误，可能是由图片格式不正确或大小超过10M或服务器错误引起。</strong><br /><br /><span>端口支持的图片格式为:</span><ul style="list-style:inside circle;"><li>*.jpg</li><li>*.jpeg</li><li>*.gif</li><li>*.png</li></ul></div>',
								mask:true,
								maskMode:false,
								buttons:'<span class="popBtns closeBtn" id="uploadPic_cancel">确定</span>'
							});
							$("#flashuploaduid div").show()
							$("#flashuploaduid object").height(25);//隐藏选择图片按钮
							$("#flashuploaduid").height(50).css({"border":"1px solid #CCCCCC", "padding":"3px 10px"});
							$("#distributeButton").parent().attr("data", "false");
						}
					},
					"onCancel":function() {
						_self.photoData = null;
						$("body").removeData("photoData");
						$("#flashuploaduid div").show()
						$("#flashuploaduid object").height(25);//隐藏选择图片按钮
						$("#flashuploaduid").height(50).css({"border":"1px solid #CCCCCC", "padding":"3px 10px"});
					},
					"onError":function(e, qid, fo, eo) {
						if (eo.type == "File Size") {
							$(this).popUp({
								width:450,
								title:'提示!',
								content:'<div style="padding:10px">上传图片大小不能超过4M !</div>',
								buttons:'<span class="popBtns blueBtn closeBtn">确定</span>',
								mask:true,
								maskMode:true
							});
							$("#photo_queueID").html('');
						}
					}
				});
			//提交
			//$("[name='btn_sendInfo']", _self.webposts).unbind("click");
			$("[name='btn_sendInfo']", _self.webposts).bind("click", function() {
				if (!$('[name="btn_sendInfo"]', _self.webposts).parent().hasClass('disable')) {
					_self.photoData = $("body").data('photoData');
					if (!_self.photoData) {
						$(this).popUp({
							width:450,
							title:'提示!',
							content:'<div style="padding:10px">请先上传图片!</div>',
							buttons:'<span class="popBtns blueBtn closeBtn">确定</span>',
							mask:true,
							maskMode:true
						})
						return false;
					}
					if(self.isCurrent && $(".yearNum").val() == "") {
						$.alert("请填写公元前年份", "提交失败！")
						return false;
					}
					var flashID = _self.getID('campz');
					_self.argData.type = "photo";
					_self.argData.fid = _self.photoData.fid;
					_self.argData.picurl = _self.photoData.picurl
					_self.argData.content = $("[name='txt_photoText']", _self.webposts).eq(_self.index).val();
					_self.argData.timestr = $(".html_date").data('time') || $(".html_date").val();
					_self.argData.note = _self.photoData.note;
					_self.argData.web_id = WEB_ID;
					_self.getDate();
					_self.post();
					$("body").removeData("photoData");
				}
			});
		} else {
			//var webpath = "http://localhost/frontendcore/"
			//拍照
			postImgCam.show();
			postImg.hide();
			if ($.browser.mozilla) {
				var campz = '<embed id="postCmaPZ" width="380" height="270" wmode="opaque" scale="noscale" salign="tl" allowscriptaccess="always" allowfullscreen="true" quality="high" bgcolor="#ffffff" name="postCmaPZ" style="display:block;" src="' + CONFIG['misc_path'] + 'flash/campz.swf" type="application/x-shockwave-flash" allownetworking="all" />';
				$("div[name='postCmaPZ']").parent().append(campz);
				$("div[name='postCmaPZ']").remove();
			} else {
				swfobject.embedSWF(CONFIG['misc_path'] + "/flash/campz.swf", "postCmaPZ", "380", "270", "9.0.0", CONFIG['misc_path'] + "flash/expressInstall.swf");
			};
			_self.createBtnAble(false);
			//摄相象头拍照照片发布
			window.photo = function(data) {
				var data = eval('(' + data + ')');
				sendPhotoComplete.call(_self, data);
			};
			//提交
			//$("[name='btn_sendInfo']", _self.webposts).unbind("click");
			$("[name='btn_sendInfo']", _self.webposts).bind("click", function() {
				if (!$('[name="btn_sendInfo"]').parent().hasClass('disable')) {
					var flashID = _self.getID('postCmaPZ');
					if(self.isCurrent && $(".yearNum").val() == "") {
						$.alert("请填写公元前年份", "提交失败！")
						return false;
					}
					flashID.save(mk_url("walbum/api/camera"), CONFIG['u_id'], WEB_ID);
				}
			});
		}
	},
	//获得flash对象
	getID:function(swfID) {
		/*
		 if (navigator.appName.indexOf("Microsoft") > -1) {
		 return window[swfID];
		 } else {
		 return document[swfID];
		 }
		 */
		return $("#" + swfID, this.webposts).get(0);
	},
	post:function() {
		var _self = this,
            _tempYear = parseInt(_self.argData.timestr);
        _self.argData.isRequestMonth = $.inArray(_tempYear,_self._class.timeLineClickArray) == -1 ? 1 : 0;
        _self._class.timeLineClickArray.push(_tempYear);
		$.djax({
			url:mk_url("webmain/web/doPost"),
			dataType:"json",
			async:true,
			data:_self.argData,
			success:function(data) {
				if (data.status == 1) {
					_self.resetInput();
					$(".tabContent,.split,.TopPostBox,.pointUp", this.webposts).hide();
					//try{
					var tempA = _self._class.CLASS_TIMELINE_NAV.addNewYear(data, _self._class,_self.classTimeLine);
					_self.siderClick([tempA, data]);
					//}catch(ex){
					//}
				} else {
					alert('发布失败了，请稍后重试！');
				}

			},
			error:function(data) {
				alert("网络错误，请重试！");
			}
		});
	},
	resetInput:function() {
		var _self = this;
		//$("div.tabContent",_self.webposts).hide();
		$("[name='txt_Text'],[name='txt_videoText'],[name='txt_photoText']", _self.webposts).val('');
		$("#up_success a", _self.webposts).click();
		$("#flashuploaduid div").show()
		$("#flashuploaduid object").height(25);//隐藏选择图片按钮
		$("#flashuploaduid").height(50).css({"border":"1px solid #CCCCCC", "padding":"3px 10px"});
		$("#up_photo_success").hide();
		$("#up_success").hide();
		$(".sendData").addClass("disable");
	},
	video:function() {
		var _self = this;
		var recordVideoPanel = $("#recordVideoPanel");
		var postVideoUpload = $(".postVideoUpload");
		var postVideoCam = $(".postVideoCam");
		var videoText = $("#videoArea").find("textarea");
		$(".tabFooter").show();
		var checkfn = function(dataId, textarea) {
			if ($(dataId).val() == "") {
				_self.createBtnAble(false);
				$("#distributeButton").parent().attr("data", "false");
			} else if(textarea.val().length > textarea.attr("tmaxlength")) {
				_self.createBtnAble(false);
			} else {
				_self.createBtnAble(true);
			}
		};
		//var webpath= "http://localhost/frontendcore/"
		if (_self.index == 1) {
			postVideoUpload.show();
			recordVideoPanel.hide();
			postVideoCam.hide();
			$("#distributeButton").parent().attr("data", "true");
			checkfn("#videoId", videoText);
			function cancelUpload() {
				$(".flashContent").show();
				$("#uploadTips").show();
				$('#videoDescriptions').hide();
				$("#up_success").hide();
				$("#uploadState").hide();
				$(".flashContent div").show();
				$(".flashContent object").height("25px");
				$(".flashContent .txt_url").show().val("");
				$(".flashContent").height(65).css({"border":"1px solid #CCCCCC", "padding":"3px 10px"});
				if ($(".uploadifyQueueItem").first().length)
					$("#uploadify").uploadifyCancel($(".uploadifyQueueItem").first().attr("id").replace("uploadify", ""));
				$("#videoId").val("");
				$("#saveVideoInfomation").attr("disabled", true).addClass("disabled");
				$('#distributeButton').parent().attr("data", "false")
				self.createBtnAble(false);
			};
			var miscpath = CONFIG['misc_path'];
			videoUpload.AC_FL_RunContent({
				'appendTo' : document.getElementById("uploadify"),//flash添加到页面的容器
				'url' : $("#hd_video_upload_url").val()+'?appkey='+$("#hd_url").val()+'&mid=2',//上传到的url
				'width' : 380,
				'height' : 60,
				'types' : '*.rm;*.rmvb;*.flv;*.3gp;*.mp4;*.dv',//可用的视频格式
				'size' : "102400",//限制上传大小，单位是kb
				'allowScriptAccess' : "always",
				'movie' : CONFIG['misc_path']+"flash/upload.swf",//该swf的地址
				'wmode' : 'opaque',  //默认window
				'onInit':function(list) {
					//$(".flashContent").hide();
					$(".uploadifyQueueItem .cancel").html('<label class="uiButton uiButtonConfirm"><input class="closeBtn" id="cancelBtn" type="button" value="取消"></lable>');
					$("#cancelBtn").click(function() {
						$(this).popUp({
							width:450,
							title:'取消上传!',
							content:'<div style="padding:10px">您确定你想取消视频上传么?</div>',
							buttons:'<span class="popBtns blueBtn callbackBtn">取消上传</span><span class="popBtns blueBtn closeBtn">请勿取消</span>',
							mask:true,
							maskMode:true,
							callback:function() {
								$("#videoId").val("");
								$("#saveVideoInfomation").attr("disabled", true).addClass("disabled");
								$('#distributeButton').parent().attr("data", "false");
								_self.createBtnAble(false);
							}
						});
					});
				},
				"onComplete":function(data) {
					var str = eval('(' + data + ')');
					try{
					if(str.status == 1){
						$("#videoId").val(str.data);
						//$('.flashContent').hide();
						var videoText = $("#videoArea").find("textarea");
						if(videoText.val() !== "" && (videoText.val().length > videoText.attr("tmaxlength"))) {
							_self.createBtnAble(false);
						} else {
							_self.createBtnAble(true);
						}
						$('#distributeButton').parent().attr("data", "true")
						videoUpload.thisMovie('flashvideoupload').isJsComplete(true);//陈功后与flash交互
					}else{
						videoUpload.thisMovie('flashvideoupload').isJsComplete(false);//失败后与flash交互
					}
					}catch(e){
					}
				},
				"onCancel":function() {
					$('#distributeButton').parent().attr("data", "false")
					_self.createBtnAble(false);
				},
				"onWarn":function(error) {
					$(this).popUp({
						width:450,
						title:'提示!',
						content: '<div style="padding:10px">'+ error + '</div>',
						buttons:'<label class="uiButton uiButtonConfirm"><input type="button" value="确定" class="closeBtn" /></label>',
						mask:true,
						maskMode:true,
						callback:function(){setTimeout(cancelUpload,200);}
					});
				},
				'onAgain' : function(){
					/*
					 var uploadagain=confirm("确认重新上传吗吗？");
					 if(uploadagain==true){
					 thisMovie('flashvideoupload').isJsOnAgain(true);
					 }else{
					 thisMovie('flashvideoupload').isJsOnAgain(false);
					 }
					 */
					$('#distributeButton').parent().attr("data", "false")
					_self.createBtnAble(false);
					videoUpload.thisMovie('flashvideoupload').isJsOnAgain(true);
				}
			});

		} else {
			postVideoCam.show();
			recordVideoPanel.show();
			postVideoUpload.hide();
			_self.createBtnAble(false);
			var flashvars = {
				uid:document.getElementById("videoname").value,
				url:document.getElementById("recordurl").value
			}
			var camRecord = '<div id="recordVideoPanel"><embed id="cam" width="380" height="270" wmode="opaque" scale="noscale" salign="tl" allowscriptaccess="always" allowfullscreen="true" quality="high" bgcolor="#ffffff" name="camRecord" FlashVars="uid=' + document.getElementById("videoname").value + '&url=' + document.getElementById("recordurl").value + '"  style="display:block;" src="' + CONFIG['misc_path'] + '/flash/Videocam1.swf" type="application/x-shockwave-flash" allownetworking="all" /></div>';
			$("[name='postCmaRecord']", _self.webposts).parent().before(camRecord);
			$("[name='postCmaRecord']", _self.webposts).remove();
			window.camComplete = function(w, h, name) {
				/*
				 document.getElementById("hd_v_w").value=w;
				 document.getElementById("hd_v_h").value=h;
				 document.getElementById("hd_v_name").value=name;
				 */
				$("[name='hd_v_name']").val(name);
				$("[name='hd_v_h']").val(h);
				$("[name='hd_v_w']").val(w);
				_self.videoData = {};
				_self.videoData.hd_v_w = w;
				_self.videoData.hd_v_h = h;
				_self.videoData.hd_v_name = name;
				$("body").data("videoData", _self.videoData);
			}

		}
		//提交
		//$("[name='btn_sendInfo']", _self.webposts).unbind("click");
		$("[name='btn_sendInfo']", _self.webposts).bind("click", function() {
			var vId = $("#videoId").val();
			if(self.isCurrent && $(".yearNum").val() == "") {
				$.alert("请填写公元前年份", "提交失败！")
				return false;
			}
			function videoManger(response) {
				if (response) {
					var status = response.status;
					var result = response.data;
					switch (parseInt(status)) {
						case 1:
						{
							var data = {content:$("[name='txt_videoText']:visible").val(), type:'video', vid:result.vid, videourl:result.videourl, imgurl:result.imgurl, url:result.url, height:result.height, width:result.width, web_id:WEB_ID};
							_self.getDate();
							$.extend(_self.argData, data);
							_self.post();
						}
							;
							break;
						case 2:
						{
							$(this).popUp({
								width:450,
								title:'提示!',
								content:'<div style="padding:10px">' + response.info + '</div>',
								buttons:'<label class="uiButton uiButtonConfirm"><input type="button" value="确定" class="callbackBtn" /></label>',
								mask:true,
								maskMode:true,
								callback:function() {
									location.reload();
								}
							});
							_self.resetInput();
						}
							break;
						case 3:
							$(this).popUp({
								width:450,
								title: '提示！',
								content: '<div style="padding:10px">' + response.info + '</div>',
								buttons:'<label class="uiButton uiButtonConfirm"><input type="button" value="确定" class="callbackBtn" /></label>',
								mask: true,
								maskMode: true,
								callback: function() {
									location.reload();
								}
							})
							break;
					}
				}
			}

            if ($('.postVideoCam:visible', _self.webposts).length > 0) {
                _self.getDate();
                _self.videoData = $("body").data("videoData");
				$.ajax({
					url:mk_url("wvideo/videoapi/save_makevideo"),
					dataType:"jsonp",
					type:"GET",
					data:$.extend({hd_v_w:_self.videoData.hd_v_w, hd_v_h:_self.videoData.hd_v_h, hd_v_name:_self.videoData.hd_v_name, txtdesc:$("[name='txt_videoText']:visible").val(), web_id:WEB_ID},  _self.argData),
					success:function(result) {
						videoManger(result);
					},
					error:function() {
						alert("网络错误!")
					}
				});
				$("body").removeData("videoData");
			}

			if (vId) {
				_self.getDate();
				$.ajax({
					url:mk_url("wvideo/videoapi/add_video", {web_id: WEB_ID}),
					dataType:"jsonp",
					type:"GET",
					data:$.extend({vid:vId, txtdesc:$("[name='txt_videoText']:visible").val(), info:$("#hd_info").val()}, _self.argData),
					success:function(result) {
						videoManger(result);
					},
					error:function() {
						alert("网络错误!")
					}
				});
			} else {
				if ($('#recordVideoPanel').css('display') == 'none') {
					$(this).popUp({
						width:450,
						title:'提示!',
						content:'<div style="padding:10px">请先上传视频!</div>',
						buttons:'<span class="popBtns blueBtn closeBtn">确定</span>',
						mask:true,
						maskMode:true
					})
				}
			}
		});
	},
	siderClick:function(arg) { // a年份  data 后的json
		var _self = this;
		if (arg[1].status == 0) {
			return false;
		}
		$(window).off("scroll", _self._class.scrollChangeLoad);
//			var date = new Date(arg[1].data.ctime*1000);

//            var year = date.getFullYear();
//			var month = date.getMonth()+1;
		var $timePsBox;
		var timelineBar = arg[0].closest(".timelineBar");
		var $current = _self._class.cpu(["currentShowHide"], [timelineBar, arg[0]]);
		var time = $current.children().attr("time");


		var index, yearLast, year, month, _time;
		_time = arg[1].data.ctime;
		index = _time >= 0 ? 10 : 4;
		yearLast = _time.length - index;
		year = _time.slice(0, yearLast);
		month = _time.substr(yearLast, 2);
		var title = time + "年";
		var timeYM = year + "-" + parseInt(month);
		var $time_li, $timeYm_li;
		$timeYm_li = _self._class.timelineTree.find("li[time=" + timeYM + "]");
		$time_li = _self._class.timelineTree.find("li[time=" + time + "]");
		if ($timeYm_li.size() != 0) {
			$timePsBox = _self._class.timelineTree.find("li[time=" + timeYM + "]");
			$timePsBox.attr("value", "monthData");
			time = timeYM;

			$.each(arg[1], function(a, b) {
				_self._class.view([b.type], [$timePsBox, b, true]);
			});
		} else {
			if ($time_li.size() == 0) { // 不存在这个年份  需要创建一个新的标识
				var tempObj = timelineBar.find("a[time=" + time + "]");
				tempObj.parent().removeClass('current');
				tempObj.click();
				$timePsBox = $('li.time[time=' + time + ']');
//
//					$timePsBox = _self._class.view(["timelinePs1"],{time:time,title:title});
				_self._class.event(["removeLoading"], [$timePsBox.children("ul")]);
			} else {
				// 如果存在这个年份

				if (time != timeYM && $time_li.attr("Ymonth") == "true") {
					$timePsBox = _self._class.view(["timelinePs1"], {time:timeYM, title:year + "年" + month + "月"});
					_self._class.event(["removeLoading"], [$timePsBox.children("ul")]);
					time = timeYM;
				} else {
					$timePsBox = _self._class.timelineTree.find("li[time=" + time + "]");
				}
				$.each(arg[1], function(a, b) {
					_self._class.view([b.type], [$timePsBox, b, true]);
				});
			}

		}

		_self._class.cpu(["recodePsTimeTop"], [$timePsBox]);
		_self._class.cpu(["lay"], [$timePsBox.children("ul.content")]);

		//self.event(["newTimeAction"],[self.timelineTree]);

		//$(window).on("scroll",self.scrollChange);
		_self._class.event(["changeSize"], [$timePsBox]);
		_self._class.event(["timelineBoxHover"], [$timePsBox]);
		_self._class.plug(['commentEasy'], [$timePsBox]);
		_self._class.plug(["tip_up_right_black", "tip_up_left_black"], [$timePsBox]);
		var a = $timePsBox.find("a[name=" + time + "]");  //得到时间轴psTime 锚点坐标
		$("html,body").animate({scrollTop:$timePsBox.offset().top - 165}, 200);
		$(window).on("scroll", _self._class.scrollChangeLoad);
	},
	sideBar:function(arg) {
		var year = arg[1].getFullYear();
		var month = arg[1].getMonth() + 1;
		var date = arg[1].getDate();

		var $a = arg[0].find("a[time^=" + year + "]");

		var $lasta = arg[0].find("a[time]").last();
		var $oneA = arg[0].find("a[time]").eq(0);
		var $twoA = arg[0].find("a[time]").eq(1);
		if ($a.size() == 0) { // 说明这个年份时间线上面没有

			$.each(arg[0].find("a[time]"), function() {
				var time = $(this).attr("time");
				if (time.substr(0, 4) < year) {
					var $this = $(this).parent().clone().html("<a class='time' time='" + year + "'>" + year + "年</a>");
					$(this).parent().before($this);

					self.opts._class.cpu(["addTimelineSelect"], [year]);
					self.event(["siderClick"], [$this.find("a"), arg[2]]);

					return false;
				}
			});
			if (year < $lasta.attr("time")) {
				var $this = $lasta.parent().clone();
				$this.html("<a class='time' time='" + year + "'>" + year + "年</a>");
				$lasta.parent().after($this);
				self.opts._class.cpu(["addTimelineSelect"], [year]);
				self.event(["siderClick"], [$this.find("a"), arg[2]]);

				return false;
			}
		} else {
			//  时间线上面有

			var oneTime = $oneA.attr("time");
			var twoTime = $twoA.attr("time");

			if (oneTime.substr(0, 4) == year && oneTime.substr(5, oneTime.length) == month) {
				// 说明是当前月

				self.event(["siderClick"], [$oneA, arg[2]]);
				return false;

			}
			if (twoTime.substr(0, 4) == year && twoTime.substr(5, twoTime.length) == month) {
				// 说明是上一月
				self.event(["siderClick"], [$twoA, arg[2]]);
				return false;
			}


			if ($a.size() > 1) {
				$a = arg[0].find("a[time=" + year + "]");
			}
			// 排除当月和上一个月，肯定是年份了。 或者年份中某个月

			self.event(["siderClick"], [$a, arg[2]]);
		}
	}
};
/*
 var postbox = new CLASS_WEBPOSTBOX();
 postbox.init($('div.webpost'));
 */
window.getUid = function() {
	return document.getElementById("hd_sessionId").value;
}
window.camNontCam = function() {
	$("[name='noCam']").show();
	$("[name='postCmaRecord']").hide();
	$(".postVideoCam .imgText").hide();
}
$("#btn_Refresh").click(function() {
	location.href = location.href;
});
window.sendPhotoComplete = function(data) {
	var _self = this;
	var str = data;
	if (str.status) {
		_self.argData.type = "photo";
		_self.argData.fid = str.data.fid;
		_self.argData.picurl = str.data.picurl
		_self.argData.content = $("[name='txt_photoText']", _self.webposts).eq(_self.index).val();
		_self.argData.timestr = $(".html_date").data('time') || $(".html_date").val();
		_self.argData.note = str.data.note;
		_self.argData.web_id = WEB_ID;
		_self.getDate();
		_self.post();
		//inserData(str.data,true);
	} else {
		alert(str.msg);
	}
};

//禁用开启保存图片按钮
window.disable = function(boolen) {
	document.getElementById('distributeButton').disabled = boolen;
	var inputBol = $(".postImg").find("textarea").val() || $(".postImgCam").find("textarea").val();
	var inputLen = inputBol.length < 140;
	if (Boolean(boolen) || !inputLen) {
		$('#distributeButton').parent().attr("data", "false")
		$('#distributeButton').parent().addClass('disable');
	} else {
		$('#distributeButton').parent().attr("data", "true")
		$('#distributeButton').parent().removeClass('disable');
	}
}
function validateDateSel(e) {
	validateDate.call($("[name='yearNum']"), e);
}
function validateDate() {
	var regYear = /^[1-9]\d{0,3}$/;
	var thisVal = $.trim($(this).val());
	var ele = $(this);
	var thisYear = thisVal * $(this).next().val();
	if (!regYear.test(thisVal) && thisVal.length != 0) {
		//alert("无效的年份，请重新输入！");
		$(this).val('');
		$(this).focus();
	} else {
		$.djax({
			url:mk_url("webmain/timeline/getAliasOfDate"),
			data:{webId:WEB_ID, date:thisYear},
			dataType:"json",
			async:true,
			success:function(data) {
				if (data.status != 0) {
					ele.parent().next().find('input').first().val(data.data);
				}
			},
			error:function(data) {

			}
		});

	}
}

/* 发表框商品 */
/* inputHelper */
var webId = $(".web_id").val();
$.fn.createBtnDisabled = function(isDisabled) {
	if(!$(this)[0]) return;
	var parent = $(this).parent();
	if (isDisabled) {
		parent.attr("data", "false");
		parent.addClass('disable');
	} else {
		parent.attr("data", "true");
		parent.removeClass('disable');
	}
};

var inputHelper = function(inputObj, options) {
	var options = jQuery.extend({}, inputHelper.defaults, options),
		useMethodName = options.useMethodName;
	this.inputObj = inputObj;
	if(options.textTipObj) {
		this.unique = options.unique;
		this.textTipObj = options.textTipObj;
	}
	this.defaultValue = options.defaultValue || this.textTipObj.text();
	this[useMethodName](this.defaultValue);

};
inputHelper.unique = 0;
inputHelper.defaults = {
	defaultValue: undefined,
	textTipObj: null,
	useMethodName: 'setDefaultValue'
};
inputHelper.prototype = {
	createUnique: function(id) {
		this.textTipObj.attr("for", id);
		this.inputObj.attr("id", id);
	},
	setDefaultValue: function(value) {
		var inputObj = this.inputObj,
			oThis = this;
		if(value == "") return this;
		this.defaultValue = value;
		inputObj.val(oThis.defaultValue);
		inputObj.focus(function() {
			if($(this).val() == oThis.defaultValue) {
				$(this).val("");
			}
		}).blur(function() {
				if($(this).val() == "") {
					$(this).val(oThis.defaultValue);
				}
				oThis.validateValue();
			});
		return this;
	},
	setDefaultValue1: function(value) {
		this.createUnique(this.inputObj.attr("id") + "_" + inputHelper.unique++);
		var inputObj = this.inputObj,
			validataType = inputObj.attr("require-data"),
			tipTextObj = this.textTipObj,
			oThis = this;
		!value ? value = tipTextObj.text() : tipTextObj.text(value);
		inputObj.focus(function() {
			if(tipTextObj.text() == oThis.defaultValue && inputObj.val() == "") {
				tipTextObj.text("");
			}
		}).blur(function() {
				if(tipTextObj.text() == "" && inputObj.val() == "") {
					tipTextObj.text(oThis.defaultValue);
				}
				if(validataType) {
					if(oThis.validateValue(validataType)) {
						inputObj.parent().removeClass('error');
						//inputObj.parent().attr('error','0');
					} else {
						inputObj.parent().addClass('error');
						// inputObj.parent().attr('error','1');
					};
				}
			});
		return this;
	},
	getValue: function() {
		var value = this.inputObj.val();
		return (value != "") && (value != this.defaultValue) ? value : "";
	},
	validateValue: function(methodName) {
		var value = this.getValue();
		return this.validateRegValue(methodName,value);
	},
	validateRegValue:function(methodName,val){
		var value 	= val;
		return (function(methodName) {
			var validataFns = {
				isEmpty: function() { return value != "" },
				isLinks: function() { return /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig.test(value); },
				isNumber: function() { return /^\d+(?:\.\d+)?$/.test(value); },
				isPrice: function() { return /^\d{1,7}(?:\.\d{1,2})?$/.test(value); }
			};
			return validataFns[methodName]();
		})(methodName);
	},
	goodsRegValue:function(methodName,value){
		return this.validateRegValue(methodName,value);
	}
};
var goods_reg_fun	= inputHelper;

var mediaBox = function() {
	this.isBtnDisabled = false;
};

mediaBox.prototype.getTimeDate = function() { //暂定
	var timeData = {};
	if ($("[name='sel_christian']").val() == "0") {
		timeData.bc = 1;
		timeData.timestr = $("[name='txt_selectDate']").val();
	} else {
		timeData.bc = -1;
		if ($.trim($("[name='yearNum']").val()).length == 0) {
			$("[name='yearNum']").focus();
			return false;
		}
		timeData.timestr = parseInt($("[name='yearNum']").val()) * parseInt($("[name='sel_yearUnit']").val());
		if ($.trim($("[name='sel_month']").val()).length != 0 && $("[name='sel_yearUnit']").val() == 1) {
			timeData.timestr += '-' + $("[name='sel_month']").val();
		}
		if ($.trim($("[name='sel_Days']").val()).length != 0 && $("[name='sel_yearUnit']").val() == 1) {
			timeData.timestr += '-' + $("[name='sel_Days']").val();
		}
	}
	timeData.timedesc = $("[name='txt_explain']").val();
	return timeData;
};

mediaBox.prototype.getFormParams = function(inputObj) {
	var formParams = mediaBox.inputHelper.getValue(inputObj);
	if(jQuery.isArray(formParams))
		return formParams;
};
var goods_offset = 0;
var goodsHtml = '';
$.goodsHtml = function(data){
	goodsHtml = data;
};
mediaBox.prototype.sendPost = function(postData) {
	var that = this;
	var post_url	= "";
	if(postData.type=='goods' && $('#channel_goods_add').length>0){	// 商品添加与修改
			var type	= $('#channel_goods_add').attr('type');
			if(type=='edit_goods'){	// 修改
				var web_id	= "";
				var gid		= '';
				try{
					web_id	= CONFIG['web_id'];
					gid		= $('#channel_goods_add').attr('gid');
				}catch(e){}
				post_url	= mk_url("channel/goods_publish/goods_edit" , {"web_id":web_id,'gid':gid});
			}else{
				post_url	= mk_url("webmain/web/doPost");
			}
	}else{
		post_url	= mk_url("webmain/web/doPost");
	}
	$.djax({
		url: post_url,
		dataType: "jsonp",
		async: true,
		data: postData,
		success:function(data) {
			
			if (data.status == 1) {
				
				if(data.data.type=='goods'){	// 商品添加	//有用
					
					try{
						var web_id	= CONFIG['web_id'];
						if($('#channel_goods_add').length>0){
							
							url	= mk_url('channel/goods/alist',{"web_id":web_id});
							window.location.href	= url;
							return ;
						}
					}catch(e){}
				}
				
				that.goodsAppend(data ,postData);
				$("#distributeButton").parent().bind("click", function() {
					oThis.render();

				});
				// 初使化发表框
				try{
					$('#goodsDK').html(goodsHtml);
					mediaBox.goods.initialize();	// 初使化类
				}catch(e){}
				
			} else {
				$.alert('发布失败了，请稍后重试！');
			}

		},
		error:function(){}
	});
	

};

mediaBox.prototype.goodsAppend = function(data , postData){	//即时渲染
	var $content = $('.time').children("ul.content");
	var location = mk_url("main/index/index", {web_id: postData.web_id});
	if (goods_offset == 0) {
		sideClass = "sideLeft";
		goods_offset = 1
	} else {
		sideClass = "sideRight";
		goods_offset = 0
	}

	var timeData = [
		data.data.ymd.year,
		data.data.ymd.month,
		data.data.ymd.day,
		data.data.ymd.hour,
		data.data.ymd.minute,
		data.data.ymd.second
	];

	var faceSrc = $('#topUserAvatar').attr('src');
	var str = '<li name="timeBox" scale="true" id="' + data.data.goods.gid + '"  fid="' + data.data.fid + '" uid="' + data.data.uid + '" type="' + data.data.type + '" highlight="' + data.data.highlight + '" time="' + data.data.ctime + '" timeData ="' + timeData + '" class="undefined ' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><!--<div class="editControl hide"><span class="conWrap midLine tip_up_left_black " tip=""><a><i class="conResize"></i></a></span><span class="conWrap tip_up_right_black" tip="编辑或删除"><a><i class="conEdit"></i></a><ul class="editMenu hide"><li class="sepLine"></li><li name="delTopic"><i class="delTopic"></i>删除帖子...</li></ul></span></div>--><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + faceSrc + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + data.data.uname + '</a></div><div class="postTime"><a href="javascript:void(0)">' + data.data.friendly_time + '</a>';
	var msgname = data.data.title || "";
	var goods_bigimg = '',goods_minimg = '';
	var goods	= data.data.goods;
	var goods_size	= data.data.goods.img_size;
	bigImg =  data.data.goods.img;
	minImg = data.data.goods.thumb;
	
	
	for(i = 0 ; i < minImg.length ; i++){
		var init_w	= 379;
		var t 	= init_w;
		var h	= "";
		try{
			var bw	= cint(goods.img_size[i]['b']['w']);
			var bh	= cint(goods.img_size[i]['b']['h']);
			
			if( bw<t ){
				t = bw;
			}
			
			if(bh>0){
				if( bw>init_w ){
					var th	= bh / ( bw / init_w );
					var h 	= "height:"+th+"px;";	
				}else{
					h = "height:"+bh+"px;";	
				}
			}
		}catch(e){}
		
		goods_bigimg = '<li style="display:block;'+h+'" ><img  width="'+ t +'px" src="' + bigImg[i] + '" /></li>' + goods_bigimg;
		goods_minimg = '<li style="display:block;'+h+'" ><img  width="'+ t +'px" src="' + minImg[i] +'" /></li>' + goods_minimg;
		break;
	}
	var goods_url	= mk_url('channel/goods/goods_show',{"web_id":data.data.pid,"gid":data.data.goods.gid});
	str += '</div></div></div><div class="infoContent"><div class="goods_name">商品：<a href="'+goods_url+'" >'+ goods.goodsname +'</a></div><ul class="goods_bigimg">'+ goods_bigimg +'</ul>';
	str += '<div class="goods_entry" >';
		str += '<span class="goods_price" >售价<i>￥</i>'+ goods.saleprice +' </span>';
		str += '<span class="goods_gopay_span" ><a href="'+ goods.href +'" target="_blank" class="goods_gopay">立即购买</a></span>';
		str += '<div class="cr"></div>';
	str += '</div>';
	

	str += '</div><div class="commentBox pd" msgname="' + goods.name + '" commentObjId="' + goods.gid + '" pageType="goods" ctime="' + data.data.ctime + '" action_uid="' + data.data.uid + '" mk_url="'+goods_url+'" ></div></div></li>';

	/*
	str += '</div></div></div><div class="infoContent"><ul class="goods_bigimg">'+ goods_bigimg +'</ul><ul class="goods_minimg">'+ goods_minimg +'</ul><div class="goods_name">'+ postData.goodsname +'</div><p class="goods_price">售价<i>￥</i>'+ postData.saleprice +'</p><p><a href="'+ postData.href +'" target="_blank" class="goods_gopay">立即购买</a></p></div><div class="commentBox pd" msgname="'+ msgname +'" commentObjId=" arg[1].tid " pageType="web_topic" ctime=" arg[1].ctime " action_uid="arg[1].uid "></div></div></li>';
	*/
	
	if($content.children('li').length > 0){
		$content.children('li').eq(0).before(str);
	}else{
		$content.append(str);
	}

	$content.children('li').eq(0).find('.goods_bigimg li').eq(0).show();
	$content.children('li').eq(0).find('.goods_minimg li').eq(0).attr('class','goodsNowli');
	$('html,body').animate({scrollTop : $('#distributeButton').offset().top},400);

	var bigImg = $('.goods_bigimg');
	var minImg = $('.goods_minimg');
	minImg.each(function(m){
		_bigimg = $(this).parent().find('.goods_bigimg li');
		_minimg = $(this).find('li');
		_minimg.live('click',function(){
			e = $(this).index();
			$(this).attr('class','goodsNowli').siblings().attr('class','');
			bigImg.eq(m).find('li').eq(e).show().siblings().hide();
			return false;
		});
	});

};


function cint(value){						//  parseInt  转成数字  整型
	if( (!value))	return 0;
	var number	=  parseInt(value,10);
	if(isNaN(number)) return 0;
	return number;
}


mediaBox.prototype.reset = function() {
	$(".goods").find("form").each(function() {
		$(this).reset();
	})
}

mediaBox.prototype.setBtnDisabled = function(element) {
	$("#goodsDK").find("input").change(function(){
		if( $(this).val() != '' )
		{
			$(element).parents().removeClass("disable").addClass("active");
			
		}else
		{
			$(element).parents().removeClass("active").addClass("disable");
		}
	})
	
	if(this.isDisabled) {
		$(element).createBtnDisabled(false);
	} else {
		$(element).createBtnDisabled(true)
	}
};

mediaBox.inputHelper = {
	setDefault: function(inputObj, methodName) {
		inputObj.each(function() {
			new inputHelper($(this), {
				textTipObj: $(this).parent().find(".label"),
				useMethodName: methodName
			});
		});
	},
	getValue: function(inputObj) {
		var values = [];
		inputObj.each(function() {
			values.push($(this).val());
		});
		return values;
	}
};

mediaBox.loadPage = function() {};

/* 商品 */
mediaBox.goods = {
	addGoodsCount: 0,
	maxGoods: 4,
	initialize: function() {
		var oThis = this;
		this.backGroundText = ["","","",""];
		this.backGroundTextSelect = 0;
		this.obtainArry();
		this.radiusShowImgOn();
		
		
		this.goods = $(".goods");
		this.mediaItem = $("#mediaItem");

		/** 添加商品
		 * this.addGoodsButton = this.goods.find(".addGoodsButton");
		 *  this.goodsItemHtml = this.mediaItem.html();
		 */
		/* 传递参数 */
		this.postData = null;
		
		this.backGroundNum = 0;
		this.inputData = new Array();
		this.postImgsSmall = new Array();

		this.postImgsBig = new Array();
		this.postImgObj	= new Object();
		this.postTimeData = null;

		this.mediaBox = new mediaBox();
		/* 发布按钮 */
		this.mediaBox.isBtnDisabled = true;
		this.mediaBox.setBtnDisabled("#distributeButton");
		/*自定义按钮方法*/
		mediaBox.inputHelper.setDefault(this.goods.find(".mediaItem .text"), "setDefaultValue1");
		/** 添加商品
		 * this.addGoodsButton.bind("click", function(event) {
		 * event.preventDefault();
		 * oThis.addGoods();
		 * });
		 */
		 if($("#goodsDK")[0]){
			this.uploadFile();
			$("#distributeButton").parent().bind("click", function() {

 				oThis.render();
				if(oThis.backGroundNum == 0)
				{
					$('#distributeButton').parent().unbind('click'); 
				}
			});
		}

		
		this.radiusCheck();//设定单选，删除，更改按钮的功能；
	
	},
	obtainArry: function(){
		var i = 0,//用于数组计数
		j = "",
		_self = this,
		indexInput = $("input:[name=cover]").index($("input[name=cover]:checked"));
		$('.uploadWrap').each(function(){
			/* _self.backGroundText[i] = $(this).attr() */
			if($(this).attr("style")){
				var j = $(this).attr("style");
				j = j.replace("background: url('","");
				j = j.replace("') no-repeat scroll center center transparent;","");
				_self.backGroundText[i] = j;			
			}
			i++;
		})
		if(indexInput != -1){
			_self.backGroundTextSelect = indexInput;
		}else{
			_self.backGroundTextSelect = 0;
		}
		

	},
	//左边框出现按钮绑定的图片
	radiusShowImgOn: function(){
		
		var checkedRadio = $("input[type='radio']:checked"),
		_self = this,
		chenckradus = this.backGroundText[_self.backGroundTextSelect];
		re = new RegExp("ts.jpg","g");
	
		if(chenckradus != "")
				
			{	
				chenckradus = chenckradus.replace(re,"f.jpg");		
				$(".rightPhoto").html("<div class='imgText'><img src='"+ chenckradus +"'/></div>");
			}
			
	},
	//添加一个给单选按钮绑定单选功能的JS
	radiusCheck: function(){
		var self = this,
		allradus = $("input:[name=cover]");
		//re = new RegExp("ts.jpg","g"); chenckradus = chenckradus.replace(re,"f.jpg");
		
		var alreadyRadus = $("input[name=cover]:checked");
		allradus.click(function(){
			var checkedBac = $(this).parents(".photoCover").find(".uploadWrap").attr("style"),//父亲元素的背景内容；
			re = new RegExp("ts.jpg","g"),_self = this;	
			if (checkedBac == undefined)//背景层没有内容，代表没有图片可供选择
			{
				$.alert("没有图片供你选择");
				if ( alreadyRadus ){
					alreadyRadus.attr('checked',true);
					
					$(this).attr('checked',false);
					return;
				}
				
			}else{
				
				$(this).attr('checked',true);
				self.backGroundTextSelect = allradus.index($(this));

				self.radiusShowImgOn();
				
				allradus.each(function(){
					

					if( allradus.index($(this)) != allradus.index($(_self)) ){
						
						$(this).attr('checked',false);
					}
				})
				
				
			
			}
			
			alreadyRadus = $("input[name=cover]:checked");
		});
		//删除按钮功能实现
		if($(".shopping")[0]){
			$("ul.EditEase .relative").click(function(){
				$(this).parents("span.uploadWrap").attr("style","");
				$(this).parents("span.uploadWrap").attr("is_onload","0");
				$(this).parents("span.uploadWrap").find("ul.EditEase").hide();
				$(this).parents("span.uploadWrap").find(".fileUpload").attr("style","");
				self.postImgObj == null;
			})
			//实现编辑按钮位置
			
			//鼠标经过显示删除编辑按钮
		$("span.uploadWrap").hover(function(){
				if($(this).attr("is_onload") == 1){ 
					$(this).parents(".photoCover").find(".EditEase").show();
				}
			},function(){
				if($(this).attr("is_onload") == 1){ 
					$(this).parents(".photoCover").find(".EditEase").hide();
				}
			});
		}
	},
	addGoods: function() {
		this.addGoodsCount++;
		var mediaItem = this.mediaItem;
		if(this.addGoodsCount < this.maxGoods) {
			mediaItem.append(this.goodsItemHtml);
			mediaBox.inputHelper.setDefault(mediaItem.find(".mediaItem .text"), "setDefaultValue1");
		}
	},
	uploadFile: function() {
		var oThis = this;
		if(!window.uploadCallback) {
			window.uploadCallback = {
				success: function(response, successElm) {
					var sel = successElm;
					if($('#goodsDK')[0]){
						if($('.shoppingEdit')[0]){
							successElm = $('.addPhoto').find('.uploadWrap').eq(successElm);
						}else{
							successElm	= $('#goodsDK').find('.uploadWrap').eq(successElm);
						}
						var data = response;
						var msg = data.msg;
						var img_m = msg.img_url.img_ts;
						
						var url = msg.groupname + "/" + msg.filename + "_";
						successElm.css({
							"background": "url(" + img_m + ") no-repeat center center"
						});
						oThis.backGroundText[sel] = img_m;
						if($(".shopping")[0])
						{
							successElm.attr("is_onLoad","1");
							successElm.find(".fileUpload").attr("style","width:auto;height:auto;z-index:2200;cursor:pointer;position:relative;left: -135px;")
							
						}

						oThis.postImgsSmall.push(url + "s." + msg.type);
				
						oThis.postImgsBig.push(url + "b." + msg.type);
						oThis.postImgObj[successElm.parent().attr("lang")] =	new Object();
						oThis.postImgObj[successElm.parent().attr("lang")] = msg;
					}
				},
				error: function() {
					$.alert('网络错误');
				}
			};
		}

		
		this.goods.find(".formUpload").each(function() {
			
			var oo_this	= this;
			jQuery.uploader(this);
		})
	},
	render: function() {
		this.inputData = this.mediaBox.getFormParams(this.mediaItem.find(".mediaItem .text"));
		this.postTimeData = this.mediaBox.getTimeDate();
        if(this.goods.css("display") == "block"){
            this.sendPost();
		}
	},
	/*添加一个设为封面的参数*/
	firstCoverCheck: function(){
		
		return $('input[name="cover"]').index($('input[name="cover"]:checked'));
	},
	sendPost: function() {
		
		var oThis	= this;
		/**
		 * require params
		 * 商品名称  goodsname
		 * 商品链接  href
		 * 商品价格  saleprice
		 * 商品图片  img
		 * =商品缩略图   thumb
		 */
		this.postData = {
			type: "goods",
			web_id: webId,
			goodsname: this.inputData[0],
			href: this.inputData[1],
			saleprice: this.inputData[2],
			img: oThis.obj_json(this.postImgObj),
			thumb: this.postImgsSmall.join(","),
			catid : $('#checkedPP').attr('catid'),
			brand : $('#checkedPP').attr('brand'),
			brand_name : $('#checkedPP').attr('brand_name'),
			timestr: this.postTimeData.timestr,
			timedesc: this.postTimeData.timedesc,
			bc: this.postTimeData.bc,
			firstCover: this.firstCoverCheck()
		};



			// 验证图片
			var is_img	= false;
			for( obj in this.postImgObj){
					is_img	= true;
			}
			if(!is_img){
				$.alert('请上传商品图片');
				oThis.backGroundNum = 1;
				return ;
			}
			if( this.trim(this.postData.goodsname)=='' ){
				$.alert('请输入商品名');
				oThis.backGroundNum = 1;
				return ;
			}
			
			if( this.calculation(this.postData.goodsname) >= 100){
				$.alert('您输入的名字过长，最长50个中文或100个英文');
				oThis.backGroundNum = 1;
				return ;
			}

			if( this.trim(this.postData.href)=='' ){
				$.alert('请输入商品的链接地址');
				oThis.backGroundNum = 1;
				return ;
			}else if( ! goods_reg_fun.prototype.goodsRegValue('isLinks',this.postData.href)){
				$.alert('商品的链接地址不正确');
				oThis.backGroundNum = 1;
				return ;
			}
			if(this.trim(this.postData.saleprice)==''){
				$.alert('请输入商品的价格');
				oThis.backGroundNum = 1;
				return ;
			}else if(! goods_reg_fun.prototype.goodsRegValue('isPrice',this.postData.saleprice) ){
				$.alert('商品的价格格式不正确');
				oThis.backGroundNum = 1;
				return ;
			}
			
			if( this.trim(this.postData.brand)=='' ){
				$.alert('请输入品牌');
				oThis.backGroundNum = 1;
				return ;
			}else if( this.trim(this.postData.brand)=='0' && this.trim(this.postData.brand_name)==''){
				$.alert('请输入品牌');
				oThis.backGroundNum = 1;
				return ;
			}
		
		if(this.goods.css("display") == "block"){
			this.mediaBox.sendPost(this.postData);
			

		}
	},
	obj_json:function(o){  						// 对象转 json 字符串   0 必须是对象
		var r = [];  
		if(typeof o =="string") return "\""+o.replace(/([\'\"\\])/g,"\\$1").replace(/(\n)/g,"\\n").replace(/(\r)/g,"\\r").replace(/(\t)/g,"\\t")+"\"";  
		if(typeof o =="undefined") return "\"\"";  
		if(typeof o == "object"){  
			if(o===null) return "null";
			else if(!o.sort){  
				for(var i in o){  r.push("\""+i+"\""+":"+this.obj_json(o[i])); }
				r="{"+r.join()+"}"; 
			}else{
				for(var i =0;i<o.length;i++)  r.push(this.obj_json(o[i])) ; 
				r="["+r.join()+"]";
			}  
			return r;  
		}else if(typeof o == "number"){
			return o;
		}else if(typeof o == "boolean"){
			if(o){
				return 1;
			}else{
				return 0;	
			}
		}else{
			return "\"\"";
		}  
		return o.toString();
	},
	trim:function(str){							// 去掉前后空格 	
		if(!str)	return '';
		if(str==undefined) return '';
		if( ! isNaN(str) ) return str;
		return str.replace(/(^\s*)|(\s*$)/g, "");
	},
	//计算中英文字符串的长度
	calculation : function(str){
		var relLength = 0,len = str.length,charCode = -1;
		for(var i = 0; i < len;i++){
			charCode = str.charCodeAt(i);
			if(charCode >= 0 && charCode <= 128){
				relLength += 1;
			}else{
				relLength += 2;
			}
		}
		return relLength;
	}
};


/*
$(function() {
	var locals = {};
	var dataPage = $("#data-page");

	$(".webpost").live("click", function(event) {
		var target = $(event.target).parent(),
			page = target.attr("data-page");
		if(!locals[page]) {
			if(target.prop("tagName").toLowerCase() == "li") {
				dataPage.addClass("data-page-" + page);	
				$.ajax({
					url: mk_url('channel/publish/loadPostbox'),
					method: "POST",
					data: {page: page,web_id: webId },
					dataType: 'jsonp',
					success: function(response) {
						var html = $(response.data);
						
						dataPage.find("." + page).html(html);
						switch(page){
							case 'group':
								$.groupHtml(response.data);
								break;
							case 'goods':
								$.goodsHtml(response.data);
								break;
							case 'dish':
								$.dishHtml(response.data);
								break;
						};
						html.show();
						locals[page] = html;		
					},
					error : function(){
						layer_alert('加载失败！请重试');
					}
				});		
			}
		}
	})
});*/

mediaBox.goods.initialize();
try{
	if(init__goods_img){
		mediaBox.goods.postImgObj 	= eval("(" + init__goods_img + ")");
	}
}catch(e){
	// 	没有变量 就不赋值
}
/*
 * 选择品牌(树形结构)
 * date:2012.7.19
 * By 贤心(xxj)
 */
 
var goods = {
	init : function(){
		_gLen = $('#goodsDK').length;
		_gLen <= 0 || this.getPingpai();
		this.goods_brand_click();
		this.customInput();
	},
	calculation : function(str){
		var relLength = 0,len = str.length,charCode = -1;
		for(var i = 0; i < len;i++){
			charCode = str.charCodeAt(i);
			if(charCode >= 0 && charCode <= 128){
				relLength += 1;
			}else{
				relLength += 2;
			}
		}
		return relLength;
	},
	customInput: function(){
		

		var _self = this;
		$('#goods_add_brand').live('keyup',function(){
			var goods_add_brand = $('#goods_add_brand');
			var val = goods_add_brand.val();
			if (_self.calculation(val) >= 20){
				goods_add_brand.css("color","red");
			}else if((_self.calculation(val) < 20)){
				goods_add_brand.css("color","#333333");
			};
		})
	},
	getPingpai : function(){
		var ppHTML = '<div id="pingpaiBox"><div class="ppbox_left" id="ppbox_left" style="height:180px;"></div><div class="ppbox_right" id="ppbox_right" style=""><div id="ppcontent"><ul></ul></div><div class="goods_add_brand_div clearfix" ><div>添加自定义品牌：</div> <label class="goods_add_brand_label" for="goods_add_brand">最多输入20个</label><input class="goods_add_brand" id="goods_add_brand" type="text" name="goods_add_brand" /></div></div></div>';					
		
		$("#goods_add_brand_checkbox").attr('checked',false);
		$(".goods_add_brand_label").show();
		$(".goods_add_brand").val('');
//		$('.goods_add_brand').attr('disabled','disabled');	//
		
		var goods = {
			checkPP : function(){
				othis = this;
				var _dl = '',_dd = [];
				var pp_id = $('#ppweb_id').text();						
				var _url = mk_url('webmain/goods/get_category_tree');											
				$.ajax({
					url : _url,
					method: "POST",
					data : {catid : pp_id},
					dataType: 'jsonp',
					success : function(data){
						//var data = $.parseJSON(data);
						var D = data.data;

						switch(D.level){
							case '2':										
								for(a in D.info){
									_dd[a] = '';
									for(b in D.info[a].child){									
										_dd[a] = _dd[a] + '<dd id="' + D.info[a].child[b].id + '" level="' + D.info[a].child[b].level + '" has_son="'+ D.info[a].child[b].has_son +'"><a href="javascript:;">'+ D.info[a].child[b].name +'</a></dd>';										
									}
									_dl =  _dl + '<dl><dt id="' + D.info[a].id + '" level="' + D.info[a].level + '" has_son="'+ D.info[a].has_son +'" class="DK_tree treeMinus"><a href="javascript:;">'+ D.info[a].name +'</a></dt>'+ _dd[a] +'</dl>'
								}
								var _content = '<h3 id="' + D.id + '" level="' + D.level + '" has_son="'+ D.has_son +'" class="DK_tree treeMinus"><a href="javascript:;">'+ D.name +'</a></h3>' + _dl + '</dl>';
								break;	
								
							case '3':
								for(a in D.info){										
									_dd = _dd + '<dd style="background-position: 20px -15px; padding-left: 3em;" id="' + D.info[a].id + '" level="' + D.info[a].level + '" has_son="'+ D.info[a].has_son +'"><a href="javascript:;">'+ D.info[a].name +'</a></dd>';										
								}	
								var _content = '<dl ><dt style="padding-left:15px; background-position: 0 -15px;" id="' + D.id + '" level="' + D.level + '" has_son="'+ D.has_son +'" class="DK_tree treeMinus"><a href="javascript:;">'+ D.name +'</a></dt>'+ _dd +'</dl>';
								break;
							case '4':
								var _content = '<dd id="' + D.id + '" level="' + D.level + '" has_son="'+ D.has_son +'"><a href="javascript:;">'+ D.name +'</a></dd>';
								break;		
						}
																						
						$('#ppbox_left').html(_content);
					}
				});
				$('#ppbox_right').hide();		
				$(this).popUp({
					width : 407,
					height : 200,
					title : '选择品牌',
					content : ppHTML,
					callback : function(){	
						othis.postBtn();	
					}
				});
			},
			postBtn : function(){	//确认选择
				if($('#goods_add_brand').val() !== ''){	// 添加产品
					var brand_name = $("#goods_add_brand").val();
					$('#checkedPP').attr({'brand' : '0','brand_name' : brand_name,'title':brand_name});
					$('#checkedPP').html(brand_name);
				}else if($('#ppcontent').find('input').length > 0){
					var _inputC = $('#ppcontent input:checked');
					var pp = {
						name : _inputC.val(),
						catid : _inputC.attr('catid'),
						brand : _inputC.attr('brand')
					}
					$('#checkedPP').html(pp.name);
					$('#checkedPP').attr({'catid' : pp.catid , 'brand' : pp.brand,'title':pp.name,'brand_name':''});
				}else{
					$('#checkedPP').html('无品牌');
					$('#checkedPP').attr({'catid' : '' , 'brand' : ''});
				}
				$.closePopUp();
			}
		};

		$('#goods_pinpai').live('click',function(){
			$("#goods_add_brand_checkbox").attr('checked',false);
			$(".goods_add_brand_label").show();
			$(".goods_add_brand").val('');
			goods.checkPP();
		});
		$('#yesBtn').live('click',function(){
			goods.postBtn();
		});
		$('.DK_tree').live('click',function(){
			var _that = $(this);
			var _class = $(this).attr('class');					
			if(_class.indexOf('treePlug') == -1){
				_that.addClass('treePlug');
				_that.parent('dl').find('dd').hide();
				if(_that.attr('has_son') == 1){
					_that.parent().find('dl').hide();
					_that.parent().find('.DK_tree').addClass('treePlug');
				}						
			}else{
				_that.removeClass('treePlug');
				_that.parent().find('dd').show();
				if(_that.attr('has_son') == 1){
					_that.parent().find('dl').show();
					_that.parent().find('.DK_tree').removeClass('treePlug');
				}
			}
		});
		$('#ppbox_left').find('dd').live('click',function(){							
			var dd_id = $(this).attr('id');
			$.djax({
				method: "GET",
				url : mk_url('webmain/goods/get_brand?catid=' + dd_id),
				loading : true,
				dataType: 'jsonp',
				success : function(data){
					$('#checkedPP').attr('catid', dd_id);
					
					var data = data.data;
					var ulHTML = '';
					for(i in data){
						ulHTML = ulHTML + '<li class="clearfix"><input type="radio" name="goods_ppradio" id="goods_'+data[i].gbid+'" catid="'+ dd_id +'"  value="'+ data[i].name +'" brand="'+ data[i].iid +'"  /><label class="goods_label_height" for="goods_'+data[i].gbid+'" >'+ data[i].name +'</label></li>';
					}
					if(data.length == 0){
						$('#ppcontent ul').html('<ul><li>该类别无对应品牌</li></ul>')
					}else{
						$('#ppcontent ul').html('<ul>'+ ulHTML +'</ul>')
					}
				}
			});
		});
	},
	goods_brand_click:function(){
		var self = this;
		
		$(".goods_add_brand").live('click',function(){
			$('.goods_add_brand_label').hide();
			$('.goods_add_brand').focus();
//			$('.goods_add_brand').attr('disabled','');
		});

		$(".goods_add_brand").live('blur',function(){
			if( self.trim($(this).val()) =='' ){
				$('.goods_add_brand_label').show();
//				$('.goods_add_brand').attr('disabled','disabled');
			}
		});
	},
	trim:function(str){							// 去掉前后空格 	
		if(!str)	return '';
		if(str==undefined) return '';
		if( ! isNaN(str) ) return str;
		return str.replace(/(^\s*)|(\s*$)/g, "");
	}
};
goods.init();

$.goodsHtml($("#goodsDK").html());