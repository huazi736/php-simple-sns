/**
 * @author:    tianxb(55342775@qq.com)
 * @created:   2011/12/2
 * @version:   v1.0
 * @desc:      IM chat
 * 参数说明:
 * UID: 当前用户ID
 * onlineCountUrl: 在线数
 * friendList: 在线列表
 * searchUrl: 搜索地址
 * sendUrl: 发送地址
 * recciveUrl: 接收地址
 * lastMsgUrl: 最近三条消息
 * isGetLastMsg:是否获取最近消息 默认否 false;
 * time: 定时获取时间毫秒数 默认10000
 * onlineUrl:检测在线
 * ajaxType: ajax格式默认jsonp
 * ajaxMethodType: ajax提交方式,默认GET
 * historyUrl:聊天记录完整URL，不含ID
 * sessionUrl:获取会话ID的URL
 * friendGroupList根据组查好友 参数uid,groupId
 */
 var miscpath=CONFIG['misc_path'],imsound;
(function($){	
	String.prototype.len=function(){return this.replace(/[^\x00-\xff]/g,"**").length;};
	$.fn.IM=function(options){
		//$(this).html(_html);
		var relativeUrl='/app/modules/home/views/';
		var _self=this;
		var settings={
			UID:1099,userName:"李萌",
			//onlineCountUrl:relativeUrl+"tianxbAjaxTest.php?act=getOnlineNum",
			friendList:relativeUrl+"tianxbAjaxTest.php?act=getList",
			searchUrl:relativeUrl+"tianxbAjaxTest.php?act=search",
			sendUrl:relativeUrl+"tianxbAjaxTest.php?act=send",
			recciveUrl:relativeUrl+"tianxbAjaxTest.php?act=getMsg",
			lastMsgUrl:relativeUrl+"tianxbAjaxTest.php?act=getLastMsg",
			isGetLastMsg:false,
			time:3000,
			onlineUrl:relativeUrl+"tianxbAjaxTest.php?act=online",
			ajaxType:"json",ajaxMethodType:"GET",
			historyUrl:"http://192.168.12.125/dk/notice/list_msg?fromid=",
			sessionUrl:relativeUrl+"tianxbAjaxTest.php?act=getSessionId",
			friendGroupList:relativeUrl+"tianxbAjaxTest.php?act=getGroupList"
		};
		if(options) $.extend(settings,options);
		var tempValue='';//临时变量
		var timeChange;//定时器
		var jqImContent=$("#imContent");
		var imDialog=$("#imDialog");
		var _history = $("div.imHistory");
		var imFriendList=jqImContent.find(".imFriendList");
		var faceArr=['微笑','撇嘴','色','发呆','大哭','害羞','闭嘴','睡觉','发怒','调皮',
					'呲牙','难过','冷汗','吐','可爱','饿','白眼','傲慢','困','惊恐',
					'流汗','憨笑','疑问','晕','折磨','抠鼻','坏笑','鄙视','委屈','快哭了',
					'亲亲','阴险','吓','囧','可怜','生气','财迷','惊','冰冻','石化'
		];
		//$("#TreeTitle").one('click',function(){initTree()});
		//初始化
		var initTree=function(){
			var xhr = $.ajax({
					dataType:settings.ajaxType,
					data:{uid:settings.UID},
					url:settings.friendList,
					type:settings.ajaxMethodType,
					cache:false,
					error:alertError
				});
			xhr.done(function(result){
				if(result){
					if(result.state==1){
							var data=result.data;
							$("#TreeTitle").find("strong").html(result.count||0);
							var len=data.length;
							if (len>0){
								var TempArry=[];
								for(var i=0;i<len;i++){
									var dataItem=data[i];
									var list=dataItem.list||[];
									var listLen=list.length||0;
									TempArry.push('<div class="imGroup">');
									TempArry.push('<h4 class="extend">'+dataItem.groupName+'<span class="count">[<em>'+listLen+'</em>]</span></h4>');								
									TempArry.push('<ul style="display: none;" class="imGroup" groupId="'+dataItem.groupId+'">');
									for(var j=0;j<listLen;j++){
										var item=list[j];
										var isOnline=item.isOnline?'':' class="offline" ';
										TempArry.push(' <li rel="'+item.id+'"><a'+isOnline+' href="javascript:;"><img src="'+item.imgUrl+'" alt="'+item.name+'"/><span class="imName">'+item.name+'</span></a></li>');
									}							
									TempArry.push('</ul>');
									TempArry.push('</div>');
								}
								jqImContent.find("div.imFriendGroup").html(TempArry.join(''));
								///聊天
								jqImContent.find("div.imFriendGroup li").bind("click",openTabChat);
								//分组显示/隐藏
								jqImContent.find("div.imFriendGroup h4").bind("click",function(){
									var _next = $(this).toggleClass("extend").next();
									if (next.child().size()){
										$(this).toggleClass("extend").next().toggle();
									}
								});
							}else{
								jqImContent.find("div.imFriendGroup").html('<li class="imError">没有任何联系人</li>');
							}
						}
					}else
						alert(result.msg);
					return result;
				});
                               /* xhr.always(function(){
                                    receiveMsg();
                                })*/
		}
		function init(){	
			$("ul.imGroup").find("em").html("0/0");
			try{
			DwrServlet.sessionInit(settings.UID);
			}catch(ex){}
			///聊天
			jqImContent.find("div.imFriendGroup li").bind("click",openTabChat);
			//分组显示/隐藏
			jqImContent.find("div.imFriendGroup h4").bind("click",function(){
				var _next = $(this).toggleClass("extend").next();
				if (_next.children().length){
					!$(this).hasClass("extend") ? _next.show():_next.hide();
				}
			});
			initSound();
			$("a.imListSet").attr("title","设置");
			$("a.imListMini").attr("title","最小化");
			$("a.imListClose").attr("title","关闭");
			$(".imContainer a").live("focus",function(){this.blur();});
		};
		init();

		function initSound(){
			if ($("[name='imPlayer']").size() == 0){
				if (getSound()==1)
					{
					/*if (-1 != navigator.userAgent.indexOf("MSIE")){
						//$("body").append('<object id="imPlayer" classid="clsid:6BF52A52-394A-11d3-B153-00C04F79FAA6" width=0 height=0 style="overflow:hidden;visibility:hidden;"><param name="URL" value="'+miscpath+ 'flash/msg.wav"/><param name="AutoStart" value="false"/></object>');
					}else{
						//$("body").append('<object id="imPlayer" type="application/x-ms-wmp" autostart="false" src="'+miscpath+ 'flash/msg.wav" width=0 height=0  style="overflow:hidden;visibility:hidden;"></object>');
					}*/					
					imsound = new js4swf();
					$("body").append("<span id='sp_imSound'></span>");
					imsound.AC_FL_RunContent({
						'appendTo' :$("#sp_imSound")[0], //flash添加到页面的容器
						'allowScriptAccess' : "always",
						'movie' : miscpath+"flash/playsound.swf", //该swf的地址
						'id' : 'imPlayer',
						'name' : 'imPlayer',
						'height':"1",
						'width':"1",
						'wmode' : 'opaque' //默认window
					});
				}
			}
		}
		function playSound(){
			//initSound()
			if ($("[name='imPlayer']").size()>0){
				setTimeout(function(){
					if (imsound.thisMovie('imPlayer').onPlay){
						imsound.thisMovie('imPlayer').onPlay();
					}
				},100)
			}
		}
		//initTree();
		/*
		var init=function(){
			$.ajax({
					dataType:settings.ajaxType,
					data:{uid:settings.UID},
					url:settings.onlineCountUrl,
					type:settings.ajaxMethodType,
					cache:false,
					success:function(result){
						if(result){
							if(result.state==1){
								var len=result.data.length;
								var data=result.data;
								$("#TreeTitle").find("strong").html(data.count||0);
							}else
								alert(result.msg);
						}
					},
					error:alertError
				})//.always(initTree);
		};
		init();*/
		if(imFriendList.css('display')==='block'){
			//$(document).one("click",function(e){e.stopPropagation();imFriendList.hide();return false;});
		}
		jqImContent.find("a.imListMini").click(function(){
			var right = $(this).closest("div.imFriendList").css("right");
			$(this).closest("div.imFriendList").css("position","absolute").data('right',right).animate({right:-$(this).width()},function(){$(this).hide();surveyLocation()});			
			resetSearch($("[name='txt_imSearch']"),true);
			return false;
		});
		
		var tip = new Tip({obj:$(".imListSet",".imFriendList"),content:'<div><a class="rl" href="javascript:void(0);" name="openSound">开启</a>收到新消息时播放提示音</div><div><a class="rl" href="javascript:void(0);" name="openIM">关闭</a>实时聊天功能已开启</div>',arrowPos:130});
		//声音
		$(".imTIP [name='openSound']").click(function(){
			var _this =this;
			if ($(this).html()=="开启"){
				$(_this).html("关闭");
				 //$.cookie("openSound",1,{expires:365});
				 setSound(1,initSound,null,0,settings.UID);
				 //initSound();
			}else{
				 //$.cookie("openSound",0,{expires:365});
				 setSound(0,function(){
				$(_this).html("开启");},function(){alert("设置出错");},0,settings.UID);
			}
		});
		//启用聊天功能
		$(".imTIP [name='openIM']").click(function(){
			var _this=this;
			if ($(this).html()=="开启"){
				//$.cookie("openIM",1,{expires:365});
				setIM(1,function(){
					$(_this).html("关闭");
					$("#TreeTitle").find(".imTitle").css("visibility","visible").show();
					$("#TreeTitle").height("auto").css({"margin-top":"auto"});
				},function(){
					alert("设置出错！");
				},0,settings.UID);
			}else{
				//$.cookie("openIM",0,{expires:365});
				setIM(0,function(){						
					$(_this).html("开启");
					$(_this).closest("div.imTIP").hide();
					$(".imFriendList,.imHistory").hide();
					$(".imListClose",imDialog).click();
					var jqStartIM=$('<a class="startIM"><span class="friend"></span>开启聊天模式</a>');				
					$("#TreeTitle").find(".imTitle").css("visibility","hidden").hide();
					$("#TreeTitle").height(182).css({"margin-top":"210px"});
					$("#TreeTitle").append(jqStartIM);
					$("#TreeTitle .startIM").click(startIM);
				},function(){
					alert("设置出错！");
				},0,settings.UID);
			}
		});
		if (getSound()=="1"){
			$(".imTIP [name='openSound']").html("关闭");
		}else{
			$(".imTIP [name='openSound']").html("开启");
		}
		if (getIM()=="1"){
			$(".imTIP [name='openIM']").html("关闭");
		}else if (getIM()=="0"){
			$(".imTIP [name='openIM']").html("开启");
			$(".imFriendList,.imHistory").hide();
			var jqStartIM=$('<a class="startIM"><span class="friend"></span>开启聊天模式</a>');			
			$("#TreeTitle").append(jqStartIM);
			$("#TreeTitle .startIM").click(startIM);
		};
		function startIM(e){
			$("#imContent .friendTree").css("bottom","0")
			$("#TreeTitle").find(".imTitle").css("visibility","visible").removeClass("active").show();
			$(this).remove();
			$(".imTIP [name='openIM']").click();
			e.stopPropagation();
		};
		$("#TreeTitle",jqImContent).click(function(e){
			if ($(this).next(":visible").size()>0){
				$(".friendTree .imListMini").click();
			}else
			$(this).next().show().animate({right:$(".friendTree").outerWidth()});
			surveyLocation();
		});
		$("div.imListFoot i").click(function(){
			resetSearch($("[name='txt_imSearch']"),true);
			$(this).hide();
		});
		jqImContent.bind('click',function(e){
				e.stopPropagation();
				$('#imFace').hide();
				$("#imChooseBtn").next().hide();
				return false;
		});
		jqImContent.bind('mousedown',function(e){$(".imTIP").hide();e.stopPropagation();});
		jqImContent.find("div.TreeTitle").click(function(e){
			if($(this).filter('[rel]').length>0){
				var leftLiArr=imDialog.find("div.imListLeftBar li");
				if(leftLiArr.filter(".active").length==0){
					leftLiArr.first().trigger('click');
				}	
				leftLiArr.filter(".active").trigger('click');		
			}
			$(".imTitle",this).removeClass("imSplashNewMsg");
			$(this).next().show();	
			e.stopPropagation();
			$(document).bind("mousedown",function(e){
				if(e.which==1){
					resetSearch($("[name='txt_imSearch']"),true);
					imFriendList.hide();_history.hide(); surveyLocation();
					//e.stopPropagation();return false;
				}
			});
			
			return false;
		});
		//搜索
		$("[name='txt_imSearch']").focus(function(){
			$(this).attr("tip")==$(this).val()?$(this).val(''):"";	
			timeChange=window.setInterval(change,100);
		}).blur(function(){
			if($.trim($(this).val())==""){
				resetSearch($(this),true);
			}
			window.clearInterval(timeChange);
		});
		//重置搜索
		function resetSearch(searchTextBox,isResetValue){
			if(isResetValue){
				searchTextBox.val(searchTextBox.attr("tip"));
				searchTextBox.blur();
			}
			$('[name="imSearchInfo"]',jqImContent).hide().css("visibility","hidden");
			jqImContent.find("div.imGroup").show();	
			tempValue='';
			$("div.imListFoot i").hide();
		}
			
		//文本改变搜索
		function change(){
			var docSearchInput=$("[name='txt_imSearch']");
			var keyName=docSearchInput.val();			
			if($.trim(docSearchInput.val())==""){
				resetSearch(docSearchInput,false);				
				change.cache ="";
				return;
			}
			if (!/^[A-Za-z\u4e00-\u9fa5]+$/.test(keyName)&&$.trim(docSearchInput.val())!=""){
				docSearchInput.val(change.cache);
				return;
			}
			change.cache = keyName ;
			if(keyName.length==0&& keyName!=tempValue){				
				resetSearch(docSearchInput,false);
				$("div.imListFoot .imListClose").hide();
			}
			if(keyName.length>0 && keyName!=tempValue){
				var resultContent=$('[name="imSearchInfo"]');
				jqImContent.find("div.imGroup").hide();
				var searchResult=resultContent.find("ul");
				$("div.imListFoot .imListClose").show().css({position:"absolute",top:5,right:5});
				searchResult.html("");		
				$.ajax({
					dataType:settings.ajaxType,
					data:{searchKey:keyName,userId:settings.UID},
					url:settings.searchUrl,
					cache:false,
					type:'POST',
					success:function(result){
						if(result){
							var len=result.length;
							var data=result;
							var TempArry=[];
							for(var i=0;i<len;i++){
								var temp=data[i];
								var isOnline=temp.status==1?'':' class="offline" ';
								var name=temp.username.replace(keyName,"<em>"+keyName+"</em>");
								TempArry.push('<li rel="'+temp.uid+'"><a href="javascript:;" '+isOnline+'><img src="'+temp.head+'" alt="'+temp.username+'"/><span class="imName">'+name+'</span></a></li>');
							}
							resultContent.height(32*len >330?330:32*len);
							searchResult.html(TempArry.join(''));
							searchResult.find("li").bind('click',openTabChat);
						}else{
							searchResult.html('<li class="imError">没有找到符合搜索条件的联系人</li>');
						}
					},
					error:function(){
						searchResult.html('<li class="imError">没有找到符合搜索条件的联系人</li>');
					}
				});
				resultContent.show().css("visibility","visible");
				tempValue = keyName;
			}	
		}
		//打开群聊天
		window.showGroupChat = function(roomID){
			var item = $("ul.imGroup[groupId='3'] li").filter("[rel='"+roomID+"']");
			if (item.size()){
				item.trigger("click");
			}else{
				alert("没有找到该组相关信息，请尝试刷新后重试!");
			}
		}
		// 对话框(选项卡)
		function openTabChat(){
			var _thisRel = $(this).attr("rel");
			var _name = $(this).text();
			var list = jqImContent.find("div.imListLeftBar li");
			var ishaveThis=false;//是否已存在
			var currentIndex=-1;
			for (var i =0;i<list.length;i++){
				if(list.eq(i).attr("rel")==_thisRel){
					ishaveThis=true;
					currentIndex=i;
					break;
				}
			}
			if(ishaveThis){					
				showChatContent(list.eq(currentIndex));
			}else{
			//创建	
				var tempLi= $(this).clone(true);
				jqImContent.find("div.imListLeftBar ul").append(tempLi);
				var tempI=document.createElement("i");
				tempI.innerHTML='<img src="'+miscpath+'img/system/IconClose.png" width="9px" height="9px"/>';
				tempLi.append(tempI);
				$(tempI).hover(function(){
					$(this).toggleClass('Xhover');
				}).click(function(e){
					deleteItem(tempLi);//删除项
					e.stopPropagation();
				});
				tempLi.hover(function(){
						$(this).find("i").show();
					},function(){					
						$(this).find("i").hide();
				});		
				$("#imChatContent ul").hide();
				//聊天内容
				$("#imChatContent").append('<ul id="'+_thisRel+'"></ul>');
				showChatContent(tempLi);
				
				//请求最近三条消息
				if(settings.isGetLastMsg){
					$.ajax({
							dataType:settings.ajaxType,
							data:{uid:settings.UID},
							url:settings.lastMsgUrl,
							cache:false,
							success:function(result){
								if(result){
									if(result.state==1){
										var len=result.data.length;
										var data=result.data;
										var TempArry=[];
										for(var i=0;i<len;i++){
											var temp=data[i];
											var className=temp.uid==settings.UID?"imGreenFont":"";
											TempArry.push('<li rel="'+temp.uid+'" class="'+className+'">');
											TempArry.push(temp.user+' '+temp.time+'<p>');
											TempArry.push(temp.msg);
											TempArry.push('</p></li>');
										}
										$("#"+tempLi.attr("rel")).html(TempArry.join(''));
									}else
										alert(result.msg);
								}
							},
							error:alertError
					});
				}
			}
			if ($("#imDialog:visible").length==0){
				$("#imDialog").show().animate({right:210});
				surveyLocation();
			}
			checkMsg();
			return false;
		}
		//显示聊天内容窗
		function showChatContent(curTab){
			//hideChatContent(curTab.closest("div.imTabList").find("div.imFriendList:visible"));
			jqImContent.find("div.imTabList").show();
			jqImContent.find("div.imTabList").find("a.imTitle").height(22).width(23);
			curTab.siblings(".active").removeClass("active").removeClass("imSplashSendMsg");
			curTab.addClass("active");
			//imFriendList.show();
			var imDialog=$("#imDialog");
			//标题
			var TabContent=jqImContent.find("div.imTabList");
			TabContent.attr("rel",curTab.attr("rel"));
			var curName=curTab.text().substring(0,6);
			//TabContent.find('#imCurrentTips').html('正与 <em>'+curName+'</em> 聊天').parent().removeClass('imSplashNewMsg');	
			TabContent.find('#imCurrentTips').html('');
			imDialog.find('h3').html(curTab.text());
			if(curTab.find("a").hasClass("offline")){
				$("div.imChatContentTips",imDialog).show();			
				$("#imChatContent").height('200px');
			}else{
				$("div.imChatContentTips",imDialog).hide();				
				$("#imChatContent").height('220px');
			}
			//大小
			var len=imDialog.find("div.imListLeftBar li").length;	
			//	alert(len);
			if(len==1){
				imDialog.removeClass("imFriendListBig");
				imDialog.find('div.imListBody').hide();
			}else{
				imDialog.find('div.imListBody').show().css("visibility","visible");
				imDialog.addClass('imFriendListBig');
			}
			$("#imChatContent").find("ul:visible").hide();
			var chatContent=$("#imChatContent").find("#"+curTab.attr("rel"));
			chatContent.show();			
			var D_value=chatContent.height() - $("#imChatContent").height();
			if(D_value>0){
				$("#imChatContent").scrollTop(D_value+30);
			}
			//alert(imDialog.find('div.imListBody').css('display'))
			//curTab.closest("div.imTabInfo").addClass("imToggleOpen");
			//****获取会话ID，绑定聊天记录链接***********/	
			/*
			$.ajax({
						dataType:settings.ajaxType,
						data:{uid:settings.UID,touid:curTab.attr("rel")},
						url:settings.sessionUrl,
						cache:true,
						success:function(result){
							if(result){
								if(result.state==1){
									$("a.imChatRecord",imDialog).attr("href",settings.historyUrl+result.sessionId);
								}else
									$("a.imChatRecord",imDialog).attr("href",settings.historyUrl);
							}else{
								$("a.imChatRecord",imDialog).attr("href",settings.historyUrl);
							}
							$("a.imChatRecord").click(function(){
								location.href=$(this).attr("href");
							});
						},
						error:function(){
							$("a.imChatRecord",imDialog).attr("href",settings.historyUrl);
						}
			});*/
			if (curTab.attr("isgroup")=="1"){
				$("a.imChatRecord",imDialog).hide();
			}else{
				$("a.imChatRecord",imDialog).show();
			}
			if (_history.filter(":visible").size()){
				goPager(curTab);
			}
			$("a.imChatRecord",imDialog).unbind('click');
			$("a.imChatRecord",imDialog).bind('click',function(){
				if (_history.filter(":visible").size()){
					_history.hide();
				}else{
					_history.show();
					goPager(curTab);
				}
				$("#TreeTitle").next().hide();
				surveyLocation();
				return false;
			});
			return false;
		}
		//聊天记录
		function goPager(curTab){
			var page= new Pager({
						count:44,
						pageSize: 20,
						content:$("div.imChatInfo",_history),
						url:settings.historyUrl+"?fromUid="+settings.UID+"&toUid="+curTab.attr("rel"),
						returnFunc:initHistory
					});
			page.init({currentIndex:1});
		}
		//格式化聊天记录
		function initHistory(data){
			var arr = data.list;
			var historyContent = $("div.imHistoryContent",_history);
			var _html=[];
			var uid = settings.UID;
			if (data.count==0){
				_html.push("");
			}else
			for (var i = 0,len=arr.length; i<len ;i++ ){
				var item = arr[i];
				var style = "historyOtherMsg";
				if (uid==item.fromUid){
					style = "historyMyMsg";
				}
				_html.push('<div class="'+style+'">'+item.fromUsername+'<span>'+item.date+'</span></div><div class="imMsg">'+converFace(item.msg)+'</div>');
			}
			historyContent.html('').append(_html.join(''))
		}
		//删除项
		function deleteItem(tempLi){
			var imDialog=$("#imDialog");
			var tempNext = tempLi.next();
			var activeObj;
			if(tempNext.length==1){
				activeObj=tempNext;
			}else{
				activeObj=tempLi.prev();
			}
			if(activeObj.length==1){
				activeObj.trigger('click');
			}else{
				jqImContent.find("div.imTabList").hide();
				imDialog.find('.imListBody').hide().css("visibility","hidden");
			}	
			if(tempLi.siblings().length==1){
				imDialog.find('.imListBody').css("visibility","hidden").hide();
				imDialog.removeClass("imFriendListBig");
			}
			tempLi.remove();
			return false;
		}
		//隐藏内容窗
		function hideChatContent(curTab){
			curTab.hide();		
			curTab.closest("div.imToggleOpen").removeClass("imToggleOpen");	
			return false;
		}
		$("a.imListClose",imDialog).click(closeChat);
		$("a.imListClose",_history).click(function(){
			_history.hide();
			surveyLocation();
			return false;
		});
		$("div.imChatContentTips img",imDialog).click(function(){
			$(this).parent().hide();		
			$("#imChatContent").height('228px');
		});
		//关闭聊天窗
		function closeChat(e){
			var imDialog=$("#imDialog");
			if (imDialog.find("div.imListLeftBar li").size()>1){
				if (!confirm("当前窗口有多个会话，确定全部关闭吗？")){
					return ;
				}
			}
			if ($("#TreeTitle").next(".imFriendList:visible").size()==0){
				$("#TreeTitle .imTitle").removeClass("active");
			}
			imDialog.find("div.imListLeftBar li").remove();
			imDialog.find("#imChatContent *").remove();
			jqImContent.find("div.imTabList").hide();
			e.stopPropagation();
			imDialog.find('.imListBody').css("visibility","hidden").hide();
			imDialog.removeClass("imFriendListBig");
			imDialog.hide();
			$("#imCurrentTips i").html("0");
			_history.hide();
			return false;
		}
		//向上移动
		imDialog.find("a.imListUp").bind("click",function(){
			var ul=imDialog.find("div.imListLeftBar ul");
			var top=parseInt(ul.css("top"));
			top+=30;
			if(top>0){
				top=0;	
			}
			ul.css({top:top});
			$(this).removeClass('imSplashSendMsg');
			checkMsg();
			return false;
		});
		//向下移动
		imDialog.find("a.imListDown").bind("click",function(){
			var ul=imDialog.find("div.imListLeftBar ul");
			var top=parseInt(ul.css("top"));
			var ulHeight=parseInt(ul.height());
			var leftBarHeight=parseInt(imDialog.find("div.imListLeftBar").height());
			top-=30;
			if(ulHeight+top<=leftBarHeight){
				top=leftBarHeight-ulHeight;	
			}
			imDialog.find("div.imListLeftBar ul").css({top:top});
			$(this).removeClass('imSplashSendMsg');
			checkMsg();
			return false;
		});
		function checkMsg(){
			if (imDialog.find("div.imListLeftBar ul .imSplashSendMsg").first().size()>0){
				var _top = parseInt( imDialog.find("div.imListLeftBar ul .imSplashSendMsg").first().position().top) +parseInt($("#imDialog div.imListBody div.imListLeftBar ul").css("top"));
				var _top2 = parseInt( imDialog.find("div.imListLeftBar ul .imSplashSendMsg").last().position().top) +parseInt($("#imDialog div.imListBody div.imListLeftBar ul").css("top"));
				if(_top<0){
					$("a.imListUp").addClass("imSplashSendMsg");
				}
				if(_top2>316){
					$("a.imListDown").addClass("imSplashSendMsg");
				}
			}else{
				$("a.imListUp").removeClass("imSplashSendMsg");
			}
		}
		//快捷发送
		$("#imChooseBtn").click(function(e){
			$(this).next().toggle();
			e.stopPropagation();
			setChooseEnter();
		});
		function setChooseEnter(){
			//if ($(window).width()-$(".imChooseEnter").offset().left<$(".imChooseEnter").width()){
				$(".imChooseEnter").css({left:145});
			//}else{
				//$(".imChooseEnter").css({left:304});
			//}
		}
		imDialog.find("a.imSendBtn").click(function(){
			sendMsg($('#imInputMsg'));
		});
		jqImContent.find("ul.imChooseEnter a").click(function(){
			var chooseUl=$(this).closest("ul");
			chooseUl.find(".selected").removeClass("selected");
			$(this).prev("span").addClass("selected");
			chooseUl.hide();
			setShortcutsSendKey($(this).prev("span").parent().index());
			return false;
		});
		//表情
		$('#imFace').click(function(e){
			e.stopPropagation();	
		});
		$("#imFaceSelect").bind('click',function(e){
			var imFace=$('#imFace');
			var ul=imFace.find("#imfaceList");
			if(!ul.html()){
				var len=faceArr.length;
				var face=[];
				for(var i=0;i<len;i++){
					var alt = faceArr[i]||"";
					face.push('<li><a href="javascript:;"><span src="'+miscpath+'img/system/face/'+alt+'.gif" alt="'+ alt +'"></span></a></li>');
				}
				ul.html(face.join(''));
				ul.find(">li span").bind('click',function(){
					var inputMsg=$('#imInputMsg');
					addOnPos(inputMsg[0],"[" + $(this).attr("alt")+"]");
					inputMsg.focus();
					imFace.hide();	
				});
			}
			if(imFace.css('display')==='block'){
				imFace.hide();	
			}else{
				imFace.show();
				e.stopPropagation();
				$(document).one("click",function(){imFace.hide();return false;});
			}
			return false;
		});
		//隐藏表情
		$("#imCloseFace").bind("click",function(){
			$('#imFace').hide();	
		});
		//设置发送键
		$('#imInputMsg').keydown(function(e){
			var index = $("#imChooseBtn").next().find("span.selected").parent().index();
			if(index==1){	
				if(e.ctrlKey&&e.keyCode==13){
					//alert('ctrl+enter')
					sendMsg($(this));
					return false;
				}		
			}else
				if(e.keyCode==13&&!e.ctrlKey){
					sendMsg($(this));
					return false;
				}else if(e.ctrlKey&&e.keyCode==13){
					var self=$(this);
					$(this).val(($(this).val()+"\r\n"));
					setCaretPosition($(this)[0]);
					return false;
				}
		}).keyup(computeWordLength).focus(computeWordLength);
		//设置光标
		function setCaretPosition(elem) {
            var caretPos = elem.value.length;
            if (elem != null) {
                if (elem.createTextRange) {
                    var range = elem.createTextRange();
                    range.move('character', caretPos);
                    range.select();
                }
                else {
                    elem.setSelectionRange(caretPos, caretPos);
                    elem.focus();

                    //空格键
                    var evt = document.createEvent("KeyboardEvent");
                    evt.initKeyEvent("keypress", true, true, null, false, false, false, false, 0, 32);
                    elem.dispatchEvent(evt);
                    // 退格键
                    evt = document.createEvent("KeyboardEvent");
                    evt.initKeyEvent("keypress", true, true, null, false, false, false, false, 8, 0);
                    elem.dispatchEvent(evt);
                }
            }
        }
		//计算长度
		function computeWordLength(){			
			var wordLength = $('#imInputMsg').val().len();
			var imWordCount = $('#imWordCount');
			var half=parseInt(wordLength/2);

			var curCount=200- parseInt(wordLength%2?half+1:half);			
			imWordCount.html(curCount);
			if(curCount<0||wordLength==0){
				imWordCount.addClass("tcRed");
				return false;
			}else{
				imWordCount.removeClass("tcRed");
				return true;
			}
		}
		//发送消息
		function sendMsg(msgTextBox){
			var msg=msgTextBox.val();
			var activeItem=$("div.imListLeftBar li.active",imDialog);
			var curID=activeItem.attr("rel");
			var curImgUrl=$("img",activeItem).attr("src");
			if(computeWordLength()){
				//ajax
				if($("#imInputMsg").attr("isSending")!=1){
					if (activeItem.attr("isgroup")=="1"){	
						//alert("发送群消息!");
						$.ajax({
							url:settings.sendGroupUrl,
							dataType:settings.ajaxType,
							cache:false,
							type:"POST",
							data:{fromUid:settings.UID,roomId:curID,msg:encodeURIComponent(msg)},
							success:function(result){
								if (result){
									msgTextBox.val("");	
									var TempStr='';
									var temp={};
									temp.fromid = settings.UID;
									temp.fromname = settings.userName;
									temp.msg = msg;
									temp.date = result.date;
									addMsg(curID,temp);	
									$("#imWordCount").html("200");
									//addLately(curID,{uid:curID,user:$("#imDialog").find("h3").html(),imgUrl:curImgUrl,offline:activeItem.children("a").hasClass("offline")});//添加最近联系人,2012.2.2 by 田想兵

								}else
									alert("发送失败");
							},
							timeout:3000,
							error:function(){
								alert('超时，请稍后重试！');
							},
							complete:function(){
								$("#imInputMsg").attr("isSending",0)
							}
						});
					}else
					var xhr = $.ajax({
						dataType:settings.ajaxType,
						data:{msg:encodeURIComponent(msg),toUid:curID,fromUid:settings.UID,toUsername:encodeURIComponent($("#imDialog").find("h3").html()),fromUsername:encodeURIComponent(settings.userName)},
						url:settings.sendUrl,
						cache:false,
						type:"POST",
						success:function(result){
							if(result.success){
								msgTextBox.val("");	
								var TempStr='';
								var temp={};
								temp.fromid = settings.UID;
								temp.fromname = settings.userName;
								temp.msg = msg;
								temp.date = result.date;
								addMsg(curID,temp);	
								$("#imWordCount").html("200");
								addLately(curID,{uid:curID,user:$("#imDialog").find("h3").html(),imgUrl:curImgUrl,offline:activeItem.children("a").hasClass("offline")});//添加最近联系人,2012.2.2 by 田想兵
							}else
							{
								alert("发送失败!");
							}
						},
						timeout:3000,
						error:function(){
							alert('超时，请稍后重试！');
						},
						complete:function(){
							$("#imInputMsg").attr("isSending",0)
						}
					});

				}
				$("#imInputMsg").attr("isSending",1)
				/*
				xhr.then(function(){
					$.ajax({
							dataType:settings.ajaxType,
							data:{uid:settings.UID},
							url:settings.recciveUrl,
							cache:false,
							type:settings.ajaxMethodType,
							success:function(result){
								if(result.state==1){
									alert(result.data);
								}else{
									alert(result.msg);
								}
							}
					}).fail(alertError);
				},alertError);
				*/
			}else{
				splashScreen(msgTextBox);	
			}	
		}
		//闪屏
		function splashScreen(msgTextBox,color,stopColor){
			var ii=0;
			var tto;
			var color=color||'imSplashSendMsg';
			var stopColor=stopColor||'';
			function splash(){
				tto=setInterval(function(){
					msgTextBox.toggleClass(color);
					ii++;
					//console.log(msgTextBox.css('background-color'));
					if(ii>=4){
						clearInterval(tto); 
						msgTextBox.addClass(stopColor);
					}
				},200);
			}
			splash();
			//msgTextBox.css({'background-color':'rgb(225,221,221)'});
			/*setTimeout(function(){
				msgTextBox.css({'background-color':'#fff'});
			},300);*/
		}
		function alertError(err){
			//if(err.readyState==4||err.readyState==2)
			//alert("出错，请刷新后重试！");
			//console.error(err);
		}
		///添加消息到内容
		function addMsg(relId,temp){
			var className=temp.fromid==settings.UID?"imGreenFont":"";
			var TempStr='';
			if(temp.fromid!=settings.UID){
				TempStr+='<li rel="'+temp.fromid+'" class="'+className+'">';
				TempStr+=temp.fromname+' '+temp.date+'<p>';
				TempStr+=converFace(temp.msg);
				TempStr+='</p></li>';
			}else{
				TempStr+='<li rel="'+temp.uid+'" class="'+className+'">';
				TempStr+=settings.userName+' '+temp.date+'<p>';
				TempStr+=converFace(temp.msg);
				TempStr+='</p></li>';
			}
			var tempUl = $("div.imChatContent ul",imDialog).filter("#"+relId);
			tempUl.append(TempStr);
			var imChatContent=$("#imChatContent").parent();
			var D_value=tempUl.height() - imChatContent.height();
			if(D_value>0){
				$("#imChatContent").scrollTop(D_value+30);
			}
		}
		//表情转换
		function converFace(msg){
			var len=faceArr.length;
			msg = htmlEncode(msg);
			msg=msg.replace(/\n/g,"<br/>").replace(/\s/g,"&nbsp;");
			//msg=msg.replace(/((?:https?|ftp):[\w\.\/\?&=]+)/ig,"$1".link("$1"));
			var re = new RegExp("((((ht|f)tp(s?))\://)?(www.|[a-zA-Z].)[a-zA-Z0-9\-\.]+\.(com|edu|gov|mil|net|org|biz|info|name|museum|us|ca|uk)(\:[0-9]+)*(/($|[a-zA-Z0-9\.\,\;\?\'\\\+&amp;%\$#\=~_\-]+))*)","igm");
			msg = msg.replace(re,"$1".link("$1"));
			for(var i =0;i<len;i++){
				var alt=faceArr[i];
				var temp= new RegExp("\\["+alt+"\\]","g");
				msg=msg.replace(temp,'<img src="'+miscpath+'img/system/face/'+(i+1)+'.gif" alt="['+ alt +']"/>');
			}			
			return msg;	
		}
		function htmlEncode(str){/*
			var div= document.createElement("div");
			div.appendChild(document.createTextNode(str));
			return div.innerHTML;
			var s = "";
			if (str.length == 0 ){
				return "";
			}
			s=str.replace(/&/g,"&amp;");
			s=str.replace(/</g,"&lt;");
			s=str.replace(/>/g,"&gt;");
			s=str.replace(/ /g,"&nbsp;");
			s=str.replace(/\'/g,"&#39;");
			s=str.replace(/\"/g,"&quot;");
			s=str.replace(/\n/g,"<br/>");
			return s;*/
			return str.replace(/</g,"&lt;").replace(/>/g,"&gt;");
		}
		//接收消息		
		window.processMsg=function(data){
			playSound();
			var data=eval("("+data+")");
			processMsg.call(_self,data);
		}
		//processMsg([{"fromid":"111100000","fromname":"hello","msg":"\u9ad8\u4f1f","date":"2012-12-10"}]);
		//window.receviceMsg('{"fromUid":"111100000","fromUsername":"hello","roomId":"111100001","msg":"\u9ad8\u4f1f"}');
		//receviceMsg("{\"date\":\"15:01:35\",\"fromUid\":\"1000002982\",\"fromUsername\":\"\u7530\u5C0F\u5175\",\"id\":null,\"msg\":\"asdad\",\"roomId\":\"103568136851010\",\"roomNick\":\"\u6D4B\u8BD5\u7F51\u9875\",\"status\":0,\"toUid\":\"\",\"toUsername\":\"\",\"type\":\"\"}");
		window.receviceMsg = function(data){
			playSound();
			var data=eval("("+data+")");
			data.fromUid = data.roomId ;
			data.fromname = data.fromUsername;
			processMsg.call(_self,[data],1);
		};
		//接收列表
		function receiveList(data){
			var receiveContent = $("#receiveList");
			if ( receiveContent.size() > 0 ){
				for ( var i=0,len=data.length; i<len ;i++ ){
					
				}
			}else{
				$("#imCurrentTips").append('<div id="receiveList"></div>');
				arguments.callee(data);
			}
		};
		function processMsg(result,isgroup){				
				var data=result;
				var len=data.length;
				if(data && len>0){
					var imCurrentTips=$("#imCurrentTips");
					var hasNum= parseInt($("i",imCurrentTips).html())||0;
					var activeItem=$("div.imListLeftBar li.active",imDialog);
					if(len>0 && $("#imDialog:visible").size()==0 ){
						var count= hasNum+len;
						imCurrentTips.html("<i>" +count+"</i>");
						splashScreen(imCurrentTips.parent(),'imSplashNewMsg','imSplashNewMsg');
						surveyLocation();
					}
					for(var i=0;i<len;i++){
						data[i].fromid=data[i].fromUid;
						data[i].fromname=data[i].fromUsername;
						var temp=data[i];									
						var tempLi=$("div.imListLeftBar li",imDialog).filter("[rel='"+temp.fromid+"']");
						splashScreen(tempLi,'imSplashSendMsg','imSplashSendMsg');
						if(tempLi.length==0){
							//创建(查找右侧好友对应的信息)
							var tempFriend = $("div.imListBody li",imFriendList).filter("[rel='"+temp.fromid+"']");
							temp.imgUrl = tempFriend.find("img").attr("src");
							temp.user= temp.fromname;
							var groupStr = isgroup ? 'isgroup="1"':"";
							var curItem="";
							if (isgroup){
								var name=temp.roomNick;//$("ul[groupid='3']>li[rel='"+temp.fromUid+"']").text();
								curItem=$('<li rel="'+temp.fromid+'" '+groupStr+'><a href="javascript:;"><span class="imName">'+name+'</span></a></li>');
							}else
							curItem=$('<li rel="'+temp.fromid+'" '+groupStr+'><a href="javascript:;"><img src="'+temp.imgUrl+'" alt="'+temp.user+'"/><span class="imName">'+temp.user+'</span></a></li>');
							var tempI=document.createElement("i");
							tempI.innerHTML='<img src="'+miscpath+'img/system/IconClose.png" width="9px" height="9px"/>';	
							curItem.append(tempI);															
							$(tempI).hover(function(){
								$(this).toggleClass('Xhover');
							}).click(function(e){
								deleteItem($(this).parent());//删除项
								e.stopPropagation();
							});																
							curItem.hover(function(){
									$(this).find("i").show();
								},function(){					
									$(this).find("i").hide();
							});	
							curItem.bind('click',openTabChat);
							splashScreen(curItem,'imSplashSendMsg','imSplashSendMsg');
							curItem.appendTo("div.imListLeftBar ul");
							$("#imChatContent").append('<ul id="'+temp.fromid+'"></ul>');
							//	alert(len);
							if($("div.imListLeftBar li","#imDialog").length>1){
								imDialog.addClass("imFriendListBig");
								imDialog.find('div.imListBody').show().css("visibility","visible");
							}	
							if(imDialog.filter(":visible").length>0){
								///是否可见闪屏
								var _top = parseInt( curItem.position().top) +parseInt($("#imDialog div.imListBody div.imListLeftBar ul").css("top"));
								if(_top<0){
									splashScreen($("a.imListUp"),'imSplashSendMsg','imSplashSendMsg');
								}
								if(_top>316){
									splashScreen($("a.imListDown"),'imSplashSendMsg','imSplashSendMsg');
								}
							}
						}else{	
							if(imDialog.filter(":visible").length>0){									
								///是否可见闪屏
								var _top = parseInt( tempLi.position().top) +parseInt($("#imDialog div.imListBody div.imListLeftBar ul").css("top"));
								if(_top<0){
									splashScreen($("a.imListUp"),'imSplashSendMsg','imSplashSendMsg');
								}
								if(_top>316){
									splashScreen($("a.imListDown"),'imSplashSendMsg','imSplashSendMsg');
								}
							}
						}
						addMsg(temp.fromid,temp);
						if (!temp.roomId){
							addLately(temp.fromUid,null,$(".imFriendGroup li").filter("[rel='"+temp.fromUid+"']").first());//添加最近联系人,2012.8.1 by 田想兵
						}
					}
					$("div.imTabList").show();
					//$("#"+tempLi.attr("rel")).html(TempArry.join(''));
				}else if(result.state==0)
					alert(result.msg);
		}
		function receiveMsg(){
			receiveMsg.time?clearTimeout(receiveMsg.time):null;
			$.ajax({
					dataType:settings.ajaxType,
					data:{uid:settings.UID},
					url:settings.recciveUrl,
					type:settings.ajaxMethodType,
					cache:false,
					timeout:10000,
					success:function(result){
						if(result){
							if(result.state==1&&result.data&&result.data.length>0){
								var len=result.data.length;
								var data=result.data;
								var imCurrentTips=$("#imCurrentTips");
								var hasNum= parseInt($("i",imCurrentTips).html())||0;
								var activeItem=$("div.imListLeftBar li.active",imDialog);
								if(len>0&&activeItem.attr("rel")!=data[0].uid){
									var count= hasNum+len;
									imCurrentTips.html("你有<i>" +count+"</i>条新消息");
									splashScreen(imCurrentTips.parent(),'imSplashNewMsg','imSplashNewMsg');
								}
								for(var i=0;i<len;i++){
									var temp=data[i];									
									var tempLi=$("div.imListLeftBar li",imDialog).filter("[rel='"+temp.uid+"']");
									splashScreen(tempLi,'imSplashSendMsg','imSplashSendMsg');
									if(tempLi.length==0){
										//创建
										var curItem=$('<li rel="'+temp.uid+'"><a href="javascript:;"><img src="'+temp.imgUrl+'" alt="'+temp.user+'"/><span class="imName">'+temp.user+'</span></a></li>');
										var tempI=document.createElement("i");
										tempI.innerHTML='<img src="'+miscpath+'img/system/IconClose.png" width="9px" height="9px"/>';	
										curItem.append(tempI);															
										$(tempI).hover(function(){
											$(this).toggleClass('Xhover');
										}).click(function(e){
											deleteItem($(this).parent());//删除项
											e.stopPropagation();
										});																
										curItem.hover(function(){
												$(this).find("i").show();
											},function(){					
												$(this).find("i").hide();
										});	
										curItem.bind('click',openTabChat);
										addLately(temp.uid,null,curItem);//添加最近联系人,2012.2.2 by 田想兵
										splashScreen(curItem,'imSplashSendMsg','imSplashSendMsg');
										curItem.appendTo("div.imListLeftBar ul");
										$("#imChatContent").append('<ul id="'+temp.uid+'"></ul>');
										//	alert(len);
										if($("div.imListLeftBar li","#imDialog").length>1){
											imDialog.addClass("imFriendListBig");
											imDialog.find('div.imListBody').show().css("visibility","visible");
										}	
										if(imDialog.filter(":visible").length>0){
											///是否可见闪屏
											var _top = parseInt( curItem.position().top) +parseInt($("#imDialog div.imListBody div.imListLeftBar ul").css("top"));
											if(_top<0){
												splashScreen($("a.imListUp"),'imSplashSendMsg','imSplashSendMsg');
											}
											if(_top>316){
												splashScreen($("a.imListDown"),'imSplashSendMsg','imSplashSendMsg');
											}
										}
									}else{										
										if(imDialog.filter(":visible").length>0){									
											///是否可见闪屏
											var _top = parseInt( tempLi.position().top) +parseInt($("#imDialog div.imListBody div.imListLeftBar ul").css("top"));
											if(_top<0){
												splashScreen($("a.imListUp"),'imSplashSendMsg','imSplashSendMsg');
											}
											if(_top>316){
												splashScreen($("a.imListDown"),'imSplashSendMsg','imSplashSendMsg');
											}
										}
									}
									addMsg(temp.uid,temp);
								}
								$("div.imTabList").show();
								//$("#"+tempLi.attr("rel")).html(TempArry.join(''));
							}else if(result.state==0)
								alert(result.msg);
						}
					}
				}).done(function(result){
					if(result.change){
						var arr = result.changeGroupId.toString().split(",")
						$.each(arr,function(i,d){	
							if(arr[i]!=""&&arr[i]!=null)							
							initGroupTree(arr[i]);
						});
					}
					}).always(function(){						
					receiveMsg.time=setTimeout(receiveMsg,settings.time);
				});
		};
		//更新组成员
		window.initGroup=function(data,style){
			var data = eval("("+data+")")
			//console.log(data)
			initGroup.call(_self,data,style);
		}
		window.pushGroup = function(data,style){
			var data = eval("("+data+")");
			if ( data.constructor ===Array){
				for ( var i=0,len=data.length; i<len ;i++ ){						
					data[i].type=3;
					data[i].uid = data[i].roomId;
					data[i].status =style;
					data[i].username = data[i].roomNm;
					data[i].head ="";
					data[i].isChat = 0;
					if (data[i].count){
						data[i].username += "("+data[i].count+")"
					}
				}
			}else{
				data.type=3;
				data.uid = data.roomId;
				data.status =style;
				data.username = data.roomNm;
				data.head ="";
				data.isChat = 0;
				if (data.count){
					data.username += "("+data.count+")"
				}
				data  = [data];
			}
			initGroup(data,style);
		}
		//*************测试数据********************//
		//window.joinGroup('{"groupid":"111100000","groupname":"im"}',1);
		//window.joinGroup('{"groupid":"111100001","groupname":"XP群"}',1);
		//window.initGroup('[{"type":2,"uid":10001,"status":1,"username":"test","head":"","isChat":0}]',1);
		function initGroup(data,style){
			var groupId=data[0].type;
			var groupContent=$("ul.imGroup[groupId='"+groupId+"']");
			groupContent.prev("h4").find('em').html(data.length);
			var TempArry=[];
			var listLen=data.length;
			if (listLen==1){
				var item = data[0];
				if (style == 2){
					$("ul[groupid='"+item.type+"']").find("li[rel='"+item.uid+"']").remove();
				}else{
					var isOnline=item.status==1?'':' class="offline" ';
					var userHtml = (' <li rel="'+item.uid+'" isChat="'+item.isChat+'"><a '+isOnline+' href="javascript:;"><img title="'+item.username+'" src="'+item.head+'" title="'+item.username+'" alt="'+item.username+'" onerror="this.src=\''+miscpath+'img/default/avatar_ss.gif\'"/><span class="imName" title="'+item.username+'">'+item.username+'</span></a></li>');
					if ($("ul[groupid='"+item.type+"']").find("li[rel='"+item.uid+"']").length>0){
						$("ul[groupid='"+item.type+"']").find("li[rel='"+item.uid+"']").remove();
					}
					if (item.type == 3){
						if (style){
							$("ul[groupid='"+item.type+"']").append(' <li isgroup="1" rel="'+item.uid+'" isChat="'+item.isChat+'"><a '+isOnline+' href="javascript:;"><span class="imName" title="'+item.username+'">'+item.username+'</span></a></li>');
						}
					}else
					if (item.status){
						if ( $("ul[groupid='"+item.type+"']").find("a:not(.offline)").last().parent().size()>0){
							$("ul[groupid='"+item.type+"']").find("a:not(.offline)").last().parent().after(userHtml);
						}else{
							$("ul[groupid='"+item.type+"']").prepend(userHtml);
						}
					}else{
						if ( $("ul[groupid='"+item.type+"']").find("a:not(.offline)").first().parent() .size() >0){
							//全在线
							if ($("ul[groupid='"+item.type+"']").find("a.offline").size()>0){
								$("ul[groupid='"+item.type+"']").find("a.offline").first().parent().before(userHtml);
							}else{
								$("ul[groupid='"+item.type+"']").append(userHtml);

							}
						}else{
							$("ul[groupid='"+item.type+"']").append(userHtml);
						}
					}				
					$("ul[groupid='"+item.type+"'] li").bind("click",openTabChat);	
				}
			}else{
				for(var j=0;j<listLen;j++){
					var item=data[j];
					var isOnline=item.status==1?'':' class="offline" ';
					/*if(item.status==2&&item.type!=2){
						$("ul[groupid='"+item.type+"']").find("li[rel='"+item.uid+"']").remove();
					}else if($("ul[groupid='"+item.type+"']").find("li[rel='"+item.uid+"']").length==0||style==2){*/
					if (style == 1){
						if (data[j].type == 3){
							/*
							if(item.status==2){
								$("ul[groupid='"+item.type+"']").find("li[rel='"+item.uid+"']").remove();
							}else				
							TempArry.push(' <li isgroup="1" rel="'+item.uid+'" isChat="'+item.isChat+'"><a '+isOnline+' href="javascript:;"><span class="imName" title="'+item.username+'">'+item.username+'</span></a></li>');
							*/
							if ($("ul[groupid='"+item.type+"']").find("li[rel='"+item.uid+"']").length>0){
								$("ul[groupid='"+item.type+"']").find("li[rel='"+item.uid+"']").remove();
							}else{
								TempArry.push(' <li isgroup="1" rel="'+item.uid+'" isChat="'+item.isChat+'"><a '+isOnline+' href="javascript:;"><span class="imName" title="'+item.username+'">'+item.username+'</span></a></li>');
							}
						}else{
							if ($("ul[groupid='"+item.type+"']").find("li[rel='"+item.uid+"']").length>0){
								$("ul[groupid='"+item.type+"']").find("li[rel='"+item.uid+"']").remove();
							}
							TempArry.push(' <li rel="'+item.uid+'" isChat="'+item.isChat+'"><a '+isOnline+' href="javascript:;"><img title="'+item.username+'" src="'+item.head+'" title="'+item.username+'" alt="'+item.username+'" onerror="this.src=\''+miscpath+'img/default/avatar_ss.gif\'"/><span class="imName" title="'+item.username+'">'+item.username+'</span></a></li>');
						}
					}else{
						$("ul[groupid='"+item.type+"']").find("li[rel='"+item.uid+"']").remove();
					}
					/*}*/
				}
			}
			var temp =$(TempArry.join(''));
			$(temp).bind("click",openTabChat);		
			$(temp).filter('li[isChat="1"]').each(function(){
				var isChat =$(this).attr("isChat");//是否满足聊天条件
				if (isChat){
					var notip = new Tip({obj:$(this),content:'<div><a class="rl" href="javascript:void(0);" name="closeTip"> X </a>您不满足与该用户聊天的条件</div>',arrowPos:130});
					$(this).unbind("click");
					$("a[name='closeTip']").click(function(){
						$(this).closest("div.imTIP").hide();
					});
				}
			});	
			if(style==1){
				groupContent.prepend(temp);
			}else{
				//groupContent.html(temp);
			}
			$("h4[groupId='"+groupId+"']").find("em").html(groupContent.find("li a").not(".offline").size()+"/"+groupContent.find("li").length);
			var count=0;
			/*
			$("div.imGroup",jqImContent).each(function(i,d){
				if(i!=0){
					count+= parseInt($(this).find('em').html()||0);
				}
			});
			*/
			var sum={};
			$("ul.imGroup li",jqImContent).each(function(i,d){
				if($(this).parent("ul").attr("groupId")!=2&&$(this).parent("ul").attr("groupId")!=3){
					sum[$(this).attr("rel")]=$(this);
				}
			});
			$.each(sum,function(){
				count++;
			})
			$("#TreeTitle strong").html(parseInt(count)||0);						
		}
		///针对某个组的成员刷新列表
		function initGroupTree(groupId){
			var xhr = $.ajax({
					dataType:settings.ajaxType,
					data:{uid:settings.UID,groupId:groupId},
					url:settings.friendGroupList,
					type:settings.ajaxMethodType,
					cache:false,
					error:alertError
				});
			xhr.done(function(result){
				if(result){
					if(result.state==1){
						var groupContent=$("ul.imGroup[groupId='"+groupId+"']");
						var data=result.data;
						groupContent.prev("h4").find('em').html(data.length);
						var TempArry=[];
						var listLen=data.length;
						for(var j=0;j<listLen;j++){
							var item=data[j];
							var isOnline=item.isOnline?'':' class="offline" ';
							TempArry.push(' <li rel="'+item.id+'"><a'+isOnline+' href="javascript:;"><img src="'+item.imgUrl+'" alt="'+item.name+'"/><span class="imName">'+item.name+'</span></a></li>');
						}	
						groupContent.html(TempArry.join(''));
						$("li",groupContent).bind("click",openTabChat);
						var count=0;
						$("div.imGroup",jqImContent).each(function(i,d){
                                                        if(i==1)
							count+= parseInt($(this).find('em').html()||0);
						});
						$("#TreeTitle strong").html(parseInt(count)||0);
						
					}
				}
			});
		}
		/*
		$(document).bind('click',function(){
			$('div.imFriendList').hide();
		});
		*/
		/*发送快捷键存cookie*/
		function getShortcutsSendKey(){			
			return $.cookie("sendKey")||0;
		}
		function setShortcutsSendKey(value){	
			 $.cookie("sendKey",value,{expires:365})
			var sendKey = getShortcutsSendKey();
		}
		//当前光标位置输入
		function addOnPos(myField,myValue){
			if(document.selection){
				myField.focus();
				var sel = document.selection.createRange();
				sel.text = myValue;
				sel.select();
			}else if(myField.selectionStart || myField.selectionStart == "0"){
				var startPos = myField.selectionStart;
				var endPos = myField.selectionEnd;
				var restoreTop = myField.scrollTop;
				myField.value = myField.value.substring( 0, startPos) + myValue + myField.value.substring(endPos,myField.value.length);
				if (restoreTop>0){
					myField.scrollTop = restoreTop;
				}
				myField.focus();
				myField.selectionStart = startPos + myValue.length;
				myField.selectionEnd = startPos + myValue.length;
			}else{
				myField.value += myValue;
				myField.focus();
			}
		}
		//添加最近联系人,2012.2.2 by 田想兵
		function addLately(uid,userInfo,curItem){
			if(!isHaveUser(2,uid)){
				if(!curItem){
					var offline =  userInfo.offline?"offline":"";
					curItem =$('<li rel="'+userInfo.uid+'"><a href="javascript:;" class="'+offline+'"><img src="'+userInfo.imgUrl+'" alt="'+userInfo.user+'"/><span class="imName">'+userInfo.user+'</span></a></li>');
					curItem.bind('click',openTabChat);
				}
				var jqGroup = $("ul.imGroup[groupid='2']");
				jqGroup.prepend(curItem);
				jqGroup.prev("h4").find('em').html($("ul.imGroup[groupid='2']").find("a:not(.offline)").length+"/"+jqGroup.find("li").length);				
			}else{
				$("ul.imGroup").filter('[groupid="2"]').find("li[rel='"+uid+"']").remove();
				addLately(uid,userInfo,curItem);
			}
		};	
		//判断组是否存在用户 返回boolean
		function isHaveUser(groupId,uid){
			var arrs = $("ul.imGroup").filter('[groupid='+groupId+']').find("li");
			var b_ishave=false;
			for(var i = arrs.length;i--;){
				if($(arrs[i]).attr('rel')==uid){
					b_ishave = true;
				}
			}
			return b_ishave;
		}		
		//格式化时间日期
		function formatDateString(date){
			var d=new Date(date);
			var s="";
			s+=d.getFullYear()+"-";
			s+=(d.getMonth()+1)+"-";
			s+=d.getDate()+" ";
			s+=d.getHours()+":";
			s+=d.getMinutes();
			return s;
		};
		//快捷键
		(function initShortcutsSendKey(){
			var sendKey = getShortcutsSendKey();
			var index = $("#imChooseBtn").next().find("span.selected").parent().index();
			if(index!=sendKey){
				$("#imChooseBtn").next().find("span.selected").removeClass("selected");
				$("#imChooseBtn").next().find("span").eq(sendKey).addClass("selected");
			}
		})();
		/*
		(function(){
			//在线
			setInterval(function(){
				$.ajax({
					dataType:settings.ajaxType,
					data:{uid:settings.UID},
					url:settings.onlineUrl,
					cache:false,
					type:settings.ajaxMethodType
				})
			},480000);
		})();
		
		*/
		function surveyLocation(){
			var statusWidth;//状态栏宽度
			statusWidth = $(".friendTree").outerWidth();//状态栏宽度
			var listWidth = $(".friendTree .imFriendList").outerWidth();//列表宽度
			var historyWidth = $(".imHistory").outerWidth();//聊天记录宽度
			if ($("#imDialog:visible").length==0){
				$(".imHistory").hide();
				if ($(".friendTree .imFriendList:visible").length==0){
					$("#TreeTitle .imTitle").removeClass("active");
				}
			}
			if ($(".friendTree .imFriendList:visible").length>0){//好友列表显示
				if ($(".imHistory:visible").length>0){//聊天记录
					$(".imHistory").animate({right:listWidth+statusWidth})
					$("#imDialog").animate({right:listWidth+historyWidth});
				}else{
					$("#imDialog").animate({right:listWidth});
				}
				$("#imContent .imTabList .imTitle").animate({right:45});
				$("#TreeTitle .imTitle").addClass("active");
			}else{				
				if ($(".imHistory:visible").length>0){//聊天记录
					$(".imHistory").animate({right:statusWidth})
					$("#imDialog").animate({right:historyWidth});
				}else{
					$("#imDialog").animate({right:0});
				}
				$("#imContent .imTabList .imTitle").animate({right:-136});
			}
			setChooseEnter();
		}
	}
})(jQuery);

$(function(){
	window.onload=function(e){
		
	var _html = '<div class="imContainer">\
        <div class="imTabList" style="display:none;">\
        	<div class="TreeTitle" rel="">\
                <a href="javascript:void(0);" class="imTitle">\
                    <span class="imIconChat png"></span> <span id="imCurrentTips">正与 <em></em> 聊天</span>\
                </a>\
			</div>\
        	<div class="imFriendList" id="imDialog">\
                <div class="imListHead">\
                	<a class="imListClose imListIcon" href="javascript:void(0);"></a><a class="imListMini imListIcon" href="javascript:void(0);"></a><h3>实时聊天</h3>\
                </div>\
                <div class="imChatList">\
                    <div class="imChatContent">\
                    	<div class="imChatContentTips"><span><i></i>对方当前不在线，可能无法立即回复。</span><img height="9px" width="8px" src="'+miscpath+'/img/system/icon-canncel.png"></div>\
                        <div id="imChatContent" class="wordBreak">\
                        </div>\
                    </div>\
                    <div class="imChatSplit">\
                    	<a id="imFaceSelect" href="javascript:;"></a>\
                        <div id="imFace">\
                    		<span class="imSplitSmallIcon"></span>\
                            <div class="imFaceTitle">普通表情<i><img height="9px" width="9px" src="'+miscpath+'/img/system/icon-canncel.png" id="imCloseFace"></i></div>\
                        	<div id="imfaceList"></div>\
                        </div>\
                        <a href="javascript:;" class="imChatRecord">聊天记录</a>\
                    </div>\
                    <div class="imChatInput">\
                    	<textarea id="imInputMsg"></textarea>\
                    </div>\
                    <div class="imChatInfo">\
                        <span>您还可以输入：<em id="imWordCount">200</em> 字</span>\
                        <div class="imSend">\
                        	<a class="imSendBtn" href="javascript:;">发送</a>\
                            <div class="imChoose">\
                            	<a class="imChooseBtn" id="imChooseBtn" href="javascript:;">选择</a>\
                                <ul class="imChooseEnter">\
                                    <li><span class="selected"></span><a href="javascript:;">按Enter键发送</a></li>\
                                    <li><span></span><a href="javascript:;">按Ctrl+Enter键发送</a></li>\
                                </ul>\
                            </div>\
                        </div>\
                    </div>\
                </div>\
                <div class="imListBody">\
                <a class="imListUp" href="javascript:;"></a>\
                <div class="imListLeftBar">\
                    <ul>\
                    </ul>\
                </div>\
                <a class="imListDown" href="javascript:;"></a>\
                </div>\
            </div>\
        </div>\
		<div class="imHistory" style="">\
			<div class="imListHead">\
				<a class="imListClose imListIcon" href="javascript:void(0);"></a><h3>聊天记录</h3>\
			</div>\
			<div class="imHistoryContent">\
			正在加载中。。。\
			</div>\
			<div class="imChatInfo">\
				<div class="pager">\
					<a href="javascript:void(0);">首页</a>\
					<a href="javascript:void(0);">上一页</a>\
					<span>第<input type="text" value="" class="txt_curIndex" name="txt_curIndex"/>页/<i>3</i>页</span>\
					<a href="javascript:void(0);">下一页</a>\
					<a href="javascript:void(0);">末页</a>\
				</div>\
			</div>\
		</div>\
        <div class="friendTree">\
            <div id="TreeTitle" class="TreeTitle">\
                <a href="javascript:void(0);" class="imTitle">\
				  <span class="im_bar">\
                    <span class="friend"></span>\
					 聊天<span class="count"><strong>0</strong></span>\
				  </span>\
                </a>\
            </div>\
            <div class="imFriendList">\
                <div class="imListHead">\
                	<a class="imListMini imListIcon" href="javascript:void(0);"></a><a class="imListSet imListIcon" href="javascript:void(0);"></a><h3>实时聊天</h3>\
                </div>\
                <div class="imListBody">\
                    <div class="imFriendGroup">\
\
								<div class="imGroup">\
									<h4 class="extend" groupId="2">最近联系人<span class="count">[<em>0/0</em>]</span></h4>\
									<ul style="display: none;" class="imGroup" groupId="2">\
									</ul>\
								</div>\
								<div class="imGroup">\
									<h4 class="extend" groupId="1">相互关注<span class="count">[<em>0/0</em>]</span></h4>\
									<ul style="display: none;" class="imGroup" groupId="1">\
									</ul>\
								</div>\
								<div class="imGroup">\
									<h4 class="extend" groupId="0">好友<span class="count">[<em>0/0</em>]</span></h4>\
									<ul style="display: none;" class="imGroup" groupId="0">\
									</ul>\
								</div>\
								<div class="imGroup">\
									<h4 class="extend" groupId="3">我的群<span class="count">[<em>0</em>]</span></h4>							\
									<ul style="display: none;" class="imGroup" groupId="3">\
									</ul>\
								</div>\
                    </div>\
                    <div class="imSearchInfo" name="imSearchInfo">\
                        <ul>\
                        </ul>\
                    </div>\
                </div>\
				<div class="imListFoot">\
					<h5>\
					<span class="imSearchIcon"></span><input type="text" name="txt_imSearch" class="imSearch" value="搜索好友" tip="搜索好友"/><i class="imListClose imListIcon imhide"></i>\
					</h5>\
				</div>\
            </div>\
        </div>\
    </div>';
		//alert("cookie:"+$.cookie("openIM"));
		var xhr = $.ajax({
			url:mk_url("main/im/getCurUser"),	
			dataType:"jsonp",
			type:"GET",
			data:{},
			cache:false,
			success:function(result){
			if(result.status){
				$("#imContent").html(_html);
				result = result.data;	
					$.ajax({
						url:"/dksns-im-web/getSwitch?uid="+result.uid,
						dataType:"json",
						cache:false,
						success:function(r){
							//alert("switch:"+r.data.imSwitch);
							if (r.status == 1){
								setIM(r.data.imSwitch,null,null,1,result.uid);
								setSound(r.data.ringSwitch,null,null,1,result.uid);
							}	
						},
						async:false
					});
				}
				
				if (getIM()!="0"){
					startIM(e);
				}else{
					$(".imTIP [name='openIM']").html("开启");
					$(".imFriendList,.imHistory").hide();
					var jqStartIM=$('<a class="startIM"><span class="friend"></span>开启聊天模式</a>');			
					$("#TreeTitle").append(jqStartIM).find(".imTitle").css("visibility","hidden").hide();
					$("#TreeTitle").height(182).css({"margin-top":"210px"});
					$("#TreeTitle .startIM").click(function(){
						setIM(1,function(){
						$(".startIM").hide();
						startIM();
						},null,0,result.uid);
					});
				}
			}
		});
	}
});

//获取设置里的声音
window.getSound = function(){
	return $.cookie("openSound");
}
//获取设置里的IM开关
window.getIM = function(){
	return $.cookie("openIM");
}
window.setSound = function(v,success,error,isReq,uid){
	if (isReq){
			$.cookie("openSound",v,{expires:365});
	}else{
	$.ajax({
		url:"/dksns-im-web/ringSwitch",
		data:{value:v,uid:uid},
		type:"GET",
		dataType:"json",
		success:function(r){
			if(r.status==1){
				$.cookie("openSound",v,{expires:365});
				if (success){
					success();
				}
			}else{
				if (error){
					error();
				}
			}
		},
		error:function(e){
			if (error){
				error();
			}
		}
	});
	}
}
window.setIM = function(v,success,error,isReq,uid){	
	if (isReq){
			$.cookie("openIM",v,{expires:365});
	}else{
	$.ajax({
		url:"/dksns-im-web/imSwitch",
		data:{value:v,uid:uid},
		type:"GET",
		dataType:"json",
		success:function(r){
			if(r.status==1){
				//alert(r.status);
				$.cookie("openIM",v,{expires:365});
				//alert($.cookie("openIM"));
				if (success){
					success();
				}
			}else{
				if (error){
					error();
				}
			}
		},
		error:function(e){
			alert("设置失败!");
			if (error){
				error();
			}
		}
	});
	}
}
function startIM(e){	
	var webpath = "/";
	$("#imContent .imTitle:visible").height("380px").removeClass("active");
	$(this).remove();
	$("#TreeTitle").find(".imTitle").css("visibility","visible").show();
	$("#TreeTitle").height("auto").css({"margin-top":"auto"});
	$(".imTitle").removeClass("active");
	//$.cookie("openIM",1)
	try{
			dwr.engine.setActiveReverseAjax(true);
	}catch(err){}
	var xhr = $.ajax({
		url:mk_url("main/im/getCurUser"),	
		dataType:"jsonp",
		type:"GET",
		cache:false,
		data:{},
		success:function(result){
			if(result.status){
				result = result.data;
				setIM(1,null,null,1,result.uid);
				$("#imContent").IM({
					friendList:webpath+"im/im/getlist",
					searchUrl:webpath+"dksns-im-web/search/searchff",
					sendUrl:webpath+"dksns-im-web/message/send",
					sendGroupUrl:webpath+"dksns-im-web/sendGroupMessage/sendMessage",
					recciveUrl:webpath+"im/im/receive",
					time:6000,
					UID:result.uid,
					userName:result.username,
					historyUrl:webpath+"dksns-im-web/chatHistory/get",
					//historyUrl:miscpath+"js/im/imHistory.txt",
					sessionUrl:webpath+"im/im/getzrtalkNo",
					onlineUrl:webpath+"im/im/mqsendheart",
					friendGroupList:webpath+"im/im/getlist"
				});	
			}
		}
	});
}

function errorInfo(str){
	//alert("对不起，你的账号已在别的地方登录!");
}

function Pager(ops){
	this.currentIndex = 1;
	this.count = ops.count;
	this.pageSize = ops.pageSize||10;
	this.sumPage = 1;
	this.content = ops.content;
	this.ajaxUrl = ops.url;
	this.returnFunc=ops.returnFunc||new Function();
};
Pager.prototype={
	init:function(ops){
		var _self = this;
		_self.currentIndex = ops.currentIndex;
		_self.ajaxArgs = $.extend( ops.ajaxArgs,{page:this.currentIndex-1});
		_self.model("go",[_self.ajaxArgs,_self.returnFunc,function(){
			_self.currentIndex = _self.sumPage;
			_self.ajaxArgs = $.extend( ops.ajaxArgs,{page:_self.currentIndex-1});
			_self.model("go",[_self.ajaxArgs,_self.returnFunc]);
		}]);
	},
	view:function(method,args){		
		var _self=this;	
		var _class={
			page:function(args){
				var _html='\
						<div class="pager">\
							<span class="firstPage"><a href="javascript:void(0);">首页</a></span>\
							<span class="prePage"><a href="javascript:void(0);">上一页</a></span>\
							<span class="inputPage">第<input type="text" value="'+ _self.currentIndex +'" class="txt_curIndex" name="txt_curIndex"/>页/<i>'+_self.sumPage+'</i>页</span>\
							<span class="nextPage"><a href="javascript:void(0);">下一页</a></span>\
							<span class="lastPage"><a href="javascript:void(0);">末页</a></span>\
						</div>\
							';
				_self.content.html(_html);
				_self.event("bind",args);
				switch (_self.currentIndex){
					case 0:{
						$(".nextPage,.lastPage",_self.content).find("a").addClass("enable");
						$(".firstPage,.prePage",_self.content).find("a").addClass("enable");
						}break;
					case 1:{
						$(".firstPage,.prePage",_self.content).unbind("click");
						$(".firstPage,.prePage",_self.content).find("a").addClass("enable");
						$(".nextPage,.lastPage",_self.content).find("a").removeClass("enable");
					}break;
					case _self.sumPage:{
						$(".nextPage,.lastPage",_self.content).unbind("click");
						$(".firstPage,.prePage",_self.content).find("a").removeClass("enable");
						$(".nextPage,.lastPage",_self.content).find("a").addClass("enable");
					}break;
					default:{
						$(".nextPage,.lastPage",_self.content).find("a").removeClass("enable");						
						$(".firstPage,.prePage",_self.content).find("a").removeClass("enable");
					}
				}
				if (_self.sumPage ==1 ||_self.sumPage==0){
					$(".nextPage,.lastPage",_self.content).find("a").addClass("enable");
					$(".firstPage,.prePage",_self.content).find("a").addClass("enable");
					$(".firstPage,.prePage,.nextPage,.lastPage",_self.content).unbind("click");
				}
			}
		};
		return _class[method](args);
	},
	event:function(method,args){
		var _self=this;
		var pager = $("div.pager",_self.content);
		var _class={
			bind:function(args){
				$("[name='txt_curIndex']",_self.content).keyup(function(e){
					if ($(this).val()){
						var rex =/^\d+$/;
						var i = parseInt($(this).val());
						if (!rex.test( $(this).val()) || i>_self.sumPage || i<1 ){
							$(this).val(_self.currentIndex);
						}else
						if (e.keyCode==13){
							_self.cpu("jump",args);
						}
					}
				});
				$(".prePage",_self.content).click(function(){
					_self.cpu("prev",args);
				});	
				$(".nextPage",_self.content).click(function(){
					_self.cpu("next",args);
				});
				$(".lastPage",_self.content).click(function(){
					_self.cpu("last",args);
				});
				$(".firstPage",_self.content).click(function(){
					_self.cpu("first",args);
				});
			}
		};
		return _class[method](args);
	},
	model:function(method,args){
		var _self=this;
		var _class={
			go:function(args){
				return $.ajax({
					url:_self.ajaxUrl,
					dataType:"json",
					cache:false,
					data:args[0],
					success:function(data){
						args[1](data);
						_self.cpu("change",data);
						args[2] ? args[2]() :"";
					},
					type:"POST",
					error:function(data){
						 alert("json格式不正确")
					}
				});
			}
		};
		return _class[method](args);
	},
	cpu:function(method,args){
		var _self=this;
		var _class={
			change:function(data){
				var arr = data;
				//if (data.count>0){
					_self.count=data.count;
					_self.sumPage= parseInt( _self.count % _self.pageSize >0 ? _self.count / _self.pageSize+1 : _self.count / _self.pageSize);
					_self.view("page");
				//}
			},				
			jump:function(args){
				var input = parseInt($("[name='txt_curIndex']",_self.content).val());
				if (input>0 && input <= _self.sumPage){
					_self.currentIndex = input;
					_self.ajaxArgs.page = _self.currentIndex-1;
					_self.model("go",[_self.ajaxArgs,_self.returnFunc]);
				}
			},
			prev:function(args){
				if (_self.currentIndex > 1){
					_self.currentIndex--;
					$("[name='txt_curIndex']",_self.content).val(_self.currentIndex);
					_self.cpu("jump",args);
				}
			},
			next:function(args){				
				if (_self.currentIndex < _self.sumPage ){
					_self.currentIndex++;
					$("[name='txt_curIndex']",_self.content).val(_self.currentIndex);
					_self.cpu("jump",args);
				}
			},
			last:function(args){
				_self.currentIndex = _self.sumPage;
				$("[name='txt_curIndex']",_self.content).val(_self.currentIndex);
				_self.cpu("jump",args);
			},
			first:function(args){
				_self.currentIndex = 1;
				$("[name='txt_curIndex']",_self.content).val(_self.currentIndex);
				_self.cpu("jump",args);
			}
		};
		return _class[method](args);
	}
};
function Tip(ops){
	this.obj=ops.obj;
	this.arrowPos=ops.arrowPos || 10;
	this.content = ops.content;
	this.imTIP=null;
	this.init();
};
Tip.prototype = {
	init:function(){
		var _html = $('<div class="imTIP"><i></i><div class="TIPCONTENT">'+ this.content +'</div></div>');		
		this.imTIP =_html;
		$("body").append(this.imTIP);
		//this.imTIP.find("i").css({left:this.arrowPos}).end().css({top:this.obj.height()});
		this._event();
	},
	_event:function(){
		var _self = this;
		(function(_self){
			_self.obj.mousedown(function(e){
				Pos.call(this,e);
				_self.imTIP.toggle();
				e.stopPropagation();
			})
			function Pos(e){	
				if ($(this).length>0){
				var ex = parseInt($(this).offset().left);
				var ey = $(this).offset().top+$(this).height();
				if (ex+parseInt(_self.imTIP.width())> parseInt($(document).width())){
					ex= $(document).width()-_self.imTIP.width()-50;
				}
				var ix = parseInt($(this).offset().left)-ex+$(this).width()/2-12;
				_self.imTIP.find("i").css({left:ix});
				_self.imTIP.css({left:ex,top:ey});
				}
			}
			$(window).scroll(function(e){
				Pos.call(_self.obj,e);
			});
			$(window).resize(function(e){
				Pos.call(_self.obj,e);
			});
		})(_self);
		$(".imTIP").mousedown(function(e){
			e.stopPropagation();
		});
		$(document).mousedown(function(){
			$(".imTIP").hide();
		});
	}
};
/*

if(-1 != navigator.userAgent.indexOf("MSIE"))
{
	document.write(' <OBJECT id="Player"');
	document.write(' classid="clsid:6BF52A52-394A-11d3-B153-00C04F79FAA6"');
	document.write(' width=0 height=0 > <param name="URL" value="'+miscpath+ 'flash/msg.wav" /> <param name="AutoStart" value="false" /> </OBJECT>');
}
else
{
	document.write(' <OBJECT id="Player"');
	document.write(' type="application/x-ms-wmp"');
	document.write(' autostart="false" src="'+miscpath+ 'flash/msg.wav" width=0 height=0> </OBJECT>');
}
*/

/******jsSWF*********/
var js4swf = function() {
	this.isIE = (navigator.appVersion.indexOf("MSIE") != -1) ? true : false;
	this.isWin = (navigator.appVersion.toLowerCase().indexOf("win") != -1) ? true : false;
	this.isOpera = (navigator.userAgent.indexOf("Opera") != -1) ? true : false;
}

js4swf.prototype = {
	ControlVersion : function() {
		var version;
		var axo;
		var e;
		// NOTE : new ActiveXObject(strFoo) throws an exception if strFoo isn't in the registry
		try {
			// version will be set for 7.X or greater players
			axo = new ActiveXObject("ShockwaveFlash.ShockwaveFlash.7");
			version = axo.GetVariable("$version");
		} catch (e) {
		}
		if(!version) {
			try {
				// version will be set for 6.X players only
				axo = new ActiveXObject("ShockwaveFlash.ShockwaveFlash.6");
				// installed player is some revision of 6.0
				// GetVariable("$version") crashes for versions 6.0.22 through 6.0.29,
				// so we have to be careful.
				// default to the first public version
				version = "WIN 6,0,21,0";
				// throws if AllowScripAccess does not exist (introduced in 6.0r47)
				axo.AllowScriptAccess = "always";
				// safe to call for 6.0r47 or greater
				version = axo.GetVariable("$version");
			} catch (e) {
			}
		}
		if(!version) {
			try {
				// version will be set for 4.X or 5.X player
				axo = new ActiveXObject("ShockwaveFlash.ShockwaveFlash.3");
				version = axo.GetVariable("$version");
			} catch (e) {
			}
		}
		if(!version) {
			try {
				// version will be set for 3.X player
				axo = new ActiveXObject("ShockwaveFlash.ShockwaveFlash.3");
				version = "WIN 3,0,18,0";
			} catch (e) {
			}
		}
		if(!version) {
			try {
				// version will be set for 2.X player
				axo = new ActiveXObject("ShockwaveFlash.ShockwaveFlash");
				version = "WIN 2,0,0,11";
			} catch (e) {
				version = -1;
			}
		}
		return version;
	},
	// JavaScript helper required to detect Flash Player PlugIn version information
	GetSwfVer : function() {
		// NS/Opera version >= 3 check for Flash plugin in plugin array
		var flashVer = -1;
		if(navigator.plugins != null && navigator.plugins.length > 0) {
			if(navigator.plugins["Shockwave Flash 2.0"] || navigator.plugins["Shockwave Flash"]) {
				var swVer2 = navigator.plugins["Shockwave Flash 2.0"] ? " 2.0" : "";
				var flashDescription = navigator.plugins["Shockwave Flash" + swVer2].description;
				var descArray = flashDescription.split(" ");
				var tempArrayMajor = descArray[2].split(".");
				var versionMajor = tempArrayMajor[0];
				var versionMinor = tempArrayMajor[1];
				var versionRevision = descArray[3];
				if(versionRevision == "") {
					versionRevision = descArray[4];
				}
				if(versionRevision[0] == "d") {
					versionRevision = versionRevision.substring(1);
				} else if(versionRevision[0] == "r") {
					versionRevision = versionRevision.substring(1);
					if(versionRevision.indexOf("d") > 0) {
						versionRevision = versionRevision.substring(0, versionRevision.indexOf("d"));
					}
				}
				var flashVer = versionMajor + "." + versionMinor + "." + versionRevision;
			}
		}
		// MSN/WebTV 2.6 supports Flash 4
		else if(navigator.userAgent.toLowerCase().indexOf("webtv/2.6") != -1)
			flashVer = 4;
		// WebTV 2.5 supports Flash 3
		else if(navigator.userAgent.toLowerCase().indexOf("webtv/2.5") != -1)
			flashVer = 3;
		// older WebTV supports Flash 2
		else if(navigator.userAgent.toLowerCase().indexOf("webtv") != -1)
			flashVer = 2;
		else if(this.isIE && this.isWin && !this.isOpera) {
			flashVer = this.ControlVersion();
		}
		return flashVer;
	},
	// When called with reqMajorVer, reqMinorVer, reqRevision returns true if that version or greater is available
	DetectFlashVer : function(reqMajorVer, reqMinorVer, reqRevision) {
		versionStr = this.GetSwfVer();
		if(versionStr == -1) {
			return false;
		} else if(versionStr != 0) {
			if(this.isIE && this.isWin && !this.isOpera) {
				// Given "WIN 2,0,0,11"
				tempArray = versionStr.split(" ");
				// ["WIN", "2,0,0,11"]
				tempString = tempArray[1];
				// "2,0,0,11"
				versionArray = tempString.split(",");
				// ['2', '0', '0', '11']
			} else {
				versionArray = versionStr.split(".");
			}
			var versionMajor = versionArray[0];
			var versionMinor = versionArray[1];
			var versionRevision = versionArray[2];
			// is the major.revision >= requested major.revision AND the minor version >= requested minor
			if(versionMajor > parseFloat(reqMajorVer)) {
				return true;
			} else if(versionMajor == parseFloat(reqMajorVer)) {
				if(versionMinor > parseFloat(reqMinorVer))
					return true;
				else if(versionMinor == parseFloat(reqMinorVer)) {
					if(versionRevision >= parseFloat(reqRevision))
						return true;
				}
			}
			return false;
		}
	},
	AC_AddExtension : function(src, ext) {
		//if(src.indexOf('?') != -1)
		return src;
		//else
			//return src + ext;
	},
	AC_Generateobj : function(objAttrs, params, embedAttrs, container) {
		var str = '';
		if(this.isIE && this.isWin && !this.isOpera) {
			/*var object = document.createElement("object");
			var ckassid = document.createAttribute("ckassid");	
			ckassid.nodeValue = objAttrs["classid"];
			
			object.setAttributeNode(ckassid);
			for(var i in objAttrs) {
				if(i != "classid"){
					object[i] = objAttrs[i]
				}
			}
			document.getElementById(container).appendChild(object);
			for(var i in params) {
				var param = document.createElement("param");
				var reg = new RegExp("^(.)","g");
				var w = reg.test(i);
				var u = RegExp["$1"].toUpperCase();
				param.name = i.replace(reg,u);
				param.value = params[i];
				document.getElementById("upload").getElementsByTagName("object")[0].appendChild(param);
			}*/
			str += '<object ';
			for(var i in objAttrs) {
				str += i + '="' + objAttrs[i] + '" ';
			}
			str += '>';
			for(var i in params) {
				str += '<param name="' + i + '" value="' + params[i] + '" /> ';
			}
			str += '</object>';
		} else {
			str += '<embed ';
			for(var i in embedAttrs) {
				str += i + '="' + embedAttrs[i] + '" ';
			}
			str += '> </embed>';
		}
		if(container)
			container.innerHTML = str;
	},
	AC_FL_RunContent : function() {
		var ret = this.AC_GetArgs(arguments, ".swf", "movie", "clsid:d27cdb6e-ae6d-11cf-96b8-444553540000", "application/x-shockwave-flash");
		this.AC_Generateobj(ret.objAttrs, ret.params, ret.embedAttrs, ret.container);
	},
	AC_SW_RunContent : function() {
		var ret = this.AC_GetArgs(arguments, ".dcr", "src", "clsid:166B1BCA-3F9C-11CF-8075-444553540000", null);
		this.AC_Generateobj(ret.objAttrs, ret.params, ret.embedAttrs);
	},
	thisMovie: function(movieName){
		if(navigator.appName.indexOf("Microsoft")!=-1)
    	{
	    	return window[movieName];  	
    	}else{
    		return document[movieName];
    	}
	},
	AC_GetArgs : function(args, ext, srcParamName, classid, mimeType) {
		var ret = new Object();
		ret.objAttrs = new Object();
		ret.params = new Object();
		ret.embedAttrs = new Object();
		ret.embedAttrs["wmode"] = ret.params["wmode"] = "window";
		ret.embedAttrs["bgcolor"] = ret.params["bgcolor"] = "#ffffff";
		ret.embedAttrs["menu"] = ret.params["menu"] = "false";
		ret.embedAttrs["allowScriptAccess"] = ret.params["allowScriptAccess"] = "always";
		ret.embedAttrs["width"] = ret.objAttrs["width"] = "100%";
		ret.embedAttrs["height"] = ret.objAttrs["height"] = "100%";
		ret.embedAttrs["name"] = ret.objAttrs["name"] = "flashvideoupload";
		ret.embedAttrs["style"] = ret.objAttrs["style"] = 'display:block;';
		
		
		args = args[0];
		for(var i in args) {
			var currArg = i.toLowerCase();
			switch (currArg) {
				case "appendto":
					ret.container = args[i];
					break;
				case "width":
				case "height":
					ret.embedAttrs[i] = ret.objAttrs[i] = args[i];
					break;
				case "classid":
					break;
				case "pluginspage":
					ret.embedAttrs[args[i]] = 'http://www.macromedia.com/go/getflashplayer';
					break;
				case "src":
				case "movie":
					args[i] = this.AC_AddExtension(args[i], ext);
					ret.embedAttrs["src"] = args[i];
					ret.params[srcParamName] = args[i];
					break;
				case "onafterupdate":
				case "onbeforeupdate":
				case "onblur":
				case "oncellchange":
				case "onclick":
				case "ondblclick":
				case "ondrag":
				case "ondragend":
				case "ondragenter":
				case "ondragleave":
				case "ondragover":
				case "ondrop":
				case "onfinish":
				case "onfocus":
				case "onhelp":
				case "onmousedown":
				case "onmouseup":
				case "onmouseover":
				case "onmousemove":
				case "onmouseout":
				case "onkeypress":
				case "onkeydown":
				case "onkeyup":
				case "onload":
				case "onlosecapture":
				case "onpropertychange":
				case "onreadystatechange":
				case "onrowsdelete":
				case "onrowenter":
				case "onrowexit":
				case "onrowsinserted":
				case "onstart":
				case "onscroll":
				case "onbeforeeditfocus":
				case "onactivate":
				case "onbeforedeactivate":
				case "ondeactivate":
				case "type":
				case "id":
					ret.objAttrs[i] = args[i];
					break;
				case "codebase":
					ret.objAttrs[i] = 'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0';
					break;
				case "align":
				case "vspace":
				case "hspace":
				case "class":
				case "title":
				case "accesskey":
				case "name":
				case "tabindex":
				case "style":
					ret.embedAttrs[i] = ret.objAttrs[i] = args[i];
					break;
				case "quality":
					ret.embedAttrs[i] = ret.params[i] = 'high';
					break;
				default:
					ret.embedAttrs[i] = ret.params[i] = args[i];
			}
		}
		ret.objAttrs["classid"] = classid;
		if(mimeType)
			ret.embedAttrs["type"] = mimeType;
		return ret;
	}
}
