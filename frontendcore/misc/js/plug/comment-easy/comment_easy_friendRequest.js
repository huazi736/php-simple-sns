/*
* Update on  2011-3-08
* @author： 田想兵
* @version： 1.0.01
* @desc： 发送好友请求
* @depend:  comment_easy.js
*/

	if (typeof( WEB_ID)=="undefined"){
		WEB_ID="";
	}
//$(function() {
//	//添加取消关注
//	var addfollow = $('#addFollow'),
//		delFollow = $('#delFollow'),
//		triggerBtn = addfollow.parent().next().find('div.triggerBtn');
//	addfollow.live('click',function() {
//		var $this = $(this);
//		if($this.hasClass('getting'))return false;
//		$this.addClass('getting');
//		$.ajax({
//			url: mk_url('main/api/addWebFollow'),
//			type: 'POST',
//			dataType: 'jsonp',
//			data: {f_uid: $this.attr('name'), web_id: WEB_ID},
//			success: function(data) {
//
//			}
//		}).then(function(q){
//                if(data.status) {
//                    $this.removeClass('getting').parent().addClass("hide").next().removeClass("hide");
//                }else{
//                    alert(data.info);
//                }
//            });
//	});
//	delFollow.live('click',function() {
//		var $this = $(this);
//		if($this.hasClass('getting'))return false;
//		$this.addClass('getting');
//		$.ajax({
//			url: mk_url('main/api/unWebFollow'),
//			type: 'POST',
//			dataType: 'jsonp',
//			data: {f_uid: $this.attr('name'), web_id: WEB_ID},
//			success: function(data) {
//
//			}
//		}).then(function(q){
//                if(q.status) {
//                    triggerBtn.click();
//                    $this.removeClass('getting').closest(".dropWrap").addClass("hide").prev().removeClass("hide");
//                }else{
//                    alert(data.info);
//                }
//            });
//	});
//});
$(function(){
	$(".follow_state").live('click',function(){
		var _self=$(this);
		_self.next(".hide").show();
		/*
		_self.parent().one("mouseout",function(){
			_self.next(".hide").hide();
		})
		*/
	})
	$("#comment_userList").delegate("li a[act]","click",function(){
		var fid=$(this).attr("href");
		var _self=$(this).closest("div.friendBtns");
		switch($(this).attr("act")){
			case "1":{
				delFriend(fid,_self);
			}break;//解除好友
			case "2.1":{
				addFriend(fid,_self);
			}break;//加为好友
			case "2.2":{
				delFollow(fid,_self);
			}break;//取消关注
			case "2.3":{
				delFollow(fid,_self,true);
			}break;
			case "4":{
				addFollow(fid,_self,true);//互样关系
			}break;	//
			case "0":{
				addFollow(fid,_self);
			}break;	//加关注		
			case "6.1":{//同意好友请求
				greeFriend(fid,_self);
				}break;
			case "6.2":{
				refuseFriend(fid,_self);
				}break;
		}
		return false;
	})
	//praise_addFriend();
	//变量
	var pageCount=parseInt($("#hd_pageCount").val());
	var page=1;
	var commentID=$("#hd_commentID").val();
	var pageType=$("#hd_pageType").val();
	if(page>=pageCount){
		$("#loadMore").hide();
	}
	function getList(){
		//列表
		$.ajax({
			//url:webpath+"main/index.php?c=comment&m=like_list&web_id="+WEB_ID+"&pageType="+pageType+"&page="+page+"&comment_ID="+commentID,
            url:"like_list?c=comment&m=like_list&web_id="+WEB_ID+"&pageType="+pageType+"&page="+page+"&comment_ID="+commentID,
			dataType:"jsonp",
			data:{date:new Date()},
			cache:false,
			success:function(result){
				if(page>=pageCount){
					$("#loadMore").hide();
				}
				page++;
				if(result){
					var _html ="";
					$.each(result.data,function(e,d){
						var act="";
						if (d.isweb){
							var str1="",str2="";
							if (d.relationship==0){
								str1=""
								str2="hide";
							}else{
								str1="hide"
								str2="";
							}
							act='<div class="webrelation"> <span class="btnBlue '+str1+'"><i></i><a href="javascript:void(0);" id="addFollow" name="'+d.uid+'" class="addFollow">加关注</a></span><div class="dropWrap dropMenu '+str2+'"><div class="triggerBtn"><i class="friend"></i><span>关注</span><s></s></div><div class="dropList"><ul class="dropListul checkedUl"><li><a href="javascript:void(0);" id="delFollow" name="'+d.uid+'" class="itemAnchor delFollow"><span>取消关注</span></a></li></ul></div></div></div>';
						}else{							
							act='<div uid="'+d.uid+'" rel="'+d.relationship+'" class="statusBox"></div>';
						}
						if(d.relationship==7){
							act="";
						}
						_html += ' <li><input type="hidden" value="'+d.uid+'" /><a href="'+d.url+'" target="_parent"><img class="comment_userList_avatar" src="'+d.avatar_s+'" /></a><a href="'+d.url+'" class="comment_userList_userName" target="_parent">'+d.username+'</a>'+act+'</li>';
					});
					var tempObj=$(_html);
					tempObj.find("div.statusBox").relation();					
					$("#comment_userList ul").append(tempObj);
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