/**
 * @author:    lincy(old author wangxd)
 * @modify:    qiuminggang
 * @created:   2012/02/10
 * @version:   v1.0
 * @desc:
 */
var sharePreviewVideoisOk = false;
var sharePreviewVideoData = null;
var sharePreviewVideoInput = null;
function CLASS_POSTBOX(arg) {
	this.opts = arg;
	this.photoDatas = [];
	this.photoStatus = "";
	this.photoData = null;
}
CLASS_POSTBOX.prototype = {
	init:function(postBox) {
		var self = this;
		this.postBox = $(postBox);
		this.html_date = $(".html_date");
		this.permission = $("input[name=permission]");
		this.distributeInfoBody = this.postBox.find('[data-id="distributeInfoBody"]');
		this.attachPhotoIntroduce = this.postBox.find('[data-id="attachPhotoIntroduce"]');
		this.distributeInfoBodyFooter = this.postBox.find('div.footer');
		this.$face = this.distributeInfoBodyFooter.find("#face a");
		$(".Js_textArea").each(function() {
			var myStatusTextArea = new Textarea.msgTip(this, {
				maxlength:140,
				notMedia: true,
				textareaProps:{
					"class":"shareInfoCont msg"
				},
				textareaStyles:{
					overflow: "hidden",
					height: 19
				},
				button:{
					id:self.postBox.find('[data-id="distributeButton"]').parent()
				}
			});
		});
		$(".Js_mediaArea").each(function() {
			var mediaArea = new Textarea.msgTip(this, {
				maxlength:140,
				textareaProps:{
					"class":"shareInfoCont msg"
				},
				notMedia: false,
				textareaStyles:{
					overflow:"hidden",
					height:50
				},
				button:{
					id:self.postBox.find('[data-id="distributeButton"]').parent()
				}
			});
		});
		this.myStatusTextArea = this.postBox.find('[data-id="myStatusTextArea"]');
		//DKLayerHider.addHideItem('#destinationCircle');
		this.event(["status"]);
		this.event(["photo"]);
		this.event(["button"]);
		this.event(["video"]);
		this.event(["distributeInfoBody"], [this.distributeInfoBody]);
		this.plug(["tip_up_right_black"], [this.distributeInfoBody]);
		$("#TopPostArea,.dk_calendar,#calendar_block").bind('mousedown', function(e) {
			e.stopPropagation();
		});
		$(document).bind('mousedown', function (e) {
			if($(".dk_calendar").find(e.target).size() !== 0 ) {
				return;
			}
			$(".TopPostBox").hide().prev(".hide").hide();
		});
        this.$face.face($("#" + this.$face.attr("data-id")));
	},
	view:function(method, arg) {
		var self = this;
		var _class = {
			status:function(arg) {

			},
			photo:function(arg) {

			},
			video:function(arg) {

			},
			life:function(arg) {

			}

		}
		$.each(method, function(index, value) {
			if (value) {
				return _class[value](arg);
			}
		});
	},
	//获得flash对象
	getID:function(swfID) {
		if (navigator.appName.indexOf("Microsoft") > -1) {
			return window[swfID];
		} else {
			return document[swfID];
		}
	},
	createBtnAble:function(bool) {
		var uiButton = self.postBox.find('[data-id="distributeButton"]');
		if(!uiButton[0]) return;
		if (bool) {
			uiButton.parent().removeClass('disable');
		} else {
			uiButton.parent().addClass('disable');
		}
	},
	cpu:function(method, arg) {
		var self = this;
		var func = null;
		var _class = {
			psTime:function(arg) {
				$.each(arg[0].find("li[name=time]"), function() {
					var id = $(this).attr("id");
					var scale = ($(this).offset().top + 15) + ($(this).height() - 15);
					self.psTime[id] = ($(this).offset().top) + "-" + scale;
				});

			}

		}
		$.each(method, function(index, value) {
			if (value) {
				func = _class[value](arg);
				return func;
			}
		});
		return func;
	},
	iefix:function(method, arg) {
		var self = this;
		var _class = {
			returnScale:function(arg) {
			}
		}
		$.each(method, function(index, value) {
			if (value) {
				return fn = _class[value](arg);
			}
		});
		return fn;
	},
	event:function(method, arg) {
		var self = this;
		var _class = {
			status:function(arg) {
				self.postBox.find('[data-id="distributeMsg"]').find('textarea.shareInfoCont').focus(function() {
					self.distributeInfoBodyFooter.show();
					self.plug(["msg"], [self.postBox.find('[data-id="distributeMsg"]')]);
					//self.createBtnAble(true);
				});
				//$("#myStatusTextArea").canNotBeEmpty($("#distributeButton").parent());
			},
			photo:function() {
				self.postBox.find('[data-id="upoadPhotoFromLocal"]').click(function() {
					self.postBox.find('[data-id="photoUploadWay"]').hide();
					self.postBox.find('[data-id="photoFileOption"]').show();
					self.postBox.find('[data-id="uploadPhotoPanel"]').show();
					self.postBox.find('[data-id="attachPhotoIntroduce"]').show();
					self.plug(["msg"], [self.postBox.find('[data-id="photoFileOption"]')]);
					$.faceInsert.insert(self.postBox.find('[data-id="attachPhotoIntroduce"]'));
					self.postBox.find('[data-id="distributeButton"]').parent().attr("data", "true");
					if (!self.photoData) {
						self.createBtnAble(false);
						self.postBox.find('[data-id="distributeButton"]').parent().attr("data", "false");
					} else if(self.postBox.find('[data-id="attachPhotoIntroduce"]').val().length > self.postBox.find('[data-id="attachPhotoIntroduce"]').attr("tmaxlength")) {
						self.createBtnAble(false);
					} else {
						self.createBtnAble(true);
						self.postBox.find('[data-id="distributeButton"]').parent().attr("data", "true");
					}
					self.distributeInfoBodyFooter.show();
				});
				var miscpath = CONFIG['misc_path'];
				//@ 上传图片Ajax、flash(服务器、本地)地址
				var flashUrl = CONFIG['misc_path'] + "flash/plug-flash/jQuery-uploadify/uploadify.swf";
				var updataPic_URL = mk_url("album/api/upload");
				var type = $("#photoFileOption [name='type']").val();
				self.postBox.find('[data-id="uploadPhotoButton"]').uploadify({
					"uploader":flashUrl,
					"script":updataPic_URL,
					"method":'GET',
					"scriptData":{
//						'c':'api',
//						'm':'upload',
						"flashUploadUid":self.postBox.find('[data-id="flashuploaduid"]').attr("flashuploaduid"),
						'type':type
					},
					"cancelImg":miscpath + "img/system/icon_close_03.png",
					"buttonImg":miscpath + "img/system/icon_selectImg.png",
					"folder":miscpath + "temp",
					"fileExt":"*.jpg;*.jpeg;*.gif;*.png;",
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
						self.postBox.find('[data-id="flashuploaduid"]').height(0).css({"border":"none", "padding":"0px"});
						$("#flashuploaduid div").hide();
					},
					"onComplete":function(e, queueID, fileObj, response, data) {
						var res = eval('(' + response + ')');
                        var status = res.status;
                        var data = res.data;
						if (status == 1) {
							self.photoStatus = status;
							self.photoDatas.push(data['photo_id']);
							self.singlePhotoData = { "status": self.photoStatus, "photo_id": self.photoDatas.join(",") };
							self.postBox.find('[data-id="up_photo_success"]').show();
						} else {
							$(this).subPopUp({
								width:557,
								title:"上传图片时发生错误",
								content:'<div style="padding:15px; line-height:200%;"><strong>上传图片时发生错误，可能是由图片格式不正确或大小超过10M或服务器错误引起。</strong><br /><br /><span>端口支持的图片格式为:</span><ul style="list-style:inside circle;"><li>*.jpg</li><li>*.jpeg</li><li>*.gif</li><li>*.png</li></ul></div>',
								mask:true,
								maskMode:false,
								buttons:'<span class="popBtns closeBtn" id="uploadPic_cancel">确定</span>'
							});
							$("#flashuploaduid div").show();
							$("#flashuploaduid object").height(25);//隐藏选择图片按钮
							self.postBox.find('[data-id="flashuploaduid"]').height(50).css({"border":"none", "padding":"3px 10px"});
							self.postBox.find('[data-id="distributeButton"]').parent().attr("data", "false");
						}
					},
					"onCancel":function() {
						$("#flashuploaduid div").show();
						$("#flashuploaduid object").height(25);//隐藏选择图片按钮
						self.postBox.find('[data-id="flashuploaduid"]').height(50).css({"border":"none", "padding":"3px 10px"});
					},
					"onAllComplete": function() {
						self.photoDatas.length = 0;
						self.photoData = null;
						$.ajax({
							url: mk_url('album/api/uploadSavePhoto', {}),
							data: 'pids=' + self.singlePhotoData.photo_id + '&type=' + type + '&flashUploadUid=' + self.postBox.find('[data-id="flashuploaduid"]').attr("flashuploaduid"),
							dataType: "jsonp",
							success: function(data) {
								self.photoData = data;
								if(self.postBox.find('[data-id="attachPhotoIntroduce"]').val() !== "" && (self.postBox.find('[data-id="attachPhotoIntroduce"]').val().length > self.postBox.find('[data-id="attachPhotoIntroduce"]').attr("tmaxlength"))) {
									self.createBtnAble(false);
								} else {
									self.createBtnAble(true);
								}
								self.postBox.find('[data-id="distributeButton"]').parent().attr("data", "true");
							}
						});
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
							self.postBox.find('[data-id="photo_queueID"]').html('');
						}
					}
				});
				//摄像头拍摄照片
				self.postBox.find('[data-id="snapshotPhoto"]').click(function() {
					self.postBox.find('[data-id="photoUploadWay"]').hide();
					self.postBox.find('[data-id="attachCameraPhotoIntroduce"]').show();
					self.postBox.find('[data-id="snapshotPhotoFileOption"]').show();
                    $.faceInsert.insert(self.postBox.find('[data-id="attachCameraPhotoIntroduce"]'));
					self.plug(["msg"], [self.postBox.find('[data-id="snapshotPhotoFileOption"]')]);
					self.postBox.find('[data-id="photoFileOption"]').hide();
					self.distributeInfoBodyFooter.show();
					self.postBox.find('[data-id="distributeButton"]').parent().attr("data", "false");
					self.createBtnAble(false);
				});
			},
			video:function(arg) {
				function cancelUpload(){
					$(".flashContent").show();
					self.postBox.find('[data-id="uploadTips"]').show();
					self.postBox.find('[data-id="videoDescriptions"]').hide();
					self.postBox.find('[data-id="up_success"]').hide();
					self.postBox.find('[data-id="uploadState"]').hide();
					$(".flashContent div").show();
					$(".flashContent object").height("25px");
					$(".flashContent .txt_url").show().val("");
					$(".flashContent").height(65).css({"border":"1px solid #CCCCCC", "padding":"3px 10px"});
					if ($(".uploadifyQueueItem").first().length)
						self.postBox.find('[data-id="uploadify"]').uploadifyCancel($(".uploadifyQueueItem").first().attr("id").replace("uploadify", ""));
					self.postBox.find('[data-id="videoId"]').val("");
					self.postBox.find('[data-id="saveVideoInfomation"]').attr("disabled", true).addClass("disabled");
					self.postBox.find('[data-id="distributeButton"]').parent().attr("data", "false")
					self.createBtnAble(false);
				}
				/** 上传视频 **/
				var miscpath = CONFIG['misc_path'];
				/** 上传视频 **/
				videoUpload.AC_FL_RunContent({
					'appendTo' : document.getElementById("uploadify"),//flash添加到页面的容器
					'url' : $("#hd_video_upload_url").val()+'?appkey='+$("#hd_url").val()+'&mid=1',//上传到的url
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
									$.closePopUp();
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
									$('#distributeButton').parent().attr("data", "false");
									self.createBtnAble(false);
								}
							});
						});
					},
					"onComplete":function(data) {
						var str = eval('(' + data + ')');
						if(str.status == 1){
							$("#videoId").val(str.data);
							//$('.flashContent').hide();
							if($("#attachVIntroduce").val() !== "" && ($("#attachVIntroduce").val().length > $("#attachVIntroduce").attr("tmaxlength"))) {
								self.createBtnAble(false);
							} else {
								self.createBtnAble(true);
							}
							$('#distributeButton').parent().attr("data", "true");
							//console.log(videoUpload.thisMovie('flashvideoupload'));
							videoUpload.thisMovie('flashvideoupload').isJsComplete(true);//成功后与flash交互
						}else{
							//console.log(videoUpload.thisMovie('flashvideoupload'));
							videoUpload.thisMovie('flashvideoupload').isJsComplete(false);//失败后与flash交互
						}
					},
					"onCancel":function() {
						$(".flashContent object").height(25);//隐藏选择图片按钮
						$(".flashContent").height(50).css({"border":"1px solid #CCCCCC", "padding":"3px 10px"});
						$(".flashContent div").show();
						$('#distributeButton').parent().attr("data", "false")
						self.createBtnAble(false);
					},
					"onWarn":function(error) {
						//console.log(eo);
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
						self.createBtnAble(false);
						videoUpload.thisMovie('flashvideoupload').isJsOnAgain(true);
					}
				});
				/* 分享视频 */
				self.postBox.find('[data-id="shareVideo"]').click(function() {
					self.postBox.find('[data-id="videoUploadWay"]').hide();
					self.postBox.find('[data-id="recordVideoPanel"]').hide();
					self.postBox.find('[data-id="recordVideoPanel"]').hide();
					self.postBox.find('[data-id="uploadVideoFlashWrap"]').hide();
					self.postBox.find('[data-id="shareVideoPanel"]').show();
					self.postBox.find('[data-id="videoFileOption"]').show();
					self.plug(["msg"], [self.postBox.find('[data-id="videoFileOption"]')]);
					self.postBox.find('[data-id="distributeButton"]').parent().attr("data", "true");
				});

				self.postBox.find('[data-id="upoadVideoFromLocal"]').click(function() {
					self.postBox.find('[data-id="videoUploadWay"]').hide();
					self.postBox.find('[data-id="recordVideoPanel"]').hide();
					self.postBox.find('[data-id="recordVideoPanel"]').hide();
					self.postBox.find('[data-id="shareVideoPanel"]').hide();
					//self.postBox.find('[data-id="uploadVideoPanel"]').show();
					self.postBox.find('[data-id="uploadVideoFlashWrap"]').show();
					self.postBox.find('[data-id="videoFileOption"]').show();
					self.postBox.find('[data-id="noCam"]').hide();
                    $.faceInsert.insert((self.postBox.find('[data-id="attachVIntroduce"]')));
					self.plug(["msg"], [self.postBox.find('[data-id="videoFileOption"]')]);
					self.distributeInfoBodyFooter.show();
					self.postBox.find('[data-id="distributeButton"]').parent().attr("data", "true");
					if (self.postBox.find('[data-id="videoId"]').val() == "") {
						self.createBtnAble(false);
						self.postBox.find('[data-id="distributeButton"]').parent().attr("data", "false");
					} else if(self.postBox.find('[data-id="attachVIntroduce"]').val().length > self.postBox.find('[data-id="attachVIntroduce"]').attr("tmaxlength")) {
						self.createBtnAble(false);
					} else {
						self.createBtnAble(true);
					}
				});

				//摄像头拍摄照片
				self.postBox.find('[data-id="recordVideo"]').click(function() {
					self.postBox.find('[data-id="videoUploadWay"]').hide();
					self.postBox.find('[data-id="uploadVideoFlashWrap"]').hide();
					self.postBox.find('[data-id="shareVideoPanel"]').hide();
					self.postBox.find('[data-id="recordVideoPanel"]').show();
					self.postBox.find('[data-id="videoFileOption"]').show();
					$.faceInsert.insert(self.postBox.find('[data-id="attachMakeVideoIntroduce"]'))
					self.plug(["msg"], [self.postBox.find('[data-id="videoFileOption"]')]);
					self.distributeInfoBodyFooter.show();
					self.postBox.find('[data-id="distributeButton"]').parent().attr("data", "false");
					self.createBtnAble(false);
				});
			},
			button:function(arg) {
				function resetVideoInput(type) {
					self.postBox.find('[data-id="'+ type +'"]').val("");
					self.postBox.find('[data-id="videoId"]').val("");
					self.postBox.find('[data-id="hd_info"]').val("");
					self.postBox.find('[data-id="up_success"]').hide();
					videoUpload.thisMovie('flashvideoupload').isJsPublish();
					$(".flashContent").show();
				}
				function videoManger(response,type) {
					if (response) {
						var status = response.status;
						var result = response.data;
						switch (parseInt(status)) {
							case 1: {
								var data = {
									content: self.postBox.find('[data-id="'+ type +'"]').val(),
									type:'video',
									timestr:self.postBox.find('[data-id="date_a"]').val(),
									permission:$("[name='permission']").val(),
									vid:result.vid,
									imgurl:result.imgurl,
									height:result.height,
									width:result.width
								};
								if (self.opts.friend) {
									data.permission = 4;
								}
								self.model(["video"], [data, function(data) {
									if (data.status) {
										//alert("发表成功!");
										$(".flashContent div").show()
										$(".flashContent object").height(25);//隐藏选择图片按钮
										$(".flashContent").height(50).css({"border":"1px solid #CCCCCC", "padding":"3px 10px"});
										$.closePopUp();
										$(this).popUp({
											width:450,
											title:'提示!',
											content:'<div style="padding:10px">发表成功</div>',
											buttons:'<label class="uiButton uiButtonConfirm"><input type="button" value="确定" class="callbackBtn" /></label>',
											mask:true,
											maskMode:true,
											callback:function() {
												$.closePopUp();
												self.createBtnAble(false);
											}
										});
//                                        $(".s_msg:visible").click();//不需要切换tab
									} else {
										$.closePopUp();
										$(this).popUp({
											width:450,
											title:'提示!',
											content:'<div style="padding:10px">发表失败</div>',
											buttons:'<label class="uiButton uiButtonConfirm"><input type="button" value="确定" class="callbackBtn" /></label>',
											mask:true,
											maskMode:true,
											callback:function() {
												$.closePopUp();
											}
										});

									}
									$(".flashContent div").show()
									$(".flashContent object").height(25);//隐藏选择图片按钮
									$(".flashContent").height(50).css({"border":"1px solid #CCCCCC", "padding":"3px 10px"});
									var liNow = self.postBox.find('[data-id="timelineTree"]').children("li.time");

									var time = new Date(data.data.ctime * 1000);
									if (self.opts.friend) {
										self.opts._class.view(["video"], [self.opts.box, data.data, "afterFirst"]);
										self.opts._class.cpu(["lay"], [self.opts.box]);
										self.opts._class.plug(['commentEasy'], [self.opts.box.children().eq(1)]);
									} else {
										self.cpu(["sideBar"], [self.opts._class.sideArea, time, data]);
									}
									resetVideoInput(type);
									//
								}]);
							};
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
								$(".flashContent div").show()
								$(".flashContent object").height(25);//隐藏选择图片按钮
								$(".flashContent").height(50).css({"border":"1px solid #CCCCCC", "padding":"3px 10px"});
								resetVideoInput();
							}
								break;
							case 3:
							{
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
								});
							}
								break;
						}
					}
				}
				self.postBox.find('[data-id="distributeButton"]').parent().mousedown(function(event) {
					if(event.which == 3) return;
					if ($(this).hasClass("disable")) {
						return;
					}
					var currentDistributeType = self.postBox.find('[data-id="currentComposerAttachment"]').val();
					switch (currentDistributeType) {
						case '0'://·¢±í×´Ì¬
							var data = {};
							data.type = "info";
							data.content = self.myStatusTextArea.val();
							data.timestr = self.html_date.data('time') || self.html_date.val();
							if (self.opts.friend) {
								data.permission = 4;
							} else {
								data.permission = self.permission.val();
							}
							self.model(["info"], [data, function(data) {
								if (data.status == 0) {
									alert(data.info);
									return false;
								}
								var time = new Date(data.data.ctime * 1000);

								self.myStatusTextArea.val("");
								self.html_date.val(self.html_date.attr("end_year"));
								if (self.opts.friend) {
									self.opts._class.view(["info"], [self.opts.box, data.data, "afterFirst"]);

									self.opts._class.plug(['commentEasy'], [self.opts.box.children().eq(1)]);
									self.opts._class.cpu(["lay"], [self.opts.box]);
								} else {
									self.cpu(["sideBar"], [self.opts._class.sideArea, time, data]);
								}
								//self.opts._class.view(["info"],[liNow,data.data,"new"]);
							}]);
							break;
						case '1'://
							if (self.postBox.find('[data-id="snapshotPhotoFileOption"]').css('display') != 'none') {
								if (!self.postBox.find('[data-id="distributeButton"]').parent().hasClass("disable")) {
									var flashID = self.getID('campz');
									flashID.save(mk_url("album/api/camera"), CONFIG['u_id']);
								}
							}

							if (self.photoData) {
								sendPhotoComplete(self.photoData, "photo");
							} else {
								$(this).popUp({
									width:450,
									title:'提示!',
									content:'<div style="padding:10px">请先上传图片!</div>',
									buttons:'<span class="popBtns blueBtn closeBtn">确定</span>',
									mask:true,
									maskMode:true
								})
							}
							break;
						case '2':
							var vId = self.postBox.find('[data-id="videoId"]').val();
							if (self.postBox.find('[data-id="recordVideoPanel"]').css('display') != 'none') {
								if (!self.postBox.find('[data-id="distributeButton"]')[0].disabled) {
									var p = $("[name='permission']").val();
									if (self.opts.friend) {
										p = 4;
									} else {
										p = $("[name='permission']").val();
									}
									$.ajax({
										url:mk_url("video/videoapi/save_makevideo", {}),
										dataType:"jsonp",
										type:"GET",
										data:{hd_v_w:$("#hd_v_w").val(), hd_v_h:$("#hd_v_h").val(), hd_v_name:$("#hd_v_name").val(), txtdesc:$("#attachmakeVideoIntroduce").val(), permission:p},
										success:function(result) {
											videoManger(result,"attachMakeVideoIntroduce");
										},
										error:function() {
											alert("网络错误!");
										}
									});
								};
							}
							if (vId && self.postBox.find('[data-id="uploadVideoFlashWrap"]').css("display") != "none") {
								var p = $("[name='permission']").val();
								if (self.opts.friend) {
									p = 4;
								} else {
									p = $("[name='permission']").val();
								}
                                $.ajax({
									url:mk_url("video/videoapi/add_video", {}),
									dataType:"jsonp",
									type:"GET",
									data:{
                                        vid:vId,
                                        txtdesc:$("#attachVIntroduce").val(),
                                        permission:p,
                                        info:$("#hd_info").val(),
                                        timestr:$("#date_a").val()
                                    },
									success:function(result) {
										videoManger(result,"attachVIntroduce");
									},
									error:function() {
										alert("网络错误!")
									}
								});
							}
							if(sharePreviewVideoisOk) {
								var shareVideoText = self.postBox.find('[data-id="shareVideoText"]');
								var data = {};
								data.type = "sharevideo";
								data.content = shareVideoText.val();
								data.timestr = self.html_date.data('time') || self.html_date.val();;
								if (self.opts.friend) {
									data.permission = 4;
								} else {
									data.permission = self.permission.val();
								}
								data = $.extend(data, sharePreviewVideoData);
								self.postBox.find('[data-id="shareVideoPanel"]').find(".form-field").show();
								self.postBox.find('[data-id="shareVideoPanel"]').find(".shareData").hide();
								self.model(["shareVideo"], [data, function(data) {
									if (data.status == 0) {
										alert(data.info);
										return false;
									}
									var time = new Date(data.data.ctime * 1000);

									shareVideoText.val("");
									self.html_date.val(self.html_date.attr("end_year"));
									if (self.opts.friend) {
										self.opts._class.view(["info"], [self.opts.box, data.data, "afterFirst"]);

										self.opts._class.plug(['commentEasy'], [self.opts.box.children().eq(1)]);
										self.opts._class.cpu(["lay"], [self.opts.box]);
									} else {
										self.cpu(["sideBar"], [self.opts._class.sideArea, time, data]);
									}
									//self.opts._class.view(["info"],[liNow,data.data,"new"]);
								}]);
							}
							break;
						case '3':
							$(this).popUp({
								width:450,
								title:'提示!',
								content:'<div style="padding:10px">'+ data.msg +'</div>',
								buttons:'<span class="popBtns blueBtn closeBtn">确定</span>',
								mask:true,
								maskMode:true,
								callback : function() {
									window.location.reload();
								}
							})
							break;
					}
				});
				self.postBox.find('[data-id="uploadPhotoForm"]').submit(function(arg) {

				})
			},
			distributeInfoBody:function(arg) {
				arg[0].find("ul.composerAttachments").children().click(function() {
					var c = $(this).closest(self.postBox.find('[data-id="distributeInfoBody"]'));
					var index = $(this).index();
					var $pointUp = c.find("div.pointUp");
					$pointUp.css("margin-left", 22 + 70 * (index));
					if (index == 1) {
						self.postBox.find('[data-id="photoUploadWay"]').show();
						self.postBox.find('[data-id="distributePhoto"]').show();
						self.postBox.find('[data-id="distributeMsg"]').hide();
						self.postBox.find('[data-id="videoUploadWay"]').hide();
						self.postBox.find('[data-id="distributeVideo"]').hide();
						$("#distributePhoto .fileOption").hide();
						$("#distributeVideo .fileOption").hide();
						self.distributeInfoBodyFooter.hide();
						c.find("div.distributeInfoBox").children("#distributePhoto").show();
					}
					if (index == 2) {
						self.postBox.find('[data-id="videoUploadWay"]').show();
						self.postBox.find('[data-id="distributeVideo"]').show();
						self.postBox.find('[data-id="distributeMsg"]').hide();
						self.postBox.find('[data-id="photoUploadWay"]').hide();
						self.postBox.find('[data-id="distributePhoto"]').hide();
						$("#distributePhoto .fileOption").hide();
						$("#distributeVideo .fileOption").hide();
						self.distributeInfoBodyFooter.hide();
						c.find("div.distributeInfoBox").children("#distributeVideo").show();
					}
                    if(index == 3) {
                        window.location = "http://netdisk.duankou.com"
                    }
					if (index == 0) {
						self.postBox.find('[data-id="distributeMsg"]').show();
						self.postBox.find('[data-id="distributePhoto"]').hide();
						self.postBox.find('[data-id="distributeVideo"]').hide();
						self.distributeInfoBodyFooter.show();
                        self.$face.attr("data-id", self.myStatusTextArea.attr("id"));
                        $.faceInsert.insert(self.myStatusTextArea);
                        c.find("div.distributeInfoBox").children("#distributeMsg").show();
						if(self.myStatusTextArea.height() < 50) {
							self.myStatusTextArea.css("height", 50)
						}
						var l = self.myStatusTextArea.val().length;
						if(self.myStatusTextArea.val() != "" && !(l > self.myStatusTextArea.attr("tmaxlength"))) {
							self.createBtnAble(true);
						} else {
							self.createBtnAble(false);
						}
					}
					self.postBox.find('[data-id="distributeButton"]').parent().attr("data", "false");
					self.postBox.find('[data-id="currentComposerAttachment"]').val(index);
					arg[0].find(self.postBox.find('[data-id="currentComposerAttachment"]')).val(index);
				});
			},
			siderClick:function(arg) { // a年份  data 后的json
				var _self = self.opts;
				if (arg[1].status == 0) {
					return false;
				}
				$(window).off("scroll", _self._class.scrollChangeLoad);
				var $timePsBox;
				var timelineBar = arg[0].closest(".timelineBar");
				var $current = _self._class.cpu(["currentShowHide"], [timelineBar, arg[0]]);
				var time = $current.children().attr("time");
				var $box  = null;

				var index, yearLast, year, month, _time;
				_time = new Date(arg[1].data.ctime*1000);

                year = _time.getFullYear();
                month = _time.getMonth() + 1;
				var title = time + "年";
				var timeYM = year + "-" + parseInt(month);
				var $time_li, $timeYm_li;
				$timeYm_li = _self._class.timelineTree.find("li[time=" + timeYM + "]");
				$time_li = _self._class.timelineTree.find("li[time=" + time + "]");
				if ($timeYm_li.size() != 0) {
					$timePsBox = _self._class.timelineTree.find("li[time=" + timeYM + "]");
					$timePsBox.attr("value", "monthData");
					time = timeYM;

					$box = self.opts._class.view([arg[1].data.type],[$timePsBox,arg[1].data,true]);
				} else {
					if ($time_li.size() == 0) { // 不存在这个年份  需要创建一个新的标识
						var tempObj = timelineBar.find("a[time=" + time + "]");
						tempObj.parent().removeClass('current');
						tempObj.click();
						$timePsBox = $('li.time[time=' + time + ']');
						_self._class.event(["removeLoading"], [$timePsBox.children("ul")]);
					} else {
						if (time != timeYM && $time_li.attr("Ymonth") == "true") {
							$timePsBox = _self._class.view(["timelinePs1"], {time:timeYM, title:year + "年" + month + "月"});
							_self._class.event(["removeLoading"], [$timePsBox.children("ul")]);
							time = timeYM;
						} else {
							$timePsBox = _self._class.timelineTree.find("li[time=" + time + "]");
						}
						$box = self.opts._class.view([arg[1].data.type],[$timePsBox,arg[1].data,true]);
					}
				}




				//self.event(["newTimeAction"],[self.timelineTree]);

				//$(window).on("scroll",self.scrollChange);

				if($box){
					_self._class.cpu(["permissionShow"],[arg[1].data.permission,$box,arg[1]]);
					_self._class.event(["changeSize"], [$box]);

					_self._class.plug(["tip_up_right_black", "tip_up_left_black"], [$box]);
					//var a = $box.find("a[name=" + time + "]");  //得到时间轴psTime 锚点坐标
					$("html,body").animate({scrollTop:$box.offset().top - 165}, 200);
					$(window).on("scroll", _self._class.scrollChangeLoad);
				}


				_self._class.event(["timelineBoxHover"], [$timePsBox]);
				_self._class.plug(['commentEasy'], [$timePsBox]);
				_self._class.cpu(["recodePsTimeTop"], [$timePsBox]);
				_self._class.cpu(["lay"], [$timePsBox.children("ul.content")]);




			}
		}
		return _class[method](arg);
	},
	cpu:function(method, arg) {
		var self = this;
		var _class = {
			sideBar:function(arg) {
				var _self = self.opts;
				var tempA = _self._class.CLASS_TIMELINE_NAV.addNewYear(arg[2], _self._class);
				self.event(["siderClick"], [tempA, arg[2]]);
			}
		}
		return _class[method](arg);
	},
	plug:function(method, arg) {
		var self = this;
		var _class = {
			tip_up_right_black:function(arg) {
				arg[0].find(".tip_up_right_black").tip({
					direction:"up",
					position:"right",
					skin:"black"
				});
			},
			msg:function(arg) {
				arg[0].find("[msg]").msg();
			}
		}
		$.each(method, function(index, value) {
			if (value) {
				return _class[value](arg);
			}
		});
	},
	model:function(method, arg) {
		var self = this;
		var _class = {
			info:function(arg) {
				$.djax({
					url:mk_url("main/info/doPost"),
					dataType:"json",
					async:true,
					data:arg[0],
					success:function(data) {
						arg[1](data);
                        //for plus
                        $('#addNewAction').find('.uiButton').attr('isSend',1);
					},
					error:function(data) {

					}
				});
			},

			photo:function(arg) {
				$.djax({
					url:mk_url("main/info/doPost"),
					dataType:"json",
					async:true,
					data:arg[0],
					success:function(data) {
						self.postBox.find('[data-id="distributeButton"]').parent().attr("data", "false");
						self.createBtnAble(false);
						arg[1](data);
					},
					error:function(data) {

					}
				});
			},
			video:function(arg) {
				$.djax({
					url:mk_url("main/info/doPost"),
					dataType:"json",
					async:true,
					data:arg[0],
					success:function(data) {
						arg[1](data);
						self.postBox.find('[data-id="distributeButton"]').parent().attr("data", "false");
						self.createBtnAble(false);
					},
					error:function(data) {

					}
				});
			},
			shareVideo: function(arg) {
				$.djax({
					url:mk_url("main/info/doPost"),
					dataType:"json",
					async:true,
					data:arg[0],
					success:function(data) {
						arg[1](data);
						self.createBtnAble(false);
						sharePreviewVideoInput.setDefaultValue("请将连接复制到此处");
					},
					error:function(data) {

					}
				});
			}
		}
		return _class[method](arg);
	}
}
$(document).ready(function() {
	(function(postBox) {
		var self = window;
		self.postBox = $(postBox);
		if (self.postBox.size() != 0) {
			var miscpath = CONFIG['misc_path'];
			if ($.browser.mozilla) {
				var campz = '<embed id="campz" width="380" height="270" wmode="opaque" scale="noscale" salign="tl" allowscriptaccess="always" allowfullscreen="true" quality="high" bgcolor="#ffffff" name="campz" style="display:block;" src="' + CONFIG['misc_path'] + 'flash/campz.swf" type="application/x-shockwave-flash" />';
				var camRecord = '<embed id="camRecord" width="380" height="270" wmode="opaque" scale="noscale" salign="tl" allowscriptaccess="always" allowfullscreen="true" quality="high" bgcolor="#ffffff" name="camRecord" style="display:block;" src="' + CONFIG['misc_path'] + 'flash/Videocam1.swf" FlashVars="uid=' + document.getElementById("videoname").value + '&url='+document.getElementById("recordurl").value +'" type="application/x-shockwave-flash" allownetworking="all" />';
				self.postBox.find('[data-id="campz"]').parent().append(campz);
				self.postBox.find('[data-id="campz"]').remove();
				self.postBox.find('[data-id="camRecord"]').parent().append(camRecord);
				self.postBox.find('[data-id="camRecord"]').remove();
			} else {
				var params = {
					width:"380",
					height:"270",
					wmode:"opaque",
					scale:"noscale",
					align:"middle",
					quality:"high",
					allowfullscreen:"false",
					type:"application/x-shockwave-flash",
					allowscriptaccess:"always",
					menu:"true",
					devicefont:"false",
					scale:"showall",
					play:"true"
				};

				if (document.getElementById("videoname") && document.getElementById("recordurl")) {
					var flashvars = {
						uid:document.getElementById("videoname").value,
						url:document.getElementById("recordurl").value
					};
					swfobject.embedSWF(CONFIG['misc_path'] + "flash/campz.swf", "campz", "380", "270", "9.0.0", miscpath + "/flash/expressInstall.swf", {}, params);
					swfobject.embedSWF(CONFIG['misc_path'] + "flash/Videocam1.swf", "camRecord", "380", "270", "9.0.0", miscpath + "/flash/expressInstall.swf", flashvars, params);
				}
			};
		}
		window.showMsg = function(msg) {
			$(this).popUp({
				width:450,
				title:'提示!',
				content:'<div style="padding:10px">' + msg + '?</div>',
				buttons:'<span class="popBtns blueBtn closeBtn">确定</span>',
				mask:true,
				maskMode:true,
				callback:function() {
				}
			});
		}
		window.sendPhotoComplete = function(data, type) {
			var str = data;
			if (str.status) {
				$("#flashuploaduid div").show()
				$("#flashuploaduid object").height(25);//隐藏选择图片按钮
				self.postBox.find('[data-id="flashuploaduid"]').height(50).css({"border":"1px solid #CCCCCC", "padding":"3px 10px"});
				var data = {};
				data.type = type;
                console.log()
				data.fid = str.data.fid;
				data.picurl = str.data.picurl;
				data.content = self.postBox.find('[data-id="attachPhotoIntroduce"]').val() || self.postBox.find('[data-id="attachCameraPhotoIntroduce"]').val();
				data.timestr = $(".html_date").data('time') || $(".html_date").val();
				data.note = str.data.note;
				if (self.postBox.find('[data-id="friendFramwork"]').size() == 0) {
					data.permission = $("input[name=permission]").val();
				} else {
					data.permission = 4;
				}
				class_postBox.model(["photo"], [data, function(data) {

					var time = new Date(data.data.ctime * 1000);
					var html_date = self.postBox.find('[data-id="distributeInfoBody"]').find(".html_date");
					html_date.val(html_date.attr("end_year"));

					//                $(".s_msg:visible").click();
					if (self.postBox.find('[data-id="friendFramwork"]').size() == 0) {
						class_postBox.cpu(["sideBar"], [class_timeline.sideArea, time, data]);
					} else {
						var flowLayout = new CLASS_FLOWLAYOUT();
						flowLayout.view(["album"], [self.postBox.find('[data-id="timelineTree"]').children(".timelinebody"), data.data, "afterFirst"]);
						flowLayout.cpu(["lay"], [self.postBox.find('[data-id="timelineTree"]').children(".timelinebody")]);
						flowLayout.plug(['commentEasy'], [self.postBox.find('[data-id="timelineTree"]').children(".timelinebody").children().eq(1)]);
					}
				}]);
				class_postBox.photoData = null;
				self.postBox.find('[data-id="flashuploaduid"]').show();
				self.postBox.find('[data-id="up_photo_success"]').hide();
				//inserData(str.data,true);
			} else {
				alert(str.msg);
			}

			self.postBox.find('[data-id="uploadPhotoForm"]').reset;
			self.postBox.find('[data-id="tokenareaList"]').empty();
			self.postBox.find('[data-id="attachPhotoIntroduce"]').val('');
			self.postBox.find('[data-id="tokenShareDestinations"]').val('');
			/*var $tempParent  = self.postBox.find('[data-id="uploadPhotoButton"]').parent();
				 self.postBox.find('[data-id="uploadPhotoButton"]').remove();
				 $($tempParent).prepend('<input type=\"file\" name=\"uploadPhotoFile\" id=\"uploadPhotoButton\">');*/
			//hideAnimation($('#distributeButton'),$('#distributeInfoBody').find('div.showWhenLoading'));

		};

		//摄相象头拍照照片发布
		window.photo = function(data) {
			sendPhotoComplete(data, "album");
		};

		//禁用开启保存图片按钮
		window.disable = function(boolen) {
			document.getElementById('distributeButton').disabled = boolen;
			var inputBol = self.postBox.find('[data-id="attachmakeVideoIntroduce"]').val() || self.postBox.find('[data-id="attachCameraPhotoIntroduce"]').val();
			var inputLen =  inputBol.length < 140;
			if (Boolean(boolen) || !inputLen) {
				self.postBox.find('[data-id="distributeButton"]').parent().attr("data", "false")
				self.postBox.find('[data-id="distributeButton"]').parent().addClass('disable');
			} else {
				self.postBox.find('[data-id="distributeButton"]').parent().attr("data", "true")
				self.postBox.find('[data-id="distributeButton"]').parent().removeClass('disable');
			}
		}

		window.onunload = function() {
			self.postBox.find('[data-id="currentComposerAttachment"]').val(0);
		}

		/*window.getUid = function(){
			 return document.getElementById("hd_sessionId").value;
			 }*/

		window.camComplete = function(w, h, name) {
			document.getElementById("hd_v_w").value = w;
			document.getElementById("hd_v_h").value = h;
			document.getElementById("hd_v_name").value = name;
		}
		window.camNontCam = function() {
			self.postBox.find('[data-id="noCam"]').show();
			self.postBox.find('[data-id="recordVideoPanel"]').hide();
			self.postBox.find('[data-id="attachMakeVideoIntroduce"]').hide();
		}

		self.postBox.find('[data-id="btn_Refresh"]').click(function() {
			location.reload();
		});

		/* 分享视频预览 */
		(function($) {
			$.fn.createBtnAble = function(isAble) {
				if(!$(this)[0]) return;
				var parent = $(this).parent();
				if (isAble) {
					parent.attr("data", "true");
					parent.removeClass('disable');
				} else {
					parent.attr("data", "false");
					parent.addClass('disable');
				}
			};
			/* inputHelper */
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
							oThis.validateValue()
						});
					return this;
				},
				getValue: function() {
					var value = this.inputObj.val();
					return (value != "") && (value != this.defaultValue) ? value : "";
				},
				validateValue: function() {
					var value = this.getValue();
					if(value != "" && /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig.test(value)) {
						return true;
					}
					return false;
				}
			};
			var sharePreviewVideo = function(inputId, submitId) {
				var oThis = this;
				this.submitId = $(submitId);
				this.footer = $(".footer");
				this.inputHelper = sharePreviewVideoInput = new inputHelper($(inputId), {
					useMethodName: "setDefaultValue", defaultValue: "请将连接复制到此处"
				});
				this.submitId.bind("click", function(event) {
					if(oThis.inputHelper.validateValue()) {
						$.ajax({
							url: mk_url("video/videoapi/video_share_link"),
							method: "GET",
							data: {
								"url": encodeURIComponent(oThis.inputHelper.getValue())
							},
							dataType: "jsonp",
							success: function(response) {
								var data = response.data;
								var shareVideoText = $("#shareVideoText");
								var html = '<img src="' + data.img + '" width="128" height="80"><a class="showFlash" href="javascript:void(0);"><img alt="" src="/img/system/feedvideoplay.gif"></a>';
								$("#distributeButton").createBtnAble(true);
								oThis.footer.show();
								$(".form-field").hide();
								$(".shareData").show().find(".media_prev").html(html);
								shareVideoText.text(data.title);
								shareVideoText.focus();
								shareVideoText.blur();
								sharePreviewVideoisOk = true;
								sharePreviewVideoData = {"videourl": data.swf, "imgurl": data.img, "url": data.url, "content": shareVideoText.val()};
							}
						});
					}
					return false;
				});
			};
			sharePreviewVideo("#inputLinks", "#shareVideoPanel .submit");
		})(jQuery)
	})("#postBox")
});
