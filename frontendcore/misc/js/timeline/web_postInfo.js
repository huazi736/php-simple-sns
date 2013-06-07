/*
 * 信息流模块(团购、租房)
 * By 贤心(xuxinjian)
 * Date:2012.07.02
 */
 
var nowPost = '';
var groupHtml = '';
(function(U){
	U('.tabHead').find("li").each(function(e){
		U('.tabHead').find("li").live('click',function(){
			nowPost = $(this).attr('rel');
		});
	});
	
	$.groupHtml = function(data){
		groupHtml = data
	};

	
})($);
var postGroHouseInit = function(){
	(function(U){
		//普通时分列表插件
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
		
		//表单交互
		U.fn.xform = function(xform){
			var init = U.extend({
				enterAuto:false
			});
		};
		
		//----------------------- 页面相关操作 ----------------------- //
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
		//提交渲染
		var group = U('#groupDK');
		var _tx,imgUrl;
		var goods_offset = 0
		var postInfo = {
			uploadPic : function(){
				var oThis = this;
				group.find(".formUpload").each(function(g) {
					var _form = U(this);
					U.uploader(this, function(response, successElm) {
						var data = U.parseJSON(response[1]);
						var msg = data.msg;
						//imgUrl = data.msg.img_url;			
						var url = msg.groupname + "/" + msg.filename + "_";
						imgUrl = url + "m." + msg.type;
						console.log(imgUrl);
						
						U('.addGroupImg').hide();
						U('.addGroupOk').show();
						group.find('.uploadWrap').css({'background':'none'});
					});
				})
			},
			sendInfo : function(){
				function getTimeDate() { 
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
				};
				postTimeData = new getTimeDate();
		
				//传递参数
				var values = {
					type : 'groupon',
					web_id : U('.web_id').val(),
					img : imgUrl,
					groupname : group.find('#groupTitleTex').val(),
					oriprice : group.find('#groupPrice').val(),
					currprice : group.find('#groupNowPrice').val(),
					expiretime : group.find('#groupDate').val() + ' ' + group.find('#groupNowHouers').text(),
					href : group.find('#groupLink').val(),
					timestr: postTimeData.timestr,
					timedesc: postTimeData.timedesc,
					bc: postTimeData.bc
				};
				
				layer_load(0,false);
				$.djax({
					url: webpath + "main/index.php?c=web&m=doPost",
					dataType: "json",
					async: true,
					data: values,
					success:function(data) {
						if (data.status == 1) {
							//console.log(data);
							layer_close();
							postInfo.groupshow(data , values);
							$('#groupDK').html(groupHtml);
							
						} else {
							layer_alert('发布失败了，请稍后重试！');
						}

					},
					error:function(data) {
						layer_alert("网络错误，请重试！");
					}
				});			
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
				var location = webpath + "main/index.php?c=index&m=index&web_id=" + values.web_id;
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
				console.log(data);
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
				
				
				groupStr = '<h2 class="group_showtitle">'+ groupon.groupname +'</h2>'
							+'<div class="group_showimg"><img src="'+ groupon.img +'" /><span>原价：<del>'+ groupon.oriprice +'</del>元 <em>折扣：'+ groupon.discount +'折</em><em class="group_showtime">剩余时间：<i>'+ diff +'</i></em></span></div>'
							+'<p class="group_showgosee">团购 ￥<em>'+ groupon.currprice +'</em><a href="'+ groupon.href +'" target="_blank">现在去看看</a></p>'
					
				
				 str += '</div></div></div><div class="infoContent">'+ groupStr +'</div><div class="commentBox pd" msgname="' + msgname + '" commentObjId="' + data.data.tid + '" pageType="web_topic" ctime="' + data.data.ctime + '" action_uid="' + data.data.uid + '"></div></li>';
				
				$content.children('li').eq(0).before(str);
				
				for(var i=0 ; i < $content.offset().top ; i++){
					$(window).scrollTop(i);
				}
			}
		};
		
		postInfo.uploadPic();
		
		U('#distributeButton').live('click',function(){
			if(nowPost == 'group'){
				postInfo.sendInfo();
			}			
		});
		group.find('.inputField').each(function(e){
			var _t = U(this);
			_t.find('input').live('focus',function(){
				if(U(this).val() == ''){
					postInfo.hideInput(e);
				}	
			}).blur(function(){
				if(U(this).val() == ''){
					postInfo.showInput(e);
				}
			});
			_t.find('label').live('click',function(){
				_t.find('input').focus();
			});
			
		});
		
		
		//团购标题
		U('#groupTitleTex').blur(function(){
			U(this).css({'height':'30px'});
		});
	})($);
}


