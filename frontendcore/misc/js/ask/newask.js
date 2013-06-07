/*
 *@author:    林长源
 *@created:   2011/12/08
 *@desc:      问答
 *@version:   v2.1
 */
function CLASS_ASK(arg){
	this.friend_askList_box = null;
	this.askAdd = null;
	this.arg = arg;
	this.init();
}
CLASS_ASK.prototype= {
	init:function(){
		var self = this;
		if($("#my_ask").size()==0){
			//"http://localhost/new_duankou/single/ask/index.php/ask/list_asks?type=0&dkcode=100026";

			this.location = webpath;
			this.dkcode = "";
			this.webpath=webpath+"single/ask/index.php";
			this.webpath2 = webpath+"main/index.php";
			this.my_askList = $("#my_askList");
			this.my_askList_box = $("#my_askList_box");
			this.friend_askList = $("#friend_askList");
			this.ask_operation = $("div.ask_operation");
			this.friend_askList_box = $("#friend_askList_box");							// 问答列表 box
			this.askDetail = $("#askDetail");						// 问答详细窗口
			this.friends_detail = $("#friends_detail");				// 好友列表窗口
			this.uid = $("#sessionuid").val();		
			this.setting_voting = $("#setting_voting");				// 展开添加答案
			this.add_asks = $("#add_asks");							// 提问发送按钮
			this.addask = $("#addask");								// 提问展开问题按钮
			this.votingBox = $("#votingBox"); 						// 问题 多选项box
			this.inputBox = $("#inputBox"); 						// 问题输入框 
			this.addNewAsk = $("#addNewAsk"); 						// 新问题box 
			this.add_allPerson = $("#add_allPerson"); 				// 允许添加多人添加答案 box
			this.setting_moreOptions = $("#setting_moreOptions"); 	// 设置是否多选 box
			this.dkou = $("#dkou").val();
			this.qidArray = [];
			this.$permission = $("input[name=permission]");
			this.setting_more = $("#setting_more");
			this.other_item = $(".other_item");
			this.shareDestinationObjects = $("#shareDestinationObjects");
			this.plug(["shareDestinationObjects"],[this.shareDestinationObjects]);
			this.event("init");
			this.model("friend_asks",[{type:0},function(data){
				self.friend_askList_box.show();
				self.view(["askList"],[self.friend_askList_box,data]);
				self.plug(["tip_up_right_black","tip_up_black","tip_right_black","tip_up_djax","msg"],[self.friend_askList_box]);
				self.event("askList",self.friend_askList_box);
				self.event("otherPerson",self.friend_askList_box);
				self.event("item_box");
				self.event("new_item",self.friend_askList_box);
				

			},self.friend_askList_box.find("div.ask_more")]);
			
		}else{
		//if(this.url=="/my_ask.html"){
			this.my_ask = $("#my_ask");
			self.plug(["msg"],[self.my_ask]);
			self.event("item_box");
		}
		if(this.arg.from_ask_notices){
			self.askuid = $("#askuid").val();
			var data = {};
			data.poll_id = this.arg.from_ask_notices.id;
			self.plug(["popUpAskDetail"],[$(),self.askDetail,this.arg.from_ask_notices.title]);
			self.model("one_ask",[data,function(data){
				self.view(["askDetail"],[self.askDetail,data]);
				self.event("otherPerson",self.askDetail);

				// 先有答案再加载评论
				self.model("list_comments",[{frmid:data.poll_id},function(data){
					self.view(["note"],[self.askDetail,data]);
					$("#from_ask_notices").remove();
				}]);
			}]);
			
			
			
		}
	},
	view:function(method,arg){
		var self = this;
		var _class = {
			askList:function(arg){
				var str = "";
				if(arg[1].state==0){
					arg[0].children("ul").html("");
					arg[0].find("div.ask_more").attr("page",1).children().text(arg[1].msg);
					arg[0].find("div.ask_more").addClass("disabled");
					return false;
				}
				var showmoreinfo = arg[1].showmoreinfo;
				$.each(arg[1].data,function(n,a){
					var id = a.questionid,name = a.username,actionMsg = a.msg,uid = a.uid,question = a.question,answer = a.answer,time = a.time,img = a.img,muti =a.muti,focusTotal = a.votes,suport = a.friendsnum||0,discuz = a.commentnum||0,allow = a.allow,followed = a.followed,showfollow = a.showfollow,dkou = a.dkou,askfriend = a.askfriend,link_url = a.link_url;
					var qArr = a.questionoptions;
					str+='<li id="'+id+'" class="li clearfix" muti="'+muti+'"><a class="user_face" href="'+link_url+'/"> <img height="50" width="50" src="'+img+'"></a><div class="item_box clearfix"><div class="content"><div class="info"> <strong><a href="'+link_url+'/">'+name+'</a></strong>&nbsp;'+actionMsg+'" <a name="question">'+question+'</a> "。';
					if(answer){
						str+='选择是" <a name="answer">'+answer+'</a> 。"';
					}
					str+=' </div>';
					$.each(qArr,function(a,b){
						if(a>=3&&qArr.length>4){
							return false;
						}
						var qid = b.id,pollid= b.poll_id,votes=b.votes,value = b.message||"undefined",voters=b.voters||null,selected = b.selected,otherPerson=b.otherPerson||null;
						var checked = false;
						
						str+='<ul  class="block clearfix" qid="'+qid+'" pollid="'+pollid+'">'+
								'<li class="poll_btn">'
									if(muti==0){
										if(selected){
											str+='<input type="radio" name="option_id'+id+'" checked="checked">'; 
										}else{
											str+='<input type="radio" name="option_id'+id+'">';
										}
}else{
										if(selected){
											str+='<input type="checkbox" name="option_id'+id+'" checked="checked">';
										}else{
											str+='<input type="checkbox" name="option_id'+id+'">';
										}
									}
							str+='</li>';
							str+='<li class="poll_result_bar tip_up_right_black" tip="'+votes+'票">';
								var votes_ = (votes/focusTotal)*100;
								str+='<div style="width:'+votes_+'%" class="shaded"></div>'
								str+='<div class="label">'+value+'</div>';
							str+='</li><li class="target_event"></li>';
						str+='<li class="poll_result_facepile">';
							str+='<ul class="pile_list clearfix">';
							if(voters){
								if(voters.friend){
									$.each(voters.friend,function(i,c){
									
										var fid = c.uid,f_name = c.name,f_face = c.img;
										str+='<li uid="'+fid+'" class="friends_face"><a class="uiface_pile_face" href="javascript:void(0)"> <img height="24" width="24" src="'+f_face+'" class="tip_up_black" tip="'+f_name+'"> </a> </li>';
										if(i>4){
											return false;
										}
									});
									
									if(voters.friend.length>5){
										str+='<li><a class="uiface_pile_face add_faces tip_right_black" id="selectedList" href="javascript:void(0)" tip="李海棠<br>岳飞">+2</a> </li>';
									}
								}
								if(voters.otherPerson){
									str+='<li class="otherPerson"><a class="uiface_pile_face more_faces uitool_tip tip_right_black" id="selectedList" href="javascript:void(0)" tip="+'+voters.otherPerson.count+'"> </a> </li>';
								}
							}
							str+='</ul></li></ul>';
								
					});
          
					if(qArr.length<=4){
						if(allow==1){
						str+='<div class="add_new_item">'+
								'<table width="230">'+
									'<tr>'+
										'<td><div class="new_item">'+
											'<input type="text"  msg="添加一个答案..." >'+
										'</div>'+
										'</td>'+
										'<td style="display:none" width="40"><div class="uiButton" id="add_new_item">添加</div></td>'+
									'</tr>'+
								'</table>'+
							'</div>';
						}
					}
					if(qArr.length>4){
						str+='<div class="other_item clearfix"> <span class="other_item_span">其它'+(qArr.length-3)+'个选项</span>  </div>';
					}
					str+='<div class="item_footer clearfix"> <span class="time">'+time+'</span> · <a class="dialog_pipe"  href="javascript:void(0)"> <i class="icon_suport"></i> <span>'+suport+'</span> <i class="icon_discuz"></i> <span>'+discuz+'</span> </a>';
				
					if(showfollow){
						if(followed){
							str+=' · <span class="ding_focus"  style="display:none">关注</span><span class="cancel_focus">取消关注</span>';
						}else{
							str+=' · <span class="ding_focus">关注</span><span class="cancel_focus" style="display:none">取消关注</span>';
						}
					}
					if(askfriend){
						str+=' · <span class="choice_friends">提问</span> </div>';
					}
					str+='</ul></div></div></li>';
				});
				
				if(arg[2]){
					arg[0].children("ul").append(str);
					
				}else{
					arg[0].children("ul").html(str);
				}
				if(showmoreinfo){
					arg[0].find("div.ask_more").show();
					arg[0].find("div.ask_more").children().text("点击查看更多");
				}else{
					arg[0].find("div.ask_more").hide();
				}
				self.iefix(["height"],[arg[0]]);
			},
			askDetail:function(arg){
				var str = "";
				if(arg[1].state==0){
					arg[0].children().append(arg[1].msg);
					return false;
				}
				var a = arg[1].data;
				var id = a.id,name = a.username,time = a.dateline,face=a.img,muti = a.muti,focusTotal = a.votes||0,oredit = a.oredit,followinfo = a.followinfo,followed = a.followed,showfollow = a.showfollow,curvoted = a.curvoted,allow = a.allow,access = a.access,accessText,askfriend = a.askfriend||null;
				var qArr = a.options||[];
				str+='<form><div class="item_box clearfix">';
				arg[0].attr("frmid",id);
				arg[0].attr("muti",muti);
				$.each(qArr,function(i,b){
					if(a>=3&&qArr.length>4){
						return false;
					}
					var qid = b.id,pollid= b.poll_id,votes=b.votes,value = b.message||"undefined",voters=b.voters||null,selected = b.selected,ordel = a.ordel;
					var checked = false;
					
				
					str+='<ul  class="block clearfix" qid="'+qid+'" pollid="'+pollid+'">'+
							'<li class="poll_btn">'
								if(muti==0){
									if(selected){
										str+='<input type="radio" name="option_id'+id+'" checked="checked">'; 
									}else{
										str+='<input type="radio" name="option_id'+id+'">';
									}
}else{
if(selected){
										str+='<input type="checkbox" name="option_id'+id+'" checked="checked">';
									}else{
										str+='<input type="checkbox" name="option_id'+id+'">';
									}
								}
						str+='</li>';
						str+='<li class="poll_result_bar tip_up_right_black" tip="'+votes+'票">';
							var votes_ = (votes/focusTotal)*100;
							str+='<div style="width:'+votes_+'%" class="shaded"></div>'
							str+='<div class="label">'+value+'</div>';
						str+='</li><li class="target_event"></li>';
					str+='<li class="poll_result_facepile">';
						str+='<ul class="pile_list clearfix">';
							if(voters){
								if(voters.friend){
									$.each(voters.friend,function(i,c){
									
										var fid = c.uid,f_name = c.name,f_face = c.img;
										str+='<li uid="'+fid+'" class="friends_face"><a class="uiface_pile_face" href="javascript:void(0)"> <img height="24" width="24" src="'+f_face+'" class="tip_up_black" tip="'+f_name+'"> </a> </li>';
										if(i>4){
											return false;
										}
									});
									
									if(voters.friend.length>5){
										str+='<li><a class="uiface_pile_face add_faces tip_right_black" id="selectedList" href="javascript:void(0)" tip="李海棠<br>岳飞">+2</a> </li>';
									}
								}
								if(voters.otherPerson){
									str+='<li class="otherPerson"><a class="uiface_pile_face more_faces uitool_tip tip_right_black" id="selectedList" href="javascript:void(0)" tip="+'+voters.otherPerson.count+'"> </a> </li>';
								}
							}
							
							
							str+='</ul></li></ul>';
							
				});
				if(allow==1&&qArr.length<100){
					str+='<div class="add_new_item">'+
						'<table width="230">'+
							'<tr>'+
								'<td><div class="new_item">'+
									'<input type="text"  msg="添加一个答案..." >'+
								'</div>'+
								'</td>'+
								'<td style="display:none" width="40"><div class="uiButton" id="add_new_item">添加</div></td>'+
							'</tr>'+
						'</table>'+
					'</div>';
				}
				str+=' </div></form>';
				str+='<div class="question_box">';
					str+='<h3><strong>提问者</strong>';
					if(followinfo){
						str+='<a class="tip_right_black focus_pearson" tip="其他'+followinfo.otherPerson+'个人" style="float:right;color:#808080;" href="javascript:void(0);" class="question_focuser">·'+followinfo.follownum+'关注者</a>';
					}
					str+='</h3>';

					str+='<div class="content clearfix">';
						str+='<div class="question_user"><a href="javascript:void(0);"><img width="30" height="30" border="0" src="'+face+'" /></a></div>';
						str+='<div class="question_user_info"> <a class="name" href="javascript:void(0);">'+name+'</a><br /><span title="2011年5月16日22：41" class="time">'+time+'</span>';
						switch(access){
							case "-1":
								accessText=[];
								$.each(a.access_users,function(j,k){
									accessText.push(k.username);
								});
								accessText = accessText.join(" ");
							break;
							case "1":
								accessText = "公开";
							break;
							case "8":
								accessText = "仅自己";
							break;
							case "4":
								accessText = "好友";
							break;
							case "3":
								accessText = "粉丝";
							break;
						}
						str+=' · <a class="bp_mars tip_up_black" tip="公开对象:'+accessText+'"></a>'
						if(curvoted){
							str+=' · <a class="cancelVote">取消投票</a>';
							
						}
						
						if(oredit==1&&qArr.length>0){
							str+=' · <a class="editAsk">编辑选项</a> · <a class="deleteAsk">删除</a>';
						}
						if(oredit==1 &&qArr.length==0){
							str+=' · <a class="deleteAsk">删除</a>';
						}
						
						
						str+='</div>';
						if(showfollow){
							var rightPx = '73px';
							if(!askfriend){
								rightPx = '3px';
							}
							if(followed){
							str+='<div class="ding_focus friendBtns" style="display:none;right:'+rightPx+'"><a class="btn"><i class="followed"></i><span>关注</span></a></div><div class="cancel_focus friendBtns" style="right:'+rightPx+'"><a class="btn"><i class="followed"></i><span>取消关注</span></a></div>';
							}else{
								str+='<div class="ding_focus friendBtns"  style="right:'+rightPx+'"><a class="btn"><i class="followed"></i><span>关注</span></a></div><div class="cancel_focus friendBtns" style="display:none;right:'+rightPx+'"><a class="btn"><i class="followed"></i><span>取消关注</span></a></div>';
							}
							
						}
						
						/*<span class="friends_comments_list on">朋友</span> · <span class="other_comments_list">其他</span>*/				
						if(askfriend){
							str+='<div class="ask_friends friendBtns" style="right:3px"><a class="btn"><i class="followed"></i><span>提问</span></a></div>';
						}
						str+='</div></div><div class="note"><h3><strong>帖子</strong><a></a></h3><div class="content clearfix"><div class="reply_user"><a href="javascript:void(0);"><img width="50" height="50" border="0" src="'+face+'" /></a></div><div class="reply_input"><input type="text" style=""  msg="写点什么吧..." maxlength="500" /><div class="reply_btn"><span>发表</span></div></div></div>';
				
				arg[0].find("#askDetail_item").html(str);
				self.plug(["tip_up_right_black","tip_up_black","tip_right_black","tip_up_djax","msg"],[self.askDetail]);
				self.event("askDetail_item");
				self.event("otherPerson",arg[0]);
				self.iefix(["height"],[arg[0]]);
			},
			note:function(arg){
				var str="";
				if(arg[1].state=="1"){
					$.each(arg[1].data,function(i,a){
						var id = a.id,name = a.username,img = a.img,msg = a.message,time = a.dateline,scomments = a.scomments,ordel = a.ordel||0,str2="",delete_comment_str="",liked_a = a.liked,dkou = a.dkou,uid = a.uid,qids = a.qids;

						var textArr = [];
						var text = ""
						if(qids){
							$.each(qids,function(i,v){
								var $liText = self.askDetail.find("ul[qid="+v+"]").find(".label").text();

								textArr.push('"<span class="question">'+$liText+'</span>"');
							})
							if(textArr.length>0){
								text = "选择了 ";
								text+= textArr.join(" 、 ");

							}
						}
						if(ordel==1){
							delete_comment_str='<span class="ui_closeBtn_box"><i class="ui_closeBtn png"></i></span>';
						}
						str+='<div class="comment clearfix" frmid="'+id+'">'+delete_comment_str;
						str+='<div class="reply_user"><a href="'+self.location+dkou+'"><img width="50" border="0" height="50" src="'+img+'"></a></div><div class="reply_content"><span class="name"><a href="'+self.location+dkou+'">'+name+'</a></span><span class="text">'+text+'</span><span class="content">'+msg+'</span><div class="operation" commentObjId="'+id+'" pageType="ask" action_uid="'+uid+'"></div></div></div>';
					});
					arg[0].find("#askDetail_note").html('<div class="comments_box">'+str+'</div>');
					self.plug(["commentEasy"],[arg[0].find("div.comments_box").find("div.operation")]);
					self.event("askDetail_note",self.askDetail);
					self.event("postlike",self.askDetail);
					self.event("delete_comment",self.askDetail.find("div.comment"));
				
					self.plug(["msg"],[self.askDetail]);
					
					
				}else{
					arg[0].find("#askDetail_note").html('<div class="comments_box"></div>');
					return false;
				}
				
			},
			del_asks:function(arg){
				if(arg[0].state==1){
					$("#popUp").find("span.closeBtn").click();
					self.ask_operation.find("span.on").click();
				}
			},
			friends_list:function(arg){
				var str="";
				str+='<ul>';
				$.each(arg[1].data,function(index,a){
					var id = a.usr_id,name = a.username,user_face = a.avatar_img;
					str+='<li id="'+id+'"><label for="checkbox'+id+'"><input type="checkbox" id="checkbox'+id+'"/><img width="32" height="32" src="'+user_face+'" /><span>'+name+'</span></label></li>';
				});
				str+='</ul>';
				arg[0].html(str);
			},
			voting:function(arg){
				if(arg[1].state == 0){
					alert(arg[1].msg);
					return false;
				}
				
				var a = arg[1].data;
				var total = parseInt(a.votes),img = a.img, name = a.username, uid = a.uid;
				var tipId,$poll_btn,$pile_list,pollResultBar,votingNum;
				
				var $pile = $('<li uid="'+uid+'"><a href="javascript:void(0)" class="uiface_pile_face"> <img width="24" height="24" tip="'+name+'" class="tip_up_black" src="'+img+'"> </a> </li>');
				
				function set_voting_number(obj,number){
					$.each(obj,function(){
						
						if($(this).find("li[uid="+uid+"]").size()!=0 || number==1){	// 当前，循环对象判断含有该ID
							pollResultBar = $(this).children(".poll_result_bar");
							votingNum = parseInt(pollResultBar.attr("tip"));
							pollResultBar.children(".shaded").css("width",(votingNum+number)/total*100+"%");
							pollResultBar.attr("tip",votingNum+number+"票");
							tipId = pollResultBar.attr("tipId");
							$("body").children("div[tipId="+tipId+"]").find("div.bg").text(votingNum+number+"票");
						}
					});
					
				}
				function set_voting_number_more(o,p,number){
					var bfb;
					function set(pollResultBar,vote,bfb){
						pollResultBar.children(".shaded").css("width",bfb+"%");
						pollResultBar.attr("tip",vote+"票");
						tipId = pollResultBar.attr("tipId");
						$("body").children("div[tipId="+tipId+"]").find("div.bg").text(vote+"票");
					}
					$.each(p,function(){
						pollResultBar = $(this).children(".poll_result_bar");
						votingNum = parseInt(pollResultBar.attr("tip"));
						bfb = ((votingNum)/total*100)
						set(pollResultBar,votingNum,bfb)
					});
					pollResultBar = o.children(".poll_result_bar");
					votingNum = parseInt(pollResultBar.attr("tip"));
					votingNum+number<=0?bfb=0:bfb = (votingNum+number)/total*100;
					set(pollResultBar,votingNum+number,bfb)
				}
				function set_face(obj,op){
					$.each(obj,function(){
						$pile_list = $(this).find(".pile_list");
						if(op=="del"){
							$pile_list.find("li[uid="+uid+"]").remove();
						}else{
							$pile_list.prepend($pile);
							self.plug(["tip_up_black"],[$pile_list]);
						}
					});
				}
				if(arg[2]=="radio"){
					set_voting_number(arg[0],1);//焦点+1
					set_face(arg[0],"add",arg[1]);
					set_voting_number(arg[0].siblings("ul.block"),-1);//其他-1
					set_face(arg[0].siblings("ul.block"),"del");
				}else{
					var allUl = arg[0].parent().find("ul.block");
					if(arg[3]){
						set_voting_number_more(arg[0],allUl,-1); 
						set_face(arg[0],"del");
					}else{
						set_voting_number_more(arg[0],allUl,1); 
						set_face(arg[0],"add");
					}
				}
				
			},
			add_options:function(arg){
				if(arg[1].state==1){
					var value = arg[1].data.options
					var $newOption = $('<ul class="block clearfix" pollid="'+arg[2]+'" qid="'+arg[1].data.id+'"><li class="poll_btn"><input type="'+arg[3]+'" name="option_id'+arg[2]+'" checked="checked"></li><li class="poll_result_bar tip_up_right_black" tip="0票"><div style="width:0%" class="shaded"></div><div class="label">'+value+'</div></li><li class="target_event"></li><li class="poll_result_facepile"><ul class="pile_list clearfix"></ul></li></ul>');
					arg[0].before($newOption);
					self.iefix2([arg[0].prev()]);
					if(arg[4]==99){
						arg[0].hide();
					}
				}else{
					alert(arg[1].msg);
				}
			},
			del_options:function(arg){
				if(arg[1].state==1){
					arg[0].parent().remove();
					self.ask_operation.find("span.on").click();
				}
			},
			add_comments:function(arg){
				if(arg[1].state==1){
					var a = arg[1].data;
					var id = a.id,name = a.username,img = a.img,msg = a.msg,time = a.dateline,qids = a.qids;
					var textArr = [];
					var str = ""
					if(qids){
						$.each(qids,function(i,v){
							var $liText = self.askDetail.find("ul[qid="+v+"]").find(".label").text();

							textArr.push('"<span class="question">'+$liText+'</span>"');
						})
						if(textArr.length>0){
							str = "选择了 ";
							str+= textArr.join(" 、 ");
						}
					}
					var $newComment = $('<div frmid="'+id+'" class="comment clearfix"><span class="ui_closeBtn_box"><i class="ui_closeBtn png"></i></span><div class="reply_user"><a href="javascript:void(0);"><img width="50" border="0" height="50" src="'+img+'"></a></div><div class="reply_content"><span class="name">'+name+'</span><span class="text">'+str+'</span><span class="content">'+msg+'</span><div class="operation  clearfix" commentObjId="'+id+'" pageType="ask"></div></div></div>');
					arg[0].find("div.comments_box").prepend($newComment);
					
					
					self.plug(["commentEasy"],[arg[0].find("div.comments_box").find("div.operation")]);
					self.event("askDetail_note",$newComment);
					self.event("delete_comment",$newComment);
					self.event("postlike",$newComment);
					self.plug(["msg"],[$newComment]);
				}
			},
			del_comments:function(arg){
				if(arg[1].state==1){
					arg[0].parent().remove();
				}
			},
			eidt:function(arg){
				var $ul = arg[0].find("ul.block");
				var $li = $('<li class="editLi"></li>');
				var $c	= $('<a class="delete">x</a>');
				$li.click(function(e){
					var data = {};
					var elm = $(this);
					data.options_id = elm.parent().attr("qid");
					self.plug(["popUpDelete"],[elm,"确定要删除该项吗？","提示",function(){
						self.model("del_options",[data,function(json){
							self.view(["del_options"],[elm,json]);
						},arg[0]]);
					}]);
					e.stopPropagation();
				});
				$li.mouseover(function(){
					$(this).children().addClass("hover");
					$(this).prev().prev().trigger("mouseover");
					//$(this).prev().prev().prev().trigger("mouseover");
				})
				$li.mouseout(function(){
					$(this).children().removeClass("hover");
					$(this).prev().prev().trigger("mouseout");
					//$(this).prev().prev().prev().trigger("mouseout");
				})
				$li.append($c.clone(true));
				$.each($ul,function(){
					$(this).append($li.clone(true));
					$(this).children("li.poll_result_facepile").hide();
					$(this).find("input").attr("disabled",true);
				});
				arg[0].find("div.add_new_item").hide();
				self.iefix(["height"],[arg[0]]);
			},
			eidtOk:function(arg){
				var $ul = arg[0].find("ul.block");
				$.each($ul,function(){
					$(this).find("li.editLi").remove();
					$(this).children("li.poll_result_facepile").show();
					$(this).find("input").attr("disabled",false);
				});
				arg[0].find("div.add_new_item").show();
			},
			cancelVote:function(arg){
				if(arg[1].state==1){
					var data = {};
					data.poll_id = self.askDetail.attr("frmid");
					self.model("one_ask",[data,function(data){
						self.view(["askDetail"],[self.askDetail,data]);
					}]);
				}
			},
			list_voters:function(arg){
				if(arg[1].state==1){
					var a = arg[1].data,str="";
					$.each(a,function(i,v){
						var id = v.uid,name = v.username,img = v.avatar,status = v.status||0,dkou = v.dkou,link_url = v.link_url;
						switch(status){
							case "0":
								status = '<div class="ding_focus friendBtns"><a class="btn">关注</a></div><div class="cancel_focus friendBtns" style="display:none"><a class="btn">取消关注</a></div>';	//未关注
							break;
							case "1":
								status = '<div class="add_friends friendBtns"><a class="btn"><i class="followed"></i><span>加为好友</span></a></div><div class="friendBtns tip_up friend_invite" style="display:none"><a class="btn"><i class="followed"></i><span>已发送好友请求</span></a></div>';		//未好友
							break
							case "2":
								status = '<div class="add_friends friendBtns" style="display:none"><a class="btn"><i class="followed"></i>加为好友</a></div><div class="friendBtns tip_up friend_invite"><a class="btn"><i class="followed"></i><span>已发送好友请求</span></a></div>';				// 未接受
							break
							case "3":
								status = '<div class="friendBtns tip_up my_friends"><a class="btn"><i class="followed"></i><span>好友</span></a></div><div class="friendBtns tip_up add_friends" style="display:none"><a class="btn"><i class="followed"></i><span>加为好友</span></a></div><div class="friendBtns tip_up friend_invite" style="display:none"><a class="btn"><i class="followed"></i><span>已发送好友请求</span></a></div>';  // 
							break
							case "4":
								status = '<div class="ding_focus friendBtns"  style="display:none"><a class="btn">关注</a></div><div class="cancel_focus friendBtns"><a class="btn">取消关注</a></div>';
							break
							case "8":
								status = "";
							break
						}
						str += '<li class="other_voters clearfix" frmid="'+id+'"><a href="'+link_url+'"><img src="'+img+'" width="32" height="32" /></a><div class="content"><strong><a href="'+link_url+'">'+name+'</a></strong><span class="status">'+status+'</span></div></li>';
					});
					arg[0].html("<ul>"+str+"</ul>");
				}
				self.event("list_voters",arg[0]);
			}
		}
		$.each(method,function(index,value){
			if(value){
				return _class[value](arg);
			}
		});
		

	},
	plug:function(method,arg){
		var self = this;
		var _class = {
			tip_up_right_black:function(arg){
				arg[0].find(".tip_up_right_black").tip({
					direction:"up",
					position:"right",
					skin:"black"
				});
			},
			tip_up_black:function(arg){
				arg[0].find(".tip_up_black").tip({
					direction:"up",
					skin:"black"
				});
			},
			tip_right_black:function(arg){
				arg[0].find(".tip_right_black").tip({
					direction:"right",
					skin:"black"
				});
			},
			tip_up_djax:function(arg){
				arg[0].find(".tip_up_djax").tip({
					direction:"up",
					djax:true,
					hold:true
				});
			},
			tip_up:function(arg){
				arg[0].tip({
					direction:"up",
					width:"auto",
					showOn:"click",
					content:arg[1],
					key:arg[2],
					hold:true
				});
			},
			msg:function(arg){
				arg[0].find("[msg]").msg();
			},
			commentEasy:function(arg){
				arg[0].commentEasy({
					minNum:3,
					UID:CONFIG['u_id'],
					userName:CONFIG['u_name'],
					avatar:CONFIG['u_head'],
					userPageUrl:'http:\/\/'+CONFIG['domain']+'main/index/profile?dkcode='+$('#dkou').val()
				});
			},
			popUp:function(arg){
				arg[0].popUp({
					width:580,
					title:arg[2],
					content:arg[1],
					buttons:'<span class="popBtns blueBtn callbackBtn">确定</span><span class="popBtns closeBtn">取消</span>',
					mask:true,
					maskMode:true,
					callback:function(){
						var data = {};
						data.poll_id = arg[1].attr("pid");
						data.src_uid = [];
						$.each(arg[1].find("div.friends_list").children().children(),function(){
							if($(this).find("input").attr("checked")){
								data.src_uid.push($(this).attr("id"));
							}
						});
						data.src_uid = data.src_uid.join(",");
						self.model("ask_friends",[data,function(){
							
						}])
						$.closePopUp();
					}
				});
			},
			popUpAskDetail:function(arg){
				arg[0].popUp({
					width:580,
					title:arg[2],
					content:arg[1],
					mask:true,
					buttons:'<span class="popBtns closeBtn">关闭</span>',
					maskMode:true,
					callback:function(){
						$.closePopUp();
					},
					closeCallback: function(){
					//	self.ask_operation.find("span.on").click();
					}
				});
			},
			popUpVotersDetail:function(arg){
				arg[0].subPopUp({
					width:580,
					title:arg[2],
					content:arg[1],
					mask:true,
					buttons:'<span class="popBtns closeBtn">关闭</span>',
					maskMode:true,
					callback:function(){
						$.closeSubPop();
					},
					closeCallback: function(){
						//self.ask_operation.find("span.on").click();
					}
				});
			},
			popUpDelete:function(arg){
				$.confirm(arg[2],arg[1],arg[3]);
			},
			popFriendDetail:function(arg){
				$.confirm(arg[2],arg[1],arg[3]);
			},
			uicombox:function(arg){
				arg[0].uiCombox({
					selectorName: '按姓名搜索',
					lis: [{ref:0,text:'按姓名搜索'},{ref:1,text:'已选'}],
					width:100,
					defaultSelect:0,
					callback:function(){
						//self.view(["filter_friend"],arg[0]);
					}
				});
			},
			shareDestinationObjects:function(arg){
				$('#shareRights').dropdown({
					top: 22,
					position: 'right',
					permission:{
						type: 'ask'
					}
				});
			},
			search_friends:function(arg){
				ViolenceSearch.init({
					input: arg[0],
					resource: arg[1].data,
					filter: arg[2],
					filterWord: 'username',
					filterKey: 'usr_id',
					isFilterSelected:true,
					callback: function(data){
						if(data){
							arg[2].find("li").hide();
							$.each(data,function(index,a){
								var id = a.item.usr_id;
								arg[2].find("li[id="+id+"]").show();
							});
						}else{
							arg[2].find("li").show();
						}
						
					},
					descend: false
				});
			}

		}
		$.each(method,function(index,value){
			if(value){
				return _class[value](arg);
			}
		});
	},
	event:function(type,dom){
		var self = this;
		switch(type){
			case "askDetail_note":
				dom.find("span.comment_add").click(function(){
					$(this).closest("div.comment").find("ul.comment_content").show();
				});
				
			break;
			case "delete_comment":
				dom.find("i.ui_closeBtn").hover(function(){
					$(this).css("background-position","0px -15px");
				},function(){
					$(this).css("background-position","0px 0px");
				})
				dom.hover(function(){
					$(this).children("span.ui_closeBtn_box").show();
				},function(){
					$(this).children("span.ui_closeBtn_box").hide();
				});
				dom.find("span.ui_closeBtn_box").unbind("click").bind("click",function(e){
					var data = {};
					var elm = $(this);
					
					data.id = elm.closest("div.comment").attr("frmid");
					data.frmid = self.askDetail.attr("frmid");
					
					self.model("del_comments",[data,function(json){
						self.view(["del_comments"],[elm,json]);
						self.ask_operation.find("span.on").click();
					}]);
					e.stopPropagation();
					return false;
				});
			break;
			
			
			case "askDetail_item":
				self.event("new_item",self.askDetail);
				self.event("item_box",self.askDetail);
				self.askDetail.find("div.reply_input").find("input").focus(function(){
	
					$(this).parent().find("div.reply_btn").show();
				});
				self.askDetail.find("div.reply_btn").unbind("click").bind("click",function(){
					var data = {};
					var elm = $(this);
					
					data.frmid = self.askDetail.attr("frmid");
					data.message = $(this).parent().find("input").val();
					data.qid = [];
					$.each(self.askDetail.find("div.item_box").children(),function(){
						var $input = $(this).find("input");
						if($input.attr("checked")){
							data.qid.push($(this).attr("qid"));
						}
					})
					data.askuid = self.askuid;
					if(data.message==""){
						return false;
					}
					self.model("add_comments",[data,function(data){
						self.view(["add_comments"],[self.askDetail,data])
						self.ask_operation.find("span.on").click();
						elm.parent().find("input").val("");
					}])
				});
				self.askDetail.find("a.cancelVote").unbind("click").bind("click",function(){
					var elm = $(this);
					var data = {};
					data.poll_id = self.askDetail.attr("frmid");
					self.model("cancel_allvote",[data,function(json){
						self.view(["cancelVote"],[self.askDetail,json]);
						elm.remove();
					
					},elm]);
				});
				self.askDetail.find("a.deleteAsk").unbind("click").bind("click",function(){
					var elm = $(this);
					var data = {};
					data.poll_id = self.askDetail.attr("frmid");
					self.plug(["popUpDelete"],[$(this),"确定要删除该项吗？","提示",function(){
						self.model("del_asks",[data,function(json){
							self.view(["del_asks"],[json]);
							self.ask_operation.find("span.on").click();
						},elm]);
					}]);
				});
				

				
				self.askDetail.find("div.ding_focus").unbind("click").bind("click",function(){
					var elm = $(this);
					var data = {};
					data.object_id = self.askDetail.attr("frmid")+",ask";
					self.model("post_objectfollow",[data,function(data){
						if(data.state=="0"){
							alert(data.msg);
							return false;
						}
						elm.hide();
						elm.next().show();
					},elm])
				});

				self.askDetail.find("div.cancel_focus").unbind("click").bind("click",function(){
					var elm = $(this);
					var data = {};
					data.object_id = self.askDetail.attr("frmid")+",ask";
					self.model("del_objectfollow",[data,function(data){
						if(data.state=="0"){
							alert(data.msg);
							return false;
						}
						elm.hide();
						elm.prev().show();
					},elm])
				});
				self.askDetail.find("a.editAsk").unbind("click").bind("click",function(){
					var $editOk = $('<div class="editOk"><span>编辑完成</span></div>');
					self.askDetail.find("div.item_box").append($editOk);
					$editOk.unbind("click").bind("click",function(){
						self.view(["eidtOk"],[self.askDetail]);
						$(this).remove();
						var data = {};
						data.poll_id = self.askDetail.attr("frmid");
						self.model("one_ask",[data,function(data){
							self.view(["askDetail"],[self.askDetail,data]);
						}]);
					});
					self.view(["eidt"],[self.askDetail]);
				});
				self.askDetail.find("div.ask_friends").unbind("click").bind("click",function(){
					var elm = $(this);
					var id = self.askDetail.attr("frmid");
					new CLASS_FRIENDS_LIST({
						title:"好友列表",
						detail:self.friends_detail,
						id:id,
						uid:self.uid,
						elm:elm,
						getUrl:self.webpath+"?c=newask&m=searchFriend",
						postUrl:self.webpath+"?c=newask&m=askFriend"
					});
				});
				
			break;
			case "friends_list":
				self.friends_detail.find(".friends_list li").hover(function(){
					
					if($(this).attr("class")!="chekced"){
						$(this).css("background","#eceff5");
					}
				},function(){
					
					if($(this).attr("class")!="checked"){
						$(this).css("background","");
					}
				}).unbind("click").bind("click",function(){
					if($(this).find("input").attr("checked")){
						$(this).addClass("chekced");
					}else{
						$(this).removeClass("chekced");
					}
				});
				self.friends_detail.find("div.uiComboxMenu").find("li[ref=1]").unbind("click").bind("click",function(){
					$.each(self.friends_detail.find(".friends_list").find("li"),function(){
						if($(this).find("input").attr("checked")){
						
							$(this).show();
						}else{
							$(this).hide();
						}
					});
				});
				self.friends_detail.find("div.uiComboxMenu").find("li[ref=0]").unbind("click").bind("click",function(){
					self.friends_detail.find(".search_bar").find("input").val("");
					self.friends_detail.find(".friends_list").find("li").show();
				});
				
			break;
			case "askList":
				dom.find("div.add_new_item").find("input").focus(function(){
					$(this).closest("table").find("td:last").show();
				});
				dom.find(".other_item").find("span").hover(function(){
					$(this).css({ "background":"#ebeff4","border-color":"#6d84b4"});
				},function(){
					$(this).css({ "background":"","border-color":"#BECBDD"});
				});
				
				dom.unbind("click").bind("click",function(e){
					if($(e.target).attr("class")=="other_item_span"||$(e.target).attr("name")=="question"||$(e.target).attr("name")=="answer"){
						$(e.target).closest("li.li").find(".dialog_pipe").trigger("click");
					}
				})
				dom.find("span.choice_friends").unbind("click").bind("click",function(){
					var elm = $(this);
					var id = elm.closest("li.li").attr("id");
					//var poll_id = 
					new CLASS_FRIENDS_LIST({
						title:"好友列表",
						detail:self.friends_detail,
						id:id,
						uid:self.uid,
						elm:elm,
						getUrl:self.webpath+"?c=newask&m=searchFriend",
						postUrl:self.webpath+"?c=newask&m=askFriend"
					});
				/*	return false;
					self.model("get_friends",[{id:id},function(data){
						self.friends_detail.attr("pid",id);
						self.view(["friends_list"],[self.friends_detail.children(".friends_list"),data]);
					
						self.plug(["popUp"],[elm,self.friends_detail,"邀请好友"]);
						self.plug(["uicombox"],[$("div.selectFriendType")]);
						self.plug(["search_friends"],[self.friends_detail.find(".search_bar").find("input"),data,self.friends_detail.find("div.friends_list")])
						self.event("friends_list");
						
					}]);*/
				});
				dom.find("span.ding_focus").unbind("click").bind("click",function(){
					var elm = $(this);
					var data = {};
					data.object_id = $(this).closest("li.li").attr("id")+",ask";
					self.model("post_objectfollow",[data,function(data){
						if(data.state=="0"){
							alert(data.msg);
							return false;
						}
						elm.hide();
						elm.next().show();
					},elm])
				});
				dom.find("span.cancel_focus").unbind("click").bind("click",function(){
					var elm = $(this);
					var data = {};
					data.object_id = $(this).closest("li.li").attr("id")+",ask";
					self.model("del_objectfollow",[data,function(data){
						if(data.state=="0"){
							alert(data.msg);
							return false;
						}
						elm.hide();
						elm.prev().show();
					},elm])
				});
				dom.find(".dialog_pipe").unbind("click").bind("click",function(){
					var question = $(this).closest("li.li").find("a[name=question]").text();
					var data = {};
					data.poll_id = $(this).closest("li.li").attr("id");
					self.plug(["popUpAskDetail"],[$(this),self.askDetail,question]);
					self.model("one_ask",[data,function(data){
						self.view(["askDetail"],[self.askDetail,data]);
						
						
					}]);
					self.model("list_comments",[{frmid:data.poll_id},function(data){
						self.view(["note"],[self.askDetail,data]);
					}]);
					
				});
				
			break;
			case "otherPerson":
				dom.find("li.otherPerson").unbind("click").bind("click",function(){
					var data = {};
					
					data.poll_id = $(this).closest("ul.block").attr("pollid");
					data.options_id = $(this).closest("ul.block").attr("qid");
					self.list_voters_detail = $("<div id='list_voters_detail'></div>");
					self.plug(["popUpVotersDetail"],[$(this),self.list_voters_detail,"投选了这选项的人"]);
					
					self.model("list_voters",[data,function(data){
						self.view(["list_voters"],[self.list_voters_detail,data]);
						$.resetPopUp();
						self.plug(["tip_up"],[self.list_voters_detail.find("div.friend_invite"),"<a class='cancel_request'>取消请求</a>","1"]);
						self.plug(["tip_up"],[self.list_voters_detail.find("div.my_friends"),"<a class='del_friend'>解除好友关系</a>","2"]);
						self.plug(["tip_up"],[self.list_voters_detail.find("div.each_followed"),"<a class='add_friends'>加为好友</a> · <a class='unfollow'>取消关注</a>","2"]);
						self.event(["list_voters"],[self.list_voters_detail]);
					}]);
					
				});
			break;
			case "item_box":
				$("div.add_new_item").find("input").focus(function(){
					$(this).closest("table").find("td:last").show();
				});
				$("div.add_new_item").find(".uiButton").unbind("click").bind("click",function(){
					var elm 	= $(this);
					var p		= elm.closest("li.li");
					var pInput	= elm.closest("div.add_new_item").find("input");
					var muti	= p.attr("muti")||self.askDetail.attr("muti");
					var size 	= p.find("ul.block").size();
						if(muti=="0"){
							type = "radio";
						}else{
							type = "checkbox";
						}
					if(pInput.val()==""){
						pInput.focus();
						return false;
					}
					var data = {};
					data.poll_id = p.attr("id")||self.askDetail.attr("frmid");
					data.options = pInput.val();
					data.askuid = self.askuid;
					data.muti = type;
					var someName = false;
					if(p.size()==0){
						p = elm.closest("div.item_box");
					}
					
					$.each(p.find("div.label"),function(){
						if(data.options == $(this).text()){
							someName = true;
						}
					});
					if(someName){
						elm.closest("div.add_new_item").find("input").val("").focus();
						return false;
						
					}
		
					if(size>99){
						return false;
					}
					self.model(["add_options"],[data,function(json){
						var p = elm.closest("div.add_new_item");
						self.view(["add_options"],[p,json,data.poll_id,type,size]);
						var newItem = p.prev();
						self.view(["voting"],[newItem,json,type]);
						self.event("new_item",newItem);
						self.plug(["tip_up_right_black","tip_up_black","tip_right_black","msg"],[newItem]);
						elm.closest("div.add_new_item").find("input").val("").focus();
						//self.ask_operation.find("span.on").click();
					},elm]);
				});
			break;
			
			case "new_item":
	
				dom.find("li.target_event").unbind("mouseover").bind("mouseover",function(){
					$(this).prev().css("border","1px solid #3b5998");
					$(this).prev().mouseover();
					
					return false;
				});
				dom.find("li.target_event").unbind("mouseout").bind("mouseout",function(){
					$(this).prev().css("border","1px solid #bbbbbb");
					$(this).prev().mouseout();
					return false;
				}).unbind("click").bind("click",function(e){
					$(this).prev().prev().children().trigger("click");
					return false; //e.stopPropagation();
				});
			
				dom.find("li.poll_btn").children().each(function(){
					$(this).unbind("click").bind("click",function(){
						var checked = $(this).attr("checked");
						var pLi = $(this).closest("li.li");
						var p = $(this).closest("ul.block");
						var type = $(this).attr("type");
						var data = {};
						data.poll_id = pLi.attr("id")||self.askDetail.attr("frmid");
						data.options_id = p.attr("qid");
						data.muti = type;
						data.askuid = self.askuid;

						if(type=="radio"){
							if(checked){
								return false;
							}else{
								
								self.model("add_voting",[data,function(data){
									self.view(["voting"],[p,data,type]);
								},p]);
							}
						}else{
							
							if(checked){
								self.model("add_voting",[data,function(data){
									self.view(["voting"],[p,data,type,checked]);
								},p]);
							}else{
								self.model("add_voting",[data,function(data){
									self.view(["voting"],[p,data,type,checked]);
								},p]);
							}
						}
						//self.ask_operation.find("span.on").click();
					});
				});
			break;
			case "list_voters":
				dom.find("div.ding_focus").unbind("click").bind("click",function(){
					var elm = $(this);
					var data = {};
					data.f_uid = $(this).closest("li").attr("frmid");
					self.model("add_follow",[data,function(data){
						if(data.state=="0"){
							alert(data.msg);
							return false;
						}
						elm.hide();
						elm.next().show();
					},elm])
				});
				dom.find("div.cancel_focus").unbind("click").bind("click",function(){
					var elm = $(this);
					var data = {};
					data.f_uid = $(this).closest("li").attr("frmid");
					self.model("unfollow",[data,function(data){
						if(data.state=="0"){
							alert(data.msg);
							return false;
						}
						elm.hide();
						elm.prev().show();
					},elm])
				});
				dom.find("div.unfollow").unbind("click").bind("click",function(){
					var elm = $(this);
					var data = {};
					data.f_uid = $(this).closest("li").attr("frmid");
					self.model("unfollow",[data,function(data){
						if(data.state=="0"){
							alert(data.msg);
							return false;
						}
						elm.hide();
						elm.prev().show();
					},elm])
				});
				dom.find("div.add_friends").unbind("click").bind("click",function(){
					var elm = $(this);
					var data = {};
					data.f_uid = $(this).closest("li").attr("frmid");
					self.model("friend_invite",[data,function(data){
						if(data.state=="0"){
							alert(data.msg);
							return false;
						}
						elm.hide();
						elm.prev().hide();
						elm.next().show();
					},elm])
				});
				$("body").undelegate();
				$("body").delegate("a.cancel_request","click",function(){
					var elm = $(this);
					var data = {};
					var div = $(this).closest("div.tip_win");
					var pDiv = dom.find("div[tipid="+div.attr("tipid")+"]");
					var li = pDiv.closest("li")
					data.f_uid = li.attr("frmid");
					self.model("cancel_request",[data,function(data){
						if(data.state=="0"){
							alert(data.msg);
							return false;
						}
						div.remove();
						pDiv.hide();
						pDiv.prev().show();
					},elm])
				});
				$("body").delegate("a.del_friend","click",function(){
					var elm = $(this);
					var data = {};
					var div = $(this).closest("div.tip_win");
					var pDiv = dom.find("div[tipid="+div.attr("tipid")+"]");
					var li = pDiv.closest("li")
					data.f_uid = li.attr("frmid");
					self.model("del_friend",[data,function(data){
						if(data.state=="0"){
							alert(data.msg);
							return false;
						}
						div.remove();
						pDiv.hide();
						pDiv.next().show();
					},elm])
				});
			break;
			case "init":
				self.my_askList.unbind("click").bind("click",function(){
					self.my_askList_box.show();
					self.friend_askList_box.hide();
					self.friend_askList.attr("class","");
					self.model("my_asks",[,function(data){
						self.view(["askList"],[self.my_askList_box,data]);
						self.plug(["tip_up_right_black","tip_up_black","tip_right_black","tip_up_djax","msg"],[self.my_askList_box]);
						
						self.event("askList",self.my_askList_box);
						self.event("otherPerson",self.my_askList_box);
						self.event("item_box");
						self.event("new_item",self.my_askList_box);

					},self.my_askList_box.find("div.ask_more")])
					$(this).attr("class","on");
					
				});
				self.friend_askList.unbind("click").bind("click",function(){
					self.friend_askList_box.show();
					self.my_askList_box.hide();
					self.my_askList.attr("class","");
					self.model("friend_asks",[,function(data){
						self.view(["askList"],[self.friend_askList_box,data]);
						self.plug(["tip_up_right_black","tip_up_black","tip_right_black","tip_up_djax","msg"],[self.friend_askList_box]);
						self.event("askList",self.friend_askList_box);
						self.event("otherPerson",self.friend_askList_box);
						self.event("item_box");
						self.event("new_item",self.friend_askList_box);

					},self.friend_askList_box.find("div.ask_more")])
					$(this).attr("class","on");
					
				});
				self.my_askList_box.find("div.ask_more").unbind("click").bind("click",function(){
					var elm = $(this);
					var page = parseInt(elm.attr("page"))+1;
					if(elm.hasClass("disabled")){
						return false;
					}
						elm.attr("page",page);
					var li = self.my_askList_box.find("li.li").last();
					self.model("my_asks",[{nowpage:page},function(data){
						if(data.state==0){
							elm.hide();
							return false;
						}
						self.view(["askList"],[self.my_askList_box,data,"more"]);
						var new_box = li.nextAll();
						self.plug(["tip_up_right_black","tip_up_black","tip_right_black","tip_up_djax","msg"],[new_box]);
						
						self.event("askList",new_box);
						self.event("otherPerson",new_box);
						self.event("item_box");
						self.event("new_item",new_box);

					},$(this).children()]);
					
				});
				self.friend_askList_box.find("div.ask_more").unbind("click").bind("click",function(){
					var page = parseInt($(this).attr("page"))+1;
					var elm = $(this);
					if(elm.hasClass("disabled")){
						return false;
					}
						elm.attr("page",page);
					var li = self.friend_askList_box.find("li.li").last();
					self.model("friend_asks",[{nowpage:page},function(data){
						if(data.state==0){
							elm.hide();
							return false;
						}
						self.view(["askList"],[self.friend_askList_box,data,"more"]);
						var new_box = li.nextAll();
						self.plug(["tip_up_right_black","tip_up_black","tip_right_black","tip_up_djax","msg"],[new_box]);
						self.event("askList",new_box);
						self.event("otherPerson",new_box);
						self.event("item_box");
						self.event("new_item",new_box);

					},$(this).children()]);
				});
				self.addask.toggle(function(){
					self.addNewAsk.show();
					self.plug(["msg"],[self.addNewAsk]);
				},function(){
					self.addNewAsk.hide();
				});
				self.setting_voting.click(function(){
					$(this).hide();
					self.votingBox.show();
					self.votingBox.find("input:first").focus();
					
					self.setting_moreOptions.show();
					self.setting_more.show();
				});
				self.votingBox.find("input:last").live("focus",function(){
					if(self.votingBox.find("div.voting_option").children().size()>9){
						return false;
					}
					var newVoting = $('<div class="new_item"><input type="text" maxlength="80"  msg="添加选项" ></div>')
					self.votingBox.find("div.voting_option").append(newVoting);
					
					self.plug(["msg"],[newVoting]);
				});
				
				self.add_asks.click(function(){
					var data = {};
					var question = $.trim(self.inputBox.children("input").val());
					if(!question){
						self.inputBox.children("input").val("").focus();
						return false;
					}
					data.title = question;
					data.options = [];
					data.permission = self.$permission.val();
					$.each(self.votingBox.find("div.new_item"),function(){
						var v = $.trim($(this).find("input").val());
						if(v){
							data.options.push(v);
						}
					});
					
					data.allow = self.add_allPerson.attr("checked")?1:0;
					data.muti = self.addNewAsk.find("input[name=moreRadio]:checked").val();
					
					if(data.allow==0&&data.options.length<2){
						if($.trim(self.addNewAsk.find("div.new_item:first").find("input").val())==""){
							self.addNewAsk.find("div.new_item:first").find("input").focus();
						}else{
							self.addNewAsk.find("div.new_item:eq(1)").find("input").focus();
						}
						return false;
					}
					self.model("add_asks",[data,function(data){
						if(data.state==1){
							self.addask.trigger("click");
							self.my_askList.trigger("click");
							self.addNewAsk.find("input[type=text]").val("").blur();
						}
					}]);
				});
				
				self.addNewAsk.find("#add_allPerson").click(function(){
					if($(this).attr("checked")){
						
						self.setting_more.find("input:first").attr("disabled",true).attr("checked",false).parent().addClass("disabled");
						self.setting_more.find("input:last").attr("checked",true);
					}else{
						self.setting_more.find("input:first").attr("disabled",false).parent().removeClass("disabled");
					}
				});
				
			break;
		
			default:
			break;
		}
	},
	iefix:function(method,arg){
		var self = this;
		var _class={
			height:function(arg){
				if($.browser.msie&&($.browser.version=="6.0")){
					var h,shaded,target,editLi;
					var $ul = arg[0].children().find("ul.block");
					$.each($ul,function(){
						 h=$(this).height();
						 shaded = $(this).find("div.shaded");
						 target = $(this).find("li.target_event");
						 editLi = $(this).find("li.editLi");
						 if(editLi.size()!=0){
						 	editLi.height(h);
						 }else{
						 	shaded.height(h-2);
						 	target.height(h);
						 }
					});
				}
			}
		}
		return _class[method](arg);
	},
	iefix2:function(arg){
		var h,shaded,target,editLi;
		h=arg[0].height();
		shaded = arg[0].find("div.shaded");
		target = arg[0].find("li.target_event");
		editLi = arg[0].find("li.editLi");
		if(editLi.size()!=0){
		editLi.height(h);
		}else{
		shaded.height(h-2);
		target.height(h);
		}
	},
	model:function(method,arg){
		var self = this;
		var _class={
			list_voters:function(arg){
				$.djax({
					//obj:arg[2],
					//loading:true,
					url:self.webpath+"?c=newask&m=listVoter",
					dataType:"json",
					
					data:arg[0],
					success:arg[1]
				});
			},
			friend_asks:function(arg){
				var dkou;
				if(self.dkou){
					dkou = "&action_dkcode="+self.dkou;
				}else{
					dkou="";
				}
				$.djax({
					//obj:arg[2],
					//loading:true,
					url:self.webpath+"?c=newask&m=listAsk"+dkou,


					dataType:"json",
					
					data:arg[0],
					//relative:true,
					success:arg[1]
				});
			},
			my_asks:function(){
				$.djax({
					//obj:arg[2],
					//loading:true,
					url:self.webpath+"?c=newask&m=listAsk&getmy=1",
					dataType:"json",
					
					data:arg[0],
					success:arg[1]
				});
			},
			add_asks:function(arg){
				$.djax({
					url:self.webpath+"?c=newask&m=addAsk",
					data:arg[0],
					success:arg[1]
				});
			},
			del_asks:function(arg){
				$.djax({
					url:self.webpath+"?c=newask&m=delAsk",
					data:arg[0],
					success:arg[1]
				});
				
			},
			add_options:function(arg){
				var url = self.webpath+"?c=newask&m=addOption";
				
				$.djax({
					el:arg[2],
					loading:true,
					url:url,
					data:arg[0],
					success:arg[1]
				});
			},
			del_options:function(arg){
				var url = self.webpath+"?c=newask&m=delOption";
				
				$.djax({
					el:arg[2],
					loading:true,
					url:url,
					data:arg[0],
					success:arg[1]
				});
			},
			del_votes:function(arg){
				$.djax({
					url:self.webpath+"?c=newask&m=del_votes",
					data:arg[0],
					success:arg[1]
				});
			},
			add_voting:function(arg){
				$.djax({
					obj:arg[2],
					loading:true,
					relative:true,
					async:true,
					aborted:false,
					url:self.webpath+"?c=newask&m=addVote",
					data:arg[0],
					success:arg[1]
				});
				
				// 判断单选还是多选
			},
			get_friends:function(more){
				$.djax({
					url:self.webpath+"?c=newask&m=searchFriend",
					dataType:"json",
					data:arg[0],
					success:arg[1]
				});
				// 判断单选还是多选
			},
			ask_friends:function(){
				$.djax({
					url:self.webpath+"?c=newask&m=askFriend",
					dataType:"json",
					data:arg[0],
					success:arg[1]
				});
			},
			one_ask:function(){
				$.djax({
					obj:self.askDetail,
					url:self.webpath+"?c=newask&m=singleAsk",
					
					loading:true,
					relative:true,
					data:arg[0],
					success:arg[1]
				});
			},
			list_comments:function(arg){
				$.djax({
					
					url:self.webpath+"?c=newask&m=listComment",
					
			
					relative:true,
					data:arg[0],
					success:arg[1]
				});
			},
			add_comments:function(){
				$.djax({
					obj:$("#askDetail_note"),
					url:self.webpath+"?c=newask&m=addComment",
					type:"POST",
					data:arg[0],
					loading:true,
					relative:true,
					success:arg[1]
				});
			},
			del_comments:function(){
				$.djax({
					obj:$("#askDetail_note"),
					url:self.webpath+"?c=newask&m=delComment",
					type:"POST",
					data:arg[0],
					loading:true,
					relative:true,
					success:arg[1]
				});
			},
			cancel_allvote:function(arg){
				$.djax({
					el:arg[2],
					url:self.webpath+"?c=newask&m=cancelVote",
					type:"POST",
					data:arg[0],
					loading:true,
					relative:true,
					success:arg[1]
				});
			},
			post_like:function(arg){
				$.djax({
					el:arg[2],
					url:self.webpath+"api/interact/post_like",
					type:"POST",
					data:arg[0],
					loading:true,
					relative:true,
					success:arg[1]
				});
			},
			del_like:function(arg){
				$.djax({
					el:arg[2],
					url:self.webpath+"api/interact/del_like",
					type:"POST",
					data:arg[0],
					loading:true,
					relative:true,
					success:arg[1]
				});
			},
			post_objectfollow:function(arg){
				$.djax({
					el:arg[2],
					url:self.webpath+"?c=objectfollow&m=post_objectfollow",
					type:"POST",
					data:arg[0],
					loading:true,
					relative:true,
					success:arg[1]
				});
			},
			del_objectfollow:function(arg){
				$.djax({
					el:arg[2],
					url:self.webpath+"?c=objectfollow&m=del_objectfollow",
					type:"POST",
					data:arg[0],
					loading:true,
					relative:true,
					success:arg[1]
				});
			},
			add_follow:function(arg){
				$.djax({
					el:arg[2],
					url:self.webpath2+"?c=api&m=addfollow",
					type:"POST",
					data:arg[0],
					loading:true,
					relative:true,
					success:arg[1]
				});
			},
			unfollow:function(arg){
				$.djax({
					el:arg[2],
					url:self.webpath2+"?c=api&m=unfollow",
					type:"POST",
					data:arg[0],
					loading:true,
					relative:true,
					success:arg[1]
				});
			},
			friend_invite:function(arg){
				$.djax({
					el:arg[2],
					url:self.webpath2+"?c=api&m=addFriend",
					type:"POST",
					data:arg[0],
					loading:true,
					relative:true,
					success:arg[1]
				});
			},
			cancel_request:function(arg){
				$.djax({
					el:arg[2],
					url:self.webpath+"api/user/cancel_request",
					type:"POST",
					data:arg[0],
					loading:true,
					relative:true,
					success:arg[1]
				});
			},
			del_friend:function(arg){
				$.djax({
					el:arg[2],
					url:self.webpath2+"?c=api&m=delFriend",
					type:"POST",
					data:arg[0],
					loading:true,
					relative:true,
					success:arg[1]
				});
			}
			
		}
		return _class[method](arg);
	}
}

$(document).ready(function(){
	//var index = location.href.lastIndexOf("/");
	//var url = location.href.slice(index,location.href.lenght);
	var temp = {};
	if($("#from_ask_notices").size()!=0){
		temp.from_ask_notices = {};
		temp.from_ask_notices.id = $("#from_ask_notices").val();
		temp.from_ask_notices.title = $("#from_ask_notices").attr("title");
	}
	new CLASS_ASK(temp);
});