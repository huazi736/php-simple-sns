(function ($) {
    /*
     * Created on 2012-07-06.
     * @author: wangyuefei
     * @desc: 问答提问插件
     * @example: $('dom').ask({
                     clickCallback:function(data){
                        //do something...
                     }
                 });
     * @param:参数 clickCallback:回调函数，点击提问之后触发的事件；
     */
    function input_ask(element, options) {
        this.opts = options;
        this.$e = element;
        this.init();
    }

    input_ask.prototype = {
        init:function () {
            var self = this;
            self.view();
            this.setting_voting = $('#setting_voting');
            this.votingBox = $('#votingBox');
            this.setting_moreOptions = $("#setting_moreOptions");
            this.setting_more = $("#setting_more");
            this.addNewAsk = $('#addNewAsk');
            this.add_asks = $('#add_asks');
            this.shareRights = $('#shareRights');
            this.questionInput = $('#questionInput');
            self.plug(['msg'], [this.addNewAsk]);
            if (this.opts.type === 1) {
                self.plug(['shareDestinationObjects']);
                self.plug(['tip_up_right_black'], [this.addNewAsk]);
            }
            self.plug(['textareaTip'], [this.add_asks, '', 'disable', 260, true, '#questionInputBox'])
            self.event('init');
        },
        reset:function () {
            var self = this;
            self.questionInput.val('').blur();
            !self.add_asks.hasClass('disable') && self.add_asks.addClass('disable');
            self.addNewAsk.find('div.tip').hide();
            self.addNewAsk.find("#setting_voting").show();
            self.addNewAsk.find("#votingBox").hide();
            var voting_options = self.votingBox.find('div.voting_option').children();
            voting_options.slice(0, 3).find("input").val("").blur();
            voting_options.slice(3, voting_options.size()).remove();
            self.addNewAsk.find("#setting_moreOptions").hide();
            self.addNewAsk.find("#setting_moreOptions").find("input[value=0]").attr("checked", false).attr("disabled", false);
            self.addNewAsk.find("#setting_moreOptions").find("input[value=1]").attr("checked", true).attr('disabled',false);
        },
        view:function () {
            var self = this;
            var str = '<div id="addNewAsk" class="webComoserQuestion"><div id="questionInputBox"><textarea class="questionInputBox" msg="问个问题..." name="question" id="questionInput" title="问个问题" placeholder="问个问题..."></textarea></div><div id="votingBox" style="display:none"><div class="title"><span>投票选项</span></div><!--start 添加投票选项 --><div class="voting_option"><div class="new_item"><input type="text" maxlength="80"  msg="添加选项" /></div><div class="new_item"><input type="text" maxlength="80"  msg="添加选项" /></div><div class="new_item"><input type="text" maxlength="80"  msg="添加选项" /></div></div><!--end 添加投票选项 --></div><div class="operation"><div id="setting_moreOptions"  style="display: none;"><label class="bold"><input type="checkbox" name="moreRadio" value="0" />单选</label><label class="bold" ><input type="checkbox" checked="checked" name="moreRadio" value="1" />允许添加选项</label></div><a  href="javascript:void(0)" id="setting_voting">添加投票选项</a><a id="add_asks" class="uiButton uiButtonConfirm disable" href="javascript:void(0)">发表</a>';
            if (self.opts.type === 1) {
                str += '<div id="shareRights" tip="公开" oid="123" s="1" uid="" class="dropWrap tip_up_right_black"><input type="hidden" name="permission" value="1" /></div>';
            }
            str += '</div></div>';
            self.$e.html(str);
        },
        plug:function (method, arg) {
            var self = this;
            var _class = {
                shareDestinationObjects:function (arg) {
                    $('#shareRights').dropdown({
                        top:22,
                        position:'right',
                        permission:{
                            type:'ask'
                        },
                        friend_url:{
                            getUrl:mk_url('ask/ask/listFriend'),
                            postUrl:mk_url('ask/ask/askFriend'),
                            searchUrl:mk_url('ask/ask/listFriend'),
                            hasPage:true
                        }
                    });
                },
                msg:function (arg) {
                    arg[0].find("[msg]").msg();
                },
                tip_up_right_black:function (arg) {
                    arg[0].find(".tip_up_right_black").tip({
                        direction:"up",
                        position:"right",
                        skin:"black"
                    });
                },
                popUpVotersDetail:function (arg) {
                    arg[0].subPopUp({
                        width:arg[3] || 500,
                        title:arg[2],
                        content:arg[1],
                        mask:true,
                        buttons:'<span class="popBtns closeBtn">关闭</span>',
                        maskMode:true,
                        callback:function () {
                            $.closeSubPop();
                        },
                        closeCallback:function () {
                            if (arg[4] && typeof(arg[4]) == "function") {
                                arg[4]();
                            }
                            //self.ask_operation.find("span.on").click();
                        }
                    });
                },
                textareaTip:function () {
                    new Textarea.msgTip(arg[5], {
                        maxlength:arg[3],
                        notMedia:arg[4],
                        iswordwrap:true,
                        button:{
                            id:arg[0],
                            activeClass:arg[1],
                            disableClass:arg[2],
                            callback:function(reset){
                                self.event('postclick');
                            }
                        }
                    })
                }
            };
            $.each(method, function (index, value) {
                if (value) {
                    return _class[value](arg);
                }
            })
        },
        event:function (method,arg) {
            var self = this;
            var _class={
                init:function(){
                    self.setting_voting.click(function () {
                        $(this).hide();
                        self.votingBox.show();
                        self.votingBox.find("input:first").focus();
                        self.setting_moreOptions.show();
                    });
                    self.setting_moreOptions.find('input:first').click(function () {
                        if ($(this).attr('checked')) {
                            self.setting_moreOptions.find('input:last').attr('disabled', true).attr('checked', false);
                        } else {
                            self.setting_moreOptions.find('input:last').attr('disabled', false)
                        }
                    });
                    self.votingBox.find("input:last").live("focus", function () {
                        if (self.votingBox.find("div.voting_option").children().size() > 9) {
                            return false;
                        }
                        var newVoting = $('<div class="new_item"><input type="text" maxlength="80"  msg="添加选项" ></div>')
                        self.votingBox.find("div.voting_option").append(newVoting);

                        self.plug(["msg"], [newVoting]);
                    });
                },
                postclick:function(){
                    var data = {};
                    var bool = true;
                    var question = $.trim(self.questionInput.val());
                    if (!question || question.length > 260) {
                        !question && self.questionInput.val("");
                        return false;
                    }
                    data.title = question;
                    data.options = [];
                    data.type = self.opts.type;
                    data.permission = self.addNewAsk.find('input[name=permission]').val();
                    $.each(self.votingBox.find("div.new_item"), function () {
                        var v = $.trim($(this).find("input").val());
                        var input = $(this).find("input");
                        if (v) {
                            if ($.inArray(v, data.options) == -1) {
                                data.options.push(v);
                            } else {
                                self.plug(["popUpVotersDetail"], [$(this), "<div style='padding:10px'>存在重复的投票选项</div>", "提示", 350, function () {
                                    input.focus();
                                }]);

                                bool = false;
                                return false;
                            }

                        }
                    });

                    if (!bool) {
                        return false;
                    }
                    var checkedradio = self.setting_moreOptions.find('input[name=moreRadio]:checked').val();
                    data.allow = checkedradio == "1" ? 1 : 0;
                    data.multi = checkedradio || 1;

                    if (data.allow == 0 && data.options.length < 2) {
                        if ($.trim(self.addNewAsk.find("div.new_item:first").find("input").val()) == "") {
                            self.plug(["popUpVotersDetail"], [$(this), "<div style='padding:10px'>不允许添加选项的情况下，请至少填写两项</div>", "提示", 350, function () {
                                self.addNewAsk.find("div.new_item:first").find("input").focus();
                            }]);
                        } else {
                            self.plug(["popUpVotersDetail"], [$(this), "<div style='padding:10px'>不允许添加选项的情况下，请至少填写两项</div>", "提示", 350, function () {
                                self.addNewAsk.find("div.new_item:eq(1)").find("input").focus();
                            }]);
                        }
                        return false;
                    }
                    if (typeof self.opts.clickCallback === 'function') {
                        self.opts.clickCallback(data,self);
                    }
                }
            };
            return _class[method](arg);
        },
        model:function (method, arg) {
            var self = this;
            var _class = {
                add_asks:function (arg) {
                    $.djax({
                        url:self.opts.url||mk_url('ask/ask/addAsk'),
                        dataType:self.opts.dataType||'json',
                        type:self.opts.ajaxType||'POST',
                        data:arg[0],
                        success:arg[1]
                    });
                }
            };
            $.each(method, function (index, value) {
                if (value) {
                    return _class[value](arg);
                }
            })
        }
    };
    /*
     * Created on 2012-07-06.
     * @author: wangyuefei
     * @desc: 问答单条信息渲染插件
     * @example: $('dom').showAsk(json);
     * @param:参数 json:单条问答信息
     */
    function getAsk(element, options) {
        this.opts = options;
        this.$e = $(element);
        this.init();
    }

    getAsk.prototype = {
        init:function () {
            var self = this;
            self.askDetail = $('<div id="askDetail"><div id="askDetail_item"></div><div id="askDetail_note"></div></div>');           //问答详细弹出层
            self.hd_avatar = CONFIG['u_head'];
            self.uid = CONFIG['u_id'];
            self.dkcode = CONFIG['dkcode'];
            self.friends_detail = $('<div id="friends_detail"></div>');             // 好友列表窗口
            self.from_ask_notices_id = $('#from_ask_notices').val();    //问答详细页ID
            var data={};
            if(self.opts.ispopBox){         //直接调用弹出层问答
                self.plug(['popUpAskDetail'],[self.$e,self.askDetail]);
                data.poll_id=self.opts.poll_id;
                self.model("one_ask", [data, function (result) {
                    if (result.status == "0") {
                        self.askDetail.html("<div class='noInfo' style='margin-top:160px;'>" + result.info + "</div>");
                        return false;
                    }
                    self.view(["askDetail"], [self.askDetail, result]);
                    self.commentPage = 1;
                    self.model("list_comments", [
                        {frmid:data.poll_id, page:self.commentPage},
                        function (data) {
                            self.view(["note"], [self.askDetail, data, self.commentPage]);
                        }
                    ]);
                }]);
                return false;
            }
            if(self.from_ask_notices_id){
                self.askDetail=$('#askDetail');
                data.poll_id=self.from_ask_notices_id;
                self.model("one_ask", [data, function (result) {
                    if (result.status == "0") {
                        self.askDetail.html("<li class='noInfo' style='margin-top:160px;'>" + result.info + "</li>");
                        self.askDetail.css({width:"auto"});
                        return false;
                    }
                    self.view(["askDetail"], [self.askDetail, result]);
                    self.commentPage = 1;
                    self.model("list_comments", [
                        {frmid:data.poll_id, page:self.commentPage},
                        function (data) {
                            self.view(["note"], [self.askDetail, data, self.commentPage]);
                        }
                    ]);
                }]);
                return false;
            }
            if(self.opts.atype){
                switch (self.opts.atype) {
                    case 1:
                        self.view(['asklistShow_one'], [this.opts,self.$e]);
                        break;
                    case 2:
                        self.view(['asklistShow_two'], [this.opts]);
                        break;
                    case 3:
                        self.view(['asklistShow_three'], [this.opts,self.$e]);
                        break;
                    case 4:
                        self.view(['asklistShow_four'], [this.opts]);
                        break;
                    default:
                        break;
                }
            }else{
                this.$e.html('<div class="noInfo">问答已被删除或者您访问的问答不存在</div>');
            }
        },
        view:function (method, arg) {
            var self = this;
            var _class = {
                //列表展示（有选项信息）在时间线上
                asklistShow_one:function (arg) {
                    var str = "";
                    if (arg[0]) {
                        var id = arg[0].questionid, name = arg[0].username, question = arg[0].question, answer = arg[0].answer, img = arg[0].img, muti = arg[0].multi, focusTotal = arg[0].votes, allow = arg[0].allow, link_url = arg[0].link_url,optionsNum=arg[0].optionsNum,is_end= arg[0].is_end;
                        var qArr = arg[0].options;
                        str += '<div poll_id="' + id + '" class="questions clearfix" muti="' + muti + '"><form><div class="item_box clearfix"><div class="content"><div class="info"> <a class="info_title" href="javascript:;" name="question">' + replaceBrackets(question) + '</a>';
                        str += ' </div>';
                        if (!answer) {
                            $.each(qArr, function (a, b) {
                                if (a >= 3 && qArr.length > 3) {
                                    return false;
                                }
                                var qid = b.id, votes = b.votes, value = replaceBrackets(b.message) || "undefined", voters = b.voters || null, selected = b.selected;
                                var checked = false;
                                str += '<ul  class="block clearfix" qid="' + qid + '" pollid="' + id + '">' +
                                    '<li class="poll_btn">'
                                if (muti == 0) {
                                    if (selected) {
                                        str += '<input type="radio" name="option_id' + id + '" checked="checked">';
                                    } else {
                                        str += '<input type="radio" name="option_id' + id + '">';
                                    }
                                } else {
                                    if (selected) {
                                        str += '<input type="checkbox" name="option_id' + id + '" checked="checked">';
                                    } else {
                                        str += '<input type="checkbox" name="option_id' + id + '">';
                                    }
                                }
                                str += '</li>';
                                str += '<li class="poll_result_bar tip_up_right_black" tip="' + votes + '票">';
                                var votes_ = (votes / focusTotal) * 100;
                                str += '<div style="width:' + votes_ + '%" class="shaded"></div>'
                                str += '<div class="label">' + value + '</div>';
                                str += '</li><li class="target_event"></li>';
                                str += '<li class="poll_result_facepile">';
                                str += '<ul class="pile_list clearfix">';
                                if (voters) {
                                    var num = 0;
                                    if (voters.friend) {
                                        $.each(voters.friend, function (i, c) {
                                            if (i > 2) {
                                                return false;
                                            }
                                            var f_name = c.username, f_face = c.img, dkcode = c.dkcode;
                                            str += '<li dkcode="' + dkcode + '" class="friends_face"><a class="uiface_pile_face" href="' + mk_url('main/index/main',{dkcode:dkcode}) +'"> <img height="24" width="24" src="' + f_face + '" class="tip_up_black" tip="' + f_name + '"> </a> </li>';
                                        });
                                        if (voters.friend.length > 3) {
                                            num = voters.friend.length - 3;
                                        }
                                    }

                                    //当存在其他人的时候
                                    if (voters.otherPerson) {
                                        num = num + parseInt(voters.otherPerson);
                                    }
                                    if (num > 0) {
                                        str += '<li class="otherPerson"><a class="uiface_pile_face more_faces uitool_tip tip_right_black" href="javascript:void(0)" tip="+' + num + '"> </a> </li>';
                                    }
                                }

                                str += '</ul></li></ul>';

                            });

                            if (qArr.length <= 3 && is_end) {
                                if (allow == 1) {
                                    str += '<div class="add_new_item">' +
                                        '<table style="width:222px;">' +
                                        '<tr>' +
                                        '<td><div class="new_item">' +
                                        '<input type="text"  msg="添加一个答案..." maxlength="80" />' +
                                        '</div>' +
                                        '</td>' +
                                        '<td style="display:none" width="40"><div class="uiButton" id="add_new_item">添加</div></td>' +
                                        '</tr>' +
                                        '</table>' +
                                        '</div>';
                                }
                            }
                            if (!is_end || qArr.length > 3) {
                                str += '<div class="other_item clearfix"> <span class="other_item_span">查看其它' + (optionsNum - 3) + '个选项</span>  </div>';
                            }
                        }
                        str += '</div></div></form></div>';
                    }
                    arg[1].append(str);
                    var thisdom=self.$e.find('div[poll_id=' + arg[0].questionid + ']');
                    self.iefix(["height"], [thisdom]);
                    self.plug(["tip_up_right_black", "tip_up_black", "tip_right_black", "tip_up_djax", "msg"], [thisdom]);
                    self.event('askInTimeline_one', thisdom);
                    self.event('otherPerson', thisdom);
                    self.event('item_box', thisdom);
                    self.event('new_item', thisdom);
                },
                //列表展示（无选项信息）在时间线上
                asklistShow_two:function (arg) {
                    var str = "",question=arg[0].question,poll_id=arg[0].poll_id,type=arg[0].type,addtime=arg[0].addtime,muti=arg[0].multi,link_url=arg[0].link_url,options=arg[0].options;
                    var questionslist=$('<div class="questions" muti="'+muti+'" poll_id="'+poll_id+'"></div>');
                    str += '<div class="question_time">'+addtime+'</div>';
                    switch(type){
                        case "1":
                        str+='<div class="question_info">提出了问题 " <strong><a name="questionshow" href="'+link_url+'" title="'+question+'">'+question+'</a></strong> "</div>' +
                        '</div>';
                            break;
                        case "2":
                            str+='<div class="question_info">回答了 " <a name="questionshow" href="'+link_url+'" title="'+question+'">'+question+'</a> " 答案是：" <a name="questionshow" href="'+link_url+'" title="'+options[0].message+'">'+options[0].message+'</a> " ' ;
                            if(options.length>1){
                                str+='和 " <a href="'+from.link_url+'" title="其他答案">其他'+options.length-1+'个答案</a> "';
                            }
                            str+='</div>';
                            break;
                        case "3":
                            str+='<div class="question_info">提出并回答了 " <a name="questionshow" href="'+link_url+'" title="'+question+'">'+question+'</a> " 答案是：" <a name="questionshow" href="'+link_url+'" title="连体裤怎么尿尿">连体裤怎么尿尿</a> " ';
                            if(options.length>1){
                                str+='和 " <a href="'+from.link_url+'" title="其他答案">其他'+options.length-1+'个答案</a> "';
                            }
                            str+='</div>';
                            break;
                        default:
                            break;
                    }
                    questionslist.append(str)
                    self.$e.append(questionslist);
                    self.event('askInTimeline_two',questionslist);
                },
                //列表展示(有选项信息) 在问答列表
                asklistShow_three:function(arg){
                    var str = "";
                    if (arg[0]) {
                        var id = arg[0].questionid, name = arg[0].username, question = arg[0].question, answer = arg[0].answer, img = arg[0].img, muti = arg[0].multi, focusTotal = arg[0].votes, allow = arg[0].allow, link_url = arg[0].link_url,optionsNum=arg[0].optionsNum,time=arg[0].addtime,followed=arg[0].followed, is_end= arg[0].is_end,askfriend=arg[0].askfriend,commentsNum=arg[0].commentsNum,access=arg[0].access,i_voting=arg[0].i_voting;
                        var qArr = arg[0].options,touch='',classname='';
                        switch (access) {
                            case -1:
                                touch = "自定义";
                                classname = 'c';
                                break;
                            case 1:
                                touch = "公开";
                                classname = 'o';
                                break;
                            case 8:
                                touch = "仅自己";
                                classname = 's';
                                break;
                            case 4:
                                touch = "好友";
                                classname = 'fr';
                                break;
                            case 3:
                                touch = "粉丝";
                                classname = 'fan';
                                break;
                            default:
                                touch = "未知";
                                classname = 'o';
                                break;
                        }
                        str += '<div poll_id="' + id + '" class="questions clearfix" muti="' + muti + '" myvote="'+i_voting+'"><form>' +
                            '<a class="user_face" href="'+link_url+'/"> <img height="50" width="50" src="'+img+'"></a><div class="item_box clearfix"><div class="content"><div class="userinfo clearfix"><strong class="fl"><a href="'+link_url+'" title="'+name+'">'+name+'</a></strong><span class="time">'+time+'</span><i class="icons7 uiIcon fl ' + classname + '"></i><span class="touch">'+touch+'</span></div><div class="info clearfix"> <a class="info_title" href="javascript:;" name="question">' + question + '</a>';
                        str += ' </div>';
                        if (!answer) {

                            $.each(qArr, function (a, b) {
                                if (a >= 3 && qArr.length > 3) {
                                    return false;
                                }
                                var qid = b.id, pollid = b.poll_id, votes = b.votes, value = b.message || "undefined", voters = b.voters || null, selected = b.selected, otherPerson = b.otherPerson || null;
                                var checked = false;

                                str += '<ul  class="block clearfix" qid="' + qid + '" pollid="' + pollid + '">' +
                                    '<li class="poll_btn">'
                                if (muti == 0) {
                                    if (selected) {
                                        str += '<input type="radio" name="option_id' + id + '" checked="checked">';
                                    } else {
                                        str += '<input type="radio" name="option_id' + id + '">';
                                    }
                                } else {
                                    if (selected) {
                                        str += '<input type="checkbox" name="option_id' + id + '" checked="checked">';
                                    } else {
                                        str += '<input type="checkbox" name="option_id' + id + '">';
                                    }
                                }
                                str += '</li>';
                                str += '<li class="poll_result_bar tip_up_right_black" tip="' + votes + '票">';
                                var votes_ = (votes / focusTotal) * 100;
                                str += '<div style="width:' + votes_ + '%" class="shaded"></div>'
                                str += '<div class="label">' + value + '</div>';
                                str += '</li><li class="target_event"></li>';
                                str += '<li class="poll_result_facepile">';
                                str += '<ul class="pile_list clearfix">';
                                if (voters) {
                                    var num = 0;
                                    if (voters.friend) {
                                        $.each(voters.friend, function (i, c) {
                                            if (i > 2) {
                                                return false;
                                            }
                                            var f_name = c.username, f_face = c.img, dkcode = c.dkcode;
                                            str += '<li dkcode="' + dkcode + '" class="friends_face"><a class="uiface_pile_face" href="' + mk_url('main/index/main',{dkcode:dkcode}) + '"> <img height="24" width="24" src="' + f_face + '" class="tip_up_black" tip="' + f_name + '"> </a> </li>';
                                        });
                                        if (voters.friend.length > 3) {
                                            num = voters.friend.length - 3;
                                        }
                                    }

                                    //当存在其他人的时候
                                    if (voters.otherPerson) {
                                        num = num + parseInt(voters.otherPerson);
                                    }
                                    if (num > 0) {
                                        str += '<li class="otherPerson"><a class="uiface_pile_face more_faces uitool_tip tip_right_black" href="javascript:void(0)" tip="+' + num + '"> </a> </li>';
                                    }
                                }

                                str += '</ul></li></ul>';

                            });
                            if (qArr.length <= 3 && is_end) {
                                if (allow == 1) {
                                    str += '<div class="add_new_item">' +
                                        '<table style="width:222px;">' +
                                        '<tr>' +
                                        '<td><div class="new_item">' +
                                        '<input type="text"  msg="添加一个答案..." maxlength="80" />' +
                                        '</div>' +
                                        '</td>' +
                                        '<td style="display:none" width="40"><div class="uiButton" id="add_new_item">添加</div></td>' +
                                        '</tr>' +
                                        '</table>' +
                                        '</div>';
                                }
                            }
                            if (!is_end || qArr.length > 3) {
                                str += '<div class="other_item clearfix"> <span class="other_item_span">查看其它' + (optionsNum - 3) + '个选项</span>  </div>';
                            }
                        }
                        str+='<div class="item_footer clearfix">';
                        if(followed){
                            str+='<span class="ding_focus"  style="display:none">关注</span><span class="cancel_focus">取消关注</span>';
                        }else{
                            str+='<span class="ding_focus">关注</span><span class="cancel_focus" style="display:none">取消关注</span>';
                        }
                        if(askfriend){
                            str+=' · <span class="choice_friends">提问</span>';
                        }
                        if(!answer){
                            str+=' · <a class="dialog_pipe" name="question"  href="javascript:void(0)"> <i class="icon_suport"></i> <span class="votersNum" defaultNum="'+focusTotal+'">'+focusTotal+'</span> <i class="icon_discuz"></i> <span class="commentsNum">'+commentsNum+'</span> </a>';
                        }else{
                            str+='<a class="dialog_pipe hide"></a>'
                        }
                        str += '</div></div></div></form></div>';
                    }
                    switch(arg[2]){
                        case 'after':
                            arg[1].after(str);
                            break;
                        case 'prepend':
                            arg[1].prepend(str);
                            break;
                        default:
                            arg[1].append(str);
                            break
                    }
                    var thislist=self.$e.find('div[poll_id=' + arg[0].questionid + ']');
                    self.iefix(["height"], [thislist]);
                    self.plug(["tip_up_right_black", "tip_up_black", "tip_right_black", "tip_up_djax", "msg"], [thislist]);
                    self.event('askInTimeline_one', thislist);
                    self.event('otherPerson', thislist);
                    self.event('item_box', thislist);
                    self.event('new_item', thislist);
                },
                //列表展示(无选项信息) 在问答列表
                asklistShow_four:function(arg){
                    var str = "",question=arg[0].question,poll_id=arg[0].poll_id,type=arg[0].type,addtime=arg[0].addtime,muti=arg[0].multi,img=arg[0].img,name=arg[0].username,link_url=arg[0].link_url,options=arg[0].options;
                    var questionslist=$('<div class="questions" muti="'+muti+'" poll_id="'+poll_id+'"></div>');
                    str += '<a class="user_face" href="'+link_url+'/"> <img height="50" width="50" src="'+img+'"></a>';
                    str += '<div class="questions_content"><div class="question_time">'+addtime+'</div><div class="question_info"><strong><a href="'+link_url+'" title="'+name+'">'+name+'</a></strong> ';
                    switch(type){
                        case "1":
                            str+='提出了问题 " <strong><a class="question_title" name="questionshow" href="javascript:;">'+question+'</a></strong> "</div>';
                            break;
                        case "2":
                            str+='回答了 " <a class="question_title" name="questionshow" href="javascript:;">'+question+'</a> " </div><div class="questions_answer">' ;
                            if(options.length>0){
                                str += '答案是：" <a name="questionshow" href="javascript:;">'+options[0].message+'</a> "';
                            }
                            if(options.length>1){
                                str+='和 " <a name="questionshow" href="###" >其他'+(options.length-1)+'个答案</a> "';
                            }
                            str+='</div>';
                            break;
                        case "3":
                            str+='提出并回答了 " <a class="question_title" name="questionshow" href="javascript:;">'+question+'</a> " </div><div class="questions_answer">答案是：" <a name="questionshow" href="javascript:;">'+options[0].message+'</a> "';
                            if(options.length>1){
                                str+='和 " <a name="questionshow" href="javascript:;">其他'+options.length-1+'个答案</a> "';
                            }
                            str+='</div>';
                            break;
                        default:
                            break;
                    }
                    str +='</div>';
                    questionslist.append(str);
                    self.$e.append(questionslist);
                    self.event('askInTimeline_two',questionslist);
                },
                //详情展示（弹出层）
                askDetail:function (arg) {
                    var str = "";

                    if (arg[1].status == 0) {
                        arg[0].children().html(arg[1].info);
                        return false;
                    }
                    self.commentPage = 1;
                    self.optionsPage = 1;
                    var a = arg[1].data;
                    var id = a.questionid, name = a.username, question = a.question, time = a.addtime, muti = a.multi, focusTotal = a.votes || 0, oredit = a.oredit, followed = a.followed, allow = a.allow, access = a.access, askfriend = a.askfriend || null, dkcode = a.dkcode, img = a.img, isend= a.is_end, optionsNum = a.optionsNum;
                    var qArr = a.options || [],askDetail_title={};
                    switch (access) {
                        case -1:
                            askDetail_title.classN = 'c';
                            askDetail_title.tiptext = "自定义";
                            break;
                        case 1:
                            askDetail_title.tiptext = "公开";
                            askDetail_title.classN = "o";
                            break;
                        case 8:
                            askDetail_title.tiptext = "仅自己";
                            askDetail_title.classN = "s";
                            break;
                        case 4:
                            askDetail_title.tiptext = "好友";
                            askDetail_title.classN = "fr";
                            break;
                        case 3:
                            askDetail_title.tiptext = "粉丝";
                            askDetail_title.classN = "fan";
                            break;
                        default:
                            askDetail_title.classN = 'c';
                            askDetail_title.tiptext = access;
                            break;
                    }
                    $('#popUp').find('.popTitle').html('<strong class="pop_title_name">' + name + '</strong><small class="pop_title_time">' + time + '</small><u class="' + askDetail_title.classN + ' tip_up_black" tip="' + askDetail_title.tiptext + '"></u>')
                    str += '<div class="askDetail_title">' + question + '</div>';
                    str += '<form id="askDetailForm" name="askDetailForm"><div class="item_box clearfix"></div></form>';
                    arg[0].attr("frmid", id);
                    arg[0].attr("muti", muti);
                    arg[0].attr("allow",allow);
                    str += '<div class="question_box clearfix"><ul>';
                    if (followed) {
                        str += '<li><div class="ding_focus friendBtns" style="display:none;"><a class="btn"><i class="followed"></i><span>关注</span></a></div><div class="cancel_focus friendBtns" ><a class="btn"><i class="followed"></i><span>取消关注</span></a></div></li>';
                    } else {
                        str += '<li><div class="ding_focus friendBtns"><a class="btn"><i class="followed"></i><span>关注</span></a></div><div class="cancel_focus friendBtns" style="display:none;"><a class="btn"><i class="followed"></i><span>取消关注</span></a></div></li>';
                    }
                    if (askfriend) {
                        str += '<li><div class="ask_friends friendBtns"><a class="btn"><i class="followed"></i><span>问朋友</span></a></div></li>';
                    }
                    str += '<li><div class="cancelVote friendBtns"><a class="btn"><i class="followed"></i><span>取消投票</span></a></div></li>';
                    str += '<li><div class="question_user_info">';
                    if (oredit == 1 && qArr.length > 0) {
                        str += '<span> · <a class="editAsk">编辑选项</a></span> · <a class="deleteAsk">删除</a>';
                    }
                    if (oredit == 1 && qArr.length == 0) {
                        str += ' · <a class="deleteAsk">删除</a>';
                    }
                    str += '</div></li>';
                    str += '</ul></div><div class="note"><h3><strong style="float:left">帖子</strong>';
                    str += '</h3><div class="content clearfix"><div class="reply_user"><a href="' +mk_url('main/index/main',{dkcode:self.dkcode})+ '"><img width="50" height="50" border="0" src="' + self.hd_avatar + '" /></a></div><div class="reply_input"><input type="text" style=""  msg="写点什么吧..." maxlength="260" /><div class="reply_btn"><span>发表</span></div></div></div>';
                    if (arg[0].has("#askDetail_item")) {
                        arg[0].find("#askDetail_item").html(str);
                    } else {
                        arg[0].append('<div id="askDetail_item">' + str + '</div>');
                    }
                    self.plug(["tip_up_black"], [self.askDetail.parent().prev()]);
                    self.view(['getOptions'],[$('#askDetailForm').find('.item_box'),qArr,isend,focusTotal,optionsNum]);
                    // self.event("comment_list",arg[0]);
                    // self.event("focus_pearson",arg[0]);
                },
                //答案列表
                getOptions:function(arg){
                    var str='',options=arg[1],is_end=arg[2],type=self.askDetail.attr('muti'),id=self.askDetail.attr('frmid'),focusTotal=arg[3],optionsNum=arg[4],allow=self.askDetail.attr('allow');
                    self.optionsPage++;
                    $.each(options, function (i, b) {
                        if (i >= 10 && options.length > 10) {
                            return false;
                        }
                        var qid = b.id, pollid = b.poll_id, votes = b.votes, value = b.message || "undefined", voters = b.voters || null, selected = b.selected;
                        var checked = false;


                        str += '<ul  class="block clearfix" qid="' + qid + '" pollid="' + pollid + '">' +
                            '<li class="poll_btn">';
                        if (type == 0) {
                            if (selected) {
                                str += '<input type="radio" name="option_id' + id + '" checked="checked">';
                            } else {
                                str += '<input type="radio" name="option_id' + id + '">';
                            }
                        } else {
                            if (selected) {
                                str += '<input type="checkbox" name="option_id' + id + '" checked="checked">';
                            } else {
                                str += '<input type="checkbox" name="option_id' + id + '">';
                            }
                        }
                        str += '</li>';
                        str += '<li class="poll_result_bar tip_up_right_black" tip="' + votes + '票">';
                        var votes_ = (votes / focusTotal) * 100;
                        str += '<div style="width:' + votes_ + '%" class="shaded"></div>'
                        str += '<div class="label">' + value + '</div>';
                        str += '</li><li class="target_event"></li>';
                        str += '<li class="poll_result_facepile">';
                        str += '<ul class="pile_list clearfix">';
                        if (voters) {
                            var num = 0;
                            if (voters.friend) {
                                $.each(voters.friend, function (i, c) {
                                    if (i > 2) {
                                        return false;
                                    }
                                    var f_name = c.username, f_face = c.img, dkcode = c.dkcode;
                                    str += '<li dkcode="' + dkcode + '" class="friends_face"><a class="uiface_pile_face" href="' + mk_url('main/index/main',{dkcode:dkcode}) + '"> <img height="24" width="24" src="' + f_face + '" class="tip_up_black" tip="' + f_name + '"> </a> </li>';
                                });
                            }

                            //当存在其他人的时候
                            if (voters.otherPerson) {
                                num = num + parseInt(voters.otherPerson);
                            }
                            if (num > 0) {
                                str += '<li class="otherPerson"><a class="uiface_pile_face more_faces uitool_tip tip_right_black" id="selectedList" href="javascript:void(0)" tip="+' + num + '"> </a> </li>';
                            }
                        }


                        str += '</ul></li></ul>';

                    });
                    if (allow == 1 && is_end && optionsNum<100) {
                        str += '<div class="add_new_item">' +
                            '<table width="230">' +
                            '<tr>' +
                            '<td><div class="new_item">' +
                            '<input type="text"  msg="添加一个答案..." maxlength="80" />' +
                            '</div>' +
                            '</td>' +
                            '<td style="display:none" width="40"><div class="uiButton" id="add_new_item">添加</div></td>' +
                            '</tr>' +
                            '</table>' +
                            '</div>';
                    }
                    if(!is_end){
                        var showNum = $('#askDetailForm').find('ul.block').length + options.length;
                        str += '<div class="other_item clearfix"> <span class="other_item_span">查看其它'+(optionsNum-showNum)+'选项</span>  </div>';
                    }
                    arg[0].append(str);
                    self.plug(["tip_up_right_black", "tip_up_black", "tip_right_black", "tip_up_djax", "msg"], [self.askDetail]);
                    self.event("askDetail_item");
                    self.event("otherPerson", arg[0]);
                    self.iefix(["height"], [arg[0]]);
                },
                //评论列表
                note:function (arg) {
                    var str = "";
                    if (arg[1].status == "1") {
                        $.each(arg[1].data, function (i, a) {
                            var id = a.id, name = a.username, img = a.img, msg = a.message, time = a.dateline, scomments = a.scomments, ordel = a.ordel || 0, delete_comment_str = "", liked_a = a.liked, dkou = a.dkou, uid = a.uid,options= a.options;
                            var textArr = [],text = "";
                            if (options.length>0) {
                                $.each(options, function (i, v) {
                                    textArr.push('"<span class="question">' + replaceBrackets(v.message) + '</span>"');
                                })
                                if (textArr.length > 0) {
                                    text = "选择了 ";
                                    text += textArr[0];
                                }
                                if(textArr.length > 1){
                                    text += ' 和 <span class="question">其他'+(textArr.length-1)+'个答案</span>';
                                }
                            }
                            if (ordel == 1) {
                                delete_comment_str = '<span class="ui_closeBtn_box" style="display:none"><i class="ui_closeBtn png"></i></span>';
                            }
                            str += '<div class="comment clearfix" frmid="' + id + '">' + delete_comment_str;
                            str += '<div class="reply_user"><a href="' + mk_url('main/index/main',{dkcode:dkou}) + '"><img width="50" border="0" height="50" src="' + img + '"></a></div><div class="reply_content"><span class="name"><a href="' + mk_url('main/index/main',{dkcode:dkou}) + '">' + name + '</a></span><span class="text">' + text + '</span><span class="content">' + replaceBrackets(msg) + '</span>';
                            if (arg[2] && arg[2] == "otherComments") {

                            } else {
                                str += '<div time="' + time + '" class="operation" commentObjId="' + id + '" pageType="ask" action_uid="' + uid + '"></div>';
                            }
                            str += '</div></div>';
                        });
                        if (arg[2] == "more") {
                            arg[0].find("#askDetail_note").find("div.comments_box").append(str);
                            if (arg[1].is_end) {
                                arg[0].find("#askDetail_note").find("div.comments_more").remove();
                            }
                        } else {
                            if (arg[0].has('#askDetail_note')) {
                                arg[0].find("#askDetail_note").html('<div class="comments_box">' + str + '</div>');
                            } else {
                                arg[0].append('<div id="askDetail_note"><div class="comments_box">' + str + '</div></div>');
                            }
                            if (!arg[1].is_end) {
                                arg[0].find("#askDetail_note").append('<div class="comments_more" page="' + self.commentPage + '"><a href="javascript:void(0);">点击查看更多</a></div>');
                            }
                        }
                        self.plug(["commentEasy"], [arg[0].find("div.comments_box").find("div.operation")]);
                        self.event("delete_comment", self.askDetail.find("div.comment"));
                        self.event("commentMore", self.askDetail.find("div.comments_more"));


                    } else {

                        arg[0].find("#askDetail_note").find("div.comments_more").remove();
                        arg[0].find("#askDetail_note").find("div.comments_box").remove();
                        arg[0].find("#askDetail_note").append("<div class='comments_box'></div>");
                        return false;
                    }

                },
                //删除问答
                del_asks:function (arg) {
                    if (arg[0].status == "1") {
                        $("#popUp").find("span.closeBtn").click();
                        if (arg[1].find('ul.block').length > 0 || arg[1].find('div.add_new_item').length>0) {
                            var islist = arg[1].closest('div#askList_box'),parent = arg[1].parent();
                            arg[1].remove();
                            if(islist.length>0){
                                var list_len = islist.find('div.questions').length;
                                if(list_len<1){
                                    islist.find('div.noInfo').show();
                                }
                            }else{
                                parent.html("<li class='noInfo'>问答已被删除或者您访问的问答不存在</li>");
                            }
                        }
                    }
                },
                //添加选项
                add_options:function (arg) {
                    if (arg[1].status == "1") {
                        var value = arg[1].data.options;
                        var $newOption = $('<ul class="block clearfix" pollid="' + arg[2] + '" qid="' + arg[1].data.id + '"><li class="poll_btn"><input type="' + arg[3] + '" name="option_id' + arg[2] + '" checked="checked"></li><li class="poll_result_bar tip_up_right_black" tip="0票"><div style="width:0%" class="shaded"></div><div class="label">' + value + '</div></li><li class="target_event"></li><li class="poll_result_facepile"><ul class="pile_list clearfix"></ul></li></ul>');
                        arg[0].before($newOption);
                        self.iefix(["height"], [arg[0].prev()]);
                        if (arg[4] > 98) {
                            arg[0].hide();
                        }
                    } else {
                        $.alert(arg[1].info, '提示');
                        arg[0].hide();
                    }
                },
                //取消投票
                cancelVote:function (arg) {
                    if (arg[1].status == "1") {
                        var data = {};
                        data.poll_id = self.askDetail.attr("frmid");
                        self.model("one_ask", [data, function (data) {
                            self.view(["askDetail"], [self.askDetail, data]);
                            if (data.status == 1) {
                                if (arg[2].length > 0) {
                                    var thisPrev = arg[2].prev(),isAsklist=arg[2].has('a.user_face');
                                    arg[2].remove();
                                    if(isAsklist.length>0){
                                        if(thisPrev.length>0){
                                            self.view(['asklistShow_three'], [data.data,thisPrev,'after']);
                                        }else{
                                            self.view(['asklistShow_three'], [data.data,self.$e,'prepend']);
                                        }
                                    }else{
                                        if(thisPrev.length>0){
                                            self.view(['asklistShow_one'], [data.data,thisPrev]);
                                        }else{
                                            self.view(['asklistShow_one'], [data.data,self.$e,'prepend']);
                                        }
                                    }
                                }
                            } else {
                                $.alert(data.info, '提示');
                                return false;
                            }
                        }]);
                    }
                },
                //删除选项
                del_options:function (arg) {
                    if (arg[1].status == "1") {
                        arg[0].parent().remove();
                        var pp = $('div.questions[poll_id=' + arg[2].poll_id + ']').find('ul.block[qid=' + arg[2].option_id + ']');
                        pp.remove();
                    }
                },
                //添加评论
                add_comments:function (arg) {
                    if (arg[1].status == "1") {
                        var a = arg[1].data;
                        var id = a.id, name = a.username, img = a.img, msg = a.message, time = a.dateline, uid = a.uid,str = "", options=a.options,dkcode = a.dkcode,textArr=[];
                        if (options.length>0) {
                            $.each(options, function (i, v) {
                                textArr.push('"<span class="question">' + v.message + '</span>"');
                            })
                            if (textArr.length > 0) {
                                str = "选择了 ";
                                str += textArr[0];
                            }
                            if(textArr.length > 1){
                                str += ' 和其他'+(textArr.length-1)+'个答案';
                            }
                        }
                        var $newComment = $('<div frmid="' + id + '" class="comment clearfix"><span class="ui_closeBtn_box" style="display:none"><i class="ui_closeBtn png"></i></span><div class="reply_user"><a href="'+ mk_url('main/index/main ',{dkcode:dkcode}) +'"><img width="50" border="0" height="50" src="' + img + '"></a></div><div class="reply_content"><span class="name">' + name + '</span><span class="text">' + str + '</span><span class="content">' + msg + '</span><div class="operation  clearfix" time="刚刚" commentObjId="' + id + '" action_uid="' + uid + '" pageType="ask"></div></div></div>');
                        arg[0].find("div.comments_box").prepend($newComment);


                        self.plug(["commentEasy"], [arg[0].find("div.comments_box").find("div.operation")]);
                        self.event("delete_comment", $newComment);
                    }
                },
                //删除评论
                del_comments:function (arg) {
                    if (arg[1].status == "1") {
                        arg[0].parent().remove();
                    }
                },
                //添加投票选项
                voting:function (arg) {
                    if (arg[1].status == 0) {
                        $.alert(arg[1].info, '提示')
                        return false;
                    }
                    if (arg[1].is_del && arg[1].is_del == 1) {

                        arg[0].remove();
                        $('.tip_win_black').remove();
                        return false;
                    }
                    var a = arg[1].data;
                    var total = parseInt(a.votes) || 1, img = a.img, name = a.username, dkcode = a.dkcode;
                    var tipId, $pile_list, pollResultBar, votingNum;
                    var $pile = $('<li dkcode="' + dkcode + '"><a href="' + mk_url('main/index/main',{dkcode:dkcode}) + '" class="uiface_pile_face"> <img width="24" height="24" tip="' + name + '" class="tip_up_black" src="' + img + '"> </a> </li>');
                    // set voting scrollbar
                    function set_voting_number(obj, number) {
                        $.each(obj, function () {
                            if ($(this).find("li[dkcode=" + dkcode + "]").size() != 0 || number == 1) {    // 当前，循环对象判断含有该ID
                                pollResultBar = $(this).children(".poll_result_bar");
                                votingNum = parseInt(pollResultBar.attr("tip"));
                                pollResultBar.children(".shaded").css("width", (votingNum + number) / total * 100 + "%");
                                pollResultBar.attr("tip", votingNum + number + "票");
                                tipId = pollResultBar.attr("tipId");
                                $("body").children("div[tipId=" + tipId + "]").find("div.bg").text(votingNum + number + "票");
                            } else if ($(this).find("li[dkcode=" + dkcode + "]").size() == 0 && number == -1) {
                                pollResultBar = $(this).children(".poll_result_bar");
                                votingNum = parseInt(pollResultBar.attr("tip"));
                                pollResultBar.children(".shaded").css("width", (votingNum / total) * 100 + "%");
                                pollResultBar.attr("tip", votingNum + "票");
                                tipId = pollResultBar.attr("tipId");
                                $("body").children("div[tipId=" + tipId + "]").find("div.bg").text(votingNum + "票");
                            }
                        });

                    }
                    //set the more voting scrollbar
                    function set_voting_number_more(o, p, number) {
                        var bfb;

                        function set(pollResultBar, vote, bfb) {
                            pollResultBar.children(".shaded").css("width", bfb + "%");
                            pollResultBar.attr("tip", vote + "票");
                            tipId = pollResultBar.attr("tipId");
                            $("body").children("div[tipId=" + tipId + "]").find("div.bg").text(vote + "票");
                        }

                        $.each(p, function () {
                            pollResultBar = $(this).children(".poll_result_bar");
                            votingNum = parseInt(pollResultBar.attr("tip"));
                            bfb = ((votingNum) / total * 100)
                            set(pollResultBar, votingNum, bfb)
                        });
                        pollResultBar = o.children(".poll_result_bar");
                        votingNum = parseInt(pollResultBar.attr("tip"));
                        votingNum + number <= 0 ? bfb = 0 : bfb = (votingNum + number) / total * 100;
                        set(pollResultBar, votingNum + number, bfb)
                    }
                    //set face img
                    function set_face(obj, op) {
                        $.each(obj, function () {
                            $pile_list = $(this).find(".pile_list");
                            if (op == "del") {
                                $pile_list.find("li[dkcode=" + dkcode + "]").remove();
                            } else {
                                $pile_list.prepend($pile.clone());
                                self.plug(["tip_up_black"], [$pile_list]);
                            }
                        });
                    }
                    if (arg[2] == "radio") {
                        if (arg[3]) {
                            arg[0].find("li.poll_btn").find('input').attr('checked', false);
                            set_voting_number(arg[0].find("ul.block"), -1);//其他-1
                            set_face(arg[0].find("ul.block"), "del");
                        } else {
                            arg[0].find("li.poll_btn").find('input').attr('checked', true);
                            set_voting_number(arg[0], 1);//焦点+1
                            set_face(arg[0], "add", arg[1]);
                            set_voting_number(arg[0].siblings("ul.block"), -1);//其他-1
                            set_face(arg[0].siblings("ul.block"), "del");
                        }
                    } else {
                        var allUl = arg[0].parent().find("ul.block");
                        if (arg[3]) {
                            arg[0].find("li.poll_btn").find('input').attr('checked', false);
                            set_voting_number_more(arg[0], allUl, -1);
                            set_face(arg[0], "del");
                        } else {
                            arg[0].find("li.poll_btn").find('input').attr('checked', true);
                            set_voting_number_more(arg[0], allUl, 1);
                            set_face(arg[0], "add");
                        }
                    }
                },
                //编辑选项按钮
                eidtOk:function (arg) {
                    var $ul = arg[0].find("ul.block");
                    $.each($ul, function () {
                        $(this).find("li.editLi").remove();
                        $(this).children("li.poll_result_facepile").show();
                        $(this).find("input").attr("disabled", false);
                    });
                    arg[0].find("div.add_new_item").show();
                },
                //编辑选项
                eidt:function (arg) {
                    var $ul = arg[0].find("ul.block");
                    var $li = $('<li class="editLi"></li>');
                    var $c = $('<a class="delete">x</a>');
                    $li.click(function (e) {
                        var data = {};
                        var elm = $(this);
                        data.option_id = elm.parent().attr("qid");
                        data.poll_id = elm.parent().attr("pollid");
                        self.plug(["popUpDelete"], [arg[0], '确定要删除该项吗？', "提示", function () {
                            self.model("del_options", [data, function (json) {
                                self.view(["del_options"], [elm, json, data]);
                            }, elm]);
                        }]);


                        e.stopPropagation();
                    });
                    $li.mouseover(function () {
                        $(this).children().addClass("hover");
                        $(this).prev().prev().trigger("mouseover");
                        //$(this).prev().prev().prev().trigger("mouseover");
                    })
                    $li.mouseout(function () {
                        $(this).children().removeClass("hover");
                        $(this).prev().prev().trigger("mouseout");
                        //$(this).prev().prev().prev().trigger("mouseout");
                    })
                    $li.append($c.clone(true));
                    $.each($ul, function () {
                        $(this).append($li.clone(true));
                        $(this).children("li.poll_result_facepile").hide();
                        $(this).find("input").attr("disabled", true);
                    });
                    arg[0].find("div.add_new_item").hide();
                    self.iefix(["height"], [arg[0]]);
                },
                //投票人列表
                relation_list:function (arg) {
                    if (arg[1].status == "1") {
                        var a = arg[1].data, str = "", status = "";
                        $.each(a, function (i, v) {
                            var uid = v.uid, name = v.username, img = v.avatar, status = v.status || 0, dkcode = v.dkcode, link_url = v.link_url;

                            if (String(status) == "0") {

                                status = "";
                            } else {
                                status = '<div uid="' + uid + '" rel="' + status + '" class="statusBox"></div>';
                            }


                            str += '<li class="other_voters clearfix" frmid="' + uid + '"><a href="' + link_url + '"><img src="' + img + '" width="32" height="32" /></a><div class="content"><strong><a href="' + link_url + '">' + name + '</a></strong><span class="status">' + status + '</span></div></li>';
                        });

                        if (arg[2] == "more") {
                            var $str = $(str);
                            $str.find('div.statusBox').relation({quickDelFriend: true});
                            arg[0].children("ul").append($str);
                            if (arg[1].isend) {
                                arg[0].find("div.relation_more").remove();
                            }

                        } else {
                            arg[0].children("ul").html(str);

                            if (!arg[1].is_end) {
                                arg[0].append('<div class="relation_more" page="' + self.relation_more + '"><a href="javascript:void(0);">点击查看更多</a></div>');
                            }
                            arg[0].find('div.statusBox').relation({quickDelFriend: true});
                        }
                    } else {
                        arg[0].find("div.relation_more").remove();
                    }


                    if (arg[2]) {
                        self.event("focus_more", arg[0]);
                    } else {
                        self.event("relation_more", arg[0]);
                    }

                }
            };
            $.each(method, function (index, value) {
                if (value) {
                    return _class[value](arg);
                }
            })
        },
        plug:function (method, arg) {
            var self = this;
            var _class = {
                // global comment plug
                commentEasy:function (arg) {
                    arg[0].commentEasy({
                        minNum:3,
                        UID:CONFIG['u_id'],
                        userName:CONFIG['u_name'],
                        avatar:CONFIG['u_head'],
                        userPageUrl:'http:\/\/'+CONFIG['domain']+'main/index/profile?dkcode='+$('#dkou').val(),
                        relayCallback:function (obj,_arg) {
                            var comment=new ui.Comment();
                            comment.share(obj,_arg);
                        },
                        onLoadCallback:function(){
                            $.each(arg[0], function () {
                                var time = $(this).attr("time");
                                if ($(this).find(".comment_title").find("li.time").size() == 0) {
                                    $(this).find(".comment_title").prepend("<li class='time'>" + time + "</li><li><strong> · </strong></li>");
                                }
                            });
                        }
                    });
                },
                tip_up_right_black:function (arg) {
                    arg[0].find(".tip_up_right_black").tip({
                        direction:"up",
                        position:"right",
                        stopPropagation:true,
                        skin:"black"
                    });
                },
                tip_up_black:function (arg) {
                    arg[0].find(".tip_up_black").tip({
                        direction:"up",
                        skin:"black"
                    });
                },
                tip_right_black:function (arg) {
                    arg[0].find(".tip_right_black").tip({
                        direction:"right",
                        skin:"black"
                    });
                },
                tip_up_djax:function (arg) {
                    arg[0].find(".tip_up_djax").tip({
                        direction:"up",
                        djax:true,
                        hold:true
                    });
                },
                msg:function (arg) {
                    arg[0].find("[msg]").msg();
                },
                popUpAskDetail:function (arg) {
                    arg[1].find("div.comments_box").html("");
                    arg[1].find("div.item_box").html("");
                    arg[0].popUp({
                        width:580,
                        title:'问答详情',
                        content:arg[1],
                        mask:true,
                        buttons:'<span class="popBtns closeBtn">关闭</span>',
                        maskMode:true,
                        callback:function () {
                            $.closePopUp();
                        },
                        closeCallback:function () {
                            $('#popUp').find('.popTitle').html('问答详细');
                            if (self.askDetailChange) {
                                //self.ask_operation.find("span.on").click();
                                self.askDetailChange = false;
                            }
                        }
                    });
                },
                popUpVotersDetail:function (arg) {
                    arg[0].subPopUp({
                        width:arg[3] || 500,
                        title:arg[2],
                        content:arg[1],
                        mask:true,
                        buttons:'<span class="popBtns closeBtn">关闭</span>',
                        maskMode:true,
                        callback:function () {
                            $.closeSubPop();
                        },
                        closeCallback:function () {
                            if (arg[4] && typeof(arg[4]) == "function") {
                                arg[4]();
                            }
                            //self.ask_operation.find("span.on").click();
                        }
                    });
                },
                popUpDelete:function (arg) {
                    $.confirm(arg[2], arg[1], arg[3]);
                },
                tip_up:function (arg) {
                    arg[0].tip({
                        direction:"up",
                        width:"auto",
                        showOn:"click",
                        content:arg[1],
                        key:arg[2],
                        hold:true
                    });
                }
            };
            $.each(method, function (index, value) {
                if (value) {
                    return _class[value](arg);
                }
            })
        },
        event:function (type, dom) {
            var self = this;
            //show askdefault in popUpbox
            var showDefault=function(event){
                var data = {};
                data.poll_id = $(this).closest("div.questions").attr("poll_id");
                self.plug(["popUpAskDetail"], [$(this), self.askDetail]);
                self.model("one_ask", [data, function (result) {
                    if (result.status == "0") {
                        self.askDetail.html("<li class='noInfo' style='margin-top:160px;'>" + result.info + "</li>");
                        return false;
                    }
                    self.view(["askDetail"], [self.askDetail, result]);
                    self.commentPage = 1;
                    self.model("list_comments", [
                        {frmid:data.poll_id, page:self.commentPage},
                        function (data) {
                            self.view(["note"], [self.askDetail, data, self.commentPage]);
                        }
                    ]);
                }]);
                event.preventDefault();
            };
            switch (type) {
                case "askInTimeline_one":
                    dom.find("div.add_new_item").find("input").focus(function () {
                        $(this).closest("table").find("td:last").show();
                    });
                    dom.find(".other_item").find("span").hover(function () {
                        $(this).css({ "background":"#ebeff4", "border-color":"#6d84b4"});
                    }, function () {
                        $(this).css({ "background":"", "border-color":"#BECBDD"});
                    });
                    dom.unbind("click").bind("click", function (e) {
                        if ($(e.target).attr("class") == "other_item_span") {
                            showDefault.call(e.target, e);
                        }
                    });
                    dom.find("a[name=question]").off("click").on({
                        'click':function(e){showDefault.call(this, e);}
                    });
                    dom.find("span.ding_focus").unbind("click").bind("click",function(){
                        var elm = $(this);
                        var data = {};
                        data.object_id = elm.closest('div.questions').attr("poll_id");
                        self.model("post_objectfollow", [data, function (data) {
                            if (data.status == "0") {
                                $.alert(data.info, '提示');
                                return false;
                            }
                            elm.hide();
                            elm.next().show();
                        }])
                    });
                    dom.find("span.cancel_focus").unbind("click").bind("click",function(){
                        var elm = $(this);
                        var data = {};
                        data.object_id = elm.closest('div.questions').attr("poll_id");
                        self.model("del_objectfollow", [data, function (data) {
                            if (data.status == "0") {
                                $.alert(data.info,'提示');
                                return false;
                            }
                            elm.hide();
                            elm.prev().show();
                        }])
                    });
                    dom.find("span.choice_friends").unbind("click").bind("click",function(){
                        var elm = $(this);
                        var id = elm.closest("div.questions").attr("poll_id");
                        new CLASS_FRIENDS_LIST({
                            title:"好友列表",
                            detail:self.friends_detail,
                            id:id,
                            uid:self.uid,
                            elm:elm,
                            dataType:'jsonp',
                            type:'GET',
                            hasPage:true,
                            getUrl:mk_url('ask/ask/listFriend'),
                            postUrl:mk_url('ask/ask/askFriend'),
                            searchUrl:mk_url('ask/ask/listFriend')
                        });
                    });
                    break;
                case "askInTimeline_two":
                    dom.find('a[name=questionshow]').off('click').on({
                        'click':function(e){showDefault.call(this, e);}
                    });
                    break;
                case "askDetail_item"://in askdetai's event
                    self.event("new_item", self.askDetail);
                    self.event("item_box", self.askDetail);
                    self.askDetail.find("div.reply_input").find("input").focus(function () {

                        $(this).parent().find("div.reply_btn").show();
                    });
                    self.askDetail.find(".other_item").find("span").hover(function () {
                        $(this).css({ "background":"#ebeff4", "border-color":"#6d84b4"});
                    }, function () {
                        $(this).css({ "background":"", "border-color":"#BECBDD"});
                    }).off('click').on('click',function(){
                            var data = {
                                poll_id:self.askDetail.attr('frmid'),
                                page:self.optionsPage
                            },other_item=$(this).parent();
                            self.model(['get_options'],[data,function(data){
                                if(data.status==0){
                                    $.alert(data.info,'提示');
                                    return false;
                                }
                                other_item.remove();
                                self.view(['getOptions'],[$('#askDetailForm').find('.item_box'),data.data,data.is_end,data.votes,data.optionsNum])
                            }])
                        });
                    self.askDetail.find("div.reply_btn").unbind("click").bind("click", function () {
                        if ($(this).hasClass("disabled")) {
                            return false;
                        }
                        $(this).addClass("disabled");
                        var data = {};
                        var elm = $(this);
                        data.frmid = self.askDetail.attr("frmid");
                        data.message = $(this).parent().find("input").val();
                        if ($.trim(data.message) == "") {
                            $(this).parent().find("input").val("").focus();
                            elm.removeClass("disabled");
                            return false;
                        }
                        var $li = $("div.questions[poll_id="+data.frmid+"]");
                        self.model("add_comments", [data, function (data) {
                            elm.removeClass("disabled");

                            if (data.status == 0) {
                                $.alert(data.info, '提示');
                                return false;
                            }
                            self.view(["add_comments"], [self.askDetail, data]);
                            elm.parent().find("input").val("");
                            self.cpu("setCommentsNum",[$li,1]);
                        }])
                    });
                    self.askDetail.find("div.cancelVote").unbind("click").bind("click", function () {
                        var elm = $(this);
                        var data = {};
                        data.poll_id = self.askDetail.attr("frmid");
                        self.model("cancel_allvote", [data, function (json) {
                            self.view(["cancelVote"], [self.askDetail, json, $('div.questions[poll_id=' + data.poll_id + ']').has('ul.block')]);
                            elm.remove();
                        }, elm]);
                    });
                    self.askDetail.find("a.deleteAsk").unbind("click").bind("click", function () {
                        var elm = $(this);
                        var data = {};
                        data.poll_id = self.askDetail.attr("frmid");
                        self.plug(["popUpDelete"], [$(this), "确定删除该问题吗？", "提示", function () {
                            self.model("del_asks", [data, function (json) {
                                self.view(["del_asks"], [json, $('div.questions[poll_id=' + data.poll_id + ']')]);
                                //self.ask_operation.find("span.on").click();
                            }, elm]);
                        }]);
                    });
                    self.askDetail.find("div.ding_focus").unbind("click").bind("click", function () {
                        var elm = $(this);
                        var data = {};
                        data.object_id = self.askDetail.attr("frmid");
                        self.model("post_objectfollow", [data, function (json) {
                            if (json.status == "0") {
                                $.alert(json.info, '提示');
                                return false;
                            }
                            var pp = $('div.questions[poll_id='+ data.object_id +']').find('span.ding_focus');
                            if(pp.length>0){
                                pp.hide();
                                pp.next().show();
                            }
                            elm.hide();
                            elm.next().show();
                        }])
                    });
                    self.askDetail.find("div.cancel_focus").unbind("click").bind("click", function () {
                        var elm = $(this);
                        var data = {};
                        data.object_id = self.askDetail.attr("frmid");
                        self.model("del_objectfollow", [data, function (json) {
                            if (json.status == "0") {
                                $.alert(json.info,'提示');
                                return false;
                            }
                            var pp = $('div.questions[poll_id='+ data.object_id +']').find('span.cancel_focus');
                            if(pp.length>0){
                                pp.hide();
                                pp.prev().show();
                            }
                            elm.hide();
                            elm.prev().show();
                        }])
                    });
                    self.askDetail.find("a.editAsk").unbind("click").bind("click", function () {
                        var $editOk = $('<div class="editOk"><span>编辑完成</span></div>');
                        var ele = $(this);
                        self.askDetail.find("div.item_box").append($editOk);
                        $editOk.unbind("click").bind("click", function () {
                            self.view(["eidtOk"], [self.askDetail]);
                            $(this).remove();
                            var data = {};
                            data.poll_id = self.askDetail.attr("frmid");
                            self.model("one_ask", [data, function (data) {
                                self.view(["askDetail"], [self.askDetail, data]);
                            }]);
                            ele.parent().show();
                        });
                        ele.parent().hide();
                        self.view(["eidt"], [self.askDetail]);
                    });
                    self.askDetail.find("div.ask_friends").unbind("click").bind("click", function () {
                        var elm = $(this);
                        var id = self.askDetail.attr("frmid");
                        new CLASS_FRIENDS_LIST({
                            title:"好友列表",
                            detail:self.friends_detail,
                            id:id,
                            uid:self.uid,
                            elm:elm,
                            type:'GET',
                            dataType:'jsonp',
                            hasPage:true,
                            getUrl:mk_url('ask/ask/listFriend'),
                            postUrl:mk_url('ask/ask/askFriend'),
                            searchUrl:mk_url('ask/ask/listFriend')
                        });
                    });
                    break;
                case "new_item"://add a new answer's event
                    $("div.add_new_item").find("input").focus(function () {
                        $(this).closest("table").find("td:last").show();
                    });
                    var newOptionFunction = function () {
                        var elm = $(this);
                        var p = elm.closest("div.questions");
                        if (p.size() == 0) { // 说明是askdetai 里面
                            p = elm.closest("div.item_box");
                        }


                        var pInput = elm.closest("div.add_new_item").find("input");
                        var muti = p.attr("muti") || self.askDetail.attr("muti");
                        var size = p.find("ul.block").size();
                        if (muti == "0") {
                            type = "radio";
                        } else {
                            type = "checkbox";
                        }
                        if ($.trim(pInput.val()) == "") {
                            pInput.focus().val("");
                            return false;
                        }
                        var data = {};
                        data.poll_id = p.attr("poll_id") || self.askDetail.attr("frmid");
                        data.message = pInput.val();
                        var someName = false;

                        $.each(p.find("div.label"), function () {
                            if (data.message == $(this).text()) {
                                someName = true;
                            }
                        });
                        if (someName) {
                            elm.closest("div.add_new_item").find("input").val("").focus();
                            return false;
                        }
                        var isPop = self.askDetail.size() != 0 && self.askDetail.closest(".popUpWindow").size() != 0 && self.askDetail.closest(".popUpWindow").css("display") != "none";

                        var pp = [], pl;
                        var add_new_item = elm.closest("div.add_new_item");
                        if (isPop) {
                            var pollid = data.poll_id;
                            pp = $('div.questions[poll_id=' + pollid + ']').find('div.add_new_item');
                            pl = $('div.questions[poll_id=' + pollid + ']');
                            if (pl.length > 1) {
                                pl = pl.has('ul.block');
                            }
                        }
                        self.model(["add_options"], [data, function (json) {

                            elm.bind("click", newOptionFunction);
                            if (json.status == "0") {
                                $.alert(json.info, '提示');
                                return false;
                            }
                            self.view(["add_options"], [add_new_item, json, data.poll_id, type, size]);
                            var newItem = add_new_item.prev();
                            self.view(["voting"], [newItem, json, type]);
                            self.event("item_box", newItem);
                            self.plug(["tip_up_right_black", "tip_up_black", "tip_right_black", "msg"], [newItem]);

                            $.each(pp, function () {
                                self.view(["add_options"], [$(this), json, data.poll_id, type, size]);
                                self.view(["voting"], [$(this).prev(), json, type]);
                                self.event("item_box", $(this).prev());
                                self.plug(["tip_up_right_black", "tip_up_black", "tip_right_black", "msg"], [$(this).prev()]);
                            });
                            isPop?self.cpu("setvotingNum",[pl,1]):self.cpu("setvotingNum",[p,1]);
                            elm.closest("div.add_new_item").find("input").val("").focus();
                        }, elm]);
                        elm.unbind("click", newOptionFunction);
                    }
                    $("div.add_new_item").find(".uiButton").unbind("click").bind("click", newOptionFunction);
                    break;
                case "item_box"://the question's options's event
                    dom.find("form").unbind("submit").bind("submit", function () {
                        $(this).find("#add_new_item").click();
                        return false;
                    });
                    dom.find("li.target_event").unbind('mouseover').bind('mouseover',function(e){
                        $(this).prev().css("border", "1px solid #3b5998");
                        $(this).prev().mouseover();
                        e.stopPropagation();
                        return false;
                    }).unbind('mouseout').bind('mouseout',function(e){
                        $(this).prev().css("border", "1px solid #bbbbbb");
                        $(this).prev().mouseout();
                        e.stopPropagation();
                        return false;
                    }).unbind("click").bind("click", function () {
                            $(this).prev().prev().children().trigger("click");
                            return false;//e.stopPropagation();
                        });
                    dom.find("li.poll_btn").children().unbind("click").bind("click", function (e) {
                        var $this = $(this);
                        var checked = $(this).attr("checked");
                        var pLi = $(this).closest("div.questions");
                        var p = $(this).closest("ul.block");

                        var type = $(this).attr("type");
                        var data = {};
                        data.poll_id = pLi.attr("poll_id") || self.askDetail.attr("frmid");
                        data.option_id = p.attr("qid");
                        // if pop is show
                        var isPop = self.askDetail.size() != 0 && self.askDetail.closest(".popUpWindow").size() != 0 && self.askDetail.closest(".popUpWindow").css("display") != "none";
                        var pp = [];
                        var $li = $("div.questions[poll_id=" + data.poll_id + "]");
                        if ($li.length > 1) {
                            $li = $li.has('ul.block');
                        }
                        if (isPop) {
                            var pollid = $(this).closest('ul').attr('pollid');
                            var qid = $(this).closest('ul').attr('qid');
                            pp = $('div.questions[poll_id=' + pollid + ']').find('ul[qid=' + qid + ']');
                        }
                        if (type == "radio") {
                            if (checked) {
                                return false;
                            } else {
                                var list_content = $('ul[pollid=' + pollid + ']').closest('div.content');
                                var checkedinput = list_content.find('input[checked=checked]');
                                self.model("add_voting", [data, function (data) {
                                    self.view(["voting"], [p, data, type]);
                                    if (pp.length > 0) {
                                        $.each(pp, function () {
                                            self.view(["voting"], [$(this), data, type]);
                                            $(this).find("input").attr("checked", true);
                                        });
                                    } else if (isPop) {
                                        checkedinput.attr('checked', false);
                                        self.view(["voting"], [list_content, data, type, true]);
                                    }
                                }, $(this).closest("ul.block")]);
                                pLi.length>0?self.cpu('setvotingNum',[pLi,1]):self.cpu('setvotingNum',[pp,1]);
                            }
                        } else {
                            if (checked) {
                                self.model("del_voting", [data, function (data) {

                                    self.view(["voting"], [p, data, type , checked]);
                                    if (pp.length > 0) {
                                        $.each(pp, function () {
                                            self.view(["voting"], [$(this), data, type, checked]);
                                            self.cpu('setvotingNum',[pp,-1]);
                                        });
                                    }else{
                                        self.cpu('setvotingNum',[pLi,-1]);
                                    }
                                }, $(this).closest("ul.block")]);
                            } else {
                                self.model("add_voting", [data, function (data) {
                                    self.view(["voting"], [p, data, type , checked]);
                                    if (pp.length > 0) {
                                        $.each(pp, function () {
                                            self.view(["voting"], [$(this), data, type, checked]);
                                            self.cpu('setvotingNum',[pp,1]);
                                        });
                                    } else {
                                        self.cpu('setvotingNum',[pLi,1]);
                                    }
                                }, $(this).closest("ul.block")]);
                            }
                        }
                        e.preventDefault();
                    });
                    break;
                case "otherPerson"://the more voters button's click event
                    dom.find("li.otherPerson").unbind("click").bind("click", function () {
                        var data = {};
                        self.relation_more = 1;
                        data.poll_id = $(this).closest("ul.block").attr("pollid");
                        data.option_id = $(this).closest("ul.block").attr("qid");
                        data.page = self.relation_more;
                        self.list_voters_detail = $("<div id='list_voters_detail' poll_id='" + data.poll_id + "' option_id='" + data.option_id + "' class='detail'><ul></ul></div>");

                        self.plug(["popUpVotersDetail"], [$(this), self.list_voters_detail, "投选了该选项的人"]);

                        self.model("list_voters", [data, function (data) {
                            self.view(["relation_list"], [self.list_voters_detail, data]);
                            $.resetPopUp();
                        }]);

                    });
                    break;
                case "delete_comment"://del comment's event
                    dom.find("i.ui_closeBtn").hover(function () {
                        $(this).css("background-position", "0px -15px");
                    }, function () {
                        $(this).css("background-position", "0px 0px");
                    })
                    dom.hover(function () {
                        $(this).children("span.ui_closeBtn_box").show();
                    }, function () {
                        $(this).children("span.ui_closeBtn_box").hide();
                    });
                    dom.find("span.ui_closeBtn_box").unbind("click").bind("click", function (e) {
                        var data = {};
                        var elm = $(this);

                        data.id = elm.closest("div.comment").attr("frmid");
                        data.frmid = self.askDetail.attr("frmid");

                        var $li = $("div.questions[poll_id=" + data.frmid + "]");
                        self.plug(["popUpDelete"], [$(this), "确定删除该条评论吗？", "提示", function () {
                            self.model("del_comments", [data, function (json) {
                                self.view(["del_comments"], [elm, json]);
                                self.cpu("setCommentsNum",[$li,-1]);
                            }]);
                        }]);

                        e.stopPropagation();
                        return false;
                    });
                    break;
                case "commentMore"://the more comment's button
                    dom.unbind("click").bind("click", function () {
                        self.commentPage++;
                        var data = {};
                        data.poll_id = self.askDetail.attr("frmid");

                        self.model("list_comments", [
                            {frmid:data.poll_id, page:self.commentPage},
                            function (data) {
                                self.view(["note"], [self.askDetail, data, "more"]);
                            }
                        ]);
                    })
                    break;
                default:
                    break;
            }
        },
        model:function (method, arg) {
            var self = this;
            var _class = {
                //del one ask
                del_asks:function (arg) {
                    $.djax({
                        url:mk_url('ask/ask/delAsk'),
                        data:arg[0],
                        type:'GET',
                        dataType:'jsonp',
                        success:arg[1]
                    });
                },
                //get question's options
                get_options:function(arg){
                    $.djax({
                        url:mk_url('ask/ask/getOptions',{time:new Date().getTime()}),
                        type:'GET',
                        dataType:'jsonp',
                        relative:false,
                        data:arg[0],
                        success:arg[1]
                    });
                },
                //add question's options
                add_options:function (arg) {
                    var url = mk_url('ask/ask/addOption');
                    $.djax({
                        el:arg[2],
                        loading:true,
                        url:url,
                        type:'GET',
                        dataType:'jsonp',
                        data:arg[0],
                        success:arg[1]
                    });
                },
                //添加投票 参数：poll_id,option_id
                add_voting:function (arg) {
                    $.djax({
                        obj:arg[2],
                        loading:true,
                        relative:true,
                        type:'GET',
                        dataType:'jsonp',
                        async:true,
                        aborted:false,
                        url:mk_url('ask/ask/addVote'),
                        data:arg[0],
                        success:arg[1]
                    });
                },
                //删除投票 参数：poll_id,option_id
                del_voting:function (arg) {
                    $.djax({
                        obj:arg[2],
                        loading:true,
                        relative:true,
                        async:true,
                        aborted:false,
                        type:'GET',
                        dataType:'jsonp',
                        url:mk_url('ask/ask/delVote'),
                        data:arg[0],
                        success:arg[1]
                    });
                },
                //投票人列表 参数：poll_id,option_id,page
                list_voters:function (arg) {
                    $.djax({
                        url:mk_url('ask/ask/listVoter',{time:new Date().getTime()}),
                        dataType:"jsonp",
                        type:'GET',
                        data:arg[0],
                        success:arg[1]
                    });
                },
                //get one ask
                one_ask:function (arg) {
                    $.djax({
                        //obj:self.askDetail,
                        url:mk_url('ask/ask/getAsk',{time:new Date().getTime()}),
                        //loading:true,
                        type:'GET',
                        dataType:'jsonp',
                        relative:false,
                        data:arg[0],
                        success:arg[1]
                    });
                },
                //del question's options
                del_options:function (arg) {
                    var url = mk_url('ask/ask/delOption');
                    $.djax({
                        el:arg[2],
                        loading:true,
                        url:url,
                        data:arg[0],
                        type:'GET',
                        dataType:'jsonp',
                        success:arg[1]
                    });
                },
                //cancel all my votes
                cancel_allvote:function (arg) {
                    $.djax({
                        url:mk_url('ask/ask/cancelVote'),
                        type:'GET',
                        dataType:'jsonp',
                        data:arg[0],
                        relative:true,
                        success:arg[1]
                    });
                },
                //add follow
                post_objectfollow:function (arg) {
                    $.djax({
                        url:mk_url('ask/ask/addFollow'),
                        type:'GET',
                        dataType:'jsonp',
                        data:arg[0],
                        success:arg[1]
                    });
                },
                //del follow
                del_objectfollow:function (arg) {
                    $.djax({
                        url:mk_url('ask/ask/delFollow'),
                        type:'GET',
                        dataType:'jsonp',
                        data:arg[0],
                        success:arg[1]
                    });
                },
                //get comments list
                list_comments:function (arg) {
                    $.djax({
                        url:mk_url('ask/ask/listComments',{time:new Date().getTime()}),
                        type:'GET',
                        dataType:'jsonp',
                        relative:true,
                        data:arg[0],
                        success:arg[1]
                    });
                },
                //add comments
                add_comments:function () {
                    $.djax({
                        url:mk_url('ask/ask/addComment'),
                        type:'GET',
                        dataType:'jsonp',
                        data:arg[0],
                        relative:true,
                        success:arg[1]
                    });
                },
                //del comments
                del_comments:function () {
                    $.djax({
                        url:mk_url('ask/ask/delComment'),
                        type:'GET',
                        dataType:'jsonp',
                        data:arg[0],
                        relative:true,
                        success:arg[1]
                    });
                }
            };
            return _class[method](arg);
        },
        iefix:function (method, arg) {
            var self = this;
            var _class = {
                //解决ie6下不能兼容100%高度的问题
                height:function (arg) {
                    if ($.browser.msie && ($.browser.version == "6.0")) {
                        var h, shaded, target, editLi;
                        var $ul = arg[0].has("ul.block").length>0?arg[0].find("ul.block"):arg[0];
                        $.each($ul, function () {
                            h = $(this).height();
                            shaded = $(this).find("div.shaded");
                            target = $(this).find("li.target_event");
                            editLi = $(this).find("li.editLi");
                            if (editLi.size() != 0) {
                                editLi.height(h);
                            } else {
                                shaded.height(h - 2);
                                target.height(h);
                            }
                        });
                    }
                }
            }
            return _class[method](arg);
        },
        cpu:function(method,arg){
            var _class={
                //计算评论数
                setCommentsNum:function(arg){
                    var defaultNum = parseInt(arg[0].find("a.dialog_pipe").children("span").last().text());
                    arg[0].find("a.dialog_pipe").children("span").last().text(defaultNum+arg[1]);
                },
                //计算投票数
                setvotingNum:function(arg){
                    var myvotes=parseInt(arg[0].attr('myvote')),voterNum=parseInt(arg[0].find('span.votersNum').text());
                    arg[0].attr('myvote',myvotes+arg[1]);
                    if(myvotes==0&&arg[1]==1){
                        voterNum=parseInt(arg[0].find('span.votersNum').text())+1;
                    }else if(myvotes==1&&arg[1]==-1){
                        voterNum=parseInt(arg[0].find('span.votersNum').text())-1;
                    }else{
                        return;
                    }
                    arg[0].find('span.votersNum').text(voterNum);
                }
            }
            return _class[method](arg);
        }
    };
    $.fn.ask = function (options) {
        var opts = $.extend({}, options);
        return new input_ask(this, opts);
    }
    $.fn.ask.defaults = {
        type : 1//发表的地方 1[时间线]2[问答列表]3[网页]
    }
    $.fn.showAsk = function (options) {
        var opts = $.extend({},$.fn.showAsk.defaults, options);
        return new getAsk(this, opts);
    }
    $.fn.showAsk.defaults = {
        ispopBox:false,//是不是直接调用弹出层
        poll_id:''//弹出层调用问答id
    }
})(jQuery);