
$(function(){
	//所有银行的缩写
	var  a_banks = ["ICBCB2C","CMB","CCB","BOCB2C","ABC","COMM","PSBC-DEBIT","CEBBANK","SPDB","GDB","CITIC","CIB","SDB","CMBC","BJBANK","HZCBB2C","SHBANK","BJRCB","SPABANK","FDB","WZCBB2C-DEBIT","NBBANK","ICBCBTB","CCBBTB","SPDBB2B","ABCBTB"];
	function gen_bank_html(s_bank,b_checked)
	{
		var a_html = [];
		a_html.push('<li class="bank list-item ' + s_bank + ' radio-logo">');
		a_html.push('<span class="radio-wrap">');
		a_html.push('<input class="radio-which-bank" type="radio" value="' + s_bank + '" name="pay_bank"' + (b_checked?'checked="checked"':'') + ' />');
		a_html.push('</span>');
		a_html.push('<span class="logo-bank logo-' + s_bank + (b_checked?' logo-cur ':'') + '"></span>');
		a_html.push('</li>');
		return a_html.join('');
	}
	function gen_html_bank_list(o_del)
	{
		var a_list = [];
		a_list.push('<ol class="left-banks" >');
		//生成剩余可选银行的html代码
		for(var i=0,i_total = a_banks.length;i<i_total;i++)
		{
			if(!o_del[a_banks[i]])
			{
				a_list.push(gen_bank_html(a_banks[i]));
			}
		}
		a_list.push('<span class="clearfix"></span>');
		a_list.push('</ol>');
		return a_list.join("");
	}
	function del_pop_things()
	{
		$('.popUpWindow').remove();
		$('.popUpMask').remove();
	}
	$(".PAY .fee").bind("keyup",function(event){
			//alert('keyup');
			//删除前导非 正数字符, 删除非数字字符; 然后删除 .00
			var s_tmp = this.value.replace(/(?:\..*)|(?:^[^1-9]+)|(?:[^0-9])/g,'');
			this.value = s_tmp.replace(/^(.{8,8}).*/,'$1');
		}
	);
	$(".PAY_ways .way").click(function(event){
		//切换 tab
		$(this).addClass("cur").siblings().removeClass("cur");
		//提取特征 如 “banks”或“alipay”
		var s_mark = $(this).attr("class").replace(/(?:cur|way| )/g,"");
		//切换面板
		//alert(s_mark);
		$(this).parent().next().children(".panel").removeClass("panel-cur")
				.filter(".panel-" + s_mark).addClass("panel-cur");
	});


	$(".link-to-other-banks").click(function(event){
			//var s_curbank = $(".bank-list input:checked").attr("bank");
			var s_curbank = null;
			//获取被选中的银行的缩写
			var $banks = $('.bank-list .radio-which-bank');
			for(var i=0,i_len=$banks.length;i<i_len;i++){
				var $cur = $($banks[i]);
				if($cur.is(':checked')){
					s_curbank = $cur.attr('value');
					break;
				}
			}
			if(! s_curbank){
				return;
			}
			//alert(s_curbank);
			var o_del = {"ALIPAY":true,"TENPAY":true};
			o_del[s_curbank] = true;
			var s_list_html = gen_html_bank_list(o_del);
			var a_html = ['<div class="PAY_left-banks-content">','<div class="pop-item item-cur-bank">','<label class="label label-cur-bank">当前银行：</label><span class="logo-cur logo-bank logo-'+ s_curbank +'"></span>','</div>','<div class="pop-item item-left-banks">','<label class="label label-left-banks">其它银行：</label>'];
			$(this).popUp({
					"width":700,
					"title":"其它支付方式",
					"mask":true,
					"content":a_html.join('') + s_list_html + '</div></div>',
					'maskMode':false,
					"buttons":'<div class="PAY_left-banks-buttons" ><a class="button submit" href="#">确认</a><a class="button cancel" href="#">取消</a></div>'
				}
			);

			$(".PAY_left-banks-buttons .submit").click(function(){
					//alert("hhh");
					//判断用户的选择并提取银行的缩写
					var s_bank = $('.left-banks input:checked').attr('value');
					//使得 pay.html 中只显示被选中的银行 logo
					if(s_bank){
						$('.panel-banks .bank-list').children('.bank').remove()
							.end().prepend(gen_bank_html(s_bank,true));
					}
					//删除 弹出的 dom
					del_pop_things();
				}
			);
			$(".PAY_left-banks-buttons .cancel").click(function(){
					//alert("hhh");
					//删除 dom
					del_pop_things();
				}
			);
		}
	);

	// 提交支付
	$('#dk_pay_sub').click(function() {
		var _self = $(this),
			_s_url = mk_url('ads/adadmin/billad'),
			_f_url = mk_url('pay/pay/index', {type:'ads'});
		_self.attr('disabled', true).css({'background':'#ADBAD4',"border-color":"#94A2BF"});
		_self.popUp({
			noButton: true,
			mask: true,
			title: '支付提示',
			maskMode: false,
			content: '<div style="padding:20px 20px;"><h3 style="margin-bottom:10px">请您在新打开的页面完成支付！</h3><ul style="margin-bottom:10px"><li>支付完成前请不要关闭此窗口。</li><li>支付完成后，请根据结果选择</li></ul><p><a href="'+_s_url+'" class="btnBlue" style="color:#fff">支付成功</a>&nbsp;&nbsp;<a href="'+_f_url+'" class="btnGray">支付失败，重新支付</a></p></div>'
		})
	});
});