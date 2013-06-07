
/*
 * Created on 2012-07-26.
 * @name:  v1.0
 * @author: bohailiang
 * @desc:  
		 中
 */

/*
(function($) {
	
});
*/


// game 类
var game = {
	webList: $("#webList"),		// 网页列表的容器 => div
	classNav: $("#classNav"),	// 左侧类别导航 => ul
	clickMore: $("#clickMore"),	// 点击更多 => div
	scollCount: 0,				// 滚动加载的次数
	isLast: false,				// 是否已经显示到最后 false - 未； ture - 已显示到最后
	getMoreUrl: '',				// 获取更多的url
	postData: null,				// 获取更多时，需要post传值的内容
	thePage: 1,					// 当前的页数

	// 初始化
	init: function(option){
		// 赋值
		game.getMoreUrl = option.getMore.url;
		game.postData = option.getMore.postData;

		// 绑定事件
		game.bindEvent();
	},

	// 绑定事件
	bindEvent: function(method, obj){
		// 为左侧的类别的<a></a>绑定事件
		game.classNav.find('li').find('a').each(function(){
			var _this = $(this),
				href = _this.attr('href'),				// 存储a的href值
				parent = _this.parents('li'),			// 获取a的父元素li
				isCurrent = parent.hasClass('current');	// 是否是当前选中的
			_this.attr('href', 'javascript: void(0);');	// 重写a的href值

			$(this).click(function(){
				//点击后，不是当前选中的类别，ajax请求该类别下的网页，在右边显示
				if(false === isCurrent){
					game.ajaxFun(game.classNav, href, function(data){
						var html = game.htmlBuild(data.list, 'new');		// 组建html代码
						game.webList.empty();
						game.webList.html(html);	// 重置网页列表的容器的html
						game.isLast = data.last;
						game.thePage = 1;			// 重置页面计数
					});
				}
			});
		});

		// 绑定窗口滚动事件
		$(window).bind('scroll', function(){
			var wH = $(window).height(),
				sH = $(window).scrollTop(),
				bH = $('body').height();

			// 滚动加载计数小于2，未达显示了最后，ajax获取数据
			if(game.scollCount < 2 && sH > 0 && sH > (bH - wH - 10) && false == game.isLast) {
				var postData = $.extend({}, game.postData, { page: game.thePage + 1 });
				// ajax请求下一页数据
				game.ajaxFun(game.webList, game.getMoreUrl, function(data){
					var html = game.htmlBuild(data.list);		// 组建html代码
					game.clickMore.before(html);				// 嵌入新的html代码
					game.isLast = data.last;
					game.thePage = game.thePage + 1;			// 页面计数 + 1
					game.scollCount = game.scollCount + 1;		// 重置滚动加载计数
					if(2 == game.scollCount){
						game.clickMore.show();		// 显示点击更多
					}
				}, postData);
			}
		});

		// 绑定“查看更多”的点击事件
		game.clickMore.find('a').attr('href', 'javascript:void(0);'); // "#" => "javascript:void(0);" 放置点击后回到顶部
		game.clickMore.click(function(){
			// 已经显示了最后，或是滚动加载技术未达到2，则不处理
			if(true == game.isLast || 2 > game.scollCount){
				game.clickMore.hide();		// 隐藏点击更多
				return false;
			}
			var postData = $.extend({}, game.postData, { page: game.thePage + 1 });
			// ajax请求下一页数据
			game.ajaxFun(game.webList, game.getMoreUrl, function(data){
				var html = game.htmlBuild(data.list);		// 组建html代码
				game.clickMore.before(html);				// 嵌入新的html代码
				game.isLast = data.last;
				game.thePage = game.thePage + 1;			// 页面计数 + 1
				game.scollCount = 0;		// 重置滚动加载计数
			}, postData);

			game.clickMore.hide();		// 隐藏点击更多
		});
	},

	// 组建html代码
	htmlBuild: function(list, isNew){
		var html = '',
			item = {},
			newLi = '',
			newItemLi;

		if( 0 < list.length ){	// 又数据返回
			for(var i = 0; i < list.length; i++){
				item = list[i];
				newLi = '<div class="webInfos">\
							<div class="webImg">\
								<a target="_blank" href="#"><img src="http://avatar.duankou.dev/webavatar_1609_mm.jpg" width="99" alt="" height="99" /></a>\
							</div>\
							<div class="webInfo">\
								<div class="webName"><a target="_blank" href="#">名称：测试网页</a></div>\
								<div class="follow">粉丝：<a target="_blank" href="#">1</a></div>\
								<div class="cate">属类：游戏</div>\
							</div>\
						</div>';

				html += newLi;
			}
		} else {	// 没有数据返回
			// 新加载页面的，就显示提示语句，否则不显示
			html = ('new' == isNew) ? '<div class="noDate">该类别下没有网页</div>' : '';
		}

		return html;
	},

	// 统一的ajxa请求
	ajaxFun: function(obj, url, callback, data){
		// 防止多次请求
		if(true == obj.hasClass('getting')){
			return false;
		}
		obj.addClass('getting');
		var _data = {};

		data = $.extend({}, _data, data);
		$.djax({
			url: url,
			type: 'POST',
			dataType: 'json',
			data: data,
			success: function(data) {
				data = {status: 1, data: { last: false, list: [1, 2, 3]}};
				if(data.status !== 1) {
					//显示错误信息，弹出层
					obj.popUp({
						width:400,
						title:'温馨提示',
						content:'<div class="delFriendDiv">' + data.info + '</div>',
						buttons:'<span class="popBtns closeBtn callbackBtn">关闭</span>',
						mask:false,
						maskMode:true,
						callback:function(){
							//window.location.reload();
							return false;
						}
					});

					return false;
				}
				data = data.data;
				callback(data);
				obj.removeClass('getting');

				return true;
			}
		});
	}
}