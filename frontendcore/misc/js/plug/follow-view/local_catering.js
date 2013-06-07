
/*
	Create 2012-7-26
	@ author sentsin(贤心)
	@ name 《首页 - 关注信息流 - 本地生活 模块》
	desc 
*/

var Class_follow_local_catering = (function() {
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