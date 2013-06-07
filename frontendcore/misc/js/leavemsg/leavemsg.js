/**
 * @author:    chenhy(陈海云)
 * @created:   2012/06/29
 * @version:   v1.0
 * @desc:      留言板

 ======================

 ======================
 */
/*******************start:留言板主程序*********************/

function MsgBoard(arg) {
};

MsgBoard.template = {
	msg:[
		'<li class="message clearfix" msgid="${id}" uid="${uid}" uname="${username}">',
			'<div class="msgUserAvatar"><a href="${url}" title="${username}"><img src="${headpic}" height="50" width="50" alt="${username}"></a></div>',
			'<div class="msgContent">',
				'<div class="msgUserInfo"><a href="${url}">${username}</a> <span>${dateline}</span></div>',
				'<p>$${content}</p>',
				'<div class="editControl">',
					'<span class="conWrap midLine" tip="回复"><a title="回复"><strong class="answer">回复</strong></a></span>',
					'<span class="moreOperate dropMenu">',
						'<a title="更多操作" class="triggerBtn"><i class="conEdit"></i></a>',
						'{@if (curUserId == uid || isSelf) || sendmsg}',
							'<ul class="editMenu dropList">',
								'{@if curUserId == uid || isSelf}',
									'<li name="delMsg">删除</li>',
								'{@/if}',
								'{@if sendmsg}',
									'<li name="postLetter">发站内信</li>',
								'{@/if}',
								'<!--<li name="reportMsg">举报</li>-->',
							'</ul>',
						'{@/if}',
					'</span>',
				'</div>',

			'</div>',
			'<ul class="msgAnswerLst"></ul>',
			'<div class="msgAnswerInput"><input type="text" msg="留段话吧..." /><span +class="msgAnswerTip">提示:按回车键（enter）提交回复。</span></div>',
		'</li>'
	].join(""),
	answer:[
		'<li class="clearfix" answerid="${id}" uid="${uid}">',
			'<div class="userAvatar"><a href="${url}" title="${username}"><img src="${headpic}" height="32" width="32" alt="${username}" /></a></div>',
			'<div class="msgContent">',
				'<div class="msgUserInfo"><a href="${url}">${username}</a> <span>${dateline}</span></div>',
				'<p>$${content}</p>',
			'</div>',
			'<span class="pointer"></span>',
		'</li>'
	].join(""),
	users:[
		'{@each users as user,index}',
			'<span rel="${user.uid}">${user.uname}<a href="javascript:void(0)" class="deleteToken"></a></span>',
		'{@/each}'
	].join("")
};

MsgBoard.prototype = {
	init:function() {
		var self = this;
		var postLetterBtn = $("#jewelSendNewMessage");

		self.postBox = $("#myStatusTextArea");	// 留言发表框
		self.postBtn = $("#distributeButton");	// 留言发表按钮
		self.msgLst = $("#j_messageLst");		// 留言列表
		self.msgAmount = $("#j_msgAmount");		// 留言数量
		
		self.uid = $("#action_uid").val();		// 当前当前用户ID
		self.uname = $("#action_uid").val();	// 当前留言板用户ID
		
		self.curUserId = $("#uid").val();		// 当前用户id	
		self.curUserName = $("#username").val();// 当前用户名字
		self.curHeadpic = $("#avatar").val();	// 当前用户的头像地址

		self.isSelf	= (self.uid === self.curUserId);

		self.getMoreMsg = $("#j_getMore");		// 获取更多留言

		self.event({method:"postBox",param:{}});
		self.event({method:"getMoreMsg",param:{}});
		$("#J_face a").face(self.postBox);

		self.getMoreMsg.find("a").trigger("click");

		// 发送私信的方法
		MsgBoard.postLetter = function(users) {
			var userData = {users:users};

			postLetterBtn.trigger("click");
			$("#msgTokenareaList").html(juicer(MsgBoard.template["users"], userData));
		};
	},
	view:function(conf) {
		var self = this;
		var viewClass = {
			msg:function(msgData) {
				var _msgData = $.extend({
					curUserId:self.curUserId,
					//url:webpath + "main/index.php?c=index&m=index&action_dkcode=" + msgData.object_id,
					url:mk_url("main/index/main",{dkcode:msgData.object_id}),
					isSelf:self.isSelf
				},msgData);
				var $msg = $(juicer(MsgBoard.template.msg,_msgData));
				var $answerLst = $msg.find("ul.msgAnswerLst");

				self.view({method:"answerLst",param:{$content:$answerLst,data:_msgData.reply || []}});
				
				return $msg;
			},
			msgLst:function(param) {
				for(var i = 0, l = param.data.length; i < l; i ++) {
					var $msg = this.msg(param.data[i]);

					param.$content.append($msg);
					self.event({method:"msg",param:{$content:$msg}});
				}
			},
			answer:function(answerData) {
				var _answerData = $.extend({curUserId:self.curUserId,url:mk_url("main/index/main",{dkcode:answerData.object_id})/* url:webpath + "main/index.php?c=index&m=index&action_dkcode=" + answerData.object_id */},answerData);
				var answerHtml = juicer(MsgBoard.template.answer,_answerData);

				return $(answerHtml);
			},
			answerLst:function(param) {
				if(param.data.length === 0) {
					param.$content.hide();
				}
				for(var i = 0, l = param.data.length; i < l; i ++) {
					var $answer = this.answer(param.data[i]);

					param.$content.append($answer);
					self.event({method:"answer",param:{$content:$answer}});
				}
			},
			msgAmount:function(param) {
				var msgAmount = self.msgAmount.attr("amount") || 0;

				msgAmount = parseInt(msgAmount) + param.num;

				self.msgAmount.attr("amount",msgAmount).html(msgAmount + "条留言");
			}
		};
		var returnVal = null;

		if(conf.method) {
			if(typeof viewClass[conf.method] === "function") {
				returnVal = viewClass[conf.method].call(viewClass,conf.param);
			}
		}

		return returnVal;
	},
	event:function(conf) {
		var self = this;
		var eventClass = {
			postBox:function(param) {
				// 输入字数提示
				new Textarea.msgTip("#distributeMsg",{
					maxlength:140,
					notMedia:true,
					iswordwrap:true,
					button:{
						id:self.postBtn.parent(),
						activeClass:"",
						disableClass:"disable"
					}
				});
				// 文本框输入提示
				self.postBox.msg().focus().blur();

				self.postBtn.click(function() {
					var $this = $(this);

					if(!$this.parent().hasClass("disable")) {
						var msgContent = $.trim(self.postBox.val());
						self.model({
							method:"addMsg",
							param:{
								data:{
									action_uid:self.uid,
									content:msgContent
								},
								callback:function(data) {
									if(data.status === 1) {
										var msg = data.data;

										msg.headpic = self.curHeadpic;

										var $msg = self.view({method:"msg",param:msg});
										self.msgLst.prepend($msg);
										self.event({method:"msg",param:{$content:$msg}});
										self.postBox.val("");
										self.view({method:"msgAmount",param:{num:1}});
									} else {
										alert(data.msg);
									}
								}
							}
						});
					}
				});
			},
			msg:function(param) {
				var _self = this;
				var $msg = param.$content;
				var $operate = $msg.find("div.editControl"),
					$moreOpBtn = $operate.find("span.moreOperate");
					$answerBtn = $operate.find("strong.answer"),		// “回复留言”按钮
					$delBtn = $operate.find("li[name=delMsg]"),			// “删除留言”按钮
					$letterBtn = $operate.find("li[name=postLetter]"),	// “发送站内信”按钮
					$reportBtn = $operate.find("li[name=reportMsg]");	// “举报留言”按钮
				var $answerLst = $msg.find("ul.msgAnswerLst"),			// 回复列表
					$answerPost = $msg.find("div.msgAnswerInput"),		// 回复发表框
					$answerInput = $answerPost.find("input");

				_self.delMsg({$msg:$msg,$delBtn:$delBtn});
				_self.answerMsg({$msg:$msg,$answerBtn:$answerBtn,$answerLst:$answerLst,$answerPost:$answerPost,$answerInput:$answerInput});

				$operate.find("ul.editMenu").click(function() {
					$operate.find("span.moreOperate").removeClass("dropDown");
				});

				$moreOpBtn.hover(
					function() {
						$moreOpBtn.addClass("dropDown");
					},
					function() {
						$moreOpBtn.removeClass("dropDown");
					}
				);

				$letterBtn.click(function() {
					MsgBoard.postLetter([{uid:$msg.attr("uid"),uname:$msg.attr("uname")}]);
				});
			},
			delMsg:function(param) {
				var $msg = param.$msg,
					$delBtn = param.$delBtn;

				$delBtn.click(function() {
					$.confirm("删除留言","确定要删除留言？",function() {
						if($delBtn.attr("ajax_lock") !== "true") {
							$delBtn.attr("ajax_lock","true");
							self.model({
								method:"delMsg",
								param:{
									data:{uid:$msg.attr("uid"),id:$msg.attr("msgid")},
									callback:function(data) {
										if(data.status === 1) {
											$msg.fadeOut("fast",function() {
												$msg.remove();
												self.view({method:"msgAmount",param:{num:-1}});
											});
										} else {
											alert(data.msg);
										}
									}
								}
							});
						}
					});
				});
			},
			answerMsg:function(param) {
				var $msg = param.$msg,
					$answerLst = param.$answerLst,
					$answerPost = param.$answerPost,
					$answerInput = param.$answerInput,
					$answerBtn = param.$answerBtn;

				$answerBtn.click(function() {
					$answerPost.css({display:"block"});
					
					// 解决IE8的bug
					$msg.find("span.moreOperate").addClass("dropDown").removeClass("dropDown");
					
					$answerInput.focus();
				});

				$answerInput.blur(function() {
					$answerPost.hide();
				});
				$answerInput.keyup(function(ev) {
					if(ev.keyCode == 13 && $.trim($answerInput.val()) !== "") {
						self.model({
							method:"addAnswer",
							param:{
								data:{
									fid:$msg.attr("msgid"),
									action_uid:$msg.attr("uid"),
									content:$.trim($answerInput.val())
								},
								callback:function(data) {
									if(data.status === 1) {
										var $answer = self.view({
											method:"answer",
											param:{
												id:data.id,
												uid:self.curUserId,
												username:self.curUserName,
												dateline:"刚刚",
												headpic:self.curHeadpic,
												content:data.data.content
											}
										});

										$answerLst.show().append($answer);
										$answerInput.val("");
									} else {
										alert(data.msg);
									}
								}
							}
						});
					}
				});
			},
			getMoreMsg:function(param) {
				var $getMoreBtn = self.getMoreMsg.find("a");
				var $loadingIco = self.getMoreMsg.find("img");

				$getMoreBtn.click(function() {
					var page = parseInt(self.getMoreMsg.attr("page"));

					page = page ? (page + 1) : 1;

					if($getMoreBtn.attr("ajax_lock") !== "true") {
						$getMoreBtn.attr("ajax_lock","true");
						$getMoreBtn.hide();
						$loadingIco.show();

						self.model({
							method:"getMsg",
							param:{
								data:{action_uid:self.uid,page:page},
								callback:function(data) {
									self.getMoreMsg.attr("page",page);
									$getMoreBtn.attr("ajax_lock","false");
									$getMoreBtn.show();
									$loadingIco.hide();

									if(data.status === 1 && data.data && data.data.length) {
										self.view({method:"msgLst",param:{$content:self.msgLst,data:data.data}});
										self.view({method:"msgAmount",param:{num:data.data.length}});

										if(!data.page) {
											//self.getMoreMsg.html("<span>已经没有留言了！</span>");
											self.getMoreMsg.html("");
										}
									} else {
										//alert(data.msg);
										//self.getMoreMsg.html("<span>已经没有留言了！</span>");
										self.getMoreMsg.html("");
									}
								}
							}
						});
					}
				});
			}
		};
		var returnVal = null;

		if(conf.method) {
			if(typeof eventClass[conf.method] === "function") {
				returnVal = eventClass[conf.method].call(eventClass,conf.param);
			}
		}

		return returnVal;
	},
	model:function(conf) {
		var self = this;
		var modelClass = {
			getMsg:function(param) {
				$.djax({
                    //url:"index.php?c=leavemsg&m=read_leave",
                    url:mk_url("main/leavemsg/read_leave"),
					async:true,
                    dataType:"json",
                    data:param.data || {},
                    success:function(data){
                        if(data){
							if(typeof param.callback === "function") {
								param.callback(data);
                        	}
                        }
                    }
                });
			},
			addMsg:function(param) {
				$.djax({
				    //url:"index.php?c=leavemsg&m=add_leave",
				    url:mk_url("main/leavemsg/add_leave"),
					async:true,
				    dataType:"json",
				    data:param.data || {},
				    success:function(data){
				        if(data){
							if(typeof param.callback === "function") {
								param.callback(data);
				        	}
						}
					}
				});
			},
			addAnswer:function(param) {
				$.djax({
                    //url:"index.php?c=leavemsg&m=add_leave",
                    url:mk_url("main/leavemsg/add_leave"),
					async:true,
                    dataType:"json",
                    data:param.data || {},
                    success:function(data){
                        if(data){
							if(typeof param.callback === "function") {
								param.callback(data);
                        	}
                        }
                    }
                });
			},
			delMsg:function(param) {
				$.djax({
                    //url:"index.php?c=leavemsg&m=del_leave",
                    url:mk_url("main/leavemsg/del_leave"),
					async:true,
                    dataType:"json",
                    data:param.data || {},
                    success:function(data){
                        if(data){
							if(typeof param.callback === "function") {
								param.callback(data);
                        	}
                        }
                    }
                });
			}
		};
		var returnVal = null;

		if(conf.method) {
			if(typeof modelClass[conf.method] === "function") {
				returnVal = modelClass[conf.method].call(modelClass,conf.param);
			}
		}

		return returnVal;
	}
};

$(document).ready(function() {
	var msgBoard = new MsgBoard();
	msgBoard.init();
});
/*******************end:留言板主程序*********************/