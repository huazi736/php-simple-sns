/***视频播放***/
function setVideo(dom,vid){
    dom.on('click', 'div.media_prev', function () {
        var _self = this;
        //创建一个视频对象
        var videoController = new VideoController();
        //获取页面上的视频id
        var videoId = $(this).next().children('div').attr('id');
        //获取视频其它参数，与id不在同一个div上
        var fid = $(this).attr("fid");
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
}
    