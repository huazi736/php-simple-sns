
/*
	Create 2012-7-23
	@ author Chenhaiyun(陈海云)
	@ name 《首页 - 关注信息流 - "album" 模块》
	desc 
*/
var Class_follow_person_forward = (function() {
	var F = function() {};

	// 信息模板
	var template = [
		'<li id="${tid}" fid="${fid}" class="box" type="${type}" uid="${uid}" time="${ctime}">',
			'<a class="user_face" href="${location}?dkcode=${dkcode}" title="${uname}">',
				'<img width="50" height="50" src="${headpic}" alt="${uname}" />',
			'</a>',
			'<div class="wrap">',
				'<div class="content">',
					'<div class="info head"><strong><a title="${uname}" href="${location}?dkcode=${dkcode}">${uname}</a></strong> <span style="font-size:14px; color:#999;">分享了<a href="${forward.person_url}">${forward.uname}</a>的${forward.typeTxt}</span></div>',
					'<div class="info">',
						'<div>$${data|forwardContent}</div>',
					'</div>',
					'<div class="commentBox pd" time="${friendly_time}" action_uid="${uid}" ctime="${ctime}" pagetype="${type}" msgname="${title}" commentobjid="${tid}" complete="true"></div>',
				'</div>',
			'</div>',
		'</li>'
	].join("");

	// 转发内容模板
	var forwardTmpl = {
		blog:[
			'<p style="padding:0px 0px 5px;">$${content}</p>',
			'<div class="content" style="padding:8px 10px; margin-left:5px; border-left:#C0C9DD 2px solid;">',
				'<p style="padding:0px 0px 5px;"><a href="${forward.blogUrl}"><b>${forward.title}</b></a></p>',
				'<p>$${forward.content}</p>',
				'<p style="padding:5px 0px 0px;"><a href="${forward.blogUrl}">阅读全文...</a></p></div>',
			'</div>'
		].join(""),
		info:[
			'<p style="padding:0px 0px 5px;">$${content}</p>',
			'<div class="content" style="padding:8px 10px; margin-left:5px; border-left:#C0C9DD 2px solid;">',
				'<p>$${forward.content}</p>',
			'</div>'
		].join(""),
		video:[
			'<p style="padding:0px 0px 5px;">$${content}</p>',
			'<div class="content" style="padding:8px 10px; margin-left:5px; border-left:#C0C9DD 2px solid;">',
				'<div class="info">$${forward.content}</div>',
				'<div class="mediaContent info center">',
					'<div class="media_prev" fid="${forward.fid}">',
						'<img width="403" height="300" alt="" src="${host}${forward.imgurl}">',
						'<a href="javascript:void(0);" class="showFlash">',
							'<img style="top:125px;left:184px;" src="' + CONFIG["misc_path"] + 'img/system/feedvideoplay.gif" alt="">',
						'</a>',
					'</div>',
					'<div class="media_disp hide">',
						'<div id="video_${forward.fid}"></div>',
					'</div>',
				'</div>',
			'</div>'
		].join(""),
		album:[
			'<p style="padding:0px 0px 5px;">$${content}</p>',
			'<div class="content" style="padding:8px 10px; margin-left:5px; border-left:#C0C9DD 2px solid;">',
				'<p style="padding:0px 0px 5px;">${forward.title}</p>',
				'<div>',
					'<ul class="photoContent clearfix">',
						'$${forward.picurl,host,forward.dkcode|createAlbumLst}',
					'</ul>',
				'</div>',
			'</div>'
		].join(""),
		photo:[
			'<p style="padding:0px 0px 5px;">$${content}</p>',
			'<div class="content" style="padding:8px 10px; margin-left:5px; border-left:#C0C9DD 2px solid;">',
				'<p style="padding:0px 0px 5px;">${forward.title}</p>',
				'<div>',
					'<ul class="photoContent clearfix">',
						'$${forward.picurl,host,forward.dkcode|createPhotoLst}',
					'</ul>',
				'</div>',
			'</div>'
		].join("")
	};

	// 转发分类信息对应
	var forwardType = {
		video:"视频",
		blog:"日志",
		info:"状态",
		photo:"照片",
		album:"相册"
	};
	
	var forward = function(data) {
		var type = data.forward.type;
		var tmpl = forwardTmpl[type] || "";
		var str = "";

		if(type === "blog") {
			data.forward.blogUrl = mk_url('blog/blog/main',{'id':data.forward.fid,'dkcode':data.forward.dkcode});
			console.log(data.forward.blogUrl);
		}
		str = juicer(tmpl,data);

		return str;
	};

	var forwardContent = function(data) {
		var str = "",
			forwardType = data.forward.type;
		
		str += forward(data);

		return str;
	};

	juicer.register("forwardContent", forwardContent);

	F.prototype.init = function(data) {
		
		data.forward.person_url =  mk_url("main/index/main",{dkcode:data.forward.dkcode});
		data.forward.typeTxt = forwardType[data.forward.type];
		data.data = data;

		var infoHtml = juicer(template, data);
		
		return $(infoHtml);
	};

	return F;
})();

/*
	Create 2012-7-23
	@ author Chenhaiyun(陈海云)
	@ name 《首页 - 关注信息流 - "album" 模块》
	desc 
*/
var Class_follow_person_album = (function() {
	var F = function() {};

	var template = [
		'<li id="${tid}" fid="${fid}" class="box" type="${type}" uid="${uid}" time="${ctime}">',
			'<a class="user_face" href="${location}?dkcode=${dkcode}" title="${uname}">',
				'<img width="50" height="50" src="${headpic}" alt="${uname}" />',
			'</a>',
			'<div class="wrap">',
				'<div class="content">',
					'<div class="info head"><strong><a title="${uname}" href="${location}?dkcode=${dkcode}">${uname}</a></strong> <span style="font-size:14px; color:#999;">添加了${picurl.length}张新图片到相册中</span></div>',
					'<div class="info">',
						'<div>$${content}</div>',
						'<div>',
							'<ul class="photoContent clearfix">',
								'$${picurl,host,dkcode|createAlbumLst}',
							'</ul>',
						'</div>',
					'</div>',
					'<div class="commentBox pd" time="${friendly_time}" action_uid="${uid}" ctime="${ctime}" pagetype="${type}" msgname="${title}" commentobjid="${fid}" complete="true"></div>',
				'</div>',
			'</div>',
		'</li>'
	].join("");

	var createAlbumLst = function(picsInfo,host,dkcode) {
		var str = "";
		var _height;

        $.each(picsInfo, function (i, v) {
        	if(i > 3) {
        		return;
        	}
            var hidden = "";
            var size = "_tm";

            var firstPhoto = "";
            var width = "", height = "";
            if (i == 0) {
                firstPhoto = "firstPhoto";
                if (v.size) {
                    if (v.size.tm) {
                        _height = v.size.tm.h;
                    }
                }
            } else {
                size = "_ts";
            }
            if (i == 1) {
                if (v.size) {
                    if (v.size.ts) {
                        _height = 133;
                    }
                }
            }
            var _width = "expression(this.width>=838?838:'auto')";
            picurl = host + v.groupname + "/" + v.filename + size + "." + v.type;

            str += '<li class="' + firstPhoto + ' ' + hidden + '"><a href="javascript:void(0);" action_dkcode="' + dkcode + '" pid="' + v.pid + '" url="' + mk_url('album/index/photoInfo',{photoid:v.pid,dkcode:dkcode}) + '" class="photoLink"><img src="' + picurl + '" alt="" style="max-width:838px;_width:' + _width + '"/></a></li>';
        });

		return str;
	};
	juicer.register("createAlbumLst", createAlbumLst);

	F.prototype.init = function(data) {
		var infoHtml = juicer(template, data);
		
		return $(infoHtml);
	};

	return F;
})();

/*
	Create 2012-7-23
	@ author Chenhaiyun(陈海云)
	@ name 《首页 - 关注信息流 - "album" 模块》
	desc 
*/
var Class_follow_person_photo = (function() {
	var F = function() {};

	var template = [
		'<li id="${tid}" fid="${fid}" class="box" type="${type}" uid="${uid}" time="${ctime}">',
			'<a class="user_face" href="${location}?dkcode=${dkcode}" title="${uname}">',
				'<img width="50" height="50" src="${headpic}" alt="${uname}" />',
			'</a>',
			'<div class="wrap">',
				'<div class="content">',
					'<div class="info head"><strong><a title="${uname}" href="${location}?dkcode=${dkcode}">${uname}</a></strong> <span style="font-size:14px; color:#999;">添加了${picurl.length}张新照片</span></div>',
					'<div class="info">',
						'<div>$${content}</div>',
						'<div>',
							'<ul class="photoContent clearfix">',
								'$${picurl,host,dkcode|createPhotoLst}',
							'</ul>',
						'</div>',
					'</div>',
					'<div class="commentBox pd" time="${friendly_time}" action_uid="${uid}" ctime="${ctime}" pagetype="${type}" msgname="${title}" commentobjid="${fid}" complete="true"></div>',
				'</div>',
			'</div>',
		'</li>'
	].join("");

	var createPhotoLst = function(picsInfo,host,dkcode) {
		var str = "";
		var _height;

        $.each(picsInfo, function (i, v) {
        	if(i > 3) {
        		return;
        	}
            var hidden = "";
            var size = "_tm";

            var firstPhoto = "";
            var width = "", height = "";
            if (i == 0) {
                firstPhoto = "firstPhoto";
                if (v.size) {
                    if (v.size.tm) {
                        _height = v.size.tm.h;
                    }
                }
            } else {
                size = "_ts";
            }
            if (i == 1) {
                if (v.size) {
                    if (v.size.ts) {
                        _height = 133;
                    }
                }
            }
            var _width = "expression(this.width>=838?838:'auto')";
            picurl = host + v.groupname + "/" + v.filename + size + "." + v.type;

            str += '<li class="' + firstPhoto + ' ' + hidden + '" style="height:' + _height + 'px" ><a href="javascript:void(0);" action_dkcode="' + dkcode + '" pid="' + v.pid + '" url="' + mk_url('album/index/photoInfo',{photoid:v.pid,dkcode:dkcode}) + '" class="photoLink"><img src="' + picurl + '" alt="" style="max-width:838px;_width:' + _width + '"/></a></li>';
        });

		return str;
	};
	juicer.register("createPhotoLst", createPhotoLst);

	F.prototype.init = function(data) {
		var infoHtml = juicer(template, data);
		
		return $(infoHtml);
	};

	return F;
})();


/*
	Create 2012-7-23
	@ author Chenhaiyun(陈海云)
	@ name 《首页 - 关注信息流 - "ask" 模块》
	desc 
*/
var Class_follow_person_ask = (function() {
	var F = function() {};

	var template = [
		'<li id="${tid}" fid="${fid}" class="box" type="${type}" uid="${uid}" time="${ctime}">',
			'<a class="user_face" href="${location}?dkcode=${dkcode}" title="${uname}">',
				'<img width="50" height="50" src="${headpic}" alt="${uname}" />',
			'</a>',
			'<div class="wrap">',
				'<div class="content">',
				'<div class="info head"><strong><a title="${uname}" href="${location}?dkcode=${dkcode}">${uname}</a></strong> <span style="font-size:14px; color:#999;">提问：</span></div>',
				'<div class="info center showAsk" data="${index}">',
				'</div>',
				'<div class="commentBox pd" time="${friendly_time}" action_uid="${uid}" ctime="${ctime}" pagetype="${type}" msgname="${title}" commentobjid="${fid}" complete="true"></div>',
			'</div>',
		'</li>'
	].join("");

	F.prototype.init = function(data) {
		var infoHtml = juicer(template, data);

		return $(infoHtml);
	};

	return F;
})();


/*
	Create 2012-7-23
	@ author Chenhaiyun(陈海云)
	@ name 《首页 - 关注信息流 - "blog" 模块》
	desc 
*/
var Class_follow_person_blog = (function() {
	var F = function() {};

	var template = [
		'<li id="${tid}" fid="${fid}" class="box" type="${type}" uid="${uid}" time="${ctime}">',
			'<a class="user_face" href="${location}?dkcode=${dkcode}" title="${uname}">',
				'<img width="50" height="50" src="${headpic}" alt="${uname}" />',
			'</a>',
			'<div class="wrap">',
				'<div class="content">',
					'<div class="info head"><strong><a title="${uname}" href="${location}?dkcode=${dkcode}">${uname}</a></strong><span style="font-size:14px; color:#666;">发表了日志</span></div>',
					'<p style="padding:10px 0px 5px;"><a href="${blogUrl}"><strong>${title}</strong></a></p>',
					'<div class="info">$${content}</div>',
					'<div class="commentBox pd" time="${friendly_time}" action_uid="${uid}" ctime="${ctime}" pagetype="${type}" msgname="${title}" commentobjid="${fid}" complete="true"></div>',
				'</div>',
			'</div>',
		'</li>'
	].join("");

	F.prototype.init = function(data) {
		var infoHtml = "";
		data.blogUrl = mk_url('blog/blog/main',{'id':data.fid,'dkcode':data.dkcode});;
		infoHtml = juicer(template, data);

		return $(infoHtml);
	};

	return F;
})();


/*
	Create 2012-7-23
	@ author Chenhaiyun(陈海云)
	@ name 《首页 - 关注信息流 - "sharevideo" 模块》
	desc 
*/
var Class_follow_person_event = (function() {
	var F = function() {};

	var template = [
		'<li id="${tid}" fid="${fid}" class="box" type="${type}" uid="${uid}" time="${ctime}">',
			'<a class="user_face" href="${location}?dkcode=${dkcode}" title="${uname}">',
				'<img width="50" height="50" src="${headpic}" alt="${uname}" />',
			'</a>',
			'<div class="wrap">',
				'<div class="content">',
					'<div class="info head"><strong><a title="${uname}" href="${location}?dkcode=${dkcode}">${uname}</a></strong></div>',
					'<div class="info">',
						'<div>$${title}</div>',
					'</div>',
				
					'<div class="commentBox pd" time="${friendly_time}" action_uid="${uid}" ctime="${ctime}" pagetype="${type}" msgname="${title}" commentobjid="${fid}" complete="true"></div>',
				'</div>',
			'</div>',
		'</li>'
	].join("");

	F.prototype.init = function(data) {
		var infoHtml = juicer(template, data);

		return $(infoHtml);
	};

	return F;
})();


/*
	Create 2012-7-23
	@ author Chenhaiyun(陈海云)
	@ name 《首页 - 关注信息流 - "info" 模块》
	desc 
*/
var Class_follow_person_info = (function() {
	var F = function() {};

	var template = [
		'<li id="${tid}" fid="${fid}" class="box" type="${type}" uid="${uid}" time="${ctime}">',
			'<a class="user_face" href="${location}?dkcode=${dkcode}" title="${uname}">',
				'<img width="50" height="50" src="${headpic}" alt="" />',
			'</a>',
			'<div class="wrap">',
				'<div class="content">',
					'<div class="info head"><strong><a title="${uname}" href="${location}?dkcode=${dkcode}">${uname}</a></strong><span style="font-size:14px; color:#666;">更新了状态</span></div>',
					'<div class="info">$${content}</div>',
				
					'<div class="commentBox pd" time="${friendly_time}" action_uid="${uid}" ctime="${ctime}" pagetype="topic" msgname="${title}" commentobjid="${tid}" complete="true"></div>',
				'</div>',
			'</div>',
		'</li>'
	].join("");

	F.prototype.init = function(data) {
		var infoHtml = juicer(template, data);

		return $(infoHtml);
	};

	return F;
})();


/*
	Create 2012-7-23
	@ author Chenhaiyun(陈海云)
	@ name 《首页 - 关注信息流 - "sharevideo" 模块》
	desc 
*/
var Class_follow_person_sharevideo = (function() {
	var F = function() {};

	var template = [
		'<li id="${tid}" fid="${fid}" class="box" type="${type}" uid="${uid}" time="${ctime}">',
			'<a class="user_face" href="${location}?dkcode=${dkcode}" title="${uname}">',
				'<img width="50" height="50" src="${headpic}" alt="${uname}" />',
			'</a>',
			'<div class="wrap">',
				'<div class="content">',
					'<div class="info head"><strong><a title="${uname}" href="${location}?dkcode=${dkcode}" title="${uname}">${uname}</a></strong></div>',
					'<div class="info">',
						'<div>$${content}</div>',
					'</div>',
				
					'<div class="commentBox pd" time="${friendly_time}" action_uid="${uid}" ctime="${ctime}" pagetype="${type}" msgname="${title}" commentobjid="${tid}" complete="true"></div>',
				'</div>',
			'</div>',
		'</li>'
	].join("");

	F.prototype.init = function(data) {
		var infoHtml = juicer(template, data);

		return $(infoHtml);
	};

	return F;
})();


/*
	Create 2012-7-23
	@ author Chenhaiyun(陈海云)
	@ name 《首页 - 关注信息流 - "social" 模块》
	desc 
*/
var Class_follow_person_social = (function() {
	var F = function() {};

	var template = [
		'<li id="${tid}" fid="${fid}" class="box" type="${type}" uid="${uid}" time="${ctime}">',
			'<a class="user_face" href="${location}?dkcode=${dkcode}" title="${uname}">',
				'<img width="50" height="50" src="${headpic}" alt="${uname}" />',
			'</a>',
			'<div class="wrap">',
				'<div class="content">',
					'<div class="info head"><strong><a title="${uname}" href="${location}?dkcode=${dkcode}">${uname}</a></strong></div>',
					'<div class="info">',
						'<div>$${content}</div>',
					'</div>',
					
					'<div class="commentBox pd" time="${friendly_time}" action_uid="${uid}" ctime="${ctime}" pagetype="${type}" msgname="${title}" commentobjid="${fid}" complete="true"></div>',
				'</div>',
			'</div>',
		'</li>'
	].join("");

	F.prototype.init = function(data) {
		var infoHtml = juicer(template, data);

		return $(infoHtml);
	};

	return F;
})();


/*
	Create 2012-7-23
	@ author Chenhaiyun(陈海云)
	@ name 《首页 - 关注信息流 - "video" 模块》
	desc 
*/
var Class_follow_person_video = (function() {
	var F = function() {};

	var template = [
		'<li id="${tid}" fid="${fid}" class="box" type="${type}" uid="${uid}" time="${ctime}">',
			'<a class="user_face" href="${location}?dkcode=${dkcode}" title="${uname}">',
				'<img width="50" height="50" src="${headpic}" alt="${uname}" />',
			'</a>',
			'<div class="wrap">',
				'<div class="content">',
					'<div class="info head"><strong><a title="${uname}" href="${location}?dkcode=${dkcode}">${uname}</a></strong></div>',
					'<div class="info">$${content}</div>',
					'<div class="mediaContent info center">',
						'<div class="media_prev" fid="${fid}">',
							'<img width="403" height="300" alt="" src="${host}${imgurl}">',
							'<a href="javascript:void(0);" class="showFlash">',
								'<img style="top:125px;left:184px;" src="' + CONFIG["misc_path"] + 'img/system/feedvideoplay.gif" alt="">',
							'</a>',
						'</div>',
						'<div class="media_disp hide">',
							'<div id="video_${fid}"></div>',
						'</div>',
					'</div>',

					'<div class="commentBox pd" time="${friendly_time}" action_uid="${uid}" ctime="${ctime}" pagetype="${type}" msgname="${title}" commentobjid="${fid}" complete="true"></div>',
				'</div>',
			'</div>',
		'</li>'
	].join("");

	F.prototype.init = function(data) {
		var infoHtml = juicer(template, data);

		return $(infoHtml);
	};

	return F;
})();