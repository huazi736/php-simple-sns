/*
* Created on 2012-08-02
* @author: 卜海亮
* @desc: 游戏频道，上传flash游戏
*/

var gameUpload = {
	flashGameForm: $("#flashGameForm"),				// 上传最后需要提交的表单
	uploadSuccess: $("#uploadSuccess"),				// 上传成功后显示的区域
	uploadProcessInfo: $("#uploadProcessInfo"),		// 上传进行中、完成后，显示提示语句的区域
	uploadFlashGame: $('#uploadFlashGame'),			// 上传按钮
	uplaodArea: $("#uplaodArea"),					// 上传文件选择区域
	formSubmit: $("#formSubmit"),					// 表单提交按钮
	flashGameImg: $('#flashGameImg'),				// 图片上传按钮
	options: {
		uploadUrl: mk_url('channel/games_list/upload'),		// 上传的url
		sizeLimit: 1024 * 1024 * 10,						// 上传大小
		uploadProcess: 'uploadProcess',	// 
		method: 'GET',
		flashUploadUid: 1000002888,		// 上传检测登录的值
		imgUploadUrl: mk_url('channel/games_list/upload'),		// 上传的url
		imgSizeLimit: 1024 * 1024 * 1			// 图片上传大小
	},
	formCheck: false,		// 表单检测结果

	init: function(option){
		var self = this,
			opt = self.options = $.extend({}, self.options, option);

		self.initUpload();
		self.initImgUpload();
		self.bindEvent();
		self.setSubmitBtnStatus();
	},

	// 初始化上传按钮
	initUpload: function(){
		var self = this,
			opt = self.options;

		// 初始化按钮
		self.uploadFlashGame.uploadify({
			"uploader":CONFIG['local_run'] ? (CONFIG['misc_path'] + "/flash/uploadify.swf") : ("//www" + CONFIG['domain'] + "/apps/main/uploadify.swf"),
			"script": opt.uploadUrl,
			"method": opt.method,
			"cancelImg":CONFIG['misc_path'] + "img/system/icon_close_03.png",
			"buttonImg":CONFIG['misc_path'] + "img/system/icon_selectFile.png",
			"folder":CONFIG['misc_path'] + "temp",
			"fileExt":"*.swf;*.flv;",
			"fileDesc":"*.swf格式",
			"width":67,
			"height":24,
			"queueID": opt.uploadProcess,
			"multi":false,
			"auto":true,
			'sizeLimit': opt.sizeLimit,
			"fileDataName":'uploadFlashFile',
			"simUploadLimit": 1,
			"scriptData":{
				"flashUploadUid": opt.flashUploadUid,
				'type': 3
			},
			onSelect: function(e, queueId, fileObj){	// 选择文件后
				var fileName = fileObj.name.split('.')[0];
				self.flashGameForm.show();
				$("#uploadGameTitle", self.flashGameForm).val(fileName);
			},
			onOpen: function(e, queueId, fileObj){	// 打开文件后
			},
			onProgress: function(){					// 开始上传
				self.uplaodArea.hide();
				self.uploadProcessInfo.html('游戏上传中，你可以输入以下信息');	// 更改上传进度区的内容
			},
			onCancel: function(){					// 取消上传
				$("#reUpload").click();
			},
			onComplete: function(e, queueId, fileObj, response, data){		// 上传成功
				$("#flashFile").val('123');		// 填写上传返回的内容
				self.uploadSuccess.show();		// 显示上传成功区
				self.uploadProcessInfo.html('游戏上传已完成，你可以输入以下信息');	// 更改上传进度区的内容

				// 检测表单状态，以及设置按钮可点状态
				self.setFormCheck();
				self.setSubmitBtnStatus();
			},
			onError: function(e, qid, fo, eo) {
				if (eo.type == "File Size") {
					$(this).popUp({
						width:450,
						title:'提示!',
						content:'<div style="padding:10px">上传游戏大小不能超过10M !</div>',
						buttons:'<span class="popBtns blueBtn closeBtn">确定</span>',
						mask:true,
						maskMode:true
					});
					$("#uploadProcess").html('');
				}
				$("#reUpload").click();
			}
		});
	},

	// 初始化图片上传
	initImgUpload: function(){
		var self = this,
			opt = self.options;

		// 初始化按钮
		self.flashGameImg.uploadify({
			"uploader":CONFIG['local_run'] ? (CONFIG['misc_path'] + "/flash/uploadify.swf") : ("//www" + CONFIG['domain'] + "/apps/main/uploadify.swf"),
			"script": opt.imgUploadUrl,
			"method": opt.method,
			"cancelImg":CONFIG['misc_path'] + "img/system/icon_close_03.png",
			"buttonImg":CONFIG['misc_path'] + "img/system/icon_selectFile_white.png",
			"folder":CONFIG['misc_path'] + "temp",
			"fileExt":"*.jpg;*.jpeg;*.gif;*.png;",
			"fileDesc":"*.jpg;*.jpeg;*.gif;*.png图片格式",
			"width":69,
			"height":24,
			"queueID": 'imgUploadProgress',
			"multi":false,
			"auto":true,
			'sizeLimit': opt.imgSizeLimit,
			"fileDataName":'uploadImageFile',
			"simUploadLimit": 1,
			"scriptData":{
				"flashUploadUid": opt.flashUploadUid,
				'type': 3
			},
			onSelect: function(e, queueId, fileObj){	// 选择文件后
				$("#imgUploadBody").show();
			},
			onOpen: function(e, queueId, fileObj){	// 打开文件后
			},
			onProgress: function(){					// 开始上传
				// 开始上传后，不能提交表单
				$("#imgBody").addClass('imgUploading');
				self.setFormCheck();
				self.setSubmitBtnStatus();
			},
			onCancel: function(){					// 取消上传
				$("#imgBody").empty();
				$("#imgUploadBody").hide();
				self.setFormCheck();
				self.setSubmitBtnStatus();
			},
			onComplete: function(e, queueId, fileObj, response, data){		// 上传成功
				$("#imgBody").removeClass('imgUploading').append('<img src="http://avatar.duankou.dev/avatar_1000002929_b.jpg?v=1343971861" />');
				$("#imageFile").val('321');		// 填写上传返回的内容

				// 检测表单状态，以及设置按钮可点状态
				self.setFormCheck();
				self.setSubmitBtnStatus();
			},
			onError: function(e, qid, fo, eo) {
				if (eo.type == "File Size") {
					$(this).popUp({
						width:450,
						title:'提示!',
						content:'<div style="padding:10px">上传图片大小不能超过1M !</div>',
						buttons:'<span class="popBtns blueBtn closeBtn">确定</span>',
						mask:true,
						maskMode:true
					});
					$("#imgUploadProgress").html('');
				}
				$("#imgUploadBody").hide();
			}
		});
	},

	// 绑定事件
	bindEvent: function(){
		var self = this,
			opt = self.options;

		// 绑定重新上传事件
		$("#reUpload").click(function(){
			self.flashGameForm[0].reset();
			$("#imgBody").empty();
			self.flashGameForm.hide();
			self.uplaodArea.show();
			$("#imgUploadBody").hide();

			return false;
		});

		// 表单提交事件
		self.flashGameForm.bind('submit', function(){
			var _this = $(this),
				actionUrl = _this.attr('action');

			// 检测失败，或是正在提交中
			if(false == self.formCheck || true == _this.hasClass('uploading')){
				return false;
			}

			_this.addClass('uploading');
			var postData = _this.serializeArray();

			$.djax({
				url: actionUrl,
				type: 'POST',
				dataType: 'json',
				data: postData,
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
					} else {
						//window.location.href = '';
					}
					
					_this.removeClass('uploading');
					return false;
				}
			});

			return false;
		});

		// 标题框，输入检测
		$("#uploadGameTitle", self.flashGameForm).bind('keyup', function(){
			var val = $(this).val();

			self.setFormCheck();
			self.setSubmitBtnStatus();
		});

		var uploadGameDesc = $("#uploadGameDesc", self.flashGameForm);
		var tip = uploadGameDesc.parent().find('.tip');
		// 说明框，获得焦点
		uploadGameDesc.bind('focus', function(){
			tip.show();
		});
		// 说明框，失去焦点
		uploadGameDesc.bind('blur', function(){
			var val = $(this).val();
			if('' == val){
				tip.hide();
			}
		});
		// 说明框，输入检测
		uploadGameDesc.bind('keyup', function(){
			var val = $(this).val(),	// 输入的内容
				len = val.length;		// 内容长度

			// 及时显示输入长度
			tip.find('.num').html(len);
			if(len > 140){
				// 长度超过140个
				tip.find('.num').attr('style', 'color: #ff0000;');
			} else {
				tip.find('.num').attr('style', 'color: #333333;');
			}

			self.setFormCheck();
			self.setSubmitBtnStatus();
		});

		// 初始化上传图片的按钮
	},

	// 设置表单检测状态
	setFormCheck: function(){
		var self = this,
			flashFile = $("#flashFile").val(),
			uploadGameTitle = $("#uploadGameTitle", self.flashGameForm).val(),
			uploadGameDesc = $("#uploadGameDesc", self.flashGameForm).val();

		if('' == flashFile || '' == uploadGameTitle || ('' != uploadGameDesc && 140 < uploadGameDesc.length) || true == $("#imgBody").hasClass('imgUploading')){
			self.formCheck = false;
		} else {
			self.formCheck = true;
		}

		return ;
	},

	// 设置表单提交按钮的状态
	setSubmitBtnStatus: function(){
		var self = this;

		if(false == self.formCheck){
			self.formSubmit.addClass('false');
		} else {
			self.formSubmit.removeClass('false');
		}

		return ;
	}
};