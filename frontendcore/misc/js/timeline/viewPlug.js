/**
 * Created by hcj.
 * Date: 12-6-18
 * Time: 下午5:58
 * To change this template use File | Settings | File Templates.
 * use : for view
 */

View.prototype.plug.blog=function (arg) {
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
        var str = '<li name="timeBox" scale="true" id="' + arg[1].tid + '"  fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><span class="conWrap midLine tip_up_left_black ' + clickDown + '" tip="' + tipTxthighlight + '"><a><i class="conResize"></i></a></span><span class="conWrap tip_up_right_black" tip="编辑或删除"><a><i class="conEdit"></i></a><ul class="editMenu hide"><li name="changeDate"><i class="changeDate"></i>更改日期...</li><li class="sepLine"></li><li name="delTopic"><i class="delTopic"></i>删除帖子...</li> </ul></span></div><div class="headBlock clearfix"><a href="" class="headImg"><img src="' + this.avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a>';
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

        if (arg[2]) {
            // 发布框的数据 需要判断日期 插入到临近节点。
            var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);

            if ($obj) {
                $obj.before($str);
            } else {
                $content.append($str);
            }
        } else {
            $content.append($str);
        }
        return $str;
    };
View.prototype.plug.forward=function (arg) {
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
                    var blogUrl = mk_url('blog/blog/main',{'id':arg[1].forward.fid,'dkcode':arg[1].forward.dkcode});
                    var blogTitle = arg[1].title;
                    blogTitle = blogTitle.length > 23 ? blogTitle.substring(0,20) + "..." : blogTitle;
                    forwardContent = '<div class="forwardContent"><p style="margin:0; padding:10px 0px; font-size:14px;"><a href="' + blogUrl + '" title="' + arg[1].title + '"><strong>' + blogTitle + '</strong></a></p><span class="memo" style="margin:0; line-height:1.5;">' + arg[1].forward.content + '</span><p style="margin:0; padding:10px 0px 0px;"><a href="' + blogUrl + '">继续阅读......</a></p></div>';

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
                        temp += '<img onerror="this.src=\'' + (CONFIG.misc_path + "img/default/video_err1.jpg") + '\'" src="' + mk_videoPicUrl(arg[1].forward.imgurl) + '" width="838" height="600" alt="" />';
                        showFlashImgT = "300px";
                        showFlashImgL = "407px";
                    }

                    typeHtml = '分享了<span class="oldAuthorName" uid="' + arg[1].forward.uid + '"><a href="' + location + '">' + arg[1].forward.uname + '</a></span>的<a href="' + mk_url('video/video/player_video',{vid:arg[1].forward.fid}) + '">视频</a>';

                    temp += '<a class="showFlash" href="javascript:void(0);"><img alt="" src="' + CONFIG.misc_path + '/img/system/feedvideoplay.gif" style="left:' + showFlashImgL + ';top:' + showFlashImgT + ';"></a></div><div class="media_disp hide" ><div id="video_' + arg[1].forward.fid + arg[1].tid + '"></div></div></div>';
                    forwardContent = '<div class="forwardContent"><span class="memo" style="margin:0">' + arg[1].forward.content + '</span></div>' + temp;
                    break;
                    
                case "sharevideo":
                    var temp = "";
                    temp = '<div class="mediaContent" style="height:auto !important; height:80px; min-height:80px; width:128px;"><div class="media_prev" videourl="' + arg[1].forward.videourl + '" url="' + arg[1].forward.url + '" style="padding:0px 0px 10px 15px;">';

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
        var str = '<li name="timeBox" scale="true" id="' + arg[1].tid + '"  fid="' + arg[1].fid + '" forwardId="' + ftid + '" forwardType="' + ftype + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><span class="conWrap midLine tip_up_left_black ' + clickDown + '" tip="' + tipTxthighlight + '"><a><i class="conResize"></i></a></span><span class="conWrap tip_up_right_black" tip="编辑或删除"><a><i class="conEdit"></i></a><ul class="editMenu hide"><li name="changeDate"><i class="changeDate"></i>更改日期...</li><li class="sepLine"></li><li name="delTopic"><i class="delTopic"></i>删除帖子...</li>    </ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + this.avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a>';

        var msgname = arg[1].title || "";
        str += '<span class="subTip">' + typeHtml + '</span>';
        str += '</div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a></div></div></div><div class="infoContent">' + arg[1].content + '</div>' + forwardContent + '<div class="commentBox pd" msgname="' + msgname + '" commentObjId="' + arg[1].tid + '" pageType="' + arg[1].type + '" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div></div></li>';


        var $str = $(str);

        if (arg[2]) {
            // 发布框的数据 需要判断日期 插入到临近节点。
            var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);

            if ($obj) {
                $obj.before($str);
            } else {
                $content.append($str);
            }
        } else {
            $content.append($str);
        }
        return $str;
        //this.event(["photoLink"],[$str]);
    };
View.prototype.plug.event=function (arg) {
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
        var str = '<li id="' + arg[1].tid + '" name="timeBox" scale="true"  fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><span class="conWrap midLine tip_up_left_black ' + clickDown + '" tip="' + tipTxthighlight + '"><a><i class="conResize"></i></a></span><span class="conWrap tip_up_right_black" tip="编辑或删除"><a><i class="conEdit"></i></a><ul class="editMenu hide"><li name="delTopic"><i class="delTopic"></i>删除帖子...</li></ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + this.avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a><span class="subTip">创建了<a href="' + eventUrl + '">活动</a></span></div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a></div></div></div><div class="infoContent clearfix"><a class="eventPic" href="' + eventUrl + '"><img width="50" height="50" src="' + arg[1].photo + '" alt=""></a><div class="eventInfo"><h4><a href="' + eventUrl + '">' + arg[1].title + '</a></h4><span>' + arg[1].starttime + '</span></div></div></div></li>';


        var $str = $(str);

        if (arg[2]) {
            // 发布框的数据 需要判断日期 插入到临近节点。
            var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);

            if ($obj) {
                $obj.before($str);
            } else {
                $content.append($str);
            }
        } else {
            $content.append($str);
        }
        return $str;
    };
View.prototype.plug.album=function (arg) {
    var $content = arg[0].children("ul.content");

    var picurl;

    var typeHref;
    var size, photoClass = "";

    var albumText = "";
    var sideClass, clickDown, tipTxthighlight;
    var pageType = "album", commentObjId;

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
    str += '<li name="delTopic"><i class="delTopic"></i>删除帖子...</li> </ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + this.avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a>';
    if (parseInt(arg[1].fid) > 10000000) {
        albumText = "照片";
        //pageType = "photo"

        commentObjId = arg[1].picurl[0].pid;
    } else {
        albumText = "相册";
        // pageType = "album";
        commentObjId = arg[1].fid;

    }
    if (arg[1].from != 2) {
        // pageType = "photo"

    } else {

        // typeHref = webpath + "single/album/index.php?c=index&m=photoLists&action_dkcode=" + arg[1].dkcode + "&albumid=" + arg[1].note;
        //typeHref = mk_url("album/index/photoLists",{dkcode: arg[1].dkcode,albumid:arg[1].fid});
        //typeHref = mk_url('album/index/photoLists',{'albumid':arg[1].note,'dkcode':arg[1].dkcode);
        str += '<span class="subTip">上传了<a href="' + typeHref + '">' + albumText + '</a></span>';
    }
    typeHref = mk_url('album/index/photoLists',{'albumid':arg[1].note,'dkcode':arg[1].dkcode});
    str += '</span></div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a>';
    if (arg[1].from != 2) {
        var ctime = arg[1].ctime;
        var dateline = arg[1].dateline;


        var friendly_dateline = this.cpu(["returnFriendly_date"], [ctime, dateline]);
        if (friendly_dateline) {
            str += '<i class="insertTime tip_up_left_black" tip="' + friendly_dateline + '"></i>';
        }
    }

    str += '</div></div></div><div class="infoContent"><a href=' + typeHref + '>' + arg[1].title + '(' + arg[1].photonum + ')</a></div><ul class="photoContent clearfix">';

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
            picurl = fdfsHost + "/" + v.groupname + "/" + v.filename + size + "." + v.type;

            str += '<li class="' + firstPhoto + ' ' + hidden + '"><a href="javascript:void(0);" action_dkcode="' + arg[1].dkcode + '" pid="' + v.pid + '" url="' + mk_url('album/index/photoInfo',{photoid:v.pid,dkcode:arg[1].dkcode}) + '" class="photoLink"><img src="' + picurl + '" alt="" style="max-width:838px;_width:' + _width + '"/></a></li>'; //height="'+height+'" width="'+width+'" 删除 by李世君 2012-4-1
        });
    }
    var msgname = arg[1].title || "";
    str += '</ul><div class="commentBox pd" commentObjId="' + commentObjId + '" msgname="' + msgname + '" pageType="' + pageType + '" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div></div></li>';
    var $str = $(str);

    if (arg[2]) {
        // 发布框的数据 需要判断日期 插入到临近节点。
        var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);

        if ($obj) {
            $obj.before($str);
        } else {
            $content.append($str);
        }
    } else {
        $content.append($str);
    }
    return $str;
    // this.event(["photoLink"],[$str]);
};
View.prototype.plug.photo=function (arg) {
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
    str += '<li name="delTopic"><i class="delTopic"></i>删除帖子...</li> </ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + this.avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a>';
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

    /*if (arg[1].from != 2) {
        pageType = "photo"

    } else {

        // typeHref = webpath + "single/album/index.php?c=index&m=photoLists&action_dkcode=" + arg[1].dkcode + "&albumid=" + arg[1].note;
        typeHref = mk_url("album/index/photoLists",{dkcode: arg[1].dkcode,albumid:arg[1].note});
        str += '<span class="subTip">上传了<a href="' + typeHref + '">' + albumText + '</a></span>';
    }*/
    str += '</span></div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a>';
    if (arg[1].from != 2) {
        var ctime = arg[1].ctime;
        var dateline = arg[1].dateline;


        var friendly_dateline = this.cpu(["returnFriendly_date"], [ctime, dateline]);
        if (friendly_dateline) {
            str += '<i class="insertTime tip_up_left_black" tip="' + friendly_dateline + '"></i>';
        }
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

    if (arg[2]) {
        // 发布框的数据 需要判断日期 插入到临近节点。
        var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);

        if ($obj) {
            $obj.before($str);
        } else {
            $content.append($str);
        }
    } else {
        $content.append($str);
    }
    return $str;
    // this.event(["photoLink"],[$str]);
};
View.prototype.plug.ask=function (arg) {
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

        var str = '<li name="timeBox" scale="true" id="' + arg[1].tid + '"  fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><span class="conWrap midLine tip_up_left_black ' + clickDown + '" tip="' + tipTxthighlight + '"><a><i class="conResize"></i></a></span><span class="conWrap tip_up_right_black" tip="编辑或删除"><a><i class="conEdit"></i></a><ul class="editMenu hide"><li name="delTopic"><i class="delTopic"></i>删除帖子...</li></ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + this.avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a> <span class="subTip">提出了<a href="javascript:;" onclick="$(this).showAsk({ispopBox:true,poll_id:\'' + arg[1].fid + '\'})">问答</a></span></div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a></div></div></div><div class="infoContent">';


        str += '<div class="J_askPanel" style="height:auto !important;"></div></div><div class="commentBox pd" commentObjId="' + commentObjId + '" msgname="' + msgname + '" pageType="' + pageType + '" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div></div></li>';

        var $str = $(str);

        if (arg[2]) {
            // 发布框的数据 需要判断日期 插入到临近节点。
            var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);

            if ($obj) {
                $obj.before($str);
            } else {
                $content.append($str);
            }
        } else {
            $content.append($str);
        }

        var askPanel = $str.find("div.J_askPanel");
        askPanel.showAsk(arg[1].ask);
        askPanel.css({"overflow":"hidden","width":"375px"});

        return $str;
    };
View.prototype.plug.video=function (arg) {
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
        str += '<li name="delTopic"><i class="delTopic"></i>删除帖子...</li>   </ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + this.avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a>';
        if (String(arg[1].from) == "1") {
            // 不需要提示文字；
        } else {
            str += '<span class="subTip">上传了<a href="' + mk_url('video/video/player_video',{vid:arg[1].fid}) + '">视频</a>';
        }
        str += '</span></div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a>';
        var ctime = arg[1].ctime;
        var dateline = arg[1].dateline;


        var friendly_dateline = this.cpu(["returnFriendly_date"], [ctime, dateline]);
        if (friendly_dateline) {
            str += '<i class="insertTime tip_up_left_black" tip="' + friendly_dateline + '"></i>';
        }
        str += '</div></div></div><div class="infoContent">' + arg[1].content + '</div><div class="mediaContent" style><div class="media_prev">';
        var showFlashImgT, showFlashImgL;
        if (arg[1].highlight == 0) {
            str += '<img onerror="this.src=\'' + (CONFIG.misc_path + "img/default/video_err1.jpg") + '\'" src="' + mk_videoPicUrl(arg[1].imgurl) + '" width=403 height=300 alt="" />';
            showFlashImgT = "125px";
            showFlashImgL = "184px";
        } else {
            str += '<img onerror="this.src=\'' + (CONFIG.misc_path + "img/default/video_err1.jpg") + '\'" src="' + mk_videoPicUrl(arg[1].imgurl) + '" width=838 height=600 alt="" />';
            showFlashImgT = "300px";
            showFlashImgL = "407px";
        }
        var msgname = arg[1].title || "";

        str += '<a class="showFlash" href="javascript:void(0);"><img alt="" src="' + CONFIG.misc_path + 'img/system/feedvideoplay.gif" style="top:' + showFlashImgT + ';left:' + showFlashImgL + ';"></a></div><div class="media_disp hide"><div id="video_' + arg[1].fid + arg[1].tid + '"></div></div></div><div class="commentBox pd" msgname="' + msgname + '" commentObjId="' + arg[1].fid + '" pageType="' + arg[1].type + '" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div></div></li>';

        var $str = $(str);

        if (arg[2]) {
            // 发布框的数据 需要判断日期 插入到临近节点。
            var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);

            if ($obj) {
                $obj.before($str);
            } else {
                $content.append($str);
            }
        } else {
            $content.append($str);
        }
        return $str;
    };
View.prototype.plug.uinfo=function (arg) {
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
    };
View.prototype.plug.social=function(arg) {
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

        var str = '<li id="' + arg[1].tid + '" name="timeBox" fid="' + arg[1].fid +'" uid="' + arg[1].uid + '" class="' + sideClass + '" timearea="" type="' + arg[1].type + '"><!--<i class="spinePointer"></i>--><div class="timelineBox"><div class="hy-friendHead clearfix"><div class="avatar"><a href="' + location + '"><img src="' + this.avatar + '" height="32" width="32" /></div><div class="info"><a href="' + location + '">' + arg[1].uname + '</a><br><span>' + (arg[1].friendly_time || '最近') + '</span></div></div>';

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

        if (arg[2]) {
            // 发布框的数据 需要判断日期 插入到临近节点。
            var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);

            if ($obj) {
                $obj.before($str);
            } else {
                $content.append($str);
            }
        } else {
            $content.append($str);
        }
        return $str;
    };
View.prototype.plug.friend=function(arg) {
        var $content = arg[0].children("ul.content");
        var sideClass, clickDown, tipTxthighlight;
        var friends = arg[1].friends || [];

        // 小
        clickDown = "";
        tipTxthighlight = "放大";
        sideClass = "sideLeft";

        var actionDkcode = (arg[3] || "");
        var location = mk_url('main/index/main',{dkcode:actionDkcode});
        var str = '<li id="' + arg[1].tid + '" name="timeBox" fid="' + arg[1].fid +'" uid="' + arg[1].uid + '" class="' + sideClass + '" timearea="" type="friend"><!--<i class="spinePointer"></i>--><div class="timelineBox"><div class="hy-friendHead clearfix"><div class="avatar"><a href="' + location + '"><img src="' + this.avatar + '" height="32" width="32" /></div><div class="info"><a href="' + location + '">' + (arg[2] || "") + '</a><br><span>' + (arg[1].friends_num || 0) + '个朋友</span><!--<a href="' + mk_url('main/following/index') + '" class="goLst">显示全部</a>--></div></div>';
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
        if (arg[2]) {
            // 发布框的数据 需要判断日期 插入到临近节点。
            var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);


            if ($obj) {
                $obj.before($str);
            } else {
                $content.append($str);
            }
        } else {
            $content.append($str);
        }
        return $str;
    };
View.prototype.plug.followWeb=function(arg) {
        var $content = arg[0].children("ul.content");
        var sideClass, clickDown, tipTxthighlight;
        var follows = arg[1].follows || [];

        // 小
        clickDown = "";
        tipTxthighlight = "放大";
        sideClass = "sideLeft";

        //var location = webpath + "main/index.php?c=index&m=index&action_dkcode=" + arg[1].dkcode;
        var location = mk_url("main/index/main",{dkcode:arg[1].dkcode});

        var str = '<li id="' + arg[1].tid + '" name="timeBox" fid="' + arg[1].fid +'" uid="' + arg[1].uid + '" class="' + sideClass + '" timearea="" type="' + arg[1].type + '"><i class="spinePointer"></i><div class="timelineBox"><div class="hy-friendHead clearfix"><div class="avatar"><a href="' + location + '"><img src="' + this.avatar + '" height="32" width="32" /></div><div class="info"><a href="' + location + '">' + arg[1].uname + '</a><br><span>' + (arg[1].friendly_time || '最近') + '</span></div></div>';

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
        if (arg[2]) {
            // 发布框的数据 需要判断日期 插入到临近节点。
            var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);


            if ($obj) {
                $obj.before($str);
            } else {
                $content.append($str);
            }
        } else {
            $content.append($str);
        }
        return $str;
    };
View.prototype.plug.change=function(arg) {
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

        str += '<li name="delTopic"><i class="delTopic"></i>删除帖子...</li></ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + this.avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a> <span style="font-weight:100;">' + info + '</span>';
        str += '</span></div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a>';
        var ctime = arg[1].ctime;
        var dateline = arg[1].dateline;


        var friendly_dateline = this.cpu(["returnFriendly_date"], [ctime, dateline]);
        if (friendly_dateline) {
            str += '<i class="insertTime tip_up_left_black" tip="' + friendly_dateline + '"></i>';
        }
        str += '</div></div></div><div class="mediaContent clearfix" style="height:auto !important; margin:0px; padding:14px;">';
        str += '<a href="javascript:void(0);" action_dkcode="' + arg[1].dkcode + '" pid="' + arg[1].fid + '" url="' + mk_url('album/index/photoInfo',{photoid:arg[1].fid,dkcode:arg[1].dkcode}) + '" class="photoLink"><img src="' + fdfsHost + "/" + arg[1].imgurl + '" style="max-width:375px; _width:expression(this.width>=375?375:\'auto\');" alt="" /></a>';
        showFlashImgT = "29px";
        showFlashImgL = "67px";

        var msgname = arg[1].title || "";

        str += '</div><!--<div class="commentBox pd" msgname="' + msgname + '" commentObjId="' + arg[1].tid + '" pageType="'+arg[1].type+'" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div>--></div></li>';

        var $str = $(str);

        if (arg[2]) {
            // 发布框的数据 需要判断日期 插入到临近节点。
            var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);

            if ($obj) {
                $obj.before($str);
            } else {
                $content.append($str);
            }
        } else {
            $content.append($str);
        }
        return $str;
    };
View.prototype.plug.sharevideo=function(arg) {
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
        str += '<li name="delTopic"><i class="delTopic"></i>删除帖子...</li></ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + this.avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a> <span style="font-weight:100;">分享了视频</span>';
        /*
         if (String(arg[1].from) == "1") {
         // 不需要提示文字；
         } else {
         str += '<span class="subTip">分享了<a href="' + arg[1].url + '">视频</a>';
         }*/
        str += '</span></div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a>';
        var ctime = arg[1].ctime;
        var dateline = arg[1].dateline;


        var friendly_dateline = this.cpu(["returnFriendly_date"], [ctime, dateline]);
        if (friendly_dateline) {
            str += '<i class="insertTime tip_up_left_black" tip="' + friendly_dateline + '"></i>';
        }
        str += '</div></div></div><div class="infoContent" style="font-size:13px; padding-bottom:8px;"><a href="' + arg[1].url + '" target="_blank">' + arg[1].content + '</a></div><div class="mediaContent" style="height:auto !important; height:80px; min-height:80px; width:128px;"><div class="media_prev" videourl="' + arg[1].videourl + '" url="' + arg[1].url + '" style="padding:0px 0px 10px 15px;">';
        var showFlashImgT, showFlashImgL;
        str += '<img onerror="this.src=\'' + (CONFIG.misc_path + "img/default/video_err1.jpg") + '\'" src="' +  arg[1].imgurl  + '" width="128" height="80" alt="" />';
        showFlashImgT = "29px";
        showFlashImgL = "67px";

        var msgname = arg[1].title || "";

        str += '<a class="showFlash" href="javascript:void(0);"><img alt="" src="' + CONFIG.misc_path + 'img/system/feedvideoplay_small.gif" style="height:23px;top:' + showFlashImgT + ';left:' + showFlashImgL + ';"></a></div><div class="media_disp hide" ><div id="' + arg[1].fid + arg[1].tid + '"></div></div></div><div class="commentBox pd" msgname="' + msgname + '" commentObjId="' + arg[1].tid + '" pageType="' + arg[1].type + '" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div></div></li>';

        var $str = $(str);

        if (arg[2]) {
            // 发布框的数据 需要判断日期 插入到临近节点。
            var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);

            if ($obj) {
                $obj.before($str);
            } else {
                $content.append($str);
            }
        } else {
            $content.append($str);
        }
        return $str;
    };
View.prototype.plug.join=function(arg) {
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
        var str = '<li id="' + arg[1].tid + '" name="timeBox" scale="true"  fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" class="' + sideClass + '"><!--<i class="spinePointer"></i>--><div class="timelineBox"><div class="hy-friendHead clearfix"><div class="avatar"><a href="' + location + '"><img src="' + this.avatar + '" height="32" width="32" /></div><div class="info"><a href="' + location + '" style="font-size:12px;"><b>' + arg[1].uname + '</b></a><b>参加了' + arg[1].events_num + '个活动</b><br /><span>' + arg[1].friendly_time + '</span</div></div></div><div class="infoContent clearfix">';

        for(var i = 0,l = events.length; i < l; i ++) {
            var eventUrl = mk_url("event/event/detail",{id:events[i].id});
            str += '<div class="infoContent clearfix" style="padding:5px 0px; border-bottom:1px solid #ddd;"><a class="eventPic" style="margin:0px 10px 0px 0px;" href="#"><img width="50" height="50" src="' + (events[i].cover || (CONFIG.misc_path) + 'img/default/event.jpg') + '" alt=""></a><div class="eventInfo" style="margin-top:0px;"><h4><a href="' + eventUrl + '">' + events[i].title + '</a></h4><span>' + events[i].join_time + '</span></div></div>';
        }

        if(arg[1].events_num > events.length) {
            str += '<div class="hy-answerMore"><a href="javascript:;" class="J_showMoreEvent">其他 ' + (arg[1].events_num - l) + ' 条</a></div>';
        }

        str += '</div></div></li>';

        var $str = $(str);

        if (arg[2]) {
            // 发布框的数据 需要判断日期 插入到临近节点。
            var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);

            if ($obj) {
                $obj.before($str);
            } else {
                $content.append($str);
            }
        } else {
            $content.append($str);
        }
        return $str;
    };
View.prototype.plug.answer=function(arg) {
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
        var str = '<li id="' + arg[1].tid + '" name="timeBox" scale="true"  fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" class="' + sideClass + '"><div class="timelineBox"><div class="hy-friendHead clearfix"><div class="avatar"><a href="' + location + '"><img src="' + this.avatar + '" height="32" width="32" /></a></div><div class="info"><a href="' + location + '" style="font-size:12px;"><strong>' + (arg[1].uname || "") + '</strong></a><b>回答了' + arg[1].question_num + '个问题</b><br><span>' + arg[1].start_time + "-" + arg[1].end_time + '</span></div></div><div class="infoContent clearfix"><ul class="hy-answerLst">';

        var defaultNum = 3;
        var l = (arg[1].questions.length > defaultNum) ? defaultNum : arg[1].questions.length;
        for(var i = 0; i < l; i ++) {
            var questionTitle = arg[1].questions[i].title;
            var answersInfo = arg[1].questions[i].answers.join(" · ");


            //questionTitle = (questionTitle.length > 25) ? questionTitle.substring(0,22) + "...？" : questionTitle;
            questionTitle = arg[1].questions[i].title
            answersInfo = (answersInfo.length > 33) ? answersInfo.substring(0,30) + "..." : answersInfo;

            str += '<li question_id="' + arg[1].questions[i].id + '"><h4 class="hy-answerTitle" style="line-height:16px;"><a onclick="$(this).showAsk({ispopBox:true,poll_id:\'' + arg[1].questions[i].id + '\'});return false;" href="javascript:;" title="' + arg[1].questions[i].title_full + '" style="font-size:12px;">' + questionTitle + '</a></h4><p class="hy-answerContent" title="' + arg[1].questions[i].answers.join(" · ") + '">' + answersInfo + '</p></li>';
        }
        str += '</ul>';

        if(arg[1].question_num > defaultNum) {
            str += '<div class="hy-answerMore"><a href="javascript:;">其他 ' + (arg[1].question_num - l) + ' 条</a></div>';
        }

        str += '</div></div></li>';

        var $str = $(str);
        $str.find("ul.hy-answerLst li").last().css({"border-bottom":"0px"});

        if (arg[2]) {
            // 发布框的数据 需要判断日期 插入到临近节点。
            var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);

            if ($obj) {
                $obj.before($str);
            } else {
                $content.append($str);
            }
        } else {
            $content.append($str);
        }
        return $str;
    };
View.prototype.plug.current_month_top=function(arg) {
        var $content = arg[0].children("ul.content");

        var sideClass, clickDown, tipTxthighlight;
        var follows = arg[1].follows || [],
            friends = arg[1].friends || [],
            followWeb = arg[1].webs || [];
            followWeb = (followWeb === "null") ? [] : followWeb;
        var maxNum = 6;

        // 小
        clickDown = "";
        tipTxthighlight = "放大";
        sideClass = "sideRight";

        var location = mk_url("main/index/main",{dkcode:arg[1].dkcode});

        var str = '<li id="' + arg[1].tid + '" tid="' + arg[1].tid + '" fid="' + arg[1].fid +'" uid="' + arg[1].uid + '" class="' + sideClass + '" timearea="" type="current_month_top" time="0"><div class="timelineBox"><div class="hy-friendHead clearfix"><div class="avatar"><a href="' + location + '"><img src="' + this.avatar + '" height="32" width="32" /></div><div class="info"><a href="' + location + '">' + arg[1].uname + '</a><br><span>' + (arg[1].friendly_time || '最近') + '</span></div></div>';

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

        if (arg[2]) {
            // 发布框的数据 需要判断日期 插入到临近节点。
            var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);

            if ($obj) {
                $obj.before($str);
            } else {
                $content.append($str);
            }
        } else {
            $content.append($str);
        }
        return $str;
    };
View.prototype.plug.hot=function(arg) {
    };
View.prototype.plug.birth=function (arg) {
        var time = arg[1].time.slice(0, 4);
        var str = '<li id="' + arg[1].id + '" name="timeBox"  time="' + arg[1].time + '" class="twoColumn clearfix"><i class="spinePointer"></i><div class="timelineBox" style="margin:0px">' + arg[1].content + '</div></li>'
        var $content = arg[0].children("ul.content");
        $content.children("div").remove();
        $content.append(str);
        //this.hMethod[p.attr("id")] = p.offset().top;
        //this.hMethod[arg[1].id] = p.next().find("i").offset().top
    };
View.prototype.plug.month=function (arg) {
        // 下拉对象，下拉数据
        arg[0].empty();
        var str = "";
        //str = '<ul class="dropListul checkedUl"><li class="current" type="hotData"><a class="itemAnchor" href="javascript:void(0)"><i></i><span>热点信息</span></a></li><li  type="yearAllData"><a class="itemAnchor" href="javascript:void(0)" name="hotData"><i></i><span>全年的信息</span></a></li>';
        str = '<ul class="dropListul checkedUl">';
        $.each(arg[1], function (a, b) {
            str += '<li type="monthData"><a class="itemAnchor" href="javascript:void(0)"><i></i><span time="' + b + '">' + b + '月</span></a></li>';
        });
        str += '</ul>';
        arg[0].show();
        arg[0].dropdown({
            btn:'<i></i><span class="fl"></span>',
            top:22,
            list:str,
            templete:true,
            callback:function (ele) {
                // 显示年份具体月份数据在时间轴上面
            }
        });
        this.event(["selectCheckEvent"], [arg[0].find("ul.dropListul")]);
    };
