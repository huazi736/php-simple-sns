/**
 * @author:    bohailiang
 * @created:   2012/07/10
 * @desc:      网页粉丝列表
 */
var list = {
	init: function(url) {
		var moreUrl = (url.mk_url === false) ? url.moreUrl : mk_url(url.moreUrl);
		this.loadmore.scrollParameter.url = moreUrl;
		this.loadmore.init();
		// if(url.visibleUrl) {
		// 	var visibleUrl = (url.mk_url === false) ? url.visibleUrl : mk_url(url.visibleUrl);
		// 	this.visible(visibleUrl);
		// }
		
		if(url.searchUrl) {
			var searchUrl = (url.mk_url === false) ? url.searchUrl : mk_url(url.searchUrl);
			this.search.init(searchUrl);
		}

		//start: 关系按钮绑定 by卜海亮 2012-07-10
		this.sameRelation($('.sameFriend'));
		//end: 关系按钮绑定
	},

	//显示共同好友或共同关注，弹出层
	sameRelation: function(obj){
		var self = this;
		obj.each(function(){
			var _this = $(this),
				href = _this.attr('href'),
				rid = _this.parents('li').attr('rid'),
				samerel = _this.attr('rel'),
				title = '';

			_this.attr('href', 'javascript:void(0);');

			switch(samerel){
				case '1':
					title = '共同好友列表';
					break;
				case '2':
					title = '共同关注列表';
					break;
				case '3':
					title = '共同兴趣列表';
					break;
			}

			_this.click(function(){
				$.ajax({
					url: href,
					type: "post",
					dataType: "json",
					data: { f_uid: rid },
					success: function(result){
						if(result.status==1){
							//返回成功
							result = result.data;

							var html = '<ul>',
								newLi = '',
								item = {};

							for(var i = 0; i < result.data.length; i++){
								item = result.data[i];
								newLi = '<li>\
											<a href="' + item.url + '"><img src="' + item.img + '" /></a>\
											<a class="name" href="' + item.url + '">' + item.name + '</a>\
										</li>\
								';
								html += newLi;
							}
							html += '</ul><br class="clear" />';
							
							_this.popUp({
								width : 560,
								height: 400,
								title : title,
								content : '<div class="shareList" id="share_List_box">'+ html +'</div>',
								mask : true,
								maskMode : false,
								buttons : '<span class="popBtns closeBtn">关闭</span>',
								callback : function() {
									$(_this).closePopUp();
								}
							}); //重新绑定事件
							$('div.popCont').css({'overflow-y':"scroll"});
							return false;
						}else{
							//返回失败，提示错误信息，弹出层
							_this.popUp({
								width : 460,
								title : '温馨提示',
								content : '<div class="shareList" id="share_List_box">'+ result.info +'</div>',
								mask : true,
								maskMode : false,
								buttons : '<span class="popBtns closeBtn">关闭</span>',
								callback : function() {
									$(_this).closePopUp();
								}
							}); //重新绑定事件
							return false;
						}
					}
				}); 
			});
		});
	},

	//组合列表html代码，参照模板html
	listHtml: function(data, parent, isSelf){
		var html = '',
			item = {},
			newLi = '',
			newItemLi;
		for(var i = 0; i < data.length; i++){
			item = data[i];
			newLi = '<li class="newItem listli" rid="' + item.id + '">\
                        <div class="avatarBox">\
							<a href="' + item.href + '"><img src="' + item.src + '" height="65" width="65" alt="" /></a>';
			if(true == isSelf){
				newLi += '	<s id="' + item.id + '"></s>';
			}
            newLi += '	</div>\
						<div class="listInfo">\
							<span class="uName">\
								<a href="' + item.href + '">' + item.name + '</a>\
								<span>' + item.now_addr + '</span>\
							</span>\
							<div class="relationState">\
								<span>关注 <a href="' + item.following_url + '">' + item.following + '</a></span><span>|</span><span>粉丝 <a href="' + item.follower_url + '">' + item.follower + '</a></span><span>|</span><span>好友 <a href="' + item.friend_url + '">' + item.friend + '</a></span>\
							</div>\
							<div class="relationSame">' + item.display + '</div>\
						</div>\
                    </li>';

            html += newLi;
		}
		newItemLi = parent.append(html).find('li.newItem');
		list.sameRelation($('.sameFriend', newItemLi));
		newItemLi.removeClass('newItem');
	},

	//滚动加载及点击加载
	loadmore: {
		init: function() {
			if($('#loadmore')[0]) {
				this.bindScroll();
			}
		},
		//滚动加载及点击加载 ——参数
		scrollParameter: {
			pager: 2,
			scroll: 0,
			url: '',
			keyword: ''
		},
		resetLoad: function(url, keyword) {
			this.scrollParameter = {
				pager: 2,
				scroll: 0,
				url: url,
				keyword: keyword
			};

			$('#loadmore').unbind('click');
			this.clickGet($('#loadmore'));
		},
		bindScroll: function() {
			var self = this;
			$(window).on('scroll', self.scroll);
			this.clickGet($('#loadmore'));
		},
		//滚动获取更多
		scroll: function() {
			var bar = $('#loadmore');
			if(bar[0] && !bar.hasClass('getting')) {
				var wH = $(window).height(),
					sH = $(window).scrollTop(),
					bH = $('body').height(),
					par = list.loadmore.scrollParameter;
				if(par.scroll < 2 && sH > 0 && sH > (bH - wH - 10)) {
					bar.removeClass('hide').addClass('getting');
					var data = {pager: par.pager};
					data.web_id = CONFIG['web_id'];
					if(par.keyword !== '') {
						data.keyword = par.keyword;
					}
					if(par.room_id)data.room_id = par.room_id;
					$.ajax({
						url: par.url,
						type: 'POST',
						data: data,
						dataType: 'json',
						success: function(data) {
							if(data.status.toString() === '1') {
								data = data.data;
								par.pager++;
								par.scroll++;
								//start: 组建html代码，绑定事件 by卜海亮 2012-07-13
								list.listHtml(data.data, $('#listWrap'), data.isSelf);
								//end: 组建html代码，绑定事件
								if(data.last === true) {
									bar.remove();
								} else {
									bar.addClass('hide').removeClass('getting');
									if(par.scroll === 2) {
										bar.removeClass('hide').addClass('clickGet').children().text('点击查看更多');
									}
								}
							} else {
								bar.popUp({
									width : 460,
									title : '温馨提示',
									content : '<div class="shareList" id="share_List_box">'+ data.info +'</div>',
									mask : true,
									maskMode : false,
									buttons : '<span class="popBtns closeBtn">关闭</span>',
									callback : function() {
										$(bar).closePopUp();
									}
								}); //重新绑定事件
								return false;
							}
						}
					});
				}
			}
		},
		//点击获取更多
		clickGet: function(loadBar) {
			var par = this.scrollParameter;
			loadBar.click(function () {
				var $this = $(this);
				if($this.hasClass('clickGet')) {
					$this.removeClass('clickGet').children().text('');
					var data = {pager: par.pager};
					data.web_id = CONFIG['web_id'];
					if(par.keyword !== '') {
						data.keyword = par.keyword;
					}
					if(par.room_id)data.room_id = par.room_id;
					$.djax({
						url: par.url,
						type: 'POST',
						data: data,
						dataType: 'json',
						success: function(data) {
							if(data.status.toString() === '1') {
								data = data.data;
								par.pager++;
								par.scroll = 0;
								//start: 组建html代码，绑定事件 by卜海亮 2012-07-13
								list.listHtml(data.data, $('#listWrap'), data.isSelf);
								//end: 组建html代码，绑定事件
								if(data.last === true) {
									$this.remove();
								}
							} else {
								$this.popUp({
									width : 460,
									title : '温馨提示',
									content : '<div class="shareList" id="share_List_box">'+ data.info +'</div>',
									mask : true,
									maskMode : false,
									buttons : '<span class="popBtns closeBtn">关闭</span>',
									callback : function() {
										$($this).closePopUp();
									}
								}); //重新绑定事件
								return false;
							}
						}
					});
				}
			});
		}
	},
	//搜索
	search: {
		init: function(url) {
			var searchInput = $('#searchList'),
				self = this,
				opt = self.opts,
				room_id = searchInput.attr('room_id');
			if(room_id && room_id.length) {
				list.loadmore.scrollParameter.room_id = room_id;
				opt.room_id = room_id;
			}
			if(opt.opera < 0) {
				searchInput.keyup(function() {
					var _this = this,
						value = $.trim(_this.value);
					setTimeout(function() {
						if(value !== opt.init_val && value !== $(_this).attr('ref')) {
							self.getData(searchInput, url);
							opt.init_val = _this.value;
						}
					}, 300);
					
				});
				
			} else {
				searchInput.bind('input',function(event) {
					var _this = this,
						value = $.trim(_this.value);
					setTimeout(function() {
						if(value !== opt.init_val && value !== $(_this).attr('ref')){
							self.getData(searchInput, url);
							opt.init_val = _this.value;
						}
					}, 300);
				});
			}
		},
		opts: {
			ajax: '',
			opera: navigator.userAgent.indexOf('Opera'),
			init_val: ''
		},
		getData: function(keywords, url) {
			// if($.trim(keywords.val()) === '') return;
			$('#nodata').addClass('hide');
			$('#noresult').addClass('hide');
			var opt = list.search.opts;
			if(opt.ajax.abort) {
				opt.ajax.abort();
			}
			keywords.addClass('loadingData');
			var keyword = $.trim(keywords.val()),
				_data = {keyword: keyword};
			
			_data.web_id = CONFIG['web_id'];
			if(opt.room_id)_data.room_id = opt.room_id;
			opt.ajax = $.ajax({
				type: 'POST',			
				url: url,
				data: _data,
				dataType:'json',
				success:function(data) {
					var wrap = $('#listWrap');
					if(data.status.toString() === '1') {
						data = data.data;
						wrap.empty();
						$('#loadmore').remove();
						if(data.last !== true) {
							$('#listWrap').after('<div id="loadmore" class="loadmore hide"><a></a></div>');
						}
						if(0 < data.data.length) {
							//start: 组建html代码，绑定事件 by卜海亮 2012-07-13
							list.listHtml(data.data, wrap, data.isSelf);
							//end: 组建html代码，绑定事件
						} else {
							$('#noresult').removeClass('hide');
						}
					} else {
						wrap.popUp({
							width : 460,
							title : '温馨提示',
							content : '<div class="shareList" id="share_List_box">'+ data.info +'</div>',
							mask : true,
							maskMode : false,
							buttons : '<span class="popBtns closeBtn">关闭</span>',
							callback : function() {
								$(wrap).closePopUp();
							}
						}); //重新绑定事件
						return false;
					}
					keywords.removeClass('loadingData');
					list.loadmore.resetLoad(url, keyword);
				}
			});
		}
	}
};