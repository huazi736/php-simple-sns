/**
 * @author:    liangshanshan
 * @created:   2012/03/7
 * @version:   v1.0
 * @desc:      关注及朋友头部导航，头像列表
 */

function CLASS_FOLLOW(){
	window["__score"] = "0";
	window["__lastid"] = "0";

	this.init();
}
CLASS_FOLLOW.prototype={
	init:function(){
		var self = this;

		// 分类菜单
		this.classPanel = $('<div class="classLstPanel"><ul class="clearfix"></ul></div>');
		this.classLst = this.classPanel.children("ul");
		//this.selectClassBtn = $("#selectClassBtn");
		this.classPanel.appendTo(document.body);


		this.$navWrapper = $("#navWrapper");	//关注大类
		this.$topAvatar = $("#topAvatar");		//关注的人or网页
		this.$timelineTree = $('#timelineTree');
		this.$timelinebody = this.$timelineTree.find("div.timelinebody");
		this.$timelineContent = $("#timelineContent");
		this.view('nav_list');
		this.action_uid = $("#action_uid").val();
		this.selfOther = false;//标识进入自己or别人页面
		this.hasFollow = false;//标识有无关注对象
		this.maypersonflag = true;

		flowLayout = new CLASS_FLOWLAYOUT({
			e:self.$timelinebody,
			url:mk_url("main/msgstreams/followstream"),
			data:{action_uid:self.action_uid},
			//LoadType:"prepend",
			success:function(data) {
				if (data.length == 0) {
					self.$timelinebody.html('<div class="noInfo"><div>暂无关注信息</div></div>');
				}
			}
		});
		flowLayout.init();
		HttpTimeContent(['fans','msg']);
	},
	view:function(method,arg){
		var self = this;
		var _class = {
			nav_list:function(){
				var classPanel = self.classPanel,
					classLst = self.classLst;
				var selectClassBtn = self.selectClassBtn;
				
				//classPanel.css({left:selectClassBtn.offset().left - 1,top:selectClassBtn.offset().top + 36});

				self.$navWrapper.hide();
				self.model('ask_nav',[function(json){//个人 相互关注 好友三大类
					if(json.state === 1) {
						var classData = json.data,
							len = classData.length,
							strArr = [];

						// 当json.self === 1 时表示当前访问本人的关注，否则为访问别人的关注
						if(json.self === 1) {
							self.selfOther = true;

							for(var i = 0; i < len; i ++) {
								strArr.push('<li class="' + classData[i].state + ' ' + classData[i].see + '"><a href="javascript:;">' + classData[i].content + '</a><s class="' + classData[i].classN + 's"></s><b></b></li>');
							}
							classLst.append(strArr.join(""));
						} else {
							if (len > 0) {
								for (var i = 0; i < len; i ++){
								 strArr.push('<li class="' + classData[i].state + ' ' + classData[i].see + '"><a href="javascript:;">' + classData[i].content + '</a><s class="' + classData[i].classN + 's"></s><b></b></li>');
								}
								str += '</ul>';
								self.$navWrapper.append(str);	
							}
						}

						// 获取所关注的网页类别
						self.model('web',[function(json){
							if (json.state === 1) {
								var webClassData = json.data,
									len = webClassData.length,
									classArr = [];
								//var $grayBar = $("#grayBar");

								//添加所关注的网页类别
								if (len > 0) {
									if(classLst.children().size() !== 0) {
										classArr.push('<li class="line"></li>');
									}

									for (var i = 0; i < len; i ++){
										classArr.push('<li class="web' + ' ' + webClassData[i].state + ' ' + webClassData[i].see + '"><a href="javascript:;">' + webClassData[i].content + '</a><s class="' + webClassData[i].classN + '"></s><b></b></li>');
									}
									classLst.append(classArr.join(""));
								}
								
								//用户没有关注任何类别（进他人页面，改用户隐藏了自己的关注时）
								if(classLst.children(":not(.line)").size() === 0) {
									classPanel.html("<h2 style='font-size:13px; text-align:center; line-height:30px;'>该用户还没有关注对象</h2>")
								} else {
									var navLi = classLst.children(":not(.line)");
									var fistLi = navLi.eq(0).addClass('on');
									//第一个关注的类别
									var p = fistLi.children('s').attr('class');
									//判断来自网页/个人
									var path = fistLi.hasClass('web') ? 'ask_person_web' : 'ask_person';

									selectClassBtn.text(fistLi.text());
									self.model(path,[{'f_id':parseInt(p),'action_dkcode':action_dkcode},function(data){//获得第一个关注类别下的头像
											self.view('person_list',[data,self.$topAvatar]);
										}
									]);
									self.event('personlist',navLi);//绑定获取头像列表
								}

								//如果自己绑定隐藏显示事件
								if(self.selfOther){
									self.event('see',classLst.find('li b'));
									self.event("hover",classLst.find("li:not(.line)"));
								}
							};
						}]);
						
						self.event("showClass",self.selectClassBtn.parent());
					} else {
						alert(json.msg);
					}


					// if(json.state == 1){
					// 	var data = json.data,
					// 		len = data.length,
					// 		str = '<ul id="grayBar" class="timeLineGrayBar grayBarClose clearfix">';
					// 	if(json.self == 1){//访问本人
					// 		self.selfOther = true;
					// 		for (var i=0;i<len;i++){
					// 			str +='<li class="'+ data[i].state+' '+data[i].see+'"><s class="'+data[i].classN+'s"></s>'+data[i].content+'<b></b></li>';
					// 		}

					// 		str +='</ul>';
					// 		self.$navWrapper.append(str);
					// 		self.$grayBar = $('#grayBar');
							
					// 		self.model('ask_person_default',[{'action_dkcode':action_dkcode},function(data){//获取个人
					// 			self.view('person_list',[data,self.$topAvatar]);
					// 		}]);
					// 	}else{//访问他人
					// 		if (len>0) {
					// 			for (var i=0;i<len;i++){
					// 			 str +='<li class="'+ data[i].state+' '+data[i].see+'"><s class="'+data[i].classN+'s"></s>'+data[i].content+'</li>';
					// 			}
					// 			str += '</ul>';
					// 			self.$navWrapper.append(str);	
					// 		}
					// 	}
						
					// 	self.model('web',[function(json){//所关注的网页类别
					// 		if (json.state==1) {
					// 			var data = json.data,
					// 				len = data.length,
					// 				str = '';
					// 			var $grayBar = $("#grayBar");

					// 			if (len > 0) {//添加所关注的网页类别
					// 				for (var i = 0; i < len; i ++){
					// 					str += '<li class="web' + ' ' + data[i].state + ' ' + data[i].see + '"><s class="' + data[i].classN + '"></s>' + data[i].content + '<b></b></li>';
					// 				}

					// 				if ($grayBar.length == 0) {//如果被访问者将他们的个人 相互关注 好友 三大关注类都隐藏
					// 					grayBar = $('<ul id="grayBar" class="timeLineGrayBar grayBarClose clearfix"></ul>');
					// 					grayBar.append(str);
					// 					self.$navWrapper.show().append(grayBar);
					// 					self.$grayBar = $('#grayBar');
					// 				}else{
					// 					$grayBar.append(str);
					// 				};


					// 				var width = 0;//自动换行显示
					// 				for(var i = 0, len = $grayBar.children().length; i < len; i ++){
					// 					width += $grayBar.children().eq(i).width()+20;//padding+border+margin-right=20px
						
					// 					if(width > 805){
					// 						if(i < len){
					// 							var more = len - i;
					// 							var li = '<li class="grayBarNum"><i></i><span>' + more + '</span></li>';
					// 							$grayBar.prepend(li);
					// 							self.event('opennav',[$grayBar.children('li:first'),i]);
					// 							$grayBar.children('li:gt('+i+')').hide();
					// 						}
					// 						break;
					// 					}
					// 				}
					// 			}

					// 			if ($grayBar.children().length == 0){//用户没有关注任何类别（进他人页面，改用户隐藏了自己的关注时）
					// 				self.$navWrapper.hide().next().hide().after('<div class="noInfo"><div>该用户还没有关注对象</div></div>');
					// 			}else{
					// 				if ($grayBar.find('.grayBarNum').length>0) {//关注类别超出一行
					// 					var navLi = $grayBar.children('li:gt(0)');
					// 				}else{
					// 					var navLi = $grayBar.children();
					// 				};

					// 				var fistLi = navLi.eq(0).addClass('on');
					// 				var p = fistLi.children('s').attr('class');//第一个关注的类别
					// 				var path = fistLi.hasClass('web')?'ask_person_web':'ask_person';//判断来自网页/个人
					// 				self.model(path,[{'f_id':parseInt(p),'action_dkcode':action_dkcode},function(data){//获得第一个关注类别下的头像
					// 						self.view('person_list',[data,self.$topAvatar]);
					// 					}
					// 				]);
					// 				self.event('hover',navLi);
					// 				self.event('personlist',navLi);//绑定获取头像列表
					// 			};

					// 			//如果自己绑定隐藏显示事件
					// 			if(self.selfOther){
					// 				self.event('see',self.$navWrapper.find('b'));
					// 			}	
					// 		};
					// 	}]);
						
					// }else{
					// 	alert(json.msg);
					// }
				},function(){}]);
			},
			person_list:function(arg){
				var conf = (function() {
					if(arg[2] && arg[2] === "web") {
						return {
							size:65,
							maxNum:8
						};
					} else {
						return {
							size:65,
							maxNum:12
						};
					}
				})();
				if(arg[0].state == 1){
					arg[1].parent('div').show();
					
					var data = arg[0].data;
					var len = data.list.length;
					//if(len > conf.maxNum){
						if (arg[1] == self.$topAvatar) {
							self.hasFollow = true;
						}
						var str = '';//<li id="mFollower" type="' + data.type + '" class="topAvatarNum outnumber"><a class="num" href="#"><i></i><span>' + (data.num - conf.maxNum) + '</span></a></li>';
							
						for(var i = 0; i < len && i < 51; i ++) {
							if(data.list[i]) {
								str += '<li><a href="' + data.list[i].href + '" title="' + data.list[i].name + '"><img src="' + data.list[i].src + '" width="' + conf.size + '" height="' + conf.size + '" alt="' + data.list[i].name + '" /></a></li>';
							}else{
								continue;
							}
						}
						if(arg[1].children()){
							arg[1].children().remove();
						}
						var div = arg[1].parent().find('.enterList');
						if(div){
							div.remove();
						}
						arg[1].prepend(str).find("a.num").show();
						/*arg[1].children('li:gt(' + conf.maxNum + ')').hide();
						var $div = '<div class="enterList"><a href="' + data.link + '">进入列表</a></div>';
						arg[1].after($div);*/

						var goLstBtn = $('<li style="background:#EEEFF4; height:' + conf.size + 'px; width:' + conf.size + 'px; line-height:' + conf.size + 'px; text-align:center;"><a target="_blank" href="' + data.link + '" style="height:' + conf.size + 'px; width:' + conf.size + 'px; display:block;">进入列表</a></li>');
						goLstBtn.appendTo(arg[1]);
						
						//self.event('askmore',arg[1].children('li').first(),conf.maxNum);
						//如果进入他人页面，默认展开他关注的对象
						if(!self.selfOther) {
							$('#mFollower').trigger('click');
						};
					// }else{
					// 	if(len !== 0){
					// 		if (arg[1]==self.$topAvatar) {
					// 			self.hasFollow=true;
					// 		}
					// 		$("#notip").remove();
					// 		var str = '<li class="topAvatarNum"><a style="display:inline-block; width:24px; height:50px;" href="' + data.link + '"></a></li>';
					// 		for(var i=0;i<len;i++){
					// 			str +='<li><a href="'+data.list[i].href+'" title="'+data.list[i].name+'"><img src="'+data.list[i].src+'" width="' + conf.size + '" height="' + conf.size + '" alt="' + data.list[i].name + '" /></a></li>';
					// 		}
					// 		if(arg[1].children()){
					// 			arg[1].children().remove();
					// 		}
					// 		var div =arg[1].parent().find('.enterList');
					// 		if(div){
					// 			div.remove();
					// 		}
					// 		arg[1].prepend(str);
					// 		self.event('tip',arg[1].find('li.topAvatarNum'));

					// 	//没有关注
					// 	}else{
					// 		arg[1].parent('div').hide();
					// 		if (arg[1]==self.$topAvatar) {
					// 			self.hasFollow=false;//没有关注对象
					// 			$('#grayBar').css('margin-bottom','0px');
					// 			self.$timelinebody.html('<div id="notip" class="noInfo"><div>您还没有关注对象</div></div>');
					// 			if (self.selfOther==false) {
					// 				$('#grayBar').after('<div id="notip" class="noInfo"><div>该用户还没有关注对象</div></div>');
					// 			};
								
					// 		};							
					// 	}
				
					// }
				}
			}
		};
		return _class[method](arg);
	},
	event:function(method,dom){
		var self = this;
		var _class ={
			hover:function(dom){
				dom.each(function(){
					$(this).hover(
						function(){
							$(this).addClass("cur hov");
						},
						function(){
							$(this).removeClass("cur hov");
						}
					);
					$(this).find("b").click(function(){
						$(this).toggleClass("hid");
					});
				});
			},
			opennav:function(dom){
				dom[0].bind('click',function(){
					self.$grayBar = $('#grayBar');
					self.$grayBar.toggleClass("grayBarClose");
					if(self.$grayBar.hasClass("grayBarClose")){
						$(this).find("span").show().siblings("i").css({"background-position":"-32px 0","margin-right":"0"});
						self.$grayBar.children('li:gt('+dom[1]+')').hide();
					}else{
						$(this).find("span").hide().siblings("i").css({"background-position":"-48px 0","margin-right":"9px"});
						self.$grayBar.children().show();
					}
				});
			},
			personlist:function(dom){
				dom.each(function(){
					$(this).find('a').attr('href', 'javascript:void(0);');
					$(this).click(function(ev){
						ev = window.event || ev;
						var target = ev.srcElement || ev.target;

						if(target.tagName.toLowerCase() !== "a") {
							return;
						}

						$(this).addClass('on').siblings().removeClass('on');
						var p = $(this).children('s').attr('class');
						//判断来自网页/个人
						var path = $(this).hasClass('web') ? 'ask_person_web':'ask_person';
						//获得关注头像
						self.model(path,[{'f_id':parseInt(p),'action_dkcode':action_dkcode},function(data){
							var argArr = [];
							argArr = (path === "ask_person_web") ? [data,self.$topAvatar,"web"] : [data,self.$topAvatar];
							self.view('person_list',argArr);
							
							if (self.selfOther) {//加载信息流
								switch (p){
									case '0s':	//个人
										if (self.hasFollow) {//若有关注对象则请求信息流
											self.$timelinebody.children().remove();
											flowLayout = new CLASS_FLOWLAYOUT({
												e:self.$timelinebody,
												url:webpath + "main/index.php?c=msgstreams&m=followstream",
												data:{action_uid:self.action_uid},
												success:function(data){
													if (data.status == 0 || data.data.length == 0) {
														self.$timelinebody.html('<div class="noInfo"><div>暂无关注信息</div></div>');
													}else{
										
													};
												}
											});
											flowLayout.init();
										}	
										HttpTimeContent(['fans','msg']);
										break;
									case '1s'://相互关注
										if (self.hasFollow) {
											self.$timelinebody.children().remove();
											flowLayout = new CLASS_FLOWLAYOUT({
												e:self.$timelinebody,
												url:webpath+"main/index.php?c=msgstreams&m=followMutualStream",
												data:{action_uid:self.action_uid},
												success:function(data){

													self.$timelineContent.find(".noInfo").remove();
													if (data.status==0||data.data.length==0) {
														self.$timelinebody.html('<div class="noInfo"><div>暂无关注信息</div></div>');
													}else{
													
													};
												}
											});
											flowLayout.init();
										}
										HttpTimeContent(['fans_both','msg']);

										break;
									case '2s'://好友
										if (self.hasFollow) {
											self.$timelinebody.children().remove();
											flowLayout = new CLASS_FLOWLAYOUT({
												e:self.$timelinebody,
												url:webpath+"main/index.php?c=msgstreams&m=followFriStream",
												data:{action_uid:self.action_uid},
												success:function(data){
													self.$timelineContent.find(".noInfo").remove();
													if (data.status==0||data.data.length==0) {
									
														self.$timelinebody.html('<div class="noInfo"><div>暂无关注信息</div></div>');

													}else{
													};
												}					
											});
											flowLayout.init();
										}
										HttpTimeContent(['fans_fris','msg']);

										break;
									default://网页信息流	
										if (self.hasFollow) {
											self.$timelinebody.children().remove();
											flowLayout = new CLASS_FLOWLAYOUT({
												p:'web_comment',
												e:self.$timelinebody,
												url:webpath+"main/index.php?c=webstreams&m=msgActionCate",
												data:{action_uid:self.action_uid,tagid:p},
												success:function(data){
													self.$timelineContent.find(".noInfo").remove();
													if (data.status == 0 || data.data.length == 0) {
													
														self.$timelinebody.html('<div class="noInfo"><div>暂无关注信息</div></div>');

													}else{
													};
												}
											});
											flowLayout.init();
										}
										HttpTimeContent(['','web',p,self.action_uid]);

								}
							};
						}]);
						
						self.classPanel.hide();
						self.selectClassBtn.text($(this).text());
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
			askmore:function(dom,maxNum){
				maxNum = parseInt(maxNum) || 11;
				dom.toggle(function(){
					$(this).addClass('unfold');
					$(this).closest('ul').children().css('padding-bottom','0px').show();
					$(this).closest('ul').next('div').show();
				},function(){
					$(this).removeClass('unfold');
					$(this).closest('ul').children().css('padding-bottom','0px');
					$(this).closest('ul').children('li:gt(' + maxNum + ')').hide();
					$(this).closest('ul').next('div').hide();
				});
			},
			see:function(dom){
				dom.click(function(e){
					e.stopPropagation();
					var type = $(this).prev().attr('class');
					var _this = $(this);
					var hid ='';
					var path = $(this).closest('li').hasClass('web') ? 'Web' : '';
					if ($(this).closest('li').hasClass('hid')){
						hid ='un';
					}
					
					self.model('see',[{'f_id':type},function(data){
						if(data.state == 1){
							var li = _this.closest('li');
							if(li.hasClass('hid')){
								li.removeClass('hid');
							}else{
								li.addClass('hid');
							}
						}
					},hid,path]);
				});
			},

			// 显示分类菜单
			showClass:function(doms) {
				var classPanel = self.classPanel,
					selectClassBtn = self.selectClassBtn;
				var showFlag = false;

				doms.each(function(index,val) {
					var $dom = $(this);

					$dom.hover(
						function() {
							var _offset = selectClassBtn.parents("div.modlueHeader").offset();

							classPanel.show().css({left:selectClassBtn.offset().left - 9,top:_offset.top + 51});
						},
						function() {
							window.setTimeout(function() {
								if(!showFlag) {
									classPanel.hide();
									showFlag = false;
								}
							},300);
						}
					);
				});

				classPanel.hover(
					function() {
						showFlag = true;
						classPanel.show();
					},
					function() {
						showFlag = false;
						classPanel.hide();
					}
				);
			}
		};
		return _class[method](dom,arguments[2]);
	},
	model:function(method,arg){
		var self = this;
		var _class={
			ask_nav:function(arg){//获取导航个人，相互关注，好友
				$.ajax({
					data:{'action_dkcode':CONFIG['action_dkcode']},
					url:mk_url('main/following/getFollowingCategory'),
					dataType:"json",
					success:arg[0],
					error:'',
					cache:false
				});
			},
			ask_person_default:function(){//初始页面加载头像加载
				$.ajax({
					url:mk_url('main/following/getFollowingList'),
					data:arg[0],
					dataType:"json",
					success:arg[1],
					error:'',
					cache:false
				});
			},
			ask_person:function(){//获取个人头像
				$.ajax({
					url:mk_url('main/following/getFollowingByType'),
					data:arg[0],
					dataType:"json",
					success:arg[1],
					error:'',
					cache:false
				});
			},
			ask_person_web:function(){//获取网页头像
				$.ajax({
					url:mk_url('main/following/getWebFollowingByCategoryId'),
					data:arg[0],
					dataType:"json",
					success:arg[1],
					error:'',
					cache:false
				});
			},
			see:function(arg){//是否对他人可见
				$.ajax({
					url: mk_url('main/following/' + arg[2] + 'hidden' + arg[3] + 'FollowingCategory'),
					data:arg[0],
					dataType:"json",
					success:arg[1],
					error:'',
					cache:false
				});
			},
			web:function(){
				$.ajax({
					data:{'action_dkcode':action_dkcode},
					url: mk_url('main/following/getWebFollowingCategory'),
					dataType:"json",
					success:arg[0],
					error:"",
					cache:false
				});
			}
		};
		return _class[method](arg);
	}
}

$(function(){
	new CLASS_FOLLOW();
});

var setIntervalArr=[];
function HttpTimeContent(arg){//arg为数组，arg[1]标识个人/网页（msg/web）
	
	$('#timecount').html(0);//初始化
	$('#moredata').hide();

	clearInterval(setIntervalArr[0]);//切换关注类别时候清除定时请求
	setIntervalArr.shift();

	var setIntervalEvent = setInterval(function(){
		 var time = parseFloat($('#moredata').attr('ctime'));
		 var data = (arg[1]=='msg'? {"msgtype":arg[0],"ctime":time}:{"tagid":arg[2],"action_uid":arg[3],"ctime":time});
	     $.ajax({
	     	type:'post',
	     	url:mk_url("main/"+arg[1]+"streams/getTimeCount"),
	     	dataType:'json',
	     	data:data,
	     	success:function(result){
		        if(result.status==1){
		            $('#moredata').attr('ctime',result.ctime); //更改这个ctime时间
		            if(result.count!=0 && result.count!=null){
		                if($('#moredata').css('display')=='block') {//如果是显示状态
		                    //数字应该等于显示的加上传过来的
		                    $('#timecount').html(parseInt($('#timecount').html()) + parseInt(result.count));//设置里面的内容 为这个数字  
		                }else{
			                $('#timecount').html(result.count);//设置里面的内容 为这个数字
				            $('#moredata').show();//显示 这个
		                }
		        	}
		        }
		     }
	     });

	},30000);

	setIntervalArr.push(setIntervalEvent);

    $('#moredata a').unbind("click").bind("click",function(){//清除上次点击广播事件
    	$('#moredata').hide();  //隐藏域
    	var $noInfo = $('#timelineTree').find('.noInfo');
    	if ($noInfo.length>0) {//初始页面为 暂无信息
    		$noInfo.remove();
    	};
        var time = parseFloat($('#moredata').attr('ltime'));
        var data = (arg[1]=='msg'?{"msgtype":arg[0],"ltime":time}:{"tagid":arg[2],"action_uid":arg[3],"ltime":time});     
    	$.ajax({
    		type:'post',
    		url:webpath+"main/index.php?c="+arg[1]+"streams&m=getTimeContent",
    		dataType:'json',
    		data:data,
    		success:function(result){
		        if(result.status==1){
		            $('#moredata').attr('ltime',result.ltime); //更改ltime
	                $('#timecount').html('0');//设置这个域数字为0
		            flowLayout.LoadType = 'prepend';
		            if(result.data != null) {
			           $.each(result.data,function(a,b){
							flowLayout.view([b.type],[flowLayout.$e,b,flowLayout.LoadType]);
						});
						flowLayout.plug(["commentEasy"],[$('#timelineTree').children("div.timelinebody")]);
						flowLayout.cpu('lay',[flowLayout.$e]);
			           flowLayout.LoadType = '';
			        }
		        }
		     }
    	});
        
    });
}