
/*
	Create 2012-7-18
	@ author linchangyuan
	@ name 《首页 - 关注信息流》
	desc init初始化-> event 事件驱动-> model 调用远端数据 -> view 渲染呈现 -> plug 绑定插件
*/

$(function(){
	var NAV_FOLLOW = {};

	// 分类映射关系
	NAV_FOLLOW.classRelation = {
		// 个人
		"follow-people":{
			viewClass:"person"
		},
		// 本地生活
		"follow-localLife":{
			viewClass:"local"
		},
		// 购物
		"follow-shopping":{
			viewClass:"shopping"
		},
		// 游戏
		"follow-games":{
			viewClass:"games"
		},
		// 房屋
		"follow-house":{
			viewClass:"house"
		},
		// 游戏
		"follow-traveling":{
			viewClass:"traveling"
		},
		// 群组-好友
		"group-friend":{
			"viewClass":"person"
		}
	};

	// juicer模板
	NAV_FOLLOW.template = {
		// 右侧用户列表
		userList:[
			'{@if data.length != 0}',
				'{@each data as itm, index}',
					'<a class="user_face_one" href="${itm.href}" title="${itm.name}">',
						'<img width="32" height="32" src="${itm.avatar}" alt="${itm.name}" />',
					'</a>',
				'{@/each}',
			'{@else}',
				'<span>暂无关注！<span>',
			'{@/if}'
		].join("")
	};

	NAV_FOLLOW.view = function(name,arg){
		var _class = {
			// 渲染页面右侧关注列表
			list:function(arg){
				var str = "<div></div>"
				str += '';
				arg[0].html(str);
			},
			// 渲染页面信息流模块
			infoModule:function(arg) {
				var infoData = arg[1],
					type = infoData.type || "";

				var viewClass = arg[0],
					className = "Class_follow_" + viewClass + "_" + type;

				var $infoBox = null,
					theClass = window[className];
				
				infoData.followList = NAV_FOLLOW.$followList;

				if(typeof theClass === "function") {
					try {
						var infoObj = new theClass;

						$infoBox = infoObj.init(infoData);
					} catch(ex) {
						$infoBox = null;
					}
				}

				return $infoBox;
			},
			// 批量渲染页面信息流模块
			infoModuleLst:function(arg) {
				var infoLst = arg[0],
					modulesData = arg[1],
					viewClass = arg[2],
					position = arg[3] || "bottom";
				var $infoBox = null;

				for(var i = 0, l = modulesData.length; i < l; i ++) {
					var infoData = $.extend({
						index:i,
						host:CONFIG['fdfsdomain'] ,
						location:mk_url("main/index/main")
					},modulesData[i]);
					
					$infoBox = NAV_FOLLOW.view(["infoModule"],[viewClass,infoData]);

					if($infoBox) {
						if(position === "top") {
							infoLst.prepend($infoBox);
						} else {
							infoLst.append($infoBox);
						}
					}
				}
			},
			followUserList:function(data) {
				var html = juicer(NAV_FOLLOW.template.userList, data);
				var $userLst = $("#followContain");
				
				$userLst.find("div.one").html(html);
				$userLst.find("div.more a").attr("href", data.href);
			}
		}
		return _class[name](arg);
	};

	NAV_FOLLOW.event = function(name,arg){
		this.event._class = {
			navChange:function(){
				NAV_FOLLOW.$focusNav.children("[type]").click(function(){
					var el = $(this);

					if(el.hasClass("current")) {
						return;
					}

					NAV_FOLLOW.$focusNav.children().removeClass("current");
					el.addClass("current");

					var url = el.children().attr('href').replace(/^.*#/, '');
					$.history.load(url);
				});
			}
		};

		return this.event._class[name](arg)
	};

	NAV_FOLLOW.plug = (function(){
		var _class = {
			commentEasy:function(arg){
				arg.commentEasy({
	                minNum:3,
	                UID:CONFIG.u_id,
	                userName:CONFIG.u_name,
	                avatar:CONFIG.u_head,
                    relay:!0,
	                userPageUrl:$("#hd_userPageUrl").val(),relayCallback:function (obj,_arg) {
                            var comment=new ui.Comment();
                            comment.share(obj,_arg,!0);
                        },
	                onLoadCallback:function () {
                        $.each(arg, function () {
                            var time = $(this).attr("time");
                            if ($(this).find(".comment_title").find("li.time").size() == 0) {
                                $(this).find(".comment_title").prepend("<li class='time'>" + time + "</li>");
                            }
                        });
                    }
	            });
			},
			showAsk:function(arg){
				$.each(arg[0],function(){
					var index = parseInt($(this).attr("data"));
					$(this).showAsk(arg[1][index].ask);
				});
			},
			setVideo:function(arg){
				setVideo(arg[0]);
			},
			scrollLoad:function(arg){
				/*arg[0].scrollLoad({
	                type:'POST',
	                dataType:'json',
	                data:$.extend({action_uid:CONFIG.u_id},arg[2] || {}),
	                proxy:self,
	                url:arg[1],
	                text:"显示更多信息",
	                success:arg[3]
	            });*/
				
				var url = arg[1],
					data = $.extend({action_uid:CONFIG.u_id,page:1}, arg[2] || {});
					success = arg[3] || (new Function);
				var param = {};
				var nextPage = NAV_FOLLOW.$nextPage.attr("loading","false").show();
				var showTimes = 0,
					maxTimes = 3;

				var getNext = function() {
					var _param = $.extend({},param);
					var _data = $.extend({},data);

					_data = $.extend(_data,_param);

					nextPage.attr("loading","true");
					nextPage.find("a").html('<img src="' + CONFIG.misc_path + 'img/plug-img/djax/loading2.gif" />');
					$.ajax({
						type:"POST",
						url:url,
						data:_data,
						dataType:"JSON",
						success:function(returnData) {
							showTimes = showTimes + 1;

							param = returnData.data.param || {};
							data.page = parseInt(data.page || "1") + 1;
							
							nextPage.find("a").html("显示更多信息");

							if(showTimes >= maxTimes) {
								nextPage.attr("loading","true").show;
								showTimes = 0;
							} else {
								nextPage.attr("loading","false");
							}

							success(returnData.data);

							if(returnData.data.isend) {
								nextPage.hide();
							}
						},
						error:function(data) {
						}
					});
				};

				nextPage.unbind("click").bind("click",function() {
					getNext();
				});

				$(window).unbind("scroll",NAV_FOLLOW.scrollLoad || (new Function));

				NAV_FOLLOW.scrollLoad = function(ev) {
					if(nextPage.attr("loading") === "true") {
						return;
					}

					var scrollTop = $(window).scrollTop(),
						winHeight = $(window).height(),
						nextPageTop = nextPage.offset().top;

					if((scrollTop + winHeight) > nextPageTop) {
						getNext();
					}
				};
				
				$(window).bind("scroll",NAV_FOLLOW.scrollLoad).scroll();
			},
			setPhotoLink:function(arg){
				var dkcode,pid,src;
				$.each(arg[0],function(){
					dkcode = $(this).attr("action_dkcode");
					pid = $(this).attr("pid");
					src = mk_url('album/index/photoInfo',{photoid:pid,dkcode:dkcode});
					$(this).attr("url",src);
				})
			},
			updateNewInfo:function(conf) {
				var ctime = $("#ctime").val();
				var ltime = $("#ltime").val();
				var updateInfoBtn = NAV_FOLLOW.$updateInfoBtn;
				var tagType = conf.tagid || conf.msgtype;

				updateInfoBtn.attr("acount","0");

				if(NAV_FOLLOW.updateTimer) {
					window.clearInterval(NAV_FOLLOW.updateTimer);
				}
				
				NAV_FOLLOW.updateTimer = window.setInterval(function() {
					NAV_FOLLOW.model(conf.getCountMethod,[tagType,ctime,function(data) {
						var curCount = parseInt(updateInfoBtn.attr("acount") || 0) + parseInt(data.data.data);

						ctime = data.data.param.ctime;

						if(curCount === 0) {
							updateInfoBtn.hide();
						} else {
							updateInfoBtn.show();
						}
						updateInfoBtn.find("a").html('有' + curCount + '条新信息，点击查看');
						updateInfoBtn.attr("acount",curCount);
						
						//console.log(data);
						//console.log(curCount);
						//console.log(ctime);
					}]);
				},15000);

				updateInfoBtn.unbind("click").bind("click",function() {
					if(updateInfoBtn.attr("loading") === "true") {
						return;
					}
					updateInfoBtn.attr("loading","true");
					updateInfoBtn.find("a").html('<img src="' + CONFIG.misc_path + 'img/plug-img/djax/loading2.gif" />');
					NAV_FOLLOW.model(conf.getInfoMethod,[tagType,ltime,function(data) {
						var $infoBox = null;
						var viewClass = NAV_FOLLOW.classRelation[conf.type]["viewClass"];
						var oldData = data;

						data = data.data.data;

						// 批量渲染信息流模块
						NAV_FOLLOW.view(["infoModuleLst"],[NAV_FOLLOW.$followList,data,viewClass,"top"]);
						NAV_FOLLOW.processModule(data);

						updateInfoBtn.attr({"loading":"false","acount":0}).hide();
						ltime = oldData.data.param.ltime;
					}]);
				});
			}
		};

		var F = function(name,arg) {
			return _class[name](arg);
		};
		return F;
	})();

	//请求得到数据
	NAV_FOLLOW.model = function(name,arg){
		this.model._class = {
			// 更新网页信息流个数
			updateWebCount:function(arg) {
				var tagid = arg[0],
					ctime = arg[1],
					callback = arg[2] || new Function;

				var data = {tagid:tagid,ctime:ctime};

				if(NAV_FOLLOW.$followList.children().size() !== 0) {
					data.lastid = NAV_FOLLOW.$followList.children().first().attr("id")
				}

				$.djax({
					url:mk_url("main/webstreams/getTimeTopicCount"),
					async:true,
					dataType:"json",
					data:data,
					success:function(data){
						if(data){
							callback(data);
						}
					}
				});
			},
			// 更新个人/好友信息流个数
			updatePersonFriendCount:function(arg) {
				var msgtype = arg[0],
					ctime = arg[1],
					callback = arg[2] || new Function;

				var data = {msgtype:msgtype,ctime:ctime};

				if(NAV_FOLLOW.$followList.children().size() !== 0) {
					data.lastid = NAV_FOLLOW.$followList.children().first().attr("id")
				}

				$.djax({
					url:mk_url("main/msgstreams/getTimeTopicCount"),
					async:true,
					dataType:"json",
					data:data,
					success:function(data){
						if(data){
							callback(data);
						}
					}
				});
			},
			getNewWebInfo:function(arg) {
				var tagid = arg[0],
					ltime = arg[1],
					callback = arg[2] || new Function;

				var data = {tagid:tagid,ltime:ltime};

				if(NAV_FOLLOW.$followList.children().size() !== 0) {
					data.lastid = NAV_FOLLOW.$followList.children().first().attr("id")
				}

				$.djax({
					url:mk_url("main/webstreams/getTimeTopicInfo"),
					async:true,
					dataType:"json",
					data:data,
					success:function(data){
						if(data){
							callback(data);
						}
					}
				});
			},
			getNewPerFriInfo:function(arg) {
				var msgtype = arg[0],
					ltime = arg[1],
					callback = arg[2] || new Function;
				var data = {msgtype:msgtype,ltime:ltime};

				if(NAV_FOLLOW.$followList.children().size() !== 0) {
					data.lastid = NAV_FOLLOW.$followList.children().first().attr("id")
				}

				$.djax({
					url:mk_url("main/msgstreams/getTimeTopicInfo"),
					async:true,
					dataType:"json",
					data:data,
					success:function(data){
						if(data){
							callback(data);
						}
					}
				});
			}
		};
		return this.model._class[name](arg);
	};

	// 统一处理列表中的插件、事件
	NAV_FOLLOW.processModule = function(data) {
		NAV_FOLLOW.plug("showAsk",[NAV_FOLLOW.$followList.children("li").not("[loaded]").find(".showAsk"),data]);
		NAV_FOLLOW.plug('commentEasy',NAV_FOLLOW.$followList.find("div.commentBox"));
		NAV_FOLLOW.plug('setVideo',[NAV_FOLLOW.$followList]);
		NAV_FOLLOW.$focusNav.find(".current").removeClass("min-loading");
		NAV_FOLLOW.$followList.children("li").attr("loaded",true);
		NAV_FOLLOW.plug('setPhotoLink',[NAV_FOLLOW.$followList.find("a.photoLink")]);
	};

	NAV_FOLLOW.init = function(){
		NAV_FOLLOW.$focusNav = $("ul.sideNavList");// 关注菜单
		NAV_FOLLOW.$followList = $("#followList"); // 右侧列表容器
		NAV_FOLLOW.$nextPage = $('<div name="nextPage" class="nextPage"><a class="nextPage">显示更多信息</a></div>');
		NAV_FOLLOW.$updateInfoBtn = $('<div name="updateInfo" class="nextPage"><a href="javascript:;">有0条新信息，点击查看</a></div>').show().hide();

		
		if($("#is_main").val() !== "1") {
			return;
		}

		NAV_FOLLOW.event("navChange");
		NAV_FOLLOW.$followList.after(NAV_FOLLOW.$nextPage);
		NAV_FOLLOW.$followList.before(NAV_FOLLOW.$updateInfoBtn);

		$.history.init(function(anchor) {
			// 获取当前的锚点名称（即分类名称）
			var type = anchor || "follow-people";

			var el = NAV_FOLLOW.$focusNav.children("li[type='" + type + "']");
			var tagid = el.find("a.sideNavLink").attr("rel");

			NAV_FOLLOW.$focusNav.children().removeClass("current");
			el.addClass("current");

			NAV_FOLLOW.$followList.empty(); // 清空信息流列表中的内容
			NAV_FOLLOW.$updateInfoBtn.hide();

			if(tagid) {
				var scrollLoadParam = {
					tagid:tagid,
					action_uid:CONFIG.u_id,
					lastid:0,score:0
				};

				NAV_FOLLOW.plug("scrollLoad",[NAV_FOLLOW.$followList,mk_url("main/webstreams/msgActionCate"),scrollLoadParam,function(data){

					var status = data.status;
					var following = data.following;
					var oldData = data;

					// 渲染右侧关注的用户/网页列表
					NAV_FOLLOW.view(["followUserList"], following);
					
					data = data.data || [];
					if(status != 0 && data && data.length !== 0){
						var $infoBox = null;
						var viewClass = NAV_FOLLOW.classRelation[type]["viewClass"];

						// 批量渲染信息流模块
						NAV_FOLLOW.view(["infoModuleLst"],[NAV_FOLLOW.$followList,data,viewClass]);


						NAV_FOLLOW.processModule(data);
					}else{
						//临时的
						//NAV_FOLLOW.$followList.append('<div style="margin:10px; border:1px solid #899BC1; padding:5px 10px; background:#F5F8FA;">您还未关注好友，或您未关注好友网页，赶快去关注吧！</div>');
						NAV_FOLLOW.$nextPage.attr("loading","true").hide();
					}
				}]);

				NAV_FOLLOW.plug("updateNewInfo",{
					type:type,
					getCountMethod:"updateWebCount",
					getInfoMethod:"getNewWebInfo",
					tagid:tagid
				});

				$("#followContain").find("div.title").html("关注列表");
			} else {
				if(type === "follow-people") {
					NAV_FOLLOW.plug("scrollLoad",[NAV_FOLLOW.$followList,mk_url("main/msgstreams/followstream"),{},function(data){
						var status = data.status;
						var following = data.following;
						
						NAV_FOLLOW.view(["followUserList"], following);
						if(data.status != 0 && data.data){
							var $infoBox = null;
							var viewClass = NAV_FOLLOW.classRelation[type]["viewClass"];

							data = data.data;

							// 批量渲染信息流模块
							NAV_FOLLOW.view(["infoModuleLst"],[NAV_FOLLOW.$followList,data,viewClass]);
							NAV_FOLLOW.processModule(data);
							
						}else{
							//NAV_FOLLOW.$followList.append('<div style="margin:10px; border:1px solid #899BC1; padding:5px 10px; background:#F5F8FA;">暂无信息！</div>');
							NAV_FOLLOW.$nextPage.attr("loading","true").hide();
						}
					}]);

					NAV_FOLLOW.plug("updateNewInfo",{
						type:type,
						getCountMethod:"updatePersonFriendCount",
						getInfoMethod:"getNewPerFriInfo",
						msgtype:"fansInfos"
					});

					$("#followContain").find("div.title").html("关注列表");
				} else if(type === "group-friend") {
					NAV_FOLLOW.plug("scrollLoad",[NAV_FOLLOW.$followList,mk_url('main/msgstreams/followFriStream'),{},function(data){

						var status = data.status;
						var friends = data.friends;
						var oldData = data;

						// 渲染右侧关注的用户/网页列表
						NAV_FOLLOW.view(["followUserList"], friends);
						
						data = data.data || [];
						if(status != 0 && data && data.length !== 0){
							var $infoBox = null;
							var viewClass = NAV_FOLLOW.classRelation[type]["viewClass"];

							// 批量渲染信息流模块
							NAV_FOLLOW.view(["infoModuleLst"],[NAV_FOLLOW.$followList,data,viewClass]);

							NAV_FOLLOW.processModule(data);
						}else{
							//临时的
							//NAV_FOLLOW.$followList.append('<div style="margin:10px; border:1px solid #899BC1; padding:5px 10px; background:#F5F8FA;">您还未关注好友，或您未关注好友网页，赶快去关注吧！</div>');
							NAV_FOLLOW.$nextPage.attr("loading","true").hide();
						}
					}]);

					NAV_FOLLOW.plug("updateNewInfo",{
						type:type,
						getCountMethod:"updatePersonFriendCount",
						getInfoMethod:"getNewPerFriInfo",
						msgtype:"frisInfos"
					});

					$("#followContain").find("div.title").html("好友列表");
				}
			}
		});
	};

	NAV_FOLLOW.init();
});
