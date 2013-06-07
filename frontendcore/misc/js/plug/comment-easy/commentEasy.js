/**
 * @author:    tianxb(55342775@qq.com)
 * @created:   2011/12/2
 * @version:   v1.0
 * @desc:      本JS为 评论 "博客，相册，活动" 函数
 * 参数说明:
 * commentTopList_URL  : 显示最新评论URL，与minNum对应,默认 3 条
 * commentList_URL  : 显示全部评论URL
 * commentDEL_URL ： 评论删除URL
 * comment_URL : 评论发送URL
 * praise_URL : 文章 "赞" URL
 * praiseDEL_URL ： 取消文章 "赞" URL
 * praiseSon_URL : 评论 "赞" URL
 * praiseSonDEL_URL :取消 评论 "赞" URL
 * userlistFather_URL : 文章赞的用户列表URL  占位符{0}为该文章ID的位置
 * userlistSon_URL : 评论赞的用户列表URL  占位符{0}为该评论ID的位置
 * minNum : 最新评论数，要与后台commentTopList_URL对应，默认显示 3 条
 * UID : 当前用户ID (必传)
 * userName : 当前用户名 (必传)
 * avatar:当前用户头像 (必传)
 * userPageUrl : 当前用户主页 (必传)
 * pageType : 其它参数 如相册模式
 * addCallback:func(item) 添加回调，参数为添加项 jquery object
 * deleteCallback:func(item) 删除回调，参数为删除项 jquery object
 * praiseCallback : func(commentID) "赞" 回调，参数为当前文章ID
 * delPraiseCallback : func(commentID) "赞"取消 回调，参数为当前文章ID
 * praiseSonCallback : func(item,commentID) "赞" 回调，参数为当前项及文章ID,item为jquery html对象
 * delPraiseSonCallback : func(item,commentID) "赞"取消 回调，参数为当前项及文章ID,item为jquery html对象
 * otherButtons:"" 其它按钮  
 * buttonsPrev:true  前插其他按钮
 * isWeb是否是网页
 * 动态方法： commentAdd 
 * 示例:
 var con=$(".blogComment").commentEasy({
		commentTopList_URL:"/app/modules/home/views/album/tianxbAjax.php?act=getTopList",
		commentList_URL:"/app/modules/home/views/album/tianxbAjax.php?act=getAllList",
		commentDEL_URL:"/app/modules/home/views/album/tianxbAjax.php?act=deleteMsg",
		comment_URL:"/app/modules/home/views/album/tianxbAjax.php?act=sendMsg",
		praise_URL:"/app/modules/home/views/album/tianxbAjax.php?act=like",
		praiseDEL_URL:"/app/modules/home/views/album/tianxbAjax.php?act=delLike",
		praiseSon_URL:"/app/modules/home/views/album/tianxbAjax.php?act=like",
		praiseSonDEL_URL:"/app/modules/home/views/album/tianxbAjax.php?act=delLike",
		userlistFather_URL:"/10000422/home/album/get_like_lists/{0}",
		userlistSon_URL:"/10000422/home/album/get_like_lists/{0}",
		minNum:3,
		UID:10009,
		userName:"刘德华",
		avatar:"/images/temp/testPhoto_a0.jpg",
		userPageUrl:"/",
		pageType:"",
		isShowPaiseName:false
	});
	con.commentAdd(".comment_easy145");
* 前端固定式简约调用默认以上参数已赋值使用下面方法
$(function(){
	 $(".blogComment").commentEasy();
});
或
$(function(){
	var con = $(".blogComment").commentEasy();
	//ajax操作..........
	con.commentAdd(".blogComment123");
});
 程序建议传
 $(".blogComment").commentEasy({
		UID:10009,
		userName:"刘德华",
		avatar:"/images/temp/testPhoto_a0.jpg",
		userPageUrl:"/"
 });
 
 <div class="blogComment" commentObjId="123456" pageType="album"></div>
 其他注意事项:
 1. 除发送外，其它ajax都采用GET方式,
 2. URL参数评论ID为comment_ID,评论内容comment_content,其它参数加pageType
 3. 动态添加的内容需调用commentAdd添加当前的动添加项容器的jquery对象
 **/
(function($){
	var miscpath = CONFIG['misc_path'];
	if (typeof( WEB_ID)=="undefined"){
		WEB_ID="";
	};
	var commentHTML='<div class="comment_easy">\
					  <ul class="comment_title"></ul>\
					  <ul class="comment_content">\
						<li class="comment_corner clearfix" style="display: none;"></li>\
						<li class="comment_praise_father" style="display: none;"><em>您觉得这挺赞的！</em></li>\
						<li class="comment_view" style="display: none;"><span class="comment_more"></span><a href="javascript:;"  class="commentAll"></a></li>\
						<li class="comment_list" style="display: none"><ul></ul></li>\
						<li class="comment_form clearfix"  style="display: none"><img src="" alt="" /><div class="comment_form_input"><input type="text" value="留段话吧..." rel="留段话吧..." class="fieldWithText" maxlength="140"/><div class="tc6 comment_form_tip hide">提示:按回车键（enter）发表评论。</div></div></li>\
					  </ul>\
					</div>';
	var CommentEasy = function(options){
		var defaults = {};
		this.settings = $.extend({},defaults,options);
		var _self = this;
		var commentData = _self.settings.ops;
        var _el=$(_self.settings.elem),
            _commentID=_el.attr("commentobjid")||'',
            _pageType=_el.attr("pagetype")||'',
            _action_uid=_el.attr("action_uid")||'',
            _web_id='';
            _web_id_temp=!!(CONFIG['web_id']>>0)?CONFIG['web_id']:_el.attr('web_id')||0,//变态的配置
            _replyFilter='';//存放 回复评论时的默认值。(回复xxx:)
		var commentID = _el.attr("commentObjId")|| '';
        var __id=_el.closest('li').attr('id');
        _web_id=_web_id_temp;
		var ajaxOptions = {type:"GET",dataType:"jsonp",cache:false};
        var _uid='';
        var comment_uid='';//存放 评论回复uid;
        var __page=1;//评论当前分页数
        var isFavoriteTit=['您已收藏','点击收藏','不能对自己收藏'];
        var andMore=['查看更多记录'];
        var praise=['赞','取消赞','您觉得这挺赞的','觉得这挺赞的朋友'];
        var ignoreType='ask,event,web_event,web_ask';//忽略类型 不显示 推荐栏
		//初始化开始
		this.init = function(data){
            if(commentData.relay&&!data.pageType.hasString(ui.pagetype.timeline,!0)) return;//时间线 处理
            if(!commentData.relay&&!data.pageType.hasString(ui.pagetype.module,!0)) return;//模块 处理
			var _content = $(commentHTML);
            if(!commentData.pageType.hasString(ui.pagetype.coll)) commentData.otherButtons='';
			var _html = commentData.buttonsPrev ? _content.find("ul.comment_title").prepend(commentData.otherButtons+'<li><strong>·</strong></li>') : _content.find("ul.comment_title").append(commentData.otherButtons);
            var relayObj=[];
			//if (commentData.relay && !$(_self.settings.elem).attr("noForward")) {
            /**
             * 显示分享和收藏 时间线 暂时 不显示分享和收藏 马正洁
             */
            var issue=_el.attr('action_uid'),
                _uid=CONFIG['u_id'],
                isFavorite=data.isFavorite?'hasColl':'',
                _isFavoriteTit=data.isFavorite?isFavoriteTit[0]:isFavoriteTit[1],
                share_count=data.share_count || 0;
            //判断 是否是自己 通过action_uid  和 CONFIG['u_id']对比
            (issue!=_uid)?'':(_isFavoriteTit=isFavoriteTit[2],isFavorite='hasColl');
            /**
             * 处理 分享和收藏的显示 状态 分享只在模块中对对自己隐藏， 收藏对自己隐藏
             */
            var __share='<li><strong>·</strong></li><li class="sendForward">{0}<span class="forward_count '+(share_count>0?'cursorPointer':'')+'">('+share_count+')</span></li>',
                __coll='<li class="showForward showColl"><a href="javascript:void(0)" title="'+_isFavoriteTit+'" class="'+isFavorite+'"><span class="forwardNum">'+data.favoriteNums+'</span></a></li>',
                __zang='<li><a class="comment_praiseFather" href="javascript:;">赞</a></li>',
                __comment='<li><strong>·</strong></li><li><a class="comment_send" href="javascript:;">评论</a></li>';
            if(!commentData.relay&&data.pageType.hasString(ui.pagetype.share.module)){
                if(commentData.hasShare)
                    issue==_uid?relayObj.push(_format(__share,'分享')):relayObj.push(_format(__share,'<a isTimeline="0">分享</a>'));
                if(commentData.hasColl)
                    relayObj.push(__coll);
            }
            if(commentData.relay&&data.pageType.hasString(ui.pagetype.share.timeline)){
                if(commentData.hasShare)
                    relayObj.push(_format(__share,'<a isTimeline="1">分享</a>'));
                if(data.pageType.hasString(ui.pagetype.coll)){
                    if(commentData.hasColl)
                            relayObj.push(__coll);
                }
            }
            var comment_title=_content.find("ul.comment_title");
            if(commentData.hasZang){
                comment_title.append(__zang);
            }
            if(commentData.hasComment){
                comment_title.append(__comment);
            }
            relayObj=$(relayObj.join(''));
            comment_title.append(relayObj);
            _self.settings.elem.append(_content).attr("complete",true);
			if(commentData.relayCallback){
                var _pageType=commentData.pageType;
                var _type=_el.closest('li').attr('type')||'';
                var isweb=_pageType.hasString('web');
                var __commentID=_el.closest('li').attr('id');
                var ctime=_el.attr('ctime')||'';
                if(__commentID&&!isNaN(__commentID))_commentID=__commentID;
                //时间线 pageType类型的 区分.
                if(commentData.relay&&!isweb){
                    _pageType='topic';
                }else if(commentData.relay&&isweb){
                    _pageType='web_topic';
                }
				$("li.sendForward a",_self.settings.elem).unbind('click').bind("click",function(e){
					commentData.relayCallback($(this),'pageType='+_pageType+'&topic_type='+_type+'&action_uid='+commentData.action_uid+'&comment_ID='+_commentID+'&web_id='+_web_id+'&ctime='+ctime);
				});
			}
			$("a.comment_send",_self.settings.elem).unbind('click').bind('click',function(){
                //时间线 隐藏评论和评论框 2012-08-01 考虑时间线高度计算问题。
               if($("li.comment_list li",_self.settings.elem).length>0)
                    $("li.comment_list",_self.settings.elem).show();
                $("li.comment_form",_self.settings.elem).show();
                if(commentData.relay&&commentData.minNum<($(".comment_count",_self.settings.elem).html()).replace(/\(|\)/g,'')){
                    $("li.comment_view",_self.settings.elem).show();
                }
				$("div.comment_form_input input",_self.settings.elem).trigger('click');
			});
			$("li.comment_form > img",_self.settings.elem).attr("src",commentData.avatar);
			addLoading($("ul.comment_content",_self.settings.elem),"prepend");
			if(data) {
				_self.initList(data);
			}else{
				var xhr = $.ajax(
					$.extend({},ajaxOptions,{
						url:commentData.commentTopList_URL,
                        dataType:'jsonp',
						data:{
							comment_ID:commentID,
							pageType:commentData.pageType,
							action_uid:commentData.action_uid
						}
					})
				);
				xhr.then(_self.initList);
				xhr.fail(ajaxError);
				xhr.then(function(result){
					if(commentData.readyCallback){
						commentData.readyCallback(result,_self.settings.elem);
					}
				});
			}
			//绑定 "赞"
			$("a.comment_praiseFather",_self.settings.elem).unbind('click').bind("click",_self._agreeFather);
            $("span.praise_count",_self.settings.elem).unbind('click').bind("click",_self._praise_count);
            $("span.forward_count",_self.settings.elem).unbind('click').bind("click",_self._share_count);
            $("span.comment_count",_self.settings.elem).unbind('click').bind("click",_self._comment_count);
		};
        /**
         * 点击赞 数字
         * @param ele
         * @private
         */
        this._praise_count=function(ele){
            var self=($(this).html().replace(/\(|\)/g,''))>>0;
            if(self)
            praise_userListPOPUP.call(this,commentData.userlistSon_URL+'&pageType='+commentData.pageType,commentID);
        };
        this._comment_count=function(ele){
            var comment_list=$("li.comment_list li",_self.settings.elem);
            $('a.commentAll',_self.settings.elem).trigger('click');
            if(comment_list.length>0)
                $("li.comment_list",_self.settings.elem).show();
            $("li.comment_form",_self.settings.elem).show();
            if(comment_list.length!=($(".comment_count",_self.settings.elem).html()).replace(/\(|\)/g,'')){
                $("li.comment_view",_self.settings.elem).show();
            }

        };
        this._share_count=function(ele){
            var self=($(this).html().replace(/\(|\)/g,''))>>0;
            var isweb=_pageType.hasString('web');
            /**
             * 时间线 pageType类型的 区分.
             */
            if(commentData.relay){
                if(!isweb)
                    _pageType='topic';
                else _pageType='web_topic';
                commentID=__id;
            }
            if(self)
                forward_userListPOPUP.call(this,commentData.forward_URL+'&pageType='+_pageType+'&comment_ID='+commentID+'&action_uid='+_action_uid);
        };
		this.initList = function(result) {
			removeLoading($("ul.comment_content",_self.settings.elem));
			if(result) {
				if(result.state) {
					var data = result.data;
					var list = _self.getCommentList(data);
					if(data.length != 0) {
						$("li.comment_list ul",_self.settings.elem).html(list);
					} else{
						$("li.comment_list ul",_self.settings.elem).parent().hide();
						if(!commentData.isShow){
							$("li.comment_form",_self.settings.elem).hide();
						}
					}
					var count = result.count || 0;
					if(count > commentData.minNum){
                        $("li.comment_view",_self.settings.elem).find("a").html(andMore[0]);
					}
					_self.greePeople(result);
					// 添加评论的数量（马正杰修改）
                    var __comment_send=$("a.comment_send",_self.settings.elem),
                        __comment_count=__comment_send.parent().find('.comment_count');
                    if(!__comment_count.length)
                        __comment_send.after('<span class="comment_count '+(count>0?'cursorPointer':'')+'">('+count+')</span>');
                    else __comment_count.html('('+count+')');
				}else{
					alert(result.info);
				}
			}
			_self._initContent();
			$('a.commentAll',_self.settings.elem).one('click',_self.getAllCommentList);
			$('li.comment_praise_father',_self.settings.elem).delegate('a.other','click',function(){
				praise_userListPOPUP.call(this,commentData.userlistFather_URL,commentID);
				return false;
			});

			$('li.comment_list',_self.settings.elem).delegate('a.comment_praiseSonNum','click',function(e){
				var cid = $(e.currentTarget).closest("li.clearfix").children("input:hidden").val();
				praise_userListPOPUP.call(this,commentData.userlistSon_URL+'&pageType=comment',cid);
				return false;
			});
            /**
             * 处理 模块收藏
             */
			$('li.showForward',_self.settings.elem).delegate('a','click',function(event){
				//forward_userListPOPUP.call(this,commentData.forward_URL,commentID);
                var isSend=this.isSend = this.isSend || false;
                var self=$(this);
                if(isSend) {
                    return false;
                }
                if(self.hasClass('hasColl'))return false;
                $.ajax({
                    url:mk_url('main/favorite/addFavorite'),
                    dataType:'jsonp',
                    data:{'object_id':commentID,'action_uid':commentData.action_uid,'page_type':_pageType,'web_id':_web_id},
                    success:function(q){
                        isSend=false;
                        if(q.status){
                            self.addClass('hasColl');
                            self.find('span').html(q.data);
                            self.attr('title',isFavoriteTit[0]);
                        }else{
                            alert(q.info);
                        }
                    }
                })
				return false;
			});
            /**
             * 时间线 隐藏评论和评论框 2012-08-01 考虑时间线高度计算问题。
             */
            if(!commentData.relay){
                if($("li.comment_list li",_self.settings.elem).length>0)
                    $("li.comment_list",_self.settings.elem).show();
                $("li.comment_form",_self.settings.elem).show();
                if($("li.comment_list li",_self.settings.elem).length!=($(".comment_count",_self.settings.elem).html()).replace(/\(|\)/g,'')){
                    $("li.comment_view",_self.settings.elem).show();
                }
            }
		};
		// 个人"赞"
		this.greePeople = function(result){
			if(parseInt(result.greeCount) > 0 &&result.isgree){
				var _praise = $("li.comment_praise_father",_self.settings.elem).show();
				var userlist = [];
                result.greepeople&&$.each(result.greepeople,function(i,d){
					if(d.uid != commentData.UID){
						userlist.push('<a href="' + d.url + '">' + d.username + '</a>');
					}
				});
				if(result.isgree){
					$("a.comment_praiseFather",_self.settings.elem).html(praise[1]);
					var nowNum = result.greeCount - 1;
					if(result.greeCount == 1){
                        _praise.find("em").html(praise[2]);
					}
				}
			}
			// 为“赞”添加数字（陈海云添加）
			if($("a.comment_praiseFather",_self.settings.elem).next().hasClass("praise_count")) {
				$("a.comment_praiseFather",_self.settings.elem).next().html('(' + (result.greeCount || 0) + ')');
			} else {
				$("a.comment_praiseFather",_self.settings.elem).after('<span class="praise_count '+(result.greeCount>0?'cursorPointer':'')+'">(' + (result.greeCount || 0) + ')</span>');
			}
		};
		// 评论"赞"
		this._agreeSon=function(elem){
			var jqCurrentTarget = $(elem.currentTarget);
			var text = jqCurrentTarget.html();
			var jqdelegateTarget = $(elem.delegateTarget);
			var _that = this;

			this.isSend = this.isSend || false;
			if(this.isSend) {
				return false;
			}
			if(text ==praise[0]){
				//发送赞
				var _ajaxOptions = $.extend({},ajaxOptions,{
					url:commentData.praiseSon_URL.replace(/pageType=undefined/g,''),
                    dataType:'jsonp',
					data:{"comment_ID":jqdelegateTarget.children("input:hidden").val(),'pageType':'comment',"action_uid":commentData.action_uid},
					complete:function(){
						_that.isSend=false;
					}
				});
				var xhr = $.ajax(_ajaxOptions);
				xhr.then(function(result){
					if(!!result.status){
						jqCurrentTarget.html(praise[1]);
						var oldCount = parseInt($("a.comment_praiseSonNum",jqdelegateTarget).html()) || 0;
						var greeCount=oldCount+1;
						$("span.comment_praise_son",jqdelegateTarget).html('<strong> · </strong><a class="comment_praiseSonNum"> '+greeCount+' </a>').show();
						if($.isFunction(commentData.praiseSonCallback)){
							commentData.praiseSonCallback(jqdelegateTarget,commentID);
						}
					}else{
						alert(result.info);
					}
				},ajaxError);
			}else{
                var __url=commentData.praiseSonDEL_URL.replace(/pageType=undefined/g,'pageType=comment');
				var xhr = $.ajax($.extend({},ajaxOptions,{
					url:__url,
					dataType:"jsonp",
					data:{"comment_ID":jqdelegateTarget.children("input:hidden").val(),"action_uid":commentData.action_uid},
					complete:function(){
						_that.isSend = false;
					}
				}
				));
				xhr.then(function(result){
					if(result){
						if(!!result.status){
							jqCurrentTarget.html(praise[0]);
							var oldCount = parseInt($("a.comment_praiseSonNum",jqdelegateTarget).html()) || 0;
							var greeCount=oldCount-1;
							if(greeCount>0)
							$("a.comment_praiseSonNum",jqdelegateTarget).html(" "+greeCount+" ");
							else{
								$("a.comment_praiseSonNum",jqdelegateTarget).html("");
								$("span.comment_praise_son",jqdelegateTarget).hide();
							}
							if($.isFunction(commentData.delPraiseSonCallback)){
								commentData.delPraiseSonCallback(jqdelegateTarget,commentID);
							}

						}else{
							alert(result.info);
						}
					}
				},ajaxError);
			}

			_that.isSend = true;
			return false;
		};
		//文章"赞"
		this._agreeFather = function(elem){
			var jqCurrentTarget = $(elem.currentTarget);
			var _that = this;
            var praise_count=$("span.praise_count",_self.settings.elem);
			if($(this).text()==praise[0]){
				//发送赞
				$.ajax($.extend({},ajaxOptions,{
					//type:commentData.dataType,
                    dataType:'jsonp',
					url:commentData.praise_URL,
					data:{comment_ID:commentID,action_uid:commentData.action_uid,msgname:commentData.msgname,msgurl:commentData.msgurl},
					complete:function(result){
                        _that.isSend=true
                    }
				})).then(function(result){
                        if(result.status){
                            var jqPaise = $("li.comment_praise_father",_self.settings.elem);
                            if((result.data.greeCount>>0)>0){
                                jqCurrentTarget.html(praise[1]);
                                praise_count.addClass('cursorPointer');
                            }else{
                                praise_count.removeClass('cursorPointer');
                            }
                            $("li.comment_praise_father",_self.settings.elem).show();
                            _self.greePeople(result.data);
                            if($.isFunction(commentData.praiseCallback))
                                commentData.praiseCallback(commentID);
                        }else{
                            alert(result.info)
                        }
                });
			}else if($(this).text()==praise[1]){
				//取消
				$.ajax($.extend({},ajaxOptions,{
					url:commentData.praiseDEL_URL,
                    dataType:'jsonp',
					data:{"comment_ID":commentID,"action_uid":commentData.action_uid,msgname:commentData.msgname,msgurl:commentData.msgurl},
					complete:function(result){
						_that.isSend=true;
					}
				})).then(function(result){
                        if(result.status){
                            jqCurrentTarget.text(praise[0]);
                            var greeCount=result.greeCount;//$("li.comment_praise_father i",_self.settings.elem).html()||0;
                            if(!greeCount){
                                $("li.comment_praise_father",_self.settings.elem).hide();
                                praise_count.removeClass('cursorPointer');
                            }else{
                                praise_count.addClass('cursorPointer');
                            }
                            _self.greePeople(result.data);
                            if($.isFunction(commentData.delPraiseCallback))
                                commentData.delPraiseCallback(commentID);
                        }else{
                            alert(result.info);
                        }
                    });;
			}else{
				alert("发生未知错误。");
			}
		};
		//格式化列表
		this.getCommentList = function(data){
				var fragment = document.createDocumentFragment();
				var li,temp='';
            var target='';
            if(ui.utils.inIframe()){
                target='target="_parent"'
            }
				for(var i = data.length - 1; i >= 0; i --){
					temp = data[i];
                    //if(data){
                        li = document.createElement("li");
                        li.className = "clearfix";
                        li.setAttribute('uid',temp.uid);
                        var url = temp.url;
                        var ui_closeBtn = temp.isdel ?'ui_closeBtn':'';
                        var ui_replyBtn = temp.isdel&&!temp.isReply ?'':'<span class="repeatBtn">回复</span>';
                        li.innerHTML = '<a '+target+' href="' + url + '"><img src="' + temp.imgUrl + '"  alt="' + temp.name + '"/></a><span class="ui_closeBtn_box"><i class="' + ui_closeBtn + ' png"></i></span>'+ui_replyBtn+'<span class="comment_text"><strong><a '+target+' href="' + url + '" alt="' + temp.name + '" >' + temp.name + '</a>&nbsp;&nbsp;</strong> <span>' + temp.content + '</span><br /><span class="tc_6">' + temp.time + '</span><strong> · </strong><a class="comment_praiseSon" href="javascript:;">' + (temp.isgree?praise[1]:praise[0]) +'</a><span class="comment_praise_son" style="display:'+(temp.greeNum>0?'inline':'none')+'"><strong> · </strong><a class="comment_praiseSonNum"> '+temp.greeNum+' </a></span></span><input type="hidden" value="'+temp.cid+'" />';
                        fragment.appendChild(li);
                   // }
				}
				return fragment;
		};
        /**
         * 查看所有评论
         */
		this.getAllCommentList = function(e){
            var _this=$(this);
            addLoading(_this.parent());
			var target = this;
            __page =parseInt($(target).attr("page"))||1;
            /**
             * 修复分页时 删数据小于最小分页数时的 请求错误问题
             * @type {*}
             * @private
             */
            var __c=$("a.comment_send",_self.settings.elem).next().text().replace(/\(|\)/g,'');
            if(__page>1&&__c<=commentData.pageSize){__page=1;$(target).attr("page",'1')}

			var xhr = $.ajax($.extend({},ajaxOptions,{
					  	url:commentData.commentList_URL,
                        dataType:'jsonp',
					 	data:{comment_ID:commentID,pageType:commentData.pageType,pageIndex:__page,action_uid:commentData.action_uid}
			 		}
			 ));
			 xhr.done(function(result){
                 if(result.status){
                     var data=result.data,
                         datas=result.data.data,
                         num = data.length,
                         sumPage = data.count % commentData.pageSize > 0 ? Math.floor(data.count/commentData.pageSize) + 1 : data.count/commentData.pageSize;
                    if(__page==1)
                        $("li.comment_list ul",_self.settings.elem).html(_self.getCommentList(datas));
                    else
                        $("li.comment_list ul",_self.settings.elem).prepend(_self.getCommentList(datas));
                    if(sumPage>__page){
                        $(target).text(andMore[0]);
                        $(target).attr("page",__page+1);
                        $(target).one('click',_self.getAllCommentList);
                        removeLoading($(target).parent());
                        $("span.comment_more",$(target).parent()).show().html("显示 <i>"+data.count+"</i> 个中的 <i>"+(__page*commentData.pageSize)+"</i> 个");
                    }else
                        $(target).parent().hide();
                     removeLoading(_this.parent());
                 }
			  }).fail(ajaxError);
			 xhr.then(function(){
				var itemArr = $("li.comment_list li",_self.settings.elem);
				itemArr.each(function(){
					_self._bindEvent($(this));
				});
			 });
		};
		//初始化内容
		this._initContent = function(){
			var itemArr = $("li.comment_list li",_self.settings.elem);
			itemArr.each(function(){
				_self._bindEvent($(this));
			});
			$("li.comment_form input.fieldWithText div.comment_form_tip",_self.settings.elem).show();
			//发送键绑定
			$("li.comment_form input.fieldWithText",_self.settings.elem).unbind('keydown').bind('keydown',function(event){
                event=window.event||event;
				if(event.keyCode == 13){
					_self.add($(this));
				}
			}).unbind('click').bind('click',function(){
                comment_uid='';//清空uid
				if($.browser.msie)
				{
					$(this).width($(this).closest(".comment_form").width()-42);
				}
				$(this).prev("img").show().end().nextAll("div.comment_form_tip").show();
				if($(this).val()=="" || $(this).val()==$(this).attr("rel")){
					$(this).css({color:"#333333"}).val("").closest(".comment_form_input").prev("img").show();
				}
                    $(this).focus();
			}).blur(function(){
				if($(this).val()=="" || $(this).val()==$(this).attr("rel")){
					$(this).prev("img").show().end().nextAll("div.comment_form_tip").hide();
					$(this).css({color:"#999999"}).val($(this).attr("rel")).closest(".comment_form_input").prev("img").hide();
					{
						$(this).width("99%");
					}
				}
			});
		};
		//绑定事件项
		this._bindEvent = function(elem){		
			//绑定删除
			var closeArr=$("i.ui_closeBtn",elem);
			closeArr.hide().hover(function(){
				$(this).css("background-position","0px -15px");
			},function(){
				$(this).css("background-position","0px 0px");
			}).click(function(){
				_self._del(elem);
			});
			elem.hover(function(){
				$("i.ui_closeBtn",this).show();
                $("span.repeatBtn",this).show();
			},function(){
                $("i.ui_closeBtn",this).hide();
                $("span.repeatBtn",this).hide();
			});
			//绑定 "赞"
			elem.delegate("a.comment_praiseSon",'click',_self._agreeSon);
            //评论回复 触发
            $("span.repeatBtn",elem).unbind('click').bind('click',function(event){
                var p=$(this).parents('ul.comment_content').find('input.fieldWithText'),
                    _li=$(this).parent(),
                    _name=_li.find('.comment_text a').eq(0).text();
                p.trigger('click');
                comment_uid=_li.attr('uid');
                _replyFilter='回复'+_name+':';//设置默认回复文字
                p.val(_replyFilter);
            });
		};
		//删除评论
		this._del = function(elem){
			popUp({
					width:357,
					title:"删除评论",
					content:"<strong style='display:block; padding:15px; em-size:14px;'>您确定要删除该条评论吗？</strong>",
					buttons:'<span class="popBtns blueBtn callbackBtn">确定删除</span><span class="popBtns closeBtn">取消</span>',
					callback:function(){
						var xhr = $.ajax($.extend({},ajaxOptions,{
							url:commentData.commentDEL_URL,
                            dataType:'jsonp',
                            data:{"comment_ID":$(elem).children("input:hidden").val()}
							})
						);
						xhr.then(function(result){
							if(result.status){
								$(elem).remove();
								top.$.closeSubPop();
								if($.isFunction(commentData.deleteCallback)){
									commentData.deleteCallback($(elem));
								}
								var nowCount= parseInt($("li.comment_view i",_self.settings.elem).html())||0;
								nowCount--;
								if(nowCount>0){
									$("li.comment_view i",_self.settings.elem).html(nowCount);
								}else{
									$("li.comment_view i",_self.settings.elem).hide();
								}
                                if($("li.comment_list li",_self.settings.elem).length==0){
                                     $("li.comment_list",_self.settings.elem).hide();
                                }
								// 删除评论后重新计算评论的数量（陈海云添加）
								if($("a.comment_send",_self.settings.elem).next().hasClass("comment_count")) {
									var commentCount = $("a.comment_send",_self.settings.elem).next().text().replace("(","").replace(")","") || 0;
									commentCount = parseInt(commentCount) - 1;
									if(commentCount < 0) {
										commentCount = 0;
									}
									$("a.comment_send",_self.settings.elem).next().text("("+commentCount+")");
								}
							}else{
								alert(result.info);
							}
						},ajaxError);
					}
				});
		};
		//添加评论
		this.add = function(InputMsg){
			var msg = InputMsg.val().replace(new RegExp(_replyFilter,'g'),'');
            var target='';
			InputMsg.val(msg);
			if($.trim(msg)==''){
				return false;
			}
			InputMsg.val('').blur();
            if(ui.utils.inIframe()){
                target='target="_parent"'
            }
            var item = $('<li class="clearfix"><a '+target+' alt="' + commentData.userName + '" href="' + commentData.userPageUrl + '"><img alt="" src="' + commentData.avatar + '"/></a><span class="ui_closeBtn_box"><i class="ui_closeBtn png" style="display: block;"></i></span><span class="comment_text"><strong><a  '+target+'  alt="' + commentData.userName + '" href="' + commentData.userPageUrl + '">' + commentData.userName + '</a>&nbsp;&nbsp;</strong> <span class="comment_msg"></span><br><span class="tc_6">' + formatDateString(new Date()) + '</span><strong> · </strong><a href="javascript:;" class="comment_praiseSon" style="display:none;">'+praise[0]+'</a><span style="display:none" class="comment_praise_son"><strong> · </strong><a class="comment_praiseSonNum"> 0 </a></span></span><input type="hidden" value=""/></li>');
			sendMsgAjax(item,msg);//发送添加评论请求
		};
        //发送添加评论请求
        function sendMsgAjax(item,msg){
            var xhr = $.ajax($.extend({},ajaxOptions,{
                    dataType:'jsonp',
                    url:commentData.comment_URL,
                    data:{comment_content:msg,comment_ID:commentID,action_uid:commentData.action_uid,uid:comment_uid,msgname:commentData.msgname,msgurl:commentData.msgurl}
                }
            ));
            xhr.then(function(result){
                var data=result.data;
                if(result.status){
                    $("li.comment_list ul",_self.settings.elem).append(item);
                    if(data.msg)$("span.comment_msg",item).html(data.msg);
                    $("li.comment_list ul",_self.settings.elem).parent().show();
                    item.attr('uid',data.uid||'');
                    $("a.comment_praiseSon",item).show();
                    $("span.tc_6",item).html("刚刚");
                    $("input:hidden",item).val(data.cid);
                    _self._bindEvent(item);
                    item.removeClass("enable");
                    if($.isFunction(commentData.addCallback)){
                        commentData.addCallback(item);
                    }
                    if($("a.comment_send",_self.settings.elem).next().hasClass("comment_count")) {
                        var commentCount = $("a.comment_send",_self.settings.elem).next().text().replace("(","").replace(")","") || 0;
                        commentCount = parseInt(commentCount) + 1;
                        $("a.comment_send",_self.settings.elem).next().text("(" +commentCount+ ")");
                    }
                }else{
                    alert(result.info);
                    tryAgain(item,msg);
                }
            },function(){
                tryAgain(item,msg);
            });
        };
        //再次发送添加评论请求
        function tryAgain(item,msg){
            var jqTryAgain = $('<a href="javascript:;">重试</a>');
            $("span.tc_6",item).html('<span class="error"></span>无法发布评论。').append(jqTryAgain);
            item.addClass("enable");
            jqTryAgain.bind('click',function(){
                sendMsgAjax(item,msg);
            });
        };
		//赞列表
		var praise_userListPOPUP = function(userlist_URL,cid){//获得用户列表的AJAX函数
			var comment_userList_url=userlist_URL.replace(/\{0\}/,cid);
            top.$(this).subPopUp({
                mask:true,
                maskMode:true,
                width:467,
                title:praise[3],
                content:'<iframe width="445" height="353" scrolling="no" frameborder="0" src="'+comment_userList_url+'"></iframe>',
                buttons:'<span class="popBtns closeBtn">关闭</span>'
            });
		};
        //评论列表
        var comment_userListPOPUP = function(userlist_URL,cid){//获得用户列表的AJAX函数
            var comment_userList_url = userlist_URL.replace(/\{0\}/,cid);
            top.$(this).popUp({
                width:482,
                title:"评论它的人",
                content:'<iframe width="460" height="353" scrolling="no" frameborder="0" src="'+comment_userList_url+'"></iframe>',
                buttons:'<span class="popBtns closeBtn">关闭</span>'
            });
        };
		//分享列表
		var forward_userListPOPUP = function(userlist_URL,cid){//获得用户列表的AJAX函数
			top.$(this).popUp({
				width:482,
				title:"分享它的人",
				content:'<iframe width="460" height="353" scrolling="no" frameborder="0" src="'+userlist_URL+'"></iframe>',
				buttons:'<span class="popBtns closeBtn">关闭</span>'
			});
		};
		//异步失败处理
		function ajaxError(err){
			/*if(err.readyState==4||err.readyState==2)
			alert("网络连接失败,请稍后重试!");
			*/
		};		
		//弹窗函数
		function popUp(args){
			top.$("body").subPopUp($.extend({
					mask:true,
					maskMode:false
			},args));
		};
	};
	//格式化时间日期
	function formatDateString(date){
		var d=new Date(date);
		var s="";
		s+=d.getFullYear()+"-";
		s+=(d.getMonth()+1)+"-";
		s+=d.getDate()+" ";
		s+=d.getHours()+":";
		s+=d.getMinutes();
		return s;
	};
	//加载
	function addLoading(jqContent,position){
		var loading='<img src="'+miscpath+'img/system/icon-loading.gif" class="load" width="16px" height="11px" alt="loading..."/>';
		switch(position){
			case "prepend":{
				jqContent.prepend(loading);		
			}break;
			default:{				
				jqContent.append(loading);	
			}break;
		}
	};
	//移除load
	function removeLoading(jqContent){
		jqContent.find("img.load").remove();
	};
	function strip_tags(input , allowed){
		return input;

		allowed = (((allowed||"") + "").toLowerCase().match(/<[a-z][a-z0-9]*>/g)||[]).join('');
		var tags =/<\/?([a-z][a-z0-9]*)\b[^>]*>/gi, 
			commentsAndPhpTags=/<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?/gi;
		return input.replace(commentsAndPhpTags,'').replace(tags,function($0,$1){
			return allowed.indexOf('<'+$1.toLowerCase()+'>')>-1?$0:'';
		});
	}
	//插件
	$.fn.extend({
		//评论插件入口
		commentEasy:function(options){
            var __web_id=CONFIG['web_id'];
			var _default = {
				commentTopList_URL:mk_url("main/comment/get_stat",{web_id:__web_id,pageType:$(this).attr("pageType")}),
				commentTopListAll_URL:mk_url("main/comment/get_stat_all"),
				minNum:3,
				UID:CONFIG['u_id'],
				userName:CONFIG['u_name'],
				avatar:CONFIG['u_head'],
				userPageUrl:'/www_duankou/main/'+CONFIG['action_dkcode']+'/index/profile',
				pageType:$(this).attr("pageType"),
				isShowPaiseName:false,
				deleteCallback:null,
				addCallback:null,
				praiseCallback:null,
				delPraiseCallback:null,
				praiseSonCallback:null,
				delPraiseSonCallback:null,
				onLoadCallback:null,
				otherButtons:"",
				buttonsPrev:false,
				action_uid:$(this).attr("action_uid"),
				data:null,
				isOnlyYou:false,
				isShow:true,
				pageSize:5,//评论分页 基数
				readyCallback:null,
				relayCallback:null,
				relay:false,
				msgname:$(this).attr("msgname"),
				msgurl:$(this).attr("msgurl"),
				isWeb:false,
				comment_path:null,
				dataType:'jsonp',
                hasColl:!0,
                hasShare:!0,
                hasZang:!0,
                hasComment:!0,
                ishome:!1 //区分首页
			};
			var ajaxOptions = {type:"GET",dataType:"jsonp",cache:false};
			var commentIDList = [];
			var pageTypeList = [];
			var arrDiv = [];
			var tidList = [];
            var action_uid=[];
            var _this=null;
            var ___web_id=[];

			this.each(function(){
                _this=$(this);
				if(!_this.hasClass("hasComment")){
					_default = $.extend(false,_default,{pageType:_this.attr("pageType")});
					_default = $.extend(_default,options);
                    commentIDList.push(_this.attr("commentobjid"));
                    pageTypeList.push(_this.attr("pagetype"));
                    action_uid.push(_this.attr("action_uid"));
                    ___web_id.push(_this.attr("web_id")||'');
                    arrDiv.push(_this);
                    tidList.push(_this.closest("li").attr("id")||"");
                    _this.addClass("hasComment");
				}
			});
			var comment_path = _default.comment_path || "comment";
            _default.commentTopListAll_URL = mk_url("main/"+comment_path+"/get_stat_all");
			ajaxOptions.dataType =_default.dataType;
            var xhr = $.ajax(
                    $.extend({},ajaxOptions,{
                        url:_default.commentTopListAll_URL,
                        dataType:"jsonp",
                        data:{comment_ID:commentIDList.join(","),pageType:pageTypeList.join(","),tid:tidList.join(","),action_uid:action_uid.join(','),web_id:!(__web_id>>0)?___web_id[0]:__web_id}
                    })
            ).then(function(result){
                    if(result.status){
                        result=result.data;
                        var l= result.length%10 ? parseInt(result.length/10)+1:parseInt(result.length/10);
                        var s =0,i=0;
                        setTimeout(function(){
                            if (s <= result.length){
                                $.each(result.slice(s,s+10),function(k,d){
                                    var comment_ID = this.comment_ID;
                                    var _thisDiv=null;
                                    for (var j =0,len = arrDiv.length; j<len ;j++){
                                        if( arrDiv[j].attr("commentObjId") == comment_ID &&arrDiv[j].attr("pagetype") == this.pageType){
                                            _thisDiv = arrDiv[j];
                                        }
                                    }
                                    if(_thisDiv){
                                        var ptype=_thisDiv.attr("pageType");
                                        var tid=_thisDiv.closest("[name='timeBox']").attr("id")||"";
                                        var ctime=_thisDiv.attr("ctime")||"";
                                        var web_id=!!(CONFIG['web_id']>>0)?CONFIG['web_id']:_thisDiv.attr('web_id');
                                        _default = $.extend(false,_default,{pageType:ptype,
                                            commentList_URL:mk_url("main/"+comment_path+"/get_all_comment",{web_id:web_id,tid:tid}),
                                            commentDEL_URL:mk_url("main/"+comment_path+"/del_comment",{web_id:web_id,tid:tid}),
                                            comment_URL:mk_url("main/"+comment_path+"/add_comment",{web_id:web_id,pageType:ptype,tid:tid}),
                                            praise_URL:mk_url("main/"+comment_path+"/add_like",{web_id:web_id,pageType:ptype,ctime:ctime,tid:tid}),
                                            praiseDEL_URL:mk_url("main/"+comment_path+"/del_like",{web_id:web_id,pageType:ptype,tid:tid}),
                                            praiseSon_URL:mk_url("main/"+comment_path+"/add_like",{web_id:web_id,pageType:comment,tid:tid}),
                                            praiseSonDEL_URL:mk_url("main/"+comment_path+"/del_like",{web_id:web_id,pageType:comment,tid:tid}),
                                            userlistFather_URL:mk_url("main/"+comment_path+"/like_list",{web_id:web_id,pageType:ptype,comment_ID:"{0}",tid:tid}),
                                            //shareNumList:mk_url("main/"+comment_path+"/like_list",{web_id:web_id,pageType:ptype,comment_ID:"{0}",tid:tid}),
                                            //userlistSon_URL:mk_url("main/"+comment_path+"/like_list",{web_id:web_id,pageType:comment,comment_ID:"{0}",tid:tid}),
                                            userlistSon_URL:mk_url("main/"+comment_path+"/like_list",{web_id:web_id,comment_ID:"{0}",tid:tid}),
                                            forward_URL:mk_url("main/"+comment_path+"/share_list",{web_id:web_id}),
                                            msgname:_thisDiv.attr("msgname"),
                                            msgurl:_thisDiv.attr("msgurl"),
                                            action_uid:_thisDiv.attr("action_uid"),
                                            web_id:web_id
                                            });
                                        _default = $.extend(_default,options);
                                        var comment=new CommentEasy($.extend({elem:_thisDiv,minNum:3},{ops:_default}));
                                        comment.init(d);
                                        _thisDiv.addClass("hasComment");
                                    }
                                });
                                s = (i + 1) * 10;
                                i ++;
                                setTimeout(arguments.callee,25);
                            }
                            if(_default.onLoadCallback){
                                _default.onLoadCallback(result);
                            }
                        },25);
                    }else{
                        alert(result.info);
                    }
            });
			this.commentArgs=_default;
			return this;
		},
		//动态插入评论
		commentAdd:function(obj){
			if( typeof obj ==="string"){
				obj=$(obj);
			}
			var _self=this;
			obj.each(function(){
				if(!$(this).hasClass("hasComment")){
					var comment=new CommentEasy($.extend({elem:$(this)},{ops:_self.commentArgs}));
					comment.init();
					$(this).addClass("hasComment");
				}
			});
			return this;
		}
	});	
})(jQuery);
/**
 * ui namespace
 * by 马正洁 Marshane
 */
var debug={log:function(a){try{console.log(a);}catch(e){}}};
/**
 * 目标字符串是否包含 object时为匹配
 * @param source
 * @return {Boolean}
 */
String.prototype.hasString=function(source){
    if(typeof source == 'object'){
        for(var i= 0,j=source.length;i<j;i++){
            if(this==source[i]) return !0;
        }
        return !1;
    }
    if(this.indexOf(source) != -1) return !0;
};
var ui={
    utils:{
        /**
         * 对目标字符串进行格式化
         * @param {string} source 目标字符串
         * 替换目标字符串中的{0}、{1}...部分。
         * _format(string,replace0,[relace1]....);
         * @returns {string} 格式化后的字符串
         */
        format:function(source){
            if (arguments.length == 1) return source;
            var data = Array.prototype.slice.call(arguments, 1);
            return source.replace(/\{(\d+)\}/g,function(_0, _1) {
                return data[_1]
            })
        },
        /**
         * 判断是否在iframe中
         * @return {Boolean}
         */
        inIframe:function(){
            try{if(window!=parent) return !0;}catch(e){}
            return !1;
        }
    }
};
window._format=ui.utils.format;
/**
 * 所支持的推荐pagetype类型，鉴于现在类型变化多 很多互相冗余 暂时不对其优化。
 * @timeline: 时间线中支持
 * @module: 模块中支持
 * @share: 分享支持类型 分时间线 和 模块
 * @type {Object}
 */
ui.pagetype={
    timeline:['topic','blog','photo','video','album','forward','web_topic','web_photo','web_video','web_album','web_forward','goods','sharevideo','group','web_dish','web_groupon','web_travel','travel','airticket','web_airticket'],
    module:['topic','blog','photo','video','album','forward','web_topic','web_photo','web_video','web_album','web_forward','info','comment', 'web_comment','event','web_event','ask','goods','sharevideo','group','web_dish','web_groupon','web_travel','travel','airticket','web_airticket'],
    share:{
        timeline:['blog','web_blog','photo','web_photo','video','web_video','album','web_album','topic','web_topic','forward','web_forward','info','web_travel','travel','airticket','web_airticket'],
        module:['blog','web_blog','photo','web_photo','video','web_video','album','web_album','web_travel','travel','airticket','web_airticket']
    },
    coll:['blog','web_blog','photo','web_photo','video','web_video','album','web_album']
};
ui.Comment=function(){
    this.share_instance=null;
    this.webID='';
    this.tpl={
        main:'<div class="laymoveText shareBox"><div class="zf_content shareBox"><textarea name="content" maxlength="140"></textarea><div class="replyFor"><div class="shareTo"><label>同时评论给：</label><label class="replyCheck"><input type="checkbox" name="reply_author" id="replyCheck"> {1}</label></div><div class="tip countTxt"><span class="num">0</span>/140</div></div><div class="content">{0}</div></div></div>',
        info:'<span class="avatar"><img src="{0}"></span><div class="avatar_info"><p><strong>状态更新</strong></p><p>由<span class="name"><a href="{1}">{2}</a></span>发布</p><p>{3}</p></div>',
        album:'<span class="avatar"><img width="92" src="{0}"></span><div class="avatar_info"><p><strong>来自相册：</strong>{1}</p><p>由<span class="name"><a href="{2}"> {3}</a></span>发布</p></div>',
        blog:'<div class="avatar_info"><h3><a href="{0}">{1}</a></h3><p>由<span class="name"><a href="{2}"> {3}</a></span>发布</p><p>{4}...</p></div>',
        video:'<span class="avatar"><img width="92" src="{0}"></span><div class="avatar_info"><p>{1}</p><p>由<span class="name"><a href="{2}"> {3} </a></span>发布</p><p>{4}</p><p>{5}</p></div>',
        isDelCon:'<div style="padding:10px">原始信息已被删除！无法进行操作</div>',
        isDelTit:'提示',
        isDelBtn:'<span class="popBtns blueBtn callbackBtn">知道了</span>',
        shareTit:'分享',
        shareBtn:'<span class="popBtns blueBtn callbackBtn">确定</span><span class="popBtns closeBtn">取消</span>',
        shareer:'<label class="replyCheckOld"><input type="checkbox" name="replyCheckOld" id="replyCheckOld"> {0}</label>',//信息源人名
        option:'<option value="{0}">{1}</option>',
        select:'<lable>分享至：</lable><select id="J_forwardTo">{0}</select>',
        unpage:'<p style="color:#ff281c">网页信息仅限转载至网页,您还未创建网页</p>',
        iswebReply:'<div class="replyFor" style="margin-bottom: 10px">{0}</div>',
        empty:'<p style="padding:10px 20px; color:#ff281c">{0}</p>'
    };
};
ui.Comment.prototype={
    /**
     * 推荐分享请求地址
     */
    url:function(){
        return{

        }
    }(),
    /**
     * 通用 评论 分享 只对模块适用, 时间线分享在info.js中调用
     * @param context
     * @param arg
     * @param {istl} //是否在时间线 可选
     */
    share:function(context,arg,istl){
        var tpl=this.tpl,
            html=[],
            _comment_easy=context.closest("div.comment_easy").parent(),
            _pagetype=_comment_easy.attr('pagetype'),
            _type=_comment_easy.attr('type'),
            _li=_comment_easy.closest('li'),
            _web_id=!!(CONFIG['web_id']>>0)?CONFIG['web_id']:_comment_easy.attr('web_id')||0,
            _type_html=function(type,d){
                var userURL = d.author;
                switch (type) {
                    case 'topic':
                    case 'web_topic':
                    case 'forward':
                    case 'web_info':
                    case "info":
                        html.push(_format(tpl.main,_format(tpl.info,d.avatar,userURL,d.username,d.content), d.username));
                        break;
                    case 'web_blog':
                    case 'blog':
                        html.push(_format(tpl.main,_format(tpl.blog,'javascript:;',d.title,userURL,d.username,d.content),d.username));
                        break;
                    case 'photo':
                    case 'web_photo':
                    case 'web_album':
                    case "album":
                        html.push(_format(tpl.main,_format(tpl.album, d.imgurl,d.title,userURL, d.username),d.username));
                        break;
                    case "web_video":
                    case "video":
                    case "sharevideo":
                        html.push(_format(tpl.main,_format(tpl.video, d.imgurl,d.title,userURL,d.username,d.content, d.url),d.username));
                        break;
                }
                return $(html.join(''));
            },
            _self=this;
        this.webID=_web_id;
        this.isweb=_pagetype.hasString('web')?1:0;
        var url='main/share/share_info?'+arg;
        //获取 分享弹出层信息
        $.ajax({
            url:mk_url(url),
            dataType:'jsonp'
        }).then(function(q){
                var data= q.data;
                if(q.status){
                    //根据不同类型 显示模板
                    _self.share_instance=html=_type_html(_pagetype,data);
                    if(_self.isweb){
                        var _h=[],_sel='',i= 0,web_list=data.web_list;
                        if(web_list&&web_list.length>0){
                            for(;i<web_list.length;i++)
                                _h.push(_format(tpl.option,web_list[i].aid,web_list[i].name));
                            _sel=_format(tpl.select,_h.join(''));
                        }else{
                            _sel=tpl.unpage;
                        }
                        html.find('.replyFor').css('margin-bottom','5px').after(_format(tpl.iswebReply,_sel));
                    }
                    //信息源被删除 情况
                    if(data.isdel){
                        _self._popUp({width:400,title:tpl.isDelTit,content:tpl.isDelCon,buttons:tpl.isDelBtn,callback:_self._popUpClose},_self.share_instance);
                        return false;
                    }
                    function _callback(){
                        if(_self.isweb){
                            var __J_forwardTo=html.find('#J_forwardTo');
                            if(__J_forwardTo[0]){
                                _web_id=$(__J_forwardTo).val();
                            }else{
                                html.find("div.replyFor").eq(1).fadeOut(100).fadeIn(100);
                                return;
                            }
                        }
                        var content=html.find('textarea').val(),
                            tid=_comment_easy.attr('commentobjid'),
                            action_uid=_comment_easy.attr('action_uid');
                        var replyCheck=$('#replyCheck:checked')[0]?_comment_easy.attr('action_uid'):'';
                        var data={};
                        //是否在首页 首页处理方式和时间线一致.
                        if(istl){
                            var _id=_li.attr("id"),_fid=_li.attr("fid");
                            //如果是信息源数据，fid为空（状态|分享视频）或则为模块id（博客|相册|照片|视频），此时li元素中的fid和commentobjid相等，如果不等则代表此条信息流数据为分享！
                            if(_fid!='' && _fid !="undefined"){
                                if (_fid != tid) {
                                    _id = _fid;
                                }
                            }
                            if(_self.isweb){//网页情况
                                data={content:content,fid:_id,my_web_id:_web_id,web_id:_self.webID,tid:tid,reply_author:replyCheck,action_uid:action_uid}
                            }else{
                                data={content:content,fid:_id,tid:tid,reply_author:replyCheck,action_uid:action_uid}
                            }
                        }else{
                            data={content:content,fid:'',tid:tid,page_type:_pagetype,reply_author:replyCheck,action_uid:action_uid,web_id:_self.webID,my_web_id:_web_id};
                        }
                        _self._doShare(context,data);
                    }
                    _self._popUp({width:475,title:tpl.shareTit,content:html,buttons:tpl.shareBtn,callback:_callback},_self.share_instance);
                }else{
                    _self._popUp({width:400,title:tpl.isDelTit,content:_format(tpl.empty,q.info),buttons:tpl.isDelBtn,callback:_self._popUpClose});
                }
                _self._textAreaListener(html);
        });
    },
    _doShare:function(context,data){
        var share_totle=context.parent().find('.forward_count'),val='',
            url=(this.isweb ? 'main/share/webShare': 'main/share/doShare'),
            _self=this,
            tpl=this.tpl;
        $.ajax({
            url:mk_url(url),
            dataType:"jsonp",
            type:'post',
            data:data
        }).then(function(q){
            if(q.status){
                val=share_totle.html().replace(/\(|\)/g,'');
                val=(val>>0)+1;
                share_totle.html('('+val+')');//成功分享数+1
                _self._popUpClose();
                share_totle.addClass('cursorPointer');
            }else{
                _self._popUp({width:400,title:tpl.isDelTit,content:_format(tpl.empty,q.info),buttons:tpl.isDelBtn,callback:_self._popUpClose});
            }
        });
    },
    _popUp:function(o,ins){
        ins=ins||$();
        ins.popUp({width:o.width,title:o.title,content:o.content,buttons:o.buttons,mask:true,maskMode:true,callback:o.callback});
    },
    _popUpClose:function(){$.closePopUp()},
    _textAreaListener:function(html){
        if(!html[0]) return;
        var textArea=html.find('textArea'),
            countTxt=html.find('div.countTxt .num'),
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
    }
};