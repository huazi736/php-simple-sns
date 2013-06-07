/**
 * author: DongWeiliang
 * Date: 2012-07-28
 * version: 0.9
 * description: setting Ads
 */

var SettingAds = function () {}

SettingAds.prototype.saveBankInfo = function(_data,_url) {
	$.ajax({
		url: _url,
		type: 'post',
		dataType: 'json',
		data: {data:_data},
		success: function (data) {
			if (data.status == 1) {
				$.alert('操作成功');
				window.location.reload();
			} else {
				$.alert(data.info);
			};
		}
	});
};

var settingAds = new SettingAds();
$(function () {
	//	show hide selset ads switch
	$('#settingAds').click(function () {
		$('.settingAds').show();
		$('.initview').hide();
	});
	//	close switch
	$('#no_save_ad_setting').click(function () {
		window.location.reload();
	});

	//	checked yes
	$('#show_ads').click(function () {
		$('.prot').show();
	});

	//	checked no
	$('#off_ads').click(function () {
		$('input[name="toShowAds"]').prop('checked', false);
		$('.step1,.step2,.prot').hide();
	});

	// agree
	$('input[name="toShowAds"]').click(function () {
		if ($(this).prop('checked')) {
			$('.step1').show();
		} else {
			$('.step1,.step2').hide();
		}
	});

	//	custom selset
	$('#cus_select').click(function () {
		$('.step2').show();
	});

	//	get different ads
	var _page = 1;		
	$('#nextAds').click(function () {
		var _checked = [],
			getUrl = mk_url('main/setting/adList'),
			target = $('#difAds'),
			checked_li = $('#selectedAds').children('li');

			for (var i = 0; i < checked_li.length; i++) {
				_checked.push(checked_li.eq(i).attr('data-ad-id'));
			};

			target.html('<div class="select-loading nothing-tip f14 color9 tac"></div>');
		$.ajax({
			url: getUrl,
			dataType: 'json',
			type: 'POST',
			data: {pager: _page, ad_id_check: _checked},
			success: function (data) {
				if(data.status == 1) {	
					$('.select-loading').hide();
					if (data.data.adRs.length) {				
						var rs = data.data.adRs;
						for (var i = 0; i < rs.length; i++) {
							var _html = '';
								_html += '<li class="clearfix posr" data-ad-id="'+rs[i].ad_id+'"><span class="check-ads"><input type="checkbox" name="check_ads[]" ></span>';
								_html += '<dl><dt><a class="ads_title" target="_blank" href="'+rs[i].ad_url+'">'+rs[i].ad_title+'</a></dt>';
								_html += '<dd class="extAdsIntro clearfix wdb"><a target="_blank" href="'+rs.ad_url+'"><img class="ads_media_url" alt="'+rs[i].ad_title+'" src="'+rs[i].ad_media_uri+'"><span class="ads_intro">'+rs[i].ad_introduce+'</span></a></dd></dl></li>';
								target.append(_html);
						};
						if(!data.data.hasNext){
							_page = 0;	
						}
					} else {
						$('#difAds').html('<div class="tac color9 f14 nothing-tip">啊哦，已经到最后一页了，继续“换一批”吧！</div>');
						_page = 0;	
					}
				}
			}
		});
		_page ++;
	});	

	//	remove selected ads
	$('#selectedAds').delegate('.close-pro', 'click', function () {
		$(this).closest('li').remove();
		if ($('#selectedAds').children('li').length === 0) {
			$('#selectedAds').addClass('nothing');
			$('#selectedAds').html('<p class="tac color9 f14 nothing-tip">您尚未选择任何广告，请通过右上角的“我要自选”按钮进行广告选择</p>')
		};
	});

	//	check Ads which you like
	$('#difAds').delegate('.check-ads > input', 'click', function (event) {
		var _self = $(this),
			_ad_id = _self.closest('li').attr('data-ad-id'),
			_title = _self.closest('li').find('.ads_title').text(),
			_img_url = _self.closest('li').find('.ads_media_url').attr('src'),
			_intro = _self.closest('li').find('.ads_intro').text(),
			_url = _self.closest('li').find('.ads_title').attr('href'),
			$org = $('#selectedAds').children('li');
		
		var _html = '';
			_html = '<li class="clearfix posr" data-ad-id="'+_ad_id+'"><a herf="javascript:;" class="close-pro" title="移除"></a>';
			_html += '<dl><dt><a href="'+_url+'" target="_blank">'+_title+'</a></dt>';
			_html += '<dd class="extAdsIntro clearfix wdb"><a href="'+_url+'" target="_blank"><img src="'+_img_url+'" alt=""><span>'+_intro+'</span></a></dd></dl>';
			_html +='</li>';
		if ($(this).prop('checked')) {
			if ($org.length === 0) {
				$('.nothing-tip').remove();
				$('#selectedAds').removeClass('nothing');
			};
			if($org.length < 8){
				for (var i = 0; i < $org.length; i++) {
					if($org.eq(i).attr('data-ad-id') == _ad_id) {
						event.preventDefault();
						$.alert('您已经选择了相同的广告，请不要重复选择');
						return false;
					} 
				};
				$(this).closest('li').addClass('bg-blue-it');
				$('#selectedAds').append(_html);
			} else {
				event.preventDefault();
				$.alert('您已经选了8条广告，不能再选其他的了。如果想要选择新的，请删除您不想投放的广告。')
			}
		} else {
			for (var i = 0; i < $org.length; i++) {
				if($org.eq(i).attr('data-ad-id') == _ad_id) {
					$org.eq(i).remove();
				} 
			};
			$(this).closest('li').removeClass('bg-blue-it');
			if ($('#selectedAds').children('li').length === 0) {
				$('#selectedAds').addClass('nothing');
				$('#selectedAds').html('<p class="tac color9 f14 nothing-tip">您尚未选择任何广告，请通过右上角的“我要自选”按钮进行广告选择</p>')
			};
		};
	});

	//save data
	$('#save_ad_setting').click(function () {
		if ($('#show_ads').prop('checked')) {
			if (!$('input[name="toShowAds"]').prop('checked')) {
				$.alert('未同意《端口网广告投放协议》，将无法开启广告投放');
				return false;
			};
		};
		var _ad_id = [],
			_status = 1,
		_li_data = $('#selectedAds').children('li'),
		_url = mk_url('main/setting/setAdPost/');

		if($('#show_ads').prop('checked')){
			_status = 0;
			for (var i = 0; i < _li_data.length; i++) {
				_ad_id.push(_li_data.eq(i).attr('data-ad-id'));
			};
			if (_ad_id.length === 0) {
				$.alert('您尚未选择任何广告,请选择！');
				return false;
			};
		}

		$.ajax({
			url: _url,
			type: 'post',
			dataType: 'json',
			data: {'ad_id': _ad_id, 'status': _status},
			success: function (data) {
				if (data.status == 1) {
					$.alert('操作成功');
					window.location.href = mk_url('main/setting/settingAds');
				};
			}
		});
	});

	//	提现切换
	$('.switch-pay li').click(function () {
		var _self = $(this),
			_index = $(this).index();
		if (!_self.hasClass('on')) {
			_self.siblings().removeClass('on');
			_self.addClass('on');
			$('#switch-cont > ul').hide();
			$('#switch-cont > ul').eq(_index).show();
		};
	});

	//	提现按钮
	$('#kiting').click(function () {
		$('.income-setting').slideDown();
	});


	
	//	保存alipay
	$('#save_alipay_info').click(function () {
		var _alipay_name = $('#alipay-name').val(),	
			_alipay_real_name = $('#alipay-real-name').val(),
			_rmb = $('#alipay-name').attr('data-price'),
			_data = {title: '支付宝',number: _alipay_name, name: _alipay_real_name, money: _rmb,type: 1},
			_url = mk_url('main/setting/adsaccountPost');
		settingAds.saveBankInfo(_data, _url);
	});

	//	保存bank
	$('#save_bank_info').click(function () {
		var _bank = $('#bank').val(),	
			_bank_real_name = $('#bank-real-name').val(),
			_bank_card_num = $('#bank-card-num').val(),
			_rmb = $('#bank-real-name').attr('data-price'),
			_data = {title: _bank, name: _bank_real_name, number: _bank_card_num, money: _rmb,type: 2},
			_url = mk_url('main/setting/adsaccountPost');
		settingAds.saveBankInfo(_data, _url);
	});
});