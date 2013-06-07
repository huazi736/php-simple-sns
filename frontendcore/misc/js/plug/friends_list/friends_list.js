/*
 *@author:    林长源
 *@created:   2012/1/5
 *@version:   v1.3
 *@desc:      好友模块插件
 			1.1 增加地址参数
			1.2 HTML 改为append 不全覆盖容器内容
			1.3 解决IE6下选中状态不对， 点击头像无法选中问题
			1.4 搜索添加向服务器请求好友列表功能 by wangyuefei
			1.5 添加分页功能 by wangyuefei (ps:开启分页功能需要在好友列表的返回json数据中加入is_end参数，值为0，1){
			    status:0/1,
			    info:"",
			    is_end:0/1,
			    data:[]
			}

	[实例]
		new CLASS_FRIENDS_LIST({
			detail:detail,	//列表放置位置
			id:id,			//当前用户的id
			elm:elm,		//触发好友窗口点击对象
			getUrl:"",			//获取好友列表url
			postUrl:"",			//发送选中的
			searchUrl:"",       //搜索功能用服务器返回的参数渲染的时候的url(ps:返回的参数格式跟getUrl一样) by wangyuefei
			noData:"您还没有任何好友",
			hasPage:ture/false  //有无分页功能
		});

	[need js]
		<script src="/misc/js/plug/jQuery-searcher/ViolenceSearch.js" type="text/javascript"></script> 
*/




function CLASS_FRIENDS_LIST(options){
	this.id = options.id;
	this.elm = options.elm;
	this.friends_detail = $("<div id='friends_detail'></div>");
	this.getFriendsUrl = options.getUrl;
	this.postSelectedUrl = options.postUrl;
    this.searchUrl=options.searchUrl;
	this.callback = options.callback;
    this.hasPage = options.hasPage;
	this.ids = options.ids;
	this.title = options.title||"邀请好友";
	options.noData = options.noData||"您还没有任何好友";
	options.hidden = options.hidden||"true";
	this.opts = options;
	this.init();
}
CLASS_FRIENDS_LIST.prototype= {
	init:function(){
		var self = this;
		var data = {};
		if(self.opts.uid){
			data.uid = self.opts.uid;
		}
		data.id = self.id;
		self.model("get_friends",[data,function(data){
			self.friends_detail.attr("pid",self.id);
			self.view(["friends_list"],[self.friends_detail,data]);
			self.plug(["popUp"],[self.elm,self.friends_detail,self.title]);
			self.plug(["dropMenu"],[self.friends_detail.find("div.dropMenu")]);
			self.plug(["search_friends"],[self.friends_detail.find(".search_bar").find("input"),data,self.friends_detail.find("div.friends_list")]);
			self.event("friends_list");
		}]);

	},
	plug:function(method,arg){
		var self = this;
		var _class = {
			popUp:function(arg){
				arg[0].subPopUp({
					width:580,
					title:arg[2],
					content:arg[1],
					buttons:'<span class="popBtns blueBtn callbackBtn">确定</span><span class="popBtns closeBtn">取消</span>',
					mask:true,
					maskMode:true,
					callback:function(){
						var data = {},names=[],allname='',dkcode=[];
						data.poll_id = arg[1].attr("pid");
						data.src_uid = [];
						data.id = [];
						$.each(arg[1].find("div.friends_list").children().children(),function(){
							if($(this).find("input").attr("checked")){
								data.src_uid.push($(this).attr("id"));
								data.id.push($(this).closest("li").attr("id"));
								dkcode.push($(this).closest("li").attr("dkcode"));
								names.push($(this).find("span").text());
							}
						});
						data.src_uid = data.src_uid.join(",");

						data.id = data.id.join(",");
						if(self.postSelectedUrl){
							if(data.src_uid){
								self.model("ask_friends",[data,function(data){
									if(data.state==0){
										alert(data.msg);
									}
								}]);
							}
							
						}
						allname = names.join(" ");//添加by梁珊珊，资料添加同学同事需要
						dkcode = dkcode.join(",");

						if(names.length>5){

							names = names[0]+" "+names[1]+" "+names[2]+" "+names[3]+" "+names[4]+" +其他"+(names.length-5)+"人";
		
						}else{
							names = names.join(" ");
						}
						
						if(self.callback){
							self.callback({ids:data.id,names:names,allname:allname,dkcode:dkcode});
						}
						$.closeSubPop();
					}
				});
			},
			dropMenu:function(arg){
				arg[0].dropdown({
					btn: '<span>按姓名搜索</span>', 
					list: '<ul class="dropListul checkedUl"><li class="current" rel="0"><a class="itemAnchor" href="javascript:void(0);"><i></i><span>按姓名搜索</span></a></li><li rel="1"><a class="itemAnchor" href="javascript:void(0);"><i></i><span>已选</span></a></li></ul>',
					templete: true
				});
			},
			search_friends:function(arg){
				ViolenceSearch.init({
					input: arg[0],
					resource: arg[1].data,
					filter: arg[2],
					filterWord: 'name',
					filterKey: 'id',
					isFilterSelected:true,
                    searchUrl:self.searchUrl,
                    delayTime:300,
					callback: function(data){
                        self.friends_detail.find('.friends_more').hide();
                        if(self.searchUrl){
                            self.friendsPage=1;
                            self.view(['friends_more'],[data, self.friends_detail.find('.friends_list').find('ul'),'html']);
                        }else{
                            var $div = $("<div></div>");
                            if(data){
                                arg[2].find("li").hide();
                                self.searchUrl?$.each(data,function(index,a){
                                    $div.append(arg[2].find("li[id="+a.id+"]"));
                                }):$.each(data,function(index,a){
                                    $div.append(arg[2].find("li[id="+a.item.id+"]"));
                                });
                                arg[2].find('ul').append($div.children().show());
                            }else{
                                arg[2].find("li").show();
                            }
                        }
					},
					descend: false
				});
			}

		}
		$.each(method,function(index,value){
			if(value){
				return _class[value](arg);
			}
		});
	},
	view:function(method,arg){
		var self = this;
		var _class = {
			friends_list:function(arg){
				if( arg[1].status == 0 ){
					//没有好友
					var msg = "";
					if(arg[1].msg&&arg[1].msg!="undefined"){
						msg = arg[1].msg;
					}else{
						msg = self.opts.noData;
					}
					arg[0].html("<div class='noData' style='padding:10px'>"+msg+"</div>");
					return false;
				}
				var str="",str2="";
				if(arg[0].find("div.friends_list").size()!=0){
					arg[0].find("div.friends_operation").remove();
					arg[0].find("div.friends_list").remove();
				}
				str+='<div class="friends_operation"><table width="100%" cellspacing="0" cellpadding="0" border="0"><tbody><tr><td><div class="dropWrap dropMenu" style="z-index:3;"></div></td><td><div class="search_bar"><div id="del-filter"></div><input type="text" msg="寻找好友" /></div></td></tr></tbody></table></div><div class="friends_list clearfix"><ul>';
				$.each(arg[1].data,function(index,a){
					var id = a.id,dkcode = a.dkcode,name = a.name,user_face = a.face,hidden = a.hidden || 0,checked = a.checked || 0;
					if(hidden&&self.opts.hidden=="true"){
						hidden = "disabled";
					}
					str2+='<li id="'+id+'" class="'+ hidden +'" dkcode="'+dkcode+'"><label for="checkbox'+id+'"><input type="checkbox"' + (checked ? 'checked="checked"' : '') + ' id="checkbox'+id+'"/><img width="32" height="32" src="'+user_face+'" /><span>'+name+'</span></label></li>';
				});
				str+=str2+'</ul>';
                if(!arg[1].is_end && self.hasPage){
                    str+='<div class="friends_more"><a>查看更多好友</a></div>';
                    self.friendsPage=1;
                }
                str+='</div><div class="checkAll" style="position:absolute; left:20px;bottom:22px;cursor:pointer"><label><input type="checkbox" style="margin-right:5px; vertical-align:middle;" />全选</label></div>'; //rewrite by zhupinglei : 样式调整
				arg[0].prepend(str);
				if(self.ids){
					$.each(self.ids.split(","),function(i,v){
						var li = arg[0].find("div.friends_list").find("li[id="+v+"]"),
							linput = li.find("input")[0];
						if (linput) {
							linput.checked = true;
						};	
						li.attr("class","checked");
					});
				}
			},
            friends_more:function(arg){
                if(arg[0].status==0){
                    arg[1].html("<div class='noData' style='padding:10px'>"+arg[0].info+"</div>");
                    return false;
                }
                var str="";
                $.each(arg[0].data,function(index,a){
                    var id = a.id,dkcode = a.dkcode,name = a.name,user_face = a.face,hidden = a.hidden || 0,checked = a.checked || 0;
                    if(hidden&&self.opts.hidden=="true"){
                        hidden = "disabled";
                    }
                    str+='<li id="'+id+'" class="'+ hidden +'" dkcode="'+dkcode+'"><label for="checkbox'+id+'"><input type="checkbox"' + (checked ? 'checked="checked"' : '') + ' id="checkbox'+id+'"/><img width="32" height="32" src="'+user_face+'" /><span>'+name+'</span></label></li>';
                })
                arg[1][arg[2]](str);
                if(!arg[0].is_end && self.hasPage){
                    self.friends_detail.find('.friends_more').show();
                }
                self.event('friends_list');
            }
		};
		$.each(method,function(index,value){
			if(value){
				return _class[value](arg);
			}
		});
		
	},
	event:function(type,dom){
		var self = this;
		switch(type){
			case "friends_list":
				self.friends_detail.find(".friends_list li").hover(function(){
					if(!$(this).hasClass("disabled"))
						$(this).css("background","#eceff5");
				},function(){
					if(!$(this).hasClass("disabled"))
						$(this).removeAttr('style');
				}).unbind("click").bind("click",function(){
					if($(this).hasClass("disabled"))return false;
					if($(this).find("input").attr("checked")){
						$(this).addClass("checked");
						$(this).css("background","#d8dfea")
					}else{
						$(this).removeClass("checked");
						$(this).removeAttr('style');
					}
				});
                //避免webkit浏览器出现两次点击事件 by wangyuefei
                if(!$.browser.safari){
                    self.friends_detail.find(".friends_list img").unbind("click").bind("click",function(){
                        $(this).parent().trigger("click");
                    });
                }
				self.friends_detail.find("div.dropList").find("li[rel=1]").unbind("click").bind("click",function(){
					self.friends_detail.find("div.search_bar").hide();
					$.each(self.friends_detail.find(".friends_list").find("li"),function(){
						if($(this).find("input").attr("checked")){
						
							$(this).show();
						}else{
							$(this).hide();
						}
					});
				});
				self.friends_detail.find("div.dropList").find("li[rel=0]").unbind("click").bind("click",function(){
					self.friends_detail.find("div.search_bar").show().find("input").val("");
					self.friends_detail.find(".friends_list").find("li").show();
					
				});
				self.friends_detail.find("#del-filter").unbind("click").bind("click",function(){
					if($.browser.opera){
						$(this).next().val("").trigger("input").focus();
					}
					$(this).next().val("").trigger("keyup").focus();
				});
				self.friends_detail.find("#del-filter").hover(function(){
					$(this).css("background-position","-15px -15px");
				},function(){
					$(this).css("background-position","0px 0px");
				});
				
				//rewrite by zhupinglei : 增加全选后，背景变成选中色彩，去全选后，背景撤消，对disabled项，不管是否全选，不影响其状态
				self.friends_detail.find(".checkAll").find('input[type=checkbox]').click(function(){
					if($(this).attr("checked")){
						$.each(self.friends_detail.find("div.friends_list").find("input[type=checkbox]"),function(){
							if($(this).parent().parent().hasClass("disabled")){
								return;
							}else{
								$(this).attr("checked",true);
								$(this).parent().parent().addClass('checked').css({'background':'#D8DFEA'});//IE
							}
						});
					}else{
						$.each(self.friends_detail.find("div.friends_list").find("input[type=checkbox]"),function(){
							if($(this).parent().parent().hasClass("disabled")){
								return;
							}else{
								$(this).attr("checked",false);
								$(this).parent().parent().removeClass('checked').css({'background':''});//IE
							}
						});
						//self.friends_detail.find("div.friends_list").find("input[type=checkbox]").attr("checked",false);
					}
				});
                self.friends_detail.find('.friends_more').find('a').off('click').on('click',function(){
                    var data={},ul=$(this).parent().prev();
                    $(this).parent().hide();
                    data.page=++self.friendsPage;
                    self.model('get_friends',[data, function(data){
                        self.view(['friends_more'],[data,ul,'append']);
                    }])
                })
			break;
		default:
			break;
		}
	},
	model:function(method,arg){
		var self = this;
		var _class={
			get_friends:function(more){
				$.djax({
					url:self.getFriendsUrl||mk_url("main/index/getFriends"),
					dataType:self.opts.dataType||"json",//王月飞2012.7.12
                    type:self.opts.type||'POST',//王月飞2012.7.12
					data:arg[0],
					success:arg[1]
				});
				// 判断单选还是多选
			},
			ask_friends:function(){
				$.djax({
					url:self.postSelectedUrl||mk_url("ask/ask/ask_friends"),
					dataType:self.opts.dataType||"json",//王月飞2012.7.12
                    type:self.opts.type||'POST',//王月飞2012.7.12
					data:arg[0],
					success:arg[1]
				});
			}
		}
		return _class[method](arg);
	}
};

