/**
 * Created on 2012-03-19
 * @author: chengting
 * @desc: 关系按钮
 **/
 $(function(){
 	var addFollowEvent = {
 		init: function(){
 			var btns = this.btns;
 			this.addFollow(btns.addBtn);
 			this.menuFun(btns.stateBtn);
 		},
 		btns: {
 			addBtn: $("#relation_operate"),
 			stateBtn: $("#relation_state"),
 			msgBtn: $("#msgsnd")
 		},
 		addFollow: function($obj){
 			var btns = this.btns;
 			$obj.bind("click",function(){
 				var $this = $(this),
 					type = $this.attr("name"),
 					uid = $this.find("a").attr("uid");
 				$.djax({
 					type: "post",
 					url:  mk_url('main/api/addFollow'),
 					data: {f_uid:uid},
 					dataType: "json",
 					success: function(data){
 						if(data.state == "1"){
 						var text = "关注",
 							links = "<li><a id='unfollow_btn' uid= " + uid +" class='itemAnchor pl15'>取消关注</a></li>";
 							btns.msgBtn.addClass("hide");
 						if(type=="fans"){
 							text = "相互" + text;
 							links = "<li><a id='unfollow_btn' uid= " + uid +" class='itemAnchor pl15'>取消关注</a></li><li><a id='addfriend_btn' uid= " + uid +" class='itemAnchor pl15'>加为好友</a></li>"
 							btns.msgBtn.removeClass("hide");
 						}
 						$this.addClass("hide").next().find("#relation_state").children("span").text(text);
 						$this.next().removeClass("hide").find(".dropListul").empty().append(links);
 						}else{
 							alert(data.msg);
 						}
 					}
 				});
 				return false;
 			});
 		},
 		menuFun: function($obj){
 			var btns = this.btns;
 			function clickFun($_this,url,friend){
 				var $this = $_this,
 					uid = $this.attr("uid");
 				$.djax({
 					type: "post",
 					url: url,
 					data: {f_uid:uid},
 					dataType: "json",
 					success: function(data){
 						if(data.state == "1"){
 						if(friend == "addfriend"){
 							btns.stateBtn.next().find(".dropListul li").last().find("a").text("好友请求已发送");
 							return;
 						}else if(friend == "delfriend"){
 							btns.stateBtn.find("span").text("相互关注").end().next().find(".dropListul").empty().append("<li><a id='unfollow_btn' uid= " + uid +" uid= " + uid +" class='itemAnchor pl15'>取消关注</a></li><li><a id='addfriend_btn'  uid= " + uid +" class='itemAnchor pl15'>加为好友</a></li>");
 							return;
 						}
 						btns.addBtn.removeClass("hide click").next().addClass("hide");
 						btns.msgBtn.addClass("hide");
 						}else{
 							alert(data.msg);
 						}
 					}
 				});
 			}
 			$("#unfollow_btn").live("click",function(){
 				var url = mk_url('main/api/unFollow');
 				clickFun($(this) , url);
 				return false;
 			});
 			$("#addfriend_btn").live("click",function(){
 				var url =   mk_url('main/api/addFriend');
 				clickFun($(this) , url , "addfriend");
 				return false;
 			});
 			$("#delfriend_btn").live("click",function(){
 				var url =  mk_url('main/api/delFriend');
 				clickFun($(this), url , "delfriend");
 				return false;
 			});
 		}
	};
	addFollowEvent.init();
 })
