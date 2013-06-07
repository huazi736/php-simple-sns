//获取地址栏中的tid
var tid='';
(function(href) {
	if(href[1]) {
		tid = href[1];
	}
})(window.location.href.split('tid='));
$(function(){
	var get_data_url = mk_url('main/info/ajaxView');//获取数据
	var del_url = mk_url('main/setting/delWeb');//删除消息
	var forward_url = mk_url('main/setting/getWebDate');//分享
	var infoDetail = {}
	infoDetail.view = function(name,arg){
		this.view._class={
 forward:function(arg){
                
                var $content = arg[0],faceUrl;
                
                if(!arg[1].url){
                    faceUrl = $("#hd_avatar").val();
                }

                var sideClass,clickDown,forwardContent="";
                var location,typeHtml,albumText;

                if(arg[1].highlight==0||""){
                    // 小
                    clickDown = "";
                    sideClass = "sideLeft";
                }else{
                    // 大
                    clickDown = "clickDown";
                    sideClass = "twoColumn clearfix";
                }
                var ftid="",ftype="";
                 if(arg[1].forward&&arg[1].forward.length!=0){

                    ftid = arg[1].forward.tid;
                    ftype = arg[1].forward.type;
                    
                    location = webpath+"main/index.php?c=index&m=index&action_dkcode="+arg[1].forward.dkcode;
                    
                    switch(arg[1].forward.type){
                        case "info":
                            forwardContent = '<div class="forwardContent"><span class="memo" style="margin:0">'+arg[1].forward.content+'</span></div>';
                            typeHtml = '分享了<span class="oldAuthorName" uid="'+arg[1].forward.uid+'"><a href="'+location+'">'+arg[1].forward.uname+'</a></span>的状态</a>';

                        break;

                        case "album":
                            var temp = "";
                            temp='<ul class="photoContent clearfix">';


                                if(arg[1].forward.photonum=="1"&&parseInt(arg[1].forward.fid)>10000000){
                                    albumText = "照片";  

                                }else{
                                    albumText = "相册";

                                }
                                if(arg[1].forward.from=="1"){
                                    albumText = "照片";
                                }
                                if(arg[1].forward.picurl){
                                    var hidden = "";
                                    var _height;
                                   $.each(arg[1].forward.picurl,function(i,v){
                                        var firstPhoto = "";
                                        var size = "_ts"

                                        if(i==0){
                                            firstPhoto = "firstPhoto";
                                            size = "_tm";
                                            if(v.size){
                                                if(v.size.tm){
                                                    _height = v.size.tm.h;
                                                }
                                            }
                                        }else{
                                            size = "_ts";
                                        }
                                        if(i==1){

                                            if(v.size){
                                                if(v.size.ts){
                                                    _height=v.size.ts.h;
                                                }
                                            }
                                        }
                                     
                                        if(i>3){
                                            hidden = "hide";
                                        }

                                        picurl = fdfsHost+"/"+v.groupname+"/"+v.filename+size+"."+v.type;
                                        temp+='<li class="'+firstPhoto+' '+hidden+'" style="height:'+_height+'px"><a href="javascript:void(0);" url="'+webpath+'single/album/index.php?c=index&m=photoInfo&photoid='+v.pid+'&action_dkcode='+arg[1].dkcode+'" class="photoLink"><img src="http://'+picurl+'" alt="" /></a></li>';
                                    });

                                    typeHtml = '分享了<span class="oldAuthorName" uid="'+arg[1].forward.uid+'"><a href="'+location+'">'+arg[1].forward.uname+'</a></span>的<a href="'+webpath+'single/album/index.php?c=index&m=photoLists&action_dkcode='+arg[1].forward.dkcode+'&albumid='+arg[1].forward.note+'">'+albumText+'</a>';
                                }
                                temp+='</ul>';
                            forwardContent = '<div class="forwardContent"><span class="memo" style="margin:0">'+arg[1].forward.content+'</span></div>'+temp;
                        break;
                        case "video":
                            var temp = "";
                            temp='<div class="mediaContent"><div class="media_prev">';
                            if (arg[1].highlight==0||"") {
                                temp +='<img src="'+arg[1].forward.imgurl+'" alt="" width=403 height=300 />';
                            }else{
                                temp +='<img src="'+arg[1].forward.imgurl+'" alt="" width=838 height=600 />';
                            };

                            typeHtml = '分享了<span class="oldAuthorName" uid="'+arg[1].forward.uid+'"><a href="'+location+'">'+arg[1].forward.uname+'</a></span>的<a href="'+webpath+'/single/video/index.php?c=video&m=player_video&vid='+arg[1].forward.fid+'">视频</a>';

                            temp +='<a class="showFlash" href="javascript:void(0);"><img alt="" src="'+miscpath+'img/system/feedvideoplay.gif"></a></div><div class="media_disp hide" videosrc="'+arg[1].forward.videourl+'"><div id="'+arg[1].forward.fid+'"></div></div></div>';
                             forwardContent = '<div class="forwardContent"><span class="memo" style="margin:0">'+arg[1].forward.content+'</span></div>'+temp;
                        break;
                    }

                    
                }else{
                    forwardContent = '<div class="forwardContent"></span>该信息已被删除！</div>'
                    typeHtml = "分享了一个信息";
                }
                
                var location = webpath+"main/index.php?c=index&m=index&action_dkcode="+arg[1].dkcode;
                if (arg[1].web_url) {//web_url
                    location = arg[1].web_url;
                };
                var noForward="",pageType;
                if(parseInt(arg[1].from)>2){
                    noForward="noForward=true"
                    pageType = "web_"+arg[1].type;
                }else{
                    pageType =arg[1].type;
                }
                var str = '<li name="timeBox" scale="true" id="'+arg[1].tid+'"  fid="'+arg[1].fid+'" forwardId="'+ftid+'" forwardType="'+ftype+'" uid="'+arg[1].uid+'" type="'+arg[1].type+'" type="'+arg[1].type+'" highlight="'+arg[1].highlight+'" time="'+arg[1].ctime+'" class="'+sideClass+'"><i class="spinePointer"></i><div class="timelineBox"><div class="headBlock clearfix"><a href="'+location+'" class="headImg"><img src="'+(arg[1].headpic||self.hd_avatar)+'" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="'+location+'">'+arg[1].uname+'</a>';
                str+='<span class="subTip">'+typeHtml+'</span></div>';
                str+='<div class="postTime"><a href="#">'+arg[1].friendly_time+'</a><div class=""></div></div></div></div><div class="infoContent">'+arg[1].content+'</div>'+forwardContent+'<div class="commentBox pd" commentObjId="'+arg[1].tid+'" ctime="'+arg[1].ctime+'" pageType="'+pageType+'" action_uid="'+arg[1].uid+'"></div></div></li>';
                



                
                var $str = $(str);
                if(arg[2]){
                    if(arg[2]=="prepend"){
                        $content.prepend($str);
                    }
                    if(arg[2]=="afterFirst"){
                        $content.children().first().after($str);
                    }
                }else{
                    $content.append($str);
                }
                return $str;
               
            },
            info:function(arg){
               
                
                
                var $content = arg[0],faceUrl;
                if(!arg[1].url){
                    faceUrl = $("#hd_avatar").val();
                }
                
                var sideClass,clickDown;

                var location = webpath+"main/index.php?c=index&m=index&action_dkcode="+arg[1].dkcode;
                if (arg[1].web_url) {//web_url
                    location = arg[1].web_url;
                };

                if(arg[1].highlight==0||""){
                    // 小
                    clickDown = "";
                    sideClass = "sideLeft";
                }else{
                    // 大
                    clickDown = "clickDown";
                    sideClass = "twoColumn clearfix";
                } 
                if(arg[2]&&arg[2]=="afterFirst"){
                	sideClass = "sideRight";
                }
                var noForward="",pageType;
                if(parseInt(arg[1].from)>2){
                    noForward="noForward=true";
                    pageType = "web_topic";
                }else{
                    pageType ="topic";
                }
                


                var str = '<li name="timeBox" scale="true" id="'+arg[1].tid+'"  fid="'+arg[1].fid+'" uid="'+arg[1].uid+'" type="'+arg[1].type+'" type="'+arg[1].type+'" highlight="'+arg[1].highlight+'" time="'+arg[1].ctime+'" class="'+sideClass+'"><i class="spinePointer"></i><div class="timelineBox"><div class="headBlock clearfix"><a href="'+location+'" class="headImg"><img src="'+(arg[1].headpic||self.hd_avatar)+'" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="'+location+'">'+arg[1].uname+'</a></div><div class="postTime"><a href="#">'+arg[1].friendly_time+'</a><div class=""></div></div></div></div><div class="infoContent">'+arg[1].content+'</div><div class="commentBox pd" ctime="'+arg[1].ctime+'" commentObjId="'+arg[1].tid+'" pageType="'+pageType+'" '+noForward+' action_uid="'+arg[1].uid+'"></div></div></li>';
               
                
                var $str = $(str);
                if(arg[2]){
                    if(arg[2]=="prepend"){
                        $content.prepend($str);
                    }
                    if(arg[2]=="afterFirst"){
                        $content.children().first().after($str);
                    }
                }else{
                    $content.append($str);
                }
                return $str;

            },
            album:function(arg){
                var $content = arg[0],faceUrl;
                if(!arg[1].url){
                    faceUrl = $("#hd_avatar").val();
                }
                    
              
                var typeHref;

                var picurl;

                var sideClass,clickDown,commentObjId;
                var size,photoClass="";

                var location = webpath+"main/index.php?c=index&m=index&action_dkcode="+arg[1].dkcode;
                if (arg[1].web_url) {//web_url
                    location = arg[1].web_url;
                };
                if(arg[1].highlight==0||""){
                    // 小
                    clickDown = "";
                    sideClass = "sideLeft";
                }else{
                    // 大
                    clickDown = "clickDown";
                    sideClass = "twoColumn clearfix";
                }
                if(arg[2]&&arg[2]=="afterFirst"){
                	sideClass = "sideRight";
                }

                var noForward="",pageType;
                if(parseInt(arg[1].from)>2){
                    noForward="noForward=true"
                    pageType = "web_"+arg[1].type;
                }else{
                    pageType =arg[1].type;
                }
                

       

                var str = '<li name="timeBox" scale="true" id="'+arg[1].tid+'" type="album" highlight="'+arg[1].highlight+'" fid="'+arg[1].fid+'" uid="'+arg[1].uid+'" type="'+arg[1].type+'"  album="403" time="'+arg[1].ctime+'" class="'+sideClass+'"><i class="spinePointer"></i><div class="timelineBox"><div class="headBlock clearfix"><a href="'+location+'" class="headImg"><img src="'+(arg[1].headpic||self.hd_avatar)+'" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="'+location+'">'+arg[1].uname+'</a>';


                if(parseInt(arg[1].fid)>10000000){
                    albumText = "照片";  
                    pageType = "photo"

                    commentObjId = arg[1].picurl[0].pid;
                }else{
                    albumText = "相册";
                    pageType = "album";
                    commentObjId = arg[1].fid;

                }
                if(arg[1].from=="1"){
                    pageType = "photo"
                }else{
         
                    typeHref = webpath+"single/album/index.php?c=index&m=photoLists&action_dkcode="+arg[1].dkcode+"&albumid="+arg[1].note;
                    str+='<span class="subTip">上传了<a href="'+typeHref+'">'+albumText+'</a></span>';
                }
                str+='</span></div><div class="postTime"><a href="javascript:void(0)">'+arg[1].friendly_time+'</a>';

               var ctime = arg[1].ctime;
               var dateline = arg[1].dateline;

               

               var friendly_dateline = self.cpu(["returnFriendly_date"],[ctime,dateline]);
                if(friendly_dateline){
                    str+='<i class="insertTime tip_up_left_black" tip="'+friendly_dateline+'"></i>';
                }
               str+='<div class=""></div></div></div></div><div class="infoContent">'+arg[1].content+'</div><ul class="photoContent clearfix">';

                if(arg[1].picurl){
                    var hidden = "";
                    var _height;
                   $.each(arg[1].picurl,function(i,v){
                        if(i>3){
                            hidden = "hide";
                        }
                        var firstPhoto = "";
                        var width = "",height = "";
                        if(i==0){
                            firstPhoto = "firstPhoto";
                            size = "_tm";
                            if(v.size){
                                if(v.size.tm){
                                    _height = v.size.tm.h;
                                }
                            }
                        }else{
                            size = "_ts";
                        }
                        if(i==1){

                            if(v.size){
                                if(v.size.ts){
                                    _height=v.size.ts.h;
                                }
                            }
                        }


                        picurl = fdfsHost+"/"+v.groupname+"/"+v.filename+size+"."+v.type;

                        str+='<li class="'+firstPhoto+' '+hidden+'" style="height:'+_height+'px"><a href="javascript:void(0);" url="'+webpath+'single/album/index.php?c=index&m=photoInfo&photoid='+v.pid+'&action_dkcode='+arg[1].dkcode+'" class="photoLink"><img src="http://'+picurl+'" alt="" /></a></li>'; //height="'+height+'" width="'+width+'" 删除 by李世君 2012-4-1
                    });
                }
                str+='</ul><div class="commentBox pd" commentObjId="'+commentObjId+'" '+noForward+' pageType="'+pageType+'" ctime="'+arg[1].ctime+'" action_uid="'+arg[1].uid+'"></div></div></li>';

                var $str = $(str);
                if(arg[2]){
                    if(arg[2]=="prepend"){
                        $content.prepend($str);
                    }
                    if(arg[2]=="afterFirst"){
                        $content.children().first().after($str);
                    }
                }else{
                    $content.append($str);
                }
                return $str;
              
            },
            video:function(arg){
               
                var $content = arg[0],faceUrl;
                if(!arg[1].url){
                    faceUrl = $("#hd_avatar").val();
                }
                var subtype = arg[1].subtype;
                var sideClass,clickDown;


                var location = webpath+"main/index.php?c=index&m=index&action_dkcode="+arg[1].dkcode;
                if (arg[1].web_url) {//web_url
                    location = arg[1].web_url;
                };

                if(arg[1].highlight==0||""){
                    // 小

                    clickDown = "";
                    sideClass = "sideLeft";
                }else{
                    // 大
                    clickDown = "clickDown";
                    sideClass = "twoColumn clearfix";

                }
                if(arg[2]&&arg[2]=="afterFirst"){
                	sideClass = "sideRight";
                }

                var noForward="",pageType;
                if(parseInt(arg[1].from)>2){
                    noForward="noForward=true"
                    pageType = "web_"+arg[1].type;
                }else{
                    pageType = arg[1].type;
                }
                

                var str = '<li id="'+arg[1].tid+'" name="timeBox" scale="true"  fid="'+arg[1].fid+'" uid="'+arg[1].uid+'" type="'+arg[1].type+'" highlight="'+arg[1].highlight+'" time="'+arg[1].ctime+'" class="'+sideClass+'"><i class="spinePointer"></i><div class="timelineBox"><div class="headBlock clearfix"><a href="'+location+'" class="headImg"><img src="'+(arg[1].headpic||self.hd_avatar)+'" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="'+location+'">'+arg[1].uname+'</a>';
                    if(arg[1].from=="1"){
                    }else{
                        str+='<span class="subTip">上传了<a href="'+arg[1].url+'">视频</a></span>';
                    }
                    str+='</div><div class="postTime"><a href="#">'+arg[1].friendly_time+'</a><div class=""></div></div></div></div><div class="infoContent">'+arg[1].content+'</div><div class="mediaContent"><div class="media_prev"><img src="'+arg[1].imgurl+'" alt="" width="403" height="300" /><a class="showFlash" href="javascript:void(0);"><img alt="" src="'+miscpath+'img/system/feedvideoplay.gif"></a></div><div class="media_disp hide" videosrc="'+arg[1].videourl+'"><div id="'+arg[1].fid+'"></div></div></div><div class="commentBox pd" commentObjId="'+arg[1].tid+'" pageType="'+pageType+'" ctime="'+arg[1].ctime+'" '+noForward+' action_uid="'+arg[1].uid+'"></div></div></li>';

                
                var $str = $(str);
                if(arg[2]){
                    if(arg[2]=="prepend"){
                        $content.prepend($str);
                    }
                    if(arg[2]=="afterFirst"){
                        $content.children().first().after($str);
                    }
                }else{
                    $content.append($str);
                }
                return $str;
               
            }
        	
			//方法结束
        
		}
		$.each(name,function(index,value){
			if(value){
				return this.view._class[name](arg)
			}
		});
		
	}
	infoDetail.event = function(name,arg){
		this.event._class={
			forward:function(arg){
					var p = arg[0].closest("div.timelineBox");  // 父容器
			        var name1 = p.find(".AuthorName").children("a").text(); // 得到作者名字
			        var name2;
			        var name3;
			        var value,$content,imgurl;
			        // 以下判断是否是一次分享 还是二次分享
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
				var typeFunction =  function(type){
                    switch(type){
                        case "info":
                             
                             $content = $('<div class="laymoveText"><div class="zf_content"><textarea maxlength="140"></textarea><div class="content"><p>'+value+'</p><p>由<span class="name"><a>'+name3+'</a></span>发布</p></div></div><div class="replyFor"><p><input type="checkbox" id="replyCheck"><label for="replyCheck">同时评论给 '+name1+'</label></p></div></div>');
                        break;
                        case "album":
                             
                             imgurl = p.find("ul.photoContent").find("img").attr("src");
                             $content = $('<div class="laymoveText"><div class="zf_content"><textarea maxlength="140"></textarea><div class="content"><div class="left"><img src="'+imgurl+'" width="90" height="60" /></div><div class="right"><p>'+value+'</p><p>由<span class="name"><a>'+name3+'</a></span>发布</p></div></div></div><div class="replyFor"><p><input type="checkbox" id="replyCheck"><label for="replyCheck">同时评论给 '+name1+'</label></p></div></div>');
                        break;
                        case "video":
                            
                             imgurl = p.find("div.mediaContent").find("img").attr("src");
                             $content = $('<div class="laymoveText"><div class="zf_content"><textarea maxlength="140"></textarea><div class="content"><div class="left"><img src="'+imgurl+'" width="168" height="90" /></div><div class="right"><p>'+value+'</p><p>由<span class="name"><a>'+name3+'</a></span>发布</p></div></div></div><div class="replyFor"><p><input type="checkbox" id="replyCheck"><label for="replyCheck">同时评论给 '+name1+'</label></p></div></div>');
                        break;
                    }
                    return $content;
                }
				
				if(p.attr("type")=="forward"){
                    if(p.attr("forwardType")&&p.attr("forwardType")!="undefined"){
                        $content = typeFunction(p.attr("forwardType"));
                    }else{
                       self.plug(["popUp"],[p,'<div style="padding:10px">原始信息已被删除！无法进行操作</div>',"提示",function(){
                        $.closePopUp();
                       },'<span class="popBtns blueBtn callbackBtn">知道了</span>',400]);           
                       return false;
                    }
                    
                    $content.find("div.replyFor").append('<p><input type="checkbox" id="replyCheckOld"><label for="replyCheckOld">同时评论给原作者 '+name2+'</label></p>');
                }else{
                   $content = typeFunction(p.attr("type"));
                }
				
	     	 	  var $content = $('<div class="laymoveText"><div class="zf_content"><textarea maxlength="140"></textarea><div class="content"><p>'+value+'</p><p>由<span class="name"><a>'+name3+'</a></span>发布</p></div></div><div class="replyFor"><p><input type="checkbox" id="replyCheck"><label for="replyCheck">同时评论给 '+name1+'</label></p></div></div>');
				infoDetail.plug('popUp',[arg[0],$content,"分享",function(){
		            var data = {}
		            	data.content = $content.find("textarea").val();
		          	    data.tid = p.attr("id");
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
						infoDetail.model('retransmission',[data,
			                function(data){
								if(data.status!=0){
									var num = p.find(".forwardNum").text();
									p.find(".forwardNum").text(parseInt(num)+1);
								}
								$.closePopUp();
			                }
			            ]
					);
		        },'<span class="popBtns blueBtn callbackBtn">分享</span><span class="popBtns closeBtn">取消</span>']);
		        limitStrNum($content.find("textarea"));
		    },
		 forward:function(arg){
                var p = arg[0].closest("li[name=timeBox]")
                var name1 = p.find(".AuthorName").children("a").text();
                var name2;
                var name3;
                var value,$content,imgurl;
                var forwardid = p.attr("forwardid");
				var pp = arg[0].closest("div#getInfo_box").find("div#"+forwardid);

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
                
                var typeFunction =  function(type){
                    switch(type){
                        case "info":
                             
                             $content = $('<div class="laymoveText"><div class="zf_content"><textarea maxlength="140"></textarea><div class="content"><p>'+value+'</p><p>由<span class="name"><a>'+name3+'</a></span>发布</p></div></div><div class="replyFor"><p><input type="checkbox" id="replyCheck"><label for="replyCheck">同时评论给 '+name1+'</label></p></div></div>');
                        break;
                        case "album":
                             
                             imgurl = p.find("ul.photoContent").find("img").attr("src");
                             $content = $('<div class="laymoveText"><div class="zf_content"><textarea maxlength="140"></textarea><div class="content"><div class="left"><img src="'+imgurl+'" width="90" height="60" /></div><div class="right"><p>'+value+'</p><p>由<span class="name"><a>'+name3+'</a></span>发布</p></div></div></div><div class="replyFor"><p><input type="checkbox" id="replyCheck"><label for="replyCheck">同时评论给 '+name1+'</label></p></div></div>');
                        break;
                        case "video":
                            
                             imgurl = p.find("div.mediaContent").find("img").attr("src");
                             $content = $('<div class="laymoveText"><div class="zf_content"><textarea maxlength="140"></textarea><div class="content"><div class="left"><img src="'+imgurl+'" width="168" height="90" /></div><div class="right"><p>'+value+'</p><p>由<span class="name"><a>'+name3+'</a></span>发布</p></div></div></div><div class="replyFor"><p><input type="checkbox" id="replyCheck"><label for="replyCheck">同时评论给 '+name1+'</label></p></div></div>');
                        break;
                    }
                    return $content;
                }
                
                if(p.attr("type")=="forward"){
                    if(p.attr("forwardType")&&p.attr("forwardType")!="undefined"){
                        $content = typeFunction(p.attr("forwardType"));
                    }else{
                       self.plug(["popUp"],[p,'<div style="padding:10px">原始信息已被删除！无法进行操作</div>',"提示",function(){
                        $.closePopUp();
                       },'<span class="popBtns blueBtn callbackBtn">知道了</span>',400]);           
                       return false;
                    }
                    
                    $content.find("div.replyFor").append('<p><input type="checkbox" id="replyCheckOld"><label for="replyCheckOld">同时评论给原作者 '+name2+'</label></p>');
                }else{
                   $content = typeFunction(p.attr("type"));
                }

               
                self.plug(["popUp"],[arg[0],$content,"分享",function(){
                    var data = {}
                    data.content = $content.find("textarea").val();
                    data.tid = p.attr("id");
                    if(p.attr("forwardId")&&p.attr("forwardId")!="undefined"){
                        data.fid = p.attr("forwardId");
                    }else{
                        data.fid = p.attr("id");
                    }

                    // 一次分享
                    if($content.find("#replyCheck").attr("checked")){
                        data.reply_now = p.attr("uid");
                    }

                    // 二次分享
                    if($content.find("#replyCheckOld").attr("checked")){
                        data.reply_author = p.find(".oldAuthorName").attr("uid");
                    }


                    self.model(["doShare"],[data,function(result){
                        if(result.status!=0){
                            var num = p.find(".forwardNum").text();
                            p.find(".forwardNum").text(parseInt(num)+1);
                            //原信息分享数+1
                            if(pp.size()!=0){
                                num = pp.find(".forwardNum").text();
                                pp.find(".forwardNum").text(parseInt(num)+1);
                            }
                            if(self.action_dkcode==self.hd_UID){
                                var $timePsBox = self.timelineTree.find("li[now]")
                              

                                var a = $timePsBox.find("a[name]");
                                $("html,body").animate({scrollTop:a.offset().top-165},200);
                            }
                        }
                        $.closePopUp();
                    }]);


                },'<span class="popBtns blueBtn callbackBtn">分享</span><span class="popBtns closeBtn">取消</span>']);
                limitStrNum($content.find("textarea"));
            },
			delInfo:function(arg){
				var user_home= $("#hd_userPageUrl").val();
				var $msg = "<div style='padding: 10px;'><p>是否确定删除状态？</p></div>";
				var data = {};
					data.tid = arg[0].closest("div.timelineBox").attr("id");
					arg[0].click(function(){
						infoDetail.plug('popUp',[arg[0],$msg,"提示信息",function(){
			          
							infoDetail.model('del',[data,
				                function(data){
									if(data.status!=0){
										$.closePopUp();
										window.location.href = user_home;
										return false;
									}
				                }
				            ]
						);
			        },'<span class="popBtns blueBtn callbackBtn">确定</span><span class="popBtns closeBtn">取消</span>']);
					});
			}
		}
		return this.event._class[name](arg)
	}
	infoDetail.plug = function(name,arg){
		this.plug._class={
			commentEasy:function(arg){
               arg[0].find('.commentBox:not(.hasComment)').commentEasy({
                    minNum:3,
					UID:CONFIG['u_id'],
					userName:CONFIG['u_name'],
					avatar:CONFIG['u_head'],
					userPageUrl:$("#hd_userPageUrl").val(),
					isShow:false,
                    isOnlyYou:false,
                    relay:true,
					relayCallback:function(obj){
							infoDetail.event("forward",[$(".timelineBox")]);
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
		}
		return this.plug._class[name](arg)
	}

	//请求得到数据
	infoDetail.model = function(name,arg){
		this.model._class={
			retransmission:function(arg){
				$.djax({
					url:mk_url("main/info/doShare"),
					data:arg[0],
					success:function(data){
						arg[1](data);
					}
				});
			},
			del:function(arg){
				$.djax({
					url:mk_url("main/info/doDelTopic"),
					data:arg[0],
					success:function(data){
						arg[1](data)
					}
				});
			}
		}
		return this.model._class[name](arg)
	}
	infoDetail.init =function(){
		//事件驱动
		$.djax({
				url:get_data_url,
				data:{
					tid: tid
				},
				type: 'POST',
				dataType: 'json',
				success: function(data) {
						if((data.data!==null)&&(data.data.length>0)){
                        $.each(data.data,function(a,b){
                            
                            var obj = infoDetail.view([b.type],[proxy.$e,b,loadType]);
                        });
                        
                        self.cpu('lay',[proxy.$e]);
                        self.plug(["commentEasy"],[proxy.$e]);
                        
		                }else{
		                    return;
		                }
				}
		});
		
		infoDetail.plug("commentEasy",[$("#getInfo_box")]);
		infoDetail.event("delInfo",[$(".del_info")]);
	}
	infoDetail.init();
});