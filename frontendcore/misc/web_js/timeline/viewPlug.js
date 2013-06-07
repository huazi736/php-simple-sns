/**
 * Created by hcj.
 * Date: 12-6-18
 * Time: 下午5:58
 * To change this template use File | Settings | File Templates.
 * use : for view
 */
 
View.prototype.plug.blog = function (arg) {
    var $content = arg[0].children("ul.content");
    var theLast = $content.children("li[name='timeBox']").last();
    var sideClass, clickDown, tipTxthighlight;
    var contentClass = "infoContent";
    var webId = this.webId;

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

    //var location = webpath + "main/index.php?c=index&m=index&web_id=" + webId;
    var location = mk_url('webmain/index/main',{web_id:webId});
    var timeData = [
        arg[1].ymd.year,
        arg[1].ymd.month,
        arg[1].ymd.day,
        arg[1].ymd.hour,
        arg[1].ymd.minute,
        arg[1].ymd.second
    ];

    var str = '<li name="timeBox" scale="true" id="' + arg[1].tid + '"  fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" timeData ="' + timeData + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><span class="conWrap midLine tip_up_left_black ' + clickDown + '" tip="' + tipTxthighlight + '"><a><i class="conResize"></i></a></span><span class="conWrap tip_up_right_black" tip="编辑或删除"><a><i class="conEdit"></i></a><ul class="editMenu hide"><li name="changeDate"><i class="changeDate"></i>更改日期...</li><li class="sepLine"></li><li name="delTopic"><i class="delTopic"></i>删除帖子...</li>	</ul></span></div><div class="headBlock clearfix"><a href="" class="headImg"><img src="' + this.web_avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a>';
    if (arg[1].action && arg[1].action == 2) {
        str += ' <span class="subTip">分享了<a href="' + arg[1].nameurl + '">' + arg[1].fname + '</a>的<a href="' + arg[1].furl + '">博客</a></span>';
    }
    str += '</div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a>';
    if (arg[1].ctime != arg[1].dateline && arg[1].friendly_line) {

        str += '<i class="insertTime tip_up_left_black" tip="' + arg[1].friendly_line + '"></i>';
    }
    str += '</div></div></div>';
    if (arg[1].action && arg[1].action == 2) {
        contentClass = "forwardContent";
    } else {
        contentClass = "infoContent"
    }
    var msgname = arg[1].title || "";
    str += '<div class="' + contentClass + ' blog"><h3><a href="' + arg[1].url + '">' + arg[1].title + '</a></h3><p>' + arg[1].content + '</p><p><a href="' + arg[1].url + '" class="readMore">继续阅读...	</a></p></div><div noForward="true" class="commentBox pd" msgname="' + msgname + '" commentObjId="' + arg[1].tid + '" pageType="web_' + arg[1].type + '" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div></div></li>';


    if (arg[2]) {
        // 发布框的数据 需要判断日期 插入到临近节点。
        var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);

        if ($obj) {
            $obj.before(str);
        } else {
            $content.append(str);
        }
    } else {
        $content.append(str);
    }
};
View.prototype.plug.forward=function (arg) {
        var $content = arg[0].children("ul.content");

        var sideClass, clickDown, forwardContent = "";

        var location;
        var webId = this.webId;

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
        var ftid = "", ftype = "";
        if (arg[1].forward && arg[1].forward.length != 0) {
            ftid = arg[1].forward.tid;
            ftype = arg[1].forward.type;
            //location = webpath + "main/index.php?c=index&m=index&web_id=" + webId;
            location = mk_url('webmain/index/main',{web_id:webId});
            switch (arg[1].forward.type) {
                case "info":
                    forwardContent = '<div class="forwardContent"><span class="oldAuthorName" uid="' + arg[1].forward.uid + '"><a href="' + location + '">' + arg[1].forward.uname + ':</a></span><span class="memo">' + arg[1].forward.content + '</span></div>';
                    break;

                case "album":
                    var temp = "";
                    temp = '<ul class="photoContent clearfix">';

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
                            var _width = "expression(this.width>=838?838:'auto')";
                            picurl = fdfsHost + "/" + v.groupname + "/" + v.filename + size + "." + v.type;
                            //temp += '<li class="' + firstPhoto + " " + hidden + '" style="' + hidden + ';"><a href="javascript:void(0);" action_dkcode="' + arg[1].dkcode + '" pid="' + v.pid + '" url="' + webpath + 'web/album/index.php?c=photo&m=get&photoid=' + v.pid + '&web_id=' + this.web_id + '" class="photoLink"><img src="' + picurl + '" alt="" style="max-width:838px;_width:' + _width + '"/></a></li>'; //height="'+height+'" width="'+width+'" 删除 by李世君 2012-4-1
                            temp += '<li class="' + firstPhoto + " " + hidden + '" style="' + hidden + ';"><a href="javascript:void(0);" action_dkcode="' + arg[1].dkcode + '" pid="' + v.pid + '" url="' + mk_url('walbum/photo/get',{photoid:v.pid,web_id:webId}) + '" class="photoLink"><img src="' + picurl + '" alt="" style="max-width:838px;_width:' + _width + '"/></a></li>'; //height="'+height+'" width="'+width+'" 删除 by李世君 2012-4-1


                        });
                    }

                    temp += '</ul>';
                    forwardContent = '<div class="forwardContent"><span class="oldAuthorName" uid="' + arg[1].forward.uid + '"><a href="' + location + '">' + arg[1].forward.uname + ':</a></span><span class="memo" style="margin:0">' + arg[1].forward.content + '</span></div>' + temp;
                    break;

                case "photo":
                    var temp = "";
                    temp = '<ul class="photoContent clearfix">';

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
                            var _width = "expression(this.width>=838?838:'auto')";
                            picurl = fdfsHost + "/" + v.groupname + "/" + v.filename + size + "." + v.type;

                            temp += '<li class="' + firstPhoto + " " + hidden + '" style="' + hidden + ';"><a href="javascript:void(0);" action_dkcode="' + arg[1].dkcode + '" pid="' + v.pid + '" url="' + mk_url('walbum/photo/get',{photoid:v.pid,web_id:webId}) + '" class="photoLink"><img src="' + picurl + '" alt="" style="max-width:838px;_width:' + _width + '"/></a></li>'; //height="'+height+'" width="'+width+'" 删除 by李世君 2012-4-1


                        });
                    }

                    temp += '</ul>';
                    forwardContent = '<div class="forwardContent"><span class="oldAuthorName" uid="' + arg[1].forward.uid + '"><a href="' + location + '">' + arg[1].forward.uname + ':</a></span><span class="memo" style="margin:0">' + arg[1].forward.content + '</span></div>' + temp;
                    break;

                case "video":
                    var temp = "";
                    temp = '<div class="mediaContent"><div class="media_prev">';
                    if (arg[1].highlight == 0) {
                        temp += '<img src="' + mk_videoPicUrl(arg[1].forward.imgurl) + '" width=403 height=300 alt="" />';
                        showFlashImgT = "125px";
                        showFlashImgL = "184px";
                    } else {
                        temp += '<img src="' + mk_videoPicUrl(arg[1].forward.imgurl) + '" width=838 height=600 alt="" />';
                        showFlashImgT = "300px";
                        showFlashImgL = "407px";
                    }
                    temp += '<a class="showFlash" href="javascript:void(0);"><img alt="" src="' + CONFIG.misc_path + 'img/system/feedvideoplay.gif" style=left' + showFlashImgL + ';top' + showFlashImgT + ';></a></div><div class="media_disp hide" ><div id="video_' + arg[1].forward.fid + arg[1].tid + '"></div></div></div>';
                    forwardContent = '<div class="forwardContent"><span class="oldAuthorName" uid="' + arg[1].forward.uid + '"><a href="' + location + '">' + arg[1].forward.uname + ':</a></span><span class="memo" style="margin:0">' + arg[1].forward.content + '</span></div>' + temp;
                    break;
            }


        } else {
            forwardContent = '<div class="forwardContent"></span>该信息已被删除！</div>'
        }

        // location = webpath + "main/index.php?c=index&m=index&web_id=" + webId;
        location = mk_url('webmain/index/main',{web_id:webId});
        var str = '<li name="timeBox" scale="true" id="' + arg[1].tid + '"  fid="' + arg[1].fid + '" forwardId="' + ftid + '" forwardType="' + ftype + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><span class="conWrap midLine tip_up_left_black ' + clickDown + '" tip="' + tipTxthighlight + '"><a><i class="conResize"></i></a></span><span class="conWrap tip_up_right_black" tip="编辑或删除"><a><i class="conEdit"></i></a><ul class="editMenu hide"><li name="changeDate"><i class="changeDate"></i>更改日期...</li><li class="sepLine"></li><li name="delTopic"><i class="delTopic"></i>删除帖子...</li>    </ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + this.web_avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a></div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a>';
        if (arg[1].ctime != arg[1].dateline && arg[1].friendly_line) {

            str += '<i class="insertTime tip_up_left_black" tip="' + arg[1].friendly_line + '"></i>';
        }
        var msgname = arg[1].title || "";
        str += '</div></div></div><div class="infoContent">' + arg[1].content + '</div>' + forwardContent + '<div class="commentBox pd" msgname="' + msgname + '" commentObjId="' + arg[1].tid + '" pageType="web_' + arg[1].type + '" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div></div></li>';


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
        this.event(["photoLink"], [$str]);
};
View.prototype.plug.wiki=function (arg) {
        var $content = arg[0].children("ul.content");

        var sideClass, clickDown, tipTxthighlight;
        var webId = this.webId;

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

        //var location = webpath + "main/index.php?c=index&m=index&web_id=" + webId;
        var location = mk_url('webmain/index/main',{web_id:webId});
        var timeData = [
            arg[1].ymd.year,
            arg[1].ymd.month,
            arg[1].ymd.day,
            arg[1].ymd.hour,
            arg[1].ymd.minute,
            arg[1].ymd.second
        ];

        var str = '<li name="timeBox" scale="true" id="' + arg[1].tid + '"  fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" timeData ="' + timeData + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><span class="conWrap midLine tip_up_left_black ' + clickDown + '" tip="' + tipTxthighlight + '"><a><i class="conResize"></i></a></span><span class="conWrap tip_up_right_black" tip="编辑或删除"><a><i class="conEdit"></i></a><ul class="editMenu hide"><li name="changeDate"><i class="changeDate"></i>更改日期...</li><li class="sepLine"></li><li name="delTopic"><i class="delTopic"></i>删除帖子...</li>	</ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + this.web_avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a></div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a>';

        var ctime = arg[1].ctime;
        var dateline = arg[1].dateline;


        if (arg[1].ctime != arg[1].dateline && arg[1].friendly_line) {

            str += '<i class="insertTime tip_up_left_black" tip="' + arg[1].friendly_line + '"></i>';
        }
        var msgname = arg[1].title || "";
        str += '</div></div></div><div class="infoContent">' + arg[1].content + '</div><div class="commentBox pd" msgname="' + msgname + '" commentObjId="' + arg[1].tid + '" pageType="web_' + arg[1].type + '" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div></div></li>';


        if (arg[2]) {
            // 发布框的数据 需要判断日期 插入到临近节点。
            var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);


            if ($obj) {
                $obj.before(str);
            } else {
                $content.append(str);
            }
        } else {
            $content.append(str);
        }
};
View.prototype.plug.event=function (arg) {
        var $content = arg[0].children("ul.content");

        var sideClass, clickDown, tipTxthighlight;
        var webId = this.webId;

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
        var timeData = [
            arg[1].ymd.year,
            arg[1].ymd.month,
            arg[1].ymd.day,
            arg[1].ymd.hour,
            arg[1].ymd.minute,
            arg[1].ymd.second
        ];
        //var location = webpath + "main/index.php?c=index&m=index&web_id=" + webId;
        var location = mk_url('webmain/index/main',{web_id:webId});
        var str = '<li id="' + arg[1].tid + '" name="timeBox" scale="true"  fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" timeData ="' + timeData + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><span class="conWrap midLine tip_up_left_black ' + clickDown + '" tip="' + tipTxthighlight + '"><a><i class="conResize"></i></a></span><span class="conWrap tip_up_right_black" tip="编辑或删除"><a><i class="conEdit"></i></a><ul class="editMenu hide"><li class="sepLine"></li><li name="delTopic"><i class="delTopic"></i>删除帖子...</li></ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + this.web_avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a><span class="subTip">创建了<a href="' + arg[1].url + '">活动</a></span></div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a>';

        var eventUrl = mk_url('event/event/detail',{'id':arg[1].fid});
        str += '</div></div></div><div class="infoContent clearfix"><a class="eventPic" href="' + eventUrl + '"><img width="50" height="50" src="' + arg[1].photo + '" alt=""></a><div class="eventInfo"><h4><a href="' + eventUrl + '">' + arg[1].title + '</a></h4><span>' + arg[1].starttime + '</span></div></div></div></li>';


        if (arg[2]) {
            // 发布框的数据 需要判断日期 插入到临近节点。
            var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);

            if ($obj) {
                $obj.before(str);
            } else {
                $content.append(str);
            }
        } else {
            $content.append(str);
        }
};
View.prototype.plug.album=function (arg) {
    var $content = arg[0].children("ul.content");

    var picurl;
    var webId = this.webId;

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

    // var location = webpath + "main/index.php?c=index&m=index&web_id=" + webId;
    var location = mk_url('webmain/index/main',{web_id:webId});

    var timeData = [
        arg[1].ymd.year,
        arg[1].ymd.month,
        arg[1].ymd.day,
        arg[1].ymd.hour,
        arg[1].ymd.minute,
        arg[1].ymd.second
    ];
    var str = '<li name="timeBox" scale="true" id="' + arg[1].tid + '" type="album" highlight="' + arg[1].highlight + '" fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '"  album="403" time="' + arg[1].ctime + '" timeData ="' + timeData + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><span class="conWrap midLine ' + clickDown + ' tip_up_left_black" tip="' + tipTxthighlight + '"><a title="调整大小"><i class="conResize"></i></a></span><span class="conWrap tip_up_right_black" tip="编辑或删除"><a title="编辑或删除"><i class="conEdit"></i></a><ul class="editMenu hide">';
    if (arg[1].from == "3") {
        str += '<li name="changeDate"><i class="changeDate"></i>更改日期...</li>';
    }
    str += '<li class="sepLine"></li><li name="delTopic"><i class="delTopic"></i>删除帖子...</li> </ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + this.web_avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a>';
    if (parseInt(arg[1].fid) > 10000000) {
        albumText = "照片";
        pageType = "photo"

        commentObjId = arg[1].picurl[0].pid;
    } else {
        albumText = "相册";
        pageType = "album";
        commentObjId = arg[1].fid;

    }
    if (arg[1].from == "3") { // 3 是网页时间线发的  4 是网页相册发的
        pageType = "photo"

    } else {

        // typeHref = webpath + "web/album/index.php?c=photo&m=index&web_id=" + webId + "&albumid=" + arg[1].note;
        //typeHref = mk_url('walbum/photo/index',{web_id:webId,albumid:arg[1].note});
        typeHref = mk_url('walbum/photo/index',{'albumid':arg[1].fid,'web_id':webId});
        str += '<span class="subTip">上传了<a href="' + typeHref + '">' + albumText + '</a></span>';
    }
    str += '</span></div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a>';

    var ctime = arg[1].ctime;
    var dateline = arg[1].dateline;


    if (arg[1].ctime != arg[1].dateline && arg[1].friendly_line) {
        str += '<i class="insertTime tip_up_left_black" tip="' + arg[1].friendly_line + '"></i>';
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
                        _height = v.size.ts.h;
                    }
                }
            }
            var _width = "expression(this.width>=838?838:'auto')";
            picurl = fdfsHost + "/" + v.groupname + "/" + v.filename + size + "." + v.type;

            str += '<li class="' + firstPhoto + " " + hidden + '"><a href="javascript:void(0);" action_dkcode="' + arg[1].dkcode + '" pid="' + v.pid + '" url="' + mk_url('walbum/photo/get',{photoid:v.pid,web_id:webId}) + '" class="photoLink"><img src="' + picurl + '" alt="" style="max-width:838px;_width:' + _width + ';" /></a></li>'; //height="'+height+'" width="'+width+'" 删除 by李世君 2012-4-1
        });
    }
    var msgname = arg[1].title || "";
    str += '</ul><div class="commentBox pd" msgname="' + msgname + '" commentObjId="' + commentObjId + '" pageType="web_' + pageType + '" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div></div></li>';
    var $str;
    $str = $(str);
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
    this.event(["photoLink"], [$str]);
};
View.prototype.plug.photo=function (arg) {
    var $content = arg[0].children("ul.content");
    var webId = this.webId;

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
    // var location = webpath + "main/index.php?c=index&m=index&web_id=" + webId;
    var location = mk_url('webmain/index/main',{web_id:webId});

    var timeData = [
        arg[1].ymd.year,
        arg[1].ymd.month,
        arg[1].ymd.day,
        arg[1].ymd.hour,
        arg[1].ymd.minute,
        arg[1].ymd.second
    ];
    var str = '<li name="timeBox" scale="true" id="' + arg[1].tid + '" type="photo" highlight="' + arg[1].highlight + '" fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '"  album="403" time="' + arg[1].ctime + '" timeData ="' + timeData + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><span class="conWrap midLine ' + clickDown + ' tip_up_left_black" tip="' + tipTxthighlight + '"><a title="调整大小"><i class="conResize"></i></a></span><span class="conWrap tip_up_right_black" tip="编辑或删除"><a title="编辑或删除"><i class="conEdit"></i></a><ul class="editMenu hide">';
    if (arg[1].from == "3") {
        str += '<li name="changeDate"><i class="changeDate"></i>更改日期...</li>';
    }
    str += '<li class="sepLine"></li><li name="delTopic"><i class="delTopic"></i>删除帖子...</li> </ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + this.web_avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a>';
    

    // if (parseInt(arg[1].fid) > 10000000) {
        albumText = "照片";
        pageType = "photo"

        //commentObjId = arg[1].picurl[0].pid;
        commentObjId = arg[1].fid;
    // } else {
    //     albumText = "相册";
    //     pageType = "album";
    //     commentObjId = arg[1].fid;

    // }


    // if (arg[1].from == "3") { // 3 是网页时间线发的  4 是网页相册发的
        pageType = "photo"

    // } else {

    //     // typeHref = webpath + "web/album/index.php?c=photo&m=index&web_id=" + webId + "&albumid=" + arg[1].note;
            typeHref = mk_url('walbum/photo/get',{web_id:webId,photoid:arg[1].fid});
    //     str += '<span class="subTip">上传了<a href="' + typeHref + '">' + albumText + '</a></span>';
    // }


    str += '</span></div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a>';

    var ctime = arg[1].ctime;
    var dateline = arg[1].dateline;


    if (arg[1].ctime != arg[1].dateline && arg[1].friendly_line) {

        str += '<i class="insertTime tip_up_left_black" tip="' + arg[1].friendly_line + '"></i>';
    }
    str += '</div></div></div><div class="infoContent">' + arg[1].title + '</div><ul class="photoContent clearfix">';

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
                        _height = v.size.ts.h;
                    }
                }
            }
            var _width = "expression(this.width>=838?838:'auto')";
            var photoUrl = mk_url('walbum/photo/get', {'photoid':v.pid,'web_id':webId});

            picurl = fdfsHost + "/" + v.groupname + "/" + v.filename + size + "." + v.type;


            // str += '<li class="' + firstPhoto + " " + hidden + '" style="height:' + _height + 'px" ><a href="javascript:void(0);" action_dkcode="' + arg[1].dkcode + '" pid="' + v.pid + '" url="' + webpath + 'web/album/index.php?c=photo&m=get&photoid=' + v.pid + '&web_id=' + webId + '" class="photoLink"><img src="' + picurl + '" alt="" style="max-width:838px;_width:' + _width + '"/></a></li>'; //height="'+height+'" width="'+width+'" 删除 by李世君 2012-4-1
            str += '<li class="' + firstPhoto + " " + hidden + '" style="height:' + _height + 'px" ><a href="javascript:void(0);" action_dkcode="' + arg[1].dkcode + '" pid="' + v.pid + '" url="' + photoUrl + '" class="photoLink"><img src="' + picurl + '" alt="" style="max-width:838px;_width:' + _width + '"/></a></li>'; //height="'+height+'" width="'+width+'" 删除 by李世君 2012-4-1
        });
    }
    var msgname = arg[1].title || "";
    str += '</ul><div class="commentBox pd" msgname="' + msgname + '" commentObjId="' + commentObjId + '" pageType="web_' + pageType + '" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div></div></li>';
    var $str;
    $str = $(str);
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
    this.event(["photoLink"], [$str]);
};
View.prototype.plug.ask=function (arg) {
        var $content = arg[0].children("ul.content");
        var subtype = arg[1].subtype;
        var sideClass, clickDown, tipTxthighlight;
        var webId = this.webId;


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

        // var location = webpath + "main/index.php?c=index&m=index&web_id=" + webId;
        var location = mk_url('webmain/index/main',{web_id:webId});
        var timeData = [
            arg[1].ymd.year,
            arg[1].ymd.month,
            arg[1].ymd.day,
            arg[1].ymd.hour,
            arg[1].ymd.minute,
            arg[1].ymd.second
        ];
        var str = '<li name="timeBox" scale="true" id="' + arg[1].tid + '"  fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" timeData ="' + timeData + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><span class="conWrap midLine tip_up_left_black ' + clickDown + '" tip="' + tipTxthighlight + '"><a><i class="conResize"></i></a></span><span class="conWrap tip_up_right_black" tip="编辑或删除"><a><i class="conEdit"></i></a><ul class="editMenu hide"><li class="sepLine"></li><li name="delTopic"><i class="delTopic"></i>删除帖子...</li></ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + this.web_avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a> <span class="subTip">提出了<a href="' + arg[1].url + '">问答</a></span></div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a>';

        str += '</div></div></div><div class="infoContent"><div class="askQuestion"><a href="' + arg[1].url + '">' + arg[1].question + '</a></div><ul class="answerlist">';
        if (arg[1].answerlist) {
            $.each(arg[1].answerlist, function (i, v) {
                if (i > 3) {
                    return false;
                }
                if (arg[1].style == "checkbox") {
                    str += '<li><input type="checkbox" disabled="disabled" /> <div class="answer">' + v + '</div></li>';
                } else {
                    str += '<li><input type="radio" disabled="disabled" /> <div class="answer">' + v + '</div></li>';
                }
            })
        }
        str += '</ul>';

        if (arg[1].allcount && arg[1].allcount > 3) {
            str += '<div class="more">其他' + (arg[1].allcount - 3) + '项</div>';
        }
        str += '</div></div></li>';


        if (arg[2]) {
            // 发布框的数据 需要判断日期 插入到临近节点。
            var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);

            if ($obj) {
                $obj.before(str);
            } else {
                $content.append(str);
            }
        } else {
            $content.append(str);
        }
};
View.prototype.plug.video=function (arg) {
        var $content = arg[0].children("ul.content");
        var subtype = arg[1].subtype;
        var sideClass, clickDown, tipTxthighlight;
        var webId = this.webId;


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

        var timeData = [
            arg[1].ymd.year,
            arg[1].ymd.month,
            arg[1].ymd.day,
            arg[1].ymd.hour,
            arg[1].ymd.minute,
            arg[1].ymd.second
        ];
        // var location = webpath + "main/index.php?c=index&m=index&web_id=" + webId;

        var location = mk_url('webmain/index/main',{web_id:webId});
        var str = '<li id="' + arg[1].tid + '" name="timeBox" scale="true"  fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" timeData ="' + timeData + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><span class="conWrap midLine tip_up_left_black ' + clickDown + '" tip="' + tipTxthighlight + '"><a><i class="conResize"></i></a></span><span class="conWrap tip_up_right_black" tip="编辑或删除"><a><i class="conEdit"></i></a><ul class="editMenu hide">';

        if (arg[1].from != 2) {
            str += '<li name="changeDate"><i class="changeDate"></i>更改日期...</li>';
        }
        str += '<li class="sepLine"></li><li name="delTopic"><i class="delTopic"></i>删除帖子...</li>   </ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + this.web_avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a><span class="subTip">发表了<a href="' + mk_url("wvideo/video/player_video",{'vid': arg[1].fid})+'">视频</a></span></div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a>';
        var ctime = arg[1].ctime;
        var dateline = arg[1].dateline;


        if (arg[1].ctime != arg[1].dateline && arg[1].friendly_line) {

            str += '<i class="insertTime tip_up_left_black" tip="' + arg[1].friendly_line + '"></i>';
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
        str += '<a class="showFlash" href="javascript:void(0);"><img alt="" src="' + CONFIG.misc_path + 'img/system/feedvideoplay.gif" style="top:' + showFlashImgT + ';left:' + showFlashImgL + ';"></a></div><div class="media_disp hide" ><div id="' + arg[1].fid + arg[1].tid + '"></div></div></div><div class="commentBox pd" msgname="' + msgname + '" commentObjId="' + arg[1].fid + '" pageType="web_' + arg[1].type + '" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div></div></li>';

        if (arg[2]) {
            // 发布框的数据 需要判断日期 插入到临近节点。
            var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);

            if ($obj) {
                $obj.before(str);
            } else {
                $content.append(str);
            }
        } else {
            $content.append(str);
        }
};
View.prototype.plug.goods=function (arg) {
        var $content = arg[0].children("ul.content");

        var sideClass, clickDown, tipTxthighlight;
        var webId = this.webId;

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

        var location = mk_url('webmain/index/main',{web_id:webId});
        var timeData = [
            arg[1].ymd.year,
            arg[1].ymd.month,
            arg[1].ymd.day,
            arg[1].ymd.hour,
            arg[1].ymd.minute,
            arg[1].ymd.second
        ];
		
        var str = '<li name="timeBox" scale="true" id="' + arg[1].goods.gid + '"  fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" timeData ="' + timeData + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><!--<span class="conWrap midLine tip_up_left_black ' + clickDown + '" tip="' + tipTxthighlight + '"><a><i class="conResize"></i></a></span>--><span class="conWrap tip_up_right_black" tip="编辑或删除"><a><i class="conEdit"></i></a><ul class="editMenu hide"><li class="sepLine"></li><li name="delTopic"><i class="delTopic"></i>删除帖子...</li></ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + this.web_avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a></div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a>';

        var ctime = arg[1].ctime;
        var dateline = arg[1].dateline;


        if (arg[1].ctime != arg[1].dateline && arg[1].friendly_line) {
            str += '<i class="insertTime tip_up_left_black" tip="' + arg[1].friendly_line + '"></i>';
        }

        var msgname = arg[1].title || "";
        var goods = arg[1].goods;
        var goods_bigimg='',goods_minimg='';
        for(i = 0 ; i < goods.thumb.length ; i++){
            var init_w	= 379;
			var t 	= init_w;
			var h	= "";
			try{
				var bw	= cint(goods.img_size[i]['b']['w']);
				var bh	= cint(goods.img_size[i]['b']['h']);
				
				if( bw<t ){
					t = bw;
				}
				
				if(bh>0){
					if( bw>init_w ){
						var th	= bh / ( bw / init_w );
						var h 	= "height:"+th+"px;";	
					}else{
						h = "height:"+bh+"px;";	
					}
				}
			}catch(e){}
			
			goods_bigimg = '<li style="display:block;'+h+'" ><img width="'+t+'px" src="' + goods.img[i] + '"/></li>' + goods_bigimg;
			
            break;
			//goods_minimg = '<li><img src="' + goods.thumb[i] +'"/></li>' + goods_minimg;
        }
		var goods_url	= mk_url('channel/goods/goods_show',{"web_id": arg[1].pid,"gid":goods.gid});
        str += '</div></div></div><div class="infoContent"><div class="goods_name"><a href="'+goods_url+'" ><span>商品：</span>'+ goods.goodsname +'</a></div><ul class="goods_bigimg">'+ goods_bigimg +'</ul>';
		str += '<div class="goods_entry" >';
			str += '<span class="goods_price" >售价<i>￥</i>'+ goods.saleprice +' </span>';
			str += '<span class="goods_gopay_span" ><a href="'+ goods.href +'" target="_blank" class="goods_gopay">立即购买</a></span>';
			str += '<div class="cr"></div>';
		str += '</div>';
		

		str += '</div><div class="commentBox pd" msgname="' + goods.goodsname + '" commentObjId="' + goods.gid + '" pageType="goods" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '" mk_url="'+goods_url+'" ></div></div></li>';


        if (arg[2]) {
            // 发布框的数据 需要判断日期 插入到临近节点。
            var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);
            if ($obj) {
                $obj.before(str);
            } else {
                $content.append(str);
            }
        } else {
            $content.append(str);
        }

        //商品渲染后一些页面行为

        var bigImg = $('.goods_bigimg');
        var minImg = $('.goods_minimg');

        minImg.each(function(m){
            _bigimg = $(this).parent().find('.goods_bigimg li');
            _minimg = $(this).find('li');
            _bigimg.eq(0).show();
            _minimg.eq(0).attr('class','goodsNowli');

            _minimg.live('click',function(){
                e = $(this).index();
                $(this).attr('class','goodsNowli').siblings().attr('class','');
                bigImg.eq(m).find('li').eq(e).show().siblings().hide();
                return false;
            });
        });

        /*=============== End 商品渲染 ===================*/
};
View.prototype.plug.groupon = function (arg) {
		var $content = arg[0].children("ul.content");
        var sideClass, clickDown, tipTxthighlight;
        var webId = this.webId;

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

        var location = mk_url('main/index.php?c=index&m=index&web_id=',{web_id:webId});
        var timeData = [
            arg[1].ymd.year,
            arg[1].ymd.month,
            arg[1].ymd.day,
            arg[1].ymd.hour,
            arg[1].ymd.minute,
            arg[1].ymd.second
        ];
		
        //团购渲染开始
        var str = '<li name="timeBox" scale="true" id="' + arg[1].tid + '"  fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" timeData ="' + timeData + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><!--<span class="conWrap midLine tip_up_left_black ' + clickDown + '" tip="' + tipTxthighlight + '"><a><i class="conResize"></i></a></span>--><span class="conWrap tip_up_right_black" tip="编辑或删除"><a><i class="conEdit"></i></a><ul class="editMenu hide"><li class="sepLine"></li><li name="delTopic"><i class="delTopic"></i>删除帖子...</li></ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + this.web_avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a></div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + ' 发表了促销活动</a>';

        var ctime = arg[1].ctime;
        var dateline = arg[1].dateline;


        if (arg[1].ctime != arg[1].dateline && arg[1].friendly_line) {
            str += '<i class="insertTime tip_up_left_black" tip="' + arg[1].friendly_line + '"></i>';
        }

        var msgname = arg[1].title || "";		
        var groupon = arg[1].groupon;

        /*=============== Start 【本地生活】之促销活动倒计时 ===================*/
        /*
         * 【本地生活】之促销活动倒计时(暂未封装成插件)
         * By 贤心(xuxinjian)
         * date 2012.7.12
         */

        var day,hour,mins,secs,diff = '无限期'; //默认
        var groupEnd = '团购已结束';
        var _i = $('.group_showtime').length;
        var infoGroup = {
            appendTime : function(){
                if(day > 0){
                    diff = '<strong class="groupon_day">' + day + '</strong>天<strong class="groupon_hour">' + hour + '</strong>小时<strong class="groupon_mins">' + mins + '</strong>分<strong class="groupon_secs">' + secs + '</strong>秒';
                }else{
                    if(hour > 0){
                        diff = '<strong class="groupon_hour">' + hour + '</strong>小时<strong class="groupon_mins">' + mins + '</strong>分<strong class="groupon_secs">' + secs + '</strong>秒';
                    }else{
                        if(mins > 0){
                            diff = '<strong class="groupon_mins">' + mins + '</strong>分<strong class="groupon_secs">' + secs + '</strong>秒';
                        }else{
                            diff = '<strong class="groupon_secs">' + secs + '</strong>秒';
                        }
                    }
                }
            },
            parseDateTime : function(second){
                day = parseInt(second/(24*3600));
                hour = parseInt((second - day*24*3600)/3600);
                mins = parseInt((second - day*24*3600 - hour*3600)/60);
                secs = second - day*24*3600 - hour*3600 - mins*60;
                //day = 0,hour = 0,mins = 0,secs = 10;
                if(second < 0){
                    diff = groupEnd;
                }else{
                    this.appendTime();
                }
                this.run();
            },
            contime : function(){
                var _d,_h,_m,_s;
                $('.group_showtime').each(function(e){
                    _d = $(this).find('.groupon_day');
                    _h = $(this).find('.groupon_hour');
                    _m = $(this).find('.groupon_mins');
                    _s = $(this).find('.groupon_secs');
                    _dval = _d.html();
                    _hval = _h.html();
                    _mval = _m.html();
                    _sval = _s.html();
                    var _len = $(this).find('strong').length;
                    if(_len > 0){
                        _sval--;
                        if(_sval < 0){
                            if(_len == 1){	//秒
                                $(this).find('i').html(groupEnd);
                            }else if(_len == 2){	//分、秒
                                _sval = 59;
                                if(_mval > 1 ){
                                    _mval--;
                                    _m.html(_mval);

                                }else{
                                    $(this).find('i').html('<strong class="groupon_secs">' + _sval + '</strong>秒');
                                }
                            }else if(_len == 3){	//时、分、秒
                                _sval = 59;
                                if(_mval > 0){
                                    _mval--;
                                    _m.html(_mval);
                                }else{
                                    _mval = 59;
                                    if(_hval > 1){
                                        _hval--;
                                        _m.html(_mval);
                                        _h.html(_hval);
                                    }else{
                                        $(this).find('i').html('<strong class="groupon_mins">' + _mval + '</strong>分<strong class="groupon_secs">' + _sval + '</strong>秒');
                                    }
                                }
                            }else if(_len == 4){	//天、时、分、秒
                                _sval = 59;
                                if(_mval > 0){
                                    _mval--;
                                    _m.html(_mval);
                                }else{
                                    _mval = 59;
                                    if(_hval > 0){
                                        _hval--;
                                        _m.html(_mval);
                                        _h.html(_hval);
                                    }else{
                                        _hval = 23;
                                        if(_dval > 1){
                                            _dval--;
                                            _m.html(_mval);
                                            _h.html(_hval);
                                            _d.html(_dval)
                                        }else{
                                            $(this).find('i').html('<strong class="groupon_hour">' + _hval + '</strong>小时<strong class="groupon_mins">' + _mval + '</strong>分<strong class="groupon_secs">' + _sval + '</strong>秒');
                                        }
                                    }
                                }
                            }
                        }
                        _s.html(_sval);
                    }

                });
            },
            run : function(){
                if(_i == 0){
                    t = setInterval(this.contime,1000);
                }
            }
        }
        infoGroup.parseDateTime(arg[1].diff);
        /*=============== End 【本地生活】之促销活动倒计时 ===================*/
        groupStr = '<h2 class="group_showtitle"><a href="'+ groupon.link +'" target="_blank">'+ groupon.title +'</a></h2>'
							+'<div class="group_showimg"><a href="'+ groupon.link +'" target="_blank"><img src="'+ groupon.img[0].b.url +'" /></a><span>原价：<del>'+ groupon.original_price +'</del>元 <em>折扣：'+ groupon.discount +'折</em><em class="group_showtime">剩余时间：<i>'+ diff +'</i></em></span></div>'
							+'<p class="group_showgosee">促销价 ￥<em>'+ groupon.current_price +'</em><a href="'+ groupon.link +'" target="_blank">现在去看看</a></p>';


        str += '</div></div></div><div class="infoContent">'+ groupStr +'</div><div class="commentBox pd" msgname="' + msgname + '" commentObjId="' + arg[1].fid + '" pageType="web_'+arg[1].type+'" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div></li>';

        if (arg[2]) {
            // 发布框的数据 需要判断日期 插入到临近节点。
            var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);


            if ($obj) {
                $obj.before(str);
            } else {
                $content.append(str);
            }
        } else {
            $content.append(str);
        }
};
View.prototype.plug.dish = function (arg) {
		
		var $content = arg[0].children("ul.content");

        var sideClass, clickDown, tipTxthighlight;
        var webId = this.webId;

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

        var location = mk_url('main/index.php?c=index&m=index&web_id=',{web_id:webId});
        var timeData = [
            arg[1].ymd.year,
            arg[1].ymd.month,
            arg[1].ymd.day,
            arg[1].ymd.hour,
            arg[1].ymd.minute,
            arg[1].ymd.second
        ];

		
        //【本地生活】之菜品渲染开始
		 var str = '<li name="timeBox" scale="true" id="' + arg[1].tid + '"  fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" timeData ="' + timeData + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><!--<span class="conWrap midLine tip_up_left_black ' + clickDown + '" tip="' + tipTxthighlight + '"><a><i class="conResize"></i></a></span>--><span class="conWrap tip_up_right_black" tip="编辑或删除"><a><i class="conEdit"></i></a><ul class="editMenu hide"><li class="sepLine"></li><li name="delTopic"><i class="delTopic"></i>删除帖子...</li></ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + this.web_avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a></div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + ' 发表了菜品</a>';

        var ctime = arg[1].ctime;
        var dateline = arg[1].dateline;
		var msgname = arg[1].title || "";

        if (arg[1].ctime != arg[1].dateline && arg[1].friendly_line) {
            str += '<i class="insertTime tip_up_left_black" tip="' + arg[1].friendly_line + '"></i>';
        }
		
		DISH = arg[1].dish;
		
		var dish_html = '<div class="view_dish"><p class="dish_showBimg"><img src="'+ DISH.pics[0].b.url +'" alt="'+ DISH.name +'" /></p><p class="dish_decs"><span><strong>【'+ DISH.name +'】</strong>'+ DISH.description +'</span></p><p class="dish_price"><span>￥'+ DISH.price +'</span></p></div>';
		
		str += '</div></div></div><div class="infoContent">'+ dish_html +'</div><div class="commentBox pd" msgname="' + msgname + '" commentObjId="' + arg[1].fid + '" pageType="web_'+arg[1].type+'" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div></li>';
        
        if (arg[2]) {
            // 发布框的数据 需要判断日期 插入到临近节点。
            var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);


            if ($obj) {
                $obj.before(str);
            } else {
                $content.append(str);
            }
        } else {
            $content.append(str);
        }
};	
View.prototype.plug.travel = function (arg) {
		var $content = arg[0].children("ul.content");
        var sideClass, clickDown, tipTxthighlight;
        var webId = this.webId;

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

        var location = mk_url('main/index.php?c=index&m=index&web_id=',{web_id:webId});
        var timeData = [
            arg[1].ymd.year,
            arg[1].ymd.month,
            arg[1].ymd.day,
            arg[1].ymd.hour,
            arg[1].ymd.minute,
            arg[1].ymd.second
        ];

		
        //【旅游】之景点渲染开始
		 var str = '<li name="timeBox" scale="true" id="' + arg[1].tid + '"  fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" timeData ="' + timeData + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><!--<span class="conWrap midLine tip_up_left_black ' + clickDown + '" tip="' + tipTxthighlight + '"><a><i class="conResize"></i></a></span>--><span class="conWrap tip_up_right_black" tip="编辑或删除"><a><i class="conEdit"></i></a><ul class="editMenu hide"><li class="sepLine"></li><li name="delTopic"><i class="delTopic"></i>删除帖子...</li></ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + this.web_avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a></div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a>';

        var ctime = arg[1].ctime;
        var dateline = arg[1].dateline;
		var msgname = arg[1].title || "";

        if (arg[1].ctime != arg[1].dateline && arg[1].friendly_line) {
            str += '<i class="insertTime tip_up_left_black" tip="' + arg[1].friendly_line + '"></i>';
        }
		var autoImg=function(){
			var picWidth=arg[1].travel.pics[0].b.width;
			return parseInt(picWidth)<379?picWidth:379;
		}
		var travel_html = '<div class="tripbox_main"><ul><li class="trip_pics"><img src="' + arg[1].travel.pics[0].b.url + '" width="' + autoImg() + '" alt="" /></li><li><span class="trip_price">￥' + arg[1].travel.price + '</span><span class="trip_description">' + arg[1].travel.description + '</span></li><li class="tripbox_btn"><a href="' + arg[1].travel.link + '"target="_blank">现在去看看</a></li></ul></div>'
		
		str += '</div></div></div><div class="infoContent">'+ travel_html +'</div><div class="commentBox pd" msgname="' + msgname + '" commentObjId="' + arg[1].tid + '" pageType="web_'+arg[1].type+'" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div></li>';
        
        if (arg[2]) {
            // 发布框的数据 需要判断日期 插入到临近节点。
            var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);

            if ($obj) {
                $obj.before(str);
            } else {
                $content.append(str);
            }
        } else {
            $content.append(str);
        }
	
};
//特价机票
View.prototype.plug.airticket = function (arg) {
		var $content = arg[0].children("ul.content");
        var sideClass, clickDown, tipTxthighlight;
        var webId = this.webId;

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

        var location = mk_url('main/index.php?c=index&m=index&web_id=',{web_id:webId});
        var timeData = [
            arg[1].ymd.year,
            arg[1].ymd.month,
            arg[1].ymd.day,
            arg[1].ymd.hour,
            arg[1].ymd.minute,
            arg[1].ymd.second
        ];

		
        //【旅游】之景点渲染开始
		 var str = '<li name="timeBox" scale="true" id="' + arg[1].tid + '"  fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" highlight="' + arg[1].highlight + '" time="' + arg[1].ctime + '" timeData ="' + timeData + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><!--<span class="conWrap midLine tip_up_left_black ' + clickDown + '" tip="' + tipTxthighlight + '"><a><i class="conResize"></i></a></span>--><span class="conWrap tip_up_right_black" tip="编辑或删除"><a><i class="conEdit"></i></a><ul class="editMenu hide"><li class="sepLine"></li><li name="delTopic"><i class="delTopic"></i>删除帖子...</li></ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + this.web_avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a></div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a>';

        var ctime = arg[1].ctime;
        var dateline = arg[1].dateline;
		var msgname = arg[1].title || "";

        if (arg[1].ctime != arg[1].dateline && arg[1].friendly_line) {
            str += '<i class="insertTime tip_up_left_black" tip="' + arg[1].friendly_line + '"></i>';
        }
		var travelsigns=function(){
			if(arg[1].airticket.travelsigns==0){
				return '单程'
			}else{
				return '往返'
			}
		}
		var travel_html = '<div class="airlineticket_main">'
		travel_html+='<ul>';
		travel_html+='<li>特价机票：<a href="'+arg[1].airticket.link+'"><span>'+arg[1].airticket.andfromtime+'</span><span>'+arg[1].airticket.gocity+'-'+arg[1].airticket.returntrip+'</span><span>'+travelsigns()+'</span><span class="price">￥'+arg[1].airticket.price+'</span><span>'+arg[1].airticket.rate+'折</span></a></li>';
		travel_html+='</ul>';
		travel_html+='</div>';
				
		str += '</div></div></div><div class="infoContent">'+ travel_html +'</div><div class="commentBox pd" msgname="' + msgname + '" commentObjId="' + arg[1].tid + '" pageType="web_'+arg[1].type+'" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div></li>';
        
        if (arg[2]) {
            // 发布框的数据 需要判断日期 插入到临近节点。
            var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);

            if ($obj) {
                $obj.before(str);
            } else {
                $content.append(str);
            }
        } else {
            $content.append(str);
        }
	
};
View.prototype.plug.uinfo=function (arg) {
        var $content = arg[0].children("ul.content");
        var subtype = arg[1].subtype;
        var sideClass, clickDown, tipTxthighlight;
        var webId = this.webId;


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

        var str = '<li id="' + arg[1].tid + '" name="time" scale="true" highlight="' + arg[1].highlight + '" fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '" time="' + arg[1].ctime + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><span class="conWrap midLine tip_up_left_black ' + clickDown + '" tip="' + tipTxthighlight + '"><a><i class="conResize"></i></a></span>';

        if (subtype != "lifeIcon_3") {
            str += '<span class="conWrap"><a><i class="conEdit"></i></a><ul class="editMenu hide"><li class="sepLine"></li><li name="delTopic"><i class="delTopic"></i>删除帖子...</li>  </ul></span>';
        }

        var msgname = arg[1].title || "";
        str += '</div><div class="lifeContent"><div class="lifeHeader"><i class="' + subtype + '"></i><div class="lifeTitle">' + arg[1].content + '<p class="subDesc">' + arg[1].info + '</p></div></div></div><div noForward="true" class="commentBox pd" msgname="' + msgname + '" commentObjId="' + arg[1].tid + '" pageType="web_'+arg[1].type+'" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div></div></li>';
        $content.append(str);
};
View.prototype.plug.social=function(arg) {
        var $content = arg[0].children("ul.content");

        var followUsers = arg[1]["follows"] || [],
            friendUsers = arg[1]["friends"] || [];

        var sideClass, clickDown, tipTxthighlight;
        var webId = this.webId;

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
        // var location = webpath + "main/index.php?c=index&m=index&web_id=" + webId;

        var location = mk_url('webmain/index/main',{web_id:webId});
        var str = '<li id="defaultTimeBox1" name="timeBox" class="sideLeft clearfix defaultTimeBox" timearea=""><div class="timelineBox"><div class="hy-friendHead clearfix"><div class="avatar"><a href="' + location + '" title="' + arg[1]["uname"] + '"><img src="' + this.web_avatar + '" height="32" width="32" alt="' + arg[1]["uname"] + '"></a></div><div class="info"><a href="' + location + '">' + arg[1]["uname"] + '</a><br><span>最近</span></div></div>';

        if(followUsers && followUsers.length !== 0) {
            var followLst = [];

            followLst.push('<div class="hy-friendTip">添加了 <a href="#">' + followUsers.length + ' 位关注</a></div>');
            followLst.push('<div class="hy-friendSmallPanel"><ul class="hy-friendSmallLst">');

            for(var i = 0, len = followUsers.length; i < len; i ++) {
                // var href = webpath + "main/index.php?c=index&m=index&web_id=" + followUsers[i]["uid"];

                var href = mk_url('webmain/index/main',{web_id:followUsers[i]["uid"]});
                followLst.push('<li><a href="' + href + '" title="' + followUsers[i]["uname"] + '"><img src="' + followUsers[i]["avatar"] + '" height="65" width="65" alt="'+ followUsers[i]["uname"] + '" /></a></li>');
            }

            followLst.push('</ul></div>');
            str += followLst.join("");
        }

        if(friendUsers && friendUsers.length !== 0) {
            var friendLst = [];

            friendLst.push('<div class="hy-friendTip">添加了 <a href="' + href + '">' + friendUsers.length + ' 位好友</a></div>');
            friendLst.push('<div class="hy-friendSmallPanel"><ul class="hy-friendSmallLst">');

            for(var i = 0, len = friendUsers.length; i < len; i ++) {
                // var href = webpath + "main/index.php?c=index&m=index&web_id=" + friendUsers[i]["uid"];
                var href = mk_url('webmain/index/main',{web_id:followUsers[i]["uid"]});
                friendLst.push('<li><a href="' + href + '" title="' + friendUsers[i]["uname"] + '"><img src="' + friendUsers[i]["avatar"] + '" height="65" width="65" alt="'+ friendUsers[i]["uname"] + '" /></a></li>');
            }

            friendLst.push('</ul></div>');
            str += friendLst.join("");
        }

        if (arg[2]) {
            // 发布框的数据 需要判断日期 插入到临近节点。
            var $obj = this.cpu(["returnPrevTimebox"], [$content, arg[1].ctime]);

            if ($obj) {
                $obj.before(str);
            } else {
                $content.append(str);
            }
        } else {
            $content.append(str);
        }
};
View.prototype.plug.birth=function (arg) {
        var time = arg[1].time.slice(0, 4);
        var str = '<li id="' + arg[1].id + '" name="time"  time="' + arg[1].time + '" class="twoColumn clearfix"><i class="spinePointer"></i><div class="timelineBox" style="margin:0px">' + arg[1].content + '</div></li>';
        var $content = arg[0].children("ul.content");
        var webId = this.webId;

        $content.children("div").remove();
        $content.append(str);
        //this.hMethod[p.attr("id")] = p.offset().top;
        //this.hMethod[arg[1].id] = p.next().find("i").offset().top
};
View.prototype.plug.month=function (arg) {
        // 下拉对象，下拉数据
        arg[0].empty();
        var webId = this.webId;
        var str = "";
        str = '<ul class="dropListul checkedUl"><li class="current" type="hotData"><a class="itemAnchor" href="javascript:void(0)"><i></i><span>热点信息</span></a></li><li  type="yearAllData"><a class="itemAnchor" href="javascript:void(0)" name="hotData"><i></i><span>全年的动态</span></a></li>';

        $.each(arg[1], function (a, b) {
            str += '<li type="monthData"><a class="itemAnchor" href="javascript:void(0)"><i></i><span time="' + b + '">' + b + '月</span></a></li>';
        });
        str += '</ul>';
        arg[0].show();
        arg[0].dropdown({
            btn:'<i></i><span class="fl">热点信息</span>',
            top:22,
            list:str,
            templete:true,
            callback:function (ele) {
                // 显示年份具体月份数据在时间轴上面
            }
        });
        this.event(["selectCheckEvent"], [arg[0].find("ul.dropListul")]);
};
function cint(value){						//  parseInt  转成数字  整型
    if( (!value))	return 0;
    var number	=  parseInt(value,10);
    if(isNaN(number)) return 0;
    return number;
}

/*
*作者：    姚智译
*时间：    20120810
*模块：    摄影
*参数：    
            arg[1] = {
                'type': 'shoot',频道的唯一字符串标志，后端数据库决定
                'user':{
                    'home':个人主页链接,
                    'head':头像的链接,
                    'name':用户姓名,
                    'dkcode':端口号,
                    'uid':用户id
                },
                'msg':{
                    'id':信息的 id
                    'acrat_time':{
                        'y':年,'m':月,'d':日,'h':'小时','m':'分钟','s':'秒','timestamp':'时间戳'
                    },
                    'rough_time':大致时间
                },
                'imgs':[
                    {
                        'url':图片的链接
                    },
                    {
                        'url':图片的链接
                    }
                ]
                'cmts':[
                    
                ],
                'box':{
                    'highlight':0或1
                }
            }
*/
View.prototype.plug.shoot=function (arg) {
    /*
    *功能：    把{'p1':'val1','p2':'val2'} 转换为字符串 'p1="val1" p2="val2"'
    *应用：    var s_img = '<img ' + trans_obj_to_str({'src':'http://domain/path/to/pict/pict.jpg' , 'alt':'图片'}) + ' />'
    *局限：    o_desp 中的属性名不能是 toString 等，因为存在 Object.prototype.toString
    */
    function trans_obj_to_str(o_desp)
    {
        var a_html = [];
        for(var s_pro in o_desp)
        {
            if(typeof s_pro === "string" && typeof o_desp[s_pro] === "string" && ! Object.prototype[s_pro])
            {
                a_html.push(s_pro + '=' + '"' + o_desp[s_pro] + '"');
            }
        }
        return a_html.join(' ');
    }

    /*
        时间： 20120810
        功能： 生成 timebox
        参数： 从后端返回的原始数据
    */
    function gen_time_box(o_info)
    {

        var o_desp = {
            /*'album':'403','fid':'1344491039',*/
            'type': o_info['type'],
            'class': ((o_info['box']['highlight'] === '0')? 'sideLeft':'twoColumn clearfix'),
            'time': o_info['acrat_time']['timestamp'],
            'uid':o_info['user']['uid'],
            'highlight': o_info['box']['highlight'],
            'id': o_info['msg']['id'],
            'scale':'true',
            'name':'timeBox'
        };
   
        var a_html = [
            '<li ' + trans_obj_to_str(o_desp) + ' >',
                '<i class="spinePointer"></i>',
                gen_timeline_box(o_info),
            '</li>'
        ];
        return a_html.join();
    }
    /*
    *   功能： 
    */
    function gen_timeline_box(o_info)
    {
        var a_html = [
            '<div class="timelineBox">',
                gen_edit_control(o_info),
                gen_head_block(o_info),
                gen_picts_window(o_info),
                gen_comment_box(o_info),
            '</div>'
        ];

        return a_html.join('');
    }
    function gen_edit_control(o_info)
    {
        var b_highlight = o_info['box']['highlight'] === '0' || '';
        var o_desp = {
            'tip': (b_highlight ?"放大":"缩小"),
            'class': ('conWrap midLine ' + (b_highlight? '' : 'clickDown') + ' tip_up_left_black')
        };
        var a_html = [
            '<div class="editControl hide" style="display: none;">',
                '<span ' + trans_obj_to_str(o_desp) + ' >',
                    '<a title="调整大小"><i class="conResize"></i></a>',
                '</span>',
                '<span tip="编辑或删除" class="conWrap tip_up_right_black">',
                    '<a title="编辑或删除"><i class="conEdit"></i></a>',
                    '<ul class="editMenu hide">',
                        '<li name="changeDate">',
                            '<i class="changeDate"></i>更改日期...',
                        '</li>',
                        '<li class="sepLine">',
                        '</li>',
                        '<li name="delTopic">',
                            '<i class="delTopic"></i>删除帖子...',
                        '</li>',
                    '</ul>',
                '</span>',
            '</div>'
        ];

        return a_html.join('');
    }


    /*
    *功能：发表框的头部，包括头部信息以及信息流的相关信息(作者和发表的时间描述)
    *备注：
    */
    function gen_head_block(o_info)
    {
        var o_user = o_info['user'] , o_msg = o_info['msg'];
        var a_html = [
            '<div class="headBlock clearfix">',
                //头像部分
                '<a href="' + o_user['home'] + '" class="headImg">',
                    '<img ' + trans_obj_to_str({'width':'32' , 'height':'32' , 'src': o_user['head'] , 'alt':'#'}) + ' />',
                '</a>',
                //姓名和时间
                '<div class="unitHeader">',
                    //姓名
                    '<div class="AuthorName">',
                        '<a href="' + o_user['home'] + '">' + o_user['name'] + '</a>',
                    '</div>',
                    //时间
                    '<div class="postTime">',
                        '<a href="javascript:void(0)">' + o_msg['rough_time'] + '</a>',
                    '</div>',
                '</div>',
            '</div>'
        ];
        return a_html.join('');
    }
    function gen_picts_window_small(o_info)
    {
        var a_html = [], a_imgs = o_info['imgs'], o_img_desp = {};
        a_html.push('<ul class="pictsWindow_small clearfix">');
        for(var i = 1 , i_len = a_imgs.length; i < i_len; i++)
        {
            o_img_desp = {
                'alt': a_imgs[i]['alt'],
                'src': a_imgs[i]['src']
            };
            a_html.push('<li class="imgWrap"><a href="javascript:void(0);">');
            a_html.push('<img ' + trans_obj_to_str(o_img_desp) + ' />');
            a_html.push('</a></li>');
        }
        a_html.push('</ul>');
        return a_html.join('');
    }
    /*
    *功能：拼装并返回展示图片的html代码
    *备注：图片数目大于 1；不止 url 一个属性，后期可能会用到 关于图片的各种信息，都要传进来
    */
    function gen_picts_window(o_info)
    {
        var o_desp = {
            'alt': '这是一个图片' ,
            'src': o_info['imgs'][0]['url']
        };
        var a_html = [
            '<div class="pictsWindow clearfix">',
                '<div class="pictsWindow_big">',
                    '<a href="javascript:void(0);">',
                        '<img ' + trans_obj_to_str(o_desp) + ' />',
                    '</a>',
                '</div>',
                gen_picts_window_small(o_info),
            '</div>'
        ];
        return a_html.join('');
    }
    function gen_comment_box(o_info)
    {
        var o_desp = {
            'class':'class',
            'commentObjId': o_info['commentObjId'],
            'msgname': o_info['msgname'],
            'pageType': o_info['pageType'],
            'ctime': o_info['ctime'],
            'action_uid': o_info['action_uid']
        };
        return '<div ' + trans_obj_to_str(o_desp) + '></div>';
    }


/*
    var $content = arg[0].children("ul.content");

    var picurl;
    var webId = this.webId;

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

    // var location = webpath + "main/index.php?c=index&m=index&web_id=" + webId;
    var location = mk_url('webmain/index/main',{web_id:webId});

    var timeData = [
        arg[1].ymd.year,
        arg[1].ymd.month,
        arg[1].ymd.day,
        arg[1].ymd.hour,
        arg[1].ymd.minute,
        arg[1].ymd.second
    ];
    var str = '<li name="timeBox" scale="true" id="' + arg[1].tid + '" type="album" highlight="' + arg[1].highlight + '" fid="' + arg[1].fid + '" uid="' + arg[1].uid + '" type="' + arg[1].type + '"  album="403" time="' + arg[1].ctime + '" timeData ="' + timeData + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><span class="conWrap midLine ' + clickDown + ' tip_up_left_black" tip="' + tipTxthighlight + '"><a title="调整大小"><i class="conResize"></i></a></span><span class="conWrap tip_up_right_black" tip="编辑或删除"><a title="编辑或删除"><i class="conEdit"></i></a><ul class="editMenu hide">';
    if (arg[1].from == "3") {
        str += '<li name="changeDate"><i class="changeDate"></i>更改日期...</li>';
    }
    str += '<li class="sepLine"></li><li name="delTopic"><i class="delTopic"></i>删除帖子...</li> </ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + this.web_avatar + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + arg[1].uname + '</a>';
    if (parseInt(arg[1].fid) > 10000000) {
        albumText = "照片";
        pageType = "photo"

        commentObjId = arg[1].picurl[0].pid;
    } else {
        albumText = "相册";
        pageType = "album";
        commentObjId = arg[1].fid;

    }
    if (arg[1].from == "3") { // 3 是网页时间线发的  4 是网页相册发的
        pageType = "photo"

    } else {

        // typeHref = webpath + "web/album/index.php?c=photo&m=index&web_id=" + webId + "&albumid=" + arg[1].note;
        //typeHref = mk_url('walbum/photo/index',{web_id:webId,albumid:arg[1].note});
        typeHref = mk_url('walbum/photo/index',{'albumid':arg[1].fid,'web_id':webId});
        str += '<span class="subTip">上传了<a href="' + typeHref + '">' + albumText + '</a></span>';
    }
    str += '</span></div><div class="postTime"><a href="javascript:void(0)">' + arg[1].friendly_time + '</a>';

    var ctime = arg[1].ctime;
    var dateline = arg[1].dateline;


    if (arg[1].ctime != arg[1].dateline && arg[1].friendly_line) {
        str += '<i class="insertTime tip_up_left_black" tip="' + arg[1].friendly_line + '"></i>';
    }
	//infoContent photoContent
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
                        _height = v.size.ts.h;
                    }
                }
            }
            var _width = "expression(this.width>=838?838:'auto')";
            picurl = fdfsHost + "/" + v.groupname + "/" + v.filename + size + "." + v.type;

            str += '<li class="' + firstPhoto + " " + hidden + '" style="height:' + _height + 'px" ><a href="javascript:void(0);" action_dkcode="' + arg[1].dkcode + '" pid="' + v.pid + '" url="' + mk_url('walbum/photo/get',{photoid:v.pid,web_id:webId}) + '" class="photoLink"><img src="' + picurl + '" alt="" style="max-width:838px;_width:' + _width + '"/></a></li>'; //height="'+height+'" width="'+width+'" 删除 by李世君 2012-4-1
        });
    }
    var msgname = arg[1].title || "";

    str += '</ul><div class="commentBox pd" msgname="' + msgname + '" commentObjId="' + commentObjId + '" pageType="web_' + pageType + '" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div></div></li>';
    var $str;
    $str = $(str);
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
    this.event(["photoLink"], [$str]);

*/
};



