/**
 * @author:    liangshanshan
 * @created:   2012/03/7
 * @version:   v1.0
 * @desc:      关注及朋友头部导航，头像列表
 */
function CLASS_FOLLOW(){
	this.init();
}
CLASS_FOLLOW.prototype={
	init:function(){
		var self = this;
		this.dk = $('#dk_code').attr('dk');
		this.$headerWrapper = $("#headerWrapper");
		this.$timeLineTopAvatar = $(".timeLineTopAvatar");
		this.$timelineContent = $("#timelineContent");
		this.model('ask_person',[{'action_dkcode':self.dk},function(data){
			self.view('person_list',data);
		}]);
		this.$timelineTree = $('#timelineTree');
		this.$timelinebody = $('#timelineTree').children("div.timelinebody");
		this.plug(["msg"],[$("#distributeMsg")]);
	},
	view:function(method,arg){
		var self = this;
		var _class={
			
			person_list:function(data){
				if (data.state==1) {
					var data = data.data;
					self.$headerWrapper.show();
					if(data.num<=15){						
						if(data.list.length!=0){
							var str = '<ul id="topAvatar" class="topAvatarClose clearfix"><li class="topAvatarNum "><a style="display:inline-block; width:24px; height:50px;" href="'+data.link +'"></a></li>';
							for(var i=0,len=data.list.length;i<len;i++){
								str +='<li><a href="'+data.list[i].href+'" title="'+data.list[i].name+'"><img src="'+data.list[i].src+'" width="50" height="50" alt="" /></a></li>';
							
							}
							str +='</ul>';
							self.$headerWrapper.append(str);
							self.$topAvatar = $('#topAvatar');
							self.event('tip',$('#topAvatar').find('li.topAvatarNum'));
						}else{
							self.$headerWrapper.hide();
							self.$timelineContent.hide().after('<div style="margin-top:10px;background:#fff; border:1px solid #cbd2e3; text-align:center; padding-top:98px; padding-bottom:92px;"><div style=" line-height:30px; height:30px; font-size:14px; background:#eeeff4; border-top:1px solid #cbd2e3; border-bottom:1px solid #cbd2e3;">您还没有好友</div></div>');
						}
					}else{
						var str = '<ul id="topAvatar" class="topAvatarClose clearfix"><li type="'+data.type+'" class="topAvatarNum outnumber"><a  class="num" href="#"><i></i><span>'+(data.num-15)+'</span></a></li>';
						for(var i=0;i<15;i++){
							str +='<li><a href="'+data.list[i].href+'" title="'+data.list[i].name+'"><img src="'+data.list[i].src+'" width="50" height="50" alt="进入列表" /></a></li>';
						}
						self.$headerWrapper.prepend(str).find("a.num").show();
						self.$topAvatar = $('#topAvatar');
						self.event('askmore',$('#topAvatar').children('li').first());
					}
				}else{
					self.$headerWrapper.prepend(data.msg);
					return false;
				};
			},
			more_person:function(data){
				var data = data.data;
				var str = '';
				for(var i= 0,len=data.list.length;i<len;i++){
					str +='<li><a href="'+data.list[i].href+'" title="'+data.list[i].name+'"><img src="'+data.list[i].src+'" width="50" height="50" alt="" /></a></li>';
				}
				self.$topAvatar.append(str);
				var div='<div class="enterList"><a href="'+data.link+'">进入列表</a></div>';
				self.$topAvatar.after(div);
				self.event('showmore',self.$topAvatar.children('li').first());
			}				
		};
		return _class[method](arg);
	},
	plug:function(method,dom){
		var self = this;
		var _class = {
			msg:function(arg){
                arg[0].find("[msg]").msg();
            }
		}
		return _class[method](dom);
	},
	event:function(method,dom){
		var self = this;
		var _class ={			
			personlist:function(dom){
				dom.each(function(){
					$(this).click(function(){
						$(this).addClass('on').siblings().removeClass('on');
						var p = $(this).children('s').attr('class');
						self.model('ask_person',[{'action_dkcode':self.dk}]);
					});
				});
			},
			tip:function(dom){
				dom.hover(function(){
					$(this).addClass("topAvatarHov").attr("title","进入列表");
				},function(){
					$(this).removeClass("topAvatarHov");
				});
			},
			askmore:function(dom){//第一次点击更多异步请求数据
				dom.one('click',function(){
					var type = $(this).attr('type');
					$(this).addClass('unfold');
					self.model('ask_more',[{'action_dkcode':self.dk,'page':2},function(data){
						self.view('more_person',data);
					}]);
				});
			},
			showmore:function(dom){//显示隐藏已经在本地的更多数据
				dom.toggle(function(){
					$(this).removeClass('unfold');
					$(this).closest('ul').children('li:gt(15)').hide();
					$(this).closest('ul').next('div').hide();					
				},function(){
					$(this).addClass('unfold');
					$(this).closest('ul').children().show();
					$(this).closest('ul').next('div').show();					
				});	
			}
		};
		return _class[method](dom);
	},
	model:function(method,arg){
		var self = this;
		var _class={
			ask_person:function(){
				$.ajax({
					type:'post',
					url:mk_url('main/friend/friendindex'),
					data:arg[0],
					dataType:"json",
					success:arg[1],
					error:''
				});
			},
			ask_more:function(){
				$.ajax({
					type:'post',
					url:mk_url('main/friend/friendindex'),
					data:arg[0],
					dataType:"json",
					success:arg[1],
					error:''
				});
			}
		};
		return _class[method](arg);
	}
}

$(function(){
	new CLASS_FOLLOW();//好友头像列表
	flowLayout = new CLASS_FLOWLAYOUT({//信息流
		e:$("#timelineTree").children("div.timelinebody"),
		url:webpath+"main/index.php?c=msgstreams&m=friendstream",
		data:{action_uid:$("#action_uid").val()}
	});
	flowLayout.init();
	HttpTimeContent('fris');//新信息
	class_postBox = new CLASS_POSTBOX({
		_class:flowLayout,
		friend:true,
		box:$("#timelineTree").children("div.timelinebody")
	});//发表框
    class_postBox.init();
    $(".html_date").attr("disabled",true);
});

function HttpTimeContent(mt){
	setInterval(function(){
		 var time = parseFloat($('#moredata').attr('ctime'));
	     $.post(webpath+"main/index.php?c=msgstreams&m=getTimeCount",{"msgtype":mt,"ctime":time},function(result){
	        if(result.status==1){
	            $('#moredata').attr('ctime',result.ctime); //更改这个ctime时间
	        }
	        if(result.count!=0 && result.count!=null){
	            var check = result.count
	            ,l = check+1
	            ,topicId = [];

	            $('#timelineTree').children(".timelinebody").children('li:lt('+l+')').each(function(i){
	            	if(i>0){
	            		topicId.push($(this).attr('id'));
	            	};
	            });
	          	for (var i = 0,len = result.topicid.length; i < len; i++) {

	          		if (jQuery.inArray(result.topicid[i],topicId)!=-1) {//判断两数组是否有相同值，若不同返回-1，相同返回该值在topicId的索引
	          			--check;
	          		};
	          	};
	          	
                if (check) {
                	if($('#moredata').css('display') == 'block') {//如果是显示状态
                    //数字应该等于显示的加上传过来的
                    $('#timecount').html(parseInt($('#timecount').html()) + parseInt(check));//设置里面的内容 为这个数字
                
	                }else{
		                $('#timecount').html(check);//设置里面的内容 为这个数字
			            $('#moredata').show();//显示 这个
	                }
                };
                
	        }
	     },'json');
	},30000);

    $('#moredata a').click(function(){
    	 $('#moredata').hide();  //隐藏域
        var time = parseFloat($('#moredata').attr('ltime'));
        $.post(webpath+"main/index.php?c=msgstreams&m=getTimeContent",{"msgtype":mt,"ltime":time},function(result){
	        if(result.status == 1){
	            $('#moredata').attr('ltime',result.ltime); //更改ltime
                $('#timecount').html('0');//设置这个域数字为0
	           
	            flowLayout.LoadType = 'afterFirst';
	            if(result.data != null) {
		           var len = result.data.length
		           ,l = len+1
		           ,topicId = []//页面信息id
		           ,topicid = []//30000请求获得的信息id
		           ,check = [];

		           $('#timelineTree').children(".timelinebody").children('li:lt('+l+')').each(function(i){
		           		if (i>0) {
		           			topicId.push($(this).attr('id'));
		           		};
		           });
		           for(var i=0,len = result.data.length;i<len;i++){
		           		topicid.push(result.data[i].tid);
		           }
		           for (var i = 0; i < topicid.length; i++) {
		           		if (jQuery.inArray(topicid[i],topicId)==-1) {//检查数据是否已经在页面
		           			check.push(result.data[i]);
		           		};
		           };
		           
		           if (check.length) {
		           		$.each(check,function(a,b){
							flowLayout.view([b.type],[flowLayout.$e,b,flowLayout.LoadType]);
							flowLayout.cpu('lay',[flowLayout.$e]);
							flowLayout.plug(["commentEasy"],[$("#timelinebody")]);
						});
						flowLayout.LoadType = '';
		           };
		        }
	        }
	     },'json');
      
    });
}