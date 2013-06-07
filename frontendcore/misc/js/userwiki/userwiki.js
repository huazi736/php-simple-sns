/**
 * Created on  2011-12-14 
 * @author: 梁珊珊(ssinsky@hotmail.com)	
 * @desc: 资料编辑模块
 */
$(function(){
	window.miscpath = CONFIG['misc_path'];
	var mpath = mk_url('user/userwiki/getBlockTpl'),
		$birthday = $("#birthday"),
		$birthdayVal = "";
    window.wiki_Tip=function (o,t,w,f) {
  		window.tips = $(o).tip({
			className:"tip_up",
			showOn:"click",
			hold:true,
			arrowMargin:5,
			tipMargin:5,
			width:w,
			djax:{
				data:({blockName:t}),
				cache:false,
				url:mpath,
				dataType:"json",
				success:function(a,b,c){
					c.position();
					tips._class = c;
					if(f){
						f(a,b);
					}	
				}
			}
		});
	};	
	
	wiki_Tip($('.dkUserwikiBacicInfo'),'base',450);//基本资料	
	wiki_Tip($('.dkUserwikiPrivateInfo'),'private',530);//私密资料	
	wiki_Tip($('.dkUserwikiRelationships'),'relationships',500,relativemates);//家庭关系
	wiki_Tip($('.dkUserwikiIntroInfo'),'intro',530,intro);//自我介绍

	$('.timelinewiki').find("input.msg").msg();

	$('body').on('click','a.closeTip',function(){//取消
		$(this).closest('.tip_win').hide();	
	});
	
	/*****************start:删除*****************/
	var closeArr = $("i.ui_closeBtn");
	function htmlspecialchars(str){
		return $('<span>').text(str).html();
    }

	function closeF(closeArr){
		closeArr.hide().hover(function(){
			$(this).css("background-position","0px -15px");
		},function(){
			$(this).css("background-position","0px 0px");
		});
		closeArr.click(function(){
			var $obj = $(this).closest('li'),
			 	block = $(this).closest('.uiList').prev('div').find('h4').attr('block');
			if(block == "education"){
				block = $(this).closest('ul').attr('block');
			}
			var dataId = $obj.attr('id'),
				tid = $obj.attr('tid'),
				type = $obj.attr('type'),
				data = {id:dataId.substring(dataId.indexOf('_')+1)};
			if(tid){
				data.tid = tid;
			}
			if(type){
				data.type = type;
			}
			var change = 0;

			if ((block=='job')&&($obj.find('span[name="today"]').length)) {
				change = 1;
			};
			$(this).popUp({
				width:357,
				title:"删除资料",
				content:"<strong style='display:block; padding:15px; em-size:14px;'>您确定要删除该条数据吗？</strong>",
				buttons:'<span class="popBtns blueBtn callbackBtn">确定删除</span><span class="popBtns closeBtn">取消</span>',
				callback:function(){
					$.djax({//提交数据
						type:"POST",
						url:mk_url("user/jobandschooldataedit/"+block+"Delete"),
						dataType:'json',
						data:data,
						success:function(data){
							if(data.status==1){
								$.closePopUp();
								$obj.remove();
								if(change){
									$('#jobInfo').attr('today',0);
								}
							}
						},
						error:function(){			
						}
										
					});	
				},
				mask:true,
                maskMode:true
			});		
		});
	}
	closeF(closeArr);	
	function hoverF($obj){
		$obj.hover(function(){
				$("i.ui_closeBtn",this).show();
			},function(){
				$("i.ui_closeBtn",this).hide();
		});
	}
	var liItem=$('li.uiListItem');
	hoverF(liItem);
	/***************end:删除*************/
				
	/**********初始化年份月份SELECT******/
	function InitTime(timearr,select,type){
		
		var timearr = formatTime(timearr),
            startYear = new Date().getFullYear(),
			startMonth = new Date().getMonth()+1,
			endYear = '',
			endMonth = '',
			t = '',
			compt = [];	
		if(timearr[0] instanceof Array){
			switch (type){
				case 0://大学
					t = timearr[4];
					compt.push(Number(t[0])*100+Number(t[1]));
					compt.push('true');

				break;
				case 1://中学
					t = timearr[2];
					compt.push(Number(timearr[2][0])*100+Number(timearr[2][1]));
					compt.push(Number(timearr[3][0])*100+Number(timearr[3][1]));
					if (timearr[3]!='true') {
						startYear = timearr[3][0];
						startMonth = timearr[3][1];
					};
				break;
				case 2://小学
					t = timearr[0];
					compt.push(Number(t[0])*100+Number(t[1]));
					compt.push(Number(timearr[1][0])*100+Number(timearr[1][1]));
					if (timearr[1]!='true') {
						startYear = timearr[1][0];
						startMonth = timearr[1][1];
					};	
				break;
				case 3://工作
					t = timearr[0];
					startYear = new Date().getFullYear();
					startMonth = new Date().getMonth()+1;

			}
			endYear = t[0];
			endMonth = parseFloat(t[1]);//将09-00转9-0

		}else{
			$(this).popUp({
				width:357,
				title:"删除资料",
				content:"<strong style='display:block; padding:15px; em-size:14px;'>请先填写“基本资料——出生日期”</strong>",
				buttons:'<span class="popBtns closeBtn">确定</span>',
				mask:true,
                maskMode:true
			});	
			return 'nobirth';
		}
		for(var i=startYear; i>=endYear; i--){
			$(".set_year", select).append("<option value='"+i+"'>"+i+"</option>");
		}
		$(".set_year",select).change(function(){
			$(this).next(".set_month").empty();
			$(this).next(".set_month").append("<option value='-1'>请选择</option>");

			selYear = $(this).val();
			
			if(selYear == endYear){
				if(endMonth<=9){
					for(var i = endMonth; i<=9; i++){
						$(this).next(".set_month").append("<option value='0"+i+"'>0"+i+"</option>");
					}
					for(var i = 10; i<=12; i++){
						$(this).next(".set_month").append("<option value='"+i+"'>"+i+"</option>");
					} 
				}else{
					for(var i = endMonth; i<=12; i++){
						$(this).next(".set_month").append("<option value='"+i+"'>"+i+"</option>");
					} 
				}
				return;
			}
			var ei = 12;
			if(selYear == startYear){
				ei = startMonth;
			}
			if(ei<=9){
				for(var i = 1; i<=ei; i++){
					$(this).next(".set_month").append("<option value='0"+i+"'>0"+i+"</option>");
				}					 
			}else{
				for(var i = 1; i<=9; i++){
					$(this).next(".set_month").append("<option value='0"+i+"'>0"+i+"</option>");
				}
				for(var i = 10; i<=ei; i++){
					$(this).next(".set_month").append("<option value='"+i+"'>"+i+"</option>");
				} 
			}
		});
		return compt;
	}

	function formatTime(str){
		var format = [];
		var arr = str.indexOf(',')? str.split(','):(str.indexOf('-')?format.push(str.split('-')):'true');
		for (var i = 0; i < arr.length; i++) {
			if (arr[i] == 0) {
				format.push('true');
				continue;
			};
			ym = arr[i].split('-');
			format.push(ym);
		};
		return format; 
	}

	function compareyear(y,m){//与出生年份比较
		
		var birthday = $.trim($('#birthday').text());
		if(birthday){
			var _b = birthday.split('-');
			var b_y = Number(_b[0]);
			var b_m = Number(_b[1]);
			var data = {};

			if((Number(y)*100+Number(m))<(b_y*100+b_m)){
				data = {'status':0,'msg':'你选择的时间不能早于出生时间'};
			}else{
				data = {'status':1};
			}
			return data;
		}else{
			return  {'status':1};
		}
	}
	
	function errorMsg (msg,obj) {//错误提示
		obj.closest('tr').find('.line_err_msg').text(msg);
	}

	$('body').on('keyup','textarea',function(){//textarea最大字符输入限制
		var obj = $(this);
		var tip = obj.closest('td').find('.tip');
		limitStrNum(obj,tip);
	});
	
	function checkAdd(o,n,m){
		if (o.children('li').length == n) {
			$('.tip_win:visible').hide();
			$(this).popUp({
				 width:350,
                title:'友情提示',
                content:'<div style="padding:16px">'+m+'</div>',
                buttons:'<span class="popBtns blueBtn closeBtn">确定</span>',
                mask:true,
                maskMode:true
			});
			return false;
		}else{
			return true;
		}
	}
	function popError(msg){
		$(this).popUp({
			width:350,
            title:'友情提示',
            content:'<div style="padding:16px">'+msg+'</div>',
            buttons:'<span class="popBtns blueBtn closeBtn">确定</span>',
            mask:true,
            maskMode:true
		});
	}
	$("body").delegate("[type='text']","focusin",function(){
		var wikicontentWrap = $(this).closest('.wikicontentWrap');
		wikicontentWrap.find('.line_err_msg').html('');
		wikicontentWrap.next().find('.line_err_msg').html('');
	});
	$("body").delegate("select","change",function(){
		var wikicontentWrap = $(this).closest('.wikicontentWrap');
		wikicontentWrap.find('.line_err_msg').html('');
		wikicontentWrap.next().find('.line_err_msg').html('');
	});


	/******以下为工作情况，教育情况*****************************************************/
	window.currentPositionCode = '';//职位
	window.departmentId = '';//学院，院系
	function getHTML(arg){//获取模版
		$.djax({
			data:{blockName:arg[0]},
			url:mpath,
			dataType:"json",
			success:arg[1]
		});
	}
	function sendData(arg){//发送数据
		$.djax({
			url:arg[0],
			data:arg[1],
			success:arg[2]
		});
	}
	function getObject(arg){//获取值
		var temp = {};
		$.each(arg[0].find("[name]:visible"),function(){
			var name = $(this).attr("name");
			temp[name] = [];
		});
		$.each(arg[0].find("[name]:visible"),function(){
			var name = $(this).attr("name");
			var tagName = $(this)[0].tagName;//.attr("tagName");
			var tr = $(this).closest("tr");

			switch(tagName){
				case "SPAN":
					temp[name] = $(this).text().split(" ");
					if($(this).attr("key")){
						temp[name] = {};
						temp[name] = {id:$(this).attr("key"),text:$(this).text()};
					}
				break;
				case "INPUT":
					if($(this).attr("type")=="checkbox"){
						if($(this).attr("checked")){
							temp[name].push($(this).val());
						}
					}else{
						temp[name].push($(this).val());
					}
				break;
				case "SELECT":
					if(tr.find("em").size()!=0&&$(this).val()=="-1"){
						$(this).popUp({
							width:357,
							title:"提示",
							content:"<strong style='display:block; padding:15px; em-size:14px;'>打*号必填</strong>",
							buttons:'<span class="popBtns blueBtn closeBtn">确定</span>',
							maskMode:true
						});
						return temp=false;
					}
					temp[name]={id:$(this).val(),text:$(this).children("option:selected").text()};
				break;
				case "DIV":
					var names=[];
					var dkcode=[];
					$(this).find(".name").each(function(){
						names.push($(this).text());
						dkcode.push($(this).attr('href').split('dkcode=')[1]);
					});
					temp[name] = {names:names.join(' '),id:$(this).attr("id"),dkcode:dkcode.join(',')};
					break;
			}
		});
		
		return temp;//{name:value}
	}
	function setObject(arg){//填充模版
		var check=arg[0].find("[name='today']")&&(arg[1].today=='');
	
		if(check!==false){
			var str='<span class="weak_txt"><span name="time_y_s" key="'+arg[1].time_y_s+'">'+arg[1].time_y_s+'</span>年<span name="time_m_s" key="'+arg[1].time_m_s+'">'+arg[1].time_m_s+'</span>月&mdash;&mdash;<span name="time_y_e" key="'+arg[1].time_y_e +'">'+arg[1].time_y_e +'</span> 年<span name="time_m_e" key="'+arg[1].time_m_e +'">'+arg[1].time_m_e +'</span>月</span>';
			arg[0].find("[name='today']").closest('.weak_txt').html(str);
		}else if(arg[0].find("[name='time_y_e']")&&(arg[1].today=='至今')){
			var str='<span name="time_y_s" key="'+arg[1].time_y_s+'">'+arg[1].time_y_s+'</span>年<span name="time_m_s" key="'+arg[1].time_m_s+'">'+arg[1].time_m_s+'</span>月&mdash;&mdash;<span name="today">至今</span>';
			arg[0].find("[name='time_y_e']").closest('.weak_txt').html(str);
		}
		if(('classmate' in arg[1]||'colleague' in arg[1])&&(arg[0].find('div.weak_txt').length==0)){
			var str = '<div class="weak_txt">目前同事：<div id="" class="breakWord colleague" name="colleague"></div></div>';
			arg[0].find('h5.itemHead').next().append(str);
		}
		$.each(arg[0].find("[name]"),function(){
			var name = $(this).attr("name");
			var tagName = $(this)[0].tagName;
			var value = arg[1][name]||[];
			var elm = $(this);
			
			switch(tagName){
				case "SPAN":
					if(value.length>0){
						$(this).text(value.join(" "));
					}else{
						$.each(value,function(a,b){
							elm.text(b).attr("key",a);
						});
					}
				break;
				case "INPUT":
					if($(this).attr("type")=="checkbox"){
						if(value.length>0){
							$(this).trigger('change').attr("checked",true);	
						}
					}else{
						var li = "";
						if(value.length>1){
							$.each(value,function(i,v){
								if(i=="0"){
									elm.val(v);
								}else{
									li+='<li style="margin-top:1px;"> <input uid="0" value="'+v+'" class="vinput" name="'+name+'"><span class="delTr"><img alt="" src="'+webpath+'misc/img/system/cut.gif"></span></li>';
								}
							});
							elm.parent("li").after(li);
						
						}else{
							$(this).val(value[0]);
						}
					}
					
				break;
				case "SELECT":
					$(this).children().each(function(){
						if($(this).text()==value.text){
							$(this).attr('selected',true).trigger('change');
						}
					});
				break;
				case "DIV":

					if('classmate' in arg[1]||'colleague' in arg[1]){
						var html = '';
						var name = value.names.split(' ');
						if (name[0]==='') {
							$(this).html(html);
							return;
						};
						var ids = value.id.split(',');
						var dkcode = value.dkcode.split(',');
						for (var i = 0,len = name.length; i < len; i++) {
							if ($(this).hasClass('tagList')) {
								html +='<span><a id="'+ids[i]+'" href="dkcode='+dkcode[i]+'" class="name">'+name[i]+'</a><a class="tagDel" href="javascript:void(0)"></a></span>';
							}else{
								html +='<a href="index.php?c=index&amp;m=index&amp;action_dkcode='+dkcode[i]+'" class="name">'+name[i]+'</a>';
							};
							
						};
						$(this).attr('id',ids.join(',')).html(html);
					}else if($(this).hasClass('colleague')){
						$(this).parent().remove();
					}
			}

		});
	}
	function bindEvent(arg){//绑定事件
		var oldData = arg[1];
		arg[0].find('.tagList').bind("click",function(){//选择好友
			var tagList = $(this);
			var ids = tagList[0].id.replace(' ',',');
			new CLASS_FRIENDS_LIST({
				getUrl:mk_url('user/userwiki/getFriends'),
				ids:ids,
				title:'添加同学/同事',
				elm:$('.addTr'),		//触发好友窗口点击对象
				noData:"您还没有任何好友",
				callback:function(data){
					if (!data.allname) {
						tagList.attr('id',data.ids).children().remove();
						return;
					}
					var names = data.allname.split(' ');
					var ids = data.ids.split(',');
					var dkcode = data.dkcode.split(',');
					var str = '';
					for (var i = 0,len = names.length; i < len; i++) {
						str +='<span><a class="name" href="dkcode='+dkcode[i]+'"  id="'+ids[i]+'">'+names[i]+'</a><a href="javascript:void(0);" class="tagDel"></a></span>';
					};
					tagList.attr('id',data.ids).attr('names',data.allname).children().remove().end().append(str);
					deleteP(tagList.find('.tagDel'));
					preventD(tagList.find('span'));
				}
			});	
		});
		function deleteP(dom){//删除好友
			dom.click(function(e){
				e.stopPropagation();
				var uid = $(this).prev('a').attr('id');
				var div = $(this).closest('div');
				var ids = div.attr('id');
				ids = ids.replace(uid+",",'');
				ids = ids.replace(uid,'');
				$(this).closest('span').remove();
				div.attr('id',ids);
			});
		}
		function preventD(dom){
			dom.click(function(e){
				e.stopPropagation();
				return false;
			});
		}
		preventD(arg[0].find('span'));
		deleteP(arg[0].find('.tagDel'));

		arg[0].find(".close").bind("click",function(){//取消
			var div = $(this).closest("div.dkUserwikiNice");
			if(div.prev().length){
				div.prev().show();				
				div.remove();
			}else{
				var input = $(this).closest('ul').prev('div').find('input');
				input.val(input.attr('default'));
				div.parent('li').remove();
			}
		});

		function timelist(scope,time,_this){
			if (time<scope[0]||(scope[1]!='true')&&(scope[1]<time)) {
				errorMsg('请填写正确的年份',_this);
	            _this.removeClass('protected');
				return 'stop';
			};
		}

		arg[0].find(".btn_edit").bind("click",function(){//修改

			if (!$(this).hasClass('protected')) {
				$(this).addClass('protected');
				var _this = $(this);
				var li = $(this).closest("li");
				li.removeAttr("new");
				var div = $(this).closest("div.dkUserwikiNice");
				var temp = getObject([div]);
				if (temp==false) {
					$(this).removeClass('protected');
					return;
				}
				if(temp.time_m_s){
					var cd=compareyear(Number(temp.time_y_s.text),Number(temp.time_m_s.text));//与出生时间比较
					if(cd.status==0){
						errorMsg(cd.msg,_this);
						return;
					}
				}
				if (temp.school_year) {
					var ifstop = timelist(arg[4],(Number(temp.school_year.text)*100 + Number(temp.school_month.text)),_this);
					if(ifstop == 'stop'){
						return
					}
				};
				/*开始时间结束时间验证*/
				if(temp.time_m_e&&(Number(temp.time_y_s.text)*100+Number(temp.time_m_s.text))>(Number(temp.time_y_e.text)*100+Number(temp.time_m_e.text))){
	                errorMsg('结束时间不能早于开始时间',_this);
	                _this.removeClass('protected');
					return;
				}
				var today = $('#jobInfo').attr('today');

				if (today==1&&(temp.today)&&(temp.today[0]=='至今')&&(!(oldData.hasOwnProperty("today") ))){
					
					errorMsg('您只能添加一条当前工作单位',_this);
	                _this.removeClass('protected');
					return;
				}

				var name  = temp.classmate||temp.colleague
					,len  = name&&(name.length);
	
				if(temp){

					setObject([div.prev(),temp]);

					if (temp.today&&(temp.today[0]=='至今')&&($('#jobInfo').attr('today')==0)) {
						$('#jobInfo').attr('today',1);
					}else if(temp.today&&(temp.today.length==0)&&
						((oldData.today)
							&&(oldData.today[0]=='至今'))
						){
						$('#jobInfo').attr('today',0);
					};

					var data = {};
					$.each(temp,function(i,v){

						if(v){
							if(v.length>0){
								if(v.length==1){
									data[i] = v.join("");
								}
								if(v.length>1){
									data[i] = v;
								}
							}else{
								data[i] = v.id;
							}
						}
	 				});

					if(arg[2]==3){
						data.companyId=div.parent().attr('id');
						if(currentPositionCode){
							data.positionId=currentPositionCode;
						}
					}else{
						data.schoolId=div.parent().attr('id');

						if(departmentId){
							data.departmentId=departmentId;
						}
					}
					data.tid=li.attr('tid');
					data.type = arg[2];

					sendData([mk_url('user/jobandschooldataedit/'+arg[3]+'Edit'),data,function(json){
						// 发送完成
						if (json.status == 1) {
							_this.removeClass('protected');
							div.prev().show();
							div.remove();
						};
					}]);
					
				}
			};
		});
		
		arg[0].find(".btn_save").bind("click",function(){ //添加
			if (!$(this).hasClass('protected')) {
				$(this).addClass('protected');
				var _this = $(this);
				var li = $(this).closest("li");
				var sl = li.siblings('li').length;			
				if (sl >= 5) {
					$(this).popUp({
	                    width:350,
	                    title:'友情提示',
	                    content:'<div style="padding:16px">数据最多只能添加5条哦</div>',
	                    buttons:'<span class="popBtns blueBtn closeBtn">确定</span>',
	                    mask:true,
	                    maskMode:true
	                });
	                _this.removeClass('protected');
					return;
				}
				li.removeAttr("new");
				var div = $(this).closest("div.dkUserwikiNice");
				var temp = getObject([div]);
				if (!temp) {
					_this.removeClass('protected');
					return;
				};
				
				if (temp.school_year) {
					var ifstop = timelist(arg[4],(Number(temp.school_year.text)*100 + Number(temp.school_month.text)),_this);
					if(ifstop == 'stop'){
						return
					}
				};
				
				/*开始时间结束时间验证*/

				if(temp.time_m_e&&(Number(temp.time_y_s.text)*100+Number(temp.time_m_s.text))>(Number(temp.time_y_e.text)*100+Number(temp.time_m_e.text))){
	                errorMsg('结束时间不能早于开始时间',_this);
	                _this.removeClass('protected');
					return;
				}

				var today = $('#jobInfo').attr('today');
				if(temp.today){
					if (today==1&&(temp.today)&&(temp.today[0]=='至今')){
						errorMsg('您只能添加一条当前工作单位',_this);
		                _this.removeClass('protected');
						return;
					}
				}

				if(temp){
					var prevDiv;
					var job = $(this).closest("li").find("span[name=company]").size();
					if(job==0){
						prevDiv ='<div class="clearfix pds"><a class="dkSchImageBlock"><img width="50" height="50" src="'+CONFIG["misc_path"]+'img/system/editPageIcon2.png"></a><div class="dkContentBlock"><h5 class="itemHead"><span name="school_name"></span><i class="dkUserwikiEditIcon mls"></i><span class="ui_closeBtn_box"><i class="ui_closeBtn png" style="display: none;"></i></span></h5><div><span class="weak_txt"><span name="school_department"></span></span><span class="weak_txt"><span name="school_year"></span>年<span name="school_month"></span>月入学';
						if(arg[2]=="0"||arg[2]=='1'){
							prevDiv+='(<span name="eduCation_c"></span><span name="eduCation_m"></span>)';
						}
						if(temp.classmate){
							prevDiv+='</span><div class="weak_txt">同班同学：<div name="classmate" class="breakWord classmate">暂无 </div></div>';
						}
						prevDiv+='</div></div></div>';
					}else{
						prevDiv = '<div class="clearfix pds"><a class="dkSchImageBlock"><img width="50" height="50" src="'+CONFIG["misc_path"]+'img/system/editPageIcon1.png"></a><div class="dkContentBlock"><h5 class="itemHead"><span name="company"></span><i class="dkUserwikiEditIcon mls"></i><span class="ui_closeBtn_box"><i class="ui_closeBtn png" style="display: none;"></i></span></h5><div><span class="weak_txt"><span name="school_department"></span></span><span class="weak_txt"><span name="time_y_s"></span>年<span name="time_m_s"></span>月——';

						if(temp.time_y_e){
							prevDiv+='<span name="time_y_e"></span>年<span name="time_m_e"></span>月';
						}else{
							prevDiv+='<span name="today">至今</span>';
						}
						prevDiv+='</span><span class="weak_txt"><span></span><span name="industry"></span>  <span name="position"></span></span>';
						if(temp&&temp.colleague){
							prevDiv+='<div class="weak_txt">目前同事：<div name="colleague" class="breakWord colleague"></div></div>';
						}
						prevDiv+='</div></div></div>';
					}
					
					var data = {};
					$.each(temp,function(i,v){
						if(v){
							if(v.length>0){
								if(v.length==1){
									data[i] = v.join("");
								}
								if(v.length>1){
									data[i] = v;
								}
							}else{
								data[i] = v.id;
							}
						}
	 				});

	 				if(arg[2]==3){
						data.companyId=div.parent().attr('id');
						data.positionId=div.find('input[name="position"]').attr('id_code');
						if(!data.positionId){
							errorMsg('打*号必填',_this);
	                		_this.removeClass('protected');
					        return;
						}
					}else{
						data.schoolId=div.parent().attr('id');
						data.pid=div.parent().attr('pid');
						if (arg[2]==0) {
							if(departmentId){
								data.departmentId=departmentId;
							}else{
								errorMsg('打*号必填',_this);
		                		_this.removeClass('protected');
						        return;
							}
						};
						
					}
					data.type = arg[2];
					div.parent().append(prevDiv);
					hoverF(div.parent().children().last('li.uiListItem'));
					closeF(div.parent().children().last().find('i.ui_closeBtn"'));
					setObject([div.parent().children(":last"),temp]);
					if (temp.today&&(temp.today[0]=='至今')&&($('#jobInfo').attr('today')==0)){
						$('#jobInfo').attr('today',1);
					}
					sendData([mk_url('user/jobandschooldataedit/'+arg[3]+'Add'),data,function(json){
						// 发送完成 
						_this.removeClass('protected');
						if (json.status == 1) {
							if(json.data.tid){
								li.attr('tid',json.data.tid);
							};
							div.parent().children(":first").show();
							div.remove();
							departmentId = '';
							currentPositionCode = ''; 		
						}else if(json.status == 0){
							div.parent().remove();
							div.remove();
							popError(json.info);
							return;
						}
					}]);					
				}
				var input = _this.closest('ul').prev('div').find('input');
				
				input.val(input.attr('default'));
			};
		});

		var department_input = arg[0].find("input[name=school_department]"),
			schId = department_input.closest('li').attr('id'),
			pId = department_input.closest('li').attr('pid');

		department_input.selectSC({
			url: mk_url('user/userwiki/show_frame',{'frame':5}),
			parm: pId+'_'+schId, //传递省份id 和 大学的id
			popWdith: 730,
			popTitle: '选择院系' //弹出层的标题
		}); 


		arg[0].find("input[name=position]").selectSC({
			url: mk_url('user/userwiki/show_frame',{'frame':6}),
			popWdith: 730,
			popTitle: '选择职位' //弹出层的标题
		}); 
	}
	function setView(arg){
		var li = arg[0].closest("li"),
			div = arg[0].closest("div.pds"),
			temp = getObject([div]);
		div.hide();
		getHTML([arg[1],function(data){
			if(data.status){
				li.append(data.data);
				var tbody=$('tbody',li);
				if(arg[2]){
					for(i=0,len=arg[2].length;i<len;i++){
						tbody.eq(arg[2][i]).hide();
					}
				}
				var timeArr= $('#timeArr',li).val();//获取时间线时间标记点				
				var theNewDiv = div.next();
				var limit = InitTime(timeArr,theNewDiv,arg[3]);//初始化时间,及返回格式化后的时间线标记点
				setObject([theNewDiv,temp]);
				bindEvent([theNewDiv,temp,arg[3],arg[1],limit]);
			}
		}]);
	}
	$("#collegeList").delegate("i.dkUserwikiEditIcon","click",function(){//编辑大学
		setView([$(this),'university',[2],0]);
	});
	$("#midSchoolList").delegate("i.dkUserwikiEditIcon","click",function(){//编辑中学
		setView([$(this),'highSchool',[1,3],1]);
	}); 
	$("#gradeSchoolList").delegate("i.dkUserwikiEditIcon","click",function(){//编辑小学
		setView([$(this),'primarySchool',[1,2,3],2]);
	});
	$("#jobList").delegate("i.dkUserwikiEditIcon","click",function(){//编辑工作
		setView([$(this),'job',[],3]);
	});
	$('#schoolInfo,#jobInfo').find("input.custom").each(function(i){//添加
		var elm= $(this),
			type,text,url,job;
		switch(elm.attr("id")){
			case "college":
				text = "大学";
				url = 1;
				edu ="university";
				o = 0;
			break;
			case "highSchool":
				text = "高中/初中";
				url = 2;
				edu ="highschool";
				o = 1;
			break;
			case "primarySchool":		
				text = "小学";
				url = 3;
				edu = "primaryschool";
				o = 2;
			break;
			case "company":		
				text = "公司";
				url = 4;
				job = "job";
				o = 2;
			break;
		}
		currentCollegeCallback = function(title,id,i,t,pid){
			var elm = $(i),
				li = "<li id='"+id+"' class='uiListItem' new='true' pid='"+pid+"' >",
				$ul = elm.parent().next(),
				temp = {},
				school = {},
				blockName ='',
				type = '';
			temp.school_name = [title];
			temp.company = [title];
			temp.schoo_id = id;
			elm.val(title).change();
			$.closePopUp();
			switch (t){
				case "college":
					blockName = 'university';
					type=[2];
					o = 0;
				break;
				case "highSchool":
					blockName = 'highSchool';
					type=[1,3];
					o = 1;
				break;
				case "primarySchool":
					blockName = 'primarySchool';
					type=[1,2,3];
					o = 2;
				break;
				case "company":
					blockName = 'job';
					o = 3;
				break;
			}
			getHTML([blockName,function(data){
				if(data.status){
					li +=data.data+'</li>';
					var $li = $(li);
					$li.attr('type',o);
					var tbody=$('tbody',$li);
					if(type){
						for(i=0,len=type.length;i<len;i++){
							tbody.eq(type[i]).hide();
						}
					}
					var timeArr = $('#timeArr',$li).val();//获取时间线时间标记点
					var theNewDiv = $li.children();
					theNewDiv.find("a.btn_save").show().prev().hide(); 
					var limit = InitTime(timeArr,theNewDiv,o);//初始化时间,及返回格式化后的时间线标记点
					if (limit == 'nobirth' ) {

						return false;
					};
					setObject([theNewDiv,temp]);
					bindEvent([theNewDiv,temp,o,blockName,limit]);
					$ul.prepend($li);
				}
			}]);
		}
		
		elm.selectSC({
			url: mk_url('user/userwiki/show_frame',{'frame':url}),
			popWdith: 730,
			popTitle: '选择'+text //弹出层的标题
		});
		 
	});

	$('body').on('change','input[name][type="checkbox"]',function(){
		if($('#now').hasClass('hideEle')){
			$('#now').removeClass('hideEle');
			$('#select').addClass('hideEle');
			$('#colleagueNow').removeClass('hideEle');
		}else{
			$('#now').addClass('hideEle');
			$('#select').removeClass('hideEle');
			$('#colleagueNow').addClass('hideEle');
		}
	});	


	/*家庭关系(参数i为编辑按钮，o为编辑面板)*/
	function relativemates(i,o){
		var le = $('.relationshipStore').length;
		
		var relativeMemeber = $('#memerberList li'),		//显示列表
			addFrames = $('#addRelativeFrames'),			//编辑列表
			len = relativeMemeber.length,					//列表长度
			str = '',										//存拼接字符串变量
			options = '<option value="1">姐姐</option><option value="2">太太</option><option value="3">妈妈</option><option value="4">女儿</option><option value="5">妹妹</option><option value="6">外祖母</option><option value="7">孙女</option><option value="8">姨妈（母亲的姐姐）</option><option value="9">姑妈（父亲的姐姐）</option><option value="10">姨妈（母亲的妹妹）</option><option value="11">姑妈（父亲的妹妹）</option><option value="12">外甥女</option><option value="13">侄女</option><option value="14">表姐妹</option><option value="15">堂姐妹</option><option value="16">姻亲（女）</option><option value="17">儿媳妇</option><option value="18">岳母</option><option value="19">婆婆</option><option value="20">女性伙伴</option><option value="21">哥哥</option><option value="22">老爷</option><option value="23">爸爸</option><option value="24">儿子</option><option value="25">弟弟</option><option value="26">外祖父</option><option value="27">孙子</option><option value="28">姨父（母亲的姐姐的丈夫）</option><option value="29">姑父（父亲的姐姐的丈夫）</option><option value="30">姨父（母亲的妹妹的丈夫）</option><option value="31">姑父（父亲的妹妹的丈夫）</option><option value="32">外甥</option><option value="33">侄儿</option><option value="34">表兄弟</option><option value="35">堂兄弟</option><option value="36">姻亲（男）</option><option value="37">女婿</option><option value="38">岳父</option><option value="39">公公</option><option value="40">男性伙伴</option>';
		//拼接关系字符串
		if(len>0){
			addFrames.empty();
			for(var i=0; i<len; i++){
				str += '<tr class="dataRow" uid="' + relativeMemeber.eq(i).attr("uid") + '"><th width="10%"></th><td width="6%" class="data img"><img class="face" src="' + relativeMemeber.eq(i).find('img').attr('src') + '" width="50" height="50" /></td><td width="35%" class="data"><div class="fname"><input id="" type="text" name="addRelationships" value=" '+ relativeMemeber.eq(i).find('a').text() +'" /></div></td><td width="35%" class="data"><select>' + options + '</select></td><td width="10%" class="data"><img class="deletAction" style="cursor:pointer" src="'+CONFIG["misc_path"]+'img/system/icon_close_01.gif" width="15" height="16" /></td></tr>';
			}
		}
		addFrames.append(str);
		//设置select值
		var addTrs = addFrames.find('tr');
		addTrs.eq(0).find('th').text('家人：');
		for(var i=0; i<len; i++){
			addTrs.eq(i).find('select').val($('#memerberList li').eq(i).find('span').attr('value')).attr('selected',1);
		}
		//阻止冒泡
		var thisTip = $('div.tip_win[tipid="' + $('.addRelationships').attr('tipid') +'"]');
		thisTip.click(function(e){
			if(e.target.name !== 'addRelationships') {
				$('#people').hide();
			}
		});
	}
	family.init();
	/*自我介绍*/
	function intro(i,o){
		var old = $('#introYour').children().html().replace(/<br>/g,'\n').replace(/<BR>/g,'\n').replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&amp;/g,'&').replace(/&quot/,'"').replace(/&apos;/,"'");
		o.find('textarea')[0].value = old;
		var SAY = '说说自己吧';
		$('#intro').focusin(function(){
			if (old == SAY) {
				$(this).val('');
			};
		})
		$('#introInfoStore').click(function(){
			var _this = $(this);
			var data = $('[name="introduction"]',$('#introInfoFormEdit')).val();
			if ((data != SAY)&&(data != old)){//判断是否发生改变
				$.djax({
					data:{'introduction':data},
					dataType:'json',
					url: mk_url('user/jobandschooldataedit/addIntroduction'),
					success:function(json){
						if (json.status) {
							data = data.replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br/>');
							if (data=='') {
								$('[name="introduction"]',$('#timelinewikiR')).html(SAY);
							}else{
								$('[name="introduction"]',$('#timelinewikiR')).html('<p class="breakWord">'+data+'</p>');
							};
							old = data;
						};
					}
				});
			}
			
			_this.closest('.tip_win').hide();
		});
	}
	
})//end:document.ready