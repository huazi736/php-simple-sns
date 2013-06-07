/*
 * Created on 2011-09-22
 * @author: Yewang
 * @desc: 头部搜索结果页
 */

(function(){
//定义各种搜索列表结果模板
var TEMPLATE_PERSON = '<li class="searchLi newItem"><a href="{url}" class="headerImg" target="_blank"><img src="{img}" alt="" /></a><div class="searchDetail clearfix"><p class="searchCont"><a href="{url}" title="" target="_blank">{name}</a></p><div class="color666 _company">公司：<i>{company}</i></div><div class="color666 _school">学校：<i>{school}</i></div><div class="color666 _home">来自：<i>{home}</i></div><div class="color666 _address">现居：<i>{address}</i></div><div class="color666 {isHidden}"><i>有 <i>{common_friend_count}</i> 个共同好友</i></div></div><div rel="{relation}" uid="{uid}" class="statusBox {isHidden}"></div></li>',
	TEMPLATE_WEBPAGE = '<li class="searchLi clearfix"><a href="{url}" class="headerImg" target="_blank"><img src="{img}" alt="" /></a><div class="searchDetail clearfix"><p class="searchCont"><a href="{url}" title="{name}" target="_blank">{name}</a></p><div class="color666 {isTagHidden}">标签：<i>{tags}</i></div><div class="color666">粉丝：<i>{fans_count}</i></div></div><div class="relation relationWraps {isHidden}"><div webid="{web_id}" uid="{f_uid}" days="{relation_days}" dtype="{relation_type}" rel="{relation_relation}" class="statusBox"></div></div></li>',
	TEMPLATE_STATUS = '<li class="searchLi clearfix" id="{comment_id}" type="{share_type}" fid="{self_id}" uid="{user_id}"><a class="headerImg" href="{url}" title="{full_name}" target="_blank"><img alt="{full_name}" src="{img}" /></a><div class="searchDetail clearfix"><p class="searchCont"><a href="{url}" title="" target="_blank">{name}</a></p><p class="statusfont">{text}<br/><img src="{text_img}" rel={text_big_img} type={share_type}></p><p class="setTime">发布时间:&nbsp{time}</p></div><div class="clearfix"></div><div class="comment_easy" commentObjId="{comment_id}" pageType="{page_type}" action_uid="{user_id}" web_id="{web_id}" msgurl="123" msgname="123"></div></li>',
	TEMPLATE_PHOTO = '<li class="searchLi clearfix searchLiPhoto changeborder"><a rel={photo_id} uid={user_id} class="searchphotoimg photoclick" href="javascript:;" url="" picurl={url} pagetype={photo_type}><span class="popSpan"></span><span class="popSpanFont" title="{full_name}">{name}</span><div class="hideBg"><img src="{img}" alt="" /></div></a><div class="photoDetail clearfix"><a href="{author_url}" alt="" class="photoDetailImg" target="_blank"><img src="{author_img}" title=""/></a><div class="photoDetailFont"><b class="photoName"><a href="{author_url}" alt="" target="_blank">{author_name}</a></b><b class="photoTime">{time}</b></div></div></li>',
	TEMPLATE_ALBUM = '<li class="searchLi clearfix changeborder"><a class="searchphotoimg" href="{url}" target="_blank"><div class="hideBig"><img src="{img}" title="{full_name}"/></div></a><p title="{full_name}"><b>{name}</b><br />{count}</p></li>',
	TEMPLATE_VIDEO = '<li class="searchLi clearfix"><a href="{url}" class="headerImg headerImgVideo" target="_blank"><img src="{img}" alt="" /></a><div class="searchDetail clearfix"><p class="searchCont"><a href="{url}" title="{full_name}" target="_blank">{name}</a></p><p class="viodeDetail"><div class="color666">观看次数：<i>{view_times}</i></div><div class="color666">上传日期：<i>{time}</i></div><div class="color666">视频来源：<a href="{author_url}" target="_blank">{author_name}</a></div></p></div></li>',
	TEMPLATE_BLOG = '<li class="searchLi clearfix"><a href="{home_page}" class="headerImg" target="_blank"><img src="{author_img}" alt="" /></a><div class="searchDetail clearfix"><p class="searchCont"><a href="{home_page}" target="_blank">{author}</a><span>发表日志：</span><a href="{url}" title="{full_name}" target="_blank">{name}</a></p><p class="viodeContent">{text}</p><p class="viodeDetail"><a href="{url}" target="_blank">阅读更多↓</a>发布时间：<span>{time}</span></p></div></li>',
	TEMPLATE_ACTIVITY = '<li class="searchLi clearfix"><a href="{url}" class="headerImg headerImgVideo" target="_blank"><img src="{img}" width="133px" height="80px" alt="{full_name}" /></a><div class="searchDetail clearfix"><h5 class="searchCont"><a href="{url}" title="{full_name}" target="_blank">{name}</a></h5><div class="color666">时&nbsp;&nbsp;&nbsp;间：<i>{start_time} - {end_time}</i></div><div class="color666">发起人：<a href="{originatortor_url}" title="" target="_blank">{originator}</a></div><div class="color666 {isHidden}">简&nbsp;&nbsp;&nbsp;介：<i>{detail}</i></div></div><div class="fr"><div class="{statusCls}">{status}</div><div class="a-join-um">{join_num}人参与</div></div></li>';
var arg={};
var searchList = {
	init: function() {
		var user = $('#userResult div.statusBox');
		if(user[0]){
			user.relation();
		}

		this.more.init();//读取更多内容

	},
	more: {

		init: function() {
			var self = this,
				bindFormId = $("#searchResult");
			
			if($('#userResult')[0]){
				self.selectschool();
				self.bindForm(bindFormId);				
			};
			self.bindSelectColor();
			self.photo_album_event();
			$('div.moreResult').find('a').click(function() {
				
				var sendPara = self.sendParameter();
				
				var $this = $(this);
				$this.addClass('hide').next().removeClass('hide');						
				$.djax({
					type: 'POST',
					url: $this.attr('href'),
					data: sendPara/* {page: pager, app: type, term:keyword,college:college,company:company,province:province,local_address:local_address,middle_school:middle_school} */ ,
					dataType: 'json',
					success: function(data) {
						if(data.data && data.data.state === 1) {							
							self.appendMore(data.data.data, sendPara.app, $this.parent().prev());
							if(data.data.last === true) {
								$this.parent().remove();
							} else {
								$this.attr('rel', ++sendPara.page);
								if(searchList.scroll) {
									searchList.scroll = 0;
									$this.addClass('hide').next().removeClass('hide').parent().addClass('hide');
								} else {
									$this.removeClass('hide').next().addClass('hide');
								}
							}
							if($("div").is(".comment_easy"))
							{
				
								self.comment_photo_alert();
								self.status_photo_alert();
							}
							

						} else {
							alert('网络出错');
						}
						global.closeDropDown();
					}
				});
				return false;
			});
			//alert(self)
			if($('div.moreResult')[0] && $('#term').text() !== '全部')$(window).on('scroll', self.scroll);

			self.photo_alert();
			if($("div").is(".comment_easy"))
			{
				
				self.comment_photo_alert();
				self.status_photo_alert();
			}
			

			
		},
		//照片模块分享初始化
		comment_photos_alert:function(){
			var commentOptions={ 
				hasColl:0,
        				hasShare:1,
        				
				relayCallback:function (obj,_arg){
	    			var comment=new ui.Comment();
	    			comment.share(obj,_arg);//调用分享方法
				}
			};
			if(!com){
				var com = $('.comment_easy').commentEasy(commentOptions);

			}
		},
		//状态模块分享初始化
		comment_photo_alert:function(){
			var commentOptions={ 
				hasColl:0,
        				hasShare:1,
        				relay:1,
				relayCallback:function (obj,_arg){
	    			var comment=new ui.Comment();
	    			comment.share(obj,_arg);//调用分享方法
				}
			};
			if(!com){
				var com = $('.comment_easy').commentEasy(commentOptions);

			}
		},
		
		srolls_down:function(opts){
			var _self = this;
			var bar = $('div.moreResult');
			var  a = bar.find('a');
			bar.removeClass('hide').addClass('getting');
			var sendPara = _self.sendParameter();
					$.djax({
						url: a.attr('href'),
						type: 'POST',
						data: sendPara/* {page: pager, app: type, term:keyword,college:college,company:company,province:province,local_address:local_address,middle_school:middle_school} */,
						dataType: 'json',
						success: function(data) {
							if(data.data && data.data.state === 1) {
								searchList.more.appendMore(data.data.data, sendPara.app, bar.prev());
								a.attr('rel', ++sendPara.page);
								searchList.scroll++;
								
								if(data.data.is_end === true) {
									bar.remove();									
								} else {
									bar.addClass('hide').removeClass('getting');
									if(searchList.scroll === 2) {
										bar.removeClass('hide').find('a').removeClass('hide').next().addClass('hide');
									}
								}
								opts&&opts.callback && opts.callback();
								
							} else {
								alert('网络出错');
							}
							if($("div").is(".comment_easy"))
							{
				
								_self.comment_photo_alert();
								_self.status_photo_alert();
							}

						}
					});
		},
		scroll: function() {

			var _self = this;
			
			if(!searchList.scroll) {
				searchList.scroll = 0;
			}
			var bar = $('div.moreResult');
			if(bar[0] && !bar.hasClass('getting')) {
				var  a = bar.find('a'),
					wH = $(window).height(),
					sH = $(window).scrollTop(),
					bH = $('body').height();
				if(searchList.scroll < 2 && sH > 0 && sH > (bH - wH - 10)) {
					_self.searchList.more.srolls_down();
					
				}
			}

			
		},

		appendMore: function(json, type, ul) {
			var o = {
				data : json,
				ul : ul,
				tpl : '',
				dd : function(){}, //数据处理器
				extr : function(){} //扩展操作
			}
			this._setAppendConf(o, type);

			this._createSearchList(o);
			
		},
		_setAppendConf: function(o, type){
			switch (type) {
				case '1':
					o.tpl = TEMPLATE_PERSON;
					o.dd = function(d){
						d["isHidden"] = !!d.self ? "hide" : "show";
					};
					o.extr = function(ul){
						ul.find('li.newItem').removeClass('newItem').find('div.statusBox').relation();
					};
					break;
				case '2':
					o.tpl = TEMPLATE_WEBPAGE;
					o.dd = function(d){
						d["isTagHidden"] = $.trim(d.tags) == "" ? "hide" : "";
						d["relation_days"] = d.relation.days;
						d["relation_type"] = d.relation.type;
						d["relation_relation"] = d.relation.relation;
						d["isHidden"] = !!d.self ? "hide" : "";
					};
					o.extr = function(ul){
						$(".relationWraps", ul).each(function(){
							$(this).find('div.statusBox').webRelation();
						});
					};
					break;
				case '3':
					o.tpl = TEMPLATE_STATUS;

					break;
				case '4':
					o.tpl = TEMPLATE_PHOTO;
					break;
				case '5':
					o.tpl = TEMPLATE_ALBUM;
					break;
				case '6':
					o.tpl = TEMPLATE_VIDEO;
					o.dd = function(d){
						if(d.is_from_web){
							d.author_prefix = '来自网页:&nbsp;';
							d.author_suffix = '';
						}else{
							d.author_prefix = '由:&nbsp;';
							d.author_suffix = '上传';
						}
					};
					break;
				case '7':
					o.tpl = TEMPLATE_BLOG;
					break;
				case '9':
					o.tpl = TEMPLATE_ACTIVITY;
					o.dd = function(d){
						d["isHidden"] = $.trim(d.detail) == "" ? "hide" : "";
						switch (d.status) {
							case '即将开始':
								d["statusCls"] = "a-status-0";
								break;
							case '正在进行':
								d["statusCls"] = "a-status-1";
								break;
							case '已经结束':
								d["statusCls"] = "a-status-2";
								break;
							default:
								d["statusCls"] = "a-status-0";
								break;
						}
					};
					break;
			}
		},
		_createSearchList: function(conf){	
			var _self=this;		
			var lis = [];
			var data = conf.data;
			for(var i = 0, len = data.length; i < len; i++) {
				var d = data[i],_i = [];
				conf.dd(d); //数据处理器

				//for hide the null data's div 
				for( k in d){
					switch(k ){
						case 'company':
							!d[k] && _i.push('._company');
						break;
						case 'school':
							!d[k] && _i.push('._school');
							break;
						case 'home':
							!d[k] && _i.push('._home');
							break;
						case 'address':
							!d[k] && _i.push('._address');
							break;
						case  'default':
							break;

					}					
				}
				(function(j){				      	
				     if(j.length > 0){
				     	 $.each(j,function(a,b){
					      	var _tempStr,_index,_last,_len,_str1,_str2;
						 if($(conf.tpl).find(b).size() != 0){
						 	_tempStr = $(conf.tpl).find(b).html();
                             					_tempStr = '<div class="color666 '+ b.replace('.','') +'">'+_tempStr+'</div>';
							_index = conf.tpl.indexOf(_tempStr);
							_len = _tempStr.length;
							_last  = _index + _len;

							_str1 =  conf.tpl.substr(0, _index);	
							_str2 = conf.tpl.substring(_last);

							conf.tpl  = _str1 + _str2;	
						 }
				  	    });
				     }
				})(_i);

				lis.push( $.format( conf.tpl, d ) );
			}
			conf.ul.append(lis.join(""));

			conf.extr(conf.ul); //附加处理
			_self.photo_alert();
		},
		photo_album_event : function(){
			$('li.changeborder').live("hover",function(){
				$(this).attr("style","border:1px solid rgb(59,89,152)");
			});
			$('li.changeborder').live("mouseleave",function(){
				$(this).attr("style","border:1px solid #f7f7f7");
			});
			$('li.searchLiPhoto').live("hover",function(){
				$(this).find("span.popSpan").attr("style","display:block");
				$(this).find("span.popSpanFont").attr("style","display:block");
			});
			$('li.searchLiPhoto').live("mouseleave",function(){
				$(this).find("span.popSpan").attr("style","display:none");
				$(this).find("span.popSpanFont").attr("style","display:none");
			});
		},
		// 绑定学校,工作数据
		selectschool:function(){	
			var _self=this;	
			var bar = $('div.moreResult');
			var elm = $("div.selectButton").find("input"),
				url,
				text;
				currentCollegeCallback = function(title,id,i,t,pid){
				$(i).prev().show();
				searchList.more._userfilter();
				
			};
			var bindschool = function(name,url,text){
				$("div.selectButton").find("input[name='"+name+"']").selectSC({
					url: mk_url('main/search/popup',{'category':url}),
					popWdith: 730,
					popTitle: text,//弹出层的标题
					needClear : true,
					clearOnclick:function(){
						$(this).val(text);
						searchList.more._userfilter();
						$(window).off('scroll').on('scroll', _self.scroll);
						
							
						
					}
				 });
			};
			elm.each(function(){
				var inputName = $(this).attr("name");
				text = this.defaultValue;
				switch (inputName){
					case "college":
						url = 1;
						name = "college";
						bindschool(name,url,text);
						
						
						break;
					case "middle_school":
						url = 2;
						name = "middle_school";
						bindschool(name,url,text);
						
						break;
					case "company":
						url = 4;
						name = "company";
						bindschool(name,url,text);
						
						break;
				}
			});
			var hometownArea = new initAreaComponent('country,province,city,area','1-country,1-province,1-city,1-area','中国','a',false,'居住地');
			hometownArea.initalize();

			var hometownArea1 = new initAreaComponent('country0,province0,city0,area0','1-country0,1-province0,1-city0,1-area0','中国','a',false,'家乡');
			hometownArea1.initalize();

			$('div.selectButton select[name="province"],div.selectButton select[name="province0"]').change(function(){
				searchList.more._userfilter();
				

			});
		},
		/* 绑定表单方法 */
		bindForm:function(){
			var _self = this,
			college,company,province,local_adress,middle_school;
			var bindFormId = $("div.selectButton");
			
			$("#moreFilter").hide();
			bindFormId.find("li.clickMoreButton").toggle(function(){
				$("#moreFilter").show();
				$(this).find("i.clickmore").attr("style","background-position:0 0;");
			},function(){
				$("#moreFilter").hide();
				$(this).find("i.clickmore").attr("style","background-position:0 -9px;");
			});
		},
		sendParameter : function(){
			var _self = this,
				j = {"page":"","app":"","term":"","college":"","company":"","province":"","local_address":"","middle_school":""},
				bindFormId = $("div.selectButton"),
				bindFormIdSide = bindFormId.find("li");
			j.page = $("div.moreResult").find("a").attr("rel");
			j.app = $("div.moreResult").find("a").attr("name");
			j.term = $('#term').attr('name');
			if(bindFormIdSide.find("input[name='college']").val() == "请选择大学"){
				j.college = "";
			} else{
				j.college = bindFormIdSide.eq(0).find("input").val();
				
			}

			if(bindFormIdSide.eq(1).find("input").val() == "请选择工作单位"){

				j.company = "";
			}else{

				j.company = bindFormIdSide.eq(1).find("input").val();
			}

			if(bindFormIdSide.eq(2).find("option:selected").eq(1).text() == "居住地"){
				j.province = "";
			}else{
				j.province = bindFormIdSide.eq(2).find("option:selected").eq(1).text();
			}

			if(bindFormIdSide.eq(4).find("input").val() == "请选择高中"){
				j.middle_school = "";
			}else{
				j.middle_school = bindFormIdSide.eq(4).find("input").val();
			}

			if(bindFormIdSide.eq(5).find("option:selected").eq(1).text() == "家乡"){
				j.local_address = "";
			}else{
				j.local_address = bindFormIdSide.eq(5).find("option:selected").eq(1).text();
			}

			return j;
		},
		bindSelectColor : function(){
			
			var _self = this;
			var p_search=$("#term").attr("name");
			var p =$("#term").find("i").attr("class");
			var p_list=p.split(" ");

			$("#globalSearch").focus().val(p_search|| "");
			//$("#navSearch input[name='type']").val(p_search|| "");
			var listchange = $("ul.dropListul").find("li");
			
			switch(p_list[1])
			{
				case "people":
					var mark = "i_friend";
					break
				case "page":
					var mark = "i_page";
					break
				case "state":
					var mark = "i_status";
					break
				case "photo":
					var mark = "i_photo";
					break
				case "album":
					var mark = "i_album";
					break
				case "viode":
					var mark = "i_video";
					break
				case "blog":
					var mark = "i_blog";
					break
				case "act":
					var mark = "i_activity";
					break;
				
			}

			$("i."+mark+"").parent().parent().attr("style","background-color:#D8DFEA;");
			
			//返回顶部按钮
			var $backToTopTxt = "",$backToTopEle = $("<div class='backButton' style='display:none;'></div>").appendTo($("#globalSearchs")).text($backToTopTxt).attr("title",$backToTopTxt).click(function(){
				
				$("html,body").scrollTop(0);
			}),$backToTopFun = function(){
				var st = $(document).scrollTop(),winh = $(window).height();
				
				(st > 0)?$backToTopEle.show():$backToTopEle.hide();
			};
			$(window).bind("scroll",$backToTopFun);
		},
		_userfilter : function(){
			//发送数据准备
			$("div.moreResult").find("a").attr("rel",1); //设置为1
			var sendPara = searchList.more.sendParameter();
			sendPara.app = '1';
			sendPara.page = 0;

			$.ajax({
				url:mk_url('main/search/main', {"type":"people"})/*'main?type=people'*/,
				type:'POST',
				dataType:'json',
				data:sendPara,
				success:function(data) { 
					var d = data.data;
					$("#userResult").find("ul").find("li").remove();

					searchList.more.appendMore(d.data, sendPara.app, $("#userResult").find("ul"));

					$("ul.dropListul>li:eq(0) b").html(d.count);
					$("#term b").html(d.count);
				}
			});
		},
		//状态照片弹出渲染
		stats_photo_alert_view:function(arg){

			var pa_html='<div class="photo_alert_black"></div>'+
			'<div class="photo_alert_close"></div>'+			
			'<div class="photo_alert_shows"><div class="photo_alert_show">'+								
			'<span class="photo_img"><img alt="'+arg[1]+'" src="'+arg[0]+'"></span>'+
			
			
			'</div></div>';
			return pa_html;
		},
		//状态视频渲染
		stats_video_view:function(arg){

			var pa_html='<div id="'+arg[0]+'" style="width:400px;height:300px;"></div>';
			return pa_html;
		},
		//状态视频加载
		stats_video_init:function(arg){
			var _self=this;
			var video_html=_self.stats_video_view([arg[1]]);
			arg[0].after(video_html);
			arg[0].find("img").remove();
			arg[0].find(".search_video_but").remove();
			videoPlayer.AC_FL_RunContent({
					'appendTo': document.getElementById(arg[1]), //添加到的容器
					'wmode' : 'opaque',//默认为window
					'bgcolor': '#000000', //设置播放器背景色
					'movie': CONFIG['misc_path']+'flash/video/player.swf?mod=1&'+arg[2],
					'autoplay':'true',//设置是否自动播放
				});


		},
		//状态视频照片处理
		status_photo_alert:function(arg){
			var _self=this;
			var img_p=$(".statusfont");
			
			var img=img_p.find("img");
			img.mousemove(function(){
				if($(this).length!=0)
				{
					$(".search_video_but").remove();
					var img_type=$(this).attr("type");
					var img_url=$(this).attr("rel");
		 			
		 			var img_title=$.trim($(this).parent().parent().text());
					$(this).parent().off("click");
					if(img_type=="video"){
						var id=img_url.split("&")[0].split("=")[1];

						$(this).before('<a class="search_video_but"></a>');
						$(this).parent().on("click",function(){
							_self.stats_video_init([$(this),id,img_url]);
						});
					
					}else if(img_type=="photo")
					{
						$(this).parent().on("click",function(){		 				
		 					var html_status=_self.stats_photo_alert_view([img_url,img_title]);
		 					$("body").append(html_status);
		 					_self.photo_alert_close();
		 				})

					}
					
					
				}
			})

		},
		//照片模块弹出渲染
		photo_alert_view:function(arg){

			var pa_html='<div class="photo_alert_black"></div>'+
			'<div class="photo_alert_close"></div>'+			
			'<div class="photo_alert_shows"><div class="photo_alert_show">'+
			'<span class="photo_alert_arrow_left"></span>'+
			'<span class="photo_alert_arrow_right"></span>'+						
			'<span class="photo_img"><img alt='+arg[2]+' src='+arg[1]+' id='+arg[0]+'></span>'+
			'<span class="photo_author clearfix"><a href='+arg[6]+' target="_blank"><img alt='+arg[3]+' src='+arg[4]+'></a><span class="photo_author_right"><a href='+arg[6]+' target="_blank">'+arg[3]+'</a><b>'+arg[5]+'</b></span></span>'+
			'<span class="photo_alert_comment"><div class="comment_easy" commentObjId="'+arg[0]+'" pageType="'+arg[8]+'" action_uid="'+arg[7]+'" dkcode="'+CONFIG['dkcode']+'" msgurl="123" msgname="123"></div></span>'+							
			'</div></div>';
			return pa_html;
		},
		photo_alert_close:function(){
			$('.photo_alert_close').off('click');
			$('.photo_alert_close').on('click',function(){
				
				$(".photo_alert_black").remove();
				$(".photo_alert_close").remove();
				$(".photo_alert_shows").remove();
				$("body").attr("style","overflow-y:auto");
			});
		},
		photo_alert_div:function(but){
				var _self=this;
				var html="";
				var pic_name=but.parent().find(".popSpanFont").html();
				var author_name=but.parent().find("div.photoDetail").find("b.photoName").find("a").html();
				var author_time=but.parent().find("div.photoDetail").find("b.photoTime").html();
				var author_img=but.parent().find("div.photoDetail").find("a.photoDetailImg").find("img").attr("src");
				var author_url=but.parent().find("div.photoDetail").find("b.photoName").find("a").attr("href");

				html=_self.photo_alert_view([but.attr("rel"),but.attr("picurl"),pic_name,author_name,author_img,author_time,author_url,but.attr("uid"),but.attr("pagetype")]);
				
				$(".photo_alert_black").remove();
				$(".photo_alert_close").remove();
				$(".photo_alert_shows").remove();
				$("body").attr("style","overflow-y:auto");
				$("body").append(html);
				$("body").attr("style","overflow-y:hidden;");
		},
		photo_alert:function(){
			var _self=this;
			$('.photoclick').off('click');
			$('.photoclick').on('click',function(){
				
				var but=$(this);	
				_self.photo_alert_div(but);
				_self.photo_alert_close();
				_self.comment_photos_alert();
				_self.photo_right_left_click();
				
			});
			
			
		},
		photo_right_left_click:function(){
			var _self=this;
			$(".photo_alert_arrow_right").on("click",function(){
				
				var but=$(this);
				var now_img_id=but.next().find("img").attr("id");

				var buts=$("a[rel="+now_img_id+"]").parent().next().find("a.searchphotoimg");
				var now_index=$("a[rel='"+now_img_id+"']").parent().index();
				var right_index=now_index+1;
				
				
				if(typeof buts.attr("rel")=="undefined")
				{
					
					_self.srolls_down({
					            callback:function(){
							buts=$("a[rel="+now_img_id+"]").parent().next().find("a.searchphotoimg");
							if(typeof buts.attr("rel")!="undefined")
							{
								_self.photo_alert_div(buts);
					
								_self.photo_right_left_click();

								if($("#div").is("comment_easy"))
							{
								_self.comment_photos_alert();
							}
								return;
							}
							if($("#div").is("comment_easy"))
							{
								_self.comment_photos_alert();
							}

												
						}
					});	
					
				}else{					

					_self.photo_alert_div(buts);
				
					_self.photo_right_left_click();
	
					}
				
				_self.photo_alert_close();
				if($("#div").is("comment_easy"))
							{
								_self.comment_photos_alert();
							}

			});
			$(".photo_alert_arrow_left").on("click",function(){

				var but=$(this);

				var now_img_id=but.next().next().find("img").attr("id");

				var now_index=$("a[rel='"+now_img_id+"']").parent().index();
				var left_index=now_index-1;
				if(left_index!=-1)
				{
					var buts=$("a[rel='"+now_img_id+"']").parent().parent().find("li:eq("+left_index+")").find("a.searchphotoimg");
					
					_self.photo_alert_div(buts);
					_self.photo_right_left_click();
				}				

				_self.photo_alert_close();
				if($("#div").is("comment_easy"))
							{
								_self.comment_photos_alert();
							}
				
			})

		}




	}
};


window.searchList = searchList;
})();

$(function(){
	searchList.init();
});