/*
 *@author:    Dongweiliang
 *@created:   2012/6/13
 *@desc:      personal ads
 *@version:   v0.9
 */
;function ADS(){
	this.init();
}

ADS.prototype = {
	init: function () {
		this.view();
	},

	view: function () {
		this.getAdsMaxNum();
		this.closePro();
		$(window).resize(this.reBuildAds);
		$(window).scroll(this.reBuildAds);
		$('#sideArea a').bind('click',this.reBuildAds);

	},

	//调整广告显示
	reBuildAds: function () {
		var $sdArea = $('#sideArea'),
		$adsArea = $('#adsArea'),
		clientWidth = $(window).width(),
		clientHeight = $(window).height(),		
		$adw = $('#adsArea,.extAdsUrl,.extAds dt,.extAds dd');		

		if ($sdArea.length) {
			sideAreaHeight = $sdArea.outerHeight(),
			sideAreaMarginTop = parseInt($sdArea.css('margin-top')),
			sideAreaFinalHeight = sideAreaHeight + sideAreaMarginTop + 30,
			adsLastHeight = clientHeight - sideAreaFinalHeight - 25;
		} else {
			adsLastHeight = clientHeight - 25;
		};

		if (clientWidth > 1050) {
			$adw.css('width','220px');
			$adw.find('span').css({'width':'100px','float':'right'});
			if(adsLastHeight > 132){
				$adsArea.show().find('li').show();
				var visNum = Math.floor(adsLastHeight/132) - 1;
				$('.extAds li:gt('+visNum+')').hide();
			} else {
				$adsArea.hide();
			}
		} else {
			$adw.css('width','120px');
			$adw.find('span').css({'width':'110px','float':'left'});
			if(adsLastHeight > 222){
				$adsArea.show().find('li').show();
				var visNum = Math.floor(adsLastHeight/222) - 1;
				$('.extAds li:gt('+visNum+')').hide();
			}else{
				$adsArea.hide();
			}
		};
	},

	// 广告首次加载显示
	firstAdsLoad: function () {
		var clientWidth = $(window).width(),
			$adw = $('#adsArea,.extAdsUrl,.extAds dt,.extAds dd');
		if (clientWidth < 1050) {
			$adw.css('width','120px');
			$adw.find('span').css({'width':'110px','float':'left'});
		} else {
			$adw.css('width','220px');
			$adw.find('span').css({'width':'100px','float':'right'});
		}
	},

	// 获取广告最多可显示个数
	getAdsMaxNum: function () {
		var _self = this,
			clientHeight = $(window).height(),
			clientWidth = $(window).width(),
			loadTimeLine = setInterval(timeLineHeight, 200);
		function timeLineHeight () {
			var $sdArea = $('#sideArea'),
				$adsArea = $('#adsArea');
			if ($sdArea.length) {
				if($sdArea.attr('data-loaded')){
					var sideAreaHeight = $sdArea.outerHeight(),
						sideAreaMarginTop = parseInt($sdArea.css('margin-top')),
						sideAreaFinalHeight = sideAreaHeight + sideAreaMarginTop + 30,
						adHeight = clientHeight - sideAreaFinalHeight - 25;
						if(clientWidth > 1050){
							if(adHeight > 132){							
								var adsNum = Math.floor(adHeight/132);

							} else {
								var adsNum = 0;
							}
						} else {
							if(adHeight > 222){
								var adsNum = Math.floor(adHeight/222);
							}else{
								var adsNum = 0;
							}
						}
					
					_self.postForAds(adsNum);
					clearInterval(loadTimeLine);
				}
			} else {
					var adHeight = clientHeight - 25;
						if(clientWidth > 1050){
							if(adHeight > 132){							
								var adsNum = Math.floor(adHeight/132);

							} else {
								var adsNum = 0;
							}
						} else {
							if(adHeight > 222){
								var adsNum = Math.floor(adHeight/222);
							}else{
								var adsNum = 0;
							}
						}
					
					_self.postForAds(adsNum);
					// clearInterval(loadTimeLine);
			}

		}

	},

	// post相关信息，以便获取广告
	postForAds: function (adsNum) {
		if(CONFIG['action_dkcode'] != CONFIG['dkcode']){
			var _self = this,
				maxadsNum = adsNum,
				postURL = mk_url('ads/ad/getPersonalAd', {num: maxadsNum, dkcode: CONFIG['action_dkcode'], callback: 'getPersonPro'});
			$.getScript(postURL,function() {
				var res = window.__data;
				if (res !== undefined) {
					if (res.status == 1) {
						_adsData = res.data;
						if(_adsData.length > 0) {
							var html = '';
								html += '<h6>赞助链接</h6><ul class="extAds clearfix">';
							$.each(_adsData, function (i, item) {
								var _url = mk_url("ads/ad/adRedirect", {t: item.t, index:item.index, dkcode:CONFIG['action_dkcode']});
								html += '<li class="clearfix posr"><a href="javascript:;" class="close-pro" title="隐藏该条广告"></a><dl><dt><a href="'+_url+'" target="_blank">' + item.title + '</a></dt>';
								// html += '<dd class="extAdsUrl">' + item.url + '</dd>';
								html += '<dd class="extAdsIntro clearfix wdb"><a href="'+_url+'" target="_blank"><img src="' + item.media_uri + '" alt="' + item.title + '"><span>' + item.introduce + '</span></a></dd></dl></li>' ;
							});
								html += '</ul>';
							$('#adsArea').append(html);
							_self.firstAdsLoad();
						}
					} 

				};
			});
		}
	},

	// 关闭广告
	closePro: function () {
		$('#adsArea').delegate('.close-pro', 'click', function () {
			var _self = $(this),
				_li = _self.closest('li'),
				_ul = _self.closest('ul'),
				_liNum = _ul.find('li').length,
				_adsArea = $('#adsArea');
			if(_liNum != 1) {
				_li.fadeOut(200, function () {
					_li.remove();
				});
			} else {
				_adsArea.fadeOut(200, function () {
					_adsArea.remove();
				});
			}

		})
	}


}



$(function () {
	var ads = new ADS();

});


function getPersonPro (data) {
	window.__data = data;
}


