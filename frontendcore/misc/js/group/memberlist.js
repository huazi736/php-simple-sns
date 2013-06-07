/**
 * @author:    duxianwei
 * @created:   2012/03/22
 * @desc:      关注、好友、粉丝列表
 */
var list = {
	init: function(url) {
		var moreUrl = (url.mk_url === false) ? url.moreUrl : mk_url(url.moreUrl);
		this.loadmore.scrollParameter.url = moreUrl;
		this.loadmore.init();
		if(url.visibleUrl) {
			var visibleUrl = (url.mk_url === false) ? url.visibleUrl : mk_url(url.visibleUrl);
			this.visible(visibleUrl);
		}
		
		if(url.searchUrl) {
			var searchUrl = (url.mk_url === false) ? url.searchUrl : mk_url(url.searchUrl);
			this.search.init(searchUrl);
		}

	},

	//html渲染代码
	listHtml: function(data, parent){
		var html = '',
			newLi = '',
			dataList = data.data.list;
		for(var i = 0; i < dataList.length; i++){
			newLi = '<li class="clearfix">' +
						'<div class="avatarBox" >' +
							'<a href="'+ dataList[i].href + '"><img src="'+ dataList[i].avatar + '" alt="" /></a>' +
						'</div>' +
						'<span class="uName">' +
							'<a href="'+ dataList[i].href + '">'+ dataList[i].name +'</a>' +
						'</span>' +
					'</li>';
            html += newLi;
		}
		parent.append(html);
	},

	//滚动加载
	loadmore: {
		init: function() {
			this.bindScroll();
		},
		//滚动加载及点击加载 ——参数
		scrollParameter: {
			page: 2,
			url: '',
			keyword: '',
			isRequest:true
		},
		resetLoad: function(url, keyword,isRequest) {
			this.scrollParameter = {
				page: 2,
				url: url,
				keyword: keyword,
				isRequest:isRequest
			};
		},
		bindScroll: function() {
			var self = this;
			$(window).on('scroll', self.scroll);
		},
		scroll: function() {
			var wH = $(window).height(),
				sH = $(window).scrollTop(),
				bH = $('body').height(),
				par = list.loadmore.scrollParameter,
				isRequest = par.isRequest;
			if(sH > 0 && sH > (bH - wH - 10) && isRequest) {
				var data = {page: par.page,gid:$("#group_id").val()};
				if(par.keyword !== '') {
					data.keyword = par.keyword;
				}
				$.djax({
					url: par.url,
					type: 'POST',
					data: data,
					dataType: 'json',
					success: function(data) {
						if(data.status === 1) {
							par.page++;
							//start: 组建html代码，绑定事件 by卜海亮 2012-07-13
							list.listHtml(data, $('#listWrap'));
							//end: 组建html代码，绑定事件
							if(data.data.last === true) {
								par.isRequest = false;
							}
						}
						else{
							par.isRequest = false;
						}
					},
					error:function(){
						par.isRequest = false;
						$.alert("对不起，加载数据失败，请刷新页面重试!");
					}
				});
			}
		}
	},
	//搜索
	search: {
		init: function(url) {
			var searchInput = $('.group_search_input'),
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
				
			} 
			else {
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
			var opt = list.search.opts;
			if(opt.ajax.abort) {
				opt.ajax.abort();
			}
			var keyword = $.trim(keywords.val()),
				_data = {keyword: keyword,gid:$("#dissolve_group").attr('gid'),page:1};
			if(opt.room_id)_data.room_id = opt.room_id;
			par = list.loadmore.scrollParameter,
			opt.ajax = $.ajax({
				type: 'POST',			
				url: url,
				data: _data,
				dataType:'json',
				success:function(data) {
					var isRequest = true;
					if(data.status === 1) {
							par.page++;
							//start: 组建html代码，绑定事件 by卜海亮 2012-07-13
							$('.group_member_manage ul').empty();
							list.listHtml(data, $('.group_member_manage ul'));

							//end: 组建html代码，绑定事件
							if(data.data.last === true) {
								par.isRequest = false;
							}
						}
						else{
							par.isRequest = false;
						}
					list.loadmore.resetLoad(url, keyword,true);
				}
			});
		}
	}
};