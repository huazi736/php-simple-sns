
/*
	Create 2012-07-28
	@ author 卜海亮
	@ name 《首页 - 房屋频道关注信息流 - "album" 模块》
	desc 
*/
var Class_follow_house_photo = (function() {
	var F = function() {};

	var template = [
		'<li id="${tid}" fid="${fid}" class="box" type="${type}" uid="${uid}" time="${ctime}">',
			'<a class="user_face" href="${web_url}" title="${uname}">',
				'<img width="50" height="50" src="${headpic}" alt="${uname}" />',
			'</a>',
			'<div class="wrap" type="${type}">',
				'<div class="content">',
					'<div class="info head"><strong><a title="${uname}" href="${web_url}">${uname}</a></strong></div>',
					'<div class="info">',
						'<div>$${content}</div>',
						'<div>',
							'<ul class="photoContent clearfix">',
								'$${picurl,host,dkcode,pid|createGamePhotoLst}',
							'</ul>',
						'</div>',
					'</div>',
					'<div class="commentBox pd" time="${friendly_time}" action_uid="${uid}" ctime="${ctime}" pagetype="web_${type}" msgname="${title}" commentobjid="${fid}" complete="true"></div>',
				'</div>',
			'</div>',
		'</li>'
	].join("");

	var createGamePhotoLst = function(picsInfo,host,dkcode,webid) {
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

            str += '<li class="' + firstPhoto + ' ' + hidden + '" style="height:' + _height + 'px" ><a href="javascript:void(0);" action_dkcode="' + dkcode + '" pid="' + v.pid + '" url="' + mk_url('walbum/photo/get',{photoid:v.pid,dkcode:dkcode, web_id: webid}) + '" class="photoLink"><img src="' + picurl + '" alt="" style="max-width:838px;_width:' + _width + '"/></a></li>';
        });
		
		return str;
	};
	juicer.register("createGamePhotoLst", createGamePhotoLst);

	F.prototype.init = function(data) {
		var infoHtml = juicer(template, data);
		
		return $(infoHtml);
	};

	return F;
})();


/*
	Create 2012-07-28
	@ author 卜海亮
	@ name 《首页 - 房屋频道关注信息流 - "info" 模块》
	desc 
*/
var Class_follow_house_info = (function() {
	var F = function() {};

	var template = [
		'<li id="${tid}" fid="${fid}" class="box" type="${type}" uid="${uid}" time="${ctime}">',
			'<a class="user_face" href="${web_url}" title="${uname}">',
				'<img width="50" height="50" src="${headpic}" alt="" />',
			'</a>',
			'<div class="wrap" type="${type}">',
				'<div class="content">',
					'<div class="info head"><strong><a title="${uname}" href="${web_url}">${uname}</a></strong></div>',
					'<div class="info">$${content}</div>',
				
					'<div class="commentBox pd" time="${friendly_time}" action_uid="${uid}" ctime="${ctime}" pagetype="web_${type}" msgname="${title}" commentobjid="${tid}" complete="true"></div>',
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
	Create 2012-07-28
	@ author 卜海亮
	@ name 《首页 - 房屋频道关注信息流 - "video" 模块》
	desc 
*/
var Class_follow_house_video = (function() {
	var F = function() {};

	var template = [
		'<li id="${tid}" fid="${fid}" class="box" type="${type}" uid="${uid}" time="${ctime}">',
			'<a class="user_face" href="${web_url}" title="${uname}">',
				'<img width="50" height="50" src="${headpic}" alt="${uname}" />',
			'</a>',
			'<div class="wrap" type="${type}">',
				'<div class="content">',
					'<div class="info head"><strong><a title="${uname}" href="${web_url}">${uname}</a></strong></div>',
					'<div class="info">$${content}</div>',
					'<div class="mediaContent info center">',
						'<div class="media_prev" fid="${fid}">',
							'<img width="403" height="300" alt="" src="${host}${imgurl}">',
							'<a href="javascript:void(0);" class="showFlash">',
								'<img style="top:125px;left:184px;" src="' + CONFIG["misc_path"] + 'img/system/feedvideoplay.gif" alt="">',
							'</a>',
						'</div>',
						'<div class="media_disp hide">',
							'<div id="${fid}"></div>',
						'</div>',
					'</div>',

					'<div class="commentBox pd" time="${friendly_time}" action_uid="${uid}" ctime="${ctime}" pagetype="web_${type}" msgname="${title}" commentobjid="${fid}" complete="true"></div>',
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
	Create 2012-07-28
	@ author 卜海亮
	@ name 《首页 - 房屋关注信息流 - "event" 模块》
	desc 
*/
var Class_follow_house_event = (function() {
	var F = function() {};

	var template = [
		'<li id="${tid}" fid="${fid}" class="box" type="${type}" uid="${uid}" time="${ctime}">',
			'<a class="user_face" href="${web_url}" title="${uname}">',
				'<img width="50" height="50" src="${headpic}" alt="${uname}" />',
			'</a>',
			'<div class="wrap" type="${type}">',
				'<div class="content">',
					'<div class="info head"><strong><a title="${uname}" href="${web_url}">${uname}</a></strong></div>',
					'<div class="info">',
						'$${title,fid,pid|createGameEventLink}',
					'</div>',
				
					'<div class="commentBox pd" time="${friendly_time}" action_uid="${uid}" ctime="${ctime}" pagetype="web_${type}" msgname="${title}" commentobjid="${fid}" complete="true"></div>',
				'</div>',
			'</div>',
		'</li>'
	].join("");

	var createGameEventLink = function(title, event_id, web_id) {
		var str = "";

		str = '<div><a target="_blank" href="' + mk_url('wevent/event/detail', {id: event_id, web_id: web_id}) + '">' + title + '</a></div>';
		
		return str;
	};
	juicer.register("createGameEventLink", createGameEventLink);

	F.prototype.init = function(data) {
		var infoHtml = juicer(template, data);

		return $(infoHtml);
	};

	return F;
})();

/*
	Create 2012-07-28
	@ author 卜海亮
	@ name 《首页 - 房屋关注信息流 - "album" 模块》
	desc 
*/
var Class_follow_house_album = (function() {
	var F = function() {};

	var template = [
		'<li id="${tid}" fid="${fid}" class="box" type="${type}" uid="${uid}" time="${ctime}">',
			'<a class="user_face" href="${web_url}" title="${uname}">',
				'<img width="50" height="50" src="${headpic}" alt="${uname}" />',
			'</a>',
			'<div class="wrap" type="${type}">',
				'<div class="content">',
					'<div class="info head"><strong><a title="${uname}" href="${web_url}">${uname}</a></strong></div>',
					'<div class="info">',
						'<div>$${content}</div>',
						'<div>',
							'<ul class="photoContent clearfix">',
								'$${picurl,host,dkcode,pid|createGameAlbumLst}',
							'</ul>',
						'</div>',
					'</div>',
					'<div class="commentBox pd" time="${friendly_time}" action_uid="${uid}" ctime="${ctime}" pagetype="web_${type}" msgname="${title}" commentobjid="${fid}" complete="true"></div>',
				'</div>',
			'</div>',
		'</li>'
	].join("");

	var createGameAlbumLst = function(picsInfo,host,dkcode, webid) {
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

            str += '<li class="' + firstPhoto + ' ' + hidden + '" style="height:' + _height + 'px" ><a href="javascript:void(0);" action_dkcode="' + dkcode + '" pid="' + v.pid + '" url="' + mk_url('walbum/photo/get',{photoid:v.pid,dkcode:dkcode, web_id: webid}) + '" class="photoLink"><img src="' + picurl + '" alt="" style="max-width:838px;_width:' + _width + '"/></a></li>';
        });

		return str;
	};
	juicer.register("createGameAlbumLst", createGameAlbumLst);

	F.prototype.init = function(data) {
		var infoHtml = juicer(template, data);
		
		return $(infoHtml);
	};

	return F;
})();


/*
	Create 2012-07-28
	@ author 卜海亮
	@ name 《首页 - 房屋频道关注信息流 - "forward" 模块》
	desc 
*/
var Class_follow_house_forward = (function() {
	var F = function() {};

	var template = [
		'<li id="${tid}" fid="${fid}" class="box" type="${type}" uid="${uid}" time="${ctime}">',
			'<span>Forward显示在这</span>',
		'</li>'
	].join("");

	F.prototype.init = function(data) {
		var infoHtml = juicer(template, data);

		return $(infoHtml);
	};

	return F;
})();