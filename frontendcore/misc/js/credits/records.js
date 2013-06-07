/*
 * Created on 2012-07-18
 * @author: 罗豪鑫
 * @desc: 积分系统-兑换记录
 * @depends: jquery,popUp
 */
//popUp
function pop(content,title){
	$(this).popUp({
		width:380,
		title:title,
		content:content,
		buttons:'<span class="popBtns blueBtn closeBtn">关闭</span>',
		mask:true,
		maskMode:true
	});			
}
//查看详情
$('.activity_d').live({
	click:function(){
		var $this = $(this),redeemid = $this.attr('redeemid'), tt = '实物详情';
		$.djax({
			url:mk_url('credit/redeem/getRedeemDetail'),
			type:'get',
			dataType:'json',
			data:{redeemId:redeemid},
			success:function(msg){
				if(msg.status == 1){
						var html = '';
						html += '<div class="activityInfo">';
						html += '<h3>您使用<strong>'+ msg.data.c +'</strong>积分兑换了<strong>'+ msg.data.pname +'</strong></h3>';
						html +='<dl><dt>您选择的地址为：</dt>';
						html += '<dd><span class="act-tt">姓名：</span>'+ msg.data.address.uname +'</dd>';
						html += '<dd id="mob"><span class="act-tt">手机：</span>'+ msg.data.address.mob +'</dd>';
						html += '<dd id="tel"><span class="act-tt">电话：</span>'+ msg.data.address.area_code + '-' + msg.data.address.tel + '-' + msg.data.address.extension + '</dd>';
						html += '<dd class="clearfix"><span class="act-tt">地址：</span><p class="act-add">'+ msg.data.address.province + msg.data.address.city + msg.data.address.street + '</p></dd>';
						html += '<dd><span class="act-tt">邮编：</span>'+msg.data.address.pcode+'</dd>';
						html += '</dl></div>';				
					pop(html,tt);
					if(msg.data.address.tel == ''){
						$('#tel').hide();
					}else if(msg.data.address.mob == ''){
						$('#mob').hide();
					}
				}else{
					pop('<p class="fcr">'+ msg.info +'</p>',tt);				
				}
			},
			error:function(){
				pop('<p class="fcr">系统繁忙，请稍后......</p>',tt);
			}
		});
	}
});
//查看进度
$('.activity_c').live({
	click:function(){
		var $this = $(this),redeemid = $this.attr('redeemid'),tt = '配送详情';
		$.djax({
			url:mk_url('credit/redeem/getRedeemDetail'),
			type:'get',
			dataType:'json',
			data:{redeemId:redeemid},
			success:function(msg){
				if(msg.status == 1){
					if(msg.data.status == 2){
						var info = '';
						info += '<ul class="activityInfo">';
						info += '<li>物品名称：<strong>'+ msg.data.pname +'</strong></li>';
						info += '<li>快递公司：'+ msg.data.ename + '</li>';
						info += '<li>快递单号：[<em>'+ msg.data.enumber +'</em>]</li>';
						info += '<li>发出时间：[<em>'+ msg.data.dtime +'</em>]</li>';
						info += '<li class="fc9">请您登录快递官网查询快递物流明细<!--<a class="postLink" href="">立即查询</a>--></li></ul>';					
						pop(info,tt);					
					}else{
						pop('<p>正在为您配备礼品，请耐心等待......</p>',tt);						
					}		
					
				}else{
					pop('<p class="fcr">'+msg.data.info+'</p>',tt);
				}	
			},
			error:function(){
				pop('<p class="fcr">系统繁忙，请稍后......</p>',tt);
			}			
		});		
	}	
});

(function(){
	var actObj = $('.tabs').find('li'), tb = $('#h-table'), noLog = tb.find('#noLog');
	if(noLog.size() == 0){
		tb.append('<tr id="noLog" style = "display:none"><td colspan="7">没有兑换记录！</td></tr>');
	}
	actObj.click(function(){
		var $this = $(this), typeVal = $this.attr('typ');
		$this.addClass('select').siblings().removeClass('select');
		changeInfo(typeVal);
	});
	function changeInfo(arg){
		var tr = tb.find('tr[type]'), tr_y = tb.find('tr[type][type = "'+ arg +'"]'), tr_n = tb.find('tr[type][type != "'+ arg +'"]'), noLog = $('#noLog');
		if(tr.size() > 0){
			if(arg == 0){
				tr.show();
				noLog.hide();
			}else if(arg > 0 && tr_y.size() > 0){
				tr_y.show();
				tr_n.hide();
				noLog.hide();			
			}else if(arg > 0 && tr_y.size() == 0){
				tr_n.hide();
				noLog.show();
			}
		}else{
			noLog.show();
		}
	}
})()	

