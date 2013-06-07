//视频评论 JavaScript Document
//由李海棠 在 2011-09-29 创建
$(function(){
	var commentOptions={
		minNum:3,
		UID:CONFIG['u_id'],
		userName:CONFIG['u_name'],
		avatar:CONFIG['u_head'],
		userPageUrl:$("#hd_userPageUrl").val(),
        relayCallback:function (obj,_arg) {
            var comment=new ui.Comment();
            comment.share(obj,_arg);
        }
	};
	
	if($(".comment_easy").commentEasy)
	var com=$(".comment_easy").commentEasy(commentOptions);
	/*End 调用评论的函数 由李海棠在2011-10-19添加*/
})