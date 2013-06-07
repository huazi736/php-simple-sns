/*
 * 【本地生活】之时间线/发表框
 * By 贤心(xuxinjian)
 * Date:2012.07.02
 */
 
document.domain = CONFIG['local_run'] ? "duankou.com" : CONFIG['domain'].substring(1);
var nowPost = '';
var groupHtml = '',dishHtml = '';
(function(U){
	U('.tabHead').find("li").each(function(e){
		U('.tabHead').find("li").live('click',function(){
			nowPost = $(this).attr('rel');		
			postInfo.lightBtn();
		});
	});
	$.groupHtml = function(data){groupHtml = data};
	$.dishHtml = function(data){dishHtml = data};
})($);

var Dmimi = {
	dinnerInit : function(){
		(function(U){	
			var group = U('#groupDK');
			var _tx,imgUrl,imgTag = '';;
			var goods_offset = 0;
			postInfo = {
				init : function(){
					groupIF = [];
					dishIF = [];
					this.setEvent();
					this.uploadPic();
					this.xForm();
					this.setHour();
				},
				uploadPic : function(){
					var oThis = this;
					var _url = mk_url('album/api/publicUploadCrossPhoto',{type : 3 , flashUploadUid : CONFIG['u_id']});	
					U('form.DK_bite').attr('action' , _url)
					U(".DK_bite").each(function(g) {
						$.uploader(this);
						window.uploadCallback = {
							success: function(response, successElm) {		
								if( $('#groupDK').length > 0){
									var data = response;
									var msg = data.msg;		
									var url = msg.groupname + "/" + msg.filename + "_";
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
									if(nowPost == 'group'){
										var DK_bite = U(".DK_bite").eq(0);
										groupIF[0] = 1;
									}else{
										var DK_bite = U(".DK_bite").eq(1);
										dishIF[0] = 1;
									}
									DK_bite.parent().find('.DKcan_img').hide()
									DK_bite.parent().find('.DKcan_imgOK').show();
									DK_bite.find('.uploadWrap').css({'background':'none'});
									oThis.lightBtn();
									oThis.uploadPic();
								}
							},
							error: function() {}
						}
					});
				},
				setHour : function(){ //普通时分列表
					
						U.fn.xhours = function(xhours){
						var that = U(this);
						var xinit = U.extend({
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
							U(this).attr('class','nowli');
						},function(){
							U(this).attr('class','');
						});
					};
					var gDate = new Date();
					U('#groupHourList').xhours();
					//结束时间之小时
					var gHourlist = U('#groupHourList');
					U('#groupHours').live('click',function(even){
						var _t = U(this);
						even ? even.stopPropagation() : even.cancelBubble = true;
						gHourlist.show(0,function(){
							U(this).find('li').live('click',function(){
								_t.find('#groupNowHouers').text(U(this).text())
								gHourlist.hide();
								return false;
							});
						});	
					});
					U(document).live('click',function(){
						gHourlist.hide();
					});
				},
				xForm : function(){ //菜品表单交互	
					U.fn.xform = function(deliver){
						var init = U.extend({
							enterAuto:false,
							attr : 'tag'
						},deliver);
						var xthat = U(this);
						DK_from = {
							set : function(){	
								xthat.live('click',function(){			
									U(this).find('input').focus();	
								});
								xthat.find('input').focus(function(){
									__xthat = U(this).parent();	
									_text = __xthat.find('label').html();									
									__xthat.find('label').html('&nbsp');
								}).blur(function(){
									if(U(this).val() == ''){
										__xthat.find('label').html(_text);
									}
								})								
							}
						};
						DK_from.set();
					};
					$('.DK_xform').xform();
				},
				getTimeDate : function(){
					var timeData = {};
					if ($("[name='sel_christian']").val() == "0") {
						timeData.bc = 1;
						timeData.timestr = $("#date_a").val();
					} else {
						timeData.bc = -1;
						if ($.trim($("[name='yearNum']").val()).length == 0) {
							$("[name='yearNum']").focus();
							return false;
						}
						timeData.timestr = parseInt($("[name='yearNum']").val()) * parseInt($("[name='sel_yearUnit']").val());
						if ($.trim($("[name='sel_month']").val()).length != 0 && $("[name='sel_yearUnit']").val() == 1) {
							timeData.timestr += '-' + $("[name='sel_month']").val();
						}
						if ($.trim($("[name='sel_Days']").val()).length != 0 && $("[name='sel_yearUnit']").val() == 1) {
							timeData.timestr += '-' + $("[name='sel_Days']").val();
						}
					}
					timeData.timedesc = $("[name='txt_explain']").val();
					return timeData;
				},
				lightBtn : function(inputThis){ //点亮发表按钮					
					switch(nowPost){
						case 'group':
							switch(inputThis){
								case 'groupTitleTex':
									if($('#' + inputThis).val() != ''){
										groupIF[1] = 1;
									}else{
										groupIF[1] = 0;
									}
								break;
								case 'groupPrice':
									if($('#' + inputThis).val() != ''){
										groupIF[2] = 1;
									}else{
										groupIF[2] = 0;
									}
								break;
								case 'groupNowPrice':
									if($('#' + inputThis).val() != ''){
										groupIF[3] = 1;
									}else{
										groupIF[3] = 0;
									}
								break;
							};
							if(groupIF[0] == 1 && groupIF[1] == 1 && groupIF[2] == 1 && groupIF[3] == 1){
								$('#distributeButton').parent().removeClass('disable').addClass('active');
							}else{
								$('#distributeButton').parent().removeClass('active').addClass('disable');
							}
						break;
						case 'dish':
							switch(inputThis){
								case 'dish_title':
									if($('#' + inputThis).val() != ''){
										dishIF[1] = 1;
									}else{
										dishIF[1] = 0;
									}
								break;
								case 'dish_price':
									if($('#' + inputThis).val() != ''){
										dishIF[2] = 1;
									}else{
										dishIF[2] = 0;
									}
								break;
								case 'dish_decs':
									if($('#' + inputThis).val() != ''){
										dishIF[3] = 1;
									}else{
										dishIF[3] = 0;
									}
								break;
								};
								if(dishIF[0] == 1 && dishIF[1] == 1 && dishIF[2] == 1 && dishIF[3] == 1){
									$('#distributeButton').parent().removeClass('disable').addClass('active');
								}else{
									$('#distributeButton').parent().removeClass('active').addClass('disable');
								}
							break;
					};
				},
				sendInfoGroup : function(){					
					var othis = this;
					var POST = 1;
					postTimeData = postInfo.getTimeDate();
					//传递参数
					var values = {
						type : 'groupon',
						web_id : U('.web_id').val(),
						imgTag : imgTag,
						groupname : group.find('#groupTitleTex').val(),
						oriprice : group.find('#groupPrice').val(),
						currprice : group.find('#groupNowPrice').val(),
						expiretime : group.find('#groupDate').val() + ' ' + group.find('#groupNowHouers').text(),
						href : group.find('#groupLink').val(),
						timestr: postTimeData.timestr,
						timedesc: postTimeData.timedesc,
						bc: postTimeData.bc
					};

					if(values.imgTag == ''){
						$.alert('请上传图片！');
					}else if(values.groupname == ''){
						$.alert('请输入促销标题！');
					}else if(values.groupname.length > 200){
						$.alert('促销标题过长！');
					}else if(values.oriprice == '' || values.oriprice.search(/^\d+\.{0,1}\d+$/) == -1 || values.currprice == '' || values.currprice.search(/^\d+\.{0,1}\d+$/) == -1){
						$.alert('原价或现价只能输入整数或小数。如价格低于10，后面请加小数点，如：7.00');
					}else if(parseInt(values.oriprice) < parseInt(values.currprice)){
						$.alert('现价不能高于原价！');
					}else if($('#groupDate').val() == ''){
						$.alert('请选择结束时间');
					}else{
						$.djax({
							url: mk_url("channel/catering_groupon/add"),
							dataType: "jsonp",
							data: values,
							async: true,
							success:function(data) {
								if (data.status == 1) {			
									othis.groupshow(data , values);
									$('#groupDK').html(groupHtml);	
									othis.init();
									$("#groupDate").calendar({button:false,time:false});									
								} else {
									$.alert('发布失败了，请稍后重试！');
								}

							},
							error:function(data) {}
						});
					}
				},
				sendInfoDish : function(){
					var othis = this;
					postTimeData = postInfo.getTimeDate();
					pp_id = $('#ppweb_id').text();
					($('#dish_cais').attr('iid') == '') ? catid = pp_id : catid = $('#dish_cais').attr('iid')
					var values = {
						type : 'dish',
						web_id : U('.web_id').val(),
						imgTag : imgTag,
						name : U('#dish_title').val(),
						price : U('#dish_price').val(),
						//catid : catid,
						ppweb_id : $('#ppweb_id').text(),
						description : U('#dish_decs').val(),
						timestr: postTimeData.timestr,
						timedesc: postTimeData.timedesc,
						bc: postTimeData.bc
					};
					if(values.imgTag == ''){
						$.alert('请上传图片！');
					}else if(values.name == ''){
						$.alert('请输入菜品名称！');
					}else if(values.price =='' || values.price.search(/^\d+\.{0,1}\d+$/) == -1){
						$.alert('菜品价格只能输入整数或小数。如价格低于10，后面请加小数点，如：7.00');
					}else if(values.description == ''){
						$.alert('请输入菜品描述信息！');
					}else{
						$.djax({
							url: mk_url("channel/catering_dish/add"),
							dataType: "jsonp",
							data: values,
							async: true,
							success : function(data) {
								if (data.status == 1) {	
									othis.dishShow(data , values);
									$('#dishDK').html(dishHtml);
									postInfo.init();
								}else{
									$.alert('发布失败了，请稍后重试！');
								}
							},
							error:function(data) {}
						});
					}
				},
				getdishCatid : function(){
					var ppHTML = '<div id="pingpaiBox" ><div class="ppbox_left" style="height:180px;" id="ppbox_left"></div></div>';				
					//$('body').append(ppHTML);
					
					var _dl = '',_dd = [];
					var pp_id = $('#ppweb_id').text();						
					var _url = mk_url('channel/catering_dish/get_category_tree');
					$.ajax({
						url : _url,
						method: "POST",
						data : {catid : pp_id},
						dataType : 'jsonp',
						success : function(data){					
							var D = data.data.data;
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
							}
																										
							$('#ppbox_left').html(_content);
							$('#ppbox_left').find('dl').each(function(e){
								var _ddLen = $(this).find('dd');
								if(_ddLen.length < 1){
									$(this).append('<dd>该类别下无对应菜系</dd>')
								}
							});
						}
					});
					
					$(this).popUp({
						width : 240,
						height : 200,
						title : '选择菜系',
						content : ppHTML,
						callback : function(){	
							var _checkV = $('#pingpaiBox').find('input:checked');
							if(_checkV.length > 0){
								$('#dish_cais').attr('iid',_checkV.attr('id')).html(_checkV.val());
							}
							$.closePopUp();
						}
					});
					
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
				setEvent : function(){
					var sThis = this;
					$('#groupDK').find('input,textarea').keyup(function(){				
						sThis.lightBtn($(this).attr('id'));
					});
					$('#dishDK').find('input,textarea').keyup(function(){
						sThis.lightBtn($(this).attr('id'));
					});
					U('.dish_cais').live('click',function(){
						sThis.getdishCatid();
					});
					
					U('#distributeButton').off('click');
					U('#distributeButton').live('click',function(){
						if(nowPost == 'group'){
							sThis.sendInfoGroup();
						}else if(nowPost == 'dish'){
							sThis.sendInfoDish();
						}
					});
					
					group.find('.inputField').each(function(e){
						var _t = U(this);
						_t.find('input').live('focus',function(){
							if(U(this).val() == ''){
								sThis.hideInput(e);
							}	
						}).blur(function(){
							if(U(this).val() == ''){
								sThis.showInput(e);
							}
						});
						_t.find('label').live('click',function(){
							_t.find('input').focus();
						});
						
					});	
					//团购标题
					U('#groupTitleTex').live('focus',function(){
					}).blur(function(){
						if($(this).val() == ''){
							U(this).css({'height' : '30px'});
						}
					});
					$.groupHtml($("#groupDK").html());
					$.dishHtml($("#dishDK").html());
				},
				hideInput : function(e){
					_tx = group.find('label').eq(e).text()
					group.find('.inputField').eq(e).find('label').text('');
				},
				showInput :function(e){
					group.find('.inputField').eq(e).find('label').text(_tx);
				},
				groupshow : function(data , values){			
					var $content = $('.time').children("ul.content");
					var sideClass, clickDown, tipTxthighlight;
					if (goods_offset == 0) {
						sideClass = "sideLeft";
						goods_offset = 1
					} else {
						sideClass = "sideRight";
						goods_offset = 0
					}
					
					var location = mk_url("webmain/index/index", {web_id: values.web_id});
					data.data = data.data.data;

					var timeData = [
						data.data.ymd.year,
						data.data.ymd.month,
						data.data.ymd.day,
						data.data.ymd.hour,
						data.data.ymd.minute,
						data.data.ymd.second
					];
					
					var faceSrc = $('#topUserAvatar').attr('src');
					
					
					//团购渲染开始
				   var str = '<li name="timeBox" scale="true" id="' + data.data.tid + '"  fid="' + data.data.fid + '" uid="' + data.data.uid + '" type="' + data.data.type + '" highlight="' + data.data.highlight + '" time="' + data.data.ctime + '" timeData ="' + timeData + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><!--<span class="conWrap midLine tip_up_left_black ' + clickDown + '" tip="' + tipTxthighlight + '"><a><i class="conResize"></i></a></span>--><span class="conWrap tip_up_right_black" tip="编辑或删除"><a><i class="conEdit"></i></a><ul class="editMenu hide"><li class="sepLine"></li><li name="delTopic"><i class="delTopic"></i>删除帖子...</li></ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + faceSrc + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + data.data.uname + '</a></div><div class="postTime"><a href="javascript:void(0)">' + data.data.friendly_time + '</a>';
					
				   var ctime = data.data.ctime;
				   var dateline = data.data.dateline;
					

					if (data.data.ctime != data.data.dateline && data.data.friendly_line) {
						str += '<i class="insertTime tip_up_left_black" tip="' + data.data.friendly_line + '"></i>';
					}
						
					var msgname = data.data.title || "";
					var groupon = data.data.groupon;
					 
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
						},
						parseDateTime : function(second){
							day = parseInt(second/(24*3600));
							hour = parseInt((second - day*24*3600)/3600);	
							mins = parseInt((second - day*24*3600 - hour*3600)/60);
							secs = second - day*24*3600 - hour*3600 - mins*60;				
							if(second < 0){
								diff = groupEnd;
							}else{
								this.appendTime();
								
							}			
						}
						
					}
					infoGroup.parseDateTime(data.data.diff);
					
					
					groupStr = '<h2 class="group_showtitle">'+ groupon.title +'</h2>'
								+'<div class="group_showimg"><img src="'+ groupon.img[0].b.url +'" /><span>原价：<del>'+ groupon.original_price +'</del>元 <em>折扣：'+ groupon.discount +'折</em><em class="group_showtime">剩余时间：<i>'+ diff +'</i></em></span></div>'
								+'<p class="group_showgosee">团购 ￥<em>'+ groupon.current_price +'</em><a href="'+ groupon.href +'" target="_blank">现在去看看</a></p>'
						
					
					 str += '</div></div></div><div class="infoContent">'+ groupStr +'</div><div class="commentBox pd" msgname="' + msgname + '" commentObjId="' + data.data.tid + '" pageType="web_topic" ctime="' + data.data.ctime + '" action_uid="' + data.data.uid + '"></div></li>';
					
					$content.children('li').eq(0).before(str);
					$('html,body').animate({scrollTop : $('#distributeButton').offset().top},400);
				},
				dishShow : function(data , values){				
					var $content = $('.time').children("ul.content");
					var sideClass, clickDown, tipTxthighlight;
					if (goods_offset == 0) {
						sideClass = "sideLeft";
						goods_offset = 1
					} else {
						sideClass = "sideRight";
						goods_offset = 0
					}				
					var location = mk_url("webmain/index/index", {web_id: values.web_id});
					data.data = data.data.data;
					var timeData = [
						data.data.ymd.year,
						data.data.ymd.month,
						data.data.ymd.day,
						data.data.ymd.hour,
						data.data.ymd.minute,
						data.data.ymd.second
					];			
					var faceSrc = $('#topUserAvatar').attr('src');
					//菜品渲染开始
					 var str = '<li name="timeBox" scale="true" id="' + data.data.tid + '"  fid="' + data.data.fid + '" uid="' + data.data.uid + '" type="' + data.data.type + '" highlight="' + data.data.highlight + '" time="' + data.data.ctime + '" timeData ="' + timeData + '" class="' + sideClass + '"><i class="spinePointer"></i><div class="timelineBox"><div class="editControl hide"><!--<span class="conWrap midLine tip_up_left_black ' + clickDown + '" tip="' + tipTxthighlight + '"><a><i class="conResize"></i></a></span>--><span class="conWrap tip_up_right_black" tip="编辑或删除"><a><i class="conEdit"></i></a><ul class="editMenu hide"><li class="sepLine"></li><li name="delTopic"><i class="delTopic"></i>删除帖子...</li></ul></span></div><div class="headBlock clearfix"><a href="' + location + '" class="headImg"><img src="' + faceSrc + '" width="32" height="32" alt="" /></a><div class="unitHeader"><div class="AuthorName"><a href="' + location + '">' + data.data.uname + '</a></div><div class="postTime"><a href="javascript:void(0)">' + data.data.friendly_time + '</a>';

					var ctime = data.data.ctime;
					var dateline = data.data.dateline;
					var msgname = data.data.title || "";

					if (data.data.ctime != data.data.dateline && data.data.friendly_line) {
						str += '<i class="insertTime tip_up_left_black" tip="' + data.data.friendly_line + '"></i>';
					}
					
					DISH = data.data.dish;
					
					var dish_html = '<div class="view_dish"><p class="dish_showBimg"><img src="'+ DISH.pics[0].b.url +'" alt="'+ DISH.name +'" /></p><p class="dish_decs"><span><strong>【'+ DISH.name +'】</strong>'+ DISH.description +'</span></p><p class="dish_price"><span>￥'+ DISH.price +'</span></p></div>';
					
					str += '</div></div></div><div class="infoContent">'+ dish_html +'</div><div class="commentBox pd" msgname="' + msgname + '" commentObjId="' + data.data.tid + '" pageType="web_topic" ctime="' + data.data.ctime + '" action_uid="' + data.data.uid + '"></div></li>';
					
					$content.children('li').eq(0).before(str);
					$('html,body').animate({scrollTop : $('#distributeButton').offset().top},400);
										
				}
			};
			if($('#groupDK')[0]){
				postInfo.init();
			}			
		})($);
	}
}
Dmimi.dinnerInit();

