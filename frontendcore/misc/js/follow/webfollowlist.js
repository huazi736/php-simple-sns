/**
 * @author:    yewang
 * @created:   2012/03/22
 * @desc:      关注、好友、粉丝列表
 */
var list = {
	init: function(url) {
		if(url.type){
			var relationUrl = {
				webfollowing: {visibleUrl:'main/' + CONFIG['action_dkcode'] + '/following/visibleWebFollowing', moreUrl:'main/' + CONFIG['action_dkcode'] + '/following/getWebFollowingsByPage', searchUrl:'main/' + CONFIG['action_dkcode'] + '/following/searchWebFollowingByUserName', web_cateid: 0}
			};
			relationUrl[url.type].web_cateid = url.web_cateid;

			url = relationUrl[url.type];
		}

		var moreUrl = (url.mk_url === false) ? url.moreUrl : mk_url(url.moreUrl);
		this.loadmore.scrollParameter.url = moreUrl;
		this.loadmore.init();
		if(url.visibleUrl) {
			var visibleUrl = (url.mk_url === false) ? url.visibleUrl : mk_url(url.visibleUrl);
			this.visible(visibleUrl);
		}

		this.web_cateid = url.web_cateid;
		
		if(url.searchUrl) {
			var searchUrl = (url.mk_url === false) ? url.searchUrl : mk_url(url.searchUrl);
			this.search.init(searchUrl);
		}

		//start: 关系按钮绑定 by卜海亮 2012-07-10
		$(".relationWraps").each(function(){
			$(this).find('div.statusBox').webRelation();
		});
		//end: 关系按钮绑定
	},

	//组合列表html代码
	listHtml: function(data, parent, isSelf){
		var html = '',
			item = {},
			newLi = '',
			newItemLi;
		for(var i = 0; i < data.length; i++){
			item = data[i];
			newLi = '<li class="newItem listli" rid="' + item.aid + '">\
                                        <div class="avatarBox invisible">\
												<a href="' + item.href + '"><img src="' + item.src + '" hegiht="65" width="65" alt="" /></a>';
			if(true == isSelf){
				newLi += '	<s class="setHid" id="' + item.aid + '"></s>';
			}

			if('' != item.notes){
				item.notes = '简介：' + item.notes;
			} else {
				item.notes = '';
			}
            newLi += '	</div>\
                        <div class="listInfo">\
							<span class="uName">\
								<a href="' + item.href + '">' + item.name + '</a>\
							</span>\
							<div class="relationState">\
								<span>粉丝 <a href="' + item.follower_url + '">' + item.follower + '</a></span>\
							</div>\
							<div class="subdes">' + item.notes + '</div>\
						</div>\
						<div class="relation relationWraps">\
							<div webid="' + item.aid + '" uid="' + item.web_uid + '" days="' + item.days.days + '" dtype="d" rel="' + item.days.relation + '" class="statusBox"></div>\
						</div>\
                    </li>';

            if( (true == isSelf && false == item.hidden) || false == isSelf ){
            	newLi = newLi.replace('avatarBox invisible', 'avatarBox');
            }

            html += newLi;
		}
		newItemLi = parent.append(html).find('li.newItem');
		newItemLi.find('div.statusBox').webRelation();
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
					data.web_cateid = list.web_cateid;
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
		clickGet: function(loadBar) {
			var par = this.scrollParameter;
			loadBar.click(function () {
				var $this = $(this);
				if($this.hasClass('clickGet')) {
					$this.removeClass('clickGet').children().text('');
					var data = {pager: par.pager};
					data.web_cateid = list.web_cateid;
					
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
	//点击可见性
	visible: function(url) {
		$('#listWrap').delegate('s', 'click', function() {
			var par = $(this).parent(),
				data = {web_id:this.id, action_dkcode:CONFIG['action_dkcode'], visible:false};
			if(par.hasClass('invisible')) {
				data.visible = true;
			}
			$.djax({
				url: url,
				data: data,
				success: function(response) {
					if(response.status.toString() === '1') {
						if(data.visible === true) {
							par.removeClass('invisible');
						} else {
							par.addClass('invisible');
						}
					} else {
						par.popUp({
							width : 460,
							title : '温馨提示',
							content : '<div class="shareList" id="share_List_box">'+ response.info +'</div>',
							mask : true,
							maskMode : false,
							buttons : '<span class="popBtns closeBtn">关闭</span>',
							callback : function() {
								$(par).closePopUp();
							}
						}); //重新绑定事件
						return false;
					}
				}
			});
		});
		$('#listWrap').delegate('li.listli', 'hover', function() {
			$(this).find('div.avatarBox').toggleClass('hoverIn');
		});
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
			_data.web_cateid = list.web_cateid;
			
			if(opt.room_id)_data.room_id = opt.room_id;
			opt.ajax = $.djax({
				loading:true,
				el: $('#searchList'),
				type: 'POST',			
				url: url,
				data: _data,
				dataType:'json',
				success:function(data) {
					$("#searchList").removeClass('min-loading');
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