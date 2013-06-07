/* 
 *updataTime:2011-12-16
 *author:liangshanshan(ssinsky@hotmail.com)
 *desc:出生日期选择只到今天为止,将函数封装为类
 *example:var setyear= new SetYear ("sltYear","sltMonth","sltDay","xingzuo","span.xingzuo");
	      setyear.init();
 *before:<select id="checkYear"></select><select id="checkMonth"></select><select id="checkDay"></select>
 * 		
* */
/*******出生日期*********/

	function SetYear (sltYear,sltMonth,sltDay,valStore,valTip) {
		var self = this;
		this.$Year=document.getElementById(sltYear);
		this.$Month=document.getElementById(sltMonth);
		this.$Day=document.getElementById(sltDay);
	}
	SetYear.prototype={
		init:function(){
			this.BindYear();
			this.BindEvent();
		},
		BindYear:function(){
			var iYear = new Date().getFullYear();
			for(var i=iYear;i>1911;i--){
				this.$Year.options.add(new Option(" "+ i +" ", i));
			}
		},
		BindEvent:function(){
			var self = this;
			this.$Year.onchange=function(){
				var val=this.value;
				self.CheckYear(val);
			};
			this.$Month.onchange=function(){
				var val=this.value;
				self.CheckMonth(val);
			};
		},		
		CheckYear:function(obj){		
			var iYear = new Date().getFullYear();
			var iMonth = new Date().getMonth()+1;
			var iDay = new Date().getDate();
			var objMonth = this.$Month;
			var objDay = this.$Day;
			var option_M = objMonth.options[0]; 
			objMonth.options.length=0;
			objMonth.options.add(option_M);	   
			var option_D = objDay.options[0];
			objDay.options.length=0;
			objDay.options.add(option_D);

			if(obj!=-1){
				if(obj!=iYear){
					for(var i=1;i<=9;i++){
						this.$Month.options.add(new Option(" 0"+ i +" ", "0"+i));
					}
					for(var i=10;i<=12;i++){
						this.$Month.options.add(new Option(" "+ i +" ", i));
					}
				}else{
					if(iMonth<=9){
						for(var i=1;i<=iMonth;i++){
							this.$Month.options.add(new Option(" 0"+ i +" ","0"+i));
						}
					}else{
						for(var i=1;i<=9;i++){
							this.$Month.options.add(new Option(" 0"+ i +" ", "0"+i));
						}
						for(var i=10;i<=iMonth;i++){
							this.$Month.options.add(new Option(" "+ i +" ", i));
						}
					}
					
					
				}
			}
		},
		append:function(o,v){
			var option=new Option(v,v);  
    		o.options.add(option);			
		},		
		CheckMonth:function(obj){
			var iYearnow = new Date().getFullYear();
			var iMonthnow = new Date().getMonth()+1;
			var iDaynow = new Date().getDate();
			var iYear = this.$Year.value;
			var iMonth = this.$Month.value;
			var objDay= this.$Day;
			var option_f = objDay.options[0];
			objDay.options.length=0;
			objDay.options.add(option_f);			
		   if(iYearnow==iYear&&iMonthnow==iMonth){//日期只到今天
		   	 if(iDaynow<=9){
		   	 	for(var j=1;j<=iDaynow;j++){
		   	 		this.append(objDay,'0'+j);
		   	 	}
		   	 }else{
		   	 	for(var j=1;j<=9;j++){
		   	 		this.append(objDay,'0'+j);
		   	 	}
		   	 	for(var j=10;j<=iDaynow;j++){
		   	 		this.append(objDay,j);
		   	 	}
		   	 }
		   }else{
		   		if(iMonth==1||iMonth==3||iMonth==5||iMonth==7
		   			||iMonth==8||iMonth==10||iMonth==12){//大月
					for(var j=1;j<=9;j++){  
						this.append(objDay,'0'+j);  
					}
					for(var j=10;j<=31;j++){  
						this.append(objDay,j);  
					}
				}
				if(iMonth==4||iMonth==6||iMonth==9||iMonth==11){//小月
					for(var j=1;j<=9;j++){  
						this.append(objDay,'0'+j);  
					}
					for(var j=10;j<=30;j++){  
						this.append(objDay,j);  
					}
				}
				if(iMonth==2){
					if (iYear % 4 == 0 
						&& (iYear % 100 != 0 || iYear % 400 == 0)){//闰年
						for(var j=1;j<=9;j++){  
							this.append(objDay,'0'+j);  
						}
						for(var j=10;j<=29;j++){  
							this.append(objDay,j);  
						}
					}else{
						for(var j=1;j<=9;j++){  
							this.append(objDay,'0'+j);  
						}
						for(var j=10;j<=28;j++){  
							this.append(objDay,j);  
						}
					}
				}
		   }   
		}
	}