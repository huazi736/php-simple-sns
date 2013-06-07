/*
 * Created on 2011-09-29
 * @author： 李海棠
 * @desc：日志评论
 */
 
$(function(){

/*
 * Update on 2012-01-12
 * @author： yewang
 * @desc：添加Blog评论 commentEasy
 */

	if($('div.comment_easy')[0]) {
		var commentObj = {
			otherButtons:'<li><a class="viewFull">查看全文</a></li>',
			minNum:3,
			UID:CONFIG['u_id'],
			userName:CONFIG['u_name'],
			avatar:CONFIG['u_head'],
			userPageUrl:$("#hd_userPageUrl").val(),
            relayCallback:function (obj,_arg){
	            var comment=new ui.Comment();
	            comment.share(obj,_arg);
	        }
		};
		if($('#blogList')[0]) {
			var opts = $.extend(commentObj,{buttonsPrev:true});
			$('#blogList').find('div.comment_easy').commentEasy(opts);
		} else {
//			var comment = $('div.comment_easy');
//			if($('#blogListDetail_self')[0]) {
//				/* var opts = $.extend(commentObj,{otherButtons:'<li><strong> · </strong></li><li><a class="delBlog">删除</a></li>'}); */
//			} else {
//				var a = '<a class="forwardArt">转发</a>';
//				if(comment.attr('forward') == 'true') {
//					a = '<a class="forwarded">已转发</a>';
//				}
//				var opts = $.extend(commentObj,{otherButtons:'<li><strong> · </strong></li><li>'+ a +'</li>'});
//			}
			var opts = $.extend(commentObj,{otherButtons:''});
			$('div.comment_easy').commentEasy(opts);
		}
	}
	
/*
 * Update on 2011-10-25
 * @author： yewang
 * @version： 1.0.01
 * @desc：添加删除Blog的方法
 */

	$('#hasDel').delegate('a.delBlog','click', function(){
		var id = '';
		if($(this).attr('name') == 'draft') {
			id = $(this).attr('href');
		} else {
			id = $('div.comment_easy').attr('commentobjid');
		}
		$(this).popUp({
			width:550,
			title:'必须确认',
			content:'<p class="deltext">确定要删除这篇日志吗？此操作无法撤销。</p>',
			mask:true,
			maskMode:false,
			buttons:'<span class="popBtns blueBtn callbackBtn">确认</span><span class="popBtns closeBtn">取消</span>',
			callback:function(){
				$('#delBlog_id').val(id);
				$('#delBlog').submit();
			}
		});
		return false;
	});
	
/*
 * Update on 2012-01-17
 * @author： yewang
 * @desc：添加跳转方法
 */

	$('#blogList').delegate('a.viewFull','click', function(){
		var href = $(this).parents('li.blogList').find('h4').find('a').attr('href');
		this.href = href;
	});
	
	
/*
 * Update on 2011-11-08
 * @author： yewang
 * @version： 1.0.01
 * @desc：添加转载方法
 */

	$('div.comment_easy').delegate('a.forwardArt','click', function() {
		if(!$(this).hasClass('forwarded')){
			var _this = $(this),
				id = _this.parents('div[commentobjid]').attr('commentobjid');
				action_dkcode = _this.parents('div[commentobjid]').attr('dkcode');
			$.confirm('转发提示', '<p class="deltext">确认转发此篇文章吗?</p>', function(){
				$.djax({
					type:"GET",
					/*url:webpath+'blog/blog/forward?id='+id+'&action_dkcode='+action_dkcode,*/
					url:mk_url('blog/blog/forward',{id:id,dkcode:action_dkcode}),
					dataType:'json',
					success:function(data){
						if(data.result == true){
							_this.unbind('click').text('已转发');
							$.alert('<p class="deltext">转发成功</p>', '转发提示');
						}else{
							alert(data.msg);
						}
					}
				});
			});
		}
	});

/*
 * Update on 2012-02-01
 * @author： yewang
 * @version： 1.0.01
 * @desc：添加分页方法
 */
	
	/* var pager = 2;
	$('#moreBlog').click(function() {
		var $this = $(this),
			path = this.getAttribute('href',2);
		$this.hide().next().removeClass('hid');
		
		$.djax({
			url: webpath + path,
			type: 'POST',
			data: {pager: pager},
			success: function(data) {
				console.log(data);
				if(data.status === 1) {
					var temp = $(data.content);
					$('#blogList').append(temp);
					console.log($('div.comment_easy').size())
					$('div.comment_easy').commentEasy({
						otherButtons:'<li><strong> · </strong></li><li><a class="viewFull">查看全文</a></li>',
						minNum:3,
						buttonsPrev:true,
						UID:$("#hd_UID").val(),
						userName:$("#hd_userName").val(),
						avatar:$("#hd_avatar").val(),
						userPageUrl:$("#hd_userPageUrl").val()
					});
					if(data.s === 0) {
						$this.parent().remove();
					} else {
						$this.show().next().addClass('hid');
						pager++;
					}
				} else {
					alert(data.msg);
				}
			}
		});
		return false;
	});
 */
});