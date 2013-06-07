/*
 * @author:    lincy,wangwb,liangss
 * @created:   2012/02/10
 * @version:   v1.0
 * @desc:      时间线

 ======================
 关于"from"

 在首页时间线这边：
 来自于应用（相册，博客，视频，问答，活动，UINFO）的的from均为2，在首页时间线的和好友页面发表的都是1

 在网页时间线：
 来自于应用（相册，视频，活动）的的from均为3，在首页时间线的和好友页面发表的都是4
 ======================
 */
//改个东西试试
/*******************start:时间线vi主程序*********************/

function CLASS_TIMELINE(arg) {

}
CLASS_TIMELINE.prototype = {
    init:function () {
        var self = this;
        this.timelineTree = $("#timelineTree").find("div.timelinebody");     //时间轴容器
        this.sideArea = $("#sideArea");             //时间菜单
        this.modlueHeader = $("#modlueHeader");     //头部菜单
        this.timelineCursor = $("#timelineCursor"); //添加事件icon
        this.hotMonth = $("#hotMonth");             //热点信息下拉
        this.timelineSelect = $("#timelineSelect"); //现在下拉
        this.hd_avatar = $("#hd_avatar").val();
        this.html_date = $("#date_a");

        this.hMethod = {};                          //时间轴刻度存储器
        this.psTime = {};                           //时间轴区间标识存储器
        this.recodeTop = [];
        this.yearMonthArr = {};                     //存储 年对应月
        this.action_dkcode = $("#action_dkcode").val();
        this.hd_UID = $("#hd_UID").val();
        this.addNewAction = $("#addNewAction");
        this.action_avatar = $("#action_avatar").val();
        this.birthday = null;
        this.addPlusPop = true;
        // if (this.html_date.size() != 0) {
        //    var arr = this.html_date.val().split("-");
        //    var date = new Date(arr[0], arr[1], arr[2]);

        //    this.thisYear = this.html_date.val().split('-')[0];
        //    this.thisMonth = this.html_date.val().split('-')[1];
        //    this.prevMonth = this.thisMonth - 1;
        // }else {

        // }
        //server time
        var date = new Date(CONFIG['time']*1000);

        this.thisYear = date.getFullYear();
        this.thisMonth = date.getMonth() + 1;
        this.prevMonth = this.thisMonth -1;
        if (this.action_dkcode != this.hd_UID) {
            this.avatar = this.action_avatar;
        } else {
            this.avatar = this.hd_avatar;
        }

        this.event(["addNewActionHover"], [self.addNewAction]);
        this.today = self.cpu(["today"]);
        this.todayArea = this.today.slice(0, this.today.lastIndexOf("-"));
        this.shareDestinationObjects = $("div.competence");
        this.timeLoadArray = [];
        this.uid = $("#action_dkcode").val() || $("#hd_UID").val();
        this.model("timedata", [
            {type:0},
            function (data) {
                self.CLASS_TIMELINE_NAV = new CLASS_TIMELINE_NAV({
                    content:self.sideArea.children(),
                    data:data
                });
                self.view(["timelineNav"], [self.timelineSelect, data]);
                self.CLASS_TIMELINE_NAV.init();
                self.dateArray = data.slice(0);

                self.event(["current"], [self.sideArea]);
                self.event(["scroll"], [self.sideArea]);
            }
        ]);

        this.plug(["msg"], [$(".distributeMsg")]);
        // self.event(["newTimeAction"],[self.timelineTree]);
        this.event(["scrollToTop"], [$("#scrollToTop")]);

        // time line hover plus
        this.timeLineHoverPlus = function (e) {
            var _a = e,
                pop = null,
                _c = null,
                _d = null,
                self = this,
                _b = _a.find('.tlhPlus'),
                _W = $('#postBox').html();


            _a.bind({
                'mouseenter':function (evt) {
                    _b.css('top', function () {
                        return limitTop(evt);
                    });
                },
                'mousemove':function (evt) {
                    if ($(evt.target == _a)) {
                        _b.css('top', function () {
                            return limitTop(evt);
                        });
                    }
                },
                'click':function (evt) {
                    var left, time;
                    pop = $('#addNewAction');
                    left = $('#timelineContent').offset().left;
                    _c = $('#addPlusPost')
                    if(self.addPlusPop){
//                        _c.append(_W);
                        $(".html_date").calendar({button:false, time:false});
                        _c.find('.input_msg').remove();
                        self.plug(["msg"], [$(".distributeMsg")]);
                        _c.find('.uiButton').bind('click',hidePop);
                        _c.find('.uiButton').attr('isSend',0);
                    }


                    time = new Date(toClickTime(evt, _a) * 1000);
                    $('.html_date').val(time.getFullYear() + '-' + (time.getMonth() + 1) + '-' + time.getDate());
                    $('.html_date').data('time',(time.getFullYear() + '-' + (time.getMonth() + 1) + '-' + time.getDate() +  '-' +
                        time.getHours()  +  '-' + time.getMinutes() + '-' + time.getSeconds()));
                    // _c.find('.html_date').val(toClickTime(evt, _a) * 1000);

                    pop.css({
                        'left':left,
                        'top':evt.pageY - pop.height() / 2 - 10
                    }).show();

                    // pop.find('.composerAttachments').find('li').unbind().bind('click', bindPlus);
                    // pop.find('.composerAttachments').find('li').eq($('#currentComposerAttachment').val()).click();
                    self.addPlusPop = false;


                    evt.preventDefault();
                }
            });
            $(document).bind('click', hidePop);
            function hidePop(evt){
                var that = $(evt.target);
                if (pop && !that.hasClass('tlhPlus') && that.closest('#addNewAction').size() == 0 && that.closest('.dk_calendar').size() == 0 && that.closest('.footer').size() == 0 || that.parent().attr('isSend')) {
                    // pop.find('.composerAttachments').find('li').unbind();
                    // $('#distributeInfoBody').append(pop.find('.distributeInfoBox'));
                    // _c.html('');
                    pop && pop.hide();
                    pop = null;
                    // $('#distributeInfoBody').find('.composerAttachments').find('li').eq($('#currentComposerAttachment').val()).click();

                }
            }

            function limitTop(evt) {
                //var _target = $(evt.target),a;
                //a = _target != a ? _target.offset().top + evt.pageY;
                return Math.min(Math.max(evt.pageY - _a.offset().top - _b.height() / 2, 0), _a.height() - _b.height());
            }

            function bindPlus() {
                var pointUpTop,
                    that = $(this),
                    parentUl = that.parent(),
                    pointUp = parentUl.next(),
                    index = that.attr('ref'),
                    contentWrap = parentUl.siblings('.contentW').find('.distributeInfoBox'),
                    contentItems = contentWrap.find('div.distributeInfo');

                switch (index) {
                    case '0' :
                        pointUpTop = 22;
                        break;
                    case '1' :
                        pointUpTop = 92;
                        break;
                    case '2' :
                        pointUpTop = 162;
                        break;
                }
                pointUp.css('margin-left', pointUpTop + 'px');
                contentItems.hide().eq(index).show();
            }


            function toClickTime(evt, target) {
                var time, clickT, pre, next, parent, same, lis, parentT,
                    preT = 0, nextT = 0, sameT = 0;
                parent = target.closest('ul.content');
                parentT = parent.offset().top;
                lis = parent.find('li[name = "timeBox"]').has('.spinePointer');
                clickT = evt.pageY - parentT;
                for (var i = 0 , len = lis.length; i < len; i++) {
                    var t = lis.eq(i).offset().top - 2 * parentT + lis.eq(i).find('.spinePointer').offset().top + 8;
                    if (clickT > t) {
                        pre = lis.eq(i);
                        preT = t;
                    } else if (clickT < t && !nextT) {
                        next = lis.eq(i);
                        nextT = t;
                    } else if (!preT && !nextT) {
                        same = lis.eq(i);
                        sameT = t;
                    }
                }
                if (sameT) {
                    time = same.attr('time');
                } else {
                    var preTime  , nextTime;
                    if (preT && nextT) {
                        preTime = pre.attr('time');
                        nextTime = next.attr('time');
                        time = Math.round((preTime - nextTime) * (clickT - preT) / (nextT - preT)) + parseInt(nextTime);
                    } else if (preT && !nextT) {
                        time = pre.attr('time');
                    } else if (!preT && nextT) {
                        time = next.attr('time');
                    }
                }
                return time;
            }
        };

    },
    // for this.view as View object
    view:function (method, arg) {
        var tempView = new View();
        var view = tempView.view.call(this, method, arg);

        // 控制“关注”+“关注网页”+“好友”信息模块始终保持在当前月右上角（陈海云添加）
        /*try {
            var $content = view.parent();
            var current_month_tops = $content.find("li[type='current_month_top']");
            
            current_month_tops.each(function() {
                var current_month_top = $(this);
                var penel = $content;

                penel.prepend(current_month_top);
                current_month_top.removeClass("sideLeft").removeClass("twoColumn").addClass("sideRight");
            });
        }catch(ex) {}*/

        return view;
    },
    cpu:function (method, arg) {
        var self = this;
        var func = null;
        var _class = {
            permissionShow:function (arg) {
                var str = "";

                switch (String(arg[0])) {
                    case "8":
                        arg[1].find(".timelineBox").prepend("<div class='onlymeBg'></div>");
                        arg[1].find(".timelineBox").append("<div class='onlymeBg'></div>");
                        str = "<span class='onlyme tip_up_left_black' tip='仅限自己'></span>";

                        break;
                    case "4":

                        str = "<span class='friend tip_up_left_black' tip='好友'></span>";

                        break;
                    case "3":

                        str = "<span class='fans tip_up_left_black' tip='粉丝'></span>";

                        break;
                    case "1":

                        str = "<span class='open tip_up_left_black' tip='公开'></span>";

                        break;

                    case "-1":
                        str = "<span class='user tip_up_left_black' tip='自定义'></span>";

                        break;
                }
                //arg[1].find(".postTime").append("<div class='timeBoxPermission'>" + str + "</div>");
                if(self.action_dkcode == self.hd_UID){
                    arg[1].find(".postTime").append('<div class="dropWrap dropMenu timelinePermission" oid="' + arg[1][0].id + '" s="'+ arg[0] +'" uid="'+ ("relations" in arg[2] ? arg[2].relations : -1) +'"></div>');
                    arg[1].find(".postTime").find('.timelinePermission').dropdown({
                        permission:{
                            // url:webpath + 'main/index.php?c=info&m=doUpdatePermission',
                            url:mk_url('main/info/doUpdatePermission'),
                            im:true
                        }
                    });
                }
                self.plug(["tip_up_left_black"], [arg[1].find(".postTime")]);
            },
            psTime:function (arg) {

                $.each(arg[0].find("li[name=time]"), function () {
                    var id = $(this).attr("id");
                    var scale = ($(this).offset().top + 15) + ($(this).height() - 15);
                    self.psTime[id] = ($(this).offset().top) + "-" + scale;
                });
            },
            returnMaxOrMin:function (arg) {
                var arr1 = [];  //id,刻度,差值
                var arr2 = [];  //差值数组
                var arr3 = [];  //输出ID数组
                var temp;
                $.each(arg[2], function (id, b) {
                    if (arg[1] == "min") {
                        if (b - arg[0] < 0) {
                            arr1.push({id:id, b:b, c:Math.abs(b - arg[0])});
                            arr2.push((Math.abs(b - arg[0])));
                        }
                    } else {
                        if (b - arg[0] > 0) {
                            arr1.push({id:id, b:b, c:Math.abs(b - arg[0])});
                            arr2.push((Math.abs(b - arg[0])));
                        }
                    }
                });
                len = arr1.length;
                for (var i = 0; i < len; i++) {
                    temp = arr2[i];
                    for (var j = 0; j < len; j++) {
                        if (arr2[j] > temp) {
                            temp = arr1[i];
                            arr1[i] = arr1[j];
                            arr1[j] = temp;
                            temp = arr2[i];
                            arr2[i] = arr2[j];
                            arr2[j] = temp;
                        }
                    }
                }
                if (arr1) {
                    return arr1[0];
                } else {
                    return false;
                }
            },
            timeDiff:function (arg) {
                // 2012-2-1 2011-3-2
                var time, time1, time2, time3, _time;
                time = arg[0].split("-");
                time1 = new Date(time[0], time[1] - 1, time[2]);
                time = arg[1].split("-");
                time2 = new Date(time[0], time[1] - 1, time[2]);
                _time = parseInt((Math.abs(time1 - time2) / 1000 / 60 / 60 / 24));

                if (Math.abs(_time) == 1) {
                    return arg[0];
                } else {
                    time3 = (Math.abs(time1 - time2) * arg[2]) / 1000 / 60 / 60 / 24
                    time1.setDate(time1.getDate() - time3);
                    return time1.getFullYear() + "-" + (time1.getMonth() + 1) + "-" + time1.getDate();
                }
            },
            today:function (arg) {
                var _date = new Date();
                return _date.getFullYear() + "-" + (_date.getMonth() + 1) + "-" + _date.getDate();
            },
            returnPsTimeOverBoolen:function (arg) {
                var boolen = true;
                $.each(self.psTime, function (id, value) {
                    var arr = value.split("-");

                    if (arg[0] + 10 < arr[1] && arg[0] + 10 > arr[0]) {
                        boolen = false;
                    }
                });
                return boolen;
            },
            reScale:function (arg) {
                self.hMethod = {};
                self.psTime = {};
                $.each(arg[0].find("li[scale]"), function () {
                    var id = $(this).attr("id");
                    self.hMethod[id] = $(this).offset().top;
                    if ($(this).find("i").size() != 0) {
                        self.hMethod[id] = $(this).find("i").offset().top;
                    }
                });
                self.cpu(["psTime"], [self.timelineTree]);
            },
            returnPrevTimeLi:function (arg) {
                arg[0] = String(arg[0]).replace('/','-');
                var $li, that = this;
                $.each(self.timelineTree.find("li[name=pstime]"), function () {
                    var time = $(this).attr("time");
                    var arr;
                    if (time.indexOf("~") != -1) {
                        arr = time.split("~");
                        if (arr[0] == arr[1]) {
                            $(this).attr("time", arr[0]).children().text("显示" + arr[0]);
                        } else {
                            if (parseInt(arr[0]) <= parseInt(arg[0]) && parseInt(arr[1]) >= parseInt(arg[0])) {
                                $li = $(this);
                                if (parseInt(arr[0]) >= (parseInt(arg[0]) - 1)) {
                                    $(this).attr("time", arr[0]).children().text("显示" + arr[0]);
                                } else{
                                    var str = String(arg[0]).indexOf('-') != -1 ? parseInt(arg[0]) : parseInt(arg[0]) - 1;
                                    $(this).attr("time", arr[0] + "~" + arg[0]).children().text("显示" + arr[0] + "-" + str);
                                }
                                return false;
                            }
                        }
                    }
                    if (parseInt(time) < parseInt(arg[0]) || time == arg[0]) {        // 判断属于时间轴哪个时间标识前

                        $li = $(this);
                        return false;
                    }
                    if (String(time).slice(0, 4) == String(arg[0]).slice(0, 4)) { // 判断和当前某个时间标识重复，并且排除现在、上一个
                        var argMonth = String(arg[0]).split('-')[1],
                            argYear = String(arg[0]).split('-')[0],
                            timeMonth = String(time).split('-')[1] || 13;
                        if (argYear == self.thisYear) {
                            if (argMonth == self.thisMonth || (argMonth == self.prevMonth && timeMonth != self.thisMonth )) {
                                return $li = $(this);
                            } else if (parseInt(argMonth) > parseInt(timeMonth)) {
                                return $li = $(this);
                            }
                        } else if (parseInt(argMonth) > parseInt(timeMonth)) {
                            return $li = $(this);
                        }

                    }
                    // if (String(time).slice(0, 4) == String(arg[0]).slice(0, 4) && time.indexOf("-") == -1) { // 判断和当前某个时间标识重复，并且排除现在、上一个
                    //     $li = $(this);
                    //     return false;
                    // }
                    $li = undefined;
                });
                return $li;
            },
            setPsTime:function (arg) {
                var time1, time2, title1, title2, index;
                var $timePsBox = arg[0];
                var time = arg[1];
                var title = arg[2];

                var $prev = $timePsBox.prev("li");
                if ($prev.size() == 0) {
                    return false;
                }
                var $prevTime = $prev.attr("time");

                if (String($prevTime).indexOf("-") != -1) { // 含有-
                    time2 = parseInt($prevTime.slice(0, 4));
                    title2 = parseInt($prevTime.slice(0, 4));
                } else {
                    time2 = parseInt($prevTime.slice(0, 4)) - 1;
                    title2 = parseInt($prevTime.slice(0, 4)) - 1;
                }
                if (title == "出生") {

                    if (self.dateArray.length > 1) {

                        if (self.dateArray[self.dateArray.length - 2] && self.dateArray[self.dateArray.length - 2].date) {
                            time1 = self.dateArray[self.dateArray.length - 2].date;
                        } else {
                            time1 = self.dateArray[self.dateArray.length - 2];
                        }
                        if (self.dateArray[self.dateArray.length - 2] && self.dateArray[self.dateArray.length - 2].date) {
                            title1 = self.dateArray[self.dateArray.length - 2].title;
                        } else {
                            title1 = self.dateArray[self.dateArray.length - 2];
                        }
                        // time1 = self.dateArray[self.dateArray.length-1].date;
                        // title1 = self.dateArray[self.dateArray.length-1].title;
                        if (time1 && title1) {
                            self.view(["timelinePs2"], {time1:time1, title1:title1, time2:time2, title2:title2});
                        }
                    } else {
                        if (self.dateArray.length == 1) {
                            if (self.dateArray[0] && self.dateArray[0].date) {
                                time1 = self.dateArray[0].date;
                            } else {
                                time1 = self.dateArray[0];
                            }
                            if (self.dateArray[0] && self.dateArray[0].title) {
                                title1 = self.dateArray[0].title;
                            } else {
                                title1 = self.dateArray[0];
                            }
                            if (time1 && title1) {
                                self.view(["timelinePs2"], {time1:time1, title1:title1, time2:time1, title2:title1});
                            }
                        }
                    }
                } else {
                    index = self.cpu(["arraySplice"], time);

                    if (index != 0) {
                        if (self.dateArray[index - 1] && self.dateArray[index - 1].title != '现在') {

                            var tempData = self.dateArray[index - 1];
                            time1 = tempData.date || tempData;
                            title1 = tempData.title || parseInt(time1);
                            self.view(["timelinePs2"], {time1:time1, title1:title1, time2:time2, title2:title2});
                        }
                    }
                }
            },
            arraySplice:function (arg) {
                var index;
                arg = (parseInt(arg) >= 0 && arg.indexOf('-') != -1) ? arg.replace('-', '/') : arg;
                $.each(self.dateArray, function (a, b) {
                    if (b && (b == arg || b.date == arg)) {
                        self.dateArray[a] = undefined;
                        index = a;
                    }
                    if (index) {
                        return index;
                    }
                });
                return index;
            },
            currentShowHide:function (arg) {
                var isSelect = arg[2] ? false : true;
                var timelineBar = arg[0];
                var obj = arg[1];
                var $current;
                timelineBar.find("li").attr("class","");
                if(obj.next("ul.child").size()!=0){         // 是年代
                    obj.parent().siblings().find("ul.child").hide();
                    //obj.next("ul.child").show();
                    (function(obj){
                        if(obj.next()[0]){
                            obj.next().show();
                            var obj1 = obj.next().find('li').first().children('a');
                            arguments.callee(obj1);
                        }else {
                            $current = obj.parent();
                            isSelect && $current.attr("class","current");
                            return false;
                        }
                    })(obj);
                    isSelect && $current.parents('li').attr("class","selected");
                } else {
                    timelineBar.find("ul.child").hide();
                    $current = obj.parent();
                    isSelect && $current .attr("class", "current");
                    (function (obj) {
                        if (obj.closest("ul.child")[0]) {
                            obj.closest("ul.child").show();
                            var obj1 = obj.closest("ul.child").parent();
                            isSelect && obj1.attr('class', 'selected');
                            arguments.callee(obj1);
                        }
                    })(obj);

                }

                //if overflow:auto to the site
                var top = $current.prevAll().size()*16-(6*16);
                setTimeout(function(){
                    $current.closest('ul').scrollTop(top);
                },100);

               // if(obj.closest("ul.child").size()!=0){
               //     obj.closest("ul.child").children("li").attr("class","");
               //     obj.closest("ul.child").parent().attr("class","selected");
               //     $current = obj.parent();
               //     $current.attr("class","current");
               // }
                return $current;
            },
            lay:function (arg) {
                // 容器
                var _selfLi,
                    _leftHeight = 0,
                    _rightHeight = 0.001;
                _selfLi = arg[0].children().filter('[name="timeBox"]');
                for (var i = 0, len = _selfLi.size(); i < len; i++) {
                    var _height = _selfLi.eq(i).height() + 51;
                    if (!_selfLi.eq(i).hasClass('twoColumn')) {
                        _selfLi.eq(i).removeClass('sideLeft sideRight');
                        if (_rightHeight > _leftHeight && _selfLi.eq(i).attr('type') != 'social') {
                            _selfLi.eq(i).addClass('sideLeft');
                            _leftHeight += _height;
                        } else {
                            _selfLi.eq(i).addClass('sideRight');
                            _rightHeight += _height;
                        }
                        arrowRestTop(i);
                    } else {
                        _leftHeight = 0;
                        _rightHeight = 0.001;
                    }
                }

                function arrowRestTop(i) {
                    var a, b,val,
                        current = _selfLi.eq(i),
                        pre = _selfLi.eq(i).prev('[name="timeBox"]');
                    a = current.offset().top;
                    if (current.hasClass("twoColumn")) {
                        current.children("i.spinePointer").css("top", 1);
                        return;
                    }
                    if (pre[0]) {
                        b = pre.offset().top;
                    }
                    if (b && a  <= (b + 15)  && !pre.hasClass(current[0].className) && !pre.hasClass('twoColumn')) {
                        val = 65;
                    }else {
                        val = 35;
                    }
                    current.children('i.spinePointer').css('top', val);
                }
            },
            returnPrevTimebox:function (arg) {
                var time2 = arg[1];
                var obj;
                $.each(arg[0].find("li[name=timeBox]"), function () {
                    var time1 = $(this).attr("time");


                    var _time = parseInt(time1 - time2);
                    if (_time < 0) {
                        obj = $(this);
                        return false;
                    }
                });

                return obj;
            },
            // 记录坐标
            recodePsTimeTop:function (arg) {
                // 不存在 插入 并且更新之前top值;
                self.recodeTop = [];
                $.each(self.timelineTree.find("li[name=pstime][class=time]"), function () {

                    self.recodeTop.push({obj:$(this), top:$(this).offset().top});
                });
            },
            //滚到2012 选中
            scrollInCurrentNav:function (arg) {
                var i = 0;
                var compearTop = function (i) {
                    var object = self.recodeTop[i];
                    if (object) {
                        if ((arg[0] + 165) >= object.top) { // 如果大于这次，取下一次继续判断
                            i++;
                            if (self.recodeTop[i]) {
                                return compearTop(i);
                            } else {
                                i--;
                                return self.recodeTop[i];
                            }
                        }
                        if ((arg[0] + 165) <= object.top) { // 如果小于这次，返回上次
                            i--;
                            return self.recodeTop[i];
                        }
                    }
                }

                var o = compearTop(i);
                if (!o) {
                    return false;
                }
                //$.each(self.recodeTop,function(i,v){

                var time, $time, type, now;

                //  if((arg[0]+arg[1]+100)>=v.top){

                time = o.obj.attr("time");

                $time = self.sideArea.find("a[time=" + time + "]");

                now = o.obj.attr("now");

                self.cpu(["currentShowHide"], [$time.closest(".timelineBar"), $time]);
                type = o.obj.attr("type");
                Ymonth = o.obj.attr("Ymonth");

                // 把下拉对应的选中
                self.cpu(["selectCheckShow"], [time, type, now, Ymonth]);
                return false;
                // }
                //})
            },
            selectCheckShow:function (arg) {
                var arr = arg[0].split("-");
                var year = arr[0];
                var month = arr[1];
                var time, text;
                var $triggerSpan = self.timelineSelect.find(".triggerBtn").children("span");        // 现在 年份下拉得到焦点span
                var $span;                              // 根据参数得到对应的下拉里面的span

                if (self.timelineSelect.find(".dropList").find("span[time=" + arg[0] + "]").size() == 0) {
                    $.each(self.timelineSelect.find(".dropList").find('li'), function (a, b) {
                        var _span = $(b).find('span');
                        var liTime = _span.attr('time');
                        if (parseInt(liTime) == year) {
                            $span = _span;
                            time = year;
                        }
                    });
                    // $span = self.timelineSelect.find(".dropList").find("span[time="+year+"]");
                    // time = year;
                } else {

                    // $span = self.sideArea.find("li.current").find("a");
                    // time = arg[0];


                    $span = self.timelineSelect.find(".dropList").find("span[time=" + arg[0] + "]");
                    time = arg[0];

                }
                var $spanText = $span.text();
                var $li = $span.closest("li");
                var selectedYear = $triggerSpan.text($spanText).attr("time", time);
                $li.siblings().removeClass("current");
                $li.attr("class", "current");
                if (year == self.thisYear && (month == self.thisMonth || month == (self.thisMonth -1) ) &&arg[3] != 'true') {
                    self.hotMonth.hide();
                    return false;
                }

                if (self.yearMonthArr[year]) {
                    if (self.hotMonth.css("display") == "none" && self.hotMonth.attr("complete") == "false") {
                        self.hotMonth.show();
                        self.hotMonth.attr("complete", true);
                        if (selectedYear.attr("time") == year) {
                            self.view(["month"], [self.hotMonth, self.yearMonthArr[year]]);
                        }
                    }
                    var $dropListul = self.hotMonth.find("ul.dropListul");
                    var current1 = "", current2 = "", current3 = "";

                    if (arg[1] == "hotData") {
                        current1 = "current";
                    }
                    if (arg[1] == "yearAllData") {
                        current2 = "current";
                    }

                    //var str = '<li class="' + current1 + '" type="hotData"><a class="itemAnchor" href="javascript:void(0)" name="hotData"><i></i><span>热点信息</span></a></li><li class="' + current2 + '" type="yearAllData"><a class="itemAnchor" href="javascript:void(0)" name="hotData"><i></i><span>全年的信息</span></a></li>';
                    var str = '';
                    $.each(self.yearMonthArr[year], function (i, v) {
                        if (v == month) {
                            current3 = "current";
                        } else {
                            current3 = "";
                        }

                        str += '<li class="' + current3 + '" type="monthData"><a class="itemAnchor" href="javascript:void(0)" name="monthData"><i></i><span time="' + v + '">' + v + '月</span></a></li>';

                    });
                    $dropListul.html(str);
                    if (month) {
                        text = month + "月";
                    }
                    self.hotMonth.children("div.triggerBtn").find("span").text(text);
                    self.hotMonth.show();
                    self.event(["selectCheckEvent"], [$dropListul]);

                } else {
                    self.hotMonth.hide();
                }
            },
            selectHotCheck:function (arg) {
                var $dropListul = self.hotMonth.find("ul.dropListul");
                var str = "";
               //str = '<li class="current" type="hotData"><a class="itemAnchor" href="javascript:void(0)" name="hotData"><i></i><span>热点信息</span></a></li><li type="yearAllData"><a class="itemAnchor" href="javascript:void(0)" name="hotData"><i></i><span>全年的信息</span></a></li>';
                $.each(arg[0], function (i, v) {
                    str += '<li type="monthData"><a class="itemAnchor" href="javascript:void(0)" name="monthData"><i></i><span time="' + v + '">' + v + '月</span></a></li>';
                });
                var year = arg[1];
                self.hotMonth.show();
                $dropListul.html(str);
                self.event(["selectCheckEvent"], [$dropListul]);  //绑定点击事件在 热点信息和 月份之间切换
            },
            //月份数据中转
            transitMonthData:function (arg) {
                if (!arg) {
                    return false;
                }
                // {uid:self.uid,year:time.substr(0,4),month:time.substr(5,6)} $timePsBox


                $(window).off("scroll", self.scrollChangeLoad);
                self.cpu(["recodePsTimeTop"], [arg[1]]);
                self.model("monthdata", [arg[0], function (data) {
                    self.event(["removeLoading"], [arg[1]]);


                    // 添加关注、关注网页、好友合并模块
                    var curMonthTopData = data["current_month_top"] || {};
                    var friends = curMonthTopData["friends"] || [],
                        follows = curMonthTopData["follows"] || [],
                        followWebs = curMonthTopData["webs"] || [];
                    if(arg[1].find("li[type='current_month_top']").size() === 0 && (friends.length !== 0 || 
                        follows.length !== 0 || followWebs.length !== 0)) {
                        
                        var monthTopView = self.view(["current_month_top"],[arg[1], curMonthTopData]);

                        // 关注、关注网页、好友合并模块 的“+”按钮事件(陈海云添加)
                        if(monthTopView && monthTopView.find("a.social__more").size() !== 0) {
                            monthTopView.find("a.social__more").click(function() {
                                self.plug(["socialUsers"],[monthTopView,$(this).attr("type")]);
                            });
                        }
                    }


                    //$(window).on("scroll",self.scrollChangeLoad);
                    if (data.status == 0) {
                        // self.scrollLoadWhat(arg[1]);
                        self.cpu(["recodePsTimeTop"], [arg[1]]);
                        return;
                    }


                    $.each(data.topics, function (a, b) {
                        // if(b.type === "social") {
                        //     return;
                        // }

                        var view = self.view([b.type], [arg[1], b]);
                        
                        // 添加权限
                        if (view) {
                            self.cpu(["permissionShow"], [b.permission, view,b]);

                            /*if (view.prev('[name = "timeBox"]').size() != 0 && !view.hasClass("twoColumn")) {
                                if (view.prevAll(".sideRight").size() == 0) {
                                    view.attr("class", "sideRight");
                                    view.children("i").css("top", 65);
                                } else {
                                    var pr = view.prevAll(".sideRight").first();
                                    var pl = view.prevAll(".sideLeft").first();
                                    if (pr.size() != 0 && pl.size() != 0) {
                                        if (pr.offset().top + pr.height() < pl.offset().top + pl.height()) {
                                            view.attr("class", "sideRight");
                                            //view.children("i").css("top", 65);
                                        } else {
                                            //view.children("i").css("top", 35);
                                            view.attr("class", "sideLeft");
                                        }
                                    }

                                    var c, d, pre = view.prev();
                                    c = view.offset().top;
                                    if (pre[0]) {
                                        d = pre.offset().top;
                                    }
                                    if (d && c <= (d + 15) && !pre.hasClass('twoColumn')) {
                                        view.children('i.spinePointer').css('top', 65);
                                    }
                                }
                            }*/
                        }

                        if(view && view.find("a.social__more").size() !== 0) {
                            view.find("a.social__more").click(function() {
                                self.plug(["socialUsers"],[view,$(this).attr("type")]);
                            });
                        }

                        if(b.type === "answer") {
                            self.event(["answer"],[view]);
                        }

                        if(b.type === "join") {
                            self.event(["eventOfJoin"],[view]);
                        }
                    });
                    if (!data.isEnd) {            //  月份的时候才出现 翻页
                        self.view(["nextPage"], [arg[1], arg[2], arg[3], data.isEnd]);
                        arg[1].find(".nextPage").show();
                    } else {

                        arg[1].find(".nextPage").remove();
                    }

                    arg[1].attr("page", arg[0].page);

                    if (arg[2] == "Ymonth") {
                        arg[1].attr("Ymonth", "true");
                    }
                    arg[1].attr("lastTopicId", data.lastTopicId).attr("startScore", data.startScore).attr("isEnd", data.isEnd);

                    self.cpu(["lay"],[arg[1].children("ul.content")])
                    //$timePsBox.attr("total",data.total).attr("length",data.data.length);
                    //self.cpu(["psTime"],[self.timelineTree]);
                    //self.event(["newTimeAction"],[self.timelineTree]);


                    self.plug(['commentEasy'], [arg[1]]);
                    self.plug(['tip_up_left_black', "tip_up_right_black"], [arg[1]]);

                    if (self.action_dkcode == self.hd_UID) {
                        self.event(["changeSize"], [arg[1]]);
                        self.event(["timelineBoxHover"], [arg[1]]);
                    }
                    setTimeout(function(){
                        self.cpu(["recodePsTimeTop"], [arg[1]]);
                        //$(window).on("scroll",self.scrollChangeLoad);
                    },100);
                }]);
            },
            //年份数据中转
            transitYearData:function (arg) {
                self.cpu(["recodePsTimeTop"], [arg[1]]);
                // data $timebox
                $(window).off("scroll", self.scrollChangeLoad);
                self.model("data", [arg[0], function (data) {
                    self.event(["removeLoading"], [arg[1].children("ul")]);

                    $(window).on("scroll", self.scrollChangeLoad);
                    if (data.status == 0) {
                        self.cpu(["recodePsTimeTop"], [arg[1]]);

                        return;
                    }
                    var hasNow = true;
                    var hasMonth = false;
                    //self.timelineTree.find("li[timeArea="+time+"]").remove();
                    $.each(data.hots, function (a, b) {
                        // 判断排除含有当前月和上个月的数据

                        hasNow = self.cpu(["hasNow"], [b]);

                        if (!hasNow) {
                            var view = self.view([b.type], [arg[1], b]);
                            if (view) {
                                self.cpu(["permissionShow"], [b.permission, view,b]);
                            }
                        }
                    });
                    if (arg[0].year == self.thisYear) {
                        data.months = self.cpu(["arrRemoveArr"], [data.months, [self.thisMonth, self.prevMonth]]);
                    }
                    if (data.months.length > 0) {
                        self.yearMonthArr[arg[0].year] = data.months;
                        if (self.timelineSelect.find(".triggerBtn").find('span').attr("time") == arg[0].year) {
                            self.view(["month"], [self.hotMonth, data.months]);
                        }

                        //self.cpu(["selectHotCheck"],[data.months,arg[0].year]); // 生成下拉
                    }


                    self.cpu(["lay"], [arg[1].children("ul.content")]);
                    self.cpu(["psTime"], [self.timelineTree]);
                    //self.event(["newTimeAction"],[self.timelineTree]);


                    self.plug(['commentEasy'], [arg[1]]);
                    self.plug(['tip_up_left_black', "tip_up_right_black"], [arg[1]]);

                    if (self.action_dkcode == self.hd_UID) {
                        self.event(["changeSize"], [arg[1]]);
                        self.event(["timelineBoxHover"], [arg[1]]);
                    }

                }]);
            },
            //is monthdata
            transitYearOrMonth:function (arg) {
                var a = {isMonth:false};
                // $(window).off("scroll", self.scrollChangeLoad);
                self.model("data", [arg[0], function (data) {
                    self = 'sideArea' in self ? self : arg[1];//for timeline
                    if (data.status) {
                        arg[0].year > -10000 && (function () {
                            var html, currentLi = self.sideArea.find('.current');
                            html = '<ul class="child">';

                            var j = (function(){
                                for(var i = 2,len = self.CLASS_TIMELINE_NAV.data.length;i < len;i ++){
                                    if(self.CLASS_TIMELINE_NAV.data[i].date == arg[0].year){
                                        return i;
                                    }
                                }
                            })();
                            for (var i = 0, len = data.months.length; i < len; i++) {
                                var str = self.cpu(["numToStr"], [data.months[i]]);
                                var temptime = arg[0].year + '-' + data.months[i];
                                self.dateArray.push(temptime);
                                self.CLASS_TIMELINE_NAV.data.splice(j+i+1,0,{date:arg[0].year + '/' + data.months[i]});
                                str += '月';
                                html += '<li><a time="' + (temptime) + '" class="time">' + str + '</a></li>';
                                if(i == 0){
                                    a.firstStr = str;
                                }
                            }
                            html += '</ul>';
                            arg[2] && arg[2].append($(html));
                            $(window).on("scroll", self.scrollChangeLoad);
                        })();
                        a.isMonth = true;
                        a.months = data.months;
                        a.year = arg[0].year;
                    }
                    arg[3]&&arg[3](a);
                }]);
            },
            numToStr : function(num){
                var str = '';
                switch (parseInt(num)) {
                    case 1 :
                        str = '一';
                        break;
                    case 2 :
                        str = '二';
                        break;
                    case 3 :
                        str = '三';
                        break;
                    case 4 :
                        str = '四';
                        break;
                    case 5 :
                        str = '五';
                        break;
                    case 6 :
                        str = '六';
                        break;
                    case 7 :
                        str = '七';
                        break;
                    case 8 :
                        str = '八';
                        break;
                    case 9 :
                        str = '九';
                        break;
                    case 10 :
                        str = '十';
                        break;
                    case 11 :
                        str = '十一';
                        break;
                    case 12 :
                        str = '十二';
                        break;
                }
                return str;
            },
            hasNow:function (arg) {
                var date = new Date(arg[0].ctime * 1000);
                if (date.getFullYear() == self.thisYear && (date.getMonth() + 1 == self.thisMonth || date.getMonth() + 1 == self.thisMonth - 1)) {
                    return true;
                } else {
                    return false;
                }
            },
            arrRemoveArr:function (arg) {
                var a = arg[0];
                var b = arg[1];
                var arr = [];
                for (var i = 0; i < a.length; i++) {
                    var temp = false;
                    for (var j = 0; j < b.length; j++) {
                        if (a[i] == b[j])temp = true;
                    }
                    if (!temp) arr.push(a[i]);
                }
                return arr;
            },
            returnFriendly_date:function (arg) {
                if (String(arg[1]) == String(arg[0])) {
                    return false;
                } else {

                    var date = new Date(arg[1] * 1000);
                    var year, month, day, hours, minute;
                    year = date.getFullYear();
                    month = (date.getMonth() + 1);
                    day = date.getDate();
                    hours = date.getHours();
                    minute = date.getMinutes();


                    if (month < 10) {
                        month = "0" + month;
                    }
                    if (day < 10) {
                        day = "0" + day;
                    }
                    if (hours < 10) {
                        hours = "0" + hours;
                    }
                    if (minute < 10) {
                        minute = "0" + minute;
                    }

                    var friendly_dateline = year + "年" + month + "月" + day + "日 " + hours + ":" + minute;
                    return friendly_dateline;
                }
            },
            addTimelineSelect:function (arg) {
                $.each(self.timelineSelect.find("ul.dropListul").children(), function () {
                    var span = $(this).find("span[time]");
                    var time = span.attr("time");

                    if (arg[0] < time) {

                        var $this = $(this).closest("li").clone();
                        $this.html('<a href="javascript:void(0)" class="itemAnchor"><i></i><span time="' + arg[0] + '">' + arg[0] + '年</span></a>');
                        $(this).closest("li").after($this);
                        $this.click();
                        return false;
                    }
                });
            }
        };

        $.each(method, function (index, value) {
            if (value) {
                func = _class[value](arg);
                return func;
            }
        });
        return func;
    },
    iefix:function (method, arg) {
        var self = this;
        var _class = {
            returnScale:function (arg) {
                if ($.browser.msie && ($.browser.version == "7.0")) {
                    return -15;
                    //  arg[0].css({top:$(window).scrollTop()+40});
                }
                if ($.browser.msie && ($.browser.version == "8.0")) {

                    //  arg[0].css({top:$(window).scrollTop()+40});
                }
                if ($.browser.msie && ($.browser.version == "6.0")) {
                    return -15;
                    //  arg[0].css({top:$(window).scrollTop()+40});
                }

            }

        }
        $.each(method, function (index, value) {
            if (value) {
                return fn = _class[value](arg);
            }
        });
        return fn;
    },
    event:function (method, arg) {
        var self = this;
        var _class = {
            scrollToTop:function (arg) {
                arg[0].click(function () {
                    $(window).off("scroll", self.scrollChangeLoad);
                    $("html,body").animate({scrollTop:$("#timelineTree").offset().top}, $("#timelineTree").height() / 10, function () {

                        $(window).on("scroll", self.scrollChangeLoad);
                    });
                });
            },
            scroll:function (arg) {
                var setTimeId;
                var i = 0;
                self.scrollChangeShowModlueHeader = function () {
                    var win = $(this);
                    setTimeId = setTimeout(function (setTimeId) {
                        var thisTop = win.scrollTop();
                        var thisHeight = win.innerHeight();
                        if (thisTop > 550 && self.modlueHeader.css("position") != "absolute") {
                            self.modlueHeader.attr("style", "position:fixed;_position:absolute; _top:expression(document.documentElement.scrollTop+(parseInt(this.currentStyle.marginTop, 10)||38));width:819px;z-index:20;display:block");
                            $("#currentComposerAttachment").appendTo('#TopPostArea');
                        } else {
                            if (thisTop < 550) {

                                if (self.modlueHeader.css("display") != "none") {
                                    var rel = $("#currentComposerAttachment").val();

                                    $("#TopPostArea").find("div.distributeInfoBox").children().hide();

                                    var pointUp = $("#TopPostArea").find("div.distributeInfoBox").find("div.pointUp");
                                    if (rel == 0) {
                                        $("#TopPostArea").find('#distributeMsg').show();
                                        pointUp.css('margin-left', '22px');
                                    } else if (rel == 1) {
                                        $('#distributePhoto').show();
                                        pointUp.css('margin-left', '92px');
                                    } else {
                                        $('#distributeVideo').show();
                                        pointUp.css('margin-left', '162px');
                                    }

                                    $('#TopPostArea').find("div.pointUp").hide();
                                    $('#TopPostArea').find("div.TopPostBox").hide();

                                    $('#TopPostArea').find("div.TopPostBox").find("div.distributeInfo").appendTo($("#distributeInfoBody").find("div.distributeInfoBox"));
                                    $("#currentComposerAttachment").prependTo('#distributeInfoBody');
                                    $('#TopPostArea').find("div.TopPostBox").find("div.footer").appendTo($("#distributeInfoBody").find("div.distributeInfoBox"));
                                    //$("#distributeInfoBody").find("ul.composerAttachments").children().first().click();


                                 //   $("#distributeInfoBody").find("li[ref="+rel+"]").click();
                                 // if ($("#distributeInfoBody").find("textarea:visible").size()!=0&&$.trim($("#distributeInfoBody").find("textarea:visible"))!=""){
                                 //    $("#distributeInfoBody").find(".footer").show();
                                 // }else{
                                 //       $("#distributeInfoBody").find("textarea:visible").blur();
                                 //   }
                                }
                                self.modlueHeader.css({
                                    position:"static",
                                    width:820,
                                    "z-index":20,
                                    display:"none"
                                });

                            }

                        }
                        $(window).on("scroll", self.scrollChangeShowModlueHeader);

                    }, 100);
                    $(window).off("scroll", self.scrollChangeShowModlueHeader);
                };


                self.scrollChangeCheck = function () {
                    var win = $(this);
                    setTimeId = setTimeout(function (setTimeId) {
                        var thisTop = win.scrollTop();
                        var thisHeight = win.innerHeight();
                        $(window).on("scroll", self.scrollChangeCheck);
                        self.cpu(["scrollInCurrentNav"], [thisTop, thisHeight]);  // 滚到加载哪里就把导航条选中
                    }, 500);
                    $(window).off("scroll", self.scrollChangeCheck);
                };

                self.scrollChangeLoad = function (e) {
                    var win = $(this);
                    var param;
                    var time = [], timeArr;
                    setTimeId = setTimeout(function (setTimeId) {
                        var thisTop = win.scrollTop();
                        var thisHeight = win.innerHeight();

                        self.scrollLoadWhat = function ($timePsBox) {
                            time = $timePsBox.attr("time");
                            timeArr = String(time).split("-");

                            $(window).off("scroll", self.scrollChangeLoad);

                            if ($timePsBox.attr("isEnd") == "false") {
                                var page = parseInt($timePsBox.attr("page")) + 1;
                                var nowLoadNum = parseInt($timePsBox.attr("nowLoadNum")) || 0;

                                if (nowLoadNum == 2) {
                                    $timePsBox.find(".nextPage").show();

                                    return false;
                                }
                                var lastTopicId = $timePsBox.attr("lastTopicId");
                                var startScore = $timePsBox.attr("startScore");

                                // 请求月份
                                param = {
                                    uid:self.uid, year:timeArr[0], month:timeArr[1], page:page, lastTopicId:lastTopicId, startScore:startScore
                                }
                                if (self.birthday && timeArr[0] == self.birthYear && timeArr[1] == self.birthMonth) {
                                    param.birthday = self.birthday;
                                }

                                $timePsBox.find(".nextPage").hide();
                                $timePsBox.append("<div class='h100 loading'></div>");
                                nowLoadNum++;
                                $timePsBox.attr("nowLoadNum", nowLoadNum);
                                self.cpu(["transitMonthData"], [param, $timePsBox, time, page]);

                            } else {

                                $timePsBox.find(".nextPage").remove();

                                $sideTime = self.sideArea.find("li.current").next().find("a.time").first();
                                if ($sideTime.size() == 0) {
                                    var tempObj = self.sideArea.find("li.current").closest("li.selected");
                                    if (tempObj.next().size() != 0) {
                                        $sideTime = tempObj.next().find("a.time").first();
                                    } else {
                                        tempObj = tempObj.parent().closest("li.selected");
                                        if (tempObj.next().size() != 0) {
                                            $sideTime = tempObj.next().find("a.time").first();
                                        } else {
                                            return false;
                                        }
                                    }
                                }
                                var timelineBar = $(this).closest(".timelineBar");
                                time = $sideTime.attr("time");
                                timeArr = String(time).split("-");

                                if ($.inArray(time, self.timeLoadArray) == -1) {     //如果还未加载过

                                    var title = $sideTime.text();






                                    $timePsBox = self.view(["timelinePs1"], {time:time, title:title});

                                    //self.cpu(["setPsTime"],[$timePsBox,time,title]);
                                    self.timeLoadArray.push(time);
                                    self.cpu(['arraySplice'], time);
                                   // if (String(time).indexOf("-") == -1) {
                                   //     $timePsBox.attr("type", "hotData");
                                   //     param = {
                                   //         uid:self.uid, year:time
                                   //     }
                                   //     if (self.birthday && title == "出生") {
                                   //         param.birthday = self.birthday;
                                   //     }
                                   //     self.cpu(["transitYearData"], [param, $timePsBox]);   // 请求年份
                                   // } else {
                                   //     $timePsBox.attr("type", "monthData");
                                   //     param = {
                                   //         uid:self.uid, year:timeArr[0], month:timeArr[1]
                                   //     }
                                   //     if (self.birthday && title == "出生") {
                                   //         param.birthday = self.birthday;
                                   //     }
                                   //     self.cpu(["transitMonthData"], [param, $timePsBox, null, 1]);   // 请求月份
                                   // }
                                    var param = {
                                        uid:self.uid, year:time
                                    };
                                    var requestYear = function(monthData) {
                                        if (!monthData.isMonth) {
                                            $timePsBox.attr("type", "hotData");
                                            param = {
                                                uid:self.uid, year:time
                                            };
                                            if (self.birthday && title == "出生") {
                                                param.birthday = self.birthday;
                                            }
                                            self.cpu(["transitYearData"], [param, $timePsBox]);   // 请求年份
                                        } else {
                                            $timePsBox.attr("type", "monthData");
                                            param = {
                                                uid:self.uid, year:time.substr(0, 4), month:monthData.months[0] || time.substr(5, 6)
                                            };
                                            if (self.birthday && title == "出生") {
                                                param.birthday = self.birthday;
                                            }
                                            self.cpu(["transitMonthData"], [param, $timePsBox]);   // 请求月份
                                            if(monthData.months[0]){
                                                var h5 = $timePsBox.find('h5').first(),a = $timePsBox.find('a').first();
                                                h5.html(h5.html()+monthData.firstStr);
                                                $timePsBox.attr('time',($timePsBox.attr('time') + '-' + monthData.months[0]));
                                                a.attr('name',$timePsBox.attr('time'));
                                                self.timeLoadArray.push((time + '-' + monthData.months[0]));
                                            }
                                            if(String($sideTime.attr('time')).indexOf('-') != -1 && $sideTime.closest('li.selected').size() != 0){
                                                var h5 = $timePsBox.find('h5').first();
                                                h5.html($sideTime.closest('ul.child').prev().text() + h5.html());
                                            }
                                            self.cpu(["currentShowHide"], [timelineBar, $sideTime]);
                                        }

                                    };
                                    if (self.birthday && title == "出生") {
                                        param.birthday = self.birthday;
                                    }
                                    var timelineBar = $sideTime.closest(".timelineBar");

                                    var $current = self.cpu(["currentShowHide"], [timelineBar, $sideTime]);
                                    if (time.indexOf('-') == -1) {
                                        self.cpu(["transitYearOrMonth"], [param, $timePsBox, $current,requestYear]);   // 请求年份
                                    } else {
                                        requestYear({isMonth:true, months:[]})
                                    }

                                    //self.cpu(["recodePsTimeTop"],[$timePsBox]);  // 记录 标识的 坐标，用来判断滚动条是否滚到这里


                                }

                                $(window).on("scroll", self.scrollChangeLoad);
                                return false;

                                //$time = self.timelineTree.find("li[time==" + $sideTime.attr("time") + "]");

                            }

                        }

                        function scrollLoad($timePsBox) {
                            // //console.log($(window).height());
                            // //console.log($("#footer").offset().top-thisTop)
                            // var pinY = 0;
                            // if ($("body").height() > 700) {
                            //     pinY = 300;
                            // }
                            // if ($("body").height() < $(window).height() || ($("#footer").offset().top - thisTop < $(window).height() + pinY)) {
                            //     self.scrollLoadWhat($timePsBox);
                            // }


                            if($("#footer").offset().top-thisTop<$(window).height()){
                                self.scrollLoadWhat($timePsBox);

                            }
                            var ele = document.documentElement,
                                body = document.body,
                                isIe = ele.scrollTop == 0;
                            var wHeight = $(window).height(),
                                sTop = isIe ? body.scrollTop : ele.scrollTop,
                                sHeight = isIe ? body.scrollHeight : ele.scrollHeight;
                            if(wHeight + sTop == sHeight){
                                $(window).scrollTop(thisTop - 10);
                            }
                            /*else if($("#footer").offset().top + $('#footer').height() == $('body').height()) {
                                $(window).scrollTop(thisTop - 10);
                            }*/
                        }

                        var time = self.sideArea.find("li.current").find("a").attr("time");
                        var $time = self.timelineTree.find("li[time=" + time + "]");
                        scrollLoad($time);
                        $(window).on("scroll", self.scrollChangeLoad);
                    }, 500);
                    $(window).off("scroll", self.scrollChangeLoad);
                }

                /*绑定三种滚动事件
                 1、滚动加载数据
                 2、滚动选中对应导航
                 3、滚动显示头部导航
                 */
                $(window).off("scroll", self.scrollChangeLoad).on("scroll", self.scrollChangeLoad);
                $(window).on("scroll", self.scrollChangeCheck);
                $(window).on("scroll", self.scrollChangeShowModlueHeader);
            },
            current:function (arg) {
                var currentA = arg[0].find("li.current").children();    //得到current对象
                var type, now;
                if (currentA.size() == 0) {
                    alert("时间线需要返回“现在”");
                    return false;
                }
                var time = currentA.attr("time");
                var title = currentA.text();
                if ($.inArray(time, self.timeLoadArray) == -1) {
                    var $timePsBox = self.view(["timelinePs1"], {time:time, title:title});
                    
                    $timePsBox.attr("now", "true");
                    self.timeLoadArray.push(time);

                    self.cpu(['arraySplice'], time);

                    // 请求月份
                    $timePsBox.attr("type", "monthData");

                    var param = {
                        uid:self.uid, year:time.substr(0, 4), month:time.substr(5, 6), page:1
                    };
                    if (self.birthday && title == "出生") {
                        param.birthday = self.birthday;
                    }
                    self.cpu(["transitMonthData"], [param, $timePsBox]);

                    //self.cpu(["recodePsTimeTop"],[$timePsBox]);

                }
                self.currentClick = function (e) {
                    if (e.target.tagName == "A") {
                        var obj = $(e.target);

                        $(window).off("scroll", self.scrollChangeLoad);
                        if (obj.parent().hasClass("current")) {
                            return false;
                        }

                        var timelineBar = obj.closest(".timelineBar");

                        var $current = self.cpu(["currentShowHide"], [timelineBar, obj]);

                        time = $current.children().attr("time");

                        if ($.inArray(time, self.timeLoadArray) == -1) {     //如果还未加载过
                            var title = $current.text();
                            $timePsBox = self.view(["timelinePs1"], {time:time, title:title});
                            //self.cpu(["recodePsTimeTop"],[$timePsBox]);
                            self.cpu(["setPsTime"], [$timePsBox, time, title]);
                            self.timeLoadArray.push(time);

                            var requestYear = function(monthData) {
                                if (!monthData.isMonth) {
                                    $timePsBox.attr("type", "hotData");
                                    param = {
                                        uid:self.uid, year:time
                                    };
                                    if (self.birthday && title == "出生") {
                                        param.birthday = self.birthday;
                                    }
                                    self.cpu(["transitYearData"], [param, $timePsBox]);   // 请求年份
                                } else {
                                    $timePsBox.attr("type", "monthData");
                                    param = {
                                        uid:self.uid, year:time.substr(0, 4), month:monthData.months[0] || time.substr(5, 6)
                                    };
                                    if (self.birthday && title == "出生") {
                                        param.birthday = self.birthday;
                                    }
                                    self.cpu(["transitMonthData"], [param, $timePsBox]);   // 请求月份
                                    if(monthData.months[0]){
                                        var h5 = $timePsBox.find('h5').first(),a = $timePsBox.find('a').first();
                                        h5.html(h5.html()+monthData.firstStr);
                                        $timePsBox.attr('time',($timePsBox.attr('time') + '-' + monthData.months[0]));
                                        a.attr('name',$timePsBox.attr('time'));
                                        self.timeLoadArray.push((time + '-' + monthData.months[0]));
                                    }
                                    if(String(obj.attr('time')).indexOf('-') != -1 && obj.closest('li.selected').size() != 0){
                                        var h5 = $timePsBox.find('h5').first();
                                        h5.html(obj.closest('ul.child').prev().text() + h5.html());
                                    }
                                    self.yearMonthArr[monthData.year] = monthData.months;
                                    self.view(["month"], [self.hotMonth, monthData.months]);


                                }
                                type = $timePsBox.attr("type");
                                now = $timePsBox.attr("now");
                                self.cpu(["selectCheckShow"], [$timePsBox.attr('time') + '-' + monthData.months[0], type, now]);
                                self.cpu(["currentShowHide"], [timelineBar, obj]);
                            }
                            var param = {
                                uid:self.uid, year:time
                            };
                            if (self.birthday && title == "出生") {
                                param.birthday = self.birthday;
                            }
                            if (time.indexOf('-') == -1) {
                                self.cpu(["transitYearOrMonth"], [param, $timePsBox,$current, requestYear]);   // 请求年份
                            } else {
                                requestYear({isMonth:true, months:[]});
                            }


                        } else {
                            $timePsBox = self.timelineTree.children("li[time=" + time + "]");
                            type = $timePsBox.attr("type");
                            now = $timePsBox.attr("now");

                            self.cpu(["selectCheckShow"], [time, type, now]);

                        }
                        var a = $("a[name=" + time + "]");  //得到时间轴psTime 锚点坐标
                        $("html,body").animate({scrollTop:a.offset().top - 165}, 200);
                    }
                }
                arg[0].on("click", self.currentClick);
            },
            timeAreaShow:function (arg) {
                arg[0].click(function () {

                    var time = $(this).parent().attr("time");
                    var arr = time.split("~");
                    var thisYear = String(arr[0]).replace('/', '-');
                    $(this).parent().remove();
                    self.sideArea.find("a[time=" + thisYear + "]").click();
                });
            },
            psTime:function (arg) {
                /*arg[0].on("mouseover",function(){
                 console.log(1);
                 self.timelineCursor.css("visibility","hidden");
                 });
                 arg[0].on("mouseout",function(){
                 self.timelineCursor.css("visibility","");
                 });*/
            },
            selectCheckEvent:function (arg) {  // 点击热点信息的下拉。
                arg[0].children("li").click(function () {
                    var $this = $(this);
                    var _date = self.timelineSelect.children(".triggerBtn").find("span").attr("time");
                    var year = _date.split('-')[0];
                    var $current = $(this).attr("type");
                    var $triggerSpan = self.hotMonth.find(".triggerBtn").find("span");
                    var month = $(this).find("span").attr("time");
                    var date = year + "-" + month;
                    var title;

                    // 如果当前是热点信息，点击月份 remove 热点信息 load 月份
                    // 如果当前是月份，点击全年的信息 load其他月份数据
                    // 如果当前是热点信息，点击全年的信息 load 所有月份数据
                    // 如果当前是月份，点击热点信息 remove 所有月份数据， 加载该年热点信息

                    $this.attr("class", "current");
                    $this.siblings().removeClass("current");
                    self.hotMonth.find("div.triggerBtn").click();
                    switch ($current) {
                        case "hotData":
                            var $a = self.sideArea.find("a[time=" + _date + "]");
                            // $a.attr("time",year);
                            $.each(self.timelineTree.children("li[type=monthData]"), function () {
                                var time = $(this).attr("time");
                                var arr = time.split("-");
                                if (year == arr[0] && $.inArray(arr[1], self.yearMonthArr[year]) != -1) {
                                    $(this).remove();
                                }
                            });
                            title = year;
                            var $timePsBox = self.view(["timelinePs1"], {time:year, title:title + "年"});
                            $timePsBox.attr("type", "hotData");
                            var param = {
                                uid:self.uid, year:year
                            }
                            if (self.birthday && year == self.birthYear) {
                                param.birthday = self.birthday;
                            }
                            self.cpu(["transitYearData"], [param, $timePsBox]);   // 请求年份

                            $triggerSpan.text("热点信息").attr("time", year);

                            $a.attr("time", year);
                            self.timelineSelect.find('span[time=' + _date + ']').attr("time", year);
                            $.each(self.timeLoadArray, function (i, b) {
                                if (b.indexOf('-') != -1) {
                                    if (parseInt(b) == year) {
                                        if ((year == this.thisYear && b.split('-')[1] != self.thisMonth && b.split('-')[1] != self.prevMonth) || year != this.thisYear) {
                                            self.timeLoadArray[i] = year;
                                        }
                                    }
                                }
                            });
                            self.timeLoadArray.push(year);
                            break;
                        case "yearAllData":
                            self.hotMonth.find("ul.dropListul").children("li").attr("class", "");
                            self.hotMonth.find("ul.dropListul").children("li[type=monthData]").first().attr("class", "current");
                            var $a = self.sideArea.find("a[time=" + year + "]");
                            $a.attr("time", year + "-" + self.yearMonthArr[year][0]);

                            self.timelineTree.children("li[type=hotData][time=" + year + "]").remove();

                            $.each(self.yearMonthArr[year], function (i, v) {
                                date = year + "-" + v;
                                if (i == 0) {
                                    $triggerSpan.text(v + "月").attr("time", v);
                                    self.sideArea.find("a[time=" + year + "]").attr("time", year + "-" + v);
                                }
                                if ($.inArray(date, self.timeLoadArray) == -1) {
                                    title = year + "年" + v + "月";
                                    var $timePsBox = self.view(["timelinePs1"], {time:date, title:title});
                                    $timePsBox.attr("type", "monthData");

                                    var param = {
                                        uid:self.uid, year:year, month:v
                                    }
                                    if (self.birthday && year == self.birthYear && v == self.birthMonth) {
                                        param.birthday = self.birthday;
                                    }
                                    self.cpu(["transitMonthData"], [param, $timePsBox, "Ymonth"]);   // 请求月份

                                    self.timeLoadArray.push(date);
                                }
                            });


                            break;
                        case "monthData":
                            var $a = self.sideArea.find("a[time=" + year + "]");
                            // $a.attr("time", year + "-" + month);
                            // $(window).off("scroll",self.scrollChangeLoad);
                            title = year + "年" + self.numToStr(month) + "月";
                            var _time = year + "-" + month;
                            // self.sideArea.find("a[time="+year+"]").attr("time",year+"-"+month);
                            // self.sideArea.find("li.current").find('a').attr("time", _time);
                            // self.timelineSelect.find('span[time='+ year +']').attr("time",year+"-"+month);
                            self.timelineSelect.find("li.current").find('span').attr("time", _time);


                            var param = {
                                uid:self.uid, year:year, month:month
                            };
                            if (self.birthday && year == self.birthYear && month == self.birthMonth) {
                                param.birthday = self.birthday;
                            }
                            if ($.inArray(year + "-" + month, self.timeLoadArray) == -1) {
                                var yearLi = self.timelineTree.children("li[type=hotData][time=" + year + "]");
                                var monthLi = self.timelineTree.children("li[type=monthData][time=" + _time + "]");
                                if (yearLi) {
                                    yearLi.remove();
                                }
                                if (monthLi) {
                                    monthLi.remove();
                                }
                                var $timePsBox = self.view(["timelinePs1"], {time:_time, title:title});
                                $timePsBox.attr("type", "monthData");
                                self.cpu(["transitMonthData"], [param, $timePsBox, "Ymonth"]);   // 请求月份
                                self.timeLoadArray.push(_time);
                            }

                            $triggerSpan.text(month + "月").attr("time", month);
                            var a = $("a[name=" + _time + "]");  //得到时间轴psTime 锚点坐标
                            $("html,body").animate({scrollTop:a.offset().top - 165}, 200);
                            break;
                    }
                })
            },
            changeSize:function (arg) {
                arg[0].find("i.conResize").closest("span").off("click").on("click", function () {
                    var $li = $(this).closest("li");
                    var tid = $li.attr("id");

                    var highlight = $li.attr("highlight");

                    if (highlight == 1) {
                        highlight = 0;
                    } else {
                        highlight = 1;
                    }

                    self.model("heightlight", [
                        {tid:tid, highlight:highlight},
                        function (data) {
                            if (data.status == 1) {
                                if ($li.hasClass("twoColumn")) {
                                    $li.attr("class", "sideLeft");
                                    $li.children("i").css("top", 35);
                                    $li.attr("highlight", "0");
                                    $li.removeAttr("style");
                                    if ($li.attr("type") == "album" || ($li.attr("type") == "forward" && $li.attr("forwardtype") == "album")) {
                                        var firstPhoto = $li.find(".firstPhoto").find("img");
                                        var src = firstPhoto.attr("src");
                                        firstPhoto.attr("src", src.replace("_b", "_tm")).css("width", "auto");
                                        $li.find("ul.photoContent").children("li.show").removeClass("show").addClass("hide");
                                        $li.find("ul.photoContent").children("li").removeAttr("style");
                                        $li.find("ul.photoContent").removeClass("big");
                                    }
                                    if ($li.attr("type") == "video" || ($li.attr("type") == "forward" && $li.attr("forwardtype") == "video")) {
                                        var img = $li.find("div.media_prev").find("img").first();
                                        var showFlash = $li.find("div.media_prev").find("img").eq(1);
                                        img.width(403).height(300);
                                        showFlash.css({top:125, left:182});
                                        //缩小
                                        $li.find("div.media_disp embed").width(403).height(300);

                                        if($li.find("object[name=player]").size() !== 0) {
                                            $li.find("object[name=player]").attr({height:"300",width:"403"});
                                        }
                                    }

                                    $li.find(".editControl").find("span.tip_up_left_black").attr("tip", "放大");
                                } else {
                                    $li.attr("class", "twoColumn");
                                    $li.children("i").css("top", 1);
                                    $li.attr("highlight", "1");
                                    $li.attr("style", "margin:20px 0px");
                                    if ($li.attr("type") == "album" || ($li.attr("type") == "forward" && $li.attr("forwardtype") == "album")) {
                                        var firstPhoto = $li.find(".firstPhoto").find("img");
                                        var src = firstPhoto.attr("src");
                                        firstPhoto.attr("src", src.replace("_tm", "_b"));
                                        $li.find("ul.photoContent").children("li.hide").removeClass("hide").addClass("show");
                                        $li.find("ul.photoContent").children("li").removeAttr("style");
                                        $li.find("ul.photoContent").addClass("big");
                                    }
                                    if ($li.attr("type") == "video" || ($li.attr("type") == "forward" && $li.attr("forwardtype") == "video")) {
                                        var img = $li.find("div.media_prev").find("img").first();
                                        var showFlash = $li.find("div.media_prev").find("img").eq(1);
                                        img.width(838).height(600);
                                        showFlash.css({top:300, left:407});
                                        //放大
                                        $li.find("div.media_disp embed").width(838).height(600);
                                        
                                        if($li.find("object[name=player]").size() !== 0) {
                                            $li.find("object[name=player]").attr({height:"600",width:"838"});
                                        }
                                    }

                                    $li.find(".editControl").find("span.tip_up_left_black").attr("tip", "缩小");
                                }
                            }

                            self.cpu(["lay"], [arg[0].children("ul.content")]);
                        }
                    ]);
                });
                arg[0].find("li[name=changeDate]").click(function () {
                    var $li = $(this).closest("li[time]");
                    var tid = $li.attr("id");
                    var time = $li.attr("time");

                    var date = new Date(parseInt(time) * 1000);
                    var value = date.getFullYear() + "-" + (date.getMonth() + 1) + "-" + date.getDate();
                    var content = $("<div style='padding:10px'><div style='padding-bottom:10px;'>这应该添加到你的时间线的哪个地方？</div><p><input type='text' class='html_date' begin_year='" + self.html_date.attr("begin_year") + "' end_year='" + self.html_date.attr("end_year") + "' value='" + value + "' now='" + self.html_date.attr("now") + "' /></p></div>");
                    content.find(".html_date").calendar({button:false, time:false});

                    self.plug(["popUp"], [$li, content, "更改日期", function () {
                        var timeStr = content.find(".html_date").val();
                        self.model("changeDate", [
                            {tid:tid, timeStr:timeStr},
                            function (data) {
                                self.plug(["popUp"], [$li, '<div style="padding:10px">更改日期成功!</div>', "提示", function () {
                                    $.closePopUp();
                                }, '<span class="popBtns blueBtn callbackBtn">知道了</span>', 300]);
                            }
                        ]);
                        $.closePopUp();
                    }])
                })
                arg[0].find("li[name=delTopic]").click(function () {
                    var $li = $(this).closest("li[name=timeBox]");
                    var tid = $li.attr("id");
                    var time = $li.attr("time");
                    var $ul = $li.closest("ul.content");
                    var date = new Date(parseInt(time) * 1000);
                    var value = date.getFullYear() + "-" + (date.getMonth() + 1) + "-" + date.getDate();
                    var str = "<div style='padding:10px' class='clearfix'><p>确定删除吗？</p>";
//                    var content = $("<div style='padding:10px' class='clearfix'><p>确定删除吗？</p><p class='fr' ><input type='checkbox' id='v_isDelOld' style='position: relative;left: -2px;top: 3px;'><label class=''>是否删除原文件</label> </p></div>");
                    var isInfo = $(this).closest('li[name="timeBox"]').attr('type') != 'info';
                    if(isInfo){
                        str += "<p class='fr' ><input type='checkbox' id='v_isDelOld' style='position: relative;left: -2px;top: 3px;'><label class=''>是否删除原文件</label> </p>";
                    }
                    str += '</div>';
                    var content = $(str);
                    var fid = $li.attr("fid");
                    var method = {};
                    if (fid) {
                        method.fid = fid;
                    }
                    method.tid = tid;
                    self.plug(["popUp"], [$li, content, "删除帖子", function () {
                        var timeStr = content.find(".html_date").val();
                        isInfo && (method.isDelOld = $('#v_isDelOld')[0].checked ? 1: 0);
                        self.model("doDelTopic", [method, function (data) {
                            $li.remove();
                            self.cpu(["lay"], [$ul]);
                        }]);
                        $.closePopUp();
                    }]);
                });
            },
            nextPage:function (arg) {
                arg[0].find("div.nextPage").children().off("click").on("click", function () {


                    arg[0].append("<div class='h100 loading'></div>");
                    $(this).parent().remove();
                    arg[0].attr("nowLoadNum", 1);
                    var time = arg[0].attr("time");
                    var page = parseInt(arg[0].attr("page"));
                    page++;
                    var lastTopicId = arg[0].attr("lastTopicId");
                    var startScore = arg[0].attr("startScore");
                    self.cpu(["transitMonthData"], [
                        {uid:self.uid, year:time.substr(0, 4), month:time.substr(5, 6), page:page, lastTopicId:lastTopicId, startScore:startScore},
                        arg[0],
                        time,
                        page
                    ]);   // 请求月份
                });
            },
            newTimeAction:function (arg) {
                var ot = arg[0].offset().top;
                var ol = arg[0].offset().left;
                var ow = arg[0].width();
                var oh = arg[0].height();
                var psTimeArr = self.psTime
                self.thisClickFun = null;
                arg[0].find("ul.content").unbind("mousemove").bind("mousemove", function (e) {


                    var l = e.clientX;
                    var t = e.pageY;
                    var actionArea;
                    //  var psTimeBoolen = self.cpu(["returnPsTimeOverBoolen"],[t]); // 计算中间矩形区域，并且排除标识时间块的区域
                    var fixNum = self.iefix(["returnScale"], [null]) || 0;
                    self.inTimelineAreaBoolen = (l > ol + ow / 2 - 10) && (l < ol + ow / 2 + 10) && t > ot && t < ot + oh
                    if (self.inTimelineAreaBoolen) {
                        /*if(arg[0].css("cursor")!="url('..'+miscpath+'img/system/small.cur')"){
                         arg[0].css("cursor","url(..'+miscpath+'img/system/small.cur),none");
                         }*/
                        self.timelineCursor.css({"top":t - ot + fixNum - 10}).show();
                    } else {
                        //  arg[0].off("click",self.thisClickFun);
                        arg[0].css("cursor", "default");
                        self.timelineCursor.hide();

                    }

                    self.thisClickFun = function (ee) {       // click
                        if (self.inTimelineAreaBoolen) {
                            var thisY = parseInt(ee.pageY - fixNum);
                            _min = self.cpu(["returnMaxOrMin"], [thisY, "min", self.hMethod]); // 距离上面的最小刻度
                            _max = self.cpu(["returnMaxOrMin"], [thisY, "max", self.hMethod]); // 距离下面的最小刻度
                            if (_min && _max) {
                                var bfb = _min.c / (_min.c + _max.c);
                                var _minTime = $("#" + _min.id).attr("time");
                                var _maxTime = $("#" + _max.id).attr("time");
                                var _getTime = self.cpu(["timeDiff"], [_minTime, _maxTime, bfb])
                                //alert(_getTime);
                                self.addNewAction.css({
                                    top:t - ot - 20,
                                    left:0
                                }).show();
                                //console.log(_getTime);
                            } else {
                                var today = self.cpu(["today"], []);
                            }
                        } else {
                            if (!self.addNewActionOverBoolen) {
                                self.addNewAction.hide();
                            }
                        }
                    }
                    arg[0].off("click", self.thisClickFun).on("click", self.thisClickFun);
                });
            },
            addNewActionHover:function (arg) {
                self.addNewActionOverBoolen = false;
                arg[0].on("mouseover", function () {
                    self.addNewActionOverBoolen = true;
                });
                arg[0].on("mouseout", function () {
                    self.addNewActionOverBoolen = false;
                });
            },
            timelineBoxHover:function (arg) {
                arg[0].children("ul").children().on("mouseenter", function () {
                    $(this).find(".editControl").show();
                    $(this).addClass('enterBox');
                    $(this).children("i").addClass("enterBox");
                });
                arg[0].children("ul").children().on("mouseleave", function () {
                    $(this).find(".editControl").hide();
                    $(this).removeClass('enterBox');
                    $(this).children("i").removeClass("enterBox");
                });
            },
            /**
             * 时间线 分享处理
             * @param arg
             * @return {Boolean}
             */
            forward:function (arg) {
                var p = arg[0].closest("li[name=timeBox]");
                var name1 = p.find(".AuthorName").children("a").text();
                var name2;
                var name3;
                var value, $content, imgurl;
                var forwardid = p.attr("forwardid");
                var pp = arg[0].closest("ul.content").find("li#" + forwardid);
                var __data=arg[1]||{};
                if (p.find(".forwardContent").find(".memo").size() == 0) {
                    value = p.find(".infoContent").html();
                } else {
                    value = p.find(".forwardContent").find(".memo").html();
                }
                if (p.find(".oldAuthorName").size() != 0) {
                    name2 = p.find(".oldAuthorName").children("a").text().replace(":", "");
                    if(!name2) name2=p.find(".oldAuthorName").text();
                    name3 = name2;
                } else {
                    name3 = name1;
                }
                // var location = webpath + "main/index.php?c=index&m=index&action_dkcode=" + self.action_dkcode;
//                var location = mk_url('main/index/profile',{dkcode:__data.dkcode});
                var location =__data.author;
                var tpl={
                    'main':'<div class="laymoveText"><div class="zf_content shareBox"><textarea maxlength="140"></textarea>\
                            <div class="replyFor"><div class="shareTo"><label>同时评论给：</label><label class="replyCheck"><input type="checkbox" id="replyCheck"> {1}</label>\
                            </div><div class="tip countTxt"><span class="num">0</span>/140</div></div><div class="content">{0}</div></div></div>',
                    'info':'<span class="avatar"><img src="{0}"></span>\
                            <div class="avatar_info"><p><strong>状态更新</strong></p><p>由<span class="name"><a href="{1}">{2}</a></span>发布</p><p>{3}</p></div>',
                    'album':'<span class="avatar"><img width="92" src="{0}"></span>\
                             <div class="avatar_info"><p><strong>来自相册：</strong>{1}</p>\
                             <p>由<span class="name"><a href="{2}"> {3}</a></span>发布</p></div>',
                    'blog':'<div class="avatar_info"><h3><a href="{0}">{1}</a></h3><p>由<span class="name"><a href="{2}"> {3}</a></span>发布</p><p>{4}</p></div>',
                    'video':'<span class="avatar"><img width="92" src="{0}"></span>\
                             <div class="avatar_info">\
                                <p>{1}</p>\
                                <p>由<span class="name"><a href="{2}"> {3}</a></span>发布</p>\
                                <p>{4}</p></div>'
                }
                var typeFunction = function (type) {
                    switch (type) {
                        case "info":
                            $content=_format(tpl.main,_format(tpl.info,__data.avatar,location,__data.username,__data.content),name1);
                            break;
                        case 'blog':
                            $content=_format(tpl.main,_format(tpl.blog,'javascript:;',__data.title,location,name3,__data.content),name1);
                            break;
                        case "photo":
                        case "album":
                            imgurl=p.find("a.photoLink img").attr("src");
                            $content=_format(tpl.main,_format(tpl.album,imgurl,__data.title,location,name3),name1);
                            break;
                        case "video":
                        case 'web_video':
                        case "sharevideo":
                            imgurl=p.find("div.mediaContent img").attr("src");
                           $content=_format(tpl.main,_format(tpl.video,imgurl,__data.title,location,name3,__data.content),name1);
                           break;
                        case "change":
                            imgurl = p.find("div.mediaContent").find("img").attr("src");
                            $content = $('<div class="laymoveText"><div class="zf_content"><textarea maxlength="140"></textarea><div class="content"><div class="left"><img src="' + imgurl + '" style="width:120px; height:80px;" /></div><div class="right"><p>由<span class="name"><a href="'+ location+'">' + name3 + '</a></span>更新的头像</p></div></div></div><div class="replyFor"><p><input type="checkbox" id="replyCheck"><label for="replyCheck">同时评论给 ' + name1 + '</label></p></div></div>');
                            break;
                    }
                    return $($content);
                }

                if (p.attr("type") == "forward") {
                    if (p.attr("forwardType") && p.attr("forwardType") != "undefined") {
                        $content = typeFunction(p.attr("forwardType"));
                    } else {
                        self.plug(["popUp"], [p, '<div style="padding:10px">原始信息已被删除！无法进行操作</div>', "提示", function () {
                            $.closePopUp();
                        }, '<span class="popBtns blueBtn callbackBtn">知道了</span>', 400]);
                        return false;
                    }

                    $content.find("div.shareTo").append('<label class="replyCheckOld"><input type="checkbox" id="replyCheckOld"> '+name2+'</label>');
                } else {
                    $content = typeFunction(p.attr("type"));
                }

                /**
                 * 分享弹出事件
		         */
                self.plug(["popUp"], [arg[0], $content, "分享", function () {
                    var data = {};
                    data.content = $content.find("textarea").val();
                    var commentBox=arg[0].parents('div.commentBox');
                    data.action_uid=commentBox.attr('action_uid')||'';
                    //时间线上 分享不包含 page_type参数
                    var isTimeline=arg[0].attr('isTimeline');
                    if(!isTimeline) data.page_type=commentBox.attr('pagetype')||'';

                    data.tid = p.attr("id");
                    if (p.attr("forwardId") && p.attr("forwardId") != "undefined") {
                        data.fid = p.attr("forwardId");
                    } else {
                        data.fid = p.attr("id");
                    }
                    // 一次分享
                    if ($content.find("#replyCheck").attr("checked")) {
                        data.reply_now = p.attr("uid");
                        //data.object_id = p.find(".commentBox").attr("commentObjId");
                    }
                    // 二次分享
                    if ($content.find("#replyCheckOld").attr("checked")) {
                        data.reply_author = p.find(".oldAuthorName").attr("uid");
                        //data.object_id = p.attr("id");
                    }
                    self.model(["doShare"], [data, function (result) {
                        if (result.status) {
                            // 分享数+1(陈海云添加)
                            var num = p.find(".forward_count").text().replace("(","").replace(")","");
                            p.find(".forward_count").text(parseInt(num) || 0 + 1);
                            p.find("span.forward_count").addClass('cursorPointer').text("(" + (parseInt(num) + 1) + ")");
                            //原信息分享数+1
                            if (pp.size() != 0) {
                                num = pp.find(".forwardNum").text();
                                pp.find(".forwardNum").text(parseInt(num) + 1);
                            }
                            if (self.action_dkcode == self.hd_UID) {
                                var $timePsBox = self.timelineTree.find("li[now]")
                                self.view([result.data.type], [$timePsBox, result.data, "prepend"]);
                                self.plug(['commentEasy'], [$timePsBox]);
                                self.cpu(["lay"], [$timePsBox.children("ul.content")]);
                                self.event(["changeSize"], [$timePsBox]);
                                self.event(["timelineBoxHover"], [$timePsBox]);
                                var a = $timePsBox.find("a[name]");
                                $("html,body").animate({scrollTop:a.offset().top - 165}, 200);
                            }
                        }else{
                            alert(result.info);
                        }
                        $.closePopUp();
                    }]);
                }, '<span class="popBtns blueBtn callbackBtn">分享</span><span class="popBtns closeBtn">取消</span>',475]);

                this.shareBoxListener($content);
            },
            shareBoxListener:function(context){
                var textArea=context.find('textArea'),
                    countTxt=context.find('div.countTxt .num'),
                    val='';
                    len='',
                    maxLen=140;
                textArea.unbind('keyup').bind('keyup',function(e){
                    val=$(this).val();
                    len=val.length;
                    if(len<=maxLen)
                    countTxt.html(len);
                    else countTxt.html(count.substring(0,maxLen));
                });
            },
            answer:function(arg) {
                var $content = arg[0],
                    $getMoreBtn = $content.find("div.hy-answerMore a"),
                    $questionLst = $content.find("ul.hy-answerLst");

                if($getMoreBtn && $getMoreBtn.size() !== 0) {
                    $getMoreBtn.click(function() {
                        self.model(["socialUsers"],[{id:$content.attr("id"),type:"answer",page:1},function(data) {
                            var questions = data.data;
                            var str = "";

                            for(var i = 0, l = questions.length; i < l; i ++) {
                                var answersInfo = questions[i].answers.join(" · ");

                                answersInfo = (answersInfo.length > 33) ? answersInfo.substring(0,30) + "..." : answersInfo;

                                str += '<li question_id="' + questions[i].id + '"><h4 class="hy-answerTitle"><a onclick="$(this).showAsk({ispopBox:true,poll_id:\'' + questions[i].id + '\'})" href="javascript:;" style="font-size:12px;">' + questions[i].title + '</a></h4><p class="hy-answerContent">' + answersInfo + '</p></li>';
                            }

                            $questionLst.html(str).attr("style","height:180px !important;overflow-x:hidden;overflow-y:scroll;_overflow:scroll;_height:180px;");
                            $questionLst.children().last().css({"border-bottom":"0px"});

                            $content.hover(
                                function() {
                                    $questionLst.css({"overflow-y":"scroll"});
                                },
                                function(){
                                    $questionLst.css({"overflow-y":"hidden"});
                                }
                            );
                        }]);

                        $getMoreBtn.parent().hide();
                    });
                }
            },
            eventOfJoin:function(arg) {
                var $content = arg[0],
                    $showBtn = $content.find("a.J_showMoreEvent");

                $showBtn.click(function() {
                    self.plug(["showEventOfJoin"],[$content])
                });
            },
            removeLoading:function (arg) {
                arg[0].find(".loading").remove();
            },
            photoLink:function (arg) {
                var bool;
                /*
                 arg[0].find("a.photoLink").click(function(e){
                 var ele = $(this);
                 e.stopPropagation();
                 self.model(["albumPersition"],[{pid:$(this).attr("pid"),action_dkcode:$(this).attr("action_dkcode")},function(data){
                 if(data.status==0){
                 self.plug(["popUp"],[ele,'<div style="padding:10px">'+data.msg+'</div>',"提示",function(){
                 $.closePopUp();
                 },'<span class="popBtns blueBtn callbackBtn">知道了</span>',300]);
                 }else{
                 var picviewer = new CLASS_PICVIEWER();
                 picviewer.view('creatIframe',[ele.attr("url")]);
                 }
                 }]);
                 })
                 arg[0].find("a.albumLink").click(function(e){
                 var ele = $(this);
                 var picviewer = new CLASS_PICVIEWER();
                 picviewer.view('creatIframe',[ele.attr("url")]);
                 })
                 */
            }
        }
        var fn;
        $.each(method, function (index, value) {
            if (value) {
                return fn = _class[value](arg);
            }
        });
        return fn;
    },
    plug:function (method, arg) {
        var self = this;
        var _class = {
            tip_up_right_black:function (arg) {
                arg[0].find(".tip_up_right_black").tip({
                    direction:"up",
                    position:"right",
                    skin:"black",
                    clickHide:true,
                    key:1
                });
            },
            tip_up_left_black:function (arg) {
                arg[0].find(".tip_up_left_black").tip({
                    direction:"up",
                    position:"left",
                    skin:"black",
                    clickHide:true,
                    key:1
                });
            },
            msg:function (arg) {
                arg[0].find("[msg]").msg();
            },
            commentEasy:function (arg) {
                arg[0].children("ul.content").find('.commentBox:not(.hasComment)').not("[complete]").commentEasy({
                    minNum:3,
                    UID:CONFIG['u_id'],
                    userName:CONFIG['u_name'],
                    avatar:CONFIG['u_head'],
                    userPageUrl:$("#hd_userPageUrl").val(),
                    isShow:false,
                    isOnlyYou:false,
                    relay:true,
                    relayCallback:function (obj,_arg) {
                        $.ajax({
                            url:mk_url('main/share/share_info?'+_arg),
                            dataType:'jsonp'
                        }).then(function(q){
                            if(q.status){
                                self.event(["forward"], [obj, q.data]);
                            }
                        });
                    },
                    onLoadCallback:function () {
                    }

                });
            },
            popUp:function (arg) {
                arg[0].popUp({
                    width:arg[5] || 580,
                    title:arg[2],
                    content:arg[1],
                    buttons:arg[4] || '<span class="popBtns blueBtn callbackBtn">确定</span><span class="popBtns closeBtn">取消</span>',
                    mask:true,
                    maskMode:true,
                    callback:arg[3]
                });
            },
            socialUsers:function(arg) {
                var $view = arg[0],
                    type = arg[1],
                    fid = (type === "friends") ? self.uid : $view.attr("id");
                var data = {type:type,id:fid,page:1};

                var _arg = [
                    $view,
                    '<div id="j_socialMore" style="height:365px; padding:20px 10px; background:#fff;"><div class="comment_likeList clearfix" id="comment_userList" style="height:335px;"><ul class="clearfix"></ul></div><div style="height:12px; padding:10px; line-height:12px; text-align:left;"><a class="pre_page_btn" href="javascript:;">上一页</a> <a class="next_page_btn" href="javascript:;">下一页</a></div></div>',
                    "显示更多",
                    function(){},
                    '<span class="popBtns closeBtn">关闭</span>',
                    450
                ];

                var socialMorePanel,prePageBtn,nextPageBtn,pagePanel;

                var getDataOfPage = function(page) {
                    data.page = page || 1;
                    
                    self.model(["socialUsers"],[data,function(data) {
                        var html = '';
                        var users = data.data;

                        if(data.status === 1 && data.data && data.data.length !== 0) {
                            for(var i = 0, l = users.length; i < l; i ++) {
                                var href = users[i].dkcode ? mk_url("main/index/main",{dkcode:users[i].dkcode}) : users[i].web_url;
                                html += '<li class="clearfix" style="width:190px; height:65px; padding:10px 0px; float:left;"><a href="' + href + '" class="comment_likelist_userName" target="_parent"><img class="comment_userList_avatar" src="' + users[i].headpic + '" height="65" width="65" /></a><div class="likeInfo" style="width:100px;"><a href="' + href + '" class="comment_likelist_userName" target="_parent">' + users[i].name + '</a></div></li>';
                            }

                            html += '</ul></div>';
                            socialMorePanel.find("ul").html(html);
                            pagePanel.show().attr("page",page);

                            if(page === 0 || page === 1) {
                                prePageBtn.hide();
                            } else {
                                prePageBtn.show();
                            }
                            if(data.isEnd) {
                                nextPageBtn.hide();
                            } else {
                                nextPageBtn.show();
                            }
                        } else {
                            socialMorePanel.html('<div style="height:20px;text-align:center;">暂无数据</a>').height(20);
                        }
                    }]);
                };

                _class.popUp(_arg);

                socialMorePanel = $("#j_socialMore");
                prePageBtn = socialMorePanel.find("a.pre_page_btn");
                nextPageBtn = socialMorePanel.find("a.next_page_btn");
                pagePanel = prePageBtn.parent().hide();

                prePageBtn.click(function() {
                    getDataOfPage(parseInt(pagePanel.attr("page") || 0) - 1);
                });
                nextPageBtn.click(function() {
                    getDataOfPage(parseInt(pagePanel.attr("page") || 0) + 1);
                });

                getDataOfPage(1);
            },
            showEventOfJoin:function(arg) {
                var $view = arg[0],
                    type = "join",
                    fid = $view.attr("id");
                var data = {type:type,id:fid,page:1};

                var _arg = [
                    $view,
                    '<div id="J_showMoreEvent" style="height:350px; margin:0px; padding:10px; overflow:auto; overflow-x:hidden;"></div>',
                    "参与的活动",
                    function(){},
                    '<span class="popBtns closeBtn">关闭</span>',
                    450
                ];

                var eventsPanel;
                _class.popUp(_arg);
                eventsPanel = $("#J_showMoreEvent");

                self.model(["socialUsers"],[data,function(data) {
                    var html = '';
                    var events = data.data;

                    for(var i = 0, l = events.length; i < l; i ++) {
                        html += '<div style="height:50px; margin:0px; padding:5px 0px; border-bottom:1px solid #ccc;"><a href="#" style="display:black; height:50px; width:50px; float:left;"><img src="' + (events[i].cover || (CONFIG.misc_path) + 'img/default/event.jpg') + '" height="50" width="50" /></a><div style="height:35px; margin:0px 0px 0px 60px; line-height:1.7;"><a href="#" style="font-size:13px;"><b>' + events[i].title + '</b></a><br /><span style="font-size:13px; color:#999">' + events[i].join_time + '</span></div></div>';
                    }
                    eventsPanel.html(html);
                }]);
            }
        };
        $.each(method, function (index, value) {
            if (value) {
                return _class[value](arg);
            }
        });
    },
    model:function (method, arg) {
        var self = this;
        var _class = {
            timedata:function (arg) {
                $.djax({
                    //url:self.webpath+"timedata.txt",
                    url:mk_url("main/timeline/getTimelineYears"),
                    data:{uid:self.uid, hd_UID:self.hd_UID},
                    dataType:"json",
                    async:true,
                    success:function (data) {
                        if (data) {
                            //var data = eval("("+data+")");
                            arg[1](data.data);
                        }
                    },
                    error:function (data) {

                    }
                });
            },
            changeDate:function (arg) {
                $.djax({
                    url:mk_url("main/info/doSetCtime"),

                    dataType:"json",
                    async:true,
                    data:arg[0],
                    success:function (data) {
                        if (data) {
                            //var data = eval("("+data+")");

                            arg[1](data.data);
                        }
                    },
                    error:function (data) {

                    }
                });
            },
            doDelTopic:function (arg) {
                $.djax({
                    url:mk_url("main/info/doDelTopic"),


                    async:true,
                    data:arg[0],
                    dataType:"json",
                    success:function (data) {
                        if (data) {
                            //var data = eval("("+data+")");

                            arg[1](data);
                        }
                    }
                });
            },
            data:function (arg) {
                $.djax({
                    url:mk_url("main/timeline/getYearHottestFeeds"),
                    async:true,
                    data:arg[0],
                    dataType:"json",
                    aborted:false,
                    success:function (data) {
                        if (data) {
                            //var data = eval("("+data+")");

                            arg[1](data);
                        }
                    }
                });
            },
            monthdata:function (arg) {
                $.djax({
                    url:mk_url("main/timeline/getFragmentFeeds"),
                    async:false,
                    data:arg[0],
                    dataType:"json",
                    aborted:false,
                    success:function (data) {
                        if (data) {
                            //var data = eval("("+data+")");

                            arg[1](data);
                        }
                    }
                });
            },
            heightlight:function (arg) {
                $.djax({
                    url:mk_url("main/info/doUpdateHeightlight"),
                    async:true,
                    dataType:"json",
                    data:arg[0],
                    success:function (data) {
                        if (data) {
                            //var data = eval("("+data+")");
                            arg[1](data);
                        }
                    }
                });
            },
            /**
             * 时间线 分享提交
             * @param arg
             */
            doShare:function(arg) {
                $.ajax({
                    url:mk_url("main/share/doShare"),
                    type:'post',
                    dataType:"jsonp",
                    data:arg[0]
                }).then(function(data){
                    arg[1](data);
                });
            },
            albumPersition:function (arg) {
                $.djax({
                    // url:webpath + "single/album/?c=api&m=judgePhotoAccess",
                    url:mk_url('album/api/judgePhotoAccess'),
                    async:true,
                    dataType:"json",
                    data:arg[0],
                    success:function (data) {
                        if (data) {
                            arg[1](data);
                        }
                    }
                });
            },
            socialUsers:function(arg) {
                var data = arg[0];
                data.action_uid = $("#action_dkcode").val();

                $.djax({
                    // url:webpath + "main/index.php?c=timeline&m=getMoreInfo",
                    url:mk_url('main/timeline/getMoreInfo'),
                    async:true,
                    dataType:"json",
                    data:data,
                    success:function (data) {
                        if (data) {
                            arg[1](data);
                        }
                    }
                });
            }
        };

        return _class[method](arg);
    }
};

$(document).ready(function () {
    class_timeline = new CLASS_TIMELINE();
    class_timeline.init();
});
/*******************end:时间线主程序*********************/

/******************start:timeBox事件********************/
$(document).ready(function () {
    var $timelineTree = $('#timelineTree');


    /***显示下拉菜单***/
    $timelineTree.on('click', 'span.conWrap', function () {
        $(this).toggleClass('clickDown');
        if (!$(this).hasClass('midLine')) {
            $(this).find('>ul.editMenu').toggleClass('hide');
        }
    });

    $(".html_date").calendar({button:false, time:false});

    /***用户权限***/
    $('#shareRights').dropdown({
        top:22,
        position:'right',
        permission:{
            type:'blog'
        }
    });


    /***顶部发表框***/
    var distributeMsg = $("#distributeMsg");
    var distributePhoto = $("#distributePhoto");
    var distributeVideo = $("#distributeVideo");
    var distributeFoot = $("#distributeInfoBody").find("div.footer");
    var distributeInfoBody = $("#distributeInfoBody");
    var pointUp = $(".mainArea").find("div.pointUp");
    $('#TopPostArea').find('>ul.composerAttachments').on('click', 'li', function () {
        var index = $(this).index();
        $('#TopPostArea').find("div.TopPostBox").find("div.distributeInfo").appendTo(distributeInfoBody.find("div.distributeInfoBox"));
        // $('#TopPostArea').find("div.TopPostBox").find("div.footer").appendTo(distributeInfoBody.find("div.distributeInfoBox"));

        $('#TopPostArea').children('div.pointUp').show();
        $('#TopPostArea').children("div.TopPostBox").show();
        if (index == 0) {
            pointUp.css('margin-left', '22px');
            distributeMsg.show();
            distributeVideo.hide();
            distributePhoto.hide();
            $('#TopPostArea').find("div.TopPostBox").append(distributeMsg);
            $('#TopPostArea').find("div.TopPostBox").append(distributeFoot);
            $("#currentComposerAttachment").val(0);
            if ($("#myStatusTextArea").height() < 50) {
                $("#myStatusTextArea").css("height", 50)
            }
            distributeFoot.show();
        } else if (index == 1) {
            pointUp.css('margin-left', '92px');
            distributePhoto.show();
            distributeMsg.hide();
            distributeVideo.hide();
            distributePhoto.find("#photoFileOption").hide();
            distributePhoto.find("#photoUploadWay").show();
            distributePhoto.find("#snapshotPhotoFileOption").hide();
            distributeFoot.hide();
            $('#TopPostArea').find("div.TopPostBox").append(distributePhoto);
            $('#TopPostArea').find("div.TopPostBox").append(distributeFoot);
            $("#currentComposerAttachment").val(1);
        } else {
            pointUp.css('margin-left', '162px');
            distributeVideo.show();
            distributeMsg.hide();
            distributePhoto.hide();
            distributeVideo.find("#videoFileOption").hide();
            distributeVideo.find("#videoUploadWay").show();
            distributeFoot.hide();
            $('#TopPostArea').find("div.TopPostBox").append(distributeVideo);
            $('#TopPostArea').find("div.TopPostBox").append(distributeFoot);
            $("#currentComposerAttachment").val(2);
        }
    });

    /***视频播放***/

    //显示播放flash
    $timelineTree.on('click', 'div.media_prev', function () {
        var _self = this;
        //创建一个视频对象
        var videoController = new VideoController($(this));
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
    function VideoController(playBtn) {
        var videoType = playBtn.closest("[name=timeBox]").attr("type");

        this.currentVideoId = null;
        this.currentVideoParentDom = null;
        this.insertVideoToDom = function (_flashWrapId, _videoURL, _videoWidth, _videoHeight, _callfunc) {
            if (document.getElementById(_flashWrapId)) {
                this.currentVideoId = $("#" + _flashWrapId).closest("[type='video']").attr("fid") || _flashWrapId.toString().substring(0, 10);

                var playerUrl = CONFIG.misc_path + 'flash/video/player.swf?vid=' + _videoURL + '&mod=1&uid=' + CONFIG.u_id;

                if(videoType === "sharevideo") {
                    playerUrl = playBtn.attr("videourl") + '?vid=' + playBtn.attr("url") + '&mod=1&uid=' + CONFIG.u_id;
                }

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
                    'movie', playerUrl,
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

});
/*******************end:timeBox事件********************/