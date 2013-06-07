
/*
 * Created on 2012-08-31
 * @name:  v1.0
 * @author: bohailiang
 * @desc:  
		 中
 */

// flashgame 类
var gamelist = {
	options: {
		actionContain: $('.action'),			// 编辑、删除的容器的class
		gameContain: $("#flashGameList"),		// 网页列表的容器 => ul
		clickMore: $("#clickMore"),			// 点击更多 => div
		getMoreUrl: '',						// 获取更多的url
		postData: null,						// 获取更多时，需要post传值的内容
		editUrl: '',						// 编辑的url
		delUrl: ''							// 删除的url
	},
	scollCount: 0,				// 滚动加载的次数
	isLast: false,				// 是否已经显示到最后 false - 未； ture - 已显示到最后
	thePage: 1,					// 当前的页数

	init: function(option){
		var self = this,
			opt = self.options = $.extend({}, self.options, option);

		self.bindEvent();	// 绑定滚动加载事件
		gameaction.init({actionContain: opt.actionContain, editUrl: opt.editUrl, delUrl: opt.delUrl, gameContain: opt.gameContain});	// 初始化编辑、删除操作
	},

	// 绑定事件
	bindEvent: function(){
		var self = this,
			opt = self.options;
		

		// 绑定窗口滚动事件
		$(window).bind('scroll', function(){
			var wH = $(window).height(),
				sH = $(window).scrollTop(),
				bH = $('body').height();

			// 滚动加载计数小于2，未达显示了最后，ajax获取数据
			if(self.scollCount < 2 && sH > 0 && sH > (bH - wH - 10) && false == self.isLast) {
				var postData = $.extend({}, opt.postData, { page: self.thePage + 1 });
				// ajax请求下一页数据
				self.ajaxFun(opt.gameContain, opt.getMoreUrl, function(data){
					var html = self.htmlBuild(data.list);		// 组建html代码
					opt.gameContain.append(html);				// 嵌入新的html代码
					self.isLast = data.last;
					self.thePage = self.thePage + 1;			// 页面计数 + 1
					self.scollCount = self.scollCount + 1;		// 重置滚动加载计数
					if(2 == self.scollCount){
						opt.clickMore.show();		// 显示点击更多
					}
				}, postData);
			}
		});

		// 绑定“查看更多”的点击事件
		opt.clickMore.find('a').attr('href', 'javascript:void(0);'); // "#" => "javascript:void(0);" 防止点击后回到顶部
		opt.clickMore.click(function(){
			// 已经显示了最后，或是滚动加载技术未达到2，则不处理
			if(true == self.isLast || 2 > self.scollCount){
				opt.clickMore.hide();		// 隐藏点击更多
				return false;
			}
			var postData = $.extend({}, opt.postData, { page: self.thePage + 1 });
			// ajax请求下一页数据
			self.ajaxFun(opt.gameContain, opt.getMoreUrl, function(data){
				var html = self.htmlBuild(data.list);		// 组建html代码
				opt.gameContain.append(html);				// 嵌入新的html代码
				self.isLast = data.last;
				self.thePage = self.thePage + 1;			// 页面计数 + 1
				self.scollCount = 0;		// 重置滚动加载计数
			}, postData);

			opt.clickMore.hide();		// 隐藏点击更多
		});

	},

	// 组建html代码
	htmlBuild: function(list){
		var html = '',
			item = {},
			newLi = '',
			newItemLi;

		if( 0 < list.length ){	// 又数据返回
			for(var i = 0; i < list.length; i++){
				item = list[i];
				newLi = '<li class="game">\
							<div class="img">\
								<a href="#"><img width="152" height="112" src="http://192.168.12.242/group2/M00/0D/D8/wKgM8lAV2t30fni2AABggCozgy8147_1.jpg" /></a>\
							</div>\
							<div class="info">\
								<div class="name">\
									<a href="#">Fuck U now</a>\
								</div>\
								<div class="desc">暗夜骑士 iPhone幽灵灭亡版</div>\
								<div class="comment">\
									<a href="#">赞</a><span>521</span><a href="#">评论</a><span>12</span>\
								</div>\
								<div class="action">\
									<input type="hidden" class="idInput" value="" />\
									<a class="editLink" href="#">编辑</a>\
									<a class="delLink" href="#">删除</a>\
								</div>\
							</div>\
						</li>';

				html += newLi;
			}
		} else {	// 没有数据返回
			html = '';
		}

		return html;
	},

	// 统一的ajxa请求
	ajaxFun: function(obj, url, callback, data){
				data = { last: false, list: [1, 2, 3]};
				callback(data);
				obj.removeClass('getting');
				return false;
		// 防止多次请求
		if(true == obj.hasClass('getting')){
			return false;
		}
		obj.addClass('getting');
		var _data = {};

		data = $.extend({}, _data, data);
		url = mk_url(url);
		$.djax({
			url: url,
			type: 'POST',
			dataType: 'json',
			data: data,
			success: function(data) {
				data = {status: 1, data: { last: false, list: [1, 2, 3]}};
				if(data.status !== 1) {
					//显示错误信息，弹出层
					obj.subPopUp({
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

					return false;
				} else {
					data = data.data;
					callback(data);
				}
				obj.removeClass('getting');

				return true;
			}
		});
	}
};

var gameaction = {
	options: {
		actionContain: $('.action'),
		gameContain: $("#flashGameList"),
		editUrl: '',				// 编辑的url
		delUrl: '',					// 删除的url
		isList: true				// 是否是列表
	},
	formCheck: true,

	init: function(option){
		var self = this,
			opt = self.options = $.extend({}, self.options, option);

		self.bindEvent();	// 绑定编辑、删除事件
	},

	// 绑定事件
	bindEvent: function(){
		var self = this,
			opt = self.options;

		// 编辑
		opt.gameContain.delegate('a.editLink', 'click', function(e){
			var obj = $(e.target),
				editFormHtml = '';
			if(true == opt.isList){
				var parents = obj.parents('li.game'),		// 整一行
					img = parents.find('div.img').find('img').attr('src'),		// 游戏的缩略图地址
					name = parents.find('div.name').find('a').text(),	// 游戏的名字
					desc = parents.find('div.desc').text(),				// 游戏的描述
					game_id = parents.find('input.idInput').val(),		// 游戏的id
					len = desc.length;
			}

			editFormHtml = $('<form action="#" id="editForm" name="editForm" method="post">\
								<div class="metadataRow clearfix">\
									<label for="uploadGameDesc" class="metadataLabel">&nbsp;</label>\
									<div class="metadataInput">\
										<div class="uploadImg">\
											<div class="left">\
												<img id="imageView" width="152" height="112" src="' + img + '" />\
											</div>\
											<div class="left btn">\
												<input type="file" id="flashGameImg" name="flashGameImg" />\
											</div>\
											<div class="text">(图片格式支持JPG、GIF、BMP、TIF 最大不超过2M)</div>\
										</div>\
									</div>\
								</div>\
								<div class="metadataRow clearfix hide" id="imgUploadBody">\
									<label for="uploadGameDesc" class="metadataLabel">&nbsp;</label>\
									<div class="metadataInput">\
										<div class="uploadImg">\
											<div id="imgUploadProgress"></div>\
										</div>\
									</div>\
								</div>\
								<div class="metadataRow clearfix">\
									<label for="uploadGameTitle" class="metadataLabel">标题：</label>\
									<div class="metadataInput">\
										<input type="text" maxlength="50" name="title" class="inputtext" id="uploadGameTitle" value="' + name + '">\
									</div>\
								</div>\
								<div class="metadataRow clearfix">\
									<label for="uploadGameDesc" class="metadataLabel">说明：</label>\
									<div class="metadataInput">\
										<textarea isnull="0" name="uploadGameDesc" id="uploadGameDesc" class="textarea">' + desc + '</textarea>\
										<div class="tip hide"><span class="num">0</span>/140</div>\
										<input type="hidden" id="imageFile" name="imageFile" />\
										<input type="hidden" id="gameId" name="gameId" value="' + game_id + '" />\
									</div>\
								</div>\
							</form>');

			obj.popUp({
				width:480,
				title:'编辑',
				content: editFormHtml,
				buttons:'<span class="popBtns blueBtn callbackBtn">确定</span><span class="popBtns closeBtn">取消</span>',
				mask:false,
				maskMode:true,
				callback:function(){
					if(false == self.formCheck){
						alert('cancel');
						return false;
					}

					var postData = $("#editForm", editFormHtml).serializeArray(),
						newImg = $("#imageView", editFormHtml).attr('src'),
						newName = self.htmlspecialchars($("#uploadGameTitle", editFormHtml).val()),
						newDesc = self.htmlspecialchars($("#uploadGameDesc", editFormHtml).val());
					gamelist.ajaxFun(obj, opt.delUrl, function(data){
						if(true == opt.isList){
							parents.find('div.img').find('img').attr('src', newImg);
							parents.find('div.name').find('a').html(newName);
							parents.find('div.desc').html(newDesc);
						} else {
							window.location.href = '';
						}
						$.closePopUp();
					}, postData);
					return false;
				}
			});

			if(0 < len){
				$(".tip", editFormHtml).show();
				$(".num", editFormHtml).html(len);
			}

			self.initImgUpload(editFormHtml);
			self.editFormEvent(editFormHtml);
			return false;
		});

		// 删除
		opt.gameContain.delegate('a.delLink', 'click', function(e){
			var obj = $(e.target),
				game_id = obj.parent().find('.idInput').val();
			var content = '	<div class="delDIv">\
								<ul>\
									<li>\
										<span>■</span>删除游戏 是永久性操作\
									</li>\
									<li>\
										<span>■</span>删除后将不能取回\
									</li>\
									<li>\
										<span>■</span>您确定要删除该游戏吗？\
									</li>\
								</ul>\
							</div>';
			obj.popUp({
				width:300,
				title:'提示',
				content: content,
				buttons:'<span class="popBtns blueBtn callbackBtn">确定</span><span class="popBtns closeBtn">取消</span>',
				mask:false,
				maskMode:true,
				callback:function(){
					gamelist.ajaxFun(obj, opt.delUrl, function(data){
						if(true == opt.isList){
							obj.parents('li.game').remove();
							$.closePopUp();
						} else {
							window.location.href = '';
						}
					}, {game_id: game_id});
					return false;
				}
			});
			return false;
		});
	},

	// 初始化编辑框的上传按钮
	initImgUpload: function(parent){
		var self = this,
			opt = self.options;

		$("#flashGameImg", parent).uploadify({
			"uploader":CONFIG['local_run'] ? (CONFIG['misc_path'] + "/flash/uploadify.swf") : ("//www" + CONFIG['domain'] + "/apps/main/uploadify.swf"),
			"script": '',
			"method": '',
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
			'sizeLimit': '',
			"simUploadLimit": 1,
			"fileDataName":'uploadImageFile',
			"scriptData":{
				"flashUploadUid": '',
				'type': 3
			},
			onSelect: function(e, queueId, fileObj){	// 选择文件后
				//$("#imgUploadBody").show();
			},
			onOpen: function(e, queueId, fileObj){	// 打开文件后
			},
			onProgress: function(){					// 开始上传
				// 开始上传后，不能提交表单
				$("#imgUploadProgress", parent).addClass('imgUploading');
				self.setFormCheck(parent);
				self.setSubmitBtnStatus();
			},
			onCancel: function(){					// 取消上传
				//$("#imgUploadBody").hide();
				self.setFormCheck(parent);
				self.setSubmitBtnStatus();
				$("#imgUploadProgress", parent).html('');
			},
			onComplete: function(e, queueId, fileObj, response, data){		// 上传成功
				$("#imgUploadProgress", parent).removeClass('imgUploading');
				$("#imageView", parent).attr('src', 'http://avatar.duankou.dev/avatar_1000002929_b.jpg?v=1343971861');
				$("#imageFile").val('321');		// 填写上传返回的内容

				// 检测表单状态，以及设置按钮可点状态
				self.setFormCheck(parent);
				self.setSubmitBtnStatus();
			},
			onError: function(e, qid, fo, eo) {
				if (eo.type == "File Size") {
					$(this).subPopUp({
						width:450,
						title:'提示!',
						content:'<div style="padding:10px">上传图片大小不能超过1M !</div>',
						buttons:'<span class="popBtns blueBtn closeBtn">确定</span>',
						mask:true,
						maskMode:true
					});
					$("#imgUploadProgress", parent).html('');
				}
			}
		});
	},

	// 编辑框内的元素绑定事件
	editFormEvent: function(parent){
		var self = this,
			opt = self.options;

		var uploadGameDesc = $("#uploadGameDesc", parent);
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

			self.setFormCheck(parent);
			self.setSubmitBtnStatus();
		});

		// 标题框，输入检测
		$("#uploadGameTitle", parent).bind('keyup', function(){
			var val = $(this).val();

			self.setFormCheck(parent);
			self.setSubmitBtnStatus();
		}); 
	},

	// js下的html符号转换方法，例如：'<' => '&lt;'
	htmlspecialchars: function(str){
		return $('<span>').text(str).html();
	},

	// 设置表单检测状态
	setFormCheck: function(parent){
		var self = this,
			uploadGameTitle = $("#uploadGameTitle", parent).val(),
			uploadGameDesc = $("#uploadGameDesc", parent).val();

		if('' == uploadGameTitle || ('' != uploadGameDesc && 140 < uploadGameDesc.length) || true == $("#imgUploadProgress", parent).hasClass('imgUploading')){
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
			$(".popBtnsWrap").find(".callbackBtn").addClass('false');
		} else {
			$(".popBtnsWrap").find(".callbackBtn").removeClass('false');
		}

		return ;
	}
}