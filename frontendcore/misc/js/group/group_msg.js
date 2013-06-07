/**
 * @author:    duxianwei
 * @created:   2012/05/31
 * @version:   v1.0
 * @desc:      群组模块
 */

$(function(){
	//点击接受执行方法
	var $accept = $(".accept").parent();
	var accept = function(obj,postUrl){
		obj.on({
			'click':function(){
				var self =$(this);
				var gidVal = self.parents('.group_info_li_right').attr("gid");
				var idVal = self.parents(".group_info_li_right").attr("id");
				var data={gid:gidVal,id:idVal};
				$.djax({
					url:postUrl,
					dataType:"json",
					async:true,
					data:data,
					success:function(data) {
						if (data.status == 1) {
							var text = '<a href="' + mk_url('group/index/detail',{gid:gidVal}) + '">进入该群组</a>';
							self.parents(".group_info_li_right").empty().append(text);
						}
						else{
							var li = self.parents(".group_info_li");
							self.parents(".group_info_li_right").empty().append(data.error);
							setTimeout(function(){
								li.hide("600").queue(function(){
									li.remove();
								});
							},1000);
							
						}
					},
					error:function(){
						$.alert('对不起网络错误，请刷新页面重试');
					}
				});
			}
		})
	}

	accept($accept,mk_url('group/group/inviteAccept'));//调用接受执行函数

	//点击取消进行的动作
	var $refuse = $(".refuse").parent();
	var refuse =function(obj,postUrl){
		obj.on({
			'click':function(){
				var self =$(this);
				var gidVal = self.parents(".group_info_li_right").attr("gid");
				var idVal = self.parents(".group_info_li_right").attr("id");
				var data={gid:gidVal,id:idVal};
				$.djax({
					url:postUrl,
					dataType:"json",
					async:true,
					data:data,
					success:function(data) {
						if (data.status == 1) {
							var li = self.parents(".group_info_li");
							self.parents(".group_info_li_right").empty().text('您已经拒绝该请求');
							setTimeout(function(){
								li.hide("600").queue(function(){
									li.remove();
								});
							},1000);
						}
						else{
							var li = self.parents(".group_info_li");
							self.parents(".group_info_li_right").empty().text(data.error);
							setTimeout(function(){
								li.hide("600").queue(function(){
									li.remove();
								});
							},1000);
						}
					},
					error:function(){
						$.alert('对不起网络错误，请刷新页面重试');
					}
				});
			}
		})
	}
	refuse($refuse,mk_url('group/group/inviteRefuse'));//点击取消执行方法

	//点击更多加载
	var $more = $(".more a");
	var clickMore = function(obj){
		obj.on({
			'click':function(){
				var lastid = $(".group_info_li_right").last().attr("id");
				$.djax({
					url:mk_url('group/group/confirmPage',{lastid:lastid}),
					dataType:"json",
					async:true,
					data:'',
					success:function(data) {
						var	userData = data.data.html;
						lastid = $(".group_info_li_right").last().attr("id");
						$(".group_info_ul .more").before(userData);
						if (data.data.last == true) {
							$more.hide();
						}
					}
				});
			}
		});

		accept($accept,mk_url('group/group/inviteAccept'));//调用接受执行函数
		refuse($refuse,mk_url('group/group/inviteRefuse'));//点击取消执行方法
	}

	clickMore($more);//调用滚动事件
});


	