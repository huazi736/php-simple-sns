(function($){
    /*
     * Created on 2012-07-06.
     * @author: wangyuefei
     * @desc: 问答列表渲染插件
     * @example: $('dom').list_ask();
     * @param:
     */
    function list_ask(options){
        this.opts=options;
        this.init();
    }
    list_ask.prototype={
        init:function(){
            var self = this;
            self.friend_askList=$('#friend_askList');
            self.my_askList=$('#my_askList');
            self.askList_box=$('#askList_box');
            this.setting_voting = $("#setting_voting");				// 展开添加答案
            this.add_asks = $("#add_asks");							// 提问发送按钮
            this.addask = $("#addask");								// 提问展开问题按钮
            this.votingBox = $("#votingBox"); 						// 问题 多选项box
            this.inputBox = $("#inputBox"); 						// 问题输入框
            this.addNewAsk = $("#addNewAsk"); 						// 新问题box
            this.add_allPerson = $("#add_allPerson"); 				// 允许添加多人添加答案 box
            this.setting_moreOptions = $("#setting_moreOptions"); 	// 设置是否多选 box
            this.setting_more = $("#setting_more");
            self.dkou = $('#dkou').val();
            self.getmy = $('#getmy').val();
            var url = self.getmy==1?mk_url('ask/ask/listask',{getmy:1,time:new Date().getTime()}):mk_url('ask/ask/listask',{time:new Date().getTime()});
            self.view(['showlist'],[self.getmy,self.friend_askList,self.my_askList]);
            self.event([self.getmy]);
            self.askList_box.children('.listshow').scrollLoad({
                type:'GET',
                dataType:'jsonp',
                proxy:self,
                url:url,
                text:"显示更多问答",
                success:function(data,proxy){
                    self.view(["askList"],[self.askList_box.children('.listshow'),data]);
                }
            });
            self.plug('shareDestinationObjects');
            self.plug(['tip_up_right_black'], [this.addNewAsk]);
        },
        view:function(method,arg){
            var self=this;
            var _class={
                askList:function(arg){
                    var str = "",json=arg[1].data;
                    if(json.length<1){
                        arg[0].children("ul").html("");
                        self.askList_box.find(".noInfo").show();
                        return false;
                    }
                    arg[0].find('.onInfo').hide();
                    $.each(json,function(index,value){
                        arg[0].showAsk(value);
                    })
                },
                showlist:function(arg){
                    if(arg[0]==1){
                        arg[2].addClass('on');
                        arg[1].removeClass('on');
                    }else if(!arg[1].hasClass('on')){
                        arg[1].addClass('on');
                        arg[2].removeClass('on');
                    }
                }
            };
            return _class[method](arg);
        },
        event:function(arg){
            var self=this,url=mk_url('ask/ask/index');
            if(arg[0]!=1){
                url=mk_url('ask/ask/index',{getmy:1});
            }
            $('.ask_operation').find('span').off('click').on('click',function(e){
                if($(e.target).hasClass('on')){
                    return false;
                }
                location.href=url;
            })
            self.addask.toggle(function(){
                self.addNewAsk.show();
                self.plug('msg',[self.addNewAsk]);self.addNewAsk.find("#setting_voting").show();
                self.addNewAsk.find("#votingBox").hide();
                var voting_option = self.addNewAsk.find("#votingBox").find(".voting_option").children();
                voting_option.slice(0,3).find("input").val("").blur();
                voting_option.slice(3,voting_option.size()).remove();
                self.addNewAsk.find("#inputBox").children("input").val("").focus();
                self.addNewAsk.find("#setting_moreOptions").hide();
                self.addNewAsk.find("#setting_moreOptions").find("#add_allPerson").attr("checked",true);
                self.addNewAsk.find("#setting_more").find("input[value=1]").attr("checked",true);
                self.addNewAsk.find("#setting_more").find("input[value=0]").attr("disabled",true).parent().addClass("disabled").parent().hide();
            },function(){
                self.addNewAsk.hide();
            });
            self.setting_voting.click(function(){
                $(this).hide();
                self.votingBox.show();
                self.votingBox.find("input:first").focus();
                self.setting_moreOptions.show();
                self.setting_more.show();
            });
            self.votingBox.find("input:last").live("focus",function(){
                if(self.votingBox.find("div.voting_option").children().size()>9){
                    return false;
                }
                var newVoting = $('<div class="new_item"><input type="text" maxlength="80"  msg="添加选项" ></div>')
                self.votingBox.find("div.voting_option").append(newVoting);
                self.plug('msg',[newVoting]);
            });
            self.add_asks.click(function(){
                var data = {};
                var bool = true;
                var question = $.trim(self.inputBox.children("input").val());
                if(!question){
                    self.inputBox.children("input").val("").focus();
                    return false;
                }
                data.title = question;
                data.options = [];
                data.type = 3;
                data.permission = self.addNewAsk.find('input[name=permission]').val();
                $.each(self.votingBox.find("div.new_item"),function(){
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
                data.allow = self.add_allPerson.attr("checked")?1:0;
                data.multi = self.addNewAsk.find("input[name=moreRadio]:checked").val();

                if(data.allow==0&&data.options.length<2){
                    if($.trim(self.addNewAsk.find("div.new_item:first").find("input").val())==""){
                        self.addNewAsk.find("div.new_item:first").find("input").focus();
                    }else{
                        self.addNewAsk.find("div.new_item:eq(1)").find("input").focus();
                    }
                    return false;
                }
                self.model("addasks",[data,function(data){
                    if(data.status == 1){
                        self.addask.trigger("click");
                        self.addNewAsk.find("input[type=text]").val("").blur();
                        var newQuestions=$('<div class="insetNewQuestion"></div>');
                        self.askList_box.find('.listshow').prepend(newQuestions);
                        newQuestions.showAsk(data.data);
                        self.askList_box.find('.noInfo').hide();
                        $('#shareRights').find('a[rel=1]').click();
                    }else{
                        $.alert(data.info,'提示');
                        return false;
                    }
                }]);
            });
            self.addNewAsk.find("#add_allPerson").click(function(){
                if($(this).attr("checked")){
                    self.setting_more.find("input:first").attr("disabled",true).attr("checked",false).parent().addClass("disabled");
                    self.setting_more.find("input:last").attr("checked",true);
                }else{
                    self.setting_more.find("input:first").attr("disabled",false).parent().removeClass("disabled");
                }
            });
        },
        plug:function(method,arg){
            var self = this;
            var _class = {
                shareDestinationObjects:function () {
                    $('#shareRights').dropdown({
                        top:22,
                        position:'right',
                        permission:{
                            type:'ask',
                            dataType:'jsonp'
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
                tip_up_right_black:function (arg) {
                    arg[0].find(".tip_up_right_black").tip({
                        direction:"up",
                        position:"right",
                        skin:"black"
                    });
                }
            };
            return _class[method](arg);
        },
        model:function (method, arg) {
            var _class = {
                addasks:function (arg) {
                    $.djax({
                        url:mk_url('ask/ask/addAsk'),
                        data:arg[0],
                        type:'GET',
                        dataType:'jsonp',
                        success:arg[1]
                    });
                }
            };
            return _class[method](arg);
        }
    };
    $.list_ask=function(options){
        var opts= $.extend({},options);
        return new list_ask(opts);
    }
})(jQuery);
$(function(){
    $.list_ask();
});