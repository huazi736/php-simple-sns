/*
	Create 2012-7-23
	@ author xuxinjian(贤心)
	@ name 《首页 - 关注信息流 - "info" 模块》
	desc 
*/
var Class_follow_local_info = (function() {
	var F = function() {};

	var template = [
		'<li id="${tid}" fid="${fid}" class="box" type="${type}" uid="${uid}" time="${ctime}">',
			'<a class="user_face" href="${location}?dkcode=${dkcode}" title="${uname}">',
				'<img width="50" height="50" src="${headpic}" alt="" />',
			'</a>',
			'<div class="wrap">',
				'<div class="content">',
					'<div class="info head"><strong><a title="${uname}" href="${location}?dkcode=${dkcode}">${uname}</a></strong></div>',
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
	Create 2012-7-26
	@ author sentsin(贤心)
	@ name 本地生活 模块
	desc 
*/

var Class_follow_local_dish = (function(){
	var F = function() {};

	var template = [
		'<li id="${tid}" fid="${fid}" class="box" type="${type}" uid="${uid}" time="${ctime}">',
			'<a class="user_face" href="${location}?dkcode=${dkcode}" title="${uname}">',
				'<img width="50" height="50" src="${headpic}" alt="" />',
			'</a>',
			'<div class="wrap">',
				'<div class="content local_cont">',
					'<div class="info head"><strong><a title="${uname}" href="${location}?dkcode=${dkcode}">${uname}</a></strong></div>',
					'<div class="info local_indexTime">$${friendly_time} <span>发布了<em>菜品</em></span></div>',
					'<div style="clear:both"></div>',
					'<div class="local_indexbox">',
					'<div class="local_indexImg"><img src="$${dish.pics[0].b.url}" /></div>',
					'<div class="local_indexName"><strong class="local_name">$${dish.name}</strong><strong class="local_cj">菜价：￥$${dish.price}</strong></div>',
					'<div class="local_desc">$${dish.description}</div>',
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


var Class_follow_local_groupon = (function(){
	var F = function() {};
		
	var template = [
		'<li id="${tid}" fid="${fid}" class="box local_grouponBox" type="${type}" uid="${uid}" time="${ctime}">',
			'<a class="user_face" href="${location}?dkcode=${dkcode}" title="${uname}">',
				'<img width="50" height="50" src="${headpic}" alt="" />',
			'</a>',
			'<div class="wrap">',
				'<div class="content local_cont">',
					'<div class="info head"><strong><a title="${uname}" href="${location}?dkcode=${dkcode}">${uname}</a></strong></div>',
					'<div class="info local_indexTime">$${friendly_time} <span>发布了<em>促销活动</em></span></div>',
					'<div style="clear:both"></div>',
					'<div class="local_groupon local_indexbox">',
					'<h2 class="group_showtitle"><a href="$${groupon.link}" target="_blank">$${groupon.title}</a></h2>',
					'<div class="group_showimg"><a href="$${groupon.link}" target="_blank"><img src="$${groupon.img[0].b.url}" /></a><span>原价：<del>$${groupon.original_price}</del>元 <em>折扣：$${groupon.discount}折</em><!--<em class="group_showtime">剩余时间：<i>$${diff}</i></em>--></span></div>',
					'<p class="group_showgosee">促销价 ￥<em>$${groupon.current_price}</em><a href="$${groupon.link}" target="_blank">现在去看看</a></p>',
					'</div>',
					'<div class="commentBox pd" time="${friendly_time}" action_uid="${uid}" ctime="${ctime}" pagetype="web_${type}" msgname="${title}" commentobjid="${fid}" complete="true"></div>',
				'</div></div>',
			'</div>',
		'</li>'
	].join("");
	
	
	local_follow = {
		init : function(index){
			var othis = this;
			$('.group_showtime').each(function(e){
				var diffTex = $(this).find('i').html();
				othis.showEndTime(diffTex , e);
			});
		},
		showEndTime : function(diffS , e , index){ //促销倒计时
			var day,hour,mins,secs,diff = '无限期'; //默认
			var groupEnd = '团购已结束';
			var _i = $('.group_showtime').length;
			var infoGroup = {
				appendTime : function(){
					if(day > 0){
						diff = '<strong class="groupon_day">' + day + '</strong>天<strong class="groupon_hour">' + hour + '</strong>小时<strong class="groupon_mins">' + mins + '</strong>分<strong class="groupon_secs">' + secs + '</strong>秒';
					}else{
						if(hour > 0){
							diff = '<strong class="groupon_hour">' + hour + '</strong>小时<strong class="groupon_mins">' + mins + '</strong>分<strong class="groupon_secs">' + secs + '</strong>秒';
						}else{
							if(mins > 0){
								diff = '<strong class="groupon_mins">' + mins + '</strong>分<strong class="groupon_secs">' + secs + '</strong>秒';
							}else{
								diff = '<strong class="groupon_secs">' + secs + '</strong>秒';
							}
						}
					}
					 $('.group_showtime').eq(e).html('剩余时间：' + diff);
				},
				parseDateTime : function(second , e){
					day = parseInt(second/(24*3600));
					hour = parseInt((second - day*24*3600)/3600);
					mins = parseInt((second - day*24*3600 - hour*3600)/60);
					secs = second - day*24*3600 - hour*3600 - mins*60;
					//day = 0,hour = 0,mins = 0,secs = 10;
					if(second <= 0){
						$('.group_showtime').eq(e).html(groupEnd);
					}else{
						this.appendTime();
					}
					if(index == 0){
						this.run();
					}
				},
				contime : function(){
					var _d,_h,_m,_s;
					$('.group_showtime').each(function(e){
						_d = $(this).find('.groupon_day');
						_h = $(this).find('.groupon_hour');
						_m = $(this).find('.groupon_mins');
						_s = $(this).find('.groupon_secs');
						_dval = _d.html();
						_hval = _h.html();
						_mval = _m.html();
						_sval = _s.html();
						var _len = $(this).find('strong').length;
						if(_len > 0){
							_sval--;
							if(_sval < 0){
								if(_len == 1){	//秒
									$(this).find('i').html(groupEnd);
								}else if(_len == 2){	//分、秒
									_sval = 59;
									if(_mval > 1 ){
										_mval--;
										_m.html(_mval);

									}else{
										$(this).find('i').html('<strong class="groupon_secs">' + _sval + '</strong>秒');
									}
								}else if(_len == 3){	//时、分、秒
									_sval = 59;
									if(_mval > 0){
										_mval--;
										_m.html(_mval);
									}else{
										_mval = 59;
										if(_hval > 1){
											_hval--;
											_m.html(_mval);
											_h.html(_hval);
										}else{
											$(this).find('i').html('<strong class="groupon_mins">' + _mval + '</strong>分<strong class="groupon_secs">' + _sval + '</strong>秒');
										}
									}
								}else if(_len == 4){	//天、时、分、秒
									_sval = 59;
									if(_mval > 0){
										_mval--;
										_m.html(_mval);
									}else{
										_mval = 59;
										if(_hval > 0){
											_hval--;
											_m.html(_mval);
											_h.html(_hval);
										}else{
											_hval = 23;
											if(_dval > 1){
												_dval--;
												_m.html(_mval);
												_h.html(_hval);
												_d.html(_dval)
											}else{
												$(this).find('i').html('<strong class="groupon_hour">' + _hval + '</strong>小时<strong class="groupon_mins">' + _mval + '</strong>分<strong class="groupon_secs">' + _sval + '</strong>秒');
											}
										}
									}
								}
							}
							_s.html(_sval);
						}

					});
				},
				run : function(){
					t = setInterval(this.contime,1000);
				}
			}
			infoGroup.parseDateTime(diffS , e)
			/*=============== End 促销倒计时 ===================*/
		}
		
	};
	
	F.prototype.init = function(data) {
		var infoHtml = juicer(template, data);
		//local_follow.init(data.index);
		return $(infoHtml); 
		
	};

	return F;
})();

