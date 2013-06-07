/**
 * @author:    lincy,wangwb,liangss
 * @created:   2012/02/10
 * @version:   v1.0
 * @desc:      时间线
 */



/*******************start:时间线主程序*********************/

function CLASS_TIMELINE(arg){
}
CLASS_TIMELINE.prototype= {
    init:function(){
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
        this.web_name = $("#name").text();
        this.addNewAction = $("#addNewAction");
        this.action_avatar = $("#action_avatar").val();
        this.web_avatar = $("#web_avatar").val();
        /* menuRight */
        this.$seemore = $('#seemore');
        this.$navList = $('#defaultTimeBox1');
        this.birthday = null;
        this.webId = CONFIG['web_id'];
        this.CLASS_TIMELINE_NAV = null;
        this.is_self = $("#is_self").val();
        this.addPlusPop = true;
        this.timeLineClickArray = [];
        if (this.html_date.size() != 0) {
            var arr = this.html_date.val().split("-");
            var date = new Date(arr[0], arr[1], arr[2]);

            this.thisYear = this.html_date.val().split('-')[0];
            this.thisMonth = this.html_date.val().split('-')[1];
            this.prevMonth = this.thisMonth - 1;
        }else {

        }
        //server time
        //     var date = new Date(CONFIG['time']*1000);

        //     this.thisYear = date.getFullYear();
        //     this.thisMonth = date.getMonth();
        //     this.prevMonth = date.getMonth()-1;

        // if(this.is_self!="1"){
        //     this.web_avatar = this.action_avatar;
        // }

        this.event(["addNewActionHover"],[self.addNewAction]);
        this.today = self.cpu(["today"]);
        this.todayArea = this.today.slice(0,this.today.lastIndexOf("-"));
        this.shareDestinationObjects = $("div.competence");
        this.timeLoadArray = [];
        this.vGlobal = {};
        this.uid = $("#action_dkcode").val()||$("#hd_UID").val();
        this.model("timedata",[{type:0},function(data){
            self.CLASS_TIMELINE_NAV = new CLASS_TIMELINE_NAV({
                content:self.sideArea,
                data:data
            });
            self.view(["timelineNav"],[self.timelineSelect,data]);
            self.CLASS_TIMELINE_NAV.init();
            self.dateArray = data.slice(0);
            class_postBox = new CLASS_WEBPOSTBOX({
                _class:self,
                classTimeLine : new CLASS_TIMELINE()
            });

            class_postBox.init($('#timelineTree div.webpost'));
            $(".selectDate").calendar({button:false,time:false,input:true,yearSelectCallBack:yearSelectCallBack});
            function yearSelectCallBack($e,year) {
                $.djax({
                    url:mk_url("webmain/timeline/getAliasOfDate"),
                    data:{web_id:webId, date:year},
                    dataType:"json",
                    async:true,
                    success:function (data) {
                        var temp = $e.parent().nextAll('span.sp_explain').find('span.input_msg');
                        if(data.status){
                            temp.html(data.data);
                        }else {
                            temp.html('年份说明');
                        }
                    },
                    error:function (data) {

                    }
                });

            }
            if($('#date_a')[0]){
                yearSelectCallBack($(".selectDate"),$('#date_a').val().split('-')[0] );
            }
            /***顶部发表框Begin***
             var pointUp = $("#TopPostArea").find("div.pointUp");
             var topPostArea = $('#TopPostArea').addClass("webpost");
             var tabContent=$(".tabContent");
             $(".TopPostBox").append(tabContent);
             var topPostBox= new CLASS_WEBPOSTBOX({
             _class:self
             });
             topPostBox.init(topPostArea);
             topPostArea.find("ul.postHead li").click(function(){
             $(".pointUp",topPostArea).show();
             $(".TopPostBox",topPostArea).show();
             });
             /***顶部发表框END***/
            $("[name='yearNum']").blur(validateDate);
            $("[name='sel_yearUnit']").bind("change",function(e){
                validateDate.call($("[name='yearNum']"),e)
            });
            self.event(["current"],[self.sideArea]);
            self.event(["scroll"],[self.sideArea]);
        }]);
        this.event(['navBnt'], [this.$seemore]);
        this.plug(["msg"], [$(".distributeMsg")]);
        self.event(["newTimeAction"],[self.timelineTree]);
        this.event(["scrollToTop"],[$("#scrollToTop")]);
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
                        _c.append(_W);
                        topPostBox.init('#addPlusPost');
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
        return tempView.view.call(this, method, arg);
    },
    cpu:function(method,arg){
        var self = this;
        var func = null;
        var _class={
            psTime:function(arg){

                $.each(arg[0].find("li[name=time]"),function(){
                    var id = $(this).attr("id");
                    var scale = ($(this).offset().top+15)+($(this).height()-15);
                    self.psTime[id] = ($(this).offset().top)+"-"+scale;
                });
            },
            returnMaxOrMin:function(arg){
                var arr1 = [];  //id,刻度,差值
                var arr2 = [];  //差值数组
                var arr3 = [];  //输出ID数组
                var temp;
                $.each(arg[2],function(id,b){
                    if(arg[1]=="min"){
                        if(b-arg[0]<0){
                            arr1.push({id:id,b:b,c:Math.abs(b-arg[0])});
                            arr2.push((Math.abs(b-arg[0])));
                        }
                    }else{
                        if(b-arg[0]>0){
                            arr1.push({id:id,b:b,c:Math.abs(b-arg[0])});
                            arr2.push((Math.abs(b-arg[0])));
                        }
                    }
                });
                len = arr1.length;
                for(var i=0;i<len;i++){
                    temp=arr2[i];
                    for(var j =0;j<len;j++){
                        if(arr2[j]>temp){
                            temp=arr1[i];
                            arr1[i]=arr1[j];
                            arr1[j]=temp;
                            temp=arr2[i];
                            arr2[i]=arr2[j];
                            arr2[j]=temp;
                        }
                    }
                }
                if(arr1){
                    return arr1[0];
                }else{
                    return false;
                }
            },
            timeDiff:function(arg){
                // 2012-2-1 2011-3-2
                var time,time1,time2,time3,_time;
                time = arg[0].split("-");
                time1 = new Date(time[0],time[1]-1,time[2]);
                time = arg[1].split("-");
                time2 = new Date(time[0],time[1]-1,time[2]);
                _time = parseInt((Math.abs(time1-time2)/1000/60/60/24));

                if(Math.abs(_time)==1){
                    return arg[0];
                }else{
                    time3 = (Math.abs(time1-time2)*arg[2])/1000/60/60/24;
                    time1.setDate(time1.getDate()-time3);
                    return time1.getFullYear()+"-"+(time1.getMonth()+1)+"-"+time1.getDate();
                }
            },
            today:function(arg){
                var _date = new Date();
                return _date.getFullYear()+"-"+(_date.getMonth()+1)+"-"+_date.getDate();
            },
            returnPsTimeOverBoolen:function(arg){
                var boolen = true;
                $.each(self.psTime,function(id,value){
                    var arr = value.split("-");

                    if(arg[0]+10<arr[1]&&arg[0]+10>arr[0]){
                        boolen =  false;
                    }
                });
                return boolen;
            },
            reScale:function(arg){
                self.hMethod = {};
                self.psTime = {};
                $.each(arg[0].find("li[scale]"),function(){
                    var id = $(this).attr("id");
                    self.hMethod[id] = $(this).offset().top;
                    if($(this).find("i").size()!=0){
                        self.hMethod[id] = $(this).find("i").offset().top;
                    }
                });
                self.cpu(["psTime"],[self.timelineTree]);
            },
            returnPrevTimeLi:function(arg){
                arg[0] = String(arg[0]).replace('/','-');
                var $li,that = this;
                $.each(self.timelineTree.find("li[name=pstime]"),function(){
                    var time = $(this).attr("time");
                    var arr;
                    if(time.indexOf("~")!=-1){
                        arr = time.split("~");
                        if(arr[0]==arr[1]){
                            $(this).attr("time",arr[0]).children().text("显示"+ that.toWanAndYi(arr[0]).replace('年',''));
                        }else {
                            if(parseInt(arr[0])<=parseInt(arg[0])&&parseInt(arr[1])>=parseInt(arg[0])){
                                $li = $(this);
                                if(parseInt(arr[0])>=(parseInt(arg[0])-1) ){
                                    $(this).attr("time",arr[0]).children().text("显示"+ that.toWanAndYi(arr[0]).replace('年',''));
                                }else{

                                    $(this).attr("time",arr[0]+"~"+arg[0]).children().text("显示"+ that.toWanAndYi(arr[0]).replace('年','') +"-"+ that.toWanAndYi(parseInt(arg[0])).replace('年',''));
                                }
                                return false;
                            }
                        }
                    }
                    if(parseInt(time) < parseInt(arg[0]) || time == arg[0]){        // 判断属于时间轴哪个时间标识前

                        $li = $(this);
                        return false;
                    }
                    if (String(time).slice(0, 4) == String(arg[0]).slice(0, 4)) { // 判断和当前某个时间标识重复，并且排除现在、上一个
                        var argMonth = String(arg[0]).split('-')[1],
                            argYear = String(arg[0]).split('-')[0],
                            timeMonth = String(time).split('-')[1] || 13;
                        if (argYear == self.thisYear) {
                            if (argMonth == self.thisMonth || (argMonth == self.prevMonth && timeMonth != self.thisMonth )) {
                                $li = $(this);
                                return false;
                            } else if (parseInt(argMonth) > parseInt(timeMonth)) {
                                $li = $(this);
                                return false;
                            }
                        } else if (parseInt(argMonth) > parseInt(timeMonth)) {
                            $li = $(this);
                            return false;
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
            setPsTime:function(arg){
                var time1,time2,title1,title2,index;
                var $timePsBox = arg[0];
                var time = arg[1];
                var title = arg[2];

                var $prev = $timePsBox.prev("li");
                if($prev.size()==0){
                    return false;
                }
                var $prevTime = $prev.attr("time");

                if($prevTime.indexOf("-")!=-1 && parseInt($prevTime) >= 0){ // 含有-
                    time2 = parseInt($prevTime.slice(0,4));
                    title2 = parseInt($prevTime.slice(0,4));
                }else{
                    if(parseInt($prevTime) < 0){
                        $.each(self.CLASS_TIMELINE_NAV.data,function(i,v){
                            if( v && parseInt(v.date || v) == parseInt($prevTime)){
                                (function (j) {
                                    time2 = self.CLASS_TIMELINE_NAV.data[j + 1];
                                    if (!time2 && self.CLASS_TIMELINE_NAV.data.length > (j+1)) {
                                        arguments.callee(j+1);
                                    }
                                })(i);
                                time2 = time2.date || time2;
                                title2 = time2;
                            }
                        });
                    }else{
                        time2 = parseInt($prevTime.slice(0,4))-1;
                        title2 = parseInt($prevTime.slice(0,4))-1;
                    }
                }
                if(title=="现在"){
                    if(self.dateArray.length>1){
                        if(self.dateArray[self.dateArray.length-1]&&self.dateArray[self.dateArray.length-1].date){
                            time1 = self.dateArray[self.dateArray.length-1].date;
                        }else{
                            time1 = self.dateArray[self.dateArray.length-1];
                        }
                        if(self.dateArray[self.dateArray.length-1]&&self.dateArray[self.dateArray.length-1].date){
                            title1 = self.dateArray[self.dateArray.length-1].title;
                        }else{
                            title1 = self.dateArray[self.dateArray.length-1];
                        }
                        // time1 = self.dateArray[self.dateArray.length-1].date;
                        // title1 = self.dateArray[self.dateArray.length-1].title;
                        if(time1&&title1){
                            self.view(["timelinePs2"],{time1:time1,title1:title1,time2:time2,title2:title2});
                        }
                    }else{
                        if(self.dateArray.length==1){
                            if(self.dateArray[0]&&self.dateArray[0].date){
                                time1 = self.dateArray[0].date;
                            }else{
                                time1 = self.dateArray[0];
                            }
                            if(self.dateArray[0]&&self.dateArray[0].title){
                                title1 = self.dateArray[0].title;
                            }else{
                                title1 = self.dateArray[0];
                            }
                            if(time1&&title1){
                                self.view(["timelinePs2"],{time1:time1,title1:title1,time2:time1,title2:title1});
                            }
                        }
                    }
                }else{
                    index = self.cpu(["arraySplice"],time);
                    if(index!=0){
                        if(self.dateArray[index-1] && self.dateArray[index-1].title != '现在'){

                            var tempData = self.dateArray[index-1];
                            time1 = tempData.date || tempData;
                            title1 =  tempData.title || parseInt(time1);
                            self.view(["timelinePs2"],{time1:time1,title1:self.cpu(["toWanAndYi"],[title1]),time2:time2,title2:self.cpu(["toWanAndYi"],[title2])});
                        }
                    }
                }
            },
            toWanAndYi:function (arg) {
                var str = arg;
                if (str < 0) {
                    var wan, yi;
                    yi = str / 100000000;
                    wan = str / 10000;
                    if((yi >> 0) != 0 ){
                        str = yi + '亿';
                    }else if((wan >> 0) != 0 ){
                        str = wan + '万';
                    }

                }
                if(/^-?\d+$/.test(parseInt(str))){
                    str += '年';
                }
                return String(str).replace(/^-/g, 'B.C ') ;
            },
            arraySplice:function(arg){
                var index;
                arg = (parseInt(arg) >= 0 && arg.indexOf('-') != -1) ? arg.replace('-','/') : arg;
                $.each(self.dateArray,function(a,b){
                    if(b&&(b==arg||b.date==arg)){
                        self.dateArray[a] = undefined;
                        index = a;
                    }
                    if(index){
                        return index;
                    }
                });
                return index;
            },
            currentShowHide:function(arg){
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
                            $current.attr("class","current");
                            return false;
                        }
                    })(obj);
                    $current.parents('li').attr("class","selected");
                } else {
                    timelineBar.find("ul.child").hide();
                    $current = obj.parent().attr("class", "current");
                    (function (obj) {
                        if (obj.closest("ul.child")[0]) {
                            obj.closest("ul.child").show();
                            var obj1 = obj.closest("ul.child").parent().attr('class', 'selected');
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
                        if (_rightHeight > _leftHeight) {
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
                    var a, b, val,
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
            returnPrevTimebox:function(arg){
                var time2 = arg[1];
                var obj;


                //今天：2012/04/27/10/28/55
                //>0年：2012/04/27000000
                //<0年: -100/11/12000000


                $.each(arg[0].find("li[name=timeBox]"),function(){
                    var time1 = $(this).attr("time");

                    var a = [time1,time2];
                    var c = [time1,time2];
                    var b =a.sort();
                    if(b[0] == c[0]){
                        obj = $(this);
                        return false;
                    }


                    // var _time = parseInt(time1-time2);
                    // if(_time<0){
                    //     obj = $(this);
                    //     return false;
                    // }
                });

                return obj;
            },
            // 记录坐标
            recodePsTimeTop:function(arg){
                // 不存在 插入 并且更新之前top值;
                self.recodeTop = [];
                $.each(self.timelineTree.find("li[name=pstime][class=time]"),function(){

                    self.recodeTop.push({obj:$(this),top:$(this).offset().top});
                });
            },
            //滚到2012 选中
            scrollInCurrentNav:function(arg){
                var i = 0;
                var compearTop = function(i){
                    var object = self.recodeTop[i];
                    if(object){
                        if((arg[0]+165)>=object.top){ // 如果大于这次，取下一次继续判断
                            i++;
                            if(self.recodeTop[i]){
                                return compearTop(i);
                            }else{
                                i--;
                                return self.recodeTop[i];
                            }
                        }else {
                            i--;
                            return self.recodeTop[i];
                        }
                    }
                };

                var o = compearTop(i);
                if(!o){
                    return false;
                }
                //$.each(self.recodeTop,function(i,v){

                var time,$time,type,now;

                //  if((arg[0]+arg[1]+100)>=v.top){
                time = o.obj.attr("time");
                $time = self.sideArea.find("a[time="+time+"]");
                now = o.obj.attr("now");
                self.cpu(["currentShowHide"],[$time.closest(".timelineBar"),$time]);
                type = o.obj.attr("type");

                Ymonth = o.obj.attr("Ymonth");

                // 把下拉对应的选中
                self.cpu(["selectCheckShow"],[time,type,now,Ymonth]);
                // }
                //})
            },
            selectCheckShow:function(arg){
                var arr,year,month;
                arr = String(arg[0]).split("-");
                year = parseInt(arg[0]) >= 0 ? arr[0] : arr[1] * -1;
                month = parseInt(arg[0]) >= 0 ? arr[1] : arr[2];

                var time,text;
                var $triggerSpan = self.timelineSelect.find(".triggerBtn").children("span");        // 现在 年份下拉得到焦点span
                var $span;                              // 根据参数得到对应的下拉里面的span

                // if(self.timelineSelect.find(".dropList").find("span[time="+arg[0]+"]").size()==0){
                //     $span = self.timelineSelect.find(".dropList").find("span[time="+year+"]");
                //     time = year;
                // }else{
                //     $span = self.timelineSelect.find(".dropList").find("span[time="+arg[0]+"]");
                //     time = arg[0];
                // }
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
                $triggerSpan.text($spanText).attr("time",time);
                $li.siblings().removeClass("current");
                $li.attr("class","current");

                if (year == self.thisYear && (month == self.thisMonth || month == (self.thisMonth -1) ) &&arg[3] != 'true') {
                    self.hotMonth.hide();
                    return false;
                }

                if(self.yearMonthArr[year]){
                    if(self.hotMonth.css("display")=="none"&&self.hotMonth.attr("complete")=="false"){
                        self.hotMonth.show();
                        self.hotMonth.attr("complete",true);
                        self.view(["month"],[self.hotMonth,self.yearMonthArr[year]]);
                    }
                    var $dropListul = self.hotMonth.find("ul.dropListul");
                    var str="";
                    var current1="",current2="",current3="";

                    if(arg[1]=="hotData"){
                        current1="current";
                    }
                    if(arg[1]=="yearAllData"){
                        current2="current";
                    }

                    // str='<li class="'+current1+'" type="hotData"><a class="itemAnchor" href="javascript:void(0)" name="hotData"><i></i><span>热点信息</span></a></li><li class="'+current2+'" type="yearAllData"><a class="itemAnchor" href="javascript:void(0)" name="hotData"><i></i><span>全年的动态</span></a></li>';

                    $.each(self.yearMonthArr[year],function(i,v){
                        if(v==month){
                            current3 = "current";
                        }else{
                            current3 = "";
                        }

                        str+='<li class="'+current3+'" type="monthData"><a class="itemAnchor" href="javascript:void(0)" name="monthData"><i></i><span time="'+v+'">'+v+'月</span></a></li>';
                    });
                    $dropListul.html(str);
                    if(month){
                        text = month+"月";
                    }
                    self.hotMonth.children("div.triggerBtn").find("span").text(text);
                    self.hotMonth.show();
                    self.event(["selectCheckEvent"],[$dropListul]);

                }else{
                    self.hotMonth.hide();
                }
            },
            selectHotCheck:function(arg){
                var $dropListul = self.hotMonth.find("ul.dropListul");
                var str="";
                str='<li class="current" type="hotData"><a class="itemAnchor" href="javascript:void(0)" name="hotData"><i></i><span>热点信息</span></a></li><li type="yearAllData"><a class="itemAnchor" href="javascript:void(0)" name="hotData"><i></i><span>全年的动态</span></a></li>';
                $.each(arg[0],function(i,v){
                    str+='<li type="monthData"><a class="itemAnchor" href="javascript:void(0)" name="monthData"><i></i><span time="'+v+'">'+v+'月</span></a></li>';
                });
                var year = arg[1];
                self.hotMonth.show();
                $dropListul.html(str);
                self.event(["selectCheckEvent"],[$dropListul]);  //绑定点击事件在 热点信息和 月份之间切换
            },
            //月份数据中转
            transitMonthData:function(arg){
                if(!arg){
                    return false;
                }
                // {webId :self.webId ,year:time.substr(0,4),month:time.substr(5,6)} $timePsBox
                self.model("monthdata",[arg[0],function(data){
                    self.event(["removeLoading"],[arg[1].children("ul")]);

                    if(data.status==0){
                        // self.scrollLoadWhat(arg[1]);
                        self.cpu(["recodePsTimeTop"],[arg[1]]);
                        return false;
                    }


                    $.each(data.topics,function(a,b){
                        self.view([b.type],[arg[1],b,data.topics.length]);
                    });
                    if(!data.isEnd){            //  月份的时候才出现 翻页
                        self.view(["nextPage"],[arg[1],arg[2],data.page,data.isEnd]);
                    }else{

                        arg[1].find(".nextPage").remove();
                    }
                    if(data.page){
                        arg[1].attr("page",data.page);
                    }
                    if(arg[2]=="Ymonth"){
                        arg[1].attr("Ymonth","true");
                    }
                    arg[1].attr("lastTopicId",data.lastTopicId).attr("isEnd",data.isEnd);

                    self.cpu(["lay"],[arg[1].children("ul.content")]);
                    //$timePsBox.attr("total",data.total).attr("length",data.data.length);
                    //self.cpu(["psTime"],[self.timelineTree]);
                    //self.event(["newTimeAction"],[self.timelineTree]);


                    self.plug(['commentEasy'],[arg[1]]);
                    self.plug(['tip_up_left_black',"tip_up_right_black"],[arg[1]]);

                    if(self.is_self){
                        self.event(["changeSize"],[arg[1]]);
                        self.event(["timelineBoxHover"],[arg[1]]);
                    }
                    self.cpu(["recodePsTimeTop"],[arg[1]]);
                    $(window).on("scroll",self.scrollChangeLoad);
                }]);
            },
            //年份数据中转
            transitYearData:function(arg){

                // data $timebox
                self.model("data",[arg[0],function(data){
                    self.event(["removeLoading"],[arg[1].children("ul")]);

                    if(data.status==0){
                        self.cpu(["recodePsTimeTop"],[arg[1]]);
                        return false;
                    }
                    var hasNow = true;
                    var hasMonth = false;
                    //self.timelineTree.find("li[timeArea="+time+"]").remove();
                    $.each(data.hots,function(a,b){

                        // 判断排除含有当前月和上个月的数据

                        hasNow = self.cpu(["hasNow"],[b]);

                        if(!hasNow){
                            self.view([b.type],[arg[1],b]);
                        }
                    });
                    if(arg.year < 0){           //  公元前的时候出现 翻页
                        if(!data.isEnd){            //  月份的时候才出现 翻页
                            self.view(["nextPage"],[arg[1],arg[2],data.page,data.isEnd]);
                        }else{

                            arg[1].find(".nextPage").remove();
                        }
                        if(data.page){
                            arg[1].attr("page",data.page);
                        }
                        if(arg[2]=="Ymonth"){
                            arg[1].attr("Ymonth","true");
                        }
                        arg[1].attr("lastTopicId",data.lastTopicId).attr("isEnd",data.isEnd);
                    }
                    if(arg[0].year==self.thisYear){
                        data.months = self.cpu(["arrRemoveArr"],[data.months,[self.thisMonth,self.prevMonth]])
                    }
                    if(data.months.length>0&&arg[0].year>0){
                        self.yearMonthArr[arg[0].year] = data.months;


                        self.view(["month"],[self.hotMonth,data.months]);
                        //self.cpu(["selectHotCheck"],[data.months,arg[0].year]); // 生成下拉
                    }





                    self.cpu(["lay"],[arg[1].children("ul.content")]);
                    self.cpu(["psTime"],[self.timelineTree]);
                    //self.event(["newTimeAction"],[self.timelineTree]);

                    $(window).on("scroll",self.scrollChangeLoad);


                    self.plug(['commentEasy'],[arg[1]]);
                    self.plug(['tip_up_left_black',"tip_up_right_black"],[arg[1]]);

                    if(self.is_self){
                        self.event(["changeSize"],[arg[1]]);
                        self.event(["timelineBoxHover"],[arg[1]]);
                    }
                    self.cpu(["recodePsTimeTop"],[arg[1]]);
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
                                var k,tempArr,tempYear,tempMonth,_tempdata = self.CLASS_TIMELINE_NAV.data;
                                for(var i = 0,len = _tempdata.length;i < len;i ++){
                                    tempArr = String(_tempdata[i].date).split('/');
                                    tempYear = tempArr[0];
                                    tempMonth = tempArr[1];
                                    if (tempYear == arg[0].year && (tempMonth > data.months[0] && (tempYear == self.thisYear && tempMonth != self.thisMonth && tempMonth != self.prevMonth || tempYear != self.thisYear) || !tempMonth)) {
                                        k = i;
                                    }
                                }
                                k = k || (_tempdata.length - 2);
                                return k;
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
                            arg[2] && arg[2].remove('ul.child').append($(html));
                            $(window).on("scroll", self.scrollChangeLoad);
                        })();
                        a.isMonth = true;
                        a.months = data.months;
                        a.year = arg[0].year;
                    }
                     arg[3]&&arg[3](a);
                }]);
            },
            hasNow:function(arg){
                var date = new Date(arg[0].ctime*1000);
                return date.getFullYear()==self.thisYear&&(date.getMonth()+1==self.thisMonth||date.getMonth()+1==self.thisMonth-1);
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
            arrRemoveArr:function(arg){
                var a = arg[0];
                var b = arg[1] ;
                var arr=[];
                for(var i=0;i<a.length;i++){
                    var temp=false;
                    for(var j=0;j<b.length;j++){
                        if(a[i]==b[j])temp=true;
                    }
                    if(!temp) arr.push(a[i]);
                }
                return arr;
            },
            returnFriendly_date:function(arg){
                var date = arg[0];
                var year,month,day,hours,minite,second;
                year = date.slice(0,4);
                month = date.slice(4,6);
                day = date.slice(8,10);
                hours = date.slice(8,9);
                minite = date.slice(10,11);
                second = date.slice(12,13);
                return year+"年"+month+"月"+day+"日 "+hours+":"+minite;
            },
            addTimelineSelect:function(arg){

                $.each(self.timelineSelect.find("ul.dropListul").children(),function(){
                    var span = $(this).find("span[time]");
                    var time = span.attr("time");

                    if(arg[0]<time){

                        var $this = $(this).closest("li").clone();
                        $this.html('<a href="javascript:void(0)" class="itemAnchor"><i></i><span time="'+arg[0]+'">'+arg[0]+'年</span></a>');
                        $(this).closest("li").after($this);
                        $this.click();
                        return false;
                    }
                });
            }
        };

        $.each(method,function(index,value){
            if(value){
                func = _class[value](arg);
                return func;
            }
        });
        return func;
    },
    iefix:function(method,arg){
        var self = this;
        var _class={
            returnScale:function(arg){
                if($.browser.msie&&($.browser.version=="7.0")){
                    return -15;
                    //  arg[0].css({top:$(window).scrollTop()+40});
                }
                if($.browser.msie&&($.browser.version=="8.0")){

                    //  arg[0].css({top:$(window).scrollTop()+40});
                }
                if($.browser.msie&&($.browser.version=="6.0")){
                    return -15;
                    //  arg[0].css({top:$(window).scrollTop()+40});
                }

            }

        };
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
            navBnt: function(args) {
                var li = $(".tabHead li");
                var liCritical = li.eq(3);
                var liGt = $(".tabHead li:gt(3)");
                if(li.size() > 4) {
                    liCritical.css("border", 0);
                    liGt.hide();
                    $(".menuRight").show();
                }
                args[0].live("click", function() {
                    var el = $(this);
                    liGt.show();
                    liCritical.css("border-right", "1px solid #E5E5E5");
                    self.$navList.addClass("twoColumn");
                    $(this).html("<a class='icon_more' href='#'></a>").addClass("big");
                });
            },
            scrollToTop:function(arg){
                arg[0].click(function(){
                     $("html,body").animate({scrollTop:$("#timelineTree").offset().top},400);
                });
            },
            scroll:function(arg){
                var setTimeId;
	            /***顶部发表框***/
	            var split = $(".split");
	            var distributeMsg = $("#distributeMsg");
	            var distributePhoto = $("#distributePhoto");
	            var distributeVideo = $("#distributeVideo");
	            var pointUp = $(".mainArea").find("div.pointUp");
	            var topPostArea = $("#TopPostArea").addClass("webpost");
	            self.scrollChangeShowModlueHeader = function() {
                    var win = $(this);
		            setTimeId = setTimeout(function(setTimeId) {
                        var thisTop = win.scrollTop();
                        var thisHeight = win.innerHeight();
						
                        if (thisTop > 550 && self.modlueHeader.css("position") != "absolute") {
	                        var $topPostBox = topPostArea.find(".TopPostBox").show();
                            var tabListHtml = $("#defaultTimeBox1").find(".tabContent");
	                        var rel = $("#currentComposerAttachment").val();
	                        self.modlueHeader.attr("style", "position:fixed;_position:absolute; top:38px; _top:expression(document.documentElement.scrollTop+(parseInt(this.currentStyle.marginTop, 10)||38));width:819px;z-index:999;display:block");
                            $("#currentComposerAttachment").appendTo('#TopPostArea');
							if($topPostBox.find(".tabContent").size() == 0) {
								$topPostBox.append(tabListHtml);
								$("[name='sel_yearUnit']").unbind("change");
							}

	                        /***顶部发表框Begin***/
                            delete class_postBox;
                            topPostBox= new CLASS_WEBPOSTBOX({
                                _class:self,
                                classTimeLine : new CLASS_TIMELINE()
                            });
                            topPostBox.init(topPostArea);
                            /***顶部发表框END***/
                        } else {
                            if (thisTop < 550) {
	                            $("#defaultTimeBox1 .webpost").append(topPostArea.find(".tabContent"));
                                self.modlueHeader.css({
                                    position:"static",
                                    width:820,
                                    "z-index":20,
                                    display:"none"
                                });

                            }

                        }

                    }, 100);
                };

                self.scrollChangeCheck = function(){
                    var win = $(this);
                    setTimeId = setTimeout(function(setTimeId){
                        var thisTop = win.scrollTop();
                        var thisHeight = win.innerHeight();
                        $(window).on("scroll",self.scrollChangeCheck);
                        self.cpu(["scrollInCurrentNav"],[thisTop,thisHeight]);  // 滚到加载哪里就把导航条选中
                    },500);
                    $(window).off("scroll",self.scrollChangeCheck);
                };

                self.scrollChangeLoad = function(e){
                    var win = $(this);
                    var param;
                    var time,timeArr;
                    setTimeId = setTimeout(function(setTimeId){
                        var thisTop = win.scrollTop();
                        var thisHeight = win.innerHeight();
                        self.scrollLoadWhat = function($timePsBox){
                            time = $timePsBox.attr("time");
                            timeArr = String(time).split("-");
                            if($timePsBox.attr("isEnd")=="false"){
                                var page = parseInt($timePsBox.attr("page"));
                                var lastTopicId = $timePsBox.attr("lastTopicId");
                                var startScore = $timePsBox.attr("startScore");

                                // 请求月份
                                param = {
                                    webId :self.webId ,year:timeArr[0],month:timeArr[1],page:page,lastTopicId:lastTopicId,startScore:startScore
                                };
                                if(self.birthday&&timeArr[0]==self.birthYear&&timeArr[1]==self.birthMonth){
                                    param.birthday = self.birthday;
                                }
                                self.cpu(["transitMonthData"],[param,$timePsBox,time,page]);
                            }else{
                                $timePsBox.find(".nextPage").remove();
                                $sideTime = self.sideArea.find("li.current").next().find("a.time").first();
                                if($sideTime.size()==0){
                                    var tempObj = self.sideArea.find("li.current").closest("li.selected");
                                    if (tempObj.next().size() != 0) {
                                        $sideTime = tempObj.next().find("a.time").first();
                                    }else {
                                        tempObj = tempObj.parent().closest("li.selected");
                                        if(tempObj.next().size() != 0){
                                            $sideTime = tempObj.next().find("a.time").first();
                                        }else {
                                            return false;
                                        }
                                    }
                                }




                                var timelineBar = $(this).closest(".timelineBar");
                                time = $sideTime.attr("time");
                                timeArr = String(time).split("-");

                                if($.inArray(time,self.timeLoadArray)==-1){     //如果还未加载过
                                    var title = $sideTime.html();
                                    $timePsBox = self.view(["timelinePs1"],{time:time,title:title});
                                    //self.cpu(["setPsTime"],[$timePsBox,time,title]);
                                    self.timeLoadArray.push(time);
                                    self.cpu(['arraySplice'],time);
                                    if(time.indexOf('-') == -1){
                                        self.timeLineClickArray.push(time);
                                    }

                                    var param = {
                                        webId:self.webId, year:time
                                    };
                                    var requestYear = function(monthData) {
                                        if (!monthData.isMonth) {
                                            $timePsBox.attr("type", "hotData");
                                            param = {
                                                webId:self.webId, year:time
                                            };
                                            if (self.birthday && title == "出生") {
                                                param.birthday = self.birthday;
                                            }
                                            self.cpu(["transitYearData"], [param, $timePsBox]);   // 请求年份
                                        } else {
                                            $timePsBox.attr("type", "monthData");
                                            param = {
                                                webId:self.webId, year:time < 0 ? time :time.substr(0, 4), month:monthData.months[0] || time.substr(5, 6)
                                            };
                                            if (self.birthday && title == "出生") {
                                                param.birthday = self.birthday;
                                            }
                                            self.cpu(["transitMonthData"], [param, $timePsBox]);   // 请求月份
                                            if(monthData.months[0]){
                                                var h5 = $timePsBox.find('h5').first(),a = $timePsBox.find('a').first();
                                                monthData.year > -10000 && h5.html(h5.html()+monthData.firstStr);
                                                $timePsBox.attr('time',($timePsBox.attr('time') + '-' + monthData.months[0]));
                                                a.attr('name',$timePsBox.attr('time'));
                                                self.timeLoadArray.push((time + '-' + monthData.months[0]));
                                            }
                                            if(String($sideTime.attr('time')).indexOf('-') != -1 && $sideTime.closest('li.selected').size() != 0){
//                                                var h5 = $timePsBox.find('h5').first();
//                                                h5.html($sideTime.closest('ul.child').prev().text() + h5.html());
                                                var h5 = $timePsBox.find('h5').first();
//                                        var str = monthData.year > -10000 ? obj.closest('ul.child').prev().text() + h5.html() :(obj.text());
                                                var str = monthData.year > -10000 ?(parseInt(time) > 0 && time.indexOf('-') != -1 ? obj.closest('ul.child').prev().text()  + h5.html() : h5.html()) :(obj.text());
                                                h5.html(str);
                                            }
                                            self.cpu(["currentShowHide"], [timelineBar, $sideTime]);
                                        }

                                    };
                                    if (self.birthday && title == "出生") {
                                        param.birthday = self.birthday;
                                    }
                                    var timelineBar = $sideTime.closest(".timelineBar");

                                    var $current = self.cpu(["currentShowHide"], [timelineBar, $sideTime]);
                                    if (time.indexOf('-') == -1 || time  < 0 ) {
                                        self.cpu(["transitYearOrMonth"], [param, $timePsBox, $current,requestYear]);   // 请求年份
                                    } else {
                                        requestYear({isMonth:true, months:[]})
                                    }


                                    //self.cpu(["recodePsTimeTop"],[$timePsBox]);  // 记录 标识的 坐标，用来判断滚动条是否滚到这里


                                }

                                return false;

                                // $time = self.timelineTree.find("li[time=="+$sideTime.attr("time")+"]");

                            }
                        };

                        function scrollLoad($timePsBox){
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
                            // else if($("#footer").offset().top + $('#footer').height() == $('body').height()) {
                            //     $(window).scrollTop(thisTop - 10);
                            // }
                        }
                        var time = self.sideArea.find("li.current").find("a").attr("time");
                        var $time = self.timelineTree.find("li[time="+time+"]");
                        scrollLoad($time);
                        $(window).on("scroll",self.scrollChangeLoad);
                    },500);
                    $(window).off("scroll",self.scrollChangeLoad);
                };

                /*绑定三种滚动事件
                 1、滚动加载数据
                 2、滚动选中对应导航
                 3、滚动显示头部导航
                 */
                $(window).on("scroll",self.scrollChangeLoad);
                $(window).on("scroll",self.scrollChangeCheck);
                $(window).on("scroll",self.scrollChangeShowModlueHeader);

            },
            current:function(arg){
                var currentA = arg[0].find("li.current").children();    //得到current对象
                var type,now;
                if(currentA.size()==0){
                    alert("时间线需要返回“现在”");
                    return false;
                }
                var time = currentA.attr("time");
                var title = currentA.text();
                if($.inArray(time,self.timeLoadArray)==-1){
                    var $timePsBox = self.view(["timelinePs1"],{time:time,title:title});
                    $timePsBox.attr("now","true");
                    self.timeLoadArray.push(time);
                    self.cpu(['arraySplice'],time);
                    if(time.indexOf('-') == -1){
                        self.timeLineClickArray.push(time);
                    }
                    // 请求月份
                    $timePsBox.attr("type","monthData");

                    var param = {
                        webId :self.webId ,year:time.substr(0,4),month:time.substr(5,6),page : 1
                    };
                    if(self.birthday&&title=="现在"){
                        param.birthday = self.birthday;
                    }
                    self.cpu(["transitMonthData"],[param,$timePsBox]);
                    //self.cpu(["recodePsTimeTop"],[$timePsBox]);

                }
                self.currentClick = function(e){
                    if(e.target.tagName=="A"){
                        var obj = $(e.target || e.srcElement);
                        $(window).off("scroll",self.scrollChangeLoad);

                        if(obj.parent().hasClass("current")){
                            return false;
                        }

                        var timelineBar = obj.closest(".timelineBar");
                        var $current = self.cpu(["currentShowHide"],[timelineBar,obj]);
                        time = (function(obj){
                            if(obj.children('ul').size() != 0){
                                var obj1 = obj.children('ul').find('li').first();
                                arguments.callee(obj1);
                            }else {
                                return obj.children().attr("time");
                            }
                        })($current);

                        if($.inArray(time,self.timeLoadArray)==-1){     //如果还未加载过
                            var title = $current.children('a').text();
                            $timePsBox = self.view(["timelinePs1"],{time:time,title:title});
                            //self.cpu(["recodePsTimeTop"],[$timePsBox]);
                            self.cpu(["setPsTime"],[$timePsBox,time,title]);
                            self.timeLoadArray.push(time);
                            if(time.indexOf('-') == -1){
                                self.timeLineClickArray.push(time);
                            }

                            var requestYear = function(monthData) {
                                if (!monthData.isMonth) {
                                    $timePsBox.attr("type", "hotData");
                                    param = {
                                        webId:self.webId, year:time
                                    };
                                    if (self.birthday && title == "出生") {
                                        param.birthday = self.birthday;
                                    }
                                    self.cpu(["transitYearData"], [param, $timePsBox]);   // 请求年份
                                } else {
                                    $timePsBox.attr("type", "monthData");
                                    var lastIndex = time.lastIndexOf('-');
                                    param = {
                                        webId:self.webId, year:lastIndex != -1 ? (time < 0 ? time :time.substr(0, lastIndex)) : time, month:monthData.months[0] || time.substr(lastIndex + 1, lastIndex +2 )
                                    };
                                    if (self.birthday && title == "出生") {
                                        param.birthday = self.birthday;
                                    }
                                    self.cpu(["transitMonthData"], [param, $timePsBox]);   // 请求月份
                                    if(monthData.months[0]){
                                        var h5 = $timePsBox.find('h5').first(),a = $timePsBox.find('a').first();
                                        monthData.year > -10000 && h5.html(h5.html()+monthData.firstStr);
                                        $timePsBox.attr('time',($timePsBox.attr('time') + '-' + monthData.months[0]));
                                        a.attr('name',$timePsBox.attr('time'));
                                        self.timeLoadArray.push((time + '-' + monthData.months[0]));
                                    }
                                    if(String(obj.attr('time')).indexOf('-') != -1 && obj.closest('li.selected').size() != 0 ){
                                        var h5 = $timePsBox.find('h5').first();
//                                        var str = monthData.year > -10000 ? obj.closest('ul.child').prev().text() + h5.html() :(obj.text());

                                        var str = monthData.year > -10000 ?(  time.lastIndexOf('-') != 0 ? obj.closest('ul.child').prev().text()  + h5.html() : h5.html()) :(obj.text());
                                        h5.html(str);
                                    }
                                    self.yearMonthArr[monthData.year] = monthData.months;
                                    self.view(["month"], [self.hotMonth, monthData.months]);


                                }
                                type = $timePsBox.attr("type");
                                now = $timePsBox.attr("now");
                                self.cpu(["selectCheckShow"], [$timePsBox.attr('time') + '-' + monthData.months[0], type, now]);
                                self.cpu(["currentShowHide"], [timelineBar, obj]);
                            };
                            var param = {
                                webId:self.webId, year:time
                            };
                            if (self.birthday && title == "出生") {
                                param.birthday = self.birthday;
                            }
                            if (time.indexOf('-') == -1 || time  < 0 ) {
                                self.cpu(["transitYearOrMonth"], [param, $timePsBox,$current, requestYear]);   // 请求年份
                            } else {
                                requestYear({isMonth:true, months:[],year:parseInt(obj.attr('time'))});
                            }

                        }else{
                            time = time <= -10000 ? (time + '-1') : time;
                            $timePsBox = self.timelineTree.children("li[time="+time+"]");
                            type = $timePsBox.attr("type");
                            now = $timePsBox.attr("now");

                            self.cpu(["selectCheckShow"],[time,type,now]);

                        }
                        var a = $("a[name="+time+"]");  //得到时间轴psTime 锚点坐标
                        $("html,body").animate({scrollTop:a.offset().top-165},200);
                    }
                };
                arg[0].on("click",self.currentClick);
            },
            timeAreaShow:function(arg){
                arg[0].click(function(){

                    var time = $(this).parent().attr("time");
                    var arr = time.split("~");
                    var thisYear = arr[0].replace(/\//g,'-');

                    self.sideArea.find("a[time="+thisYear+"]").click();
                });
            },
            psTime:function(arg){
                /*arg[0].on("mouseover",function(){
                 console.log(1);
                 self.timelineCursor.css("visibility","hidden");
                 });
                 arg[0].on("mouseout",function(){
                 self.timelineCursor.css("visibility","");
                 });*/
            },
            selectCheckEvent:function(arg){  // 点击热点信息的下拉。

                arg[0].children("li").click(function(){
                    var $this = $(this);
                    var year = self.timelineSelect.children(".triggerBtn").find("span").attr("time").split('-')[0];
                    var $current = $(this).attr("type");
                    var $triggerSpan = self.hotMonth.find(".triggerBtn").find("span");
                    var month = $(this).find("span").attr("time");
                    var date = year+"-"+month;
                    var title;

                    // 如果当前是热点信息，点击月份 remove 热点信息 load 月份
                    // 如果当前是月份，点击全年动态 load其他月份数据
                    // 如果当前是热点信息，点击全年动态 load 所有月份数据
                    // 如果当前是月份，点击热点信息 remove 所有月份数据， 加载该年热点信息

                    $this.attr("class","current");
                    $this.siblings().removeClass("current");
                    self.hotMonth.find("div.triggerBtn").click();
                    var $a,$timePsBox,param;
                    switch($current){
                        case "hotData":
                             $a = self.sideArea.find("a[time="+year+"]");
                            $a.attr("time",year);
                            $.each(self.timelineTree.children("li[type=monthData]"),function(){
                                var time = $(this).attr("time");
                                var arr = time.split("-");
                                if(year==arr[0]&&$.inArray(arr[1],self.yearMonthArr[year])!=-1){
                                    $(this).remove();
                                }
                            });
                            title = year;
                            $timePsBox = self.view(["timelinePs1"],{time:year,title:title+"年"});
                            $timePsBox.attr("type","hotData");
                            param = {
                                webId :self.webId ,year:year
                            };
                            if(self.birthday&&year==self.birthYear){
                                param.birthday = self.birthday;
                            }
                            self.cpu(["transitYearData"],[param,$timePsBox]);   // 请求年份

                            self.timeLoadArray.push(year);
                            self.cpu(['arraySplice'],year);
                            $triggerSpan.text("热点信息").attr("time",year);

                            break;
                        case "yearAllData":
                            $a = self.sideArea.find("a[time="+year+"]");
                            $a.attr("time",year+"-"+self.yearMonthArr[year][0]);

                            self.timelineTree.children("li[type=hotData][time="+year+"]").remove();

                            $.each(self.yearMonthArr[year],function(i,v){
                                date = year+"-"+v;
                                if(i==0){
                                    $triggerSpan.text(v+"月").attr("time",v);
                                    self.sideArea.find("a[time="+year+"]").attr("time",year+"-"+v);
                                }
                                if($.inArray(date,self.timeLoadArray)==-1){
                                    title = year+"年"+v+"月";
                                    var $timePsBox = self.view(["timelinePs1"],{time:date,title:title});
                                    $timePsBox.attr("type","monthData");

                                    var param = {
                                        webId :self.webId ,year:year,month:v
                                    };
                                    if(self.birthday&&year==self.birthYear&&v==self.birthMonth){
                                        param.birthday = self.birthday;
                                    }
                                    self.cpu(["transitMonthData"],[param,$timePsBox,"Ymonth"]);   // 请求月份

                                    self.timeLoadArray.push(date);
                                    self.cpu(['arraySplice'],date);
                                }
                            });

                            break;
//                        case "monthData":
//                            $a = self.sideArea.find("a[time="+year+"]");
////                            $a.attr("time",year+"-"+month);
//
//                            var yearLi = self.timelineTree.children("li[type=hotData][time="+year+"]");
//                            var monthLi = self.timelineTree.children("li[type=monthData][time="+year+"-"+month+"]")
//                            if(yearLi){
//                                yearLi.remove();
//                            }
//                            if(monthLi){
//                                monthLi.remove();
//                            }
//                            // $(window).off("scroll",self.scrollChangeLoad);
//                            title = year+"年"+month+"月";
//                            self.sideArea.find("a[time="+year+"]").attr("time",year+"-"+month);
//                            $timePsBox = self.view(["timelinePs1"],{time:year+"-"+month,title:title});
//                            $timePsBox.attr("type","monthData");
////                            self.timelineSelect.find('span[time='+ year +']').attr("time",year+"-"+month);
//                            param = {
//                                webId :self.webId ,year:year,month:month
//                            };
//                            if(self.birthday&&year==self.birthYear&&month==self.birthMonth){
//                                param.birthday = self.birthday;
//                            }
//                            self.cpu(["transitMonthData"],[param,$timePsBox,"Ymonth"]);   // 请求月份
//
//                            self.timeLoadArray.push(year+"-"+month);
//                            self.cpu(['arraySplice',year+"/"+month]);
//                            $triggerSpan.text(month+"月").attr("time",month);
//                            var a = $("a[name=" + _time + "]");  //得到时间轴psTime 锚点坐标
//                            $("html,body").animate({scrollTop:a.offset().top - 165}, 200);
//                            break;
                        case "monthData":
                            var $a = self.sideArea.find("a[time=" + year + "]");
//                            $a.attr("time", year + "-" + month);


                            // $(window).off("scroll",self.scrollChangeLoad);
                            title = year + "年" + self.numToStr(month) + "月";
                            var _time = year + "-" + month;
//                            self.sideArea.find("a[time="+year+"]").attr("time",year+"-"+month);
//                            self.sideArea.find("li.current").find('a').attr("time", _time);
//                            self.timelineSelect.find('span[time='+ year +']').attr("time",year+"-"+month);
                            self.timelineSelect.find("li.current").find('span').attr("time", _time);


                            var param = {
                                webId :self.webId, year:year, month:month
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
            changeSize:function(arg){
                arg[0].find("i.conResize").closest("span").off("click").on("click",function(){

                    var $li = $(this).closest("li");
                    var tid = $li.attr("id");

                    var highlight = $li.attr("highlight");

                    if(highlight==1){
                        highlight = 0;
                    }else{
                        highlight = 1;
                    }

                    self.model("highlight",[{tid:tid,highlight:highlight},function(data){
                        if(data.status==1){
                            var firstPhoto,src,img,showFlash;
                            if($li.hasClass("twoColumn")){
                                $li.attr("class","sideLeft");
                                $li.children("i").css("top",35);
                                $li.attr("highlight","0");
                                $li.removeAttr("style");
                                if($li.attr("type")=="album"||($li.attr("type")=="forward"&&$li.attr("forwardtype")=="album")){
                                    firstPhoto =$li.find(".firstPhoto").find("img");
                                    src = firstPhoto.attr("src");
                                    firstPhoto.attr("src",src.replace("_tb","_tm")).css("width","auto");
                                    $li.find("ul.photoContent").children("li.show").removeClass("show").addClass("hide");
                                }
                                if($li.attr("type")=="video"||($li.attr("type")=="forward"&&$li.attr("forwardtype")=="video")){
                                    img =$li.find("div.media_prev").find("img").first();
                                    showFlash = $li.find("div.media_prev").find("img").eq(1);
                                    img.width(403).height(300);
                                    showFlash.css({top:125,left:182});
                                    //缩小
                                    $li.find("div.media_disp embed").width(403).height(300);
                                }

                                $li.find(".midLine").attr("tip","放大");

                            }else{
                                $li.attr("class","twoColumn");
                                $li.children("i").css("top",1);
                                $li.attr("highlight","1");
                                $li.attr("style","margin:20px 0px");
                                if($li.attr("type")=="album"||($li.attr("type")=="forward"&&$li.attr("forwardtype")=="album")){
                                    firstPhoto =$li.find(".firstPhoto").find("img");
                                    src = firstPhoto.attr("src");
                                    firstPhoto.attr("src",src.replace("_tm","_b"));
                                    $li.find("ul.photoContent").children("li.hide").removeClass("hide").addClass("show");
                                }
                                if($li.attr("type")=="video"||($li.attr("type")=="forward"&&$li.attr("forwardtype")=="video")){
                                    img =$li.find("div.media_prev").find("img").first();
                                    showFlash = $li.find("div.media_prev").find("img").eq(1);
                                    img.width(838).height(600);
                                    showFlash.css({top:300,left:407});
                                    //放大
                                    $li.find("div.media_disp embed").width(838).height(600);
                                }

                                $li.find(".midLine").attr("tip","缩小");

                            }
                        }

                        self.cpu(["lay"],[arg[0].children("ul.content")]);
                    }]);
                });
                arg[0].find("li[name=changeDate]").click(function(){
                    var $li = $(this).closest("li[time]");
                    var tid = $li.attr("id");
                    var time = $li.attr("time");
                    var timeData = $li.attr('timedata');


                    var year,month,date,yearLast,parentElm,parentElm1,parentElm2,index,twoSpan,isgt;
                    isgt = time >= 0 ;
                    index = isgt ? 10 : 4;
                    yearLast = time.length - index;
                    year = time.slice(0,yearLast);
                    month = time.substr(yearLast,2);
                    date = time.substr(yearLast + 2,2);
                    year = timeData.split(',')[0];
                    month = timeData.split(',')[1];
                    date = timeData.split(',')[2];
                    // var date = new Date(parseInt(time)*1000);
                    var value =  isgt ? year + "-" + month + "-" + date : self.html_date.attr("now");
                    var content = $("<div style='padding:10px' id='changeDate'><p>点击选择</p><p>" +'<div class="webpost"><div class="tabFooter clearfix" style="background: none;border:none"><span class="sp_selectChristian"><select name="sel_christian"><option value="0">公元</option><option value="1">公元前</option></select></span><span rel="0" class="sp_selectDate" >' +"<input type='text' class='html_date' begin_year='"+self.html_date.attr("begin_year")+"' end_year='"+self.html_date.attr("end_year")+"' value='"+value+"' now='"+self.html_date.attr("now")+"' />"+'</span><span rel="1" class="sp_date" ><input type="text" class="yearNum" name="yearNum" maxlength="4"><select name="sel_yearUnit"><option value="1">年</option><option value="10000">万年</option><option value="100000000">亿年</option></select><select name="sel_month" style=""><option value="">月</option><option value="1" rel="31">1月</option><option value="2" rel="29">2月</option><option value="3" rel="31">3月</option><option value="4" rel="30">4月</option><option value="5" rel="31">5月</option><option value="6" rel="30">6月</option><option value="7" rel="31">7月</option><option value="8" rel="31">8月</option><option value="9" rel="30">9月</option><option value="10" rel="31">10月</option><option value="11" rel="30">11月</option><option value="12" rel="31">12月</option></select><select name="sel_Days" style="display:None;"></select></span></p></div> </div>');
                    content.find(".html_date").calendar({button:false,time:false});
                    self.plug(["popUp"],[$li,content,"修改日期",function(){
                        var bc = parentElm1.find('select').val() == 0 ? 1 : -1; //-1（前），1（后）
                        var timeStr = (bc > 0 ? content.find(".html_date").val() : (Math.round(parentElm2.find('input').val())*parentElm2.find('select').first().val() + '-' + (Math.round(parentElm2.find('select').eq(1).val()) || 1)));
                        self.model("changeDate",[{tid:tid,timeStr:timeStr,bc:bc},function(data){
                            self.plug(["popUp"],[$li,'<div style="padding:10px">修改日期成功!</div>',"提示",function(){
                                $.closePopUp();
                            },'<span class="popBtns blueBtn callbackBtn">知道了</span>',300]);
                        }]);
                        $.closePopUp();
                    }]);
                    parentElm = $('#changeDate');
                    parentElm1 = parentElm.find('span.sp_selectChristian');
                    parentElm2 = parentElm.find('span.sp_date');
                    twoSpan = parentElm.find('span[rel]').hide();
                    if(isgt){
                        parentElm1.find('select').val(0);
                        twoSpan.first().show();
                    }else {
                        var num,val,_time;
                        parentElm1.find('select').val(1);
                        _time = wanoryi(year);
                        switch(_time.isWhat){
                            case 1 :
                                num = 100000000;
                                break;
                            case 0 :
                                num = 10000;
                                break;
                            case -1 :
                                num = 1;
                                break;
                        }
                        parentElm2.find('input').val(Math.abs(_time.val));
                        parentElm2.find('select').eq(0).val(num);
                        parentElm2.find('select').eq(1).val(month);
                        twoSpan.last().show();
                    }
                    class_postBox2 = new CLASS_WEBPOSTBOX({_class:self});
                    class_postBox2.init($('#changeDate div.webpost'));



                    function wanoryi(num){
                        var wan,yi,isWhat,val;
                        yi = num / 100000000;
                        wan = num / 10000;
                        if(yi.toFixed(0) != 0 ){
                            isWhat = 1;
                            val = yi;
                        }else if( wan.toFixed(0) != 0 ){
                            isWhat = 0;
                            val = wan;
                        }else {
                            isWhat = -1;
                            val = num;
                        }
                        return {val : val,isWhat : isWhat};
                    }

                });
                arg[0].find("li[name=delTopic]").click(function(){
                    var $li = $(this).closest("li[name=timeBox]");
                    var tid = $li.attr("id");
                    var time = $li.attr("time");
                    var $ul = $li.closest("ul.content");
                    var date = new Date(parseInt(time)*1000);
                    var value = date.getFullYear()+"-"+(date.getMonth()+1)+"-"+date.getDate();
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
                    if(fid){
                        method.fid = fid;
                    }
                    method.tid = tid;
                    method.web_id = self.webId;
                    method.padgeid = self.webId;
                    self.plug(["popUp"],[$li,content,"删除帖子",function(){
                        var timeStr = content.find(".html_date").val();

                        isInfo && (method.isDelOld = $('#v_isDelOld')[0].checked ? 1: 0);
                        self.model("doDelTopic",[method,function(data){
                            if(data.stauts!=0){
                                self.plug(["popUp"],[$li,'<div style="padding:10px">删除成功!</div>',"提示",function(){
                                    $.closePopUp();
                                },'<span class="popBtns blueBtn callbackBtn">知道了</span>',300]);
                            }
                            $li.remove();
                            self.cpu(["lay"],[$ul]);
                        }]);
                        $.closePopUp();

                    }]);

                });
            },
            nextPage:function(arg){
                arg[0].find("div.nextPage").off("click").on("click",function(){
                    var time = arg[0].attr("time");
                    var page = arg[0].attr("page");
                    var lastTopicId = arg[0].attr("lastTopicId");
                    var startScore = arg[0].attr("startScore");
                    self.cpu(["transitMonthData"],[{webId :self.webId ,year:time.substr(0,4),month:time.substr(5,6),page:page,lastTopicId:lastTopicId,startScore:startScore},arg[0],time,page]);   // 请求月份
                });
            },
            newTimeAction:function(arg){
                var ot = arg[0].offset().top;
                var ol = arg[0].offset().left;
                var ow = arg[0].width();
                var oh = arg[0].height();
                var psTimeArr = self.psTime;
                self.thisClickFun = null;
                arg[0].find("ul.content").unbind("mousemove").bind("mousemove",function(e){


                    var l = e.clientX;
                    var t = e.pageY;
                    var actionArea;
                    //  var psTimeBoolen = self.cpu(["returnPsTimeOverBoolen"],[t]); // 计算中间矩形区域，并且排除标识时间块的区域
                    var fixNum = self.iefix(["returnScale"],[null])||0;
                    self.inTimelineAreaBoolen = (l>ol+ow/2-10)&&(l<ol+ow/2+10)&&t>ot&&t<ot+oh;
                    if(self.inTimelineAreaBoolen){
                        /*if(arg[0].css("cursor")!="url('..'+miscpath+'img/system/small.cur')"){
                         arg[0].css("cursor","url(..'+miscpath+'img/system/small.cur),none");
                         }*/
                        self.timelineCursor.css({"top":t-ot+fixNum-10}).show();
                    }else{
                        //  arg[0].off("click",self.thisClickFun);
                        arg[0].css("cursor","default");
                        self.timelineCursor.hide();

                    }
                    self.thisClickFun = function(ee){       // click
                        if(self.inTimelineAreaBoolen){
                            var thisY = parseInt(ee.pageY-fixNum);
                            _min = self.cpu(["returnMaxOrMin"],[thisY,"min",self.hMethod]); // 距离上面的最小刻度
                            _max = self.cpu(["returnMaxOrMin"],[thisY,"max",self.hMethod]); // 距离下面的最小刻度
                            if(_min&&_max){
                                var bfb = _min.c/(_min.c+_max.c);
                                var _minTime = $("#"+_min.id).attr("time");
                                var _maxTime = $("#"+_max.id).attr("time");
                                var _getTime = self.cpu(["timeDiff"],[_minTime,_maxTime,bfb]);
                                //alert(_getTime);
                                self.addNewAction.css({
                                    top:t-ot-20,
                                    left:0
                                }).show();
                                //console.log(_getTime);
                            }else{
                                var today = self.cpu(["today"],[]);
                            }
                        }else{
                            if(!self.addNewActionOverBoolen){
                                self.addNewAction.hide();
                            }
                        }
                    };
                    arg[0].off("click",self.thisClickFun).on("click",self.thisClickFun);
                });
            },
            addNewActionHover:function(arg){
                self.addNewActionOverBoolen = false;
                arg[0].on("mouseover",function(){
                    self.addNewActionOverBoolen = true;
                });
                arg[0].on("mouseout",function(){
                    self.addNewActionOverBoolen = false;
                });
            },
            timelineBoxHover:function(arg){
                arg[0].children("ul").children().on("mouseenter",function(){
                    $(this).find(".editControl").show();
                    $(this).addClass('enterBox');
                    $(this).children("i").addClass("enterBox");
                });
                arg[0].children("ul").children().on("mouseleave",function(){
                    $(this).find(".editControl").hide();
                    $(this).removeClass('enterBox');
                    $(this).children("i").removeClass("enterBox");
                });
            },
            forward:function(arg){
                var p = arg[0].closest("li[name=timeBox]");
                var name1 = p.find(".AuthorName").children("a").text();
                var name2;
                var name3;
                var value,$content,imgurl;
                var __data=arg[1]||{};
                if(p.find(".forwardContent").find(".memo").size()==0){
                    value = p.find(".infoContent").html();
                }else{
                    value = p.find(".forwardContent").find(".memo").html();
                }
                if(p.find(".oldAuthorName").size()!=0){
                    name2 = p.find(".oldAuthorName").children("a").text().replace(":","");
                    name3 = name2;
                }else{
                    name3 = name1;
                }
                var tpl={
                    'main':'<div class="laymoveText"><div class="zf_content shareBox"><textarea maxlength="140"></textarea>\
                            <div class="replyFor" style="margin-bottom: 5px"><div class="shareTo"><label>同时评论给：</label><label class="replyCheck"><input type="checkbox" id="replyCheck"> {1}</label>\
                            </div><div class="tip countTxt"><span class="num">0</span>/140</div></div><div class="replyFor" style="margin-bottom: 10px">{2}</div><div class="content">{0}</div></div></div>',
                    'info':'<span class="avatar"><img src="{0}"></span>\
                            <div class="avatar_info"><p><strong>状态更新</strong></p><p>由<span class="name"><a href="{1}">{2}</a></span>发布</p><p>{3}</p></div>',
                    'album':'<span class="avatar"><img width="92" src="{0}"></span>\
                             <div class="avatar_info"><p><strong>来自相册：</strong>{1}</p>\
                             <p>由<span class="name"><a href="{2}"> {3}</a></span>发布</p></div>',
                    'photo':'<span class="avatar"><img width="92" src="{0}"></span>\
                             <div class="avatar_info"><p><strong>来自相册：</strong>{1}</p>\
                             <p>由<span class="name"><a href="{2}"> {3}</a></span>发布</p></div>',
                    'video':'<span class="avatar"><img width="92" src="{0}"></span>\
                             <div class="avatar_info">\
                                <p>{1}</p>\
                                <p>由<span class="name"><a href="{2}"> {3}</a></span>发布</p>\
                                <p>{4}</p></div>',
                    //'option':'<option id = "webId_{0}">{1}</option>',
                    option:'<option value="{0}">{1}</option>',
                    'select':'<lable>分享至：</lable><select id="J_forwardTo">{0}</select>',
                    'unpage':'<p style="color:#ff281c">网页信息仅限转载至网页,您还未创建网页</p>'
                }
                //var userURL = mk_url('webmain/index/main',{web_id:__data.dkcode});
                var typeFunction = function (data,type) {
                    var _h=[],_sel='',i=0;
                    if(data&&data.length>0){
                        for(;i<data.length;i++)
                            _h.push(_format(tpl.option,data[i].aid,data[i].name));
                        _sel=_format(tpl.select,_h.join(''));
                    }else{
                        _sel=tpl.unpage;
                    }
                    var userURL =__data.author;
                    switch (type) {
                        case "info":
                            $content=_format(tpl.main,_format(tpl.info,__data.avatar,userURL,__data.username,__data.content),name1,_sel);
                            break;
                        case "album":
                            imgurl=p.find("a.photoLink img").attr("src");
                            $content=_format(tpl.main,_format(tpl.album,imgurl,__data.title,userURL,name3),name1,_sel);
                            break;
                        case "photo":
                            imgurl=p.find("a.photoLink img").attr("src");
                            $content=_format(tpl.main,_format(tpl.album,imgurl,__data.title,userURL,name3),name1,_sel);
                            break;
                        case "video":
                            imgurl=p.find("div.mediaContent img").attr("src");
                            $content=_format(tpl.main,_format(tpl.video,imgurl,__data.title,userURL,name3,__data.content),name1,_sel);
                            break;
                    }
                    return $($content);
                }
                if(p.attr("type") == "forward"){
                   if(p.attr("forwardType") && p.attr("forwardType") != "undefined"){
                        if(self.vGlobal.webNameData){
                            $content =  displayThePop(self.vGlobal.webNameData,p.attr("forwardType"));
                        }else {
                            //self.model(['web_forwardRequest'],[{},function(data){
                                $content = displayThePop(__data.web_list,p.attr("forwardType"));
                            //}]);
                        }
                    }else{
                        self.plug(["popUp"],[p,'<div style="padding:10px">原始数据已被删除！无法进行操作</div>',"提示",function(){
                            $.closePopUp();
                        },'<span class="popBtns blueBtn callbackBtn">知道了</span>',400]);
                        return false;
                    }
                }else{
                    if(self.vGlobal.webNameData){
                        displayThePop(self.vGlobal.webNameData,p.attr("type"));
                    }else {
                        //self.model(['web_forwardRequest'],[{},function(data){
                            displayThePop(__data.web_list,p.attr("type"));
                        //}]);
                    }
                }
                function displayThePop(data,type){
                    self.vGlobal.webNameData = data;
                    $content = typeFunction(data,type);
                    //p.attr("type")=="forward" && $content.find("div.replyFor").append('<p><input type="checkbox" id="replyCheckOld"><label for="replyCheckOld">同时评论给原作者 '+name2+'</label></p>');
                    p.attr("type")=="forward" && $content.find("div.shareTo").append('<label class="replyCheckOld"><input type="checkbox" id="replyCheckOld"> '+name2+'</label>');
                    self.plug(["popUp"],[arg[0],$content,"分享",function(){
                        var data = {};
                        var __J_forwardTo=$content.find('#J_forwardTo');
                        if(__J_forwardTo[0]){
                            data.my_web_id=$(__J_forwardTo).val();
                        }else{
                            $content.find("div.replyFor").eq(1).fadeOut(100).fadeIn(100);
                            return;
                        }
                        data.web_id=CONFIG['web_id'];
//                        try{
//                            data.web_id = $('#J_forwardTo option:selected')[0].id.split('webId_')[1] || CONFIG['web_id'];
//                        }catch(e){
//                            var replyFor=$content.find("div.replyFor").eq(1).fadeOut(100).fadeIn(100);
//                            return;
//                        };
                        data.content = $content.find("textarea").val();
                        data.tid = p.attr("id");
                        var commentBox=arg[0].parents('div.commentBox');
                        data.action_uid=commentBox.attr('action_uid')||'';
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
                        if(data.web_id){
                            self.model(["doShare"],[data,function(result){
                                if(result.status){
                                    var num =p.find(".forward_count").html().replace(/\(|\)/g,'');
                                    num=(num>>0)+1;
                                    p.find(".forward_count").addClass('cursorPointer').text('('+num+')');
                                    if(self.action_dkcode==self.hd_UID){
                                        var $timePsBox = self.timelineTree.find("li[now]");
                                        self.view([result.data.type],[$timePsBox,result.data,"prepend"]);
                                        self.plug(['commentEasy'],[$timePsBox]);
                                        self.cpu(["lay"],[$timePsBox.children("ul.content")]);
                                        self.event(["changeSize"],[$timePsBox]);
                                        self.event(["timelineBoxHover"],[$timePsBox]);

                                        var a = $timePsBox.find("a[name]");
                                        $("html,body").animate({scrollTop:a.offset().top-165},200);
                                    }
                                }else{
                                    alert(result.info);
                                }
                                $.closePopUp();
                            }]);
                        }

                    },'<span class="popBtns blueBtn callbackBtn">分享</span><span class="popBtns closeBtn">取消</span>',475]);
                    //limitStrNum($content.find("textarea"));
                    _class.shareBoxListener($content);
                    return $content;
                }
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
            removeLoading:function(arg){
                arg[0].find(".loading").remove();
            },
            photoLink:function(arg){
                var bool;


                arg[0].find("a.photoLink").click(function(e){
                    var ele = $(this);
                    e.stopPropagation();
                    var picviewer = new CLASS_PICVIEWER();
                    picviewer.view('creatIframe',[ele.attr("url")]);
                })
            }

        };
        var fn;
        $.each(method,function(index,value){
            if(value){
                return fn =  _class[value](arg);
            }
        });

        return fn;
    },
    plug:function(method,arg){
        var self = this;
        var _class = {
            tip_up_right_black:function(arg){
                arg[0].find(".tip_up_right_black").tip({
                    direction:"up",
                    position:"right",
                    skin:"black",
                    clickHide:true,
                    key:1
                });
            },
            tip_up_left_black:function(arg){
                arg[0].find(".tip_up_left_black").tip({
                    direction:"up",
                    position:"left",
                    skin:"black",
                    clickHide:true,
                    key:1
                });
            },
            msg:function(arg){
                arg[0].find("[msg]").msg();
            },
            commentEasy:function(arg){
                arg[0].children("ul.content").find('.commentBox:not(.hasComment)').commentEasy({
                    minNum:3,
                    UID:CONFIG['u_id'],
					userName:CONFIG['u_name'],
					avatar:CONFIG['u_head'],
                    userPageUrl:$("#hd_userPageUrl").val(),
                    isShow:false,
                    isOnlyYou:false,
                    relay:true,
                    relayCallback:function(obj,_arg) {
                        var pagetype=obj.parents('.commentBox').attr('pagetype');
                        var url='main/share/share_info?'+_arg;
                        $.ajax({
                            url:mk_url(url),
                            dataType:'jsonp'
                        }).then(function(q){
                                if(q.status){
                                    self.event(["forward"], [obj, q.data]);
                                }else{
                                        alert(q.info);
                                }
                            });
                    }
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

        };
        $.each(method,function(index,value){
            if(value){
                return _class[value](arg);
            }
        });
    },
    model:function(method,arg){
        var self = this;
        var _class={
            /**
             * 方法已费掉 2012-07-26 马正洁
             * @param arg
             */
            web_forwardRequest : function(arg){
                $.djax({
                    url:mk_url("webmain/web/getWebs"),
                    dataType:"jsonp",
                    success:function(data){
                        if(data){
                            arg[1](data);
                        }
                    }
                });
            },
            timedata:function(arg){
                $.djax({
                    //url:self.webpath+"timedata.txt",
                    url:mk_url("webmain/timeline/getTimelineYears"),
                    data:{web_id:self.webId},
                    dataType:"json",
                    async:true,
                    success:function(data){
                        if(data){
                            //var data = eval("("+data+")");
                            arg[1](data.data);
                        }
                    },
                    error:function(data){

                    }
                });
            },
            changeDate:function(arg){
                $.djax({
                    url:mk_url("webmain/web/doSetCtime"),

                    dataType:"json",
                    async:true,
                    data:arg[0],
                    success:function(data){
                        if(data){
                            //var data = eval("("+data+")");

                            arg[1](data.data);
                        }
                    },
                    error:function(data){

                    }
                });
            },
            doDelTopic:function(arg){
                $.djax({
                    url:mk_url("webmain/web/doDelTopic"),
                    async:true,
                    data:arg[0],
                    dataType:"json",
                    success:function(data){
                        if(data){
                            //var data = eval("("+data+")");

                            arg[1](data);
                        }
                    }
                });

            },
            data:function(arg){
                $.djax({
                    url:mk_url("webmain/timeline/getYearHottestFeeds"),
                    async:true,
                    data:arg[0],
                    dataType:"json",
                    aborted:false,
                    success:function(data){
                        if(data){
                            //var data = eval("("+data+")");

                            arg[1](data);
                        }
                    }
                });
            },
            monthdata:function(arg){
                $.djax({
                    url:mk_url("webmain/timeline/getFragmentFeeds"),
                    async:true,
                    data:arg[0],
                    dataType:"json",
                    aborted:false,
                    success:function(data){
                        if(data){
                            //var data = eval("("+data+")");

                            arg[1](data);
                        }
                    }
                });
            },
            highlight:function(arg){

                $.djax({
                    url:mk_url("webmain/web/doUpdatehighlight"),
                    async:true,
                    dataType:"json",
                    data:arg[0],
                    success:function(data){
                        if(data){
                            //var data = eval("("+data+")");
                            arg[1](data);
                        }
                    }
                });
            },
            /**
             * 网页时间线 分享提交
             * @param arg
             */
            doShare:function(arg){
                $.ajax({
                    url:mk_url("main/share/webShare"),
                    type:'post',
                    dataType:"jsonp",
                    data:arg[0]
                }).then(function(data){
                    arg[1](data);
                });
            },
            albumPersition:function(arg){
                $.djax({
                    // url:webpath+"web/album/?c=api&m=judgePhotoAccess",
                    url:mk_url('walbum/api/judgePhotoAccess'),
                    async:true,
                    dataType:"json",
                    data:arg[0],
                    success:function(data){
                        if(data){
                            arg[1](data);
                        }
                    }
                });
            }

        };
        return _class[method](arg);
    }
};
$(document).ready(function(){
    class_timeline = new CLASS_TIMELINE();
    class_timeline.init();


});
/*******************end:时间线主程序*********************/

/******************start:timeBox事件********************/
$(document).ready(function() {

    var $timelineTree = $('#timelineTree');


    /***显示下拉菜单***/
    $timelineTree.on('click','span.conWrap',function(){
        $(this).toggleClass('clickDown');
        if(!$(this).hasClass('midLine')){
            $(this).find('>ul.editMenu').toggleClass('hide');
        }
    });

    // $(".html_date").calendar({button:false,time:false});

    /***用户权限***/
    $('#shareRights').dropdown({
        top: 22,
        position: 'right',
        permission:{
            type: 'blog'
        }
    });
    /***视频播放***/

        //显示播放flash
    $timelineTree.on('click','div.media_prev',function(){
        var _self=this;
        //创建一个视频对象
        var videoController = new VideoController();
        //获取页面上的视频id
        var videoId = $(this).next().children('div').attr('id');
        
        //获取视频其它参数，与id不在同一个div上
        //var videoSrc = $(this).next().attr('videosrc');
        var fid = $(this).closest("[name='timeBox']").attr("fid");

        var videoWidth,videoHeight;
        if($(this).closest("li.twoColumn").size()!=0){
            videoWidth = 838; //parseInt(videoDiv.attr('videowidth'));
            videoHeight = 600;
        }else{
            videoWidth = 401; //parseInt(videoDiv.attr('videowidth'));
            videoHeight = 300; //parseInt(videoDiv.attr('videoheight'));    //播放控制高度
        }
        //显示播放界面
        videoController.insertVideoToDom(videoId, fid, videoWidth, videoHeight,function(){
            $(_self).addClass('hide').siblings().removeClass('hide');
        });
        //收起触发事件
        var $info_media_disp = $(this).next();
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
                this.currentVideoId = $("#"+_flashWrapId).closest("[type='video']").attr("fid")||_flashWrapId.toString().substring(0,10);
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
                    'bgcolor', '#000',
                    'name', 'player',
                    'menu', 'true',
                    'allowFullScreen', 'false',
                    'allowScriptAccess','always',
                    'movie', CONFIG.misc_path+'flash/video/player.swf?vid='+_fid+'&mod=2&uid='+CONFIG.u_id,
                    //'movie',  miscpath+'flash/video/player',
                    'flashvars','autoplay=true',
                    'allowFullScreen','true',
                    'salign', '',
                    'contentId',document.getElementById(_flashWrapId)
                );
                if(_callfunc){
                    _callfunc();
                }
            }
        };
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


});
/*******************end:timeBox事件*********************/
