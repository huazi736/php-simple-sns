/**
 * @author:    duxianwei
 * @created:   2012/05/31
 * @version:   v1.0
 * @desc:      群组模块
 */

$(function(){
	//首次进入页面即请求加载中间的内容
	var $group_index_c = $(".group_index_c");
	var $group_id = $("#group_id").val();
	var $subgroup_id = $("#subgroup_id").val();
	var $group_forum = $(".group_forum");
	var getMsg = function(url){
		var data = {gid:$group_id};
		$.djax({
			url:url,
			dataType:"jsonp",
			async:true,
			data:data,
			success:function(data) {
				$group_index_c.html(data.data);
			},
			error:function() {
				$.alert("对不起，加载数据失败，请刷新页面重试!")
			}
		});
	}
	getMsg(mk_url("group/info/center"));

	//点击左侧导航条对中间的内容进行相应的替换
	var getCenterInfo = function(obj,url){
		obj.on({
			"click":function(){
				var data={gid:$("#group_id").val()}
				var timetamp = new Date().getTime();
				url += '?v=' + timetamp;
				$.djax({
					type:'get',
					url:url,
					data:data,
					dataType: "jsonp",
					async:false,
					success:function(data) {
						$group_index_c.html(data.data);
					},
					error:function() {
						$.alert("对不起，加载数据失败，请刷新页面重试!")
					}
				});
			}
		});
	}

	//左侧导航通用获取应用目标元素以及对应的请求地址
	var groupLeftChange = function(obj){
		obj.each(function(){
			var self = $(this)
			var postUrl = self.attr("postUrl");
			getCenterInfo(self,postUrl);	
		});	
	}
	groupLeftChange($(".group_forum"));

	//解散普通群组或者子群
	var $dissolve_group = $("#dissolve_group");
	var $dissolve_group_son = $("#dissolve_group_son");
	var dissolve_group_function = function(obj,val,url,turnUrl){
		obj.on({
			"click":function(){
				$.confirm("提示框","是否确定解散此群，此操作不可还原！",function(){
					var data = {gid:val};
					$.djax({
						url:url,
						dataType:"json",
						data:data,
						async:true,
						success:function(data) {
							if(data.status == 1){
								window.location.href = turnUrl || mk_url('main/index/main');
							}
							else{
								$.alert("对不起，解散群组失败，请刷新页面重试!");
							}
						},
						error:function() {
							$.alert("对不起，解散群组失败，请刷新页面重试!");
						}
					});
				});	
			}
		});
	}
	dissolve_group_function($dissolve_group,$dissolve_group.attr("gid"),mk_url("group/manage/disband"));
	dissolve_group_function($dissolve_group_son,$dissolve_group_son.attr("gid"),mk_url("group/subgroup/disband"),mk_url('group/index/detail',{gid:$("#group_id").val()}));

	//退出群组以及子群
	var $group_quit = $("#group_quit");
	var $group_son_quit = $("#group_son_quit");
	var dissolve_group_function = function(obj,url,id,turnUrl){
		obj.on({
			"click":function(){
				$.confirm("提示框","是否确定退出群组，此操作不可还原！",function(){
					var data = {gid:id};
					$.djax({
						url:url,
						dataType:"json",
						data:data,
						async:true,
						success:function(data) {
							if(data.status == 1){
								window.location.href = turnUrl || mk_url('main/index/main');
							}
							else{
								$.alert("对不起，退出群组失败，请刷新页面重试!");
							}
						},
						error:function() {
							$.alert("对不起，退出群组失败，请刷新页面重试!");
						}
					});
				});	
			}
		});
	}
	dissolve_group_function($group_quit,mk_url("group/index/quit"),$("#group_id").val());
	dissolve_group_function($group_son_quit,mk_url("group/subgroup/quit"),$("#subgroup_id").val(),mk_url('group/index/detail',{gid:$("#group_id").val()}));

	//开启群聊
	var $group_chat_open = $(".group_chat_open");
	var groupChatOpen = function(obj,groupId,postUrl){
		obj.live({
			"click":function(){
				if ($group_chat_open.attr("status") == "off") {
					var data = {gid:groupId};
					$.djax({
						url:postUrl,
						dataType:"json",
						data:data,
						async:true,
						success:function(data) {
							if(data.status == 1){
								obj.removeAttr("status").html("实时聊天");
								showGroupChat(groupId);
							}
							else{
								$.alert(data.error);
							}
						},
						error:function() {
							$.alert("对不起，开启群聊失败，请刷新页面重试!");
						}
					});
				}
				else{
					showGroupChat(groupId);
				}
			}
		});	
			
	}
	groupChatOpen($group_chat_open,$group_id,mk_url("group/chat/create/"));//群开启聊天

	//子群开启聊天
	var $group_chat = $(".group_chat");
	var subGroupChatOpen = function(obj,groupId){
		obj.on({
			"click":function(){
				showGroupChat(groupId);
			}
		});
	}
	subGroupChatOpen($group_chat,$subgroup_id);//子群开启聊天

	//点击页面其他区域关闭下拉菜单
	$(document).click(function(){
		$(".group_manage_select i").removeClass("down");
		$(".group_manage_select ul").hide();
	});

	//模拟下拉菜单
	var divSelect = function(obj){
		obj.live({
			'click':function(e){
				e.stopPropagation();
				var self = $(this);
				if(self.find(".group_manage_select_title i").hasClass("down")){
					self.find("ul").hide().end().find(".group_manage_select_title a i").removeClass("down");
				}
				else{
					self.find("ul").show().end().find(".group_manage_select_title a i").addClass("down");
				}
			}
		})
	};
	//调用detail.js模拟下拉框
	divSelect($(".group_manage_select"));
	
});


	