
/*
	Create 2012-4-22
	@ author duxianwei
	@ name 群组首页发表框
	desc init初始化-> event 事件驱动-> model 调用远端数据 -> view 渲染呈现 -> plug 绑定插件
*/
var group = {},
    photoDatas = [];
    var miscpath = CONFIG['misc_path'];
group.view = function(name,arg){
	var self = this;
    var $timelineContent = $("#timelineContent");
    var $hd_UID = $("#hd_UID").val();
    var $hd_userName = $("#hd_userName").val();
    var $hd_avatar = $("#hd_avatar").val();
    var $hd_userPageUrl = $("#hd_userPageUrl").val();
	this.view._class={
		createBtnAble:function(bool) {
	        if(!$('#distributeButton')[0]) return;
	        if (bool) {
	            $('#distributeButton').prop("disabled", false);
	            $('#distributeButton').parent().removeClass('disable');
	        } else {
	            $('#distributeButton').removeAttr("disabled");
	            $('#distributeButton').parent().addClass('disable');
	        }
	    },
        all:function(arg){
            var data = arg.data;
            for( var i in data){
                if (data[i].type == "info") {
                    var strInfo = '';
                    strInfo += '<div class="timelineBox">' +
                            '<div class="headBlock clearfix">' + 
                                '<a class="headImg" href="'+data[i].home_url+'" target="_blank">' +
                                    '<img alt="" src="'+data[i].avatar+'">' +
                                '</a>' + 
                                '<div class="unitHeader">' + 
                                    '<div class="AuthorName"><a href="'+data[i].home_url+'" target="_blank">'+data[i].uname+'</a></div>' + 
                                    '<div class="postTime"><a href="javascript:void(0)">'+data[i].title+'</a></div>' + 
                                '</div>' +
                            '</div>' +
                            '<div class="infoContent">' + data[i].content+'</div>' +
                            '<div class="commentBox pd" action_uid="'+$hd_UID+'" ctime="'+data[i].ctime+'" pagetype="group" msgname="'+data[i].title+'" commentobjid="'+data[i].id+'" complete="true">' +
                        '</div>';
                    $timelineContent.append(strInfo);
                    $commentEasy = $(".timelineBox").find(".commentBox");
                    self.plug(['commentEasy'], $commentEasy);
                }
                else if (data[i].type == "album") {
                    var picurls = [];
                    picurls = data[i].picurl.split(",");
                    var strAlbum = '';
                    strAlbum = '<div class="timelineBox">' +
                                    '<div class="headBlock clearfix">' + 
                                        '<a class="headImg" href="'+data[i].home_url+'" target="_blank">' +
                                            '<img alt="" src="'+data[i].avatar+'">' +
                                        '</a>' + 
                                        '<div class="unitHeader">' + 
                                            '<div class="AuthorName"><a href="'+data[i].home_url+'" target="_blank">'+data[i].uname+'</a></div>' + 
                                            '<div class="postTime"><a href="javascript:void(0)">'+data[i].title+'</a></div>' + 
                                        '</div>' +
                                    '</div>' +
                                    '<div class="infoContent">'+data[i].content+'</div>'+
                                    '<ul class="photoContent clearfix">';
                    for (var j = 0; j < picurls.length; j++) {
                        if(j === 0){
                            strAlbum += '<li class="firstPhoto">'
                        }
                        if(j>=1 && j<=3){
                            strAlbum += '<li class="otherPhoto">'
                        }
                        if(j>3){
                            strAlbum += '<li class="otherPhoto hide">'
                        }
                        var url = 'http://dev.duankou.com/album/index.php?c=index&m=photoInfo&photoid=28953&action_dkcode='+data[i].dkcode;
                        strAlbum += '<a class="photoLink1" url="'+url+'" pid="28953" action_dkcode="100521" href="javascript:void(0);">' +
                                    '<img  alt="" src="'+picurls[j]+'">' +
                                '</a>' +
                            '</li>';
                    };
                    strAlbum += '</ul><div class="commentBox pd" action_uid="'+$hd_UID+'" ctime="'+data[i].ctime+'" pagetype="group" msgname="'+data[i].title+'" commentobjid="'+data[i].id+'" complete="true"></div>';
                    $timelineContent.append(strAlbum);
                    strAlbum = '';
                    $commentEasy = $(".timelineBox").find(".commentBox");
                    self.plug(['commentEasy'], $commentEasy);
                }
                else if (data[i].type == "video") {
                    var strVideo = '';
                    strVideo += '<div class="timelineBox">' +
                                '<div class="headBlock clearfix">' + 
                                    '<a class="headImg" href="'+data[i].home_url+'" target="_blank">' +
                                        '<img alt="" src="'+data[i].avatar+'">' +
                                    '</a>' + 
                                    '<div class="unitHeader">' + 
                                        '<div class="AuthorName"><a href="'+data[i].home_url+'" target="_blank">'+data[i].uname+'</a></div>' + 
                                        '<div class="postTime"><a href="javascript:void(0)">'+data[i].title+'</a></div>' + 
                                    '</div>' +
                                '</div>' +
                                '<div class="infoContent">'+data[i].content+'</div>' + '<div class="mediaContent" style><div class="media_prev">';
                                var showFlashImgT, showFlashImgL;
                                strVideo += '<img src="' + video_pic_domain + data[i].purl + '" width=403 height=300 alt="" />';
                                showFlashImgT = "125px";
                                showFlashImgL = "184px";
                                strVideo += '<a class="showFlash" href="javascript:void(0);"><img alt="" src="' + miscpath+"img/system/feedvideoplay.gif" + '" style="top:' + showFlashImgT + ';left:' + showFlashImgL + ';"></a></div>' +
                                '<div class="media_disp hide" videosrc="' + data[i].vurl + '"><div id="' + data[i].vurl + data[i].id + '"></div></div></div>' +
                                '<div class="commentBox pd" action_uid="'+$hd_UID+'" ctime="'+data[i].ctime+'" pagetype="group" msgname="'+data[i].title+'" commentobjid="'+data[i].id+'" complete="true">' +
                                '</div>';
                    $timelineContent.append(strVideo);
                    $commentEasy = $(".timelineBox").find(".commentBox");
                    self.plug(['commentEasy'], $commentEasy);
                }
                else{
                    var strAsk = '';
                        strAsk += '<div class="timelineBox" askid="'+data[i].ask_id+'">' +
                                    '<div class="headBlock clearfix">' + 
                                        '<a class="headImg" href="'+data[i].home_url+'" target="_blank">' +
                                            '<img alt="" src="'+data[i].avatar+'">' +
                                        '</a>' + 
                                        '<div class="unitHeader">' + 
                                            '<div class="AuthorName"><a href="'+data[i].home_url+'" target="_blank">'+data[i].uname+'</a></div>' + 
                                            '<div class="postTime"><a href="javascript:void(0)">'+data[i].title+'</a></div>' + 
                                        '</div>' +
                                    '</div>' +
                                    '<div class="infoContent"></div>';
                        $timelineContent.append(strAsk);
                        $(".timelineBox[askid="+data[i].ask_id+"]").find('.infoContent').showAsk(data[i].ask);
                }
            }
        },
	    list:function(arg){
            var data = arg.data;
            var str = '';
            for (var i = 0; i < data.length; i++) {
                str += '<div class="timelineBox">' +
                            '<div class="headBlock clearfix">' + 
                                '<a class="headImg" href="'+data[i].home_url+'" target="_blank">' +
                                    '<img alt="" src="'+data[i].avatar+'">' +
                                '</a>' + 
                                '<div class="unitHeader">' + 
                                    '<div class="AuthorName"><a href="'+data[i].home_url+'" target="_blank">'+data[i].uname+'</a></div>' + 
                                    '<div class="postTime"><a href="javascript:void(0)">'+data[i].title+'</a></div>' + 
                                '</div>' +
                            '</div>' +
                            '<div class="infoContent">'+data[i].content+'</div>' +
                            '<div class="commentBox pd" action_uid="'+$hd_UID+'" ctime="'+data[i].ctime+'" pagetype="group" msgname="'+data[i].title+'" commentobjid="'+data[i].id+'" complete="true">' +
                            '</div>';
            };
	    	$("#distributeInfoBody").parent().after(str);
            $commentEasy = $(".timelineBox").find(".commentBox");
            self.plug(['commentEasy'], $commentEasy);
	    },
        photo:function(arg){
            var data = arg.data;
            var str = '';
            var picurls = [];
            picurls = data[0].picurl.split(",");
            str = '<div class="timelineBox">' +
                            '<div class="headBlock clearfix">' + 
                                '<a class="headImg" href="'+data[0].home_url+'" target="_blank">' +
                                    '<img alt="" src="'+data[0].avatar+'">' +
                                '</a>' + 
                                '<div class="unitHeader">' + 
                                    '<div class="AuthorName"><a href="'+data[0].home_url+'" target="_blank">'+data[0].uname+'</a></div>' + 
                                    '<div class="postTime"><a href="javascript:void(0)">'+data[0].title+'</a></div>' + 
                                '</div>' +
                            '</div>' +
                            '<div class="infoContent">'+data[0].content+'</div>'+
                            '<ul class="photoContent clearfix">';
            for (var i = 0; i < picurls.length; i++) {
                if(i === 0){
                    str += '<li class="firstPhoto">'
                }
                if(i>=1 && i<=3){
                    str += '<li class="otherPhoto">'
                }
                if(i>3){
                    str += '<li class="otherPhoto hide">'
                }
                str += '<a class="photoLink1" url="http://dev.duankou.com/album/index.php?c=index&m=photoInfo&photoid=28949&action_dkcode=100521" pid="28953" action_dkcode="100521" href="javascript:void(0);">' +
                            '<img style="max-width:838px;_width:expression(this.width&gt;=838?838:auto)" alt="" src="'+picurls[i]+'">' +
                        '</a>' +
                    '</li>';
            };
            str += '</ul><div class="commentBox pd" action_uid="'+$hd_UID+'" ctime="'+data[0].ctime+'" pagetype="group" msgname="'+data[0].title+'" commentobjid="'+data[0].id+'" complete="true"></div>';
            
            $("#distributeInfoBody").parent().after(str);
            $commentEasy = $(".timelineBox").find(".commentBox");
            self.plug(['commentEasy'], $commentEasy);
        },
        video:function(arg){
            var data = arg.data;
            var str = '';
                str += '<div class="timelineBox">' +
                            '<div class="headBlock clearfix">' + 
                                '<a class="headImg" href="'+data[0].home_url+'" target="_blank">' +
                                    '<img alt="" src="'+data[0].avatar+'">' +
                                '</a>' + 
                                '<div class="unitHeader">' + 
                                    '<div class="AuthorName"><a href="'+data[0].home_url+'" target="_blank">'+data[0].uname+'</a></div>' + 
                                    '<div class="postTime"><a href="javascript:void(0)">'+data[0].title+'</a></div>' + 
                                '</div>' +
                            '</div>' +
                            '<div class="infoContent">'+data[0].content+'</div>' + '<div class="mediaContent" style><div class="media_prev">';
                            var showFlashImgT, showFlashImgL;
                            str += '<img src="' + video_pic_domain + data[0].purl + '" width=403 height=300 alt="" />';
                            showFlashImgT = "125px";
                            showFlashImgL = "184px";
                            str += '<a class="showFlash" href="javascript:void(0);"><img alt="" src="' + miscpath +"img/system/feedvideoplay.gif" + '" style="top:' + showFlashImgT + ';left:' + showFlashImgL + ';"></a></div>' +
                                '<div class="media_disp hide" videosrc="' + data[0].vurl + '"><div id="' + data[0].vurl + data[0].id + '"></div></div></div>' +
                            '<div class="commentBox pd" action_uid="'+$hd_UID+'" ctime="'+data[0].ctime+'" pagetype="group" msgname="'+data[0].title+'" commentobjid="'+data[0].id+'" complete="true">' +
                            '</div>';
            
            $("#distributeInfoBody").parent().after(str);
            $commentEasy = $(".timelineBox").find(".commentBox");
            self.plug(['commentEasy'], $commentEasy);
        },
        ask:function(arg){
            var data = arg[0];
            var str = '';
            str += '<div class="timelineBox" askid="'+data.ask_id+'">' +
            '<div class="headBlock clearfix">' + 
                '<a class="headImg" href="'+data.home_url+'" target="_blank">' +
                    '<img alt="" src="'+data.avatar+'">' +
                '</a>' + 
                '<div class="unitHeader">' + 
                    '<div class="AuthorName"><a href="'+data.home_url+'" target="_blank">'+data.uname+'</a></div>' + 
                    '<div class="postTime"><a href="javascript:void(0)">'+data.title+'</a></div>' + 
                '</div>' +
            '</div>' +
            '<div class="infoContent"></div>';
            $("#distributeInfoBody").parent().after(str);
            $(".timelineBox[askid="+data.ask_id+"]").find('.infoContent').showAsk(data.ask);
        }
	}
	return this.view._class[name](arg)
}
group.event = function(name,arg){
	var self = this;
	this.event._class={
		status:function(arg) {
            $('#distributeMsg').find('textarea.shareInfoCont').focus(function() {
                self.distributeInfoBodyFooter.show();
            });
        },
        photo:function() {
            $('#upoadPhotoFromLocal').click(function() {
                $('#photoUploadWay').hide();
                $('#photoFileOption').show();
                $('#uploadPhotoPanel').show();
                $('#attachPhotoIntroduce').show();
                self.plug(["msg"], [$("#photoFileOption")]);
                $("#distributeButton").parent().attr("data", "true");
                if (!self.photoData) {
                    self.view("createBtnAble",false);
                    $("#distributeButton").parent().attr("data", "false");
                } else if($("#attachPhotoIntroduce").val().length > $("#attachPhotoIntroduce").attr("tmaxlength")) {
                    self.view("createBtnAble",false);
                } else {
                    self.view("createBtnAble",true);
                    $("#distributeButton").parent().attr("data", "true");
                }
                self.distributeInfoBodyFooter.show();
            });
            var updataPic_URL = mk_url('album/api/publicUploadCrossPhoto');
            var flashUrl = miscpath+'flash/plug-flash/jQuery-uploadify/uploadify.swf';
            var type = $("#photoFileOption [name='type']").val();
            $("#uploadPhotoButton").uploadify({
                "uploader":flashUrl,
                "script":updataPic_URL,
                "method":'GET',
                "scriptData":{
                    'c':'index',
                    'm':'publicUploadPhoto',
                    "flashUploadUid":$(".group_index_c").attr("uid"),
                    'type':type,
                    'from':'group'
                },
                "cancelImg":miscpath +"img/system/icon_close_03.png",
                "buttonImg":miscpath +"img/system/icon_selectImg.png",
                "folder":miscpath +"temp",
                "fileExt":"*.jpg;*.jpeg;*.gif;*.png;*.bmp",
                "fileDesc":"*.jpg;*.jpeg;*.gif;*.png;*.bmp图片格式",
                "width":67,
                "height":24,
                "queueID":"photo_queueID",
                "multi":false,
                "auto":true,
                "queueSizeLimit":100,
                "fileDataName":'Filedata',
                'sizeLimit':1024 * 1024 * 4,
                'expressInstall':miscpath +"flash/expressInstall.swf",
                'scriptAccess':'always',
                "onOpen":function() {
                    $("#flashuploaduid object").height(0).css("border", "none");//隐藏选择图片按钮
                    $("#flashuploaduid").height(0).css({"border":"none", "padding":"0px"});
                    $("#flashuploaduid div").hide();
                },
                "onComplete":function(e, queueID, fileObj, response, data) {
                    var data = eval('(' + response + ')');
                    self.photoData = data;
                    photoDatas.push(data.data.img_url.img_tm);
                    $('#distributeButton').parent().attr("data", "true");
                    if (data.status == 1) {
                        if($("#attachPhotoIntroduce").val() !== "" && ($("#attachPhotoIntroduce").val().length > $("#attachPhotoIntroduce").attr("tmaxlength"))) {
                                self.view("createBtnAble",false);
                            } else {
                                self.view("createBtnAble",true);
                            }
                        $("#up_photo_success").show();
                    } else {
                        $(this).subPopUp({
                            width:557,
                            title:"上传图片时发生错误",
                            content:'<div style="padding:15px; line-height:200%;"><strong>上传图片时发生错误，可能是由图片格式不正确或大小超过4M或服务器错误引起。</strong><br /><br /><span>端口支持的图片格式为:</span><ul style="list-style:inside circle;"><li>*.jpg</li><li>*.jpeg</li><li>*.gif</li><li>*.png</li></ul></div>',
                            mask:true,
                            maskMode:false,
                            buttons:'<span class="popBtns closeBtn" id="uploadPic_cancel">确定</span>'
                        });
                        $("#flashuploaduid div").show()
                        $("#flashuploaduid object").height(25);//隐藏选择图片按钮
                        $("#flashuploaduid").height(50).css({"border":"none", "padding":"3px 10px"});
                        $("#distributeButton").parent().attr("data", "false");
                    }
                },
                "onCancel":function() {
                    $("#flashuploaduid div").show();
                    $("#flashuploaduid object").height(25);//隐藏选择图片按钮
                    $("#flashuploaduid").height(50).css({"border":"none", "padding":"3px 10px"});
                },
                "onError":function(e, qid, fo, eo) {
                    if (eo.type == "File Size") {
                        $(this).popUp({
                            width:450,
                            title:'提示!',
                            content:'<div style="padding:10px">上传图片大小不能超过4M !</div>',
                            buttons:'<span class="popBtns blueBtn closeBtn">确定</span>',
                            mask:true,
                            maskMode:true
                        });
                        $("#photo_queueID").html('');
                    }
                }
            });
            //摄像头拍摄照片
            $('#snapshotPhoto').click(function() {
                $('#photoUploadWay').hide();
                $('#attachCameraPhotoIntroduce').show();
                $('#snapshotPhotoFileOption').show();
                self.plug(["msg"], [$("#snapshotPhotoFileOption")]);
                $('#photoFileOption').hide();
                self.distributeInfoBodyFooter.show();
                $("#distributeButton").parent().attr("data", "false");
                self.view("createBtnAble",false);
            });
        },
        video:function(arg) {
			function cancelUpload(){							
				$(".flashContent").show();
				$("#uploadTips").show();
				$('#videoDescriptions').hide();
				$("#up_success").hide();
				$("#uploadState").hide();
				$(".flashContent div").show();
				$(".flashContent object").height("25px");
				$(".flashContent .txt_url").show().val("");
				$(".flashContent").height(65).css({"border":"1px solid #CCCCCC", "padding":"3px 10px"});
				if ($(".uploadifyQueueItem").first().length)
					$("#uploadify").uploadifyCancel($(".uploadifyQueueItem").first().attr("id").replace("uploadify", ""));
				$("#videoId").val("");
				$("#saveVideoInfomation").attr("disabled", true).addClass("disabled");
				$('#distributeButton').parent().attr("data", "false")
				self.view("createBtnAble",false);
			}
            /** 上传视频 **/
            var miscpath = CONFIG['misc_path'];
            /** 上传视频 **/
            try{
                videoUpload.AC_FL_RunContent({
                    'appendTo' : document.getElementById("uploadify"),//flash添加到页面的容器
                    'url' : $("#hd_video_upload_url").val()+'?appkey='+$("#hd_url").val()+'&mid=3',//上传到的url
                    'types' : '*.rm;*.rmvb;*.flv;*.3gp;*.mp4;*.dv',//可用的视频格式
                    'size' : "512000",//限制上传大小，单位是kb
                    'width' : 490,
                    'height' : 60,
                    'allowScriptAccess' : "always",
                    'movie' : CONFIG['misc_path']+"flash/upload.swf",//该swf的地址
                    'wmode' : 'opaque',  //默认window
                    'onInit':function(list) {
                        //$(".flashContent").hide();
                        $(".uploadifyQueueItem .cancel").html('<label class="uiButton uiButtonConfirm"><input class="closeBtn" id="cancelBtn" type="button" value="取消"></lable>');
                        $("#cancelBtn").click(function() {
                            $(this).popUp({
                                width:450,
                                title:'取消上传!',
                                content:'<div style="padding:10px">您确定你想取消视频上传么?</div>',
                                buttons:'<span class="popBtns blueBtn callbackBtn">取消上传</span><span class="popBtns blueBtn closeBtn">请勿取消</span>',
                                mask:true,
                                maskMode:true,
                                callback:function() {
                                    $.closePopUp();
                                    $(".flashContent").show();
                                    $("#uploadTips").show();
                                    $('#videoDescriptions').hide();
                                    $("#up_success").hide();
                                    $("#uploadState").hide();
                                    $(".flashContent div").show();
                                    $(".flashContent object").height("25px");
                                    $(".flashContent .txt_url").show().val("");
                                    $(".flashContent").height(65).css({"border":"1px solid #CCCCCC", "padding":"3px 10px"});
                                    if ($(".uploadifyQueueItem").first().length)
                                        $("#uploadify").uploadifyCancel($(".uploadifyQueueItem").first().attr("id").replace("uploadify", ""));
                                    $("#videoId").val("");
                                    $("#saveVideoInfomation").attr("disabled", true).addClass("disabled");
                                    $('#distributeButton').parent().attr("data", "false")
                                    self.view("createBtnAble",false);
                                }
                            });
                        });
                    },
                    "onComplete":function(data) {
                        var str = eval('(' + data + ')');
                        if(str.status == 1){
                            $("#videoId").val(str.data);
                            if($("#attachVideoIntroduce").val() !== "" && ($("#attachVideoIntroduce").val().length > $("#attachVideoIntroduce").attr("tmaxlength"))) {
                                self.view("createBtnAble",false);
                            } 
                            else {
                                self.view("createBtnAble",true);
                            }
                            $('#distributeButton').parent().attr("data", "true");
                            videoUpload.thisMovie('flashvideoupload').isJsComplete(true);//陈功后与flash交互
                        }else{
                            videoUpload.thisMovie('flashvideoupload').isJsComplete(false);//失败后与flash交互
                        }
                    },
                    "onCancel":function() {
                        $(".flashContent object").height(25);//隐藏选择图片按钮
                        $(".flashContent").height(50).css({"border":"1px solid #CCCCCC", "padding":"3px 10px"});
                        $(".flashContent div").show();
                    },
                    "onWarn":function(error) {
                        $(this).popUp({
                            width:450,
                            title:'提示!',
                            content: '<div style="padding:10px">'+ error + '</div>',
                            buttons:'<label class="uiButton uiButtonConfirm"><input type="button" value="确定" class="closeBtn" /></label>',
                            mask:true,
                            maskMode:true,
                            callback:function(){setTimeout(cancelUpload,200);}
                        });
                    },
                    'onAgain' : function(){
                        /*
                         var uploadagain=confirm("确认重新上传吗吗？");
                         if(uploadagain==true){
                         thisMovie('flashvideoupload').isJsOnAgain(true);
                         }else{
                         thisMovie('flashvideoupload').isJsOnAgain(false);
                         }
                         */
                        videoUpload.thisMovie('flashvideoupload').isJsOnAgain(true);
                    }
                });
            }catch(e){

            }
            $('#upoadVideoFromLocal').click(function() {
                $('#videoUploadWay').hide();
                $('#recordVideoPanel').hide();
                $('#uploadVideoFlashWrap').show();
                $('#attachVideoIntroduce').show();
                $('#videoFileOption').show();
                $("#noCam").hide();
                self.plug(["msg"], [$("#videoFileOption")]);
                self.distributeInfoBodyFooter.show();
                $("#distributeButton").parent().attr("data", "true");
                if ($("#videoId").val() == "") {
                    self.view("createBtnAble",false);
                    $("#distributeButton").parent().attr("data", "false");
                } else if($("#attachVideoIntroduce").val().length > $("#attachVideoIntroduce").attr("tmaxlength")) {
                    self.view("createBtnAble",false);
                } else {
                    self.view("createBtnAble",true);
                }
            });

            //摄像头拍摄照片
            $('#recordVideo').click(function() {
                $('#videoUploadWay').hide();
                $('#uploadVideoFlashWrap').hide();
                $('#recordVideoPanel').show();
                $('#attachVideoIntroduce').show();
                $('#videoFileOption').show();
                self.plug(["msg"], [$("#videoFileOption")]);
                self.distributeInfoBodyFooter.show();
                $("#distributeButton").parent().attr("data", "false");
                self.view("createBtnAble",false);
            });
        },
		button:function(arg) {
            function resetVideoInput() {
                $("#attachVideoIntroduce").val("");
                $("#videoId").val("");
                $("#hd_info").val("");
                $("#up_success").hide();
                $(".flashContent").show();
            }
            function videoManger(result) {
                if (result) {
                    switch (parseInt(result.status)) {
                        case 1: {
                            var $gid = $("#group_id").val();
                            var data = {type:'video',content:$("#attachVideoIntroduce").val(), timestr:007, gid:$gid, purl:result.data.pic, vurl:result.data.vid};
                            self.model(["video"], [data, function(data) {
                                if (data.status) {
                                    self.view("video",data);
                                    $(".flashContent div").show();
                                    $(".flashContent object").height(25);//隐藏选择图片按钮
                                    $(".flashContent").height(50).css({"border":"1px solid #CCCCCC", "padding":"3px 10px"});
                                    $.closePopUp();
                                    $(this).popUp({
                                        width:450,
                                        title:'提示!',
                                        content:'<div style="padding:10px">'+result.info+'</div>',
                                        buttons:'<label class="uiButton uiButtonConfirm"><input type="button" value="确定" class="callbackBtn" /></label>',
                                        mask:true,
                                        maskMode:true,
                                        callback:function() {
                                            $.closePopUp();
                                            self.view("createBtnAble",false);
                                        }
                                    });
                                } 
                                else {
                                    $.closePopUp();
                                    $(this).popUp({
                                        width:450,
                                        title:'提示!',
                                        content:'<div style="padding:10px">发表失败</div>',
                                        buttons:'<label class="uiButton uiButtonConfirm"><input type="button" value="确定" class="callbackBtn" /></label>',
                                        mask:true,
                                        maskMode:true,
                                        callback:function() {
                                            $.closePopUp();
                                        }
                                    });
                                }
                                $(".flashContent div").show()
                                $(".flashContent object").height(25);//隐藏选择图片按钮
                                $(".flashContent").height(50).css({"border":"1px solid #CCCCCC", "padding":"3px 10px"});
                                var liNow = $("#timelineTree").children("li.time");

                                var time = new Date(data.data.ctime * 1000);
                                resetVideoInput();
                            }]);
                        }
                            ;
                            break;
                        case -1:
                        {
                            alert(result.msg);
                        }
                            break;
                        case 2:
                        {
                            $(this).popUp({
                                width:450,
                                title:'提示!',
                                content:'<div style="padding:10px">'+result.info+'</div>',
                                buttons:'<label class="uiButton uiButtonConfirm"><input type="button" value="确定" class="callbackBtn" /></label>',
                                mask:true,
                                maskMode:true,
                                callback:function() {
                                    $.closePopUp();
                                }
                            });
                            $(".flashContent div").show()
                            $(".flashContent object").height(25);//隐藏选择图片按钮
                            $(".flashContent").height(50).css({"border":"1px solid #CCCCCC", "padding":"3px 10px"});
                            resetVideoInput();
                        }
                            break;
                    }
                }
            }
            $('#distributeButton').parent().mousedown(function(event) {
                if(event.which == 3) return;
                if ($(this).hasClass("disable")) {
                    return;
                }
                var currentDistributeType = $('#currentComposerAttachment').val();
                switch (currentDistributeType) {
                    case '0'://·状态
                        var $gid = $("#group_id").val();
                        var data = {};
                        data.type = "info";
                        data.content = self.myStatusTextArea.val();
                        //data.timestr = self.html_date.val();//我们暂时不需要日历
                        data.timestr = 2012-06-16;
                        data.gid = $gid;
                        self.model("info", [data, function(data) {
                            if (data.status == 0) {//返回数据失败
                                alert(data.info);
                                return false;
                            }
                            else{//返回数据成功
                                 self.view("list",data);
                            }
                            self.myStatusTextArea.val("");
                        }]);
                        break;
                    case '1'://照片
                        if ($('#snapshotPhotoFileOption').css('display') != 'none') {
                            if (!$('#distributeButton')[0].disabled) {
                                var flashID = self.getID('campz');
                                flashID.save($('#camUrl').val(), $('#hd_UID').val());
                                return false;
                            }
                        }
                        if (self.photoData) {
                            sendPhotoComplete.call(self, self.photoData);
                        } else {
                            $(this).popUp({
                                width:450,
                                title:'提示!',
                                content:'<div style="padding:10px">请先上传图片!</div>',
                                buttons:'<span class="popBtns blueBtn closeBtn">确定</span>',
                                mask:true,
                                maskMode:true
                            })
                        }
                        break;
                    case '2'://视频
                        var vId = $("#videoId").val();
                        if ($('#recordVideoPanel').css('display') != 'none') {//录制视频
                            if (!$('#distributeButton')[0].disabled) {
                                $.djax({
                                    url:mk_url("single/video/index",{c:'videoapi',m:'save_makevideo'}),
                                    dataType:"json",
                                    type:"POST",
                                    data:{hd_v_w:$("#hd_v_w").val(), hd_v_h:$("#hd_v_h").val(), hd_v_name:$("#hd_v_name").val(), txtdesc:$("#attachVideoIntroduce").val()},
                                    success:function(result) {
                                        videoManger(result);
                                    },
                                    error:function() {
                                        alert("网络错误!")
                                    }
                                });
                            };
                        }
                        if (vId) {
                            $.djax({
                                url:mk_url("video/videoapi/add_other_video_api"),
                                dataType:"jsonp",
                                type:"get",
                                async:true,
                                data:{vid:vId, txtdesc:$("#attachVideoIntroduce").val(), mid:3, info:$("#hd_info").val()},
                                success:function(result) {
                                    videoManger(result);
                                },
                                error:function() {
                                    alert("网络错误!")
                                }
                            });
                        } 
                        else {
                            if ($('#recordVideoPanel').css('display') == 'none') {
                                $(this).popUp({
                                    width:450,
                                    title:'提示!',
                                    content:'<div style="padding:10px">请先上传视频!</div>',
                                    buttons:'<span class="popBtns blueBtn closeBtn">确定</span>',
                                    mask:true,
                                    maskMode:true
                                })
                            }
                        }
                        break
                }
            });
        },
        distributeInfoBody:function(arg) {
	        arg[0].find("ul.composerAttachments").children().click(function() {
                var $askBox = $("#askBox");
                var $distributeInfoBox = $(".distributeInfoBox")
	            var c = $(this).closest("#distributeInfoBody");
	            var index = $(this).index();
	            var $pointUp = c.find("div.pointUp");
	            $pointUp.css("margin-left", 22 + 70 * (index));
	            if (index == 1) {
                    $distributeInfoBox.show();
                    $askBox.hide();
	                $("#photoUploadWay").show();
	                $("#distributePhoto").show();
	                $("#distributeMsg").hide();
	                $("#videoUploadWay").hide();
	                $("#distributeVideo").hide();
	                $("#distributePhoto .fileOption").hide();
	                $("#distributeVideo .fileOption").hide();
	                self.distributeInfoBodyFooter.hide();
	                c.find("div.distributeInfoBox").children("#distributePhoto").show();
	            }
	            if (index == 2) {
                    $distributeInfoBox.show();
                    $askBox.hide();
	                $("#videoUploadWay").show();
	                $("#distributeVideo").show();
	                $("#distributeMsg").hide();
	                $("#photoUploadWay").hide();
	                $("#distributePhoto").hide();
	                $("#distributePhoto .fileOption").hide();
	                $("#distributeVideo .fileOption").hide();
	                self.distributeInfoBodyFooter.hide();
	                c.find("div.distributeInfoBox").children("#distributeVideo").show();
	            }
                if (index == 3) {
                    $distributeInfoBox.hide();
                    $askBox.show();
                    $("#videoUploadWay").hide();
                    $("#distributeVideo").hide();
                    $("#distributeMsg").hide();
                    $("#photoUploadWay").hide();
                    $("#distributePhoto").hide();
                    $("#distributePhoto .fileOption").hide();
                    $("#distributeVideo .fileOption").hide();
                    self.distributeInfoBodyFooter.hide();
                    c.find("div.distributeInfoBox").children("#distributeVideo").hide();
                    
                }
	            if (index == 0) {
                    $distributeInfoBox.show();
                    $askBox.hide();
	                $("#distributeMsg").show();
	                $("#distributePhoto").hide();
	                $("#distributeVideo").hide();
	                self.distributeInfoBodyFooter.show();
	                c.find("div.distributeInfoBox").children("#distributeMsg").show();
	                if(self.myStatusTextArea.height() < 50) {
	                    self.myStatusTextArea.css("height", 50)
	                }
	                var l = self.myStatusTextArea.val().length;
	                if(self.myStatusTextArea.val() != "" && !(l > self.myStatusTextArea.attr("tmaxlength"))) {
	                    self.view("createBtnAble",true);
	                } else {
	                    self.view("createBtnAble",false);
	                }
	            }
	            $("#distributeButton").parent().attr("data", "false");
	            $('#currentComposerAttachment').val(index);
	            arg[0].find("#currentComposerAttachment").val(index);
	        });
        },
        ask:function(arg){
            self.model("ask", [{type:'ask',ask:arg[0],gid:$("#group_id").val()}, function(data) {
                if (data.status == 0) {//返回数据失败
                    alert(data.info);
                    return false;
                }
                else{//返回数据成功
                    self.view("ask",data.data);
                    arg[1].reset();
                }
            }]);
        }
	}
	return this.event._class[name](arg)
}
//插件调用
group.plug = function(name,arg){
	var self = this;
	this.plug._class={
		msg:function(arg) {
            arg[0].find("[msg]").msg();
        },
        commentEasy:function (arg) {
            arg.commentEasy({
                minNum:3,
                UID:CONFIG['u_id'],
                userName:CONFIG['u_name'],
                avatar:CONFIG['u_head'],
                userPageUrl:$("#hd_userPageUrl").val(),
                hasShare:0,
                relayCallback:function (obj,_arg) {
                    var comment=new ui.Comment();
                    comment.share(obj,_arg);
                }
            });
        },
        askBox:function(arg){
            arg.ask({
                type:2,
                clickCallback:function(data,obj){
                    self.event('ask',[data,obj]);
                }
            })
        }
	}
	return this.plug._class[name](arg)
}
//请求得到数据
group.model = function(name,arg){
    var self = this;
	this.model._class={
		info:function(arg) {
            $.djax({
                url:mk_url("group/info/doPost"),
                dataType:"json",
                async:true,
                data:arg[0],
                success:function(data) {
                    arg[1](data);
                },
                error:function(data) {

                }
            });
        },
        all:function(arg) {
            $.djax({
                url:mk_url("group/info/infoLine"),
                dataType:"json",
                async:true,
                data:arg[0],
                success:function(data) {
                    arg[1](data);
                },
                error:function(data) {

                }
            });
        },
        photo:function(arg) {
            $.djax({
                url:mk_url("group/info/doPost"),
                dataType:"json",
                async:true,
                data:arg[0],
                success:function(data) {
                    $('#distributeButton').parent().attr("data", "false");
                    self.view("createBtnAble",false);
                    arg[1](data);
                },
                error:function(data) {

                }
            });
        },
        video:function(arg) {
            $.djax({
                url:mk_url("group/info/doPost"),
                dataType:"json",
                async:true,
                data:arg[0],
                success:function(data) {
                    arg[1](data);
                    $('#distributeButton').parent().attr("data", "false");
                    self.view("createBtnAble",false);
                },
                error:function(data) {

                }
            });
        },
        ask:function(arg){
            $.djax({
                url:mk_url("group/info/doPost"),
                dataType:"json",
                async:true,
                data:arg[0],
                success:function(data) {
                    arg[1](data);
                },
                error:function(data) {

                }
            }); 
        }
	}
	return this.model._class[name](arg)
}
group.init =function(){
    var $gid = $("#group_id").val();

	//事件驱动
	var self = this;
    this.attachPhotoIntroduce = $("#attachPhotoIntroduce");
    this.distributeInfoBodyFooter = $('#distributeInfoBody').find('div.footer');
    $(".Js_textArea").each(function() {
        var myStatusTextArea = new Textarea.msgTip(this, {
            maxlength:140,
            notMedia: true,
            textareaProps:{
                "class":"shareInfoCont msg"
            },
            textareaStyles:{
                overflow: "hidden",
                height: 19
            },
            button:{
                id:$("#distributeButton").parent()
            }
        });
    });
    $(".Js_mediaArea").each(function() {
        var mediaArea = new Textarea.msgTip(this, {
            maxlength:140,
            textareaProps:{
                "class":"shareInfoCont msg"
            },
            notMedia: false,
            textareaStyles:{
                overflow:"hidden",
                height:50
            },
            button:{
                id:$("#distributeButton").parent()
            }
        });
    });
    this.myStatusTextArea = $("#distributeInfoBody").find("#myStatusTextArea");
    //首次进入群首页即请求数据
    var data = {};
    data.pager = 1;
    data.gid = $gid;
    self.model("all", [data, function(data) {
        if (data.status == 0) {//返回数据失败
            alert(data.info);
            return false;
        }
        else{//返回数据成功
             self.view("all",data);
        }
    }]);
    group.event("status");
    group.event("photo");
    group.event("button");
    group.event("video");
    group.event("distributeInfoBody", [$("#distributeInfoBody")]);
    
    //滚动加载及点击加载
    var loadmore =  {
        init: function() {
            if($('#loadmore')[0]) {
                this.bindScroll();
            }
        },
        //滚动加载及点击加载 ——参数
        scrollParameter: {
            pager: 2,
            scroll: 0,
            url: mk_url("group/info/infoLine"),
            keyword: ''
        },
        resetLoad: function(url, keyword) {
            this.scrollParameter = {
                pager: 2,
                scroll: 0,
                url: url,
                keyword: keyword
            };
            $('#loadmore').unbind('click');
            this.clickGet($('#loadmore'));
        },
        bindScroll: function() {
            var self = this;
            $(window).on('scroll', self.scroll);
            this.clickGet($('#loadmore'));
        },
        scroll: function() {
            var bar = $('#loadmore');
            if(bar[0] && !bar.hasClass('getting')) {

                var wH = $(window).height(),
                    sH = $(window).scrollTop(),
                    bH = $('body').height(),
                    par = loadmore.scrollParameter;
                if(par.scroll < 2 && sH > 0 && sH > (bH - wH - 10)) {
                    bar.removeClass('hide').addClass('getting');
                    var data = {pager: par.pager,gid:$gid};
                    $.ajax({
                        url: par.url,
                        type: 'POST',
                        data: data,
                        dataType: 'json',
                        success: function(data) {
                            if(data.state == 1) {
                                par.pager++;
                                par.scroll++;
                                if(data.last == true) {
                                    bar.hide();
                                    self.view("all",data);
                                } 
                                else {
                                    bar.addClass('hide').removeClass('getting');
                                    self.view("all",data);
                                    if(par.scroll == 2) {
                                        bar.removeClass('hide').addClass('clickGet').find("span").addClass("hide").end().find("a").removeClass("hide").text('点击查看更多');
                                    }
                                }
                            } 
                            else {
                                alert(data.msg);
                            }
                        }
                    });
                }
            }
        },
        clickGet: function(loadBar) {
            var par = this.scrollParameter;
            loadBar.click(function () {
                var $this = $(this);
                if($this.hasClass('clickGet')) {
                    $this.removeClass('clickGet').find("a").addClass("hide").end().find("span").removeClass("hide");
                    var data = {pager: par.pager,gid:$gid};
                    $.djax({
                        url: par.url,
                        type: 'POST',
                        data: data,
                        dataType: 'json',
                        success: function(data) {
                            if(data.state == 1) {
                                par.pager++;
                                par.scroll = 0;
                                self.view("all",data);
                                 $this.addClass('hide').removeClass('getting');
                                if(data.last === true) {
                                    $this.remove();
                                }
                            } else {
                                alert(data.msg);
                            }
                        }
                    });
                }
            });
        }
    };
    loadmore.init();
}

$(function(){
	
    window.sendPhotoComplete = function(data) {
        var str = data;
        if (str.status == 1) {
            $("#flashuploaduid div").show();
            $("#flashuploaduid object").height(25);//隐藏选择图片按钮
            $("#flashuploaduid").height(50).css({"border":"1px solid #CCCCCC", "padding":"3px 10px"});
            var $gid = $("#group_id").val();
            var data = {};
            data.type = "album";
            data.gid = $gid;
            data.picurl = photoDatas.join(",");
            data.content = $("#attachPhotoIntroduce").val() || $("#attachCameraPhotoIntroduce").val();
            data.timestr = 2012-06-16;
            this.model(["photo"], [data, function(data) {
                if (data.status == 0) {//返回数据失败
                    alert(data.info);
                    return false;
                }
                else{//返回数据成功
                     group.view("photo",data);
                }
                group.myStatusTextArea.val("");
            }]);
            self.photoData = null;
            photoDatas.length = 0;
            $("#flashuploaduid").show();
            $("#up_photo_success").hide();
            //inserData(str.data,true);
        } else {
            alert(str.msg);
        }
        $("#uploadPhotoForm").reset;
        $('#tokenareaList').empty();
        $('#attachPhotoIntroduce').val('');
        $('#tokenShareDestinations').val('');
    };


    /***视频播放***/

        //显示播放flash
    $("#timelineContent").on('click', 'div.media_prev', function () {
        //alert($(this).next().attr('videosrc'));
        var _self = this;
        //创建一个视频对象
        var videoController = new VideoController();
        //获取页面上的视频id
        var videoId = $(this).next().children('div').attr('id');
        //获取视频其它参数，与id不在同一个div上
        var fid = $(this).closest("[name='timeBox']").attr("fid");
        var videoWidth, videoHeight;
        videoWidth = 401; //parseInt(videoDiv.attr('videowidth'));
        videoHeight = 300; //parseInt(videoDiv.attr('videoheight'));    //播放控制高度
        
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
                    'movie', miscpath +'flash/video/player.swf?vid=' + this.currentVideoId + '&mod=3&uid=' + document.getElementById('hd_UID').value,
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
$(function(){
    group.init();
    group.plug(['askBox'], $("#askBox"));
});
