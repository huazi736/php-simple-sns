/*
 * 本地生活内页
 * By 贤心(xuxinjian)
 * Date:2012.07.15
 */

document.domain = CONFIG['local_run'] ? "duankou.com" : CONFIG['domain'].substring(1);
var local = {
	init : function(){
		$('#local_typeGroupon').length <= 0 || this.groupEvent();	
		$('#local_typeDish').length <= 0 || this.dishEvent();
		$('#DK_adddish').length <= 0 || this.addDishEvent();
		$('#DK_addgroup').length <= 0 || this.addgroupEvent();
	},
	uploadImg : function(){	
		$('.dish_pic').hover(function(){
			$('#local_imgadmin').show();
		},function(){
			$('#local_imgadmin').hide();
		});
		var _url = mk_url('album/api/publicUploadCrossPhoto',{type : 3 , flashUploadUid : CONFIG['u_id']})
		imgTag = '';
		$('form.DK_bite').attr('action' , _url)
		$(".DK_bite").each(function(g) {
			$.uploader(this);
			window.uploadCallback = {
				success: function(response, successElm) {		
						var data = response;
						var msg = data.msg;		
						var url = msg.groupname + "/" + msg.filename + "_";
						imgUrl = msg.img_url.img_ts;
						imgTag = [{
							b : {
								url : url + "b." + msg.type,
								type : msg.type,
								width : msg.photosizes.b.w,
								height : msg.photosizes.b.h
							},
							s : {
								url : url + "s." + msg.type,
								type : msg.type,
								width : msg.photosizes.s.w,
								height : msg.photosizes.s.h
							}
						}];	
						var DK_bite = $(".DK_bite").eq(g);
						DK_bite.find('.uploadWrap').css({'background':'none'});
						DK_bite.parent().find('.DKcan_img').hide();
						DK_bite.parent().find('.DKcan_imgOK').show();
						$('.local_imgurl').css({"background" : "url(" + imgUrl + ") no-repeat center center"});
				},
				error: function() {}
			}
		});	
	},
	sendUpdata : function(){
		$('#local_Ebtn').live('click',function(){
			var values = {
				web_id : CONFIG['web_id'],
				//catid : $('.dish_cais>label').attr('id'),
				id : $('#local_h3').attr('cid'),
				name : $('#dish_title').val(),
				price : $('#dish_price').val(),
				description : $('#dish_decs').val(),
				imgTag : imgTag	
			};
			
			$.ajax({
				url : mk_url('channel/catering_dish/update'),
				type : 'POST',
				dataType:'jsonp',
				data : values,
				success : function(data){					
					if(data.status == 1){
						$('.local_box').hide();
						location.reload();
					}else{
						$.alert('更新失败！');
					}
				}
			});

		});
	},
	getdishCatid : function(data , dishType){
		othis = this;
		var ppHTML = '<div id="pingpaiBox"><div class="ppbox_left" style="height:180px;" id="ppbox_left"></div></div>';						
		var _dl = '',_dd = [];
		var pp_id = $('#ppweb_id').text();
		dishType == 0 ? D = data.data.data : D = data.category;
		
		$(this).popUp({
			width : 240,
			height : 200,
			title : '选择菜系',
			content : ppHTML,
			callback : function(){					
				var _checkV = $('#pingpaiBox').find('input:checked');
				$('#dish_cais').find('label').attr('id',_checkV.attr('id')).html(_checkV.val());
				$.closePopUp();
			}
		});
		
		switch(D.level){
			case '2':										
				for(a in D.info){
					_dd[a] = '';
					for(b in D.info[a].child){									
						_dd[a] = _dd[a] + '<dd ><input type="radio" name="goods_ppradio" id="' + D.info[a].child[b].id + '" iid="'+ D.info[a].child[b].iid +'" level="' + D.info[a].child[b].level + '" has_son="'+ D.info[a].child[b].has_son +'" eid="'+ D.info[a].child[b].eid +'" value="'+ D.info[a].child[b].name +'"  /><span>'+ D.info[a].child[b].name +'</span></dd>';
					}
					_dl =  _dl + '<dl><dt id="' + D.info[a].id + '" level="' + D.info[a].level + '" has_son="'+ D.info[a].has_son +'" class="DK_tree treeMinus"><a href="javascript:;">'+ D.info[a].name +'</a></dt>'+ _dd[a] +'</dl>'
				}
				var _content = '<h3 id="' + D.id + '" level="' + D.level + '" has_son="'+ D.has_son +'" class="DK_tree treeMinus"><a href="javascript:;">'+ D.name +'</a></h3>' + _dl + '</dl>';
				break;	
							
			case '3':
				for(a in D.info){										
					_dd = _dd + '<dd style="background-position: 20px -15px; padding-left: 3em;" ><input type="radio" name="goods_ppradio" id="' + D.info[a].id + '" iid="'+ D.info[a].iid +'" level="' + D.info[a].level + '" has_son="'+ D.info[a].has_son +'" eid="'+ D.info[a].eid +'" value="'+ D.info[a].name +'"  /><span>'+ D.info[a].name +'</span></dd>';										
				}	
				var _content = '<dl ><dt style="padding-left:15px; background-position: 0 -15px;" id="' + D.id + '" level="' + D.level + '" has_son="'+ D.has_son +'" class="DK_tree treeMinus"><a href="javascript:;">'+ D.name +'</a></dt>'+ _dd +'</dl>';
				break;
			case '4':
				var _content = '<dl><dt class="DK_tree treeMinus">菜系选择</dt><dd ><input type="radio" name="goods_ppradio" id="' + D.id + '" iid="'+ D.iid +'" level="' + D.level + '" has_son="'+ D.has_son +'" eid="'+ D.eid +'" value="'+ D.name +'"  /><span>'+ D.name +'</span></dd></dl>';
				break;		
		};	
		$('#ppbox_left').html(_content);
		$('#ppbox_left').find('dl').each(function(e){
			var _ddLen = $(this).find('dd');
			if(_ddLen.length < 1){
				$(this).append('<dd>该类别下无对应菜系</dd>')
			}
		});	
		
		$('#ppbox_right').hide();		
		$('.DK_tree').live('click',function(){
			var _that = $(this);
			var _class = $(this).attr('class');					
			if(_class.indexOf('treePlug') == -1){
				_that.addClass('treePlug');
				_that.parent('dl').find('dd').hide();
				if(_that.attr('has_son') == 1){
					_that.parent().find('dl').hide();
					_that.parent().find('.DK_tree').addClass('treePlug');
				}						
			}else{
				_that.removeClass('treePlug');
				_that.parent().find('dd').show();
				if(_that.attr('has_son') == 1){
					_that.parent().find('dl').show();
					_that.parent().find('.DK_tree').removeClass('treePlug');
				}
			}
		});
	},
	groupEvent : function(){
		othis = this;
		var typeG = $('.groupon_list');
		var _gLen = typeG.length;
		$('#local_typeGroupon li').each(function(e){
				$(this).show();
				diff = $(this).find('.group_showtime>i').text();
				othis.showEndTime(diff , e);

		});
		typeG.eq(typeG.length-1).css({'margin-right':0});
		if($('div.commentBox').length>=1){
			var commentOptions = { 
				minNum:3,
				UID:CONFIG.u_id,
				userName:CONFIG.u_name,
				avatar:CONFIG.u_head,
				//relay:!0,
				userPageUrl:$("#hd_userPageUrl").val(),
				relayCallback:function (obj,_arg) {
					var comment=new ui.Comment();
					comment.share(obj,_arg,!0);
				}
			};
			$('div.commentBox').commentEasy(commentOptions);
		}
	},
	showEndTime : function(diffS , e){ //促销倒计时
        var day,hour,mins,secs,diff = '无限期'; //默认
        var groupEnd = '团购已结束';
        var _i = $('.group_showtime').length;
        var infoGroup = {
            appendTime : function(){
                if(day > 0){
                    diff = '<strong class="groupon_day">' + day + '</strong>天<strong class="groupon_hour">' + hour + '</strong>小时<strong class="groupon_mins">' + mins + '</strong>分<strong class="groupon_secs">' + secs + '</strong>秒';
                }else{
                    if(hour > 0){
                        diff = '<strong class="groupon_hour">' + hour + '</strong>小时<strong class="groupon_mins">' + mins + '</strong>分<strong class="groupon_secs">' + secs + '</strong>秒';
                    }else{
                        if(mins > 0){
                            diff = '<strong class="groupon_mins">' + mins + '</strong>分<strong class="groupon_secs">' + secs + '</strong>秒';
                        }else{
                            diff = '<strong class="groupon_secs">' + secs + '</strong>秒';
                        }
                    }
                }
				 $('.group_showtime').eq(e).html('剩余时间：' + diff);
            },
            parseDateTime : function(second,e){
				day = parseInt(second/(24*3600));
                hour = parseInt((second - day*24*3600)/3600);
                mins = parseInt((second - day*24*3600 - hour*3600)/60);
                secs = second - day*24*3600 - hour*3600 - mins*60;
                //day = 0,hour = 0,mins = 0,secs = 10;
                if(second <= 0){
                    $('.group_showtime').eq(e).html(groupEnd);
                }else{
                    this.appendTime();
                }
				if(e == 0){
					this.run();
				}
            },
            contime : function(){
                var _d,_h,_m,_s;
                $('.group_showtime').each(function(e){
                    _d = $(this).find('.groupon_day');
                    _h = $(this).find('.groupon_hour');
                    _m = $(this).find('.groupon_mins');
                    _s = $(this).find('.groupon_secs');
                    _dval = _d.html();
                    _hval = _h.html();
                    _mval = _m.html();
                    _sval = _s.html();
                    var _len = $(this).find('strong').length;
                    if(_len > 0){
                        _sval--;
                        if(_sval < 0){
                            if(_len == 1){	//秒
                                $(this).find('i').html(groupEnd);
                            }else if(_len == 2){	//分、秒
                                _sval = 59;
                                if(_mval > 1 ){
                                    _mval--;
                                    _m.html(_mval);

                                }else{
                                    $(this).find('i').html('<strong class="groupon_secs">' + _sval + '</strong>秒');
                                }
                            }else if(_len == 3){	//时、分、秒
                                _sval = 59;
                                if(_mval > 0){
                                    _mval--;
                                    _m.html(_mval);
                                }else{
                                    _mval = 59;
                                    if(_hval > 1){
                                        _hval--;
                                        _m.html(_mval);
                                        _h.html(_hval);
                                    }else{
                                        $(this).find('i').html('<strong class="groupon_mins">' + _mval + '</strong>分<strong class="groupon_secs">' + _sval + '</strong>秒');
                                    }
                                }
                            }else if(_len == 4){	//天、时、分、秒
                                _sval = 59;
                                if(_mval > 0){
                                    _mval--;
                                    _m.html(_mval);
                                }else{
                                    _mval = 59;
                                    if(_hval > 0){
                                        _hval--;
                                        _m.html(_mval);
                                        _h.html(_hval);
                                    }else{
                                        _hval = 23;
                                        if(_dval > 1){
                                            _dval--;
                                            _m.html(_mval);
                                            _h.html(_hval);
                                            _d.html(_dval)
                                        }else{
                                            $(this).find('i').html('<strong class="groupon_hour">' + _hval + '</strong>小时<strong class="groupon_mins">' + _mval + '</strong>分<strong class="groupon_secs">' + _sval + '</strong>秒');
                                        }
                                    }
                                }
                            }
                        }
                        _s.html(_sval);
                    }

                });
            },
            run : function(){
              //  if(_i == 0){
                    t = setInterval(this.contime,1000);
               // }
            }
        }
		infoGroup.parseDateTime(diffS , e)
        /*=============== End 团购倒计时 ===================*/
	},
	dishEvent : function(){
		var This = this;
		var local_Ddish = '<div class="local_boxshade local_box"></div><div id="local_Ddish" class="local_Deditshow local_box"></div>';
		$('body').append(local_Ddish);
		$('.local_Dedit').live('click',function(){		
			$('.local_box').show(0,function(){
				$('#local_Ddish').css({'top' : 150 + $(window).scrollTop()});
				$('#local_Ddish').html('<div class="local_loading"></div>')
			});
			$.ajax({
				url : mk_url('channel/catering_dish/detail',{id : $(this).parents('li').attr('id'),catid : $('#category_group').val()}),
				type : 'POST',
				dataType:'jsonp',
				success : function(data){										
					D = data.data.data;
					$('#local_Ddish').html('<h3 id="local_h3" cid='+ D.id +'>编辑菜品<span id="local_boxclose" title="关闭">×</span></h3>' + '<ul class="DK_dish"><li class="groupPic dish_pic"><span class="local_imgurl" style="background:url(http://'+ D.fastdfs + '/' + D.pics[0].s.url +') scroll center center no-repeat;"></span><form class="DK_bite formUpload" action="" method="post" enctype="multipart/form-data"><span class="uploadWrap"><input type="file" class="fileUpload" name="Filedata" accept="images/jpg,images/png,images/gif"></span></form><div id="local_imgadmin"><span id="local_imgUpdata" title="更换图片"></span><span id="local_imgdelete" title="删除图片"></span></div></li><li class="dish_title DK_xform" title="菜品名称"><label>&nbsp</label><input type="text" id="dish_title"  value="'+ D.name +'" /></li><li class="dish_price DK_xform" title="菜品价格"><label>&nbsp</label><input type="text" id="dish_price" value="'+ D.price +'" /></li><!--<li class="dish_cais" id="dish_cais" title="点击更改分类"><label id="'+ D.catid +'">'+ D.catname +'</label></li>--><li class="dish_decs DK_xform" title="菜品描述"><label>&nbsp</label><input type="text" id="dish_decs" name="name"  value="'+ D.description +'" /></li></ul><div class="local_Eboxbottom"><span id="local_Ebtn" title="确认编辑"></span></div>');
					This.uploadImg();
					$('#dish_cais').off('click');
					$('#dish_cais').on('click',function(){
						This.getdishCatid(data.data.data , 1);
					});
					This.sendUpdata();			
					imgTag = [{
						b : {
							url :  D.pics[0].b.url,
							type : D.pics[0].b.type,
							width : D.pics[0].b.width,
							height : D.pics[0].b.height
						},
						s : {
							url :  D.pics[0].s.url,
							type : D.pics[0].s.type,
							width : D.pics[0].s.width,
							height : D.pics[0].s.height
						}
					}];	
				},
				error : function(){
					$('.local_box').hide();
					$.alert('请求失败！');
				}
			});
		});
		$('.local_Ddel').live('click',function(){
			var _that = $(this);
			$.confirm('删除菜品','您确定要删除<strong style="color:#3C58A9">'+ _that.parents('li').find('.local_Dtitle').text() +'</strong>这款菜肴吗？',function(){
				$.ajax({
					url : mk_url('channel/catering_dish/remove',{web_id : CONFIG['web_id'],id : _that.parents('li').attr('id')}),
					dataType : 'jsonp',
					type : 'GET',
					success : function(data){
						if(data.status == 1){
							_that.parents('li').remove().hide();
						}else{
							$.alert('删除失败，请重试！');
						}							
					}
				});
			});
		});
		$('#local_boxclose').live('click',function(){
			$('.local_box').hide();
		});
		$('#local_imgdelete').live('click',function(){
			$('.local_imgurl').css({"background" : "none"});
		});
		$('.dish_getShow').live('click',function(){
			This.dish_showBox();
		});
	},
	dish_showBox : function(){
		
	},
	addDishEvent : function(){
		oThis = this;
		this.uploadImg();
		var pp_id = $('#ppweb_id').text();
		$('#dish_cais').live('click',function(){
			var _url = mk_url('channel/catering_dish/get_category_tree');
			$.ajax({
				url : _url,
				method: "POST",
				data : {catid : pp_id},
				dataType : 'jsonp',
				success : function(data){
					oThis.getdishCatid(data , 0);
				}
			});
		});
		$('#addDish').bind('click',function(){		
			($('#dish_cais>label').attr('id') == '') ? catid = pp_id : catid = $('#dish_cais>label').attr('id')
			var values = {
				type : 'dish',
				web_id : CONFIG['web_id'],
				imgTag : imgTag,
				name : $('#dish_title').val(),
				price : $('#dish_price').val(),
				//catid : catid,
				description : $('#dish_decs').val(),
				timestr: $('#date_a').val(),
				timedesc: '',
				bc: 1
			};
			if(values.imgTag == ''){
				$.alert('请上传图片！');
			}else if(values.name ==''){
				$.alert('请填写菜品名！');
			}else if(values.price =='' || values.price.search(/^\d+\.{0,1}\d+$/)== -1){
				$.alert('菜品价格不合法，只能输入整数或小数。如价格低于10，后面请加小数点，如：7.00');
			}else if(values.description == ''){
				$.alert('请填写菜品描述！');
			}else{			
				$.ajax({
					url: mk_url("channel/catering_dish/add"),
					dataType: "jsonp",
					data: values,
					success : function(data) {
						if (data.status == 1) {							
							location.href = mk_url('channel/catering_dish/index',{web_id : CONFIG['web_id']})
						}else{
							$.alert('发布失败了，请稍后重试！');
						}
					},
					error:function(data) {
						$.alert("网络错误，请重试！");
					}
				});
			}
			
		});
	},
	setEndTime : function(){ //普通时分列表插件	
		$.fn.xhours = function(xhours){
			var that = $(this);
			var xinit = $.extend({
				limits : [0,23], //时间范围(小时)
				child : 'li', //子节点
				wall : 30 //间隔分(必须为60的约数)
			},xhours);
			var _max  = 60/xinit.wall;
			var _minute = '',_hour = '';
			for(i=xinit.limits[0] ; i<=xinit.limits[1] ; i++){
				for(j=0 ; j<_max ; j++){
					((xinit.wall)*j < 10 ) ? _minute = '0' + (xinit.wall)*j : _minute = (xinit.wall)*j;
					(i < 10 ) ? _hour = '0' + i : _hour = i;
					that.append('<' + xinit.child + '>' + _hour + ':' + _minute + '</' + xinit.child + '>');
				}
			}
			that.append('<i></i>');
			var _list = that.find(xinit.child);
			var _tHen = _list.innerHeight()*_list.length;
			that.find('i').css({'height':_tHen + 10});
			_list.hover(function(){
				$(this).attr('class','nowli');
			},function(){
				$(this).attr('class','');
			});
		};
	},
	addgroupEvent : function(){
		oThis = this;
		this.uploadImg();
		this.setEndTime();
		$('#groupHourList').xhours();
		$(".html_date").calendar({button:false,time:false});
		//结束时间之小时
		var gHourlist = $('#groupHourList');
		$('#groupHours').live('click',function(even){
			var _t = $(this);
			even ? even.stopPropagation() : even.cancelBubble = true;
			gHourlist.show(0,function(){
				$(this).find('li').live('click',function(){
					_t.find('#groupNowHouers').text($(this).text())
					gHourlist.hide();
					return false;
				});
			});	
		});
		$(document).live('click',function(){
			gHourlist.hide();
		});
		$('#addDish').bind('click',function(){
			var values = {
				type : 'groupon',
				web_id : CONFIG['web_id'],
				imgTag : imgTag,
				groupname : $('#group_title').val(),
				oriprice : $('#group_price').val(),
				currprice : $('#group_curprice').val(),
				expiretime : $('#groupAdd_time').val() + ' ' + $('#groupNowHouers').text(),
				href : $('#group_link').val(),
				timestr: $('#date_a').val(),
				timedesc: '',
				bc: 1
			};
			
			if(values.imgTag == ''){
				$.alert('请上传图片！');
			}else if(values.groupname ==''){
				$.alert('请输入促销标题!');
			}else if(values.oriprice =='' || values.oriprice.search(/^\d+\.{0,1}\d+$/)== -1 || values.currprice == '' || values.currprice.search(/^\d+\.{0,1}\d+$/)== -1){
				$.alert('原价或现价只能输入整数或小数。如价格低于10，后面请加小数点，如：7.00');
			}else if($('#groupAdd_time').val() == ''){
				$.alert('请选择结束时间');
			}else{	
				$.ajax({
					url: mk_url("channel/catering_groupon/add"),
					dataType : "jsonp",
					data : values,
					success : function(data) {
						if (data.status == 1) {
							location.href = mk_url('channel/catering_groupon/index',{web_id : CONFIG['web_id']})		
						} else {
							$.alert('发布失败了，请稍后重试！');
						}
					},
					error:function(data) {
						$.alert("网络错误，请重试！");
					}
				});
			}		
		});
	}
};
local.init();