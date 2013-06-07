/**
 * @author:    duxianwei
 * @created:   2012/05/31
 * @version:   v1.0
 * @desc:      群组模块
 */

$(function(){
	var $group_app_list_add = $(".group_app_list_add");
	var $group_set_li = $(".group_set").find("li");
	var $group_tab_contant = $(".group_tab_contant");
	var $group_set_logo_ul_li = $(".group_set_logo_ul").find("li");
	var $group_kickout_uid = $(".group_kickout").attr("id");

	//群头像的切换
	var logoSelect = function(arg){
		arg.on("click",function(){
			$(this).siblings().removeClass().end().addClass("on");
		});
	}
	logoSelect($group_set_logo_ul_li);

	//删除群成员或者子群成员
	var $group_kickout_father = $(".group_member_list_r .group_kickout");
	var $group_kickout_son = $(".group_kickout_son .group_kickout");
	var $group_son_num = $(".group_son_num");
	var deleteMember = function(obj,url){
		obj.live("click",function(){
			var self = $(this);
			var group_son_num_text = $group_son_num.text();
			$.confirm("提示框","是否确定删除此成员，此操作不可还原！",function(){
				var data = {};
				data.uid = self.attr("uid");
				data.gid= self.attr("gid");
				$.djax({
					url:url,
					dataType:"json",
					async:true,
					data:data,
					success:function(data) {
						if (data.status == 1) {
							self.parents("li").hide("600").queue(function(){
								self.remove();
								$group_son_num.text(group_son_num_text-1);
							});
						}
						else{
							$.alert("对不起，删除成员失败，请刷新页面重试!");
						}
					},
					error:function() {
						$.alert("对不起，删除成员失败，请刷新页面重试!");
					}
				});
			});
			
		});
	};
	deleteMember($group_kickout_father,mk_url('group/manage/remove'));//删除群成员
	deleteMember($group_kickout_son,mk_url("group/subgroup/remove"));//删除子群成员

	//点击保存提交表单
	var $hide_gid = $("#hide_gid");
	var $hide_icon = $("#hide_icon");
	var $hide_invitation = $("#hide_invitation");
	var $invite_host = $("#invite_host");
	var $invite_allmember = $("#invite_allmember");
	var $group_set_save = $(".group_set_save span")
	var $group_data_form = $(".group_data form")
	$group_set_logo_ul_li.click(function(){
		var oSrc = $(this).find("img").attr("alt");
		$hide_icon.val(oSrc);
	});
	$invite_host.click(function(){
		$hide_invitation.val(1);
	});
	$invite_allmember.click(function(){
		$hide_invitation.val(3);
	});
	$group_set_save.click(function(){
		$group_data_form.submit();
	});

	//点击子群开启群聊
	var openGroupChat = function(obj,gid){
		obj.on({
			"click":function(){
				//新框架im还不行showGroupChat(gid);新框架im还不行
			}
		});
	}
	openGroupChat($(".group_chat"),$("#dissolve_group_son").attr("gid"));


});
