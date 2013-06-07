/*
 * Created on 2012-3-6.
 * @name: flowlayout v1.0
 * @author: linchangyuan
 * @desc: $("#content").flowlayout({
	 		 direction:"left"
 		     //更多属性见 [default setting]
		  });
 */ 

function CLASS_FLOWLAYOUT(options){
	var self = this;
	var opts = $.extend({}, {
        p:null,					//commentEasy 接口路径
		e:null,
		direction:"sideLeft",	//默认布局方向
		url:""					//请求地址
		//success:function(){} 注释by梁珊珊
	}, options);
    this.comment_path = opts.p;
	this.$e = opts.e;
	this.opts = opts;
	this.page = 0;
	this.LoadType = opts.LoadType;
    this.hd_avatar = $("#hd_avatar").val();
    this.hd_UID = $("#hd_UID").val();
};
CLASS_FLOWLAYOUT.prototype = {
	init:function(){
		var self = this;
        
		self.$e.scrollLoad({
			data:self.opts.data,
			proxy:self,
			url:self.opts.url,
			success:function(data,proxy){
				proxy.event(["removeLoading"],[proxy.$e]);
				
				if(proxy.opts.success){
					proxy.opts.success(data);
				}
                if((data !== null) && (data.length > 0)){

                    $.each(data,function(a,b){

                        var loadType = proxy.LoadType || null;
                        var obj = proxy.view([b.type],[proxy.$e,b,loadType]);
                    });
                    
                    self.cpu('lay',[proxy.$e]);
                    self.plug(["commentEasy"],[proxy.$e,proxy.comment_path]);
                }else{
                    return;
                }
			}
		});
		
        self.event(["scrollToTop"],[$("#scrollToTop")]);
		
		/***视频播放***/

		//显示播放flash
		$("#timelineTree").on('click','div.media_prev',function(){
			var _self = this;
			
			//创建一个视频对象
			var videoController = new VideoController();
			//获取页面上的视频id
			var videoId = $(_self).next().children('div').attr('id');
			//获取视频其它参数，与id不在同一个div上
			//var videoSrc = $(_self).next().attr('videosrc');
			var fid = $(this).closest("[name='timeBox']").attr("fid");
			var videoWidth,videoHeight;
			
			if($(this).closest("li.twoColumn").size() != 0){
				videoWidth = 838;	//parseInt(videoDiv.attr('videowidth'));
				videoHeight = 600;
			}else{
				videoWidth = 401;	//parseInt(videoDiv.attr('videowidth'));
				videoHeight = 300;	//parseInt(videoDiv.attr('videoheight'));    //播放控制高度
			}
			//显示播放界面
			videoController.insertVideoToDom(videoId, fid, videoWidth, videoHeight,function(){
				$(_self).addClass('hide').siblings().removeClass('hide');           
			});
			//收起触发事件
			var $info_media_disp = $(_self).next();
			$info_media_disp.find('a.hideFlash').one('click', function() {
				$info_media_disp.addClass('hide').prev().removeClass('hide');
				videoController.deleteVideoFromDom();
			});
		});
		
		//播放器对象函数
		function VideoController() {
			this.currentVideoId = null;
			this.currentVideoParentDom = null;
			this.insertVideoToDom = function(_flashWrapId, _fid, _videoWidth, _videoHeight,_callfunc) {
				if(document.getElementById(_flashWrapId)) {
					this.currentVideoId = $("#" + _flashWrapId).closest("[type='video']").attr("fid") || _flashWrapId.toString().substring(0,10);
					AC_FL_RunContent(
						'codebase', 'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0',
						'width', _videoWidth,
						'height', _videoHeight,
						'src', 'player',
						'quality', 'high',
						'pluginspage', 'http://www.macromedia.com/go/getflashplayer',
						'align', 'middle',
						'play', 'true',
						'loop', 'true',
						'scale', 'showall',
						'wmode', 'opaque',
						'devicefont', 'false',
						'id', _flashWrapId,
						'bgcolor', '#000000',
						'name', 'player',
						'menu', 'true',
						'allowFullScreen', 'false',
						'allowScriptAccess','sameDomain',
						'movie', miscpath+'flash/video/player.swf?vid='+_fid+'&mod=1&uid='+document.getElementById('hd_UID').value,
						//'movie',  miscpath+'flash/video/player',
						//'FlashVars','url='+mk_videoUrl(_videoURL)+'&xml='+miscpath+'flash/video/path.xml',
						'allowFullScreen','true',
						'salign', '',
						'contentId',document.getElementById(_flashWrapId)
					); 
					if(_callfunc){
						_callfunc();
					}
				}
			}
			this.deleteVideoFromDom = function() {
				if(this.currentVideoId && this.currentVideoParentDom) {
					swfobject.removeSWF(this.currentVideoId);
					if(!document.getElementById(this.currentVideoId)) {
						var tempDom = document.createElement('div');
						tempDom.id = this.currentVideoId;
						this.currentVideoParentDom.appendChild(tempDom);
					}
				}
			}
		}
	},
	view:function(method,arg){
		var self = this;
		var append$str = function($str,panel,flag) {
			if(flag){
                if(flag == "prepend"){
                    panel.prepend($str);
                }
                if(flag == "afterFirst"){
                    panel.children().first().after($str);
                    //panel.children().last().before($str);
                }
            }else{
                panel.append($str);
                //panel.children().last().before($str);
            }
		};

		var _class = {
			blog:function(arg){
                var $content = arg[0],faceUrl;
				
                if(!arg[1].url){
                    faceUrl = $("#hd_avatar").val();
                }
				
                var theLast =$content.children("li[name='timeBox']").last();
                var sideClass,clickDown;
                //var location = webpath + "main/index.php?c=index&m=index&action_dkcode=" + arg[1].dkcode;
              	
                var location = mk_url("main/index/main",{"dkcode":arg[1].dkcode}); //modify 7.17
                // 小
				clickDown = "";
				sideClass = "sideLeft";
				
                var noForward = "",
					pageType;
                
				if(parseInt(arg[1].from) > 2){
                    noForward = "noForward=true"
                    pageType = "web_" + arg[1].type;
                }else{
                    pageType = arg[1].type;
                }
				
				var msgname=arg[1].title || "";
                var str = '<li name="timeBox" scale="true" id="' + arg[1].tid + '" fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + (arg[1].headpic || self.hd_avatar) + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a><span class="subTip">发表了<a href="' + arg[1].furl +'">博客</a></span></div><div class="postTime"><a href="#">' + arg[1].friendly_time + '</a><div class=""></div></div></div></div><div class="infoContent blog"><h3><a href="' + arg[1].url + '">' + arg[1].title + '</a></h3><p>' + arg[1].content + '<br /><a href="' + arg[1].url + '" class="readMore">继续阅读...</a></p></div><div noForward="true" class="commentBox pd" msgname="' + msgname + '" ctime="' + arg[1].ctime + '" commentObjId="' + arg[1].fid + '" pageType="' + pageType + '" action_uid="' + arg[1].uid + '"></div></div></li>';
				
                var $str = $(str);
                append$str($str, $content, arg[2]);
				/*if(arg[2]){
                    if(arg[2]=="prepend"){
                        $content.prepend($str);
                    }
                    if(arg[2]=="afterFirst"){
                        $content.children().first().after($str);
                    }
                }else{
                    $content.append($str);
                }*/
				
                return $str;
            },
            forward:function(arg){
                var $content = arg[0],faceUrl;
                
                if(!arg[1].url){
                    faceUrl = $("#hd_avatar").val();
                }
				
                var sideClass,clickDown,forwardContent = "";
                var location,albumText;
                var typeHtml='';

          
				// 小
				clickDown = "";
				sideClass = "sideLeft";
                
                var ftid = "",
					ftype = "";
					
				if(arg[1].forward&&arg[1].forward.length != 0){
                    ftid = arg[1].forward.tid;
                    ftype = arg[1].forward.type;
                    
                    //location = webpath + "main/index.php?c=index&m=index&action_dkcode=" + arg[1].forward.dkcode;
                    //modify 7.17 
                    location = mk_url("main/index/main",{"dkcode":arg[1].forward.dkcode});
                    
                    switch(arg[1].forward.type){
                        case "info":
                            forwardContent = '<div class="forwardContent"><span class="memo" style="margin:0">' + arg[1].forward.content + '</span></div>';
                            typeHtml = '分享了<span class="oldAuthorName" uid="' +arg[1].forward.uid+ '"><a href="' + location+'">' + arg[1].forward.uname + '</a></span>的状态</a>';
							break;
                        case "album":
                            var temp = "";
							
                            temp = '<ul class="photoContent clearfix">';
							
							if(arg[1].forward.photonum == "1" && parseInt(arg[1].forward.fid) > 10000000){
								albumText = "照片";
							}else{
								albumText = "相册";
							}
							if(arg[1].forward.from == "1"){
								albumText = "照片";
							}
							if(arg[1].forward.picurl){
								var hidden = "";
								var _height;
								
								$.each(arg[1].forward.picurl,function(i,v){
									var firstPhoto = "";
									var size = "_ts"

									if(i == 0){
										firstPhoto = "firstPhoto";
										size = "_tm";
										if(v.size){
											if(v.size.tm){
												_height = v.size.tm.h;
											}
										}
									}else{
										size = "_ts";
									}
									if(i==1){
										if(v.size){
											if(v.size.ts){
												_height=v.size.ts.h;
											}
										}
									}
								 
									if(i>3){
										hidden = "hide";
									}

									picurl = fdfsHost + "/" + v.groupname + "/" + v.filename + size + "." + v.type;
									temp += '<li class="' + firstPhoto + ' ' + hidden + '" style="height:' + _height + 'px"><a href="javascript:void(0);" url="' + webpath + 'single/album/index.php?c=index&m=photoInfo&photoid=' + v.pid + '&action_dkcode=' + arg[1].dkcode + '" class="photoLink"><img src="http://' + picurl + '" alt="" /></a></li>';
								});

								typeHtml = '分享了<span class="oldAuthorName" uid="' + arg[1].forward.uid + '"><a href="' + location + '">' + arg[1].forward.uname + '</a></span>的<a href="' + webpath+'single/album/index.php?c=index&m=photoLists&action_dkcode=' + arg[1].forward.dkcode + '&albumid=' + arg[1].forward.note + '">' + albumText + '</a>';
							}
							temp += '</ul>';
                            
							forwardContent = '<div class="forwardContent"><span class="memo" style="margin:0">' + arg[1].forward.content + '</span></div>' + temp;
                       		break;
                        case "video":
                            var temp = "";
                            temp='<div class="mediaContent"><div class="media_prev">';
                            if (arg[1].highlight==0||"") {
                                temp +='<img src="'+mk_videoImgUrl( arg[1].forward.imgurl)+'" alt="" width=403 height=300 />';
                            }else{
                                temp +='<img src="'+mk_videoImgUrl(arg[1].forward.imgurl)+'" alt="" width=838 height=600 />';
                            };

                            typeHtml = '分享了<span class="oldAuthorName" uid="'+arg[1].forward.uid+'"><a href="'+location+'">'+arg[1].forward.uname+'</a></span>的<a href="'+webpath+'/single/video/index.php?c=video&m=player_video&vid='+arg[1].forward.fid+'">视频</a>';

                            temp += '<a class="showFlash" href="javascript:void(0);"><img alt="" src="' + miscpath + 'img/system/feedvideoplay.gif"></a></div><div class="media_disp hide" videosrc="' + arg[1].forward.videourl + '"><div id="' + arg[1].forward.fid + '"></div></div></div>';
                             forwardContent = '<div class="forwardContent"><span class="memo" style="margin:0">' + arg[1].forward.content + '</span></div>' + temp;
                    	case "sharevideo":
	                        var temp = "";
	                        temp = '<div class="mediaContent" style="height:auto !important; height:80px; min-height:80px; width:128px;"><div class="media_prev" style="padding:0px 0px 10px 15px;">';

	                        temp += '<img src="' + mk_videoImgUrl(arg[1].forward.imgurl) + '" width="128" height="80" alt="" />';
	                        showFlashImgT = "29px";
	                        showFlashImgL = "67px";

	                        typeHtml = '分享了<span class="oldAuthorName" uid="' + arg[1].forward.uid + '"><a href="' + location + '">' + arg[1].forward.uname + '</a></span>分享的视频';

	                        temp += '<a class="showFlash" href="javascript:void(0);"><img alt="" src="' + miscpath + 'img/system/feedvideoplay_small.gif" style="height:23px;top:' + showFlashImgT + ';left:' + showFlashImgL + ';"></a></div><div class="media_disp hide" videosrc="' + arg[1].forward.videourl + '"><div id="' + arg[1].forward.fid + arg[1].tid + '"></div></div></div>';
	                        forwardContent = '<div class="forwardContent"><span class="memo" style="margin:0"><a href="' + arg[1].forward.videourl + '" target="_blank">' + arg[1].forward.content + '</a></span></div>' + temp;

                       	break;
                    }
                }else{
                    forwardContent = '<div class="forwardContent"></span>该信息已被删除！</div>';
                    typeHtml = "分享了一个信息";
                }
                
                //var location = webpath + "main/index.php?c=index&m=index&action_dkcode=" + arg[1].dkcode;
                var location = mk_url("main/index/main",{"dkcode":arg[1].dkcode});
				var web_id = null;
                if (arg[1].web_url) {//web_url
                    location = arg[1].web_url;
                    web_id = location.split('web_id=')[1];
                };
                var noForward="",
					pageType;
				
                if(parseInt(arg[1].from) > 2){
                    noForward = "noForward=true"
                    pageType = "web_" + arg[1].type;
                }else{
                    pageType = arg[1].type;
                }
                var str = '<li name="timeBox" scale="true" id="' + arg[1].tid + '"  fid="' + arg[1].fid + '" forwardId="' + ftid + '" forwardType="' + ftype + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + (arg[1].headpic||self.hd_avatar) + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a>';
                str += '<span class="subTip">' + typeHtml + '</span></div>';
				var msgname = arg[1].title || "";
                str += '<div class="postTime"><a href="#">' + arg[1].friendly_time + '</a><div class=""></div></div></div></div><div class="infoContent">' + arg[1].content + '</div>' + forwardContent + '<div class="commentBox pd" msgname="' + msgname + '" commentObjId="' + arg[1].tid + '" ctime="' + arg[1].ctime + '" web_id="' + web_id + '" pageType="' + pageType + '" action_uid="' + arg[1].uid + '"></div></div></li>';
                
                var $str = $(str);
				append$str($str, $content, arg[2]);
                /*if(arg[2]){
                    if(arg[2] == "prepend"){
                        $content.prepend($str);
                    }
                    if(arg[2] == "afterFirst"){
                        $content.children().first().after($str);
                    }
                }else{
                    $content.append($str);
                }*/
				
                return $str;
            },
            info:function(arg){
                var $content = arg[0],faceUrl;
				
                if(!arg[1].url){
                    faceUrl = $("#hd_avatar").val();
                }
                
                var sideClass,
					clickDown;

                var location = mk_url("main/index/main",{'dkcode':arg[1].dkcode});
                var web_id = null;
                
				if (arg[1].web_url) {//web_url
                    location = arg[1].web_url;
                    web_id= location.split('web_id=')[1];
                };
				
				// 小
				clickDown = "";
				sideClass = "sideLeft";
                
                if(arg[2]&&arg[2] == "afterFirst"){
                	sideClass = "sideRight";
                }
                var noForward = "",
					pageType;
				
                if(parseInt(arg[1].from) > 2){
                    noForward = "noForward=true";
                    pageType = "web_topic";
                }else{
                    pageType = "topic";
                }

				var msgname = arg[1].title || "";
                var str = '<li name="timeBox" scale="true" id="' + arg[1].tid + '" fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + (arg[1].headpic||self.hd_avatar) + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a></div><div class="postTime"><a href="#">' + arg[1].friendly_time + '</a><div class=""></div></div></div></div><div class="infoContent">' + arg[1].content + '</div><div class="commentBox pd" msgname="' + msgname + '" web_id="' + web_id + '" ctime="' + arg[1].ctime + '" commentObjId="' + arg[1].tid + '" pageType="' + pageType + '" ' + noForward + ' action_uid="' + arg[1].uid + '"></div></div></li>';
               
                var $str = $(str);
                append$str($str, $content, arg[2]);
				/*if(arg[2]){
                    if(arg[2] == "prepend"){
                        $content.prepend($str);
                    }
                    if(arg[2] == "afterFirst"){
                        $content.children().first().after($str);
                    }
                }else{
                    $content.append($str);
                }*/
				
                return $str;
            },
            event:function(arg){
            	var $content = arg[0],faceUrl;
                
				if(!arg[1].url){
                    faceUrl = $("#hd_avatar").val();
                }

                //var location = webpath + "main/index.php?c=index&m=index&action_dkcode=" + arg[1].dkcode;
                var location = mk_url("main/index/main",{"dkcode":arg[1].dkcode}); //modify 7.17
				if (arg[1].web_url) {//web_url
                    location = arg[1].web_url;
                };
                var sideClass,
					clickDown;

                if(arg[1].highlight == 0 || ""){
                    // 小
                    clickDown = "";
                    sideClass = "sideLeft";
                }else{
                    // 大
                    clickDown = "clickDown";
                    sideClass = "twoColumn clearfix";
                }
				
                if(arg[2] && arg[2] == "afterFirst"){
                	sideClass = "sideRight";
                }

               var str = '<li id="' + arg[1].tid + '" name="timeBox" scale="true"  fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + (arg[1].headpic || self.hd_avatar) + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a><span class="subTip">创建了<a href="' + arg[1].url + '">活动</a></span></div><div class="postTime"><a href="#">' + arg[1].friendly_time + '</a><div class=""></div></div></div></div><div class="infoContent"><a class="eventPic" href="' + arg[1].url + '"><img width="50" height="50" src="' + arg[1].photo + '" alt="" /></a><div class="eventInfo"><h4><a href="' + arg[1].url + '">' + arg[1].title + '</a></h4><span>' + arg[1].starttime + '</span></div></div></div></li>';
				
                var $str = $(str);
                append$str($str, $content, arg[2]);
				/*if(arg[2]){
                    if(arg[2]=="prepend"){
                        $content.prepend($str);
                    }
                    if(arg[2]=="afterFirst"){
                        $content.children().first().after($str);
                    }
                }else{
                    $content.append($str);
                }*/
                
				return $str;
            },
            album:function(arg){
                var $content = arg[0],faceUrl;
                
				if(!arg[1].url){
                    faceUrl = $("#hd_avatar").val();
                }
				
                var typeHref;
                var picurl;
                var sideClass,clickDown,commentObjId;
                var size,photoClass = "";

                //var location = webpath + "main/index.php?c=index&m=index&action_dkcode=" + arg[1].dkcode;
                var location = mk_url("main/index/main",{'dkcode':arg[1].dkcode});
				var web_id = null;
                var web_location = null;
                if (arg[1].web_url) {//web_url
                    location = arg[1].web_url;
                    web_id = location.split('web_id=')[1];
                    web_location = location.split('main')[0];
                };

                // 小
                clickDown = "";
                sideClass = "sideLeft";
               
                if(arg[2] && arg[2] == "afterFirst"){
                	sideClass = "sideRight";
                }

                var noForward = "", pageType;
                if(parseInt(arg[1].from) > 2){
                    noForward = "noForward=true";
                    pageType = "web_" + arg[1].type;
                }else{
                    pageType = arg[1].type;
                }

                var str = '<li name="timeBox" scale="true" id="' + arg[1].tid + '" type="album" highlight="' + arg[1].highlight + '" fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '"  album="403" time="' + arg[1].ctime + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + (arg[1].headpic || self.hd_avatar) + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a>';


                if(parseInt(arg[1].fid) > 10000000){
                    albumText = "照片";  
                    pageType = "photo";
                    commentObjId = arg[1].picurl[0].pid;
                }else{
                    albumText = "相册";
                    pageType = "album";
                    commentObjId = arg[1].fid;
                }
                if(arg[1].from == "1"){
                    pageType = "photo"
                }else{
                    //typeHref = webpath + "single/album/index.php?c=index&m=photoLists&action_dkcode=" + arg[1].dkcode + "&albumid=" + arg[1].note;
                    //modify 7.17
					typeHref = mk_url("album/index/photoLists",{'dkcode':arg[1].dkcode,'albumid':arg[1].note});
					

					str += '<span class="subTip">上传了<a href="' + typeHref+'">' + albumText + '</a></span>';
                }
                str += '</span></div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a>';
				if(parseInt(arg[1].from) > 2){
					pageType = "web_" + pageType;
				}
             	var ctime = arg[1].ctime;
             	var dateline = arg[1].dateline;

                var friendly_dateline = self.cpu(["returnFriendly_date"],[ctime,dateline]);
                if(friendly_dateline){
                    str += '<i class="insertTime tip_up_left_black" tip="' + friendly_dateline + '"></i>';
                }
               str += '<div class=""></div></div></div></div><div class="infoContent">' + arg[1].content + '</div><ul class="photoContent clearfix">';

                if(arg[1].picurl){
                    var hidden = "";
                    var _height;
                	$.each(arg[1].picurl,function(i,v){
                        if(i>3){
                            hidden = "hide";
                        }
                        var firstPhoto = "";
                        var width = "",
							height = "";
                        if(i == 0){
                            firstPhoto = "firstPhoto";
                            size = "_tm";
                            if(v.size){
                                if(v.size.tm){
                                    _height = v.size.tm.h;
                                }
                            }
                        }else{
                            size = "_ts";
                        }
                        if(i == 1){
                            if(v.size){
                                if(v.size.ts){
                                    _height=133;
                                }
                            }
                        }
						
                        picurl = fdfsHost + "/" + v.groupname + "/" + v.filename + size + "." + v.type;
                        //piclink = (web_id == null)? webpath + 'single/album/index.php?c=index&m=photoInfo&photoid=' + v.pid + '&action_dkcode=' + arg[1].dkcode : web_location + 'web/album/index.php?c=photo&m=get&photoid=' + v.pid + '&web_id=' + web_id;
						//modify 7.17
						piclink = (web_id==null) ? mk_url("album/index/photoInfo",{'photoid':v.pid,'dkcode':arg[1].dkcode}) : mk_url("album/photo/get",{'photoid':v.pid,'web_id':web_id});
                        
						str += '<li class="' + firstPhoto + ' ' + hidden + '" style="height:' + _height + 'px"><a href="javascript:void(0);" url="' + piclink + '" class="photoLink"><img src="http://' + picurl + '" alt="" /></a></li>'; //height="'+height+'" width="'+width+'" 删除 by李世君 2012-4-1
                    });
                }
				
				var msgname = arg[1].title || "";
                str += '</ul><div class="commentBox pd" msgname="' + msgname + '" commentObjId="' + commentObjId + '" ' + noForward + ' pageType="' + pageType + '" web_id="' + web_id + '" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div></div></li>';

                var $str = $(str);
                append$str($str, $content, arg[2]);
                /*if(arg[2]){
                    if(arg[2] == "prepend"){
                        $content.prepend($str);
                    }
                    if(arg[2] == "afterFirst"){
                        $content.children().first().after($str);
                    }
                }else{
                    $content.append($str);
                }*/
                
                return $str;
            },
            ask:function(arg){
                var $content = arg[0],faceUrl;
                if(!arg[1].url){
                    faceUrl = $("#hd_avatar").val();
                }
                var subtype = arg[1].subtype;
                var sideClass,clickDown;

                //var location = webpath+"main/index.php?c=index&m=index&action_dkcode="+arg[1].dkcode;
                var location = mk_url("main/index/main",{'dkcode':arg[1].dkcode}); //modify 7.17
				clickDown = "";
				sideClass = "sideLeft";
            
                if(arg[2]&&arg[2]=="afterFirst"){
                	sideClass = "sideRight";
                }

                var str = '<li name="timeBox" scale="true" id="' + arg[1].tid + '"  fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + (arg[1].headpic||self.hd_avatar) + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a> <span style="font-weight:normal">提出了问答</span></div><div class="postTime"><a href="#">' + arg[1].friendly_time + '</a><div class=""></div></div></div></div><div class="infoContent"><div class="askQuestion"><a href="' + arg[1].url + '">' + arg[1].question + '</a></div><ul class="answerlist">';
				
				if(arg[1].answerlist){
					$.each(arg[1].answerlist,function(i,v){
						if(i > 3){
							return false;
						}
						if(arg[1].style == "checkbox"){
							str += '<li><input type="checkbox" disabled="disabled" /> ' + v + '</li>';
						}else{
							str += '<li><input type="radio" disabled="disabled" /> ' + v + '</li>';
						}
					});
				}
				
				str += '</ul>';
				if(arg[1].answerlist && arg[1].answerlist.length > 4){
					str += '<div class="more">其他' + (arg[1].answerlist.length - 4) + '项</div>';
				}
				str += '</div></div></li>';
              	
                var $str = $(str);
                append$str($str, $content, arg[2]);
                /*if(arg[2]){
               		if(arg[2] == "prepend"){
						$content.prepend($str);
					}
					if(arg[2] == "afterFirst"){
						$content.children().first().after($str);
					}
				}else{
					$content.append($str);
				}*/
				
                return $str;
            },
            video:function(arg){
                var $content = arg[0],faceUrl;
                if(!arg[1].url){
                    faceUrl = $("#hd_avatar").val();
                }
                var subtype = arg[1].subtype;
                var sideClass,clickDown;
				
                //var location = webpath + "main/index.php?c=index&m=index&action_dkcode=" + arg[1].dkcode;
                var location = mk_url("main/index/main",{'dkcode':arg[1].dkcode}); //modify 7.17
				var web_id = null;
                if (arg[1].web_url) {//web_url
                    location = arg[1].web_url;
                    web_id= location.split('web_id=')[1];
                };
				
				clickDown = "";
				sideClass = "sideLeft";
              
                if(arg[2]&&arg[2] == "afterFirst"){
                	sideClass = "sideRight";
                }

                var noForward="",pageType;
                if(parseInt(arg[1].from) > 2){
                    noForward = "noForward=true";
                    pageType = "web_" + arg[1].type;
                }else{
                    pageType = arg[1].type;
                }

                var str = '<li id="' + arg[1].tid + '" name="timeBox" scale="true"  fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + (arg[1].headpic||self.hd_avatar) + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a>';
                    
				if(arg[1].from == "1"){
					
				}else{
					str += '<span class="subTip">上传了<a href="' + arg[1].url + '">视频</a></span>';
				}
				var msgname = arg[1].title || "";
				
				str += '</div><div class="postTime"><a href="#">' + arg[1].friendly_time + '</a><div class=""></div></div></div></div><div class="infoContent">' + arg[1].content + '</div><div class="mediaContent"><div class="media_prev"><img src="' + mk_videoImgUrl(arg[1].imgurl) + '" alt="" width="403" height="300" /><a class="showFlash" href="javascript:void(0);"><img alt="" src="' + miscpath + 'img/system/feedvideoplay.gif"></a></div><div class="media_disp hide" videosrc="' + arg[1].videourl + '"><div id="' + arg[1].fid + '"></div></div></div><div class="commentBox pd" msgname="' + msgname + '" commentObjId="' + arg[1].fid + '" web_id="' + web_id + '" pageType="' + pageType + '" ctime="' + arg[1].ctime + '" ' + noForward + ' action_uid="' + arg[1].uid + '"></div></div></li>';
                
                var $str = $(str);
                append$str($str, $content, arg[2]);
                /*if(arg[2]){
                    if(arg[2]=="prepend"){
                        $content.prepend($str);
                    }
                    if(arg[2]=="afterFirst"){
                        $content.children().first().after($str);
                    }
                }else{
                    $content.append($str);
                }*/
				
                return $str;
            },
            uinfo:function(arg){
                var $content = arg[0],faceUrl;
                
				if(!arg[1].url){
                    faceUrl = $("#hd_avatar").val();
                }
                var subtype = arg[1].subtype;
                var sideClass,clickDown;

                //var location = webpath + "main/index.php?c=index&m=index&action_dkcode=" + arg[1].dkcode;
                var location = mk_url("main/index/main",{'dkcode':arg[1].dkcode}); //modify 7.17
				var web_id = null;
				
				//web_url
                if (arg[1].web_url) {
                    location = arg[1].web_url;
                    web_id = location.split('web_id=')[1];
                };
				
				// 大
				clickDown = "clickDown";
				sideClass = "twoColumn clearfix";
                
                if(arg[2] && arg[2] == "afterFirst"){
                	sideClass = "sideRight";
                }
				
				switch(subtype){
                    case "static": // 加入端口网
                    	subtype = "lifeIcon_3";

                    	// “加入端口网”模块显示效果判断（与首页显示效果一致）
						/*
						if(arg[1].highlight == 0 || "") {
							sideClass = "sideRight";
						} else {
							sideClass = "twoColumn clearfix";
						}*/

                   		break;
                    case "job":    // 工作
                        subtype = "lifeIcon_2";
                    	break;
                    case "born":   // 出生
                        subtype = "lifeIcon_0";
                    	break;
                    case "edu":
                        subtype = "lifeIcon_1";
                    	break;
                    case "life":   // 生活，未知图标
                        subtype = "lifeIcon_4";
                    	break;
                }

                var str = '<li id="' + arg[1].tid + '" name="time" scale="true" highlight="' + arg[1].highlight + '" fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" time="' + arg[1].ctime + '" class="' + sideClass + '"' + (subtype === "lifeIcon_3" ? ' style="*margin-left:-16px;"' : "") + '><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><span class="conWrap midLine tip_up_left_black ' + clickDown + '" tip="放大"><a><i class="conResize"></i></a></span>';

                if(subtype != "lifeIcon_3"){
                    str += '<span class="conWrap"><a><i class="conEdit"></i></a><ul class="editMenu hide"><li name="changeDate"><i class="changeDate"></i>改换日期...</li><li class="sepLine"></li><li name="delTopic"><i class="delTopic"></i>删除帖子...</li>  </ul></span>';
                }
				
                var pageType;
                if(parseInt(arg[1].from) > 2){
                    pageType = "web_topic";
                }else{
                    pageType = "topic";
                }
				var msgname = arg[1].title || "";
                str += '</div><div class="lifeContent"><div class="lifeHeader"><i class="' + subtype + '"></i><div class="lifeTitle">' + arg[1].content + '<p class="subDesc">' + arg[1].info + '</p></div></div></div><div noForward="true" class="commentBox pd" msgname="' + msgname + '" commentObjId="' + arg[1].tid + '" web_id="' + web_id + '" pageType="' + pageType + '" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div></div></li>';

                var $str = $(str);
                append$str($str, $content, arg[2]);
                /*if(arg[2]){
                    if(arg[2] == "prepend"){
                        $content.prepend($str);
                    }
                    if(arg[2] == "afterFirst"){
                        $content.children().first().after($str);
                    }
                }else{
                    $content.append($str);
                }*/

				return $str;
            },
            social:function(arg) {
            	var $content = arg[0],faceUrl;
				
                if(!arg[1].url){
                    faceUrl = $("#hd_avatar").val();
                }
                
                var sideClass,
					clickDown;

				var friends = arg[1].friends || [],
					follows = arg[1].follows || [];

                //var location = webpath + "main/index.php?c=index&m=index&action_dkcode=" + arg[1].dkcode;
                var location = mk_url("main/index/mian",{'dkcode':arg[1].dkcode}); //modify 7.17
				var web_id = null;
                
				if (arg[1].web_url) {//web_url
                    location = arg[1].web_url;
                    web_id= location.split('web_id=')[1];
                };
				
				// 小
				clickDown = "";
				sideClass = "sideLeft";
                
                if(arg[2]&&arg[2] == "afterFirst"){
                	sideClass = "sideRight";
                }
                var noForward = "",
					pageType;
				
                if(parseInt(arg[1].from) > 2){
                    noForward = "noForward=true";
                    pageType = "web_topic";
                }else{
                    pageType = "topic";
                }

				var msgname = arg[1].title || "";
                var str = '<li name="timeBox" scale="true" id="' + arg[1].tid + '" fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="hy-friendHead clearfix"><div class="avatar"><a href="' + location + '"><img src="' + arg[1].headpic + '" height="32" width="32" /></div><div class="info"><a href="' + location + '">' + arg[1].uname + '</a><br><span>' + (arg[1].friendly_time || '最近') + '</span></div></div>';

                if(friends && friends.length) {
	                str += '<h4 class="hy-friendTip">添加了 <a href="javascript:void(0);">' + arg[1].friends_num + ' 位好友</a></h4><div class="hy-friendSmallPanel"><ul class="hy-friendSmallLst">';

	                var l = (arg[1].friends_num > 6) ? 5 : friends.length;
	                for(var i = 0; i < l; i ++) {
	                    str += '<li><a href="' + webpath + 'main/index.php?c=index&m=index&action_dkcode=' + friends[i].code + '" title="' + friends[i].name + '"><img src="' + friends[i].headpic + '" height="65" width="65" alt="' + friends[i].name + '" /></a></li>';
	                }

	                if(arg[1].friends_num > 6) {
	                    str += '<li style="width:64px;"><a href="#" class="more">+' + (arg[1].friends_num - 5) + '</a></li>';
	                }

	                str += "</ul></div>";
	            }

	            if(follows && follows.length) {
	                str += '<h4 class="hy-friendTip">添加了 <a href="javascript:void(0);">' + arg[1].follows_num + ' 位关注</a></h4><div class="hy-friendSmallPanel"><ul class="hy-friendSmallLst">';

	                var l = (arg[1].follows_num > 6) ? 5 : follows.length;
	                for(var i = 0; i < l; i ++) {
	                    str += '<li><a href="' + webpath + 'main/index.php?c=index&m=index&action_dkcode=' + follows[i].code + '" title="' + follows[i].name + '"><img src="' + follows[i].headpic + '" height="65" width="65" alt="' + follows[i].name + '" /></a></li>';
	                }

	                if(arg[1].follows_num > 6) {
	                    str += '<li style="width:64px;"><a href="#" class="more">+' + (arg[1].follows_num - 5) + '</a></li>';
	                }

	                str += "</ul></div>";
	            }

                str += '</div></li>';
               
                var $str = $(str);
                append$str($str, $content, arg[2]);
				
                return $str;
            },
	        sharevideo:function(arg) {
	        	var $content = arg[0];
	            var subtype = arg[1].subtype;
	            var sideClass, clickDown, tipTxthighlight;

                // 小
                clickDown = "";
                tipTxthighlight = "放大";
                sideClass = "sideLeft";

	            //var location = webpath + "main/index.php?c=index&m=index&action_dkcode=" + arg[1].dkcode;
                
				var location = mk_url("main/index/main",{"dkcode":arg[1].dkcode}); //modify 7.17
	            
                var str = '<li id="' + arg[1].tid + '" name="timeBox" scale="true"  fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><span class="conWrap midLine tip_up_left_black ' + clickDown + '" tip="' + tipTxthighlight + '"><a><i class="conResize"></i></a></span><span class="conWrap tip_up_right_black" tip="编辑或删除"><a><i class="conEdit"></i></a><ul class="editMenu hide">';

	            if (String(arg[1].from) == "1") {
	                str += '<li name="changeDate"><i class="changeDate"></i>更改日期...</li><li class="sepLine"></li>';
	            }
	            str += '<li name="delTopic"><i class="delTopic"></i>删除帖子...</li></ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + arg[1].headpic + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a> <span style="font-weight:100;">分享了视频</span>';

	            str += '</span></div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a>';
	            var ctime = arg[1].ctime;
	            var dateline = arg[1].dateline;


	            var friendly_dateline = self.cpu(["returnFriendly_date"], [ctime, dateline]);
	            if (friendly_dateline) {
	                str += '<i class="insertTime tip_up_left_black" tip="' + friendly_dateline + '"></i>';
	            }
	            str += '</div></div></div><div class="infoContent" style="font-size:13px; padding-bottom:8px;"><a href="' + arg[1].url + '" target="_blank">' + arg[1].content + '</a></div><div class="mediaContent" style="height:auto !important; height:80px; min-height:80px; width:128px;"><div class="media_prev" style="padding:0px 0px 10px 15px;">';
	            var showFlashImgT, showFlashImgL;
	            str += '<img src="' + mk_videoImgUrl(arg[1].imgurl) + '" width="128" height="80" alt="" />';
	            showFlashImgT = "29px";
	            showFlashImgL = "67px";

	            var msgname = arg[1].title || "";

	            str += '<a class="showFlash" href="javascript:void(0);"><img alt="" src="' + miscpath + 'img/system/feedvideoplay_small.gif" style="height:23px;top:' + showFlashImgT + ';left:' + showFlashImgL + ';"></a></div><div class="media_disp hide" videosrc="' + arg[1].videourl + '"><div id="' + arg[1].fid + arg[1].tid + '"></div></div></div><div class="commentBox pd" msgname="' + msgname + '" commentObjId="' + arg[1].tid + '" pageType="' + arg[1].type + '" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div></div></li>';

	            var $str = $(str);

	            if (arg[2]) {
	                // 发布框的数据 需要判断日期 插入到临近节点。
	                var $obj = self.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);

	                if ($obj) {
	                    $obj.before($str);
	                } else {
	                    $content.append($str);
	                }
	            } else {
	                $content.append($str);
	            }
	            return $str;
	        }
		};
        
		var fn;
		
		$.each(method,function(index,value){
			if(value){
				if(typeof _class[value] === "function") {
					return fn = _class[value](arg);
				}
			}
		});
		
        return fn;
	},
	event:function(method,arg){
		var self = this;
		var _class={
            scrollToTop:function(arg){
                arg[0].click(function(){
                     $("html,body").animate({scrollTop:$("#timelineTree").offset().top - 100},400);
                });
            },
			removeLoading:function(arg){
				arg[0].find(".loading").remove();
			},
            forward:function(arg){
                var p = arg[0].closest("li[name=timeBox]");
                var name1 = p.find(".AuthorName").children("a").text();
                var name2;
                var name3;
                var value,$content,imgurl;

                var forwardid = p.attr("forwardid");
                var pp = self.$e.find("li#" + forwardid);

                if(p.find(".forwardContent").size() == 0) {
                    value = p.find(".infoContent").html();
                }else{
                    value = p.find(".forwardContent").find(".memo").html() || "";
                }
				if(p.find(".oldAuthorName").size() != 0){
					name2 = p.find(".oldAuthorName").children("a").text().replace(":","") || "";
					name3 = name2;
				}else{
					name3 = name1;
				}

                var typeFunction =  function(type){
                    switch(type){
                        case "info":
                        	$content = $('<div class="laymoveText"><div class="zf_content"><textarea maxlength="140"></textarea><div class="content"><p>'+value+'</p><p>由<span class="name"><a>' + name3 + '</a></span>发布</p></div></div><div class="replyFor"><p><input type="checkbox" id="replyCheck"><label for="replyCheck">同时评论给 ' + name1 + '</label></p></div></div>');
                        	break;
                        case "album":
                             imgurl = p.find("ul.photoContent").find("img").attr("src");
                             $content = $('<div class="laymoveText"><div class="zf_content"><textarea maxlength="140"></textarea><div class="content"><div class="left"><img src="' + imgurl + '" width="90" height="60" /></div><div class="right"><p>' + value + '</p><p>由<span class="name"><a>' + name3 + '</a></span>发布</p></div></div></div><div class="replyFor"><p><input type="checkbox" id="replyCheck"><label for="replyCheck">同时评论给 ' + name1 + '</label></p></div></div>');
                        	break;
                        case "video":
                             imgurl = p.find("div.mediaContent").find("img").attr("src");
                             $content = $('<div class="laymoveText"><div class="zf_content"><textarea maxlength="140"></textarea><div class="content"><div class="left"><img src="' + imgurl + '" width="168" height="90" /></div><div class="right"><p>' + value + '</p><p>由<span class="name"><a>' + name3 + '</a></span>发布</p></div></div></div><div class="replyFor"><p><input type="checkbox" id="replyCheck"><label for="replyCheck">同时评论给 ' + name1 + '</label></p></div></div>');
                        case "sharevideo":

                            imgurl = p.find("div.mediaContent").find("img").attr("src");
                            $content = $('<div class="laymoveText"><div class="zf_content"><textarea maxlength="140"></textarea><div class="content"><div class="left"><img src="' + mk_videoImgUrl(imgurl) + '" width="128" height="80" /></div><div class="right"><p>' + value + '</p><p>由<span class="name"><a>' + name3 + '</a></span>发布</p></div></div></div><div class="replyFor"><p><input type="checkbox" id="replyCheck"><label for="replyCheck">同时评论给 ' + name1 + '</label></p></div></div>');
                        	break;
                    }
					
                    return $content;
                };
                
                if(p.attr("type") == "forward"){
                    if(p.attr("forwardType") && p.attr("forwardType") != "undefined"){
                        $content = typeFunction(p.attr("forwardType"));
                    }else{
                    	self.plug(["popUp"],[p,'<div style="padding:10px">原始信息已被删除！无法进行操作</div>',"提示",function(){
                        	$.closePopUp();
                    	},'<span class="popBtns blueBtn callbackBtn">知道了</span>',400]);                      
                    	
						return false;
                    }
					
                    $content.find("div.replyFor").append('<p><input type="checkbox" id="replyCheckOld"><label for="replyCheckOld">同时评论给原作者 ' + name2 + '</label></p>');
                }else{
                   $content = typeFunction(p.attr("type"));
                }
                   
                self.plug(["popUp"],[arg[0],$content,"分享",function(){
                    var data = {};
					
                    data.content = $content.find("textarea").val();
                    data.tid = p.attr("id");
                    
					if(p.attr("forwardId") && p.attr("forwardId") != "undefined"){
                        data.fid = p.attr("forwardId");
                    }else{
                        data.fid = p.attr("id");
                    }
					
                    if($content.find("#replyCheck").attr("checked")){
                        data.reply_now = p.attr("uid");
                    }
                    if($content.find("#replyCheckOld").attr("checked")){
                        data.reply_author = p.find(".oldAuthorName").attr("uid");
                    }
                    self.model(["doShare"],[data,function(result){
                        if(result.status){
                            var num = p.find(".forwardNum").text();
                            p.find(".forwardNum").text(parseInt(num) + 1);
                            
							/*
                            // 如果是好友模块肯 就可以发出来 否则需要判断
                            var bool = false;
                            
                            if($("#followerFramework").size()!=0||$("#praiseFramwork").size()!=0||$("#friendFramwork").size()!=0){
                                bool = false;
                            }else{
                                if(self.action_dkcode = self.hd_UID){

                                }
                            }
                            if(bool){
                                var $timePsBox = self.$e;
                                self.view([result.data.type],[$timePsBox,result.data,"afterFirst"]);
                                self.plug(['commentEasy'],[$timePsBox]);
                                self.cpu(["lay"],[$timePsBox]);
                            }
                            */
                            
							//原信息分享数+1
                            if(pp.size() != 0){
                                num = pp.find(".forwardNum").text();
                                pp.find(".forwardNum").text(parseInt(num) + 1);
                            }
							
                            self.plug(["popUp"],[arg[0],'<div style="padding:10px">分享成功!</div>',"提示",function(){
                            	$.closePopUp();
                            },'<span class="popBtns blueBtn callbackBtn">知道了</span>',400]);
                        }else{
                            alert(result.info);
                        }
						
                        $.closePopUp();
                    }]);
                },'<span class="popBtns blueBtn callbackBtn">分享</span><span class="popBtns closeBtn">取消</span>']);
                
				limitStrNum($content.find("textarea"));

            }
		};
		
		$.each(method,function(index,value){
			if(value){
				return _class[value](arg);
			}
		});
	},
	plug:function(method,arg){
		var self = this;
		var _class = {
			tip_up_right_black:function(arg){
				arg[0].find(".tip_up_right_black").tip({
					direction:"up",
					position:"right",
					skin:"black",
					hold:true,
					clickHide:true,
					key:1
				});
			},
			msg:function(arg){
				arg[0].find("[msg]").msg();
			},
			commentEasy:function(arg){
                arg[0].find('.commentBox:not(.hasComment)').commentEasy({
                    comment_path:arg[1],
                    minNum:3,
                    UID:CONFIG['u_id'],
                    userName:CONFIG['u_name'],
                    avatar:CONFIG['u_head'],
                    userPageUrl:$("#hd_userPageUrl").val(),
					isShow:false,
                    isOnlyYou:false,
                    relay:true,
                    relayCallback:function(obj){
                        self.event(["forward"],[obj]);
                    },
                    onLoadCallback:function(){
                        self.cpu(["lay"],[arg[0].children("ul.content")]);
                    }

                });
            },
			popUp:function(arg){
                arg[0].popUp({
                    width:arg[5] || 580,
                    title:arg[2],
                    content:arg[1],
                    buttons:arg[4] || '<span class="popBtns blueBtn callbackBtn">确定</span><span class="popBtns closeBtn">取消</span>',
                    mask:true,
                    maskMode:true,
                    callback:arg[3]
                });
            }
		};
		
		$.each(method,function(index,value){
			if(value){
				return _class[value](arg);
			}
		});
	},
	cpu:function(method,arg){
		var self = this;
		var _class = {
			lay:function(arg){
                var $content = arg[0];
                var difference;
                var thisi,previ;
				
				$.each($content.children(),function(i,obj){
					if($(this).hasClass("twoColumn")){
						$(this).children("i").css("top",1);
						return ;
					}
					if($(this).prev().size() == 0){
						$(this).attr("class","sideLeft");
						$(this).children("i").css("top",35);
					}else if($(this).prevAll("li.sideRight").size() != 0 && $(this).prevAll("li.sideLeft").size() == 0){
						$(this).attr("class","sideLeft");
						$(this).children("i").css("top",35);
					}else if($(this).prevAll("li.sideRight").size() == 0 && $(this).prevAll("li.sideLeft").size() != 0){
						$(this).attr("class","sideRight");
						$(this).children("i").css("top",65);
					
					// 上面有右的 
					}else if($(this).prevAll("li.sideRight").size() != 0 && $(this).prevAll("li.sideLeft").size() != 0){
						var $prevRight,$prevLeft,prevRightTop,prevLeftTop;
						//thisY = $(this).offset().top+$(this).height();
						
						$prevLeft = $(this).prevAll("li.sideLeft").eq(0); // 得到最近的右
						$prevRight = $(this).prevAll("li.sideRight").eq(0); // 得到最近的右
						
						prevLeftTop = $prevLeft.offset().top+$prevLeft.height();    // 最近的左坐标
						prevRightTop = $prevRight.offset().top+$prevRight.height();    // 最近的右坐标
			  
						if(!$(this).prev().hasClass("twoColumn") && prevLeftTop > prevRightTop){
							$(this).attr("class","sideRight");
							$(this).children("i").css("top",65);
						}else{
							$(this).attr("class","sideLeft");
							$(this).children("i").css("top",35);
						}
				   }
				   
				   /*
					var prevli,objTop,prevliTop;
					var objTop= parseInt($(this).children("i").css("top"));
					if($(this).attr("class")=="sideLeft"){
						if($(this).prev("li.sideRight").size()!=0){
							prevli = $(this).prev("li.sideRight");
						}
					}else{
						if($(this).prev("li.sideLeft").size()!=0){
							prevli = $(this).prev("li.sideLeft");
						}
					}
					if(prevli&&prevli.size()!=0){
			  
						if($(this).children("i").offset().top-(prevli.children("i").offset().top+10)<0){
							$(this).children("i").css("top",objTop+(prevli.children("i").offset().top-$(this).children("i").offset().top)+20);
						}
					}
					*/
				});
        	},
            returnFriendly_date:function(arg){
                if(parseInt(arg[1]) - parseInt(arg[0]) < 100){
                    return false;
                }else{
					// 为小于10的整数补零
					var addZero = function(num) {
						if(num < 10) {
							num = "0" + num;
						}
						
						return num;
					};
                    var date = new Date(arg[1]*1000);
                    var year = date.getFullYear,
						month = addZero(date.getMonth() + 1),
						day = addZero(date.getDate()),
						hours = addZero(date.getHours()),
						minute = addZero(date.getMinutes());

                    var friendly_dateline = year + "年" + month + "月" + day + "日 " + hours + ":" + minute;
					
                    return friendly_dateline;
                }
            }
		};
		return _class[method](arg);
	},
    model:function(method,arg){
        var self = this;
        var _class = {
            doShare:function(arg){
                $.djax({
                    url:mk_url("main/share/doShare"),
                    async:true,
                    dataType:"jsonp",
                    data:arg[0],
                    success:function(data){
                        if(data){
							//var data = eval("("+data+")");
							arg[1](data);
                        }
                    }
                });
            }

        }
		
        return _class[method](arg);
    }
};
