/*
 * Created on 2011-09-13
 * @author: Yewang
 * @desc: 日志发布修改
 * @depends: jquery.wysiwyg.js
 */

var blog = {
	init: function() {
		if($.fn.dkEditor) {
			this.blogEdit.init();
		} else {
			this.blogList.init();
		}
	},
	blogList: {
		init: function() {
			//选项卡
			$('.blogList-Title ul li').click(function(){
				var preListType = $('.blogList-Title ul li.hover').attr('rel');
				$(this).addClass('hover').siblings().removeClass('hover');
				var nextListType = $(this).attr('rel');
				if( preListType != nextListType ){
					$.ajax({
						url : mk_url("blog/blog/index"),
						type : 'post',
						dataType : 'json',
						data : { rel : nextListType},
						success : function(result){
							if(result.state){
								var tmp = $('#blogList');
								tmp.html(result.list);
								if(tmp.find('div.comment_easy')[0]) {
									$('div.comment_easy',tmp).commentEasy({
										otherButtons:'<li><strong> · </strong></li><li><a class="viewFull">查看全文</a></li>',
										minNum:3,
										buttonsPrev:true,
										UID:CONFIG['u_id'],
										userName:CONFIG['u_name'],
										avatar:CONFIG['u_head'],
										userPageUrl:$("#hd_userPageUrl").val(),
										relayCallback:function (obj,_arg) {
								            var comment=new ui.Comment();
								            comment.share(obj,_arg);
								        }
									});
								}
								$('.blogBody .loadmore').attr('type',nextListType);
								if(result.count){
									$('.blogBody .loadmore').show();
									$('#moreBlog').removeClass('hide');
								}else{
									$('.blogBody .loadmore').hide();
								}
							}else{
								$.alert('提示框',result.msg);
							}
						}
					});
				}
			});
			//查看更多点击获取
			$('#moreBlog').click(function() {
				var $this = $(this),
					url = $this.attr('href');
					num = Number($this.attr('pager'));
				var rel = $(this).parent().attr('type');
				$(this).addClass('hide').next().removeClass('hide');
				$.djax({
					url: url,
					type: 'POST',
					data: {pager: num,rel:rel},
					dataType: 'json',
					success: function(data) {
						if(data.state === 1) {
							var tmp = $(data.list);
							if(tmp.find('div.comment_easy')[0]) {
								$('div.comment_easy',tmp).commentEasy({
									otherButtons:'<li><strong> · </strong></li><li><a class="viewFull">查看全文</a></li>',
									minNum:3,
									buttonsPrev:true,
									UID:CONFIG['u_id'],
									userName:CONFIG['u_name'],
									avatar:CONFIG['u_head'],
									userPageUrl:$("#hd_userPageUrl").val(),
									relayCallback:function (obj,_arg) {
							            var comment=new ui.Comment();
							            comment.share(obj,_arg);
							        }
								});
							}
							$this.parents('.blogBody').find('#blogList').append(tmp);
							if(data.last === true) {
								$this.parent().hide();
							} else {
								num++;
								$this.attr('pager',num).removeClass('hide').next().addClass('hide');
							}
						} else {
							$.alert(data.msg);
						}
					}
				});
				return false;
			});
		}
	},
	blogEdit: {
		init: function() {
			this.initTEXTAREA();
			this.initEvent();
		},
		cache: {
			img_li: $('<li><h4></h4><div class="editPhoto"><img src="" alt="" /><a class="c"></a></div></li>'),
			textarea: $('#editor'),
			albumList: $('#albumList')
		},
		//编辑器初始化
		initTEXTAREA: function() {
			var textarea = this.cache.textarea;
			/*textarea.wysiwyg({
				autoGrow: false, 
				iFrameClass: 'editorIframe',
				initialContent: textarea.val(),
				controls: "bold,italic,underline,|,insertUnorderedList,insertOrderedList",
				css: CONFIG['misc_path'] + 'css/plug-css/wysiwyg/editor.css'
			});*/
			$('.textareaCont').dkEditor({
				btnId : ['Bold','Italic','Underline','Insertorderedlist','Insertunorderedlist'],
				firstCon : textarea.val()
			});
		},
		initEvent: function() {
			var textarea = this.cache.textarea;
			
			//加一张照片。按钮 
			$('#addPhoto').click(function(){
				$(this).hide().next().show();
			});
			
			//即时删除照片
			var photosList = $('#photosUl');
			photosList.delegate('a.c','click',function(){
				
				if($(this).hasClass('getting')){
					return false;
				}
				$(this).addClass('getting');
				var _this = $(this);
				
				//点击即时删除blog照片
				$.djax({
					type:"POST",
					/*url:webpath+"blog/blog/delPhoto",*/
					url:mk_url("blog/blog/delPhoto"),
					data:{photo_id:this.id},
					dataType:'json',
					success:function(data){
						if(data.state == 1 && data.result == true){					
							_this.parent().parent().remove();
							textarea.val(textarea.val().replace('<p>'+_this.parent().prev().text()+'</p>',''));
							var html = document.getElementsByTagName('iframe')[0].contentWindow.document.body.innerHTML;
							document.getElementsByTagName('iframe')[0].contentWindow.document.body.innerHTML = html.replace('<p>'+_this.parent().prev().text()+'</p>','');
						}else{
							$.alert(data.msg);
						}
						
					}
				
				});
			});
			
			
			var albumList = $('#albumList');
	
			//点击获取相册详细照片
			albumList.find('span.photoThumb').click(function(){
				if($(this).hasClass('current')){
					albumList.hide().next().show();
					return false;
				}
				if($(this).hasClass('loadingImg')){
					return false;
				}
				$(this).addClass('loadingImg');
				var _this = $(this);
				$.djax({
					type:'POST',
					/*url:webpath+'blog/blog/getPhotos?id='+this.id,*/
					url:mk_url("blog/blog/getPhotos",{id:this.id}),
					dataType:'json',
					data:{id:this.id},
					success:function(data){
						if(data.state == 1 && data.result == true){
							albumList.hide().next().show().find('tbody').empty().append(data.text);
							albumList.find('span.photoThumb').removeClass('current');
							_this.addClass('current');
						}else{
							$.alert(data.msg);
						}
						_this.removeClass('loadingImg');
					}
				});
			}).hover(function(){
				$(this).addClass('photoFocus');
			},function(){
				$(this).removeClass('photoFocus');
			});
			
			//点击返回相册列表
			albumList.next().find('>a').click(function(){
				albumList.show().next().hide();
			});
			
			var albumDetail = $('.albumDetail');
			//点击详细照片插入到blog中
			albumDetail.find('table').delegate('a','click',function(){
				if($(this).hasClass('getting'))return false;
				$(this).addClass('getting');
				
				var _this = this;
				$.djax({
					type:'POST',
					/*url:webpath+'blog/blog/doPhoto',*/
					url:mk_url("blog/blog/doPhoto"),
					data:{id:_this.id,from:2,type:$('input[name=blog_type]').val(),
						bid:$('input[name=bid]').val(),
						did:$('input[name=did]').val()
						},
					dataType:'json',
					success:function(data){
						if(data.state == 1){
							if(data.result == true){
								var temp_obj = {
									imgSrc:data.url,
									imgTitle:data.title,
									imgList:$('#photosUl'),
									imgId:data.id
								};
								insertImg(temp_obj);
								$(_this).removeClass('getting');
							}else{
								$.alert(data.type);
								$(_this).removeClass('getting');
							}
						}
						
					}
				});
				
			});
			
			//privacy setting
			$('#privacySetting').dropdown({
				permission:{
					type: 'blog',
					dataType : 'jsonp',
					access_content:''
				}
			});
			
			//删除草稿
			$('a[name="draft_del"]').live('click',function(){
				var id = this.id,
					$this = $(this);
				$.confirm('确认删除', '确认删除这篇草稿吗？', function(){
					$.djax({
						type: 'POST',
						/*url: webpath + 'blog/blog/draftDel',*/
						url: mk_url('blog/blog/draftDel'),
						data: {bid:id},
						dataType: 'JSON',
						success: function(data) {
							if(data.status == 1) {
								$this.parent().parent().remove();
							} else {
								$.alert(data.msg);
							}
							
						}
					});
				});
				
			});
		}
	}
};
$(function() {
	blog.init();
});


//外部直接调用的一些functions

 //插入图片到blog中的方法
function insertImg(obj){
	var temp = blog.blogEdit.cache.img_li.clone();
	temp.find('h4').text('{img_'+obj.imgTitle+'}').next().find('img').attr('src',obj.imgSrc).next().attr('id',obj.imgId); 
	obj.imgList.append(temp);
	$('#editor').val($('#editor').val()+'<p>{img_'+obj.imgTitle+'}</p>');
	document.getElementsByTagName('iframe')[0].contentWindow.document.body.innerHTML += '<p>{img_'+obj.imgTitle+'}</p>';
}
 
 //上传照片判断
 function judge_upload(obj){
	$('#uploadError').hide();
	if(obj.result == false){
		var errorTxt = '';
		switch(obj.type){
			case '1':
				errorTxt = '图片文件有问题';
				break;
			case '2':
				errorTxt = '服务器繁忙';
			default:
				errorTxt = '未知错误';
		}
		$('#uploadError').text(errorTxt).show();
		return false;
	}
	
	var temp_boj = {
		imgSrc:obj.url_s,
		imgTitle:obj.title,
		imgList:$('#photosUl'),
		imgId:obj.id
	};
	
	insertImg(temp_boj);
 }
 
 // delete blog
 function delete_blog(form_id,url,darft){
	var content = darft?'确定要取消发布吗？':'确定要删除这篇日志吗？';
	$(this).popUp({
		width:550,
		title:'必须确认',
		content:'<p class="deltext">'+content+'此操作无法撤销。</p>',
		mask:true,
		maskMode:false,
		buttons:'<span class="popBtns blueBtn callbackBtn">确认</span><span class="popBtns closeBtn">取消</span>',
		callback:function(){
			var the_form = $('#'+form_id);
			the_form.attr('action',url);
			the_form.submit();
		}
	});
	
	
 }
 
//上传图片模版
var draft = '<h4 class="draftListTitle">草稿箱已满，请删除部分后继续操作。</h4><div class="draftList"><table><tbody>',
	flag = false;
 
 //submit form 
 function submit_form(self,form_id,url,name){
	var _this = $(self);
	var the_form = $('#'+form_id),
		editorVal = $('#editor').val().replace(/&nbsp;/g, '') + '';
		editorVal = editorVal.replace(/<br>|<BR>/g, '');
	if(name == 'draft') {
		if($.trim($('#blogTitle').val()) == '' || $.trim(editorVal) == '') {
			$('#titleError').show();
			return false;
		}
		$.djax({
			type: 'GET',
			/*url: webpath + 'blog/blog/isDraftFull?id=' + $('input[name=bid]').val(),*/
			url: mk_url('blog/blog/isDraftFull',{id:$('input[name=bid]').val()}),
			dataType: 'JSON',
			success: function(data) {
				if(data.result == true) {
					if(data.status == 1) {
						if(flag == false) {
							draft += data.text + '</tbody></table></div>';
							flag = true;
						}
						$(this).popUp({
							width:550,
							title:'草稿列表',
							content:draft,
							mask:true,
							maskMode:false,
							buttons:'<span class="popBtns callbackBtn">关闭</span>',
							callback: function() {
								if($('div.draftList').find('tr').length < 1) {
									flag = false;
								}
								$.closePopUp();
							}
						});
					} else {
						the_form.attr('action',url);
						the_form.submit();
					}
				} else {
					$.alert(data.msg);
				}
				_this.removeClass('submiting');
			}
		});
		return false;
	} else if(name=='publish' || name=='preview') {
		if($.trim($('#blogTitle').val()) == '' || $.trim(editorVal) == '') {
			$('#titleError').html('<b>发布失败</b>请填写标题和内容。这两项是发布文章所需的项目。');
			$('#titleError').show();
			return false;
		}else if($.trim($('#editor').val()).length >= 65535){
			$('#titleError').html('<b>发布失败</b>内容长度超出。');
			$('#titleError').show();
			return false;
		}
		//防止连续多次点击‘发布’按扭
		if(_this.hasClass('submiting')){ 
			return false;
		}
		_this.addClass('submiting');
		the_form.attr('action',url);
		the_form.submit();
	}
 }