/**
 * @author:    zhangbo
 * @created:   2012/5/7
 * @version:   v1.0
 * @desc:      网页设置sssss
 */
$(function(){
	var editWeb_url = mk_url('interest/web_setting/editWeb');//修改网页设置ajax地址
	var delWeb_url = mk_url('interest/web_setting/delWeb');//删除网页ajax地址
	var getWebDate_url = mk_url('interest/web_setting/getWebDate');//删除网页ajax地址
	var webSetting = {};
	webSetting.view = function(name,arg){
		this.view._class={
			//滚动加载网页列表
			scrollLoad:function(arg){
				arg[0].scrollLoad({
					text: "查看更多",
		            url: getWebDate_url,
		            success: function(returnData){
						data = returnData.data;
						if(data.status==1){
							var str = ''; 
							for (var i = 0, len = data.data.length; i < len; i++) {
								str += '<div class="webset-bd-box clearfix"><li class="web-info-item fl">';
								str += '<div class="web-photo"><a href="'+data.data[i].url+'""><img width="30" height="30" src="'+ data.data[i].web_avatar +'" alt="'+ data.data[i].web_name +'"></a></div>';
								str += '<div class="web-name"><strong>'+ data.data[i].web_name +'</strong><p>粉丝数：'+ data.data[i].fans_count +'</p></div><div class="operate-web">';
			            		
								if (data.data[i].is_del==0){
									str += '<span class="del-web accountSettingEdit blue" id="' + data.data[i].web_aid + '">删除</span> ';
									str += '<span class="deit-web accountSettingEdit blue"><i class="icon_edit"></i>编辑</span>';
								}else{
									str += '<p>处理中</p>';
								}
								
								str +='</div></li>';
								
	            				str += '<div class="web-setting-option hide"><p><label>隐私设置：<input id="synname" type="checkbox" name="synname" value="'+ data.data[i].is_info +'"';
								if(data.data[i].is_info == 1){
									str += ' checked="checked"';
								}
								str += '><span>同步创建者姓名显示到网页</span></label></p>';
								
									str += '<p class="top-set"><label>置顶设置：<input id="topweb"  type="checkbox" name="topweb" value="0"';
									if(data.data[i].is_top == 1){
										str += ' checked="checked"';
									}
										str += '><span>显示在网页导航条首位</span></label></p>';
		            			str += '<p class="web-setting-but"><span id="'+ data.data[i].web_aid +'" class="edit-web-confirm">确定</span>';
		            			str += '<span class="cancel-edit">取消</span></p></div>';
								
								str += '</div>';
							}
							arg[0].append(str);
							$(".top-set").first().hide();
							
						}else{
							arg[0].append('<div class="blankWrap"><span>您还未创建网页</span></div>');
						}
		            }
	   			});
			},
			//编辑显示设置区域
			show:function(arg){
				arg[0].live( 'click', function(){
				var set_box = $(this).parent().parent().parent().find('.web-setting-option');  
					$(this).parent(".operate-web").addClass('hide');
					set_box.removeClass('hide');
				});
			},
			//取消 关闭设置区域
			hide:function(arg){
				arg[0].live( 'click', function(){
				var set_box = $(this).parent().parent().parent().find('.web-setting-option');  
					var operate_box = $(this).parent().parent().parent().find('.operate-web'); 
					set_box.addClass('hide');
					operate_box.removeClass('hide');
				});
			}
		}
		return this.view._class[name](arg)
	}
	webSetting.event = function(name,arg){
		this.event._class={
			//修改网页设置
			editClick:function(arg){
				arg[0].live( 'click', function(){
					$(this).parent('operate-web');
					var web_aid = $(this).attr('id');
					var synname = $(this).parent().parent().find('#synname').val(); //是否同步到个人资料 设置
					var topweb = $(this).parent().parent().find('#topweb').val();  //设置是否显示到导航首位
		            var data = {};
						data.web_aid = web_aid;
						data.synname = synname;
						data.topweb = topweb;
					webSetting.model("getData",[data,editWeb_url,function(returnData){
						console.log("1");
						data=returnData.data;
						if(data.state!=0){
							
							window.location.reload();
							return false;
						}else{
							alert(data.msg);
						}
					}])

				});
			},
			//删除网页
			delClick:function(arg){
				var $msg = "<div class='del-web-msg'><p class='msg'>您确定要删除该网页么？</p><p>(系统将在三天后删除该网页，并通知网页粉丝)</p></div>";
					arg[0].live( 'click', function(){
					var self_prev = $(this).parent().parent().find('.operate-web');
					var web_aid = $(this).attr('id');//获取webID
					var data = {};
						data.web_id = web_aid;
						webSetting.plug('popUp',[arg[0],$msg,"警告",function(){
							webSetting.model('getData',[data,delWeb_url,
				                function(data){
									if(data.state!=0){
										self_prev.html("<p>处理中</p>")
										$.closePopUp();
										return false;
									}
				                }
				            ]
						);
			        },'<span class="popBtns blueBtn callbackBtn">确定</span><span class="popBtns closeBtn">取消</span>']);
					

				});
			},
			setCheckbox:function(arg){
			//设置checkbox的value值
			$("input:checkbox").live( 'click', function(){
					if(this.checked == true){
						this.value = 1;
					}else{
						this.value = 0;
					}
				});
			}
		}
		return this.event._class[name](arg)
	}
	webSetting.plug = function(name,arg){
		this.plug._class={
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

	//请求数据
	webSetting.model = function(name,arg){
		this.model._class={
			getData:function(arg){
				$.ajax({
					url: arg[1],
					type: 'post',
					dataType: 'json',
					data: arg[0],
					success: function(data){
						arg[2](data)
					}
				});
			}
		}
		return this.model._class[name](arg)
	}
	//初始化
	webSetting.init =function(){
		webSetting.view("show",[$(".deit-web")]);
		webSetting.view("hide",[$(".cancel-edit")]);
		webSetting.view("scrollLoad",[$(".webset-info-box")]);
		webSetting.event("setCheckbox");
		webSetting.event("editClick",[$(".edit-web-confirm")]);
		webSetting.event("delClick",[$(".del-web")]);
	}
	webSetting.init();
});