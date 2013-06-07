
/*
 * Created on 2012-3-12.
 * @name:  v1.0
 * @author: 
 * @desc:  
		 中
 */

/*
(function($) {
	
});
*/


// interest 类
var interest	= {
	init:function(){	// 初使化
		var self = this;
		self.top_click();			// top 事件
		self.top_continue_load();	// 继续加载事件
		
		self.box_min_continue_load();	// 小分类的加载
		self.twoclass();//二级分类点击事件
		self.scroll_event();	// 鼠标滚动事件
		
	},
	twoclass:function(){
		var _self = this;
		var twoClass = $('#twoclass').find("li");
		twoClass.live("click",function(){
			twoClass.removeClass("current");
			$(this).addClass("current");
			$(this).parent("ul").next().fadeIn('fast',function(){
				
			});
		})
	},
	top_click:function(){	// top 事件
		$('.interest_top').click(function() {
			interest.top_click_func(this);
		});	
	},
	top_continue_load:function(){	// 继续加载事件
		$('.top_continue_load').live('click' , function() {
			interest.top_click_func(this);
		});
	},
	
	top_click_func:function(obj){
		// participle_id  	// 分词id
		// request_url	 	// 请求url 地址的数据   index.php?c=index&m=get_category_top&participle_id=1&imid=1&iid=1
		// page 			// 分页
		// is_load			// 是否己经加载
		var is_load 	= interest.cint( $(obj).attr('is_load') );	// 是否己加载
		var interest_top_this = obj;
		var participle_id = $(obj).attr('participle_id');
		
		if(is_load==1){			// 隐藏
			box_id = 'web_item_box_'+participle_id;
			$("#"+box_id).find("[ajax='ajax']").hide();
			$("#"+box_id).find('.top_continue_load').hide();
			$(interest_top_this).attr('is_load' ,'2');
			$(obj).text("全部TOP");
			
		}else if(is_load==2){	// 显示
			
			box_id = 'web_item_box_'+participle_id;
			$("#"+box_id).find("[ajax='ajax']").show();
			$("#"+box_id).find('.top_continue_load').show();
			$(interest_top_this).attr('is_load' ,'1');
			$(obj).text("收起");
			
		}else{	// 加载数据
			if($(obj).attr('class').indexOf('interest_top')>=0){
				$(obj).text("收起");
			}
			var url		= $(obj).attr('request_url');
			var page	= interest.cint( $(obj).attr('page') );
			
			url = url + '&page='+page;
			
			
			$.post(url , '' , function(result){
				$(interest_top_this).attr('is_load' ,'1'); // alert(box_id)
				box_id = 'web_item_box_'+participle_id
				$("#"+box_id).attr('lock','');	// 解锁
				
				$("#"+box_id).find('.top_continue_load').remove();
				$("#"+box_id).append(result);
			});
			
		}
	},
	
	
	box_min_continue_load:function(){	// 小分类里的加载
		$('.box_min_continue_load').live('click' , function() {
			interest.box_min_click_func(this);
		});
	},
	box_min_click_func:function(obj){
		// participle_id  	//分词id
		// request_url	 	请求url 地址的数据   index.php?c=index&m=get_category_top&participle_id=1&imid=1&iid=1
		// page 			0	// 分页
		// is_load			// 是否己经加载
		var url		= $(obj).attr('request_url');
		var page	= interest.cint( $(obj).attr('page') );
		var participle_id = $(obj).attr('participle_id');
		url = url + '&page='+page;
		$.post(url , '' , function(result){
				//$(interest_top_this).attr('is_load' ,'1');
				box_id = 'web_item_box_min'
				$("body").attr('lock','');
				$("#"+box_id).find('.box_min_continue_load').remove();
				$("#"+box_id).append(result);
		});
		
	},
	
	
	scroll_event:function(){
		$(window).scroll(function(){
			var top		= interest.__scrolltop();
			var height	= interest.__scrollheight();
			var wheight	= $(window).height();
			
			
			if( (wheight+top+60) > height){
				// 模拟加载
				var is_load		= $('.box_min_continue_load').attr('is_load');
				var load_count	= interest.cint($("body").attr('box_continue_load_count'));
				var lock 		= $("body").attr('lock');
				
				if( lock!='true' && is_load!=1 && load_count<2 ){	// 自动 load 两次
					$('.box_min_continue_load').attr('is_load','1');
					$("body").attr('box_continue_load_count', (load_count+1) );
					$("body").attr('lock','true');	// 锁定
					setTimeout(function(){
						$('.box_min_continue_load').click();
					},800 );
				}
			}
			var top_load	= $(".top_continue_load");
			if(top_load.length>=1){
				var load_count	= interest.cint( $(top_load).parent().attr('box_continue_load_count') );
				var lock	= $(top_load).parent().attr('lock');
				
				if( lock!='true' && load_count<1){		// 自动 load 两次   这里1次就等于两次  因为  top 有一次
					var offset = top_load.offset();
					var top_load_top	= offset.top;
					if( (wheight+top) > top_load_top && (wheight+top ) < top_load_top + (wheight-100) ){
						$(top_load).parent().attr('box_continue_load_count', (load_count+1) );
						$(top_load).parent().attr('lock','true');	// 锁定
						setTimeout(function(){
											$('.top_continue_load').click();
						},800 );
					}
				}
			}
		});
	},
	
	
	cint:function(value){						//  parseInt  转成数字  整型
		if( (!value))	return 0;
		var number	=  parseInt(value,10);
		if(isNaN(number)) return 0;
		return number;
	},
	__scrolltop:function(){						// 滚动条高度
		var scrollTop=0;
		if(document.documentElement&&document.documentElement.scrollTop){
			scrollTop = document.documentElement.scrollTop;
		}else if(document.body){
			scrollTop = document.body.scrollTop;
		}
		return scrollTop;
	},
	__scrollheight:function(){
		var scrollHeight	= 0;
		if(document.documentElement&&document.documentElement.scrollTop){
			scrollHeight	= document.documentElement.scrollHeight;
		}else if(document.body){
			scrollHeight	= document.body.scrollHeight;
		}
		return scrollHeight;
	}
	
}


 function Class_createWebIntreset() {
	var _self = this;
	this.findButton = $("span.linkButtonspan");
	this.buttonSize = $("div.interestMenu").find("ul").find("li").size();
	this.buttonText = $("span.text");
	this.changeLi = $("div.letter-Box").find("li.letterItem");
	this.menu = $("div.interestMenu");
	
 }
 
 Class_createWebIntreset.prototype = {
	init : function(){
		var _self = this;
		this.showLi(_self.findButton,_self.buttonSize,_self.buttonText);//显示列表分类
		this.changeBackgroundLi(_self.changeLi);
		this.changeBackgroundMenu(_self.menu);

	},
	showLi : function(findButton,buttonSize,buttonText){
		var _self = this;
		for(var i = 8; i < _self.buttonSize ; i++){
			$("div.interestMenu").find("ul").find("li").eq(i).hide();
		}
		
		buttonText.text(_self.buttonSize - 8);
		_self.findButton.toggle(function(){
			buttonText.text("");
			buttonText.append("&nbsp;");
			for(var i = 8; i < _self.buttonSize ; i++){
				$("div.interestMenu").find("ul").find("li").eq(i).show();
			}
			$("span.linkButtonspan").find("span").addClass("textAddImg");
		},function(){
			buttonText.text(_self.buttonSize - 8);
			for(var i = 8; i < _self.buttonSize ; i++){
				$("div.interestMenu").find("ul").find("li").eq(i).hide();
			}
			$("span.linkButtonspan").find("span").removeClass("textAddImg");
		});
		

	},
	changeBackgroundMenu : function(menu){
		var _self = this;

		menu.find("li").hover(function(){
			var index = $(this).index(),
			imgLink = $(this).find("div.imgLink");
			if(true == imgLink.hasClass('shopping')){
				// 购物
				imgLink.attr("style","background-position:8px 0px");
			} else if(true == imgLink.hasClass('groupshop')){
				// 本地生活
				imgLink.attr("style","background-position:8px -130px");
			} else if(true == imgLink.hasClass('traveling')){
				// 旅游景点
				imgLink.attr("style","background-position:8px -260px");
			} else if(true == imgLink.hasClass('games')){
				// 游戏
				imgLink.attr("style","background-position:8px -514px");
			} else if(true == imgLink.hasClass('house')){
				// 游戏
				imgLink.attr("style","background-position:8px -642px");
			}
			/*
			switch(index)
			{
				case 0:
					imgLink.attr("style","background-position:8px 0px");
					break
				case 1:
					imgLink.attr("style","background-position:8px -130px");
					break
				case 2:
					imgLink.attr("style","background-position:8px -260px");
					break
				// start: 添加游戏图标 by卜海亮 2012-07-27
				case 5:
					imgLink.attr("style","background-position:8px -514px");
					break
				// end: 添加游戏图标
				default:
					imgLink.attr("style","");
			}*/
		},function(){
			var index = $(this).index(),
			imgLink = $(this).find("div.imgLink");
			if($(this).attr("set") != 1){
				if(true == imgLink.hasClass('shopping')){
					// 购物
					imgLink.attr("style","background-position:8px -64px");
				} else if(true == imgLink.hasClass('groupshop')){
					// 本地生活
					imgLink.attr("style","background-position:8px -194px");
				} else if(true == imgLink.hasClass('traveling')){
					// 旅游景点
					imgLink.attr("style","background-position:8px -324px");
				} else if(true == imgLink.hasClass('games')){
					// 游戏
					imgLink.attr("style","background-position:8px -578px");
				} else if(true == imgLink.hasClass('house')){
					// 游戏
					imgLink.attr("style","background-position:8px -708px");
				}
			}
			/*
			switch(index)
			{
				case 0:
					
					if($(this).attr("set") != 1){
						imgLink.attr("style","background-position:8px -64px");
					}
					break
				case 1:
					if($(this).attr("set") != 1){
						imgLink.attr("style","background-position:8px -194px");
					}
					break
				case 2:
					if($(this).attr("set") != 1){
						imgLink.attr("style","background-position:8px -324px");
					}
					break
				// start: 添加游戏图标 by卜海亮 2012-07-27
				case 5:
					if($(this).attr("set") != 1){
						imgLink.attr("style","background-position:8px -578px");
					}
					break
				// end: 添加游戏图标
				default:
			}*/
		});
		var index = $("li[set=1]").index(),
		imgLink = $("li[set=1]").find("div.imgLink");
		if(true == imgLink.hasClass('shopping')){
			// 购物
			imgLink.attr("style","background-position:8px 0px");
		} else if(true == imgLink.hasClass('groupshop')){
			// 本地生活
			imgLink.attr("style","background-position:8px -130px");
		} else if(true == imgLink.hasClass('traveling')){
			// 旅游景点
			imgLink.attr("style","background-position:8px -260px");
		} else if(true == imgLink.hasClass('games')){
			// 游戏
			imgLink.attr("style","background-position:8px -514px");
		} else if(true == imgLink.hasClass('house')){
			// 游戏
			imgLink.attr("style","background-position:8px -642px");
		}
		/*
		switch(index)
		{
			case 0:
					imgLink.attr("style","background-position:8px 0px");
				break
			case 1:
					imgLink.attr("style","background-position:8px -130px");
				break
			case 2:
					imgLink.attr("style","background-position:8px -260px");
				break
			// start: 添加游戏图标 by卜海亮 2012-07-27
			case 5:
					imgLink.attr("style","background-position:8px -514px");
				break
			// end: 添加游戏图标
			default:
				imgLink.attr("style","");
		}*/
		
	},
	changeBackgroundLi : function(changeLi){
		changeLi.live("hover",function(){
			changeLi.attr("style","background-color:#ffffff");
			$(this).attr("style","background-color:#e7ebf2;");
		});
		$("div.sort-top").find("ul").eq(0).find("li").bind("click",function(){
			$("div.sort-top").find("ul").eq(0).find("li").removeClass("current");
			$(this).addClass("current");
		});
		$("div.sort-top").find("ul").eq(1).find("li").bind("click",function(){
			$("div.sort-top").find("ul").eq(1).find("li").removeClass("current");
			$(this).addClass("current");
		})
	}
	
	
 }



// 加载
$(function() {
	interest.init();
	var createWeb = new Class_createWebIntreset();
	createWeb.init();
})
function perfect_address(){
	var _url = mk_url('user/' + CONFIG["dkcode"] + '/userwiki/index');
	$.alert('住址填写不完整，<a href="'+ _url + '">请点击此处完善</a>');	
}

