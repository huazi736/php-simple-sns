/*
* Update on  2011-3-08
* @author： 田想兵
* @version： 1.0.01
* @desc： 发送好友请求
* @depend:  comment_easy.js
*/

$(function(){	
	if (typeof( WEB_ID)=="undefined"){
		WEB_ID="";
	}
	var commentOptions={
		minNum:3,
		UID:CONFIG['u_id'],
		userName:CONFIG['u_name'],
		avatar:CONFIG['u_head'],
		//userPageUrl:parent.$("#hd_userPageUrl").val(),
        userPageUrl:mk_url('main/index/main',{'action_dkcode':CONFIG['dkcode']}),
		isShow:false,
        hasColl:!1,
        hasShare:!1
	};	
	
	//praise_addFriend();
	//变量
	var pageCount=parseInt($("#hd_pageCount").val());
	var page=1;
	var commentID=$("#hd_commentID").val();
    var pageType=$("#hd_pageType").val();
    var action_uid=$('#hd_actionUid').val();
    var WEB_ID=CONFIG['web_id'];
	if(page>=pageCount){
		$("#loadMore").hide();
	}
	function getList(){
		//列表
		$.ajax({
            url:"share_list?web_id="+WEB_ID+"&pageType="+pageType+"&page="+page+"&comment_ID="+commentID+'&action_uid='+action_uid,
			dataType:"jsonp",
			data:{date:new Date()},
			success:function(result){
				if(page>=pageCount){
					$("#loadMore").hide();
				}
				page++;
				if(result.status){
					var _html ="";
					$.each(result.data,function(e,d){
						var pt = $("#hd_isweb").val()=="1" ? "web_forward":"forward";
						_html += ' <li class="clearfix"><input type="hidden" value="'+d.uid+'" /><a href="'+d.url+'" class="comment_likelist_userName" target="_parent"><img class="comment_userList_avatar" src="'+d.avatar_s+'" /></a>\
						<div class="likeInfo"><a href="'+d.url+'" class="comment_likelist_userName" target="_parent">'+d.username+'</a>&nbsp;&nbsp;'+d.info+'\
						<div class="shareComment"  action_uid="'+d.action_uid+'" ctime="'+d.ctime+'" pagetype="'+pt+'" commentobjid="'+d.cid+'" name="timeBox" id="'+d.tid+'"></div></div></li>';
					});
					var tempObj=$(_html);
					tempObj.find("div.shareComment").commentEasy(commentOptions);
					$("#comment_userList>ul").append(tempObj);
				}else{
                    alert(result.info);
                }
			},
			error:function(){
				alert("网络错误");
			}
		});
	}
	getList();
	$("#loadMore").click(getList);
})