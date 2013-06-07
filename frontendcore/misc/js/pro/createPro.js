/*
 *@author:    Dongweiliang
 *@created:   2012/6/13
 *@desc:      create Ads
 *@version:   v0.9
 */
var createAds = {

	// post表单
	postCreateAds: function (postURL, jumpUrl) {
		var _self = this,
			_ad_id = $('input[name="ad_id"]').val() || '',
			_classify = $('#classify option:selected').val(),
			_postURL = postURL,
			_jumpUrl = jumpUrl,
			_url = $('#url').val(),
			_title = $('#title').val(),
			_introduce = $('#introduce').val(),
			_img_url = $('#img_url').val(),
			_Mf_img = $('#Mf_img').val(),
			_region_rank = 1,
			_region_id = [],		
			_region_name = [],	
			_city = [],
			_gender = $('input[name="gender"]:checked').val(),
			_gender_name = $('input[name="gender"]:checked').next().text(),
			_age_range = $('#age_range').val(),
			_age_range_name = $('#age_range option:selected').text(),
			_interest = createAds._finalInsData,
			_interest_name = createAds._finalInsName,
			_name = $('#name').val(),
			_budget = null,
			_budget_sort = 0,
			_charge_mode = $('input[name="pay_type"]:checked').val(),
			_bid = 0,
			_is_display = $('input[name="put_in_time"]:checked').val(),
			_start_time = null,
			// _end_time = $('#date_end').val(),
			isNull = /^[\s' ']*$/,
			siPattern = /^(([1-9]\d*)|0)(\.\d{1,2})?$/,
			regUrl = /^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/;
	
			if(_title.length === 0 || isNull.test(_title) ){
				$.alert('请输入广告标题!');
				return false;
			} 

			if(_url.length < 8 || isNull.test(_url) || !regUrl.test(_url)){
				$.alert('请输入正确的目标URL地址!');
				return false;
			}

			if(_introduce.length === 0 || isNull.test(_introduce)){
				$.alert('请输入广告内容描述!');
				return false;
			} 

			if(_img_url.length === 0){
				$.alert('请上传广告图片!');
				return false;
			} 

			if ($('#classify option:selected').val() == 1) {
				if ($('#areaRs span').length < 1) {
					$.alert('请选择广告投放地区!');
					return false;
				};
				_region_rank = $('#areaRs span').attr('data-region-rank');
				if ($('#areaRs').hasClass('pr_status')) {
					for (var i = 0; i < $('#areaRs .prCities').length; i++) {
						_city.push($('#areaRs .prCities').eq(i).attr('value'));						
					};									
				} else  {
					for (var i = 0; i < $('#areaRs span').length; i++) {
						_city.push($('#areaRs span').eq(i).attr('data-last'));
					};
				};

				for (var i = 0; i < $('#areaRs span').length; i++) {
					_region_id.push($('#areaRs span').eq(i).attr('data-last'));
					_region_name.push($('#areaRs span').eq(i).attr('data-area-name'));
				};
			} 


			if($('#classify').val() == 1) {
				if ($('#getCate input[type=checkbox]:checked').length === 0) {
					$.alert('请选择兴趣分类！');
					return false;
				};		
			}			

			if (_name.length === 0 || isNull.test(_name)) {
				$.alert('请输入广告名称(不能全为空)，便于后期管理!');
				return false;
			};

			if ($('#budget').val().length === 0 || $('#budget').val() <= 0 ) {
				$.alert('当前预算值为空或者为0，请重新输入！');
				return false;
			} else {
				if (siPattern.test($('#budget').val())) {
					if($('select[name="budget_type"]').val() == 0 ){
						var _lowset_day = parseFloat($('#budget').attr('data-lowest-price-day'));
						if (parseFloat($('#budget').val()) < _lowset_day) {
							$.alert('每日最低预算须大于￥ '+_lowset_day+'元，请重新输入！');
							return false;
						} else {
							_budget_sort = 0;
							_budget = $('#budget').val();
						};
					} else {
						var _lowset_day = parseFloat($('#budget').attr('data-lowest-price-all'));
						if (parseFloat($('#budget').val()) < _lowset_day) {
							$.alert('预算总额最低须大于￥ '+_lowset_day+'元，请重新输入！');
							return false;
						} else {
							_budget_sort = 1;
							_budget = $('#budget').val();
						};
					}
				} else {
					$.alert('您输入的预算格式不正确，请重新输入!');
					return false;
				};
			};


			if ($('#classify option:selected').val() == 1) {	//如果是web就分开
				if($('input[name="pay_type"]:checked').val() == "CPM" ){
					if ($('#cpm_price').val().length) {
						if ($('#cpm_price').val() <= 0) {
							$.alert('当前竞价为空或者为0，请重新输入！');
							return false;
						};
						if (siPattern.test($('#cpm_price').val())) {
							_bid = $('#cpm_price').val();
						} else {
							$.alert('您输入的竞价格式不正确，请重新输入!');
							return false;
						};
					} else {
						$.alert('请输入您的竞价，已便获得合理的广告效果!');
						return false;
					};
					
				} else {
					if ($('#cpc_price').val().length) {
						if ($('#cpc_price').val() <= 0) {
							$.alert('当前竞价为空或者为0，请重新输入！');
							return false;
						};
						if (siPattern.test($('#cpc_price').val())) {
							_bid = $('#cpc_price').val();
						} else {
							$.alert('您输入的竞价格式不正确，请重新输入!');
							return false;
						}
					} else {
						$.alert('请输入您的竞价，已便获得合理的广告效果!');
						return false;
					};
				}	
			} else {
				_bid = $('#p_cpc_price').val();
				_charge_mode = 'CPC';
			};

			if(parseFloat(_budget) < parseFloat(_bid)){
				$.alert('您输入的预算值比单次点击的费用或者千次展示的低，请重新输入!');
				return false;
			}

			if(_is_display == -1 )	{
				_start_time = $('#date_a_start').val();
			}

			if ($('input[name="put_in_time"]:checked').val() == -1) {
				var _start_date_c = new Date($('#date_a_start').val().replace(/-/g,"/")),
					_std = new Date($('#date_a_start').attr('data-first-time').replace(/-/g,"/"));
		
				if (_std > _start_date_c) {
					$.alert('广告首次投放时间不能早于今天，广告修改后的投放时间不能早于上次投放时间！');
					return false;
				};
			}



			postData = {
				url: _url,
				title: _title,
				classify: _classify,
				introduce: _introduce,
				media_uri: _img_url,
				// local_path: _local_path,
				// allow_type: _allow_type,
				Mf_img: _Mf_img,
				region_rank: _region_rank,			
				region_id: _region_id,			
				region_name: _region_name,			
				city: _city,				
				gender: _gender,
				gender_name: _gender_name,
				age_range: _age_range,
				age_range_name: _age_range_name,
				name: _name,
				budget_sort: _budget_sort,
				budget: _budget,
				charge_mode: _charge_mode,
				bid: _bid,
				is_display: _is_display,
				start_time: _start_time,
				// end_time: _end_time,
				ad_id: _ad_id,
				interest: _interest,
				interest_name: _interest_name
			};


			$.ajax({
				type: 'post',
				url: postURL,
				cache: false,
				dataType: 'json',
				data: postData,
				success: function (res) {
					if(res === null){
						$.alert('发生错误了！');
					} else{
						if (res.status == 1) {
							// $.alert('提交成功，即将为你跳转！');
							window.location.href=_jumpUrl;
						} else if (res.status == 0) {
							$.alert(res.info);
						};
					}
				},
				error: function () {
					$.alert('发生错误了！');
				}
			});

	},

	//	判断余额情况
	checkBalance: function () {
		$.get(mk_url('ad/ads/'), function (data) {
			var _rs = data;

		})
	},

	changeLabel: function (txt) {
		var txtTmp = txt.replace(/</g, '&lt;'),
			jTxt = txtTmp.replace(/>/g, '&gt;'),
			yTxt = jTxt.replace(/'/g, '&acute;'),
			finalTxt = yTxt.replace(/"/g, '&quot;');
		return finalTxt;
	},

	//保存到数据库
	saveToDataBase: function (posturl) {

		$.ajax({
			type: 'POST',
			url : posturl,
			dataType: 'json',
			success: function (res) {
				if (res.status == 1) {
					var _jpUrl = res.data.url;
					if (res.data.valid) {
						$.confirm('提示', '<h3>操作成功</h3>');
					} else {
						$.alert('可用余额不足！');
					};
					window.location.href= _jpUrl;
				} else if (res.status == 0) {
					$.alert(res.info);
				};
			}
		})
	},

	//	兴趣名称
	_finalInsName: [],

	//	兴趣id
	_finalInsData: [],

	//广告预览
	adsPreview: function (id, target, default_des, maxlength) {
		var $id = $('#'+id);
		$id.bind('keyup', function () {
			if($(this).val().length > 0){
				var original = $(this).val();
				if (original.length > maxlength) {
					$(this).val(original.substring(0,maxlength));
					$('#adsPreview').find(target).text(original.substring(0,maxlength)).html();
				} else {
					$('#adsPreview').find(target).text(original).html();
				};
			} else {
				$('#adsPreview').find(target).text(default_des);
			}
		})
	},

	// 同意广告协议
	agreeContract: function () {
		if($('#agree').is(':checked')){		
	/*		$.ajax({
				url:mk_url('ads/ad/index'),
				type: 'POST',
				data: {'agree':'agree'}
				success: function () {
					window.location.href = mk_url('ads/ad/addAd');
				}
			});*/
			window.location.href = mk_url('ads/ad/addad');
		} else {
			$.alert('未同意DuanKou网广告合作细则，将不能投放广告');
		}
	},

	// 未同意广告协议
	disagreeContract: function () {
		window.history.go(-1);
	},

	//验证是否为空
	notNone: function (ele, txt) {
		if($(ele).val().length === 0){
			$.alert(txt);
			return;
		} 
	},

	//显示隐藏兴趣选择
	switchIns: function () {
		if ($('#classify').val() == 2) {
			$('.ads-target,.for-web').hide();
			$('.for-person').show()
		} else {
			$('.ads-target,.for-web').show();
			$('.for-person').hide();
		};
	},

	// 人数统计
	peopleCount: function () {
		$('#areaNum').addClass('ads-loading').text('');
		var _age_for_count = $('#age_range').val(),
			_sex_for_count = $('input[name="gender"]:checked').val();
			_region_rank = 1,
			_region_id_count = [],
			_region_rank = $('#areaRs span').attr('data-region-rank');
		if ($('#areaRs span').length === 0) {
			_region_id_count.push(1);
		} else {
			for (var i = 0; i < $('#areaRs span').length; i++) {
				_region_id_count.push($('#areaRs span').eq(i).attr('data-last'));
			};
		};


		$.ajax({
			url: mk_url('ads/ad/getUserCount'),
			type: 'POST',
			dataType: 'json',
			data: {
				'now_addr': _region_id_count,
				'age': _age_for_count,
				'sex': _sex_for_count
				// 'region_rank': _region_rank
			},
			success: function (res) {
				if(res !== null){
					if (res.status == 1) {
						$('#areaNum').removeClass('ads-loading');
						var areaNum = res.data;
						$('#areaNum').text(areaNum);
					} else if (res.status == 0) {
						$.alert(res.info);
					};
				}
			}
		});


	},

	// 展开下级分类
	getSubCate: function (ele) {
		var _self = ele,
			_level = parseInt(_self.attr('data-level')) + 1,
			_now_id = _self.attr('data-id'),
			_checkbox_status = _self.prev().children('input[type="checkbox"]').prop('checked'),
			_fid = _self.attr('data-fid'),
			_set_checkbox = '';
			if (_checkbox_status) {
				_set_checkbox = 'checked="true"';
			};

		if (_self.attr('data-extend') === 'on') {	//	判断当前是否是合拢
			
			if (_self.attr('data-loaded') === 'off') {	//	判断是否已经ajax请求过，没
				_self.after('<span class="v-loading"></span>');
				//	ajax获取下级分类			
				$.getJSON( mk_url('ads/ad/getInterestCategory', {pid: _now_id,  level: _level}), function (rs) {
					
					if (rs.status) {		
						_self.next('.v-loading').remove();	
						var _html = '';
						_html += '<ul class="subCateShow cateUl clearfix">';
						for (var i = 0; i < rs.data.length; i++) {				
							var _J_hasSub = '';
							
							if (rs.data[i].has_son === '0' || rs.data[i].has_son === '1') {_J_hasSub = 'J_hasSub'};		

							_html += '<li><label><input type="checkbox" data-id="'+_fid+'_'+rs.data[i].id+'" data-level="'+rs.data[i].level+'" data-fid="'+ _now_id +'" '+_set_checkbox+' class="cb subcate insCheck '+_J_hasSub+' " data-name="'+rs.data[i].name+'">&nbsp;'+rs.data[i].name+'</label>';

							if (rs.data[i].has_son === '0' || rs.data[i].has_son === '1') {
								_html += '<strong data-level="'+rs.data[i].level+'" title="显示下级分类" data-extend="on" data-loaded="off" data-fid="'+_fid+'_'+rs.data[i].id+'" data-id="'+rs.data[i].id+'" class="sub-cate J_subbtn">+</strong>';
							};

							_html += '</li>';

						};			

						_html += '</ul>';		

						_self.after(_html).show();
					};

					_self.attr('data-loaded', 'on');
				});


			} else {	//	判断是否已经ajax请求过，有，直接显示就可以
				_self.next().show();
			};

				
			_self.attr('data-extend','off').text('--');
			_self.attr('title', '隐藏下级分类');
			
		} else {								//	判断当前是否是闭合
			_self.next().hide();
			_self.attr('data-extend','on').text('+');
			_self.attr('title', '显示下级分类');
		};
		
		
	},

	// 两位小数数字
	priceType: function (obj) {
		obj.value = obj.value.replace(/[^\d.]/g,'');
		obj.value = obj.value.replace(/^\./g,'');
		obj.value = obj.value.replace(/\.{2,}/g,'.');
		obj.value = obj.value.replace(".","$#$").replace(/\./g,"").replace("$#$",".");
		obj.value = obj.value.replace(/^(\-)*(\d+)\.(\d\d).*$/,'$1$2.$3');
	},

	// 模拟placeholder
	supportPlaceholder: function () {
		var _support = 'placeholder' in document.createElement('input');
		if(!_support){
			var $pdr = $('.placeholder');
			for (var i = 0; i < $pdr.length; i++) {
				if(!$pdr.eq(i).val()){
				var pdrv = $pdr.eq(i).attr('placeholder');
				$pdr.eq(i).wrap('<div class="tempplaceholder" style="position:relative;float:left;"></div>');
				
				$inw = $pdr.eq(i).outerHeight();
				$pdr.eq(i).after('<span class="pdrs" style="display:block;position:absolute;color:#999;left:5px;top:0;height:'+$inw+'px; line-height:'+$inw+'px; z-index:2;">'+ pdrv +'</span>');

			}
			};
			
			//	点击获取焦点
			$('#createAds').delegate('.tempplaceholder', 'click', function () {				
				if($(this).find('.placeholder').attr('disabled') !== 'disabled'){					
					$(this).find('.placeholder').focus();
					$(this).find('.pdrs').hide();
				}
			});

			$('#createAds').delegate('.placeholder', 'focus', function () {
				$(this).next('.pdrs').hide();
			});

			$('#createAds').delegate('.placeholder', 'blur', function () {
				if (!$(this).val().length) {
					$(this).next('.pdrs').show();
				};
			});
		}
	},


	//	获取真正需要的cate_id
	getRealCateId: function () {
		createAds._finalInsData = [];
		createAds._finalInsName = [];
	    var all = $('#getCate').find('input[type="checkbox"]:checked');
	    if ($('#interest-type').prop('checked')) {
	    	createAds._finalInsData = $('#interest-type').attr('data-id').split(',');
	    	createAds._finalInsName = $('#interest-type').attr('data-name').split(',');
	    	return false;

	    } else {
		    all.each(function(index ,itm) {
		        itm = $(itm);
		        if(itm.hasClass("not_use")) {
		            return;
		        }
		        var hasSub = itm.hasClass("J_hasSub");
		      
		        if(hasSub) {
		            itm.parent().parent().find("ul input[type=checkbox]").addClass("not_use");
		        }
		    });
		    all.not(".not_use").each(function(index ,itm) {
		        itm = $(itm);
		        createAds._finalInsData.push(itm.attr("data-id"));
		        createAds._finalInsName.push(itm.attr("data-name"));
		        all.removeClass("not_use");
		    });

		}
	},

	// 兴趣选择
	selectIns: function (ele) {
		
			var _self = ele,
				
				level = 0,
				uls = _self.parents('.cateUl'),
				level = _self.parents('.cateUl').length;	//	上级层数
				
			if (_self.prop('checked')) {	//	未选中至选中
				
				if(_self.hasClass('J_hasSub')) {	//有子级分类的复选框

					if (_self.prop('checked')) {
						_self.closest('li').find('input[type="checkbox"]').prop('checked', true);	//	子级元素选中

						//	判断父级元素是否要被选中
						for (var i = 0; i < level; i++) {							
							var _li_count = uls.eq(i).find('li').length;	// 当前层下的所有li个数
							var _checked = uls.eq(i).children('li').find('input[type="checkbox"]:checked').length;	// 当前层下的选中的checkbox个数

							if (_li_count === _checked) {
								uls.eq(i).prev().prev().find('input[type="checkbox"].J_hasSub').prop('checked', true);
								
								
								
							} else {
								break;
							};
						};

					} 



				} else {	//	无子级分类的复选框
					if (_self.prop('checked')) {

						//	判断父级元素是否要被选中
						for (var i = 0; i < level; i++) {							
							var _li_count = uls.eq(i).find('li').length;	// 当前层下的所有li个数
							var _checked = uls.eq(i).children('li').find('input[type="checkbox"]:checked').length;	// 当前层下的选中的checkbox个数

							if (_li_count === _checked) {
								uls.eq(i).prev().prev().find('input[type="checkbox"].J_hasSub').prop('checked', true);		//	父级元素选中
							
							} else {								
								
								break;
							};
						};

					};
				}

				_self.addClass('check_checked');

			} else {			//选中到未选中
				if(_self.hasClass('J_hasSub')) {
					
					

					_self.parents('li').children('label').find('input[type="checkbox"].J_hasSub').prop('checked', false);
					_self.closest('li').find('input[type="checkbox"]').prop('checked', false);

				} else {	//	子级元素取消
	
					_self.parents('li').children('label').find('input[type="checkbox"].J_hasSub').prop('checked', false);
					
				}
				_self.removeClass('check_checked');
		

			}

			createAds._finalInsData = [],
			createAds._finalInsName = [],
			createAds.getRealCateId();

	},

	// ajax获取兴趣人数
	ajaxGetInsNum: function (_nsCates) {
		$.ajax({
			type: 'post',
			url: mk_url('ads/ad/getFansNumByInterestId'),
			cache: false,
			dataType: 'json',
			data: {nscate: _nsCates},
			success: function (res) {
				if(res === null){
					$.alert('发生错误了！');
				} else{
					if (res.status == 1) {
						$('#insNum').removeClass('ads-loading');
						var _cateData = res.data;
						$('#insNum').text(_cateData);				
					} else if (res.status == 0) {
						$('#insNum').removeClass('ads-loading');
						$('#insNum').text('0');
					};
				}
			},
			error: function () {
				$.alert('发生错误了！');
			}
		});
	},

	// CPC,CPM随机
	randomPrice: function () {
		var RdMinCPC = (Math.random()*0.5 + 0.5).toFixed(2),
			RdMaxCPC = (parseFloat(RdMinCPC) + parseFloat((Math.random()))*0.75).toFixed(2),
			RdMinCPM = (Math.random()*2.5 + 5).toFixed(2),
			RdMaxCPM = (parseFloat(RdMinCPM) + parseFloat((Math.random()*2.5))).toFixed(2);
		$('#cpc-recommend-price').html('建议单次点击为 ￥'+RdMinCPC+' - ￥'+RdMaxCPC+' 之间');
		$('#p_cpc-recommend-price').html('建议单次点击为 ￥'+RdMinCPC+' - ￥'+RdMaxCPC+' 之间');
		$('#cpm-recommend-price').html('建议千次出价为 ￥'+RdMinCPM+' - ￥'+RdMaxCPM+' 之间');
	}




}

var manageAds = {
	// checkbox 全选
	checkall: function (ele, target) {
		if (ele.prop('checked')) {
			$(target).prop('checked', true);
			$(target).click(function () {
				var _self = $(this);
				if (!_self.prop('checked')) {
					ele.prop('checked', false);
				};
			})
		} else {
			$(target).prop('checked', false);
		}
	},

	// 暂停广告
	stopAd: function (url, data, status) {
		var html = '';
		if (status) {
			html += '<div class="winbox">你确定要暂停广告吗？</div>';
		} else {
			html += '<div class="winbox">你确定要开启广告吗？</div>';
		};
		$(this).popUp({
			width:300,
			title:'提示',
			content:html,
			buttons:'<span class="popBtns blueBtn callbackBtn" id="stopAdSure">确认</span><span class="popBtns closeBtn">取消</span>',
			mask:true,
			maskMode:false,
			callback:function(){
				manageAds.ajaxFinalStatus(url, data);
				$.closePopUp();
				// window.location.reload();
			}
		});
	},

	// 删除广告
	delAd: function (url, data) {
		var html = '';
			html += '<div class="winbox">你确定要删除这条广告吗？</div>';
		$(this).popUp({
			width:300,
				title:'提示',
				content:html,
				buttons:'<span class="popBtns blueBtn callbackBtn" id="delAdSure">确认</span><span class="popBtns closeBtn">取消</span>',
				mask:true,
				maskMode:false,
				callback:function(){
					manageAds.ajaxFinalStatus(url, data);
					$.closePopUp();
				}
		});
	},

	// ajax请求,返回最终状态
	ajaxFinalStatus: function (url, data) {
		$.ajax({
			type: 'POST',
			url: url,
			dataType: 'json',
			data: data,
			success: function (rs) {
				var rsData = rs;
				if (rsData.status === 1) {
					$.confirm('提示', rsData.info, function () {
						window.location.reload();
						$('input[name="adList[]"]').prop('checked',false);
					});
					
				} else {
					$.confirm('提示', rsData.info, function () {
						window.location.reload();
						$('input[name="adList[]"]').prop('checked',false);
					});					
				};
			}
		})
	},

	// 申请发票
	getInvoice: function (costAll) {
		var cont = '';
			cont += '<div class="winbox"><ul class="get-invoice-list">';
			cont += '<li class="clearfix"><label>广告费用总额</label>￥'+ costAll +'</li>';
			cont += '<li class="clearfix"><label>本次开发票金额</label>￥&nbsp;<input type="text" name="costThisTime" id="costThisTime" class="txt w100" ></li>';
			cont += '<li class="clearfix"><label>发票抬头</label><input type="text" name="inv_title" id="inv_title" class="txt"></li>';
			cont += '<li class="clearfix"><label>收件人</label><input type="text" name="inv_person" id="inv_person" class="txt"></li>';
			cont += '<li class="clearfix"><label>联系电话</label><input type="text" name="inv_tel" id="inv_tel" class="txt"></li>';
			cont += '<li class="clearfix"><label>电子邮件</label><input type="text" name="inv_mail" id="inv_mail" class="txt"></li>';
			cont += '<li class="clearfix"><label>邮寄地址</label><input type="text" name="inv_add" id="inv_add" class="txt"></li>';
			cont += '<li class="clearfix"><label>邮政编码</label><input type="text" name="inv_zip" id="inv_zip" class="txt"></li>';
			cont += '</ul></div>';
		var _url = mk_url('ads/adadmin/invoicePost');
		$(this).popUp({
			width: 350,
			title: '申请发票',
			content: cont,
			buttons:'<span class="popBtns blueBtn callbackBtn" id="getInvoiceSure">确认</span><span class="popBtns closeBtn">取消</span>',
			mask:true,
			maskMode:false,
			callback:function(){
				var _reg_num = /^[0-9]*[1-9][0-9]*$/,
					_reg_lang = /^[a-zA-Z\u4e00-\u9fa5]+$/;


				var _data = {
					money: $('#costThisTime').val(),
					titles: $('#inv_title').val(),
					addressee: $('#inv_person').val(),
					tel: $('#inv_tel').val(),
					email: $('#inv_mail').val(),
					addr: $('#inv_add').val(),
					zipcode: $('#inv_zip').val()
				};

				if (_data.money.length === 0) {
					$.alert('本次开发票金额为必填项');
					return;
				};
				if (_data.money < 100) {
					$.alert('发票金额必须大于100元');
					return;
				};
				if(_data.money > costAll){
					$.alert('本次开发票金额不能高于广告费用总额');
					$('#costThisTime').val('');
					return;
				}
				if (!_reg_num.test(_data.money)) {
					$.alert('本次开发票金额格式不对');
					$('#costThisTime').val('');
					return;
				};

				if (!_reg_lang.test(_data.titles) || _data.titles.length === 0 ) {
					$.alert('发票抬头为必填项，且只能是汉字或英文!');
					return;
				};
				if (!_reg_lang.test(_data.addressee) || _data.addressee.length === 0 ) {
					$.alert('收件人为必填项，且只能是汉字或英文!');
					return;
				};
				if (!_reg_lang.test(_data.addressee) || _data.addressee.length === 0 ) {
					$.alert('收件人为必填项，且只能是汉字或英文!');
					return;
				};

				$.ajax({
					type: 'POST',
					url: _url,
					dataType: 'json',
					data: _data,
					success: function (rs) {
						var _status = rs.status;
						if (_status == 1) {
							$.alert(rs.info);
							window.location.reload();
						} else {
							$.alert(rs.info);
							// $.closePopUp();
							// window.location.reload();
						};
					}
				})
				
				
			}
		});
	},

	// 设置 - 保存基本信息
	saveContactInfo: function (_url) {
		var _data = {
			name: $('#name').val(),
			industry: $('#industry').val(),
			contact: $('#contact').val(),
			mobile: $('#mobile').val(),
			email: $('#email').val(),
			remarks: createAds.changeLabel($('#remarks').val())
		}
		$.ajax({
			type: 'POST',
			url: _url,
			dataType: 'json',
			data: _data,
			success: function (rs) {
				if (rs.status == '1') {
					$.alert(rs.info);
				} else {
					$.alert(rs.info);
				};
			}
		})
	},

	// 下拉跳转
	jumpURL: function () {
		$('.jumpUrl').bind('change', function () {
			var _self = $(this).val();
			window.location.href = _self;
		});
	},

	// 邮件通知	
	mailNotice: function () {
		var _data = [];
		
		for (var i = 0; i < $('.mnc').length-1; i++) {
			if($('.mnc').eq(i).prop('checked')){
				_data.push(1);
			} else {
				_data.push(0);
			}
	
		}

		if($('.mnc').eq($('.mnc').length-1).prop('checked')){
			_data.push('1'+$('#interval_notice option:selected').val());
		} else {
			_data.push('00');
		}

		$.ajax({
			url: mk_url('ads/adadmin/setNoticePost'),
			type: 'post',
			dataType: 'json',
			data: {notice:_data},
			success: function (rs) {
				var data = rs;
				if(data.status == 1){
					$.alert(data.info);
					$.closePopUp();
					window.location.reload();
				} else {
					$.alert(data.info);
					$.closePopUp();
					window.location.reload();
				}

			}

		});
	},

	// 生成行业
	initTrade: function (target, trade, val) {
		var opt = '',
			_ckd = '';
		for(var i = 0, l = trade.length; i < l; i++) {
			if (trade[i].id == val) {
				_ckd = 'selected="true"';
			} else {
				_ckd = '';
			};
			opt += '<option value="'+trade[i].id+'" '+_ckd+'>'+trade[i].trade_name+'</option>';
		}
		target.append(opt);
	}
}





$(function () {
	//选择分类，显示隐藏兴趣区块
	$('#classify').change(function () {
		createAds.switchIns();
	});

	//	上传图片并且预览
	$('#attachFileButton').uploader({
		formId:'jsUploaderAdsImg',
		url:mk_url('ads/adimg/adimg_upload'),
		callback:'retfunc',
		loading: false,
		inputFileName:'Filedata'
	});
	//验证
	$('#save_ads').bind('click', function () {
		createAds.postCreateAds(mk_url('ads/ad/addad'), mk_url('ads/ad/confirmAd'));
	});
	//列表查看后修改保存验证
	$('#save_edit_ads').bind('click', function () {		
		createAds.postCreateAds(mk_url('ads/adadmin/editAd'), mk_url('ads/adadmin/confirmAd'));
	});
	//	提交第一次申请广告
	$('#final_save_ads').bind('click', function () {
		// createAds.checkBalance();
		createAds.saveToDataBase(mk_url('ads/ad/confirmAdPost'));
		// $.cookie('cipher', '',{expires: -1,path: '/'});

	});
	//提交修改
	$('#final_edit_ads').bind('click', function () {
		createAds.saveToDataBase(mk_url('ads/adadmin/confirmAdPost'));
		// $.cookie('cipher', '',{expires: -1,path: '/'});
	});	


	//预览广告
	createAds.adsPreview('title', 'dt a', '这里是广告标题', 13);
	createAds.adsPreview('url', '.extAdsUrl', '这里是URL地址', 150);
	createAds.adsPreview('introduce', '.extAdsIntro span', '这里是广告描述', 36);	

	//	随机竞价
	createAds.randomPrice();

	// 同意广告协议
	$('#agree_contract').click(function () {
		createAds.agreeContract();
	});

	// 未同意广告协议
	$('#disagree_contract').click(function () {
		createAds.disagreeContract();
	});

	//上传图片
	
/*	var _adsImgPostUrl = mk_url('ads/adimg/adimg_upload');
	$('#attachFileButton').uploader({
		formId:'jsUploaderAdsImg',
		url:_adsImgPostUrl,
		callback:'retfunc',
		loading: false,
		inputFileName:'Filedata'
	}); */
	$('#FileDataInput').css({'top':0, 'width':'60px', 'height':'30px', 'font-size': '30px'});

	//日历
	if($("#date_a_start").length > 0){
		$("#date_a_start").calendar({button:false,time:false});
	}

	if($("#date_end").length > 0){
		$("#date_end").calendar({button:false,time:false});
	}

	//计费方式
	$('input[name="pay_type"]').click(function () {
		var _self = $(this);
		_self.closest('dl').find('dd').hide();
		_self.closest('dt').next().show();
	});

	//只能输入数字
	$('.J-num-only').keypress(function(event) {
		if (!$.browser.mozilla) {
			if (event.keyCode && ((event.keyCode < 48 && event.keyCode != 46) || event.keyCode > 57 )) {
				event.preventDefault();
			};
		} else {
			if (event.charCode && ((event.charCode < 48 && event.charCode != 46 ) || event.charCode > 57 )) {
				event.preventDefault();
			};
		};
	});

	$('.price').bind('keyup', function () {
		createAds.priceType($(this)[0])
	})

	//日历触发选中
	$('#date_a_start').bind('click', function () {
		$(this).closest('dd').find('input[name="put_in_time"]').prop('checked','checked');
	});


	//显示建议出价
	$('.recommend-price-input').bind('focus', function () {
		$(this).closest('dd').find('.recommend-price').show();
	});
	$('.recommend-price-input').bind('blur', function () {
		$(this).closest('dd').find('.recommend-price').hide();
	});	

	// 触发显示获取二级分类
	$('#getCate').delegate('.J_subbtn', 'click', function () {
		var ele = $(this);		
		createAds.getSubCate(ele);
	});


	// 初始化兴趣未选
	$('#getCate .subcate').prop('checked',false);
	// 显示按兴趣获取的数量



	// 列表全选
	$('.adlist-mod').delegate('#checkall', 'click', function () {
		var _self = $(this);
		manageAds.checkall(_self, 'input[name="adList[]"]');
	})

	// 选择地区
	// 国家级
	var region_arr = [];
	var region_count = 0;
	var htmlStrogeRegin = $('#areaRs');
	$('.areatype').delegate('#areaCountry.on', 'click', function () {
		var _self = $(this);
		if ( $('#areaRs span').length < 6) {
			$(this).popUp({
				width:300,
				title:'投放到国家',
				content:"<div class='winbox'>请选择将要投放到的国家：<span id='areaPostCo' class='withSelect'></span></div><script>var areaPost = new initAreaComponent('areaPostCo','1-ad_country,0-ad_province,0-ad_city,0-s4','');areaPost.initalize(); </script>",
				buttons:'<span class="popBtns blueBtn callbackBtn" id="markCo">确认</span><span class="popBtns closeBtn">取消</span>',
				mask:true,
				maskMode:false,
				callback:function(){
					var cid = $('#areaPostCo select[name="ad_country"]').val(),
						cname = $('#areaPostCo option:selected').text();
						if (cid === '-1') {			// 如果未选择，直接关闭
							$.closePopUp();
						} else {
							var tempspan = '<span class="areaSpan" data-country="'+ cid +'" data-province="0" data-city="0" data-span-id="'+ region_count +'" data-final="'+cid+',0,0" href="###" title="点击删除所选地区" data-area-name="'+ cname +'" data-region-rank="1" data-last="'+ cid +'">' + cname + '<a href="###" class="close">X</a></span>';
							if ($('#areaRs span').length == 0) {
								$('#areaRs').html(tempspan);
								// htmlStrogeRegin.attr('region_arr'+region_count, cid+',0,0');
								region_count++;
								_self.closest('li').siblings().find('a').attr('class','off');
							} 
							$.closePopUp();
							createAds.peopleCount();
							if($('#areaRs span').length === 5){_self.attr('class','off')}
						};				
				}
			});
		}
	});

	// 省级
	$('.areatype').delegate('#areaProvince.on', 'click', function () {
		var _self = $(this),
			hasCid = $('#areaRs span').attr('data-country'),
			hasCidName = $('#areaRs span:last').attr('data-country-name'),
			hasPid = $('#areaRs span:last').attr('data-province'),
			_content = '';

		if ( $('#areaRs span').length < 6) {

			if (hasCid === undefined) {
				_content = "<div class='winbox'>请选择将要投放到的省：<span id='areaPostPr' class='withSelect'></span></div><script>var areaPostPr = new initAreaComponent('areaPostPr','1-ad_country,1-ad_province,0-ad_city,0-s4','');areaPostPr.initalize(); </script>";
			} else {
				_content = "<div class='winbox'><p class='mb10'>本次仅限选择 <strong>"+ hasCidName + " </strong>下的省，其余的都将无效</p>请选择将要投放到的省：<span id='areaPostPr' class='withSelect'></span></div><script>var areaPostPr = new initAreaComponent('areaPostPr','0-ad_country,1-ad_province,0-ad_city,0-s4','"+hasCid+"-000000');areaPostPr.initalize(); </script>";
			};
			$(this).popUp({
				width:360,
				title:'投放到省',
				content:_content,
				mask:true,
				maskMode:false,
				callback:function(){
					var cid = $('#areaPostPr select[name="ad_country"]').val(),
						cname = $('#areaPostPr select[name="ad_country"] option:selected').text(),
						pid = $('#areaPostPr select[name="ad_province"]').val(),
						pname = $('#areaPostPr select[name="ad_province"] option:selected').text(),
						sp_co = $("#areaRs").find('span[data-country="' + cid + '"][data-province="0"]'),
						sp_pr = $("#areaRs").find('span[data-country="' + cid + '"][data-province="'+ pid +'"]'),
						ra_num = sp_co.attr('data-span-id'),
						pIndex = sp_pr.index(),
						_tempspan = '',
						hasSome = false;
						var spanLength = $('#areaRs span').length;
						for(var i= 0; i < spanLength; i++) {
						    if($('#areaRs span').eq(i).attr('data-province') ==  pid){
						    	hasSome = true;
						    }
						}




					if (pid === '-1' || pid === '0' || pid === hasPid || hasSome) {			// 如果未选择，直接关闭;已包含各项，直接关闭
						$.closePopUp();
					} else {
						$('#areaRs').addClass('pr_status');
						if (hasCid === undefined) {
							_tempspan = '<span class="areaSpan" data-country="'+ cid +'" data-province="'+ pid +'" data-city="0" data-span-id="'+ region_count +'" data-final="'+ cid +','+ pid +',0" href="###" title="点击删除所选地区" data-area-name="'+ cname + pname +'" data-country-name="'+cname+'" data-region-rank="2" data-last="'+ pid +'">' + cname + pname + '<a href="###" class="close">X</a></span>';
						} else {
							_tempspan = '<span class="areaSpan" data-country="'+ hasCid +'" data-province="'+ pid +'" data-city="0" data-span-id="'+ region_count +'" data-final="'+ hasCid +','+ pid +',0" href="###" title="点击删除所选地区" data-area-name="'+ hasCidName + pname +'" data-region-rank="2" data-country-name="'+hasCidName+'" data-last="'+ pid +'">' + hasCidName + pname + '<a href="###" class="close">X</a></span>';
						};
						var tempspan = _tempspan;
						if ($('#areaRs span').length === 0) {						
							$('#areaRs').html(tempspan);
							htmlStrogeRegin.attr('region_arr'+region_count, cid+','+pid+',0');
							region_count++;

							var cityKey,
								cityobj = areaJson["1"]["list"][+pid]["list"];
							for(cityKey in cityobj) {
								var tmpCityId = '';
									tmpCityId += '<input type="hidden" value="'+cityKey+'" cpid="cpid'+pid+'" class="prCities">';
									$('#areaRs').append(tmpCityId);
							};

							_self.closest('li').siblings().find('a').attr('class','off');
						} else {
							sp_co.remove();
							// htmlStrogeRegin.removeAttr('region_arr'+ra_num);
							$('#areaRs').append(tempspan);
							// htmlStrogeRegin.attr('region_arr'+region_count, cid+','+pid+',0');
							region_count++;		

							var cityKey,
								cityobj = areaJson["1"]["list"][+pid]["list"];
							for(cityKey in cityobj) {
								var tmpCityId = '';
									tmpCityId += '<input type="hidden" value="'+cityKey+'" cpid="cpid'+pid+'" class="prCities cpid'+pid+'">';
									$('#areaRs').append(tmpCityId);
							};

							_self.closest('li').siblings().find('a').attr('class','off');				
						};
						$.closePopUp();
						createAds.peopleCount();
						if($('#areaRs span').length === 5){_self.attr('class','off')}
					};				
				}
			});
		}
	});

	// 市级
	$('.areatype').delegate('#areaCity.on', 'click', function () {

		var _self = $(this),
			hasCid = $('#areaRs span').attr('data-country'),
			hasPid = $('#areaRs span').attr('data-province'),
			hasCiid = $('#areaRs span').attr('data-city'),
			hasCidName = $('#areaRs span:last').attr('data-country-name'),
			hasPidName = $('#areaRs span:last').attr('data-province-name'),
			_content = '';

		if ( $('#areaRs span').length < 6) {
			if (hasCid === undefined) {
				_content = "<div class='winbox'>请选择将要投放到的城市：<span id='areaPostCi' class='withSelect'></span></div><script>var areaPostCi = new initAreaComponent('areaPostCi','1-ad_country,1-ad_province,1-ad_city,0-s4','','adarea',false);areaPostCi.initalize(); </script>";
			} else {
				_content = "<div class='winbox'><p class='mb10'>本次仅限选择 <strong>"+ hasCidName + hasPidName + " </strong>下的城市，其余的都将无效</p>请选择将要投放到的城市：<span id='areaPostCi' class='withSelect'></span></div><script>var areaPostCi = new initAreaComponent('areaPostCi','0-ad_country,0-ad_province,1-ad_city,0-s4','"+hasCid+"-"+hasPid+"0000','adarea',false);areaPostCi.initalize(); </script>";
			};
			$(this).popUp({
				width:450,
				title:'投放到城市',
				content:_content,
				buttons:'<span class="popBtns blueBtn callbackBtn">确认</span><span class="popBtns closeBtn">取消</span>',
				mask:true,
				maskMode:false,
				callback:function(){
					var cid = $('#areaPostCi select[name="ad_country"]').val(),
						cname = $('#areaPostCi select[name="ad_country"] option:selected').text(),
						pid = $('#areaPostCi select[name="ad_province"]').val(),
						pname = $('#areaPostCi select[name="ad_province"] option:selected').text(),
						ciid = $('#areaPostCi select[name="ad_city"]').val(),
						ciname = $('#areaPostCi select[name="ad_city"] option:selected').text(),
						sp_co = $("#areaRs").find('span[data-country="' + cid + '"][data-province="0"][data-city="0"]'),
						sp_pr = $("#areaRs").find('span[data-country="' + cid + '"][data-province="'+ pid +'"][data-city="0"]'),
						sp_ci = $("#areaRs").find('span[data-country="' + cid + '"][data-province="'+ pid +'"][data-city="'+ ciid +'"]'),
						ra_num = sp_co.attr('data-span-id'),
						rp_num = sp_pr.attr('data-span-id'),
						ciIndex = sp_ci.index(),
						_tempspan = '',
						hasSome = false;
						var spanLength = $('#areaRs span').length;
						for(var i= 0; i < spanLength; i++) {
						    if($('#areaRs span').eq(i).attr('data-city') ==  ciid){
						    	hasSome = true;
						    }
						}
					if (ciid === '0' || ciid === hasCiid || ciid=== '-1' || hasSome) {			// 如果未选择，直接关闭
						$.closePopUp();
					} else {
						if (hasCid === undefined) {
							_tempspan = '<span class="areaSpan" data-country="'+ cid +'" data-province="'+ pid +'" data-city="'+ ciid +'" data-span-id="'+ region_count +'" data-final="'+cid+','+pid+','+ciid+'" href="###" title="点击删除所选地区" data-area-name="'+ cname + pname + ciname +'" data-country-name="'+cname+'" data-province-name="'+pname+'" data-region-rank="3" data-last="'+ ciid +'">' + cname + pname + ciname + '<a href="###" class="close">X</a></span>';
						} else {
							_tempspan = '<span class="areaSpan" data-country="'+ hasCid +'" data-province="'+ hasPid +'" data-city="'+ ciid +'" data-span-id="'+ region_count +'" data-final="'+hasCid+','+hasPid+','+ciid+'" href="###" title="点击删除所选地区" data-area-name="'+ hasCidName + hasPidName + ciname +'" data-country-name="'+hasCidName+'" data-province-name="'+hasPidName+'" data-region-rank="3" data-last="'+ ciid +'">' + hasCidName + hasPidName + ciname + '<a href="###" class="close">X</a></span>';
						};
						var tempspan = _tempspan;
						if ($('#areaRs span').length == 0) {
							$('#areaRs').html(tempspan);
							htmlStrogeRegin.attr('region_arr'+region_count, cid+','+pid+','+ ciid);
							region_count++;
							_self.closest('li').siblings().find('a').attr('class','off');
						} else {
	/*						sp_co.remove();
							sp_pr.remove();
							htmlStrogeRegin.removeAttr('region_arr'+ra_num);
							htmlStrogeRegin.removeAttr('region_arr'+rp_num);*/
							$('#areaRs').append(tempspan);
							// htmlStrogeRegin.attr('region_arr'+region_count, cid+','+pid+','+ ciid);
							region_count++;
							_self.closest('li').siblings().find('a').attr('class','off');
						};
						$.closePopUp();
						if($('#areaRs span').length === 5){_self.attr('class','off')}
						createAds.peopleCount();
					};				
				}
			});
		} 
	});	

	// 删除城市
	$('#areaRs').delegate('a', 'click', function () {
		var _self = $(this),
			_text = _self.closest('span').attr('data-area-name');
			_thisPid = _self.closest('span').attr('data-last');
		$(this).popUp({
			width:450,
			title:'删除确认',
			content:'<div class="winbox f14">你确定要删除&nbsp;<strong>'+ _text +'</strong>&nbsp;吗?</div>',
			buttons:'<span class="popBtns blueBtn callbackBtn">确认</span><span class="popBtns closeBtn">取消</span>',
			mask:true,
			maskMode:false,
			callback:function(){
				if ($('#areaRs span').length === 1) {
					_self.closest('div').html('暂未选择广告投放地区');
					$('#areaRs').removeClass('pr_status');
					_self.closest('div').find('.prCities').remove();
					$('.areatype a').attr('class','on');
					$('#areaNum').text('0');
				} else if ($('#areaRs span').length > 1 && $('#areaRs span').length < 6){
					var reg_rank = $('#areaRs span').attr('data-region-rank');
					$('.areatype li').eq(reg_rank - 1).find('a').attr('class','on');
					if ($('#areaRs').hasClass('pr_status')) {
						$('cpid'+_thisPid).remove();
					};
					createAds.peopleCount();		
				};
				_self.closest('.areaSpan').remove();
				$.closePopUp();
			}
		});

	});	

	// 广告名称长度限制
	$('#name').bind('keyup', function () {
		var original = $(this).val();
		$(this).val(original.substring(0,13));
	});

	// 复杂广告标题到广告名称
	$('#title').bind('keyup', function () {
		var original = $(this).val();
		if(original.length > 0 ){
			$('#name').closest('div').find('.pdrs').hide();
		} else {
			$('#name').closest('div').find('.pdrs').show();
		}
		
		$('#name').val(original.substring(0,13));
	});

	// 改变年龄人数统计
	$('#age_range').bind('change', function () {
		if ($('#areaRs span').length > 0) {
			createAds.peopleCount();
		};
	});

	// 改变性别人数统计
	$('input[name="gender"]').bind('change', function () {
		if ($('#areaRs span').length > 0) {
			createAds.peopleCount();
		}
	});	

	// 暂停广告
	$('.stop-ad').bind('click', function () {
		var _self = $(this),
			_ad_id_arr = [],
			_ad_id = _ad_id_arr.push($(this).attr('data-ad-id')),
			_url = mk_url('ads/adadmin/statusAd'),
			_status = _self.attr('data-status') === 'runing';
			if (_status) {
				_data = {ad_status:1,ad_id: _ad_id_arr};
			} else {
				_data = {ad_status:3,ad_id: _ad_id_arr};
			};
			
			manageAds.stopAd(_url, _data, _status);
	});

	// 暂停多选广告
	$('#stop-checked').bind('click', function () {
		if ($('input[name="adList[]"]:checked').length) {
		var _ad_id_arr = [],			
			_url = mk_url('ads/adadmin/statusAd');
			$('input[name="adList[]"]:checked').each(function (e) {
				_ad_id_arr.push($(this).attr('data-ad-id'));
			})
			_data = {ad_status:1,ad_id: _ad_id_arr};
			manageAds.stopAd(_url, _data, true);
		} else {
			$.alert('请选择要暂停的广告！');
			return false;
		}
	});

	// 删除广告
	$('.del-ad').bind('click', function () {
		var _ad_id_arr = [],
			_url = mk_url('ads/adadmin/statusAd');
			_ad_id_arr.push($(this).attr('data-ad-id'));
			_data = {ad_status:-1,ad_id: _ad_id_arr};
			manageAds.delAd(_url, _data);
	});	

	// 删除多选广告
	$('#del-checked').bind('click', function () {
		if ($('input[name="adList[]"]:checked').length) {
			var _ad_id_arr = [],
				_url = mk_url('ads/adadmin/statusAd');
				
				$('input[name="adList[]"]:checked').each(function (e) {
					_ad_id_arr.push($(this).attr('data-ad-id'));
				})
				_data = {ad_status:-1,ad_id: _ad_id_arr};
			manageAds.delAd(_url, _data);
		} else {
			$.alert('请选择要删除的广告！');
			return false;
		};
	});	

	//	邮件通知
	$('#save_notice').click(function () {
		manageAds.mailNotice();
	});

	// 设置广告 - 保存基本信息
	$('#save_contact').bind('click', function () {
		var _str = $('.J_acount').val(),
			_str_acount = $('.J_contact').val(),
			_mail = $('.J_mail').val(),
			reg = /^[a-zA-Z\u4e00-\u9fa5]+$/,
			reg_mail = /^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/;
		if (!reg.test(_str) || _str.length <2 || _str.length >10 ) {
			$.alert('帐号名称字符为2-10个，且为中文和英文');
			return;
		};
		if (!reg.test(_str_acount) || _str_acount.length <2 || _str_acount.length >10 ) {
			$.alert('联系人名称字符为2-10个，且为中文和英文');
			return;
		};		
		if(!reg_mail.test(_mail)){
			$.alert('邮箱格式不不正确！');
			return;
		}
		manageAds.saveContactInfo(mk_url('ads/adadmin/setAdPost'));
	});

	// 兴趣分类
	$('#getCate').delegate('.insCheck', 'click', function () {
		$('#insNum').addClass('ads-loading').text('');
		if ($(this).prop('checked') && $(this).attr('id') === 'interest-type') {			
			createAds.selectIns($(this));
			createAds._finalInsData = $(this).attr('data-id').split(',');
			createAds._finalInsName = $(this).attr('data-name').split(',');
		} else if (!$(this).prop('checked') && $(this).attr('id') === 'interest-type') {
			createAds.selectIns($(this));
			createAds._finalInsData = [];
		} else {		

			createAds.selectIns($(this));
			if($('#finalIns .insCheck').length === $('#finalIns .insCheck:checked').length){createAds._finalInsName.push($('#interest-type').attr('data-name'));}

		};
		createAds.ajaxGetInsNum(createAds._finalInsData);
		

	});
	

	// 下拉跳转
	manageAds.jumpURL();

	//	查询报告日历
	if($("#date_rep_single").length > 0){
		$("#date_rep_single").calendar({button:false,time:false});
	}
	
	if($("#date_rp_start").length > 0){
		$("#date_rp_start").calendar({button:false,time:false});
	}
	if($("#date_rp_end").length > 0){
		$("#date_rp_end").calendar({button:false,time:false});
	}

	//	广告报表
	$('#exptype').bind('change', function () {
		var val = $(this).find('option:selected').val();
		if (val == 1) {
			$('.adlist-mod input[type=checkbox]').prop('checked', true);
		};

	});

	$('#get_report').click(function () {
		var _date = $('#date_rep_single').val(),
			_sort = $('#adStatus option:selected').val();
			window.location.href = mk_url('ads/adadmin/reportad',{date: _date, sort: _sort});

	});


	$('#adStatus').bind('change', function () {
		var _date = $('#date_rep_single').val(),
			_sort = $('#adStatus option:selected').val();
			window.location.href = mk_url('ads/adadmin/reportad',{date: _date, sort: _sort});

	});

	createAds.supportPlaceholder();
	

});



function retfunc (data) {
	if(data.status == 0){
		$.alert(data.message);
	} else if (data.status == 1) {
		$('#prevAdsImg').attr('src',data.data.Mf_img);
		$('#Mf_img').val(data.data.Mf_img);
		$('#img_url').val(data.data.Mf_img);
		$('#img_url').closest('.tempplaceholder').find('.pdrs').hide();
		_adsImgPostUrl = mk_url('ads/adimg/adimg_upload', {fdfs: $('#img_url').val()});
		$('#jsUploaderAdsImg').attr('action', _adsImgPostUrl);
	}
}


