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
					path = $(this).closest('ul').hasClass('webClass') ? 'Web' : '',
					is_show = 0;

				if (_self.closest('li').hasClass('hid')){
					is_show = 1;
				}

				$.ajax({
					url: mk_url('main/following/categoryHidden'),
					data:{iid: type, is_show: is_show},
					dataType:"json",
					success: function(data){
						if(data.status == 1){
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