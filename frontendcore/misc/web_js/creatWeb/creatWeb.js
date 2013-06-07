/**
 *@created:2012/7/5
 *@author:zhulq
 *@version:1.0
 */
 
 function Class_createWeb(){
	var _self = this;
	
	this.creatHead = $("ul.modlueBodyHeader");
	this.creatForm = $("div.modlueBodyContent");
	this.checkPage = $("div.checkpage");
	this.inputAgree = $(":input[name='blueBtn']");
	this.reg = new RegExp("^[a-zA-Z0-9_\u4e00-\u9fa5\\s·]+$");
 }
 
 Class_createWeb.prototype = {
	init : function(){
		
		var _self = this;
		if($("#pageStepone")[0]){
			_self.module_form_event(this.creatForm,this.reg);//第一页面绑定表单验证
		};
		if($("#pageStepThree")[0]){
			_self.module_check_event(this.checkPage,this.inputAgree)//第三页CHECK框事件
		};
		
		// 初使化 select_category_option
		$(".select_category_option").not($(".select_category_option[level='1']")).find('option:first').attr('selected','selected');
		//$(".select_category_option[level='1']").find("option:eq(1)").attr('selected','selected');
		
		// 选择按下
		_self.select_category_option_change(this.creatForm);	// 按下执行
		
		_self.area_init();	// 分类初使化
		
		
	
		$("#selectEdit").bind('submit', function(){	

			if(_self.module_form_ver(_self.reg, _self.creatForm) == 1){
				return false;
			} else {
				return true;
			}

		});
		
		/*
		var area	= _self.cint( $(".select_category_option[level='1']").find('option:selected').attr('area') );
		if(area==1){
			$(".area_em").show();
		}else{
			$(".area_em").hide();	
		}
		*/
						
	},
	module_check_event: function(checkPage,inputAgree){
		var _self = this,

		checkPageCheck = checkPage.find(":checkbox");	
			
		checkPageCheck.eq(0).click(function(){
			if($(this).attr('checked') == 'checked'){
				checkPageCheck.each(function(){
					this.checked = true;
				});
			}else{
				checkPageCheck.each(function(){
					this.checked = false;
				});
			}	
			
		});
		checkPage.next().find("a").toggle(function(){
			
			checkPage.siblings("div.pageAgreement").show();
		},function(){
			checkPage.siblings("div.pageAgreement").hide();
		})
	},
	module_form_event : function(creatForm,reg){
		var _self = this;
		area	= _self.cint($(".select_category_option[level='1']").find('option:selected').attr("area"));
		cityid	= _self.cint($(".select_category_option[level='1']").find('option:selected').attr("cityid"));
		if(area==1 && cityid<=0){
			_self.show_set_address();
			return false;
		}
		
		$("#nextMark").click(function(){
			$("#selectEdit").submit();
		});
		$(":input").blur(function(){

			_self.module_form_ver(_self.reg, _self.creatForm);
		});
		$("select").change(function(){
			_self.module_form_ver(_self.reg, _self.creatForm);
		});



	},
	
	module_form_ver : function(reg,creatForm){//验证方法
			var len = 0,
			mark = 0,
			inputValue = creatForm.find("ul").find("li").eq(0).find("input").val(),
			a = inputValue.split("");
			for(var i = 0; i < inputValue.length;i++){
				if(a[i].charCodeAt(0)<299){
					len++;
				}else{
					len += 2;
				}
			}

			if(len <= 0){
				creatForm.find("ul").find("li").eq(0).find("span").text("不能为空").attr("style","color:red");	
				mark = 1;
			}else if(len > 30){
				creatForm.find("ul").find("li").eq(0).find("span").text("超过15个字符").attr("style","color:red");
				mark = 1;
			}else if(!reg.test(inputValue)){
				creatForm.find("ul").find("li").eq(0).find("span").text("不能包含特殊字符").attr("style","color:red");
				mark = 1;
			}else{
				creatForm.find("ul").find("li").eq(0).find("span").text("输入正确").attr("style","color:green");
			};
 			if(creatForm.find("ul").find("li").eq(1).find("select").val() != 0){
				if(creatForm.find("ul").find("li").eq(2).find("option:selected").val() == 0){
					creatForm.find("ul").find("li").eq(2).find("span").text("必选项").attr("style","color:red");
					mark = 1;
				}else{
					creatForm.find("ul").find("li.select_category_li").eq(0).find("span").text("填写正确").attr("style","color:999");
				}			
			};
			if($('.select_category_option[level="1"]').find("option").attr("area") == 1){
				if(creatForm.find("ul").find("li").eq(1).find("select").val() != 0){
					if(creatForm.find("ul").find("li").eq(4).find("option:selected").val() == 0){
						creatForm.find("ul").find("li").eq(4).find("span").text("必选项").attr("style","color:red");
						mark = 1;
					}else{

						creatForm.find("ul").find("li").eq(4).find("span").text("填写正确").attr("style","color:999");
					}			
				};
			}
			return mark;
	},
	select_category_option_change : function(creatForm){
		var self = this;
		var url_addr	= mk_url("webmain/create/get_category");
		$('.select_category_option').live('change' , function(target) {
				var __self	= this;

				var id 		= self.cint( $(this).find('option:selected').val() );
				var level 	= self.cint( $(this).attr('level') );
				var load_level = level + 1;	// 要加载的分类id
				
				if( id<=0 && level!=1 ){	// 选译
					
					for(var i=load_level; i<=10 ; i++){	// 删除网页
							self.delete_select_category_option_level(i);
					}
				}else{	// 添加
					data_obj		= new Object();
					data_obj.id 	= id;
					data_obj.level	= load_level;
					
					$.get(url_addr , data_obj , function (data){
						if(level==1){
							cityid	= self.cint($(__self).find('option:selected').attr('cityid'));
							area	= self.cint($(__self).find('option:selected').attr('area'));
							if(area==1 && cityid<=0){
								self.show_set_address();
							}
						}
						for(var i=load_level; i<=10 ; i++){	// 删除分类
							self.delete_select_category_option_level(i);
						}
						
						
						if( self.trim(data)=="" ){	return ;	}

						
						$(".category_em").append(data);
						creatForm.find("ul").find("li").eq(4).find("select").change(function(){
							
							self.module_form_ver(self.reg, self.creatForm);
						})
						
						
					});
				}
				
/* 			if($(this).attr("level") == 2){
				if($(this).val() == 0){
					$(this).eq(2).find("span").text("必选项").attr("style","color:red");
				}else{
					$(this).eq(2).find("span").text("必选").attr("style","color:#999");
				}	
			}	 */		
		});
		
	},
	delete_select_category_option_level : function (level){	// 查询
		var self 	= this;
		level 		= self.cint( level );
		$(".category_em").find("li[level="+level+"]").remove();
	},
	
	
	area_init : function (){	// 地区选择 初使化
		
		if($('.area_em').length>=1){
			try{
				myArea_home = new initAreaComponent('now_area','1-now_nation,1-now_province,1-now_city,1-now_town','');	
				//生成地区
				myArea_home.initalize();
			}catch(e){}
		}
		
	},
	
	area_em_hide : function ( enable ){	// enable  是否显示  1显示  0隐藏
		if(enable){
			$('.area_em').show();
		}else{
			$('.area_em').hide();
		}
	},
	
	
	
	
	
	cint:function(value){				//  parseInt  转成数字  整型
		if( (!value))	return 0;
		var number	=  parseInt(value,10);
		if(isNaN(number)) return 0;
		return number;
	},
	trim:function(str){							// 去掉前后空格 	
		if(!str)	return '';
		if(str==undefined) return '';
		if( ! isNaN(str) ) return str;
		return str.replace(/(^\s*)|(\s*$)/g, "");
	},
	show_set_address : function(){
		var _url = mk_url('user/userwiki'+ CONFIG['dkcode'] +'/index')
		$.confirm('网页提示','您未填写居住地信息，请完善后再创建本地生活网页。<br/><p>点击确认钮立即填写，否则请关闭。</p>',function(){
				location.href = _url;
		});
	}
 }
 
 $(document).ready(function(){

	var createWeb = new Class_createWeb();
	createWeb.init();
 })
 
 function perfect_address(){
	var _url = mk_url('user/' + CONFIG["dkcode"] + '/userwiki/index');
	$.alert('住址填写不完整，<a href="'+ _url + '">请点击此处完善</a>');
}