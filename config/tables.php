<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
* [ Duankou Inc ]
* Created on 2011-11-14
* @author fbbin
* The filename : tables.php   09:03:08
*/
/**
 * 系统设置、管理表
 */
define('SYSTEM_APP',      'system_app');
define('SYSTEM_APP_CATE', 'system_app_category');
define('SYSTEM_APP_NAV',  'system_app_nav');
define('SYSTEM_CONFIG',   'system_config');
define('SYSTEM_MENU_O',   'system_menu_1');
define('SYSTEM_MENU_T',   'system_menu_2');
define('SYSTEM_SYSFACE',  'system_sysface');
define('SYSTEM_USRINFO',  'system_userinfo');
define('SYSTEM_USERS',    'system_users');

/**
 * 系统应用表
 */
define('FILTER',        'filter');
define('SMS_LOG',       'sms_log');
define('LANGUAGE',      'language');
define('LINK_STAT',     'link_stat');
define('LIKES',         'likes');
define('COMMENTS',      'comments');
define('DUANKOU_APPLY', 'duankou_apply');
define('DUANKOUCODE',   'duankoucode');
define('OBJECTFOLLOW',   'object_follows');
/**
 * 用户关系表
 */
define('FOLLOWS', 'follows');
define('FRIENDS', 'friends');

/**
 * 用户问答模块表
 */
define('ANSWER_POLLS',    'answer_polls');
define('ANSWER_VOTERS',   'answer_voters');
define('ANSWER_COMMENTS', 'answer_comments');
define('ANSWER_OPTIONS',  'answer_options');

/**
 * IM模块表
 */
define('IM_CONTACT_LISTS', 'im_contact_lists');

/**
 * 用户博客模块表
 */
define('BLOG', 			 'blog');
define('BLOG_FRIENDS',   'blog_friends');
define('BLOG_VISITES',   'blog_visites');
define('BLOG_DRAFT',     'blog_draft');
define('BLOG_DRAFT_FRI', 'blog_draft_friends');
define('BLOG_DRAFT_VIS', 'blog_draft_visites');
define('BLOG_PHOTO',     'blog_photo');
define('BLOG_PHOTO_FRI', 'blog_photo_friends');
define('BLOG_PHOTO_VIS', 'blog_photo_visites');

/**
 * 用户动态模块表
 */
define('FEEDS_FRIEND', 'feeds_friends');
define('FEEDS_SOCIAL', 'feeds_socials');
define('FEEDS_VISITE', 'feeds_visites');

/**
 * 用户信息模块表
 */
define('INFO_AREA',          'info_area');
define('INFO_COLLEGE',       'info_college');
define('INFO_COMPANY',       'info_company');
define('INFO_HIGHSCHOOL',    'info_highschool');
define('INFO_NATION',        'info_nation');
define('INFO_POSITION',      'info_position');
define('INFO_PRIMARYSCHOOL', 'info_primaryschool');
define('INFO_SUPER_USER',    'info_super_user');
define('INFO_TRADE',         'info_trade');
define('INFO_WEIBO_SUPER',   'info_weibo_super');

/**
 * 站内信、通知、好友请求模块相关表
 */
define('NOTICE',             'notice');
define('FRIEND_INVITE',      'friend_invite');
define('MESSAGE_FILEUPLOAD', 'message_fileupload');
define('MESSAGE_INFO',       'message_info');
define('MESSAGE_USERGROUP',  'message_usergroup');
define('NOTICE_BIGTYPE',       'notice_bigtype');
define('NOTICE_TYPE',  'notice_type');

/**
 * 招聘信息表
 */

define('RESUME_BOOK',     'user_book');
define('RESUME_LANGUAGE', 'user_lang');
define('RESUME_PROJECT',  'user_project');
define('RESUME_SCHOOL',   'user_school');
define('RESUME_SKILL',    'user_skill');
define('RESUME_TRAIN',    'user_train');

/**
 * 搜索引擎相关
 */
define('SPHINX_COUNTER', 'sphinx_counter');
/**
 * 信息流模块表
 */
define('TOPIC',           'topic');
define('TOPIC_CL',        'topic_collectlike');
define('TOPIC_IMG',       'topic_image');
define('TOPIC_FRI', 'topic_friends');
define('TOPIC_INDEX', 'topic_attention');
//define('TOPIC_INDEX_VIS', 'topic_index_visites');
define('TOPIC_REPLY',     'topic_reply');
define('TOPIC_THEME',     'topic_theme');

/**
 * 用户相关模块表
 */
define('USERS',                 'user_info');
define('USER_ALBUM',            'user_album');
define('USER_AUTHORIZE',        'user_authorize');
define('USER_CALL_RECORD',      'user_call_record');
define('USER_EXPAND',           'user_expand');
define('USER_FORGETPWD',        'user_forgetpwd');
define('USER_HIGHSCHOOL',       'user_highschool');
define('USER_HIGHSCHOOL_CM',    'user_highschool_classmate');
define('USER_UNIVERSITY',       'user_edu');
define('USER_UNIVERSITY_CM',    'user_university_classmate');
define('USER_PRIMARYSCHOOL',    'user_primaryschool');
define('USER_PRIMARYSCHOOL_CM', 'user_primaryschool_classmate');
define('USER_JOBEXPER',         'user_work');
//define('USER_JOBEXPER_WM',      'user_jobexper_workmate');
define('USER_INTEREST',         'user_interest');
define('USER_INTEREST_RELATE',  'user_interest');
define('USER_INFO_WIKI',        'user_info_wiki');
define('USER_LIFECONDITIONS',   'user_lifeconditions');
define('USER_PHOTO',            'user_photo');
define('USER_RECORDCOUNT',      'user_recordcount');
define('USER_VIDEO',            'user_video');
define('USER_VIDEO_CAT',            'user_video_category');
define('USER_LIFE',            'user_life');
/**
 * 活动表定义
 */
define("EVENTTABLE", "event");
define("EVENT_TABLE_EXT", "event_ext");
define("EVENT_TABLE_USERJOIN","event_userjoin");
define("EVENT_TABLE_INVITE","event_invite");
define("EVENT_TABLE_PIC","event_pic");

/**
 * 权限表
 */
define("ACCESS_ALBUM", "access_album");
define("ACCESS_BLOG", "access_blog");
define("ACCESS_VIDEO","access_video");
define("ACCESS_ASK","access_ask");
define("ACCESS_EDIT","user_edit_access");

/**
 * 测试用表
 */
define('TEST', 'test_only');

/* End of file tables.php */
/* Location: ./application/config/tables.php */