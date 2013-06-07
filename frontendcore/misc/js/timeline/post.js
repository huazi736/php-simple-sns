/**
 * Author: wushaojie
 * Date: 2012-7-30
 * Time: 上午11:25
 * 发表框
 */
var Post = {};
Post.initialize = {};
Post.postBox = function(id, content, options) {
    var self = this,
        options = options,
        name = options.name,
        type = options.type;
    this.id = id;
    this.name = name;
    if(options.msg) {
        this.msg = options.msg;
    }

    this.type = type;
    this.data = {};//发送数据
    this.tmpl = new Array();//模板
    this.sendPosts = new Array();//储存自定义发送请求
    this.isError = true;//提交判断有无错误
    this.hasMedia = options.hasMedia;//如果不止文本框，将验证有需要的数据才能提交

    this.$container = $("#" + id);
    this.$content = content;
    this.$button = this.$container.find(".uiButton input");
    this.$selectDate = this.$container.find(".fieldDate");
    this.$textarea = this.$content.find(".textWrap textarea");

    this.repeatReloads = new Array();
    this.repeatReloads[this.name] = new Array();
    this.tmpl[this.name] = this.$content.html();

    //只执行一次初始化操作
    if(!Post.initialize[this.id + "once"]) {
        Post.postBox.plug.calendar.call(this);
        Post.postBox.plug.face.call(this);
        Post.postBox.plug.permission.call(this);
        Post.initialize[this.id + "once"] = true;
    }
    this.plugs = options.plugs;
    this.setBtnDisabled(this.isError);
    for(var i = 0; i < this.plugs.length; i++) {
        var plugName = this.plugs[i],
            obj = Post.media[plugName];
        if($.isPlainObject(obj)) {
            obj.init(this);
            this.repeatReloads[this.name].push(Post.media[plugName].init);
            if(obj.sendPost) {
                this.sendPosts[this.id + this.name] = Post.media[plugName].sendPost;
            }
        } else if($.isFunction(obj)) {
            obj.apply(this);
            this.repeatReloads[this.name].push(Post.media[plugName]);
        }
    }
    this.$button.bind("click", function(event) {
        event.preventDefault();
            self.sendPost();
    });
};
Post.postBox.prototype = {
    getTimestr: function() {
        return this.$selectDate.val();
    },
    getTextareaVal: function() {
        return this.$textarea.val();
    },
    getPermissionVal: function() {
        return this.$container.find('.shareRights input[type="hidden"]').val();
    },
    changeBtn: function(hasMedia) {
        this.hasMedia = hasMedia;
        this.hasMedia && !this.isMaxlength ? this.setBtnDisabled(false) : this.setBtnDisabled(true);
    },
    setBtnDisabled: function(isDisable) {
        this.isError = isDisable;
        if(this.isError && !this.$button.hasClass("disable")) {
            this.$button.parent().removeClass("active").addClass("disable");
        } else if(!this.isError && !this.$button.hasClass("active")) {
            this.$button.parent().removeClass("disable").addClass("active");
        }
    },
    appendDatas: function(obj) {
        for(var k in obj) {
            this.appendData(k, obj[k]);
        }
    },
    appendData: function(key, value) {
        this.data[key] = value;
    },
    clearData: function(key) {
        if(key) {
            delete this.data[key];
        } else {
            for(var k in this.data) {
                delete this.data[k]
            }
        }
        this.isError = true;
        this.$content.html(this.tmpl[this.name]);//copy 副本
        try {
            for(var i = 0, l = this.repeatReloads[this.name].length; i < l; i++) {
                this.repeatReloads[this.name][i].call(this);
            }
        } catch(e) {
            throw new Error("初始化失败")
        }
        $.faceInsert.insert(this.$content.find("textarea"));
    },
    sendPost: function(data) {
        var self = this;
            data = $.extend(self.data, {
            type: self.type,
            content: self.getTextareaVal(),
            permission: self.getPermissionVal(),
            timestr: self.getTimestr()
        });
        if(!this.isError && this.$content.css("display") != "none") {
            if(this.sendPosts[this.id + this.name]) {
                this.sendPosts[this.id + this.name](data);
            } else {
                $.ajax({
                    url: mk_url("main/info/doPost"),
                    type: "POST",
                    dataType: "json",
                    data: data,
                    success: function(response) {
                        if(response.status == 1) {
                            self.render(response);
                            self.clearData();
                        } else {
                            self.error();
                        }
                    }
                })
            }
        } else {
            return false;
        }
    },
    error: function() {
        //todo
    },
    render: function(response) {
        var classTimeline = class_timeline,//info.js 对象//new CLASS_TIMELINE()
            data = response.data,
            time = new Date(data.ctime * 1000);
        if (classTimeline.friend) {
            classTimeline.view(["info"], [classTimeline.box, data, "afterFirst"]);
            classTimeline.plug(['commentEasy'], [classTimeline.box.children().eq(1)]);
            classTimeline.cpu(["lay"], [classTimeline.box]);
        } else {
            this.sideBar(classTimeline, response);
        }
    },
    sideBar:function(classTimeline, response) {
        var data = response.data,
            temp = classTimeline.CLASS_TIMELINE_NAV.addNewYear(response, classTimeline);
        $(window).off("scroll", classTimeline.scrollChangeLoad);
        var $timePsBox;
        var timelineBar = temp.closest(".timelineBar");
        var $current = classTimeline.cpu(["currentShowHide"], [timelineBar, temp]);
        var time = $current.children().attr("time");
        var $box  = null;

        var index, yearLast, year, month, _time;
        _time = new Date(data.ctime*1000);

        year = _time.getFullYear();
        month = _time.getMonth() + 1;
        var title = time + "年";
        var timeYM = year + "-" + parseInt(month);
        var $time_li, $timeYm_li;
        $timeYm_li = classTimeline.timelineTree.find("li[time=" + timeYM + "]");
        $time_li = classTimeline.timelineTree.find("li[time=" + time + "]");
        if ($timeYm_li.size() != 0) {
            $timePsBox = classTimeline.timelineTree.find("li[time=" + timeYM + "]");
            $timePsBox.attr("value", "monthData");
            time = timeYM;

            $box = classTimeline.view([data.type],[$timePsBox,data,true]);
        } else {
            if ($time_li.size() == 0) { // 不存在这个年份  需要创建一个新的标识
                var tempObj = timelineBar.find("a[time=" + time + "]");
                tempObj.parent().removeClass('current');
                tempObj.click();
                $timePsBox = $('li.time[time=' + time + ']');
                classTimeline.event(["removeLoading"], [$timePsBox.children("ul")]);
            } else {
                if (time != timeYM && $time_li.attr("Ymonth") == "true") {
                    $timePsBox = classTimeline.view(["timelinePs1"], {time:timeYM, title:year + "年" + month + "月"});
                    classTimeline.event(["removeLoading"], [$timePsBox.children("ul")]);
                    time = timeYM;
                } else {
                    $timePsBox = classTimeline.timelineTree.find("li[time=" + time + "]");
                }
                $box = classTimeline.view([data.type],[$timePsBox,data,true]);
            }
        }
        if($box){
            classTimeline.cpu(["permissionShow"],[data.permission,$box, data]);
            classTimeline.event(["changeSize"], [$box]);
            classTimeline.plug(["tip_up_right_black", "tip_up_left_black"], [$box]);
            //var a = $box.find("a[name=" + time + "]");  //得到时间轴psTime 锚点坐标
            $("html,body").animate({scrollTop:$box.offset().top - 165}, 200);
            $(window).on("scroll", classTimeline.scrollChangeLoad);
        }
        classTimeline.event(["timelineBoxHover"], [$timePsBox]);
        classTimeline.plug(['commentEasy'], [$timePsBox]);
        classTimeline.cpu(["recodePsTimeTop"], [$timePsBox]);
        classTimeline.cpu(["lay"], [$timePsBox.children("ul.content")]);
    }
};
Post.postBox.plug = {
    calendar: function() {
        var self = this;
        this.$selectDate.calendar({button: false, input: false, yearSelectCallBack: function(elm, year, timestr) {
            self.$selectDate.val(timestr)
        }});
    },
    face: function() {
        this.$container.find(".face").face(this.$content.find("textarea"));
    },
    permission: function() {
        this.$container.find(".shareRights").dropdown({
            top: 22,
            position: "right",
            permission: {
                type: "blog"
            }
        })
    }
};

Post.postBox.handleClick = function(id, content, require) {
    var name = id + require.name;
    if(!Post.initialize[name]) {
        var postBoxObj = new Post.postBox(id, content, require);
        Post.system.addMedia(postBoxObj);
        Post.initialize[name] = true;
    }
    var postBox = Post.system.medias[name];
    postBox.setBtnDisabled(postBox.isError);
    //需要动态改变表情的输入框
    $.faceInsert.insert(content.find("textarea"));
};

Post.postBoxSystem = function() {
    this.medias = {};
    this.tabs = new Array();
};

Post.postBoxSystem.prototype.addMedia = function(media) {
    var name = media.id + media.name;
    if(media && name){
        this.medias[name] = media;
    }
};

Post.system = new Post.postBoxSystem();

Post.tab = function(id) {
    var id = id,
        sys = Post.system,
        tabs = [];

    this.$container = $("#" + id);
    this.$tabs = this.$container.find(".mediaNav .mediaNavItem");
    this.$contents = this.$container.find(".mediaContent .mediaSection");
    tabs.push(new Tabs.tab(this.$container.find(".mediaNav a"), this.$container.find(".mediaContent .mediaSection"), {
        id: id,
        requires: [
            {name: "status", type:"info", isBtnDisabled: true, alwaysShow: true, plugs: ["Status"], msg: "写点什么吧", tmpl: Post.tmpl.status}
        ],
        selected: 0,
        selectedClass: "mediaCurrent",
        isFooterHide: false
    }));

    tabs.push(new Tabs.tab(this.$container.find(".mediaItemNav a"), this.$container.find(".js-tabs .mediaItem"), {
        id: id,
        requires: [
            {name: "uploadPhoto", isBtnDisabled: true, hasMedia: false, plugs: ["textArea", "uploadPhoto"], msg: "给这张照片做些说明吧！"},
            {name: "camera", isBtnDisabled: true, hasMedia: false, plugs: ["textArea", "cameraPhoto"], msg: "给这张照片做些说明吧！"},
            {name: "shareVideo", isBtnDisabled: true, hasMedia: false, plugs: ["textArea", "shareVideo"], msg: "描述：（专区连接描述，点击可修改）"},
            {name: "uploadVideo", isBtnDisabled: true, hasMedia: false, plugs: ["textArea", "uploadVideo"], msg: "给这部视频做些说明吧！"},
            {name: "makeVideo", isBtnDisabled: true, hasMedia: false, plugs: ["textArea", "makeVideo"], msg: "给这部视频做些说明吧！"}
        ],
        isFooterHide: true
    }));
    sys.tabs[id] = tabs;
};

Post.tmpl = {
    status: function(data) {

    }
};

/* 上传图片 */
Post.media = {
    Status: function() {
        var self = this;
        new Textarea.msgTip(this.$content.find(".textWrap"), {
            maxlength: 140,
            notMedia: true,
            textareaProps: {
                "class": "shareInfoCont msg",
                "msg": self.msg
            },
            textareaCallback: function(isDisabled) {
                self.setBtnDisabled(isDisabled);
            },
            textareaStyles:{
                overflow: "hidden",
                height: 35
            },
            button:{
                id: this.$button.parent()
            }
        })
    },
    textArea: function() {
        var self = this;
        new Textarea.msgTip(this.$content.find(".textWrap"), {
            maxlength: 140,
            notMedia: false,
            textareaProps: {
                "class": "shareInfoCont msg",
                "msg": self.msg
            },
            textareaCallback: function(isDisabled) {
                self.isMaxlength = isDisabled;//为空为真
                if(!self.hasMedia)
                    isDisabled = true;
                self.setBtnDisabled(isDisabled);
            },
            textareaStyles:{
                overflow: "hidden",
                height: 35
            },
            button:{
                id: this.$button.parent()
            }
        })
    },
    /* 上传照片 */
    uploadPhoto: function() {
        var self = this,
            container = this.$content,
            miscpath = CONFIG['misc_path'],
            flashUrl = miscpath + "flash/plug-flash/jQuery-uploadify/uploadify.swf",

            uploadBox = container.find(".uploadBox"),
            uploadifyInput = container.find(".flashUpload input"),
            uploadifyId = uploadifyInput.attr("id"),
            fileQueueId = container.find(".fileQueue").attr("id"),
            cancel = container.find(".controls .cancel"),//取消上传按钮

            fids = [],
            type = uploadifyInput.attr("data-upload-type"),
            flashUploadUid = uploadifyInput.attr("data-upload-uid"),

            cancelUpload = function() {//取消上传
                self.changeBtn(false);
                uploadBox.removeClass("uploading").removeClass("success");
            };
        $("#" + uploadifyId).uploadify({
            uploader: flashUrl,
            script: mk_url("album/api/upload"),
            method:'GET',
            scriptData:{
                flashUploadUid: flashUploadUid,
                type: type
            },
            cancelImg: miscpath + "img/system/icon_close_03.png",
            buttonImg: miscpath + "img/system/icon_selectImg.png",
            folder: miscpath + "temp",
            fileExt: "*.jpg;*.jpeg;*.gif;*.png",
            fileDesc: "*.jpg;*.jpeg;*.gif;*.png图片w格式",
            width: 67,
            height: 24,
            queueID: fileQueueId,
            multi: false,
            auto: true,
            queueSizeLimit: 100,
            fileDataName: 'uploadPhotoFile',
            sizeLimit: 1024 * 1024 * 10,
            expressInstall: miscpath + "flash/expressInstall.swf",
            allowScriptAccess : "always",
            onOpen:function() {
                uploadBox.addClass("uploading");
            },
            onComplete:function(e, queueID, fileObj, response, data) {
                var res = $.parseJSON(response),
                    data = res.data;
                if(res.status == 1) {
                    fids.push(data.photo_id);
                    uploadBox.removeClass("uploading").addClass("success");
                    self.changeBtn(true);
                }
                cancel.click(function(event) {
                    event.preventDefault();
                    cancelUpload();
                });
            },
            onAllComplete: function() {
                var fid = fids.join(",");//处理多张图片
                $.ajax({
                    url: mk_url('album/api/uploadSavePhoto', {}),
                    data: {
                        pids: fid,
                        type: type,
                        flashUploadUid: flashUploadUid
                    },
                    dataType: "jsonp",
                    success: function(response) {
                        var status = response.status,
                            data = response.data;
                        if(!data.type) {
                            data.type = "photo";
                        }
                        if(status == 1) {
                            for(var k in data)
                            self.appendData(k, data[k]);
                        }
                    }
                });
                fids.length = 0;//清除fids
            },
            onCancel:function() {
                cancelUpload();
            },
            onError:function(e, qid, fo, eo) {
                self.changeBtn(false);
            }
        });
    },

    /* 拍照 */
    cameraPhoto: {
        init: function(postBox) {
            var self = this;
            this.postBox = postBox; //postBox 对象
            var miscpath = CONFIG['misc_path'],
                swf = miscpath + "flash/photograph.swf";
            window.photo = function(data) {
                self.sendPost(data);
            };
            cameraPhoto = flash.createSwf({
                appendTo: this.postBox.$content.find(".mediaFlash")[0],
                wmode: "opaque",
                width: 387,
                height: 270,
                align: "middle",
                flashvars: "obj=cameraPhoto",
                movie: swf,
                ondisable: function(hasMedia){
                    self.postBox.changeBtn(!hasMedia);
                    cameraPhoto.thisMovie('photograph').save(mk_url("album/api/camera"), CONFIG['u_id'])
                }
            });
        },
        sendPost: function(data) {
        }
    },
    /* 视频 */
    shareVideo: {
        init: function(postBox) {
            var self = this;
            this.postBox = postBox;
            this.$content = this.postBox.$content;
            this.$textarea = this.$content.$textarea;
            this.$input = this.$content.find(".form-field .text");

            this.helper = new inputHelper(this.$input, {
                useMethodName: "setDefaultValue", defaultValue: "请将连接复制到此处"
            });
            this.$input.click(function(event) {
                if(self.helper.validateValue()) {
                    $.ajax({
                        url: mk_url("video/videoapi/video_share_link"),
                        method: "GET",
                        data: {
                            "url": encodeURIComponent(oThis.inputHelper.getValue())
                        },
                        dataType: "jsonp",
                        success: function(response) {
                            var data = response.data;
                            var html = '<img src="' + data.img + '" width="128" height="80"><a class="showFlash" href="javascript:void(0);"><img alt="" src="/img/system/feedvideoplay.gif"></a>';
                            self.$content.find(".form-field").hide();
                            self.$content.find(".shareData").show().find(".media_prev").html(html);
                            self.$textarea.text(data.title);
                            self.$textarea.focus();
                            self.$textarea.blur();
                            self.postBox.appendDatas({
                                videourl: data.swf,
                                imgurl: data.img,
                                url: data.url
                            });
                        }
                    });
                }
                return false;
            });
        },
        sendPost: function() {

        }
    },
    uploadVideo: {
        init: function(postBox) {
            var self = this;
            this.postBox = postBox; //postBox 对象
            var uploadBox = this.postBox.$content.find(".uploadBox"),
                miscpath = CONFIG['misc_path'],
                swf = miscpath + "flash/upload.swf",
                url = uploadBox.attr("data-upload-url") + "?appkey=" + uploadBox.attr("data-upload-appkeys") + "&mid=1",
                cancelUpload = function() {//取消上传
                    self.postBox.changeBtn(false);
                };
            videoUpload.AC_FL_RunContent({
                appendTo: uploadBox[0],
                url: url,
                width: 387,
                height: 60,
                types: '*.rm;*.rmvb;*.flv;*.3gp;*.mp4;*.dv',
                size: "102400",
                allowScriptAccess: "always",
                movie: swf,
                wmode: 'opaque',
                onInit:function(list) {
                },
                onComplete:function(data) {
                    var data = $.parseJSON(data);
                    if(data.status == 1) {
                        videoUpload.thisMovie('flashvideoupload').isJsComplete(true);
                        self.postBox.appendData("vid", data.data);
                        self.postBox.changeBtn(true);
                    }else{
                        self.postBox.changeBtn(false);
                        videoUpload.thisMovie('flashvideoupload').isJsComplete(false);
                    }
                },
                onWarn:function(error) {
                    $(this).popUp({
                        width:450,
                        title:'提示!',
                        content: '<div style="padding:10px">'+ error + '</div>',
                        buttons:'<label class="uiButton uiButtonConfirm"><input type="button" value="确定" class="closeBtn" /></label>',
                        mask:true,
                        maskMode:true,
                        callback:function(){
                            setTimeout(cancelUpload,200);
                        }
                    });
                },
                onAgain : function(){
                    self.postBox.changeBtn(false);
                    videoUpload.thisMovie('flashvideoupload').isJsOnAgain(true);
                }
            });
        },
        videoManger: function(response) {
            var self = this;
            if (response) {
                var status = response.status;
                var result = response.data;
                switch (parseInt(status)) {
                    case 1:
                    {
                        for(var k in result) {
                            self.appendData(k, result[k]);
                        }
                        self.postBox.sendPost();
                    }
                        break;
                    case 2:
                    {
                        $(this).popUp({
                            width:450,
                            title:'提示!',
                            content:'<div style="padding:10px">' + response.info + '</div>',
                            buttons:'<label class="uiButton uiButtonConfirm"><input type="button" value="确定" class="callbackBtn" /></label>',
                            mask:true,
                            maskMode:true,
                            callback:function() {
                                location.reload();
                            }
                        });
                    }
                        break;
                    case 3:
                    {
                        $(this).popUp({
                            width:450,
                            title: '提示！',
                            content: '<div style="padding:10px">' + response.info + '</div>',
                            buttons:'<label class="uiButton uiButtonConfirm"><input type="button" value="确定" class="callbackBtn" /></label>',
                            mask: true,
                            maskMode: true,
                            callback: function() {
                                location.reload();
                            }
                        });
                    }
                        break;
                }
            }
        },
        sendPost: function(data) {
            var self = this;
            $.ajax({
                url: mk_url("video/videoapi/add_video", {}),
                dataType: "jsonp",
                type: "GET",
                data: data,
                success:function(data) {
                    Post.media.uploadVideo.videoManger(data);
                },
                error:function() {
                    alert("网络错误!")
                }
            });
        }
    },
    makeVideo: function() {
        var self = this,
            mediaFlash = self.$content.find(".mediaFlash"),
            miscpath = CONFIG['misc_path'],
            swf = miscpath + "flash/record.swf";
        videoRecord = flash.createSwf({
            appendTo: mediaFlash[0],
            wmode: "opaque",
            id: "record",
            name: "record",
            width: 387,
            height: 270,
            align: "middle",
            flashvars: "obj=videoRecord&url=" + mediaFlash.attr("data-record-url") + "&uid=" + mediaFlash.attr("data-record-name"),
            obj: "videoRecord",
            movie: swf,
            ondisable: function(hasMedia){
                self.changeBtn(!hasMedia);
            }
        });
    }
};

$(".mediaBox").each(function() {
    var mediabox = $(this);
    var id = mediabox.attr("id");
    new Post.tab(id);
});