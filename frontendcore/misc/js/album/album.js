 /*
 * @ name: album.js
 * @ author: lishijun
 * @ desc:  相册主JS
 *
 */

$(function(){

	var miscpath = CONFIG['misc_path'];
	var picIdStr = '';  
	var UploadPicMaxNum = 100; //每次图片上传最多上传张数
	var successUploadNum = 0; //成功上传张数
	var coverPicId = '';      

	var dkcode = CONFIG['dkcode'];
	var action_dkcode = CONFIG['action_dkcode'];
	var web_id = CONFIG['web_id'];
	var aid = $('#album_id').val();

	var addParam = '';
	var updataPicUrl = '';
	var savePicUrl = '';
	var albumExplainUrl = '';
	var albumPicMoveUrl = '';
	var editAlbumUrl = '';
	var delAlbumUrl = '';
	var delPicUrl = '';
	var movePicUrl = '';
	var addDescUrl = '';
	var albumListAjaxUrl = '';
	var picListAjaxUrl = '';
	var commentPicListAjaxUrl = '';

	//权限HTML片段
	var permissionHTML = '';

	//flash路径
	var flashUrl = miscpath+'flash/plug-flash/jQuery-uploadify/uploadify.swf';
	

	if(web_id == false){
		addParam = dkcode;
		updataPicUrl = mk_url('album/index/uploadPhoto');                                                                                                                             
		savePicUrl = mk_url('album/index/addPhoto', {'dkcode': dkcode});                                       
		albumExplainUrl = mk_url('album/index/modifyAlbumDesc', {'dkcode': dkcode});                            
		albumMove_url = mk_url('album/index/orderAlbum', {'dkcode': dkcode}); 
		albumPicMoveUrl = mk_url('album/index/orderPhoto', {'dkcode': dkcode});                                        
		editAlbumUrl = mk_url('album/index/modifyAlbum', {'dkcode': dkcode});
		delAlbumUrl = mk_url('album/index/delAlbum', {'dkcode': dkcode});
		delPicUrl = mk_url('album/index/delPhoto', {'dkcode': dkcode});                    
		movePicUrl = mk_url('album/index/movePhoto', {'dkcode': dkcode});          
		addDescUrl = mk_url('album/index/editAlbumDesc', {'dkcode': dkcode});
		albumListAjaxUrl = mk_url('album/index/albumMore', {'dkcode': action_dkcode});
		picListAjaxUrl = mk_url('album/index/photosMore', {'dkcode':action_dkcode,'albumid': aid});
		commentPicListAjaxUrl = mk_url('album/index/graphicPhotoMore', {'dkcode':action_dkcode,'albumid': aid});

		permissionHTML = '<div class="dropWrap dropMenu listPermission" oid="" s="1" uid="-1" style="display:none;">'+
						 	'<input type="hidden" name="permissions" value="1" />'+
						  '</div>';
	}
	else{
		addParam = web_id;
		updataPicUrl = mk_url('walbum/photo/upload');
		savePicUrl = mk_url('walbum/photo/add',{'web_id':web_id});                                                                   
		albumExplainUrl = mk_url('walbum/album/editAlbumDesc',{'web_id':web_id});                                                                
		albumMove_url = mk_url('walbum/album/orderAlbum',{'web_id':web_id});                        
		albumPicMoveUrl = mk_url('walbum/photo/orderPhoto',{'web_id':web_id}); 				                                            
		editAlbumUrl = mk_url('walbum/album/edit',{'web_id':web_id});							   
		delAlbumUrl = mk_url('walbum/album/delete',{'web_id':web_id});  							 
		delPicUrl = mk_url('walbum/photo/delete',{'web_id':web_id});							                                 
		movePicUrl = mk_url('walbum/photo/move',{'web_id':web_id});   						              				
		addDescUrl = mk_url('walbum/album/editAlbumDesc',{'web_id':web_id});
		albumListAjaxUrl = mk_url('walbum/album/getAlbumMore',{'web_id':web_id});
		picListAjaxUrl = mk_url('walbum/photo/getPhotoMore',{'web_id':web_id,'albumid':aid});
		commentPicListAjaxUrl = mk_url('walbum/photo/getCommentPhotoMore',{'web_id':web_id,'albumid':aid});
	}

	//是否是图片服务器处理图片上传
	if($.trim($('#upload_url').val()) != ''){
		updataPicUrl = $.trim($('#upload_url').val());
	}
	

	var commentOptions = { 
		minNum:3,
		UID:CONFIG['u_id'],
		userName:CONFIG['u_name'],
		avatar:CONFIG['u_head'],
		userPageUrl:$('#hd_userPageUrl').val(),
        relayCallback:function (obj,_arg) {
            var comment = new ui.Comment();
            comment.share(obj,_arg);
        }
	};
	
	var waitTip = function(tip){
		$.fn.subPopUp({
			width:560,
			title:tip,
			content:'<strong style="display:block;padding:10px;">正在'+tip+'，您的浏览器可能会有稍许停顿，请稍候......</strong>',
			mask:true,
			maskMode:false,
			buttons:''
		});
	}

	//@ Start 获取当前日期
	var now = new Date();
	var year = now.getYear();
	if($.browser.msie){

	}else{
		year = year + 1900;
	}
	var month = now.getMonth()+1;
	var day= now.getDate();
	var nowDate = year + "年" + month + "月" + day + "日";
	//@ End 获取当前日期

	//输入框效果（input,textarea）
	var getFocus = function(info){
		info.obj.bind('focus',function(){
			if($.trim(info.obj.val()) === info.text){
				info.obj.val('');
			}
		}).bind('blur',function(){
			var value = $.trim(info.obj.val());
			if(value === ''){
				info.obj.val(info.text);
			}
			if(value.length > info.num){
				$.alert('图片描述不能多于'+info.num+'个字。','提示');
				info.obj.focus();
				return false;
			}
		});
	}

	var submitFlag = false; //提交操作标识
	var closeFlag = false; //是否关闭标志

	//@ Start 编辑相册
	var editAlbum_albumID;              //@ 相册ID
	var editAlbum_aname;                //@ 相册名称
	var editAlbum_txtdesc;              //@ 相册说明
	var editAlbum_data;                 //@ djax POST DATA
	var editAlbum_permission;
	var eidtAlbum = function(btnName){  //@ 编辑相册弹出框函数
		if($(btnName).size()>0){
			$(btnName).click(function(){
				editAlbum_albumID = $('#album_id').val();
				var editAlbumType = $('input.album_type').val();

				$(this).popUp({
					width:557,
					title:'编辑相册',
					content:$('#edit_album').html(),
					mask:true,
		            maskMode:false,
					buttons:'<span class="popBtns blueBtn callbackBtn">保存</span><span class="popBtns closeBtn">取消</span>',
					callback:function(){
						editAlbum_aname = $('#popUp').find('.editAlbum_name').val();
						editAlbum_txtdesc = $('#popUp').find('.editAlbum_introduction').val();
						if($.trim(editAlbum_txtdesc) === '添加相册说明'){
							editAlbum_txtdesc = '';
						}
						editAlbum_permission = $('#popUp').find('div.listPermission').find('input.albumEdtListPermission').val();

						if($.trim(editAlbum_aname)==''){
							$('#popUp tr.tc_red').show().find('td').text('* 请输入相册名称!');
						}else{
							waitTip('保存修改');
							eidtAlbum_djax();
						}
					}
				});

				$('#popUp').find('div.listPermission').removeClass('albumEdtListPermission').dropdown({
					permission: {
						type: 'album',
						dataType:'jsonp',
						url:mk_url('album/access/set')
					},
					position: 'right'
				});
			});
		};
	};
	var eidtAlbum_djax = function(){//@ 编辑相册AJAX函数
		editAlbum_data = {
			albumID:editAlbum_albumID,
		    albumName:editAlbum_aname,
		    albumExplain:editAlbum_txtdesc,			
			permission:editAlbum_permission
		};
		$.djax({
			type:'post',
			url:editAlbumUrl,
			dataType:'json',
			data:editAlbum_data,
			success:function(m){
				if(m.status == 1){
					window.location.href = window.location.href;
				}else{
					$.alert(m.info,'提示');
					$.closeSubPop();
				}
			},
			error:function(XMLHttpRequest,textStatus,errorThrown){
				alert('网络连接失败，请检查您的网络连接。','提示');
				$.closeSubPop();
			}
		});
	};
	var deleteAlbum = function(btnName){//@ 删除相册函数		
		if($(btnName).size()>0){
			$(btnName).click(function(){
				editAlbum_albumID = $('#album_id').val();
				$(this).popUp({
					width:557,
					title:'删除相册',
					content:'<strong style="display:block; padding:10px;">您确定要删除该相册吗？（如果该相册存有照片，您的照片也将一起被删除，并且无法恢复。）</strong>',
					mask:true,
		            maskMode:false,
					buttons:'<span class="popBtns blueBtn callbackBtn">确定</span><span class="popBtns closeBtn">取消</span>',
					callback:function(){
						waitTip('删除相册');

						$.djax({
							type:'post',
							url:delAlbumUrl,
							dataType:'json',
							data:({albumID:editAlbum_albumID}),
							success:function(m){
								if(m.status == 1){
									window.location.href = m.data.album_url; //跳转到相册列表
								}else{
									$.alert(m.info,'提示');
									$.closeSubPop();
								};
							},
							error:function(XMLHttpRequest,textStatus,errorThrown){
								$.alert('网络连接失败，请检查您的网络连接。','提示');
								$.closeSubPop();
							}
						});
					}
				});
				return false;
			});
		}
	};
	
	deleteAlbum('#deleteAlbum_btn'); //执行删除相册函数
	eidtAlbum('.editAlbum_btn'); //执行编辑相册函数
	//@ End 编辑相册
	
	
	//@ Start 上传图片
	var updataPic = function(btnName,updataType){
		if($(btnName).size()>0){
			var cAlbum = '';
			successUploadNum = 0;

			if(updataType == 1){
				cAlbum = '<div class="chooseAlbum" style="display:none;float:left;position:relative;color:#000;">'+
							'<span class="txtTip">新建相册</span>：<i class="listDownIcon" id="listDownIcon"></i>'+
		  					'<input class="choose_album_name" type="text" maxlength="50" value="请选择相册" />'+
		  					'<div class="createAndSelectAlbum">'+$('.createAndSelectAlbum').html()+'</div>'+
		  				  '</div>'+permissionHTML;
			}

			$(btnName).click(function(){
				$(this).popUp({
					width:800,
					title:"上传照片",
					content:'<div class="tipArea"><div class="progressTip"><div class="progressBar"></div></div><div class="countTip">正在上传：<em>...</em></div></div><div class="itemsArea"><div class="uploadBtnArea"><input type="file" id="uploadBtn" /></div><div class="uploadTip"><p>上传图片请注意：</p><p>1.支持的图片格式：jpg、jpeg、png、gif</p><p>2.最大图片大小：10M</p><p>3.每次最多可以上传'+UploadPicMaxNum+'张照片</p></div></div>',
					mask:true,
		            maskMode:false,
					buttons:'<span class="popBtns blueBtn callbackBtn uploadComplete" style="display:none; height:24px; line-height:24px;">完成上传</span><input type="file" id="updataPic_file" style="display:none;" />'+cAlbum,
					callback:function(){
						closeFlag = false;
						submitFlag = true;
						submitResult();
					}
				});


				//改变弹窗位置
				var top = parseInt($('#popUp').css('margin-top'))-50;
				$('#popUp').css({'margin-top':top+'px'});

				//添加关闭按钮
				$('#popUp').find('.popTitle').css({'position':'relative'}).append('<a href="javascript:;" class="closePop"></a>').children('.closePop').bind('click',function(){
					var picIds = '';

					$('#popUp').find('.itemsArea').children('.item').each(function(){
						var picId = $(this).children('.img').attr('picId');
						
						if(picId != undefined && picId != ''){
							picIds += picId + ',';
						}
					});

					if(picIds !== ''){
						closeFlag = true;
						submitFlag = true;
						submitResult();
					}
					else{
						successUploadNum = 0;

						$.closePopUp();
						$(this).remove();
					}
				});
				
				$(".popBtnsWrap").addClass("clearfix").find(".popBtns").addClass("popBtns_float");

				//选择相册
				$('#popUp .popBtnsWrap').children('.chooseAlbum').children('i').bind('click',function(e){
					var obj = $(this);
					var ulParent = obj.nextAll('.createAndSelectAlbum');
					var ulParentH = ulParent.outerHeight(true);
					
					if(ulParent.children('ul').children('li').size() <= 0){
						ulParent.children('ul').html('<li class="noMore">没有更多相册</li>');
						ulParent.bind('click',function(e){e.stopPropagation()});
					}

					ulParent.children('ul').height(218+'px').end().css({'top':'-'+(ulParentH+1)+'px'});

					if(ulParent.is(':hidden')){
						ulParent.show().children('ul').children('li').bind('mouseover',function(){
							$(this).addClass('liHover').siblings().removeClass('liHover');
						});
						
						ulParent.show().children('ul').children('li').unbind('click').bind('click',function(){
							var aTxt = $.trim($(this).text())=='没有更多相册'? nowDate : $(this).text(); //相册名
							var chooseAlbumId = $(this).attr('albumId'); //相册ID

							obj.parent().children('span.txtTip').text('选择相册');
							if($.trim($(this).text()) !== '没有更多相册'){
								obj.parent().next('.dropWrap').hide(); //隐藏权限设置
								obj.next('input').attr({'chooseAlbumId':chooseAlbumId,'disabled':'disabled'}).val(aTxt);

								ulParent.children('.createAlbum').show().children('.createBtn').bind('click',function(){
									var newName = $(this).prev().children('input').val();
									newName = newName == '新建相册'? '' : newName;

									obj.parent().children('span.txtTip').text('新建相册');
									obj.next('input').removeAttr('chooseAlbumId').removeAttr('disabled').val(newName).focus();
									ulParent.children('.createAlbum').hide().end().hide();
									obj.parent().next('.dropWrap').show(); //打开权限设置
								});
							}

							ulParent.hide(); //隐藏列表
							//输入框光标效果
							getFocus({obj:ulParent.children('.createAlbum').find('input'),text:'新建相册',num:50});
						});
					}
					else{
						ulParent.hide();
					}
					e.stopPropagation();
				});

				$('#popUp').find('.popBtnsWrap').find('.createAlbum').bind('click',function(e){
					e.stopPropagation();
				});

				//点击文档隐藏列表
				var listUlObj = $('#popUp .popBtnsWrap').children('.chooseAlbum').find('ul');
				$(document).bind('click',function(){
					listUlObj.parent().hide();
				});

				updataPic_selectFile($('#uploadBtn'),['upbtn.jpg',241,65]);
				
				return false;
			});

			
			var submitResult = function(){
				if(updataType == 1){
					var albumId = $('#popUp .popBtnsWrap').children('.chooseAlbum').children('input').attr('chooseAlbumId');
				}
				else if(updataType == 2){
					var albumId = $('#album_id').val();
				}
				var picIds = '';
				var picInfos = [];
				var postData = {};

				$('#popUp').find('.itemsArea').children('.item').each(function(){
					var picId = $(this).children('.img').attr('picId');
					var picDesc = $(this).children('.desc').children('textarea').val();
					var picInfo = {};

					if(picDesc === '添加描述'){
						picDesc = '';
					}

					if(picId != undefined && picId != ''){
						picIds += picId + ',';
						picInfo.picId = picId;
						picInfo.picDesc = picDesc;
						picInfos.push(picInfo);
					}
				});

				if(picIds === ''){
					$.alert('上传的照片数量为空','提示');
					return false;
				}
				else if(albumId == '' || albumId == undefined){
					var newAlbumName = $('#popUp').find('.popBtnsWrap').children('.chooseAlbum').children('.choose_album_name').val();
					var newAlbumPermission= $('#popUp').find('.popBtnsWrap').find('.listPermission').children('input[name="permissions"]').val();
					if($.trim(newAlbumName) === ''){
						$.alert('请选择一个相册','提示');
						return false;
					}
					postData = {coverPicId:coverPicId,picInfos:picInfos,closeFlag:closeFlag,createAlbum:true,newAlbumName:newAlbumName,newAlbumPermission:newAlbumPermission};
				}
				else{
					postData = {coverPicId:coverPicId,picInfos:picInfos,closeFlag:closeFlag,createAlbum:false,albumId:albumId};
				}

				waitTip('保存照片');
					
				$.djax({
					type:'post',
					url:savePicUrl,
					dataType:'json',
					data:postData,
					success:function(m){
						if(m.status == 1){
							window.location.href = m.data.url; //跳转到图片编辑页面
						}else{
							submitFlag = false;
							$.alert(m.info,'提示');
							$.closeSubPop();
						};
					},
					error:function(XMLHttpRequest,textStatus,errorThrown){
						submitFlag = false;
						$.alert('网络连接失败，请检查您的网络连接','提示');
						$.closeSubPop();
					}
				});
			};


			//@ Start 绑定上传文件域多文件上传事件 函数
			var updataPic_selectFile = function(obj,btnArr){
				//@Start: add
				var progressBar = $('#popUp').find('.tipArea').children('.progressTip'); //进度条
				var $itemsArea = $('#popUp').find('.itemsArea');
				var $popBtnsWrap = $('#popUp').find('.popBtnsWrap');
				var nowStartIndex = 0;
				var totalCount = 0; //保存要上传图片总数 
				var totalWidth = (parseInt(progressBar.css('width')) === 0) ? 750 : parseInt(progressBar.css('width')); //总的进度条宽度 //三目运算用于兼容opra
				var oneWidth = 0; //每个文件进度条宽度
				//@End: add

				var theSpeed = 2000; //文件上传速度
				var fillWidth = 0; //单张进度条宽度

				obj.uploadify({
					'uploader':flashUrl,
					'script':updataPicUrl,
					'fileExt':'*.jpg;*jpeg;*.gif;*.png',
					'fileDesc':'文件类型(*.JPG,*.JPEG*.GIF,*.PNG)',
					'method':'GET',
					'scriptData':{
						'addParam':addParam,
						'flashUploadUid':$('#flashuploaduid').val(),  //uid
						'uploadKey':$('#upload_key').val()
					},
					'scriptAccess':'always',
					'folder':miscpath+'temp',
					'width':btnArr[1],							
					'buttonImg':miscpath+'img/system/'+btnArr[0],
					'height':btnArr[2],
					'multi':true,
					'auto':true,
					'queueSizeLimit':UploadPicMaxNum,
					'ShowProgressBar':false, //隐藏单张图片进度条
					//@ Start 回调函数 上传时
					'onSelectOnce':function(e,data){ //函数添加参数
						$('.uploadBtnArea').fadeTo(1,0);

						/*start: 添加权限*/
						var $permissionObj = $('#popUp').find('.listPermission');
						var disabled = $('#popUp').find('.popBtnsWrap').find('input.choose_album_name').attr('disabled');

						if(disabled == undefined || disabled == ''){
							$permissionObj.show();
							if(!$permissionObj.children('div').is('.triggerBtn')){
								$permissionObj.dropdown({
									permission: {
										type: 'album',
										dataType:'jsonp',
										url:mk_url('album/access/set')
									},
									position: 'right'
								});

								//解决a标签href非链接与beforeunload冲突问题
								$permissionObj.children('.dropList').find('a').each(function(){
									$(this).attr('href','#this');
								});
							}
						}
						/*end: 添加权限*/

						//选择相册
						if($('#popUp').find('.chooseAlbum').is(':hidden')){
							$('#popUp').find('.chooseAlbum').show().children('input.choose_album_name').val(nowDate).focus().select();
						}
						//完成上传按钮隐藏
						if($('#popUp').find('span').is('.uploadComplete')){
							$('#popUp').find('.uploadComplete').hide();
						}
						//隐藏上传提示
						if($('#popUp').find('div').is('.uploadTip')){
							$('#popUp').find('.uploadTip').remove();
						}

						progressBar.find('.progressBar').css('width',0);
						progressBar.next('.countTip').html('正在上传：<em>...</em>');

						totalCount = data.fileCount; //统计要上传图片总数量
						nowStartIndex = $itemsArea.children('.item').length;
						oneWidth = totalWidth/totalCount; //每个文件进度条宽度
						$('#popUp').find('.tipArea').show();

						var totalPicItem = '';

						for(var i=0; i<data.fileCount; i++){
							totalPicItem += '<div class="item uncomplete"><div class="progressTip"><div class="progressBar"></div></div><p>加载中……</p></div>';
						}

						$itemsArea.append(totalPicItem);

						//单张进度宽度
						fillWidth = parseInt($itemsArea.children('.uncomplete').children('.progressTip').first().css('width'));
					},
					//@ End 回调函数 上传时
					'onProgress':function(e,queueId,fileObj,data){
						theSpeed = data.speed;
					},
					'onOpen':function(e,queueId,fileObj){
						var theTime = Math.ceil(fileObj.size/theSpeed);

						$itemsArea.children('.uncomplete').first().find('.progressBar').animate({'width':fillWidth+'px'},theTime);
					},
					//@ Start 回调函数 单个文件上传完成时
					'onComplete':function(e,queueId,fileObj,response,data){
						var nowCount = totalCount - data.fileCount; //当前上传图片个数
						progressBar.find('.progressBar').css('width',nowCount*oneWidth+'px');
						progressBar.next('.countTip').children('em').text(nowCount + '张 / ' + totalCount + '张');
						// //@End: 检测总上传进度

						if($('#popUp').find('.tipArea').is(':hidden')){
							$('#popUp').find('.tipArea').show();
						}

						var responseJSON = eval('('+response+')');
						
						if(responseJSON.state == '1'){
							if(responseJSON.msg.img_s != ''){
								var itemContent = '<div class="img" picId='+responseJSON.msg.photo_id+'><img src="'+responseJSON.msg.img_s+'" /><div class="imgMask"><em>设为封面</em><i>删除</i></div></div><div class="desc"><textarea maxLength="140">添加描述</textarea></div>';
								picIdStr += responseJSON.msg.photo_id + ',';
								$itemsArea.children('.uncomplete').first().css({'border':'none','background':'none'}).html(itemContent).removeClass('uncomplete');
								successUploadNum++;
							}
							else{
								$itemsArea.children('.uncomplete').first().addClass('errorTip').removeClass('uncomplete').html('图片上传失败<div class="imgMask"><i>删除</i></div>');
							}
						}
						else if(responseJSON.state == '0'){
							$itemsArea.children('.uncomplete').first().addClass('errorTip').removeClass('uncomplete').html(responseJSON.msg + '<div class="imgMask"><i>删除</i></div>');
						}


						//input,textarea文本光标处理
						$itemsArea.find('textarea').each(function(){
							getFocus({obj:$(this),text:'添加描述',num:140});
						});

						//缩略图列表单张图片操作
						$itemsArea.children('.item').children('.img').unbind('mouseover').unbind('mouseout').bind({
							'mouseover':function(e){
								//删除
								$(this).children('.imgMask').show().children('i').unbind('click').bind('click',function(){
									if($(this).prev().text() === '封面'){
										coverPicId = '';
									}

									$(this).closest('.item').remove();

									successUploadNum--;

									if(successUploadNum === 0){
										$('#popUp').find('.tipArea').hide();
									}
									
									if($itemsArea.children('.item').size() <= 0){
										//隐藏继续添加按钮
										$('#updataPic_file').remove();
										$popBtnsWrap.children('object').remove();

										//隐藏相册选择
										$popBtnsWrap.children('.chooseAlbum').hide();
										$popBtnsWrap.children('.dropWrap').hide();

										//显示上传按钮
										$itemsArea.html('<div class="uploadBtnArea"><input type="file" id="uploadBtn" /></div><div class="uploadTip"><p>上传图片请注意：</p><p>1.支持的图片格式：jpg、jpeg、png、gif</p><p>2.最大图片大小：4M</p><p>3.每次最多可以上传'+UploadPicMaxNum+'张照片</p></div>');
										updataPic_selectFile($('#uploadBtn'),['upbtn.jpg',241,65]);

										//完成上传按钮隐藏
										if($('#popUp').find('span').is('.uploadComplete')){
											$('#popUp').find('.uploadComplete').hide();
										}
									}
								});
								//设置封面
								$(this).children('.imgMask').show().children('em').unbind('click').bind('click',function(){
									if($(this).text() === '设为封面'){
										coverPicId = $(this).closest('.img').attr('picId');

										$(this).closest('.item').siblings().children('.img').bind('mouseout',function(e){
											$(this).children('.imgMask').hide();
											e.stopPropagation();
										}).children('.imgMask').children('em').css({'background-position':'left 4px'}).text('设为封面').end().hide();

										$(this).css({'background-position':'left -15px'}).text('封面');
										$(this).closest('.img').unbind('mouseout');
									}
									else if($(this).text() === '封面'){
										coverPicId = '';
										$(this).css({'background-position':'left 4px'}).text('设为封面').closest('.img').bind('mouseout',function(e){
											$(this).children('.imgMask').hide();
											e.stopPropagation();
										});
									}
								});
								e.stopPropagation();
							},
							'mouseout':function(e){
								$(this).children('.imgMask').hide();
								e.stopPropagation();
							}
						});

						//上传出错删除
						$itemsArea.children('.errorTip').unbind('mouseover').unbind('mouseout').bind({
							'mouseover':function(){
								$(this).children('.imgMask').show().children('i').unbind('click').bind('click',function(){
									$(this).closest('.item').remove();
									if($itemsArea.children('.item').size() <= 0){
										//隐藏继续添加按钮
										$('#updataPic_file').remove();
										$popBtnsWrap.children('object').remove();

										//隐藏相册选择
										$popBtnsWrap.children('.chooseAlbum').hide();
										$popBtnsWrap.children('.dropWrap').hide();

										//显示上传按钮
										$itemsArea.html('<div class="uploadBtnArea"><input type="file" id="uploadBtn" /></div><div class="uploadTip"><p>上传图片请注意：</p><p>1.支持的图片格式：jpg、jpeg、png、gif</p><p>2.最大图片大小：4M</p><p>3.每次最多可以上传'+UploadPicMaxNum+'张照片</p></div>');
										updataPic_selectFile($('#uploadBtn'),['upbtn.jpg',241,65]);
										
										//完成上传按钮隐藏
										if($('#popUp').find('span').is('.uploadComplete')){
											$('#popUp').find('.uploadComplete').hide();
										}
										//进度条隐藏
										if(successUploadNum === 0){
											$('#popUp').find('.tipArea').hide();
										}
									}
								});
							},
							'mouseout':function(){
								$(this).find('.imgMask').hide();
							}
						});

					},
					//@ End 回调函数 单个文件上传完成时
					//@ Start 回调函数 当队列上传完成时
					'onAllComplete':function(e,data){

						//隐藏进度条
						$('#popUp').find('.tipArea').hide();

						if($('.uploadBtnArea').children('object').size() > 0){
							$('.uploadBtnArea').height(0).width(0);
							$('.uploadBtnArea').children('object').height(0).width(0); //移除上传按钮
						}

						if($('#popUp').find('span').is('.uploadComplete')){
							$('#popUp').find('.uploadComplete').show();
						}
						if(picIdStr === ''){
							$popBtnsWrap.find('.uploadComplete').hide();
							//进度条隐藏
							$('#popUp').find('.tipArea').hide();
							//隐藏相册选择
							$popBtnsWrap.children('.chooseAlbum').hide();
							$popBtnsWrap.children('.dropWrap').hide();
						}

						//意外出现上传不成功时提示
						if($itemsArea.children('.uncomplete').size() > 0){
							$itemsArea.children('.uncomplete').each(function(){
								$(this).addClass('errorTip').removeClass('uncomplete').html('图片上传失败<div class="imgMask"><i>删除</i></div>');
							});
						}

						progressBar.next('.countTip').html('成功上传'+successUploadNum+'张照片！');

						
						//打开继续添加按钮
						if($popBtnsWrap.children('object').size() <= 0){
							$popBtnsWrap.children('.uploadComplete').after('<input type="file" id="updataPic_file" style="display:none;" />');

							if($.browser.mozilla){
								updataPic_selectFile($('#updataPic_file'),['selectImg.png',72,27]);
							}
							else{
								updataPic_selectFile($('#updataPic_file'),['selectImg.png',72,25]);
							}
							
						}
					},
					//@ End 回调函数 当队列上传完成时
					//@ Start 回调函数 当单个文件上传出错时触发
					'onError':function(e,ID,fileObj,errorObj){
						$.alert(errorObj.type+':'+errorObj.info,'错误提示');
					}
					//@ End 回调函数 当单个文件上传出错时触发
				});
			};
			//@ End 绑定上传文件域多文件上传事件 函数
		}
	};
	
	updataPic('.updataPic_btn',1);//相册列表下上传图片 //updataPic_btn id改为class
	updataPic('#updataPic_picList',2);//图片列表下上传图片
	//@ End 上传图片
	
	//@ Start 图片排序
	var nowmouseX;
	var nowmouseX2;
	var nowmouseY;
	var nowmouseY2;
	var albumMove = function(albumMover_url,albumMove_type,ulbumMove_obj){
		var moverA_Index;     //@ 当前序号
		var moverB_Index;     //@ 移动后的序号
		var mover_ID;         //@ 相册ID
		var moverA_id;        //@ 移动目标ID
		var moverB_id;        //@ 移动后位置目标ID (不知道怎么表述了，将就看吧。)
		if(ulbumMove_obj.size()>0){
			ulbumMove_obj.find('li a.photoLink').hover(function(){$(this).find('i').show();},function(){$(this).find('i').hide();}); //控制'移动'图标
			ulbumMove_obj.find('ul').dragsort({//绑定拖动
				dragSelector:'a.photoLink',
				dragBetween:false,
				dragEnd:function(){
					moverA_Index=$(this).attr('nowIndex');
					moverB_Index=ulbumMove_obj.find('>ul>li').index($(this));
					moverA_id=$(this).find('input:hidden').val();

					if(moverA_Index>moverB_Index){
						moverB_id=ulbumMove_obj.find('>ul>li').eq(moverB_Index+1).find('input:hidden').val();
					}else if(moverA_Index<moverB_Index){
						moverB_id=ulbumMove_obj.find('>ul>li').eq(moverB_Index-1).find('input:hidden').val();
					}

					if(moverA_id && moverB_id){
						if(albumMove_type==1){
							albumMove_djax('moverA_id='+moverA_id+'&moverB_id='+moverB_id);
						}else if(albumMove_type==2){
							mover_ID=$('#album_myID_forMover').val();
							albumMove_djax('moverA_id='+moverA_id+'&moverB_id='+moverB_id+'&mover_ID='+mover_ID);
						}else{
							$.alert('函数参数错误','提示');
						}
					}
				}
			});
			//获取当前INDEX
			ulbumMove_obj.find('>ul>li').each(function(i){
				$(this).attr('nowIndex',i);
			});
			//拖动完成后的AJAX函数
			var albumMove_djax = function(albumMove_djaxData){
				$.djax({
					url:albumMover_url,
					type:'post',
					data:albumMove_djaxData,
					success:function(){
						ulbumMove_obj.find('>ul>li').each(function(i){
							$(this).attr('nowIndex',i);
						});
					}
				});
			};
			//@ Star 点击事件触发
			var albumClick = function(mover_obj){
				$(mover_obj).find('ul a.photoLink').each(function(i){
					$(this).click(function(){
						return false;
					});
					$(this).mousedown(function(event){
						if(event.which === 1 || event.whick === 0){ //左键操作，防止右键作用 1为火狐，0为ie
							nowmouseX=Number(event.screenX);
							nowmouseY=Number(event.screenY);
						}
					});
					$(this).mouseup(function(event){
						if(event.which === 1 || event.whick === 0){ //左键操作，防止右键作用 1为火狐，0为ie
							nowmouseX2=Number(event.screenX);
							nowmouseY2=Number(event.screenY);
							if(nowmouseX==nowmouseX2 && nowmouseY==nowmouseY2){
								window.location.href=$(this).attr('url');
							}
						}
					});
				});
			};
			if($('div.album_move').size() > 0){
				albumClick(ulbumMove_obj);
			}
			//@ End 点击事件触发
		};
	};
	albumMove(albumMove_url,1,$('.album_move'));
	albumMove(albumPicMoveUrl,2,$('.albumPic_move'));


	//没有权限也可以访问
	$('div.album_list ul, div.album_picList ul').delegate('a.photoLink','click',function(){
		window.location.href = $(this).attr('url');
	});
	//@ End 图片排序
	
	
	//@ Start 调用评论的函数
	if($('.comment_easy').commentEasy){
		var com = $('.comment_easy').commentEasy(commentOptions);
	}
	//@ End 调用评论的函数
	
	
	//@ Start 相片列表批量管理
	var manageObj = $('#managing');
	var allChecked = $('#allChecked');
	var downIcon = $('.downIcon');
	var checkObjs, toCheck;

	$('#manageMore').bind('click',function(){
		toCheck = $('.album_picList .toCheck').find('span.checkBar');
		checkObjs = toCheck.find("input[type='checkbox']");

		//初始化checkbox
		checkObjs.removeAttr('checked');
		allChecked.removeAttr('checked');

		if ($(this).html() === '批量管理'){
			toCheck.show();
			manageObj.show();
			$(this).css('background-image','none');
			
			//本页全选点击事件
			allChecked.bind('click',function(){
				toCheck = $('.album_picList .toCheck').find('span.checkBar');
				checkObjs = toCheck.find("input[type='checkbox']");

				if ($(this).is(':checked')){
					checkObjs.attr('checked','checked');
				}
				else {
					checkObjs.removeAttr('checked');
				}
				
			})
			
			//如果有checkbox取消选中时，取消本页全选
			checkObjs.live('click',function(){
				allChecked.removeAttr('checked');
			})

			//批量删除照片
			$('#delPhoto').bind('click',function(){
				var checkedArr = [];
				checkObjs.each(function(){
					if ($(this).is(':checked')){
						checkedArr.push($(this).closest('.toCheck').prevAll("input[type='hidden']").val());
					}
				})
				if (checkedArr.length === 0){
					$.alert('至少要选择一张要删除的照片','提示');
				}
				else {
					$(this).popUp({
						width:557,
						title:'删除照片',
						content:'<strong style="display:block; padding:10px;">您确定要删除这些照片吗？您的照片一旦删除，将无法恢复。</strong>',
						mask:true,
						maskMode:false,
						buttons:'<span class="popBtns blueBtn callbackBtn">确定</span><span class="popBtns closeBtn">取消</span>',
						callback:function(){
							waitTip('删除照片');

							$.djax({
								type:'post',
								url:delPicUrl,
								dataType:'json',
								data:{delArr:checkedArr}, //传递数组数据
								success:function(m){
									if(m.status == 1){
										window.location.href = m.data.album_url;
									}
								},
								error:function(XMLHttpRequest,textStatus,errorThrown){
									$.alert('网络连接失败，请检查您的网络连接。','提示');
									$.closeSubPop();
								}
							});
						}
					});
				}
			})

			//批量移动照片
			$(document).bind('click',function(){
				downIcon.css({'border-color':'#fff'}).next('.albumUl').hide();
			});

			downIcon.unbind('click').bind('click',function(e){
				if($(this).next('.albumUl').is(':hidden')){
					$(this).css({'border-color':'#ccc'}).next('.albumUl').show();
					$(this).next('.albumUl').find('a').bind('click',function(e){
						var albumId = $(this).attr('albumId');
						var checkedArr = [];

						checkObjs.each(function(){
							if ($(this).is(':checked')){
								checkedArr.push($(this).closest('.toCheck').prevAll("input[type='hidden']").val());
							}
						})
						if (checkedArr.length === 0){
							$.alert('至少要选择一张要移动的照片','提示');
						}
						else {
							$(this).popUp({
								width:557,
								title:'移动照片',
								content:'<strong style="display:block; padding:10px;">您确定要移动这些照片吗？</strong>',
								mask:true,
								maskMode:false,
								buttons:'<span class="popBtns blueBtn callbackBtn">确定</span><span class="popBtns closeBtn">取消</span>',
								callback:function(){
									waitTip('移动照片');

									$.djax({
										type:'post',
										url:movePicUrl,
										dataType:'json',
										data:{picId:checkedArr,albumId:albumId}, //传递数组数据
										success:function(m){
											if(m.status == 1){
												window.location.href = window.location.href;
											}
											else{
												$.alert(m.info,'提示');
												$.closeSubPop();
											}
										},
										error:function(XMLHttpRequest,textStatus,errorThrown){
											$.alert('网络连接失败，请检查您的网络连接。','提示');
											$.closeSubPop();
										}
									});
								}
							});
						}

						e.stopPropagation();
					});
				}
				else{
					$(this).css({'border-color':'#fff'}).next('.albumUl').hide();
				}
				e.stopPropagation();
			});

			
			$(this).html('退出管理');
		}
		else if ($(this).html() === '退出管理'){
			//初始化checkbox
			checkObjs.removeAttr('checked');
			allChecked.removeAttr('checked');
			$(this).css('background-image','url('+miscpath+'img/system/ui4.png)');

			//解除绑定,消除多次弹窗提示
			$('#delPhoto').unbind('click');

			toCheck.hide();
			manageObj.hide();
			$(this).html('批量管理');
		}
	})
	//@ End 相片列表批量管理

	//@Start 相片列表、有评论相片列表添加相册说明
	var objId = $('#album_id').val();
	if($('.pic_name').editText){
		$('.pic_name').editText({
			txt : '添加相册说明',
			djaxUrl: addDescUrl,
			objId : objId,
			maxNum : 140
		});
	}
	//@End 相片列表、有评论相片列表添加相册说明


	//相册列表
	if($('.album_list').size() == 1){
		$('.permissionTab').children('li').bind('click',function(){
			if($(this).attr('permissionType')){
				$(this).addClass('selected').siblings().removeClass('selected');
				var permissionType = $(this).attr('permissionType');
				var album_box = $('.album_list ul.album_list_ul');

				album_box.empty().append('<div id="waiting">正在加载，请稍后...</div>');
				scrollLoad(permissionType);
			}
		});

		var scrollLoad = function(param){
			$('.album_list').scrollLoad({
				text:'<p class="getMore">点击查看更多</p>',
				url:albumListAjaxUrl,
				data:{permissionType:param},
				success:function(json){
					var album_box = $('.album_list ul.album_list_ul');
					var is_author = $('#is_author').val();
					var moreAlbum_html = '';
					var indexArr = [];

					$.each(json.content, function (key, val){
						var albumNameTxt = $.trim(val['name']);
						if(albumNameTxt.length > 10){
							albumNameTxt = albumNameTxt.substr(0,10)+'...';
						}
						
						if(is_author == 1){
							var permission = '<div class="dropWrap dropMenu listPermission albumListPermission futureListPermission" oid="' + val["id"] + '" s="' + val["object_type"] + '" uid="' + val["object_content"] + '"></div>';
						} else {
							var permission = '';
						}
						moreAlbum_html += '<li class="album_mine">';
							moreAlbum_html += '<input type="hidden" value="' + val["id"] + '" />';
							moreAlbum_html += '<a href="javascript:;" url="' + val["photo_lists_url"] + '" class="photoLink">';
								moreAlbum_html += '<span>';
									moreAlbum_html += '<i></i>';
									moreAlbum_html += '<img src="' + val["album_cover"] + '" />';
								moreAlbum_html += '</span>';
							moreAlbum_html += '</a>';
							moreAlbum_html += '<div class="album_mineBox">';
								moreAlbum_html += '<div class="album_name">';
									moreAlbum_html += '<div><a href="' + val["photo_lists_url"] + '" title="' + val["name"] + '">' + albumNameTxt + '</a></div>';
									moreAlbum_html += '<div class="album_num">' + val["photo_count"] + ' 张照片</div>';
								moreAlbum_html += '</div>';
								moreAlbum_html += permission;
							moreAlbum_html += '</div>';
						moreAlbum_html += '</li>';

						if(val['a_type'] !== '0'){ //保存默认相册序列号
							indexArr.push(key);
						}

					});
					
					$('#waiting').remove();
					album_box.append(moreAlbum_html);

					if(album_box.html() == ''){
						album_box.addClass('noData').html('该分类下暂无相册数据');
					}
					else{
						album_box.removeClass('noData');
					}

					for(i=0; i<indexArr.length; i++){ //如果默认相册，权限不可更改
						$('div.futureListPermission').eq(indexArr[i]).css({'position':'relative'}).prepend('<div class="permissionMisk"></div>').children('div.permissionMisk').css({
							position:'absolute',
							top:'-1px',
							right:'-1px',
							width:'22px',
							height:'22px',
							'background-color':'#fff'
						}).fadeTo('fast',0.5);
					}


					//@Start: 再次绑定排序事件
					$(".album_move").find("li a.photoLink").hover(function(){$(this).find("i").show();},function(){$(this).find("i").hide();}); //控制"移动"图标
					$(".album_move").find(">ul>li").each(function(i){
						$(this).attr("nowIndex",i);
					});
					var albumClick = function(mover_obj){
						$(mover_obj).find("ul a.photoLink").each(function(i){
							$(this).click(function(){
								return false;
							});
							$(this).mousedown(function(event){
								if(event.which === 1 || event.whick === 0){ //左键操作，防止右键作用 1为火狐，0为ie
									nowmouseX=Number(event.screenX);
									nowmouseY=Number(event.screenY);
								}
							});
							$(this).mouseup(function(event){
								if(event.which === 1 || event.whick === 0){ //左键操作，防止右键作用 1为火狐，0为ie
									nowmouseX2=Number(event.screenX);
									nowmouseY2=Number(event.screenY);
									if(nowmouseX==nowmouseX2 && nowmouseY==nowmouseY2){
										window.location.href=$(this).attr("url");
									}
								}
							});
						});
					};
					
					albumClick($(".album_move"));
					//@End: 对未来元素再次绑定排序事件

					//@Start: 对未来元素再次绑定权限事件
					$('div.futureListPermission').dropdown({
						permission: {
							type: 'album',
							dataType: 'jsonp',
							url: mk_url('album/access/set'),
							im: true
						},
						position: 'right'
					});
					//@End: 对未来元素再次绑定权限事件

					$('div.albumListPermission').removeClass('futureListPermission');
				}
			});
		}

		//默认加载
		scrollLoad(1);
	}

	//相片列表
	if($('.album_picList').size() == 1){
		$('.album_picList').scrollLoad({
			text:'<p class="getMore">点击查看更多</p>',
			url:picListAjaxUrl,
			success:function(json){
				var photo_box = $('.album_picList ul');
				var morePhoto_html = '';

				$.each(json.content, function(key, val){
					var disType = '';
					
					var tempname = $.trim(val['name']);
					if(tempname.length > 10) {
						tempname = tempname.substr(0,20)+'...';
					}
					
					if(allChecked.is(':visible')){
						disType = 'style="display:block;"';
					}
					morePhoto_html += '<li>';
						morePhoto_html += '<input type="hidden" value="' + val['id'] + '" />';
						morePhoto_html += '<a class="photoLink picViewer picViewerOpenKey" href="javascript:;" url="' + val['photo_view_url'] + '">';
							morePhoto_html += '<i style="display:none;"></i>';
							morePhoto_html += '<img src="' + val['img_s'] + '" style="cursor: pointer;">';
						morePhoto_html += '</a>';
						morePhoto_html += '<div class="toCheck"><span class="checkBar" ' + disType + '><input type="checkbox" name="pic"></span> <span class="picName" title="' + val['name'] + '">' + tempname + '</span></div>';
					morePhoto_html += '</li>';
				});

				$('#waiting').remove();
				photo_box.append(morePhoto_html);

				toCheck = $('.album_picList .toCheck').find('span.checkBar');
				checkObjs = toCheck.find("input[type='checkbox']"); //统计当前复选框数
				if(allChecked.is(':checked')){
					checkObjs.each(function(){
						$(this).attr('checked','checked');
					});
				}

				$('.albumPic_move').find('li a.photoLink').hover(function(){$(this).find('i').show();},function(){$(this).find('i').hide();}); //控制"移动"图标
				$(".albumPic_move").find('>ul>li').each(function(i){
					$(this).attr('nowIndex',i);
				});

				
				picViewerFunc();
			}
		});
	}


	//评论照片
	if($('div.album_graphicList').size() == 1){
		$('div.album_graphicList').scrollLoad({
			text:'<p class="getMore">点击查看更多</p>',
			url:commentPicListAjaxUrl,
			success:function(json){
				var photoInfo_box = $('.album_graphicList');
				var morePhotoInfo_html = '';

				$.each(json.content, function(key, val){
					morePhotoInfo_html += '<div class="album_graphicList_list clearfix">';
						morePhotoInfo_html += '<div class="album_graphicList_img"><a href="javascript:;" url="' + val['photo_view_url'] + '" class="picViewer photoLink"><img src="' + val['img_s'] + '" alt="" /></a></div>';
							morePhotoInfo_html += '<div class="album_graphicList_text">';
							morePhotoInfo_html += '<div class="comment_easy graphicListCommentEasy" commentObjId="' + val['id'] + '" pageType="photo" action_uid="' + val['action_uid'] + '"></div>';
						morePhotoInfo_html += '</div>';
					morePhotoInfo_html += '</div>';
							
				});

				$('#waiting').remove();
				photoInfo_box.append(morePhotoInfo_html);
				
				if($(".graphicListCommentEasy, .staticCommentEasy").commentEasy){
					com=$(".graphicListCommentEasy, .staticCommentEasy").commentEasy(commentOptions);
				}

				$('.comment_easy').removeClass('graphicListCommentEasy');
			}
		});
	}


	//@ Start 创建iframe函数
	var picView_iframe = function(picView_iframeURL){
		var picView_width = parseInt($(window).width())+15; //@ 获取浏览器宽度
		var picView_height = parseInt($(window).height()); //@ 获取浏览器高度
		var picView_heightBG = parseInt($(document).height()); //@ 获取文档高度
		var offsetH = parseInt($(window).scrollTop()); //@ 滚动条距离顶部高度
		var waitTipLeft = (picView_width-15-89)/2; //@ 等待图标left位置
		var waitTipTop = (picView_height-90)/2 + offsetH; //@ 等待图标top位置

		$('body').attr('style','position:relative; overflow:hidden;').prepend('<div class="closePicViewerBtn" title="按ESC键关闭"></div>').children('.closePicViewerBtn').css({
		  	'position':'fixed',
		  	'top':0,
		  	'right':0,
		  	'height':'47px',
		  	'width':'47px',
		  	'background':'url('+miscpath+'img/system/photo_scan_layer.png) no-repeat left top',
		  	'cursor':'pointer',
		  	'z-index':'1002'
		});
		if($.browser.msie && ($.browser.version==6.0 || $.browser.version==7.0)){
			$('body').attr('style','*overflow-y:auto;');
			picView_width += 2;
		}
		$('html').attr('style','*overflow:hidden');        //隐藏滚动条(IE6)

		$('body').prepend('<div id="picView_bg"></div>');
		$('#picView_bg').css({ //设置遮罩层
			'position':'absolute',
			'top':'0px',
			'left':'0px',
			'z-index':'1000',
			'background':'#000 url('+miscpath+'img/system/waitTip.gif) no-repeat '+waitTipLeft+'px'+' '+waitTipTop+'px' 
		});
		$('#picView_bg').width(picView_width).height(picView_heightBG).fadeTo(0,0.8);
		
		//创建iframe容器
		$('body').prepend('<div id="picView_box"></div>');
		$('#picView_box').css({
			'position':'fixed',
			'top':'0px',
			'left':'0px',
			'z-index':'1001'
		});
		if($.browser.msie && $.browser.version==6.0){
			var scrollTop = $(window).scrollTop();
			$('#picView_box').css({'position':'absolute','top':scrollTop+'px'});
			$('.closePicViewerBtn').css({'position':'absolute','top':scrollTop+'px'});
		}
		$('#picView_box').width(picView_width);
		$('#picView_box').height(picView_height);
		$('#picView_box').append('<iframe width="100%" height="100%" allowtransparency="true" id="picView_iframe"></iframe>');

		$('#picView_iframe').attr('src',picView_iframeURL).load(function(){
			$('#picView_bg').css({'background-image':'none'});
		});

		$(window).resize(function(){
			$('#picView_box').css({width:$(window).width(),height:$(window).height()});
			$('#picView_bg').width($(document).width()).height($(document).height()); //遮罩层大小自适应文档大小
		});
	};

	var picViewerFunc = function(){
		var picView_selector = $('.picViewerOpenKey');  //@ 使用图片查看器的类名 //为避免重复调用此查看器，改查看器开关.picViewer为.picViewerOpenKey

		//@ Start 点击事件函数
		var picView_Click = function(mover_obj){
			mover_obj.each(function(){
				$(this).unbind('click').bind('click',function(){
					return false;
				});
				$(this).unbind('mousedown').bind('mousedown',function(event){
					if(event.which === 1 || event.whick === 0){ //左键操作，防止右键作用 1为火狐，0为ie
						nowmouseX=Number(event.screenX);
						nowmouseY=Number(event.screenY);
					}
				});
				$(this).unbind('mouseup').bind('mouseup',function(event){
					if(event.which === 1 || event.whick === 0){ //左键操作，防止右键作用 1为火狐，0为ie
						nowmouseX2=Number(event.screenX);
						nowmouseY2=Number(event.screenY);
						if(nowmouseX==nowmouseX2 && nowmouseY==nowmouseY2){
							picView_iframe($(this).attr('url'));
						}
					}
				});
			});
		};
		picView_Click(picView_selector);
		//@ End 点击事件函数
	}

	//运行查看器
	picViewerFunc();

	//@ End 创建iframe函数

	//直接展开查看器
	var url = window.location.href.replace(/%2526/g,'&').replace(/%253A/g,':').replace(/%252F/g,'/').replace(/%253F/g,'?').replace(/%253D/g,'=');
	var pattern = /\S+&iscomment=(\d+)&jumpurl=(\S+)$/i;
	
	if(pattern.test(url)){
		var arr = pattern.exec(url);
		if(arr[1] == 1){
		 	picView_iframe(arr[2]);
		}
	}
	
	var closeIframe = function(){
		var url = unescape(window.location.href);
		var pattern = /^(\S+)&iscomment=(\d+)&jumpurl=(\S+)$/i;
		if(pattern.test(url)){
			var arr = pattern.exec(url);
			if(arr[2] == 1){
				window.location.href = arr[1];
			}
		}
		$('body').removeAttr('style').children('#picView_bg').remove().end().children('#picView_box').remove().end().children('.closePicViewerBtn').remove();
		$('html').removeAttr('style'); //兼容ie6、ie7
	};

	$('body').delegate('.closePicViewerBtn','click',function(){
		closeIframe();
	}).delegate('.closePicViewerBtn','mouseover',function(){
		$(this).css({'background-position':'-48px top'});
	}).delegate('.closePicViewerBtn','mouseout',function(){
		$(this).css({'background-position':'left top'});
	});

	$(document).bind('keydown',function(e){
		if(e.keyCode === 27){
			closeIframe();
		}
	});


	//关闭或刷新窗口时提示用户
	window.onbeforeunload = function(){
		var picIds = '';

		$('#popUp').find('.itemsArea').children('.item').each(function(){
			var picId = $(this).children('.img').attr('picId');
			
			if(picId != undefined && picId != ''){
				picIds += picId + ',';
			}
		});

		if(picIds !== '' && !submitFlag){
			return '未完成上传，此时关闭将丢失正在上传的所有图片，确定要离开吗？';
		}
	}

});