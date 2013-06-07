
/**
 * weblist.js
 *
 * @author        yewang
 * @update        2012/07/12
 * @description   我的网页列表显示和管理
 * 
 */

(function($){
	var weblist = {
		init: function() {
			if(!$('#noWeb')[0]) { //有创建网页情况下 绑定事件
				var _this = this,
					lis = $('#my_weblist').find('li');
				lis.eq(0).find('label').eq(1).addClass('hide');
				lis.each(function() {
					var li = $(this);
					_this.operateWeb(li);
				});

				if($('#loadMore')[0]) {
					$(window).on('scroll', _this.scroll);
				}
			}
		},
		par: {page:2, bar:$('#loadMore'), ul:$('#my_weblist') },
		scroll: function() {
			var _this = weblist;
			_this.loadList(_this.par, mk_url('interest/web_setting/getWebDate'));
		},
		loadList: function(par, url) {
			var _this = this,
				bar = par.bar;
			if(!bar.hasClass('getting')) {
				var wH = $(window).height(),
					sH = $(window).scrollTop(),
					bH = $('body').height();
				if(sH > 0 && sH > (bH - wH - 10)) {
					bar.removeClass('hide').addClass('getting');
					$.djax({
						url: url,
						data: {page: par.page},
						success: function(data) {
							var data = data.data;
							par.page++;
							var listData = data.data,
								lis = '';
							for(var i = 0,len = listData.length; i < len; i++) {
								var d = listData[i],
									option = '<span class="del_web">删除</span><span class="edit_web"><i></i>编辑</span>';
								if(d.is_del === 1) option = '处理中';
								lis += '<li id="'+d.web_aid+'">\
											<div class="avatarBox"><a href="'+d.url+'"><img src="'+d.web_avatar+'" alt="" /></a></div>\
											<p class="webInfo"><a href="'+d.url+'">'+d.web_name+'</a><span>粉丝数：'+d.fans_count+'</span></p><div class="webOperate">'+option+'</div>\
											<div class="webSet hide">\
												<div class="setList">\
													<label>隐私设置：<input type="checkbox" name="showName" />同步创建者姓名显示到网页</label>\
													<label>置顶设置：<input type="checkbox" name="toTop" />显示在网页导航条首页</label>\
												</div>\
												<div class="webBtns">\
													<span class="btnBlue submitSet"><a href="javascript:void(0);">确认</a></span>\
													<span class="btnBlue cancelSet"><a href="javascript:void(0);">取消</a></span>\
												</div>\
											</div>\
										</li>';
							}
							
							var lastLi = par.ul.children('li:last');
							par.ul.append(lis);
							var lists = lastLi.nextAll();
							lists.each(function() {
								var li = $(this);
								_this.operateWeb(li);
							});
							// $('#listWrap').append(data.list);
							if(data.is_more === 0) {
								bar.remove();
								$(window).off('scroll', _this.scroll);
							} else {
								bar.addClass('hide').removeClass('getting');
							}
						}
					});
				}
			}
		},
		//网页操作事件绑定
		operateWeb: function(li) {
			var _this= this,
				edit = li.find('span.edit_web'),
				del  = li.find('span.del_web'),
				box  = li.find('div.webSet'),
				ok   = li.find('span.submitSet'),
				clo  = li.find('span.cancelSet'),
				id   = li[0].id;

			//编辑按钮事件绑定
			edit.on('click', function() {
				if(box.hasClass('hide')) {
					$.djax({
						url: mk_url('interest/web_setting/getoption'),
						data: {web_id: id},
						success: function(data) {
							var data = data.data,
								checked = false;
							if(data.state === 1) {
								checked = true;
							}
							var showName = li.find('input[name=showName]')[0],
								toTop = li.find('input[name=toTop]')[0];
							showName.checked = checked;
							showName.value = checked;
							checked = false;
							if(li.index() === 0) {
								checked = true;
							}
							toTop.checked = checked;
							toTop.value = checked;
							box.removeClass('hide');
						}
					});
				} else {
					box.addClass('hide');
				}
			});

			clo.on('click', function() {
				edit.click();
			});

			//确认提交按钮事件操作
			ok.on('click', function() {
				var showName = li.find('input[name=showName]')[0],
					toTop = li.find('input[name=toTop]')[0];
				if(String(showName.checked) !== showName.value || String(toTop.checked) !== toTop.value) {
					var topweb = (toTop.checked) ? 1 : 0,
						synname = (showName.checked) ? 1 : 0;
					$.djax({
						url: mk_url('interest/web_setting/editWeb'),
						data: {web_id:id, topweb:topweb, synname:synname},
						success: function(data) {
							var data = data.data;
							if(data.state === 1) {
								$.alert('设置成功','提示');
								edit.click();
								if(topweb === 1 && li.index() > 0) {
									li.parent().prepend(li);
									toTop.parentNode.className = 'hide';
									li.next().find('label.hide').removeClass('hide');
								}
							}
						}
					});
				} else {
					edit.click();
				}
				
			});


			//删除按钮
			del.on('click', function() {
				$(this).popUp({
					width:580,
					title:'删除提示',
					content:'<div class="del-web-msg"><p class="fz14">您确定要删除该网页么？</p><p class="msg">(系统将在三天后删除该网页，并通知网页粉丝)</p></div>',
					buttons:'<span class="popBtns blueBtn callbackBtn">确定</span><span class="popBtns closeBtn">取消</span>',
					mask:true,
					callback: function() {
						$.djax({
							url: mk_url('interest/web_setting/delWeb'),
							data: {web_id: id},
							success: function(data) {
								if(data.data.state === 1) {
									del.parent().html('处理中');
									box.addClass('hide');
									$.closePopUp();
								}
								
							}

						});
					}
				});
			});
		}
	};
	$(function() {
		weblist.init();
	});
})(jQuery);