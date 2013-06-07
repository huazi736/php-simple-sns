
/*
	Create 2012-7-26
	@ author sentsin(贤心)
	@ name 《首页 - 关注信息流 - 本地生活 模块》
	desc 
*/


var Class_follow_shopping_goods = (function(){
	var F = function() {};

	var template = [
		'<li commentObjId="${tid}" fid="${fid}" class="content" type="${type}" uid="${uid}" time="${ctime}">',
			'<div class="goodsHeader clearfix">',
				'<a title="${unname}" href="${web_url}">',
					'<img src="${headpic}" alt="" class="goodsHeaderImg"/>',
				'</a>',
				'<div class="goodsHeaderDetail">',
					'<div class="goodsHeaderDetailName"><a href="${web_url}" alt="">${uname}</a></div>',
					'<div class="goodsHeaderDetailFont">${friendly_time}发表了<a href="replace_mk_url_goods?web_id=${pid}&gid=${goods.gid}" class="agoods">新的商品</a></div>',					
				'</div>',
			'</div>',
			'<div class="goodsContent">',
				'<div class="goods_name">商品：<a href="replace_mk_url_goods?web_id=${pid}&gid=${goods.gid}" >${goods.goodsname}</a></div>',
				'<a title="" href="replace_mk_url_goods?web_id=${pid}&gid=${goods.gid}" >',
					'<img width="${goods.img_size[0].b.w}px" src="${goods.img[0]}"/>',
				'</a>',
				'<div class="cr"></div>',
				
				'<div class="goods_tag_name" >',
					'<div class="goodsFootPrice">',
						'<span class="goods_price" >售价￥<span class="goods_price_name" >${goods.saleprice}</span></span>',
					'</div>',
					'<a href="${goods.href}"><div class="goodsBuy">立刻购买</div></a>',
					'<div class="cr"></div>',
				'</div>',
				
			'</div>',
			'<div class="goodsFoot">',
        		'<div class="comment_easy" commentObjId="${goods.gid}" pageType="web_${type}" action_uid="${uid}" dkcode="${dkcode}" msgurl="" msgname="${goods.goodsname}"> </div>',
			'</div>',

		'</li>'
	].join("");
	

	
	F.prototype.init = function(data) {
		var goods_url	= mk_url('channel/goods/goods_show');
		var re			= /replace_mk_url_goods/g
		template		= template.replace(re,goods_url);
		
		try{
			if( cint(data.goods.img_size[0].b.w)>408 ){
				data.goods.img_size[0].b.w = 408
			}
		}catch(e){
			data.goods.img_size	= new Array();
			data.goods.img_size[0]	= new Object();
			data.goods.img_size[0].b	= new Object();
			data.goods.img_size[0].b.w = 408
		}
		
		var infoHtml 	= juicer(template, data);
		
		//replace_mk_url_goods
		return $(infoHtml); 
	};

	return F;
})();

function cint(value){						//  parseInt  转成数字  整型
	if( (!value))	return 0;
	var number	=  parseInt(value,10);
	if(isNaN(number)) return 0;
	return number;
}
