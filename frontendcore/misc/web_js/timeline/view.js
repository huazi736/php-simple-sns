/**
 * Created by hcj.
 * Date: 12-6-18
 * Time: 下午5:58
 * To change this template use File | Settings | File Templates.
 * use : for view
 */

//View object for rendering
function View() {
//    this.prototype = object;
}
View.prototype.view = function (method, arg) {
    var self = this;
    var func = null;
    $.each(method, function (index, value) {
        if (value) {
            func = View.prototype.plug[value].call(self,arg);
            return func;
        }
    });
    return func;
};
View.prototype.plug = {
    timelinePs1:function (obj) {
        //var str = $('<li name="pstime" scale="true" class="time" time="' + obj.time + '" scrollTop=""><h5>' + String(obj.title).replace('-', 'B.C') + '</h5><a name="' + obj.time + '"></a><ul class="content"><div class="h100 loading"></div></ul></li>');
        var str = '<li name="pstime" scale="true" class="time" time="' + obj.time  + '" scrollTop=""><h5>' + obj.title + '</h5><a name="' + obj.time + '"></a><ul class="content">';
        !parseInt(CONFIG['action_dkcode']) && (str += '<li><a href="#" class="timeLinePlus" id="timeLinePlus_'+obj.time+'" ><i class="tlhPlus"></i></a></li>');
        str += '<div class="h100 loading"></div></ul></li>';
        str = $(str);
        //time line plus
        !parseInt(CONFIG['action_dkcode']) && this.timeLineHoverPlus(str.find('#timeLinePlus_' + obj.time));
        var $li = this.cpu(["returnPrevTimeLi"], [obj.time]);
        $li ? $li.before(str) : this.timelineTree.append(str);

        if ($li && obj.time == $li.attr("time")) {
            $li.remove();
        }
        return str
    },
    timelinePs2:function (obj) {
        var str;
        if (parseInt(obj.time1) == parseInt(obj.time2)) {
            str = $('<li name="pstime"  class="timePeriod" time="' + String(obj.time1).replace(/\//g, '-') + '" scrollTop=""><a class="timeAreaShow">显示' + String(obj.title1).replace('年', '') + '</a></li>');
        } else {
            str = $('<li name="pstime"  class="timePeriod" time="' + String(obj.time1).replace(/\//g, '-') + '~' + String(obj.time2).replace(/\//g, '-') + '" scrollTop=""><a class="timeAreaShow">显示' + String(obj.title1).replace('年', '') + '-' + String(obj.title2).replace('年', '') + '</a></li>');
        }
        var $li = this.cpu(["returnPrevTimeLi"], [obj.time1]);
        if ($li) {
            $li.before(str);
            if (obj.time1 == $li.attr("time")) {
                $li.remove();
            }
        } else {
            this.timelineTree.append(str);
        }
        this.event(["timeAreaShow"], [str.children()]);
    },
    // 显示更多动态， 点击请求下一页数据
    nextPage:function (arg) {
        var str;
        if (arg[0].find("div.nextPage").size() == 0) {
            str = '<div name="nextPage"  class="nextPage" time="' + arg[1] + '" ><a class="nextPage">显示更多动态</a></div>';
            arg[0].append(str);
            this.event(["nextPage"], [arg[0]]);

        }
        if (arg[2]) {
            arg[0].attr("page", arg[2])
        }
        arg[0].attr("isEnd", arg[3]);
    },
    timelineNav:function (arg) {
        var str = '<ul class="dropListul checkedUl">',
            self = this;
        $.each(arg[1], function (a, b) {
            var selected = "";
            var title = "";
            var date = "";
            var birthday = "";
            var attrtitle;
            if (b) {
                if (b.title == "现在") {
                    selected = "current";
                }
                date = b.date || b;
                title = b.title || date;
                attrtitle = b.memo ? ('title = ' + b.memo) : '';
                if (b.birthday) {
                    this.birthday = b.birthday;
                    var birthDate = new Date(b.birthday * 1000);
                    this.birthYear = birthDate.getFullYear();
                    this.birthMonth = birthDate.getMonth() + 1;
                }
                str += '<li class="' + selected + '"><a class="itemAnchor" href="javascript:void(0)" ' + attrtitle + '><i></i><span time="' + String(date).replace(/\//g, "-") + '">' + self.cpu(["toWanAndYi"], [title]) + '</span></a></li>';

            }

        });
        str += '</ul>';

        $('#timelineSelect').html('');

        arg[0].show();
        arg[0].html('').dropdown({
            btn:'<i></i><span class="fl">现在</span>',
            list:str,
            templete:true,
            top:22,
            callback:function (ele) {
                var time = ele.find("span").attr("time");
                self.sideArea.find("a[time=" + time + "]").click();
            }
        });
    },
    timeline:function (arg) {
        var current = "", str1;
        if (arg[2] == 0) {
            current = "current";
        }
        var timeArea = arg[3].timeArea || false;
        var time = arg[3].time || false;
        if (arg[3].title == "现在") {
            str1 = $('<li name="time" id="' + arg[3].id + '" scale="true" class="time" time="' + arg[1].ctime + '" scrollTop=""><h5>' + arg[3].title + '</h5><a name="' + timeArea + '"></a></li><div class="h100"></div>');
            arg[0].append(str1);
            //arg[0].children("li.defaultTimeBox").attr("timeArea",this.todayArea);
        } else {
            if (arg[3].child) {
                str1 = '<li name="time" id="' + arg[3].id + '" psYear="psYear" scale="true" class="time" time="' + arg[1].ctime + '" scrollTop=""><h5>' + arg[3].title + '</h5><a name="' + timeArea + '"></a></li>';
            } else {
                str1 = '<li name="time" id="' + arg[3].id + '"  scale="true" class="time" time="' + arg[1].ctime + '" scrollTop=""><h5>' + arg[3].title + '</h5><a name="' + timeArea + '"></a></li><div class="h100"></div>';
            }


            arg[0].append(str1);
        }


        //arg[1].children().append('<li class="'+current+'"><a time="'+arg[3].timeArea+'">'+arg[3].title+'</a></li>');

        this.event(["psTime"], [arg[0].find("li.time")]);
    },
    info:function (arg) {
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

        // var location = webpath + "main/index.php?c=index&m=index&web_id=" + this.webId;
        var location = mk_url('webmain/index/main',{web_id:this.webId});
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
        str += '</div></div></div><div class="infoContent">' + arg[1].content + '</div><div class="commentBox pd" msgname="' + msgname + '" commentObjId="' + arg[1].tid + '" pageType="web_topic" ctime="' + arg[1].ctime + '" action_uid="' + arg[1].uid + '"></div></div></li>';


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
    }
};

