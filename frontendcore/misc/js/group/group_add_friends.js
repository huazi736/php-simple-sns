/**
 * @author:    duxianwei
 * @created:   2012/05/31
 * @version:   v1.0
 * @desc:      群组模块
 */

//封装函数
var group_add_friends = function(popButton,url_get,url_search){
	var gid = $("#group_id").val();
	var url_post = mk_url('group/index/invite');
	var url_group_detail = mk_url("group/index/detail", {"gid":''});
	var pager = 1;
	popButton.click(function(){
		$.djax({
			data:{page:pager,gid:$("#group_id").val()},
			url:mk_url(url_get),
			success:function(data){
			//获取json数据并且进行拼接插入到对应的位置
			var userList = '';
			var	userData = data.list;
			//如果用户列表没有数据，提示无法建立群组
			if (userData.length==0) {
				$.alert("对不你，您没有可邀请的好友")
				return false;
			}
			else{
				for(var i = 0; i < userData.length; i++) {
					userList += '<li class="0" id="'+userData[i].id+'">' +
									'<span for="checkbox'+userData[i].id+'">' +
										'<input type="checkbox" id="checkbox'+userData[i].id+'">' +
										'<img width="50" height="50" src="'+userData[i].src+'">' +
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
					title:'邀请好友',
					content:group_detail,
					buttons:'<span class="popBtns blueBtn group_index_submit" id="group_next">确定</span><span class="popBtns" id="list_close">取消</span>',
					mask:true,
					maskMode:false,
					callback:function(){
						
					}
				});

				$('.group_list').jScrollPane({}).data('jsp');//调用模拟滚动条插件
				$("#list_close").click(function(){
					list_group_fiends.loadmore.scrollParameter.pager = 2;
				});

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
					$(".popTitle span").text("邀请好友"+"("+data.num+")");
					$("#group_next").parent().show();
				}

				//对群查找调用textFocusBlue方法
				textFocusBlur($(".group_search_input"),"输入好友的名字");

				//邀请好友点击取消弹出二次确认
				$("#list_close").click(function(){
					$.confirm("提示框","是否确定取消好友邀请，此操作不可还原！",function(){$.closePopUp()});
				});

				//点击确定
				$(".group_index_submit").on({
					"click":function(){
						var checkedCount=$(".group_list .checked");
						for(var i=0;i<checkedCount.length;i++){
								arrChecked.push($(checkedCount[i]).attr('id'));
							}
						if(arrChecked.length<1){
							$.alert("您是不是忘记了选择好友呢^_^");
						}
						else{
							//点击创建群组按钮
							//拼接用户选取的好友的ID以及群组的logo、名称、简介数据发送给后台
							for(var i=0;i<arrChecked.length;i++){
								liCheckedList +="&uid[]=" + arrChecked[i];
							}
							var data=liCheckedList + "&gid=" + gid;
							$.djax({
								url:url_post,
								data:data,
								dataType:"json",
								type:"post",
								success:function(data){
									if(data.status == 1){
										window.location= url_group_detail + data.data.gid;
									}
									else{
										$.alert(data.error);
										arrChecked = [];
										liCheckedList = "";
									}
								},
								error:function(){
									$.alert("对不起，邀请好友失败");
									arrChecked = [];
									liCheckedList = "";
								}
							});
						}
					}
				})
				
			}
			}
		})
	});
}
group_add_friends($('.group_add'),'group/group/getFriend','group/group/searchFriend');
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


$('.group_add').click(function(){//这里借用望哥的followlist.js
	list_group_fiends.init({moreUrl:'group/group/getFriend', searchUrl:'group/group/searchFriend'});
});

var arrChecked = new Array();
var liCheckedList="";
var api = $('.group_list').jScrollPane({}).data('jsp');//调用模拟滚动条插件

var list_group_fiends = {
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
				var par = list_group_fiends.loadmore.scrollParameter;
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
							var	userData = data.list;
							var userList = '';
							if(userData != 0){
								par.pager++;
								for(var i = 0; i < userData.length; i++) {
									var li= '<li class="0" id="'+userData[i].id+'">' +
												'<span for="checkbox'+userData[i].id+'">' +
													'<input type="checkbox" id="checkbox'+userData[i].id+'">' +
													'<img width="50" height="50" src="'+userData[i].src+'">' +
													'<a href="javascript:void(0)">'+userData[i].name+'</a>' +
												'</span>' +
											'</li>'	
									for (var j = 0; j < arrChecked.length; j++) {
										if (arrChecked[j] == userData[i].id) {
											li = li.replace('<li', '<li class="checked"');
											li = li.replace('<input', '<input checked="checked"');
											arrChecked.splice(j,1);
										} 
									};
									userList += li;
								}
								$("#group_detail .group_list ul").append(userList);
								api.reinitialise();//重新计算滚动条高度
								if (data.last == true) {
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
			var opt = list_group_fiends.search.opts;
			if(opt.ajax.abort) {
				opt.ajax.abort();
			}
			var api = $('.group_list').jScrollPane({}).data('jsp');
			var keyword = $.trim($('.group_search_input').val()),
				_data = {keyword: keyword,pager:1,gid:$("#group_id").val()};
			opt.ajax = $.ajax({
				type: 'POST',			
				url: url,
				data: _data,
				dataType:'json',
				success:function(data) {
					var isRequest = true;
					var userList = '';
					var	userData = data.list;
					if(userData != 0){
						for(var i = 0; i < userData.length; i++) {
							var li= '<li class="0" id="'+userData[i].id+'">' +
										'<span for="checkbox'+userData[i].id+'">' +
											'<input type="checkbox" id="checkbox'+userData[i].id+'">' +
											'<img width="50" height="50" src="'+userData[i].src+'">' +
											'<a href="javascript:void(0)">'+userData[i].name+'</a>' +
										'</span>' +
									'</li>'	
							for (var j = 0; j < arrChecked.length; j++) {
								if (arrChecked[j] == userData[i].id) {
									li = li.replace('<li', '<li class="checked"');
									li = li.replace('<input', '<input checked="checked"');
									arrChecked.splice(j,1);
								} 
							};
							userList += li;
						}
						$("#group_detail .group_list ul").empty().append(userList);
						api.reinitialise();//重新计算滚动条高度
						list_group_fiends.loadmore.resetLoad(url, keyword,true);
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


