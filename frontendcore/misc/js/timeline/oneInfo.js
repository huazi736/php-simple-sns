/**
 * @author:    zhangbo
 * @created:   2012/05/15
 * @version:   v1.0
 * @desc:      单条信息页面
 */
function CLASS_ONEINFO(options){
	var self = this;
    this.hd_avatar     = $("#hd_avatar").val();//获取用户头像
    this.infoType      = $("#params").attr("from");//获取信息来源
    this.tid           = $("#params").attr("tid");//获取信息tid
    this.web_id        = $("#params").attr("webId");//获取web_id
    this.hd_UID        = $("#hd_UID").val();//获取用户id
    this.action_dkcode = $("#action_dkcode").val();//获取端口号
    self.avatar        = this.hd_avatar;
	if(this.infoType=="web"){
		isForward = false ;
	}else{
		isForward = true ;
	}
}
CLASS_ONEINFO.prototype= {
	init:function(){
		var self = this;
        var $show_box = $('#getInfo_box');   
		var postdata = {};
			postdata.tid =self.tid;
			if(self.infoType=="web"){
				postdata.from =self.infoType;
                postdata.web_id =self.web_id;
			}
		  self.model("getdata",[postdata,function(a,b){
			  if(a.status == 1){
				  self.view([a.data.type],[$show_box,a.data]);
				 	 
				  	self.view(["permissionShow"], [a.data.permission, $show_box]);
                    self.web_name = a.data.uname;//获取web_name
                    self.web_avatar = a.data.user_avartar;//获取web_pic
                    self.web_home = a.data.web_home;//获取web_pic
				  	self.plug(['commentEasy'], [$show_box, isForward]);
				  	self.plug(['tip_up_left_black'], [$show_box]);
				  	self.event(['delInfo'], [".del_info", $show_box]);
				  	
                    web_home = a.data.web_home;//获取web_home_url
					
				  }else{
				  	var $content = $show_box.find("ul.content");
					 $str = '<div class="forwardContent"></span>'+a.msg+'！</div>'
					$content.append($str); 
				  }
                    
		  }]);

        var comment_show = $('#comment_show');
        //显示播放flash
        comment_show.on('click', 'div.media_prev', function () {
            var _self = this;
            //创建一个视频对象
            var videoController = new VideoController();
            //获取页面上的视频id
            var videoId = $(this).next().children('div').attr('id');
            //获取视频其它参数，与id不在同一个div上
            var fid = $(this).closest("[name='timeBox']").attr("fid");
            var videoWidth, videoHeight;
            if ($(this).closest("li.twoColumn").size() != 0) {
                videoWidth = 838; //parseInt(videoDiv.attr('videowidth'));
                videoHeight = 600;
            } else {
                videoWidth = 401; //parseInt(videoDiv.attr('videowidth'));
                videoHeight = 300; //parseInt(videoDiv.attr('videoheight'));    //播放控制高度
            }
            //显示播放界面
            videoController.insertVideoToDom(videoId, fid, videoWidth, videoHeight, function () {
                $(_self).addClass('hide').siblings().removeClass('hide');
            });
            //收起触发事件
            var $info_media_disp = $(this).next();
            $info_media_disp.find('a.hideFlash').one('click', function () {
                $info_media_disp.addClass('hide').prev().removeClass('hide');
                videoController.deleteVideoFromDom();
            });
        });

        //播放器对象函数
        function VideoController() {
            this.currentVideoId = null;
            this.currentVideoParentDom = null;
            this.insertVideoToDom = function (_flashWrapId, _videoURL, _videoWidth, _videoHeight, _callfunc) {
                if (document.getElementById(_flashWrapId)) {
                    this.currentVideoId = $("#" + _flashWrapId).closest("[type='video']").attr("fid") || _flashWrapId.toString().substring(0, 10);
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
                        'allowScriptAccess', 'always',
                        'movie', CONFIG.misc_path + 'flash/video/player.swf?vid=' + _videoURL + '&mod=1&uid=' + CONFIG.u_id,
                        'flashvars','autoplay=true',
                        'allowFullScreen', 'true',
                        'salign', '',
                        'contentId', document.getElementById(_flashWrapId)
                    );
                    if (_callfunc) {
                        _callfunc();
                    }
                }
            }
            this.deleteVideoFromDom = function () {
                if (this.currentVideoId && this.currentVideoParentDom) {
                    swfobject.removeSWF(this.currentVideoId);
                    if (!document.getElementById(this.currentVideoId)) {
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
		var _class = {
			permissionShow:function(arg){
     
                var str = "";
                switch(String(arg[0])){
                    case "8":
                        str = "<span class='icon_permission_4 tip_up_left_black' tip='仅限自己'></span>";

                    break;
                    case "4":

                        str =  "<span class='icon_permission_3 tip_up_left_black' tip='好友'></span>";
                        
                    break;
                    case "3":

                        str = "<span class='icon_permission_2 tip_up_left_black' tip='粉丝'></span>";
                        
                    break;
                    case "1":

                        str = "<span class='icon_permission_1 tip_up_left_black' tip='公开'></span>";
                        
                    break;

                    case "-1":
                        str = "<span class='icon_permission_5 tip_up_left_black' tip='自定义'></span>";
                        
                    break;
                }

                arg[1].find(".postTime").append("<div class='timeBoxPermission'>"+str+"</div>");
               
            },
            forward:function(arg){
                var $content = arg[0].children("ul.content");
                var sideClass, clickDown, forwardContent = "", tipTxthighlight;
                var location, typeHtml, albumText, hrefPermisson;

                if (arg[1].highlight == 0 || "") {
                    // 小
                    clickDown = "";
                    sideClass = "sideLeft";
                    size = "_tm";
                    tipTxthighlight = "放大";
                } else {
                    // 大
                    size = "_tb";
                    clickDown = "clickDown";
                    sideClass = "twoColumn clearfix";
                    tipTxthighlight = "缩小";
                }
                var ftid = "", ftype = "", typetext;
                if (arg[1].forward && arg[1].forward.length != 0) {
                    ftid = arg[1].forward.tid;
                    ftype = arg[1].forward.type;
                    location = mk_url("main/index/main",{dkcode:arg[1].forward.dkcode});
                    switch (arg[1].forward.type) {
                        case "info":
                            forwardContent = '<div class="forwardContent"><span class="memo" style="margin:0">' + arg[1].forward.content + '</span></div>';

                            typeHtml = '分享了<span class="oldAuthorName" uid="' + arg[1].forward.uid + '">' + arg[1].forward.uname + '</span>的状态</a>';
                            break;
                        case "blog":
                            var blogTitle = arg[1].title;
                            blogTitle = blogTitle.length > 23 ? blogTitle.substring(0,20) + "..." : blogTitle;
                            forwardContent = '<div class="forwardContent"><p style="margin:0; padding:10px 0px; font-size:14px;"><a href="' + arg[1].forward.url + '" title="' + arg[1].title + '"><strong>' + blogTitle + '</strong></a></p><span class="memo" style="margin:0; line-height:1.5;">' + arg[1].forward.content + '</span><p style="margin:0; padding:10px 0px 0px;"><a href="' + arg[1].forward.url + '">继续阅读......</a></p></div>';

                            typeHtml = '分享了<span class="oldAuthorName" uid="' + arg[1].forward.uid + '"><a href="' + location + '">' + arg[1].forward.uname + '</a></span>日志</a>';
                            break;
                        case "album":
                            var temp = "";

                            temp = '<ul class="photoContent clearfix">';
                            if (arg[1].forward.photonum == "1" && parseInt(arg[1].forward.fid) > 10000000) {
                                albumText = "照片";

                            } else {
                                albumText = "相册";

                            }
                            if (arg[1].forward.from == "1") {
                                albumText = "照片";
                            }

                            if (arg[1].forward.picurl) {
                                $.each(arg[1].forward.picurl, function (i, v) {
                                    var hidden = "";
                                    if (arg[1].highlight == 0) {
                                        if (i > 3) {
                                            hidden = "hide";
                                        }
                                    } else {
                                        if (i > 3) {
                                            hidden = "show";
                                        }
                                    }
                                    var firstPhoto = "";
                                    var width = "", height = "";
                                    if (i == 0) {
                                        firstPhoto = "firstPhoto";
                                    } else {
                                        size = "_ts";

                                    }
                                    hrefPermisson = mk_url("album/index/photoInfo",{photoid:v.pid,dkcode:arg[1].dkcode});

                                    if (albumText == "照片") {
                                        hrefPermisson += "&permission=" + arg[1].forward.permission;
                                    }
                                    var _width = "expression(this.width>=838?838:'auto')";
                                    picurl = fdfsHost + "/" + v.groupname + "/" + v.filename + size + "." + v.type;
                                    temp += '<li class="' + firstPhoto + " " + hidden + '" style="' + hidden + ';"><a href="javascript:void(0);" action_dkcode="' + arg[1].dkcode + '" pid="' + v.pid + '" url="' + hrefPermisson + '" class="photoLink"><img src="' + picurl + '" alt="" style="max-width:838px;_width:' + _width + '"/></a></li>'; //height="'+height+'" width="'+width+'" 删除 by李世君 2012-4-1
                                });


                                typeHtml = '分享了<span class="oldAuthorName" uid="' + arg[1].forward.uid + '"><a href="' + location + '">' + arg[1].forward.uname + '</a></span>的<a href="' + mk_url("album/index/photoLists",{dkcode:arg[1].forward.dkcode,albumid:arg[1].forward.note}) + '">' + albumText + '</a>';
                            }


                            temp += '</ul>';
                            forwardContent = '<div class="forwardContent"><span class="memo" style="margin:0">' + arg[1].forward.content + '</span></div>' + temp;

                            break;
                        case "photo":
                            var temp = "";

                            temp = '<ul class="photoContent clearfix">';
                            if (arg[1].forward.photonum == "1" && parseInt(arg[1].forward.fid) > 10000000) {
                                albumText = "照片";

                            } else {
                                albumText = "相册";

                            }
                            if (arg[1].forward.from == "1") {
                                albumText = "照片";
                            }

                            if (arg[1].forward.picurl) {
                                $.each(arg[1].forward.picurl, function (i, v) {
                                    var hidden = "";
                                    if (arg[1].highlight == 0) {
                                        if (i > 3) {
                                            hidden = "hide";
                                        }
                                    } else {
                                        if (i > 3) {
                                            hidden = "show";
                                        }
                                    }
                                    var firstPhoto = "";
                                    var width = "", height = "";
                                    if (i == 0) {
                                        firstPhoto = "firstPhoto";
                                    } else {
                                        size = "_ts";

                                    }
                                    hrefPermisson = mk_url("album/index/photoInfo",{photoid:v.pid,dkcode:arg[1].dkcode});

                                    if (albumText == "照片") {
                                        hrefPermisson += "&permission=" + arg[1].forward.permission;
                                    }
                                    var _width = "expression(this.width>=838?838:'auto')";
                                    picurl = fdfsHost + "/" + v.groupname + "/" + v.filename + size + "." + v.type;
                                    temp += '<li class="' + firstPhoto + " " + hidden + '" style="' + hidden + ';"><a href="javascript:void(0);" action_dkcode="' + arg[1].dkcode + '" pid="' + v.pid + '" url="' + hrefPermisson + '" class="photoLink"><img src="' + picurl + '" alt="" style="max-width:838px;_width:' + _width + '"/></a></li>'; //height="'+height+'" width="'+width+'" 删除 by李世君 2012-4-1
                                });


                                typeHtml = '分享了<span class="oldAuthorName" uid="' + arg[1].forward.uid + '"><a href="' + location + '">' + arg[1].forward.uname + '</a></span>的<a href="' + mk_url("album/index/photoLists",{dkcode:arg[1].forward.dkcode,albumid:arg[1].forward.note}) + '">' + albumText + '</a>';
                            }


                            temp += '</ul>';
                            forwardContent = '<div class="forwardContent"><span class="memo" style="margin:0">' + arg[1].forward.content + '</span></div>' + temp;

                            break;
                        case "video":
                            var temp = "";
                            temp = '<div class="mediaContent" name="timeBox" fid="' + arg[1].forward.fid + '"><div class="media_prev">';
                            if (arg[1].highlight == 0) {
                                temp += '<img onerror="this.src=\'' + (CONFIG.misc_path + "img/default/video_err1.jpg") + '\'" src="' +  mk_videoPicUrl(arg[1].forward.imgurl) + '" width=403 height=300 alt="" />';
                                showFlashImgT = "125px";
                                showFlashImgL = "184px";
                            } else {
                                temp += '<img onerror="this.src=\'' + (CONFIG.misc_path + "img/default/video_err1.jpg") + '\'" src="' + mk_videoPicUrl(arg[1].forward.imgurl) + '" width=838 height=600 alt="" />';
                                showFlashImgT = "300px";
                                showFlashImgL = "407px";
                            }
                            typeHtml = '分享了<span class="oldAuthorName" uid="' + arg[1].forward.uid + '"><a href="' + location + '">' + arg[1].forward.uname + '</a></span>的<a href="' + mk_url('video/video/player_video',{vid:arg[1].forward.fid}) + '">视频</a>';

                            temp += '<a class="showFlash" href="javascript:void(0);"><img alt="" src="' + CONFIG.misc_path + '/img/system/feedvideoplay.gif"></a></div><div class="media_disp hide" ><div id="video_' + arg[1].forward.fid + arg[1].tid + '"></div></div></div>';
                            forwardContent = '<div class="forwardContent"><span class="memo" style="margin:0">' + arg[1].forward.content + '</span></div>' + temp;
                            break;

                        case "sharevideo":
                            var temp = "";
                            temp = '<div class="mediaContent" style="height:auto !important; height:80px; min-height:80px; width:128px;"><div class="media_prev" style="padding:0px 0px 10px 15px;">';

                            temp += '<img onerror="this.src=\'' + (CONFIG.misc_path + "img/default/video_err1.jpg") + '\'" src="' +  mk_videoPicUrl(arg[1].forward.imgurl) + '" width="128" height="80" alt="" />';
                            showFlashImgT = "29px";
                            showFlashImgL = "67px";

                            typeHtml = '分享了<span class="oldAuthorName" uid="' + arg[1].forward.uid + '"><a href="' + location + '">' + arg[1].forward.uname + '</a></span>分享的视频';

                            temp += '<a class="showFlash" href="javascript:void(0);"><img alt="" src="' + CONFIG.misc_path + 'img/system/feedvideoplay_small.gif" style="height:23px;top:' + showFlashImgT + ';left:' + showFlashImgL + ';"></a></div><div class="media_disp hide" ><div id="video_' + arg[1].forward.fid + arg[1].tid + '"></div></div></div>';
                            forwardContent = '<div class="forwardContent"><span class="memo" style="margin:0"><a href="' + arg[1].forward.videourl + '" target="_blank">' + arg[1].forward.content + '</a></span></div>' + temp;

                            break;
                    }
                } else {
                    forwardContent = '<div class="forwardContent"></span>该信息已被删除！</div>'
                    typeHtml = "分享了一个信息"
                }

                location = mk_url('main/index/main',{dkcode:arg[1].dkcode});
                var str = '<li name="timeBox" scale="true" id="' + arg[1].tid + '"  fid="' + arg[1].fid + '" forwardId="' + ftid + '" forwardType="' + ftype + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><span class="conWrap midLine tip_up_left_black ' + clickDown + '" tip="' + tipTxthighlight + '"><a><i class="conResize"></i></a></span><span class="conWrap tip_up_right_black" tip="编辑或删除"><a><i class="conEdit"></i></a><ul class="editMenu hide"><li name="changeDate"><i class="changeDate"></i>更改日期...</li><li class="sepLine"></li><li name="delTopic"><i class="delTopic"></i>删除帖子...</li>    </ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + self.avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a>';

                var msgname = arg[1].title || "";
                str += '<span class="subTip">' + typeHtml + '</span>';
                str += '</div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a></div></div></div><div class="infoContent">' + arg[1].content + '</div>' + forwardContent + '<div class="commentBox pd" msgname="' + msgname + '" commentObjId="' + arg[1].tid + '" pageType="' + arg[1].type + '" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div></div></li>';


                var $str = $(str);

//                if (arg[2]) {
//                    // 发布框的数据 需要判断日期 插入到临近节点。
//                    var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);
//
//                    if ($obj) {
//                        $obj.before($str);
//                    } else {
//                        $content.append($str);
//                    }
//                } else {
                    $content.append($str);
//                }
                return $str;
                //this.event(["photoLink"],[$str]);
            },
            info:function(arg){
               
                
                
                var $content = arg[0].children("ul.content");
                
                var sideClass,clickDown,tipTxthighlight;

                if(self.infoType=="web"){
                    self.avatar = arg[1].user_avartar;
                    var location = arg[1].web_home;
                }else{
//                     var location = webpath+"main/index.php?c=index&m=index&action_dkcode="+arg[1].dkcode;
                     var location = mk_url("main/index/main", {'dkcode':arg[1].dkcode});
                }
               

                var str = '<li class="info-item" name="timeBox" scale="true" id="'+arg[1].tid+'"  fid="'+arg[1].fid+'" uid="'+arg[1].uid+'" type="'+arg[1].type+'" type="'+arg[1].type+'" ><div class="timelineBox"><div class="headBlock clearfix"><a href="'+location+'" class="headImg"><img src="'+self.avatar+'" width="50" height="50" alt="" /></a><div class="unitHeader"><a class="fr uiTooltip uiLinkSubtle" href="javascript:void(0);"><i class="del_info"></i></a><div class="AuthorName"><a href="'+location+'">'+arg[1].uname+'</a></div><div class="postTime"><span>'+arg[1].friendly_time+'</span>';
                var pageType = "topic"
                if (self.infoType=="web") {
                    pageType = "web_topic"
                };
                var ctime = arg[1].ctime;
                var dateline = arg[1].dateline;

                var msgname=arg[1].title||"";
                str+='</div></div></div><div class="infoContent">'+arg[1].content+'</div><div class="commentBox pd" msgname="'+msgname+'" web_id="'+self.web_id+'" commentObjId="'+arg[1].tid+'" pageType="'+pageType+'" ctime="'+arg[1].ctime+'" action_uid="'+arg[1].uid+'"></div></div></li>';

                var $str = $(str);
//                if(arg[2]){
//                    // 发布框的数据 需要判断日期 插入到临近节点。
//                    var $obj = self.cpu(["returnPrevTimebox"],[$content,arg[1].ctime]);
//
//
//                    if($obj){
//                        $obj.before($str);
//                    }else{
//                        $content.append($str);
//                    }
//                }else{
                    $content.append($str);
//                }
                return $str;

    
            },
            album:function(arg){
                

                var $content = arg[0].children("ul.content");
                    
              

                var picurl;

                var typeHref;
                var size,photoClass="";
               
                var albumText = "";
                var sideClass,clickDown,tipTxthighlight;
                var pageType = "album",commentObjId;

//                var location = mk_url("main/index/main", {'dkcode':arg[1].dkcode});
                var location = mk_url("main/index/main", {'dkcode':arg[1].dkcode});
                var str = '<li class="info-item" name="timeBox" scale="true" id="'+arg[1].tid+'" type="album" highlight="'+arg[1].highlight+'" fid="'+arg[1].fid+'" uid="'+arg[1].uid+'" type="'+arg[1].type+'"  album="403" time="'+arg[1].ctime+'"><div class="timelineBox">';
              
                str+='<div class="headBlock clearfix"><a href="'+location+'" class="headImg"><img src="'+self.avatar+'" width="50" height="50" alt="" /></a><div class="unitHeader"><a class="fr uiTooltip uiLinkSubtle" href="javascript:void(0);"><i class="del_info"></i></a><div class="AuthorName"><a href="'+location+'">'+arg[1].uname+'</a>';
                if(parseInt(arg[1].fid)>10000000){
                    albumText = "照片";  
                    pageType = "photo"

                    commentObjId = arg[1].picurl[0].pid;
                }else{
                    albumText = "相册";
                    pageType = "album";
                    commentObjId = arg[1].fid;

                }
                if(arg[1].from!=2){
                    pageType = "photo"
                  
                }else{
         
//                    typeHref = webpath+"single/album/index.php?c=index&m=photoLists&action_dkcode="+arg[1].dkcode+"&albumid="+arg[1].note;
                    typeHref = mk_url("album/index/photoLists", {'dkcode':arg[1].dkcode, 'albumid':arg[1].note});
                    str+='<span class="subTip">上传了<a href="'+typeHref+'">'+albumText+'</a></span>';
                }
                str+='</span></div><div class="postTime"><span>'+arg[1].friendly_time+'</span>';

               var ctime = arg[1].ctime;
               var dateline = arg[1].dateline;

               

               str+='</div></div></div><div class="infoContent">'+arg[1].content+'</div><ul class="photoContent clearfix">';

                if(arg[1].picurl){
                    var _height;
                   $.each(arg[1].picurl,function(i,v){
                        var hidden = "";
                        var size = eval(v.size);
                        if(arg[1].highlight==0){
                            if(i>3){
                                hidden = "hide";
                            }
                        }else{
                            if(i>3){
                                hidden = "show";
                            }
                        }
                        var firstPhoto = "";
                        var width = "",height = "";
                        if(i==0){
                            firstPhoto = "firstPhoto";
                            if(v.size){
                                if(v.size.tm){
                                    _height = v.size.tm.h;
                                }
                            }
                            if(arg[1].highlight==0){
                            
                                size = "_tm";
                            }else{
                                size = "_tb";
                                
                            }
                        }else{
                            size = "_ts";
                        }
                        if(i==1){
                            if(v.size){
                                if(v.size.ts){
                                    _height=133;
                                }
                            }
                        }
                        var _width = "expression(this.width>=838?838:'auto')";
                        picurl = fdfsHost+"/"+v.groupname+"/"+v.filename+size+"."+v.type;



                        str+='<li class="'+firstPhoto+" "+hidden+'" style="height:'+_height+'px" ><a href="javascript:void(0);" action_dkcode="'+arg[1].dkcode+'" pid="'+v.pid+'" url="'+mk_url("album/index/photoInfo", {'photoid':v.pid, 'dkcode':arg[1].dkcode})+'" class="photoLink"><img src="'+picurl+'" alt="" style="max-width:838px;_width:'+_width+'"/></a></li>'; //height="'+height+'" width="'+width+'" 删除 by李世君 2012-4-1
                    });
                }
                str+='</ul><div class="commentBox pd" commentObjId="'+commentObjId+'" pageType="'+pageType+'" ctime="'+arg[1].ctime+'" action_uid="'+arg[1].uid+'"></div></div></li>';
                var $str;
                $str = $(str);
//                if(arg[2]){
//                    // 发布框的数据 需要判断日期 插入到临近节点。
//                    var $obj = self.cpu(["returnPrevTimebox"],[$content,arg[1].ctime]);
//
//                    if($obj){
//                        $obj.before($str);
//                    }else{
//                        $content.append($str);
//                    }
//                }else{
                    $content.append($str);
//                }
//               self.event(["photoLink"],[$str]);
              
            },
            video:function(arg){
               
                var $content = arg[0].children("#.content");
                var subtype = arg[1].subtype;
                var sideClass,clickDown,tipTxthighlight;



                var location = mk_url("main/index/main", {'dkcode':arg[1].dkcode});
                var str = '<li id="'+arg[1].tid+'" name="timeBox" scale="true"  fid="'+arg[1].fid+'" uid="'+arg[1].uid+'" type="'+arg[1].type+'" highlight="'+arg[1].highlight+'" ><div class="timelineBox">';

               
                str+='<div class="headBlock clearfix"><a href="'+location+'" class="headImg"><img src="'+self.avatar+'" width="50" height="50" alt="" /></a><div class="unitHeader"><a class="fr uiTooltip uiLinkSubtle" href="javascript:void(0);"><i class="del_info"></i></a><div class="AuthorName"><a href="'+location+'">'+arg[1].uname+'</a>';
                str+='</div></div><div class="postTime"><span>'+arg[1].friendly_time+'</span></div></div>';
                var ctime = arg[1].ctime;
                var dateline = arg[1].dateline;


                str+='<div class="infoContent">'+arg[1].content+'</div><div class="mediaContent"><div class="media_prev">';
                var showFlashImgT,showFlashImgL;
                if(arg[1].highlight==0){
                    str+='<img src="'+arg[1].imgurl+'" width=403 height=300 alt="" />';
                    showFlashImgT = "125px";
                    showFlashImgL = "184px";
                }else{
                    str+='<img src="'+arg[1].imgurl+'" width=838 height=600 alt="" />';
                    showFlashImgT = "300px";
                    showFlashImgL = "407px";
                }
                str+='<a class="showFlash" href="javascript:void(0);"><img alt="" src="'+miscpath+'img/system/feedvideoplay.gif" style="top:'+showFlashImgT+';left:'+showFlashImgL+';"></a></div><div class="media_disp hide" videosrc="'+arg[1].videourl+'"><div id="'+arg[1].fid+arg[1].tid+'"></div></div></div><div class="commentBox pd" commentObjId="'+arg[1].fid+'" pageType="'+arg[1].type+'" ctime="'+arg[1].ctime+'" action_uid="'+arg[1].uid+'"></div></div></li>';
                
//                if(arg[2]){
//                    // 发布框的数据 需要判断日期 插入到临近节点。
//                    var $obj = self.cpu(["returnPrevTimebox"],[$content,arg[1].ctime]);
//
//                    if($obj){
//                        $obj.before(str);
//                    }else{
//                        $content.append(str);
//                    }
//                }else{
                    $content.append(str);
//                }
               
            },
            ask:function (arg) {
                var $content = arg[0].children("ul.content");
                var subtype = arg[1].subtype;
                var sideClass, clickDown, tipTxthighlight;
                var commentObjId = arg[1].tid,
                    msgname = arg[1].title,
                    pageType = "ask";


                if (arg[1].highlight == 0 || "") {
                    // 小
                    clickDown = "";
                    tipTxthighlight = "放大";
                    sideClass = "sideLeft";
                } else {
                    // 大
                    tipTxthighlight = "缩小";
                    clickDown = "clickDown";
                    sideClass = "twoColumn clearfix";
                }

                var location = mk_url("main/index/main",{dkcode:arg[1].dkcode});

                var str = '<li name="timeBox" scale="true" id="' + arg[1].tid + '"  fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><span class="conWrap midLine tip_up_left_black ' + clickDown + '" tip="' + tipTxthighlight + '"><a><i class="conResize"></i></a></span><span class="conWrap tip_up_right_black" tip="编辑或删除"><a><i class="conEdit"></i></a><ul class="editMenu hide"><li name="delTopic"><i class="delTopic"></i>删除帖子...</li></ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + self.avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a> <span class="subTip">提出了<a href="javascript:;" onclick="$(this).showAsk({ispopBox:true,poll_id:\'' + arg[1].fid + '\'})">问答</a></span></div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a></div></div></div><div class="infoContent">';


                str += '<div class="J_askPanel" style="height:auto !important;"></div></div><div class="commentBox pd" commentObjId="' + commentObjId + '" msgname="' + msgname + '" pageType="' + pageType + '" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div></div></li>';

                var $str = $(str);

//                if (arg[2]) {
//                    // 发布框的数据 需要判断日期 插入到临近节点。
//                    var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);
//
//                    if ($obj) {
//                        $obj.before($str);
//                    } else {
//                        $content.append($str);
//                    }
//                } else {
                    $content.append($str);
//                }

                var askPanel = $str.find("div.J_askPanel");
                askPanel.showAsk(arg[1].ask);
                askPanel.css({"overflow":"hidden","width":"375px"});

                return $str;
            },
            blog:function (arg) {
            var $content = arg[0].children("ul.content");
            var theLast = $content.children("li[name='timeBox']").last();

            var contentClass = "infoContent";
            var sideClass, clickDown, tipTxthighlight;

            if (arg[1].highlight == 0 || "") {
                // 小
                clickDown = "";
                tipTxthighlight = "放大";
                sideClass = "sideLeft";
            } else {
                // 大
                tipTxthighlight = "缩小";
                clickDown = "clickDown";
                sideClass = "twoColumn clearfix";
            }

            var location = mk_url("main/index/main",{dkcode:arg[1].dkcode});
            var blogUrl = mk_url('blog/blog/main',{'id':arg[1].fid,'dkcode':arg[1].dkcode});
            var str = '<li name="timeBox" scale="true" id="' + arg[1].tid + '"  fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><span class="conWrap midLine tip_up_left_black ' + clickDown + '" tip="' + tipTxthighlight + '"><a><i class="conResize"></i></a></span><span class="conWrap tip_up_right_black" tip="编辑或删除"><a><i class="conEdit"></i></a><ul class="editMenu hide"><li name="changeDate"><i class="changeDate"></i>更改日期...</li><li class="sepLine"></li><li name="delTopic"><i class="delTopic"></i>删除帖子...</li> </ul></span></div><div class="headBlock clearfix"><a href="" class="headImg"><img src="' + self.avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a>';
            if (arg[1].action && arg[1].action == 2) {
                str += ' <span class="subTip">分享了<a href="' + arg[1].nameurl + '">' + arg[1].fname + '</a>的<a href="' + arg[1].furl + '">日志</a></span>';
            } else {
                str += ' <span class="subTip">发表了<a href="' + blogUrl + '">日志</a></span>';
            }
            str += '</div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a></div></div></div>';
            if (arg[1].action && arg[1].action == 2) {
                contentClass = "forwardContent";
            } else {
                contentClass = "infoContent"
            }
            var msgname = arg[1].title || "";
            str += '<div class="' + contentClass + ' blog"><h3><a href="' + blogUrl + '">' + arg[1].title + '</a></h3><p>' + arg[1].content + '</p><p><a href="' + blogUrl + '" class="readMore">继续阅读... </a></p></div><div noForward="true" class="commentBox pd" msgname="' + msgname + '" commentObjId="' + arg[1].fid + '" pageType="' + arg[1].type + '" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div></div></li>';

            var $str = $(str);

//            if (arg[2]) {
//                // 发布框的数据 需要判断日期 插入到临近节点。
//                var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);
//
//                if ($obj) {
//                    $obj.before($str);
//                } else {
//                    $content.append($str);
//                }
//            } else {
                $content.append($str);
//            }
            return $str;
        },
        
            event:function (arg) {
            var $content = arg[0].children("ul.content");

            var sideClass, clickDown, tipTxthighlight;

            if (arg[1].highlight == 0 || "") {
                // 小
                clickDown = "";
                tipTxthighlight = "放大";
                sideClass = "sideLeft";
            } else {
                // 大
                tipTxthighlight = "缩小";
                clickDown = "clickDown";
                sideClass = "twoColumn clearfix";
            }

            var location = mk_url("main/index/main",{dkcode: arg[1].dkcode});
            var eventUrl = mk_url('event/event/detail',{'id':arg[1].fid});
            var str = '<li id="' + arg[1].tid + '" name="timeBox" scale="true"  fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><span class="conWrap midLine tip_up_left_black ' + clickDown + '" tip="' + tipTxthighlight + '"><a><i class="conResize"></i></a></span><span class="conWrap tip_up_right_black" tip="编辑或删除"><a><i class="conEdit"></i></a><ul class="editMenu hide"><li name="delTopic"><i class="delTopic"></i>删除帖子...</li></ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + self.avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a><span class="subTip">创建了<a href="' + eventUrl + '">活动</a></span></div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a></div></div></div><div class="infoContent clearfix"><a class="eventPic" href="' + eventUrl + '"><img width="50" height="50" src="' + arg[1].photo + '" alt=""></a><div class="eventInfo"><h4><a href="' + eventUrl + '">' + arg[1].title + '</a></h4><span>' + arg[1].starttime + '</span></div></div></div></li>';


            var $str = $(str);

//            if (arg[2]) {
//                // 发布框的数据 需要判断日期 插入到临近节点。
//                var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);
//
//                if ($obj) {
//                    $obj.before($str);
//                } else {
//                    $content.append($str);
//                }
//            } else {
                $content.append($str);
//            }
            return $str;
        },
        
            photo:function (arg) {
            var $content = arg[0].children("ul.content");

            var picurl;

            var typeHref;
            var size, photoClass = "";

            var albumText = "";
            var sideClass, clickDown, tipTxthighlight;
            var pageType = "photo", commentObjId;

            if (arg[1].highlight == 0 || "") {
                // 小
                clickDown = "";
                tipTxthighlight = "放大";
                sideClass = "sideLeft";
            } else {
                // 大
                tipTxthighlight = "缩小";
                clickDown = "clickDown";
                sideClass = "twoColumn clearfix";
            }
            var location = mk_url("main/index/main",{dkcode:arg[1].dkcode});
            var str = '<li name="timeBox" scale="true" id="' + arg[1].tid + '" highlight="' + arg[1].highlight + '" fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '"  album="403" time="' + arg[1].ctime + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><span class="conWrap midLine ' + clickDown + ' tip_up_left_black" tip="' + tipTxthighlight + '"><a title="调整大小"><i class="conResize"></i></a></span><span class="conWrap tip_up_right_black" tip="编辑或删除"><a title="编辑或删除"><i class="conEdit"></i></a><ul class="editMenu hide">';
            if (arg[1].from != 2) {
                str += '<li name="changeDate"><i class="changeDate"></i>更改日期...</li><li class="sepLine"></li>';
            }
            str += '<li name="delTopic"><i class="delTopic"></i>删除帖子...</li> </ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + self.avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a>';
            // if (parseInt(arg[1].fid) > 10000000) {
            albumText = "照片";
            pageType = "photo";

            //commentObjId = arg[1].picurl[0].pid;
            commentObjId = arg[1].fid;
            // } else {
            //     albumText = "相册";
            //     pageType = "album";
            //     commentObjId = arg[1].fid;

            // }

//            if (arg[1].from != 2) {
//             pageType = "photo"
//
//             } else {
//
//             typeHref = webpath + "single/album/index.php?c=index&m=photoLists&action_dkcode=" + arg[1].dkcode + "&albumid=" + arg[1].note;
//             typeHref = mk_url("album/index/photoLists",{dkcode: arg[1].dkcode,albumid:arg[1].note});
//             str += '<span class="subTip">上传了<a href="' + typeHref + '">' + albumText + '</a></span>';
//             }
            str += '</div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a>';
            if (arg[1].from != 2) {
                var ctime = arg[1].ctime;
                var dateline = arg[1].dateline;


//                var friendly_dateline = self.cpu(["returnFriendly_date"], [ctime, dateline]);
//                if (friendly_dateline) {
//                    str += '<i class="insertTime tip_up_left_black" tip="' + friendly_dateline + '"></i>';
//                }
            }

            str += '</div></div></div><div class="infoContent">' + arg[1].content + '</div><ul class="photoContent clearfix">';

            if (arg[1].picurl) {
                var _height;
                $.each(arg[1].picurl, function (i, v) {
                    var hidden = "";
                    //var size = eval(v.size);

                    if (arg[1].highlight == 0) {
                        if (i > 3) {
                            hidden = "hide";
                        }
                    } else {
                        if (i > 3) {
                            hidden = "show";
                        }
                    }
                    var firstPhoto = "";
                    var width = "", height = "";
                    if (i == 0) {
                        firstPhoto = "firstPhoto";
                        if (v.size) {
                            if (v.size.tm) {
                                _height = v.size.tm.h;
                            }
                        }
                        if (arg[1].highlight == 0) {
                            size = "_tm";
                        } else {
                            size = "_b";

                        }
                    } else {
                        size = "_ts";
                    }
                    if (i == 1) {
                        if (v.size) {
                            if (v.size.ts) {
                                _height = 133;
                            }
                        }
                    }
                    var _width = "expression(this.width>=838?838:'auto')";
                    var photoUrl = mk_url('album/index/photoInfo', {'photoid':v.pid,dkcode:arg[1].dkcode});
                    picurl = fdfsHost + "/" + v.groupname + "/" + v.filename + size + "." + v.type;

                    str += '<li class="' + firstPhoto + ' ' + hidden + '" style="height:' + _height + 'px" ><a href="javascript:void(0);" action_dkcode="' + arg[1].dkcode + '" pid="' + v.pid + '" url="' + photoUrl + '" class="photoLink"><img src="' + picurl + '" alt="" style="max-width:838px;_width:' + _width + '"/></a></li>'; //height="'+height+'" width="'+width+'" 删除 by李世君 2012-4-1
                });
            }
            var msgname = arg[1].title || "";
            str += '</ul><div class="commentBox pd" commentObjId="' + commentObjId + '" msgname="' + msgname + '" pageType="' + pageType + '" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div></div></li>';
            var $str = $(str);

//            if (arg[2]) {
//                // 发布框的数据 需要判断日期 插入到临近节点。
//                var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);
//
//                if ($obj) {
//                    $obj.before($str);
//                } else {
//                    $content.append($str);
//                }
//            } else {
                $content.append($str);
//            }
            return $str;
            // this.event(["photoLink"],[$str]);
        },
        
            uinfo:function (arg) {
            var $content = arg[0].children("ul.content");
            var subtype = arg[1].subtype;
            var sideClass, clickDown, tipTxthighlight;

            if (arg[1].highlight == 0 || "") {
                // 小
                clickDown = "";
                tipTxthighlight = "放大";
                sideClass = "sideLeft";
            } else {
                // 大
                tipTxthighlight = "缩小";
                clickDown = "clickDown";
                sideClass = "twoColumn clearfix";
            }
            var midLine = "midLine";
            switch (subtype) {
                case "static": // 加入端口网
                    subtype = "lifeIcon_3";
                    midLine = "";
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

            var str = '<li id="' + arg[1].tid + '" name="timeBox" scale="true" highlight="' + arg[1].highlight + '" fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" time="' + arg[1].ctime + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><span class="conWrap ' + midLine + ' tip_up_left_black ' + clickDown + '" tip="' + tipTxthighlight + '"><a><i class="conResize"></i></a></span>';

            if (subtype != "lifeIcon_3") {
                str += '<span class="conWrap tip_up_right_black" tip="删除帖子"><a><i class="conEdit"></i></a><ul class="editMenu hide"><li name="delTopic"><i class="delTopic"></i>删除帖子...</li>  </ul></span>';
            }

            var msgname = arg[1].title || "";
            str += '</div><div class="lifeContent"><div class="lifeHeader"><i class="' + subtype + '"></i><div class="lifeTitle">' + arg[1].content + '<p class="subDesc">' + arg[1].info + '</p></div></div></div><div noForward="true" class="commentBox pd" msgname="' + msgname + '" commentObjId="' + arg[1].tid + '" pageType="uinfo" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div></div></li>';
            $content.append(str);
        },
            social:function(arg) {
            var $content = arg[0].children("ul.content");

            var sideClass, clickDown, tipTxthighlight;
            var follows = arg[1].follows || [],
                friends = arg[1].friends || [];
            var maxNum = 6;

            // 小
            clickDown = "";
            tipTxthighlight = "放大";
            sideClass = "sideRight";

            var location = mk_url("main/index/main",{dkcode:arg[1].dkcode});

            var str = '<li id="' + arg[1].tid + '" name="timeBox" fid="' + arg[1].fid +'" uid="' + arg[1].uid + '" class="' + sideClass + '" timearea="" type="' + arg[1].type + '"><!--<i class="spinePointer"></i>--><div class="timelineBox"><div class="hy-friendHead clearfix"><div class="avatar"><a href="' + location + '"><img src="' + self.avatar + '" height="32" width="32" /></div><div class="info"><a href="' + location + '">' + arg[1].uname + '</a><br><span>' + (arg[1].friendly_time || '最近') + '</span></div></div>';

            if(friends && friends.length) {
                str += '<h4 class="hy-friendTip">添加了 <a href="javascript:void(0);">' + arg[1].friends_num + ' 位好友</a></h4><div class="hy-friendSmallPanel"><ul class="hy-friendSmallLst">';

                //var l = (arg[1].friends_num > 6) ? 5 : friends.length;
                var l = (arg[1].friends_num > maxNum) ? (maxNum - 1) : friends.length;
                for(var i = 0; i < l; i ++) {
                    str += '<li><a href="' + mk_url("main/index/main",{dkcode:friends[i].dkcode}) + '" title="' + friends[i].name + '"><img src="' + friends[i].headpic + '" height="65" width="65" alt="' + friends[i].name + '" /></a></li>';
                }

                if(arg[1].friends_num > 6) {
                    str += '<li style="width:64px;"><a href="javascript:;" class="more social__more" type="social_friends">+' + (arg[1].friends_num - 5) + '</a></li>';
                }

                str += "</ul></div>";
            }

            if(follows && follows.length) {
                str += '<h4 class="hy-friendTip">添加了 <a href="javascript:void(0);">' + arg[1].follows_num + ' 位关注</a></h4><div class="hy-friendSmallPanel"><ul class="hy-friendSmallLst">';

                var l = (arg[1].follows_num > maxNum) ? (maxNum - 1) : follows.length;
                //var l = (arg[1].follows_num > 6) ? 5 : follows.length;
                for(var i = 0; i < l; i ++) {
                    str += '<li><a href="' + mk_url("main/index/main",{dkcode:follows[i].dkcode}) + '" title="' + follows[i].name + '"><img src="' + follows[i].headpic + '" height="65" width="65" alt="' + follows[i].name + '" /></a></li>';
                }

                if(arg[1].follows_num > 6) {
                    str += '<li style="width:64px;"><a href="javascript:;" class="more social__more" type="social_follows">+' + (arg[1].follows_num - 5) + '</a></li>';
                }

                str += "</ul></div>";
            }

            str +='</div></li>';

            var ctime = arg[1].ctime;
            var dateline = arg[1].dateline;

            var $str = $(str);

//            if (arg[2]) {
//                // 发布框的数据 需要判断日期 插入到临近节点。
//                var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);
//
//                if ($obj) {
//                    $obj.before($str);
//                } else {
//                    $content.append($str);
//                }
//            } else {
                $content.append($str);
//            }
            return $str;
        },
            friend:function(arg) {
            var $content = arg[0].children("ul.content");
            var sideClass, clickDown, tipTxthighlight;
            var friends = arg[1].friends || [];

            // 小
            clickDown = "";
            tipTxthighlight = "放大";
            sideClass = "sideLeft";

            var actionDkcode = (arg[3] || "");
            var location = mk_url('main/index/main',{dkcode:actionDkcode});
            var str = '<li id="' + arg[1].tid + '" name="timeBox" fid="' + arg[1].fid +'" uid="' + arg[1].uid + '" class="' + sideClass + '" timearea="" type="friend"><!--<i class="spinePointer"></i>--><div class="timelineBox"><div class="hy-friendHead clearfix"><div class="avatar"><a href="' + location + '"><img src="' + self.avatar + '" height="32" width="32" /></div><div class="info"><a href="' + location + '">' + (arg[2] || "") + '</a><br><span>' + (arg[1].friends_num || 0) + '个朋友</span><!--<a href="' + mk_url('main/following/index') + '" class="goLst">显示全部</a>--></div></div>';
            var maxNum = 8;

            if(friends && friends.length) {
                str += '<div class="hy-friendPanel"><ul class="hy-friendLst clearfix">';
                var l = (friends.length > maxNum) ? maxNum - 1 : friends.length;
                for(var i = 0; i < l; i ++) {
                    // var _theHref = webpath + 'main/index.php?c=index&m=index&action_dkcode=' + friends[i].dkcode;
                    var _theHref = mk_url('main/index/main',{dkcode:friends[i].dkcode});
                    str += '<li><a href="' + _theHref + '" title="' + friends[i].name + '"><img src="' + friends[i].headpic + '" height="99" width="99" alt="' + friends[i].name + '" /></a><span class="uName"><a href="' + _theHref + '">' + friends[i].name + '</a></span></li>';
                }

                if(friends.length > maxNum) {
                    str += '<li class="more"><a type="friends" class="social__more" href="javascript:;">+' + (arg[1].friends_num - l) + '</a></li>';
                }

                str += "</ul></div>";
            }

            str +='</div></li>';

            var ctime = arg[1].ctime;
            var dateline = arg[1].dateline;

            var $str = $(str);
//            if (arg[2]) {
//                // 发布框的数据 需要判断日期 插入到临近节点。
//                var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);
//
//
//                if ($obj) {
//                    $obj.before($str);
//                } else {
//                    $content.append($str);
//                }
//            } else {
                $content.append($str);
//            }
            return $str;
        },
            followWeb:function(arg) {
            var $content = arg[0].children("ul.content");
            var sideClass, clickDown, tipTxthighlight;
            var follows = arg[1].follows || [];

            // 小
            clickDown = "";
            tipTxthighlight = "放大";
            sideClass = "sideLeft";

            //var location = webpath + "main/index.php?c=index&m=index&action_dkcode=" + arg[1].dkcode;
            var location = mk_url("main/index/main",{dkcode:arg[1].dkcode});

            var str = '<li id="' + arg[1].tid + '" name="timeBox" fid="' + arg[1].fid +'" uid="' + arg[1].uid + '" class="' + sideClass + '" timearea="" type="' + arg[1].type + '"><i class="spinePointer"></i><div class="timelineBox"><div class="hy-friendHead clearfix"><div class="avatar"><a href="' + location + '"><img src="' + self.avatar + '" height="32" width="32" /></div><div class="info"><a href="' + location + '">' + arg[1].uname + '</a><br><span>' + (arg[1].friendly_time || '最近') + '</span></div></div>';

            if(follows && follows.length) {
                str += '<h4 class="hy-friendTip">关注了 <a href="javascript:void(0);">' + arg[1].follows_num + ' 个网页</a></h4><div class="hy-friendSmallPanel"><ul class="hy-friendSmallLst">';

                var l = (arg[1].follows_num > 6) ? 5 : follows.length;
                for(var i = 0; i < l; i ++) {
                    str += '<li><a href="' + follows[i].web_url + '" title="' + follows[i].name + '"><img src="' + follows[i].headpic + '" height="65" width="65" alt="' + follows[i].name + '" /></a></li>';
                }

                if(arg[1].follows_num > 6) {
                    str += '<li style="width:64px;"><a href="#" class="more">+' + (arg[1].follows_num - 5) + '</a></li>';
                }

                str += "</ul></div>";
            }

            str +='</div></li>';

            var ctime = arg[1].ctime;
            var dateline = arg[1].dateline;

            var $str = $(str);
//            if (arg[2]) {
//                // 发布框的数据 需要判断日期 插入到临近节点。
//                var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);
//
//
//                if ($obj) {
//                    $obj.before($str);
//                } else {
//                    $content.append($str);
//                }
//            } else {
                $content.append($str);
//            }
            return $str;
        },
            change:function(arg) {
            //console.log("\n---yzy---\n");
            //console.log(arg);
            //console.log("\n---yzy---\n");
            var $content = arg[0].children("ul.content");
            var subtype = arg[1].subtype;
            var sideClass, clickDown, tipTxthighlight;

            //姚智译修改
            //0改成 "0"
            if (arg[1].highlight == "0" || "") {
                // 小
                clickDown = "";
                tipTxthighlight = "放大";
                sideClass = "sideLeft";
            } else {
                // 大
                tipTxthighlight = "缩小";
                clickDown = "clickDown";
                sideClass = "twoColumn clearfix";
            }

            //单元判定
            if(arg[1].union == 'face') info = '上传了新的头像';
            else if(arg[1].union == 'cover') info = '上传了新的封面';
            else return '';

            //var location = webpath + "main/index.php?c=index&m=index&action_dkcode=" + arg[1].dkcode;
            var location = mk_url("main/index/main",{dkcode:arg[1].dkcode});

            var str = '<li id="' + arg[1].tid + '" name="timeBox" scale="true"  fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><span class="conWrap midLine tip_up_left_black ' + clickDown + '" tip="' + tipTxthighlight + '"><a><i class="conResize"></i></a></span><span class="conWrap tip_up_right_black" tip="编辑或删除"><a><i class="conEdit"></i></a><ul class="editMenu hide">';

            str += '<li name="delTopic"><i class="delTopic"></i>删除帖子...</li></ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + self.avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a> <span style="font-weight:100;">' + info + '</span>';
            str += '</span></div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a>';
            var ctime = arg[1].ctime;
            var dateline = arg[1].dateline;


//            var friendly_dateline = this.cpu(["returnFriendly_date"], [ctime, dateline]);
//            if (friendly_dateline) {
//                str += '<i class="insertTime tip_up_left_black" tip="' + friendly_dateline + '"></i>';
//            }
            str += '</div></div></div><div class="mediaContent clearfix" style="height:auto !important; margin:0px; padding:14px;">';
            str += '<a href="javascript:void(0);" action_dkcode="' + arg[1].dkcode + '" pid="' + arg[1].fid + '" url="' + mk_url('album/index/photoInfo',{photoid:arg[1].fid,dkcode:arg[1].dkcode}) + '" class="photoLink"><img src="' + fdfsHost + "/" + arg[1].imgurl + '" style="max-width:375px; _width:expression(this.width>=375?375:\'auto\');" alt="" /></a>';
            showFlashImgT = "29px";
            showFlashImgL = "67px";

            var msgname = arg[1].title || "";

            str += '</div><!--<div class="commentBox pd" msgname="' + msgname + '" commentObjId="' + arg[1].tid + '" pageType="'+arg[1].type+'" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div>--></div></li>';

            var $str = $(str);

//            if (arg[2]) {
//                // 发布框的数据 需要判断日期 插入到临近节点。
//                var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);
//
//                if ($obj) {
//                    $obj.before($str);
//                } else {
//                    $content.append($str);
//                }
//            } else {
                $content.append($str);
//            }
            return $str;
        },
            sharevideo:function(arg) {
            var $content = arg[0].children("ul.content");
            var subtype = arg[1].subtype;
            var sideClass, clickDown, tipTxthighlight;

            if (arg[1].highlight == 0 || "") {
                // 小
                clickDown = "";
                tipTxthighlight = "放大";
                sideClass = "sideLeft";
            } else {
                // 大
                tipTxthighlight = "缩小";
                clickDown = "clickDown";
                sideClass = "twoColumn clearfix";
            }

            var location = mk_url("main/index/main",{dkcode:arg[1].dkcode});

            var str = '<li id="' + arg[1].tid + '" name="timeBox" scale="true"  fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><span class="conWrap midLine tip_up_left_black ' + clickDown + '" tip="' + tipTxthighlight + '"><a><i class="conResize"></i></a></span><span class="conWrap tip_up_right_black" tip="编辑或删除"><a><i class="conEdit"></i></a><ul class="editMenu hide">';

            if (String(arg[1].from) == "1") {
                str += '<li name="changeDate"><i class="changeDate"></i>更改日期...</li><li class="sepLine"></li>';
            }
            str += '<li name="delTopic"><i class="delTopic"></i>删除帖子...</li></ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + self.avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a> <span style="font-weight:100;">分享了视频</span>';
            /*
             if (String(arg[1].from) == "1") {
             // 不需要提示文字；
             } else {
             str += '<span class="subTip">分享了<a href="' + arg[1].url + '">视频</a>';
             }*/
            str += '</span></div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a>';
            var ctime = arg[1].ctime;
            var dateline = arg[1].dateline;


//            var friendly_dateline = this.cpu(["returnFriendly_date"], [ctime, dateline]);
//            if (friendly_dateline) {
//                str += '<i class="insertTime tip_up_left_black" tip="' + friendly_dateline + '"></i>';
//            }
            str += '</div></div></div><div class="infoContent" style="font-size:13px; padding-bottom:8px;"><a href="' + arg[1].url + '" target="_blank">' + arg[1].content + '</a></div><div class="mediaContent" style="height:auto !important; height:80px; min-height:80px; width:128px;"><div class="media_prev" style="padding:0px 0px 10px 15px;">';
            var showFlashImgT, showFlashImgL;
            str += '<img onerror="this.src=\'' + (CONFIG.misc_path + "img/default/video_err1.jpg") + '\'" src="' +  mk_videoPicUrl(arg[1].imgurl)  + '" width="128" height="80" alt="" />';
            showFlashImgT = "29px";
            showFlashImgL = "67px";

            var msgname = arg[1].title || "";

            str += '<a class="showFlash" href="javascript:void(0);"><img alt="" src="' + CONFIG.misc_path + 'img/system/feedvideoplay_small.gif" style="height:23px;top:' + showFlashImgT + ';left:' + showFlashImgL + ';"></a></div><div class="media_disp hide" ><div id="' + arg[1].fid + arg[1].tid + '"></div></div></div><div class="commentBox pd" msgname="' + msgname + '" commentObjId="' + arg[1].tid + '" pageType="' + arg[1].type + '" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div></div></li>';

            var $str = $(str);

//            if (arg[2]) {
//                // 发布框的数据 需要判断日期 插入到临近节点。
//                var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);
//
//                if ($obj) {
//                    $obj.before($str);
//                } else {
//                    $content.append($str);
//                }
//            } else {
                $content.append($str);
//            }
            return $str;
        },
            join:function(arg) {
            var $content = arg[0].children("ul.content");
            var sideClass, clickDown, tipTxthighlight;
            var events = arg[1].events;

            if (arg[1].highlight == 0 || "") {
                // 小
                clickDown = "";
                tipTxthighlight = "放大";
                sideClass = "sideLeft";
            } else {
                // 大
                tipTxthighlight = "缩小";
                clickDown = "clickDown";
                sideClass = "twoColumn clearfix";
            }

            var location = mk_url("main/index/main",{dkcode:arg[1].dkcode});
            var str = '<li id="' + arg[1].tid + '" name="timeBox" scale="true"  fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" class="' + sideClass + '"><!--<i class="spinePointer"></i>--><div class="timelineBox"><div class="hy-friendHead clearfix"><div class="avatar"><a href="' + location + '"><img src="' + self.avatar + '" height="32" width="32" /></div><div class="info"><a href="' + location + '" style="font-size:12px;"><b>' + arg[1].uname + '</b></a><b>参加了' + arg[1].events_num + '个活动</b><br /><span>' + arg[1].friendly_time + '</span</div></div></div><div class="infoContent clearfix">';

            for(var i = 0,l = events.length; i < l; i ++) {
                str += '<div class="infoContent clearfix" style="padding:5px 0px; border-bottom:1px solid #ddd;"><a class="eventPic" style="margin:0px 10px 0px 0px;" href="#"><img width="50" height="50" src="' + (events[i].cover || (CONFIG.misc_path) + 'img/default/event.jpg') + '" alt=""></a><div class="eventInfo" style="margin-top:0px;"><h4><a href="#">' + events[i].title + '</a></h4><span>' + events[i].join_time + '</span></div></div>';
            }

            if(arg[1].events_num > events.length) {
                str += '<div class="hy-answerMore"><a href="javascript:;" class="J_showMoreEvent">其他 ' + (arg[1].events_num - l) + ' 条</a></div>';
            }

            str += '</div></div></li>';

            var $str = $(str);

//            if (arg[2]) {
//                // 发布框的数据 需要判断日期 插入到临近节点。
//                var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);
//
//                if ($obj) {
//                    $obj.before($str);
//                } else {
//                    $content.append($str);
//                }
//            } else {
                $content.append($str);
//            }
            return $str;
        },
            answer:function(arg) {
            var $content = arg[0].children("ul.content");

            var sideClass, clickDown, tipTxthighlight;

            if (arg[1].highlight == 0 || "") {
                // 小
                clickDown = "";
                tipTxthighlight = "放大";
                sideClass = "sideLeft";
            } else {
                // 大
                tipTxthighlight = "缩小";
                clickDown = "clickDown";
                sideClass = "twoColumn clearfix";
            }

            var location = mk_url("main/index/main",{dkcode:arg[1].dkcode});
            var str = '<li id="' + arg[1].tid + '" name="timeBox" scale="true"  fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" class="' + sideClass + '"><div class="timelineBox"><div class="hy-friendHead clearfix"><div class="avatar"><a href="' + location + '"><img src="' + self.avatar + '" height="32" width="32" /></a></div><div class="info"><a href="' + location + '" style="font-size:12px;"><strong>' + (arg[1].uname || "") + '</strong></a><b>回答了' + arg[1].question_num + '个问题</b><br><span>' + arg[1].start_time + "-" + arg[1].end_time + '</span></div></div><div class="infoContent clearfix"><ul class="hy-answerLst">';

            var defaultNum = 3;
            var l = (arg[1].questions.length > defaultNum) ? defaultNum : arg[1].questions.length;
            for(var i = 0; i < l; i ++) {
                var questionTitle = arg[1].questions[i].title;
                var answersInfo = arg[1].questions[i].answers.join(" · ");


                //questionTitle = (questionTitle.length > 25) ? questionTitle.substring(0,22) + "...？" : questionTitle;
                questionTitle = arg[1].questions[i].title
                answersInfo = (answersInfo.length > 33) ? answersInfo.substring(0,30) + "..." : answersInfo;

                str += '<li question_id="' + arg[1].questions[i].id + '"><h4 class="hy-answerTitle" style="line-height:16px;"><a onclick="$(this).showAsk({ispopBox:true,poll_id:\'' + arg[1].questions[i].id + '\'})" href="javascript:;" title="' + arg[1].questions[i].title_full + '" style="font-size:12px;">' + questionTitle + '</a></h4><p class="hy-answerContent" title="' + arg[1].questions[i].answers.join(" · ") + '">' + answersInfo + '</p></li>';
            }
            str += '</ul>';

            if(arg[1].question_num > defaultNum) {
                str += '<div class="hy-answerMore"><a href="javascript:;">其他 ' + (arg[1].question_num - l) + ' 条</a></div>';
            }

            str += '</div></div></li>';

            var $str = $(str);
            $str.find("ul.hy-answerLst li").last().css({"border-bottom":"none"});

//            if (arg[2]) {
//                // 发布框的数据 需要判断日期 插入到临近节点。
//                var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);
//
//                if ($obj) {
//                    $obj.before($str);
//                } else {
//                    $content.append($str);
//                }
//            } else {
                $content.append($str);
//            }
            return $str;
        },
            current_month_top:function(arg) {
            var $content = arg[0].children("ul.content");

            var sideClass, clickDown, tipTxthighlight;
            var follows = arg[1].follows || [],
                friends = arg[1].friends || [],
                followWeb = arg[1].webs || [];
            var maxNum = 6;

            // 小
            clickDown = "";
            tipTxthighlight = "放大";
            sideClass = "sideRight";

            var location = mk_url("main/index/main",{dkcode:arg[1].dkcode});

            var str = '<li id="' + arg[1].tid + '" tid="' + arg[1].tid + '" fid="' + arg[1].fid +'" uid="' + arg[1].uid + '" class="' + sideClass + '" timearea="" type="current_month_top" time="0"><div class="timelineBox"><div class="hy-friendHead clearfix"><div class="avatar"><a href="' + location + '"><img src="' + self.avatar + '" height="32" width="32" /></div><div class="info"><a href="' + location + '">' + arg[1].uname + '</a><br><span>' + (arg[1].friendly_time || '最近') + '</span></div></div>';

            // 好友
            if(friends && friends.length) {
                str += '<h4 class="hy-friendTip">添加了 ' + arg[1].friends_num + ' 位好友</h4><div class="hy-friendSmallPanel"><ul class="hy-friendSmallLst">';

                var l = (arg[1].friends_num > maxNum) ? (maxNum - 1) : friends.length;
                for(var i = 0; i < l; i ++) {
                    str += '<li><a href="' + mk_url("main/index/main",{dkcode:friends[i].dkcode}) + '" title="' + friends[i].name + '"><img src="' + friends[i].headpic + '" height="65" width="65" alt="' + friends[i].name + '" /></a></li>';
                }

                if(arg[1].friends_num > maxNum) {
                    str += '<li style="width:64px;"><a href="javascript:;" class="more social__more" type="social_friends">+' + (arg[1].friends_num  - maxNum + 1) + '</a></li>';
                }

                str += "</ul></div>";
            }

            // 关注
            if(follows && follows.length) {
                str += '<h4 class="hy-friendTip">添加了 ' + arg[1].follows_num + ' 位关注</h4><div class="hy-friendSmallPanel"><ul class="hy-friendSmallLst">';

                var l = (arg[1].follows_num > maxNum) ? (maxNum - 1) : follows.length;
                for(var i = 0; i < l; i ++) {
                    str += '<li><a href="' + mk_url("main/index/main",{dkcode:follows[i].dkcode}) + '" title="' + follows[i].name + '"><img src="' + follows[i].headpic + '" height="65" width="65" alt="' + follows[i].name + '" /></a></li>';
                }

                if(arg[1].follows_num > maxNum) {
                    str += '<li style="width:64px;"><a href="javascript:;" class="more social__more" type="social_follows">+' + (arg[1].follows_num - maxNum + 1) + '</a></li>';
                }

                str += "</ul></div>";
            }

            // 关注网页
            if(followWeb && followWeb.length) {
                str += '<h4 class="hy-friendTip">关注了 ' + arg[1].webs_num + ' 个网页</h4><div class="hy-friendSmallPanel"><ul class="hy-friendSmallLst">';

                var l = (arg[1].webs_num > maxNum) ? (maxNum - 1) : followWeb.length;
                for(var i = 0; i < l; i ++) {
                    str += '<li><a href="' + followWeb[i].web_url + '" title="' + followWeb[i].name + '"><img src="' + followWeb[i].headpic + '" height="65" width="65" alt="' + followWeb[i].name + '" /></a></li>';
                }

                if(arg[1].webs_num > maxNum) {
                    str += '<li style="width:64px;"><a href="javascript:;" class="more social__more" type="followWeb">+' + (arg[1].webs_num - maxNum + 1) + '</a></li>';
                }

                str += "</ul></div>";
            }

            str +='</div></li>';

            var ctime = arg[1].ctime;
            var dateline = arg[1].dateline;
            var $str = $(str);
            var height = 48 + $str.find("ul.hy-friendSmallLst").size() * 95;

            $str.find("div.timelineBox").css({height:height});

//            if (arg[2]) {
//                // 发布框的数据 需要判断日期 插入到临近节点。
//                var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);
//
//                if ($obj) {
//                    $obj.before($str);
//                } else {
//                    $content.append($str);
//                }
//            } else {
                $content.append($str);
//            }
            return $str;
        },
            hot:function(arg) {
        },
            birth:function (arg) {
            var time = arg[1].time.slice(0, 4);
            var str = '<li id="' + arg[1].id + '" name="timeBox"  time="' + arg[1].time + '" class="twoColumn clearfix"><i class="spinePointer"></i><div class="timelineBox" style="margin:0px">' + arg[1].content + '</div></li>'
            var $content = arg[0].children("ul.content");
            $content.children("div").remove();
            $content.append(str);
            //this.hMethod[p.attr("id")] = p.offset().top;
            //this.hMethod[arg[1].id] = p.next().find("i").offset().top
        }
		};
        var fn;
		$.each(method,function(index,value){
			if(value){
				return fn = _class[value](arg);
			}
		});
        return fn;
	},
	event:function(method,arg){
		var self = this;
		var _class={
			removeLoading:function(arg){
				arg[0].find(".loading").remove();
			},
			delInfo:function(arg){
				var user_home= $("#hd_userPageUrl").val();
				
				var $msg = "<div style='padding: 10px;'><p>是否确定删除该信息？</p></div>";
				var del_trigger = arg[1].find("i.del_info");
				
				  del_trigger.click(function(){
				  	  var $li = $(this).closest("li[name=timeBox]");
					  var tid = $li.attr("id");
               	      var fid = $li.attr("fid");
                      var data = {};
                      if(fid){
                        data.fid = fid;
                   	   }
	                  data.tid = tid;
                    self.plug(["popUp"],[del_trigger,$msg,"提示信息",function(){
                       	self.model('del',[data,function(data){
									if(data.status!=0){
										$.closePopUp();
										window.location.href = user_home;
										return false;
									}
				                }
				            ]
						);
                    },'<span class="popBtns blueBtn callbackBtn">确定</span><span class="popBtns closeBtn">取消</span>']);
                    
                });
				
					
			},
            forward:function(arg){
                var p = arg[0].closest("li[name=timeBox]")
                var name1 = p.find(".AuthorName").children("a").text();
                var name2;
                var name3;
                var value,$content,imgurl;

                var forwardid = p.attr("forwardid");
                var pp = p.find("li#"+forwardid);

                if(p.find(".forwardContent").size()==0){
                    value = p.find(".infoContent").html();
                }else{
                    value = p.find(".forwardContent").find(".memo").html()||"";
      
                }
                if(p.find(".oldAuthorName").size()!=0){
                    name2 = p.find(".oldAuthorName").children("a").text().replace(":","")||"";
                    name3 = name2;
                }else{
                    name3 = name1;
                }

                var typeFunction =  function(type){
                    switch(type){
                        case "info":
                             
                             $content = $('<div class="laymoveText"><div class="zf_content"><textarea maxlength="140"></textarea><div class="content"><p>'+value+'</p><p>由<span class="name"><a>'+name3+'</a></span>发布</p></div></div><div class="replyFor"><p><input type="checkbox" id="replyCheck"><label for="replyCheck">同时评论给 '+name1+'</label></p></div></div>');
                        break;
                        case "album":
                             
                             imgurl = p.find("ul.photoContent").find("img").attr("src");
                             $content = $('<div class="laymoveText"><div class="zf_content"><textarea maxlength="140"></textarea><div class="content"><div class="left"><img src="'+imgurl+'" width="90" height="60" /></div><div class="right"><p>'+value+'</p><p>由<span class="name"><a>'+name3+'</a></span>发布</p></div></div></div><div class="replyFor"><p><input type="checkbox" id="replyCheck"><label for="replyCheck">同时评论给 '+name1+'</label></p></div></div>');
                        break;
                        case "video":
                            
                             imgurl = p.find("div.mediaContent").find("img").attr("src");
                             $content = $('<div class="laymoveText"><div class="zf_content"><textarea maxlength="140"></textarea><div class="content"><div class="left"><img src="'+imgurl+'" width="168" height="90" /></div><div class="right"><p>'+value+'</p><p>由<span class="name"><a>'+name3+'</a></span>发布</p></div></div></div><div class="replyFor"><p><input type="checkbox" id="replyCheck"><label for="replyCheck">同时评论给 '+name1+'</label></p></div></div>');
                        break;
                    }
                    return $content;
                }
                
                if(p.attr("type")=="forward"){
                    if(p.attr("forwardType")&&p.attr("forwardType")!="undefined"){
                        $content = typeFunction(p.attr("forwardType"));
                    }else{
                       self.plug(["popUp"],[p,'<div style="padding:10px">原始信息已被删除！无法进行操作</div>',"提示",function(){
                        $.closePopUp();
                       },'<span class="popBtns blueBtn callbackBtn">知道了</span>',400]);                      
                       return false;
                    }
                     $content.find("div.replyFor").append('<p><input type="checkbox" id="replyCheckOld"><label for="replyCheckOld">同时评论给原作者 '+name2+'</label></p>');
                }else{
                   $content = typeFunction(p.attr("type"));
                }
                   
                self.plug(["popUp"],[arg[0],$content,"分享",function(){
                    var data = {}
                    data.content = $content.find("textarea").val();
                    data.tid = p.attr("id");
                    if(p.attr("forwardId")&&p.attr("forwardId")!="undefined"){
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
                        if(result.status!=0){
                            var num = p.find(".forwardNum").text();
                            p.find(".forwardNum").text(parseInt(num)+1);
                            //原信息分享数+1
                            if(pp.size()!=0){
                                num = pp.find(".forwardNum").text();
                                pp.find(".forwardNum").text(parseInt(num)+1);
                            }
               
                            self.plug(["popUp"],[arg[0],'<div style="padding:10px">分享成功!</div>',"提示",function(){
                            $.closePopUp();
                           },'<span class="popBtns blueBtn callbackBtn">知道了</span>',400]);
                        }
                        $.closePopUp();
                    }]);
                },'<span class="popBtns blueBtn callbackBtn">分享</span><span class="popBtns closeBtn">取消</span>']);
                limitStrNum($content.find("textarea"));
            }
			
		}
		$.each(method,function(index,value){
			if(value){
				return _class[value](arg);
			}
		});
	},
	plug:function(method,arg){
		var self = this;
		var _class = {
			tip_up_left_black:function(arg){
                arg[0].find(".tip_up_left_black").tip({
                    direction:"up",
                    position:"left",
                    skin:"black",
                    clickHide:true,
                    key:1
                });
            },
			commentEasy:function(arg){
                arg[0].find('.commentBox:not(.hasComment)').commentEasy({
                    minNum:3,
                    UID:CONFIG['u_id'],
					userName:CONFIG['u_name'],
					avatar:CONFIG['u_head'],
                    userPageUrl:self.infoType=="web"?self.web_home:$("#hd_userPageUrl").val(),
                    relay:!0,
                    relayCallback:function (obj,_arg) {
                        var comment=new ui.Comment();
                        comment.share(obj,_arg,!0);
                    }
//                    comment_path:self.infoType=="web"?"web_comment":null,
//                    relayCallback:function(obj){
//                        self.event(["forward"],[obj]);
//                    }

                });
            },
			popUp:function(arg){
                arg[0].popUp({
                    width:arg[5]||580,
                    title:arg[2],
                    content:arg[1],
                    buttons:arg[4]||'<span class="popBtns blueBtn callbackBtn">确定</span><span class="popBtns closeBtn">取消</span>',
                    mask:true,
                    maskMode:true,
                    callback:arg[3]
                });
            }

		}
		$.each(method,function(index,value){
			if(value){
				return _class[value](arg);
			}
		});
	},
    model:function(method,arg){
        var self = this;
        var _class={
			//分享
            doShare:function(arg){
                $.djax({
                    url:mk_url("main/info/doShare"),
                    async:true,
                    dataType:"jsonp",
                    data:arg[0],
                    success:function(data){
                        if(data){
                        arg[1](data);
                        }
                    }
                });
            },
			//获取数据
            getdata:function(arg){
                $.djax({
                    url:mk_url('main/info/ajaxView'),
                    async:true,
                    dataType:"json",
                    data:arg[0],
                    success:function(data){
                        if(data){
                        arg[1](data);
                        }
                    }
                });
            },
			//删除
			del:function(arg){
				$.djax({
					url:mk_url("main/info/doDelTopic"),
					dataType:"json",
					data:arg[0],
					success:function(data){
						arg[1](data)
					}
				});
			}

        }
        return _class[method](arg);
    }
}
$(document).ready(function(){
    class_oneinfo = new CLASS_ONEINFO();
    class_oneinfo.init();
});