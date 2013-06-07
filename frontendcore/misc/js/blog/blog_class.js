/*
 * Created on 2012-06-26
 * @author: zhupinglei
 * @desc: 日志分类管理
 * @depends: jquery 1.7.js
 */
 function blogClass(){
	this.init();
 }
 blogClass.prototype = {
	init : function(){
		var _this = this;
		var	admin = '<a href="javascript:void(0);" class="blogClassEdit">编辑</a> <a href="javascript:void(0);" class="blogClassDel">删除</a>',
			changeStr_admin = '<a href="javascript:void(0);" class="blogClassChange">确定</a> <a href="javascript:void(0);" class="blogClassCancel">取消</a>';	//操作替代值
		//点击编辑按扭
		$('.blogClassBody tbody').on('click','a',function(){
			var here = this;
			var tid = $(this).parents('tr').attr('tid'),	//获取点击tr的tid值
				$tidTr = $('.blogClassBody').find('tr[tid=' + tid + ']');	//获取点击的tr
			var className = $tidTr.find('td').eq(0).text();	//获取类名
			var changeStr_txt = '<input type="text" maxlength="6" value="' + className + '" />';	//类名替代值
			var eventName = $(this).attr('class');
			switch(eventName){
				case 'blogClassEdit':	//编辑
					$tidTr.find('td').eq(0).html(changeStr_txt);
					$tidTr.find('td').eq(2).html(changeStr_admin);
					break;
				case 'blogClassDel':	//删除
					$.confirm('删除','删除后将不可恢复，是否删除?',function(){
						$.ajax({
							/*url : webpath+'delCategory',*/
							url : mk_url('blog/blog/delCategory'),
							type : 'post',
							dataType : 'json',
							data : {
								tid : tid
							},
							success : function(result){
								if(result.state){
									$(here).parents('tr').remove();
								}else{
									$.alert(result.message,'错误');
								}
							}
						});
					});
					break;
				case 'blogClassChange':	//确定修改
					var newClassName = $.trim($tidTr.find('td').eq(0).find('input').val());
					if(!newClassName){
						$.alert('分类名不能为空','错误');
					}else{
						$.ajax({
							/*url : webpath+'updateCategory',*/
							url : mk_url('blog/blog/updateCategory'),
							type : 'post',
							dataType : 'json',
							data : {
								tid : tid,
								newClassName : newClassName
							},
							success : function(result){ 
								if(result.state){
									$tidTr.find('td').eq(0).html(result.categoryName);
									$tidTr.find('td').eq(2).html(admin);
								}else{
									$.alert(result.error,'错误');
								}
							}
						}); 
					}
					break;
				case 'blogClassCancel':	//取消修改
					$.ajax({
						/*url : webpath+'cancelCategory',*/
						url : mk_url('blog/blog/cancelCategory'),
						type : 'post',
						dataType : 'json',
						data : {
							tid : tid,
						},
						success : function(result){ 
							if(result.state){
								$tidTr.find('td').eq(0).html(result.categoryName);
								$tidTr.find('td').eq(2).html(admin);
							}else{
								$.alert(result.error,'错误');
							}
						}
					});
					break;
				default:
					break;
			}
		});
		//添加新分类
		$('.blogClassBody tfoot a').click(function(){
			var newClass = $.trim($('#newBlogClass').val());
			if(!newClass){
				$.alert('分类名不能为空','错误');
			}else{
				$.ajax({
					/*url : webpath+'addCategory',*/
					url : mk_url('blog/blog/addCategory'),
					type : 'post',
					dataType : 'json',
					data : {
						newClass : newClass
					},
					success : function(result){
						if(result.state){
							var data = result.data;
							var newstr = '<tr tid="'+data.tid+'"><td>'+data.newClass+'</td><td>'+data.blogNum+'</td><td>'+admin+'</td></tr>';
							$('.blogClassBody tbody').append(newstr);
						}else{
							$.alert(result.error,'错误');
						}
					}
				});
			}
		});
	}
 }
 $(function(){
	new blogClass();
 });