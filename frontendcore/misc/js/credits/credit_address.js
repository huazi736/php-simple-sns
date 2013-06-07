/*
 * Created on 2012-07-18
 * @author: 罗豪鑫
 * @desc: 积分系统-兑换记录
 * @depends: jquery,popUp,area_utils
 */

(function(){
	var addressForm = '<table id="newAddress">' +
		'<tbody><tr>' +
		'<td class="label"><label for="newName">姓名：</label></td>' +
		'<td><input type="text" id="newName" class="verify" /><span class="asterisk">*</span></td></tr><tr>' +
		'<td class="label"><label for="now_province">所在地区：</label></td>' +
		'<td><span id="selectAddress"></span><span class="asterisk">*</span></td></tr><tr>' +
		'<td valign="top" class="label"><label class="tat" for="newFullAddress">详细地址：</label></td>' +
		'<td valign="top"><textarea id="newFullAddress" class="verify" value=""></textarea><span class="asterisk">*</span><p class="inpTips">请填写您的详细地址，不需要重复填写省、市地区</p></td></tr><tr>' +
		'<td class="label"><label for="newCode">邮编：</label></td>' +
		'<td><input type="text" id="newCode" class="verify" /><span class="asterisk">*</span></td></tr><tr>' +
		'<td class="label"><label for="newPhone_m">手机号码：</label></td>' +
		'<td><input type="text" id="newPhone_m" /><span class="fc9 pl8">手机和电话至少填一个</span></td></tr><tr>' +
		'<td class="label"><label for="newPhone_h_a">电话号码：</label></td>' +
		'<td><input type="text" id="newPhone_h_a" class="tels" /><span class="fc9">-</span><input type="text" id="newPhone_h_p" class="tels" /><span class="fc9">-</span><input type="text" id="newPhone_h_e" class="tels" />'+
		'<span class="fc9 pl8">格式：区号-电话号码-分机号</span></td></tr><tr>' +
		'<td>&nbsp;</td><td><input type="checkbox" name="setDef" id="setDefault" class="radio" value="0" /><label for="setDefault" class="radio-label fcb">设为首选地址</label></td></tr>' +
		'</tbody></table>';	
	var addressItem = $('.addressItem'), setAddress = $('.setAddress'), ediAddress = $('.ediAddress'), delAddress = $('.delAddress'), addNewAddress = $('#addNewAddress'), verifyBtn = $('#verifyBtn'), wrapp = $('#a-table').find('tbody');
	//popUp
	function popCall(title,content,callback){
		$(this).popUp({
			width: 580,
			title: title,
			content: content,
			buttons: '<span class="popBtns blueBtn callbackBtn">保存</span><span class="popBtns closeBtn">取消</span>',
			mask: true,
			maskMode: false,
			callback: function(){
				var uname = $('#newName').val(), 
					street = $('#newFullAddress').val(), 
					post_code = $('#newCode').val(), 
					area_code = $('#newPhone_h_a').val(), 
					telphone = $('#newPhone_h_p').val(), 
					extension = $('#newPhone_h_e').val(), 
					mobile = $('#newPhone_m').val(), 
					province = $('#selectAddress').find('select[name="now_province"]').find('option:selected').text(), 
					city = $('#selectAddress').find('select[name="now_city"]').find('option:selected').text(), 
					area = $('#selectAddress').find('select[name="now_town"]').find('option:selected').text(), 
					priority = $('#setDefault').val();			
				var error = '<span class="errorTip"></span>';
				if(uname == '' || uname.length <2 || uname.length >10){
					if ($('#newName').nextAll('.errorTip').size() <= 0) {
						$('#newName').parent().append(error);
						$('#newName').siblings('.errorTip').html('姓名仅限制中英文(长度2-10)');
					}
					return false;			
				}
				if(province == '请选择' || city == '请选择' || area == '请选择'){
					if($('#selectAddress').nextAll('.errorTip').size() <= 0){
						$('#selectAddress').parent().append(error);
						$('#selectAddress').siblings('.errorTip').html('请选择省、市、区');	
					}else{
						$('#selectAddress').nextAll('.errorTip').remove();
					}
					return false;			
				}			
				if(street == '' && $('#newFullAddress').siblings('.errorTip').size()==0){
					$('#newFullAddress').siblings('.asterisk').after(error);
					$('#newFullAddress').siblings('.errorTip').html('请输入5-50个文字');	
					return false;				
				}
				if(post_code == '' && $('#newCode').siblings('.errorTip').size()==0){
					$('#newCode').parent().append(error);
					$('#newCode').siblings('.errorTip').html('请输入6位的邮政编码');	
					return false;			
				}
				if($.trim(mobile)==''&& $.trim(telphone)==''){
					$('#newPhone_m').parent().find('.fc9').after(error);
					$('#newPhone_m').siblings('.fc9').addClass('errorTip').html('手机和电话必须填一个');	
					return false;			
				}
				
				if($.trim(mobile)!=''){
					var mexp = /^0?(13[0-9]|15[012356789]|18[0236789]|14[57])[0-9]{8}$/;
					if (!mexp.test($.trim(mobile))) {
						$('#newPhone_m').parent().find('.fc9').after(error);
						$('#newPhone_m').siblings('.fc9').addClass('errorTip').html('请输入11位的手机号码');
						return false;
					}		
				}
				if($.trim(telphone)!=''){
					var texp = /^\d{7,8}$/;			
					if(!texp.test($.trim(telphone))){
						$('#newPhone_h_p').siblings('.pl8').addClass('errorTip').html('电话号码格式不正确');	
						return false;					
					}						
				}
				if($.trim(extension)!=''){
					var eexp = /^\d{3}$/;
					if(!eexp.test($.trim(extension))){
						$('#newPhone_h_e').siblings('.pl8').addClass('errorTip').html('电话号码格式不正确');					
						return false;
					}
				}			
				if($.trim(area_code)!=''){
					var aexp = /^0\d{2,3}$/;
					if(!aexp.test($.trim(area_code))){
						$('#newPhone_h_e').siblings('.pl8').addClass('errorTip').html('电话号码格式不正确');					
						return false;
					}
				}							
				return callback && callback();
			}
		});				
	}
	
	//验证格式	
	function check(){
		var uname = $('#newName'),
			street = $('#newFullAddress'),
			mobile = $('#newPhone_m'),
			province = $('#selectAddress').find('select[name="now_province"]'),
			city = $('#selectAddress').find('select[name="now_city"]'),
			area = $('#selectAddress').find('select[name="now_town"]'),
			mobile = $('#newPhone_m'),
			post_code = $('#newCode');
			area_code = $('#newPhone_h_a'), 
			telphone = $('#newPhone_h_p'), 
			extension = $('#newPhone_h_e');			
		var status = false;
		var error = '<span class="errorTip"></span>',verify = $('.verify');			
			provinceText = province.find('option:selected').text(),
			cityText = province.find('option:selected').text(),
			areaText = province.find('option:selected').text();
 		verify.live({
 			focus:function(){
 				var errorTip = $(this).parent().find('.errorTip');
				if(errorTip){
					errorTip.remove();
				}				
			}
		});
		uname.live({	
			blur:function(){
				var val = $.trim($(this).val());
				if(val == '' || val.length < 2 || val.length >10){	
					$(this).parent().append(error);
					$(this).siblings('.errorTip').html('姓名仅限制中英文(长度2-10)');
					return false;
				}	
			}
		});	
		street.live({		
			blur:function(){
				var val = $.trim($(this).val());
				if(val.length == '' || val.length < 5 || val.length > 50){
					$(this).siblings('.asterisk').after(error);
					$(this).siblings('.errorTip').html('请输入5-50个文字');
					return false;					
				}
			}	
		});
		mobile.live({
 			focus:function(){
 				var errorTip = $(this).parent().find('.errorTip');		
				$(this).siblings('.fc9').html('手机和电话至少填一个');		
				$(this).siblings('.fc9').removeClass('errorTip');						
			},
			blur:function(){
				var val = $(this).val();
				if($.trim(val)!=''){
					var mexp = /^0?(13[0-9]|15[012356789]|18[0236789]|14[57])[0-9]{8}$/;
					if (!mexp.test($.trim(val))) {
						$('#newPhone_m').parent().find('.fc9').after(error);
						$('#newPhone_m').siblings('.fc9').addClass('errorTip').html('请输入11位的手机号码');
						return false;
					}		
				}			
			}						
		});
		post_code.live({
			blur:function(){
				var val = $.trim($(this).val());
				var exp = /^[1-9][0-9]{5}$/;
				if(!exp.test(val)){
					$(this).siblings('.asterisk').after(error);
					$(this).siblings('.errorTip').html('请输入6位的邮政编码');
					return false;					
				}
			}
		});
		area_code.live({
			blur:function(){
				var val = $.trim($(this).val());
				if($.trim(val)!=''){
					var aexp = /^0\d{2,3}$/;
					if(!aexp.test($.trim(val))){
						$(this).siblings('.pl8').addClass('errorTip').html('区号格式不正确');					
						return false;
					}else{
						$(this).siblings('.pl8').removeClass('errorTip').html('格式：区号-电话号码-分机号');
					}
				}else{
					$(this).siblings('.pl8').removeClass('errorTip').html('格式：区号-电话号码-分机号');
				}					
			}					
		});
		telphone.live({
			blur:function(){
				var val = $.trim($(this).val());
				if($.trim(val)!=''){
					var texp = /^\d{7,8}$/;			
					if(!texp.test($.trim(val))){
						$(this).siblings('.pl8').addClass('errorTip').html('电话号码格式不正确');	
						return false;					
					}else{
						$(this).siblings('.pl8').removeClass('errorTip').html('格式：区号-电话号码-分机号');
					}							
				}else{
					$(this).siblings('.pl8').removeClass('errorTip').html('格式：区号-电话号码-分机号');
				}				
			}						
		});
		extension.live({
			blur:function(){
				var val = $.trim($(this).val());
				if($.trim(val)!=''){
					var eexp = /^\d{3}$/;
					if(!eexp.test($.trim(val))){
						$(this).siblings('.pl8').addClass('errorTip').html('分机格式不正确');					
						return false;
					}else{
						$(this).siblings('.pl8').removeClass('errorTip').html('格式：区号-电话号码-分机号');
					}
				}else{
					$(this).siblings('.pl8').removeClass('errorTip').html('格式：区号-电话号码-分机号');
				}				
			}						
		});
		$('#5').live({
			change:function(){
				var parent = $(this).parent(),errorTip = parent.siblings('.errorTip'),text = parent.find('option:selected:last').text();
				if(errorTip && text != '请选择'){
					errorTip.remove();
				}
				
			}
		});							
	}
	check();	
	//默认首选地址
	var def = function (){
		var len = ediAddress.length;
		for(var i = 0; i<len; i++){
			var priority = ediAddress.eq(i).attr('priority');
			if(priority == 1){			
				ediAddress.eq(i).siblings('.setAddress').addClass('defAddress').html('首选地址');
			}			
		}
	}();
	//改变背景色
	addressItem.live({
		mouseover:function(){			
			$(this).addClass('item_hover');
		},
		mouseout:function(){
			$(this).removeClass('item_hover');
		}
	});
	//选择首选
	$('#setDefault').live({
		click:function(){
			if($(this).attr('checked')=='checked'){
				$(this).attr('value',1)
			}else{
				$(this).attr('value',0)
			}		
		}
	});
	//新增地址
	addNewAddress.click(function(){
		var item = wrapp.find('.addressItem');
		if(item.size()>=5){
			$(this).siblings('.fc9').css({
				color:'#f00'
			});
			return false;
		}else{
			popCall('请填写新的收货地址',addressForm,function(){		
				var uname = $('#newName').val(), 
					street = $('#newFullAddress').val(), 
					post_code = $('#newCode').val(), 
					area_code = $('#newPhone_h_a').val(), 
					telphone = $('#newPhone_h_p').val(), 
					extension = $('#newPhone_h_e').val(), 
					mobile = $('#newPhone_m').val(), 
					province = $('#selectAddress').find('select[name="now_province"]').find('option:selected').text(), 
					city = $('#selectAddress').find('select[name="now_city"]').find('option:selected').text(), 
					area = $('#selectAddress').find('select[name="now_town"]').find('option:selected').text(), 
					priority = $('#setDefault').val();
				var datas = {
					uname: uname,
					street: street,
					post_code: post_code,
					area_code: area_code,
					telphone: telphone,
					extension: extension,
					mobile: mobile,
					province: province,
					city: city,
					area: area,
					priority: priority
				}
				$.djax({
					url: mk_url('credit/address/addAddress'),
					type: 'post',
					dataType: 'json',
					data: datas,
					success: function(msg){
						//添加记录
						if (msg.status == 1) {	
							var item = '';	
								item += '<tr class="addressItem">'; 
								item += '<td><span class="uname">' + uname + '</span></td>' ;
								item += '<td><span class="province">' + ' '+ province + '</span><span class="city">' + ' '+ city + '</span><span class="area">' + ' ' + area + '</span><span class="street">' + ' '+ street +'</span></td>' ;
								item += '<td><span class="mob">' + ' '+ mobile +'</span></td>' ;
								item += '<td><span class="area_code">' + area_code + ' ' + '</span>-<span class="tel">' + ' ' + telphone + ' ' + '</span>-<span class="extension">'+ ' ' + extension +'</span></td>' ;
								item += '<td><span class="setAddress handBtn set_w" time="'+ msg.data +'">设为首选地址</span>' ;
								item += '<span class="ediAddress handBtn" time="'+ msg.data +'">修改</span>' +'<span time="'+ msg.data +'" class="delAddress handBtn">删除</span></td></tr>';		
								wrapp.append(item);
							var lastItem = wrapp.find('.addressItem').last();
							var ediAddress = lastItem.find('.ediAddress');
								ediAddress.attr('uname',uname);
								ediAddress.attr('province',province);						
								ediAddress.attr('city',city);
								ediAddress.attr('area',area);
								ediAddress.attr('street',street);
								ediAddress.attr('pcode',post_code);
								ediAddress.attr('mob',mobile);
								ediAddress.attr('area_code',area_code);
								ediAddress.attr('tel',telphone);
								ediAddress.attr('extension',extension);
								ediAddress.attr('priority',priority);	
							if(priority == 1){
								lastItem.find('.setAddress').addClass('defAddress').html('首选地址');
								lastItem.siblings().find('.setAddress').removeClass('defAddress').html('设为首选地址');
								lastItem.siblings().find('.ediAddress').attr('priority',0);
							}
							var item = $('#addNewAddress').siblings('.fc9');
								if(item.size()==0){
									$('#addNewAddress').after('<span class="fc9">最多只能保存<strong>5</strong>个有效地址</span>');
									$('.fc9').css({
										'line-height':'28px'
									});
								}																			
						}
						else {
							alert(msg.info);
						}
					}					
				});	
				var nodata = $('#nodata');
				if (nodata) {
					nodata.remove();
				}
				$('#popUp,#popMask').hide();
			});			
		}

		//地区联动
		var myArea = new initAreaComponent('selectAddress', '0-now_nation,1-now_province,1-now_city,1-now_town', '中国 请选择', true);
		myArea.initalize();
	}).mouseout(function(){
		$(this).siblings('.fc9').css({
			color:''
		});
	});
	//修改地址
	ediAddress.live({
		click:function(){
			var $this = $(this), 
				time = $this.attr('time'),
				province = $this.attr('province'),
				city = $this.attr('city'),
				area = $this.attr('area');
			popCall('修改地址',addressForm,function(){
				var uname = $('#newName').val(), 
					street = $('#newFullAddress').val(), 
					post_code = $('#newCode').val(), 
					area_code = $('#newPhone_h_a').val(), 
					telphone = $('#newPhone_h_p').val(), 
					extension = $('#newPhone_h_e').val(), 
					mobile = $('#newPhone_m').val(), 
					province = $('#selectAddress').find('select[name="now_province"]').find('option:selected').text(), 
					city = $('#selectAddress').find('select[name="now_city"]').find('option:selected').text(), 
					area = $('#selectAddress').find('select[name="now_town"]').find('option:selected').text(), 
					priority = $('#setDefault').val();
				var datas = {
						time:time,
						uname: uname,
						street: street,
						post_code: post_code,
						area_code: area_code,
						telphone: telphone,
						extension: extension,
						mobile: mobile,
						province: province,
						city: city,
						area: area,
						priority: priority
				}
				$.djax({
					url:mk_url('credit/address/updateAddress'),
					type:'post',
					dataType:'json',
					data:datas,
					success:function(msg){
						if(msg.status == 1){			
							var items = $this.parents('.addressItem');
								items.find('.uname').html(uname);
								items.find('.province').html(province);
								items.find('.city').html(' '+ city);
								items.find('.area').html(' '+ area);
								items.find('.street').html(' '+ street);
								items.find('.mob').html(mobile);
								items.find('.area_code').html(area_code + ' ');
								items.find('.tel').html(' '+ telphone + ' ');	
								items.find('.extension').html(' '+ extension);						
								$this.attr('uname',uname);
								$this.attr('province',province);						
								$this.attr('city',city);
								$this.attr('area',area);
								$this.attr('street',street);
								$this.attr('pcode',post_code);
								$this.attr('mob',mobile);
								$this.attr('area_code',area_code);
								$this.attr('tel',telphone);
								$this.attr('extension',extension);
								$this.attr('priority',priority);		
							if(priority == 1){
								$this.siblings('.setAddress').addClass('defAddress').html('首选地址');
								$this.parents('.addressItem').siblings().find('.setAddress').removeClass('defAddress').html('设为首选地址');
							}else{
								$this.siblings('.setAddress').removeClass('defAddress').html('设为首选地址');
							}								
						}else{
							alert(msg.info);
						}
					},
					error:function(){
						alert('系统忙，请稍后......');
					}						
				});
				$('#popUp,#popMask').hide();					
			});	
			
			var myArea = new initAreaComponent('selectAddress', '0-now_nation,1-now_province,1-now_city,1-now_town', '中国 '+ province +' '+ city +' '+ area, true);
			myArea.initalize();					
			$('#newName').val($this.attr('uname'));//姓名					
			$('#newFullAddress').val($this.attr('street'));//街道			
			$('#newCode').val($this.attr('pcode'));//邮编
			$('#newPhone_m').val($this.attr('mob'));//手机号码
			$('#newPhone_h_a').val($this.attr('area_code'));//区号
			$('#newPhone_h_p').val($this.attr('tel'));//电话号码
			$('#newPhone_h_e').val($this.attr('extension'));//分机号
			$('#setDefault').val($this.attr('priority'));//默认值	
			if($('#setDefault').val() == '1'){
				$('#setDefault').attr('checked','checked');
			}			
		}
	});
	
	//删除地址
	delAddress.live({
		click:function(){
			var time = $(this).attr('time');
			var $this = $(this);
			$.djax({
				url:mk_url('credit/address/deleteAddress'),
				dataType:'json',
				data:{time:time},
				success:function(msg){
					if(msg.status == 1){
						$this.parents('.addressItem').remove();		
						var nodata = $('#nodata'),addressItem = $('.addressItem');
						if(addressItem.size()==0){
							$('#a-table-tt').after('<tr id="nodata"><td colspan="5">请添加相关的地址信息！</td></tr>');
							$('#addNewAddress').siblings('.fc9').remove();							
						}										
					}else{
						//console.log(msg.info)
					}
				}
			});
		}
	});

	//设置首选地址
	setAddress.live({
		click:function(){
			var $this = $(this), time = $this.attr('time');
			$.djax({
				url:mk_url('credit/address/setDefaultAddress'),
				type:'post',
				dataType:'json',
				data:{time:time},
				success:function(msg){
					if(msg.status == 1){
						$this.addClass('defAddress').html('首选地址');
						$this.parents('.addressItem').siblings().find('.setAddress').removeClass('defAddress').html('设为首选地址');
						$this.siblings('.ediAddress').attr('priority',1);
						$this.parents('.addressItem').siblings().find('.ediAddress').attr('priority',0);
					}else{
						alert(msg.info)
					}
				}
			});
		}
	});
	var item = $('#a-table').find('.addressItem');
	if(item.size()>0){
		$('#addNewAddress').after('<span class="fc9">最多只能保存<strong>5</strong>个有效地址</span>');
		$('.fc9').css({
			'line-height':'28px'
		});
	}		
})()
