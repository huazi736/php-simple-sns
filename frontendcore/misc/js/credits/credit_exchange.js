(function(){
	$('#excBtn').live({
		click:function(){
		var pid = $('#pid').val();
		var type = $('#type').val();
		$.djax({
			url: mk_url('credit/redeem/doRedeem'),
			type: 'post',
			dataType:'json',
			data: {
				pid: pid
			},
			success: function(data){
				var html = '<div class="tipsBox clearfix"><div class="fl"><img src="'+CONFIG['misc_path']+'img/credits/fail.png" /></div>' +
				'<div class="fl pl20"><h5 class="fcr">对不起，兑换失败!</h5>' +
				'<p>'+data.info+'</p></div>'
				if (data.status == 1&&type ==1) {
					window.location.href = ''+data.data+'';
				}
				else {
					$(this).popUp({
						width: 450,
						title: '实物兑换',
						content: html,
						buttons: '<span class="popBtns blueBtn closeBtn">关闭</span>',
						mask: true,
						maskMode: true
					});
					//console.log(data.info)
				}
			}
		});
	}
	})	
})()
