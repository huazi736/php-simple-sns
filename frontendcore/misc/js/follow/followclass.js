$(document).ready(function(){
	$(".followingClassNav").find('li').each(function(){
		var self = $(this);
		if(false == self.hasClass('web')){
			//需要设置隐藏的
			self.hover(function(){
				self.addClass('hov');
			}, function(){
				self.removeClass('hov');
			});

			//给<b>设置事件
			self.find('b').click(function(e){
				var _self = $(this),
					type = _self.prev().attr('class'),
					hid = '',
					path = $(this).closest('ul').hasClass('webClass') ? 'Web' : '';

				if (_self.closest('li').hasClass('hid')){
					hid ='un';
				}

				$.ajax({
					url: mk_url('main/following/' + hid + 'hidden' + path + 'FollowingCategory'),
					data:{'f_id': type},
					dataType:"json",
					success: function(data){
						if(data.state == 1){
							var li = _self.closest('li');
							if(li.hasClass('hid')){
								li.removeClass('hid');
							}else{
								li.addClass('hid');
							}
						}
					},
					error:'',
					cache:false
				});
			});
		} 
	});
});