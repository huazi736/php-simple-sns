<?php
//通知常量设置
$config=array(
	/*duankou*/
	'dk_guanzhu'=>'关注了你',
	'dk_addfriend'=>'向你发送了朋友请求',
	'dk_confirmfriend'=>'已经确认了你的朋友请求',
	'dk_receiveinvite'=>'接受了你的邀请 加入了Duankou',
	'dk_leavecomment'=>array('给你留言'),
	'dk_leave_reply'=>array('回复了你的留言'),
	'dk_reply_comment'=>array('回复了你的评论'),
	'dk_commodity_zan'=>array('赞了你的商品'),
	'dk_commodity_comment'=>array('评论了你的商品'),
	/*info*/
 	'info_infocomment'=>array('评论了你的','状态'),
 	'info_frowardinfo'=>array('分享了你的','状态'),
 	'info_zaninfo'=>array('赞了你的','状态'),
	'info_frowardpic'=>array('分享了你的','照片'),
	'info_frowardalbum'=>array('分享了你的相册'),
	'info_frowardvideo'=>array('分享了你的视频'),
	'info_froward_blog'=>array('分享了你的日志'),
	/*photo*/
	'photo_commenttoyou'=>array('评论了你的','照片'),
	'photo_zan'=>array('赞了你的','照片'),
	'photo_albumcommenttoyou'=>array('评论了你的相册'),  
	'photo_albumzan'=>array('赞了你的相册'),
	'photo_favorite'=>array('收藏了你的照片'),
	'photo_albumfavorite'=>array('收藏了你的相册'),
	/*video*/
	'video_commenttoyou'=>array('评论了你的视频'),
	'video_zan'=>array('赞了你的视频'),
	'video_upload_true'=>array('你的视频','已经转码成功'),
	'video_upload_false'=>array('你的视频','转码失败，请','重新上传'),
	'upload_check_true_video'=>array('你的视频','审核成功'),
	'upload_check_false_video'=>array('你的视频','审核失败'),
	'video_favorite'=>array('收藏了你的视频'),
	/*blog*/
	'blog_commenttoyou'=>array('评论了你的博客'),
	'blog_zan'=>array('赞了你的博客'),
	'blog_reprint' => array('转发了你的博客'),
	'blog_favorite'=>array('收藏了你的日志'),
	/*ask*/
	'ask_you'=>array('向你提问'),
	//'ask_reply'=>array('关注了你的问题'),
	//'ask_comment'=>array('评论了你的问题'),
	//'ask_commentreply'=>array('评论并回答了你的问题'),
	'ask_commentyoufollow'=>array('回答了你关注的问题'),
	'ask_commentyoureply'=>array('评论了你关注的问题'),
	
	/*event*/
	'event_invitejoin'=>array('邀请你参加活动'),
	'event_update'=>array('更新了活动'),
	'event_cancel'=>array('取消了活动'),
	'event_setting'=>array('将你设为了活动','的管理员（确认参加后有效！）'),
	'event_ban'=>array('禁止你参加活动'),
	'event_answer'=>array('答复了你的活动'),
	'event_edit'=>array('编辑了你的活动'),
	'event_message'=>array('在你的活动','上留言'),
	'event_c_setting'=>array('取消了你在活动','中的管理员身份'),

	'event_c_manager'=>array('取消了你管理的活动'),
	/*group*/
	'group_out'=>array('你已被踢出','群'),
	'group_dismiss'=>array('你所在的群组','已解散'),
	'group_out_sub'=>array('你被','子群踢出'),
	'group_dismiss_sub'=>array('你所在的子群','已解散'),
	'group_join'=>array('邀请你加入群','，请点击确认'),

	/*web*/
	'dk_guanzhu_web'=>array('关注了你的网页'),
	'dk_creat_web'=>array('创建了网页','并邀请你加入'),
	'dk_commodityzan_web'=>array('赞了你的网页','的商品'),
	'dk_commoditycomment_web'=>array('评论了你的网页','的商品'),
	'dk_replycomment_web'=>array('回复了你在','网页上的评论'),
	'dk_zandishes_web'=>array('赞了你的网页','的菜品'),
	'dk_zanpromotions_web'=>array('赞了你的网页','的促销'),
	'dk_commentdishes_web'=>array('评论了你的网页','的菜品'),
	'dk_commentpromotions_web'=>array('评论了你的网页','的促销'),
	'dk_replydishes_web'=>array('回复了你的网页','的菜品'),
	'dk_replypromotions_web'=>array('回复了你的网页','的促销'),

	'photo_albumcomment_web'=> array('评论了你的网页','的相册'),
	'photo_comment_web' =>array('评论了你的网页','的','照片'),
	'photo_albumzan_web' => array('赞了你的网页','的相册'),
	'photo_zan_web' => array('赞了你的网页','的','照片'),
	'photo_favorite_web'=>array('收藏了你的网页','的','照片'),
	'photo_albumfavorite_web'=>array('收藏了你的网页','的相册'),
		
	'video_comment_web'=>array('评论了你的网页','的视频'),
	'video_zan_web'=>array('赞了你的网页','的视频'),
	'upload_true_videoweb'=>array('网页','的视频','已经转码成功'),
	'upload_false_videoweb'=>array('网页','的视频','转码失败，请','重新上传'),
	'upload_check_true_videoweb'=>array('网页','的视频','审核成功'),
	'upload_check_false_videoweb'=>array('网页','的视频','审核失败'),
	'video_favorite_web'=>array('收藏了你的网页','的视频'),
		
	'info_infocomment_web'=>array('评论了你的网页','的','状态'),
	'info_frowardinfo_web'=>array('分享了你的网页','的','状态'),
	'info_zaninfo_web'=>array('赞了你的网页','的','状态'),
	'info_frowardpic_web'=>array('分享了你的网页','的','照片'),
	'info_frowardalbum_web'=>array('分享了你的网页','的相册'),
	'info_frowardvideo_web'=>array('分享了你的网页','的视频'), 
		
	'event_message_web'=>array('在你的活动','中留言'),
	'event_update_web'=>array('更新了活动'),
	'event_c_web'=>array('取消了活动'),
	'event_ban_web'=>array('禁止你参加活动'),	
	
	'dk_del_web'=>array('你关注的网页','已被创建者删除了'),
	
	'baike_ban_web'=>array('因举报过多，您在','端口百科','中的权限将被封禁60天,将于','解禁'),
	'baike_remove_web'=>array('您在','端口百科','中的封禁已经解除'),
	'baike_stop_web'=>array('因举报过多，您在','端口百科','中的权限将被封禁','天'),
);

?>