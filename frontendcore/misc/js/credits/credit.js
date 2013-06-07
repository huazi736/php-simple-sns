/*
 * Created on 2012-07-6
 * @author: 罗豪鑫
 * @desc: 积分系统
 * @depends: jquery
 */

function creditAnimate(curCredit,total){
	var curExp = $('.expBar-cur'),
		totalExp = $('.expBar-total'),
		tipBox = $('.c-tip-box');
	curExp.animate({
		width:curCredit*(totalExp.width()/total)		
		},1000,function(){
			tipBox.css({
				left:curExp.width()-tipBox.innerWidth()/2+3  //'3'为小三角图标的一半宽度				
			});
			tipBox.fadeIn(600);
		});	
}

(function(){
	//排行榜		
	$.djax({
		url:mk_url('credit/credit/getRankingList'),
		type:'get',
		dataType:'json',
		loading:true,
		success:function(data){
			var ot = $('#ot'),
				ft = $('#ft'),
				at = $('#at'),
				follows = data.data.follows.users,
				friends = data.data.friends.users,
				all = data.data.all.users;												
			function setSort(elem,group){
				var len = group.length;			
				if (len == 0) {
					//无数据时的提示内容
					elem.find('.noTip').show();
				}
				else {	
					for (var i=0;i<len;i++) {		
						var item = '', e =	i + 1;;			
						item += '<li class="userTop-list-items clearfix">';
						item += '<div class="userTop-rank"><span class="top_'+ e +'">'+ e +'</span></div>';
						item += '<div class="userTop-user"><a class="uhref" href="'+ group[i].home +'" target="_blank"><img class="uface" alt="'+ group[i].uname +'" title="'+ group[i].uname +'" src="'+group[i].avatar+'" /></a></div>';
						item += '<div class="userTop-user-info">';
						item += '<div class="user-name">';
						item += '<a title="'+ group[i].uname +'" href="'+ group[i].home +'" target="_blank" class="userTop-user-name">' + group[i].uname + '</a>';
						item += '</div>';
						item += '<div class="dk-level mt7"><span class="dk-level-icon dk-level-lv'+ group[i].lv +'"></span></div>';
						item += '<div class="dk-user-credits"><p>积分:'+ group[i].c +'</p></div>';
						item += '</div></li>';	
						//清除无数据时的提示												
						elem.find('.noTip').remove();																																
						//生成条目
						elem.find('.userTop-list').children('ul').append(item);	
					}
				}				
			}		
			//相互关注
			setSort(at,follows);
			at.siblings('.updateTime').html('更新时间: '+ data.data.follows.time);				
			//好友
			setSort(ft,friends);
			ft.siblings('.updateTime').html('更新时间: '+ data.data.friends.time);
			//全站
			setSort(ot,all);
			ot.siblings('.updateTime').html('更新时间: '+ data.data.all.time);
		},
		error:function(){
			$('.noTip').html('数据加载错误').show();
		}
	});
	$('#usCredit').mouseover(function(){
		$(this).find('.tipsInfos').show();
	}).mouseleave(function(){
		$(this).find('.tipsInfos').hide();
	});
})()






