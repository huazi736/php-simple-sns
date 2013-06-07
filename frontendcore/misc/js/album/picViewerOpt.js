/*
 * @ name: picViewerOpt.js
 * @ author: lishijun
 * @ desc:  图片查看器操作JS
 *
 */

 function CLASS_PICVIEWEROPT(){
 	this.init();
 }

 CLASS_PICVIEWEROPT.prototype = {
 	init : function(){
 		this.webId = CONFIG['web_id'];
 		
 		if(this.webId == false){
 			this.editPicNameUrl = mk_url('album/index/editPhotoName'); //编辑图片名称djax后台url
	 		this.addInfoUrl = mk_url('album/index/editPhotoDesc'); //添加描述djax后台url
	 		this.movePicUrl = mk_url('album/index/movePhoto'); //照片移动ajax处理url
			this.rotateAjaxUrl = mk_url('album/index/rotate'); //照片旋转ajax处理url
 		}
 		else{
			this.editPicNameUrl = mk_url('walbum/photo/editPhotoName',{'web_id':this.webId}); //编辑图片名称djax后台url
	 		this.addInfoUrl = mk_url('walbum/photo/editPhotoDesc',{'web_id':this.webId}); //添加描述djax后台url
	 		this.movePicUrl = mk_url('walbum/photo/move',{'web_id':this.webId}); //照片移动ajax处理url
			this.rotateAjaxUrl = mk_url('walbum/photo/rotate',{'web_id':this.webId}); //照片旋转ajax处理url
 		}

 		this.picId = $("input[name='pid']").val(); //图片id
 		this.$editNameBar = $('.picViewer_picBox .pic_name');
 		this.$setCover = $("#picToCover"); //设置封面动作对象
 		this.$setCoverForm = $('#picToCover_form'); //设置封面form表单
 		this.$setMainCover = $("#picToMainCover"); //设置应用区封面动作对象
 		this.$setMainCoverForm = $('#picToMainCover_form'); //设置应用区封面form表单
 		this.$addInfoBtn = $('.info_addInfo_addComment .pic_name');
 		
 		this.$commentEasyBtn = $(".comment_easy"); //照片评论开关
 		this.commentOptions = { 
			minNum:3,
			UID:CONFIG['u_id'],
			userName:CONFIG['u_name'],
			avatar:CONFIG['u_head'],
			userPageUrl:$("#hd_userPageUrl").val(),
			relayCallback:function (obj,_arg) {
                var comment=new ui.Comment();
                comment.share(obj,_arg);
            }
		};
		this.$delPhotoBtn = $('#picViewer_del'); //照片删除动作对象
		this.$picViewerPic = $('.picViewer_pic');
		this.$prevArea = this.$picViewerPic.children('.prevArea'); //向前翻页区域
		this.$nextArea = this.$picViewerPic.children('.nextArea'); //向后翻页区域
		this.degree = 0;
		this.$downIcon = $('#movePhoto2').children('.downIcon'); //照片移动按钮
		

		this.$rotateToLeft = $('#rotateToLeft');
		this.$rotateToRight = $('#rotateToRight');
		this.$remoteFlag = $('#remoteFlag').val() || 0;
 		
 		this.event('setCover'); //设置封面
 		this.event('setMainCover'); //设置应用区封面
 		this.event('delPhoto'); //照片删除
 		this.event('zoomIn'); //照片放大

 		if(this.$prevArea.size() > 0){
 			this.event('togglePage',[this.$prevArea,37]); //向前翻页
 		}
 		if(this.$nextArea.size() > 0){
 			this.event('togglePage',[this.$nextArea,39]); //向后翻页
 		}

 		this.rotateFlag = true; //是否显示旋转按钮
 		this.event('rotatePic'); //图片旋转
 		this.event('movePic'); //图片移动

 		this.plug('editText',[this.$addInfoBtn,'添加照片描述',this.addInfoUrl,this.picId,140]); //添加照片描述
 		this.plug('editText',[this.$editNameBar,'未命名',this.editPicNameUrl,this.picId,50]); //编辑图片名称
 		this.plug('commentEasy'); //照片评论
 		if($.browser.msie && $.browser.version==6.0){
 			this.view('setCursor');
 		}
 	},
 	view : function(method,arg){
 		var self = this;
 		var _class = {
 			setCursor : function(){
 				var picAreaHeight = self.$picViewerPic.height();
 				self.$picViewerPic.children('div').height(picAreaHeight+'px');
 			},
 			getRotatedPic : function(arg){
 				if(self.$remoteFlag){ //拥有者
					var imgWdith = self.$picViewerPic.children('img').width();

					self.$picViewerPic.css({
						height:imgWdith+'px',
						background:'url('+CONFIG['misc_path']+'img/system/bg_line_loading.gif) no-repeat 50% 50%'
					}).children('img').removeAttr('src').removeAttr('alt').hide();

					self.$rotateToLeft.hide();
					self.$rotateToRight.hide();
					self.$prevArea.hide();
					self.$nextArea.hide();

					self.event('saveRotate',[arg[0]]);
				}
				else{
					if(arg[0] == 'left'){
	 					self.degree -= 90;
	 				}
	 				else if(arg[0] == 'right'){
	 					self.degree += 90;
	 				}
	 				
	 				$('#pic').rotate(self.degree);

					if(self.degree %360 === 0){
						self.degree = 0;
					}
				}
 			},
 			waitTip: function(arg){
				self.plug('subPopUp',[$.fn,arg[0],'正在'+arg[0]+'，您的浏览器可能会有稍许停顿，请稍候......','','']);
 			}
 		};
 		return _class[method](arg);
 	},
 	event : function(method,arg){
 		var self = this;
 		var _class = {
 			setCover : function(){
 				self.$setCover.bind('click',function(){
 					self.model('getJsonData',[self.$setCoverForm.attr('action'),self.$setCoverForm.serialize(),
 						function(m){
							self.plug('popUp',[$(this),'设置为封面',m.info,'<span class="popBtns blueBtn closeBtn">确定</span>','']);
						}
					]);
 				});
 			},
 			setMainCover : function(){
 				self.$setMainCover.bind('click',function(){
 					self.model('getJsonData',[self.$setMainCoverForm.attr('action'),self.$setMainCoverForm.serialize(),
 						function(m){
							self.plug('popUp',[$(this),'设置为首页应用区相册封面',m.info,'<span class="popBtns blueBtn closeBtn">确定</span>','']);
						}
					]);
 				});
 			},
 			delPhoto : function(){
 				self.$delPhotoBtn.bind('click',function(){
 					var $formObj = $(this).children('form');
					self.plug('popUp',[$(this),'照片删除','你确定要删除这张照片？','<span class="popBtns blueBtn callbackBtn">确定</span><span class="popBtns closeBtn">取消</span>',function(){
						self.view('waitTip',['删除照片']);
						$formObj.submit();
					}]);
 				});
 			},
 			togglePage : function(arg){ // arg[0]为点击区域，arg[1]为按键asscii码
 				arg[0].bind('click',function(){
 					var href = arg[0].attr('href');
 					if(href !== ''){
						window.location.href = href;
					}
 				});
 				$(document).bind('keydown',function(e){
 					if(e.keyCode === arg[1]){
 						var href = arg[0].attr('href');
 						if(href !== ''){
 							window.location.href = href;
 						}
 					}
 				});
 			},
 			rotatePic : function(){
 				self.$picViewerPic.hover(
 					function(){
	 					if($(this).children('img').attr('src')){
	 						if(self.rotateFlag){
	 							self.$rotateToLeft.show().unbind('click').bind('click',function(){
									self.view('getRotatedPic',['left']);
				 				});
				 				self.$rotateToRight.show().unbind('click').bind('click',function(){
				 					self.view('getRotatedPic',['right']);
				 				});
	 						}
			 			}
 					},
 					function(){
 						self.$rotateToLeft.hide();
 						self.$rotateToRight.hide();
 					}
 				);
 			},
 			saveRotate : function(){ //保存旋转
				self.model('postData',[self.rotateAjaxUrl,{picId:self.picId,direction:arg[0]},function(m){
					if(m.status == 1){
						self.$picViewerPic.css({
							background:'none'
						}).children('img').attr('src',m.data.picUrl).show();

						self.$rotateToLeft.show();
						self.$rotateToRight.show();
						self.$prevArea.show();
						self.$nextArea.show();

						self.rotateFlag = true;
					}
					else{
						$.alert(m.info,'提示');
						$.closeSubPop();
						self.rotateFlag = false;
					}
				},
				function(XMLHttpRequest,textStatus,errorThrown){
					$.alert('网络连接失败，请检查您的网络连接。','提示');
					$.closeSubPop();
					self.rotateFlag = false;
				}]);
 			},
 			movePic: function(){
				$(document).bind('click',function(){
					self.$downIcon.css({'border-color':'#fff','padding-left':0,'background-position':'40px 10px'}).next('.albumUl').hide();
				});

				self.$downIcon.unbind('click').bind('click',function(e){
					if($(this).next('.albumUl').is(':hidden')){
						$(this).css({'border-color':'#ccc','padding-left':'10px','background-position':'50px 10px'}).next('.albumUl').show();
						$(this).next('.albumUl').find('a').unbind('click').bind('click',function(e){
							var albumId = $(this).attr('albumId');
							self.plug('popUp',[$(this),'移动照片','您确定要移动本张照片吗？','<span class="popBtns blueBtn callbackBtn">确定</span><span class="popBtns closeBtn">取消</span>',function(){
								self.view('waitTip',['移动照片']);
								self.model('postData',[self.movePicUrl,{picId:self.picId,albumId:albumId},function(m){
									if(m.status == 1){
										window.location.href = m.data.photoNext;
									}
									else{
										$.alert(m.info,'提示');
										$.closeSubPop();
									}
								},
								function(XMLHttpRequest,textStatus,errorThrown){
									$.alert('网络连接失败，请检查您的网络连接。','提示');
									$.closeSubPop();
								}]);
							}]);
							
							e.stopPropagation();
						});
					}
					else{
						$(this).css({'border-color':'#fff','padding-left':0,'background-position':'40px 10px'}).next('.albumUl').hide();
					}
					e.stopPropagation();
				});
 			},
 			zoomIn: function(){
 				self.$picViewerPic.children('.picZoom').bind('click',function(){
 					var imgObj = $(this).prevAll('img');
 					var _src = imgObj.attr('ysrc'); //源图路径
 					var _height = parseInt(imgObj.attr('imgH')); //源图高度
 					var _width = parseInt(imgObj.attr('imgW')); //源图宽度
 					var winWidth = parseInt($(window).width()); //窗口宽度
 					var winHeight = parseInt($(window).height()); //窗口高度
 					var docWidth = parseInt($(document).width()); //文档宽度
 					var docHeight = parseInt($(document).height()); //文档高度
 					var maxWidth = Math.max(_width,winWidth,docWidth); //判断最大宽度
 					var maxHeight = Math.max(_height,winHeight,docHeight); //判断最大高度

 					var _left = Math.abs(maxWidth - _width)/2; //绝对定位x坐标
 					var _top = Math.abs(maxHeight - _height)/2; //绝对定位y坐标

 					$('body').append('<div class="misk"></div>').children('.misk').css({
 						'position':'absolute',
 						'left':0,
 						'top':0,
 						'background-color':'#000',
 						'text-align':'center',
 						'padding-top':0,
 						'height':maxHeight+'px',
 						'width':maxWidth+'px',
 						'z-index':'999'
 					}).html('<img src="'+_src+'" />').children('img').css({
 						'position':'absolute',
 						'left':_left+'px',
 						'top':_top+'px',
 						'cursor':'url(http://static.duankou.dev/misc/img/system/zoom_out.cur),auto'
 					}).bind('click',function(){
 						$(this).parent().remove();
 					});

 					//窗口改变时
 					$(window).resize(function(){ 
						var rdw = parseInt($(document).width());
						var rdh = parseInt($(document).height());
						var rmaxWidth = Math.max(_width,rdw); //判断最大宽度
 						var rmaxHeight = Math.max(_height,rdh); //判断最大高度

						var rleft = Math.abs(rmaxWidth -_width)/2; //绝对定位x坐标
						var rtop = Math.abs(rmaxHeight -_height)/2; //绝对定位x坐标

						$('.misk').css({ //遮罩层大小自适应文档大小
							'width':rmaxWidth+'px',
							'height':rmaxHeight+'px'
						}).children('img').css({
							'left':rleft+'px',
							'top':rtop+'px'
						}); 
					});

 				});
 			}
 		};
 		
 		return _class[method](arg);
 	},
 	plug : function(method,arg){
 		var self = this;
 		var _class = {
 			editText : function(arg){
 				arg[0].editText({
					txt:arg[1],
					djaxUrl:arg[2],
					objId:arg[3],
					maxNum:arg[4]
				});
 			},
 			popUp : function(arg){
 				arg[0].popUp({
					width:400,
					title:arg[1],
					content:'<span style="padding:10px; display:block;"><strong>' + arg[2] + '</strong></span>',
					mask:true,
		            maskMode:false,
					buttons:arg[3],
					callback:arg[4]
				});
 			},
 			subPopUp : function(arg){
 				arg[0].subPopUp({
					width:400,
					title:arg[1],
					content:'<span style="padding:10px; display:block;"><strong>' + arg[2] + '</strong></span>',
					mask:true,
		            maskMode:false,
					buttons:arg[3],
					callback:arg[4]
				});
 			},
 			commentEasy : function(){
 				self.$commentEasyBtn.commentEasy(self.commentOptions);
 			}
 		};

 		return _class[method](arg);
 	},
 	model : function(method,arg){
 		var self = this;
 		var _class = {
 			postData : function(arg){
 				$.djax({
					type:'post',
					url:arg[0],
					data:arg[1],
					dataType:'json',
					success:arg[2],
					error:arg[3]
				});
 			},	
 			getJsonData : function(arg){
 				$.getJSON(arg[0], arg[1], arg[2]);
 			}
 		};
 		
 		return _class[method](arg);
 	}
 }

 $(function(){
 	new CLASS_PICVIEWEROPT();
 })