/**
data : 2012/07/05
author : zhupinglei
desc : groupEvent.js
**/
function groupEvent(){
	this.init();
}
groupEvent.prototype = {

	init : function(){
		this.event();
		this.getList();
	},
	event : function(){
		var _this = this;

		//切换时加载列表
		$('#groupEvent').find('ul.eventLabel li').click(function(){
			if($(this).hasClass('allGroupEvent')){
				$('#doMoreList').attr({'eventtype':'grouplist','page':'2'}).parent().removeClass('hide');
			}else{
				$('#doMoreList').attr({'eventtype':'mylist','page':'2'}).parent().removeClass('hide');
			}

			$(this).addClass('hover').siblings().removeClass('hover');

			_this.getList();
		});

		//加载更多
		$('#doMoreList').click(function(){

			_this.doMoreList();

		});


		//显示删除
		$('#groupEvent').find('.groupEventList ul li').hover(function(){
			$(this).find('dt i').show();
		},function(){
			$(this).find('dt i').hide();
		});
		$('#groupEvent').find('.groupEventList ul li').find('dt i').hover(function(){
			$(this).addClass('hover');
		},function(){
			$(this).removeClass('hover');
		}).click(function(){
			if(confirm('是否删除?')){
				$(this).parents('li').remove();
			}
		});

		//查看活动
		$('#groupEvent').on('click','a.eventLink',function(){
			var viewEvent_url = $(this).attr('ref');
			$.ajax({
				url: viewEvent_url,
				type: 'post',
				dataType: 'jsonp',
				data: { },
				success: function(result){
					if(result){
						$(this).popUp({
							width : 620,
							title : "活动详情",
							content : '<div class="contentBox">'+ result.data +'</div>',
							mask : true,
							maskMode : false,
							buttons : '',
							callback : function(){}
						});

					}else{
						$.alert(result.info);
					}
				}	
			});

		});

		//创建活动弹窗
		$('#eventPub').click(function(){
			var gid = $('#group_id').val();
			$.ajax({
				url: mk_url('gevent/event/create',{gid:gid}),
				type: 'post',
				dataType: 'jsonp',
				data: { },
				success: function(result){
					if(result){
						$(this).popUp({
							width : 877,
							title : "创建活动",
							content : '<iframe src="" width="0" height="0" class="hide" name="nofreshFrame"></iframe><div class="contentBox">'+ result.data +'</div>',
							mask : true,
							maskMode : false,
							buttons : '',
							callback : function(){}
						});

					}else{
						$.alert(result.info);
					}
				}	
			});
			if(!CONFIG['local_run']){
				 document.domain = CONFIG['domain'].slice(1);
			}
			window.creatEventComplete = function (result){
				if(result.status){
					$('#popUp').css('width','620px').find('.contentBox').html(result.data);
				}else{
					$.alert('活动创建失败，请检查网络稍后再试！');
				}
			};


		});
	},
	getList: function(){

		var gid = $('#group_id').val(),
			getList_url  = mk_url('gevent/event/doMoreList',{gid:gid});
		var $eventBox = $('#groupEvent').find('div.groupEventList');
		var eventType = $('#doMoreList').attr('eventtype');

		$.ajax({
			url : getList_url,
			dataType : 'jsonp',
			data : { eventType:eventType },
			type : 'post',
			success : function(result){
				$eventBox.find('div.eventList').html('');

				if(result.status){
					$eventBox.find("div.eventLoading").removeClass("hide");
					var data = result.data;
					var str = "";
					for(var i=0,len=data.length; i<len; i++){
						str += data[i].list;
					}
					$eventBox.find('div.eventList').html('<ul>'+ str + '</ul');
					$eventBox.find("div.eventLoading").addClass("hide");
				}else{
					$.alert(result.info);
				}
			}
		});

	},
	doMoreList: function(){

		var gid = $('#group_id').val(),
			getList_url  = mk_url('gevent/event/doMoreList',{gid:gid});
		var $eventBox = $('#groupEvent').find('div.groupEventList');
		var eventType = $('#doMoreList').attr('eventtype'),
			page =  parseInt( $('#doMoreList').attr('page') );


		$.ajax({
			url : getList_url,
			dataType : 'jsonp',
			data : { eventType:eventType,page:page },
			type : 'post',
			beforeSend: function(XMLHttpRequest){
				$eventBox.find("div.eventLoading").removeClass("hide");
			},
			success : function(result){
				if(result.status){
					
					var data = result.data;
					var str = "";
					for(var i=0,len=data.length; i<len; i++){
						str += data[i].list;
					}
					$eventBox.find('div.eventList').append('<ul>'+ str + '</ul');
					$('#doMoreList').attr('page',++page);
					$eventBox.find('div.eventLoading').addClass('hide');

					if(result.isend == 1) $('#doMoreList').parent().addClass('hide');

				}else{
					$.alert(result.info);
				}
			}
		});


	}

}

$(document).ready(function(){
	new groupEvent();
});