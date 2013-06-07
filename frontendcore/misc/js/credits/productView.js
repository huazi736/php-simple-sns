/*
 * Created on 2012-07-18
 * @author: 罗豪鑫
 * @desc: 积分系统-加载商品
 * @depends: jquery,scrollLoad
 */

//商品排序
function ProductView(){
	this.type = $('.tab_selected').attr('serial');
	this.init();
}
ProductView.prototype = {
	init:function(){
		this.loadpro(2,type);
		this.productStyle();
		this.productSort();		
	},
	productSort:function(){
		var actObj = $('.list_s'),
			box = $('.dropBox'),
			items = box.find('.list-items').children('li');
		actObj.click(function(){
			$(this).find(box).show();
		}).mouseleave(function(){
			$(this).find(box).hide();
		});
		items.hover(function(){
			$(this).addClass('hover_bcb');
		},function(){
			$(this).removeClass('hover_bcb');
		});		
	},
	setSort:function(e){
		var $this = $(e),
			st = $this.attr('s'),
			t = $('.tab_selected').attr('serial');
		$this.parent().parent().siblings('.list_selected').html($this.html());		
		//清空当前商品重新排序
		$('#products').find('.product').remove();
		this.loadpro(st,t);					
	},
	loadpro:function(sortType,type){
		var wrapp = $('#products'), tip = wrapp.find('#noProTip');
		if(tip.size() == 0){
			wrapp.scrollLoad({
				text:'',	
				url:mk_url('credit/product/sort'),
				data:{sortType:sortType,type:type},
				success:function(data){
					var totalHtml = '';
					$.each(data,function(key,val){			
						var html = '';		
							html += '<div class="product"><a class="product-img" href="'+ mk_url('credit/product/view', {pid:val['_id']['$id']}) +'">';
							html +=	'<img src="'+ imgHost + val['pic'] +'" title="'+ val['name'] + '" alt="' + val['name'] +'" /></a>';
							html +=	'<p class="product-name">'+val['name']+'</p>';
							html +=	'<p class="product-credit">积分兑换：<span>'+ val['credit'] +'</span>积分</p>';
							html +=	'<p class="product-level">兑换条件：<span>'+ val['condition'] +'</span>级</p>';
							html +=	'<p class="product-price">参考价：<span class="rmb">￥</span><span>'+ val['price'] +'</span></p>';
						html +=	'</div>';				
						totalHtml += html;	
					});
					//生成商品项		
					wrapp.append(totalHtml);			
				}
			});			
		}
	},
	productStyle:function(){
		var obj = $('.product');
		obj.live({
			mouseover:function(){
				$(this).addClass('pshover');
			},
			mouseout:function(){
				$(this).removeClass('pshover');
			}
		});		
	}
}

$(function(){
	var view = new ProductView();
	var items = $('.dropBox').find('.list-items').children('li');
	items.click(function(e){
		view.setSort(this)
	});
})
