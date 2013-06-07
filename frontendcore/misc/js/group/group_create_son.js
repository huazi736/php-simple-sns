/**
 * @author:    duxianwei
 * @created:   2012/05/31
 * @version:   v1.0
 * @desc:      群组模块
 */

//封装函数
var group_create_son = function(popButton,url_get,url_search){
	var url_post = mk_url('group/subgroup/add');
	var url_group_detail = mk_url("group/subgroup/index", {'sid': ''});
	popButton.click(function(){
		$.djax({
			data:{gid:$("#group_id").val(),page:1},
			url:mk_url(url_get),
			success:function(data){
			//获取json数据并且进行拼接插入到对应的位置
			var userList = '';
			var	userData = data.data.list;
			//如果用户列表没有数据，提示无法建立群组
			if (userData.length==0) {
				$.alert("对不起，您的群组成员列表数量为0，请您先邀请您的好友进入该群！")
				return false;
			}
			else{
				for(var i = 0; i < userData.length; i++) {
					userList += '<li class="0" id="'+userData[i].uid+'">' +
									'<span for="checkbox'+userData[i].uid+'">' +
										'<input type="checkbox" id="checkbox'+userData[i].uid+'">' +
										'<img width="50" height="50" src="'+userData[i].avatar+'">' +
										'<a href="javascript:void(0)">'+userData[i].name+'</a>' +
									'</span>' +
								'</li>'	
				}
				var group_detail = '<div id="group_form">' +
										'<div class="group_search">' +
											'<span class="group_search_submit">查找好友：</span>' +
											'<input type="text" class="group_search_input color999" value="" default="输入好友的名字" />' +
										'</div>' +
										'<div id="group_detail">' +
											'<div class="group_list clearfix">' +
												'<ul class="clearfix">'+userList+'</ul>' +
											'</div>' +
										'</div>';
				$(this).popUp({
					width:780,
					title:'创建好友',
					content:group_detail,
					buttons:'<span class="popBtns blueBtn" id="group_next">下一步</span><span class="popBtns" id="list_close">取消</span>',
					mask:true,
					maskMode:false,
					callback:function(){
						
					}
				});

				//群组选择好友弹窗的滚动条调用插件
				var api = $('.group_list').jScrollPane({}).data('jsp');


				//列表好友的选取以及鼠标移入移出效果
				$("#group_detail li").die().live({
						"click":function(){
							if ($(this).attr("class")=="0") {
								$(this).find("input").attr("checked","checked");
								$(this).attr("class","checked").css({"background-color":"#D8DFEA"});;
							}
							else{
								$(this).find("input").removeAttr("checked");
								$(this).attr("class","0");
							}
						},
						"mouseover":function(){
							$(this).css({"background-color":"#eceff5"});
						},
						"mouseout":function(){
							$(this).removeAttr("style");
						}
				});

				//如果好友列表是显示的话
				if($("#group_create").is("hidden")==false){
					$(".popTitle span").text("邀请好友");
					$("#group_next").parent().show();
				}

				//对群查找调用textFocusBlue方法
				textFocusBlur($(".group_search_input"),"输入好友的名字");

				//邀请好友点击取消弹出二次确认
				$("#list_close").click(function(){
					$.confirm("提示框","是否确定取消好友邀请，此操作不可还原！",function(){$.closePopUp()});
				});

				//点击下一步填写群组相关信息
				$("#group_next").on({
					"click":function(){
						var checkedCount=$(".group_list .checked");
						for(var i=0;i<checkedCount.length;i++){
								arrChecked.push($(checkedCount[i]).attr('id'));
							}
						if(arrChecked.length<1){
							$.alert("请至少选择一个好友");
						}
						else{
							$(".group_search").hide();
							$("#group_detail").hide();
							$("#group_next").parent().hide();
							$(".popTitle span").text("创建群");
							$(".popTitle").append('<a href="javascript:void(0)" class="create_close" title="关闭"><img title="关闭" src="'+ CONFIG['misc_path']+'img/group/close.png"  alt="关闭"/></a>');
							//点击下一步生成群组信息
							$("#group_form").append('<div id="group_info">' +
														'<div class="group_info_fill" id="group_logo_sub">' +
															'<table id="group_table" cellspacing="0" cellpadding="0" border="0">' +
																'<tr>' +
																	'<th valign="top">群名称：</th>' +
																	'<td>' +
																		'<input type="text" id="group_name" class="group_name_dafault color999" value="以中文、字母、数字组成，且长度不超过20个字符" />' +
																	'</td>' +
																'</tr>' +
																'<tr>' +
																	'<th>群头像：</th>' +
																	'<td>' +
																		'<ul class="group_logo clearfix">' +
																			'<li><a href="javascript:void(0)"><img src="'+ CONFIG['misc_path']+'img/group/icon/subgroup/1.png" /></a></li>' +
																			'<li><a href="javascript:void(0)"><img src="'+ CONFIG['misc_path']+'img/group/icon/subgroup/2.png" /></a></li>' +
																			'<li><a href="javascript:void(0)"><img src="'+ CONFIG['misc_path']+'img/group/icon/subgroup/3.png" /></a></li>' +
																			'<li><a href="javascript:void(0)"><img src="'+ CONFIG['misc_path']+'img/group/icon/subgroup/4.png" /></a></li>' +
																			'<li><a href="javascript:void(0)"><img src="'+ CONFIG['misc_path']+'img/group/icon/subgroup/5.png" /></a></li>' +
																			'<li><a href="javascript:void(0)"><img src="'+ CONFIG['misc_path']+'img/group/icon/subgroup/6.png" /></a></li>' +
																			'<li><a href="javascript:void(0)"><img src="'+ CONFIG['misc_path']+'img/group/icon/subgroup/7.png" /></a></li>' +
																			'<li><a href="javascript:void(0)"><img src="'+ CONFIG['misc_path']+'img/group/icon/subgroup/8.png" /></a></li>' +
																			'<li><a href="javascript:void(0)"><img src="'+ CONFIG['misc_path']+'img/group/icon/subgroup/9.png" /></a></li>' +
																			'<li><a href="javascript:void(0)"><img src="'+ CONFIG['misc_path']+'img/group/icon/subgroup/10.png" /></a></li>' +
																			'<li><a href="javascript:void(0)"><img src="'+ CONFIG['misc_path']+'img/group/icon/subgroup/11.png" /></a></li>' +
																			'<li><a href="javascript:void(0)"><img src="'+ CONFIG['misc_path']+'img/group/icon/subgroup/12.png" /></a></li>' +
																			'<li><a href="javascript:void(0)"><img src="'+ CONFIG['misc_path']+'img/group/icon/subgroup/13.png" /></a></li>' +
																			'<li><a href="javascript:void(0)"><img src="'+ CONFIG['misc_path']+'img/group/icon/subgroup/14.png" /></a></li>' +
																			'<li><a href="javascript:void(0)"><img src="'+ CONFIG['misc_path']+'img/group/icon/subgroup/15.png" /></a></li>' +
																		'</ul>' +
																	'</td>' +
																'</tr>' +
																'<tr><th>&nbsp;</th><td>&nbsp;</td></tr>'+
																'<tr><th>&nbsp;</th><td>&nbsp;</td></tr>'+
																'<tr>' +
																	'<th>&nbsp;</th>' +
																	'<td>' +
																		'<div class="popBtnsWrap">' +
																			'<span id="group_create" class="popBtns blueBtn callbackBtn">创建</span><span class="popBtns closeBtn" id="create_close">取消</span>' +
																		'</div>' +
																	'</td>' +
																'</tr>' +
															'</table>' +
														'</div>' +
													'</div>');
							//选择某个群组logo
							$(".group_logo li").click(function(){
								$(".group_logo li").removeClass("checked");
								$(this).addClass("checked");
							})
							//对群名称调用textFocusBlue方法
							textFocusBlur($("#group_name"),"以中文、字母、数字组成，且长度不超过20个字符");
							//对群介绍调用textFocusBlue方法
							textFocusBlur($("#group_textarea"),"这里填写您的群介绍");
							
							//点击创建群组按钮
							$("#group_create").on({
								"click":function(){
									var group_name = $("#group_name").val().length;
									if($("#group_name").val()=="以中文、字母、数字组成，且长度不超过20个字符"||$("#group_name").val()==""){
										$.alert("您是不是忘记了填写子群名称哦");
									}
									else if (group_name>20) {
										$.alert("呃，您填写的群名称太长了哦（最多20个字符的）");
									}
									 
									else{
										//拼接用户选取的好友的ID以及群组的logo、名称、简介数据发送给后台
										for(var i=0;i<arrChecked.length;i++){
											liCheckedList +="&uid[]=" + arrChecked[i];
										}
										var data=liCheckedList + "&icon=" 
												+ ($(".group_logo .checked img").attr("src") || '')
												+ "&name=" + $("#group_name").val()
												+ "&gid=" + $("#group_id").val();
										$.djax({
											url:url_post,
											data:data,
											dataType:"json",
											type:"post",
											success:function(data){
												if(data.status == 1){
													window.location=url_group_detail + data.data.sid;
												}
												else if (data.status == 0) {
													$.alert(data.error);
												}
												else{
													$.alert("对不起，子群创建失败");
													arrChecked = [];
													liCheckedList = "";
												}
											},
											error:function(){
												$.alert("对不起，子群创建失败");
												arrChecked = [];
												liCheckedList = "";
											}
										});
									}
								}
							});
							//创建的时候点击取消按钮事件
							$("#create_close,.create_close").click(function(){
								$.confirm("提示框","是否确定取消创建群组，此操作不可还原！",function(){
									$.closePopUp();
									$(".create_close").remove();
									arrChecked = [];
									liCheckedList = "";
								});
							});
						}
					}
				})
				
			}
			}
		})
	});
}
group_create_son($('.link_wrap_son_group'),'group/subgroup/groupallmember','group/subgroup/searchbygroup');
// group_create($('#group_friend a'),'single/group/group/getFriend','single/group/group/searchFriend');
//这里封装好了一个关于文本框和文本域默认显示值以及获取焦点默认值消失的方法textFocusBlue()
//用法示例 textFocusBlue($("#div"),"默认值");
var textFocusBlur=function(obj,value){
	obj.on({
		"focus":function(){
			if (obj.val() == "" || $(this).val() == value) {
				obj.val("");
				obj.addClass("color333");
			}
			else{
				obj.addClass("color333");
			}
		},
		"blur":function(){
			if (obj.val()=="") {
				obj.val(value);
				obj.removeClass("color333");
			};
			
		}
	})
}

//对群组发言区域调用textFocusBlue方法
textFocusBlur($(".group_index_main-textarea textarea"),"想说点什么");
//针对群组首页右边的成员列表的滚动条调用滚动条插件
$(function(){
				$('.group_user_list').jScrollPane();
			});


$('.link_wrap_son_group').click(function(){//这里借用望哥的followlist.js
	list_group_add_son.init({moreUrl:'group/subgroup/groupallmember', searchUrl:'group/subgroup/searchbygroup'});
});

var arrChecked = new Array();
var liCheckedList="";
var api = $('.group_list').jScrollPane({}).data('jsp');//调用模拟滚动条插件

//搜索之后运行的js
var list_group_add_son = {
	init: function(url) {
		var moreUrl = (url.mk_url === false) ? url.moreUrl : mk_url(url.moreUrl);
		this.loadmore.scrollParameter.url = moreUrl;
		this.loadmore.init();
		if(url.visibleUrl) {
			var visibleUrl = (url.mk_url === false) ? url.visibleUrl : mk_url(url.visibleUrl);
			this.visible(visibleUrl);
		}	
		if(url.searchUrl) {
			var searchUrl = (url.mk_url === false) ? url.searchUrl : mk_url(url.searchUrl);
			this.search.init(searchUrl);
		}
	},
	//滚动加载
	loadmore: {
		init: function() {
			this.scroll();
		},
		//滚动加载及点击加载 ——参数
		scrollParameter: {
			pager: 2,
			url: '',
			keyword: '',
			isRequest:true
		},
		resetLoad: function(url, keyword,isRequest) {
			this.scrollParameter = {
				pager: 2,
				url: url,
				keyword: keyword,
				isRequest:isRequest
			};
		},
		scroll: function() {
			//滚动条是否到达底部
			$('.group_list').live('jsp-scroll-y',function(event,scrollPositionY,isAtTop,isAtBottom){
				var par = list_group_add_son.loadmore.scrollParameter;
				var isRequest = par.isRequest;
				var data = {pager: par.pager,gid:$("#group_id").val()};
				if(isAtBottom && isRequest){
					$.djax({
						url: par.url,
						type: 'POST',
						data: data,
						dataType: 'json',
						success: function(data) {
							var api = $('.group_list').jScrollPane({}).data('jsp');
							var str = '';
							var	userData = data.data.list;
							var userList = '';
							if(userData != 0){
								par.pager++;
								for(var i = 0; i < userData.length; i++) {
									var li= '<li class="0" id="'+userData[i].uid+'">' +
												'<span for="checkbox'+userData[i].uid+'">' +
													'<input type="checkbox" id="checkbox'+userData[i].uid+'">' +
													'<img width="50" height="50" src="'+userData[i].avatar+'">' +
													'<a href="javascript:void(0)">'+userData[i].name+'</a>' +
												'</span>' +
											'</li>'	
									for (var j = 0; j < arrChecked.length; j++) {
										if (arrChecked[j] == userData[i].uid) {
											li = li.replace('<li', '<li class="checked"');
											li = li.replace('<input', '<input checked="checked"');
											arrChecked.splice(j,1);
										} 
									};
									userList += li;
								}
								$("#group_detail .group_list ul").append(userList);
								api.reinitialise();//重新计算滚动条高度
								if (data.data.last == true) {
									par.isRequest = false;
								}
								else{
									isRequest = true;
								}
							}
						}
					});
				}
			});
		}
	},
	//搜索
	search: {
		init: function(url) {

			var searchInput = $('.group_search_input'),
				self = this,
				opt = self.opts;
			if(opt.opera < 0) {
				$('.group_search_input').live('keyup',function(){
					var _this = this,
						value = $.trim(_this.value);
					setTimeout(function() {
						if(value !== opt.init_val){
							self.getData(value, url);
							opt.init_val = _this.value;
						}
					}, 300);
				});
			} 
			else {
				$('.group_search_input').live('input',function(event) {
					var _this = this,
						value = $.trim(_this.value);
					setTimeout(function() {
						if(value !== opt.init_val){
							self.getData(value, url);
							opt.init_val = _this.value;
						}
					}, 300);
				});
			}
		},
		opts: {
			ajax: '',
			opera: navigator.userAgent.indexOf('Opera'),
			init_val: ''
		},
		getData: function(keywords, url) {
			var opt = list_group_add_son.search.opts;
			if(opt.ajax.abort) {
				opt.ajax.abort();
			}
			var api = $('.group_list').jScrollPane({}).data('jsp');
			var keyword = $.trim($('.group_search_input').val()),
				_data = {keyword: keyword,gid:$("#group_id").val(),pager:1};
				
			opt.ajax = $.ajax({
				type: 'POST',			
				url: url,
				data: _data,
				dataType:'json',
				success:function(data) {
					var isRequest = true;
					var userList = '';
					var	userData = data.data.list;
					if(userData != 0){
						for(var i = 0; i < userData.length; i++) {
							var li= '<li class="0" id="'+userData[i].uid+'">' +
										'<span for="checkbox'+userData[i].uid+'">' +
											'<input type="checkbox" id="checkbox'+userData[i].uid+'">' +
											'<img width="50" height="50" src="'+userData[i].avatar+'">' +
											'<a href="javascript:void(0)">'+userData[i].name+'</a>' +
										'</span>' +
									'</li>'	
							for (var j = 0; j < arrChecked.length; j++) {
								if (arrChecked[j] == userData[i].uid) {
									li = li.replace('<li', '<li class="checked"');
									li = li.replace('<input', '<input checked="checked"');
									arrChecked.splice(j,1);
								} 
							};
							userList += li;
						}
						$("#group_detail .group_list ul").empty().append(userList);
						api.reinitialise();//重新计算滚动条高度
						list_group_add_son.loadmore.resetLoad(url, keyword,true);
					}
					else{
						$("#group_detail .group_list ul").empty();
						api.reinitialise();//重新计算滚动条高度
					}
				}
			});
			var liChecked =$(".group_list .checked");
			var liCheckedList="";
			for(var i=0;i<liChecked.length;i++){
				arrChecked.push($(liChecked[i]).attr('id'));
			}
		}
	}
};


