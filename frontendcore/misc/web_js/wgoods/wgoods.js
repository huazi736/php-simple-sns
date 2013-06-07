function ShoppingClass(){
	var _self = this;
	this.popUpShow = $('div.popUpShow');
	this.popUpShowBac = $('div.popUpShowBac');

}

ShoppingClass.prototype = {
	init: function(){
		var _self = this;
		
		_self.scrollLoading();//滚动获取更多
		_self.clickMore();//点击DIVBAR显示更多
		_self.quickReturn_closeButton();
		_self.show_listPhoto(_self.popUpShow,_self.popUpShowBac);//点击显示遮罩层
		
		_self.shopDetailEditImg_live();	// 初使化  商品的修改
	
	},
	clickMore: function(){
		var _self = this;
		$("#divBar").click(function(){
			_self.scroll_redering();
			$("#divBar").attr("load_continue_time",0);
			$("#divBar").hide();
		});
	},
	//获取数据,渲染
	
	scroll_redering: function(){

		var _self = this;
		var obj	= $("#divBar");
		
		if(_self.cint($(obj).attr('is_end')) ==1 ){	// is last
			return ;
		}
		
		var load_continue_time = _self.cint($("#divBar").attr("load_continue_time"));
		
		
		$("#divBar").attr("load_continue_time",load_continue_time + 1);
		$("#divBar").addClass("divBarLoading");
		$("#divBar").show();
		
		var rurl	= $(obj).attr('request_url');
		var attrib	= $(obj).attr('page');
		rurl		= rurl + "&page=" + attrib;
		$.ajax({
			url : rurl, //'http://zhuliqi.duankou.com/www_duankou/channel/goods/get_list?web_id=1624',
			type : 'get',
			dataType : 'jsonp',
			jsonp : 'callback',
			success: function(data){
				if (data.status == "1"){
					var page = data.data.page;
					var is_end = data.data.is_end;
					$(obj).attr('page',page);
					$(obj).attr('is_end',is_end);
					for(var i = 0; i <= data.data.data.length; i++)
					{
						
						var classOne = $("#shoppingOne").height();
						var classTwo = $("#shoppingTwo").height();
						var classThree = $("#shoppingThree").height();
						var classFour = $("#shoppingFour").height();
						var minHeight = Math.min(classOne,classTwo,classThree,classFour);
						
						switch (minHeight)
						{
							case classOne:

								$("#shoppingOne").append(data.data.data[i]);
								
								break
							case classTwo:

								$("#shoppingTwo").append(data.data.data[i]);
								break
							case classThree:

								$("#shoppingThree").append(data.data.data[i]);
								break
							case classFour:

								$("#shoppingFour").append(data.data.data[i]);
								break
							default:

						}
						
					}
				}
				$("#divBar").removeClass("divBarLoading");
				if($("#divBar").attr("load_continue_time") < 2){
					$("#divBar").hide();
				}
				$("#divBar").attr("page",page);
				$("#divBar").attr("lock","0"); 
			}
		});
		

	},
	scrollLoading: function(){
		var _self = this;
		
		$(window).scroll(function(){
			var top =  _self._scrolltop(),
			height = _self._scrollHeight(),
			wheight = $(window).height();

			var divBar = $("#divBar");
			if((wheight + top + 60) > height){
				
				var load_continue_time = _self.cint(divBar.attr("load_continue_time"));
				var lock = divBar.attr("lock");
				if(lock == "0"  && load_continue_time < 2){
					divBar.attr('lock',"1");
					//读取数去开始选软页面；
					
					_self.scroll_redering();
					
				}else if(load_continue_time == 2){
					
				}
			}
		});
		
	},
	cint:function(value){						
		if( (!value))	return 0;
		var number	=  parseInt(value,10);
		if(isNaN(number)) return 0;
		return number;
	},
	_scrolltop:function(){						
		var scrollTop=0;
		if(document.documentElement&&document.documentElement.scrollTop){
			scrollTop = document.documentElement.scrollTop;
		}else if(document.body){
			scrollTop = document.body.scrollTop;
		}
		return scrollTop;
	},
	_scrollHeight:function(){
		var scrollHeight	= 0;
		if(document.documentElement&&document.documentElement.scrollTop){
			scrollHeight	= document.documentElement.scrollHeight;
		}else if(document.body){
			scrollHeight	= document.body.scrollHeight;
		}
		return scrollHeight;
	},
	quickReturn_closeButton: function(){
		//快速返回按钮
		var $backToTopEle = $('	<a class="upPopUp"></a>').appendTo($('.popUpShow')).click(function(){
			$("html,body").animate({scrollTop:0},120);
		}),$backToTopFun = function(){
			var st = $(document).scrollTop(),winh = $(window).height();
			(st > 0)?$backToTopEle.show():$backToTopEle.hide();
			if(!window.XMLHttpRequest){
				$backToTopEle.css("top",st + winh - 166);
			}
		};
		$(window).bind("scroll",$backToTopFun);
		$backToTopFun();


		
		//关闭按钮
		var $closeButton = $("div.closePopUp");

		$closeButton.live('click', function(){

			$(parent.document).find('#popUpShowZ').hide();
			$(parent.document).find('#popUpShowZ').parents().find("div.popUpShowBac").hide();
			$(parent.document).find('body').attr("style","overflow:auto;");
			//alert($("#popUpShowZ").length + "&" +$('.popUpShow').length);


			if($.browser.msie && (($.browser.version == "7.0") || ($.browser.version == "6.0"))){

				$(parent.document).find('html').css({"overflow":"auto"});
				$(parent.document).find('body').css({"overflow":"auto"});
			}
			return ;
			
		})

		$closeButton.hover(function(){
			$(this).attr("style","background-position:-17px -241px");
		},function(){
			$(this).attr("style","background-position:-17px -78px");
		})
	},
	show_listPhoto: function(popUpShow,popUpShowBac){
		var show_img = $('div.shopDetailHead').find('img'),
		setHeight = "",
		// url = 'http://zhuliqi.duankou.com/www_duankou/channel/goods/goods_desc?web_id=1624&gid=151',
		sendPara = "1";
		show_img.live('click',function(){
			var request_url	= $(this).parents('.shopDetail').attr('request_url');
			$('body').attr("style","overflow:hidden;");
			var marginLeft = (document.body.clientWidth-677)/2;
			var popUpshowBacHeight = $(document.body).height();
			popUpShowBac.attr("style","height:"+popUpshowBacHeight+"px");
			popUpShow.attr("style","margin-left:"+marginLeft+"px");
			//$("#popUpIfram").attr('src' ,request_url);
			if($.browser.version == "6.0"){
				setHeight = $(document.documentElement).height()+"px";
			}else{
				setHeight = '100%';
			}
			$("#popUpShowZ").html('');
			$("#popUpShowZ").append('<iframe width="100%" height="'+setHeight+'" allowtransparency="true" src="'+request_url+'" id="popUpIfram"></iframe>')
			$("#popUpShowZ").show();
			
			
			
			if($.browser.msie && (($.browser.version == "7.0") || ($.browser.version == "6.0"))){
				$('html').css({"overflow":"hidden"})
				
			}
			return ;

		});
		$("div.shopDetail").live("mouseover mouseout",function(event){
			if(event.type == "mouseover"){
				if($(this).attr('is_self')=='true'){
					$(this).find("div.shopDetailEditImg").show();
				}
			}else{
				$(this).find("div.shopDetailEditImg").hide();
			}
		})
	},
	shopDetailEditImg_live:function(){
		var self	= this;
		$(".shopDetailEditImg").live("click",function(){
			var url	= $(this).attr('request_url');
			if(url){
				self.__href(url);
			}
		});
		
	},
	__href:function(url){							// 页面跳转 与刷新网站
		window.location.href	= url;
	}
}



// 添加商品
var shopping_add	= {
	init:function (){
		$(".goods").show();
	}	
	
}





$(function() {
	var shoppingNew = new ShoppingClass();
	var isIE = !!window.ActiveXObject;
	var isIE6 = isIE&&!window.XMLHttpRequest;
	if(isIE){
		if(isIE6){
			DD_belatedPNG.fix('.shopChat','.shopCorner','.shopCircle','.divBar');
		}
	}

	shoppingNew.init();
	
	if($('.comment_easy').length>=1){
		var commentOptions={ 
			relayCallback:function (obj,_arg){
				var comment=new ui.Comment();
				comment.share(obj,_arg);//调用分享方法
			}
		};
		if(!com){
			var com = $('.comment_easy').commentEasy(commentOptions);
		}
	}
	
	
	shopping_add.init();
	
});



